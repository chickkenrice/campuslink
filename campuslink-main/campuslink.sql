-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 27, 2026 at 04:16 PM
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
-- Database: `campuslink`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_term`
--

CREATE TABLE `academic_term` (
  `termID` int(11) NOT NULL,
  `academicYear` varchar(10) NOT NULL,
  `semester` int(11) NOT NULL,
  `programID` varchar(10) NOT NULL,
  `year` int(11) NOT NULL COMMENT 'Program year (1, 2, 3, 4)',
  `startDate` date NOT NULL,
  `endDate` date NOT NULL,
  `regStartDate` date DEFAULT NULL,
  `regEndDate` date DEFAULT NULL,
  `weeksTotal` int(11) NOT NULL COMMENT '7 for short sem, 14 for long sem',
  `status` enum('Upcoming','Active','Completed') DEFAULT 'Upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Academic terms specific to each program/year/semester combination';

--
-- Dumping data for table `academic_term`
--

INSERT INTO `academic_term` (`termID`, `academicYear`, `semester`, `programID`, `year`, `startDate`, `endDate`, `regStartDate`, `regEndDate`, `weeksTotal`, `status`, `created_at`) VALUES
(1, '2025/2026', 1, 'RSD', 1, '2026-01-26', '2026-03-15', '2026-02-19', '2026-03-20', 7, 'Active', '2026-01-29 12:27:52'),
(2, '2025/2026', 2, 'RSD', 1, '2026-03-30', '2026-07-11', '2026-02-26', '2026-03-29', 14, 'Active', '2026-01-29 12:27:52'),
(3, '2025/2026', 2, 'RSW', 1, '2026-03-30', '2026-07-11', NULL, NULL, 14, 'Active', '2026-01-29 12:27:52'),
(4, '2025/2026', 1, 'RSW', 1, '2026-01-26', '2026-03-15', NULL, NULL, 7, 'Active', '2026-01-29 12:37:02'),
(5, '2025/2026', 2, 'RSD', 2, '2026-01-26', '2026-05-03', NULL, NULL, 14, 'Active', '2026-02-11 12:40:40'),
(6, '2025/2026', 3, 'RSD', 2, '2026-05-18', '2026-08-23', '2026-02-11', '2026-05-03', 14, 'Upcoming', '2026-02-11 12:40:40');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `logID` int(11) NOT NULL,
  `userID` varchar(50) NOT NULL,
  `userName` varchar(255) DEFAULT NULL,
  `userRole` enum('Student','Staff','Admin') DEFAULT NULL,
  `activity_type` varchar(50) NOT NULL,
  `activity_description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`logID`, `userID`, `userName`, `userRole`, `activity_type`, `activity_description`, `ip_address`, `timestamp`, `details`) VALUES
