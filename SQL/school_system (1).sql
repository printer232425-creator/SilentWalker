-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 25, 2026 at 02:56 PM
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
  `account_number` varchar(255) NOT NULL,
  `date_today` varchar(255) DEFAULT NULL,
  `grade` varchar(255) DEFAULT NULL,
  `section` varchar(255) DEFAULT NULL,
  `adviser` varchar(255) DEFAULT NULL,
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
  `school_name` varchar(255) NOT NULL,
  `grade_level` int(11) DEFAULT NULL,
  `student_status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `account_number`, `pin`, `pin_text`, `first_name`, `middle_name`, `surname`, `age`, `sex`, `birth_date`, `birth_place`, `address`, `contact_number`, `guardian`, `guardian_contact_number`, `registered_date`, `profile_account`, `user_type`, `academic_status`, `school_name`, `grade_level`, `student_status`) VALUES
(4, '26_DJBSES_9423444', '$2y$10$YFasM7gytyMcqWWxqStl3eHfmSyzFGqqprbShUdxZKzQEjQsk8lza', 332211, 'Norlie', 'Jay', 'Verzosa', 25, 'Male', '2000-11-24', 'Dasol, Pangasinan', 'Purok 5 Poblacion, Dasol, Pangasinan', '09455374819', 'Corina B. Magno', '09455374819', 'October 24, 2026 4:47PM', 'profile-image.jpg', 'student', 1, 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 7, 'N'),
(5, '26_DJBSES_9423445', '$2y$10$Ab1cD2..eF3ghijKlmN4opq5rsT6uvwXyzA7bcd8efg9hijKlmNoP', 445566, 'Angelo', 'Reyes', 'Bautista', 13, 'Male', '2013-05-12', 'Bolinao, Pangasinan', 'Purok 2 Barangay Poblacion, Bolinao, Pangasinan', '09171234567', 'Maria R. Bautista', '09181234567', 'October 25, 2026 8:20AM', 'profile-image.jpg', 'student', 1, 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 7, 'N'),
(6, '26_DJBSES_9423446', '$2y$10$Cd4eF5..gH6ijklMnoP7qrs8tuV9wxyZabC0def1ghiJklMnOpQ', 556677, 'Jasmine', 'Cruz', 'Del Rosario', 13, 'Female', '2013-09-03', 'Lingayen, Pangasinan', 'Purok 1 Barangay Libsong, Lingayen, Pangasinan', '09287654321', 'Rosa C. Del Rosario', '09297654321', 'October 25, 2026 9:05AM', 'profile-image.jpg', 'student', 1, 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 7, 'N'),
(7, '26_DJBSES_9423447', '$2y$10$Ef7gH8..iJ9klmnOpqR0stu1vwX2yzaBcdE3fgh4ijkLmnOpQrS', 667788, 'Kier', 'Lim', 'Aquino', 13, 'Male', '2013-01-28', 'Alaminos, Pangasinan', 'Purok 3 Barangay Poblacion, Alaminos, Pangasinan', '09393456789', 'Pedro L. Aquino', '09403456789', 'October 25, 2026 10:15AM', 'profile-image.jpg', 'student', 1, 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 7, 'N'),
(8, '26_DJBSES_9423448', '$2y$10$Gh0iJ1..kL2mnopQrsT3uvw4xyZ5abcDefG6hij7klmNopQrStU', 778899, 'Chloe', 'Tan', 'Villanueva', 13, 'Female', '2013-07-19', 'Dagupan, Pangasinan', 'Purok 4 Barangay Tapuac, Dagupan, Pangasinan', '09456789012', 'Ana T. Villanueva', '09466789012', 'October 25, 2026 11:40AM', 'profile-image.jpg', 'student', 1, 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 7, 'N'),
(9, '26_DJBSES_9423449', '$2y$10$Ij3kL4..mN5opqrStuV6wxy7zaB8cdeFghI9jkl0mnoPqrStUvW', 889900, 'Paolo', 'Garcia', 'Mendoza', 13, 'Male', '2013-03-07', 'San Carlos, Pangasinan', 'Purok 6 Barangay Poblacion, San Carlos, Pangasinan', '09567890123', 'Luz G. Mendoza', '09577890123', 'October 25, 2026 12:55PM', 'profile-image.jpg', 'student', 1, 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 7, 'N'),
(10, '26_DJBSES_9423450', '$2y$10$Kl5mN6..oP7qrstUvwX8yza1bcD2efgHijK3lmn4opQrsTuvWxY', 990011, 'Bianca', 'Santos', 'Ramos', 14, 'Female', '2012-06-21', 'Bolinao, Pangasinan', 'Purok 2 Barangay Concordia, Bolinao, Pangasinan', '09171234568', 'Elena S. Ramos', '09181234568', 'October 25, 2026 1:10PM', 'profile-image.jpg', 'student', 1, 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 8, 'O'),
(11, '26_DJBSES_9423451', '$2y$10$Mn7oP8..qR9stuvWxyZ0abc1deF2ghiJklM3nop4qrStuvWxYzA', 112233, 'Marco', 'Diaz', 'Castillo', 14, 'Male', '2012-02-14', 'Lingayen, Pangasinan', 'Purok 1 Barangay Poblacion, Lingayen, Pangasinan', '09287654322', 'Felipe D. Castillo', '09297654322', 'October 25, 2026 1:35PM', 'profile-image.jpg', 'student', 1, 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 8, 'O'),
(12, '26_DJBSES_9423452', '$2y$10$Op9qR0..sT1uvwxYzaB2cde3fGh4ijkLmnO5pqr6stUvwXyZaBc', 223344, 'Nadine', 'Lopez', 'Soriano', 14, 'Female', '2012-10-30', 'Alaminos, Pangasinan', 'Purok 3 Barangay Tanaytay, Alaminos, Pangasinan', '09393456780', 'Gloria L. Soriano', '09403456780', 'October 25, 2026 2:00PM', 'profile-image.jpg', 'student', 1, 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 8, 'O'),
(13, '26_DJBSES_9423453', '$2y$10$Qr1sT2..uV3wxyZabC4def5gHi6jklMnoP7qrs8tuVwxyZabCde', 334455, 'Tristan', 'Reyes', 'Gonzales', 14, 'Male', '2012-08-08', 'Dagupan, Pangasinan', 'Purok 4 Barangay Bonuan, Dagupan, Pangasinan', '09456789013', 'Rico R. Gonzales', '09466789013', 'October 25, 2026 2:25PM', 'profile-image.jpg', 'student', 1, 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 8, 'O'),
(14, '26_DJBSES_9423454', '$2y$10$Su3vW4..xY5zabCdefG6hij7kLm8nopQrsT9uvw0xyZabCdeFgh', 445566, 'Yvonne', 'Go', 'Navarro', 14, 'Female', '2012-12-05', 'San Carlos, Pangasinan', 'Purok 6 Barangay Talang, San Carlos, Pangasinan', '09567890124', 'Carmen G. Navarro', '09577890124', 'October 25, 2026 2:50PM', 'profile-image.jpg', 'student', 1, 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 8, 'O'),
(15, '26_DJBSES_9423455', '$2y$10$Wx5yZ6..aB7cdeFghI8jkl9mNo0pqrStuV1wxy2zaBcDefGhiJ', 556677, 'Rafael', 'Mercado', 'Domingo', 15, 'Male', '2011-04-17', 'Bolinao, Pangasinan', 'Purok 2 Barangay Luna, Bolinao, Pangasinan', '09171234569', 'Teresa M. Domingo', '09181234569', 'October 25, 2026 3:15PM', 'profile-image.jpg', 'student', 1, 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 9, 'T'),
(16, '26_DJBSES_9423456', '$2y$10$Yz7aB8..cD9efgHijK0lmn1oPq2rstUvwX3yza4bcDefGhiJkl', 667788, 'Camille', 'Uy', 'Pascual', 15, 'Female', '2011-09-09', 'Lingayen, Pangasinan', 'Purok 1 Barangay Naguelguel, Lingayen, Pangasinan', '09287654323', 'Lorna U. Pascual', '09297654323', 'October 25, 2026 3:40PM', 'profile-image.jpg', 'student', 1, 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 9, 'T'),
(17, '26_DJBSES_9423457', '$2y$10$Ab9cD0..eF1ghIjKlM2nop3qRs4tuvWxyZ5abc6deFgHiJklMno', 778899, 'Dexter', 'Ong', 'Salazar', 15, 'Male', '2011-06-25', 'Alaminos, Pangasinan', 'Purok 3 Barangay Poblacion, Alaminos, Pangasinan', '09393456781', 'Ben O. Salazar', '09403456781', 'October 25, 2026 4:05PM', 'profile-image.jpg', 'student', 1, 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 9, 'T'),
(18, '26_DJBSES_9423458', '$2y$10$Cd1eF2..gH3ijKlMnO4pqr5StU6vwxYzaB7cde8fGhIjKlMnO', 889900, 'Sabrina', 'Chua', 'Estrada', 15, 'Female', '2011-11-11', 'Dagupan, Pangasinan', 'Purok 4 Barangay Lucao, Dagupan, Pangasinan', '09456789014', 'Vivian C. Estrada', '09466789014', 'October 25, 2026 4:30PM', 'profile-image.jpg', 'student', 1, 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 9, 'T'),
(19, '26_DJBSES_9423459', '$2y$10$Ef3gH4..iJ5klMnOpQ6rst7UvW8xyaZbcD9efg0hIjKlMnO', 990011, 'Lorenzo', 'Bautista', 'Fajardo', 15, 'Male', '2011-07-03', 'San Carlos, Pangasinan', 'Purok 6 Barangay Arenas, San Carlos, Pangasinan', '09567890125', 'Nora B. Fajardo', '09577890125', 'October 25, 2026 4:55PM', 'profile-image.jpg', 'student', 1, 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 9, 'T');

-- --------------------------------------------------------

--
-- Table structure for table `student_subjects`
--

CREATE TABLE `assinged_teachers` (
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
(4, '26_DJBSES_T6661', '$2y$10$Ulp83..bZ8yaonAflN1afe4dw8Gw0BwIcefSVuHQbwghYtczczGLa', 112233, 'Corina', 'Baybay', 'Magno', 25, 'Female', '2000-12-23', 'Dasol, Pangasinan', 'Purok 5 Poblacion, Dasol, Pangasinan', '09455374819', 'October 24, 2026 4:49PM', 'profile-image.jpg', 'teacher', 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 'Teacher I', 'English'),
(5, '26_DJBSES_T6662', '$2y$10$Qw3rT8..cY9zaonBflO2bge5dx9Hw1CwJdfgTWvIRucxZuydadHMb', 223344, 'Ramon', 'Cruz', 'Delos Santos', 34, 'Male', '1992-03-15', 'Bolinao, Pangasinan', 'Purok 2 Barangay Poblacion, Bolinao, Pangasinan', '09171234567', 'October 25, 2026 9:15AM', 'profile-image.jpg', 'teacher', 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 'Teacher II', 'Mathematics'),
(6, '26_DJBSES_T6663', '$2y$10$Rt4sU9..dZ0abpoCgmP3chf6ey0Ix2DxKegrUXwJSvdyAvzeebINc', 334455, 'Liza', 'Reyes', 'Fernandez', 29, 'Female', '1997-07-08', 'Lingayen, Pangasinan', 'Purok 1 Barangay Libsong, Lingayen, Pangasinan', '09287654321', 'October 25, 2026 10:32AM', 'profile-image.jpg', 'teacher', 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 'Teacher III', 'Science'),
(7, '26_DJBSES_T6664', '$2y$10$Su5tV0..eA1bcqpDhnQ4dig7fz1Jy3EyLfhsVYxKTwezBwafgfcJOd', 445566, 'Mark', 'Tan', 'Villanueva', 41, 'Male', '1985-11-30', 'Alaminos, Pangasinan', 'Purok 3 Barangay Poblacion, Alaminos, Pangasinan', '09393456789', 'October 25, 2026 11:47AM', 'profile-image.jpg', 'teacher', 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 'Master Teacher I', 'Filipino'),
(8, '26_DJBSES_T6665', '$2y$10$Vw6uW1..fB2cdrqEioR5ejh8ga2Kz4FzMgitWZylLUxfaCxbgdKP', 556677, 'Grace', 'Lim', 'Aquino', 27, 'Female', '1999-02-14', 'Dagupan, Pangasinan', 'Purok 4 Barangay Tapuac, Dagupan, Pangasinan', '09456789012', 'October 25, 2026 1:05PM', 'profile-image.jpg', 'teacher', 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL', 'Teacher I', 'MAPEH');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
