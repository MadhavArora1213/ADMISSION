-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 17, 2026 at 02:28 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `admission`
--

-- --------------------------------------------------------

--
-- Table structure for table `ab_tests`
--

CREATE TABLE `ab_tests` (
  `id` int(11) NOT NULL,
  `test_name` varchar(255) NOT NULL,
  `variant_a` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variant_a`)),
  `variant_b` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variant_b`)),
  `metric` enum('ctr','conversion','lead_rate','time_on_page') NOT NULL,
  `winner` enum('a','b','inconclusive') DEFAULT NULL,
  `confidence_pct` float DEFAULT NULL,
  `status` enum('running','completed','paused') DEFAULT 'running',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `variant_a_views` int(11) DEFAULT 0,
  `variant_a_conv` int(11) DEFAULT 0,
  `variant_b_views` int(11) DEFAULT 0,
  `variant_b_conv` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `activity_type` enum('create','update','delete','login','flag') NOT NULL,
  `actor_id` char(36) DEFAULT NULL,
  `entity_type` enum('college','exam','article','review','lead') NOT NULL,
  `entity_id` char(36) NOT NULL,
  `meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

-- Seed data removed to avoid FK violation — user-uuid-1 does not exist in users table

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` char(36) NOT NULL,
  `actor_id` char(36) DEFAULT NULL,
  `action_type` varchar(255) DEFAULT NULL,
  `entity_type` varchar(255) DEFAULT NULL,
  `entity_id` char(36) DEFAULT NULL,
  `module_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `before_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`before_json`)),
  `after_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`after_json`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_alerts`
--

CREATE TABLE `admin_alerts` (
  `id` char(36) NOT NULL,
  `alert_type` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT NULL,
  `source_module` varchar(255) DEFAULT NULL,
  `entity_type` varchar(255) DEFAULT NULL,
  `entity_id` char(36) DEFAULT NULL,
  `status` enum('open','acknowledged','resolved','ignored') DEFAULT 'open',
  `assigned_to` char(36) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata_json`)),
  `resolved_by` char(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_widgets`
--

CREATE TABLE `admin_widgets` (
  `id` int(11) NOT NULL,
  `widget_type` enum('chart','table','kpi','map') NOT NULL,
  `widget_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`widget_config`)),
  `visible_to_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`visible_to_roles`)),
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ad_products`
--

CREATE TABLE `ad_products` (
  `id` int(11) NOT NULL,
  `college_id` char(36) NOT NULL,
  `ad_type` enum('banner','sponsored_listing','featured_badge','email_blast') NOT NULL,
  `ad_placement` varchar(255) DEFAULT NULL,
  `ad_start` date DEFAULT NULL,
  `ad_end` date DEFAULT NULL,
  `impressions` int(11) DEFAULT 0,
  `clicks` int(11) DEFAULT 0,
  `ctr` float GENERATED ALWAYS AS (if(`impressions` > 0,`clicks` / `impressions` * 100,0)) STORED,
  `media_url` varchar(255) DEFAULT NULL,
  `target_url` varchar(255) DEFAULT NULL,
  `cost_inr` decimal(10,2) DEFAULT NULL,
  `status` enum('active','paused','completed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_chat_sessions`
--

CREATE TABLE `ai_chat_sessions` (
  `id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `messages_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`messages_json`)),
  `lead_captured` tinyint(1) DEFAULT 0,
  `entity_context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`entity_context`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_config`
--

CREATE TABLE `ai_config` (
  `id` int(11) NOT NULL,
  `model_name` varchar(255) NOT NULL,
  `system_prompt` longtext DEFAULT NULL,
  `temperature` float DEFAULT 0.7,
  `max_tokens` int(11) DEFAULT 800,
  `fallback_response` text DEFAULT NULL,
  `session_memory` tinyint(1) DEFAULT 1,
  `escalation_keywords` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`escalation_keywords`)),
  `lead_capture_enabled` tinyint(1) DEFAULT 0,
  `whatsapp_bot_enabled` tinyint(1) DEFAULT 0,
  `response_language` enum('en','hi','en_hi_mix') DEFAULT 'en',
  `spam_threshold` float DEFAULT 0.8,
  `auto_approve_threshold` float DEFAULT 0.2,
  `auto_reject_threshold` float DEFAULT 0.9,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_config`
--

INSERT INTO `ai_config` (`id`, `model_name`, `system_prompt`, `temperature`, `max_tokens`, `fallback_response`, `session_memory`, `escalation_keywords`, `lead_capture_enabled`, `whatsapp_bot_enabled`, `response_language`, `spam_threshold`, `auto_approve_threshold`, `auto_reject_threshold`, `updated_at`) VALUES
(1, 'gpt-4o', 'You are a helpful admission counsellor for students.', 0.7, 800, '', 1, '[\"human\",\"help\",\"contact\",\"call\"]', 0, 0, 'en', 0.8, 0.2, 0.9, '2026-06-05 14:57:19');

-- --------------------------------------------------------

--
-- Table structure for table `ai_dashboard_insights`
--

CREATE TABLE `ai_dashboard_insights` (
  `id` char(36) NOT NULL,
  `insight_type` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `related_entity_type` varchar(255) DEFAULT NULL,
  `related_entity_id` char(36) DEFAULT NULL,
  `confidence_score` float DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_recommendations`
--

CREATE TABLE `ai_recommendations` (
  `id` int(11) NOT NULL,
  `algo_type` enum('collaborative','content','hybrid','llm_ranked') DEFAULT 'hybrid',
  `feature_weights` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`feature_weights`)),
  `user_profile_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`user_profile_fields`)),
  `recommendation_limit` tinyint(4) DEFAULT 10,
  `personalization_enabled` tinyint(1) DEFAULT 1,
  `model_version` varchar(50) DEFAULT NULL,
  `retrain_schedule` varchar(50) DEFAULT NULL,
  `ab_test_variant` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_recommendations`
--

INSERT INTO `ai_recommendations` (`id`, `algo_type`, `feature_weights`, `user_profile_fields`, `recommendation_limit`, `personalization_enabled`, `model_version`, `retrain_schedule`, `ab_test_variant`, `updated_at`) VALUES
(1, 'hybrid', '{}', '[]', 10, 1, 'v1.0.0', '0 0 * * *', '', '2026-06-05 14:59:00');

-- --------------------------------------------------------

--
-- Table structure for table `alert_rules`
--

CREATE TABLE `alert_rules` (
  `id` char(36) NOT NULL,
  `rule_name` varchar(255) DEFAULT NULL,
  `module_name` varchar(255) DEFAULT NULL,
  `condition_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`condition_json`)),
  `severity` enum('low','medium','high','critical') DEFAULT NULL,
  `notification_channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_channels`)),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `analytics_reports`
--

CREATE TABLE `analytics_reports` (
  `id` int(11) NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `report_format` enum('pdf','csv','xlsx') NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `report_url` varchar(255) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE `answers` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `question_id` char(36) NOT NULL,
  `answer_text` text NOT NULL,
  `answered_by` char(36) NOT NULL,
  `is_expert_answer` tinyint(1) DEFAULT 0,
  `is_verified_alumnus` tinyint(1) DEFAULT 0,
  `upvotes` int(11) DEFAULT 0,
  `is_accepted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--

CREATE TABLE `api_keys` (
  `id` varchar(36) NOT NULL,
  `api_key_name` varchar(150) NOT NULL,
  `api_key_hash` varchar(255) NOT NULL,
  `api_scope` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`api_scope`)),
  `api_expires_at` timestamp NULL DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` varchar(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `api_keys`
--

INSERT INTO `api_keys` (`id`, `api_key_name`, `api_key_hash`, `api_scope`, `api_expires_at`, `last_used_at`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
('f9759e69-60eb-11f1-b290-a0510b1a7448', 'fg', '$2y$10$ieDYRReCLZWixgpy2UQdouzFOYAm8ydZ7XkL4pQ7SpEHFj5jrSDBa', '[]', NULL, NULL, 1, 'a85f4bf4-5c3b-11f1-a48e-c8f7507a8de6', '2026-06-05 14:36:40', '2026-06-05 14:36:40');

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `course_id` char(36) NOT NULL,
  `application_number` varchar(50) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('draft','submitted','under_review','waitlisted','admitted','rejected') DEFAULT 'draft',
  `payment_status` enum('pending','paid','refunded','waived') DEFAULT 'pending',
  `fee_paid` decimal(10,2) DEFAULT 0.00,
  `transaction_id` varchar(100) DEFAULT NULL,
  `counsellor_assigned` char(36) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `interview_date` timestamp NULL DEFAULT NULL,
  `offer_letter_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_documents`
--

CREATE TABLE `application_documents` (
  `id` char(36) NOT NULL,
  `application_id` char(36) NOT NULL,
  `doc_type` enum('class10','class12','id_proof','photo','caste_cert','domicile') NOT NULL,
  `doc_url` varchar(255) NOT NULL,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_by` char(36) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `ocr_extracted_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ocr_extracted_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `id` char(36) NOT NULL,
  `article_title` varchar(500) NOT NULL,
  `article_slug` varchar(500) NOT NULL,
  `article_type` enum('blog','news','guide','exam_update','opinion','ranking') DEFAULT 'blog',
  `content_body` longtext DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image_url` varchar(255) DEFAULT NULL,
  `featured_image_alt` varchar(255) DEFAULT NULL,
  `author_id` char(36) DEFAULT NULL,
  `custom_author_name` varchar(255) DEFAULT NULL,
  `editor_id` char(36) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `status` enum('draft','pending_review','published','archived') DEFAULT 'draft',
  `publish_at` timestamp NULL DEFAULT NULL,
  `reading_time_mins` tinyint(4) DEFAULT NULL,
  `view_count` int(11) DEFAULT 0,
  `share_count` int(11) DEFAULT 0,
  `draft_saved_at` timestamp NULL DEFAULT NULL,
  `auto_save_version` int(11) DEFAULT 1,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `unpublish_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `articles`
--

INSERT INTO `articles` (`id`, `article_title`, `article_slug`, `article_type`, `content_body`, `excerpt`, `featured_image_url`, `featured_image_alt`, `author_id`, `custom_author_name`, `editor_id`, `category_id`, `tags`, `status`, `publish_at`, `reading_time_mins`, `view_count`, `share_count`, `draft_saved_at`, `auto_save_version`, `scheduled_at`, `unpublish_at`, `created_at`, `updated_at`) VALUES
('', 'Top 10 Engineering Colleges in India for 2026', 'top-10-engineering-colleges-2026', 'ranking', '<p>Engineering remains one of the most sought-after career paths in India. In 2026, the rankings have seen a significant shift, with several new IITs and private institutions moving up the ladder...</p>', 'Discover the top-ranked engineering institutions in India based on placement records, faculty, and research output for the year 2026.', 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=800&q=80', NULL, NULL, 'Madhav Arora', NULL, 2, NULL, 'published', '2026-06-14 15:53:09', NULL, 0, 0, NULL, 1, NULL, NULL, '2026-06-14 15:53:09', '2026-06-14 15:53:09'),
('5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', 'Top 10 Engineering Colleges in India for 2026', 'top-10-engineering-colleges-2026-v2', 'ranking', '<p>Engineering remains one of the most sought-after career paths in India. In 2026, the rankings have seen a significant shift, with several new IITs and private institutions moving up the ladder...</p>', 'Discover the top-ranked engineering institutions in India based on placement records, faculty, and research output for the year 2026.', 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=800&q=80', NULL, NULL, 'Madhav Arora', NULL, 2, NULL, 'published', '2026-06-14 15:55:04', NULL, 17, 0, NULL, 1, NULL, NULL, '2026-06-14 15:55:04', '2026-06-17 11:44:46'),
('6443a93e-56d5-488c-b02b-a75bfa1a0758', 'How to Choose the Right College: A Comprehensive Guide', 'how-to-choose-the-right-college-v2', 'guide', '<p>Choosing the right college is a life-changing decision. It is not just about the brand name; it is about finding a place that aligns with your personal and professional aspirations. Let us dive into the key factors you must consider...</p>', 'Feeling overwhelmed by college options? Here is a step-by-step guide to evaluating colleges based on your career goals, budget, and location preferences.', 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=800&q=80', NULL, NULL, 'Career Counselor', NULL, 2, NULL, 'published', '2026-06-14 15:55:04', NULL, 3, 0, NULL, 1, NULL, NULL, '2026-06-14 15:55:04', '2026-06-15 04:24:39'),
('8c302e7f-f5b8-436d-b037-3e54d6a086cb', 'Why Liberal Arts Education is Gaining Popularity in India', 'liberal-arts-education-popularity-v2', 'blog', '<p>The traditional mindset of \"Engineering or Medical\" is slowly changing in India. A liberal arts education offers critical thinking, adaptability, and a broad worldview, which modern employers highly value...</p>', 'More students are moving away from traditional STEM fields to explore Liberal Arts. What is driving this shift, and what are the career prospects?', 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800&q=80', NULL, NULL, 'Guest Blogger', NULL, 2, NULL, 'published', '2026-06-14 15:55:04', NULL, 0, 0, NULL, 1, NULL, NULL, '2026-06-14 15:55:04', '2026-06-14 15:55:04'),
('9a915909-6c0d-4dc1-8799-3811226adb9e', 'My Opinion: Are Entrance Exams Putting Too Much Pressure on Students?', 'opinion-entrance-exams-pressure-v2', 'opinion', '<p>Every year, millions of students appear for competitive exams like JEE, NEET, and CUET. While these exams are designed to be a fair metric for selection, the sheer pressure and the booming coaching industry are creating an unhealthy environment...</p>', 'With rising competition and coaching culture, entrance exams are taking a toll on student mental health. It is time we rethink our evaluation methods.', 'https://images.unsplash.com/photo-1513258496099-48168024aec0?w=800&q=80', NULL, NULL, 'Student Voice', NULL, 2, NULL, 'published', '2026-06-14 15:55:04', NULL, 0, 0, NULL, 1, NULL, NULL, '2026-06-14 15:55:04', '2026-06-14 15:55:04'),
('bcbf97c5-f71a-47db-b507-1fd618d370ef', 'Delhi University Introduces New B.Tech Programs', 'du-introduces-new-btech-programs-v2', 'news', '<p>Delhi University (DU) is expanding its technical education footprint by introducing B.Tech programs in Computer Science, Electronics, and Electrical Engineering. Admissions will be based on JEE Main scores...</p>', 'In a major academic expansion, Delhi University has announced the launch of three new B.Tech programs starting this academic session. Here is what you need to know.', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', NULL, NULL, 'Campus Reporter', NULL, 2, NULL, 'published', '2026-06-14 15:55:04', NULL, 0, 0, NULL, 1, NULL, NULL, '2026-06-14 15:55:04', '2026-06-14 15:55:04'),
('c83156f5-2dea-4676-b04e-020d84e24ee8', 'JEE Main 2026 Dates Announced: Check Registration Details', 'jee-main-2026-dates-announced-v2', 'exam_update', '<p>Attention engineering aspirants! The NTA has officially announced the exam dates for JEE Main 2026. The exam will be conducted in two sessions, as usual. Students are advised to keep their documents ready for the registration process...</p>', 'The National Testing Agency (NTA) has finally released the schedule for JEE Main 2026. Registrations will commence from the first week of November.', 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=800&q=80', NULL, NULL, 'Education Desk', NULL, 2, NULL, 'published', '2026-06-14 15:55:04', NULL, 0, 0, NULL, 1, NULL, NULL, '2026-06-14 15:55:04', '2026-06-14 15:55:04'),
('ca435e52-4315-46eb-b0e5-ab09c1e0fb72', 'ghrghghrbv bcgchgvb', 'ghrghghr', 'blog', '<p>gbgb b fv&nbsp; &nbsp; fv r rgbet g rg r&nbsp;</p>', 'v vf vd dv vd v vxdf huwdvhfwbucvhdb ciuwb fhbrjic j', '/ADMISSION/uploads/article_featured_1780658855_6a22b2a7162a4.jpg', 'bgb', '8b0478e7-602f-11f1-9ea0-a0510b1a7448', NULL, '8b0478e7-602f-11f1-9ea0-a0510b1a7448', 2, '[2]', 'published', '2026-06-05 11:25:00', 42, 5, 0, NULL, 1, '2026-06-12 11:36:00', '2026-06-12 11:36:00', '2026-06-05 11:27:35', '2026-06-14 16:59:14');

-- --------------------------------------------------------

--
-- Table structure for table `article_categories`
--

CREATE TABLE `article_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `category_slug` varchar(255) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `article_categories`
--

INSERT INTO `article_categories` (`id`, `category_name`, `category_slug`, `parent_id`, `sort_order`, `created_at`) VALUES
(2, 'ytrdfgtryhg', 'ytrdfgtryhg', NULL, 0, '2026-06-05 10:44:20');

-- --------------------------------------------------------

--
-- Table structure for table `article_comments`
--

