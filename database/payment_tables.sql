ALTER TABLE orders 
ADD COLUMN payment_id VARCHAR(100) NULL AFTER status,
ADD COLUMN payment_status VARCHAR(50) NULL AFTER payment_id,
ADD INDEX (payment_id);

CREATE TABLE payment_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_id VARCHAR(100),
    status VARCHAR(20) NOT NULL,
    response_data JSON,
    created_at DATETIME NOT NULL,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX (payment_id),
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

<?php
define('IYZICO_API_KEY', 'your-api-key');
define('IYZICO_SECRET_KEY', 'your-secret-key');