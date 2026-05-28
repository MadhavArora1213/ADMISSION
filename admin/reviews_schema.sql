USE admission;

CREATE TABLE IF NOT EXISTS reviews (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    college_id CHAR(36) NOT NULL,
    overall_rating DECIMAL(2,1) NOT NULL,
    academics_rating DECIMAL(2,1) NULL,
    faculty_rating DECIMAL(2,1) NULL,
    placements_rating DECIMAL(2,1) NULL,
    infrastructure_rating DECIMAL(2,1) NULL,
    hostel_rating DECIMAL(2,1) NULL,
    social_life_rating DECIMAL(2,1) NULL,
    food_rating DECIMAL(2,1) NULL,
    review_title VARCHAR(200) NULL,
    review_body TEXT NULL,
    pros TEXT NULL,
    cons TEXT NULL,
    batch_year YEAR NULL,
    course_id CHAR(36) NULL,
    helpful_votes INT DEFAULT 0,
    media_urls JSON NULL,
    
    moderation_status ENUM('pending','approved','rejected','escalated') DEFAULT 'pending',
    moderation_reason TEXT NULL,
    moderated_by CHAR(36) NULL,
    moderated_at TIMESTAMP NULL,
    is_verified_alumnus BOOLEAN DEFAULT FALSE,
    alumni_proof_url VARCHAR(255) NULL,
    ai_spam_score FLOAT DEFAULT 0,
    ai_sentiment ENUM('positive','negative','neutral','mixed') NULL,
    reported_count INT DEFAULT 0,
    fraud_flag BOOLEAN DEFAULT FALSE,
    duplicate_score FLOAT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    FOREIGN KEY (moderated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS review_meta (
    id CHAR(36) PRIMARY KEY,
    review_id CHAR(36) NOT NULL,
    ip_address VARCHAR(45) NULL,
    device_fingerprint VARCHAR(255) NULL,
    vpn_detected BOOLEAN DEFAULT FALSE,
    velocity_flag BOOLEAN DEFAULT FALSE,
    ai_model_version VARCHAR(50) NULL,
    auto_action ENUM('approve','hold','reject') NULL,
    user_agent TEXT NULL,
    geo_country VARCHAR(100) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS review_reports (
    id CHAR(36) PRIMARY KEY,
    review_id CHAR(36) NOT NULL,
    reported_by CHAR(36) NOT NULL,
    reason ENUM('spam','fake','offensive','irrelevant','harassment') NOT NULL,
    description TEXT NULL,
    status ENUM('open','resolved','dismissed') DEFAULT 'open',
    resolved_by CHAR(36) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);
