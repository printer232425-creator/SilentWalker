-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 03, 2026 at 11:31 AM
-- Server version: 11.8.9-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u408983097_db_Bv9RVNRg`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `pin` varchar(255) NOT NULL,
  `pin_text` int(11) DEFAULT 0,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `sex` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `contact_number` varchar(255) DEFAULT NULL,
  `registered_date` varchar(255) DEFAULT NULL,
  `profile_account` varchar(255) NOT NULL DEFAULT 'profile-image.jpg',
  `user_type` varchar(255) NOT NULL DEFAULT 'admin',
  `school_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `account_number`, `pin`, `pin_text`, `first_name`, `middle_name`, `surname`, `age`, `sex`, `address`, `contact_number`, `registered_date`, `profile_account`, `user_type`, `school_name`) VALUES
(1, '26DJBSES_A001', '$2y$10$YFasM7gytyMcqWWxqStl3eHfmSyzFGqqprbShUdxZKzQEjQsk8lza', 332211, 'Norlie', 'Jay', 'Verzosa', 25, 'Male', 'Purok 5 Poblacion, Dasol, Pangasinan', '09455374819', 'October 24, 2026 4:47PM', 'profile-image.jpg', 'admin', 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL');

-- --------------------------------------------------------

--
-- Table structure for table `assigned_teachers`
--

CREATE TABLE `assigned_teachers` (
  `id` int(11) NOT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `adviser` varchar(255) DEFAULT NULL,
  `surname` varchar(255) DEFAULT NULL,
  `grade_level` varchar(255) DEFAULT NULL,
  `section` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `days` varchar(255) DEFAULT NULL,
  `start_time` varchar(10) DEFAULT NULL,
  `end_time` varchar(10) DEFAULT NULL,
  `advisory` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

--
-- Dumping data for table `assigned_teachers`
--

INSERT INTO `assigned_teachers` (`id`, `account_number`, `adviser`, `surname`, `grade_level`, `section`, `subject`, `days`, `start_time`, `end_time`, `advisory`) VALUES
(0, NULL, '26ENHS_T001', 'Magno', '7', 'Ilang-Ilang', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T002', 'Torres', '7', 'Ilang-Ilang', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T003', 'Verzosa', '7', 'Ilang-Ilang', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T004', 'Fernandez', '7', 'Ilang-Ilang', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T005', 'Bautista', '7', 'Ilang-Ilang', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T002', 'Torres', '7', 'Dahlia', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T003', 'Verzosa', '7', 'Dahlia', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T004', 'Fernandez', '7', 'Dahlia', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T005', 'Bautista', '7', 'Dahlia', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T001', 'Magno', '7', 'Dahlia', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T003', 'Verzosa', '7', 'Rosal', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T004', 'Fernandez', '7', 'Rosal', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T005', 'Bautista', '7', 'Rosal', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T001', 'Magno', '7', 'Rosal', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T002', 'Torres', '7', 'Rosal', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T004', 'Fernandez', '7', 'Sampaguita', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T005', 'Bautista', '7', 'Sampaguita', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T001', 'Magno', '7', 'Sampaguita', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T002', 'Torres', '7', 'Sampaguita', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T003', 'Verzosa', '7', 'Sampaguita', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T001', 'Magno', '8', 'Acacia', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T002', 'Torres', '8', 'Acacia', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T003', 'Verzosa', '8', 'Acacia', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T004', 'Fernandez', '8', 'Acacia', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T005', 'Bautista', '8', 'Acacia', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T002', 'Torres', '8', 'Yakal', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T003', 'Verzosa', '8', 'Yakal', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T004', 'Fernandez', '8', 'Yakal', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T005', 'Bautista', '8', 'Yakal', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T001', 'Magno', '8', 'Yakal', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T003', 'Verzosa', '8', 'Molave', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T004', 'Fernandez', '8', 'Molave', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T005', 'Bautista', '8', 'Molave', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T001', 'Magno', '8', 'Molave', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T002', 'Torres', '8', 'Molave', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T004', 'Fernandez', '8', 'Mahogany', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T005', 'Bautista', '8', 'Mahogany', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T001', 'Magno', '8', 'Mahogany', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T002', 'Torres', '8', 'Mahogany', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T003', 'Verzosa', '8', 'Mahogany', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T001', 'Magno', '9', 'Mabini', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T002', 'Torres', '9', 'Mabini', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T003', 'Verzosa', '9', 'Mabini', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T004', 'Fernandez', '9', 'Mabini', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T005', 'Bautista', '9', 'Mabini', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T002', 'Torres', '9', 'Rizal', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T003', 'Verzosa', '9', 'Rizal', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T004', 'Fernandez', '9', 'Rizal', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T005', 'Bautista', '9', 'Rizal', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T001', 'Magno', '9', 'Rizal', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T003', 'Verzosa', '9', 'Jacinto', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T004', 'Fernandez', '9', 'Jacinto', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T005', 'Bautista', '9', 'Jacinto', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T001', 'Magno', '9', 'Jacinto', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T002', 'Torres', '9', 'Jacinto', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T004', 'Fernandez', '9', 'Bonifacio', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T005', 'Bautista', '9', 'Bonifacio', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T001', 'Magno', '9', 'Bonifacio', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T002', 'Torres', '9', 'Bonifacio', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T003', 'Verzosa', '9', 'Bonifacio', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T001', 'Magno', '10', 'Emerald', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T002', 'Torres', '10', 'Emerald', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T003', 'Verzosa', '10', 'Emerald', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T004', 'Fernandez', '10', 'Emerald', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T005', 'Bautista', '10', 'Emerald', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T002', 'Torres', '10', 'Diamond', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T003', 'Verzosa', '10', 'Diamond', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T004', 'Fernandez', '10', 'Diamond', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T005', 'Bautista', '10', 'Diamond', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T001', 'Magno', '10', 'Diamond', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T003', 'Verzosa', '10', 'Jade', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T004', 'Fernandez', '10', 'Jade', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T005', 'Bautista', '10', 'Jade', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T001', 'Magno', '10', 'Jade', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T002', 'Torres', '10', 'Jade', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T004', 'Fernandez', '11', 'Home Economics - Pasteur', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T005', 'Bautista', '11', 'Home Economics - Pasteur', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T001', 'Magno', '11', 'Home Economics - Pasteur', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T002', 'Torres', '11', 'Home Economics - Pasteur', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T003', 'Verzosa', '11', 'Home Economics - Pasteur', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T005', 'Bautista', '11', 'Einstein', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T001', 'Magno', '11', 'Einstein', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T002', 'Torres', '11', 'Einstein', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T003', 'Verzosa', '11', 'Einstein', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T004', 'Fernandez', '11', 'Einstein', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T001', 'Magno', '11', 'Socrates', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T002', 'Torres', '11', 'Socrates', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T003', 'Verzosa', '11', 'Socrates', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T004', 'Fernandez', '11', 'Socrates', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T005', 'Bautista', '11', 'Socrates', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T002', 'Torres', '11', 'Franklin', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T003', 'Verzosa', '11', 'Franklin', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T004', 'Fernandez', '11', 'Franklin', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T005', 'Bautista', '11', 'Franklin', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T001', 'Magno', '11', 'Franklin', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T003', 'Verzosa', '12', 'Automotive Servicing', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T004', 'Fernandez', '12', 'Automotive Servicing', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T005', 'Bautista', '12', 'Automotive Servicing', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T001', 'Magno', '12', 'Automotive Servicing', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T002', 'Torres', '12', 'Automotive Servicing', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T004', 'Fernandez', '12', 'TVL - HE', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T005', 'Bautista', '12', 'TVL - HE', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T001', 'Magno', '12', 'TVL - HE', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T002', 'Torres', '12', 'TVL - HE', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T003', 'Verzosa', '12', 'TVL - HE', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T005', 'Bautista', '12', 'STEM', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T001', 'Magno', '12', 'STEM', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T002', 'Torres', '12', 'STEM', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T003', 'Verzosa', '12', 'STEM', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T004', 'Fernandez', '12', 'STEM', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0),
(0, NULL, '26ENHS_T001', 'Magno', '12', 'Humanities & Social Science', 'Mathematics', 'Monday,Tuesday,Wednesday,Thursday,Friday', '07:30', '08:30', 1),
(0, NULL, '26ENHS_T002', 'Torres', '12', 'Humanities & Social Science', 'Science', 'Monday,Tuesday,Wednesday,Thursday,Friday', '08:30', '09:30', 0),
(0, NULL, '26ENHS_T003', 'Verzosa', '12', 'Humanities & Social Science', 'English', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:30', '10:30', 0),
(0, NULL, '26ENHS_T004', 'Fernandez', '12', 'Humanities & Social Science', 'Filipino', 'Monday,Tuesday,Wednesday,Thursday,Friday', '10:30', '11:30', 0),
(0, NULL, '26ENHS_T005', 'Bautista', '12', 'Humanities & Social Science', 'Araling Panlipunan', 'Monday,Tuesday,Wednesday,Thursday,Friday', '13:00', '14:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `attendance_list`
--

CREATE TABLE `attendance_list` (
  `id` int(10) UNSIGNED NOT NULL,
  `account_number` varchar(32) NOT NULL,
  `date_today` date NOT NULL,
  `time_start` time DEFAULT NULL,
  `time_end` time DEFAULT NULL,
  `grade_level` varchar(20) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `adviser` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `attendance_list`
--

INSERT INTO `attendance_list` (`id`, `account_number`, `date_today`, `time_start`, `time_end`, `grade_level`, `section`, `subject`, `status`, `adviser`) VALUES
(66, '26ENHS_E2738887', '2026-10-29', NULL, NULL, '8', 'Acacia', 'Araling Panlipunan', 'Present', '26ENHS_T005');

-- --------------------------------------------------------

--
-- Table structure for table `class_updates`
--

CREATE TABLE `class_updates` (
  `id` int(11) NOT NULL,
  `grade_level` varchar(255) DEFAULT NULL,
  `section` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `modified_at` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

--
-- Dumping data for table `class_updates`
--

INSERT INTO `class_updates` (`id`, `grade_level`, `section`, `subject`, `file_name`, `type`, `modified_at`) VALUES
(1, '8', 'Ilang-Ilang', 'English', 'Activity 1', 'docs', '2026-10-28 8:36PM'),
(2, '8', 'Ilang-Ilang', 'Filipino', 'Activity 2 - Sanaysay', 'docs', '2026-10-28 8:36PM'),
(3, '8', 'Ilang-Ilang', 'Mathematics', 'Problem Set 1 - Linear Equations', 'pdf', '2026-10-28 8:36PM'),
(4, '8', 'Ilang-Ilang', 'Science', 'Laboratory Report - Photosynthesis', 'docs', '2026-10-28 8:36PM'),
(5, '8', 'Ilang-Ilang', 'Araling Panlipunan', 'Group Project - Kasaysayan ng Asya', 'pdf', '2026-10-28 8:36PM'),
(6, '8', 'Ilang-Ilang', 'TLE', 'Performance Task - Basic Cooking', 'docs', '2026-10-28 8:36PM'),
(7, '8', 'Ilang-Ilang', 'MAPEH', 'Music Worksheet - Folk Songs', 'pdf', '2026-10-28 8:36PM'),
(8, '8', 'Ilang-Ilang', 'English', 'Reading Comprehension - Short Story', 'docs', '2026-10-28 8:36PM'),
(9, '8', 'Ilang-Ilang', 'Mathematics', 'Quiz 1 - Algebraic Expressions', 'pdf', '2026-10-28 8:36PM'),
(10, '8', 'Ilang-Ilang', 'Science', 'Quiz 2 - Ecosystem', 'docs', '2026-10-28 8:36PM');

-- --------------------------------------------------------

--
-- Table structure for table `enrolled_students`
--

CREATE TABLE `enrolled_students` (
  `id` int(11) NOT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `grade_level` int(11) DEFAULT 0,
  `section` varchar(255) DEFAULT NULL,
  `class_adviser` varchar(255) DEFAULT NULL,
  `academic_year` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT 'Not enrolled yet',
  `school_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

--
-- Dumping data for table `enrolled_students`
--

INSERT INTO `enrolled_students` (`id`, `account_number`, `grade_level`, `section`, `class_adviser`, `academic_year`, `status`, `school_name`) VALUES
(39, '26ENHS_E4307141', 7, 'Ilang-Ilang', NULL, '2026-2027', 'Active', 'EGUIA NATIONAL HIGH SCHOOL');

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `id` int(11) NOT NULL,
  `school_name` varchar(255) DEFAULT NULL,
  `school_type` varchar(255) DEFAULT NULL,
  `school_address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `school_name`, `school_type`, `school_address`) VALUES
(2, 'EGUIA NATIONAL HIGH SCHOOL', 'High School - Senior High', 'EGUIA, DASOL, PANGASINAN');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `pin` varchar(255) NOT NULL,
  `pin_text` int(11) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `sex` varchar(255) NOT NULL,
  `birth_date` varchar(255) NOT NULL,
  `birth_place` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `contact_number` varchar(255) DEFAULT NULL,
  `guardian` varchar(255) DEFAULT NULL,
  `guardian_contact_number` varchar(255) DEFAULT NULL,
  `registered_date` varchar(255) DEFAULT NULL,
  `profile_account` varchar(255) NOT NULL DEFAULT 'profile-image.jpg',
  `user_type` varchar(255) NOT NULL DEFAULT 'student',
  `school_name` varchar(255) NOT NULL,
  `grade_level` int(11) DEFAULT NULL,
  `student_type` varchar(255) DEFAULT NULL,
  `student_lrn` varchar(255) DEFAULT NULL,
  `enrollment_status` varchar(255) NOT NULL DEFAULT 'Not Enrolled',
  `enrollment_access` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `account_number`, `pin`, `pin_text`, `first_name`, `middle_name`, `surname`, `age`, `sex`, `birth_date`, `birth_place`, `address`, `contact_number`, `guardian`, `guardian_contact_number`, `registered_date`, `profile_account`, `user_type`, `school_name`, `grade_level`, `student_type`, `student_lrn`, `enrollment_status`, `enrollment_access`) VALUES
(12, '26ENHS_E2738883', '$2y$10$cBYRMEfqdp9HOHKlTbvy5Oxyf4fgh5PV.RDT8AOFxlyhawEWbKUt2', NULL, 'Sophia Nicole', 'Dizon', 'Cordero', 12, 'Female', '2013-11-02', 'Alaminos, Pangasinan', 'Purok 2 Poblacion, Dasol, Pangasinan', '09281234522', 'Teresita Cordero', '09291234522', 'October 26, 2026 7:51AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 8, 'O', NULL, 'Not Enrolled', 1),
(13, '26ENHS_E2738884', '$2y$10$P.7RvNdfvYuVh9RKukuerO.I.0JbE7a6kM.Ji395kLN0Mj6/p9vuy', NULL, 'Ethan Josh', 'Aguilar', 'Soriano', 12, 'Male', '2013-01-25', 'Dagupan, Pangasinan', 'Brgy. Malabago, Dasol, Pangasinan', '09291234523', 'Rosalie Soriano', '09301234523', 'October 26, 2026 7:52AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 8, 'O', NULL, 'Pending', 1),
(14, '26ENHS_E2738885', '$2y$10$cBYRMEfqdp9HOHKlTbvy5Oxyf4fgh5PV.RDT8AOFxlyhawEWbKUt2', NULL, 'Isabela Marie', 'Salcedo', 'Reyes', 13, 'Female', '2013-09-09', 'Urdaneta, Pangasinan', 'Brgy. Bobonot, Dasol, Pangasinan', '09301234524', 'Marilou Reyes', '09311234524', 'October 26, 2026 7:53AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 8, 'N', NULL, 'Not Enrolled', 1),
(15, '26ENHS_E2738886', '$2y$10$4g4pk5cHjowD0RBiK.ICu.x4g0JOtgsbwkxlvVUkpuI.bWhGMwEzy', 112400, 'Daniel Luis', 'Cabrera', 'Delos Santos', 12, 'Male', '2013-05-18', 'Lingayen, Pangasinan', 'Purok 5 Poblacion, Dasol, Pangasinan', '09311234525', 'Ferdinand Delos Santos', '09321234525', 'October 26, 2026 7:54AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 8, 'T', NULL, 'Enrolled', 1),
(41, '26ENHS_E2738887', '$2y$10$PHFA4EpeVGIwJRWSCSvlEe7WoDcFcIaDpn5mt07w5rjnE3gaXhxDS', 22011, 'Joseph', 'Migano', 'Villaruz', 25, 'Male', '2014-10-14', 'Dasol, Pangasinan', 'Poblacion, Dasol, Pangasinan', '09216583146', 'Nancy Oclarit', '09216583145', 'October 28, 2026 1:57AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 8, 'O', NULL, 'Enrolled', 1),
(44, '26ENHS_E4307141', '$2y$10$7Ww/Zek7xGFQjHIbmWI9qekxvN3SBkkpzQFCbc5lha7pLxVRWfrJK', 22011, 'Ma sofia', 'Sangalqng', 'Villaruz', 14, 'Female', '2012-02-18', 'Alaminos city, pangasinan', 'Western hospital', '09216583145', 'Joseph mugano villaruz', '092165831456', 'November 2, 2026 4:22AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 7, 'O', NULL, 'Enrolled', 1);

-- --------------------------------------------------------

--
-- Table structure for table `student_grades`
--

CREATE TABLE `student_grades` (
  `id` int(11) NOT NULL,
  `account_number` varchar(20) NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `section` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `quarter` tinyint(1) NOT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `adviser` varchar(100) NOT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ;

--
-- Dumping data for table `student_grades`
--

INSERT INTO `student_grades` (`id`, `account_number`, `grade_level`, `section`, `subject`, `quarter`, `grade`, `adviser`, `academic_year`, `updated_at`) VALUES
(15, '26ENHS_E2738872', '8', 'Yakal', 'Mathematics', 1, 85.00, '26ENHS_T006', '2026-2027', '2026-10-28 02:05:56'),
(16, '26ENHS_E2738872', '8', 'Yakal', 'Mathematics', 2, 79.00, '26ENHS_T006', '2026-2027', '2026-10-28 02:05:56'),
(17, '26ENHS_E2738872', '8', 'Yakal', 'Mathematics', 3, 83.00, '26ENHS_T006', '2026-2027', '2026-10-28 02:05:56'),
(18, '26ENHS_E2738872', '8', 'Yakal', 'Mathematics', 4, 81.00, '26ENHS_T006', '2026-2027', '2026-10-28 02:05:56'),
(23, '26DJBSES_E9901651', '8', 'Yakal', 'Mathematics', 1, 89.00, '26ENHS_T006', '2026-2027', '2026-10-28 02:05:56'),
(24, '26ENHS_E2738888', '8', 'Yakal', 'Mathematics', 2, 85.00, '26ENHS_T006', '2026-2027', '2026-10-28 08:02:51'),
(25, '26ENHS_E2738888', '8', 'Yakal', 'Mathematics', 3, 82.00, '26ENHS_T006', '2026-2027', '2026-10-28 08:02:48'),
(26, '26ENHS_E2738888', '8', 'Yakal', 'Mathematics', 4, 80.00, '26ENHS_T006', '2026-2027', '2026-10-28 08:02:45'),
(27, '26ENHS_E2738887', '8', 'Acacia', 'Araling Panlipunan', 1, 80.00, '26ENHS_T005', '2026-2027', '2026-10-29 06:21:15'),
(28, '26ENHS_E2738887', '8', 'Acacia', 'Araling Panlipunan', 2, 83.00, '26ENHS_T005', '2026-2027', '2026-10-29 06:21:15'),
(29, '26ENHS_E2738887', '8', 'Acacia', 'Araling Panlipunan', 3, 87.00, '26ENHS_T005', '2026-2027', '2026-10-29 06:21:15'),
(30, '26ENHS_E2738887', '8', 'Acacia', 'Araling Panlipunan', 4, 86.00, '26ENHS_T005', '2026-2027', '2026-10-29 06:21:15');

-- --------------------------------------------------------

--
-- Table structure for table `submitted_activities`
--

CREATE TABLE `submitted_activities` (
  `id` int(11) NOT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `grade_level` varchar(255) DEFAULT NULL,
  `section` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'editing',
  `modified_at` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `submitted_activities`
--

INSERT INTO `submitted_activities` (`id`, `account_number`, `grade_level`, `section`, `subject`, `file_name`, `type`, `status`, `modified_at`) VALUES
(1, '26ENHS_E2738872', '8', 'Ilang-Ilang', 'Filipino', 'Activity 2 - Sanaysay', 'docs', 'posted', '2026-10-27 9:15AM'),
(2, '26ENHS_E2738872', '8', 'Ilang-Ilang', 'Mathematics', 'Problem Set 1 - Linear Equations', 'pdf', 'posted', '2026-10-27 10:45AM'),
(3, '26ENHS_E2738872', '8', 'Ilang-Ilang', 'Science', 'Laboratory Report - Photosynthesis', 'docs', 'posted', '2026-10-28 8:00AM'),
(4, '26ENHS_E2738872', '8', 'Ilang-Ilang', 'Araling Panlipunan', 'Group Project - Kasaysayan ng Asya', 'pdf', 'posted', '2026-10-28 1:30PM');

-- --------------------------------------------------------

--
-- Table structure for table `system_updates`
--

CREATE TABLE `system_updates` (
  `id` int(11) NOT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `desc` varchar(255) DEFAULT NULL,
  `notif_shown` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `system_updates`
--

INSERT INTO `system_updates` (`id`, `account_number`, `title`, `desc`, `notif_shown`) VALUES
(1, '26ENHS_T007', 'Maligayang Pagdating!', 'Matagumpay ang iyong pagpaparehistro. Kumpletuhin ang iyong profile para makapagsimula.', 0);

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `account_number` varchar(255) NOT NULL,
  `pin` varchar(255) NOT NULL,
  `pin_text` int(11) DEFAULT 0,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL DEFAULT '',
  `surname` varchar(255) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `sex` varchar(255) NOT NULL DEFAULT '',
  `birth_date` varchar(255) NOT NULL DEFAULT '',
  `birth_place` varchar(255) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '',
  `contact_number` varchar(255) DEFAULT NULL,
  `registered_date` varchar(255) DEFAULT NULL,
  `profile_account` varchar(255) NOT NULL DEFAULT 'profile-image.jpg',
  `user_type` varchar(255) NOT NULL DEFAULT 'teacher',
  `school_name` varchar(255) NOT NULL DEFAULT '',
  `position` varchar(50) NOT NULL DEFAULT '',
  `department` varchar(50) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `account_number`, `pin`, `pin_text`, `first_name`, `middle_name`, `surname`, `age`, `sex`, `birth_date`, `birth_place`, `address`, `contact_number`, `registered_date`, `profile_account`, `user_type`, `school_name`, `position`, `department`) VALUES
(1, '26ENHS_T001', '$2y$10$g5yhI4TCxCeHWG5JKiOP2u/qlZHtOGBODshpvprSLlUQE0hXmTwGi', 0, 'Marites', 'Reyes', 'Magno', 34, 'Female', '1991-03-14', 'Lingayen, Pangasinan', 'Brgy. Malabago, Dasol, Pangasinan', '09171234501', 'October 26, 2026 7:30AM', 'profile-image.jpg', 'teacher', 'EGUIA NATIONAL HIGH SCHOOL', 'Teacher II', 'Mathematics'),
(2, '26ENHS_T002', '$2y$10$g5yhI4TCxCeHWG5JKiOP2u/qlZHtOGBODshpvprSLlUQE0hXmTwGi', 0, 'Ricardo', 'Santos', 'Torres', 41, 'Male', '1984-07-09', 'Alaminos, Pangasinan', 'Purok 2 Poblacion, Dasol, Pangasinan', '09181234502', 'October 26, 2026 7:31AM', 'profile-image.jpg', 'teacher', 'EGUIA NATIONAL HIGH SCHOOL', 'Master Teacher I', 'Science'),
(3, '26ENHS_T003', '$2y$10$g5yhI4TCxCeHWG5JKiOP2u/qlZHtOGBODshpvprSLlUQE0hXmTwGi', 0, 'Analyn', 'Cruz', 'Verzosa', 29, 'Female', '1996-11-22', 'Dagupan, Pangasinan', 'Brgy. Tambobong, Dasol, Pangasinan', '09191234503', 'October 26, 2026 7:32AM', 'profile-image.jpg', 'teacher', 'EGUIA NATIONAL HIGH SCHOOL', 'Teacher I', 'Filipino'),
(4, '26ENHS_T004', '$2y$10$g5yhI4TCxCeHWG5JKiOP2u/qlZHtOGBODshpvprSLlUQE0hXmTwGi', 0, 'Jonathan', 'Mendoza', 'Fernandez', 37, 'Male', '1988-02-06', 'Urdaneta, Pangasinan', 'Brgy. Bobonot, Dasol, Pangasinan', '09201234504', 'October 26, 2026 7:33AM', 'profile-image.jpg', 'teacher', 'EGUIA NATIONAL HIGH SCHOOL', 'Teacher III', 'MAPEH'),
(15, '26ENHS_T005', '$2y$10$Gr9Pj9cmqs3wZBDDUjvHLuDaQLEO6pkgoFNRPX.12ZINK17B6zz5.', 12233, 'Corina', 'Baybay', 'Bautista', 25, 'Female', '2000-12-23', 'Poblacion, Dasol, Pangasinan', 'Gais-Guipe, Dasol, Pangasinan', '09278608678', 'October 28, 2026 2:13AM', 'profile-image.jpg', 'teacher', 'EGUIA NATIONAL HIGH SCHOOL', 'Teacher I', 'Mathematics'),
(16, '26ENHS_T006', '$2y$10$HSpueL9cgQnLN7eZqtnkve.WzQAC0uPYeO/wILoV9y1Y0R.GzPG/i', 22011, 'Joseph', 'Migano', 'Villaruz', 52, 'Male', '1974-10-14', 'Dasol, pangasinan', 'Purok rang ay', '09216583145', 'October 31, 2026 5:54AM', 'profile-image.jpg', 'teacher', 'EGUIA NATIONAL HIGH SCHOOL', 'Teacher I', 'English'),
(17, '26ENHS_T007', '$2y$10$8Q2bT8jlppeEZSc64G0MqeMaMkNtNi5evjKQ1jKly17C3d71UT1sW', 112400, 'Norlie', 'Jay', 'Verzosa', 25, 'Male', '2000-11-24', 'Dasol, Pangasinan', 'Purok 5 Poblacion, Dasol, Pangasinan', '09455374819', 'November 3, 2026 4:39AM', 'profile-image.jpg', 'teacher', 'EGUIA NATIONAL HIGH SCHOOL', 'Teacher I', 'English');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance_list`
--
ALTER TABLE `attendance_list`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`account_number`),
  ADD KEY `idx_date` (`date_today`),
  ADD KEY `idx_subject` (`subject`);

--
-- Indexes for table `class_updates`
--
ALTER TABLE `class_updates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `enrolled_students`
--
ALTER TABLE `enrolled_students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_grades`
--
ALTER TABLE `student_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_student_quarter` (`account_number`,`grade_level`,`section`,`subject`,`quarter`,`academic_year`);

--
-- Indexes for table `submitted_activities`
--
ALTER TABLE `submitted_activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_updates`
--
ALTER TABLE `system_updates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `attendance_list`
--
ALTER TABLE `attendance_list`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `class_updates`
--
ALTER TABLE `class_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `enrolled_students`
--
ALTER TABLE `enrolled_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `student_grades`
--
ALTER TABLE `student_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `submitted_activities`
--
ALTER TABLE `submitted_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `system_updates`
--
ALTER TABLE `system_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
