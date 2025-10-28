<?php
declare(strict_types=1);

namespace App\Classes;

class DataAggregator
{
    private $conn;
    private $cache;
    private $cacheTimeout = 300; // 5 minutes

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->cache = new CacheManager();
    }

    public function getDashboardData($userId, $role)
    {
        $cacheKey = "dashboard_data_{$userId}_{$role}";

        // Try to get from cache first
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData !== null) {
            return $cachedData;
        }

        // Collect fresh data
        $data = [
            'stats' => $this->getStats($role),
            'charts' => $this->getChartData($role),
            'recent' => $this->getRecentActivity($userId, $role)
        ];

        // Cache the result
        $this->cache->set($cacheKey, $data, $this->cacheTimeout);

        return $data;
    }

    private function getStats($role)
    {
        switch ($role) {
            case 'admin':
                return $this->getAdminStats();
            case 'carwash':
                return $this->getCarwashStats();
            default:
                return $this->getUserStats();
        }
    }

    private function getChartData($role)
    {
        $stmt = $this->conn->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                SUM(amount) as revenue
            FROM bookings
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

