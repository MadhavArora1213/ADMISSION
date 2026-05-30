-- 1. Main Partner Accounts Table (The Billing/Contract Entity)
CREATE TABLE partners (
    id VARCHAR(36) PRIMARY KEY, -- Use UUID()
    partner_college_id VARCHAR(36) NOT NULL, -- FK to colleges
    
    contact_person VARCHAR(150) NOT NULL,
    designation VARCHAR(100),
    
    -- Contract & Billing
    plan_id VARCHAR(36) DEFAULT NULL, -- FK to subscription_plans
    leads_quota INT DEFAULT 0,
    leads_used INT DEFAULT 0,
    contract_start DATE DEFAULT NULL,
    contract_end DATE DEFAULT NULL,
    
    -- Management
    account_manager_id VARCHAR(36) DEFAULT NULL, -- FK to users (Your EduSearch Admin)
    onboarding_status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    status ENUM('active', 'suspended', 'trial', 'churned') DEFAULT 'trial',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (partner_college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 2. Partner Users (The people who actually log into the portal)
CREATE TABLE partner_users (
    id VARCHAR(36) PRIMARY KEY,
    partner_id VARCHAR(36) NOT NULL, -- FK to partners
    
    full_name VARCHAR(150) NOT NULL,
    login_email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    
    access_level ENUM('read', 'write', 'admin') DEFAULT 'read',
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
);

-- 3. Partner Content Management (Approval Queue)
CREATE TABLE partner_content_requests (
    id VARCHAR(36) PRIMARY KEY,
    college_id VARCHAR(36) NOT NULL, -- FK to colleges
    requested_by VARCHAR(36) NOT NULL, -- FK to partner_users
    
    content_type ENUM('info', 'photo', 'placement', 'course', 'ranking') NOT NULL,
    submitted_data JSON NOT NULL, -- The new data they want to publish
    
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by VARCHAR(36) DEFAULT NULL, -- FK to users (Your EduSearch Admin)
    review_notes TEXT DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES partner_users(id) ON DELETE CASCADE
);