CREATE TABLE `article_comments` (
  `id` int(11) NOT NULL,
  `article_id` char(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `article_comments`
--

INSERT INTO `article_comments` (`id`, `article_id`, `user_id`, `comment_text`, `created_at`) VALUES
(1, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', 'user-1234-uuid', 'hlo', '2026-06-15 04:15:11'),
(2, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', '64e20c70-d8d7-402f-a700-53c759a659d4', 'hlo', '2026-06-17 11:44:46');

-- --------------------------------------------------------

--
-- Table structure for table `article_revisions`
--

CREATE TABLE `article_revisions` (
  `id` char(36) NOT NULL,
  `article_id` char(36) NOT NULL,
  `version` int(11) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `content_snapshot` longtext DEFAULT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `article_revisions`
--

INSERT INTO `article_revisions` (`id`, `article_id`, `version`, `user_id`, `content_snapshot`, `saved_at`) VALUES
('9e462534-0e1b-4beb-85b0-8532a6953c37', 'ca435e52-4315-46eb-b0e5-ab09c1e0fb72', 1, 'a85f4bf4-5c3b-11f1-a48e-c8f7507a8de6', '<p>gbgb b fv&nbsp; &nbsp; fv r rgbet g rg r&nbsp;</p>', '2026-06-05 11:39:16');

-- --------------------------------------------------------

--
-- Table structure for table `article_tags`
--

CREATE TABLE `article_tags` (
  `id` int(11) NOT NULL,
  `article_id` char(36) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `article_tags`
--

INSERT INTO `article_tags` (`id`, `article_id`, `tag_id`) VALUES
(3, 'ca435e52-4315-46eb-b0e5-ab09c1e0fb72', 2);

-- --------------------------------------------------------

--
-- Table structure for table `audience_segments`
--

CREATE TABLE `audience_segments` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `segment_name` varchar(255) NOT NULL,
  `filters_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`filters_json`)),
  `user_count` int(11) DEFAULT 0,
  `refresh_schedule` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audience_segments`
--

INSERT INTO `audience_segments` (`id`, `segment_name`, `filters_json`, `user_count`, `refresh_schedule`, `created_at`, `updated_at`) VALUES
('419ff199-60f4-11f1-b290-a0510b1a7448', 'hjhf', '{\"bvhjb\":\"vhj\"}', 0, '0 0 * * *', '2026-06-05 15:35:57', '2026-06-05 15:35:57');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` varchar(36) NOT NULL,
  `user_id` varchar(36) DEFAULT NULL,
  `audit_action` enum('create','update','delete','login','export') NOT NULL,
  `entity_type` varchar(100) NOT NULL,
  `entity_id` varchar(36) DEFAULT NULL,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_value`)),
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_value`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blacklisted_entities`
--

CREATE TABLE `blacklisted_entities` (
  `id` varchar(36) NOT NULL,
  `entity_type` enum('ip','user','email','device','phone') NOT NULL,
  `entity_value` varchar(255) NOT NULL,
  `reason` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `expires_at` timestamp NULL DEFAULT NULL,
  `added_by` varchar(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `calculator_config`
--

CREATE TABLE `calculator_config` (
  `id` int(11) NOT NULL DEFAULT 1,
  `loan_providers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`loan_providers`)),
  `default_interest_rate_pct` float DEFAULT 10.5,
  `max_tenure_months` int(11) DEFAULT 84,
  `min_loan_amount` decimal(10,2) DEFAULT 0.00,
  `max_loan_amount` decimal(10,2) DEFAULT 5000000.00,
  `processing_fee_pct` float DEFAULT 1,
  `tax_rate` float DEFAULT 0.18,
  `is_active` tinyint(1) DEFAULT 1,
  `affiliate_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`affiliate_links`)),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `calculator_config`
--

INSERT INTO `calculator_config` (`id`, `loan_providers`, `default_interest_rate_pct`, `max_tenure_months`, `min_loan_amount`, `max_loan_amount`, `processing_fee_pct`, `tax_rate`, `is_active`, `affiliate_links`, `updated_at`) VALUES
(1, '[{\"name\":\"gd\",\"interest_rate_range\":\"4\",\"max_tenure\":6}]', 10.5, 84, 0.00, 5000000.00, 1, 0.18, 1, '[{\"provider\":\"hnhfh\",\"url\":\"https:\\/\\/github.com\",\"cta_label\":\"bgyxhgf\"}]', '2026-06-05 14:32:09');

-- --------------------------------------------------------

--
-- Table structure for table `calculator_sessions`
--

CREATE TABLE `calculator_sessions` (
  `id` varchar(36) NOT NULL,
  `session_token` varchar(255) DEFAULT NULL,
  `user_id` varchar(36) DEFAULT NULL,
  `college_id` varchar(36) DEFAULT NULL,
  `fee_amount` decimal(10,2) NOT NULL,
  `down_payment` decimal(10,2) DEFAULT 0.00,
  `loan_amount` decimal(10,2) NOT NULL,
  `tenure_months` int(11) NOT NULL,
  `interest_rate` float NOT NULL,
  `emi_results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`emi_results`)),
  `provider_compared` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`provider_compared`)),
  `lead_captured_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chart_configurations`
--

CREATE TABLE `chart_configurations` (
  `id` char(36) NOT NULL,
  `chart_name` varchar(255) DEFAULT NULL,
  `chart_type` enum('line','bar','area','pie','donut','funnel','heatmap') DEFAULT NULL,
  `query_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`query_json`)),
  `visualization_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`visualization_json`)),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `state_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `state_id`, `name`) VALUES
(1, 1, 'Anantapur'),
(2, 1, 'Chittoor'),
(3, 1, 'East Godavari'),
(4, 1, 'Guntur'),
(5, 1, 'Krishna'),
(6, 1, 'Kurnool'),
(7, 1, 'Nellore'),
(8, 1, 'Prakasam'),
(9, 1, 'Srikakulam'),
(10, 1, 'Visakhapatnam'),
(11, 1, 'Vizianagaram'),
(12, 1, 'West Godavari'),
(13, 1, 'YSR Kadapa'),
(14, 2, 'Tawang'),
(15, 2, 'West Kameng'),
(16, 2, 'East Kameng'),
(17, 2, 'Papum Pare'),
(18, 2, 'Kurung Kumey'),
(19, 2, 'Kra Daadi'),
(20, 2, 'Lower Subansiri'),
(21, 2, 'Upper Subansiri'),
(22, 2, 'West Siang'),
(23, 2, 'East Siang'),
(24, 2, 'Siang'),
(25, 2, 'Upper Siang'),
(26, 2, 'Lower Siang'),
(27, 2, 'Lower Dibang Valley'),
(28, 2, 'Dibang Valley'),
(29, 2, 'Anjaw'),
(30, 2, 'Lohit'),
(31, 2, 'Namsai'),
(32, 2, 'Changlang'),
(33, 2, 'Tirap'),
(34, 2, 'Longding'),
(35, 3, 'Baksa'),
(36, 3, 'Barpeta'),
(37, 3, 'Biswanath'),
(38, 3, 'Bongaigaon'),
(39, 3, 'Cachar'),
(40, 3, 'Charaideo'),
(41, 3, 'Chirang'),
(42, 3, 'Darrang'),
(43, 3, 'Dhemaji'),
(44, 3, 'Dhubri'),
(45, 3, 'Dibrugarh'),
(46, 3, 'Goalpara'),
(47, 3, 'Golaghat'),
(48, 3, 'Hailakandi'),
(49, 3, 'Hojai'),
(50, 3, 'Jorhat'),
(51, 3, 'Kamrup Metropolitan'),
(52, 3, 'Kamrup'),
(53, 3, 'Karbi Anglong'),
(54, 3, 'Karimganj'),
(55, 3, 'Kokrajhar'),
(56, 3, 'Lakhimpur'),
(57, 3, 'Majuli'),
(58, 3, 'Morigaon'),
(59, 3, 'Nagaon'),
(60, 3, 'Nalbari'),
(61, 3, 'Dima Hasao'),
(62, 3, 'Sivasagar'),
(63, 3, 'Sonitpur'),
(64, 3, 'South Salmara-Mankachar'),
(65, 3, 'Tinsukia'),
(66, 3, 'Udalguri'),
(67, 3, 'West Karbi Anglong'),
(68, 4, 'Araria'),
(69, 4, 'Arwal'),
(70, 4, 'Aurangabad'),
(71, 4, 'Banka'),
(72, 4, 'Begusarai'),
(73, 4, 'Bhagalpur'),
(74, 4, 'Bhojpur'),
(75, 4, 'Buxar'),
(76, 4, 'Darbhanga'),
(77, 4, 'East Champaran (Motihari)'),
(78, 4, 'Gaya'),
(79, 4, 'Gopalganj'),
(80, 4, 'Jamui'),
(81, 4, 'Jehanabad'),
(82, 4, 'Kaimur (Bhabua)'),
(83, 4, 'Katihar'),
(84, 4, 'Khagaria'),
(85, 4, 'Kishanganj'),
(86, 4, 'Lakhisarai'),
(87, 4, 'Madhepura'),
(88, 4, 'Madhubani'),
(89, 4, 'Munger (Monghyr)'),
(90, 4, 'Muzaffarpur'),
(91, 4, 'Nalanda'),
(92, 4, 'Nawada'),
(93, 4, 'Patna'),
(94, 4, 'Purnia (Purnea)'),
(95, 4, 'Rohtas'),
(96, 4, 'Saharsa'),
(97, 4, 'Samastipur'),
(98, 4, 'Saran'),
(99, 4, 'Sheikhpura'),
(100, 4, 'Sheohar'),
(101, 4, 'Sitamarhi'),
(102, 4, 'Siwan'),
(103, 4, 'Supaul'),
(104, 4, 'Vaishali'),
(105, 4, 'West Champaran'),
(106, 5, 'Chandigarh'),
(107, 6, 'Balod'),
(108, 6, 'Baloda Bazar'),
(109, 6, 'Balrampur'),
(110, 6, 'Bastar'),
(111, 6, 'Bemetara'),
(112, 6, 'Bijapur'),
(113, 6, 'Bilaspur'),
(114, 6, 'Dantewada (South Bastar)'),
(115, 6, 'Dhamtari'),
(116, 6, 'Durg'),
(117, 6, 'Gariyaband'),
(118, 6, 'Janjgir-Champa'),
(119, 6, 'Jashpur'),
(120, 6, 'Kabirdham (Kawardha)'),
(121, 6, 'Kanker (North Bastar)'),
(122, 6, 'Kondagaon'),
(123, 6, 'Korba'),
(124, 6, 'Korea (Koriya)'),
(125, 6, 'Mahasamund'),
(126, 6, 'Mungeli'),
(127, 6, 'Narayanpur'),
(128, 6, 'Raigarh'),
(129, 6, 'Raipur'),
(130, 6, 'Rajnandgaon'),
(131, 6, 'Sukma'),
(132, 6, 'Surajpur  '),
(133, 6, 'Surguja'),
(134, 7, 'Dadra & Nagar Haveli'),
(135, 8, 'Daman'),
(136, 8, 'Diu'),
(137, 9, 'Central Delhi'),
(138, 9, 'East Delhi'),
(139, 9, 'New Delhi'),
(140, 9, 'North Delhi'),
(141, 9, 'North East  Delhi'),
(142, 9, 'North West  Delhi'),
(143, 9, 'Shahdara'),
(144, 9, 'South Delhi'),
(145, 9, 'South East Delhi'),
(146, 9, 'South West  Delhi'),
(147, 9, 'West Delhi'),
(148, 10, 'North Goa'),
(149, 10, 'South Goa'),
(150, 11, 'Ahmedabad'),
(151, 11, 'Amreli'),
(152, 11, 'Anand'),
(153, 11, 'Aravalli'),
(154, 11, 'Banaskantha (Palanpur)'),
(155, 11, 'Bharuch'),
(156, 11, 'Bhavnagar'),
(157, 11, 'Botad'),
(158, 11, 'Chhota Udepur'),
(159, 11, 'Dahod'),
(160, 11, 'Dangs (Ahwa)'),
(161, 11, 'Devbhoomi Dwarka'),
(162, 11, 'Gandhinagar'),
(163, 11, 'Gir Somnath'),
(164, 11, 'Jamnagar'),
(165, 11, 'Junagadh'),
(166, 11, 'Kachchh'),
(167, 11, 'Kheda (Nadiad)'),
(168, 11, 'Mahisagar'),
(169, 11, 'Mehsana'),
(170, 11, 'Morbi'),
(171, 11, 'Narmada (Rajpipla)'),
(172, 11, 'Navsari'),
(173, 11, 'Panchmahal (Godhra)'),
(174, 11, 'Patan'),
(175, 11, 'Porbandar'),
(176, 11, 'Rajkot'),
(177, 11, 'Sabarkantha (Himmatnagar)'),
(178, 11, 'Surat'),
(179, 11, 'Surendranagar'),
(180, 11, 'Tapi (Vyara)'),
(181, 11, 'Vadodara'),
(182, 11, 'Valsad'),
(183, 12, 'Ambala'),
(184, 12, 'Bhiwani'),
(185, 12, 'Charkhi Dadri'),
(186, 12, 'Faridabad'),
(187, 12, 'Fatehabad'),
(188, 12, 'Gurgaon'),
(189, 12, 'Hisar'),
(190, 12, 'Jhajjar'),
(191, 12, 'Jind'),
(192, 12, 'Kaithal'),
(193, 12, 'Karnal'),
(194, 12, 'Kurukshetra'),
(195, 12, 'Mahendragarh'),
(196, 12, 'Mewat'),
(197, 12, 'Palwal'),
(198, 12, 'Panchkula'),
(199, 12, 'Panipat'),
(200, 12, 'Rewari'),
(201, 12, 'Rohtak'),
(202, 12, 'Sirsa'),
(203, 12, 'Sonipat'),
(204, 12, 'Yamunanagar'),
(205, 13, 'Bilaspur'),
(206, 13, 'Chamba'),
(207, 13, 'Hamirpur'),
(208, 13, 'Kangra'),
(209, 13, 'Kinnaur'),
(210, 13, 'Kullu'),
(211, 13, 'Lahaul &amp; Spiti'),
(212, 13, 'Mandi'),
(213, 13, 'Shimla'),
(214, 13, 'Sirmaur (Sirmour)'),
(215, 13, 'Solan'),
(216, 13, 'Una'),
(217, 14, 'Anantnag'),
(218, 14, 'Bandipore'),
(219, 14, 'Baramulla'),
(220, 14, 'Budgam'),
(221, 14, 'Doda'),
(222, 14, 'Ganderbal'),
(223, 14, 'Jammu'),
(224, 14, 'Kargil'),
(225, 14, 'Kathua'),
(226, 14, 'Kishtwar'),
(227, 14, 'Kulgam'),
(228, 14, 'Kupwara'),
(229, 14, 'Leh'),
(230, 14, 'Poonch'),
(231, 14, 'Pulwama'),
(232, 14, 'Rajouri'),
(233, 14, 'Ramban'),
(234, 14, 'Reasi'),
(235, 14, 'Samba'),
(236, 14, 'Shopian'),
(237, 14, 'Srinagar'),
(238, 14, 'Udhampur'),
(239, 15, 'Bokaro'),
(240, 15, 'Chatra'),
(241, 15, 'Deoghar'),
(242, 15, 'Dhanbad'),
(243, 15, 'Dumka'),
(244, 15, 'East Singhbhum'),
(245, 15, 'Garhwa'),
(246, 15, 'Giridih'),
(247, 15, 'Godda'),
(248, 15, 'Gumla'),
(249, 15, 'Hazaribag'),
(250, 15, 'Jamtara'),
(251, 15, 'Khunti'),
(252, 15, 'Koderma'),
(253, 15, 'Latehar'),
(254, 15, 'Lohardaga'),
(255, 15, 'Pakur'),
(256, 15, 'Palamu'),
(257, 15, 'Ramgarh'),
(258, 15, 'Ranchi'),
(259, 15, 'Sahibganj'),
(260, 15, 'Seraikela-Kharsawan'),
(261, 15, 'Simdega'),
(262, 15, 'West Singhbhum'),
(263, 16, 'Bagalkot'),
(264, 16, 'Ballari (Bellary)'),
(265, 16, 'Belagavi (Belgaum)'),
(266, 16, 'Bengaluru (Bangalore) Rural'),
(267, 16, 'Bengaluru (Bangalore) Urban'),
(268, 16, 'Bidar'),
(269, 16, 'Chamarajanagar'),
(270, 16, 'Chikballapur'),
(271, 16, 'Chikkamagaluru (Chikmagalur)'),
(272, 16, 'Chitradurga'),
(273, 16, 'Dakshina Kannada'),
(274, 16, 'Davangere'),
(275, 16, 'Dharwad'),
(276, 16, 'Gadag'),
(277, 16, 'Hassan'),
(278, 16, 'Haveri'),
(279, 16, 'Kalaburagi (Gulbarga)'),
(280, 16, 'Kodagu'),
(281, 16, 'Kolar'),
(282, 16, 'Koppal'),
(283, 16, 'Mandya'),
(284, 16, 'Mysuru (Mysore)'),
(285, 16, 'Raichur'),
(286, 16, 'Ramanagara'),
(287, 16, 'Shivamogga (Shimoga)'),
(288, 16, 'Tumakuru (Tumkur)'),
(289, 16, 'Udupi'),
(290, 16, 'Uttara Kannada (Karwar)'),
(291, 16, 'Vijayapura (Bijapur)'),
(292, 16, 'Yadgir'),
(293, 17, 'Alappuzha'),
(294, 17, 'Ernakulam'),
(295, 17, 'Idukki'),
(296, 17, 'Kannur'),
(297, 17, 'Kasaragod'),
(298, 17, 'Kollam'),
(299, 17, 'Kottayam'),
(300, 17, 'Kozhikode'),
(301, 17, 'Malappuram'),
(302, 17, 'Palakkad'),
(303, 17, 'Pathanamthitta'),
(304, 17, 'Thiruvananthapuram'),
(305, 17, 'Thrissur'),
(306, 17, 'Wayanad'),
(307, 18, 'Agatti'),
(308, 18, 'Amini'),
(309, 18, 'Androth'),
(310, 18, 'Bithra'),
(311, 18, 'Chethlath'),
(312, 18, 'Kavaratti'),
(313, 18, 'Kadmath'),
(314, 18, 'Kalpeni'),
(315, 18, 'Kilthan'),
(316, 18, 'Minicoy'),
(317, 19, 'Agar Malwa'),
(318, 19, 'Alirajpur'),
(319, 19, 'Anuppur'),
(320, 19, 'Ashoknagar'),
(321, 19, 'Balaghat'),
(322, 19, 'Barwani'),
(323, 19, 'Betul'),
(324, 19, 'Bhind'),
(325, 19, 'Bhopal'),
(326, 19, 'Burhanpur'),
(327, 19, 'Chhatarpur'),
(328, 19, 'Chhindwara'),
(329, 19, 'Damoh'),
(330, 19, 'Datia'),
(331, 19, 'Dewas'),
(332, 19, 'Dhar'),
(333, 19, 'Dindori'),
(334, 19, 'Guna'),
(335, 19, 'Gwalior'),
(336, 19, 'Harda'),
(337, 19, 'Hoshangabad'),
(338, 19, 'Indore'),
(339, 19, 'Jabalpur'),
(340, 19, 'Jhabua'),
(341, 19, 'Katni'),
(342, 19, 'Khandwa'),
(343, 19, 'Khargone'),
(344, 19, 'Mandla'),
(345, 19, 'Mandsaur'),
(346, 19, 'Morena'),
(347, 19, 'Narsinghpur'),
(348, 19, 'Neemuch'),
(349, 19, 'Panna'),
(350, 19, 'Raisen'),
(351, 19, 'Rajgarh'),
(352, 19, 'Ratlam'),
(353, 19, 'Rewa'),
(354, 19, 'Sagar'),
(355, 19, 'Satna'),
(356, 19, 'Sehore'),
(357, 19, 'Seoni'),
(358, 19, 'Shahdol'),
(359, 19, 'Shajapur'),
(360, 19, 'Sheopur'),
(361, 19, 'Shivpuri'),
(362, 19, 'Sidhi'),
(363, 19, 'Singrauli'),
(364, 19, 'Tikamgarh'),
(365, 19, 'Ujjain'),
(366, 19, 'Umaria'),
(367, 19, 'Vidisha'),
(368, 20, 'Ahmednagar'),
(369, 20, 'Akola'),
(370, 20, 'Amravati'),
(371, 20, 'Aurangabad'),
(372, 20, 'Beed'),
(373, 20, 'Bhandara'),
(374, 20, 'Buldhana'),
(375, 20, 'Chandrapur'),
(376, 20, 'Dhule'),
(377, 20, 'Gadchiroli'),
(378, 20, 'Gondia'),
(379, 20, 'Hingoli'),
(380, 20, 'Jalgaon'),
(381, 20, 'Jalna'),
(382, 20, 'Kolhapur'),
(383, 20, 'Latur'),
(384, 20, 'Mumbai City'),
(385, 20, 'Mumbai Suburban'),
(386, 20, 'Nagpur'),
(387, 20, 'Nanded'),
(388, 20, 'Nandurbar'),
(389, 20, 'Nashik'),
(390, 20, 'Osmanabad'),
(391, 20, 'Palghar'),
(392, 20, 'Parbhani'),
(393, 20, 'Pune'),
(394, 20, 'Raigad'),
(395, 20, 'Ratnagiri'),
(396, 20, 'Sangli'),
(397, 20, 'Satara'),
(398, 20, 'Sindhudurg'),
(399, 20, 'Solapur'),
(400, 20, 'Thane'),
(401, 20, 'Wardha'),
(402, 20, 'Washim'),
(403, 20, 'Yavatmal'),
(404, 21, 'Bishnupur'),
(405, 21, 'Chandel'),
(406, 21, 'Churachandpur'),
(407, 21, 'Imphal East'),
(408, 21, 'Imphal West'),
(409, 21, 'Jiribam'),
(410, 21, 'Kakching'),
(411, 21, 'Kamjong'),
(412, 21, 'Kangpokpi'),
(413, 21, 'Noney'),
(414, 21, 'Pherzawl'),
(415, 21, 'Senapati'),
(416, 21, 'Tamenglong'),
(417, 21, 'Tengnoupal'),
(418, 21, 'Thoubal'),
(419, 21, 'Ukhrul'),
(420, 22, 'East Garo Hills'),
(421, 22, 'East Jaintia Hills'),
(422, 22, 'East Khasi Hills'),
(423, 22, 'North Garo Hills'),
(424, 22, 'Ri Bhoi'),
(425, 22, 'South Garo Hills'),
(426, 22, 'South West Garo Hills '),
(427, 22, 'South West Khasi Hills'),
(428, 22, 'West Garo Hills'),
(429, 22, 'West Jaintia Hills'),
(430, 22, 'West Khasi Hills'),
(431, 23, 'Aizawl'),
(432, 23, 'Champhai'),
(433, 23, 'Kolasib'),
(434, 23, 'Lawngtlai'),
(435, 23, 'Lunglei'),
(436, 23, 'Mamit'),
(437, 23, 'Saiha'),
(438, 23, 'Serchhip'),
(439, 24, 'Dimapur'),
(440, 24, 'Kiphire'),
(441, 24, 'Kohima'),
(442, 24, 'Longleng'),
(443, 24, 'Mokokchung'),
(444, 24, 'Mon'),
(445, 24, 'Peren'),
(446, 24, 'Phek'),
(447, 24, 'Tuensang'),
(448, 24, 'Wokha'),
(449, 24, 'Zunheboto'),
(450, 25, 'Angul'),
(451, 25, 'Balangir'),
(452, 25, 'Balasore'),
(453, 25, 'Bargarh'),
(454, 25, 'Bhadrak'),
(455, 25, 'Boudh'),
(456, 25, 'Cuttack'),
(457, 25, 'Deogarh'),
(458, 25, 'Dhenkanal'),
(459, 25, 'Gajapati'),
(460, 25, 'Ganjam'),
(461, 25, 'Jagatsinghapur'),
(462, 25, 'Jajpur'),
(463, 25, 'Jharsuguda'),
(464, 25, 'Kalahandi'),
(465, 25, 'Kandhamal'),
(466, 25, 'Kendrapara'),
(467, 25, 'Kendujhar (Keonjhar)'),
(468, 25, 'Khordha'),
(469, 25, 'Koraput'),
(470, 25, 'Malkangiri'),
(471, 25, 'Mayurbhanj'),
(472, 25, 'Nabarangpur'),
(473, 25, 'Nayagarh'),
(474, 25, 'Nuapada'),
(475, 25, 'Puri'),
(476, 25, 'Rayagada'),
(477, 25, 'Sambalpur'),
(478, 25, 'Sonepur'),
(479, 25, 'Sundargarh'),
(480, 26, 'Karaikal'),
(481, 26, 'Mahe'),
(482, 26, 'Pondicherry'),
(483, 26, 'Yanam'),
(484, 27, 'Amritsar'),
(485, 27, 'Barnala'),
(486, 27, 'Bathinda'),
(487, 27, 'Faridkot'),
(488, 27, 'Fatehgarh Sahib'),
(489, 27, 'Fazilka'),
(490, 27, 'Ferozepur'),
(491, 27, 'Gurdaspur'),
(492, 27, 'Hoshiarpur'),
(493, 27, 'Jalandhar'),
(494, 27, 'Kapurthala'),
(495, 27, 'Ludhiana'),
(496, 27, 'Mansa'),
(497, 27, 'Moga'),
(498, 27, 'Muktsar'),
(499, 27, 'Nawanshahr (Shahid Bhagat Singh Nagar)'),
(500, 27, 'Pathankot'),
(501, 27, 'Patiala'),
(502, 27, 'Rupnagar'),
(503, 27, 'Sahibzada Ajit Singh Nagar (Mohali)'),
(504, 27, 'Sangrur'),
(505, 27, 'Tarn Taran'),
(506, 28, 'Ajmer'),
(507, 28, 'Alwar'),
(508, 28, 'Banswara'),
(509, 28, 'Baran'),
(510, 28, 'Barmer'),
(511, 28, 'Bharatpur'),
(512, 28, 'Bhilwara'),
(513, 28, 'Bikaner'),
(514, 28, 'Bundi'),
(515, 28, 'Chittorgarh'),
(516, 28, 'Churu'),
(517, 28, 'Dausa'),
(518, 28, 'Dholpur'),
(519, 28, 'Dungarpur'),
(520, 28, 'Hanumangarh'),
(521, 28, 'Jaipur'),
(522, 28, 'Jaisalmer'),
(523, 28, 'Jalore'),
(524, 28, 'Jhalawar'),
(525, 28, 'Jhunjhunu'),
(526, 28, 'Jodhpur'),
(527, 28, 'Karauli'),
(528, 28, 'Kota'),
(529, 28, 'Nagaur'),
(530, 28, 'Pali'),
(531, 28, 'Pratapgarh'),
(532, 28, 'Rajsamand'),
(533, 28, 'Sawai Madhopur'),
(534, 28, 'Sikar'),
(535, 28, 'Sirohi'),
(536, 28, 'Sri Ganganagar'),
(537, 28, 'Tonk'),
(538, 28, 'Udaipur'),
(539, 29, 'East Sikkim'),
(540, 29, 'North Sikkim'),
(541, 29, 'South Sikkim'),
(542, 29, 'West Sikkim'),
(543, 30, 'Ariyalur'),
(544, 30, 'Chennai'),
(545, 30, 'Coimbatore'),
(546, 30, 'Cuddalore'),
(547, 30, 'Dharmapuri'),
(548, 30, 'Dindigul'),
(549, 30, 'Erode'),
(550, 30, 'Kanchipuram'),
(551, 30, 'Kanyakumari'),
(552, 30, 'Karur'),
(553, 30, 'Krishnagiri'),
(554, 30, 'Madurai'),
(555, 30, 'Nagapattinam'),
(556, 30, 'Namakkal'),
(557, 30, 'Nilgiris'),
(558, 30, 'Perambalur'),
(559, 30, 'Pudukkottai'),
(560, 30, 'Ramanathapuram'),
(561, 30, 'Salem'),
(562, 30, 'Sivaganga'),
(563, 30, 'Thanjavur'),
(564, 30, 'Theni'),
(565, 30, 'Thoothukudi (Tuticorin)'),
(566, 30, 'Tiruchirappalli'),
(567, 30, 'Tirunelveli'),
(568, 30, 'Tiruppur'),
(569, 30, 'Tiruvallur'),
(570, 30, 'Tiruvannamalai'),
(571, 30, 'Tiruvarur'),
(572, 30, 'Vellore'),
(573, 30, 'Viluppuram'),
(574, 30, 'Virudhunagar'),
(575, 31, 'Adilabad'),
(576, 31, 'Bhadradri Kothagudem'),
(577, 31, 'Hyderabad'),
(578, 31, 'Jagtial'),
(579, 31, 'Jangaon'),
(580, 31, 'Jayashankar Bhoopalpally'),
(581, 31, 'Jogulamba Gadwal'),
(582, 31, 'Kamareddy'),
(583, 31, 'Karimnagar'),
(584, 31, 'Khammam'),
(585, 31, 'Komaram Bheem Asifabad'),
(586, 31, 'Mahabubabad'),
(587, 31, 'Mahabubnagar'),
(588, 31, 'Mancherial'),
(589, 31, 'Medak'),
(590, 31, 'Medchal'),
(591, 31, 'Nagarkurnool'),
(592, 31, 'Nalgonda'),
(593, 31, 'Nirmal'),
(594, 31, 'Nizamabad'),
(595, 31, 'Peddapalli'),
(596, 31, 'Rajanna Sircilla'),
(597, 31, 'Rangareddy'),
(598, 31, 'Sangareddy'),
(599, 31, 'Siddipet'),
(600, 31, 'Suryapet'),
(601, 31, 'Vikarabad'),
(602, 31, 'Wanaparthy'),
(603, 31, 'Warangal (Rural)'),
(604, 31, 'Warangal (Urban)'),
(605, 31, 'Yadadri Bhuvanagiri'),
(606, 32, 'Dhalai'),
(607, 32, 'Gomati'),
(608, 32, 'Khowai'),
(609, 32, 'North Tripura'),
(610, 32, 'Sepahijala'),
(611, 32, 'South Tripura'),
(612, 32, 'Unakoti'),
(613, 32, 'West Tripura'),
(614, 33, 'Almora'),
(615, 33, 'Bageshwar'),
(616, 33, 'Chamoli'),
(617, 33, 'Champawat'),
(618, 33, 'Dehradun'),
(619, 33, 'Haridwar'),
(620, 33, 'Nainital'),
(621, 33, 'Pauri Garhwal'),
(622, 33, 'Pithoragarh'),
(623, 33, 'Rudraprayag'),
(624, 33, 'Tehri Garhwal'),
(625, 33, 'Udham Singh Nagar'),
(626, 33, 'Uttarkashi'),
(627, 34, 'Agra'),
(628, 34, 'Aligarh'),
(629, 34, 'Allahabad'),
(630, 34, 'Ambedkar Nagar'),
(631, 34, 'Amethi (Chatrapati Sahuji Mahraj Nagar)'),
(632, 34, 'Amroha (J.P. Nagar)'),
(633, 34, 'Auraiya'),
(634, 34, 'Azamgarh'),
(635, 34, 'Baghpat'),
(636, 34, 'Bahraich'),
(637, 34, 'Ballia'),
(638, 34, 'Balrampur'),
(639, 34, 'Banda'),
(640, 34, 'Barabanki'),
(641, 34, 'Bareilly'),
(642, 34, 'Basti'),
(643, 34, 'Bhadohi'),
(644, 34, 'Bijnor'),
(645, 34, 'Budaun'),
(646, 34, 'Bulandshahr'),
(647, 34, 'Chandauli'),
(648, 34, 'Chitrakoot'),
(649, 34, 'Deoria'),
(650, 34, 'Etah'),
(651, 34, 'Etawah'),
(652, 34, 'Faizabad'),
(653, 34, 'Farrukhabad'),
(654, 34, 'Fatehpur'),
(655, 34, 'Firozabad'),
(656, 34, 'Gautam Buddha Nagar'),
(657, 34, 'Ghaziabad'),
(658, 34, 'Ghazipur'),
(659, 34, 'Gonda'),
(660, 34, 'Gorakhpur'),
(661, 34, 'Hamirpur'),
(662, 34, 'Hapur (Panchsheel Nagar)'),
(663, 34, 'Hardoi'),
(664, 34, 'Hathras'),
(665, 34, 'Jalaun'),
(666, 34, 'Jaunpur'),
(667, 34, 'Jhansi'),
(668, 34, 'Kannauj'),
(669, 34, 'Kanpur Dehat'),
(670, 34, 'Kanpur Nagar'),
(671, 34, 'Kanshiram Nagar (Kasganj)'),
(672, 34, 'Kaushambi'),
(673, 34, 'Kushinagar (Padrauna)'),
(674, 34, 'Lakhimpur - Kheri'),
(675, 34, 'Lalitpur'),
(676, 34, 'Lucknow'),
(677, 34, 'Maharajganj'),
(678, 34, 'Mahoba'),
(679, 34, 'Mainpuri'),
(680, 34, 'Mathura'),
(681, 34, 'Mau'),
(682, 34, 'Meerut'),
(683, 34, 'Mirzapur'),
(684, 34, 'Moradabad'),
(685, 34, 'Muzaffarnagar'),
(686, 34, 'Pilibhit'),
(687, 34, 'Pratapgarh'),
(688, 34, 'RaeBareli'),
(689, 34, 'Rampur'),
(690, 34, 'Saharanpur'),
(691, 34, 'Sambhal (Bhim Nagar)'),
(692, 34, 'Sant Kabir Nagar'),
(693, 34, 'Shahjahanpur'),
(694, 34, 'Shamali (Prabuddh Nagar)'),
(695, 34, 'Shravasti'),
(696, 34, 'Siddharth Nagar'),
(697, 34, 'Sitapur'),
(698, 34, 'Sonbhadra'),
(699, 34, 'Sultanpur'),
(700, 34, 'Unnao'),
(701, 34, 'Varanasi'),
(702, 35, 'Alipurduar'),
(703, 35, 'Bankura'),
(704, 35, 'Birbhum'),
(705, 35, 'Burdwan (Bardhaman)'),
(706, 35, 'Cooch Behar'),
(707, 35, 'Dakshin Dinajpur (South Dinajpur)'),
(708, 35, 'Darjeeling'),
(709, 35, 'Hooghly'),
(710, 35, 'Howrah'),
(711, 35, 'Jalpaiguri'),
(712, 35, 'Kalimpong'),
(713, 35, 'Kolkata'),
(714, 35, 'Malda'),
(715, 35, 'Murshidabad'),
(716, 35, 'Nadia'),
(717, 35, 'North 24 Parganas'),
(718, 35, 'Paschim Medinipur (West Medinipur)'),
(719, 35, 'Purba Medinipur (East Medinipur)'),
(720, 35, 'Purulia'),
(721, 35, 'South 24 Parganas'),
(722, 35, 'Uttar Dinajpur (North Dinajpur)');

-- --------------------------------------------------------

--
-- Table structure for table `colleges`
--

CREATE TABLE `colleges` (
  `id` char(36) NOT NULL,
  `name` varchar(300) NOT NULL,
  `slug` varchar(300) NOT NULL,
  `college_type` enum('govt','private','deemed','autonomous') DEFAULT NULL,
  `ownership` enum('central','state','private_trust','minority') DEFAULT NULL,
  `status` enum('active','pending','archived','rejected') DEFAULT 'pending',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_verified` tinyint(1) DEFAULT 0,
  `featured_order` int(11) DEFAULT 0,
  `ranking_nirf` int(11) DEFAULT NULL,
  `ranking_qs` int(11) DEFAULT NULL,
  `ranking_times` int(11) DEFAULT NULL,
  `city_id` int(11) DEFAULT NULL,
  `state_id` int(11) DEFAULT NULL,
  `established_year` year(4) DEFAULT NULL,
  `data_quality_score` tinyint(4) DEFAULT 0,
  `university_affiliation` varchar(255) DEFAULT NULL,
  `university_id` char(36) DEFAULT NULL,
  `autonomous` tinyint(1) DEFAULT 0,
  `naac_grade` enum('A++','A+','A','B++','B+','B','C') DEFAULT NULL,
  `ugc_approved` tinyint(1) DEFAULT 0,
  `aicte_approved` tinyint(1) DEFAULT 0,
  `nba_approved` tinyint(1) DEFAULT 0,
  `total_students` int(11) DEFAULT 0,
  `total_faculty` int(11) DEFAULT 0,
  `campus_area_acres` float DEFAULT NULL,
  `verification_status` enum('unverified','pending','verified','disputed') DEFAULT 'unverified',
  `verified_by` char(36) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `duplicate_of` char(36) DEFAULT NULL,
  `import_batch_id` char(36) DEFAULT NULL,
  `last_data_audit_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `founded_year` year(4) DEFAULT NULL,
  `type_label` varchar(100) DEFAULT NULL,
  `campus_type` enum('urban','semi-urban','rural') DEFAULT NULL,
  `overall_rating_avg` float DEFAULT 0,
  `total_reviews` int(11) DEFAULT 0,
  `rating_distribution` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rating_distribution`)),
  `verified_reviews_count` int(11) DEFAULT 0,
  `publish_status` enum('draft','published','archived') DEFAULT 'draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `colleges`
--

INSERT INTO `colleges` (`id`, `name`, `slug`, `college_type`, `ownership`, `status`, `is_featured`, `is_verified`, `featured_order`, `ranking_nirf`, `ranking_qs`, `ranking_times`, `city_id`, `state_id`, `established_year`, `data_quality_score`, `university_affiliation`, `university_id`, `autonomous`, `naac_grade`, `ugc_approved`, `aicte_approved`, `nba_approved`, `total_students`, `total_faculty`, `campus_area_acres`, `verification_status`, `verified_by`, `verified_at`, `rejection_reason`, `duplicate_of`, `import_batch_id`, `last_data_audit_at`, `created_at`, `updated_at`, `founded_year`, `type_label`, `campus_type`, `overall_rating_avg`, `total_reviews`, `rating_distribution`, `verified_reviews_count`, `publish_status`) VALUES
('35a8b1c1-ce2c-4956-b88a-10867788637b', 'Madhav Arora', 'madhav-arora', 'private', 'state', 'active', 1, 1, 1, 4, NULL, NULL, 507, 28, NULL, 4, NULL, '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', 1, 'A++', 1, 1, 0, 444, 656, 345, 'verified', 'a85f4bf4-5c3b-11f1-a48e-c8f7507a8de6', NULL, NULL, NULL, NULL, NULL, '2026-06-04 06:09:58', '2026-06-04 06:25:40', '2000', 'jhyubj', 'urban', 0, 0, NULL, 0, 'published'),
('fda1bdb7-8dab-4ce5-a7bf-4bee67f5320e', 'gvgftyft', 'gvgftyft', NULL, NULL, 'pending', 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 0, NULL, 0, 0, 0, NULL, NULL, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-04 05:58:45', '2026-06-04 05:58:45', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft');

-- --------------------------------------------------------

--
-- Table structure for table `college_accreditations`
--

CREATE TABLE `college_accreditations` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `accreditation_body` varchar(255) NOT NULL,
  `accreditation_grade` varchar(50) DEFAULT NULL,
  `accreditation_year` year(4) DEFAULT NULL,
  `accreditation_valid_until` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `college_admissions`
--

CREATE TABLE `college_admissions` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `admission_process` text DEFAULT NULL,
  `accepted_exams` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`accepted_exams`)),
  `admission_start_date` date DEFAULT NULL,
  `admission_end_date` date DEFAULT NULL,
  `merit_based` tinyint(1) DEFAULT 0,
  `direct_admission` tinyint(1) DEFAULT 0,
  `management_quota_seats` int(11) DEFAULT 0,
  `nri_quota_seats` int(11) DEFAULT 0,
  `lateral_entry_available` tinyint(1) DEFAULT 0,
  `application_mode` enum('online','offline','both') DEFAULT NULL,
  `selection_criteria` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `college_admissions`
--

INSERT INTO `college_admissions` (`id`, `college_id`, `admission_process`, `accepted_exams`, `admission_start_date`, `admission_end_date`, `merit_based`, `direct_admission`, `management_quota_seats`, `nri_quota_seats`, `lateral_entry_available`, `application_mode`, `selection_criteria`) VALUES
('ffdbfb21-16cc-4a1c-95c0-0995eb87e22d', '35a8b1c1-ce2c-4956-b88a-10867788637b', 'fyf yhrvfyu ftcg yfyfyug xtrf cytgc yhg ', '[\"y fyu\",\"c cg v\"]', '2026-06-05', '2026-06-26', 1, 1, 66, 66, 1, 'online', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `college_contacts`
--

CREATE TABLE `college_contacts` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `latitude` decimal(9,6) DEFAULT NULL,
  `longitude` decimal(9,6) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `google_maps_embed_url` varchar(500) DEFAULT NULL,
  `nearest_railway_km` float DEFAULT NULL,
  `nearest_airport_km` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `college_contacts`
--

INSERT INTO `college_contacts` (`id`, `college_id`, `website_url`, `email`, `phone`, `address`, `latitude`, `longitude`, `pincode`, `google_maps_embed_url`, `nearest_railway_km`, `nearest_airport_km`) VALUES
('26554fb6-ef65-44ed-8bb8-183e6bd551cb', '35a8b1c1-ce2c-4956-b88a-10867788637b', 'https://ctgroup.in', 'madhavarora132005@gmail.com', '09877275894', 'WARD 24, OSWALI MOHALLA', 55.000000, 555.000000, '305801', 'https://maps.google.com', 45, 67),
('33a21801-b81b-4dad-bcb9-09eaccb4c176', 'fda1bdb7-8dab-4ce5-a7bf-4bee67f5320e', '', '', '', '', NULL, NULL, '', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `college_content`
--

CREATE TABLE `college_content` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `about_text` longtext DEFAULT NULL,
  `highlights_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`highlights_json`)),
  `accreditations_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`accreditations_json`)),
  `rankings_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rankings_json`)),
  `awards_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`awards_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `college_content`
--

INSERT INTO `college_content` (`id`, `college_id`, `about_text`, `highlights_json`, `accreditations_json`, `rankings_json`, `awards_json`) VALUES
('54e7fb66-10b6-4511-956f-54089077aa98', '35a8b1c1-ce2c-4956-b88a-10867788637b', 'yftyy vuyhv yhvygh', '[\"cgg\",\"cgvctyg\"]', '[\"ugyhv\"]', '{\"vh\":\"8\"}', '[\"vcfg vtyv\"]');

-- --------------------------------------------------------

--
-- Table structure for table `college_courses`
--

CREATE TABLE `college_courses` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `course_level` enum('UG','PG','Diploma','PhD','Certificate') NOT NULL,
  `duration_years` tinyint(4) DEFAULT NULL,
  `total_fee` decimal(10,2) DEFAULT NULL,
  `semester_fee` decimal(10,2) DEFAULT NULL,
  `annual_fee` decimal(10,2) DEFAULT NULL,
  `seats_available` int(11) DEFAULT NULL,
  `fee_last_updated` date DEFAULT NULL,
  `specializations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specializations`)),
  `eligibility_criteria` text DEFAULT NULL,
  `application_fee` decimal(8,2) DEFAULT NULL,
  `emi_available` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `college_courses`
--

INSERT INTO `college_courses` (`id`, `college_id`, `course_name`, `course_level`, `duration_years`, `total_fee`, `semester_fee`, `annual_fee`, `seats_available`, `fee_last_updated`, `specializations`, `eligibility_criteria`, `application_fee`, `emi_available`) VALUES
('ac981b57-0739-4262-9882-b78bf44f659f', '35a8b1c1-ce2c-4956-b88a-10867788637b', 'vhbfehvbre', 'Diploma', 3, 3334.00, 2343.00, 444.00, 33, NULL, '[\"nhv\",\"mdlcyufgcvhu\"]', 'dh vjfvhuj', 22.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `college_cutoffs`
--

CREATE TABLE `college_cutoffs` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `exam_id` char(36) DEFAULT NULL,
  `course_id` char(36) DEFAULT NULL,
  `category` enum('General','OBC','SC','ST','EWS','PwD') NOT NULL,
  `year` year(4) NOT NULL,
  `opening_rank` int(11) DEFAULT NULL,
  `closing_rank` int(11) DEFAULT NULL,
  `round_number` tinyint(4) DEFAULT NULL,
  `quota` enum('AI','HS','OS','TF','PwD') DEFAULT NULL,
  `gender` enum('neutral','female_only') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `college_cutoffs`
--

INSERT INTO `college_cutoffs` (`id`, `college_id`, `exam_id`, `course_id`, `category`, `year`, `opening_rank`, `closing_rank`, `round_number`, `quota`, `gender`) VALUES
('3de35d3c-5fe0-11f1-a3ef-c8f7507a8de6', '35a8b1c1-ce2c-4956-b88a-10867788637b', 'c5c585e4-93a6-4176-b528-ddd5507e0711', 'ac981b57-0739-4262-9882-b78bf44f659f', 'General', '2000', 5, 7, 3, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `college_faculty`
--

CREATE TABLE `college_faculty` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `faculty_name` varchar(200) NOT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `experience_years` tinyint(4) DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `research_papers` int(11) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `phd_from` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `college_faculty`
--

INSERT INTO `college_faculty` (`id`, `college_id`, `faculty_name`, `designation`, `department`, `qualification`, `experience_years`, `photo_url`, `research_papers`, `linkedin_url`, `specialization`, `phd_from`) VALUES
('aaa879f0-5fe3-11f1-a3ef-c8f7507a8de6', '35a8b1c1-ce2c-4956-b88a-10867788637b', 'dvfvfv', 'Frontend Developer', 'dvfvfv', 'MBA, NET', 7, 'uploads/faculty/1780556669_pexels-poopfishsocks-340403551.jpg', 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `college_faqs`
--

CREATE TABLE `college_faqs` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `question_text` text NOT NULL,
  `answer_text` text NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `schema_faq_enabled` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `college_faqs`
--

INSERT INTO `college_faqs` (`id`, `college_id`, `question_text`, `answer_text`, `category`, `sort_order`, `is_active`, `schema_faq_enabled`) VALUES
('257bdee4-5fe3-11f1-a3ef-c8f7507a8de6', '35a8b1c1-ce2c-4956-b88a-10867788637b', 'gfn', 'erhfg', 'rg', 0, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `college_hostels`
--

CREATE TABLE `college_hostels` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `hostel_available` tinyint(1) DEFAULT 0,
  `hostel_type` enum('boys','girls','both','co-ed') DEFAULT NULL,
  `hostel_capacity` int(11) DEFAULT NULL,
  `hostel_fee_annual` decimal(10,2) DEFAULT NULL,
  `mess_available` tinyint(1) DEFAULT 0,
  `mess_type` enum('veg','non-veg','both') DEFAULT NULL,
  `ac_available` tinyint(1) DEFAULT 0,
  `room_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`room_types`)),
  `security_features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`security_features`)),
  `laundry_available` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `college_hostels`
--

INSERT INTO `college_hostels` (`id`, `college_id`, `hostel_available`, `hostel_type`, `hostel_capacity`, `hostel_fee_annual`, `mess_available`, `mess_type`, `ac_available`, `room_types`, `security_features`, `laundry_available`) VALUES
('f650b9d3-47a2-486d-8e12-75539bb643c1', '35a8b1c1-ce2c-4956-b88a-10867788637b', 0, 'boys', 5, 45455.00, 1, 'veg', 1, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `college_infrastructure`
--

CREATE TABLE `college_infrastructure` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `library` tinyint(1) DEFAULT 0,
  `library_books_count` int(11) DEFAULT NULL,
  `sports_facilities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sports_facilities`)),
  `labs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`labs`)),
  `auditorium` tinyint(1) DEFAULT 0,
  `auditorium_capacity` int(11) DEFAULT NULL,
  `cafeteria` tinyint(1) DEFAULT 0,
  `wifi` tinyint(1) DEFAULT 0,
  `wifi_speed_mbps` int(11) DEFAULT NULL,
  `medical_facility` tinyint(1) DEFAULT 0,
  `transport` tinyint(1) DEFAULT 0,
  `ev_charging` tinyint(1) DEFAULT 0,
  `solar_power` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `college_infrastructure`
