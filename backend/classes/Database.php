<?php
/**
 * Database Connection Class (PSR-4 Autoloaded)
 * Modernized PDO wrapper with prepared statements
 *
 * @package App\Classes
 * @namespace App\Classes
 */

namespace App\Classes;

use PDO;
use PDOException;

class Database {

    /**
     * Singleton instance
     * @var Database|null
     */
    private static $instance = null;

    /**
     * PDO connection
     * @var PDO|null
     */
    private $connection = null;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->connect();
    }

    /**
     * Get Database instance (Singleton)
     * @return Database
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish PDO connection using config constants if available
     * @return void
     * @throws PDOException
     */
    private function connect(): void {
        // Prefer config constants if defined (from backend/includes/config.php)
        $host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
        $db   = defined('DB_NAME') ? DB_NAME : 'carwash';
        $user = defined('DB_USER') ? DB_USER : 'root';
        $pass = defined('DB_PASS') ? DB_PASS : '';
        $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $host, $db, $charset);

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, $user, $pass, $options);
            // ensure proper charset
            $this->connection->exec("SET NAMES {$charset} COLLATE {$charset}_unicode_ci");
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            throw new PDOException('اتصال به دیتابیس ناموفق بود. لطفاً تنظیمات را بررسی کنید.');
        }
    }

    /**
     * Get raw PDO connection
     * @return PDO
     */
    public function getConnection(): PDO {
        return $this->connection;
    }

    /**
     * Execute prepared statement
     * @param string $sql
     * @param array $params
     * @return \PDOStatement
     * @throws PDOException
     */
    public function query(string $sql, array $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Query Error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw $e;
        }
    }

    /**
     * Fetch single row
     * @param string $sql
     * @param array $params
     * @return array|false
     */
    public function fetchOne(string $sql, array $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Fetch all rows
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insert record and return last insert id
     * @param string $table
     * @param array $data
     * @return int
     */
    public function insert(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        return (int) $this->connection->lastInsertId();
    }

    /**
     * Update record
     * @param string $table
     * @param array $data
     * @param array $where
     * @return int affected rows
     */
    public function update(string $table, array $data, array $where): int {
        $setParts = [];
        foreach ($data as $col => $val) {
            $setParts[] = "{$col} = :{$col}";
        }
        $setString = implode(', ', $setParts);

        $whereParts = [];
        foreach ($where as $col => $val) {
            $whereParts[] = "{$col} = :where_{$col}";
        }
        $whereString = implode(' AND ', $whereParts);

        $sql = "UPDATE {$table} SET {$setString} WHERE {$whereString}";

        $params = $data;
        foreach ($where as $col => $val) {
            $params["where_{$col}"] = $val;
        }

        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Delete record
     * @param string $table
     * @param array $where
     * @return int affected rows
     */
    public function delete(string $table, array $where): int {
        $whereParts = [];
        foreach ($where as $col => $val) {
            $whereParts[] = "{$col} = :{$col}";
        }
        $whereString = implode(' AND ', $whereParts);

        $sql = "DELETE FROM {$table} WHERE {$whereString}";
        $stmt = $this->query($sql, $where);
        return $stmt->rowCount();
    }

    /**
     * Check existence
     * @param string $table
     * @param array $where
     * @return bool
     */
    public function exists(string $table, array $where): bool {
        $whereParts = [];
        foreach ($where as $col => $val) {
            $whereParts[] = "{$col} = :{$col}";
        }
        $whereString = implode(' AND ', $whereParts);

        $sql = "SELECT COUNT(*) as cnt FROM {$table} WHERE {$whereString}";
        $result = $this->fetchOne($sql, $where);
        return !empty($result) && ((int)$result['cnt'] > 0);
    }

    /**
     * Count rows
     * @param string $table
     * @param array $where
     * @return int
     */
    public function count(string $table, array $where = []): int {
        $sql = "SELECT COUNT(*) as cnt FROM {$table}";
        $params = [];
        if (!empty($where)) {
            $parts = [];
            foreach ($where as $col => $val) {
                $parts[] = "{$col} = :{$col}";
                $params[$col] = $val;
            }
            $sql .= " WHERE " . implode(' AND ', $parts);
        }
        $result = $this->fetchOne($sql, $params);
        return (int)($result['cnt'] ?? 0);
    }

    /**
     * Transaction helpers
     */
    public function beginTransaction(): void {
        $this->connection->beginTransaction();
    }

    public function commit(): void {
        $this->connection->commit();
    }

    public function rollback(): void {
        $this->connection->rollBack();
    }

    /**
     * Prevent cloning and unserialization
     */
    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("Cannot unserialize Database singleton");
    }
}
?>