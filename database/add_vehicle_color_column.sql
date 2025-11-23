-- Add vehicle_color column to bookings table
-- This allows storing complete vehicle information including color

ALTER TABLE bookings 
ADD COLUMN vehicle_color VARCHAR(50) DEFAULT NULL AFTER vehicle_model;

-- Update comment for better documentation
ALTER TABLE bookings 
MODIFY COLUMN vehicle_type enum('sedan','suv','truck','van','motorcycle') NOT NULL COMMENT 'Vehicle type/category';

ALTER TABLE bookings 
MODIFY COLUMN vehicle_plate varchar(20) DEFAULT NULL COMMENT 'Vehicle license plate number';

ALTER TABLE bookings 
MODIFY COLUMN vehicle_model varchar(100) DEFAULT NULL COMMENT 'Vehicle model name';

ALTER TABLE bookings 
MODIFY COLUMN vehicle_color varchar(50) DEFAULT NULL COMMENT 'Vehicle color';
