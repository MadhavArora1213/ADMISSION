-- College engagement tables for News & Q&A tabs
USE admission;

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
    INDEX idx_college_qna_college (college_id),
    INDEX idx_college_qna_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS college_updates (
    id CHAR(36) PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    update_type ENUM('news', 'event', 'admission_deadline', 'exam_date') NOT NULL DEFAULT 'news',
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    event_date DATE NULL,
    action_url VARCHAR(255) NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_college_updates_college (college_id),
    INDEX idx_college_updates_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
