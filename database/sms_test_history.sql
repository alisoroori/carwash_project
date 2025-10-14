CREATE TABLE sms_test_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT NOT NULL,
    phone VARCHAR(20) NOT NULL,
    variables JSON NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL,
    response TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES sms_templates(id),
    INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;