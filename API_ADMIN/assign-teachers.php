<?php
// API/assign-teachers.php — Handles the JS fetch from admin/assign-teachers.php
// Accepts JSON: { teacher_account, assignments: [{ grade_level, section, subjects[], days[], start_time, end_time, advisory }] }
// Admin-only, POST-only, CSRF-protected.
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

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');

function jsonOut(bool $ok, string $msg, int $code = 200, array $extra = []): void
{
    http_response_code($code);
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit;
}

/* ------------------------------------------------------------
   Method guard
   ------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(false, 'Method not allowed.', 405);
}

/* ------------------------------------------------------------
   1. AUTH CHECK — must be Admin
   ------------------------------------------------------------ */
$isAdmin = false;
if (!empty($_SESSION['account_number']) && is_string($_SESSION['account_number'])) {
    $accountNumber = trim($_SESSION['account_number']);

    if (preg_match('/^26[A-Z]{1,8}_A\d{1,7}$/', $accountNumber)) {
        try {
            $stmt = $pdo->prepare("
                SELECT id
                FROM admins
                WHERE account_number = ?
                  AND user_type = 'admin'
                LIMIT 1
            ");
            $stmt->execute([$accountNumber]);
            if ($stmt->fetchColumn()) {
                $isAdmin = true;
            }
        } catch (PDOException $e) {
            error_log("assign-teacher: auth check failed: " . $e->getMessage());
        }
    }
}

if (!$isAdmin) {
    jsonOut(false, 'Unauthorized access. Please log in as an admin.', 401);
}

/* ------------------------------------------------------------
   2. CSRF PROTECTION
   Accepts either:
     - X-CSRF-Token header (JSON fetch — current flow)
     - csrf_token POST field (legacy form fallback)
   ------------------------------------------------------------ */
$sessionToken = $_SESSION['csrf_token'] ?? '';
$clientToken  = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');

if ($sessionToken === '' || !is_string($clientToken) || !hash_equals($sessionToken, $clientToken)) {
    jsonOut(false, 'Invalid security token. Please try again.', 403);
}

/* ------------------------------------------------------------
   3. PARSE BODY — JSON or form-data
   ------------------------------------------------------------ */
$rawBody    = file_get_contents('php://input');
$jsonInput  = json_decode((string) $rawBody, true);
$isJson     = is_array($jsonInput);

$teacherAccount  = '';
$assignmentsRaw  = [];

if ($isJson) {
    $teacherAccount = trim((string) ($jsonInput['teacher_account'] ?? ''));
    $assignmentsRaw = $jsonInput['assignments'] ?? [];
} else {
    // Legacy form-data fallback (old shape with grades[]/sections[]/subjects[])
    $teacherAccount = trim((string) ($_POST['teacher_account'] ?? ''));
    if (isset($_POST['assignments']) && is_array($_POST['assignments'])) {
        $assignmentsRaw = $_POST['assignments'];
    }
}

if (!is_array($assignmentsRaw)) {
    $assignmentsRaw = [];
}

if ($teacherAccount === '' || empty($assignmentsRaw)) {
    jsonOut(false, 'Please select a teacher and at least one assignment row.');
}

/* ------------------------------------------------------------
   Whitelists
   ------------------------------------------------------------ */
$allowedGrades = ['7', '8', '9', '10', '11', '12'];

$sectionOptionsByGrade = [
    '7'  => ['Ilang-Ilang', 'Dahlia', 'Rosal', 'Sampaguita'],
    '8'  => ['Acacia', 'Yakal', 'Molave', 'Mahogany'],
    '9'  => ['Mabini', 'Rizal', 'Jacinto', 'Bonifacio'],
    '10' => ['Emerald', 'Diamond', 'Jade'],
    '11' => ['Home Economics - Pasteur', 'Einstein', 'Socrates', 'Franklin'],
    '12' => ['Automotive Servicing', 'TVL - HE', 'STEM', 'Humanities & Social Science'],
];

$allowedSubjects = [
    'English', 'Filipino', 'Mathematics', 'Science', 'Araling Panlipunan',
    'Edukasyon sa Pagpapakatao (ESP)', 'MAPEH', 'TLE', 'Computer Science',
    'Physical Education', 'Music', 'Arts', 'Health'
];

$allowedDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

/* ------------------------------------------------------------
   4. FETCH TEACHER DETAILS
   ------------------------------------------------------------ */
$teacherSurname = '';
try {
    $stmt = $pdo->prepare("
        SELECT first_name, middle_name, surname
        FROM teachers
        WHERE account_number = ?
        LIMIT 1
    ");
    $stmt->execute([$teacherAccount]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        jsonOut(false, 'Teacher not found.');
    }

    $teacherSurname = trim((string) ($teacher['surname'] ?? ''));
} catch (PDOException $e) {
    error_log("assign-teacher: teacher fetch failed: " . $e->getMessage());
    jsonOut(false, 'Database error. Please try again.', 500, ['debug' => $e->getMessage()]);
}

/* ------------------------------------------------------------
   5. DETECT TABLE
   ------------------------------------------------------------ */
$tableName = null;
try {
    foreach (['assigned_teachers', 'assinged_teachers'] as $candidate) {
        $check = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = ?
        ");
        $check->execute([$candidate]);
        if ((int) $check->fetchColumn() > 0) {
            $tableName = $candidate;
            break;
        }
    }
} catch (PDOException $e) {
    error_log("assign-teacher: table detection failed: " . $e->getMessage());
}

if ($tableName === null) {
    jsonOut(false, 'Assignment table not found. Create `assigned_teachers` first.', 500, [
        'debug' => 'Neither `assigned_teachers` nor `assinged_teachers` exists in this database.',
    ]);
}

/* ------------------------------------------------------------
   6. DETECT COLUMN TYPES + WHICH COLUMNS EXIST
   ------------------------------------------------------------ */
function columnExists(PDO $pdo, string $table, string $col): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = ?
          AND column_name = ?
    ");
    $stmt->execute([$table, $col]);
    return (int) $stmt->fetchColumn() > 0;
}

function columnType(PDO $pdo, string $table, string $col): string
{
    $stmt = $pdo->prepare("
        SELECT DATA_TYPE
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = ?
          AND column_name = ?
        LIMIT 1
    ");
    $stmt->execute([$table, $col]);
    return strtolower((string) ($stmt->fetchColumn() ?: ''));
}

try {
    $hasDays      = columnExists($pdo, $tableName, 'days');
    $hasStartTime = columnExists($pdo, $tableName, 'start_time');
    $hasEndTime   = columnExists($pdo, $tableName, 'end_time');
    $hasAdvisory  = columnExists($pdo, $tableName, 'advisory');

    $adviserType  = columnType($pdo, $tableName, 'adviser');
    $gradeType    = columnType($pdo, $tableName, 'grade_level');
    $advisoryType = $hasAdvisory ? columnType($pdo, $tableName, 'advisory') : '';

    $adviserIsInt  = in_array($adviserType, ['int', 'bigint', 'smallint', 'tinyint', 'mediumint'], true);
    $gradeIsInt    = in_array($gradeType,   ['int', 'bigint', 'smallint', 'tinyint', 'mediumint'], true);
    $advisoryIsInt = in_array($advisoryType, ['int', 'bigint', 'smallint', 'tinyint', 'mediumint'], true);
} catch (PDOException $e) {
    error_log("assign-teacher: column inspection failed: " . $e->getMessage());
    jsonOut(false, 'Database error while inspecting table.', 500, ['debug' => $e->getMessage()]);
}

/* ------------------------------------------------------------
   7. BUILD + RUN INSERTS
   ------------------------------------------------------------ */
try {
    $pdo->beginTransaction();

    // Build column list dynamically based on what exists
    $cols   = ['grade_level', 'section', 'adviser', 'surname', 'subject'];
    $params = [':grade_level', ':section', ':adviser', ':surname', ':subject'];

    if ($hasDays)      { $cols[] = 'days';       $params[] = ':days'; }
    if ($hasStartTime) { $cols[] = 'start_time'; $params[] = ':start_time'; }
    if ($hasEndTime)   { $cols[] = 'end_time';   $params[] = ':end_time'; }
    if ($hasAdvisory)  { $cols[] = 'advisory';   $params[] = ':advisory'; }

    $sql = "
        INSERT IGNORE INTO `{$tableName}` (" . implode(', ', $cols) . ")
        VALUES (" . implode(', ', $params) . ")
    ";
    $insertStmt = $pdo->prepare($sql);

    $inserted = 0;
    $skipped  = 0;

    foreach ($assignmentsRaw as $rowIndex => $row) {
        if (!is_array($row)) {
            jsonOut(false, "Invalid assignment row #" . ($rowIndex + 1) . '.');
        }

        $grade      = trim((string) ($row['grade_level'] ?? ''));
        $section    = trim((string) ($row['section'] ?? ''));
        $subjectsIn = $row['subjects'] ?? [];
        $daysIn     = $row['days'] ?? [];
        $startTime  = trim((string) ($row['start_time'] ?? ''));
        $endTime    = trim((string) ($row['end_time'] ?? ''));
        $advisory   = !empty($row['advisory']) ? 1 : 0;

        if (!is_array($subjectsIn)) $subjectsIn = [];
        if (!is_array($daysIn))     $daysIn = [];

        // Validate grade
        if (!in_array($grade, $allowedGrades, true)) {
            jsonOut(false, "Row " . ($rowIndex + 1) . ": invalid grade level.");
        }

        // Validate section belongs to grade
        $validSections = $sectionOptionsByGrade[$grade] ?? [];
        if (!in_array($section, $validSections, true)) {
            jsonOut(false, "Row " . ($rowIndex + 1) . ": section '{$section}' does not belong to Grade {$grade}.");
        }

        // Validate subjects
        $subjects = [];
        foreach ($subjectsIn as $s) {
            $s = trim((string) $s);
            if ($s !== '' && in_array($s, $allowedSubjects, true)) {
                $subjects[] = $s;
            }
        }
        $subjects = array_values(array_unique($subjects));
        if (empty($subjects)) {
            jsonOut(false, "Row " . ($rowIndex + 1) . ": select at least one valid subject.");
        }

        // Validate days
        $days = [];
        foreach ($daysIn as $d) {
            $d = trim((string) $d);
            if ($d !== '' && in_array($d, $allowedDays, true)) {
                $days[] = $d;
            }
        }
        $days = array_values(array_unique($days));
        if ($hasDays && empty($days)) {
            jsonOut(false, "Row " . ($rowIndex + 1) . ": select at least one day in the schedule.");
        }

        // Validate times
        $timePattern = '/^([01]\d|2[0-3]):[0-5]\d$/';
        if ($hasStartTime && !preg_match($timePattern, $startTime)) {
            jsonOut(false, "Row " . ($rowIndex + 1) . ": invalid start time.");
        }
        if ($hasEndTime && !preg_match($timePattern, $endTime)) {
            jsonOut(false, "Row " . ($rowIndex + 1) . ": invalid end time.");
        }
        if ($hasStartTime && $hasEndTime && $startTime !== '' && $endTime !== '' && $endTime <= $startTime) {
            jsonOut(false, "Row " . ($rowIndex + 1) . ": end time must be after start time.");
        }

        $daysStr = implode(',', $days);

        // Insert one row per subject
        foreach ($subjects as $subject) {
            $bindings = [
                ':grade_level' => $gradeIsInt ? (int) $grade : $grade,
                ':section'     => $section,
                ':adviser'     => $adviserIsInt ? (int) $teacherAccount : $teacherAccount,
                ':surname'     => $teacherSurname,
                ':subject'     => $subject,
            ];

            if ($hasDays)      $bindings[':days']       = $daysStr;
            if ($hasStartTime) $bindings[':start_time'] = $startTime;
            if ($hasEndTime)   $bindings[':end_time']   = $endTime;
            if ($hasAdvisory)  $bindings[':advisory']   = $advisoryIsInt ? (int) $advisory : (string) $advisory;

            try {
                $insertStmt->execute($bindings);
                if ($insertStmt->rowCount() > 0) {
                    $inserted++;
                } else {
                    $skipped++;
                }
            } catch (PDOException $rowEx) {
                throw new PDOException(
                    $rowEx->getMessage() . " | grade={$grade} section={$section} subject={$subject}",
                    (int) $rowEx->getCode()
                );
            }
        }
    }

    $pdo->commit();

    $msg = "Assignment(s) saved: {$inserted} inserted";
    if ($skipped > 0) {
        $msg .= ", {$skipped} already existed (skipped)";
    }
    $msg .= '.';

    jsonOut(true, $msg, 200, [
        'inserted' => $inserted,
        'skipped'  => $skipped,
        'table'    => $tableName,
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("assign-teacher: insert failed: " . $e->getMessage());
    jsonOut(false, 'Failed to save assignment. Database error.', 500, [
        'debug'    => $e->getMessage(),
        'sqlstate' => $e->getCode(),
        'table'    => $tableName,
    ]);
}
exit;