-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2025 at 05:45 AM
-- Server version: 10.4.11-MariaDB
-- PHP Version: 7.4.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `skill_tracker`
--

-- --------------------------------------------------------

--
-- Table structure for table `mentoring_tasks`
--

CREATE TABLE `mentoring_tasks` (
  `id` int(11) NOT NULL,
  `intern_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `skill_id` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `mentoring_tasks`
--

INSERT INTO `mentoring_tasks` (`id`, `intern_id`, `supervisor_id`, `title`, `description`, `skill_id`, `due_date`, `status`, `created_at`, `updated_at`) VALUES
(4, 16, 17, 'meeting', 'improve skills', 3, '2025-05-11', 'pending', '2025-05-09 11:35:22', '2025-05-09 11:35:22');

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('technical','soft') NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `skills`
--

INSERT INTO `skills` (`id`, `name`, `category`, `description`, `created_at`) VALUES
(1, 'PHP Programming', 'technical', 'Ability to write clean, efficient PHP code', '2025-05-08 13:29:44'),
(2, 'JavaScript', 'technical', 'Frontend programming with JavaScript', '2025-05-08 13:29:44'),
(3, 'Database Design', 'technical', 'SQL database design and optimization', '2025-05-08 13:29:44'),
(4, 'Git Version Control', 'technical', 'Using Git for source code management', '2025-05-08 13:29:44'),
(5, 'API Development', 'technical', 'Building RESTful APIs', '2025-05-08 13:29:44'),
(6, 'Communication', 'soft', 'Ability to clearly convey information', '2025-05-08 13:29:44'),
(7, 'Teamwork', 'soft', 'Working effectively with others', '2025-05-08 13:29:44'),
(8, 'Problem Solving', 'soft', 'Finding effective solutions to challenges', '2025-05-08 13:29:44'),
(9, 'Time Management', 'soft', 'Managing deadlines and priorities', '2025-05-08 13:29:44'),
(10, 'Leadership', 'soft', 'Guiding and motivating team members', '2025-05-08 13:29:44');

-- --------------------------------------------------------

--
-- Table structure for table `skill_assessments`
--

CREATE TABLE `skill_assessments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `initial_level` enum('Beginner','Intermediate','Advanced','Expert') NOT NULL,
  `current_level` enum('Beginner','Intermediate','Advanced','Expert') NOT NULL,
  `supervisor_rating` enum('Beginner','Intermediate','Advanced','Expert') DEFAULT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `supervisor_comments` text DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `skill_assessments`
--

INSERT INTO `skill_assessments` (`id`, `user_id`, `skill_id`, `initial_level`, `current_level`, `supervisor_rating`, `supervisor_id`, `supervisor_comments`, `last_updated`) VALUES
(8, 7, 2, 'Beginner', 'Beginner', NULL, NULL, NULL, '2025-05-08 14:39:31'),
(9, 7, 3, 'Intermediate', 'Intermediate', NULL, NULL, NULL, '2025-05-08 14:39:42'),
(10, 7, 1, 'Beginner', 'Beginner', NULL, NULL, NULL, '2025-05-08 14:39:50'),
(11, 8, 1, 'Advanced', 'Advanced', NULL, NULL, NULL, '2025-05-08 14:40:33'),
(12, 8, 3, 'Beginner', 'Beginner', NULL, NULL, NULL, '2025-05-08 14:40:49'),
(13, 8, 2, 'Advanced', 'Advanced', NULL, NULL, NULL, '2025-05-08 14:40:57'),
(14, 9, 5, 'Intermediate', 'Intermediate', NULL, NULL, NULL, '2025-05-08 14:41:21'),
(15, 9, 2, 'Intermediate', 'Intermediate', NULL, NULL, NULL, '2025-05-08 14:41:26'),
(16, 9, 3, 'Beginner', 'Beginner', NULL, NULL, NULL, '2025-05-08 14:41:33'),
(22, 12, 5, 'Beginner', 'Beginner', NULL, NULL, NULL, '2025-05-08 14:42:46'),
(23, 12, 4, 'Intermediate', 'Intermediate', NULL, NULL, NULL, '2025-05-08 14:42:52'),
(24, 12, 1, 'Intermediate', 'Intermediate', NULL, NULL, NULL, '2025-05-08 14:42:55'),
(25, 14, 2, 'Advanced', 'Advanced', NULL, NULL, NULL, '2025-05-08 14:43:18'),
(26, 14, 1, 'Advanced', 'Advanced', NULL, NULL, NULL, '2025-05-08 14:43:22'),
(27, 14, 3, 'Intermediate', 'Intermediate', NULL, NULL, NULL, '2025-05-08 14:43:28'),
(28, 13, 5, 'Beginner', 'Beginner', NULL, NULL, NULL, '2025-05-08 14:43:45'),
(29, 13, 3, 'Beginner', 'Beginner', NULL, NULL, NULL, '2025-05-08 14:43:49'),
(30, 13, 4, 'Beginner', 'Beginner', NULL, NULL, NULL, '2025-05-08 14:43:52'),
(31, 13, 2, 'Beginner', 'Beginner', NULL, NULL, NULL, '2025-05-08 14:43:56'),
(32, 13, 1, 'Beginner', 'Beginner', NULL, NULL, NULL, '2025-05-08 14:44:00'),
(33, 11, 5, 'Advanced', 'Advanced', NULL, NULL, NULL, '2025-05-08 14:44:18'),
(34, 11, 4, 'Beginner', 'Beginner', NULL, NULL, NULL, '2025-05-08 14:44:25'),
(35, 11, 1, 'Advanced', 'Advanced', NULL, NULL, NULL, '2025-05-08 14:44:29'),
(36, 11, 3, 'Expert', 'Expert', NULL, NULL, NULL, '2025-05-08 14:44:34'),
(37, 11, 2, 'Expert', 'Expert', NULL, NULL, NULL, '2025-05-08 14:44:38'),
(38, 7, 5, 'Beginner', 'Beginner', NULL, NULL, NULL, '2025-05-08 14:57:35'),
(39, 7, 4, 'Beginner', 'Beginner', NULL, NULL, NULL, '2025-05-08 14:57:39'),
(40, 7, 6, 'Beginner', 'Beginner', NULL, NULL, NULL, '2025-05-08 14:57:46'),
(41, 7, 10, 'Beginner', 'Beginner', NULL, NULL, NULL, '2025-05-08 14:57:50'),
(42, 15, 3, 'Beginner', 'Beginner', NULL, NULL, NULL, '2025-05-08 15:45:51'),
(43, 15, 9, 'Expert', 'Expert', NULL, NULL, NULL, '2025-05-08 15:46:07'),
(44, 15, 4, 'Beginner', 'Beginner', NULL, NULL, NULL, '2025-05-08 16:11:50'),
(45, 16, 3, 'Intermediate', 'Beginner', 'Advanced', 17, 'keep up', '2025-05-09 11:35:43'),
(46, 16, 6, 'Advanced', 'Beginner', 'Beginner', 17, 'mag aral kang lapuk ka', '2025-05-09 11:33:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `role` enum('intern','supervisor','admin') NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `first_name`, `last_name`, `role`, `department`, `created_at`, `updated_at`) VALUES

(6, 'jim', '$2y$10$czQARu26ToEnH7obzCEnauccfKBDE4NGSSPMfS1BoXoZyux39yZzC', 'mijsuofficial@gmail.com', 'Mar James', 'Delimios', 'supervisor', 'IT', '2025-05-08 14:32:43', '2025-05-08 14:32:43'),
(7, 'gervin', '$2y$10$19fyQs.0sIIG6feXglrvn.GV9K5h2Zj13xoOmlKR4Ggtem0K7nXje', 'gervintubol@gmail.com', 'Gervin', 'Dela Cueva', 'intern', 'IT', '2025-05-08 14:33:18', '2025-05-08 14:33:18'),
(8, 'jericho', '$2y$10$lMtORrNMUV4maOf70Ck9r.avLDNFnJKSQExKJhKt2AvzIPhF4KcXu', 'galacetubol@gmail.com', 'Jericho', 'Galace', 'intern', 'IT', '2025-05-08 14:33:54', '2025-05-08 14:33:54'),
(9, 'daniel', '$2y$10$025mpffzz6J3JvjZlq6EOO6qlolAm/y3vBReeuS2ZJmMZUHPnR6pm', 'serranotubol@gmail.com', 'Daniel', 'Serrano', 'intern', 'HR', '2025-05-08 14:34:28', '2025-05-08 14:34:28'),
(11, 'arnold', '$2y$10$jaAUi8HXZ.PzcpmFhtwXTeREKoLcO27rQ6UmJJD1F977fBR1y9ePm', 'bernastubol@gmail.com', 'Arnold', 'Bernas', 'intern', 'Marketing', '2025-05-08 14:35:56', '2025-05-08 14:35:56'),
(12, 'ivan', '$2y$10$ELj3VHN6hsImdnIyh2kWEOMwTEAXggARVfBfoeudNrPFBcJWtB1CK', 'villegastubol@gmail.com', 'Ivan', 'Villegas', 'intern', 'Operations', '2025-05-08 14:36:38', '2025-05-08 14:36:38'),
(13, 'jorvince', '$2y$10$BEUu0cMw8XusWNdAAs7zYemiqQ4jfwHH2uVbVLPVM99ZcZ33gED5y', 'budoytubol@gmail.com', 'Jorvince', 'Budoy', 'intern', 'Sales', '2025-05-08 14:37:41', '2025-05-08 14:37:41'),
(14, 'jeremy', '$2y$10$8wO0DKlMBiEHcjNH2M0fsOfgJ3t0n3xCkDaECsVakuoqLDSrcih7C', 'aclantubol@gmail.com', 'Jeremy', 'Aclan', 'intern', 'Customer Support', '2025-05-08 14:38:33', '2025-05-08 14:38:33'),
(15, 'gus', '$2y$10$1WCCjNk1SKkQqvT91eDyVet6elYaXgCyqAlun3liN2pNnpGoIVS2O', 'gustambunting@gmail.com', 'gus', 'tambunting', 'intern', 'Marketing', '2025-05-08 15:43:03', '2025-05-08 15:43:03'),
(16, 'lani123', '$2y$10$8p172zvqP4yP5xO.0tbOceA9TZfEU5EUSBAYEsR9PRVg9mg37tYKW', 'lani@gmail.com', 'lani', 'cayatano', 'intern', 'Marketing', '2025-05-09 11:16:47', '2025-05-09 11:16:47'),
(17, 'visor123', '$2y$10$s7pHowVkbfwfi51nnTNTu.gacEnxmMIK8DwH4t8ET.lyyJibrKYRa', 'visor@gmail.com', 'super', 'visor', 'supervisor', 'Marketing', '2025-05-09 11:27:12', '2025-05-09 11:27:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mentoring_tasks`
--
ALTER TABLE `mentoring_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `intern_id` (`intern_id`),
  ADD KEY `supervisor_id` (`supervisor_id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `skill_assessments`
--
ALTER TABLE `skill_assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `skill_id` (`skill_id`),
  ADD KEY `supervisor_id` (`supervisor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mentoring_tasks`
--
ALTER TABLE `mentoring_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `skill_assessments`
--
ALTER TABLE `skill_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `mentoring_tasks`
--
ALTER TABLE `mentoring_tasks`
  ADD CONSTRAINT `mentoring_tasks_ibfk_1` FOREIGN KEY (`intern_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mentoring_tasks_ibfk_2` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mentoring_tasks_ibfk_3` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`);

--
-- Constraints for table `skill_assessments`
--
ALTER TABLE `skill_assessments`
  ADD CONSTRAINT `skill_assessments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `skill_assessments_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `skill_assessments_ibfk_3` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


INSERT INTO `users` (`id`, `username`, `password`, `email`, `first_name`, `last_name`, `role`, `department`, `created_at`, `updated_at`) VALUES 
(1, 'admin', 'admin1230', 'admin@gmail.com', 'Admin', 'User', 'admin', 'IT', NOW(), NOW());

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `intern_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `feedback_type` enum('weekly','monthly') NOT NULL,
  `content` text NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `intern_id` (`intern_id`),
  KEY `supervisor_id` (`supervisor_id`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`intern_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
