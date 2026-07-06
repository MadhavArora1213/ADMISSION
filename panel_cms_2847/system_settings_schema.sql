-- 1. System Configuration Table
-- Designed as a single-row table to store global settings
CREATE TABLE system_config (
    id INT PRIMARY KEY DEFAULT 1, -- Always 1, single row
    
    -- General & SMTP
    site_name VARCHAR(255) DEFAULT 'AdmissionSeason',
    site_url VARCHAR(255) DEFAULT 'https://admissionseason.com',
    logo_url VARCHAR(255) DEFAULT NULL,
    favicon_url VARCHAR(255) DEFAULT NULL,
    maintenance_mode BOOLEAN DEFAULT FALSE,
    maintenance_message TEXT DEFAULT NULL,
    
    smtp_host VARCHAR(255) DEFAULT NULL,
    smtp_port INT DEFAULT 587,
    smtp_user VARCHAR(255) DEFAULT NULL,
    smtp_password TEXT DEFAULT NULL, -- Should be encrypted in application
    from_email VARCHAR(255) DEFAULT NULL,
    from_name VARCHAR(255) DEFAULT NULL,
    
    -- Storage, CDN & Payment Gateways
    storage_provider ENUM('s3', 'cloudinary', 'gcs', 'r2', 'local') DEFAULT 'local',
    storage_bucket VARCHAR(255) DEFAULT NULL,
    cdn_url VARCHAR(255) DEFAULT NULL,
    
    payment_gateway ENUM('razorpay', 'stripe', 'paytm', 'cashfree', 'none') DEFAULT 'none',
    gateway_key TEXT DEFAULT NULL, -- Encrypted
    gateway_secret TEXT DEFAULT NULL, -- Encrypted
    gst_rate FLOAT DEFAULT 0.18,
    currency_default CHAR(3) DEFAULT 'INR',
    
    -- AI Keys, Security & Backups
    openai_api_key TEXT DEFAULT NULL, -- Encrypted
    gemini_api_key TEXT DEFAULT NULL, -- Encrypted
    ai_provider ENUM('openai', 'gemini', 'anthropic', 'ollama') DEFAULT 'openai',
    
    mfa_enabled BOOLEAN DEFAULT TRUE,
    session_timeout_mins INT DEFAULT 60,
    max_login_attempts INT DEFAULT 5,
    ip_whitelist JSON DEFAULT NULL,
    api_rate_limit_per_min INT DEFAULT 60,
    
    backup_schedule VARCHAR(100) DEFAULT '0 0 * * *', -- Cron format
    backup_retention_days INT DEFAULT 30,
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by VARCHAR(36) DEFAULT NULL, -- FK to users table
    
    -- Constraint to ensure only one row exists
    CHECK (id = 1)
);

-- Insert the default configuration row
INSERT INTO system_config (id) VALUES (1) ON DUPLICATE KEY UPDATE id=1;

-- 2. API Keys Table
-- For programmatic access to the system
CREATE TABLE api_keys (
    id VARCHAR(36) PRIMARY KEY, -- Use UUID()
    api_key_name VARCHAR(150) NOT NULL,
    api_key_hash VARCHAR(255) NOT NULL, -- Hashed key (never store raw API keys)
    api_scope JSON DEFAULT NULL, -- Array of module names (e.g., ["colleges", "leads"])
    
    api_expires_at TIMESTAMP NULL DEFAULT NULL,
    last_used_at TIMESTAMP NULL DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    
    created_by VARCHAR(36) DEFAULT NULL, -- FK to users table
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
