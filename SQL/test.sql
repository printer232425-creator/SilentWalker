-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2026 at 07:25 PM
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
(1, '26_DJBSES_001', '$2y$10$YFasM7gytyMcqWWxqStl3eHfmSyzFGqqprbShUdxZKzQEjQsk8lza', 332211, 'Norlie', 'Jay', 'Verzosa', 25, 'Male', 'Purok 5 Poblacion, Dasol, Pangasinan', '09455374819', 'October 24, 2026 4:47PM', 'profile-image.jpg', 'admin', 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `code_name` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `date_today` varchar(255) DEFAULT NULL,
  `grade` varchar(255) DEFAULT NULL,
  `section` varchar(255) DEFAULT NULL,
  `adviser` varchar(255) DEFAULT NULL,
  `sub_adviser` varchar(255) DEFAULT NULL,
  `profile_account` varchar(255) NOT NULL DEFAULT 'profile-image.jpg',
  `status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_reports`
--

CREATE TABLE `document_reports` (
  `id` int(11) NOT NULL,
  `code_name` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `date_modified` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `file_size` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrolled_students`
--

CREATE TABLE `enrolled_students` (
  `id` int(11) NOT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `grade_level` int(11) DEFAULT 0,
  `section` varchar(255) DEFAULT NULL,
  `academic_year` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT 'Not enrolled yet',
  `school_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrolled_students`
--

INSERT INTO `enrolled_students` (`id`, `account_number`, `grade_level`, `section`, `academic_year`, `status`, `school_name`) VALUES
(26, '26_DJBSES_9423444', 7, 'Section B', '2022-2023', 'Active', 'EGUIA NATIONAL HIGH SCHOOL'),
(27, '26_DJBSES_9423444', 9, 'Section A', '2024-2025', 'Active', 'EGUIA NATIONAL HIGH SCHOOL'),
(30, '26_DJBSES_9423444', 9, 'Section B', '2026-2027', 'Active', 'EGUIA NATIONAL HIGH SCHOOL');

-- --------------------------------------------------------

--
-- Table structure for table `faculties_staff`
--

CREATE TABLE `faculties_staff` (
  `id` int(11) NOT NULL,
  `anonymous` varchar(255) DEFAULT NULL,
  `code_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `pin` int(11) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `age` varchar(255) NOT NULL,
  `birth_date` varchar(255) NOT NULL,
  `birth_place` varchar(255) NOT NULL,
  `mother_tounge` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `contact_number` varchar(255) DEFAULT NULL,
  `moto_in_life` varchar(255) DEFAULT NULL,
  `registered_date` varchar(255) DEFAULT NULL,
  `profile_account` varchar(255) NOT NULL DEFAULT 'profile-image.jpg',
  `user_type` varchar(255) NOT NULL DEFAULT 'staff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculties_staff`
--

INSERT INTO `faculties_staff` (`id`, `anonymous`, `code_name`, `account_number`, `pin`, `first_name`, `middle_name`, `surname`, `age`, `birth_date`, `birth_place`, `mother_tounge`, `address`, `contact_number`, `moto_in_life`, `registered_date`, `profile_account`, `user_type`) VALUES
(1, 'Cobra', 'STF-001', 'ACC-3001', 1111, 'Rico', 'Santos', 'Dela Rosa', '38', '1986-03-15', 'Manila', 'Tagalog', '15 Ayala Ave., Makati', '09172220001', 'Serve with heart.', '2024-04-01', 'profile-image.jpg', 'staff'),
(2, 'Leopard', 'STF-002', 'ACC-3002', 2222, 'Nina', 'Cruz', 'Fernandez', '30', '1994-08-08', 'Quezon City', 'Tagalog', '25 EDSA, QC', '09172220002', 'Stay organized.', '2024-04-02', 'profile-image.jpg', 'staff'),
(3, 'Shark', 'STF-003', 'ACC-3003', 3333, 'Ben', 'Reyes', 'Aquino', '42', '1982-11-11', 'Cebu', 'Cebuano', '35 Colon St., Cebu', '09172220003', 'Help others.', '2024-04-03', 'profile-image.jpg', 'staff'),
(4, 'Raven', 'STF-004', 'ACC-3004', 4444, 'Grace', 'Lopez', 'Navarro', '28', '1996-02-02', 'Davao', 'Bisaya', '45 Roxas Blvd., Davao', '09172220004', 'Be reliable.', '2024-04-04', 'profile-image.jpg', 'staff'),
(5, 'Bison', 'STF-005', 'ACC-3005', 5555, 'Dennis', 'Garcia', 'Villamor', '36', '1988-07-07', 'Baguio', 'Ilocano', '55 Session Rd., Baguio', '09172220005', 'Stay humble.', '2024-04-05', 'profile-image.jpg', 'staff');

-- --------------------------------------------------------

--
-- Table structure for table `gawad_parangal`
--

CREATE TABLE `gawad_parangal` (
  `id` int(11) NOT NULL,
  `code_name` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `date_today` varchar(255) DEFAULT NULL,
  `grade` varchar(255) DEFAULT NULL,
  `section` varchar(255) DEFAULT NULL,
  `adviser` varchar(255) DEFAULT NULL,
  `anouncer` varchar(255) DEFAULT NULL,
  `event_name` varchar(255) NOT NULL,
  `types_of_award` varchar(255) NOT NULL,
  `desc` varchar(255) NOT NULL,
  `when` varchar(255) NOT NULL,
  `where` varchar(255) NOT NULL,
  `shown_info` varchar(255) NOT NULL,
  `modified_at` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gawad_parangal`
--

INSERT INTO `gawad_parangal` (`id`, `code_name`, `first_name`, `middle_name`, `surname`, `date_today`, `grade`, `section`, `adviser`, `anouncer`, `event_name`, `types_of_award`, `desc`, `when`, `where`, `shown_info`, `modified_at`) VALUES
(1, 'STU-001', 'Juan', 'Dela', 'Cruz', '2024-06-15', '7', 'Sampaguita', 'Maria Santos', 'Principal Office', 'Recognition Day', 'With Honors', 'Achieved 90+ average.', '2024-06-15', 'Auditorium', 'Shown', '2024-06-15 09:00 AM'),
(2, 'STU-002', 'Ana', 'Santos', 'Reyes', '2024-06-15', '7', 'Sampaguita', 'Maria Santos', 'Principal Office', 'Recognition Day', 'With High Honors', 'Achieved 95+ average.', '2024-06-15', 'Auditorium', 'Shown', '2024-06-15 09:05 AM'),
(3, 'STU-003', 'Mark', 'Lopez', 'Garcia', '2024-06-15', '8', 'Rosal', 'Elena Villanueva', 'Academic Office', 'Quiz Bee', 'Champion', 'Won 1st place in Science Quiz Bee.', '2024-06-15', 'School Gym', 'Shown', '2024-06-15 09:10 AM'),
(4, 'STU-004', 'Liza', 'Cruz', 'Mendoza', '2024-06-15', '8', 'Rosal', 'Elena Villanueva', 'Academic Office', 'Essay Writing', '1st Runner-Up', 'Placed 2nd in Essay Writing Contest.', '2024-06-15', 'School Gym', 'Shown', '2024-06-15 09:15 AM'),
(5, 'STU-005', 'Kevin', 'Reyes', 'Torres', '2024-06-15', '9', 'Ilang-Ilang', 'Carla Bautista', 'Student Affairs', 'Sports Fest', 'MVP', 'Most Valuable Player in Basketball.', '2024-06-15', 'School Gym', 'Hidden', '2024-06-15 09:20 AM');

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
(1, '26_DJBSES_9423444', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Math', '8 - Section A', 81, 'January 2025', 'Passed', 'Corina Baybay Magno', 'January 5, 2025 8:35AM'),
(2, '26_DJBSES_9423444', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Filipino', '8 - Section A', 81, 'January 2025', 'Passed', 'Corina Baybay Magno', 'January 5, 2025 8:40AM'),
(3, '26_DJBSES_9423444', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Science', '8 - Section A', 81, 'January 2025', 'Passed', 'Corina Baybay Magno', 'January 5, 2026 8:45AM'),
(4, '26_DJBSES_9423444', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Math', '8 - Section A', 81, 'January 2025', 'Passed', 'Corina Baybay Magno', 'February 5, 2026 9:10AM'),
(5, '26_DJBSES_9423444', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Filipino', '8 - Section A', 81, 'January 2026', 'Passed', 'Corina Baybay Magno', 'February 5, 2026 9:15AM'),
(6, '26_DJBSES_9423444', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Science', '8 - Section A', 81, 'January 2026', 'Passed', 'Corina Baybay Magno', 'February 5, 2026 9:20AM'),
(7, '26_DJBSES_9423444', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Math', '8 - Section A', 81, 'January 2026', 'Passed', 'Corina Baybay Magno', 'March 5, 2026 10:05AM'),
(8, '26_DJBSES_9423444', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Filipino', '8 - Section A', 81, 'January 2026', 'Passed', 'Corina Baybay Magno', 'March 5, 2026 10:10AM');

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
-- Table structure for table `school_updates`
--

CREATE TABLE `school_updates` (
  `id` int(11) NOT NULL,
  `anouncer` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `desc` varchar(255) NOT NULL,
  `when` varchar(255) NOT NULL,
  `where` varchar(255) NOT NULL,
  `shown_info` varchar(255) NOT NULL,
  `modified_at` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_updates`
--

INSERT INTO `school_updates` (`id`, `anouncer`, `subject`, `title`, `desc`, `when`, `where`, `shown_info`, `modified_at`) VALUES
(1, 'Principal Office', 'Enrollment', 'Enrollment for SY 2024-2025', 'Enrollment opens June 1 to June 30.', '2026', 'Portal', 'Shown', '2024-05-30 09:00 AM');

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
  `academic_status` int(11) DEFAULT 0,
  `school_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `account_number`, `pin`, `pin_text`, `first_name`, `middle_name`, `surname`, `age`, `sex`, `birth_date`, `birth_place`, `address`, `contact_number`, `guardian`, `guardian_contact_number`, `registered_date`, `profile_account`, `user_type`, `academic_status`, `school_name`) VALUES
(4, '26_DJBSES_9423444', '$2y$10$YFasM7gytyMcqWWxqStl3eHfmSyzFGqqprbShUdxZKzQEjQsk8lza', 332211, 'Norlie', 'Jay', 'Verzosa', 25, 'Male', '2000-11-24', 'Dasol, Pangasinan', 'Purok 5 Poblacion, Dasol, Pangasinan', '09455374819', 'Corina B. Magno', '09455374819', 'October 24, 2026 4:47PM', 'profile-image.jpg', 'student', 1, 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL');

-- --------------------------------------------------------

--
-- Table structure for table `student_subjects`
--

CREATE TABLE `student_subjects` (
  `id` int(11) NOT NULL,
  `grade_level` int(255) DEFAULT NULL,
  `section` varchar(255) DEFAULT NULL,
  `adviser` varchar(255) DEFAULT NULL,
  `surname` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_subjects`
--

INSERT INTO `student_subjects` (`id`, `grade_level`, `section`, `adviser`, `surname`, `subject`) VALUES
(14, 9, 'Section B', '26_DJBSES_T6661', 'Magno', 'English'),
(15, 7, 'Section A', '26_DJBSES_T6661', 'Magno', 'Science'),
(16, 7, 'Section C', '26_DJBSES_T6661', 'Magno', 'Science'),
(17, 7, 'Section C', '26_DJBSES_T6662', 'Verzosa', 'PE');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `department` varchar(50) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `department`) VALUES
(1, 'MATH', 'Mathematics', 'Mathematics'),
(2, 'FIL', 'Filipino', 'Filipino'),
(3, 'ENG', 'English', 'English'),
(4, 'SCI', 'Science', 'Science'),
(5, 'AP', 'Araling Panlipunan', 'Araling Panlipunan'),
(6, 'MAPEH', 'MAPEH', 'MAPEH'),
(7, 'TLE', 'TLE', 'TLE'),
(8, 'ESP', 'ESP', 'ESP');

-- --------------------------------------------------------

--
-- Table structure for table `system_admin`
--

CREATE TABLE `system_admin` (
  `id` int(11) NOT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `pin` varchar(255) DEFAULT NULL,
  `user_type` varchar(255) NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_admin`
--

INSERT INTO `system_admin` (`id`, `account_number`, `pin`, `user_type`) VALUES
(1, 'ADM-0001', '1111', 'admin'),
(2, 'ADM-0002', '2222', 'admin'),
(3, 'ADM-0003', '3333', 'superadmin'),
(4, 'ADM-0004', '4444', 'admin'),
(5, 'ADM-0005', '5555', 'superadmin');

-- --------------------------------------------------------

--
-- Table structure for table `system_updates`
--

CREATE TABLE `system_updates` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `desc` varchar(255) DEFAULT NULL,
  `notif_shown` int(11) DEFAULT 0,
  `pin` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_updates`
--

INSERT INTO `system_updates` (`id`, `title`, `desc`, `notif_shown`, `pin`) VALUES
(1, 'System Maintenance', 'Scheduled maintenance on Saturday 10 PM - 12 AM.', 0, 1234),
(2, 'New Feature Released', 'Grade export to PDF is now available.', 0, 2345),
(3, 'Security Patch', 'Please update your password for security.', 0, 3456),
(4, 'Data Backup Completed', 'All records backed up successfully.', 0, 4567),
(5, 'Holiday Notice', 'Office closed on November 30 (Bonifacio Day).', 0, 5678);

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
(4, '26_DJBSES_T6661', '$2y$10$Ulp83..bZ8yaonAflN1afe4dw8Gw0BwIcefSVuHQbwghYtczczGLa', 112233, 'Corina', 'Baybay', 'Magno', 25, 'Female', '2000-12-23', 'Dasol, Pangasinan', 'Purok 5 Poblacion, Dasol, Pangasinan', '09455374819', 'October 24, 2026 4:49PM', 'profile-image.jpg', 'teacher', 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 'Teacher I', 'English');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `id` int(11) NOT NULL,
  `teacher_account_number` varchar(50) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `grade_level` varchar(20) NOT NULL DEFAULT '',
  `section` varchar(50) NOT NULL DEFAULT '',
  `academic_year` varchar(20) NOT NULL DEFAULT '',
  `assigned_at` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `document_reports`
--
ALTER TABLE `document_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `enrolled_students`
--
ALTER TABLE `enrolled_students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `faculties_staff`
--
ALTER TABLE `faculties_staff`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gawad_parangal`
--
ALTER TABLE `gawad_parangal`
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
-- Indexes for table `school_updates`
--
ALTER TABLE `school_updates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_subjects`
--
ALTER TABLE `student_subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_subject_code` (`subject_code`);

--
-- Indexes for table `system_admin`
--
ALTER TABLE `system_admin`
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
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_assignment` (`teacher_account_number`,`subject_id`,`grade_level`,`section`,`academic_year`),
  ADD KEY `idx_teacher` (`teacher_account_number`),
  ADD KEY `idx_subject` (`subject_id`),
  ADD KEY `idx_year` (`academic_year`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_reports`
--
ALTER TABLE `document_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrolled_students`
--
ALTER TABLE `enrolled_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `faculties_staff`
--
ALTER TABLE `faculties_staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `gawad_parangal`
--
ALTER TABLE `gawad_parangal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT for table `school_updates`
--
ALTER TABLE `school_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_subjects`
--
ALTER TABLE `student_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `system_admin`
--
ALTER TABLE `system_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `system_updates`
--
ALTER TABLE `system_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
