-- 1. Shortlists (The actual student wishlist items)
CREATE TABLE shortlists (
    id VARCHAR(36) PRIMARY KEY, -- Use UUID()
    user_id VARCHAR(36) NOT NULL, -- FK to users
    college_id VARCHAR(36) NOT NULL, -- FK to colleges
    course_id VARCHAR(36) DEFAULT NULL, -- FK to courses (optional)
    
    notes TEXT DEFAULT NULL, -- Private notes for the student
    notification_pref BOOLEAN DEFAULT TRUE, -- Alert on cutoff/fee changes
    
    priority ENUM('dream', 'target', 'safe') DEFAULT 'target',
    status ENUM('active', 'applied', 'removed') DEFAULT 'active',
    
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE,
    -- Prevent duplicate shortlists for the exact same college+course combination
    UNIQUE(user_id, college_id, course_id) 
);

-- 2. Shortlist Analytics (Daily aggregation for fast admin dashboards)
CREATE TABLE shortlist_analytics (
    id VARCHAR(36) PRIMARY KEY,
    date DATE NOT NULL UNIQUE, -- One analytics row per day
    
    shortlist_count INT DEFAULT 0, -- Total new shortlists today
    avg_shortlists_per_user FLOAT DEFAULT 0.0,
    shortlist_to_apply_rate FLOAT DEFAULT 0.0, -- Conversion rate metric
    
    most_shortlisted_colleges JSON DEFAULT NULL, -- [{college_id, count}]
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