--

INSERT INTO `college_infrastructure` (`id`, `college_id`, `library`, `library_books_count`, `sports_facilities`, `labs`, `auditorium`, `auditorium_capacity`, `cafeteria`, `wifi`, `wifi_speed_mbps`, `medical_facility`, `transport`, `ev_charging`, `solar_power`) VALUES
('3ac2f704-7973-45ef-aa16-4abbe2055aa2', '35a8b1c1-ce2c-4956-b88a-10867788637b', 1, NULL, '[\"vbcfg\"]', '[\"bjnij\"]', 0, NULL, 0, 0, NULL, 0, 0, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `college_media`
--

CREATE TABLE `college_media` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `cover_image_url` varchar(255) DEFAULT NULL,
  `image_type` enum('campus','lab','hostel','event','classroom') DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `video_type` enum('tour','placement','event','alumni_talk') DEFAULT NULL,
  `caption` varchar(300) DEFAULT NULL,
  `sort_order` tinyint(4) DEFAULT 0,
  `document_type` enum('brochure','prospectus','annual_report','ranking_cert') DEFAULT NULL,
  `document_url` varchar(255) DEFAULT NULL,
  `360_tour_url` varchar(255) DEFAULT NULL,
  `virtual_tour_enabled` tinyint(1) DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `college_media`
--

INSERT INTO `college_media` (`id`, `college_id`, `logo_url`, `cover_image_url`, `image_type`, `video_url`, `video_type`, `caption`, `sort_order`, `document_type`, `document_url`, `360_tour_url`, `virtual_tour_enabled`, `image_url`) VALUES
('37c6992b-a9a5-47eb-8ff7-f87ea60c53e3', '35a8b1c1-ce2c-4956-b88a-10867788637b', NULL, NULL, NULL, NULL, NULL, 'fgdb', 0, NULL, NULL, NULL, 0, 'uploads/media/1780555941_pexels-poopfishsocks-340403551.jpg'),
('661f26fe-3f51-4bc2-90ba-d38be4788ab1', 'fda1bdb7-8dab-4ce5-a7bf-4bee67f5320e', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL),
('a6f36162-46d2-46cf-a97d-62d156a721e8', '35a8b1c1-ce2c-4956-b88a-10867788637b', 'uploads/college_logo_1780553398_6a2116b61d252.jpg', 'uploads/college_cover_1780553398_6a2116b61ddca.jpg', NULL, NULL, NULL, NULL, 0, NULL, NULL, 'https://github.com', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `college_placements`
--

CREATE TABLE `college_placements` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `placement_year` year(4) NOT NULL,
  `avg_package_lpa` decimal(5,2) DEFAULT NULL,
  `highest_package_lpa` decimal(5,2) DEFAULT NULL,
  `median_package_lpa` decimal(5,2) DEFAULT NULL,
  `placement_percentage` float DEFAULT NULL,
  `students_placed` int(11) DEFAULT NULL,
  `international_placements` int(11) DEFAULT NULL,
  `top_recruiters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`top_recruiters`)),
  `sector_wise_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sector_wise_json`)),
  `placement_report_pdf` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `college_placements`
--

INSERT INTO `college_placements` (`id`, `college_id`, `placement_year`, `avg_package_lpa`, `highest_package_lpa`, `median_package_lpa`, `placement_percentage`, `students_placed`, `international_placements`, `top_recruiters`, `sector_wise_json`, `placement_report_pdf`) VALUES
('ac8febb2-a83b-40fb-aec1-682c377e442b', '35a8b1c1-ce2c-4956-b88a-10867788637b', '2000', 5.00, 22.00, 22.00, 333, 34, 3, '[{\"name\":\"dg\"},{\"name\":\"duhfgvf\"},{\"name\":\"cducgfj\"}]', '[{\"sector\":\"IT\",\"pct\":98}]', 'uploads/placements/1780555175_Madhav_Arora_Resume.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `college_scholarships`
--

CREATE TABLE `college_scholarships` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `scholarship_name` varchar(255) NOT NULL,
  `scholarship_type` enum('merit','need','sports','minority') DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `amount_type` enum('fixed','percentage','full_tuition') DEFAULT NULL,
  `eligibility_criteria` text DEFAULT NULL,
  `renewable` tinyint(1) DEFAULT 0,
  `apply_link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `college_scholarships`
--

INSERT INTO `college_scholarships` (`id`, `college_id`, `scholarship_name`, `scholarship_type`, `amount`, `amount_type`, `eligibility_criteria`, `renewable`, `apply_link`) VALUES
('b1f58d8d-5fe3-11f1-a3ef-c8f7507a8de6', '35a8b1c1-ce2c-4956-b88a-10867788637b', 'gvhghvghbvgh', 'merit', 45454.00, NULL, '5tetbgfbgrb', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `commissions`
--

CREATE TABLE `commissions` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `college_id` char(36) NOT NULL,
  `consultant_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `commission_pct` float NOT NULL,
  `commission_earned` decimal(10,2) NOT NULL,
  `commission_status` enum('pending','paid','disputed') DEFAULT 'pending',
  `payout_date` date DEFAULT NULL,
  `payout_method` enum('bank_transfer','credit') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compare_config`
--

CREATE TABLE `compare_config` (
  `id` int(11) NOT NULL,
  `max_entities` tinyint(4) DEFAULT 4,
  `compare_fields_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Ordered list of field groups' CHECK (json_valid(`compare_fields_config`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compare_sessions`
--

CREATE TABLE `compare_sessions` (
  `id` char(36) NOT NULL,
  `comparison_type` enum('college','course','exam') NOT NULL,
  `entity_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'JSON array of 2-4 UUIDs' CHECK (json_valid(`entity_ids`)),
  `user_id` char(36) DEFAULT NULL COMMENT 'Nullable for anonymous users',
  `session_id` varchar(255) DEFAULT NULL COMMENT 'Anonymous tracking',
  `is_saved` tinyint(1) DEFAULT 0,
  `share_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consultants`
--

CREATE TABLE `consultants` (
  `id` int(11) NOT NULL,
  `consultant_name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `consultant_rating` float DEFAULT NULL,
  `verified_consultant` tinyint(1) DEFAULT 0,
  `specialization_countries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specialization_countries`)),
  `fee_range` varchar(100) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `specializations` varchar(255) DEFAULT NULL,
  `consultation_mode` enum('Online','Offline','Both') DEFAULT 'Both',
  `success_rate_percent` float DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `office_location` varchar(255) DEFAULT NULL,
  `languages_spoken` varchar(255) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `iso_code` varchar(5) DEFAULT NULL,
  `currency_code` varchar(5) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `name`, `iso_code`, `currency_code`, `created_at`) VALUES
(1, 'United States', 'US', 'USD', '2026-05-30 16:08:44'),
(2, 'United Kingdom', 'GB', 'GBP', '2026-05-30 16:08:44'),
(3, 'Canada', 'CA', 'CAD', '2026-05-30 16:08:44'),
(4, 'Australia', 'AU', 'AUD', '2026-05-30 16:08:44'),
(5, 'Germany', 'DE', 'EUR', '2026-05-30 16:08:44');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` char(36) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `course_slug` varchar(255) NOT NULL,
  `course_level` enum('UG','PG','Diploma','PhD','Certificate','Integrated') DEFAULT NULL,
  `course_category` varchar(255) DEFAULT NULL,
  `category_id` char(36) DEFAULT NULL,
  `duration_years` tinyint(4) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `eligibility` text DEFAULT NULL,
  `career_scope` text DEFAULT NULL,
  `top_recruiters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`top_recruiters`)),
  `avg_salary_lpa` decimal(5,2) DEFAULT NULL,
  `salary_range_min` decimal(5,2) DEFAULT NULL,
  `salary_range_max` decimal(5,2) DEFAULT NULL,
  `is_popular` tinyint(1) DEFAULT 0,
  `total_colleges_offering` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_name`, `course_slug`, `course_level`, `course_category`, `category_id`, `duration_years`, `description`, `eligibility`, `career_scope`, `top_recruiters`, `avg_salary_lpa`, `salary_range_min`, `salary_range_max`, `is_popular`, `total_colleges_offering`, `status`, `created_at`, `updated_at`) VALUES
('04b264ad-5fe9-11f1-a3ef-c8f7507a8de6', 'MBA', 'mba', 'PG', NULL, '85ce791e-b01b-440b-9fff-f944fd6212b9', 33, '<p>jvgbvhv nuhtgcbjv fhvjnm nvnyv kn</p>', '  bhg bhu6ygvgvhnv', 'fhvjgb jgfuh', '[{\"name\":\"fhdgfjrbcbhyfhbd kjvg\",\"logo\":\"uploads\\/recruiters\\/1780586514_994_pexels-poopfishsocks-340403551.jpg\"}]', 221.00, 11.00, 22.00, 1, 0, 'active', '2026-06-04 07:42:47', '2026-06-04 15:21:54'),
('04b26f0c-5fe9-11f1-a3ef-c8f7507a8de6', 'MBBS', 'mbbs', 'UG', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 'active', '2026-06-04 07:42:47', '2026-06-04 07:42:47'),
('fa650635-32d7-4840-9289-5cbfe369d40d', 'regtbeg', 'regtbeg', 'PG', NULL, '85ce791e-b01b-440b-9fff-f944fd6212b9', 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 'active', '2026-06-04 15:47:52', '2026-06-04 15:47:52');

-- --------------------------------------------------------

--
-- Table structure for table `course_career_paths`
--

CREATE TABLE `course_career_paths` (
  `id` char(36) NOT NULL,
  `course_id` char(36) NOT NULL,
  `job_role` varchar(255) NOT NULL,
  `avg_salary_lpa` decimal(5,2) DEFAULT NULL,
  `top_companies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`top_companies`)),
  `growth_outlook` enum('high','medium','low') DEFAULT NULL,
  `skills_required` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skills_required`)),
  `fresher_salary_lpa` decimal(5,2) DEFAULT NULL,
  `experienced_salary_lpa` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_career_paths`
