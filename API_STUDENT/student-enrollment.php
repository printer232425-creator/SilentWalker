<?php
// API_STUDENT/student-enrollment.php — Enrollment backend.
declare(strict_types=1);

require_once __DIR__ . '/../DB_Conn/config.php';

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
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/* ------------------------------------------------------------
   BASE PATH — works at domain root OR in a subfolder
   e.g. /campuslink/API_STUDENT/student-enrollment.php
        → /campuslink
        → redirect to /campuslink/student-enrollment.php
   ------------------------------------------------------------ */
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$appBase    = preg_replace('#/API_STUDENT(/student-enrollment\.php)?$#', '', $scriptName);
if ($appBase === false) $appBase = '';
$appBase = rtrim($appBase, '/');

function redirectHome(string $appBase): void
{
    header('Location: ' . $appBase . '/student-enrollment.php');
    exit;
}

function flash(string $key, string $msg): void
{
    $_SESSION['flash'][$key] = $msg;
}

/* ------------------------------------------------------------
   SECTION OPTIONS — per grade (server-side whitelist)
   ------------------------------------------------------------ */
$SECTION_OPTIONS_BY_GRADE = [
    '7'  => ['Ilang-Ilang', 'Dahlia', 'Rosal', 'Sampaguita'],
    '8'  => ['Acacia', 'Yakal', 'Molave', 'Mahogany'],
    '9'  => ['Mabini', 'Rizal', 'Jacinto', 'Bonifacio'],
    '10' => ['Emerald', 'Diamond', 'Jade'],
    '11' => ['Home Economics - Pasteur', 'Einstein', 'Socrates', 'Franklin'],
    '12' => ['Automotive Servicing', 'TVL - HE', 'STEM', 'Humanities & Social Science'],
];

function currentAcademicYear(): string
{
    $y = (int) date('Y');
    return ((int) date('n') < 6) ? (($y - 1) . '-' . $y) : ($y . '-' . ($y + 1));
}

function nextGradeLevel(string $current): string
{
    $current = trim($current);
    if ($current === '') return '7';
    if (preg_match('/(?:grade\s*|g)?\s*(\d{1,2})/i', $current, $m)) {
        $n = (int) $m[1];
        if ($n >= 1 && $n < 12) $n++;
        elseif ($n >= 12) $n = 12;
        return (string) $n;
    }
    return $current;
}

/* ------------------------------------------------------------
   0. Method guard
   ------------------------------------------------------------ */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    flash('enroll_error', 'Invalid request method.');
    redirectHome($appBase);
}

/* 0b. CSRF */
$clientToken = (string) ($_POST['csrf_token'] ?? '');
if ($clientToken === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $clientToken)) {
    flash('enroll_error', 'Invalid security token. Refresh and try again.');
    redirectHome($appBase);
}

/* 0c. Section presence */
$postedSection = trim((string) ($_POST['section'] ?? ''));
if ($postedSection === '') {
    flash('enroll_error', 'Please select a section.');
    redirectHome($appBase);
}

/* ------------------------------------------------------------
   1. Auth
   ------------------------------------------------------------ */
$accountNumber = $_SESSION['account_number'] ?? null;
if (!is_string($accountNumber) || strlen($accountNumber) < 5) {
    flash('enroll_error', 'Invalid session. Please log in again.');
    redirectHome($appBase);
}

/* ------------------------------------------------------------
   2. Load student
   ------------------------------------------------------------ */
