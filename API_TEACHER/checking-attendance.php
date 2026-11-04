<?php
// teachers/API_TEACHER/checking-attendance.php
// Endpoint: POST JSON → insert into attendance_list
// Body: { csrf_token, account_number, grade_level, section }

require_once __DIR__ . '/../../DB_Conn/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

/* ------------------------------------------------------------
   ONLY POST
   ------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

/* ------------------------------------------------------------
   READ JSON BODY
   ------------------------------------------------------------ */
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON body.']);
    exit;
}

/* ------------------------------------------------------------
   CSRF CHECK
   ------------------------------------------------------------ */
$postedCsrf = (string) ($input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $postedCsrf)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid security token.']);
    exit;
}

/* ------------------------------------------------------------
   VERIFY LOGGED-IN TEACHER
   ------------------------------------------------------------ */
$isTeacher = false;
$teacherAccount = '';

if (!empty($_SESSION['account_number']) && is_string($_SESSION['account_number'])) {
    $candidate = trim($_SESSION['account_number']);
    if (preg_match('/^26[A-Z]{1,8}_T\d{3,}$/', $candidate)) {
        try {
            $stmt = $pdo->prepare("SELECT account_number FROM teachers WHERE account_number = ? LIMIT 1");
            $stmt->execute([$candidate]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($row) {
                $isTeacher = true;
                $teacherAccount = (string) $row['account_number'];
            }
        } catch (PDOException $e) {
            error_log("checking-attendance teacher lookup failed: " . $e->getMessage());
        }
    }
}

if (!$isTeacher) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated as a teacher.']);
    exit;
}

/* ------------------------------------------------------------
   VALIDATE INPUTS
   ------------------------------------------------------------ */
$accountNumber = trim((string) ($input['account_number'] ?? ''));
$gradeLevel    = trim((string) ($input['grade_level'] ?? ''));
$section       = trim((string) ($input['section'] ?? ''));

$allowedGrades = ['7', '8', '9', '10', '11', '12'];

$allowedSections = [
    'Ilang-Ilang', 'Dahlia', 'Rosal', 'Sampaguita', 'Acacia',
    'Yakal', 'Molave', 'Mahogany', 'Mabini', 'Rizal', 'Jacinto',
    'Bonifacio', 'Emerald', 'Diamond', 'Jade',
    'Home Economics - Pasteur', 'Einstein', 'Socrates', 'Franklin',
    'Automotive Servicing', 'TVL - HE', 'STEM', 'Humanities & Social Science'
];

if ($accountNumber === '') {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Missing student account number.']);
    exit;
}
if (!in_array($gradeLevel, $allowedGrades, true)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Invalid grade level.']);
    exit;
}
if (!in_array($section, $allowedSections, true)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Invalid section.']);
    exit;
}

/* ------------------------------------------------------------
   VERIFY STUDENT IS ENROLLED IN THIS CLASS
   ------------------------------------------------------------ */
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM enrolled_students
        WHERE account_number = ?
          AND CAST(grade_level AS CHAR) = ?
          AND section = ?
    ");
    $stmt->execute([$accountNumber, $gradeLevel, $section]);
    if ((int) $stmt->fetchColumn() === 0) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Student not enrolled in this class.']);
        exit;
    }
} catch (PDOException $e) {
    error_log("checking-attendance enrollment check failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Could not verify enrollment.']);
    exit;
}

/* ------------------------------------------------------------
   PREVENT DUPLICATE
   ------------------------------------------------------------ */
$today = date('Y-m-d');

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM attendance_list
        WHERE account_number = ?
          AND date_today = ?
          AND CAST(grade_level AS CHAR) = ?
          AND section = ?
    ");
    $stmt->execute([$accountNumber, $today, $gradeLevel, $section]);
    if ((int) $stmt->fetchColumn() > 0) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Already marked present today: ' . $accountNumber,
            'data'    => [
                'account_number' => $accountNumber,
                'date_today'     => $today,
                'grade_level'    => $gradeLevel,
                'section'        => $section,
                'adviser'        => $teacherAccount,
                'duplicate'      => true,
            ],
        ]);
        exit;
    }
} catch (PDOException $e) {
    error_log("checking-attendance duplicate check failed: " . $e->getMessage());
}

/* ------------------------------------------------------------
   INSERT
   ------------------------------------------------------------ */
try {
    $stmt = $pdo->prepare("
        INSERT INTO attendance_list
            (account_number, date_today, grade_level, section, adviser)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $accountNumber,
        $today,
        $gradeLevel,
        $section,
        $teacherAccount,
    ]);

    echo json_encode([
        'status'  => 'success',
        'message' => 'Marked present: ' . $accountNumber . ' (' . $gradeLevel . ' · ' . $section . ')',
        'data'    => [
            'id'             => (int) $pdo->lastInsertId(),
            'account_number' => $accountNumber,
            'date_today'     => $today,
            'grade_level'    => $gradeLevel,
            'section'        => $section,
            'adviser'        => $teacherAccount,
            'duplicate'      => false,
        ],
    ]);
} catch (PDOException $e) {
    error_log("checking-attendance insert failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Could not save attendance: ' . $e->getMessage()]);
}