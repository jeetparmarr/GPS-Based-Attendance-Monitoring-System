-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 24, 2025 at 05:59 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gps`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `otp` varchar(20) NOT NULL,
  `otp_verified` tinyint(1) DEFAULT 0,
  `user_type` varchar(20) NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `email`, `password`, `otp`, `otp_verified`, `user_type`, `created_at`) VALUES
(1, 'jeet', 'jeetparmar7722@gmail.com', '$2y$10$RItJrF8dE8TAXIIRicONNu3Egk3SQWTJf0mc4G2dCXMPJJTdivuiq', '236854', 1, 'admin', '2025-03-22 17:24:42'),
(11, 'prit', 'jpjeet3138@gmail.com', '$2y$10$G5sUm6MgP7lAJNxgAXVAd.z8ZxoF.HAuqij3smgT/PtscxWmGWozK', '', 1, 'admin', '2025-03-24 15:32:12');

-- --------------------------------------------------------

--
-- Table structure for table `check_in`
--

CREATE TABLE `check_in` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(255) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `latitude` varchar(255) NOT NULL,
  `longitude` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `check_in_time` datetime DEFAULT NULL,
  `distance` varchar(50) DEFAULT 'N/A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `check_in`
--

INSERT INTO `check_in` (`id`, `employee_id`, `employee_name`, `email`, `latitude`, `longitude`, `address`, `check_in_time`, `distance`) VALUES
(1, '118', 'jeet', 'jeetparmar7722@gmail.com', '22.9843474', '72.6147774', 'Bandhan CHS, 36-B, Ghodasar, Ahmedabad 380050, India', '2025-03-24 17:55:43', 'N/A');

-- --------------------------------------------------------

--
-- Table structure for table `check_out`
--

CREATE TABLE `check_out` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `latitude` varchar(50) NOT NULL,
  `longitude` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `check_time` datetime NOT NULL,
  `distance` varchar(50) DEFAULT 'N/A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `check_out`
--

INSERT INTO `check_out` (`id`, `employee_id`, `employee_name`, `email`, `latitude`, `longitude`, `address`, `check_time`, `distance`) VALUES
(1, '118', 'jeet', 'jeetparmar7722@gmail.com', '22.9843474', '72.6147774', 'Bandhan CHS, 36-B, Ghodasar, Ahmedabad 380050, India', '2025-03-24 17:56:41', 'N/A');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `otp` varchar(20) DEFAULT NULL,
  `otp_verified` tinyint(1) DEFAULT 0,
  `first_login` tinyint(1) DEFAULT 1,
  `user_type` varchar(20) NOT NULL DEFAULT 'employee',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_id`, `employee_name`, `email`, `password`, `otp`, `otp_verified`, `first_login`, `user_type`, `created_at`) VALUES
(11, '118', 'jeet', 'jeetparmar7722@gmail.com', '$2y$10$QfYWIZANeXQFEIT.qACO/ubGp4PIEbWJ/0sORlmAZHbFxlwk7qvy2', NULL, 1, 1, 'employee', '2025-03-24 15:37:46');

-- --------------------------------------------------------

--
-- Table structure for table `security_password`
--

CREATE TABLE `security_password` (
  `id` int(11) NOT NULL,
  `code_type` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `security_password`
--

INSERT INTO `security_password` (`id`, `code_type`, `password`, `created_at`) VALUES
(1, 'admin_registration', 'user@123', '2025-03-22 17:24:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `check_in`
--
ALTER TABLE `check_in`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `check_out`
--
ALTER TABLE `check_out`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `security_password`
--
ALTER TABLE `security_password`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `check_in`
--
ALTER TABLE `check_in`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `check_out`
--
ALTER TABLE `check_out`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `security_password`
--
ALTER TABLE `security_password`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
