CREATE TABLE IF NOT EXISTS `applications` (
    `id` CHAR(36) PRIMARY KEY,
    `user_id` CHAR(36) NOT NULL,
    `college_id` CHAR(36) NOT NULL,
    `course_id` CHAR(36) NOT NULL,
    `application_number` VARCHAR(50) UNIQUE NOT NULL,
    `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('draft','submitted','under_review','waitlisted','admitted','rejected') DEFAULT 'draft',
    `payment_status` ENUM('pending','paid','refunded','waived') DEFAULT 'pending',
    `fee_paid` DECIMAL(10,2) DEFAULT 0.00,
    `transaction_id` VARCHAR(100) NULL,
    `counsellor_assigned` CHAR(36) NULL,
    `remarks` TEXT NULL,
    `interview_date` TIMESTAMP NULL,
    `offer_letter_url` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`college_id`) REFERENCES `colleges`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`counsellor_assigned`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `application_documents` (
    `id` CHAR(36) PRIMARY KEY,
    `application_id` CHAR(36) NOT NULL,
    `doc_type` ENUM('class10','class12','id_proof','photo','caste_cert','domicile') NOT NULL,
    `doc_url` VARCHAR(255) NOT NULL,
    `verification_status` ENUM('pending','verified','rejected') DEFAULT 'pending',
    `verified_by` CHAR(36) NULL,
    `rejection_reason` TEXT NULL,
    `verified_at` TIMESTAMP NULL,
    `ocr_extracted_data` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`application_id`) REFERENCES `applications`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `payments` (
    `id` CHAR(36) PRIMARY KEY,
    `application_id` CHAR(36) NOT NULL,
    `gateway` ENUM('razorpay','stripe','paytm','cashfree') NOT NULL,
    `gateway_txn_id` VARCHAR(100) NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` CHAR(3) DEFAULT 'INR',
    `payment_status` ENUM('initiated','success','failed','refunded') DEFAULT 'initiated',
    `paid_at` TIMESTAMP NULL,
    `refund_status` ENUM('none','requested','processed') DEFAULT 'none',
    `refund_amount` DECIMAL(10,2) DEFAULT 0.00,
    `invoice_url` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`application_id`) REFERENCES `applications`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
