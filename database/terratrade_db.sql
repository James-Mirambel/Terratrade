-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 18, 2025 at 07:37 AM
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
-- Database: `terratrade_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 2, 'register', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-10 03:48:51'),
(2, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-10 03:48:59'),
(3, 2, 'property_create', 'properties', 3, NULL, '{\"title\":\"zdfv\",\"price\":\"234\",\"area_sqm\":\"2344\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-10 06:15:39'),
(4, 2, 'property_create', 'properties', 4, NULL, '{\"title\":\"as\",\"price\":\"12\",\"area_sqm\":\"123\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-10 06:17:38'),
(5, 2, 'property_create', 'properties', 5, NULL, '{\"title\":\"sdfsdfsdf\",\"price\":\"12\",\"area_sqm\":\"123\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-10 06:18:28'),
(6, 2, 'property_delete', 'properties', 2, '{\"title\":\"Test Property 2025-10-10 13:32:44\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-10 06:23:33'),
(7, 2, 'property_delete', 'properties', 2, '{\"title\":\"Test Property 2025-10-10 13:32:44\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-10 06:23:42'),
(8, 2, 'property_create', 'properties', 6, NULL, '{\"title\":\"asfsf\",\"price\":\"234\",\"area_sqm\":\"234\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-10 07:07:18'),
(9, 3, 'register', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-10 15:20:13'),
(10, 3, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-10 15:20:21'),
(11, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-10 15:23:14'),
(12, 4, 'register', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-10 15:25:05'),
(13, 4, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-10 15:25:13'),
(14, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-10 15:26:06'),
(15, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 04:13:02'),
(16, 4, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-11 04:13:56'),
(17, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-12 03:34:12'),
(18, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-13 15:37:32'),
(19, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 10:01:52'),
(20, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-15 06:34:37'),
(21, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 15:00:43'),
(22, 4, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-16 15:02:50'),
(23, 4, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-16 15:19:35'),
(24, 4, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-17 02:00:56'),
(25, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-17 02:01:11'),
(26, 4, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-17 09:34:55'),
(27, 4, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-17 16:35:48'),
(28, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-17 17:03:04'),
(29, 2, 'offer_accept', 'offers', 2, '{\"status\":\"pending\"}', '{\"status\":\"accepted\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-17 17:43:10'),
(30, 2, 'offer_accept', 'offers', 3, '{\"status\":\"pending\"}', '{\"status\":\"accepted\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-17 17:46:12'),
(31, 4, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-17 18:25:15'),
(32, 4, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-17 18:25:32'),
(33, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 02:21:49'),
(34, 4, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-18 03:49:33'),
(35, 2, 'property_delete', 'properties', 5, '{\"title\":\"sdfsdfsdf\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 04:32:15'),
(36, 2, 'property_delete', 'properties', 5, '{\"title\":\"sdfsdfsdf\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 04:32:19'),
(37, 2, 'property_delete', 'properties', 5, '{\"title\":\"sdfsdfsdf\",\"price\":\"12.00\",\"area_sqm\":\"123.00\",\"city\":\"Daao city\",\"province\":\"Dvao\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 04:40:02'),
(38, 2, 'offer_reject', 'offers', 1, '{\"status\":\"pending\"}', '{\"status\":\"rejected\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 04:40:29'),
(39, 2, 'property_delete', 'properties', 4, '{\"title\":\"as\",\"price\":\"12.00\",\"area_sqm\":\"123.00\",\"city\":\"Daao city\",\"province\":\"Dvao\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 04:40:39'),
(40, 2, 'property_delete', 'properties', 4, '{\"title\":\"as\",\"price\":\"12.00\",\"area_sqm\":\"123.00\",\"city\":\"Daao city\",\"province\":\"Dvao\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 04:45:31'),
(41, 2, 'property_delete', 'properties', 4, '{\"title\":\"as\",\"price\":\"12.00\",\"area_sqm\":\"123.00\",\"city\":\"Daao city\",\"province\":\"Dvao\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 04:45:34'),
(42, 2, 'property_create', 'properties', 7, NULL, '{\"title\":\"2.5 Hectare Prime Agricultural Land in Cebu with Mountain View and Water Source\",\"price\":\"1250000000\",\"area_sqm\":\"25000\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 05:03:45'),
(43, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 05:14:34'),
(44, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 05:14:40');

-- --------------------------------------------------------

--
-- Table structure for table `contracts`
--

CREATE TABLE `contracts` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `offer_id` int(11) DEFAULT NULL,
  `contract_type` enum('purchase_agreement','deed_of_sale','lease_agreement') DEFAULT 'purchase_agreement',
  `contract_amount` decimal(15,2) NOT NULL,
  `earnest_money` decimal(15,2) DEFAULT NULL,
  `status` enum('draft','pending_signatures','signed','completed','cancelled') DEFAULT 'draft',
  `contract_terms` text DEFAULT NULL,
  `closing_date` date DEFAULT NULL,
  `contract_file` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `signed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `escrow_id` int(11) DEFAULT NULL,
  `escrow_status` enum('not_required','pending','funded','completed') DEFAULT 'not_required'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `contract_id` int(11) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `status` enum('active','archived','closed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversation_participants`
--

CREATE TABLE `conversation_participants` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('buyer','seller','admin','broker') NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `counter_offers`
--

CREATE TABLE `counter_offers` (
  `id` int(11) NOT NULL,
  `original_offer_id` int(11) NOT NULL,
  `counter_amount` decimal(15,2) NOT NULL,
  `counter_terms` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `digital_signatures`
--

CREATE TABLE `digital_signatures` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `signature_type` enum('buyer','seller','witness','notary') NOT NULL,
  `signature_data` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `signed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disputes`
--

CREATE TABLE `disputes` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) DEFAULT NULL,
  `property_id` int(11) DEFAULT NULL,
  `complainant_id` int(11) NOT NULL,
  `respondent_id` int(11) NOT NULL,
  `dispute_type` enum('contract_breach','payment_issue','property_misrepresentation','fraud','other') NOT NULL,
  `status` enum('open','investigating','mediation','resolved','closed') DEFAULT 'open',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `resolution` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `escrow_accounts`
--

CREATE TABLE `escrow_accounts` (
  `id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `escrow_agent_id` int(11) DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `deposited_amount` decimal(15,2) DEFAULT 0.00,
  `released_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('pending','funded','partial_release','completed','disputed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kyc_documents`
--

CREATE TABLE `kyc_documents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_type` enum('national_id','drivers_license','passport','business_permit','tin_id','other') NOT NULL,
  `document_number` varchar(100) DEFAULT NULL,
  `front_image` varchar(500) NOT NULL,
  `back_image` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `listing_id` int(11) DEFAULT NULL,
  `parent_message_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `is_deleted_by_sender` tinyint(1) DEFAULT 0,
  `is_deleted_by_recipient` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `recipient_id`, `subject`, `message`, `listing_id`, `parent_message_id`, `is_read`, `is_deleted_by_sender`, `is_deleted_by_recipient`, `created_at`, `read_at`) VALUES
(1, 4, 2, NULL, 'asdasd\n\nContact Info:\nName: louie benduho\nEmail: sagazynnn@gmail.com\nPhone: 0912378123\n\nPreferred Contact: Email Phone Text', NULL, NULL, 1, 0, 0, '2025-10-16 15:35:43', '2025-10-16 15:40:26'),
(2, 2, 4, NULL, 'yes i do', NULL, NULL, 1, 0, 0, '2025-10-16 16:01:06', '2025-10-16 16:01:11'),
(3, 4, 2, NULL, 'ok', NULL, NULL, 1, 0, 0, '2025-10-16 16:13:45', '2025-10-16 16:13:47'),
(4, 2, 4, NULL, 'tagalaba', NULL, NULL, 1, 0, 0, '2025-10-16 16:13:58', '2025-10-16 16:14:07'),
(5, 2, 4, NULL, 'hoi', NULL, NULL, 1, 0, 0, '2025-10-16 16:14:56', '2025-10-16 16:15:05'),
(6, 4, 2, NULL, 'what', NULL, NULL, 1, 0, 0, '2025-10-16 16:15:13', '2025-10-16 16:15:15'),
(7, 4, 2, NULL, 'nice', NULL, NULL, 1, 0, 0, '2025-10-16 16:16:08', '2025-10-16 16:23:56'),
(8, 4, 2, NULL, 'err', NULL, NULL, 1, 0, 0, '2025-10-16 16:16:39', '2025-10-16 16:23:56'),
(9, 4, 2, NULL, 'xb', NULL, NULL, 1, 0, 0, '2025-10-16 16:21:03', '2025-10-16 16:23:56'),
(10, 2, 4, NULL, 'oh ano saan', NULL, NULL, 1, 0, 0, '2025-10-16 16:24:14', '2025-10-16 16:27:53'),
(11, 2, 4, NULL, 'asdasd', NULL, NULL, 1, 0, 0, '2025-10-16 17:14:11', '2025-10-16 17:14:26'),
(12, 2, 4, NULL, 'asd', NULL, NULL, 1, 0, 0, '2025-10-16 17:14:39', '2025-10-16 17:14:50'),
(13, 4, 2, NULL, 'asdasd', NULL, NULL, 1, 0, 0, '2025-10-16 17:15:06', '2025-10-16 17:15:09'),
(14, 4, 2, NULL, 'asdasd', NULL, NULL, 1, 0, 0, '2025-10-16 17:27:22', '2025-10-16 17:27:43'),
(15, 4, 2, NULL, 'asdasdasd', NULL, NULL, 1, 0, 0, '2025-10-16 18:01:10', '2025-10-16 18:01:16'),
(16, 2, 4, NULL, 'asdasd', NULL, NULL, 1, 0, 0, '2025-10-16 18:01:21', '2025-10-16 18:01:24'),
(17, 4, 2, NULL, 'ufufyg\n\nContact Info:\nName: louie benduho\nEmail: sagazynnn@gmail.com\nPhone: 0912378123\n\nPreferred Contact:', NULL, NULL, 1, 0, 0, '2025-10-17 02:03:59', '2025-10-17 04:28:28');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('offer_received','offer_accepted','offer_rejected','counter_offer','message','auction_outbid','auction_won','payment_due','document_required','kyc_approved','kyc_rejected','system') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `data`, `read_at`, `created_at`) VALUES
(1, 2, 'system', 'Welcome to TerraTrade!', 'Your account has been created successfully.', NULL, NULL, '2025-10-10 03:48:51'),
(2, 3, 'system', 'Welcome to TerraTrade!', 'Your account has been created successfully.', NULL, NULL, '2025-10-10 15:20:13'),
(3, 4, 'system', 'Welcome to TerraTrade!', 'Your account has been created successfully.', NULL, NULL, '2025-10-10 15:25:05'),
(4, 2, '', 'New Offer Received', 'You received a new offer of ₱1,243,243,255 for your property \'as\'', '{\"offer_id\":{\"queryString\":\"INSERT INTO offers (\\n        property_id, \\n        buyer_id, \\n        seller_id, \\n        offer_amount, \\n        buyer_comments, \\n        status, \\n        contingencies,\\n        created_at\\n    ) VALUES (?, ?, ?, ?, ?, \'pending\', ?, NOW())\"},\"property_id\":4,\"buyer_id\":4,\"offer_amount\":1243243255}', NULL, '2025-10-17 17:22:23'),
(5, 2, '', 'New Offer Received', 'You received a new offer of ₱1,243,243,255 for your property \'as\'', '{\"offer_id\":1,\"property_id\":4,\"buyer_id\":4,\"offer_amount\":1243243255}', NULL, '2025-10-17 17:28:36'),
(6, 2, '', 'New Offer Received', 'You received a new offer of ₱1,243,243,255 for your property \'as\'', '{\"offer_id\":1,\"property_id\":4,\"buyer_id\":4,\"offer_amount\":1243243255}', NULL, '2025-10-17 17:34:05'),
(7, 2, '', 'New Offer Received', 'You received a new offer of ₱1,342,355 for your property \'as\'', '{\"offer_id\":1,\"property_id\":4,\"buyer_id\":4,\"offer_amount\":1342355}', NULL, '2025-10-17 17:37:40'),
(8, 2, '', 'New Offer Received', 'You received a new offer of ₱2,342,343 for your property \'zdfv\'', '{\"offer_id\":\"2\",\"property_id\":3,\"buyer_id\":4,\"offer_amount\":2342343}', NULL, '2025-10-17 17:38:50'),
(9, 4, 'offer_accepted', 'Offer Accepted!', 'Your offer of ₱2,342,343 for \'zdfv\' has been accepted!', '{\"offer_id\":\"2\",\"property_id\":3}', NULL, '2025-10-17 17:43:10'),
(10, 2, '', 'New Offer Received', 'You received a new offer of ₱690,000 for your property \'asfsf\'', '{\"offer_id\":\"3\",\"property_id\":6,\"buyer_id\":4,\"offer_amount\":690000}', NULL, '2025-10-17 17:45:27'),
(11, 4, 'offer_accepted', 'Offer Accepted!', 'Your offer of ₱690,000 for \'asfsf\' has been accepted!', '{\"offer_id\":\"3\",\"property_id\":6}', NULL, '2025-10-17 17:46:12');

-- --------------------------------------------------------

--
-- Table structure for table `offers`
--

CREATE TABLE `offers` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `offer_amount` decimal(15,2) NOT NULL,
  `earnest_money` decimal(15,2) DEFAULT NULL,
  `offer_type` enum('direct','auction_bid') DEFAULT 'direct',
  `status` enum('pending','accepted','rejected','countered','withdrawn','expired') DEFAULT 'pending',
  `contingencies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contingencies`)),
  `inclusions` text DEFAULT NULL,
  `exclusions` text DEFAULT NULL,
  `special_terms` text DEFAULT NULL,
  `buyer_comments` text DEFAULT NULL,
  `closing_date` date DEFAULT NULL,
  `financing_contingency_days` int(11) DEFAULT 30,
  `survey_contingency_days` int(11) DEFAULT 10,
  `title_contingency_days` int(11) DEFAULT 15,
  `environmental_contingency_days` int(11) DEFAULT 20,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(500) NOT NULL,
  `region` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `zoning` enum('Residential','Commercial','Agricultural','Industrial','Mixed') NOT NULL,
  `area_sqm` decimal(15,2) NOT NULL,
  `area_hectares` decimal(10,4) GENERATED ALWAYS AS (`area_sqm` / 10000) STORED,
  `price` decimal(15,2) NOT NULL,
  `price_per_sqm` decimal(10,2) GENERATED ALWAYS AS (`price` / `area_sqm`) STORED,
  `listing_type` enum('sale','auction') DEFAULT 'sale',
  `type` varchar(50) DEFAULT 'sale',
  `status` enum('draft','pending','active','sold','expired','suspended') DEFAULT 'pending',
  `featured` tinyint(1) DEFAULT 0,
  `auction_start` timestamp NULL DEFAULT NULL,
  `auction_end` timestamp NULL DEFAULT NULL,
  `minimum_bid` decimal(15,2) DEFAULT NULL,
  `current_bid` decimal(15,2) DEFAULT NULL,
  `bid_increment` decimal(15,2) DEFAULT 10000.00,
  `views_count` int(11) DEFAULT 0,
  `favorites_count` int(11) DEFAULT 0,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `user_id`, `title`, `description`, `location`, `region`, `province`, `city`, `barangay`, `contact_name`, `contact_phone`, `zoning`, `area_sqm`, `price`, `listing_type`, `type`, `status`, `featured`, `auction_start`, `auction_end`, `minimum_bid`, `current_bid`, `bid_increment`, `views_count`, `favorites_count`, `latitude`, `longitude`, `created_at`, `updated_at`, `expires_at`) VALUES
(7, 2, '2.5 Hectare Prime Agricultural Land in Cebu with Mountain View and Water Source', 'Excellent agricultural land perfect for farming or agribusiness development. This 2.5-hectare property features fertile soil ideal for rice, corn, vegetables, and fruit trees. The land has a natural water source (spring) and is accessible via concrete road.\n\nKey Features:\n- Fertile, well-drained soil suitable for various crops\n- Natural spring water source on property\n- 200-meter road frontage on concrete provincial road\n- Gently sloping terrain with mountain backdrop\n- Power line available at property boundary\n- Clean land title (TCT) with no liens or encumbrances\n- Peaceful rural setting, 15 minutes from city center\n\nPerfect for:\n- Organic farming operations\n- Agritourism development\n- Residential subdivision (with proper permits)\n- Investment property with high appreciation potential\n\nThe property is located in a growing agricultural area with good market access. Ideal for serious farmers or investors looking for prime agricultural land with development potential.', 'Tokyo, Manila, Region IVX', 'Region IVX', 'Manila', 'Tokyo', 'D Makita', '0953928485', '0953928485', 'Residential', 25000.00, 1250000000.00, 'sale', 'sale', 'active', 0, NULL, NULL, NULL, NULL, 10000.00, 0, 0, NULL, NULL, '2025-10-18 05:03:45', '2025-10-18 05:03:45', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `property_documents`
--

CREATE TABLE `property_documents` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `document_type` enum('title','tax_declaration','survey_plan','deed_of_sale','other') NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_images`
--

CREATE TABLE `property_images` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `image_type` enum('main','gallery','document','map') DEFAULT 'gallery',
  `display_order` int(11) DEFAULT 0,
  `caption` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_by`, `updated_at`) VALUES
(1, 'site_name', 'TerraTrade', 'string', 'Site name', NULL, '2025-10-10 03:12:43'),
(2, 'site_email', 'admin@terratrade.com', 'string', 'System email address', NULL, '2025-10-10 03:12:43'),
(3, 'kyc_required', 'true', 'boolean', 'Require KYC verification for transactions', NULL, '2025-10-10 03:12:43'),
(4, 'max_file_size', '5242880', 'number', 'Maximum file upload size in bytes (5MB)', NULL, '2025-10-10 03:12:43'),
(5, 'auction_bid_increment', '10000', 'number', 'Default auction bid increment', NULL, '2025-10-10 03:12:43'),
(6, 'escrow_fee_percentage', '2.5', 'number', 'Escrow service fee percentage', NULL, '2025-10-10 03:12:43'),
(7, 'contract_expiry_days', '30', 'number', 'Default contract expiry in days', NULL, '2025-10-10 03:12:43');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('buyer','seller','admin','broker') DEFAULT 'buyer',
  `status` enum('active','pending','suspended','banned') DEFAULT 'pending',
  `kyc_status` enum('none','pending','verified','rejected') DEFAULT 'none',
  `profile_image` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `phone_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `full_name`, `phone`, `role`, `status`, `kyc_status`, `profile_image`, `created_at`, `updated_at`, `last_login`, `email_verified`, `phone_verified`) VALUES
(1, 'admin@terratrade.com', '$2y$10$nbC0Efx.XQEVAlaLXuEblejvvCNx.Ng4qOOnLPmRBeWufs8wW6eOq', 'System Administrator', NULL, 'admin', 'active', 'verified', NULL, '2025-10-10 03:12:43', '2025-10-10 03:27:13', NULL, 1, 0),
(2, 'louiejames094@gmail.com', '$2y$10$kXlxjYwynPwUpztgxhTvR.cXLrP5K5bm1S708CFBGsd..TctyPYxm', 'XIn', '09348723345', '', 'active', 'none', NULL, '2025-10-10 03:48:51', '2025-10-18 05:14:40', '2025-10-18 05:14:40', 1, 0),
(3, 'bendijozynnn@gmail.com', '$2y$10$oXLB2xMS.1cSbrUMeXP9COALoqTVplQuF1sMDlkvE6XN0iBuHvIHe', 'Zed', 'Contact me on telegr', '', 'active', 'none', NULL, '2025-10-10 15:20:13', '2025-10-10 15:20:21', '2025-10-10 15:20:21', 1, 0),
(4, 'sagazynnn@gmail.com', '$2y$10$NW5Qo207rSrMoz0zoOp/yeQvY7gNPgxSY4mka2sM8jJfrkgY58gY2', 'Xion', '09348723345', '', 'active', 'none', NULL, '2025-10-10 15:25:05', '2025-10-18 03:49:33', '2025-10-18 03:49:33', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_activity`
--

CREATE TABLE `user_activity` (
  `user_id` int(11) NOT NULL,
  `last_seen` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_online` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activity`
--

INSERT INTO `user_activity` (`user_id`, `last_seen`, `is_online`) VALUES
(4, '2025-10-16 17:59:18', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_favorites`
--

CREATE TABLE `user_favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_action` (`user_id`,`action`),
  ADD KEY `idx_table_record` (`table_name`,`record_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `offer_id` (`offer_id`),
  ADD KEY `escrow_id` (`escrow_id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `contract_id` (`contract_id`);

--
-- Indexes for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_participant` (`conversation_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `counter_offers`
--
ALTER TABLE `counter_offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `original_offer_id` (`original_offer_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `digital_signatures`
--
ALTER TABLE `digital_signatures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_id` (`contract_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `disputes`
--
ALTER TABLE `disputes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_id` (`contract_id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `complainant_id` (`complainant_id`),
  ADD KEY `respondent_id` (`respondent_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `escrow_accounts`
--
ALTER TABLE `escrow_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contract_id` (`contract_id`),
  ADD KEY `escrow_agent_id` (`escrow_agent_id`);

--
-- Indexes for table `kyc_documents`
--
ALTER TABLE `kyc_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender` (`sender_id`),
  ADD KEY `idx_recipient` (`recipient_id`),
  ADD KEY `idx_listing` (`listing_id`),
  ADD KEY `idx_thread` (`parent_message_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_read` (`is_read`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_read` (`user_id`,`read_at`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `offers`
--
ALTER TABLE `offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_property_status` (`property_id`,`status`),
  ADD KEY `idx_buyer` (`buyer_id`),
  ADD KEY `idx_seller` (`seller_id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_location` (`region`,`province`,`city`),
  ADD KEY `idx_zoning` (`zoning`),
  ADD KEY `idx_price` (`price`),
  ADD KEY `idx_area` (`area_sqm`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_auction_end` (`auction_end`);

--
-- Indexes for table `property_documents`
--
ALTER TABLE `property_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `property_images`
--
ALTER TABLE `property_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_activity`
--
ALTER TABLE `user_activity`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_last_seen` (`last_seen`),
  ADD KEY `idx_is_online` (`is_online`);

--
-- Indexes for table `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`user_id`,`property_id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_activity` (`user_id`,`last_activity`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `counter_offers`
--
ALTER TABLE `counter_offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `digital_signatures`
--
ALTER TABLE `digital_signatures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disputes`
--
ALTER TABLE `disputes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `escrow_accounts`
--
ALTER TABLE `escrow_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kyc_documents`
--
ALTER TABLE `kyc_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `offers`
--
ALTER TABLE `offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `property_documents`
--
ALTER TABLE `property_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `property_images`
--
ALTER TABLE `property_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_favorites`
--
ALTER TABLE `user_favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contracts`
--
ALTER TABLE `contracts`
  ADD CONSTRAINT `contracts_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contracts_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contracts_ibfk_3` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contracts_ibfk_4` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contracts_ibfk_5` FOREIGN KEY (`escrow_id`) REFERENCES `escrow_accounts` (`id`);

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD CONSTRAINT `conversation_participants_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversation_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `counter_offers`
--
ALTER TABLE `counter_offers`
  ADD CONSTRAINT `counter_offers_ibfk_1` FOREIGN KEY (`original_offer_id`) REFERENCES `offers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `counter_offers_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `digital_signatures`
--
ALTER TABLE `digital_signatures`
  ADD CONSTRAINT `digital_signatures_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `digital_signatures_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `disputes`
--
ALTER TABLE `disputes`
  ADD CONSTRAINT `disputes_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `disputes_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `disputes_ibfk_3` FOREIGN KEY (`complainant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disputes_ibfk_4` FOREIGN KEY (`respondent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disputes_ibfk_5` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `escrow_accounts`
--
ALTER TABLE `escrow_accounts`
  ADD CONSTRAINT `escrow_accounts_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `escrow_accounts_ibfk_2` FOREIGN KEY (`escrow_agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `kyc_documents`
--
ALTER TABLE `kyc_documents`
  ADD CONSTRAINT `kyc_documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kyc_documents_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`listing_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `messages_ibfk_4` FOREIGN KEY (`parent_message_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `offers`
--
ALTER TABLE `offers`
  ADD CONSTRAINT `offers_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `offers_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `offers_ibfk_3` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `properties_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_documents`
--
ALTER TABLE `property_documents`
  ADD CONSTRAINT `property_documents_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `property_documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `property_documents_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `property_images`
--
ALTER TABLE `property_images`
  ADD CONSTRAINT `property_images_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_activity`
--
ALTER TABLE `user_activity`
  ADD CONSTRAINT `user_activity_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD CONSTRAINT `user_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_favorites_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
