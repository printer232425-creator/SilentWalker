<?php
// API_ADMIN/enroll-students.php
// Handles: (1) enrolling a New Student / Transferee, (2) approving a student
// with a captured camera photo + LRN.
// POST-only. CSRF required. Admin session required.
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/../DB_Conn/config.php';

/* Method guard */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

/* CSRF */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$sessionToken = $_SESSION['csrf_token'];
$clientToken  = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!is_string($clientToken) || !hash_equals($sessionToken, $clientToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Refresh and try again.']);
    exit;
}

/* Auth — admin */
$adminUser = null;

if (!empty($_SESSION['account_number']) && is_string($_SESSION['account_number'])) {
    $accountNumberSession = trim($_SESSION['account_number']);

    if (preg_match('/^26[A-Z]{1,8}_A\d{1,7}$/', $accountNumberSession)) {
        try {
            $stmt = $pdo->prepare("
                SELECT id, account_number, first_name, surname, school_name
                FROM admins
                WHERE account_number = ?
                  AND user_type = 'admin'
                LIMIT 1
            ");
            $stmt->execute([$accountNumberSession]);
            $adminUser = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("enroll-students: admin lookup failed: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error. Try again later.']);
            exit;
        }
    }
}

if (!$adminUser) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

/* Flash helper */
function setFlash(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['flash']['admin_' . $type] = $message;
}

/* ------------------------------------------------------------
   Whitelists / helpers
   ------------------------------------------------------------ */
$SECTION_OPTIONS = [
    'Ilang-Ilang', 'Dahlia', 'Rosal', 'Sampaguita',
    'Acacia', 'Yakal', 'Molave', 'Mahogany',
    'Mabini', 'Rizal', 'Jacinto', 'Bonifacio',
    'Emerald', 'Diamond', 'Jade',
    'Home Economics - Pasteur', 'Einstein', 'Socrates', 'Franklin',
    'Automotive Servicing', 'TVL - HE', 'STEM', 'Humanities & Social Science'
];

function currentAcademicYear(): string
{
    $y = (int) date('Y');
    return ((int) date('n') < 6) ? (($y - 1) . '-' . $y) : ($y . '-' . ($y + 1));
}

function columnExists(PDO $pdo, string $table, string $col): bool
{
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = ?
              AND column_name = ?
        ");
        $stmt->execute([$table, $col]);
        return (int) $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("columnExists failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Decode a data URL (data:image/jpeg;base64,...) into raw JPEG bytes.
 * Returns null on any failure.
 */
function decodeJpegDataUrl(string $dataUrl): ?string
{
    if (!preg_match('#^data:image/jpe?g;base64,#i', $dataUrl)) {
        return null;
    }
    $b64 = preg_replace('#^data:image/jpe?g;base64,#i', '', $dataUrl);
    $b64 = preg_replace('/\s+/', '', $b64);
    $bin = base64_decode($b64, true);
    if ($bin === false || strlen($bin) < 100) {
        return null;
    }
    if (substr($bin, 0, 3) !== "\xFF\xD8\xFF") {
        return null;
    }
    return $bin;
}

/**
 * Save a JPEG to Students_Profile/{account}.jpg
 * CHANGED: returns ONLY the filename ({account}.jpg) — no folder prefix.
 * Returns null on failure.
 */
function saveStudentProfilePhoto(string $account, string $jpegBytes): ?string
{
    $account = preg_replace('/[^A-Za-z0-9_\-]/', '_', $account);
    if ($account === '') {
        return null;
    }

    $absDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Students_Profile/';

    if (!is_dir($absDir)) {
        if (!@mkdir($absDir, 0775, true) && !is_dir($absDir)) {
            error_log("enroll-students: failed to create Students_Profile at {$absDir}");
            return null;
        }
    }

    if (!is_writable($absDir)) {
        error_log("enroll-students: Students_Profile is not writable: {$absDir}");
        return null;
    }

    $fileName = $account . '.jpg';
    $absFile  = $absDir . DIRECTORY_SEPARATOR . $fileName;

    if (@file_put_contents($absFile, $jpegBytes, LOCK_EX) === false) {
        error_log("enroll-students: failed to write {$absFile}");
        return null;
    }

    /* CHANGED: return only the filename, not "Students_Profile/..." */
    return $fileName;
}

/**
 * Resolve class adviser full name from assigned_teachers + teachers.
 */
function resolveClassAdviser(PDO $pdo, int $gradeLevel, string $section): ?string
{
    try {
        $atStmt = $pdo->prepare("
            SELECT account_number
            FROM assigned_teachers
            WHERE grade_level = ?
              AND section = ?
              AND advisory = 1
            LIMIT 1
        ");
        $atStmt->execute([(string) $gradeLevel, $section]);
        $adviserAccountNumber = $atStmt->fetchColumn();

        if (!$adviserAccountNumber) {
            return null;
        }

        $tStmt = $pdo->prepare("
            SELECT first_name, middle_name, surname
            FROM teachers
            WHERE account_number = ?
            LIMIT 1
        ");
        $tStmt->execute([$adviserAccountNumber]);
        $teacher = $tStmt->fetch(PDO::FETCH_ASSOC);

        if (!$teacher) {
            return null;
        }

        $name = trim(
            ($teacher['first_name'] ?? '') . ' ' .
            ($teacher['middle_name'] ?? '') . ' ' .
            ($teacher['surname'] ?? '')
        );
        $name = preg_replace('/\s+/', ' ', $name) ?: null;
        return $name;
    } catch (PDOException $e) {
        error_log("resolveClassAdviser failed: " . $e->getMessage());
        return null;
    }
}

/* ------------------------------------------------------------
   Route by action
   ------------------------------------------------------------ */
$action = trim((string) ($_POST['action'] ?? ''));
$allowedActions = ['enroll_student', 'approve_with_capture'];

if ($action === '' || !in_array($action, $allowedActions, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

/* ============================================================
   ACTION: approve_with_capture
   ------------------------------------------------------------
   Fields:
     - account_number  (required)
     - lrn             (required, exactly 12 digits)
     - grade_level     (optional)
     - section         (optional)
     - photo_data      (required; data:image/jpeg;base64,...)
   ============================================================ */
if ($action === 'approve_with_capture') {
    $accountNumber = trim((string) ($_POST['account_number'] ?? ''));
    $lrnInput      = trim((string) ($_POST['lrn'] ?? ''));
    $gradeLevelRaw = trim((string) ($_POST['grade_level'] ?? ''));
    $sectionRaw    = trim((string) ($_POST['section'] ?? ''));
    $photoData     = (string) ($_POST['photo_data'] ?? '');

    if ($accountNumber === '') {
        setFlash('error', 'Missing student account number.');
        header('Location: ../admin/dashboard.php');
        exit;
    }

    if ($lrnInput === '' || !preg_match('/^\d{12}$/', $lrnInput)) {
        setFlash('error', 'LRN must be exactly 12 digits (numbers only).');
        header('Location: ../admin/dashboard.php');
        exit;
    }

    $jpegBytes = decodeJpegDataUrl($photoData);
    if ($jpegBytes === null) {
        setFlash('error', 'Invalid or missing photo. Please capture again.');
        header('Location: ../admin/dashboard.php');
        exit;
    }

    /* Persist the photo to disk — $profilePath is now just "{account}.jpg" */
    $profilePath = saveStudentProfilePhoto($accountNumber, $jpegBytes);
    if ($profilePath === null) {
        setFlash('error', 'Could not save the student photo. Check folder permissions.');
        header('Location: ../admin/dashboard.php');
        exit;
    }

    $academicYear     = currentAcademicYear();
    $enrollSchoolName = trim((string) ($adminUser['school_name'] ?? ''));
    if ($enrollSchoolName === '') {
        $enrollSchoolName = 'Eguia National High School';
    }

    /* Detect optional columns */
    $hasEnrollmentAccessCol = columnExists($pdo, 'students', 'enrollment_access');
    $hasProfileCol          = columnExists($pdo, 'students', 'profile_account');
    $hasLrnCol              = columnExists($pdo, 'students', 'lrn');
    $hasStudentLrnCol       = columnExists($pdo, 'students', 'student_lrn');
    $hasClassAdviserCol     = columnExists($pdo, 'enrolled_students', 'class_adviser');

    /* Determine grade + section */
    $gradeLevel = null;
    $section    = null;

    if ($gradeLevelRaw !== '' && preg_match('/^(?:[7-9]|1[0-2])$/', $gradeLevelRaw)) {
        $gradeLevel = (int) $gradeLevelRaw;
    }
    if ($sectionRaw !== '' && in_array($sectionRaw, $SECTION_OPTIONS, true)) {
        $section = $sectionRaw;
    }

    try {
        $pdo->beginTransaction();

        /* Lock student row */
        $stmt = $pdo->prepare("
            SELECT account_number, grade_level, enrollment_status
            FROM students
            WHERE account_number = ?
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute([$accountNumber]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            $pdo->rollBack();
            setFlash('error', 'Student not found.');
            header('Location: ../admin/dashboard.php');
            exit;
        }

        /* Fetch pending enrolled_students row (if any) */
        $pendStmt = $pdo->prepare("
            SELECT id, grade_level, section, status
            FROM enrolled_students
            WHERE account_number = ?
              AND academic_year = ?
            ORDER BY id DESC
            LIMIT 1
            FOR UPDATE
        ");
        $pendStmt->execute([$accountNumber, $academicYear]);
        $pendingRow = $pendStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($gradeLevel === null) {
            if ($pendingRow && $pendingRow['grade_level'] !== null && $pendingRow['grade_level'] !== '') {
                $gradeLevel = (int) $pendingRow['grade_level'];
            } elseif (!empty($student['grade_level'])) {
                $gradeLevel = (int) $student['grade_level'];
            }
        }
        if ($section === null && $pendingRow && !empty($pendingRow['section'])) {
            $section = (string) $pendingRow['section'];
        }

        if ($gradeLevel === null || !preg_match('/^(?:[7-9]|1[0-2])$/', (string) $gradeLevel)) {
            $pdo->rollBack();
            setFlash('error', 'Missing grade level for this student.');
            header('Location: ../admin/dashboard.php');
            exit;
        }
        if ($section === null || !in_array($section, $SECTION_OPTIONS, true)) {
            $pdo->rollBack();
            setFlash('error', 'Missing or invalid section for this student.');
            header('Location: ../admin/dashboard.php');
            exit;
        }

        /* Resolve adviser */
        $classAdviser = $hasClassAdviserCol
            ? resolveClassAdviser($pdo, $gradeLevel, $section)
            : null;

        /* ---------- UPDATE students ---------- */
        $setParts = [
            "enrollment_status = 'Enrolled'",
            "student_type = '1'",
        ];
        $params = [];

        if ($hasEnrollmentAccessCol) {
            $setParts[] = "enrollment_access = 0";
        }
        if ($hasProfileCol) {
            $setParts[] = "profile_account = ?";
            $params[]   = $profilePath;      // now just "{account}.jpg"
        }
        if ($hasLrnCol) {
            $setParts[] = "lrn = ?";
            $params[]   = $lrnInput;
        }
        if ($hasStudentLrnCol) {
            $setParts[] = "student_lrn = ?";
            $params[]   = $lrnInput;
        }

        $params[] = $accountNumber;

        $sql = "UPDATE students SET " . implode(', ', $setParts) . " WHERE account_number = ?";
        $upd = $pdo->prepare($sql);
        $upd->execute($params);

        /* ---------- INSERT or UPDATE enrolled_students ---------- */
        if ($pendingRow) {
            if ($hasClassAdviserCol) {
                $upd = $pdo->prepare("
                    UPDATE enrolled_students
                    SET grade_level = ?, section = ?, status = 'Active',
                        school_name = ?, class_adviser = ?
                    WHERE id = ?
                ");
                $upd->execute([
                    $gradeLevel,
                    $section,
                    $enrollSchoolName,
                    $classAdviser,
                    (int) $pendingRow['id'],
                ]);
            } else {
                $upd = $pdo->prepare("
                    UPDATE enrolled_students
                    SET grade_level = ?, section = ?, status = 'Active', school_name = ?
                    WHERE id = ?
                ");
                $upd->execute([
                    $gradeLevel,
                    $section,
                    $enrollSchoolName,
                    (int) $pendingRow['id'],
                ]);
            }
        } else {
            if ($hasClassAdviserCol) {
                $ins = $pdo->prepare("
                    INSERT INTO enrolled_students
                        (account_number, grade_level, section, academic_year, status, school_name, class_adviser)
                    VALUES
                        (?, ?, ?, ?, 'Active', ?, ?)
                ");
                $ins->execute([
                    $accountNumber,
                    $gradeLevel,
                    $section,
                    $academicYear,
                    $enrollSchoolName,
                    $classAdviser,
                ]);
            } else {
                $ins = $pdo->prepare("
                    INSERT INTO enrolled_students
                        (account_number, grade_level, section, academic_year, status, school_name)
                    VALUES
                        (?, ?, ?, ?, 'Active', ?)
                ");
                $ins->execute([
                    $accountNumber,
                    $gradeLevel,
                    $section,
                    $academicYear,
                    $enrollSchoolName,
                ]);
            }
        }

        $pdo->commit();

        setFlash(
            'success',
            "Student {$accountNumber} approved and enrolled in Grade {$gradeLevel} - {$section} for S.Y. {$academicYear}."
        );
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("enroll-students: approve_with_capture failed: " . $e->getMessage());
        setFlash('error', 'Could not approve student. Please try again.');
    }

    header('Location: ../admin/dashboard.php');
    exit;
}

/* ============================================================
   ACTION: enroll_student (legacy path — unchanged)
   ============================================================ */
$accountNumber = trim((string) ($_POST['account_number'] ?? ''));
$gradeLevelRaw = trim((string) ($_POST['grade_level'] ?? ''));
$sectionRaw    = trim((string) ($_POST['section'] ?? ''));

if ($accountNumber === '') {
    setFlash('error', 'Missing student account number.');
    header('Location: ../admin/dashboard.php');
    exit;
}

if (!preg_match('/^(?:[7-9]|1[0-2])$/', $gradeLevelRaw)) {
    setFlash('error', 'Please select a grade level between 7 and 12.');
    header('Location: ../admin/dashboard.php');
    exit;
}

if (!in_array($sectionRaw, $SECTION_OPTIONS, true)) {
    setFlash('error', 'Please select a valid section.');
    header('Location: ../admin/dashboard.php');
    exit;
}

$gradeLevel   = (int) $gradeLevelRaw;
$academicYear = currentAcademicYear();

$enrollSchoolName = trim((string) ($adminUser['school_name'] ?? ''));
if ($enrollSchoolName === '') {
    $enrollSchoolName = 'Eguia National High School';
}

$studentTypeCol = columnExists($pdo, 'students', 'student_type')
    ? 'student_type'
    : (columnExists($pdo, 'students', 'student_status') ? 'student_status' : null);

if ($studentTypeCol === null) {
    setFlash('error', 'Student type column is missing in the students table.');
    header('Location: ../admin/dashboard.php');
    exit;
}

$hasEnrollmentAccessCol = columnExists($pdo, 'students', 'enrollment_access');
$hasClassAdviserCol     = columnExists($pdo, 'enrolled_students', 'class_adviser');

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT account_number, {$studentTypeCol} AS student_type, enrollment_status
        FROM students
        WHERE account_number = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([$accountNumber]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        $pdo->rollBack();
        setFlash('error', 'Student not found.');
        header('Location: ../admin/dashboard.php');
        exit;
    }

    if (!in_array($student['student_type'], ['N', 'T'], true)) {
        $pdo->rollBack();
        setFlash('error', 'Only New Students and Transferees can be enrolled from this action.');
        header('Location: ../admin/dashboard.php');
        exit;
    }

    if (strtolower((string) ($student['enrollment_status'] ?? '')) === 'enrolled') {
        $pdo->rollBack();
        setFlash('error', 'Student is already enrolled.');
        header('Location: ../admin/dashboard.php');
        exit;
    }

    $check = $pdo->prepare("
        SELECT 1 FROM enrolled_students
        WHERE account_number = ?
          AND academic_year = ?
        LIMIT 1
    ");
    $check->execute([$accountNumber, $academicYear]);
    $rowExists = (bool) $check->fetchColumn();

    $classAdviser = $hasClassAdviserCol
        ? resolveClassAdviser($pdo, $gradeLevel, $sectionRaw)
        : null;

    if ($rowExists) {
        if ($hasClassAdviserCol) {
            $upd = $pdo->prepare("
                UPDATE enrolled_students
                SET grade_level = ?, section = ?, status = 'Enrolled', school_name = ?, class_adviser = ?
                WHERE account_number = ? AND academic_year = ?
            ");
            $upd->execute([
                $gradeLevel,
                $sectionRaw,
                $enrollSchoolName,
                $classAdviser,
                $accountNumber,
                $academicYear,
            ]);
        } else {
            $upd = $pdo->prepare("
                UPDATE enrolled_students
                SET grade_level = ?, section = ?, status = 'Enrolled', school_name = ?
                WHERE account_number = ? AND academic_year = ?
            ");
            $upd->execute([
                $gradeLevel,
                $sectionRaw,
                $enrollSchoolName,
                $accountNumber,
                $academicYear,
            ]);
        }
    } else {
        if ($hasClassAdviserCol) {
            $ins = $pdo->prepare("
                INSERT INTO enrolled_students
                    (account_number, grade_level, section, academic_year, status, school_name, class_adviser)
                VALUES
                    (?, ?, ?, ?, 'Active', ?, ?)
            ");
            $ins->execute([
                $accountNumber,
                $gradeLevel,
                $sectionRaw,
                $academicYear,
                $enrollSchoolName,
                $classAdviser,
            ]);
        } else {
            $ins = $pdo->prepare("
                INSERT INTO enrolled_students
                    (account_number, grade_level, section, academic_year, status, school_name)
                VALUES
                    (?, ?, ?, ?, 'Active', ?)
            ");
            $ins->execute([
                $accountNumber,
                $gradeLevel,
                $sectionRaw,
                $academicYear,
                $enrollSchoolName,
            ]);
        }
    }

    if ($hasEnrollmentAccessCol) {
        $upd = $pdo->prepare("
            UPDATE students
            SET enrollment_status = 'Enrolled', student_type = 'O',
                enrollment_access = 1
            WHERE account_number = ?
        ");
        $upd->execute([$accountNumber]);
    } else {
        $upd = $pdo->prepare("
            UPDATE students
            SET enrollment_status = 'Enrolled', student_type = 'O'
            WHERE account_number = ?
        ");
        $upd->execute([$accountNumber]);
    }

    $pdo->commit();

    setFlash(
        'success',
        "Student {$accountNumber} enrolled in Grade {$gradeLevel} - {$sectionRaw} for S.Y. {$academicYear}."
    );
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("enroll-students: enroll_student failed: " . $e->getMessage());
    setFlash('error', 'Could not enroll student. Please try again.');
}

header('Location: ../admin/dashboard.php');
exit;