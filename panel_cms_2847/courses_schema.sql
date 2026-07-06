USE admission;

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
