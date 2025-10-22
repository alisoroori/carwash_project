<?php
declare(strict_types=1);

namespace App\Models;

use App\Classes\Database;

/**
 * User Model
 */
class User_Model
{
    private $db;
    protected $table = 'users';
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Find user by ID
     * 
     * @param int $id User ID
     * @return array|null User data or null if not found
     */
    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Find user by email
     * 
     * @param string $email User email
     * @return array|null User data or null if not found
     */
    public function findByEmail(string $email): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE email = :email",
            ['email' => $email]
        );
    }
    
    /**
     * Create new user
     * 
     * @param array $userData User data
     * @return int|false User ID or false on failure
     */
    public function create(array $userData)
    {
        // Hash password if provided
        if (isset($userData['password'])) {
            $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        }
        
        // Add creation timestamp if not set
        if (!isset($userData['created_at'])) {
            $userData['created_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->insert($this->table, $userData);
    }
    
    /**
     * Update user
     * 
     * @param int $id User ID
     * @param array $userData User data
     * @return bool Success status
     */
    public function update(int $id, array $userData): bool
    {
        // Hash password if provided
        if (isset($userData['password'])) {
            $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        }
        
        // Add update timestamp
        $userData['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->update($this->table, $userData, ['id' => $id]);
    }
    
    /**
     * Delete user
     * 
     * @param int $id User ID
     * @return bool Success status
     */
    public function delete(int $id): bool
    {
        return $this->db->delete($this->table, ['id' => $id]);
    }
    
    /**
     * Find all users with optional filters
     * 
     * @param array $conditions WHERE conditions
     * @param string $orderBy Order by field
     * @param string $direction Order direction
     * @return array Users data
     */
    public function findAll(array $conditions = [], string $orderBy = 'id', string $direction = 'ASC'): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereConditions = [];
            
            foreach ($conditions as $column => $value) {
                $whereConditions[] = "$column = :$column";
                $params[$column] = $value;
            }
            
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $sql .= " ORDER BY $orderBy $direction";
        
        return $this->db->fetchAll($sql, $params);
    }
}