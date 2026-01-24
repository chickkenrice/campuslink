-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 24, 2026 at 05:03 PM
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
('ADM01', 'U001', 'Super Admin', 'admin@tarc.edu.my', '012-3456789');

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

-- --------------------------------------------------------

--
-- Table structure for table `class_schedule`
--

CREATE TABLE `class_schedule` (
  `scheduleID` int(11) NOT NULL,
  `programID` varchar(10) NOT NULL,
  `courseID` varchar(12) NOT NULL,
  `tutGroup` varchar(20) NOT NULL,
  `staffID` varchar(10) NOT NULL,
  `facilityID` varchar(12) NOT NULL,
  `day` varchar(10) NOT NULL,
  `startTime` time NOT NULL,
  `endTime` time NOT NULL,
  `classType` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_schedule`
--

INSERT INTO `class_schedule` (`scheduleID`, `programID`, `courseID`, `tutGroup`, `staffID`, `facilityID`, `day`, `startTime`, `endTime`, `classType`) VALUES
(3, 'RSD', 'BACS1013', 'Group A', 'S010', 'DK-B', 'Thursday', '08:00:00', '10:00:00', 'Lecture'),
(4, 'RSD', 'BACS1013', 'Group A', 'S010', 'LAB-A01', 'Thursday', '10:00:00', '11:00:00', 'Practical'),
(21, 'RSW', 'BACS1013', 'Group B', 'S010', 'DK-C', 'Monday', '08:00:00', '10:00:00', 'Lecture'),
(22, 'RSW', 'BACS1013', 'Group B', 'S010', 'LAB-A02', 'Monday', '10:00:00', '11:00:00', 'Practical'),
(23, 'RSW', 'BJEL1713', 'Group B', 'S001', 'DK-C', 'Tuesday', '08:00:00', '10:00:00', 'Lecture'),
(24, 'RSW', 'BJEL1713', 'Group B', 'S001', 'TR-101', 'Tuesday', '10:00:00', '11:00:00', 'Tutorial'),
(25, 'RSW', 'MPU3103', 'Group B', 'S007', 'DK-C', 'Wednesday', '08:00:00', '10:00:00', 'Lecture'),
(26, 'RSW', 'MPU3103', 'Group B', 'S007', 'TR-101', 'Wednesday', '10:00:00', '11:00:00', 'Tutorial'),
(27, 'RSD', 'BJEL1713', 'Group A', 'S001', 'DK-B', 'Friday', '08:00:00', '10:00:00', 'Lecture'),
(28, 'RSD', 'BJEL1713', 'Group A', 'S001', 'TR-101', 'Friday', '10:00:00', '11:00:00', 'Tutorial'),
(29, 'RSD', 'MPU3103', 'Group A', 'S007', 'DK-C', 'Monday', '14:00:00', '16:00:00', 'Lecture'),
(30, 'RSD', 'MPU3103', 'Group A', 'S007', 'TR-102', 'Monday', '16:00:00', '17:00:00', 'Tutorial');

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
('BMIT2154', 'Switching and Routing Technologies', 4),
('BMIT305C', 'Industrial Training', 12),
('BMIT3084', 'Enterprise Networking', 4),
('BMIT3173', 'Integrative Programming', 3),
('BMIT3273', 'Cloud Computing', 3),
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
('DK-A', 'Lecture Hall A', 'Hall', 'Block A, Gnd', 200, 'Active'),
('DK-B', 'Lecture Hall B', 'Hall', 'Block B, Gnd', 150, 'Active'),
('DK-C', 'Lecture Hall C', 'Hall', 'Block B, Gnd', 150, 'Active'),
('LAB-A01', 'Computer Lab 1', 'Lab', 'Block A, Lvl 2', 30, 'Active'),
('LAB-A02', 'Software Eng. Lab', 'Lab', 'Block A, Lvl 2', 40, 'Active'),
('LAB-B01', 'Networking Lab', 'Lab', 'Block B, Lvl 1', 35, 'Active'),
('LAB-B02', 'Multimedia Lab', 'Lab', 'Block B, Lvl 1', 35, 'Active'),
('LAB-C01', 'General IT Lab', 'Lab', 'Block C, Lvl 3', 60, 'Active'),
('TR-101', 'Tutorial Room 101', 'Tutorial', 'Block C, Lvl 1', 30, 'Active'),
('TR-102', 'Tutorial Room 102', 'Tutorial', 'Block C, Lvl 1', 30, 'Active');

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
('RSD', 'BMCS3033', 'Main', 3, 2),
('RSD', 'BMCS3403', 'Main', 3, 1),
('RSD', 'BMCS3413', 'Main', 3, 2),
('RSD', 'BMIT2154', 'Main', 2, 2),
('RSD', 'BMIT305C', 'Main', 3, 3),
('RSD', 'BMIT3084', 'Main', 2, 3),
('RSD', 'BMIT3173', 'Main', 2, 3),
('RSD', 'BMIT3273', 'Main', 3, 2),
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
  `newDate` date NOT NULL,
  `newTime` varchar(50) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `programID` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`studentID`, `userID`, `studentName`, `email`, `contactNo`, `tutGroup`, `programID`) VALUES
('23WP12509', 'U013', 'Arvind A/L Subramaniam', 'arvind.12509@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP12926', 'U014', 'Divya A/P Kumar', 'divya.12926@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW'),
('23WP14406', 'U015', 'Teoh Kah Mun', 'teoh.14406@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP20441', 'U016', 'Puteri Balqis Binti Mahadzir', 'puteri.20441@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW'),
('23WP20606', 'U017', 'Karthik A/L Anuar', 'karthik.20606@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP21572', 'U018', 'Ravi A/L Chandran', 'ravi.21572@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW'),
('23WP21706', 'U019', 'Sanjay A/L Muniandy', 'sanjay.21706@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW'),
('23WP24179', 'U020', 'Mohammad Faizal Bin Zakaria', 'mohammad.24179@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP24747', 'U021', 'Lee Kian Seng', 'lee.24747@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP27426', 'U022', 'Priya A/P Ravichandran', 'priya.27426@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP27671', 'U023', 'Azman Bin Sulaiman', 'azman.27671@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW'),
('23WP31454', 'U024', 'Syafiqah Binti Mansor', 'syafiqah.31454@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP32466', 'U025', 'Jason Low Kah Hing', 'jason.32466@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW'),
('23WP34128', 'U026', 'Mohammad Haziq Bin Razak', 'mohammad.34128@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP34773', 'U027', 'Chong Wei Liang', 'chong.34773@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP37638', 'U028', 'Preeti A/P Sundar', 'preeti.37638@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW'),
('23WP37849', 'U029', 'Khairul Azmi Bin Hassan', 'khairul.37849@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW'),
('23WP38477', 'U030', 'Michelle Tan Xin Yi', 'michelle.38477@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW'),
('23WP40593', 'U031', 'Low Yee Wen', 'low.40593@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW'),
('23WP47754', 'U032', 'Nurul Izzah Binti Kamaruddin', 'nurul.47754@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP51727', 'U033', 'Siti Aminah Binti Hassan', 'siti.51727@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP56143', 'U034', 'Yeoh Zi Yi', 'yeoh.56143@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP57187', 'U035', 'Kelly Chan Pui Yi', 'kelly.57187@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW'),
('23WP57467', 'U036', 'Thivya A/P Selvam', 'thivya.57467@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW'),
('23WP61618', 'U037', 'Ng See Kiat', 'ng.61618@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW'),
('23WP61839', 'U038', 'Zulhilmi Bin Mohamad', 'zulhilmi.61839@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW'),
('23WP69300', 'U039', 'Farah Nabilah Binti Yusof', 'farah.69300@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP71365', 'U040', 'Vikneswaran A/L Mani', 'vikneswaran.71365@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW'),
('23WP73033', 'U041', 'Ganesh A/L Thapandion', 'ganesh.73033@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP73789', 'U042', 'Bryan Wong Jun Kit', 'bryan.73789@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW'),
('23WP77365', 'U043', 'Lim Jia Hao', 'lim.77365@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP78708', 'U044', 'Tan Mei Ling', 'tan.78708@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP79311', 'U045', 'Siti Sarah Binti Mokhtar', 'siti.79311@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW'),
('23WP79793', 'U046', 'Nurul Ain Binti Zulkifli', 'nurul.79793@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW'),
('23WP80803', 'U047', 'Wong Siew Fen', 'wong.80803@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP82178', 'U048', 'Anjali A/P Mohan', 'anjali.82178@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP82670', 'U049', 'Liew Chee Keong', 'liew.82670@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW'),
('23WP84917', 'U050', 'Ahmad Zaki Bin Rosli', 'ahmad.84917@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP94665', 'U051', 'Ahmad Syahmi Bin Mohd Rizal', 'ahmad.94665@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD'),
('23WP96546', 'U052', 'Noraini Binti Abdullah', 'noraini.96546@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW');

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
('U001', 'Admin'),
('U002', 'Staff'),
('U003', 'Staff'),
('U004', 'Staff'),
('U005', 'Staff'),
('U006', 'Staff'),
('U007', 'Staff'),
('U008', 'Staff'),
('U009', 'Staff'),
('U010', 'Staff'),
('U011', 'Staff'),
('U012', 'Staff'),
('U013', 'Student'),
('U014', 'Student'),
('U015', 'Student'),
('U016', 'Student'),
('U017', 'Student'),
('U018', 'Student'),
('U019', 'Student'),
('U020', 'Student'),
('U021', 'Student'),
('U022', 'Student'),
('U023', 'Student'),
('U024', 'Student'),
('U025', 'Student'),
('U026', 'Student'),
('U027', 'Student'),
('U028', 'Student'),
('U029', 'Student'),
('U030', 'Student'),
('U031', 'Student'),
('U032', 'Student'),
('U033', 'Student'),
('U034', 'Student'),
('U035', 'Student'),
('U036', 'Student'),
('U037', 'Student'),
('U038', 'Student'),
('U039', 'Student'),
('U040', 'Student'),
('U041', 'Student'),
('U042', 'Student'),
('U043', 'Student'),
('U044', 'Student'),
('U045', 'Student'),
('U046', 'Student'),
('U047', 'Student'),
('U048', 'Student'),
('U049', 'Student'),
('U050', 'Student'),
('U051', 'Student'),
('U052', 'Student'),
('U053', 'Staff'),
('U054', 'Staff'),
('U055', 'Staff'),
('U056', 'Staff');

--
-- Indexes for dumped tables
--

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
-- Indexes for table `class_schedule`
--
ALTER TABLE `class_schedule`
  ADD PRIMARY KEY (`scheduleID`),
  ADD KEY `fk_cs_program` (`programID`),
  ADD KEY `fk_cs_course` (`courseID`),
  ADD KEY `fk_cs_staff` (`staffID`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`courseID`);

--
-- Indexes for table `facility`
--
ALTER TABLE `facility`
  ADD PRIMARY KEY (`facilityID`);

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
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `recordID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  MODIFY `sessionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_schedule`
--
ALTER TABLE `class_schedule`
  MODIFY `scheduleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `replacement_request`
--
ALTER TABLE `replacement_request`
  MODIFY `replacementID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_fk_user` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_cs_course` FOREIGN KEY (`courseID`) REFERENCES `course` (`courseID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cs_program` FOREIGN KEY (`programID`) REFERENCES `program` (`programID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cs_staff` FOREIGN KEY (`staffID`) REFERENCES `staff` (`staffID`) ON DELETE CASCADE;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
