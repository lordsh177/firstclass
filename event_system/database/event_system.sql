-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2025 at 09:02 AM
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
-- Database: `event_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reg_id` varchar(20) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `check_in` datetime DEFAULT NULL,
  `check_out` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `reg_id`, `event_id`, `check_in`, `check_out`) VALUES
(1, 3, 'REG1225', 3, '2025-10-24 13:27:13', '2025-10-24 13:44:42'),
(2, 3, 'REG1225', 1, '2025-10-24 13:29:09', '2025-10-24 13:48:04'),
(3, 3, 'REG1225', 2, '2025-10-24 13:29:34', '2025-10-24 14:22:43'),
(4, 3, 'REG1225', 2, '2025-10-24 13:32:51', '2025-10-24 14:22:43'),
(5, 3, 'REG1225', 2, '2025-10-24 13:33:19', '2025-10-24 14:22:43'),
(6, 4, 'REG2661', 2, '2025-10-24 13:37:33', '2025-10-24 14:22:34'),
(7, 3, 'REG1225', 3, '2025-10-24 13:46:50', '2025-10-24 13:46:53'),
(8, 5, 'REG3236', 3, '2025-10-24 14:37:25', '2025-10-24 14:37:42'),
(9, 5, 'REG3236', 2, '2025-10-24 14:38:16', '2025-10-24 14:39:08'),
(10, 5, 'REG3236', 1, '2025-10-24 14:39:23', '2025-10-24 14:39:25'),
(11, 4, 'REG2661', 3, '2025-10-24 14:46:28', '2025-10-24 14:46:30'),
(12, 6, 'REG7161', 3, '2025-10-24 14:52:30', '2025-10-24 14:52:43');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user`, `action`, `timestamp`) VALUES
(1, 'Juan Dela Cruz', 'Created user', '2025-10-22 15:11:29'),
(2, 'Admin', 'Created event: Sci-Math', '2025-10-22 15:12:12'),
(3, 'Jeriel Cuizon', 'Created user with reg_id REG1225', '2025-10-22 15:39:59'),
(4, 'Kristel Joy Seno', 'Created user with reg_id REG2661', '2025-10-22 16:50:36'),
(5, 'Admin', 'Created event: Moglooween USTP', '2025-10-22 16:51:54'),
(6, 'Admin', 'Deleted user ID 2', '2025-10-23 06:31:07'),
(7, 'Admin', 'Created event: TrailBlazers', '2025-10-24 05:18:12'),
(8, 'User 3', 'Marked in for event 3', '2025-10-24 05:27:13'),
(9, 'User 3', 'Marked in for event 1', '2025-10-24 05:29:09'),
(10, 'User 3', 'Marked in for event 2', '2025-10-24 05:29:34'),
(11, 'User 3', 'Marked in for event 2', '2025-10-24 05:32:51'),
(12, 'User 3', 'Marked in for event 2', '2025-10-24 05:33:19'),
(13, 'User 4', 'Marked in for event 2', '2025-10-24 05:37:33'),
(14, 'Clark Lomopog', 'Created user REG3236', '2025-10-24 06:36:56'),
(15, 'Kristel Joy Seno', 'Checked in to event 3', '2025-10-24 06:46:28'),
(16, 'Kristel Joy Seno', 'Checked out from event 3', '2025-10-24 06:46:30'),
(17, 'Christzyl Ann CasiÃ±o', 'Created user REG7161', '2025-10-24 06:52:10'),
(18, 'Christzyl Ann CasiÃ±o', 'Checked in to event 3', '2025-10-24 06:52:30'),
(19, 'Christzyl Ann CasiÃ±o', 'Checked out from event 3', '2025-10-24 06:52:43');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `name`, `date`, `location`, `description`) VALUES
(1, 'Sci-Math', '2025-10-24', 'USTP-Villanueva', 'Science and Mathematics month celebration.'),
(2, 'Moglooween USTP', '2025-11-02', 'USTP-Villanuva Campus', 'come and watch horror films'),
(3, 'TrailBlazers', '2025-10-24', 'USTP-Villanueva', 'Lorem Ipsum dolor');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `reg_id` varchar(10) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL DEFAULT '81dc9bdb52d04dc20036dbd8313ed055',
  `reg_code` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `reg_id`, `name`, `email`, `contact`, `gender`, `address`, `state`, `country`, `password`, `reg_code`, `created_at`) VALUES
(1, NULL, 'Admin User', 'admin@eventsystem.com', '09123456789', 'Male', 'Admin Office', 'Misamis Oriental', 'Philippines', '81dc9bdb52d04dc20036dbd8313ed055', 'REG-ADMIN', '2025-10-21 18:35:47'),
(3, 'REG1225', 'Jeriel Cuizon', 'jcuizon@gmail.com', '09656106442', 'Male', 'Brgy.Looc', 'Villanueva', 'Misamis Oriental', '482c811da5d5b4bc6d497ffa98491e38', '', '2025-10-22 15:39:59'),
(4, 'REG2661', 'Kristel Joy Seno', 'k.seno@gmail.com', '09123456789', 'Female', 'Zone 4', 'Tagoloan', 'Misamis Oriental', '482c811da5d5b4bc6d497ffa98491e38', '', '2025-10-22 16:50:36'),
(5, 'REG3236', 'Clark Lomopog', 'lomopog.clark777@gmail.com', '09656106443', 'Male', 'Brgy.Pob2', 'Villanueva', 'Misamis Oriental', '482c811da5d5b4bc6d497ffa98491e38', '', '2025-10-24 06:36:56'),
(6, 'REG7161', 'Christzyl Ann CasiÃ±o', 'christzylcasino@gmail.com', '09517072294', 'Female', 'Zone 4', 'Tagoloan', 'Misamis Oriental', '482c811da5d5b4bc6d497ffa98491e38', '', '2025-10-24 06:52:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `attendance_event_fk` (`event_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `reg_id` (`reg_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_event_fk` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`),
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
