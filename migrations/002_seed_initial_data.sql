-- Migration: 002_seed_initial_data.sql
-- Description: Seeds initial data for the CarWash application
-- Date: 2025-10-22

-- Insert admin user (password: Admin@123456)
INSERT INTO `users` (`full_name`, `email`, `password`, `role`, `status`, `email_verified_at`) 
VALUES ('System Administrator', 'admin@carwash.com', 
        '$2y$10$mC7MQegXVIN5pX8j8vx3aeb0rJEtODMJhdgQDq.MTYqu1I0Kii0xC', 
        'admin', 'active', NOW())
ON DUPLICATE KEY UPDATE
    `status` = 'active',
    `email_verified_at` = NOW();

-- Insert service categories
INSERT INTO `service_categories` (`name`, `description`, `icon`, `display_order`) VALUES
('Exterior Wash', 'Exterior cleaning services for your vehicle', 'fa-car-wash', 1),
('Interior Cleaning', 'Interior cleaning and detailing services', 'fa-car-side', 2),
('Full Detailing', 'Comprehensive cleaning both inside and out', 'fa-car', 3),
('Express Services', 'Quick wash options for when you\'re in a hurry', 'fa-clock', 4),
('Premium Services', 'Luxury cleaning options with premium products', 'fa-star', 5),
('Specialty Services', 'Special treatments for specific vehicle needs', 'fa-tools', 6)
ON DUPLICATE KEY UPDATE
    `description` = VALUES(`description`),
    `icon` = VALUES(`icon`),
    `display_order` = VALUES(`display_order`);