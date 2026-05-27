USE admission;

-- Drop tables if they exist to avoid conflicts with MVP versions
DROP TABLE IF EXISTS admin_alerts;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS dashboard_snapshots;

-- 1. DASHBOARD LAYOUT SYSTEM
CREATE TABLE IF NOT EXISTS dashboard_layouts (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NULL,
    role_id CHAR(36) NULL,
    layout_name VARCHAR(255),
    is_default BOOLEAN DEFAULT FALSE,
    layout_json JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. WIDGET ENGINE
CREATE TABLE IF NOT EXISTS dashboard_widgets (
    id CHAR(36) PRIMARY KEY,
    widget_key VARCHAR(255) UNIQUE,
    widget_name VARCHAR(255),
    widget_type ENUM('metric','chart','table','feed','alert','ai_summary','system_health','leaderboard'),
    data_source VARCHAR(255),
    config_json JSON,
    default_size JSON,
    is_realtime BOOLEAN DEFAULT FALSE,
    cache_duration INT DEFAULT 300,
    status ENUM('active','inactive','draft') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. USER WIDGET SETTINGS
CREATE TABLE IF NOT EXISTS user_dashboard_widgets (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    widget_id CHAR(36) NOT NULL,
    position_json JSON,
    settings_json JSON,
    is_hidden BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (widget_id) REFERENCES dashboard_widgets(id) ON DELETE CASCADE
);

-- 4. REALTIME METRICS ENGINE
CREATE TABLE IF NOT EXISTS realtime_metrics (
    id CHAR(36) PRIMARY KEY,
    metric_key VARCHAR(255),
    metric_value DECIMAL(15,2),
    source VARCHAR(255),
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. ADVANCED ALERT SYSTEM
CREATE TABLE IF NOT EXISTS admin_alerts (
    id CHAR(36) PRIMARY KEY,
    alert_type VARCHAR(255),
    title VARCHAR(255),
    message TEXT,
    severity ENUM('low','medium','high','critical'),
    source_module VARCHAR(255),
    entity_type VARCHAR(255) NULL,
    entity_id CHAR(36) NULL,
    status ENUM('open','acknowledged','resolved','ignored') DEFAULT 'open',
    assigned_to CHAR(36) NULL,
    resolution_notes TEXT,
    metadata_json JSON,
    resolved_by CHAR(36) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL
);

-- 6. ALERT RULE ENGINE
CREATE TABLE IF NOT EXISTS alert_rules (
    id CHAR(36) PRIMARY KEY,
    rule_name VARCHAR(255),
    module_name VARCHAR(255),
    condition_json JSON,
    severity ENUM('low','medium','high','critical'),
    notification_channels JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. ACTIVITY LOGS (UPDATED)
CREATE TABLE IF NOT EXISTS activity_logs (
    id CHAR(36) PRIMARY KEY,
    actor_id CHAR(36) NULL,
    action_type VARCHAR(255),
    entity_type VARCHAR(255),
    entity_id CHAR(36) NULL,
    module_name VARCHAR(255),
    description TEXT,
    before_json JSON NULL,
    after_json JSON NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    metadata_json JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 8. KPI DEFINITIONS SYSTEM
CREATE TABLE IF NOT EXISTS kpi_definitions (
    id CHAR(36) PRIMARY KEY,
    metric_key VARCHAR(255) UNIQUE,
    metric_name VARCHAR(255),
    metric_type ENUM('count','sum','percentage','average'),
    query_config JSON,
    chart_type VARCHAR(255),
    cache_duration INT DEFAULT 300,
    is_realtime BOOLEAN DEFAULT FALSE,
    status ENUM('active','inactive','draft') DEFAULT 'active'
);

-- 9. KPI SNAPSHOTS (UPDATED)
CREATE TABLE IF NOT EXISTS dashboard_snapshots (
    id CHAR(36) PRIMARY KEY,
    metric_key VARCHAR(255),
    metric_value DECIMAL(15,2),
    dimension_json JSON,
    snapshot_type ENUM('hourly','daily','weekly','monthly'),
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 10. DASHBOARD FILTER ENGINE
CREATE TABLE IF NOT EXISTS dashboard_filters (
    id CHAR(36) PRIMARY KEY,
    filter_key VARCHAR(255),
    filter_type VARCHAR(255),
    options_json JSON,
    default_value JSON,
    is_global BOOLEAN DEFAULT FALSE
);

-- 11. SAVED REPORTS SYSTEM
CREATE TABLE IF NOT EXISTS saved_reports (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    report_name VARCHAR(255),
    filters_json JSON,
    widgets_json JSON,
    schedule_json JSON,
    export_format ENUM('pdf','csv','xlsx'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 12. CHART CONFIG ENGINE
CREATE TABLE IF NOT EXISTS chart_configurations (
    id CHAR(36) PRIMARY KEY,
    chart_name VARCHAR(255),
    chart_type ENUM('line','bar','area','pie','donut','funnel','heatmap'),
    query_json JSON,
    visualization_json JSON,
    status ENUM('active','inactive') DEFAULT 'active'
);

-- 13. SYSTEM HEALTH MODULE
CREATE TABLE IF NOT EXISTS system_health (
    id CHAR(36) PRIMARY KEY,
    service_name VARCHAR(255),
    status ENUM('healthy','warning','critical','offline'),
    cpu_usage FLOAT,
    memory_usage FLOAT,
    response_time_ms FLOAT,
    last_checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 14. REALTIME ACTIVITY FEED
CREATE TABLE IF NOT EXISTS realtime_activity_feed (
    id CHAR(36) PRIMARY KEY,
    activity_type VARCHAR(255),
    title VARCHAR(255),
    description TEXT,
    entity_type VARCHAR(255),
    entity_id CHAR(36),
    priority ENUM('low','medium','high'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 15. ROLE-BASED DASHBOARDS
CREATE TABLE IF NOT EXISTS role_dashboard_configs (
    id CHAR(36) PRIMARY KEY,
    role_name VARCHAR(255),
    default_layout_json JSON,
    default_widgets_json JSON,
    permissions_json JSON
);

-- 16. AI INSIGHTS MODULE
CREATE TABLE IF NOT EXISTS ai_dashboard_insights (
    id CHAR(36) PRIMARY KEY,
    insight_type VARCHAR(255),
    title VARCHAR(255),
    description TEXT,
    related_entity_type VARCHAR(255) NULL,
    related_entity_id CHAR(36) NULL,
    confidence_score FLOAT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 17. DASHBOARD SEARCH SYSTEM
CREATE TABLE IF NOT EXISTS dashboard_search_logs (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NULL,
    search_query VARCHAR(255),
    results_count INT,
    clicked_result JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 18. EXPORT SYSTEM
CREATE TABLE IF NOT EXISTS exports (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    export_type VARCHAR(255),
    filters_json JSON,
    file_url VARCHAR(255),
    status ENUM('pending','processing','completed','failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 19. DYNAMIC MODULE BUILDER
CREATE TABLE IF NOT EXISTS dynamic_modules (
    id CHAR(36) PRIMARY KEY,
    module_key VARCHAR(255) UNIQUE,
    module_name VARCHAR(255),
    entity_type VARCHAR(255),
    config_json JSON,
    status ENUM('active','inactive') DEFAULT 'active'
);

CREATE TABLE IF NOT EXISTS dynamic_fields (
    id CHAR(36) PRIMARY KEY,
    module_id CHAR(36) NOT NULL,
    field_key VARCHAR(255),
    field_label VARCHAR(255),
    field_type VARCHAR(255),
    validation_json JSON,
    settings_json JSON,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (module_id) REFERENCES dynamic_modules(id) ON DELETE CASCADE
);

-- 20. MEDIA & ATTACHMENTS SUPPORT
CREATE TABLE IF NOT EXISTS dashboard_attachments (
    id CHAR(36) PRIMARY KEY,
    entity_type VARCHAR(255),
    entity_id CHAR(36),
    file_url VARCHAR(255),
    file_type VARCHAR(255),
    metadata_json JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
