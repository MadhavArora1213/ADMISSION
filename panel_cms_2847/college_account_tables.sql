-- College/Institute accounts for self-service dashboard
CREATE TABLE IF NOT EXISTS `college_accounts` (
  `id` char(36) NOT NULL PRIMARY KEY,
  `college_id` char(36) DEFAULT NULL,
  `institute_type` enum('college','university','institute') NOT NULL DEFAULT 'college',
  `institute_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `temp_password` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','active','suspended') NOT NULL DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON update current_timestamp(),
  UNIQUE KEY `email` (`email`),
  KEY `college_id` (`college_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- College data submissions (pending admin approval)
CREATE TABLE IF NOT EXISTS `college_submissions` (
  `id` char(36) NOT NULL PRIMARY KEY,
  `account_id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `submission_type` enum('profile','courses','placements','cutoffs','seat_matrix','facilities','faqs') NOT NULL,
  `data_json` longtext NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON update current_timestamp(),
  KEY `account_id` (`account_id`),
  KEY `college_id` (`college_id`),
  KEY `status` (`status`),
  KEY `submission_type` (`submission_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
