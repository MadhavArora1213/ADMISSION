USE admission;

-- Search Indices Config
CREATE TABLE IF NOT EXISTS search_indices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    index_name VARCHAR(100) NOT NULL UNIQUE,
    entity_type ENUM('college','exam','course','article','scholarship') NOT NULL,
    indexed_at TIMESTAMP NULL,
    document_count INT DEFAULT 0,
    search_weight_config JSON NULL,
    facets_config JSON NULL,
    stop_words JSON NULL,
    language ENUM('en','hi') DEFAULT 'en',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Search Analytics (Queries)
CREATE TABLE IF NOT EXISTS search_queries (
    id CHAR(36) PRIMARY KEY,
    query_text VARCHAR(255) NOT NULL,
    results_count INT DEFAULT 0,
    clicked_result_id CHAR(36) NULL,
    clicked_type ENUM('college','exam','course','article') NULL,
    session_id VARCHAR(255) NULL,
    user_id CHAR(36) NULL,
    zero_results BOOLEAN DEFAULT FALSE,
    device_type ENUM('mobile','desktop','tablet') NULL,
    filters_applied JSON NULL,
    search_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Autocomplete Suggestions
CREATE TABLE IF NOT EXISTS search_suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    suggestion_text VARCHAR(255) NOT NULL UNIQUE,
    suggestion_type ENUM('college','exam','course','city','query') NOT NULL,
    frequency INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Synonyms Dictionary
CREATE TABLE IF NOT EXISTS search_synonyms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    canonical VARCHAR(255) NOT NULL UNIQUE,
    synonyms JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Trending Searches
CREATE TABLE IF NOT EXISTS search_trending (
    id INT AUTO_INCREMENT PRIMARY KEY,
    query_text VARCHAR(255) NOT NULL,
    trending_score FLOAT DEFAULT 0.0,
    trending_period ENUM('daily','weekly','monthly') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_trending (query_text, trending_period)
);
