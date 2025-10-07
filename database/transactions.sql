CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    payment_id VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    response_data JSON,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    INDEX (payment_id),
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;