-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 21, 2026 at 04:17 PM
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
(1, 'Welcome back!', 'Classes resume next Monday. Please check your schedule.', '2025-11-10 13:15:47'),
(2, 'Maintenance window', 'Portal downtime tonight from 11 PM to 12 AM for updates.', '2025-11-10 13:15:47');

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `courseID` varchar(12) NOT NULL,
  `programID` varchar(12) DEFAULT NULL,
  `courseName` varchar(100) NOT NULL,
  `creditHours` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`courseID`, `programID`, `courseName`, `creditHours`) VALUES
('BACS1013', 'RSD', 'Problem Solving and Programming', 3),
('BACS1024', 'RSD', 'Introduction to Computer Systems', 4),
('BACS1053', 'RSD', 'Database Management', 3),
('BACS2023', 'RSD', 'Object-Oriented Programming', 3),
('BACS2042', 'RSD', 'Research Methods', 2),
('BACS2053', 'RSD', 'Object-Oriented Analysis and Design', 3),
('BACS2063', 'RSD', 'Data Structures and Algorithms', 3),
('BACS2163', 'RSD', 'Software Engineering', 3),
('BAIT1023', 'RSD', 'Web Design and Development', 3),
('BAIT1043', 'RSD', 'Systems Analysis and Design', 3),
('BAIT1173', 'RSD', 'IT Fundamentals', 3),
('BAIT2004', 'RSD', 'Fundamentals of Computer Networks', 3),
('BAIT2023', 'RSD', 'Introduction to Internet Security', 3),
('BAIT2073', 'RSD', 'Mobile Application Development', 3),
('BAIT2113', 'RSD', 'Web Application Development', 3),
('BAIT2203', 'RSD', 'Human Computer Interaction', 3),
('BAMS1613', 'RSD', 'Probability and Statistics', 3),
('BAMS1623', 'RSD', 'Discrete Mathematics', 3),
('BJEL1713', 'RSD', 'English for Tertiary Studies', 3),
('BJEL1723', 'RSD', 'Academic English', 3),
('BJEL2013', 'RSD', 'English for Career Preparation', 3),
('BMCS3033', 'RSD', 'Social and Professional Issues', 3),
('BMCS3403', 'RSD', 'Project I', 3),
('BMCS3413', 'RSD', 'Project II', 3),
('BMIT2154', 'RSD', 'Switching and Routing Technologies', 4),
('BMIT305C', 'RSD', 'Industrial Training', 12),
('BMIT3084', 'RSD', 'Enterprise Networking', 4),
('BMIT3173', 'RSD', 'Integrative Programming', 3),
('BMIT3273', 'RSD', 'Cloud Computing', 3),
('ECOQ', 'RSD', 'Co-Curricular', 2),
('EG01', 'RSD', 'Elective Course (Group 1)', 3),
('EG02', 'RSD', 'Elective Course (Group 2)', 3),
('EG03', 'RSD', 'Elective Course (Group 3)', 3),
('EG04', 'RSD', 'Elective Course (Group 4)', 3),
('EGU2', 'RSD', 'Elective Course (University)', 2),
('MPU-3103', 'RSD', 'Penghayatan Etika Dan Peradaban', 3),
('MPU-3133', 'RSD', 'Falsafah Dan Isu Semasa', 3),
('MPU-3302', 'RSD', 'Integrity and Anti-Corruption', 2);

-- --------------------------------------------------------

--
-- Table structure for table `program`
--

