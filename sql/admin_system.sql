-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 20, 2025 at 09:30 AM
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
-- Database: `admin_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `log_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_management`
--

CREATE TABLE `employee_management` (
  `ID` int(11) NOT NULL,
  `FULL NAME` text DEFAULT NULL,
  `POSITION/JOB TITLE` text DEFAULT NULL,
  `DEPARTMENT` text DEFAULT NULL,
  `DATE HIRED` date DEFAULT NULL,
  `EMPLOYMENT TYPE` enum('REGULAR','INTERN','PROBATIONARY') DEFAULT NULL,
  `STATUS` enum('ACTIVE','INACTIVE','RESIGNED','') DEFAULT NULL,
  `CONTACT NUMBER` int(11) DEFAULT NULL,
  `EMAIL ADDRESS` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr functions`
--

CREATE TABLE `hr functions` (
  `ID` int(11) NOT NULL,
  `FULL NAME` varchar(150) NOT NULL,
  `LEAVE TYPE` set('VACATION','SICK','MATERNITY','PATERNITY','BEREAVEMENT','OTHERS PLS SPECIFY') NOT NULL,
  `DATE FILED` date NOT NULL,
  `LEAVE DURATION` varchar(50) NOT NULL,
  `STATUS` enum('PENDING','APPROVED','REJECTED','CANCELLED') NOT NULL,
  `BENEFIT TYPE` enum('SSS','PHILHEALTH','PAGIBIG','COMPANY','OTHERS') DEFAULT NULL,
  `BENEFIT START DATE` date DEFAULT NULL,
  `REMARKS` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recruitment management`
--

CREATE TABLE `recruitment management` (
  `APPLICANT NO.` int(11) NOT NULL,
  `FULL NAME` varchar(150) NOT NULL,
  `POSITION APPLIED` varchar(100) NOT NULL,
  `DATE APPLIED` date NOT NULL,
  `APPLICATION STATUS` enum('PENDING','INTERVIEW','HIRED','REJECTED') NOT NULL,
  `CONTACT NUMBER` int(20) DEFAULT NULL,
  `EMAIL ADDRESS` varchar(150) NOT NULL,
  `ASSIGNED HR` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `employee_management`
--
ALTER TABLE `employee_management`
  ADD UNIQUE KEY `ID` (`ID`),
  ADD UNIQUE KEY `EMAIL ADDRESS` (`EMAIL ADDRESS`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_management`
--
ALTER TABLE `employee_management`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
