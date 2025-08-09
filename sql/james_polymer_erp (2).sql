-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 09, 2025 at 02:54 AM
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
-- Table structure for table `budget`
--

CREATE TABLE `budget` (
  `id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `allocated` decimal(10,2) NOT NULL,
  `spent` decimal(10,2) DEFAULT 0.00,
  `remaining` decimal(10,2) GENERATED ALWAYS AS (`allocated` - `spent`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget`
--

INSERT INTO `budget` (`id`, `category`, `allocated`, `spent`, `created_at`, `updated_at`) VALUES
(1, 'Raw Materials', 903.19, 5.46, '2025-07-31 03:18:36', '2025-07-31 03:18:36'),
(2, 'Equipment', 537.65, 0.04, '2025-07-31 03:18:36', '2025-07-31 03:18:36'),
(3, 'Utilities', 442.82, 1.11, '2025-07-31 03:18:36', '2025-07-31 03:18:36'),
(4, 'Logistics', 842.57, 5.63, '2025-07-31 03:18:36', '2025-07-31 03:18:36'),
(5, 'Maintenance', 399.39, 0.74, '2025-07-31 03:18:36', '2025-07-31 03:18:36'),
(6, 'Marketing', 528.47, 2.62, '2025-07-31 03:18:36', '2025-07-31 03:18:36'),
(7, 'Office Supplies', 720.69, 4.51, '2025-07-31 03:18:36', '2025-07-31 03:18:36'),
(8, 'Insurance', 317.54, 2.11, '2025-07-31 03:18:36', '2025-07-31 03:18:36'),
(9, 'Training', 375.49, 0.53, '2025-07-31 03:18:36', '2025-07-31 03:18:36'),
(10, 'Software', 362.88, 2.02, '2025-07-31 03:18:36', '2025-07-31 03:18:36');

-- --------------------------------------------------------

--
-- Table structure for table `calendar_schedule_note_history`
--

CREATE TABLE `calendar_schedule_note_history` (
  `id` int(11) NOT NULL,
  `note_id` varchar(50) NOT NULL,
  `note_date` date NOT NULL,
  `note_text` text NOT NULL,
  `user` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `calendar_schedule_note_history`
--

INSERT INTO `calendar_schedule_note_history` (`id`, `note_id`, `note_date`, `note_text`, `user`, `created_at`) VALUES
(2, '', '2025-08-29', 'Added note for date: 2025-08-29 (Note: ghgjhgh)', 'System', '2025-08-08 14:58:15'),
(3, '', '2025-08-29', 'Edited note ID: 1 (New note: hbhj)', 'System', '2025-08-08 14:58:39'),
(4, '', '2025-08-08', 'Deleted note ID: 1 (Original note: hbhj)', 'System', '2025-08-08 14:58:47');

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `id` int(11) NOT NULL,
  `delivery_number` varchar(50) NOT NULL,
  `purchase_order_id` int(11) NOT NULL,
  `delivery_date` date NOT NULL,
  `received_date` date DEFAULT NULL,
  `status` enum('Scheduled','In Transit','Delivered','Cancelled') DEFAULT 'Scheduled',
  `carrier` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `emp_status` enum('PROBATIONARY','REGULAR') NOT NULL,
  `appointed_as` varchar(100) NOT NULL,
  `birthdate` varchar(30) NOT NULL,
  `civil_status` varchar(20) NOT NULL,
  `address` varchar(150) NOT NULL,
  `image` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `position`, `mobile`, `emp_status`, `appointed_as`, `birthdate`, `civil_status`, `address`, `image`) VALUES
('JPMC-HRD-025', 'ROBIN NOMBRADO', 'PROCESS ENGINEER', '0945-2737-786', 'PROBATIONARY', 'SAFETY OFFICER/ MAINTENANCE OIC', 'APRIL 30, 1992', 'SINGLE', 'SILVERSTOWN 4, IMUS CAVITE', 'images/abs1.png'),
('JPMC-HRD-026', 'JONATHAN RAY. ANTIONIO', 'QA SUPERVISOR', '0912-3456-789', 'REGULAR', 'QUALITY CONTROL HEAD', 'MARCH 15, 1988', 'MARRIED', 'BACOOR, CAVITE', 'images/abs2.png'),
('JPMC-HRD-027', 'ALBERT B. ALACAPA', 'WAREHOUSEMAN', '0923-4567-890', 'REGULAR', 'INVENTORY CONTROLLER', 'JULY 22, 1990', 'SINGLE', 'DASMARIÑAS, CAVITE', 'images/abs3.png'),
('JPMC-HRD-028', 'MARDY AGUILAR', 'MOLD FABRICATOR', '0934-5678-901', 'REGULAR', 'PRODUCTION SPECIALIST', 'SEPTEMBER 8, 1985', 'MARRIED', 'GENERAL TRIAS, CAVITE', 'images/hips1.png'),
('JPMC-HRD-029', 'JOHN BRYAN FERRER', 'IT SUPERVISOR', '0945-6789-012', 'REGULAR', 'SYSTEMS ADMINISTRATOR', 'DECEMBER 3, 1987', 'SINGLE', 'IMUS, CAVITE', 'images/hips2.png'),
('JPMC-HRD-030', 'ANABEL E. PUNINCIAR', 'MACHINE OPERATOR', '0956-7890-123', 'PROBATIONARY', 'PRODUCTION OPERATOR', 'MAY 12, 1993', 'SINGLE', 'SILANG, CAVITE', 'images/hips3.png'),
('JPMC-HRD-031', 'RICKY V. TONGOL', 'MOLD FABRICATOR', '0967-8901-234', 'REGULAR', 'TECHNICAL SPECIALIST', 'AUGUST 19, 1989', 'MARRIED', 'TRECE MARTIRES, CAVITE', 'images/nylon1.png'),
('JPMC-HRD-032', 'VIRGINIA M. BRUN', 'MACHINE OPERATOR', '0978-9012-345', 'REGULAR', 'PRODUCTION OPERATOR', 'JANUARY 25, 1991', 'SINGLE', 'NAIC, CAVITE', 'images/nylon2.png'),
('JPMC-HRD-033', 'ZALDY P. ALFON', 'PRINTING OPERATOR', '0989-0123-456', 'REGULAR', 'PRINTING SPECIALIST', 'NOVEMBER 7, 1986', 'MARRIED', 'TANZA, CAVITE', 'images/nylon3.png');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('Paid','Pending') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `date`, `category`, `description`, `amount`, `status`, `created_at`, `updated_at`) VALUES
(1, '2025-02-06', 'Raw Materials', '0', 215.05, 'Paid', '2025-07-31 03:18:32', '2025-07-31 03:18:32'),
(2, '2025-07-20', 'Utilities', '0', 96.73, 'Paid', '2025-07-31 03:18:32', '2025-07-31 03:18:32'),
(3, '2024-10-21', 'Training', '0', 240.46, 'Paid', '2025-07-31 03:18:32', '2025-07-31 03:18:32'),
(4, '2025-01-10', 'Office Supplies', '0', 352.14, 'Pending', '2025-07-31 03:18:32', '2025-07-31 03:18:32'),
(5, '2025-02-24', 'Training', '0', 377.50, 'Pending', '2025-07-31 03:18:32', '2025-07-31 03:18:32'),
(6, '2024-11-09', 'Insurance', '0', 259.50, 'Pending', '2025-07-31 03:18:33', '2025-07-31 03:18:33'),
(7, '2024-11-15', 'Maintenance', '0', 113.15, 'Pending', '2025-07-31 03:18:33', '2025-07-31 03:18:33'),
(8, '2024-10-21', 'Marketing', '0', 198.82, 'Paid', '2025-07-31 03:18:33', '2025-07-31 03:18:33'),
(9, '2024-09-12', 'Maintenance', '0', 256.51, 'Paid', '2025-07-31 03:18:33', '2025-07-31 03:18:33'),
(10, '2025-01-31', 'Raw Materials', '0', 282.74, 'Paid', '2025-07-31 03:18:33', '2025-07-31 03:18:33'),
(11, '2025-02-18', 'Training', '0', 401.88, 'Pending', '2025-07-31 03:18:33', '2025-07-31 03:18:33'),
(12, '2025-03-01', 'Training', '0', 376.75, 'Paid', '2025-07-31 03:18:33', '2025-07-31 03:18:33'),
(13, '2025-01-27', 'Equipment', '0', 285.71, 'Paid', '2025-07-31 03:18:33', '2025-07-31 03:18:33'),
(14, '2025-07-25', 'Raw Materials', '0', 192.52, 'Pending', '2025-07-31 03:18:33', '2025-07-31 03:18:33'),
(15, '2024-10-03', 'Office Supplies', '0', 56.92, 'Paid', '2025-07-31 03:18:33', '2025-07-31 03:18:33'),
(16, '2025-01-07', 'Insurance', '0', 20.33, 'Paid', '2025-07-31 03:18:33', '2025-07-31 03:18:33'),
(17, '2024-08-24', 'Training', '0', 353.51, 'Pending', '2025-07-31 03:18:33', '2025-07-31 03:18:33'),
(18, '2025-02-20', 'Utilities', '0', 239.12, 'Paid', '2025-07-31 03:18:34', '2025-07-31 03:18:34'),
(19, '2024-10-12', 'Raw Materials', '0', 221.74, 'Pending', '2025-07-31 03:18:34', '2025-07-31 03:18:34'),
(20, '2025-07-25', 'Equipment', '0', 94.45, 'Pending', '2025-07-31 03:18:34', '2025-07-31 03:18:34'),
(21, '2025-03-08', 'Logistics', '0', 43.74, 'Paid', '2025-07-31 03:18:34', '2025-07-31 03:18:34'),
(22, '2024-11-29', 'Office Supplies', '0', 260.29, 'Paid', '2025-07-31 03:18:34', '2025-07-31 03:18:34'),
(23, '2025-05-28', 'Maintenance', '0', 323.11, 'Pending', '2025-07-31 03:18:34', '2025-07-31 03:18:34'),
(24, '2025-05-02', 'Utilities', '0', 483.71, 'Pending', '2025-07-31 03:18:34', '2025-07-31 03:18:34'),
(25, '2024-08-07', 'Software', '0', 476.74, 'Pending', '2025-07-31 03:18:34', '2025-07-31 03:18:34');

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

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `client` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('Paid','Pending','Overdue','Draft') DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_no`, `client`, `amount`, `due_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'INV-861', 'Quality Products Ltd', 130.18, '2025-09-11', 'Pending', '2025-07-31 03:18:35', '2025-07-31 03:18:35'),
(2, 'INV-560', 'Tech Solutions', 380.27, '2025-08-12', 'Overdue', '2025-07-31 03:18:35', '2025-07-31 03:18:35'),
(3, 'INV-584', 'Premium Industries', 139.37, '2025-09-16', 'Paid', '2025-07-31 03:18:35', '2025-07-31 03:18:35'),
(4, 'INV-445', 'Tech Solutions', 299.00, '2025-10-28', 'Overdue', '2025-07-31 03:18:35', '2025-07-31 03:18:35'),
(5, 'INV-046', 'Local Business', 434.15, '2025-09-16', 'Overdue', '2025-07-31 03:18:35', '2025-07-31 03:18:35'),
(6, 'INV-544', 'Global Corp', 213.21, '2025-08-10', 'Draft', '2025-07-31 03:18:35', '2025-07-31 03:18:35'),
(7, 'INV-028', 'ABC Manufacturing', 291.12, '2025-09-17', 'Draft', '2025-07-31 03:18:35', '2025-07-31 03:18:35'),
(8, 'INV-734', 'Premium Industries', 203.66, '2025-09-14', 'Draft', '2025-07-31 03:18:36', '2025-07-31 03:18:36'),
(9, 'INV-969', 'Global Corp', 186.75, '2025-09-08', 'Overdue', '2025-07-31 03:18:36', '2025-07-31 03:18:36'),
(10, 'INV-819', 'XYZ Industries', 278.43, '2025-08-04', 'Draft', '2025-07-31 03:18:36', '2025-07-31 03:18:36'),
(11, 'INV-286', 'ABC Manufacturing', 235.22, '2025-08-21', 'Draft', '2025-07-31 03:18:36', '2025-07-31 03:18:36'),
(12, 'INV-480', 'Quality Products Ltd', 43.23, '2025-08-12', 'Overdue', '2025-07-31 03:18:36', '2025-07-31 03:18:36'),
(13, 'INV-211', 'Quality Products Ltd', 406.11, '2025-09-27', 'Paid', '2025-07-31 03:18:36', '2025-07-31 03:18:36'),
(14, 'INV-681', 'Local Business', 126.04, '2025-09-09', 'Draft', '2025-07-31 03:18:36', '2025-07-31 03:18:36'),
(15, 'INV-846', 'Quality Products Ltd', 282.43, '2025-08-28', 'Draft', '2025-07-31 03:18:36', '2025-07-31 03:18:36');

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
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery` date NOT NULL,
  `status` enum('Pending','Approved','Completed','Cancelled') DEFAULT 'Pending',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `order_number`, `supplier_id`, `order_date`, `expected_delivery`, `status`, `total_amount`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'PO-20241201-1001', 1, '2024-12-01', '2024-12-15', 'Approved', 150000.00, 'Regular monthly order for ABS materials', '2025-07-31 02:00:14', '2025-07-31 02:00:14'),
(2, 'PO-20241202-1002', 2, '2024-12-02', '2024-12-20', 'Pending', 85000.00, 'PP materials for new product line', '2025-07-31 02:00:14', '2025-07-31 02:00:14'),
(3, 'PO-20241203-1003', 3, '2024-12-03', '2024-12-18', 'Completed', 120000.00, 'Nylon materials for automotive parts', '2025-07-31 02:00:14', '2025-07-31 02:00:14'),
(4, 'PO-20241204-1004', 4, '2024-12-04', '2024-12-25', 'Approved', 95000.00, 'PS materials for packaging', '2025-07-31 02:00:14', '2025-07-31 02:00:14'),
(5, 'PO-20241205-1005', 5, '2024-12-05', '2024-12-30', 'Pending', 180000.00, 'HIPS materials for consumer goods', '2025-07-31 02:00:14', '2025-07-31 02:00:14');

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
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `rating` int(1) DEFAULT 3,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `email`, `phone`, `address`, `rating`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ABC Polymers Inc.', 'John Smith', 'john.smith@abcpolymers.com', '+63 912 345 6789', '123 Polymer Street, Makati City, Metro Manila', 5, 'Active', '2025-07-31 02:00:14', '2025-07-31 02:00:14'),
(2, 'XYZ Materials Corp.', 'Maria Garcia', 'maria.garcia@xyzmaterials.com', '+63 923 456 7890', '456 Industrial Ave, Quezon City, Metro Manila', 4, 'Active', '2025-07-31 02:00:14', '2025-07-31 02:00:14'),
(3, 'PolyTech Solutions', 'Robert Johnson', 'robert.johnson@polytech.com', '+63 934 567 8901', '789 Manufacturing Blvd, Taguig City, Metro Manila', 4, 'Active', '2025-07-31 02:00:14', '2025-07-31 02:00:14'),
(4, 'Global Polymers Ltd.', 'Sarah Wilson', 'sarah.wilson@globalpolymers.com', '+63 945 678 9012', '321 Export Road, Pasig City, Metro Manila', 3, 'Active', '2025-07-31 02:00:14', '2025-07-31 02:00:14'),
(5, 'Premium Materials Co.', 'Michael Brown', 'michael.brown@premiummaterials.com', '+63 956 789 0123', '654 Quality Street, Mandaluyong City, Metro Manila', 5, 'Active', '2025-07-31 02:00:14', '2025-07-31 02:00:14');

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
-- Indexes for table `budget`
--
ALTER TABLE `budget`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `calendar_schedule_note_history`
--
ALTER TABLE `calendar_schedule_note_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `delivery_number` (`delivery_number`),
  ADD KEY `purchase_order_id` (`purchase_order_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `income`
--
ALTER TABLE `income`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`);

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
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `purchase_orders_sample`
--
ALTER TABLE `purchase_orders_sample`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_id` (`purchase_order_id`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quotation_no` (`quotation_no`);

--
-- Indexes for table `raw_materials`
--
ALTER TABLE `raw_materials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code_color` (`code_color`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

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
-- AUTO_INCREMENT for table `budget`
--
ALTER TABLE `budget`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `calendar_schedule_note_history`
--
ALTER TABLE `calendar_schedule_note_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `income`
--
ALTER TABLE `income`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `purchase_orders_sample`
--
ALTER TABLE `purchase_orders_sample`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `raw_materials`
--
ALTER TABLE `raw_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- Constraints for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD CONSTRAINT `fk_delivery_purchase_order` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_materials`
--
ALTER TABLE `product_materials`
  ADD CONSTRAINT `product_materials_ibfk_2` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders_sample`
--
ALTER TABLE `purchase_orders_sample`
  ADD CONSTRAINT `purchase_orders_sample_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders_sample` (`id`) ON DELETE CASCADE;

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
