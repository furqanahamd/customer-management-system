-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 20, 2025 at 11:16 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14


SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `customer_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `auth_user`
--

DROP TABLE IF EXISTS `auth_user`;
CREATE TABLE IF NOT EXISTS `auth_user` (
  `Role_Name` varchar(20) DEFAULT NULL,
  `Role_ID` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`Role_ID`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `auth_user`
--

INSERT INTO `auth_user` (`Role_Name`, `Role_ID`) VALUES
('Admin', 1),
('Manager', 2),
('Viewer', 3);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `firstname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `lastname` varchar(50) NOT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `role` enum('admin','editor','viewer') NOT NULL DEFAULT 'viewer',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `firstname`, `email`, `phone`, `created_at`, `lastname`, `city`, `state`, `pincode`, `profile_picture`, `dob`, `role`, `updated_at`, `active`) VALUES
(4, 'Under', 'undertaker@gmail.com', '+917756844682', '2025-08-08 04:46:09', 'Taker', 'Aligarh', 'Uttar Pradesh', '202001', '1754628369_319034833_610884284172185_3254890395919304751_n.jpg', '1991-11-13', 'viewer', '2025-08-20 03:34:24', 1),
(5, 'Dywane', 'therock@gmail.com', '+917756842520', '2025-08-08 05:46:21', 'Johnson', '', '', '158165', '1754632456_willian-justen-de-vasconcellos-4hMET7vYTAQ-unsplash.jpg', '1992-07-17', 'viewer', '2025-08-18 09:03:45', 1),
(7, 'Waseem', 'waseemkhan@gmail.com', '+919999999999', '2025-08-08 09:57:27', 'Khan', 'Agra', 'Uttar Pradesh', '2147483647', '1754647047_319503614_610884344172179_4358410199159242393_n.jpg', '2025-09-23', 'viewer', '2025-08-18 07:46:11', 1),
(10, 'Harry', 'HarryPotter@gmail.com', '+917756844682', '2025-08-14 10:11:20', 'Potter', 'Rajouri', 'Jammu and Kashmir', '158165', NULL, '1995-10-15', 'viewer', '2025-08-18 09:50:09', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `firstname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Role_ID` int DEFAULT NULL,
  `lastname` varchar(50) NOT NULL,
  `profile_picture` varchar(255) DEFAULT 'default.jpeg',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `email_2` (`email`),
  KEY `fk_role_id` (`Role_ID`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `email`, `password`, `is_active`, `created_at`, `Role_ID`, `lastname`, `profile_picture`) VALUES
(6, 'Furqan', 'fur4796@gmail.com', '$2y$10$e8vwyzrQwtBaIU8w1oLMjuZCne95Pf17xjs22Pa67aTJxiy4QQvfm', 1, '2025-08-11 07:36:00', 1, 'Ahmad', '689c2aaa5082b_enes-f-DvU93UhTs-unsplash.jpg'),
(8, 'Halludba', 'mail@AbdullahAnsari.com', '$2y$10$7/JYJDZZiGMGvcJKfhfRdu0juDHFH7R7fg6m64RWvDNFY9D93jeTm', 1, '2025-08-12 04:57:02', 1, 'Irasna', 'default.jpeg'),
(7, 'Mohammad', 'mofazal987@gmail.com', '$2y$10$pWqAwAg7wi1UtMi.07YJzOuiQMBEsLCxYUgyKbjuTFHnbcf3PbDxy', 1, '2025-08-11 09:20:04', 3, 'Fazal', 'default.jpeg'),
(5, 'Roman', 'romanreigns@gmail.com', '$2y$10$dm3C3UrGnAQDDCsOb4ExhOe/GcNX2ZrzdsJw6jQR.1Tf6LB7j7qb6', 1, '2025-08-11 07:27:28', 2, 'Reigns', 'default.jpeg'),
(9, 'Seth', 'Seth@Rollins.in', '$2y$10$V9kdnyPnrRjUJSpXYHcj2.0z3dIEDnP0CKdFH7WcVbXFFe8/szvra', 1, '2025-08-13 06:21:20', 1, 'Rollins', '689c2ff07148f_24-10-26_Square_GALLERY360_Seth_Rollins_7564_Profile--d7425c5ddc7d4230400deeb639fd1db8.png');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
=======
CREATE DATABASE IF NOT EXISTS customer_management;
USE customer_management;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'editor', 'viewer') NOT NULL DEFAULT 'viewer',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);



