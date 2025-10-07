CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    carwash_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL, -- in minutes
    category ENUM('exterior', 'interior', 'full', 'special') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (carwash_id) REFERENCES carwash(id),
    INDEX (carwash_id),
    INDEX (category),
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;