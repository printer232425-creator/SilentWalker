-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 26, 2026 at 09:53 AM
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
-- Database: `school_system`
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `grade_level` int(255) DEFAULT NULL,
  `section` varchar(255) DEFAULT NULL,
  `adviser` varchar(255) DEFAULT NULL,
  `surname` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `academic_year` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assigned_teachers`
--

INSERT INTO `assigned_teachers` (`id`, `grade_level`, `section`, `adviser`, `surname`, `subject`, `academic_year`) VALUES
(29, 8, 'Ilang-Ilang', '26ENHS_T002', 'Bautista', 'English', NULL),
(30, 8, 'Ilang-Ilang', '26ENHS_T004', 'Fernandez', 'Filipino', NULL),
(31, 8, 'Ilang-Ilang', '26ENHS_T005', 'Torres', 'Mathematics', NULL),
(32, 8, 'Ilang-Ilang', '26ENHS_T006', 'Verzosa', 'Edukasyon sa Pagpapakatao (ESP)', NULL),
(33, 8, 'Ilang-Ilang', '26ENHS_T003', 'Villanueva', 'Physical Education', NULL),
(34, 7, 'Dahlia', '26ENHS_T006', 'Verzosa', 'English', NULL),
(35, 9, 'Emerald', '26ENHS_T006', 'Verzosa', 'Araling Panlipunan', NULL);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrolled_students`
--

INSERT INTO `enrolled_students` (`id`, `account_number`, `grade_level`, `section`, `class_adviser`, `academic_year`, `status`, `school_name`) VALUES
(43, '26ENHS_E2738872', 8, 'Ilang-Ilang', NULL, '2026-2027', 'Active', 'EGUIA NATIONAL HIGH SCHOOL');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `account_number` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL DEFAULT '',
  `surname` varchar(255) NOT NULL,
  `quarter` varchar(20) NOT NULL DEFAULT 'Q1',
  `subject` varchar(255) NOT NULL,
  `grade_and_section` varchar(255) DEFAULT NULL,
  `grade` int(11) DEFAULT NULL,
  `academic_year` varchar(255) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Passed',
  `teacher` varchar(255) NOT NULL,
  `date_modified` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `account_number`, `first_name`, `middle_name`, `surname`, `quarter`, `subject`, `grade_and_section`, `grade`, `academic_year`, `status`, `teacher`, `date_modified`) VALUES