$student = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE account_number = ? AND user_type = 'student' LIMIT 1");
    $stmt->execute([$accountNumber]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (PDOException $e) {
    error_log("enrollment: student fetch failed: " . $e->getMessage());
}

if (!$student) {
    flash('enroll_error', 'Student account not found.');
    redirectHome($appBase);
}

/* ------------------------------------------------------------
   3. Status check
   ------------------------------------------------------------ */
$enrollmentAccess = (int) ($student['enrollment_access'] ?? 0);
if ($enrollmentAccess !== 1) {
    flash('enroll_error', 'Enrollment is currently closed. Please wait for the school to open it.');
    redirectHome($appBase);
}

$currentEnrollmentStatus = strtolower(trim((string) ($student['enrollment_status'] ?? 'not enrolled')));
if (in_array($currentEnrollmentStatus, ['enrolled', 'active'], true)) {
    flash('enroll_error', 'You are already enrolled for this academic year.');
    redirectHome($appBase);
}
if ($currentEnrollmentStatus === 'pending') {
    flash('enroll_error', 'You already submitted an enrollment request. Please wait for approval.');
    redirectHome($appBase);
}

/* ------------------------------------------------------------
   4. Duplicate check (same student + same academic year)
   ------------------------------------------------------------ */
$currentYear = currentAcademicYear();
try {
    $stmt = $pdo->prepare("SELECT status FROM enrolled_students WHERE account_number = ? AND academic_year = ? LIMIT 1");
    $stmt->execute([$accountNumber, $currentYear]);
    $existingStatus = $stmt->fetchColumn();
    if ($existingStatus !== false) {
        flash('enroll_error', 'You already have an enrollment record (' . $existingStatus . ') for ' . $currentYear . '.');
        redirectHome($appBase);
    }
} catch (PDOException $e) {
    error_log("enrollment: duplicate check failed: " . $e->getMessage());
    flash('enroll_error', 'Unable to check enrollment status. Please try again later.');
    redirectHome($appBase);
}

/* ------------------------------------------------------------
   5. Determine grade level
   ------------------------------------------------------------ */
$studentType  = strtoupper(trim((string) ($student['student_type'] ?? '')));
$isOldStudent = ($studentType === 'O');

$gradeLevel = trim((string) ($student['grade_level'] ?? ''));

if ($isOldStudent) {
    try {
        $stmt = $pdo->prepare("
            SELECT grade_level
            FROM enrolled_students
            WHERE account_number = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$accountNumber]);
        $lastGrade = $stmt->fetchColumn();

        if ($lastGrade !== false && trim((string) $lastGrade) !== '') {
            $gradeLevel = nextGradeLevel((string) $lastGrade);
        }
    } catch (PDOException $e) {
        error_log("enrollment: last grade fetch failed: " . $e->getMessage());
    }
}

if ($gradeLevel === '') {
    $gradeLevel = '7';
}

/* ------------------------------------------------------------
   5b. Validate section against the student's grade
   ------------------------------------------------------------ */
$allowedSections = $SECTION_OPTIONS_BY_GRADE[$gradeLevel] ?? [];

if (!in_array($postedSection, $allowedSections, true)) {
    flash(
        'enroll_error',
        'The selected section "' . $postedSection . '" does not belong to Grade ' . $gradeLevel . '.'
    );
    redirectHome($appBase);
}

/* ------------------------------------------------------------
   6. Submit enrollment request (Pending)
   ------------------------------------------------------------ */
$schoolName = trim((string) ($student['school_name'] ?? ''));

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO enrolled_students
            (account_number, academic_year, grade_level, section, school_name, status)
        VALUES
            (:account_number, :academic_year, :grade_level, :section, :school_name, 'Pending')
    ");
    $stmt->execute([
        ':account_number' => $accountNumber,
        ':academic_year'  => $currentYear,
        ':grade_level'    => $gradeLevel,
        ':section'        => $postedSection,
        ':school_name'    => $schoolName,
    ]);

    $stmt = $pdo->prepare("
        UPDATE students
        SET enrollment_status = 'Pending'
        WHERE account_number = ?
    ");
    $stmt->execute([$accountNumber]);

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    if ($e->getCode() === '23000') {
        flash('enroll_error', 'You already have an enrollment request for the ' . $currentYear . ' academic year.');
    } else {
        error_log("enrollment insert failed: " . $e->getMessage());
        flash('enroll_error', 'Enrollment submission failed. Please try again later.');
    }
    redirectHome($appBase);
}

/* ------------------------------------------------------------
   7. Cache subjects (for the pending page)
   ------------------------------------------------------------ */
try {
    $stmt = $pdo->prepare("
        SELECT subject, adviser
        FROM assigned_teachers
        WHERE CAST(grade_level AS CHAR) = ?
          AND section = ?
        ORDER BY subject ASC
    ");
    $stmt->execute([$gradeLevel, $postedSection]);
    $_SESSION['flash']['enrolled_subjects'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log("enrollment: assigned_teachers fetch failed: " . $e->getMessage());
    $_SESSION['flash']['enrolled_subjects'] = [];
}

/* ------------------------------------------------------------
   8. Success
   ------------------------------------------------------------ */
$_SESSION['flash']['just_enrolled'] = true;
flash(
    'enroll_success',
    'Your enrollment request for ' . $currentYear
    . ' (Grade ' . $gradeLevel . ' - ' . $postedSection . ') has been submitted. '
    . 'Status: Pending. Please wait for the school to approve it.'
);
redirectHome($appBase);