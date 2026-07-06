-- Rankings and Seat Matrix Schema

CREATE TABLE IF NOT EXISTS rankings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ranking_body ENUM('NIRF','QS','Times','Outlook','IndiaToday','NAAC','Careers360') NOT NULL,
    ranking_year YEAR NOT NULL,
    category ENUM('Overall','Engineering','Management','Medical','Law','Arts') NOT NULL,
    college_id CHAR(36) NOT NULL,
    rank_position INT,
    rank_band VARCHAR(100),
    score FLOAT,
    sub_scores JSON,
    source_url VARCHAR(255),
    published_date DATE,
    previous_year_rank INT,
    rank_delta INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS seat_matrix (
    id INT AUTO_INCREMENT PRIMARY KEY,
    college_id CHAR(36) NOT NULL,
    course_id CHAR(36) NOT NULL,
    category ENUM('General','OBC','SC','ST','EWS','PwD','NRI','Mgmt') NOT NULL,
    year YEAR NOT NULL,
    total_seats INT NOT NULL DEFAULT 0,
    filled_seats INT DEFAULT 0,
    source VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);
