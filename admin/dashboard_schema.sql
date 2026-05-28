USE admission;

CREATE TABLE IF NOT EXISTS users (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS dashboard_snapshots (
    id CHAR(36) PRIMARY KEY,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_colleges INT NOT NULL DEFAULT 0,
    total_exams INT NOT NULL DEFAULT 0,
    total_users INT NOT NULL DEFAULT 0,
    daily_leads INT DEFAULT 0,
    revenue_today DECIMAL(12,2) DEFAULT 0.00,
    pending_moderation INT DEFAULT 0,
    active_sessions INT DEFAULT 0,
    new_signups_today INT DEFAULT 0,
    ctr_today FLOAT DEFAULT 0.0,
    avg_session_duration_sec INT DEFAULT 0,
    monthly_revenue DECIMAL(12,2) DEFAULT 0.00,
    lead_revenue DECIMAL(12,2) DEFAULT 0.00,
    subscription_revenue DECIMAL(12,2) DEFAULT 0.00,
    ad_revenue DECIMAL(12,2) DEFAULT 0.00,
    commission_earned DECIMAL(12,2) DEFAULT 0.00,
    revenue_trend_json JSON NULL,
    mom_growth_pct FLOAT DEFAULT 0.0,
    yoy_growth_pct FLOAT DEFAULT 0.0,
    page_views_today INT DEFAULT 0,
    bounce_rate FLOAT DEFAULT 0.0,
    organic_traffic_pct FLOAT DEFAULT 0.0,
    core_web_vitals_lcp FLOAT DEFAULT 0.0
);

CREATE TABLE IF NOT EXISTS admin_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_type ENUM('system', 'content', 'payment', 'fraud') NOT NULL,
    alert_message TEXT NOT NULL,
    alert_severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_by CHAR(36) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_type ENUM('create', 'update', 'delete', 'login', 'flag') NOT NULL,
    actor_id CHAR(36) NULL,
    entity_type ENUM('college', 'exam', 'article', 'review', 'lead') NOT NULL,
    entity_id CHAR(36) NOT NULL,
    meta_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS admin_widgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    widget_type ENUM('chart', 'table', 'kpi', 'map') NOT NULL,
    widget_config JSON NOT NULL,
    visible_to_roles JSON NULL,
    sort_order INT DEFAULT 0
);

-- Insert Dummy Data for users
INSERT IGNORE INTO users (id, name, email, role) VALUES ('user-uuid-1', 'Super Admin', 'admin@admission.com', 'admin');

-- Insert Dummy Data for dashboard_snapshots
INSERT INTO dashboard_snapshots (id, recorded_at, total_colleges, total_exams, total_users, daily_leads, revenue_today, active_sessions, monthly_revenue)
VALUES (UUID(), NOW(), 1250, 45, 14500, 349, 230000.00, 1482, 1200000.00);

-- Insert Dummy Data for admin_alerts
INSERT INTO admin_alerts (alert_type, alert_message, alert_severity, is_read, is_resolved) VALUES 
('system', 'Search API Latency Spike: Response time exceeded 2000ms.', 'critical', FALSE, FALSE),
('content', 'Lead Delivery Failed: Failed to sync 12 leads to CRM Webhook.', 'high', TRUE, FALSE),
('fraud', 'Suspicious login attempt from multiple IPs.', 'medium', FALSE, FALSE);

-- Insert Dummy Data for activity_log
INSERT INTO activity_log (activity_type, actor_id, entity_type, entity_id, meta_json) VALUES 
('create', 'user-uuid-1', 'lead', UUID(), '{"name": "Rohan Sharma", "course": "B.Tech"}'),
('update', 'user-uuid-1', 'college', UUID(), '{"field": "status", "old": "pending", "new": "published"}');
