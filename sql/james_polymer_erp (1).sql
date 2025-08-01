-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 21, 2025 at 10:09 AM
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
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `name`, `address`) VALUES
(1, 'Plant 1', NULL),
(2, 'Plant 2', NULL),
(3, 'Plant 3', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_materials`
--

CREATE TABLE `product_materials` (
  `product_id` int(11) NOT NULL,
  `raw_material_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_materials`
--

INSERT INTO `product_materials` (`product_id`, `raw_material_id`, `name`, `stock_quantity`, `unit_cost`, `status`, `image_url`, `created_at`, `updated_at`) VALUES
(1, 1, '40 SLX Stand cap', 1000, 12.50, 'Active', NULL, '2025-07-21 03:20:37', '2025-07-21 03:20:37'),
(2, 2, 'GAS KNOB', 800, 8.75, 'Active', NULL, '2025-07-21 03:20:37', '2025-07-21 03:20:37'),
(3, 3, 'SWITCH KNOB', 500, 6.20, 'Active', NULL, '2025-07-21 03:20:37', '2025-07-21 03:20:37'),
(4, 4, 'PLASTIC YELLOW CORE', 1200, 15.00, 'Active', NULL, '2025-07-21 03:20:37', '2025-07-21 03:20:37'),
(5, 5, 'PLASTIC CORE', 950, 10.25, 'Active', NULL, '2025-07-21 03:20:37', '2025-07-21 03:20:37');

-- --------------------------------------------------------

--
-- Table structure for table `raw_materials`
--

CREATE TABLE `raw_materials` (
  `id` int(11) NOT NULL,
  `code_color` varchar(100) NOT NULL COMMENT 'Combination of material code and color',
  `name` varchar(150) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `stock_quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Critical','Low','Normal','Out of Stock') NOT NULL DEFAULT 'Out of Stock',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image1` varchar(255) DEFAULT NULL,
  `image2` varchar(255) DEFAULT NULL,
  `image3` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `raw_materials`
--

INSERT INTO `raw_materials` (`id`, `code_color`, `name`, `category_id`, `location_id`, `stock_quantity`, `status`, `created_at`, `updated_at`, `image1`, `image2`, `image3`) VALUES
(1, 'PP-001 (White)', 'PP PROPILINAS', 1, 1, 15500.00, 'Normal', '2025-07-21 03:20:37', '2025-07-21 03:27:02', NULL, NULL, NULL),
(2, 'NY-002 (Black)', 'NYLON', 1, 1, 0.00, 'Out of Stock', '2025-07-21 03:20:37', '2025-07-21 03:20:37', NULL, NULL, NULL),
(3, 'ABS-003 (White)', 'ABS', 1, 2, 2000.00, 'Normal', '2025-07-21 03:20:37', '2025-07-21 03:27:02', NULL, NULL, NULL),
(4, 'PS-004 (Clear)', 'POLYSTYRENE CLEAR', 1, 2, 500.00, 'Critical', '2025-07-21 03:20:37', '2025-07-21 03:27:02', NULL, NULL, NULL),
(5, 'HIPS-005 (White)', 'HIPS H-IMPACT', 1, 1, 3.00, 'Critical', '2025-07-21 03:20:37', '2025-07-21 05:00:56', NULL, NULL, NULL),
(6, 'RM-007 Brown', 'Kahoy', 1, 2, 4.00, 'Critical', '2025-07-21 03:20:37', '2025-07-21 04:01:16', NULL, NULL, NULL),
(8, 'RM-008 Yellow', 'Straw', 1, 3, 50.00, 'Critical', '2025-07-21 03:20:37', '2025-07-21 03:27:02', 'matimg_6879cd8aa399c.png', 'matimg_6879cd8aa3c06.png', 'matimg_6879cd8aa6e0c.png');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `transaction_id_str` varchar(50) NOT NULL,
  `raw_material_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('IN','OUT') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `balance` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `transaction_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `transaction_id_str`, `raw_material_id`, `product_id`, `user_id`, `type`, `quantity`, `location_id`, `balance`, `notes`, `transaction_date`) VALUES
(1, 'TRX-2025-0530-001', 5, 5, 1, 'OUT', 4.00, 1, 0.00, NULL, '2025-05-30 10:00:00'),
(2, 'TRX-2025-0529-001', 5, 5, 1, 'OUT', 6.00, 1, 4.00, NULL, '2025-05-29 11:00:00'),
(3, 'TRX-2025-0528-001', 5, 5, 1, 'OUT', 5.00, 1, 10.00, NULL, '2025-05-28 14:00:00'),
(4, 'TRX-2025-0527-001', 2, 2, 1, 'OUT', 1.00, 1, 1.00, NULL, '2025-05-27 09:30:00'),
(5, 'TRX-2025-0527-002', 5, 5, 1, 'OUT', 4.00, 1, 70.00, NULL, '2025-05-27 16:00:00'),
(6, 'TRX-20250721-060116-380', 6, 2, 1, 'IN', 2.00, 3, 2.00, '', '2025-07-18 00:00:00'),
(7, 'TRX-20250721-060116-961', 6, 2, 1, 'IN', 2.00, 3, 4.00, '', '2025-07-18 00:00:00'),
(8, 'TRX-20250721-070056-843', 5, 2, 1, 'IN', 3.00, 1, 3.00, '', '2025-07-23 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin1', 'jamespolymer0823', 'Administrator', 'admin', '2025-07-21 03:20:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_materials`
--
ALTER TABLE `product_materials`
  ADD PRIMARY KEY (`product_id`,`raw_material_id`),
  ADD KEY `product_materials_ibfk_2` (`raw_material_id`);

--
-- Indexes for table `raw_materials`
--
ALTER TABLE `raw_materials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code_color` (`code_color`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id_str` (`transaction_id_str`),
  ADD KEY `raw_material_id` (`raw_material_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `location_id` (`location_id`);

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
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `raw_materials`
--
ALTER TABLE `raw_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `product_materials`
--
ALTER TABLE `product_materials`
  ADD CONSTRAINT `product_materials_ibfk_2` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `raw_materials`
--
ALTER TABLE `raw_materials`
  ADD CONSTRAINT `raw_materials_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`id`),
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `transactions_ibfk_4` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