--

INSERT INTO `course_career_paths` (`id`, `course_id`, `job_role`, `avg_salary_lpa`, `top_companies`, `growth_outlook`, `skills_required`, `fresher_salary_lpa`, `experienced_salary_lpa`, `created_at`, `updated_at`) VALUES
('3a828208-29b8-4a42-9830-0321329a6305', '04b264ad-5fe9-11f1-a3ef-c8f7507a8de6', 'fvfgfgeg', 33.00, '[{\"name\":\"frgfhrtbg\",\"logo\":\"uploads\\/companies\\/1780588047_891_pexels-poopfishsocks-340403551.jpg\"}]', 'high', '[\"fgbgb\",\"grtghb\"]', 34.00, 35.00, '2026-06-04 15:47:27', '2026-06-04 15:47:27');

-- --------------------------------------------------------

--
-- Table structure for table `course_categories`
--

CREATE TABLE `course_categories` (
  `id` char(36) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `category_slug` varchar(255) NOT NULL,
  `icon_url` varchar(255) DEFAULT NULL,
  `parent_category_id` char(36) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_categories`
--

INSERT INTO `course_categories` (`id`, `category_name`, `category_slug`, `icon_url`, `parent_category_id`, `sort_order`, `is_featured`, `created_at`, `updated_at`) VALUES
('85ce791e-b01b-440b-9fff-f944fd6212b9', 'ytrdfgtryhg', 'ytrdfgtryhg', NULL, NULL, 0, 0, '2026-06-04 08:44:34', '2026-06-04 08:44:34');

-- --------------------------------------------------------

--
-- Table structure for table `course_specializations`
--

CREATE TABLE `course_specializations` (
  `id` char(36) NOT NULL,
  `parent_course_id` char(36) NOT NULL,
  `specialization_name` varchar(255) NOT NULL,
  `specialization_slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_popular` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_specializations`
--

INSERT INTO `course_specializations` (`id`, `parent_course_id`, `specialization_name`, `specialization_slug`, `description`, `sort_order`, `is_popular`, `created_at`, `updated_at`) VALUES
('a640363b-69af-433f-a300-723c3c57d17e', '04b264ad-5fe9-11f1-a3ef-c8f7507a8de6', 'fgadfgfg', 'fgadfgfg', 'eesf gght htythg', 0, 1, '2026-06-04 15:23:44', '2026-06-04 15:23:52');

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_attachments`
--

CREATE TABLE `dashboard_attachments` (
  `id` char(36) NOT NULL,
  `entity_type` varchar(255) DEFAULT NULL,
  `entity_id` char(36) DEFAULT NULL,
  `file_url` varchar(255) DEFAULT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_filters`
--

CREATE TABLE `dashboard_filters` (
  `id` char(36) NOT NULL,
  `filter_key` varchar(255) DEFAULT NULL,
  `filter_type` varchar(255) DEFAULT NULL,
  `options_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options_json`)),
  `default_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_value`)),
  `is_global` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_layouts`
--

CREATE TABLE `dashboard_layouts` (
  `id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `role_id` char(36) DEFAULT NULL,
  `layout_name` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `layout_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`layout_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_search_logs`
--

CREATE TABLE `dashboard_search_logs` (
  `id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `search_query` varchar(255) DEFAULT NULL,
  `results_count` int(11) DEFAULT NULL,
  `clicked_result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`clicked_result`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_snapshots`
--

CREATE TABLE `dashboard_snapshots` (
  `id` char(36) NOT NULL,
  `metric_key` varchar(255) DEFAULT NULL,
  `metric_value` decimal(15,2) DEFAULT NULL,
  `dimension_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dimension_json`)),
  `snapshot_type` enum('hourly','daily','weekly','monthly') DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_widgets`
--

CREATE TABLE `dashboard_widgets` (
  `id` char(36) NOT NULL,
  `widget_key` varchar(255) DEFAULT NULL,
  `widget_name` varchar(255) DEFAULT NULL,
  `widget_type` enum('metric','chart','table','feed','alert','ai_summary','system_health','leaderboard') DEFAULT NULL,
  `data_source` varchar(255) DEFAULT NULL,
  `config_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config_json`)),
  `default_size` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_size`)),
  `is_realtime` tinyint(1) DEFAULT 0,
  `cache_duration` int(11) DEFAULT 300,
  `status` enum('active','inactive','draft') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dynamic_fields`
--

CREATE TABLE `dynamic_fields` (
  `id` char(36) NOT NULL,
  `module_id` char(36) NOT NULL,
  `field_key` varchar(255) DEFAULT NULL,
  `field_label` varchar(255) DEFAULT NULL,
  `field_type` varchar(255) DEFAULT NULL,
  `validation_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_json`)),
  `settings_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings_json`)),
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dynamic_modules`
--

CREATE TABLE `dynamic_modules` (
  `id` char(36) NOT NULL,
  `module_key` varchar(255) DEFAULT NULL,
  `module_name` varchar(255) DEFAULT NULL,
  `entity_type` varchar(255) DEFAULT NULL,
  `config_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config_json`)),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` char(36) NOT NULL,
  `exam_name` varchar(255) NOT NULL,
  `exam_slug` varchar(255) NOT NULL,
  `exam_abbreviation` varchar(20) DEFAULT NULL,
  `conducting_body` varchar(255) DEFAULT NULL,
  `conducting_body_logo` varchar(255) DEFAULT NULL,
  `exam_level` enum('national','state','university','institute') DEFAULT NULL,
  `exam_mode` enum('online','offline','both') DEFAULT NULL,
  `exam_frequency` enum('annual','biannual','quarterly','monthly') DEFAULT NULL,
  `participating_colleges_count` int(11) DEFAULT 0,
  `applicants_last_year` int(11) DEFAULT 0,
  `is_national` tinyint(1) DEFAULT 0,
  `status` enum('active','upcoming','completed','cancelled') DEFAULT 'upcoming',
  `age_min` int(11) DEFAULT NULL,
  `age_max` int(11) DEFAULT NULL,
  `min_percentage_required` float DEFAULT NULL,
  `qualifying_exam` varchar(255) DEFAULT NULL,
  `nationality` enum('indian','nri','both') DEFAULT NULL,
  `total_marks` int(11) DEFAULT NULL,
  `total_questions` int(11) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `subjects_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`subjects_json`)),
  `marking_scheme` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`marking_scheme`)),
  `sections` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sections`)),
  `language_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`language_options`)),
  `application_fee_general` decimal(8,2) DEFAULT NULL,
  `application_fee_obc` decimal(8,2) DEFAULT NULL,
  `application_fee_sc_st` decimal(8,2) DEFAULT NULL,
  `application_fee_pwd` decimal(8,2) DEFAULT NULL,
  `application_fee_female` decimal(8,2) DEFAULT NULL,
  `application_url` varchar(255) DEFAULT NULL,
  `official_website` varchar(255) DEFAULT NULL,
  `syllabus_pdf_url` varchar(255) DEFAULT NULL,
  `result_url` varchar(255) DEFAULT NULL,
  `scorecard_url` varchar(255) DEFAULT NULL,
  `counselling_authority` varchar(255) DEFAULT NULL,
  `counselling_rounds` tinyint(4) DEFAULT NULL,
  `merit_list_url` varchar(255) DEFAULT NULL,
  `normalisation_method` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `exam_name`, `exam_slug`, `exam_abbreviation`, `conducting_body`, `conducting_body_logo`, `exam_level`, `exam_mode`, `exam_frequency`, `participating_colleges_count`, `applicants_last_year`, `is_national`, `status`, `age_min`, `age_max`, `min_percentage_required`, `qualifying_exam`, `nationality`, `total_marks`, `total_questions`, `duration_minutes`, `subjects_json`, `marking_scheme`, `sections`, `language_options`, `application_fee_general`, `application_fee_obc`, `application_fee_sc_st`, `application_fee_pwd`, `application_fee_female`, `application_url`, `official_website`, `syllabus_pdf_url`, `result_url`, `scorecard_url`, `counselling_authority`, `counselling_rounds`, `merit_list_url`, `normalisation_method`, `created_at`, `updated_at`) VALUES
('c5c585e4-93a6-4176-b528-ddd5507e0711', 'sdfghf', 'sdfghf', 'vbhv ghh', 't hgfygb yh ', 'uploads/exam_logo_6a204366d5add.jpg', 'state', 'online', 'biannual', 677, 2000, 1, 'completed', 22, 33, 55, '12', 'nri', 200, 10, 44, '[{\"subject\":\"yghchv\",\"questions\":22,\"marks\":22},{\"subject\":\"gjch\",\"questions\":20,\"marks\":33}]', '{\"correct\":5,\"wrong\":3,\"unattempted\":0}', '[{\"name\":\"chjvymfu yfnu dn h \",\"questions\":44,\"time\":23},{\"name\":\"ghvjhhnjbhjnn\",\"questions\":4,\"time\":56}]', '[\"vhdh\",\"ufhfv\"]', 2222.00, 2222.00, 2222.00, 2222.00, 2222.00, 'https://githib.com', 'https://githib.com', 'uploads/exam_doc_6a2047ff1a13c.pdf', 'uploads/exam_doc_6a2047ff1b4f3.pdf', 'uploads/exam_doc_6a2047ff1bf05.pdf', 'chfcjgf', 22, 'uploads/exam_doc_6a2047ff1c751.pdf', 'dffvrf', '2026-06-03 15:08:22', '2026-06-03 15:27:59');

-- --------------------------------------------------------

--
-- Table structure for table `exam_cutoffs`
--

