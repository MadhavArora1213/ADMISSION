USE admission;

CREATE TABLE IF NOT EXISTS users (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    role ENUM('student', 'admin', 'moderator') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS dashboard_snapshots (
    id CHAR(36) PRIMARY KEY,
    snapshot_date DATE,
    metric_key VARCHAR(100),
    metric_value DECIMAL(15,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin_alerts (
    id CHAR(36) PRIMARY KEY,
    alert_type VARCHAR(50),
    message TEXT,
    severity ENUM('low', 'medium', 'high', 'critical'),
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_by CHAR(36) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS activity_log (
    id CHAR(36) PRIMARY KEY,
    actor_id CHAR(36) NULL,
    action VARCHAR(100),
    entity_type VARCHAR(50),
    entity_id CHAR(36) NULL,
    meta JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS role_widgets (
    id CHAR(36) PRIMARY KEY,
    widget_type VARCHAR(50),
    widget_config JSON NULL,
    visible_to_roles JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert dummy data
INSERT IGNORE INTO users (id, name, email, role) VALUES 
('u1-uuid', 'Admin User', 'admin@example.com', 'admin'),
('u2-uuid', 'Rahul Sharma', 'rahul@example.com', 'student');

-- Insert dummy data for dashboard_snapshots
INSERT IGNORE INTO dashboard_snapshots (id, snapshot_date, metric_key, metric_value) VALUES
(UUID(), CURDATE(), 'total_colleges', 1245.00),
(UUID(), CURDATE(), 'total_exams', 128.00),
(UUID(), CURDATE(), 'total_users', 8432.00),
(UUID(), CURDATE(), 'daily_leads', 345.00),
(UUID(), CURDATE(), 'revenue_today', 12500.50),
(UUID(), CURDATE(), 'pending_moderation', 42.00),
(UUID(), CURDATE(), 'active_sessions', 1024.00),
(UUID(), CURDATE(), 'new_signups_today', 156.00),
(UUID(), CURDATE(), 'monthly_revenue', 345000.00),
(UUID(), CURDATE(), 'lead_revenue', 150000.00),
(UUID(), CURDATE(), 'subscription_revenue', 120000.00),
(UUID(), CURDATE(), 'ad_revenue', 45000.00),
(UUID(), CURDATE(), 'commission_earned', 30000.00),
(UUID(), CURDATE(), 'page_views_today', 45200.00),
(UUID(), CURDATE(), 'bounce_rate', 42.50);

-- Insert dummy data for admin_alerts
INSERT IGNORE INTO admin_alerts (id, alert_type, message, severity, is_resolved) VALUES
(UUID(), 'System', 'High server CPU usage detected on Node 3', 'high', FALSE),
(UUID(), 'Security', 'Failed login attempts from IP 192.168.1.5', 'critical', FALSE),
(UUID(), 'Content', '3 new college profiles require approval', 'medium', FALSE),
(UUID(), 'Traffic', 'Unusual traffic spike in MBA section', 'low', FALSE);

-- Insert dummy data for activity_log
INSERT IGNORE INTO activity_log (id, actor_id, action, entity_type, meta) VALUES
(UUID(), 'u1-uuid', 'Updated College Details', 'college', '{"college_name": "IIT Bombay"}'),
(UUID(), 'u1-uuid', 'Approved Review', 'review', '{"review_id": 4512}'),
(UUID(), 'u1-uuid', 'Changed System Setting', 'setting', '{"setting_key": "maintenance_mode", "value": "false"}'),
(UUID(), 'u1-uuid', 'Generated Revenue Report', 'report', '{"month": "May"}');

-- Insert dummy data for role_widgets
INSERT IGNORE INTO role_widgets (id, widget_type, widget_config, visible_to_roles) VALUES
(UUID(), 'revenue_chart', '{"chartType":"line", "dataSource":"revenue"}', '["admin"]'),
(UUID(), 'pending_moderation', '{"displayLimit":10}', '["admin", "moderator"]');

CREATE TABLE IF NOT EXISTS states (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    slug VARCHAR(100) UNIQUE
);

CREATE TABLE IF NOT EXISTS cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    state_id INT,
    is_popular BOOLEAN DEFAULT FALSE,
    slug VARCHAR(100) UNIQUE,
    FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS universities (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(255),
    slug VARCHAR(255) UNIQUE
);

CREATE TABLE IF NOT EXISTS colleges (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(300),
    slug VARCHAR(300) UNIQUE,
    college_type ENUM('govt','private','deemed','autonomous'),
    ownership ENUM('public','private','trust','society'),
    status ENUM('active','pending','archived') DEFAULT 'pending',
    
    logo_url VARCHAR(255),
    cover_image_url VARCHAR(255),
    established_year INT,
    autonomous BOOLEAN DEFAULT FALSE,
    ugc_approved BOOLEAN DEFAULT FALSE,
    aicte_approved BOOLEAN DEFAULT FALSE,
    total_students INT,
    total_faculty INT,
    campus_area_acres FLOAT,
    
    city_id INT,
    state_id INT,
    university_id CHAR(36),
    naac_grade ENUM('A++','A+','A','B++','B+','B','C', 'None') DEFAULT 'None',
    nirf_rank INT,
    
    is_verified BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    featured_order INT DEFAULT 0,
    data_quality_score TINYINT DEFAULT 0,
    
    verification_status ENUM('pending','verified','rejected') DEFAULT 'pending',
    verified_by CHAR(36) NULL,
    verified_at TIMESTAMP NULL,
    rejection_reason TEXT,
    duplicate_of CHAR(36) NULL,
    import_batch_id CHAR(36) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    archived_at TIMESTAMP NULL,
    
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL,
    FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE SET NULL,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (duplicate_of) REFERENCES colleges(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS college_contacts (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36),
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    latitude DECIMAL(9,6),
    longitude DECIMAL(9,6),
    website_url VARCHAR(255),
    pincode VARCHAR(10),
    google_maps_url VARCHAR(255),
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);