CREATE TABLE `program` (
  `programID` varchar(12) NOT NULL,
  `programName` varchar(100) NOT NULL,
  `duration` int(11) NOT NULL,
  `faculty` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program`
--

INSERT INTO `program` (`programID`, `programName`, `duration`, `faculty`) VALUES
('RSD', 'Bachelor of Information Technology (Honours) in Software Systems Development', 3, 'FOCS'),
('RSW', 'Bachelor of Software Engineering (Honours)', 3, 'FOCS');

-- --------------------------------------------------------

--
-- Table structure for table `program_course`
--

CREATE TABLE `program_course` (
  `id` int(11) NOT NULL,
  `programID` varchar(12) NOT NULL,
  `courseID` varchar(12) NOT NULL,
  `year` int(1) NOT NULL,
  `semester` int(1) NOT NULL,
  `type` varchar(20) DEFAULT 'Core'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_course`
--

INSERT INTO `program_course` (`id`, `programID`, `courseID`, `year`, `semester`, `type`) VALUES
(1, 'RSD', 'BACS1013', 1, 1, 'Core'),
(2, 'RSD', 'BJEL1713', 1, 1, 'Mpu'),
(3, 'RSD', 'MPU-3103', 1, 1, 'Mpu'),
(4, 'RSD', 'BACS1053', 1, 2, 'Core'),
(5, 'RSD', 'BAIT1023', 1, 2, 'Core'),
(6, 'RSD', 'BAIT1043', 1, 2, 'Core'),
(7, 'RSD', 'BAIT1173', 1, 2, 'Core'),
(8, 'RSD', 'BAMS1613', 1, 2, 'Core'),
(9, 'RSD', 'MPU-3302', 1, 2, 'Mpu'),
(10, 'RSD', 'BACS2023', 1, 3, 'Core'),
(11, 'RSD', 'BAIT2203', 1, 3, 'Core'),
(12, 'RSD', 'BAIT2004', 1, 3, 'Core'),
(13, 'RSD', 'BACS1024', 1, 3, 'Core'),
(14, 'RSD', 'BAMS1623', 1, 3, 'Core'),
(15, 'RSD', 'BJEL1723', 1, 3, 'Mpu'),
(16, 'RSD', 'ECOQ', 2, 1, 'Mpu'),
(17, 'RSD', 'BACS2063', 2, 1, 'Core'),
(18, 'RSD', 'BACS2163', 2, 1, 'Core'),
(19, 'RSD', 'BAIT2113', 2, 2, 'Core'),
(20, 'RSD', 'BAIT2073', 2, 2, 'Core'),
(21, 'RSD', 'BACS2053', 2, 2, 'Core'),
(22, 'RSD', 'BACS2042', 2, 2, 'Core'),
(23, 'RSD', 'BAIT2023', 2, 2, 'Core'),
(24, 'RSD', 'BMIT2154', 2, 2, 'Core'),
(25, 'RSD', 'BMIT3173', 2, 3, 'Core'),
(26, 'RSD', 'EG01', 2, 3, 'Elective'),
(27, 'RSD', 'BMIT3084', 2, 3, 'Core'),
(28, 'RSD', 'BJEL2013', 2, 3, 'Mpu'),
(29, 'RSD', 'MPU-3133', 2, 3, 'Mpu'),
(30, 'RSD', 'BMCS3403', 3, 1, 'Core'),
(31, 'RSD', 'EG02', 3, 1, 'Elective'),
(32, 'RSD', 'EGU2', 3, 1, 'Elective'),
(33, 'RSD', 'BMCS3413', 3, 2, 'Core'),
(34, 'RSD', 'BMCS3033', 3, 2, 'Core'),
(35, 'RSD', 'BMIT3273', 3, 2, 'Core'),
(36, 'RSD', 'EG03', 3, 2, 'Elective'),
(37, 'RSD', 'EG04', 3, 2, 'Elective'),
(38, 'RSD', 'BMIT305C', 3, 3, 'Core');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `studentID` varchar(12) NOT NULL,
  `userID` varchar(12) DEFAULT NULL,
  `studentName` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contactNo` varchar(15) DEFAULT NULL,
  `tutGroup` varchar(10) DEFAULT NULL,
  `programme` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`studentID`, `userID`, `studentName`, `email`, `contactNo`, `tutGroup`, `programme`) VALUES
('23WP12509', '23WP12509', 'Arvind A/L Subramaniam', 'arvind.12509@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP12926', '23WP12926', 'Divya A/P Kumar', 'divya.12926@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP14406', '23WP14406', 'Teoh Kah Mun', 'teoh.14406@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP20441', '23WP20441', 'Puteri Balqis Binti Mahadzir', 'puteri.20441@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP20606', '23WP20606', 'Karthik A/L Anuar', 'karthik.20606@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP21572', '23WP21572', 'Ravi A/L Chandran', 'ravi.21572@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP21706', '23WP21706', 'Sanjay A/L Muniandy', 'sanjay.21706@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP24179', '23WP24179', 'Mohammad Faizal Bin Zakaria', 'mohammad.24179@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP24747', '23WP24747', 'Lee Kian Seng', 'lee.24747@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP27426', '23WP27426', 'Priya A/P Ravichandran', 'priya.27426@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP27671', '23WP27671', 'Azman Bin Sulaiman', 'azman.27671@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP31454', '23WP31454', 'Syafiqah Binti Mansor', 'syafiqah.31454@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP32466', '23WP32466', 'Jason Low Kah Hing', 'jason.32466@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP34128', '23WP34128', 'Mohammad Haziq Bin Razak', 'mohammad.34128@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP34773', '23WP34773', 'Chong Wei Liang', 'chong.34773@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP37638', '23WP37638', 'Preeti A/P Sundar', 'preeti.37638@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP37849', '23WP37849', 'Khairul Azmi Bin Hassan', 'khairul.37849@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP38477', '23WP38477', 'Michelle Tan Xin Yi', 'michelle.38477@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP40593', '23WP40593', 'Low Yee Wen', 'low.40593@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP47754', '23WP47754', 'Nurul Izzah Binti Kamaruddin', 'nurul.47754@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP51727', '23WP51727', 'Siti Aminah Binti Hassan', 'siti.51727@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP56143', '23WP56143', 'Yeoh Zi Yi', 'yeoh.56143@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP57187', '23WP57187', 'Kelly Chan Pui Yi', 'kelly.57187@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP57467', '23WP57467', 'Thivya A/P Selvam', 'thivya.57467@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP61618', '23WP61618', 'Ng See Kiat', 'ng.61618@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP61839', '23WP61839', 'Zulhilmi Bin Mohamad', 'zulhilmi.61839@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP69300', '23WP69300', 'Farah Nabilah Binti Yusof', 'farah.69300@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP71365', '23WP71365', 'Vikneswaran A/L Mani', 'vikneswaran.71365@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP73033', '23WP73033', 'Ganesh A/L Thapandion', 'ganesh.73033@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP73789', '23WP73789', 'Bryan Wong Jun Kit', 'bryan.73789@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP77365', '23WP77365', 'Lim Jia Hao', 'lim.77365@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP78708', '23WP78708', 'Tan Mei Ling', 'tan.78708@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP79311', '23WP79311', 'Siti Sarah Binti Mokhtar', 'siti.79311@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP79793', '23WP79793', 'Nurul Ain Binti Zulkifli', 'nurul.79793@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP80803', '23WP80803', 'Wong Siew Fen', 'wong.80803@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP82178', '23WP82178', 'Anjali A/P Mohan', 'anjali.82178@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP82670', '23WP82670', 'Liew Chee Keong', 'liew.82670@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP84917', '23WP84917', 'Ahmad Zaki Bin Rosli', 'ahmad.84917@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP94665', '23WP94665', 'Ahmad Syahmi Bin Mohd Rizal', 'ahmad.94665@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP96546', '23WP96546', 'Noraini Binti Abdullah', 'noraini.96546@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering');

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
('23WP96546', '2003-02-07', 'Female', '110112-14-2190', 'No. 55, Jalan Imbi, 55100 Kuala Lumpur', '30, Jalan Ampang, Utama Heights, 50450 Kuala Lumpur, Malaysia', 'Abdullah Bin Ghani', '016-6007183');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userID` varchar(12) NOT NULL,
  `role` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userID`, `role`) VALUES
('23WP12509', 'Student'),
('23WP12926', 'Student'),
('23WP14406', 'Student'),
('23WP20441', 'Student'),
('23WP20606', 'Student'),
('23WP21572', 'Student'),
('23WP21706', 'Student'),
('23WP24179', 'Student'),
('23WP24747', 'Student'),
('23WP27426', 'Student'),
('23WP27671', 'Student'),
('23WP31454', 'Student'),
('23WP32466', 'Student'),
('23WP34128', 'Student'),
('23WP34773', 'Student'),
('23WP37638', 'Student'),
('23WP37849', 'Student'),
('23WP38477', 'Student'),
('23WP40593', 'Student'),
('23WP47754', 'Student'),
('23WP51727', 'Student'),
('23WP56143', 'Student'),
('23WP57187', 'Student'),
('23WP57467', 'Student'),
('23WP61618', 'Student'),
('23WP61839', 'Student'),
('23WP69300', 'Student'),
('23WP71365', 'Student'),
('23WP73033', 'Student'),
('23WP73789', 'Student'),
('23WP77365', 'Student'),
('23WP78708', 'Student'),
('23WP79311', 'Student'),
('23WP79793', 'Student'),
('23WP80803', 'Student'),
('23WP82178', 'Student'),
('23WP82670', 'Student'),
('23WP84917', 'Student'),
('23WP94665', 'Student'),
('23WP96546', 'Student');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`courseID`),
  ADD KEY `programID` (`programID`);

--
-- Indexes for table `program`
--
ALTER TABLE `program`
  ADD PRIMARY KEY (`programID`);

--
-- Indexes for table `program_course`
--
ALTER TABLE `program_course`
  ADD PRIMARY KEY (`id`),
  ADD KEY `programID` (`programID`),
  ADD KEY `courseID` (`courseID`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`studentID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `student_details`
--
ALTER TABLE `student_details`
  ADD PRIMARY KEY (`studentID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `program_course`
--
ALTER TABLE `program_course`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `course_ibfk_1` FOREIGN KEY (`programID`) REFERENCES `program` (`programID`);

--
-- Constraints for table `program_course`
--
ALTER TABLE `program_course`
  ADD CONSTRAINT `program_course_ibfk_1` FOREIGN KEY (`programID`) REFERENCES `program` (`programID`),
  ADD CONSTRAINT `program_course_ibfk_2` FOREIGN KEY (`courseID`) REFERENCES `course` (`courseID`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`);

--
-- Constraints for table `student_details`
--
ALTER TABLE `student_details`
  ADD CONSTRAINT `student_details_ibfk_1` FOREIGN KEY (`studentID`) REFERENCES `student` (`studentID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
