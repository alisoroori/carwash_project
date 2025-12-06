<?php
declare(strict_types=1);

namespace App\Classes;

/**
 * Database management class using PDO and Singleton pattern
 * Prevents SQL Injection attacks using prepared statements
 */
class Database
{
    /** @var Database|null Singleton instance */
    private static $instance = null;
    
    /** @var \PDO Database connection */
    private $pdo;
    
    /**
     * Private constructor to prevent direct instantiation
     * Establishes database connection using environment variables or defaults
     */
    private function __construct()
    {
        try {
            // Use environment variables or default settings
            $host = getenv('DB_HOST') ?: 'localhost';
            $name = getenv('DB_NAME') ?: 'carwash_db';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';
            
            // If environment variables aren't defined, use defined constants
            if (defined('DB_HOST')) {
                $host = DB_HOST;
                $name = DB_NAME;
                $user = DB_USER;
                $pass = DB_PASS;
            }
            
            $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
            
            // PDO settings for security and performance
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false // Use real prepared statements
            ];
            
            $this->pdo = new \PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            // Log error without exposing sensitive information
            error_log('Database connection failed: ' . $e->getMessage());
            throw new \Exception('Database connection error');
        }
    }
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserialize of the instance
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
    
    /**
     * Get class instance (Singleton pattern)
     * 
     * @return Database Singleton instance
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Get PDO object for direct access
     * 
     * @return \PDO PDO object
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }
    
    /**
     * Prepare an SQL statement
     * 
     * @param string $query SQL statement
     * @return \PDOStatement Prepared PDOStatement object
     */
    public function prepare(string $query): \PDOStatement
    {
        return $this->pdo->prepare($query);
    }
    
    /**
     * Execute a query with parameters
     * 
     * @param string $query SQL statement
     * @param array $params Optional parameters for binding
     * @return \PDOStatement Query result
     * @throws \Exception On error
     */
    public function query(string $query, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            // Log error with details for debugging
            error_log('Query failed: ' . $e->getMessage() . ' - Query: ' . $query);
            throw new \Exception('Error executing database query');
        }
    }
    
    /**
     * Fetch a single record
     * 
     * @param string $query SQL statement
     * @param array $params Parameters for binding
     * @return array|null Result as array or null if no result
     */
    public function fetchOne(string $query, array $params = []): ?array
    {
        $stmt = $this->query($query, $params);
        $result = $stmt->fetch();
        
        return $result !== false ? $result : null;
    }
    
    /**
     * Fetch all records
     * 
     * @param string $query SQL statement
     * @param array $params Parameters for binding
     * @return array Array of results
     */
    public function fetchAll(string $query, array $params = []): array
    {
        $stmt = $this->query($query, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Insert a new record
     * 
     * @param string $table Table name
     * @param array $data Record data (column_name => value)
     * @return int|false Inserted record ID or false on error
     */
    public function insert(string $table, array $data)
    {
        $columns = array_keys($data);
        $placeholders = array_map(function($col) {
            return ":$col";
        }, $columns);
        
        $columnsStr = implode(', ', $columns);
        $placeholdersStr = implode(', ', $placeholders);
        
        $query = "INSERT INTO $table ($columnsStr) VALUES ($placeholdersStr)";
        
        // Lightweight file for reliable insert diagnostics
        $debugLogFile = __DIR__ . '/../../../logs/db_insert_debug.log';
        $attemptLine = date('Y-m-d H:i:s') . " - INSERT ATTEMPT table=$table data=" . json_encode($data, JSON_UNESCAPED_UNICODE) . " query=" . $query . "\n";
        @file_put_contents($debugLogFile, $attemptLine, FILE_APPEND | LOCK_EX);
        // Also emit to PHP error_log so CLI and Apache logs show the attempt
        error_log($attemptLine);

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($data);
            $lastId = (int) $this->pdo->lastInsertId();
            // Log success
            $successLine = date('Y-m-d H:i:s') . " - INSERT SUCCESS table=$table lastInsertId=" . var_export($lastId, true) . "\n";
            @file_put_contents($debugLogFile, $successLine, FILE_APPEND | LOCK_EX);
            error_log($successLine);
            return $lastId;
        } catch (\PDOException $e) {
            // Capture PDO exception, error code and errorInfo if available
            $msg = $e->getMessage();
            $code = $e->getCode();
            $errorInfo = null;
            if (isset($stmt) && is_object($stmt)) {
                try {
                    $errorInfo = $stmt->errorInfo();
                } catch (\Throwable $_) {
                    $errorInfo = null;
                }
            }

            $logLine = date('Y-m-d H:i:s') . " - INSERT FAILED table=$table code=" . var_export($code, true) . " message=" . json_encode($msg) . " errorInfo=" . json_encode($errorInfo) . " query=" . $query . " data=" . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
            // Write to the dedicated debug file and to PHP error_log
            @file_put_contents($debugLogFile, $logLine, FILE_APPEND | LOCK_EX);
            error_log($logLine);
            return false;
        }
    }
    
    /**
     * Update records
     * 
     * @param string $table Table name
     * @param array $data New data (column_name => value)
     * @param array $where Update conditions (column_name => value)
     * @return bool Operation result (success or failure)
     */
    public function update(string $table, array $data, array $where): bool
    {
        $set = [];
        $params = [];
        
        // Build SET part of query
        foreach ($data as $column => $value) {
            $set[] = "$column = :set_$column";
            $params["set_$column"] = $value;
        }
        
        // Build WHERE part of query
        $whereConditions = [];
        foreach ($where as $column => $value) {
            $whereConditions[] = "$column = :where_$column";
            $params["where_$column"] = $value;
        }
        
        $setStr = implode(', ', $set);
        $whereStr = implode(' AND ', $whereConditions);
        
        $query = "UPDATE $table SET $setStr WHERE $whereStr";
        
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return true;
        } catch (\PDOException $e) {
            error_log('Update failed: ' . $e->getMessage() . ' - Query: ' . $query);
            return false;
        }
    }
    
    /**
     * Delete records
     * 
     * @param string $table Table name
     * @param array $where Delete conditions (column_name => value)
     * @return bool Operation result (success or failure)
     */
    public function delete(string $table, array $where): bool
    {
        $whereConditions = [];
        $params = [];
        
        // Build WHERE part of query
        foreach ($where as $column => $value) {
            $whereConditions[] = "$column = :$column";
            $params[$column] = $value;
        }
        
        $whereStr = implode(' AND ', $whereConditions);
        
        $query = "DELETE FROM $table WHERE $whereStr";
        
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return true;
        } catch (\PDOException $e) {
            error_log('Delete failed: ' . $e->getMessage() . ' - Query: ' . $query);
            return false;
        }
    }
    
    /**
     * Count records
     * 
     * @param string $table Table name
     * @param array $where Count conditions (optional)
     * @return int Number of records
     */
    public function count(string $table, array $where = []): int
    {
        $query = "SELECT COUNT(*) AS count FROM $table";
        $params = [];
        
        if (!empty($where)) {
            $whereConditions = [];
            
            foreach ($where as $column => $value) {
                $whereConditions[] = "$column = :$column";
                $params[$column] = $value;
            }
            
            $whereStr = implode(' AND ', $whereConditions);
            $query .= " WHERE $whereStr";
        }
        
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch();
            if (!$result || !is_array($result)) {
                return 0;
            }
            return (int) ($result['count'] ?? 0);
        } catch (\PDOException $e) {
            error_log('Count failed: ' . $e->getMessage() . ' - Query: ' . $query);
            return 0;
        }
    }
    
    /**
     * Begin transaction
     * 
     * @return bool Operation result
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     * 
     * @return bool Operation result
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transaction
     * 
     * @return bool Operation result
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }
}
