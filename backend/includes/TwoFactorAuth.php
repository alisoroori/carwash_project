<?php
/**
 * Two-Factor Authentication System
 * TOTP-based 2FA with backup codes
 * 
 * @package CarWash Admin
 * @author CarWash Team
 * @version 2.0
 * @created October 18, 2025
 */

class TwoFactorAuth {
    private $pdo;
    private $secretLength = 32;
    private $codeDigits = 6;
    private $timeStep = 30; // seconds
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Generate a new secret key for user
     */
    public function generateSecret() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 characters
        $secret = '';
        for ($i = 0; $i < $this->secretLength; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }
    
    /**
     * Generate backup codes
     */
    public function generateBackupCodes($count = 10) {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(md5(random_bytes(16)), 0, 8));
        }
        return $codes;
    }
    
    /**
     * Enable 2FA for user
     */
    public function enable($userId) {
        $secret = $this->generateSecret();
        $backupCodes = $this->generateBackupCodes();
        $hashedCodes = array_map(function($code) {
            return password_hash($code, PASSWORD_DEFAULT);
        }, $backupCodes);
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_2fa (user_id, secret_key, backup_codes, is_enabled, enabled_at)
                VALUES (?, ?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE 
                    secret_key = VALUES(secret_key),
                    backup_codes = VALUES(backup_codes),
                    is_enabled = 1,
                    enabled_at = NOW()
            ");
            
            $stmt->execute([$userId, $secret, json_encode($hashedCodes)]);
            
            return [
                'secret' => $secret,
                'backup_codes' => $backupCodes,
                'qr_url' => $this->getQRCodeUrl($userId, $secret)
            ];
        } catch (PDOException $e) {
            error_log("2FA Enable Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Disable 2FA for user
     */
    public function disable($userId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE user_2fa 
                SET is_enabled = 0, updated_at = NOW()
                WHERE user_id = ?
            ");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("2FA Disable Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if 2FA is enabled for user
     */
    public function isEnabled($userId) {
        $stmt = $this->pdo->prepare("
            SELECT is_enabled FROM user_2fa WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return (bool) $stmt->fetchColumn();
    }
    
    /**
     * Verify TOTP code
     */
    public function verifyCode($userId, $code) {
        // Get user's secret
        $stmt = $this->pdo->prepare("
            SELECT secret_key FROM user_2fa 
            WHERE user_id = ? AND is_enabled = 1
        ");
        $stmt->execute([$userId]);
        $secret = $stmt->fetchColumn();
        
        if (!$secret) {
            return false;
        }
        
        // Check current time window and adjacent windows (to account for time drift)
        $currentTime = time();
        for ($i = -1; $i <= 1; $i++) {
            $timeSlice = floor($currentTime / $this->timeStep) + $i;
            $expectedCode = $this->generateTOTP($secret, $timeSlice);
            
            if (hash_equals($expectedCode, $code)) {
                // Update last used time
                $updateStmt = $this->pdo->prepare("
                    UPDATE user_2fa SET last_used_at = NOW() WHERE user_id = ?
                ");
                $updateStmt->execute([$userId]);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verify backup code
     */
    public function verifyBackupCode($userId, $code) {
        $stmt = $this->pdo->prepare("
            SELECT backup_codes FROM user_2fa 
            WHERE user_id = ? AND is_enabled = 1
        ");
        $stmt->execute([$userId]);
        $backupCodesJson = $stmt->fetchColumn();
        
        if (!$backupCodesJson) {
            return false;
        }
        
        $backupCodes = json_decode($backupCodesJson, true);
        
        // Check each backup code
        foreach ($backupCodes as $index => $hashedCode) {
            if (password_verify($code, $hashedCode)) {
                // Remove used backup code
                unset($backupCodes[$index]);
                $backupCodes = array_values($backupCodes); // Reindex array
                
                // Update database
                $updateStmt = $this->pdo->prepare("
                    UPDATE user_2fa 
                    SET backup_codes = ?, last_used_at = NOW() 
                    WHERE user_id = ?
                ");
                $updateStmt->execute([json_encode($backupCodes), $userId]);
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate TOTP code
     */
    private function generateTOTP($secret, $timeSlice) {
        $key = $this->base32Decode($secret);
        $time = pack('N*', 0, $timeSlice);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[19]) & 0x0f;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % pow(10, $this->codeDigits);
        
        return str_pad($code, $this->codeDigits, '0', STR_PAD_LEFT);
    }
    
    /**
     * Base32 decode
     */
    private function base32Decode($secret) {
        $secret = strtoupper($secret);
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        
        for ($i = 0; $i < strlen($secret); $i++) {
            $val = strpos($chars, $secret[$i]);
            $bits .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
        }
        
        $result = '';
        for ($i = 0; $i < strlen($bits); $i += 8) {
            if (strlen($bits) - $i < 8) break;
            $result .= chr(bindec(substr($bits, $i, 8)));
        }
        
        return $result;
    }
    
    /**
     * Get QR code URL for authenticator apps
     */
    public function getQRCodeUrl($userId, $secret) {
        // Get user details
        $stmt = $this->pdo->prepare("SELECT email, user_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $issuer = 'CarWash Admin';
        $label = $user['email'] ?? $user['user_name'];
        
        $otpauthUrl = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s',
            rawurlencode($issuer),
            rawurlencode($label),
            $secret,
            rawurlencode($issuer)
        );
        
        // Use Google Charts API for QR code generation
        return 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($otpauthUrl);
    }
    
    /**
     * Get user 2FA status and details
     */
    public function getStatus($userId) {
        $stmt = $this->pdo->prepare("
            SELECT is_enabled, enabled_at, last_used_at,
                   JSON_LENGTH(backup_codes) as remaining_backup_codes
            FROM user_2fa 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Regenerate backup codes
     */
    public function regenerateBackupCodes($userId) {
        $backupCodes = $this->generateBackupCodes();
        $hashedCodes = array_map(function($code) {
            return password_hash($code, PASSWORD_DEFAULT);
        }, $backupCodes);
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE user_2fa 
                SET backup_codes = ?, updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([json_encode($hashedCodes), $userId]);
            
            return $backupCodes;
        } catch (PDOException $e) {
            error_log("2FA Regenerate Backup Codes Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Require 2FA verification in session
     */
    public function requireVerification($userId) {
        if (!$this->isEnabled($userId)) {
            return true; // 2FA not enabled, no verification needed
        }
        
        // Check if already verified in this session
        if (isset($_SESSION['2fa_verified']) && $_SESSION['2fa_verified'] === true) {
            return true;
        }
        
        // Redirect to 2FA verification page
        header('Location: /carwash_project/backend/auth/verify_2fa.php');
        exit;
    }
    
    /**
     * Mark 2FA as verified in session
     */
    public function markVerified() {
        $_SESSION['2fa_verified'] = true;
        $_SESSION['2fa_verified_at'] = time();
    }
    
    /**
     * Clear 2FA verification from session
     */
    public function clearVerification() {
        unset($_SESSION['2fa_verified']);
        unset($_SESSION['2fa_verified_at']);
    }
}