CREATE TABLE `exam_cutoffs` (
  `id` char(36) NOT NULL,
  `exam_id` char(36) NOT NULL,
  `college_id` char(36) DEFAULT NULL,
  `course_id` char(36) DEFAULT NULL,
  `year` year(4) NOT NULL,
  `category` enum('General','OBC','SC','ST','EWS','PWD') NOT NULL,
  `opening_rank` int(11) DEFAULT NULL,
  `closing_rank` int(11) DEFAULT NULL,
  `round` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_cutoffs`
--

INSERT INTO `exam_cutoffs` (`id`, `exam_id`, `college_id`, `course_id`, `year`, `category`, `opening_rank`, `closing_rank`, `round`) VALUES
('92dc2746-5c8c-4995-b737-543ab95a6eee', 'c5c585e4-93a6-4176-b528-ddd5507e0711', NULL, NULL, '2026', 'General', 2, 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `exam_dates`
--

CREATE TABLE `exam_dates` (
  `id` char(36) NOT NULL,
  `exam_id` char(36) NOT NULL,
  `year` year(4) DEFAULT NULL,
  `event_name` varchar(255) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `application_start` date DEFAULT NULL,
  `application_end` date DEFAULT NULL,
  `exam_date` date DEFAULT NULL,
  `result_date` date DEFAULT NULL,
  `admit_card_date` date DEFAULT NULL,
  `counselling_start` date DEFAULT NULL,
  `answer_key_date` date DEFAULT NULL,
  `is_tentative` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_resources`
--

CREATE TABLE `exam_resources` (
  `id` char(36) NOT NULL,
  `exam_id` char(36) NOT NULL,
  `sample_papers_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sample_papers_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_resources`
--

INSERT INTO `exam_resources` (`id`, `exam_id`, `sample_papers_json`) VALUES
('911dafdf-947b-454f-83be-85c45da8d2ea', 'c5c585e4-93a6-4176-b528-ddd5507e0711', '[{\"year\":\"2024\",\"subject\":\"bhh\",\"description\":\"cfgf tyg ffythv vyth vyvyuh vhcfcvgc hn\",\"url\":\"uploads\\/sp_6a2048ff47102.pdf\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

CREATE TABLE `exam_results` (
  `id` char(36) NOT NULL,
  `exam_id` char(36) NOT NULL,
  `percentile_vs_marks_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`percentile_vs_marks_json`)),
  `cutoff_pdfs_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cutoff_pdfs_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_results`
--

INSERT INTO `exam_results` (`id`, `exam_id`, `percentile_vs_marks_json`, `cutoff_pdfs_json`) VALUES
('d0115728-9f48-422b-803b-2f396eeaaa6d', 'c5c585e4-93a6-4176-b528-ddd5507e0711', '{\"2000\":[{\"marks\":22,\"percentile\":22}]}', '[{\"year\":\"22\",\"subject\":\"vfv\",\"description\":\"v g bg\",\"url\":\"uploads\\/co_6a2051ed21abd.pdf\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `exam_syllabus`
--

CREATE TABLE `exam_syllabus` (
  `id` char(36) NOT NULL,
  `exam_id` char(36) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `topic` varchar(255) NOT NULL,
  `subtopics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`subtopics`)),
  `weightage_pct` float DEFAULT NULL,
  `chapter_pdf_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_syllabus`
--

INSERT INTO `exam_syllabus` (`id`, `exam_id`, `subject`, `topic`, `subtopics`, `weightage_pct`, `chapter_pdf_url`) VALUES
('e4e65a1c-2c03-4216-856c-85b784979365', 'c5c585e4-93a6-4176-b528-ddd5507e0711', 'jgewjrhteshrfgjh', 'gcfxdt', '[\"dxdty\",\"dftydfv\"]', 55, 'uploads/syl_6a2053c16c45d.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `experts`
--

CREATE TABLE `experts` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `expert_name` varchar(255) NOT NULL,
  `expert_designation` varchar(255) DEFAULT NULL,
  `expert_college` varchar(255) DEFAULT NULL,
  `verified_badge` tinyint(1) DEFAULT 0,
  `answer_count` int(11) DEFAULT 0,
  `profile_url` varchar(255) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `response_rate_pct` float DEFAULT 0,
  `avg_response_hours` float DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `experts`
--

INSERT INTO `experts` (`id`, `expert_name`, `expert_designation`, `expert_college`, `verified_badge`, `answer_count`, `profile_url`, `specialization`, `linkedin_url`, `response_rate_pct`, `avg_response_hours`, `created_at`, `updated_at`) VALUES
('fcf508bc-602f-11f1-9ea0-a0510b1a7448', 'ncjbvf', 'djfhrjv', 'rjgjfr', 1, 0, 'https://bhfruf.com', 'fjkwnrgjmr', 'https://www.linkedin.com/in/madhav-arora-32b056254/', 100, 0, '2026-06-04 16:10:54', '2026-06-04 16:14:25');

-- --------------------------------------------------------

--
-- Table structure for table `exports`
--

CREATE TABLE `exports` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `export_type` varchar(255) DEFAULT NULL,
  `filters_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters_json`)),
  `file_url` varchar(255) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `foreign_universities`
--

CREATE TABLE `foreign_universities` (
  `id` int(11) NOT NULL,
  `university_name` varchar(255) NOT NULL,
  `university_slug` varchar(255) NOT NULL,
  `country` varchar(255) DEFAULT NULL,
  `qs_rank` int(11) DEFAULT NULL,
  `times_rank` int(11) DEFAULT NULL,
  `acceptance_rate` float DEFAULT NULL,
  `tuition_usd_annual` decimal(10,2) DEFAULT NULL,
  `living_cost_usd_monthly` decimal(8,2) DEFAULT NULL,
  `intake_months` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`intake_months`)),
  `official_url` varchar(255) DEFAULT NULL,
  `min_ielts` float DEFAULT NULL,
  `min_toefl` int(11) DEFAULT NULL,
  `min_gre` int(11) DEFAULT NULL,
  `scholarship_available` tinyint(1) DEFAULT 0,
  `logo_url` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `institution_type` enum('Public','Private') DEFAULT NULL,
  `application_fee_usd` decimal(8,2) DEFAULT NULL,
  `min_pte` float DEFAULT NULL,
  `min_gmat` int(11) DEFAULT NULL,
  `min_gpa` varchar(50) DEFAULT NULL,
  `degrees_offered` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`degrees_offered`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `foreign_universities`
--

INSERT INTO `foreign_universities` (`id`, `university_name`, `university_slug`, `country`, `qs_rank`, `times_rank`, `acceptance_rate`, `tuition_usd_annual`, `living_cost_usd_monthly`, `intake_months`, `official_url`, `min_ielts`, `min_toefl`, `min_gre`, `scholarship_available`, `logo_url`, `description`, `city`, `institution_type`, `application_fee_usd`, `min_pte`, `min_gmat`, `min_gpa`, `degrees_offered`, `created_at`, `updated_at`) VALUES
(1, 'jbjvshdcf', 'jbjvshdcf', 'dhcvbdj', 2, NULL, NULL, 343.00, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '<p style=\"text-align: center; \">bevvwc dhcvyh ch<strong>bxzuic bhc ubsdh hsch dhe yueh<em><sup>vc dv cydycvewy</sup></em></strong><em><sup>hcv ye fv cjbdjc j, jcb fh</sup></em></p>', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-04 16:22:09', '2026-06-04 16:22:09');

-- --------------------------------------------------------

--
-- Table structure for table `funnel_analytics`
--

CREATE TABLE `funnel_analytics` (
  `id` int(11) NOT NULL,
  `funnel_step` enum('visit','search','college_view','shortlist','lead','apply','convert') NOT NULL,
  `users_entered` int(11) DEFAULT 0,
  `users_completed` int(11) DEFAULT 0,
  `drop_off_rate` float GENERATED ALWAYS AS (if(`users_entered` > 0,(`users_entered` - `users_completed`) / `users_entered` * 100,0)) STORED,
  `date` date NOT NULL,
  `segment` varchar(100) DEFAULT 'All',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `internal_links`
--

CREATE TABLE `internal_links` (
  `id` int(11) NOT NULL,
  `link_source_page` varchar(255) NOT NULL,
  `link_target_page` varchar(255) NOT NULL,
  `anchor_text` varchar(255) DEFAULT NULL,
  `is_broken` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `internal_links`
--

INSERT INTO `internal_links` (`id`, `link_source_page`, `link_target_page`, `anchor_text`, `is_broken`, `created_at`, `updated_at`) VALUES
(1, 'fhn', 'et', '', 0, '2026-06-05 15:40:59', '2026-06-05 15:40:59');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `invoice_description` text DEFAULT NULL,
  `college_id` varchar(36) NOT NULL,
  `subscription_id` int(11) DEFAULT NULL,
  `gst_number` varchar(15) DEFAULT NULL,
  `gst_amount` decimal(10,2) DEFAULT 0.00,
  `payment_method` enum('bank_transfer','card','upi') DEFAULT NULL,
  `payment_status` enum('pending','paid','overdue') DEFAULT 'pending',
  `subtotal_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `invoice_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_definitions`
--

CREATE TABLE `kpi_definitions` (
  `id` char(36) NOT NULL,
  `metric_key` varchar(255) DEFAULT NULL,
  `metric_name` varchar(255) DEFAULT NULL,
  `metric_type` enum('count','sum','percentage','average') DEFAULT NULL,
  `query_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`query_config`)),
  `chart_type` varchar(255) DEFAULT NULL,
  `cache_duration` int(11) DEFAULT 300,
  `is_realtime` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive','draft') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `lead_type` enum('inquiry','callback','download','apply','chat_exit') DEFAULT NULL,
  `source_page` varchar(255) DEFAULT NULL,
  `utm_source` varchar(255) DEFAULT NULL,
  `utm_medium` varchar(255) DEFAULT NULL,
  `utm_campaign` varchar(255) DEFAULT NULL,
  `utm_content` varchar(255) DEFAULT NULL,
  `utm_term` varchar(255) DEFAULT NULL,
  `gclid` varchar(255) DEFAULT NULL,
  `college_id` char(36) DEFAULT NULL,
  `course_id` char(36) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `class_12_score` float DEFAULT NULL,
  `target_year` year(4) DEFAULT NULL,
  `preferred_budget` decimal(10,2) DEFAULT NULL,
  `lead_status` enum('new','contacted','qualified','converted','lost','invalid') DEFAULT 'new',
  `assigned_to` char(36) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `last_contacted_at` timestamp NULL DEFAULT NULL,
  `next_followup_at` timestamp NULL DEFAULT NULL,
  `call_attempts` tinyint(4) DEFAULT 0,
  `counsellor_notes` text DEFAULT NULL,
  `disposition` enum('not_reachable','interested','not_interested','wrong_number') DEFAULT NULL,
  `sla_breach_at` timestamp NULL DEFAULT NULL,
  `delivered_to_college_at` timestamp NULL DEFAULT NULL,
  `delivery_status` enum('pending','delivered','failed','disputed') DEFAULT 'pending',
  `dispute_reason` text DEFAULT NULL,
  `dispute_raised_at` timestamp NULL DEFAULT NULL,
  `dispute_resolved_at` timestamp NULL DEFAULT NULL,
  `dispute_outcome` enum('credited','rejected') DEFAULT NULL,
  `is_blacklisted` tinyint(1) DEFAULT 0,
  `blacklist_reason` text DEFAULT NULL,
  `attribution_model` enum('first_touch','last_touch','linear','position_based') DEFAULT NULL,
  `first_touch_source` varchar(255) DEFAULT NULL,
  `last_touch_source` varchar(255) DEFAULT NULL,
  `touchpoints_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`touchpoints_json`)),
  `revenue_attributed` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_call_logs`
--

CREATE TABLE `lead_call_logs` (
  `id` char(36) NOT NULL,
  `lead_id` char(36) NOT NULL,
  `call_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `duration_seconds` int(11) DEFAULT NULL,
  `outcome` enum('answered','no_answer','voicemail','busy') DEFAULT NULL,
  `recording_url` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `called_by` char(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_credits`
--

CREATE TABLE `lead_credits` (
  `id` int(11) NOT NULL,
  `college_id` char(36) NOT NULL,
  `leads_purchased` int(11) NOT NULL DEFAULT 0,
  `leads_delivered` int(11) NOT NULL DEFAULT 0,
  `lead_cost` decimal(8,2) NOT NULL DEFAULT 0.00,
  `credits_remaining` int(11) GENERATED ALWAYS AS (`leads_purchased` - `leads_delivered`) STORED,
  `expiry_date` date DEFAULT NULL,
  `status` enum('active','expired','depleted') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_credits`
--

INSERT INTO `lead_credits` (`id`, `college_id`, `leads_purchased`, `leads_delivered`, `lead_cost`, `expiry_date`, `status`, `created_at`, `updated_at`) VALUES
(1, '35a8b1c1-ce2c-4956-b88a-10867788637b', 54, 54, 1.35, '2026-06-16', 'active', '2026-06-05 06:35:30', '2026-06-05 06:35:40');

-- --------------------------------------------------------

--
-- Table structure for table `media_files`
--

CREATE TABLE `media_files` (
  `id` char(36) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_url` varchar(255) NOT NULL,
  `cdn_url` varchar(255) DEFAULT NULL,
  `file_type` enum('image','video','pdf','doc','svg') DEFAULT 'image',
  `file_size_kb` int(11) DEFAULT NULL,
  `dimensions_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dimensions_json`)),
  `alt_text` varchar(255) DEFAULT NULL,
  `uploaded_by` char(36) DEFAULT NULL,
  `folder_path` varchar(255) DEFAULT NULL,
  `webp_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `media_files`
--

INSERT INTO `media_files` (`id`, `file_name`, `file_url`, `cdn_url`, `file_type`, `file_size_kb`, `dimensions_json`, `alt_text`, `uploaded_by`, `folder_path`, `webp_url`, `created_at`, `updated_at`) VALUES
('4a8d602b-a839-454e-88e6-7d7cb8da436f', '61123.jpg', '../uploads/media/6a22a983f32bb_61123.jpg', NULL, 'image', 2411, NULL, '', 'a85f4bf4-5c3b-11f1-a48e-c8f7507a8de6', '/uploads/media/', NULL, '2026-06-05 10:48:35', '2026-06-05 10:48:35'),
('d2808d51-03a5-4306-845a-50032b00d65d', 'pexels-poopfishsocks-34040355 (1).jpg', '../uploads/media/6a22a96f25a13_pexels-poopfishsocks-34040355__1_.jpg', NULL, 'image', 8999, NULL, '', 'a85f4bf4-5c3b-11f1-a48e-c8f7507a8de6', '/uploads/media/', NULL, '2026-06-05 10:48:15', '2026-06-05 10:48:15');

-- --------------------------------------------------------

--
-- Table structure for table `moderation_queue`
--

CREATE TABLE `moderation_queue` (
  `id` varchar(36) NOT NULL,
  `entity_type` enum('review','qa','article','college_data','comment') NOT NULL,
  `entity_id` varchar(36) NOT NULL,
  `status` enum('pending','in_progress','resolved','dismissed') DEFAULT 'pending',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `flagged_reason` enum('spam','offensive','misleading','duplicate','low_quality') NOT NULL,
  `ai_score` float DEFAULT NULL CHECK (`ai_score` >= 0 and `ai_score` <= 1),
  `reporter_id` varchar(36) DEFAULT NULL,
  `moderator_id` varchar(36) DEFAULT NULL,
  `action_taken` enum('approve','reject','flag','escalate','warn_user') DEFAULT NULL,
  `action_note` text DEFAULT NULL,
  `actioned_at` timestamp NULL DEFAULT NULL,
  `escalated_to` varchar(36) DEFAULT NULL,
  `sla_due_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_campaigns`
--

CREATE TABLE `notification_campaigns` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `campaign_name` varchar(255) NOT NULL,
  `template_id` char(36) NOT NULL,
  `audience_segment_id` char(36) DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_count` int(11) DEFAULT 0,
  `delivered_count` int(11) DEFAULT 0,
  `opened_count` int(11) DEFAULT 0,
  `clicked_count` int(11) DEFAULT 0,
  `unsubscribed_count` int(11) DEFAULT 0,
  `failed_count` int(11) DEFAULT 0,
  `status` enum('draft','scheduled','sending','sent','cancelled') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_campaigns`
--

INSERT INTO `notification_campaigns` (`id`, `campaign_name`, `template_id`, `audience_segment_id`, `scheduled_at`, `sent_count`, `delivered_count`, `opened_count`, `clicked_count`, `unsubscribed_count`, `failed_count`, `status`, `created_at`, `updated_at`) VALUES
('7bdb49f4-60f4-11f1-b290-a0510b1a7448', 'ngccgj', '1972a1b7-60f3-11f1-b290-a0510b1a7448', '419ff199-60f4-11f1-b290-a0510b1a7448', '2026-06-12 15:37:00', 0, 0, 0, 0, 0, 0, 'sending', '2026-06-05 15:37:35', '2026-06-05 15:37:35');

-- --------------------------------------------------------

--
-- Table structure for table `notification_logs`
--

CREATE TABLE `notification_logs` (
  `id` int(11) NOT NULL,
  `user_id` char(36) NOT NULL,
  `campaign_id` char(36) DEFAULT NULL,
  `channel` enum('email','sms','push','whatsapp','in_app') NOT NULL,
  `status` enum('sent','delivered','failed','bounced','opened') NOT NULL,
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_templates`
--

CREATE TABLE `notification_templates` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `template_name` varchar(255) NOT NULL,
  `channel` enum('email','sms','push','whatsapp','in_app') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body_html` longtext DEFAULT NULL,
  `body_text` text DEFAULT NULL,
  `variables_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables_json`)),
  `language` enum('en','hi') DEFAULT 'en',
  `is_active` tinyint(1) DEFAULT 1,
  `category` enum('transactional','marketing','alert') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_templates`
--

INSERT INTO `notification_templates` (`id`, `template_name`, `channel`, `subject`, `body_html`, `body_text`, `variables_json`, `language`, `is_active`, `category`, `created_at`, `updated_at`) VALUES
('1972a1b7-60f3-11f1-b290-a0510b1a7448', 'fyrfdgch', 'email', '', '<p>httfhtug</p>', 'htfg', '[]', 'en', 1, 'transactional', '2026-06-05 15:27:40', '2026-06-05 15:28:02');

-- --------------------------------------------------------

--
-- Table structure for table `page_analytics`
--

CREATE TABLE `page_analytics` (
  `id` int(11) NOT NULL,
  `page_url` varchar(255) NOT NULL,
  `page_views` int(11) DEFAULT 0,
  `unique_visitors` int(11) DEFAULT 0,
  `bounce_rate` float DEFAULT 0,
  `avg_time_seconds` int(11) DEFAULT 0,
  `traffic_source` enum('organic','direct','referral','social','email','paid') DEFAULT NULL,
  `device_type` enum('desktop','mobile','tablet') DEFAULT NULL,
  `country` char(2) DEFAULT NULL,
  `date` date NOT NULL,
  `utm_campaign` varchar(100) DEFAULT NULL,
  `utm_medium` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partners`
--

CREATE TABLE `partners` (
  `id` varchar(36) NOT NULL,
  `partner_college_id` varchar(36) NOT NULL,
  `contact_person` varchar(150) NOT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `plan_id` varchar(36) DEFAULT NULL,
  `leads_quota` int(11) DEFAULT 0,
  `leads_used` int(11) DEFAULT 0,
  `contract_start` date DEFAULT NULL,
  `contract_end` date DEFAULT NULL,
  `account_manager_id` varchar(36) DEFAULT NULL,
  `onboarding_status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `status` enum('active','suspended','trial','churned') DEFAULT 'trial',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partner_content_requests`
--

CREATE TABLE `partner_content_requests` (
  `id` varchar(36) NOT NULL,
  `college_id` varchar(36) NOT NULL,
  `requested_by` varchar(36) NOT NULL,
  `content_type` enum('info','photo','placement','course','ranking') NOT NULL,
  `submitted_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`submitted_data`)),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` varchar(36) DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partner_users`
--

CREATE TABLE `partner_users` (
  `id` varchar(36) NOT NULL,
  `partner_id` varchar(36) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `login_email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `access_level` enum('read','write','admin') DEFAULT 'read',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` char(36) NOT NULL,
  `application_id` char(36) NOT NULL,
  `gateway` enum('razorpay','stripe','paytm','cashfree') NOT NULL,
  `gateway_txn_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` char(3) DEFAULT 'INR',
  `payment_status` enum('initiated','success','failed','refunded') DEFAULT 'initiated',
  `paid_at` timestamp NULL DEFAULT NULL,
  `refund_status` enum('none','requested','processed') DEFAULT 'none',
  `refund_amount` decimal(10,2) DEFAULT 0.00,
  `invoice_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `predictor_config`
--

CREATE TABLE `predictor_config` (
  `id` varchar(36) NOT NULL,
  `exam_id` varchar(36) NOT NULL,
  `model_weights` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`model_weights`)),
  `data_year` year(4) NOT NULL,
  `prediction_accuracy` float DEFAULT NULL,
  `min_score` int(11) DEFAULT 0,
  `max_score` int(11) NOT NULL,
  `category_adjustments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`category_adjustments`)),
  `state_quota_enabled` tinyint(1) DEFAULT 0,
  `home_state_quota_pct` float DEFAULT 0,
  `counselling_round_model` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `predictor_submissions`
--

CREATE TABLE `predictor_submissions` (
  `id` int(11) NOT NULL,
  `predictor_exam_id` char(36) DEFAULT NULL,
  `input_score` int(11) DEFAULT NULL,
  `input_rank` int(11) DEFAULT NULL,
  `input_category` enum('General','OBC','SC','ST','EWS','PwD') DEFAULT NULL,
  `input_state` varchar(100) DEFAULT NULL,
  `input_course_pref` char(36) DEFAULT NULL,
  `predicted_colleges` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`predicted_colleges`)),
  `confidence_score` float DEFAULT NULL,
  `model_year` year(4) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qa_reports`
--

CREATE TABLE `qa_reports` (
  `id` int(11) NOT NULL,
  `question_id` char(36) DEFAULT NULL,
  `answer_id` char(36) DEFAULT NULL,
  `report_reason` enum('spam','offensive','wrong_info','duplicate') NOT NULL,
  `reported_by` char(36) NOT NULL,
  `moderation_action` enum('approve','reject','remove','warn_user') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `question_text` text NOT NULL,
  `question_category` enum('admission','fees','placements','hostel','exams','general') NOT NULL,
  `related_college_id` char(36) DEFAULT NULL,
  `related_exam_id` char(36) DEFAULT NULL,
  `related_course_id` char(36) DEFAULT NULL,
  `asked_by` char(36) NOT NULL,
  `views` int(11) DEFAULT 0,
  `answer_count` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `status` enum('open','answered','closed') DEFAULT 'open',
  `trending_score` float DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rankings`
--

CREATE TABLE `rankings` (
  `id` int(11) NOT NULL,
  `ranking_body` varchar(150) NOT NULL,
  `ranking_year` year(4) NOT NULL,
  `category` varchar(150) NOT NULL,
  `college_id` char(36) NOT NULL,
  `rank_position` int(11) DEFAULT NULL,
  `rank_band` varchar(100) DEFAULT NULL,
  `score` float DEFAULT NULL,
  `sub_scores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sub_scores`)),
  `source_url` varchar(255) DEFAULT NULL,
  `published_date` date DEFAULT NULL,
  `previous_year_rank` int(11) DEFAULT NULL,
  `rank_delta` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `realtime_activity_feed`
--

