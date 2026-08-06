-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2026 at 11:16 AM
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
-- Database: `hospital_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `username` varchar(150) NOT NULL,
  `action` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Success',
  `event_type` varchar(50) DEFAULT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` varchar(64) DEFAULT NULL,
  `actor_id` varchar(64) DEFAULT NULL,
  `actor_role` varchar(50) DEFAULT NULL,
  `source` varchar(150) DEFAULT NULL,
  `request_id` varchar(64) DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `severity` varchar(20) DEFAULT NULL,
  `duration_ms` int(11) DEFAULT NULL,
  `status_code` int(11) DEFAULT NULL,
  `error_code` varchar(50) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_type` varchar(30) DEFAULT NULL,
  `client_ip` varchar(45) DEFAULT NULL,
  `hostname` varchar(255) DEFAULT NULL,
  `old_values` longtext DEFAULT NULL,
  `new_values` longtext DEFAULT NULL,
  `diff_json` longtext DEFAULT NULL,
  `metadata_json` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `username`, `action`, `ip_address`, `status`, `event_type`, `entity_type`, `entity_id`, `actor_id`, `actor_role`, `source`, `request_id`, `session_id`, `severity`, `duration_ms`, `status_code`, `error_code`, `error_message`, `user_agent`, `device_type`, `client_ip`, `hostname`, `old_values`, `new_values`, `diff_json`, `metadata_json`, `created_at`) VALUES
(1, 'admin', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'admin', NULL, NULL, 'auth/login', '689b40d22c99c697', '', 'WARN', 2419, 401, NULL, 'User not found', NULL, NULL, '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 13:40:45'),
(2, 'admin', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'admin', NULL, NULL, 'auth/login', 'f8b34b992aace8c5', '', 'WARN', 4578, 401, NULL, 'User not found', NULL, NULL, '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 13:57:25'),
(3, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'f62caffda4958c78', 'afb4b8ab5c20387b805083c1d7dc6d0b', 'INFO', 1294, 200, NULL, NULL, NULL, NULL, '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 14:18:05'),
(4, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '167405e4a22d9db3', 'c4e84f0f3f042ff0a8da525013d1e300', 'INFO', 1066, 200, NULL, NULL, NULL, NULL, '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 14:18:06'),
(5, 'admin@gmail.com', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'admin@gmail.com', NULL, NULL, 'auth/login', '7816f3ccac38bc84', '', 'WARN', 431, 401, NULL, 'User not found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 14:20:16'),
(6, 'admin', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'admin', NULL, NULL, 'auth/login', 'cb48c1ea2335d9d1', '', 'WARN', 862, 401, NULL, 'Password mismatch', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 14:20:38'),
(7, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '799a25cc54bf61bd', '0f8ee02fa25c4d38461fb329cc5c9cde', 'INFO', 603, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 14:20:44'),
(8, '1', 'Logout', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', 'e7abf47f4b3ab1f7', '0f8ee02fa25c4d38461fb329cc5c9cde', 'INFO', 2385, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 14:20:47'),
(9, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '682efbceac623234', '3fce964783899d9b42e0c5eab3bcb094', 'INFO', 2315, 200, NULL, NULL, NULL, NULL, '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 14:25:41'),
(10, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '002fe0b19fd9dc33', '3ae95a38ca97298762b1d329bf65a2a3', 'INFO', 2384, 200, NULL, NULL, NULL, NULL, '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 14:35:30'),
(11, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '9aed39d9822fe943', '71403ada69e43447682fa8d7356145ab', 'INFO', 1282, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 16:12:02'),
(12, '1', 'Logout', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', '67c0985442f97718', '71403ada69e43447682fa8d7356145ab', 'INFO', 3190, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 16:12:06'),
(13, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '4c06403994c10f3d', 'b93cd4f474b3f18ceb2998a0049c2a0a', 'INFO', 1743, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 06:48:46'),
(14, '1', 'Logout', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', 'f226581767f3ef75', 'b93cd4f474b3f18ceb2998a0049c2a0a', 'INFO', 3388, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 06:48:51'),
(15, 'rec1', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'rec1', NULL, NULL, 'auth/login', 'f684e624b73cf5d9', '', 'WARN', 3287, 401, NULL, 'Password mismatch', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 06:49:47'),
(16, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', 'e1042b016afb91c8', 'be1d2567f644ef86f00c39a7fa91a321', 'INFO', 2968, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 06:49:55'),
(17, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', '0ee2193aa96f1cc8', 'be1d2567f644ef86f00c39a7fa91a321', 'INFO', 5271, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 06:50:01'),
(18, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'aadbd399cacf9566', '90b754b03001b10b43914589aa32ae34', 'INFO', 3990, 200, NULL, NULL, NULL, NULL, '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 07:33:26'),
(19, 'nurse1', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'nurse1', NULL, NULL, 'auth/login', '6bc71fe9c1fb40e7', '', 'WARN', 1454, 401, NULL, 'Password mismatch', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 07:44:38'),
(20, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', 'b8cbd7d9acbba992', '7867fb2616bd445ca90619f9c53bde77', 'INFO', 1268, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 07:44:44'),
(21, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '30e61dc86482f731', '7867fb2616bd445ca90619f9c53bde77', 'INFO', 2825, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 07:44:48'),
(22, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '9f7e568ef23d1f8b', '7739d75ad221f08295bdcca5ee7605bb', 'INFO', 1475, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:09:36'),
(23, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', '3fb92589e83c3369', '7739d75ad221f08295bdcca5ee7605bb', 'INFO', 4826, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:09:42'),
(24, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'b90cc9d26bc3ef62', '86f7ffded09399b5a471560002f40f64', 'INFO', 1197, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:27:46'),
(25, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'd23156e41b7d99c9', '2ee8c9dee48c93fb8ae475775d3a26e1', 'INFO', 4309, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:28:29'),
(26, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '4d88df1deac83c72', '06258dd0101e3d3ba39c64c7709f9179', 'INFO', 5520, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:34:29'),
(27, 'triage1', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'triage1', NULL, NULL, 'auth/login', 'f761988592b8ceb8', '', 'WARN', 1524, 401, NULL, 'User not found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:37:06'),
(28, 'itsup1', 'Login', '::1', 'Success', 'audit', 'user', '6', '6', 'it_support', 'auth/login', 'b97e9d5a1c2eee02', '83a3a67eee9d1da5767e70350450abce', 'INFO', 1236, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:40:08'),
(29, '6', 'Logout', '::1', 'Success', 'audit', 'user', '6', '6', 'it_support', 'auth/logout', 'a8a8d5ff6536e502', '83a3a67eee9d1da5767e70350450abce', 'INFO', 3183, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:40:12'),
(30, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', 'f8811dafa72ed0d1', 'e67dad0f247bacb6a0f8061577aa3209', 'INFO', 1962, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:40:31'),
(31, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '26e512a8d110d32f', 'e67dad0f247bacb6a0f8061577aa3209', 'INFO', 2676, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:40:35'),
(32, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '251b63c059718187', 'e67dad0f247bacb6a0f8061577aa3209', 'INFO', 2607, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:44:07'),
(33, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'd8aa7f660920ff8d', '1f12607ab9dd34c2f495df564a919037', 'INFO', 1138, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:44:19'),
(34, '1', 'Logout', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', '46857278fd4300f5', '1f12607ab9dd34c2f495df564a919037', 'INFO', 4127, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:44:24'),
(35, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '21d320a374f446f5', 'fa6c58366eef76a1c4de265514e3012e', 'INFO', 3127, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:47:48'),
(36, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', 'ce8a4cf71e76e6ed', '6759571188a3e090d21e55365fa7bc33', 'INFO', 2578, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:48:28'),
(37, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '9b658e4152dd7681', '6759571188a3e090d21e55365fa7bc33', 'INFO', 4747, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:48:33'),
(38, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '19a730c166a38e61', '6759571188a3e090d21e55365fa7bc33', 'INFO', 5034, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:48:34'),
(39, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '3c3c627068e5efaa', 'de961a96326cb8dcb2d898a4ca7b99ba', 'INFO', 1144, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:49:18'),
(40, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', '8d6272f49d0d6c6e', 'de961a96326cb8dcb2d898a4ca7b99ba', 'INFO', 3014, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:49:22'),
(41, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', 'cfff7249e4be8381', '8438dca15834091a6d8e1c0007405ecc', 'INFO', 1170, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:49:58'),
(42, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '04477b08face82db', '8438dca15834091a6d8e1c0007405ecc', 'INFO', 3831, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:50:03'),
(43, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '843cb591fc578d96', '8438dca15834091a6d8e1c0007405ecc', 'INFO', 4269, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:50:03'),
(44, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '9102322d1676e3a9', '096a9cff06c4e89f5e347db6ca0afcae', 'INFO', 3683, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:56:06'),
(45, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', 'e6a1c582d7a4b05a', 'f440f5fee130461006ed4a60cba3d817', 'INFO', 4399, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:56:11'),
(46, 'Triage Officer 1', 'Captured vitals', '::1', 'Success', 'audit', 'patient_queue', '1', '7', 'nurse_aid', 'nurse_aid/save_vitals', 'bc9fe1aca5822532', '', 'INFO', 4795, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:56:28'),
(47, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'ac55667b07ad1d14', '3ebca70cab3c0b7b305851d4ab7afd3d', 'INFO', 960, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:57:03'),
(48, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '64fc06d2b4ada2b8', '20e40f47776a1c9578f4d805f6fadc54', 'INFO', 795, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:57:04'),
(49, 'Triage Officer 1', 'Captured vitals', '::1', 'Success', 'audit', 'patient_queue', '2', '7', 'nurse_aid', 'nurse_aid/save_vitals', '9945b7bc2a2b19ae', '', 'INFO', 1472, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:57:11'),
(50, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'e26f4b36b7acd0a7', '3b00ef463288e0ecc98ffab31cf310cf', 'INFO', 1491, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:58:50'),
(51, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '6972a0836397aa3f', '33778f9ac666e63edacc3e8df2424acc', 'INFO', 1244, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:00:47'),
(52, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', 'b3cbdb80b355de13', '33778f9ac666e63edacc3e8df2424acc', 'INFO', 4113, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:00:52'),
(53, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '34c8242b5c21c931', '707432442910290babd1491d15142f20', 'INFO', 1773, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:01:19'),
(54, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', 'b21b36928a593268', '707432442910290babd1491d15142f20', 'INFO', 4567, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:01:25'),
(55, 'triage', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'triage', NULL, NULL, 'auth/login', '5fd1bf5c72ecfe91', '', 'WARN', 1908, 401, NULL, 'User not found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:02:08'),
(56, 'triage', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'triage', NULL, NULL, 'auth/login', '89354fee740374a4', '', 'WARN', 1949, 401, NULL, 'User not found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:02:20'),
(57, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', 'a781fa35d7afd677', '86cdbf97c6be5a6afe548f964a184fdb', 'INFO', 2057, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:02:28'),
(58, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '17f27dffaf0d7503', '86cdbf97c6be5a6afe548f964a184fdb', 'INFO', 5719, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:02:35'),
(59, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '0a63a376fc932a20', '86cdbf97c6be5a6afe548f964a184fdb', 'INFO', 5694, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:02:35'),
(60, 'Triage Officer 1', 'Started triage', '::1', 'Success', 'audit', 'patient_queue', '3', '7', 'nurse_aid', 'nurse_aid/start_triage', '39736abe88267a76', '', 'INFO', 1263, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:02:43'),
(61, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '7f1b6a4a3662eff2', '86cdbf97c6be5a6afe548f964a184fdb', 'INFO', 9159, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:14:18'),
(62, 'Triage Officer 1', 'Started triage', '::1', 'Success', 'audit', 'patient_queue', '3', '7', 'nurse_aid', 'nurse_aid/start_triage', '3cde52cc9f685f7f', '', 'INFO', 4631, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:14:25'),
(63, 'Triage Officer 1', 'Started triage', '::1', 'Success', 'audit', 'patient_queue', '3', '7', 'nurse_aid', 'nurse_aid/start_triage', '396440bfa4a18f51', '', 'INFO', 3261, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:17:48'),
(64, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '3deb680207b7b0b5', 'bb2088be3d6a28204f0d234d9c4d1239', 'INFO', 1592, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:45:30'),
(65, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '619730c6affff12c', 'bb2088be3d6a28204f0d234d9c4d1239', 'INFO', 3035, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:45:34'),
(66, 'Triage Officer 1', 'Started triage', '::1', 'Success', 'audit', 'patient_queue', '3', '7', 'nurse_aid', 'nurse_aid/start_triage', 'fe9d331be34f44e8', '', 'INFO', 3387, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:45:39'),
(67, 'Triage Officer 1', 'Captured vitals', '::1', 'Success', 'audit', 'patient_queue', '3', '7', 'nurse_aid', 'nurse_aid/save_vitals', 'c257d33299657f7a', '', 'INFO', 2163, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:47:10'),
(68, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '9095532c759310bf', '90327cc71e6485ac97fd6f79fe0c4981', 'INFO', 2624, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:52:28'),
(69, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'a08b5861a6ad6209', '90327cc71e6485ac97fd6f79fe0c4981', 'INFO', 2614, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:53:08'),
(70, 'doc1', 'Login', '::1', 'Success', 'audit', 'user', '2', '2', 'doctor', 'auth/login', '0405fe9f7a3d2cdf', '53dce8f4596c7ef2be6f1e38884596b0', 'INFO', 2234, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:53:22'),
(71, '2', 'Logout', '::1', 'Success', 'audit', 'user', '2', '2', 'doctor', 'auth/logout', '8ff0386062431d03', '53dce8f4596c7ef2be6f1e38884596b0', 'INFO', 5683, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:53:28'),
(72, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '8d8719775705737c', '249543d683e975c5127742dd48c498d7', 'INFO', 2272, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:14:53'),
(73, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '88bf1048814c21ce', '249543d683e975c5127742dd48c498d7', 'INFO', 6813, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:15:01'),
(74, 'Sister Betty', 'Completed nurse consultation', '::1', 'Success', 'audit', 'patient_queue', '3', '3', 'nurse', 'nurse/consult_save', '6034dacd311af5af', '', 'INFO', 2446, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, '{\"prescriptions_added\":0,\"sent_to_pharmacy\":\"yes\"}', '2026-02-20 10:22:20'),
(75, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', 'ee89aa9e4c4a0dde', '6e4d5407edc78d52b7c9a41141e896c6', 'INFO', 1518, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:00'),
(76, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', '6595d4136951720a', '6e4d5407edc78d52b7c9a41141e896c6', 'INFO', 3725, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:05'),
(77, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '1', '5', 'pharmacist', 'pharmacy/nurse_request_update', 'ba7fd9bfbe9b1fa0', '', 'INFO', 6662, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:23'),
(78, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '1', '5', 'pharmacist', 'pharmacy/nurse_request_update', '59129d1ab46b6a3d', '', 'INFO', 5941, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:27'),
(79, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '1', '5', 'pharmacist', 'pharmacy/nurse_request_update', 'fbd3b497d4295719', '', 'INFO', 6318, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:28'),
(80, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '1', '5', 'pharmacist', 'pharmacy/nurse_request_update', '92417e594fe6d8eb', '', 'INFO', 6686, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:33'),
(81, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '1', '5', 'pharmacist', 'pharmacy/nurse_request_update', 'a73ee6cc0c90a376', '', 'INFO', 7176, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:35'),
(82, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '1', '5', 'pharmacist', 'pharmacy/nurse_request_update', '7f6979f8a198ea21', '', 'INFO', 5572, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:35'),
(83, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '1', '5', 'pharmacist', 'pharmacy/nurse_request_update', 'f03b9cf4e80f7197', '', 'INFO', 3460, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:36'),
(84, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '1', '5', 'pharmacist', 'pharmacy/nurse_request_update', '76ec2546ba420ae3', '', 'INFO', 3191, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:36'),
(85, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '1', '5', 'pharmacist', 'pharmacy/nurse_request_update', '61c4b758e40b5456', '', 'INFO', 2974, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:36'),
(86, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', 'bdc0d54d737471b6', 'c8fb44a2bd41e1a4e9c6091c7289f045', 'INFO', 5786, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:27:15'),
(87, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '6f23021f21a30832', 'c8fb44a2bd41e1a4e9c6091c7289f045', 'INFO', 4310, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:27:21'),
(88, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '9a35fc6f758d2b02', 'c8fb44a2bd41e1a4e9c6091c7289f045', 'INFO', 4520, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:27:21'),
(89, 'Sister Betty', 'Completed nurse consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '3', 'nurse', 'nurse/consult_save', '807c0c88c93fa42f', '', 'INFO', 1583, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, '{\"prescriptions_added\":0,\"sent_to_pharmacy\":\"no\"}', '2026-02-20 10:30:57'),
(90, 'Sister Betty', 'Completed nurse consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '3', 'nurse', 'nurse/consult_save', '4628a8daec46e015', '', 'INFO', 2902, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, '{\"prescriptions_added\":0,\"sent_to_pharmacy\":\"yes\"}', '2026-02-20 10:31:38'),
(91, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', 'f4bada3de11a710b', 'ba0c24ef82b86d7f7146bdf68b571552', 'INFO', 3171, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:31:54'),
(92, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', 'aabf1ee1539a09b4', 'ba0c24ef82b86d7f7146bdf68b571552', 'INFO', 3424, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:31:58'),
(93, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '2', '5', 'pharmacist', 'pharmacy/nurse_request_update', '7a917e98d696e761', '', 'INFO', 5634, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:32:14'),
(94, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '2', '5', 'pharmacist', 'pharmacy/nurse_request_update', '3a25b1bb837807c2', '', 'INFO', 5304, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:32:16'),
(95, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '2', '5', 'pharmacist', 'pharmacy/nurse_request_update', 'ce66a65698a87447', '', 'INFO', 3467, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:32:17'),
(96, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '8ade81c627b3fc7b', '1ff55526af78d1aae39e43729e74dfb1', 'INFO', 1217, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:35:15'),
(97, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '893aa8565e4ea480', '1ff55526af78d1aae39e43729e74dfb1', 'INFO', 4427, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:35:21'),
(98, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', 'cf6313c4cc13a885', '88b88603ec3c71627463f18f45b0cab9', 'INFO', 1565, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:05:05'),
(99, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', '92fbaeb2cda45599', '88b88603ec3c71627463f18f45b0cab9', 'INFO', 4914, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:05:11'),
(100, 'triage1', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'triage1', NULL, NULL, 'auth/login', '6cb22f4adbc1a8d7', '', 'WARN', 803, 401, NULL, 'Password mismatch', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:07:04'),
(101, 'triage1', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'triage1', NULL, NULL, 'auth/login', 'a2b04b7567b7433e', '', 'WARN', 859, 401, NULL, 'Password mismatch', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:07:18'),
(102, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '2acd0bb6bd71bcd3', '6071b9dd437b54cc398bf615d3212a2f', 'INFO', 812, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:07:34'),
(103, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '90bd128d1fea8304', '6071b9dd437b54cc398bf615d3212a2f', 'INFO', 2407, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:07:37'),
(104, 'Triage Officer 1', 'Started triage', '::1', 'Success', 'audit', 'patient_queue', '4', '7', 'nurse_aid', 'nurse_aid/start_triage', '867f7edcbdd522c0', '', 'INFO', 726, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:07:57'),
(105, 'Triage Officer 1', 'Captured vitals', '::1', 'Success', 'audit', 'patient_queue', '4', '7', 'nurse_aid', 'nurse_aid/save_vitals', '3af9115d2ca73354', '', 'INFO', 673, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:08:28'),
(106, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '2e59cac2bb239b30', '6a3f28909bbe14831295c41baf450dbf', 'INFO', 803, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:08:54'),
(107, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '7611015b378b09fd', '6a3f28909bbe14831295c41baf450dbf', 'INFO', 3070, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:08:58'),
(108, 'Sister Betty', 'Completed nurse consultation', '::1', 'Success', 'audit', 'patient_queue', '4', '3', 'nurse', 'nurse/consult_save', 'b08278de4279b694', '', 'INFO', 1707, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, '{\"prescriptions_added\":1,\"sent_to_pharmacy\":\"yes\"}', '2026-02-20 11:21:01'),
(109, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', 'c4c361ed0cf49c2e', 'b2acbdbef38df14977376de8b1f8a965', 'INFO', 885, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:21:46'),
(110, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', 'd5db473c946cf23b', 'b2acbdbef38df14977376de8b1f8a965', 'INFO', 3287, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:21:51'),
(111, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '62b24751e26f0ea7', '23a2f211be2e3f3dc8114cf459d9c950', 'INFO', 1208, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:46:18'),
(112, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '2ad6f6114967be5d', '23a2f211be2e3f3dc8114cf459d9c950', 'INFO', 3849, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:46:23'),
(113, 'Sister Betty', 'Completed nurse consultation', '::1', 'Success', 'audit', 'patient_queue', '3', '3', 'nurse', 'nurse/consult_save', '7adf27d2778385bc', '', 'INFO', 2124, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, '{\"prescriptions_added\":3,\"sent_to_pharmacy\":\"yes\"}', '2026-02-20 11:48:14'),
(114, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', '4fc236c5a75b982a', 'a279afde05a277b9099a2878a4d57901', 'INFO', 1992, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:48:35'),
(115, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', '4e3508ef8a6540e2', 'a279afde05a277b9099a2878a4d57901', 'INFO', 4520, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:48:41'),
(116, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', 'fcfa9682a2741600', 'a279afde05a277b9099a2878a4d57901', 'INFO', 2185, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:49:26'),
(117, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '67b448b4bbf58378', '1e96820a59a98625964cff083ce5ef11', 'INFO', 967, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:49:34'),
(118, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '58967d8e9a21ae6a', '1e96820a59a98625964cff083ce5ef11', 'INFO', 5288, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:49:41'),
(119, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '7f226855f10655fd', '0d60a4fe3ef0aa87f08a69e87a5da07a', 'INFO', 1542, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 12:22:19'),
(120, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '31476318f79f778c', '0d60a4fe3ef0aa87f08a69e87a5da07a', 'INFO', 6710, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 12:22:27'),
(121, 'doc1', 'Login', '::1', 'Success', 'audit', 'user', '2', '2', 'doctor', 'auth/login', '8d6ed62e13722500', 'c865fce1c30c986344c2251aa5663fcc', 'INFO', 2206, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 12:24:09'),
(122, '2', 'Logout', '::1', 'Success', 'audit', 'user', '2', '2', 'doctor', 'auth/logout', 'ae557af5ccfb7807', 'c865fce1c30c986344c2251aa5663fcc', 'INFO', 5348, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 12:24:15'),
(123, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', 'cd6efd8b3276fc18', '57cbcc9457e67a735bc21d5233b94c1f', 'INFO', 1746, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 12:25:56'),
(124, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '67d8df07aa43124f', '57cbcc9457e67a735bc21d5233b94c1f', 'INFO', 6666, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 12:26:03'),
(125, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '2654d671b634bc20', '2a11ddb5e52ad9b53309666444effc35', 'INFO', 1733, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:00:59'),
(126, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '363e20d792f68b8f', '2a11ddb5e52ad9b53309666444effc35', 'INFO', 5197, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:01:05'),
(127, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'f89a671266fd71ef', 'f0ab94db7beec8a75444b9aae79909ba', 'INFO', 1081, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:01:26'),
(128, '1', 'Logout', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', 'c73b6c7370b96d45', 'f0ab94db7beec8a75444b9aae79909ba', 'INFO', 2054, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:01:29'),
(129, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '021e6521e4995a41', '5a2a926fc38af15e03e42669ecb06043', 'INFO', 1338, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:02:11'),
(130, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', 'b55ca77bd8a0cde8', '5a2a926fc38af15e03e42669ecb06043', 'INFO', 5442, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:02:18'),
(131, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', 'e6e7ba0ac01faeec', '5a2a926fc38af15e03e42669ecb06043', 'INFO', 2747, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:02:33'),
(132, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '144957fb04cec14b', '5a2a926fc38af15e03e42669ecb06043', 'INFO', 2861, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:02:33'),
(133, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', 'ad5af5d538b9d2b7', 'e7a66883a837668b5bb00b51f08724e2', 'INFO', 1346, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:03:41'),
(134, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'ffd0f4a10e03c18d', 'e7a66883a837668b5bb00b51f08724e2', 'INFO', 4705, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:03:47'),
(135, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'a3675a8a42be0fe3', '73ee2a673b63ab23e36a76f76d9a991a', 'INFO', 874, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:08:46'),
(136, '1', 'Logout', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', 'e71ddaf31fc67058', '73ee2a673b63ab23e36a76f76d9a991a', 'INFO', 2233, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:08:49'),
(137, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', 'ad4749a958897783', '3d8f5777b85848d0f86af5bdd74d7922', 'INFO', 1575, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:21:55');
INSERT INTO `activity_logs` (`id`, `username`, `action`, `ip_address`, `status`, `event_type`, `entity_type`, `entity_id`, `actor_id`, `actor_role`, `source`, `request_id`, `session_id`, `severity`, `duration_ms`, `status_code`, `error_code`, `error_message`, `user_agent`, `device_type`, `client_ip`, `hostname`, `old_values`, `new_values`, `diff_json`, `metadata_json`, `created_at`) VALUES
(138, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'bd006b25cca2ca61', '3d8f5777b85848d0f86af5bdd74d7922', 'INFO', 4091, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:22:01'),
(139, 'admin', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'admin', NULL, NULL, 'auth/login', 'd8acb0816a2a2e52', '', 'WARN', 1300, 401, NULL, 'Password mismatch', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:22:28'),
(140, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '21c83db02fb8f885', '7837cb55a4f966f0d4b51c0d8f7cba46', 'INFO', 2197, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:22:31'),
(141, '1', 'Logout', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', '44707c5761e91b4a', '7837cb55a4f966f0d4b51c0d8f7cba46', 'INFO', 5234, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:22:37'),
(142, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '514f5d1605f8e4d5', 'ef36ee3217d9cc0511cac83bdb6b0134', 'INFO', 1586, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:50:07'),
(143, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '7603cd3940a10010', 'ef36ee3217d9cc0511cac83bdb6b0134', 'INFO', 4380, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:50:13'),
(144, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', 'b9bd70a0799b1f27', '3382310fd936bc4a98512de479a2cc93', 'INFO', 7043, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:20:01'),
(145, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', 'a155420cded94348', '3382310fd936bc4a98512de479a2cc93', 'INFO', 8603, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:20:11'),
(146, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '4313cf5d8fee40f2', '0fe67c81391496f2077580b4e781c691', 'INFO', 3370, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:21:51'),
(147, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '63d8c51cdd4186a9', '0fe67c81391496f2077580b4e781c691', 'INFO', 3670, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:21:56'),
(148, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '3ece7c9891fc31c7', 'f46d8e1d33d36b98f971f7591aaebf2e', 'INFO', 1971, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:22:24'),
(149, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '95dfbce06d8486de', 'f46d8e1d33d36b98f971f7591aaebf2e', 'INFO', 4610, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:22:29'),
(150, 'doc1', 'Login', '::1', 'Success', 'audit', 'user', '2', '2', 'doctor', 'auth/login', '9edd890bc29988f1', '8d410069a6002b7813716cb1a20d4998', 'INFO', 3089, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:23:23'),
(151, '2', 'Logout', '::1', 'Success', 'audit', 'user', '2', '2', 'doctor', 'auth/logout', '149cddf4094ea70e', '8d410069a6002b7813716cb1a20d4998', 'INFO', 7463, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:23:32'),
(152, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '4bd15c27b803d68f', '', 'INFO', 9114, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:24:47'),
(153, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '9f6bd565507fbf29', '', 'INFO', 8758, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:24:47'),
(154, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', 'f03d124cf7494959', '', 'INFO', 8572, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:24:49'),
(155, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '52c89bd264807d28', '', 'INFO', 8452, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:24:49'),
(156, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '5e617435d50c31c8', '', 'INFO', 7527, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:24:52'),
(157, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', 'f0e605d309630528', '', 'INFO', 6891, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:24:54'),
(158, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '6410b111ce326202', '', 'INFO', 7072, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:01'),
(159, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '416fee5bea9c1072', '', 'INFO', 6741, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:02'),
(160, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '9817959d95db797c', '', 'INFO', 5626, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:02'),
(161, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '0cd86a2bfa5c6c5a', '', 'INFO', 8613, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:06'),
(162, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', 'c50edf8a8638e48c', '', 'INFO', 7716, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:07'),
(163, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '20ee18c630a05cf3', '', 'INFO', 9431, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:10'),
(164, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', 'ccfc7813ee5b71c9', '', 'INFO', 9797, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:12'),
(165, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', 'a3435131ffecab23', '', 'INFO', 9418, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:12'),
(166, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '12061a9409f72ff3', '', 'INFO', 9312, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:16'),
(167, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', 'd8298fbe8a306e10', '', 'INFO', 8912, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:17'),
(168, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '3a74536ab07234fe', '', 'INFO', 9093, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:18'),
(169, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', 'af7b28889595f90c', '', 'INFO', 8168, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:20'),
(170, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '1b8b2a2e728ad703', '', 'INFO', 8566, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:22'),
(171, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '8c8e6afe75192ffd', '', 'INFO', 9022, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:22'),
(172, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', '198c35d3d8b1438f', '7b669e840c901dbe52ee66ee5ddfa327', 'INFO', 1800, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:26:26'),
(173, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', '27894df997e05107', '7b669e840c901dbe52ee66ee5ddfa327', 'INFO', 6953, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:26:34'),
(174, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '27', '5', 'pharmacist', 'pharmacy/dispense', '61fb5acefb794323', '', 'INFO', 6613, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:29:59'),
(175, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '27', '5', 'pharmacist', 'pharmacy/dispense', 'bc770abf8f294d73', '', 'INFO', 8051, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:30:04'),
(176, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '8', '5', 'pharmacist', 'pharmacy/dispense', 'cf1923b76e1f53c3', '', 'INFO', 8127, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:30:14'),
(177, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '8', '5', 'pharmacist', 'pharmacy/dispense', '7143bbb3d3231b06', '', 'INFO', 4966, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:30:21'),
(178, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '8', '5', 'pharmacist', 'pharmacy/dispense', '59b586d511ee5798', '', 'INFO', 5269, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:30:22'),
(179, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '9', '5', 'pharmacist', 'pharmacy/dispense', 'fb1a648c99151358', '', 'INFO', 10333, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:30:33'),
(180, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '9', '5', 'pharmacist', 'pharmacy/dispense', '147d5845d391f558', '', 'INFO', 7684, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:30:34'),
(181, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '10', '5', 'pharmacist', 'pharmacy/dispense', '70e456c4e43966e9', '', 'INFO', 7099, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:30:45'),
(182, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '11', '5', 'pharmacist', 'pharmacy/dispense', '7cdc2338a3ec99fc', '', 'INFO', 8400, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:31:29'),
(183, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '11', '5', 'pharmacist', 'pharmacy/dispense', '579644faf3e6f79c', '', 'INFO', 7592, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:31:38'),
(184, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '11', '5', 'pharmacist', 'pharmacy/dispense', '95097c489a38cda8', '', 'INFO', 6836, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:31:39'),
(185, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '11', '5', 'pharmacist', 'pharmacy/dispense', 'af685c28d5ffb860', '', 'INFO', 7196, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:31:40'),
(186, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '3', '5', 'pharmacist', 'pharmacy/nurse_request_update', '52a1200484c5a428', '', 'INFO', 7540, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:32:24'),
(187, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '4', '5', 'pharmacist', 'pharmacy/nurse_request_update', '62cba99849578f70', '', 'INFO', 7632, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:32:25'),
(188, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '3', '5', 'pharmacist', 'pharmacy/nurse_request_update', '59ebd02e171444a0', '', 'INFO', 9896, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:32:47'),
(189, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '4', '5', 'pharmacist', 'pharmacy/nurse_request_update', 'f0bcf922c8d5ae8b', '', 'INFO', 8894, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:32:49'),
(190, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '4', '5', 'pharmacist', 'pharmacy/nurse_request_update', 'b104355c06d45677', '', 'INFO', 6745, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:32:57'),
(191, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '5694a6a55b327aa5', '2960fe2e09c50d44fc378a8758b2ba9d', 'INFO', 2910, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:33:17'),
(192, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', 'b1c0781fa075d179', '2960fe2e09c50d44fc378a8758b2ba9d', 'INFO', 7874, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:33:26'),
(193, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', '23b7d05788e1c527', '94f202912164542f6fe231bb65f520d1', 'INFO', 8532, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 09:11:56'),
(194, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', 'd4f4d6c8c7795813', '94f202912164542f6fe231bb65f520d1', 'INFO', 13830, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 09:12:12'),
(195, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', 'a8079074a11d438d', 'c4237b98ef2b6c5cae8f8946dc26452e', 'INFO', 7950, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 09:50:46'),
(196, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', '250e0f678b8e3388', 'c4237b98ef2b6c5cae8f8946dc26452e', 'INFO', 14651, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 09:51:02'),
(197, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', 'b2e3a394618e1b3b', '55822f3a72579e4992407c8fe0c87671', 'INFO', 1231, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:24:08'),
(198, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', '6055c66d3f616cb0', '55822f3a72579e4992407c8fe0c87671', 'INFO', 6127, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:24:15'),
(199, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', '59403456957a8259', '55822f3a72579e4992407c8fe0c87671', 'INFO', 6215, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:24:16'),
(200, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '13', '5', 'pharmacist', 'pharmacy/dispense', 'a85d8134b05d2f45', '', 'INFO', 1330, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:24:59'),
(201, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '14', '5', 'pharmacist', 'pharmacy/dispense', '531ae3176c777341', '', 'INFO', 5129, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:25:07'),
(202, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '15', '5', 'pharmacist', 'pharmacy/dispense', 'c51f52ed24f58e95', '', 'INFO', 2165, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:25:10'),
(203, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '16', '5', 'pharmacist', 'pharmacy/dispense', '779306860eab2aa9', '', 'INFO', 4961, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:25:18'),
(204, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '17', '5', 'pharmacist', 'pharmacy/dispense', '89a0d7773f34b8a0', '', 'INFO', 2788, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:25:18'),
(205, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '18', '5', 'pharmacist', 'pharmacy/dispense', '7c4cfbf93e8228d6', '', 'INFO', 2581, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:25:22'),
(206, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '19', '5', 'pharmacist', 'pharmacy/dispense', '363831c88261cf7d', '', 'INFO', 2888, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:25:25'),
(207, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '20', '5', 'pharmacist', 'pharmacy/dispense', '4de6a365103f316a', '', 'INFO', 3801, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:26:06'),
(208, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '21', '5', 'pharmacist', 'pharmacy/dispense', '7e5bfd8e8d482f85', '', 'INFO', 4688, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:26:14'),
(209, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '22', '5', 'pharmacist', 'pharmacy/dispense', 'b995d328e626b514', '', 'INFO', 3051, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:26:32'),
(210, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '23', '5', 'pharmacist', 'pharmacy/dispense', '9c74773e2b1ca361', '', 'INFO', 10840, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:27:01'),
(211, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '32', '5', 'pharmacist', 'pharmacy/dispense', 'd5b62cb10c482620', '', 'INFO', 5334, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:27:22'),
(212, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '31', '5', 'pharmacist', 'pharmacy/dispense', '117945cf4dadab32', '', 'INFO', 3099, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:27:30'),
(213, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '24', '5', 'pharmacist', 'pharmacy/dispense', '07337c8db5dfb989', '', 'INFO', 2950, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:27:49'),
(214, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '25', '5', 'pharmacist', 'pharmacy/dispense', '284d322de88b262e', '', 'INFO', 4989, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:27:55'),
(215, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '26', '5', 'pharmacist', 'pharmacy/dispense', '71fa5ac4e3fc12b9', '', 'INFO', 2079, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:27:58'),
(216, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '28', '5', 'pharmacist', 'pharmacy/dispense', '7ad1ef24cc00117e', '', 'INFO', 5821, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:28:06'),
(217, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '29', '5', 'pharmacist', 'pharmacy/dispense', 'e5c1140555ee73d5', '', 'INFO', 5391, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:28:12'),
(218, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '30', '5', 'pharmacist', 'pharmacy/dispense', 'ff4693e9eb78d1b8', '', 'INFO', 7012, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:28:56'),
(219, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '7768814dd7e01d30', '88677d7f44a6150146478388ed33db74', 'INFO', 1741, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:38:32'),
(220, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', '81a9f287e19b841c', '88677d7f44a6150146478388ed33db74', 'INFO', 5394, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:38:38'),
(221, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', 'f60e1d57b0248e47', '08ec8b25f4b53a4a0a985dd71184c29d', 'INFO', 1164, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:41:18'),
(222, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '88e0f35aab649f74', '08ec8b25f4b53a4a0a985dd71184c29d', 'INFO', 4737, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:41:23'),
(223, 'Triage Officer 1', 'Started triage', '::1', 'Success', 'audit', 'patient_queue', '5', '7', 'nurse_aid', 'nurse_aid/start_triage', '852810cf44257af8', '', 'INFO', 1349, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:41:29'),
(224, 'Triage Officer 1', 'Captured vitals', '::1', 'Success', 'audit', 'patient_queue', '5', '7', 'nurse_aid', 'nurse_aid/save_vitals', '7abbf8861bf99dec', '', 'INFO', 11122, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:43:25'),
(225, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '7a55ec72300967bb', '2aa00e6097c61295716712e1ab493516', 'INFO', 4851, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:44:10'),
(226, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '855d1315d578a93e', '2aa00e6097c61295716712e1ab493516', 'INFO', 11315, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:44:22'),
(227, 'Sister Betty', 'Completed nurse consultation', '::1', 'Success', 'audit', 'patient_queue', '5', '3', 'nurse', 'nurse/consult_save', '984a3dc93202ac29', '', 'INFO', 14294, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, '{\"prescriptions_added\":2,\"sent_to_pharmacy\":\"no\"}', '2026-02-25 10:53:32'),
(228, 'Sister Betty', 'Completed nurse consultation', '::1', 'Success', 'audit', 'patient_queue', '5', '3', 'nurse', 'nurse/consult_save', 'c00db5e45f16cdf9', '', 'INFO', 15200, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, '{\"prescriptions_added\":2,\"sent_to_pharmacy\":\"yes\"}', '2026-02-25 10:59:21'),
(229, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', 'b8dc0e28c57c061b', '975d3aaeb003a69cf98b02c6555cc3e2', 'INFO', 7372, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 11:00:19'),
(230, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', '9f3993b3a529cb5e', '975d3aaeb003a69cf98b02c6555cc3e2', 'INFO', 6203, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 11:00:26'),
(231, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription_batch', '34,35,36', '5', 'pharmacist', 'pharmacy/dispense', '353531cafafd4915', '', 'INFO', 15747, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 11:02:00'),
(232, 'rec1', 'Login', '192.168.1.180', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '46c4d4a68ae3a23e', '494f058bb056bfe231a7926045c388ec', 'INFO', 7932, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-02-25 11:17:15'),
(233, '4', 'Logout', '192.168.1.180', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', '4de37111a36aa198', '494f058bb056bfe231a7926045c388ec', 'INFO', 11645, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-02-25 11:17:28'),
(234, 'rec1', 'Login', '192.168.26.131', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', 'cdbbe58b405fb6fd', '750dac428ef697c5d6d6df901af7cb39', 'INFO', 2115, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 12:12:14'),
(235, '4', 'Logout', '192.168.26.131', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', 'b20ac00a5fe3b301', '750dac428ef697c5d6d6df901af7cb39', 'INFO', 5413, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 12:12:21'),
(236, 'nurse1', 'Login', '192.168.26.131', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '44dfe65a3ab3f996', 'eaa517cf1b9b2aa58c7392853f23cbb6', 'INFO', 2082, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 12:13:10'),
(237, '3', 'Logout', '192.168.26.131', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '6174010632c445f4', 'eaa517cf1b9b2aa58c7392853f23cbb6', 'INFO', 7008, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 12:13:18'),
(238, 'admin', 'Login', '192.168.26.131', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '5e4aa89947ef82ca', '77c26e526168fb69893cfcf6076dfbd1', 'INFO', 2520, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 12:41:08'),
(239, '1', 'Logout', '192.168.26.131', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', '8bb38f6d51f700f0', '77c26e526168fb69893cfcf6076dfbd1', 'INFO', 5780, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 12:41:14'),
(240, '1', 'Logout', '192.168.26.131', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', 'f6a1f4dd7f24195f', '77c26e526168fb69893cfcf6076dfbd1', 'INFO', 6608, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 12:41:15'),
(241, 'nurse1', 'Login', '192.168.26.131', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '0a3c1b9ce35dac36', 'c5b8d2fff1cbef8a975f86524aa02fee', 'INFO', 6898, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 12:45:38'),
(242, '3', 'Logout', '192.168.26.131', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '2d2a75ce07eff642', 'c5b8d2fff1cbef8a975f86524aa02fee', 'INFO', 6278, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 12:45:46'),
(243, 'admin', 'Login', '192.168.26.131', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '455646a633b6110a', 'b029cfcba2daf1715c52c82b7d4bddad', 'INFO', 1501, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 13:00:41'),
(244, '1', 'Logout', '192.168.26.131', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', 'de20b6e603e0b28c', 'b029cfcba2daf1715c52c82b7d4bddad', 'INFO', 9481, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 13:00:52'),
(245, 'nurse1', 'Login', '192.168.26.131', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '3243d8d1c5529c3b', '8d0892bf130ba3658a4824a47f795775', 'INFO', 2382, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 13:03:37'),
(246, '3', 'Logout', '192.168.26.131', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '960123042ba164be', '8d0892bf130ba3658a4824a47f795775', 'INFO', 6043, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 13:03:44'),
(247, '3', 'Logout', '192.168.26.131', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '1ba4607ae6421a1b', '8d0892bf130ba3658a4824a47f795775', 'INFO', 6583, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 13:04:54'),
(248, 'nurse1', 'Login', '192.168.26.131', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', 'b2e556aced906c9d', '8c22539158f69e077592360bb28cd861', 'INFO', 6251, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 14:09:25'),
(249, '3', 'Logout', '192.168.26.131', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '1263a8cd02a89832', '8c22539158f69e077592360bb28cd861', 'INFO', 12999, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 14:09:39'),
(250, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', 'e8fe7620492d0aa5', '6cca3988c1c58a9505a5bdf27486fafb', 'INFO', 7461, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-26 16:08:44'),
(251, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'f4513ce49f7d4ed7', '6cca3988c1c58a9505a5bdf27486fafb', 'INFO', 7880, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-26 16:08:53'),
(252, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '4826f587476e4aa9', 'b1982af2cde4bc5db02ce953de9a4b9f', 'INFO', 2586, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 10:40:56'),
(253, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', 'db633a6278bceb74', 'b1982af2cde4bc5db02ce953de9a4b9f', 'INFO', 4083, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 10:41:01'),
(254, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '95dbf63af5336c00', '24c4251c98e2b48b0821eda54d3b1cc2', 'INFO', 4690, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 10:46:18'),
(255, '1', 'Logout', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', 'de97ef30dace805d', '24c4251c98e2b48b0821eda54d3b1cc2', 'INFO', 6785, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 10:46:26'),
(256, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'b156cae8ccfec12c', 'e6d5dcd86dd7086bd8d6b7617d8b445c', 'INFO', 3103, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 11:08:11'),
(257, '1', 'Logout', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', 'dab96b80e2e1c136', 'e6d5dcd86dd7086bd8d6b7617d8b445c', 'INFO', 6745, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 11:08:19'),
(258, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '774006185f3c8d3e', '3d395269ef30639d84c70c50a14f67c9', 'INFO', 1692, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 12:56:03'),
(259, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'b430c8a73b29cff7', '3d395269ef30639d84c70c50a14f67c9', 'INFO', 6126, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 12:56:10'),
(260, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '02281c4ea59ac88f', '3062623318dccd86e1a3502aa5d4c3aa', 'INFO', 2377, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 12:59:12'),
(261, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', 'c56660f219658243', '3062623318dccd86e1a3502aa5d4c3aa', 'INFO', 8256, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 12:59:22'),
(262, 'Triage1', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'Triage1', NULL, NULL, 'auth/login', 'f897da1693a947c6', '', 'WARN', 1322, 401, NULL, 'Password mismatch', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:00:19'),
(263, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '27e110e069f45e3e', '79b913687b7a13b9411a0a266dc62b5a', 'INFO', 2516, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:00:50'),
(264, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '7ead622f7f71b1d6', '79b913687b7a13b9411a0a266dc62b5a', 'INFO', 5840, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:00:57'),
(265, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', 'ec46a19554dea18a', '3a1237ead5020dd7fbc6e2128ff27610', 'INFO', 1407, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:05:40'),
(266, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', '40b4fc8359d60483', '3a1237ead5020dd7fbc6e2128ff27610', 'INFO', 10659, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:05:52'),
(267, 'Mike Meds', 'Deleted pending medication', '::1', 'Success', 'audit', 'prescription', '33', '5', 'pharmacist', 'pharmacy/delete', '65fbefa40db7b6de', '', 'INFO', 6688, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:06:15'),
(268, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '2255a14f7b5a5bb1', 'aa8a426d60e4c13c08a3bdf62cf18906', 'INFO', 3389, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:12:43'),
(269, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', 'a39acb960e3ba8b7', 'aa8a426d60e4c13c08a3bdf62cf18906', 'INFO', 6817, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:12:51');
INSERT INTO `activity_logs` (`id`, `username`, `action`, `ip_address`, `status`, `event_type`, `entity_type`, `entity_id`, `actor_id`, `actor_role`, `source`, `request_id`, `session_id`, `severity`, `duration_ms`, `status_code`, `error_code`, `error_message`, `user_agent`, `device_type`, `client_ip`, `hostname`, `old_values`, `new_values`, `diff_json`, `metadata_json`, `created_at`) VALUES
(270, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', 'e79406ad75af652e', '060e5c68de68d8af12cc824a3a31cd7a', 'INFO', 3548, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:25:02'),
(271, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', 'abc44dfd7f915c8f', '060e5c68de68d8af12cc824a3a31cd7a', 'INFO', 6112, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:25:10'),
(272, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', 'c5ea559424b797e3', '6776a9511783e8d288a0942eb740f01b', 'INFO', 4348, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:25:49'),
(273, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', '97d72bc97846470f', '6776a9511783e8d288a0942eb740f01b', 'INFO', 9774, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:26:00'),
(274, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '113893933f2cbb5c', '7cb92b131f7ca927ad80e462a54ca717', 'INFO', 3331, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:26:35'),
(275, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '9ef88c1ccee4e241', '7cb92b131f7ca927ad80e462a54ca717', 'INFO', 5734, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:26:41'),
(276, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '122ce2df545933a5', '3ce605a91854eb278d77cc99f04431f3', 'INFO', 3586, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:28:16'),
(277, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'b84365a887400a57', '3ce605a91854eb278d77cc99f04431f3', 'INFO', 7323, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:28:24'),
(278, 'rec1', 'Login', '192.168.1.175', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '667c15e6667dbe37', '18a6e1e56121e22d487c690b3a1fe600', 'INFO', 15725, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.175', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:42:59'),
(279, '4', 'Logout', '192.168.1.175', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', 'dff3e60669a992a1', '18a6e1e56121e22d487c690b3a1fe600', 'INFO', 10494, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.175', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:43:11'),
(280, 'triage1', 'Login', '192.168.1.175', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '0239bbdb9c0b9ef1', 'ac8e502da97259d27312348c00ed2165', 'INFO', 3470, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.175', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:45:02'),
(281, '7', 'Logout', '192.168.1.175', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '2c2a515f777ce3f0', 'ac8e502da97259d27312348c00ed2165', 'INFO', 6626, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.175', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:45:10'),
(282, 'rec1', 'Login', '192.168.1.180', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '0d7555cea93d5693', '923459a6a8eef34a8ce4378a323be6d5', 'INFO', 4757, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:50:14'),
(283, '4', 'Logout', '192.168.1.180', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', 'c59221646f394bb9', '923459a6a8eef34a8ce4378a323be6d5', 'INFO', 9025, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:50:25'),
(284, '4', 'Logout', '192.168.1.180', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', '6a9733282bf5eac5', '923459a6a8eef34a8ce4378a323be6d5', 'INFO', 9910, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:50:25'),
(285, 'triage1', 'Login', '192.168.1.180', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '9d40ea467d10f8ae', '65c6fcfb70beab7d26c85fa9eb99f035', 'INFO', 3224, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:51:49'),
(286, '7', 'Logout', '192.168.1.180', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', 'c7bfafac057ca9d6', '65c6fcfb70beab7d26c85fa9eb99f035', 'INFO', 5991, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:51:56'),
(287, 'Triage Officer 1', 'Started triage', '192.168.1.180', 'Success', 'audit', 'patient_queue', '8', '7', 'nurse_aid', 'nurse_aid/start_triage', '2f8bec27537adb38', '', 'INFO', 3389, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:52:17'),
(288, 'nurse1', 'Login', '192.168.1.180', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '14317623fc718189', '4b917345e6df546642940e70da1ec8fb', 'INFO', 1309, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:52:39'),
(289, '3', 'Logout', '192.168.1.180', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'f7f9cc56607465b6', '4b917345e6df546642940e70da1ec8fb', 'INFO', 6459, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:52:47'),
(290, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '3754fac7da971e30', '274898ae76c2be159d33694af2686a8c', 'INFO', 8522, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:54:56'),
(291, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', 'f081b66dafc8a2cf', '274898ae76c2be159d33694af2686a8c', 'INFO', 7775, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:55:05'),
(292, 'Triage Officer 1', 'Started triage', '::1', 'Success', 'audit', 'patient_queue', '7', '7', 'nurse_aid', 'nurse_aid/start_triage', '991c98c22b959b1f', '', 'INFO', 5534, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:55:13'),
(293, 'Triage Officer 1', 'Captured vitals', '::1', 'Success', 'audit', 'patient_queue', '7', '7', 'nurse_aid', 'nurse_aid/save_vitals', '0013c661173ec586', '', 'INFO', 7753, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:55:43'),
(294, 'Triage Officer 1', 'Started triage', '::1', 'Success', 'audit', 'patient_queue', '8', '7', 'nurse_aid', 'nurse_aid/start_triage', '67953ae6b162fc8b', '', 'INFO', 5573, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:56:09'),
(295, 'Triage Officer 1', 'Captured vitals', '::1', 'Success', 'audit', 'patient_queue', '8', '7', 'nurse_aid', 'nurse_aid/save_vitals', '06c45201fcaf67a5', '', 'INFO', 7703, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:56:38'),
(296, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '4f8b33ab5952fbc8', '9c74d02d37f9443b7436e1a3372d1b34', 'INFO', 4761, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:57:02'),
(297, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'c74ae6ba6eff6b82', '9c74d02d37f9443b7436e1a3372d1b34', 'INFO', 8915, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:57:12'),
(298, 'Sister Betty', 'Completed nurse consultation', '::1', 'Success', 'audit', 'patient_queue', '7', '3', 'nurse', 'nurse/consult_save', 'e9dd41fff95d6f3e', '', 'INFO', 7689, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, '{\"prescriptions_added\":2,\"sent_to_pharmacy\":\"yes\"}', '2026-03-09 13:59:58'),
(299, 'doc1', 'Login', '::1', 'Success', 'audit', 'user', '2', '2', 'doctor', 'auth/login', '57a20c724ce4251c', 'e52a3a54452d18207fc0c22441938928', 'INFO', 4845, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 14:01:01'),
(300, '2', 'Logout', '::1', 'Success', 'audit', 'user', '2', '2', 'doctor', 'auth/logout', 'a25e71396eda1970', 'e52a3a54452d18207fc0c22441938928', 'INFO', 9683, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 14:03:28'),
(301, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '96f440985804d2f9', '6b9e963ab4af00671d5bddb7dd777bb7', 'INFO', 10685, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 14:03:34'),
(302, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '08eb31f4d9599f6a', 'cba1cf09f731ba926b1b612a5cbab03c', 'INFO', 1855, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 16:17:55'),
(303, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'cd76c3145e38f923', 'cba1cf09f731ba926b1b612a5cbab03c', 'INFO', 5728, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 16:18:02'),
(304, 'rec1', 'Login', '192.168.25.61', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '7e2aa00c50cc5045', '1e9755283c2b121f08af1e3eb6c7b93c', 'INFO', 4969, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.25.61', NULL, NULL, NULL, NULL, NULL, '2026-03-10 09:01:14'),
(305, '4', 'Logout', '192.168.25.61', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', '2be6107ed96a519a', '1e9755283c2b121f08af1e3eb6c7b93c', 'INFO', 8519, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.25.61', NULL, NULL, NULL, NULL, NULL, '2026-03-10 09:01:23'),
(306, 'nurse1', 'Login', '192.168.25.61', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '6d7fdfb78056c738', '4abf849326e5eef88fac46ab7f461094', 'INFO', 2862, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.25.61', NULL, NULL, NULL, NULL, NULL, '2026-03-10 09:02:37'),
(307, '3', 'Logout', '192.168.25.61', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'fa1b7ec7569f69d2', '4abf849326e5eef88fac46ab7f461094', 'INFO', 9156, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.25.61', NULL, NULL, NULL, NULL, NULL, '2026-03-10 09:02:47');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `scheduled_at` datetime NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'scheduled',
  `reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `username` varchar(150) NOT NULL,
  `action` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Success',
  `event_type` varchar(50) DEFAULT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` varchar(64) DEFAULT NULL,
  `actor_id` varchar(64) DEFAULT NULL,
  `actor_role` varchar(50) DEFAULT NULL,
  `source` varchar(150) DEFAULT NULL,
  `request_id` varchar(64) DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `severity` varchar(20) DEFAULT NULL,
  `duration_ms` int(11) DEFAULT NULL,
  `status_code` int(11) DEFAULT NULL,
  `error_code` varchar(50) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_type` varchar(30) DEFAULT NULL,
  `client_ip` varchar(45) DEFAULT NULL,
  `hostname` varchar(255) DEFAULT NULL,
  `old_values` longtext DEFAULT NULL,
  `new_values` longtext DEFAULT NULL,
  `diff_json` longtext DEFAULT NULL,
  `metadata_json` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `username`, `action`, `ip_address`, `status`, `event_type`, `entity_type`, `entity_id`, `actor_id`, `actor_role`, `source`, `request_id`, `session_id`, `severity`, `duration_ms`, `status_code`, `error_code`, `error_message`, `user_agent`, `device_type`, `client_ip`, `hostname`, `old_values`, `new_values`, `diff_json`, `metadata_json`, `created_at`) VALUES
