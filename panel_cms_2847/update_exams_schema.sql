USE admission;

ALTER TABLE exams
ADD COLUMN conducting_body VARCHAR(255) NULL,
ADD COLUMN exam_mode ENUM('online','offline','both') NULL,
ADD COLUMN frequency ENUM('annual','biannual','monthly') NULL,
ADD COLUMN is_national BOOLEAN DEFAULT FALSE,
ADD COLUMN status ENUM('active','upcoming','completed') DEFAULT 'upcoming',
ADD COLUMN application_start DATE NULL,
ADD COLUMN application_end DATE NULL,
ADD COLUMN exam_date DATE NULL,
ADD COLUMN result_date DATE NULL,
ADD COLUMN admit_card_date DATE NULL,
ADD COLUMN counselling_start DATE NULL,
ADD COLUMN answer_key_date DATE NULL,
ADD COLUMN is_tentative BOOLEAN DEFAULT FALSE,
ADD COLUMN age_min INT NULL,
ADD COLUMN age_max INT NULL,
ADD COLUMN min_percentage_required FLOAT NULL,
ADD COLUMN qualifying_exam VARCHAR(255) NULL,
ADD COLUMN total_marks INT NULL,
ADD COLUMN total_questions INT NULL,
ADD COLUMN duration_minutes INT NULL,
ADD COLUMN subjects_json LONGTEXT NULL,
ADD COLUMN marking_scheme LONGTEXT NULL,
ADD COLUMN application_fee_general DECIMAL(10,2) NULL,
ADD COLUMN application_fee_obc DECIMAL(10,2) NULL,
ADD COLUMN application_fee_sc_st DECIMAL(10,2) NULL,
ADD COLUMN application_url VARCHAR(255) NULL,
ADD COLUMN official_website VARCHAR(255) NULL,
ADD COLUMN syllabus_pdf_url VARCHAR(255) NULL,
ADD COLUMN result_url VARCHAR(255) NULL,
ADD COLUMN scorecard_url VARCHAR(255) NULL,
ADD COLUMN counselling_authority VARCHAR(255) NULL,
ADD COLUMN counselling_rounds INT NULL,
ADD COLUMN merit_list_url VARCHAR(255) NULL;

CREATE TABLE IF NOT EXISTS exam_dates (
    id CHAR(36) PRIMARY KEY,
    exam_id CHAR(36) NOT NULL,
    event_name VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    is_tentative BOOLEAN DEFAULT FALSE,
    year YEAR NOT NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS exam_syllabus (
    id CHAR(36) PRIMARY KEY,
    exam_id CHAR(36) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    topic VARCHAR(255) NOT NULL,
    subtopics LONGTEXT NULL,
    weightage_pct FLOAT NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS exam_cutoffs (
    id CHAR(36) PRIMARY KEY,
    exam_id CHAR(36) NOT NULL,
    college_id CHAR(36) NOT NULL,
    course_id CHAR(36) NOT NULL,
    year YEAR NOT NULL,
    category ENUM('General','OBC','SC','ST','EWS','PWD') NOT NULL,
    opening_rank INT NULL,
    closing_rank INT NULL,
    round TINYINT NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);
