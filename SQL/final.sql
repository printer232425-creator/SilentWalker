-- users personal data 1
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `students` (`id`, `account_number`, `pin`, `pin_text`, `first_name`, `middle_name`, `surname`, `age`, `sex`, `birth_date`, `birth_place`, `address`, `contact_number`, `guardian`, `guardian_contact_number`, `registered_date`, `profile_account`, `user_type`, `academic_status`, `school_name`) VALUES
(4, '26_DJBSES_9423444', '$2y$10$YFasM7gytyMcqWWxqStl3eHfmSyzFGqqprbShUdxZKzQEjQsk8lza', 332211, 'Norlie', 'Jay', 'Verzosa', 25, 'Male', '2000-11-24', 'Dasol, Pangasinan', 'Purok 5 Poblacion, Dasol, Pangasinan', '09455374819', 'Corina B. Magno', '09455374819', 'October 24, 2026 4:47PM', 'profile-image.jpg', 'admin', 0, 'DON JUAN BERNAL SR. ELEMENTARY SCHOOL');


CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `enrollment_status` varchar(255) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `emergency_contact_number` varchar(255) DEFAULT NULL,
  `registered_date` varchar(255) DEFAULT NULL,
  `profile_account` varchar(255) NOT NULL DEFAULT 'profile-image.jpg',
  `user_type` varchar(255) NOT NULL DEFAULT 'teacher',
  `school_name` varchar(255) NOT NULL DEFAULT '',
  `position` varchar(50) NOT NULL DEFAULT '',
  `department` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_number` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL DEFAULT '',
  `surname` varchar(255) NOT NULL,
  `quarter` varchar(20) NOT NULL DEFAULT 'Q1',
  `subject` varchar(255) NOT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Passed',
  `section` varchar(255) DEFAULT NULL,
  `teacher` varchar(255) NOT NULL,
  `date_modified` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_number` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL DEFAULT '',
  `surname` varchar(255) NOT NULL,
  `quarter` varchar(20) NOT NULL DEFAULT 'Q1',
  `subject` varchar(255) NOT NULL,
  `grade` decimal(5,2) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Passed',
  `section` varchar(255) DEFAULT NULL,
  `teacher` varchar(255) NOT NULL,
  `date_modified` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `grades` (`id`, `account_number`, `first_name`, `middle_name`, `surname`, `quarter`, `subject`, `grade`, `score`, `status`, `section`, `teacher`, `date_modified`) VALUES
(1, 'ACC-6820', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Math', 88.00, 92.00, 'Passed', 'Grade 8 - Section A', 'Corina Baybay Magno', 'January 5, 2026 8:35AM'),
(2, 'ACC-6820', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Filipino', 91.00, 95.00, 'Passed', 'Grade 8 - Section A', 'Corina Baybay Magno', 'January 5, 2026 8:40AM'),
(3, 'ACC-6820', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Science', 89.00, 93.00, 'Passed', 'Grade 8 - Section A', 'Corina Baybay Magno', 'January 5, 2026 8:45AM'),
(4, 'ACC-6820', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Math', 85.00, 88.00, 'Passed', 'Grade 8 - Section A', 'Corina Baybay Magno', 'February 5, 2026 9:10AM'),
(5, 'ACC-6820', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Filipino', 90.00, 94.00, 'Passed', 'Grade 8 - Section A', 'Corina Baybay Magno', 'February 5, 2026 9:15AM'),
(6, 'ACC-6820', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Science', 87.00, 90.00, 'Passed', 'Grade 8 - Section A', 'Corina Baybay Magno', 'February 5, 2026 9:20AM'),
(7, 'ACC-6820', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Math', 92.00, 96.00, 'Passed', 'Grade 8 - Section A', 'Corina Baybay Magno', 'March 5, 2026 10:05AM'),
(8, 'ACC-6820', 'Norlie Jay', 'Balatico', 'Verzosa', 'Q1', 'Filipino', 88.00, 91.00, 'Passed', 'Grade 8 - Section A', 'Corina Baybay Magno', 'March 5, 2026 10:10AM');


--
-- Dumping data for table `grades`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `department` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_subject_code` (`subject_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `subjects` (`subject_code`, `subject_name`, `department`) VALUES
  ('MATH', 'Mathematics',        'Mathematics'),
  ('FIL',  'Filipino',           'Filipino'),
  ('ENG',  'English',            'English'),
  ('SCI',  'Science',            'Science'),
  ('AP',   'Araling Panlipunan', 'Araling Panlipunan'),
  ('MAPEH','MAPEH',              'MAPEH'),
  ('TLE',  'TLE',                'TLE'),
  ('ESP',  'ESP',                'ESP');

  CREATE TABLE `teacher_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_account_number` varchar(50) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `grade_level` varchar(20) NOT NULL DEFAULT '',
  `section` varchar(50) NOT NULL DEFAULT '',
  `academic_year` varchar(20) NOT NULL DEFAULT '',
  `assigned_at` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_teacher` (`teacher_account_number`),
  KEY `idx_subject` (`subject_id`),
  KEY `idx_year` (`academic_year`),
  UNIQUE KEY `uniq_assignment` (`teacher_account_number`, `subject_id`, `grade_level`, `section`, `academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `assigned_teachers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `account_number` VARCHAR(255) DEFAULT NULL,
    `adviser` VARCHAR(255) DEFAULT NULL,
    `surname` VARCHAR(255) DEFAULT NULL,
    `grade_level` VARCHAR(255) DEFAULT NULL,
    `section` VARCHAR(255) DEFAULT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `days` VARCHAR(255) DEFAULT NULL,
    `start_time` VARCHAR(10) DEFAULT NULL,
    `end_time` VARCHAR(10) DEFAULT NULL,
    `advisory` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_grade_section` (`grade_level`, `section`),
    KEY `idx_account` (`account_number`),
    KEY `idx_adviser` (`adviser`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `attendance_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_number` varchar(20) NOT NULL,
  `date_today` varchar(100) NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `section` varchar(100) NOT NULL,
  `adviser` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;