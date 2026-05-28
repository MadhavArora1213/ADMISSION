USE admission;

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
