-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 15, 2026 at 04:09 PM
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
('23WP12509', '23WP12509', 'Student RSD 7', 'rsd7@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP12926', '23WP12926', 'Student RSW 6', 'rsw6@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP14406', '23WP14406', 'Student RSD 10', 'rsd10@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP20441', '23WP20441', 'Student RSW 20', 'rsw20@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP20606', '23WP20606', 'Student RSD 4', 'rsd4@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP21572', '23WP21572', 'Student RSW 3', 'rsw3@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP21706', '23WP21706', 'Student RSW 9', 'rsw9@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP24179', '23WP24179', 'Student RSD 20', 'rsd20@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP24747', '23WP24747', 'Student RSD 13', 'rsd13@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP27426', '23WP27426', 'Student RSD 12', 'rsd12@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP27671', '23WP27671', 'Student RSW 5', 'rsw5@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP31454', '23WP31454', 'Student RSD 14', 'rsd14@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP32466', '23WP32466', 'Student RSW 1', 'rsw1@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP34128', '23WP34128', 'Student RSD 11', 'rsd11@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP34773', '23WP34773', 'Student RSD 3', 'rsd3@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP37638', '23WP37638', 'Student RSW 18', 'rsw18@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP37849', '23WP37849', 'Student RSW 11', 'rsw11@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP38477', '23WP38477', 'Student RSW 4', 'rsw4@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP40593', '23WP40593', 'Student RSW 10', 'rsw10@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP47754', '23WP47754', 'Student RSD 2', 'rsd2@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP51727', '23WP51727', 'Student RSD 6', 'rsd6@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP56143', '23WP56143', 'Student RSD 19', 'rsd19@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP57187', '23WP57187', 'Student RSW 16', 'rsw16@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP57467', '23WP57467', 'Student RSW 12', 'rsw12@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP61618', '23WP61618', 'Student RSW 7', 'rsw7@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP61839', '23WP61839', 'Student RSW 17', 'rsw17@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP69300', '23WP69300', 'Student RSD 9', 'rsd9@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP71365', '23WP71365', 'Student RSW 15', 'rsw15@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP73033', '23WP73033', 'Student RSD 15', 'rsd15@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP73789', '23WP73789', 'Student RSW 13', 'rsw13@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP77365', '23WP77365', 'Student RSD 8', 'rsd8@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP78708', '23WP78708', 'Student RSD 5', 'rsd5@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP79311', '23WP79311', 'Student RSW 8', 'rsw8@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP79793', '23WP79793', 'Student RSW 2', 'rsw2@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP80803', '23WP80803', 'Student RSD 16', 'rsd16@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP82178', '23WP82178', 'Student RSD 18', 'rsd18@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP82670', '23WP82670', 'Student RSW 19', 'rsw19@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering'),
('23WP84917', '23WP84917', 'Student RSD 17', 'rsd17@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP94665', '23WP94665', 'Student RSD 1', 'rsd1@student.tarc.edu.my', '012-3456789', 'Group A', 'RSD: Software Development'),
('23WP96546', '23WP96546', 'Student RSW 14', 'rsw14@student.tarc.edu.my', '012-9876543', 'Group B', 'RSW: Software Engineering');

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
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`studentID`),
  ADD KEY `userID` (`userID`);

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
-- Constraints for dumped tables
--

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
