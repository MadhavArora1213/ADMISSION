-- 1. Moderation Queue Table
CREATE TABLE moderation_queue (
    id VARCHAR(36) PRIMARY KEY, -- Use UUID() in your INSERT queries
    entity_type ENUM('review', 'qa', 'article', 'college_data', 'comment') NOT NULL,
    entity_id VARCHAR(36) NOT NULL,
    
    -- Status & Priority
    status ENUM('pending', 'in_progress', 'resolved', 'dismissed') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    
    -- Detection / Flagging Info
    flagged_reason ENUM('spam', 'offensive', 'misleading', 'duplicate', 'low_quality') NOT NULL,
    ai_score FLOAT CHECK (ai_score >= 0 AND ai_score <= 1),
    reporter_id VARCHAR(36) DEFAULT NULL, -- Null if flagged by AI
    
    -- Action taken
    moderator_id VARCHAR(36) DEFAULT NULL,
    action_taken ENUM('approve', 'reject', 'flag', 'escalate', 'warn_user') DEFAULT NULL,
    action_note TEXT,
    actioned_at TIMESTAMP NULL DEFAULT NULL,
    escalated_to VARCHAR(36) DEFAULT NULL,
    
    -- SLAs & Timestamps
    sla_due_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys (Assuming 'users' table has a VARCHAR(36) id)
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (moderator_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (escalated_to) REFERENCES users(id) ON DELETE SET NULL
);

-- 2. Spam Analysis Logs (To store the detection signals)
CREATE TABLE spam_detection_logs (
    id VARCHAR(36) PRIMARY KEY, -- Use UUID() in your INSERT queries
    user_id VARCHAR(36) DEFAULT NULL, -- Null if guest
    ip_address VARCHAR(45) NOT NULL,
    device_fingerprint VARCHAR(255),
    
    -- Detection Metrics
    duplicate_content_score FLOAT DEFAULT 0.0,
    velocity_flag BOOLEAN DEFAULT FALSE,
    vpn_detected BOOLEAN DEFAULT FALSE,
    proxy_detected BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 3. Blacklisted Entities (The actual ban list)
CREATE TABLE blacklisted_entities (
    id VARCHAR(36) PRIMARY KEY, -- Use UUID() in your INSERT queries
    entity_type ENUM('ip', 'user', 'email', 'device', 'phone') NOT NULL,
    entity_value VARCHAR(255) NOT NULL,
    
    -- Ban Info
    reason TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL DEFAULT NULL, -- Null means permanent ban
    
    -- Audit
    added_by VARCHAR(36) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE(entity_type, entity_value) -- Prevent duplicate active bans for the same entity
);
