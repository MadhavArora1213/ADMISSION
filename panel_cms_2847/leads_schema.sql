USE admission;

CREATE TABLE IF NOT EXISTS leads (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NULL,
    lead_type ENUM('inquiry','callback','download','apply','chat_exit') NULL,
    source_page VARCHAR(255) NULL,
    utm_source VARCHAR(255) NULL,
    utm_medium VARCHAR(255) NULL,
    utm_campaign VARCHAR(255) NULL,
    utm_content VARCHAR(255) NULL,
    utm_term VARCHAR(255) NULL,
    gclid VARCHAR(255) NULL,
    
    college_id CHAR(36) NULL,
    course_id CHAR(36) NULL,
    
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    class_12_score FLOAT NULL,
    target_year YEAR NULL,
    preferred_budget DECIMAL(10,2) NULL,
    
    -- CRM & Assignment
    lead_status ENUM('new','contacted','qualified','converted','lost','invalid') DEFAULT 'new',
    assigned_to CHAR(36) NULL,
    priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
    last_contacted_at TIMESTAMP NULL,
    next_followup_at TIMESTAMP NULL,
    call_attempts TINYINT DEFAULT 0,
    counsellor_notes TEXT NULL,
    disposition ENUM('not_reachable','interested','not_interested','wrong_number') NULL,
    sla_breach_at TIMESTAMP NULL,
    
    -- Delivery & Disputes
    delivered_to_college_at TIMESTAMP NULL,
    delivery_status ENUM('pending','delivered','failed','disputed') DEFAULT 'pending',
    dispute_reason TEXT NULL,
    dispute_raised_at TIMESTAMP NULL,
    dispute_resolved_at TIMESTAMP NULL,
    dispute_outcome ENUM('credited','rejected') NULL,
    is_blacklisted BOOLEAN DEFAULT FALSE,
    blacklist_reason TEXT NULL,
    
    -- Attribution
    attribution_model ENUM('first_touch','last_touch','linear','position_based') NULL,
    first_touch_source VARCHAR(255) NULL,
    last_touch_source VARCHAR(255) NULL,
    touchpoints_json JSON NULL,
    revenue_attributed DECIMAL(10,2) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE SET NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS lead_call_logs (
    id CHAR(36) PRIMARY KEY,
    lead_id CHAR(36) NOT NULL,
    call_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    duration_seconds INT NULL,
    outcome ENUM('answered','no_answer','voicemail','busy') NULL,
    recording_url VARCHAR(255) NULL,
    notes TEXT NULL,
    called_by CHAR(36) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (called_by) REFERENCES users(id) ON DELETE SET NULL
);
