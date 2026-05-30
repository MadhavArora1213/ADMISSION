USE admission;

-- Alter existing seo_meta table to match the new spec
ALTER TABLE seo_meta 
    CHANGE entity_type page_type ENUM('college','exam','course','article','listing','tool') NOT NULL,
    CHANGE entity_id page_id CHAR(36) NOT NULL,
    ADD COLUMN robots_directive ENUM('index_follow','noindex','nofollow') NULL,
    ADD COLUMN hreflang VARCHAR(50) NULL,
    ADD COLUMN last_crawled_at TIMESTAMP NULL,
    ADD COLUMN google_index_status ENUM('indexed','not_indexed','excluded') NULL;

-- Modify schema_type to match new ENUM
ALTER TABLE seo_meta
    MODIFY schema_type ENUM('College','Exam','Article','FAQPage','BreadcrumbList') NULL;

-- Redirects
CREATE TABLE IF NOT EXISTS redirects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    redirect_from VARCHAR(255) NOT NULL UNIQUE,
    redirect_to VARCHAR(255) NOT NULL,
    redirect_type ENUM('301','302','410') DEFAULT '301',
    redirect_reason VARCHAR(255) NULL,
    hits INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sitemaps & Internal Linking
CREATE TABLE IF NOT EXISTS sitemaps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sitemap_name VARCHAR(100) NOT NULL,
    sitemap_url VARCHAR(255) NOT NULL UNIQUE,
    sitemap_type ENUM('colleges','exams','courses','articles','tools') NOT NULL,
    last_generated_at TIMESTAMP NULL,
    url_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS internal_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    link_source_page VARCHAR(255) NOT NULL,
    link_target_page VARCHAR(255) NOT NULL,
    anchor_text VARCHAR(255) NULL,
    is_broken BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Programmatic SEO Templates
CREATE TABLE IF NOT EXISTS seo_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL,
    template_slug_pattern VARCHAR(255) NOT NULL,
    data_source ENUM('colleges','exams','courses') NOT NULL,
    title_template VARCHAR(255) NOT NULL,
    description_template TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    pages_generated INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