CREATE TABLE `realtime_activity_feed` (
  `id` char(36) NOT NULL,
  `activity_type` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `entity_type` varchar(255) DEFAULT NULL,
  `entity_id` char(36) DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `realtime_metrics`
--

CREATE TABLE `realtime_metrics` (
  `id` char(36) NOT NULL,
  `metric_key` varchar(255) DEFAULT NULL,
  `metric_value` decimal(15,2) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `redirects`
--

CREATE TABLE `redirects` (
  `id` int(11) NOT NULL,
  `redirect_from` varchar(255) NOT NULL,
  `redirect_to` varchar(255) NOT NULL,
  `redirect_type` enum('301','302','410') DEFAULT '301',
  `redirect_reason` varchar(255) DEFAULT NULL,
  `hits` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `retention_cohorts`
--

CREATE TABLE `retention_cohorts` (
  `id` int(11) NOT NULL,
  `cohort_date` date NOT NULL,
  `users_in_cohort` int(11) DEFAULT 0,
  `day_1_retention` float DEFAULT 0,
  `day_7_retention` float DEFAULT 0,
  `day_30_retention` float DEFAULT 0,
  `segment` varchar(100) DEFAULT 'All Users',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `overall_rating` decimal(2,1) NOT NULL,
  `academics_rating` decimal(2,1) DEFAULT NULL,
  `faculty_rating` decimal(2,1) DEFAULT NULL,
  `placements_rating` decimal(2,1) DEFAULT NULL,
  `infrastructure_rating` decimal(2,1) DEFAULT NULL,
  `hostel_rating` decimal(2,1) DEFAULT NULL,
  `social_life_rating` decimal(2,1) DEFAULT NULL,
  `food_rating` decimal(2,1) DEFAULT NULL,
  `review_title` varchar(200) DEFAULT NULL,
  `review_body` text DEFAULT NULL,
  `pros` text DEFAULT NULL,
  `cons` text DEFAULT NULL,
  `batch_year` year(4) DEFAULT NULL,
  `course_id` char(36) DEFAULT NULL,
  `helpful_votes` int(11) DEFAULT 0,
  `media_urls` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`media_urls`)),
  `moderation_status` enum('pending','approved','rejected','escalated') DEFAULT 'pending',
  `moderation_reason` text DEFAULT NULL,
  `moderated_by` char(36) DEFAULT NULL,
  `moderated_at` timestamp NULL DEFAULT NULL,
  `is_verified_alumnus` tinyint(1) DEFAULT 0,
  `alumni_proof_url` varchar(255) DEFAULT NULL,
  `ai_spam_score` float DEFAULT 0,
  `ai_sentiment` enum('positive','negative','neutral','mixed') DEFAULT NULL,
  `reported_count` int(11) DEFAULT 0,
  `fraud_flag` tinyint(1) DEFAULT 0,
  `duplicate_score` float DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_meta`
--

CREATE TABLE `review_meta` (
  `id` char(36) NOT NULL,
  `review_id` char(36) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_fingerprint` varchar(255) DEFAULT NULL,
  `vpn_detected` tinyint(1) DEFAULT 0,
  `velocity_flag` tinyint(1) DEFAULT 0,
  `ai_model_version` varchar(50) DEFAULT NULL,
  `auto_action` enum('approve','hold','reject') DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `geo_country` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_reports`
--

CREATE TABLE `review_reports` (
  `id` char(36) NOT NULL,
  `review_id` char(36) NOT NULL,
  `reported_by` char(36) NOT NULL,
  `reason` enum('spam','fake','offensive','irrelevant','harassment') NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('open','resolved','dismissed') DEFAULT 'open',
  `resolved_by` char(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` varchar(36) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `permissions`, `created_at`, `updated_at`) VALUES
('169481d3-602f-11f1-9ea0-a0510b1a7448', 'gcvgfc', '{\"dashboard\":[\"read\",\"write\",\"delete\"],\"colleges\":[\"write\"]}', '2026-06-04 16:04:27', '2026-06-04 16:04:27'),
('a84ab069-5c3b-11f1-a48e-c8f7507a8de6', 'Super Administrator', '{\"all\": [\"read\", \"write\", \"delete\"]}', '2026-05-30 15:24:17', '2026-05-30 15:24:17');

-- --------------------------------------------------------

--
-- Table structure for table `role_dashboard_configs`
--

CREATE TABLE `role_dashboard_configs` (
  `id` char(36) NOT NULL,
  `role_name` varchar(255) DEFAULT NULL,
  `default_layout_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_layout_json`)),
  `default_widgets_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_widgets_json`)),
  `permissions_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_reports`
--

CREATE TABLE `saved_reports` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `report_name` varchar(255) DEFAULT NULL,
  `filters_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters_json`)),
  `widgets_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`widgets_json`)),
  `schedule_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`schedule_json`)),
  `export_format` enum('pdf','csv','xlsx') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scholarships`
--

CREATE TABLE `scholarships` (
  `id` char(36) NOT NULL,
  `scholarship_name` varchar(255) NOT NULL,
  `scholarship_slug` varchar(255) NOT NULL,
  `provider_name` varchar(255) DEFAULT NULL,
  `provider_logo` varchar(255) DEFAULT NULL,
  `scholarship_type` enum('government','private','college','abroad','sports','minority') DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `amount_type` enum('fixed','percentage','full_tuition','stipend') DEFAULT NULL,
  `eligibility_criteria` text DEFAULT NULL,
  `min_percentage` float DEFAULT NULL,
  `income_limit` decimal(12,2) DEFAULT NULL,
  `gender` enum('all','male','female','transgender') DEFAULT 'all',
  `category` enum('all','sc','st','obc','ews','minority','pwd') DEFAULT 'all',
  `state_specific` varchar(255) DEFAULT NULL,
  `course_levels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON array of course levels' CHECK (json_valid(`course_levels`)),
  `apply_start` date DEFAULT NULL,
  `apply_end` date DEFAULT NULL,
  `official_link` varchar(255) DEFAULT NULL,
  `renewable` tinyint(1) DEFAULT 0,
  `renewable_conditions` text DEFAULT NULL,
  `status` enum('active','expired','upcoming') DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scholarships`
--

INSERT INTO `scholarships` (`id`, `scholarship_name`, `scholarship_slug`, `provider_name`, `provider_logo`, `scholarship_type`, `amount`, `amount_type`, `eligibility_criteria`, `min_percentage`, `income_limit`, `gender`, `category`, `state_specific`, `course_levels`, `apply_start`, `apply_end`, `official_link`, `renewable`, `renewable_conditions`, `status`, `created_at`, `updated_at`) VALUES
('4973da69-2e9d-4a42-bdf6-aebc2e38cfae', 'fbvfbb hch hj', 'fbvfb', 'fvvf', 'uploads/scholarships/1780588564_138_pexels-poopfishsocks-340403551.jpg', 'government', 343.00, 'fixed', 'vfcbdv  cv  fdbvd df gf g gngn negf', 44, 455.00, 'all', 'all', 'bfbv', '[\"UG\",\"PG\"]', '2026-06-25', '2026-06-30', 'https://github.copm', 0, 'hkvehd cbj efhdj hxb', 'upcoming', '2026-06-04 15:56:04', '2026-06-04 15:56:16');

-- --------------------------------------------------------

--
-- Table structure for table `search_indices`
--

CREATE TABLE `search_indices` (
  `id` int(11) NOT NULL,
  `index_name` varchar(100) NOT NULL,
  `entity_type` enum('college','exam','course','article','scholarship') NOT NULL,
  `indexed_at` timestamp NULL DEFAULT NULL,
  `document_count` int(11) DEFAULT 0,
  `search_weight_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`search_weight_config`)),
  `facets_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`facets_config`)),
  `stop_words` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`stop_words`)),
  `language` enum('en','hi') DEFAULT 'en',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `search_queries`
--

CREATE TABLE `search_queries` (
  `id` char(36) NOT NULL,
  `query_text` varchar(255) NOT NULL,
  `results_count` int(11) DEFAULT 0,
  `clicked_result_id` char(36) DEFAULT NULL,
  `clicked_type` enum('college','exam','course','article') DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `user_id` char(36) DEFAULT NULL,
  `zero_results` tinyint(1) DEFAULT 0,
  `device_type` enum('mobile','desktop','tablet') DEFAULT NULL,
  `filters_applied` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters_applied`)),
  `search_timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `search_suggestions`
--

CREATE TABLE `search_suggestions` (
  `id` int(11) NOT NULL,
  `suggestion_text` varchar(255) NOT NULL,
  `suggestion_type` enum('college','exam','course','city','query') NOT NULL,
  `frequency` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `search_synonyms`
--

CREATE TABLE `search_synonyms` (
  `id` int(11) NOT NULL,
  `canonical` varchar(255) NOT NULL,
  `synonyms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`synonyms`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `search_trending`
--

CREATE TABLE `search_trending` (
  `id` int(11) NOT NULL,
  `query_text` varchar(255) NOT NULL,
  `trending_score` float DEFAULT 0,
  `trending_period` enum('daily','weekly','monthly') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seat_matrix`
--

CREATE TABLE `seat_matrix` (
  `id` int(11) NOT NULL,
  `college_id` char(36) NOT NULL,
  `course_id` char(36) NOT NULL,
  `category` enum('General','OBC','SC','ST','EWS','PwD','NRI','Mgmt') NOT NULL,
  `year` year(4) NOT NULL,
  `total_seats` int(11) NOT NULL DEFAULT 0,
  `filled_seats` int(11) DEFAULT 0,
  `source` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seo_meta`
--

CREATE TABLE `seo_meta` (
  `id` char(36) NOT NULL,
  `page_type` enum('college','university','exam','course','article','listing','tool') NOT NULL,
  `page_id` char(36) NOT NULL,
  `meta_title` varchar(70) DEFAULT NULL,
  `meta_description` varchar(160) DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `og_image_url` varchar(255) DEFAULT NULL,
  `canonical_url` varchar(255) DEFAULT NULL,
  `schema_markup` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`schema_markup`)),
  `noindex` tinyint(1) DEFAULT 0,
  `breadcrumb_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`breadcrumb_json`)),
  `og_title` varchar(255) DEFAULT NULL,
  `og_description` text DEFAULT NULL,
  `og_image` varchar(255) DEFAULT NULL,
  `schema_type` enum('College','Exam','Article','FAQPage','BreadcrumbList') DEFAULT NULL,
  `schema_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`schema_json`)),
  `primary_keyword` varchar(255) DEFAULT NULL,
  `keyword_density` float DEFAULT NULL,
  `robots_directive` enum('index_follow','noindex','nofollow') DEFAULT NULL,
  `hreflang` varchar(50) DEFAULT NULL,
  `last_crawled_at` timestamp NULL DEFAULT NULL,
  `google_index_status` enum('indexed','not_indexed','excluded') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seo_meta`
--

INSERT INTO `seo_meta` (`id`, `page_type`, `page_id`, `meta_title`, `meta_description`, `meta_keywords`, `og_image_url`, `canonical_url`, `schema_markup`, `noindex`, `breadcrumb_json`, `og_title`, `og_description`, `og_image`, `schema_type`, `schema_json`, `primary_keyword`, `keyword_density`, `robots_directive`, `hreflang`, `last_crawled_at`, `google_index_status`) VALUES
('00750aff-fff2-4f18-a092-9a44d8aaf499', 'article', 'ca435e52-4315-46eb-b0e5-ab09c1e0fb72', 'ghxvhdcxfvbeyux', 'tr6vyf yfcty tfgyftrghfng ytgfhvyg', NULL, NULL, NULL, NULL, 0, NULL, 'tyg tyfh ghuy', 'tdc tygnf', '/ADMISSION/uploads/article_og_1780659357_6a22b49d694a6.jpg', 'Article', NULL, 'tfttyg', NULL, NULL, NULL, NULL, NULL),
('37c2c8c5-dd86-46bf-a2a6-363eccef2490', 'college', '35a8b1c1-ce2c-4956-b88a-10867788637b', 'sfdghfngfh', 'grhnhdfv vf gbdf fd g g f f dff efef dv', NULL, 'uploads/college_og_1780554340_6a211a640b128.jpg', 'https://hllo.com', '{\n    \"@context\": \"https://schema.org\",\n    \"@type\": \"CollegeOrUniversity\",\n    \"name\": \"Madhav Arora\",\n    \"description\": \"yftyy vuyhv yhvygh\",\n    \"url\": \"https://ctgroup.in\",\n    \"telephone\": \"09877275894\",\n    \"address\": {\n        \"@type\": \"PostalAddress\",\n        \"streetAddress\": \"WARD 24, OSWALI MOHALLA\"\n    },\n    \"logo\": \"https://localhost/uploads/college_logo_1780553398_6a2116b61d252.jpg\"\n}', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('cfd8d410-944c-4968-b314-e6ad43e034dd', 'university', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', 'ghxvhdcxfvbeyux', 'x ghegxyugeh', 'dfdgvc,exvyhedgv', 'https://hllo.com', 'https://hllo.com', '{\r\n    \"@context\": \"https://schema.org\",\r\n    \"@type\": \"CollegeOrUniversity\",\r\n    \"name\": \"fgfhyryf\",\r\n    \"description\": \"vby gh yuhv yjhv ygf hg\",\r\n    \"url\": \"https://ctgroup.in\",\r\n    \"telephone\": \"9877275894\",\r\n    \"address\": {\r\n        \"@type\": \"PostalAddress\",\r\n        \"streetAddress\": \"WARD 24, OSWALI MOHALLA\"\r\n    },\r\n    \"logo\": \"https://localhost/uploads/universities/1780228437_logo_ChatGPT_Image_May_31__2026__03_28_08_PM.png\"\r\n}', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `seo_templates`
--

CREATE TABLE `seo_templates` (
  `id` int(11) NOT NULL,
  `template_name` varchar(100) NOT NULL,
  `template_slug_pattern` varchar(255) NOT NULL,
  `data_source` enum('colleges','exams','courses') NOT NULL,
  `title_template` varchar(255) NOT NULL,
  `description_template` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `pages_generated` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shortlists`
--

