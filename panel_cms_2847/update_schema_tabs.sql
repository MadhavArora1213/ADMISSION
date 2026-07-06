USE admission;

-- Alter Colleges Table
ALTER TABLE colleges
ADD COLUMN about_text LONGTEXT NULL,
ADD COLUMN highlights_json JSON NULL,
ADD COLUMN accreditations JSON NULL,
ADD COLUMN rankings_json JSON NULL,
ADD COLUMN awards_json JSON NULL,

ADD COLUMN admission_process TEXT NULL,
ADD COLUMN accepted_exams JSON NULL,
ADD COLUMN admission_start_date DATE NULL,
ADD COLUMN admission_end_date DATE NULL,
ADD COLUMN merit_based BOOLEAN DEFAULT FALSE,
ADD COLUMN direct_admission BOOLEAN DEFAULT FALSE,
ADD COLUMN management_quota_seats INT DEFAULT 0,
ADD COLUMN nri_quota_seats INT DEFAULT 0,

ADD COLUMN library BOOLEAN DEFAULT FALSE,
ADD COLUMN sports_facilities JSON NULL,
ADD COLUMN labs JSON NULL,
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
ADD COLUMN schema_markup JSON NULL,
ADD COLUMN publish_status ENUM('draft','published') DEFAULT 'draft',
ADD COLUMN published_at TIMESTAMP NULL,
ADD COLUMN noindex BOOLEAN DEFAULT FALSE;

-- Create Reference Tables
CREATE TABLE IF NOT EXISTS courses (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE,
    level ENUM('UG','PG','Diploma','PhD','Certificate') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS exams (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE,
    level ENUM('National','State','University') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert dummy reference data
INSERT IGNORE INTO courses (id, name, slug, level) VALUES
(UUID(), 'B.Tech Computer Science', 'btech-cse', 'UG'),
(UUID(), 'MBA Marketing', 'mba-marketing', 'PG'),
(UUID(), 'MBBS', 'mbbs', 'UG');

INSERT IGNORE INTO exams (id, name, slug, level) VALUES
(UUID(), 'JEE Main', 'jee-main', 'National'),
(UUID(), 'CAT', 'cat', 'National'),
(UUID(), 'NEET', 'neet', 'National');

-- Create 1:N Tables
CREATE TABLE IF NOT EXISTS college_courses (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    course_id CHAR(36) NOT NULL,
    duration_years TINYINT,
    total_fee DECIMAL(10,2),
    semester_fee DECIMAL(10,2),
    annual_fee DECIMAL(10,2),
    seats INT,
    specializations JSON,
    fee_last_updated DATE,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS college_placements (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    placement_year YEAR NOT NULL,
    avg_lpa DECIMAL(5,2),
    highest_lpa DECIMAL(5,2),
    median_lpa DECIMAL(5,2),
    placed_pct FLOAT,
    top_recruiters JSON,
    students_placed INT,
    international_placements INT DEFAULT 0,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS college_cutoffs (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    exam_id CHAR(36) NOT NULL,
    course_id CHAR(36) NOT NULL,
    cutoff_year YEAR NOT NULL,
    category ENUM('General','OBC','SC','ST','EWS','PWD') NOT NULL,
    round_number TINYINT,
    opening_rank INT,
    closing_rank INT,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS college_media (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    media_type ENUM('image','video','document') NOT NULL,
    sub_type ENUM('campus','lab','hostel','event','tour','placement','brochure','prospectus','ranking') NULL,
    url VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(255),
    caption VARCHAR(255),
    sort_order TINYINT DEFAULT 0,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS college_faqs (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(100),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS college_faculty (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    faculty_name VARCHAR(255) NOT NULL,
    designation VARCHAR(150),
    department VARCHAR(150),
    qualification VARCHAR(255),
    experience_years INT,
    photo_url VARCHAR(255),
    research_papers INT DEFAULT 0,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS college_scholarships (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    scholarship_name VARCHAR(255) NOT NULL,
    scholarship_type ENUM('merit','means','sports','reserved_category') NOT NULL,
    amount DECIMAL(10,2),
    eligibility_criteria TEXT,
    renewable BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);
