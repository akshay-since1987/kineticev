-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 13, 2025 at 04:21 AM
-- Server version: 10.11.14-MariaDB
-- PHP Version: 8.4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_log`
--

CREATE TABLE `admin_activity_log` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_activity_log`
--

INSERT INTO `admin_activity_log` (`id`, `username`, `action`, `ip`, `user_agent`, `created_at`) VALUES
(1, 'admin', 'Login Success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 10:44:40'),
(2, 'admin', 'Viewed Transactions Page', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 10:44:40'),
(3, 'admin', 'Viewed Transactions Page', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 10:52:51'),
(4, 'admin', 'Viewed Transactions Page', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 10:52:53'),
(5, 'admin', 'Viewed Transactions Page', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 10:54:27'),
(6, 'admin', 'Viewed Transactions Page', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 10:54:28'),
(7, 'admin', 'Viewed Transactions Page', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 10:54:28'),
(8, 'admin', 'Logout', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 10:54:33'),
(9, 'kineticev_admin', 'Login Success', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 10:54:45'),
(10, 'kineticev_admin', 'Viewed Transactions Page', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 10:54:45'),
(11, 'kineticev_admin', 'Exported Transactions as Excel', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-07-30 10:54:54');

-- --------------------------------------------------------
--
-- Database: `kineticev_web`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('super_admin','admin','viewer') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `uuid`, `username`, `password_hash`, `email`, `full_name`, `role`, `is_active`, `last_login`, `last_login_ip`, `created_at`, `updated_at`) VALUES
(1, '7d97587d-303b-4fef-b509-39c4927e90ec', 'kineticadmin', '$2y$10$nOIV1z75VyIJzSD9J1z3s.DIqjYIW3Sp1H70NDr0MmasC9sFSWGX.', 'info@kineticev.in', 'KineticEV Administrator', 'super_admin', 1, '2025-09-20 15:35:21', '127.0.0.1', '2025-08-16 12:38:23', '2025-09-20 15:35:21'),
(2, '5ec2c0e6-2cc7-4fc7-8140-5fad4c44d2d4', 'Akshay', '$2y$10$nOIV1z75VyIJzSD9J1z3s.DIqjYIW3Sp1H70NDr0MmasC9sFSWGX.', 'akshay.since1987@gmail.com', 'Akshay Khandelwal', 'admin', 1, NULL, NULL, '2025-08-16 16:13:57', '2025-09-06 12:47:31');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `product_name` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `phonepe_transaction_id` varchar(50) DEFAULT NULL,
  `phonepe_status` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- --------------------------------------------------------

--
-- Table structure for table `allowed_cities`
--

CREATE TABLE `allowed_cities` (
  `id` int(11) NOT NULL,
  `city_name` varchar(255) NOT NULL,
  `coordinates` varchar(50) NOT NULL,
  `is_allowed` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `allowed_cities`
--

INSERT INTO `allowed_cities` (`id`, `city_name`, `coordinates`, `is_allowed`, `created_at`) VALUES
(1, 'Mumbai', '19.0760,72.8777', 1, '2025-10-07 09:02:17'),
(2, 'Pune', '18.5204,73.8567', 1, '2025-10-07 09:02:17');

-- --------------------------------------------------------

--
-- Table structure for table `allowed_pincodes`
--

CREATE TABLE `allowed_pincodes` (
  `id` int(11) NOT NULL,
  `pincode` varchar(10) NOT NULL,
  `area_name` varchar(200) NOT NULL,
  `city_id` int(11) NOT NULL,
  `coordinates` varchar(50) NOT NULL COMMENT 'Format: lat,lng',
  `is_allowed` tinyint(1) DEFAULT 1,
  `is_enabled` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `phone_verified` tinyint(1) DEFAULT 0,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `help_type` enum('support','enquiry','dealership','others') NOT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `subject` varchar(255) DEFAULT NULL COMMENT 'Contact form subject/title'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `uuid`, `full_name`, `phone`, `phone_verified`, `phone_verified_at`, `email`, `help_type`, `message`, `ip_address`, `user_agent`, `created_at`, `updated_at`, `subject`) VALUES
(1, 'c0a53da6-9ef5-4863-b8ea-61b0a485e424', 'Akshay', '7506754344', 0, NULL, 'akshay@gmail.com', 'support', 'Testing the database save methods', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-05 16:14:29', '2025-08-18 23:20:11', NULL),
(2, '0dd63ef0-3b7f-4445-93ec-d6f1c3c7ef66', 'akshay', '7852085204', 0, NULL, 'aks@gmail.com', 'support', 'testing data save contact', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-05 18:04:32', '2025-08-18 23:20:11', NULL),
(3, 'f5050584-4f56-46e8-b73e-639474a0fa62', 'akshay', '7852085204', 0, NULL, 'aks@gmail.com', 'support', 'testing data save contact', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-05 18:14:15', '2025-08-18 23:20:11', NULL),
(4, '7f535fa0-5f1e-4656-93a0-b1e582b13f35', 'akshay', '412578963214', 0, NULL, 'ab@cd.ef', 'support', 'testing failed data save', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-05 18:14:57', '2025-08-18 23:20:11', NULL),
(5, '3c9ba845-4a74-4ee3-b73f-63ff10057cd2', 'Akshay TestContact', '9191919191', 0, NULL, 'akshay.since1987@gmail.com', 'support', 'test msg for checking form submission', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 21:11:41', '2025-08-18 23:20:11', NULL),
(6, 'a7c046dc-98a9-42b5-8eb7-80a128a55ccc', 'Akshay TestContactTwo', '7474747474', 0, NULL, 'akshay.since1987@gmail.com', 'enquiry', 'Enquiry test Salesforce send', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-15 21:23:30', '2025-08-18 23:20:11', NULL),
(7, 'cb3675c4-8038-4a43-8c91-570ed2914370', 'Akshay Khandelwal ContactTestoooooo', '7506754344', 0, NULL, 'akshay.since1987@gmail.com', 'support', 'Support test', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-18 21:28:48', '2025-08-18 23:20:11', NULL),
(8, 'e5027573-8141-462a-b3fe-7ac76be9a2a7', 'Akshay Khandelwal TESTContact', '7506754344', 0, NULL, 'akshay.since1987@gmail.com', 'enquiry', 'Test', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-18 22:22:22', '2025-08-18 23:20:11', NULL),
(11, '7409dffc-5b07-450e-aafa-57a3dd479022', 'UUID Test User', '9999999999', 0, NULL, 'uuid-test@example.com', 'enquiry', 'Testing UUID in email templates', '127.0.0.1', 'Test Browser', '2025-08-20 13:45:23', '2025-08-20 13:45:23', NULL),
(12, 'a43f4a10-efe9-482e-8454-231ed40a60b7', 'Email Template Test User', '9876543210', 0, NULL, 'emailtest@example.com', 'enquiry', 'Testing email template with UUID', '127.0.0.1', 'Test Browser', '2025-08-20 13:47:43', '2025-08-20 13:47:43', NULL),
(13, 'cdf07db2-c98e-4ee5-8698-e1727758f9e1', 'Email Template Test User', '9876543210', 0, NULL, 'emailtest@example.com', 'enquiry', 'Testing email template with UUID', '127.0.0.1', 'Test Browser', '2025-08-20 13:53:55', '2025-08-20 13:53:55', NULL),
(14, 'cdf5356e-24e0-42eb-a7f1-4e4bd471f102', 'akshay khandelwal', '9321666348', 0, NULL, 'akshay.since1987@gmail.com', 'enquiry', 'test', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-31 16:38:35', '2025-08-31 16:38:35', NULL),
(15, '8c948063-b2d6-451c-90ed-73531cbc8536', 'akshay khandelwal', '9321666348', 0, NULL, 'akshay.since1987@gmail.com', 'enquiry', 'test message', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-31 16:42:20', '2025-08-31 16:42:20', NULL),
(16, '329c2a75-da1f-4131-8d92-f3ee90e43cc3', 'akshay khandelwal', '7506754344', 1, NULL, 'akshay.since1987@gmail.com', 'enquiry', 'test msg', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-31 16:46:17', '2025-08-31 16:46:17', NULL),
(17, '8aed0887-6476-4dd1-9392-c99b539bab82', 'Sameer khan', '7827461623', 1, NULL, 'vikasjoshi4297@gmail.com', 'enquiry', 'test', '103.159.183.58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-11 11:50:12', '2025-09-11 11:50:12', NULL),
(18, '344a1952-07df-4fab-8536-67a491483448', 'Rohan Singh', '8987876765', 1, NULL, 'test022@gmail.com', 'enquiry', '', '103.159.183.58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-11 15:57:30', '2025-09-11 15:57:30', NULL),
(19, 'd10852be-2a5b-4204-a6d7-93929b0bed11', 'Shan kumar', '8767363536', 1, NULL, 'Test@gmail.com', 'dealership', '', '103.159.183.58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-11 15:58:43', '2025-09-11 15:58:43', NULL),
(20, 'dc77843d-dfb9-4d27-aa6d-d6b7e70a431a', 'Check for booking', '7876745312', 1, NULL, 'Test@gmail.com', 'enquiry', '', '103.159.183.58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-11 16:32:42', '2025-09-11 16:32:42', NULL),
(21, '78a4892e-d7f7-4ce8-b7ec-a957579a902c', 'Check for Dealers', '7383848382', 0, NULL, 'Test@gmail.com', 'dealership', '', '103.159.183.58', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-11 16:33:52', '2025-09-11 16:33:52', NULL),
(22, '3052cb96-2352-4a4c-a68c-2aace88eb47d', 'Ram Kapoor', '8879908086', 1, NULL, 'namrata.vallakati@crmlanding.in', 'enquiry', 'test booking enquiry', '111.125.219.96', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-11 16:40:28', '2025-09-11 16:40:28', NULL),
(23, '46158590-c5ac-478e-9737-7fb6a38077e1', 'Deepak Gupta', '8879908086', 1, NULL, 'namrata.vallakati@crmlanding.in', 'dealership', 'test dealership enquiry', '111.125.219.96', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-11 16:58:02', '2025-09-11 16:58:02', NULL),
(24, '956a843a-6740-4372-a19f-f125f0b20778', 'Web to lead', '8879908086', 1, NULL, 'namrata.vallakati18@gmail.com', 'enquiry', 'web to lead booking enquiry', '111.125.219.96', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-11 18:59:05', '2025-09-11 18:59:05', NULL),
(25, 'e799b585-00dc-4253-964f-17c95bb4a4e2', 'Test Contact Enquiry', '9876543212', 0, NULL, 'testenquiry@salesforcetest.com', 'enquiry', 'Testing contact form with enquiry type - should be sent to Salesforce', '192.168.1.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-IN) WindowsPowerShell/5.1.19041.6328', '2025-09-11 23:01:34', '2025-09-11 23:01:34', NULL),
(26, 'fac1cd15-d59f-4da9-858a-da9aa9b4dc7c', 'Test Contact Enquiry', '9876543212', 0, NULL, 'testenquiry@salesforcetest.com', 'enquiry', 'Testing contact form with enquiry type - should be sent to Salesforce', '192.168.1.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-IN) WindowsPowerShell/5.1.19041.6328', '2025-09-11 23:02:20', '2025-09-11 23:02:20', NULL),
(27, 'e76881a3-2ddc-44eb-b736-3fab53a8942c', 'Test Contact Support', '9876543213', 0, NULL, 'testsupport@salesforcetest.com', 'support', 'Testing contact form with support type - should be sent to Salesforce', '192.168.1.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-IN) WindowsPowerShell/5.1.19041.6328', '2025-09-11 23:03:04', '2025-09-11 23:03:04', NULL),
(28, '5cb20f72-c02e-4ed7-92dc-94ba23129011', 'Test Contact Others', '9876543214', 0, NULL, 'testothers@salesforcetest.com', 'others', 'Testing contact form with others type - should NOT be sent to Salesforce', '192.168.1.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-IN) WindowsPowerShell/5.1.19041.6328', '2025-09-11 23:03:26', '2025-09-11 23:03:26', NULL),
(29, 'b59ad2b7-9626-42a6-9ed3-68db82bcd80e', 'RejectedTestRide Akshay', '7506754344', 0, NULL, 'testride@akshay.com', 'support', 'Test Rejection Salesforce', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-11 23:49:54', '2025-09-11 23:49:54', NULL),
(30, '6088840e-5eb3-4b57-9716-fdeed1091194', 'TestBookingEnquiry Akshay', '7506754344', 1, NULL, 'testridebookinenquiry@akshay.com', 'enquiry', 'test ride booking enquiry', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-11 23:50:50', '2025-09-11 23:50:50', NULL),
(31, 'f649804d-876c-4af4-b219-d89ae44e597d', 'testridedealershipenq Akshay', '7506754344', 1, NULL, 'testridedealershipenq@akshay.com', 'dealership', 'test ride dealership enquiry', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-11 23:51:44', '2025-09-11 23:51:44', NULL),
(32, '2074aeb6-8c7b-4331-8ea0-6a11ab59d670', 'testriderejectothers Akshay', '7506754344', 1, NULL, 'testriderejectothers@akshay.com', 'others', 'test ride reject others', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-11 23:52:23', '2025-09-11 23:52:23', NULL),
(33, 'c67ce4e3-b270-4075-9cf5-ade7b8072579', 'Namrata booking enquiry', '8879908086', 1, NULL, 'namrata.vallakati@crmlanding.in', 'enquiry', 'test booking enquiry', '111.125.219.96', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-12 11:53:52', '2025-09-12 11:53:52', NULL),
(34, 'd31ae62a-4a1d-4e5a-bf81-8ff70afa31af', 'dealership test test', '8879908086', 1, NULL, 'namrata.vallakati@crmlanding.in', 'dealership', 'testing of contact us dealership enquiry', '111.125.219.96', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-12 11:59:29', '2025-09-12 11:59:29', NULL),
(35, '33284ea0-de91-41f2-ba29-1dd22383830f', 'contact us booking enquiry', '8879908086', 1, NULL, 'namrata.vallakati@crmlanding.in', 'enquiry', 'test BE', '111.125.219.96', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-13 12:41:11', '2025-09-13 12:41:11', NULL),
(36, '1b487c5a-d085-4e4a-a815-a517cf8ac720', 'dealership enquiry', '8879908086', 1, NULL, 'namrata.vallakati@crmlanding.in', 'dealership', 'tst', '111.125.219.96', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-13 12:44:26', '2025-09-13 12:44:26', NULL),
(37, '56607365-3370-45f6-9992-6313ea93b533', 'Test User Support', '9876543210', 1, NULL, 'test.support@example.com', 'support', 'Testing support form submission', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-IN) WindowsPowerShell/5.1.19041.6328', '2025-09-13 12:48:41', '2025-09-13 12:48:41', NULL),
(38, 'f49506b0-3863-4137-9cc1-68eebe5a85dd', 'Test User Support', '9876543210', 1, NULL, 'test.support@example.com', 'support', 'Testing support form submission for contact logging', '::1', '', '2025-09-13 12:57:55', '2025-09-13 12:57:55', NULL),
(39, 'd672b128-2f58-4df2-97eb-c57304441ecf', 'Test User Enquiry', '8765432109', 1, NULL, 'test.enquiry@example.com', 'enquiry', 'Testing enquiry form submission for contact logging', '::1', '', '2025-09-13 12:57:57', '2025-09-13 12:57:57', NULL),
(40, 'a1e598a1-9f84-40c6-b50d-2ff49b969287', 'Test User Dealership', '7654321098', 1, NULL, 'test.dealership@example.com', 'dealership', 'Testing dealership form submission for contact logging', '::1', '', '2025-09-13 12:58:00', '2025-09-13 12:58:00', NULL),
(41, '500b97ec-aebd-4834-b1a1-45590c76202a', 'Test User Others', '6543210987', 1, NULL, 'test.others@example.com', 'others', 'Testing others form submission for contact logging', '::1', '', '2025-09-13 12:58:02', '2025-09-13 12:58:02', NULL),
(42, '1e1cf4c7-ce0d-498e-a6f7-e1bb6cb2e1b7', 'Test careers Akshay', '7506754344', 1, NULL, 'akshay.since1987@gmail.com', '', 'Test careers Development Env', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 15:38:18', '2025-09-16 15:38:18', NULL),
(43, '8c8c9354-c5f0-4485-a234-aa8ee834cf2b', 'akshay careers test', '7506754344', 1, NULL, 'akshay.since1987@gmail.com', '', 'Careers enquiry', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 16:00:50', '2025-09-16 16:00:50', NULL),
(44, '33526356-af84-431c-aad2-1d3da4ca6adc', 'Contact Akshay', '7506754344', 1, NULL, 'support@testcnotact.in', 'support', 'test support send', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-17 12:18:57', '2025-09-17 12:18:57', NULL),
(45, '1fea13be-834b-4ee3-b827-b2d4de39d736', 'Booking Test Akshay', '7506765434', 1, NULL, 'booking@testcontact.in', 'enquiry', 'Testi Booking Enbquiry', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-17 12:19:37', '2025-09-17 12:19:37', NULL),
(46, '32ed9f4d-1976-4ac3-bd0c-4fbdf0326c45', 'booking test', '7567543444', 1, NULL, 'booking@enquirytest.in', 'enquiry', 'Test booking enquiry', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-17 12:22:22', '2025-09-17 12:22:22', NULL),
(47, '1cea66be-4652-463c-bf78-7b01c28deef3', 'DealershipEnq Test', '7506754344', 1, NULL, 'dealership@testenq.in', 'dealership', 'Test dealership', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-17 12:23:58', '2025-09-17 12:23:58', NULL);
(48, 'fccc68f1-8335-49ce-9e8e-a1c5b3be5895', 'anup kharote', '9970720173', 'anup.kharote1111@gmail.com', NULL, 'dealership', '', '2401:4900:5303:db1f:df47:8350:5106:cc5c', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '2025-10-08 19:43:24', '2025-10-08 19:43:24', '1', NULL),
(49, '000cf071-0a0b-4119-9f2a-344e722028e9', 'RONAK KEJRIWAL', '9679966363', 'kejriwalronak29@gmail.com', NULL, 'dealership', 'I want to get associated with the company and was looking if any dealership opportunity is available in West Bengal.', '103.112.106.64', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-09 19:03:37', '2025-10-09 19:03:37', '1', NULL),
(50, '97d44218-1760-4ece-9306-5439872e0c22', 'P CHANDRUDU', '9550659565', 'pchandrudu@gmail.com', NULL, 'dealership', 'Kindly Contact us for dealership', '2405:201:c038:7944:2661:8962:436b:b65b', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36', '2025-10-10 07:54:19', '2025-10-10 07:54:19', '1', NULL),
(51, 'f0f4663a-89b2-4457-b136-ff81b37f6261', 'jitender pal', '9990644007', 'jitenderdhankhar76@gmail.com', NULL, 'enquiry', 'Delhi me kab booking start ho rahi h', '103.70.43.89', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36', '2025-10-11 20:11:42', '2025-10-11 20:11:42', '1', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `email_notifications`
--

CREATE TABLE `email_notifications` (
  `id` int(11) NOT NULL,
  `transaction_id` varchar(50) NOT NULL,
  `status` enum('success','failure','pending') NOT NULL,
  `email_type` enum('admin','customer','both') NOT NULL DEFAULT 'both',
  `recipients` text DEFAULT NULL COMMENT 'JSON array of email addresses',
  `sent_at` timestamp NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Track email notifications to prevent duplicates';

--
-- Dumping data for table `email_notifications`
--

INSERT INTO `email_notifications` (`id`, `transaction_id`, `status`, `email_type`, `recipients`, `sent_at`, `created_at`) VALUES
(1, '1759814932373199', 'success', 'both', '{\"admin\":\"info@kineticev.in\",\"customer\":\"SHODHANAMIN@GMAIL.COM\"}', '2025-10-07 05:34:03', '2025-10-07 05:34:03'),
(2, '1759816850517545', 'success', 'both', '{\"admin\":\"info@kineticev.in\",\"customer\":\"pu211198@gmail.com\"}', '2025-10-07 06:02:34', '2025-10-07 06:02:34'),
(3, '1759827727644500', 'failure', 'both', '{\"admin\":\"info@kineticev.in\",\"customer\":\"akshay.since1987@gmail.com\"}', '2025-10-07 09:03:52', '2025-10-07 09:03:52'),
(4, '1760034245717324', 'success', 'both', '{\"admin\":\"info@kineticev.in\",\"customer\":\"gourang4050@gmail.com\"}', '2025-10-09 18:28:04', '2025-10-09 18:28:04'),
(5, '1760184429316139', 'success', 'both', '{\"admin\":\"info@kineticev.in\",\"customer\":\"adiljdesai@gmail.com\"}', '2025-10-11 12:09:48', '2025-10-11 12:09:48');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(11) NOT NULL,
  `migration_name` varchar(255) NOT NULL,
  `executed_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration_name`, `executed_at`) VALUES
(2, 'add_uuid_to_admin_users', '2025-10-06 21:05:30'),
(3, 'add_uuid_to_contacts', '2025-10-06 21:05:47'),
(4, 'add_uuid_to_test_drives', '2025-10-06 21:05:47'),
(5, 'add_phone_verified_to_contacts', '2025-10-06 21:05:47'),
(6, 'add_dealership_to_help_type_enum_20250902', '2025-10-06 21:05:47'),
(7, 'add_status_column_to_test_drives_20250904', '2025-10-06 21:05:47'),
(8, 'add_subject_column_to_contacts_20250904', '2025-10-06 21:05:47');

-- --------------------------------------------------------

--
-- Table structure for table `otp_verifications`
--

CREATE TABLE `otp_verifications` (
  `id` int(11) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `purpose` enum('contact_form','test_ride','booking_form') NOT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `verified_at` timestamp NULL DEFAULT NULL,
  `attempts` int(11) DEFAULT 0,
  `max_attempts` int(11) DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_verifications`
--

INSERT INTO `otp_verifications` (`id`, `phone`, `otp`, `purpose`, `verified`, `expires_at`, `created_at`, `verified_at`, `attempts`, `max_attempts`) VALUES
(1, '+917506754344', '660812', 'test_ride', 1, '2025-10-06 21:10:47', '2025-10-06 21:05:47', '2025-10-06 21:05:59', 0, 3),
(2, '+919898042191', '359730', 'booking_form', 1, '2025-10-07 05:34:36', '2025-10-07 05:29:36', '2025-10-07 05:29:55', 0, 3),
(4, '+919579891711', '543459', 'booking_form', 1, '2025-10-07 06:06:00', '2025-10-07 06:01:00', '2025-10-07 06:01:13', 0, 3),
(5, '+919987925285', '805969', 'booking_form', 1, '2025-10-07 08:45:52', '2025-10-07 08:40:52', '2025-10-07 08:41:10', 0, 3),
(6, '+917506754344', '663814', 'booking_form', 1, '2025-10-07 09:07:29', '2025-10-07 09:02:29', '2025-10-07 09:02:44', 0, 3),
(7, '+919987925285', '295978', 'test_ride', 1, '2025-10-07 10:17:57', '2025-10-07 10:12:57', '2025-10-07 10:13:41', 0, 3),
(8, '+917387706848', '733629', 'test_ride', 1, '2025-10-07 15:36:24', '2025-10-07 15:31:24', '2025-10-07 15:31:41', 0, 3),
(9, '+919725615494', '954211', 'booking_form', 1, '2025-10-07 15:42:38', '2025-10-07 15:37:38', '2025-10-07 15:38:14', 0, 3),
(13, '+917276751555', '983440', 'booking_form', 1, '2025-10-07 19:56:52', '2025-10-07 19:51:52', '2025-10-07 19:52:21', 0, 3),
(14, '+919099938981', '502872', 'test_ride', 1, '2025-10-08 02:15:51', '2025-10-08 02:10:51', '2025-10-08 02:10:59', 0, 3),
(15, '+919916030126', '382334', 'booking_form', 1, '2025-10-08 09:07:58', '2025-10-08 09:02:58', '2025-10-08 09:03:21', 0, 3),
(16, '+919845195414', '914543', 'test_ride', 1, '2025-10-08 12:41:26', '2025-10-08 12:36:26', '2025-10-08 12:36:41', 0, 3),
(17, '+918149920000', '928524', 'booking_form', 1, '2025-10-08 14:02:27', '2025-10-08 13:57:27', '2025-10-08 13:57:42', 0, 3),
(18, '+919970720173', '960542', 'booking_form', 1, '2025-10-08 14:16:23', '2025-10-08 14:11:23', '2025-10-08 14:11:44', 0, 3),
(19, '+919970720173', '413135', 'contact_form', 1, '2025-10-08 14:18:01', '2025-10-08 14:13:01', '2025-10-08 14:13:17', 0, 3),
(20, '+919922441166', '342574', 'booking_form', 1, '2025-10-08 20:07:01', '2025-10-08 20:02:01', '2025-10-08 20:02:25', 0, 3),
(21, '+919998003722', '342971', 'test_ride', 1, '2025-10-09 04:47:50', '2025-10-09 04:42:50', '2025-10-09 04:43:07', 0, 3),
(22, '+919449927906', '853596', 'test_ride', 0, '2025-10-09 08:03:55', '2025-10-09 07:58:55', NULL, 0, 3),
(23, '+919686755723', '263857', 'test_ride', 1, '2025-10-09 08:04:33', '2025-10-09 07:59:33', '2025-10-09 08:00:00', 0, 3),
(24, '+919987813196', '786651', 'test_ride', 1, '2025-10-09 11:02:21', '2025-10-09 10:57:21', '2025-10-09 10:57:37', 0, 3),
(25, '+919679966363', '523408', 'contact_form', 1, '2025-10-09 13:37:11', '2025-10-09 13:32:11', '2025-10-09 13:32:25', 1, 3),
(26, '+919881810606', '735134', 'booking_form', 1, '2025-10-09 18:29:14', '2025-10-09 18:24:14', '2025-10-09 18:24:44', 0, 3),
(27, '+919550659565', '247720', 'contact_form', 1, '2025-10-10 02:28:00', '2025-10-10 02:23:00', '2025-10-10 02:23:16', 0, 3),
(28, '+919550659565', '369792', 'booking_form', 1, '2025-10-10 02:32:11', '2025-10-10 02:27:11', '2025-10-10 02:27:25', 0, 3),
(29, '+919845973001', '281176', 'test_ride', 0, '2025-10-10 04:26:35', '2025-10-10 04:21:35', NULL, 0, 3),
(30, '+919198450897', '211337', 'test_ride', 0, '2025-10-10 04:26:46', '2025-10-10 04:21:46', NULL, 0, 3),
(31, '+919845089739', '988526', 'test_ride', 0, '2025-10-10 04:26:46', '2025-10-10 04:21:46', NULL, 0, 3),
(32, '+911984508973', '206866', 'test_ride', 0, '2025-10-10 04:26:46', '2025-10-10 04:21:46', NULL, 0, 3),
(33, '+919493611345', '696409', 'booking_form', 1, '2025-10-10 13:07:06', '2025-10-10 13:02:06', '2025-10-10 13:02:31', 0, 3),
(34, '+917039183625', '615385', 'test_ride', 0, '2025-10-10 13:18:32', '2025-10-10 13:13:32', NULL, 0, 3),
(35, '+917788889900', '910225', 'test_ride', 1, '2025-10-11 03:25:21', '2025-10-11 03:20:21', '2025-10-11 03:20:58', 0, 3),
(36, '+917788889900', '726156', 'booking_form', 1, '2025-10-11 03:37:39', '2025-10-11 03:32:39', '2025-10-11 03:33:15', 0, 3),
(37, '+919769652565', '141628', 'booking_form', 1, '2025-10-11 05:02:13', '2025-10-11 04:57:13', '2025-10-11 04:57:30', 0, 3),
(38, '+919615343333', '834250', 'test_ride', 1, '2025-10-11 11:09:50', '2025-10-11 11:04:50', '2025-10-11 11:05:01', 0, 3),
(39, '+919823569352', '527003', 'booking_form', 1, '2025-10-11 12:12:18', '2025-10-11 12:07:18', '2025-10-11 12:07:33', 0, 3),
(40, '+919990644007', '639587', 'booking_form', 1, '2025-10-11 14:42:57', '2025-10-11 14:37:57', '2025-10-11 14:38:25', 0, 3),
(41, '+919967900756', '704071', 'booking_form', 1, '2025-10-11 20:02:21', '2025-10-11 19:57:21', '2025-10-11 19:58:02', 0, 3),
(42, '+916305902905', '445510', 'booking_form', 1, '2025-10-12 04:21:57', '2025-10-12 04:16:57', '2025-10-12 04:17:51', 0, 3),
(43, '+916305902905', '789202', 'booking_form', 0, '2025-10-12 04:29:39', '2025-10-12 04:24:39', NULL, 0, 3),
(44, '+916305902905', '117055', 'booking_form', 0, '2025-10-12 04:37:58', '2025-10-12 04:32:58', NULL, 0, 3),
(45, '+918484957004', '296152', 'test_ride', 0, '2025-10-12 13:42:52', '2025-10-12 13:37:52', NULL, 0, 3),
(46, '+917378411292', '279271', 'test_ride', 1, '2025-10-12 13:42:59', '2025-10-12 13:37:59', '2025-10-12 13:39:45', 2, 3),
(47, '+916597264314', '468728', 'booking_form', 0, '2025-10-13 03:30:41', '2025-10-13 03:25:41', NULL, 0, 3),
(48, '+919186829933', '573145', 'booking_form', 0, '2025-10-13 03:31:27', '2025-10-13 03:26:27', NULL, 0, 3),
(49, '+911868299330', '481743', 'booking_form', 0, '2025-10-13 03:31:33', '2025-10-13 03:26:33', NULL, 0, 3),
(50, '+918682993309', '936771', 'booking_form', 1, '2025-10-13 03:31:48', '2025-10-13 03:26:48', '2025-10-13 03:27:25', 0, 3);

-- --------------------------------------------------------

--
-- Table structure for table `salesforce_submissions`
--

CREATE TABLE `salesforce_submissions` (
  `id` int(11) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `form_type` enum('book_now','test_ride','contact') NOT NULL,
  `help_type` varchar(50) DEFAULT NULL COMMENT 'Contact form help type if applicable',
  `submission_type` enum('success','pending','failed') NOT NULL DEFAULT 'success',
  `customer_email` varchar(255) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `salesforce_response` text DEFAULT NULL COMMENT 'Store Salesforce API response',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `salesforce_submissions`
--

INSERT INTO `salesforce_submissions` (`id`, `transaction_id`, `form_type`, `help_type`, `submission_type`, `customer_email`, `customer_phone`, `salesforce_response`, `submitted_at`) VALUES
(1, '1759814932373199', 'book_now', NULL, 'success', 'SHODHANAMIN@GMAIL.COM', '9898042191', '{\"success\":true,\"http_code\":200,\"response_body\":\"\",\"response_time_ms\":925.05999999999994543031789362430572509765625,\"submitted_data\":{\"oid\":\"00DC1000002crkB\",\"recordType\":\"012C1000000uk1l\",\"status\":\"Lead Created\",\"lead_source\":\"Website\",\"encoding\":\"UTF-8\",\"first_name\":\"SHODHAN\",\"last_name\":\" MINESH AMIN\",\"email\":\"SHODHANAMIN@GMAIL.COM\",\"phone\":\"9898042191\"}}', '2025-10-07 11:04:02'),
(2, '1759816850517545', 'book_now', NULL, 'success', 'pu211198@gmail.com', '9579891711', '{\"success\":true,\"http_code\":200,\"response_body\":\"\",\"response_time_ms\":754.1100000000000136424205265939235687255859375,\"submitted_data\":{\"oid\":\"00DC1000002crkB\",\"recordType\":\"012C1000000uk1l\",\"status\":\"Lead Created\",\"lead_source\":\"Website\",\"encoding\":\"UTF-8\",\"first_name\":\"Pranita\",\"last_name\":\"Kulkarni\",\"email\":\"pu211198@gmail.com\",\"phone\":\"9579891711\"}}', '2025-10-07 11:32:34'),
(3, '1759827727644500', 'book_now', NULL, 'failed', 'akshay.since1987@gmail.com', '7506754344', '{\"success\":true,\"http_code\":200,\"response_body\":\"\",\"response_time_ms\":781.990000000000009094947017729282379150390625,\"submitted_data\":{\"oid\":\"00DC1000002crkB\",\"recordType\":\"012C1000000uk1l\",\"status\":\"Lead Created\",\"lead_source\":\"Website\",\"encoding\":\"UTF-8\",\"first_name\":\"Akshay\",\"last_name\":\"Test BookFailPin\",\"email\":\"akshay.since1987@gmail.com\",\"phone\":\"7506754344\"}}', '2025-10-07 14:33:53'),
(4, '1760034245717324', 'book_now', NULL, 'success', 'gourang4050@gmail.com', '9881810606', '{\"success\":true,\"http_code\":200,\"response_body\":\"\",\"response_time_ms\":767.779999999999972715158946812152862548828125,\"submitted_data\":{\"oid\":\"00DC1000002crkB\",\"recordType\":\"012C1000000uk1l\",\"status\":\"Lead Created\",\"lead_source\":\"Website\",\"encoding\":\"UTF-8\",\"first_name\":\"Gaurang\",\"last_name\":\"Kunkolienkar\",\"email\":\"gourang4050@gmail.com\",\"phone\":\"9881810606\"}}', '2025-10-09 23:58:02'),
(5, '1760184429316139', 'book_now', NULL, 'success', 'adiljdesai@gmail.com', '9823569352', '{\"success\":true,\"http_code\":200,\"response_body\":\"\",\"response_time_ms\":813.6200000000000045474735088646411895751953125,\"submitted_data\":{\"oid\":\"00DC1000002crkB\",\"recordType\":\"012C1000000uk1l\",\"status\":\"Lead Created\",\"lead_source\":\"Website\",\"encoding\":\"UTF-8\",\"first_name\":\"Adil\",\"last_name\":\"Desai\",\"email\":\"adiljdesai@gmail.com\",\"phone\":\"9823569352\"}}', '2025-10-11 17:39:47');

-- --------------------------------------------------------

--
-- Table structure for table `test_drives`
--

CREATE TABLE `test_drives` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `phone_verified` tinyint(1) DEFAULT 0,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `date` date DEFAULT NULL,
  `pincode` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` varchar(50) DEFAULT 'pending' COMMENT 'Test drive request status',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `test_drives`
--

INSERT INTO `test_drives` (`id`, `uuid`, `full_name`, `phone`, `phone_verified`, `phone_verified_at`, `date`, `pincode`, `message`, `email`, `status`, `created_at`, `updated_at`) VALUES
(1, 'a016d8b9-ed15-4e8d-b1b2-c823951b2adb', 'akshay', '7506754344', 0, NULL, NULL, '410210', 'Testing Test ride post migration to GD', 'akshay.since1987@gmail.com', 'pending', '2025-10-07 02:36:53', '2025-10-07 02:36:53'),
(2, '09060ad3-6f0b-4189-8a95-0fd73d0769aa', 'Yash Paradkar Test', '9987925285', 0, NULL, NULL, '411014', 'Test Form', 'yash.paradkar@digitalf5.com', 'pending', '2025-10-07 15:44:04', '2025-10-07 15:44:04'),
(3, 'ab75447e-10a1-4c66-adbe-420cf05266b1', 'Gaurav', '7387706848', 0, NULL, NULL, '422101', '', 'gaurav.thakare20@gmail.com', 'pending', '2025-10-07 21:01:53', '2025-10-07 21:01:53'),
(4, 'c0d67377-3847-464b-99f6-96586ef79891', 'Bhagyesh Brahmbhatt', '9099938981', 0, NULL, NULL, '390020', 'please provide test ride', 'bhagyesh8585@gmail.com', 'pending', '2025-10-08 07:41:30', '2025-10-08 07:41:30'),
(5, 'f82536b5-6d9c-4385-9669-5097f678d039', 'deepak kumar', '9845195414', 0, NULL, NULL, '560004', 'looking forward the gangster ride.......', 'fragranceindia@yahoo.co.in', 'pending', '2025-10-08 18:07:32', '2025-10-08 18:07:32'),
(6, '88797e13-cf74-40e4-97b3-6ea58f34c61b', 'mehul thakkar', '9998003722', 0, NULL, NULL, '390011', 'inquiry about kinetic dx plus', 'mehul_4443@yahoo.com', 'pending', '2025-10-09 10:14:02', '2025-10-09 10:14:02'),
(7, '5fcacf68-6678-4fa8-b227-69ba9140dcc7', 'Sanjay', '9686755723', 0, NULL, NULL, '576101', 'Nearest Location for Test Drive', 'sanjaysudp@gmail.com', 'pending', '2025-10-09 13:30:29', '2025-10-09 13:30:29'),
(8, '5f1b4bb5-d2fc-49e0-ae00-64bc79aba0fd', 'Nikhil Mahajan', '9987813196', 0, NULL, NULL, '411033', 'Test drive at home available ? \r\nLife Republic Township, marunji,Pune', 'nikhilmahajan1906@gmail.com', 'pending', '2025-10-09 16:28:34', '2025-10-09 16:28:34'),
(9, 'f790d417-d6e8-4485-b305-66981db9034c', 'SEPTEMBER', '7788889900', 0, NULL, NULL, '416416', '', 'write2axis@gmail.com', 'pending', '2025-10-11 08:51:05', '2025-10-11 08:51:05'),
(10, 'e24979d5-b5cd-4302-8247-770a1be09659', 'AMRUT ASHTEKAR', '9615343333', 0, NULL, NULL, '411038', '', 'amrut.ashtekar@hotmail.com', 'pending', '2025-10-11 16:35:19', '2025-10-11 16:35:19'),
(11, 'a28b8cf2-4702-4d4e-bdc5-d1550e001e66', 'Rohit Chodankar', '7378411292', 0, NULL, NULL, '403708', 'i wld like to test drive kinetic dx+ variant. Let me knw whether test drive facility is available in South Goa', 'ronit.chodankar@gmail.com', 'pending', '2025-10-12 19:11:04', '2025-10-12 19:11:04');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `ownedBefore` tinyint(1) DEFAULT NULL,
  `variant` varchar(50) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `terms` tinyint(1) DEFAULT NULL,
  `productinfo` varchar(255) DEFAULT NULL,
  `merchant_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(50) NOT NULL,
  `payment_details` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `transaction_id`, `firstname`, `phone`, `email`, `address`, `city`, `state`, `pincode`, `ownedBefore`, `variant`, `color`, `terms`, `productinfo`, `merchant_id`, `amount`, `status`, `payment_details`, `created_at`, `updated_at`) VALUES
(1, '1759814932373199', 'SHODHAN  MINESH AMIN', '9898042191', 'SHODHANAMIN@GMAIL.COM', '11,AMIN SOCIETY,NR SARDAR PATEL COLONY,NARANPURA,AHMEDABAD', 'Ahmedabad', 'Gujarat', '380013', 1, 'dx-plus', 'blue', 1, 'dx-plus', '1759814932373199', 1000.00, 'COMPLETED', '{\"orderId\":\"OMO2510071101547559004941\",\"state\":\"COMPLETED\",\"amount\":100000,\"payableAmount\":100000,\"feeAmount\":0,\"expireAt\":1759816014755,\"metaInfo\":{\"udf1\":\"additional-information-1\",\"udf2\":\"additional-information-2\",\"udf3\":\"additional-information-3\",\"udf4\":\"additional-information-4\",\"udf5\":\"additional-information-5\"},\"paymentDetails\":[{\"transactionId\":\"OM2510071102192759007862\",\"paymentMode\":\"NET_BANKING\",\"timestamp\":1759815139328,\"amount\":100000,\"payableAmount\":100000,\"feeAmount\":0,\"state\":\"COMPLETED\",\"instrument\":{\"type\":\"NET_BANKING\",\"bankId\":\"KKBK\"},\"rail\":{\"type\":\"PG\"},\"splitInstruments\":[{\"instrument\":{\"type\":\"NET_BANKING\",\"bankId\":\"KKBK\"},\"rail\":{\"type\":\"PG\"},\"amount\":100000}]}]}', '2025-10-07 11:01:54', '2025-10-07 11:04:01'),
(2, '1759816850517545', 'Pranita Kulkarni', '9579891711', 'pu211198@gmail.com', 'Chinchwad Pune', 'Chinchwad', 'Maharashtra', '411019', 0, 'dx-plus', 'black', 1, 'dx-plus', '1759816850517545', 1000.00, 'COMPLETED', '{\"orderId\":\"OMO2510071131484588779882\",\"state\":\"COMPLETED\",\"amount\":100000,\"payableAmount\":100000,\"feeAmount\":0,\"expireAt\":1759817808458,\"metaInfo\":{\"udf1\":\"additional-information-1\",\"udf2\":\"additional-information-2\",\"udf3\":\"additional-information-3\",\"udf4\":\"additional-information-4\",\"udf5\":\"additional-information-5\"},\"paymentDetails\":[{\"transactionId\":\"OM2510071131599027230893\",\"paymentMode\":\"UPI_QR\",\"timestamp\":1759816919931,\"amount\":100000,\"payableAmount\":100000,\"feeAmount\":0,\"state\":\"COMPLETED\",\"instrument\":{\"type\":\"ACCOUNT\",\"maskedAccountNumber\":\"XXXXXX0304\",\"ifsc\":\"SBIN0014578\",\"accountType\":\"SAVINGS\",\"bankId\":\"SBIN\"},\"rail\":{\"type\":\"UPI\",\"utr\":\"797755412735\",\"upiTransactionId\":\"YBL3a02d80f2ae74f1f839c39b1f1e9b9ff\",\"vpa\":\"prXXXXXXXX12@ybl\"},\"splitInstruments\":[{\"instrument\":{\"type\":\"ACCOUNT\",\"maskedAccountNumber\":\"XXXXXX0304\",\"ifsc\":\"SBIN0014578\",\"accountType\":\"SAVINGS\",\"bankId\":\"SBIN\"},\"rail\":{\"type\":\"UPI\",\"utr\":\"797755412735\",\"upiTransactionId\":\"YBL3a02d80f2ae74f1f839c39b1f1e9b9ff\",\"vpa\":\"prXXXXXXXX12@ybl\"},\"amount\":100000}]}]}', '2025-10-07 11:31:48', '2025-10-07 11:32:33'),
(3, '1759827727644500', 'Akshay Test BookFailPin', '7506754344', 'akshay.since1987@gmail.com', 'Test address', 'Dapodi', 'Maharashtra', '411012', 0, 'dx-plus', 'white', 1, 'dx-plus', '1759827727644500', 1000.00, 'FAILED', '{\"orderId\":\"OMO2510071433339731693869\",\"state\":\"FAILED\",\"amount\":100000,\"expireAt\":1759828713973,\"errorCode\":\"TXN_CANCELLED\",\"detailedErrorCode\":\"REQUEST_CANCEL_BY_REQUESTEE\",\"metaInfo\":{\"udf1\":\"additional-information-1\",\"udf2\":\"additional-information-2\",\"udf3\":\"additional-information-3\",\"udf4\":\"additional-information-4\",\"udf5\":\"additional-information-5\"},\"paymentDetails\":[{\"transactionId\":\"OM2510071433368033036875\",\"paymentMode\":\"UPI_QR\",\"timestamp\":1759827816836,\"amount\":100000,\"payableAmount\":100000,\"feeAmount\":0,\"state\":\"FAILED\",\"errorCode\":\"TXN_CANCELLED\",\"detailedErrorCode\":\"REQUEST_CANCEL_BY_REQUESTEE\"}]}', '2025-10-07 14:33:33', '2025-10-07 14:33:52'),
(4, '1759866678447545', 'Pruthviraj Chikhale ', '7276751555', 'pruthvirajchikhale@rediffmail.com', 'Rambag colony kothroud pune', 'Pune', 'Maharashtra', '411038', 1, 'dx-plus', 'white', 1, 'dx-plus', '1759866678447545', 1000.00, 'PENDING', NULL, '2025-10-08 01:24:51', NULL),
(5, '1760034245717324', 'Gaurang Kunkolienkar', '9881810606', 'gourang4050@gmail.com', 'AS 204 Supreme Temple Retreat, Near Maruti Temple,Davorlim Navelim Goa', 'Pune', 'Maharashtra', '411014', 0, 'dx-plus', 'blue', 1, 'dx-plus', '1760034245717324', 1000.00, 'COMPLETED', '{\"orderId\":\"OMO2510092357237635987784\",\"state\":\"COMPLETED\",\"amount\":100000,\"payableAmount\":100000,\"feeAmount\":0,\"expireAt\":1760035343763,\"metaInfo\":{\"udf1\":\"additional-information-1\",\"udf2\":\"additional-information-2\",\"udf3\":\"additional-information-3\",\"udf4\":\"additional-information-4\",\"udf5\":\"additional-information-5\"},\"paymentDetails\":[{\"transactionId\":\"OM2510092357329860023226\",\"paymentMode\":\"UPI_INTENT\",\"timestamp\":1760034453017,\"amount\":100000,\"payableAmount\":100000,\"feeAmount\":0,\"state\":\"COMPLETED\",\"instrument\":{\"type\":\"ACCOUNT\",\"maskedAccountNumber\":\"XXXXXX7732\",\"ifsc\":\"HDFC0000370\",\"accountType\":\"SAVINGS\",\"bankId\":\"HDFC\"},\"rail\":{\"type\":\"UPI\",\"utr\":\"573789575719\",\"upiTransactionId\":\"AXL38a931400a3b44eda196a530005eee15\",\"vpa\":\"98XXXXXXXX-2@axl\"},\"splitInstruments\":[{\"instrument\":{\"type\":\"ACCOUNT\",\"maskedAccountNumber\":\"XXXXXX7732\",\"ifsc\":\"HDFC0000370\",\"accountType\":\"SAVINGS\",\"bankId\":\"HDFC\"},\"rail\":{\"type\":\"UPI\",\"utr\":\"573789575719\",\"upiTransactionId\":\"AXL38a931400a3b44eda196a530005eee15\",\"vpa\":\"98XXXXXXXX-2@axl\"},\"amount\":100000}]}]}', '2025-10-09 23:57:23', '2025-10-09 23:58:02'),
(6, '1760184429316139', 'Adil Desai', '9823569352', 'adiljdesai@gmail.com', 'Salunke Vihar', 'Pune', 'Maharashtra', '411048', 0, 'dx-plus', 'black', 1, 'dx-plus', '1760184429316139', 1000.00, 'COMPLETED', '{\"orderId\":\"OMO2510111738256501955815\",\"state\":\"COMPLETED\",\"amount\":100000,\"payableAmount\":100000,\"feeAmount\":0,\"expireAt\":1760185405651,\"metaInfo\":{\"udf1\":\"additional-information-1\",\"udf2\":\"additional-information-2\",\"udf3\":\"additional-information-3\",\"udf4\":\"additional-information-4\",\"udf5\":\"additional-information-5\"},\"paymentDetails\":[{\"transactionId\":\"OM2510111739180071955138\",\"paymentMode\":\"UPI_COLLECT\",\"timestamp\":1760184558034,\"amount\":100000,\"payableAmount\":100000,\"feeAmount\":0,\"state\":\"COMPLETED\",\"instrument\":{\"type\":\"ACCOUNT\",\"ifsc\":\"SBIN0003861\",\"accountType\":\"SAVINGS\"},\"rail\":{\"type\":\"UPI\",\"utr\":\"528455827766\",\"upiTransactionId\":\"YBL899a6ba1758b4fda9491dd8250ba69fa\",\"vpa\":\"adXXXXXXai@oksbi\"},\"splitInstruments\":[{\"instrument\":{\"type\":\"ACCOUNT\",\"ifsc\":\"SBIN0003861\",\"accountType\":\"SAVINGS\"},\"rail\":{\"type\":\"UPI\",\"utr\":\"528455827766\",\"upiTransactionId\":\"YBL899a6ba1758b4fda9491dd8250ba69fa\",\"vpa\":\"adXXXXXXai@oksbi\"},\"amount\":100000}]}]}', '2025-10-11 17:38:25', '2025-10-11 17:39:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `idx_admin_users_uuid` (`uuid`);

--
-- Indexes for table `allowed_cities`
--
ALTER TABLE `allowed_cities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `allowed_pincodes`
--
ALTER TABLE `allowed_pincodes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pincode` (`pincode`),
  ADD KEY `city_id` (`city_id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_contacts_uuid` (`uuid`);

--
-- Indexes for table `email_notifications`
--
ALTER TABLE `email_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_notification` (`transaction_id`,`status`),
  ADD KEY `idx_transaction_id` (`transaction_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sent_at` (`sent_at`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `migration_name` (`migration_name`),
  ADD KEY `idx_migration_name` (`migration_name`);

--
-- Indexes for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_phone_purpose` (`phone`,`purpose`),
  ADD KEY `idx_otp_expires` (`otp`,`expires_at`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `salesforce_submissions`
--
ALTER TABLE `salesforce_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_submission` (`transaction_id`,`form_type`,`submission_type`),
  ADD KEY `idx_transaction_id` (`transaction_id`),
  ADD KEY `idx_form_type` (`form_type`),
  ADD KEY `idx_submitted_at` (`submitted_at`);

--
-- Indexes for table `test_drives`
--
ALTER TABLE `test_drives`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_test_drives_uuid` (`uuid`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `allowed_cities`
--
ALTER TABLE `allowed_cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `allowed_pincodes`
--
ALTER TABLE `allowed_pincodes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `email_notifications`
--
ALTER TABLE `email_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `salesforce_submissions`
--
ALTER TABLE `salesforce_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `test_drives`
--
ALTER TABLE `test_drives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `allowed_pincodes`
--
ALTER TABLE `allowed_pincodes`
  ADD CONSTRAINT `allowed_pincodes_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `allowed_cities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
