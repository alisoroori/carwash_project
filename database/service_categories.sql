CREATE TABLE service_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    carwash_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (carwash_id) REFERENCES carwash(id),
    INDEX (carwash_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add category_id to services table if not exists
ALTER TABLE services ADD COLUMN category_id INT,
ADD FOREIGN KEY (category_id) REFERENCES service_categories(id);