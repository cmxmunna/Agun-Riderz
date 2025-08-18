-- Agun Riderzz Database Setup
-- Created for Purbachal Agun Riderzz Tour Management System

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `agun_riderzz` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Use the database
USE `agun_riderzz`;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) UNIQUE,
    `phone` VARCHAR(20) UNIQUE,
    `facebook_id` VARCHAR(100) UNIQUE,
    `password` VARCHAR(255),
    `role` ENUM('admin', 'member') DEFAULT 'member',
    `profile_image` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tours table
CREATE TABLE IF NOT EXISTS `tours` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `destination` VARCHAR(200) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `budget` DECIMAL(10,2) DEFAULT 0,
    `max_members` INT DEFAULT 20,
    `status` ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'draft',
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tour members table
CREATE TABLE IF NOT EXISTS `tour_members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tour_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `status` ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`tour_id`) REFERENCES `tours`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_tour_member` (`tour_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Expenses table
CREATE TABLE IF NOT EXISTS `expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tour_id` INT,
    `user_id` INT NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `date` DATE NOT NULL,
    `receipt_image` VARCHAR(255),
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `approved_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tour_id`) REFERENCES `tours`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Announcements table
CREATE TABLE IF NOT EXISTS `announcements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `content` TEXT NOT NULL,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`) VALUES 
('Admin', 'admin@agunriderzz.com', '01700000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE `id` = `id`;

-- Insert sample tours
INSERT INTO `tours` (`title`, `description`, `destination`, `start_date`, `end_date`, `budget`, `max_members`, `status`, `created_by`) VALUES
('Cox\'s Bazar Adventure', 'A thrilling motorcycle tour to the longest sea beach in the world. Experience the beauty of Cox\'s Bazar with fellow riders.', 'Cox\'s Bazar', '2024-03-15', '2024-03-18', 15000.00, 15, 'active', 1),
('Sylhet Tea Garden Tour', 'Explore the beautiful tea gardens of Sylhet. Visit Ratargul Swamp Forest and enjoy the scenic beauty.', 'Sylhet', '2024-04-10', '2024-04-12', 12000.00, 12, 'active', 1),
('Bandarban Hill Tour', 'Adventure tour to the hills of Bandarban. Visit Nilgiri, Chimbuk Hill, and experience tribal culture.', 'Bandarban', '2024-05-20', '2024-05-23', 18000.00, 10, 'draft', 1)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- Insert sample announcements
INSERT INTO `announcements` (`title`, `content`, `created_by`) VALUES
('Welcome to Agun Riderzz!', 'Welcome all new members to our motorcycle club. We are excited to have you join our community of passionate riders.', 1),
('Upcoming Tour Meeting', 'There will be a meeting next Saturday to discuss the upcoming Cox\'s Bazar tour. All interested members are requested to attend.', 1),
('Safety Guidelines', 'Please remember to always wear helmets and follow traffic rules during our tours. Safety first!', 1)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- Create indexes for better performance
CREATE INDEX `idx_users_email` ON `users`(`email`);
CREATE INDEX `idx_users_phone` ON `users`(`phone`);
CREATE INDEX `idx_tours_start_date` ON `tours`(`start_date`);
CREATE INDEX `idx_tours_status` ON `tours`(`status`);
CREATE INDEX `idx_expenses_date` ON `expenses`(`date`);
CREATE INDEX `idx_expenses_status` ON `expenses`(`status`);
CREATE INDEX `idx_tour_members_tour_id` ON `tour_members`(`tour_id`);
CREATE INDEX `idx_tour_members_user_id` ON `tour_members`(`user_id`);

-- Show success message
SELECT 'Agun Riderzz database setup completed successfully!' AS message;
