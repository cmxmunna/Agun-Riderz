-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 19, 2025 at 06:51 PM
-- Server version: 10.4.18-MariaDB
-- PHP Version: 8.0.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `agun_riderzz`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `created_by`, `created_at`) VALUES
(1, 'Welcome to Agun Riderzz!', 'Welcome all new members to our motorcycle club. We are excited to have you join our community of passionate riders.', 1, '2025-08-19 10:43:09'),
(2, 'Upcoming Tour Meeting', 'There will be a meeting next Saturday to discuss the upcoming Cox\'s Bazar tour. All interested members are requested to attend.', 1, '2025-08-19 10:43:09'),
(3, 'Safety Guidelines', 'Please remember to always wear helmets and follow traffic rules during our tours. Safety first!', 1, '2025-08-19 10:43:09');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `tour_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `receipt_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tours`
--

CREATE TABLE `tours` (
  `id` int(11) NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destination` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `budget` decimal(10,2) DEFAULT 0.00,
  `max_members` int(11) DEFAULT 20,
  `status` enum('draft','active','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tours`
--

INSERT INTO `tours` (`id`, `title`, `description`, `destination`, `start_date`, `end_date`, `budget`, `max_members`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Cox&#039;s Bazar Adventure', 'A thrilling motorcycle tour to the longest sea beach in the world. Experience the beauty of Cox&#039;s Bazar with fellow riders.', 'Cox&#039;s Bazar', '2024-03-15', '2024-03-18', '15000.00', 15, 'completed', 1, '2025-08-19 10:43:09', '2025-08-19 16:22:22'),
(2, 'Sylhet Tea Garden Tour', 'Explore the beautiful tea gardens of Sylhet. Visit Ratargul Swamp Forest and enjoy the scenic beauty.', 'Sylhet', '2024-04-10', '2024-04-12', '12000.00', 12, 'completed', 1, '2025-08-19 10:43:09', '2025-08-19 16:22:29'),
(3, 'Bandarban Hill Tour', 'Adventure tour to the hills of Bandarban. Visit Nilgiri, Chimbuk Hill, and experience tribal culture.', 'Bandarban', '2024-05-20', '2024-05-23', '18000.00', 10, 'completed', 1, '2025-08-19 10:43:09', '2025-08-19 16:22:35'),
(4, 'Boga Lake Adventure', 'Boga Lage is a lake inside hills. Located in Ruma, Bandarban.', 'Boga Lake, Bandarban', '2025-08-23', '2025-08-27', '7000.00', 8, 'active', 1, '2025-08-19 15:30:11', '2025-08-19 16:22:45');

-- --------------------------------------------------------

--
-- Table structure for table `tour_members`
--

CREATE TABLE `tour_members` (
  `id` int(11) NOT NULL,
  `tour_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','confirmed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facebook_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','member') COLLATE utf8mb4_unicode_ci DEFAULT 'member',
  `profile_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `facebook_id`, `password`, `role`, `profile_image`, `created_at`, `updated_at`) VALUES
(1, 'Shihab Munna', 'cmxmunna@gmail.com', '01627124780', NULL, '$2y$10$RU7sqHUd9z9fbuucHvd9GepglYgmMAw5RABohc32mXf3dsp1VnhwC', 'admin', NULL, '2025-08-19 10:43:09', '2025-08-19 15:23:42'),
(2, 'Farhan Naeem', 'member@agun.com', '01625487452', NULL, '$2y$10$MT/s2IyFmVUwA5U9WENPHO214awdeG3UwJkBDeTc6ho.VnpMxKayG', 'member', NULL, '2025-08-19 16:33:37', '2025-08-19 16:33:37'),
(3, 'Rakib Hasan', 'rakib@agun.com', '01624587850', NULL, '$2y$10$TGMchcR6cm5aSaV.u6.Mzu3ZpVyZrNeuLWGkO6s8dPtWWr8DJFzO2', 'member', NULL, '2025-08-19 16:37:16', '2025-08-19 16:37:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tour_id` (`tour_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_expenses_date` (`date`),
  ADD KEY `idx_expenses_status` (`status`);

--
-- Indexes for table `tours`
--
ALTER TABLE `tours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_tours_start_date` (`start_date`),
  ADD KEY `idx_tours_status` (`status`);

--
-- Indexes for table `tour_members`
--
ALTER TABLE `tour_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tour_member` (`tour_id`,`user_id`),
  ADD KEY `idx_tour_members_tour_id` (`tour_id`),
  ADD KEY `idx_tour_members_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `facebook_id` (`facebook_id`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_phone` (`phone`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tours`
--
ALTER TABLE `tours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tour_members`
--
ALTER TABLE `tour_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tours`
--
ALTER TABLE `tours`
  ADD CONSTRAINT `tours_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tour_members`
--
ALTER TABLE `tour_members`
  ADD CONSTRAINT `tour_members_ibfk_1` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tour_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
