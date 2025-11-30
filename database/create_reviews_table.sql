-- Create reviews table for customer feedback system
-- This table stores ratings and reviews for completed reservations

CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) UNSIGNED NOT NULL,
    `reservation_id` INT(11) UNSIGNED NOT NULL,
    `carwash_id` INT(11) UNSIGNED NOT NULL,
    `rating` TINYINT(1) UNSIGNED NOT NULL COMMENT 'Rating from 1 to 5 stars',
    `comment` TEXT NULL COMMENT 'Optional review text',
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'approved' COMMENT 'Moderation status',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reviews_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reviews_carwash` FOREIGN KEY (`carwash_id`) REFERENCES `carwashes` (`id`) ON DELETE CASCADE,
    
    -- Indexes for performance
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_reservation_id` (`reservation_id`),
    INDEX `idx_carwash_id` (`carwash_id`),
    INDEX `idx_rating` (`rating`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`),
    
    -- Unique constraint to prevent duplicate reviews for same reservation
    UNIQUE KEY `unique_review_per_reservation` (`user_id`, `reservation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Customer reviews and ratings for completed reservations';

-- Add review_status column to bookings table if it doesn't exist
-- This tracks whether a reservation has been reviewed
ALTER TABLE `bookings` 
ADD COLUMN IF NOT EXISTS `review_status` ENUM('pending', 'reviewed') DEFAULT 'pending' COMMENT 'Whether customer has left a review' AFTER `status`;

-- Create index on review_status for faster queries
ALTER TABLE `bookings` 
ADD INDEX IF NOT EXISTS `idx_review_status` (`review_status`);
