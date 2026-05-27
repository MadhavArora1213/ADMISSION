USE admission;

-- 1. DYNAMIC CONTENT ENGINE
CREATE TABLE IF NOT EXISTS college_content_blocks (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    block_type VARCHAR(255),
    block_name VARCHAR(255),
    block_data_json JSON,
    sort_order INT DEFAULT 0,
    visibility_rules_json JSON,
    status ENUM('active','draft','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 2. UNIVERSAL MEDIA SYSTEM
CREATE TABLE IF NOT EXISTS media_assets (
    id CHAR(36) PRIMARY KEY,
    file_name VARCHAR(255),
    mime_type VARCHAR(100),
    file_size BIGINT,
    storage_provider VARCHAR(100),
    file_url TEXT,
    thumbnail_url TEXT,
    alt_text TEXT,
    caption TEXT,
    metadata_json JSON,
    uploaded_by CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS entity_media (
    id CHAR(36) PRIMARY KEY,
    entity_type VARCHAR(255),
    entity_id CHAR(36),
    media_asset_id CHAR(36),
    module_name VARCHAR(255),
    sort_order INT DEFAULT 0,
    FOREIGN KEY (media_asset_id) REFERENCES media_assets(id) ON DELETE CASCADE
);

-- 3. DYNAMIC FIELD SYSTEM
CREATE TABLE IF NOT EXISTS custom_fields (
    id CHAR(36) PRIMARY KEY,
    module_name VARCHAR(255),
    field_key VARCHAR(255),
    field_label VARCHAR(255),
    field_type VARCHAR(100),
    validation_json JSON,
    options_json JSON,
    settings_json JSON
);

-- 4. LEAD MANAGEMENT
CREATE TABLE IF NOT EXISTS college_leads (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    user_id CHAR(36) NULL,
    lead_source VARCHAR(255),
    course_interest CHAR(36) NULL,
    status ENUM('new','contacted','qualified','converted','lost') DEFAULT 'new',
    assigned_counsellor CHAR(36) NULL,
    notes_json JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 5. REVIEWS SYSTEM
CREATE TABLE IF NOT EXISTS college_reviews (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    user_id CHAR(36) NULL,
    rating DECIMAL(3,2),
    placements_rating DECIMAL(3,2),
    faculty_rating DECIMAL(3,2),
    hostel_rating DECIMAL(3,2),
    campus_rating DECIMAL(3,2),
    review_text LONGTEXT,
    verified BOOLEAN DEFAULT FALSE,
    moderation_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 6. PROGRAMMATIC SEO ENGINE
CREATE TABLE IF NOT EXISTS seo_pages (
    id CHAR(36) PRIMARY KEY,
    page_type VARCHAR(255),
    slug VARCHAR(255) UNIQUE,
    title VARCHAR(255),
    meta_description TEXT,
    filters_json JSON,
    schema_json JSON,
    content_json JSON,
    generated BOOLEAN DEFAULT TRUE
);

-- 7. INFRASTRUCTURE SYSTEM
CREATE TABLE IF NOT EXISTS college_facilities (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    facility_name VARCHAR(255),
    description TEXT,
    images_json JSON,
    availability ENUM('available','limited','unavailable') DEFAULT 'available',
    metadata_json JSON,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 8. HOSTEL SYSTEM
CREATE TABLE IF NOT EXISTS hostel_rooms (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    room_type VARCHAR(255),
    occupancy INT,
    annual_fee DECIMAL(10,2),
    facilities_json JSON,
    images_json JSON,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 9. ANALYTICS SYSTEM
CREATE TABLE IF NOT EXISTS college_analytics (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    views INT DEFAULT 0,
    unique_visitors INT DEFAULT 0,
    lead_conversion_rate FLOAT DEFAULT 0,
    avg_time_spent FLOAT DEFAULT 0,
    saved_count INT DEFAULT 0,
    compared_count INT DEFAULT 0,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 10. VERSION HISTORY
CREATE TABLE IF NOT EXISTS entity_versions (
    id CHAR(36) PRIMARY KEY,
    entity_type VARCHAR(255),
    entity_id CHAR(36),
    snapshot_json JSON,
    changed_by CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 11. APPROVAL WORKFLOW
CREATE TABLE IF NOT EXISTS approval_workflows (
    id CHAR(36) PRIMARY KEY,
    entity_type VARCHAR(255),
    entity_id CHAR(36),
    submitted_by CHAR(36),
    reviewed_by CHAR(36),
    status ENUM('pending','approved','rejected','changes_requested') DEFAULT 'pending',
    comments TEXT
);

-- ==========================================
-- ALTER EXISTING TABLES
-- ==========================================

-- Alter `colleges`
ALTER TABLE colleges
DROP COLUMN about_text,
ADD COLUMN about_content_json JSON NULL,
ADD COLUMN ai_tags JSON NULL,
ADD COLUMN ai_summary TEXT NULL,
ADD COLUMN embedding_vector JSON NULL,
ADD COLUMN search_keywords LONGTEXT NULL,
ADD COLUMN search_weight FLOAT DEFAULT 1.0,
ADD COLUMN comparison_priority INT DEFAULT 0,
ADD COLUMN highlight_features JSON NULL;

-- Alter `college_courses`
ALTER TABLE college_courses
ADD COLUMN mode ENUM('full_time','part_time','online','distance') NULL,
ADD COLUMN study_type ENUM('degree','diploma','certificate') NULL;

-- Alter `college_placements`
ALTER TABLE college_placements
ADD COLUMN placement_trends_json JSON NULL,
ADD COLUMN salary_distribution_json JSON NULL;

-- Alter `college_faculty`
ALTER TABLE college_faculty
ADD COLUMN linkedin_url VARCHAR(255) NULL,
ADD COLUMN google_scholar_url VARCHAR(255) NULL,
ADD COLUMN research_interests JSON NULL,
ADD COLUMN patents_count INT DEFAULT 0,
ADD COLUMN citations_count INT DEFAULT 0;

-- Alter `college_scholarships`
ALTER TABLE college_scholarships
ADD COLUMN application_url VARCHAR(255) NULL,
ADD COLUMN deadline DATE NULL,
ADD COLUMN documents_required JSON NULL,
ADD COLUMN renewal_conditions TEXT NULL;
