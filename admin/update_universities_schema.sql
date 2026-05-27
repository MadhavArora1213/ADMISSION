USE admission;

ALTER TABLE universities
ADD COLUMN university_type ENUM('govt','private','deemed','autonomous') NULL,
ADD COLUMN ownership ENUM('public','private','trust','society') NULL,
ADD COLUMN status ENUM('active','pending','archived') DEFAULT 'pending',
ADD COLUMN logo_url VARCHAR(255) NULL,
ADD COLUMN cover_image_url VARCHAR(255) NULL,
ADD COLUMN established_year INT NULL,
ADD COLUMN autonomous BOOLEAN DEFAULT FALSE,
ADD COLUMN ugc_approved BOOLEAN DEFAULT FALSE,
ADD COLUMN aicte_approved BOOLEAN DEFAULT FALSE,
ADD COLUMN total_students INT NULL,
ADD COLUMN total_faculty INT NULL,
ADD COLUMN campus_area_acres FLOAT NULL,
ADD COLUMN city_id INT NULL,
ADD COLUMN state_id INT NULL,
ADD COLUMN naac_grade ENUM('A++','A+','A','B++','B+','B','C','None') DEFAULT 'None',
ADD COLUMN nirf_rank INT NULL,
ADD COLUMN is_verified BOOLEAN DEFAULT FALSE,
ADD COLUMN is_featured BOOLEAN DEFAULT FALSE,
ADD COLUMN featured_order INT DEFAULT 0,
ADD COLUMN data_quality_score TINYINT DEFAULT 0,
ADD COLUMN verification_status ENUM('pending','verified','rejected') DEFAULT 'pending',
ADD COLUMN verified_by CHAR(36) NULL,
ADD COLUMN verified_at TIMESTAMP NULL,
ADD COLUMN rejection_reason TEXT NULL,
ADD COLUMN duplicate_of CHAR(36) NULL,
ADD COLUMN import_batch_id CHAR(36) NULL,
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN archived_at TIMESTAMP NULL,
ADD COLUMN about_text LONGTEXT NULL,
ADD COLUMN highlights_json LONGTEXT NULL,
ADD COLUMN accreditations LONGTEXT NULL,
ADD COLUMN rankings_json LONGTEXT NULL,
ADD COLUMN awards_json LONGTEXT NULL,
ADD COLUMN admission_process TEXT NULL,
ADD COLUMN accepted_exams LONGTEXT NULL,
ADD COLUMN admission_start_date DATE NULL,
ADD COLUMN admission_end_date DATE NULL,
ADD COLUMN merit_based BOOLEAN DEFAULT FALSE,
ADD COLUMN direct_admission BOOLEAN DEFAULT FALSE,
ADD COLUMN management_quota_seats INT DEFAULT 0,
ADD COLUMN nri_quota_seats INT DEFAULT 0,
ADD COLUMN library BOOLEAN DEFAULT FALSE,
ADD COLUMN sports_facilities LONGTEXT NULL,
ADD COLUMN labs LONGTEXT NULL,
ADD COLUMN auditorium BOOLEAN DEFAULT FALSE,
ADD COLUMN cafeteria BOOLEAN DEFAULT FALSE,
ADD COLUMN wifi BOOLEAN DEFAULT FALSE,
ADD COLUMN medical_facility BOOLEAN DEFAULT FALSE,
ADD COLUMN transport BOOLEAN DEFAULT FALSE,
ADD COLUMN hostel_available BOOLEAN DEFAULT FALSE,
ADD COLUMN hostel_type ENUM('boys','girls','both') NULL,
ADD COLUMN hostel_capacity INT DEFAULT 0,
ADD COLUMN hostel_fee_annual DECIMAL(10,2) NULL,
ADD COLUMN mess_available BOOLEAN DEFAULT FALSE,
ADD COLUMN mess_type ENUM('veg','non-veg','both') NULL,
ADD COLUMN ac_available BOOLEAN DEFAULT FALSE,
ADD COLUMN meta_title VARCHAR(70) NULL,
ADD COLUMN meta_description VARCHAR(160) NULL,
ADD COLUMN meta_keywords TEXT NULL,
ADD COLUMN og_image_url VARCHAR(255) NULL,
ADD COLUMN canonical_url VARCHAR(255) NULL,
ADD COLUMN schema_markup LONGTEXT NULL,
ADD COLUMN publish_status ENUM('draft','published') DEFAULT 'draft',
ADD COLUMN published_at TIMESTAMP NULL,
ADD COLUMN noindex BOOLEAN DEFAULT FALSE;

CREATE TABLE IF NOT EXISTS university_contacts (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    website_url VARCHAR(255),
    pincode VARCHAR(20),
    google_maps_url VARCHAR(255),
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS university_courses (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    course_id CHAR(36) NOT NULL,
    duration_years TINYINT,
    total_fee DECIMAL(10,2),
    semester_fee DECIMAL(10,2),
    annual_fee DECIMAL(10,2),
    seats INT,
    specializations JSON,
    fee_last_updated DATE,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS university_placements (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    placement_year YEAR NOT NULL,
    avg_lpa DECIMAL(5,2),
    highest_lpa DECIMAL(5,2),
    median_lpa DECIMAL(5,2),
    placed_pct FLOAT,
    top_recruiters JSON,
    students_placed INT,
    international_placements INT DEFAULT 0,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS university_cutoffs (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    exam_id CHAR(36) NOT NULL,
    course_id CHAR(36) NOT NULL,
    cutoff_year YEAR NOT NULL,
    category ENUM('General','OBC','SC','ST','EWS','PWD') NOT NULL,
    round_number TINYINT,
    opening_rank INT,
    closing_rank INT,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS university_media (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    media_type ENUM('image','video','document') NOT NULL,
    sub_type ENUM('campus','lab','hostel','event','tour','placement','brochure','prospectus','ranking') NULL,
    url VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(255),
    caption VARCHAR(255),
    sort_order TINYINT DEFAULT 0,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS university_faqs (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(100),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS university_faculty (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    faculty_name VARCHAR(255) NOT NULL,
    designation VARCHAR(150),
    department VARCHAR(150),
    qualification VARCHAR(255),
    experience_years INT,
    photo_url VARCHAR(255),
    research_papers INT DEFAULT 0,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS university_scholarships (
    id CHAR(36) PRIMARY KEY,
    university_id CHAR(36) NOT NULL,
    scholarship_name VARCHAR(255) NOT NULL,
    scholarship_type ENUM('merit','means','sports','reserved_category') NOT NULL,
    amount DECIMAL(10,2),
    eligibility_criteria TEXT,
    renewable BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);
