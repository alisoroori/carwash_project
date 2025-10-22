<?php
declare(strict_types=1);

namespace App\Classes;

/**
 * Secure Database Access Layer
 * Provides PDO-based database access with prepared statements
 */
class Database 
{
    private static $instance = null;
    private $pdo;
    
    /**
     * Private constructor to enforce singleton pattern
     */
    private function __construct() 
    {
        try {
            // Load configuration
            require_once __DIR__ . '/../includes/config.php';
            
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false, // Use real prepared statements
                \PDO::MYSQL_ATTR_FOUND_ROWS => true   // Return found rows instead of affected rows
            ];
            
            $this->pdo = new \PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (\PDOException $e) {
            // Log error but don't expose details
            error_log('Database connection failed: ' . $e->getMessage());
            throw new \Exception('خطا در اتصال به پایگاه داده');
        }
    }
    
    /**
     * Prevent cloning singleton instance
     */
    private function __clone() {}
    
    /**
     * Get singleton instance
     * 
     * @return Database Database instance
     */
    public static function getInstance(): Database 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Get PDO instance
     * 
     * @return \PDO PDO instance
     */
    public function getPdo(): \PDO 
    {
        return $this->pdo;
    }
    
    /**
     * Execute a query with parameters
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return \PDOStatement PDO statement
     * @throws \Exception When query fails
     */
    public function query(string $query, array $params = []): \PDOStatement 
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            // Log error but don't expose details
            error_log('Query failed: ' . $e->getMessage() . ' - Query: ' . $query);
            throw new \Exception('خطا در اجرای درخواست از پایگاه داده');
        }
    }
    
    /**
     * Fetch a single row
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return array|null Row data or null if not found
     */
    public function fetchOne(string $query, array $params = []): ?array 
    {
        $stmt = $this->query($query, $params);
        $result = $stmt->fetch();
        
        return $result !== false ? $result : null;
    }
    
    /**
     * Fetch all rows
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return array Rows data
     */
    public function fetchAll(string $query, array $params = []): array 
    {
        $stmt = $this->query($query, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Count rows
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return int Number of rows
     */
    public function count(string $query, array $params = []): int 
    {
        $stmt = $this->query($query, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Insert a row
     * 
     * @param string $table Table name
     * @param array $data Column data (key => value)
     * @return int|false Last insert ID or false on failure
     */
    public function insert(string $table, array $data) 
    {
        // Secure against SQL injection by sanitizing table name
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        
        $columns = array_keys($data);
        $placeholders = array_map(function($column) {
            return ":$column";
        }, $columns);
        
        $columnsStr = implode(', ', $columns);
        $placeholdersStr = implode(', ', $placeholders);
        
        $query = "INSERT INTO $table ($columnsStr) VALUES ($placeholdersStr)";
        
        try {
            $this->query($query, $data);
            return (int) $this->pdo->lastInsertId();
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Update rows
     * 
     * @param string $table Table name
     * @param array $data Column data to update (key => value)
     * @param array $where Where conditions (key => value)
     * @return bool Success status
     */
    public function update(string $table, array $data, array $where): bool 
    {
        // Secure against SQL injection by sanitizing table name
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        
        $set = [];
        $params = [];
        
        foreach ($data as $column => $value) {
            $set[] = "$column = :$column";
            $params[$column] = $value;
        }
        
        $whereConditions = [];
        foreach ($where as $column => $value) {
            $whereConditions[] = "$column = :where_$column";
            $params["where_$column"] = $value;
        }
        
        $setStr = implode(', ', $set);
        $whereStr = implode(' AND ', $whereConditions);
        
        $query = "UPDATE $table SET $setStr WHERE $whereStr";
        
        try {
            $this->query($query, $params);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Delete rows
     * 
     * @param string $table Table name
     * @param array $where Where conditions (key => value)
     * @return bool Success status
     */
    public function delete(string $table, array $where): bool 
    {
        // Secure against SQL injection by sanitizing table name
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        
        $whereConditions = [];
        $params = [];
        
        foreach ($where as $column => $value) {
            $whereConditions[] = "$column = :$column";
            $params[$column] = $value;
        }
        
        $whereStr = implode(' AND ', $whereConditions);
        
        $query = "DELETE FROM $table WHERE $whereStr";
        
        try {
            $this->query($query, $params);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Begin a transaction
     */
    public function beginTransaction(): void 
    {
        $this->pdo->beginTransaction();
    }
    
    /**
     * Commit a transaction
     */
    public function commit(): void 
    {
        $this->pdo->commit();
    }
    
    /**
     * Rollback a transaction
     */
    public function rollback(): void 
    {
        $this->pdo->rollBack();
    }
}