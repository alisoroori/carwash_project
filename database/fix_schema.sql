-- Updated CarWash Database Schema to support full registration form
-- This fixes the column mismatch issues

-- Use the database
USE carwash_db;

-- Add missing columns to users table if they don't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS username VARCHAR(50) UNIQUE,
ADD COLUMN IF NOT EXISTS full_name VARCHAR(100);

-- Update users table password column name if needed
ALTER TABLE users 
CHANGE COLUMN password_hash password VARCHAR(255);

-- Drop and recreate carwash table with all needed columns
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS carwash;

CREATE TABLE carwash (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    name VARCHAR(100) NOT NULL, -- This maps to business_name in the form
    email VARCHAR(100),
    phone VARCHAR(20),
    tax_number VARCHAR(50),
    license_number VARCHAR(50),
    owner_name VARCHAR(100),
    owner_id VARCHAR(11), -- TC Kimlik
    owner_phone VARCHAR(20),
    birth_date DATE,
    city VARCHAR(50),
    district VARCHAR(100),
    address TEXT,
    exterior_price DECIMAL(10,2) DEFAULT 0,
    interior_price DECIMAL(10,2) DEFAULT 0,
    detailing_price DECIMAL(10,2) DEFAULT 0,
    opening_time TIME,
    closing_time TIME,
    capacity INT,
    description TEXT,
    profile_image VARCHAR(255),
    logo_image VARCHAR(255),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    status ENUM('pending', 'active', 'inactive', 'suspended') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Recreate services table
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    carwash_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    duration INT COMMENT 'Duration in minutes',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (carwash_id) REFERENCES carwash(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Recreate bookings table
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    carwash_id INT,
    service_id INT,
    booking_date DATE,
    booking_time TIME,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    total_price DECIMAL(10,2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (carwash_id) REFERENCES carwash(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add indexes for better performance
CREATE INDEX idx_carwash_user ON carwash(user_id);
CREATE INDEX idx_carwash_city ON carwash(city);
CREATE INDEX idx_carwash_status ON carwash(status);
CREATE INDEX idx_services_carwash ON services(carwash_id);
CREATE INDEX idx_bookings_user ON bookings(user_id);
CREATE INDEX idx_bookings_carwash ON bookings(carwash_id);
CREATE INDEX idx_bookings_date ON bookings(booking_date);

-- Show the updated structure
DESCRIBE users;
DESCRIBE carwash;