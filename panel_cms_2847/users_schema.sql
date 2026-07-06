-- 1. Roles & Permissions Table
CREATE TABLE roles (
    id VARCHAR(36) PRIMARY KEY, -- Use UUID()
    role_name VARCHAR(100) UNIQUE NOT NULL,
    permissions JSON NOT NULL, -- e.g. {"colleges": ["read", "write"], "users": ["read"]}
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Core Users Table
CREATE TABLE users (
    id VARCHAR(36) PRIMARY KEY, -- Use UUID()
    full_name VARCHAR(200) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(15) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    
    auth_provider ENUM('email', 'google', 'facebook', 'phone_otp') DEFAULT 'email',
    status ENUM('active', 'suspended', 'deleted', 'pending_verification') DEFAULT 'pending_verification',
    
    role_id VARCHAR(36) DEFAULT NULL, -- Foreign key to roles table
    is_super_admin BOOLEAN DEFAULT FALSE, -- If true, bypasses role permissions
    college_access VARCHAR(36) DEFAULT NULL, -- If role is college_admin, limit scope to this college ID
    
    email_verified BOOLEAN DEFAULT FALSE,
    phone_verified BOOLEAN DEFAULT FALSE,
    mfa_enabled BOOLEAN DEFAULT FALSE,
    
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    last_login_ip VARCHAR(45) DEFAULT NULL,
    login_count INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
);

-- 3. Student Profiles
CREATE TABLE student_profiles (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) UNIQUE NOT NULL,
    
    dob DATE DEFAULT NULL,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say') DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    state VARCHAR(100) DEFAULT NULL,
    
    class_12_score FLOAT DEFAULT NULL,
    class_12_stream ENUM('science', 'commerce', 'arts', 'vocational') DEFAULT NULL,
    class_12_board VARCHAR(100) DEFAULT NULL,
    
    preferred_courses JSON DEFAULT NULL, -- Array of course IDs
    target_year YEAR DEFAULT NULL,
    exam_scores JSON DEFAULT NULL, -- {jee_percentile: 95.2, neet: 620}
    shortlisted_college_ids JSON DEFAULT NULL, -- Array of college IDs
    
    profile_completeness TINYINT DEFAULT 0 CHECK (profile_completeness BETWEEN 0 AND 100),
    avatar_url VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Audit Logs (Tracking System Changes)
CREATE TABLE audit_logs (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) DEFAULT NULL, -- Who performed the action
    
    audit_action ENUM('create', 'update', 'delete', 'login', 'export') NOT NULL,
    entity_type VARCHAR(100) NOT NULL, -- e.g., 'college', 'user', 'moderation_queue'
    entity_id VARCHAR(36) DEFAULT NULL,
    
    old_value JSON DEFAULT NULL,
    new_value JSON DEFAULT NULL,
    
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default Super Admin Role
INSERT INTO roles (id, role_name, permissions) VALUES (UUID(), 'Super Administrator', '{"all": ["read", "write", "delete"]}');
