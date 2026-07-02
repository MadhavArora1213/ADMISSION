-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 02, 2026 at 11:47 AM
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
CREATE DATABASE IF NOT EXISTS `admission` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `admission`;

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

INSERT INTO `activity_log` (`id`, `activity_type`, `actor_id`, `entity_type`, `entity_id`, `meta_json`, `created_at`) VALUES
(1, 'create', 'a85f4bf4-5c3b-11f1-a48e-c8f7507a8de6', 'lead', '1bde368e-5a5c-11f1-a611-c8f7507a8de6', '{\"name\": \"Rohan Sharma\", \"course\": \"B.Tech\"}', '2026-05-28 06:11:42'),
(2, 'update', 'a85f4bf4-5c3b-11f1-a48e-c8f7507a8de6', 'college', '1bde6172-5a5c-11f1-a611-c8f7507a8de6', '{\"field\": \"status\", \"old\": \"pending\", \"new\": \"published\"}', '2026-05-28 06:11:42'),
(3, 'create', '64e20c70-d8d7-402f-a700-53c759a659d4', 'college', 'col00001-0000-0000-0000-000000000001', '{\"application_number\":\"APP-20260619-261A9E\",\"student\":\"Madhav Arora\"}', '2026-06-19 16:32:34'),
(4, 'create', '64e20c70-d8d7-402f-a700-53c759a659d4', 'college', 'col00001-0000-0000-0000-000000000004', '{\"application_number\":\"APP-20260620-B34DB7\",\"student\":\"Madhav Arora\"}', '2026-06-20 03:40:59'),
(5, 'create', '64e20c70-d8d7-402f-a700-53c759a659d4', 'college', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', '{\"application_number\":\"APP-20260628-5461BE\",\"student\":\"Madhav Arora\"}', '2026-06-28 11:39:49');

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

--
-- Dumping data for table `admin_alerts`
--

INSERT INTO `admin_alerts` (`id`, `alert_type`, `title`, `message`, `severity`, `source_module`, `entity_type`, `entity_id`, `status`, `assigned_to`, `resolution_notes`, `metadata_json`, `resolved_by`, `created_at`, `resolved_at`) VALUES
('ac46b4f6-730c-11f1-81b7-a0510b1a7448', 'system_maintenance', 'Scheduled Maintenance Window', 'System maintenance is scheduled for Saturday 2:00 AM - 4:00 AM IST. All services may experience brief downtime.', 'low', 'system', NULL, NULL, 'open', NULL, NULL, NULL, NULL, '2026-06-28 15:15:45', NULL),
('ac4759e3-730c-11f1-81b7-a0510b1a7448', 'high_lead_volume', 'Lead Volume Spike Detected', 'Lead volume has increased by 45% compared to the weekly average. Marketing campaigns may be performing well.', 'medium', 'leads', NULL, NULL, 'open', NULL, NULL, NULL, NULL, '2026-06-28 13:15:45', NULL),
('ac476eff-730c-11f1-81b7-a0510b1a7448', 'pending_approval', 'New Consultant Registration', 'A new education consultant has registered and is pending verification of credentials and business license.', 'medium', 'consultants', NULL, NULL, 'open', NULL, NULL, NULL, NULL, '2026-06-28 10:15:45', NULL),
('ac477758-730c-11f1-81b7-a0510b1a7448', 'data_quality', 'Missing University Logos', 'Several universities are missing logo images. This affects the presentation on the public website.', 'low', 'universities', NULL, NULL, 'open', NULL, NULL, NULL, NULL, '2026-06-28 04:15:45', NULL),
('ac4778d3-730c-11f1-81b7-a0510b1a7448', 'subscription_expiring', 'Annual Plan Renewal Reminder', 'Multiple college partner annual subscriptions are approaching their renewal dates in the next 30 days.', 'high', 'subscriptions', NULL, NULL, 'open', NULL, NULL, NULL, NULL, '2026-06-27 16:15:45', NULL),
('ac4779bd-730c-11f1-81b7-a0510b1a7448', 'failed_payments', 'Payment Gateway Timeout', 'The payment gateway reported intermittent timeouts between 11:00-11:30 AM. No transactions were permanently lost.', 'high', 'payments', NULL, NULL, 'acknowledged', NULL, NULL, NULL, NULL, '2026-06-26 16:15:45', NULL),
('ac477b96-730c-11f1-81b7-a0510b1a7448', 'exam_deadline', 'JEE Main Registration Closing', 'JEE Main 2025 registration deadline is approaching. Students should be notified via push notifications.', 'critical', 'exams', NULL, NULL, 'acknowledged', NULL, NULL, NULL, NULL, '2026-06-26 16:15:45', NULL),
('ac477d0d-730c-11f1-81b7-a0510b1a7448', 'spam_detected', 'Spam Comment Activity', 'Automated spam bot detected posting comments on 3 college pages. Rate limiting has been applied automatically.', 'high', 'security', NULL, NULL, 'resolved', NULL, NULL, NULL, NULL, '2026-06-25 16:15:45', NULL),
('ac477f37-730c-11f1-81b7-a0510b1a7448', 'data_quality', 'Duplicate College Entries Found', 'AI system flagged 12 potential duplicate college entries that need manual review and merging.', 'medium', 'ai_engine', NULL, NULL, 'open', NULL, NULL, NULL, NULL, '2026-06-25 16:15:45', NULL),
('ac4780ff-730c-11f1-81b7-a0510b1a7448', 'suspicious_login', 'Unusual Admin Access Pattern', 'Admin account was accessed from a new IP address in a different geographic region. Verify with the account owner.', 'critical', 'security', NULL, NULL, 'open', NULL, NULL, NULL, NULL, '2026-06-24 16:15:45', NULL),
('ac4782ba-730c-11f1-81b7-a0510b1a7448', 'failed_payments', 'Recurring Payment Failures', '3 subscriptions from the same partner failed payment processing consecutively. Partner may need to update card details.', 'high', 'payments', NULL, NULL, 'open', NULL, NULL, NULL, NULL, '2026-06-23 16:15:45', NULL),
('ac479c0f-730c-11f1-81b7-a0510b1a7448', 'partner_contract', 'Partner Agreement Expiration', 'TechEd Solutions partnership agreement expires next week. Initiate renewal discussion with account manager.', 'medium', 'partners', NULL, NULL, 'open', NULL, NULL, NULL, NULL, '2026-06-23 16:15:45', NULL),
('ac479d9e-730c-11f1-81b7-a0510b1a7448', 'overdue_invoices', 'Outstanding Invoice Follow-up', 'Invoice INV-2025-0042 from Sunrise College is 15 days overdue. Payment reminder sent twice already.', 'high', 'invoices', NULL, NULL, 'acknowledged', NULL, NULL, NULL, NULL, '2026-06-22 16:15:45', NULL),
('ac479e36-730c-11f1-81b7-a0510b1a7448', 'pending_reviews', 'Review Moderation Queue Growing', 'Review moderation queue has crossed 10 pending items. Allocate additional moderator time.', 'medium', 'reviews', NULL, NULL, 'resolved', NULL, NULL, NULL, NULL, '2026-06-21 16:15:45', NULL),
('ac479f4d-730c-11f1-81b7-a0510b1a7448', 'moderation_backlog', 'Community Q&A Moderation Backlog', 'Questions and answers in the community section need moderation. 8 items pending review.', 'medium', 'community', NULL, NULL, 'resolved', NULL, NULL, NULL, NULL, '2026-06-20 16:15:45', NULL);

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

--
-- Dumping data for table `alert_rules`
--

INSERT INTO `alert_rules` (`id`, `rule_name`, `module_name`, `condition_json`, `severity`, `notification_channels`, `is_active`, `created_at`) VALUES
('c61c1e2b-730c-11f1-81b7-a0510b1a7448', 'Failed Login Threshold', 'security', '{\"table\":\"audit_logs\",\"condition\":\"audit_action=login_failed\",\"threshold\":5,\"window\":\"1h\"}', 'critical', '[\"email\",\"dashboard\"]', 1, '2026-06-28 16:16:28'),
('c61c2841-730c-11f1-81b7-a0510b1a7448', 'Pending Review Alert', 'reviews', '{\"table\":\"reviews\",\"condition\":\"moderation_status=pending\",\"threshold\":10,\"window\":\"24h\"}', 'high', '[\"dashboard\"]', 1, '2026-06-28 16:16:28'),
('c61c2a03-730c-11f1-81b7-a0510b1a7448', 'Payment Failure Spike', 'payments', '{\"table\":\"payments\",\"condition\":\"payment_status=failed\",\"threshold\":3,\"window\":\"7d\"}', 'high', '[\"email\",\"dashboard\"]', 1, '2026-06-28 16:16:28'),
('c61c2ae0-730c-11f1-81b7-a0510b1a7448', 'Lead Volume Anomaly', 'leads', '{\"table\":\"leads\",\"condition\":\"daily_count\",\"threshold\":100,\"window\":\"1d\"}', 'medium', '[\"dashboard\"]', 1, '2026-06-28 16:16:28'),
('c61c2b93-730c-11f1-81b7-a0510b1a7448', 'College Account Pending', 'college_accounts', '{\"table\":\"college_accounts\",\"condition\":\"status=pending\",\"threshold\":1,\"window\":\"48h\"}', 'medium', '[\"dashboard\"]', 1, '2026-06-28 16:16:28'),
('c61c6283-730c-11f1-81b7-a0510b1a7448', 'Subscription Expiration Warning', 'subscriptions', '{\"table\":\"subscriptions\",\"condition\":\"expiring_soon\",\"threshold\":1,\"window\":\"7d\"}', 'medium', '[\"email\",\"dashboard\"]', 1, '2026-06-28 16:16:28'),
('c61c63ca-730c-11f1-81b7-a0510b1a7448', 'System Health Critical', 'system_health', '{\"table\":\"system_health\",\"condition\":\"status_in_critical_offline\",\"threshold\":1,\"window\":\"5m\"}', 'critical', '[\"email\",\"sms\",\"dashboard\"]', 1, '2026-06-28 16:16:28'),
('c61c649d-730c-11f1-81b7-a0510b1a7448', 'Spam Detection Surge', 'security', '{\"table\":\"spam_detection_logs\",\"condition\":\"daily_count\",\"threshold\":20,\"window\":\"1d\"}', 'high', '[\"email\",\"dashboard\"]', 1, '2026-06-28 16:16:28'),
('c61c6545-730c-11f1-81b7-a0510b1a7448', 'Overdue Invoice Alert', 'invoices', '{\"table\":\"invoices\",\"condition\":\"payment_overdue\",\"threshold\":1,\"window\":\"1d\"}', 'high', '[\"email\",\"dashboard\"]', 1, '2026-06-28 16:16:28'),
('c61c65df-730c-11f1-81b7-a0510b1a7448', 'Duplicate Content Detection', 'ai_engine', '{\"table\":\"admin_alerts\",\"condition\":\"type_data_quality\",\"threshold\":1,\"window\":\"7d\"}', 'medium', '[\"dashboard\"]', 1, '2026-06-28 16:16:28');

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
  `dislike_count` int(11) DEFAULT 0,
  `is_accepted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `answers`
--

INSERT INTO `answers` (`id`, `question_id`, `answer_text`, `answered_by`, `is_expert_answer`, `is_verified_alumnus`, `upvotes`, `dislike_count`, `is_accepted`, `created_at`, `updated_at`) VALUES
('8f24070d-1941-4f97-88f6-d902d12d6475', 'que00001-0000-0000-0000-000000000001', '<p>dedededefd4d4</p>', 'user-1234-uuid', 0, 0, 0, 0, 0, '2026-06-22 06:50:16', '2026-06-22 06:50:16'),
('ans00001-0000-0000-0000-000000000001', 'que00001-0000-0000-0000-000000000001', 'For General category, the JEE Advanced closing rank for B.Tech CSE at IIT Bombay was around 105 in 2024 (Round 7). The opening rank was 1. For female-only seats, the closing rank was around 150.', 'usr00001-0000-0000-0000-000000000004', 1, 0, 47, 0, 1, '2026-06-19 06:04:41', '2026-06-22 10:32:57'),
('ans00001-0000-0000-0000-000000000002', 'que00001-0000-0000-0000-000000000001', 'The cutoff varies each year based on difficulty level and number of candidates. In 2023, the closing rank was 110 for General category. Check JoSAA website for exact cutoffs after each round.', 'usr00001-0000-0000-0000-000000000005', 0, 1, 32, 0, 0, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('ans00001-0000-0000-0000-000000000003', 'que00001-0000-0000-0000-000000000001', 'For OBC category, the closing rank is around 320, for SC it is around 680, for ST around 1200, and for EWS around 180. These are approximate values for 2024.', 'usr00001-0000-0000-0000-000000000002', 0, 0, 28, 0, 0, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('ans00001-0000-0000-0000-000000000004', 'que00001-0000-0000-0000-000000000002', 'IIT Delhi provides hostel accommodation to all first-year students. There are separate hostels for boys and girls. Rooms are generally double or triple sharing. Each hostel has a mess, common room, and Wi-Fi facility.', 'usr00001-0000-0000-0000-000000000003', 0, 1, 38, 0, 1, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('ans00001-0000-0000-0000-000000000005', 'que00001-0000-0000-0000-000000000002', 'First year students get triple sharing rooms. From second year onwards, based on CGPA, you can get double or single rooms. Hostel fee is included in the annual fee.', 'usr00001-0000-0000-0000-000000000005', 0, 0, 22, 0, 0, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('ans00001-0000-0000-0000-000000000006', 'que00001-0000-0000-0000-000000000003', 'NIT Trichy ECE department has good placements. In 2024, the average package for ECE was around Rs. 12 LPA with highest package of Rs. 45 LPA. Top recruiters include Qualcomm, Texas Instruments, and Samsung.', 'usr00001-0000-0000-0000-000000000004', 0, 1, 30, 0, 1, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('ans00001-0000-0000-0000-000000000007', 'que00001-0000-0000-0000-000000000003', 'Around 85% of ECE students get placed through campus recruitment. Many students also go for higher studies at IITs and foreign universities. The placement cell provides training and mock interviews.', 'usr00001-0000-0000-0000-000000000001', 0, 0, 18, 0, 0, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('ans00001-0000-0000-0000-000000000008', 'que00001-0000-0000-0000-000000000004', 'BITS Pilani offers excellent ROI. The Practice School program provides 6 months of paid internship, which offsets a significant portion of the fees. Average placement of Rs. 20+ LPA justifies the investment.', 'usr00001-0000-0000-0000-000000000005', 1, 1, 52, 0, 1, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('ans00001-0000-0000-0000-000000000009', 'que00001-0000-0000-0000-000000000004', 'BITS Pilani offers merit scholarships covering up to 80% tuition for top BITSAT performers. The need-based financial aid also helps economically weaker students. The industry connections ensure good placements.', 'usr00001-0000-0000-0000-000000000002', 0, 0, 35, 0, 0, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('ans00001-0000-0000-0000-000000000010', 'que00001-0000-0000-0000-000000000004', 'While IITs have lower fees, BITS Pilani compensates with the Practice School program, international exposure, and industry-aligned curriculum. Many BITS graduates end up in top companies alongside IIT graduates.', 'usr00001-0000-0000-0000-000000000003', 0, 0, 28, 0, 0, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('ans00001-0000-0000-0000-000000000011', 'que00001-0000-0000-0000-000000000005', 'VITEEE is a computer-based online test with 125 multiple choice questions. Duration is 2.5 hours. Physics (35), Chemistry (35), Maths/Biology (40), Aptitude (15). Difficulty level is moderate.', 'usr00001-0000-0000-0000-000000000001', 0, 1, 40, 0, 1, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('ans00001-0000-0000-0000-000000000012', 'que00001-0000-0000-0000-000000000005', 'VITEEE is easier compared to JEE Main. Focus on NCERT concepts and practice previous year papers. The exam is conducted in multiple slots from April to May. You can choose your preferred slot.', 'usr00001-0000-0000-0000-000000000004', 0, 0, 25, 0, 0, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('ans00001-0000-0000-0000-000000000013', 'que00001-0000-0000-0000-000000000006', 'The average package for B.Tech CSE at IIT Bombay in 2024 was Rs. 32 LPA. The highest package was Rs. 250 LPA from an international company. Over 94% of students were placed.', 'usr00001-0000-0000-0000-000000000004', 0, 1, 55, 0, 1, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('ans00001-0000-0000-0000-000000000014', 'que00001-0000-0000-0000-000000000006', 'IIT Bombay CSE placements are among the best in India. Top recruiters include Google, Microsoft, Apple, Amazon, and Goldman Sachs. Many students also receive pre-placement offers from summer internships.', 'usr00001-0000-0000-0000-000000000005', 0, 0, 38, 0, 0, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('ans00001-0000-0000-0000-000000000015', 'que00001-0000-0000-0000-000000000007', 'Documents needed for JoSAA counselling: JEE Advanced admit card, Class 10 and 12 mark sheets, category certificate (if applicable), photo ID, passport size photographs, and medical fitness certificate.', 'usr00001-0000-0000-0000-000000000004', 0, 1, 42, 0, 1, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('ans00001-0000-0000-0000-000000000016', 'que00001-0000-0000-0000-000000000007', 'Keep original documents and self-attested copies ready. You also need to carry the seat acceptance fee payment receipt. Visit the reporting center with all documents on the specified date.', 'usr00001-0000-0000-0000-000000000002', 0, 0, 30, 0, 0, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('ans00001-0000-0000-0000-000000000017', 'que00001-0000-0000-0000-000000000008', 'BITS Pilani does offer lateral entry admission for B.E. programs. Diploma holders and B.Sc graduates can apply through BITS lateral entry test. The duration is reduced to 3 years for lateral entry students.', 'usr00001-0000-0000-0000-000000000003', 0, 1, 35, 0, 1, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('ans00001-0000-0000-0000-000000000018', 'que00001-0000-0000-0000-000000000008', 'Lateral entry admission at BITS is competitive. You need to clear the BITS lateral entry test and meet the minimum percentage criteria in your diploma/bachelor degree. Check the BITS website for exact eligibility.', 'usr00001-0000-0000-0000-000000000005', 0, 0, 22, 0, 0, '2026-06-19 06:04:41', '2026-06-19 06:04:41');

--
-- Triggers `answers`
--
DELIMITER $$
CREATE TRIGGER `trg_answers_after_delete` AFTER DELETE ON `answers` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'answers', OLD.id,
        JSON_OBJECT('question_id', OLD.question_id, 'answered_by', OLD.answered_by), NULL, NULL, NOW());
END
$$
DELIMITER ;

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
  `course_id` char(36) DEFAULT NULL,
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

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `user_id`, `college_id`, `course_id`, `application_number`, `applied_at`, `status`, `payment_status`, `fee_paid`, `transaction_id`, `counsellor_assigned`, `remarks`, `interview_date`, `offer_letter_url`, `created_at`, `updated_at`) VALUES
('5a4ccc16-00e9-f5f8-ba44-297772482579', '64e20c70-d8d7-402f-a700-53c759a659d4', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', '512d88d3-0f21-420b-a5ce-8e371db8f0a0', 'APP-20260628-5461BE', '2026-06-28 11:39:49', 'submitted', 'pending', 0.00, NULL, NULL, 'Course: gcvhdhjcf\nExam Score: 77\nTarget Year: 2026\nNotes: gcg tgbfyh fcfhgd yrh', NULL, NULL, '2026-06-28 11:39:49', '2026-06-28 11:39:49'),
('86efb533-d7e7-bb28-28ee-c66e7c4d27f6', '64e20c70-d8d7-402f-a700-53c759a659d4', 'col-iima-0009', '123f2a50-6400-4e2b-b162-e05ca620db84', 'APP-TEST', '2026-06-28 11:49:26', 'submitted', 'pending', 0.00, NULL, NULL, NULL, NULL, NULL, '2026-06-28 11:49:26', '2026-06-28 11:49:26');

--
-- Triggers `applications`
--
DELIMITER $$
CREATE TRIGGER `trg_applications_after_insert` AFTER INSERT ON `applications` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'applications', NEW.id, NULL,
        JSON_OBJECT('user_id', NEW.user_id, 'college_id', NEW.college_id, 'status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_applications_after_update` AFTER UPDATE ON `applications` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'applications', NEW.id,
        JSON_OBJECT('status', OLD.status, 'payment_status', OLD.payment_status),
        JSON_OBJECT('status', NEW.status, 'payment_status', NEW.payment_status),
        NULL, NOW());
END
$$
DELIMITER ;

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
('', 'Top 10 Engineering Colleges in India for 2027', 'top-10-engineering-colleges-2026', 'ranking', '<p>Engineering remains one of the most sought-after career paths in India. In 2026, the rankings have seen a significant shift, with several new IITs and private institutions moving up the ladder...</p>', 'Discover the top-ranked engineering institutions in India based on placement records, faculty, and research output for the year 2026.', 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=800&q=80', NULL, NULL, 'Madhav Arora', NULL, 9, NULL, 'published', '2026-06-14 15:53:00', NULL, 2, 0, NULL, 1, NULL, NULL, '2026-06-14 15:53:09', '2026-06-24 08:03:06'),
('5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', 'Top 10 Engineering Colleges in India for 2026', 'top-10-engineering-colleges-2026-v2', 'ranking', '<p>Engineering remains one of the most sought-after career paths in India. In 2026, the rankings have seen a significant shift, with several new IITs and private institutions moving up the ladder...</p>', 'Discover the top-ranked engineering institutions in India based on placement records, faculty, and research output for the year 2026.', 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=800&q=80', NULL, NULL, 'Madhav Arora', NULL, 9, NULL, 'published', '2026-06-14 15:55:04', NULL, 14, 0, NULL, 1, NULL, NULL, '2026-06-14 15:55:04', '2026-06-24 08:03:06'),
('6443a93e-56d5-488c-b02b-a75bfa1a0758', 'How to Choose the Right College: A Comprehensive Guide', 'how-to-choose-the-right-college-v2', 'guide', '<p>Choosing the right college is a life-changing decision. It is not just about the brand name; it is about finding a place that aligns with your personal and professional aspirations. Let us dive into the key factors you must consider...</p>', 'Feeling overwhelmed by college options? Here is a step-by-step guide to evaluating colleges based on your career goals, budget, and location preferences.', 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=800&q=80', NULL, NULL, 'Career Counselor', NULL, 14, NULL, 'published', '2026-06-14 15:55:04', NULL, 12, 0, NULL, 1, NULL, NULL, '2026-06-14 15:55:04', '2026-06-24 08:03:06'),
('8c302e7f-f5b8-436d-b037-3e54d6a086cb', 'Why Liberal Arts Education is Gaining Popularity in India', 'liberal-arts-education-popularity-v2', 'blog', '<p>The traditional mindset of \"Engineering or Medical\" is slowly changing in India. A liberal arts education offers critical thinking, adaptability, and a broad worldview, which modern employers highly value...</p>', 'More students are moving away from traditional STEM fields to explore Liberal Arts. What is driving this shift, and what are the career prospects?', 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800&q=80', NULL, NULL, 'Guest Blogger', NULL, 13, NULL, 'published', '2026-06-14 15:55:04', NULL, 9, 0, NULL, 1, NULL, NULL, '2026-06-14 15:55:04', '2026-06-24 08:03:06'),
('9a915909-6c0d-4dc1-8799-3811226adb9e', 'My Opinion: Are Entrance Exams Putting Too Much Pressure on Students?', 'opinion-entrance-exams-pressure-v2', 'opinion', '<p>Every year, millions of students appear for competitive exams like JEE, NEET, and CUET. While these exams are designed to be a fair metric for selection, the sheer pressure and the booming coaching industry are creating an unhealthy environment...</p>', 'With rising competition and coaching culture, entrance exams are taking a toll on student mental health. It is time we rethink our evaluation methods.', 'https://images.unsplash.com/photo-1513258496099-48168024aec0?w=800&q=80', NULL, NULL, 'Student Voice', NULL, 11, NULL, 'published', '2026-06-14 15:55:04', NULL, 7, 0, NULL, 1, NULL, NULL, '2026-06-14 15:55:04', '2026-06-24 08:03:06'),
('bcbf97c5-f71a-47db-b507-1fd618d370ef', 'Delhi University Introduces New B.Tech Programs', 'du-introduces-new-btech-programs-v2', 'news', '<p>Delhi University (DU) is expanding its technical education footprint by introducing B.Tech programs in Computer Science, Electronics, and Electrical Engineering. Admissions will be based on JEE Main scores...</p>', 'In a major academic expansion, Delhi University has announced the launch of three new B.Tech programs starting this academic session. Here is what you need to know.', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', NULL, NULL, 'Campus Reporter', NULL, 10, NULL, 'published', '2026-06-14 15:55:04', NULL, 5, 0, NULL, 1, NULL, NULL, '2026-06-14 15:55:04', '2026-06-24 08:03:06'),
('c83156f5-2dea-4676-b04e-020d84e24ee8', 'JEE Main 2026 Dates Announced: Check Registration Details', 'jee-main-2026-dates-announced-v2', 'exam_update', '<p>Attention engineering aspirants! The NTA has officially announced the exam dates for JEE Main 2026. The exam will be conducted in two sessions, as usual. Students are advised to keep their documents ready for the registration process...</p>', 'The National Testing Agency (NTA) has finally released the schedule for JEE Main 2026. Registrations will commence from the first week of November.', 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=800&q=80', NULL, NULL, 'Education Desk', NULL, 10, NULL, 'published', '2026-06-14 15:55:04', NULL, 3, 0, NULL, 1, NULL, NULL, '2026-06-14 15:55:04', '2026-06-24 08:03:06');

--
-- Triggers `articles`
--
DELIMITER $$
CREATE TRIGGER `trg_articles_after_delete` AFTER DELETE ON `articles` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'articles', OLD.id,
        JSON_OBJECT('article_title', OLD.article_title, 'status', OLD.status), NULL, NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_articles_after_insert` AFTER INSERT ON `articles` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'articles', NEW.id, NULL,
        JSON_OBJECT('article_title', NEW.article_title, 'status', NEW.status, 'author_id', NEW.author_id),
        NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_articles_after_update` AFTER UPDATE ON `articles` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'articles', NEW.id,
        JSON_OBJECT('article_title', OLD.article_title, 'status', OLD.status),
        JSON_OBJECT('article_title', NEW.article_title, 'status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;

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
(9, 'Rankings', 'rankings', NULL, 0, '2026-06-21 10:59:02'),
(10, 'News', 'news', NULL, 0, '2026-06-21 10:59:02'),
(11, 'Admissions', 'admissions', NULL, 0, '2026-06-21 10:59:02'),
(12, 'Placements', 'placements', NULL, 0, '2026-06-21 10:59:02'),
(13, 'Campus Life', 'campus-life', NULL, 0, '2026-06-21 10:59:02'),
(14, 'How-To', 'how-to', NULL, 0, '2026-06-21 10:59:02');

--
-- Triggers `article_categories`
--
DELIMITER $$
CREATE TRIGGER `trg_article_categories_after_delete` AFTER DELETE ON `article_categories` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'article_categories', OLD.id,
        JSON_OBJECT('category_name', OLD.category_name), NULL, NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_article_categories_after_insert` AFTER INSERT ON `article_categories` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'article_categories', NEW.id, NULL,
        JSON_OBJECT('category_name', NEW.category_name, 'category_slug', NEW.category_slug),
        NULL, NOW());
END
$$
DELIMITER ;

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
('a678fc69-2700-4e28-9f83-5dec553a8f5f', '', 1, 'a85f4bf4-5c3b-11f1-a48e-c8f7507a8de6', '<p>Engineering remains one of the most sought-after career paths in India. In 2026, the rankings have seen a significant shift, with several new IITs and private institutions moving up the ladder...</p>', '2026-06-17 17:15:12');

-- --------------------------------------------------------

--
-- Table structure for table `article_tags`
--

CREATE TABLE `article_tags` (
  `id` int(11) NOT NULL,
  `article_id` char(36) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `article_views`
--

CREATE TABLE `article_views` (
  `id` int(11) NOT NULL,
  `article_id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `article_views`
--

INSERT INTO `article_views` (`id`, `article_id`, `user_id`, `session_id`, `ip_address`, `viewed_at`) VALUES
(1, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '172.16.0.2', '2026-06-17 07:52:13'),
(2, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '172.16.0.4', '2026-06-22 07:52:13'),
(3, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '192.168.1.2', '2026-05-29 07:52:13'),
(4, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '192.168.1.6', '2026-06-04 07:52:13'),
(5, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '10.0.0.3', '2026-05-27 07:52:13'),
(6, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '192.168.1.3', '2026-06-08 07:52:13'),
(7, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '172.16.0.5', '2026-06-08 07:52:13'),
(8, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '192.168.1.3', '2026-06-18 07:52:13'),
(9, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '10.0.0.5', '2026-05-26 07:52:13'),
(10, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '192.168.1.6', '2026-06-16 07:52:13'),
(11, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '10.0.0.3', '2026-05-24 07:52:13'),
(12, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '10.0.0.5', '2026-06-06 07:52:13'),
(13, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '10.0.0.4', '2026-06-10 07:52:13'),
(14, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '192.168.1.6', '2026-06-20 07:52:13'),
(15, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '10.0.0.3', '2026-06-03 07:52:13'),
(16, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '172.16.0.4', '2026-06-07 07:52:13'),
(17, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '192.168.1.4', '2026-06-11 07:52:13'),
(18, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '192.168.1.5', '2026-06-07 07:52:13'),
(19, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '10.0.0.1', '2026-06-14 07:52:13'),
(20, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '10.0.0.2', '2026-06-08 07:52:13'),
(21, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '10.0.0.5', '2026-06-04 07:52:13'),
(22, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '192.168.1.5', '2026-06-20 07:52:13'),
(23, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '192.168.1.3', '2026-06-10 07:52:13'),
(24, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '10.0.0.5', '2026-05-28 07:52:13'),
(25, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '192.168.1.3', '2026-05-29 07:52:13'),
(26, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '10.0.0.2', '2026-06-02 07:52:13'),
(27, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '10.0.0.1', '2026-06-07 07:52:13'),
(28, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '172.16.0.2', '2026-05-24 07:52:13'),
(29, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '10.0.0.3', '2026-05-29 07:52:13'),
(30, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '172.16.0.5', '2026-06-04 07:52:13'),
(31, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '10.0.0.2', '2026-06-23 07:52:13'),
(32, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', NULL, NULL, '192.168.1.5', '2026-06-19 07:52:13'),
(33, '6443a93e-56d5-488c-b02b-a75bfa1a0758', NULL, NULL, '192.168.1.6', '2026-06-20 07:52:13'),
(34, '6443a93e-56d5-488c-b02b-a75bfa1a0758', NULL, NULL, '172.16.0.1', '2026-06-14 07:52:13'),
(35, '6443a93e-56d5-488c-b02b-a75bfa1a0758', NULL, NULL, '172.16.0.4', '2026-06-17 07:52:13'),
(36, '6443a93e-56d5-488c-b02b-a75bfa1a0758', NULL, NULL, '10.0.0.3', '2026-06-22 07:52:13'),
(37, '6443a93e-56d5-488c-b02b-a75bfa1a0758', NULL, NULL, '10.0.0.5', '2026-06-06 07:52:13'),
(38, '6443a93e-56d5-488c-b02b-a75bfa1a0758', NULL, NULL, '10.0.0.1', '2026-06-04 07:52:13'),
(39, '6443a93e-56d5-488c-b02b-a75bfa1a0758', NULL, NULL, '192.168.1.6', '2026-06-11 07:52:13'),
(40, '6443a93e-56d5-488c-b02b-a75bfa1a0758', NULL, NULL, '172.16.0.4', '2026-06-11 07:52:13'),
(41, '6443a93e-56d5-488c-b02b-a75bfa1a0758', NULL, NULL, '172.16.0.4', '2026-06-07 07:52:13'),
(42, '6443a93e-56d5-488c-b02b-a75bfa1a0758', NULL, NULL, '10.0.0.2', '2026-05-27 07:52:13'),
(43, '6443a93e-56d5-488c-b02b-a75bfa1a0758', NULL, NULL, '172.16.0.5', '2026-06-03 07:52:13'),
(44, '6443a93e-56d5-488c-b02b-a75bfa1a0758', NULL, NULL, '172.16.0.2', '2026-06-09 07:52:13'),
(45, '6443a93e-56d5-488c-b02b-a75bfa1a0758', NULL, NULL, '10.0.0.2', '2026-06-07 07:52:13'),
(46, '6443a93e-56d5-488c-b02b-a75bfa1a0758', NULL, NULL, '10.0.0.5', '2026-06-17 07:52:13'),
(47, '6443a93e-56d5-488c-b02b-a75bfa1a0758', NULL, NULL, '192.168.1.4', '2026-06-17 07:52:13'),
(48, '6443a93e-56d5-488c-b02b-a75bfa1a0758', NULL, NULL, '192.168.1.5', '2026-05-24 07:52:13'),
(49, '6443a93e-56d5-488c-b02b-a75bfa1a0758', NULL, NULL, '172.16.0.1', '2026-05-28 07:52:13'),
(50, '6443a93e-56d5-488c-b02b-a75bfa1a0758', NULL, NULL, '192.168.1.1', '2026-05-28 07:52:13'),
(51, '8c302e7f-f5b8-436d-b037-3e54d6a086cb', NULL, NULL, '192.168.1.3', '2026-06-19 07:52:13'),
(52, '8c302e7f-f5b8-436d-b037-3e54d6a086cb', NULL, NULL, '192.168.1.5', '2026-06-15 07:52:13'),
(53, '8c302e7f-f5b8-436d-b037-3e54d6a086cb', NULL, NULL, '192.168.1.6', '2026-06-21 07:52:13'),
(54, '8c302e7f-f5b8-436d-b037-3e54d6a086cb', NULL, NULL, '172.16.0.4', '2026-05-29 07:52:13'),
(55, '8c302e7f-f5b8-436d-b037-3e54d6a086cb', NULL, NULL, '192.168.1.3', '2026-06-22 07:52:13'),
(56, '8c302e7f-f5b8-436d-b037-3e54d6a086cb', NULL, NULL, '192.168.1.2', '2026-06-11 07:52:13'),
(57, '8c302e7f-f5b8-436d-b037-3e54d6a086cb', NULL, NULL, '192.168.1.6', '2026-05-24 07:52:13'),
(58, '8c302e7f-f5b8-436d-b037-3e54d6a086cb', NULL, NULL, '10.0.0.4', '2026-05-29 07:52:13'),
(59, '8c302e7f-f5b8-436d-b037-3e54d6a086cb', NULL, NULL, '10.0.0.2', '2026-06-15 07:52:13'),
(60, '8c302e7f-f5b8-436d-b037-3e54d6a086cb', NULL, NULL, '192.168.1.1', '2026-06-20 07:52:13'),
(61, '8c302e7f-f5b8-436d-b037-3e54d6a086cb', NULL, NULL, '172.16.0.5', '2026-06-14 07:52:13'),
(62, '8c302e7f-f5b8-436d-b037-3e54d6a086cb', NULL, NULL, '192.168.1.1', '2026-06-18 07:52:13'),
(63, '9a915909-6c0d-4dc1-8799-3811226adb9e', NULL, NULL, '10.0.0.1', '2026-05-30 07:52:13'),
(64, '9a915909-6c0d-4dc1-8799-3811226adb9e', NULL, NULL, '10.0.0.3', '2026-06-23 07:52:13'),
(65, '9a915909-6c0d-4dc1-8799-3811226adb9e', NULL, NULL, '192.168.1.4', '2026-06-13 07:52:13'),
(66, '9a915909-6c0d-4dc1-8799-3811226adb9e', NULL, NULL, '10.0.0.3', '2026-06-07 07:52:13'),
(67, '9a915909-6c0d-4dc1-8799-3811226adb9e', NULL, NULL, '192.168.1.6', '2026-06-23 07:52:13'),
(68, '9a915909-6c0d-4dc1-8799-3811226adb9e', NULL, NULL, '10.0.0.5', '2026-06-17 07:52:13'),
(69, '9a915909-6c0d-4dc1-8799-3811226adb9e', NULL, NULL, '192.168.1.2', '2026-06-07 07:52:13'),
(70, '9a915909-6c0d-4dc1-8799-3811226adb9e', NULL, NULL, '192.168.1.4', '2026-05-31 07:52:13'),
(71, 'bcbf97c5-f71a-47db-b507-1fd618d370ef', NULL, NULL, '192.168.1.4', '2026-05-31 07:52:13'),
(72, 'bcbf97c5-f71a-47db-b507-1fd618d370ef', NULL, NULL, '10.0.0.2', '2026-06-05 07:52:13'),
(73, 'bcbf97c5-f71a-47db-b507-1fd618d370ef', NULL, NULL, '192.168.1.4', '2026-06-05 07:52:13'),
(74, 'bcbf97c5-f71a-47db-b507-1fd618d370ef', NULL, NULL, '172.16.0.2', '2026-06-22 07:52:13'),
(75, 'bcbf97c5-f71a-47db-b507-1fd618d370ef', NULL, NULL, '192.168.1.5', '2026-06-12 07:52:13'),
(76, 'bcbf97c5-f71a-47db-b507-1fd618d370ef', NULL, NULL, '192.168.1.1', '2026-06-14 07:52:13'),
(77, 'c83156f5-2dea-4676-b04e-020d84e24ee8', NULL, NULL, '10.0.0.3', '2026-06-12 07:52:13'),
(78, 'c83156f5-2dea-4676-b04e-020d84e24ee8', NULL, NULL, '192.168.1.2', '2026-06-13 07:52:13'),
(79, 'c83156f5-2dea-4676-b04e-020d84e24ee8', NULL, NULL, '192.168.1.2', '2026-05-29 07:52:13'),
(80, 'c83156f5-2dea-4676-b04e-020d84e24ee8', NULL, NULL, '10.0.0.3', '2026-06-22 07:52:13'),
(81, 'c83156f5-2dea-4676-b04e-020d84e24ee8', NULL, NULL, '172.16.0.2', '2026-06-23 07:52:13'),
(82, '', NULL, NULL, '192.168.1.1', '2026-06-11 07:52:13'),
(83, '', NULL, NULL, '192.168.1.4', '2026-06-03 07:52:13'),
(84, '', NULL, NULL, '192.168.1.4', '2026-06-11 07:52:13'),
(85, '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', '64e20c70-d8d7-402f-a700-53c759a659d4', 'k417qh95d7inojuj2pugo4sc56', '::1', '2026-06-23 11:42:00'),
(86, '9a915909-6c0d-4dc1-8799-3811226adb9e', NULL, 'it1uajau4jp9pmgoog27s7tro2', '::1', '2026-06-24 07:55:37');

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

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `audit_action`, `entity_type`, `entity_id`, `old_value`, `new_value`, `ip_address`, `created_at`) VALUES
('032208e7-75e5-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'f6a7b8c9-d0e1-2345-fabc-456789012345', '{\"name\": \"Anna University\", \"status\": \"active\"}', '{\"name\": \"Anna University\", \"status\": \"active\"}', NULL, '2026-07-02 07:09:48'),
('0322c0cf-75e5-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'd4e5f6a7-b8c9-0123-defa-234567890123', '{\"name\": \"Birla Institute of Technology and Science Pilani\", \"status\": \"active\"}', '{\"name\": \"Birla Institute of Technology and Science Pilani\", \"status\": \"active\"}', NULL, '2026-07-02 07:09:48'),
('03235db0-75e5-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'a7b8c9d0-e1f2-3456-abcd-567890123456', '{\"name\": \"Jadavpur University\", \"status\": \"active\"}', '{\"name\": \"Jadavpur University\", \"status\": \"active\"}', NULL, '2026-07-02 07:09:48'),
('03241c21-75e5-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'e5f6a7b8-c9d0-1234-efab-345678901234', '{\"name\": \"Jawaharlal Nehru University\", \"status\": \"active\"}', '{\"name\": \"Jawaharlal Nehru University\", \"status\": \"active\"}', NULL, '2026-07-02 07:09:48'),
('0324d0d9-75e5-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'd0e1f2a3-b4c5-6789-defa-890123456789', '{\"name\": \"Manipal Academy of Higher Education\", \"status\": \"active\"}', '{\"name\": \"Manipal Academy of Higher Education\", \"status\": \"active\"}', NULL, '2026-07-02 07:09:48'),
('032579d8-75e5-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'e1f2a3b4-c5d6-7890-efab-901234567890', '{\"name\": \"National Institute of Technology Tiruchirappalli\", \"status\": \"active\"}', '{\"name\": \"National Institute of Technology Tiruchirappalli\", \"status\": \"active\"}', NULL, '2026-07-02 07:09:48'),
('03264c3e-75e5-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'c9d0e1f2-a3b4-5678-cdef-789012345678', '{\"name\": \"SRM Institute of Science and Technology\", \"status\": \"active\"}', '{\"name\": \"SRM Institute of Science and Technology\", \"status\": \"active\"}', NULL, '2026-07-02 07:09:48'),
('03276bfc-75e5-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'b8c9d0e1-f2a3-4567-bcde-678901234567', '{\"name\": \"Vellore Institute of Technology\", \"status\": \"active\"}', '{\"name\": \"Vellore Institute of Technology\", \"status\": \"active\"}', NULL, '2026-07-02 07:09:48'),
('22fa9986-75eb-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'u-a005', '{\"name\": \"Indian Institute of Technology Roorkee\", \"status\": \"active\"}', '{\"name\": \"Indian Institute of Technology Roorkee\", \"status\": \"active\"}', NULL, '2026-07-02 07:53:38'),
('22fb747d-75eb-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'u1a00001-0001-0001-0001-000000000004', '{\"name\": \"University of Mumbai\", \"status\": \"active\"}', '{\"name\": \"University of Mumbai\", \"status\": \"active\"}', NULL, '2026-07-02 07:53:38'),
('22fbfa91-75eb-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'u1a00001-0001-0001-0001-000000000005', '{\"name\": \"University of Calcutta\", \"status\": \"active\"}', '{\"name\": \"University of Calcutta\", \"status\": \"active\"}', NULL, '2026-07-02 07:53:38'),
('27a6d07e-75dd-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', '{\"name\": \"fgfhyryf\", \"status\": \"active\"}', '{\"name\": \"Indian Institute of Management Ahmedabad\", \"status\": \"active\"}', NULL, '2026-07-02 06:13:33'),
('2e76a369-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-test-0001-0001-0001-0001-000000000', NULL, '{\"name\": \"Test University\", \"slug\": \"test-university-xyz\", \"status\": \"active\"}', NULL, '2026-07-02 07:11:01'),
('3178cee4-7311-11f1-81b7-a0510b1a7448', NULL, 'create', 'moderation_queue', '31789e5a-7311-11f1-81b7-a0510b1a7448', NULL, '{\"entity_type\": \"qa_answer\", \"entity_id\": \"ans00001-0000-0000-0000-000000000001\", \"status\": \"resolved\"}', NULL, '2026-06-28 16:48:06'),
('3178d604-7311-11f1-81b7-a0510b1a7448', NULL, 'create', 'moderation_queue', '3178d2f3-7311-11f1-81b7-a0510b1a7448', NULL, '{\"entity_type\": \"qa_answer\", \"entity_id\": \"ans00001-0000-0000-0000-000000000001\", \"status\": \"resolved\"}', NULL, '2026-06-28 16:48:06'),
('3178da72-7311-11f1-81b7-a0510b1a7448', NULL, 'create', 'moderation_queue', '3178d80a-7311-11f1-81b7-a0510b1a7448', NULL, '{\"entity_type\": \"qa_answer\", \"entity_id\": \"ans00001-0000-0000-0000-000000000001\", \"status\": \"resolved\"}', NULL, '2026-06-28 16:48:06'),
('3178ddd7-7311-11f1-81b7-a0510b1a7448', NULL, 'create', 'moderation_queue', '3178dbb7-7311-11f1-81b7-a0510b1a7448', NULL, '{\"entity_type\": \"qa_answer\", \"entity_id\": \"ans00001-0000-0000-0000-000000000001\", \"status\": \"resolved\"}', NULL, '2026-06-28 16:48:06'),
('711ec1cb-75e0-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u1a00001-0001-0001-0001-000000000002', NULL, '{\"name\": \"Aligarh Muslim University\", \"slug\": \"amu-aligarh\", \"status\": \"active\"}', NULL, '2026-07-02 06:37:05'),
('711ecf8e-75e0-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u1a00001-0001-0001-0001-000000000004', NULL, '{\"name\": \"University of Mumbai\", \"slug\": \"mumbai-university\", \"status\": \"active\"}', NULL, '2026-07-02 06:37:05'),
('711ede61-75e0-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u1a00001-0001-0001-0001-000000000006', NULL, '{\"name\": \"Amity University Noida\", \"slug\": \"amity-noida\", \"status\": \"active\"}', NULL, '2026-07-02 06:37:05'),
('711febf1-75e0-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u1a00001-0001-0001-0001-000000000007', NULL, '{\"name\": \"Lovely Professional University\", \"slug\": \"lpu-phagwara\", \"status\": \"active\"}', NULL, '2026-07-02 06:37:05'),
('711ff60d-75e0-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u1a00001-0001-0001-0001-000000000008', NULL, '{\"name\": \"Sharda University\", \"slug\": \"sharda-greater-noida\", \"status\": \"active\"}', NULL, '2026-07-02 06:37:05'),
('711ffc39-75e0-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u1a00001-0001-0001-0001-000000000010', NULL, '{\"name\": \"Chandigarh University\", \"slug\": \"cu-mohali\", \"status\": \"active\"}', NULL, '2026-07-02 06:37:05'),
('776b9698-75df-11f1-8ac5-a0510b1a7444', NULL, 'delete', 'universities', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', '{\"name\": \"Indian Institute of Management Ahmedabad\"}', NULL, NULL, '2026-07-02 06:30:06'),
('776bd026-75df-11f1-8ac5-a0510b1a7444', NULL, 'delete', 'universities', 'a1b2c3d4-e5f6-7890-abcd-ef1234567890', '{\"name\": \"Indian Institute of Technology Bombay\"}', NULL, NULL, '2026-07-02 06:30:06'),
('776ca083-75df-11f1-8ac5-a0510b1a7444', NULL, 'delete', 'universities', 'c3d4e5f6-a7b8-9012-cdef-123456789012', '{\"name\": \"Indian Institute of Technology Madras\"}', NULL, NULL, '2026-07-02 06:30:06'),
('81450f22-75e2-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', NULL, '{\"name\": \"Indian Institute of Management Ahmedabad\", \"slug\": \"iim-ahmedabad\", \"status\": \"active\"}', NULL, '2026-07-02 06:51:51'),
('82976d07-7310-11f1-81b7-a0510b1a7448', NULL, 'create', 'moderation_queue', '8296ba7d-7310-11f1-81b7-a0510b1a7448', NULL, '{\"entity_type\": \"\", \"entity_id\": \"ans00001-0000-0000-0000-000000000001\", \"status\": \"resolved\"}', NULL, '2026-06-28 16:43:13'),
('82979412-7310-11f1-81b7-a0510b1a7448', NULL, 'create', 'moderation_queue', '8297832e-7310-11f1-81b7-a0510b1a7448', NULL, '{\"entity_type\": \"\", \"entity_id\": \"ans00001-0000-0000-0000-000000000001\", \"status\": \"resolved\"}', NULL, '2026-06-28 16:43:13'),
('82987051-7310-11f1-81b7-a0510b1a7448', NULL, 'create', 'moderation_queue', '82979926-7310-11f1-81b7-a0510b1a7448', NULL, '{\"entity_type\": \"\", \"entity_id\": \"ans00001-0000-0000-0000-000000000001\", \"status\": \"resolved\"}', NULL, '2026-06-28 16:43:13'),
('829876d9-7310-11f1-81b7-a0510b1a7448', NULL, 'create', 'moderation_queue', '829872e4-7310-11f1-81b7-a0510b1a7448', NULL, '{\"entity_type\": \"\", \"entity_id\": \"ans00001-0000-0000-0000-000000000001\", \"status\": \"resolved\"}', NULL, '2026-06-28 16:43:13'),
('88306a36-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-d001', NULL, '{\"name\": \"Thapar Institute of Engineering and Technology\", \"slug\": \"thapar-university\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('88322982-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-d002', NULL, '{\"name\": \"Symbiosis International University\", \"slug\": \"symbiosis-pune\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('8832d600-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-d003', NULL, '{\"name\": \"Amrita Vishwa Vidyapeetham\", \"slug\": \"amrita-coimbatore\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('88340340-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-d004', NULL, '{\"name\": \"Bharathidasan University\", \"slug\": \"bharathidasan-uni\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('8835813a-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-d005', NULL, '{\"name\": \"Siksha O Anusandhan University\", \"slug\": \"soa-bhubaneswar\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('88363ae5-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-d006', NULL, '{\"name\": \"KIIT University\", \"slug\": \"kiit-bhubaneswar\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('8836ec92-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-d007', NULL, '{\"name\": \"Shiv Nadar University\", \"slug\": \"shiv-nadar-uni\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('8837c642-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-d008', NULL, '{\"name\": \"Dr. D.Y. Patil Vidyapeeth\", \"slug\": \"dyp-pune\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('88387459-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-d009', NULL, '{\"name\": \"Tata Institute of Social Sciences\", \"slug\": \"tiss-mumbai\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('88395d76-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-d010', NULL, '{\"name\": \"Gujarat Forensic Sciences University\", \"slug\": \"gfsu-gandhinagar\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('883a6748-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-a001', NULL, '{\"name\": \"Indian Institute of Science\", \"slug\": \"iisc-bangalore\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('883b5b13-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-a002', NULL, '{\"name\": \"Indian Institute of Technology Delhi\", \"slug\": \"iit-delhi\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('883c1a5d-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-a003', NULL, '{\"name\": \"Indian Institute of Technology Kanpur\", \"slug\": \"iit-kanpur\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('883cf826-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-a004', NULL, '{\"name\": \"Indian Institute of Technology Kharagpur\", \"slug\": \"iit-kharagpur\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('883d762f-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-a005', NULL, '{\"name\": \"Indian Institute of Technology Roorkee\", \"slug\": \"iit-roorkee\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('883e9920-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-a006', NULL, '{\"name\": \"Indian Institute of Technology Guwahati\", \"slug\": \"iit-guwahati\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('883f0e74-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-a007', NULL, '{\"name\": \"Indian Institute of Technology Hyderabad\", \"slug\": \"iit-hyderabad\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('883f6dbf-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-a008', NULL, '{\"name\": \"Birla Institute of Technology Mesra\", \"slug\": \"bit-mesra\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('883fd491-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-a009', NULL, '{\"name\": \"PSG College of Technology\", \"slug\": \"psg-coimbatore\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('88403dd5-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-a010', NULL, '{\"name\": \"Thiagarajar College of Engineering\", \"slug\": \"tce-madurai\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('8840a355-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-g001', NULL, '{\"name\": \"Indian Institute of Technology Ropar\", \"slug\": \"iit-ropar\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('884109ad-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-g002', NULL, '{\"name\": \"National Institute of Technology Warangal\", \"slug\": \"nit-warangal\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('88417199-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-g003', NULL, '{\"name\": \"National Institute of Technology Surathkal\", \"slug\": \"nitk-surathkal\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('8841d99a-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-g004', NULL, '{\"name\": \"Motilal Nehru National Institute of Technology\", \"slug\": \"mnnit-allahabad\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('8842467c-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-g005', NULL, '{\"name\": \"National Institute of Technology Calicut\", \"slug\": \"nit-calicut\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('8842caba-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-g006', NULL, '{\"name\": \"Malaviya National Institute of Technology\", \"slug\": \"mnit-jaipur\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('884358ac-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-g007', NULL, '{\"name\": \"Visvesvaraya National Institute of Technology\", \"slug\": \"vnit-nagpur\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('8843c462-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-g008', NULL, '{\"name\": \"Sardar Vallabhbhai National Institute of Technology\", \"slug\": \"svnit-surat\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('88441b23-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-p001', NULL, '{\"name\": \"Lovely Professional University\", \"slug\": \"lpu-university\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('884482d5-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-p002', NULL, '{\"name\": \"Chandigarh University\", \"slug\": \"cuchd-mohali\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('8844f922-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-p003', NULL, '{\"name\": \"Amity University Noida\", \"slug\": \"amity-university\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('88455a0b-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-p004', NULL, '{\"name\": \"Sharda University Greater Noida\", \"slug\": \"sharda-university\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('8845c90a-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-p005', NULL, '{\"name\": \"Parul University Vadodara\", \"slug\": \"parul-university\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('88466bc0-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-p006', NULL, '{\"name\": \"Ashoka University\", \"slug\": \"ashoka-sonipat\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('884721cf-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-p007', NULL, '{\"name\": \"Kalinga Institute of Industrial Technology\", \"slug\": \"kiit-university\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('8847d185-75e5-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u-p008', NULL, '{\"name\": \"Bharati Vidyapeeth University\", \"slug\": \"bvp-pune\", \"status\": \"active\"}', NULL, '2026-07-02 07:13:31'),
('a11a9136-75e5-11f1-8ac5-a0510b1a7444', NULL, 'delete', 'universities', 'u-test-0001-0001-0001-0001-000000000', '{\"name\": \"Test University\"}', NULL, NULL, '2026-07-02 07:14:13'),
('a11bfed4-75e5-11f1-8ac5-a0510b1a7444', NULL, 'delete', 'universities', 'u1a00001-0001-0001-0001-000000000006', '{\"name\": \"Amity University Noida\"}', NULL, NULL, '2026-07-02 07:14:13'),
('a1207aea-75e5-11f1-8ac5-a0510b1a7444', NULL, 'delete', 'universities', 'u1a00001-0001-0001-0001-000000000007', '{\"name\": \"Lovely Professional University\"}', NULL, NULL, '2026-07-02 07:14:13'),
('a1226d33-75e5-11f1-8ac5-a0510b1a7444', NULL, 'delete', 'universities', 'u1a00001-0001-0001-0001-000000000009', '{\"name\": \"Parul University\"}', NULL, NULL, '2026-07-02 07:14:13'),
('a123ea40-75e5-11f1-8ac5-a0510b1a7444', NULL, 'delete', 'universities', 'u1a00001-0001-0001-0001-000000000010', '{\"name\": \"Chandigarh University\"}', NULL, NULL, '2026-07-02 07:14:13'),
('a125329f-75e5-11f1-8ac5-a0510b1a7444', NULL, 'delete', 'universities', 'u1a00001-0001-0001-0001-000000000008', '{\"name\": \"Sharda University\"}', NULL, NULL, '2026-07-02 07:14:13'),
('afff6ae2-75df-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u1a00001-0001-0001-0001-000000000001', NULL, '{\"name\": \"Banaras Hindu University\", \"slug\": \"bhu-varanasi\", \"status\": \"active\"}', NULL, '2026-07-02 06:31:41'),
('b00119fd-75df-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u1a00001-0001-0001-0001-000000000003', NULL, '{\"name\": \"Savitribai Phule Pune University\", \"slug\": \"sppu-pune\", \"status\": \"active\"}', NULL, '2026-07-02 06:31:41'),
('b001381d-75df-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u1a00001-0001-0001-0001-000000000005', NULL, '{\"name\": \"University of Calcutta\", \"slug\": \"cu-kolkata\", \"status\": \"active\"}', NULL, '2026-07-02 06:31:41'),
('b0015fc2-75df-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'u1a00001-0001-0001-0001-000000000009', NULL, '{\"name\": \"Parul University\", \"slug\": \"parul-vadodara\", \"status\": \"active\"}', NULL, '2026-07-02 06:31:41'),
('b8c44c9e-75ea-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'u1a00001-0001-0001-0001-000000000002', '{\"name\": \"Aligarh Muslim University\", \"status\": \"active\"}', '{\"name\": \"Aligarh Muslim University\", \"status\": \"active\"}', NULL, '2026-07-02 07:50:40'),
('b8c574cc-75ea-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'b2c3d4e5-f6a7-8901-bcde-f12345678901', '{\"name\": \"University of Delhi\", \"status\": \"active\"}', '{\"name\": \"University of Delhi\", \"status\": \"active\"}', NULL, '2026-07-02 07:50:40'),
('c199d584-73d1-11f1-81b7-a0510b1a7448', NULL, 'update', 'college_accounts', '80d73b32-163e-4789-9789-b7a6a1dc7da8', '{\"status\": \"approved\"}', '{\"status\": \"approved\"}', NULL, '2026-06-29 15:46:30'),
('c3591fac-7310-11f1-81b7-a0510b1a7448', NULL, '', 'moderation_queue', '8296ba7d-7310-11f1-81b7-a0510b1a7448', '{\"status\": \"resolved\"}', '{\"status\": \"resolved\"}', NULL, '2026-06-28 16:45:01'),
('c3596036-7310-11f1-81b7-a0510b1a7448', NULL, '', 'moderation_queue', '8297832e-7310-11f1-81b7-a0510b1a7448', '{\"status\": \"resolved\"}', '{\"status\": \"resolved\"}', NULL, '2026-06-28 16:45:01'),
('c3596647-7310-11f1-81b7-a0510b1a7448', NULL, '', 'moderation_queue', '82979926-7310-11f1-81b7-a0510b1a7448', '{\"status\": \"resolved\"}', '{\"status\": \"resolved\"}', NULL, '2026-06-28 16:45:01'),
('c35a7122-7310-11f1-81b7-a0510b1a7448', NULL, '', 'moderation_queue', '829872e4-7310-11f1-81b7-a0510b1a7448', '{\"status\": \"resolved\"}', '{\"status\": \"resolved\"}', NULL, '2026-06-28 16:45:01'),
('d9badb43-75e4-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'f6a7b8c9-d0e1-2345-fabc-456789012345', '{\"name\": \"Anna University\", \"status\": \"active\"}', '{\"name\": \"Anna University\", \"status\": \"active\"}', NULL, '2026-07-02 07:08:39'),
('d9bbe58b-75e4-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'd4e5f6a7-b8c9-0123-defa-234567890123', '{\"name\": \"Birla Institute of Technology and Science Pilani\", \"status\": \"active\"}', '{\"name\": \"Birla Institute of Technology and Science Pilani\", \"status\": \"active\"}', NULL, '2026-07-02 07:08:39'),
('d9bcb6e1-75e4-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'a7b8c9d0-e1f2-3456-abcd-567890123456', '{\"name\": \"Jadavpur University\", \"status\": \"active\"}', '{\"name\": \"Jadavpur University\", \"status\": \"active\"}', NULL, '2026-07-02 07:08:39'),
('d9be0991-75e4-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'e5f6a7b8-c9d0-1234-efab-345678901234', '{\"name\": \"Jawaharlal Nehru University\", \"status\": \"active\"}', '{\"name\": \"Jawaharlal Nehru University\", \"status\": \"active\"}', NULL, '2026-07-02 07:08:39'),
('d9beb3e5-75e4-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'd0e1f2a3-b4c5-6789-defa-890123456789', '{\"name\": \"Manipal Academy of Higher Education\", \"status\": \"active\"}', '{\"name\": \"Manipal Academy of Higher Education\", \"status\": \"active\"}', NULL, '2026-07-02 07:08:39'),
('d9bf2d45-75e4-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'e1f2a3b4-c5d6-7890-efab-901234567890', '{\"name\": \"National Institute of Technology Tiruchirappalli\", \"status\": \"active\"}', '{\"name\": \"National Institute of Technology Tiruchirappalli\", \"status\": \"active\"}', NULL, '2026-07-02 07:08:39'),
('d9bfe73c-75e4-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'c9d0e1f2-a3b4-5678-cdef-789012345678', '{\"name\": \"SRM Institute of Science and Technology\", \"status\": \"active\"}', '{\"name\": \"SRM Institute of Science and Technology\", \"status\": \"active\"}', NULL, '2026-07-02 07:08:39'),
('d9c045f6-75e4-11f1-8ac5-a0510b1a7444', NULL, 'update', 'universities', 'b8c9d0e1-f2a3-4567-bcde-678901234567', '{\"name\": \"Vellore Institute of Technology\", \"status\": \"active\"}', '{\"name\": \"Vellore Institute of Technology\", \"status\": \"active\"}', NULL, '2026-07-02 07:08:39'),
('ea672257-7310-11f1-81b7-a0510b1a7448', NULL, 'create', 'moderation_queue', 'ea66e0bb-7310-11f1-81b7-a0510b1a7448', NULL, '{\"entity_type\": \"\", \"entity_id\": \"ans00001-0000-0000-0000-000000000001\", \"status\": \"resolved\"}', NULL, '2026-06-28 16:46:07'),
('ea67290f-7310-11f1-81b7-a0510b1a7448', NULL, 'create', 'moderation_queue', 'ea672549-7310-11f1-81b7-a0510b1a7448', NULL, '{\"entity_type\": \"\", \"entity_id\": \"ans00001-0000-0000-0000-000000000001\", \"status\": \"resolved\"}', NULL, '2026-06-28 16:46:07'),
('ea672e2d-7310-11f1-81b7-a0510b1a7448', NULL, 'create', 'moderation_queue', 'ea672a9b-7310-11f1-81b7-a0510b1a7448', NULL, '{\"entity_type\": \"\", \"entity_id\": \"ans00001-0000-0000-0000-000000000001\", \"status\": \"resolved\"}', NULL, '2026-06-28 16:46:07'),
('ea68eeaa-7310-11f1-81b7-a0510b1a7448', NULL, 'create', 'moderation_queue', 'ea67f5df-7310-11f1-81b7-a0510b1a7448', NULL, '{\"entity_type\": \"\", \"entity_id\": \"ans00001-0000-0000-0000-000000000001\", \"status\": \"resolved\"}', NULL, '2026-06-28 16:46:07'),
('ef43a664-75dc-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'a1b2c3d4-e5f6-7890-abcd-ef1234567890', NULL, '{\"name\": \"Indian Institute of Technology Bombay\", \"slug\": \"iit-bombay\", \"status\": \"active\"}', NULL, '2026-07-02 06:11:59'),
('ef44f372-75dc-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'b2c3d4e5-f6a7-8901-bcde-f12345678901', NULL, '{\"name\": \"University of Delhi\", \"slug\": \"delhi-university\", \"status\": \"active\"}', NULL, '2026-07-02 06:11:59'),
('ef45e84f-75dc-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'c3d4e5f6-a7b8-9012-cdef-123456789012', NULL, '{\"name\": \"Indian Institute of Technology Madras\", \"slug\": \"iit-madras\", \"status\": \"active\"}', NULL, '2026-07-02 06:11:59'),
('ef479f51-75dc-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'd4e5f6a7-b8c9-0123-defa-234567890123', NULL, '{\"name\": \"Birla Institute of Technology and Science Pilani\", \"slug\": \"bits-pilani\", \"status\": \"active\"}', NULL, '2026-07-02 06:11:59'),
('ef4840f9-75dc-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'e5f6a7b8-c9d0-1234-efab-345678901234', NULL, '{\"name\": \"Jawaharlal Nehru University\", \"slug\": \"jnu-delhi\", \"status\": \"active\"}', NULL, '2026-07-02 06:11:59'),
('ef48db64-75dc-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'f6a7b8c9-d0e1-2345-fabc-456789012345', NULL, '{\"name\": \"Anna University\", \"slug\": \"anna-university\", \"status\": \"active\"}', NULL, '2026-07-02 06:11:59'),
('ef4966ff-75dc-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'a7b8c9d0-e1f2-3456-abcd-567890123456', NULL, '{\"name\": \"Jadavpur University\", \"slug\": \"jadavpur-university\", \"status\": \"active\"}', NULL, '2026-07-02 06:11:59'),
('ef4a0d7a-75dc-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'b8c9d0e1-f2a3-4567-bcde-678901234567', NULL, '{\"name\": \"Vellore Institute of Technology\", \"slug\": \"vit-vellore\", \"status\": \"active\"}', NULL, '2026-07-02 06:11:59'),
('ef4a7ae8-75dc-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'c9d0e1f2-a3b4-5678-cdef-789012345678', NULL, '{\"name\": \"SRM Institute of Science and Technology\", \"slug\": \"srm-chennai\", \"status\": \"active\"}', NULL, '2026-07-02 06:11:59'),
('ef4b012e-75dc-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'd0e1f2a3-b4c5-6789-defa-890123456789', NULL, '{\"name\": \"Manipal Academy of Higher Education\", \"slug\": \"manipal\", \"status\": \"active\"}', NULL, '2026-07-02 06:11:59'),
('ef4b809a-75dc-11f1-8ac5-a0510b1a7444', NULL, 'create', 'universities', 'e1f2a3b4-c5d6-7890-efab-901234567890', NULL, '{\"name\": \"National Institute of Technology Tiruchirappalli\", \"slug\": \"nit-trichy\", \"status\": \"active\"}', NULL, '2026-07-02 06:11:59'),
('f02f12db-7501-11f1-81b7-a0510b1a7448', NULL, 'create', 'college_accounts', '3e6458a6-996b-4cff-aab7-c8dfcd9c4491', NULL, '{\"institute_name\": \"grgrger\", \"email\": \"admin@edusearch.in\", \"status\": \"pending\"}', NULL, '2026-07-01 04:03:50');

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

--
-- Dumping data for table `blacklisted_entities`
--

INSERT INTO `blacklisted_entities` (`id`, `entity_type`, `entity_value`, `reason`, `is_active`, `expires_at`, `added_by`, `created_at`, `updated_at`) VALUES
('d55124b8-7313-11f1-81b7-a0510b1a7448', 'ip', '45.33.32.156', 'Scraping college data repeatedly', 1, '2026-07-28 17:07:00', NULL, '2026-06-26 17:07:00', '2026-06-28 17:07:00'),
('d5515449-7313-11f1-81b7-a0510b1a7448', 'email', 'spam-bot@tempmail.com', 'Automated spam account posting fake reviews', 1, NULL, NULL, '2026-06-23 17:07:00', '2026-06-28 17:07:00'),
('d551586a-7313-11f1-81b7-a0510b1a7448', 'ip', '103.21.244.0', 'Mass lead form submissions from bot network', 1, '2026-08-27 17:07:00', NULL, '2026-06-27 17:07:00', '2026-06-28 17:07:00'),
('d5515ac7-7313-11f1-81b7-a0510b1a7448', 'user', 'user-spam-001', 'Repeated offensive content in Q&A section', 1, '2026-07-12 17:07:00', NULL, '2026-06-25 17:07:00', '2026-06-28 17:07:00'),
('d5515c52-7313-11f1-81b7-a0510b1a7448', 'email', 'fake-college@scam.in', 'Fake college registration attempt', 1, NULL, NULL, '2026-06-21 17:07:00', '2026-06-28 17:07:00'),
('d5515da1-7313-11f1-81b7-a0510b1a7448', 'ip', '198.51.100.42', 'Review bombing - 15 negative reviews in 2 minutes', 0, '2026-06-27 17:07:00', NULL, '2026-06-18 17:07:00', '2026-06-28 17:07:00'),
('d5515f30-7313-11f1-81b7-a0510b1a7448', 'phone', '+919999900000', 'Spam calls reported by multiple users', 1, '2026-09-26 17:07:00', NULL, '2026-06-24 17:07:00', '2026-06-28 17:07:00'),
('d551610b-7313-11f1-81b7-a0510b1a7448', 'device', 'fp-abc123def456', 'Known fraud device fingerprint', 1, NULL, NULL, '2026-06-22 17:07:00', '2026-06-28 17:07:00');

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
-- Table structure for table `careers`
--

CREATE TABLE `careers` (
  `id` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `stream` enum('Science','Commerce','Humanities') NOT NULL,
  `sub_stream` varchar(100) NOT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `job_profile` text DEFAULT NULL,
  `how_to_get_there` text DEFAULT NULL,
  `salary_range` varchar(100) DEFAULT NULL,
  `skills_required` varchar(255) DEFAULT NULL,
  `is_popular` tinyint(1) DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `careers`
--

INSERT INTO `careers` (`id`, `name`, `slug`, `stream`, `sub_stream`, `short_description`, `job_profile`, `how_to_get_there`, `salary_range`, `skills_required`, `is_popular`, `image_url`, `created_at`, `updated_at`) VALUES
('car-1-uuid', 'Aeronautical Engineer', 'aeronautical-engineer', 'Science', 'Aviation & Aerospace', 'Aeronautical engineering focuses on the design, development, and testing of aircraft, missiles, and spacecraft operating within Earth\'s atmosphere.', 'Aeronautical engineers are primarily responsible for creating safer, more efficient, and structurally sound commercial aircraft, military fighter jets, helicopters, and drones. The job profile includes performing aerodynamic testing in wind tunnels, designing engines, analyzing structural fatigue, and supervising manufacturing lines. Modern aeronautical engineers also work extensively with flight control electronics and digital twin simulations to predict flight behaviour.', '1. **Higher Secondary Schooling (10+2)**: Science stream with Physics, Chemistry, and Mathematics (PCM).\n2. **Entrance Exam**: Qualify for engineering entrance exams like JEE Main and JEE Advanced.\n3. **Undergraduate Degree**: Pursue a 4-year B.Tech / B.E. in Aeronautical, Aerospace, or Mechanical Engineering from a recognized college.\n4. **Postgraduate (Optional)**: Complete an M.Tech or MS for specialized research fields.', '6 - 18 LPA', 'Aerodynamics, Calculus, CAD/CAM, Matlab, Structural Analysis, Physics', 1, 'https://images.unsplash.com/photo-1540962351504-03099e0a754b?w=600&h=400&fit=crop', '2026-06-21 07:15:21', '2026-06-21 07:15:21'),
('car-2-uuid', 'Computer Engineer', 'computer-engineer', 'Science', 'Software & IT', 'Computer engineering combines computer science and electronic engineering to design hardware, write software, and build complex system architectures.', 'Computer engineers work at the intersection of hardware and software. They design microprocessors, create firmware for embedded systems, write compiler routines, and build complex software architectures like cloud systems, operating systems, and AI models. They ensure hardware components communicate seamlessly with software packages to produce fast, reliable, and energy-efficient systems.', '1. **10+2 Education**: Complete High School with Physics, Chemistry, and Mathematics (PCM).\n2. **Entrance Examination**: Sit for JEE Main, BITSAT, VITEEE, or state-level engineering entrance exams.\n3. **Bachelor\\\'s Degree**: Complete B.Tech/B.E. in Computer Science Engineering (CSE), Information Technology, or Computer Engineering.\n4. **Skills & Internships**: Build a solid programming portfolio on GitHub and intern as a developer.', '8 - 25 LPA', 'Data Structures, Algorithms, C++, Python, Computer Architecture, Cloud Computing', 1, 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=600&h=400&fit=crop', '2026-06-21 07:15:21', '2026-06-21 07:15:21'),
('car-3-uuid', 'Doctor (MBBS)', 'doctor', 'Science', 'Medical & Health', 'Medical practitioners diagnose illnesses, prescribe treatments, perform surgeries, and provide general health counselling to patients.', 'Doctors are the backbone of clinical healthcare. They examine patients, analyze medical reports and diagnostic images, formulate treatment plans, perform surgical operations, and prescribe medications. Depending on specialization, a doctor can be a cardiologist, surgeon, pediatrician, neurologist, or general physician.', '1. **10+2 Education**: Science stream with Physics, Chemistry, and Biology (PCB) as core subjects.\n2. **Entrance Exam**: Crack the National Eligibility cum Entrance Test (NEET UG) with a top rank.\n3. **Medical Degree**: Complete 4.5 years of MBBS course followed by 1 year of compulsory rotating internship.\n4. **Specialization**: Crack NEET PG to pursue MD, MS, or DNB courses.', '9 - 30 LPA', 'Human Anatomy, Pharmacology, Clinical Diagnosis, Patient Care, Medical Ethics', 1, 'https://images.unsplash.com/photo-1537368910025-700350fe46c7?w=600&h=400&fit=crop', '2026-06-21 07:15:21', '2026-06-21 07:15:21'),
('car-4-uuid', 'Chartered Accountant', 'chartered-accountant', 'Commerce', 'Finance & Accounting', 'Chartered Accountants handle financial audits, corporate tax filing, accounting advice, and financial management for businesses.', 'Chartered Accountants (CAs) serve as financial advisors, auditors, and tax consultants for corporations, government bodies, and individuals. They analyze ledger accounts, perform statutory financial audits, formulate business taxation strategies, manage corporate mergers/acquisitions, and advise on cost optimization and risk management.', '1. **Register with ICAI**: Register for the CA Foundation exam after completing Class 10 or 12.\n2. **CA Intermediate**: Complete CA Foundation and clear both groups of CA Intermediate.\n3. **Articleship**: Undergo 2-3 years of practical articleship training under a practicing CA.\n4. **CA Final**: Clear the CA Final examination to register as a member of the ICAI.', '7 - 20 LPA', 'Financial Auditing, Direct & Indirect Taxation, Corporate Law, Tally, Business Valuation', 1, 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=600&h=400&fit=crop', '2026-06-21 07:15:21', '2026-06-21 07:15:21'),
('car-5-uuid', 'Commercial Pilot', 'pilot', 'Science', 'Aviation & Aerospace', 'Commercial pilots fly cargo, charter flights, and commercial aircraft carrying passengers across domestic and international routes.', 'Commercial pilots operate commercial aircraft for commercial airlines. Before flight, they run through safety checklists, check weather routes, verify cargo weight, and plan fuel requirements. During flight, they navigate the skies, communicate with Air Traffic Control, monitor instruments, and manage emergency protocols. They ensure passengers reach their destination safely and on schedule.', '1. **10+2 Education**: Physics and Mathematics are mandatory core subjects in 10+2.\n2. **Medical Evaluation**: Clear Class II and Class I medical exams conducted by DGCA-approved doctors.\n3. **Flying School**: Join a DGCA-approved Flying Training Organization (FTO).\n4. **Obtain Licenses**: Clear DGCA theory exams and complete 200 hours of flying to obtain a Commercial Pilot License (CPL).', '12 - 36 LPA', 'Meteorology, Navigation, Aircraft Systems, Crisis Management, Communication', 1, 'https://images.unsplash.com/photo-1506012787146-f92b2d7d6d96?w=600&h=400&fit=crop', '2026-06-21 07:15:21', '2026-06-21 07:15:21'),
('car-6-uuid', 'Corporate Lawyer', 'corporate-lawyer', 'Humanities', 'Legal & Law', 'Corporate lawyers advise businesses on legal rights, obligations, transaction compliance, contracts, and dispute resolutions.', 'Corporate lawyers draft commercial contracts, ensure regulatory compliance with corporate laws, structure mergers and acquisitions, protect intellectual property, and represent corporate entities in court or arbitration. They protect companies from legal liabilities and guide leadership on corporate governance.', '1. **10+2 Education**: Open to any stream (Humanities, Commerce, or Science).\n2. **Entrance Exam**: Clear CLAT (Common Law Admission Test) or AILET.\n3. **Integrative Law Degree**: Pursue a 5-year integrated B.A. LL.B., B.B.A. LL.B. or B.Com LL.B. course.\n4. **Bar Council Enrollment**: Register with the State Bar Council and pass the All India Bar Examination (AIBE).', '6 - 22 LPA', 'Contract Drafting, Corporate Law, Litigation, Critical Thinking, Negotiation', 0, 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=600&h=400&fit=crop', '2026-06-21 07:15:21', '2026-06-21 07:15:21'),
('car-7-uuid', 'Hotel Manager', 'hotel-manager', 'Humanities', 'Management', 'Hotel managers supervise lodging establishments, front desk operations, housekeeping, catering, and event hosting services.', 'Hotel managers manage the daily operations of hotels, luxury resorts, and cruise ships. They ensure guest satisfaction, monitor budgets, coordinate front desk check-ins, manage catering schedules, oversee housekeeping standards, and organize corporate conferences or wedding events. They balance financial management with premium hospitality service.', '1. **10+2 Education**: Stream agnostic, but English must be a core subject.\n2. **Entrance Exam**: Clear NCHMCT JEE (National Council for Hotel Management Joint Entrance Exam).\n3. **Undergraduate Degree**: Pursue a 3-4 year B.Sc in Hospitality & Hotel Administration, or Bachelor of Hotel Management (BHM).\n4. **Industrial Training**: Undergo training in leading hotel chains during the degree.', '5 - 15 LPA', 'Hospitality Operations, Staff Management, Customer Relations, Budgeting, Event Planning', 1, 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&h=400&fit=crop', '2026-06-21 07:15:21', '2026-06-21 07:15:21'),
('car-8-uuid', 'Graphic Designer', 'graphic-designer', 'Humanities', 'Creative & Design', 'Graphic designers create visual content to communicate marketing messages, brand identity, layout designs, and illustrations.', 'Graphic designers develop visual concepts for advertising campaigns, corporate logo designs, website user interfaces, packaging, and digital publications. They use professional software tools to combine typography, layout systems, photography, and illustration into compelling marketing materials.', '1. **10+2 Education**: Completed in any stream. A background in Fine Arts is a plus.\n2. **Design Entrances**: Crack entrance exams like NID DAT, UCEED, or NIFT.\n3. **Design Degree**: Complete a Bachelor of Design (B.Des) in Communication Design or Graphic Design.\n4. **Portfolio Development**: Build an active online portfolio showing packaging, brand, and UX designs.', '4 - 12 LPA', 'Adobe Photoshop, Adobe Illustrator, Figma, Typography, Color Theory, Brand Identity', 0, 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=600&h=400&fit=crop', '2026-06-21 07:15:21', '2026-06-21 07:15:21'),
('car-9-uuid', 'Investment Banker', 'investment-banker', 'Commerce', 'Finance & Accounting', 'Investment bankers assist corporations, governments, and institutions in raising capital, restructuring debt, and structuring mergers.', 'Investment bankers work in corporate finance teams, advising businesses on issuing equity shares or corporate bonds to raise capital. They create mathematical valuation models, conduct due diligence, structure mergers and acquisitions, prepare prospectus documents, and manage large-scale corporate restructurings.', '1. **10+2 Education**: Commerce stream with Math/Applied Mathematics is highly recommended.\n2. **Undergraduate Degree**: Pursue B.Com (Hons), Bachelor of Management Studies (BMS), or B.Sc in Economics/Finance.\n3. **Specialized Postgraduate (Optional)**: Complete an MBA in Finance or clear Chartered Financial Analyst (CFA) levels.\n4. **Networking**: Participate in internship programs in financial centers.', '10 - 30 LPA', 'Financial Modelling, Excel Formulas, Corporate Finance, Mergers & Acquisitions, Valuation', 1, 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=600&h=400&fit=crop', '2026-06-21 07:15:21', '2026-06-21 07:15:21');

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
(722, 35, 'Uttar Dinajpur (North Dinajpur)'),
(723, 28, 'Pilani'),
(724, 3, 'Guwahati'),
(725, 35, 'Kharagpur'),
(726, 33, 'Roorkee'),
(727, 31, 'Warangal'),
(728, 16, 'Surathkal'),
(729, 16, 'Manipal'),
(730, 27, 'Phagwara'),
(731, 25, 'Bhubaneswar'),
(732, 34, 'Noida'),
(733, 34, 'Greater Noida');

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
('col-aiims-0011', 'All India Institute of Medical Sciences Delhi', 'aiims-delhi', 'govt', 'central', 'active', 1, 1, 11, 1, NULL, NULL, 110001, 7, '1956', 0, 'Autonomous Institute of National Importance', NULL, 0, 'A++', 1, 0, 0, 3500, 500, 115, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:32', '2026-06-23 05:40:24', NULL, NULL, NULL, 4.8, 6, NULL, 6, 'published'),
('col-aiimsj-0029', 'All India Institute of Medical Sciences Jodhpur', 'aiims-jodhpur', 'govt', 'central', 'active', 1, 1, 0, 10, NULL, NULL, 526, 28, '2012', 0, NULL, NULL, 0, 'A++', 1, 0, 1, 1500, 300, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-23 05:38:42', '2026-06-23 05:38:42', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('col-aiimspb-0030', 'All India Institute of Medical Sciences Patna', 'aiims-patna', 'govt', 'central', 'active', 1, 1, 0, 12, NULL, NULL, 93, 4, '2012', 0, NULL, NULL, 0, 'A+', 1, 0, 1, 1200, 250, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-23 05:38:43', '2026-06-23 05:38:43', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('col-bits-0008', 'Birla Institute of Technology and Science Pilani', 'bits-pilani', '', '', 'active', 0, 1, 8, 22, NULL, NULL, 333031, 8, '1964', 0, 'Deemed to be University', NULL, 0, 'A++', 1, 0, 0, 16000, 600, 328, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:32', '2026-06-21 15:44:39', NULL, NULL, NULL, 4.6, 4, NULL, 3, 'published'),
('col-iima-0009', 'Indian Institute of Management Ahmedabad', 'iim-ahmedabad', 'govt', 'central', 'active', 1, 1, 9, 1, NULL, NULL, 380015, 24, '1961', 0, 'Autonomous Institute of National Importance', NULL, 0, 'A++', 1, 0, 0, 2000, 120, 102, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:32', '2026-06-23 05:40:24', NULL, NULL, NULL, 4.9, 4, NULL, 4, 'published'),
('col-iimb-0010', 'Indian Institute of Management Bangalore', 'iim-bangalore', 'govt', 'central', 'active', 0, 1, 10, 2, NULL, NULL, 560012, 18, '1973', 0, 'Autonomous Institute of National Importance', NULL, 0, 'A++', 1, 0, 0, 1800, 110, 100, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:32', '2026-06-23 05:40:24', NULL, NULL, NULL, 4.8, 3, NULL, 2, 'published'),
('col-iitb-0001', 'Indian Institute of Technology Bombay', 'iit-bombay', 'govt', 'central', 'active', 1, 1, 1, 3, NULL, NULL, 384, 20, '1958', 0, 'Autonomous Institute of National Importance', NULL, 0, 'A++', 1, 0, 1, 12000, 780, 1200, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:32', '2026-06-23 05:32:04', NULL, NULL, NULL, 4.7, 5, NULL, 4, 'published'),
('col-iitbbs-0018', 'Indian Institute of Technology Bhubaneswar', 'iit-bhubaneswar', 'govt', 'central', 'active', 1, 1, 0, 18, NULL, NULL, 731, 25, '2008', 0, NULL, NULL, 0, 'A', 1, 0, 1, 3200, 190, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-23 05:32:04', '2026-06-23 05:32:04', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('col-iitbhu-0023', 'Indian Institute of Technology Banaras Hindu University', 'iit-bhu', 'govt', 'central', 'active', 1, 1, 0, 10, NULL, NULL, 669, 34, '1919', 0, NULL, NULL, 0, 'A++', 1, 0, 1, 9000, 500, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-23 05:32:05', '2026-06-23 05:32:05', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('col-iitd-0002', 'Indian Institute of Technology Delhi', 'iit-delhi', 'govt', 'central', 'active', 1, 1, 2, 2, NULL, NULL, 139, 9, '1963', 0, 'Autonomous Institute of National Importance', NULL, 0, 'A++', 1, 0, 1, 11500, 720, 320, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:32', '2026-06-23 05:32:04', NULL, NULL, NULL, 4.7, 4, NULL, 3, 'published'),
('col-iitdh-0022', 'Indian Institute of Technology Dharwad', 'iit-dharwad', 'govt', 'central', 'active', 1, 1, 0, 24, NULL, NULL, 275, 16, '2016', 0, NULL, NULL, 0, 'A', 1, 0, 1, 1800, 110, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-23 05:32:05', '2026-06-23 05:32:05', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('col-iitg-0014', 'Indian Institute of Technology Guwahati', 'iit-guwahati', 'govt', 'central', 'active', 1, 1, 0, 6, NULL, NULL, 724, 3, '1994', 0, NULL, NULL, 0, 'A++', 1, 0, 1, 7000, 400, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-23 05:32:04', '2026-06-23 05:32:04', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('col-iith-0015', 'Indian Institute of Technology Hyderabad', 'iit-hyderabad', 'govt', 'central', 'active', 1, 1, 0, 8, NULL, NULL, 577, 31, '2008', 0, NULL, NULL, 0, 'A+', 1, 0, 1, 6000, 350, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-23 05:32:04', '2026-06-23 05:32:04', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('col-iiti-0016', 'Indian Institute of Technology Indore', 'iit-indore', 'govt', 'central', 'active', 1, 1, 0, 14, NULL, NULL, 338, 19, '2009', 0, NULL, NULL, 0, 'A+', 1, 0, 1, 3500, 200, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-23 05:32:04', '2026-06-23 05:32:04', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('col-iitj-0019', 'Indian Institute of Technology Jodhpur', 'iit-jodhpur', 'govt', 'central', 'active', 1, 1, 0, 20, NULL, NULL, 526, 28, '2008', 0, NULL, NULL, 0, 'A+', 1, 0, 1, 2800, 170, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-23 05:32:04', '2026-06-23 05:32:04', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('col-iitk-0004', 'Indian Institute of Technology Kanpur', 'iit-kanpur', 'govt', 'central', 'active', 1, 1, 4, 4, NULL, NULL, 670, 34, '1960', 0, 'Autonomous Institute of National Importance', NULL, 0, 'A++', 1, 0, 1, 10500, 650, 1055, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:32', '2026-06-23 05:32:04', NULL, NULL, NULL, 4.7, 4, NULL, 3, 'published'),
('col-iitkgp-0005', 'Indian Institute of Technology Kharagpur', 'iit-kharagpur', 'govt', 'central', 'active', 1, 1, 5, 5, NULL, NULL, 725, 35, '1951', 0, 'Autonomous Institute of National Importance', NULL, 0, 'A++', 1, 0, 1, 13000, 850, 2100, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:32', '2026-06-23 05:32:04', NULL, NULL, NULL, 4.6, 4, NULL, 3, 'published'),
('col-iitm-0003', 'Indian Institute of Technology Madras', 'iit-madras', 'govt', 'central', 'active', 1, 1, 3, 1, NULL, NULL, 544, 30, '1959', 0, 'Autonomous Institute of National Importance', NULL, 0, 'A++', 1, 0, 1, 12500, 800, 630, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:32', '2026-06-23 05:32:04', NULL, NULL, NULL, 4.9, 4, NULL, 3, 'published'),
('col-iitmn-0020', 'Indian Institute of Technology Mandi', 'iit-mandi', 'govt', 'central', 'active', 1, 1, 0, 15, NULL, NULL, 212, 13, '2009', 0, NULL, NULL, 0, 'A+', 1, 0, 1, 2500, 160, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-23 05:32:04', '2026-06-23 05:32:04', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('col-iitp-0017', 'Indian Institute of Technology Patna', 'iit-patna', 'govt', 'central', 'active', 1, 1, 0, 19, NULL, NULL, 93, 4, '2008', 0, NULL, NULL, 0, 'A+', 1, 0, 1, 3000, 180, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-23 05:32:04', '2026-06-23 05:32:04', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('col-iitpkd-0021', 'Indian Institute of Technology Palakkad', 'iit-palakkad', 'govt', 'central', 'active', 1, 1, 0, 22, NULL, NULL, 302, 17, '2016', 0, NULL, NULL, 0, 'A', 1, 0, 1, 2000, 120, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-23 05:32:05', '2026-06-23 05:32:05', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('col-iitr-0013', 'Indian Institute of Technology Roorkee', 'iit-roorkee', 'govt', 'central', 'active', 1, 1, 0, 7, NULL, NULL, 726, 33, '0000', 0, NULL, NULL, 0, 'A++', 1, 0, 1, 8500, 450, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-23 05:32:04', '2026-06-23 05:32:04', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('col-nitk-0007', 'National Institute of Technology Surathkal', 'nit-surathkal', 'govt', 'central', 'active', 0, 1, 7, 10, NULL, NULL, 575025, 18, '1960', 0, 'Autonomous Institute', NULL, 0, 'A++', 1, 0, 0, 7500, 420, 295, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:32', '2026-06-23 05:40:24', NULL, NULL, NULL, 4.4, 3, NULL, 2, 'published'),
('col-nitt-0006', 'National Institute of Technology Tiruchirappalli', 'nit-tiruchirappalli', 'govt', 'central', 'active', 1, 1, 6, 9, NULL, NULL, 620015, 33, '1964', 0, 'Autonomous Institute', NULL, 0, 'A++', 1, 0, 0, 8500, 480, 325, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:32', '2026-06-23 05:40:24', NULL, NULL, NULL, 4.5, 3, NULL, 2, 'published'),
('col-nlsiu-0012', 'National Law School of India University Bangalore', 'nlsiu-bangalore', 'govt', 'central', 'active', 0, 1, 12, 1, NULL, NULL, 560012, 18, '1986', 0, 'State University', NULL, 0, 'A++', 1, 0, 0, 1200, 85, 25, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:32', '2026-06-23 05:40:24', NULL, NULL, NULL, 4.8, 3, NULL, 2, 'published'),
('e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'fhfhrfhfrh', 'fhfhrfhfrh', 'govt', 'central', 'active', 0, 0, 0, 5, NULL, NULL, 8, 1, NULL, 0, NULL, NULL, 1, 'A++', 1, 1, 0, 20000, 5558, 56, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 16:13:20', '2026-06-28 04:22:06', '2000', 'ebfuebfd', NULL, 0, 0, NULL, 0, 'published');

--
-- Triggers `colleges`
--
DELIMITER $$
CREATE TRIGGER `trg_colleges_after_delete` AFTER DELETE ON `colleges` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'colleges', OLD.id,
        JSON_OBJECT('name', OLD.name, 'slug', OLD.slug),
        NULL, NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_colleges_after_insert` AFTER INSERT ON `colleges` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'colleges', NEW.id, NULL,
        JSON_OBJECT('name', NEW.name, 'slug', NEW.slug, 'status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_colleges_after_update` AFTER UPDATE ON `colleges` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'colleges', NEW.id,
        JSON_OBJECT('name', OLD.name, 'status', OLD.status),
        JSON_OBJECT('name', NEW.name, 'status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `college_accounts`
--

CREATE TABLE `college_accounts` (
  `id` char(36) NOT NULL,
  `college_id` char(36) DEFAULT NULL,
  `institute_type` enum('college','university','institute') NOT NULL DEFAULT 'college',
  `institute_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `state_id` int(11) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `established_year` year(4) DEFAULT NULL,
  `affiliation_details` text DEFAULT NULL,
  `pan_number` varchar(20) DEFAULT NULL,
  `aadhar_number` varchar(20) DEFAULT NULL,
  `gst_number` varchar(30) DEFAULT NULL,
  `pan_doc` varchar(255) DEFAULT NULL,
  `aadhar_doc` varchar(255) DEFAULT NULL,
  `gst_doc` varchar(255) DEFAULT NULL,
  `affiliation_doc` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `temp_password` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','active','suspended') NOT NULL DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `college_accounts`
--

INSERT INTO `college_accounts` (`id`, `college_id`, `institute_type`, `institute_name`, `contact_person`, `designation`, `email`, `phone`, `website`, `state_id`, `city`, `established_year`, `affiliation_details`, `pan_number`, `aadhar_number`, `gst_number`, `pan_doc`, `aadhar_doc`, `gst_doc`, `affiliation_doc`, `password_hash`, `temp_password`, `status`, `rejection_reason`, `approved_by`, `approved_at`, `last_login`, `created_at`, `updated_at`) VALUES
('3e6458a6-996b-4cff-aab7-c8dfcd9c4491', NULL, 'college', 'grgrger', 'Madhav Arora', 'Assistant Professor', 'admin@edusearch.in', '+919877275894', 'http://localhost:3000', 11, 'North West Delhi', NULL, 'hbvd h', 'ABCDS1234F', '538747467646', NULL, 'pan_38ba517ce10c36ec777c.jpg', 'aadhar_ecc203223ce47425a58e.jpg', NULL, NULL, '$2y$10$jRLLBFmbS4CV41JTioEPmu8eO4eIrXVGBm40H8i8DKpxWN8zn1KUS', NULL, 'pending', NULL, NULL, NULL, NULL, '2026-07-01 04:03:50', '2026-07-01 04:03:50'),
('65245970-ace5-456a-9d2d-8c5ac270c1f3', NULL, 'college', 'grgrger', 'Madhav Arora', 'ffrfr', 'madhavarora132005@gmail.com', '+919877275894', 'http://localhost:3000', 1, 'gtrgerg', '2000', 'vfvfbcv v v d f', 'ABCDE1234F', '254356546464', NULL, 'pan_deccf961edc3683b.jpg', 'aadhar_49cba9f4e636c052.jpg', NULL, NULL, '$2y$10$gpC2JLip031teIkGn9JdbOyZB7OpxKUmSwjIaTiEX6U43JN4O/VnK', NULL, 'pending', NULL, NULL, NULL, NULL, '2026-06-27 13:02:30', '2026-06-27 13:02:30'),
('80d73b32-163e-4789-9789-b7a6a1dc7da8', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'college', 'fhfhrfhfrh', 'Madhav Arora', 'vvc', 'aroramadhav1213@gmail.com', '+919877275894', 'https://www.genzmedia.online/', 14, 'Hoshiarpur', '2000', 'jbhhevfh', 'ABDSE1234F', '254356546464', NULL, 'pan_244ddc148a6a7ac17c6e.jpg', 'aadhar_72773734b9b3130cc7b8.jpg', NULL, NULL, '$2y$10$8VrtJMY.xWWAiG.a.sDEOeVpYPZTAi3QsxIZkrb8LaDFxS/qsRtOm', '12345678', 'approved', NULL, 0, '2026-06-27 16:13:20', '2026-06-29 15:46:30', '2026-06-27 15:23:43', '2026-06-29 15:46:30');

--
-- Triggers `college_accounts`
--
DELIMITER $$
CREATE TRIGGER `trg_college_accounts_after_insert` AFTER INSERT ON `college_accounts` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'college_accounts', NEW.id, NULL,
        JSON_OBJECT('institute_name', NEW.institute_name, 'email', NEW.email, 'status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_college_accounts_after_update` AFTER UPDATE ON `college_accounts` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'college_accounts', NEW.id,
        JSON_OBJECT('status', OLD.status),
        JSON_OBJECT('status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;

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

--
-- Dumping data for table `college_accreditations`
--

INSERT INTO `college_accreditations` (`id`, `college_id`, `accreditation_body`, `accreditation_grade`, `accreditation_year`, `accreditation_valid_until`) VALUES
('0a33f452-c0c8-4b0d-b1f4-7de3482ea0c5', 'col-nitk-0007', 'NAAC', 'A++', '2022', NULL),
('1aebcf36-2490-400c-a9ab-b8cd4fe3fa52', 'col-iitb-0001', 'NAAC', 'A++', '2022', NULL),
('2b5d4e41-006f-4f52-87bc-f242e2abf7e1', 'col-iitd-0002', 'NAAC', 'A++', '2023', NULL),
('2c6ec983-9028-4bfa-8a2a-edc275c33020', 'col-aiims-0011', 'NAAC', 'A++', '2022', NULL),
('54ee2ef9-ea1c-452d-8e5e-599b317cacaa', 'col-nlsiu-0012', 'NAAC', 'A++', '2023', NULL),
('63de66ba-84f9-4b65-866d-2a964b45e01f', 'col-iitk-0004', 'NAAC', 'A++', '2023', NULL),
('7bb178ff-6145-4f4a-bb1f-3ce9d5d43dfa', 'col-iitm-0003', 'NAAC', 'A++', '2022', NULL),
('8a3afa98-d1ae-48b6-a6d6-a0b119a14e7f', 'col-bits-0008', 'NAAC', 'A++', '2023', NULL),
('b3cbd222-fedb-44d8-8547-c69dbb79c9ad', 'col-iimb-0010', 'NAAC', 'A++', '2023', NULL),
('bbd0893a-7c40-4dc2-ae7a-c728b86394d2', 'col-iitkgp-0005', 'NAAC', 'A++', '2022', NULL),
('d642a15c-e9d7-4b42-ae95-1184871bdbba', 'col-nitt-0006', 'NAAC', 'A++', '2023', NULL),
('ea9b641f-c30d-43ca-b3fe-e4ac0d24e80d', 'col-iima-0009', 'NAAC', 'A++', '2022', NULL);

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
('44fc9b5e-0904-46ba-ae82-aad055a4128d', 'col-aiims-0011', 'Admission to AIIMS Delhi MBBS is through NEET UG (National Eligibility cum Entrance Test). Candidates must qualify NEET UG with a top All India Rank.', '[\"NEET UG\"]', NULL, NULL, 1, 0, 0, 0, 0, 'online', 'Selection based on NEET UG All India Rank. counselling through MCC for All India Quota seats.'),
('4d228e9e-89d2-446e-9fff-0f6c6fada68a', 'col-iitkgp-0005', 'Admission to IIT Kharagpur is through JEE Advanced. Candidates must qualify JEE Main and then appear for JEE Advanced. Seat allocation through JoSAA counselling.', '[\"JEE Advanced\",\"JEE Main\"]', NULL, NULL, 1, 0, 0, 0, 0, 'online', 'Selection based on JEE Advanced All India Rank. JoSAA counselling with multiple rounds.'),
('7251dbd4-d88e-4fb7-99b2-ba0b2da2d137', 'col-iitk-0004', 'Admission to IIT Kanpur is through JEE Advanced. Candidates must qualify JEE Main first. Seats are allotted through JoSAA counselling based on rank and preferences.', '[\"JEE Advanced\",\"JEE Main\"]', NULL, NULL, 1, 0, 0, 0, 0, 'online', 'Selection based on JEE Advanced rank. JoSAA counselling with multiple rounds.'),
('7428c853-bb56-4e6d-abc1-e86e13cd2a8f', 'col-nlsiu-0012', 'Admission to NLSIU BA LLB is through CLAT (Common Law Admission Test). Candidates must qualify CLAT with a top All India Rank.', '[\"CLAT\"]', NULL, NULL, 1, 0, 0, 0, 0, 'online', 'Selection based on CLAT All India Rank. Counselling through CLAT Consortium.'),
('a270038f-6935-41f7-8e97-85bf819dab4a', 'col-iitd-0002', 'Admission to IIT Delhi is through JEE Advanced examination. Candidates must first qualify JEE Main, then appear for JEE Advanced. Seats are allotted through JoSAA counselling.', '[\"JEE Advanced\",\"JEE Main\"]', NULL, NULL, 1, 0, 0, 0, 0, 'online', 'Selection is based on All India Rank in JEE Advanced. JoSAA counselling with multiple rounds.'),
('af1da88f-1824-444a-89b9-8d5428e9839c', 'col-iima-0009', 'Admission to IIM Ahmedabad PGP is through CAT (Common Admission Test). Shortlisted candidates are called for Written Ability Test (WAT) and Personal Interview (PI).', '[\"CAT\",\"GMAT\"]', NULL, NULL, 1, 0, 0, 0, 0, 'online', 'Final selection based on CAT score, WAT performance, PI, and academic profile. Composite score used for ranking.'),
('b3276468-b2bb-4b8e-9697-b83fc4fb899a', 'col-iitb-0001', 'Admission to IIT Bombay is through JEE Advanced examination. Candidates must first qualify JEE Main, then appear for JEE Advanced. Seats are allotted through JoSAA counselling based on All India Rank, category, and seat availability.', '[\"JEE Advanced\",\"JEE Main\"]', NULL, NULL, 1, 0, 0, 0, 0, 'online', 'Selection is based on All India Rank in JEE Advanced. Counselling is conducted through JoSAA with multiple rounds of seat allocation.'),
('c7d3692e-5002-45c6-be23-69f6591e50ea', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', '<p>&nbsp; cghbn vnhjv hb vygh</p>', '[\"uyfyft cfgdghn\"]', '2026-06-29', '2026-06-30', 1, 1, 55, 66, 1, '', NULL),
('ce63e411-22b6-4d21-a27f-76b34728bbe8', 'col-nitk-0007', 'Admission to NIT Surathkal is through JEE Main. Candidates must qualify JEE Main and apply through CSAB/JoSAA counselling.', '[\"JEE Main\"]', NULL, NULL, 1, 0, 0, 0, 0, 'online', 'Selection based on JEE Main All India Rank. CSAB/JoSAA counselling.'),
('dc47bd6a-59df-4006-89ae-bdb8fb0fc381', 'col-nitt-0006', 'Admission to NIT Trichy is through JEE Main. Candidates must qualify JEE Main and apply through CSAB/JoSAA counselling. Seats are allotted based on All India Rank in JEE Main.', '[\"JEE Main\"]', NULL, NULL, 1, 0, 0, 0, 0, 'online', 'Selection based on JEE Main All India Rank. CSAB/JoSAA counselling with multiple rounds.'),
('edb0cca9-eab5-4579-845d-97ec1a39042b', 'col-bits-0008', 'Admission to BITS Pilani is through BITSAT (BITS Admission Test). Candidates must have 75% aggregate in PCM in Class XII and qualify BITSAT. Admission is through iterative counselling based on BITSAT score.', '[\"BITSAT\"]', NULL, NULL, 1, 0, 0, 0, 0, 'online', 'Selection based on BITSAT score. Iterative counselling rounds with preference filling.'),
('f5f3afdc-3243-4509-a6bc-c2cfc10e2509', 'col-iitm-0003', 'Admission to IIT Madras is through JEE Advanced examination. Candidates must qualify JEE Main and then appear for JEE Advanced. Seat allocation is through JoSAA counselling.', '[\"JEE Advanced\",\"JEE Main\"]', NULL, NULL, 1, 0, 0, 0, 0, 'online', 'Selection based on JEE Advanced All India Rank. JoSAA counselling with multiple rounds of seat allocation.'),
('f61376c5-79ec-451c-9147-43d8f5db8c0c', 'col-iimb-0010', 'Admission to IIM Bangalore PGP is through CAT (Common Admission Test). Shortlisted candidates undergo WAT and PI.', '[\"CAT\",\"GMAT\"]', NULL, NULL, 1, 0, 0, 0, 0, 'online', 'Final selection based on CAT score, WAT, PI, and academic diversity factors.');

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
('0585259e-ecf8-497e-8001-89dbce8ce85d', 'col-nlsiu-0012', 'https://www.nls.ac.in', 'dean.acad@nls.ac.in', '+91-80-23213161', 'Nagarbhavi, Bangalore', NULL, NULL, '560072', NULL, NULL, NULL),
('39486ed4-0139-4361-af11-6617103183c6', 'col-iima-0009', 'https://www.iima.ac.in', 'dean.acad@iima.ac.in', '+91-79-63066000', 'Vastrapur, Ahmedabad', NULL, NULL, '380015', NULL, NULL, NULL),
('5e8e366c-5b96-472b-b9fa-a4a52b06c149', 'col-iitm-0003', 'https://www.iitm.ac.in', 'dean.acad@iitm.ac.in', '+91-44-22578200', 'Adyar, Chennai', NULL, NULL, '600036', NULL, NULL, NULL),
('64ad3f1f-74de-474a-80db-faf05acc0132', 'col-aiims-0011', 'https://www.aiims.ac.in', 'dean.acad@aiims.ac.in', '+91-11-26588500', 'Sri Aurobindo Marg, New Delhi', NULL, NULL, '110029', NULL, NULL, NULL),
('6c680c76-675c-442d-802d-46f02afc9160', 'col-bits-0008', 'https://www.bits-pilani.ac.in', 'dean.acad@bits-pilani.ac.in', '+91-1596-245073', 'Pilani, Rajasthan', NULL, NULL, '333031', NULL, NULL, NULL),
('70876acb-bea2-48ab-94cb-1c7c47a82255', 'col-nitt-0006', 'https://www.nitt.edu', 'dean.acad@nitt.edu', '+91-431-2503000', 'Tiruchirappalli, Tamil Nadu', NULL, NULL, '620015', NULL, NULL, NULL),
('78583ed9-1019-4c7e-af69-2a51596cf07c', 'col-iitb-0001', 'https://www.iitb.ac.in', 'dean.acad@iitb.ac.in', '+91-22-25722545', 'Powai, Mumbai', NULL, NULL, '400076', NULL, NULL, NULL),
('7d86a307-6f81-49db-af85-df43a455f5e2', 'col-iitkgp-0005', 'https://www.iitkgp.ac.in', 'dean.acad@iitkgp.ac.in', '+91-3222-255221', 'Kharagpur, West Bengal', NULL, NULL, '721302', NULL, NULL, NULL),
('82ac3836-53bb-4301-b2f5-aaa4e2e2e2f6', 'col-iitk-0004', 'https://www.iitk.ac.in', 'dean.acad@iitk.ac.in', '+91-512-2590106', 'Kalyanpur, Kanpur', NULL, NULL, '208016', NULL, NULL, NULL),
('a550cc33-e380-41f2-864c-bd4408366638', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'https://githib.com', 'madhavarora132005@gmail.com', '09877275894', 'WARD 24, OSWALI MOHALLA', 23.000000, 44.000000, '305801', 'https://maps.google.com', 66, 56),
('b730508f-ca08-448b-b4a4-18a9ce3630b6', 'col-nitk-0007', 'https://www.nitk.ac.in', 'dean.acad@nitk.ac.in', '+91-824-2474000', 'Surathkal, Mangalore', NULL, NULL, '575025', NULL, NULL, NULL),
('bdb2a348-c833-4dcb-b17e-cecb601e069a', 'col-iitd-0002', 'https://www.iitd.ac.in', 'dean.acad@iitd.ac.in', '+91-11-26591754', 'Hauz Khas, New Delhi', NULL, NULL, '110016', NULL, NULL, NULL),
('ef2b4bcd-8cb5-4c8c-83fd-f62689a90b20', 'col-iimb-0010', 'https://www.iimb.ac.in', 'dean.acad@iimb.ac.in', '+91-80-26993000', 'Bannerghatta Road, Bangalore', NULL, NULL, '560076', NULL, NULL, NULL);

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
('2ce1ecc1-7ee6-4826-9db4-e79cd75a8a1d', 'col-iima-0009', 'IIM Ahmedabad, established in 1961, is consistently ranked as the number one management institution in India. Located in Ahmedabad, Gujarat, the institute is known for its case-based pedagogy, world-class faculty, and exceptional placement records. IIMA offers MBA, PGPX, and doctoral programs.', '[\"NIRF Rank 1 (Management)\",\"NAAC A++ Accredited\",\"102 Acre Campus\",\"Established 1961\",\"120+ Faculty Members\"]', '[\"NAAC A++\",\"NBA Accredited\",\"UGC Recognized\"]', '[{\"body\":\"NIRF\",\"year\":2025,\"category\":\"Management\",\"rank\":1,\"score\":95.1}]', NULL),
('3491a7af-feaa-494d-b7ee-8725cd943d6a', 'col-aiims-0011', 'AIIMS Delhi, established in 1956, is the premier medical institution in India and a hospital of national importance. Located in New Delhi, AIIMS is known for its world-class medical education, cutting-edge research, and affordable healthcare. It offers MBBS, MD, MS, and various super-specialty programs.', '[\"NIRF Rank 1 (Medical)\",\"NAAC A++ Accredited\",\"115 Acre Campus\",\"Established 1956\",\"500+ Faculty Members\"]', '[\"NAAC A++\",\"NMC Accredited\",\"UGC Recognized\"]', '[{\"body\":\"NIRF\",\"year\":2025,\"category\":\"Medical\",\"rank\":1,\"score\":96.5}]', NULL),
('37666606-d6b1-4a45-9c3c-5b21bfc6c51f', 'col-iitb-0001', 'IIT Bombay is one of the premier engineering institutions in India, established in 1958 with assistance from UNESCO. Located on a 1200-acre campus in Powai, Mumbai, it is consistently ranked among the top engineering colleges in the country. The institute offers a wide range of undergraduate, postgraduate, and doctoral programs across engineering, science, design, and management disciplines.', '[\"NIRF Rank 3 (Engineering)\",\"NAAC A++ Accredited\",\"1200 Acre Campus\",\"Established 1958\",\"780+ Faculty Members\"]', '[\"NAAC A++\",\"NBA Accredited\",\"UGC Recognized\"]', '[{\"body\":\"NIRF\",\"year\":2025,\"category\":\"Engineering\",\"rank\":3,\"score\":92.5},{\"body\":\"NIRF\",\"year\":2024,\"category\":\"Engineering\",\"rank\":4,\"score\":91.8}]', NULL),
('6efc8b5f-84d2-4b14-aec6-ffe680c6a29e', 'col-iitkgp-0005', 'IIT Kharagpur, established in 1951, is the oldest and largest IIT in India. Spread across a sprawling 2100-acre campus, it offers the widest range of academic programs among all IITs. The institute has been a pioneer in engineering education and research in India.', '[\"NIRF Rank 5 (Engineering)\",\"NAAC A++ Accredited\",\"2100 Acre Campus\",\"Established 1951 (Oldest IIT)\",\"850+ Faculty Members\"]', '[\"NAAC A++\",\"NBA Accredited\",\"UGC Recognized\"]', '[{\"body\":\"NIRF\",\"year\":2025,\"category\":\"Engineering\",\"rank\":5,\"score\":90.9},{\"body\":\"NIRF\",\"year\":2024,\"category\":\"Engineering\",\"rank\":6,\"score\":90.2}]', NULL),
('751c6be9-de05-4334-95cb-d95e37b31837', 'col-iitd-0002', 'IIT Delhi, established in 1963, is one of the most prestigious engineering institutions in India. Located in Hauz Khas, New Delhi, the institute is known for its academic excellence, cutting-edge research, and strong industry connections. It has consistently maintained a top position in national and international rankings.', '[\"NIRF Rank 2 (Engineering)\",\"NAAC A++ Accredited\",\"320 Acre Campus\",\"Established 1963\",\"720+ Faculty Members\"]', '[\"NAAC A++\",\"NBA Accredited\",\"UGC Recognized\"]', '[{\"body\":\"NIRF\",\"year\":2025,\"category\":\"Engineering\",\"rank\":2,\"score\":93.1},{\"body\":\"NIRF\",\"year\":2024,\"category\":\"Engineering\",\"rank\":2,\"score\":92.7}]', NULL),
('7aa8449e-f044-421a-bb02-125b65073070', 'col-iitm-0003', 'IIT Madras, established in 1959, is consistently ranked as the number one engineering institution in India. Located on a 630-acre campus in Adyar, Chennai, the institute is renowned for its world-class faculty, research output, and strong alumni network. It offers programs across engineering, science, humanities, and management.', '[\"NIRF Rank 1 (Engineering)\",\"NAAC A++ Accredited\",\"630 Acre Campus\",\"Established 1959\",\"800+ Faculty Members\"]', '[\"NAAC A++\",\"NBA Accredited\",\"UGC Recognized\"]', '[{\"body\":\"NIRF\",\"year\":2025,\"category\":\"Engineering\",\"rank\":1,\"score\":94.2},{\"body\":\"NIRF\",\"year\":2024,\"category\":\"Engineering\",\"rank\":1,\"score\":93.8}]', NULL),
('aa243149-9240-4d81-849c-c49172fee1e8', 'col-iimb-0010', 'IIM Bangalore, established in 1973, is the second-ranked management institution in India. Located in the IT hub of Bangalore, the institute is known for its research focus, innovative pedagogy, and strong industry interface. IIMB offers PGP, EPGP, and executive education programs.', '[\"NIRF Rank 2 (Management)\",\"NAAC A++ Accredited\",\"100 Acre Campus\",\"Established 1973\",\"110+ Faculty Members\"]', '[\"NAAC A++\",\"NBA Accredited\",\"UGC Recognized\"]', '[{\"body\":\"NIRF\",\"year\":2025,\"category\":\"Management\",\"rank\":2,\"score\":94.3}]', NULL),
('ab02f9e9-3e9e-439b-b26c-1b00e6a3fe16', 'col-iitk-0004', 'IIT Kanpur, established in 1960, is known for its strong emphasis on research and innovation. Spread across 1055 acres in Kalyanpur, Kanpur, the institute has a rich legacy of producing top engineers and scientists. It offers programs in engineering, sciences, design, and management.', '[\"NIRF Rank 4 (Engineering)\",\"NAAC A++ Accredited\",\"1055 Acre Campus\",\"Established 1960\",\"650+ Faculty Members\"]', '[\"NAAC A++\",\"NBA Accredited\",\"UGC Recognized\"]', '[{\"body\":\"NIRF\",\"year\":2025,\"category\":\"Engineering\",\"rank\":4,\"score\":91.3},{\"body\":\"NIRF\",\"year\":2024,\"category\":\"Engineering\",\"rank\":5,\"score\":90.6}]', NULL),
('b1e9770f-981d-4f14-8217-8d9a4516c6e3', 'col-nitt-0006', 'NIT Tiruchirappalli, established in 1964, is one of the premier National Institutes of Technology in India. Located in Tiruchirappalli, Tamil Nadu, the institute is known for its excellent academic programs and strong placement records. It offers undergraduate, postgraduate, and doctoral programs in engineering and technology.', '[\"NIRF Rank 9 (Engineering)\",\"NAAC A++ Accredited\",\"325 Acre Campus\",\"Established 1964\",\"480+ Faculty Members\"]', '[\"NAAC A++\",\"NBA Accredited\",\"UGC Recognized\"]', '[{\"body\":\"NIRF\",\"year\":2025,\"category\":\"Engineering\",\"rank\":9,\"score\":85.3}]', NULL),
('bff0eed8-868f-4575-a51c-1937f316c143', 'col-bits-0008', 'BITS Pilani, established in 1964, is a premier private deemed university known for its innovative practice school system and strong industry connections. Located in Pilani, Rajasthan, the institute offers integrated first-degree, higher degree, and doctoral programs. BITS is known for its rigorous academic curriculum and excellent placement record.', '[\"NIRF Rank 22 (Engineering)\",\"NAAC A++ Accredited\",\"328 Acre Campus\",\"Established 1964\",\"Deemed University Status\"]', '[\"NAAC A++\",\"NBA Accredited\",\"UGC Recognized\"]', '[{\"body\":\"NIRF\",\"year\":2025,\"category\":\"Engineering\",\"rank\":22,\"score\":78.4}]', NULL),
('cc94d5a7-5958-4a41-a192-f3fc228f2b0a', 'col-nitk-0007', 'NIT Surathkal, established in 1960, is a premier engineering institution located on the scenic coast of Karnataka. Formerly known as Karnataka Regional Engineering College, it is known for its excellent academic environment and strong placement record. The institute offers programs in engineering, science, and management.', '[\"NIRF Rank 10 (Engineering)\",\"NAAC A++ Accredited\",\"295 Acre Campus\",\"Established 1960\",\"420+ Faculty Members\"]', '[\"NAAC A++\",\"NBA Accredited\",\"UGC Recognized\"]', '[{\"body\":\"NIRF\",\"year\":2025,\"category\":\"Engineering\",\"rank\":10,\"score\":84.7}]', NULL),
('f4264d9f-3d86-46f2-86b0-968c906ea869', 'col-nlsiu-0012', 'NLSIU Bangalore, established in 1986, is India\'s premier law university and consistently ranked number one in legal education. Located in Nagarbhavi, Bangalore, NLSIU is known for its rigorous academic programs, moot court competitions, and strong placement record in top law firms. It offers BA LLB, LLM, and PhD programs.', '[\"NIRF Rank 1 (Law)\",\"NAAC A++ Accredited\",\"25 Acre Campus\",\"Established 1986\",\"85+ Faculty Members\"]', '[\"NAAC A++\",\"BCI Accredited\",\"UGC Recognized\"]', '[{\"body\":\"NIRF\",\"year\":2025,\"category\":\"Law\",\"rank\":1,\"score\":93.7}]', NULL),
('f834df72-8844-4776-9e48-8ae263b59fed', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', '<section class=\"college-section\" style=\"margin: 0px 0px 36px; font-family: &quot;Space Grotesk&quot;, sans-serif; background-color: rgb(255, 255, 255);\"><p class=\"college-prose\" style=\"color: rgba(15, 23, 42, 0.65); line-height: 1.8; font-size: 0.93rem;\">IIM Ahmedabad, established in 1961, is consistently ranked as the number one management institution in India. Located in Ahmedabad, Gujarat, the institute is known for its case-based pedagogy, world-class faculty, and exceptional placement records. IIMA offers MBA, PGPX, and doctoral programs.</p></section>', '[\"h fujfdjnsdwqhjsnkmhsudbHUISBCKASUIDBVB CJSAHZOIXJ ND CJSD  SDN\"]', '[\"VNMB HJEIHCJB DANBWDJB N JBKJbM jksbvdjbudsbmsdhbujfkj j chbsfj cmashcvdc\"]', '{\"NIRF\":\"12\",\"NAAC\":\"B\"}', '[\"hvbefvbrjh v\",\"evchrbvc n dbc\",\"idvhc j hveurwd ncn\"]');

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
('', 'col-iitg-0014', 'B.Tech Computer Science', 'UG', 4, 800000.00, NULL, 200000.00, 60, NULL, NULL, '10+2 with JEE Advanced', NULL, 0),
('00711ef6-056e-4960-9d3b-1afa12950a0d', 'col-iith-0015', 'PhD Engineering', 'PhD', 5, 500000.00, NULL, 100000.00, 20, NULL, NULL, 'M.Tech with GATE/GRE', NULL, 0),
('061733e9-32b0-420d-a74b-af2ca6a7c2ae', 'col-iitk-0004', 'B.Tech Computer Science and Engineering', 'UG', 4, 800000.00, NULL, 200000.00, 140, NULL, '[\"Artificial Intelligence\",\"Computer Systems\",\"Software Engineering\"]', 'JEE Advanced qualified, Class XII (75% aggregate)', NULL, 0),
('0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'col-aiims-0011', 'MBBS Bachelor of Medicine and Surgery', 'UG', 6, 8250.00, NULL, 1500.00, 125, NULL, '[\"General Medicine\",\"Surgery\"]', 'NEET UG qualified, Class XII (50% in PCB)', NULL, 0),
('0b05dbef-7c03-4c69-a5dd-7c43a286adea', 'col-nlsiu-0012', 'PhD Law', 'PhD', 5, 250000.00, NULL, 50000.00, 30, NULL, '[\"Constitutional Law\",\"Criminal Justice\",\"International Law\"]', 'NET/CLAT PhD qualified, LLM degree with 55% aggregate', NULL, 0),
('123f2a50-6400-4e2b-b162-e05ca620db84', 'col-iima-0009', 'MBA Post Graduate Programme in Management', 'PG', 2, 4600000.00, NULL, 2300000.00, 395, NULL, '[\"Marketing\",\"Finance\",\"Operations\",\"Strategy\"]', 'CAT qualified, Bachelor degree with 50% aggregate', NULL, 0),
('1782400a-cc00-4100-a175-f88ff7debf08', 'col-iitkgp-0005', 'B.Tech Computer Science and Engineering', 'UG', 4, 800000.00, NULL, 200000.00, 180, NULL, '[\"Artificial Intelligence\",\"Data Science\",\"Cloud Computing\"]', 'JEE Advanced qualified, Class XII (75% aggregate)', NULL, 0),
('2219d11a-fbe0-4375-bad9-a3a74b745b04', 'col-aiims-0011', 'MS Master of Surgery', 'PG', 3, 15000.00, NULL, 5000.00, 100, NULL, '[\"General Surgery\",\"Orthopedics\",\"Ophthalmology\",\"ENT\"]', 'NEET PG qualified, MBBS degree', NULL, 0),
('27ac76c1-12f1-4688-a3d9-d33cc19d7725', 'col-nitt-0006', 'B.Tech Electrical and Electronics Engineering', 'UG', 4, 480000.00, NULL, 120000.00, 100, NULL, '[\"Power Systems\",\"Electronics\",\"Control Systems\"]', 'JEE Main qualified, Class XII (75% aggregate)', NULL, 0),
('2a1f5cba-9a08-4896-b4b1-7cc93d1395e5', 'col-iitk-0004', 'B.Tech Aerospace Engineering', 'UG', 4, 800000.00, NULL, 200000.00, 60, NULL, '[\"Aerodynamics\",\"Propulsion\",\"Flight Mechanics\"]', 'JEE Advanced qualified, Class XII (75% aggregate)', NULL, 0),
('3147b06b-92d8-4c37-a98e-998095ce7038', 'col-nlsiu-0012', 'LLM Master of Laws', 'PG', 1, 200000.00, NULL, 200000.00, 80, NULL, '[\"Human Rights Law\",\"Business Law\",\"Intellectual Property Law\"]', 'CLAT LLM qualified, LLB degree', NULL, 0),
('31e3ed80-459e-484e-8c59-b90790291b01', 'col-iitr-0013', 'PhD Engineering', 'PhD', 5, 500000.00, NULL, 100000.00, 20, NULL, NULL, 'M.Tech with GATE/GRE', NULL, 0),
('32892c63-27d5-4fa4-a4f3-053bbb1f57bd', 'col-iitm-0003', 'PhD Engineering', 'PhD', 5, 250000.00, NULL, 50000.00, 220, NULL, '[\"All Engineering Disciplines\"]', 'GATE/NET qualified, Master degree with 60% aggregate', NULL, 0),
('34dd9fe6-5897-421d-b14a-df8f5337a93c', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'gcvhdhjcf', 'PG', 2, 6999.00, 3000.00, 50000.00, 45, NULL, '[\"cbjcxb hjdc\"]', 'vjkdvc dbinxzgv', 68766.00, 1),
('3ce33ffb-b0fd-47db-82d2-2ff826554530', 'col-iitd-0002', 'B.Tech Mechanical Engineering', 'UG', 4, 800000.00, NULL, 200000.00, 110, NULL, '[\"Thermal Engineering\",\"Manufacturing\",\"Robotics\"]', 'JEE Advanced qualified, Class XII (75% aggregate)', NULL, 0),
('3dc22518-64c4-4f37-acce-be3bb8b3f539', 'col-iitm-0003', 'B.Tech Electrical Engineering', 'UG', 4, 800000.00, NULL, 200000.00, 120, NULL, '[\"Embedded Systems\",\"Signal Processing\",\"Power Electronics\"]', 'JEE Advanced qualified, Class XII (75% aggregate)', NULL, 0),
('41d84c2c-416f-4dd2-bbd2-f126e195ee82', 'col-iitkgp-0005', 'PhD Engineering', 'PhD', 5, 250000.00, NULL, 50000.00, 250, NULL, '[\"All Engineering Disciplines\"]', 'GATE/NET qualified, Master degree with 60% aggregate', NULL, 0),
('467525ed-e340-46fc-ac4f-10c08bcb33d5', 'col-nlsiu-0012', 'BA LLB Honours', 'UG', 5, 1250000.00, NULL, 250000.00, 120, NULL, '[\"Constitutional Law\",\"Criminal Law\",\"Corporate Law\",\"International Law\"]', 'CLAT qualified, Class XII (45% aggregate)', NULL, 0),
('48d4bc21-935f-4321-af37-7d7a60c2ca7b', 'col-nitk-0007', 'B.Tech Computer Science and Engineering', 'UG', 4, 480000.00, NULL, 120000.00, 110, NULL, '[\"Artificial Intelligence\",\"Cybersecurity\",\"Software Engineering\"]', 'JEE Main qualified, Class XII (75% aggregate)', NULL, 0),
('4c7d30de-c87b-400c-95b8-7b8e59cf83b2', 'col-iitd-0002', 'PhD Engineering', 'PhD', 5, 250000.00, NULL, 50000.00, 180, NULL, '[\"All Engineering Disciplines\"]', 'GATE/NET qualified, Master degree with 60% aggregate', NULL, 0),
('4dd4f2f8-f7af-4946-91e5-341bec1ce65e', 'col-iimb-0010', 'MBA Post Graduate Programme in Management', 'PG', 2, 4600000.00, NULL, 2300000.00, 520, NULL, '[\"Finance\",\"Marketing\",\"Human Resources\",\"Business Analytics\"]', 'CAT qualified, Bachelor degree with 50% aggregate', NULL, 0),
('4e162fa5-112c-4107-8c57-c9eaf9152535', 'col-nitt-0006', 'M.Tech Structural Engineering', 'PG', 2, 240000.00, NULL, 120000.00, 40, NULL, '[\"Structural Dynamics\",\"Earthquake Engineering\",\"Concrete Technology\"]', 'GATE qualified, B.Tech in CE or equivalent', NULL, 0),
('512d88d3-0f21-420b-a5ce-8e371db8f0a0', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'gcvhdhjcf', 'PG', 2, 6999.00, 3000.00, 50000.00, 45, NULL, '[\"cbjcxb hjdc\"]', 'vjkdvc dbinxzgv', 68766.00, 1),
('52113b24-637e-4df4-b70d-3e371d4de904', 'col-aiims-0011', 'PhD Medical Sciences', 'PhD', 5, 50000.00, NULL, 10000.00, 80, NULL, '[\"Biochemistry\",\"Pharmacology\",\"Pathology\",\"Microbiology\"]', 'AIIMS PhD entrance qualified, MD/MS degree', NULL, 0),
('54dd6dd0-6419-4f0a-9cbe-58330f60c570', 'col-bits-0008', 'B.E. Electrical and Electronics Engineering', 'UG', 4, 1400000.00, NULL, 350000.00, 100, NULL, '[\"Power Electronics\",\"Control Systems\",\"Robotics\"]', 'BITSAT qualified, Class XII (75% in PCM)', NULL, 0),
('560153bd-b36e-43f7-b08d-828bf927e548', 'col-iitd-0002', 'M.Tech Artificial Intelligence', 'PG', 2, 400000.00, NULL, 200000.00, 50, NULL, '[\"Machine Learning\",\"Deep Learning\",\"Natural Language Processing\"]', 'GATE qualified, B.Tech in CS or equivalent', NULL, 0),
('5e609058-012e-405b-8f99-a0704ffae969', 'col-iima-0009', 'PhD Management', 'PhD', 5, 500000.00, NULL, 100000.00, 50, NULL, '[\"Organizational Behaviour\",\"Finance\",\"Marketing\",\"Operations\"]', 'CAT/GMAT qualified, Master degree with 55% aggregate', NULL, 0),
('7512dee5-e13b-4573-86ee-b93eb4acb7fd', 'col-iitk-0004', 'M.Tech Computer Science', 'PG', 2, 400000.00, NULL, 200000.00, 50, NULL, '[\"Algorithms\",\"Distributed Systems\",\"Machine Learning\"]', 'GATE qualified, B.Tech in CS or equivalent', NULL, 0),
('7a34866b-cae5-422a-bd09-17bc11173ca6', 'col-iitr-0013', 'B.Tech Electrical Engineering', 'UG', 4, 800000.00, NULL, 200000.00, 60, NULL, NULL, '10+2 with JEE Advanced', NULL, 0),
('7dfc0eed-fa03-4618-aed8-48f39f75c6c8', 'col-iitkgp-0005', 'B.Tech Civil Engineering', 'UG', 4, 800000.00, NULL, 200000.00, 100, NULL, '[\"Structural Engineering\",\"Transportation\",\"Environmental Engineering\"]', 'JEE Advanced qualified, Class XII (75% aggregate)', NULL, 0),
('874ce98e-7434-45fc-98cb-f0d8e29a079d', 'col-nitt-0006', 'B.Tech Computer Science and Engineering', 'UG', 4, 480000.00, NULL, 120000.00, 120, NULL, '[\"Computer Networks\",\"Data Science\",\"Information Security\"]', 'JEE Main qualified, Class XII (75% aggregate)', NULL, 0),
('8c866980-ae6b-4ec3-9710-aa55d8983639', 'col-iitb-0001', 'B.Tech Computer Science and Engineering', 'UG', 4, 800000.00, NULL, 200000.00, 160, NULL, '[\"Data Science\",\"Artificial Intelligence\",\"Cybersecurity\"]', 'JEE Advanced qualified, Class XII (75% aggregate)', NULL, 0),
('976faa51-7487-4976-a9dc-9d55cdfbc07c', 'col-iitm-0003', 'B.Tech Computer Science and Engineering', 'UG', 4, 800000.00, NULL, 200000.00, 170, NULL, '[\"AI and Machine Learning\",\"Data Science\",\"Cybersecurity\"]', 'JEE Advanced qualified, Class XII (75% aggregate)', NULL, 0),
('9d336efe-ac6f-4e45-a54f-eb13ff151e06', 'col-iitr-0013', 'M.Tech Computer Science', 'PG', 2, 300000.00, NULL, 150000.00, 30, NULL, NULL, 'B.Tech with GATE', NULL, 0),
('9ed15a3d-520d-41a0-af6a-6f2020991524', 'col-iitb-0001', 'B.Tech Electrical Engineering', 'UG', 4, 800000.00, NULL, 200000.00, 120, NULL, '[\"Power Systems\",\"Control Systems\",\"VLSI Design\"]', 'JEE Advanced qualified, Class XII (75% aggregate)', NULL, 0),
('b32dad8a-0507-4301-9d32-1594861e4c09', 'col-iith-0015', 'M.Tech Computer Science', 'PG', 2, 300000.00, NULL, 150000.00, 30, NULL, NULL, 'B.Tech with GATE', NULL, 0),
('b8c5be46-1c87-4821-9ae1-1b6a976d22d9', 'col-aiims-0011', 'MD Doctor of Medicine', 'PG', 3, 15000.00, NULL, 5000.00, 200, NULL, '[\"Internal Medicine\",\"Pediatrics\",\"Radiology\",\"Anesthesiology\"]', 'NEET PG qualified, MBBS degree', NULL, 0),
('b99f633c-62cc-4cf2-bf7a-fe306385bf97', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'gcvhdhjcf', 'PG', 2, 6999.00, 3000.00, 50000.00, 45, NULL, '[\"cbjcxb hjdc\"]', 'vjkdvc dbinxzgv', 68766.00, 1),
('b9b8dabf-eb7b-4e95-bdd4-2bfd79ec0908', 'col-bits-0008', 'PhD Engineering', 'PhD', 5, 500000.00, NULL, 100000.00, 80, NULL, '[\"All Engineering Disciplines\"]', 'GATE/NET qualified, Master degree with 60% aggregate', NULL, 0),
('b9ff2450-4b6d-4c24-9c66-00105595e0cd', 'col-iimb-0010', 'Executive PGPEM', 'PG', 2, 5000000.00, NULL, 2500000.00, 120, NULL, '[\"Leadership\",\"Strategy\",\"Innovation Management\"]', 'GMAT/GRE qualified, 5+ years work experience', NULL, 0),
('c0b38566-db71-4958-9aab-276572242015', 'col-iith-0015', 'B.Tech Computer Science', 'UG', 4, 800000.00, NULL, 200000.00, 60, NULL, NULL, '10+2 with JEE Advanced', NULL, 0),
('ca3b7d60-7884-4a3a-a8cf-1eee834cdfb2', 'col-iimb-0010', 'PhD Management', 'PhD', 5, 500000.00, NULL, 100000.00, 45, NULL, '[\"Economics\",\"Finance\",\"Information Systems\",\"Operations\"]', 'CAT/GMAT qualified, Master degree with 55% aggregate', NULL, 0),
('d39a99e4-d70f-4afa-8ae6-f971d2bc44e1', 'col-iith-0015', 'B.Tech Electrical Engineering', 'UG', 4, 800000.00, NULL, 200000.00, 60, NULL, NULL, '10+2 with JEE Advanced', NULL, 0),
('d48ba0ba-3b5c-44f8-8dcc-c75018f2faba', 'col-bits-0008', 'B.E. Computer Science', 'UG', 4, 1400000.00, NULL, 350000.00, 200, NULL, '[\"AI and Data Science\",\"Software Development\",\"Cybersecurity\"]', 'BITSAT qualified, Class XII (75% in PCM)', NULL, 0),
('d887734e-2afc-4b12-98c2-fa73390fe4a2', 'col-iitr-0013', 'B.Tech Computer Science', 'UG', 4, 800000.00, NULL, 200000.00, 60, NULL, NULL, '10+2 with JEE Advanced', NULL, 0),
('dbcd9f2c-328d-43f8-8e70-a08f7c89b2fd', 'col-iitm-0003', 'M.Tech Data Science', 'PG', 2, 400000.00, NULL, 200000.00, 55, NULL, '[\"Big Data Analytics\",\"Machine Learning\",\"Statistical Learning\"]', 'GATE qualified, B.Tech in CS or equivalent', NULL, 0),
('df806c01-4df5-4a64-8233-6e6cc26d06b3', 'col-nitk-0007', 'B.Tech Electronics and Communication Engineering', 'UG', 4, 480000.00, NULL, 120000.00, 100, NULL, '[\"Signal Processing\",\"VLSI Design\",\"Embedded Systems\"]', 'JEE Main qualified, Class XII (75% aggregate)', NULL, 0),
('e30e0785-293e-452f-8530-cc0c0f0cc9a6', 'col-iitk-0004', 'PhD Engineering', 'PhD', 5, 250000.00, NULL, 50000.00, 160, NULL, '[\"All Engineering Disciplines\"]', 'GATE/NET qualified, Master degree with 60% aggregate', NULL, 0),
('e7a58a29-cdf9-4504-9861-3f541979a015', 'col-iima-0009', 'PGPX Executive MBA', 'PG', 1, 2800000.00, NULL, 2800000.00, 140, NULL, '[\"Leadership\",\"General Management\",\"Digital Transformation\"]', 'GMAT/GRE qualified, 5+ years work experience', NULL, 0),
('ed337996-b383-47c3-a2a5-76e74ac20db7', 'col-iitkgp-0005', 'M.Tech Mechanical Engineering', 'PG', 2, 400000.00, NULL, 200000.00, 60, NULL, '[\"Thermal Science\",\"Design\",\"Manufacturing\"]', 'GATE qualified, B.Tech in ME or equivalent', NULL, 0),
('f246c795-5f78-4750-b358-d62e715364e7', 'col-nitk-0007', 'M.Tech Computer Science', 'PG', 2, 240000.00, NULL, 120000.00, 35, NULL, '[\"Machine Learning\",\"Cloud Computing\",\"Big Data\"]', 'GATE qualified, B.Tech in CS or equivalent', NULL, 0),
('f5ded9ec-fc6c-4983-a024-97d0dd9d320a', 'col-bits-0008', 'M.Tech Software Systems', 'PG', 2, 700000.00, NULL, 350000.00, 60, NULL, '[\"Software Engineering\",\"DevOps\",\"Cloud Architecture\"]', 'GATE qualified, B.E. in CS or equivalent', NULL, 0),
('f6382a32-9440-4c38-a66a-29da283dae1b', 'col-iitb-0001', 'M.Tech Computer Science and Engineering', 'PG', 2, 400000.00, NULL, 200000.00, 60, NULL, '[\"Machine Learning\",\"Software Systems\",\"Information Security\"]', 'GATE qualified, B.Tech in CS or equivalent', NULL, 0),
('f7433a19-233f-4315-8ff8-6d8d0a05c19a', 'col-iitb-0001', 'PhD Engineering', 'PhD', 5, 250000.00, NULL, 50000.00, 200, NULL, '[\"All Engineering Disciplines\"]', 'GATE/NET qualified, Master degree with 60% aggregate', NULL, 0),
('f7f070e2-4185-48e2-b15d-7ac08a1a8181', 'col-iitd-0002', 'B.Tech Computer Science and Engineering', 'UG', 4, 800000.00, NULL, 200000.00, 150, NULL, '[\"Artificial Intelligence\",\"Data Engineering\",\"Cybersecurity\"]', 'JEE Advanced qualified, Class XII (75% aggregate)', NULL, 0);

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
('00dcce0b-eba9-3b90-0ae2-6b71a78efe84', 'col-iitm-0003', 'ex-jee-adv-2026', '976faa51-7487-4976-a9dc-9d55cdfbc07c', 'EWS', '2023', 40, 375, 1, 'AI', NULL),
('0181e24d-b6f0-657c-3edd-757d1bf36d1d', 'col-nlsiu-0012', 'ex-clat-2026', NULL, 'ST', '2022', 500, 3400, 1, 'AI', NULL),
('01dfc353-4c73-b664-475f-6318659cb761', 'col-iima-0009', 'ex-cat-2026', '123f2a50-6400-4e2b-b162-e05ca620db84', 'OBC', '2024', 95, 97, 1, 'AI', NULL),
('0591cab2-015f-7a8c-35d4-8f2da4645f82', 'col-nlsiu-0012', 'ex-clat-2026', NULL, 'SC', '2022', 200, 1700, 1, 'AI', NULL),
('06287675-66b3-1aa0-9b08-7783935e0fea', 'col-aiims-0011', 'ex-neet-pg-2026', 'b8c5be46-1c87-4821-9ae1-1b6a976d22d9', 'SC', '2024', 200, 800, 1, 'AI', NULL),
('06984f1b-a957-a408-4a79-57a8b45b2e42', 'col-aiims-0011', 'ex-neet-pg-2026', 'b8c5be46-1c87-4821-9ae1-1b6a976d22d9', 'SC', '2022', 200, 880, 1, 'AI', NULL),
('06d34185-868a-552e-d41b-44623c41817e', 'col-nitk-0007', 'ex-jee-main-2026', '48d4bc21-935f-4321-af37-7d7a60c2ca7b', 'OBC', '2024', 4000, 12000, 1, 'AI', NULL),
('073ff681-10f7-fc58-0068-c1b4c44bd5f8', 'col-iitd-0002', 'ex-jee-adv-2026', 'f7f070e2-4185-48e2-b15d-7ac08a1a8181', 'SC', '2022', 100, 1000, 1, 'AI', NULL),
('07e83648-0918-5406-f20d-d5a217109eb9', 'col-iitk-0004', 'ex-jee-adv-2026', '061733e9-32b0-420d-a74b-af2ca6a7c2ae', 'SC', '2023', 200, 1580, 1, 'AI', NULL),
('080bb5dc-c363-2f70-bcda-2ea40621540d', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'General', '2022', 40, 87, 2, 'AI', NULL),
('09120aed-573c-9310-f6c0-19342eecd4b1', 'col-aiims-0011', 'ex-neet-pg-2026', '2219d11a-fbe0-4375-bad9-a3a74b745b04', 'OBC', '2023', 80, 425, 1, 'AI', NULL),
('0b2467d6-7542-8225-7b92-34ed413c5db3', 'col-aiims-0011', 'ex-neet-pg-2026', 'b8c5be46-1c87-4821-9ae1-1b6a976d22d9', 'General', '2023', 1, 110, 1, 'AI', NULL),
('0da91d9b-77b9-5937-b158-9615c02ef967', 'col-iitd-0002', 'ex-jee-adv-2026', 'f7f070e2-4185-48e2-b15d-7ac08a1a8181', 'SC', '2024', 100, 900, 1, 'AI', NULL),
('0ec7a8c7-056b-da4b-23bb-9b9c0c835fb1', 'col-iima-0009', 'ex-cat-2026', '123f2a50-6400-4e2b-b162-e05ca620db84', 'ST', '2023', 70, 75, 1, 'AI', NULL),
('0f6fcca1-3144-f60a-5c6a-5ea5a2eb5557', 'col-iitk-0004', 'ex-jee-adv-2026', '061733e9-32b0-420d-a74b-af2ca6a7c2ae', 'General', '2022', 1, 280, 1, 'AI', NULL),
('0f7b1d36-b30d-f6ee-2786-67ed3db601dc', 'col-iitb-0001', 'ex-jee-adv-2026', '9ed15a3d-520d-41a0-af6a-6f2020991524', 'EWS', '2024', 300, 900, 1, 'AI', NULL),
('131d96d7-5bb6-eceb-0a4e-bee9c4fb9cf3', 'col-bits-0008', 'ex-bitstat-2026', '54dd6dd0-6419-4f0a-9cbe-58330f60c570', 'OBC', '2023', 220, 288, 1, 'AI', NULL),
('1374cec0-34b8-6ada-29ae-a2a9a7412539', 'col-nitt-0006', 'ex-jee-main-2026', '27ac76c1-12f1-4688-a3d9-d33cc19d7725', 'General', '2024', 3000, 10000, 1, 'AI', NULL),
('15ac115e-5fbe-1e54-f01f-ee942c0d9853', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'ST', '2022', 200, 1100, 1, 'AI', NULL),
('1780e5bb-6be5-c74d-8db1-7a09b1b8831f', 'col-iitb-0001', 'ex-jee-adv-2026', '9ed15a3d-520d-41a0-af6a-6f2020991524', 'SC', '2024', 800, 2500, 1, 'AI', NULL),
('1d7d86a1-a683-504f-c16f-c59051f5d3ca', 'col-iitd-0002', 'ex-jee-adv-2026', 'f7f070e2-4185-48e2-b15d-7ac08a1a8181', 'EWS', '2023', 30, 320, 1, 'AI', NULL),
('1d81d833-9ed2-0063-3423-7e28097d1308', 'col-iitkgp-0005', 'ex-jee-adv-2026', '7dfc0eed-fa03-4618-aed8-48f39f75c6c8', 'OBC', '2024', 1500, 5000, 1, 'AI', NULL),
('1d9e54ba-e06f-8ba7-6a2a-e15f02ef0114', 'col-iitd-0002', 'ex-jee-adv-2026', NULL, 'OBC', '2024', 500, 1400, 1, 'AI', NULL),
('1ff96a8b-1479-717d-c1a4-91307557f239', 'col-iitkgp-0005', 'ex-jee-adv-2026', '7dfc0eed-fa03-4618-aed8-48f39f75c6c8', 'General', '2024', 800, 3000, 1, 'AI', NULL),
('20a36750-735e-6e6c-dd54-0de48076651b', 'col-iimb-0010', 'ex-cat-2026', '4dd4f2f8-f7af-4946-91e5-341bec1ce65e', 'OBC', '2022', 94, 97, 1, 'AI', NULL),
('20a9cd2e-cfa3-7e7c-0762-2ff0128479be', 'col-bits-0008', 'ex-bitstat-2026', '54dd6dd0-6419-4f0a-9cbe-58330f60c570', 'OBC', '2024', 220, 290, 1, 'AI', NULL),
('220fa3e0-20f2-aaca-e4f4-4260a4dfff1f', 'col-iitm-0003', 'ex-jee-adv-2026', '976faa51-7487-4976-a9dc-9d55cdfbc07c', 'General', '2022', 1, 174, 1, 'AI', NULL),
('229a51d4-ed2d-0d11-b827-6df46d2b822a', 'col-iitb-0001', 'ex-jee-adv-2026', '9ed15a3d-520d-41a0-af6a-6f2020991524', 'OBC', '2024', 400, 1200, 1, 'AI', NULL),
('23491341-b5b2-93fd-bd21-0ab8b46213b7', 'col-iitd-0002', 'ex-jee-adv-2026', NULL, 'General', '2023', 250, 740, 1, 'AI', NULL),
('235bbe8c-453c-7216-3303-4388817f97ff', 'col-iitd-0002', 'ex-jee-adv-2026', 'f7f070e2-4185-48e2-b15d-7ac08a1a8181', 'ST', '2024', 200, 1800, 1, 'AI', NULL),
('2370e941-bfb1-82e0-0cd0-6d48ba207e15', 'col-nlsiu-0012', 'ex-clat-2026', NULL, 'General', '2024', 1, 100, 1, 'AI', NULL),
('23805b50-24d0-4c55-c38b-334573f46a61', 'col-nitt-0006', 'ex-jee-main-2026', '27ac76c1-12f1-4688-a3d9-d33cc19d7725', 'OBC', '2022', 6000, 19600, 1, 'AI', NULL),
('240b872a-6c90-b2f6-32ec-ffa531839807', 'col-aiims-0011', 'ex-neet-pg-2026', 'b8c5be46-1c87-4821-9ae1-1b6a976d22d9', 'OBC', '2024', 50, 300, 1, 'AI', NULL),
('24b55e6a-c792-433e-1747-77f6a148660d', 'col-nitt-0006', 'ex-jee-main-2026', '874ce98e-7434-45fc-98cb-f0d8e29a079d', 'ST', '2024', 8000, 35000, 1, 'AI', NULL),
('257e1a02-9577-4c1f-1bc7-02f0d2041f57', 'col-iitm-0003', 'ex-jee-adv-2026', '3dc22518-64c4-4f37-acce-be3bb8b3f539', 'OBC', '2024', 600, 1600, 1, 'AI', NULL),
('263ed0ce-4295-9462-372c-5633ac8aeb13', 'col-iima-0009', 'ex-cat-2026', '123f2a50-6400-4e2b-b162-e05ca620db84', 'OBC', '2022', 95, 97, 1, 'AI', NULL),
('26ca3d62-c830-c20b-d959-2c2ba5575d6d', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'General', '2023', 1, 55, 1, 'AI', NULL),
('292b5416-9db8-0206-6be6-3d6c3237b60d', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'OBC', '2023', 30, 108, 1, 'AI', NULL),
('29df0ec3-1bea-e818-1df9-a15a2a195a05', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'EWS', '2023', 20, 86, 1, 'AI', NULL),
('2ab7b54f-4378-16ef-540c-b865d19df3f7', 'col-nitt-0006', 'ex-jee-main-2026', '874ce98e-7434-45fc-98cb-f0d8e29a079d', 'SC', '2022', 5000, 22000, 1, 'AI', NULL),
('2e0e72e9-fa93-7167-90d4-fdc3d49ae703', 'col-nitt-0006', 'ex-jee-main-2026', '874ce98e-7434-45fc-98cb-f0d8e29a079d', 'ST', '2022', 8000, 38000, 1, 'AI', NULL),
('2f947281-90c4-2064-15f4-74c55e0fb231', 'col-iitm-0003', 'ex-jee-adv-2026', '976faa51-7487-4976-a9dc-9d55cdfbc07c', 'SC', '2023', 120, 1050, 1, 'AI', NULL),
('309434a4-aa73-4e5b-364e-075cb87cbe62', 'col-nlsiu-0012', 'ex-clat-2026', NULL, 'OBC', '2022', 50, 340, 1, 'AI', NULL),
('311b1ec1-e969-573c-1f0e-bf36dbbf2385', 'col-bits-0008', 'ex-bitstat-2026', 'd48ba0ba-3b5c-44f8-8dcc-c75018f2faba', 'General', '2022', 280, 346, 1, 'AI', NULL),
('317cdccd-ed25-271a-67dd-900d8598e736', 'col-iitk-0004', 'ex-jee-adv-2026', '061733e9-32b0-420d-a74b-af2ca6a7c2ae', 'EWS', '2024', 50, 450, 1, 'AI', NULL),
('33129653-7e9a-aead-6747-0ca36d25ccaa', 'col-iitb-0001', 'ex-jee-adv-2026', '9ed15a3d-520d-41a0-af6a-6f2020991524', 'SC', '2022', 800, 2700, 1, 'AI', NULL),
('332ea394-da9d-54ca-53bc-179eec76ff78', 'col-nitk-0007', 'ex-jee-main-2026', '48d4bc21-935f-4321-af37-7d7a60c2ca7b', 'SC', '2023', 6000, 23200, 1, 'AI', NULL),
('344a82ee-9f90-5f2a-e02a-a39771e38274', 'col-bits-0008', 'ex-bitstat-2026', '54dd6dd0-6419-4f0a-9cbe-58330f60c570', 'General', '2023', 240, 308, 1, 'AI', NULL),
('363e9f86-d8d5-cd67-7103-896afc7ad535', 'col-iitb-0001', 'ex-jee-adv-2026', '8c866980-ae6b-4ec3-9710-aa55d8983639', 'General', '2022', 1, 130, 1, 'AI', NULL),
('37be5af2-c820-43ec-27fd-17ebbdc62032', 'col-iitd-0002', 'ex-jee-adv-2026', 'f7f070e2-4185-48e2-b15d-7ac08a1a8181', 'SC', '2023', 100, 950, 1, 'AI', NULL),
('3cd2611f-c4f8-96e7-de0f-80f989bccf12', 'col-iitm-0003', 'ex-jee-adv-2026', '3dc22518-64c4-4f37-acce-be3bb8b3f539', 'General', '2024', 300, 800, 1, 'AI', NULL),
('3d18fbb4-a537-4196-7dc1-aafe2757fd41', 'col-iitkgp-0005', 'ex-jee-adv-2026', '1782400a-cc00-4100-a175-f88ff7debf08', 'OBC', '2023', 100, 790, 1, 'AI', NULL),
('3d568c98-339c-fdf3-738f-e4d8f7ef6ab4', 'col-nitt-0006', 'ex-jee-main-2026', '874ce98e-7434-45fc-98cb-f0d8e29a079d', 'OBC', '2024', 3000, 10000, 1, 'AI', NULL),
('3e5191f5-c726-99a5-4e49-6679bf2f9014', 'col-iimb-0010', 'ex-cat-2026', '4dd4f2f8-f7af-4946-91e5-341bec1ce65e', 'EWS', '2022', 88, 93, 1, 'AI', NULL),
('3e84fe73-d017-2fe1-b0fa-26ca6f8ebc95', 'col-iitm-0003', 'ex-jee-adv-2026', '976faa51-7487-4976-a9dc-9d55cdfbc07c', 'SC', '2022', 120, 1100, 1, 'AI', NULL),
('3e993081-e9bb-72bd-0b78-5a5169ae8bb1', 'col-nitt-0006', 'ex-jee-main-2026', '27ac76c1-12f1-4688-a3d9-d33cc19d7725', 'General', '2023', 3000, 10500, 1, 'AI', NULL),
('3f0c5e08-f708-2f4e-6d1c-abc5da8d0c03', 'col-nitt-0006', 'ex-jee-main-2026', '874ce98e-7434-45fc-98cb-f0d8e29a079d', 'OBC', '2022', 3000, 11000, 1, 'AI', NULL),
('3f81df02-c26e-9712-2024-9786a4d33bf3', 'col-aiims-0011', 'ex-neet-pg-2026', 'b8c5be46-1c87-4821-9ae1-1b6a976d22d9', 'OBC', '2023', 50, 320, 1, 'AI', NULL),
('40f00eed-c746-4e29-136d-70ef475bf05d', 'col-iitm-0003', 'ex-jee-adv-2026', '976faa51-7487-4976-a9dc-9d55cdfbc07c', 'General', '2023', 1, 162, 1, 'AI', NULL),
('410a0cc4-ee40-5efb-da06-c5f0ded8fac3', 'col-iitb-0001', 'ex-jee-adv-2026', '8c866980-ae6b-4ec3-9710-aa55d8983639', 'EWS', '2024', 30, 250, 1, 'AI', NULL),
('41d93066-fcd4-091f-f4e8-6c7af6282cae', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'SC', '2024', 100, 500, 1, 'AI', NULL),
('421d1029-904b-9ce6-0c5c-3eaba0a28dfc', 'col-aiims-0011', 'ex-neet-pg-2026', '2219d11a-fbe0-4375-bad9-a3a74b745b04', 'General', '2024', 1, 150, 1, 'AI', NULL),
('42275440-62bc-7639-8185-2835296bb10b', 'col-bits-0008', 'ex-bitstat-2026', '54dd6dd0-6419-4f0a-9cbe-58330f60c570', 'OBC', '2022', 220, 286, 1, 'AI', NULL),
('43d470d8-1093-097d-b0dc-95f350b330d8', 'col-nitt-0006', 'ex-jee-main-2026', '874ce98e-7434-45fc-98cb-f0d8e29a079d', 'ST', '2023', 8000, 36500, 1, 'AI', NULL),
('4722c7a7-d0be-ef87-5940-1aa77f7641e0', 'col-bits-0008', 'ex-bitstat-2026', 'd48ba0ba-3b5c-44f8-8dcc-c75018f2faba', 'General', '2024', 280, 350, 1, 'AI', NULL),
('4802220d-fb5f-5d73-2884-259e125904a2', 'col-nlsiu-0012', 'ex-clat-2026', NULL, 'OBC', '2024', 50, 300, 1, 'AI', NULL),
('4953dce8-e87c-3bab-0ed0-5f56aa75f11d', 'col-iitd-0002', 'ex-jee-adv-2026', NULL, 'General', '2024', 250, 700, 1, 'AI', NULL),
('4c4bb7aa-6e2c-5a3c-8087-2acc32c30b9d', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'EWS', '2024', 20, 80, 1, 'AI', NULL),
('4d771203-f670-5bf0-215d-d5fa84b9b3ef', 'col-iitm-0003', 'ex-jee-adv-2026', '976faa51-7487-4976-a9dc-9d55cdfbc07c', 'SC', '2024', 120, 1000, 1, 'AI', NULL),
('5242b787-ff1c-d289-9bba-f3196645cf1b', 'col-iitm-0003', 'ex-jee-adv-2026', '976faa51-7487-4976-a9dc-9d55cdfbc07c', 'EWS', '2024', 40, 350, 1, 'AI', NULL),
('525c3d84-0300-d6ae-7882-4d1903274f3a', 'col-aiims-0011', 'ex-neet-pg-2026', 'b8c5be46-1c87-4821-9ae1-1b6a976d22d9', 'SC', '2023', 200, 840, 1, 'AI', NULL),
('54241202-1f2a-283b-f1de-52f2e0744f24', 'col-nitt-0006', 'ex-jee-main-2026', '874ce98e-7434-45fc-98cb-f0d8e29a079d', 'General', '2022', 1500, 5600, 1, 'AI', NULL),
('549c5ca0-7f92-66f8-d82f-60ddb011c1bf', 'col-iitk-0004', 'ex-jee-adv-2026', '061733e9-32b0-420d-a74b-af2ca6a7c2ae', 'OBC', '2024', 80, 600, 1, 'AI', NULL),
('56ce571d-3d93-39cd-9b46-af8a8c1cb944', 'col-iitkgp-0005', 'ex-jee-adv-2026', '1782400a-cc00-4100-a175-f88ff7debf08', 'EWS', '2024', 60, 550, 1, 'AI', NULL),
('56f8ab6c-f53f-cf5c-e7fa-f92878e10f84', 'col-nitk-0007', 'ex-jee-main-2026', '48d4bc21-935f-4321-af37-7d7a60c2ca7b', 'General', '2023', 1800, 6350, 1, 'AI', NULL),
('590bb033-557e-e29e-71d4-e14edb70b068', 'col-nlsiu-0012', 'ex-clat-2026', NULL, 'General', '2022', 1, 120, 1, 'AI', NULL),
('5bee27d0-845d-43a0-dedb-d2f56def59d2', 'col-nitt-0006', 'ex-jee-main-2026', '27ac76c1-12f1-4688-a3d9-d33cc19d7725', 'General', '2022', 3000, 11000, 1, 'AI', NULL),
('5d914adb-611f-744d-309d-8c812c0dd244', 'col-iitm-0003', 'ex-jee-adv-2026', '3dc22518-64c4-4f37-acce-be3bb8b3f539', 'OBC', '2022', 600, 1760, 1, 'AI', NULL),
('5ef044c9-c1a3-f437-d68f-6fa591bcb8ef', 'col-iitkgp-0005', 'ex-jee-adv-2026', '1782400a-cc00-4100-a175-f88ff7debf08', 'EWS', '2022', 60, 610, 1, 'AI', NULL),
('611569c9-724b-d8ef-0439-28789aa19e8a', 'col-iima-0009', 'ex-cat-2026', '123f2a50-6400-4e2b-b162-e05ca620db84', 'ST', '2022', 70, 75, 1, 'AI', NULL),
('614433bd-720b-1cfa-50d7-29f246072f02', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'General', '2022', 1, 60, 1, 'AI', NULL),
('679243f3-42c5-afa7-4daf-192538cd8c58', 'col-iitb-0001', 'ex-jee-adv-2026', '8c866980-ae6b-4ec3-9710-aa55d8983639', 'General', '2024', 1, 100, 1, 'AI', NULL),
('68a8e9fe-d31f-8adc-8e85-66335f276dd6', 'col-iima-0009', 'ex-cat-2026', '123f2a50-6400-4e2b-b162-e05ca620db84', 'EWS', '2023', 90, 95, 1, 'AI', NULL),
('6a122533-082b-84fb-f367-8d42e81eb0d6', 'col-iitd-0002', 'ex-jee-adv-2026', 'f7f070e2-4185-48e2-b15d-7ac08a1a8181', 'General', '2022', 1, 144, 1, 'AI', NULL),
('6cb3b48f-671d-0efd-0b0e-2a99dc804314', 'col-iimb-0010', 'ex-cat-2026', '4dd4f2f8-f7af-4946-91e5-341bec1ce65e', 'SC', '2022', 78, 83, 1, 'AI', NULL),
('6d8574d2-c7fa-c25c-b5ee-2972c78f3556', 'col-iitb-0001', 'ex-jee-adv-2026', '8c866980-ae6b-4ec3-9710-aa55d8983639', 'General', '2023', 1, 115, 1, 'AI', NULL),
('6dc51ed4-84b0-b7e7-4dbd-3b8e7fea9675', 'col-iitkgp-0005', 'ex-jee-adv-2026', '1782400a-cc00-4100-a175-f88ff7debf08', 'OBC', '2022', 100, 830, 1, 'AI', NULL),
('6f210543-9344-1f06-fcf6-2dd17311b9ee', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'OBC', '2024', 30, 100, 1, 'AI', NULL),
('6fb341d0-1531-9b25-a050-09ec5eea3e52', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'General', '2024', 1, 50, 1, 'AI', NULL),
('70b61472-3a41-b2d8-4a9b-6b45752bbcbe', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'OBC', '2022', 80, 170, 2, 'AI', NULL),
('70cc1e1a-e33a-9316-0e7b-e4c20ed671b9', 'col-aiims-0011', 'ex-neet-pg-2026', '2219d11a-fbe0-4375-bad9-a3a74b745b04', 'OBC', '2022', 80, 450, 1, 'AI', NULL),
('70d619e4-0394-88f3-abde-dc9d6dffd98e', 'col-bits-0008', 'ex-bitstat-2026', 'd48ba0ba-3b5c-44f8-8dcc-c75018f2faba', 'General', '2023', 280, 348, 1, 'AI', NULL),
('71b1681b-9e51-124e-158b-fdb17bbce75a', 'col-iitd-0002', 'ex-jee-adv-2026', 'f7f070e2-4185-48e2-b15d-7ac08a1a8181', 'EWS', '2024', 30, 300, 1, 'AI', NULL),
('71c3c454-a5ba-3c49-663d-b7ce97922daf', 'col-iitkgp-0005', 'ex-jee-adv-2026', '1782400a-cc00-4100-a175-f88ff7debf08', 'SC', '2023', 250, 2100, 1, 'AI', NULL),
('739622cd-84b9-d6ef-767b-6768f9c1deb9', 'col-iima-0009', 'ex-cat-2026', '123f2a50-6400-4e2b-b162-e05ca620db84', 'General', '2023', 99, 100, 1, 'AI', NULL),
('7522effc-f19d-2277-0bc3-960a4d0d6005', 'col-iitk-0004', 'ex-jee-adv-2026', '061733e9-32b0-420d-a74b-af2ca6a7c2ae', 'EWS', '2023', 50, 480, 1, 'AI', NULL),
('75856fba-db46-1a1c-432b-ab74b4da9c2c', 'col-iitkgp-0005', 'ex-jee-adv-2026', '1782400a-cc00-4100-a175-f88ff7debf08', 'OBC', '2024', 100, 750, 1, 'AI', NULL),
('759f1006-d8de-80f3-ec29-14c71e7a25ad', 'col-iitd-0002', 'ex-jee-adv-2026', 'f7f070e2-4185-48e2-b15d-7ac08a1a8181', 'ST', '2023', 200, 1900, 1, 'AI', NULL),
('767d357e-0660-32a6-ffc2-266340569769', 'col-iimb-0010', 'ex-cat-2026', '4dd4f2f8-f7af-4946-91e5-341bec1ce65e', 'SC', '2023', 78, 83, 1, 'AI', NULL),
('79dec030-d4be-200d-e1e9-312a9b1e6455', 'col-iima-0009', 'ex-cat-2026', '123f2a50-6400-4e2b-b162-e05ca620db84', 'General', '2022', 99, 100, 1, 'AI', NULL),
('79fd1f16-bcbb-3dd4-ac86-b793d602cd18', 'col-iitb-0001', 'ex-jee-adv-2026', '8c866980-ae6b-4ec3-9710-aa55d8983639', 'ST', '2022', 200, 1700, 1, 'AI', NULL),
('7a6b0ad9-83f6-3b47-7147-2e280c4c1ed7', 'col-iitb-0001', 'ex-jee-adv-2026', '9ed15a3d-520d-41a0-af6a-6f2020991524', 'General', '2023', 200, 640, 1, 'AI', NULL),
('7a96613a-5d60-d6c1-d6f9-d93da7d33d43', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'SC', '2023', 100, 530, 1, 'AI', NULL),
('7b36a6c9-d593-dce4-7ccc-0bb240203f53', 'col-iitkgp-0005', 'ex-jee-adv-2026', '7dfc0eed-fa03-4618-aed8-48f39f75c6c8', 'General', '2022', 800, 3300, 1, 'AI', NULL),
('7c86d7e4-1def-2aca-c76b-e629650ea16d', 'col-iimb-0010', 'ex-cat-2026', '4dd4f2f8-f7af-4946-91e5-341bec1ce65e', 'OBC', '2023', 94, 97, 1, 'AI', NULL),
('7e2f876f-8fb1-7d56-055b-f1b37e49a8ae', 'col-iitkgp-0005', 'ex-jee-adv-2026', '1782400a-cc00-4100-a175-f88ff7debf08', 'EWS', '2023', 60, 580, 1, 'AI', NULL),
('7e5cf300-b75b-09b9-35cd-b61ffccd2f11', 'col-nitk-0007', 'ex-jee-main-2026', 'df806c01-4df5-4a64-8233-6e6cc26d06b3', 'General', '2024', 3000, 9000, 1, 'AI', NULL),
('7fa80683-4916-7ab4-0235-b99d774a3b44', 'col-iitb-0001', 'ex-jee-adv-2026', '8c866980-ae6b-4ec3-9710-aa55d8983639', 'OBC', '2023', 50, 375, 1, 'AI', NULL),
('8036fff4-b4fb-bb0d-80ad-cb089c7f49e6', 'col-nlsiu-0012', 'ex-clat-2026', NULL, 'EWS', '2022', 30, 230, 1, 'AI', NULL),
('81e7f83e-f921-717e-7511-7964426b1d68', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'ST', '2023', 200, 1050, 1, 'AI', NULL),
('82cfe613-818b-7b8b-6316-f9693b124cfc', 'col-nlsiu-0012', 'ex-clat-2026', NULL, 'ST', '2023', 500, 3200, 1, 'AI', NULL),
('85537a69-6f85-e1de-5c19-1b7c2e04cfaf', 'col-aiims-0011', 'ex-neet-pg-2026', '2219d11a-fbe0-4375-bad9-a3a74b745b04', 'General', '2023', 1, 162, 1, 'AI', NULL),
('8561f3d8-f1b0-ff18-aa54-338813837abc', 'col-iimb-0010', 'ex-cat-2026', '4dd4f2f8-f7af-4946-91e5-341bec1ce65e', 'EWS', '2024', 88, 93, 1, 'AI', NULL),
('866f5dc8-8a7d-bc65-78c1-48951d85f95c', 'col-nitt-0006', 'ex-jee-main-2026', '874ce98e-7434-45fc-98cb-f0d8e29a079d', 'General', '2024', 1500, 5000, 1, 'AI', NULL),
('87907b87-b793-8bb6-294c-8245f18a3cdf', 'col-nitt-0006', 'ex-jee-main-2026', '874ce98e-7434-45fc-98cb-f0d8e29a079d', 'EWS', '2024', 2500, 8000, 1, 'AI', NULL),
('897da748-2538-f927-87a7-4ea62490a41e', 'col-iitb-0001', 'ex-jee-adv-2026', '8c866980-ae6b-4ec3-9710-aa55d8983639', 'EWS', '2023', 30, 270, 1, 'AI', NULL),
('89ca1893-9087-bccd-4587-7a8a47687d15', 'col-nitt-0006', 'ex-jee-main-2026', '874ce98e-7434-45fc-98cb-f0d8e29a079d', 'SC', '2023', 5000, 21000, 1, 'AI', NULL),
('8a3c62f8-f982-522f-41e9-81d238d493aa', 'col-nitk-0007', 'ex-jee-main-2026', '48d4bc21-935f-4321-af37-7d7a60c2ca7b', 'EWS', '2024', 3000, 9500, 1, 'AI', NULL),
('8b137667-143e-3962-c328-88863590b5d1', 'col-nitk-0007', 'ex-jee-main-2026', '48d4bc21-935f-4321-af37-7d7a60c2ca7b', 'EWS', '2022', 3000, 10500, 1, 'AI', NULL),
('8c1418ca-ed9e-c0c8-3b6a-43836082b654', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'OBC', '2023', 80, 160, 2, 'AI', NULL),
('8df57706-9971-3dfc-0808-99306d672649', 'col-nitk-0007', 'ex-jee-main-2026', '48d4bc21-935f-4321-af37-7d7a60c2ca7b', 'SC', '2024', 6000, 22000, 1, 'AI', NULL),
('8ec62508-316c-892f-8808-f214f527ebc1', 'col-iitk-0004', 'ex-jee-adv-2026', '2a1f5cba-9a08-4896-b4b1-7cc93d1395e5', 'General', '2023', 500, 1580, 1, 'AI', NULL),
('8ee9aeed-b2f0-e9eb-fb53-0da0c7558bf2', 'col-iitd-0002', 'ex-jee-adv-2026', NULL, 'General', '2022', 250, 780, 1, 'AI', NULL),
('8f34e93c-3c76-e266-674c-325020742395', 'col-nlsiu-0012', 'ex-clat-2026', NULL, 'SC', '2024', 200, 1500, 1, 'AI', NULL),
('901b39f9-9467-6af1-e795-0b8aefd6e218', 'col-iitm-0003', 'ex-jee-adv-2026', '3dc22518-64c4-4f37-acce-be3bb8b3f539', 'General', '2023', 300, 850, 1, 'AI', NULL),
('91aabef1-9978-4346-b8f0-dadde21b6750', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'ex-jee-adv-2026', NULL, 'OBC', '2026', 6, 8, NULL, '', NULL),
('91f1c747-fb33-dd28-d72d-998ac24e5366', 'col-iitk-0004', 'ex-jee-adv-2026', '061733e9-32b0-420d-a74b-af2ca6a7c2ae', 'OBC', '2023', 80, 635, 1, 'AI', NULL),
('932741ee-7d9c-bd66-7d3e-5466d972f6a9', 'col-iima-0009', 'ex-cat-2026', '123f2a50-6400-4e2b-b162-e05ca620db84', 'SC', '2023', 80, 85, 1, 'AI', NULL),
('94626def-d219-434d-5b9d-4b3edade5f5b', 'col-nitt-0006', 'ex-jee-main-2026', '27ac76c1-12f1-4688-a3d9-d33cc19d7725', 'OBC', '2024', 6000, 18000, 1, 'AI', NULL),
('94ddb751-c5d0-06a4-2c45-da598914f3a5', 'col-nitk-0007', 'ex-jee-main-2026', '48d4bc21-935f-4321-af37-7d7a60c2ca7b', 'OBC', '2023', 4000, 12600, 1, 'AI', NULL),
('958b86f2-2d32-b948-62cd-6394c1984b04', 'col-iimb-0010', 'ex-cat-2026', '4dd4f2f8-f7af-4946-91e5-341bec1ce65e', 'General', '2023', 99, 100, 1, 'AI', NULL),
('9626b365-0a64-a342-4552-8d1bde77e0f3', 'col-iitd-0002', 'ex-jee-adv-2026', 'f7f070e2-4185-48e2-b15d-7ac08a1a8181', 'OBC', '2023', 50, 430, 1, 'AI', NULL),
('96a64e85-1f75-226e-5647-2173d885602b', 'col-iima-0009', 'ex-cat-2026', '123f2a50-6400-4e2b-b162-e05ca620db84', 'EWS', '2024', 90, 95, 1, 'AI', NULL),
('96da4e5c-ebd5-d9cf-9a08-79fd5a3e026c', 'col-iitb-0001', 'ex-jee-adv-2026', '8c866980-ae6b-4ec3-9710-aa55d8983639', 'SC', '2023', 100, 850, 1, 'AI', NULL),
('97bb4ee4-1af7-456f-f4d4-976c218c458f', 'col-iitk-0004', 'ex-jee-adv-2026', '2a1f5cba-9a08-4896-b4b1-7cc93d1395e5', 'General', '2022', 500, 1660, 1, 'AI', NULL),
('98641912-3fcd-bc70-e7f3-9ad41bbf4992', 'col-iitk-0004', 'ex-jee-adv-2026', '061733e9-32b0-420d-a74b-af2ca6a7c2ae', 'General', '2024', 1, 250, 1, 'AI', NULL),
('99316323-57b8-f068-d765-ffcd3aebfad9', 'col-iitb-0001', 'ex-jee-adv-2026', '8c866980-ae6b-4ec3-9710-aa55d8983639', 'SC', '2022', 100, 900, 1, 'AI', NULL),
('9a56ad64-e09e-6002-e3b8-52ced7c095f1', 'col-iimb-0010', 'ex-cat-2026', '4dd4f2f8-f7af-4946-91e5-341bec1ce65e', 'OBC', '2024', 94, 97, 1, 'AI', NULL),
('9a6a60cd-0707-682f-77ba-0b35bc7ceab1', 'col-iitb-0001', 'ex-jee-adv-2026', '8c866980-ae6b-4ec3-9710-aa55d8983639', 'ST', '2024', 200, 1500, 1, 'AI', NULL),
('9ada5fa7-074b-9b3f-e1bd-4262f7a2bd7a', 'col-nitk-0007', 'ex-jee-main-2026', '48d4bc21-935f-4321-af37-7d7a60c2ca7b', 'EWS', '2023', 3000, 10000, 1, 'AI', NULL),
('9bb70442-bd26-c4c4-f17f-46edd4cd8c13', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'EWS', '2022', 20, 92, 1, 'AI', NULL),
('9be74ce3-d33b-f8ec-919f-23ee29e905f4', 'col-iitb-0001', 'ex-jee-adv-2026', '8c866980-ae6b-4ec3-9710-aa55d8983639', 'EWS', '2022', 30, 290, 1, 'AI', NULL),
('9c44366e-ee21-8dba-c6cb-f7282adba876', 'col-iitb-0001', 'ex-jee-adv-2026', '8c866980-ae6b-4ec3-9710-aa55d8983639', 'SC', '2024', 100, 800, 1, 'AI', NULL),
('9db3f8df-c998-b8fa-4c8e-4773cb9a3477', 'col-iitb-0001', 'ex-jee-adv-2026', '9ed15a3d-520d-41a0-af6a-6f2020991524', 'OBC', '2022', 400, 1320, 1, 'AI', NULL),
('9ee006fa-d431-1c1d-6016-55a2e6e022dd', 'col-iitd-0002', 'ex-jee-adv-2026', 'f7f070e2-4185-48e2-b15d-7ac08a1a8181', 'General', '2023', 1, 132, 1, 'AI', NULL),
('9ee50f2a-d05f-3e28-cee7-098492da3da0', 'col-iitk-0004', 'ex-jee-adv-2026', '061733e9-32b0-420d-a74b-af2ca6a7c2ae', 'SC', '2022', 200, 1660, 1, 'AI', NULL),
('a00f5d25-b1e6-a4c2-7302-5f9f16e83da9', 'col-bits-0008', 'ex-bitstat-2026', 'd48ba0ba-3b5c-44f8-8dcc-c75018f2faba', 'OBC', '2023', 260, 328, 1, 'AI', NULL),
('a06262f9-618d-2271-a0b0-46855f778c1f', 'col-iitd-0002', 'ex-jee-adv-2026', 'f7f070e2-4185-48e2-b15d-7ac08a1a8181', 'General', '2024', 1, 120, 1, 'AI', NULL),
('a2867831-7600-1896-1f0f-b262b66d071b', 'col-iitk-0004', 'ex-jee-adv-2026', '061733e9-32b0-420d-a74b-af2ca6a7c2ae', 'SC', '2024', 200, 1500, 1, 'AI', NULL),
('a2df6219-7fab-acfb-5c00-9b4720ae1ec8', 'col-iitb-0001', 'ex-jee-adv-2026', '8c866980-ae6b-4ec3-9710-aa55d8983639', 'ST', '2023', 200, 1600, 1, 'AI', NULL),
('a360531f-1be8-8f6c-e2c0-2c9646af26ef', 'col-iima-0009', 'ex-cat-2026', '123f2a50-6400-4e2b-b162-e05ca620db84', 'EWS', '2022', 90, 95, 1, 'AI', NULL),
('a3c6da48-9fc4-33f9-5959-71872f087800', 'col-iitkgp-0005', 'ex-jee-adv-2026', '7dfc0eed-fa03-4618-aed8-48f39f75c6c8', 'General', '2023', 800, 3150, 1, 'AI', NULL),
('a46efca5-3f92-d5f7-64c1-cc1e14c12614', 'col-iitm-0003', 'ex-jee-adv-2026', '976faa51-7487-4976-a9dc-9d55cdfbc07c', 'EWS', '2022', 40, 400, 1, 'AI', NULL),
('a5ae481b-3c7c-39d7-4724-ba1d4bae7980', 'col-iitb-0001', 'ex-jee-adv-2026', '9ed15a3d-520d-41a0-af6a-6f2020991524', 'SC', '2023', 800, 2600, 1, 'AI', NULL),
('a6291aab-abb9-34b6-f9a1-e03155b5e7f6', 'col-iima-0009', 'ex-cat-2026', '123f2a50-6400-4e2b-b162-e05ca620db84', 'General', '2024', 99, 100, 1, 'AI', NULL),
('a7404afb-8908-87bb-2088-8803eb65e3e9', 'col-aiims-0011', 'ex-neet-pg-2026', 'b8c5be46-1c87-4821-9ae1-1b6a976d22d9', 'OBC', '2022', 50, 340, 1, 'AI', NULL),
('a76f2020-a5ca-0645-02c3-d0cc46540058', 'col-bits-0008', 'ex-bitstat-2026', 'd48ba0ba-3b5c-44f8-8dcc-c75018f2faba', 'OBC', '2022', 260, 326, 1, 'AI', NULL),
('a7b444c5-fbb8-87e8-0e04-9cc236bf7005', 'col-iitkgp-0005', 'ex-jee-adv-2026', '7dfc0eed-fa03-4618-aed8-48f39f75c6c8', 'OBC', '2023', 1500, 5200, 1, 'AI', NULL),
('a91e9135-00bb-ccdc-37ac-4a69db1eb0b0', 'col-iitd-0002', 'ex-jee-adv-2026', 'f7f070e2-4185-48e2-b15d-7ac08a1a8181', 'ST', '2022', 200, 2000, 1, 'AI', NULL),
('a9614306-847f-a565-5ea4-866a64b88c9c', 'col-iitd-0002', 'ex-jee-adv-2026', 'f7f070e2-4185-48e2-b15d-7ac08a1a8181', 'EWS', '2022', 30, 340, 1, 'AI', NULL),
('aa7b4dbb-db4a-1b10-699b-1d29d587611a', 'col-iitk-0004', 'ex-jee-adv-2026', '061733e9-32b0-420d-a74b-af2ca6a7c2ae', 'OBC', '2022', 80, 670, 1, 'AI', NULL),
('ab1b0e2d-e6c1-117a-1c36-976cc3066e19', 'col-iima-0009', 'ex-cat-2026', '123f2a50-6400-4e2b-b162-e05ca620db84', 'OBC', '2023', 95, 97, 1, 'AI', NULL),
('ad46063a-4597-d906-926c-415d23ecf9c2', 'col-nitk-0007', 'ex-jee-main-2026', '48d4bc21-935f-4321-af37-7d7a60c2ca7b', 'General', '2024', 1800, 6000, 1, 'AI', NULL),
('ae41523f-f6a9-e15f-8fe3-568a3fd7604b', 'col-iitm-0003', 'ex-jee-adv-2026', '976faa51-7487-4976-a9dc-9d55cdfbc07c', 'OBC', '2023', 60, 480, 1, 'AI', NULL),
('b048ee90-5409-b051-ef39-a39077f3695c', 'col-aiims-0011', 'ex-neet-pg-2026', 'b8c5be46-1c87-4821-9ae1-1b6a976d22d9', 'General', '2024', 1, 100, 1, 'AI', NULL),
('b3429d2c-3fbc-256b-72dc-cad8897ef10b', 'col-nlsiu-0012', 'ex-clat-2026', NULL, 'EWS', '2024', 30, 200, 1, 'AI', NULL),
('b3f84bca-9d70-924f-832a-e3b28c57b67f', 'col-nlsiu-0012', 'ex-clat-2026', NULL, 'SC', '2023', 200, 1600, 1, 'AI', NULL),
('b4dfc573-d716-1b43-abce-50f5a9dc614f', 'col-iitd-0002', 'ex-jee-adv-2026', NULL, 'OBC', '2023', 500, 1470, 1, 'AI', NULL),
('b4fecd02-f08b-f25d-f7ca-e7290fd1589a', 'col-iimb-0010', 'ex-cat-2026', '4dd4f2f8-f7af-4946-91e5-341bec1ce65e', 'EWS', '2023', 88, 93, 1, 'AI', NULL),
('b50bb3c3-0f7b-a828-45b5-8a02c36b065c', 'col-iima-0009', 'ex-cat-2026', '123f2a50-6400-4e2b-b162-e05ca620db84', 'SC', '2022', 80, 85, 1, 'AI', NULL),
('b82d3498-a186-b483-b497-2b7c7e33972e', 'col-nitk-0007', 'ex-jee-main-2026', 'df806c01-4df5-4a64-8233-6e6cc26d06b3', 'General', '2022', 3000, 10000, 1, 'AI', NULL),
('b9106b32-1410-7b5c-9c03-d50969223f3e', 'col-iitkgp-0005', 'ex-jee-adv-2026', '1782400a-cc00-4100-a175-f88ff7debf08', 'General', '2022', 1, 340, 1, 'AI', NULL),
('b9c2127a-c9b5-8e6a-bb1d-994df71e6451', 'col-iitb-0001', 'ex-jee-adv-2026', '9ed15a3d-520d-41a0-af6a-6f2020991524', 'General', '2024', 200, 600, 1, 'AI', NULL),
('b9c4a668-efba-56dc-fdeb-020fc49aa803', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'ST', '2024', 200, 1000, 1, 'AI', NULL),
('bae554b3-30ee-9267-4c50-d57752198f6a', 'col-iitm-0003', 'ex-jee-adv-2026', '976faa51-7487-4976-a9dc-9d55cdfbc07c', 'OBC', '2024', 60, 450, 1, 'AI', NULL),
('bcc2121f-01ad-d265-d80c-b6477be36637', 'col-iimb-0010', 'ex-cat-2026', '4dd4f2f8-f7af-4946-91e5-341bec1ce65e', 'General', '2024', 99, 100, 1, 'AI', NULL),
('bdf3c14e-b0b1-4587-cf07-fd527b720aa9', 'col-nitk-0007', 'ex-jee-main-2026', '48d4bc21-935f-4321-af37-7d7a60c2ca7b', 'General', '2022', 1800, 6700, 1, 'AI', NULL),
('c0cfd1e4-a32d-b96e-bef7-cc32225a500a', 'col-iitkgp-0005', 'ex-jee-adv-2026', '7dfc0eed-fa03-4618-aed8-48f39f75c6c8', 'OBC', '2022', 1500, 5400, 1, 'AI', NULL),
('c1743f1e-338f-af1d-28f5-47a384c8272c', 'col-iitm-0003', 'ex-jee-adv-2026', '976faa51-7487-4976-a9dc-9d55cdfbc07c', 'General', '2024', 1, 150, 1, 'AI', NULL),
('c249f738-6990-7d40-0dad-eddb39363b30', 'col-iitb-0001', 'ex-jee-adv-2026', '8c866980-ae6b-4ec3-9710-aa55d8983639', 'OBC', '2022', 50, 400, 1, 'AI', NULL),
('c34cf20b-688a-e8f0-12fb-745d691e417f', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'SC', '2022', 100, 560, 1, 'AI', NULL),
('c556684c-0133-bdfd-c389-49f7ffe3a2ed', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'OBC', '2024', 80, 150, 2, 'AI', NULL),
('c57f45f0-dddd-1b3b-9c2b-2f5bbd27b576', 'col-iitm-0003', 'ex-jee-adv-2026', '976faa51-7487-4976-a9dc-9d55cdfbc07c', 'OBC', '2022', 60, 510, 1, 'AI', NULL),
('c5c30e26-d1cb-08d8-808b-c1187af09a17', 'col-iitb-0001', 'ex-jee-adv-2026', '9ed15a3d-520d-41a0-af6a-6f2020991524', 'EWS', '2023', 300, 950, 1, 'AI', NULL),
('c60303b2-2923-b739-bdde-f79b4511b417', 'col-iitkgp-0005', 'ex-jee-adv-2026', '1782400a-cc00-4100-a175-f88ff7debf08', 'SC', '2022', 250, 2200, 1, 'AI', NULL),
('c618bb36-81a1-57b3-3d4c-00a2edf65ef8', 'col-iitm-0003', 'ex-jee-adv-2026', '3dc22518-64c4-4f37-acce-be3bb8b3f539', 'General', '2022', 300, 900, 1, 'AI', NULL),
('c7752de9-9e3b-513f-3f83-a8cf1b2de632', 'col-iitkgp-0005', 'ex-jee-adv-2026', '1782400a-cc00-4100-a175-f88ff7debf08', 'General', '2023', 1, 320, 1, 'AI', NULL),
('c7b3a0fd-9c0b-88da-1a4d-2db9dcf13b1f', 'col-iitd-0002', 'ex-jee-adv-2026', 'f7f070e2-4185-48e2-b15d-7ac08a1a8181', 'OBC', '2024', 50, 400, 1, 'AI', NULL),
('c7f086bb-4a7a-b438-8e93-03505c735bd9', 'col-nitt-0006', 'ex-jee-main-2026', '27ac76c1-12f1-4688-a3d9-d33cc19d7725', 'OBC', '2023', 6000, 18800, 1, 'AI', NULL),
('c884bc35-74ec-40f0-37ef-eb59eef91045', 'col-iitb-0001', 'ex-jee-adv-2026', '8c866980-ae6b-4ec3-9710-aa55d8983639', 'OBC', '2024', 50, 350, 1, 'AI', NULL),
('cb4a1ae7-3dce-bc9c-8006-cdd5d88c9b94', 'col-iimb-0010', 'ex-cat-2026', '4dd4f2f8-f7af-4946-91e5-341bec1ce65e', 'General', '2022', 99, 100, 1, 'AI', NULL),
('cb4eefad-9991-63ca-3d06-78041d7b17f8', 'col-aiims-0011', 'ex-neet-pg-2026', 'b8c5be46-1c87-4821-9ae1-1b6a976d22d9', 'General', '2022', 1, 120, 1, 'AI', NULL),
('cf321527-d6d1-49e1-f306-68355ef383e6', 'col-iima-0009', 'ex-cat-2026', '123f2a50-6400-4e2b-b162-e05ca620db84', 'SC', '2024', 80, 85, 1, 'AI', NULL),
('d0c27a27-1c1e-ead5-ca8a-58abe283d512', 'col-iitd-0002', 'ex-jee-adv-2026', NULL, 'OBC', '2022', 500, 1540, 1, 'AI', NULL),
('d1b2c57c-c825-3923-65e4-70326199652e', 'col-nlsiu-0012', 'ex-clat-2026', NULL, 'EWS', '2023', 30, 215, 1, 'AI', NULL),
('d339c736-065f-60e7-a337-5f6e196f0bef', 'col-bits-0008', 'ex-bitstat-2026', '54dd6dd0-6419-4f0a-9cbe-58330f60c570', 'General', '2024', 240, 310, 1, 'AI', NULL),
('d4f39cd9-e957-87d6-1ae0-64819962da36', 'col-nitk-0007', 'ex-jee-main-2026', '48d4bc21-935f-4321-af37-7d7a60c2ca7b', 'OBC', '2022', 4000, 13200, 1, 'AI', NULL),
('d52c1402-1998-8672-e092-c23af0aaaa7e', 'col-nlsiu-0012', 'ex-clat-2026', NULL, 'ST', '2024', 500, 3000, 1, 'AI', NULL),
('d54be8b7-7d8e-a4f3-b0c7-db4bf743df22', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'General', '2023', 40, 81, 2, 'AI', NULL),
('d6aa24fb-5d8a-b0ee-6836-b63c610d9e37', 'col-nitt-0006', 'ex-jee-main-2026', '874ce98e-7434-45fc-98cb-f0d8e29a079d', 'EWS', '2023', 2500, 8400, 1, 'AI', NULL),
('d948722a-74ca-6189-7d02-2b1ef05105f4', 'col-nitk-0007', 'ex-jee-main-2026', 'df806c01-4df5-4a64-8233-6e6cc26d06b3', 'General', '2023', 3000, 9500, 1, 'AI', NULL),
('dbcf1102-11b1-f737-feb3-3f29ed2cfbb2', 'col-nitt-0006', 'ex-jee-main-2026', '874ce98e-7434-45fc-98cb-f0d8e29a079d', 'SC', '2024', 5000, 20000, 1, 'AI', NULL),
('df1bba36-75c5-b208-0e53-0cad5a066fd3', 'col-iima-0009', 'ex-cat-2026', '123f2a50-6400-4e2b-b162-e05ca620db84', 'ST', '2024', 70, 75, 1, 'AI', NULL),
('e03edf3b-a66e-f183-7c50-1b4385d35fd8', 'col-nlsiu-0012', 'ex-clat-2026', NULL, 'General', '2023', 1, 110, 1, 'AI', NULL),
('e34a6cc8-57f1-7e2f-fabb-728d698fbf51', 'col-iitk-0004', 'ex-jee-adv-2026', '061733e9-32b0-420d-a74b-af2ca6a7c2ae', 'EWS', '2022', 50, 510, 1, 'AI', NULL),
('e4d3b5fa-c1ad-eb92-0060-58e04af2d98b', 'col-nitt-0006', 'ex-jee-main-2026', '874ce98e-7434-45fc-98cb-f0d8e29a079d', 'EWS', '2022', 2500, 8800, 1, 'AI', NULL),
('e88a669f-201f-11ca-58b0-f73e69a87bd8', 'col-iimb-0010', 'ex-cat-2026', '4dd4f2f8-f7af-4946-91e5-341bec1ce65e', 'SC', '2024', 78, 83, 1, 'AI', NULL),
('e98be9b0-2772-f82c-d8b4-9505e0222a7e', 'col-nitt-0006', 'ex-jee-main-2026', '874ce98e-7434-45fc-98cb-f0d8e29a079d', 'General', '2023', 1500, 5300, 1, 'AI', NULL),
('e9beab99-ca40-6831-b3ca-369a8f38c5c6', 'col-aiims-0011', 'ex-neet-pg-2026', '2219d11a-fbe0-4375-bad9-a3a74b745b04', 'OBC', '2024', 80, 400, 1, 'AI', NULL),
('ead32ecc-f418-3c97-5756-1f14f0a886e7', 'col-iitkgp-0005', 'ex-jee-adv-2026', '1782400a-cc00-4100-a175-f88ff7debf08', 'SC', '2024', 250, 2000, 1, 'AI', NULL),
('eb6a35cb-4c46-a4bc-f25e-da0910c20236', 'col-bits-0008', 'ex-bitstat-2026', '54dd6dd0-6419-4f0a-9cbe-58330f60c570', 'General', '2022', 240, 306, 1, 'AI', NULL),
('ec018c99-c55a-88f3-74ee-a73c7018fe18', 'col-aiims-0011', 'ex-neet-pg-2026', '2219d11a-fbe0-4375-bad9-a3a74b745b04', 'General', '2022', 1, 174, 1, 'AI', NULL),
('ec046a79-d3b9-d30c-63ae-b6931e935ae1', 'col-iitm-0003', 'ex-jee-adv-2026', '3dc22518-64c4-4f37-acce-be3bb8b3f539', 'OBC', '2023', 600, 1680, 1, 'AI', NULL),
('ec83ba19-9fd2-1cd6-eb7a-7bd610b15d5b', 'col-iitb-0001', 'ex-jee-adv-2026', '9ed15a3d-520d-41a0-af6a-6f2020991524', 'General', '2022', 200, 680, 1, 'AI', NULL),
('ef7e5efe-a1b0-4c7c-e045-c32afa110fce', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'General', '2024', 40, 75, 2, 'AI', NULL),
('efcf36a2-bffd-a37f-898e-385e98aa4fed', 'col-iitd-0002', 'ex-jee-adv-2026', 'f7f070e2-4185-48e2-b15d-7ac08a1a8181', 'OBC', '2022', 50, 460, 1, 'AI', NULL),
('f27db285-10ed-de93-41ce-b5f1f7067615', 'col-iitb-0001', 'ex-jee-adv-2026', '9ed15a3d-520d-41a0-af6a-6f2020991524', 'OBC', '2023', 400, 1260, 1, 'AI', NULL),
('f29f6d48-ae4a-68dc-07d1-e4a5d942efcd', 'col-nlsiu-0012', 'ex-clat-2026', NULL, 'OBC', '2023', 50, 320, 1, 'AI', NULL),
('f467eb13-6217-5690-4ce3-8989201445ff', 'col-iitkgp-0005', 'ex-jee-adv-2026', '1782400a-cc00-4100-a175-f88ff7debf08', 'General', '2024', 1, 300, 1, 'AI', NULL),
('f58dfe9d-c4dc-ad0a-511f-17b61acc6ed0', 'col-nitk-0007', 'ex-jee-main-2026', '48d4bc21-935f-4321-af37-7d7a60c2ca7b', 'SC', '2022', 6000, 24400, 1, 'AI', NULL),
('f5d301dd-3e4b-da6f-8902-aafaaf78b627', 'col-iitk-0004', 'ex-jee-adv-2026', '2a1f5cba-9a08-4896-b4b1-7cc93d1395e5', 'General', '2024', 500, 1500, 1, 'AI', NULL),
('f76e74e0-173d-2290-f17f-ab2b3b8ff8f7', 'col-iitb-0001', 'ex-jee-adv-2026', '9ed15a3d-520d-41a0-af6a-6f2020991524', 'EWS', '2022', 300, 1000, 1, 'AI', NULL),
('fb0d9d83-499e-bd24-21c1-d078d0095472', 'col-aiims-0011', 'ex-neet-ug-2026', '0852eaf4-a4c6-424f-916c-abd16a8a8d0f', 'OBC', '2022', 30, 116, 1, 'AI', NULL),
('fbcb6726-c858-e86b-fefc-efe0b303e5e0', 'col-nitt-0006', 'ex-jee-main-2026', '874ce98e-7434-45fc-98cb-f0d8e29a079d', 'OBC', '2023', 3000, 10500, 1, 'AI', NULL),
('fd131847-7db1-de23-3254-070a75a84d62', 'col-iitk-0004', 'ex-jee-adv-2026', '061733e9-32b0-420d-a74b-af2ca6a7c2ae', 'General', '2023', 1, 265, 1, 'AI', NULL),
('fe738b9b-bd07-388c-4e1e-78f4d7f34a65', 'col-bits-0008', 'ex-bitstat-2026', 'd48ba0ba-3b5c-44f8-8dcc-c75018f2faba', 'OBC', '2024', 260, 330, 1, 'AI', NULL);

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
('060f0afe-9d23-4dd7-9aa0-ad22eb587433', 'col-aiims-0011', 'Prof. Neerja Bhatla', 'Associate Professor', 'Obstetrics and Gynaecology', 'PhD', 22, NULL, NULL, NULL, 'Maternal and Fetal Medicine', NULL),
('06a6439e-b21b-4f3f-8f71-1284623ae294', 'col-bits-0008', 'Prof. M. Balakrishnan', 'Professor', 'Computer Science and Information Systems', 'PhD', 29, NULL, NULL, NULL, 'Software Engineering and DevOps', NULL),
('14e20a4e-ccaa-40ce-ac20-2b92c1d70944', 'col-iitkgp-0005', 'Dr. Tapan K. Ghosh', 'Assistant Professor', 'Mechanical Engineering', 'PhD', 10, NULL, NULL, NULL, 'Manufacturing Science and Engineering', NULL),
('192e4b60-042b-48e2-b954-9aef405119e2', 'col-iitm-0003', 'Prof. K. Ramanathan', 'Professor', 'Computer Science and Engineering', 'PhD', 30, NULL, NULL, NULL, 'Machine Learning and Data Science', NULL),
('1d44a083-3379-4dfd-9163-52c5c1edee1a', 'col-nlsiu-0012', 'Prof. (Dr.) M.P. Singh', 'Professor', 'Constitutional Law', 'PhD', 28, NULL, NULL, NULL, 'Constitutional Law and Public Policy', NULL),
('221f8fa7-915c-4a8b-ae0d-2be8f0a9a5d7', 'col-bits-0008', 'Prof. V. Chithambaram', 'Associate Professor', 'Electrical and Electronics Engineering', 'PhD', 16, NULL, NULL, NULL, 'Embedded Systems and IoT', NULL),
('2248135e-9dac-4f46-a04b-f97838180d96', 'col-iitk-0004', 'Prof. Ashok K. Misra', 'Professor', 'Computer Science and Engineering', 'PhD', 26, NULL, NULL, NULL, 'Algorithms and Complexity Theory', NULL),
('257e533c-7542-4c1c-b371-3581eb09c150', 'col-iima-0009', 'Prof. Saral Mukherjee', 'Associate Professor', 'Production and Quantitative Methods', 'PhD', 18, NULL, NULL, NULL, 'Operations Management and Supply Chain', NULL),
('29cf2715-d1e1-48cd-8f8c-4e87a285beef', 'col-iitkgp-0005', 'Prof. Arpita M. Banerjee', 'Associate Professor', 'Civil Engineering', 'PhD', 19, NULL, NULL, NULL, 'Structural Engineering and Earthquake Analysis', NULL),
('2ab21deb-e155-4beb-a0e4-f2d8b2e6479c', 'col-iitb-0001', 'Prof. Meera S. Patel', 'Associate Professor', 'Electrical Engineering', 'PhD', 18, NULL, NULL, NULL, 'VLSI Design and Embedded Systems', NULL),
('350390bd-3bae-44a4-b063-7bdc14a92197', 'col-iitk-0004', 'Prof. Nandini S. Rao', 'Associate Professor', 'Electrical Engineering', 'PhD', 17, NULL, NULL, NULL, 'Control Systems and Automation', NULL),
('3a370275-bca0-425c-a72c-c389e5c8181f', 'col-iitm-0003', 'Dr. Suresh Babu R.', 'Assistant Professor', 'Aerospace Engineering', 'PhD', 9, NULL, NULL, NULL, 'Aerodynamics and Flight Mechanics', NULL),
('3c8717e9-ba8e-4dcf-9458-6201828b23d8', 'col-iima-0009', 'Prof. Ashish Nanda', 'Professor', 'Organizational Behaviour', 'PhD', 30, NULL, NULL, NULL, 'Leadership and Organizational Design', NULL),
('3ef29e40-462f-4b15-b067-b46d88a8b23a', 'col-iitm-0003', 'Prof. Lakshmi N. Iyer', 'Associate Professor', 'Electrical Engineering', 'PhD', 20, NULL, NULL, NULL, 'Power Electronics and Drives', NULL),
('43228d33-3b6d-4e03-9df9-6c9e97ea8dc9', 'col-iitd-0002', 'Prof. Priya R. Singh', 'Associate Professor', 'Electrical Engineering', 'PhD', 16, NULL, NULL, NULL, 'Signal Processing and Communication Systems', NULL),
('55501d71-c2db-48ff-ac19-69df3c6a2fb2', 'col-iitb-0001', 'Dr. Anand V. Kulkarni', 'Assistant Professor', 'Mechanical Engineering', 'PhD', 8, NULL, NULL, NULL, 'Thermal Engineering and Fluid Mechanics', NULL),
('5b3a7570-bdae-4691-b848-20c200d1c5cd', 'col-aiims-0011', 'Prof. Randeep Guleria', 'Professor', 'Pulmonary Medicine', 'PhD', 35, NULL, NULL, NULL, 'Pulmonary and Critical Care Medicine', NULL),
('671c416d-3c76-411b-8afd-a5e5e61b9cf4', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'dvfvfv', 'Frontend Developer', 'dvfvfv', 'fdghfgn', 3, 'uploads/faculty/1782646110_a75699bd-7902-48dc-90ff-1584f5cf8048-removebg-preview.png', 3, 'https://www.linkedin.com/in/madhav-arora-32b056254/', 'dfdg', 'hgvcrfh'),
('7558143f-da20-4713-913a-7eeccc01ac6a', 'col-nitt-0006', 'Prof. K. Sangeetha', 'Associate Professor', 'Electrical and Electronics Engineering', 'PhD', 15, NULL, NULL, NULL, 'Power Systems and Renewable Energy', NULL),
('76c57c6e-b0e5-4209-ba3b-cf81ed798d38', 'col-iimb-0010', 'Prof. Dinesh Kumar', 'Associate Professor', 'Finance and Accounting', 'PhD', 17, NULL, NULL, NULL, 'Corporate Finance and Investment Banking', NULL),
('7c512f06-208e-447e-9f68-353662f032a9', 'col-nitt-0006', 'Prof. R. Palanisamy', 'Professor', 'Computer Science and Engineering', 'PhD', 24, NULL, NULL, NULL, 'Network Security and Cryptography', NULL),
('80681811-6339-47c3-9646-f4a040432929', 'col-iitkgp-0005', 'Prof. Subrata K. Das', 'Professor', 'Computer Science and Engineering', 'PhD', 27, NULL, NULL, NULL, 'Computer Networks and Security', NULL),
('80b766f8-185e-4372-bafe-7346fa7ec657', 'col-bits-0008', 'Dr. Sneha S. Nair', 'Assistant Professor', 'Mechanical Engineering', 'PhD', 9, NULL, NULL, NULL, 'Robotics and Automation', NULL),
('84a573bd-8b6a-43d2-bb69-4108c5970add', 'col-iitk-0004', 'Dr. Prakash C. Verma', 'Assistant Professor', 'Aerospace Engineering', 'PhD', 6, NULL, NULL, NULL, 'Propulsion Systems and Combustion', NULL),
('84f0c11c-8798-4d40-8164-3cf6996b7d94', 'col-iimb-0010', 'Prof. Suresh Bhagavatula', 'Professor', 'Entrepreneurship', 'PhD', 28, NULL, NULL, NULL, 'Entrepreneurship and Family Business', NULL),
('8c2ad34f-0b9d-4f74-8562-ab9d0e30f7a9', 'col-nitt-0006', 'Dr. M. Selvakumar', 'Assistant Professor', 'Mechanical Engineering', 'PhD', 7, NULL, NULL, NULL, 'Thermal Engineering and HVAC', NULL),
('96a6e0fd-a60e-495d-8e64-f26ad96ea19e', 'col-iitd-0002', 'Dr. Vikram M. Reddy', 'Assistant Professor', 'Mechanical Engineering', 'PhD', 7, NULL, NULL, NULL, 'Robotics and Control Systems', NULL),
('a6b1ba68-73a1-432b-9fc4-718786aa89e6', 'col-iima-0009', 'Dr. Ruchira Gupta', 'Assistant Professor', 'Marketing', 'PhD', 8, NULL, NULL, NULL, 'Consumer Behaviour and Digital Marketing', NULL),
('a95004aa-b0a5-48cc-bb4c-953c4745626b', 'col-nlsiu-0012', 'Dr. Varsha Valsala Menon', 'Assistant Professor', 'International Law', 'PhD', 9, NULL, NULL, NULL, 'International Humanitarian Law and Human Rights', NULL),
('aec1b2a4-ddb9-458f-a7d8-f5a668dd7e54', 'col-nlsiu-0012', 'Prof. (Dr.) Sudhir Krishnaswamy', 'Associate Professor', 'Corporate Law', 'PhD', 20, NULL, NULL, NULL, 'Corporate Governance and Securities Regulation', NULL),
('b04d4be2-00b9-418e-a2a5-d54e3430ff60', 'col-aiims-0011', 'Dr. Sanjeev Kumar', 'Assistant Professor', 'Anaesthesiology', 'PhD', 10, NULL, NULL, NULL, 'Regional Anaesthesia and Pain Management', NULL),
('b4ccf600-15a1-4980-8bdb-54e6eb7f659e', 'col-iitd-0002', 'Prof. Sanjay K. Gupta', 'Professor', 'Computer Science and Engineering', 'PhD', 28, NULL, NULL, NULL, 'Distributed Systems and Cloud Computing', NULL),
('ba468682-9337-48da-a796-1afe02ec7dec', 'col-nitk-0007', 'Prof. S. Naveen', 'Associate Professor', 'Electronics and Communication Engineering', 'PhD', 14, NULL, NULL, NULL, 'VLSI and Signal Processing', NULL),
('d401f4b6-b285-4c3a-bff6-7dc8e7e29b06', 'col-nitk-0007', 'Prof. P. Shama Bhat', 'Professor', 'Computer Science and Engineering', 'PhD', 22, NULL, NULL, NULL, 'Artificial Intelligence and Data Mining', NULL),
('daf26363-f9c6-43d1-abcb-de098ac3011c', 'col-nitk-0007', 'Dr. Arun K. Shetty', 'Assistant Professor', 'Civil Engineering', 'PhD', 8, NULL, NULL, NULL, 'Transportation Engineering and Planning', NULL),
('eb2fe684-4117-4088-91a2-b553b2800ea1', 'col-iimb-0010', 'Dr. Anupama Kondayya', 'Assistant Professor', 'Information Systems', 'PhD', 7, NULL, NULL, NULL, 'Business Analytics and Digital Transformation', NULL),
('f5f3631f-3068-4e80-99a8-03b3f827c7f4', 'col-iitb-0001', 'Prof. Rajesh K. Sharma', 'Professor', 'Computer Science and Engineering', 'PhD', 25, NULL, NULL, NULL, 'Artificial Intelligence and Machine Learning', NULL);

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
('02a61b4b-ea2c-4ed3-87ad-8cb5dad0bbfa', 'col-nlsiu-0012', 'What is the fee structure for BA LLB at NLSIU?', 'The total fee for BA LLB at NLSIU is approximately INR 12,50,000 for 5 years (INR 2,50,000 per year). This includes tuition, hostel, and other institutional charges. Scholarships are available for meritorious students.', NULL, 2, 1, 0),
('21f9690a-8113-4643-9a4b-2696037fe79b', 'col-iitb-0001', 'How are placements at IIT Bombay?', 'IIT Bombay has an excellent placement record with over 94% placement rate. The average package is around INR 22 LPA and the highest package goes up to INR 1.35 Crore per annum. Top recruiters include Google, Microsoft, Goldman Sachs, and Amazon.', NULL, 3, 1, 0),
('22a912e8-aa19-481b-95f9-da1e0df8b2e3', 'col-iitd-0002', 'What is the fee structure for B.Tech at IIT Delhi?', 'The total fee for B.Tech at IIT Delhi is approximately INR 8,00,000 for 4 years (INR 2,00,000 per year). This includes tuition, hostel, and mess charges. Fee waivers are available for economically weaker sections.', NULL, 2, 1, 0),
('2429eca7-ec9f-4e8c-981e-f42773ce2f0d', 'col-bits-0008', 'What are the placement statistics of BITS Pilani?', 'BITS Pilani has a strong placement record with 91% placement rate. The average package is around INR 18 LPA with the highest package reaching INR 85 LPA. Top recruiters include Google, Microsoft, Amazon, and Goldman Sachs.', NULL, 3, 1, 0),
('260f0b9e-bbe4-410e-a326-e6337fc0e0aa', 'col-iitm-0003', 'What is the total fee for B.Tech at IIT Madras?', 'The total fee for B.Tech at IIT Madras is approximately INR 8,00,000 for 4 years (INR 2,00,000 per year). This covers tuition, hostel, and other institutional charges. Merit-based scholarships are available.', NULL, 2, 1, 0),
('2dc8de08-8f25-4416-b376-c168803ed46a', 'col-nitk-0007', 'What is the admission process for B.Tech at NIT Surathkal?', 'Admission to B.Tech at NIT Surathkal is through JEE Main. Candidates must qualify JEE Main and apply through CSAB/JoSAA counselling. Admission is based on All India Rank in JEE Main.', NULL, 1, 1, 0),
('31ce74a1-537f-402b-9410-b0afe3bbc3cf', 'col-iitk-0004', 'What are the placement statistics of IIT Kanpur?', 'IIT Kanpur has a strong placement record with 92.5% placement rate. The average package is around INR 20.8 LPA with the highest package reaching INR 1.15 Crore. Top recruiters include Google, Microsoft, Amazon, and Goldman Sachs.', NULL, 3, 1, 0),
('36eceb18-3156-42d4-985a-ab5996ebf843', 'col-aiims-0011', 'What are the placement statistics of AIIMS Delhi?', 'AIIMS Delhi has a 98.5% placement record. The average package is around INR 13.5 LPA with the highest package reaching INR 28 LPA. Most graduates join AIIMS Hospital, Fortis, Apollo, or pursue super-specialty courses.', NULL, 3, 1, 0),
('372612ce-cff5-4ed9-a372-43a686f3c021', 'col-iitb-0001', 'What is the admission process for B.Tech at IIT Bombay?', 'Admission to B.Tech programs at IIT Bombay is through JEE Advanced. Candidates must first qualify JEE Main, then appear for JEE Advanced. Admission is based on All India Rank in JEE Advanced through JoSAA counselling.', NULL, 1, 1, 0),
('3b57c39b-12cb-4c44-823b-5b03ac163ec2', 'col-iitd-0002', 'What are the placement statistics of IIT Delhi?', 'IIT Delhi has a strong placement record with over 93% placement rate. The average package is around INR 21.5 LPA with the highest package reaching INR 1.25 Crore. Top recruiters include Google, Microsoft, Amazon, and Goldman Sachs.', NULL, 3, 1, 0),
('434e0d2e-aac0-4358-8ad2-c865e1c2fff2', 'col-nlsiu-0012', 'How are placements at NLSIU Bangalore?', 'NLSIU has a 96% placement record. The average package is around INR 20 LPA with the highest package reaching INR 60 LPA. Top recruiters include Cyril Amarchand Mangaldas, AZB & Partners, Khaitan & Co, and Tata Group.', NULL, 3, 1, 0),
('47faee9a-4662-4001-aff5-1da36848e7e4', 'col-nitt-0006', 'What is the fee structure for B.Tech at NIT Trichy?', 'The total fee for B.Tech at NIT Trichy is approximately INR 4,80,000 for 4 years (INR 1,20,000 per year). Government funding makes it more affordable compared to private institutions. Scholarships are available.', NULL, 2, 1, 0),
('51b49fbe-5a49-4f7f-9407-9b9348321bb8', 'col-nitk-0007', 'What is the fee structure for B.Tech at NIT Surathkal?', 'The total fee for B.Tech at NIT Surathkal is approximately INR 4,80,000 for 4 years (INR 1,20,000 per year). Being a government institution, the fees are subsidized. Scholarships are available for meritorious students.', NULL, 2, 1, 0),
('5b4c7853-69bf-4208-bb04-ae0ac0926936', 'col-nitt-0006', 'What are the placement statistics of NIT Trichy?', 'NIT Trichy has a strong placement record with 89.5% placement rate. The average package is around INR 13.8 LPA with the highest package reaching INR 58 LPA. Top recruiters include TCS, Infosys, Amazon, and Microsoft.', NULL, 3, 1, 0),
('60ccaf9c-e67d-4581-a41d-80adf35c6fe5', 'col-iima-0009', 'What is the admission process for MBA at IIM Ahmedabad?', 'Admission to PGP (MBA) at IIM Ahmedabad is through CAT (Common Admission Test). Shortlisted candidates are called for Written Ability Test (WAT) and Personal Interview (PI). Final selection is based on CAT score, WAT, PI, and academic profile.', NULL, 1, 1, 0),
('61896e28-9c0c-492e-a869-e94818f316c3', 'col-aiims-0011', 'What is the admission process for MBBS at AIIMS?', 'Admission to MBBS at AIIMS Delhi is through NEET UG (National Eligibility cum Entrance Test). Candidates must qualify NEET UG with a top All India Rank. AIIMS accepts NEET scores for MBBS admission since 2020.', NULL, 1, 1, 0),
('7a57aaff-3ed7-49af-b0cd-63796240f5a9', 'col-iima-0009', 'What is the fee structure for MBA at IIM Ahmedabad?', 'The total fee for PGP (MBA) at IIM Ahmedabad is approximately INR 46,00,000 for 2 years (INR 23,00,000 per year). This includes tuition, hostel, mess, and other charges. Education loans are readily available.', NULL, 2, 1, 0),
('83007786-fc0d-4a74-bff7-b5c1450dbfde', 'col-iitb-0001', 'What is the total fee for B.Tech at IIT Bombay?', 'The total fee for B.Tech at IIT Bombay is approximately INR 8,00,000 for 4 years (INR 2,00,000 per year). This includes tuition fee, hostel fee, and other charges. Scholarships are available for meritorious students.', NULL, 2, 1, 0),
('90995fe7-20a2-4c22-add3-77d241122db8', 'col-iitkgp-0005', 'How are placements at IIT Kharagpur?', 'IIT Kharagpur has a good placement record with 92% placement rate. The average package is around INR 20.1 LPA and the highest package is INR 1.10 Crore. Top recruiters include Google, Microsoft, Amazon, and TCS.', NULL, 3, 1, 0),
('958cb5c7-08fa-4191-b0c4-a4f3bd74abf1', 'col-nitt-0006', 'What is the admission process for B.Tech at NIT Tiruchirappalli?', 'Admission to B.Tech at NIT Trichy is through JEE Main. Candidates must qualify JEE Main and apply through CSAB/JoSAA counselling. Admission is based on All India Rank in JEE Main.', NULL, 1, 1, 0),
('9e548890-a0cf-461c-a6c7-3f6bfcda2e5e', 'col-iitm-0003', 'How are placements at IIT Madras?', 'IIT Madras has an exceptional placement record with 95% placement rate. The average package is around INR 23.2 LPA and the highest package is INR 1.40 Crore. Top recruiters include Google, Microsoft, Amazon, and Apple.', NULL, 3, 1, 0),
('a8bb3383-9244-44a7-88ad-0fbe2a5bc466', 'col-bits-0008', 'What is the admission process for B.E. at BITS Pilani?', 'Admission to BITS Pilani is through BITSAT (BITS Admission Test). Candidates must have scored 75% in PCM in Class XII and qualify BITSAT. Admission is based on BITSAT score through iterative counselling.', NULL, 1, 1, 0),
('ae628df6-1100-4c1a-88c7-6d7a5015e517', 'col-iitkgp-0005', 'What is the admission process for B.Tech at IIT Kharagpur?', 'Admission to B.Tech at IIT Kharagpur is through JEE Advanced. Candidates must first qualify JEE Main, then appear for JEE Advanced. Seat allocation is through JoSAA counselling based on All India Rank.', NULL, 1, 1, 0),
('b51ec617-b8f7-4889-b10b-d56120b1d817', 'col-nlsiu-0012', 'What is the admission process for BA LLB at NLSIU?', 'Admission to BA LLB at NLSIU is through CLAT (Common Law Admission Test). Candidates must qualify CLAT with a top All India Rank. NLSIU is one of the most sought-after law schools accepting CLAT scores.', NULL, 1, 1, 0),
('c324ff85-4923-443d-a883-f9541ef0ac81', 'col-aiims-0011', 'What is the fee structure for MBBS at AIIMS?', 'The total fee for MBBS at AIIMS Delhi is extremely affordable at approximately INR 8,250 for 5.5 years (INR 1,500 per year). AIIMS is fully funded by the Government of India, making it one of the most affordable medical education options.', NULL, 2, 1, 0),
('c8fc83a5-fff0-44e6-a4a2-e57025b303ca', 'col-iimb-0010', 'What is the fee structure for MBA at IIM Bangalore?', 'The total fee for PGP (MBA) at IIM Bangalore is approximately INR 46,00,000 for 2 years (INR 23,00,000 per year). This includes tuition, accommodation, and other institutional charges. Financial assistance is available.', NULL, 2, 1, 0),
('cfcdca2c-2852-49cb-a60b-0a3f99ed0936', 'col-bits-0008', 'What is the fee structure for B.E. at BITS Pilani?', 'The total fee for B.E. at BITS Pilani is approximately INR 14,00,000 for 4 years (INR 3,50,000 per year). Being a deemed university, fees are higher but BITS offers merit scholarships and fee waivers.', NULL, 2, 1, 0),
('d43fb9ca-8143-4211-b91e-10b8bc956386', 'col-iitkgp-0005', 'What is the total fee for B.Tech at IIT Kharagpur?', 'The total fee for B.Tech at IIT Kharagpur is approximately INR 8,00,000 for 4 years (INR 2,00,000 per year). This covers tuition, hostel, and other institutional charges. Merit-based scholarships are available.', NULL, 2, 1, 0),
('e2595b7e-189c-4a16-b7fe-3d550d56e447', 'col-iimb-0010', 'How are placements at IIM Bangalore?', 'IIM Bangalore has 100% placement record. The average package is around INR 36.5 LPA with the highest package reaching INR 1.25 Crore. Top recruiters include McKinsey, BCG, Goldman Sachs, JP Morgan, and Google.', NULL, 3, 1, 0),
('e6b1977c-1a46-4281-a494-47ed9c4430be', 'col-iimb-0010', 'What is the admission process for MBA at IIM Bangalore?', 'Admission to PGP (MBA) at IIM Bangalore is through CAT (Common Admission Test). Shortlisted candidates are called for Written Ability Test (WAT) and Personal Interview (PI). Final selection considers CAT score, WAT, PI, and profile.', NULL, 1, 1, 0),
('ee4ad5ed-044a-47da-92b6-8be8edea5fae', 'col-iima-0009', 'What are the placement statistics of IIM Ahmedabad?', 'IIM Ahmedabad has 100% placement record. The average package is around INR 37.2 LPA with the highest package reaching INR 1.35 Crore. Top recruiters include McKinsey, BCG, Bain, Goldman Sachs, and Google.', NULL, 3, 1, 0),
('ef435650-257f-4f76-ae23-ba760481eccf', 'col-nitk-0007', 'How are placements at NIT Surathkal?', 'NIT Surathkal has a good placement record with 89% placement rate. The average package is around INR 13.2 LPA with the highest package reaching INR 55 LPA. Top recruiters include TCS, Infosys, Amazon, and Microsoft.', NULL, 3, 1, 0),
('efebd496-d1eb-4a81-b37c-92af452be792', 'col-iitm-0003', 'What is the admission process for B.Tech at IIT Madras?', 'Admission to B.Tech at IIT Madras is through JEE Advanced. Candidates must first qualify JEE Main, then appear for JEE Advanced. Seat allocation is through JoSAA counselling based on All India Rank.', NULL, 1, 1, 0),
('f01be98c-8951-4728-b77c-130db6bebb46', 'col-iitd-0002', 'What is the admission process for B.Tech at IIT Delhi?', 'Admission to B.Tech at IIT Delhi is through JEE Advanced. Candidates must qualify JEE Main and then appear for JEE Advanced. Admission is through JoSAA counselling based on All India Rank.', NULL, 1, 1, 0),
('fa02b494-d455-4ada-9fcb-cc5a95c12554', 'col-iitk-0004', 'What is the admission process for B.Tech at IIT Kanpur?', 'Admission to B.Tech at IIT Kanpur is through JEE Advanced. Candidates must qualify JEE Main first, then appear for JEE Advanced. Admission is through JoSAA counselling based on All India Rank.', NULL, 1, 1, 0),
('fd34f418-9f36-472b-a8bb-c75ea171a267', 'col-iitk-0004', 'What is the fee structure for B.Tech at IIT Kanpur?', 'The total fee for B.Tech at IIT Kanpur is approximately INR 8,00,000 for 4 years (INR 2,00,000 per year). This includes tuition, hostel, and mess charges. Scholarships are available for meritorious and economically weaker students.', NULL, 2, 1, 0);

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
('020dd04a-1ec6-4885-ac9f-092c109c20d6', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 1, 'boys', 2456, 456777.00, 1, 'veg', 1, NULL, NULL, 1),
('0217b1bd-ff65-4a43-a472-89191d5cfa39', 'col-nlsiu-0012', 1, 'both', 800, 60000.00, 1, 'both', 1, NULL, NULL, 0),
('0e62422f-31ac-481a-b6ce-dcaac63d1c3e', 'col-iimb-0010', 1, 'both', 1100, 145000.00, 1, 'both', 1, NULL, NULL, 0),
('1d75ea23-0755-46b1-b924-70a68d8af8a8', 'col-iitd-0002', 1, 'both', 7500, 55000.00, 1, 'both', 1, NULL, NULL, 0),
('6241f800-9ddf-42e8-a4ac-06daccc41aa7', 'col-aiims-0011', 1, 'both', 2000, 15000.00, 1, 'both', 1, NULL, NULL, 0),
('661faecb-4aca-4264-ae7d-e17d36a6b0cd', 'col-nitk-0007', 1, 'both', 5000, 33000.00, 1, 'both', 1, NULL, NULL, 0),
('6ae90e0a-eb7b-4339-a28c-41d1afcace8e', 'col-bits-0008', 1, 'both', 10000, 80000.00, 1, 'both', 1, NULL, NULL, 0),
('9757f58f-cd9d-4feb-9395-c66a3aae6f83', 'col-iitk-0004', 1, 'both', 7000, 48000.00, 1, 'both', 1, NULL, NULL, 0),
('a4205245-3775-4dac-9b4c-51d170e27a7a', 'col-iima-0009', 1, 'both', 1200, 150000.00, 1, 'both', 1, NULL, NULL, 0),
('c6acb334-1811-4cf5-a352-c1c9d732f5fb', 'col-iitkgp-0005', 1, 'both', 9000, 42000.00, 1, 'both', 1, NULL, NULL, 0),
('ce8819cb-02b2-4774-a679-7c5c5b6c924e', 'col-iitb-0001', 1, 'both', 8000, 50000.00, 1, 'both', 1, NULL, NULL, 0),
('e00e761a-b9e2-4248-942e-54a57870078e', 'col-iitm-0003', 1, 'both', 8500, 45000.00, 1, 'both', 1, NULL, NULL, 0),
('e2e99823-20af-46c4-a4ae-7de8a01f3e2e', 'col-nitt-0006', 1, 'both', 5500, 35000.00, 1, 'both', 1, NULL, NULL, 0);

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
('0ec05eac-d273-42bf-9029-429930293575', 'col-iimb-0010', 1, 140000, NULL, NULL, 1, NULL, 1, 1, NULL, 1, 1, 0, 0),
('0f8285db-06d0-479d-a970-588005b93e8d', 'col-iima-0009', 1, 150000, NULL, NULL, 1, NULL, 1, 1, NULL, 1, 1, 0, 0),
('63021ece-ac4f-40dc-b6cf-b76f3f28544a', 'col-iitk-0004', 1, 400000, NULL, NULL, 1, NULL, 1, 1, NULL, 1, 1, 0, 0),
('86a4df87-0ce0-4dd0-9716-ac1b31c65bd5', 'col-nlsiu-0012', 1, 100000, NULL, NULL, 1, NULL, 1, 1, NULL, 1, 1, 0, 0),
('9c899e86-0df6-4626-a57c-f078abdf9506', 'col-iitd-0002', 1, 450000, NULL, NULL, 1, NULL, 1, 1, NULL, 1, 1, 0, 0),
('a6cd059f-0696-4d78-9dba-358957cd46bf', 'col-iitm-0003', 1, 520000, NULL, NULL, 1, NULL, 1, 1, NULL, 1, 1, 0, 0),
('d0a020f2-4f3b-4e1f-8701-2fed50f57ab8', 'col-aiims-0011', 1, 200000, NULL, NULL, 1, NULL, 1, 1, NULL, 1, 1, 0, 0),
('d3a945a2-73fe-45ea-95c5-423d9920b529', 'col-iitkgp-0005', 1, 600000, NULL, NULL, 1, NULL, 1, 1, NULL, 1, 1, 0, 0),
('d41ea6be-12ba-4141-8e69-31774c630404', 'col-nitt-0006', 1, 300000, NULL, NULL, 1, NULL, 1, 1, NULL, 1, 1, 0, 0),
('d6bc331d-ab55-4906-9043-3cc4f05a6a65', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 1, NULL, '[\"htu fjhtjhngffcfthg\",\"hhy hyh56tyt\"]', '[\"tv5vyyyyyyyyyyyyyyyyyyythyh y\",\"yhgghfgb\"]', 1, NULL, 1, 1, NULL, 1, 1, 1, 1),
('e1e9f51c-4a4c-4073-8f08-ce5fe49ccdd3', 'col-bits-0008', 1, 350000, NULL, NULL, 1, NULL, 1, 1, NULL, 1, 1, 0, 0),
('f55960f8-ba0f-4598-bc95-ed12795cec02', 'col-nitk-0007', 1, 280000, NULL, NULL, 1, NULL, 1, 1, NULL, 1, 1, 0, 0),
('fd5a6a48-965b-4aef-b416-7efbcb640b85', 'col-iitb-0001', 1, 500000, NULL, NULL, 1, NULL, 1, 1, NULL, 1, 1, 0, 0);

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
('27c7a566-0186-46d5-a58a-8a6e48e46c07', 'col-nitk-0007', 'assets/images/exam-logos/nitk.svg', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'campus', NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL),
('66be98a5-6b1a-477f-b68d-9772ee150c49', 'col-iitb-0001', 'assets/images/exam-logos/iitb.svg', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'campus', NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL),
('710ba1cc-5a12-40e4-8f29-604f46e57ef7', 'col-nitt-0006', 'assets/images/exam-logos/nitt.svg', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'campus', NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL),
('75a14a20-c2af-4148-92c8-c4eb2ec0ff25', 'col-aiims-0011', 'assets/images/exam-logos/aiims.svg', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'campus', NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL),
('7c11ef10-9420-4d0f-a106-7f24552ab348', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL),
('81f118fa-fa77-4acc-b95b-ed286644e496', 'col-iitd-0002', 'assets/images/exam-logos/iitd.svg', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'campus', NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL),
('920e5442-4bc1-474a-be62-8cf4abfbec27', 'col-iitm-0003', 'assets/images/exam-logos/iitm.svg', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'campus', NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL),
('98b1f13a-bdaa-48aa-ab48-7be52605cf25', 'col-iitkgp-0005', 'assets/images/exam-logos/iitkgp.svg', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'campus', NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL),
('db2448c4-eb27-4ec5-bb46-f26eaeb54a24', 'col-bits-0008', 'assets/images/exam-logos/bits.svg', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'campus', NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL),
('e91ae1fd-806e-45c4-9996-1aa24352279a', 'col-nlsiu-0012', 'assets/images/exam-logos/nlsiu.svg', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'campus', NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL),
('eb61a15c-5e5e-4c6e-bba3-1bc158191b2e', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', NULL, NULL, '', NULL, NULL, 'xcvbn', 0, NULL, NULL, NULL, 0, 'uploads/media/1782646083_153006.jpg'),
('f1470186-9257-42fd-b263-55b5902143d3', 'col-iimb-0010', 'assets/images/exam-logos/iimb.svg', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'campus', NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL),
('f4784852-e0b0-4b0e-baa6-03895cb18219', 'col-iitk-0004', 'assets/images/exam-logos/iitk.svg', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'campus', NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL),
('f807ec9e-36b1-4ec0-93b5-df4b261b7a7b', 'col-iima-0009', 'assets/images/exam-logos/iima.svg', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'campus', NULL, NULL, NULL, 0, NULL, NULL, NULL, 0, NULL);

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
('071a822c-736f-40b6-b333-dd1eb6d42b42', 'col-iith-0015', '2023', 16.80, 58.00, 11.00, 82, 420, NULL, NULL, NULL, NULL),
('0911f25c-dc92-4d06-b035-ca02bd141b1a', 'col-nitk-0007', '2024', 12.00, 48.00, 10.50, 87.5, 850, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Amazon\",\"Microsoft\",\"Samsung\",\"Bosch\"]', NULL, NULL),
('12f45239-8a88-49f7-83f5-4894a1be2f06', 'col-iitkgp-0005', '2025', 20.10, 110.00, 17.20, 92, 1450, NULL, '[\"Google\",\"Microsoft\",\"Amazon\",\"Goldman Sachs\",\"TCS\",\"Infosys\",\"Cognizant\",\"Wipro\"]', NULL, NULL),
('148a9fee-0ea8-4262-8204-c7955a86e5f5', 'col-iitg-0014', '2024', 17.50, 62.00, 11.50, 83, 430, NULL, NULL, NULL, NULL),
('14ffe9f3-efa0-4cff-b298-66f91ee2bcbf', 'col-nlsiu-0012', '2024', 18.00, 55.00, 15.00, 95, 180, NULL, '[\"Cyril Amarchand Mangaldas\",\"AZB & Partners\",\"Khaitan & Co\",\"Luthra & Luthra\",\"Shardul Amarchand Mangaldas\",\"ICICI Bank\"]', NULL, NULL),
('155dad59-cc27-4d0a-b48b-3d8b78598116', 'col-iitm-0003', '2025', 23.20, 140.00, 20.10, 95, 1600, NULL, '[\"Google\",\"Microsoft\",\"Amazon\",\"Goldman Sachs\",\"McKinsey & Company\",\"Texas Instruments\",\"Samsung\",\"Apple\"]', NULL, NULL),
('1c222b96-05a5-496a-a71a-94b5db9ed24e', 'col-iitk-0004', '2025', 20.80, 115.00, 17.80, 92.5, 1250, NULL, '[\"Google\",\"Microsoft\",\"Amazon\",\"Goldman Sachs\",\"Adobe\",\"Qualcomm\",\"Flipkart\",\"Razorpay\"]', NULL, NULL),
('1d19ca13-e3c3-45dc-a00f-5ba896b3cbc9', 'col-iitd-0002', '2025', 21.50, 125.00, 18.50, 93.2, 1400, NULL, '[\"Google\",\"Microsoft\",\"Amazon\",\"Goldman Sachs\",\"Deloitte\",\"Adobe\",\"Qualcomm\",\"Flipkart\"]', NULL, NULL),
('274a2468-7097-40b6-b539-634a481b77bb', 'col-aiims-0011', '2024', 12.00, 25.00, 10.00, 98, 340, NULL, '[\"AIIMS Hospital\",\"Fortis Healthcare\",\"Apollo Hospitals\",\"Max Healthcare\",\"Manipal Hospitals\",\"Medanta\"]', NULL, NULL),
('2808aa7e-70e5-44b0-aac3-803ca146126b', 'col-iitm-0003', '2024', 21.50, 115.00, 18.20, 93.5, 1550, NULL, '[\"Google\",\"Microsoft\",\"Amazon\",\"Goldman Sachs\",\"McKinsey & Company\",\"Texas Instruments\",\"Samsung\"]', NULL, NULL),
('2bdeb9a3-90fe-4a4f-b6d0-43ac295336f2', 'col-iima-0009', '2025', 37.20, 135.00, 32.50, 100, 400, NULL, '[\"McKinsey & Company\",\"BCG\",\"Bain & Company\",\"Goldman Sachs\",\"JP Morgan\",\"Google\",\"Amazon\",\"Deloitte\"]', NULL, NULL),
('34e9857c-4522-4c70-9b1b-701f0bc516ff', 'col-iimb-0010', '2025', 36.50, 125.00, 32.00, 100, 530, NULL, '[\"McKinsey & Company\",\"BCG\",\"Bain & Company\",\"Goldman Sachs\",\"JP Morgan\",\"Microsoft\",\"Google\",\"Flipkart\"]', NULL, NULL),
('480336d3-d7a0-4daf-8929-b7fc4748c587', 'col-iitd-0002', '2024', 19.80, 110.00, 16.80, 91.8, 1350, NULL, '[\"Google\",\"Microsoft\",\"Amazon\",\"Goldman Sachs\",\"Deloitte\",\"Adobe\",\"Qualcomm\"]', NULL, NULL),
('57998ad6-0554-4983-920d-e1ac1e91e4e0', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', '2026', 55.00, 77.00, 90.00, 56, 55, NULL, '[{\"name\":\"dg\"},{\"name\":\"duhfgvf\"},{\"name\":\"cducgfj\"}]', NULL, NULL),
('600503c6-6683-4177-b4bd-8bbf2f825c05', 'col-iitr-0013', '2024', 18.50, 65.00, 12.00, 85, 450, NULL, NULL, NULL, NULL),
('61479145-619b-461a-8027-123cb1ec97e5', 'col-iitg-0014', '2023', 15.80, 55.00, 10.50, 80, 400, NULL, NULL, NULL, NULL),
('63aca271-8705-4bb2-8473-4e19963a10e7', 'col-iitb-0001', '2024', 20.30, 120.00, 17.50, 92.5, 1480, NULL, '[\"Google\",\"Microsoft\",\"Goldman Sachs\",\"Amazon\",\"Apple\",\"McKinsey & Company\",\"Bain & Company\"]', NULL, NULL),
('6bd4397f-ba90-402c-9455-76cabd3244d4', 'col-iima-0009', '2024', 34.50, 120.00, 30.00, 100, 395, NULL, '[\"McKinsey & Company\",\"BCG\",\"Bain & Company\",\"Goldman Sachs\",\"JP Morgan\",\"Google\",\"Amazon\"]', NULL, NULL),
('6d43b8d6-ae21-49c3-bca7-7b7e89f16239', 'col-bits-0008', '2025', 18.00, 85.00, 15.80, 91, 1650, NULL, '[\"Google\",\"Microsoft\",\"Amazon\",\"Goldman Sachs\",\"Adobe\",\"Qualcomm\",\"Flipkart\",\"Atlassian\"]', NULL, NULL),
('6f0c5641-cf0b-47bb-ad8f-82033e780457', 'col-nitt-0006', '2024', 12.50, 52.00, 10.80, 88, 950, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Cognizant\",\"Amazon\",\"Microsoft\",\"L&T\"]', NULL, NULL),
('7e464674-ff01-4436-ae42-9655c1423ff7', 'col-iitkgp-0005', '2024', 18.50, 95.00, 15.50, 90.5, 1400, NULL, '[\"Google\",\"Microsoft\",\"Amazon\",\"Goldman Sachs\",\"TCS\",\"Infosys\",\"Cognizant\"]', NULL, NULL),
('98086d54-2bc5-4135-a667-63e59bd29fdf', 'col-aiims-0011', '2025', 13.50, 28.00, 11.50, 98.5, 345, NULL, '[\"AIIMS Hospital\",\"Fortis Healthcare\",\"Apollo Hospitals\",\"Max Healthcare\",\"Manipal Hospitals\",\"Medanta\",\"Narayana Health\"]', NULL, NULL),
('a1567a19-0aa1-487d-bd54-5a6521b3f634', 'col-nlsiu-0012', '2025', 20.00, 60.00, 17.00, 96, 185, NULL, '[\"Cyril Amarchand Mangaldas\",\"AZB & Partners\",\"Khaitan & Co\",\"Luthra & Luthra\",\"Shardul Amarchand Mangaldas\",\"ICICI Bank\",\"Tata Group\"]', NULL, NULL),
('b479f7ab-b67c-40f4-8540-158bd0764b60', 'col-iitk-0004', '2024', 19.20, 100.00, 16.00, 91, 1200, NULL, '[\"Google\",\"Microsoft\",\"Amazon\",\"Goldman Sachs\",\"Adobe\",\"Qualcomm\",\"Flipkart\"]', NULL, NULL),
('bcf3fb58-0f52-47b8-adf8-a5e5524b4a0f', 'col-nitt-0006', '2025', 13.80, 58.00, 12.00, 89.5, 1000, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Cognizant\",\"Amazon\",\"Microsoft\",\"L&T\",\"Bosch\"]', NULL, NULL),
('bd4c20c7-4336-447e-bb30-dd2a619b8204', 'col-iitr-0013', '2023', 16.80, 58.00, 11.00, 82, 420, NULL, NULL, NULL, NULL),
('c57a267f-a8d8-479c-96b4-c2934938bd2f', 'col-iitb-0001', '2025', 22.10, 135.00, 19.20, 94, 1520, NULL, '[\"Google\",\"Microsoft\",\"Goldman Sachs\",\"Amazon\",\"Apple\",\"McKinsey & Company\",\"Bain & Company\",\"OpenAI\"]', NULL, NULL),
('ceb5e256-769c-44e2-a0f7-34fae592fe23', 'col-iimb-0010', '2024', 33.80, 110.00, 29.50, 100, 520, NULL, '[\"McKinsey & Company\",\"BCG\",\"Bain & Company\",\"Goldman Sachs\",\"JP Morgan\",\"Microsoft\",\"Google\"]', NULL, NULL),
('d6fd88c1-0468-44d2-9170-6df83f2a8873', 'col-nitk-0007', '2025', 13.20, 55.00, 11.80, 89, 900, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Amazon\",\"Microsoft\",\"Samsung\",\"Bosch\",\"Oracle\"]', NULL, NULL),
('d81159ac-51f8-4499-82ac-d4c4627decc4', 'col-bits-0008', '2024', 16.50, 72.00, 14.20, 89, 1600, NULL, '[\"Google\",\"Microsoft\",\"Amazon\",\"Goldman Sachs\",\"Adobe\",\"Qualcomm\",\"Flipkart\"]', NULL, NULL),
('e4eba42b-48f9-4082-9c2e-e3df3bdb5861', 'col-iith-0015', '2024', 18.50, 65.00, 12.00, 85, 450, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `college_qna`
--

CREATE TABLE `college_qna` (
  `id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `question_text` text NOT NULL,
  `answer_text` text DEFAULT NULL,
  `answered_by_user_id` char(36) DEFAULT NULL,
  `upvotes` int(11) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
('1437e89a-2cc3-4f71-b69e-690b87ad61f9', 'col-nitt-0006', 'Government of India Merit Scholarship', '', 0.00, NULL, 'Top 10% of department', 0, NULL),
('1cd8e5a9-6a27-4cf2-9e68-e977afd9dc53', 'col-iitk-0004', 'Institute Free Studentship', '', 0.00, NULL, 'Family income below INR 5 LPA', 0, NULL),
('37906575-305d-4207-8de8-796c0f525b14', 'col-iima-0009', 'IIMA Need-Based Financial Assistance', '', 0.00, NULL, 'Family income below INR 8 LPA', 0, NULL),
('3d2babd5-5f14-49d1-b50e-130e789fb9f8', 'col-iitk-0004', 'Institute Merit-cum-Means Scholarship', '', 0.00, NULL, 'Top 10% of department with family income below INR 8 LPA', 0, NULL),
('3f45b1bc-eac7-4f9a-8af1-3cb22d02b0f7', 'col-iitkgp-0005', 'Institute Merit-cum-Means Scholarship', '', 0.00, NULL, 'Top 10% of department with family income below INR 8 LPA', 0, NULL),
('4f0ef63a-6295-4413-9b9c-788d491e61dd', 'col-iitd-0002', 'Institute Free Studentship', '', 0.00, NULL, 'Family income below INR 5 LPA', 0, NULL),
('679421b5-1fbd-413a-a40a-d492ca9b5923', 'col-aiims-0011', 'Government of India Scholarship', '', 0.00, NULL, 'All admitted students (AIIMS is fully funded)', 0, NULL),
('6de4bf97-52e1-47f5-85f6-fdcd39833a72', 'col-iitd-0002', 'Institute Merit-cum-Means Scholarship', '', 0.00, NULL, 'Top 10% of department with family income below INR 8 LPA', 0, NULL),
('70f4b2ab-4eee-4219-b287-476687cd8640', 'col-iitm-0003', 'Institute Free Studentship', '', 0.00, NULL, 'Family income below INR 5 LPA', 0, NULL),
('8b10fc57-a549-4341-8e33-65c64da2cf07', 'col-iitkgp-0005', 'Institute Free Studentship', '', 0.00, NULL, 'Family income below INR 5 LPA', 0, NULL),
('8faf5a64-4f72-4175-bd46-ad464d4d24d8', 'col-iimb-0010', 'IIMB Need-Based Financial Assistance', '', 0.00, NULL, 'Family income below INR 8 LPA', 0, NULL),
('989f15f4-75f3-49aa-b6b2-3dc6bf812fd8', 'col-bits-0008', 'BITS Need-based Scholarship', '', 0.00, NULL, 'Family income below INR 8 LPA', 0, NULL),
('9cff1251-9424-415f-b185-7b758dfff31a', 'col-nlsiu-0012', 'NLSIU Need-Based Scholarship', '', 0.00, NULL, 'Family income below INR 6 LPA', 0, NULL),
('9d70463a-1376-478b-b264-7ac3d6e05589', 'col-nitt-0006', 'SC/ST Scholarship', '', 0.00, NULL, 'SC/ST category with family income below INR 6 LPA', 0, NULL),
('ac34b070-4914-41c1-8fe4-ca8df28d8bb1', 'col-nlsiu-0012', 'NLSIU Merit Scholarship', '', 0.00, NULL, 'Top 10% of batch', 0, NULL),
('bd0c1faa-7d5b-4f7d-b5b0-645d406cb97f', 'col-iimb-0010', 'IIMB Merit Scholarship', '', 0.00, NULL, 'Top 10% of batch', 0, NULL),
('cd0344e4-edbb-4f40-b549-98b4d3a2f0fc', 'col-iitm-0003', 'Institute Merit-cum-Means Scholarship', '', 0.00, NULL, 'Top 10% of department with family income below INR 8 LPA', 0, NULL),
('d3dbe4cb-fdb3-4f58-b0bc-a9f89a83018d', 'col-iima-0009', 'IIMA Merit Scholarship', '', 0.00, NULL, 'Top 10% of batch', 0, NULL),
('e013d489-0e7b-4afe-9b94-219db9db0a63', 'col-iitb-0001', 'Institute Free Studentship', '', 0.00, NULL, 'Family income below INR 5 LPA', 0, NULL),
('ee0af3e2-44a5-4897-9b50-ccc7e9f4b86d', 'col-nitk-0007', 'Government of India Merit Scholarship', '', 0.00, NULL, 'Top 10% of department', 0, NULL),
('fbb27273-fafc-473e-8011-17e983d2c475', 'col-iitb-0001', 'Institute Merit-cum-Means Scholarship', '', 0.00, NULL, 'Top 10% of department with family income below INR 8 LPA', 0, NULL),
('fd1c85ae-b643-462f-9481-418485943923', 'col-bits-0008', 'BITS Merit Scholarship', '', 0.00, NULL, 'BITSAT score above 350', 0, NULL),
('fea0fc8c-487e-4dbf-af64-5c4df08a8f54', 'col-nitk-0007', 'SC/ST Scholarship', '', 0.00, NULL, 'SC/ST category with family income below INR 6 LPA', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `college_submissions`
--

CREATE TABLE `college_submissions` (
  `id` char(36) NOT NULL,
  `account_id` char(36) NOT NULL,
  `college_id` char(36) NOT NULL,
  `submission_type` enum('profile','courses','placements','cutoffs','seat_matrix','facilities','faqs') NOT NULL,
  `data_json` longtext NOT NULL,
  `status` enum('draft','pending','approved','rejected') NOT NULL DEFAULT 'draft',
  `admin_note` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `college_submissions`
--

INSERT INTO `college_submissions` (`id`, `account_id`, `college_id`, `submission_type`, `data_json`, `status`, `admin_note`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
('0230351b-eb47-42ef-af2e-2fcf28609bef', '80d73b32-163e-4789-9789-b7a6a1dc7da8', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'cutoffs', '{\"exam_id\":\"ex-jee-adv-2026\",\"year\":2026,\"category\":\"OBC\",\"quota\":\"All India\",\"opening_rank\":6,\"closing_rank\":8,\"course_name\":\"gcvhdhjcf\"}', 'approved', NULL, 0, '2026-06-28 12:08:06', '2026-06-28 11:27:18', '2026-06-28 12:08:06'),
('0a64e140-5d84-4fad-bd66-d99b80341fb2', '80d73b32-163e-4789-9789-b7a6a1dc7da8', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'seat_matrix', '{\"course_id\":\"512d88d3-0f21-420b-a5ce-8e371db8f0a0\",\"category\":\"ST\",\"total_seats\":77,\"year\":2026,\"source\":\"yyfhvtu\"}', 'rejected', 'nothing', 0, '2026-06-28 12:11:58', '2026-06-28 11:27:30', '2026-06-28 12:11:58'),
('3bc0c725-7b8d-4128-b50c-7ee29c9d94c4', '80d73b32-163e-4789-9789-b7a6a1dc7da8', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'profile', '{\"name\":\"fhfhrfhfrh\",\"college_type\":\"govt\",\"ownership\":\"central\",\"type_label\":\"ebfuebfd\",\"founded_year\":\"2000\",\"university_id\":null,\"naac_grade\":null,\"ranking_nirf\":null,\"autonomous\":0,\"ugc_approved\":0,\"aicte_approved\":0,\"total_students\":null,\"total_faculty\":null,\"campus_area_acres\":null,\"campus_type\":null,\"state_id\":null,\"city_id\":null,\"logo_url\":\"\",\"cover_image_url\":\"\",\"email\":null,\"phone\":null,\"address\":null,\"latitude\":null,\"longitude\":null,\"website_url\":null,\"pincode\":null,\"google_maps_embed_url\":null,\"nearest_railway_km\":null,\"nearest_airport_km\":null}', 'approved', NULL, 0, '2026-06-27 16:59:37', '2026-06-27 16:47:45', '2026-06-27 16:59:37'),
('48ae4703-5bf4-4159-85e0-34d1c93c5aa6', '80d73b32-163e-4789-9789-b7a6a1dc7da8', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'placements', '{\"placement_year\":2026,\"avg_package_lpa\":55,\"highest_package_lpa\":77,\"median_package_lpa\":90,\"placement_percentage\":56,\"total_students\":88,\"total_placed\":55,\"top_recruiters\":\"dg,duhfgvf, cducgfj,\"}', 'approved', NULL, 0, '2026-06-28 12:09:39', '2026-06-28 11:27:06', '2026-06-28 12:09:39'),
('4de85850-742d-43c9-985b-5c03980be1c8', '80d73b32-163e-4789-9789-b7a6a1dc7da8', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'profile', '{\"about_text\":\"<section class=\\\"college-section\\\" style=\\\"margin: 0px 0px 36px; font-family: &quot;Space Grotesk&quot;, sans-serif; background-color: rgb(255, 255, 255);\\\"><p class=\\\"college-prose\\\" style=\\\"color: rgba(15, 23, 42, 0.65); line-height: 1.8; font-size: 0.93rem;\\\">IIM Ahmedabad, established in 1961, is consistently ranked as the number one management institution in India. Located in Ahmedabad, Gujarat, the institute is known for its case-based pedagogy, world-class faculty, and exceptional placement records. IIMA offers MBA, PGPX, and doctoral programs.<\\/p><\\/section>\",\"highlights_json\":\"[\\\"h fujfdjnsdwqhjsnkmhsudbHUISBCKASUIDBVB CJSAHZOIXJ ND CJSD  SDN\\\"]\",\"accreditations_json\":\"[\\\"VNMB HJEIHCJB DANBWDJB N JBKJbM jksbvdjbudsbmsdhbujfkj j chbsfj cmashcvdc\\\"]\",\"rankings_json\":\"{\\\"NIRF\\\":\\\"12\\\",\\\"NAAC\\\":\\\"B\\\"}\",\"awards_json\":\"[\\\"hvbefvbrjh v\\\",\\\"evchrbvc n dbc\\\",\\\"idvhc j hveurwd ncn\\\"]\",\"library\":1,\"auditorium\":1,\"cafeteria\":1,\"wifi\":1,\"medical_facility\":1,\"transport\":1,\"ev_charging\":1,\"solar_power\":1,\"sports_facilities\":\"[\\\"htu fjhtjhngffcfthg\\\",\\\"hhy hyh56tyt\\\"]\",\"labs\":\"[\\\"tv5vyyyyyyyyyyyyyyyyyyythyh y\\\",\\\"yhgghfgb\\\"]\",\"hostel_available\":1,\"hostel_type\":\"boys\",\"hostel_capacity\":\"2456\",\"hostel_fee_annual\":\"456777\",\"mess_available\":1,\"mess_type\":\"veg\",\"ac_available\":1,\"laundry_available\":1}', 'approved', NULL, 0, '2026-06-28 04:51:12', '2026-06-28 04:29:04', '2026-06-28 04:51:12'),
('6388cf8e-51a9-4c29-909e-2aded4cee044', '80d73b32-163e-4789-9789-b7a6a1dc7da8', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'courses', '{\"course_name\":\"gcvhdhjcf\",\"course_level\":\"PG\",\"duration_years\":\"2\",\"total_fee\":\"6999\",\"semester_fee\":\"3000\",\"annual_fee\":\"50000\",\"seats_available\":\"45\",\"specializations\":\"[\\\"cbjcxb hjdc\\\"]\",\"eligibility_criteria\":\"vjkdvc dbinxzgv\",\"application_fee\":\"68766\",\"emi_available\":1}', 'approved', NULL, 0, '2026-06-28 10:21:58', '2026-06-28 04:50:51', '2026-06-28 10:21:58'),
('6672063b-1c2d-4293-9252-6216c5a85d7b', '80d73b32-163e-4789-9789-b7a6a1dc7da8', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'profile', '{\"publish_status\":\"published\",\"meta_title\":\"ghxvhdcxfvbeyux\",\"meta_description\":\"Canonical URL\\r\\nOG Image File\\r\\nNo file chosen\\r\\nSchema Markup\\r\\n\\r\\nMark Page as No-Index (Do not show on Google)\\r\\n\\r\\nSave SEO Settings\",\"og_image_url\":\"\",\"canonical_url\":\"https:\\/\\/localhost\\/ADMISSION\\/college.php?id=e696ffa2-1ff6-45c5-8102-c4b494b5fac4\",\"schema_markup\":\"{\\n    \\\"@context\\\": \\\"https:\\/\\/schema.org\\\",\\n    \\\"@type\\\": \\\"CollegeOrUniversity\\\",\\n    \\\"name\\\": \\\"fhfhrfhfrh\\\",\\n    \\\"description\\\": \\\"\\\",\\n    \\\"url\\\": \\\"https:\\/\\/githib.com\\\",\\n    \\\"telephone\\\": \\\"09877275894\\\",\\n    \\\"address\\\": {\\n        \\\"@type\\\": \\\"PostalAddress\\\",\\n        \\\"streetAddress\\\": \\\"WARD 24, OSWALI MOHALLA\\\"\\n    }\\n}\",\"noindex\":0}', 'approved', NULL, 0, '2026-06-28 10:22:07', '2026-06-28 04:35:11', '2026-06-28 10:22:07'),
('7058316d-7e46-4999-b12a-25d14a893353', '80d73b32-163e-4789-9789-b7a6a1dc7da8', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'profile', '{\"name\":\"fhfhrfhfrh\",\"college_type\":\"govt\",\"ownership\":\"central\",\"type_label\":\"ebfuebfd\",\"founded_year\":\"2000\",\"university_id\":\"02f1f361-2b42-446e-bc59-4d7a7ac3a0fb\",\"naac_grade\":\"A++\",\"ranking_nirf\":\"5\",\"autonomous\":1,\"ugc_approved\":1,\"aicte_approved\":1,\"total_students\":\"20000\",\"total_faculty\":\"5558\",\"campus_area_acres\":\"56\",\"campus_type\":null,\"state_id\":\"1\",\"city_id\":\"8\",\"logo_url\":\"\",\"cover_image_url\":\"\",\"email\":\"madhavarora132005@gmail.com\",\"phone\":\"09877275894\",\"address\":\"WARD 24, OSWALI MOHALLA\",\"latitude\":\"23\",\"longitude\":\"44\",\"website_url\":\"https:\\/\\/githib.com\",\"pincode\":\"305801\",\"google_maps_embed_url\":\"https:\\/\\/maps.google.com\",\"nearest_railway_km\":\"66\",\"nearest_airport_km\":\"56\"}', 'approved', NULL, 0, '2026-06-28 04:51:05', '2026-06-28 04:20:21', '2026-06-28 04:51:05'),
('af64b519-622e-4daf-af2f-118cffe58e2a', '80d73b32-163e-4789-9789-b7a6a1dc7da8', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'profile', '{\"admission_process\":\"<p>&nbsp; cghbn vnhjv hb vygh<\\/p>\",\"accepted_exams\":\"[\\\"uyfyft cfgdghn\\\"]\",\"admission_start_date\":\"2026-06-29\",\"admission_end_date\":\"2026-06-30\",\"merit_based\":1,\"direct_admission\":1,\"management_quota_seats\":\"55\",\"nri_quota_seats\":\"66\",\"lateral_entry_available\":1,\"application_mode\":\"fg thfuyjh\"}', 'approved', NULL, 0, '2026-06-28 12:09:39', '2026-06-28 11:26:46', '2026-06-28 12:09:39'),
('b9284adb-c36a-46f4-bbdb-d05dc3403863', '80d73b32-163e-4789-9789-b7a6a1dc7da8', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'profile', '{\"publish_status\":\"published\",\"meta_title\":\"ghxvhdcxfvbeyuxft\",\"meta_description\":\"Canonical URL\\r\\nOG Image File\\r\\nNo file chosen\\r\\nSchema Markup\\r\\n\\r\\nMark Page as No-Index (Do not show on Google)\\r\\n\\r\\nSave SEO Settings\",\"og_image_url\":\"\",\"canonical_url\":\"https:\\/\\/localhost\\/ADMISSION\\/college.php?id=e696ffa2-1ff6-45c5-8102-c4b494b5fac4\",\"schema_markup\":\"{\\n    \\\"@context\\\": \\\"https:\\/\\/schema.org\\\",\\n    \\\"@type\\\": \\\"CollegeOrUniversity\\\",\\n    \\\"name\\\": \\\"fhfhrfhfrh\\\",\\n    \\\"description\\\": \\\"\\\",\\n    \\\"url\\\": \\\"https:\\/\\/githib.com\\\",\\n    \\\"telephone\\\": \\\"09877275894\\\",\\n    \\\"address\\\": {\\n        \\\"@type\\\": \\\"PostalAddress\\\",\\n        \\\"streetAddress\\\": \\\"WARD 24, OSWALI MOHALLA\\\"\\n    }\\n}\",\"noindex\":0}', 'rejected', 'Rejected by admin', 0, '2026-06-28 12:21:48', '2026-06-28 12:12:08', '2026-06-28 12:21:48'),
('ed8b7f69-0d40-4f83-92e1-1317c75a5cd9', '80d73b32-163e-4789-9789-b7a6a1dc7da8', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'profile', '{\"publish_status\":\"published\",\"meta_title\":\"ghxvhdcxfvbeyux\",\"meta_description\":\"Canonical URL\\r\\nOG Image File\\r\\nNo file chosen\\r\\nSchema Markup\\r\\n\\r\\nMark Page as No-Index (Do not show on Google)\\r\\n\\r\\nSave SEO Settings\",\"og_image_url\":\"\",\"canonical_url\":\"https:\\/\\/localhost\\/ADMISSION\\/college.php?id=e696ffa2-1ff6-45c5-8102-c4b494b5fac4\",\"schema_markup\":\"{\\n    \\\"@context\\\": \\\"https:\\/\\/schema.org\\\",\\n    \\\"@type\\\": \\\"CollegeOrUniversity\\\",\\n    \\\"name\\\": \\\"fhfhrfhfrh\\\",\\n    \\\"description\\\": \\\"\\\",\\n    \\\"url\\\": \\\"https:\\/\\/githib.com\\\",\\n    \\\"telephone\\\": \\\"09877275894\\\",\\n    \\\"address\\\": {\\n        \\\"@type\\\": \\\"PostalAddress\\\",\\n        \\\"streetAddress\\\": \\\"WARD 24, OSWALI MOHALLA\\\"\\n    }\\n}\",\"noindex\":0}', 'approved', NULL, 0, '2026-06-28 10:22:07', '2026-06-28 04:50:07', '2026-06-28 10:22:07');

-- --------------------------------------------------------

--
-- Table structure for table `college_updates`
--

CREATE TABLE `college_updates` (
  `id` char(36) NOT NULL,
  `slug` varchar(300) DEFAULT NULL,
  `college_id` char(36) NOT NULL,
  `title` varchar(500) NOT NULL,
  `description` text DEFAULT NULL,
  `update_type` enum('admission','placement','exam','event','ranking','scholarship','general') DEFAULT 'general',
  `event_date` date DEFAULT NULL,
  `action_url` varchar(500) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `college_updates`
--

INSERT INTO `college_updates` (`id`, `slug`, `college_id`, `title`, `description`, `update_type`, `event_date`, `action_url`, `status`, `created_at`) VALUES
('054541fd-7652-4edb-b9bd-1f7280376c0e', 'indian-institute-of-technology-kanpur-admission-2026-open', 'col-iitk-0004', 'Indian Institute of Technology Kanpur Admission 2026 Open', 'Applications for Indian Institute of Technology Kanpur are now open for the academic session 2026-27. Interested candidates must appear for the relevant entrance examination and apply through the official counselling process.', 'admission', '2026-07-21', NULL, 'published', '2026-06-21 15:24:33'),
('0677c061-426f-454a-a24a-dccea67f46d0', 'national-institute-of-technology-surathkal-admission-2026-open', 'col-nitk-0007', 'National Institute of Technology Surathkal Admission 2026 Open', 'Applications for National Institute of Technology Surathkal are now open for the academic session 2026-27. Interested candidates must appear for the relevant entrance examination and apply through the official counselling process.', 'admission', '2026-07-21', NULL, 'published', '2026-06-21 15:24:33'),
('0ef02247-f821-4390-a277-e43f9422ca02', 'all-india-institute-of-medical-sciences-delhi-admission-2026-open', 'col-aiims-0011', 'All India Institute of Medical Sciences Delhi Admission 2026 Open', 'Applications for All India Institute of Medical Sciences Delhi are now open for the academic session 2026-27. Interested candidates must appear for the relevant entrance examination and apply through the official counselling process.', 'admission', '2026-07-21', NULL, 'published', '2026-06-21 15:24:33'),
('1a47e153-e52a-4567-bb6d-4c8778e09f55', 'national-law-school-of-india-university-bangalore-admission-2026-open', 'col-nlsiu-0012', 'National Law School of India University Bangalore Admission 2026 Open', 'Applications for National Law School of India University Bangalore are now open for the academic session 2026-27. Interested candidates must appear for the relevant entrance examination and apply through the official counselling process.', 'admission', '2026-07-21', NULL, 'published', '2026-06-21 15:24:33'),
('1b91c529-a8a3-42bb-900a-ae72bfbb6ac2', 'indian-institute-of-technology-madras-placement-season-2025-record-breaking-numb', 'col-iitm-0003', 'Indian Institute of Technology Madras Placement Season 2025 - Record Breaking Numbers', 'Indian Institute of Technology Madras has concluded its 2025 placement season with outstanding results. The institute achieved over 90% placement with top packages exceeding INR 1 Crore per annum. Leading companies from various sectors participated in the campus recruitment drive.', 'placement', '2026-06-06', NULL, 'published', '2026-06-21 15:24:33'),
('207bb45c-a2f8-4e88-bd33-861753872dd0', 'national-institute-of-technology-tiruchirappalli-admission-2026-open', 'col-nitt-0006', 'National Institute of Technology Tiruchirappalli Admission 2026 Open', 'Applications for National Institute of Technology Tiruchirappalli are now open for the academic session 2026-27. Interested candidates must appear for the relevant entrance examination and apply through the official counselling process.', 'admission', '2026-07-21', NULL, 'published', '2026-06-21 15:24:33'),
('25e165df-29f8-4b5e-80ba-67dc71446188', 'indian-institute-of-technology-kharagpur-placement-season-2025-record-breaking-n', 'col-iitkgp-0005', 'Indian Institute of Technology Kharagpur Placement Season 2025 - Record Breaking Numbers', 'Indian Institute of Technology Kharagpur has concluded its 2025 placement season with outstanding results. The institute achieved over 90% placement with top packages exceeding INR 1 Crore per annum. Leading companies from various sectors participated in the campus recruitment drive.', 'placement', '2026-06-06', NULL, 'published', '2026-06-21 15:24:33'),
('2623f834-bfa0-4d40-a681-d991efd970ef', 'national-institute-of-technology-tiruchirappalli-placement-season-2025-record-br', 'col-nitt-0006', 'National Institute of Technology Tiruchirappalli Placement Season 2025 - Record Breaking Numbers', 'National Institute of Technology Tiruchirappalli has concluded its 2025 placement season with outstanding results. The institute achieved over 90% placement with top packages exceeding INR 1 Crore per annum. Leading companies from various sectors participated in the campus recruitment drive.', 'placement', '2026-06-06', NULL, 'published', '2026-06-21 15:24:33'),
('2d428271-9427-4306-b089-e84ec7a4f1ad', 'indian-institute-of-technology-madras-admission-2026-open', 'col-iitm-0003', 'Indian Institute of Technology Madras Admission 2026 Open', 'Applications for Indian Institute of Technology Madras are now open for the academic session 2026-27. Interested candidates must appear for the relevant entrance examination and apply through the official counselling process.', 'admission', '2026-07-21', NULL, 'published', '2026-06-21 15:24:33'),
('31cef3b8-3ce2-4779-accc-db49fbba701b', 'indian-institute-of-technology-bombay-admission-2026-open', 'col-iitb-0001', 'Indian Institute of Technology Bombay Admission 2026 Open', 'Applications for Indian Institute of Technology Bombay are now open for the academic session 2026-27. Interested candidates must appear for the relevant entrance examination and apply through the official counselling process.', 'admission', '2026-07-21', NULL, 'published', '2026-06-21 15:24:33'),
('3858727b-87f0-4eec-84c2-22279b72fc0a', 'all-india-institute-of-medical-sciences-delhi-placement-season-2025-record-break', 'col-aiims-0011', 'All India Institute of Medical Sciences Delhi Placement Season 2025 - Record Breaking Numbers', 'All India Institute of Medical Sciences Delhi has concluded its 2025 placement season with outstanding results. The institute achieved over 90% placement with top packages exceeding INR 1 Crore per annum. Leading companies from various sectors participated in the campus recruitment drive.', 'placement', '2026-06-06', NULL, 'published', '2026-06-21 15:24:33'),
('66e46612-f886-4d8c-86ba-3e0533eadc2f', 'indian-institute-of-technology-delhi-admission-2026-open', 'col-iitd-0002', 'Indian Institute of Technology Delhi Admission 2026 Open', 'Applications for Indian Institute of Technology Delhi are now open for the academic session 2026-27. Interested candidates must appear for the relevant entrance examination and apply through the official counselling process.', 'admission', '2026-07-21', NULL, 'published', '2026-06-21 15:24:33'),
('83d3efbc-fdf7-4e82-9645-4007cad728bf', 'indian-institute-of-technology-bombay-placement-season-2025-record-breaking-numb', 'col-iitb-0001', 'Indian Institute of Technology Bombay Placement Season 2025 - Record Breaking Numbers', 'Indian Institute of Technology Bombay has concluded its 2025 placement season with outstanding results. The institute achieved over 90% placement with top packages exceeding INR 1 Crore per annum. Leading companies from various sectors participated in the campus recruitment drive.', 'placement', '2026-06-06', NULL, 'published', '2026-06-21 15:24:33'),
('92055db1-4d05-456e-93aa-954d5501210c', 'birla-institute-of-technology-and-science-pilani-admission-2026-open', 'col-bits-0008', 'Birla Institute of Technology and Science Pilani Admission 2026 Open', 'Applications for Birla Institute of Technology and Science Pilani are now open for the academic session 2026-27. Interested candidates must appear for the relevant entrance examination and apply through the official counselling process.', 'admission', '2026-07-21', NULL, 'published', '2026-06-21 15:24:33'),
('9ded0f6a-d009-4023-9857-5ac96c65a55b', 'national-law-school-of-india-university-bangalore-placement-season-2025-record-b', 'col-nlsiu-0012', 'National Law School of India University Bangalore Placement Season 2025 - Record Breaking Numbers', 'National Law School of India University Bangalore has concluded its 2025 placement season with outstanding results. The institute achieved over 90% placement with top packages exceeding INR 1 Crore per annum. Leading companies from various sectors participated in the campus recruitment drive.', 'placement', '2026-06-06', NULL, 'published', '2026-06-21 15:24:33'),
('9ecaf25a-70c9-4db5-a49b-a6e17097d51f', 'birla-institute-of-technology-and-science-pilani-placement-season-2025-record-br', 'col-bits-0008', 'Birla Institute of Technology and Science Pilani Placement Season 2025 - Record Breaking Numbers', 'Birla Institute of Technology and Science Pilani has concluded its 2025 placement season with outstanding results. The institute achieved over 90% placement with top packages exceeding INR 1 Crore per annum. Leading companies from various sectors participated in the campus recruitment drive.', 'placement', '2026-06-06', NULL, 'published', '2026-06-21 15:24:33'),
('b1e5f5aa-a21b-4cd0-a371-2e303f01dda8', 'indian-institute-of-management-ahmedabad-placement-season-2025-record-breaking-n', 'col-iima-0009', 'Indian Institute of Management Ahmedabad Placement Season 2025 - Record Breaking Numbers', 'Indian Institute of Management Ahmedabad has concluded its 2025 placement season with outstanding results. The institute achieved over 90% placement with top packages exceeding INR 1 Crore per annum. Leading companies from various sectors participated in the campus recruitment drive.', 'placement', '2026-06-06', NULL, 'published', '2026-06-21 15:24:33'),
('b53164fe-809e-4848-8a21-5eda54b301bb', 'indian-institute-of-management-bangalore-placement-season-2025-record-breaking-n', 'col-iimb-0010', 'Indian Institute of Management Bangalore Placement Season 2025 - Record Breaking Numbers', 'Indian Institute of Management Bangalore has concluded its 2025 placement season with outstanding results. The institute achieved over 90% placement with top packages exceeding INR 1 Crore per annum. Leading companies from various sectors participated in the campus recruitment drive.', 'placement', '2026-06-06', NULL, 'published', '2026-06-21 15:24:33'),
('bec032a6-7de8-4066-bd2d-3924f171a9c2', 'indian-institute-of-technology-kanpur-placement-season-2025-record-breaking-numb', 'col-iitk-0004', 'Indian Institute of Technology Kanpur Placement Season 2025 - Record Breaking Numbers', 'Indian Institute of Technology Kanpur has concluded its 2025 placement season with outstanding results. The institute achieved over 90% placement with top packages exceeding INR 1 Crore per annum. Leading companies from various sectors participated in the campus recruitment drive.', 'placement', '2026-06-06', NULL, 'published', '2026-06-21 15:24:33'),
('c52d6f68-15c3-4b41-8228-141f3e1baed7', 'indian-institute-of-technology-delhi-placement-season-2025-record-breaking-numbe', 'col-iitd-0002', 'Indian Institute of Technology Delhi Placement Season 2025 - Record Breaking Numbers', 'Indian Institute of Technology Delhi has concluded its 2025 placement season with outstanding results. The institute achieved over 90% placement with top packages exceeding INR 1 Crore per annum. Leading companies from various sectors participated in the campus recruitment drive.', 'placement', '2026-06-06', NULL, 'published', '2026-06-21 15:24:33'),
('c58cb358-817a-4ec4-976f-497f717c25ff', 'indian-institute-of-management-ahmedabad-admission-2026-open', 'col-iima-0009', 'Indian Institute of Management Ahmedabad Admission 2026 Open', 'Applications for Indian Institute of Management Ahmedabad are now open for the academic session 2026-27. Interested candidates must appear for the relevant entrance examination and apply through the official counselling process.', 'admission', '2026-07-21', NULL, 'published', '2026-06-21 15:24:33'),
('ce965f90-1ecd-4620-ab5f-d7493a2f47d7', 'indian-institute-of-technology-kharagpur-admission-2026-open', 'col-iitkgp-0005', 'Indian Institute of Technology Kharagpur Admission 2026 Open', 'Applications for Indian Institute of Technology Kharagpur are now open for the academic session 2026-27. Interested candidates must appear for the relevant entrance examination and apply through the official counselling process.', 'admission', '2026-07-21', NULL, 'published', '2026-06-21 15:24:33'),
('d38665dc-6ce2-42ae-bcb9-e845ff9a92e3', 'ghcgfytftdrtcyt-1782646141', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'ghcgfytftdrtcyt', '<p>mh nfvh jghyu hjmgbrtgf vtyhjym tyhfvf cfjghf vutjh</p>', 'admission', '2026-06-25', NULL, 'published', '2026-06-28 11:29:01'),
('d9ace2ac-8bcb-4b34-a1b8-1702c6b42a14', 'national-institute-of-technology-surathkal-placement-season-2025-record-breaking', 'col-nitk-0007', 'National Institute of Technology Surathkal Placement Season 2025 - Record Breaking Numbers', 'National Institute of Technology Surathkal has concluded its 2025 placement season with outstanding results. The institute achieved over 90% placement with top packages exceeding INR 1 Crore per annum. Leading companies from various sectors participated in the campus recruitment drive.', 'placement', '2026-06-06', NULL, 'published', '2026-06-21 15:24:33'),
('fb1ca3e6-14d6-4e88-a11f-ada8ce4cb522', 'indian-institute-of-management-bangalore-admission-2026-open', 'col-iimb-0010', 'Indian Institute of Management Bangalore Admission 2026 Open', 'Applications for Indian Institute of Management Bangalore are now open for the academic session 2026-27. Interested candidates must appear for the relevant entrance examination and apply through the official counselling process.', 'admission', '2026-07-21', NULL, 'published', '2026-06-21 15:24:33');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` varchar(36) NOT NULL,
  `answer_id` varchar(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `comment_text` text NOT NULL,
  `like_count` int(11) DEFAULT 0,
  `dislike_count` int(11) DEFAULT 0,
  `status` enum('active','hidden','removed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `answer_id`, `user_id`, `comment_text`, `like_count`, `dislike_count`, `status`, `created_at`) VALUES
('07539fb3-3bc6-434a-b379-0a945229a241', 'ans00001-0000-0000-0000-000000000001', '64e20c70-d8d7-402f-a700-53c759a659d4', 'bhb jnnv vhvhvh', 0, 0, 'active', '2026-06-22 10:33:13'),
('259a85c4-8d69-4b70-b835-46002f714de7', 'ans00001-0000-0000-0000-000000000001', '64e20c70-d8d7-402f-a700-53c759a659d4', 'cgbcxgxfgc', 1, 0, 'active', '2026-06-22 10:32:54');

-- --------------------------------------------------------

--
-- Table structure for table `comment_votes`
--

CREATE TABLE `comment_votes` (
  `id` varchar(36) NOT NULL,
  `comment_id` varchar(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `vote_type` enum('like','dislike') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comment_votes`
--

INSERT INTO `comment_votes` (`id`, `comment_id`, `user_id`, `vote_type`, `created_at`) VALUES
('52b30b61-22be-4d24-ab27-b06cc7605766', '259a85c4-8d69-4b70-b835-46002f714de7', '64e20c70-d8d7-402f-a700-53c759a659d4', 'like', '2026-06-22 10:32:58');

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

--
-- Triggers `commissions`
--
DELIMITER $$
CREATE TRIGGER `trg_commissions_after_insert` AFTER INSERT ON `commissions` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'commissions', NEW.id, NULL,
        JSON_OBJECT('college_id', NEW.college_id, 'commission_earned', NEW.commission_earned, 'commission_status', NEW.commission_status),
        NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_commissions_after_update` AFTER UPDATE ON `commissions` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'commissions', NEW.id,
        JSON_OBJECT('commission_status', OLD.commission_status),
        JSON_OBJECT('commission_status', NEW.commission_status),
        NULL, NOW());
END
$$
DELIMITER ;

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

--
-- Dumping data for table `consultants`
--

INSERT INTO `consultants` (`id`, `consultant_name`, `slug`, `profile_picture`, `consultant_rating`, `verified_consultant`, `specialization_countries`, `fee_range`, `logo_url`, `contact_email`, `contact_phone`, `address`, `city`, `experience_years`, `specializations`, `consultation_mode`, `success_rate_percent`, `created_at`, `updated_at`, `office_location`, `languages_spoken`, `website_url`, `bio`) VALUES
(2, 'IDP Education', 'idp-education', NULL, 4.6, 1, '[\"Australia\",\"Canada\",\"UK\",\"USA\",\"New Zealand\"]', 'INR 5000 - 15000', NULL, 'info@idp.com', '+91-124-4514514', NULL, 'New Delhi', 30, 'Study Abroad, Visa Assistance, University Selection', 'Both', 95, '2026-06-21 08:42:27', '2026-06-21 08:42:27', 'Nehru Place, New Delhi', 'English, Hindi', 'https://www.idp.com', 'IDP Education is a global leader in international student placement with offices in 50+ countries.'),
(3, 'British Council', 'british-council', NULL, 4.7, 1, '[\"UK\"]', 'Free', NULL, ' enquiries@britishcouncil.org', '+91-124-4518100', NULL, 'New Delhi', 85, 'Study in UK, IELTS, Scholarships', 'Both', 92, '2026-06-21 08:42:27', '2026-06-21 08:42:27', 'Barakhamba Road, New Delhi', 'English, Hindi', 'https://www.britishcouncil.in', 'The UKs international cultural relations organization helping students study in the UK.'),
(4, 'Shiksha Study Abroad', 'shiksha-abroad', NULL, 4.4, 1, '[\"USA\",\"UK\",\"Canada\",\"Australia\",\"Germany\",\"Singapore\"]', 'INR 0 (Free Counseling)', NULL, 'counsel@shiksha.com', '+91-9876543210', NULL, 'Gurgaon', 15, 'University Selection, Application, SOP Review, Visa', 'Online', 88, '2026-06-21 08:42:27', '2026-06-21 08:42:27', 'Cyber City, Gurgaon', 'English, Hindi', 'https://www.shiksha.com/abroad', 'Shiksha is Indias largest education marketplace with free counseling services.'),
(5, 'Study Abroad Counselling Service', 'sacs-mumbai', NULL, 4.3, 1, '[\"USA\",\"UK\",\"Canada\",\"Australia\"]', 'INR 8000 - 20000', NULL, 'info@sacs.com', '+91-22-45678900', NULL, 'Mumbai', 12, 'MBA Abroad, MS Abroad, PhD Abroad', 'Both', 85, '2026-06-21 08:42:27', '2026-06-21 08:42:27', 'Andheri West, Mumbai', 'English, Hindi, Marathi', 'https://www.sacs.in', 'Specialized counseling for graduate admissions to top universities worldwide.'),
(6, 'Educomp Study Abroad', 'educomp-abroad', NULL, 4.2, 0, '[\"USA\",\"Canada\",\"Australia\"]', 'INR 10000 - 25000', NULL, 'abroad@educomp.com', '+91-11-45678901', NULL, 'New Delhi', 18, 'Undergraduate, Graduate, Test Prep (SAT, GRE, GMAT)', 'Both', 82, '2026-06-21 08:42:27', '2026-06-21 08:42:27', 'Noida, UP', 'English, Hindi', 'https://www.educomp.com', 'Educomp provides end-to-end solutions for students planning to study abroad.'),
(7, 'Global Study Partners', 'gsp-hyderabad', NULL, 4.5, 1, '[\"USA\",\"UK\",\"Canada\",\"Germany\"]', 'INR 5000 - 12000', NULL, 'info@gsp.in', '+91-40-45678902', NULL, 'Hyderabad', 10, 'Engineering, Computer Science, Business Schools', 'Online', 90, '2026-06-21 08:42:27', '2026-06-21 08:42:27', 'Banjara Hills, Hyderabad', 'English, Hindi, Telugu', 'https://www.gsp.in', 'GSP connects Indian students with partner universities for direct admissions.'),
(8, 'Meridian Global Studies', 'meridian-pune', NULL, 4.1, 0, '[\"UK\",\"Australia\",\"New Zealand\",\"Ireland\"]', 'INR 7000 - 18000', NULL, 'contact@meridian.com', '+91-20-45678903', NULL, 'Pune', 8, 'Foundation Programs, Pathway Programs, Undergraduate', 'Both', 80, '2026-06-21 08:42:27', '2026-06-21 08:42:27', 'Kothrud, Pune', 'English, Hindi, Marathi', 'https://www.meridian-studies.com', 'Meridian specializes in pathway and foundation programs for international students.'),
(9, 'Canam Consultants', 'canam', NULL, 4, 1, '[\"Canada\",\"USA\",\"UK\",\"Australia\",\"Singapore\"]', 'INR 3000 - 10000', NULL, 'info@canam.com', '+91-172-45678904', NULL, 'Chandigarh', 28, 'Student Visa, Immigration, Study Permits', 'Both', 87, '2026-06-21 08:42:27', '2026-06-21 08:42:27', 'Sector 17, Chandigarh', 'English, Hindi, Punjabi', 'https://www.canamgroup.com', 'Canam is one of Indias largest overseas education and immigration consultancies.'),
(10, 'The Chopras', 'the-chopras', NULL, 4.3, 1, '[\"USA\",\"UK\",\"Canada\",\"Germany\",\"Singapore\"]', 'Free Counseling', NULL, 'query@thechopras.com', '+91-11-45678905', NULL, 'New Delhi', 35, 'End-to-End Admission, Scholarships, Test Prep', 'Both', 91, '2026-06-21 08:42:27', '2026-06-21 08:42:27', 'South Delhi', 'English, Hindi', 'https://www.thechopras.com', 'The Chopras have been guiding students to global universities since 1995.'),
(11, 'Manya Education', 'manya-education', NULL, 4.2, 0, '[\"USA\",\"UK\",\"Canada\",\"Australia\"]', 'INR 5000 - 15000', NULL, 'info@manyagroup.com', '+91-11-45678906', NULL, 'New Delhi', 20, 'GRE, GMAT, SAT, TOEFL, IELTS, Study Abroad', 'Both', 84, '2026-06-21 08:42:27', '2026-06-21 08:42:27', 'CP, New Delhi', 'English, Hindi', 'https://www.manyagroup.com', 'Manya Education is a leading test prep and study abroad counseling organization.');

--
-- Triggers `consultants`
--
DELIMITER $$
CREATE TRIGGER `trg_consultants_after_delete` AFTER DELETE ON `consultants` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'consultants', OLD.id,
        JSON_OBJECT('consultant_name', OLD.consultant_name), NULL, NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_consultants_after_insert` AFTER INSERT ON `consultants` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'consultants', NEW.id, NULL,
        JSON_OBJECT('consultant_name', NEW.consultant_name, 'contact_email', NEW.contact_email),
        NULL, NOW());
END
$$
DELIMITER ;

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
('', 'B.Sc - Bachelor of Science', 'b-sc', 'UG', 'Science', NULL, 4, NULL, NULL, NULL, NULL, 4.50, NULL, NULL, 1, 3200, 'active', '2026-06-23 08:40:58', '2026-06-23 08:40:58'),
('1100a8b9-4378-4e1a-b770-415491f91a81', 'BA English (Hons)', 'ba-english-hons', 'UG', 'Arts', NULL, 3, NULL, NULL, NULL, NULL, 2.80, NULL, NULL, 1, 1200, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('1df6aa3c-774f-40c1-8d29-ded243b55737', 'B.Tech Mechanical', 'btech-me-hons', 'UG', 'Engineering', NULL, 4, NULL, NULL, NULL, NULL, 5.50, NULL, NULL, 1, 2500, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('1f096a2b-5a1b-483b-8e36-f19a5b05313e', 'B.Com', 'bcom-hons2', 'UG', 'Commerce', NULL, 3, NULL, NULL, NULL, NULL, 3.00, NULL, NULL, 1, 5000, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('2cb75968-88a0-4597-8bc3-4f7d2612946e', 'BA Psychology (Hons)', 'ba-psych-hons', 'UG', 'Arts', NULL, 3, NULL, NULL, NULL, NULL, 3.00, NULL, NULL, 1, 900, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('2cee5a0b-ed15-489f-82cb-5b93079aecdf', 'B.Sc Biology (Hons)', 'bsc-bio-hons', 'UG', 'Science', NULL, 3, NULL, NULL, NULL, NULL, 3.20, NULL, NULL, 1, 650, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('376617e0-ba4e-48a2-87d5-eb3241f58efd', 'B.Sc Environmental Science', 'bsc-env-sci', 'UG', 'Science', NULL, 3, NULL, NULL, NULL, NULL, 3.00, NULL, NULL, 1, 300, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('44edbcdb-3116-48d4-ae1c-31bb400000e8', 'PGDM', 'pgdm', 'PG', 'Management', NULL, 2, NULL, NULL, NULL, NULL, 7.00, NULL, NULL, 1, 1500, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('48df5db5-d5c9-49e8-82ef-e52fa4923aac', 'B.Pharm', 'bpharm', 'UG', 'Medical', NULL, 4, NULL, NULL, NULL, NULL, 4.50, NULL, NULL, 1, 900, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('4d8181dd-b269-434b-8978-04cd3479f5a0', 'B.Tech IT', 'btech-it-hons', 'UG', 'IT & Software', NULL, 4, NULL, NULL, NULL, NULL, 7.00, NULL, NULL, 1, 2000, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('51757683-ed73-446c-adc7-ce1dea892f19', 'BDS', 'bds', 'UG', 'Medical', NULL, 5, NULL, NULL, NULL, NULL, 5.00, NULL, NULL, 1, 400, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('5961f4d3-0ac0-479a-84e4-5c910d1fc284', 'BA History (Hons)', 'ba-history-hons', 'UG', 'Arts', NULL, 3, NULL, NULL, NULL, NULL, 2.50, NULL, NULL, 1, 800, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('5a1cc230-be02-4fb3-9b74-9b2f95cb5804', 'CA', 'ca-course', 'UG', 'Commerce', NULL, 3, NULL, NULL, NULL, NULL, 8.00, NULL, NULL, 1, 2000, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('5b79824c-d2a5-40b9-a29b-122507eb3a5c', 'M.Sc - Master of Science', 'msc', 'PG', 'Science', NULL, 2, NULL, NULL, NULL, NULL, 6.00, NULL, NULL, 1, 1800, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('619d105e-726a-420a-a988-6533f7e92982', 'M.Pharm', 'mpharm', 'PG', 'Medical', NULL, 2, NULL, NULL, NULL, NULL, 5.50, NULL, NULL, 1, 400, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('68a6ed8d-ac36-4d06-97e7-ef33c8de4070', 'B.Tech Civil', 'btech-ce-hons', 'UG', 'Engineering', NULL, 4, NULL, NULL, NULL, NULL, 5.00, NULL, NULL, 1, 2200, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('719439e1-2e21-4f95-8c9e-a48a336ff1d3', 'BMS', 'bms', 'UG', 'Management', NULL, 3, NULL, NULL, NULL, NULL, 4.00, NULL, NULL, 1, 1800, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('72d739b6-a1d3-45b6-9b87-efad30e3e469', 'BA Economics (Hons)', 'ba-eco-hons', 'UG', 'Arts', NULL, 3, NULL, NULL, NULL, NULL, 3.50, NULL, NULL, 1, 1100, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('765fd0f6-336b-4465-901d-1e6f43022790', 'M.Des', 'mdes', 'PG', 'Design', NULL, 2, NULL, NULL, NULL, NULL, 6.00, NULL, NULL, 1, 250, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('76b5fb2b-7f1d-425e-9d69-5af62dab35a0', 'MA - Master of Arts', 'ma', 'PG', 'Arts', NULL, 2, NULL, NULL, NULL, NULL, 3.50, NULL, NULL, 1, 2000, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('7cd16b09-b98a-41b3-8c28-ab8b04ad3032', 'GNM Nursing', 'gnm', 'UG', 'Nursing', NULL, 3, NULL, NULL, NULL, NULL, 3.00, NULL, NULL, 1, 800, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('8573ed2a-7bf9-4a23-b817-16922168c0ad', 'BA - Bachelor of Arts', 'ba', 'UG', 'Arts', NULL, 3, NULL, NULL, NULL, NULL, 2.50, NULL, NULL, 1, 4500, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('86a83eaa-1875-4a13-b2ad-c0dbe89fd31a', 'B.Tech AI & Data Science', 'btech-ai-ds', 'UG', 'Engineering', NULL, 4, NULL, NULL, NULL, NULL, 9.00, NULL, NULL, 1, 1500, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('91d0d996-f1c6-48d8-8943-7eb15a389ae2', 'B.Tech CSE', 'btech-cse-hons', 'UG', 'Engineering', NULL, 4, NULL, NULL, NULL, NULL, 8.00, NULL, NULL, 1, 4000, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('960613be-e6b6-425d-9bc9-7d1414c1cac1', 'M.Tech', 'mtech', 'PG', 'Engineering', NULL, 2, NULL, NULL, NULL, NULL, 7.50, NULL, NULL, 1, 1200, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('96aead16-877a-4011-a3ae-683afdf4638d', 'B.Sc Mathematics (Hons)', 'bsc-math-hons', 'UG', 'Science', NULL, 3, NULL, NULL, NULL, NULL, 3.80, NULL, NULL, 1, 700, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('99516d1b-c069-44e2-be14-bbd4847d29de', 'B.Arch', 'barch', 'UG', 'Design', NULL, 5, NULL, NULL, NULL, NULL, 5.50, NULL, NULL, 1, 500, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('add1b85c-f65c-4047-94e0-f1a940afaff9', 'MD - Doctor of Medicine', 'md', 'PG', 'Medical', NULL, 3, NULL, NULL, NULL, NULL, 10.00, NULL, NULL, 1, 500, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('b76f1356-78ef-4a0f-a759-7de53283fe12', 'BAMS - Ayurveda', 'bams', 'UG', 'Medical', NULL, 5, NULL, NULL, NULL, NULL, 4.00, NULL, NULL, 1, 600, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('c3eaba4f-d87a-42e5-8a39-e1e4187eda98', 'BBA LLB', 'bba-llb', 'Integrated', 'Law', NULL, 5, NULL, NULL, NULL, NULL, 5.00, NULL, NULL, 1, 600, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('c669bcaa-c4be-41cd-a79d-fe61beb13392', 'B.Sc Physics (Hons)', 'bsc-physics-hons', 'UG', 'Science', NULL, 3, NULL, NULL, NULL, NULL, 3.50, NULL, NULL, 1, 800, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('crs-ba-llb-09', 'BA LLB - Bachelor of Arts and Bachelor of Legislative Law', 'ba-llb', 'Integrated', 'Law', 'cat-law-01', 5, 'BA LLB is a 5-year integrated dual-degree program that combines liberal arts education with legal studies. Students earn both a Bachelor of Arts degree and a Bachelor of Legislative Law degree upon completion. The program provides a strong foundation in humanities subjects (political science, history, sociology, economics) alongside comprehensive legal education. It covers constitutional law, criminal law, civil law, corporate law, international law, and environmental law, along with practical training through moot courts and legal internships.', '10+2 from any recognized stream with a minimum of 45-50% aggregate marks (40-45% for reserved categories). Admission is through national-level CLAT (Common Law Admission Test) for NLUs, AILET for NLU Delhi, LSAT India, or state-level law entrance exams like MH CET Law, AP LAWCET, TS LAWCET. Some private universities conduct their own entrance exams.', 'BA LLB graduates have excellent career opportunities in the legal profession. They can practice as Advocates, join corporate legal departments, work in law firms, or appear for judicial services exams. Other options include legal journalism, legal process outsourcing, arbitration, and mediation. Many graduates pursue LLM for specialization or appear for UPSC/State PSC exams. Average starting salary ranges from ₹4-8 LPA, with experienced lawyers at top firms earning ₹15-50+ LPA.', '[{\"name\":\"Cyril Amarchand Mangaldas\",\"logo\":\"\"},{\"name\":\"Khaitan & Co\",\"logo\":\"\"},{\"name\":\"Trilegal\",\"logo\":\"\"},{\"name\":\"AZB & Partners\",\"logo\":\"\"},{\"name\":\"J Sagar Associates\",\"logo\":\"\"},{\"name\":\"Luthra & Luthra\",\"logo\":\"\"},{\"name\":\"Nishith Desai Associates\",\"logo\":\"\"},{\"name\":\"Shardul Amarchand Mangaldas\",\"logo\":\"\"},{\"name\":\"S&R Associates\",\"logo\":\"\"},{\"name\":\"Crawford Bayley & Co\",\"logo\":\"\"}]', 6.50, 3.50, 30.00, 1, 700, 'active', '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('crs-bba-07', 'BBA - Bachelor of Business Administration', 'bba', 'UG', 'Management', 'cat-mgt-02', 3, 'BBA (Bachelor of Business Administration) is a 3-year undergraduate program that provides foundational knowledge in business management, entrepreneurship, marketing, finance, and human resource management. It is designed to develop managerial and leadership skills in students. The curriculum covers principles of management, business communication, financial accounting, marketing management, organizational behavior, business law, and strategic management. Many BBA programs also include internships, industry projects, and personality development modules.', '10+2 from any recognized board with a minimum of 50% aggregate marks. Some colleges require English as a compulsory subject. Admission is through entrance exams like IPMAT (IIM Indore/Ranchi), DU JAT, AIMA UGAT, NPAT, or merit-based selection. Top colleges include IIM Indore (IPM), NMIMS, Symbiosis, Christ University, and Amity.', 'BBA graduates can start careers in marketing, sales, human resources, operations, and business development. They can pursue MBA for advanced career growth or join family businesses. Career roles include Marketing Executive, Sales Executive, HR Executive, Business Development Executive, Operations Analyst, and Management Trainee. Many BBA graduates also prepare for competitive exams like CAT, XAT for MBA admissions. Average starting salary ranges from ₹3-6 LPA.', '[{\"name\":\"Amazon\",\"logo\":\"\"},{\"name\":\"Deloitte\",\"logo\":\"\"},{\"name\":\"KPMG\",\"logo\":\"\"},{\"name\":\"EY\",\"logo\":\"\"},{\"name\":\"Wipro\",\"logo\":\"\"},{\"name\":\"HCL Technologies\",\"logo\":\"\"},{\"name\":\"Aditya Birla Group\",\"logo\":\"\"},{\"name\":\"ITC Limited\",\"logo\":\"\"},{\"name\":\"Nestle India\",\"logo\":\"\"},{\"name\":\"Asian Paints\",\"logo\":\"\"}]', 4.50, 2.50, 18.00, 1, 2200, 'active', '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('crs-bca-04', 'BCA - Bachelor of Computer Applications', 'bca', 'UG', 'IT & Software', 'cat-it-01', 3, 'BCA (Bachelor of Computer Applications) is a 3-year undergraduate program that provides a strong foundation in computer science, programming languages, database management, networking, and web development. It is an excellent alternative to B.Tech for students interested in building a career in the IT industry. The curriculum covers programming in C, C++, Java, Python, web technologies (HTML, CSS, JavaScript, React), database systems (MySQL, MongoDB), operating systems, and software engineering principles.', '10+2 from a recognized board with a minimum of 50% aggregate marks. Mathematics as a subject in 12th grade is preferred (and required by some universities). Some colleges conduct their own entrance exams while others offer direct admission based on merit. Universities like IGNOU, Symbiosis, Christ University, and SRM have specific admission processes.', 'BCA graduates can work as Software Developers, Web Developers, System Analysts, Database Administrators, Network Engineers, and IT Support Specialists. The IT industry offers excellent growth opportunities with companies like TCS, Infosys, Wipro, and tech startups regularly hiring BCA graduates. Pursuing MCA after BCA significantly enhances career prospects and salary potential. Average starting salary ranges from ₹3-6 LPA, with experienced professionals earning ₹10-20+ LPA.', '[{\"name\":\"Tata Consultancy Services\",\"logo\":\"\"},{\"name\":\"Wipro\",\"logo\":\"\"},{\"name\":\"Infosys\",\"logo\":\"\"},{\"name\":\"Tech Mahindra\",\"logo\":\"\"},{\"name\":\"Capgemini\",\"logo\":\"\"},{\"name\":\"Cognizant\",\"logo\":\"\"},{\"name\":\"HCL Technologies\",\"logo\":\"\"},{\"name\":\"Mindtree\",\"logo\":\"\"},{\"name\":\"Mphasis\",\"logo\":\"\"},{\"name\":\"LTIMindtree\",\"logo\":\"\"}]', 4.50, 2.50, 15.00, 1, 1200, 'active', '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('crs-bcom-06', 'B.Com (Hons) - Bachelor of Commerce (Honours)', 'bcom-hons', 'UG', 'Commerce', 'cat-com-01', 3, 'B.Com (Hons) is a 3-year undergraduate degree program that provides advanced knowledge in commerce, accounting, finance, economics, and business law. It is one of the most popular choices for students aspiring to careers in chartered accountancy, company secretaryship, banking, financial services, and business management. The curriculum covers financial accounting, cost accounting, taxation, auditing, business economics, corporate law, and management accounting.', '10+2 from a recognized board with Commerce stream (Mathematics/Accountancy/Economics as subjects) with a minimum of 50-60% aggregate marks. Some universities accept students from all streams. Admission is through merit-based cutoffs (Delhi University, Mumbai University) or entrance exams (IPU CET, BHU UET, Christ University).', 'B.Com (Hons) graduates can pursue careers as Chartered Accountants (CA), Company Secretaries (CS), Cost and Management Accountants (CMA), Financial Analysts, Bankers, Tax Consultants, and Auditors. They can also join government services through SSC, UPSC, or banking exams. Many graduates pursue MBA or M.Com for advanced career opportunities. Average starting salary ranges from ₹3-6 LPA, with CA/CFA qualified professionals earning ₹8-25+ LPA.', '[{\"name\":\"Deloitte\",\"logo\":\"\"},{\"name\":\"PwC India\",\"logo\":\"\"},{\"name\":\"EY India\",\"logo\":\"\"},{\"name\":\"KPMG India\",\"logo\":\"\"},{\"name\":\"ICICI Bank\",\"logo\":\"\"},{\"name\":\"HDFC Bank\",\"logo\":\"\"},{\"name\":\"SBI\",\"logo\":\"\"},{\"name\":\"Axis Bank\",\"logo\":\"\"},{\"name\":\"Grant Thornton\",\"logo\":\"\"},{\"name\":\"BDO India\",\"logo\":\"\"}]', 5.00, 2.50, 20.00, 1, 3000, 'active', '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('crs-bdes-10', 'B.Des - Bachelor of Design', 'bdes', 'UG', 'Design', 'cat-des-01', 4, 'B.Des (Bachelor of Design) is a 4-year undergraduate program that develops creative and analytical skills in design thinking, visual communication, product design, UI/UX design, fashion design, interior design, and industrial design. The curriculum covers design fundamentals, sketching, color theory, typography, user research, prototyping, materials science, and design management. Students work on live projects, portfolio development, and industry internships throughout the program.', '10+2 from any recognized board with a minimum of 50% aggregate marks. Admission is through national-level entrance exams like NIFT Entrance Exam, UCEED (IIT Bombay), NID DAT, AIEED, or state-level design exams. Portfolio review and design aptitude tests are also part of the selection process at many institutions.', 'Design professionals are in high demand across industries including technology, fashion, automotive, architecture, advertising, and media. Career options include UI/UX Designer, Product Designer, Graphic Designer, Fashion Designer, Interior Designer, Industrial Designer, and Design Manager. Top employers include design studios, IT companies, fashion brands, automotive companies, and e-commerce platforms. Average starting salary ranges from ₹4-8 LPA, with experienced designers earning ₹15-35+ LPA.', '[{\"name\":\"IDEO\",\"logo\":\"\"},{\"name\":\"Frog Design\",\"logo\":\"\"},{\"name\":\"Tata Group\",\"logo\":\"\"},{\"name\":\"Infosys\",\"logo\":\"\"},{\"name\":\"Wipro\",\"logo\":\"\"},{\"name\":\"Godrej\",\"logo\":\"\"},{\"name\":\"Flipkart\",\"logo\":\"\"},{\"name\":\"Amazon India\",\"logo\":\"\"},{\"name\":\"Ola\",\"logo\":\"\"},{\"name\":\"Zomato\",\"logo\":\"\"}]', 7.00, 3.50, 30.00, 1, 500, 'active', '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('crs-btech-01', 'B.Tech - Bachelor of Technology', 'btech', 'UG', 'Engineering', 'cat-eng-01', 4, 'B.Tech (Bachelor of Technology) is a 4-year undergraduate professional degree program in engineering and technology. It is one of the most popular and sought-after courses in India, offered by thousands of engineering colleges including IITs, NITs, and private institutions. The program provides a strong foundation in technical and analytical skills, preparing students for careers in software development, hardware engineering, civil infrastructure, electronics, and emerging technologies like AI, data science, and cybersecurity.', '10+2 with Physics, Chemistry, and Mathematics (PCM) with a minimum of 60% aggregate marks (50% for reserved categories). Admission is primarily through national-level entrance exams like JEE Main, JEE Advanced, state-level CETs, and university-specific exams. Some private colleges also accept SAT scores.', 'B.Tech graduates have excellent career prospects across diverse sectors. The IT industry remains the largest recruiter, hiring software engineers, full-stack developers, data scientists, and AI/ML engineers. Core engineering sectors like manufacturing, automotive, construction, and energy also offer significant opportunities. Graduates can pursue higher studies (M.Tech, MS abroad), appear for GATE/IES, or start their own tech ventures. Average starting salary ranges from ₹4-8 LPA, with top performers at premium institutions earning ₹20-50+ LPA.', '[{\"name\":\"Tata Consultancy Services\",\"logo\":\"\"},{\"name\":\"Infosys\",\"logo\":\"\"},{\"name\":\"Wipro\",\"logo\":\"\"},{\"name\":\"Microsoft India\",\"logo\":\"\"},{\"name\":\"Google India\",\"logo\":\"\"},{\"name\":\"Larsen & Toubro\",\"logo\":\"\"},{\"name\":\"Tata Motors\",\"logo\":\"\"},{\"name\":\"Samsung India\",\"logo\":\"\"},{\"name\":\"Amazon India\",\"logo\":\"\"},{\"name\":\"Flipkart\",\"logo\":\"\"}]', 8.50, 4.00, 45.00, 1, 3500, 'active', '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('crs-llb-05', 'LLB - Bachelor of Legislative Law', 'llb', 'UG', 'Law', 'cat-law-01', 3, 'LLB (Bachelor of Legislative Law) is a 3-year undergraduate degree in law that provides comprehensive knowledge of the Indian legal system, constitutional law, criminal law, civil law, corporate law, and international law. The program trains students in legal research, drafting, advocacy, and analytical thinking. Students learn about the Indian Penal Code, Code of Criminal Procedure, Code of Civil Procedure, Evidence Act, Contract Act, and various other statutes. Moot courts, legal aid clinics, and internships with law firms are integral parts of the curriculum.', 'Graduation in any discipline from a recognized university with a minimum of 45-50% aggregate marks (40-45% for reserved categories). Admission is through entrance exams like DU LLB Entrance, MH CET Law, LSAT India, BHU UET, AP LAWCET, TS LAWCET, and Christ University Law Entrance. Some universities also offer merit-based admission.', 'LLB graduates have diverse career opportunities in the legal profession. They can practice as Advocates in High Courts and Supreme Court, become Legal Advisors for corporations, join the Judiciary through competitive exams, or work in government legal departments. Other career paths include Corporate Law firms, Legal Process Outsourcing (LPO), Arbitration and Mediation, Legal Journalism, Academia and Law Teaching, Public Interest Litigation, and Civil Services (IAS/IPS through UPSC). Average starting salary ranges from ₹3-8 LPA, with experienced lawyers at top firms earning ₹20-50+ LPA.', '[{\"name\":\"Cyril Amarchand Mangaldas\",\"logo\":\"\"},{\"name\":\"Khaitan & Co\",\"logo\":\"\"},{\"name\":\"Shardul Amarchand Mangaldas\",\"logo\":\"\"},{\"name\":\"Trilegal\",\"logo\":\"\"},{\"name\":\"AZB & Partners\",\"logo\":\"\"},{\"name\":\"J Sagar Associates\",\"logo\":\"\"},{\"name\":\"Luthra & Luthra\",\"logo\":\"\"},{\"name\":\"S&R Associates\",\"logo\":\"\"},{\"name\":\"Nishith Desai Associates\",\"logo\":\"\"},{\"name\":\"Crawford Bayley & Co\",\"logo\":\"\"}]', 6.00, 3.50, 25.00, 1, 850, 'active', '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('crs-mba-02', 'MBA - Master of Business Administration', 'mba', 'PG', 'Management', 'cat-mgt-02', 2, 'MBA (Master of Business Administration) is a 2-year postgraduate program that provides comprehensive knowledge of business management, leadership, and organizational strategy. It covers core areas including finance, marketing, human resources, operations, and entrepreneurship. MBA graduates are equipped with analytical, managerial, and decision-making skills essential for leadership roles in corporate organizations, startups, consulting firms, and multinational companies. The program also offers excellent networking opportunities through peer learning and industry connections.', 'Bachelor\'s degree in any discipline from a recognized university with a minimum of 50% aggregate marks (45% for reserved categories). Final year students can also apply. Admission is through national-level entrance exams like CAT, XAT, MAT, CMAT, ATMA, GMAT, or state-level exams like KMAT, TANCET. Some institutes conduct their own admission processes including GD, PI, and WAT.', 'MBA graduates are in high demand across all sectors of the economy. Top recruiters include consulting firms (McKinsey, BCG, Deloitte), FMCG companies (HUL, P&G, ITC), banking and finance (Goldman Sachs, HDFC Bank, ICICI), e-commerce (Amazon, Flipkart), and technology companies (Google, Microsoft). Career roles include Business Analyst, Marketing Manager, Financial Analyst, HR Manager, Product Manager, Operations Manager, and Management Consultant. Entrepreneurs leverage MBA skills to launch successful startups. Average starting salary ranges from ₹6-12 LPA, with top B-school graduates earning ₹25-60+ LPA.', '[{\"name\":\"McKinsey & Company\",\"logo\":\"\"},{\"name\":\"Boston Consulting Group\",\"logo\":\"\"},{\"name\":\"Amazon\",\"logo\":\"\"},{\"name\":\"Deloitte\",\"logo\":\"\"},{\"name\":\"HDFC Bank\",\"logo\":\"\"},{\"name\":\"Reliance Industries\",\"logo\":\"\"},{\"name\":\"Hindustan Unilever\",\"logo\":\"\"},{\"name\":\"ITC Limited\",\"logo\":\"\"},{\"name\":\"Goldman Sachs\",\"logo\":\"\"},{\"name\":\"Tata Group\",\"logo\":\"\"}]', 12.00, 6.00, 60.00, 1, 2800, 'active', '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('crs-mbbs-03', 'MBBS - Bachelor of Medicine and Bachelor of Surgery', 'mbbs', 'UG', 'Medical', 'cat-med-03', 5, 'MBBS (Bachelor of Medicine and Bachelor of Surgery) is a 5.5-year undergraduate medical degree program (including 1 year of compulsory rotating internship). It is the primary medical qualification in India that enables graduates to practice as licensed medical doctors. The curriculum covers anatomy, physiology, biochemistry, pharmacology, pathology, microbiology, forensic medicine, community medicine, and clinical rotations across various departments including medicine, surgery, pediatrics, obstetrics & gynecology, ophthalmology, and ENT.', '10+2 with Physics, Chemistry, and Biology (PCB) with a minimum of 50% aggregate marks (40% for reserved categories). Must qualify NEET UG (National Eligibility cum Entrance Test) conducted by NTA. The admission process is through MCC counseling for AIQ seats and state counseling for state quota seats. Age requirement: minimum 17 years as of December 31 of the admission year.', 'MBBS graduates have diverse career opportunities in the medical field. After completing the degree, graduates can practice as general physicians, pursue higher specialization through MD/MS/DM/MCh programs, or appear for competitive exams like NEET PG, UPSC CMS, and AIIMS PG. Career options include Government and Private Hospital practice, Medical Officer in PHCs/CHCs, Armed Forces Medical Services, Medical College teaching, Research in medical sciences, Public Health administration, and Medical journalism. The healthcare sector is growing rapidly with increasing demand for qualified doctors.', '[{\"name\":\"All India Institute of Medical Sciences\",\"logo\":\"\"},{\"name\":\"Apollo Hospitals\",\"logo\":\"\"},{\"name\":\"Fortis Healthcare\",\"logo\":\"\"},{\"name\":\"Max Healthcare\",\"logo\":\"\"},{\"name\":\"Manipal Hospitals\",\"logo\":\"\"},{\"name\":\"Narayana Health\",\"logo\":\"\"},{\"name\":\"AIIMS Delhi\",\"logo\":\"\"},{\"name\":\"PGIMER Chandigarh\",\"logo\":\"\"},{\"name\":\"Armed Forces Medical Services\",\"logo\":\"\"},{\"name\":\"Government Medical Colleges\",\"logo\":\"\"}]', 9.00, 6.00, 30.00, 1, 600, 'active', '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('crs-nursing-08', 'B.Sc Nursing - Bachelor of Science in Nursing', 'bsc-nursing', 'UG', 'Nursing', 'cat-med-03', 4, 'B.Sc Nursing is a 4-year undergraduate program that trains students to become professional nurses capable of providing healthcare services in hospitals, clinics, community health centers, and other healthcare settings. The curriculum covers anatomy, physiology, microbiology, pharmacology, medical-surgical nursing, community health nursing, pediatric nursing, psychiatric nursing, and obstetric & gynecological nursing. Clinical practice in hospitals is a mandatory component of the program.', '10+2 with Physics, Chemistry, Biology, and English with a minimum of 45-50% aggregate marks. Must be at least 17 years old at the time of admission. Admission is through state-level entrance exams, NEET (some states), or institution-level exams. Female candidates are preferred, though male candidates are also eligible.', 'Nurses are in high demand globally, with excellent job opportunities in India and abroad. Career options include Staff Nurse, Nursing Officer, ICU Nurse, OT Nurse, Community Health Nurse, School Nurse, and Nursing Educator. Qualified nurses can work in government hospitals, private hospitals, international organizations (WHO, UNICEF), cruise ships, and old-age homes. Post-graduation (M.Sc Nursing) opens doors to specialization and teaching roles. Average starting salary ranges from ₹3-5 LPA in India, with significantly higher salaries abroad.', '[{\"name\":\"Apollo Hospitals\",\"logo\":\"\"},{\"name\":\"Fortis Healthcare\",\"logo\":\"\"},{\"name\":\"Max Healthcare\",\"logo\":\"\"},{\"name\":\"AIIMS\",\"logo\":\"\"},{\"name\":\"Manipal Hospitals\",\"logo\":\"\"},{\"name\":\"Narayana Health\",\"logo\":\"\"},{\"name\":\"Christian Medical College\",\"logo\":\"\"},{\"name\":\"Lilavati Hospital\",\"logo\":\"\"},{\"name\":\"Kokilaben Hospital\",\"logo\":\"\"},{\"name\":\"Global Hospital\",\"logo\":\"\"}]', 4.00, 2.50, 12.00, 0, 1500, 'active', '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('d66aaf3b-e2d2-4513-9b4f-912c047c13ab', 'LLM', 'llm', 'PG', 'Law', NULL, 2, NULL, NULL, NULL, NULL, 5.00, NULL, NULL, 1, 500, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('db08c09b-cd7d-4266-b77f-1727ae594141', 'MCA', 'mca', 'PG', 'IT & Software', NULL, 3, NULL, NULL, NULL, NULL, 6.00, NULL, NULL, 1, 1600, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('de9623ad-cf46-4888-998b-4be9e1af4a0c', 'M.Sc Nursing', 'msc-nursing', 'PG', 'Nursing', NULL, 2, NULL, NULL, NULL, NULL, 4.50, NULL, NULL, 1, 400, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('e224d2e8-273e-4f81-9511-05b7723612c8', 'B.Tech ECE', 'btech-ece-hons', 'UG', 'Engineering', NULL, 4, NULL, NULL, NULL, NULL, 6.50, NULL, NULL, 1, 2800, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('ecb6ec29-c69d-47a5-bae9-2aada0c2d1b9', 'BHMS - Homeopathy', 'bhms', 'UG', 'Medical', NULL, 5, NULL, NULL, NULL, NULL, 3.50, NULL, NULL, 1, 350, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('ef1b6690-6394-4c2b-8de9-ad07e6f0f742', 'BFA - Fine Arts', 'bfa', 'UG', 'Design', NULL, 4, NULL, NULL, NULL, NULL, 3.00, NULL, NULL, 1, 300, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('f3403688-ad16-490d-b1d3-a896cf470152', 'B.Sc Biotechnology', 'bsc-biotech', 'UG', 'Science', NULL, 3, NULL, NULL, NULL, NULL, 4.00, NULL, NULL, 1, 400, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('facd1b8d-55f5-4c91-86ba-cc081b75b702', 'B.Sc Chemistry (Hons)', 'bsc-chem-hons', 'UG', 'Science', NULL, 3, NULL, NULL, NULL, NULL, 3.50, NULL, NULL, 1, 750, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19'),
('fbd1f454-1593-4993-b63d-a2371328e851', 'M.Com', 'mcom', 'PG', 'Commerce', NULL, 2, NULL, NULL, NULL, NULL, 4.50, NULL, NULL, 1, 1500, 'active', '2026-06-23 08:43:19', '2026-06-23 08:43:19');

--
-- Triggers `courses`
--
DELIMITER $$
CREATE TRIGGER `trg_courses_after_delete` AFTER DELETE ON `courses` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'courses', OLD.id,
        JSON_OBJECT('course_name', OLD.course_name), NULL, NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_courses_after_insert` AFTER INSERT ON `courses` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'courses', NEW.id, NULL,
        JSON_OBJECT('course_name', NEW.course_name, 'course_slug', NEW.course_slug),
        NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_courses_after_update` AFTER UPDATE ON `courses` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'courses', NEW.id,
        JSON_OBJECT('course_name', OLD.course_name, 'status', OLD.status),
        JSON_OBJECT('course_name', NEW.course_name, 'status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;

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
('cp-ballb-01', 'crs-ba-llb-09', 'Litigation Advocate', 7.00, '[\"Cyril Amarchand\",\"Khaitan & Co\",\"Trilegal\",\"Independent Practice\"]', 'high', '[\"Court Practice\",\"Legal Drafting\",\"Advocacy\",\"Research\"]', 4.00, 22.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-ballb-02', 'crs-ba-llb-09', 'Legal Counsel', 10.00, '[\"Google\",\"Microsoft\",\"Amazon\",\"Tata Group\",\"Reliance\"]', 'high', '[\"Corporate Law\",\"Compliance\",\"Contract Drafting\",\"Risk Assessment\"]', 6.00, 28.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-ballb-03', 'crs-ba-llb-09', 'Public Prosecutor', 8.00, '[\"State Government\",\"Central Government\"]', 'medium', '[\"Criminal Law\",\"Trial Advocacy\",\"Legal Knowledge\"]', 5.50, 18.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-bba-01', 'crs-bba-07', 'Marketing Executive', 4.50, '[\"HUL\",\"Nestle\",\"ITC\",\"Asian Paints\",\"Britannia\"]', 'high', '[\"Sales\",\"Digital Marketing\",\"Market Research\",\"Communication\"]', 3.00, 12.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-bba-02', 'crs-bba-07', 'Business Development Executive', 5.00, '[\"Amazon\",\"Flipkart\",\"BYJU\'S\",\"Swiggy\",\"Zomato\"]', 'high', '[\"Sales\",\"Negotiation\",\"CRM\",\"Client Management\"]', 3.50, 14.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-bba-03', 'crs-bba-07', 'HR Executive', 4.00, '[\"TCS\",\"Wipro\",\"Infosys\",\"HCL\"]', 'medium', '[\"Recruitment\",\"Payroll\",\"Employee Relations\"]', 2.80, 10.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-bc-01', 'crs-bcom-06', 'Chartered Accountant', 12.00, '[\"Deloitte\",\"PwC\",\"EY\",\"KPMG\",\"BDO\"]', 'high', '[\"Accounting\",\"Tax\",\"Audit\",\"Financial Reporting\"]', 7.00, 30.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-bc-02', 'crs-bcom-06', 'Financial Analyst', 6.50, '[\"HDFC Bank\",\"ICICI Bank\",\"Axis Bank\",\"Kotak\"]', 'high', '[\"Financial Analysis\",\"Excel\",\"Valuation\",\"Forecasting\"]', 4.00, 15.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-bc-03', 'crs-bcom-06', 'Tax Consultant', 7.00, '[\"Deloitte\",\"EY\",\"PwC\",\"KPMG\",\"Grant Thornton\"]', 'high', '[\"Income Tax\",\"GST\",\"Tax Planning\",\"Compliance\"]', 4.50, 18.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-bca-01', 'crs-bca-04', 'Web Developer', 4.50, '[\"TCS\",\"Wipro\",\"Infosys\",\"Tech Mahindra\",\"Capgemini\"]', 'high', '[\"HTML/CSS\",\"JavaScript\",\"React\",\"PHP\",\"Node.js\"]', 3.00, 10.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-bca-02', 'crs-bca-04', 'Software Developer', 5.50, '[\"Infosys\",\"IBM\",\"Accenture\",\"HCL\",\"Cognizant\"]', 'high', '[\"Java\",\"Python\",\"SQL\",\"Git\",\"Problem Solving\"]', 3.50, 14.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-bca-03', 'crs-bca-04', 'System Analyst', 6.00, '[\"IBM\",\"Wipro\",\"TCS\",\"Tech Mahindra\"]', 'medium', '[\"System Architecture\",\"Networking\",\"Database Management\"]', 4.00, 15.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-bca-04', 'crs-bca-04', 'Database Administrator', 5.00, '[\"Oracle\",\"TCS\",\"Infosys\",\"Cognizant\"]', 'medium', '[\"MySQL\",\"Oracle\",\"SQL Server\",\"Backup & Recovery\"]', 3.50, 12.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-bd-01', 'crs-bdes-10', 'UI/UX Designer', 8.00, '[\"Google\",\"Microsoft\",\"Flipkart\",\"Zomato\",\"PhonePe\"]', 'high', '[\"Figma\",\"Sketch\",\"Prototyping\",\"User Research\",\"Wireframing\"]', 5.00, 20.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-bd-02', 'crs-bdes-10', 'Graphic Designer', 5.00, '[\"Ogilvy\",\"Wieden+Kennedy\",\"Pentagram\",\"DDB Mudra\"]', 'medium', '[\"Photoshop\",\"Illustrator\",\"InDesign\",\"Typography\"]', 3.00, 12.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-bd-03', 'crs-bdes-10', 'Product Designer', 9.00, '[\"IDEO\",\"Frog Design\",\"Godrej\",\"Tata\",\"Ola\"]', 'high', '[\"Industrial Design\",\"3D Modeling\",\"CAD\",\"Materials Science\"]', 5.50, 22.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-bd-04', 'crs-bdes-10', 'Interior Designer', 6.00, '[\"Livspace\",\"HomeLane\",\"Godrej Interio\",\"Asian Paints\"]', 'medium', '[\"AutoCAD\",\"3ds Max\",\"Space Planning\",\"Materials\"]', 3.50, 15.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-bt-01', 'crs-btech-01', 'Software Engineer', 8.50, '[\"Google\",\"Microsoft\",\"Amazon\",\"TCS\",\"Infosys\"]', 'high', '[\"Java\",\"Python\",\"Data Structures\",\"Algorithms\",\"System Design\"]', 5.50, 18.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-bt-02', 'crs-btech-01', 'Data Scientist', 12.00, '[\"Fractal Analytics\",\"MuSigma\",\"Meta\",\"IBM\",\"Netflix\"]', 'high', '[\"Python\",\"Machine Learning\",\"SQL\",\"Statistics\",\"Deep Learning\"]', 7.00, 28.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-bt-03', 'crs-btech-01', 'Full Stack Developer', 9.00, '[\"Flipkart\",\"Razorpay\",\"Zomato\",\"Swiggy\",\"PhonePe\"]', 'high', '[\"JavaScript\",\"React\",\"Node.js\",\"MongoDB\",\"AWS\"]', 5.00, 20.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-bt-04', 'crs-btech-01', 'Mechanical Engineer', 5.50, '[\"Tata Motors\",\"L&T\",\"Maruti Suzuki\",\"BOEING\",\"HAL\"]', 'medium', '[\"AutoCAD\",\"SolidWorks\",\"Thermodynamics\",\"Manufacturing\"]', 3.50, 12.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-bt-05', 'crs-btech-01', 'Civil Engineer', 5.00, '[\"L&T\",\"Tata Projects\",\"DLF\",\"NCC Ltd\"]', 'medium', '[\"AutoCAD\",\"Staad Pro\",\"Construction Management\"]', 3.00, 10.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-llb-01', 'crs-llb-05', 'Corporate Lawyer', 8.00, '[\"Khaitan & Co\",\"Trilegal\",\"AZB & Partners\",\"Cyril Amarchand\"]', 'high', '[\"Legal Drafting\",\"Negotiation\",\"Corporate Governance\",\"M&A\"]', 5.00, 25.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-llb-02', 'crs-llb-05', 'Litigation Lawyer', 6.50, '[\"Independent Practice\",\"Crawford Bayley\",\"Luthra & Luthra\"]', 'medium', '[\"Court Practice\",\"Legal Research\",\"Advocacy\",\"Drafting\"]', 3.50, 20.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-llb-03', 'crs-llb-05', 'Legal Advisor', 7.00, '[\"HDFC Bank\",\"ICICI Bank\",\"Reliance\",\"Tata Group\"]', 'high', '[\"Contract Law\",\"Compliance\",\"Risk Management\",\"Advisory\"]', 4.50, 18.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-llb-04', 'crs-llb-05', 'Judicial Officer', 10.00, '[\"District Courts\",\"High Courts\",\"Supreme Court\"]', 'medium', '[\"Judiciary Exam Prep\",\"Legal Knowledge\",\"Integrity\"]', 7.00, 25.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-mb-01', 'crs-mba-02', 'Management Consultant', 18.00, '[\"McKinsey\",\"BCG\",\"Bain\",\"Deloitte\",\"Accenture\"]', 'high', '[\"Strategy\",\"Problem Solving\",\"Excel\",\"PowerPoint\",\"Analytics\"]', 12.00, 45.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-mb-02', 'crs-mba-02', 'Marketing Manager', 12.00, '[\"HUL\",\"P&G\",\"Amazon\",\"ITC\",\"Nestle\"]', 'high', '[\"Digital Marketing\",\"Brand Management\",\"Market Research\",\"Communication\"]', 8.00, 25.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-mb-03', 'crs-mba-02', 'Investment Banker', 15.00, '[\"Goldman Sachs\",\"JP Morgan\",\"Morgan Stanley\",\"ICICI Securities\"]', 'high', '[\"Financial Modeling\",\"Valuation\",\"Excel\",\"Negotiation\"]', 10.00, 40.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-mb-04', 'crs-mba-02', 'Product Manager', 14.00, '[\"Google\",\"Microsoft\",\"Amazon\",\"Flipkart\",\"Zomato\"]', 'high', '[\"Product Strategy\",\"Analytics\",\"Agile\",\"UX Understanding\"]', 9.00, 35.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-mb-05', 'crs-mba-02', 'HR Manager', 8.00, '[\"TCS\",\"Infosys\",\"Wipro\",\"HDFC Bank\"]', 'medium', '[\"Recruitment\",\"Employee Relations\",\"Performance Management\"]', 5.00, 18.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-mb-06', 'crs-mbbs-03', 'General Physician', 9.00, '[\"Apollo Hospitals\",\"Fortis\",\"Govt Hospitals\",\"Max Healthcare\"]', 'medium', '[\"Clinical Skills\",\"Diagnostics\",\"Patient Care\",\"Communication\"]', 6.00, 18.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-mb-07', 'crs-mbbs-03', 'Surgeon', 15.00, '[\"AIIMS\",\"Apollo\",\"Fortis\",\"Narayana Health\"]', 'high', '[\"Surgery\",\"Operative Skills\",\"Anatomy\",\"Emergency Medicine\"]', 8.00, 35.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-mb-08', 'crs-mbbs-03', 'Pediatrician', 10.00, '[\"AIIMS\",\"CMC Vellore\",\"Manipal Hospital\"]', 'medium', '[\"Child Care\",\"Vaccination\",\"Developmental Assessment\"]', 6.50, 22.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-mb-09', 'crs-mbbs-03', 'Medical Researcher', 8.00, '[\"ICMR\",\"AIIMS Research\",\"WHO\",\"Pharma Companies\"]', 'high', '[\"Research Methodology\",\"Biostatistics\",\"Publishing\"]', 5.00, 20.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-ns-01', 'crs-nursing-08', 'Staff Nurse', 4.00, '[\"Apollo\",\"Fortis\",\"AIIMS\",\"CMC Vellore\",\"Max Healthcare\"]', 'high', '[\"Patient Care\",\"Medication Administration\",\"Documentation\"]', 3.00, 8.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-ns-02', 'crs-nursing-08', 'ICU Nurse', 5.00, '[\"Apollo\",\"Fortis\",\"Manipal\",\"Narayana Health\"]', 'high', '[\"Critical Care\",\"Ventilator Management\",\"Monitoring\"]', 3.50, 10.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('cp-ns-03', 'crs-nursing-08', 'Community Health Nurse', 3.50, '[\"Govt Health Dept\",\"WHO\",\"UNICEF\"]', 'medium', '[\"Public Health\",\"Immunization\",\"Health Education\"]', 2.50, 7.00, '2026-06-22 03:55:39', '2026-06-22 03:55:39');

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
('0cad6eb4-c398-4aea-b11c-37d0680d660c', 'Pharmacy', 'pharmacy', 'ph-pill', NULL, 10, 0, '2026-06-21 10:59:02', '2026-06-21 10:59:02'),
('199acb12-2997-4d7c-81a2-6e5007d6a5c8', 'Design', 'design', 'ph-paint-brush', NULL, 11, 0, '2026-06-21 10:59:02', '2026-06-21 10:59:02'),
('37d625bd-fa1f-40c7-bdfe-3228789d2220', 'Nursing', 'nursing', 'ph-hand-heart', NULL, 9, 0, '2026-06-21 10:59:02', '2026-06-21 10:59:02'),
('5543c231-d284-40ed-98e2-0ab3105d6e6a', 'Law', 'law', 'ph-scales', NULL, 4, 0, '2026-06-21 10:59:02', '2026-06-21 10:59:02'),
('625a1c93-e23b-4dd6-ab3e-77f2b9013341', 'Medical', 'medical', 'ph-heart-pulse', NULL, 3, 0, '2026-06-21 10:59:02', '2026-06-21 10:59:02'),
('6d9f72d9-9668-4b76-8e02-217b53b70dcb', 'Management', 'management', 'ph-briefcase', NULL, 2, 0, '2026-06-21 10:59:02', '2026-06-21 10:59:02'),
('74d1cdde-0f84-496f-85e9-27a0e9e87d9e', 'Education', 'education', 'ph-chalkboard-teacher', NULL, 12, 0, '2026-06-21 10:59:02', '2026-06-21 10:59:02'),
('847e0256-0493-421a-8559-5dd2f192254e', 'Computer Applications', 'computer-applications', 'ph-laptop', NULL, 6, 0, '2026-06-21 10:59:02', '2026-06-21 10:59:02'),
('878bc5d8-9321-4343-b738-28e3ba9127bb', 'Commerce', 'commerce', 'ph-chart-line-up', NULL, 8, 0, '2026-06-21 10:59:02', '2026-06-21 10:59:02'),
('a974959f-4857-46bf-9aac-c26de7124ffc', 'frf', 'ghvyfhgyuhg', 'uploads/categories/1782718700_153006.jpg', 'bf6d310e-e0df-4c70-806c-8567a2cd9f06', 0, 0, '2026-06-29 07:38:20', '2026-06-29 07:38:20'),
('b1996682-22a9-4027-88da-09973cd20785', 'Engineering', 'engineering', 'ph-wrench', NULL, 1, 0, '2026-06-21 10:59:02', '2026-06-21 10:59:02'),
('bf6d310e-e0df-4c70-806c-8567a2cd9f06', 'Arts', 'arts', 'ph-palette', NULL, 7, 0, '2026-06-21 10:59:02', '2026-06-21 10:59:02'),
('cat-it-01', 'IT & Software', 'it-software', NULL, NULL, 4, 1, '2026-06-22 03:54:55', '2026-06-22 03:54:55'),
('cd1dc7e7-46b8-454c-ac37-ead71f76c6d4', 'Science', 'science', 'ph-atom', NULL, 5, 0, '2026-06-21 10:59:02', '2026-06-21 10:59:02');

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
('sp-acc-01', 'crs-bcom-06', 'Accounting & Auditing', 'accounting-auditing', 'Focuses on financial accounting, cost accounting, auditing procedures, and tax compliance.', 1, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-ai-06', 'crs-btech-01', 'AI & Data Science', 'ai-data-science', 'Specializes in artificial intelligence, machine learning, deep learning, data analytics, and big data technologies.', 6, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-ba-06', 'crs-mba-02', 'Business Analytics', 'ba', 'Specializes in data-driven decision making, predictive modeling, business intelligence, and statistical analysis.', 6, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-bfin-02', 'crs-bcom-06', 'Banking & Finance', 'banking-finance', 'Covers banking operations, financial markets, investment analysis, and portfolio management.', 2, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-bm-01', 'crs-bba-07', 'Business Management', 'business-management', 'Covers general management principles, entrepreneurship, strategic planning, and organizational leadership.', 1, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-ce-04', 'crs-btech-01', 'Civil Engineering', 'civil', 'Covers construction, structural design, transportation engineering, water resources, and urban planning.', 4, 0, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-chn-02', 'crs-nursing-08', 'Community Health Nursing', 'community-health', 'Covers public health nursing, community assessment, and health promotion programs.', 2, 0, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-cloud-04', 'crs-bca-04', 'Cloud Computing', 'cloud-computing', 'Study of AWS, Azure, GCP cloud services, deployment, and cloud-native application development.', 4, 0, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-cml-03', 'crs-ba-llb-09', 'Corporate & Commercial Law', 'corp-comm', 'Covers business regulations, company law, competition law, and commercial transactions.', 3, 0, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-cnl-02', 'crs-ba-llb-09', 'Constitutional & Administrative Law', 'const-admin', 'Focuses on constitutional framework, fundamental rights, and administrative governance.', 2, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-const-03', 'crs-llb-05', 'Constitutional Law', 'constitutional-law', 'Focuses on interpretation of the Constitution, fundamental rights, and judicial review.', 3, 0, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-corp-01', 'crs-llb-05', 'Corporate Law', 'corporate-law', 'Deals with laws governing businesses, mergers & acquisitions, corporate governance, and compliance.', 1, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-crim-02', 'crs-llb-05', 'Criminal Law', 'criminal-law', 'Involves prosecution and defense of criminal cases, criminology, and criminal justice system.', 2, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-crl-01', 'crs-ba-llb-09', 'Criminal Law', 'criminal-law-ba', 'In-depth study of criminal jurisprudence, IPC, CrPC, and criminal litigation.', 1, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-cse-01', 'crs-btech-01', 'Computer Science & Engineering', 'cse', 'Focuses on computer programming, algorithms, data structures, artificial intelligence, machine learning, and software development.', 1, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-cyber-02', 'crs-bca-04', 'Cyber Security', 'cyber-security', 'Focuses on protecting computer systems, networks, and data from cyber threats and vulnerabilities.', 2, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-data-03', 'crs-bca-04', 'Data Science', 'data-analytics', 'Involves data analysis, visualization, machine learning, and statistical modeling using Python and R.', 3, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-dm-02', 'crs-bba-07', 'Digital Marketing', 'digital-marketing', 'Focuses on SEO, SEM, social media marketing, content marketing, and online advertising.', 2, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-ece-03', 'crs-btech-01', 'Electronics & Communication', 'ece', 'Deals with electronic devices, circuits, VLSI design, embedded systems, and communication networks.', 3, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-ee-05', 'crs-btech-01', 'Electrical Engineering', 'electrical', 'Deals with power systems, electrical machines, control systems, renewable energy, and power electronics.', 5, 0, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-fin-01', 'crs-mba-02', 'Finance', 'finance', 'Focuses on financial management, investment analysis, corporate finance, banking, and portfolio management.', 1, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-gd-02', 'crs-bdes-10', 'Graphic Design', 'graphic-design', 'Covers visual communication, typography, branding, and print/digital media design.', 2, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-gen-01', 'crs-mbbs-03', 'General Medicine', 'general-medicine', 'Focuses on diagnosis and treatment of adult diseases, internal medicine, and patient care.', 1, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-gyn-04', 'crs-mbbs-03', 'Obstetrics & Gynecology', 'obs-gyn', 'Focuses on pregnancy, childbirth, reproductive health, and female reproductive system disorders.', 4, 0, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-hr-03', 'crs-mba-02', 'Human Resource Management', 'hr', 'Deals with recruitment, talent management, employee relations, compensation, and organizational development.', 3, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-hrm-03', 'crs-bba-07', 'Human Resources', 'hr-bba', 'Deals with recruitment, training, performance management, and employee engagement.', 3, 0, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-id-04', 'crs-bdes-10', 'Interior Design', 'interior-design', 'Focuses on space planning, interior architecture, furniture design, and sustainable interiors.', 4, 0, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-intl-04', 'crs-llb-05', 'International Law', 'international-law', 'Covers treaties, international organizations, human rights law, and cross-border legal issues.', 4, 0, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-it-05', 'crs-mba-02', 'Information Technology', 'it-mba', 'Combines business management with IT strategy, digital transformation, and technology-driven innovation.', 5, 0, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-me-02', 'crs-btech-01', 'Mechanical Engineering', 'mechanical', 'Involves design, manufacturing, and operation of machinery, thermal systems, robotics, and automotive engineering.', 2, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-mkt-02', 'crs-mba-02', 'Marketing', 'marketing', 'Covers sales management, digital marketing, brand management, consumer behavior, and advertising strategy.', 2, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-msn-01', 'crs-nursing-08', 'Medical-Surgical Nursing', 'med-surg', 'Focuses on care of adult patients with acute and chronic medical conditions.', 1, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-ops-04', 'crs-mba-02', 'Operations Management', 'operations', 'Focuses on supply chain management, production planning, quality control, and logistics optimization.', 4, 0, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-pd-03', 'crs-bdes-10', 'Product Design', 'product-design', 'Deals with industrial product design, ergonomics, materials, and manufacturing processes.', 3, 0, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-pdn-03', 'crs-nursing-08', 'Pediatric Nursing', 'pediatric-nursing', 'Deals with healthcare of infants, children, and adolescents.', 3, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-ped-03', 'crs-mbbs-03', 'Pediatrics', 'pediatrics', 'Deals with medical care of infants, children, and adolescents including preventive health and developmental issues.', 3, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-surg-02', 'crs-mbbs-03', 'General Surgery', 'general-surgery', 'Covers surgical procedures, operative techniques, pre-operative and post-operative patient management.', 2, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-tax-03', 'crs-bcom-06', 'Taxation', 'taxation', 'Deals with income tax, GST, tax planning, and tax compliance for individuals and businesses.', 3, 0, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-uxd-01', 'crs-bdes-10', 'UI/UX Design', 'ui-ux', 'Focuses on user interface design, user experience research, prototyping, and usability testing.', 1, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39'),
('sp-web-01', 'crs-bca-04', 'Web Development', 'web-development', 'Covers frontend (HTML, CSS, JavaScript, React) and backend (Node.js, PHP, Python) web technologies.', 1, 1, '2026-06-22 03:55:39', '2026-06-22 03:55:39');

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
('ex-bitstat-2026', 'BITSAT 2026', 'bitsat-2026', 'BITSAT', 'Birla Institute of Technology and Science', 'assets/images/exam-logos/bitsat-2026.svg', 'university', 'online', 'annual', 4, 200000, 0, 'active', 0, 0, 75, '10+2 with PCM and 75% aggregate', 'indian', 390, 130, 180, '[\"Physics\",\"Chemistry\",\"Mathematics\",\"English\",\"Logical Reasoning\"]', '{\"+3\":\"Correct answer\",\"-1\":\"Incorrect answer\",\"0\":\"Unattempted\"}', '[\"Physics (40 questions)\",\"Chemistry (40 questions)\",\"Mathematics (45 questions)\",\"English (15 questions)\",\"Logical Reasoning (10 questions)\"]', '[\"English\"]', 3400.00, 3400.00, 3400.00, 0.00, 3400.00, 'https://bitsadmission.com', 'https://bits-pilani.ac.in', '', 'https://bitsadmission.com', 'https://bitsadmission.com', 'BITS counselling', 3, 'https://bitsadmission.com', 'No normalisation. Raw score out of 390. Additional 12 marks for correct answers in extra questions attempted if time permits.', '2026-06-21 12:10:08', '2026-06-21 14:36:49'),
('ex-cat-2026', 'CAT 2026', 'cat-2026', 'CAT', 'Indian Institutes of Management (IIMs)', 'assets/images/exam-logos/cat-2026.svg', 'national', 'online', 'annual', 130, 350000, 1, 'active', 0, 0, 50, 'Bachelor degree with 50%', 'both', 198, 66, 120, '[\"Verbal Ability & Reading Comprehension\",\"Data Interpretation & Logical Reasoning\",\"Quantitative Ability\"]', '{\"+3\":\"Correct answer\",\"-1\":\"Incorrect answer\",\"0\":\"Unattempted\\/MCQ\",\"NA\":\"TITA (no negative marking)\"}', '[\"VARC: Verbal Ability & Reading Comprehension (24 questions)\",\"DILR: Data Interpretation & Logical Reasoning (20 questions)\",\"QA: Quantitative Ability (22 questions)\"]', '[\"English\"]', 2400.00, 1200.00, 600.00, 0.00, 600.00, 'https://iimcat.ac.in', 'https://iimcat.ac.in', '', 'https://iimcat.ac.in', 'https://iimcat.ac.in', 'Individual IIM counselling + CAP process', 3, 'https://iimcat.ac.in', 'IIMs use a complex normalisation process. Sectional and overall scores are scaled and percentiled. Each IIM has its own composite score formula.', '2026-06-21 12:10:07', '2026-06-21 14:36:49'),
('ex-clat-2026', 'CLAT 2026', 'clat-2026', 'CLAT', 'Consortium of National Law Universities', 'assets/images/exam-logos/clat-2026.svg', 'national', 'offline', 'annual', 22, 70000, 1, 'active', 0, 0, 45, '10+2 or equivalent', 'indian', 150, 150, 120, '[\"English Language\",\"Current Affairs\",\"Legal Reasoning\",\"Logical Reasoning\",\"Quantitative Techniques\"]', '{\"+1\":\"Correct answer\",\"-0.25\":\"Incorrect answer\",\"0\":\"Unattempted\"}', '[\"English Language (28-32 questions)\",\"Current Affairs including GK (35-39 questions)\",\"Legal Reasoning (35-39 questions)\",\"Logical Reasoning (28-32 questions)\",\"Quantitative Techniques (13-17 questions)\"]', '[\"English\"]', 4000.00, 4000.00, 3500.00, 0.00, 3500.00, 'https://consortiumofnlus.ac.in', 'https://consortiumofnlus.ac.in', '', 'https://consortiumofnlus.ac.in', 'https://consortiumofnlus.ac.in', 'CLAT Consortium counselling', 3, 'https://consortiumofnlus.ac.in', 'Raw marks are used for ranking. No normalisation applied. Merit list is based on total marks out of 150.', '2026-06-21 12:10:07', '2026-06-21 14:36:49'),
('ex-cmat-2026', 'CMAT 2026', 'cmat-2026', 'CMAT', 'National Testing Agency (NTA)', 'assets/images/exam-logos/cmat-2026.svg', 'national', 'online', 'annual', 1000, 70000, 1, 'active', 0, 0, 50, 'Bachelor degree with 50%', 'indian', 400, 100, 180, '[\"Logical Reasoning\",\"Language Comprehension\",\"Quantitative Techniques & DI\",\"General Awareness\",\"Innovation & Entrepreneurship\"]', '{\"+4\":\"Correct answer\",\"-1\":\"Incorrect answer\",\"0\":\"Unattempted\"}', '[\"Logical Reasoning (25 questions)\",\"Language Comprehension (25 questions)\",\"Quantitative Techniques & DI (25 questions)\",\"General Awareness (25 questions)\",\"Innovation & Entrepreneurship (Optional, 25 questions)\"]', '[\"English\"]', 2100.00, 1050.00, 1050.00, 0.00, 1050.00, 'https://cmat.nta.nic.in', 'https://cmat.nta.nic.in', '', 'https://cmat.nta.nic.in', 'https://cmat.nta.nic.in', 'AICTE counselling + individual institute', 3, 'https://cmat.nta.nic.in', 'NTA uses percentile-based normalisation across multiple sessions.', '2026-06-21 12:10:07', '2026-06-21 14:36:49'),
('ex-cuet-ug-2026', 'CUET UG 2026', 'cuet-ug-2026', 'CUET', 'National Testing Agency (NTA)', 'assets/images/exam-logos/cuet-ug-2026.svg', 'national', 'online', 'annual', 250, 1500000, 1, 'active', 0, 0, 50, '10+2 or equivalent', 'both', 800, 150, 195, '[\"Language Test\",\"Domain Subject\",\"General Test\"]', '{\"+5\":\"Correct MCQ\",\"-1\":\"Incorrect MCQ\",\"0\":\"Unattempted\"}', '[\"Section IA: Language (13 languages)\",\"Section IB: Language (20 languages)\",\"Section II: Domain Subjects (29 subjects)\",\"Section III: General Test\"]', '[\"English\",\"Hindi\",\"Assamese\",\"Bengali\",\"Gujarati\",\"Kannada\",\"Malayalam\",\"Marathi\",\"Odia\",\"Punjabi\",\"Tamil\",\"Telugu\",\"Urdu\",\"French\",\"German\",\"Italian\",\"Japanese\",\"Korean\",\"Chinese\",\"Spanish\",\"Russian\",\"Arabic\"]', 750.00, 750.00, 375.00, 0.00, 375.00, 'https://cuet.nta.nic.in', 'https://cuet.nta.nic.in', '', 'https://cuet.nta.nic.in', 'https://cuet.nta.nic.in', 'Participating Universities conduct their own counselling', 3, 'https://cuet.nta.nic.in', 'NTA uses percentile-based normalisation across multiple shifts. Score of each candidate is equated to a common scale to ensure fairness.', '2026-06-21 12:10:07', '2026-06-21 14:36:49'),
('ex-gate-2026', 'GATE 2026', 'gate-2026', 'GATE', 'Indian Institute of Science (IISc) & IITs', 'assets/images/exam-logos/gate-2026.svg', 'national', 'online', 'annual', 600, 900000, 1, 'active', 0, 0, 60, 'B.E./B.Tech or equivalent', 'both', 100, 65, 180, '[\"General Aptitude\",\"Engineering Mathematics\",\"Subject Specific\"]', '{\"+1\":\"MCQ 1-mark Correct\",\"-1\\/3\":\"MCQ 1-mark Incorrect\",\"+2\":\"MCQ 2-mark Correct\",\"-2\\/3\":\"MCQ 2-mark Incorrect\",\"0\":\"Unattempted\"}', '[\"Section 1: General Aptitude (15 marks)\",\"Section 2: Engineering Mathematics (13 marks)\",\"Section 3: Subject Specific (72 marks)\"]', '[\"English\"]', 1800.00, 1500.00, 1000.00, 0.00, 750.00, 'https://gate.iitb.ac.in', 'https://gate.iitb.ac.in', '', 'https://gate.iitb.ac.in', 'https://gate.iitb.ac.in', 'GATE COAP (IITs) / CCMT (NITs) / JoSAA', 5, 'https://gate.iitb.ac.in', 'Raw marks are used. Score card is valid for 3 years from the date of announcement of results.', '2026-06-21 12:10:07', '2026-06-21 14:36:49'),
('ex-gmat-2026', 'GMAT 2026', 'gmat-2026', 'GMAT', 'Graduate Management Admission Council (GMAC)', 'assets/images/exam-logos/gmat-2026.svg', '', 'online', 'annual', 7000, 300000, 0, 'active', 18, 0, 0, 'Bachelor degree', 'both', 805, 64, 195, '[\"Quantitative Reasoning\",\"Verbal Reasoning\",\"Data Insights\"]', '[\"Adaptive scoring from 200-805\"]', '[\"Quantitative Reasoning (21 questions)\",\"Verbal Reasoning (23 questions)\",\"Data Insights (20 questions)\"]', '[\"English\"]', 275.00, 275.00, 275.00, 0.00, 275.00, 'https://www.mba.com/exams/gmat', 'https://www.mba.com', '', 'https://www.mba.com', 'https://www.mba.com', 'Direct application to business schools', 0, 'https://www.mba.com', 'GMAT uses adaptive testing. Questions adjust difficulty based on performance. Final score is on a 200-805 scale.', '2026-06-21 12:10:07', '2026-06-21 14:36:49'),
('ex-inicet-2026', 'INI-CET 2026', 'ini-cet-2026', 'INI-CET', 'National Board of Examinations (NBE)', 'assets/images/exam-logos/ini-cet-2026.svg', 'national', 'online', 'annual', 10, 40000, 1, 'active', 0, 0, 55, 'MBBS with internship completion', 'indian', 200, 200, 180, '[\"Pre-Clinical\",\"Para-Clinical\",\"Clinical\"]', '{\"+1\":\"Correct answer\",\"-1\\/3\":\"Incorrect answer\",\"0\":\"Unattempted\"}', '[\"Pre-Clinical subjects\",\"Para-Clinical subjects\",\"Clinical subjects\"]', '[\"English\"]', 4250.00, 4250.00, 4250.00, 0.00, 2125.00, 'https://natboard.edu.in', 'https://natboard.edu.in', '', 'https://natboard.edu.in', 'https://natboard.edu.in', 'INI-CET counselling by AIIMS', 3, 'https://natboard.edu.in', 'Raw marks used. INI-CET replaced AIIMS PG and JIPMER PG entrance exams.', '2026-06-21 12:10:08', '2026-06-21 14:36:49'),
('ex-jee-adv-2026', 'JEE Advanced 2026', 'jee-advanced-2026', 'JEE Advanced', 'Indian Institute of Technology (IIT)', 'assets/images/exam-logos/jee-advanced-2026.svg', 'national', 'online', 'annual', 23, 250000, 1, 'active', 0, 25, 75, 'JEE Main qualified', 'both', 396, 54, 180, '[\"Physics\",\"Chemistry\",\"Mathematics\"]', '{\"+3\":\"MCQ Correct\",\"-1\":\"MCQ Incorrect\",\"+4\":\"Numerical Correct\",\"0\":\"Unattempted\"}', '[\"Paper 1: Physics, Chemistry, Mathematics\",\"Paper 2: Physics, Chemistry, Mathematics\"]', '[\"English\",\"Hindi\"]', 3200.00, 1600.00, 1600.00, 0.00, 1600.00, 'https://jeeadv.ac.in', 'https://jeeadv.ac.in', '', 'https://jeeadv.ac.in', 'https://jeeadv.ac.in', 'JoSAA', 5, 'https://jeeadv.ac.in', 'Raw scores are used for ranking. Two papers (Paper 1 and Paper 2) are conducted. Both papers are compulsory. Final rank is based on combined marks of both papers.', '2026-06-21 12:10:07', '2026-06-21 14:36:49'),
('ex-jee-main-2026', 'JEE Main 2026', 'jee-main-2026', 'JEE Main', 'National Testing Agency (NTA)', 'assets/images/exam-logos/jee-main-2026.svg', 'national', 'online', 'annual', 3200, 1250000, 1, 'active', 17, 25, 75, '10+2 with PCM', 'both', 300, 75, 180, '[\"Physics\",\"Chemistry\",\"Mathematics\"]', '{\"+4\":\"Correct answer\",\"-1\":\"Incorrect answer\",\"0\":\"Unattempted\"}', '[\"Physics\",\"Chemistry\",\"Mathematics\"]', '[\"English\",\"Hindi\",\"Gujarati\",\"Tamil\",\"Telugu\",\"Marathi\",\"Bengali\",\"Assamese\",\"Odia\",\"Punjabi\",\"Kannada\",\"Malayalam\",\"Urdu\"]', 1000.00, 500.00, 500.00, 0.00, 500.00, 'https://jeemain.nta.ac.in', 'https://jeemain.nta.ac.in', '', 'https://jeemain.nta.ac.in', 'https://jeemain.nta.ac.in', 'JoSAA / CSAB', 5, 'https://jeemain.nta.ac.in', 'NTA uses percentile-based normalisation across multiple sessions to ensure fair comparison. The NTA score is calculated using the formula: 100 × (Number of candidates with raw score equal to or less than the candidate) / (Total number of candidates in the session).', '2026-06-21 12:10:07', '2026-06-21 14:36:49'),
('ex-mat-2026', 'MAT 2026', 'mat-2026', 'MAT', 'All India Management Association (AIMA)', 'assets/images/exam-logos/mat-2026.svg', 'national', 'both', 'annual', 800, 200000, 1, 'active', 0, 0, 50, 'Bachelor degree with 50%', 'both', 200, 200, 150, '[\"Language Comprehension\",\"Mathematical Skills\",\"Data Analysis & Sufficiency\",\"Intelligence & Critical Reasoning\",\"Indian & Global Environment\"]', '{\"+1\":\"Correct answer\",\"-0.25\":\"Incorrect answer\",\"0\":\"Unattempted\"}', '[\"Language Comprehension (40 questions)\",\"Mathematical Skills (40 questions)\",\"Data Analysis & Sufficiency (40 questions)\",\"Intelligence & Critical Reasoning (40 questions)\",\"Indian & Global Environment (40 questions)\"]', '[\"English\",\"Hindi\"]', 1800.00, 1800.00, 900.00, 0.00, 900.00, 'https://www.aima.in', 'https://www.aima.in', '', 'https://www.aima.in', 'https://www.aima.in', 'Individual institute counselling', 4, 'https://www.aima.in', 'MAT uses composite score. Indian & Global Environment section marks are not used in final percentile calculation.', '2026-06-21 12:10:07', '2026-06-21 14:36:49'),
('ex-mht-cet-2026', 'MHT CET 2026', 'mht-cet-2026', 'MHT CET', 'State Common Entrance Test Cell, Maharashtra', 'assets/images/exam-logos/mht-cet-2026.svg', 'state', 'online', 'annual', 200, 500000, 0, 'active', 0, 0, 50, '10+2 with PCM or PCB', 'indian', 200, 150, 180, '[\"Physics\",\"Chemistry\",\"Mathematics\",\"Biology\"]', '{\"+2\":\"Correct answer\",\"0\":\"Incorrect (no negative marking)\"}', '[\"PCM Group: Physics (50), Chemistry (50), Mathematics (50)\",\"PCB Group: Physics (50), Chemistry (50), Biology (50)\"]', '[\"English\",\"Marathi\",\"Hindi\",\"Urdu\"]', 1000.00, 800.00, 400.00, 0.00, 400.00, 'https://cetcell.mahacet.org', 'https://cetcell.mahacet.org', '', 'https://cetcell.mahacet.org', 'https://cetcell.mahacet.org', 'State CAP counselling by Maharashtra CET Cell', 4, 'https://cetcell.mahacet.org', '10th + 12th board marks are also considered for merit ranking along with CET score.', '2026-06-21 12:10:08', '2026-06-21 14:36:49'),
('ex-nata-2026', 'NATA 2026', 'nata-2026', 'NATA', 'Council of Architecture (CoA)', 'assets/images/exam-logos/nata-2026.svg', 'national', 'online', 'annual', 400, 80000, 1, 'active', 0, 0, 50, '10+2 with 50% aggregate in PCM', 'indian', 200, 52, 180, '[\"Physics\",\"Chemistry\",\"Mathematics\",\"Drawing & Composition\"]', '[\"MCQ: +2 per correct\",\"Drawing: Graded on composition, creativity, visual communication\"]', '[\"Part A: PCM (12 MCQ questions, 30 marks)\",\"Part B: Drawing (2 questions, 80 marks)\",\"Part C: PCM MCQ (40 questions, 90 marks)\"]', '[\"English\"]', 2000.00, 2000.00, 1000.00, 0.00, 1000.00, 'https://www.nata.in', 'https://www.nata.in', '', 'https://www.nata.in', 'https://www.nata.in', 'State-level counselling + CAP rounds', 3, 'https://www.nata.in', 'Part A and Part B are evaluated separately. Drawing section is evaluated offline by expert panel.', '2026-06-21 12:10:08', '2026-06-21 14:36:49'),
('ex-neet-pg-2026', 'NEET PG 2026', 'neet-pg-2026', 'NEET PG', 'National Board of Examinations (NBE)', 'assets/images/exam-logos/neet-pg-2026.svg', 'national', 'online', 'annual', 700, 400000, 1, 'active', 0, 0, 50, 'MBBS degree', 'indian', 800, 200, 210, '[\"Pre-Clinical\",\"Para-Clinical\",\"Clinical\"]', '{\"+4\":\"Correct answer\",\"-1\":\"Incorrect answer\",\"0\":\"Unattempted\"}', '[\"Anatomy\",\"Physiology\",\"Biochemistry\",\"Pathology\",\"Pharmacology\",\"Microbiology\",\"Forensic Medicine\",\"Community Medicine\",\"Ophthalmology\",\"ENT\",\"Medicine\",\"Surgery\",\"OBG\",\"Pediatrics\",\"Orthopedics\",\"Anesthesia\",\"Radiology\",\"Psychiatry\",\"Dermatology\"]', '[\"English\"]', 5500.00, 5500.00, 5500.00, 0.00, 2750.00, 'https://natboard.edu.in', 'https://natboard.edu.in', '', 'https://natboard.edu.in', 'https://natboard.edu.in', 'MCC DG (50% AIQ) + State Counselling (50%)', 4, 'https://natboard.edu.in', 'Raw scores are used. NBE applies normalisation if exam is conducted in multiple shifts using equipercentile method.', '2026-06-21 12:10:07', '2026-06-21 14:36:49'),
('ex-neet-pg-2026b', 'AIIMS PG 2026', 'aiims-pg-2026', 'AIIMS PG', 'All India Institute of Medical Sciences', 'assets/images/exam-logos/aiims-pg-2026.svg', 'national', 'online', 'annual', 20, 50000, 1, 'active', 0, 0, 55, 'MBBS with internship completion', 'indian', 200, 200, 180, '[\"Pre-Clinical\",\"Para-Clinical\",\"Clinical\"]', '{\"+1\":\"Correct answer\",\"-1\\/3\":\"Incorrect answer\",\"0\":\"Unattempted\"}', '[\"Anatomy\",\"Physiology\",\"Biochemistry\",\"Pathology\",\"Pharmacology\",\"Microbiology\",\"Community Medicine\",\"Medicine\",\"Surgery\",\"OBG\",\"Pediatrics\"]', '[\"English\"]', 2000.00, 1500.00, 1000.00, 0.00, 1000.00, 'https://www.aiimsexams.ac.in', 'https://www.aiims.edu', '', 'https://www.aiimsexams.ac.in', 'https://www.aiimsexams.ac.in', 'AIIMS counselling', 3, 'https://www.aiimsexams.ac.in', 'Raw marks used for ranking. Computer-based test.', '2026-06-21 12:10:08', '2026-06-21 14:36:49'),
('ex-neet-ug-2026', 'NEET UG 2026', 'neet-ug-2026', 'NEET', 'National Testing Agency (NTA)', 'assets/images/exam-logos/neet-ug-2026.svg', 'national', 'offline', 'annual', 1800, 2400000, 1, 'active', 17, 25, 50, '10+2 with PCB', 'both', 720, 200, 200, '[\"Physics\",\"Chemistry\",\"Botany\",\"Zoology\"]', '{\"+4\":\"Correct answer\",\"-1\":\"Incorrect answer\",\"0\":\"Unattempted\\/Skipped\"}', '[\"Section A: Physics (35 questions)\",\"Section B: Physics (15 questions, attempt 10)\",\"Section A: Chemistry (35 questions)\",\"Section B: Chemistry (15 questions, attempt 10)\",\"Section A: Botany (35 questions)\",\"Section B: Botany (15 questions, attempt 10)\",\"Section A: Zoology (35 questions)\",\"Section B: Zoology (15 questions, attempt 10)\"]', '[\"English\",\"Hindi\",\"Assamese\",\"Bengali\",\"Gujarati\",\"Kannada\",\"Marathi\",\"Odia\",\"Punjabi\",\"Tamil\",\"Telugu\",\"Urdu\",\"Bodo\",\"Dogri\",\"Khasi\",\"Konkani\",\"Manipuri\",\"Maithili\",\"Santali\"]', 1700.00, 1600.00, 900.00, 0.00, 900.00, 'https://neet.nta.nic.in', 'https://neet.nta.nic.in', '', 'https://neet.nta.nic.in', 'https://neet.nta.nic.in', 'MCC DG (15% AIQ) + State Counselling (85%)', 4, 'https://neet.nta.nic.in', 'Raw marks are used for ranking. No normalisation is applied in NEET UG. All India Rank is based on total marks scored out of 720.', '2026-06-21 12:10:07', '2026-06-21 14:36:49'),
('ex-snap-2026', 'SNAP 2026', 'snap-2026', 'SNAP', 'Symbiosis International (Deemed University)', 'assets/images/exam-logos/snap-2026.svg', 'national', 'online', 'annual', 15, 80000, 1, 'active', 0, 0, 50, 'Bachelor degree with 50%', 'both', 150, 60, 60, '[\"General English\",\"Quantitative Aptitude\",\"Logical & Analytical Reasoning\",\"Current Affairs\"]', '{\"+1\":\"Correct answer\",\"-0.25\":\"Incorrect answer\",\"0\":\"Unattempted\"}', '[\"General English (15 questions)\",\"Quantitative Aptitude (20 questions)\",\"Logical & Analytical Reasoning (25 questions)\"]', '[\"English\"]', 2250.00, 2250.00, 1125.00, 0.00, 1125.00, 'https://www.snaptest.org', 'https://www.snaptest.org', '', 'https://www.snaptest.org', 'https://www.snaptest.org', 'Symbiosis institute counselling', 3, 'https://www.snaptest.org', 'Raw marks are used. Score is valid only for the admission year.', '2026-06-21 12:10:07', '2026-06-21 14:36:49'),
('ex-ssc-cgl-2026', 'SSC CGL 2026', 'ssc-cgl-2026', 'SSC CGL', 'Staff Selection Commission', 'assets/images/exam-logos/ssc-cgl-2026.svg', 'national', 'online', 'annual', 0, 3000000, 1, 'active', 18, 32, 0, 'Bachelor degree', 'indian', 390, 100, 300, '[\"General Intelligence & Reasoning\",\"General Awareness\",\"Quantitative Aptitude\",\"English Comprehension\"]', '{\"+3\":\"Correct answer\",\"-1\":\"Incorrect answer\",\"0\":\"Unattempted\"}', '[\"Tier 1: General Intelligence & Reasoning (25 questions)\",\"Tier 1: General Awareness (25 questions)\",\"Tier 1: Quantitative Aptitude (25 questions)\",\"Tier 1: English Comprehension (25 questions)\",\"Tier 2: Quantitative Abilities\",\"Tier 2: English Language & Comprehension\",\"Tier 2: General Awareness\",\"Tier 2: Computer Proficiency\"]', '[\"English\",\"Hindi\"]', 100.00, 100.00, 0.00, 0.00, 0.00, 'https://ssc.nic.in', 'https://ssc.nic.in', '', 'https://ssc.nic.in', 'https://ssc.nic.in', 'Combined counselling by SSC', 2, 'https://ssc.nic.in', 'SSC uses normalisation formula for Tier 1 exam across multiple shifts to equalise difficulty levels.', '2026-06-21 12:10:07', '2026-06-21 14:36:49'),
('ex-ts-eamcet-2026', 'TS EAMCET 2026', 'ts-eamcet-2026', 'TS EAMCET', 'Jawaharlal Nehru Technological University Hyderabad', 'assets/images/exam-logos/ts-eamcet-2026.svg', 'state', 'online', 'annual', 300, 250000, 0, 'active', 0, 0, 45, '10+2 with PCM or PCB', 'indian', 160, 160, 180, '[\"Physics\",\"Chemistry\",\"Mathematics\",\"Biology\"]', '{\"+1\":\"Correct answer\",\"0\":\"Incorrect (no negative marking)\"}', '[\"Engineering: Physics (40), Chemistry (40), Mathematics (80)\",\"Medical: Physics (40), Chemistry (40), Biology (80)\"]', '[\"English\",\"Telugu\",\"Hindi\"]', 800.00, 400.00, 200.00, 0.00, 200.00, 'https://tseamcet.nic.in', 'https://tseamcet.nic.in', '', 'https://tseamcet.nic.in', 'https://tseamcet.nic.in', 'TS EAMCET counselling by TSCHE', 3, 'https://tseamcet.nic.in', 'No negative marking. 25% weightage to 12th board marks in some years (check notification).', '2026-06-21 12:10:08', '2026-06-21 14:36:49'),
('ex-upsc-cse-2026', 'UPSC CSE 2026', 'upsc-cse-2026', 'UPSC CSE', 'Union Public Service Commission', 'assets/images/exam-logos/upsc-cse-2026.svg', 'national', 'offline', 'annual', 0, 1000000, 1, 'active', 21, 32, 50, 'Bachelor degree from recognised university', 'indian', 2025, 175, 360, '[\"General Studies\",\"Optional Subject\",\"Essay\",\"Ethics\",\"Language\"]', '[\"GS: +1\\/-0.33\",\"Optional: +1\\/-0.33\",\"Essay: No negative marking\"]', '[\"Prelims: GS Paper 1 (100 questions)\",\"Prelims: CSAT Paper 2 (80 questions)\",\"Mains: Essay Paper\",\"Mains: GS Paper 1-4\",\"Mains: Optional Subject Paper 1 & 2\",\"Mains: Language Papers\",\"Interview\"]', '[\"English\",\"Hindi\"]', 100.00, 100.00, 0.00, 0.00, 0.00, 'https://upsconline.nic.in', 'https://upsc.gov.in', '', 'https://upsc.gov.in', 'https://upsc.gov.in', 'DoPT/Department of Personnel & Training', 1, 'https://upsc.gov.in', 'Prelims uses normalised scoring. Mains uses raw marks. Final merit is based on Mains (1750) + Interview (275) = 2025 total.', '2026-06-21 12:10:07', '2026-06-21 14:36:49'),
('ex-wbjee-2026', 'WBJEE 2026', 'wbjee-2026', 'WBJEE', 'West Bengal Joint Entrance Examinations Board', 'assets/images/exam-logos/wbjee-2026.svg', 'state', 'offline', 'annual', 150, 120000, 0, 'active', 17, 0, 45, '10+2 with PCM', 'indian', 200, 155, 240, '[\"Physics\",\"Chemistry\",\"Mathematics\"]', '{\"+1\":\"Category I correct\",\"-0.25\":\"Category I incorrect\",\"+2\":\"Category II correct\",\"0\":\"Category II incorrect\"}', '[\"Paper 1: Mathematics (100 marks)\",\"Paper 2: Physics + Chemistry (100 marks)\"]', '[\"English\",\"Hindi\",\"Bengali\"]', 600.00, 500.00, 250.00, 0.00, 250.00, 'https://wbjeeb.nic.in', 'https://wbjeeb.nic.in', '', 'https://wbjeeb.nic.in', 'https://wbjeeb.nic.in', 'WBJEE counselling by WBJEEB', 3, 'https://wbjeeb.nic.in', 'No normalisation. Raw marks used for ranking.', '2026-06-21 12:10:08', '2026-06-21 14:36:49'),
('ex-xat-2026', 'XAT 2026', 'xat-2026', 'XAT', 'Xavier Labour Relations Institute (XLRI)', 'assets/images/exam-logos/xat-2026.svg', 'national', 'online', 'annual', 150, 100000, 1, 'active', 0, 0, 50, 'Bachelor degree with 50%', 'both', 100, 100, 180, '[\"Verbal & Logical Reasoning\",\"Decision Making\",\"Quantitative Aptitude & Data Interpretation\",\"General Knowledge\"]', '{\"+1\":\"Correct answer\",\"-0.25\":\"Incorrect answer\",\"0\":\"Unattempted\"}', '[\"Part 1: Verbal & Logical Reasoning\",\"Part 1: Decision Making\",\"Part 1: Quantitative Aptitude & DI\",\"Part 2: General Knowledge (25 questions)\"]', '[\"English\"]', 2100.00, 2100.00, 1050.00, 0.00, 1050.00, 'https://xatonline.in', 'https://xatonline.in', '', 'https://xatonline.in', 'https://xatonline.in', 'Individual institute counselling', 3, 'https://xatonline.in', 'Raw scores are used. GK section is not used for ranking but considered during interview stage by individual institutes.', '2026-06-21 12:10:07', '2026-06-21 14:36:49');

--
-- Triggers `exams`
--
DELIMITER $$
CREATE TRIGGER `trg_exams_after_delete` AFTER DELETE ON `exams` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'exams', OLD.id,
        JSON_OBJECT('exam_name', OLD.exam_name), NULL, NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_exams_after_insert` AFTER INSERT ON `exams` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'exams', NEW.id, NULL,
        JSON_OBJECT('exam_name', NEW.exam_name, 'exam_slug', NEW.exam_slug),
        NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_exams_after_update` AFTER UPDATE ON `exams` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'exams', NEW.id,
        JSON_OBJECT('exam_name', OLD.exam_name, 'status', OLD.status),
        JSON_OBJECT('exam_name', NEW.exam_name, 'status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;

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
('009bda2d-e6e5-462c-baa4-8591ba39a685', 'ex-clat-2026', NULL, NULL, '2025', 'OBC', 200, 3000, 1),
('0640644e-eb74-46c0-9bb4-fe8665bda11b', 'ex-jee-main-2026', NULL, NULL, '2025', 'SC', 500, 50000, 1),
('0c4c7ca3-03dd-4578-8080-203d37855363', 'ex-jee-main-2026', NULL, NULL, '2025', 'ST', 500, 60000, 1),
('0e3eb0f5-c3a4-4874-9b78-f713793fcd5a', 'ex-cat-2026', NULL, NULL, '2025', 'EWS', 500, 6000, 1),
('14628fdf-f2fc-448e-864a-813713d02bc2', 'ex-upsc-cse-2026', NULL, NULL, '2024', 'General', 1, 100, 0),
('16439683-d5f5-425b-ab00-4f0ff6871b2d', 'ex-neet-ug-2026', NULL, NULL, '2025', 'General', 1, 15000, 1),
('1a24a4c4-bbab-4eec-8590-30f3e4e73904', 'ex-upsc-cse-2026', NULL, NULL, '2023', 'General', 1, 998, 1),
('2875172c-9bbd-4b15-b5e6-2015b63ac1ea', 'ex-upsc-cse-2026', NULL, NULL, '2024', 'ST', 1, 80, 0),
('2e21c23d-0b2a-4fe0-b197-7fbff1213b09', 'ex-upsc-cse-2026', NULL, NULL, '2024', 'SC', 1, 85, 0),
('330a3ace-0dd4-4148-b5ae-a316076d402b', 'ex-cat-2026', NULL, NULL, '2025', 'ST', 200, 20000, 1),
('3743e92d-24b5-4dc4-a170-4d6f8c113503', 'ex-gate-2026', NULL, NULL, '2025', 'ST', 50, 3500, 1),
('3c1607ef-5677-4625-bf05-0a5b0ef0151c', 'ex-upsc-cse-2026', NULL, NULL, '2022', 'OBC', 1, 1064, 1),
('3fbb5028-b013-492b-ab7f-6e59382b770a', 'ex-cat-2026', NULL, NULL, '2025', 'SC', 200, 15000, 1),
('458ae5b8-2ed8-4d07-bbd6-1ba445e1d450', 'ex-upsc-cse-2026', NULL, NULL, '2023', 'OBC', 1, 1036, 1),
('4efd8f23-eb65-4cd9-8ef1-7199a7282fcd', 'ex-upsc-cse-2026', NULL, NULL, '2024', 'General', 1, 962, 1),
('508a7cf6-f188-4e2a-9f48-61fb2ed6bc25', 'ex-wbjee-2026', NULL, NULL, '2025', 'SC', 200, 25000, 1),
('541d1e78-878f-47c0-acc5-9000caa98366', 'ex-clat-2026', NULL, NULL, '2025', 'General', 1, 1500, 1),
('55160087-a27e-4305-843e-aa3355bdfd9a', 'ex-xat-2026', NULL, NULL, '2025', 'General', 1, 2000, 1),
('557478d7-ecc4-414c-b2d0-934dc6af2c2f', 'ex-wbjee-2026', NULL, NULL, '2025', 'ST', 200, 30000, 1),
('56a06462-08ac-4dc4-99fd-f260068b89fd', 'ex-clat-2026', NULL, NULL, '2025', 'SC', 100, 5000, 1),
('56fe38ce-7e96-4be3-aa4e-f7bc7b80651d', 'ex-jee-main-2026', NULL, NULL, '2025', 'General', 1, 10000, 1),
('5bc9319d-9190-48a0-90a0-78edd648edb1', 'ex-upsc-cse-2026', NULL, NULL, '2023', 'SC', 1, 1100, 1),
('7466e0f9-ea1a-4605-99c6-2ceebe737105', 'ex-upsc-cse-2026', NULL, NULL, '2024', 'SC', 1, 1072, 1),
('7556d9aa-75aa-4ef8-bf43-1be277ffa4ab', 'ex-wbjee-2026', NULL, NULL, '2025', 'General', 1, 5000, 1),
('77346745-a00e-42ae-9fdd-3e9ce161c5d7', 'ex-neet-ug-2026', NULL, NULL, '2025', 'EWS', 1500, 30000, 1),
('7dca563a-6f9b-4beb-9fc9-d02aaa163c81', 'ex-gate-2026', NULL, NULL, '2025', 'SC', 50, 2500, 1),
('7fa841fd-9595-43f7-89a0-7077e10add60', 'ex-upsc-cse-2026', NULL, NULL, '2024', 'OBC', 1, 1008, 1),
('81ed809b-a394-40d6-809e-f7ecef875a29', 'ex-cat-2026', NULL, NULL, '2025', 'General', 1, 3000, 1),
('84bf3d3e-9fc8-48dd-864e-9250207fa87d', 'ex-xat-2026', NULL, NULL, '2025', 'OBC', 200, 5000, 1),
('9038dab4-7ff8-4b0c-8275-c75aaac61786', 'ex-upsc-cse-2026', NULL, NULL, '2024', 'PWD', 1, 1200, 1),
('94995089-7237-4757-a49b-169f753a1e36', 'ex-upsc-cse-2026', NULL, NULL, '2024', 'EWS', 1, 92, 0),
('95c11b51-cff0-4516-8027-f719a259f85e', 'ex-upsc-cse-2026', NULL, NULL, '2022', 'SC', 1, 1140, 1),
('981c5c6b-4af9-4f25-bfca-ebb05f59d433', 'ex-xat-2026', NULL, NULL, '2025', 'SC', 100, 8000, 1),
('9846c738-58c5-4606-93da-72c03b5b9066', 'ex-neet-ug-2026', NULL, NULL, '2025', 'SC', 500, 80000, 1),
('9eb3879e-8253-4d70-9318-f9ce70673b70', 'ex-clat-2026', NULL, NULL, '2025', 'ST', 100, 6000, 1),
('a5865b09-4b84-400e-a83b-8b9c25ba32f0', 'ex-neet-ug-2026', NULL, NULL, '2025', 'ST', 500, 100000, 1),
('aab1a6d5-58e6-4e73-a9ae-ac12ea078d15', 'ex-gate-2026', NULL, NULL, '2025', 'OBC', 100, 1500, 1),
('b129a927-8efd-4990-a843-04d7f0f0530b', 'ex-upsc-cse-2026', NULL, NULL, '2023', 'EWS', 1, 998, 1),
('b2394ced-60dc-43a2-a07f-11a5d407f966', 'ex-upsc-cse-2026', NULL, NULL, '2023', 'ST', 1, 1126, 1),
('b28cb872-8747-4f8d-ba74-a1ea551c7828', 'ex-neet-ug-2026', NULL, NULL, '2025', 'OBC', 1500, 50000, 1),
('b6e00152-b8ff-41e6-aa9e-aa07ad8d587c', 'ex-gate-2026', NULL, NULL, '2025', 'EWS', 100, 1000, 1),
('c0540cba-9a41-4fb4-95d5-2127e02e3367', 'ex-clat-2026', NULL, NULL, '2025', 'EWS', 200, 2500, 1),
('c1e3a9ea-d43a-4d25-83ab-36331ed3ee99', 'ex-jee-main-2026', NULL, NULL, '2025', 'EWS', 1000, 20000, 1),
('c2e3700d-1d46-46b2-8beb-b8efa1fecd90', 'ex-upsc-cse-2026', NULL, NULL, '2022', 'General', 1, 1032, 1),
('c60abb7b-2b66-4f1c-b88c-12f315df211b', 'ex-upsc-cse-2026', NULL, NULL, '2024', 'ST', 1, 1100, 1),
('d579b34d-458b-45b9-8f38-75d40eab1a1b', 'ex-upsc-cse-2026', NULL, NULL, '2022', 'EWS', 1, 1032, 1),
('e108e28d-2429-4b43-afed-378891725e53', 'ex-cat-2026', NULL, NULL, '2025', 'OBC', 500, 8000, 1),
('e8b5e7c4-1ff7-4b46-8730-e6faddaed0b4', 'ex-upsc-cse-2026', NULL, NULL, '2024', 'EWS', 1, 962, 1),
('edd99a81-b79d-4d34-88f2-c4da5f92d039', 'ex-wbjee-2026', NULL, NULL, '2025', 'OBC', 500, 15000, 1),
('f3cd9bb8-aa90-4e26-95cc-132c72efa69e', 'ex-upsc-cse-2026', NULL, NULL, '2024', 'OBC', 1, 95, 0),
('fa0a564f-68d4-47cd-bff2-ee4b5bae64b6', 'ex-gate-2026', NULL, NULL, '2025', 'General', 1, 500, 1),
('fae155ac-32ef-4e3c-8e2d-d204fdcb1737', 'ex-jee-main-2026', NULL, NULL, '2025', 'OBC', 1000, 30000, 1),
('fd622ede-9940-4f5c-bf31-ddc48b1c1d89', 'ex-upsc-cse-2026', NULL, NULL, '2022', 'ST', 1, 1160, 1);

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

--
-- Dumping data for table `exam_dates`
--

INSERT INTO `exam_dates` (`id`, `exam_id`, `year`, `event_name`, `event_date`, `application_start`, `application_end`, `exam_date`, `result_date`, `admit_card_date`, `counselling_start`, `answer_key_date`, `is_tentative`) VALUES
('12b72b1c-3b4e-43ab-8ae0-3156e6a68586', 'ex-upsc-cse-2026', '2027', 'Personality Test (Interview) Begins', '2027-02-01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
('178f7988-3c5b-4da5-91fd-5a0bad0efae6', 'ex-neet-ug-2026', '2026', 'Application Window', NULL, '2025-12-01', '2026-01-15', '2026-05-04', '2026-06-14', NULL, NULL, NULL, 0),
('19e5b95d-7c00-4c29-8b19-7a266374b93d', 'ex-upsc-cse-2026', '2026', 'Last Date for Online Application', '2026-03-03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
('20ca5f59-0b94-4c02-84bd-c16ac03f8362', 'ex-jee-main-2026', '2026', 'Session 1 Registration Opens', NULL, '2025-11-01', '2025-12-15', '2026-01-22', '2026-02-12', NULL, NULL, NULL, 0),
('21f64dd3-1cd5-4b45-8825-560906d6e70c', 'ex-mat-2026', '2026', 'PBT Registration', NULL, '2026-01-01', '2026-02-15', '2026-02-28', '2026-03-15', NULL, NULL, NULL, 0),
('2ce9fe53-2bc6-4d0c-adbc-a37aac978f97', 'ex-cat-2026', '2026', 'Registration', NULL, '2026-08-01', '2026-09-15', '2026-11-29', '2027-01-05', NULL, NULL, NULL, 0),
('2e9d9162-9455-4375-b4ae-5586ba466d6c', 'ex-upsc-cse-2026', '2026', 'Mains Exam (Day 2)', '2026-09-19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
('30c82e30-516f-4285-8712-40eccf44f81c', 'ex-gate-2026', '2026', 'Registration', NULL, '2025-08-28', '2025-10-09', '2026-02-07', '2026-03-19', NULL, NULL, NULL, 0),
('40762f90-62f9-4162-84ad-e1ecd87468e1', 'ex-wbjee-2026', '2026', 'Registration', NULL, '2026-01-10', '2026-02-28', '2026-04-26', '2026-05-30', NULL, NULL, NULL, 0),
('41a91898-46c1-4281-9fc9-80079a3abfc6', 'ex-ts-eamcet-2026', '2026', 'Registration', NULL, '2026-02-01', '2026-03-15', '2026-05-07', '2026-06-15', NULL, NULL, NULL, 0),
('50455811-96ee-44ec-be9d-9ab9f6ba5fab', 'ex-upsc-cse-2026', '2026', 'Notification Released', '2026-02-05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
('595b46b2-2a50-4e95-a63b-a175de114b9f', 'ex-neet-pg-2026', '2026', 'Registration', NULL, '2026-04-01', '2026-04-30', '2026-06-23', '2026-07-15', NULL, NULL, NULL, 0),
('5ad721e7-7a27-4ed1-9de2-0abae350a075', 'ex-mht-cet-2026', '2026', 'Registration', NULL, '2025-12-01', '2026-01-31', '2026-04-15', '2026-06-01', NULL, NULL, NULL, 0),
('6dc04b4a-9222-47e3-8460-7cff0a99c625', 'ex-nata-2026', '2026', 'Test 2 Registration', NULL, '2026-04-01', '2026-06-01', '2026-06-28', '2026-07-15', NULL, NULL, NULL, 0),
('72ec62e7-f622-4d7b-bb8e-82b43f36677e', 'ex-upsc-cse-2026', '2026', 'Prelims Answer Key Released', '2026-06-05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
('7861322f-6213-42b0-8f88-0ac90364798b', 'ex-upsc-cse-2026', '2026', 'Mains Exam (Day 3)', '2026-09-20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
('80ba3950-36c2-47e9-842c-fa9df82e86e0', 'ex-upsc-cse-2026', '2026', 'Last Date for Mains Application', '2026-08-05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
('8738a2de-e069-4b68-8281-505b9f7e6404', 'ex-bitstat-2026', '2026', 'Registration', NULL, '2026-01-15', '2026-03-31', '2026-05-20', '2026-06-10', NULL, NULL, NULL, 0),
('8967bd22-f32d-47ce-ab63-c96b80726620', 'ex-upsc-cse-2026', '2026', 'Mains Application Opens', '2026-07-15', '2026-07-15', '2026-08-05', NULL, NULL, NULL, NULL, NULL, 1),
('8db1b676-4eff-4b4c-a6b3-f45276be6c87', 'ex-jee-main-2026', '2026', 'Session 2 Registration Opens', NULL, '2026-02-01', '2026-03-01', '2026-04-01', '2026-04-30', NULL, NULL, NULL, 0),
('9195af83-f33d-49d8-8c24-4cec24e1c61c', 'ex-upsc-cse-2026', '2026', 'Mains Exam (Day 4)', '2026-09-21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
('9971043c-a8d6-491c-8313-5876c7d9eb1a', 'ex-upsc-cse-2026', '2026', 'Prelims Admit Card Released', '2026-05-10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
('9cb8f9bb-b7f0-4f91-a8aa-b1ad4eb03cd8', 'ex-upsc-cse-2026', '2026', 'Mains Exam (Day 5)', '2026-09-22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
('9e5700a7-d9ac-4847-9dc5-f09d6ac0d78a', 'ex-xat-2026', '2026', 'Registration', NULL, '2025-08-15', '2025-11-30', '2026-01-05', '2026-01-20', NULL, NULL, NULL, 0),
('af96a5ed-4817-46e0-8256-eb3c63246295', 'ex-upsc-cse-2026', '2026', 'Mains Exam (Day 1)', '2026-09-18', NULL, NULL, '2026-09-18', NULL, NULL, NULL, NULL, 0),
('afeae1e8-2a22-4953-948c-6709bf61cec0', 'ex-gmat-2026', '2026', 'Test Window', NULL, '2025-10-01', '2026-12-31', '2026-01-01', NULL, NULL, NULL, NULL, 0),
('b1ccee34-e059-4381-88b6-e3f50c743d26', 'ex-clat-2026', '2025', 'Registration', NULL, '2025-10-01', '2025-11-30', '2025-12-08', '2025-12-22', NULL, NULL, NULL, 0),
('b4d625dc-8acd-45a4-8615-5c8cc9d4e8eb', 'ex-upsc-cse-2026', '2026', 'Mains Admit Card Released', '2026-09-05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
('b6f2b220-38d0-41c1-8757-178d240f8255', 'ex-upsc-cse-2026', '2026', 'Prelims Exam', '2026-05-24', NULL, NULL, '2026-05-24', NULL, NULL, NULL, NULL, 0),
('b7c0e3b1-8926-46cf-a8b7-aa16566f91e3', 'ex-upsc-cse-2026', '2026', 'Online Application Opens', '2026-02-11', '2026-02-11', '2026-03-03', NULL, NULL, NULL, NULL, NULL, 0),
('c301f5e7-690d-4d0c-a834-51fa26e03bde', 'ex-snap-2026', '2025', 'Registration', NULL, '2025-08-01', '2025-11-22', '2025-12-08', '2025-12-20', NULL, NULL, NULL, 0),
('c900d7ca-eee1-4582-b374-01ff6db43850', 'ex-cmat-2026', '2026', 'Registration', NULL, '2026-02-01', '2026-03-01', '2026-03-25', '2026-04-10', NULL, NULL, NULL, 0),
('cc94b11f-e94b-466d-8054-f2d7d04e7ab8', 'ex-jee-adv-2026', '2026', 'Registration Opens', NULL, '2026-05-01', '2026-05-15', '2026-06-01', '2026-06-15', NULL, NULL, NULL, 0),
('d2ce5d21-f7f7-4fe5-89cc-fe19d2f94e58', 'ex-nata-2026', '2026', 'Test 1 Registration', NULL, '2026-01-15', '2026-03-15', '2026-04-05', '2026-04-20', NULL, NULL, NULL, 0),
('d41b38c8-5872-48a8-988c-2f9f4d0f7d72', 'ex-upsc-cse-2026', '2026', 'Prelims Result Declared', '2026-07-10', NULL, NULL, NULL, '2026-07-10', NULL, NULL, NULL, 1),
('e6d42cca-5dd9-40fe-a067-0481e8366b72', 'ex-ssc-cgl-2026', '2026', 'Tier 1 Registration', NULL, '2026-05-01', '2026-06-01', '2026-07-15', '2026-09-01', NULL, NULL, NULL, 0),
('ec5d7f54-d0fa-437c-b44b-14a3f6388f9f', 'ex-mat-2026', '2026', 'CBT Registration', NULL, '2026-01-01', '2026-03-01', '2026-03-14', '2026-03-30', NULL, NULL, NULL, 0),
('f213c7a0-505f-4e17-9027-c20e9ce8df36', 'ex-upsc-cse-2026', '2026', 'Mains Result Declared', '2026-12-15', NULL, NULL, NULL, '2026-12-15', NULL, NULL, NULL, 1),
('f71f1d0c-acec-46e1-89a6-b9cba0cb3f7f', 'ex-upsc-cse-2026', '2026', 'Application Correction Window', '2026-03-10', '2026-03-10', '2026-03-17', NULL, NULL, NULL, NULL, NULL, 0),
('f7a41767-54a9-4841-8d21-83fca78e5828', 'ex-neet-pg-2026b', '2026', 'Registration', NULL, '2026-04-01', '2026-04-30', '2026-06-23', '2026-07-15', NULL, NULL, NULL, 0),
('fdb60161-9563-4c12-b003-8487b9a05dde', 'ex-inicet-2026', '2026', 'Registration', NULL, '2026-03-01', '2026-03-31', '2026-05-17', '2026-06-10', NULL, NULL, NULL, 0),
('fe3e7740-5df4-4445-b0d4-f8bac9e099f5', 'ex-upsc-cse-2026', '2027', 'Final Result Declared', '2027-04-15', NULL, NULL, NULL, '2027-04-15', NULL, NULL, NULL, 1),
('ffdc74fb-7a6a-4058-b48d-c46c3ea6ed86', 'ex-cuet-ug-2026', '2026', 'Registration', NULL, '2026-02-01', '2026-03-31', '2026-05-15', '2026-06-30', NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `exam_resources`
--

CREATE TABLE `exam_resources` (
  `id` char(36) NOT NULL,
  `exam_id` char(36) NOT NULL,
  `sample_papers_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sample_papers_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
('03344709-762b-4f50-8c3f-8c3d03d37640', 'ex-cat-2026', 'DILR', 'Logical Reasoning', '[\"Arrangements\",\"Blood Relations\",\"Syllogisms\",\"Coding-Decoding\",\"Puzzles\",\"Direction Sense\"]', 16, NULL),
('03368a63-f208-458b-9b65-5a96fec07392', 'ex-upsc-cse-2026', 'GS Paper 2', 'Governance', '[\"Indian Constitution\",\"Political System\",\"Panchayati Raj\",\"Public Policy\",\"Rights Issues\"]', 10, NULL),
('0d401d46-e84d-419d-a5d1-e18db4877e03', 'ex-ts-eamcet-2026', 'Mathematics', 'Algebra', '[\"Matrices\",\"Determinants\",\"Complex Numbers\",\"Quadratic Equations\",\"Permutations\"]', 20, NULL),
('0d9d4000-0fc0-4e07-a4db-e773d5050095', 'ex-neet-ug-2026', 'Physics', 'Class 12 Electromagnetism', '[\"Electric Charges & Fields\",\"Electrostatic Potential\",\"Current Electricity\",\"Moving Charges\",\"Magnetism\",\"EMI\",\"AC\",\"Electromagnetic Waves\"]', 25, NULL),
('12476c6a-b526-43f5-b064-e5d7b9d72f8d', 'ex-cat-2026', 'VARC', 'Verbal Ability', '[\"Para Jumbles\",\"Para Summary\",\"Odd One Out\",\"Sentence Correction\",\"Fill in the Blanks\"]', 10, NULL),
('1784e0e0-4094-40f9-8107-723c0dc4d016', 'ex-upsc-cse-2026', 'GS Paper 1', 'Geography', '[\"Indian & World Geography\",\"Physical Geography\",\"Human Geography\",\"Geographical phenomena\"]', 10, NULL),
('1df0b361-8156-4531-bfef-515dd9f13840', 'ex-cat-2026', 'QA', 'Algebra', '[\"Linear Equations\",\"Quadratic Equations\",\"Inequalities\",\"Functions\",\"Logarithms\"]', 14, NULL),
('1fe1d0da-caa8-4c62-906b-5a84b2f2bbf6', 'ex-wbjee-2026', 'Physics', 'Electricity & Magnetism', '[\"Electrostatics\",\"Current Electricity\",\"Magnetism\",\"EMI\",\"AC\"]', 15, NULL),
('20d99226-7450-40a0-9f25-f6d7c44c9047', 'ex-cat-2026', 'QA', 'Arithmetic', '[\"Percentages\",\"Profit & Loss\",\"SI & CI\",\"Time Speed Distance\",\"Time & Work\",\"Averages\",\"Ratio & Proportion\"]', 14, NULL),
('2632ec5b-336a-4457-8a18-154d8f6a40b1', 'ex-bitstat-2026', 'Physics', 'Mechanics', '[\"Units\",\"Kinematics\",\"Laws of Motion\",\"Work Energy\",\"Rotational\",\"Gravitation\",\"Properties of Matter\"]', 15, NULL),
('2b25a0df-9a1d-44fe-8738-33bf6986d8e9', 'ex-clat-2026', 'English', 'Language & Comprehension', '[\"Reading Comprehension passages\",\"Grammar\",\"Vocabulary\",\"Fill in the blanks\",\"Incorrect sentences\"]', 20, NULL),
('2e742d28-be70-4751-82a5-e12fcbc1a62b', 'ex-clat-2026', 'Current Affairs', 'General Knowledge', '[\"Static GK\",\"Current events of national and international importance\",\"Arts & Culture\",\"Historical events\"]', 25, NULL),
('2f18786f-f9c5-4416-a670-2c101ac2271e', 'ex-wbjee-2026', 'Chemistry', 'Physical Chemistry', '[\"Atomic Structure\",\"Chemical Bonding\",\"Thermodynamics\",\"Equilibrium\",\"Electrochemistry\",\"Kinetics\"]', 15, NULL),
('2f89ec69-56e1-4230-aeda-e355d059df59', 'ex-wbjee-2026', 'Mathematics', 'Algebra', '[\"Sets\",\"Relations\",\"Complex Numbers\",\"Quadratic Equations\",\"Permutation & Combination\",\"Binomial Theorem\",\"Sequences & Series\"]', 30, NULL),
('314b2665-2e4c-4f4c-8c4c-935c656c9809', 'ex-mht-cet-2026', 'Physics', 'Modern Physics', '[\"Dual Nature\",\"Atoms & Nuclei\",\"Semiconductors\",\"Communication Systems\"]', 10, NULL),
('33a789a0-35a6-4308-8144-89d2ddb139fc', 'ex-ts-eamcet-2026', 'Physics', 'Mechanics', '[\"Motion\",\"Laws of Motion\",\"Work Energy Power\",\"Gravitation\",\"Properties of Matter\"]', 15, NULL),
('3402ae9f-5b63-4774-895c-6c7355e6c7b4', 'ex-neet-ug-2026', 'Chemistry', 'Physical Chemistry', '[\"Some Basic Concepts\",\"Atomic Structure\",\"Classification\",\"Chemical Bonding\",\"States of Matter\",\"Thermodynamics\",\"Equilibrium\",\"Redox Reactions\",\"Electrochemistry\",\"Kinetics\",\"Surface Chemistry\",\"Solid State\",\"Solutions\"]', 30, NULL),
('37245ea1-4c25-4667-9c5c-feb4437950d5', 'ex-jee-main-2026', 'Physics', 'Modern Physics', '[\"Dual Nature of Matter\",\"Atoms & Nuclei\",\"Electronic Devices\",\"Semiconductors\"]', 15, NULL),
('3ad6068c-a3d9-4c3f-8289-dac332391617', 'ex-gate-2026', 'General Aptitude', 'Verbal Ability', '[\"English Grammar\",\"Sentence Completion\",\"Verbal Analogies\",\"Word Groups\",\"Critical Reasoning\",\"Verbal Deduction\"]', 15, NULL),
('3b03b92d-4f19-4c93-9ffb-ec71e25cf392', 'ex-jee-main-2026', 'Mathematics', 'Calculus', '[\"Limits & Continuity\",\"Differentiability\",\"Differentiation\",\"Integration\",\"Differential Equations\",\"Application of Derivatives\",\"Area Under Curves\"]', 35, NULL),
('3dcc83da-40fc-40e4-a82d-aedf0b0a5aa8', 'ex-bitstat-2026', 'Physics', 'Modern Physics', '[\"Dual Nature\",\"Atoms\",\"Nuclei\",\"Semiconductors\",\"Optics\"]', 10, NULL),
('3f68d92f-27b5-406f-a345-4ac5b5904ea5', 'ex-neet-ug-2026', 'Zoology', 'Diversity & Animal Kingdom', '[\"Animal Kingdom\",\"Structural Organisation\",\"Cockroach Anatomy\"]', 10, NULL),
('43321243-c43f-4e94-9707-53c4f2e609ef', 'ex-ts-eamcet-2026', 'Mathematics', 'Calculus & Coordinate Geometry', '[\"Limits\",\"Differentiation\",\"Integration\",\"Straight Lines\",\"Circles\",\"Conics\"]', 30, NULL),
('47e6dc19-8bae-41a2-a75b-da2dfc83ad57', 'ex-jee-main-2026', 'Physics', 'Electromagnetism', '[\"Electrostatics\",\"Current Electricity\",\"Magnetic Effects of Current\",\"EMI & AC\",\"Electromagnetic Waves\"]', 25, NULL),
('49919fed-b6e5-453c-9b67-3f356a2efd11', 'ex-neet-ug-2026', 'Physics', 'Physics Additional Topics', '[\"Optics\",\"Dual Nature\",\"Atoms & Nuclei\",\"Semiconductor Electronics\"]', 15, NULL),
('4ce2abbc-0f3b-4baa-a0db-6ffce4cad027', 'ex-mht-cet-2026', 'Physics', 'Mechanics', '[\"Force\",\"Friction\",\"Laws of Motion\",\"Work Energy Power\",\"Gravitation\",\"Rotational Motion\"]', 20, NULL),
('4e25543c-0814-4798-be79-e2f45da818e3', 'ex-ts-eamcet-2026', 'Physics', 'Electricity & Magnetism', '[\"Electrostatics\",\"Current Electricity\",\"Magnetism\",\"EMI\",\"AC\"]', 20, NULL),
('4fa6fe68-dff3-49ca-8674-d3668661194e', 'ex-neet-ug-2026', 'Physics', 'Class 11 Mechanics', '[\"Units & Measurements\",\"Motion in Straight Line\",\"Motion in Plane\",\"Laws of Motion\",\"Work Energy Power\",\"System of Particles\",\"Rotational Motion\",\"Gravitation\",\"Mechanical Properties\",\"Thermal Properties\",\"Thermodynamics\",\"Kinetic Theory\"]', 20, NULL),
('522c673b-163b-46a1-8543-76ed2d248323', 'ex-jee-main-2026', 'Physics', 'Optics & Waves', '[\"Ray Optics\",\"Wave Optics\",\"Sound Waves\",\"Superposition\"]', 15, NULL),
('5575bfc5-5f06-434d-b4c2-3c89a7e4db3a', 'ex-cat-2026', 'QA', 'Geometry & Mensuration', '[\"Lines\",\"Triangles\",\"Circles\",\"Polygons\",\"Areas\",\"Volumes\"]', 8, NULL),
('57130fba-87b7-46db-8650-be989dc9b009', 'ex-mht-cet-2026', 'Physics', 'Heat & Thermodynamics', '[\"Thermal Properties\",\"Kinetic Theory of Gases\",\"Thermodynamics\"]', 10, NULL),
('6386c0a3-129d-483f-94ed-1752f3ca09b2', 'ex-neet-ug-2026', 'Chemistry', 'Inorganic Chemistry', '[\"Hydrogen\",\"s-Block\",\"p-Block (Group 13-18)\",\"d & f Block\",\"Coordination Compounds\",\"Environmental Chemistry\"]', 25, NULL),
('64b108f5-5a59-4159-bd68-502a6bfa2667', 'ex-neet-ug-2026', 'Chemistry', 'Organic Chemistry', '[\"GOC\",\"Hydrocarbons\",\"Haloalkanes & Haloarenes\",\"Alcohols Phenols Ethers\",\"Aldehydes Ketones\",\"Carboxylic Acids\",\"Amines\",\"Biomolecules\",\"Polymers\",\"Chemistry in Everyday Life\"]', 25, NULL),
('6a223195-7fc1-4085-b20e-cd1306cb8c4a', 'ex-upsc-cse-2026', 'GS Paper 1', 'Indian Society & Polity', '[\"Indian Polity & Governance\",\"Social Justice\",\"International Relations\"]', 10, NULL),
('6a4c1453-289e-40c7-bff7-26014fb5679d', 'ex-upsc-cse-2026', 'GS Paper 3', 'Economy & Technology', '[\"Indian Economy\",\"Planning\",\"Mobilization of Resources\",\"Technology\",\"Biodiversity\",\"Environment\",\"Security\"]', 12, NULL),
('6c94517f-0e90-4707-9942-7d6c53ef40e8', 'ex-cat-2026', 'DILR', 'Data Interpretation', '[\"Tables\",\"Bar Graphs\",\"Line Charts\",\"Pie Charts\",\"Venn Diagrams\",\"Caselets\"]', 16, NULL),
('6d684022-bc84-4d96-8b50-94951278dcf0', 'ex-xat-2026', 'Part 1', 'Quantitative Aptitude & DI', '[\"Arithmetic\",\"Algebra\",\"Geometry\",\"Data Interpretation (Bar Graphs\",\"Pie Charts\",\"Tables)\"]', 28, NULL),
('6f5282d6-46e1-445a-ab9b-499881cb3793', 'ex-mht-cet-2026', 'Physics', 'Waves & Oscillations', '[\"Simple Harmonic Motion\",\"Waves\",\"Sound\",\"Superposition\"]', 10, NULL),
('7206ff6f-a134-4ba2-b7b5-24f4eb4a3a3d', 'ex-neet-ug-2026', 'Botany', 'Plant Physiology', '[\"Transport in Plants\",\"Mineral Nutrition\",\"Photosynthesis\",\"Respiration\",\"Plant Growth & Development\"]', 15, NULL),
('78aa2510-35d4-4c5d-85a6-084100f62026', 'ex-mht-cet-2026', 'Chemistry', 'Inorganic Chemistry', '[\"s-Block\",\"p-Block\",\"d-Block\",\"Coordination Compounds\",\"Environmental Chemistry\"]', 20, NULL),
('82223642-1f8a-43f2-95d7-dddd144ff7e0', 'ex-cat-2026', 'VARC', 'Reading Comprehension', '[\"Inference\",\"Main Idea\",\"Vocabulary in Context\",\"Tone & Attitude\",\"Author Argument\"]', 14, NULL),
('85dd8ac4-05ca-4db5-a046-b8f716bf831d', 'ex-upsc-cse-2026', 'Optional', 'Optional Subject', '[\"Two papers on chosen optional subject (250 marks each)\"]', 20, NULL),
('8a6cb766-3129-485d-b042-f841c2ab8c23', 'ex-bitstat-2026', 'Chemistry', 'Physical Chemistry', '[\"Atomic Structure\",\"Chemical Bonding\",\"States of Matter\",\"Thermodynamics\",\"Equilibrium\"]', 15, NULL),
('8a8a4d6f-7d6f-4569-adc1-9ba1a322374d', 'ex-bitstat-2026', 'Chemistry', 'Organic Chemistry', '[\"GOC\",\"Hydrocarbons\",\"Functional Groups\",\"Polymers\",\"Biomolecules\"]', 10, NULL),
('8b40b8cb-329f-4991-ac90-de0cf59f5881', 'ex-xat-2026', 'Part 1', 'Decision Making', '[\"Caselets\",\"Situational Decision Making\",\"Mathematical Reasoning in decision contexts\"]', 21, NULL),
('8ff8a8d2-2d9e-467e-baa3-9edd1fd1e4bd', 'ex-gate-2026', 'General Aptitude', 'Numerical Ability', '[\"Numerical Computation\",\"Numerical Estimation\",\"Numerical Reasoning\",\"Data Interpretation\"]', 15, NULL),
('91d25a91-8f1f-43fe-ab5d-e0aec8637f0b', 'ex-bitstat-2026', 'English', 'Language Skills', '[\"Reading Comprehension\",\"Grammar\",\"Vocabulary\",\"Synonyms\",\"Antonyms\"]', 5, NULL),
('924b4096-a2d8-428b-afbb-25c6ea4f38e6', 'ex-neet-ug-2026', 'Botany', 'Diversity & Classification', '[\"The Living World\",\"Biological Classification\",\"Plant Kingdom\",\"Morphology\",\"Anatomy\"]', 15, NULL),
('92de22d5-4c6c-4ab9-a2d8-c027a8b4d1b5', 'ex-jee-main-2026', 'Chemistry', 'Inorganic Chemistry', '[\"Classification of Elements\",\"Hydrogen\",\"s-Block\",\"p-Block\",\"d-Block\",\"f-Block\",\"Coordination Compounds\",\"Environmental Chemistry\"]', 35, NULL),
('99dc07ba-b9ba-471c-9bc1-8edd5e98d7bb', 'ex-neet-ug-2026', 'Zoology', 'Human Physiology', '[\"Digestion\",\"Breathing\",\"Body Fluids\",\"Excretion\",\"Locomotion\",\"Neural Control\",\"Chemical Coordination\",\"Reproduction\"]', 20, NULL),
('9b84cccc-cdd2-4d41-8246-6a016971e3fc', 'ex-clat-2026', 'Quantitative Techniques', 'Mathematics', '[\"Short propositions\",\"graphs\",\"charts\",\"Elementary algebra\",\"Mensuration\",\"Statistical estimation\"]', 10, NULL),
('9bd502f9-bf4e-47df-9946-7aac05b9187a', 'ex-jee-main-2026', 'Chemistry', 'Organic Chemistry', '[\"GOC\",\"Hydrocarbons\",\"Haloalkanes\",\"Alcohols\",\"Aldehydes\",\"Carboxylic Acids\",\"Amines\",\"Biomolecules\",\"Polymers\"]', 30, NULL),
('9d1b3f46-6ee1-4d6d-926c-71b1cc73891c', 'ex-mht-cet-2026', 'Physics', 'Electricity & Magnetism', '[\"Electrostatics\",\"Current Electricity\",\"Magnetic Effects\",\"EMI\",\"AC\",\"Electromagnetic Waves\"]', 20, NULL),
('a783a4da-1c8e-48e4-ae5e-98db72cb53a7', 'ex-gate-2026', 'Engineering Mathematics', 'Core Topics', '[\"Linear Algebra\",\"Calculus\",\"Differential Equations\",\"Complex Analysis\",\"Probability & Statistics\",\"Discrete Mathematics\"]', 13, NULL),
('a88ea184-be3a-462b-a7ba-ffc615da4e7b', 'ex-xat-2026', 'Part 1', 'Verbal & Logical Reasoning', '[\"Reading Comprehension\",\"Para Jumbles\",\"Critical Reasoning\",\"Inferences\",\"Analogies\",\"Vocabulary\"]', 26, NULL),
('ac95baa0-ba2f-42c4-b563-3c885283a25c', 'ex-mht-cet-2026', 'Chemistry', 'Physical Chemistry', '[\"Solid State\",\"Solutions\",\"Electrochemistry\",\"Chemical Kinetics\",\"Surface Chemistry\",\"Thermodynamics\"]', 20, NULL),
('ad357168-47ac-4795-8505-d52fd03d309f', 'ex-bitstat-2026', 'Mathematics', 'Calculus', '[\"Limits\",\"Differentiability\",\"Integration\",\"Differential Equations\",\"Area\"]', 20, NULL),
('b4ed73c2-193e-4613-96fa-57f8c40fe256', 'ex-jee-main-2026', 'Mathematics', 'Coordinate Geometry & Vectors', '[\"Straight Lines\",\"Circles\",\"Conic Sections\",\"3D Geometry\",\"Vector Algebra\"]', 20, NULL),
('b68bd281-9520-4269-8a6f-f2ce4b437779', 'ex-jee-main-2026', 'Mathematics', 'Algebra', '[\"Sets\",\"Relations & Functions\",\"Complex Numbers\",\"Quadratic Equations\",\"Sequences & Series\",\"Binomial Theorem\",\"Matrices & Determinants\",\"Permutations & Combinations\"]', 30, NULL),
('c01c87ce-f248-49f9-8f7b-3cb0521112af', 'ex-xat-2026', 'Part 2', 'General Knowledge', '[\"Economics\",\"Finance\",\"Business\",\"Science & Tech\",\"History\",\"Geography\",\"Sports\",\"Awards\"]', 25, NULL),
('c1544c22-1a90-4e6e-96a0-3c1bedfca897', 'ex-bitstat-2026', 'Mathematics', 'Algebra', '[\"Sets\",\"Complex Numbers\",\"Quadratics\",\"Sequences\",\"Matrices\",\"Determinants\"]', 15, NULL),
('c289b186-8820-479e-9424-c0fda91ae7b1', 'ex-wbjee-2026', 'Physics', 'Mechanics & Waves', '[\"Kinematics\",\"Laws of Motion\",\"Work Energy\",\"Rotational Motion\",\"Gravitation\",\"Oscillations\",\"Waves\"]', 15, NULL),
('c9adca6b-e3b5-4b43-8595-65225a446333', 'ex-clat-2026', 'Logical Reasoning', 'Analytical Reasoning', '[\"Syllogisms\",\"Logical sequences\",\"Analogies\",\"Series completion\",\"Blood relations\",\"Direction sense\"]', 20, NULL),
('ceedd7dc-b049-435f-a63a-0b211b66001d', 'ex-upsc-cse-2026', 'GS Paper 1', 'Indian History', '[\"Modern Indian History\",\"Post-independence Consolidation\",\"World History events\"]', 12, NULL),
('cf01bedc-7b32-4158-b505-9206109c68c0', 'ex-jee-main-2026', 'Chemistry', 'Physical Chemistry', '[\"Some Basic Concepts\",\"Atomic Structure\",\"Chemical Bonding\",\"States of Matter\",\"Thermodynamics\",\"Equilibrium\",\"Electrochemistry\",\"Kinetics\",\"Surface Chemistry\"]', 35, NULL),
('d24fbd62-6cc3-4c81-b60f-39872bdcdaf1', 'ex-ts-eamcet-2026', 'Chemistry', 'Physical Chemistry', '[\"Atomic Structure\",\"Chemical Bonding\",\"Thermodynamics\",\"Equilibrium\",\"Kinetics\"]', 15, NULL),
('d2eadeab-8ddf-4245-a387-ae9e7ec8bd11', 'ex-wbjee-2026', 'Mathematics', 'Calculus & Geometry', '[\"Limits\",\"Continuity\",\"Differentiation\",\"Integration\",\"Coordinate Geometry\",\"3D Geometry\"]', 40, NULL),
('d3747d45-5338-4f53-b72c-cd28518ad463', 'ex-clat-2026', 'Legal Reasoning', 'Legal Aptitude', '[\"Legal propositions\",\"Legal maxims\",\"Fact situations\",\"Principles and facts\",\"Conclusions\"]', 25, NULL),
('d38cf546-3dbc-43d0-b5b7-cd6253adf24b', 'ex-ts-eamcet-2026', 'Chemistry', 'Organic Chemistry', '[\"GOC\",\"Hydrocarbons\",\"Halo Compounds\",\"Alcohols\",\"Aldehydes\",\"Carboxylic Acids\"]', 15, NULL),
('d651fda0-046c-4a12-9f0f-cba1642c9b3f', 'ex-ts-eamcet-2026', 'Physics', 'Modern Physics & Optics', '[\"Dual Nature\",\"Atoms\",\"Nuclei\",\"Semiconductors\",\"Ray & Wave Optics\"]', 15, NULL),
('d924fead-0459-47e1-8d4e-5d7580f11c05', 'ex-jee-main-2026', 'Mathematics', 'Probability & Statistics', '[\"Probability\",\"Statistics\",\"Mathematical Reasoning\"]', 15, NULL),
('db19434f-f9a2-4511-925c-6f942a015ccd', 'ex-bitstat-2026', 'Chemistry', 'Inorganic Chemistry', '[\"Periodic Table\",\"s-Block\",\"p-Block\",\"d-Block\",\"Coordination Compounds\"]', 10, NULL),
('e17db2c4-36eb-4c90-9c82-8147a0d3e6d2', 'ex-upsc-cse-2026', 'GS Paper 1', 'Indian Heritage & Culture', '[\"Indian Art Forms\",\"Literature\",\"Architecture from ancient to modern times\"]', 8, NULL),
('e441aebc-d191-4914-b525-73d63f45980e', 'ex-gate-2026', 'Subject Specific', 'Core Discipline', '[\"As per GATE syllabus for the chosen paper - detailed topic coverage\"]', 57, NULL),
('e47786e0-91d9-44a0-90e6-e9492c608ac7', 'ex-jee-main-2026', 'Physics', 'Mechanics', '[\"Kinematics\",\"Laws of Motion\",\"Work Energy Power\",\"Rotational Motion\",\"Gravitation\",\"Properties of Solids & Liquids\",\"Thermodynamics\"]', 25, NULL),
('e769665a-8207-4c17-84a6-906e4a4767a6', 'ex-mht-cet-2026', 'Chemistry', 'Organic Chemistry', '[\"GOC\",\"Haloalkanes\",\"Alcohols\",\"Aldehydes\",\"Carboxylic Acids\",\"Amines\",\"Polymers\",\"Biomolecules\"]', 20, NULL),
('ea7fcc6e-8ff5-44f3-a69b-e2ed7c65f145', 'ex-cat-2026', 'QA', 'Number Systems & Modern Math', '[\"Number Theory\",\"Set Theory\",\"Permutation & Combination\",\"Probability\"]', 8, NULL),
('ea8644d0-a695-4563-9b98-703b9848661a', 'ex-ts-eamcet-2026', 'Chemistry', 'Inorganic Chemistry', '[\"p-Block Elements\",\"d-Block Elements\",\"Coordination Compounds\"]', 10, NULL),
('eca89118-433f-43cf-8238-4844b4187848', 'ex-mht-cet-2026', 'Mathematics', 'Algebra', '[\"Sets\",\"Matrices\",\"Determinants\",\"Trigonometry\",\"Vectors\",\"3D Geometry\"]', 30, NULL),
('f42ff1c3-7f12-4144-9664-656205579c51', 'ex-mht-cet-2026', 'Mathematics', 'Calculus', '[\"Limits\",\"Continuity\",\"Differentiation\",\"Integration\",\"Differential Equations\",\"Area Under Curves\"]', 30, NULL),
('f633f430-8824-4201-9df7-a014266c4606', 'ex-mht-cet-2026', 'Mathematics', 'Statistics & Probability', '[\"Mean\",\"Median\",\"Mode\",\"Standard Deviation\",\"Probability\",\"Random Variables\"]', 10, NULL),
('f7130398-1e63-45fe-b52c-1fbe9cdc13b6', 'ex-wbjee-2026', 'Chemistry', 'Organic & Inorganic', '[\"GOC\",\"Hydrocarbons\",\"Halogen Compounds\",\"d-Block\",\"p-Block Elements\"]', 15, NULL),
('fbf7a0c5-dde4-486f-8b16-862b6f68857b', 'ex-bitstat-2026', 'Physics', 'Heat & Thermodynamics', '[\"Thermal Properties\",\"Kinetic Theory\",\"Thermodynamics\"]', 10, NULL),
('ffbf3a9e-1f94-4f52-a0d2-bb03066b185e', 'ex-upsc-cse-2026', 'GS Paper 4', 'Ethics & Aptitude', '[\"Ethics & Human Interface\",\"Attitude\",\"Aptitude\",\"Emotional Intelligence\",\"Moral Thinkers\",\"Public Service Ethics\"]', 8, NULL),
('fffcd25a-0b7f-42cf-bfe6-3e276f41b251', 'ex-bitstat-2026', 'Physics', 'Electricity & Magnetism', '[\"Electrostatics\",\"Current Electricity\",\"Magnetic Effects\",\"EMI\",\"AC\"]', 20, NULL);

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
  `follow_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `experts`
--

INSERT INTO `experts` (`id`, `expert_name`, `expert_designation`, `expert_college`, `verified_badge`, `answer_count`, `profile_url`, `specialization`, `linkedin_url`, `response_rate_pct`, `avg_response_hours`, `follow_count`, `created_at`, `updated_at`) VALUES
('3805fe9a-4ee8-477a-bdef-25f8d687caed', 'Dr. Ananya Sharma', 'Senior Admission Counselor', 'IIT Delhi', 1, 28, 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=120&h=120&fit=crop', 'Engineering Admissions', NULL, 98, 1, 0, '2026-06-22 05:03:53', '2026-06-22 05:03:53'),
('4e51d9f6-3f43-4889-b8d8-cc1b68cf34d8', 'Amit Verma', 'Study Abroad Consultant', 'Amity University', 1, 71, 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=120&h=120&fit=crop', 'International Admissions', NULL, 92, 2, 0, '2026-06-22 05:03:53', '2026-06-22 05:03:53'),
('5630adab-6aad-41af-a191-79f28a3c5cae', 'Prof. Rajesh Kumar', 'Academic Director', 'IIM Bangalore', 1, 120, 'https://images.unsplash.com/photo-1560250097-0b93528c311a?w=120&h=120&fit=crop', 'MBA & Management', NULL, 85, 4, 1, '2026-06-22 05:03:53', '2026-06-22 11:17:47'),
('b75a8e11-ccfd-4773-916a-c0a00cabb145', 'Dr. Meera Joshi', 'Career Guidance Expert', 'AIIMS Delhi', 1, 81, 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=120&h=120&fit=crop', 'Medical Admissions', NULL, 90, 11, 1, '2026-06-22 05:03:53', '2026-06-22 11:35:14');

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
-- Table structure for table `follows`
--

CREATE TABLE `follows` (
  `id` varchar(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `followable_type` enum('question','expert') NOT NULL,
  `followable_id` varchar(36) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `follows`
--

INSERT INTO `follows` (`id`, `user_id`, `followable_type`, `followable_id`, `created_at`) VALUES
('24bd7374-9927-493d-a420-2a443a48e473', '64e20c70-d8d7-402f-a700-53c759a659d4', 'expert', '5630adab-6aad-41af-a191-79f28a3c5cae', '2026-06-22 11:17:47'),
('6490095c-5851-496a-9578-788fc7724afe', 'user-1234-uuid', 'question', 'que00001-0000-0000-0000-000000000001', '2026-06-22 07:17:52'),
('96bf8649-a92f-4934-aeb6-d8da25e31a64', 'user-1234-uuid', 'question', 'que00001-0000-0000-0000-000000000004', '2026-06-22 07:17:57'),
('a1766be1-4712-487b-8353-d1dcafe16541', '64e20c70-d8d7-402f-a700-53c759a659d4', 'expert', 'b75a8e11-ccfd-4773-916a-c0a00cabb145', '2026-06-22 11:35:14'),
('ada6d7d6-49ca-450f-ba9f-a499a266c83f', '64e20c70-d8d7-402f-a700-53c759a659d4', 'question', 'que00001-0000-0000-0000-000000000001', '2026-06-22 10:00:34');

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
(32, 'Massachusetts Institute of Technology', 'mit-cambridge', 'USA', 1, NULL, 3.9, 57986.00, 2500.00, '[\"September\",\"February\"]', 'https://www.mit.edu', 7, 100, 325, 1, NULL, 'MIT is a world-renowned research university known for science, engineering, and technology.', 'Cambridge', 'Private', 75.00, 70, NULL, '3.9', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(33, 'Stanford University', 'stanford-california', 'USA', 2, NULL, 3.6, 56169.00, 2800.00, '[\"September\"]', 'https://www.stanford.edu', 7, 100, 320, 1, NULL, 'Stanford is a leading research university in Silicon Valley known for entrepreneurship.', 'Palo Alto', 'Private', 90.00, 70, NULL, '3.9', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(34, 'Harvard University', 'harvard-cambridge', 'USA', 4, NULL, 3.2, 54768.00, 2600.00, '[\"September\"]', 'https://www.harvard.edu', 7, 100, 325, 1, NULL, 'Harvard is the oldest and most prestigious university in the world.', 'Cambridge', 'Private', 75.00, 70, NULL, '3.9', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(35, 'California Institute of Technology', 'caltech-pasadena', 'USA', 6, NULL, 2.7, 58680.00, 2400.00, '[\"September\"]', 'https://www.caltech.edu', 7, 100, 330, 1, NULL, 'Caltech is a world-renowned science and engineering research institute.', 'Pasadena', 'Private', 85.00, 70, NULL, '3.9', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(36, 'University of Chicago', 'uchicago-chicago', 'USA', 11, NULL, 5.3, 60963.00, 2200.00, '[\"September\",\"January\"]', 'https://www.uchicago.edu', 7, 104, 325, 1, NULL, 'Leading research university known for rigorous academics and economics.', 'Chicago', 'Private', 75.00, 70, NULL, '3.9', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(37, 'University of Pennsylvania', 'upenn-philadelphia', 'USA', 13, NULL, 5.7, 58620.00, 2100.00, '[\"August\"]', 'https://www.upenn.edu', 7, 100, 320, 1, NULL, 'Ivy League university known for Wharton business school.', 'Philadelphia', 'Private', 75.00, 70, NULL, '3.8', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(38, 'UC Berkeley', 'ucb-berkeley', 'USA', 15, NULL, 11.6, 44008.00, 2300.00, '[\"August\",\"January\"]', 'https://www.berkeley.edu', 7, 100, 310, 1, NULL, 'Top public university known for research and innovation.', 'Berkeley', 'Public', 80.00, 64, NULL, '3.7', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(39, 'UCLA', 'ucla-losangeles', 'USA', 29, NULL, 8.7, 43022.00, 2200.00, '[\"September\"]', 'https://www.ucla.edu', 7, 100, 300, 1, NULL, 'Leading public research university in Los Angeles.', 'Los Angeles', 'Public', 80.00, 64, NULL, '3.7', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(40, 'Columbia University', 'columbia-newyork', 'USA', 19, NULL, 3.7, 63530.00, 2800.00, '[\"September\",\"January\"]', 'https://www.columbia.edu', 7, 101, 320, 1, NULL, 'Ivy League university in New York City.', 'New York', 'Private', 85.00, 69, NULL, '3.7', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(41, 'Yale University', 'yale-newhaven', 'USA', 16, NULL, 4.4, 59950.00, 2200.00, '[\"December\"]', 'https://www.yale.edu', 7, 100, 325, 1, NULL, 'Known for outstanding arts, humanities, and law programs.', 'New Haven', 'Private', 80.00, 70, NULL, '3.9', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(42, 'University of Oxford', 'oxford-oxford', 'UK', 2, NULL, 14.5, 42083.00, 1800.00, '[\"September\",\"January\"]', 'https://www.ox.ox.ac.uk', 7.5, 110, 310, 1, NULL, 'Oldest university in the English-speaking world.', 'Oxford', 'Public', 75.00, 67, NULL, '3.7', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(43, 'University of Cambridge', 'cambridge-cambridge', 'UK', 3, NULL, 18.6, 37023.00, 1700.00, '[\"September\"]', 'https://www.cam.ac.uk', 7.5, 110, 310, 1, NULL, 'World-leading university with centuries of academic excellence.', 'Cambridge', 'Public', 75.00, 70, NULL, '3.8', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(44, 'Imperial College London', 'imperial-london', 'UK', 6, NULL, 11, 40240.00, 1900.00, '[\"September\",\"January\"]', 'https://www.imperial.ac.uk', 7, 100, 310, 1, NULL, 'Science-focused university for engineering, medicine, and business.', 'London', 'Public', 75.00, 70, NULL, '3.7', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(45, 'UCL London', 'ucl-london', 'UK', 9, NULL, 11.8, 35480.00, 1800.00, '[\"September\"]', 'https://www.ucl.ac.uk', 7, 100, 300, 1, NULL, 'Multidisciplinary university in central London.', 'London', 'Public', 80.00, 67, NULL, '3.5', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(46, 'University of Edinburgh', 'edinburgh-edinburgh', 'UK', 15, NULL, 11.4, 30540.00, 1500.00, '[\"September\"]', 'https://www.ed.ac.uk', 7, 100, 300, 1, NULL, 'World-leading university known for AI research and humanities.', 'Edinburgh', 'Public', 75.00, 67, NULL, '3.5', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(47, 'University of Toronto', 'uoft-toronto', 'Canada', 21, NULL, 42.5, 57020.00, 1500.00, '[\"September\",\"January\"]', 'https://www.utoronto.ca', 6.5, 100, 310, 1, NULL, 'Canadas top university with global research reputation.', 'Toronto', 'Public', 90.00, 60, NULL, '3.7', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(48, 'University of British Columbia', 'ubc-vancouver', 'Canada', 34, NULL, 46, 42981.00, 1400.00, '[\"September\",\"January\"]', 'https://www.ubc.ca', 6.5, 90, 300, 1, NULL, 'Leading global university known for sustainability.', 'Vancouver', 'Public', 80.00, 60, NULL, '3.4', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(49, 'McGill University', 'mcgill-montreal', 'Canada', 30, NULL, 41, 25274.00, 1300.00, '[\"September\",\"January\"]', 'https://www.mcgill.ca', 6.5, 90, 300, 1, NULL, 'One of Canadas most prestigious universities.', 'Montreal', 'Public', 75.00, 60, NULL, '3.5', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(50, 'University of Waterloo', 'waterloo-waterloo', 'Canada', 112, NULL, 53, 53397.00, 1200.00, '[\"September\"]', 'https://uwaterloo.ca', 6.5, 90, 300, 0, NULL, 'Known for co-op programs and engineering excellence.', 'Waterloo', 'Public', 80.00, 60, NULL, '3.5', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(51, 'University of Alberta', 'ualberta-edmonton', 'Canada', 110, NULL, 48, 29580.00, 1100.00, '[\"September\",\"January\"]', 'https://www.ualberta.ca', 6.5, 86, 300, 1, NULL, 'Top research university in Western Canada.', 'Edmonton', 'Public', 75.00, 60, NULL, '3.3', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(52, 'University of Melbourne', 'unimelb-melbourne', 'Australia', 13, NULL, 69, 47280.00, 1500.00, '[\"February\",\"July\"]', 'https://www.unimelb.edu.au', 6.5, 79, 300, 1, NULL, 'Australias top-ranked university for research.', 'Melbourne', 'Public', 100.00, 58, NULL, '3.5', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(53, 'University of Sydney', 'usyd-sydney', 'Australia', 18, NULL, 55.5, 46000.00, 1600.00, '[\"February\",\"July\"]', 'https://www.sydney.edu.au', 6.5, 85, 300, 1, NULL, 'Leading research university with strong industry connections.', 'Sydney', 'Public', 100.00, 58, NULL, '3.5', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(54, 'Australian National University', 'anu-canberra', 'Australia', 30, NULL, 35, 44420.00, 1400.00, '[\"February\",\"July\"]', 'https://www.anu.edu.au', 6.5, 80, 300, 1, NULL, 'Leading research university in public policy and sciences.', 'Canberra', 'Public', 100.00, 58, NULL, '3.5', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(55, 'UNSW Sydney', 'unsw-sydney', 'Australia', 19, NULL, 61, 47760.00, 1500.00, '[\"February\",\"September\"]', 'https://www.unsw.edu.au', 6.5, 85, 300, 1, NULL, 'Known for engineering, business, and law programs.', 'Sydney', 'Public', 100.00, 58, NULL, '3.5', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(56, 'TU Munich', 'tum-munich', 'Germany', 37, NULL, 8, 163.00, 1100.00, '[\"October\",\"April\"]', 'https://www.tum.de', 6.5, 88, 310, 1, NULL, 'Germanys top technical university with tuition-free education.', 'Munich', 'Public', 0.00, 58, NULL, '3.0', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(57, 'LMU Munich', 'lmu-munich', 'Germany', 63, NULL, 20, 163.00, 1100.00, '[\"October\"]', 'https://www.lmu.de', 6.5, 80, 300, 1, NULL, 'Leading university in humanities and sciences.', 'Munich', 'Public', 0.00, 58, NULL, '3.0', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(58, 'Heidelberg University', 'heidelberg-heidelberg', 'Germany', 65, NULL, 18, 163.00, 1000.00, '[\"October\"]', 'https://www.uni-heidelberg.de', 6.5, 80, 300, 1, NULL, 'Germanys oldest university known for medicine and sciences.', 'Heidelberg', 'Public', 0.00, 58, NULL, '3.0', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(59, 'National University of Singapore', 'nus-singapore', 'Singapore', 8, NULL, 5, 38200.00, 1500.00, '[\"August\",\"January\"]', 'https://www.nus.edu.sg', 6.5, 92, 320, 1, NULL, 'Asias top university for global research and innovation.', 'Singapore', 'Public', 20.00, 62, NULL, '3.7', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(60, 'Nanyang Technological University', 'ntu-singapore', 'Singapore', 15, NULL, 12, 34800.00, 1400.00, '[\"August\",\"January\"]', 'https://www.ntu.edu.sg', 6.5, 90, 300, 1, NULL, 'Known for engineering and technology research.', 'Singapore', 'Public', 20.00, 62, NULL, '3.5', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(61, 'University of Hong Kong', 'hku-hongkong', 'Hong Kong', 26, NULL, 15, 43200.00, 1400.00, '[\"September\",\"January\"]', 'https://www.hku.hk', 6.5, 80, 300, 1, NULL, 'Oldest university in Hong Kong with strong global ties.', 'Hong Kong', 'Public', 50.00, 62, NULL, '3.5', '[\"Bachelors\",\"Masters\",\"PhD\"]', '2026-06-21 08:42:27', '2026-06-21 08:42:27');

--
-- Triggers `foreign_universities`
--
DELIMITER $$
CREATE TRIGGER `trg_foreign_universities_after_delete` AFTER DELETE ON `foreign_universities` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'foreign_universities', OLD.id,
        JSON_OBJECT('university_name', OLD.university_name), NULL, NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_foreign_universities_after_insert` AFTER INSERT ON `foreign_universities` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'foreign_universities', NEW.id, NULL,
        JSON_OBJECT('university_name', NEW.university_name, 'country', NEW.country),
        NULL, NOW());
END
$$
DELIMITER ;

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

--
-- Triggers `invoices`
--
DELIMITER $$
CREATE TRIGGER `trg_invoices_after_insert` AFTER INSERT ON `invoices` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'invoices', NEW.id, NULL,
        JSON_OBJECT('college_id', NEW.college_id, 'total_amount', NEW.total_amount, 'payment_status', NEW.payment_status),
        NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_invoices_after_update` AFTER UPDATE ON `invoices` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'invoices', NEW.id,
        JSON_OBJECT('payment_status', OLD.payment_status, 'total_amount', OLD.total_amount),
        JSON_OBJECT('payment_status', NEW.payment_status, 'total_amount', NEW.total_amount),
        NULL, NOW());
END
$$
DELIMITER ;

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
  `name` varchar(255) DEFAULT NULL,
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

--
-- Dumping data for table `leads`
--

INSERT INTO `leads` (`id`, `user_id`, `name`, `lead_type`, `source_page`, `utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term`, `gclid`, `college_id`, `course_id`, `phone`, `email`, `city`, `state`, `class_12_score`, `target_year`, `preferred_budget`, `lead_status`, `assigned_to`, `priority`, `last_contacted_at`, `next_followup_at`, `call_attempts`, `counsellor_notes`, `disposition`, `sla_breach_at`, `delivered_to_college_at`, `delivery_status`, `dispute_reason`, `dispute_raised_at`, `dispute_resolved_at`, `dispute_outcome`, `is_blacklisted`, `blacklist_reason`, `attribution_model`, `first_touch_source`, `last_touch_source`, `touchpoints_json`, `revenue_attributed`, `created_at`, `updated_at`) VALUES
('303c8005-96c7-456d-8af0-18e71888223f', NULL, 'Madhav Arora', 'inquiry', 'counselling', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'crs-bca-04', '09877275894', 'madhavarora132005@gmail.com', 'Hoshiarpur', 'Punjab', 44, '2026', 334444.00, 'new', NULL, 'medium', NULL, NULL, 0, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-22 16:54:32', '2026-06-22 16:54:32'),
('558189fa-0edc-496a-b175-eb7f48c3afc7', NULL, 'Madhav Arora', 'inquiry', 'counselling', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'crs-bca-04', '9877275894', 'madhavarora132005@gmail.com', 'Hoshiarpur', 'Punjab', 97, '2026', 333333.00, 'new', NULL, 'medium', NULL, NULL, 0, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-22 17:21:05', '2026-06-22 17:21:05'),
('6325ce38-c6ca-4993-b1e2-7a15dc6eaa24', NULL, 'Madhav Arora', 'inquiry', 'counselling', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'crs-btech-01', '09877275894', 'madhavarora132005@gmail.com', 'Hoshiarpur', 'Punjab', 67, '2026', 99999999.99, 'new', NULL, 'medium', NULL, NULL, 0, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-22 16:23:46', '2026-06-22 16:23:46'),
('6b1f46ee-7693-4ed7-9441-7b959742fb89', NULL, 'Madhav Arora', 'inquiry', 'counselling', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'crs-bca-04', '09877275894', 'madhavarora132005@gmail.com', 'Hoshiarpur', 'Punjab', 44, '2026', 334444.00, 'new', NULL, 'medium', NULL, NULL, 0, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-22 16:54:32', '2026-06-22 16:54:32'),
('720155cd-d22c-4548-990d-c4a7b95ff3fc', NULL, 'Madhav Arora', 'inquiry', 'counselling', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'crs-bca-04', '9877275894', 'madhavarora132005@gmail.com', 'Hoshiarpur', 'Punjab', 97, '2026', 333333.00, 'new', NULL, 'medium', NULL, NULL, 0, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-22 17:14:26', '2026-06-22 17:14:26'),
('86067728-47b3-43b5-8d67-cc85c3078b20', NULL, 'Madhav Arora', 'inquiry', 'counselling', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'crs-btech-01', '09877275894', 'madhavarora132005@gmail.com', 'Hoshiarpur', 'Punjab', 67, '2026', 99999999.99, 'new', NULL, 'medium', NULL, NULL, 0, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-22 16:23:46', '2026-06-22 16:23:46'),
('b06a4139-c44d-4753-9b99-7c4a3047a201', NULL, 'Madhav Arora', 'inquiry', 'counselling', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'crs-nursing-08', '9877275894', 'madhavarora132005@gmail.com', 'Hoshiarpur', 'Punjab', 98.98, '2026', NULL, 'new', NULL, 'medium', NULL, NULL, 0, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-22 17:21:34', '2026-06-22 17:21:34'),
('c44e7690-bcb0-f966-8ad0-1e1d7a3b4a3c', '64e20c70-d8d7-402f-a700-53c759a659d4', 'Test Lead Student', 'apply', 'apply_page', NULL, NULL, NULL, NULL, NULL, NULL, 'col-iima-0009', NULL, '1234567890', 'student@test.com', NULL, NULL, NULL, '2026', NULL, 'new', NULL, 'high', NULL, NULL, 0, 'Applied via Application Number: APP-TEST-123\nRemarks: Test remarks', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-28 11:46:38', '2026-06-28 11:46:38'),
('c8dd7c92-bd7e-c019-4d54-d718a19fe86e', '64e20c70-d8d7-402f-a700-53c759a659d4', 'Madhav Arora', 'apply', 'apply_page', NULL, NULL, NULL, NULL, NULL, NULL, 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', '512d88d3-0f21-420b-a5ce-8e371db8f0a0', '+919877275894', 'madhavarora132005@gmail.com', NULL, NULL, NULL, NULL, NULL, 'new', NULL, 'high', NULL, NULL, 0, 'Migrated application: APP-20260628-5461BE\nRemarks: Course: gcvhdhjcf\nExam Score: 77\nTarget Year: 2026\nNotes: gcg tgbfyh fcfhgd yrh', NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-28 11:39:49', '2026-06-28 11:39:49');

--
-- Triggers `leads`
--
DELIMITER $$
CREATE TRIGGER `trg_leads_after_delete` AFTER DELETE ON `leads` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'leads', OLD.id,
        JSON_OBJECT('name', OLD.name, 'email', OLD.email), NULL, NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_leads_after_insert` AFTER INSERT ON `leads` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'leads', NEW.id, NULL,
        JSON_OBJECT('name', NEW.name, 'email', NEW.email, 'lead_status', NEW.lead_status, 'source_page', NEW.source_page),
        NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_leads_after_update` AFTER UPDATE ON `leads` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'leads', NEW.id,
        JSON_OBJECT('lead_status', OLD.lead_status, 'assigned_to', OLD.assigned_to),
        JSON_OBJECT('lead_status', NEW.lead_status, 'assigned_to', NEW.assigned_to),
        NULL, NOW());
END
$$
DELIMITER ;

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

--
-- Triggers `lead_credits`
--
DELIMITER $$
CREATE TRIGGER `trg_lead_credits_after_insert` AFTER INSERT ON `lead_credits` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'lead_credits', NEW.id, NULL,
        JSON_OBJECT('college_id', NEW.college_id, 'leads_purchased', NEW.leads_purchased, 'lead_cost', NEW.lead_cost),
        NULL, NOW());
END
$$
DELIMITER ;

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
-- Table structure for table `mega_menu`
--

CREATE TABLE `mega_menu` (
  `id` varchar(36) NOT NULL,
  `parent_id` varchar(36) DEFAULT NULL,
  `menu_key` varchar(50) NOT NULL DEFAULT '',
  `label` varchar(255) NOT NULL,
  `url` varchar(500) DEFAULT '#',
  `icon` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `section_title` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mega_menu`
--

INSERT INTO `mega_menu` (`id`, `parent_id`, `menu_key`, `label`, `url`, `icon`, `sort_order`, `is_active`, `section_title`, `created_at`, `updated_at`) VALUES
('01feb66e-5781-580d-9922-999b62032ec9', '8d815b0c-fa4d-0113-1777-320b1d8f4056', '', 'CMA', '/courses?level=PG', NULL, 3, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('0241eac8-fb43-4e01-fac6-8c9e00883177', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'RRB JE', '/exams', NULL, 22, 1, 'Railway', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('02b01642-173e-4c3e-d1af-2d51cd85902b', '67deb8d6-f25e-fecf-e726-94ffc0ea0f06', '', 'All Design Courses', '/courses?level=UG', NULL, 5, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('066a7480-87e5-412b-641c-4a38c76f9839', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'Entrepreneurship', '/courses?course=mba', NULL, 13, 1, 'Popular Specializations', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('070494d4-2012-5dc2-c5cd-f3c5bceab9ae', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'DU LLB', '/exams', NULL, 18, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('08cf6d51-5aad-f983-d563-5232e123761d', '67deb8d6-f25e-fecf-e726-94ffc0ea0f06', '', 'M.Des', '/courses?level=PG', NULL, 1, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('09b56177-f7b3-c667-16b1-4f27b7a746d4', '558daeb0-5e32-5b5e-0b17-add1b40f2de4', '', 'Top Science Colleges in India', '/colleges', NULL, 5, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('0b273df5-93e0-3668-8f83-1cb2c57bfdc7', 'd1a28835-20a7-eda3-3a1b-ca7bf2a84b74', '', 'MCA', '/courses?course=mca', NULL, 1, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('0c9e4f29-9eea-4eac-ba16-98bc7ea55af0', 'cd806122-7640-0220-c164-55efd166de00', '', 'BA', '/courses?level=UG', NULL, 0, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('0cff7d46-bbc3-0bcf-15c4-6a5151c18e29', '67deb8d6-f25e-fecf-e726-94ffc0ea0f06', '', 'Top NIFT Colleges', '/colleges', NULL, 8, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('1089394a-8065-0457-0c33-47cd951a7c10', 'd2008d04-c43b-1eb3-7ddc-ba6a30274d38', '', 'VITEEE', '/exams', NULL, 14, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('12f7ea3d-575b-ea4b-1082-3ad5e308008d', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'All SSC Exams', '/exams', NULL, 15, 1, 'SSC', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('14aef5eb-428f-384e-126f-72e57f01570a', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'Constitutional Law', '/courses?course=llb', NULL, 13, 1, 'Popular Specializations', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('1697e4af-f030-43cf-55f1-42294195c58e', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'CSIR NET', '/exams', NULL, 9, 1, 'Teaching', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('177fc788-bbf2-5e13-9740-8387d8eff7b9', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'Top MBA Colleges in Bangalore', '/colleges?state=10', NULL, 8, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('17b626ac-8866-c9cf-384b-a05129e5be60', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'Top Law Colleges in Pune', '/colleges?state=22', NULL, 9, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('18b7dfab-8e32-197f-6f72-cbf687ab7daa', 'd2008d04-c43b-1eb3-7ddc-ba6a30274d38', '', 'Top Engineering Colleges in India', '/colleges?type=government', NULL, 6, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('18ded2df-54c7-c4ea-0733-840e2845c986', 'cd806122-7640-0220-c164-55efd166de00', '', 'Top Arts Colleges in India', '/colleges', NULL, 5, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('1b71145c-7adc-d9c8-52fc-e300bcd24199', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'AFCAT', '/exams', NULL, 17, 1, 'Defence', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('1b98f9d5-83b7-0d0b-62c0-fcc350c1d9f1', '8d815b0c-fa4d-0113-1777-320b1d8f4056', '', 'All Commerce Courses', '/courses?level=UG', NULL, 5, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('1c1549ca-eba9-7d63-98f8-c62602104f9b', '8d815b0c-fa4d-0113-1777-320b1d8f4056', '', 'Top B.Com Colleges', '/colleges', NULL, 7, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('1cfc49b6-bb8e-11dc-a04d-2a68b4f1dd19', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'Corporate Law', '/courses?course=llb', NULL, 10, 1, 'Popular Specializations', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('1e83cff6-d811-a2d1-17e5-ef02c1dfd99b', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'SBI Clerk', '/exams', NULL, 2, 1, 'Banking', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('203f6b27-6629-3f87-a88c-99dcfb23c52c', 'b73f0574-7cc4-f27f-7464-2cf94c0789d4', '', 'News & Articles', '/news', NULL, 18, 1, 'Resources', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('20f8e14c-dfe4-7382-88a2-99d6c3e3e23b', 'd2008d04-c43b-1eb3-7ddc-ba6a30274d38', '', 'BCA', '/courses?course=bca', NULL, 3, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('2274c713-4808-8369-a3dc-730438008a2d', '558daeb0-5e32-5b5e-0b17-add1b40f2de4', '', 'B.Sc', '/courses?level=UG', NULL, 0, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('25b092cf-86ad-42fe-e8ec-644684488194', 'd1a28835-20a7-eda3-3a1b-ca7bf2a84b74', '', 'IPU CET', '/exams', NULL, 7, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('2722a1ab-a532-38e6-7208-9a289021a49d', '67deb8d6-f25e-fecf-e726-94ffc0ea0f06', '', 'B.Des', '/courses?level=UG', NULL, 0, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('27662ccb-0ca3-8e7e-bda3-1dcf0c2594a2', '67deb8d6-f25e-fecf-e726-94ffc0ea0f06', '', 'Fashion Design', '/courses?level=UG', NULL, 3, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('2c7ee0f3-325e-567b-f5be-f2d73cb0eb50', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'IBPS Clerk', '/exams', NULL, 0, 1, 'Banking', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('2cdc259d-a4a1-c6ca-6b42-ba6062095a83', 'd1a28835-20a7-eda3-3a1b-ca7bf2a84b74', '', 'Top IT Colleges in India', '/colleges', NULL, 5, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('2f13d1ab-0df0-fff4-4d5a-6011a3a53478', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'Top Law Colleges in India', '/colleges', NULL, 6, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('328fc3ed-be8b-65d5-2dd9-3bf75dd3122c', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'SSC JE', '/exams', NULL, 13, 1, 'SSC', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('33b244ab-ebf2-6198-4cb1-f856e542db99', 'd2008d04-c43b-1eb3-7ddc-ba6a30274d38', '', 'BITSAT', '/exams', NULL, 13, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('35e6b2a2-0fb7-6e6f-1a82-d680bf39fb54', '558daeb0-5e32-5b5e-0b17-add1b40f2de4', '', 'IISC', '/exams', NULL, 8, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('36537cab-e266-fa6c-afbe-72e1542d97cc', '6f330685-fa76-fb4f-fbee-de9654d31485', '', 'All Hospitality Courses', '/courses?level=UG', NULL, 3, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('36725e12-b3de-7368-2699-1d6769a43877', '8d815b0c-fa4d-0113-1777-320b1d8f4056', '', 'Top Commerce Colleges in India', '/colleges', NULL, 6, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('39801828-8e32-95cc-f79f-622b678278f2', 'b73f0574-7cc4-f27f-7464-2cf94c0789d4', '', 'Top Medical Colleges in Delhi', '/colleges?state=11', NULL, 7, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('39ae7a17-dc8a-c116-278d-b338830083a8', 'd2008d04-c43b-1eb3-7ddc-ba6a30274d38', '', 'Top Engineering Colleges in Bangalore', '/colleges?state=10', NULL, 8, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('3c546e71-7a1e-8b6b-523b-89314f97cc13', 'b73f0574-7cc4-f27f-7464-2cf94c0789d4', '', 'AIIMS', '/exams', NULL, 13, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('3cf808d5-5aa5-cd2f-acb2-310b4ff30833', '6f330685-fa76-fb4f-fbee-de9654d31485', '', 'B.Sc Hospitality', '/courses?level=UG', NULL, 2, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('3d1d41bf-7520-fe1b-9f0e-eb7544d4e4e3', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'RRB NTPC', '/exams', NULL, 21, 1, 'Railway', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('42c54f44-1c7d-ba58-c6ec-fb716c31c36a', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'RRB Group D', '/exams', NULL, 20, 1, 'Railway', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('44fe66a3-e21e-1b07-9de7-f17d09ef1f63', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'Top Law Colleges in Bangalore', '/colleges?state=10', NULL, 8, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('45a0bf77-e392-da6b-7148-aa13012c9601', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'Family Law', '/courses?course=llb', NULL, 14, 1, 'Popular Specializations', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('47ff391a-cdbf-9a36-bbca-04375b694c23', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'Ask a Question', '/ask-question', NULL, 21, 1, 'Resources', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('492d0f1a-6d16-8626-065a-517ca2f50851', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'UPTET', '/exams', NULL, 7, 1, 'Teaching', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('4a226480-e67f-6f43-b8fa-de512178b0d4', 'd1a28835-20a7-eda3-3a1b-ca7bf2a84b74', '', 'B.Tech CSE', '/courses?course=b-tech', NULL, 2, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('4a25e5a4-5856-9489-b493-154c0eaff232', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'All Defence Exams', '/exams', NULL, 19, 1, 'Defence', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('4b2ac7f2-54eb-9cb3-0f11-a2f03c324add', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'XAT', '/exams', NULL, 17, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('4c9d4af6-0cef-b50e-54df-e869a3d020e7', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'All Teaching Exams', '/exams', NULL, 10, 1, 'Teaching', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('4dd841ce-a16b-84b1-54e5-46355a77a06f', '8d815b0c-fa4d-0113-1777-320b1d8f4056', '', 'CMA', '/exams', NULL, 9, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('4eafde67-0531-8926-2abb-7a4367c9be41', 'd2008d04-c43b-1eb3-7ddc-ba6a30274d38', '', 'B.Tech', '/courses?level=UG', NULL, 0, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('4f11e668-5526-c6ca-c238-4bd8bf7c5352', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'CMAT', '/exams', NULL, 19, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('510a60aa-cded-1d96-5018-ad985e37fb19', '558daeb0-5e32-5b5e-0b17-add1b40f2de4', '', 'IISER', '/exams', NULL, 7, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('51172a28-1bf7-824a-9429-8cfad18ad369', 'b73f0574-7cc4-f27f-7464-2cf94c0789d4', '', 'Top Dental Colleges', '/colleges', NULL, 9, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('52420914-3ae2-ce89-d829-e0484f8a9a58', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'Top Law Colleges in Delhi', '/colleges?state=11', NULL, 7, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('5313cf40-011b-2c5d-a02c-d22ccf178d7b', '6f330685-fa76-fb4f-fbee-de9654d31485', '', 'Hotel Management', '/courses?level=UG', NULL, 1, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('53c7ea51-ce0a-7351-8033-ea3a680751fe', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'Discussions', '/discussions', NULL, 22, 1, 'Resources', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('54c79cb5-e332-1848-bcf7-f59fd3155387', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'Top MBA Colleges in India', '/colleges', NULL, 6, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('558daeb0-5e32-5b5e-0b17-add1b40f2de4', NULL, 'root', 'Science', '#', 'ph-flask', 6, 1, NULL, '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('57ce6889-77c0-f760-b970-f5137442c9ef', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'LLM', '/courses?level=PG', NULL, 3, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('57faa61e-d53d-b96a-9645-19f48a9a1679', '6f330685-fa76-fb4f-fbee-de9654d31485', '', 'BHM', '/courses?level=UG', NULL, 0, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('59e2d0de-6d01-8e9c-7e64-8e5cc67412f6', 'd2008d04-c43b-1eb3-7ddc-ba6a30274d38', '', 'Ask a Question', '/ask-question', NULL, 16, 1, 'Resources', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('5cc60193-8603-c20d-f79c-931f0744486b', '8d815b0c-fa4d-0113-1777-320b1d8f4056', '', 'M.Com', '/courses?level=PG', NULL, 1, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('5ff8170c-1b8a-9665-b0d2-9a6769c97092', 'd2008d04-c43b-1eb3-7ddc-ba6a30274d38', '', 'Top Engineering Colleges in Chennai', '/colleges?state=24', NULL, 10, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('6458fdbd-ca60-d419-2e27-318102350ad9', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'SSC GD', '/exams', NULL, 14, 1, 'SSC', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('64728c70-45ed-422b-ee22-2ebdb19ed841', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'UGC NET', '/exams', NULL, 8, 1, 'Teaching', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('6777efa3-bfc0-7345-320b-f06902ce1274', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'BBA LLB', '/courses?course=llb', NULL, 2, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('67deb8d6-f25e-fecf-e726-94ffc0ea0f06', NULL, 'root', 'Design', '#', 'ph-palette', 4, 1, NULL, '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('68dbb7e0-5c27-729c-2014-ff6072fbe16b', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'LLB', '/courses?course=llb', NULL, 0, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('6959d7a4-7e8b-de51-ae46-926d6f77a5b9', 'd1a28835-20a7-eda3-3a1b-ca7bf2a84b74', '', 'Data Science', '/courses?level=PG', NULL, 3, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('6a7d1838-fe8a-6434-be73-8962ea1b5c48', 'b73f0574-7cc4-f27f-7464-2cf94c0789d4', '', 'Top Medical Colleges in Mumbai', '/colleges?state=22', NULL, 8, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('6bd11a98-1ea0-1664-cf94-af7c38cfd1b7', 'cd806122-7640-0220-c164-55efd166de00', '', 'All Arts Exams', '/exams', NULL, 8, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('6e4f10a5-c222-efc7-448a-1fc2196fd001', '558daeb0-5e32-5b5e-0b17-add1b40f2de4', '', 'M.Sc', '/courses?level=PG', NULL, 1, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('6e9185ac-c7f2-0d11-6b7d-6d097ba038c8', 'b73f0574-7cc4-f27f-7464-2cf94c0789d4', '', 'MD/MS', '/courses?level=PG', NULL, 4, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('6f330685-fa76-fb4f-fbee-de9654d31485', NULL, 'root', 'Hospitality', '#', 'ph-plane', 9, 1, NULL, '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('71cdb6c5-46d3-6b3f-263f-fdf51721c247', '8d815b0c-fa4d-0113-1777-320b1d8f4056', '', 'CA', '/courses?level=PG', NULL, 2, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('720478a0-3453-a725-5207-c09cae5cb90c', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'BBA', '/courses?course=bba', NULL, 1, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('73e9f084-e0ce-d7df-54f9-b9a1edda75bd', 'd2008d04-c43b-1eb3-7ddc-ba6a30274d38', '', 'Discussions', '/discussions', NULL, 17, 1, 'Resources', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('74e717b2-8e68-6704-1ec3-35c50f9d4a5e', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'Cyber Law', '/courses?course=llb', NULL, 12, 1, 'Popular Specializations', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('760bb352-696d-5e03-8c41-51d68dc1110d', 'b73f0574-7cc4-f27f-7464-2cf94c0789d4', '', 'MBBS', '/courses?course=mbbs', NULL, 0, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('76cdaa68-f1c8-f3e8-449d-de95af1ace87', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'PGDM', '/courses?level=PG', NULL, 2, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('7b4c0e66-bbaf-4c69-8614-b2ef20dda336', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'Sales & Marketing', '/courses?course=mba', NULL, 12, 1, 'Popular Specializations', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('7c0b5315-d353-faee-b173-96a97459a29b', 'b73f0574-7cc4-f27f-7464-2cf94c0789d4', '', 'NEET PG', '/exams', NULL, 12, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('7f4ad975-da35-9a22-40e6-32fa6679204a', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'SSC CGL', '/exams', NULL, 11, 1, 'SSC', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('82005191-57ce-b428-4bb3-ad4ea80f0c60', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'IBPS RRB', '/exams', NULL, 4, 1, 'Banking', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('825e68a0-da9e-1c8e-e07b-67c47415a030', 'd2008d04-c43b-1eb3-7ddc-ba6a30274d38', '', 'All Engineering Exams', '/exams', NULL, 15, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('83821394-95a7-f4f4-94d9-3e0bb7d2617f', 'b73f0574-7cc4-f27f-7464-2cf94c0789d4', '', 'BDS', '/courses?level=UG', NULL, 1, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('8858abba-c57d-6cbc-47a8-029e529cbbb0', 'd1a28835-20a7-eda3-3a1b-ca7bf2a84b74', '', 'All IT Courses', '/courses?level=UG', NULL, 4, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('89843f18-3253-17fb-07d0-67a568f46125', '67deb8d6-f25e-fecf-e726-94ffc0ea0f06', '', 'BFA', '/courses?level=UG', NULL, 2, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('8abc9bf9-ac97-810a-a423-909876bf86a6', 'cd806122-7640-0220-c164-55efd166de00', '', 'MA', '/courses?level=PG', NULL, 1, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('8add4d6f-d2d8-6937-1eda-3a35904d6bb7', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'Distance MBA', '/courses?course=mba', NULL, 4, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('8d815b0c-fa4d-0113-1777-320b1d8f4056', NULL, 'root', 'Commerce', '#', 'ph-chart-line', 5, 1, NULL, '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('8de9ce8a-49ca-f24c-2d6f-f09e9acc3f4f', 'd2008d04-c43b-1eb3-7ddc-ba6a30274d38', '', 'News & Articles', '/news', NULL, 18, 1, 'Resources', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('8e4fb419-5ea2-b3b0-928e-23e03a2cab66', 'd2008d04-c43b-1eb3-7ddc-ba6a30274d38', '', 'Top Engineering Colleges in Delhi', '/colleges?state=11', NULL, 7, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('8eee60d4-9799-2d7e-a84f-b5af4d242c5f', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'CDS', '/exams', NULL, 18, 1, 'Defence', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('91c2f877-00f7-ca1e-5bf8-27d7916236cc', 'b73f0574-7cc4-f27f-7464-2cf94c0789d4', '', 'Top Nursing Colleges', '/colleges', NULL, 10, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('9292c85d-b955-992a-1421-a9e05686d8c8', NULL, 'root', 'Management', '#', 'ph-briefcase', 1, 1, NULL, '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('9467169b-8761-491a-8e94-7ef9af91d1e5', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'SSC CHSL', '/exams', NULL, 12, 1, 'SSC', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('97713ad5-bb67-7903-424b-993fb4532dbb', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'Digital Marketing', '/courses?course=mba', NULL, 14, 1, 'Popular Specializations', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('9a7541ed-4893-1d56-9f7f-57d8aa5b01c0', 'cd806122-7640-0220-c164-55efd166de00', '', 'Top BA Colleges', '/colleges', NULL, 6, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('9b12aa5b-f3e3-89c7-a890-64454bd97744', NULL, 'root', 'Law', '#', 'ph-scales', 3, 1, NULL, '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('9e52717c-78d5-2167-2fef-93ec74a5ba98', 'd2008d04-c43b-1eb3-7ddc-ba6a30274d38', '', 'JEE Main', '/exam/jee-main-2026', NULL, 11, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('9e9ae975-574d-5a4f-1e69-36d11b2a9dd8', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'Human Resources', '/courses?course=mba', NULL, 15, 1, 'Popular Specializations', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('9fe399d7-8bb0-2ee1-3c55-011d7b1a8aa6', '6f330685-fa76-fb4f-fbee-de9654d31485', '', 'NCHMCT JEE', '/exams', NULL, 4, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('a00aa8a2-5b42-0aa1-7c02-fb0cef96f3e1', 'b73f0574-7cc4-f27f-7464-2cf94c0789d4', '', 'Top Medical Colleges in India', '/colleges', NULL, 6, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('a0666bcd-1fe5-a96c-7366-17808df745c9', '558daeb0-5e32-5b5e-0b17-add1b40f2de4', '', 'B.Stat', '/courses?level=UG', NULL, 3, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('a483fc02-5b02-8cd7-0349-ba3d66c3c77e', 'b73f0574-7cc4-f27f-7464-2cf94c0789d4', '', 'Ask a Question', '/ask-question', NULL, 16, 1, 'Resources', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('a5c36e58-3f75-e10f-dd1d-d64525cdf939', '67deb8d6-f25e-fecf-e726-94ffc0ea0f06', '', 'All Design Exams', '/exams', NULL, 12, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('a66bfee6-930e-92d4-87dd-eb7cfa1ab4cf', 'd1a28835-20a7-eda3-3a1b-ca7bf2a84b74', '', 'BCA', '/courses?course=bca', NULL, 0, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('a6e15279-4ddb-7c84-a0f8-aadbfcbbfb5c', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'CTET', '/exams', NULL, 6, 1, 'Teaching', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('a7eccaf5-60a8-fef7-a163-4c97d5e57a15', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'SBI PO', '/exams', NULL, 3, 1, 'Banking', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('a93a98ce-a1e0-311a-12c6-325290719ad6', '8d815b0c-fa4d-0113-1777-320b1d8f4056', '', 'All Commerce Exams', '/exams', NULL, 10, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('a9ebab07-6f4b-6a51-7858-2ced96529bbb', '558daeb0-5e32-5b5e-0b17-add1b40f2de4', '', 'All Science Exams', '/exams', NULL, 9, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('ab64af5c-6e68-42fb-d336-4378ff9c5155', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'All Management Courses', '/courses?level=PG', NULL, 5, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('acae0530-fd26-fd0e-7d1a-6d5543911d7c', 'd2008d04-c43b-1eb3-7ddc-ba6a30274d38', '', 'M.Tech', '/courses?level=PG', NULL, 1, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('adb52007-0444-c07e-d6b6-091d7a74f9d5', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'BA LLB', '/courses?course=llb', NULL, 1, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('b065c68f-89b7-04c1-dd41-285bc3bb5767', '67deb8d6-f25e-fecf-e726-94ffc0ea0f06', '', 'Interior Design', '/courses?level=UG', NULL, 4, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('b0ba6edb-25f8-dacc-5446-04104f32dcde', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'Finance', '/courses?course=mba', NULL, 11, 1, 'Popular Specializations', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('b25de682-54df-0243-347a-9e32c24deabe', 'cd806122-7640-0220-c164-55efd166de00', '', 'B.Ed', '/courses?level=UG', NULL, 2, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('b2e7e8bc-2340-6423-c210-6a1001cf7c35', '67deb8d6-f25e-fecf-e726-94ffc0ea0f06', '', 'UCEED', '/exams', NULL, 11, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('b38ef289-08b2-2716-da1d-ecec1b5c50ab', '8d815b0c-fa4d-0113-1777-320b1d8f4056', '', 'BBA', '/courses?course=bba', NULL, 4, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('b47111d7-dfa0-8c91-140b-912d6a1b675b', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'Criminal Law', '/courses?course=llb', NULL, 11, 1, 'Popular Specializations', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('b52a9bad-40ca-5941-6d9e-a3fed8d7db5b', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'AILET', '/exams', NULL, 16, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('b60e21b4-9d1e-44a2-a682-2a32952ed516', '6f330685-fa76-fb4f-fbee-de9654d31485', '', 'All Hospitality Exams', '/exams', NULL, 5, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('b73f0574-7cc4-f27f-7464-2cf94c0789d4', NULL, 'root', 'Medical', '#', 'ph-stethoscope', 2, 1, NULL, '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('bb755cb5-7f8e-78e3-873d-37d3827dda38', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'Top Management Colleges', '/colleges', NULL, 10, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('bc05ebab-b82a-716a-c763-eadc1c26ed36', 'd2008d04-c43b-1eb3-7ddc-ba6a30274d38', '', 'B.E.', '/courses?level=UG', NULL, 2, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('bc43fa62-1d34-d028-f60c-1fe0f9296aae', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'IBPS PO', '/exams', NULL, 1, 1, 'Banking', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('bcd5d18c-7405-d0e7-bfca-dcd6c72bf659', '67deb8d6-f25e-fecf-e726-94ffc0ea0f06', '', 'NIFT', '/exams', NULL, 9, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('bce85af2-45b3-beef-9d6b-36b2a9f3f7d4', 'd1a28835-20a7-eda3-3a1b-ca7bf2a84b74', '', 'All IT Exams', '/exams', NULL, 8, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('c0b4776b-c790-3c23-40f7-6411bacd70a7', 'cd806122-7640-0220-c164-55efd166de00', '', 'All Arts Courses', '/courses?level=UG', NULL, 4, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('c5c35dfc-26bb-69de-f978-18bba93aa392', '67deb8d6-f25e-fecf-e726-94ffc0ea0f06', '', 'Top Design Colleges in India', '/colleges', NULL, 6, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('ca215e0a-2d45-2bf1-69a0-fb2e420dd5df', 'd2008d04-c43b-1eb3-7ddc-ba6a30274d38', '', 'MCA', '/courses?course=mca', NULL, 4, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', NULL, 'root', 'Sarkari Exams', '#', 'ph-shield-check', 10, 1, NULL, '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('cc2702b6-1e44-0475-5c64-f6957bffc527', '558daeb0-5e32-5b5e-0b17-add1b40f2de4', '', 'BCA', '/courses?course=bca', NULL, 2, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('cd806122-7640-0220-c164-55efd166de00', NULL, 'root', 'Arts & Humanities', '#', 'ph-books', 8, 1, NULL, '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('cd99cab7-f96f-944e-7892-2707fdf8bf7d', '558daeb0-5e32-5b5e-0b17-add1b40f2de4', '', 'All Science Courses', '/courses?level=UG', NULL, 4, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('cff8cc08-f307-85a5-a645-742b8b96d06d', '67deb8d6-f25e-fecf-e726-94ffc0ea0f06', '', 'Top Design Colleges in Delhi', '/colleges?state=11', NULL, 7, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('d04e782d-bfa4-3dbe-aebe-dd297e800e81', 'b73f0574-7cc4-f27f-7464-2cf94c0789d4', '', 'B.Sc Nursing', '/courses?course=b-sc-nursing', NULL, 2, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('d1a28835-20a7-eda3-3a1b-ca7bf2a84b74', NULL, 'root', 'IT & Software', '#', 'ph-code', 7, 1, NULL, '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('d2008d04-c43b-1eb3-7ddc-ba6a30274d38', NULL, 'root', 'Engineering', '#', 'ph-engineering', 0, 1, NULL, '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('d29c4390-7c80-c722-fb2a-855c805e2226', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'CLAT', '/exam/clat-2026', NULL, 15, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('d4c20037-f707-2cfe-a648-51eeb642d5f9', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'NDA', '/exams', NULL, 16, 1, 'Defence', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('d5681f32-a4a9-9764-9b9d-59690d4b1774', '558daeb0-5e32-5b5e-0b17-add1b40f2de4', '', 'Top B.Sc Colleges', '/colleges', NULL, 6, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('d9ac6084-4c1d-0c1d-e8cc-21847852f74e', 'd2008d04-c43b-1eb3-7ddc-ba6a30274d38', '', 'All Engineering Courses', '/courses?level=UG', NULL, 5, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('da15fdc4-9cb8-7c47-3498-320de0fc8732', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'Ask a Question', '/ask-question', NULL, 20, 1, 'Resources', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('da2aa542-4404-4646-30d5-bbcf65473ef0', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'All Banking Exams', '/exams', NULL, 5, 1, 'Banking', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('dbe017e1-781b-9ec0-6e72-9e6da59a797e', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'All Management Exams', '/exams', NULL, 20, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('dc7fa3e2-301f-9333-c234-4d35cd93549a', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'LSAT', '/exams', NULL, 17, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('dd64160f-2927-e89c-2df7-aaee8e075315', 'd1a28835-20a7-eda3-3a1b-ca7bf2a84b74', '', 'Top BCA Colleges', '/colleges', NULL, 6, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('e183865d-287e-b05a-f6dd-599158c92ca9', '67deb8d6-f25e-fecf-e726-94ffc0ea0f06', '', 'NID', '/exams', NULL, 10, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('e30b96c1-e6c4-0742-38ac-6bcc2b6cc71b', 'b73f0574-7cc4-f27f-7464-2cf94c0789d4', '', 'NEET UG', '/exam/neet-ug-2026', NULL, 11, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('e52d3afc-9020-edfc-9b3e-5b288717ef77', 'b73f0574-7cc4-f27f-7464-2cf94c0789d4', '', 'JIPMER', '/exams', NULL, 14, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('e63f407c-f274-9e2d-18ab-8b8c25eb179d', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'MAT', '/exams', NULL, 18, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('e6bd9f44-4096-02a5-e31e-5f404a222ef5', '8d815b0c-fa4d-0113-1777-320b1d8f4056', '', 'CA Foundation', '/exams', NULL, 8, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('e6c298b5-ba1d-7c56-82c8-5fdcefccfdb3', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'All Law Courses', '/courses?level=UG', NULL, 5, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('e8fee0de-2233-71ba-df40-fe04addd49ba', 'd2008d04-c43b-1eb3-7ddc-ba6a30274d38', '', 'Top Engineering Colleges in Pune', '/colleges?state=22', NULL, 9, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('ea1fdb95-cec2-5c98-2bf2-34f3df2e4fd2', 'cd806122-7640-0220-c164-55efd166de00', '', 'Journalism', '/courses?level=UG', NULL, 3, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('ec7659fc-0282-40dd-467c-c971a3e3e880', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'CAT', '/exam/cat-2026', NULL, 16, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('ecb9dcb8-951d-71a7-bd04-40bd45162287', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'B.Sc LLB', '/courses?course=llb', NULL, 4, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('ecef27cc-4705-6d75-f38b-6b0a90a8f41d', 'd2008d04-c43b-1eb3-7ddc-ba6a30274d38', '', 'JEE Advanced', '/exam/jee-main-2026', NULL, 12, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('ed297116-0c3c-d983-9a4c-3ca382e393b1', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'News & Articles', '/news', NULL, 23, 1, 'Resources', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('ed882d88-8660-dd43-c6dc-fb95a316dc0b', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'Top MBA Colleges in Delhi', '/colleges?state=11', NULL, 7, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('eeaa386d-5239-90b2-f906-e610ab29e36e', 'ca6c5cf1-4ab6-77c5-615d-e1f001d08c70', '', 'All Railway Exams', '/exams', NULL, 23, 1, 'Railway', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('ef5ec007-9d55-cd66-006c-a190e4924753', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'Top BBA Colleges in India', '/colleges', NULL, 9, 1, 'Top Ranked Colleges', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('f0920cc8-23ec-e2cc-73d2-d0b160a9bc06', 'b73f0574-7cc4-f27f-7464-2cf94c0789d4', '', 'All Medical Courses', '/courses?level=UG', NULL, 5, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('f1971d2c-a460-958d-4262-860748c7f8da', '8d815b0c-fa4d-0113-1777-320b1d8f4056', '', 'B.Com', '/courses?level=UG', NULL, 0, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('f5c52f38-e600-30fc-778f-e26a78332ef7', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'All Law Exams', '/exams', NULL, 19, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('f62e2a30-7646-90ca-7aa9-62647f764e40', 'b73f0574-7cc4-f27f-7464-2cf94c0789d4', '', 'BAMS', '/courses?level=UG', NULL, 3, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('f7600868-754c-7f8d-0d34-794083ea5fee', 'b73f0574-7cc4-f27f-7464-2cf94c0789d4', '', 'Discussions', '/discussions', NULL, 17, 1, 'Resources', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('f8bec4a0-ad2b-f74b-880b-2b628b9e04a6', 'b73f0574-7cc4-f27f-7464-2cf94c0789d4', '', 'All Medical Exams', '/exams', NULL, 15, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('fb44ba0e-c254-7acd-43a2-08df2930a65d', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'Executive MBA', '/courses?level=PG', NULL, 3, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('fca5cdfb-3f5b-241b-14c5-26c5548511c4', 'cd806122-7640-0220-c164-55efd166de00', '', 'CUET', '/exams', NULL, 7, 1, 'Exams', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('fe6328b1-92af-7910-4995-c737bbfe4f60', '9b12aa5b-f3e3-89c7-a890-64454bd97744', '', 'Discussions', '/discussions', NULL, 21, 1, 'Resources', '2026-06-21 07:22:17', '2026-06-21 07:22:17'),
('fea7556c-6cde-f622-6e10-f6f60eff46ac', '9292c85d-b955-992a-1421-a9e05686d8c8', '', 'MBA', '/courses?course=mba', NULL, 0, 1, 'Popular Courses', '2026-06-21 07:22:17', '2026-06-21 07:22:17');

-- --------------------------------------------------------

--
-- Table structure for table `moderation_queue`
--

CREATE TABLE `moderation_queue` (
  `id` varchar(36) NOT NULL,
  `entity_type` enum('review','qa','qa_answer','qa_question','qa_comment','article','college_data','comment') NOT NULL,
  `entity_id` varchar(36) NOT NULL,
  `status` enum('pending','in_progress','resolved','dismissed') DEFAULT 'pending',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `flagged_reason` enum('spam','offensive','misleading','duplicate','low_quality','abuse','wrong_info','new_review','other') NOT NULL,
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

--
-- Dumping data for table `moderation_queue`
--

INSERT INTO `moderation_queue` (`id`, `entity_type`, `entity_id`, `status`, `priority`, `flagged_reason`, `ai_score`, `reporter_id`, `moderator_id`, `action_taken`, `action_note`, `actioned_at`, `escalated_to`, `sla_due_at`, `created_at`, `updated_at`) VALUES
('31789e5a-7311-11f1-81b7-a0510b1a7448', 'qa_answer', 'ans00001-0000-0000-0000-000000000001', 'resolved', 'medium', 'abuse', NULL, 'user-1234-uuid', NULL, '', NULL, '2026-06-22 09:13:35', NULL, '2026-06-23 09:12:42', '2026-06-22 09:12:42', '2026-06-28 16:48:06'),
('3178d2f3-7311-11f1-81b7-a0510b1a7448', 'qa_answer', 'ans00001-0000-0000-0000-000000000001', 'resolved', 'medium', 'duplicate', NULL, 'user-1234-uuid', NULL, 'reject', NULL, '2026-06-22 10:18:28', NULL, '2026-06-23 09:29:52', '2026-06-22 09:29:52', '2026-06-28 16:48:06'),
('3178d80a-7311-11f1-81b7-a0510b1a7448', 'qa_answer', 'ans00001-0000-0000-0000-000000000001', 'resolved', 'medium', 'abuse', NULL, '64e20c70-d8d7-402f-a700-53c759a659d4', NULL, 'reject', NULL, '2026-06-22 10:22:27', NULL, '2026-06-23 10:12:01', '2026-06-22 10:12:01', '2026-06-28 16:48:06'),
('3178dbb7-7311-11f1-81b7-a0510b1a7448', 'qa_answer', 'ans00001-0000-0000-0000-000000000001', 'resolved', 'medium', 'abuse', NULL, '64e20c70-d8d7-402f-a700-53c759a659d4', NULL, 'reject', NULL, '2026-06-22 10:28:59', NULL, '2026-06-23 10:28:47', '2026-06-22 10:28:47', '2026-06-28 16:48:06');

--
-- Triggers `moderation_queue`
--
DELIMITER $$
CREATE TRIGGER `trg_moderation_queue_after_insert` AFTER INSERT ON `moderation_queue` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'moderation_queue', NEW.id, NULL,
        JSON_OBJECT('entity_type', NEW.entity_type, 'entity_id', NEW.entity_id, 'status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_moderation_queue_after_update` AFTER UPDATE ON `moderation_queue` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'status_change', 'moderation_queue', NEW.id,
        JSON_OBJECT('status', OLD.status),
        JSON_OBJECT('status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;

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

--
-- Triggers `notification_campaigns`
--
DELIMITER $$
CREATE TRIGGER `trg_notification_campaigns_after_delete` AFTER DELETE ON `notification_campaigns` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'notification_campaigns', OLD.id,
        JSON_OBJECT('campaign_name', OLD.campaign_name), NULL, NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_notification_campaigns_after_insert` AFTER INSERT ON `notification_campaigns` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'notification_campaigns', NEW.id, NULL,
        JSON_OBJECT('campaign_name', NEW.campaign_name, 'status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_notification_campaigns_after_update` AFTER UPDATE ON `notification_campaigns` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'status_change', 'notification_campaigns', NEW.id,
        JSON_OBJECT('status', OLD.status),
        JSON_OBJECT('status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;

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

--
-- Triggers `notification_templates`
--
DELIMITER $$
CREATE TRIGGER `trg_notification_templates_after_delete` AFTER DELETE ON `notification_templates` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'notification_templates', OLD.id,
        JSON_OBJECT('template_name', OLD.template_name), NULL, NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_notification_templates_after_insert` AFTER INSERT ON `notification_templates` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'notification_templates', NEW.id, NULL,
        JSON_OBJECT('template_name', NEW.template_name, 'channel', NEW.channel),
        NULL, NOW());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `nps_feedback`
--

CREATE TABLE `nps_feedback` (
  `id` int(11) NOT NULL,
  `score` tinyint(4) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `article_id` char(36) DEFAULT NULL,
  `article_slug` varchar(500) DEFAULT NULL,
  `page_url` varchar(1000) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nps_feedback`
--

INSERT INTO `nps_feedback` (`id`, `score`, `user_id`, `article_id`, `article_slug`, `page_url`, `user_agent`, `ip_address`, `created_at`) VALUES
(1, 9, '64e20c70-d8d7-402f-a700-53c759a659d4', '5ba7e718-4bb4-4946-834e-ee0b36c1d8d5', 'top-10-engineering-colleges-2026-v2', 'https://localhost/ADMISSION/news_details.php?slug=top-10-engineering-colleges-2026-v2', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '::1', '2026-06-23 11:13:58');

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

--
-- Triggers `partners`
--
DELIMITER $$
CREATE TRIGGER `trg_partners_after_delete` AFTER DELETE ON `partners` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'partners', OLD.id,
        JSON_OBJECT('contact_person', OLD.contact_person), NULL, NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_partners_after_insert` AFTER INSERT ON `partners` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'partners', NEW.id, NULL,
        JSON_OBJECT('contact_person', NEW.contact_person, 'partner_college_id', NEW.partner_college_id, 'status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_partners_after_update` AFTER UPDATE ON `partners` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'partners', NEW.id,
        JSON_OBJECT('status', OLD.status),
        JSON_OBJECT('status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;

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

--
-- Triggers `payments`
--
DELIMITER $$
CREATE TRIGGER `trg_payments_after_insert` AFTER INSERT ON `payments` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'payments', NEW.id, NULL,
        JSON_OBJECT('application_id', NEW.application_id, 'amount', NEW.amount, 'payment_status', NEW.payment_status),
        NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_payments_after_update` AFTER UPDATE ON `payments` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'payments', NEW.id,
        JSON_OBJECT('payment_status', OLD.payment_status),
        JSON_OBJECT('payment_status', NEW.payment_status),
        NULL, NOW());
END
$$
DELIMITER ;

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

--
-- Dumping data for table `qa_reports`
--

INSERT INTO `qa_reports` (`id`, `question_id`, `answer_id`, `report_reason`, `reported_by`, `moderation_action`, `created_at`, `updated_at`) VALUES
(1, 'que00001-0000-0000-0000-000000000001', 'ans00001-0000-0000-0000-000000000001', '', 'user-1234-uuid', 'remove', '2026-06-22 09:12:42', '2026-06-22 09:13:35'),
(2, 'que00001-0000-0000-0000-000000000001', 'ans00001-0000-0000-0000-000000000001', 'duplicate', 'user-1234-uuid', 'reject', '2026-06-22 09:29:52', '2026-06-22 10:18:28'),
(3, 'que00001-0000-0000-0000-000000000001', 'ans00001-0000-0000-0000-000000000001', '', '64e20c70-d8d7-402f-a700-53c759a659d4', 'reject', '2026-06-22 10:12:01', '2026-06-22 10:22:27'),
(4, 'que00001-0000-0000-0000-000000000001', 'ans00001-0000-0000-0000-000000000001', '', '64e20c70-d8d7-402f-a700-53c759a659d4', 'reject', '2026-06-22 10:28:47', '2026-06-22 10:28:59');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `slug` varchar(300) DEFAULT NULL,
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
  `follow_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `slug`, `question_text`, `question_category`, `related_college_id`, `related_exam_id`, `related_course_id`, `asked_by`, `views`, `answer_count`, `is_featured`, `status`, `trending_score`, `follow_count`, `created_at`, `updated_at`) VALUES
('392aaa16-6aa0-42ff-baa3-c9712ef7bf71', 'fcghchg-ffyu-y-tfyftyfhcft', 'fcghchg ffyu y tfyftyfhcft', 'general', NULL, NULL, NULL, 'user-1234-uuid', 85, 0, 0, 'open', 0, 0, '2026-06-22 06:16:01', '2026-06-23 11:45:07'),
('que00001-0000-0000-0000-000000000001', 'what-is-the-jee-advanced-cutoff-rank-for-btech-cse-at-iit-bombay-for-general-cat', 'What is the JEE Advanced cutoff rank for B.Tech CSE at IIT Bombay for General category?', 'admission', 'col00001-0000-0000-0000-000000000001', NULL, NULL, 'usr00001-0000-0000-0000-000000000001', 63, 4, 1, '', 0.2, 2, '2026-06-19 06:04:41', '2026-06-23 11:48:33'),
('que00001-0000-0000-0000-000000000002', 'how-are-the-hostel-facilities-at-iit-delhi-for-first-year-btech-students', 'How are the hostel facilities at IIT Delhi for first year B.Tech students?', 'hostel', 'col00001-0000-0000-0000-000000000002', NULL, NULL, 'usr00001-0000-0000-0000-000000000002', 48, 2, 0, 'answered', 0, 0, '2026-06-19 06:04:41', '2026-06-23 11:45:07'),
('que00001-0000-0000-0000-000000000003', 'what-is-the-placement-scenario-for-btech-ece-at-nit-trichy', 'What is the placement scenario for B.Tech ECE at NIT Trichy?', 'placements', 'col00001-0000-0000-0000-000000000003', NULL, NULL, 'usr00001-0000-0000-0000-000000000003', 35, 2, 0, 'answered', 0, 0, '2026-06-19 06:04:41', '2026-06-23 11:45:07'),
('que00001-0000-0000-0000-000000000004', 'is-bits-pilani-worth-the-high-fee-compared-to-iits', 'Is BITS Pilani worth the high fee compared to IITs?', 'fees', 'col00001-0000-0000-0000-000000000004', NULL, NULL, 'usr00001-0000-0000-0000-000000000004', 28, 3, 1, 'answered', 0, 1, '2026-06-19 06:04:41', '2026-06-23 11:45:07'),
('que00001-0000-0000-0000-000000000005', 'how-is-the-viteee-exam-pattern-and-difficulty-level', 'How is the VITEEE exam pattern and difficulty level?', 'exams', 'col00001-0000-0000-0000-000000000005', NULL, NULL, 'usr00001-0000-0000-0000-000000000005', 22, 2, 0, 'answered', 0, 0, '2026-06-19 06:04:41', '2026-06-23 11:45:07'),
('que00001-0000-0000-0000-000000000006', 'what-is-the-average-package-for-btech-cse-at-iit-bombay-in-2024', 'What is the average package for B.Tech CSE at IIT Bombay in 2024?', 'placements', 'col00001-0000-0000-0000-000000000001', NULL, NULL, 'usr00001-0000-0000-0000-000000000002', 18, 2, 1, 'answered', 0, 0, '2026-06-19 06:04:41', '2026-06-23 11:45:07'),
('que00001-0000-0000-0000-000000000007', 'what-documents-are-needed-for-josaa-counselling-at-iits', 'What documents are needed for JoSAA counselling at IITs?', 'admission', 'col00001-0000-0000-0000-000000000001', NULL, NULL, 'usr00001-0000-0000-0000-000000000003', 15, 2, 0, 'answered', 0, 0, '2026-06-19 06:04:41', '2026-06-23 11:44:36'),
('que00001-0000-0000-0000-000000000008', 'does-bits-pilani-offer-lateral-entry-admission-for-diploma-holders', 'Does BITS Pilani offer lateral entry admission for Diploma holders?', 'admission', 'col00001-0000-0000-0000-000000000004', NULL, NULL, 'usr00001-0000-0000-0000-000000000001', 12, 2, 0, 'answered', 0, 0, '2026-06-19 06:04:41', '2026-06-23 11:44:36');

--
-- Triggers `questions`
--
DELIMITER $$
CREATE TRIGGER `trg_questions_after_delete` AFTER DELETE ON `questions` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'questions', OLD.id,
        JSON_OBJECT('question_text', LEFT(OLD.question_text, 200), 'asked_by', OLD.asked_by), NULL, NULL, NOW());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `question_views`
--

CREATE TABLE `question_views` (
  `id` int(11) NOT NULL,
  `question_id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `question_views`
--

INSERT INTO `question_views` (`id`, `question_id`, `user_id`, `session_id`, `ip_address`, `viewed_at`) VALUES
(1, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '128.54.212.210', '2026-06-15 08:15:07'),
(2, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '101.78.74.5', '2026-05-07 08:15:07'),
(3, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '198.117.169.37', '2026-05-11 08:15:07'),
(4, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '16.223.14.145', '2026-06-12 08:15:07'),
(5, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '163.8.112.238', '2026-06-03 08:15:07'),
(6, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '197.34.18.41', '2026-04-30 08:15:07'),
(7, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '77.159.202.204', '2026-05-04 08:15:07'),
(8, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '88.81.28.78', '2026-06-18 08:15:07'),
(9, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '41.154.177.113', '2026-06-10 08:15:07'),
(10, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '51.221.150.9', '2026-06-09 08:15:07'),
(11, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '183.90.208.109', '2026-05-24 08:15:07'),
(12, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '199.203.191.146', '2026-05-11 08:15:07'),
(13, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '95.64.167.21', '2026-05-05 08:15:07'),
(14, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '119.253.21.152', '2026-05-07 08:15:07'),
(15, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '49.241.86.62', '2026-05-03 08:15:07'),
(16, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '180.37.73.29', '2026-05-28 08:15:07'),
(17, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '89.191.53.132', '2026-06-22 08:15:07'),
(18, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '118.57.72.165', '2026-06-11 08:15:07'),
(19, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '199.121.64.113', '2026-05-18 08:15:07'),
(20, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '62.142.18.29', '2026-05-17 08:15:07'),
(21, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '84.251.210.130', '2026-04-30 08:15:07'),
(22, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '56.71.181.231', '2026-05-13 08:15:07'),
(23, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '26.123.118.103', '2026-06-15 08:15:07'),
(24, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '112.253.128.175', '2026-06-22 08:15:07'),
(25, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '115.143.199.91', '2026-06-01 08:15:07'),
(26, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '138.128.70.227', '2026-04-25 08:15:07'),
(27, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '155.112.208.41', '2026-05-13 08:15:07'),
(28, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '110.229.90.64', '2026-06-13 08:15:07'),
(29, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '44.73.82.187', '2026-06-20 08:15:07'),
(30, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '45.243.96.129', '2026-05-08 08:15:07'),
(31, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '23.183.217.226', '2026-06-03 08:15:07'),
(32, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '143.97.140.113', '2026-06-18 08:15:07'),
(33, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '200.3.219.240', '2026-05-15 08:15:07'),
(34, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '71.200.33.110', '2026-06-23 08:15:07'),
(35, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '154.161.77.231', '2026-04-25 08:15:07'),
(36, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '92.16.15.250', '2026-05-09 08:15:07'),
(37, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '48.84.186.203', '2026-05-25 08:15:07'),
(38, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '10.147.30.229', '2026-06-21 08:15:07'),
(39, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '151.66.131.211', '2026-05-07 08:15:07'),
(40, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '182.22.62.157', '2026-05-05 08:15:07'),
(41, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '193.112.208.89', '2026-06-04 08:15:07'),
(42, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '29.228.17.16', '2026-05-22 08:15:07'),
(43, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '193.15.246.138', '2026-05-11 08:15:07'),
(44, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '72.247.175.95', '2026-05-02 08:15:07'),
(45, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '84.60.252.64', '2026-06-10 08:15:07'),
(46, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '45.211.61.169', '2026-05-15 08:15:07'),
(47, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '64.51.82.205', '2026-05-18 08:15:07'),
(48, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '141.67.143.121', '2026-05-30 08:15:07'),
(49, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '132.74.67.178', '2026-05-08 08:15:07'),
(50, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '143.141.89.131', '2026-05-07 08:15:07'),
(51, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '61.211.98.69', '2026-06-02 08:15:07'),
(52, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '70.114.249.147', '2026-06-21 08:15:07'),
(53, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '145.229.107.145', '2026-05-25 08:15:07'),
(54, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '163.230.233.63', '2026-05-30 08:15:07'),
(55, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '90.133.163.55', '2026-05-27 08:15:07'),
(56, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '198.6.191.136', '2026-05-27 08:15:07'),
(57, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '69.37.169.73', '2026-05-18 08:15:07'),
(58, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '72.89.168.77', '2026-06-03 08:15:07'),
(59, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '62.187.12.236', '2026-05-27 08:15:07'),
(60, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '148.229.3.153', '2026-04-29 08:15:07'),
(61, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '45.15.81.12', '2026-05-13 08:15:07'),
(62, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '138.47.233.97', '2026-06-23 08:15:07'),
(63, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '13.164.40.190', '2026-05-13 08:15:07'),
(64, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '170.17.245.99', '2026-06-09 08:15:07'),
(65, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '12.45.122.52', '2026-06-10 08:15:07'),
(66, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '108.45.98.117', '2026-05-03 08:15:07'),
(67, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '113.18.183.181', '2026-05-06 08:15:07'),
(68, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '31.126.58.4', '2026-05-15 08:15:07'),
(69, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '128.217.241.15', '2026-06-01 08:15:07'),
(70, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '200.81.151.103', '2026-06-01 08:15:07'),
(71, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '179.19.113.232', '2026-06-08 08:15:07'),
(72, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '123.16.216.77', '2026-06-05 08:15:07'),
(73, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '41.232.201.199', '2026-05-06 08:15:07'),
(74, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '108.50.200.70', '2026-06-06 08:15:07'),
(75, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '115.52.215.27', '2026-05-02 08:15:07'),
(76, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '168.189.129.240', '2026-06-19 08:15:07'),
(77, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '178.234.189.210', '2026-04-24 08:15:07'),
(78, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '108.29.181.49', '2026-05-03 08:15:07'),
(79, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '179.236.96.102', '2026-05-04 08:15:07'),
(80, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '128.75.51.31', '2026-06-22 08:15:07'),
(81, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '59.76.98.130', '2026-05-24 08:15:07'),
(82, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '90.176.168.244', '2026-06-12 08:15:07'),
(83, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '31.205.103.213', '2026-04-27 08:15:07'),
(84, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '196.78.149.32', '2026-05-02 08:15:07'),
(85, '392aaa16-6aa0-42ff-baa3-c9712ef7bf71', NULL, NULL, '106.240.111.141', '2026-06-07 08:15:07'),
(86, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '139.156.37.71', '2026-05-09 08:15:07'),
(87, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '98.25.113.212', '2026-05-26 08:15:07'),
(88, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '75.59.13.246', '2026-06-19 08:15:07'),
(89, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '68.139.104.65', '2026-06-13 08:15:07'),
(90, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '123.219.167.58', '2026-06-20 08:15:07'),
(91, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '127.98.55.5', '2026-06-19 08:15:07'),
(92, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '180.229.40.132', '2026-04-24 08:15:07'),
(93, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '98.170.210.73', '2026-06-12 08:15:07'),
(94, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '134.160.157.36', '2026-05-21 08:15:07'),
(95, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '114.134.246.137', '2026-06-04 08:15:07'),
(96, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '177.19.41.184', '2026-05-13 08:15:07'),
(97, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '177.130.143.94', '2026-05-16 08:15:07'),
(98, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '97.202.29.208', '2026-04-26 08:15:07'),
(99, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '30.110.56.182', '2026-05-02 08:15:07'),
(100, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '104.237.60.217', '2026-06-02 08:15:07'),
(101, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '134.90.45.94', '2026-06-09 08:15:07'),
(102, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '38.51.15.167', '2026-06-03 08:15:07'),
(103, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '24.221.130.25', '2026-05-31 08:15:07'),
(104, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '195.87.40.172', '2026-05-21 08:15:07'),
(105, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '181.235.198.177', '2026-05-19 08:15:07'),
(106, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '106.39.143.151', '2026-05-31 08:15:07'),
(107, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '180.78.127.7', '2026-05-19 08:15:07'),
(108, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '63.68.234.186', '2026-06-15 08:15:07'),
(109, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '182.94.44.129', '2026-04-30 08:15:07'),
(110, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '66.65.233.214', '2026-05-01 08:15:07'),
(111, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '161.113.156.205', '2026-04-24 08:15:07'),
(112, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '181.66.78.94', '2026-05-31 08:15:07'),
(113, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '49.238.78.110', '2026-06-12 08:15:07'),
(114, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '36.145.29.81', '2026-05-16 08:15:07'),
(115, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '190.143.160.58', '2026-05-22 08:15:07'),
(116, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '107.56.54.18', '2026-06-19 08:15:07'),
(117, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '116.246.221.162', '2026-06-18 08:15:07'),
(118, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '185.57.159.47', '2026-05-28 08:15:07'),
(119, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '14.243.133.141', '2026-05-14 08:15:07'),
(120, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '28.170.251.200', '2026-05-06 08:15:07'),
(121, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '39.19.180.23', '2026-05-01 08:15:07'),
(122, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '102.6.214.6', '2026-06-23 08:15:07'),
(123, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '197.230.69.28', '2026-06-17 08:15:07'),
(124, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '49.126.233.25', '2026-05-07 08:15:07'),
(125, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '125.142.213.69', '2026-04-30 08:15:07'),
(126, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '159.212.215.153', '2026-04-30 08:15:07'),
(127, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '176.55.59.51', '2026-05-06 08:15:07'),
(128, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '151.52.38.123', '2026-06-07 08:15:07'),
(129, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '198.95.227.218', '2026-05-27 08:15:07'),
(130, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '56.153.100.17', '2026-06-03 08:15:07'),
(131, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '71.139.37.81', '2026-05-12 08:15:07'),
(132, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '188.10.125.106', '2026-05-24 08:15:07'),
(133, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '115.249.167.153', '2026-06-15 08:15:07'),
(134, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '38.5.157.140', '2026-06-21 08:15:07'),
(135, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '91.180.9.115', '2026-06-16 08:15:07'),
(136, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '129.187.90.191', '2026-06-12 08:15:07'),
(137, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '145.113.252.82', '2026-05-13 08:15:07'),
(138, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '128.41.58.214', '2026-05-14 08:15:07'),
(139, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '98.239.198.208', '2026-05-21 08:15:07'),
(140, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '173.242.171.55', '2026-05-10 08:15:07'),
(141, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '119.155.7.131', '2026-05-29 08:15:07'),
(142, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '110.71.245.25', '2026-06-15 08:15:07'),
(143, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '103.145.202.156', '2026-05-24 08:15:07'),
(144, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '183.160.109.247', '2026-05-22 08:15:07'),
(145, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '64.75.252.30', '2026-06-04 08:15:07'),
(146, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '189.220.93.199', '2026-06-22 08:15:07'),
(147, 'que00001-0000-0000-0000-000000000001', NULL, NULL, '61.208.117.1', '2026-06-18 08:15:07'),
(148, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '59.217.218.120', '2026-05-15 08:15:07'),
(149, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '13.109.115.246', '2026-05-15 08:15:07'),
(150, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '15.170.29.53', '2026-06-21 08:15:07'),
(151, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '105.225.55.244', '2026-05-24 08:15:07'),
(152, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '60.81.184.11', '2026-05-05 08:15:07'),
(153, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '12.121.76.251', '2026-05-20 08:15:07'),
(154, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '194.84.13.7', '2026-06-15 08:15:07'),
(155, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '112.28.181.212', '2026-05-03 08:15:07'),
(156, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '178.98.136.99', '2026-05-12 08:15:07'),
(157, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '88.176.58.227', '2026-06-12 08:15:07'),
(158, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '116.18.94.180', '2026-05-27 08:15:07'),
(159, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '116.231.68.187', '2026-06-06 08:15:07'),
(160, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '149.208.118.10', '2026-05-21 08:15:07'),
(161, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '105.93.32.171', '2026-06-19 08:15:07'),
(162, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '194.69.54.177', '2026-06-23 08:15:07'),
(163, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '191.187.59.91', '2026-05-14 08:15:07'),
(164, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '38.15.88.151', '2026-05-16 08:15:07'),
(165, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '183.94.236.49', '2026-05-06 08:15:07'),
(166, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '35.168.145.61', '2026-06-01 08:15:07'),
(167, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '22.158.88.184', '2026-05-24 08:15:07'),
(168, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '108.223.188.125', '2026-05-07 08:15:07'),
(169, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '41.124.222.81', '2026-05-24 08:15:07'),
(170, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '128.62.139.10', '2026-06-05 08:15:07'),
(171, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '134.37.253.211', '2026-05-17 08:15:07'),
(172, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '74.136.62.177', '2026-06-16 08:15:07'),
(173, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '146.89.26.73', '2026-06-20 08:15:07'),
(174, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '65.146.247.155', '2026-05-19 08:15:07'),
(175, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '41.199.228.32', '2026-05-28 08:15:07'),
(176, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '176.129.151.140', '2026-05-05 08:15:07'),
(177, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '85.231.77.5', '2026-05-12 08:15:07'),
(178, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '32.46.113.228', '2026-05-07 08:15:07'),
(179, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '200.50.179.219', '2026-05-22 08:15:07'),
(180, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '20.98.254.167', '2026-05-02 08:15:07'),
(181, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '124.176.222.175', '2026-05-19 08:15:07'),
(182, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '191.113.100.224', '2026-05-26 08:15:07'),
(183, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '134.177.147.252', '2026-06-23 08:15:07'),
(184, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '120.188.212.219', '2026-06-12 08:15:07'),
(185, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '108.225.215.120', '2026-05-13 08:15:07'),
(186, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '39.196.233.17', '2026-05-04 08:15:07'),
(187, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '78.6.188.196', '2026-06-07 08:15:07'),
(188, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '200.194.104.166', '2026-06-15 08:15:07'),
(189, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '133.83.77.13', '2026-05-03 08:15:07'),
(190, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '109.241.95.151', '2026-05-12 08:15:07'),
(191, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '179.169.9.221', '2026-04-29 08:15:07'),
(192, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '101.12.5.57', '2026-06-12 08:15:07'),
(193, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '151.181.134.22', '2026-06-19 08:15:07'),
(194, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '139.203.11.215', '2026-05-08 08:15:07'),
(195, 'que00001-0000-0000-0000-000000000002', NULL, NULL, '91.7.218.74', '2026-06-09 08:15:07'),
(196, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '144.157.97.74', '2026-05-11 08:15:07'),
(197, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '40.183.193.214', '2026-05-21 08:15:07'),
(198, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '103.157.38.53', '2026-06-06 08:15:07'),
(199, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '83.193.165.220', '2026-05-26 08:15:07'),
(200, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '195.253.143.182', '2026-05-23 08:15:07'),
(201, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '11.185.240.77', '2026-06-09 08:15:07'),
(202, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '15.94.200.117', '2026-06-05 08:15:07'),
(203, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '106.17.112.235', '2026-05-28 08:15:07'),
(204, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '144.89.83.99', '2026-05-11 08:15:07'),
(205, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '14.0.222.140', '2026-06-02 08:15:07'),
(206, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '170.203.92.136', '2026-06-21 08:15:07'),
(207, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '10.72.73.76', '2026-05-25 08:15:07'),
(208, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '137.3.158.80', '2026-04-28 08:15:07'),
(209, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '35.229.15.34', '2026-06-20 08:15:07'),
(210, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '129.253.8.87', '2026-05-09 08:15:07'),
(211, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '62.180.23.1', '2026-05-30 08:15:07'),
(212, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '51.215.137.58', '2026-04-24 08:15:07'),
(213, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '122.222.66.156', '2026-06-21 08:15:07'),
(214, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '127.245.99.181', '2026-06-06 08:15:07'),
(215, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '187.195.190.89', '2026-04-29 08:15:07'),
(216, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '91.145.78.181', '2026-05-17 08:15:07'),
(217, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '200.132.136.33', '2026-05-20 08:15:07'),
(218, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '144.250.146.245', '2026-06-11 08:15:07'),
(219, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '25.230.197.141', '2026-06-02 08:15:07'),
(220, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '88.209.226.246', '2026-05-30 08:15:07'),
(221, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '151.40.222.160', '2026-05-12 08:15:07'),
(222, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '59.241.118.84', '2026-06-23 08:15:07'),
(223, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '60.200.253.29', '2026-06-09 08:15:07'),
(224, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '88.4.26.164', '2026-06-07 08:15:07'),
(225, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '38.226.209.63', '2026-04-24 08:15:07'),
(226, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '147.244.113.153', '2026-06-18 08:15:07'),
(227, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '23.35.18.241', '2026-06-03 08:15:07'),
(228, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '110.139.213.33', '2026-05-06 08:15:07'),
(229, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '59.151.77.155', '2026-04-29 08:15:07'),
(230, 'que00001-0000-0000-0000-000000000003', NULL, NULL, '138.7.92.212', '2026-05-15 08:15:07'),
(231, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '72.44.230.91', '2026-05-26 08:15:07'),
(232, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '144.47.2.251', '2026-05-06 08:15:07'),
(233, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '45.131.226.251', '2026-05-21 08:15:07'),
(234, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '146.187.165.130', '2026-06-14 08:15:07'),
(235, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '130.68.116.194', '2026-04-27 08:15:07'),
(236, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '127.241.165.199', '2026-06-23 08:15:07'),
(237, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '45.22.75.36', '2026-05-18 08:15:07'),
(238, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '134.75.117.238', '2026-05-22 08:15:07'),
(239, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '122.125.151.244', '2026-06-13 08:15:07'),
(240, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '27.181.9.130', '2026-05-21 08:15:07'),
(241, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '43.117.36.130', '2026-06-03 08:15:07'),
(242, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '187.249.138.182', '2026-06-22 08:15:07'),
(243, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '88.144.104.69', '2026-05-14 08:15:07'),
(244, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '196.186.94.213', '2026-06-05 08:15:07'),
(245, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '112.236.62.145', '2026-05-24 08:15:07'),
(246, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '200.107.26.97', '2026-05-05 08:15:07'),
(247, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '135.72.153.121', '2026-05-20 08:15:07'),
(248, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '122.118.191.161', '2026-05-07 08:15:07'),
(249, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '65.1.74.105', '2026-06-23 08:15:07'),
(250, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '107.206.150.118', '2026-05-05 08:15:07'),
(251, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '51.70.49.191', '2026-05-07 08:15:07'),
(252, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '193.160.11.157', '2026-05-23 08:15:07'),
(253, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '19.169.135.245', '2026-05-31 08:15:07'),
(254, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '51.223.88.140', '2026-05-03 08:15:07'),
(255, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '75.171.239.141', '2026-05-23 08:15:07'),
(256, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '133.173.156.58', '2026-05-30 08:15:07'),
(257, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '109.67.36.55', '2026-06-20 08:15:07'),
(258, 'que00001-0000-0000-0000-000000000004', NULL, NULL, '93.78.148.243', '2026-06-13 08:15:07'),
(259, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '33.98.205.78', '2026-05-21 08:15:07'),
(260, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '79.235.226.175', '2026-05-25 08:15:07'),
(261, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '38.91.118.219', '2026-04-28 08:15:07'),
(262, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '150.16.195.191', '2026-04-27 08:15:07'),
(263, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '126.91.148.245', '2026-06-14 08:15:07'),
(264, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '164.187.13.178', '2026-06-16 08:15:07'),
(265, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '109.8.159.92', '2026-05-07 08:15:07'),
(266, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '16.17.105.159', '2026-05-20 08:15:07'),
(267, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '69.28.100.87', '2026-04-27 08:15:07'),
(268, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '160.195.117.82', '2026-05-22 08:15:07'),
(269, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '101.250.134.157', '2026-05-03 08:15:07'),
(270, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '47.24.71.168', '2026-06-03 08:15:07'),
(271, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '123.91.59.202', '2026-05-20 08:15:07'),
(272, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '131.80.45.167', '2026-05-20 08:15:07'),
(273, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '193.141.172.77', '2026-06-22 08:15:07'),
(274, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '190.45.142.239', '2026-05-05 08:15:07'),
(275, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '136.204.89.228', '2026-06-18 08:15:07'),
(276, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '194.20.209.226', '2026-05-17 08:15:07'),
(277, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '104.205.37.214', '2026-05-24 08:15:07'),
(278, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '105.85.181.232', '2026-05-29 08:15:07'),
(279, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '145.191.91.222', '2026-05-05 08:15:07'),
(280, 'que00001-0000-0000-0000-000000000005', NULL, NULL, '178.191.124.239', '2026-06-06 08:15:07'),
(281, 'que00001-0000-0000-0000-000000000006', NULL, NULL, '156.147.120.73', '2026-04-28 08:15:07'),
(282, 'que00001-0000-0000-0000-000000000006', NULL, NULL, '108.89.152.219', '2026-04-26 08:15:07'),
(283, 'que00001-0000-0000-0000-000000000006', NULL, NULL, '105.105.181.195', '2026-06-05 08:15:07'),
(284, 'que00001-0000-0000-0000-000000000006', NULL, NULL, '12.194.207.120', '2026-05-10 08:15:07'),
(285, 'que00001-0000-0000-0000-000000000006', NULL, NULL, '164.51.164.243', '2026-04-26 08:15:07'),
(286, 'que00001-0000-0000-0000-000000000006', NULL, NULL, '149.142.248.165', '2026-06-09 08:15:07'),
(287, 'que00001-0000-0000-0000-000000000006', NULL, NULL, '123.255.52.178', '2026-06-15 08:15:07'),
(288, 'que00001-0000-0000-0000-000000000006', NULL, NULL, '155.214.71.109', '2026-05-09 08:15:07'),
(289, 'que00001-0000-0000-0000-000000000006', NULL, NULL, '102.169.215.233', '2026-06-21 08:15:07'),
(290, 'que00001-0000-0000-0000-000000000006', NULL, NULL, '129.165.89.201', '2026-05-05 08:15:07'),
(291, 'que00001-0000-0000-0000-000000000006', NULL, NULL, '125.254.57.128', '2026-06-21 08:15:07'),
(292, 'que00001-0000-0000-0000-000000000006', NULL, NULL, '115.118.158.10', '2026-05-13 08:15:07'),
(293, 'que00001-0000-0000-0000-000000000006', NULL, NULL, '102.120.197.103', '2026-05-12 08:15:07'),
(294, 'que00001-0000-0000-0000-000000000006', NULL, NULL, '11.131.89.152', '2026-05-24 08:15:07'),
(295, 'que00001-0000-0000-0000-000000000006', NULL, NULL, '143.27.187.68', '2026-05-04 08:15:07'),
(296, 'que00001-0000-0000-0000-000000000006', NULL, NULL, '156.41.2.160', '2026-06-13 08:15:07'),
(297, 'que00001-0000-0000-0000-000000000006', NULL, NULL, '181.99.116.158', '2026-05-12 08:15:07'),
(298, 'que00001-0000-0000-0000-000000000006', NULL, NULL, '101.195.236.56', '2026-05-30 08:15:07'),
(299, 'que00001-0000-0000-0000-000000000007', NULL, NULL, '72.34.129.225', '2026-05-30 08:15:07'),
(300, 'que00001-0000-0000-0000-000000000007', NULL, NULL, '192.90.134.60', '2026-06-19 08:15:07'),
(301, 'que00001-0000-0000-0000-000000000007', NULL, NULL, '15.211.64.183', '2026-05-25 08:15:07'),
(302, 'que00001-0000-0000-0000-000000000007', NULL, NULL, '174.46.220.91', '2026-05-14 08:15:07'),
(303, 'que00001-0000-0000-0000-000000000007', NULL, NULL, '76.168.25.74', '2026-06-23 08:15:07'),
(304, 'que00001-0000-0000-0000-000000000007', NULL, NULL, '152.235.97.253', '2026-05-01 08:15:07'),
(305, 'que00001-0000-0000-0000-000000000007', NULL, NULL, '169.138.147.35', '2026-05-10 08:15:07'),
(306, 'que00001-0000-0000-0000-000000000007', NULL, NULL, '63.132.171.123', '2026-05-30 08:15:07'),
(307, 'que00001-0000-0000-0000-000000000007', NULL, NULL, '30.13.50.21', '2026-06-16 08:15:07'),
(308, 'que00001-0000-0000-0000-000000000007', NULL, NULL, '59.52.206.158', '2026-06-05 08:15:07'),
(309, 'que00001-0000-0000-0000-000000000007', NULL, NULL, '67.5.230.201', '2026-05-20 08:15:07'),
(310, 'que00001-0000-0000-0000-000000000007', NULL, NULL, '180.108.164.97', '2026-06-23 08:15:07'),
(311, 'que00001-0000-0000-0000-000000000007', NULL, NULL, '180.112.114.17', '2026-06-18 08:15:07'),
(312, 'que00001-0000-0000-0000-000000000007', NULL, NULL, '125.250.168.253', '2026-06-22 08:15:07'),
(313, 'que00001-0000-0000-0000-000000000007', NULL, NULL, '79.109.78.57', '2026-05-22 08:15:07'),
(314, 'que00001-0000-0000-0000-000000000008', NULL, NULL, '170.51.123.140', '2026-06-09 08:15:07'),
(315, 'que00001-0000-0000-0000-000000000008', NULL, NULL, '18.50.211.157', '2026-06-19 08:15:07'),
(316, 'que00001-0000-0000-0000-000000000008', NULL, NULL, '24.99.29.158', '2026-05-15 08:15:07'),
(317, 'que00001-0000-0000-0000-000000000008', NULL, NULL, '39.10.82.21', '2026-04-30 08:15:07'),
(318, 'que00001-0000-0000-0000-000000000008', NULL, NULL, '200.101.251.104', '2026-06-06 08:15:07'),
(319, 'que00001-0000-0000-0000-000000000008', NULL, NULL, '186.84.229.20', '2026-04-26 08:15:07'),
(320, 'que00001-0000-0000-0000-000000000008', NULL, NULL, '179.120.173.6', '2026-05-10 08:15:07'),
(321, 'que00001-0000-0000-0000-000000000008', NULL, NULL, '196.65.7.208', '2026-05-22 08:15:07'),
(322, 'que00001-0000-0000-0000-000000000008', NULL, NULL, '27.49.165.134', '2026-06-22 08:15:07'),
(323, 'que00001-0000-0000-0000-000000000008', NULL, NULL, '74.30.130.208', '2026-05-06 08:15:07'),
(324, 'que00001-0000-0000-0000-000000000008', NULL, NULL, '122.183.197.108', '2026-05-12 08:15:07'),
(325, 'que00001-0000-0000-0000-000000000008', NULL, NULL, '11.31.168.52', '2026-05-12 08:15:07'),
(326, 'que00001-0000-0000-0000-000000000001', '64e20c70-d8d7-402f-a700-53c759a659d4', 'k417qh95d7inojuj2pugo4sc56', '::1', '2026-06-23 11:48:33');

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

--
-- Dumping data for table `rankings`
--

INSERT INTO `rankings` (`id`, `ranking_body`, `ranking_year`, `category`, `college_id`, `rank_position`, `rank_band`, `score`, `sub_scores`, `source_url`, `published_date`, `previous_year_rank`, `rank_delta`, `created_at`, `updated_at`) VALUES
(32, 'NIRF', '2025', 'Engineering', 'col-iitb-0001', 3, NULL, 92.5, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:33', '2026-06-21 15:24:33'),
(33, 'NIRF', '2024', 'Engineering', 'col-iitb-0001', 4, NULL, 91.8, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:33', '2026-06-21 15:24:33'),
(34, 'NIRF', '2025', 'Engineering', 'col-iitd-0002', 2, NULL, 93.1, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:33', '2026-06-21 15:24:33'),
(35, 'NIRF', '2024', 'Engineering', 'col-iitd-0002', 2, NULL, 92.7, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:33', '2026-06-21 15:24:33'),
(36, 'NIRF', '2025', 'Engineering', 'col-iitm-0003', 1, NULL, 94.2, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:33', '2026-06-21 15:24:33'),
(37, 'NIRF', '2024', 'Engineering', 'col-iitm-0003', 1, NULL, 93.8, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:33', '2026-06-21 15:24:33'),
(38, 'NIRF', '2025', 'Engineering', 'col-iitk-0004', 4, NULL, 91.3, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:33', '2026-06-21 15:24:33'),
(39, 'NIRF', '2024', 'Engineering', 'col-iitk-0004', 5, NULL, 90.6, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:33', '2026-06-21 15:24:33'),
(40, 'NIRF', '2025', 'Engineering', 'col-iitkgp-0005', 5, NULL, 90.9, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:33', '2026-06-21 15:24:33'),
(41, 'NIRF', '2024', 'Engineering', 'col-iitkgp-0005', 6, NULL, 90.2, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:33', '2026-06-21 15:24:33'),
(42, 'NIRF', '2025', 'Engineering', 'col-nitt-0006', 9, NULL, 85.3, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:33', '2026-06-21 15:24:33'),
(43, 'NIRF', '2025', 'Engineering', 'col-nitk-0007', 10, NULL, 84.7, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:33', '2026-06-21 15:24:33'),
(44, 'NIRF', '2025', 'Engineering', 'col-bits-0008', 22, NULL, 78.4, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:33', '2026-06-21 15:24:33'),
(45, 'NIRF', '2025', 'Management', 'col-iima-0009', 1, NULL, 95.1, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:33', '2026-06-21 15:24:33'),
(46, 'NIRF', '2025', 'Management', 'col-iimb-0010', 2, NULL, 94.3, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:33', '2026-06-21 15:24:33'),
(47, 'NIRF', '2025', 'Medical', 'col-aiims-0011', 1, NULL, 96.5, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:33', '2026-06-21 15:24:33'),
(48, 'NIRF', '2025', 'Law', 'col-nlsiu-0012', 1, NULL, 93.7, NULL, NULL, NULL, NULL, NULL, '2026-06-21 15:24:33', '2026-06-21 15:24:33');

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
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` varchar(36) NOT NULL,
  `reportable_type` enum('answer','comment','question') NOT NULL,
  `reportable_id` varchar(36) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `reasons` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`reasons`)),
  `other_text` text DEFAULT NULL,
  `status` enum('pending','reviewed','resolved','dismissed') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `reportable_type`, `reportable_id`, `user_id`, `reasons`, `other_text`, `status`, `admin_notes`, `created_at`) VALUES
('b0f16cac-eb0d-43fc-b7be-9d189ac4bc89', 'answer', 'ans00001-0000-0000-0000-000000000001', 'user-1234-uuid', '[\"copyright\"]', NULL, 'pending', NULL, '2026-06-22 09:00:49');

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

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `college_id`, `overall_rating`, `academics_rating`, `faculty_rating`, `placements_rating`, `infrastructure_rating`, `hostel_rating`, `social_life_rating`, `food_rating`, `review_title`, `review_body`, `pros`, `cons`, `batch_year`, `course_id`, `helpful_votes`, `media_urls`, `moderation_status`, `moderation_reason`, `moderated_by`, `moderated_at`, `is_verified_alumnus`, `alumni_proof_url`, `ai_spam_score`, `ai_sentiment`, `reported_count`, `fraud_flag`, `duplicate_score`, `created_at`, `updated_at`) VALUES
('0a1f027d-05ac-6b70-ef63-1b746e25f0d9', 'usr00001-0000-0000-0000-000000000004', 'col-bits-0008', 4.7, 4.7, 4.5, 4.8, 5.0, 4.5, 4.5, 4.0, 'Industry-Ready Graduates', 'The practice school program at BITS is a game changer. Students spend 6 to 8 months at companies during their degree, making them industry-ready from day one. Companies love BITS graduates.', 'Practice school, industry readiness, BITS brand', 'Fees are very high, academic pressure immense', '2022', NULL, 16, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-05-02 15:44:39', '2026-06-21 15:44:39'),
('0c369053-6bf5-f1a0-b4be-6de68ae0aaf0', 'usr00001-0000-0000-0000-000000000004', 'col-iima-0009', 4.8, 4.8, 5.0, 5.0, 4.5, 4.0, 4.0, 3.5, 'Best Return on Investment', 'Despite high fees, IIMA offers the best ROI in management education. The average salary is among the highest globally. The brand opens doors everywhere.', 'ROI, placement averages, brand prestige', 'High fees for non-sponsored students, intense pressure', '2022', NULL, 44, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-03-03 15:44:39', '2026-06-21 15:44:39'),
('16cbd0ac-b806-cdb6-c219-1c68f344c07c', 'usr00001-0000-0000-0000-000000000002', 'col-nitt-0006', 4.5, 4.5, 4.5, 4.7, 4.5, 4.0, 4.0, 3.5, 'Best NIT in India', 'NIT Trichy lives up to its reputation as the best NIT. The academics, placements, and campus culture are excellent. A great alternative to IITs with much lower fees.', 'Best NIT ranking, excellent placements, affordable fees', 'Trichy heat, limited city life, strict rules', '2023', NULL, 15, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-06-14 15:44:39', '2026-06-21 15:44:39'),
('19d7bdae-e96b-7990-8a43-35c52db96008', 'usr00001-0000-0000-0000-000000000001', 'col-aiims-0011', 4.9, 5.0, 5.0, 5.0, 4.5, 4.0, 3.5, 3.5, 'The Mecca of Medical Education', 'AIIMS Delhi is the dream of every medical aspirant in India. The quality of clinical exposure, faculty, and patient diversity is unmatched. Studying here is both a privilege and a responsibility.', 'Best clinical exposure, top faculty, immense patient load', 'Extreme academic pressure, limited social life, strict schedule', '2022', NULL, 8, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-06-07 15:44:39', '2026-06-21 15:44:39'),
('203de97c-501d-05cf-0fb2-d5e9503cb81e', 'usr00001-0000-0000-0000-000000000003', 'col-iitb-0001', 4.8, 4.9, 5.0, 4.7, 4.5, 4.0, 4.8, 3.5, 'Exceptional Institute with World-Class Faculty', 'IIT Bombay has been an incredible experience. The faculty is top-notch, and the curriculum is designed to challenge and inspire. The campus life is vibrant with numerous clubs and festivals.', 'World-class faculty, excellent research opportunities, amazing campus', 'Heavy academic workload, limited hostel rooms', '2023', NULL, 24, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-06-13 15:44:38', '2026-06-21 15:44:38'),
('20416aab-04eb-fed3-4224-885253d2c01f', '64e20c70-d8d7-402f-a700-53c759a659d4', 'col-iitb-0001', 4.5, 4.8, 4.5, 4.7, 4.0, 3.5, 4.8, 3.0, 'Great Campus but Hostels Need Work', 'The academics and placements are stellar but the hostel facilities are aging. Food quality in mess varies. The campus itself is beautiful with Powai Lake nearby.', 'Campus beauty, location in Mumbai, brand value', 'Hostel maintenance, mess food variety, internet speed', '2023', NULL, 10, NULL, 'approved', NULL, NULL, NULL, 0, NULL, 0, NULL, 0, 0, 0, '2026-03-18 15:44:39', '2026-06-21 15:44:39'),
('2245cb8f-f7a6-35ad-2485-b8d5e92d5d8d', 'usr00001-0000-0000-0000-000000000003', 'col-nitk-0007', 4.3, 4.4, 4.3, 4.5, 4.5, 4.0, 4.5, 3.5, 'Decent College, Great Location', 'The college provides a good education and the campus location is simply unbeatable. The beach is a huge stress buster. Placements are good especially for CS and IT branches.', 'Campus location, decent placements, growing reputation', 'Some departments lack focus, limited industry visits', '2023', NULL, 8, NULL, 'approved', NULL, NULL, NULL, 0, NULL, 0, NULL, 0, 0, 0, '2026-04-14 15:44:39', '2026-06-21 15:44:39'),
('2cf33919-7bb8-cfd2-aa95-dbcc3aa9c8ce', '8b0478e7-602f-11f1-9ea0-a0510b1a7448', 'col-iima-0009', 4.9, 5.0, 5.0, 5.0, 4.5, 4.0, 4.5, 3.5, 'Gold Standard of Management Education', 'IIMA is the undisputed leader of management education in India. The case-study pedagogy, world-class faculty, and incredible peer learning make it transformative.', 'Case method, brand value, alumni network, global recognition', 'Intense academic pressure, competitive environment', '2022', NULL, 12, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-05-28 15:44:39', '2026-06-21 15:44:39'),
('3284ad02-7295-b2b3-d0f8-8cf32b3bed77', 'usr00001-0000-0000-0000-000000000001', 'col-iitb-0001', 4.6, 4.5, 4.5, 4.5, 4.5, 4.0, 4.5, 3.5, 'Transformative MBA Experience', 'The SJMSOM MBA program is rigorous and industry-oriented. Great mix of case studies, live projects, and industry interactions. The alumni network is incredibly strong.', 'Industry connections, case-based learning, brand value', 'Expensive compared to IIMs, fewer MBA-specific companies', '2022', NULL, 28, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-02-05 15:44:39', '2026-06-21 15:44:39'),
('37401333-18ec-e107-c697-b07f35ce38e6', 'usr00001-0000-0000-0000-000000000003', 'col-nitt-0006', 4.4, 4.5, 4.3, 4.7, 4.2, 4.0, 3.8, 3.5, 'Good Overall Experience', 'NIT Trichy provides a good overall experience with decent academics, active clubs, and solid placements. The campus is well-maintained and the faculty is supportive.', 'Supportive faculty, active clubs, good placement support', 'Trichy location not very exciting, limited entertainment', '2023', NULL, 18, NULL, 'approved', NULL, NULL, NULL, 0, NULL, 0, NULL, 0, 0, 0, '2026-04-03 15:44:39', '2026-06-21 15:44:39'),
('3a77fcf4-7ac7-2276-f160-4a379f797b56', 'usr00001-0000-0000-0000-000000000004', 'col-bits-0008', 4.5, 4.6, 4.4, 4.7, 5.0, 4.0, 4.5, 4.0, 'Expensive But Worth It', 'Yes, BITS is expensive, but the ROI is excellent. The exposure, peer learning, and placement opportunities make it worth the investment. Campus life is vibrant and modern.', 'ROI, modern facilities, global exposure, alumni network', 'Heavy fees, hostel occupancy issues, academic load', '2023', NULL, 2, NULL, 'approved', NULL, NULL, NULL, 0, NULL, 0, NULL, 0, 0, 0, '2026-03-25 15:44:39', '2026-06-21 15:44:39'),
('3f1295d5-b175-5157-7920-43a113d5ddea', 'usr00001-0000-0000-0000-000000000005', 'col-iitd-0002', 4.7, 4.8, 4.7, 4.7, 4.0, 3.5, 4.8, 3.5, 'Premier Institute in Heart of Delhi', 'IIT Delhi offers the perfect blend of academics and city life. Being in Delhi, there are endless opportunities for internships, cultural exposure, and networking.', 'Location advantage, strong alumni network, diverse culture', 'Small campus, crowded hostels, pollution', '2023', NULL, 26, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-06-16 15:44:39', '2026-06-21 15:44:39'),
('3f5816de-d082-33e5-bd68-733bed9d7093', 'usr00001-0000-0000-0000-000000000003', 'col-iitkgp-0005', 4.6, 4.5, 4.5, 4.6, 4.2, 4.0, 4.8, 3.5, 'Oldest IIT, Rich Legacy', 'Being the oldest IIT, Kharagpur has a rich legacy and one of the strongest alumni networks globally. The brand value combined with solid academics makes it a top choice.', 'Legacy, alumni network, brand recognition, diverse courses', 'Needs more modern labs, campus too spread out', '2022', NULL, 43, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-03-14 15:44:39', '2026-06-21 15:44:39'),
('40f16dde-2622-aa5f-ed77-747f287b51b1', 'usr00001-0000-0000-0000-000000000004', 'col-iitd-0002', 4.8, 4.8, 4.7, 4.8, 4.0, 3.5, 4.8, 3.5, 'Innovation Hub with Startup Support', 'IIT Delhi has a thriving startup ecosystem. The incubation cell supports student ventures with funding and mentorship. Many unicorns have roots here.', 'Startup support, location in Delhi, industry connect', 'Competition is intense, limited green spaces', '2022', NULL, 32, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-04-23 15:44:39', '2026-06-21 15:44:39'),
('4177a718-aa84-418d-629d-3cf5d2e01462', '8b0478e7-602f-11f1-9ea0-a0510b1a7448', 'col-aiims-0011', 4.8, 5.0, 5.0, 4.5, 4.0, 4.0, 3.5, 3.0, 'Best for Postgraduate Medical Education', 'AIIMS MD/MS programs are the most sought after in India. The training is rigorous and prepares you for any challenge in clinical practice. The research output is phenomenal.', 'PG reputation, research output, super-specialty exposure', 'Bond period, immense workload, limited stipend', '2022', NULL, 6, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-02-27 15:44:39', '2026-06-21 15:44:39'),
('4aef30b1-f2bd-7159-227e-666ea770f424', 'usr00001-0000-0000-0000-000000000005', 'col-nlsiu-0012', 4.8, 5.0, 5.0, 4.8, 4.0, 4.0, 4.5, 3.5, 'Harvard of Indian Legal Education', 'NLSIU Bangalore is the Harvard of India when it comes to legal education. The five-year integrated program is rigorous and transformative. Moot courts and legal aid clinics provide practical exposure.', 'Legal education brand, moot court culture, Bangalore location', 'Extremely competitive, limited campus size, academic pressure', '2022', NULL, 43, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-06-20 15:44:39', '2026-06-21 15:44:39'),
('4af05a0b-a51b-a1a7-9a06-40ddddc6c2d0', 'usr00001-0000-0000-0000-000000000005', 'col-aiims-0011', 4.9, 5.0, 5.0, 5.0, 4.5, 4.0, 3.5, 3.0, 'Unmatched Clinical Training', 'The clinical training at AIIMS is the best in India. You get to see cases that other medical colleges might see in years. The OPD handles thousands of patients daily, giving unparalleled hands-on experience.', 'Clinical variety, hands-on training, research opportunities', 'Workload is intense, hostel food poor, limited leisure time', '2021', NULL, 16, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-05-20 15:44:39', '2026-06-21 15:44:39'),
('567b1c8d-232d-cb14-2a2b-ed54d2044297', 'usr00001-0000-0000-0000-000000000005', 'col-iimb-0010', 4.8, 4.8, 4.8, 4.8, 4.5, 4.0, 4.5, 3.5, 'Excellent Management Education', 'IIM Bangalore offers a world-class MBA experience with its unique blend of academics, industry interaction, and beautiful green campus. The location in Bangalore adds to the tech startup ecosystem exposure.', 'Bangalore location, tech ecosystem, campus beauty', 'Fees very high, academic pressure, limited hostels', '2023', NULL, 8, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-06-06 15:44:39', '2026-06-21 15:44:39'),
('5d4a0e3b-d1fb-621f-6781-84ae77769b0f', '64e20c70-d8d7-402f-a700-53c759a659d4', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 5.0, 5.0, 5.0, 5.0, 5.0, NULL, 5.0, 5.0, 'trjythbtghfcg', 'yt fvtyc vyghf vfhg ctrhgf', 'v ygbc vytfc jtg', 't hgvgfh', '2016', NULL, 0, NULL, 'approved', '', 'a85f4bf4-5c3b-11f1-a48e-c8f7507a8de6', '2026-06-28 11:26:05', 0, NULL, 0, NULL, 0, 0, 0, '2026-06-28 11:25:20', '2026-06-28 11:26:05'),
('660c52e3-e729-b2b9-6b50-62701ee664db', '64e20c70-d8d7-402f-a700-53c759a659d4', 'col-iitm-0003', 4.8, 5.0, 4.8, 4.8, 5.0, 4.5, 4.5, 4.0, 'Paradise for Tech Enthusiasts', 'From Shaastra to Techfest, the technical culture is amazing. Research Park provides real-world exposure. The startup ecosystem within campus is growing rapidly.', 'Tech festivals, research park, industry collaborations', 'Rainy campus can get muddy, limited nightlife', '2022', NULL, 33, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-05-09 15:44:39', '2026-06-21 15:44:39'),
('662eee69-b290-a55b-8d1f-64b09b225ac2', 'usr00001-0000-0000-0000-000000000004', 'col-bits-0008', 4.5, 4.6, 4.4, 4.7, 5.0, 4.5, 4.3, 4.0, 'Pilani Campus is Beautiful', 'The Pilani campus is massive and beautiful with modern infrastructure. The desert landscape is unique. BITS has invested heavily in state-of-the-art labs and facilities.', 'Campus infrastructure, modern labs, sports facilities', 'Pilani is in Rajasthan desert, extreme weather', '2022', NULL, 36, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-03-11 15:44:39', '2026-06-21 15:44:39'),
('66d2647d-0af4-997f-3c0e-c99706f39585', 'usr00001-0000-0000-0000-000000000003', 'col-iitb-0001', 4.9, 5.0, 5.0, 4.5, 5.0, 4.0, 4.5, 3.5, 'Research Paradise', 'If you are interested in research, IIT Bombay is the place to be. The labs are well-equipped, and professors are always encouraging students to take up research projects.', 'Research infrastructure, funded projects, global collaborations', 'Bureaucracy in admin, outdated electives sometimes', '2021', NULL, 39, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-03-28 15:44:39', '2026-06-21 15:44:39'),
('7067b0b7-e3cf-fa77-e696-dcfc79ddd81d', 'usr00001-0000-0000-0000-000000000003', 'col-iima-0009', 4.8, 5.0, 5.0, 5.0, 4.5, 4.0, 4.0, 3.5, 'World-Class in Every Aspect', 'From faculty to curriculum to placements, IIMA delivers world-class quality. The alumni network spans Fortune 500 companies and leading startups globally.', 'Global recognition, alumni power, placement packages', 'Overwhelming competition, WAC assignments never end', '2021', NULL, 33, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-04-21 15:44:39', '2026-06-21 15:44:39'),
('72ea86f5-ada3-541c-d55d-e27593a074b4', '8b0478e7-602f-11f1-9ea0-a0510b1a7448', 'col-iitkgp-0005', 4.6, 4.7, 4.5, 4.6, 4.5, 4.0, 4.8, 3.5, 'Massive Campus, Endless Opportunities', 'IIT Kharagpur has the largest campus among all IITs. The variety of courses, clubs, and activities is unmatched. The brand value of the oldest IIT opens many doors.', 'Largest campus, most diverse courses, iconic brand', 'Very hot and humid weather, remote location', '2023', NULL, 9, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-06-02 15:44:39', '2026-06-21 15:44:39'),
('7b0972b2-484a-59b8-5bdf-3625f2f35554', 'usr00001-0000-0000-0000-000000000001', 'col-iitm-0003', 4.8, 4.9, 5.0, 4.7, 4.5, 4.0, 4.3, 3.8, 'Outstanding Education Quality', 'The curriculum is well-structured and updated regularly. Professors are approachable and genuinely interested in student growth. Lab facilities are world-class.', 'Curriculum quality, lab infrastructure, professor mentorship', 'Heavy coursework, competitive environment', '2023', NULL, 5, NULL, 'approved', NULL, NULL, NULL, 0, NULL, 0, NULL, 0, 0, 0, '2026-03-02 15:44:39', '2026-06-21 15:44:39'),
('7e403922-f84b-7024-38a4-765df394633b', 'usr00001-0000-0000-0000-000000000003', 'col-nitk-0007', 4.5, 4.5, 4.5, 4.6, 4.5, 4.0, 4.5, 3.5, 'Beautiful Campus by the Sea', 'NITK Surathkal has a stunning campus right next to the Arabian Sea. The combination of beach access and quality education makes it unique among all engineering colleges.', 'Beach campus, beautiful scenery, strong CS department', 'Humidity, snakes on campus sometimes, limited public transport', '2023', NULL, 38, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-06-19 15:44:39', '2026-06-21 15:44:39'),
('85619eff-b20e-24c2-1121-6bd963395eca', 'usr00001-0000-0000-0000-000000000004', 'col-iitd-0002', 4.6, 4.8, 4.6, 4.5, 4.0, 3.5, 4.5, 3.5, 'Great for Research and Higher Studies', 'The research culture at IIT Delhi is phenomenal. Many students go on to top PhD programs globally. The faculty actively publishes in top conferences and journals.', 'Research output, global collaborations, library resources', 'Admin processes slow, some labs outdated', '2021', NULL, 7, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-03-16 15:44:39', '2026-06-21 15:44:39'),
('8653cf99-acf6-6bcc-77cd-2e6aef60ce55', 'usr00001-0000-0000-0000-000000000004', 'col-iitk-0004', 4.8, 4.9, 4.8, 4.8, 4.5, 4.0, 4.5, 3.5, 'Strong CS Culture', 'The computer science department at IIT Kanpur is legendary. The coding culture is vibrant with regular hackathons and programming contests. Many alumni are at top tech companies.', 'CS reputation, coding culture, research opportunities', 'Other departments get less attention, weather extremes', '2022', NULL, 7, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-05-21 15:44:39', '2026-06-21 15:44:39'),
('9c021da6-da57-6ced-c3cd-98c60ba4c4c7', 'usr00001-0000-0000-0000-000000000002', 'col-aiims-0011', 4.9, 5.0, 5.0, 5.0, 4.0, 3.5, 4.0, 3.0, 'Proud to Be an AIIMSite', 'The three letters AIIMS carry immense pride and responsibility. The alumni network is the strongest in Indian healthcare. Training here transforms you into a confident, competent physician.', 'Alumni network, brand recognition, career opportunities', 'Social life limited, academic stress, old hostels', '2021', NULL, 3, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-02-11 15:44:39', '2026-06-21 15:44:39'),
('a720fc9f-2f34-98dd-8ca9-0c1b5757109a', 'usr00001-0000-0000-0000-000000000005', 'col-iitm-0003', 4.9, 5.0, 5.0, 4.8, 5.0, 4.5, 4.5, 4.0, 'Best IIT in India, Period', 'IIT Madras consistently ranks #1 in NIRF for a reason. The campus is lush green, the faculty is outstanding, and the academic atmosphere is unparalleled. The Chennai weather takes some getting used to.', '#1 NIRF ranking, beautiful campus, excellent faculty', 'Chennai heat, strict hostel rules, language barrier sometimes', '2023', NULL, 20, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-05-26 15:44:39', '2026-06-21 15:44:39'),
('b9ec3e01-ca1a-a40f-b53a-92cf9b18181d', 'usr00001-0000-0000-0000-000000000004', 'col-bits-0008', 4.6, 4.7, 4.5, 4.7, 5.0, 4.5, 4.5, 4.0, 'Premium Private Engineering College', 'BITS Pilani offers a unique practice school model where students get mandatory industry exposure. The campus is excellent and the peer group is incredibly talented.', 'Practice school model, great peer group, modern campus', 'Very expensive, tough grading, high competition', '2023', NULL, 38, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-06-08 15:44:39', '2026-06-21 15:44:39'),
('c0b0d172-9a02-2a7b-e9bc-edd318b7c089', '8b0478e7-602f-11f1-9ea0-a0510b1a7448', 'col-nlsiu-0012', 4.8, 5.0, 5.0, 4.8, 4.0, 4.0, 4.5, 3.5, 'Legal Excellence Redefined', 'NLSIU produces the finest lawyers, judges, and legal professionals in India. The curriculum is comprehensive and the faculty includes practicing advocates and renowned legal scholars.', 'Faculty quality, legal network, career diversity', 'Placement in law firms is intense, high expectations', '2023', NULL, 11, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-05-11 15:44:39', '2026-06-21 15:44:39'),
('c31967a0-791e-99a3-68d4-c60c8c0fdd17', 'usr00001-0000-0000-0000-000000000005', 'col-iitk-0004', 4.5, 4.7, 4.5, 4.7, 4.0, 4.0, 4.3, 3.0, 'Good But Could Be Better', 'Academics are great but infrastructure needs modernization. The campus is green and spacious. Placements are excellent across all branches.', 'Campus size, academic quality, placement record', 'Infrastructure aging, limited food options, remote location', '2023', NULL, 31, NULL, 'approved', NULL, NULL, NULL, 0, NULL, 0, NULL, 0, 0, 0, '2026-04-11 15:44:39', '2026-06-21 15:44:39'),
('c4f9956a-8291-ca07-b57b-be279c18fa25', 'usr00001-0000-0000-0000-000000000005', 'col-iitm-0003', 4.9, 5.0, 5.0, 4.8, 4.5, 4.0, 4.8, 4.0, 'Well-Rounded Experience', 'IIT Madras gave me everything - academics, research, cultural activities, and lifelong friendships. The alumni network is incredibly supportive and active worldwide.', 'Holistic development, alumni network, campus culture', 'Academic pressure can be intense, limited girls hostel space', '2021', NULL, 40, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-03-26 15:44:39', '2026-06-21 15:44:39'),
('c9457c6c-3957-1053-201b-a004f9f0d839', 'usr00001-0000-0000-0000-000000000002', 'col-iima-0009', 4.9, 5.0, 5.0, 5.0, 4.5, 4.0, 4.0, 3.5, 'Life-Changing Two Years', 'The PGP at IIMA is a life-changing experience. The rigor, the case studies, the sleepless nights - all build you into a complete professional. Placements are exceptional with median package over 35 LPA.', 'Transformative experience, top recruiters, global brand', 'Extremely hectic, no work-life balance, expensive canteen', '2023', NULL, 19, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-04-24 15:44:39', '2026-06-21 15:44:39'),
('cd307fff-201c-a7dc-86b7-f9efb572b44a', 'usr00001-0000-0000-0000-000000000004', 'col-iimb-0010', 4.8, 4.8, 4.8, 4.8, 4.5, 4.0, 4.5, 3.5, 'Tech-Savvy Management School', 'Being in Bangalore, IIMB has strong connections with the tech industry. Many tech founders and CXOs are regular guest speakers. The startup culture here is unmatched.', 'Tech industry connections, Bangalore ecosystem, diverse cohort', 'Heavy coursework, expensive living, hostel distance', '2022', NULL, 28, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-05-13 15:44:39', '2026-06-21 15:44:39'),
('d2130e34-feed-fa3c-daa4-84153c858cfa', '8b0478e7-602f-11f1-9ea0-a0510b1a7448', 'col-iitkgp-0005', 4.5, 4.6, 4.3, 4.6, 4.2, 3.8, 4.8, 3.5, 'Solid Academics, Growing Infrastructure', 'The academics are solid and placements have improved significantly over the years. The new buildings and labs are modern, though some old infrastructure still exists.', 'Placement growth, new infrastructure, diverse student body', 'Old hostel blocks, inconsistent faculty quality', '2023', NULL, 2, NULL, 'approved', NULL, NULL, NULL, 0, NULL, 0, NULL, 0, 0, 0, '2026-03-26 15:44:39', '2026-06-21 15:44:39'),
('d4213642-9d31-5162-7bfb-c80642094f1d', 'usr00001-0000-0000-0000-000000000004', 'col-aiims-0011', 4.8, 5.0, 5.0, 4.5, 4.0, 3.5, 3.5, 3.0, 'Dream College but Demanding', 'Getting into AIIMS was the hardest part. Living up to its standards is equally challenging. The professors are brilliant but expect nothing less than excellence. The patient exposure is truly world-class.', 'Brand value, patient diversity, academic rigor', 'Mental health challenges, sleep deprivation, limited campus area', '2023', NULL, 27, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-04-17 15:44:39', '2026-06-21 15:44:39'),
('d453ba53-86bb-1289-bd40-8524f3e5a482', '64e20c70-d8d7-402f-a700-53c759a659d4', 'col-iitd-0002', 4.5, 4.8, 4.5, 4.7, 4.0, 3.5, 4.5, 3.0, 'Strong Academics, Average Hostels', 'Academic rigour is excellent. Faculty is approachable and knowledgeable. But hostels are cramped and food could be better. Still, the brand opens many doors.', 'Academic quality, brand recognition, placement opportunities', 'Hostel conditions, mess food, small campus', '2023', NULL, 43, NULL, 'approved', NULL, NULL, NULL, 0, NULL, 0, NULL, 0, 0, 0, '2026-04-14 15:44:39', '2026-06-21 15:44:39'),
('e55e59d2-cdea-d9cb-0bab-3848763e5dcb', 'usr00001-0000-0000-0000-000000000003', 'col-nlsiu-0012', 4.7, 4.8, 4.8, 4.7, 4.0, 4.0, 4.5, 3.5, 'Top Law School, Period', 'NLSIU consistently ranks number 1 in law school rankings. The peer group is incredibly talented and diverse. The legal aid clinic gives real-world experience from second year itself.', '#1 ranking, peer quality, practical exposure, Bangalore city', 'Intense academic schedule, limited sports infrastructure', '2023', NULL, 14, NULL, 'approved', NULL, NULL, NULL, 0, NULL, 0, NULL, 0, 0, 0, '2026-04-08 15:44:39', '2026-06-21 15:44:39'),
('e58aeb1a-aeb1-8b43-4d34-ef9c8f695391', '64e20c70-d8d7-402f-a700-53c759a659d4', 'col-iimb-0010', 4.7, 4.8, 4.7, 4.8, 4.5, 4.0, 4.5, 3.5, 'Solid Brand, Great Placements', 'IIM Bangalore delivers on its promise of quality education and excellent placements. The PE/VC and consulting cohorts are particularly strong here.', 'Placement diversity, consulting/PE dominance, brand strength', 'Grade deflation, competitive peer group, work overload', '2023', NULL, 26, NULL, 'approved', NULL, NULL, NULL, 0, NULL, 0, NULL, 0, 0, 0, '2026-03-24 15:44:39', '2026-06-21 15:44:39'),
('e71e85b2-e939-19e2-e50e-9ba6a074ac71', 'usr00001-0000-0000-0000-000000000001', 'col-iitb-0001', 4.7, 4.8, 4.5, 5.0, 4.5, 4.0, 4.5, 3.5, 'Best Placement Scene in India', 'Placements at IIT Bombay are unmatched. Every year, top companies visit the campus and offer lucrative packages. The average package is around 22 LPA with highest going above 1 Cr.', 'Amazing placements, great peer group, startup culture', 'ACB attendance, hectic schedule', '2022', NULL, 20, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-05-15 15:44:38', '2026-06-21 15:44:38'),
('ea1b7486-68db-b913-188d-0c6ea87683fe', 'usr00001-0000-0000-0000-000000000002', 'col-iitk-0004', 4.7, 4.8, 4.8, 4.5, 4.5, 4.0, 4.5, 3.5, 'Best for Mathematics and Computing', 'If you are into mathematics and computing, IIT Kanpur is the best choice. The department has excellent faculty and research groups working on cutting-edge problems.', 'Math department strength, research groups, academic depth', 'Less industry exposure compared to Bombay and Delhi', '2021', NULL, 11, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-02-24 15:44:39', '2026-06-21 15:44:39'),
('ead3eccd-7b0a-45e0-2d92-9f61e94f98c6', 'usr00001-0000-0000-0000-000000000004', 'col-nitk-0007', 4.5, 4.5, 4.4, 4.6, 4.5, 4.0, 4.5, 3.5, 'Strong Technical Culture', 'NITK has a thriving technical culture with clubs like IEEE, ACM, and numerous technical festivals. The coding culture has improved tremendously and placements are on par with many IITs.', 'Technical clubs, improving placements, campus beauty', 'City Mangalore is average, limited intern opportunities locally', '2022', NULL, 31, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-05-04 15:44:39', '2026-06-21 15:44:39'),
('ecb825f9-7708-68eb-c23e-477bb0ae7e89', 'usr00001-0000-0000-0000-000000000005', 'col-nitt-0006', 4.6, 4.5, 4.5, 4.8, 4.2, 4.0, 4.0, 3.5, 'Value for Money Education', 'NIT Trichy offers world-class education at a fraction of IIT fees. The ROI is amazing. Companies like Google, Microsoft visit regularly for placements.', 'Affordable fees, strong placements, brand value', 'Campus infrastructure needs upgrade, limited sports facilities', '2022', NULL, 28, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-04-30 15:44:39', '2026-06-21 15:44:39'),
('ee512de1-a77a-60e1-09d3-ce41e58c8fc8', 'usr00001-0000-0000-0000-000000000002', 'col-aiims-0011', 4.7, 5.0, 5.0, 4.5, 4.5, 4.0, 3.5, 3.0, 'World-Class Research Environment', 'AIIMS has excellent research facilities and encourages students to participate in cutting-edge medical research. The publications in top journals speak for themselves.', 'Research infrastructure, publication opportunities, grants', 'Research competes with clinical duties, long hours', '2020', NULL, 5, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-01-13 15:44:39', '2026-06-21 15:44:39'),
('f7434c5f-6af5-a4e1-4412-8f1679d3ccca', 'usr00001-0000-0000-0000-000000000004', 'col-iitkgp-0005', 4.7, 4.5, 4.5, 4.6, 4.0, 4.0, 5.0, 4.0, 'Great Campus Life', 'The campus life at KGP is legendary. From Kshitij to Spring Fest, there is always something happening. The food courts, night canteens, and hostel culture create unforgettable memories.', 'Campus festivals, social life, food options, alumni network', 'Humidity, distance from Kolkata, some buildings old', '2022', NULL, 28, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-04-30 15:44:39', '2026-06-21 15:44:39'),
('ff05299d-3d58-d929-ef91-4685bb6c8fad', 'usr00001-0000-0000-0000-000000000001', 'col-iitk-0004', 4.7, 4.8, 4.7, 4.7, 4.5, 4.0, 4.5, 3.5, 'Academic Excellence with Open Culture', 'IIT Kanpur is known for its academic rigor and open culture. The freedom to explore diverse fields while maintaining academic standards is what sets it apart.', 'Academic freedom, strong CS department, beautiful campus', 'Remote location, limited city connectivity', '2023', NULL, 19, NULL, 'approved', NULL, NULL, NULL, 1, NULL, 0, NULL, 0, 0, 0, '2026-05-27 15:44:39', '2026-06-21 15:44:39');

--
-- Triggers `reviews`
--
DELIMITER $$
CREATE TRIGGER `trg_reviews_after_insert` AFTER INSERT ON `reviews` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'reviews', NEW.id, NULL,
        JSON_OBJECT('user_id', NEW.user_id, 'college_id', NEW.college_id, 'overall_rating', NEW.overall_rating, 'moderation_status', NEW.moderation_status),
        NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_reviews_after_update` AFTER UPDATE ON `reviews` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'reviews', NEW.id,
        JSON_OBJECT('moderation_status', OLD.moderation_status, 'overall_rating', OLD.overall_rating),
        JSON_OBJECT('moderation_status', NEW.moderation_status, 'overall_rating', NEW.overall_rating),
        NULL, NOW());
END
$$
DELIMITER ;

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

--
-- Triggers `roles`
--
DELIMITER $$
CREATE TRIGGER `trg_roles_after_delete` AFTER DELETE ON `roles` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'roles', OLD.id,
        JSON_OBJECT('role_name', OLD.role_name), NULL, NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_roles_after_insert` AFTER INSERT ON `roles` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'roles', NEW.id, NULL,
        JSON_OBJECT('role_name', NEW.role_name, 'permissions', NEW.permissions),
        NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_roles_after_update` AFTER UPDATE ON `roles` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'permission_change', 'roles', NEW.id,
        JSON_OBJECT('role_name', OLD.role_name, 'permissions', OLD.permissions),
        JSON_OBJECT('role_name', NEW.role_name, 'permissions', NEW.permissions),
        NULL, NOW());
END
$$
DELIMITER ;

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
-- Table structure for table `saved_colleges`
--

CREATE TABLE `saved_colleges` (
  `id` int(11) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `college_id` varchar(50) NOT NULL,
  `university_id` varchar(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_colleges`
--

INSERT INTO `saved_colleges` (`id`, `user_id`, `college_id`, `university_id`, `created_at`) VALUES
(1, '64e20c70-d8d7-402f-a700-53c759a659d4', 'col-iima-0009', NULL, '2026-06-28 11:16:45');

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

--
-- Triggers `scholarships`
--
DELIMITER $$
CREATE TRIGGER `trg_scholarships_after_delete` AFTER DELETE ON `scholarships` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'scholarships', OLD.id,
        JSON_OBJECT('scholarship_name', OLD.scholarship_name), NULL, NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_scholarships_after_insert` AFTER INSERT ON `scholarships` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'scholarships', NEW.id, NULL,
        JSON_OBJECT('scholarship_name', NEW.scholarship_name, 'status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_scholarships_after_update` AFTER UPDATE ON `scholarships` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'scholarships', NEW.id,
        JSON_OBJECT('scholarship_name', OLD.scholarship_name, 'status', OLD.status),
        JSON_OBJECT('scholarship_name', NEW.scholarship_name, 'status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;

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
('ab189381-23b1-41cd-ace8-5afe22468829', 'college', 'e696ffa2-1ff6-45c5-8102-c4b494b5fac4', 'ghxvhdcxfvbeyux', 'Canonical URL\r\nOG Image File\r\nNo file chosen\r\nSchema Markup\r\n\r\nMark Page as No-Index (Do not show on Google)\r\n\r\nSave SEO Settings', NULL, '', 'https://localhost/ADMISSION/college.php?id=e696ffa2-1ff6-45c5-8102-c4b494b5fac4', '{\n    \"@context\": \"https://schema.org\",\n    \"@type\": \"CollegeOrUniversity\",\n    \"name\": \"fhfhrfhfrh\",\n    \"description\": \"\",\n    \"url\": \"https://githib.com\",\n    \"telephone\": \"09877275894\",\n    \"address\": {\n        \"@type\": \"PostalAddress\",\n        \"streetAddress\": \"WARD 24, OSWALI MOHALLA\"\n    }\n}', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

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

--
-- Dumping data for table `spam_detection_logs`
--

INSERT INTO `spam_detection_logs` (`id`, `user_id`, `ip_address`, `device_fingerprint`, `duplicate_content_score`, `velocity_flag`, `vpn_detected`, `proxy_detected`, `created_at`) VALUES
('ea8d8bd0-7313-11f1-81b7-a0510b1a7448', NULL, '45.33.32.156', 'fp-suspicious-001', 0.85, 1, 0, 0, '2026-06-28 15:07:35'),
('ea8dd241-7313-11f1-81b7-a0510b1a7448', NULL, '103.21.244.0', NULL, 0.92, 1, 0, 1, '2026-06-28 12:07:35'),
('ea8dd610-7313-11f1-81b7-a0510b1a7448', NULL, '203.0.113.50', NULL, 0.45, 0, 0, 0, '2026-06-28 09:07:35'),
('ea8dd77c-7313-11f1-81b7-a0510b1a7448', NULL, '198.51.100.42', 'fp-review-bomb', 0.78, 1, 0, 0, '2026-06-27 17:07:35'),
('ea8dd85b-7313-11f1-81b7-a0510b1a7448', NULL, '45.33.32.156', 'fp-suspicious-001', 0.91, 1, 0, 0, '2026-06-27 17:07:35'),
('ea8dd929-7313-11f1-81b7-a0510b1a7448', NULL, '203.0.113.50', NULL, 0.55, 0, 1, 0, '2026-06-26 17:07:35'),
('ea8dda97-7313-11f1-81b7-a0510b1a7448', NULL, '192.0.2.100', NULL, 0.3, 0, 0, 0, '2026-06-26 17:07:35'),
('ea8ddc01-7313-11f1-81b7-a0510b1a7448', NULL, '103.21.244.0', NULL, 0.88, 1, 0, 1, '2026-06-25 17:07:35'),
('ea8ddd75-7313-11f1-81b7-a0510b1a7448', NULL, '45.33.32.156', 'fp-suspicious-001', 0.72, 1, 0, 0, '2026-06-24 17:07:35'),
('ea8dde8d-7313-11f1-81b7-a0510b1a7448', NULL, '203.0.113.50', NULL, 0.4, 0, 0, 0, '2026-06-23 17:07:35'),
('ea8de01b-7313-11f1-81b7-a0510b1a7448', NULL, '198.51.100.42', 'fp-review-bomb', 0.95, 1, 0, 1, '2026-06-22 17:07:35'),
('ea8de29a-7313-11f1-81b7-a0510b1a7448', NULL, '192.0.2.100', NULL, 0.25, 0, 0, 0, '2026-06-21 17:07:35');

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

--
-- Triggers `subscriptions`
--
DELIMITER $$
CREATE TRIGGER `trg_subscriptions_after_insert` AFTER INSERT ON `subscriptions` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'subscriptions', NEW.id, NULL,
        JSON_OBJECT('college_id', NEW.college_id, 'amount', NEW.amount, 'status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_subscriptions_after_update` AFTER UPDATE ON `subscriptions` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'status_change', 'subscriptions', NEW.id,
        JSON_OBJECT('status', OLD.status),
        JSON_OBJECT('status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;

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

--
-- Dumping data for table `system_health`
--

INSERT INTO `system_health` (`id`, `service_name`, `status`, `cpu_usage`, `memory_usage`, `response_time_ms`, `last_checked_at`) VALUES
('b146e4db-730c-11f1-81b7-a0510b1a7448', 'MySQL Database', 'healthy', 12.4, 34.2, 8, '2026-06-28 16:15:53'),
('b1470050-730c-11f1-81b7-a0510b1a7448', 'PHP Runtime', 'healthy', 8.1, 28.6, 3, '2026-06-28 16:15:53'),
('b147012b-730c-11f1-81b7-a0510b1a7448', 'File Storage', 'healthy', 5.2, 18.9, 2, '2026-06-28 16:15:53'),
('b1470179-730c-11f1-81b7-a0510b1a7448', 'Email Service', 'healthy', 2.1, 12.4, 145, '2026-06-28 16:15:53'),
('b14701b5-730c-11f1-81b7-a0510b1a7448', 'Cache Layer', 'healthy', 6.8, 22.1, 4, '2026-06-28 16:15:53'),
('b14701ee-730c-11f1-81b7-a0510b1a7448', 'Payment Gateway', 'warning', 45.2, 62.8, 320, '2026-06-28 16:15:53'),
('b147022d-730c-11f1-81b7-a0510b1a7448', 'CDN Edge', 'healthy', 15.6, 41.3, 12, '2026-06-28 16:15:53'),
('b147026a-730c-11f1-81b7-a0510b1a7448', 'Search Index', 'healthy', 22.4, 55.7, 45, '2026-06-28 16:15:53');

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
('02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', 'Indian Institute of Management Ahmedabad', 'iim-ahmedabad', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'govt', 'central', 'active', 1, 1, 0, 1, NULL, NULL, 150, 11, '1961', 0, 0, 'A++', 1, 1, 1, 1200, 120, 107, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 06:51:51', '2026-07-02 06:51:51', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('a7b8c9d0-e1f2-3456-abcd-567890123456', 'Jadavpur University', 'jadavpur-university', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'govt', 'state', 'active', 0, 1, 0, 1, 8, NULL, 713, 35, '1955', 0, 0, 'A', 0, 1, 1, 0, 10000, 500, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 06:11:59', '2026-07-02 07:08:39', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('b2c3d4e5-f6a7-8901-bcde-f12345678901', 'University of Delhi', 'delhi-university', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'govt', 'central', 'active', 1, 1, 0, 1, 3, NULL, NULL, 9, '1922', 0, 0, '', 0, 1, 1, 0, 300000, 4500, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 06:11:59', '2026-07-02 07:50:40', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('b8c9d0e1-f2a3-4567-bcde-678901234567', 'Vellore Institute of Technology', 'vit-vellore', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'deemed', 'private_trust', 'active', 1, 1, 0, 0, 9, NULL, 544, 30, '1984', 0, 0, 'A', 0, 1, 1, 1, 5000, 300, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 06:11:59', '2026-07-02 07:08:39', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('c9d0e1f2-a3b4-5678-cdef-789012345678', 'SRM Institute of Science and Technology', 'srm-chennai', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'deemed', 'private_trust', 'active', 0, 1, 0, 0, 10, NULL, 544, 30, '1985', 0, 0, 'A', 0, 1, 1, 1, 6000, 350, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 06:11:59', '2026-07-02 07:08:39', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('d0e1f2a3-b4c5-6789-defa-890123456789', 'Manipal Academy of Higher Education', 'manipal', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'deemed', 'private_trust', 'active', 1, 1, 0, 0, 11, NULL, 267, 16, '1953', 0, 0, 'A+', 0, 1, 1, 1, 8000, 400, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 06:11:59', '2026-07-02 07:08:39', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('d4e5f6a7-b8c9-0123-defa-234567890123', 'Birla Institute of Technology and Science Pilani', 'bits-pilani', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'deemed', 'private_trust', 'active', 1, 1, 0, 0, 5, NULL, 506, 28, '1964', 0, 0, 'A', 0, 1, 1, 1, 4500, 350, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 06:11:59', '2026-07-02 07:08:39', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('e1f2a3b4-c5d6-7890-efab-901234567890', 'National Institute of Technology Tiruchirappalli', 'nit-trichy', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'govt', 'central', 'active', 1, 1, 0, 1, 12, NULL, 544, 30, '1964', 0, 0, 'A', 0, 1, 1, 1, 5000, 350, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 06:11:59', '2026-07-02 07:08:39', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('e5f6a7b8-c9d0-1234-efab-345678901234', 'Jawaharlal Nehru University', 'jnu-delhi', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'govt', 'central', 'active', 1, 1, 0, 1, 6, NULL, 139, 9, '1969', 0, 0, 'A++', 0, 1, 1, 0, 8000, 400, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 06:11:59', '2026-07-02 07:08:39', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('f6a7b8c9-d0e1-2345-fabc-456789012345', 'Anna University', 'anna-university', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'govt', 'state', 'active', 1, 1, 0, 1, 7, NULL, 544, 30, '1978', 0, 0, 'A', 0, 1, 1, 1, 18000, 800, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 06:11:59', '2026-07-02 07:08:39', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-a001', 'Indian Institute of Science', 'iisc-bangalore', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'autonomous', 'central', 'active', 0, 0, 0, NULL, NULL, NULL, 267, 16, '1909', 0, 0, 'A++', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-a002', 'Indian Institute of Technology Delhi', 'iit-delhi', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'autonomous', 'central', 'active', 0, 0, 0, NULL, NULL, NULL, 139, 9, '1961', 0, 0, 'A++', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-a003', 'Indian Institute of Technology Kanpur', 'iit-kanpur', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'autonomous', 'central', 'active', 0, 0, 0, NULL, NULL, NULL, 627, 34, '1959', 0, 0, 'A++', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-a004', 'Indian Institute of Technology Kharagpur', 'iit-kharagpur', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'autonomous', 'central', 'active', 0, 0, 0, NULL, NULL, NULL, 713, 35, '1951', 0, 0, 'A++', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-a005', 'Indian Institute of Technology Roorkee', 'iit-roorkee', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'autonomous', 'central', 'active', 0, 0, 0, NULL, NULL, NULL, 614, 33, '0000', 0, 0, 'A++', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-a006', 'Indian Institute of Technology Guwahati', 'iit-guwahati', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'autonomous', 'central', 'active', 0, 0, 0, NULL, NULL, NULL, 35, 3, '1994', 0, 0, 'A++', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-a007', 'Indian Institute of Technology Hyderabad', 'iit-hyderabad', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'autonomous', 'central', 'active', 0, 0, 0, NULL, NULL, NULL, 575, 31, '2008', 0, 0, 'A++', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-a008', 'Birla Institute of Technology Mesra', 'bit-mesra', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'autonomous', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 70, 15, '1955', 0, 0, 'A', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-a009', 'PSG College of Technology', 'psg-coimbatore', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'autonomous', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 544, 30, '1926', 0, 0, 'A', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-a010', 'Thiagarajar College of Engineering', 'tce-madurai', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'autonomous', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 544, 30, '1957', 0, 0, 'A+', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-d001', 'Thapar Institute of Engineering and Technology', 'thapar-university', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'deemed', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 484, 27, '1956', 0, 0, 'A', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-d002', 'Symbiosis International University', 'symbiosis-pune', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'deemed', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 393, 20, '1971', 0, 0, 'A+', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-d003', 'Amrita Vishwa Vidyapeetham', 'amrita-coimbatore', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'deemed', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 544, 30, '1994', 0, 0, 'A+', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-d004', 'Bharathidasan University', 'bharathidasan-uni', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'deemed', 'state', 'active', 0, 0, 0, NULL, NULL, NULL, 544, 30, '1982', 0, 0, 'A', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-d005', 'Siksha O Anusandhan University', 'soa-bhubaneswar', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'deemed', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 450, 25, '1996', 0, 0, 'A+', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-d006', 'KIIT University', 'kiit-bhubaneswar', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'deemed', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 450, 25, '1992', 0, 0, 'A+', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-d007', 'Shiv Nadar University', 'shiv-nadar-uni', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'deemed', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 627, 34, '2011', 0, 0, 'A', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-d008', 'Dr. D.Y. Patil Vidyapeeth', 'dyp-pune', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'deemed', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 393, 20, '1996', 0, 0, 'A+', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-d009', 'Tata Institute of Social Sciences', 'tiss-mumbai', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'deemed', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 393, 20, '1936', 0, 0, 'A++', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-d010', 'Gujarat Forensic Sciences University', 'gfsu-gandhinagar', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'deemed', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 150, 11, '2008', 0, 0, 'A', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-g001', 'Indian Institute of Technology Ropar', 'iit-ropar', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'govt', 'central', 'active', 0, 0, 0, NULL, NULL, NULL, 484, 27, '2008', 0, 0, 'A++', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-g002', 'National Institute of Technology Warangal', 'nit-warangal', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'govt', 'central', 'active', 0, 0, 0, NULL, NULL, NULL, 575, 31, '1959', 0, 0, 'A', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-g003', 'National Institute of Technology Surathkal', 'nitk-surathkal', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'govt', 'central', 'active', 0, 0, 0, NULL, NULL, NULL, 267, 16, '1960', 0, 0, 'A+', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-g004', 'Motilal Nehru National Institute of Technology', 'mnnit-allahabad', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'govt', 'central', 'active', 0, 0, 0, NULL, NULL, NULL, 629, 34, '1961', 0, 0, 'A+', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-g005', 'National Institute of Technology Calicut', 'nit-calicut', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'govt', 'central', 'active', 0, 0, 0, NULL, NULL, NULL, 293, 17, '1961', 0, 0, 'A+', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-g006', 'Malaviya National Institute of Technology', 'mnit-jaipur', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'govt', 'central', 'active', 0, 0, 0, NULL, NULL, NULL, 521, 28, '1963', 0, 0, 'A+', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-g007', 'Visvesvaraya National Institute of Technology', 'vnit-nagpur', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'govt', 'central', 'active', 0, 0, 0, NULL, NULL, NULL, 393, 20, '1960', 0, 0, 'A', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-g008', 'Sardar Vallabhbhai National Institute of Technology', 'svnit-surat', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'govt', 'central', 'active', 0, 0, 0, NULL, NULL, NULL, 150, 11, '1961', 0, 0, 'A+', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-p001', 'Lovely Professional University', 'lpu-university', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'private', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 484, 27, '2005', 0, 0, 'A+', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-p002', 'Chandigarh University', 'cuchd-mohali', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'private', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 484, 27, '2012', 0, 0, 'A+', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-p003', 'Amity University Noida', 'amity-university', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'private', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 732, 34, '2005', 0, 0, 'A+', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-p004', 'Sharda University Greater Noida', 'sharda-university', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'private', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 733, 34, '2009', 0, 0, 'A', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-p005', 'Parul University Vadodara', 'parul-university', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'private', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 150, 11, '1995', 0, 0, 'A+', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-p006', 'Ashoka University', 'ashoka-sonipat', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'private', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 183, 12, '2014', 0, 0, 'A', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-p007', 'Kalinga Institute of Industrial Technology', 'kiit-university', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'private', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 450, 25, '1992', 0, 0, 'A+', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u-p008', 'Bharati Vidyapeeth University', 'bvp-pune', 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80', 'private', 'private_trust', 'active', 0, 0, 0, NULL, NULL, NULL, 393, 20, '1964', 0, 0, 'A+', 1, 1, 0, 0, 0, NULL, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 07:13:31', '2026-07-02 07:13:31', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u1a00001-0001-0001-0001-000000000001', 'Banaras Hindu University', 'bhu-varanasi', NULL, NULL, 'govt', 'central', 'active', 1, 1, 0, 5, NULL, NULL, 713, 34, '1916', 0, 0, 'A++', 1, 0, 0, 30000, 1500, 1300, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 06:31:41', '2026-07-02 06:31:41', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u1a00001-0001-0001-0001-000000000002', 'Aligarh Muslim University', 'amu-aligarh', NULL, NULL, 'govt', 'central', 'active', 1, 1, 0, NULL, NULL, NULL, 628, 34, '1920', 0, 0, 'A++', 1, 0, 0, 25000, 1200, 1100, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 06:37:05', '2026-07-02 07:50:40', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u1a00001-0001-0001-0001-000000000003', 'Savitribai Phule Pune University', 'sppu-pune', NULL, NULL, 'govt', 'state', 'active', 0, 1, 0, 12, NULL, NULL, 393, 20, '1949', 0, 0, 'A', 1, 0, 0, 500000, 300, 400, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 06:31:41', '2026-07-02 06:31:41', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u1a00001-0001-0001-0001-000000000004', 'University of Mumbai', 'mumbai-university', NULL, NULL, 'govt', 'state', 'active', 0, 1, 0, NULL, NULL, NULL, NULL, 20, '0000', 0, 0, 'A++', 1, 0, 0, 700000, 3500, 530, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 06:37:05', '2026-07-02 06:37:05', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft'),
('u1a00001-0001-0001-0001-000000000005', 'University of Calcutta', 'cu-kolkata', NULL, NULL, 'govt', 'state', 'active', 0, 1, 0, 10, NULL, NULL, 713, 35, '0000', 0, 0, 'A+', 1, 0, 0, 400000, 2000, 64, 'unverified', NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 06:31:41', '2026-07-02 06:31:41', NULL, NULL, NULL, 0, 0, NULL, 0, 'draft');

--
-- Triggers `universities`
--
DELIMITER $$
CREATE TRIGGER `trg_universities_after_delete` AFTER DELETE ON `universities` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'universities', OLD.id,
        JSON_OBJECT('name', OLD.name), NULL, NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_universities_after_insert` AFTER INSERT ON `universities` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'create', 'universities', NEW.id, NULL,
        JSON_OBJECT('name', NEW.name, 'slug', NEW.slug, 'status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_universities_after_update` AFTER UPDATE ON `universities` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'update', 'universities', NEW.id,
        JSON_OBJECT('name', OLD.name, 'status', OLD.status),
        JSON_OBJECT('name', NEW.name, 'status', NEW.status),
        NULL, NOW());
END
$$
DELIMITER ;

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
('adm-amu-001', 'u1a00001-0001-0001-0001-000000000002', 'Admission to Aligarh Muslim University (AMU) is done through multiple entrance examinations:\n\n1. B.Tech Programs: Through AMU Entrance Examination (AMUEEE) conducted by the university. Candidates must have passed 10+2 with Physics, Chemistry, and Mathematics with minimum 50% marks.\n\n2. MBA Programs: Through AMU Management Entrance Test (AMUMET). Candidates need a Bachelor degree with minimum 50% marks.\n\n3. B.A. LLB: Through AMU Law Entrance Examination. Candidates must have passed 10+2 with minimum 50% marks.\n\n4. Medical Programs (MBBS/BDS): Through NEET UG score followed by AMU counselling.\n\n5. Other UG/PG Programs: Through respective AMU entrance examinations conducted department-wise.\n\nThe admission process includes:\n- Online application on AMU official website\n- Download admit card\n- Appear for entrance examination\n- Result declaration\n- counselling and document verification\n- Seat allotment and fee payment', '[\"AMUEEE\",\"AMUMET\",\"NEET UG\",\"AMU Law Entrance\",\"AMU Entrance Exam\"]', '2026-03-01', '2026-05-31', 1, 0, 0, 0, 1, 'online', 'Selection is purely based on merit in the entrance examination. For B.Tech programs, the ranking is based on AMUEEE score. For MBA, AMUMET score is considered. Medical programs use NEET UG score. There is no management quota at AMU as it is a central government university.');

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
('uc-02f1f361', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', 'https://www.iima.ac.in', 'admissions@iima.ac.in', '079-63066000', 'Vastrapur, Ahmedabad, Gujarat 380015', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-03', 'b2c3d4e5-f6a7-8901-bcde-f12345678901', 'https://www.du.ac.in', 'info@du.ac.in', '011-27667011', 'North Campus, Delhi 110007', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-05', 'd4e5f6a7-b8c9-0123-defa-234567890123', 'https://www.bits-pilani.ac.in', 'admission@bits-pilani.ac.in', '01596-245022', 'Pilani, Rajasthan 333031', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-06', 'e5f6a7b8-c9d0-1234-efab-345678901234', 'https://www.jnu.ac.in', 'info@jnu.ac.in', '011-26704234', 'New Mehrauli Road, New Delhi 110067', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-07', 'f6a7b8c9-d0e1-2345-fabc-456789012345', 'https://www.annauniv.edu', 'dean@annauniv.edu', '044-22358000', 'Sardar Patel Road, Chennai 600025', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-08', 'a7b8c9d0-e1f2-3456-abcd-567890123456', 'https://www.jadavpur.edu', 'info@jadavpur.edu', '033-24146330', '188 Raja S C Mallick Road, Kolkata 700032', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-09', 'b8c9d0e1-f2a3-4567-bcde-678901234567', 'https://www.vit.ac.in', 'admission@vit.ac.in', '0416-2202000', 'Vellore, Tamil Nadu 632014', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-10', 'c9d0e1f2-a3b4-5678-cdef-789012345678', 'https://www.srmist.edu.in', 'admissions@srmist.edu.in', '044-27417000', 'Kattankulathur, Chennai 603203', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-11', 'd0e1f2a3-b4c5-6789-defa-890123456789', 'https://manipal.edu', 'admissions@manipal.edu', '0820-2571000', 'Manipal, Karnataka 576104', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-12', 'e1f2a3b4-c5d6-7890-efab-901234567890', 'https://www.nitt.edu', 'dean@nitt.edu', '0431-2503000', 'Tiruchirappalli, Tamil Nadu 620015', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-a01', 'u-a001', 'https://www.iisc.ac.in', 'admissions@iisc.ac.in', '080-22932275', 'Bangalore, Karnataka 560012', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-a02', 'u-a002', 'https://www.iitd.ac.in', 'admissions@iitd.ac.in', '011-26591729', 'New Delhi 110016', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-a03', 'u-a003', 'https://www.iitk.ac.in', 'admissions@iitk.ac.in', '0512-2597637', 'Kanpur, UP 208016', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-a04', 'u-a004', 'https://www.iitkgp.ac.in', 'admissions@iitkgp.ac.in', '03222-281000', 'Kharagpur, WB 721302', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-a05', 'u-a005', 'https://www.iitr.ac.in', 'admissions@iitr.ac.in', '01332-285311', 'Roorkee, UK 247667', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-a06', 'u-a006', 'https://www.iitg.ac.in', 'admissions@iitg.ac.in', '0361-2583000', 'Guwahati, Assam 781039', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-a07', 'u-a007', 'https://www.iith.ac.in', 'admissions@iith.ac.in', '040-23016000', 'Hyderabad, TS 502285', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-a08', 'u-a008', 'https://www.bitmesra.ac.in', 'admissions@bitmesra.ac.in', '0651-2275444', 'Ranchi, JH 835215', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-a09', 'u-a009', 'https://www.psgtech.edu', 'admissions@psgtech.edu', '0422-2570170', 'Coimbatore, TN 641004', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-a10', 'u-a010', 'https://www.tce.edu', 'admissions@tce.edu', '0452-2482093', 'Madurai, TN 625015', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-d01', 'u-d001', 'https://www.thapar.edu.in', 'admissions@thapar.edu', '0175-2393021', 'Patiala, Punjab 147004', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-d02', 'u-d002', 'https://www.siu.edu.in', 'admissions@siu.ac.in', '020-28165155', 'Pune, Maharashtra 411014', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-d03', 'u-d003', 'https://www.amrita.edu', 'admissions@amrita.edu', '0422-2685000', 'Coimbatore, Tamil Nadu 641112', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-d05', 'u-d005', 'https://www.soa.ac.in', 'admissions@soa.ac.in', '0674-2351915', 'Bhubaneswar, Odisha 751030', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-d06', 'u-d006', 'https://www.kiit.ac.in', 'admissions@kiit.ac.in', '0674-2742103', 'Bhubaneswar, Odisha 751024', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-d07', 'u-d007', 'https://www.shivnadar.ac.in', 'admissions@shiksha.com', '0120-4567890', 'Greater Noida, UP 201314', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-d08', 'u-d008', 'https://www.dypatiluniversity.org', 'admissions@dypatil.edu', '020-25664927', 'Pune, Maharashtra 411033', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-d09', 'u-d009', 'https://www.tiss.edu', 'admissions@tiss.edu', '022-25525000', 'Mumbai, Maharashtra 400088', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-d10', 'u-d010', 'https://www.gfsu.edu.in', 'info@gfsu.edu.in', '079-23977000', 'Gandhinagar, Gujarat 382007', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-g01', 'u-g001', 'https://www.iitrpr.ac.in', 'admissions@iitrpr.ac.in', '01881-231032', 'Rupnagar, Punjab 140001', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-g02', 'u-g002', 'https://www.nitw.ac.in', 'admissions@nitw.ac.in', '0870-2462035', 'Warangal, TS 506004', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-g03', 'u-g003', 'https://www.nitk.edu.in', 'admissions@nitk.edu.in', '0824-2474000', 'Mangalore, KA 575025', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-g04', 'u-g004', 'https://www.mnnit.ac.in', 'admissions@mnnit.ac.in', '0532-2545501', 'Prayagraj, UP 211004', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-g05', 'u-g005', 'https://www.nitc.ac.in', 'admissions@nitc.ac.in', '0495-2281303', 'Kozhikode, KL 673601', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-g06', 'u-g006', 'https://www.mnit.ac.in', 'admissions@mnit.ac.in', '0141-2529093', 'Jaipur, RJ 302017', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-g07', 'u-g007', 'https://www.vnit.ac.in', 'admissions@vnit.ac.in', '0712-2801500', 'Nagpur, MH 440010', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-g08', 'u-g008', 'https://www.svnit.ac.in', 'admissions@svnit.ac.in', '0261-2202000', 'Surat, GJ 395007', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-n01', 'u1a00001-0001-0001-0001-000000000001', 'https://www.bhu.ac.in', 'info@bhu.ac.in', '0542-2307254', 'Varanasi, UP 221005', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-n03', 'u1a00001-0001-0001-0001-000000000003', 'https://www.unipune.ac.in', 'info@unipune.ac.in', '020-25601000', 'Pune, Maharashtra 411007', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-n05', 'u1a00001-0001-0001-0001-000000000005', 'https://www.cu.ac.in', 'info@cu.ac.in', '033-22410071', '87/1 College Street, Kolkata 700073', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-p01', 'u-p001', 'https://www.lpu.in', 'admissions@lpu.co.in', '01824-517000', 'Phagwara, Punjab 144411', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-p02', 'u-p002', 'https://www.cuchd.in', 'admissions@cuchd.in', '0172-3931000', 'Mohali, Punjab 140413', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-p03', 'u-p003', 'https://www.amity.edu', 'admissions@amity.edu', '0120-4392500', 'Noida, UP 201301', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-p04', 'u-p004', 'https://www.sharda.ac.in', 'admissions@sharda.ac.in', '0120-4567890', 'Greater Noida, UP 201306', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-p05', 'u-p005', 'https://paruluniversity.ac.in', 'info@paruluniversity.ac.in', '0265-2652100', 'Vadodara, GJ 391760', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-p06', 'u-p006', 'https://www.ashoka.edu.in', 'admissions@ashoka.edu.in', '0130-2300100', 'Sonipat, HR 131029', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-p07', 'u-p007', 'https://www.kiit.ac.in', 'admissions@kiit.ac.in', '0674-2742103', 'Bhubaneswar, OD 751024', NULL, NULL, NULL, NULL, NULL, NULL),
('uc-p08', 'u-p008', 'https://www.bharatividyapeeth.edu', 'admissions@bvp.ac.in', '020-25654038', 'Pune, MH 411043', NULL, NULL, NULL, NULL, NULL, NULL);

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
('uc-a7b8c9d0', 'a7b8c9d0-e1f2-3456-abcd-567890123456', 'Jadavpur University is a premier public state university in Kolkata, West Bengal. Established in 1955, it offers programs in engineering, science, arts, and commerce.', NULL, NULL, NULL, NULL),
('uc-b2c3d4e5', 'b2c3d4e5-f6a7-8901-bcde-f12345678901', 'University of Delhi is one of the largest and most prestigious universities in India. Established in 1922, it offers undergraduate, postgraduate, and doctoral programs across arts, science, commerce, and professional courses.', NULL, NULL, NULL, NULL),
('uc-b8c9d0e1', 'b8c9d0e1-f2a3-4567-bcde-678901234567', 'VIT Vellore is a private deemed university in Vellore, Tamil Nadu. Known for its VITEEE entrance exam and strong placement record. Offers B.Tech, M.Tech, MBA, and PhD programs.', NULL, NULL, NULL, NULL),
('uc-c9d0e1f2', 'c9d0e1f2-a3b4-5678-cdef-789012345678', 'SRM Institute of Science and Technology is a private deemed university in Chennai. Known for its SRMJEEE entrance exam and modern campus infrastructure.', NULL, NULL, NULL, NULL),
('uc-d0e1f2a3', 'd0e1f2a3-b4c5-6789-defa-890123456789', 'Manipal Academy of Higher Education (MAHE) is a private deemed university in Manipal, Karnataka. Known for its medical, engineering, and management programs.', NULL, NULL, NULL, NULL),
('uc-d4e5f6a7', 'd4e5f6a7-b8c9-0123-defa-234567890123', 'BITS Pilani is one of the top private engineering institutions in India. Known for its unique Practice School program and strong industry connections. Offers B.E., M.E., MBA, and PhD programs.', NULL, NULL, NULL, NULL),
('uc-e1f2a3b4', 'e1f2a3b4-c5d6-7890-efab-901234567890', 'NIT Trichy is one of the top NITs in India. Established in 1964, it offers B.Tech, M.Tech, MCA, MBA, and PhD programs. Known for its strong placement record and research output.', NULL, NULL, NULL, NULL),
('uc-e5f6a7b8', 'e5f6a7b8-c9d0-1234-efab-345678901234', 'Jawaharlal Nehru University is a premier research university in New Delhi. Established in 1969, it is known for its strong emphasis on social sciences, humanities, and international studies.', NULL, NULL, NULL, NULL),
('uc-f6a7b8c9', 'f6a7b8c9-d0e1-2345-fabc-456789012345', 'Anna University is a technical university in Chennai, Tamil Nadu. It offers engineering, technology, and management programs through its affiliated colleges across the state.', NULL, NULL, NULL, NULL),
('ucn-02f1f361', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', 'Indian Institute of Management Ahmedabad is a reputed institution of higher education in India, offering a wide range of undergraduate, postgraduate, and doctoral programs.', NULL, NULL, NULL, NULL),
('ucn-a01', 'u-a001', 'Indian Institute of Science Bangalore is India premier research university. Founded in 1909, it consistently ranks as the top university for research.', NULL, NULL, NULL, NULL),
('ucn-a02', 'u-a002', 'IIT Delhi is one of the most prestigious engineering institutions. Established in 1961, it offers B.Tech, M.Tech, MBA, and PhD programs.', NULL, NULL, NULL, NULL),
('ucn-a03', 'u-a003', 'IIT Kanpur was established in 1959. Known for its strong computer science program and research output across multiple disciplines.', NULL, NULL, NULL, NULL),
('ucn-a04', 'u-a004', 'IIT Kharagpur was established in 1951 as the first IIT. It is the largest IIT with 19 departments.', NULL, NULL, NULL, NULL),
('ucn-a05', 'u-a005', 'IIT Roorkee was established in 1847. It is one of the oldest technical institutions in Asia.', NULL, NULL, NULL, NULL),
('ucn-d01', 'u-d001', 'Thapar Institute of Engineering and Technology is a private deemed university in Patiala, Punjab. Established in 1956, it is known for its engineering and management programs.', NULL, NULL, NULL, NULL),
('ucn-d02', 'u-d002', 'Symbiosis International University is a private deemed university in Pune, Maharashtra. Established in 1971, it offers programs across management, law, and sciences.', NULL, NULL, NULL, NULL),
('ucn-d03', 'u-d003', 'Amrita Vishwa Vidyapeetham is a private deemed university headquartered in Coimbatore. Founded in 1994, it offers programs in engineering, medicine, and social sciences.', NULL, NULL, NULL, NULL),
('ucn-d06', 'u-d006', 'Kalinga Institute of Industrial Technology is a private university in Bhubaneswar, Odisha. Established in 1992, it offers 100+ programs with strong placement record.', NULL, NULL, NULL, NULL),
('ucn-d09', 'u-d009', 'Tata Institute of Social Sciences is a deemed university in Mumbai. Founded in 1936, it is premier institution for social sciences and public policy.', NULL, NULL, NULL, NULL),
('ucn-g02', 'u-g002', 'NIT Warangal was established in 1959. It is one of the top NITs known for engineering programs and placements.', NULL, NULL, NULL, NULL),
('ucn-g03', 'u-g003', 'NIT Karnataka Surathkal was established in 1960. Located on the coast, it offers engineering, science, and management.', NULL, NULL, NULL, NULL),
('ucn-n01', 'u1a00001-0001-0001-0001-000000000001', 'Banaras Hindu University (BHU) is a public central university in Varanasi, Uttar Pradesh. Founded in 1916, it is one of the largest residential universities in Asia with over 30,000 students.', NULL, NULL, NULL, NULL),
('ucn-n03', 'u1a00001-0001-0001-0001-000000000003', 'Savitribai Phule Pune University (SPPU) is a public state university in Pune, Maharashtra. Established in 1949, it has over 500 affiliated colleges.', NULL, NULL, NULL, NULL),
('ucn-p01', 'u-p001', 'Lovely Professional University is one of the largest private universities in India with 300,000+ students. Offers 200+ programs.', NULL, NULL, NULL, NULL),
('ucn-p03', 'u-p003', 'Amity University Noida is a private university. Established in 2005, it offers programs across 100+ disciplines.', NULL, NULL, NULL, NULL),
('ucn-u1a00001', 'u1a00001-0001-0001-0001-000000000002', 'Aligarh Muslim University is a reputed institution of higher education in India, offering a wide range of undergraduate, postgraduate, and doctoral programs.', NULL, NULL, NULL, NULL);

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
('uc-02f1f361-c1', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', 'B.Tech Computer Science', 'UG', 4, 400000.00, NULL, 100000.00, 200, NULL, '[\"CSE\"]', '10+2 with PCM', NULL, 1),
('uc-02f1f361-c2', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', 'MBA', 'PG', 2, 300000.00, NULL, 150000.00, 120, NULL, '[\"MBA\"]', 'Bachelor degree', NULL, 1),
('uc-06', 'b2c3d4e5-f6a7-8901-bcde-f12345678901', 'B.A. Economics (Hons)', 'UG', 3, 15000.00, 15000.00, 45000.00, 1500, NULL, '[\"CUET\"]', '10+2 with 60% marks', 500.00, 1),
('uc-07', 'b2c3d4e5-f6a7-8901-bcde-f12345678901', 'B.Com (Hons)', 'UG', 3, 12000.00, 12000.00, 36000.00, 1200, NULL, '[\"CUET\"]', '10+2 with Commerce, 60% marks', 500.00, 1),
('uc-08', 'b2c3d4e5-f6a7-8901-bcde-f12345678901', 'M.Sc Physics', 'PG', 2, 18000.00, 18000.00, 36000.00, 200, NULL, '[\"DU Entrance\"]', 'B.Sc with Physics, 55% marks', 500.00, 0),
('uc-11', 'd4e5f6a7-b8c9-0123-defa-234567890123', 'B.E. Computer Science', 'UG', 4, 220000.00, 110000.00, 880000.00, 300, NULL, '[\"BITSAT\"]', '10+2 with PCM, 75% marks', 2000.00, 1),
('uc-12', 'd4e5f6a7-b8c9-0123-defa-234567890123', 'MBA', 'PG', 2, 300000.00, 150000.00, 600000.00, 150, NULL, '[\"CAT/GMAT\"]', 'Bachelor degree with 60% marks', 2000.00, 1),
('uc-13', 'e5f6a7b8-c9d0-1234-efab-345678901234', 'M.A. International Relations', 'PG', 2, 5000.00, 5000.00, 10000.00, 80, NULL, '[\"JNU Entrance\"]', 'Bachelor degree with 50% marks', 500.00, 1),
('uc-14', 'e5f6a7b8-c9d0-1234-efab-345678901234', 'M.A. Economics', 'PG', 2, 5000.00, 5000.00, 10000.00, 60, NULL, '[\"JNU Entrance\"]', 'Bachelor degree with 50% marks', 500.00, 0),
('uc-15', 'e1f2a3b4-c5d6-7890-efab-901234567890', 'B.Tech Computer Science', 'UG', 4, 150000.00, 75000.00, 600000.00, 120, NULL, '[\"JEE Main\"]', '10+2 with PCM, 75% marks', 2000.00, 1),
('uc-16', 'e1f2a3b4-c5d6-7890-efab-901234567890', 'M.Tech Structural Engineering', 'PG', 2, 150000.00, 75000.00, 300000.00, 40, NULL, '[\"GATE\"]', 'B.Tech with valid GATE score', 2000.00, 0),
('uc-a7b8c9d0-c1', 'a7b8c9d0-e1f2-3456-abcd-567890123456', 'B.Tech Computer Science', 'UG', 4, 400000.00, NULL, 100000.00, 200, NULL, '[\"CSE\"]', '10+2 with PCM', NULL, 1),
('uc-a7b8c9d0-c2', 'a7b8c9d0-e1f2-3456-abcd-567890123456', 'MBA', 'PG', 2, 300000.00, NULL, 150000.00, 120, NULL, '[\"MBA\"]', 'Bachelor degree', NULL, 1),
('uc-b8c9d0e1-c1', 'b8c9d0e1-f2a3-4567-bcde-678901234567', 'B.Tech Computer Science', 'UG', 4, 400000.00, NULL, 100000.00, 200, NULL, '[\"CSE\"]', '10+2 with PCM', NULL, 1),
('uc-b8c9d0e1-c2', 'b8c9d0e1-f2a3-4567-bcde-678901234567', 'MBA', 'PG', 2, 300000.00, NULL, 150000.00, 120, NULL, '[\"MBA\"]', 'Bachelor degree', NULL, 1),
('uc-c9d0e1f2-c1', 'c9d0e1f2-a3b4-5678-cdef-789012345678', 'B.Tech Computer Science', 'UG', 4, 400000.00, NULL, 100000.00, 200, NULL, '[\"CSE\"]', '10+2 with PCM', NULL, 1),
('uc-c9d0e1f2-c2', 'c9d0e1f2-a3b4-5678-cdef-789012345678', 'MBA', 'PG', 2, 300000.00, NULL, 150000.00, 120, NULL, '[\"MBA\"]', 'Bachelor degree', NULL, 1),
('uc-d0e1f2a3-c1', 'd0e1f2a3-b4c5-6789-defa-890123456789', 'B.Tech Computer Science', 'UG', 4, 400000.00, NULL, 100000.00, 200, NULL, '[\"CSE\"]', '10+2 with PCM', NULL, 1),
('uc-d0e1f2a3-c2', 'd0e1f2a3-b4c5-6789-defa-890123456789', 'MBA', 'PG', 2, 300000.00, NULL, 150000.00, 120, NULL, '[\"MBA\"]', 'Bachelor degree', NULL, 1),
('uc-f6a7b8c9-c1', 'f6a7b8c9-d0e1-2345-fabc-456789012345', 'B.Tech Computer Science', 'UG', 4, 400000.00, NULL, 100000.00, 200, NULL, '[\"CSE\"]', '10+2 with PCM', NULL, 1),
('uc-f6a7b8c9-c2', 'f6a7b8c9-d0e1-2345-fabc-456789012345', 'MBA', 'PG', 2, 300000.00, NULL, 150000.00, 120, NULL, '[\"MBA\"]', 'Bachelor degree', NULL, 1),
('uc-u1a00001-c1', 'u1a00001-0001-0001-0001-000000000002', 'B.Tech Computer Science', 'UG', 4, 400000.00, NULL, 100000.00, 200, NULL, '[\"CSE\"]', '10+2 with PCM', NULL, 1),
('uc-u1a00001-c2', 'u1a00001-0001-0001-0001-000000000002', 'MBA', 'PG', 2, 300000.00, NULL, 150000.00, 120, NULL, '[\"MBA\"]', 'Bachelor degree', NULL, 1),
('ucrs-a01', 'u-a001', 'B.S. Research', 'UG', 4, 200000.00, NULL, 50000.00, 120, NULL, '[\"Research\"]', '10+2 PCM, KVPY/IISER', NULL, 0),
('ucrs-a02', 'u-a001', 'M.Tech', 'PG', 2, 200000.00, NULL, 100000.00, 200, NULL, '[\"Engineering\"]', 'GATE score', NULL, 0),
('ucrs-a03', 'u-a002', 'B.Tech CSE', 'UG', 4, 200000.00, NULL, 100000.00, 150, NULL, '[\"CSE\"]', 'JEE Advanced', NULL, 0),
('ucrs-a04', 'u-a003', 'B.Tech CSE', 'UG', 4, 200000.00, NULL, 100000.00, 120, NULL, '[\"CSE\"]', 'JEE Advanced', NULL, 0),
('ucrs-a05', 'u-a004', 'B.Tech CSE', 'UG', 4, 200000.00, NULL, 100000.00, 130, NULL, '[\"CSE\"]', 'JEE Advanced', NULL, 0),
('ucrs-a06', 'u-a005', 'B.Tech CSE', 'UG', 4, 200000.00, NULL, 100000.00, 110, NULL, '[\"CSE\"]', 'JEE Advanced', NULL, 0),
('ucrs-a07', 'u-a008', 'B.Tech CSE', 'UG', 4, 400000.00, NULL, 100000.00, 180, NULL, '[\"CSE\"]', 'JEE Main', NULL, 1),
('ucrs-d01', 'u-d001', 'B.Tech Computer Science', 'UG', 4, 800000.00, NULL, 200000.00, 180, NULL, '[\"CSE\"]', '10+2 with PCM, 60% marks', NULL, 1),
('ucrs-d02', 'u-d001', 'MBA', 'PG', 2, 600000.00, NULL, 300000.00, 120, NULL, '[\"MBA\"]', 'CAT/MAT score', NULL, 1),
('ucrs-d03', 'u-d002', 'BBA', 'UG', 3, 600000.00, NULL, 200000.00, 300, NULL, '[\"BBA\"]', '10+2 with 50% marks + SET', NULL, 1),
('ucrs-d04', 'u-d002', 'MBA', 'PG', 2, 1200000.00, NULL, 600000.00, 180, NULL, '[\"MBA\"]', 'SNAP score', NULL, 1),
('ucrs-d05', 'u-d003', 'B.Tech CSE', 'UG', 4, 800000.00, NULL, 200000.00, 200, NULL, '[\"CSE\"]', '10+2 with PCM + AEEE', NULL, 1),
('ucrs-d06', 'u-d006', 'B.Tech CSE', 'UG', 4, 720000.00, NULL, 180000.00, 1500, NULL, '[\"CSE\"]', '10+2 with PCM + KIITEE', NULL, 1),
('ucrs-d07', 'u-d006', 'MBA', 'PG', 2, 500000.00, NULL, 250000.00, 600, NULL, '[\"MBA\"]', 'CAT/MAT/KIITEE', NULL, 1),
('ucrs-d08', 'u-d009', 'MA Social Work', 'PG', 2, 200000.00, NULL, 100000.00, 60, NULL, '[\"Social Work\"]', 'Bachelor degree + TISSNET', NULL, 0),
('ucrs-g01', 'u-g002', 'B.Tech CSE', 'UG', 4, 150000.00, NULL, 75000.00, 150, NULL, '[\"CSE\"]', 'JEE Main', NULL, 1),
('ucrs-g02', 'u-g003', 'B.Tech CSE', 'UG', 4, 150000.00, NULL, 75000.00, 160, NULL, '[\"CSE\"]', 'JEE Main', NULL, 1),
('ucrs-n01', 'u1a00001-0001-0001-0001-000000000001', 'B.A. LLB', 'UG', 5, 50000.00, NULL, 10000.00, 120, NULL, '[\"Law\"]', '10+2 with 50% marks', NULL, 1),
('ucrs-n02', 'u1a00001-0001-0001-0001-000000000001', 'B.Tech Computer Science', 'UG', 4, 80000.00, NULL, 20000.00, 180, NULL, '[\"CSE\"]', '10+2 with PCM, 60% marks', NULL, 1),
('ucrs-n05', 'u1a00001-0001-0001-0001-000000000003', 'B.Sc Computer Science', 'UG', 3, 30000.00, NULL, 10000.00, 500, NULL, '[\"CS\"]', '10+2 with Science, 55% marks', NULL, 1),
('ucrs-n06', 'u1a00001-0001-0001-0001-000000000003', 'MBA', 'PG', 2, 200000.00, NULL, 100000.00, 300, NULL, '[\"MBA\"]', 'CAT/MAT score', NULL, 1),
('ucrs-p01', 'u-p001', 'B.Tech CSE', 'UG', 4, 600000.00, NULL, 150000.00, 2000, NULL, '[\"CSE\"]', 'LPUNEST', NULL, 1),
('ucrs-p02', 'u-p003', 'B.Tech CSE', 'UG', 4, 800000.00, NULL, 200000.00, 1500, NULL, '[\"CSE\"]', 'Amity Entrance Test', NULL, 1);

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
('ucut-amu-cse-2022-g-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'General', '2022', 10500, 16000, 1, 'AI', 'neutral'),
('ucut-amu-cse-2022-obc-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'OBC', '2022', 16000, 25000, 1, 'AI', 'neutral'),
('ucut-amu-cse-2022-sc-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'SC', '2022', 36000, 58000, 1, 'AI', 'neutral'),
('ucut-amu-cse-2022-st-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'ST', '2022', 52000, 82000, 1, 'AI', 'neutral'),
('ucut-amu-cse-2023-ews-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'EWS', '2023', 14000, 20000, 1, 'AI', 'neutral'),
('ucut-amu-cse-2023-g-f', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'General', '2023', 14000, 20000, 1, 'AI', 'female_only'),
('ucut-amu-cse-2023-g-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'General', '2023', 11000, 17000, 1, 'AI', 'neutral'),
('ucut-amu-cse-2023-g-r2', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'General', '2023', 13000, 19500, 2, 'AI', 'neutral'),
('ucut-amu-cse-2023-obc-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'OBC', '2023', 17000, 26000, 1, 'AI', 'neutral'),
('ucut-amu-cse-2023-sc-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'SC', '2023', 38000, 60000, 1, 'AI', 'neutral'),
('ucut-amu-cse-2023-st-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'ST', '2023', 55000, 88000, 1, 'AI', 'neutral'),
('ucut-amu-cse-2024-ews-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'EWS', '2024', 15000, 22000, 1, 'AI', 'neutral'),
('ucut-amu-cse-2024-ews-r2', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'EWS', '2024', 18000, 25000, 2, 'AI', 'neutral'),
('ucut-amu-cse-2024-g-f', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'General', '2024', 15000, 22000, 1, 'AI', 'female_only'),
('ucut-amu-cse-2024-g-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'General', '2024', 12000, 18500, 1, 'AI', 'neutral'),
('ucut-amu-cse-2024-g-r2', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'General', '2024', 14000, 21000, 2, 'AI', 'neutral'),
('ucut-amu-cse-2024-obc-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'OBC', '2024', 18000, 28000, 1, 'AI', 'neutral'),
('ucut-amu-cse-2024-obc-r2', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'OBC', '2024', 22000, 32000, 2, 'AI', 'neutral'),
('ucut-amu-cse-2024-sc-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'SC', '2024', 40000, 65000, 1, 'AI', 'neutral'),
('ucut-amu-cse-2024-sc-r2', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'SC', '2024', 45000, 72000, 2, 'AI', 'neutral'),
('ucut-amu-cse-2024-st-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'ST', '2024', 60000, 95000, 1, 'AI', 'neutral'),
('ucut-amu-cse-2024-st-r2', 'u1a00001-0001-0001-0001-000000000002', 'ex-jee-main-2026', 'uc-u1a00001-c1', 'ST', '2024', 65000, 105000, 2, 'AI', 'neutral'),
('ucut-amu-mba-2023-g-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-cat-2026', 'uc-u1a00001-c2', 'General', '2023', 72, 88, 1, 'AI', 'neutral'),
('ucut-amu-mba-2023-obc-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-cat-2026', 'uc-u1a00001-c2', 'OBC', '2023', 58, 72, 1, 'AI', 'neutral'),
('ucut-amu-mba-2023-sc-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-cat-2026', 'uc-u1a00001-c2', 'SC', '2023', 42, 58, 1, 'AI', 'neutral'),
('ucut-amu-mba-2024-ews-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-cat-2026', 'uc-u1a00001-c2', 'EWS', '2024', 60, 75, 1, 'AI', 'neutral'),
('ucut-amu-mba-2024-g-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-cat-2026', 'uc-u1a00001-c2', 'General', '2024', 70, 85, 1, 'AI', 'neutral'),
('ucut-amu-mba-2024-g-r2', 'u1a00001-0001-0001-0001-000000000002', 'ex-cat-2026', 'uc-u1a00001-c2', 'General', '2024', 65, 80, 2, 'AI', 'neutral'),
('ucut-amu-mba-2024-obc-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-cat-2026', 'uc-u1a00001-c2', 'OBC', '2024', 55, 70, 1, 'AI', 'neutral'),
('ucut-amu-mba-2024-sc-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-cat-2026', 'uc-u1a00001-c2', 'SC', '2024', 40, 55, 1, 'AI', 'neutral'),
('ucut-amu-mba-2024-st-r1', 'u1a00001-0001-0001-0001-000000000002', 'ex-cat-2026', 'uc-u1a00001-c2', 'ST', '2024', 35, 50, 1, 'AI', 'neutral');

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
('ufa-04', 'd4e5f6a7-b8c9-0123-defa-234567890123', 'Dr. Ramgopal Rao', 'Director', 'Electronics', 'PhD IIT Bombay', 22, NULL, 35, NULL, NULL, NULL),
('ufa-05', 'e5f6a7b8-c9d0-1234-efab-345678901234', 'Dr. Santishree D. Pandit', 'Vice Chancellor', 'Social Sciences', 'PhD JNU', 20, NULL, 25, NULL, NULL, NULL),
('ufa-a01', 'u-a001', 'Prof. Govindan Rangarajan', 'Director', 'Mathematics', 'PhD Princeton', 30, NULL, 100, NULL, NULL, NULL),
('ufa-a02', 'u-a002', 'Prof. Rangan Banerjee', 'Director', 'Engineering', 'PhD IIT Bombay', 28, NULL, 80, NULL, NULL, NULL),
('ufa-a03', 'u-a003', 'Prof. Abhay Karandikar', 'Director', 'Computer Science', 'PhD IIT Bombay', 30, NULL, 75, NULL, NULL, NULL),
('ufa-d01', 'u-d001', 'Dr. Prakash Gopalan', 'Director', 'Engineering', 'PhD IIT Madras', 25, NULL, 50, NULL, NULL, NULL),
('ufa-d02', 'u-d002', 'Dr. Rajani Gupte', 'Vice Chancellor', 'Management', 'PhD Pune University', 20, NULL, 30, NULL, NULL, NULL),
('ufa-n01', 'u1a00001-0001-0001-0001-000000000001', 'Prof. Sudhir Kumar Jain', 'Vice Chancellor', 'Administration', 'PhD IIT Kanpur', 30, NULL, 80, NULL, NULL, NULL),
('ufac-amu-01', 'u1a00001-0001-0001-0001-000000000002', 'Prof. Naima Khatoon', 'Vice Chancellor', 'Administration', 'PhD AMU', 28, NULL, 45, 'https://www.linkedin.com/', 'Educational Administration', 'AMU'),
('ufac-amu-02', 'u1a00001-0001-0001-0001-000000000002', 'Prof. Mohammad Husain', 'Pro-Vice Chancellor', 'Administration', 'PhD AMU', 25, NULL, 38, NULL, 'Biotechnology', 'AMU'),
('ufac-amu-03', 'u1a00001-0001-0001-0001-000000000002', 'Prof. Amanullah Khan', 'Dean, Faculty of Engineering', 'Computer Engineering', 'PhD IIT Kanpur', 22, NULL, 52, NULL, 'Computer Science & Engineering', 'IIT Kanpur'),
('ufac-amu-04', 'u1a00001-0001-0001-0001-000000000002', 'Prof. M. Sarwar Alam', 'Professor', 'Computer Engineering', 'PhD IIT Delhi', 20, NULL, 48, NULL, 'Data Science & AI', 'IIT Delhi'),
('ufac-amu-05', 'u1a00001-0001-0001-0001-000000000002', 'Prof. Shamsul Haque', 'Professor', 'Electrical Engineering', 'PhD IIT BHU', 24, NULL, 40, NULL, 'Power Systems', 'IIT BHU'),
('ufac-amu-06', 'u1a00001-0001-0001-0001-000000000002', 'Prof. Rizwan A. Khan', 'Professor', 'Mechanical Engineering', 'PhD AMU', 21, NULL, 35, NULL, 'Thermal Engineering', 'AMU'),
('ufac-amu-07', 'u1a00001-0001-0001-0001-000000000002', 'Prof. M. Zubair', 'Professor', 'Physics', 'PhD AMU', 23, NULL, 55, NULL, 'Condensed Matter Physics', 'AMU'),
('ufac-amu-08', 'u1a00001-0001-0001-0001-000000000002', 'Prof. Shadab Khan', 'Professor', 'Chemistry', 'PhD AMU', 20, NULL, 42, NULL, 'Organic Chemistry', 'AMU'),
('ufac-amu-09', 'u1a00001-0001-0001-0001-000000000002', 'Prof. Faisal Farooq', 'Associate Professor', 'Computer Engineering', 'PhD Jamia Millia Islamia', 15, NULL, 28, NULL, 'Cloud Computing', 'JMI'),
('ufac-amu-10', 'u1a00001-0001-0001-0001-000000000002', 'Prof. Asad S. Qureshi', 'Professor', 'Civil Engineering', 'PhD IIT Roorkee', 22, NULL, 36, NULL, 'Structural Engineering', 'IIT Roorkee'),
('ufac-amu-11', 'u1a00001-0001-0001-0001-000000000002', 'Prof. Sayyada Arif Ali', 'Professor', 'Electronics Engineering', 'PhD AMU', 19, NULL, 30, NULL, 'VLSI Design', 'AMU'),
('ufac-amu-12', 'u1a00001-0001-0001-0001-000000000002', 'Prof. M. M. S. Ansari', 'Professor', 'Law', 'PhD AMU', 25, NULL, 50, NULL, 'Constitutional Law', 'AMU'),
('ufac-amu-13', 'u1a00001-0001-0001-0001-000000000002', 'Prof. Nadeem A. Khan', 'Professor', 'Business Administration', 'PhD AMU', 18, NULL, 32, NULL, 'Marketing Management', 'AMU'),
('ufac-amu-14', 'u1a00001-0001-0001-0001-000000000002', 'Prof. Mohd. Aslam', 'Associate Professor', 'Mathematics', 'PhD AMU', 14, NULL, 22, NULL, 'Applied Mathematics', 'AMU'),
('ufac-amu-15', 'u1a00001-0001-0001-0001-000000000002', 'Prof. S. M. Haidar Abbas Rizvi', 'Professor', 'Biomedical Engineering', 'PhD AMU', 20, NULL, 38, NULL, 'Biomedical Signal Processing', 'AMU');

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

--
-- Dumping data for table `university_faqs`
--

INSERT INTO `university_faqs` (`id`, `university_id`, `question_text`, `answer_text`, `category`, `sort_order`, `is_active`, `schema_faq_enabled`) VALUES
('faq-amu-01', 'u1a00001-0001-0001-0001-000000000002', 'What is the admission process at AMU?', 'Admission to AMU is through university entrance exams (AMUEEE for B.Tech, AMUMET for MBA). Medical programs use NEET UG scores. Apply online at amu.ac.in.', 'Admission', 1, 1, 0),
('faq-amu-02', 'u1a00001-0001-0001-0001-000000000002', 'What is the fee structure at AMU?', 'AMU is a central government university with very affordable fees. B.Tech fee is approximately ₹15,000-20,000 per year. MBA fee is around ₹50,000 per year.', 'Fees', 2, 1, 0),
('faq-amu-03', 'u1a00001-0001-0001-0001-000000000002', 'Does AMU provide hostel facility?', 'Yes, AMU has separate hostels for boys and girls with a combined capacity of 12,000+ students. Hostel fee is approximately ₹15,000 per year including mess.', 'Infrastructure', 3, 1, 0),
('faq-amu-04', 'u1a00001-0001-0001-0001-000000000002', 'What are the placement statistics at AMU?', 'AMU has a decent placement record with average package around ₹5 LPA and highest package reaching ₹18 LPA. Top recruiters include TCS, Infosys, and Wipro.', 'Placements', 4, 1, 0),
('faq-amu-05', 'u1a00001-0001-0001-0001-000000000002', 'Is AMU a central university?', 'Yes, Aligarh Muslim University is a central university established by an Act of Parliament in 1920. It is funded by the Government of India.', 'General', 5, 1, 0),
('faq-amu-06', 'u1a00001-0001-0001-0001-000000000002', 'What is the NAAC grade of AMU?', 'AMU has been accredited with A++ grade by NAAC (National Assessment and Accreditation Council).', 'General', 6, 1, 0),
('faq-amu-07', 'u1a00001-0001-0001-0001-000000000002', 'Does AMU offer lateral entry for B.Tech?', 'Yes, AMU offers lateral entry admission to B.Tech programs for diploma holders. Candidates must have a 3-year diploma with minimum 60% marks.', 'Admission', 7, 1, 0),
('faq-amu-08', 'u1a00001-0001-0001-0001-000000000002', 'What scholarships are available at AMU?', 'AMU offers merit scholarships, need-based financial aid, SC/ST/OBC scholarships, sports scholarships, and minority scholarships. Details available on the university website.', 'Scholarships', 8, 1, 0),
('faq-amu-09', 'u1a00001-0001-0001-0001-000000000002', 'How is the campus life at AMU?', 'AMU has a vibrant campus life with 14 halls of residence, sports facilities, cultural clubs, libraries, and a 1200-acre campus. The university hosts annual cultural festival Tazeen.', 'General', 9, 1, 0),
('faq-amu-10', 'u1a00001-0001-0001-0001-000000000002', 'What are the popular courses at AMU?', 'Popular courses include B.Tech CSE, MBA, B.A. LLB, MBBS, B.Sc, M.Sc, and various diploma programs. The university offers 300+ courses across multiple disciplines.', 'General', 10, 1, 0),
('uf-05', 'b2c3d4e5-f6a7-8901-bcde-f12345678901', 'How to get admission in Delhi University?', 'Admission to Delhi University undergraduate programs is through CUET (Common University Entrance Test). Students must register on DU portal and appear for CUET.', 'Admission', 1, 1, 0),
('uf-06', 'e1f2a3b4-c5d6-7890-efab-901234567890', 'What is the fee structure for NIT Trichy?', 'Annual fee at NIT Trichy is approximately INR 1.5 Lakhs for tuition plus hostel charges. Total 4-year B.Tech cost is around INR 6-8 Lakhs.', 'Fees', 1, 1, 0),
('uf-a01', 'u-a001', 'How to get into IISc Bangalore?', 'Admission through KVPY, JEE Main/Advanced, or NEET.', 'Admission', 1, 1, 0),
('uf-a02', 'u-a002', 'What are the placements at IIT Delhi?', '98%+ placement rate with average package around INR 22 LPA.', 'Placements', 1, 1, 0),
('uf-d01', 'u-d001', 'What is the admission process at Tharper?', 'Admission through JEE Main scores followed by counselling. MBA requires CAT/MAT.', 'Admission', 1, 1, 0),
('uf-d02', 'u-d002', 'How to get into Symbiosis MBA?', 'Admission through SNAP exam followed by GE-PI-WAT process.', 'Admission', 1, 1, 0),
('uf-d03', 'u-d006', 'What is the fee structure at KIIT?', 'B.Tech fee is approximately INR 1.8 Lakhs per year.', 'Fees', 1, 1, 0),
('uf-g01', 'u-g002', 'What is the fee for NIT Warangal?', 'Annual fee approximately INR 1.5 Lakhs for tuition.', 'Fees', 1, 1, 0),
('uf-n03', 'u1a00001-0001-0001-0001-000000000001', 'What courses does BHU offer?', 'Wide range of UG, PG, and PhD programs across arts, science, engineering, medicine, law.', 'General', 1, 1, 0);

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
('hostel-amu-001', 'u1a00001-0001-0001-0001-000000000002', 1, 'both', 12000, 15000.00, 1, 'both', 0, '[\"Single occupancy\",\"Double occupancy\",\"Triple occupancy\"]', '[\"24\\/7 security\",\"CCTV surveillance\",\"Biometric entry\"]', 1);

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
('infra-amu-001', 'u1a00001-0001-0001-0001-000000000002', 1, 1200000, '[\"Cricket ground\",\"Football field\",\"Hockey field\",\"Tennis courts\",\"Badminton courts\",\"Swimming pool\",\"Gymnasium\",\"Athletics track\"]', '[\"Physics labs\",\"Chemistry labs\",\"Computer science labs\",\"Engineering labs\",\"Language labs\",\"Biotechnology labs\",\"Media labs\"]', 1, 2500, 1, 1, 100, 1, 1, 0, 1);

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
('gal-amu-01', 'u1a00001-0001-0001-0001-000000000002', NULL, NULL, NULL, NULL, NULL, 'AMU Main Building', 1, NULL, NULL, NULL, 0, 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80'),
('gal-amu-02', 'u1a00001-0001-0001-0001-000000000002', NULL, NULL, NULL, NULL, NULL, 'AMU Campus View', 2, NULL, NULL, NULL, 0, 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=800&q=80'),
('gal-amu-03', 'u1a00001-0001-0001-0001-000000000002', NULL, NULL, NULL, NULL, NULL, 'AMU Library', 3, NULL, NULL, NULL, 0, 'https://images.unsplash.com/photo-1523050854058-8df90110c476?w=800&q=80'),
('gal-amu-04', 'u1a00001-0001-0001-0001-000000000002', NULL, NULL, NULL, NULL, NULL, 'AMU Classroom', 4, NULL, NULL, NULL, 0, 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=800&q=80'),
('gal-amu-05', 'u1a00001-0001-0001-0001-000000000002', NULL, NULL, NULL, NULL, NULL, 'AMU Grounds', 5, NULL, NULL, NULL, 0, 'https://images.unsplash.com/photo-1498243691581-b145c3f54a5a?w=800&q=80'),
('gal-amu-06', 'u1a00001-0001-0001-0001-000000000002', NULL, NULL, NULL, NULL, NULL, 'AMU Auditorium', 6, NULL, NULL, NULL, 0, 'https://images.unsplash.com/photo-1471295253337-3ceaaedca402?w=800&q=80');

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

--
-- Dumping data for table `university_placements`
--

INSERT INTO `university_placements` (`id`, `university_id`, `placement_year`, `avg_package_lpa`, `highest_package_lpa`, `median_package_lpa`, `placement_percentage`, `students_placed`, `international_placements`, `top_recruiters`, `sector_wise_json`, `placement_report_pdf`) VALUES
('up-02f1f361-p1', '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', '2024', 35.00, 120.00, 30.00, 100, 400, NULL, '[\"McKinsey\",\"BCG\",\"Bain\",\"Deloitte\",\"Goldman Sachs\",\"Google\"]', NULL, NULL),
('up-06', 'd4e5f6a7-b8c9-0123-defa-234567890123', '2024', 18.00, 60.00, 14.00, 92, 900, NULL, '[\"Microsoft\",\"Amazon\",\"Goldman Sachs\",\"Deloitte\"]', NULL, NULL),
('up-07', 'e1f2a3b4-c5d6-7890-efab-901234567890', '2024', 10.00, 45.00, 8.00, 90, 600, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Cognizant\",\"Amazon\"]', NULL, NULL),
('up-08', 'f6a7b8c9-d0e1-2345-fabc-456789012345', '2024', 6.00, 25.00, 4.50, 80, 2500, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"HCL\",\"Cognizant\"]', NULL, NULL),
('up-a01', 'u-a001', '2024', 15.00, 80.00, 12.00, 95, 100, NULL, '[\"Google\",\"Microsoft\",\"Research Labs\",\"ISRO\"]', NULL, NULL),
('up-a02', 'u-a002', '2024', 22.00, 180.00, 18.00, 98, 150, NULL, '[\"Google\",\"Microsoft\",\"Amazon\",\"Apple\"]', NULL, NULL),
('up-a03', 'u-a003', '2024', 20.00, 150.00, 16.00, 97, 120, NULL, '[\"Google\",\"Microsoft\",\"Amazon\",\"Goldman Sachs\"]', NULL, NULL),
('up-a04', 'u-a004', '2024', 21.00, 160.00, 17.00, 97, 130, NULL, '[\"Google\",\"Microsoft\",\"Amazon\",\"Samsung\"]', NULL, NULL),
('up-a05', 'u-a005', '2024', 18.00, 120.00, 14.00, 96, 110, NULL, '[\"Google\",\"Microsoft\",\"Infosys\",\"TCS\"]', NULL, NULL),
('up-a7b8c9d0-p1', 'a7b8c9d0-e1f2-3456-abcd-567890123456', '2024', 6.00, 20.00, 4.50, 80, 1000, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Cognizant\"]', NULL, NULL),
('up-ashoka', 'u-p006', '2024', 7.00, 25.00, 5.00, 85, 200, NULL, '[\"Consulting\",\"Research\",\"Startups\"]', NULL, NULL),
('up-b2c3d4e5-p1', 'b2c3d4e5-f6a7-8901-bcde-f12345678901', '2024', 6.00, 20.00, 4.50, 70, 3000, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Deloitte\"]', NULL, NULL),
('up-b8c9d0e1-p1', 'b8c9d0e1-f2a3-4567-bcde-678901234567', '2024', 6.00, 30.00, 4.50, 85, 3000, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Amazon\"]', NULL, NULL),
('up-bharathidasan', 'u-d004', '2024', 4.50, 18.00, 3.00, 70, 800, NULL, '[\"TCS\",\"Infosys\",\"Wipro\"]', NULL, NULL),
('up-bit-mesra', 'u-a008', '2024', 8.00, 30.00, 6.00, 85, 400, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Amazon\"]', NULL, NULL),
('up-bvp', 'u-p008', '2024', 5.00, 20.00, 3.50, 78, 1500, NULL, '[\"TCS\",\"Infosys\",\"Wipro\"]', NULL, NULL),
('up-c9d0e1f2-p1', 'c9d0e1f2-a3b4-5678-cdef-789012345678', '2024', 5.50, 28.00, 4.00, 82, 3500, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Cognizant\"]', NULL, NULL),
('up-ccu', 'u1a00001-0001-0001-0001-000000000005', '2024', 5.00, 18.00, 3.50, 70, 2000, NULL, '[\"TCS\",\"Infosys\",\"Wipro\"]', NULL, NULL),
('up-cuchd', 'u-p002', '2024', 5.50, 35.00, 4.00, 88, 15000, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Amazon\"]', NULL, NULL),
('up-d01', 'u-d001', '2024', 8.00, 40.00, 6.00, 88, 600, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Amazon\"]', NULL, NULL),
('up-d02', 'u-d002', '2024', 7.50, 35.00, 5.50, 85, 800, NULL, '[\"TCS\",\"Infosys\",\"Deloitte\",\"KPMG\"]', NULL, NULL),
('up-d03', 'u-d003', '2024', 7.00, 35.00, 5.00, 82, 700, NULL, '[\"TCS\",\"Infosys\",\"Amazon\",\"Microsoft\"]', NULL, NULL),
('up-d04', 'u-d006', '2024', 6.50, 30.00, 4.50, 85, 5000, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Cognizant\"]', NULL, NULL),
('up-d05', 'u-d009', '2024', 6.00, 25.00, 4.00, 90, 200, NULL, '[\"TCS\",\"Social Sector\",\"Government\",\"NGOs\"]', NULL, NULL),
('up-d0e1f2a3-p1', 'd0e1f2a3-b4c5-6789-defa-890123456789', '2024', 7.00, 35.00, 5.00, 85, 800, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Amazon\"]', NULL, NULL),
('up-dyp', 'u-d008', '2024', 5.00, 20.00, 3.50, 75, 1000, NULL, '[\"TCS\",\"Infosys\",\"Wipro\"]', NULL, NULL),
('up-e5f6a7b8-p1', 'e5f6a7b8-c9d0-1234-efab-345678901234', '2024', 5.00, 15.00, 3.50, 65, 800, NULL, '[\"TCS\",\"Infosys\",\"Research\",\"Government\"]', NULL, NULL),
('up-g01', 'u-g002', '2024', 10.00, 45.00, 8.00, 90, 600, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Cognizant\",\"Amazon\"]', NULL, NULL),
('up-g02', 'u-g003', '2024', 10.00, 45.00, 8.00, 90, 600, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Cognizant\",\"Amazon\"]', NULL, NULL),
('up-gfsu', 'u-d010', '2024', 5.00, 15.00, 3.50, 75, 200, NULL, '[\"Government\",\"Police\",\"CBI\"]', NULL, NULL),
('up-iitg', 'u-a006', '2024', 16.00, 80.00, 12.00, 95, 100, NULL, '[\"Google\",\"Microsoft\",\"Amazon\",\"Flipkart\"]', NULL, NULL),
('up-iith', 'u-a007', '2024', 16.00, 80.00, 12.00, 95, 100, NULL, '[\"Google\",\"Microsoft\",\"Amazon\",\"TCS\"]', NULL, NULL),
('up-iitrpr', 'u-g001', '2024', 14.00, 60.00, 10.00, 92, 300, NULL, '[\"Google\",\"Microsoft\",\"Amazon\"]', NULL, NULL),
('up-kiit', 'u-p007', '2024', 6.50, 30.00, 4.50, 85, 5000, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Cognizant\"]', NULL, NULL),
('up-mnit-jaipur', 'u-g006', '2024', 8.00, 35.00, 6.00, 88, 500, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Amazon\"]', NULL, NULL),
('up-mnnit', 'u-g004', '2024', 8.00, 35.00, 6.00, 88, 500, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Amazon\"]', NULL, NULL),
('up-mu', 'u1a00001-0001-0001-0001-000000000004', '2024', 6.00, 22.00, 4.50, 75, 3000, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"HCL\"]', NULL, NULL),
('up-n01', 'u1a00001-0001-0001-0001-000000000001', '2024', 6.00, 25.00, 4.50, 75, 2000, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"HCL\"]', NULL, NULL),
('up-n03', 'u1a00001-0001-0001-0001-000000000003', '2024', 5.00, 20.00, 3.50, 72, 5000, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Cognizant\"]', NULL, NULL),
('up-nitc', 'u-g005', '2024', 8.00, 35.00, 6.00, 88, 500, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Amazon\"]', NULL, NULL),
('up-p01', 'u-p001', '2024', 5.50, 30.00, 4.00, 90, 20000, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Cognizant\",\"HCL\"]', NULL, NULL),
('up-p02', 'u-p003', '2024', 6.50, 40.00, 5.00, 85, 10000, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Amazon\"]', NULL, NULL),
('up-parul', 'u-p005', '2024', 5.00, 22.00, 3.50, 82, 3000, NULL, '[\"TCS\",\"Infosys\",\"Wipro\"]', NULL, NULL),
('up-psg', 'u-a009', '2024', 6.00, 25.00, 4.50, 85, 400, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"HCL\"]', NULL, NULL),
('up-sharda', 'u-p004', '2024', 5.00, 25.00, 3.50, 80, 5000, NULL, '[\"TCS\",\"Infosys\",\"Wipro\"]', NULL, NULL),
('up-shivnadar', 'u-d007', '2024', 7.00, 35.00, 5.00, 85, 300, NULL, '[\"TCS\",\"Infosys\",\"Amazon\",\"Microsoft\"]', NULL, NULL),
('up-soa', 'u-d005', '2024', 5.00, 22.00, 3.50, 78, 1500, NULL, '[\"TCS\",\"Infosys\",\"Wipro\"]', NULL, NULL),
('up-svnit', 'u-g008', '2024', 8.00, 35.00, 6.00, 88, 500, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Amazon\"]', NULL, NULL),
('up-tce', 'u-a010', '2024', 5.00, 22.00, 3.50, 80, 300, NULL, '[\"TCS\",\"Infosys\",\"Wipro\"]', NULL, NULL),
('up-u1a00001-p1', 'u1a00001-0001-0001-0001-000000000002', '2024', 5.00, 18.00, 3.50, 70, 1500, NULL, '[\"TCS\",\"Infosys\",\"Wipro\"]', NULL, NULL),
('up-vnit', 'u-g007', '2024', 8.00, 35.00, 6.00, 88, 500, NULL, '[\"TCS\",\"Infosys\",\"Wipro\",\"Amazon\"]', NULL, NULL);

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
('sch-amu-01', 'u1a00001-0001-0001-0001-000000000002', 'AMU Merit Scholarship', 'merit', 50000.00, 'fixed', 'Top 10% students in each department based on academic performance', 1, NULL),
('sch-amu-02', 'u1a00001-0001-0001-0001-000000000002', 'AMU Need-Based Financial Aid', 'need', 100.00, 'full_tuition', 'Students from economically weaker sections with family income below ₹3 LPA', 1, NULL),
('sch-amu-03', 'u1a00001-0001-0001-0001-000000000002', 'AMU SC/ST Scholarship', 'merit', 75000.00, 'fixed', 'SC/ST category students with minimum 60% marks in previous examination', 1, NULL),
('sch-amu-04', 'u1a00001-0001-0001-0001-000000000002', 'AMU OBC Scholarship', 'merit', 40000.00, 'fixed', 'OBC category students with minimum 65% marks in previous examination', 1, NULL),
('sch-amu-05', 'u1a00001-0001-0001-0001-000000000002', 'AMU Sports Scholarship', 'sports', 60000.00, 'fixed', 'Students who have represented state or country in sports competitions', 1, NULL),
('sch-amu-06', 'u1a00001-0001-0001-0001-000000000002', 'AMU Minority Scholarship', 'minority', 30000.00, 'fixed', 'Students from minority communities with family income below ₹6 LPA', 1, NULL),
('sch-amu-07', 'u1a00001-0001-0001-0001-000000000002', 'AMU Research Fellowship', '', 25000.00, 'fixed', 'PhD and MPhil students engaged in approved research projects', 1, NULL),
('usch-03', 'd4e5f6a7-b8c9-0123-defa-234567890123', 'BITS Pilani Merit Scholarship', 'merit', 100.00, 'percentage', 'Based on BITSAT score', 1, NULL),
('usch-04', 'e5f6a7b8-c9d0-1234-efab-345678901234', 'JNU Fee Waiver', 'need', 100.00, 'full_tuition', 'For students from economically weaker sections', 0, NULL);

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
('64e20c70-d8d7-402f-a700-53c759a659d4', 'Madhav Arora', 'madhavarora132005@gmail.com', '+919877275894', '$2y$10$6EPeZE02GC57pypaUAp.LezNs62SufTYnB4QZgKw41JOpG4Hg6Q8K', 'phone_otp', 'active', NULL, 0, NULL, 1, 1, 0, '2026-07-02 09:32:02', '::1', 14, '2026-06-17 11:16:21', '2026-07-02 09:32:02'),
('8b0478e7-602f-11f1-9ea0-a0510b1a7448', 'Madhav Arora', 'admi@example.com', NULL, '$2y$10$.P/prjvLjX3zn27/DW1j..roInHmRD3LUgJNgGpMyyhO9cj5/AJDa', 'email', 'suspended', '169481d3-602f-11f1-9ea0-a0510b1a7448', 1, NULL, 0, 0, 0, NULL, NULL, 0, '2026-06-04 16:07:43', '2026-06-04 16:07:58'),
('a85f4bf4-5c3b-11f1-a48e-c8f7507a8de6', 'Super Admin', 'admin@example.com', NULL, '$2y$10$RTMB7txQfeY7yMgStWBiLuNJUymVTRIXn45SAYuLYH.mXiKmWVLaG', 'email', 'active', 'a84ab069-5c3b-11f1-a48e-c8f7507a8de6', 1, NULL, 0, 0, 0, NULL, NULL, 0, '2026-05-30 15:24:17', '2026-06-04 15:57:46'),
('user-1234-uuid', 'Guest Student', 'guest@admissionseason.local', NULL, '$2y$10$8WKAiqLzxwzsOoXiKpN9ieswTgZ53/166m4trxDI6EJpOhMkjokTS', 'email', 'active', NULL, 0, NULL, 0, 0, 0, NULL, NULL, 0, '2026-06-22 06:12:29', '2026-06-22 06:12:29'),
('usr00001-0000-0000-0000-000000000001', 'Rahul Sharma', 'rahul.sharma@example.com', '9876543210', '.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'email', 'active', NULL, 0, NULL, 1, 1, 0, NULL, NULL, 0, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('usr00001-0000-0000-0000-000000000002', 'Priya Patel', 'priya.patel@example.com', '9876543211', '.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'email', 'active', NULL, 0, NULL, 1, 1, 0, NULL, NULL, 0, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('usr00001-0000-0000-0000-000000000003', 'Amit Kumar', 'amit.kumar@example.com', '9876543212', '.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'email', 'active', NULL, 0, NULL, 1, 1, 0, NULL, NULL, 0, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('usr00001-0000-0000-0000-000000000004', 'Sneha Reddy', 'sneha.reddy@example.com', '9876543213', '.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'email', 'active', NULL, 0, NULL, 1, 1, 0, NULL, NULL, 0, '2026-06-19 06:04:41', '2026-06-19 06:04:41'),
('usr00001-0000-0000-0000-000000000005', 'Vikram Singh', 'vikram.singh@example.com', '9876543214', '.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'email', 'active', NULL, 0, NULL, 1, 1, 0, NULL, NULL, 0, '2026-06-19 06:04:41', '2026-06-19 06:04:41');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `trg_users_after_delete` AFTER DELETE ON `users` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NULL, 'delete', 'users', OLD.id,
        JSON_OBJECT('full_name', OLD.full_name, 'email', OLD.email, 'status', OLD.status),
        NULL, NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_users_after_insert` AFTER INSERT ON `users` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
    VALUES (UUID(), NEW.id, 'create', 'users', NEW.id, NULL,
        JSON_OBJECT('full_name', NEW.full_name, 'email', NEW.email, 'status', NEW.status, 'role_id', NEW.role_id),
        NULL, NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_users_after_update` AFTER UPDATE ON `users` FOR EACH ROW BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
        VALUES (UUID(), NEW.id, 'status_change', 'users', NEW.id,
            JSON_OBJECT('status', OLD.status),
            JSON_OBJECT('status', NEW.status),
            NULL, NOW());
    ELSEIF OLD.full_name != NEW.full_name OR OLD.email != NEW.email OR OLD.role_id != NEW.role_id THEN
        INSERT INTO audit_logs (id, user_id, audit_action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
        VALUES (UUID(), NEW.id, 'update', 'users', NEW.id,
            JSON_OBJECT('full_name', OLD.full_name, 'email', OLD.email, 'role_id', OLD.role_id),
            JSON_OBJECT('full_name', NEW.full_name, 'email', NEW.email, 'role_id', NEW.role_id),
            NULL, NOW());
    END IF;
END
$$
DELIMITER ;

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
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL,
  `user_id` char(36) NOT NULL,
  `pref_key` varchar(50) NOT NULL,
  `pref_value` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_preferences`
--

INSERT INTO `user_preferences` (`id`, `user_id`, `pref_key`, `pref_value`, `created_at`, `updated_at`) VALUES
(1, '64e20c70-d8d7-402f-a700-53c759a659d4', 'open_to_private', 'yes', '2026-06-20 06:30:40', '2026-06-20 06:30:40'),
(2, '64e20c70-d8d7-402f-a700-53c759a659d4', 'recommendation_feedback', 'helpful', '2026-06-20 06:30:42', '2026-06-20 06:30:42');

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
(2, 'USA', 'F-1 Student Visa', 60, 185.00, '[\"Passport\",\"I-20\",\"DS-160\",\"SEVIS fee\",\"Financial proof\",\"Academic transcripts\",\"English proficiency test\"]', 'Apply early, prepare for visa interview, show strong ties to home country.', 12, 25000.00, 1, 20, '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(3, 'UK', 'Student Visa (Tier 4)', 15, 490.00, '[\"CAS letter\",\"Passport\",\"TB test\",\"Financial proof\",\"ATAS certificate\",\"English proficiency\"]', 'Apply as soon as CAS is received, ensure financial requirements are met.', 6, 13347.00, 0, 20, '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(4, 'Canada', 'Study Permit', 45, 150.00, '[\"LOA from DLI\",\"Passport\",\"Financial proof\",\"Medical exam\",\"Police clearance\",\"Digital photograph\"]', 'Apply online, provide strong financial evidence, write a convincing SOP.', 6, 20635.00, 0, 20, '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(5, 'Australia', 'Student Visa (Subclass 500)', 42, 710.00, '[\"CoE\",\"OSHC\",\"Passport\",\"GTE statement\",\"Financial proof\",\"English proficiency\",\"Health exam\"]', 'Demonstrate GTE requirement, show sufficient funds, maintain health insurance.', 6, 24505.00, 0, 48, '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(6, 'Germany', 'Student Visa', 25, 75.00, '[\"University admission\",\"Blocked account\",\"Passport\",\"Health insurance\",\"APS certificate\"]', 'Open blocked account early, get APS certificate if from India.', 18, 11208.00, 0, 20, '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(7, 'Singapore', 'Student Pass', 15, 30.00, '[\"IPA letter\",\"Passport\",\"Financial proof\",\"Medical examination\",\"Academic transcripts\"]', 'Apply online through SOLAR, ensure university submits application on time.', 0, 0.00, 0, 16, '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(8, 'Ireland', 'Student Visa (Stamp 2)', 40, 80.00, '[\"Letter of acceptance\",\"Financial proof\",\"Medical insurance\",\"English proficiency\",\"Passport\"]', 'Show 7000 EUR plus 10000 EUR per year of study.', 6, 11500.00, 0, 20, '2026-06-21 08:42:27', '2026-06-21 08:42:27'),
(9, 'New Zealand', 'Student Visa', 30, 330.00, '[\"Offer of Place\",\"Passport\",\"Financial proof\",\"Medical certificate\",\"Police clearance\"]', 'Show NZD 20000 per year for living costs.', 12, 15000.00, 0, 20, '2026-06-21 08:42:27', '2026-06-21 08:42:27');

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
-- Indexes for table `article_views`
--
ALTER TABLE `article_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_article` (`article_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_viewed` (`viewed_at`);

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
-- Indexes for table `careers`
--
ALTER TABLE `careers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

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
  ADD KEY `duplicate_of` (`duplicate_of`),
  ADD KEY `idx_colleges_search` (`name`,`status`,`college_type`,`publish_status`,`is_verified`,`city_id`,`state_id`);

--
-- Indexes for table `college_accounts`
--
ALTER TABLE `college_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `status` (`status`);

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
-- Indexes for table `college_qna`
--
ALTER TABLE `college_qna`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cq_college` (`college_id`);

--
-- Indexes for table `college_scholarships`
--
ALTER TABLE `college_scholarships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `college_submissions`
--
ALTER TABLE `college_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `status` (`status`),
  ADD KEY `submission_type` (`submission_type`);

--
-- Indexes for table `college_updates`
--
ALTER TABLE `college_updates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_update_slug` (`slug`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_answer` (`answer_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `comment_votes`
--
ALTER TABLE `comment_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_comment_user` (`comment_id`,`user_id`),
  ADD KEY `idx_comment` (`comment_id`);

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
-- Indexes for table `follows`
--
ALTER TABLE `follows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_follow` (`user_id`,`followable_type`,`followable_id`),
  ADD KEY `idx_followable` (`followable_type`,`followable_id`),
  ADD KEY `idx_user` (`user_id`);

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
-- Indexes for table `mega_menu`
--
ALTER TABLE `mega_menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_menu_key` (`menu_key`),
  ADD KEY `idx_sort` (`sort_order`);

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
-- Indexes for table `nps_feedback`
--
ALTER TABLE `nps_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_score` (`score`),
  ADD KEY `idx_created` (`created_at`);

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
  ADD UNIQUE KEY `idx_question_slug` (`slug`),
  ADD KEY `asked_by` (`asked_by`);

--
-- Indexes for table `question_views`
--
ALTER TABLE `question_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_question` (`question_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_viewed` (`viewed_at`);

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
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reportable` (`reportable_type`,`reportable_id`),
  ADD KEY `idx_status` (`status`);

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
-- Indexes for table `saved_colleges`
--
ALTER TABLE `saved_colleges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_college` (`user_id`,`college_id`),
  ADD KEY `idx_university_id` (`university_id`);

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
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_pref` (`user_id`,`pref_key`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

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
-- AUTO_INCREMENT for table `article_views`
--
ALTER TABLE `article_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=734;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `foreign_universities`
--
ALTER TABLE `foreign_universities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

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
-- AUTO_INCREMENT for table `nps_feedback`
--
ALTER TABLE `nps_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `question_views`
--
ALTER TABLE `question_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=327;

--
-- AUTO_INCREMENT for table `rankings`
--
ALTER TABLE `rankings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

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
-- AUTO_INCREMENT for table `saved_colleges`
--
ALTER TABLE `saved_colleges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

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
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `visa_guides`
--
ALTER TABLE `visa_guides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
-- Constraints for table `college_qna`
--
ALTER TABLE `college_qna`
  ADD CONSTRAINT `college_qna_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

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
--
-- Database: `admissionseason`
--
CREATE DATABASE IF NOT EXISTS `admissionseason` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `admissionseason`;
--
-- Database: `admission_enterprise`
--
CREATE DATABASE IF NOT EXISTS `admission_enterprise` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `admission_enterprise`;

-- --------------------------------------------------------

--
-- Table structure for table `admin_roles`
--

CREATE TABLE `admin_roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `permissions_json` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_roles`
--

INSERT INTO `admin_roles` (`id`, `role_name`, `permissions_json`) VALUES
(1, 'Super Admin', '[\"all\"]'),
(2, 'Content Manager', '[\"articles\",\"seo\",\"traffic\",\"moderation\"]'),
(3, 'Moderator', '[\"reviews\",\"reports\",\"qna\",\"spam\"]'),
(4, 'Finance Team', '[\"revenue\",\"subscriptions\",\"payments\",\"invoices\"]'),
(5, 'Counsellor', '[\"assigned_students\",\"calls\",\"lead_pipeline\",\"conversion\"]');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `role_id` int(11) NOT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `role_id`, `last_login`) VALUES
(1, 'admin_super', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE `alerts` (
  `id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `severity` varchar(20) NOT NULL,
  `message` varchar(255) NOT NULL,
  `status` varchar(20) DEFAULT 'New',
  `assigned_to` int(11) DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alerts`
--

INSERT INTO `alerts` (`id`, `category`, `severity`, `message`, `status`, `assigned_to`, `resolved_by`, `created_at`, `resolved_at`) VALUES
(1, 'Infrastructure', 'Critical', '3 Failed Lead Deliveries (API Timeout)', 'New', NULL, NULL, '2026-05-25 20:17:10', NULL),
(2, 'Moderation', 'Warning', 'Pending Moderation Spike (23 Reviews)', 'New', NULL, NULL, '2026-05-25 20:17:10', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `analytics_snapshots`
--

CREATE TABLE `analytics_snapshots` (
  `id` int(11) NOT NULL,
  `metric_type` varchar(50) NOT NULL,
  `metric_key` varchar(50) NOT NULL,
  `metric_value` float NOT NULL,
  `date_recorded` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `analytics_snapshots`
--

INSERT INTO `analytics_snapshots` (`id`, `metric_type`, `metric_key`, `metric_value`, `date_recorded`) VALUES
(1, 'Traffic', 'page_views', 22440, '2026-05-19'),
(2, 'Traffic', 'unique_users', 7529, '2026-05-19'),
(3, 'Traffic', 'page_views', 19375, '2026-05-20'),
(4, 'Traffic', 'unique_users', 9435, '2026-05-20'),
(5, 'Traffic', 'page_views', 22114, '2026-05-21'),
(6, 'Traffic', 'unique_users', 11496, '2026-05-21'),
(7, 'Traffic', 'page_views', 24160, '2026-05-22'),
(8, 'Traffic', 'unique_users', 12209, '2026-05-22'),
(9, 'Traffic', 'page_views', 11104, '2026-05-23'),
(10, 'Traffic', 'unique_users', 9813, '2026-05-23'),
(11, 'Traffic', 'page_views', 12025, '2026-05-24'),
(12, 'Traffic', 'unique_users', 9128, '2026-05-24'),
(13, 'Traffic', 'page_views', 24440, '2026-05-25'),
(14, 'Traffic', 'unique_users', 10763, '2026-05-25');

-- --------------------------------------------------------

--
-- Table structure for table `blacklisted_entities`
--

CREATE TABLE `blacklisted_entities` (
  `id` int(11) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_value` varchar(255) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `colleges`
--

CREATE TABLE `colleges` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(100) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `college_type` enum('Engineering','Medical','Management','Law','Arts','Commerce','Polytechnic','University') NOT NULL,
  `ownership_type` enum('Government','Private','Semi-Government','Deemed','Autonomous') NOT NULL,
  `established_year` year(4) DEFAULT NULL,
  `affiliated_university` varchar(255) DEFAULT NULL,
  `accreditation` varchar(255) DEFAULT NULL,
  `approval_bodies` varchar(255) DEFAULT NULL,
  `campus_size` varchar(100) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `banner_image` varchar(255) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `google_maps_url` text DEFAULT NULL,
  `nearby_landmark` varchar(255) DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `full_description` text DEFAULT NULL,
  `why_choose_this_college` text DEFAULT NULL,
  `highlights` text DEFAULT NULL,
  `history` text DEFAULT NULL,
  `mission_vision` text DEFAULT NULL,
  `admission_process` text DEFAULT NULL,
  `application_start_date` date DEFAULT NULL,
  `application_end_date` date DEFAULT NULL,
  `counselling_info` text DEFAULT NULL,
  `required_documents` text DEFAULT NULL,
  `reservation_policy` text DEFAULT NULL,
  `how_to_apply` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `has_library` tinyint(1) DEFAULT 0,
  `has_labs` tinyint(1) DEFAULT 0,
  `has_wifi` tinyint(1) DEFAULT 0,
  `has_auditorium` tinyint(1) DEFAULT 0,
  `has_gym` tinyint(1) DEFAULT 0,
  `has_sports` tinyint(1) DEFAULT 0,
  `has_medical` tinyint(1) DEFAULT 0,
  `has_transport` tinyint(1) DEFAULT 0,
  `has_cafeteria` tinyint(1) DEFAULT 0,
  `has_bank` tinyint(1) DEFAULT 0,
  `has_atm` tinyint(1) DEFAULT 0,
  `infrastructure_description` text DEFAULT NULL,
  `boys_hostel_available` tinyint(1) DEFAULT 0,
  `girls_hostel_available` tinyint(1) DEFAULT 0,
  `hostel_fees` varchar(100) DEFAULT NULL,
  `room_types` varchar(255) DEFAULT NULL,
  `mess_charges` varchar(100) DEFAULT NULL,
  `hostel_rules` text DEFAULT NULL,
  `hostel_images` text DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `canonical_url` varchar(255) DEFAULT NULL,
  `schema_json` text DEFAULT NULL,
  `og_image` varchar(255) DEFAULT NULL,
  `meta_robots` varchar(50) DEFAULT 'index, follow',
  `page_views` int(11) DEFAULT 0,
  `lead_count` int(11) DEFAULT 0,
  `ctr` decimal(5,2) DEFAULT 0.00,
  `downloads` int(11) DEFAULT 0,
  `trending_score` int(11) DEFAULT 0,
  `search_impressions` int(11) DEFAULT 0,
  `status` enum('Draft','Published','Scheduled') DEFAULT 'Draft',
  `is_featured` tinyint(1) DEFAULT 0,
  `priority_score` int(11) DEFAULT 0,
  `published_by` varchar(100) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `colleges`
--

INSERT INTO `colleges` (`id`, `name`, `short_name`, `slug`, `college_type`, `ownership_type`, `established_year`, `affiliated_university`, `accreditation`, `approval_bodies`, `campus_size`, `website_url`, `email`, `phone`, `logo`, `banner_image`, `thumbnail`, `country`, `state`, `city`, `address`, `pincode`, `latitude`, `longitude`, `google_maps_url`, `nearby_landmark`, `short_description`, `full_description`, `why_choose_this_college`, `highlights`, `history`, `mission_vision`, `admission_process`, `application_start_date`, `application_end_date`, `counselling_info`, `required_documents`, `reservation_policy`, `how_to_apply`, `created_at`, `updated_at`, `has_library`, `has_labs`, `has_wifi`, `has_auditorium`, `has_gym`, `has_sports`, `has_medical`, `has_transport`, `has_cafeteria`, `has_bank`, `has_atm`, `infrastructure_description`, `boys_hostel_available`, `girls_hostel_available`, `hostel_fees`, `room_types`, `mess_charges`, `hostel_rules`, `hostel_images`, `meta_title`, `meta_description`, `meta_keywords`, `canonical_url`, `schema_json`, `og_image`, `meta_robots`, `page_views`, `lead_count`, `ctr`, `downloads`, `trending_score`, `search_impressions`, `status`, `is_featured`, `priority_score`, `published_by`, `published_at`) VALUES
(1, 'Madhav arora', NULL, 'madhav-arora', 'Law', 'Private', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-23 16:54:24', '2026-05-23 16:54:24', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'index, follow', 0, 0, 0.00, 0, 0, 0, 'Draft', 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `college_courses`
--

CREATE TABLE `college_courses` (
  `id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `degree` varchar(100) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `total_fees` decimal(10,2) DEFAULT NULL,
  `annual_fees` decimal(10,2) DEFAULT NULL,
  `seats` int(11) DEFAULT NULL,
  `eligibility` text DEFAULT NULL,
  `entrance_exams_accepted` varchar(255) DEFAULT NULL,
  `brochure_pdf` varchar(255) DEFAULT NULL,
  `apply_link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `college_cutoffs`
--

CREATE TABLE `college_cutoffs` (
  `id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `cutoff_year` year(4) NOT NULL,
  `course_name` varchar(255) DEFAULT NULL,
  `exam_name` varchar(100) DEFAULT NULL,
  `counselling_round` varchar(50) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `opening_rank` int(11) DEFAULT NULL,
  `closing_rank` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `college_documents`
--

CREATE TABLE `college_documents` (
  `id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `college_faculty`
--

CREATE TABLE `college_faculty` (
  `id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `faculty_name` varchar(255) NOT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `experience` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `college_faqs`
--

CREATE TABLE `college_faqs` (
  `id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `college_gallery`
--

CREATE TABLE `college_gallery` (
  `id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `college_placements`
--

CREATE TABLE `college_placements` (
  `id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `placement_year` year(4) NOT NULL,
  `placement_percentage` decimal(5,2) DEFAULT NULL,
  `highest_package` decimal(10,2) DEFAULT NULL,
  `average_package` decimal(10,2) DEFAULT NULL,
  `median_package` decimal(10,2) DEFAULT NULL,
  `internship_percentage` decimal(5,2) DEFAULT NULL,
  `placement_report_pdf` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `college_recruiters`
--

CREATE TABLE `college_recruiters` (
  `id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `recruiter_name` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `college_reviews`
--

CREATE TABLE `college_reviews` (
  `id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `review_text` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_pinned` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `college_scholarships`
--

CREATE TABLE `college_scholarships` (
  `id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `scholarship_name` varchar(255) NOT NULL,
  `eligibility` text DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `official_link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `college_videos`
--

CREATE TABLE `college_videos` (
  `id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `youtube_url` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(100) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `conducting_authority` varchar(255) DEFAULT NULL,
  `exam_level` enum('National','State','University') DEFAULT 'National',
  `mode` enum('Online','Offline','Both') DEFAULT 'Online',
  `frequency` varchar(100) DEFAULT NULL,
  `official_website` varchar(255) DEFAULT NULL,
  `helpline` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive','Upcoming') DEFAULT 'Active',
  `logo` varchar(255) DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `min_qualification` varchar(255) DEFAULT NULL,
  `min_marks` varchar(100) DEFAULT NULL,
  `age_limit` varchar(100) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `subjects_required` varchar(255) DEFAULT NULL,
  `attempts_allowed` varchar(100) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `total_questions` varchar(100) DEFAULT NULL,
  `total_marks` varchar(100) DEFAULT NULL,
  `sections` varchar(255) DEFAULT NULL,
  `negative_marking` varchar(100) DEFAULT NULL,
  `language_options` varchar(255) DEFAULT NULL,
  `documents_required` text DEFAULT NULL,
  `application_fee` text DEFAULT NULL,
  `payment_modes` varchar(255) DEFAULT NULL,
  `correction_process` text DEFAULT NULL,
  `admit_card_date` date DEFAULT NULL,
  `admit_card_process` text DEFAULT NULL,
  `admit_card_link` varchar(255) DEFAULT NULL,
  `admit_card_instructions` text DEFAULT NULL,
  `documents_to_carry` text DEFAULT NULL,
  `result_date` date DEFAULT NULL,
  `scorecard_link` varchar(255) DEFAULT NULL,
  `cutoff_link` varchar(255) DEFAULT NULL,
  `merit_list_details` text DEFAULT NULL,
  `percentile_rules` text DEFAULT NULL,
  `normalization_method` text DEFAULT NULL,
  `counselling_authority` varchar(255) DEFAULT NULL,
  `reservation_rules` text DEFAULT NULL,
  `choice_filling_process` text DEFAULT NULL,
  `counselling_documents` text DEFAULT NULL,
  `reporting_process` text DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `schema_markup` text DEFAULT NULL,
  `canonical_url` varchar(255) DEFAULT NULL,
  `og_image` varchar(255) DEFAULT NULL,
  `keywords` text DEFAULT NULL,
  `publish_status` varchar(50) DEFAULT 'Draft',
  `is_featured` tinyint(1) DEFAULT 0,
  `priority_score` int(11) DEFAULT 0,
  `updated_by` varchar(100) DEFAULT NULL,
  `result_declared` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_application_steps`
--

CREATE TABLE `exam_application_steps` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `step_number` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_colleges_accepting`
--

CREATE TABLE `exam_colleges_accepting` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `course_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_counselling_rounds`
--

CREATE TABLE `exam_counselling_rounds` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `round_name` varchar(255) NOT NULL,
  `seat_matrix_link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_cutoffs`
--

CREATE TABLE `exam_cutoffs` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `year` int(11) DEFAULT NULL,
  `college_name` varchar(255) DEFAULT NULL,
  `course_name` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `opening_rank` int(11) DEFAULT NULL,
  `closing_rank` int(11) DEFAULT NULL,
  `round` varchar(100) DEFAULT NULL,
  `quota` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_dates`
--

CREATE TABLE `exam_dates` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_date` datetime DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(100) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_faqs`
--

CREATE TABLE `exam_faqs` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_news`
--

CREATE TABLE `exam_news` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `news_type` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `news_date` date DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_syllabus`
--

CREATE TABLE `exam_syllabus` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `topics` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `interested_course` varchar(255) DEFAULT NULL,
  `exam_score` varchar(100) DEFAULT NULL,
  `12th_percentage` decimal(5,2) DEFAULT NULL,
  `graduation_percentage` decimal(5,2) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `budget` varchar(100) DEFAULT NULL,
  `college_id` int(11) DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `campaign` varchar(255) DEFAULT NULL,
  `utm_tags` text DEFAULT NULL,
  `device` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'New',
  `assigned_to_college_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `dispute_reason` text DEFAULT NULL,
  `opened_at` datetime DEFAULT NULL,
  `viewed_at` datetime DEFAULT NULL,
  `clicked_at` datetime DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `quality_score` int(11) DEFAULT 0,
  `delivery_status` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_notes`
--

CREATE TABLE `lead_notes` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `note_type` varchar(50) DEFAULT 'Note',
  `content` text DEFAULT NULL,
  `created_by` varchar(100) DEFAULT 'Admin',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_timeline`
--

CREATE TABLE `lead_timeline` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `status_from` varchar(50) DEFAULT NULL,
  `status_to` varchar(50) DEFAULT NULL,
  `changed_by` varchar(100) DEFAULT 'System',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `college_id` int(11) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `student_email` varchar(255) DEFAULT NULL,
  `course_name` varchar(255) DEFAULT NULL,
  `graduation_year` int(11) DEFAULT NULL,
  `review_date` datetime DEFAULT current_timestamp(),
  `verification_status` varchar(50) DEFAULT 'Unverified',
  `moderation_status` varchar(50) DEFAULT 'Pending',
  `rating_placements` int(11) DEFAULT NULL,
  `rating_faculty` int(11) DEFAULT NULL,
  `rating_infrastructure` int(11) DEFAULT NULL,
  `rating_campus_life` int(11) DEFAULT NULL,
  `rating_value` int(11) DEFAULT NULL,
  `rating_hostel` int(11) DEFAULT NULL,
  `rating_crowd` int(11) DEFAULT NULL,
  `rating_safety` int(11) DEFAULT NULL,
  `rating_overall` decimal(3,1) DEFAULT NULL,
  `pros` text DEFAULT NULL,
  `cons` text DEFAULT NULL,
  `placement_experience` text DEFAULT NULL,
  `faculty_experience` text DEFAULT NULL,
  `hostel_experience` text DEFAULT NULL,
  `overall_verdict` text DEFAULT NULL,
  `reported_count` int(11) DEFAULT 0,
  `helpful_count` int(11) DEFAULT 0,
  `verification_method` varchar(100) DEFAULT NULL,
  `verification_evidence` varchar(255) DEFAULT NULL,
  `ai_score` int(11) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `flag_spam` tinyint(1) DEFAULT 0,
  `flag_duplicate` tinyint(1) DEFAULT 0,
  `flag_promo` tinyint(1) DEFAULT 0,
  `flag_abusive` tinyint(1) DEFAULT 0,
  `flag_fake_placement` tinyint(1) DEFAULT 0,
  `flag_mass_submission` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_pinned` tinyint(1) DEFAULT 0,
  `user_shadow_banned` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_reports`
--

CREATE TABLE `review_reports` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `reason` varchar(100) NOT NULL,
  `reported_by` varchar(255) DEFAULT NULL,
  `report_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_settings`
--

CREATE TABLE `review_settings` (
  `id` int(11) NOT NULL,
  `auto_approve` tinyint(1) DEFAULT 0,
  `min_length` int(11) DEFAULT 100,
  `allowed_languages` varchar(255) DEFAULT 'English',
  `require_verification` tinyint(1) DEFAULT 1,
  `profanity_filter` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review_settings`
--

INSERT INTO `review_settings` (`id`, `auto_approve`, `min_length`, `allowed_languages`, `require_verification`, `profanity_filter`) VALUES
(1, 0, 100, 'English', 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_roles`
--
ALTER TABLE `admin_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `resolved_by` (`resolved_by`);

--
-- Indexes for table `analytics_snapshots`
--
ALTER TABLE `analytics_snapshots`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blacklisted_entities`
--
ALTER TABLE `blacklisted_entities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `colleges`
--
ALTER TABLE `colleges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

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
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `college_documents`
--
ALTER TABLE `college_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

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
-- Indexes for table `college_gallery`
--
ALTER TABLE `college_gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `college_placements`
--
ALTER TABLE `college_placements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `college_recruiters`
--
ALTER TABLE `college_recruiters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `college_reviews`
--
ALTER TABLE `college_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `college_scholarships`
--
ALTER TABLE `college_scholarships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `college_videos`
--
ALTER TABLE `college_videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `exam_application_steps`
--
ALTER TABLE `exam_application_steps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `exam_colleges_accepting`
--
ALTER TABLE `exam_colleges_accepting`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `exam_counselling_rounds`
--
ALTER TABLE `exam_counselling_rounds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `exam_cutoffs`
--
ALTER TABLE `exam_cutoffs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `exam_dates`
--
ALTER TABLE `exam_dates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `exam_faqs`
--
ALTER TABLE `exam_faqs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `exam_news`
--
ALTER TABLE `exam_news`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `exam_syllabus`
--
ALTER TABLE `exam_syllabus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `lead_notes`
--
ALTER TABLE `lead_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_id` (`lead_id`);

--
-- Indexes for table `lead_timeline`
--
ALTER TABLE `lead_timeline`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_id` (`lead_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `review_reports`
--
ALTER TABLE `review_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `review_id` (`review_id`);

--
-- Indexes for table `review_settings`
--
ALTER TABLE `review_settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_roles`
--
ALTER TABLE `admin_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `analytics_snapshots`
--
ALTER TABLE `analytics_snapshots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `blacklisted_entities`
--
ALTER TABLE `blacklisted_entities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `colleges`
--
ALTER TABLE `colleges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `college_courses`
--
ALTER TABLE `college_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `college_cutoffs`
--
ALTER TABLE `college_cutoffs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `college_documents`
--
ALTER TABLE `college_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `college_faculty`
--
ALTER TABLE `college_faculty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `college_faqs`
--
ALTER TABLE `college_faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `college_gallery`
--
ALTER TABLE `college_gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `college_placements`
--
ALTER TABLE `college_placements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `college_recruiters`
--
ALTER TABLE `college_recruiters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `college_reviews`
--
ALTER TABLE `college_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `college_scholarships`
--
ALTER TABLE `college_scholarships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `college_videos`
--
ALTER TABLE `college_videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_application_steps`
--
ALTER TABLE `exam_application_steps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_colleges_accepting`
--
ALTER TABLE `exam_colleges_accepting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_counselling_rounds`
--
ALTER TABLE `exam_counselling_rounds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_cutoffs`
--
ALTER TABLE `exam_cutoffs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_dates`
--
ALTER TABLE `exam_dates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_faqs`
--
ALTER TABLE `exam_faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_news`
--
ALTER TABLE `exam_news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_syllabus`
--
ALTER TABLE `exam_syllabus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_notes`
--
ALTER TABLE `lead_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_timeline`
--
ALTER TABLE `lead_timeline`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_reports`
--
ALTER TABLE `review_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_settings`
--
ALTER TABLE `review_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD CONSTRAINT `admin_users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`id`);

--
-- Constraints for table `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `admin_users` (`id`),
  ADD CONSTRAINT `alerts_ibfk_2` FOREIGN KEY (`resolved_by`) REFERENCES `admin_users` (`id`);

--
-- Constraints for table `college_courses`
--
ALTER TABLE `college_courses`
  ADD CONSTRAINT `college_courses_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_cutoffs`
--
ALTER TABLE `college_cutoffs`
  ADD CONSTRAINT `college_cutoffs_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_documents`
--
ALTER TABLE `college_documents`
  ADD CONSTRAINT `college_documents_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `college_gallery`
--
ALTER TABLE `college_gallery`
  ADD CONSTRAINT `college_gallery_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_placements`
--
ALTER TABLE `college_placements`
  ADD CONSTRAINT `college_placements_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_recruiters`
--
ALTER TABLE `college_recruiters`
  ADD CONSTRAINT `college_recruiters_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_reviews`
--
ALTER TABLE `college_reviews`
  ADD CONSTRAINT `college_reviews_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_scholarships`
--
ALTER TABLE `college_scholarships`
  ADD CONSTRAINT `college_scholarships_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_videos`
--
ALTER TABLE `college_videos`
  ADD CONSTRAINT `college_videos_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_application_steps`
--
ALTER TABLE `exam_application_steps`
  ADD CONSTRAINT `exam_application_steps_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_colleges_accepting`
--
ALTER TABLE `exam_colleges_accepting`
  ADD CONSTRAINT `exam_colleges_accepting_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_colleges_accepting_ibfk_2` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_counselling_rounds`
--
ALTER TABLE `exam_counselling_rounds`
  ADD CONSTRAINT `exam_counselling_rounds_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_cutoffs`
--
ALTER TABLE `exam_cutoffs`
  ADD CONSTRAINT `exam_cutoffs_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_dates`
--
ALTER TABLE `exam_dates`
  ADD CONSTRAINT `exam_dates_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_faqs`
--
ALTER TABLE `exam_faqs`
  ADD CONSTRAINT `exam_faqs_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_news`
--
ALTER TABLE `exam_news`
  ADD CONSTRAINT `exam_news_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_syllabus`
--
ALTER TABLE `exam_syllabus`
  ADD CONSTRAINT `exam_syllabus_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leads`
--
ALTER TABLE `leads`
  ADD CONSTRAINT `leads_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lead_notes`
--
ALTER TABLE `lead_notes`
  ADD CONSTRAINT `lead_notes_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lead_timeline`
--
ALTER TABLE `lead_timeline`
  ADD CONSTRAINT `lead_timeline_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_reports`
--
ALTER TABLE `review_reports`
  ADD CONSTRAINT `review_reports_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE;
--
-- Database: `edusearch`
--
CREATE DATABASE IF NOT EXISTS `edusearch` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `edusearch`;

-- --------------------------------------------------------

--
-- Table structure for table `admin_audit_log`
--

CREATE TABLE `admin_audit_log` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `admin_user_id` varchar(36) NOT NULL,
  `action` enum('CREATE','UPDATE','DELETE','APPROVE','REJECT','VERIFY','SUSPEND','RESTORE','LOGIN','PERMISSION_CHANGE') NOT NULL,
  `entity_type` varchar(100) NOT NULL,
  `entity_id` varchar(36) DEFAULT NULL,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_value`)),
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_value`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_counselor_logs`
--

CREATE TABLE `ai_counselor_logs` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `session_id` varchar(36) NOT NULL,
  `student_id` varchar(36) DEFAULT NULL,
  `turn_number` tinyint(3) UNSIGNED NOT NULL,
  `prompt_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`prompt_payload`)),
  `response_text` text DEFAULT NULL,
  `recommended_college_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recommended_college_ids`)),
  `feedback_score` tinyint(4) DEFAULT NULL,
  `cache_hit` tinyint(1) NOT NULL DEFAULT 0,
  `response_time_ms` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_counselor_sessions`
--

CREATE TABLE `ai_counselor_sessions` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `user_id` varchar(36) DEFAULT NULL,
  `channel` enum('WEB','WHATSAPP') NOT NULL DEFAULT 'WEB',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ended_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `student_id` varchar(36) NOT NULL,
  `college_id` varchar(36) NOT NULL,
  `course_id` varchar(36) NOT NULL,
  `status` enum('SUBMITTED','UNDER_REVIEW','SHORTLISTED','INTERVIEW_SCHEDULED','OFFER_ISSUED','ADMITTED','WAITLISTED','REJECTED','WITHDRAWN') NOT NULL DEFAULT 'SUBMITTED',
  `payment_status` enum('PENDING','PAID','FAILED','REFUNDED') NOT NULL DEFAULT 'PENDING',
  `payment_id` varchar(255) DEFAULT NULL,
  `application_fee` int(11) DEFAULT NULL,
  `documents_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`documents_json`)),
  `interview_at` datetime DEFAULT NULL,
  `offer_letter_url` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `b2b_invoices`
--

CREATE TABLE `b2b_invoices` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `b2b_account_id` varchar(36) NOT NULL,
  `period_month` tinyint(4) NOT NULL,
  `period_year` smallint(6) NOT NULL,
  `leads_delivered` int(11) NOT NULL DEFAULT 0,
  `cpl_rate` int(11) NOT NULL,
  `gross_amount` int(11) NOT NULL,
  `discount_amount` int(11) NOT NULL DEFAULT 0,
  `net_amount` int(11) NOT NULL,
  `status` enum('DRAFT','SENT','PAID','OVERDUE','DISPUTED') NOT NULL DEFAULT 'DRAFT',
  `razorpay_payment_id` varchar(255) DEFAULT NULL,
  `pdf_url` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `colleges`
--

CREATE TABLE `colleges` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `name` varchar(255) NOT NULL,
  `short_name` varchar(100) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `country` varchar(100) DEFAULT 'India',
  `address` text DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `google_maps_url` text DEFAULT NULL,
  `nearby_landmark` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `established_year` smallint(6) DEFAULT NULL,
  `type` enum('PRIVATE','GOVERNMENT','DEEMED','CENTRAL','AUTONOMOUS') NOT NULL,
  `ownership_type` varchar(100) DEFAULT NULL,
  `affiliated_to` varchar(255) DEFAULT NULL,
  `approval_bodies` text DEFAULT NULL,
  `campus_area_acres` decimal(8,2) DEFAULT NULL,
  `naac_grade` varchar(10) DEFAULT NULL,
  `accreditation` varchar(255) DEFAULT NULL,
  `naac_year` smallint(6) DEFAULT NULL,
  `nirf_rank` int(11) DEFAULT NULL,
  `nirf_year` smallint(6) DEFAULT NULL,
  `total_students` int(11) DEFAULT NULL,
  `total_faculty` int(11) DEFAULT NULL,
  `gender_type` varchar(20) DEFAULT NULL,
  `residential_type` varchar(30) DEFAULT NULL,
  `about_description` text DEFAULT NULL,
  `admission_process` text DEFAULT NULL,
  `logo_url` text DEFAULT NULL,
  `thumbnail_url` text DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `website_url` text DEFAULT NULL,
  `banner_url` text DEFAULT NULL,
  `brochure_pdf_url` text DEFAULT NULL,
  `video_tour_url` text DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_sponsored` tinyint(1) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `data_quality_score` tinyint(4) DEFAULT 0,
  `claimed_by_user_id` varchar(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `colleges`
--

INSERT INTO `colleges` (`id`, `name`, `short_name`, `slug`, `city`, `state`, `country`, `address`, `pincode`, `google_maps_url`, `nearby_landmark`, `latitude`, `longitude`, `established_year`, `type`, `ownership_type`, `affiliated_to`, `approval_bodies`, `campus_area_acres`, `naac_grade`, `accreditation`, `naac_year`, `nirf_rank`, `nirf_year`, `total_students`, `total_faculty`, `gender_type`, `residential_type`, `about_description`, `admission_process`, `logo_url`, `thumbnail_url`, `email`, `phone`, `website_url`, `banner_url`, `brochure_pdf_url`, `video_tour_url`, `is_verified`, `is_sponsored`, `is_featured`, `data_quality_score`, `claimed_by_user_id`, `created_at`, `updated_at`) VALUES
('4af1a566b2ae7acd63d0e7837d361115', 'Indian Institute of Technology (IIT) Delhi', NULL, 'iit-delhi', 'New Delhi', 'Delhi', 'India', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'GOVERNMENT', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, NULL, NULL, NULL, NULL, 'Indian Institute of Technology Delhi is a public technical and research university located in Hauz Khas, Delhi, India.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0, NULL, '2026-05-19 10:18:37', '2026-05-19 10:18:37'),
('564476567a71e0b6d3da8aea74528dd8', 'Vellore Institute of Technology (VIT)', NULL, 'vit-vellore', 'Vellore', 'Tamil Nadu', 'India', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PRIVATE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 11, NULL, NULL, NULL, NULL, NULL, 'VIT is a private deemed university located in Vellore, Tamil Nadu, India. It has campuses in Vellore, Chennai, Bhopal and Amaravati.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0, NULL, '2026-05-19 10:18:39', '2026-05-19 10:18:39'),
('5c62e6514971141cea78232a8dc22360', 'All India Institute of Medical Sciences (AIIMS) Delhi', 'TEST_SHORT_NAME', 'aiims-delhi', 'New Delhi', 'Delhi', 'India', '', '', '', '', NULL, NULL, 1999, 'GOVERNMENT', '', '', '', NULL, NULL, '', NULL, 1, NULL, NULL, NULL, NULL, NULL, 'AIIMS Delhi is a public medical research university and hospital based in New Delhi, India.', NULL, '', '', '', '', '', '', NULL, NULL, 1, 0, 0, 0, NULL, '2026-05-19 10:18:40', '2026-05-19 10:27:56'),
('607f4675c4b9544eac9f7cba9e76db31', 'Birla Institute of Technology and Science (BITS) Pilani', NULL, 'bits-pilani', 'Pilani', 'Rajasthan', 'India', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PRIVATE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 25, NULL, NULL, NULL, NULL, NULL, 'BITS Pilani is a private deemed university in Pilani, India. It focuses primarily on higher education in engineering and the sciences.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0, NULL, '2026-05-19 10:18:37', '2026-05-19 10:18:37'),
('a714a77b44e2016d0d3b82eaa5fa2e77', 'Indian Institute of Management (IIM) Ahmedabad', NULL, 'iim-ahmedabad', 'Ahmedabad', 'Gujarat', 'India', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'GOVERNMENT', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, 'IIM Ahmedabad is a business school located in Ahmedabad, Gujarat, India. It was the second Indian Institute of Management to be established.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0, NULL, '2026-05-19 10:18:37', '2026-05-19 10:18:37'),
('da2cadbde9df980001330f154835104e', 'Lovely Professional University (LPU)', NULL, 'lpu-phagwara', 'Phagwara', 'Punjab', 'India', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PRIVATE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 38, NULL, NULL, NULL, NULL, NULL, 'LPU is a private university located in Phagwara, Punjab, India. The university was established in 2005 by Lovely International Trust.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 0, NULL, '2026-05-19 10:18:40', '2026-05-19 10:18:40');

-- --------------------------------------------------------

--
-- Table structure for table `college_approvals`
--

CREATE TABLE `college_approvals` (
  `college_id` varchar(36) NOT NULL,
  `body_name` varchar(50) NOT NULL,
  `cert_url` text DEFAULT NULL,
  `approved_year` smallint(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `college_b2b_accounts`
--

CREATE TABLE `college_b2b_accounts` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `college_id` varchar(36) NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `plan` enum('FREE','GROWTH','PREMIUM','ENTERPRISE') NOT NULL DEFAULT 'FREE',
  `plan_started_at` timestamp NULL DEFAULT NULL,
  `plan_expires_at` timestamp NULL DEFAULT NULL,
  `cpl_rate` int(11) NOT NULL DEFAULT 500,
  `lead_credit_balance` int(11) NOT NULL DEFAULT 0,
  `monthly_lead_cap` int(11) DEFAULT NULL,
  `is_trial` tinyint(1) NOT NULL DEFAULT 0,
  `trial_expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `college_facilities`
--

CREATE TABLE `college_facilities` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `college_id` varchar(36) NOT NULL,
  `facility_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `college_gallery`
--

CREATE TABLE `college_gallery` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `college_id` varchar(36) NOT NULL,
  `image_url` text NOT NULL,
  `category` enum('CAMPUS','HOSTEL','LABS','EVENTS','CLASSROOMS','SPORTS','CAFETERIA','OTHER') NOT NULL DEFAULT 'OTHER',
  `caption` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `uploaded_by` varchar(36) DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `college_qa`
--

CREATE TABLE `college_qa` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `college_id` varchar(36) NOT NULL,
  `asked_by` varchar(36) DEFAULT NULL,
  `question` text NOT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `view_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `report_count` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `college_qa_answers`
--

CREATE TABLE `college_qa_answers` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `question_id` varchar(36) NOT NULL,
  `answered_by` varchar(36) NOT NULL,
  `answer` text NOT NULL,
  `role_badge` varchar(50) DEFAULT NULL,
  `is_official` tinyint(1) NOT NULL DEFAULT 0,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `upvotes` int(11) NOT NULL DEFAULT 0,
  `status` enum('APPROVED','REJECTED') NOT NULL DEFAULT 'APPROVED',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `college_rankings`
--

CREATE TABLE `college_rankings` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `college_id` varchar(36) NOT NULL,
  `ranking_agency` enum('NIRF','THE_WEEK','OUTLOOK','INDIA_TODAY','QS','EDUSEARCH') DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `year` smallint(6) NOT NULL,
  `rank` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `college_id` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `stream` varchar(100) NOT NULL,
  `specialization` varchar(150) DEFAULT NULL,
  `degree_level` enum('UG','PG','DIPLOMA','PhD','CERTIFICATE','INTEGRATED') NOT NULL,
  `study_mode` enum('FULL_TIME','PART_TIME','DISTANCE','ONLINE') NOT NULL DEFAULT 'FULL_TIME',
  `duration_years` decimal(3,1) NOT NULL,
  `total_fees` int(11) NOT NULL,
  `annual_fees` int(11) DEFAULT NULL,
  `first_year_fees` int(11) DEFAULT NULL,
  `eligibility_criteria` text DEFAULT NULL,
  `entrance_exams_accepted` text DEFAULT NULL,
  `total_seats` int(11) DEFAULT NULL,
  `syllabus_pdf_url` text DEFAULT NULL,
  `apply_link` text DEFAULT NULL,
  `brochure_pdf_url` text DEFAULT NULL,
  `course_description` text DEFAULT NULL,
  `status` enum('ACTIVE','PAUSED','DISCONTINUED') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_exams`
--

CREATE TABLE `course_exams` (
  `course_id` varchar(36) NOT NULL,
  `exam_id` varchar(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_seats`
--

CREATE TABLE `course_seats` (
  `course_id` varchar(36) NOT NULL,
  `general` int(11) DEFAULT NULL,
  `obc_ncl` int(11) DEFAULT NULL,
  `sc` int(11) DEFAULT NULL,
  `st` int(11) DEFAULT NULL,
  `ews` int(11) DEFAULT NULL,
  `pwd` int(11) DEFAULT NULL,
  `nri` int(11) DEFAULT NULL,
  `management` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cutoffs`
--

CREATE TABLE `cutoffs` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `course_id` varchar(36) NOT NULL,
  `exam_id` varchar(36) NOT NULL,
  `year` smallint(6) NOT NULL,
  `category` enum('GENERAL','OBC-NCL','SC','ST','EWS','PWD','NRI') NOT NULL,
  `quota` varchar(50) DEFAULT NULL,
  `counseling_round` varchar(50) DEFAULT NULL,
  `cutoff_type` enum('RANK','SCORE','PERCENTILE','MARKS') NOT NULL,
  `opening_value` decimal(10,2) DEFAULT NULL,
  `closing_value` decimal(10,2) DEFAULT NULL,
  `is_expected` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `data_deletion_requests`
--

CREATE TABLE `data_deletion_requests` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `user_id` varchar(36) NOT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `statutory_deadline` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('PENDING','IN_PROGRESS','COMPLETED') NOT NULL DEFAULT 'PENDING',
  `processed_by` varchar(36) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `name` varchar(100) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `conducting_body` varchar(255) DEFAULT NULL,
  `level` enum('NATIONAL','STATE','UNIVERSITY','COLLEGE_LEVEL') NOT NULL,
  `stream` varchar(100) DEFAULT NULL,
  `mode` varchar(50) DEFAULT NULL,
  `official_url` text DEFAULT NULL,
  `syllabus_pdf_url` text DEFAULT NULL,
  `negative_marking` tinyint(1) NOT NULL DEFAULT 0,
  `total_marks` int(11) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_alerts`
--

CREATE TABLE `exam_alerts` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `user_id` varchar(36) NOT NULL,
  `exam_id` varchar(36) NOT NULL,
  `alert_type` enum('RESULT','APPLICATION_OPEN','ADMIT_CARD','COUNSELLING') NOT NULL DEFAULT 'RESULT',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_sessions`
--

CREATE TABLE `exam_sessions` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `exam_id` varchar(36) NOT NULL,
  `session_name` varchar(100) DEFAULT NULL,
  `year` smallint(6) NOT NULL,
  `application_open` date DEFAULT NULL,
  `application_close` date DEFAULT NULL,
  `exam_date` date DEFAULT NULL,
  `admit_card_date` date DEFAULT NULL,
  `result_date` date DEFAULT NULL,
  `counselling_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `international_universities`
--

CREATE TABLE `international_universities` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `name` varchar(255) NOT NULL,
  `country` varchar(100) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `qs_rank` int(11) DEFAULT NULL,
  `avg_tuition` decimal(10,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `ielts_score` decimal(3,1) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `is_partner` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ip_blacklist`
--

CREATE TABLE `ip_blacklist` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `ip_address` varchar(45) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `student_id` varchar(36) DEFAULT NULL,
  `college_id` varchar(36) NOT NULL,
  `course_id` varchar(36) DEFAULT NULL,
  `student_name` varchar(255) NOT NULL,
  `student_phone` varchar(20) NOT NULL,
  `student_email` varchar(255) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `quality_score` enum('HIGH','MEDIUM','LOW') NOT NULL DEFAULT 'MEDIUM',
  `status` enum('NEW','CONTACTED','CONVERTED','JUNK','INVALID') NOT NULL DEFAULT 'NEW',
  `source_page` varchar(500) DEFAULT NULL,
  `utm_source` varchar(100) DEFAULT NULL,
  `utm_medium` varchar(100) DEFAULT NULL,
  `utm_campaign` varchar(100) DEFAULT NULL,
  `ip_hash` varchar(64) DEFAULT NULL,
  `is_blacklisted` tinyint(1) NOT NULL DEFAULT 0,
  `brevo_sms_sent` tinyint(1) NOT NULL DEFAULT 0,
  `brevo_email_sent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `moderation_rules`
--

CREATE TABLE `moderation_rules` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `rule_name` varchar(255) NOT NULL,
  `condition_text` text DEFAULT NULL,
  `action` enum('APPROVE','REJECT','ESCALATE') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `moderation_tasks`
--

CREATE TABLE `moderation_tasks` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `task_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('LOW','MEDIUM','HIGH','URGENT') NOT NULL DEFAULT 'MEDIUM',
  `status` enum('PENDING','IN_PROGRESS','COMPLETED') NOT NULL DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `user_id` varchar(36) NOT NULL,
  `type` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `action_url` varchar(500) DEFAULT NULL,
  `channel` enum('IN_APP','EMAIL','SMS','PUSH','WHATSAPP') NOT NULL DEFAULT 'IN_APP',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `placement_companies`
--

CREATE TABLE `placement_companies` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `placement_stat_id` varchar(36) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `logo_url` text DEFAULT NULL,
  `offers_made` int(11) DEFAULT NULL,
  `highest_ctc` decimal(8,2) DEFAULT NULL,
  `sector` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `placement_stats`
--

CREATE TABLE `placement_stats` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `college_id` varchar(36) NOT NULL,
  `year` smallint(6) NOT NULL,
  `average_package` decimal(8,2) DEFAULT NULL,
  `highest_package` decimal(8,2) DEFAULT NULL,
  `median_package` decimal(8,2) DEFAULT NULL,
  `total_recruiters` int(11) DEFAULT NULL,
  `placement_percentage` decimal(5,2) DEFAULT NULL,
  `students_placed` int(11) DEFAULT NULL,
  `internship_data` text DEFAULT NULL,
  `total_eligible` int(11) DEFAULT NULL,
  `source_pdf_url` text DEFAULT NULL,
  `placement_report_pdf` text DEFAULT NULL,
  `is_self_reported` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `college_id` varchar(36) NOT NULL,
  `student_id` varchar(36) NOT NULL,
  `course_id` varchar(36) DEFAULT NULL,
  `academic_rating` decimal(3,1) DEFAULT NULL CHECK (`academic_rating` between 1 and 10),
  `faculty_rating` decimal(3,1) DEFAULT NULL CHECK (`faculty_rating` between 1 and 10),
  `infrastructure_rating` decimal(3,1) DEFAULT NULL CHECK (`infrastructure_rating` between 1 and 10),
  `accommodation_rating` decimal(3,1) DEFAULT NULL CHECK (`accommodation_rating` between 1 and 10),
  `placement_rating` decimal(3,1) DEFAULT NULL CHECK (`placement_rating` between 1 and 10),
  `social_life_rating` decimal(3,1) DEFAULT NULL CHECK (`social_life_rating` between 1 and 10),
  `overall_rating` decimal(3,2) DEFAULT NULL,
  `batch_year` smallint(6) NOT NULL,
  `admission_year` smallint(6) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `course_curriculum_review` text DEFAULT NULL,
  `faculty_review` text DEFAULT NULL,
  `campus_life_review` text DEFAULT NULL,
  `placement_review` text DEFAULT NULL,
  `admission_process_review` text DEFAULT NULL,
  `fees_and_financial_aid_review` text DEFAULT NULL,
  `pros` text DEFAULT NULL,
  `cons` text DEFAULT NULL,
  `verification_method` enum('COLLEGE_EMAIL_OTP','STUDENT_ID_UPLOAD','ALUMNI_CERT') DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_evidence_url` text DEFAULT NULL,
  `sentiment_label` enum('POSITIVE','NEUTRAL','NEGATIVE','MIXED') DEFAULT NULL,
  `quality_score` tinyint(3) UNSIGNED DEFAULT 0,
  `status` enum('PENDING','APPROVED','REJECTED','ESCALATED') NOT NULL DEFAULT 'PENDING',
  `rejection_reason` varchar(100) DEFAULT NULL,
  `helpful_votes` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_responses`
--

CREATE TABLE `review_responses` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `review_id` varchar(36) NOT NULL,
  `college_id` varchar(36) NOT NULL,
  `response` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scholarships`
--

CREATE TABLE `scholarships` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `college_id` varchar(36) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `provider_name` varchar(255) NOT NULL,
  `category` enum('GOVERNMENT','PRIVATE','INSTITUTIONAL','INTERNATIONAL') NOT NULL,
  `target_caste_category` varchar(100) DEFAULT NULL,
  `state_scope` varchar(100) DEFAULT NULL,
  `income_limit` int(11) DEFAULT NULL,
  `merit_percentage_min` decimal(5,2) DEFAULT NULL,
  `amount_description` varchar(255) DEFAULT NULL,
  `amount_inr` int(11) DEFAULT NULL,
  `about_scholarship` text DEFAULT NULL,
  `required_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`required_documents`)),
  `application_link` text DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` enum('ACTIVE','EXPIRED','COMING_SOON') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `user_id` varchar(36) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shortlists`
--

CREATE TABLE `shortlists` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `user_id` varchar(36) NOT NULL,
  `college_id` varchar(36) NOT NULL,
  `stage` enum('TO_RESEARCH','INTERESTED','APPLIED','DECISION_MADE') NOT NULL DEFAULT 'TO_RESEARCH',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_exam_scores`
--

CREATE TABLE `student_exam_scores` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `user_id` varchar(36) NOT NULL,
  `exam_id` varchar(36) NOT NULL,
  `score` decimal(10,2) DEFAULT NULL,
  `percentile` decimal(5,2) DEFAULT NULL,
  `rank` int(11) DEFAULT NULL,
  `year` smallint(6) NOT NULL,
  `roll_number` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `user_id` varchar(36) NOT NULL,
  `stream` varchar(50) DEFAULT NULL,
  `class_10_marks` decimal(5,2) DEFAULT NULL,
  `class_10_board` varchar(100) DEFAULT NULL,
  `class_10_year` smallint(6) DEFAULT NULL,
  `class_12_marks` decimal(5,2) DEFAULT NULL,
  `class_12_board` varchar(100) DEFAULT NULL,
  `class_12_year` smallint(6) DEFAULT NULL,
  `class_12_stream` varchar(50) DEFAULT NULL,
  `preferred_cities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferred_cities`)),
  `preferred_states` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferred_states`)),
  `budget_min` int(11) DEFAULT NULL,
  `budget_max` int(11) DEFAULT NULL,
  `career_goals` text DEFAULT NULL,
  `counseling_points` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` enum('STUDENT','COLLEGE_ADMIN','SUPER_ADMIN','MODERATOR','DATA_ENTRY') NOT NULL DEFAULT 'STUDENT',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `image_url` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password_hash`, `role`, `email_verified`, `image_url`, `created_at`, `updated_at`) VALUES
('2c9edca4-4eac-11f1-bd8a-c8f7507a8de6', 'Super Admin', 'admin@edusearch.in', NULL, '$2y$10$BI4KdPTXYyRvs9xk/QkgYeplnso7mYRcX90PMU5M.0lRhnZvLfyNW', 'SUPER_ADMIN', 0, NULL, '2026-05-13 09:14:41', '2026-05-19 10:04:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_audit_log`
--
ALTER TABLE `admin_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_admin_user` (`admin_user_id`),
  ADD KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_audit_created` (`created_at`);

--
-- Indexes for table `ai_counselor_logs`
--
ALTER TABLE `ai_counselor_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ai_logs_session` (`session_id`),
  ADD KEY `idx_ai_logs_student` (`student_id`);

--
-- Indexes for table `ai_counselor_sessions`
--
ALTER TABLE `ai_counselor_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `idx_apps_student` (`student_id`),
  ADD KEY `idx_apps_college` (`college_id`),
  ADD KEY `idx_apps_status` (`status`),
  ADD KEY `idx_apps_applied_at` (`applied_at`);

--
-- Indexes for table `b2b_invoices`
--
ALTER TABLE `b2b_invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoices_b2b_status` (`b2b_account_id`,`status`);

--
-- Indexes for table `colleges`
--
ALTER TABLE `colleges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `claimed_by_user_id` (`claimed_by_user_id`),
  ADD KEY `idx_colleges_city_state` (`city`,`state`),
  ADD KEY `idx_colleges_state` (`state`),
  ADD KEY `idx_colleges_type` (`type`),
  ADD KEY `idx_colleges_naac` (`naac_grade`),
  ADD KEY `idx_colleges_nirf` (`nirf_rank`),
  ADD KEY `idx_colleges_verified` (`is_verified`),
  ADD KEY `idx_colleges_featured` (`is_featured`),
  ADD KEY `idx_colleges_quality` (`data_quality_score`);

--
-- Indexes for table `college_approvals`
--
ALTER TABLE `college_approvals`
  ADD PRIMARY KEY (`college_id`,`body_name`),
  ADD KEY `idx_approvals_college` (`college_id`);

--
-- Indexes for table `college_b2b_accounts`
--
ALTER TABLE `college_b2b_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `college_id` (`college_id`),
  ADD KEY `idx_b2b_college` (`college_id`);

--
-- Indexes for table `college_facilities`
--
ALTER TABLE `college_facilities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `college_gallery`
--
ALTER TABLE `college_gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_gallery_college` (`college_id`);

--
-- Indexes for table `college_qa`
--
ALTER TABLE `college_qa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asked_by` (`asked_by`),
  ADD KEY `idx_qa_college_status` (`college_id`,`status`);

--
-- Indexes for table `college_qa_answers`
--
ALTER TABLE `college_qa_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `answered_by` (`answered_by`),
  ADD KEY `idx_qa_answers_question` (`question_id`);

--
-- Indexes for table `college_rankings`
--
ALTER TABLE `college_rankings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rankings_college_year` (`college_id`,`year`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_courses_college` (`college_id`),
  ADD KEY `idx_courses_stream` (`stream`),
  ADD KEY `idx_courses_degree` (`degree_level`),
  ADD KEY `idx_courses_fees` (`total_fees`),
  ADD KEY `idx_courses_status` (`status`);

--
-- Indexes for table `course_exams`
--
ALTER TABLE `course_exams`
  ADD PRIMARY KEY (`course_id`,`exam_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `course_seats`
--
ALTER TABLE `course_seats`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `cutoffs`
--
ALTER TABLE `cutoffs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `idx_cutoffs_course_exam_yr` (`course_id`,`exam_id`,`year`),
  ADD KEY `idx_cutoffs_closing` (`closing_value`);

--
-- Indexes for table `data_deletion_requests`
--
ALTER TABLE `data_deletion_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_exams_level` (`level`);

--
-- Indexes for table `exam_alerts`
--
ALTER TABLE `exam_alerts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_exam_alert` (`user_id`,`exam_id`,`alert_type`),
  ADD KEY `idx_exam_alerts_user` (`user_id`),
  ADD KEY `idx_exam_alerts_exam` (`exam_id`);

--
-- Indexes for table `exam_sessions`
--
ALTER TABLE `exam_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_exam_sessions_exam_yr` (`exam_id`,`year`);

--
-- Indexes for table `international_universities`
--
ALTER TABLE `international_universities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ip_blacklist`
--
ALTER TABLE `ip_blacklist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip_address` (`ip_address`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `idx_leads_college_created` (`college_id`,`created_at`),
  ADD KEY `idx_leads_student` (`student_id`),
  ADD KEY `idx_leads_status` (`status`),
  ADD KEY `idx_leads_quality` (`quality_score`),
  ADD KEY `idx_leads_blacklisted` (`is_blacklisted`);

--
-- Indexes for table `moderation_rules`
--
ALTER TABLE `moderation_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `moderation_tasks`
--
ALTER TABLE `moderation_tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifs_user_unread` (`user_id`,`is_read`);

--
-- Indexes for table `placement_companies`
--
ALTER TABLE `placement_companies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `placement_stat_id` (`placement_stat_id`);

--
-- Indexes for table `placement_stats`
--
ALTER TABLE `placement_stats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `idx_reviews_college_status` (`college_id`,`status`),
  ADD KEY `idx_reviews_student` (`student_id`),
  ADD KEY `idx_reviews_created` (`created_at`);

--
-- Indexes for table `review_responses`
--
ALTER TABLE `review_responses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `review_id` (`review_id`),
  ADD KEY `college_id` (`college_id`);

--
-- Indexes for table `scholarships`
--
ALTER TABLE `scholarships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_scholarships_state` (`state_scope`),
  ADD KEY `idx_scholarships_deadline` (`deadline`),
  ADD KEY `idx_scholarships_status` (`status`),
  ADD KEY `fk_scholarships_college` (`college_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_sessions_user_id` (`user_id`),
  ADD KEY `idx_sessions_expires` (`expires_at`);

--
-- Indexes for table `shortlists`
--
ALTER TABLE `shortlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_college` (`user_id`,`college_id`),
  ADD KEY `college_id` (`college_id`),
  ADD KEY `idx_shortlists_user` (`user_id`);

--
-- Indexes for table `student_exam_scores`
--
ALTER TABLE `student_exam_scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `idx_exam_scores_user` (`user_id`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `idx_users_role` (`role`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_counselor_logs`
--
ALTER TABLE `ai_counselor_logs`
  ADD CONSTRAINT `ai_counselor_logs_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `ai_counselor_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ai_counselor_logs_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ai_counselor_sessions`
--
ALTER TABLE `ai_counselor_sessions`
  ADD CONSTRAINT `ai_counselor_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `b2b_invoices`
--
ALTER TABLE `b2b_invoices`
  ADD CONSTRAINT `b2b_invoices_ibfk_1` FOREIGN KEY (`b2b_account_id`) REFERENCES `college_b2b_accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `colleges`
--
ALTER TABLE `colleges`
  ADD CONSTRAINT `colleges_ibfk_1` FOREIGN KEY (`claimed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `college_approvals`
--
ALTER TABLE `college_approvals`
  ADD CONSTRAINT `college_approvals_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_b2b_accounts`
--
ALTER TABLE `college_b2b_accounts`
  ADD CONSTRAINT `college_b2b_accounts_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_facilities`
--
ALTER TABLE `college_facilities`
  ADD CONSTRAINT `college_facilities_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_gallery`
--
ALTER TABLE `college_gallery`
  ADD CONSTRAINT `college_gallery_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `college_gallery_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `college_qa`
--
ALTER TABLE `college_qa`
  ADD CONSTRAINT `college_qa_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `college_qa_ibfk_2` FOREIGN KEY (`asked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `college_qa_answers`
--
ALTER TABLE `college_qa_answers`
  ADD CONSTRAINT `college_qa_answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `college_qa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `college_qa_answers_ibfk_2` FOREIGN KEY (`answered_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `college_rankings`
--
ALTER TABLE `college_rankings`
  ADD CONSTRAINT `college_rankings_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_exams`
--
ALTER TABLE `course_exams`
  ADD CONSTRAINT `course_exams_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_exams_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_seats`
--
ALTER TABLE `course_seats`
  ADD CONSTRAINT `course_seats_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cutoffs`
--
ALTER TABLE `cutoffs`
  ADD CONSTRAINT `cutoffs_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cutoffs_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `data_deletion_requests`
--
ALTER TABLE `data_deletion_requests`
  ADD CONSTRAINT `data_deletion_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_alerts`
--
ALTER TABLE `exam_alerts`
  ADD CONSTRAINT `exam_alerts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_alerts_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_sessions`
--
ALTER TABLE `exam_sessions`
  ADD CONSTRAINT `exam_sessions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leads`
--
ALTER TABLE `leads`
  ADD CONSTRAINT `leads_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leads_ibfk_2` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leads_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `placement_companies`
--
ALTER TABLE `placement_companies`
  ADD CONSTRAINT `placement_companies_ibfk_1` FOREIGN KEY (`placement_stat_id`) REFERENCES `placement_stats` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `placement_stats`
--
ALTER TABLE `placement_stats`
  ADD CONSTRAINT `placement_stats_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `review_responses`
--
ALTER TABLE `review_responses`
  ADD CONSTRAINT `review_responses_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_responses_ibfk_2` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `scholarships`
--
ALTER TABLE `scholarships`
  ADD CONSTRAINT `fk_scholarships_college` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shortlists`
--
ALTER TABLE `shortlists`
  ADD CONSTRAINT `shortlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shortlists_ibfk_2` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_exam_scores`
--
ALTER TABLE `student_exam_scores`
  ADD CONSTRAINT `student_exam_scores_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_exam_scores_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
--
-- Database: `himt_db`
--
CREATE DATABASE IF NOT EXISTS `himt_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `himt_db`;
--
-- Database: `news`
--
CREATE DATABASE IF NOT EXISTS `news` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `news`;

-- --------------------------------------------------------

--
-- Table structure for table `ad_zones`
--
-- Error reading structure for table news.ad_zones: #1932 - Table &#039;news.ad_zones&#039; doesn&#039;t exist in engine
-- Error reading data for table news.ad_zones: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `news`.`ad_zones`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--
-- Error reading structure for table news.articles: #1932 - Table &#039;news.articles&#039; doesn&#039;t exist in engine
-- Error reading data for table news.articles: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `news`.`articles`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `article_tags`
--
-- Error reading structure for table news.article_tags: #1932 - Table &#039;news.article_tags&#039; doesn&#039;t exist in engine
-- Error reading data for table news.article_tags: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `news`.`article_tags`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `article_translations`
--
-- Error reading structure for table news.article_translations: #1932 - Table &#039;news.article_translations&#039; doesn&#039;t exist in engine
-- Error reading data for table news.article_translations: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `news`.`article_translations`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `breaking_news`
--
-- Error reading structure for table news.breaking_news: #1932 - Table &#039;news.breaking_news&#039; doesn&#039;t exist in engine
-- Error reading data for table news.breaking_news: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `news`.`breaking_news`&#039; at line 1

--
-- Triggers `breaking_news`
--
DELIMITER $$
CREATE TRIGGER `bi_breaking_news_sync` BEFORE INSERT ON `breaking_news` FOR EACH ROW BEGIN
  SET NEW.is_active = IFNULL(NEW.is_active, IFNULL(NEW.active, 1));
  SET NEW.active = NEW.is_active;
  SET NEW.headline = IF(NEW.headline IS NULL OR NEW.headline = '', IFNULL(NEW.text, ''), NEW.headline);
  SET NEW.text = IFNULL(NEW.text, NEW.headline);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `bu_breaking_news_sync` BEFORE UPDATE ON `breaking_news` FOR EACH ROW BEGIN
  SET NEW.is_active = IFNULL(NEW.is_active, IFNULL(NEW.active, OLD.is_active));
  SET NEW.active = NEW.is_active;
  SET NEW.headline = IF(NEW.headline IS NULL OR NEW.headline = '', IFNULL(NEW.text, OLD.headline), NEW.headline);
  SET NEW.text = IFNULL(NEW.text, NEW.headline);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--
-- Error reading structure for table news.categories: #1932 - Table &#039;news.categories&#039; doesn&#039;t exist in engine
-- Error reading data for table news.categories: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `news`.`categories`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--
-- Error reading structure for table news.comments: #1932 - Table &#039;news.comments&#039; doesn&#039;t exist in engine
-- Error reading data for table news.comments: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `news`.`comments`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `media`
--
-- Error reading structure for table news.media: #1932 - Table &#039;news.media&#039; doesn&#039;t exist in engine
-- Error reading data for table news.media: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `news`.`media`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `page_views`
--
-- Error reading structure for table news.page_views: #1932 - Table &#039;news.page_views&#039; doesn&#039;t exist in engine
-- Error reading data for table news.page_views: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `news`.`page_views`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `push_subscribers`
--
-- Error reading structure for table news.push_subscribers: #1932 - Table &#039;news.push_subscribers&#039; doesn&#039;t exist in engine
-- Error reading data for table news.push_subscribers: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `news`.`push_subscribers`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `redirects`
--
-- Error reading structure for table news.redirects: #1932 - Table &#039;news.redirects&#039; doesn&#039;t exist in engine
-- Error reading data for table news.redirects: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `news`.`redirects`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--
-- Error reading structure for table news.settings: #1932 - Table &#039;news.settings&#039; doesn&#039;t exist in engine
-- Error reading data for table news.settings: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `news`.`settings`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `subcategories`
--
-- Error reading structure for table news.subcategories: #1932 - Table &#039;news.subcategories&#039; doesn&#039;t exist in engine
-- Error reading data for table news.subcategories: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `news`.`subcategories`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `subscribers`
--
-- Error reading structure for table news.subscribers: #1932 - Table &#039;news.subscribers&#039; doesn&#039;t exist in engine
-- Error reading data for table news.subscribers: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `news`.`subscribers`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--
-- Error reading structure for table news.tags: #1932 - Table &#039;news.tags&#039; doesn&#039;t exist in engine
-- Error reading data for table news.tags: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `news`.`tags`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
-- Error reading structure for table news.users: #1932 - Table &#039;news.users&#039; doesn&#039;t exist in engine
-- Error reading data for table news.users: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `news`.`users`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `user_names`
--
-- Error reading structure for table news.user_names: #1932 - Table &#039;news.user_names&#039; doesn&#039;t exist in engine
-- Error reading data for table news.user_names: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `news`.`user_names`&#039; at line 1
--
-- Database: `phpmyadmin`
--
CREATE DATABASE IF NOT EXISTS `phpmyadmin` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `phpmyadmin`;

-- --------------------------------------------------------

--
-- Table structure for table `pma__bookmark`
--

CREATE TABLE `pma__bookmark` (
  `id` int(10) UNSIGNED NOT NULL,
  `dbase` varchar(255) NOT NULL DEFAULT '',
  `user` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `query` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Bookmarks';

-- --------------------------------------------------------

--
-- Table structure for table `pma__central_columns`
--

CREATE TABLE `pma__central_columns` (
  `db_name` varchar(64) NOT NULL,
  `col_name` varchar(64) NOT NULL,
  `col_type` varchar(64) NOT NULL,
  `col_length` text DEFAULT NULL,
  `col_collation` varchar(64) NOT NULL,
  `col_isNull` tinyint(1) NOT NULL,
  `col_extra` varchar(255) DEFAULT '',
  `col_default` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Central list of columns';

-- --------------------------------------------------------

--
-- Table structure for table `pma__column_info`
--

CREATE TABLE `pma__column_info` (
  `id` int(5) UNSIGNED NOT NULL,
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `column_name` varchar(64) NOT NULL DEFAULT '',
  `comment` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `mimetype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `transformation` varchar(255) NOT NULL DEFAULT '',
  `transformation_options` varchar(255) NOT NULL DEFAULT '',
  `input_transformation` varchar(255) NOT NULL DEFAULT '',
  `input_transformation_options` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Column information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__designer_settings`
--

CREATE TABLE `pma__designer_settings` (
  `username` varchar(64) NOT NULL,
  `settings_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Settings related to Designer';

-- --------------------------------------------------------

--
-- Table structure for table `pma__export_templates`
--

CREATE TABLE `pma__export_templates` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL,
  `export_type` varchar(10) NOT NULL,
  `template_name` varchar(64) NOT NULL,
  `template_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved export templates';

-- --------------------------------------------------------

--
-- Table structure for table `pma__favorite`
--

CREATE TABLE `pma__favorite` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Favorite tables';

-- --------------------------------------------------------

--
-- Table structure for table `pma__history`
--

CREATE TABLE `pma__history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db` varchar(64) NOT NULL DEFAULT '',
  `table` varchar(64) NOT NULL DEFAULT '',
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp(),
  `sqlquery` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='SQL history for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__navigationhiding`
--

CREATE TABLE `pma__navigationhiding` (
  `username` varchar(64) NOT NULL,
  `item_name` varchar(64) NOT NULL,
  `item_type` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Hidden items of navigation tree';

-- --------------------------------------------------------

--
-- Table structure for table `pma__pdf_pages`
--

CREATE TABLE `pma__pdf_pages` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `page_nr` int(10) UNSIGNED NOT NULL,
  `page_descr` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='PDF relation pages for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__recent`
--

CREATE TABLE `pma__recent` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Recently accessed tables';

--
-- Dumping data for table `pma__recent`
--

INSERT INTO `pma__recent` (`username`, `tables`) VALUES
('root', '[{\"db\":\"admission\",\"table\":\"colleges\"},{\"db\":\"himt_db\",\"table\":\"users\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `pma__relation`
--

CREATE TABLE `pma__relation` (
  `master_db` varchar(64) NOT NULL DEFAULT '',
  `master_table` varchar(64) NOT NULL DEFAULT '',
  `master_field` varchar(64) NOT NULL DEFAULT '',
  `foreign_db` varchar(64) NOT NULL DEFAULT '',
  `foreign_table` varchar(64) NOT NULL DEFAULT '',
  `foreign_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Relation table';

-- --------------------------------------------------------

--
-- Table structure for table `pma__savedsearches`
--

CREATE TABLE `pma__savedsearches` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `search_name` varchar(64) NOT NULL DEFAULT '',
  `search_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved searches';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_coords`
--

CREATE TABLE `pma__table_coords` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `pdf_page_number` int(11) NOT NULL DEFAULT 0,
  `x` float UNSIGNED NOT NULL DEFAULT 0,
  `y` float UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table coordinates for phpMyAdmin PDF output';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_info`
--

CREATE TABLE `pma__table_info` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `display_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_uiprefs`
--

CREATE TABLE `pma__table_uiprefs` (
  `username` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `prefs` text NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Tables'' UI preferences';

-- --------------------------------------------------------

--
-- Table structure for table `pma__tracking`
--

CREATE TABLE `pma__tracking` (
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `version` int(10) UNSIGNED NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `schema_snapshot` text NOT NULL,
  `schema_sql` text DEFAULT NULL,
  `data_sql` longtext DEFAULT NULL,
  `tracking` set('UPDATE','REPLACE','INSERT','DELETE','TRUNCATE','CREATE DATABASE','ALTER DATABASE','DROP DATABASE','CREATE TABLE','ALTER TABLE','RENAME TABLE','DROP TABLE','CREATE INDEX','DROP INDEX','CREATE VIEW','ALTER VIEW','DROP VIEW') DEFAULT NULL,
  `tracking_active` int(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Database changes tracking for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__userconfig`
--

CREATE TABLE `pma__userconfig` (
  `username` varchar(64) NOT NULL,
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `config_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User preferences storage for phpMyAdmin';

--
-- Dumping data for table `pma__userconfig`
--

INSERT INTO `pma__userconfig` (`username`, `timevalue`, `config_data`) VALUES
('root', '2026-07-02 09:47:19', '{\"Console\\/Mode\":\"collapse\"}');

-- --------------------------------------------------------

--
-- Table structure for table `pma__usergroups`
--

CREATE TABLE `pma__usergroups` (
  `usergroup` varchar(64) NOT NULL,
  `tab` varchar(64) NOT NULL,
  `allowed` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User groups with configured menu items';

-- --------------------------------------------------------

--
-- Table structure for table `pma__users`
--

CREATE TABLE `pma__users` (
  `username` varchar(64) NOT NULL,
  `usergroup` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Users and their assignments to user groups';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pma__central_columns`
--
ALTER TABLE `pma__central_columns`
  ADD PRIMARY KEY (`db_name`,`col_name`);

--
-- Indexes for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`);

--
-- Indexes for table `pma__designer_settings`
--
ALTER TABLE `pma__designer_settings`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`);

--
-- Indexes for table `pma__favorite`
--
ALTER TABLE `pma__favorite`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__history`
--
ALTER TABLE `pma__history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`,`db`,`table`,`timevalue`);

--
-- Indexes for table `pma__navigationhiding`
--
ALTER TABLE `pma__navigationhiding`
  ADD PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`);

--
-- Indexes for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  ADD PRIMARY KEY (`page_nr`),
  ADD KEY `db_name` (`db_name`);

--
-- Indexes for table `pma__recent`
--
ALTER TABLE `pma__recent`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__relation`
--
ALTER TABLE `pma__relation`
  ADD PRIMARY KEY (`master_db`,`master_table`,`master_field`),
  ADD KEY `foreign_field` (`foreign_db`,`foreign_table`);

--
-- Indexes for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`);

--
-- Indexes for table `pma__table_coords`
--
ALTER TABLE `pma__table_coords`
  ADD PRIMARY KEY (`db_name`,`table_name`,`pdf_page_number`);

--
-- Indexes for table `pma__table_info`
--
ALTER TABLE `pma__table_info`
  ADD PRIMARY KEY (`db_name`,`table_name`);

--
-- Indexes for table `pma__table_uiprefs`
--
ALTER TABLE `pma__table_uiprefs`
  ADD PRIMARY KEY (`username`,`db_name`,`table_name`);

--
-- Indexes for table `pma__tracking`
--
ALTER TABLE `pma__tracking`
  ADD PRIMARY KEY (`db_name`,`table_name`,`version`);

--
-- Indexes for table `pma__userconfig`
--
ALTER TABLE `pma__userconfig`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__usergroups`
--
ALTER TABLE `pma__usergroups`
  ADD PRIMARY KEY (`usergroup`,`tab`,`allowed`);

--
-- Indexes for table `pma__users`
--
ALTER TABLE `pma__users`
  ADD PRIMARY KEY (`username`,`usergroup`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__history`
--
ALTER TABLE `pma__history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  MODIFY `page_nr` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- Database: `test`
--
CREATE DATABASE IF NOT EXISTS `test` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `test`;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
