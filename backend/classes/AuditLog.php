<?php
declare(strict_types=1);

namespace App\Classes;

/**
 * Audit Logging System
 * Immutable audit trail for all admin actions
 * 
 * @package CarWash Admin
 * @author CarWash Team
 * @version 2.0
 */

class AuditLog 
{
    private $pdo;
    private $actorId;
    private $actorRole;
    private $ipAddress;
    private $userAgent;
    private $requestId;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->actorId = $_SESSION['user_id'] ?? null;
        $this->actorRole = $_SESSION['role'] ?? null;
        $this->ipAddress = $this->getClientIP();
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $this->requestId = $this->generateRequestId();
    }
    
    /**
     * Log an action
     * 
     * @param string $action Action performed (create, update, delete, approve, etc)
     * @param string $entityType Entity type (order, payment, user, etc)
     * @param int $entityId Entity ID
     * @param array|null $oldValues State before action
     * @param array|null $newValues State after action
     * @param string|null $description Human-readable description
     * @return bool
     */
    public function log($action, $entityType, $entityId, $oldValues = null, $newValues = null, $description = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO audit_logs (
                    actor_id, actor_role, action, entity_type, entity_id,
                    description, old_values, new_values,
                    ip_address, user_agent, request_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $oldJson = $oldValues ? json_encode($oldValues) : null;
            $newJson = $newValues ? json_encode($newValues) : null;
            
            return $stmt->execute([
                $this->actorId,
                $this->actorRole,
                $action,
                $entityType,
                $entityId,
                $description,
                $oldJson,
                $newJson,
                $this->ipAddress,
                $this->userAgent,
                $this->requestId
            ]);
        } catch (PDOException $e) {
            error_log("Audit Log Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log create action
     */
    public function logCreate($entityType, $entityId, $values, $description = null) {
        $description = $description ?? "Created $entityType #$entityId";
        return $this->log('create', $entityType, $entityId, null, $values, $description);
    }
    
    /**
     * Log update action
     */
    public function logUpdate($entityType, $entityId, $oldValues, $newValues, $description = null) {
        $description = $description ?? "Updated $entityType #$entityId";
        return $this->log('update', $entityType, $entityId, $oldValues, $newValues, $description);
    }
    
    /**
     * Log delete action
     */
    public function logDelete($entityType, $entityId, $oldValues, $description = null) {
        $description = $description ?? "Deleted $entityType #$entityId";
        return $this->log('delete', $entityType, $entityId, $oldValues, null, $description);
    }
    
    /**
     * Log approve action
     */
    public function logApprove($entityType, $entityId, $description = null) {
        $description = $description ?? "Approved $entityType #$entityId";
        return $this->log('approve', $entityType, $entityId, null, null, $description);
    }
    
    /**
     * Log reject action
     */
    public function logReject($entityType, $entityId, $reason = null, $description = null) {
        $description = $description ?? "Rejected $entityType #$entityId";
        $values = $reason ? ['reason' => $reason] : null;
        return $this->log('reject', $entityType, $entityId, null, $values, $description);
    }
    
    /**
     * Log cancel action
     */
    public function logCancel($entityType, $entityId, $reason = null, $description = null) {
        $description = $description ?? "Cancelled $entityType #$entityId";
        $values = $reason ? ['reason' => $reason] : null;
        return $this->log('cancel', $entityType, $entityId, null, $values, $description);
    }
    
    /**
     * Log status change
     */
    public function logStatusChange($entityType, $entityId, $oldStatus, $newStatus, $description = null) {
        $description = $description ?? "Changed $entityType #$entityId status from $oldStatus to $newStatus";
        return $this->log(
            'status_change',
            $entityType,
            $entityId,
            ['status' => $oldStatus],
            ['status' => $newStatus],
            $description
        );
    }
    
    /**
     * Log bulk action
     */
    public function logBulkAction($action, $entityType, array $entityIds, $description = null) {
        $count = count($entityIds);
        $description = $description ?? "Bulk $action on $count $entityType items";
        
        return $this->log(
            "bulk_$action",
            $entityType,
            0, // No single entity ID for bulk
            null,
            ['entity_ids' => $entityIds, 'count' => $count],
            $description
        );
    }
    
    /**
     * Log login
     */
    public function logLogin($userId, $success = true) {
        $action = $success ? 'login_success' : 'login_failed';
        $description = $success ? "User logged in successfully" : "Failed login attempt";
        
        return $this->log($action, 'user', $userId, null, null, $description);
    }
    
    /**
     * Log logout
     */
    public function logLogout($userId) {
        return $this->log('logout', 'user', $userId, null, null, "User logged out");
    }
    
    /**
     * Log impersonation
     */
    public function logImpersonation($targetUserId, $targetUserName) {
        return $this->log(
            'impersonate',
            'user',
            $targetUserId,
            null,
            ['impersonated_user' => $targetUserName],
            "Admin impersonated user: $targetUserName"
        );
    }
    
    /**
     * Get audit logs for an entity
     */
    public function getEntityLogs($entityType, $entityId, $limit = 50) {
        $stmt = $this->pdo->prepare("
            SELECT al.*, u.user_name as actor_name, u.full_name as actor_full_name
            FROM audit_logs al
            LEFT JOIN users u ON al.actor_id = u.id
            WHERE al.entity_type = ? AND al.entity_id = ?
            ORDER BY al.created_at DESC
            LIMIT ?
        ");
        
        $stmt->execute([$entityType, $entityId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get recent logs for dashboard
     */
    public function getRecentLogs($limit = 20, $filters = []) {
        $where = [];
        $params = [];
        
        if (!empty($filters['actor_id'])) {
            $where[] = "al.actor_id = ?";
            $params[] = $filters['actor_id'];
        }
        
        if (!empty($filters['action'])) {
            $where[] = "al.action = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['entity_type'])) {
            $where[] = "al.entity_type = ?";
            $params[] = $filters['entity_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(al.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(al.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $stmt = $this->pdo->prepare("
            SELECT al.*, u.user_name as actor_name, u.full_name as actor_full_name
            FROM audit_logs al
            LEFT JOIN users u ON al.actor_id = u.id
            $whereClause
            ORDER BY al.created_at DESC
            LIMIT ?
        ");
        
        $params[] = $limit;
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get audit statistics
     */
    public function getStatistics($dateFrom = null, $dateTo = null) {
        $dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $dateTo ?? date('Y-m-d');
        
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_actions,
                COUNT(DISTINCT actor_id) as unique_actors,
                COUNT(DISTINCT entity_type) as entity_types_affected,
                action,
                COUNT(*) as action_count
            FROM audit_logs
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY action
            ORDER BY action_count DESC
        ");
        
        $stmt->execute([$dateFrom, $dateTo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search audit logs
     */
    public function search($query, $limit = 50) {
        $stmt = $this->pdo->prepare("
            SELECT al.*, u.user_name as actor_name, u.full_name as actor_full_name
            FROM audit_logs al
            LEFT JOIN users u ON al.actor_id = u.id
            WHERE al.description LIKE ? 
                OR al.entity_type LIKE ?
                OR u.user_name LIKE ?
                OR u.full_name LIKE ?
            ORDER BY al.created_at DESC
            LIMIT ?
        ");
        
        $searchTerm = "%$query%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get client IP address (handles proxies)
     */
    private function getClientIP() {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Generate unique request ID for tracing
     */
    private function generateRequestId() {
        return uniqid('req_', true) . '_' . bin2hex(random_bytes(8));
    }
    
    /**
     * Format audit log for display
     */
    public static function formatLog($log) {
        $formatted = [
            'id' => $log['id'],
            'action' => ucwords(str_replace('_', ' ', $log['action'])),
            'entity' => ucfirst($log['entity_type']) . ' #' . $log['entity_id'],
            'actor' => $log['actor_full_name'] ?? $log['actor_name'] ?? 'System',
            'description' => $log['description'],
            'time' => date('Y-m-d H:i:s', strtotime($log['created_at'])),
            'time_ago' => self::timeAgo($log['created_at']),
            'ip' => $log['ip_address']
        ];
        
        // Add changes if available
        if ($log['old_values'] && $log['new_values']) {
            $old = json_decode($log['old_values'], true);
            $new = json_decode($log['new_values'], true);
            $formatted['changes'] = self::formatChanges($old, $new);
        }
        
        return $formatted;
    }
    
    /**
     * Format changes between old and new values
     */
    private static function formatChanges($old, $new) {
        $changes = [];
        foreach ($new as $key => $newValue) {
            $oldValue = $old[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[] = [
                    'field' => ucfirst(str_replace('_', ' ', $key)),
                    'from' => $oldValue,
                    'to' => $newValue
                ];
            }
        }
        return $changes;
    }
    
    /**
     * Convert timestamp to "time ago" format
     */
    private static function timeAgo($timestamp) {
        $time = time() - strtotime($timestamp);
        
        if ($time < 60) return $time . ' saniye önce';
        if ($time < 3600) return floor($time / 60) . ' dakika önce';
        if ($time < 86400) return floor($time / 3600) . ' saat önce';
        if ($time < 2592000) return floor($time / 86400) . ' gün önce';
        if ($time < 31536000) return floor($time / 2592000) . ' ay önce';
        return floor($time / 31536000) . ' yıl önce';
    }
}

