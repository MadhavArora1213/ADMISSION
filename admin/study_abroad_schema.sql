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
