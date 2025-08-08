-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 08, 2025 at 09:41 AM
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
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` int(11) NOT NULL,
  `quotation_no` varchar(50) NOT NULL,
  `quotation_date` date NOT NULL,
  `attention_to` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(150) DEFAULT NULL,
  `item` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total` decimal(12,2) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL,
  `vat` decimal(12,2) DEFAULT NULL,
  `grand_total` decimal(12,2) DEFAULT NULL,
  `product_name` varchar(100) DEFAULT NULL,
  `product_image_path` varchar(255) DEFAULT NULL,
  `validity_days` int(11) DEFAULT NULL,
  `delivery_days` int(11) DEFAULT NULL,
  `sender_company` varchar(150) DEFAULT NULL,
  `sender_name` varchar(100) DEFAULT NULL,
  `sender_position` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `quotation_no`, `quotation_date`, `attention_to`, `position`, `company_name`, `address`, `contact_number`, `email`, `website`, `item`, `description`, `qty`, `unit`, `unit_price`, `total`, `subtotal`, `vat`, `grand_total`, `product_name`, `product_image_path`, `validity_days`, `delivery_days`, `sender_company`, `sender_name`, `sender_position`, `created_at`, `status`) VALUES
(27, 'JPMC 2025-08-0001', '2025-08-07', 'Kenneth Gabren E. Oakes', 'CEO', 'Geronimo Corp.', '456 Brgy. Street', '09454632234', 'leah@gmail.com', 'trt', 'rope', 'long', 3000, 'inches', 50.00, 150000.00, 150000.00, 18000.00, 168000.00, 'rope', 'uploads/MEAN AND VARIANCE OF DISCRETE VARIABLES..jpg', 30, 21, 'James Polymers Mfg.', 'Greg Yors', 'HR ', '2025-08-07 05:45:11', 'Approved'),
(28, 'JPMC 2025-08-0002', '2025-08-07', 'sfgsg', 'bfzb', 'fbz', '456 Brgy. Street', '09454632234', 'leah@gmail.com', 'bfd', 'nylon', 'rgr', 64, 'feet', 42.00, 2688.00, 2688.00, 322.56, 3010.56, 'nylon', 'uploads/withgrandma.jpg', 30, 21, 'James Polymers Mfg.', 'ty', 'etyet', '2025-08-07 06:17:23', 'Approved'),
(29, 'JPMC 2025-08-0003', '2025-08-07', 'Kenneth Gabren E. Oakes', 'CEO', 'Geronimo Corp.', '456 Brgy. Street', '09454632234', 'leah@gmail.com', 'bfd', 'nylon', 'rgr', 64, 'feet', 42.00, 2688.00, 2688.00, 322.56, 3010.56, 'nylon', 'uploads/260489562_269254361924406_1772521474463228092_n.jpg', 30, 21, 'James Polymers Mfg.', 'ty', 'etyet', '2025-08-07 07:20:58', 'Rejected'),
(30, 'JPMC 2025-08-0004', '2025-08-07', 'MS. MHIRA SHANE O. PATO', 'CEO', 'Shane Corp.', '228 TOCLONG, KAWIT, CAVITE', '09070569669', 'rawr@gmail.com', 'N/A', 'nylon', 'rgr', 64, 'feet', 42.00, 2688.00, 2688.00, 322.56, 3010.56, 'nylon', 'uploads/Untitled-1.png', 30, 21, 'James Polymers Mfg.', 'ty', 'etyet', '2025-08-07 08:00:05', 'Rejected'),
(31, 'JPMC 2025-08-0005', '2025-08-06', 'John Cena', 'brawler', 'WWE', 'You can\'t See Me', 'Can\'t See', 'Can\'tSee@gmail.com', 'N/A', 'Ladder', '3 Steps', 1, 'millimeters', 123.00, 123.00, 123.00, 14.76, 137.76, 'Ladder', 'uploads/matimg_687d986aab7d6.png', 1, 21, 'James Polymers Mfg.', 'sad', 'sd', '2025-08-08 06:19:31', 'Pending'),
(32, 'JPMC 2025-08-0006', '2025-08-08', 'Claire Masion', 'Secretary', 'Tycoon Corp', 'hhrdjdryrdj\r\njbxhjXhGXHXbKZHX\r\nJuahxjax', '09876787678', 'fdjhfs@huahdu-id', NULL, 'rope', 'getg', 67, 'pcs', 55.00, 3685.00, 3685.00, 442.20, 4127.20, 'rope', 'uploads/matimg_6879d5ef4db09.png', 30, 21, 'James Polymers Mfg.', 'fgsg', 'sgsfgs', '2025-08-08 06:37:58', 'Approved'),
(33, 'JPMC 2025-08-0007', '2025-08-08', 'grgrg', 'rgw', 'gwrgrw', 'grrwgw', 'rgw', 'wgrg@vfvsb', 'grwg', 'wrg', 'gr', 4, 'meters', 56.00, 224.00, 224.00, 26.88, 250.88, 'wrg', 'uploads/hips2.png', 30, 21, 'James Polymers Mfg.', 'fdf', 'dsf', '2025-08-08 07:01:20', 'Pending');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quotation_no` (`quotation_no`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
