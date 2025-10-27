-- =====================================================
-- CarWash Admin Dashboard - Enterprise Schema
-- Created: October 18, 2025
-- Purpose: Complete admin system with RBAC, audit logs, and enterprise features
-- =====================================================

-- =====================================================
-- ROLES AND PERMISSIONS SYSTEM
-- =====================================================

CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `display_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `level` INT(11) NOT NULL DEFAULT 0 COMMENT 'Priority level: 100=SuperAdmin, 80=Admin, 60=Manager, 40=Support, 20=Auditor',
    `permissions` JSON NOT NULL COMMENT 'Array of permission strings',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_level` (`level`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `category` VARCHAR(50) NOT NULL COMMENT 'users, orders, payments, settings, etc',
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_user` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) UNSIGNED NOT NULL,
    `role_id` INT(11) UNSIGNED NOT NULL,
    `assigned_by` INT(11) UNSIGNED,
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_role` (`user_id`, `role_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TWO-FACTOR AUTHENTICATION
-- =====================================================

CREATE TABLE IF NOT EXISTS `user_2fa` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) UNSIGNED NOT NULL UNIQUE,
    `secret_key` VARCHAR(255) NOT NULL,
    `backup_codes` JSON COMMENT 'Array of one-time backup codes',
    `is_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `enabled_at` TIMESTAMP NULL,
    `last_used_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- AUDIT LOGS (Immutable)
-- =====================================================

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `actor_id` INT(11) UNSIGNED NOT NULL COMMENT 'User who performed the action',
    `actor_role` VARCHAR(50),
    `action` VARCHAR(100) NOT NULL COMMENT 'create, update, delete, approve, reject, etc',
    `entity_type` VARCHAR(50) NOT NULL COMMENT 'order, payment, user, service, etc',
    `entity_id` INT(11) UNSIGNED NOT NULL,
    `description` TEXT,
    `old_values` JSON COMMENT 'State before action',
    `new_values` JSON COMMENT 'State after action',
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `request_id` VARCHAR(100) COMMENT 'Unique request identifier for tracing',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_actor_id` (`actor_id`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECURITY LOGS
-- =====================================================

CREATE TABLE IF NOT EXISTS `security_logs` (
    `id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) UNSIGNED,
    `event_type` ENUM('login_success', 'login_failed', 'logout', '2fa_enabled', '2fa_disabled', 'password_changed', 'suspicious_activity', 'rate_limit_exceeded') NOT NULL,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `details` JSON,
    `severity` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_event_type` (`event_type`),
    INDEX `idx_severity` (`severity`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SUPPORT TICKETS
-- =====================================================

CREATE TABLE IF NOT EXISTS `support_tickets` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ticket_number` VARCHAR(20) NOT NULL UNIQUE,
    `user_id` INT(11) UNSIGNED NOT NULL,
    `assigned_to` INT(11) UNSIGNED COMMENT 'Admin/Support user assigned',
    `subject` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `category` ENUM('technical', 'billing', 'service', 'complaint', 'other') NOT NULL,
    `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    `status` ENUM('new', 'open', 'in_progress', 'waiting_on_user', 'resolved', 'closed', 'cancelled') DEFAULT 'new',
    `related_order_id` INT(11) UNSIGNED COMMENT 'Link to order if relevant',
    `first_response_at` TIMESTAMP NULL,
    `resolved_at` TIMESTAMP NULL,
    `closed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_assigned_to` (`assigned_to`),
    INDEX `idx_status` (`status`),
    INDEX `idx_priority` (`priority`),
    INDEX `idx_ticket_number` (`ticket_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ticket_replies` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT(11) UNSIGNED NOT NULL,
    `user_id` INT(11) UNSIGNED NOT NULL,
    `message` TEXT NOT NULL,
    `is_internal` TINYINT(1) DEFAULT 0 COMMENT 'Internal note not visible to customer',
    `attachments` JSON COMMENT 'Array of file paths',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets`(`id`) ON DELETE CASCADE,
    INDEX `idx_ticket_id` (`ticket_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SERVICES MANAGEMENT
-- =====================================================

CREATE TABLE IF NOT EXISTS `services` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `icon` VARCHAR(50),
    `category` VARCHAR(50),
    `base_price` DECIMAL(10,2) NOT NULL,
    `duration_minutes` INT(11) NOT NULL,
    `vehicle_types` JSON COMMENT 'Array: sedan, suv, truck, etc with price modifiers',
    `is_active` TINYINT(1) DEFAULT 1,
    `is_featured` TINYINT(1) DEFAULT 0,
    `display_order` INT(11) DEFAULT 0,
    `requirements` TEXT COMMENT 'Special requirements or notes',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_category` (`category`),
    INDEX `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- REVIEWS & RATINGS
-- =====================================================

CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT(11) UNSIGNED NOT NULL,
    `user_id` INT(11) UNSIGNED NOT NULL,
    `carwash_id` INT(11) UNSIGNED NOT NULL,
    `service_id` INT(11) UNSIGNED,
    `rating` TINYINT(1) NOT NULL COMMENT '1-5 stars',
    `title` VARCHAR(255),
    `comment` TEXT,
    `pros` TEXT,
    `cons` TEXT,
    `images` JSON COMMENT 'Array of uploaded image paths',
    `status` ENUM('pending', 'approved', 'rejected', 'flagged') DEFAULT 'pending',
    `moderated_by` INT(11) UNSIGNED,
    `moderated_at` TIMESTAMP NULL,
    `moderation_note` TEXT,
    `is_verified_purchase` TINYINT(1) DEFAULT 1,
    `helpful_count` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_carwash_id` (`carwash_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- NOTIFICATIONS SYSTEM
-- =====================================================

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL COMMENT 'order_status, payment_success, ticket_reply, etc',
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `action_url` VARCHAR(255),
    `action_text` VARCHAR(50),
    `data` JSON COMMENT 'Additional metadata',
    `channels` JSON COMMENT 'Array: in_app, email, sms, push',
    `is_read` TINYINT(1) DEFAULT 0,
    `read_at` TIMESTAMP NULL,
    `sent_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_is_read` (`is_read`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notification_templates` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `event` VARCHAR(100) NOT NULL,
    `channel` ENUM('email', 'sms', 'push', 'in_app') NOT NULL,
    `subject` VARCHAR(255),
    `body` TEXT NOT NULL,
    `variables` JSON COMMENT 'Available template variables',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_event` (`event`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CMS PAGES
-- =====================================================

CREATE TABLE IF NOT EXISTS `cms_pages` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `title` VARCHAR(255) NOT NULL,
    `content` LONGTEXT,
    `meta_title` VARCHAR(255),
    `meta_description` TEXT,
    `meta_keywords` VARCHAR(255),
    `is_published` TINYINT(1) DEFAULT 0,
    `published_at` TIMESTAMP NULL,
    `author_id` INT(11) UNSIGNED,
    `last_edited_by` INT(11) UNSIGNED,
    `template` VARCHAR(50) DEFAULT 'default',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_slug` (`slug`),
    INDEX `idx_is_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `media_library` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_type` VARCHAR(50),
    `file_size` INT(11),
    `mime_type` VARCHAR(100),
    `uploaded_by` INT(11) UNSIGNED,
    `alt_text` VARCHAR(255),
    `title` VARCHAR(255),
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_uploaded_by` (`uploaded_by`),
    INDEX `idx_file_type` (`file_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SYSTEM SETTINGS
-- =====================================================

CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT,
    `type` ENUM('string', 'number', 'boolean', 'json', 'text') DEFAULT 'string',
    `category` VARCHAR(50) COMMENT 'general, payment, notification, security, etc',
    `description` TEXT,
    `is_public` TINYINT(1) DEFAULT 0 COMMENT 'Can be accessed by frontend',
    `updated_by` INT(11) UNSIGNED,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`),
    INDEX `idx_is_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DATABASE BACKUPS LOG
-- =====================================================

CREATE TABLE IF NOT EXISTS `backup_logs` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_size` BIGINT(20),
    `backup_type` ENUM('manual', 'scheduled', 'auto') DEFAULT 'manual',
    `tables_included` JSON,
    `created_by` INT(11) UNSIGNED,
    `status` ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    `error_message` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_backup_type` (`backup_type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Default Roles
INSERT INTO `roles` (`name`, `display_name`, `description`, `level`, `permissions`) VALUES
('superadmin', 'Super Administrator', 'Full system access with all permissions', 100, JSON_ARRAY('*')),
('admin', 'Administrator', 'Full admin access except system-critical operations', 80, JSON_ARRAY('users.*', 'orders.*', 'payments.*', 'carwash_profiles.*', 'services.*', 'support.*', 'reviews.*', 'reports.*', 'notifications.*', 'cms.*', 'settings.view', 'settings.edit')),
('manager', 'Manager', 'Manage operations and view reports', 60, JSON_ARRAY('orders.*', 'payments.view', 'carwashes.view', 'services.*', 'support.*', 'reviews.moderate', 'reports.view')),
('support', 'Support Agent', 'Handle customer support and tickets', 40, JSON_ARRAY('users.view', 'orders.view', 'support.*', 'reviews.view', 'notifications.send')),
('auditor', 'Auditor', 'Read-only access to all sections for compliance', 20, JSON_ARRAY('*.view', 'audit_logs.view', 'reports.view'))
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

-- Default Permissions
INSERT INTO `permissions` (`name`, `category`, `description`) VALUES
('users.view', 'users', 'View user list and details'),
('users.create', 'users', 'Create new users'),
('users.edit', 'users', 'Edit user information'),
('users.delete', 'users', 'Delete or suspend users'),
('users.impersonate', 'users', 'Login as another user'),
('orders.view', 'orders', 'View orders'),
('orders.create', 'orders', 'Create new orders'),
('orders.edit', 'orders', 'Edit order details'),
('orders.cancel', 'orders', 'Cancel orders'),
('orders.approve', 'orders', 'Approve orders'),
('payments.view', 'payments', 'View payment transactions'),
('payments.approve', 'payments', 'Approve pending payments'),
('payments.refund', 'payments', 'Process refunds'),
('payments.settle', 'payments', 'Settle car wash payments'),
('carwash_profiles.view', 'carwash_profiles', 'View car washes'),
('carwash_profiles.create', 'carwash_profiles', 'Add new car washes'),
('carwash_profiles.edit', 'carwash_profiles', 'Edit car wash details'),
('carwash_profiles.delete', 'carwash_profiles', 'Delete car washes'),
('carwash_profiles.approve', 'carwash_profiles', 'Approve car wash registrations'),
('services.view', 'services', 'View services'),
('services.create', 'services', 'Create new services'),
('services.edit', 'services', 'Edit service details'),
('services.delete', 'services', 'Delete services'),
('support.view', 'support', 'View support tickets'),
('support.create', 'support', 'Create tickets'),
('support.edit', 'support', 'Update ticket status'),
('support.reply', 'support', 'Reply to tickets'),
('support.close', 'support', 'Close tickets'),
('reviews.view', 'reviews', 'View reviews'),
('reviews.moderate', 'reviews', 'Approve or reject reviews'),
('reviews.delete', 'reviews', 'Delete reviews'),
('reports.view', 'reports', 'View all reports'),
('reports.export', 'reports', 'Export reports'),
('notifications.send', 'notifications', 'Send notifications'),
('notifications.manage', 'notifications', 'Manage notification templates'),
('cms.view', 'cms', 'View CMS pages'),
('cms.edit', 'cms', 'Edit CMS content'),
('cms.publish', 'cms', 'Publish CMS pages'),
('settings.view', 'settings', 'View system settings'),
('settings.edit', 'settings', 'Edit system settings'),
('audit_logs.view', 'audit', 'View audit logs'),
('security.view', 'security', 'View security logs'),
('backup.create', 'backup', 'Create database backups'),
('backup.restore', 'backup', 'Restore from backups')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- Default Settings
INSERT INTO `settings` (`key`, `value`, `type`, `category`, `description`, `is_public`) VALUES
('site_name', 'CarWash Management System', 'string', 'general', 'Website name', 1),
('site_email', 'admin@carwash.com', 'string', 'general', 'Main contact email', 0),
('commission_rate', '15', 'number', 'payment', 'Commission percentage for car washes', 0),
('currency', 'TRY', 'string', 'payment', 'Default currency', 1),
('2fa_required', '0', 'boolean', 'security', 'Require 2FA for all admin users', 0),
('session_timeout', '3600', 'number', 'security', 'Session timeout in seconds', 0),
('max_login_attempts', '5', 'number', 'security', 'Maximum login attempts before lockout', 0),
('backup_retention_days', '30', 'number', 'backup', 'Days to keep backup files', 0),
('notification_email_enabled', '1', 'boolean', 'notification', 'Enable email notifications', 0),
('notification_sms_enabled', '0', 'boolean', 'notification', 'Enable SMS notifications', 0)
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

-- Default Notification Templates
INSERT INTO `notification_templates` (`name`, `event`, `channel`, `subject`, `body`, `variables`) VALUES
('order_created', 'order.created', 'email', 'Sipariş Oluşturuldu #{{order_number}}', 'Merhaba {{customer_name}},\n\nSiparişiniz başarıyla oluşturuldu.\n\nSipariş No: {{order_number}}\nHizmet: {{service_name}}\nTutar: {{amount}}\n\nTeşekkürler!', JSON_ARRAY('customer_name', 'order_number', 'service_name', 'amount')),
('order_completed', 'order.completed', 'email', 'Siparişiniz Tamamlandı #{{order_number}}', 'Merhaba {{customer_name}},\n\nSiparişiniz tamamlanmıştır.\n\nLütfen hizmetimizi değerlendirin.\n\nTeşekkürler!', JSON_ARRAY('customer_name', 'order_number')),
('payment_success', 'payment.success', 'email', 'Ödeme Başarılı', 'Ödemeniz başarıyla alınmıştır.\n\nTutar: {{amount}}\nİşlem No: {{transaction_id}}', JSON_ARRAY('amount', 'transaction_id')),
('ticket_created', 'ticket.created', 'email', 'Destek Talebi Alındı #{{ticket_number}}', 'Destek talebiniz alınmıştır. Ekibimiz en kısa sürede size dönüş yapacaktır.', JSON_ARRAY('ticket_number'))
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

-- =====================================================
-- TRIGGERS FOR AUDIT LOGGING
-- =====================================================

DELIMITER $$

-- Trigger: Log user updates
CREATE TRIGGER IF NOT EXISTS `after_user_update` 
AFTER UPDATE ON `users`
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status OR OLD.role != NEW.role THEN
        INSERT INTO `audit_logs` (
            `actor_id`, 
            `action`, 
            `entity_type`, 
            `entity_id`, 
            `description`,
            `old_values`, 
            `new_values`,
            `ip_address`
        ) VALUES (
            @current_user_id,
            'update',
            'user',
            NEW.id,
            CONCAT('User ', NEW.user_name, ' updated'),
            JSON_OBJECT('status', OLD.status, 'role', OLD.role),
            JSON_OBJECT('status', NEW.status, 'role', NEW.role),
            @current_ip_address
        );
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- VIEWS FOR REPORTING
-- =====================================================

CREATE OR REPLACE VIEW `v_admin_dashboard_stats` AS
SELECT 
    (SELECT COUNT(*) FROM `users` WHERE DATE(`created_at`) = CURDATE()) AS new_users_today,
    (SELECT COUNT(*) FROM `orders` WHERE `status` = 'pending') AS pending_orders,
    (SELECT COUNT(*) FROM `support_tickets` WHERE `status` IN ('new', 'open')) AS open_tickets,
    (SELECT SUM(`amount`) FROM `payments` WHERE DATE(`created_at`) = CURDATE() AND `status` = 'success') AS revenue_today;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Additional indexes will be added based on query patterns

-- =====================================================
-- END OF SCHEMA
-- =====================================================
