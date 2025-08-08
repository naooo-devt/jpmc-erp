-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 08, 2025 at 09:42 AM
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
-- Table structure for table `purchase_orders_sample`
--

CREATE TABLE `purchase_orders_sample` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `order_date` date NOT NULL,
  `terms` varchar(100) DEFAULT NULL,
  `ship_via` varchar(100) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `part_no` varchar(50) DEFAULT NULL,
  `delivery_date` varchar(100) DEFAULT NULL,
  `net_price` decimal(10,2) DEFAULT NULL,
  `sales_tax` decimal(10,2) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `conforme` varchar(100) DEFAULT NULL,
  `prepared_by` varchar(100) DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `accounting` varchar(100) DEFAULT NULL,
  `manager` varchar(100) DEFAULT NULL,
  `quantity` varchar(100) DEFAULT NULL,
  `status` enum('Pending','For-Delivery','Cancelled') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders_sample`
--

INSERT INTO `purchase_orders_sample` (`id`, `order_number`, `order_date`, `terms`, `ship_via`, `supplier_id`, `description`, `part_no`, `delivery_date`, `net_price`, `sales_tax`, `amount`, `total_amount`, `conforme`, `prepared_by`, `approved_by`, `accounting`, `manager`, `quantity`, `status`) VALUES
(1, '001', '2025-08-06', '20', 'Air', 1, NULL, NULL, NULL, NULL, NULL, NULL, 1230.00, 'asd', 'wde', 'sedf', 'dsf', 'sd', NULL, 'Pending'),
(2, '002', '2025-08-07', '30 Days', 'LBC', 4, NULL, NULL, NULL, NULL, NULL, NULL, 5300.00, '', '', '', '', '', NULL, 'Pending'),
(3, '003', '2025-08-07', '20', 'Land', 1, NULL, NULL, NULL, NULL, NULL, NULL, 1500.00, '', '', '', '', '', NULL, 'Cancelled'),
(4, '004', '2025-08-08', '30', 'Pokemon', 4, NULL, NULL, NULL, NULL, NULL, NULL, 1000.00, 'Gomez', 'Robin', 'Jenny', 'Jenny', 'James', NULL, 'Pending'),
(5, '005', '2025-08-08', '10', 'lamd', 1, NULL, NULL, NULL, NULL, NULL, NULL, 213.00, 'asd', 'qwe', 'E', 'QW', 'mH8IRA', NULL, 'Pending'),
(6, '006', '2025-08-08', '99', 'Rocket', 5, NULL, NULL, NULL, NULL, NULL, NULL, 100000000.00, 'ASd', 'asd', 'asd', 'SAD', 'asd', NULL, 'Pending'),
(7, '007', '2025-08-08', '30', 'Pokemon', 1, NULL, NULL, NULL, NULL, NULL, NULL, 3265.00, 'xfghjj', 'gfhghjjlk', 'fghhjkl', 'fghhjklk', 'James', NULL, 'Pending'),
(8, '008', '2025-08-08', '99', 'Shippppppppppp', 2, NULL, NULL, NULL, NULL, NULL, NULL, 99999999.99, 'ASd', 'asd', 'asd', 'SAD', 'asd', NULL, 'Pending');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `purchase_orders_sample`
--
ALTER TABLE `purchase_orders_sample`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `purchase_orders_sample`
--
ALTER TABLE `purchase_orders_sample`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `purchase_orders_sample`
--
ALTER TABLE `purchase_orders_sample`
  ADD CONSTRAINT `purchase_orders_sample_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
