CREATE TABLE IF NOT EXISTS subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(255) NOT NULL,
    plan_type ENUM('basic', 'standard', 'premium', 'enterprise') NOT NULL,
    
    -- Recommended fields
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    features JSON,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    college_id INT NOT NULL, -- Assuming INT for colleges.id. Change to VARCHAR(36) if using UUIDs
    plan_id INT NOT NULL,    -- Recommended FK
    amount DECIMAL(10,2) NOT NULL,
    billing_cycle ENUM('monthly', 'quarterly', 'annual') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    auto_renew BOOLEAN DEFAULT TRUE,
    
    -- Recommended fields
    status ENUM('active', 'cancelled', 'expired', 'pending') DEFAULT 'pending',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    -- FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
);

CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(100) UNIQUE NOT NULL,
    college_id INT NOT NULL,      -- Recommended FK (who is billed)
    subscription_id INT,          -- Recommended FK (optional, link to sub)
    gst_number VARCHAR(15),
    gst_amount DECIMAL(10,2) DEFAULT 0.00,
    payment_method ENUM('bank_transfer', 'card', 'upi'),
    payment_status ENUM('pending', 'paid', 'overdue') DEFAULT 'pending',
    
    -- Recommended fields
    subtotal_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    invoice_date DATE NOT NULL,
    due_date DATE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS lead_credits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    college_id INT NOT NULL,      -- Recommended FK (who owns the credits)
    leads_purchased INT NOT NULL DEFAULT 0,
    leads_delivered INT NOT NULL DEFAULT 0,
    lead_cost DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    credits_remaining INT GENERATED ALWAYS AS (leads_purchased - leads_delivered) STORED, -- Computed
    expiry_date DATE,
    
    -- Recommended fields
    status ENUM('active', 'expired', 'depleted') DEFAULT 'active',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ad_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    college_id INT NOT NULL,      -- Recommended FK (who bought the ad)
    ad_type ENUM('banner', 'sponsored_listing', 'featured_badge', 'email_blast') NOT NULL,
    ad_placement VARCHAR(255),
    ad_start DATE,
    ad_end DATE,
    impressions INT DEFAULT 0,
    clicks INT DEFAULT 0,
    ctr FLOAT GENERATED ALWAYS AS (IF(impressions > 0, (clicks / impressions) * 100, 0)) STORED, -- Computed
    
    -- Recommended fields
    media_url VARCHAR(255),
    target_url VARCHAR(255),
    cost_usd DECIMAL(10,2),
    status ENUM('active', 'paused', 'completed') DEFAULT 'active',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS commissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,  -- Change to VARCHAR(36) if using UUIDs for applications
    college_id INT NOT NULL,      -- Recommended FK (billed to)
    consultant_id INT,            -- Recommended FK (optional, payee)
    invoice_id INT,               -- Recommended FK (optional link to generated invoice)
    commission_pct FLOAT NOT NULL,
    commission_earned DECIMAL(10,2) NOT NULL,
    commission_status ENUM('pending', 'paid', 'disputed') DEFAULT 'pending',
    payout_date DATE,
    payout_method ENUM('bank_transfer', 'credit'),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
