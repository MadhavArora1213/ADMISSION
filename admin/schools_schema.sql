USE admission;

-- 1. Schools Table
CREATE TABLE IF NOT EXISTS schools (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(300) NOT NULL,
    slug VARCHAR(300) UNIQUE NOT NULL,
    school_type ENUM('govt', 'private', 'aided', 'unaided', 'international', 'boarding') NULL,
    ownership ENUM('central', 'state', 'private_trust', 'minority') NULL,
    board_affiliation ENUM('CBSE', 'ICSE', 'State', 'IB', 'IGCSE', 'NIOS') NULL,
    board_state_name VARCHAR(100) NULL,
    status ENUM('active', 'pending', 'archived', 'rejected') DEFAULT 'pending',
    is_featured BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    featured_order INT DEFAULT 0,
    city_id INT NULL,
    state_id INT NULL,
    established_year YEAR NULL,
    total_students INT DEFAULT 0,
    total_faculty INT DEFAULT 0,
    campus_area_acres FLOAT NULL,
    publish_status ENUM('published', 'draft', 'archived') DEFAULT 'draft',
    overall_rating_avg DECIMAL(2,1) DEFAULT 0,
    total_reviews INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL,
    FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE SET NULL
);

-- 2. School Contacts
CREATE TABLE IF NOT EXISTS school_contacts (
    id CHAR(36) PRIMARY KEY,
    school_id CHAR(36) NOT NULL,
    website_url VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    latitude DECIMAL(9,6) NULL,
    longitude DECIMAL(9,6) NULL,
    pincode VARCHAR(10) NULL,
    google_maps_embed_url VARCHAR(500) NULL,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
);

-- 3. School Media (logo & cover)
CREATE TABLE IF NOT EXISTS school_media (
    id CHAR(36) PRIMARY KEY,
    school_id CHAR(36) NOT NULL,
    logo_url VARCHAR(255) NULL,
    cover_image_url VARCHAR(255) NULL,
    image_type VARCHAR(50) NULL,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
);

-- 4. School Content (about, highlights, admissions)
CREATE TABLE IF NOT EXISTS school_content (
    id CHAR(36) PRIMARY KEY,
    school_id CHAR(36) NOT NULL,
    about_text LONGTEXT NULL,
    highlights_json JSON NULL,
    admission_process TEXT NULL,
    accepted_exams JSON NULL,
    admission_start_date DATE NULL,
    admission_end_date DATE NULL,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    UNIQUE KEY unique_school_content (school_id)
);

-- 5. School Infrastructure
CREATE TABLE IF NOT EXISTS school_infrastructure (
    id CHAR(36) PRIMARY KEY,
    school_id CHAR(36) NOT NULL,
    library BOOLEAN DEFAULT FALSE,
    auditorium BOOLEAN DEFAULT FALSE,
    cafeteria BOOLEAN DEFAULT FALSE,
    wifi BOOLEAN DEFAULT FALSE,
    medical_facility BOOLEAN DEFAULT FALSE,
    transport BOOLEAN DEFAULT FALSE,
    playground BOOLEAN DEFAULT FALSE,
    swimming_pool BOOLEAN DEFAULT FALSE,
    labs BOOLEAN DEFAULT FALSE,
    smart_classrooms BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    UNIQUE KEY unique_school_infrastructure (school_id)
);
