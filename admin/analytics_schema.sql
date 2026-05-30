CREATE TABLE IF NOT EXISTS page_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_url VARCHAR(255) NOT NULL,
    page_views INT DEFAULT 0,
    unique_visitors INT DEFAULT 0,
    bounce_rate FLOAT DEFAULT 0.0,
    avg_time_seconds INT DEFAULT 0,
    traffic_source ENUM('organic', 'direct', 'referral', 'social', 'email', 'paid'),
    device_type ENUM('desktop', 'mobile', 'tablet'),
    country CHAR(2),
    date DATE NOT NULL,
    
    -- Recommended additions
    utm_campaign VARCHAR(100),
    utm_medium VARCHAR(100),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS funnel_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    funnel_step ENUM('visit', 'search', 'college_view', 'shortlist', 'lead', 'apply', 'convert') NOT NULL,
    users_entered INT DEFAULT 0,
    users_completed INT DEFAULT 0,
    drop_off_rate FLOAT GENERATED ALWAYS AS (IF(users_entered > 0, ((users_entered - users_completed) / users_entered) * 100, 0)) STORED,
    date DATE NOT NULL,
    
    -- Recommended additions
    segment VARCHAR(100) DEFAULT 'All',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ab_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_name VARCHAR(255) NOT NULL,
    variant_a JSON,
    variant_b JSON,
    metric ENUM('ctr', 'conversion', 'lead_rate', 'time_on_page') NOT NULL,
    winner ENUM('a', 'b', 'inconclusive'),
    confidence_pct FLOAT,
    status ENUM('running', 'completed', 'paused') DEFAULT 'running',
    
    -- Recommended additions
    start_date DATE,
    end_date DATE,
    variant_a_views INT DEFAULT 0,
    variant_a_conv INT DEFAULT 0,
    variant_b_views INT DEFAULT 0,
    variant_b_conv INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS retention_cohorts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cohort_date DATE NOT NULL,
    users_in_cohort INT DEFAULT 0,
    day_1_retention FLOAT DEFAULT 0.0,
    day_7_retention FLOAT DEFAULT 0.0,
    day_30_retention FLOAT DEFAULT 0.0,
    
    -- Recommended additions
    segment VARCHAR(100) DEFAULT 'All Users',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS analytics_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(255) NOT NULL,
    report_format ENUM('pdf', 'csv', 'xlsx') NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Recommended additions
    report_url VARCHAR(255),
    admin_id INT, -- Assuming admin users exist and have an INT id
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
