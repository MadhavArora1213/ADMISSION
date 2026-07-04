-- Cookie Consents table
CREATE TABLE IF NOT EXISTS cookie_consents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(128) DEFAULT NULL,
    user_id INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    consent_action ENUM('accepted_all','rejected_all','custom','closed') NOT NULL,
    necessary TINYINT(1) DEFAULT 1,
    analytics TINYINT(1) DEFAULT 0,
    marketing TINYINT(1) DEFAULT 0,
    preferences TINYINT(1) DEFAULT 0,
    page_url VARCHAR(500) DEFAULT NULL,
    country VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    device_type VARCHAR(20) DEFAULT NULL,
    browser VARCHAR(100) DEFAULT NULL,
    os VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_consent_action (consent_action),
    INDEX idx_user_id (user_id),
    INDEX idx_ip_address (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
