<?php
declare(strict_types=1);

namespace App\Models;

use App\Classes\Database;

/**
 * Booking Model
 */
class Booking_Model
{
    private $db;
    protected $table = 'bookings';
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Find booking by ID
     * 
     * @param int $id Booking ID
     * @return array|null Booking data or null if not found
     */
    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Find bookings by user ID
     * 
     * @param int $userId User ID
     * @return array Bookings data
     */
    public function findByUserId(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY booking_date DESC",
            ['user_id' => $userId]
        );
    }
    
    /**
     * Create new booking
     * 
     * @param array $bookingData Booking data
     * @return int|false Booking ID or false on failure
     */
    public function create(array $bookingData)
    {
        if (!isset($bookingData['created_at'])) {
            $bookingData['created_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->insert($this->table, $bookingData);
    }
    
    /**
     * Update booking
     * 
     * @param int $id Booking ID
     * @param array $bookingData Booking data
     * @return bool Success status
     */
    public function update(int $id, array $bookingData): bool
    {
        $bookingData['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->update($this->table, $bookingData, ['id' => $id]);
    }
    
    /**
     * Delete booking
     * 
     * @param int $id Booking ID
     * @return bool Success status
     */
    public function delete(int $id): bool
    {
        return $this->db->delete($this->table, ['id' => $id]);
    }
    
    /**
     * Find all bookings with optional filters
     * 
     * @param array $conditions WHERE conditions
     * @param string $orderBy Order by field
     * @param string $direction Order direction
     * @return array Bookings data
     */
    public function findAll(array $conditions = [], string $orderBy = 'booking_date', string $direction = 'DESC'): array
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
    
    /**
     * Get bookings with related user data
     * 
     * @param array $conditions WHERE conditions
     * @return array Bookings data with user info
     */
    public function findAllWithUserData(array $conditions = []): array
    {
        $sql = "SELECT b.*, u.name as user_name, u.email as user_email, u.phone as user_phone 
                FROM {$this->table} b
                LEFT JOIN users u ON b.user_id = u.id";
        
        $params = [];
        
        if (!empty($conditions)) {
            $whereConditions = [];
            
            foreach ($conditions as $column => $value) {
                $whereConditions[] = "b.$column = :$column";
                $params[$column] = $value;
            }
            
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $sql .= " ORDER BY b.booking_date DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Check booking availability
     * 
     * @param string $date Date
     * @param string $time Time slot
     * @param int $serviceId Service ID
     * @param int|null $excludeBookingId Booking ID to exclude (for updates)
     * @return bool True if available
     */
    public function isAvailable(string $date, string $time, int $serviceId, ?int $excludeBookingId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE booking_date = :date AND booking_time = :time AND service_id = :service_id";
        
        $params = [
            'date' => $date,
            'time' => $time,
            'service_id' => $serviceId
        ];
        
        if ($excludeBookingId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeBookingId;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        
        return ($result['count'] === 0 || $result['count'] === '0');
    }
}
