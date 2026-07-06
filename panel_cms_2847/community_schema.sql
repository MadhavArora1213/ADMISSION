USE admission;

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
