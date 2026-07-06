USE admission;

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