(1, 'admin', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'admin', NULL, NULL, 'auth/login', '689b40d22c99c697', '', 'WARN', 2419, 401, NULL, 'User not found', NULL, NULL, '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 13:40:45'),
(2, 'admin', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'admin', NULL, NULL, 'auth/login', 'f8b34b992aace8c5', '', 'WARN', 4578, 401, NULL, 'User not found', NULL, NULL, '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 13:57:25'),
(3, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'f62caffda4958c78', 'afb4b8ab5c20387b805083c1d7dc6d0b', 'INFO', 1294, 200, NULL, NULL, NULL, NULL, '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 14:18:05'),
(4, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '167405e4a22d9db3', 'c4e84f0f3f042ff0a8da525013d1e300', 'INFO', 1066, 200, NULL, NULL, NULL, NULL, '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 14:18:06'),
(5, 'admin@gmail.com', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'admin@gmail.com', NULL, NULL, 'auth/login', '7816f3ccac38bc84', '', 'WARN', 431, 401, NULL, 'User not found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 14:20:16'),
(6, 'admin', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'admin', NULL, NULL, 'auth/login', 'cb48c1ea2335d9d1', '', 'WARN', 862, 401, NULL, 'Password mismatch', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 14:20:38'),
(7, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '799a25cc54bf61bd', '0f8ee02fa25c4d38461fb329cc5c9cde', 'INFO', 603, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 14:20:44'),
(8, '1', 'Logout', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', 'e7abf47f4b3ab1f7', '0f8ee02fa25c4d38461fb329cc5c9cde', 'INFO', 2385, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 14:20:47'),
(9, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '682efbceac623234', '3fce964783899d9b42e0c5eab3bcb094', 'INFO', 2315, 200, NULL, NULL, NULL, NULL, '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 14:25:41'),
(10, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '002fe0b19fd9dc33', '3ae95a38ca97298762b1d329bf65a2a3', 'INFO', 2384, 200, NULL, NULL, NULL, NULL, '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 14:35:30'),
(11, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '9aed39d9822fe943', '71403ada69e43447682fa8d7356145ab', 'INFO', 1282, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 16:12:02'),
(12, '1', 'Logout', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', '67c0985442f97718', '71403ada69e43447682fa8d7356145ab', 'INFO', 3190, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-19 16:12:06'),
(13, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '4c06403994c10f3d', 'b93cd4f474b3f18ceb2998a0049c2a0a', 'INFO', 1743, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 06:48:47'),
(14, '1', 'Logout', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', 'f226581767f3ef75', 'b93cd4f474b3f18ceb2998a0049c2a0a', 'INFO', 3388, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 06:48:51'),
(15, 'rec1', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'rec1', NULL, NULL, 'auth/login', 'f684e624b73cf5d9', '', 'WARN', 3287, 401, NULL, 'Password mismatch', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 06:49:47'),
(16, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', 'e1042b016afb91c8', 'be1d2567f644ef86f00c39a7fa91a321', 'INFO', 2968, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 06:49:55'),
(17, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', '0ee2193aa96f1cc8', 'be1d2567f644ef86f00c39a7fa91a321', 'INFO', 5271, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 06:50:01'),
(18, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'aadbd399cacf9566', '90b754b03001b10b43914589aa32ae34', 'INFO', 3990, 200, NULL, NULL, NULL, NULL, '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 07:33:26'),
(19, 'nurse1', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'nurse1', NULL, NULL, 'auth/login', '6bc71fe9c1fb40e7', '', 'WARN', 1454, 401, NULL, 'Password mismatch', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 07:44:38'),
(20, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', 'b8cbd7d9acbba992', '7867fb2616bd445ca90619f9c53bde77', 'INFO', 1268, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 07:44:44'),
(21, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '30e61dc86482f731', '7867fb2616bd445ca90619f9c53bde77', 'INFO', 2825, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 07:44:48'),
(22, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '9f7e568ef23d1f8b', '7739d75ad221f08295bdcca5ee7605bb', 'INFO', 1475, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:09:36'),
(23, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', '3fb92589e83c3369', '7739d75ad221f08295bdcca5ee7605bb', 'INFO', 4826, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:09:42'),
(24, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'b90cc9d26bc3ef62', '86f7ffded09399b5a471560002f40f64', 'INFO', 1197, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:27:46'),
(25, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'd23156e41b7d99c9', '2ee8c9dee48c93fb8ae475775d3a26e1', 'INFO', 4309, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:28:29'),
(26, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '4d88df1deac83c72', '06258dd0101e3d3ba39c64c7709f9179', 'INFO', 5520, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:34:29'),
(27, 'triage1', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'triage1', NULL, NULL, 'auth/login', 'f761988592b8ceb8', '', 'WARN', 1524, 401, NULL, 'User not found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:37:06'),
(28, 'itsup1', 'Login', '::1', 'Success', 'audit', 'user', '6', '6', 'it_support', 'auth/login', 'b97e9d5a1c2eee02', '83a3a67eee9d1da5767e70350450abce', 'INFO', 1236, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:40:08'),
(29, '6', 'Logout', '::1', 'Success', 'audit', 'user', '6', '6', 'it_support', 'auth/logout', 'a8a8d5ff6536e502', '83a3a67eee9d1da5767e70350450abce', 'INFO', 3183, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:40:12'),
(30, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', 'f8811dafa72ed0d1', 'e67dad0f247bacb6a0f8061577aa3209', 'INFO', 1962, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:40:31'),
(31, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '26e512a8d110d32f', 'e67dad0f247bacb6a0f8061577aa3209', 'INFO', 2676, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:40:35'),
(32, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '251b63c059718187', 'e67dad0f247bacb6a0f8061577aa3209', 'INFO', 2607, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:44:07'),
(33, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'd8aa7f660920ff8d', '1f12607ab9dd34c2f495df564a919037', 'INFO', 1138, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:44:19'),
(34, '1', 'Logout', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', '46857278fd4300f5', '1f12607ab9dd34c2f495df564a919037', 'INFO', 4127, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:44:25'),
(35, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '21d320a374f446f5', 'fa6c58366eef76a1c4de265514e3012e', 'INFO', 3127, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:47:48'),
(36, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', 'ce8a4cf71e76e6ed', '6759571188a3e090d21e55365fa7bc33', 'INFO', 2578, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:48:28'),
(37, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '9b658e4152dd7681', '6759571188a3e090d21e55365fa7bc33', 'INFO', 4747, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:48:33'),
(38, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '19a730c166a38e61', '6759571188a3e090d21e55365fa7bc33', 'INFO', 5034, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:48:34'),
(39, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '3c3c627068e5efaa', 'de961a96326cb8dcb2d898a4ca7b99ba', 'INFO', 1144, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:49:18'),
(40, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', '8d6272f49d0d6c6e', 'de961a96326cb8dcb2d898a4ca7b99ba', 'INFO', 3014, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:49:22'),
(41, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', 'cfff7249e4be8381', '8438dca15834091a6d8e1c0007405ecc', 'INFO', 1170, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:49:58'),
(42, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '04477b08face82db', '8438dca15834091a6d8e1c0007405ecc', 'INFO', 3831, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:50:03'),
(43, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '843cb591fc578d96', '8438dca15834091a6d8e1c0007405ecc', 'INFO', 4269, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:50:04'),
(44, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '9102322d1676e3a9', '096a9cff06c4e89f5e347db6ca0afcae', 'INFO', 3683, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:56:07'),
(45, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', 'e6a1c582d7a4b05a', 'f440f5fee130461006ed4a60cba3d817', 'INFO', 4399, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:56:11'),
(46, 'Triage Officer 1', 'Captured vitals', '::1', 'Success', 'audit', 'patient_queue', '1', '7', 'nurse_aid', 'nurse_aid/save_vitals', 'bc9fe1aca5822532', '', 'INFO', 4795, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:56:28'),
(47, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'ac55667b07ad1d14', '3ebca70cab3c0b7b305851d4ab7afd3d', 'INFO', 960, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:57:03'),
(48, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '64fc06d2b4ada2b8', '20e40f47776a1c9578f4d805f6fadc54', 'INFO', 795, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:57:04'),
(49, 'Triage Officer 1', 'Captured vitals', '::1', 'Success', 'audit', 'patient_queue', '2', '7', 'nurse_aid', 'nurse_aid/save_vitals', '9945b7bc2a2b19ae', '', 'INFO', 1472, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:57:11'),
(50, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'e26f4b36b7acd0a7', '3b00ef463288e0ecc98ffab31cf310cf', 'INFO', 1491, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-GB) WindowsPowerShell/5.1.26100.7705', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 08:58:50'),
(51, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '6972a0836397aa3f', '33778f9ac666e63edacc3e8df2424acc', 'INFO', 1244, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:00:47'),
(52, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', 'b3cbdb80b355de13', '33778f9ac666e63edacc3e8df2424acc', 'INFO', 4113, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:00:52'),
(53, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '34c8242b5c21c931', '707432442910290babd1491d15142f20', 'INFO', 1773, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:01:19'),
(54, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', 'b21b36928a593268', '707432442910290babd1491d15142f20', 'INFO', 4567, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:01:25'),
(55, 'triage', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'triage', NULL, NULL, 'auth/login', '5fd1bf5c72ecfe91', '', 'WARN', 1908, 401, NULL, 'User not found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:02:08'),
(56, 'triage', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'triage', NULL, NULL, 'auth/login', '89354fee740374a4', '', 'WARN', 1949, 401, NULL, 'User not found', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:02:20'),
(57, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', 'a781fa35d7afd677', '86cdbf97c6be5a6afe548f964a184fdb', 'INFO', 2057, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:02:28'),
(58, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '17f27dffaf0d7503', '86cdbf97c6be5a6afe548f964a184fdb', 'INFO', 5719, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:02:35'),
(59, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '0a63a376fc932a20', '86cdbf97c6be5a6afe548f964a184fdb', 'INFO', 5694, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:02:35'),
(60, 'Triage Officer 1', 'Started triage', '::1', 'Success', 'audit', 'patient_queue', '3', '7', 'nurse_aid', 'nurse_aid/start_triage', '39736abe88267a76', '', 'INFO', 1263, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:02:43'),
(61, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '7f1b6a4a3662eff2', '86cdbf97c6be5a6afe548f964a184fdb', 'INFO', 9159, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:14:18'),
(62, 'Triage Officer 1', 'Started triage', '::1', 'Success', 'audit', 'patient_queue', '3', '7', 'nurse_aid', 'nurse_aid/start_triage', '3cde52cc9f685f7f', '', 'INFO', 4631, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:14:25'),
(63, 'Triage Officer 1', 'Started triage', '::1', 'Success', 'audit', 'patient_queue', '3', '7', 'nurse_aid', 'nurse_aid/start_triage', '396440bfa4a18f51', '', 'INFO', 3261, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:17:48'),
(64, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '3deb680207b7b0b5', 'bb2088be3d6a28204f0d234d9c4d1239', 'INFO', 1592, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:45:30'),
(65, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '619730c6affff12c', 'bb2088be3d6a28204f0d234d9c4d1239', 'INFO', 3035, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:45:34'),
(66, 'Triage Officer 1', 'Started triage', '::1', 'Success', 'audit', 'patient_queue', '3', '7', 'nurse_aid', 'nurse_aid/start_triage', 'fe9d331be34f44e8', '', 'INFO', 3387, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:45:39'),
(67, 'Triage Officer 1', 'Captured vitals', '::1', 'Success', 'audit', 'patient_queue', '3', '7', 'nurse_aid', 'nurse_aid/save_vitals', 'c257d33299657f7a', '', 'INFO', 2163, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:47:10'),
(68, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '9095532c759310bf', '90327cc71e6485ac97fd6f79fe0c4981', 'INFO', 2624, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:52:28'),
(69, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'a08b5861a6ad6209', '90327cc71e6485ac97fd6f79fe0c4981', 'INFO', 2614, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:53:08'),
(70, 'doc1', 'Login', '::1', 'Success', 'audit', 'user', '2', '2', 'doctor', 'auth/login', '0405fe9f7a3d2cdf', '53dce8f4596c7ef2be6f1e38884596b0', 'INFO', 2234, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:53:22'),
(71, '2', 'Logout', '::1', 'Success', 'audit', 'user', '2', '2', 'doctor', 'auth/logout', '8ff0386062431d03', '53dce8f4596c7ef2be6f1e38884596b0', 'INFO', 5683, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 09:53:29'),
(72, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '8d8719775705737c', '249543d683e975c5127742dd48c498d7', 'INFO', 2272, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:14:53'),
(73, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '88bf1048814c21ce', '249543d683e975c5127742dd48c498d7', 'INFO', 6813, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:15:01'),
(74, 'Sister Betty', 'Completed nurse consultation', '::1', 'Success', 'audit', 'patient_queue', '3', '3', 'nurse', 'nurse/consult_save', '6034dacd311af5af', '', 'INFO', 2446, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, '{\"prescriptions_added\":0,\"sent_to_pharmacy\":\"yes\"}', '2026-02-20 10:22:20'),
(75, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', 'ee89aa9e4c4a0dde', '6e4d5407edc78d52b7c9a41141e896c6', 'INFO', 1518, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:00'),
(76, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', '6595d4136951720a', '6e4d5407edc78d52b7c9a41141e896c6', 'INFO', 3725, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:05'),
(77, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '1', '5', 'pharmacist', 'pharmacy/nurse_request_update', 'ba7fd9bfbe9b1fa0', '', 'INFO', 6662, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:23'),
(78, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '1', '5', 'pharmacist', 'pharmacy/nurse_request_update', '59129d1ab46b6a3d', '', 'INFO', 5941, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:27'),
(79, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '1', '5', 'pharmacist', 'pharmacy/nurse_request_update', 'fbd3b497d4295719', '', 'INFO', 6318, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:29'),
(80, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '1', '5', 'pharmacist', 'pharmacy/nurse_request_update', '92417e594fe6d8eb', '', 'INFO', 6686, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:33'),
(81, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '1', '5', 'pharmacist', 'pharmacy/nurse_request_update', 'a73ee6cc0c90a376', '', 'INFO', 7176, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:35'),
(82, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '1', '5', 'pharmacist', 'pharmacy/nurse_request_update', '7f6979f8a198ea21', '', 'INFO', 5572, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:35'),
(83, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '1', '5', 'pharmacist', 'pharmacy/nurse_request_update', 'f03b9cf4e80f7197', '', 'INFO', 3460, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:36'),
(84, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '1', '5', 'pharmacist', 'pharmacy/nurse_request_update', '76ec2546ba420ae3', '', 'INFO', 3191, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:36'),
(85, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '1', '5', 'pharmacist', 'pharmacy/nurse_request_update', '61c4b758e40b5456', '', 'INFO', 2974, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:23:36'),
(86, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', 'bdc0d54d737471b6', 'c8fb44a2bd41e1a4e9c6091c7289f045', 'INFO', 5786, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:27:15'),
(87, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '6f23021f21a30832', 'c8fb44a2bd41e1a4e9c6091c7289f045', 'INFO', 4310, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:27:21'),
(88, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '9a35fc6f758d2b02', 'c8fb44a2bd41e1a4e9c6091c7289f045', 'INFO', 4520, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:27:21'),
(89, 'Sister Betty', 'Completed nurse consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '3', 'nurse', 'nurse/consult_save', '807c0c88c93fa42f', '', 'INFO', 1583, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, '{\"prescriptions_added\":0,\"sent_to_pharmacy\":\"no\"}', '2026-02-20 10:30:57'),
(90, 'Sister Betty', 'Completed nurse consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '3', 'nurse', 'nurse/consult_save', '4628a8daec46e015', '', 'INFO', 2902, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, '{\"prescriptions_added\":0,\"sent_to_pharmacy\":\"yes\"}', '2026-02-20 10:31:38'),
(91, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', 'f4bada3de11a710b', 'ba0c24ef82b86d7f7146bdf68b571552', 'INFO', 3171, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:31:54'),
(92, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', 'aabf1ee1539a09b4', 'ba0c24ef82b86d7f7146bdf68b571552', 'INFO', 3424, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:31:58'),
(93, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '2', '5', 'pharmacist', 'pharmacy/nurse_request_update', '7a917e98d696e761', '', 'INFO', 5634, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:32:14'),
(94, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '2', '5', 'pharmacist', 'pharmacy/nurse_request_update', '3a25b1bb837807c2', '', 'INFO', 5304, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:32:16'),
(95, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '2', '5', 'pharmacist', 'pharmacy/nurse_request_update', 'ce66a65698a87447', '', 'INFO', 3467, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:32:17'),
(96, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '8ade81c627b3fc7b', '1ff55526af78d1aae39e43729e74dfb1', 'INFO', 1217, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:35:15'),
(97, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '893aa8565e4ea480', '1ff55526af78d1aae39e43729e74dfb1', 'INFO', 4427, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 10:35:21'),
(98, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', 'cf6313c4cc13a885', '88b88603ec3c71627463f18f45b0cab9', 'INFO', 1565, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:05:05'),
(99, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', '92fbaeb2cda45599', '88b88603ec3c71627463f18f45b0cab9', 'INFO', 4914, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:05:11'),
(100, 'triage1', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'triage1', NULL, NULL, 'auth/login', '6cb22f4adbc1a8d7', '', 'WARN', 803, 401, NULL, 'Password mismatch', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:07:04'),
(101, 'triage1', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'triage1', NULL, NULL, 'auth/login', 'a2b04b7567b7433e', '', 'WARN', 859, 401, NULL, 'Password mismatch', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:07:18'),
(102, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '2acd0bb6bd71bcd3', '6071b9dd437b54cc398bf615d3212a2f', 'INFO', 812, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:07:34'),
(103, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '90bd128d1fea8304', '6071b9dd437b54cc398bf615d3212a2f', 'INFO', 2407, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:07:37'),
(104, 'Triage Officer 1', 'Started triage', '::1', 'Success', 'audit', 'patient_queue', '4', '7', 'nurse_aid', 'nurse_aid/start_triage', '867f7edcbdd522c0', '', 'INFO', 726, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:07:57'),
(105, 'Triage Officer 1', 'Captured vitals', '::1', 'Success', 'audit', 'patient_queue', '4', '7', 'nurse_aid', 'nurse_aid/save_vitals', '3af9115d2ca73354', '', 'INFO', 673, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:08:28'),
(106, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '2e59cac2bb239b30', '6a3f28909bbe14831295c41baf450dbf', 'INFO', 803, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:08:54'),
(107, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '7611015b378b09fd', '6a3f28909bbe14831295c41baf450dbf', 'INFO', 3070, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:08:58'),
(108, 'Sister Betty', 'Completed nurse consultation', '::1', 'Success', 'audit', 'patient_queue', '4', '3', 'nurse', 'nurse/consult_save', 'b08278de4279b694', '', 'INFO', 1707, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, '{\"prescriptions_added\":1,\"sent_to_pharmacy\":\"yes\"}', '2026-02-20 11:21:01'),
(109, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', 'c4c361ed0cf49c2e', 'b2acbdbef38df14977376de8b1f8a965', 'INFO', 885, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:21:46'),
(110, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', 'd5db473c946cf23b', 'b2acbdbef38df14977376de8b1f8a965', 'INFO', 3287, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:21:51'),
(111, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '62b24751e26f0ea7', '23a2f211be2e3f3dc8114cf459d9c950', 'INFO', 1208, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:46:18'),
(112, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '2ad6f6114967be5d', '23a2f211be2e3f3dc8114cf459d9c950', 'INFO', 3849, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:46:23'),
(113, 'Sister Betty', 'Completed nurse consultation', '::1', 'Success', 'audit', 'patient_queue', '3', '3', 'nurse', 'nurse/consult_save', '7adf27d2778385bc', '', 'INFO', 2124, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, '{\"prescriptions_added\":3,\"sent_to_pharmacy\":\"yes\"}', '2026-02-20 11:48:14'),
(114, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', '4fc236c5a75b982a', 'a279afde05a277b9099a2878a4d57901', 'INFO', 1992, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:48:35'),
(115, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', '4e3508ef8a6540e2', 'a279afde05a277b9099a2878a4d57901', 'INFO', 4520, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:48:41'),
(116, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', 'fcfa9682a2741600', 'a279afde05a277b9099a2878a4d57901', 'INFO', 2185, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:49:26'),
(117, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '67b448b4bbf58378', '1e96820a59a98625964cff083ce5ef11', 'INFO', 967, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:49:35'),
(118, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '58967d8e9a21ae6a', '1e96820a59a98625964cff083ce5ef11', 'INFO', 5288, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 11:49:41'),
(119, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '7f226855f10655fd', '0d60a4fe3ef0aa87f08a69e87a5da07a', 'INFO', 1542, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 12:22:19'),
(120, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '31476318f79f778c', '0d60a4fe3ef0aa87f08a69e87a5da07a', 'INFO', 6710, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 12:22:27'),
(121, 'doc1', 'Login', '::1', 'Success', 'audit', 'user', '2', '2', 'doctor', 'auth/login', '8d6ed62e13722500', 'c865fce1c30c986344c2251aa5663fcc', 'INFO', 2206, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 12:24:09'),
(122, '2', 'Logout', '::1', 'Success', 'audit', 'user', '2', '2', 'doctor', 'auth/logout', 'ae557af5ccfb7807', 'c865fce1c30c986344c2251aa5663fcc', 'INFO', 5348, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 12:24:15'),
(123, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', 'cd6efd8b3276fc18', '57cbcc9457e67a735bc21d5233b94c1f', 'INFO', 1746, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 12:25:56'),
(124, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '67d8df07aa43124f', '57cbcc9457e67a735bc21d5233b94c1f', 'INFO', 6666, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 12:26:03'),
(125, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '2654d671b634bc20', '2a11ddb5e52ad9b53309666444effc35', 'INFO', 1733, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:00:59'),
(126, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '363e20d792f68b8f', '2a11ddb5e52ad9b53309666444effc35', 'INFO', 5197, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:01:06'),
(127, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'f89a671266fd71ef', 'f0ab94db7beec8a75444b9aae79909ba', 'INFO', 1081, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:01:26'),
(128, '1', 'Logout', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', 'c73b6c7370b96d45', 'f0ab94db7beec8a75444b9aae79909ba', 'INFO', 2054, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:01:29'),
(129, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '021e6521e4995a41', '5a2a926fc38af15e03e42669ecb06043', 'INFO', 1338, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:02:11'),
(130, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', 'b55ca77bd8a0cde8', '5a2a926fc38af15e03e42669ecb06043', 'INFO', 5442, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:02:18'),
(131, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', 'e6e7ba0ac01faeec', '5a2a926fc38af15e03e42669ecb06043', 'INFO', 2747, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:02:33'),
(132, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '144957fb04cec14b', '5a2a926fc38af15e03e42669ecb06043', 'INFO', 2861, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:02:33'),
(133, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', 'ad5af5d538b9d2b7', 'e7a66883a837668b5bb00b51f08724e2', 'INFO', 1346, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:03:41'),
(134, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'ffd0f4a10e03c18d', 'e7a66883a837668b5bb00b51f08724e2', 'INFO', 4705, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:03:47'),
(135, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'a3675a8a42be0fe3', '73ee2a673b63ab23e36a76f76d9a991a', 'INFO', 874, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:08:46'),
(136, '1', 'Logout', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', 'e71ddaf31fc67058', '73ee2a673b63ab23e36a76f76d9a991a', 'INFO', 2233, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:08:50'),
(137, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', 'ad4749a958897783', '3d8f5777b85848d0f86af5bdd74d7922', 'INFO', 1575, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:21:55');
INSERT INTO `audit_logs` (`id`, `username`, `action`, `ip_address`, `status`, `event_type`, `entity_type`, `entity_id`, `actor_id`, `actor_role`, `source`, `request_id`, `session_id`, `severity`, `duration_ms`, `status_code`, `error_code`, `error_message`, `user_agent`, `device_type`, `client_ip`, `hostname`, `old_values`, `new_values`, `diff_json`, `metadata_json`, `created_at`) VALUES
(138, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'bd006b25cca2ca61', '3d8f5777b85848d0f86af5bdd74d7922', 'INFO', 4091, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:22:01'),
(139, 'admin', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'admin', NULL, NULL, 'auth/login', 'd8acb0816a2a2e52', '', 'WARN', 1300, 401, NULL, 'Password mismatch', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:22:28'),
(140, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '21c83db02fb8f885', '7837cb55a4f966f0d4b51c0d8f7cba46', 'INFO', 2197, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:22:31'),
(141, '1', 'Logout', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', '44707c5761e91b4a', '7837cb55a4f966f0d4b51c0d8f7cba46', 'INFO', 5234, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:22:38'),
(142, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '514f5d1605f8e4d5', 'ef36ee3217d9cc0511cac83bdb6b0134', 'INFO', 1586, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:50:07'),
(143, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '7603cd3940a10010', 'ef36ee3217d9cc0511cac83bdb6b0134', 'INFO', 4380, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-20 13:50:13'),
(144, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', 'b9bd70a0799b1f27', '3382310fd936bc4a98512de479a2cc93', 'INFO', 7043, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:20:01'),
(145, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', 'a155420cded94348', '3382310fd936bc4a98512de479a2cc93', 'INFO', 8603, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:20:11'),
(146, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '4313cf5d8fee40f2', '0fe67c81391496f2077580b4e781c691', 'INFO', 3370, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:21:51'),
(147, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '63d8c51cdd4186a9', '0fe67c81391496f2077580b4e781c691', 'INFO', 3670, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:21:56'),
(148, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '3ece7c9891fc31c7', 'f46d8e1d33d36b98f971f7591aaebf2e', 'INFO', 1971, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:22:24'),
(149, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '95dfbce06d8486de', 'f46d8e1d33d36b98f971f7591aaebf2e', 'INFO', 4610, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:22:29'),
(150, 'doc1', 'Login', '::1', 'Success', 'audit', 'user', '2', '2', 'doctor', 'auth/login', '9edd890bc29988f1', '8d410069a6002b7813716cb1a20d4998', 'INFO', 3089, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:23:23'),
(151, '2', 'Logout', '::1', 'Success', 'audit', 'user', '2', '2', 'doctor', 'auth/logout', '149cddf4094ea70e', '8d410069a6002b7813716cb1a20d4998', 'INFO', 7463, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:23:32'),
(152, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '4bd15c27b803d68f', '', 'INFO', 9114, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:24:47'),
(153, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '9f6bd565507fbf29', '', 'INFO', 8758, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:24:48'),
(154, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', 'f03d124cf7494959', '', 'INFO', 8572, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:24:49'),
(155, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '52c89bd264807d28', '', 'INFO', 8452, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:24:49'),
(156, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '5e617435d50c31c8', '', 'INFO', 7527, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:24:52'),
(157, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', 'f0e605d309630528', '', 'INFO', 6891, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:24:54'),
(158, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '6410b111ce326202', '', 'INFO', 7072, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:01'),
(159, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '416fee5bea9c1072', '', 'INFO', 6741, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:02'),
(160, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '9817959d95db797c', '', 'INFO', 5626, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:04'),
(161, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '0cd86a2bfa5c6c5a', '', 'INFO', 8613, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:06'),
(162, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', 'c50edf8a8638e48c', '', 'INFO', 7716, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:07'),
(163, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '20ee18c630a05cf3', '', 'INFO', 9431, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:11'),
(164, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', 'ccfc7813ee5b71c9', '', 'INFO', 9797, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:12'),
(165, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', 'a3435131ffecab23', '', 'INFO', 9418, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:12'),
(166, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '12061a9409f72ff3', '', 'INFO', 9312, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:16'),
(167, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', 'd8298fbe8a306e10', '', 'INFO', 8912, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:17'),
(168, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '3a74536ab07234fe', '', 'INFO', 9093, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:18'),
(169, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', 'af7b28889595f90c', '', 'INFO', 8168, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:20'),
(170, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '1b8b2a2e728ad703', '', 'INFO', 8566, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:22'),
(171, 'Dr. Sarah Moyo', 'Completed consultation', '::1', 'Success', 'audit', 'patient_queue', '2', '2', 'doctor', 'doctor/complete_multiple', '8c8e6afe75192ffd', '', 'INFO', 9022, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:25:22'),
(172, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', '198c35d3d8b1438f', '7b669e840c901dbe52ee66ee5ddfa327', 'INFO', 1800, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:26:26'),
(173, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', '27894df997e05107', '7b669e840c901dbe52ee66ee5ddfa327', 'INFO', 6953, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:26:34'),
(174, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '27', '5', 'pharmacist', 'pharmacy/dispense', '61fb5acefb794323', '', 'INFO', 6613, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:29:59'),
(175, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '27', '5', 'pharmacist', 'pharmacy/dispense', 'bc770abf8f294d73', '', 'INFO', 8051, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:30:04'),
(176, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '8', '5', 'pharmacist', 'pharmacy/dispense', 'cf1923b76e1f53c3', '', 'INFO', 8127, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:30:14'),
(177, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '8', '5', 'pharmacist', 'pharmacy/dispense', '7143bbb3d3231b06', '', 'INFO', 4966, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:30:21'),
(178, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '8', '5', 'pharmacist', 'pharmacy/dispense', '59b586d511ee5798', '', 'INFO', 5269, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:30:22'),
(179, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '9', '5', 'pharmacist', 'pharmacy/dispense', 'fb1a648c99151358', '', 'INFO', 10333, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:30:33'),
(180, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '9', '5', 'pharmacist', 'pharmacy/dispense', '147d5845d391f558', '', 'INFO', 7684, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:30:34'),
(181, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '10', '5', 'pharmacist', 'pharmacy/dispense', '70e456c4e43966e9', '', 'INFO', 7099, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:30:45'),
(182, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '11', '5', 'pharmacist', 'pharmacy/dispense', '7cdc2338a3ec99fc', '', 'INFO', 8400, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:31:29'),
(183, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '11', '5', 'pharmacist', 'pharmacy/dispense', '579644faf3e6f79c', '', 'INFO', 7592, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:31:38'),
(184, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '11', '5', 'pharmacist', 'pharmacy/dispense', '95097c489a38cda8', '', 'INFO', 6836, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:31:39'),
(185, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '11', '5', 'pharmacist', 'pharmacy/dispense', 'af685c28d5ffb860', '', 'INFO', 7196, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:31:40'),
(186, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '3', '5', 'pharmacist', 'pharmacy/nurse_request_update', '52a1200484c5a428', '', 'INFO', 7540, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:32:24'),
(187, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '4', '5', 'pharmacist', 'pharmacy/nurse_request_update', '62cba99849578f70', '', 'INFO', 7632, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:32:25'),
(188, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '3', '5', 'pharmacist', 'pharmacy/nurse_request_update', '59ebd02e171444a0', '', 'INFO', 9896, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:32:47'),
(189, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '4', '5', 'pharmacist', 'pharmacy/nurse_request_update', 'f0bcf922c8d5ae8b', '', 'INFO', 8894, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:32:50'),
(190, 'Mike Meds', 'Updated nurse pharmacy request', '::1', 'Success', 'audit', 'pharmacy_request', '4', '5', 'pharmacist', 'pharmacy/nurse_request_update', 'b104355c06d45677', '', 'INFO', 6745, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:32:57'),
(191, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '5694a6a55b327aa5', '2960fe2e09c50d44fc378a8758b2ba9d', 'INFO', 2910, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:33:17'),
(192, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', 'b1c0781fa075d179', '2960fe2e09c50d44fc378a8758b2ba9d', 'INFO', 7874, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 08:33:27'),
(193, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', '23b7d05788e1c527', '94f202912164542f6fe231bb65f520d1', 'INFO', 8532, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 09:11:56'),
(194, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', 'd4f4d6c8c7795813', '94f202912164542f6fe231bb65f520d1', 'INFO', 13830, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 09:12:12'),
(195, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', 'a8079074a11d438d', 'c4237b98ef2b6c5cae8f8946dc26452e', 'INFO', 7950, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 09:50:46'),
(196, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', '250e0f678b8e3388', 'c4237b98ef2b6c5cae8f8946dc26452e', 'INFO', 14651, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 09:51:02'),
(197, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', 'b2e3a394618e1b3b', '55822f3a72579e4992407c8fe0c87671', 'INFO', 1231, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:24:08'),
(198, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', '6055c66d3f616cb0', '55822f3a72579e4992407c8fe0c87671', 'INFO', 6127, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:24:15'),
(199, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', '59403456957a8259', '55822f3a72579e4992407c8fe0c87671', 'INFO', 6215, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:24:16'),
(200, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '13', '5', 'pharmacist', 'pharmacy/dispense', 'a85d8134b05d2f45', '', 'INFO', 1330, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:24:59'),
(201, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '14', '5', 'pharmacist', 'pharmacy/dispense', '531ae3176c777341', '', 'INFO', 5129, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:25:07'),
(202, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '15', '5', 'pharmacist', 'pharmacy/dispense', 'c51f52ed24f58e95', '', 'INFO', 2165, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:25:10'),
(203, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '16', '5', 'pharmacist', 'pharmacy/dispense', '779306860eab2aa9', '', 'INFO', 4961, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:25:18'),
(204, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '17', '5', 'pharmacist', 'pharmacy/dispense', '89a0d7773f34b8a0', '', 'INFO', 2788, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:25:18'),
(205, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '18', '5', 'pharmacist', 'pharmacy/dispense', '7c4cfbf93e8228d6', '', 'INFO', 2581, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:25:22'),
(206, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '19', '5', 'pharmacist', 'pharmacy/dispense', '363831c88261cf7d', '', 'INFO', 2888, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:25:25'),
(207, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '20', '5', 'pharmacist', 'pharmacy/dispense', '4de6a365103f316a', '', 'INFO', 3801, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:26:06'),
(208, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '21', '5', 'pharmacist', 'pharmacy/dispense', '7e5bfd8e8d482f85', '', 'INFO', 4688, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:26:14'),
(209, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '22', '5', 'pharmacist', 'pharmacy/dispense', 'b995d328e626b514', '', 'INFO', 3051, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:26:32'),
(210, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '23', '5', 'pharmacist', 'pharmacy/dispense', '9c74773e2b1ca361', '', 'INFO', 10840, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:27:01'),
(211, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '32', '5', 'pharmacist', 'pharmacy/dispense', 'd5b62cb10c482620', '', 'INFO', 5334, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:27:22'),
(212, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '31', '5', 'pharmacist', 'pharmacy/dispense', '117945cf4dadab32', '', 'INFO', 3099, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:27:30'),
(213, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '24', '5', 'pharmacist', 'pharmacy/dispense', '07337c8db5dfb989', '', 'INFO', 2950, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:27:49'),
(214, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '25', '5', 'pharmacist', 'pharmacy/dispense', '284d322de88b262e', '', 'INFO', 4989, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:27:55'),
(215, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '26', '5', 'pharmacist', 'pharmacy/dispense', '71fa5ac4e3fc12b9', '', 'INFO', 2079, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:27:58'),
(216, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '28', '5', 'pharmacist', 'pharmacy/dispense', '7ad1ef24cc00117e', '', 'INFO', 5821, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:28:06'),
(217, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '29', '5', 'pharmacist', 'pharmacy/dispense', 'e5c1140555ee73d5', '', 'INFO', 5391, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:28:12'),
(218, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription', '30', '5', 'pharmacist', 'pharmacy/dispense', 'ff4693e9eb78d1b8', '', 'INFO', 7012, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:28:56'),
(219, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '7768814dd7e01d30', '88677d7f44a6150146478388ed33db74', 'INFO', 1741, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:38:32'),
(220, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', '81a9f287e19b841c', '88677d7f44a6150146478388ed33db74', 'INFO', 5394, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:38:38'),
(221, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', 'f60e1d57b0248e47', '08ec8b25f4b53a4a0a985dd71184c29d', 'INFO', 1164, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:41:18'),
(222, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '88e0f35aab649f74', '08ec8b25f4b53a4a0a985dd71184c29d', 'INFO', 4737, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:41:23'),
(223, 'Triage Officer 1', 'Started triage', '::1', 'Success', 'audit', 'patient_queue', '5', '7', 'nurse_aid', 'nurse_aid/start_triage', '852810cf44257af8', '', 'INFO', 1349, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:41:29'),
(224, 'Triage Officer 1', 'Captured vitals', '::1', 'Success', 'audit', 'patient_queue', '5', '7', 'nurse_aid', 'nurse_aid/save_vitals', '7abbf8861bf99dec', '', 'INFO', 11122, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:43:25'),
(225, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '7a55ec72300967bb', '2aa00e6097c61295716712e1ab493516', 'INFO', 4851, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:44:10'),
(226, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '855d1315d578a93e', '2aa00e6097c61295716712e1ab493516', 'INFO', 11315, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 10:44:22'),
(227, 'Sister Betty', 'Completed nurse consultation', '::1', 'Success', 'audit', 'patient_queue', '5', '3', 'nurse', 'nurse/consult_save', '984a3dc93202ac29', '', 'INFO', 14294, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, '{\"prescriptions_added\":2,\"sent_to_pharmacy\":\"no\"}', '2026-02-25 10:53:32'),
(228, 'Sister Betty', 'Completed nurse consultation', '::1', 'Success', 'audit', 'patient_queue', '5', '3', 'nurse', 'nurse/consult_save', 'c00db5e45f16cdf9', '', 'INFO', 15200, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, '{\"prescriptions_added\":2,\"sent_to_pharmacy\":\"yes\"}', '2026-02-25 10:59:21'),
(229, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', 'b8dc0e28c57c061b', '975d3aaeb003a69cf98b02c6555cc3e2', 'INFO', 7372, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 11:00:19'),
(230, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', '9f3993b3a529cb5e', '975d3aaeb003a69cf98b02c6555cc3e2', 'INFO', 6203, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 11:00:26'),
(231, 'Mike Meds', 'Dispensed medication', '::1', 'Success', 'audit', 'prescription_batch', '34,35,36', '5', 'pharmacist', 'pharmacy/dispense', '353531cafafd4915', '', 'INFO', 15747, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-25 11:02:00'),
(232, 'rec1', 'Login', '192.168.1.180', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '46c4d4a68ae3a23e', '494f058bb056bfe231a7926045c388ec', 'INFO', 7932, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-02-25 11:17:15'),
(233, '4', 'Logout', '192.168.1.180', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', '4de37111a36aa198', '494f058bb056bfe231a7926045c388ec', 'INFO', 11645, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-02-25 11:17:28'),
(234, 'rec1', 'Login', '192.168.26.131', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', 'cdbbe58b405fb6fd', '750dac428ef697c5d6d6df901af7cb39', 'INFO', 2115, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 12:12:14'),
(235, '4', 'Logout', '192.168.26.131', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', 'b20ac00a5fe3b301', '750dac428ef697c5d6d6df901af7cb39', 'INFO', 5413, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 12:12:21'),
(236, 'nurse1', 'Login', '192.168.26.131', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '44dfe65a3ab3f996', 'eaa517cf1b9b2aa58c7392853f23cbb6', 'INFO', 2082, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 12:13:10'),
(237, '3', 'Logout', '192.168.26.131', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '6174010632c445f4', 'eaa517cf1b9b2aa58c7392853f23cbb6', 'INFO', 7008, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 12:13:18'),
(238, 'admin', 'Login', '192.168.26.131', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '5e4aa89947ef82ca', '77c26e526168fb69893cfcf6076dfbd1', 'INFO', 2520, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 12:41:08'),
(239, '1', 'Logout', '192.168.26.131', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', '8bb38f6d51f700f0', '77c26e526168fb69893cfcf6076dfbd1', 'INFO', 5780, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 12:41:14'),
(240, '1', 'Logout', '192.168.26.131', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', 'f6a1f4dd7f24195f', '77c26e526168fb69893cfcf6076dfbd1', 'INFO', 6608, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 12:41:15'),
(241, 'nurse1', 'Login', '192.168.26.131', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '0a3c1b9ce35dac36', 'c5b8d2fff1cbef8a975f86524aa02fee', 'INFO', 6898, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 12:45:38'),
(242, '3', 'Logout', '192.168.26.131', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '2d2a75ce07eff642', 'c5b8d2fff1cbef8a975f86524aa02fee', 'INFO', 6278, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 12:45:46'),
(243, 'admin', 'Login', '192.168.26.131', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '455646a633b6110a', 'b029cfcba2daf1715c52c82b7d4bddad', 'INFO', 1501, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 13:00:41'),
(244, '1', 'Logout', '192.168.26.131', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', 'de20b6e603e0b28c', 'b029cfcba2daf1715c52c82b7d4bddad', 'INFO', 9481, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 13:00:52'),
(245, 'nurse1', 'Login', '192.168.26.131', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '3243d8d1c5529c3b', '8d0892bf130ba3658a4824a47f795775', 'INFO', 2382, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 13:03:37'),
(246, '3', 'Logout', '192.168.26.131', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '960123042ba164be', '8d0892bf130ba3658a4824a47f795775', 'INFO', 6043, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 13:03:44'),
(247, '3', 'Logout', '192.168.26.131', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '1ba4607ae6421a1b', '8d0892bf130ba3658a4824a47f795775', 'INFO', 6583, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 13:04:54'),
(248, 'nurse1', 'Login', '192.168.26.131', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', 'b2e556aced906c9d', '8c22539158f69e077592360bb28cd861', 'INFO', 6251, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 14:09:25'),
(249, '3', 'Logout', '192.168.26.131', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '1263a8cd02a89832', '8c22539158f69e077592360bb28cd861', 'INFO', 12999, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.26.131', NULL, NULL, NULL, NULL, NULL, '2026-02-25 14:09:40'),
(250, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', 'e8fe7620492d0aa5', '6cca3988c1c58a9505a5bdf27486fafb', 'INFO', 7461, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-26 16:08:44'),
(251, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'f4513ce49f7d4ed7', '6cca3988c1c58a9505a5bdf27486fafb', 'INFO', 7880, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-02-26 16:08:53'),
(252, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '4826f587476e4aa9', 'b1982af2cde4bc5db02ce953de9a4b9f', 'INFO', 2586, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 10:40:56'),
(253, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', 'db633a6278bceb74', 'b1982af2cde4bc5db02ce953de9a4b9f', 'INFO', 4083, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 10:41:01'),
(254, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', '95dbf63af5336c00', '24c4251c98e2b48b0821eda54d3b1cc2', 'INFO', 4690, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 10:46:19'),
(255, '1', 'Logout', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', 'de97ef30dace805d', '24c4251c98e2b48b0821eda54d3b1cc2', 'INFO', 6785, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 10:46:26'),
(256, 'admin', 'Login', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/login', 'b156cae8ccfec12c', 'e6d5dcd86dd7086bd8d6b7617d8b445c', 'INFO', 3103, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 11:08:11'),
(257, '1', 'Logout', '::1', 'Success', 'audit', 'user', '1', '1', 'admin', 'auth/logout', 'dab96b80e2e1c136', 'e6d5dcd86dd7086bd8d6b7617d8b445c', 'INFO', 6745, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 11:08:19'),
(258, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '774006185f3c8d3e', '3d395269ef30639d84c70c50a14f67c9', 'INFO', 1692, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 12:56:03'),
(259, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'b430c8a73b29cff7', '3d395269ef30639d84c70c50a14f67c9', 'INFO', 6126, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 12:56:11'),
(260, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '02281c4ea59ac88f', '3062623318dccd86e1a3502aa5d4c3aa', 'INFO', 2377, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 12:59:12'),
(261, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', 'c56660f219658243', '3062623318dccd86e1a3502aa5d4c3aa', 'INFO', 8256, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 12:59:22'),
(262, 'Triage1', 'Login Failed', '::1', 'Failed', 'audit', 'user', 'Triage1', NULL, NULL, 'auth/login', 'f897da1693a947c6', '', 'WARN', 1322, 401, NULL, 'Password mismatch', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:00:19'),
(263, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '27e110e069f45e3e', '79b913687b7a13b9411a0a266dc62b5a', 'INFO', 2516, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:00:50'),
(264, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', '7ead622f7f71b1d6', '79b913687b7a13b9411a0a266dc62b5a', 'INFO', 5840, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:00:57'),
(265, 'pharm1', 'Login', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/login', 'ec46a19554dea18a', '3a1237ead5020dd7fbc6e2128ff27610', 'INFO', 1407, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:05:40'),
(266, '5', 'Logout', '::1', 'Success', 'audit', 'user', '5', '5', 'pharmacist', 'auth/logout', '40b4fc8359d60483', '3a1237ead5020dd7fbc6e2128ff27610', 'INFO', 10659, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:05:52'),
(267, 'Mike Meds', 'Deleted pending medication', '::1', 'Success', 'audit', 'prescription', '33', '5', 'pharmacist', 'pharmacy/delete', '65fbefa40db7b6de', '', 'INFO', 6688, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:06:17'),
(268, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '2255a14f7b5a5bb1', 'aa8a426d60e4c13c08a3bdf62cf18906', 'INFO', 3389, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:12:43'),
(269, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', 'a39acb960e3ba8b7', 'aa8a426d60e4c13c08a3bdf62cf18906', 'INFO', 6817, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:12:51');
INSERT INTO `audit_logs` (`id`, `username`, `action`, `ip_address`, `status`, `event_type`, `entity_type`, `entity_id`, `actor_id`, `actor_role`, `source`, `request_id`, `session_id`, `severity`, `duration_ms`, `status_code`, `error_code`, `error_message`, `user_agent`, `device_type`, `client_ip`, `hostname`, `old_values`, `new_values`, `diff_json`, `metadata_json`, `created_at`) VALUES
(270, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', 'e79406ad75af652e', '060e5c68de68d8af12cc824a3a31cd7a', 'INFO', 3548, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:25:03'),
(271, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', 'abc44dfd7f915c8f', '060e5c68de68d8af12cc824a3a31cd7a', 'INFO', 6112, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:25:10'),
(272, 'rec1', 'Login', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', 'c5ea559424b797e3', '6776a9511783e8d288a0942eb740f01b', 'INFO', 4348, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:25:49'),
(273, '4', 'Logout', '::1', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', '97d72bc97846470f', '6776a9511783e8d288a0942eb740f01b', 'INFO', 9774, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:26:00'),
(274, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '113893933f2cbb5c', '7cb92b131f7ca927ad80e462a54ca717', 'INFO', 3331, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:26:35'),
(275, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '9ef88c1ccee4e241', '7cb92b131f7ca927ad80e462a54ca717', 'INFO', 5734, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:26:41'),
(276, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '122ce2df545933a5', '3ce605a91854eb278d77cc99f04431f3', 'INFO', 3586, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:28:16'),
(277, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'b84365a887400a57', '3ce605a91854eb278d77cc99f04431f3', 'INFO', 7323, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:28:24'),
(278, 'rec1', 'Login', '192.168.1.175', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '667c15e6667dbe37', '18a6e1e56121e22d487c690b3a1fe600', 'INFO', 15725, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.175', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:42:59'),
(279, '4', 'Logout', '192.168.1.175', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', 'dff3e60669a992a1', '18a6e1e56121e22d487c690b3a1fe600', 'INFO', 10494, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.175', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:43:11'),
(280, 'triage1', 'Login', '192.168.1.175', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '0239bbdb9c0b9ef1', 'ac8e502da97259d27312348c00ed2165', 'INFO', 3470, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.175', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:45:02'),
(281, '7', 'Logout', '192.168.1.175', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', '2c2a515f777ce3f0', 'ac8e502da97259d27312348c00ed2165', 'INFO', 6626, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.175', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:45:10'),
(282, 'rec1', 'Login', '192.168.1.180', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '0d7555cea93d5693', '923459a6a8eef34a8ce4378a323be6d5', 'INFO', 4757, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:50:14'),
(283, '4', 'Logout', '192.168.1.180', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', 'c59221646f394bb9', '923459a6a8eef34a8ce4378a323be6d5', 'INFO', 9025, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:50:25'),
(284, '4', 'Logout', '192.168.1.180', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', '6a9733282bf5eac5', '923459a6a8eef34a8ce4378a323be6d5', 'INFO', 9910, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:50:26'),
(285, 'triage1', 'Login', '192.168.1.180', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '9d40ea467d10f8ae', '65c6fcfb70beab7d26c85fa9eb99f035', 'INFO', 3224, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:51:49'),
(286, '7', 'Logout', '192.168.1.180', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', 'c7bfafac057ca9d6', '65c6fcfb70beab7d26c85fa9eb99f035', 'INFO', 5991, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:51:56'),
(287, 'Triage Officer 1', 'Started triage', '192.168.1.180', 'Success', 'audit', 'patient_queue', '8', '7', 'nurse_aid', 'nurse_aid/start_triage', '2f8bec27537adb38', '', 'INFO', 3389, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:52:17'),
(288, 'nurse1', 'Login', '192.168.1.180', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '14317623fc718189', '4b917345e6df546642940e70da1ec8fb', 'INFO', 1309, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:52:39'),
(289, '3', 'Logout', '192.168.1.180', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'f7f9cc56607465b6', '4b917345e6df546642940e70da1ec8fb', 'INFO', 6459, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.1.180', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:52:47'),
(290, 'triage1', 'Login', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/login', '3754fac7da971e30', '274898ae76c2be159d33694af2686a8c', 'INFO', 8522, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:54:56'),
(291, '7', 'Logout', '::1', 'Success', 'audit', 'user', '7', '7', 'nurse_aid', 'auth/logout', 'f081b66dafc8a2cf', '274898ae76c2be159d33694af2686a8c', 'INFO', 7775, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:55:05'),
(292, 'Triage Officer 1', 'Started triage', '::1', 'Success', 'audit', 'patient_queue', '7', '7', 'nurse_aid', 'nurse_aid/start_triage', '991c98c22b959b1f', '', 'INFO', 5534, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:55:13'),
(293, 'Triage Officer 1', 'Captured vitals', '::1', 'Success', 'audit', 'patient_queue', '7', '7', 'nurse_aid', 'nurse_aid/save_vitals', '0013c661173ec586', '', 'INFO', 7753, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:55:43'),
(294, 'Triage Officer 1', 'Started triage', '::1', 'Success', 'audit', 'patient_queue', '8', '7', 'nurse_aid', 'nurse_aid/start_triage', '67953ae6b162fc8b', '', 'INFO', 5573, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:56:09'),
(295, 'Triage Officer 1', 'Captured vitals', '::1', 'Success', 'audit', 'patient_queue', '8', '7', 'nurse_aid', 'nurse_aid/save_vitals', '06c45201fcaf67a5', '', 'INFO', 7703, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:56:38'),
(296, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '4f8b33ab5952fbc8', '9c74d02d37f9443b7436e1a3372d1b34', 'INFO', 4761, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:57:02'),
(297, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'c74ae6ba6eff6b82', '9c74d02d37f9443b7436e1a3372d1b34', 'INFO', 8915, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 13:57:12'),
(298, 'Sister Betty', 'Completed nurse consultation', '::1', 'Success', 'audit', 'patient_queue', '7', '3', 'nurse', 'nurse/consult_save', 'e9dd41fff95d6f3e', '', 'INFO', 7689, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, '{\"prescriptions_added\":2,\"sent_to_pharmacy\":\"yes\"}', '2026-03-09 13:59:58'),
(299, 'doc1', 'Login', '::1', 'Success', 'audit', 'user', '2', '2', 'doctor', 'auth/login', '57a20c724ce4251c', 'e52a3a54452d18207fc0c22441938928', 'INFO', 4845, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 14:01:01'),
(300, '2', 'Logout', '::1', 'Success', 'audit', 'user', '2', '2', 'doctor', 'auth/logout', 'a25e71396eda1970', 'e52a3a54452d18207fc0c22441938928', 'INFO', 9683, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 14:03:28'),
(301, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '96f440985804d2f9', '6b9e963ab4af00671d5bddb7dd777bb7', 'INFO', 10685, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 14:03:34'),
(302, 'nurse1', 'Login', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '08eb31f4d9599f6a', 'cba1cf09f731ba926b1b612a5cbab03c', 'INFO', 1855, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 16:17:55'),
(303, '3', 'Logout', '::1', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'cd76c3145e38f923', 'cba1cf09f731ba926b1b612a5cbab03c', 'INFO', 5728, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '::1', NULL, NULL, NULL, NULL, NULL, '2026-03-09 16:18:02'),
(304, 'rec1', 'Login', '192.168.25.61', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/login', '7e2aa00c50cc5045', '1e9755283c2b121f08af1e3eb6c7b93c', 'INFO', 4969, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.25.61', NULL, NULL, NULL, NULL, NULL, '2026-03-10 09:01:14'),
(305, '4', 'Logout', '192.168.25.61', 'Success', 'audit', 'user', '4', '4', 'receptionist', 'auth/logout', '2be6107ed96a519a', '1e9755283c2b121f08af1e3eb6c7b93c', 'INFO', 8519, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.25.61', NULL, NULL, NULL, NULL, NULL, '2026-03-10 09:01:23'),
(306, 'nurse1', 'Login', '192.168.25.61', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/login', '6d7fdfb78056c738', '4abf849326e5eef88fac46ab7f461094', 'INFO', 2862, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.25.61', NULL, NULL, NULL, NULL, NULL, '2026-03-10 09:02:37'),
(307, '3', 'Logout', '192.168.25.61', 'Success', 'audit', 'user', '3', '3', 'nurse', 'auth/logout', 'fa1b7ec7569f69d2', '4abf849326e5eef88fac46ab7f461094', 'INFO', 9156, 200, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'desktop', '192.168.25.61', NULL, NULL, NULL, NULL, NULL, '2026-03-10 09:02:47');

-- --------------------------------------------------------

--
-- Table structure for table `beds`
--

CREATE TABLE `beds` (
  `id` int(11) NOT NULL,
  `ward_name` varchar(191) NOT NULL,
  `bed_number` varchar(50) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Available',
  `current_patient_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `beds`
--

INSERT INTO `beds` (`id`, `ward_name`, `bed_number`, `status`, `current_patient_id`, `created_at`, `updated_at`) VALUES
(1, 'ICU', 'Bed 1', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(2, 'ICU', 'Bed 2', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(3, 'ICU', 'Bed 3', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(4, 'ICU', 'Bed 4', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(5, 'ICU', 'Bed 5', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(6, 'ICU', 'Bed 6', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(7, 'ICU', 'Bed 7', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(8, 'ICU', 'Bed 8', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(9, 'ICU', 'Bed 9', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(10, 'ICU', 'Bed 10', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(12, 'ICU', 'Bed 11', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(13, 'ICU', 'Bed 12', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(14, 'ICU', 'Bed 13', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(15, 'ICU', 'Bed 14', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(16, 'ICU', 'Bed 15', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(18, 'ICU', 'Bed 16', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(19, 'ICU', 'Bed 17', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(20, 'ICU', 'Bed 18', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(21, 'ICU', 'Bed 19', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(22, 'ICU', 'Bed 20', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(23, 'ICU', 'Bed 21', 'Available', NULL, '2026-02-25 13:07:49', '2026-02-25 13:07:49'),
(26, 'ICU', 'Bed 22', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(28, 'ICU', 'Bed 23', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(29, 'ICU', 'Bed 24', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(30, 'ICU', 'Bed 25', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(31, 'ICU', 'Bed 26', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(32, 'ICU', 'Bed 27', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(33, 'ICU', 'Bed 28', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(34, 'ICU', 'Bed 29', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(35, 'ICU', 'Bed 30', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(36, 'Male Ward 1', 'Bed 1', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(37, 'Male Ward 1', 'Bed 2', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(38, 'Male Ward 1', 'Bed 3', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(39, 'Male Ward 1', 'Bed 4', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(40, 'Male Ward 1', 'Bed 5', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(41, 'Male Ward 1', 'Bed 6', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(42, 'Male Ward 1', 'Bed 7', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(43, 'Male Ward 1', 'Bed 8', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(44, 'Male Ward 1', 'Bed 9', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(45, 'Male Ward 1', 'Bed 10', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(46, 'Male Ward 1', 'Bed 11', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(47, 'Male Ward 1', 'Bed 12', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(48, 'Male Ward 1', 'Bed 13', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(49, 'Male Ward 1', 'Bed 14', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(50, 'Male Ward 1', 'Bed 15', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(51, 'Male Ward 1', 'Bed 16', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(52, 'Male Ward 1', 'Bed 17', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(53, 'Male Ward 1', 'Bed 18', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(54, 'Male Ward 1', 'Bed 19', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50'),
(55, 'Male Ward 1', 'Bed 20', 'Available', NULL, '2026-02-25 13:07:50', '2026-02-25 13:07:50');

-- --------------------------------------------------------

--
-- Table structure for table `billings`
--

CREATE TABLE `billings` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `visit_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `balance_due` decimal(10,2) NOT NULL,
  `status` enum('unpaid','partial','paid') DEFAULT 'unpaid',
  `payment_method` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `last_payment_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_inquiries`
--

CREATE TABLE `contact_inquiries` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `organization` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `controlled_requests`
--

CREATE TABLE `controlled_requests` (
  `id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `controlled_substances`
--

CREATE TABLE `controlled_substances` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `schedule` varchar(20) NOT NULL,
  `requires_approval` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discharge_summaries`
--

CREATE TABLE `discharge_summaries` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `summary` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_escalations`
--

CREATE TABLE `doctor_escalations` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `severity` varchar(20) DEFAULT 'urgent',
  `status` varchar(20) DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_handover`
--

CREATE TABLE `doctor_handover` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shift_start` datetime DEFAULT NULL,
  `shift_end` datetime DEFAULT NULL,
  `notes` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_tasks`
--

CREATE TABLE `doctor_tasks` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `task` varchar(255) NOT NULL,
  `priority` varchar(20) DEFAULT 'normal',
  `status` varchar(20) DEFAULT 'open',
  `due_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `drug_interactions`
--

CREATE TABLE `drug_interactions` (
  `id` int(11) NOT NULL,
  `drug_a` varchar(120) NOT NULL,
  `drug_b` varchar(120) NOT NULL,
  `severity` varchar(20) DEFAULT 'moderate',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipts`
--

CREATE TABLE `goods_receipts` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `received_by` int(11) DEFAULT NULL,
  `received_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `insurance_claims`
--

CREATE TABLE `insurance_claims` (
  `id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'Submitted',
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `it_tickets`
--

CREATE TABLE `it_tickets` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'open',
  `priority` varchar(20) NOT NULL DEFAULT 'normal',
  `source` varchar(20) NOT NULL DEFAULT 'local',
  `requester_name` varchar(120) DEFAULT NULL,
  `requester_email` varchar(120) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'General',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `name`, `description`, `category`, `price`, `stock_quantity`, `expiry_date`, `created_at`) VALUES
(1, 'Paracetamol 500mg Tablet', 'Pain and fever reducer.', 'Analgesic', 0.10, 1500, '2028-06-30', '2026-02-20 10:29:18'),
(2, 'Ibuprofen 400mg Tablet', 'NSAID for pain and inflammation.', 'Analgesic', 0.15, 1200, '2028-04-30', '2026-02-20 10:29:18'),
(3, 'Diclofenac 50mg Tablet', 'NSAID for moderate pain.', 'Analgesic', 0.18, 900, '2028-03-31', '2026-02-20 10:29:18'),
(4, 'Tramadol 50mg Capsule', 'Opioid analgesic for severe pain.', 'Analgesic', 0.65, 400, '2027-12-31', '2026-02-20 10:29:18'),
(5, 'Morphine Sulfate 10mg/mL Injection', 'Strong opioid analgesic for acute severe pain.', 'Controlled', 3.50, 120, '2027-10-31', '2026-02-20 10:29:18'),
(6, 'Amoxicillin 500mg Capsule', 'Broad-spectrum penicillin antibiotic.', 'Antibiotic', 0.35, 1000, '2028-01-31', '2026-02-20 10:29:18'),
(7, 'Azithromycin 500mg Tablet', 'Macrolide antibiotic.', 'Antibiotic', 0.90, 700, '2027-11-30', '2026-02-20 10:29:18'),
(8, 'Ciprofloxacin 500mg Tablet', 'Fluoroquinolone antibiotic.', 'Antibiotic', 0.55, 649, '2028-02-29', '2026-02-20 10:29:18'),
(9, 'Doxycycline 100mg Capsule', 'Tetracycline antibiotic.', 'Antibiotic', 0.40, 800, '2027-09-30', '2026-02-20 10:29:18'),
(10, 'Ceftriaxone 1g Injection', 'Third-generation cephalosporin antibiotic.', 'Antibiotic', 2.20, 300, '2027-08-31', '2026-02-20 10:29:18'),
(11, 'Metformin 500mg Tablet', 'Oral antidiabetic medicine.', 'Antidiabetic', 0.20, 1800, '2029-01-31', '2026-02-20 10:29:18'),
(12, 'Insulin Regular 100IU/mL Vial', 'Short-acting insulin.', 'Antidiabetic', 5.80, 250, '2027-07-31', '2026-02-20 10:29:18'),
(13, 'Insulin NPH 100IU/mL Vial', 'Intermediate-acting insulin.', 'Antidiabetic', 6.20, 220, '2027-07-31', '2026-02-20 10:29:18'),
(14, 'Glibenclamide 5mg Tablet', 'Sulfonylurea for type 2 diabetes.', 'Antidiabetic', 0.12, 900, '2028-05-31', '2026-02-20 10:29:18'),
(15, 'Amlodipine 5mg Tablet', 'Calcium-channel blocker for hypertension.', 'Antihypertensive', 0.22, 1040, '2029-03-31', '2026-02-20 10:29:18'),
(16, 'Lisinopril 10mg Tablet', 'ACE inhibitor for hypertension.', 'Antihypertensive', 0.24, 1100, '2028-12-31', '2026-02-20 10:29:18'),
(17, 'Losartan 50mg Tablet', 'ARB for hypertension.', 'Antihypertensive', 0.30, 900, '2028-11-30', '2026-02-20 10:29:18'),
(18, 'Hydrochlorothiazide 25mg Tablet', 'Thiazide diuretic for hypertension.', 'Antihypertensive', 0.16, 1000, '2028-10-31', '2026-02-20 10:29:18'),
(19, 'Furosemide 40mg Tablet', 'Loop diuretic for edema and hypertension.', 'Diuretic', 0.19, 850, '2028-09-30', '2026-02-20 10:29:18'),
(20, 'Atorvastatin 20mg Tablet', 'Lipid-lowering statin.', 'Cardiovascular', 0.28, 1000, '2029-02-28', '2026-02-20 10:29:18'),
(21, 'Aspirin 75mg Tablet', 'Antiplatelet for cardiovascular risk reduction.', 'Cardiovascular', 0.09, 1600, '2029-05-31', '2026-02-20 10:29:18'),
(22, 'Clopidogrel 75mg Tablet', 'Antiplatelet agent.', 'Cardiovascular', 0.45, 700, '2028-08-31', '2026-02-20 10:29:19'),
(23, 'Omeprazole 20mg Capsule', 'Proton pump inhibitor for acid disorders.', 'Gastrointestinal', 0.25, 1400, '2028-07-31', '2026-02-20 10:29:19'),
(24, 'Oral Rehydration Salts (ORS) Sachet', 'Electrolyte replacement for dehydration.', 'Gastrointestinal', 0.20, 2000, '2029-01-31', '2026-02-20 10:29:19'),
(25, 'Ondansetron 4mg Tablet', 'Antiemetic for nausea and vomiting.', 'Gastrointestinal', 0.33, 750, '2028-06-30', '2026-02-20 10:29:19'),
(26, 'Salbutamol Inhaler 100mcg', 'Bronchodilator for asthma/COPD.', 'Respiratory', 3.20, 350, '2027-12-31', '2026-02-20 10:29:19'),
(27, 'Cetirizine 10mg Tablet', 'Antihistamine for allergy symptoms.', 'Allergy', 0.14, 1160, '2028-04-30', '2026-02-20 10:29:19'),
(28, 'Prednisolone 5mg Tablet', 'Corticosteroid for inflammatory conditions.', 'Steroid', 0.18, 700, '2028-03-31', '2026-02-20 10:29:19'),
(29, 'Hydrocortisone 1% Cream', 'Topical corticosteroid for dermatitis.', 'Dermatology', 1.10, 500, '2027-11-30', '2026-02-20 10:29:19'),
(30, 'Zinc Sulfate 20mg Tablet', 'Zinc supplement, often adjunct in diarrhea care.', 'Supplement', 0.11, 1000, '2029-04-30', '2026-02-20 10:29:19'),
(31, 'Ferrous Sulfate 200mg Tablet', 'Iron supplement for anemia.', 'Supplement', 0.13, 943, '2029-03-31', '2026-02-20 10:29:19'),
(32, 'Folic Acid 5mg Tablet', 'Folate supplement.', 'Supplement', 0.10, 1050, '2029-02-28', '2026-02-20 10:29:19');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(20) DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nurse_consultations`
--

CREATE TABLE `nurse_consultations` (
  `id` int(11) NOT NULL,
  `queue_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `nurse_id` int(11) DEFAULT NULL,
  `diagnosis_notes` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nurse_consultations`
--

INSERT INTO `nurse_consultations` (`id`, `queue_id`, `patient_id`, `nurse_id`, `diagnosis_notes`, `created_at`) VALUES
(10, 4, 3, 3, 'Headache', '2026-02-20 11:21:01'),
(11, 3, 1, 3, 'Sick', '2026-02-20 11:48:13'),
(12, 5, 3, 3, 'Migraines\nColds and Fevers', '2026-02-25 10:53:24'),
(13, 5, 3, 3, 'Headache\nMigraines\nchest pains', '2026-02-25 10:59:13'),
(14, 7, 4, 3, 'Pregnant\nHeadaches\nCramps', '2026-03-09 13:59:54');

-- --------------------------------------------------------

--
-- Table structure for table `nurse_escalations`
--

CREATE TABLE `nurse_escalations` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `severity` varchar(20) NOT NULL DEFAULT 'urgent',
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nurse_escalations`
--

INSERT INTO `nurse_escalations` (`id`, `patient_id`, `reason`, `severity`, `status`, `created_at`) VALUES
(1, 1, 'Critical vitals detected during triage: critical temperature (35C)', 'urgent', 'open', '2026-02-20 09:47:09'),
(2, 2, 'Too many clients', 'urgent', 'open', '2026-02-20 12:23:51'),
(3, 3, 'Critical vitals detected during triage: critical temperature (32C); low oxygen saturation (70%)', 'urgent', 'open', '2026-02-25 10:43:19'),
(4, 1, 'Car accident', 'urgent', 'open', '2026-03-09 13:03:40'),
(5, 1, 'Car accident', 'urgent', 'open', '2026-03-09 13:03:41'),
(6, 1, 'Car accident', 'urgent', 'open', '2026-03-09 13:03:42'),
(7, 1, 'Car accident', 'urgent', 'open', '2026-03-09 13:03:42'),
(8, 1, 'Car accident', 'urgent', 'open', '2026-03-09 13:03:43'),
(9, 1, 'Car accident', 'urgent', 'open', '2026-03-09 13:03:43'),
(10, 1, 'Car accident', 'urgent', 'open', '2026-03-09 13:03:48'),
(11, 1, 'Car accident', 'urgent', 'open', '2026-03-09 13:03:49'),
(12, 1, 'Car accident', 'urgent', 'open', '2026-03-09 13:03:50'),
(13, 1, 'Car accident', 'urgent', 'open', '2026-03-09 13:03:50'),
(14, 1, 'Car accident', 'urgent', 'open', '2026-03-09 13:03:55'),
(15, 4, 'Critical vitals detected during triage: low oxygen saturation (72%)', 'urgent', 'open', '2026-03-09 13:55:41'),
(16, 6, 'Critical vitals detected during triage: critical temperature (35C)', 'urgent', 'open', '2026-03-09 13:56:35'),
(17, 6, 'Nurse could not resolve case. because the head is open', 'urgent', 'open', '2026-03-09 13:57:43');

-- --------------------------------------------------------

--
-- Table structure for table `nurse_handover`
--

CREATE TABLE `nurse_handover` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shift_start` datetime DEFAULT NULL,
  `shift_end` datetime DEFAULT NULL,
  `notes` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nurse_tasks`
--

CREATE TABLE `nurse_tasks` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `task` text NOT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'normal',
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `due_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `dob` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `contact` varchar(50) NOT NULL,
  `fingerprint_hash` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `full_name` varchar(150) DEFAULT NULL,
  `national_id` varchar(50) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `has_medical_aid` tinyint(1) NOT NULL DEFAULT 0,
  `medical_aid_provider` varchar(120) DEFAULT NULL,
  `medical_aid_number` varchar(80) DEFAULT NULL,
  `kin_name` varchar(120) DEFAULT NULL,
  `kin_relation` varchar(80) DEFAULT NULL,
  `kin_phone` varchar(30) DEFAULT NULL,
  `allergies` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `name`, `dob`, `gender`, `contact`, `fingerprint_hash`, `created_at`, `full_name`, `national_id`, `phone`, `address`, `has_medical_aid`, `medical_aid_provider`, `medical_aid_number`, `kin_name`, `kin_relation`, `kin_phone`, `allergies`) VALUES
(1, 'Al Ali', '2026-01-26', 'Male', '+263700000000', NULL, '2026-02-20 06:52:33', 'Al Ali', '05-000000-X-05', '+263700000000', '113 Mvurwi', 0, '', '', 'Leo Ali', 'Brother', '+263700033322', 'None'),
(2, 'Flow Test Patient', '1990-01-01', 'Male', '+263771234567', NULL, '2026-02-20 08:57:04', 'Flow Test Patient', '99-1771577824-A-99', '+263771234567', 'Flow Street', 0, '', '', 'Test Kin', 'Sibling', '+263771111111', 'none'),
(3, 'Rutendo Muza', '2026-01-27', 'Female', '+263789000540', NULL, '2026-02-20 11:06:30', 'Rutendo Muza', '04-000023-Z-05', '+263789000540', '14 Mvurwi', 0, '', '', 'Ropah Muza', 'Sister', '+263704333322', 'none'),
(4, 'Revelation Mudoti', '1977-07-07', 'Male', '+263720023875', NULL, '2026-02-25 11:29:20', 'Revelation Mudoti', '05-120490-X-90', '+263720023875', '113 Shashi', 0, '', '', 'Peter Mdoti', 'Brother', '+263700032255', 'None'),
(6, 'betty mpofu', '2000-08-12', 'Female', '+263771865436', NULL, '2026-03-09 13:44:04', 'betty mpofu', '12-345679-S-66', '+263771865436', '97 bindura', 0, '', '', 'bright jester', 'mother', '+263789610359non', 'non');

-- --------------------------------------------------------

--
-- Table structure for table `patient_queue`
--

CREATE TABLE `patient_queue` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_assigned` int(11) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Waiting',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_queue`
--

INSERT INTO `patient_queue` (`id`, `patient_id`, `doctor_assigned`, `status`, `created_at`, `updated_at`) VALUES
(1, 2912, NULL, 'With Nurse', '2026-02-20 08:56:19', '2026-02-20 08:56:26'),
(2, 2, 2, 'Completed', '2026-02-20 08:57:07', '2026-02-25 08:24:42'),
(3, 1, NULL, 'Cancelled', '2026-02-20 09:01:37', '2026-02-25 10:39:54'),
(4, 3, NULL, 'Cancelled', '2026-02-20 11:06:45', '2026-02-25 10:39:50'),
(5, 3, NULL, 'Ready for Admission', '2026-02-25 10:40:07', '2026-03-09 13:06:13'),
(6, 4, NULL, 'Cancelled', '2026-03-09 12:59:29', '2026-03-09 13:13:10'),
(7, 4, NULL, 'Waiting Pharmacy', '2026-03-09 13:26:13', '2026-03-09 13:59:55'),
(8, 6, NULL, 'With Doctor', '2026-03-09 13:51:21', '2026-03-09 13:57:43');

-- --------------------------------------------------------

--
-- Table structure for table `patient_vitals`
--

CREATE TABLE `patient_vitals` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `queue_id` int(11) DEFAULT NULL,
  `temperature` varchar(10) DEFAULT NULL,
  `pulse` varchar(10) DEFAULT NULL,
  `bp` varchar(20) DEFAULT NULL,
  `weight` varchar(10) DEFAULT NULL,
  `spo2` varchar(10) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_vitals`
--

INSERT INTO `patient_vitals` (`id`, `patient_id`, `queue_id`, `temperature`, `pulse`, `bp`, `weight`, `spo2`, `notes`, `created_at`) VALUES
(1, 2912, 1, '36.9', '78', '120/80', '70', '98', 'Flow verification vitals', '2026-02-20 08:56:26'),
(2, 2, 2, '36.9', '78', '120/80', '70', '98', 'Flow verification vitals', '2026-02-20 08:57:11'),
(3, 1, 3, '35', '70', '100/77', '100', '99', 'none', '2026-02-20 09:47:09'),
(4, 3, 4, '37', '100', '120/80', '78', '100', 'none', '2026-02-20 11:08:27'),
(5, 3, 5, '32', '70', '110/80', '30', '70', 'Nil', '2026-02-25 10:43:19'),
(6, 4, 7, '36', '72', '120/80', '100', '72', 'Dizzy', '2026-03-09 13:55:41'),
(7, 6, 8, '35', '76', '120/85', '133', '90', 'Dizzy', '2026-03-09 13:56:35');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacy_requests`
--

CREATE TABLE `pharmacy_requests` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pharmacy_requests`
--

INSERT INTO `pharmacy_requests` (`id`, `patient_id`, `requested_by`, `status`, `notes`, `created_at`) VALUES
(3, 3, 3, 'Completed', 'Nurse consultation completed and forwarded to pharmacy.', '2026-02-20 11:21:01'),
(4, 1, 3, 'Completed', 'Nurse consultation completed and forwarded to pharmacy.', '2026-02-20 11:48:13'),
(5, 3, 3, 'Pending', 'Nurse consultation completed and forwarded to pharmacy.', '2026-02-25 10:59:14'),
(6, 4, 3, 'Pending', 'Nurse consultation completed and forwarded to pharmacy.', '2026-03-09 13:59:55');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `visit_id` int(11) DEFAULT NULL,
  `medication_name` varchar(100) DEFAULT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(50) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `patient_id` int(11) DEFAULT NULL,
  `medicine_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `visit_id`, `medication_name`, `dosage`, `frequency`, `duration`, `status`, `created_at`, `patient_id`, `medicine_id`, `quantity`, `notes`) VALUES
(8, NULL, NULL, '1 tab twice', NULL, NULL, 'Dispensed', '2026-02-20 11:21:01', 3, 27, 13, NULL),
(9, NULL, NULL, '2 tab thrice', NULL, NULL, 'Dispensed', '2026-02-20 11:48:13', 1, 31, 3, NULL),
(10, NULL, NULL, '1 tab twice', NULL, NULL, 'Dispensed', '2026-02-20 11:48:13', 1, 27, 1, NULL),
(11, NULL, NULL, '1 tab twice', NULL, NULL, 'Dispensed', '2026-02-20 11:48:13', 1, 15, 2, NULL),
(13, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:24:42', 2, 15, 12, NULL),
(14, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:24:44', 2, 15, 12, NULL),
(15, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:24:45', 2, 15, 12, NULL),
(16, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:24:45', 2, 15, 12, NULL),
(17, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:24:49', 2, 15, 12, NULL),
(18, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:24:50', 2, 15, 12, NULL),
(19, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:24:58', 2, 15, 12, NULL),
(20, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:24:59', 2, 15, 12, NULL),
(21, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:25:00', 2, 15, 12, NULL),
(22, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:25:01', 2, 15, 12, NULL),
(23, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:25:02', 2, 15, 12, NULL),
(24, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:25:06', 2, 15, 12, NULL),
(25, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:25:08', 2, 15, 12, NULL),
(26, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:25:08', 2, 15, 12, NULL),
(27, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:25:12', 2, 15, 12, NULL),
(28, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:25:13', 2, 15, 12, NULL),
(29, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:25:13', 2, 15, 12, NULL),
(30, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:25:17', 2, 15, 12, NULL),
(31, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:25:18', 2, 15, 12, NULL),
(32, NULL, NULL, '2*3', NULL, NULL, 'Dispensed', '2026-02-25 08:25:18', 2, 15, 12, NULL),
(33, NULL, NULL, '1 tab twice daily', NULL, NULL, 'Deleted', '2026-02-25 10:53:24', 3, NULL, 12, 'chringa'),
(34, NULL, NULL, '1 tab twice', NULL, NULL, 'Dispensed', '2026-02-25 10:53:24', 3, 8, 1, NULL),
(35, NULL, NULL, '1 tab twice', NULL, NULL, 'Dispensed', '2026-02-25 10:59:13', 3, NULL, 1, 'Chiringa'),
(36, NULL, NULL, '1 tab thrice daily', NULL, NULL, 'Dispensed', '2026-02-25 10:59:13', 3, 31, 1, NULL),
(37, NULL, NULL, '1 twice a day', NULL, NULL, 'Pending', '2026-03-09 13:59:55', 4, 2, 1, NULL),
(38, NULL, NULL, '2 tablets thrice a day', NULL, NULL, 'Pending', '2026-03-09 13:59:55', 4, 14, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_invoices`
--

CREATE TABLE `purchase_invoices` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `invoice_number` varchar(60) NOT NULL,
  `amount` decimal(12,2) DEFAULT 0.00,
  `invoice_date` date NOT NULL,
  `status` varchar(20) DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `medicine_name` varchar(120) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `total_cost` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quarantine_batches`
--

CREATE TABLE `quarantine_batches` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `batch_number` varchar(60) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `reason` varchar(255) NOT NULL,
  `status` varchar(20) DEFAULT 'Quarantined',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reception_handover`
--

CREATE TABLE `reception_handover` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shift_start` datetime DEFAULT NULL,
  `shift_end` datetime DEFAULT NULL,
  `notes` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refill_requests`
--

CREATE TABLE `refill_requests` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `medicine_name` varchar(120) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Requested',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_shifts`
--

CREATE TABLE `staff_shifts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `shift_start` datetime NOT NULL,
  `shift_end` datetime NOT NULL,
  `shift_type` varchar(50) DEFAULT NULL,
  `ward_name` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Confirmed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustments`
--

CREATE TABLE `stock_adjustments` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `adjustment` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `adjusted_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `contact_name` varchar(120) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `hospital_name` varchar(255) NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `maintenance_mode` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `hospital_name`, `contact_email`, `maintenance_mode`, `updated_at`) VALUES
(1, 'City General Hospital', 'admin@cityhospital.co.zw', 0, '2026-02-19 13:53:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `role` enum('admin','doctor','nurse','nurse_aid','pharmacist','senior_pharmacist','receptionist','it_support') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `current_session_id` varchar(64) DEFAULT NULL,
  `session_expires_at` datetime DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `gender`, `role`, `phone`, `is_active`, `current_session_id`, `session_expires_at`, `last_login`, `created_at`) VALUES
(1, 'admin', 'admin@hms.com', '$2y$10$CCsbQ5h764ZPWD21jHAvw.X8ZVW8WmF8u/dGTWL81CGMfB79dIan2', 'System Administrator', NULL, 'admin', NULL, 0, NULL, NULL, '2026-03-09 11:08:10', '2026-02-19 14:17:46'),
(2, 'doc1', 'doctor@hms.com', '$2y$10$CCsbQ5h764ZPWD21jHAvw.X8ZVW8WmF8u/dGTWL81CGMfB79dIan2', 'Dr. Sarah Moyo', NULL, 'doctor', NULL, 0, NULL, NULL, '2026-03-09 14:00:58', '2026-02-19 14:17:46'),
(3, 'nurse1', 'nurse@hms.com', '$2y$10$CCsbQ5h764ZPWD21jHAvw.X8ZVW8WmF8u/dGTWL81CGMfB79dIan2', 'Sister Betty', NULL, 'nurse', NULL, 0, NULL, NULL, '2026-03-10 09:02:36', '2026-02-19 14:17:46'),
(4, 'rec1', 'reception@hms.com', '$2y$10$CCsbQ5h764ZPWD21jHAvw.X8ZVW8WmF8u/dGTWL81CGMfB79dIan2', 'John Frontdesk', NULL, 'receptionist', NULL, 0, NULL, NULL, '2026-03-10 09:01:13', '2026-02-19 14:17:46'),
(5, 'pharm1', 'pharmacy@hms.com', '$2y$10$CCsbQ5h764ZPWD21jHAvw.X8ZVW8WmF8u/dGTWL81CGMfB79dIan2', 'Mike Meds', NULL, 'pharmacist', NULL, 0, NULL, NULL, '2026-03-09 13:05:39', '2026-02-19 14:17:46'),
(6, 'itsup1', 'it@hms.com', '$2y$10$CCsbQ5h764ZPWD21jHAvw.X8ZVW8WmF8u/dGTWL81CGMfB79dIan2', 'IT Support', NULL, 'it_support', NULL, 0, NULL, NULL, '2026-02-20 08:40:08', '2026-02-19 14:17:46'),
(7, 'triage1', 'triage1@hms.com', '$2y$10$HyYonnCpR8X/mB97C5vWIeBDyUWAGgUrK0YLHI5D7wjcdqUCCPfKG', 'Triage Officer 1', NULL, 'nurse_aid', NULL, 0, NULL, NULL, '2026-03-09 13:54:53', '2026-02-20 08:47:24');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(64) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_seen` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

CREATE TABLE `visits` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `status` enum('waiting','triaged','in_consultation','pharmacy','completed','cancelled') DEFAULT 'waiting',
  `chief_complaint` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vital_signs`
--

CREATE TABLE `vital_signs` (
  `id` int(11) NOT NULL,
  `visit_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `temperature` varchar(10) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `pulse_rate` varchar(10) DEFAULT NULL,
  `weight` varchar(10) DEFAULT NULL,
  `height` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wards`
--

CREATE TABLE `wards` (
  `id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `capacity` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wards`
--

INSERT INTO `wards` (`id`, `name`, `capacity`, `notes`, `is_active`, `created_at`) VALUES
(1, 'ICU', 30, 'For critical patients only', 1, '2026-02-25 12:43:15'),
(4, 'Male Ward 1', 20, 'Beds 1 - 20', 1, '2026-02-25 13:01:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_logs_created_at` (`created_at`),
  ADD KEY `idx_activity_logs_event_type` (`event_type`),
  ADD KEY `idx_activity_logs_actor_id` (`actor_id`),
  ADD KEY `idx_activity_logs_entity_type` (`entity_type`),
  ADD KEY `idx_activity_logs_entity_id` (`entity_id`),
  ADD KEY `idx_activity_logs_request_id` (`request_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_appointments_patient` (`patient_id`),
  ADD KEY `idx_appointments_doctor` (`doctor_id`),
  ADD KEY `idx_appointments_date` (`scheduled_at`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_logs_created_at` (`created_at`),
  ADD KEY `idx_audit_logs_event_type` (`event_type`),
  ADD KEY `idx_audit_logs_actor_id` (`actor_id`),
  ADD KEY `idx_audit_logs_entity_type` (`entity_type`),
  ADD KEY `idx_audit_logs_entity_id` (`entity_id`),
  ADD KEY `idx_audit_logs_request_id` (`request_id`);

--
-- Indexes for table `beds`
--
ALTER TABLE `beds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_beds_ward_bed` (`ward_name`,`bed_number`),
  ADD KEY `idx_beds_status` (`status`),
  ADD KEY `idx_beds_patient` (`current_patient_id`);

--
-- Indexes for table `billings`
--
ALTER TABLE `billings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `visit_id` (`visit_id`);

--
-- Indexes for table `contact_inquiries`
--
ALTER TABLE `contact_inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `controlled_requests`
--
ALTER TABLE `controlled_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ctrl_req_presc` (`prescription_id`),
  ADD KEY `idx_ctrl_req_status` (`status`);

--
-- Indexes for table `controlled_substances`
--
ALTER TABLE `controlled_substances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_controlled_med` (`medicine_id`);

--
-- Indexes for table `discharge_summaries`
--
ALTER TABLE `discharge_summaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_discharge_patient` (`patient_id`),
  ADD KEY `idx_discharge_status` (`status`);

--
-- Indexes for table `doctor_escalations`
--
ALTER TABLE `doctor_escalations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doctor_escalations_patient` (`patient_id`),
  ADD KEY `idx_doctor_escalations_status` (`status`);

--
-- Indexes for table `doctor_handover`
--
ALTER TABLE `doctor_handover`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doctor_handover_user` (`user_id`),
  ADD KEY `idx_doctor_handover_created` (`created_at`);

--
-- Indexes for table `doctor_tasks`
--
ALTER TABLE `doctor_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doctor_tasks_patient` (`patient_id`),
  ADD KEY `idx_doctor_tasks_status` (`status`),
  ADD KEY `idx_doctor_tasks_due` (`due_at`);

--
-- Indexes for table `drug_interactions`
--
ALTER TABLE `drug_interactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_drug_a` (`drug_a`),
  ADD KEY `idx_drug_b` (`drug_b`);

--
-- Indexes for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_grn_order` (`order_id`);

--
-- Indexes for table `insurance_claims`
--
ALTER TABLE `insurance_claims`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_claim_presc` (`prescription_id`),
  ADD KEY `idx_claim_patient` (`patient_id`),
  ADD KEY `idx_claim_status` (`status`);

--
-- Indexes for table `it_tickets`
--
ALTER TABLE `it_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_it_status` (`status`),
  ADD KEY `idx_it_priority` (`priority`),
  ADD KEY `idx_it_created` (`created_at`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `nurse_consultations`
--
ALTER TABLE `nurse_consultations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nurse_consult_queue` (`queue_id`),
  ADD KEY `idx_nurse_consult_patient` (`patient_id`),
  ADD KEY `idx_nurse_consult_nurse` (`nurse_id`),
  ADD KEY `idx_nurse_consult_created` (`created_at`);

--
-- Indexes for table `nurse_escalations`
--
ALTER TABLE `nurse_escalations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nurse_escalations_patient` (`patient_id`),
  ADD KEY `idx_nurse_escalations_status` (`status`);

--
-- Indexes for table `nurse_handover`
--
ALTER TABLE `nurse_handover`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nurse_handover_user` (`user_id`),
  ADD KEY `idx_nurse_handover_created` (`created_at`);

--
-- Indexes for table `nurse_tasks`
--
ALTER TABLE `nurse_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nurse_tasks_patient` (`patient_id`),
  ADD KEY `idx_nurse_tasks_status` (`status`),
  ADD KEY `idx_nurse_tasks_due` (`due_at`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patient_queue`
--
ALTER TABLE `patient_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_queue_patient` (`patient_id`),
  ADD KEY `idx_queue_status` (`status`),
  ADD KEY `idx_queue_doctor` (`doctor_assigned`),
  ADD KEY `idx_queue_created` (`created_at`);

--
-- Indexes for table `patient_vitals`
--
ALTER TABLE `patient_vitals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_vitals_patient` (`patient_id`),
  ADD KEY `idx_patient_vitals_queue` (`queue_id`),
  ADD KEY `idx_patient_vitals_created` (`created_at`);

--
-- Indexes for table `pharmacy_requests`
--
ALTER TABLE `pharmacy_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pharm_req_patient` (`patient_id`),
  ADD KEY `idx_pharm_req_status` (`status`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visit_id` (`visit_id`);

--
-- Indexes for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inv_order` (`order_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_po_supplier` (`supplier_id`),
  ADD KEY `idx_po_status` (`status`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_po_items_order` (`order_id`);

--
-- Indexes for table `quarantine_batches`
--
ALTER TABLE `quarantine_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quarantine_med` (`medicine_id`),
  ADD KEY `idx_quarantine_status` (`status`);

--
-- Indexes for table `reception_handover`
--
ALTER TABLE `reception_handover`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_handover_user` (`user_id`),
  ADD KEY `idx_handover_created` (`created_at`);

--
-- Indexes for table `refill_requests`
--
ALTER TABLE `refill_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_refill_patient` (`patient_id`),
  ADD KEY `idx_refill_status` (`status`);

--
-- Indexes for table `staff_shifts`
--
ALTER TABLE `staff_shifts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_staff_shift_slot` (`user_id`,`shift_start`,`shift_end`);

--
-- Indexes for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_adjust_med` (`medicine_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_supplier_name` (`name`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_session_id` (`session_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `vital_signs`
--
ALTER TABLE `vital_signs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visit_id` (`visit_id`);

--
-- Indexes for table `wards`
--
ALTER TABLE `wards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wards_name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `beds`
--
ALTER TABLE `beds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `billings`
--
ALTER TABLE `billings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_inquiries`
--
ALTER TABLE `contact_inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `controlled_requests`
--
ALTER TABLE `controlled_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `controlled_substances`
--
ALTER TABLE `controlled_substances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `discharge_summaries`
--
ALTER TABLE `discharge_summaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_escalations`
--
ALTER TABLE `doctor_escalations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_handover`
--
ALTER TABLE `doctor_handover`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_tasks`
--
ALTER TABLE `doctor_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `drug_interactions`
--
ALTER TABLE `drug_interactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `insurance_claims`
--
ALTER TABLE `insurance_claims`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `it_tickets`
--
ALTER TABLE `it_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nurse_consultations`
--
ALTER TABLE `nurse_consultations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nurse_escalations`
--
ALTER TABLE `nurse_escalations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nurse_handover`
--
ALTER TABLE `nurse_handover`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nurse_tasks`
--
ALTER TABLE `nurse_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_queue`
--
ALTER TABLE `patient_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_vitals`
--
ALTER TABLE `patient_vitals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pharmacy_requests`
--
ALTER TABLE `pharmacy_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quarantine_batches`
--
ALTER TABLE `quarantine_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reception_handover`
--
ALTER TABLE `reception_handover`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `refill_requests`
--
ALTER TABLE `refill_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_shifts`
--
ALTER TABLE `staff_shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vital_signs`
--
ALTER TABLE `vital_signs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wards`
--
ALTER TABLE `wards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `billings`
--
ALTER TABLE `billings`
  ADD CONSTRAINT `billings_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `billings_ibfk_2` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `visits`
--
ALTER TABLE `visits`
  ADD CONSTRAINT `visits_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visits_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `vital_signs`
--
ALTER TABLE `vital_signs`
  ADD CONSTRAINT `vital_signs_ibfk_1` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
