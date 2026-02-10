-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 10, 2026 at 06:12 AM
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
-- Database: `spark`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `author_id` int(11) NOT NULL,
  `target_role` enum('all','student','admin','departmentcoordinator','studentaffairs') DEFAULT 'all',
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `message`, `author_id`, `target_role`, `is_featured`, `created_at`) VALUES
(1, 'Welcome to SPARK\'26!', 'We are excited to announce that SPARK\'26 registration is now open! This year\'s event promises to be bigger and better than ever. Get ready to showcase your innovative projects and compete with the brightest minds on campus.', 1, 'all', 1, '2026-02-09 13:02:04'),
(2, 'Submission Guidelines Updated', 'Please review the updated project submission guidelines. We have added new categories and revised the documentation requirements. Make sure to check the Guidelines page for complete details.', 1, 'all', 0, '2026-02-09 13:02:04');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `student_id` int(11) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `team_members` text DEFAULT NULL,
  `github_link` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_comments` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `title`, `description`, `category`, `student_id`, `team_id`, `department`, `team_members`, `github_link`, `file_path`, `status`, `reviewed_by`, `review_comments`, `reviewed_at`, `score`, `created_at`) VALUES
(5, 'Sowmi AI', 'Hello Sowmi', 'ai', 11, 3, 'AIML', '', 'https://github.com/jayanthansenthilkumar/sowmiai', 'assets/uploads/projects/1770699728_bus-pass.pdf', 'approved', 10, 'Accepted', '2026-02-10 05:06:02', NULL, '2026-02-10 05:02:08');

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` datetime NOT NULL,
  `event_type` varchar(50) DEFAULT 'general',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule`
--

INSERT INTO `schedule` (`id`, `title`, `description`, `event_date`, `event_type`, `created_by`, `created_at`) VALUES
(1, 'Registration Opens', 'Start registering your projects and teams', '2026-02-01 09:00:00', 'milestone', 1, '2026-02-09 13:02:04'),
(2, 'Project Submission Deadline', 'Last date to submit your project details', '2026-02-15 23:59:00', 'deadline', 1, '2026-02-09 13:02:04'),
(3, 'Review Phase', 'Projects will be reviewed by department coordinators', '2026-02-20 09:00:00', 'milestone', 1, '2026-02-09 13:02:04'),
(4, 'Final Presentations', 'Present your projects to the judging panel', '2026-02-25 09:00:00', 'event', 1, '2026-02-09 13:02:04'),
(5, 'Awards Ceremony', 'Winners announcement and prize distribution', '2026-02-28 14:00:00', 'event', 1, '2026-02-09 13:02:04');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'event_name', 'SPARK\'26', '2026-02-09 13:02:04'),
(2, 'event_date', '2026-02-15', '2026-02-09 13:02:04'),
(3, 'submission_deadline', '2026-02-15 23:59:00', '2026-02-09 13:02:04'),
(4, 'max_team_size', '4', '2026-02-09 13:02:04'),
(5, 'registration_open', '1', '2026-02-09 13:02:04');

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` int(11) NOT NULL,
  `team_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `team_code` varchar(10) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `leader_id` int(11) NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  `max_members` int(11) DEFAULT 4,
  `status` enum('open','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `team_name`, `description`, `team_code`, `project_id`, `leader_id`, `department`, `max_members`, `status`, `created_at`) VALUES
(3, 'Syraa', 'Welcome to Syraa', '179A08', NULL, 11, 'AIML', 4, 'open', '2026-02-10 05:00:51');

-- --------------------------------------------------------

--
-- Table structure for table `team_invitations`
--

CREATE TABLE `team_invitations` (
  `id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `invited_by` int(11) NOT NULL,
  `invited_user_id` int(11) NOT NULL,
  `status` enum('pending','accepted','declined') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(50) DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`id`, `team_id`, `user_id`, `role`, `joined_at`) VALUES
(4, 3, 11, 'leader', '2026-02-10 05:00:51'),
(5, 3, 12, 'member', '2026-02-10 05:05:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  `year` varchar(20) DEFAULT NULL,
  `reg_no` varchar(12) DEFAULT NULL,
  `role` enum('student','admin','departmentcoordinator','studentaffairs') NOT NULL DEFAULT 'student',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `department`, `year`, `reg_no`, `role`, `status`, `created_at`) VALUES
(1, 'Jayanthan Senthilkumar', 'spark', 'spark@spark.com', 'spark', NULL, NULL, NULL, 'admin', 'active', '2026-02-09 13:02:04'),
(8, 'Ramesh C', 'samkce', 'studentaffairs@mkce.ac.in', 'samkce', NULL, NULL, NULL, 'studentaffairs', 'active', '2026-02-10 04:53:17'),
(9, 'AIDS', 'sparkaids', 'aidscoordinator@gmail.com', 'sparkaids', 'AIDS', NULL, NULL, 'departmentcoordinator', 'active', '2026-02-10 04:57:49'),
(10, 'AIML', 'sparkaiml', 'aimlcoordinator@gmail.com', 'sparkaiml', 'AIML', NULL, NULL, 'departmentcoordinator', 'active', '2026-02-10 04:58:21'),
(13, 'FE Coordinator', 'sparkfe', 'fecoordinator@gmail.com', 'sparkfe', 'FE', NULL, NULL, 'departmentcoordinator', 'active', '2026-02-10 06:00:00'),
(11, 'JAYANTHAN S', 'jayanthan', 'itsmejayanthan@gmail.com', '123456', 'AIML', 'IV year', '927622BAL016', 'student', 'active', '2026-02-10 04:59:46'),
(12, 'Hari', 'hari', 'hari@gmail.com', '123456', 'CSE', 'I year', '927625BCS001', 'student', 'active', '2026-02-10 05:04:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `recipient_id` (`recipient_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `fk_project_team` (`team_id`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `team_code` (`team_code`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `leader_id` (`leader_id`);

--
-- Indexes for table `team_invitations`
--
ALTER TABLE `team_invitations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `invited_by` (`invited_by`),
  ADD KEY `invited_user_id` (`invited_user_id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `team_invitations`
--
ALTER TABLE `team_invitations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_project_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `schedule_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `teams_ibfk_2` FOREIGN KEY (`leader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `team_invitations`
--
ALTER TABLE `team_invitations`
  ADD CONSTRAINT `team_invitations_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_invitations_ibfk_2` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_invitations_ibfk_3` FOREIGN KEY (`invited_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
