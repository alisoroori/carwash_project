-- Security updates for carwash database

-- Add security fields to users table
ALTER TABLE users 
ADD COLUMN login_attempts INT DEFAULT 0,
ADD COLUMN last_login_attempt DATETIME DEFAULT NULL,
ADD COLUMN password_reset_token VARCHAR(100) DEFAULT NULL,
ADD COLUMN password_reset_expires DATETIME DEFAULT NULL,
ADD COLUMN remember_token VARCHAR(100) DEFAULT NULL,
ADD COLUMN status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add audit log table for security tracking
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actor_id INT DEFAULT NULL,
    actor_role VARCHAR(50) DEFAULT NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id VARCHAR(50) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    request_id VARCHAR(50) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Add security settings table
CREATE TABLE IF NOT EXISTS security_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (setting_key)
);

-- Insert default security settings
INSERT INTO security_settings (setting_key, setting_value) VALUES
('max_login_attempts', '5'),
('login_timeout_minutes', '15'),
('password_min_length', '8'),
('session_lifetime_minutes', '30'),
('require_2fa_for_admin', 'false'),
('ip_whitelist', '');