-- 1. Calculator Configuration (Single-row table for global settings)
CREATE TABLE calculator_config (
    id INT PRIMARY KEY DEFAULT 1,
    
    loan_providers JSON DEFAULT NULL, -- [{name, logo, interest_rate_range, max_tenure}]
    default_interest_rate_pct FLOAT DEFAULT 10.5,
    
    max_tenure_months INT DEFAULT 84,
    min_loan_amount DECIMAL(10,2) DEFAULT 0.00,
    max_loan_amount DECIMAL(10,2) DEFAULT 5000000.00, -- 50 Lakhs
    
    processing_fee_pct FLOAT DEFAULT 1.0,
    tax_rate FLOAT DEFAULT 0.18, -- 18% GST usually
    
    is_active BOOLEAN DEFAULT TRUE,
    affiliate_links JSON DEFAULT NULL, -- [{provider, url, cta_label}]
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CHECK (id = 1)
);

-- Insert the default configuration row
INSERT INTO calculator_config (id) VALUES (1) ON DUPLICATE KEY UPDATE id=1;


-- 2. Calculator Sessions (Tracking user inputs and leads)
CREATE TABLE calculator_sessions (
    id VARCHAR(36) PRIMARY KEY, -- Use UUID()
    session_token VARCHAR(255) DEFAULT NULL, -- For tracking anonymous guest sessions
    
    user_id VARCHAR(36) DEFAULT NULL, -- FK to users (nullable)
    college_id VARCHAR(36) DEFAULT NULL, -- FK to colleges (context of where they used it)
    
    fee_amount DECIMAL(10,2) NOT NULL,
    down_payment DECIMAL(10,2) DEFAULT 0.00,
    loan_amount DECIMAL(10,2) NOT NULL,
    
    tenure_months INT NOT NULL,
    interest_rate FLOAT NOT NULL,
    
    emi_results JSON NOT NULL, -- {monthly_emi, total_interest, total_payment}
    provider_compared JSON DEFAULT NULL, -- Array of provider names
    
    lead_captured_at TIMESTAMP NULL DEFAULT NULL, -- Timestamp if they clicked 'Apply for Loan'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE SET NULL
);
