USE admission;

-- 1. Reference Tables (needed for FKs)
CREATE TABLE IF NOT EXISTS states (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    state_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS universities (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL
);

-- 2. Core Colleges Table
CREATE TABLE IF NOT EXISTS colleges (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(300) NOT NULL,
    slug VARCHAR(300) UNIQUE NOT NULL,
    college_type ENUM('govt', 'private', 'deemed', 'autonomous') NULL,
    ownership ENUM('central', 'state', 'private_trust', 'minority') NULL,
    status ENUM('active', 'pending', 'archived', 'rejected') DEFAULT 'pending',
    is_featured BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    featured_order INT DEFAULT 0,
    ranking_nirf INT NULL,
    ranking_qs INT NULL,
    ranking_times INT NULL,
    city_id INT NULL,
    state_id INT NULL,
    established_year YEAR NULL,
    data_quality_score TINYINT DEFAULT 0,
    
    university_affiliation VARCHAR(255) NULL,
    university_id CHAR(36) NULL,
    autonomous BOOLEAN DEFAULT FALSE,
    naac_grade ENUM('A++', 'A+', 'A', 'B++', 'B+', 'B', 'C') NULL,
    ugc_approved BOOLEAN DEFAULT FALSE,
    aicte_approved BOOLEAN DEFAULT FALSE,
    nba_approved BOOLEAN DEFAULT FALSE,
    total_students INT DEFAULT 0,
    total_faculty INT DEFAULT 0,
    campus_area_acres FLOAT NULL,
    
    verification_status ENUM('unverified', 'pending', 'verified', 'disputed') DEFAULT 'unverified',
    verified_by CHAR(36) NULL,
    verified_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    duplicate_of CHAR(36) NULL,
    import_batch_id CHAR(36) NULL,
    last_data_audit_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL,
    FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE SET NULL,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (duplicate_of) REFERENCES colleges(id) ON DELETE SET NULL
);

-- 3. College Contacts
CREATE TABLE IF NOT EXISTS college_contacts (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    website_url VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    latitude DECIMAL(9,6) NULL,
    longitude DECIMAL(9,6) NULL,
    pincode VARCHAR(10) NULL,
    google_maps_embed_url VARCHAR(500) NULL,
    nearest_railway_km FLOAT NULL,
    nearest_airport_km FLOAT NULL,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 4. College Accreditations
CREATE TABLE IF NOT EXISTS college_accreditations (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    accreditation_body VARCHAR(255) NOT NULL,
    accreditation_grade VARCHAR(50) NULL,
    accreditation_year YEAR NULL,
    accreditation_valid_until DATE NULL,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 5. College Media (for logo and cover_image)
CREATE TABLE IF NOT EXISTS college_media (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    logo_url VARCHAR(255) NULL,
    cover_image_url VARCHAR(255) NULL,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);
