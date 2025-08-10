-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20250707.de50d366ca
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 10, 2025 at 05:10 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ayuda_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `residents`
--

CREATE TABLE `residents` (
  `id` int NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `age` int NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `address` varchar(255) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `civil_status` enum('Single','Married','Divorced','Widowed') DEFAULT 'Single',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `residents`
--

INSERT INTO `residents` (`id`, `first_name`, `last_name`, `middle_name`, `age`, `gender`, `address`, `barangay`, `contact_number`, `occupation`, `civil_status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Juan', 'Dela Cruz', 'Santos', 35, 'Male', '123 Main St', 'Barangay 1', '09171234567', 'Teacher', 'Married', 2, '2025-08-09 15:23:49', '2025-08-09 15:23:49'),
(2, 'Maria', 'Garcia', 'Lopez', 28, 'Female', '456 Oak Ave', 'Barangay 1', '09181234567', 'Nurse', 'Single', 2, '2025-08-09 15:23:49', '2025-08-09 15:23:49'),
(3, 'Pedro', 'Rodriguez', 'Martinez', 42, 'Male', '789 Pine St', 'Barangay 2', '09191234567', 'Driver', 'Married', 3, '2025-08-09 15:23:49', '2025-08-09 15:23:49'),
(4, 'Ana', 'Gonzales', 'Silva', 31, 'Female', '321 Elm St', 'Barangay 2', '09201234567', 'Store Owner', 'Divorced', 3, '2025-08-09 15:23:49', '2025-08-09 15:23:49'),
(5, 'Carlos', 'Mendoza', 'Cruz', 25, 'Male', '654 Maple Ave', 'Barangay 3', '09211234567', 'Student', 'Single', 4, '2025-08-09 15:23:49', '2025-08-09 15:23:49'),
(6, 'Rosa', 'Fernandez', 'Reyes', 38, 'Female', '987 Cedar Rd', 'Barangay 3', '09221234567', 'Seamstress', 'Widowed', 4, '2025-08-09 15:23:49', '2025-08-09 15:23:49');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `isAdmin` tinyint(1) DEFAULT '0',
  `barangay` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `isAdmin`, `barangay`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$kqJM7P/JjvRtk39/JBEicObAMCaSL1LqxgWoA8tlpD1lIP5yQ/6aC', 1, NULL, '2025-08-09 15:23:49', '2025-08-09 15:25:34'),
(2, 'barangay1_leader', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'Barangay 1', '2025-08-09 15:23:49', '2025-08-09 15:23:49'),
(3, 'barangay2_leader', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'Barangay 2', '2025-08-09 15:23:49', '2025-08-09 15:23:49'),
(4, 'barangay3_leader', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'Barangay 3', '2025-08-09 15:23:49', '2025-08-09 15:23:49'),
(6, 'test_barangay_1754753696561', '$2y$10$ujuLR.wWO8/T7oEXCALUy.kM3GHSgnLkSWinyU6XFZ1t.DOg3yNNe', 0, 'Test Barangay', '2025-08-09 15:34:56', '2025-08-09 15:34:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `residents`
--
ALTER TABLE `residents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `residents`
--
ALTER TABLE `residents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `residents`
--
ALTER TABLE `residents`
  ADD CONSTRAINT `residents_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
