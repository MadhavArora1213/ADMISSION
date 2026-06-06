-- =============================================
-- MASTER IMPORT FILE for Hostinger
-- Database: u642624414_edusearch
-- Select this database in phpMyAdmin then import
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';


/* === FILE: users_schema.sql === */
-- 1. Roles & Permissions Table
CREATE TABLE roles (
    id VARCHAR(36) PRIMARY KEY, -- Use UUID()
    role_name VARCHAR(100) UNIQUE NOT NULL,
    permissions JSON NOT NULL, -- e.g. {"colleges": ["read", "write"], "users": ["read"]}
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Core Users Table
CREATE TABLE users (
    id VARCHAR(36) PRIMARY KEY, -- Use UUID()
    full_name VARCHAR(200) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(15) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    
    auth_provider ENUM('email', 'google', 'facebook', 'phone_otp') DEFAULT 'email',
    status ENUM('active', 'suspended', 'deleted', 'pending_verification') DEFAULT 'pending_verification',
    
    role_id VARCHAR(36) DEFAULT NULL, -- Foreign key to roles table
    is_super_admin BOOLEAN DEFAULT FALSE, -- If true, bypasses role permissions
    college_access VARCHAR(36) DEFAULT NULL, -- If role is college_admin, limit scope to this college ID
    
    email_verified BOOLEAN DEFAULT FALSE,
    phone_verified BOOLEAN DEFAULT FALSE,
    mfa_enabled BOOLEAN DEFAULT FALSE,
    
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    last_login_ip VARCHAR(45) DEFAULT NULL,
    login_count INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
);

-- 3. Student Profiles
CREATE TABLE student_profiles (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) UNIQUE NOT NULL,
    
    dob DATE DEFAULT NULL,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say') DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    state VARCHAR(100) DEFAULT NULL,
    
    class_12_score FLOAT DEFAULT NULL,
    class_12_stream ENUM('science', 'commerce', 'arts', 'vocational') DEFAULT NULL,
    class_12_board VARCHAR(100) DEFAULT NULL,
    
    preferred_courses JSON DEFAULT NULL, -- Array of course IDs
    target_year YEAR DEFAULT NULL,
    exam_scores JSON DEFAULT NULL, -- {jee_percentile: 95.2, neet: 620}
    shortlisted_college_ids JSON DEFAULT NULL, -- Array of college IDs
    
    profile_completeness TINYINT DEFAULT 0 CHECK (profile_completeness BETWEEN 0 AND 100),
    avatar_url VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Audit Logs (Tracking System Changes)
CREATE TABLE audit_logs (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) DEFAULT NULL, -- Who performed the action
    
    audit_action ENUM('create', 'update', 'delete', 'login', 'export') NOT NULL,
    entity_type VARCHAR(100) NOT NULL, -- e.g., 'college', 'user', 'moderation_queue'
    entity_id VARCHAR(36) DEFAULT NULL,
    
    old_value JSON DEFAULT NULL,
    new_value JSON DEFAULT NULL,
    
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default Super Admin Role
INSERT INTO roles (id, role_name, permissions) VALUES (UUID(), 'Super Administrator', '{"all": ["read", "write", "delete"]}');


/* === FILE: database.sql === */
CREATE DATABASE IF NOT EXISTS admission;


CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- The password is 'admin123' hashed using PHP's password_hash() default algorithm (bcrypt)
-- password_hash('admin123', PASSWORD_DEFAULT);
INSERT INTO admins (username, password) 
VALUES ('admin', '$2y$10$sL1O2n1t8pbVzDBNvlSo2.Jf6mQP6vbzIrPeUX3KGOnZTcqL8lXDS')
ON DUPLICATE KEY UPDATE password='$2y$10$sL1O2n1t8pbVzDBNvlSo2.Jf6mQP6vbzIrPeUX3KGOnZTcqL8lXDS';


/* === FILE: colleges_schema.sql === */


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


/* === FILE: college_details_schema.sql === */


-- 1. Updates to existing 'colleges' table
ALTER TABLE colleges
ADD COLUMN founded_year YEAR NULL,
ADD COLUMN type_label VARCHAR(100) NULL,
ADD COLUMN campus_type ENUM('urban', 'semi-urban', 'rural') NULL,
ADD COLUMN overall_rating_avg FLOAT DEFAULT 0,
ADD COLUMN total_reviews INT DEFAULT 0,
ADD COLUMN rating_distribution JSON NULL,
ADD COLUMN verified_reviews_count INT DEFAULT 0,
ADD COLUMN publish_status ENUM('draft', 'published', 'archived') DEFAULT 'draft';

-- 2. Updates to 'college_media' table
ALTER TABLE college_media
ADD COLUMN image_type ENUM('campus', 'lab', 'hostel', 'event', 'classroom') NULL,
ADD COLUMN video_url VARCHAR(255) NULL,
ADD COLUMN video_type ENUM('tour', 'placement', 'event', 'alumni_talk') NULL,
ADD COLUMN caption VARCHAR(300) NULL,
ADD COLUMN sort_order TINYINT DEFAULT 0,
ADD COLUMN document_type ENUM('brochure', 'prospectus', 'annual_report', 'ranking_cert') NULL,
ADD COLUMN document_url VARCHAR(255) NULL,
ADD COLUMN `360_tour_url` VARCHAR(255) NULL,
ADD COLUMN virtual_tour_enabled BOOLEAN DEFAULT FALSE;

-- 3. college_content
CREATE TABLE IF NOT EXISTS college_content (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    about_text LONGTEXT NULL,
    highlights_json JSON NULL,
    accreditations_json JSON NULL,
    rankings_json JSON NULL,
    awards_json JSON NULL,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 4. college_courses
CREATE TABLE IF NOT EXISTS college_courses (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    course_level ENUM('UG', 'PG', 'Diploma', 'PhD', 'Certificate') NOT NULL,
    duration_years TINYINT NULL,
    total_fee DECIMAL(10,2) NULL,
    semester_fee DECIMAL(10,2) NULL,
    annual_fee DECIMAL(10,2) NULL,
    seats_available INT NULL,
    fee_last_updated DATE NULL,
    specializations JSON NULL,
    eligibility_criteria TEXT NULL,
    application_fee DECIMAL(8,2) NULL,
    emi_available BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 5. college_placements
CREATE TABLE IF NOT EXISTS college_placements (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    placement_year YEAR NOT NULL,
    avg_package_lpa DECIMAL(5,2) NULL,
    highest_package_lpa DECIMAL(5,2) NULL,
    median_package_lpa DECIMAL(5,2) NULL,
    placement_percentage FLOAT NULL,
    students_placed INT NULL,
    international_placements INT NULL,
    top_recruiters JSON NULL,
    sector_wise_json JSON NULL,
    placement_report_pdf VARCHAR(255) NULL,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 6. college_admissions
CREATE TABLE IF NOT EXISTS college_admissions (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    admission_process TEXT NULL,
    accepted_exams JSON NULL,
    admission_start_date DATE NULL,
    admission_end_date DATE NULL,
    merit_based BOOLEAN DEFAULT FALSE,
    direct_admission BOOLEAN DEFAULT FALSE,
    management_quota_seats INT DEFAULT 0,
    nri_quota_seats INT DEFAULT 0,
    lateral_entry_available BOOLEAN DEFAULT FALSE,
    application_mode ENUM('online', 'offline', 'both') NULL,
    selection_criteria TEXT NULL,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- We need a dummy 'exams' table for the FK in college_cutoffs
CREATE TABLE IF NOT EXISTS exams (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

-- 7. college_cutoffs
CREATE TABLE IF NOT EXISTS college_cutoffs (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    exam_id CHAR(36) NULL,
    course_id CHAR(36) NULL,
    category ENUM('General', 'OBC', 'SC', 'ST', 'EWS', 'PwD') NOT NULL,
    year YEAR NOT NULL,
    opening_rank INT NULL,
    closing_rank INT NULL,
    round_number TINYINT NULL,
    quota ENUM('AI', 'HS', 'OS', 'TF', 'PwD') NULL,
    gender ENUM('neutral', 'female_only') NULL,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE SET NULL,
    FOREIGN KEY (course_id) REFERENCES college_courses(id) ON DELETE SET NULL
);

-- 8. college_scholarships
CREATE TABLE IF NOT EXISTS college_scholarships (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    scholarship_name VARCHAR(255) NOT NULL,
    scholarship_type ENUM('merit', 'need', 'sports', 'minority') NULL,
    amount DECIMAL(10,2) NULL,
    amount_type ENUM('fixed', 'percentage', 'full_tuition') NULL,
    eligibility_criteria TEXT NULL,
    renewable BOOLEAN DEFAULT FALSE,
    apply_link VARCHAR(255) NULL,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 9. college_infrastructure
CREATE TABLE IF NOT EXISTS college_infrastructure (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    library BOOLEAN DEFAULT FALSE,
    library_books_count INT NULL,
    sports_facilities JSON NULL,
    labs JSON NULL,
    auditorium BOOLEAN DEFAULT FALSE,
    auditorium_capacity INT NULL,
    cafeteria BOOLEAN DEFAULT FALSE,
    wifi BOOLEAN DEFAULT FALSE,
    wifi_speed_mbps INT NULL,
    medical_facility BOOLEAN DEFAULT FALSE,
    transport BOOLEAN DEFAULT FALSE,
    ev_charging BOOLEAN DEFAULT FALSE,
    solar_power BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 10. college_hostels
CREATE TABLE IF NOT EXISTS college_hostels (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    hostel_available BOOLEAN DEFAULT FALSE,
    hostel_type ENUM('boys', 'girls', 'both', 'co-ed') NULL,
    hostel_capacity INT NULL,
    hostel_fee_annual DECIMAL(10,2) NULL,
    mess_available BOOLEAN DEFAULT FALSE,
    mess_type ENUM('veg', 'non-veg', 'both') NULL,
    ac_available BOOLEAN DEFAULT FALSE,
    room_types JSON NULL,
    security_features JSON NULL,
    laundry_available BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 11. college_faculty
CREATE TABLE IF NOT EXISTS college_faculty (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    faculty_name VARCHAR(200) NOT NULL,
    designation VARCHAR(255) NULL,
    department VARCHAR(255) NULL,
    qualification VARCHAR(255) NULL,
    experience_years TINYINT NULL,
    photo_url VARCHAR(255) NULL,
    research_papers INT NULL,
    linkedin_url VARCHAR(255) NULL,
    specialization VARCHAR(255) NULL,
    phd_from VARCHAR(255) NULL,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 12. college_faqs
CREATE TABLE IF NOT EXISTS college_faqs (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    question_text TEXT NOT NULL,
    answer_text TEXT NOT NULL,
    category VARCHAR(255) NULL,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    schema_faq_enabled BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 13. seo_meta
CREATE TABLE IF NOT EXISTS seo_meta (
    id CHAR(36) PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    entity_id CHAR(36) NOT NULL,
    meta_title VARCHAR(70) NULL,
    meta_description VARCHAR(160) NULL,
    og_image_url VARCHAR(255) NULL,
    canonical_url VARCHAR(255) NULL,
    schema_markup JSON NULL,
    noindex BOOLEAN DEFAULT FALSE,
    breadcrumb_json JSON NULL
);


/* === FILE: universities_schema.sql === */


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

-- 2. Core Universities Table
CREATE TABLE IF NOT EXISTS universities (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(300) NOT NULL,
    slug VARCHAR(300) UNIQUE NOT NULL,
    university_type ENUM('govt', 'private', 'deemed', 'autonomous') NULL,
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
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (duplicate_of) REFERENCES universities(id) ON DELETE SET NULL
);

-- 3. University Contacts
CREATE TABLE IF NOT EXISTS university_contacts (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
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
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);

-- 4. University Accreditations
CREATE TABLE IF NOT EXISTS university_accreditations (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    accreditation_body VARCHAR(255) NOT NULL,
    accreditation_grade VARCHAR(50) NULL,
    accreditation_year YEAR NULL,
    accreditation_valid_until DATE NULL,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);

-- 5. University Media (for logo and cover_image)
CREATE TABLE IF NOT EXISTS university_media (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    logo_url VARCHAR(255) NULL,
    cover_image_url VARCHAR(255) NULL,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);


/* === FILE: university_details_schema.sql === */


-- 1. Updates to existing 'universities' table
ALTER TABLE universities
ADD COLUMN founded_year YEAR NULL,
ADD COLUMN type_label VARCHAR(100) NULL,
ADD COLUMN campus_type ENUM('urban', 'semi-urban', 'rural') NULL,
ADD COLUMN overall_rating_avg FLOAT DEFAULT 0,
ADD COLUMN total_reviews INT DEFAULT 0,
ADD COLUMN rating_distribution JSON NULL,
ADD COLUMN verified_reviews_count INT DEFAULT 0,
ADD COLUMN publish_status ENUM('draft', 'published', 'archived') DEFAULT 'draft';

-- 2. Updates to 'university_media' table
ALTER TABLE university_media
ADD COLUMN image_type ENUM('campus', 'lab', 'hostel', 'event', 'classroom') NULL,
ADD COLUMN video_url VARCHAR(255) NULL,
ADD COLUMN video_type ENUM('tour', 'placement', 'event', 'alumni_talk') NULL,
ADD COLUMN caption VARCHAR(300) NULL,
ADD COLUMN sort_order TINYINT DEFAULT 0,
ADD COLUMN document_type ENUM('brochure', 'prospectus', 'annual_report', 'ranking_cert') NULL,
ADD COLUMN document_url VARCHAR(255) NULL,
ADD COLUMN `360_tour_url` VARCHAR(255) NULL,
ADD COLUMN virtual_tour_enabled BOOLEAN DEFAULT FALSE;

-- 3. university_content
CREATE TABLE IF NOT EXISTS university_content (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    about_text LONGTEXT NULL,
    highlights_json JSON NULL,
    accreditations_json JSON NULL,
    rankings_json JSON NULL,
    awards_json JSON NULL,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);

-- 4. university_courses
CREATE TABLE IF NOT EXISTS university_courses (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    course_level ENUM('UG', 'PG', 'Diploma', 'PhD', 'Certificate') NOT NULL,
    duration_years TINYINT NULL,
    total_fee DECIMAL(10,2) NULL,
    semester_fee DECIMAL(10,2) NULL,
    annual_fee DECIMAL(10,2) NULL,
    seats_available INT NULL,
    fee_last_updated DATE NULL,
    specializations JSON NULL,
    eligibility_criteria TEXT NULL,
    application_fee DECIMAL(8,2) NULL,
    emi_available BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);

-- 5. university_placements
CREATE TABLE IF NOT EXISTS university_placements (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    placement_year YEAR NOT NULL,
    avg_package_lpa DECIMAL(5,2) NULL,
    highest_package_lpa DECIMAL(5,2) NULL,
    median_package_lpa DECIMAL(5,2) NULL,
    placement_percentage FLOAT NULL,
    students_placed INT NULL,
    international_placements INT NULL,
    top_recruiters JSON NULL,
    sector_wise_json JSON NULL,
    placement_report_pdf VARCHAR(255) NULL,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);

-- 6. university_admissions
CREATE TABLE IF NOT EXISTS university_admissions (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    admission_process TEXT NULL,
    accepted_exams JSON NULL,
    admission_start_date DATE NULL,
    admission_end_date DATE NULL,
    merit_based BOOLEAN DEFAULT FALSE,
    direct_admission BOOLEAN DEFAULT FALSE,
    management_quota_seats INT DEFAULT 0,
    nri_quota_seats INT DEFAULT 0,
    lateral_entry_available BOOLEAN DEFAULT FALSE,
    application_mode ENUM('online', 'offline', 'both') NULL,
    selection_criteria TEXT NULL,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);

-- We need a dummy 'exams' table for the FK in university_cutoffs
CREATE TABLE IF NOT EXISTS exams (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

-- 7. university_cutoffs
CREATE TABLE IF NOT EXISTS university_cutoffs (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    exam_id CHAR(36) NULL,
    course_id CHAR(36) NULL,
    category ENUM('General', 'OBC', 'SC', 'ST', 'EWS', 'PwD') NOT NULL,
    year YEAR NOT NULL,
    opening_rank INT NULL,
    closing_rank INT NULL,
    round_number TINYINT NULL,
    quota ENUM('AI', 'HS', 'OS', 'TF', 'PwD') NULL,
    gender ENUM('neutral', 'female_only') NULL,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE SET NULL,
    FOREIGN KEY (course_id) REFERENCES university_courses(id) ON DELETE SET NULL
);

-- 8. university_scholarships
CREATE TABLE IF NOT EXISTS university_scholarships (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    scholarship_name VARCHAR(255) NOT NULL,
    scholarship_type ENUM('merit', 'need', 'sports', 'minority') NULL,
    amount DECIMAL(10,2) NULL,
    amount_type ENUM('fixed', 'percentage', 'full_tuition') NULL,
    eligibility_criteria TEXT NULL,
    renewable BOOLEAN DEFAULT FALSE,
    apply_link VARCHAR(255) NULL,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);

-- 9. university_infrastructure
CREATE TABLE IF NOT EXISTS university_infrastructure (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    library BOOLEAN DEFAULT FALSE,
    library_books_count INT NULL,
    sports_facilities JSON NULL,
    labs JSON NULL,
    auditorium BOOLEAN DEFAULT FALSE,
    auditorium_capacity INT NULL,
    cafeteria BOOLEAN DEFAULT FALSE,
    wifi BOOLEAN DEFAULT FALSE,
    wifi_speed_mbps INT NULL,
    medical_facility BOOLEAN DEFAULT FALSE,
    transport BOOLEAN DEFAULT FALSE,
    ev_charging BOOLEAN DEFAULT FALSE,
    solar_power BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);

-- 10. university_hostels
CREATE TABLE IF NOT EXISTS university_hostels (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    hostel_available BOOLEAN DEFAULT FALSE,
    hostel_type ENUM('boys', 'girls', 'both', 'co-ed') NULL,
    hostel_capacity INT NULL,
    hostel_fee_annual DECIMAL(10,2) NULL,
    mess_available BOOLEAN DEFAULT FALSE,
    mess_type ENUM('veg', 'non-veg', 'both') NULL,
    ac_available BOOLEAN DEFAULT FALSE,
    room_types JSON NULL,
    security_features JSON NULL,
    laundry_available BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);

-- 11. university_faculty
CREATE TABLE IF NOT EXISTS university_faculty (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    faculty_name VARCHAR(200) NOT NULL,
    designation VARCHAR(255) NULL,
    department VARCHAR(255) NULL,
    qualification VARCHAR(255) NULL,
    experience_years TINYINT NULL,
    photo_url VARCHAR(255) NULL,
    research_papers INT NULL,
    linkedin_url VARCHAR(255) NULL,
    specialization VARCHAR(255) NULL,
    phd_from VARCHAR(255) NULL,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);

-- 12. university_faqs
CREATE TABLE IF NOT EXISTS university_faqs (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    question_text TEXT NOT NULL,
    answer_text TEXT NOT NULL,
    category VARCHAR(255) NULL,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    schema_faq_enabled BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);

-- 13. seo_meta
CREATE TABLE IF NOT EXISTS seo_meta (
    id CHAR(36) PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    entity_id CHAR(36) NOT NULL,
    meta_title VARCHAR(70) NULL,
    meta_description VARCHAR(160) NULL,
    og_image_url VARCHAR(255) NULL,
    canonical_url VARCHAR(255) NULL,
    schema_markup JSON NULL,
    noindex BOOLEAN DEFAULT FALSE,
    breadcrumb_json JSON NULL
);


/* === FILE: courses_schema.sql === */


CREATE TABLE IF NOT EXISTS course_categories (
    id CHAR(36) PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL,
    category_slug VARCHAR(255) NOT NULL UNIQUE,
    icon_url VARCHAR(255) NULL,
    parent_category_id CHAR(36) NULL,
    sort_order INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_category_id) REFERENCES course_categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS courses (
    id CHAR(36) PRIMARY KEY,
    course_name VARCHAR(255) NOT NULL,
    course_slug VARCHAR(255) NOT NULL UNIQUE,
    course_level ENUM('UG','PG','Diploma','PhD','Certificate','Integrated') NULL,
    course_category VARCHAR(255) NULL,
    category_id CHAR(36) NULL,
    duration_years TINYINT NULL,
    description LONGTEXT NULL,
    eligibility TEXT NULL,
    career_scope TEXT NULL,
    top_recruiters JSON NULL,
    avg_salary_lpa DECIMAL(5,2) NULL,
    salary_range_min DECIMAL(5,2) NULL,
    salary_range_max DECIMAL(5,2) NULL,
    is_popular BOOLEAN DEFAULT FALSE,
    total_colleges_offering INT DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES course_categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS course_specializations (
    id CHAR(36) PRIMARY KEY,
    parent_course_id CHAR(36) NOT NULL,
    specialization_name VARCHAR(255) NOT NULL,
    specialization_slug VARCHAR(255) NOT NULL,
    description TEXT NULL,
    sort_order INT DEFAULT 0,
    is_popular BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS course_career_paths (
    id CHAR(36) PRIMARY KEY,
    course_id CHAR(36) NOT NULL,
    job_role VARCHAR(255) NOT NULL,
    avg_salary_lpa DECIMAL(5,2) NULL,
    top_companies JSON NULL,
    growth_outlook ENUM('high','medium','low') NULL,
    skills_required JSON NULL,
    fresher_salary_lpa DECIMAL(5,2) NULL,
    experienced_salary_lpa DECIMAL(5,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);


/* === FILE: exams_schema_new_tables.sql === */


CREATE TABLE IF NOT EXISTS exam_dates (
    id CHAR(36) PRIMARY KEY,
    exam_id CHAR(36) NOT NULL,
    year YEAR NULL,
    event_name VARCHAR(255) NULL,
    event_date DATE NULL,
    application_start DATE NULL,
    application_end DATE NULL,
    exam_date DATE NULL,
    result_date DATE NULL,
    admit_card_date DATE NULL,
    counselling_start DATE NULL,
    answer_key_date DATE NULL,
    is_tentative BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS exam_resources (
    id CHAR(36) PRIMARY KEY,
    exam_id CHAR(36) NOT NULL,
    sample_papers_json JSON NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS exam_results (
    id CHAR(36) PRIMARY KEY,
    exam_id CHAR(36) NOT NULL,
    percentile_vs_marks_json JSON NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS exam_syllabus (
    id CHAR(36) PRIMARY KEY,
    exam_id CHAR(36) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    topic VARCHAR(255) NOT NULL,
    subtopics JSON NULL,
    weightage_pct FLOAT NULL,
    chapter_pdf_url VARCHAR(255) NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);


/* === FILE: dashboard_schema.sql === */


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


/* === FILE: leads_schema.sql === */


CREATE TABLE IF NOT EXISTS leads (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NULL,
    lead_type ENUM('inquiry','callback','download','apply','chat_exit') NULL,
    source_page VARCHAR(255) NULL,
    utm_source VARCHAR(255) NULL,
    utm_medium VARCHAR(255) NULL,
    utm_campaign VARCHAR(255) NULL,
    utm_content VARCHAR(255) NULL,
    utm_term VARCHAR(255) NULL,
    gclid VARCHAR(255) NULL,
    
    college_id CHAR(36) NULL,
    course_id CHAR(36) NULL,
    
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    class_12_score FLOAT NULL,
    target_year YEAR NULL,
    preferred_budget DECIMAL(10,2) NULL,
    
    -- CRM & Assignment
    lead_status ENUM('new','contacted','qualified','converted','lost','invalid') DEFAULT 'new',
    assigned_to CHAR(36) NULL,
    priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
    last_contacted_at TIMESTAMP NULL,
    next_followup_at TIMESTAMP NULL,
    call_attempts TINYINT DEFAULT 0,
    counsellor_notes TEXT NULL,
    disposition ENUM('not_reachable','interested','not_interested','wrong_number') NULL,
    sla_breach_at TIMESTAMP NULL,
    
    -- Delivery & Disputes
    delivered_to_college_at TIMESTAMP NULL,
    delivery_status ENUM('pending','delivered','failed','disputed') DEFAULT 'pending',
    dispute_reason TEXT NULL,
    dispute_raised_at TIMESTAMP NULL,
    dispute_resolved_at TIMESTAMP NULL,
    dispute_outcome ENUM('credited','rejected') NULL,
    is_blacklisted BOOLEAN DEFAULT FALSE,
    blacklist_reason TEXT NULL,
    
    -- Attribution
    attribution_model ENUM('first_touch','last_touch','linear','position_based') NULL,
    first_touch_source VARCHAR(255) NULL,
    last_touch_source VARCHAR(255) NULL,
    touchpoints_json JSON NULL,
    revenue_attributed DECIMAL(10,2) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE SET NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS lead_call_logs (
    id CHAR(36) PRIMARY KEY,
    lead_id CHAR(36) NOT NULL,
    call_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    duration_seconds INT NULL,
    outcome ENUM('answered','no_answer','voicemail','busy') NULL,
    recording_url VARCHAR(255) NULL,
    notes TEXT NULL,
    called_by CHAR(36) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (called_by) REFERENCES users(id) ON DELETE SET NULL
);


/* === FILE: reviews_schema.sql === */


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


/* === FILE: cms_schema.sql === */


-- Article Categories
CREATE TABLE IF NOT EXISTS article_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL,
    category_slug VARCHAR(255) NOT NULL UNIQUE,
    parent_id INT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES article_categories(id) ON DELETE SET NULL
);

-- Tags
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag_name VARCHAR(100) NOT NULL,
    tag_slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Articles
CREATE TABLE IF NOT EXISTS articles (
    id CHAR(36) PRIMARY KEY,
    article_title VARCHAR(500) NOT NULL,
    article_slug VARCHAR(500) NOT NULL UNIQUE,
    article_type ENUM('blog','news','guide','exam_update','opinion','ranking') DEFAULT 'blog',
    content_body LONGTEXT NULL,
    excerpt TEXT NULL,
    featured_image_url VARCHAR(255) NULL,
    featured_image_alt VARCHAR(255) NULL,
    author_id CHAR(36) NULL,
    editor_id CHAR(36) NULL,
    category_id INT NULL,
    tags JSON NULL,
    status ENUM('draft','pending_review','published','archived') DEFAULT 'draft',
    publish_at TIMESTAMP NULL,
    reading_time_mins TINYINT NULL,
    view_count INT DEFAULT 0,
    share_count INT DEFAULT 0,

    -- Drafts & Scheduling
    draft_saved_at TIMESTAMP NULL,
    auto_save_version INT DEFAULT 1,
    scheduled_at TIMESTAMP NULL,
    unpublish_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (editor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES article_categories(id) ON DELETE SET NULL
);

-- Article Tags (pivot)
CREATE TABLE IF NOT EXISTS article_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id CHAR(36) NOT NULL,
    tag_id INT NOT NULL,
    UNIQUE KEY uq_article_tag (article_id, tag_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- SEO Meta
CREATE TABLE IF NOT EXISTS seo_meta (
    id CHAR(36) PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL COMMENT 'e.g. article, college, course',
    entity_id CHAR(36) NOT NULL,
    meta_title VARCHAR(70) NULL,
    meta_description VARCHAR(160) NULL,
    og_title VARCHAR(255) NULL,
    og_description TEXT NULL,
    og_image VARCHAR(255) NULL,
    canonical_url VARCHAR(255) NULL,
    schema_type ENUM('Article','NewsArticle','HowTo','FAQ') NULL,
    schema_json JSON NULL,
    primary_keyword VARCHAR(255) NULL,
    keyword_density FLOAT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_seo_entity (entity_type, entity_id)
);

-- Media Library
CREATE TABLE IF NOT EXISTS media_files (
    id CHAR(36) PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    file_url VARCHAR(255) NOT NULL,
    cdn_url VARCHAR(255) NULL,
    file_type ENUM('image','video','pdf','doc','svg') DEFAULT 'image',
    file_size_kb INT NULL,
    dimensions_json JSON NULL,
    alt_text VARCHAR(255) NULL,
    uploaded_by CHAR(36) NULL,
    folder_path VARCHAR(255) NULL,
    webp_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Article Revisions
CREATE TABLE IF NOT EXISTS article_revisions (
    id CHAR(36) PRIMARY KEY,
    article_id CHAR(36) NOT NULL,
    version INT NOT NULL,
    user_id CHAR(36) NULL,
    content_snapshot LONGTEXT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);


/* === FILE: analytics_schema.sql === */
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


/* === FILE: applications_schema.sql === */
CREATE TABLE IF NOT EXISTS `applications` (
    `id` CHAR(36) PRIMARY KEY,
    `user_id` CHAR(36) NOT NULL,
    `college_id` CHAR(36) NOT NULL,
    `course_id` CHAR(36) NOT NULL,
    `application_number` VARCHAR(50) UNIQUE NOT NULL,
    `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('draft','submitted','under_review','waitlisted','admitted','rejected') DEFAULT 'draft',
    `payment_status` ENUM('pending','paid','refunded','waived') DEFAULT 'pending',
    `fee_paid` DECIMAL(10,2) DEFAULT 0.00,
    `transaction_id` VARCHAR(100) NULL,
    `counsellor_assigned` CHAR(36) NULL,
    `remarks` TEXT NULL,
    `interview_date` TIMESTAMP NULL,
    `offer_letter_url` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`college_id`) REFERENCES `colleges`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`counsellor_assigned`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `application_documents` (
    `id` CHAR(36) PRIMARY KEY,
    `application_id` CHAR(36) NOT NULL,
    `doc_type` ENUM('class10','class12','id_proof','photo','caste_cert','domicile') NOT NULL,
    `doc_url` VARCHAR(255) NOT NULL,
    `verification_status` ENUM('pending','verified','rejected') DEFAULT 'pending',
    `verified_by` CHAR(36) NULL,
    `rejection_reason` TEXT NULL,
    `verified_at` TIMESTAMP NULL,
    `ocr_extracted_data` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`application_id`) REFERENCES `applications`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `payments` (
    `id` CHAR(36) PRIMARY KEY,
    `application_id` CHAR(36) NOT NULL,
    `gateway` ENUM('razorpay','stripe','paytm','cashfree') NOT NULL,
    `gateway_txn_id` VARCHAR(100) NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` CHAR(3) DEFAULT 'INR',
    `payment_status` ENUM('initiated','success','failed','refunded') DEFAULT 'initiated',
    `paid_at` TIMESTAMP NULL,
    `refund_status` ENUM('none','requested','processed') DEFAULT 'none',
    `refund_amount` DECIMAL(10,2) DEFAULT 0.00,
    `invoice_url` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`application_id`) REFERENCES `applications`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


/* === FILE: billing_revenue_schema.sql === */
CREATE TABLE IF NOT EXISTS subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(255) NOT NULL,
    plan_type ENUM('basic', 'standard', 'premium', 'enterprise') NOT NULL,
    
    -- Recommended fields
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    features JSON,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    college_id INT NOT NULL, -- Assuming INT for colleges.id. Change to VARCHAR(36) if using UUIDs
    plan_id INT NOT NULL,    -- Recommended FK
    amount DECIMAL(10,2) NOT NULL,
    billing_cycle ENUM('monthly', 'quarterly', 'annual') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    auto_renew BOOLEAN DEFAULT TRUE,
    
    -- Recommended fields
    status ENUM('active', 'cancelled', 'expired', 'pending') DEFAULT 'pending',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    -- FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
);

CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(100) UNIQUE NOT NULL,
    college_id INT NOT NULL,      -- Recommended FK (who is billed)
    subscription_id INT,          -- Recommended FK (optional, link to sub)
    gst_number VARCHAR(15),
    gst_amount DECIMAL(10,2) DEFAULT 0.00,
    payment_method ENUM('bank_transfer', 'card', 'upi'),
    payment_status ENUM('pending', 'paid', 'overdue') DEFAULT 'pending',
    
    -- Recommended fields
    subtotal_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    invoice_date DATE NOT NULL,
    due_date DATE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS lead_credits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    college_id INT NOT NULL,      -- Recommended FK (who owns the credits)
    leads_purchased INT NOT NULL DEFAULT 0,
    leads_delivered INT NOT NULL DEFAULT 0,
    lead_cost DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    credits_remaining INT GENERATED ALWAYS AS (leads_purchased - leads_delivered) STORED, -- Computed
    expiry_date DATE,
    
    -- Recommended fields
    status ENUM('active', 'expired', 'depleted') DEFAULT 'active',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ad_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    college_id INT NOT NULL,      -- Recommended FK (who bought the ad)
    ad_type ENUM('banner', 'sponsored_listing', 'featured_badge', 'email_blast') NOT NULL,
    ad_placement VARCHAR(255),
    ad_start DATE,
    ad_end DATE,
    impressions INT DEFAULT 0,
    clicks INT DEFAULT 0,
    ctr FLOAT GENERATED ALWAYS AS (IF(impressions > 0, (clicks / impressions) * 100, 0)) STORED, -- Computed
    
    -- Recommended fields
    media_url VARCHAR(255),
    target_url VARCHAR(255),
    cost_usd DECIMAL(10,2),
    status ENUM('active', 'paused', 'completed') DEFAULT 'active',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS commissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,  -- Change to VARCHAR(36) if using UUIDs for applications
    college_id INT NOT NULL,      -- Recommended FK (billed to)
    consultant_id INT,            -- Recommended FK (optional, payee)
    invoice_id INT,               -- Recommended FK (optional link to generated invoice)
    commission_pct FLOAT NOT NULL,
    commission_earned DECIMAL(10,2) NOT NULL,
    commission_status ENUM('pending', 'paid', 'disputed') DEFAULT 'pending',
    payout_date DATE,
    payout_method ENUM('bank_transfer', 'credit'),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


/* === FILE: community_schema.sql === */


CREATE TABLE IF NOT EXISTS questions (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    question_text TEXT NOT NULL,
    question_category ENUM('admission','fees','placements','hostel','exams','general') NOT NULL,
    related_college_id CHAR(36) NULL,
    related_exam_id CHAR(36) NULL,
    related_course_id CHAR(36) NULL,
    asked_by CHAR(36) NOT NULL,
    views INT DEFAULT 0,
    answer_count INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    status ENUM('open','answered','closed') DEFAULT 'open',
    trending_score FLOAT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (asked_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS answers (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    question_id CHAR(36) NOT NULL,
    answer_text TEXT NOT NULL,
    answered_by CHAR(36) NOT NULL,
    is_expert_answer BOOLEAN DEFAULT FALSE,
    is_verified_alumnus BOOLEAN DEFAULT FALSE,
    upvotes INT DEFAULT 0,
    is_accepted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id),
    FOREIGN KEY (answered_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS experts (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    expert_name VARCHAR(255) NOT NULL,
    expert_designation VARCHAR(255) NULL,
    expert_college VARCHAR(255) NULL,
    verified_badge BOOLEAN DEFAULT FALSE,
    answer_count INT DEFAULT 0,
    profile_url VARCHAR(255) NULL,
    specialization VARCHAR(255) NULL,
    linkedin_url VARCHAR(255) NULL,
    response_rate_pct FLOAT DEFAULT 0,
    avg_response_hours FLOAT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS qa_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id CHAR(36) NULL,
    answer_id CHAR(36) NULL,
    report_reason ENUM('spam','offensive','wrong_info','duplicate') NOT NULL,
    reported_by CHAR(36) NOT NULL,
    moderation_action ENUM('approve','reject','remove','warn_user') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES users(id),
    FOREIGN KEY (question_id) REFERENCES questions(id),
    FOREIGN KEY (answer_id) REFERENCES answers(id)
);


/* === FILE: compare_engine_schema.sql === */
CREATE TABLE IF NOT EXISTS `compare_config` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `max_entities` TINYINT DEFAULT 4,
    `compare_fields_config` JSON COMMENT 'Ordered list of field groups',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `compare_sessions` (
    `id` CHAR(36) PRIMARY KEY,
    `comparison_type` ENUM('college', 'course', 'exam') NOT NULL,
    `entity_ids` JSON NOT NULL COMMENT 'JSON array of 2-4 UUIDs',
    `user_id` CHAR(36) NULL COMMENT 'Nullable for anonymous users',
    `session_id` VARCHAR(255) NULL COMMENT 'Anonymous tracking',
    `is_saved` BOOLEAN DEFAULT FALSE,
    `share_token` VARCHAR(255) UNIQUE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


/* === FILE: emi_calculator_schema.sql === */
-- 1. Calculator Configuration (Single-row table for global settings)
CREATE TABLE calculator_config (
    id INT PRIMARY KEY DEFAULT 1,
    
    loan_providers JSON DEFAULT NULL, -- [{name, logo, interest_rate_range, max_tenure}]
    default_interest_rate_pct FLOAT DEFAULT 10.5,
    
    max_tenure_months INT DEFAULT 84,
    min_loan_amount DECIMAL(10,2) DEFAULT 0.00,
    max_loan_amount DECIMAL(10,2) DEFAULT 5000000.00, -- 50 Lakhs
    
    processing_fee_pct FLOAT DEFAULT 1.0,
    tax_rate FLOAT DEFAULT 0.18, -- 18% GST usually
    
    is_active BOOLEAN DEFAULT TRUE,
    affiliate_links JSON DEFAULT NULL, -- [{provider, url, cta_label}]
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CHECK (id = 1)
);

-- Insert the default configuration row
INSERT INTO calculator_config (id) VALUES (1) ON DUPLICATE KEY UPDATE id=1;


-- 2. Calculator Sessions (Tracking user inputs and leads)
CREATE TABLE calculator_sessions (
    id VARCHAR(36) PRIMARY KEY, -- Use UUID()
    session_token VARCHAR(255) DEFAULT NULL, -- For tracking anonymous guest sessions
    
    user_id VARCHAR(36) DEFAULT NULL, -- FK to users (nullable)
    college_id VARCHAR(36) DEFAULT NULL, -- FK to colleges (context of where they used it)
    
    fee_amount DECIMAL(10,2) NOT NULL,
    down_payment DECIMAL(10,2) DEFAULT 0.00,
    loan_amount DECIMAL(10,2) NOT NULL,
    
    tenure_months INT NOT NULL,
    interest_rate FLOAT NOT NULL,
    
    emi_results JSON NOT NULL, -- {monthly_emi, total_interest, total_payment}
    provider_compared JSON DEFAULT NULL, -- Array of provider names
    
    lead_captured_at TIMESTAMP NULL DEFAULT NULL, -- Timestamp if they clicked 'Apply for Loan'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE SET NULL
);


/* === FILE: enterprise_colleges_schema_fixed.sql === */


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


/* === FILE: enterprise_dashboard_schema.sql === */


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


/* === FILE: moderation_schema.sql === */
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


/* === FILE: notifications_schema.sql === */


CREATE TABLE IF NOT EXISTS notification_templates (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    template_name VARCHAR(255) NOT NULL,
    channel ENUM('email','sms','push','whatsapp','in_app') NOT NULL,
    subject VARCHAR(255) NULL,
    body_html LONGTEXT NULL,
    body_text TEXT NULL,
    variables_json JSON NULL,
    language ENUM('en','hi') DEFAULT 'en',
    is_active BOOLEAN DEFAULT TRUE,
    category ENUM('transactional','marketing','alert') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS audience_segments (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    segment_name VARCHAR(255) NOT NULL,
    filters_json JSON NOT NULL,
    user_count INT DEFAULT 0,
    refresh_schedule VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notification_campaigns (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    campaign_name VARCHAR(255) NOT NULL,
    template_id CHAR(36) NOT NULL,
    audience_segment_id CHAR(36) NULL,
    scheduled_at TIMESTAMP NULL,
    sent_count INT DEFAULT 0,
    delivered_count INT DEFAULT 0,
    opened_count INT DEFAULT 0,
    clicked_count INT DEFAULT 0,
    unsubscribed_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    status ENUM('draft','scheduled','sending','sent','cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES notification_templates(id),
    FOREIGN KEY (audience_segment_id) REFERENCES audience_segments(id)
);

CREATE TABLE IF NOT EXISTS notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    campaign_id CHAR(36) NULL,
    channel ENUM('email','sms','push','whatsapp','in_app') NOT NULL,
    status ENUM('sent','delivered','failed','bounced','opened') NOT NULL,
    error_message TEXT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES notification_campaigns(id)
);


/* === FILE: partner_portal_schema.sql === */
-- 1. Main Partner Accounts Table (The Billing/Contract Entity)
CREATE TABLE partners (
    id VARCHAR(36) PRIMARY KEY, -- Use UUID()
    partner_college_id VARCHAR(36) NOT NULL, -- FK to colleges
    
    contact_person VARCHAR(150) NOT NULL,
    designation VARCHAR(100),
    
    -- Contract & Billing
    plan_id VARCHAR(36) DEFAULT NULL, -- FK to subscription_plans
    leads_quota INT DEFAULT 0,
    leads_used INT DEFAULT 0,
    contract_start DATE DEFAULT NULL,
    contract_end DATE DEFAULT NULL,
    
    -- Management
    account_manager_id VARCHAR(36) DEFAULT NULL, -- FK to users (Your EduSearch Admin)
    onboarding_status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    status ENUM('active', 'suspended', 'trial', 'churned') DEFAULT 'trial',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (partner_college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 2. Partner Users (The people who actually log into the portal)
CREATE TABLE partner_users (
    id VARCHAR(36) PRIMARY KEY,
    partner_id VARCHAR(36) NOT NULL, -- FK to partners
    
    full_name VARCHAR(150) NOT NULL,
    login_email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    
    access_level ENUM('read', 'write', 'admin') DEFAULT 'read',
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
);

-- 3. Partner Content Management (Approval Queue)
CREATE TABLE partner_content_requests (
    id VARCHAR(36) PRIMARY KEY,
    college_id VARCHAR(36) NOT NULL, -- FK to colleges
    requested_by VARCHAR(36) NOT NULL, -- FK to partner_users
    
    content_type ENUM('info', 'photo', 'placement', 'course', 'ranking') NOT NULL,
    submitted_data JSON NOT NULL, -- The new data they want to publish
    
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by VARCHAR(36) DEFAULT NULL, -- FK to users (Your EduSearch Admin)
    review_notes TEXT DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES partner_users(id) ON DELETE CASCADE
);


/* === FILE: predictor_schema.sql === */
-- 1. Predictor Configuration Table
-- Stores the algorithm weights, offsets, and rules for predicting colleges per exam per year
CREATE TABLE predictor_config (
    id VARCHAR(36) PRIMARY KEY, -- Use UUID()
    exam_id VARCHAR(36) NOT NULL, -- FK to exams table
    
    model_weights JSON NOT NULL, -- e.g. {"rank":0.5, "category":0.3, "state":0.2}
    data_year YEAR NOT NULL,
    prediction_accuracy FLOAT DEFAULT NULL, -- Last backtested accuracy
    
    min_score INT DEFAULT 0,
    max_score INT NOT NULL,
    
    category_adjustments JSON DEFAULT NULL, -- Rank band offsets by category
    
    state_quota_enabled BOOLEAN DEFAULT FALSE,
    home_state_quota_pct FLOAT DEFAULT 0.0,
    counselling_round_model TINYINT DEFAULT 1, -- Rounds to simulate
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    UNIQUE(exam_id, data_year) -- Only one active config per exam per year
);

-- 2. Predictor Submissions & Results
-- Stores the data entered by students and the results generated by the algorithm
CREATE TABLE predictor_submissions (
    id VARCHAR(36) PRIMARY KEY, -- Use UUID()
    user_id VARCHAR(36) DEFAULT NULL, -- Null if anonymous/guest
    exam_id VARCHAR(36) NOT NULL, -- FK to exams table
    
    score INT DEFAULT NULL,
    rank INT DEFAULT NULL,
    percentile FLOAT DEFAULT NULL,
    
    category ENUM('General', 'OBC', 'SC', 'ST', 'EWS', 'PwD') DEFAULT 'General',
    preferred_state VARCHAR(100) DEFAULT NULL,
    preferred_course_id VARCHAR(36) DEFAULT NULL, -- FK to courses table
    
    results JSON DEFAULT NULL, -- Array of objects: [{college_id, probability, tier, match_factors}]
    share_token VARCHAR(255) UNIQUE NOT NULL, -- For public sharing links
    
    lead_captured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Assuming users, exams, and courses tables exist with VARCHAR(36) UUIDs
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (preferred_course_id) REFERENCES courses(id) ON DELETE SET NULL
);


/* === FILE: rankings_schema.sql === */
-- Rankings and Seat Matrix Schema

CREATE TABLE IF NOT EXISTS rankings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ranking_body ENUM('NIRF','QS','Times','Outlook','IndiaToday','NAAC','Careers360') NOT NULL,
    ranking_year YEAR NOT NULL,
    category ENUM('Overall','Engineering','Management','Medical','Law','Arts') NOT NULL,
    college_id CHAR(36) NOT NULL,
    rank_position INT,
    rank_band VARCHAR(100),
    score FLOAT,
    sub_scores JSON,
    source_url VARCHAR(255),
    published_date DATE,
    previous_year_rank INT,
    rank_delta INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS seat_matrix (
    id INT AUTO_INCREMENT PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    course_id CHAR(36) NOT NULL,
    category ENUM('General','OBC','SC','ST','EWS','PwD','NRI','Mgmt') NOT NULL,
    year YEAR NOT NULL,
    total_seats INT NOT NULL DEFAULT 0,
    filled_seats INT DEFAULT 0,
    source VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);


/* === FILE: scholarships_schema.sql === */
CREATE TABLE IF NOT EXISTS `scholarships` (
    `id` CHAR(36) PRIMARY KEY,
    `scholarship_name` VARCHAR(255) NOT NULL,
    `scholarship_slug` VARCHAR(255) UNIQUE NOT NULL,
    `provider_name` VARCHAR(255) NULL,
    `provider_logo` VARCHAR(255) NULL,
    `scholarship_type` ENUM('government','private','college','abroad','sports','minority') NULL,
    `amount` DECIMAL(10,2) NULL,
    `amount_type` ENUM('fixed','percentage','full_tuition','stipend') NULL,
    `eligibility_criteria` TEXT NULL,
    `min_percentage` FLOAT NULL,
    `income_limit` DECIMAL(12,2) NULL,
    `gender` ENUM('all','male','female','transgender') DEFAULT 'all',
    `category` ENUM('all','sc','st','obc','ews','minority','pwd') DEFAULT 'all',
    `state_specific` VARCHAR(255) NULL,
    `course_levels` JSON NULL COMMENT 'JSON array of course levels',
    `apply_start` DATE NULL,
    `apply_end` DATE NULL,
    `official_link` VARCHAR(255) NULL,
    `renewable` BOOLEAN DEFAULT FALSE,
    `renewable_conditions` TEXT NULL,
    `status` ENUM('active','expired','upcoming') DEFAULT 'upcoming',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


/* === FILE: search_schema.sql === */


-- Search Indices Config
CREATE TABLE IF NOT EXISTS search_indices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    index_name VARCHAR(100) NOT NULL UNIQUE,
    entity_type ENUM('college','exam','course','article','scholarship') NOT NULL,
    indexed_at TIMESTAMP NULL,
    document_count INT DEFAULT 0,
    search_weight_config JSON NULL,
    facets_config JSON NULL,
    stop_words JSON NULL,
    language ENUM('en','hi') DEFAULT 'en',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Search Analytics (Queries)
CREATE TABLE IF NOT EXISTS search_queries (
    id CHAR(36) PRIMARY KEY,
    query_text VARCHAR(255) NOT NULL,
    results_count INT DEFAULT 0,
    clicked_result_id CHAR(36) NULL,
    clicked_type ENUM('college','exam','course','article') NULL,
    session_id VARCHAR(255) NULL,
    user_id CHAR(36) NULL,
    zero_results BOOLEAN DEFAULT FALSE,
    device_type ENUM('mobile','desktop','tablet') NULL,
    filters_applied JSON NULL,
    search_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Autocomplete Suggestions
CREATE TABLE IF NOT EXISTS search_suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    suggestion_text VARCHAR(255) NOT NULL UNIQUE,
    suggestion_type ENUM('college','exam','course','city','query') NOT NULL,
    frequency INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Synonyms Dictionary
CREATE TABLE IF NOT EXISTS search_synonyms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    canonical VARCHAR(255) NOT NULL UNIQUE,
    synonyms JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Trending Searches
CREATE TABLE IF NOT EXISTS search_trending (
    id INT AUTO_INCREMENT PRIMARY KEY,
    query_text VARCHAR(255) NOT NULL,
    trending_score FLOAT DEFAULT 0.0,
    trending_period ENUM('daily','weekly','monthly') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_trending (query_text, trending_period)
);


/* === FILE: seo_schema.sql === */


-- Alter existing seo_meta table to match the new spec
ALTER TABLE seo_meta 
    CHANGE entity_type page_type ENUM('college','exam','course','article','listing','tool') NOT NULL,
    CHANGE entity_id page_id CHAR(36) NOT NULL,
    ADD COLUMN robots_directive ENUM('index_follow','noindex','nofollow') NULL,
    ADD COLUMN hreflang VARCHAR(50) NULL,
    ADD COLUMN last_crawled_at TIMESTAMP NULL,
    ADD COLUMN google_index_status ENUM('indexed','not_indexed','excluded') NULL;

-- Modify schema_type to match new ENUM
ALTER TABLE seo_meta
    MODIFY schema_type ENUM('College','Exam','Article','FAQPage','BreadcrumbList') NULL;

-- Redirects
CREATE TABLE IF NOT EXISTS redirects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    redirect_from VARCHAR(255) NOT NULL UNIQUE,
    redirect_to VARCHAR(255) NOT NULL,
    redirect_type ENUM('301','302','410') DEFAULT '301',
    redirect_reason VARCHAR(255) NULL,
    hits INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sitemaps & Internal Linking
CREATE TABLE IF NOT EXISTS sitemaps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sitemap_name VARCHAR(100) NOT NULL,
    sitemap_url VARCHAR(255) NOT NULL UNIQUE,
    sitemap_type ENUM('colleges','exams','courses','articles','tools') NOT NULL,
    last_generated_at TIMESTAMP NULL,
    url_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS internal_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    link_source_page VARCHAR(255) NOT NULL,
    link_target_page VARCHAR(255) NOT NULL,
    anchor_text VARCHAR(255) NULL,
    is_broken BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Programmatic SEO Templates
CREATE TABLE IF NOT EXISTS seo_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL,
    template_slug_pattern VARCHAR(255) NOT NULL,
    data_source ENUM('colleges','exams','courses') NOT NULL,
    title_template VARCHAR(255) NOT NULL,
    description_template TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    pages_generated INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


/* === FILE: shortlist_schema.sql === */
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


/* === FILE: study_abroad_schema.sql === */
CREATE TABLE IF NOT EXISTS foreign_universities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    university_name VARCHAR(255) NOT NULL,
    university_slug VARCHAR(255) UNIQUE NOT NULL,
    country_id INT NOT NULL, -- Ensure you have a 'countries' table for this FK
    qs_rank INT,
    times_rank INT,
    acceptance_rate FLOAT,
    tuition_usd_annual DECIMAL(10,2),
    living_cost_usd_monthly DECIMAL(8,2),
    intake_months JSON, -- e.g. ["Jan", "Sep"]
    official_url VARCHAR(255),
    min_ielts FLOAT,
    min_toefl INT,
    min_gre INT,
    scholarship_available BOOLEAN DEFAULT FALSE,
    
    -- Recommended Additional Fields
    logo_url VARCHAR(255),
    description TEXT,
    city VARCHAR(100),
    institution_type ENUM('Public', 'Private'),
    application_fee_usd DECIMAL(8,2),
    min_pte FLOAT,
    min_gmat INT,
    min_gpa VARCHAR(50),
    degrees_offered JSON,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS visa_guides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_id INT NOT NULL, -- Crucial: links visa guide to specific country
    visa_type VARCHAR(100) NOT NULL,
    processing_time_days INT,
    visa_fee_usd DECIMAL(8,2),
    documents_required JSON,
    success_tips TEXT,
    
    -- Recommended Additional Fields
    pswv_duration_months INT, -- Post-Study Work Visa duration
    proof_of_funds_usd DECIMAL(10,2),
    interview_required BOOLEAN DEFAULT FALSE,
    part_time_work_hours INT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS consultants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consultant_name VARCHAR(255) NOT NULL,
    consultant_rating FLOAT,
    verified_consultant BOOLEAN DEFAULT FALSE,
    specialization_countries JSON,
    fee_range VARCHAR(100),
    
    -- Recommended Additional Fields
    logo_url VARCHAR(255),
    contact_email VARCHAR(150),
    contact_phone VARCHAR(50),
    address VARCHAR(255),
    city VARCHAR(100),
    experience_years INT,
    consultation_mode ENUM('Online', 'Offline', 'Both') DEFAULT 'Both',
    success_rate_percent FLOAT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


/* === FILE: system_settings_schema.sql === */
-- 1. System Configuration Table
-- Designed as a single-row table to store global settings
CREATE TABLE system_config (
    id INT PRIMARY KEY DEFAULT 1, -- Always 1, single row
    
    -- General & SMTP
    site_name VARCHAR(255) DEFAULT 'AdmissionSeason',
    site_url VARCHAR(255) DEFAULT 'https://admissionseason.com',
    logo_url VARCHAR(255) DEFAULT NULL,
    favicon_url VARCHAR(255) DEFAULT NULL,
    maintenance_mode BOOLEAN DEFAULT FALSE,
    maintenance_message TEXT DEFAULT NULL,
    
    smtp_host VARCHAR(255) DEFAULT NULL,
    smtp_port INT DEFAULT 587,
    smtp_user VARCHAR(255) DEFAULT NULL,
    smtp_password TEXT DEFAULT NULL, -- Should be encrypted in application
    from_email VARCHAR(255) DEFAULT NULL,
    from_name VARCHAR(255) DEFAULT NULL,
    
    -- Storage, CDN & Payment Gateways
    storage_provider ENUM('s3', 'cloudinary', 'gcs', 'r2', 'local') DEFAULT 'local',
    storage_bucket VARCHAR(255) DEFAULT NULL,
    cdn_url VARCHAR(255) DEFAULT NULL,
    
    payment_gateway ENUM('razorpay', 'stripe', 'paytm', 'cashfree', 'none') DEFAULT 'none',
    gateway_key TEXT DEFAULT NULL, -- Encrypted
    gateway_secret TEXT DEFAULT NULL, -- Encrypted
    gst_rate FLOAT DEFAULT 0.18,
    currency_default CHAR(3) DEFAULT 'INR',
    
    -- AI Keys, Security & Backups
    openai_api_key TEXT DEFAULT NULL, -- Encrypted
    gemini_api_key TEXT DEFAULT NULL, -- Encrypted
    ai_provider ENUM('openai', 'gemini', 'anthropic', 'ollama') DEFAULT 'openai',
    
    mfa_enabled BOOLEAN DEFAULT TRUE,
    session_timeout_mins INT DEFAULT 60,
    max_login_attempts INT DEFAULT 5,
    ip_whitelist JSON DEFAULT NULL,
    api_rate_limit_per_min INT DEFAULT 60,
    
    backup_schedule VARCHAR(100) DEFAULT '0 0 * * *', -- Cron format
    backup_retention_days INT DEFAULT 30,
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by VARCHAR(36) DEFAULT NULL, -- FK to users table
    
    -- Constraint to ensure only one row exists
    CHECK (id = 1)
);

-- Insert the default configuration row
INSERT INTO system_config (id) VALUES (1) ON DUPLICATE KEY UPDATE id=1;

-- 2. API Keys Table
-- For programmatic access to the system
CREATE TABLE api_keys (
    id VARCHAR(36) PRIMARY KEY, -- Use UUID()
    api_key_name VARCHAR(150) NOT NULL,
    api_key_hash VARCHAR(255) NOT NULL, -- Hashed key (never store raw API keys)
    api_scope JSON DEFAULT NULL, -- Array of module names (e.g., ["colleges", "leads"])
    
    api_expires_at TIMESTAMP NULL DEFAULT NULL,
    last_used_at TIMESTAMP NULL DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    
    created_by VARCHAR(36) DEFAULT NULL, -- FK to users table
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


/* === FILE: ugc_and_engagement_schema.sql === */


-- 1. Q&A Forum (Community Engagement)
CREATE TABLE IF NOT EXISTS college_qna (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    user_id CHAR(36) NULL,
    question_text TEXT NOT NULL,
    answer_text TEXT NULL,
    answered_by_user_id CHAR(36) NULL,
    upvotes INT DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 2. Notable Alumni
CREATE TABLE IF NOT EXISTS college_alumni (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    current_company VARCHAR(255) NULL,
    designation VARCHAR(255) NULL,
    graduation_year YEAR NULL,
    photo_url VARCHAR(255) NULL,
    linkedin_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 3. News, Updates & Important Dates
CREATE TABLE IF NOT EXISTS college_updates (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    update_type ENUM('news', 'event', 'admission_deadline', 'exam_date') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    event_date DATE NULL,
    action_url VARCHAR(255) NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- 4. Alter colleges to add rating cache
ALTER TABLE colleges
ADD COLUMN average_rating DECIMAL(3,2) DEFAULT 0.00,
ADD COLUMN total_reviews INT DEFAULT 0;

-- 5. Alter college_courses to add syllabus and eligibility
ALTER TABLE college_courses
ADD COLUMN eligibility_criteria TEXT NULL,
ADD COLUMN selection_process TEXT NULL,
ADD COLUMN syllabus_url VARCHAR(255) NULL;

-- 6. Alter college_reviews to add more granular details
ALTER TABLE college_reviews
ADD COLUMN course_id CHAR(36) NULL,
ADD COLUMN year_of_passing YEAR NULL,
ADD COLUMN review_title VARCHAR(255) NULL,
ADD COLUMN pros TEXT NULL,
ADD COLUMN cons TEXT NULL,
ADD CONSTRAINT fk_review_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL;


/* === FILE: ai_systems_schema.sql === */
-- AI Systems Schema

CREATE TABLE IF NOT EXISTS ai_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    model_name VARCHAR(255) NOT NULL,
    system_prompt LONGTEXT,
    temperature FLOAT DEFAULT 0.7,
    max_tokens INT DEFAULT 800,
    fallback_response TEXT,
    session_memory BOOLEAN DEFAULT TRUE,
    escalation_keywords JSON,
    lead_capture_enabled BOOLEAN DEFAULT FALSE,
    whatsapp_bot_enabled BOOLEAN DEFAULT FALSE,
    response_language ENUM('en','hi','en_hi_mix') DEFAULT 'en',
    spam_threshold FLOAT DEFAULT 0.8,
    auto_approve_threshold FLOAT DEFAULT 0.2,
    auto_reject_threshold FLOAT DEFAULT 0.9,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ai_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    algo_type ENUM('collaborative','content','hybrid','llm_ranked') DEFAULT 'hybrid',
    feature_weights JSON,
    user_profile_fields JSON,
    recommendation_limit TINYINT DEFAULT 10,
    personalization_enabled BOOLEAN DEFAULT TRUE,
    model_version VARCHAR(50),
    retrain_schedule VARCHAR(50),
    ab_test_variant VARCHAR(50),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS predictor_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    predictor_exam_id CHAR(36),
    input_score INT,
    input_rank INT,
    input_category ENUM('General','OBC','SC','ST','EWS','PwD'),
    input_state VARCHAR(100),
    input_course_pref CHAR(36),
    predicted_colleges JSON,
    confidence_score FLOAT,
    model_year YEAR,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (predictor_exam_id) REFERENCES exams(id) ON DELETE SET NULL,
    FOREIGN KEY (input_course_pref) REFERENCES courses(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS ai_chat_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    messages_json JSON,
    lead_captured BOOLEAN DEFAULT FALSE,
    entity_context JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default AI Config if empty
INSERT INTO ai_config (model_name, system_prompt, escalation_keywords) 
SELECT 'gpt-4o', 'You are a helpful admission counsellor for students.', '["human","help","agent","contact","call"]'
WHERE NOT EXISTS (SELECT 1 FROM ai_config);

-- Insert default Recommendation settings if empty
INSERT INTO ai_recommendations (model_version, retrain_schedule) 
SELECT 'v1.0.0', '0 0 * * *'
WHERE NOT EXISTS (SELECT 1 FROM ai_recommendations);


SET FOREIGN_KEY_CHECKS = 1;
