-- Business Profiles Table Migration
-- Stores car wash business information including logo and working hours

CREATE TABLE IF NOT EXISTS `business_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `address` text,
  `phone` varchar(20),
  `mobile_phone` varchar(20),
  `email` varchar(255),
  `logo_path` varchar(500),
  `working_hours` text COMMENT 'JSON format: {"monday": {"start": "09:00", "end": "18:00"}, ...}',
  `certificate_path` varchar(500),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_business_name` (`business_name`),
  CONSTRAINT `fk_business_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add some sample data for testing
INSERT INTO `business_profiles` (`user_id`, `business_name`, `address`, `phone`, `mobile_phone`, `email`, `working_hours`, `created_at`, `updated_at`) 
VALUES 
(1, 'CarWash Merkez', 'İstanbul, Kadıköy, Moda Mahallesi, No: 123', '0216 555 1234', '0555 123 4567', 'info@carwash-merkez.com', 
 '{"monday":{"start":"09:00","end":"18:00"},"tuesday":{"start":"09:00","end":"18:00"},"wednesday":{"start":"09:00","end":"18:00"},"thursday":{"start":"09:00","end":"18:00"},"friday":{"start":"09:00","end":"18:00"},"saturday":{"start":"09:00","end":"18:00"},"sunday":{"start":"09:00","end":"18:00"}}',
 NOW(), NOW())
ON DUPLICATE KEY UPDATE 
  `business_name` = VALUES(`business_name`),
  `address` = VALUES(`address`),
  `phone` = VALUES(`phone`),
  `mobile_phone` = VALUES(`mobile_phone`),
  `email` = VALUES(`email`),
  `working_hours` = VALUES(`working_hours`),
  `updated_at` = NOW();
