-- system updates
CREATE TABLE `system_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_number` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `desc` varchar(255) DEFAULT NULL,
  `notif_shown` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- school documents/reports/files/
CREATE TABLE `grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `date_modified` varchar(255) DEFAULT NULL,
  `grade` varchar(255) DEFAULT NULL,
  `score` varchar(255) DEFAULT NULL,
  `section` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- system admin
CREATE TABLE `system_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_number` varchar(255) DEFAULT NULL,
  `pin` varchar(255) DEFAULT NULL,
  `user_type` varchar(255) NOT NULL DEFAULT 'admin',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- system schools
CREATE TABLE `schools` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_name` varchar(255) DEFAULT NULL,
  `school_address` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- users personal data 1
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- users personal data 2
CREATE TABLE `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `user_type` varchar(255) NOT NULL DEFAULT 'teacher',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- users personal data 3
CREATE TABLE `faculties_staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `user_type` varchar(255) NOT NULL DEFAULT 'staff',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- school documents/reports/files/
CREATE TABLE `document_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code_name` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `date_modified` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `file_size` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- student records
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `date_today` varchar(255) DEFAULT NULL,
  `grade` varchar(255) DEFAULT NULL,
  `section` varchar(255) DEFAULT NULL,
  `adviser` varchar(255) DEFAULT NULL,
  `sub_adviser` varchar(255) DEFAULT NULL,
  `profile_account` varchar(255) NOT NULL DEFAULT 'profile-image.jpg',
  `status` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- student records of school
CREATE TABLE `student_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `anonymous` varchar(255) DEFAULT NULL,
  `code_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `grade` varchar(255) DEFAULT NULL,
  `section` varchar(255) DEFAULT NULL,
  `adviser` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `age` varchar(255) NOT NULL,
  `birth_date` varchar(255) NOT NULL,
  `birth_place` varchar(255) NOT NULL,
  `mother_tounge` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `contact_number` varchar(255) DEFAULT NULL,
  `guardian` varchar(255) DEFAULT NULL,
  `guardian_contact_number` varchar(255) DEFAULT NULL,
  `moto_in_life` varchar(255) DEFAULT NULL,
  `registered_date` varchar(255) DEFAULT NULL,
  `profile_account` varchar(255) NOT NULL DEFAULT 'profile-image.jpg',
  `user_type` varchar(255) NOT NULL DEFAULT 'student',
  `school_name` varchar(255) DEFAULT NULL,
  `school_address` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- student's subject records
CREATE TABLE `student_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `anonymous` varchar(255) DEFAULT NULL,
  `code_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `grade` varchar(255) DEFAULT NULL,
  `section` varchar(255) DEFAULT NULL,
  `adviser` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `surname` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `subject_grade` varchar(255) NOT NULL,
  `modified_at` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- school news and updates
CREATE TABLE `school_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_number` varchar(255) DEFAULT NULL,
  `anouncer` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `desc` varchar(255) NOT NULL,
  `when` varchar(255) NOT NULL,
  `where` varchar(255) NOT NULL,
  `shown_info` varchar(255) NOT NULL,
  `modified_at` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- school events and awardees
