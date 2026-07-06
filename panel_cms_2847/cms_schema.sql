USE admission;

-- Article Categories
CREATE TABLE IF NOT EXISTS article_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL,
    category_slug VARCHAR(255) NOT NULL UNIQUE,
    parent_id INT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES article_categories(id) ON DELETE SET NULL
);

-- Tags
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag_name VARCHAR(100) NOT NULL,
    tag_slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Articles
CREATE TABLE IF NOT EXISTS articles (
    id CHAR(36) PRIMARY KEY,
    article_title VARCHAR(500) NOT NULL,
    article_slug VARCHAR(500) NOT NULL UNIQUE,
    article_type ENUM('blog','news','guide','exam_update','opinion','ranking') DEFAULT 'blog',
    content_body LONGTEXT NULL,
    excerpt TEXT NULL,
    featured_image_url VARCHAR(255) NULL,
    featured_image_alt VARCHAR(255) NULL,
    author_id CHAR(36) NULL,
    editor_id CHAR(36) NULL,
    category_id INT NULL,
    tags JSON NULL,
    status ENUM('draft','pending_review','published','archived') DEFAULT 'draft',
    publish_at TIMESTAMP NULL,
    reading_time_mins TINYINT NULL,
    view_count INT DEFAULT 0,
    share_count INT DEFAULT 0,

    -- Drafts & Scheduling
    draft_saved_at TIMESTAMP NULL,
    auto_save_version INT DEFAULT 1,
    scheduled_at TIMESTAMP NULL,
    unpublish_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (editor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES article_categories(id) ON DELETE SET NULL
);

-- Article Tags (pivot)
CREATE TABLE IF NOT EXISTS article_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id CHAR(36) NOT NULL,
    tag_id INT NOT NULL,
    UNIQUE KEY uq_article_tag (article_id, tag_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- SEO Meta
CREATE TABLE IF NOT EXISTS seo_meta (
    id CHAR(36) PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL COMMENT 'e.g. article, college, course',
    entity_id CHAR(36) NOT NULL,
    meta_title VARCHAR(70) NULL,
    meta_description VARCHAR(160) NULL,
    og_title VARCHAR(255) NULL,
    og_description TEXT NULL,
    og_image VARCHAR(255) NULL,
    canonical_url VARCHAR(255) NULL,
    schema_type ENUM('Article','NewsArticle','HowTo','FAQ') NULL,
    schema_json JSON NULL,
    primary_keyword VARCHAR(255) NULL,
    keyword_density FLOAT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_seo_entity (entity_type, entity_id)
);

-- Media Library
CREATE TABLE IF NOT EXISTS media_files (
    id CHAR(36) PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    file_url VARCHAR(255) NOT NULL,
    cdn_url VARCHAR(255) NULL,
    file_type ENUM('image','video','pdf','doc','svg') DEFAULT 'image',
    file_size_kb INT NULL,
    dimensions_json JSON NULL,
    alt_text VARCHAR(255) NULL,
    uploaded_by CHAR(36) NULL,
    folder_path VARCHAR(255) NULL,
    webp_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Article Revisions
CREATE TABLE IF NOT EXISTS article_revisions (
    id CHAR(36) PRIMARY KEY,
    article_id CHAR(36) NOT NULL,
    version INT NOT NULL,
    user_id CHAR(36) NULL,
    content_snapshot LONGTEXT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
