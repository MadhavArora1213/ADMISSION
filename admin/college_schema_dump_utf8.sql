-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: admission
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `colleges`
--

DROP TABLE IF EXISTS `colleges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `colleges` (
  `id` char(36) NOT NULL,
  `name` varchar(300) DEFAULT NULL,
  `slug` varchar(300) DEFAULT NULL,
  `college_type` enum('govt','private','deemed','autonomous') DEFAULT NULL,
  `ownership` enum('public','private','trust','society') DEFAULT NULL,
  `status` enum('active','pending','archived') DEFAULT 'pending',
  `logo_url` varchar(255) DEFAULT NULL,
  `cover_image_url` varchar(255) DEFAULT NULL,
  `established_year` int(11) DEFAULT NULL,
  `autonomous` tinyint(1) DEFAULT 0,
  `ugc_approved` tinyint(1) DEFAULT 0,
  `aicte_approved` tinyint(1) DEFAULT 0,
  `total_students` int(11) DEFAULT NULL,
  `total_faculty` int(11) DEFAULT NULL,
  `campus_area_acres` float DEFAULT NULL,
  `city_id` int(11) DEFAULT NULL,
  `state_id` int(11) DEFAULT NULL,
  `university_id` char(36) DEFAULT NULL,
  `naac_grade` enum('A++','A+','A','B++','B+','B','C','None') DEFAULT 'None',
  `nirf_rank` int(11) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `featured_order` int(11) DEFAULT 0,
  `data_quality_score` tinyint(4) DEFAULT 0,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_by` char(36) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `duplicate_of` char(36) DEFAULT NULL,
  `import_batch_id` char(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_at` timestamp NULL DEFAULT NULL,
  `highlights_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`highlights_json`)),
  `accreditations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`accreditations`)),
  `rankings_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rankings_json`)),
  `awards_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`awards_json`)),
  `admission_process` text DEFAULT NULL,
  `accepted_exams` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`accepted_exams`)),
  `admission_start_date` date DEFAULT NULL,
  `admission_end_date` date DEFAULT NULL,
  `merit_based` tinyint(1) DEFAULT 0,
  `direct_admission` tinyint(1) DEFAULT 0,
  `management_quota_seats` int(11) DEFAULT 0,
  `nri_quota_seats` int(11) DEFAULT 0,
  `library` tinyint(1) DEFAULT 0,
  `sports_facilities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sports_facilities`)),
  `labs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`labs`)),
  `auditorium` tinyint(1) DEFAULT 0,
  `cafeteria` tinyint(1) DEFAULT 0,
  `wifi` tinyint(1) DEFAULT 0,
  `medical_facility` tinyint(1) DEFAULT 0,
  `transport` tinyint(1) DEFAULT 0,
  `hostel_available` tinyint(1) DEFAULT 0,
  `hostel_type` enum('boys','girls','both') DEFAULT NULL,
  `hostel_capacity` int(11) DEFAULT 0,
  `hostel_fee_annual` decimal(10,2) DEFAULT NULL,
  `mess_available` tinyint(1) DEFAULT 0,
  `mess_type` enum('veg','non-veg','both') DEFAULT NULL,
  `ac_available` tinyint(1) DEFAULT 0,
  `meta_title` varchar(70) DEFAULT NULL,
  `meta_description` varchar(160) DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `og_image_url` varchar(255) DEFAULT NULL,
  `canonical_url` varchar(255) DEFAULT NULL,
  `schema_markup` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`schema_markup`)),
  `publish_status` enum('draft','published') DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `noindex` tinyint(1) DEFAULT 0,
  `about_content_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`about_content_json`)),
  `ai_tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_tags`)),
  `ai_summary` text DEFAULT NULL,
  `embedding_vector` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`embedding_vector`)),
  `search_keywords` longtext DEFAULT NULL,
  `search_weight` float DEFAULT 1,
  `comparison_priority` int(11) DEFAULT 0,
  `highlight_features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`highlight_features`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `city_id` (`city_id`),
  KEY `state_id` (`state_id`),
  KEY `university_id` (`university_id`),
  KEY `verified_by` (`verified_by`),
  KEY `duplicate_of` (`duplicate_of`),
  CONSTRAINT `colleges_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `colleges_ibfk_2` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL,
  CONSTRAINT `colleges_ibfk_3` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `colleges_ibfk_4` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `colleges_ibfk_5` FOREIGN KEY (`duplicate_of`) REFERENCES `colleges` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `college_analytics`