CREATE TABLE `shortlists` (
  `id` varchar(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `college_id` varchar(36) NOT NULL,
  `course_id` varchar(36) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `notification_pref` tinyint(1) DEFAULT 1,
  `priority` enum('dream','target','safe') DEFAULT 'target',
  `status` enum('active','applied','removed') DEFAULT 'active',
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shortlist_analytics`
--

CREATE TABLE `shortlist_analytics` (
  `id` varchar(36) NOT NULL,
  `date` date NOT NULL,
  `shortlist_count` int(11) DEFAULT 0,
  `avg_shortlists_per_user` float DEFAULT 0,
  `shortlist_to_apply_rate` float DEFAULT 0,
  `most_shortlisted_colleges` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`most_shortlisted_colleges`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sitemaps`
--

CREATE TABLE `sitemaps` (
  `id` int(11) NOT NULL,
  `sitemap_name` varchar(100) NOT NULL,
  `sitemap_url` varchar(255) NOT NULL,
  `sitemap_type` enum('colleges','exams','courses','articles','tools') NOT NULL,
  `last_generated_at` timestamp NULL DEFAULT NULL,
  `url_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `spam_detection_logs`
--

CREATE TABLE `spam_detection_logs` (
  `id` varchar(36) NOT NULL,
  `user_id` varchar(36) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `device_fingerprint` varchar(255) DEFAULT NULL,
  `duplicate_content_score` float DEFAULT 0,
  `velocity_flag` tinyint(1) DEFAULT 0,
  `vpn_detected` tinyint(1) DEFAULT 0,
  `proxy_detected` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `states`
--

CREATE TABLE `states` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `states`
--

INSERT INTO `states` (`id`, `name`) VALUES
(1, 'Andhra Pradesh'),
(2, 'Arunachal Pradesh'),
(3, 'Assam'),
(4, 'Bihar'),
(5, 'Chandigarh (UT)'),
(6, 'Chhattisgarh'),
(7, 'Dadra and Nagar Haveli (UT)'),
(8, 'Daman and Diu (UT)'),
(9, 'Delhi (NCT)'),
(10, 'Goa'),
(11, 'Gujarat'),
(12, 'Haryana'),
(13, 'Himachal Pradesh'),
(14, 'Jammu and Kashmir'),
(15, 'Jharkhand'),
(16, 'Karnataka'),
(17, 'Kerala'),
(18, 'Lakshadweep (UT)'),
(19, 'Madhya Pradesh'),
(20, 'Maharashtra'),
(21, 'Manipur'),
(22, 'Meghalaya'),
(23, 'Mizoram'),
(24, 'Nagaland'),
(25, 'Odisha'),
(26, 'Puducherry (UT)'),
(27, 'Punjab'),
(28, 'Rajasthan'),
(29, 'Sikkim'),
(30, 'Tamil Nadu'),
(31, 'Telangana'),
(32, 'Tripura'),
(33, 'Uttarakhand'),
(34, 'Uttar Pradesh'),
(35, 'West Bengal');

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `id` varchar(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `class_12_score` float DEFAULT NULL,
  `class_12_stream` enum('science','commerce','arts','vocational') DEFAULT NULL,
  `class_12_board` varchar(100) DEFAULT NULL,
  `preferred_courses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferred_courses`)),
  `target_year` year(4) DEFAULT NULL,
  `exam_scores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`exam_scores`)),
  `shortlisted_college_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`shortlisted_college_ids`)),
  `profile_completeness` tinyint(4) DEFAULT 0 CHECK (`profile_completeness` between 0 and 100),
  `avatar_url` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_profiles`
--

INSERT INTO `student_profiles` (`id`, `user_id`, `dob`, `gender`, `city`, `state`, `class_12_score`, `class_12_stream`, `class_12_board`, `preferred_courses`, `target_year`, `exam_scores`, `shortlisted_college_ids`, `profile_completeness`, `avatar_url`, `bio`, `created_at`, `updated_at`) VALUES
('f604bef7-4c31-455c-8506-4c059deb9021', '64e20c70-d8d7-402f-a700-53c759a659d4', NULL, NULL, 'Hoshiarpur', NULL, NULL, NULL, NULL, '[\"BCA\"]', NULL, NULL, NULL, 0, NULL, NULL, '2026-06-17 11:16:21', '2026-06-17 11:16:21');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `college_id` char(36) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `billing_cycle` enum('monthly','quarterly','annual') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `auto_renew` tinyint(1) DEFAULT 1,
  `status` enum('active','cancelled','expired','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_gateway_id` varchar(255) DEFAULT NULL,
  `next_billing_date` date DEFAULT NULL,
  `trial_end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_plans`
--

CREATE TABLE `subscription_plans` (
  `id` int(11) NOT NULL,
  `plan_name` varchar(255) NOT NULL,
  `plan_type` enum('basic','standard','premium','enterprise') NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscription_plans`
--

INSERT INTO `subscription_plans` (`id`, `plan_name`, `plan_type`, `price`, `features`, `created_at`, `updated_at`) VALUES
(1, 'Basic Plan', 'basic', 9.99, '[]', '2026-06-05 06:10:23', '2026-06-05 06:10:23'),
(3, 'Premium Plan', 'premium', 99.99, '[]', '2026-06-05 06:12:29', '2026-06-05 06:12:29'),
(4, 'ffbfd', 'enterprise', 0.24, NULL, '2026-06-05 06:18:47', '2026-06-05 06:18:47');

-- --------------------------------------------------------

--
-- Table structure for table `system_config`
--

CREATE TABLE `system_config` (
  `id` int(11) NOT NULL DEFAULT 1,
  `site_name` varchar(255) DEFAULT 'AdmissionSeason',
  `site_url` varchar(255) DEFAULT 'https://admissionseason.com',
  `logo_url` varchar(255) DEFAULT NULL,
  `favicon_url` varchar(255) DEFAULT NULL,
  `maintenance_mode` tinyint(1) DEFAULT 0,
  `maintenance_message` text DEFAULT NULL,
  `smtp_host` varchar(255) DEFAULT NULL,
  `smtp_port` int(11) DEFAULT 587,
  `smtp_user` varchar(255) DEFAULT NULL,
  `smtp_password` text DEFAULT NULL,
  `from_email` varchar(255) DEFAULT NULL,
  `from_name` varchar(255) DEFAULT NULL,
  `storage_provider` enum('s3','cloudinary','gcs','r2','local') DEFAULT 'local',
  `storage_bucket` varchar(255) DEFAULT NULL,
  `cdn_url` varchar(255) DEFAULT NULL,
  `payment_gateway` enum('razorpay','stripe','paytm','cashfree','none') DEFAULT 'none',
  `gateway_key` text DEFAULT NULL,
  `gateway_secret` text DEFAULT NULL,
  `gst_rate` float DEFAULT 0.18,
  `currency_default` char(3) DEFAULT 'INR',
  `openai_api_key` text DEFAULT NULL,
  `gemini_api_key` text DEFAULT NULL,
  `ai_provider` enum('openai','gemini','anthropic','ollama') DEFAULT 'openai',
  `mfa_enabled` tinyint(1) DEFAULT 1,
  `session_timeout_mins` int(11) DEFAULT 60,
  `max_login_attempts` int(11) DEFAULT 5,
  `ip_whitelist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ip_whitelist`)),
  `api_rate_limit_per_min` int(11) DEFAULT 60,
  `backup_schedule` varchar(100) DEFAULT '0 0 * * *',
  `backup_retention_days` int(11) DEFAULT 30,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` varchar(36) DEFAULT NULL
) ;

--
-- Dumping data for table `system_config`
--

INSERT INTO `system_config` (`id`, `site_name`, `site_url`, `logo_url`, `favicon_url`, `maintenance_mode`, `maintenance_message`, `smtp_host`, `smtp_port`, `smtp_user`, `smtp_password`, `from_email`, `from_name`, `storage_provider`, `storage_bucket`, `cdn_url`, `payment_gateway`, `gateway_key`, `gateway_secret`, `gst_rate`, `currency_default`, `openai_api_key`, `gemini_api_key`, `ai_provider`, `mfa_enabled`, `session_timeout_mins`, `max_login_attempts`, `ip_whitelist`, `api_rate_limit_per_min`, `backup_schedule`, `backup_retention_days`, `updated_at`, `updated_by`) VALUES
(1, 'AdmissionSeason', 'https://admissionseason.com', '', '', 0, 'fvfbrwgserg', '3rgrgr', 587, 'admin@example.com', 'password123', 'madhavarora132005@gmail.com', 'trqgre', 's3', 'twfrwvr', 'frgfrvfvfrv', 'stripe', NULL, NULL, 0.18, 'INR', NULL, NULL, 'openai', 1, 60, 5, NULL, 60, '0 0 * * *', 30, '2026-06-05 14:36:23', 'a85f4bf4-5c3b-11f1-a48e-c8f7507a8de6');

-- --------------------------------------------------------

--
-- Table structure for table `system_health`
--

CREATE TABLE `system_health` (
  `id` char(36) NOT NULL,
  `service_name` varchar(255) DEFAULT NULL,
  `status` enum('healthy','warning','critical','offline') DEFAULT NULL,
  `cpu_usage` float DEFAULT NULL,
  `memory_usage` float DEFAULT NULL,
  `response_time_ms` float DEFAULT NULL,
  `last_checked_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `tag_name` varchar(100) NOT NULL,
  `tag_slug` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tags`
--

INSERT INTO `tags` (`id`, `tag_name`, `tag_slug`, `created_at`) VALUES
(2, 'cygdhjcbf', 'cygdhjcbf', '2026-06-05 10:44:34');

-- --------------------------------------------------------

--
-- Table structure for table `universities`
--

CREATE TABLE `universities` (
  `id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `cover_image_url` varchar(255) DEFAULT NULL,
  `university_type` enum('govt','private','deemed','autonomous') DEFAULT NULL,
  `ownership` enum('central','state','private_trust','minority') DEFAULT NULL,
  `status` enum('active','pending','archived','rejected') DEFAULT 'pending',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_verified` tinyint(1) DEFAULT 0,
  `featured_order` int(11) DEFAULT 0,
  `ranking_nirf` int(11) DEFAULT NULL,
  `ranking_qs` int(11) DEFAULT NULL,
  `ranking_times` int(11) DEFAULT NULL,
  `city_id` int(11) DEFAULT NULL,
  `state_id` int(11) DEFAULT NULL,
  `established_year` year(4) DEFAULT NULL,
  `data_quality_score` tinyint(4) DEFAULT 0,
  `autonomous` tinyint(1) DEFAULT 0,
  `naac_grade` enum('A++','A+','A','B++','B+','B','C') DEFAULT NULL,
  `ugc_approved` tinyint(1) DEFAULT 0,
  `aicte_approved` tinyint(1) DEFAULT 0,
  `nba_approved` tinyint(1) DEFAULT 0,
  `total_students` int(11) DEFAULT 0,
  `total_faculty` int(11) DEFAULT 0,
  `campus_area_acres` float DEFAULT NULL,
  `verification_status` enum('unverified','pending','verified','disputed') DEFAULT 'unverified',
  `verified_by` char(36) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `duplicate_of` char(36) DEFAULT NULL,
  `import_batch_id` char(36) DEFAULT NULL,
  `last_data_audit_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `founded_year` year(4) DEFAULT NULL,
  `type_label` varchar(100) DEFAULT NULL,
  `campus_type` enum('urban','semi-urban','rural') DEFAULT NULL,
  `overall_rating_avg` float DEFAULT 0,
  `total_reviews` int(11) DEFAULT 0,
  `rating_distribution` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rating_distribution`)),
  `verified_reviews_count` int(11) DEFAULT 0,
  `publish_status` enum('draft','published','archived') DEFAULT 'draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `universities`
--

INSERT INTO `universities` (`id`, `name`, `slug`, `logo_url`, `cover_image_url`, `university_type`, `ownership`, `status`, `is_featured`, `is_verified`, `featured_order`, `ranking_nirf`, `ranking_qs`, `ranking_times`, `city_id`, `state_id`, `established_year`, `data_quality_score`, `autonomous`, `naac_grade`, `ugc_approved`, `aicte_approved`, `nba_approved`, `total_students`, `total_faculty`, `campus_area_acres`, `verification_status`, `verified_by`, `verified_at`, `rejection_reason`, `duplicate_of`, `import_batch_id`, `last_data_audit_at`, `created_at`, `updated_at`, `founded_year`, `type_label`, `campus_type`, `overall_rating_avg`, `total_reviews`, `rating_distribution`, `verified_reviews_count`, `publish_status`) VALUES
('02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', 'fgfhyryf', 'fgfhyryf', 'uploads/universities/1780228437_logo_ChatGPT_Image_May_31__2026__03_28_08_PM.png', 'uploads/universities/1780228437_cover_ChatGPT_Image_May_31__2026__03_28_08_PM.png', 'private', '', 'active', 1, 1, 2, 4, NULL, NULL, 293, 10, NULL, 2, 1, 'A+', 1, 1, 0, 20000, 200, 46, 'verified', 'a85f4bf4-5c3b-11f1-a48e-c8f7507a8de6', '2026-05-29 11:47:00', NULL, NULL, NULL, NULL, '2026-05-31 11:53:57', '2026-05-31 12:08:46', '2020', 'ebfuebf', 'urban', 0, 0, NULL, 0, 'published');

-- --------------------------------------------------------

--
-- Table structure for table `university_accreditations`
--

CREATE TABLE `university_accreditations` (
  `id` char(36) NOT NULL,
  `university_id` char(36) NOT NULL,
  `accreditation_body` varchar(255) NOT NULL,
  `accreditation_grade` varchar(50) DEFAULT NULL,
  `accreditation_year` year(4) DEFAULT NULL,
  `accreditation_valid_until` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `university_admissions`
--

CREATE TABLE `university_admissions` (
  `id` char(36) NOT NULL,
  `university_id` char(36) NOT NULL,
  `admission_process` text DEFAULT NULL,
  `accepted_exams` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`accepted_exams`)),
  `admission_start_date` date DEFAULT NULL,
  `admission_end_date` date DEFAULT NULL,
  `merit_based` tinyint(1) DEFAULT 0,
  `direct_admission` tinyint(1) DEFAULT 0,
  `management_quota_seats` int(11) DEFAULT 0,
  `nri_quota_seats` int(11) DEFAULT 0,
  `lateral_entry_available` tinyint(1) DEFAULT 0,
  `application_mode` enum('online','offline','both') DEFAULT NULL,
  `selection_criteria` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `university_admissions`
--

INSERT INTO `university_admissions` (`id`, `university_id`, `admission_process`, `accepted_exams`, `admission_start_date`, `admission_end_date`, `merit_based`, `direct_admission`, `management_quota_seats`, `nri_quota_seats`, `lateral_entry_available`, `application_mode`, `selection_criteria`) VALUES
('0bcf307d-7395-4d68-b6d9-46a0c34a3f82', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', 'ytfbtyghf vythgfgh vhjn vghf ghbc hg fch', '[\"gftg\",\"gcvf\"]', '2026-05-15', '2026-06-01', 1, 1, 25, 42, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `university_contacts`
--

CREATE TABLE `university_contacts` (
  `id` char(36) NOT NULL,
  `university_id` char(36) NOT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `latitude` decimal(9,6) DEFAULT NULL,
  `longitude` decimal(9,6) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `google_maps_embed_url` varchar(500) DEFAULT NULL,
  `nearest_railway_km` float DEFAULT NULL,
  `nearest_airport_km` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `university_contacts`
--

INSERT INTO `university_contacts` (`id`, `university_id`, `website_url`, `email`, `phone`, `address`, `latitude`, `longitude`, `pincode`, `google_maps_embed_url`, `nearest_railway_km`, `nearest_airport_km`) VALUES
('8d154b90-477d-4275-b431-2a150d3929f3', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', 'https://ctgroup.in', 'madhavarora132005@gmail.com', '9877275894', 'WARD 24, OSWALI MOHALLA', 23.000000, 55.000000, '305801', 'https://www.google.com/maps', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `university_content`
--

CREATE TABLE `university_content` (
  `id` char(36) NOT NULL,
  `university_id` char(36) NOT NULL,
  `about_text` longtext DEFAULT NULL,
  `highlights_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`highlights_json`)),
  `accreditations_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`accreditations_json`)),
  `rankings_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rankings_json`)),
  `awards_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`awards_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `university_content`
--

INSERT INTO `university_content` (`id`, `university_id`, `about_text`, `highlights_json`, `accreditations_json`, `rankings_json`, `awards_json`) VALUES
('553d9e97-81b4-45c1-a422-3351d51f0ed3', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', 'vby gh yuhv yjhv ygf hg', '[\"gfg hgvyh f\",\"jv huifvh\"]', '[\"vhg gycv\",\"cg cygh yh\"]', '{\"nrf\":\"12\"}', '[\"bv g v uyfv vyjh\"]');

-- --------------------------------------------------------

--
-- Table structure for table `university_courses`
--

CREATE TABLE `university_courses` (
  `id` char(36) NOT NULL,
  `university_id` char(36) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `course_level` enum('UG','PG','Diploma','PhD','Certificate') NOT NULL,
  `duration_years` tinyint(4) DEFAULT NULL,
  `total_fee` decimal(10,2) DEFAULT NULL,
  `semester_fee` decimal(10,2) DEFAULT NULL,
  `annual_fee` decimal(10,2) DEFAULT NULL,
  `seats_available` int(11) DEFAULT NULL,
  `fee_last_updated` date DEFAULT NULL,
  `specializations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specializations`)),
  `eligibility_criteria` text DEFAULT NULL,
  `application_fee` decimal(8,2) DEFAULT NULL,
  `emi_available` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `university_courses`
--

INSERT INTO `university_courses` (`id`, `university_id`, `course_name`, `course_level`, `duration_years`, `total_fee`, `semester_fee`, `annual_fee`, `seats_available`, `fee_last_updated`, `specializations`, `eligibility_criteria`, `application_fee`, `emi_available`) VALUES
('52f47f19-d023-446c-b37c-df7f01041edf', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', 'gcvhdhjcf', 'PG', 4, 22222.00, 2222.00, 22222.00, 200, NULL, '[\"dhjg\",\"djgeudhv\",\"dguiedui\"]', 'hdbhfjnc', 9978.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `university_cutoffs`
--

CREATE TABLE `university_cutoffs` (
  `id` char(36) NOT NULL,
  `university_id` char(36) NOT NULL,
  `exam_id` char(36) DEFAULT NULL,
  `course_id` char(36) DEFAULT NULL,
  `category` enum('General','OBC','SC','ST','EWS','PwD') NOT NULL,
  `year` year(4) NOT NULL,
  `opening_rank` int(11) DEFAULT NULL,
  `closing_rank` int(11) DEFAULT NULL,
  `round_number` tinyint(4) DEFAULT NULL,
  `quota` enum('AI','HS','OS','TF','PwD') DEFAULT NULL,
  `gender` enum('neutral','female_only') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `university_cutoffs`
--

INSERT INTO `university_cutoffs` (`id`, `university_id`, `exam_id`, `course_id`, `category`, `year`, `opening_rank`, `closing_rank`, `round_number`, `quota`, `gender`) VALUES
('10982cf5-5f6b-11f1-a3ef-c8f7507a8de6', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', 'c5c585e4-93a6-4176-b528-ddd5507e0711', '52f47f19-d023-446c-b37c-df7f01041edf', 'General', '2000', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `university_faculty`
--

CREATE TABLE `university_faculty` (
  `id` char(36) NOT NULL,
  `university_id` char(36) NOT NULL,
  `faculty_name` varchar(200) NOT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `experience_years` tinyint(4) DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `research_papers` int(11) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `phd_from` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `university_faculty`
--

INSERT INTO `university_faculty` (`id`, `university_id`, `faculty_name`, `designation`, `department`, `qualification`, `experience_years`, `photo_url`, `research_papers`, `linkedin_url`, `specialization`, `phd_from`) VALUES
('d61d5991-5fd6-11f1-a3ef-c8f7507a8de6', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', 'dvfvfv', 'vfcvcvfv', 'dvfvfv', 'dvfvfv', 2, 'uploads/faculty/1780551158_pexels-poopfishsocks-340403551.jpg', 2, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `university_faqs`
--

CREATE TABLE `university_faqs` (
  `id` char(36) NOT NULL,
  `university_id` char(36) NOT NULL,
  `question_text` text NOT NULL,
  `answer_text` text NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `schema_faq_enabled` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `university_hostels`
--

CREATE TABLE `university_hostels` (
  `id` char(36) NOT NULL,
  `university_id` char(36) NOT NULL,
  `hostel_available` tinyint(1) DEFAULT 0,
  `hostel_type` enum('boys','girls','both','co-ed') DEFAULT NULL,
  `hostel_capacity` int(11) DEFAULT NULL,
  `hostel_fee_annual` decimal(10,2) DEFAULT NULL,
  `mess_available` tinyint(1) DEFAULT 0,
  `mess_type` enum('veg','non-veg','both') DEFAULT NULL,
  `ac_available` tinyint(1) DEFAULT 0,
  `room_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`room_types`)),
  `security_features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`security_features`)),
  `laundry_available` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `university_hostels`
--

INSERT INTO `university_hostels` (`id`, `university_id`, `hostel_available`, `hostel_type`, `hostel_capacity`, `hostel_fee_annual`, `mess_available`, `mess_type`, `ac_available`, `room_types`, `security_features`, `laundry_available`) VALUES
('be5927ea-0673-42b7-98d2-dcd8266fc4f7', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', 1, 'boys', 57, 24455.00, 1, 'veg', 1, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `university_infrastructure`
--

CREATE TABLE `university_infrastructure` (
  `id` char(36) NOT NULL,
  `university_id` char(36) NOT NULL,
  `library` tinyint(1) DEFAULT 0,
  `library_books_count` int(11) DEFAULT NULL,
  `sports_facilities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sports_facilities`)),
  `labs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`labs`)),
  `auditorium` tinyint(1) DEFAULT 0,
  `auditorium_capacity` int(11) DEFAULT NULL,
  `cafeteria` tinyint(1) DEFAULT 0,
  `wifi` tinyint(1) DEFAULT 0,
  `wifi_speed_mbps` int(11) DEFAULT NULL,
  `medical_facility` tinyint(1) DEFAULT 0,
  `transport` tinyint(1) DEFAULT 0,
  `ev_charging` tinyint(1) DEFAULT 0,
  `solar_power` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `university_infrastructure`
--

INSERT INTO `university_infrastructure` (`id`, `university_id`, `library`, `library_books_count`, `sports_facilities`, `labs`, `auditorium`, `auditorium_capacity`, `cafeteria`, `wifi`, `wifi_speed_mbps`, `medical_facility`, `transport`, `ev_charging`, `solar_power`) VALUES
('2cfec23b-c870-496b-b5ac-2bac1ae33d9a', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', 1, NULL, '[\"vbcgyg v\",\"uchhcghv\",\"ytjkl\"]', '[\"fghtytg\",\"ftghhj\"]', 1, NULL, 1, 1, NULL, 1, 1, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `university_media`
--

CREATE TABLE `university_media` (
  `id` char(36) NOT NULL,
  `university_id` char(36) NOT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `cover_image_url` varchar(255) DEFAULT NULL,
  `image_type` enum('campus','lab','hostel','event','classroom') DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `video_type` enum('tour','placement','event','alumni_talk') DEFAULT NULL,
  `caption` varchar(300) DEFAULT NULL,
  `sort_order` tinyint(4) DEFAULT 0,
  `document_type` enum('brochure','prospectus','annual_report','ranking_cert') DEFAULT NULL,
  `document_url` varchar(255) DEFAULT NULL,
  `360_tour_url` varchar(255) DEFAULT NULL,
  `virtual_tour_enabled` tinyint(1) DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `university_media`
--

INSERT INTO `university_media` (`id`, `university_id`, `logo_url`, `cover_image_url`, `image_type`, `video_url`, `video_type`, `caption`, `sort_order`, `document_type`, `document_url`, `360_tour_url`, `virtual_tour_enabled`, `image_url`) VALUES
('0a41dcba-9c8a-4ed1-8e45-2b27259a325b', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 'https://github.com', 1, NULL),
('8c1516fd-2f8c-4c9d-817e-ad0f3b440b7b', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', NULL, NULL, 'campus', NULL, NULL, 'xcvbn', 0, NULL, NULL, NULL, 0, 'uploads/media/1780549568_pexels-poopfishsocks-340403551.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `university_placements`
--

CREATE TABLE `university_placements` (
  `id` char(36) NOT NULL,
  `university_id` char(36) NOT NULL,
  `placement_year` year(4) NOT NULL,
  `avg_package_lpa` decimal(5,2) DEFAULT NULL,
  `highest_package_lpa` decimal(5,2) DEFAULT NULL,
  `median_package_lpa` decimal(5,2) DEFAULT NULL,
  `placement_percentage` float DEFAULT NULL,
  `students_placed` int(11) DEFAULT NULL,
  `international_placements` int(11) DEFAULT NULL,
  `top_recruiters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`top_recruiters`)),
  `sector_wise_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sector_wise_json`)),
  `placement_report_pdf` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `university_scholarships`
--

CREATE TABLE `university_scholarships` (
  `id` char(36) NOT NULL,
  `university_id` char(36) NOT NULL,
  `scholarship_name` varchar(255) NOT NULL,
  `scholarship_type` enum('merit','need','sports','minority') DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `amount_type` enum('fixed','percentage','full_tuition') DEFAULT NULL,
  `eligibility_criteria` text DEFAULT NULL,
  `renewable` tinyint(1) DEFAULT 0,
  `apply_link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `university_scholarships`
--

INSERT INTO `university_scholarships` (`id`, `university_id`, `scholarship_name`, `scholarship_type`, `amount`, `amount_type`, `eligibility_criteria`, `renewable`, `apply_link`) VALUES
('730d619c-5fd7-11f1-a3ef-c8f7507a8de6', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', 'gvhghvghbvgh', 'merit', 30000.00, NULL, 'cvghcb yuh vhvcghcvbhj', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` varchar(36) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `auth_provider` enum('email','google','facebook','phone_otp') DEFAULT 'email',
  `status` enum('active','suspended','deleted','pending_verification') DEFAULT 'pending_verification',
  `role_id` varchar(36) DEFAULT NULL,
  `is_super_admin` tinyint(1) DEFAULT 0,
  `college_access` varchar(36) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `phone_verified` tinyint(1) DEFAULT 0,
  `mfa_enabled` tinyint(1) DEFAULT 0,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `login_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `auth_provider`, `status`, `role_id`, `is_super_admin`, `college_access`, `email_verified`, `phone_verified`, `mfa_enabled`, `last_login_at`, `last_login_ip`, `login_count`, `created_at`, `updated_at`) VALUES
('64e20c70-d8d7-402f-a700-53c759a659d4', 'Madhav Arora', 'madhavarora132005@gmail.com', '+919877275894', '$2y$10$6EPeZE02GC57pypaUAp.LezNs62SufTYnB4QZgKw41JOpG4Hg6Q8K', 'phone_otp', 'active', NULL, 0, NULL, 1, 1, 0, '2026-06-17 11:32:06', '::1', 2, '2026-06-17 11:16:21', '2026-06-17 11:40:43'),
('8b0478e7-602f-11f1-9ea0-a0510b1a7448', 'Madhav Arora', 'admi@example.com', NULL, '$2y$10$.P/prjvLjX3zn27/DW1j..roInHmRD3LUgJNgGpMyyhO9cj5/AJDa', 'email', 'suspended', '169481d3-602f-11f1-9ea0-a0510b1a7448', 1, NULL, 0, 0, 0, NULL, NULL, 0, '2026-06-04 16:07:43', '2026-06-04 16:07:58'),
('a85f4bf4-5c3b-11f1-a48e-c8f7507a8de6', 'Super Admin', 'admin@example.com', NULL, '$2y$10$RTMB7txQfeY7yMgStWBiLuNJUymVTRIXn45SAYuLYH.mXiKmWVLaG', 'email', 'active', 'a84ab069-5c3b-11f1-a48e-c8f7507a8de6', 1, NULL, 0, 0, 0, NULL, NULL, 0, '2026-05-30 15:24:17', '2026-06-04 15:57:46'),
('user-1234-uuid', 'Rahul Sharma', 'rahul.sharma@example.com', NULL, '$2y$10$Bk1vvPMZIOT6KwTEMLu1s.DoWuHmf/YNnY2wvG.7fTElDetlF4IXe', 'email', 'active', NULL, 0, NULL, 0, 0, 0, NULL, NULL, 0, '2026-06-15 04:15:11', '2026-06-15 04:15:11');

-- --------------------------------------------------------

--
-- Table structure for table `user_dashboard_widgets`
--

CREATE TABLE `user_dashboard_widgets` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `widget_id` char(36) NOT NULL,
  `position_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`position_json`)),
  `settings_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings_json`)),
  `is_hidden` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visa_guides`
--

CREATE TABLE `visa_guides` (
  `id` int(11) NOT NULL,
  `country` varchar(255) DEFAULT NULL,
  `visa_type` varchar(100) NOT NULL,
  `processing_time_days` int(11) DEFAULT NULL,
  `visa_fee_usd` decimal(8,2) DEFAULT NULL,
  `documents_required` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`documents_required`)),
  `success_tips` text DEFAULT NULL,
  `pswv_duration_months` int(11) DEFAULT NULL,
  `proof_of_funds_usd` decimal(10,2) DEFAULT NULL,
  `interview_required` tinyint(1) DEFAULT 0,
  `part_time_work_hours` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visa_guides`
--

INSERT INTO `visa_guides` (`id`, `country`, `visa_type`, `processing_time_days`, `visa_fee_usd`, `documents_required`, `success_tips`, `pswv_duration_months`, `proof_of_funds_usd`, `interview_required`, `part_time_work_hours`, `created_at`, `updated_at`) VALUES
(1, 'dhcvbdj', 'vdvfvf', 3, 455.00, NULL, '<p>vfebvfe f gfrgfv</p>', 4312, NULL, 0, NULL, '2026-06-04 16:32:10', '2026-06-04 16:32:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ab_tests`
--
ALTER TABLE `ab_tests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `actor_id` (`actor_id`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_alerts`
--
ALTER TABLE `admin_alerts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_widgets`
--
ALTER TABLE `admin_widgets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ad_products`
--
ALTER TABLE `ad_products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ai_chat_sessions`
--
ALTER TABLE `ai_chat_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`);

--
-- Indexes for table `ai_config`
--
ALTER TABLE `ai_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ai_dashboard_insights`
--
ALTER TABLE `ai_dashboard_insights`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `alert_rules`
--
ALTER TABLE `alert_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `analytics_reports`
--
ALTER TABLE `analytics_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `answered_by` (`answered_by`);

--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_number` (`application_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `counsellor_assigned` (`counsellor_assigned`);

--
-- Indexes for table `application_documents`
--
ALTER TABLE `application_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `article_slug` (`article_slug`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `editor_id` (`editor_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `article_categories`
--
ALTER TABLE `article_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_slug` (`category_slug`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `article_comments`
--
ALTER TABLE `article_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `article_id` (`article_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `article_revisions`
--
ALTER TABLE `article_revisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `article_id` (`article_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `article_tags`
--
ALTER TABLE `article_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_article_tag` (`article_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `audience_segments`
--
ALTER TABLE `audience_segments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `blacklisted_entities`
--
ALTER TABLE `blacklisted_entities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `entity_type` (`entity_type`,`entity_value`),
  ADD KEY `added_by` (`added_by`);

--
-- Indexes for table `calculator_config`
--
ALTER TABLE `calculator_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `calculator_sessions`
--
ALTER TABLE `calculator_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `chart_configurations`
--
ALTER TABLE `chart_configurations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `state_id` (`state_id`);

--
-- Indexes for table `colleges`
--
ALTER TABLE `colleges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `city_id` (`city_id`),
  ADD KEY `state_id` (`state_id`),
  ADD KEY `university_id` (`university_id`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `duplicate_of` (`duplicate_of`);

--
-- Indexes for table `college_accreditations`
--
ALTER TABLE `college_accreditations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `college_admissions`
--
ALTER TABLE `college_admissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `college_contacts`
--
ALTER TABLE `college_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `college_content`
--
ALTER TABLE `college_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `college_courses`
--
ALTER TABLE `college_courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `college_cutoffs`
--
ALTER TABLE `college_cutoffs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `college_faculty`
--
ALTER TABLE `college_faculty`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `college_faqs`
--
ALTER TABLE `college_faqs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `college_hostels`
--
ALTER TABLE `college_hostels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `college_infrastructure`
--
ALTER TABLE `college_infrastructure`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `college_media`
--
ALTER TABLE `college_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `college_placements`
--
ALTER TABLE `college_placements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `college_scholarships`
--
ALTER TABLE `college_scholarships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `commissions`
--
ALTER TABLE `commissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `compare_config`
--
ALTER TABLE `compare_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `compare_sessions`
--
ALTER TABLE `compare_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `share_token` (`share_token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `consultants`
--
ALTER TABLE `consultants`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_slug` (`course_slug`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `course_career_paths`
--
ALTER TABLE `course_career_paths`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `course_categories`
--
ALTER TABLE `course_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_slug` (`category_slug`),
  ADD KEY `parent_category_id` (`parent_category_id`);

--
-- Indexes for table `course_specializations`
--
ALTER TABLE `course_specializations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_course_id` (`parent_course_id`);

--
-- Indexes for table `dashboard_attachments`
--
ALTER TABLE `dashboard_attachments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dashboard_filters`
--
ALTER TABLE `dashboard_filters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dashboard_layouts`
--
ALTER TABLE `dashboard_layouts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dashboard_search_logs`
--
ALTER TABLE `dashboard_search_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dashboard_snapshots`
--
ALTER TABLE `dashboard_snapshots`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dashboard_widgets`
--
ALTER TABLE `dashboard_widgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `widget_key` (`widget_key`);

--
-- Indexes for table `dynamic_fields`
--
ALTER TABLE `dynamic_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `dynamic_modules`
--
ALTER TABLE `dynamic_modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `module_key` (`module_key`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `exam_slug` (`exam_slug`);

--
-- Indexes for table `exam_cutoffs`
--
ALTER TABLE `exam_cutoffs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `exam_dates`
--
ALTER TABLE `exam_dates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `exam_resources`
--
ALTER TABLE `exam_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `exam_syllabus`
--
ALTER TABLE `exam_syllabus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `experts`
--
ALTER TABLE `experts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exports`
--
ALTER TABLE `exports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `foreign_universities`
--
ALTER TABLE `foreign_universities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `university_slug` (`university_slug`);

--
-- Indexes for table `funnel_analytics`
--
ALTER TABLE `funnel_analytics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `internal_links`
--
ALTER TABLE `internal_links`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`);

--
-- Indexes for table `kpi_definitions`
--
ALTER TABLE `kpi_definitions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `metric_key` (`metric_key`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `lead_call_logs`
--
ALTER TABLE `lead_call_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_id` (`lead_id`),
  ADD KEY `called_by` (`called_by`);

--
-- Indexes for table `lead_credits`
--
ALTER TABLE `lead_credits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `media_files`
--
ALTER TABLE `media_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `moderation_queue`
--
ALTER TABLE `moderation_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reporter_id` (`reporter_id`),
  ADD KEY `moderator_id` (`moderator_id`),
  ADD KEY `escalated_to` (`escalated_to`);

--
-- Indexes for table `notification_campaigns`
--
ALTER TABLE `notification_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `template_id` (`template_id`),
  ADD KEY `audience_segment_id` (`audience_segment_id`);

--
-- Indexes for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `fk_notification_logs_user` (`user_id`);

--
-- Indexes for table `notification_templates`
--
ALTER TABLE `notification_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `page_analytics`
--
ALTER TABLE `page_analytics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `partners`
--
ALTER TABLE `partners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partner_college_id` (`partner_college_id`);

--
-- Indexes for table `partner_content_requests`
--
ALTER TABLE `partner_content_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `requested_by` (`requested_by`);

--
-- Indexes for table `partner_users`
--
ALTER TABLE `partner_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login_email` (`login_email`),
  ADD KEY `partner_id` (`partner_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `predictor_config`
--
ALTER TABLE `predictor_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `exam_id` (`exam_id`,`data_year`);

--
-- Indexes for table `predictor_submissions`
--
ALTER TABLE `predictor_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `predictor_exam_id` (`predictor_exam_id`),
  ADD KEY `input_course_pref` (`input_course_pref`);

--
-- Indexes for table `qa_reports`
--
ALTER TABLE `qa_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `answer_id` (`answer_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asked_by` (`asked_by`);

--
-- Indexes for table `rankings`
--
ALTER TABLE `rankings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `realtime_activity_feed`
--
ALTER TABLE `realtime_activity_feed`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `realtime_metrics`
--
ALTER TABLE `realtime_metrics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `redirects`
--
ALTER TABLE `redirects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `redirect_from` (`redirect_from`);

--
-- Indexes for table `retention_cohorts`
--
ALTER TABLE `retention_cohorts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `moderated_by` (`moderated_by`);

--
-- Indexes for table `review_meta`
--
ALTER TABLE `review_meta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `review_id` (`review_id`);

--
-- Indexes for table `review_reports`
--
ALTER TABLE `review_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `review_id` (`review_id`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `resolved_by` (`resolved_by`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `role_dashboard_configs`
--
ALTER TABLE `role_dashboard_configs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `saved_reports`
--
ALTER TABLE `saved_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `scholarships`
--
ALTER TABLE `scholarships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `scholarship_slug` (`scholarship_slug`);

--
-- Indexes for table `search_indices`
--
ALTER TABLE `search_indices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `index_name` (`index_name`);

--
-- Indexes for table `search_queries`
--
ALTER TABLE `search_queries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `search_suggestions`
--
ALTER TABLE `search_suggestions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `suggestion_text` (`suggestion_text`);

--
-- Indexes for table `search_synonyms`
--
ALTER TABLE `search_synonyms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `canonical` (`canonical`);

--
-- Indexes for table `search_trending`
--
ALTER TABLE `search_trending`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_trending` (`query_text`,`trending_period`);

--
-- Indexes for table `seat_matrix`
--
ALTER TABLE `seat_matrix`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `seat_matrix_ibfk_2` (`course_id`);

--
-- Indexes for table `seo_meta`
--
ALTER TABLE `seo_meta`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `seo_templates`
--
ALTER TABLE `seo_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shortlists`
--
ALTER TABLE `shortlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`college_id`,`course_id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `shortlist_analytics`
--
ALTER TABLE `shortlist_analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `date` (`date`);

--
-- Indexes for table `sitemaps`
--
ALTER TABLE `sitemaps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sitemap_url` (`sitemap_url`);

--
-- Indexes for table `spam_detection_logs`
--
ALTER TABLE `spam_detection_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `states`
--
ALTER TABLE `states`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_config`
--
ALTER TABLE `system_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_health`
--
ALTER TABLE `system_health`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tag_slug` (`tag_slug`);

--
-- Indexes for table `universities`
--
ALTER TABLE `universities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `city_id` (`city_id`),
  ADD KEY `state_id` (`state_id`);

--
-- Indexes for table `university_accreditations`
--
ALTER TABLE `university_accreditations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `university_id` (`university_id`);

--
-- Indexes for table `university_admissions`
--
ALTER TABLE `university_admissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `university_id` (`university_id`);

--
-- Indexes for table `university_contacts`
--
ALTER TABLE `university_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `university_id` (`university_id`);

--
-- Indexes for table `university_content`
--
ALTER TABLE `university_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `university_id` (`university_id`);

--
-- Indexes for table `university_courses`
--
ALTER TABLE `university_courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `university_id` (`university_id`);

--
-- Indexes for table `university_cutoffs`
--
ALTER TABLE `university_cutoffs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `university_id` (`university_id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `university_faculty`
--
ALTER TABLE `university_faculty`
  ADD PRIMARY KEY (`id`),
  ADD KEY `university_id` (`university_id`);

--
-- Indexes for table `university_faqs`
--
ALTER TABLE `university_faqs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `university_id` (`university_id`);

--
-- Indexes for table `university_hostels`
--
ALTER TABLE `university_hostels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `university_id` (`university_id`);

--
-- Indexes for table `university_infrastructure`
--
ALTER TABLE `university_infrastructure`
  ADD PRIMARY KEY (`id`),
  ADD KEY `university_id` (`university_id`);

--
-- Indexes for table `university_media`
--
ALTER TABLE `university_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `university_id` (`university_id`);

--
-- Indexes for table `university_placements`
--
ALTER TABLE `university_placements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `university_id` (`university_id`);

--
-- Indexes for table `university_scholarships`
--
ALTER TABLE `university_scholarships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `university_id` (`university_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `user_dashboard_widgets`
--
ALTER TABLE `user_dashboard_widgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `widget_id` (`widget_id`);

--
-- Indexes for table `visa_guides`
--
ALTER TABLE `visa_guides`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ab_tests`
--
ALTER TABLE `ab_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admin_widgets`
--
ALTER TABLE `admin_widgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ad_products`
--
ALTER TABLE `ad_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ai_chat_sessions`
--
ALTER TABLE `ai_chat_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_config`
--
ALTER TABLE `ai_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `analytics_reports`
--
ALTER TABLE `analytics_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `article_categories`
--
ALTER TABLE `article_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `article_comments`
--
ALTER TABLE `article_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `article_tags`
--
ALTER TABLE `article_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=723;

--
-- AUTO_INCREMENT for table `commissions`
--
ALTER TABLE `commissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `compare_config`
--
ALTER TABLE `compare_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `consultants`
--
ALTER TABLE `consultants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `foreign_universities`
--
ALTER TABLE `foreign_universities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `funnel_analytics`
--
ALTER TABLE `funnel_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `internal_links`
--
ALTER TABLE `internal_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lead_credits`
--
ALTER TABLE `lead_credits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `page_analytics`
--
ALTER TABLE `page_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `predictor_submissions`
--
ALTER TABLE `predictor_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qa_reports`
--
ALTER TABLE `qa_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rankings`
--
ALTER TABLE `rankings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `redirects`
--
ALTER TABLE `redirects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `retention_cohorts`
--
ALTER TABLE `retention_cohorts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `search_indices`
--
ALTER TABLE `search_indices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `search_suggestions`
--
ALTER TABLE `search_suggestions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `search_synonyms`
--
ALTER TABLE `search_synonyms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `search_trending`
--
ALTER TABLE `search_trending`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seat_matrix`
--
ALTER TABLE `seat_matrix`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `seo_templates`
--
ALTER TABLE `seo_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sitemaps`
--
ALTER TABLE `sitemaps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `states`
--
ALTER TABLE `states`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subscription_plans`
--
ALTER TABLE `subscription_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `visa_guides`
--
ALTER TABLE `visa_guides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`),
  ADD CONSTRAINT `answers_ibfk_2` FOREIGN KEY (`answered_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_4` FOREIGN KEY (`counsellor_assigned`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `application_documents`
--
ALTER TABLE `application_documents`
  ADD CONSTRAINT `application_documents_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `application_documents_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `articles_ibfk_2` FOREIGN KEY (`editor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `articles_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `article_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `article_categories`
--
ALTER TABLE `article_categories`
  ADD CONSTRAINT `article_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `article_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `article_comments`
--
ALTER TABLE `article_comments`
  ADD CONSTRAINT `article_comments_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `article_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `article_revisions`
--
ALTER TABLE `article_revisions`
  ADD CONSTRAINT `article_revisions_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `article_revisions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `article_tags`
--
ALTER TABLE `article_tags`
  ADD CONSTRAINT `article_tags_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `article_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `blacklisted_entities`
--
ALTER TABLE `blacklisted_entities`
  ADD CONSTRAINT `blacklisted_entities_ibfk_1` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `calculator_sessions`
--
ALTER TABLE `calculator_sessions`
  ADD CONSTRAINT `calculator_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `calculator_sessions_ibfk_2` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `cities_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `colleges`
--
ALTER TABLE `colleges`
  ADD CONSTRAINT `colleges_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `colleges_ibfk_2` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `colleges_ibfk_3` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `colleges_ibfk_4` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `colleges_ibfk_5` FOREIGN KEY (`duplicate_of`) REFERENCES `colleges` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `college_accreditations`
--
ALTER TABLE `college_accreditations`
  ADD CONSTRAINT `college_accreditations_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_admissions`
--
ALTER TABLE `college_admissions`
  ADD CONSTRAINT `college_admissions_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_contacts`
--
ALTER TABLE `college_contacts`
  ADD CONSTRAINT `college_contacts_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_content`
--
ALTER TABLE `college_content`
  ADD CONSTRAINT `college_content_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_courses`
--
ALTER TABLE `college_courses`
  ADD CONSTRAINT `college_courses_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_cutoffs`
--
ALTER TABLE `college_cutoffs`
  ADD CONSTRAINT `college_cutoffs_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `college_cutoffs_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `college_cutoffs_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `college_courses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `college_faculty`
--
ALTER TABLE `college_faculty`
  ADD CONSTRAINT `college_faculty_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_faqs`
--
ALTER TABLE `college_faqs`
  ADD CONSTRAINT `college_faqs_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_hostels`
--
ALTER TABLE `college_hostels`
  ADD CONSTRAINT `college_hostels_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_infrastructure`
--
ALTER TABLE `college_infrastructure`
  ADD CONSTRAINT `college_infrastructure_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_media`
--
ALTER TABLE `college_media`
  ADD CONSTRAINT `college_media_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_placements`
--
ALTER TABLE `college_placements`
  ADD CONSTRAINT `college_placements_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_scholarships`
--
ALTER TABLE `college_scholarships`
  ADD CONSTRAINT `college_scholarships_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `compare_sessions`
--
ALTER TABLE `compare_sessions`
  ADD CONSTRAINT `compare_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `course_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `course_career_paths`
--
ALTER TABLE `course_career_paths`
  ADD CONSTRAINT `course_career_paths_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_categories`
--
ALTER TABLE `course_categories`
  ADD CONSTRAINT `course_categories_ibfk_1` FOREIGN KEY (`parent_category_id`) REFERENCES `course_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `course_specializations`
--
ALTER TABLE `course_specializations`
  ADD CONSTRAINT `course_specializations_ibfk_1` FOREIGN KEY (`parent_course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dynamic_fields`
--
ALTER TABLE `dynamic_fields`
  ADD CONSTRAINT `dynamic_fields_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `dynamic_modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_cutoffs`
--
ALTER TABLE `exam_cutoffs`
  ADD CONSTRAINT `exam_cutoffs_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_cutoffs_ibfk_2` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_cutoffs_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_dates`
--
ALTER TABLE `exam_dates`
  ADD CONSTRAINT `exam_dates_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_resources`
--
ALTER TABLE `exam_resources`
  ADD CONSTRAINT `exam_resources_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_syllabus`
--
ALTER TABLE `exam_syllabus`
  ADD CONSTRAINT `exam_syllabus_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leads`
--
ALTER TABLE `leads`
  ADD CONSTRAINT `leads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leads_ibfk_2` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leads_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leads_ibfk_4` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lead_call_logs`
--
ALTER TABLE `lead_call_logs`
  ADD CONSTRAINT `lead_call_logs_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lead_call_logs_ibfk_2` FOREIGN KEY (`called_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `media_files`
--
ALTER TABLE `media_files`
  ADD CONSTRAINT `media_files_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `moderation_queue`
--
ALTER TABLE `moderation_queue`
  ADD CONSTRAINT `moderation_queue_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `moderation_queue_ibfk_2` FOREIGN KEY (`moderator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `moderation_queue_ibfk_3` FOREIGN KEY (`escalated_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notification_campaigns`
--
ALTER TABLE `notification_campaigns`
  ADD CONSTRAINT `notification_campaigns_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `notification_templates` (`id`),
  ADD CONSTRAINT `notification_campaigns_ibfk_2` FOREIGN KEY (`audience_segment_id`) REFERENCES `audience_segments` (`id`);

--
-- Constraints for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD CONSTRAINT `fk_notification_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `notification_logs_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `notification_campaigns` (`id`);

--
-- Constraints for table `partners`
--
ALTER TABLE `partners`
  ADD CONSTRAINT `partners_ibfk_1` FOREIGN KEY (`partner_college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `partner_content_requests`
--
ALTER TABLE `partner_content_requests`
  ADD CONSTRAINT `partner_content_requests_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `partner_content_requests_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `partner_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `partner_users`
--
ALTER TABLE `partner_users`
  ADD CONSTRAINT `partner_users_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `predictor_config`
--
ALTER TABLE `predictor_config`
  ADD CONSTRAINT `predictor_config_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `predictor_submissions`
--
ALTER TABLE `predictor_submissions`
  ADD CONSTRAINT `predictor_submissions_ibfk_1` FOREIGN KEY (`predictor_exam_id`) REFERENCES `exams` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `predictor_submissions_ibfk_2` FOREIGN KEY (`input_course_pref`) REFERENCES `courses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `qa_reports`
--
ALTER TABLE `qa_reports`
  ADD CONSTRAINT `qa_reports_ibfk_1` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `qa_reports_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`),
  ADD CONSTRAINT `qa_reports_ibfk_3` FOREIGN KEY (`answer_id`) REFERENCES `answers` (`id`);

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`asked_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `rankings`
--
ALTER TABLE `rankings`
  ADD CONSTRAINT `rankings_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reviews_ibfk_4` FOREIGN KEY (`moderated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `review_meta`
--
ALTER TABLE `review_meta`
  ADD CONSTRAINT `review_meta_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_reports`
--
ALTER TABLE `review_reports`
  ADD CONSTRAINT `review_reports_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_reports_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_reports_ibfk_3` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `search_queries`
--
ALTER TABLE `search_queries`
  ADD CONSTRAINT `search_queries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `seat_matrix`
--
ALTER TABLE `seat_matrix`
  ADD CONSTRAINT `seat_matrix_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seat_matrix_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `college_courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shortlists`
--
ALTER TABLE `shortlists`
  ADD CONSTRAINT `shortlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shortlists_ibfk_2` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `spam_detection_logs`
--
ALTER TABLE `spam_detection_logs`
  ADD CONSTRAINT `spam_detection_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `universities`
--
ALTER TABLE `universities`
  ADD CONSTRAINT `universities_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `universities_ibfk_2` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `university_accreditations`
--
ALTER TABLE `university_accreditations`
  ADD CONSTRAINT `university_accreditations_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `university_admissions`
--
ALTER TABLE `university_admissions`
  ADD CONSTRAINT `university_admissions_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `university_contacts`
--
ALTER TABLE `university_contacts`
  ADD CONSTRAINT `university_contacts_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `university_content`
--
ALTER TABLE `university_content`
  ADD CONSTRAINT `university_content_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `university_courses`
--
ALTER TABLE `university_courses`
  ADD CONSTRAINT `university_courses_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `university_cutoffs`
--
ALTER TABLE `university_cutoffs`
  ADD CONSTRAINT `university_cutoffs_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `university_cutoffs_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `university_cutoffs_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `university_courses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `university_faculty`
--
ALTER TABLE `university_faculty`
  ADD CONSTRAINT `university_faculty_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `university_faqs`
--
ALTER TABLE `university_faqs`
  ADD CONSTRAINT `university_faqs_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `university_hostels`
--
ALTER TABLE `university_hostels`
  ADD CONSTRAINT `university_hostels_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `university_infrastructure`
--
ALTER TABLE `university_infrastructure`
  ADD CONSTRAINT `university_infrastructure_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `university_media`
--
ALTER TABLE `university_media`
  ADD CONSTRAINT `university_media_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `university_placements`
--
ALTER TABLE `university_placements`
  ADD CONSTRAINT `university_placements_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `university_scholarships`
--
ALTER TABLE `university_scholarships`
  ADD CONSTRAINT `university_scholarships_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_dashboard_widgets`
--
ALTER TABLE `user_dashboard_widgets`
  ADD CONSTRAINT `user_dashboard_widgets_ibfk_1` FOREIGN KEY (`widget_id`) REFERENCES `dashboard_widgets` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
