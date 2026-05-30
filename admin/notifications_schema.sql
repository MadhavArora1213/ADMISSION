USE admission;

CREATE TABLE IF NOT EXISTS notification_templates (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    template_name VARCHAR(255) NOT NULL,
    channel ENUM('email','sms','push','whatsapp','in_app') NOT NULL,
    subject VARCHAR(255) NULL,
    body_html LONGTEXT NULL,
    body_text TEXT NULL,
    variables_json JSON NULL,
    language ENUM('en','hi') DEFAULT 'en',
    is_active BOOLEAN DEFAULT TRUE,
    category ENUM('transactional','marketing','alert') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS audience_segments (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    segment_name VARCHAR(255) NOT NULL,
    filters_json JSON NOT NULL,
    user_count INT DEFAULT 0,
    refresh_schedule VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notification_campaigns (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    campaign_name VARCHAR(255) NOT NULL,
    template_id CHAR(36) NOT NULL,
    audience_segment_id CHAR(36) NULL,
    scheduled_at TIMESTAMP NULL,
    sent_count INT DEFAULT 0,
    delivered_count INT DEFAULT 0,
    opened_count INT DEFAULT 0,
    clicked_count INT DEFAULT 0,
    unsubscribed_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    status ENUM('draft','scheduled','sending','sent','cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES notification_templates(id),
    FOREIGN KEY (audience_segment_id) REFERENCES audience_segments(id)
);

CREATE TABLE IF NOT EXISTS notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    campaign_id CHAR(36) NULL,
    channel ENUM('email','sms','push','whatsapp','in_app') NOT NULL,
    status ENUM('sent','delivered','failed','bounced','opened') NOT NULL,
    error_message TEXT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES notification_campaigns(id)
);