--

DROP TABLE IF EXISTS `college_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `college_analytics` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `views` int(11) DEFAULT 0,
  `unique_visitors` int(11) DEFAULT 0,
  `lead_conversion_rate` float DEFAULT 0,
  `avg_time_spent` float DEFAULT 0,
  `saved_count` int(11) DEFAULT 0,
  `compared_count` int(11) DEFAULT 0,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `college_id` (`college_id`),
  CONSTRAINT `college_analytics_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `college_contacts`
--

DROP TABLE IF EXISTS `college_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `college_contacts` (
  `id` char(36) NOT NULL,
  `college_id` char(36) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `latitude` decimal(9,6) DEFAULT NULL,
  `longitude` decimal(9,6) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `google_maps_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `college_id` (`college_id`),
  CONSTRAINT `college_contacts_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `college_content_blocks`
--

DROP TABLE IF EXISTS `college_content_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `college_content_blocks` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `block_type` varchar(255) DEFAULT NULL,
  `block_name` varchar(255) DEFAULT NULL,
  `block_data_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`block_data_json`)),
  `sort_order` int(11) DEFAULT 0,
  `visibility_rules_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`visibility_rules_json`)),
  `status` enum('active','draft','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `college_id` (`college_id`),
  CONSTRAINT `college_content_blocks_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `college_courses`
--

DROP TABLE IF EXISTS `college_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `college_courses` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `course_id` char(36) NOT NULL,
  `duration_years` tinyint(4) DEFAULT NULL,
  `total_fee` decimal(10,2) DEFAULT NULL,
  `semester_fee` decimal(10,2) DEFAULT NULL,
  `annual_fee` decimal(10,2) DEFAULT NULL,
  `seats` int(11) DEFAULT NULL,
  `specializations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specializations`)),
  `fee_last_updated` date DEFAULT NULL,
  `mode` enum('full_time','part_time','online','distance') DEFAULT NULL,
  `study_type` enum('degree','diploma','certificate') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `college_id` (`college_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `college_courses_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE,
  CONSTRAINT `college_courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `college_cutoffs`
--

DROP TABLE IF EXISTS `college_cutoffs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `college_cutoffs` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `exam_id` char(36) NOT NULL,
  `course_id` char(36) NOT NULL,
  `cutoff_year` year(4) NOT NULL,
  `category` enum('General','OBC','SC','ST','EWS','PWD') NOT NULL,
  `round_number` tinyint(4) DEFAULT NULL,
  `opening_rank` int(11) DEFAULT NULL,
  `closing_rank` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `college_id` (`college_id`),
  KEY `exam_id` (`exam_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `college_cutoffs_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE,
  CONSTRAINT `college_cutoffs_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `college_cutoffs_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `college_facilities`
--

DROP TABLE IF EXISTS `college_facilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `college_facilities` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `facility_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `images_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images_json`)),
  `availability` enum('available','limited','unavailable') DEFAULT 'available',
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata_json`)),
  PRIMARY KEY (`id`),
  KEY `college_id` (`college_id`),
  CONSTRAINT `college_facilities_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `college_faculty`
--

DROP TABLE IF EXISTS `college_faculty`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `college_faculty` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `faculty_name` varchar(255) NOT NULL,
  `designation` varchar(150) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `research_papers` int(11) DEFAULT 0,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `google_scholar_url` varchar(255) DEFAULT NULL,
  `research_interests` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`research_interests`)),
  `patents_count` int(11) DEFAULT 0,
  `citations_count` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `college_id` (`college_id`),
  CONSTRAINT `college_faculty_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `college_faqs`
--

DROP TABLE IF EXISTS `college_faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `college_faqs` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `college_id` (`college_id`),
  CONSTRAINT `college_faqs_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `college_leads`
--

DROP TABLE IF EXISTS `college_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `college_leads` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `lead_source` varchar(255) DEFAULT NULL,
  `course_interest` char(36) DEFAULT NULL,
  `status` enum('new','contacted','qualified','converted','lost') DEFAULT 'new',
  `assigned_counsellor` char(36) DEFAULT NULL,
  `notes_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notes_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `college_id` (`college_id`),
  CONSTRAINT `college_leads_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `college_media`
--

DROP TABLE IF EXISTS `college_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `college_media` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `media_type` enum('image','video','document') NOT NULL,
  `sub_type` enum('campus','lab','hostel','event','tour','placement','brochure','prospectus','ranking') DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `sort_order` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `college_id` (`college_id`),
  CONSTRAINT `college_media_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `college_placements`