CREATE TABLE `gawad_parangal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
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
  `modified_at` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- ============================================================
-- SAMPLE DATA — 5 rows per table
-- ============================================================

-- ------------------------------------------------------------
-- grades
-- ------------------------------------------------------------
INSERT INTO `grades`
(`id`, `code_name`, `account_number`, `first_name`, `middle_name`, `surname`, `date_modified`, `grade`, `score`, `section`)
VALUES
(1, 'STU-001', 'ACC-1001', 'Juan',  'Dela',  'Cruz',    '2024-06-15 10:00 AM', '7', '88', 'Sampaguita'),
(2, 'STU-002', 'ACC-1002', 'Ana',   'Santos','Reyes',   '2024-06-15 10:05 AM', '7', '92', 'Sampaguita'),
(3, 'STU-003', 'ACC-1003', 'Mark',  'Lopez', 'Garcia',  '2024-06-15 10:10 AM', '8', '85', 'Rosal'),
(4, 'STU-004', 'ACC-1004', 'Liza',  'Cruz',  'Mendoza', '2024-06-15 10:15 AM', '8', '90', 'Rosal'),
(5, 'STU-005', 'ACC-1005', 'Kevin', 'Reyes', 'Torres',  '2024-06-15 10:20 AM', '9', '87', 'Ilang-Ilang');

-- ------------------------------------------------------------
-- system_updates (5 rows)
-- ------------------------------------------------------------
INSERT INTO `system_updates` (`account_number`, `title`, `desc`, `notif_shown`) VALUES
('ADM-0001', 'System Maintenance', 'Scheduled maintenance on Saturday 10 PM - 12 AM.', 0),
('ADM-0001', 'New Feature Released', 'Grade export to PDF is now available.', 0),
('ADM-0001', 'Security Patch', 'Please update your password for security.', 0),
('ADM-0001', 'Data Backup Completed', 'All records backed up successfully.', 0),
('ADM-0001', 'Holiday Notice', 'Office closed on November 30 (Bonifacio Day).', 0);


-- ============================================================
-- SAMPLE DATA — 5 rows per table, WITH explicit IDs (1-5)
-- anonymous column = animal code names
-- ============================================================

-- ------------------------------------------------------------
-- system_admin  (NO account_number change — kept as-is)
-- ------------------------------------------------------------
INSERT INTO `system_admin` (`id`, `account_number`, `pin`, `user_type`) VALUES
(1, 'ADM-0001', '1111', 'admin');

-- -- ------------------------------------------------------------
-- -- students  (anonymous = animal code names)
-- -- ------------------------------------------------------------
-- INSERT INTO `students`
-- (`id`, `anonymous`, `code_name`, `account_number`, `pin`, `first_name`, `middle_name`, `surname`, `age`, `birth_date`, `birth_place`, `mother_tounge`, `address`, `contact_number`, `guardian`, `guardian_contact_number`, `moto_in_life`, `registered_date`, `profile_account`, `user_type`)
-- VALUES
-- (1, 'Eagle',   'STU-001', 'ACC-1001', 1111, 'Juan',  'Dela',  'Cruz',    '15', '2009-03-12', 'Manila',      'Tagalog',  '123 Rizal St., Manila',    '09170000001', 'Maria Cruz',   '09180000001', 'Study hard, dream big.',  '2024-06-01', 'profile-image.jpg', 'student'),
-- (2, 'Tiger',   'STU-002', 'ACC-1002', 2222, 'Ana',   'Santos','Reyes',   '16', '2008-07-22', 'Quezon City', 'Tagalog',  '45 Mabini Ave., QC',       '09170000002', 'Pedro Reyes',  '09180000002', 'Never give up.',          '2024-06-02', 'profile-image.jpg', 'student'),
-- (3, 'Dolphin', 'STU-003', 'ACC-1003', 3333, 'Mark',  'Lopez', 'Garcia',  '14', '2010-01-05', 'Cebu',        'Cebuano',  '78 Colon St., Cebu',       '09170000003', 'Rosa Garcia',  '09180000003', 'Be kind always.',         '2024-06-03', 'profile-image.jpg', 'student'),
-- (4, 'Lion',    'STU-004', 'ACC-1004', 4444, 'Liza',  'Cruz',  'Mendoza', '15', '2009-11-30', 'Davao',       'Bisaya',   '12 Roxas Blvd., Davao',    '09170000004', 'Jose Mendoza', '09180000004', 'Dream big.',              '2024-06-04', 'profile-image.jpg', 'student'),
-- (5, 'Wolf',    'STU-005', 'ACC-1005', 5555, 'Kevin', 'Reyes', 'Torres',  '16', '2008-05-18', 'Baguio',      'Ilocano',  '56 Session Rd., Baguio',   '09170000005', 'Lorna Torres', '09180000005', 'Stay positive.',          '2024-06-05', 'profile-image.jpg', 'student');

-- -- ------------------------------------------------------------
-- -- teachers  (anonymous = animal code names)
-- -- ------------------------------------------------------------
-- INSERT INTO `teachers`
-- (`id`, `anonymous`, `code_name`, `account_number`, `pin`, `first_name`, `middle_name`, `surname`, `age`, `birth_date`, `birth_place`, `mother_tounge`, `address`, `contact_number`, `moto_in_life`, `registered_date`, `profile_account`, `user_type`)
-- VALUES
-- (1, 'Falcon',  'TCH-001', 'ACC-2001', 1111, 'Maria', 'Lopez', 'Santos',     '35', '1989-02-14', 'Manila',      'Tagalog',  '10 Katipunan Ave., QC',  '09171110001', 'Teach with passion.', '2024-05-01', 'profile-image.jpg', 'teacher'),
-- (2, 'Bear',    'TCH-002', 'ACC-2002', 2222, 'Jose',  'Cruz',  'Reyes',      '40', '1984-09-09', 'Cebu',        'Cebuano',  '22 Osmena Blvd., Cebu',  '09171110002', 'Inspire students.',   '2024-05-02', 'profile-image.jpg', 'teacher'),
-- (3, 'Owl',     'TCH-003', 'ACC-2003', 3333, 'Elena', 'Garcia','Villanueva', '32', '1992-12-01', 'Davao',       'Bisaya',   '33 JP Laurel Ave., Davao','09171110003', 'Learn together.',    '2024-05-03', 'profile-image.jpg', 'teacher'),
-- (4, 'Hawk',    'TCH-004', 'ACC-2004', 4444, 'Paolo', 'Ramos', 'Castro',     '45', '1979-06-20', 'Iloilo',      'Ilonggo',  '44 Diversion Rd., Iloilo','09171110004', 'Lead by example.',   '2024-05-04', 'profile-image.jpg', 'teacher'),
-- (5, 'Panther', 'TCH-005', 'ACC-2005', 5555, 'Carla', 'Reyes', 'Bautista',   '29', '1995-04-25', 'Baguio',      'Ilocano',  '55 Session Rd., Baguio', '09171110005', 'Teach to reach.',    '2024-05-05', 'profile-image.jpg', 'teacher');

-- -- ------------------------------------------------------------
-- -- faculties_staff  (anonymous = animal code names)
-- -- ------------------------------------------------------------
-- INSERT INTO `faculties_staff`
-- (`id`, `anonymous`, `code_name`, `account_number`, `pin`, `first_name`, `middle_name`, `surname`, `age`, `birth_date`, `birth_place`, `mother_tounge`, `address`, `contact_number`, `moto_in_life`, `registered_date`, `profile_account`, `user_type`)
-- VALUES
-- (1, 'Cobra',   'STF-001', 'ACC-3001', 1111, 'Rico',   'Santos', 'Dela Rosa', '38', '1986-03-15', 'Manila',      'Tagalog',  '15 Ayala Ave., Makati',  '09172220001', 'Serve with heart.', '2024-04-01', 'profile-image.jpg', 'staff'),
-- (2, 'Leopard', 'STF-002', 'ACC-3002', 2222, 'Nina',   'Cruz',   'Fernandez', '30', '1994-08-08', 'Quezon City', 'Tagalog',  '25 EDSA, QC',            '09172220002', 'Stay organized.',   '2024-04-02', 'profile-image.jpg', 'staff'),
-- (3, 'Shark',   'STF-003', 'ACC-3003', 3333, 'Ben',    'Reyes',  'Aquino',    '42', '1982-11-11', 'Cebu',        'Cebuano',  '35 Colon St., Cebu',     '09172220003', 'Help others.',      '2024-04-03', 'profile-image.jpg', 'staff'),
-- (4, 'Raven',   'STF-004', 'ACC-3004', 4444, 'Grace',  'Lopez',  'Navarro',   '28', '1996-02-02', 'Davao',       'Bisaya',   '45 Roxas Blvd., Davao',  '09172220004', 'Be reliable.',      '2024-04-04', 'profile-image.jpg', 'staff'),
-- (5, 'Bison',   'STF-005', 'ACC-3005', 5555, 'Dennis', 'Garcia', 'Villamor',  '36', '1988-07-07', 'Baguio',      'Ilocano',  '55 Session Rd., Baguio', '09172220005', 'Stay humble.',      '2024-04-05', 'profile-image.jpg', 'staff');

-- ------------------------------------------------------------
-- document_reports  (NO account_number — kept as-is)
-- ------------------------------------------------------------
INSERT INTO `document_reports`
(`id`, `code_name`, `first_name`, `middle_name`, `surname`, `date_modified`, `file_name`, `file_type`, `file_size`, `status`, `password`)
VALUES
(1, 'DOC-001', 'Juan',  'Dela',  'Cruz',    '2024-06-10 10:00 AM', 'report_q1.pdf',    'PDF',  '1.2 MB', 'Approved', NULL),
(2, 'DOC-002', 'Ana',   'Santos','Reyes',   '2024-06-11 11:30 AM', 'report_q2.pdf',    'PDF',  '980 KB', 'Pending',  NULL),
(3, 'DOC-003', 'Mark',  'Lopez', 'Garcia',  '2024-06-12 09:15 AM', 'attendance.xlsx',  'XLSX', '450 KB', 'Approved', NULL),
(4, 'DOC-004', 'Liza',  'Cruz',  'Mendoza', '2024-06-13 02:45 PM', 'grades.xlsx',      'XLSX', '520 KB', 'Rejected', NULL),
(5, 'DOC-005', 'Kevin', 'Reyes', 'Torres',  '2024-06-14 04:00 PM', 'summary.docx',     'DOCX', '300 KB', 'Pending',  NULL);

-- ------------------------------------------------------------
-- attendance  (no anonymous column in schema)
-- ------------------------------------------------------------
INSERT INTO `attendance`
(`id`, `code_name`, `account_number`, `first_name`, `middle_name`, `surname`, `date_today`, `grade`, `section`, `adviser`, `sub_adviser`, `profile_account`, `status`)
VALUES
(1, 'STU-001', 'ACC-1001', 'Juan',  'Dela',  'Cruz',    '2024-06-15', '7', 'Sampaguita',   'Maria Santos',      'Jose Reyes',     'profile-image.jpg', 'Present'),
(2, 'STU-002', 'ACC-1002', 'Ana',   'Santos','Reyes',   '2024-06-15', '7', 'Sampaguita',   'Maria Santos',      'Jose Reyes',     'profile-image.jpg', 'Present'),
(3, 'STU-003', 'ACC-1003', 'Mark',  'Lopez', 'Garcia',  '2024-06-15', '8', 'Rosal',        'Elena Villanueva',  'Paolo Castro',   'profile-image.jpg', 'Absent'),
(4, 'STU-004', 'ACC-1004', 'Liza',  'Cruz',  'Mendoza', '2024-06-15', '8', 'Rosal',        'Elena Villanueva',  'Paolo Castro',   'profile-image.jpg', 'Late'),
(5, 'STU-005', 'ACC-1005', 'Kevin', 'Reyes', 'Torres',  '2024-06-15', '9', 'Ilang-Ilang',  'Carla Bautista',    'Maria Santos',   'profile-image.jpg', 'Present');

INSERT INTO `student_records`
(`id`, `anonymous`, `code_name`, `account_number`, `grade`, `section`, `adviser`, `status`, `first_name`, `middle_name`, `surname`, `age`, `birth_date`, `birth_place`, `mother_tounge`, `address`, `contact_number`, `guardian`, `guardian_contact_number`, `moto_in_life`, `registered_date`, `profile_account`, `user_type`, `school_name`, `school_address`)
VALUES
-- ============================================================
-- DE GUZMAN INTEGRATED SCHOOL (5 students)
-- ============================================================
(26, 'Lion',      'STU-026', 'ACC-1026', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Ramon',    'Bautista', 'Salazar',    '15', '2009-04-18', 'Dagupan',    'Pangasinense', 'Poblacion, Dasol, Pangasinan',       '09170000026', 'Lorna Salazar',     '09180000026', 'Learn every day.',    '2024-06-26', 'profile-image.jpg', 'student', 'DE GUZMAN INTEGRATED SCHOOL',                    'Poblacion, Dasol, Pangasinan'),
(27, 'Tiger',     'STU-027', 'ACC-1027', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Cristina', 'Villanueva','Aguilar',    '16', '2008-08-09', 'Alaminos',   'Pangasinense', 'Poblacion, Dasol, Pangasinan',       '09170000027', 'Pedro Aguilar',     '09180000027', 'Respect everyone.',   '2024-06-27', 'profile-image.jpg', 'student', 'DE GUZMAN INTEGRATED SCHOOL',                    'Poblacion, Dasol, Pangasinan'),
(28, 'Dolphin',   'STU-028', 'ACC-1028', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Anthony',  'Mendoza',   'Bautista',   '14', '2010-02-21', 'Lingayen',   'Pangasinense', 'Poblacion, Dasol, Pangasinan',       '09170000028', 'Rosa Bautista',     '09180000028', 'Stay humble.',        '2024-06-28', 'profile-image.jpg', 'student', 'DE GUZMAN INTEGRATED SCHOOL',                    'Poblacion, Dasol, Pangasinan'),
(29, 'Wolf',      'STU-029', 'ACC-1029', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Patricia', 'Reyes',     'Navarro',    '15', '2009-12-03', 'Urdaneta',   'Pangasinense', 'Poblacion, Dasol, Pangasinan',       '09170000029', 'Jose Navarro',      '09180000029', 'Dream big.',          '2024-06-29', 'profile-image.jpg', 'student', 'DE GUZMAN INTEGRATED SCHOOL',                    'Poblacion, Dasol, Pangasinan'),
(30, 'Falcon',    'STU-030', 'ACC-1030', '9', 'Ilang-Ilang','Carla Bautista',   'Active', 'Daniel',    'Garcia',    'Villamor',   '16', '2008-05-30', 'San Carlos', 'Pangasinense', 'Poblacion, Dasol, Pangasinan',       '09170000030', 'Liza Villamor',     '09180000030', 'Never give up.',      '2024-06-30', 'profile-image.jpg', 'student', 'DE GUZMAN INTEGRATED SCHOOL',                    'Poblacion, Dasol, Pangasinan'),

-- ============================================================
-- VIGA ELEMENTARY SCHOOL (5 students)
-- ============================================================
(31, 'Bear',      'STU-031', 'ACC-1031', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Michelle',  'Cruz',      'Aquino',     '15', '2009-09-14', 'Binmaley',   'Pangasinense', 'Viga, Dasol, Pangasinan',            '09170000031', 'Rico Aquino',       '09180000031', 'Be kind always.',     '2024-07-01', 'profile-image.jpg', 'student', 'VIGA ELEMENTARY SCHOOL',                         'Viga, Dasol, Pangasinan'),
(32, 'Owl',       'STU-032', 'ACC-1032', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Christian', 'Lopez',     'Del Rosario','14', '2010-03-25', 'Calasiao',   'Pangasinense', 'Viga, Dasol, Pangasinan',            '09170000032', 'Nina Del Rosario',  '09180000032', 'Help others.',        '2024-07-02', 'profile-image.jpg', 'student', 'VIGA ELEMENTARY SCHOOL',                         'Viga, Dasol, Pangasinan'),
(33, 'Hawk',      'STU-033', 'ACC-1033', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Angela',    'Fernandez', 'Villareal',  '16', '2008-11-07', 'Mangaldan',  'Pangasinense', 'Viga, Dasol, Pangasinan',            '09170000033', 'Ben Villareal',     '09180000033', 'Stay positive.',      '2024-07-03', 'profile-image.jpg', 'student', 'VIGA ELEMENTARY SCHOOL',                         'Viga, Dasol, Pangasinan'),
(34, 'Panther',   'STU-034', 'ACC-1034', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Roberto',   'Navarro',   'Castro',     '15', '2009-06-19', 'Bani',       'Pangasinense', 'Viga, Dasol, Pangasinan',            '09170000034', 'Grace Castro',      '09180000034', 'Lead by example.',    '2024-07-04', 'profile-image.jpg', 'student', 'VIGA ELEMENTARY SCHOOL',                         'Viga, Dasol, Pangasinan'),
(35, 'Cobra',     'STU-035', 'ACC-1035', '9', 'Ilang-Ilang','Carla Bautista',   'Active', 'Jennifer',  'Aquino',    'Santiago',   '14', '2010-01-28', 'Bugallon',   'Pangasinense', 'Viga, Dasol, Pangasinan',            '09170000035', 'Dennis Santiago',   '09180000035', 'Serve with heart.',   '2024-07-05', 'profile-image.jpg', 'student', 'VIGA ELEMENTARY SCHOOL',                         'Viga, Dasol, Pangasinan'),

-- ============================================================
-- PATAR ELEMENTARY SCHOOL (5 students)
-- ============================================================
(36, 'Leopard',   'STU-036', 'ACC-1036', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Francis',   'Del Rosario','Mendoza',   '16', '2008-07-11', 'Aguilar',    'Pangasinense', 'Patar, Dasol, Pangasinan',           '09170000036', 'Lorna Mendoza',     '09180000036', 'Work hard.',          '2024-07-06', 'profile-image.jpg', 'student', 'PATAR ELEMENTARY SCHOOL',                        'Patar, Dasol, Pangasinan'),
(37, 'Shark',     'STU-037', 'ACC-1037', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Marvin',    'Castro',    'Soriano',    '15', '2009-10-04', 'Mabini',     'Pangasinense', 'Patar, Dasol, Pangasinan',           '09170000037', 'Cristina Soriano',  '09180000037', 'Be honest.',          '2024-07-07', 'profile-image.jpg', 'student', 'PATAR ELEMENTARY SCHOOL',                        'Patar, Dasol, Pangasinan'),
(38, 'Raven',     'STU-038', 'ACC-1038', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Lovely',    'Santiago',  'Velasco',    '14', '2010-04-16', 'Burgos',     'Pangasinense', 'Patar, Dasol, Pangasinan',           '09170000038', 'Roberto Velasco',   '09180000038', 'Be a blessing.',      '2024-07-08', 'profile-image.jpg', 'student', 'PATAR ELEMENTARY SCHOOL',                        'Patar, Dasol, Pangasinan'),
(39, 'Bison',     'STU-039', 'ACC-1039', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Ronald',    'Velasco',   'Bautista',   '16', '2008-09-23', 'Manaoag',    'Pangasinense', 'Patar, Dasol, Pangasinan',           '09170000039', 'Michelle Bautista', '09180000039', 'Keep going.',         '2024-07-09', 'profile-image.jpg', 'student', 'PATAR ELEMENTARY SCHOOL',                        'Patar, Dasol, Pangasinan'),
(40, 'Cheetah',   'STU-040', 'ACC-1040', '9', 'Ilang-Ilang','Carla Bautista',   'Active', 'Sheila',    'Soriano',   'Gonzales',   '15', '2009-02-08', 'Sta. Barbara','Pangasinense','Patar, Dasol, Pangasinan',           '09170000040', 'Marvin Gonzales',   '09180000040', 'Stay strong.',        '2024-07-10', 'profile-image.jpg', 'student', 'PATAR ELEMENTARY SCHOOL',                        'Patar, Dasol, Pangasinan'),

-- ============================================================
-- BARLO INTEGRATED SCHOOL (5 students)
-- ============================================================
(41, 'Panda',     'STU-041', 'ACC-1041', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Jerome',    'Gonzales',  'Ramos',      '14', '2010-06-30', 'Sual',       'Pangasinense', 'Barlo, Dasol, Pangasinan',           '09170000041', 'Jennifer Ramos',    '09180000041', 'Be patient.',         '2024-07-11', 'profile-image.jpg', 'student', 'BARLO INTEGRATED SCHOOL',                        'Barlo, Dasol, Pangasinan'),
(42, 'Koala',     'STU-042', 'ACC-1042', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Catherine', 'Ramos',     'Torres',     '16', '2008-03-17', 'Labrador',   'Pangasinense', 'Barlo, Dasol, Pangasinan',           '09170000042', 'Francis Torres',    '09180000042', 'Be courageous.',      '2024-07-12', 'profile-image.jpg', 'student', 'BARLO INTEGRATED SCHOOL',                        'Barlo, Dasol, Pangasinan'),
(43, 'Giraffe',   'STU-043', 'ACC-1043', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Jonathan',  'Torres',    'Flores',     '15', '2009-08-12', 'Infanta',    'Pangasinense', 'Barlo, Dasol, Pangasinan',           '09170000043', 'Lovely Flores',     '09180000043', 'Stay focused.',       '2024-07-13', 'profile-image.jpg', 'student', 'BARLO INTEGRATED SCHOOL',                        'Barlo, Dasol, Pangasinan'),
(44, 'Zebra',     'STU-044', 'ACC-1044', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Melanie',   'Flores',    'Domingo',    '14', '2010-05-20', 'Dasol',      'Pangasinense', 'Barlo, Dasol, Pangasinan',           '09170000044', 'Ronald Domingo',    '09180000044', 'Be grateful.',        '2024-07-14', 'profile-image.jpg', 'student', 'BARLO INTEGRATED SCHOOL',                        'Barlo, Dasol, Pangasinan'),
(45, 'Hippo',     'STU-045', 'ACC-1045', '9', 'Ilang-Ilang','Carla Bautista',   'Active', 'Reynaldo',  'Domingo',   'Estrada',    '16', '2008-12-25', 'Dasol',      'Pangasinense', 'Barlo, Dasol, Pangasinan',           '09170000045', 'Sheila Estrada',    '09180000045', 'Keep the faith.',     '2024-07-15', 'profile-image.jpg', 'student', 'BARLO INTEGRATED SCHOOL',                        'Barlo, Dasol, Pangasinan'),

-- ============================================================
-- ANAPAO ELEMENTARY SCHOOL (5 students)
-- ============================================================
(46, 'Rhino',     'STU-046', 'ACC-1046', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Isabel',    'Bautista',  'Navarro',    '15', '2009-05-09', 'Dasol',      'Pangasinense', 'Anapao, Dasol, Pangasinan',          '09170000046', 'Cesar Navarro',     '09180000046', 'Be diligent.',        '2024-07-16', 'profile-image.jpg', 'student', 'ANAPAO ELEMENTARY SCHOOL',                       'Anapao, Dasol, Pangasinan'),
(47, 'Turtle',    'STU-047', 'ACC-1047', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Ferdinand', 'Aguilar',   'Castro',     '16', '2008-10-02', 'Dasol',      'Pangasinense', 'Anapao, Dasol, Pangasinan',          '09170000047', 'Lorna Castro',      '09180000047', 'Stay positive.',      '2024-07-17', 'profile-image.jpg', 'student', 'ANAPAO ELEMENTARY SCHOOL',                       'Anapao, Dasol, Pangasinan'),
(48, 'Crocodile', 'STU-048', 'ACC-1048', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Elaine',    'Navarro',   'Soriano',    '14', '2010-07-19', 'Dasol',      'Pangasinense', 'Anapao, Dasol, Pangasinan',          '09170000048', 'Dennis Soriano',    '09180000048', 'Dream big.',          '2024-07-18', 'profile-image.jpg', 'student', 'ANAPAO ELEMENTARY SCHOOL',                       'Anapao, Dasol, Pangasinan'),
(49, 'Lion',      'STU-049', 'ACC-1049', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Gilbert',   'Castro',    'Ramos',      '15', '2009-11-25', 'Dasol',      'Pangasinense', 'Anapao, Dasol, Pangasinan',          '09170000049', 'Marissa Ramos',     '09180000049', 'Be humble.',          '2024-07-19', 'profile-image.jpg', 'student', 'ANAPAO ELEMENTARY SCHOOL',                       'Anapao, Dasol, Pangasinan'),
(50, 'Tiger',     'STU-050', 'ACC-1050', '9', 'Ilang-Ilang','Carla Bautista',   'Active', 'Rosalie',   'Soriano',   'Velasco',    '16', '2008-06-13', 'Dasol',      'Pangasinense', 'Anapao, Dasol, Pangasinan',          '09170000050', 'Antonio Velasco',   '09180000050', 'Work hard.',          '2024-07-20', 'profile-image.jpg', 'student', 'ANAPAO ELEMENTARY SCHOOL',                       'Anapao, Dasol, Pangasinan'),

-- ============================================================
-- DAB MEMORIAL ELEMENTARY SCHOOL (5 students)
-- ============================================================
(51, 'Dolphin',   'STU-051', 'ACC-1051', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Arnel',     'Ramos',     'Bautista',   '15', '2009-01-10', 'Dasol',      'Pangasinense', 'Poblacion, Dasol, Pangasinan',       '09170000051', 'Liza Bautista',     '09180000051', 'Be kind.',            '2024-07-21', 'profile-image.jpg', 'student', 'DAB MEMORIAL ELEMENTARY SCHOOL',                 'Poblacion, Dasol, Pangasinan'),
(52, 'Wolf',      'STU-052', 'ACC-1052', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Vilma',     'Velasco',   'Mendoza',    '16', '2008-04-22', 'Dasol',      'Pangasinense', 'Poblacion, Dasol, Pangasinan',       '09170000052', 'Cesar Mendoza',     '09180000052', 'Stay strong.',        '2024-07-22', 'profile-image.jpg', 'student', 'DAB MEMORIAL ELEMENTARY SCHOOL',                 'Poblacion, Dasol, Pangasinan'),
(53, 'Falcon',    'STU-053', 'ACC-1053', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Eduardo',   'Bautista',  'Soriano',    '14', '2010-08-08', 'Dasol',      'Pangasinense', 'Poblacion, Dasol, Pangasinan',       '09170000053', 'Marissa Soriano',   '09180000053', 'Stay humble.',        '2024-07-23', 'profile-image.jpg', 'student', 'DAB MEMORIAL ELEMENTARY SCHOOL',                 'Poblacion, Dasol, Pangasinan'),
(54, 'Bear',      'STU-054', 'ACC-1054', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Norma',     'Soriano',   'Castro',     '15', '2009-03-30', 'Dasol',      'Pangasinense', 'Poblacion, Dasol, Pangasinan',       '09170000054', 'Pedro Castro',      '09180000054', 'Be patient.',         '2024-07-24', 'profile-image.jpg', 'student', 'DAB MEMORIAL ELEMENTARY SCHOOL',                 'Poblacion, Dasol, Pangasinan'),
(55, 'Owl',       'STU-055', 'ACC-1055', '9', 'Ilang-Ilang','Carla Bautista',   'Active', 'Carlito',   'Navarro',   'Flores',     '16', '2008-09-15', 'Dasol',      'Pangasinense', 'Poblacion, Dasol, Pangasinan',       '09170000055', 'Gemma Flores',      '09180000055', 'Stay focused.',       '2024-07-25', 'profile-image.jpg', 'student', 'DAB MEMORIAL ELEMENTARY SCHOOL',                 'Poblacion, Dasol, Pangasinan'),

-- ============================================================
-- SAPA GRANDE INTEGRATED SCHOOL (5 students)
-- ============================================================
(56, 'Hawk',      'STU-056', 'ACC-1056', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Marilou',   'Castro',    'Gonzales',   '15', '2009-06-04', 'Dasol',      'Pangasinense', 'Sapa Grande, Dasol, Pangasinan',     '09170000056', 'Fernando Gonzales', '09180000056', 'Be grateful.',        '2024-07-26', 'profile-image.jpg', 'student', 'SAPA GRANDE INTEGRATED SCHOOL',                  'Sapa Grande, Dasol, Pangasinan'),
(57, 'Panther',   'STU-057', 'ACC-1057', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Nestor',    'Gonzales',  'Aguilar',    '16', '2008-12-11', 'Dasol',      'Pangasinense', 'Sapa Grande, Dasol, Pangasinan',     '09170000057', 'Rosalie Aguilar',   '09180000057', 'Stay honest.',        '2024-07-27', 'profile-image.jpg', 'student', 'SAPA GRANDE INTEGRATED SCHOOL',                  'Sapa Grande, Dasol, Pangasinan'),
(58, 'Cobra',     'STU-058', 'ACC-1058', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Teresita',  'Aguilar',   'Bautista',   '14', '2010-09-23', 'Dasol',      'Pangasinense', 'Sapa Grande, Dasol, Pangasinan',     '09170000058', 'Ramon Bautista',    '09180000058', 'Be thankful.',        '2024-07-28', 'profile-image.jpg', 'student', 'SAPA GRANDE INTEGRATED SCHOOL',                  'Sapa Grande, Dasol, Pangasinan'),
(59, 'Leopard',   'STU-059', 'ACC-1059', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Rodolfo',   'Bautista',  'Del Rosario','15', '2009-02-18', 'Dasol',      'Pangasinense', 'Sapa Grande, Dasol, Pangasinan',     '09170000059', 'Lorna Del Rosario', '09180000059', 'Be courteous.',       '2024-07-29', 'profile-image.jpg', 'student', 'SAPA GRANDE INTEGRATED SCHOOL',                  'Sapa Grande, Dasol, Pangasinan'),
(60, 'Shark',     'STU-060', 'ACC-1060', '9', 'Ilang-Ilang','Carla Bautista',   'Active', 'Alicia',    'Del Rosario','Navarro',   '16', '2008-11-05', 'Dasol',      'Pangasinense', 'Sapa Grande, Dasol, Pangasinan',     '09170000060', 'Rico Navarro',      '09180000060', 'Stay humble.',        '2024-07-30', 'profile-image.jpg', 'student', 'SAPA GRANDE INTEGRATED SCHOOL',                  'Sapa Grande, Dasol, Pangasinan'),

-- ============================================================
-- CABINUANGAN ELEMENTARY SCHOOL (5 students)
-- ============================================================
(61, 'Raven',     'STU-061', 'ACC-1061', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Cristobal', 'Navarro',   'Villamor',   '15', '2009-08-27', 'Dasol',      'Pangasinense', 'Cabinuangan, Dasol, Pangasinan',     '09170000061', 'Marites Villamor',  '09180000061', 'Stay positive.',      '2024-08-01', 'profile-image.jpg', 'student', 'CABINUANGAN ELEMENTARY SCHOOL',                  'Cabinuangan, Dasol, Pangasinan'),
(62, 'Bison',     'STU-062', 'ACC-1062', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Editha',    'Villamor',  'Castro',     '16', '2008-01-19', 'Dasol',      'Pangasinense', 'Cabinuangan, Dasol, Pangasinan',     '09170000062', 'Rey Castro',        '09180000062', 'Keep going.',         '2024-08-02', 'profile-image.jpg', 'student', 'CABINUANGAN ELEMENTARY SCHOOL',                  'Cabinuangan, Dasol, Pangasinan'),
(63, 'Cheetah',   'STU-063', 'ACC-1063', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Ernesto',   'Castro',    'Gonzales',   '14', '2010-10-15', 'Dasol',      'Pangasinense', 'Cabinuangan, Dasol, Pangasinan',     '09170000063', 'Marilou Gonzales',  '09180000063', 'Be loyal.',           '2024-08-03', 'profile-image.jpg', 'student', 'CABINUANGAN ELEMENTARY SCHOOL',                  'Cabinuangan, Dasol, Pangasinan'),
(64, 'Panda',     'STU-064', 'ACC-1064', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Lourdes',   'Gonzales',  'Flores',     '15', '2009-05-08', 'Dasol',      'Pangasinense', 'Cabinuangan, Dasol, Pangasinan',     '09170000064', 'Mario Flores',      '09180000064', 'Be faithful.',        '2024-08-04', 'profile-image.jpg', 'student', 'CABINUANGAN ELEMENTARY SCHOOL',                  'Cabinuangan, Dasol, Pangasinan'),
(65, 'Koala',     'STU-065', 'ACC-1065', '9', 'Ilang-Ilang','Carla Bautista',   'Active', 'Arturo',    'Flores',    'Soriano',    '16', '2008-03-22', 'Dasol',      'Pangasinense', 'Cabinuangan, Dasol, Pangasinan',     '09170000065', 'Luzviminda Soriano','09180000065', 'Be considerate.',     '2024-08-05', 'profile-image.jpg', 'student', 'CABINUANGAN ELEMENTARY SCHOOL',                  'Cabinuangan, Dasol, Pangasinan'),

-- ============================================================
-- DON TEMOTEO NABAL SR. ELEMENTARY SCHOOL (5 students)
-- ============================================================
(66, 'Giraffe',   'STU-066', 'ACC-1066', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Benigno',   'Soriano',   'Aguilar',    '15', '2009-07-04', 'Dasol',      'Pangasinense', 'Poblacion, Dasol, Pangasinan',       '09170000066', 'Rosalinda Aguilar', '09180000066', 'Serve others.',       '2024-08-06', 'profile-image.jpg', 'student', 'DON TEMOTEO NABAL SR. ELEMENTARY SCHOOL',        'Poblacion, Dasol, Pangasinan'),
(67, 'Zebra',     'STU-067', 'ACC-1067', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Imelda',    'Aguilar',   'Bautista',   '16', '2008-02-14', 'Dasol',      'Pangasinense', 'Poblacion, Dasol, Pangasinan',       '09170000067', 'Crispin Bautista',  '09180000067', 'Be generous.',        '2024-08-07', 'profile-image.jpg', 'student', 'DON TEMOTEO NABAL SR. ELEMENTARY SCHOOL',        'Poblacion, Dasol, Pangasinan'),
(68, 'Hippo',     'STU-068', 'ACC-1068', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Salvador',  'Bautista',  'Del Rosario','14', '2010-11-06', 'Dasol',      'Pangasinense', 'Poblacion, Dasol, Pangasinan',       '09170000068', 'Estrella Del Rosario','09180000068', 'Be hopeful.',        '2024-08-08', 'profile-image.jpg', 'student', 'DON TEMOTEO NABAL SR. ELEMENTARY SCHOOL',        'Poblacion, Dasol, Pangasinan'),
(69, 'Rhino',     'STU-069', 'ACC-1069', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Amelia',    'Del Rosario','Navarro',   '15', '2009-10-18', 'Dasol',      'Pangasinense', 'Poblacion, Dasol, Pangasinan',       '09170000069', 'Ariel Navarro',     '09180000069', 'Be optimistic.',      '2024-08-09', 'profile-image.jpg', 'student', 'DON TEMOTEO NABAL SR. ELEMENTARY SCHOOL',        'Poblacion, Dasol, Pangasinan'),
(70, 'Turtle',    'STU-070', 'ACC-1070', '9', 'Ilang-Ilang','Carla Bautista',   'Active', 'Alfredo',   'Navarro',   'Villamor',   '16', '2008-07-29', 'Dasol',      'Pangasinense', 'Poblacion, Dasol, Pangasinan',       '09170000070', 'Melba Villamor',    '09180000070', 'Be resilient.',       '2024-08-10', 'profile-image.jpg', 'student', 'DON TEMOTEO NABAL SR. ELEMENTARY SCHOOL',        'Poblacion, Dasol, Pangasinan'),

-- ============================================================
-- EGUIA NATIONAL HIGH SCHOOL (5 students)
-- ============================================================
(71, 'Crocodile', 'STU-071', 'ACC-1071', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Joselito',  'Villamor',  'Castro',     '15', '2009-04-02', 'Dasol',      'Pangasinense', 'Eguia, Dasol, Pangasinan',           '09170000071', 'Rowena Castro',     '09180000071', 'Stay determined.',    '2024-08-11', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL',                     'Eguia, Dasol, Pangasinan'),
(72, 'Lion',      'STU-072', 'ACC-1072', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Maribel',   'Castro',    'Gonzales',   '16', '2008-09-09', 'Dasol',      'Pangasinense', 'Eguia, Dasol, Pangasinan',           '09170000072', 'Danilo Gonzales',   '09180000072', 'Be ambitious.',       '2024-08-12', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL',                     'Eguia, Dasol, Pangasinan'),
(73, 'Tiger',     'STU-073', 'ACC-1073', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Danilo',    'Gonzales',  'Flores',     '14', '2010-12-20', 'Dasol',      'Pangasinense', 'Eguia, Dasol, Pangasinan',           '09170000073', 'Evelyn Flores',     '09180000073', 'Be bold.',            '2024-08-13', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL',                     'Eguia, Dasol, Pangasinan'),
(74, 'Dolphin',   'STU-074', 'ACC-1074', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Evelyn',    'Flores',    'Soriano',    '15', '2009-01-30', 'Dasol',      'Pangasinense', 'Eguia, Dasol, Pangasinan',           '09170000074', 'Rogelio Soriano',   '09180000074', 'Be brave.',           '2024-08-14', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL',                     'Eguia, Dasol, Pangasinan'),
(75, 'Wolf',      'STU-075', 'ACC-1075', '9', 'Ilang-Ilang','Carla Bautista',   'Active', 'Rogelio',   'Soriano',   'Aguilar',    '16', '2008-11-14', 'Dasol',      'Pangasinense', 'Eguia, Dasol, Pangasinan',           '09170000075', 'Aurora Aguilar',    '09180000075', 'Stay true.',          '2024-08-15', 'profile-image.jpg', 'student', 'EGUIA NATIONAL HIGH SCHOOL',                     'Eguia, Dasol, Pangasinan'),

-- ============================================================
-- PETAL ELEMENTARY SCHOOL (5 students)
-- ============================================================
(76, 'Falcon',    'STU-076', 'ACC-1076', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Rodel',     'Aguilar',   'Bautista',   '15', '2009-06-21', 'Dasol',      'Pangasinense', 'Petal, Dasol, Pangasinan',           '09170000076', 'Marissa Bautista',  '09180000076', 'Be responsible.',     '2024-08-16', 'profile-image.jpg', 'student', 'PETAL ELEMENTARY SCHOOL',                        'Petal, Dasol, Pangasinan'),
(77, 'Bear',      'STU-077', 'ACC-1077', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Marissa',   'Bautista',  'Del Rosario','16', '2008-05-07', 'Dasol',      'Pangasinense', 'Petal, Dasol, Pangasinan',           '09170000077', 'Edgar Del Rosario', '09180000077', 'Stay humble.',        '2024-08-17', 'profile-image.jpg', 'student', 'PETAL ELEMENTARY SCHOOL',                        'Petal, Dasol, Pangasinan'),
(78, 'Owl',       'STU-078', 'ACC-1078', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Edgar',     'Del Rosario','Navarro',   '14', '2010-03-12', 'Dasol',      'Pangasinense', 'Petal, Dasol, Pangasinan',           '09170000078', 'Lorna Navarro',     '09180000078', 'Be respectful.',      '2024-08-18', 'profile-image.jpg', 'student', 'PETAL ELEMENTARY SCHOOL',                        'Petal, Dasol, Pangasinan'),
(79, 'Hawk',      'STU-079', 'ACC-1079', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Lorna',     'Navarro',   'Villamor',   '15', '2009-09-01', 'Dasol',      'Pangasinense', 'Petal, Dasol, Pangasinan',           '09170000079', 'Ernesto Villamor',  '09180000079', 'Be strong.',          '2024-08-19', 'profile-image.jpg', 'student', 'PETAL ELEMENTARY SCHOOL',                        'Petal, Dasol, Pangasinan'),
(80, 'Panther',   'STU-080', 'ACC-1080', '9', 'Ilang-Ilang','Carla Bautista',   'Active', 'Ernesto',   'Villamor',  'Castro',     '16', '2008-02-26', 'Dasol',      'Pangasinense', 'Petal, Dasol, Pangasinan',           '09170000080', 'Josie Castro',      '09180000080', 'Be humble.',          '2024-08-20', 'profile-image.jpg', 'student', 'PETAL ELEMENTARY SCHOOL',                        'Petal, Dasol, Pangasinan'),

-- ============================================================
-- ILIO-ILIO ELEMENTARY SCHOOL (5 students)
-- ============================================================
(81, 'Cobra',     'STU-081', 'ACC-1081', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Josie',     'Castro',    'Gonzales',   '15', '2009-10-10', 'Dasol',      'Pangasinense', 'Ilio-Ilio, Dasol, Pangasinan',       '09170000081', 'Nestor Gonzales',   '09180000081', 'Be kind.',            '2024-08-21', 'profile-image.jpg', 'student', 'ILIO-ILIO ELEMENTARY SCHOOL',                    'Ilio-Ilio, Dasol, Pangasinan'),
(82, 'Leopard',   'STU-082', 'ACC-1082', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Ignacio',   'Gonzales',  'Flores',     '16', '2008-06-15', 'Dasol',      'Pangasinense', 'Ilio-Ilio, Dasol, Pangasinan',       '09170000082', 'Cristina Flores',   '09180000082', 'Be patient.',         '2024-08-22', 'profile-image.jpg', 'student', 'ILIO-ILIO ELEMENTARY SCHOOL',                    'Ilio-Ilio, Dasol, Pangasinan'),
(83, 'Shark',     'STU-083', 'ACC-1083', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Cristina',  'Flores',    'Soriano',    '14', '2010-01-17', 'Dasol',      'Pangasinense', 'Ilio-Ilio, Dasol, Pangasinan',       '09170000083', 'Gilbert Soriano',   '09180000083', 'Be prayerful.',       '2024-08-23', 'profile-image.jpg', 'student', 'ILIO-ILIO ELEMENTARY SCHOOL',                    'Ilio-Ilio, Dasol, Pangasinan'),
(84, 'Raven',     'STU-084', 'ACC-1084', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Gilbert',   'Soriano',   'Aguilar',    '15', '2009-04-07', 'Dasol',      'Pangasinense', 'Ilio-Ilio, Dasol, Pangasinan',       '09170000084', 'Editha Aguilar',    '09180000084', 'Be cautious.',        '2024-08-24', 'profile-image.jpg', 'student', 'ILIO-ILIO ELEMENTARY SCHOOL',                    'Ilio-Ilio, Dasol, Pangasinan'),
(85, 'Bison',     'STU-085', 'ACC-1085', '9', 'Ilang-Ilang','Carla Bautista',   'Active', 'Editha',    'Aguilar',   'Bautista',   '16', '2008-09-29', 'Dasol',      'Pangasinense', 'Ilio-Ilio, Dasol, Pangasinan',       '09170000085', 'Salvador Bautista', '09180000085', 'Be appreciative.',    '2024-08-25', 'profile-image.jpg', 'student', 'ILIO-ILIO ELEMENTARY SCHOOL',                    'Ilio-Ilio, Dasol, Pangasinan'),

-- ============================================================
-- MAC ARTHUR SALANGA SR. MEMORIAL ELEMENTARY SCHOOL (5 students)
-- ============================================================
(86, 'Cheetah',   'STU-086', 'ACC-1086', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Salvador',  'Bautista',  'Del Rosario','15', '2009-12-12', 'Dasol',      'Pangasinense', 'Salanga, Dasol, Pangasinan',         '09170000086', 'Amelia Del Rosario','09180000086', 'Serve others.',       '2024-08-26', 'profile-image.jpg', 'student', 'MAC ARTHUR SALANGA SR. MEMORIAL ELEMENTARY SCHOOL','Salanga, Dasol, Pangasinan'),
(87, 'Panda',     'STU-087', 'ACC-1087', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Amelia',    'Del Rosario','Navarro',   '16', '2008-08-24', 'Dasol',      'Pangasinense', 'Salanga, Dasol, Pangasinan',         '09170000087', 'Alfredo Navarro',   '09180000087', 'Be honest.',          '2024-08-27', 'profile-image.jpg', 'student', 'MAC ARTHUR SALANGA SR. MEMORIAL ELEMENTARY SCHOOL','Salanga, Dasol, Pangasinan'),
(88, 'Koala',     'STU-088', 'ACC-1088', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Alfredo',   'Navarro',   'Villamor',   '14', '2010-04-25', 'Dasol',      'Pangasinense', 'Salanga, Dasol, Pangasinan',         '09170000088', 'Milagros Villamor', '09180000088', 'Be just.',            '2024-08-28', 'profile-image.jpg', 'student', 'MAC ARTHUR SALANGA SR. MEMORIAL ELEMENTARY SCHOOL','Salanga, Dasol, Pangasinan'),
(89, 'Giraffe',   'STU-089', 'ACC-1089', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Milagros',  'Villamor',  'Castro',     '15', '2009-07-18', 'Dasol',      'Pangasinense', 'Salanga, Dasol, Pangasinan',         '09170000089', 'Arturo Castro',     '09180000089', 'Be caring.',          '2024-08-29', 'profile-image.jpg', 'student', 'MAC ARTHUR SALANGA SR. MEMORIAL ELEMENTARY SCHOOL','Salanga, Dasol, Pangasinan'),
(90, 'Zebra',     'STU-090', 'ACC-1090', '9', 'Ilang-Ilang','Carla Bautista',   'Active', 'Arturo',    'Castro',    'Gonzales',   '16', '2008-10-30', 'Dasol',      'Pangasinense', 'Salanga, Dasol, Pangasinan',         '09170000090', 'Lourdes Gonzales',  '09180000090', 'Be gentle.',          '2024-08-30', 'profile-image.jpg', 'student', 'MAC ARTHUR SALANGA SR. MEMORIAL ELEMENTARY SCHOOL','Salanga, Dasol, Pangasinan'),

-- ============================================================
-- VILLACORTA ELEMENTARY SCHOOL (5 students)
-- ============================================================
(91, 'Hippo',     'STU-091', 'ACC-1091', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Lourdes',   'Gonzales',  'Flores',     '15', '2009-02-25', 'Dasol',      'Pangasinense', 'Villacorta, Dasol, Pangasinan',      '09170000091', 'Benigno Flores',    '09180000091', 'Be a blessing.',      '2024-09-01', 'profile-image.jpg', 'student', 'VILLACORTA ELEMENTARY SCHOOL',                   'Villacorta, Dasol, Pangasinan'),
(92, 'Rhino',     'STU-092', 'ACC-1092', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Benigno',   'Flores',    'Soriano',    '16', '2008-03-03', 'Dasol',      'Pangasinense', 'Villacorta, Dasol, Pangasinan',      '09170000092', 'Imelda Soriano',    '09180000092', 'Be humble.',          '2024-09-02', 'profile-image.jpg', 'student', 'VILLACORTA ELEMENTARY SCHOOL',                   'Villacorta, Dasol, Pangasinan'),
(93, 'Turtle',    'STU-093', 'ACC-1093', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Imelda',    'Soriano',   'Aguilar',    '14', '2010-07-27', 'Dasol',      'Pangasinense', 'Villacorta, Dasol, Pangasinan',      '09170000093', 'Rodel Aguilar',     '09180000093', 'Be thoughtful.',      '2024-09-03', 'profile-image.jpg', 'student', 'VILLACORTA ELEMENTARY SCHOOL',                   'Villacorta, Dasol, Pangasinan'),
(94, 'Crocodile', 'STU-094', 'ACC-1094', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Rodel',     'Aguilar',   'Bautista',   '15', '2009-11-16', 'Dasol',      'Pangasinense', 'Villacorta, Dasol, Pangasinan',      '09170000094', 'Marissa Bautista',  '09180000094', 'Be reliable.',        '2024-09-04', 'profile-image.jpg', 'student', 'VILLACORTA ELEMENTARY SCHOOL',                   'Villacorta, Dasol, Pangasinan'),
(95, 'Lion',      'STU-095', 'ACC-1095', '9', 'Ilang-Ilang','Carla Bautista',   'Active', 'Marissa',   'Bautista',  'Del Rosario','16', '2008-06-05', 'Dasol',      'Pangasinense', 'Villacorta, Dasol, Pangasinan',      '09170000095', 'Edgar Del Rosario', '09180000095', 'Be dependable.',      '2024-09-05', 'profile-image.jpg', 'student', 'VILLACORTA ELEMENTARY SCHOOL',                   'Villacorta, Dasol, Pangasinan');

-- ------------------------------------------------------------
-- student_subjects  (anonymous = animal code names)
-- ------------------------------------------------------------
INSERT INTO `student_subjects`
(`id`, `anonymous`, `code_name`, `account_number`, `grade`, `section`, `adviser`, `status`, `first_name`, `middle_name`, `surname`, `subject`, `subject_grade`, `modified_at`)
VALUES
(1, 'Eagle',   'STU-001', 'ACC-1001', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Juan',  'Dela',  'Cruz',    'Mathematics', '88', '2024-06-15 10:00 AM'),
(2, 'Tiger',   'STU-002', 'ACC-1002', '7', 'Sampaguita',  'Maria Santos',     'Active', 'Ana',   'Santos','Reyes',   'Mathematics', '92', '2024-06-15 10:05 AM'),
(3, 'Dolphin', 'STU-003', 'ACC-1003', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Mark',  'Lopez', 'Garcia',  'Science',     '85', '2024-06-15 10:10 AM'),
(4, 'Lion',    'STU-004', 'ACC-1004', '8', 'Rosal',       'Elena Villanueva', 'Active', 'Liza',  'Cruz',  'Mendoza', 'English',     '90', '2024-06-15 10:15 AM'),
(5, 'Wolf',    'STU-005', 'ACC-1005', '9', 'Ilang-Ilang', 'Carla Bautista',   'Active', 'Kevin', 'Reyes', 'Torres',  'Filipino',    '87', '2024-06-15 10:20 AM');

-- ------------------------------------------------------------
-- school_updates
-- ------------------------------------------------------------
INSERT INTO `school_updates`
(`id`, `account_number`, `anouncer`, `subject`, `title`, `desc`, `when`, `where`, `shown_info`, `modified_at`)
VALUES
(1, 'ADM-0001', 'Principal Office', 'Enrollment', 'Enrollment for SY 2024-2025', 'Enrollment opens June 1 to June 30.', '2024-06-01', 'Main Campus',   'Shown',  '2024-05-30 09:00 AM'),
(2, 'ADM-0001', 'Principal Office', 'Holiday',    'Independence Day',            'No classes on June 12.',              '2024-06-12', 'All Campuses',  'Shown',  '2024-06-10 08:30 AM'),
(3, 'ADM-0001', 'Academic Office',  'Exam',       'Midterm Exams',               'Midterm exams on July 15-19.',        '2024-07-15', 'All Classrooms','Shown',  '2024-07-01 10:00 AM'),
(4, 'ADM-0001', 'Student Affairs',  'Activity',   'Intramurals 2024',            'Annual intramurals on August 5-9.',   '2024-08-05', 'School Gym',    'Shown',  '2024-07-25 02:00 PM'),
(5, 'ADM-0001', 'Principal Office', 'Meeting',    'PTA General Assembly',        'PTA meeting on September 10.',        '2024-09-10', 'Auditorium',    'Hidden', '2024-09-01 11:00 AM');

-- ------------------------------------------------------------
-- gawad_parangal
-- ------------------------------------------------------------
INSERT INTO `gawad_parangal`
(`id`, `code_name`, `account_number`, `first_name`, `middle_name`, `surname`, `date_today`, `grade`, `section`, `adviser`, `anouncer`, `event_name`, `types_of_award`, `desc`, `when`, `where`, `shown_info`, `modified_at`)
VALUES
(1, 'STU-001', 'ACC-1001', 'Juan',  'Dela',  'Cruz',    '2024-06-15', '7', 'Sampaguita',  'Maria Santos',     'Principal Office', 'Recognition Day', 'With Honors',      'Achieved 90+ average.',              '2024-06-15', 'Auditorium', 'Shown',  '2024-06-15 09:00 AM'),
(2, 'STU-002', 'ACC-1002', 'Ana',   'Santos','Reyes',   '2024-06-15', '7', 'Sampaguita',  'Maria Santos',     'Principal Office', 'Recognition Day', 'With High Honors', 'Achieved 95+ average.',              '2024-06-15', 'Auditorium', 'Shown',  '2024-06-15 09:05 AM'),
(3, 'STU-003', 'ACC-1003', 'Mark',  'Lopez', 'Garcia',  '2024-06-15', '8', 'Rosal',       'Elena Villanueva', 'Academic Office',  'Quiz Bee',        'Champion',         'Won 1st place in Science Quiz Bee.', '2024-06-15', 'School Gym', 'Shown',  '2024-06-15 09:10 AM'),
(4, 'STU-004', 'ACC-1004', 'Liza',  'Cruz',  'Mendoza', '2024-06-15', '8', 'Rosal',       'Elena Villanueva', 'Academic Office',  'Essay Writing',   '1st Runner-Up',    'Placed 2nd in Essay Writing Contest.','2024-06-15', 'School Gym', 'Shown',  '2024-06-15 09:15 AM'),
(5, 'STU-005', 'ACC-1005', 'Kevin', 'Reyes', 'Torres',  '2024-06-15', '9', 'Ilang-Ilang', 'Carla Bautista',   'Student Affairs',  'Sports Fest',     'MVP',              'Most Valuable Player in Basketball.', '2024-06-15', 'School Gym', 'Hidden', '2024-06-15 09:20 AM');