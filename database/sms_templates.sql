CREATE TABLE sms_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    content TEXT NOT NULL,
    variables JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    INDEX (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default templates
INSERT INTO sms_templates (name, code, content, variables) VALUES
(
    'Rezervasyon Onayı',
    'BOOKING_CONFIRMATION',
    'CarWash Rezervasyon Onayı\nNo: #{booking_id}\nTarih: {date}\nSaat: {time}\nHizmet: {service}',
    '["booking_id", "date", "time", "service"]'
),
(
    'İşletme Bildirimi',
    'CARWASH_NOTIFICATION',
    'Yeni Rezervasyon\nNo: #{booking_id}\nMüşteri: {customer}\nTarih: {date} Saat: {time}',
    '["booking_id", "customer", "date", "time"]'
);