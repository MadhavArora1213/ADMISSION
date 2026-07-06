CREATE TABLE IF NOT EXISTS `compare_config` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `max_entities` TINYINT DEFAULT 4,
    `compare_fields_config` JSON COMMENT 'Ordered list of field groups',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `compare_sessions` (
    `id` CHAR(36) PRIMARY KEY,
    `comparison_type` ENUM('college', 'course', 'exam') NOT NULL,
    `entity_ids` JSON NOT NULL COMMENT 'JSON array of 2-4 UUIDs',
    `user_id` CHAR(36) NULL COMMENT 'Nullable for anonymous users',
    `session_id` VARCHAR(255) NULL COMMENT 'Anonymous tracking',
    `is_saved` BOOLEAN DEFAULT FALSE,
    `share_token` VARCHAR(255) UNIQUE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
