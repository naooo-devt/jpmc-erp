-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 08, 2025 at 09:43 AM
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
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `part_no` varchar(50) DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `net_price` decimal(10,2) DEFAULT NULL,
  `sales_tax` decimal(10,2) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`id`, `purchase_order_id`, `description`, `part_no`, `delivery_date`, `quantity`, `net_price`, `sales_tax`, `amount`) VALUES
(2, 2, 'Rubber', '2032', '2025-08-15', 5, 0.00, 20.00, 2300.00),
(3, 2, 'plastic', '0012', '2025-08-15', 13, 100.00, 200.00, 3000.00),
(4, 3, 'Sap', '0023', '2025-08-30', 100, 200.00, 500.00, 1000.00),
(5, 3, 'Tree', '0000', '2025-08-30', 1, 10.00, 20.00, 500.00),
(8, 1, 'French Fries', '0001', '2025-08-06', 20, 0.00, 0.00, 230.00),
(9, 1, 'Sap', '0023', '2025-08-30', 100, 200.00, 500.00, 1000.00),
(10, 4, 'Cheese', '9213', '2025-08-20', 100, 20.00, 10.00, 1000.00),
(11, 5, 'rgr', '123', '2025-08-09', 1, 123.00, 123.00, 213.00),
(12, 6, 'rope', '123', '2025-08-08', 100, 500.00, 1000.00, 99999999.99),
(13, 7, 'nylon', '123', '2025-08-09', 65, 35.00, 34.00, 3265.00),
(16, 8, 'rope', '123', '2025-08-08', 100, 500.00, 1000.00, 99999999.99);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_id` (`purchase_order_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders_sample` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