--

DROP TABLE IF EXISTS `college_placements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `college_placements` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `placement_year` year(4) NOT NULL,
  `avg_lpa` decimal(5,2) DEFAULT NULL,
  `highest_lpa` decimal(5,2) DEFAULT NULL,
  `median_lpa` decimal(5,2) DEFAULT NULL,
  `placed_pct` float DEFAULT NULL,
  `top_recruiters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`top_recruiters`)),
  `students_placed` int(11) DEFAULT NULL,
  `international_placements` int(11) DEFAULT 0,
  `placement_trends_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`placement_trends_json`)),
  `salary_distribution_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`salary_distribution_json`)),
  PRIMARY KEY (`id`),
  KEY `college_id` (`college_id`),
  CONSTRAINT `college_placements_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `college_reviews`
--

DROP TABLE IF EXISTS `college_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `college_reviews` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT NULL,
  `placements_rating` decimal(3,2) DEFAULT NULL,
  `faculty_rating` decimal(3,2) DEFAULT NULL,
  `hostel_rating` decimal(3,2) DEFAULT NULL,
  `campus_rating` decimal(3,2) DEFAULT NULL,
  `review_text` longtext DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `moderation_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `college_id` (`college_id`),
  CONSTRAINT `college_reviews_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `college_scholarships`
--

DROP TABLE IF EXISTS `college_scholarships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `college_scholarships` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `scholarship_name` varchar(255) NOT NULL,
  `scholarship_type` enum('merit','means','sports','reserved_category') NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `eligibility_criteria` text DEFAULT NULL,
  `renewable` tinyint(1) DEFAULT 0,
  `application_url` varchar(255) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `documents_required` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`documents_required`)),
  `renewal_conditions` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `college_id` (`college_id`),
  CONSTRAINT `college_scholarships_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hostel_rooms`
--

DROP TABLE IF EXISTS `hostel_rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hostel_rooms` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `room_type` varchar(255) DEFAULT NULL,
  `occupancy` int(11) DEFAULT NULL,
  `annual_fee` decimal(10,2) DEFAULT NULL,
  `facilities_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`facilities_json`)),
  `images_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images_json`)),
  PRIMARY KEY (`id`),
  KEY `college_id` (`college_id`),
  CONSTRAINT `hostel_rooms_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `custom_fields`
--

DROP TABLE IF EXISTS `custom_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custom_fields` (
  `id` char(36) NOT NULL,
  `module_name` varchar(255) DEFAULT NULL,
  `field_key` varchar(255) DEFAULT NULL,
  `field_label` varchar(255) DEFAULT NULL,
  `field_type` varchar(100) DEFAULT NULL,
  `validation_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_json`)),
  `options_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options_json`)),
  `settings_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings_json`)),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `entity_media`
--

DROP TABLE IF EXISTS `entity_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entity_media` (
  `id` char(36) NOT NULL,
  `entity_type` varchar(255) DEFAULT NULL,
  `entity_id` char(36) DEFAULT NULL,
  `media_asset_id` char(36) DEFAULT NULL,
  `module_name` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `media_asset_id` (`media_asset_id`),
  CONSTRAINT `entity_media_ibfk_1` FOREIGN KEY (`media_asset_id`) REFERENCES `media_assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `entity_versions`
--

DROP TABLE IF EXISTS `entity_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entity_versions` (
  `id` char(36) NOT NULL,
  `entity_type` varchar(255) DEFAULT NULL,
  `entity_id` char(36) DEFAULT NULL,
  `snapshot_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`snapshot_json`)),
  `changed_by` char(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `media_assets`
--

DROP TABLE IF EXISTS `media_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `media_assets` (
  `id` char(36) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `storage_provider` varchar(100) DEFAULT NULL,
  `file_url` text DEFAULT NULL,
  `thumbnail_url` text DEFAULT NULL,
  `alt_text` text DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata_json`)),
  `uploaded_by` char(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `approval_workflows`
--

DROP TABLE IF EXISTS `approval_workflows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `approval_workflows` (
  `id` char(36) NOT NULL,
  `entity_type` varchar(255) DEFAULT NULL,
  `entity_id` char(36) DEFAULT NULL,
  `submitted_by` char(36) DEFAULT NULL,
  `reviewed_by` char(36) DEFAULT NULL,
  `status` enum('pending','approved','rejected','changes_requested') DEFAULT 'pending',
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-28 10:29:16
