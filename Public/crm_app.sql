-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 04, 2025 at 07:33 AM
-- Server version: 8.0.30
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `crm_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `crm`
--

CREATE TABLE `crm` (
  `company_email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `marketing_id` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `marketing_person_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `person_email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name_person` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contact_person_position_title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_number2` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company_website` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company_category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contact_person_position_category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `postcode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('input','wa','emailed','contacted','replied','presentation','CLIENT') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'input',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `crm`
--

INSERT INTO `crm` (`company_email`, `marketing_id`, `marketing_person_name`, `person_email`, `company_name`, `name_person`, `contact_person_position_title`, `phone_number`, `phone_number2`, `company_website`, `company_category`, `contact_person_position_category`, `company_type`, `address`, `city`, `postcode`, `status`, `created_at`, `updated_at`) VALUES
('Antara@gmail.com', 'FRL', 'Farelfadlillah', 'Example@gmail.com', 'PT. Anatara', 'Bpk ...', 'IT SUPPORT', '0811...', '', 'antara.com', 'Public', 'IT', 'insurence', 'Jl...', 'Jakarta', '10410', 'emailed', '2025-08-27 05:16:26', '2025-08-27 05:16:39'),
('rayterton@gmail.con', 'BTG', NULL, 'tes@gmail.com', 'PT Repliaa', 'Kangkung', 'PM', '0887741241', '', 'digidaw', 'iya', 'iyadeh', 'Mobil', 'JL iya', 'Jakarta', '103020', 'input', '2025-09-04 06:00:22', '2025-09-04 07:25:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `marketing_id` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
--
-- Dumping data for table `users`
--

INSERT INTO `users` (`marketing_id`, `name`, `email`, `password`, `created_at`) VALUES
('AGS', 'Agusti Bahtiar', 'agusti@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('ASA', 'Aisyah Ratna Aulia', 'aisyah@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('BRY', 'Bryan Syahputra', 'bryan@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('BTG', 'Bintang Rayvan', 'bintang@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('FDL', 'Fadhal Nurul Azmi', 'fadhal@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('FRL', 'Farelfadlillah', 'farel@rtn.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-27 04:36:07'),
('FZL', 'Fazle Adrevi Bintang Al Farrel', 'fazle@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('HDN', 'Hildan Argiansyah', 'hildan@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('HFA', 'Haikal Fakhri Agnitian', 'haikal@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('HNA', 'Halena Maheswari Viehandini', 'halena@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('HNF', 'Hannif Fahmy Fadilah', 'hannif@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('IBL', 'Iqbal Hadi Mustafa', 'iqbal@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('ILY', 'Firyal Dema Elputri', 'firyal@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('JMH', 'Joshua Matthew Hendra', 'joshua@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('KIY', 'Kirana Firjal Attakhira', 'kirana@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('KYD', 'Kurniawan Yafi Djayakusuma', 'kurniawan@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('MST', 'Marsya Safeena Tama', 'marsya@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('NBL', 'Muhammad Nabil', 'nabil@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('PRS', 'Prasetyo Adi', 'prasetyo@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('PTR', 'Rizky Putra Hadi Sarwono', 'putra@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('RAD', 'Ristyo Arditto', 'ristyo@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('RAF', 'Shaquille Raffalea', 'raffa@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('RBO', 'Rhomie Bireuno', 'rhomie@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('RFK', 'Rifki Alhaqi', 'rifki@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('RSV', 'Achmad Wafiq Risvyan', 'risvyan@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('RZY', 'Muhammad Rizky', 'rizky@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('SYA', 'Rasya Al Zikri', 'rasya@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('VIN', 'Kevin Revaldo', 'kevin@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('WLD', 'Muhammad Wildan Ichsanul Akbar', 'muhammad@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('ZFR', 'Zafira Marvella Rae', 'zafira@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52'),
('ZID', 'Ahmad Zidan', 'zidan@rayterton.com', '$2y$10$oM1L2.rdmilz70hafNTiz.Gm.q8J6wj3ZDTjo.My6yi4WdltIX0Du', '2025-08-28 10:26:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `crm`
--
ALTER TABLE `crm`
  ADD PRIMARY KEY (`company_email`),
  ADD KEY `marketing_id` (`marketing_id`),
  ADD KEY `company_email` (`company_email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`marketing_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `crm`
--
ALTER TABLE `crm`
  ADD CONSTRAINT `crm_ibfk_1` FOREIGN KEY (`marketing_id`) REFERENCES `users` (`marketing_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
