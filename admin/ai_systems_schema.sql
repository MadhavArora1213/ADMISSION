-- AI Systems Schema

CREATE TABLE IF NOT EXISTS ai_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    model_name VARCHAR(255) NOT NULL,
    system_prompt LONGTEXT,
    temperature FLOAT DEFAULT 0.7,
    max_tokens INT DEFAULT 800,
    fallback_response TEXT,
    session_memory BOOLEAN DEFAULT TRUE,
    escalation_keywords JSON,
    lead_capture_enabled BOOLEAN DEFAULT FALSE,
    whatsapp_bot_enabled BOOLEAN DEFAULT FALSE,
    response_language ENUM('en','hi','en_hi_mix') DEFAULT 'en',
    spam_threshold FLOAT DEFAULT 0.8,
    auto_approve_threshold FLOAT DEFAULT 0.2,
    auto_reject_threshold FLOAT DEFAULT 0.9,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ai_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    algo_type ENUM('collaborative','content','hybrid','llm_ranked') DEFAULT 'hybrid',
    feature_weights JSON,
    user_profile_fields JSON,
    recommendation_limit TINYINT DEFAULT 10,
    personalization_enabled BOOLEAN DEFAULT TRUE,
    model_version VARCHAR(50),
    retrain_schedule VARCHAR(50),
    ab_test_variant VARCHAR(50),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS predictor_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    predictor_exam_id CHAR(36),
    input_score INT,
    input_rank INT,
    input_category ENUM('General','OBC','SC','ST','EWS','PwD'),
    input_state VARCHAR(100),
    input_course_pref CHAR(36),
    predicted_colleges JSON,
    confidence_score FLOAT,
    model_year YEAR,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (predictor_exam_id) REFERENCES exams(id) ON DELETE SET NULL,
    FOREIGN KEY (input_course_pref) REFERENCES courses(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS ai_chat_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    messages_json JSON,
    lead_captured BOOLEAN DEFAULT FALSE,
    entity_context JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default AI Config if empty
INSERT INTO ai_config (model_name, system_prompt, escalation_keywords) 
SELECT 'gpt-4o', 'You are a helpful admission counsellor for students.', '["human","help","agent","contact","call"]'
WHERE NOT EXISTS (SELECT 1 FROM ai_config);

-- Insert default Recommendation settings if empty
INSERT INTO ai_recommendations (model_version, retrain_schedule) 
SELECT 'v1.0.0', '0 0 * * *'
WHERE NOT EXISTS (SELECT 1 FROM ai_recommendations);
