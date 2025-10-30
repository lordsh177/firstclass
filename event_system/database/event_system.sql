-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 30, 2025 at 04:40 PM
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
(30, 40, NULL, 7, '2025-10-29 01:05:51', '2025-10-29 01:05:52'),
(31, 42, NULL, 3, '2025-10-29 02:41:20', '2025-10-29 02:42:21'),
(32, 39, NULL, 7, '2025-10-29 12:10:38', '2025-10-29 12:10:54'),
(33, 43, NULL, 3, '2025-10-29 12:13:09', '2025-10-29 12:13:17'),
(34, 43, NULL, 1, '2025-10-29 12:58:22', '2025-10-29 12:58:22'),
(35, 43, NULL, 7, '2025-10-29 13:02:15', '2025-10-29 13:02:15'),
(36, 43, NULL, 2, '2025-10-29 13:02:35', '2025-10-29 13:02:37'),
(37, 43, NULL, 4, '2025-10-29 13:06:43', '2025-10-29 13:06:46'),
(40, 40, NULL, 1, '2025-10-30 22:11:16', '2025-10-30 22:18:51'),
(41, 42, NULL, 1, '2025-10-30 22:15:09', '2025-10-30 22:21:38'),
(42, 42, NULL, 2, '2025-10-30 22:24:27', '2025-10-30 22:25:00'),
(43, 42, NULL, 4, '2025-10-30 22:29:06', '2025-10-30 22:29:54'),
(44, 42, NULL, 7, '2025-10-30 22:32:44', '2025-10-30 22:33:38'),
(45, 40, NULL, 4, '2025-10-30 22:34:58', '2025-10-30 22:36:23'),
(46, 45, NULL, 8, '2025-10-30 22:54:44', '2025-10-30 22:55:14');

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
(2, 'Admin', 'Created event: Sci-Math', '2025-10-22 15:12:12'),
(5, 'Admin', 'Created event: Moglooween USTP', '2025-10-22 16:51:54'),
(7, 'Admin', 'Created event: TrailBlazers', '2025-10-24 05:18:12'),
(21, 'Admin', 'Created event Earthquake Drill', '2025-10-26 05:12:14'),
(22, 'Juan Dela Cruz', 'Created user REG0467', '2025-10-26 11:58:14'),
(24, 'Admin', 'Created event A Small Tourna Event', '2025-10-26 14:59:01'),
(25, 'Admin', 'Updated event: A Small Tournament of dota 2', '2025-10-26 15:01:47'),
(27, 'Admin', 'Updated event: Earthquake Drill', '2025-10-26 15:02:26'),
(32, 'Admin', 'Created event: Misamis Oriental E-Sports Organization', '2025-10-26 15:05:53'),
(33, 'Admin', 'Updated event: Misamis Oriental E-Sports Organization', '2025-10-26 15:07:13'),
(45, 'Angelie Garcia', 'Created user REG6034 with QR code', '2025-10-28 16:50:11'),
(47, 'Clark Lomopog', 'Created user REG9437 with QR code', '2025-10-28 17:05:03'),
(48, 'Juan Dela Cruz', 'Created user REG8739 with QR code', '2025-10-28 18:25:19'),
(49, 'Admin', 'Deleted User 41', '2025-10-28 18:25:51'),
(50, 'Jerriel Cuizon', 'Created user REG3156 with QR code', '2025-10-28 18:33:35'),
(51, 'Admin', 'Updated event: Earthquake Drill', '2025-10-28 18:37:50'),
(52, 'Angelie Garcia', 'Updated user', '2025-10-28 18:39:56'),
(53, 'Christzyl Ann Casiño', 'Created user REG0685 with QR code', '2025-10-29 04:11:46'),
(54, 'Kristel Joy Seno', 'Created user REG4241 with QR code', '2025-10-30 14:38:56'),
(55, 'Admin', 'Created event: Acquintance Party', '2025-10-30 14:39:58'),
(56, 'Admin', 'Updated event: Acquintance Party', '2025-10-30 14:40:10'),
(57, 'Xyrel Valledor', 'Created user REG9815 with QR code', '2025-10-30 14:53:34'),
(70, 'System', 'Created new event: Trial for email notification and sent email notifications.', '2025-10-30 15:37:40');

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
(3, 'TrailBlazers', '2025-10-24', 'USTP-Villanueva', 'Lorem Ipsum dolor'),
(4, 'Earthquake Drill', '2025-10-25', 'USTP-Villanueva Unity Building 3rd Floor', 'A brief comprehension to assist student what to do during earthquake.'),
(7, 'Misamis Oriental E-Sports Organization', '2025-10-28', 'LGU - Villanuva SK', 'Open to all players Around Misamis Oriental.'),
(8, 'Acquintance Party', '2025-10-29', 'Villanueva Gymnasium', 'A USTP event open to all levels.'),
(14, 'Trial for email notification', '2025-10-30', 'USTP - Villanueva', 'Please disregard this msg it if reach you out I just did randomized email intended for school purposes.');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `qr_code` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `reg_id`, `name`, `email`, `contact`, `gender`, `address`, `state`, `country`, `password`, `reg_code`, `created_at`, `qr_code`) VALUES
(1, NULL, 'Admin User', 'admin@eventsystem.com', '09123456789', 'Male', 'Admin Office', 'Misamis Oriental', 'Philippines', '81dc9bdb52d04dc20036dbd8313ed055', 'REG-ADMIN', '2025-10-21 18:35:47', NULL),
(39, 'REG6034', 'Angelie Garcia', 'a.g@gmail.com', '07562194172', 'Female', 'Brgy.Dayawan', 'Misamis Oriental', 'Villanueva', '482c811da5d5b4bc6d497ffa98491e38', '', '2025-10-28 16:50:11', 'qrcodes/REG6034.png'),
(40, 'REG9437', 'Clark Lomopog', 'lomopog.clark777@gmail.com', '09656106443', 'Male', 'Brgy. Poblacion 2 Mutya', 'Misamis Oriental', 'Villanueva', '482c811da5d5b4bc6d497ffa98491e38', '', '2025-10-28 17:05:03', 'qrcodes/REG9437.png'),
(42, 'REG3156', 'Jerriel Cuizon', 'jc@gmail.com', '09876543123', 'Male', 'bgry. Katipunan Zone 4', 'Misamis Oriental', 'Villanueva', '482c811da5d5b4bc6d497ffa98491e38', '', '2025-10-28 18:33:35', 'qrcodes/REG3156.png'),
(43, 'REG0685', 'Christzyl Ann Casiño', 'christzylcasino@gmail.com', '09517072294', 'Female', 'Zone 2 St.Ana', 'Misamis Oriental', 'Tagoloan', '482c811da5d5b4bc6d497ffa98491e38', '', '2025-10-29 04:11:46', 'qrcodes/REG0685.png'),
(44, 'REG4241', 'Kristel Joy Seno', 'k.j@gmail.com', '09123456782', 'Male', 'Villamanga', 'Misamis Oriental', 'Tagoloan', '482c811da5d5b4bc6d497ffa98491e38', '', '2025-10-30 14:38:56', 'qrcodes/REG4241.png'),
(45, 'REG9815', 'Xyrel Valledor', 'xyrax@gmail.com', '09273618724', 'Male', 'Poblacion 1', 'Misamis Oriental', 'Villanueva', '482c811da5d5b4bc6d497ffa98491e38', '', '2025-10-30 14:53:34', 'qrcodes/REG9815.png');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

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
