USE admission;

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