(17, 'ADM01', 'Unknown', '', 'LOGOUT', 'User logged out', '::1', '2026-02-08 20:17:23', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(18, 'ADM01', 'Unknown', '', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-08 20:17:28', '{\"userName\":\"Super Admin\"}'),
(19, 'ADM01', 'Unknown', '', 'LOGOUT', 'User logged out', '::1', '2026-02-08 20:22:45', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(20, '23WP12509', 'Unknown', '', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-08 20:22:51', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(21, '23WP12509', 'Unknown', '', 'LOGOUT', 'User logged out', '::1', '2026-02-08 20:23:08', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(22, 'ADM01', 'Unknown', '', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-08 20:23:20', '{\"userName\":\"Super Admin\"}'),
(23, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-08 20:28:33', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(24, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-08 20:28:37', '{\"userName\":\"Super Admin\"}'),
(25, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-08 20:28:45', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(26, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-08 20:28:49', '{\"userName\":\"Dr. Aini Musa\"}'),
(27, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-08 20:28:51', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(28, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-08 20:28:54', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(29, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-08 20:28:55', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(30, 'U058', 'john', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-08 20:29:02', '{\"userName\":\"john\"}'),
(31, 'U058', 'john', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-08 20:30:22', '{\"userName\":\"john\",\"role\":\"admin\"}'),
(32, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-08 20:30:27', '{\"userName\":\"Dr. Aini Musa\"}'),
(33, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-08 20:31:08', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(34, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-08 20:31:13', '{\"userName\":\"Super Admin\"}'),
(35, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-08 21:12:58', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(36, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-08 21:13:02', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(37, 'U013', 'Arvind A/L Subramaniam', 'Student', 'ASSIGNMENT_SUBMIT', 'Student submitted assignment: Etika Portfolio for Penghayatan Etika dan Peradaban (MPU3103)', '::1', '2026-02-08 21:13:24', '{\"assignmentID\":\"2\",\"title\":\"Etika Portfolio\",\"courseID\":\"MPU3103\",\"fileName\":\"1770556404_23WP12509_Chua Jian Xi_Week 2.pdf\",\"submissionStatus\":\"Late\"}'),
(38, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-08 21:13:28', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(39, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-08 21:13:33', '{\"userName\":\"Super Admin\"}'),
(40, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-08 21:35:54', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(41, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-08 21:35:57', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(42, 'U013', 'Arvind A/L Subramaniam', 'Student', 'PROFILE_UPDATE', 'Student updated their profile', '::1', '2026-02-08 21:36:12', '{\"contactNo\":\"012-2262226\",\"homeAddress\":\"No. 15, Jalan 2\\/14, Taman Melawati, 53100 Kuala Lumpur\",\"corrAddress\":\"92, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia\",\"parentContact\":\"014-4425384\"}'),
(43, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-08 21:36:16', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(44, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-08 21:36:21', '{\"userName\":\"Super Admin\"}'),
(45, 'U001', 'Super Admin', 'Admin', 'USER_DELETE', 'Deleted user account: U059', '::1', '2026-02-08 21:37:12', '{\"deletedUserID\":\"U059\",\"deletedUserRole\":\"Staff\"}'),
(46, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-08 21:45:53', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(47, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-08 21:45:58', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(48, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-08 21:46:23', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(49, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-08 21:46:28', '{\"userName\":\"Dr. Aini Musa\"}'),
(50, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-08 21:48:28', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(51, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-08 21:48:33', '{\"userName\":\"Super Admin\"}'),
(52, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-08 21:56:34', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(53, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-08 21:56:38', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(54, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-08 21:57:18', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(55, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-08 21:57:26', '{\"userName\":\"Dr. Aini Musa\"}'),
(56, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-08 21:58:12', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(57, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-08 21:58:19', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(58, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 02:13:53', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(59, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 02:14:08', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(60, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 02:14:12', '{\"userName\":\"Super Admin\"}'),
(61, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 02:14:38', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(62, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 02:14:55', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(63, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 02:17:28', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(64, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 02:20:02', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(65, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 02:20:15', '{\"userName\":\"Super Admin\"}'),
(66, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 02:20:18', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(67, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 02:20:28', '{\"userName\":\"Super Admin\"}'),
(68, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 02:20:47', '{\"userName\":\"Dr. Aini Musa\"}'),
(69, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 02:21:52', '{\"userName\":\"Super Admin\"}'),
(70, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 02:22:11', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(71, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 02:23:40', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(72, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 02:23:51', '{\"userName\":\"Dr. Aini Musa\"}'),
(73, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 02:26:28', '{\"userName\":\"Dr. Aini Musa\"}'),
(74, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 02:26:47', '{\"userName\":\"Super Admin\"}'),
(75, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 02:43:05', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(76, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 02:43:10', '{\"userName\":\"Dr. Aini Musa\"}'),
(77, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 02:45:29', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(78, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 02:45:33', '{\"userName\":\"Super Admin\"}'),
(79, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 02:46:30', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(80, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 02:46:34', '{\"userName\":\"Dr. Aini Musa\"}'),
(81, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 02:51:48', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(82, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 02:51:52', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(83, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:00:51', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(84, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 03:00:55', '{\"userName\":\"Super Admin\"}'),
(85, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:01:13', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(86, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 03:01:17', '{\"userName\":\"Dr. Aini Musa\"}'),
(87, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:02:51', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(88, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 03:02:55', '{\"userName\":\"Super Admin\"}'),
(89, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:03:10', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(90, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 03:03:14', '{\"userName\":\"Dr. Aini Musa\"}'),
(91, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:16:59', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(92, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 03:17:04', '{\"userName\":\"Dr. Aini Musa\"}'),
(93, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:17:13', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(94, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 03:17:16', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(95, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:17:23', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(96, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 03:17:28', '{\"userName\":\"Dr. Aini Musa\"}'),
(97, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:19:19', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(98, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 03:19:23', '{\"userName\":\"Super Admin\"}'),
(99, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:19:40', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(100, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 03:19:45', '{\"userName\":\"Dr. Aini Musa\"}'),
(101, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:21:08', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(102, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 03:21:29', '{\"userName\":\"Dr. Wong Siew\"}'),
(103, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:27:15', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(104, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 03:27:26', '{\"userName\":\"Dr. Aini Musa\"}'),
(105, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:36:25', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(106, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 03:36:28', '{\"userName\":\"Super Admin\"}'),
(107, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:37:01', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(108, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 03:37:06', '{\"userName\":\"Dr. Aini Musa\"}'),
(109, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:49:42', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(110, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 03:49:46', '{\"userName\":\"Super Admin\"}'),
(111, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:50:05', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(112, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 03:50:08', '{\"userName\":\"Dr. Aini Musa\"}'),
(113, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:50:20', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(114, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 03:50:33', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(115, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:50:39', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(116, 'U014', 'Divya A/P Kumar', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 03:50:42', '{\"userName\":\"Divya A\\/P Kumar\"}'),
(117, 'U014', 'Divya A/P Kumar', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:51:13', '{\"userName\":\"Divya A\\/P Kumar\",\"role\":\"student\"}'),
(118, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 03:51:17', '{\"userName\":\"Dr. Aini Musa\"}'),
(119, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:51:58', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(120, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 03:52:02', '{\"userName\":\"Super Admin\"}'),
(121, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:52:15', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(122, 'U014', 'Divya A/P Kumar', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 03:52:19', '{\"userName\":\"Divya A\\/P Kumar\"}'),
(123, 'U014', 'Divya A/P Kumar', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:52:32', '{\"userName\":\"Divya A\\/P Kumar\",\"role\":\"student\"}'),
(124, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 03:52:43', '{\"userName\":\"Dr. Aini Musa\"}'),
(125, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:52:56', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(126, 'U014', 'Divya A/P Kumar', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 03:53:00', '{\"userName\":\"Divya A\\/P Kumar\"}'),
(127, 'U014', 'Divya A/P Kumar', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:53:05', '{\"userName\":\"Divya A\\/P Kumar\",\"role\":\"student\"}'),
(128, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 03:53:09', '{\"userName\":\"Dr. Aini Musa\"}'),
(129, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:54:14', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(130, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 03:54:17', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(131, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:54:19', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(132, 'U014', 'Divya A/P Kumar', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 03:54:23', '{\"userName\":\"Divya A\\/P Kumar\"}'),
(133, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 03:59:46', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(134, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 03:59:49', '{\"userName\":\"Super Admin\"}'),
(135, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 04:02:19', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(136, 'U058', 'john', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 04:02:34', '{\"userName\":\"john\"}'),
(137, 'U058', 'john', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 04:03:50', '{\"userName\":\"john\",\"role\":\"admin\"}'),
(138, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 04:03:54', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(139, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 04:19:23', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(140, 'U014', 'Divya A/P Kumar', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 04:19:27', '{\"userName\":\"Divya A\\/P Kumar\"}'),
(141, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 13:34:41', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(142, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 13:34:43', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(143, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 13:34:46', '{\"userName\":\"Dr. Aini Musa\"}'),
(144, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 13:34:57', '{\"userName\":\"Super Admin\"}'),
(145, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 13:35:22', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(146, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 13:35:30', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(147, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 13:36:56', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(148, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 13:37:00', '{\"userName\":\"Super Admin\"}'),
(149, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 13:42:49', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(150, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 13:42:54', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(151, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 13:43:10', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(152, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 13:43:14', '{\"userName\":\"Dr. Aini Musa\"}'),
(153, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 13:43:26', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(154, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 13:43:30', '{\"userName\":\"Super Admin\"}'),
(155, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 13:45:08', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(156, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 13:45:13', '{\"userName\":\"Super Admin\"}'),
(157, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 13:51:14', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(158, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 13:51:18', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(159, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 14:50:54', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(160, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 14:50:58', '{\"userName\":\"Super Admin\"}'),
(161, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 18:49:41', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(162, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 18:49:47', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(163, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 19:08:55', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(164, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 19:08:59', '{\"userName\":\"Dr. Aini Musa\"}'),
(165, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 19:16:43', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(166, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 19:16:49', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(167, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 21:07:22', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(168, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 21:07:26', '{\"userName\":\"Dr. Aini Musa\"}'),
(169, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 21:16:58', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(170, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 21:17:02', '{\"userName\":\"Super Admin\"}'),
(171, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 21:20:35', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(172, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 21:20:38', '{\"userName\":\"Dr. Aini Musa\"}'),
(173, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 21:22:19', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(174, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 21:22:26', '{\"userName\":\"Super Admin\"}'),
(175, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 21:22:52', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(176, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 21:22:57', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(177, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 21:23:03', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(178, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 21:23:07', '{\"userName\":\"Super Admin\"}'),
(179, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 21:25:14', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(180, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 21:26:22', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(181, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 21:26:34', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(182, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 21:26:38', '{\"userName\":\"Super Admin\"}'),
(183, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 21:29:46', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(184, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 21:29:52', '{\"userName\":\"Dr. Aini Musa\"}'),
(185, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 21:31:53', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(186, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 21:31:57', '{\"userName\":\"Super Admin\"}'),
(187, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 21:33:25', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(188, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 21:33:29', '{\"userName\":\"Dr. Aini Musa\"}'),
(189, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 21:33:56', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(190, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 21:34:04', '{\"userName\":\"Dr. Aini Musa\"}'),
(191, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 21:34:08', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(192, 'U014', 'Divya A/P Kumar', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 21:34:12', '{\"userName\":\"Divya A\\/P Kumar\"}'),
(193, 'U014', 'Divya A/P Kumar', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 21:40:12', '{\"userName\":\"Divya A\\/P Kumar\",\"role\":\"student\"}'),
(194, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 21:40:16', '{\"userName\":\"Dr. Aini Musa\"}'),
(195, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 21:43:53', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(196, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 21:43:57', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(197, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 21:46:08', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(198, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 21:46:13', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(199, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 21:47:18', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(200, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 21:47:39', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(201, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-09 21:47:43', '{\"userName\":\"Dr. Aini Musa\"}'),
(202, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-09 21:49:53', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(203, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-09 21:53:15', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(204, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-09 23:52:17', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(205, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 23:52:22', '{\"userName\":\"Super Admin\"}'),
(206, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-09 23:52:23', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(207, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-09 23:52:28', '{\"userName\":\"Super Admin\"}'),
(208, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 19:38:18', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(209, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-11 19:55:31', '{\"userName\":\"Dr. Aini Musa\"}'),
(210, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 20:13:53', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(211, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-11 20:14:56', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(212, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-11 20:15:00', '{\"userName\":\"Super Admin\"}'),
(213, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-11 20:15:10', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(214, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-11 20:15:14', '{\"userName\":\"Dr. Aini Musa\"}'),
(215, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-11 20:15:57', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(216, 'U014', 'Divya A/P Kumar', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 20:16:01', '{\"userName\":\"Divya A\\/P Kumar\"}'),
(217, 'U014', 'Divya A/P Kumar', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 20:16:11', '{\"userName\":\"Divya A\\/P Kumar\",\"role\":\"student\"}'),
(218, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-11 20:16:16', '{\"userName\":\"Dr. Aini Musa\"}'),
(219, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-11 20:16:28', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(220, 'U023', 'Azman Bin Sulaiman', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 20:16:32', '{\"userName\":\"Azman Bin Sulaiman\"}'),
(221, 'U023', 'Azman Bin Sulaiman', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 20:16:36', '{\"userName\":\"Azman Bin Sulaiman\",\"role\":\"student\"}'),
(222, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-11 20:16:40', '{\"userName\":\"Dr. Aini Musa\"}'),
(223, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-11 20:16:51', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(224, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-11 20:17:05', '{\"userName\":\"Dr. Aini Musa\"}'),
(225, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-11 20:17:20', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(226, 'U042', 'Bryan Wong Jun Kit', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 20:17:23', '{\"userName\":\"Bryan Wong Jun Kit\"}'),
(227, 'U042', 'Bryan Wong Jun Kit', 'Student', 'ATTENDANCE', 'Student took attendance for English for Tertiary Studies (BJEL1713) - Status: Late', '::1', '2026-02-11 20:17:37', '{\"scheduleID\":\"23\",\"courseID\":\"BJEL1713\",\"courseName\":\"English for Tertiary Studies\",\"date\":\"2026-02-11\",\"scanTime\":\"20:17:37\",\"status\":\"Late\"}'),
(228, 'U042', 'Bryan Wong Jun Kit', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 20:17:56', '{\"userName\":\"Bryan Wong Jun Kit\",\"role\":\"student\"}'),
(229, 'U057', 'Alex Chen Wei Jie', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 20:18:15', '{\"userName\":\"Alex Chen Wei Jie\"}'),
(230, 'U057', 'Alex Chen Wei Jie', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 20:42:15', '{\"userName\":\"Alex Chen Wei Jie\",\"role\":\"student\"}'),
(231, 'U051', 'Ahmad Syahmi Bin Mohd Rizal', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 20:42:18', '{\"userName\":\"Ahmad Syahmi Bin Mohd Rizal\"}'),
(232, 'U051', 'Ahmad Syahmi Bin Mohd Rizal', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 21:03:04', '{\"userName\":\"Ahmad Syahmi Bin Mohd Rizal\",\"role\":\"student\"}'),
(233, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-11 21:03:08', '{\"userName\":\"Dr. Aini Musa\"}'),
(234, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-11 21:04:18', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(235, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 21:04:21', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(236, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 21:08:51', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(237, 'U014', 'Divya A/P Kumar', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 21:08:59', '{\"userName\":\"Divya A\\/P Kumar\"}'),
(238, 'U014', 'Divya A/P Kumar', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 21:14:27', '{\"userName\":\"Divya A\\/P Kumar\",\"role\":\"student\"}'),
(239, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 21:14:32', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(240, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 21:14:50', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(241, 'U059', 'Test Student Y2S2', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 21:15:00', '{\"userName\":\"Test Student Y2S2\"}'),
(242, 'U059', 'Test Student Y2S2', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 21:18:21', '{\"userName\":\"Test Student Y2S2\",\"role\":\"student\"}'),
(243, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 21:18:26', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(244, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 21:18:32', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(245, 'U059', 'Test Student Y2S2', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 21:18:37', '{\"userName\":\"Test Student Y2S2\"}'),
(246, 'U059', 'Test Student Y2S2', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 21:20:06', '{\"userName\":\"Test Student Y2S2\",\"role\":\"student\"}'),
(247, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-11 21:20:14', '{\"userName\":\"Dr. Aini Musa\"}'),
(248, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-11 21:20:42', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(249, 'U015', 'Teoh Kah Mun', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 21:20:53', '{\"userName\":\"Teoh Kah Mun\"}'),
(250, 'U015', 'Teoh Kah Mun', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 21:21:12', '{\"userName\":\"Teoh Kah Mun\",\"role\":\"student\"}'),
(251, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-11 21:21:21', '{\"userName\":\"Dr. Aini Musa\"}'),
(252, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-11 21:21:29', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(253, 'U014', 'Divya A/P Kumar', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 21:21:34', '{\"userName\":\"Divya A\\/P Kumar\"}'),
(254, 'U014', 'Divya A/P Kumar', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 21:21:44', '{\"userName\":\"Divya A\\/P Kumar\",\"role\":\"student\"}'),
(255, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-11 21:21:51', '{\"userName\":\"Dr. Aini Musa\"}'),
(256, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-11 21:30:21', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(257, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-11 21:30:28', '{\"userName\":\"Super Admin\"}'),
(258, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-11 21:33:36', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(259, 'U014', 'Divya A/P Kumar', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 22:13:30', '{\"userName\":\"Divya A\\/P Kumar\"}'),
(260, 'U014', 'Divya A/P Kumar', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 22:13:48', '{\"userName\":\"Divya A\\/P Kumar\",\"role\":\"student\"}'),
(261, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-11 22:13:52', '{\"userName\":\"Dr. Aini Musa\"}'),
(262, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-11 22:14:22', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(263, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 22:14:26', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(264, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 22:14:40', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(265, 'U059', 'Test Student Y2S2', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 22:14:57', '{\"userName\":\"Test Student Y2S2\"}'),
(266, 'U059', 'Test Student Y2S2', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 22:35:13', '{\"userName\":\"Test Student Y2S2\",\"role\":\"student\"}'),
(267, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-11 22:35:18', '{\"userName\":\"Super Admin\"}'),
(268, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-11 22:50:51', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(269, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 22:50:55', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(270, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 22:51:55', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(271, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-11 22:52:06', '{\"userName\":\"Super Admin\"}'),
(272, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-11 22:53:36', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(273, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 22:53:42', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(274, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 22:55:59', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(275, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 22:56:02', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(276, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 23:00:29', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(277, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-11 23:00:34', '{\"userName\":\"Super Admin\"}'),
(278, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-11 23:03:02', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(279, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-11 23:03:10', '{\"userName\":\"Dr. Aini Musa\"}'),
(280, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-11 23:04:45', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(281, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-11 23:04:50', '{\"userName\":\"Super Admin\"}'),
(282, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-11 23:06:42', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(283, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 23:08:43', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(284, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 23:15:17', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(285, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 23:15:39', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(286, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 23:15:41', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(287, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-11 23:15:49', '{\"userName\":\"Super Admin\"}'),
(288, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-11 23:17:43', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(289, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-11 23:17:47', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(290, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-11 23:18:02', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(291, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-11 23:18:05', '{\"userName\":\"Super Admin\"}'),
(292, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-11 23:21:31', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(293, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-11 23:21:36', '{\"userName\":\"Dr. Aini Musa\"}'),
(294, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-11 23:21:42', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(295, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-11 23:21:46', '{\"userName\":\"Super Admin\"}'),
(296, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-11 23:31:03', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(297, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-11 23:31:06', '{\"userName\":\"Dr. Aini Musa\"}'),
(298, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-11 23:31:14', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(299, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-11 23:31:18', '{\"userName\":\"Super Admin\"}'),
(300, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-11 23:38:04', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(301, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-12 15:41:17', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(302, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-12 15:42:07', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(303, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-12 15:42:11', '{\"userName\":\"Dr. Wong Siew\"}'),
(304, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-12 15:42:38', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(305, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-12 15:42:49', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(306, 'U013', 'Arvind A/L Subramaniam', 'Student', 'PROFILE_UPDATE', 'Student updated their profile', '::1', '2026-02-12 16:04:30', '{\"contactNo\":\"012-2262226\"}'),
(307, '23WP12509', 'Unknown', '', 'PROFILE_UPDATE', 'Student removed their profile photo', '::1', '2026-02-12 16:27:21', NULL),
(308, '23WP12509', 'Unknown', '', 'PROFILE_UPDATE', 'Student removed their profile photo', '::1', '2026-02-12 16:27:25', NULL),
(309, 'U013', 'Arvind A/L Subramaniam', 'Student', 'PROFILE_UPDATE', 'Student updated their profile', '::1', '2026-02-12 16:32:34', '{\"contactNo\":\"012-2262226\"}'),
(310, '23WP12509', 'Unknown', '', 'PROFILE_UPDATE', 'Student removed their profile photo', '::1', '2026-02-12 16:34:36', NULL),
(311, 'U013', 'Arvind A/L Subramaniam', 'Student', 'PROFILE_UPDATE', 'Student updated their profile', '::1', '2026-02-12 16:39:56', '{\"contactNo\":\"012-2262226\"}'),
(312, '23WP12509', 'Unknown', '', 'PROFILE_UPDATE', 'Student removed their profile photo', '::1', '2026-02-12 16:40:03', NULL),
(313, 'U013', 'Arvind A/L Subramaniam', 'Student', 'PROFILE_UPDATE', 'Student updated their profile', '::1', '2026-02-12 16:43:15', '{\"contactNo\":\"012-2262226\"}'),
(314, '23WP12509', 'Unknown', '', 'PROFILE_UPDATE', 'Student removed their profile photo', '::1', '2026-02-12 16:43:18', NULL),
(315, 'U013', 'Arvind A/L Subramaniam', 'Student', 'PROFILE_UPDATE', 'Student updated their profile', '::1', '2026-02-12 16:43:20', '{\"contactNo\":\"012-2262226\"}'),
(316, 'U013', 'Arvind A/L Subramaniam', 'Student', 'PROFILE_UPDATE', 'Student updated their profile', '::1', '2026-02-12 16:43:22', '{\"contactNo\":\"012-2262226\"}'),
(317, 'U013', 'Arvind A/L Subramaniam', 'Student', 'PROFILE_UPDATE', 'Student updated their profile', '::1', '2026-02-12 16:43:27', '{\"contactNo\":\"012-2262226\"}'),
(318, '23WP12509', 'Unknown', '', 'PROFILE_UPDATE', 'Student removed their profile photo', '::1', '2026-02-12 16:43:30', NULL),
(319, 'U013', 'Arvind A/L Subramaniam', 'Student', 'PROFILE_UPDATE', 'Student updated their profile', '::1', '2026-02-12 16:43:31', '{\"contactNo\":\"012-2262226\"}'),
(320, 'U013', 'Arvind A/L Subramaniam', 'Student', 'PROFILE_UPDATE', 'Student updated their profile', '::1', '2026-02-12 16:43:37', '{\"contactNo\":\"012-2262226\"}'),
(321, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-12 16:44:10', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(322, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-12 16:44:15', '{\"userName\":\"Dr. Wong Siew\"}'),
(323, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-12 16:54:18', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(324, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-12 16:54:22', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(325, 'U013', 'Arvind A/L Subramaniam', 'Student', 'PROFILE_UPDATE', 'Student updated their profile', '::1', '2026-02-12 16:54:36', '{\"contactNo\":\"012-2262226\"}'),
(326, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-12 16:54:40', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(327, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-12 16:54:45', '{\"userName\":\"Dr. Wong Siew\"}'),
(328, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-12 17:02:56', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(329, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-12 17:03:02', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(330, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-12 17:06:51', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(331, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-12 17:06:56', '{\"userName\":\"Dr. Wong Siew\"}'),
(332, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-12 17:07:42', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}');
INSERT INTO `activity_logs` (`logID`, `userID`, `userName`, `userRole`, `activity_type`, `activity_description`, `ip_address`, `timestamp`, `details`) VALUES
(333, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-12 17:07:53', '{\"userName\":\"Dr. Wong Siew\"}'),
(334, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-12 17:08:04', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(335, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-12 17:08:30', '{\"userName\":\"Super Admin\"}'),
(336, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-12 17:24:03', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(337, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-12 17:24:54', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(338, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-12 17:24:59', '{\"userName\":\"Dr. Wong Siew\"}'),
(339, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-12 17:26:14', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(340, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-12 17:28:09', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(341, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-12 17:35:23', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(342, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-12 17:35:28', '{\"userName\":\"Dr. Wong Siew\"}'),
(343, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-12 17:41:30', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(344, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-12 17:41:36', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(345, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-12 17:45:36', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(346, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-12 17:45:40', '{\"userName\":\"Dr. Wong Siew\"}'),
(347, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-12 17:49:45', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(348, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-12 17:49:49', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(349, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-12 17:52:41', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(350, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-12 17:52:45', '{\"userName\":\"Dr. Wong Siew\"}'),
(351, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-12 17:53:56', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(352, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-12 17:54:00', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(353, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-12 21:38:08', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(354, '23WP12509', 'Unknown', '', 'PROFILE_UPDATE', 'Student removed their profile photo', '::1', '2026-02-12 21:38:16', NULL),
(355, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-12 21:43:44', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(356, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-12 22:22:48', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(357, 'U013', 'Arvind A/L Subramaniam', 'Student', 'PROFILE_UPDATE', 'Student updated their profile', '::1', '2026-02-12 22:23:50', '{\"contactNo\":\"012-2262226\"}'),
(358, 'U013', 'Arvind A/L Subramaniam', 'Student', 'PROFILE_UPDATE', 'Student updated their profile', '::1', '2026-02-12 22:27:20', '{\"contactNo\":\"012-2262226\"}'),
(359, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-12 22:27:38', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(360, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-12 22:27:43', '{\"userName\":\"Dr. Wong Siew\"}'),
(361, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-12 22:28:27', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(362, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-12 22:30:03', '{\"userName\":\"Super Admin\"}'),
(363, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-12 22:30:31', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(364, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-13 20:03:32', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(365, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-13 20:05:31', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(366, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-13 20:05:37', '{\"userName\":\"Dr. Wong Siew\"}'),
(367, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-13 20:05:57', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(368, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-13 20:06:03', '{\"userName\":\"Super Admin\"}'),
(369, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-13 20:13:31', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(370, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-13 20:13:37', '{\"userName\":\"Dr. Wong Siew\"}'),
(371, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-13 20:23:44', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(372, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-13 20:23:49', '{\"userName\":\"Super Admin\"}'),
(373, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-13 20:47:04', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(374, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-13 20:47:10', '{\"userName\":\"Dr. Wong Siew\"}'),
(375, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-13 21:07:25', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(376, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-13 21:58:02', '{\"userName\":\"Dr. Wong Siew\"}'),
(377, 'S007', 'Unknown', '', 'STUDENT_REMOVAL', 'Removed student Azman Bin Sulaiman (23WP27671) from RSW Group B. Reason: Test', '::1', '2026-02-13 21:59:22', NULL),
(378, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-13 21:59:50', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(379, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-13 21:59:56', '{\"userName\":\"Super Admin\"}'),
(380, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-13 22:00:35', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(381, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-13 22:00:40', '{\"userName\":\"Dr. Wong Siew\"}'),
(382, 'S007', 'Unknown', '', 'STUDENT_REINSTATEMENT', 'Reinstated student Azman Bin Sulaiman (23WP27671) back to RSW Group B', '::1', '2026-02-13 22:01:10', NULL),
(383, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-13 22:39:42', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(384, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-13 22:39:49', '{\"userName\":\"Super Admin\"}'),
(385, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-13 22:40:43', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(386, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-13 22:40:48', '{\"userName\":\"Dr. Aini Musa\"}'),
(387, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-13 22:42:18', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(388, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-13 22:42:24', '{\"userName\":\"Super Admin\"}'),
(389, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-13 22:43:04', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(390, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-13 22:43:10', '{\"userName\":\"Dr. Aini Musa\"}'),
(391, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-13 22:56:27', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(392, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-13 22:56:39', '{\"userName\":\"Dr. Aini Musa\"}'),
(393, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-13 22:56:44', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(394, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-13 22:56:51', '{\"userName\":\"Dr. Aini Musa\"}'),
(395, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-13 22:56:58', '{\"userName\":\"Dr. Wong Siew\"}'),
(396, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-13 22:58:04', '{\"userName\":\"Super Admin\"}'),
(397, 'S007', 'Unknown', '', 'STUDENT_REMOVAL', 'Removed student Arvind A/L Subramaniam (23WP12509) from RSD Group A. Reason: Missing', '::1', '2026-02-13 22:59:25', NULL),
(398, 'S007', 'Unknown', '', 'STUDENT_REINSTATEMENT', 'Reinstated student Arvind A/L Subramaniam (23WP12509) back to RSD Group A', '::1', '2026-02-13 23:00:11', NULL),
(399, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-13 23:00:24', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(400, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-13 23:00:31', '{\"userName\":\"Dr. Aini Musa\"}'),
(401, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-13 23:03:38', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(402, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-13 23:03:43', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(403, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-13 23:03:52', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(404, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-13 23:04:00', '{\"userName\":\"Dr. Aini Musa\"}'),
(405, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-13 23:07:20', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(406, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-13 23:07:24', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(407, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-13 23:10:09', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(408, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-13 23:10:15', '{\"userName\":\"Dr. Wong Siew\"}'),
(409, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-13 23:11:21', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(410, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-13 23:11:26', '{\"userName\":\"Dr. Aini Musa\"}'),
(411, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-13 23:12:07', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(412, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-13 23:12:12', '{\"userName\":\"Super Admin\"}'),
(413, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-13 23:13:02', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(414, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-13 23:13:10', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(415, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-13 23:13:54', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(416, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-14 19:07:43', '{\"userName\":\"Dr. Aini Musa\"}'),
(417, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-14 19:53:02', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(418, 'U057', 'Alex Chen Wei Jie', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-14 19:53:06', '{\"userName\":\"Alex Chen Wei Jie\"}'),
(419, 'U057', 'Alex Chen Wei Jie', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-14 19:53:08', '{\"userName\":\"Alex Chen Wei Jie\",\"role\":\"student\"}'),
(420, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-14 19:53:18', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(421, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-14 19:53:23', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(422, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-14 19:53:28', '{\"userName\":\"Dr. Aini Musa\"}'),
(423, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-14 19:53:56', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(424, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-14 19:54:01', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(425, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-14 19:58:53', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(426, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-14 19:58:56', '{\"userName\":\"Dr. Aini Musa\"}'),
(427, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-14 19:59:47', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(428, 'U059', 'Test Student Y2S2', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-14 19:59:52', '{\"userName\":\"Test Student Y2S2\"}'),
(429, 'U003', 'Mr. Tan Ah Kao', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-14 20:00:17', '{\"userName\":\"Mr. Tan Ah Kao\"}'),
(430, 'U059', 'Test Student Y2S2', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-14 20:01:58', '{\"userName\":\"Test Student Y2S2\",\"role\":\"student\"}'),
(431, 'U014', 'Divya A/P Kumar', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-14 20:02:01', '{\"userName\":\"Divya A\\/P Kumar\"}'),
(432, 'U014', 'Divya A/P Kumar', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-14 20:20:36', '{\"userName\":\"Divya A\\/P Kumar\",\"role\":\"student\"}'),
(433, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-14 20:20:39', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(434, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-14 20:20:50', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(435, 'U014', 'Divya A/P Kumar', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-14 20:20:52', '{\"userName\":\"Divya A\\/P Kumar\"}'),
(436, 'U014', 'Divya A/P Kumar', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-14 20:29:34', '{\"userName\":\"Divya A\\/P Kumar\",\"role\":\"student\"}'),
(437, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-14 20:29:37', '{\"userName\":\"Dr. Aini Musa\"}'),
(438, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-14 20:30:15', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(439, 'U003', 'Mr. Tan Ah Kao', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-14 20:34:29', '{\"userName\":\"Mr. Tan Ah Kao\",\"role\":\"staff\"}'),
(440, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-14 20:42:43', '{\"userName\":\"Super Admin\"}'),
(441, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-14 21:28:52', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(442, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-14 21:31:25', '{\"userName\":\"Dr. Aini Musa\"}'),
(443, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-14 21:42:10', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(444, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-14 21:42:17', '{\"userName\":\"Dr. Aini Musa\"}'),
(445, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-14 21:55:36', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(446, 'U003', 'Mr. Tan Ah Kao', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-14 21:55:39', '{\"userName\":\"Mr. Tan Ah Kao\"}'),
(447, 'U003', 'Mr. Tan Ah Kao', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-14 21:55:47', '{\"userName\":\"Mr. Tan Ah Kao\",\"role\":\"staff\"}'),
(448, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-14 21:55:50', '{\"userName\":\"Dr. Aini Musa\"}'),
(449, 'S001', 'Unknown', '', 'STUDENT_REMOVAL', 'Removed student Alex Chen Wei Jie (23WP99001) from RSD Group A. Reason: racist', '::1', '2026-02-14 21:56:42', NULL),
(450, 'S001', 'Unknown', '', 'STUDENT_REINSTATEMENT', 'Reinstated student Alex Chen Wei Jie (23WP99001) back to RSD Group A', '::1', '2026-02-14 21:56:54', NULL),
(451, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-14 21:56:59', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(452, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-14 21:57:03', '{\"userName\":\"Dr. Aini Musa\"}'),
(453, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-14 21:57:20', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(454, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-14 21:57:24', '{\"userName\":\"Super Admin\"}'),
(455, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-14 21:57:55', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(456, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-14 21:57:59', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(457, '23WP12509', 'Unknown', '', 'PROFILE_UPDATE', 'Student removed their profile photo', '::1', '2026-02-14 21:58:26', NULL),
(458, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-14 22:01:55', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(459, 'U003', 'Mr. Tan Ah Kao', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-14 22:02:05', '{\"userName\":\"Mr. Tan Ah Kao\"}'),
(460, 'U003', 'Mr. Tan Ah Kao', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-14 22:02:41', '{\"userName\":\"Mr. Tan Ah Kao\",\"role\":\"staff\"}'),
(461, 'U011', 'Ms. Pong Suk Fun', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-14 22:03:11', '{\"userName\":\"Ms. Pong Suk Fun\"}'),
(462, 'U011', 'Ms. Pong Suk Fun', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-14 22:04:50', '{\"userName\":\"Ms. Pong Suk Fun\",\"role\":\"staff\"}'),
(463, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-14 22:28:02', '{\"userName\":\"Super Admin\"}'),
(464, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-14 22:30:58', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(465, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-14 22:31:11', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(466, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-14 22:31:33', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(467, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-14 22:31:37', '{\"userName\":\"Super Admin\"}'),
(468, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-14 22:35:09', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(469, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-14 22:35:12', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(470, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-15 15:23:02', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(471, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-15 15:23:52', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(472, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-15 15:23:57', '{\"userName\":\"Dr. Aini Musa\"}'),
(473, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-15 15:29:37', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(474, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-15 15:29:42', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(475, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-15 15:29:50', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(476, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-15 15:29:54', '{\"userName\":\"Dr. Aini Musa\"}'),
(477, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-15 15:30:07', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(478, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-15 15:30:11', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(479, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-15 15:33:09', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(480, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-15 15:33:14', '{\"userName\":\"Dr. Aini Musa\"}'),
(481, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-15 15:40:37', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(482, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-15 15:40:43', '{\"userName\":\"Super Admin\"}'),
(483, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-15 15:41:00', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(484, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-15 15:43:48', '{\"userName\":\"Dr. Aini Musa\"}'),
(485, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-15 15:44:11', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(486, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-15 15:44:15', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(487, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-15 15:44:22', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(488, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-15 15:44:31', '{\"userName\":\"Dr. Aini Musa\"}'),
(489, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-15 18:38:30', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(490, 'U013', 'Arvind A/L Subramaniam', 'Student', 'PROFILE_UPDATE', 'Student updated their profile', '::1', '2026-02-15 18:39:01', '{\"contactNo\":\"012-2262226\"}'),
(491, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-15 18:41:44', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(492, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-15 18:41:50', '{\"userName\":\"Super Admin\"}'),
(493, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-15 18:42:02', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(494, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-15 18:42:08', '{\"userName\":\"Dr. Wong Siew\"}'),
(495, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-15 18:42:16', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(496, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-15 18:42:21', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(497, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-15 18:49:21', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(498, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-15 18:49:26', '{\"userName\":\"Dr. Wong Siew\"}'),
(499, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-15 18:51:58', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(500, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-15 18:52:04', '{\"userName\":\"Dr. Aini Musa\"}'),
(501, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-15 18:53:25', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(502, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-15 18:53:29', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(503, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-15 19:08:11', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(504, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-15 19:08:17', '{\"userName\":\"Dr. Aini Musa\"}'),
(505, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-15 19:10:49', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(506, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-15 19:10:54', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(507, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-15 19:11:09', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(508, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-15 19:11:15', '{\"userName\":\"Dr. Aini Musa\"}'),
(509, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-15 19:16:36', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(510, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-15 19:16:41', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(511, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-15 19:22:15', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(512, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-15 19:22:20', '{\"userName\":\"Dr. Aini Musa\"}'),
(513, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-15 21:27:13', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(514, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-15 21:27:32', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(515, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-15 21:27:37', '{\"userName\":\"Dr. Wong Siew\"}'),
(516, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-15 21:27:46', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(517, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-15 21:27:51', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(518, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-15 21:40:00', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(519, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-15 21:40:04', '{\"userName\":\"Dr. Aini Musa\"}'),
(520, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-15 21:41:34', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(521, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-15 21:44:04', '{\"userName\":\"Dr. Aini Musa\"}'),
(522, 'U014', 'Divya A/P Kumar', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-15 21:49:17', '{\"userName\":\"Divya A\\/P Kumar\"}'),
(523, 'U014', 'Divya A/P Kumar', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-15 21:49:22', '{\"userName\":\"Divya A\\/P Kumar\",\"role\":\"student\"}'),
(524, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-15 21:49:26', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(525, 'U013', 'Arvind A/L Subramaniam', 'Student', 'ATTENDANCE', 'Student took attendance for English for Tertiary Studies (BJEL1713) - Status: Late', '::1', '2026-02-15 21:56:58', '{\"scheduleID\":\"59\",\"courseID\":\"BJEL1713\",\"courseName\":\"English for Tertiary Studies\",\"date\":\"2026-02-15\",\"scanTime\":\"21:56:58\",\"status\":\"Late\"}'),
(526, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-15 21:59:31', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(527, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-15 22:01:00', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(528, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-15 22:01:04', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(529, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-15 22:01:19', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(530, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-15 22:01:25', '{\"userName\":\"Dr. Wong Siew\"}'),
(531, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-15 22:03:17', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(532, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-15 22:03:21', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(533, 'U013', 'Arvind A/L Subramaniam', 'Student', 'PROFILE_UPDATE', 'Student updated their profile', '::1', '2026-02-15 22:09:08', '{\"contactNo\":\"012-2262226\"}'),
(534, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-15 22:09:18', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(535, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-15 22:09:23', '{\"userName\":\"Dr. Wong Siew\"}'),
(536, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-15 22:09:39', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(537, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-20 22:06:52', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(538, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-20 22:08:00', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(539, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-20 22:08:05', '{\"userName\":\"Dr. Wong Siew\"}'),
(540, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-20 22:09:08', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(541, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-20 22:12:19', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(542, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-20 22:20:48', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(543, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-20 22:20:53', '{\"userName\":\"Dr. Wong Siew\"}'),
(544, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-20 22:24:04', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(545, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-20 22:24:08', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(546, 'U013', 'Arvind A/L Subramaniam', 'Student', 'PROFILE_UPDATE', 'Student updated their profile', '::1', '2026-02-20 22:27:25', '{\"contactNo\":\"012-2262226\"}'),
(547, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-20 22:28:39', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(548, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-20 22:28:45', '{\"userName\":\"Super Admin\"}'),
(549, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-20 22:28:56', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(550, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-23 19:48:35', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(551, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-23 20:04:22', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(552, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-23 20:04:27', '{\"userName\":\"Dr. Aini Musa\"}'),
(553, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-23 20:15:26', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(554, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-23 20:15:33', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(555, 'U013', 'Arvind A/L Subramaniam', 'Student', 'PROFILE_UPDATE', 'Student updated their profile', '::1', '2026-02-23 20:18:02', '{\"contactNo\":\"012-2262226\"}'),
(556, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-23 20:18:16', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(557, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-23 20:18:21', '{\"userName\":\"Dr. Wong Siew\"}'),
(558, 'U008', 'Dr. Wong Siew', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-23 20:19:04', '{\"userName\":\"Dr. Wong Siew\",\"role\":\"staff\"}'),
(559, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-24 22:06:51', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(560, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-24 22:07:43', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(561, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-24 22:08:18', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(562, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-24 22:08:21', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(563, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-24 22:11:14', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(564, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-24 22:11:21', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(565, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-24 22:11:28', '{\"userName\":\"Dr. Aini Musa\"}'),
(566, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-24 22:11:50', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(567, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-24 22:17:50', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(568, 'U014', 'Divya A/P Kumar', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-24 22:17:55', '{\"userName\":\"Divya A\\/P Kumar\"}'),
(569, 'U014', 'Divya A/P Kumar', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-24 22:17:58', '{\"userName\":\"Divya A\\/P Kumar\",\"role\":\"student\"}'),
(570, 'U015', 'Teoh Kah Mun', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-24 22:18:24', '{\"userName\":\"Teoh Kah Mun\"}'),
(571, 'U015', 'Teoh Kah Mun', 'Student', 'ATTENDANCE', 'Student took attendance for English for Tertiary Studies (BJEL1713) - Status: Late', '::1', '2026-02-24 22:18:45', '{\"scheduleID\":\"60\",\"courseID\":\"BJEL1713\",\"courseName\":\"English for Tertiary Studies\",\"date\":\"2026-02-24\",\"scanTime\":\"22:18:45\",\"status\":\"Late\"}'),
(572, 'U015', 'Teoh Kah Mun', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-24 22:18:51', '{\"userName\":\"Teoh Kah Mun\",\"role\":\"student\"}'),
(573, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-24 22:21:13', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(574, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-24 22:27:03', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(575, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-24 22:32:12', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(576, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-24 22:35:16', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(577, 'U015', 'Teoh Kah Mun', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-24 22:35:44', '{\"userName\":\"Teoh Kah Mun\"}'),
(578, 'U015', 'Teoh Kah Mun', 'Student', 'ATTENDANCE', 'Student took attendance for English for Tertiary Studies (BJEL1713) - Status: Late', '::1', '2026-02-24 22:36:05', '{\"scheduleID\":\"61\",\"courseID\":\"BJEL1713\",\"courseName\":\"English for Tertiary Studies\",\"date\":\"2026-02-24\",\"scanTime\":\"22:36:05\",\"status\":\"Late\"}'),
(579, 'U015', 'Teoh Kah Mun', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-24 22:38:54', '{\"userName\":\"Teoh Kah Mun\",\"role\":\"student\"}'),
(580, 'U059', 'Test Student Y2S2', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-24 22:39:28', '{\"userName\":\"Test Student Y2S2\"}'),
(581, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-24 22:39:50', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(582, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-24 22:39:55', '{\"userName\":\"Dr. Aini Musa\"}'),
(583, 'U059', 'Test Student Y2S2', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-24 22:46:11', '{\"userName\":\"Test Student Y2S2\",\"role\":\"student\"}'),
(584, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-24 22:46:16', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(585, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-24 22:47:23', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(586, 'U059', 'Test Student Y2S2', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-24 22:48:16', '{\"userName\":\"Test Student Y2S2\"}'),
(587, 'U059', 'Test Student Y2S2', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-24 22:54:40', '{\"userName\":\"Test Student Y2S2\",\"role\":\"student\"}'),
(588, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-24 22:54:45', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(589, 'U013', 'Arvind A/L Subramaniam', 'Student', 'ASSIGNMENT_SUBMIT', 'Student submitted assignment: Final for English for Tertiary Studies (BJEL1713)', '::1', '2026-02-24 22:57:03', '{\"assignmentID\":\"5\",\"title\":\"Final\",\"courseID\":\"BJEL1713\",\"fileName\":\"1771945023_23WP12509_1769883132_23WP12509_MOCK TEST.txt\",\"submissionStatus\":\"Submitted\"}'),
(590, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-24 22:58:11', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(591, 'U059', 'Test Student Y2S2', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-24 22:58:26', '{\"userName\":\"Test Student Y2S2\"}'),
(592, 'S001', 'Unknown', '', 'STUDENT_REMOVAL', 'Removed student Test Student Y2S2 (25WP00001) from RSD Group A. Reason: Try', '::1', '2026-02-24 22:59:13', NULL),
(593, 'S001', 'Unknown', '', 'STUDENT_REINSTATEMENT', 'Reinstated student Test Student Y2S2 (25WP00001) back to RSD Group A', '::1', '2026-02-24 22:59:26', NULL),
(594, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-24 22:59:32', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(595, 'U059', 'Test Student Y2S2', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-24 22:59:35', '{\"userName\":\"Test Student Y2S2\",\"role\":\"student\"}'),
(596, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-24 22:59:43', '{\"userName\":\"Super Admin\"}'),
(597, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-25 20:33:59', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(598, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-25 20:49:53', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(599, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-25 20:53:49', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(600, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-25 20:53:52', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(601, 'U032', 'Nurul Izzah Binti Kamaruddin', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-25 20:54:12', '{\"userName\":\"Nurul Izzah Binti Kamaruddin\"}'),
(602, 'U032', 'Nurul Izzah Binti Kamaruddin', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-25 20:54:20', '{\"userName\":\"Nurul Izzah Binti Kamaruddin\",\"role\":\"student\"}'),
(603, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-25 20:54:31', '{\"userName\":\"Dr. Aini Musa\"}'),
(604, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-25 20:57:27', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(605, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-25 20:57:40', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(606, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-25 20:58:12', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(607, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-25 20:58:20', '{\"userName\":\"Super Admin\"}'),
(608, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-25 21:02:03', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(609, 'U060', 'Wong Chun Kit', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-25 21:17:51', '{\"userName\":\"Wong Chun Kit\"}'),
(610, 'U060', 'Wong Chun Kit', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-25 21:18:14', '{\"userName\":\"Wong Chun Kit\",\"role\":\"student\"}'),
(611, 'U059', 'Test Student Y2S2', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-25 21:19:32', '{\"userName\":\"Test Student Y2S2\"}'),
(612, 'U059', 'Test Student Y2S2', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-25 21:20:09', '{\"userName\":\"Test Student Y2S2\",\"role\":\"student\"}'),
(613, 'U060', 'Wong Chun Kit', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-26 04:34:17', '{\"userName\":\"Wong Chun Kit\"}'),
(614, 'U060', 'Wong Chun Kit', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-26 04:34:19', '{\"userName\":\"Wong Chun Kit\",\"role\":\"student\"}'),
(615, 'U060', 'Wong Chun Kit', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-26 04:35:13', '{\"userName\":\"Wong Chun Kit\"}'),
(616, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-26 19:44:47', '{\"userName\":\"Super Admin\"}'),
(617, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-26 19:48:31', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(618, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-26 19:48:40', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(619, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-26 19:49:40', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(620, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-26 20:33:13', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(621, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-26 20:33:25', '{\"userName\":\"Dr. Aini Musa\"}'),
(622, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-26 20:35:00', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(623, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-26 20:35:07', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(624, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-26 20:57:11', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(625, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-26 20:57:21', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(626, 'U060', 'Wong Chun Kit', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-26 21:03:32', '{\"userName\":\"Wong Chun Kit\"}'),
(627, 'U060', 'Wong Chun Kit', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-26 21:03:34', '{\"userName\":\"Wong Chun Kit\",\"role\":\"student\"}'),
(628, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-26 21:36:48', '{\"userName\":\"Dr. Aini Musa\"}'),
(629, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-26 21:51:00', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(630, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-26 21:53:22', '{\"userName\":\"Super Admin\"}'),
(631, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-26 21:57:03', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(632, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-26 21:58:35', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(633, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-26 21:59:46', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(634, 'U060', 'Wong Chun Kit', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-26 22:03:15', '{\"userName\":\"Wong Chun Kit\"}'),
(635, 'U060', 'Wong Chun Kit', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-26 22:04:12', '{\"userName\":\"Wong Chun Kit\",\"role\":\"student\"}'),
(636, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-26 22:04:19', '{\"userName\":\"Dr. Aini Musa\"}'),
(637, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-26 23:03:56', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(638, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-27 21:06:06', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(639, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-27 21:07:05', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(640, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-27 21:07:13', '{\"userName\":\"Super Admin\"}'),
(641, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-27 22:10:08', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}');
INSERT INTO `activity_logs` (`logID`, `userID`, `userName`, `userRole`, `activity_type`, `activity_description`, `ip_address`, `timestamp`, `details`) VALUES
(642, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-27 22:10:15', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(643, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-27 22:11:25', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(644, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-27 22:11:32', '{\"userName\":\"Super Admin\"}'),
(645, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-27 22:11:39', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(646, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-27 22:11:46', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(647, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-27 22:17:33', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(648, 'U059', 'Test Student Y2S2', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-27 22:17:54', '{\"userName\":\"Test Student Y2S2\"}'),
(649, 'U059', 'Test Student Y2S2', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-27 22:31:52', '{\"userName\":\"Test Student Y2S2\",\"role\":\"student\"}'),
(650, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-27 22:31:58', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(651, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-27 22:32:40', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(652, 'U059', 'Test Student Y2S2', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-27 22:33:26', '{\"userName\":\"Test Student Y2S2\"}'),
(653, 'U059', 'Test Student Y2S2', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-27 22:40:28', '{\"userName\":\"Test Student Y2S2\",\"role\":\"student\"}'),
(654, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-27 22:40:35', '{\"userName\":\"Super Admin\"}'),
(655, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-27 22:44:34', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(656, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-27 22:46:16', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(657, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-27 22:46:42', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(658, '23WP12509', 'Unknown', '', 'PROFILE_UPDATE', 'Student removed their profile photo', '::1', '2026-02-27 22:47:55', NULL),
(659, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-27 22:48:36', '{\"userName\":\"Dr. Aini Musa\"}'),
(660, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-27 22:49:09', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(661, 'U013', 'Arvind A/L Subramaniam', 'Student', 'PROFILE_UPDATE', 'Student updated their profile', '::1', '2026-02-27 22:49:49', '{\"contactNo\":\"012-2262227\"}'),
(662, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGIN', 'Staff logged in successfully', '::1', '2026-02-27 22:50:07', '{\"userName\":\"Dr. Aini Musa\"}'),
(663, 'U013', 'Arvind A/L Subramaniam', 'Student', 'PROFILE_UPDATE', 'Student updated their profile', '::1', '2026-02-27 22:52:19', '{\"contactNo\":\"012-2262227\"}'),
(664, 'U002', 'Dr. Aini Musa', 'Staff', 'LOGOUT', 'User logged out', '::1', '2026-02-27 22:55:55', '{\"userName\":\"Dr. Aini Musa\",\"role\":\"staff\"}'),
(665, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-27 22:56:04', '{\"userName\":\"Super Admin\"}'),
(666, 'U001', 'Super Admin', 'Admin', 'LOGOUT', 'User logged out', '::1', '2026-02-27 23:00:01', '{\"userName\":\"Super Admin\",\"role\":\"admin\"}'),
(667, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGIN', 'Student logged in successfully', '::1', '2026-02-27 23:00:10', '{\"userName\":\"Arvind A\\/L Subramaniam\"}'),
(668, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-27 23:04:56', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}'),
(669, 'U001', 'Super Admin', 'Admin', 'LOGIN', 'Admin logged in successfully', '::1', '2026-02-27 23:05:03', '{\"userName\":\"Super Admin\"}'),
(670, 'U013', 'Arvind A/L Subramaniam', 'Student', 'LOGOUT', 'User logged out', '::1', '2026-02-27 23:15:20', '{\"userName\":\"Arvind A\\/L Subramaniam\",\"role\":\"student\"}');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `adminID` varchar(12) NOT NULL,
  `userID` varchar(12) DEFAULT NULL,
  `adminName` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contactNo` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`adminID`, `userID`, `adminName`, `email`, `contactNo`) VALUES
('ADM01', 'U001', 'Super Admin', 'admin@tarc.edu.my', '012-3456789'),
('ADM02', 'U058', 'john', 'ckwong@admin.tarc.edu.my', '012-222-3344');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `body`, `created_at`) VALUES
(1, 'Welcome back!', 'Classes resume next Monday. Please check your schedule.', '2025-11-10 05:15:47'),
(2, 'Maintenance window', 'Portal downtime tonight from 11 PM to 12 AM for updates.', '2025-11-10 05:15:47');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `assignmentID` int(11) NOT NULL,
  `courseID` varchar(10) NOT NULL,
  `staffID` varchar(10) NOT NULL,
  `tutGroup` varchar(50) NOT NULL DEFAULT 'All',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `attachmentPath` varchar(255) DEFAULT NULL,
  `deadline` datetime NOT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`assignmentID`, `courseID`, `staffID`, `tutGroup`, `title`, `description`, `attachmentPath`, `deadline`, `createdAt`) VALUES
(1, 'BJEL1713', 'S001', 'Group A', 'Lab Report 1', '1. Create a login page (login.php) that accepts a username and password.\r\n2. On success, start a Session and redirect to \'dashboard.php\'.\r\n3. Display \"Welcome, [Username]\" on the dashboard.\r\n4. Create a \'Logout\' button that destroys the session.', '1769883077_MOCK TEST.txt', '2026-02-02 12:00:00', '2026-01-31 18:11:17'),
(2, 'MPU3103', 'S007', 'Group A', 'Etika Portfolio', 'Dear students, this is your assignment so do it and submit.', '1769888869_MOCK TEST.txt', '2026-02-02 04:47:00', '2026-01-31 19:47:49'),
(3, 'BJEL1713', 'S001', 'Group A', 'English Article', 'Students pls do this work as part of your marks', '1770216608_1769883132_23WP12509_MOCK TEST.txt', '2026-02-05 10:00:00', '2026-02-04 14:50:08'),
(4, 'BAIT2113', 'S001', 'Group A', 'Web Assignment', 'Please submit your work', NULL, '2026-02-24 23:59:00', '2026-02-24 14:44:38'),
(5, 'BJEL1713', 'S001', 'Group A', 'Final', 'Students do your work.', '1771944323_BMCS3033_Tutorial2.pdf', '2026-02-25 23:59:00', '2026-02-24 14:45:23');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `recordID` int(11) NOT NULL,
  `studentID` varchar(12) NOT NULL,
  `scheduleID` int(11) NOT NULL,
  `attendanceDate` date NOT NULL,
  `scanTime` time NOT NULL,
  `status` enum('Present','Late','Absent') NOT NULL DEFAULT 'Absent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`recordID`, `studentID`, `scheduleID`, `attendanceDate`, `scanTime`, `status`) VALUES
(6, '23WP12509', 3, '2026-01-29', '08:10:53', 'Present'),
(7, '23WP14406', 3, '2026-01-29', '08:07:55', 'Present'),
(8, '23WP20606', 3, '2026-01-29', '08:10:20', 'Present'),
(9, '23WP24179', 3, '2026-01-29', '08:12:35', 'Present'),
(10, '23WP24747', 3, '2026-01-29', '08:07:59', 'Present'),
(11, '23WP34128', 3, '2026-01-29', '08:14:29', 'Present'),
(12, '23WP34773', 3, '2026-01-29', '08:24:37', 'Present'),
(13, '23WP51727', 3, '2026-01-29', '08:07:29', 'Present'),
(14, '23WP56143', 3, '2026-01-29', '08:20:43', 'Present'),
(15, '23WP69300', 3, '2026-01-29', '08:16:10', 'Present'),
(16, '23WP73033', 3, '2026-01-29', '08:13:50', 'Present'),
(17, '23WP77365', 3, '2026-01-29', '08:10:17', 'Present'),
(18, '23WP78708', 3, '2026-01-29', '08:00:08', 'Present'),
(19, '23WP80803', 3, '2026-01-29', '08:03:28', 'Present'),
(20, '23WP82178', 3, '2026-01-29', '08:04:42', 'Present'),
(21, '23WP84917', 3, '2026-01-29', '08:12:09', 'Present'),
(22, '23WP94665', 3, '2026-01-29', '08:05:47', 'Present'),
(23, '23WP99001', 3, '2026-01-29', '08:14:27', 'Present'),
(24, '23WP12509', 4, '2026-01-29', '10:10:00', 'Present'),
(25, '23WP14406', 4, '2026-01-29', '10:20:14', 'Present'),
(26, '23WP20606', 4, '2026-01-29', '10:29:01', 'Present'),
(27, '23WP24179', 4, '2026-01-29', '10:05:58', 'Present'),
(28, '23WP24747', 4, '2026-01-29', '10:23:40', 'Late'),
(29, '23WP27426', 4, '2026-01-29', '10:23:07', 'Present'),
(30, '23WP31454', 4, '2026-01-29', '10:10:12', 'Present'),
(31, '23WP34128', 4, '2026-01-29', '10:12:13', 'Present'),
(32, '23WP34773', 4, '2026-01-29', '10:11:56', 'Present'),
(33, '23WP47754', 4, '2026-01-29', '10:25:39', 'Present'),
(34, '23WP51727', 4, '2026-01-29', '10:19:40', 'Present'),
(35, '23WP56143', 4, '2026-01-29', '10:11:56', 'Present'),
(36, '23WP69300', 4, '2026-01-29', '10:04:08', 'Present'),
(37, '23WP73033', 4, '2026-01-29', '10:08:21', 'Present'),
(38, '23WP77365', 4, '2026-01-29', '10:16:40', 'Present'),
(39, '23WP78708', 4, '2026-01-29', '10:28:48', 'Present'),
(40, '23WP80803', 4, '2026-01-29', '10:24:06', 'Late'),
(41, '23WP82178', 4, '2026-01-29', '10:10:39', 'Present'),
(42, '23WP84917', 4, '2026-01-29', '10:21:42', 'Present'),
(43, '23WP94665', 4, '2026-01-29', '10:08:21', 'Present'),
(44, '23WP99001', 4, '2026-01-29', '10:23:21', 'Present'),
(45, '23WP12926', 21, '2026-01-26', '08:23:01', 'Present'),
(46, '23WP20441', 21, '2026-01-26', '08:11:32', 'Present'),
(47, '23WP21572', 21, '2026-01-26', '08:02:01', 'Present'),
(48, '23WP21706', 21, '2026-01-26', '08:00:46', 'Present'),
(49, '23WP27671', 21, '2026-01-26', '08:07:13', 'Present'),
(50, '23WP37638', 21, '2026-01-26', '08:02:57', 'Present'),
(51, '23WP37849', 21, '2026-01-26', '08:02:48', 'Present'),
(52, '23WP38477', 21, '2026-01-26', '08:24:18', 'Present'),
(53, '23WP40593', 21, '2026-01-26', '08:09:16', 'Present'),
(54, '23WP57187', 21, '2026-01-26', '08:25:26', 'Present'),
(55, '23WP57467', 21, '2026-01-26', '08:15:00', 'Present'),
(56, '23WP61618', 21, '2026-01-26', '08:03:28', 'Present'),
(57, '23WP71365', 21, '2026-01-26', '08:27:37', 'Present'),
(58, '23WP73789', 21, '2026-01-26', '08:24:24', 'Present'),
(59, '23WP79311', 21, '2026-01-26', '08:06:40', 'Present'),
(60, '23WP79793', 21, '2026-01-26', '08:25:36', 'Present'),
(61, '23WP82670', 21, '2026-01-26', '08:08:15', 'Present'),
(62, '23WP96546', 21, '2026-01-26', '08:29:15', 'Present'),
(63, '23WP12926', 22, '2026-01-26', '10:17:16', 'Present'),
(64, '23WP20441', 22, '2026-01-26', '10:21:36', 'Present'),
(65, '23WP21572', 22, '2026-01-26', '10:02:39', 'Present'),
(66, '23WP21706', 22, '2026-01-26', '10:19:37', 'Present'),
(67, '23WP37638', 22, '2026-01-26', '10:03:32', 'Present'),
(68, '23WP37849', 22, '2026-01-26', '10:26:31', 'Present'),
(69, '23WP38477', 22, '2026-01-26', '10:16:20', 'Late'),
(70, '23WP40593', 22, '2026-01-26', '10:21:40', 'Present'),
(71, '23WP57187', 22, '2026-01-26', '10:03:15', 'Present'),
(72, '23WP61618', 22, '2026-01-26', '10:05:22', 'Present'),
(73, '23WP61839', 22, '2026-01-26', '10:09:14', 'Present'),
(74, '23WP71365', 22, '2026-01-26', '10:24:25', 'Present'),
(75, '23WP73789', 22, '2026-01-26', '10:23:49', 'Present'),
(76, '23WP79311', 22, '2026-01-26', '10:16:56', 'Present'),
(77, '23WP79793', 22, '2026-01-26', '10:22:13', 'Present'),
(78, '23WP82670', 22, '2026-01-26', '10:00:21', 'Present'),
(79, '23WP96546', 22, '2026-01-26', '10:28:15', 'Present'),
(80, '23WP12926', 23, '2026-01-27', '08:00:34', 'Present'),
(81, '23WP20441', 23, '2026-01-27', '08:03:14', 'Present'),
(82, '23WP21572', 23, '2026-01-27', '08:03:52', 'Present'),
(83, '23WP21706', 23, '2026-01-27', '08:12:37', 'Present'),
(84, '23WP27671', 23, '2026-01-27', '08:05:47', 'Present'),
(85, '23WP32466', 23, '2026-01-27', '08:01:49', 'Present'),
(86, '23WP37638', 23, '2026-01-27', '08:19:48', 'Present'),
(87, '23WP37849', 23, '2026-01-27', '08:05:27', 'Present'),
(88, '23WP38477', 23, '2026-01-27', '08:21:30', 'Present'),
(89, '23WP57187', 23, '2026-01-27', '08:12:46', 'Present'),
(90, '23WP57467', 23, '2026-01-27', '08:22:36', 'Present'),
(91, '23WP61618', 23, '2026-01-27', '08:23:31', 'Late'),
(92, '23WP61839', 23, '2026-01-27', '08:11:25', 'Present'),
(93, '23WP71365', 23, '2026-01-27', '08:09:03', 'Present'),
(94, '23WP79311', 23, '2026-01-27', '08:15:03', 'Present'),
(95, '23WP82670', 23, '2026-01-27', '08:20:53', 'Present'),
(96, '23WP96546', 23, '2026-01-27', '08:26:17', 'Present'),
(97, '23WP12926', 24, '2026-01-27', '10:08:45', 'Present'),
(98, '23WP20441', 24, '2026-01-27', '10:21:16', 'Present'),
(99, '23WP21572', 24, '2026-01-27', '10:25:06', 'Present'),
(100, '23WP21706', 24, '2026-01-27', '10:24:10', 'Present'),
(101, '23WP27671', 24, '2026-01-27', '10:26:19', 'Present'),
(102, '23WP32466', 24, '2026-01-27', '10:12:53', 'Present'),
(103, '23WP37638', 24, '2026-01-27', '10:20:35', 'Present'),
(104, '23WP37849', 24, '2026-01-27', '10:09:00', 'Present'),
(105, '23WP38477', 24, '2026-01-27', '10:15:14', 'Present'),
(106, '23WP40593', 24, '2026-01-27', '10:15:40', 'Present'),
(107, '23WP57187', 24, '2026-01-27', '10:01:55', 'Present'),
(108, '23WP57467', 24, '2026-01-27', '10:01:35', 'Present'),
(109, '23WP61618', 24, '2026-01-27', '10:15:25', 'Present'),
(110, '23WP61839', 24, '2026-01-27', '10:20:54', 'Present'),
(111, '23WP71365', 24, '2026-01-27', '10:26:03', 'Present'),
(112, '23WP73789', 24, '2026-01-27', '10:19:37', 'Present'),
(113, '23WP79311', 24, '2026-01-27', '10:26:30', 'Present'),
(114, '23WP79793', 24, '2026-01-27', '10:00:11', 'Present'),
(115, '23WP82670', 24, '2026-01-27', '10:20:24', 'Present'),
(116, '23WP96546', 24, '2026-01-27', '10:07:41', 'Present'),
(117, '23WP12926', 25, '2026-01-28', '08:04:03', 'Present'),
(118, '23WP20441', 25, '2026-01-28', '08:26:42', 'Present'),
(119, '23WP21572', 25, '2026-01-28', '08:16:57', 'Late'),
(120, '23WP21706', 25, '2026-01-28', '08:05:18', 'Present'),
(121, '23WP27671', 25, '2026-01-28', '08:26:47', 'Present'),
(122, '23WP32466', 25, '2026-01-28', '08:19:50', 'Present'),
(123, '23WP37849', 25, '2026-01-28', '08:06:17', 'Present'),
(124, '23WP38477', 25, '2026-01-28', '08:05:02', 'Present'),
(125, '23WP57187', 25, '2026-01-28', '08:02:10', 'Present'),
(126, '23WP57467', 25, '2026-01-28', '08:02:12', 'Present'),
(127, '23WP71365', 25, '2026-01-28', '08:15:52', 'Late'),
(128, '23WP73789', 25, '2026-01-28', '08:13:11', 'Present'),
(129, '23WP79311', 25, '2026-01-28', '08:26:36', 'Late'),
(130, '23WP79793', 25, '2026-01-28', '08:02:25', 'Present'),
(131, '23WP96546', 25, '2026-01-28', '08:20:49', 'Present'),
(132, '23WP12926', 26, '2026-01-28', '10:18:06', 'Present'),
(133, '23WP20441', 26, '2026-01-28', '10:16:14', 'Present'),
(134, '23WP21572', 26, '2026-01-28', '10:29:58', 'Present'),
(135, '23WP21706', 26, '2026-01-28', '10:09:00', 'Present'),
(136, '23WP27671', 26, '2026-01-28', '10:00:58', 'Present'),
(137, '23WP32466', 26, '2026-01-28', '10:14:32', 'Present'),
(138, '23WP37638', 26, '2026-01-28', '10:27:19', 'Present'),
(139, '23WP37849', 26, '2026-01-28', '10:23:05', 'Present'),
(140, '23WP38477', 26, '2026-01-28', '10:00:05', 'Present'),
(141, '23WP40593', 26, '2026-01-28', '10:13:35', 'Present'),
(142, '23WP57187', 26, '2026-01-28', '10:05:16', 'Present'),
(143, '23WP57467', 26, '2026-01-28', '10:25:18', 'Present'),
(144, '23WP61618', 26, '2026-01-28', '10:22:38', 'Present'),
(145, '23WP61839', 26, '2026-01-28', '10:18:00', 'Present'),
(146, '23WP71365', 26, '2026-01-28', '10:19:35', 'Late'),
(147, '23WP73789', 26, '2026-01-28', '10:01:30', 'Present'),
(148, '23WP79311', 26, '2026-01-28', '10:10:53', 'Present'),
(149, '23WP82670', 26, '2026-01-28', '10:17:00', 'Present'),
(150, '23WP96546', 26, '2026-01-28', '10:06:20', 'Present'),
(151, '23WP12509', 27, '2026-01-30', '08:17:54', 'Present'),
(152, '23WP14406', 27, '2026-01-30', '08:00:52', 'Present'),
(153, '23WP20606', 27, '2026-01-30', '08:06:15', 'Present'),
(154, '23WP24179', 27, '2026-01-30', '08:27:23', 'Present'),
(155, '23WP24747', 27, '2026-01-30', '08:12:41', 'Present'),
(156, '23WP27426', 27, '2026-01-30', '08:06:09', 'Present'),
(157, '23WP31454', 27, '2026-01-30', '08:00:20', 'Present'),
(158, '23WP34773', 27, '2026-01-30', '08:27:01', 'Present'),
(159, '23WP47754', 27, '2026-01-30', '08:16:59', 'Late'),
(160, '23WP51727', 27, '2026-01-30', '08:29:13', 'Present'),
(161, '23WP69300', 27, '2026-01-30', '08:16:13', 'Present'),
(162, '23WP73033', 27, '2026-01-30', '08:04:20', 'Present'),
(163, '23WP77365', 27, '2026-01-30', '08:29:08', 'Present'),
(164, '23WP78708', 27, '2026-01-30', '08:23:34', 'Present'),
(165, '23WP80803', 27, '2026-01-30', '08:18:45', 'Present'),
(166, '23WP82178', 27, '2026-01-30', '08:24:04', 'Late'),
(167, '23WP84917', 27, '2026-01-30', '08:10:03', 'Present'),
(168, '23WP94665', 27, '2026-01-30', '08:04:32', 'Present'),
(169, '23WP99001', 27, '2026-01-30', '08:01:51', 'Present'),
(170, '23WP12509', 28, '2026-01-30', '10:26:25', 'Present'),
(171, '23WP20606', 28, '2026-01-30', '10:27:52', 'Present'),
(172, '23WP24179', 28, '2026-01-30', '10:21:39', 'Present'),
(173, '23WP27426', 28, '2026-01-30', '10:11:07', 'Present'),
(174, '23WP31454', 28, '2026-01-30', '10:29:44', 'Present'),
(175, '23WP34128', 28, '2026-01-30', '10:09:42', 'Present'),
(176, '23WP34773', 28, '2026-01-30', '10:02:51', 'Present'),
(177, '23WP47754', 28, '2026-01-30', '10:26:48', 'Present'),
(178, '23WP51727', 28, '2026-01-30', '10:27:35', 'Present'),
(179, '23WP56143', 28, '2026-01-30', '10:12:52', 'Present'),
(180, '23WP69300', 28, '2026-01-30', '10:15:06', 'Present'),
(181, '23WP73033', 28, '2026-01-30', '10:21:08', 'Present'),
(182, '23WP77365', 28, '2026-01-30', '10:23:19', 'Present'),
(183, '23WP78708', 28, '2026-01-30', '10:05:30', 'Present'),
(184, '23WP80803', 28, '2026-01-30', '10:21:13', 'Present'),
(185, '23WP82178', 28, '2026-01-30', '10:18:55', 'Late'),
(186, '23WP84917', 28, '2026-01-30', '10:11:09', 'Present'),
(187, '23WP94665', 28, '2026-01-30', '10:21:37', 'Present'),
(188, '23WP99001', 28, '2026-01-30', '10:18:37', 'Present'),
(189, '23WP12509', 29, '2026-01-26', '14:05:32', 'Present'),
(190, '23WP14406', 29, '2026-01-26', '14:00:04', 'Present'),
(191, '23WP20606', 29, '2026-01-26', '14:06:06', 'Present'),
(192, '23WP24179', 29, '2026-01-26', '14:06:30', 'Present'),
(193, '23WP24747', 29, '2026-01-26', '14:25:24', 'Present'),
(194, '23WP27426', 29, '2026-01-26', '14:27:20', 'Present'),
(195, '23WP31454', 29, '2026-01-26', '14:00:52', 'Present'),
(196, '23WP34128', 29, '2026-01-26', '14:21:51', 'Present'),
(197, '23WP34773', 29, '2026-01-26', '14:15:21', 'Present'),
(198, '23WP47754', 29, '2026-01-26', '14:08:47', 'Present'),
(199, '23WP51727', 29, '2026-01-26', '14:18:43', 'Present'),
(200, '23WP56143', 29, '2026-01-26', '14:00:33', 'Present'),
(201, '23WP69300', 29, '2026-01-26', '14:19:40', 'Late'),
(202, '23WP73033', 29, '2026-01-26', '14:28:58', 'Present'),
(203, '23WP77365', 29, '2026-01-26', '14:16:12', 'Present'),
(204, '23WP78708', 29, '2026-01-26', '14:03:58', 'Present'),
(205, '23WP80803', 29, '2026-01-26', '14:00:53', 'Present'),
(206, '23WP82178', 29, '2026-01-26', '14:23:38', 'Present'),
(207, '23WP84917', 29, '2026-01-26', '14:07:08', 'Present'),
(208, '23WP94665', 29, '2026-01-26', '14:23:26', 'Present'),
(209, '23WP99001', 29, '2026-01-26', '14:02:41', 'Present'),
(210, '23WP12509', 30, '2026-01-26', '16:02:54', 'Present'),
(211, '23WP14406', 30, '2026-01-26', '16:09:26', 'Present'),
(212, '23WP20606', 30, '2026-01-26', '16:16:35', 'Present'),
(213, '23WP24179', 30, '2026-01-26', '16:22:21', 'Present'),
(214, '23WP24747', 30, '2026-01-26', '16:21:15', 'Present'),
(215, '23WP27426', 30, '2026-01-26', '16:16:33', 'Present'),
(216, '23WP31454', 30, '2026-01-26', '16:00:27', 'Present'),
(217, '23WP34128', 30, '2026-01-26', '16:09:18', 'Present'),
(218, '23WP34773', 30, '2026-01-26', '16:12:44', 'Present'),
(219, '23WP47754', 30, '2026-01-26', '16:18:10', 'Present'),
(220, '23WP51727', 30, '2026-01-26', '16:29:24', 'Present'),
(221, '23WP56143', 30, '2026-01-26', '16:02:09', 'Present'),
(222, '23WP69300', 30, '2026-01-26', '16:08:11', 'Present'),
(223, '23WP73033', 30, '2026-01-26', '16:27:31', 'Present'),
(224, '23WP77365', 30, '2026-01-26', '16:01:07', 'Present'),
(225, '23WP78708', 30, '2026-01-26', '16:06:47', 'Present'),
(226, '23WP80803', 30, '2026-01-26', '16:03:06', 'Present'),
(227, '23WP82178', 30, '2026-01-26', '16:05:20', 'Present'),
(228, '23WP84917', 30, '2026-01-26', '16:12:43', 'Present'),
(229, '23WP94665', 30, '2026-01-26', '16:03:36', 'Present'),
(230, '23WP99001', 30, '2026-01-26', '16:04:33', 'Present'),
(231, '23WP12509', 35, '2026-01-27', '08:27:31', 'Present'),
(232, '23WP14406', 35, '2026-01-27', '08:09:32', 'Present'),
(233, '23WP24179', 35, '2026-01-27', '08:16:07', 'Present'),
(234, '23WP27426', 35, '2026-01-27', '08:19:31', 'Present'),
(235, '23WP31454', 35, '2026-01-27', '08:20:41', 'Present'),
(236, '23WP34128', 35, '2026-01-27', '08:18:18', 'Present'),
(237, '23WP34773', 35, '2026-01-27', '08:06:02', 'Present'),
(238, '23WP47754', 35, '2026-01-27', '08:19:11', 'Present'),
(239, '23WP51727', 35, '2026-01-27', '08:22:28', 'Present'),
(240, '23WP56143', 35, '2026-01-27', '08:19:03', 'Present'),
(241, '23WP73033', 35, '2026-01-27', '08:02:27', 'Present'),
(242, '23WP77365', 35, '2026-01-27', '08:15:31', 'Present'),
(243, '23WP78708', 35, '2026-01-27', '08:05:43', 'Present'),
(244, '23WP82178', 35, '2026-01-27', '08:23:09', 'Present'),
(245, '23WP84917', 35, '2026-01-27', '08:02:27', 'Present'),
(246, '23WP94665', 35, '2026-01-27', '08:21:05', 'Present'),
(247, '23WP12509', 36, '2026-01-27', '10:11:55', 'Present'),
(248, '23WP14406', 36, '2026-01-27', '10:05:03', 'Present'),
(249, '23WP20606', 36, '2026-01-27', '10:22:51', 'Present'),
(250, '23WP24179', 36, '2026-01-27', '10:11:47', 'Present'),
(251, '23WP24747', 36, '2026-01-27', '10:25:54', 'Present'),
(252, '23WP31454', 36, '2026-01-27', '10:17:28', 'Present'),
(253, '23WP34128', 36, '2026-01-27', '10:18:19', 'Present'),
(254, '23WP34773', 36, '2026-01-27', '10:04:23', 'Present'),
(255, '23WP47754', 36, '2026-01-27', '10:06:35', 'Present'),
(256, '23WP51727', 36, '2026-01-27', '10:19:17', 'Late'),
(257, '23WP56143', 36, '2026-01-27', '10:14:49', 'Present'),
(258, '23WP69300', 36, '2026-01-27', '10:05:36', 'Present'),
(259, '23WP73033', 36, '2026-01-27', '10:17:56', 'Present'),
(260, '23WP77365', 36, '2026-01-27', '10:23:51', 'Present'),
(261, '23WP78708', 36, '2026-01-27', '10:14:02', 'Present'),
(262, '23WP80803', 36, '2026-01-27', '10:29:48', 'Present'),
(263, '23WP82178', 36, '2026-01-27', '10:25:07', 'Present'),
(264, '23WP84917', 36, '2026-01-27', '10:05:03', 'Present'),
(265, '23WP94665', 36, '2026-01-27', '10:26:33', 'Present'),
(266, '23WP99001', 36, '2026-01-27', '10:07:21', 'Present'),
(267, '23WP12509', 37, '2026-01-28', '08:22:03', 'Present'),
(268, '23WP20606', 37, '2026-01-28', '08:29:24', 'Present'),
(269, '23WP24179', 37, '2026-01-28', '08:01:29', 'Present'),
(270, '23WP24747', 37, '2026-01-28', '08:14:40', 'Present'),
(271, '23WP27426', 37, '2026-01-28', '08:23:29', 'Present'),
(272, '23WP31454', 37, '2026-01-28', '08:12:58', 'Present'),
(273, '23WP34128', 37, '2026-01-28', '08:14:11', 'Present'),
(274, '23WP34773', 37, '2026-01-28', '08:04:38', 'Present'),
(275, '23WP47754', 37, '2026-01-28', '08:18:35', 'Present'),
(276, '23WP51727', 37, '2026-01-28', '08:12:17', 'Present'),
(277, '23WP56143', 37, '2026-01-28', '08:01:36', 'Present'),
(278, '23WP69300', 37, '2026-01-28', '08:07:12', 'Present'),
(279, '23WP77365', 37, '2026-01-28', '08:26:49', 'Present'),
(280, '23WP78708', 37, '2026-01-28', '08:16:00', 'Present'),
(281, '23WP80803', 37, '2026-01-28', '08:16:38', 'Present'),
(282, '23WP82178', 37, '2026-01-28', '08:28:58', 'Present'),
(283, '23WP84917', 37, '2026-01-28', '08:08:58', 'Present'),
(284, '23WP94665', 37, '2026-01-28', '08:26:08', 'Present'),
(285, '23WP99001', 37, '2026-01-28', '08:18:57', 'Late'),
(286, '23WP12509', 38, '2026-01-28', '10:00:30', 'Present'),
(287, '23WP14406', 38, '2026-01-28', '10:08:46', 'Present'),
(288, '23WP20606', 38, '2026-01-28', '10:28:54', 'Present'),
(289, '23WP24179', 38, '2026-01-28', '10:05:45', 'Present'),
(290, '23WP24747', 38, '2026-01-28', '10:10:08', 'Present'),
(291, '23WP27426', 38, '2026-01-28', '10:26:27', 'Present'),
(292, '23WP31454', 38, '2026-01-28', '10:13:07', 'Present'),
(293, '23WP34128', 38, '2026-01-28', '10:06:04', 'Present'),
(294, '23WP34773', 38, '2026-01-28', '10:10:55', 'Present'),
(295, '23WP47754', 38, '2026-01-28', '10:20:14', 'Present'),
(296, '23WP51727', 38, '2026-01-28', '10:03:43', 'Present'),
(297, '23WP56143', 38, '2026-01-28', '10:21:44', 'Present'),
(298, '23WP69300', 38, '2026-01-28', '10:18:07', 'Present'),
(299, '23WP73033', 38, '2026-01-28', '10:23:03', 'Present'),
(300, '23WP77365', 38, '2026-01-28', '10:05:41', 'Present'),
(301, '23WP78708', 38, '2026-01-28', '10:12:56', 'Present'),
(302, '23WP80803', 38, '2026-01-28', '10:11:45', 'Present'),
(303, '23WP82178', 38, '2026-01-28', '10:21:38', 'Present'),
(304, '23WP84917', 38, '2026-01-28', '10:03:56', 'Present'),
(305, '23WP94665', 38, '2026-01-28', '10:01:15', 'Present'),
(306, '23WP12509', 39, '2026-01-27', '14:09:29', 'Present'),
(307, '23WP14406', 39, '2026-01-27', '14:23:56', 'Present'),
(308, '23WP20606', 39, '2026-01-27', '14:09:30', 'Present'),
(309, '23WP24179', 39, '2026-01-27', '14:23:53', 'Present'),
(310, '23WP24747', 39, '2026-01-27', '14:04:21', 'Present'),
(311, '23WP27426', 39, '2026-01-27', '14:03:46', 'Present'),
(312, '23WP31454', 39, '2026-01-27', '14:04:54', 'Present'),
(313, '23WP34128', 39, '2026-01-27', '14:20:17', 'Present'),
(314, '23WP34773', 39, '2026-01-27', '14:20:19', 'Present'),
(315, '23WP47754', 39, '2026-01-27', '14:28:44', 'Present'),
(316, '23WP51727', 39, '2026-01-27', '14:10:37', 'Present'),
(317, '23WP56143', 39, '2026-01-27', '14:13:43', 'Present'),
(318, '23WP69300', 39, '2026-01-27', '14:01:50', 'Present'),
(319, '23WP73033', 39, '2026-01-27', '14:16:05', 'Present'),
(320, '23WP77365', 39, '2026-01-27', '14:08:08', 'Present'),
(321, '23WP78708', 39, '2026-01-27', '14:07:11', 'Present'),
(322, '23WP80803', 39, '2026-01-27', '14:25:14', 'Present'),
(323, '23WP82178', 39, '2026-01-27', '14:25:21', 'Present'),
(324, '23WP99001', 39, '2026-01-27', '14:21:25', 'Present'),
(325, '23WP12509', 40, '2026-01-27', '16:03:15', 'Present'),
(326, '23WP14406', 40, '2026-01-27', '16:12:32', 'Present'),
(327, '23WP20606', 40, '2026-01-27', '16:14:42', 'Present'),
(328, '23WP24179', 40, '2026-01-27', '16:28:28', 'Present'),
(329, '23WP24747', 40, '2026-01-27', '16:20:36', 'Present'),
(330, '23WP27426', 40, '2026-01-27', '16:09:43', 'Present'),
(331, '23WP31454', 40, '2026-01-27', '16:11:49', 'Present'),
(332, '23WP34128', 40, '2026-01-27', '16:22:56', 'Present'),
(333, '23WP34773', 40, '2026-01-27', '16:16:05', 'Present'),
(334, '23WP47754', 40, '2026-01-27', '16:08:16', 'Present'),
(335, '23WP51727', 40, '2026-01-27', '16:16:56', 'Present'),
(336, '23WP56143', 40, '2026-01-27', '16:21:29', 'Present'),
(337, '23WP69300', 40, '2026-01-27', '16:02:10', 'Present'),
(338, '23WP73033', 40, '2026-01-27', '16:14:08', 'Present'),
(339, '23WP77365', 40, '2026-01-27', '16:22:59', 'Present'),
(340, '23WP78708', 40, '2026-01-27', '16:17:23', 'Present'),
(341, '23WP80803', 40, '2026-01-27', '16:20:58', 'Present'),
(342, '23WP82178', 40, '2026-01-27', '16:08:04', 'Present'),
(343, '23WP94665', 40, '2026-01-27', '16:12:44', 'Present'),
(344, '23WP99001', 40, '2026-01-27', '16:13:52', 'Present'),
(345, '23WP12509', 41, '2026-01-29', '14:16:18', 'Present'),
(346, '23WP14406', 41, '2026-01-29', '14:00:18', 'Present'),
(347, '23WP20606', 41, '2026-01-29', '14:04:16', 'Present'),
(348, '23WP24747', 41, '2026-01-29', '14:18:07', 'Present'),
(349, '23WP27426', 41, '2026-01-29', '14:19:58', 'Present'),
(350, '23WP31454', 41, '2026-01-29', '14:28:45', 'Present'),
(351, '23WP34128', 41, '2026-01-29', '14:20:42', 'Present'),
(352, '23WP47754', 41, '2026-01-29', '14:27:46', 'Present'),
(353, '23WP51727', 41, '2026-01-29', '14:12:37', 'Present'),
(354, '23WP56143', 41, '2026-01-29', '14:20:29', 'Present'),
(355, '23WP69300', 41, '2026-01-29', '14:08:57', 'Present'),
(356, '23WP73033', 41, '2026-01-29', '14:12:42', 'Present'),
(357, '23WP77365', 41, '2026-01-29', '14:25:00', 'Present'),
(358, '23WP78708', 41, '2026-01-29', '14:08:03', 'Present'),
(359, '23WP80803', 41, '2026-01-29', '14:28:55', 'Present'),
(360, '23WP82178', 41, '2026-01-29', '14:26:50', 'Present'),
(361, '23WP84917', 41, '2026-01-29', '14:16:12', 'Present'),
(362, '23WP94665', 41, '2026-01-29', '14:01:25', 'Present'),
(363, '23WP12509', 42, '2026-01-29', '16:03:18', 'Present'),
(364, '23WP14406', 42, '2026-01-29', '16:23:37', 'Present'),
(365, '23WP20606', 42, '2026-01-29', '16:00:27', 'Present'),
(366, '23WP24179', 42, '2026-01-29', '16:29:24', 'Present'),
(367, '23WP24747', 42, '2026-01-29', '16:00:04', 'Present'),
(368, '23WP27426', 42, '2026-01-29', '16:21:20', 'Present'),
(369, '23WP31454', 42, '2026-01-29', '16:25:44', 'Present'),
(370, '23WP34128', 42, '2026-01-29', '16:13:22', 'Present'),
(371, '23WP34773', 42, '2026-01-29', '16:15:11', 'Present'),
(372, '23WP47754', 42, '2026-01-29', '16:14:43', 'Present'),
(373, '23WP51727', 42, '2026-01-29', '16:17:03', 'Present'),
(374, '23WP56143', 42, '2026-01-29', '16:06:45', 'Present'),
(375, '23WP69300', 42, '2026-01-29', '16:20:27', 'Present'),
(376, '23WP73033', 42, '2026-01-29', '16:14:22', 'Present'),
(377, '23WP77365', 42, '2026-01-29', '16:27:04', 'Present'),
(378, '23WP78708', 42, '2026-01-29', '16:08:00', 'Present'),
(379, '23WP80803', 42, '2026-01-29', '16:29:24', 'Present'),
(380, '23WP82178', 42, '2026-01-29', '16:06:12', 'Present'),
(381, '23WP84917', 42, '2026-01-29', '16:02:51', 'Present'),
(382, '23WP94665', 42, '2026-01-29', '16:00:26', 'Present'),
(383, '23WP99001', 42, '2026-01-29', '16:18:39', 'Present'),
(384, '23WP12509', 43, '2026-01-28', '14:10:56', 'Present'),
(385, '23WP14406', 43, '2026-01-28', '14:12:04', 'Present'),
(386, '23WP20606', 43, '2026-01-28', '14:11:00', 'Present'),
(387, '23WP24179', 43, '2026-01-28', '14:14:19', 'Present'),
(388, '23WP24747', 43, '2026-01-28', '14:09:14', 'Present'),
(389, '23WP27426', 43, '2026-01-28', '14:21:46', 'Present'),
(390, '23WP31454', 43, '2026-01-28', '14:22:41', 'Present'),
(391, '23WP34128', 43, '2026-01-28', '14:27:06', 'Present'),
(392, '23WP34773', 43, '2026-01-28', '14:26:15', 'Present'),
(393, '23WP47754', 43, '2026-01-28', '14:19:10', 'Present'),
(394, '23WP51727', 43, '2026-01-28', '14:14:28', 'Present'),
(395, '23WP56143', 43, '2026-01-28', '14:10:18', 'Present'),
(396, '23WP69300', 43, '2026-01-28', '14:13:54', 'Present'),
(397, '23WP73033', 43, '2026-01-28', '14:23:54', 'Present'),
(398, '23WP77365', 43, '2026-01-28', '14:07:25', 'Present'),
(399, '23WP78708', 43, '2026-01-28', '14:07:58', 'Present'),
(400, '23WP80803', 43, '2026-01-28', '14:17:13', 'Present'),
(401, '23WP82178', 43, '2026-01-28', '14:23:28', 'Present'),
(402, '23WP84917', 43, '2026-01-28', '14:03:10', 'Present'),
(403, '23WP94665', 43, '2026-01-28', '14:10:38', 'Present'),
(404, '23WP12509', 44, '2026-01-28', '16:19:47', 'Present'),
(405, '23WP14406', 44, '2026-01-28', '16:29:00', 'Present'),
(406, '23WP20606', 44, '2026-01-28', '16:16:04', 'Present'),
(407, '23WP24179', 44, '2026-01-28', '16:22:36', 'Present'),
(408, '23WP27426', 44, '2026-01-28', '16:12:49', 'Present'),
(409, '23WP34128', 44, '2026-01-28', '16:17:13', 'Present'),
(410, '23WP34773', 44, '2026-01-28', '16:11:23', 'Present'),
(411, '23WP47754', 44, '2026-01-28', '16:26:14', 'Present'),
(412, '23WP51727', 44, '2026-01-28', '16:21:33', 'Late'),
(413, '23WP69300', 44, '2026-01-28', '16:12:35', 'Present'),
(414, '23WP73033', 44, '2026-01-28', '16:22:55', 'Present'),
(415, '23WP77365', 44, '2026-01-28', '16:23:57', 'Present'),
(416, '23WP78708', 44, '2026-01-28', '16:07:40', 'Present'),
(417, '23WP80803', 44, '2026-01-28', '16:27:21', 'Present'),
(418, '23WP82178', 44, '2026-01-28', '16:01:25', 'Present'),
(419, '23WP84917', 44, '2026-01-28', '16:05:32', 'Present'),
(420, '23WP94665', 44, '2026-01-28', '16:15:02', 'Present'),
(421, '23WP99001', 44, '2026-01-28', '16:03:59', 'Present'),
(422, '23WP12509', 45, '2026-01-30', '14:03:22', 'Present'),
(423, '23WP14406', 45, '2026-01-30', '14:11:31', 'Present'),
(424, '23WP20606', 45, '2026-01-30', '14:21:11', 'Present'),
(425, '23WP24179', 45, '2026-01-30', '14:04:44', 'Present'),
(426, '23WP27426', 45, '2026-01-30', '14:00:32', 'Present'),
(427, '23WP31454', 45, '2026-01-30', '14:03:20', 'Present'),
(428, '23WP34128', 45, '2026-01-30', '14:13:17', 'Present'),
(429, '23WP34773', 45, '2026-01-30', '14:12:28', 'Present'),
(430, '23WP47754', 45, '2026-01-30', '14:08:51', 'Present'),
(431, '23WP51727', 45, '2026-01-30', '14:11:28', 'Present'),
(432, '23WP56143', 45, '2026-01-30', '14:19:10', 'Present'),
(433, '23WP69300', 45, '2026-01-30', '14:24:12', 'Present'),
(434, '23WP73033', 45, '2026-01-30', '14:08:55', 'Present'),
(435, '23WP77365', 45, '2026-01-30', '14:00:57', 'Present'),
(436, '23WP78708', 45, '2026-01-30', '14:15:12', 'Present'),
(437, '23WP80803', 45, '2026-01-30', '14:20:28', 'Present'),
(438, '23WP82178', 45, '2026-01-30', '14:27:44', 'Present'),
(439, '23WP84917', 45, '2026-01-30', '14:16:52', 'Present'),
(440, '23WP94665', 45, '2026-01-30', '14:00:43', 'Present'),
(441, '23WP12509', 46, '2026-01-30', '16:24:19', 'Present'),
(442, '23WP14406', 46, '2026-01-30', '16:22:45', 'Present'),
(443, '23WP20606', 46, '2026-01-30', '16:23:42', 'Present'),
(444, '23WP24179', 46, '2026-01-30', '16:21:51', 'Present'),
(445, '23WP24747', 46, '2026-01-30', '16:28:58', 'Present'),
(446, '23WP27426', 46, '2026-01-30', '16:17:10', 'Present'),
(447, '23WP34128', 46, '2026-01-30', '16:26:10', 'Present'),
(448, '23WP34773', 46, '2026-01-30', '16:23:52', 'Present'),
(449, '23WP51727', 46, '2026-01-30', '16:23:00', 'Present'),
(450, '23WP56143', 46, '2026-01-30', '16:29:13', 'Present'),
(451, '23WP69300', 46, '2026-01-30', '16:06:02', 'Present'),
(452, '23WP73033', 46, '2026-01-30', '16:25:06', 'Present'),
(453, '23WP77365', 46, '2026-01-30', '16:15:27', 'Present'),
(454, '23WP78708', 46, '2026-01-30', '16:18:49', 'Late'),
(455, '23WP80803', 46, '2026-01-30', '16:08:35', 'Present'),
(456, '23WP82178', 46, '2026-01-30', '16:29:27', 'Present'),
(457, '23WP84917', 46, '2026-01-30', '16:23:58', 'Present'),
(458, '23WP94665', 46, '2026-01-30', '16:13:02', 'Present'),
(459, '23WP99001', 46, '2026-01-30', '16:05:53', 'Present'),
(466, '23WP12926', 23, '2026-02-11', '20:15:42', 'Present'),
(467, '23WP73789', 23, '2026-02-11', '20:17:37', 'Late'),
(468, '23WP57187', 23, '2026-02-11', '21:20:28', 'Present'),
(469, '23WP82670', 23, '2026-02-11', '21:20:29', 'Present'),
(470, '23WP40593', 23, '2026-02-11', '21:20:30', 'Present'),
(471, '23WP38477', 23, '2026-02-11', '21:20:31', 'Present'),
(472, '23WP61618', 23, '2026-02-11', '21:20:32', 'Present'),
(473, '23WP61839', 23, '2026-02-11', '21:20:34', 'Present'),
(474, '23WP71365', 23, '2026-02-11', '21:20:34', 'Present'),
(475, '23WP57467', 23, '2026-02-11', '21:20:35', 'Present'),
(476, '23WP79311', 23, '2026-02-11', '21:20:36', 'Present'),
(477, '23WP21706', 23, '2026-02-11', '21:20:36', 'Present'),
(479, '23WP84917', 28, '2026-02-13', '21:32:34', 'Present'),
(484, '23WP99001', 28, '2026-02-13', '15:38:00', 'Present'),
(485, '23WP12509', 28, '2026-02-13', '15:43:54', 'Present'),
(487, '23WP94665', 28, '2026-02-13', '19:14:40', 'Present');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_sessions`
--

CREATE TABLE `attendance_sessions` (
  `sessionID` int(11) NOT NULL,
  `scheduleID` int(11) NOT NULL,
  `sessionDate` date NOT NULL,
  `code` varchar(6) NOT NULL,
  `status` enum('OPEN','CLOSED') DEFAULT 'OPEN',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_sessions`
--

INSERT INTO `attendance_sessions` (`sessionID`, `scheduleID`, `sessionDate`, `code`, `status`, `created_at`) VALUES
(6, 3, '2026-01-29', '383023', 'CLOSED', '2026-01-30 15:39:51'),
(7, 4, '2026-01-29', '298560', 'CLOSED', '2026-01-30 15:39:51'),
(8, 21, '2026-01-26', '243731', 'CLOSED', '2026-01-30 15:39:51'),
(9, 22, '2026-01-26', '222975', 'CLOSED', '2026-01-30 15:39:51'),
(10, 23, '2026-01-27', '283682', 'CLOSED', '2026-01-30 15:39:51'),
(11, 24, '2026-01-27', '649484', 'CLOSED', '2026-01-30 15:39:51'),
(12, 25, '2026-01-28', '496380', 'CLOSED', '2026-01-30 15:39:51'),
(13, 26, '2026-01-28', '433447', 'CLOSED', '2026-01-30 15:39:51'),
(14, 27, '2026-01-30', '578098', 'CLOSED', '2026-01-30 15:39:51'),
(15, 28, '2026-01-30', '590148', 'CLOSED', '2026-01-30 15:39:51'),
(16, 29, '2026-01-26', '216449', 'CLOSED', '2026-01-30 15:39:51'),
(17, 30, '2026-01-26', '111798', 'CLOSED', '2026-01-30 15:39:51'),
(18, 35, '2026-01-27', '709645', 'CLOSED', '2026-01-30 15:39:51'),
(19, 36, '2026-01-27', '412836', 'CLOSED', '2026-01-30 15:39:51'),
(20, 37, '2026-01-28', '735242', 'CLOSED', '2026-01-30 15:39:51'),
(21, 38, '2026-01-28', '537704', 'CLOSED', '2026-01-30 15:39:51'),
(22, 39, '2026-01-27', '382794', 'CLOSED', '2026-01-30 15:39:51'),
(23, 40, '2026-01-27', '200861', 'CLOSED', '2026-01-30 15:39:51'),
(24, 41, '2026-01-29', '655920', 'CLOSED', '2026-01-30 15:39:51'),
(25, 42, '2026-01-29', '777021', 'CLOSED', '2026-01-30 15:39:51'),
(26, 43, '2026-01-28', '917347', 'CLOSED', '2026-01-30 15:39:51'),
(27, 44, '2026-01-28', '355674', 'CLOSED', '2026-01-30 15:39:51'),
(28, 45, '2026-01-30', '726330', 'CLOSED', '2026-01-30 15:39:51'),
(29, 46, '2026-01-30', '664627', 'CLOSED', '2026-01-30 15:39:51'),
(30, 21, '2026-02-02', '844129', 'CLOSED', '2026-02-02 15:20:27'),
(31, 22, '2026-02-02', '548771', 'CLOSED', '2026-02-02 15:20:27'),
(32, 29, '2026-02-02', '211471', 'CLOSED', '2026-02-02 15:20:27'),
(33, 30, '2026-02-02', '411042', 'CLOSED', '2026-02-02 15:20:27'),
(34, 25, '2026-02-04', '637574', 'CLOSED', '2026-02-04 14:15:40'),
(35, 26, '2026-02-04', '866639', 'CLOSED', '2026-02-04 14:15:40'),
(36, 37, '2026-02-04', '420472', 'CLOSED', '2026-02-04 14:15:40'),
(37, 38, '2026-02-04', '502446', 'CLOSED', '2026-02-04 14:15:40'),
(38, 43, '2026-02-04', '250814', 'CLOSED', '2026-02-04 14:15:40'),
(39, 44, '2026-02-04', '746733', 'CLOSED', '2026-02-04 14:15:40'),
(40, 3, '2026-02-05', '930593', 'CLOSED', '2026-02-05 09:11:42'),
(41, 4, '2026-02-05', '765767', 'CLOSED', '2026-02-05 09:11:42'),
(42, 41, '2026-02-05', '037058', 'CLOSED', '2026-02-05 09:11:42'),
(43, 42, '2026-02-05', '887987', 'CLOSED', '2026-02-05 09:11:42'),
(47, 35, '2026-02-03', '000000', 'CLOSED', '2026-02-05 09:19:53'),
(48, 36, '2026-02-03', '000000', 'CLOSED', '2026-02-05 09:19:53'),
(49, 39, '2026-02-03', '000000', 'CLOSED', '2026-02-05 09:19:53'),
(50, 40, '2026-02-03', '000000', 'CLOSED', '2026-02-05 09:19:53'),
(51, 23, '2026-02-03', '000000', 'CLOSED', '2026-02-05 09:19:53'),
(52, 24, '2026-02-03', '000000', 'CLOSED', '2026-02-05 09:19:53'),
(53, 27, '2026-02-06', '498110', 'CLOSED', '2026-02-06 13:11:28'),
(54, 28, '2026-02-06', '931617', 'CLOSED', '2026-02-06 13:11:28'),
(55, 45, '2026-02-06', '163755', 'CLOSED', '2026-02-06 13:11:28'),
(56, 46, '2026-02-06', '023923', 'CLOSED', '2026-02-06 13:11:28'),
(57, 21, '2026-02-09', '382300', 'CLOSED', '2026-02-09 13:34:15'),
(58, 22, '2026-02-09', '611702', 'CLOSED', '2026-02-09 13:34:15'),
(59, 29, '2026-02-09', '911609', 'CLOSED', '2026-02-09 13:34:15'),
(60, 30, '2026-02-09', '722943', 'CLOSED', '2026-02-09 13:34:15'),
(61, 25, '2026-02-11', '066496', 'CLOSED', '2026-02-11 11:38:19'),
(62, 26, '2026-02-11', '700242', 'CLOSED', '2026-02-11 11:38:19'),
(63, 37, '2026-02-11', '301726', 'CLOSED', '2026-02-11 11:38:19'),
(64, 38, '2026-02-11', '407901', 'CLOSED', '2026-02-11 11:38:19'),
(65, 43, '2026-02-11', '134329', 'CLOSED', '2026-02-11 11:38:19'),
(66, 44, '2026-02-11', '447941', 'CLOSED', '2026-02-11 11:38:19'),
(68, 23, '2026-02-11', '973485', 'CLOSED', '2026-02-11 12:15:17'),
(69, 49, '2026-02-11', '069985', 'CLOSED', '2026-02-11 12:42:59'),
(70, 54, '2026-02-11', '696289', 'CLOSED', '2026-02-11 12:42:59'),
(71, 57, '2026-02-11', '271490', 'CLOSED', '2026-02-11 12:42:59'),
(72, 3, '2026-02-12', '439201', 'CLOSED', '2026-02-12 07:41:19'),
(73, 4, '2026-02-12', '549494', 'CLOSED', '2026-02-12 07:41:19'),
(74, 41, '2026-02-12', '429867', 'CLOSED', '2026-02-12 07:41:19'),
(75, 51, '2026-02-12', '500855', 'CLOSED', '2026-02-12 07:41:19'),
(76, 53, '2026-02-12', '214673', 'CLOSED', '2026-02-12 07:41:19'),
(79, 42, '2026-02-12', '084293', 'CLOSED', '2026-02-12 08:26:49'),
(80, 28, '2026-02-13', '894308', 'CLOSED', '2026-02-13 12:03:43'),
(81, 45, '2026-02-13', '616378', 'CLOSED', '2026-02-13 12:03:43'),
(82, 46, '2026-02-13', '398968', 'CLOSED', '2026-02-13 12:03:43'),
(83, 56, '2026-02-13', '145705', 'CLOSED', '2026-02-13 12:03:43'),
(84, 58, '2026-02-13', '531621', 'CLOSED', '2026-02-13 12:03:43'),
(86, 48, '2026-02-16', '829616', 'CLOSED', '2026-02-20 14:05:36'),
(87, 21, '2026-02-16', '905332', 'CLOSED', '2026-02-20 14:05:36'),
(88, 22, '2026-02-16', '037811', 'CLOSED', '2026-02-20 14:05:36'),
(89, 50, '2026-02-16', '473062', 'CLOSED', '2026-02-20 14:05:36'),
(90, 29, '2026-02-16', '251875', 'CLOSED', '2026-02-20 14:05:36'),
(91, 30, '2026-02-16', '840188', 'CLOSED', '2026-02-20 14:05:36'),
(92, 35, '2026-02-17', '445318', 'CLOSED', '2026-02-20 14:05:36'),
(93, 52, '2026-02-17', '706028', 'CLOSED', '2026-02-20 14:05:36'),
(94, 24, '2026-02-17', '194185', 'CLOSED', '2026-02-20 14:05:36'),
(95, 36, '2026-02-17', '852840', 'CLOSED', '2026-02-20 14:05:36'),
(96, 55, '2026-02-17', '681645', 'CLOSED', '2026-02-20 14:05:36'),
(97, 39, '2026-02-17', '849705', 'CLOSED', '2026-02-20 14:05:36'),
(98, 40, '2026-02-17', '203591', 'CLOSED', '2026-02-20 14:05:36'),
(99, 27, '2026-02-17', '468842', 'CLOSED', '2026-02-20 14:05:36'),
(100, 49, '2026-02-18', '733438', 'CLOSED', '2026-02-20 14:05:36'),
(101, 25, '2026-02-18', '260663', 'CLOSED', '2026-02-20 14:05:36'),
(102, 37, '2026-02-18', '103003', 'CLOSED', '2026-02-20 14:05:36'),
(103, 54, '2026-02-18', '733025', 'CLOSED', '2026-02-20 14:05:36'),
(104, 38, '2026-02-18', '356117', 'CLOSED', '2026-02-20 14:05:36'),
(105, 26, '2026-02-18', '581510', 'CLOSED', '2026-02-20 14:05:36'),
(106, 43, '2026-02-18', '839198', 'CLOSED', '2026-02-20 14:05:36'),
(107, 57, '2026-02-18', '451463', 'CLOSED', '2026-02-20 14:05:36'),
(108, 44, '2026-02-18', '739723', 'CLOSED', '2026-02-20 14:05:36'),
(109, 3, '2026-02-19', '344224', 'CLOSED', '2026-02-20 14:05:36'),
(110, 51, '2026-02-19', '501952', 'CLOSED', '2026-02-20 14:05:36'),
(111, 4, '2026-02-19', '477088', 'CLOSED', '2026-02-20 14:05:36'),
(112, 41, '2026-02-19', '879583', 'CLOSED', '2026-02-20 14:05:36'),
(113, 53, '2026-02-19', '966654', 'CLOSED', '2026-02-20 14:05:36'),
(114, 42, '2026-02-19', '194519', 'CLOSED', '2026-02-20 14:05:36'),
(115, 27, '2026-02-20', '002753', 'CLOSED', '2026-02-20 14:07:00'),
(116, 28, '2026-02-20', '176869', 'CLOSED', '2026-02-20 14:07:00'),
(117, 45, '2026-02-20', '876089', 'CLOSED', '2026-02-20 14:07:00'),
(118, 46, '2026-02-20', '849839', 'CLOSED', '2026-02-20 14:07:00'),
(119, 56, '2026-02-20', '620929', 'CLOSED', '2026-02-20 14:07:00'),
(120, 58, '2026-02-20', '555126', 'CLOSED', '2026-02-20 14:07:00'),
(121, 21, '2026-02-23', '098851', 'CLOSED', '2026-02-23 11:48:38'),
(122, 22, '2026-02-23', '114520', 'CLOSED', '2026-02-23 11:48:38'),
(123, 29, '2026-02-23', '276050', 'CLOSED', '2026-02-23 11:48:38'),
(124, 30, '2026-02-23', '036691', 'CLOSED', '2026-02-23 11:48:38'),
(125, 48, '2026-02-23', '355307', 'CLOSED', '2026-02-23 11:48:38'),
(126, 50, '2026-02-23', '666460', 'CLOSED', '2026-02-23 11:48:38'),
(127, 23, '2026-02-24', '891909', 'CLOSED', '2026-02-24 14:07:11'),
(128, 24, '2026-02-24', '693483', 'CLOSED', '2026-02-24 14:07:11'),
(129, 35, '2026-02-24', '791692', 'CLOSED', '2026-02-24 14:07:11'),
(130, 36, '2026-02-24', '878008', 'CLOSED', '2026-02-24 14:07:11'),
(131, 39, '2026-02-24', '014966', 'CLOSED', '2026-02-24 14:07:11'),
(132, 40, '2026-02-24', '440807', 'CLOSED', '2026-02-24 14:07:11'),
(133, 52, '2026-02-24', '159136', 'CLOSED', '2026-02-24 14:07:11'),
(134, 55, '2026-02-24', '473262', 'CLOSED', '2026-02-24 14:07:11'),
(144, 25, '2026-02-25', '904328', 'CLOSED', '2026-02-25 12:34:00'),
(145, 26, '2026-02-25', '269297', 'CLOSED', '2026-02-25 12:34:00'),
(146, 37, '2026-02-25', '633502', 'CLOSED', '2026-02-25 12:34:00'),
(147, 38, '2026-02-25', '359619', 'CLOSED', '2026-02-25 12:34:00'),
(148, 43, '2026-02-25', '897588', 'CLOSED', '2026-02-25 12:34:00'),
(149, 44, '2026-02-25', '409086', 'CLOSED', '2026-02-25 12:34:00'),
(150, 49, '2026-02-25', '352664', 'CLOSED', '2026-02-25 12:34:00'),
(151, 54, '2026-02-25', '536065', 'CLOSED', '2026-02-25 12:34:00'),
(152, 57, '2026-02-25', '622334', 'CLOSED', '2026-02-25 12:34:00'),
(153, 3, '2026-02-26', '668130', 'CLOSED', '2026-02-26 11:49:43'),
(154, 4, '2026-02-26', '567737', 'CLOSED', '2026-02-26 11:49:43'),
(155, 41, '2026-02-26', '834293', 'CLOSED', '2026-02-26 11:49:43'),
(156, 42, '2026-02-26', '468254', 'CLOSED', '2026-02-26 11:49:43'),
(157, 51, '2026-02-26', '838394', 'CLOSED', '2026-02-26 11:49:43'),
(158, 53, '2026-02-26', '787208', 'CLOSED', '2026-02-26 11:49:43'),
(159, 27, '2026-02-27', '092929', 'CLOSED', '2026-02-27 13:06:09'),
(160, 28, '2026-02-27', '184024', 'CLOSED', '2026-02-27 13:06:09'),
(161, 45, '2026-02-27', '641334', 'CLOSED', '2026-02-27 13:06:09'),
(162, 46, '2026-02-27', '654600', 'CLOSED', '2026-02-27 13:06:09'),
(163, 56, '2026-02-27', '348998', 'CLOSED', '2026-02-27 13:06:09'),
(164, 58, '2026-02-27', '781191', 'CLOSED', '2026-02-27 13:06:09');

-- --------------------------------------------------------

--
-- Table structure for table `booking_rules`
--

CREATE TABLE `booking_rules` (
  `ruleID` int(11) NOT NULL,
  `facilityType` varchar(20) NOT NULL,
  `maxDurationMinutes` int(11) DEFAULT 120,
  `maxAdvanceDays` int(11) DEFAULT 7,
  `maxActiveBookings` int(11) DEFAULT 3,
  `allowedRoles` varchar(50) DEFAULT 'Student,Staff',
  `operatingStart` time DEFAULT '08:00:00',
  `operatingEnd` time DEFAULT '22:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_rules`
--

INSERT INTO `booking_rules` (`ruleID`, `facilityType`, `maxDurationMinutes`, `maxAdvanceDays`, `maxActiveBookings`, `allowedRoles`, `operatingStart`, `operatingEnd`) VALUES
(1, 'Sport', 120, 7, 3, 'Student,Staff', '08:00:00', '22:00:00'),
(2, 'Lab', 120, 7, 2, 'Student,Staff', '08:00:00', '18:00:00'),
(3, 'Meeting', 120, 14, 3, 'Staff', '08:00:00', '18:00:00'),
(4, 'Study', 120, 7, 3, 'Student,Staff', '08:00:00', '22:00:00'),
(5, 'Tutorial', 120, 7, 2, 'Staff', '08:00:00', '18:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `class_group_removals`
--

CREATE TABLE `class_group_removals` (
  `removalID` int(11) NOT NULL,
  `studentID` varchar(12) NOT NULL,
  `programID` varchar(10) NOT NULL,
  `tutGroup` varchar(20) NOT NULL,
  `removedBy` varchar(12) NOT NULL,
  `reason` text NOT NULL,
  `removedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `reinstatedAt` timestamp NULL DEFAULT NULL,
  `reinstatedBy` varchar(12) DEFAULT NULL,
  `status` enum('Removed','Reinstated') DEFAULT 'Removed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_group_removals`
--

INSERT INTO `class_group_removals` (`removalID`, `studentID`, `programID`, `tutGroup`, `removedBy`, `reason`, `removedAt`, `reinstatedAt`, `reinstatedBy`, `status`) VALUES
(1, '23WP27671', 'RSW', 'Group B', 'S007', 'Test', '2026-02-13 13:59:22', '2026-02-13 14:01:10', 'S007', 'Reinstated'),
(2, '23WP12509', 'RSD', 'Group A', 'S007', 'Missing', '2026-02-13 14:59:25', '2026-02-13 15:00:11', 'S007', 'Reinstated'),
(3, '23WP99001', 'RSD', 'Group A', 'S001', 'racist', '2026-02-14 13:56:42', '2026-02-14 13:56:54', 'S001', 'Reinstated'),
(4, '25WP00001', 'RSD', 'Group A', 'S001', 'Try', '2026-02-24 14:59:13', '2026-02-24 14:59:26', 'S001', 'Reinstated');

-- --------------------------------------------------------

--
-- Table structure for table `class_schedule`
--

CREATE TABLE `class_schedule` (
  `scheduleID` int(11) NOT NULL,
  `termID` int(11) DEFAULT NULL,
  `programID` varchar(10) NOT NULL,
  `courseID` varchar(12) NOT NULL,
  `tutGroup` varchar(20) NOT NULL,
  `staffID` varchar(10) NOT NULL,
  `facilityID` varchar(12) NOT NULL,
  `day` varchar(10) NOT NULL,
  `startTime` time NOT NULL,
  `endTime` time NOT NULL,
  `classType` varchar(20) NOT NULL,
  `semesterWeeks` int(11) DEFAULT 14 COMMENT 'Number of weeks this class runs (7 for short sem, 14 for long sem)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_schedule`
--

INSERT INTO `class_schedule` (`scheduleID`, `termID`, `programID`, `courseID`, `tutGroup`, `staffID`, `facilityID`, `day`, `startTime`, `endTime`, `classType`, `semesterWeeks`) VALUES
(3, 1, 'RSD', 'BACS1013', 'Group A', 'S010', 'DK-B', 'Thursday', '08:00:00', '10:00:00', 'Lecture', 7),
(4, 1, 'RSD', 'BACS1013', 'Group A', 'S010', 'LAB-A01', 'Thursday', '10:00:00', '11:00:00', 'Practical', 7),
(21, 4, 'RSW', 'BACS1013', 'Group B', 'S010', 'DK-C', 'Monday', '08:00:00', '10:00:00', 'Lecture', 7),
(22, 4, 'RSW', 'BACS1013', 'Group B', 'S010', 'LAB-A02', 'Monday', '10:00:00', '11:00:00', 'Practical', 7),
(23, 4, 'RSW', 'BJEL1713', 'Group B', 'S001', 'DK-C', 'Tuesday', '08:00:00', '10:00:00', 'Lecture', 7),
(24, 4, 'RSW', 'BJEL1713', 'Group B', 'S001', 'TR-101', 'Tuesday', '10:00:00', '11:00:00', 'Tutorial', 7),
(25, 4, 'RSW', 'MPU3103', 'Group B', 'S007', 'DK-C', 'Wednesday', '08:00:00', '10:00:00', 'Lecture', 7),
(26, 4, 'RSW', 'MPU3103', 'Group B', 'S007', 'TR-101', 'Wednesday', '10:00:00', '11:00:00', 'Tutorial', 7),
(27, 1, 'RSD', 'BJEL1713', 'Group A', 'S001', 'DK-B', 'Friday', '08:00:00', '10:00:00', 'Lecture', 7),
(28, 1, 'RSD', 'BJEL1713', 'Group A', 'S001', 'TR-101', 'Friday', '10:00:00', '11:00:00', 'Tutorial', 7),
(29, 1, 'RSD', 'MPU3103', 'Group A', 'S007', 'DK-C', 'Monday', '14:00:00', '16:00:00', 'Lecture', 7),
(30, 1, 'RSD', 'MPU3103', 'Group A', 'S007', 'TR-102', 'Monday', '16:00:00', '17:00:00', 'Tutorial', 7),
(35, 2, 'RSD', 'BACS1053', 'Group A', 'S004', 'DK-A', 'Tuesday', '08:00:00', '10:00:00', 'Lecture', 14),
(36, 2, 'RSD', 'BACS1053', 'Group A', 'S004', 'LAB-A01', 'Tuesday', '10:00:00', '11:00:00', 'Practical', 14),
(37, 2, 'RSD', 'BAIT1023', 'Group A', 'S005', 'DK-A', 'Wednesday', '08:00:00', '10:00:00', 'Lecture', 14),
(38, 2, 'RSD', 'BAIT1023', 'Group A', 'S005', 'LAB-B02', 'Wednesday', '10:00:00', '11:00:00', 'Practical', 14),
(39, 2, 'RSD', 'BAIT1043', 'Group A', 'S006', 'DK-B', 'Tuesday', '14:00:00', '16:00:00', 'Lecture', 14),
(40, 2, 'RSD', 'BAIT1043', 'Group A', 'S006', 'TR-101', 'Tuesday', '16:00:00', '17:00:00', 'Tutorial', 14),
(41, 2, 'RSD', 'BAIT1173', 'Group A', 'S011', 'DK-A', 'Thursday', '14:00:00', '16:00:00', 'Lecture', 14),
(42, 2, 'RSD', 'BAIT1173', 'Group A', 'S011', 'LAB-C01', 'Thursday', '16:00:00', '17:00:00', 'Practical', 14),
(43, 2, 'RSD', 'BAMS1613', 'Group A', 'S014', 'DK-C', 'Wednesday', '14:00:00', '16:00:00', 'Lecture', 14),
(44, 2, 'RSD', 'BAMS1613', 'Group A', 'S014', 'TR-102', 'Wednesday', '16:00:00', '17:00:00', 'Tutorial', 14),
(45, 2, 'RSD', 'MPU3302', 'Group A', 'S008', 'DK-B', 'Friday', '14:00:00', '16:00:00', 'Lecture', 14),
(46, 2, 'RSD', 'MPU3302', 'Group A', 'S008', 'TR-101', 'Friday', '16:00:00', '17:00:00', 'Tutorial', 14),
(48, 5, 'RSD', 'BAIT2113', 'Group A', 'S001', 'DK-A', 'Monday', '08:00:00', '10:00:00', 'Lecture', 14),
(49, 5, 'RSD', 'BAIT2113', 'Group A', 'S001', 'LAB-A01', 'Wednesday', '08:00:00', '09:00:00', 'Lab', 14),
(50, 5, 'RSD', 'BAIT2073', 'Group A', 'S002', 'DK-B', 'Monday', '14:00:00', '16:00:00', 'Lecture', 14),
(51, 5, 'RSD', 'BAIT2073', 'Group A', 'S002', 'LAB-B01', 'Thursday', '10:00:00', '11:00:00', 'Lab', 14),
(52, 5, 'RSD', 'BACS2053', 'Group A', 'S003', 'DK-C', 'Tuesday', '09:00:00', '11:00:00', 'Lecture', 14),
(53, 5, 'RSD', 'BACS2053', 'Group A', 'S003', 'TR-101', 'Thursday', '14:00:00', '15:00:00', 'Tutorial', 14),
(54, 5, 'RSD', 'BACS2042', 'Group A', 'S004', 'TR-102', 'Wednesday', '10:00:00', '12:00:00', 'Lecture', 14),
(55, 5, 'RSD', 'BAIT2023', 'Group A', 'S005', 'DK-A', 'Tuesday', '14:00:00', '16:00:00', 'Lecture', 14),
(56, 5, 'RSD', 'BAIT2023', 'Group A', 'S005', 'LAB-A02', 'Friday', '09:00:00', '10:00:00', 'Lab', 14),
(57, 5, 'RSD', 'BMIT2154', 'Group A', 'S006', 'DK-B', 'Wednesday', '14:00:00', '16:00:00', 'Lecture', 14),
(58, 5, 'RSD', 'BMIT2154', 'Group A', 'S006', 'LAB-B02', 'Friday', '14:00:00', '16:00:00', 'Lab', 14);

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `courseID` varchar(12) NOT NULL,
  `courseName` varchar(100) NOT NULL,
  `creditHours` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`courseID`, `courseName`, `creditHours`) VALUES
('BACS1013', 'Problem Solving and Programming', 3),
('BACS1024', 'Introduction to Computer Systems', 4),
('BACS1053', 'Database Management', 3),
('BACS2023', 'Object-Oriented Programming', 3),
('BACS2042', 'Research Methods', 2),
('BACS2053', 'Object-Oriented Analysis and Design', 3),
('BACS2063', 'Data Structures and Algorithms', 3),
('BACS2163', 'Software Engineering', 3),
('BAIT1023', 'Web Design and Development', 3),
('BAIT1043', 'Systems Analysis and Design', 3),
('BAIT1173', 'IT Fundamentals', 3),
('BAIT2004', 'Fundamentals of Computer Networks', 4),
('BAIT2023', 'Introduction to Internet Security', 3),
('BAIT2073', 'Mobile Application Development', 3),
('BAIT2113', 'Web Application Development', 3),
('BAIT2203', 'Human Computer Interaction', 3),
('BAMS1613', 'Probability and Statistics', 3),
('BAMS1623', 'Discrete Mathematics', 3),
('BJEL1713', 'English for Tertiary Studies', 3),
('BJEL1723', 'Academic English', 3),
('BJEL2013', 'English for Career Preparation', 3),
('BMCS1024', 'Computer Organisation and Architecture', 4),
('BMCS2073', 'Operating Systems', 3),
('BMCS2203', 'Artificial Intelligence', 3),
('BMCS3033', 'Social and Professional Issues', 3),
('BMCS3103', 'Image Processing', 3),
('BMCS3403', 'Project I', 3),
('BMCS3413', 'Project II', 3),
('BMDS3013', 'Data Science', 3),
('BMIT2123', 'Internet of Things', 3),
('BMIT2154', 'Switching and Routing Technologies', 4),
('BMIT305C', 'Industrial Training', 12),
('BMIT3084', 'Enterprise Networking', 4),
('BMIT3173', 'Integrative Programming', 3),
('BMIT3273', 'Cloud Computing', 3),
('BMSE2103', 'Software Quality Assurance and Testing', 3),
('BMSE3013', 'Software Testing', 3),
('BMSE3023', 'Software Quality and Measurement', 3),
('BMSE3033', 'Software Requirements Engineering', 3),
('BMSE3043', 'Software Design and Architecture', 3),
('BMSE3053', 'Software Engineering Ethics and Professionalism', 3),
('BMSE3063', 'Software Security and Safety', 3),
('BMSE3073', 'Software Project Management', 3),
('BMSE3083', 'Software Maintenance', 3),
('BMSE3093', 'Collaborative Development', 3),
('BMSE3103', 'Formal Methods for Software Engineering', 3),
('BMSE3113', 'Web-Based Integrated Systems', 3),
('ECOQ', 'Co-Curricular', 2),
('MPU3103', 'Penghayatan Etika dan Peradaban', 3),
('MPU3133', 'Falsafah dan Isu Semasa', 3),
('MPU3212', 'Bahasa Kebangsaan A', 2),
('MPU3232', 'Entrepreneurship', 2),
('MPU3302', 'Integrity and Anti-Corruption', 2);

-- --------------------------------------------------------

--
-- Table structure for table `course_offering`
--

CREATE TABLE `course_offering` (
  `offeringID` int(11) NOT NULL,
  `courseID` varchar(12) NOT NULL,
  `programID` varchar(10) NOT NULL,
  `termID` int(11) NOT NULL,
  `sectionNo` int(11) DEFAULT 1,
  `capacity` int(11) NOT NULL DEFAULT 40,
  `status` enum('Open','Closed','Full') DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_offering`
--

INSERT INTO `course_offering` (`offeringID`, `courseID`, `programID`, `termID`, `sectionNo`, `capacity`, `status`, `created_at`) VALUES
(1, 'BMIT3173', 'RSD', 6, 1, 30, 'Open', '2026-02-11 12:40:40'),
(2, 'BMIT3084', 'RSD', 6, 1, 30, 'Open', '2026-02-11 12:40:40'),
(3, 'BJEL2013', 'RSD', 6, 1, 30, 'Open', '2026-02-11 12:40:40'),
(4, 'MPU3133', 'RSD', 6, 1, 30, 'Open', '2026-02-11 12:40:40'),
(5, 'BMCS2203', 'RSD', 6, 1, 25, 'Open', '2026-02-11 12:40:40'),
(6, 'BMIT2123', 'RSD', 6, 1, 25, 'Open', '2026-02-11 12:40:40'),
(7, 'BMSE2103', 'RSD', 6, 1, 25, 'Open', '2026-02-11 12:40:40'),
(8, 'BAIT2203', 'RSW', 6, 1, 100, 'Open', '2026-02-27 13:08:26'),
(9, 'BJEL2013', 'RSW', 6, 1, 100, 'Open', '2026-02-27 13:08:26'),
(10, 'BMCS2203', 'RSW', 6, 1, 100, 'Open', '2026-02-27 13:08:26'),
(11, 'BMCS3103', 'RSW', 6, 1, 100, 'Open', '2026-02-27 13:08:26'),
(12, 'BMSE3093', 'RSW', 6, 1, 100, 'Open', '2026-02-27 13:08:26'),
(13, 'MPU3133', 'RSW', 6, 1, 100, 'Open', '2026-02-27 13:08:26'),
(14, 'BACS1053', 'RSD', 2, 1, 40, 'Open', '2026-02-27 14:16:35'),
(15, 'BAIT1023', 'RSD', 2, 1, 40, 'Open', '2026-02-27 14:16:35'),
(16, 'BAIT1043', 'RSD', 2, 1, 40, 'Open', '2026-02-27 14:16:35'),
(17, 'BAIT1173', 'RSD', 2, 1, 40, 'Open', '2026-02-27 14:16:35'),
(18, 'BAMS1613', 'RSD', 2, 1, 40, 'Open', '2026-02-27 14:16:35'),
(19, 'MPU3302', 'RSD', 2, 1, 40, 'Open', '2026-02-27 14:16:35');

-- --------------------------------------------------------

--
-- Table structure for table `course_registration`
--

CREATE TABLE `course_registration` (
  `registrationID` int(11) NOT NULL,
  `studentID` varchar(12) NOT NULL,
  `offeringID` int(11) NOT NULL,
  `status` enum('Registered','Dropped','Waitlisted') DEFAULT 'Registered',
  `registeredAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `droppedAt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_registration`
--

INSERT INTO `course_registration` (`registrationID`, `studentID`, `offeringID`, `status`, `registeredAt`, `droppedAt`) VALUES
(8, '25WP00001', 1, 'Registered', '2026-02-11 13:15:16', NULL),
(9, '25WP00001', 2, 'Registered', '2026-02-11 13:15:16', NULL),
(10, '25WP00001', 3, 'Registered', '2026-02-11 13:15:16', NULL),
(11, '25WP00001', 4, 'Registered', '2026-02-11 14:33:09', NULL),
(12, '25WP00001', 5, 'Dropped', '2026-02-11 14:19:57', '2026-02-11 14:32:58'),
(13, '25WP00001', 6, 'Registered', '2026-02-11 14:33:02', NULL),
(18, '23WP12509', 14, 'Registered', '2026-02-27 15:06:11', NULL),
(19, '23WP12509', 15, 'Registered', '2026-02-27 15:06:11', NULL),
(20, '23WP12509', 16, 'Registered', '2026-02-27 15:06:11', NULL),
(21, '23WP12509', 17, 'Registered', '2026-02-27 15:06:11', NULL),
(22, '23WP12509', 18, 'Registered', '2026-02-27 15:06:11', NULL),
(23, '23WP12509', 19, 'Registered', '2026-02-27 15:06:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `facility`
--

CREATE TABLE `facility` (
  `facilityID` varchar(12) NOT NULL,
  `facilityName` varchar(50) DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  `location` varchar(50) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `status` varchar(15) DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facility`
--

INSERT INTO `facility` (`facilityID`, `facilityName`, `type`, `location`, `capacity`, `status`) VALUES
('BC-01', 'Badminton Court 1', 'Sport', 'Sports Complex, Lvl 1', 4, 'Active'),
('BC-02', 'Badminton Court 2', 'Sport', 'Sports Complex, Lvl 1', 4, 'Active'),
('DK-A', 'Lecture Hall A', 'Hall', 'Block A, Gnd', 200, 'Active'),
('DK-B', 'Lecture Hall B', 'Hall', 'Block B, Gnd', 150, 'Active'),
('DK-C', 'Lecture Hall C', 'Hall', 'Block B, Gnd', 150, 'Active'),
('LAB-A01', 'Computer Lab 1', 'Lab', 'Block A, Lvl 2', 30, 'Active'),
('LAB-A02', 'Software Eng. Lab', 'Lab', 'Block A, Lvl 2', 40, 'Active'),
('LAB-B01', 'Networking Lab', 'Lab', 'Block B, Lvl 1', 35, 'Active'),
('LAB-B02', 'Multimedia Lab', 'Lab', 'Block B, Lvl 1', 35, 'Active'),
('LAB-C01', 'General IT Lab', 'Lab', 'Block C, Lvl 3', 60, 'Active'),
('MR-01', 'Meeting Room 1', 'Meeting', 'Block A, Lvl 3', 10, 'Active'),
('MR-02', 'Meeting Room 2', 'Meeting', 'Block B, Lvl 2', 8, 'Active'),
('PPT-01', 'Ping Pong Table 1', 'Sport', 'Sports Complex, Lvl 1', 4, 'Active'),
('PPT-02', 'Ping Pong Table 2', 'Sport', 'Sports Complex, Lvl 1', 4, 'Active'),
('SQC-01', 'Squash Court 1', 'Sport', 'Sports Complex, Lvl 2', 2, 'Active'),
('SR-01', 'Study Room 1', 'Study', 'Block C, Lvl 2', 6, 'Active'),
('SR-02', 'Study Room 2', 'Study', 'Block C, Lvl 2', 6, 'Active'),
('TNC-01', 'Tennis Court', 'Sport', 'Outdoor Area', 4, 'Active'),
('TR-101', 'Tutorial Room 101', 'Tutorial', 'Block C, Lvl 1', 30, 'Active'),
('TR-102', 'Tutorial Room 102', 'Tutorial', 'Block C, Lvl 1', 30, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `facility_booking`
--

CREATE TABLE `facility_booking` (
  `bookingID` int(11) NOT NULL,
  `facilityID` varchar(12) NOT NULL,
  `userID` varchar(20) NOT NULL,
  `userRole` enum('Student','Staff') NOT NULL,
  `bookingDate` date NOT NULL,
  `startTime` time NOT NULL,
  `endTime` time NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `status` enum('Active','Cancelled','Completed') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facility_booking`
--

INSERT INTO `facility_booking` (`bookingID`, `facilityID`, `userID`, `userRole`, `bookingDate`, `startTime`, `endTime`, `purpose`, `status`, `created_at`) VALUES
(2, 'BC-02', '23WP12509', 'Student', '2026-02-10', '08:00:00', '10:00:00', 'exercise', 'Active', '2026-02-09 11:26:03'),
(3, 'BC-01', '23WP12926', 'Student', '2026-02-11', '08:00:00', '10:00:00', 'exercise', 'Active', '2026-02-09 13:38:15');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `userID` varchar(12) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `program`
--

CREATE TABLE `program` (
  `programID` varchar(10) NOT NULL,
  `programName` varchar(150) NOT NULL,
  `duration` int(11) NOT NULL,
  `faculty` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program`
--

INSERT INTO `program` (`programID`, `programName`, `duration`, `faculty`) VALUES
('RSD', 'Bachelor of Information Technology (Honours) in Software Systems Development', 3, 'Faculty of Computing and Information Technology'),
('RSW', 'Bachelor in Software Engineering (Honours)', 3, 'Faculty of Computing and Information Technology');

-- --------------------------------------------------------

--
-- Table structure for table `program_course`
--

CREATE TABLE `program_course` (
  `programID` varchar(10) NOT NULL,
  `courseID` varchar(12) NOT NULL,
  `type` varchar(20) DEFAULT 'Core',
  `year` int(1) NOT NULL DEFAULT 1,
  `semester` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_course`
--

INSERT INTO `program_course` (`programID`, `courseID`, `type`, `year`, `semester`) VALUES
('RSD', 'BACS1013', 'Main', 1, 1),
('RSD', 'BACS1024', 'Main', 1, 3),
('RSD', 'BACS1053', 'Main', 1, 2),
('RSD', 'BACS2023', 'Main', 1, 3),
('RSD', 'BACS2042', 'Main', 2, 2),
('RSD', 'BACS2053', 'Main', 2, 2),
('RSD', 'BACS2063', 'Main', 2, 1),
('RSD', 'BACS2163', 'Main', 2, 1),
('RSD', 'BAIT1023', 'Main', 1, 2),
('RSD', 'BAIT1043', 'Main', 1, 2),
('RSD', 'BAIT1173', 'Main', 1, 2),
('RSD', 'BAIT2004', 'Main', 1, 3),
('RSD', 'BAIT2023', 'Main', 2, 2),
('RSD', 'BAIT2073', 'Main', 2, 2),
('RSD', 'BAIT2113', 'Main', 2, 2),
('RSD', 'BAIT2203', 'Main', 1, 3),
('RSD', 'BAMS1613', 'Main', 1, 2),
('RSD', 'BAMS1623', 'Main', 1, 3),
('RSD', 'BJEL1713', 'Main', 1, 1),
('RSD', 'BJEL1723', 'Main', 1, 3),
('RSD', 'BJEL2013', 'Main', 2, 3),
('RSD', 'BMCS2203', 'Elective', 2, 3),
('RSD', 'BMCS3033', 'Main', 3, 2),
('RSD', 'BMCS3403', 'Main', 3, 1),
('RSD', 'BMCS3413', 'Main', 3, 2),
('RSD', 'BMIT2123', 'Elective', 2, 3),
('RSD', 'BMIT2154', 'Main', 2, 2),
('RSD', 'BMIT305C', 'Main', 3, 3),
('RSD', 'BMIT3084', 'Main', 2, 3),
('RSD', 'BMIT3173', 'Main', 2, 3),
('RSD', 'BMIT3273', 'Main', 3, 2),
('RSD', 'BMSE2103', 'Elective', 2, 3),
('RSD', 'ECOQ', 'Main', 2, 1),
('RSD', 'MPU3103', 'Main', 1, 1),
('RSD', 'MPU3133', 'Main', 2, 3),
('RSD', 'MPU3212', 'Elective', 3, 1),
('RSD', 'MPU3232', 'Elective', 3, 1),
('RSD', 'MPU3302', 'Main', 1, 2),
('RSW', 'BACS1013', 'Main', 1, 1),
('RSW', 'BACS1053', 'Main', 1, 2),
('RSW', 'BACS2023', 'Main', 1, 3),
('RSW', 'BACS2063', 'Main', 2, 1),
('RSW', 'BACS2163', 'Main', 2, 1),
('RSW', 'BAIT1043', 'Main', 1, 2),
('RSW', 'BAIT2004', 'Main', 1, 3),
('RSW', 'BAIT2203', 'Elective', 2, 3),
('RSW', 'BAMS1613', 'Main', 1, 2),
('RSW', 'BAMS1623', 'Main', 1, 3),
('RSW', 'BJEL1713', 'Main', 1, 1),
('RSW', 'BJEL1723', 'Main', 1, 3),
('RSW', 'BJEL2013', 'Main', 2, 3),
('RSW', 'BMCS1024', 'Main', 1, 2),
('RSW', 'BMCS2073', 'Main', 2, 1),
('RSW', 'BMCS2203', 'Main', 2, 3),
('RSW', 'BMCS3103', 'Elective', 2, 3),
('RSW', 'BMCS3403', 'Main', 3, 1),
('RSW', 'BMCS3413', 'Main', 3, 2),
('RSW', 'BMDS3013', 'Elective', 3, 2),
('RSW', 'BMIT305C', 'Main', 3, 3),
('RSW', 'BMIT3273', 'Elective', 3, 2),
('RSW', 'BMSE3013', 'Main', 2, 2),
('RSW', 'BMSE3023', 'Main', 2, 2),
('RSW', 'BMSE3033', 'Main', 2, 1),
('RSW', 'BMSE3043', 'Main', 2, 2),
('RSW', 'BMSE3053', 'Main', 3, 1),
('RSW', 'BMSE3063', 'Main', 2, 2),
('RSW', 'BMSE3073', 'Main', 3, 2),
('RSW', 'BMSE3083', 'Main', 3, 2),
('RSW', 'BMSE3093', 'Main', 2, 3),
('RSW', 'BMSE3103', 'Elective', 3, 2),
('RSW', 'BMSE3113', 'Elective', 3, 2),
('RSW', 'ECOQ', 'Main', 1, 3),
('RSW', 'MPU3103', 'Main', 1, 1),
('RSW', 'MPU3133', 'Main', 2, 3),
('RSW', 'MPU3212', 'Elective', 3, 1),
('RSW', 'MPU3232', 'Elective', 3, 1),
('RSW', 'MPU3302', 'Main', 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `replacement_request`
--

CREATE TABLE `replacement_request` (
  `replacementID` int(11) NOT NULL,
  `scheduleID` int(11) NOT NULL,
  `originalDate` date DEFAULT NULL,
  `staffID` varchar(12) NOT NULL,
  `newDate` date NOT NULL,
  `newTime` varchar(50) NOT NULL,
  `facilityID` varchar(12) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `adminNotes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `replacement_request`
--

INSERT INTO `replacement_request` (`replacementID`, `scheduleID`, `originalDate`, `staffID`, `newDate`, `newTime`, `facilityID`, `reason`, `adminNotes`, `status`, `reviewed_at`, `created_at`) VALUES
(8, 23, '2026-02-10', 'S001', '2026-02-09', '11:00 - 13:00', 'DK-A', 'Emergency', '', 'Approved', '2026-02-08 19:52:12', '2026-02-08 19:51:56'),
(9, 27, '2026-02-13', 'S001', '2026-02-17', '11:00 - 13:00', 'DK-B', 'Medical Leave', 'assign dkb because dka having speech today, sorry for the trouble', 'Approved', '2026-02-09 13:33:22', '2026-02-09 13:31:48'),
(10, 23, '2026-02-17', 'S001', '2026-02-11', '20:00 - 22:00', 'DK-A', 'Medical Leave', '', 'Approved', '2026-02-11 12:15:08', '2026-02-11 12:14:55');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staffID` varchar(12) NOT NULL,
  `userID` varchar(12) DEFAULT NULL,
  `staffName` varchar(100) DEFAULT NULL,
  `staffType` varchar(10) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contactNo` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staffID`, `userID`, `staffName`, `staffType`, `email`, `contactNo`) VALUES
('S001', 'U002', 'Dr. Aini Musa', 'Both', 'aini@tarc.edu.my', '012-5550001'),
('S002', 'U003', 'Mr. Tan Ah Kao', 'Tutor', 'tanak@tarc.edu.my', '012-5550002'),
('S003', 'U004', 'Ms. Sarah Lee', 'Tutor', 'sarah@tarc.edu.my', '012-5550003'),
('S004', 'U005', 'Prof. John Doe', 'Lecturer', 'john@tarc.edu.my', '012-5550004'),
('S005', 'U006', 'Mr. Lim Wei', 'Tutor', 'limw@tarc.edu.my', '012-5550005'),
('S006', 'U007', 'Ms. Priya K', 'Tutor', 'priya@tarc.edu.my', '012-5550006'),
('S007', 'U008', 'Dr. Wong Siew', 'Both', 'wong@tarc.edu.my', '012-5550007'),
('S008', 'U009', 'Mr. Ali Baba', 'Tutor', 'ali@tarc.edu.my', '012-5550008'),
('S009', 'U010', 'Mr. David Teo', 'Tutor', 'david@tarc.edu.my', '012-5550009'),
('S010', 'U011', 'Ms. Pong Suk Fun', 'Both', 'pongsf@tarc.edu.my', '012-5550010'),
('S011', 'U012', 'Mr. Kumar', 'Lecturer', 'kumar@tarc.edu.my', '012-5550011'),
('S012', 'U053', 'Dr. Sarah Connor', 'Lecturer', 'sarahc@tarc.edu.my', '012-5550012'),
('S013', 'U054', 'Mr. Bruce Wayne', 'Lecturer', 'brucew@tarc.edu.my', '012-5550013'),
('S014', 'U055', 'Ms. Katherine Johnson', 'Lecturer', 'katherine@tarc.edu.my', '012-5550014'),
('S015', 'U056', 'Mr. Clark Kent', 'Tutor', 'clarkk@tarc.edu.my', '012-5550015');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `studentID` varchar(12) NOT NULL,
  `userID` varchar(12) NOT NULL,
  `studentName` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contactNo` varchar(20) DEFAULT NULL,
  `tutGroup` varchar(20) DEFAULT NULL,
  `programID` varchar(10) DEFAULT NULL,
  `currentYear` int(1) DEFAULT 1,
  `currentSemester` int(1) DEFAULT 1,
  `studentImage` varchar(255) DEFAULT 'default_avatar.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`studentID`, `userID`, `studentName`, `email`, `contactNo`, `tutGroup`, `programID`, `currentYear`, `currentSemester`, `studentImage`) VALUES
('23WP12509', 'U013', 'Arvind A/L Subramaniam', 'arvind.12509@student.tarc.edu.my', '012-2262227', 'Group A', 'RSD', 1, 1, 'profile_23WP12509_1772203939.png'),
('23WP12926', 'U014', 'Divya A/P Kumar', 'divya.12926@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP14406', 'U015', 'Teoh Kah Mun', 'teoh.14406@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP20441', 'U016', 'Puteri Balqis Binti Mahadzir', 'puteri.20441@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP20606', 'U017', 'Karthik A/L Anuar', 'karthik.20606@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP21001', 'U060', 'Wong Chun Kit', 'wongck-wp21@student.tarc.edu.my', '012-0000000', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP21572', 'U018', 'Ravi A/L Chandran', 'ravi.21572@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP21706', 'U019', 'Sanjay A/L Muniandy', 'sanjay.21706@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP24179', 'U020', 'Mohammad Faizal Bin Zakaria', 'mohammad.24179@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP24747', 'U021', 'Lee Kian Seng', 'lee.24747@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP27426', 'U022', 'Priya A/P Ravichandran', 'priya.27426@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP27671', 'U023', 'Azman Bin Sulaiman', 'azman.27671@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP31454', 'U024', 'Syafiqah Binti Mansor', 'syafiqah.31454@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP32466', 'U025', 'Jason Low Kah Hing', 'jason.32466@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP34128', 'U026', 'Mohammad Haziq Bin Razak', 'mohammad.34128@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP34773', 'U027', 'Chong Wei Liang', 'chong.34773@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP37638', 'U028', 'Preeti A/P Sundar', 'preeti.37638@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP37849', 'U029', 'Khairul Azmi Bin Hassan', 'khairul.37849@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP38477', 'U030', 'Michelle Tan Xin Yi', 'michelle.38477@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP40593', 'U031', 'Low Yee Wen', 'low.40593@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP47754', 'U032', 'Nurul Izzah Binti Kamaruddin', 'nurul.47754@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP51727', 'U033', 'Siti Aminah Binti Hassan', 'siti.51727@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP56143', 'U034', 'Yeoh Zi Yi', 'yeoh.56143@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP57187', 'U035', 'Kelly Chan Pui Yi', 'kelly.57187@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP57467', 'U036', 'Thivya A/P Selvam', 'thivya.57467@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP61618', 'U037', 'Ng See Kiat', 'ng.61618@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP61839', 'U038', 'Zulhilmi Bin Mohamad', 'zulhilmi.61839@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP69300', 'U039', 'Farah Nabilah Binti Yusof', 'farah.69300@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP71365', 'U040', 'Vikneswaran A/L Mani', 'vikneswaran.71365@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP73033', 'U041', 'Ganesh A/L Thapandion', 'ganesh.73033@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP73789', 'U042', 'Bryan Wong Jun Kit', 'bryan.73789@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP77365', 'U043', 'Lim Jia Hao', 'lim.77365@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP78708', 'U044', 'Tan Mei Ling', 'tan.78708@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP79311', 'U045', 'Siti Sarah Binti Mokhtar', 'siti.79311@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP79793', 'U046', 'Nurul Ain Binti Zulkifli', 'nurul.79793@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP80803', 'U047', 'Wong Siew Fen', 'wong.80803@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP82178', 'U048', 'Anjali A/P Mohan', 'anjali.82178@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP82670', 'U049', 'Liew Chee Keong', 'liew.82670@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP84917', 'U050', 'Ahmad Zaki Bin Rosli', 'ahmad.84917@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP94665', 'U051', 'Ahmad Syahmi Bin Mohd Rizal', 'ahmad.94665@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('23WP96546', 'U052', 'Noraini Binti Abdullah', 'noraini.96546@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW', 1, 1, 'default_avatar.png'),
('23WP99001', 'U057', 'Alex Chen Wei Jie', 'alex.99001@student.tarc.edu.my', '012-3334455', 'Group A', 'RSD', 1, 1, 'default_avatar.png'),
('25WP00001', 'U059', 'Test Student Y2S2', 'test.y2s2@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD', 2, 2, 'default_avatar.png');

-- --------------------------------------------------------

--
-- Table structure for table `student_details`
--

CREATE TABLE `student_details` (
  `studentID` varchar(12) NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `icNo` varchar(20) DEFAULT NULL,
  `homeAddress` text DEFAULT NULL,
  `corrAddress` text DEFAULT NULL,
  `parentName` varchar(100) DEFAULT NULL,
  `parentContact` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_details`
--

INSERT INTO `student_details` (`studentID`, `dob`, `gender`, `icNo`, `homeAddress`, `corrAddress`, `parentName`, `parentContact`) VALUES
('23WP12509', '2004-03-13', 'Male', '90112-14-6203', 'No. 15, Jalan 2/14, Taman Melawati, 53100 Kuala Lumpur', '92, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Subramaniam A/L Govindasamy', '014-4425384'),
('23WP12926', '2004-09-28', 'Female', '80112-14-8405', 'Lot 452, Jalan Sultan Azlan Shah, 11700 Gelugor, Pulau Pinang', '35, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Kumar A/L Vijay', '012-1123228'),
('23WP14406', '2004-02-26', 'Female', '70112-14-2538', 'A-12-3, Platinum Victory, Setapak, 53300 Kuala Lumpur', '140, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Teoh Boon Hock', '012-2682335'),
('23WP20441', '2003-02-21', 'Female', '30112-14-6760', 'No. 8, Lorong Bukit Cetak, Section 17, 46400 Petaling Jaya', '117, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Mahadzir Bin Mansor', '011-9909620'),
('23WP20606', '2004-10-24', 'Male', '30112-14-1363', 'No. 22, Jalan Kerinchi, Bangsar South, 59200 Kuala Lumpur', '61, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Anuar Bin Hashim', '017-3546782'),
('23WP21572', '2003-10-23', 'Male', '80112-14-2572', '56, Jalan Molek 1/9, Taman Molek, 81100 Johor Bahru', '35, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Chandran A/L Murugan', '011-8328360'),
('23WP21706', '2004-05-19', 'Male', '100112-14-2240', 'No. 5, Jalan Radin Bagus, Sri Petaling, 57000 Kuala Lumpur', '53, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Muniandy A/L Perumal', '011-4894496'),
('23WP24179', '2004-01-20', 'Male', '50112-14-4460', 'Lot 101, Taman Pekan Baru, 08000 Sungai Petani, Kedah', '146, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Zakaria Bin Ibrahim', '012-9789284'),
('23WP24747', '2003-10-02', 'Male', '120112-14-2989', 'No. 14, Jalan Telawi 3, Bangsar Baru, 59100 Kuala Lumpur', '121, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Lee Ah Seng', '012-3368605'),
('23WP27426', '2004-10-04', 'Female', '70112-14-9787', 'C-05-09, Residensi Saville, Jalan Kajang, 43000 Selangor', '39, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Ravichandran A/L Muniandy', '014-2155556'),
('23WP27671', '2003-02-02', 'Male', '80112-14-9106', 'No. 19, Lorong Maarof, Bangsar Park, 59000 Kuala Lumpur', '96, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Sulaiman Bin Sidek', '016-1462985'),
('23WP31454', '2003-03-19', 'Female', '60112-14-8910', '38, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', '93, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Mansor Bin Mat', '014-8738069'),
('23WP32466', '2003-10-28', 'Male', '80112-14-6118', '12-A, Jalan Pinang, Georgetown, 10150 Pulau Pinang', '69, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Low Kah Hing', '016-5541279'),
('23WP34128', '2004-07-03', 'Male', '100112-14-6072', 'No. 3, Jalan USJ 11/4, Subang Jaya, 47620 Selangor', '101, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Razak Bin Daud', '013-6848142'),
('23WP34773', '2003-04-24', 'Male', '80112-14-3836', 'No. 88, Jalan Gasing, Section 10, 46000 Petaling Jaya', '1, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Chong Wei Kit', '016-3309841'),
('23WP37638', '2003-10-06', 'Female', '110112-14-9427', 'No. 21, Jalan Tun Razak, 50400 Kuala Lumpur', '50, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Sundar A/L Raman', '016-8420547'),
('23WP37849', '2003-09-06', 'Male', '40112-14-7740', 'No. 44, Taman Ipoh Jaya, 31350 Ipoh, Perak', '100, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Hassan Bin Ali', '012-9303547'),
('23WP38477', '2003-07-14', 'Female', '70112-14-1865', 'No. 10, Jalan Ampang Hilir, 55000 Kuala Lumpur', '118, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Tan Xin Hua', '018-9846402'),
('23WP40593', '2003-08-08', 'Female', '30112-14-1078', 'No. 5, Jalan Semarak, 54000 Kuala Lumpur', '143, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Low Kee Yat', '017-9816284'),
('23WP47754', '2004-01-14', 'Female', '120112-14-9628', 'Lot 8, Kampung Baru, 50300 Kuala Lumpur', '94, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Kamaruddin Bin Ahmad', '014-2678650'),
('23WP51727', '2004-12-18', 'Female', '90112-14-3618', 'No. 33, Jalan Ipoh, 51200 Kuala Lumpur', '117, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Hassan Bin Omar', '013-2333342'),
('23WP56143', '2004-10-16', 'Female', '80112-14-3120', 'No. 9, Lorong Seratus Tahun, 10400 Pulau Pinang', '44, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Yeoh Boon Heong', '013-4742956'),
('23WP57187', '2003-04-14', 'Female', '60112-14-2582', 'No. 12, Jalan Pandan Utama, 55100 Kuala Lumpur', '42, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Kelly Chan', '011-7135894'),
('23WP57467', '2003-06-28', 'Female', '30112-14-4326', 'No. 5, Jalan Metro Pudu, 55200 Kuala Lumpur', '86, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Selvam A/L Krishnan', '016-5721156'),
('23WP61618', '2004-10-03', 'Male', '90112-14-3719', 'No. 18, Jalan Kuchai Maju 1, 58200 Kuala Lumpur', '130, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Ng Kit Lye', '016-7082880'),
('23WP61839', '2003-01-04', 'Male', '70112-14-9847', 'No. 2, Jalan Kiara 3, Mont Kiara, 50480 Kuala Lumpur', '79, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Mohamad Bin Ariff', '015-2031272'),
('23WP69300', '2004-06-12', 'Female', '40112-14-5893', 'No. 77, Jalan Damai, 55000 Kuala Lumpur', '51, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Yusof Bin Ishak', '015-4996957'),
('23WP71365', '2004-06-26', 'Male', '90112-14-4686', 'No. 9, Jalan Puteri 1/4, Bandar Puteri, 47100 Puchong', '39, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Mani A/L Rajan', '011-3079578'),
('23WP73033', '2003-01-24', 'Male', '110112-14-8267', 'No. 3, Lorong Titiwangsa, 53200 Kuala Lumpur', '106, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Thapandion A/L Sanjay', '016-1948419'),
('23WP73789', '2004-09-16', 'Male', '30112-14-8557', 'No. 11, Jalan PJU 8/1, Damansara Perdana, 47820 Selangor', '60, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Wong Jun Kit', '014-1073834'),
('23WP77365', '2004-07-20', 'Male', '110112-14-8744', 'No. 4, Jalan Sultan Ismail, 50250 Kuala Lumpur', '37, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Lim Ah Huat', '019-8925647'),
('23WP78708', '2004-07-23', 'Female', '60112-14-7161', 'No. 20, Jalan SS21/37, Damansara Utama, 47400 Selangor', '136, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Tan Cheng Lock', '014-1529889'),
('23WP79311', '2003-12-04', 'Female', '60112-14-4003', 'No. 15, Jalan Perang, Taman Pelangi, 80400 Johor Bahru', '108, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Mokhtar Bin Dahari', '014-6587926'),
('23WP79793', '2003-05-17', 'Female', '110112-14-5360', 'No. 5, Lorong Keramat 5, 54000 Kuala Lumpur', '133, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Zulkifli Bin Ismail', '018-4774500'),
('23WP80803', '2004-01-04', 'Female', '90112-14-3982', 'No. 22, Jalan Genting Klang, Setapak, 53300 Kuala Lumpur', '127, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Wong Siew Kai', '019-1489060'),
('23WP82178', '2004-06-18', 'Female', '40112-14-2447', 'No. 8, Jalan Cochrane, 55100 Kuala Lumpur', '137, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Mohan A/L Gopal', '012-8601455'),
('23WP82670', '2004-11-19', 'Male', '70112-14-1988', 'No. 14, Jalan Kenari 5, Bandar Puchong Jaya, 47100 Selangor', '3, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Liew Chee Kit', '013-3724133'),
('23WP84917', '2004-08-27', 'Male', '100112-14-9524', 'Lot 99, Jalan Melati, 53100 Kuala Lumpur', '110, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Rosli Bin Jaafar', '011-3627415'),
('23WP94665', '2003-01-14', 'Male', '80112-14-6497', 'No. 1, Jalan Tunku, Bukit Tunku, 50480 Kuala Lumpur', '150, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Mohd Rizal Bin Abdullah', '018-4835810'),
('23WP96546', '2003-02-07', 'Female', '110112-14-2190', 'No. 55, Jalan Imbi, 55100 Kuala Lumpur', '30, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Abdullah Bin Ghani', '016-6007183'),
('23WP99001', '2005-03-15', 'Male', '050315-14-5678', 'No. 25, Jalan Bahagia, Taman Sejahtera, 52100 Kuala Lumpur', 'No. 25, Jalan Bahagia, Taman Sejahtera, 52100 Kuala Lumpur', 'Chen Wei Ming', '012-9998877');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `submissionID` int(11) NOT NULL,
  `assignmentID` int(11) NOT NULL,
  `studentID` varchar(15) NOT NULL,
  `filePath` varchar(255) NOT NULL,
  `submittedAt` datetime DEFAULT current_timestamp(),
  `grade` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `status` enum('Submitted','Graded','Late') DEFAULT 'Submitted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`submissionID`, `assignmentID`, `studentID`, `filePath`, `submittedAt`, `grade`, `feedback`, `status`) VALUES
(1, 1, '23WP12509', '1769883132_23WP12509_MOCK TEST.txt', '2026-02-01 02:12:12', 79, '', 'Graded'),
(2, 3, '23WP12509', '1770216663_23WP12509_1769883132_23WP12509_MOCK TEST.txt', '2026-02-04 22:51:03', 89, 'Good Job', 'Graded'),
(3, 2, '23WP12509', '1770556404_23WP12509_Chua Jian Xi_Week 2.pdf', '2026-02-08 21:13:24', NULL, NULL, 'Late'),
(4, 5, '23WP12509', '1771945023_23WP12509_1769883132_23WP12509_MOCK TEST.txt', '2026-02-24 22:57:03', 10, 'What is this!!!', 'Graded');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userID` varchar(12) NOT NULL,
  `role` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userID`, `role`, `password`) VALUES
('U001', 'Admin', '$2y$10$mB57yiLGHxATWCZ.0b9iO.yxKd0MMtVNUbuW0C2yDP1mBavKD.B.2'),
('U002', 'Staff', '$2y$10$M9WuJnLKHH90zHq3ojC.heBJyKMNnmTNwcKAqkXoH19dYjhfK1CbK'),
('U003', 'Staff', '$2y$10$3OOiDN3XGGb5LDqNWE3TN.QcMtB7e3BqOGd96j8eI7AjuQiAhU2xa'),
('U004', 'Staff', '$2y$10$kNCULBVuJL5cRXvRgspBau0pyTtO2wR/b7e98IaOvOauIafjnsB76'),
('U005', 'Staff', '$2y$10$x1Zzb3EwPFIPrwmB739md.F/SanUglPJ6b7TQ3N7YaADm.udzfcde'),
('U006', 'Staff', '$2y$10$SS5C4GL13YyUWUdVHzb6bOuvLLSl9Xrv/KAga1EaJnwsS.EG4OIya'),
('U007', 'Staff', '$2y$10$w/J7zNK2FDtm5VVmtCoMnul16UXuASl8JzaAvMbhuj02CNsxhgf9y'),
('U008', 'Staff', '$2y$10$zgAUTtW4QOhYvdsJZ56Dbu3KA4YliqiKpkTM7KIy34IPMFCGT4KHy'),
('U009', 'Staff', '$2y$10$eLT1VubWcSNm4RulT2IAdOWRYZhSMLTmQIKbYeT.BHpYZ3.G1CMIy'),
('U010', 'Staff', '$2y$10$MJjkNvhJ9CzRpQlg1Au6MOOC0BOvheBM/Hccht3awGrQqAo/0rLUi'),
('U011', 'Staff', '$2y$10$c8m6ZamqmAFRDXpsqt..JOY0cwW040GGDHPiNWvwQb3FVnMY9IGfm'),
('U012', 'Staff', '$2y$10$vbAkFdbSQciG2OhSK36U.e6ynmhA4lmM.srfn1DbUdbgs7lSC0NFu'),
('U013', 'Student', '$2y$10$OSziQF4qzYqegGP7zm9xQ.aiHK4vmebFMHLTd0md2iWKl.F//cbOK'),
('U014', 'Student', '$2y$10$nFsfh/uMe9W4ubbzjbI.VucAb89WszzZgC6GjhrvaUdTK8Y0z6mg6'),
('U015', 'Student', '$2y$10$lmmCtqTFcdIdMxYD7TAr1.rY/bZqm9kr9ZFX3wSSEW9bSlbCSB4mi'),
('U016', 'Student', '$2y$10$HDVZ4QmXOXJ1grKqpJtaaOfMbrOmHlE4sN6s4ESrAbhC/R5Ubqhqm'),
('U017', 'Student', '$2y$10$yyrsNq76siBvrqxA04HRuOmgH9cfn4ltPDKRhX8LeVPJaTgPbJUse'),
('U018', 'Student', '$2y$10$dByUpxgOEXCKbRWliVZvGeq7Mu33CEBcLXz3/24vnjn009sk7G3Sq'),
('U019', 'Student', '$2y$10$vZGYtI3u8IWTcjbkjGiCEengVtOLgvOnuLFdUfVI07oOYLSlfq5oK'),
('U020', 'Student', '$2y$10$PNnrceC2Uwm8VTh96ORL6udo5OXV8OzVH2z7ATWeJ/lx/kJzAnalG'),
('U021', 'Student', '$2y$10$17AqJ9X1dJjqI5Pz7..jhujh7Lyge.nqlOxgJWa5v0CrbbLlwP9Ne'),
('U022', 'Student', '$2y$10$e4Zjuls7sVtMT/hHnl8VIe2/PpPP7LjIQQ48LK3ABkgXdMKt8Uuka'),
('U023', 'Student', '$2y$10$dm8.bFXYVwuD99MLdIPMzeyYZd2/lYtFWmUJL5csLiM//ypYaPppe'),
('U024', 'Student', '$2y$10$G49pvGFmpjgfeH5ghWz3BOxQATbSB5HnsxAXux7ZY/Nqujj1V72Nu'),
('U025', 'Student', '$2y$10$nR1PCECmrW3bB9c3TFNACuK4HYPyR9EW5qAPPlvXuk78kH3gUrWEq'),
('U026', 'Student', '$2y$10$iJF/L1BUdXkeCiLvEumJjuL01CRZszGd4JdI0kf94jOgyxu0D1qOG'),
('U027', 'Student', '$2y$10$VOHmYrDvwMs3vbqkEmk9uucbq0S1bMg3IKhwXED/WLIRcFwpoZuwu'),
('U028', 'Student', '$2y$10$xibcbYpsvuVijxjl3DWp4u5/8l6o9fc/a0s5dn6tFYvj.BA26eOCG'),
('U029', 'Student', '$2y$10$kWbhneT64akgGkdu1VekP.dnNnzcxkohImY45iWr8rDOLoK9S3i6i'),
('U030', 'Student', '$2y$10$U10MPlhV1b7V3l8CWo43guA1.TVarpaM5eThEfCm/SYuyXgzBu/mW'),
('U031', 'Student', '$2y$10$KWJctMHSEFBYtIT2GWPeVO6clcQcdWEP81zBbBh8q4bEvfpSKHvGe'),
('U032', 'Student', '$2y$10$lHHUmlNoyUBtrLvTF4Zff.xd/DiS07vlyjB.8V.IwaNNynbc3GeNC'),
('U033', 'Student', '$2y$10$4Kf88LJHu8p2Fq9flWYzjeJm92y0w45NFXrZQv4I/PLsscWv2dyoK'),
('U034', 'Student', '$2y$10$ZbFaEeZ8qxlvVWcK9.fZZuSCCfuRT1KctDnnRphzJfIjpO8JpWj8a'),
('U035', 'Student', '$2y$10$AEomO03jvGxZIAFx/xLg8ukEuTb22PQfZ00XyWNe2Y.otptVCiB0a'),
('U036', 'Student', '$2y$10$sfob4t4dGVc7pXjiIygCxubasQ3so2eiH4FYBbJRF3jjWtwNkPdDK'),
('U037', 'Student', '$2y$10$40Kf2n7.O8RfCGsnRy782OF8exmTVL81UTX2CiwDnvkJ3tBvbQom6'),
('U038', 'Student', '$2y$10$a2mCIvFrRLBUCUbXCG8a2eJCDCaJ6Pqc52Bv/GHxmh7VV2z4AvCGO'),
('U039', 'Student', '$2y$10$8.w5hRZBR3aXAi90O9LAO.CG3tTwuXMzCWLAThenFYbdnkBCDDq/O'),
('U040', 'Student', '$2y$10$Ode5ez5DUD9XbC2irVWqEuQDezAnpnukr1tlfMuoag6ZpiHKXC6BO'),
('U041', 'Student', '$2y$10$aDjuoqBqEoDYpX9pcANBNuIMw1fa77aUfkz75LQXrSrTCgWwOWc9C'),
('U042', 'Student', '$2y$10$/BHSDkjyuh4CJmGv6c6W1.dQUFgvBVrLKWMxaWM8P5s/Otv8Y7.ku'),
('U043', 'Student', '$2y$10$PjyPlGPzKbW7Jg.MV0fTX.aXrYxSxT.sC5pB40J.iBw2z0U6NotEu'),
('U044', 'Student', '$2y$10$oHWS/t6GFW7hSmztwLnXlufwguEzVifYrEawvctDBlzaKudtv4c1a'),
('U045', 'Student', '$2y$10$r/Xc1CttWq2xVvcxhxB5..xC1oV2uE9f7zj9tLJcSv4G1yPIR/OEC'),
('U046', 'Student', '$2y$10$kbBwJzKUpM7f2A7FQBrkoumUH4dqoMXP2BSbTsAKJBl5HfP1js026'),
('U047', 'Student', '$2y$10$MMlBnFjctafbBiBHOKkhjuhldn0Bw1LIcWevY8Wj/BYS7BnvnY8mS'),
('U048', 'Student', '$2y$10$ghvkVuAUk92vzSoxCEKD1ODrBISdd8r0PicnhAiswjERZFLCWtxwm'),
('U049', 'Student', '$2y$10$JTTYD703faj5AWLZpk.0vOHlxxm7c2U0StbQ8hN4Jjl.Jcl0FsBbW'),
('U050', 'Student', '$2y$10$5wyMwTPpLzaME/YEButPl.qIVJTO80JEDUP0LWjWeSAoeFE3Lec82'),
('U051', 'Student', '$2y$10$IIq2EqNHDorYPAFahn5Hfed.RbPyngZRIBD3.YGthd7M8mArpZXXS'),
('U052', 'Student', '$2y$10$c0xgfRyK0dLe2K78T1xlYO9trlEBTo2nTBY2V6Uk4dihmVc4ZFW26'),
('U053', 'Staff', '$2y$10$uI.0nyIODyYCbQx5blzIJ.pRc8NuHkNRSPUPEHfHzjGW1DtlWfAkO'),
('U054', 'Staff', '$2y$10$IbaubCaQfDKzPPaeErEYVOpOtgXk8Y6l6J3w213IIVL7D5RIIsfDi'),
('U055', 'Staff', '$2y$10$HBe7cV/n5A4NplVr0Raj/OUd5rShA1myY5rB7c.TeXHw7yOpXRqp6'),
('U056', 'Staff', '$2y$10$v4cXA.KtkiIPOm/I.FYiretPVzKbmPLBx0UD79pLeWsPY7lNN9yfi'),
('U057', 'Student', '$2y$10$FdeUgXn/qAGbSVkkN3IgEugpwkk2Ogvd7ojkN2e1qu.CYGCb3aV0m'),
('U058', 'Admin', '$2y$10$x3kAq.v4mNW7ysppQ6Bzj./3LeChA6ZosQFAzqj3dldG0ZmQjrcvW'),
('U059', 'Student', '$2y$10$UOHk2zzhKVkcwNP3GmTf2e6ruKEO0hVTBc8I7zCo3u2PwweWVEDcy'),
('U060', 'Student', '$2y$10$AgXoXxDVwofBCildA3M2a.CrYqBu71twvXOR9T3oAqKmZ1xBQhhU2');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_term`
--
ALTER TABLE `academic_term`
  ADD PRIMARY KEY (`termID`),
  ADD UNIQUE KEY `unique_term` (`programID`,`year`,`semester`,`academicYear`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`logID`),
  ADD KEY `idx_user` (`userID`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_activity` (`activity_type`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`adminID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`assignmentID`),
  ADD KEY `courseID` (`courseID`),
  ADD KEY `staffID` (`staffID`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`recordID`),
  ADD KEY `studentID` (`studentID`),
  ADD KEY `scheduleID` (`scheduleID`);

--
-- Indexes for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD PRIMARY KEY (`sessionID`),
  ADD KEY `scheduleID` (`scheduleID`);

--
-- Indexes for table `booking_rules`
--
ALTER TABLE `booking_rules`
  ADD PRIMARY KEY (`ruleID`),
  ADD UNIQUE KEY `facilityType` (`facilityType`);

--
-- Indexes for table `class_group_removals`
--
ALTER TABLE `class_group_removals`
  ADD PRIMARY KEY (`removalID`),
  ADD KEY `studentID` (`studentID`),
  ADD KEY `programID` (`programID`),
  ADD KEY `tutGroup` (`tutGroup`);

--
-- Indexes for table `class_schedule`
--
ALTER TABLE `class_schedule`
  ADD PRIMARY KEY (`scheduleID`),
  ADD KEY `fk_cs_program` (`programID`),
  ADD KEY `fk_cs_course` (`courseID`),
  ADD KEY `fk_cs_staff` (`staffID`),
  ADD KEY `termID` (`termID`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`courseID`);

--
-- Indexes for table `course_offering`
--
ALTER TABLE `course_offering`
  ADD PRIMARY KEY (`offeringID`),
  ADD UNIQUE KEY `courseID` (`courseID`,`programID`,`termID`,`sectionNo`),
  ADD KEY `programID` (`programID`),
  ADD KEY `termID` (`termID`);

--
-- Indexes for table `course_registration`
--
ALTER TABLE `course_registration`
  ADD PRIMARY KEY (`registrationID`),
  ADD UNIQUE KEY `studentID` (`studentID`,`offeringID`),
  ADD KEY `offeringID` (`offeringID`);

--
-- Indexes for table `facility`
--
ALTER TABLE `facility`
  ADD PRIMARY KEY (`facilityID`);

--
-- Indexes for table `facility_booking`
--
ALTER TABLE `facility_booking`
  ADD PRIMARY KEY (`bookingID`),
  ADD KEY `idx_facility_date` (`facilityID`,`bookingDate`),
  ADD KEY `idx_user` (`userID`,`userRole`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `program`
--
ALTER TABLE `program`
  ADD PRIMARY KEY (`programID`);

--
-- Indexes for table `program_course`
--
ALTER TABLE `program_course`
  ADD PRIMARY KEY (`programID`,`courseID`),
  ADD KEY `fk_pc_course` (`courseID`);

--
-- Indexes for table `replacement_request`
--
ALTER TABLE `replacement_request`
  ADD PRIMARY KEY (`replacementID`),
  ADD KEY `scheduleID` (`scheduleID`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staffID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`studentID`),
  ADD UNIQUE KEY `userID` (`userID`),
  ADD KEY `programme` (`programID`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`submissionID`),
  ADD KEY `assignmentID` (`assignmentID`),
  ADD KEY `studentID` (`studentID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_term`
--
ALTER TABLE `academic_term`
  MODIFY `termID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `logID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=671;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `assignmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `recordID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=491;

--
-- AUTO_INCREMENT for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  MODIFY `sessionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=166;

--
-- AUTO_INCREMENT for table `booking_rules`
--
ALTER TABLE `booking_rules`
  MODIFY `ruleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `class_group_removals`
--
ALTER TABLE `class_group_removals`
  MODIFY `removalID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `class_schedule`
--
ALTER TABLE `class_schedule`
  MODIFY `scheduleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `course_offering`
--
ALTER TABLE `course_offering`
  MODIFY `offeringID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `course_registration`
--
ALTER TABLE `course_registration`
  MODIFY `registrationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `facility_booking`
--
ALTER TABLE `facility_booking`
  MODIFY `bookingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `replacement_request`
--
ALTER TABLE `replacement_request`
  MODIFY `replacementID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `submissionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_term`
--
ALTER TABLE `academic_term`
  ADD CONSTRAINT `academic_term_ibfk_1` FOREIGN KEY (`programID`) REFERENCES `program` (`programID`) ON DELETE CASCADE;

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_fk_user` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`courseID`) REFERENCES `course` (`courseID`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`staffID`) REFERENCES `staff` (`staffID`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`studentID`) REFERENCES `student` (`studentID`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`scheduleID`) REFERENCES `class_schedule` (`scheduleID`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD CONSTRAINT `attendance_sessions_ibfk_1` FOREIGN KEY (`scheduleID`) REFERENCES `class_schedule` (`scheduleID`) ON DELETE CASCADE;

--
-- Constraints for table `class_schedule`
--
ALTER TABLE `class_schedule`
  ADD CONSTRAINT `class_schedule_ibfk_1` FOREIGN KEY (`termID`) REFERENCES `academic_term` (`termID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cs_course` FOREIGN KEY (`courseID`) REFERENCES `course` (`courseID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cs_program` FOREIGN KEY (`programID`) REFERENCES `program` (`programID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cs_staff` FOREIGN KEY (`staffID`) REFERENCES `staff` (`staffID`) ON DELETE CASCADE;

--
-- Constraints for table `course_offering`
--
ALTER TABLE `course_offering`
  ADD CONSTRAINT `course_offering_ibfk_1` FOREIGN KEY (`courseID`) REFERENCES `course` (`courseID`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_offering_ibfk_2` FOREIGN KEY (`programID`) REFERENCES `program` (`programID`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_offering_ibfk_3` FOREIGN KEY (`termID`) REFERENCES `academic_term` (`termID`) ON DELETE CASCADE;

--
-- Constraints for table `course_registration`
--
ALTER TABLE `course_registration`
  ADD CONSTRAINT `course_registration_ibfk_1` FOREIGN KEY (`studentID`) REFERENCES `student` (`studentID`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_registration_ibfk_2` FOREIGN KEY (`offeringID`) REFERENCES `course_offering` (`offeringID`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `program_course`
--
ALTER TABLE `program_course`
  ADD CONSTRAINT `fk_pc_course` FOREIGN KEY (`courseID`) REFERENCES `course` (`courseID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pc_program` FOREIGN KEY (`programID`) REFERENCES `program` (`programID`) ON DELETE CASCADE;

--
-- Constraints for table `replacement_request`
--
ALTER TABLE `replacement_request`
  ADD CONSTRAINT `replacement_request_ibfk_1` FOREIGN KEY (`scheduleID`) REFERENCES `class_schedule` (`scheduleID`) ON DELETE CASCADE;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_fk_user` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `fk_student_program` FOREIGN KEY (`programID`) REFERENCES `program` (`programID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_student_user` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignmentID`) REFERENCES `assignments` (`assignmentID`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`studentID`) REFERENCES `student` (`studentID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
