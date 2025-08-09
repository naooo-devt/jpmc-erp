-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 09, 2025 at 02:50 AM
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
-- Database: `james_polymer_erp`
--

-- --------------------------------------------------------

--
-- Table structure for table `income`
--

CREATE TABLE `income` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `source` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('Received','Pending') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `income`
--

INSERT INTO `income` (`id`, `date`, `source`, `description`, `amount`, `status`, `created_at`, `updated_at`) VALUES
(1, '2025-04-05', 'Service Revenue', '0', 266.43, 'Pending', '2025-07-31 03:18:34', '2025-07-31 03:18:34'),
(2, '2025-02-06', 'Licensing', '0', 315.77, 'Received', '2025-07-31 03:18:34', '2025-07-31 03:18:34'),
(3, '2024-09-27', 'Product Sales', '0', 241.66, 'Pending', '2025-07-31 03:18:34', '2025-07-31 03:18:34'),
(4, '2024-12-19', 'Licensing', '0', 246.45, 'Pending', '2025-07-31 03:18:34', '2025-07-31 03:18:34'),
(5, '2025-07-21', 'Maintenance Contracts', '0', 127.44, 'Pending', '2025-07-31 03:18:34', '2025-07-31 03:18:34'),
(6, '2025-01-30', 'Maintenance Contracts', '0', 43.73, 'Received', '2025-07-31 03:18:34', '2025-07-31 03:18:34'),
(7, '2025-02-05', 'Maintenance Contracts', '0', 438.23, 'Received', '2025-07-31 03:18:34', '2025-07-31 03:18:34'),
(8, '2025-05-18', 'Licensing', '0', 224.71, 'Pending', '2025-07-31 03:18:35', '2025-07-31 03:18:35'),
(9, '2025-04-02', 'Product Sales', '0', 129.86, 'Pending', '2025-07-31 03:18:35', '2025-07-31 03:18:35'),
(10, '2025-02-25', 'Consultation', '0', 492.80, 'Pending', '2025-07-31 03:18:35', '2025-07-31 03:18:35'),
(11, '2024-08-18', 'Service Revenue', '0', 66.23, 'Received', '2025-07-31 03:18:35', '2025-07-31 03:18:35'),
(12, '2024-09-27', 'Service Revenue', '0', 441.37, 'Pending', '2025-07-31 03:18:35', '2025-07-31 03:18:35'),
(13, '2025-06-16', 'Licensing', '0', 110.21, 'Pending', '2025-07-31 03:18:35', '2025-07-31 03:18:35'),
(14, '2024-12-01', 'Licensing', '0', 231.78, 'Received', '2025-07-31 03:18:35', '2025-07-31 03:18:35'),
(15, '2025-05-30', 'Licensing', '0', 422.62, 'Pending', '2025-07-31 03:18:35', '2025-07-31 03:18:35'),
(16, '2025-05-25', 'Consultation', '0', 345.30, 'Pending', '2025-07-31 03:18:35', '2025-07-31 03:18:35'),
(17, '2024-09-05', 'Licensing', '0', 412.97, 'Received', '2025-07-31 03:18:35', '2025-07-31 03:18:35'),
(18, '2024-10-20', 'Product Sales', '0', 329.64, 'Pending', '2025-07-31 03:18:35', '2025-07-31 03:18:35'),
(19, '2024-12-19', 'Product Sales', '0', 329.92, 'Received', '2025-07-31 03:18:35', '2025-07-31 03:18:35'),
(20, '2024-12-29', 'Consultation', '0', 425.47, 'Pending', '2025-07-31 03:18:35', '2025-07-31 03:18:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `income`
--
ALTER TABLE `income`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `income`
--
ALTER TABLE `income`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