(1, '26ENHS9934836', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Math', '8 - Section A', 81, 'January 2025', 'Passed', 'Corina Baybay Magno', 'January 5, 2025 8:35AM'),
(2, '26ENHS9934836', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Filipino', '8 - Section A', 81, 'January 2025', 'Passed', 'Corina Baybay Magno', 'January 5, 2025 8:40AM'),
(3, '26ENHS9934836', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Science', '8 - Section A', 81, 'January 2025', 'Passed', 'Corina Baybay Magno', 'January 5, 2026 8:45AM'),
(4, '26ENHS9934836', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Math', '8 - Section A', 81, 'January 2025', 'Passed', 'Corina Baybay Magno', 'February 5, 2026 9:10AM'),
(5, '26ENHS9934836', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Filipino', '8 - Section A', 81, 'January 2026', 'Passed', 'Corina Baybay Magno', 'February 5, 2026 9:15AM'),
(6, '26ENHS9934836', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Science', '8 - Section A', 81, 'January 2026', 'Passed', 'Corina Baybay Magno', 'February 5, 2026 9:20AM'),
(7, '26ENHS9934836', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Math', '8 - Section A', 81, 'January 2026', 'Passed', 'Corina Baybay Magno', 'March 5, 2026 10:05AM'),
(8, '26ENHS9934836', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Filipino', '8 - Section A', 81, 'January 2026', 'Passed', 'Corina Baybay Magno', 'March 5, 2026 10:10AM');

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `id` int(11) NOT NULL,
  `school_name` varchar(255) DEFAULT NULL,
  `school_type` varchar(255) DEFAULT NULL,
  `school_address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `school_name`, `school_type`, `school_address`) VALUES
(1, 'BARLO INTEGRATED SCHOOL', 'Elementary - Senior High', 'BARLO, MABINI, PANGASINAN'),
(2, 'EGUIA NATIONAL HIGH SCHOOL', 'High School - Senior High', 'EGUIA, DASOL, PANGASINAN'),
(3, 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 'Elementary', 'HERMOSA, DASOL, PANGASINAN'),
(4, 'TAMBOBONG NATIONAL HIGH SCHOOL', 'High School - Senior High', 'TAMBOBONG, DASOL, PANGASINAN'),
(5, 'DON MATIAS ELEMENTARY SCHOOL', 'Elementary', 'DON MATIAS, BURGOS, PANGASINAN'),
(6, 'SUROD ELEMENTARY SCHOOL', 'Elementary', 'SUROD, MABINI, PANGASINAN');

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
  `student_status` varchar(255) DEFAULT NULL,
  `enrollment_status` varchar(255) NOT NULL DEFAULT 'closed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `account_number`, `pin`, `pin_text`, `first_name`, `middle_name`, `surname`, `age`, `sex`, `birth_date`, `birth_place`, `address`, `contact_number`, `guardian`, `guardian_contact_number`, `registered_date`, `profile_account`, `user_type`, `school_name`, `grade_level`, `student_status`, `enrollment_status`) VALUES
(22, '26ENHS_E6731045', '$2y$10$cBYRMEfqdp9HOHKlTbvy5Oxyf4fgh5PV.RDT8AOFxlyhawEWbKUt2', NULL, 'Mark Anthony', 'Reyes', 'Bautista', 15, 'Male', '2011-02-11', 'Dasol, Pangasinan', 'Brgy. Tambobong, Dasol, Pangasinan', '09171234511', 'Elena Bautista', '09181234511', 'October 26, 2026 7:40AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 9, 'O', 'open'),
(23, '26ENHS_E6731046', '$2y$10$cBYRMEfqdp9HOHKlTbvy5Oxyf4fgh5PV.RDT8AOFxlyhawEWbKUt2', NULL, 'Jessa Mae', 'Cruz', 'Villanueva', 14, 'Female', '2011-06-03', 'Alaminos, Pangasinan', 'Purok 2 Poblacion, Dasol, Pangasinan', '09181234512', 'Rowena Villanueva', '09191234512', 'October 26, 2026 7:41AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 9, 'O', 'open'),
(24, '26ENHS_E6731047', '$2y$10$cBYRMEfqdp9HOHKlTbvy5Oxyf4fgh5PV.RDT8AOFxlyhawEWbKUt2', NULL, 'John Paul', 'Santos', 'Fernandez', 15, 'Male', '2011-09-19', 'Lingayen, Pangasinan', 'Brgy. Malabago, Dasol, Pangasinan', '09191234513', 'Arnel Fernandez', '09201234513', 'October 26, 2026 7:42AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 9, 'O', 'open'),
(25, '26ENHS_E6731048', '$2y$10$cBYRMEfqdp9HOHKlTbvy5Oxyf4fgh5PV.RDT8AOFxlyhawEWbKUt2', NULL, 'Kristine Joy', 'Mendoza', 'Torres', 14, 'Female', '2011-04-27', 'Dagupan, Pangasinan', 'Purok 5 Poblacion, Dasol, Pangasinan', '09201234514', 'Marilou Torres', '09211234514', 'October 26, 2026 7:43AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 9, 'O', 'open'),
(26, '26ENHS_E6731049', '$2y$10$cBYRMEfqdp9HOHKlTbvy5Oxyf4fgh5PV.RDT8AOFxlyhawEWbKUt2', NULL, 'Ryan Christopher', 'Dela', 'Aquino', 15, 'Male', '2011-08-15', 'Urdaneta, Pangasinan', 'Brgy. Bobonot, Dasol, Pangasinan', '09211234515', 'Liza Aquino', '09221234515', 'October 26, 2026 7:44AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 9, 'O', 'open'),
(27, '26ENHS_E6731050', '$2y$10$cBYRMEfqdp9HOHKlTbvy5Oxyf4fgh5PV.RDT8AOFxlyhawEWbKUt2', NULL, 'Angelica', 'Lim', 'Ramos', 13, 'Female', '2012-01-22', 'Bani, Pangasinan', 'Brgy. Tambobong, Dasol, Pangasinan', '09221234516', 'Cristina Ramos', '09231234516', 'October 26, 2026 7:45AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 8, 'T', 'closed'),
(28, '26ENHS_E6731051', '$2y$10$cBYRMEfqdp9HOHKlTbvy5Oxyf4fgh5PV.RDT8AOFxlyhawEWbKUt2', NULL, 'Jerome', 'Castro', 'Navarro', 14, 'Male', '2012-05-30', 'Anda, Pangasinan', 'Purok 3 Poblacion, Dasol, Pangasinan', '09231234517', 'Rowena Navarro', '09241234517', 'October 26, 2026 7:46AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 8, 'T', 'closed'),
(29, '26ENHS_E6731052', '$2y$10$cBYRMEfqdp9HOHKlTbvy5Oxyf4fgh5PV.RDT8AOFxlyhawEWbKUt2', NULL, 'Hazel Ann', 'Pascual', 'Domingo', 13, 'Female', '2012-10-08', 'Infanta, Pangasinan', 'Brgy. Malabago, Dasol, Pangasinan', '09241234518', 'Editha Domingo', '09251234518', 'October 26, 2026 7:47AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 8, 'T', 'closed'),
(30, '26ENHS_E6731053', '$2y$10$cBYRMEfqdp9HOHKlTbvy5Oxyf4fgh5PV.RDT8AOFxlyhawEWbKUt2', NULL, 'Paolo Miguel', 'Ocampo', 'Salazar', 14, 'Male', '2012-03-17', 'Bolinao, Pangasinan', 'Brgy. Bobonot, Dasol, Pangasinan', '09251234519', 'Marissa Salazar', '09261234519', 'October 26, 2026 7:48AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 8, 'T', 'closed'),
(31, '26ENHS_E6731054', '$2y$10$cBYRMEfqdp9HOHKlTbvy5Oxyf4fgh5PV.RDT8AOFxlyhawEWbKUt2', NULL, 'Bea Patricia', 'Villamor', 'Gonzales', 13, 'Female', '2012-12-05', 'Mabini, Pangasinan', 'Purok 5 Poblacion, Dasol, Pangasinan', '09261234520', 'Carmen Gonzales', '09271234520', 'October 26, 2026 7:49AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 8, 'T', 'closed'),
(32, '26ENHS_E6731055', '$2y$10$cBYRMEfqdp9HOHKlTbvy5Oxyf4fgh5PV.RDT8AOFxlyhawEWbKUt2', NULL, 'Liam Gabriel', 'Cruz', 'Mercado', 12, 'Male', '2013-07-14', 'Dasol, Pangasinan', 'Brgy. Tambobong, Dasol, Pangasinan', '09271234521', 'Sonia Mercado', '09281234521', 'October 26, 2026 7:50AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 7, 'N', 'closed'),
(33, '26ENHS_E6731056', '$2y$10$cBYRMEfqdp9HOHKlTbvy5Oxyf4fgh5PV.RDT8AOFxlyhawEWbKUt2', NULL, 'Sophia Nicole', 'Dizon', 'Cordero', 12, 'Female', '2013-11-02', 'Alaminos, Pangasinan', 'Purok 2 Poblacion, Dasol, Pangasinan', '09281234522', 'Teresita Cordero', '09291234522', 'October 26, 2026 7:51AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 7, 'N', 'closed'),
(34, '26ENHS_E6731057', '$2y$10$cBYRMEfqdp9HOHKlTbvy5Oxyf4fgh5PV.RDT8AOFxlyhawEWbKUt2', NULL, 'Ethan Josh', 'Aguilar', 'Soriano', 12, 'Male', '2013-01-25', 'Dagupan, Pangasinan', 'Brgy. Malabago, Dasol, Pangasinan', '09291234523', 'Rosalie Soriano', '09301234523', 'October 26, 2026 7:52AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 7, 'N', 'closed'),
(35, '26ENHS_E6731058', '$2y$10$cBYRMEfqdp9HOHKlTbvy5Oxyf4fgh5PV.RDT8AOFxlyhawEWbKUt2', NULL, 'Isabela Marie', 'Salcedo', 'Reyes', 13, 'Female', '2013-09-09', 'Urdaneta, Pangasinan', 'Brgy. Bobonot, Dasol, Pangasinan', '09301234524', 'Marilou Reyes', '09311234524', 'October 26, 2026 7:53AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 7, 'N', 'closed'),
(36, '26ENHS_E6731059', '$2y$10$cBYRMEfqdp9HOHKlTbvy5Oxyf4fgh5PV.RDT8AOFxlyhawEWbKUt2', NULL, 'Daniel Luis', 'Cabrera', 'Delos Santos', 12, 'Male', '2013-05-18', 'Lingayen, Pangasinan', 'Purok 5 Poblacion, Dasol, Pangasinan', '09311234525', 'Ferdinand Delos Santos', '09321234525', 'October 26, 2026 7:54AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 7, 'N', 'closed'),
(37, '26ENHS_E2738872', '$2y$10$4g4pk5cHjowD0RBiK.ICu.x4g0JOtgsbwkxlvVUkpuI.bWhGMwEzy', 112400, 'Norlie', 'Jay', 'Verzosa', 25, 'Male', '2000-11-24', 'Dasol, Pangasinan', 'Purok 5 Poblacion, Dasol, Pangasinan', '09455374819', 'Cornelio Magno', '09455374819', 'October 26, 2026 8:27AM', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL', 8, 'O', 'open');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `account_number`, `pin`, `pin_text`, `first_name`, `middle_name`, `surname`, `age`, `sex`, `birth_date`, `birth_place`, `address`, `contact_number`, `registered_date`, `profile_account`, `user_type`, `school_name`, `position`, `department`) VALUES
(10, '26ENHS_T002', '$2y$10$g5yhI4TCxCeHWG5JKiOP2u/qlZHtOGBODshpvprSLlUQE0hXmTwGi', 0, 'Marites', 'Reyes', 'Bautista', 34, 'Female', '1991-03-14', 'Lingayen, Pangasinan', 'Brgy. Malabago, Dasol, Pangasinan', '09171234501', 'October 26, 2026 7:30AM', 'profile-image.jpg', 'teacher', 'EGUIA NATIONAL HIGH SCHOOL', 'Teacher II', 'Mathematics'),
(11, '26ENHS_T003', '$2y$10$g5yhI4TCxCeHWG5JKiOP2u/qlZHtOGBODshpvprSLlUQE0hXmTwGi', 0, 'Ricardo', 'Santos', 'Villanueva', 41, 'Male', '1984-07-09', 'Alaminos, Pangasinan', 'Purok 2 Poblacion, Dasol, Pangasinan', '09181234502', 'October 26, 2026 7:31AM', 'profile-image.jpg', 'teacher', 'EGUIA NATIONAL HIGH SCHOOL', 'Master Teacher I', 'Science'),
(12, '26ENHS_T004', '$2y$10$g5yhI4TCxCeHWG5JKiOP2u/qlZHtOGBODshpvprSLlUQE0hXmTwGi', 0, 'Analyn', 'Cruz', 'Fernandez', 29, 'Female', '1996-11-22', 'Dagupan, Pangasinan', 'Brgy. Tambobong, Dasol, Pangasinan', '09191234503', 'October 26, 2026 7:32AM', 'profile-image.jpg', 'teacher', 'EGUIA NATIONAL HIGH SCHOOL', 'Teacher I', 'Filipino'),
(13, '26ENHS_T005', '$2y$10$g5yhI4TCxCeHWG5JKiOP2u/qlZHtOGBODshpvprSLlUQE0hXmTwGi', 0, 'Jonathan', 'Mendoza', 'Torres', 37, 'Male', '1988-02-06', 'Urdaneta, Pangasinan', 'Brgy. Bobonot, Dasol, Pangasinan', '09201234504', 'October 26, 2026 7:33AM', 'profile-image.jpg', 'teacher', 'EGUIA NATIONAL HIGH SCHOOL', 'Teacher III', 'MAPEH'),
(14, '26ENHS_T006', '$2y$10$p9bji6EvTikMDLC6qeIvPOnXL0lhXQynz4mDJRajgxyNzRjOkPKzi', 784512, 'Corina', 'Baybay', 'Verzosa', 25, 'Female', '2000-12-23', 'Dasol, Pangasinan', 'Purok 5 Poblacion, Dasol, Pangasinan', '09455374819', 'October 26, 2026 8:28AM', 'profile-image.jpg', 'teacher', 'EGUIA NATIONAL HIGH SCHOOL', 'Teacher I', 'English');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assigned_teachers`
--
ALTER TABLE `assigned_teachers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `enrolled_students`
--
ALTER TABLE `enrolled_students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_account_number` (`account_number`),
  ADD KEY `idx_subject` (`subject`),
  ADD KEY `idx_teacher` (`teacher`);

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
-- AUTO_INCREMENT for table `assigned_teachers`
--
ALTER TABLE `assigned_teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `enrolled_students`
--
ALTER TABLE `enrolled_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
