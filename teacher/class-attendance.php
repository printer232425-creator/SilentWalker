<?php
// teachers/class-attendance.php — Eguia National High School Pages
require_once __DIR__ . '/../DB_Conn/config.php';

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
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/* ------------------------------------------------------------
   TIME FORMAT HELPERS
   ------------------------------------------------------------ */
function formatTime12h(string $time24): string
{
    $time24 = trim($time24);
    if ($time24 === '') {
        return '';
    }
    if (!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $time24, $m)) {
        return $time24;
    }
    $hours = (int) $m[1];
    $minutes = (int) $m[2];
    if ($hours > 23 || $minutes > 59) {
        return $time24;
    }
    $suffix = ($hours >= 12) ? 'PM' : 'AM';
    $hours12 = $hours % 12;
    if ($hours12 === 0) {
        $hours12 = 12;
    }
    return $hours12 . ':' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) . ' ' . $suffix;
}

function formatTimeRange12h(string $start, string $end): string
{
    $s = formatTime12h($start);
    $e = formatTime12h($end);
    if ($s !== '' && $e !== '') {
        return $s . ' – ' . $e;
    }
    if ($s !== '') {
        return $s;
    }
    if ($e !== '') {
        return $e;
    }
    return '';
}

/* ------------------------------------------------------------
   ALLOWED ATTENDANCE STATUSES
   ------------------------------------------------------------ */
function allowedStatuses(): array
{
    return ['On Time', 'Late', 'Absent', 'Excused'];
}

function normalizeStatus(string $status): string
{
    $s = strtolower(trim($status));
    $map = [
        'on time' => 'On Time',
        'ontime'  => 'On Time',
        'present' => 'On Time',
        'late'    => 'Late',
        'tardy'   => 'Late',
        'absent'  => 'Absent',
        'excused' => 'Excused',
        'excuse'  => 'Excused',
    ];
    return $map[$s] ?? '';
}

function statusKeyFor(string $status): string
{
    $s = strtolower(trim($status));
    return match ($s) {
        'on time', 'ontime', 'present' => 'present',
        'late', 'tardy' => 'late',
        'absent' => 'absent',
        'excused', 'excuse' => 'excused',
        default => 'default',
    };
}

function statusLabelFor(string $status): string
{
    $normalized = normalizeStatus($status);
    return $normalized !== '' ? $normalized : '—';
}

function statusIconFor(string $status): string
{
    return match (statusKeyFor($status)) {
        'present' => 'fa-circle-check',
        'late'    => 'fa-clock',
        'absent'  => 'fa-circle-xmark',
        'excused' => 'fa-shield-halved',
        default   => 'fa-question',
    };
}

/* ------------------------------------------------------------
   FLASH MESSAGES (only error kept)
   ------------------------------------------------------------ */
$flashError = $_SESSION['flash']['attendance_error'] ?? null;

unset(
    $_SESSION['flash']['attendance_error'],
    $_SESSION['flash']['attendance_success']
);

/* ------------------------------------------------------------
   FETCH LOGGED-IN TEACHER
   ------------------------------------------------------------ */
$isLoggedIn = false;
$user = null;

if (!empty($_SESSION['account_number']) && is_string($_SESSION['account_number'])) {
    $accountNumber = trim($_SESSION['account_number']);
    $formatOk = (bool) preg_match('/^26[A-Z]{1,8}_T\d{3,}$/', $accountNumber);

    if ($formatOk) {
        try {
            $stmt = $pdo->prepare("
                SELECT *
                FROM teachers
                WHERE account_number = ?
                LIMIT 1
            ");
            $stmt->execute([$accountNumber]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("teacher fetch failed: " . $e->getMessage());
            $user = null;
        }
    }

    if ($user) {
        $isLoggedIn = true;
    } else {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $p['path'],
                $p['domain'],
                $p['secure'],
                $p['httponly']
            );
        }
        session_destroy();
        header('Location: ../index.php');
        exit;
    }
}

if (!$isLoggedIn || !$user) {
    header('Location: ../index.php');
    exit;
}

$displayName = trim(
    ($user['first_name'] ?? '') . ' ' .
    ($user['middle_name'] ?? '') . ' ' .
    ($user['surname'] ?? '')
);

$teacherSchoolName = trim((string) ($user['school_name'] ?? ''));
if ($teacherSchoolName === '') {
    $teacherSchoolName = 'Eguia National High School';
}

/* ------------------------------------------------------------
   READ + VALIDATE grade_level / section FROM QUERY STRING
   ------------------------------------------------------------ */
$allowedGrades = ['7', '8', '9', '10', '11', '12'];

$allowedSections = [
    'Ilang-Ilang', 'Dahlia', 'Rosal', 'Sampaguita', 'Acacia',
    'Yakal', 'Molave', 'Mahogany', 'Mabini', 'Rizal', 'Jacinto',
    'Bonifacio', 'Emerald', 'Diamond', 'Jade',
    'Home Economics - Pasteur', 'Einstein', 'Socrates', 'Franklin',
    'Automotive Servicing', 'TVL - HE', 'STEM', 'Humanities & Social Science'
];

$gradeLevel = trim((string) ($_GET['grade_level'] ?? ''));
$section = trim((string) ($_GET['section'] ?? ''));

$invalidParams =
    !in_array($gradeLevel, $allowedGrades, true)
    || !in_array($section, $allowedSections, true);

if ($invalidParams) {
    header('Location: advisory-class.php');
    exit;
}

/* ------------------------------------------------------------
   CURRENT ACADEMIC YEAR
   ------------------------------------------------------------ */
function currentAcademicYear(): string
{
    $y = (int) date('Y');
    return ((int) date('n') < 6) ? (($y - 1) . '-' . $y) : ($y . '-' . ($y + 1));
}
$currentYear = currentAcademicYear();

/* ------------------------------------------------------------
   LOOK UP THE SUBJECT + SCHEDULE THIS TEACHER HAS FOR THIS CLASS
   ------------------------------------------------------------ */
$classSubject = '';
$classStartTime = '';
$classEndTime = '';

try {
    $subjectStmt = $pdo->prepare("
        SELECT subject, start_time, end_time
        FROM assigned_teachers
        WHERE adviser = ?
          AND CAST(grade_level AS CHAR) = ?
          AND section = ?
          AND subject IS NOT NULL
          AND subject <> ''
        ORDER BY advisory ASC, subject ASC
        LIMIT 1
    ");
    $subjectStmt->execute([$user['account_number'], $gradeLevel, $section]);
    $subjectRow = $subjectStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($subjectRow) {
        $classSubject   = trim((string) ($subjectRow['subject'] ?? ''));
        $classStartTime = trim((string) ($subjectRow['start_time'] ?? ''));
        $classEndTime   = trim((string) ($subjectRow['end_time'] ?? ''));
    }
} catch (PDOException $e) {
    error_log("class-attendance subject lookup failed: " . $e->getMessage());
}

$classTimeDisplay = formatTimeRange12h($classStartTime, $classEndTime);

/* ------------------------------------------------------------
   HANDLE "MARK ATTENDANCE" POST — verify + insert into attendance_list
   Accepts On Time / Late / Absent / Excused.
   ------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $postedCsrf = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $postedCsrf)) {
        $_SESSION['flash']['attendance_error'] = 'Invalid security token. Please try again.';
        header('Location: class-attendance.php?grade_level=' . urlencode($gradeLevel) . '&section=' . urlencode($section));
        exit;
    }

    $studentAccount = trim((string) ($_POST['account_number'] ?? ''));
    $postedStatus = normalizeStatus((string) ($_POST['status'] ?? ''));

    if ($studentAccount === '') {
        $_SESSION['flash']['attendance_error'] = 'Missing student account number.';
        header('Location: class-attendance.php?grade_level=' . urlencode($gradeLevel) . '&section=' . urlencode($section));
        exit;
    }

    if ($postedStatus === '') {
        $_SESSION['flash']['attendance_error'] = 'Please select a valid attendance status.';
        header('Location: class-attendance.php?grade_level=' . urlencode($gradeLevel) . '&section=' . urlencode($section));
        exit;
    }

    /* VERIFY 1: student exists in `students` table */
    $studentExists = false;
    try {
        $chk = $pdo->prepare("
            SELECT COUNT(*)
            FROM students
            WHERE account_number = ?
              AND user_type = 'student'
        ");
        $chk->execute([$studentAccount]);
        $studentExists = ((int) $chk->fetchColumn()) > 0;
    } catch (PDOException $e) {
        error_log("student existence check failed: " . $e->getMessage());
    }

    if (!$studentExists) {
        $_SESSION['flash']['attendance_error'] =
            'Student account ' . $studentAccount . ' was not found in the student database.';
        header('Location: class-attendance.php?grade_level=' . urlencode($gradeLevel) . '&section=' . urlencode($section));
        exit;
    }

    /* VERIFY 2: student is enrolled in this grade + section + year */
    $isEnrolled = false;
    try {
        $chk = $pdo->prepare("
            SELECT COUNT(*)
            FROM enrolled_students
            WHERE account_number = ?
              AND CAST(grade_level AS CHAR) = ?
              AND section = ?
              AND academic_year = ?
        ");
        $chk->execute([$studentAccount, $gradeLevel, $section, $currentYear]);
        $isEnrolled = ((int) $chk->fetchColumn()) > 0;
    } catch (PDOException $e) {
        error_log("enrollment check failed: " . $e->getMessage());
    }

    if (!$isEnrolled) {
        $_SESSION['flash']['attendance_error'] =
            'Student ' . $studentAccount . ' is not enrolled in Grade ' . $gradeLevel .
            ' - ' . $section . ' for S.Y. ' . $currentYear . '.';
        header('Location: class-attendance.php?grade_level=' . urlencode($gradeLevel) . '&section=' . urlencode($section));
        exit;
    }

    /* VERIFY 3: teacher owns this class's subject */
    if ($classSubject === '') {
        $_SESSION['flash']['attendance_error'] =
            'No subject is assigned to you for this class. Attendance cannot be recorded.';
        header('Location: class-attendance.php?grade_level=' . urlencode($gradeLevel) . '&section=' . urlencode($section));
        exit;
    }

    /* ALL VERIFIED — insert OR update (dedupe by student+date+class+subject) */
    $today = date('Y-m-d');
    $adviserAccount = trim((string) ($user['account_number'] ?? ''));

    try {
        // Detect time columns
        $hasStart = false;
        $hasEnd = false;
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM attendance_list")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $c) {
                $name = strtolower((string) ($c['Field'] ?? ''));
                if ($name === 'time_start') $hasStart = true;
                if ($name === 'time_end')   $hasEnd = true;
            }
        } catch (PDOException $e) {
            error_log("attendance_list columns lookup failed: " . $e->getMessage());
        }

        // Check for existing row today
        $existingId = null;
        try {
            $dupStmt = $pdo->prepare("
                SELECT id FROM attendance_list
                WHERE account_number = ?
                  AND date_today = ?
                  AND CAST(grade_level AS CHAR) = ?
                  AND section = ?
                  AND subject = ?
                LIMIT 1
            ");
            $dupStmt->execute([$studentAccount, $today, $gradeLevel, $section, $classSubject]);
            $existingId = $dupStmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("attendance dedupe lookup failed: " . $e->getMessage());
        }

        if ($existingId !== false && $existingId !== null) {
            // Update the existing row with the new status
            $updSql = "UPDATE attendance_list SET status = ?, adviser = ?";
            $updParams = [$postedStatus, $adviserAccount];

            if ($hasStart) {
                $updSql .= ", time_start = ?";
                $updParams[] = $classStartTime !== '' ? $classStartTime : null;
            }
            if ($hasEnd) {
                $updSql .= ", time_end = ?";
                $updParams[] = $classEndTime !== '' ? $classEndTime : null;
            }

            $updSql .= " WHERE id = ?";
            $updParams[] = (int) $existingId;

            $updStmt = $pdo->prepare($updSql);
            $updStmt->execute($updParams);
        } else {
            // Insert new row
            if ($hasStart && $hasEnd) {
                $stmt = $pdo->prepare("
                    INSERT INTO attendance_list
                        (account_number, date_today, time_start, time_end,
                         grade_level, section, subject, status, adviser)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $studentAccount,
                    $today,
                    $classStartTime !== '' ? $classStartTime : null,
                    $classEndTime !== '' ? $classEndTime : null,
                    $gradeLevel,
                    $section,
                    $classSubject,
                    $postedStatus,
                    $adviserAccount,
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO attendance_list
                        (account_number, date_today, grade_level, section, subject, status, adviser)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $studentAccount,
                    $today,
                    $gradeLevel,
                    $section,
                    $classSubject,
                    $postedStatus,
                    $adviserAccount,
                ]);
            }
        }
    } catch (PDOException $e) {
        error_log("attendance save failed: " . $e->getMessage());
        $_SESSION['flash']['attendance_error'] = 'Could not save attendance: ' . $e->getMessage();
    }

    header('Location: class-attendance.php?grade_level=' . urlencode($gradeLevel) . '&section=' . urlencode($section));
    exit;
}

/* ------------------------------------------------------------
   FETCH ENROLLED STUDENTS for this grade + section
   ------------------------------------------------------------ */
$students = [];
$fetchError = null;

try {
    $stmt = $pdo->prepare("
        SELECT
            es.id               AS enrollment_id,
            es.account_number,
            es.grade_level,
            es.section,
            es.academic_year,
            es.school_name      AS school_name,
            es.status           AS enrollment_status,
            s.first_name,
            s.middle_name,
            s.surname,
            s.sex,
            s.age,
            s.birth_date,
            s.contact_number,
            s.guardian,
            s.guardian_contact_number,
            s.user_type,
            s.account_number    AS student_account_exists
        FROM enrolled_students es
        LEFT JOIN students s ON s.account_number = es.account_number
        WHERE CAST(es.grade_level AS CHAR) = ?
          AND es.section = ?
          AND es.academic_year = ?
        ORDER BY s.surname ASC, s.first_name ASC
    ");
    $stmt->execute([$gradeLevel, $section, $currentYear]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    $fetchError = $e->getMessage();
    error_log("class-attendance: enrolled_students fetch failed: " . $fetchError);
}

$totalStudents = count($students);

/* ------------------------------------------------------------
   CHECK WHO IS ALREADY MARKED TODAY (for THIS subject) — with status
   ------------------------------------------------------------ */
$markedToday = [];   // acc => ['status' => ..., 'start' => ..., 'end' => ...]
if (!empty($students)) {
    try {
        $accounts = array_values(array_filter(array_map(
            fn($s) => trim((string) ($s['account_number'] ?? '')),
            $students
        )));
        if (!empty($accounts)) {
            $placeholders = implode(',', array_fill(0, count($accounts), '?'));

            $hasStart = false;
            $hasEnd = false;
            try {
                $cols = $pdo->query("SHOW COLUMNS FROM attendance_list")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($cols as $c) {
                    $name = strtolower((string) ($c['Field'] ?? ''));
                    if ($name === 'time_start') $hasStart = true;
                    if ($name === 'time_end')   $hasEnd = true;
                }
            } catch (PDOException $e) {
                error_log("attendance_list columns lookup (read) failed: " . $e->getMessage());
            }

            $timeCols = '';
            if ($hasStart) $timeCols .= ', time_start';
            if ($hasEnd)   $timeCols .= ', time_end';

            $sql = "
                SELECT DISTINCT account_number, status$timeCols
                FROM attendance_list
                WHERE date_today = ?
                  AND CAST(grade_level AS CHAR) = ?
                  AND section = ?
                  AND subject = ?
                  AND account_number IN ($placeholders)
            ";
            $params = array_merge([date('Y-m-d'), $gradeLevel, $section, $classSubject], $accounts);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $accKey = trim((string) $row['account_number']);
                $markedToday[$accKey] = [
                    'status' => trim((string) ($row['status'] ?? '')),
                    'start'  => trim((string) ($row['time_start'] ?? '')),
                    'end'    => trim((string) ($row['time_end'] ?? '')),
                ];
            }
        }
    } catch (PDOException $e) {
        error_log("attendance present check failed: " . $e->getMessage());
    }
}

/* ------------------------------------------------------------
   FLAG EACH STUDENT
   ------------------------------------------------------------ */
foreach ($students as &$s) {
    $acc = trim((string) ($s['account_number'] ?? ''));
    $hasStudentRecord = ($acc !== '' && !empty($s['student_account_exists']) && ($s['user_type'] ?? '') === 'student');
    $s['__has_student_record'] = $hasStudentRecord;
    $s['__is_enrolled'] = ($acc !== '' && !empty($s['enrollment_id']));
}
unset($s);

/* ------------------------------------------------------------
   SORT: pending (not marked) first, then marked
   ------------------------------------------------------------ */
usort($students, function ($a, $b) use ($markedToday) {
    $aAcc = trim((string) ($a['account_number'] ?? ''));
    $bAcc = trim((string) ($b['account_number'] ?? ''));
    $aMarked = ($aAcc !== '' && isset($markedToday[$aAcc]));
    $bMarked = ($bAcc !== '' && isset($markedToday[$bAcc]));

    if ($aMarked !== $bMarked) {
        return $aMarked ? 1 : -1;
    }
    return 0;
});

$markedCount = 0;
foreach ($students as $s) {
    $acc = trim((string) ($s['account_number'] ?? ''));
    if ($acc !== '' && isset($markedToday[$acc])) {
        $markedCount++;
    }
}
$pendingCount = $totalStudents - $markedCount;
?>
<!DOCTYPE html>
<html lang="fil">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>" />
    <title>Class Attendance — Eguia National High School</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        :root {
            --primary: #1e40af;
            --primary-2: #3b82f6;
            --primary-dark: #1d4ed8;
            --bg: #f8fafc;
            --text: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --radius: 10px;
            --shadow-md: 0 14px 34px -14px rgba(15, 23, 42, .22);
            --green: #047857;
            --green-bg: #ecfdf5;
            --green-line: #a7f3d0;
            --amber: #b45309;
            --amber-bg: #fffbeb;
            --amber-line: #fde68a;
        }

        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            overflow-y: scroll;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        img,
        svg {
            display: block;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: 'Poppins', sans-serif;
            line-height: 1.18;
            letter-spacing: -.02em;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* NAV */
        .nav {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, .96);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--line);
        }

        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 14px 0;
            min-height: 108px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 20px;
            min-width: 0;
            text-decoration: none;
            flex-shrink: 1;
        }

        .logo-mark {
            width: 80px;
            height: 80px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
        }

        .logo-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .logo-divider {
            width: 1px;
            height: 54px;
            background: var(--line);
            flex-shrink: 0;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
            line-height: 1.1;
            min-width: 0;
            gap: 3px;
        }

        .logo-text b {
            font-family: 'Poppins', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -.02em;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 480px;
        }

        .logo-text .school-name {
            font-size: 13.5px;
            font-weight: 500;
            color: var(--muted);
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 480px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
        }

        .page-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--primary-dark);
            padding: 8px 16px;
            white-space: nowrap;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            position: relative;
            flex-shrink: 0;
        }

        .nav-toggle {
            display: grid;
            width: 48px;
            height: 48px;
            border-radius: var(--radius);
            background: #f1f5f9;
            border: 1px solid var(--line);
            color: var(--text);
            cursor: pointer;
            place-items: center;
            font-size: 20px;
            transition: background .18s, color .18s, transform .18s;
        }

        .nav-toggle:hover {
            background: #eef2ff;
            color: var(--primary-dark);
        }

        .nav-toggle:active {
            transform: scale(.96);
        }

        .nav-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            left: auto;
            width: 280px;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            padding: 8px;
            z-index: 200;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px);
            transition: opacity .18s ease, transform .18s ease, visibility .18s ease;
        }

        .nav-dropdown.open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .nav-dropdown-head {
            padding: 10px 14px 8px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #94a3b8;
            font-weight: 700;
            border-bottom: 1px solid var(--line);
            margin-bottom: 6px;
        }

        .nav-dropdown a,
        .nav-dropdown button {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: var(--radius);
            color: var(--text);
            font-size: 15px;
            font-weight: 500;
            transition: background .15s, color .15s;
            cursor: pointer;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
            font-family: inherit;
        }

        .nav-dropdown a:hover,
        .nav-dropdown button:hover {
            background: #eef2ff;
            color: var(--primary-dark);
        }

        .nav-dropdown a i,
        .nav-dropdown button i {
            font-size: 16px;
            width: 18px;
            text-align: center;
            flex-shrink: 0;
            color: var(--primary);
        }

        /* MAIN */
        main {
            padding: 0 0 64px;
        }

        .section {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            padding: 24px;
            margin-top: 24px;
            margin-bottom: 24px;
        }

        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .section-head h2 {
            font-size: 18px;
            font-weight: 700;
        }

        .section-head p {
            font-size: 13px;
            color: var(--muted);
            margin-top: 2px;
        }

        /* PAGE HEADER */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            padding: 24px;
            border-radius: var(--radius);
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border: 1px solid #bfdbfe;
            margin-top: 30px;
        }

        .page-header-left {
            display: flex;
            align-items: center;
            gap: 18px;
            min-width: 0;
        }

        .page-header-icon {
            width: 62px;
            height: 62px;
            border-radius: 14px;
            background: #fff;
            display: grid;
            place-items: center;
            color: var(--primary-dark);
            font-size: 26px;
            box-shadow: 0 12px 26px -14px rgba(37, 99, 235, .55);
            flex-shrink: 0;
        }

        .page-header-text h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: var(--text);
            line-height: 1.2;
            margin-bottom: 4px;
        }

        .page-header-text .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 13px;
            color: #334155;
            align-items: center;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff;
            border: 1px solid #bfdbfe;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            color: #1e3a8a;
            white-space: nowrap;
        }

        .pill i {
            font-size: 11px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: transform .15s, box-shadow .15s, background .15s;
            white-space: nowrap;
        }

        .btn-back {
            background: #fff;
            color: var(--primary-dark);
            border: 1px solid #bfdbfe;
        }

        .btn-back:hover {
            background: #eff6ff;
        }

        /* FLASH ERROR */
        .flash.is-error {
            padding: 12px 16px;
            border-radius: var(--radius);
            font-size: 13.5px;
            font-weight: 500;
            margin-top: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        .flash.is-error i {
            font-size: 15px;
        }

        /* TABLE */
        .table-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--line);
            border-radius: var(--radius);
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }

        table.data thead {
            background: #f1f5f9;
        }

        table.data th,
        table.data td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
        }

        table.data th {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
        }

        table.data tbody tr:last-child td {
            border-bottom: none;
        }

        table.data tbody tr:hover {
            background: #f8fafc;
        }

        table.data tbody tr.is-pending td:first-child {
            border-left: 3px solid #f59e0b;
        }

        table.data tbody tr.is-present td:first-child {
            border-left: 3px solid #22c55e;
        }

        table.data tbody tr.is-missing td:first-child {
            border-left: 3px solid #ef4444;
        }

        table.data tbody tr.group-divider td {
            padding: 8px 14px;
            background: #f8fafc;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #94a3b8;
            border-left: none !important;
        }

        .name-block {
            display: flex;
            flex-direction: column;
            line-height: 1.25;
        }

        .name-block span.main {
            font-weight: 600;
        }

        .name-block span.sub {
            font-size: 11.5px;
            color: var(--muted);
            font-weight: 500;
            margin-top: 2px;
        }

        .cell-subject {
            font-weight: 500;
            color: var(--text);
        }

        .cell-subject.empty {
            color: #cbd5e1;
            font-style: italic;
            font-weight: 400;
        }

        .cell-subject .sub-time {
            display: block;
            font-size: 11px;
            color: var(--muted);
            margin-top: 2px;
            font-weight: 500;
        }

        /* STATUS SELECT */
        .status-form {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        .status-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding: 7px 30px 7px 12px;
            border-radius: 8px;
            border: 1px solid var(--line);
            background-color: #fff;
            font-family: inherit;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text);
            cursor: pointer;
            min-width: 120px;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>");
            background-repeat: no-repeat;
            background-position: right 10px center;
            transition: border-color .15s, box-shadow .15s;
        }

        .status-select:focus {
            outline: none;
            border-color: var(--primary-2);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .18);
        }

        .status-select.status-pick-on-time { color: var(--green); }
        .status-select.status-pick-late    { color: var(--amber); }
        .status-select.status-pick-absent  { color: #b91c1c; }
        .status-select.status-pick-excused { color: var(--primary-dark); }

        /* SUBMIT BUTTON */
        .btn-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 8px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #fff;
            font-size: 12.5px;
            font-weight: 700;
            font-family: inherit;
            border: none;
            cursor: pointer;
            box-shadow: 0 6px 14px -8px rgba(37, 99, 235, .85);
            transition: transform .15s, box-shadow .15s, opacity .15s;
            white-space: nowrap;
        }

        .btn-mark:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 10px 18px -10px rgba(37, 99, 235, .95);
        }

        .btn-mark:disabled {
            opacity: .45;
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn-mark i {
            font-size: 11.5px;
        }

        /* STATUS BADGE (already marked) */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .status-badge i {
            font-size: 11.5px;
        }

        .status-badge.s-present {
            background: var(--green-bg);
            color: var(--green);
            border-color: var(--green-line);
        }

        .status-badge.s-late {
            background: var(--amber-bg);
            color: var(--amber);
            border-color: var(--amber-line);
        }

        .status-badge.s-absent {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }

        .status-badge.s-excused {
            background: #eff6ff;
            color: var(--primary-dark);
            border-color: #bfdbfe;
        }

        .status-badge.s-default {
            background: #f1f5f9;
            color: #64748b;
            border-color: var(--line);
        }

        .status-badge .present-time {
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 0;
            text-transform: none;
            margin-left: 4px;
            opacity: .8;
        }

        /* MISSING / UNVERIFIED BADGE */
        .badge-missing {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-radius: 6px;
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            font-size: 11.5px;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge-missing i {
            font-size: 10.5px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }

        .empty-state i {
            font-size: 40px;
            color: #cbd5e1;
            margin-bottom: 12px;
            display: block;
        }

        .debug {
            margin-top: 12px;
            font-family: ui-monospace, 'Courier New', monospace;
            font-size: 11.5px;
            color: #b91c1c;
            word-break: break-word;
        }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .nav-inner {
                padding: 12px 0;
                gap: 14px;
                min-height: 92px;
            }

            .nav-toggle {
                width: 46px;
                height: 46px;
                font-size: 19px;
            }

            .logo {
                gap: 16px;
            }

            .logo-mark {
                width: 68px;
                height: 68px;
            }

            .logo-divider {
                height: 46px;
            }

            .logo-text b {
                font-size: 23px;
                max-width: 380px;
            }

            .logo-text .school-name {
                font-size: 12.5px;
                max-width: 380px;
            }

            .page-label {
                font-size: 11.5px;
                padding: 7px 14px;
            }
        }

        @media (max-width: 820px) {
            .section {
                margin-top: 20px;
            }

            .nav-inner {
                padding: 10px 0;
                gap: 10px;
                min-height: 76px;
            }

            .logo {
                gap: 12px;
                flex: 1;
                min-width: 0;
            }

            .logo-mark {
                width: 56px;
                height: 56px;
            }

            .logo-divider {
                height: 40px;
            }

            .logo-text b {
                font-size: 19px;
                max-width: 240px;
            }

            .logo-text .school-name {
                font-size: 11px;
                max-width: 240px;
            }

            .nav-toggle {
                width: 44px;
                height: 44px;
                font-size: 18px;
            }

            .page-label {
                font-size: 10.5px;
                padding: 6px 12px;
                gap: 6px;
            }

            .section {
                padding: 20px;
                margin-bottom: 20px;
            }

            .page-header {
                padding: 20px;
            }
        }

        @media (max-width: 640px) {
            .container {
                padding: 0 16px;
            }

            .section {
                margin-top: 16px;
                padding: 18px 14px;
                margin-bottom: 18px;
            }

            .page-header {
                padding: 16px;
                gap: 12px;
            }

            .page-header-icon {
                width: 50px;
                height: 50px;
                font-size: 22px;
                border-radius: 12px;
            }

            .page-header-text h1 {
                font-size: 18px;
            }

            .nav-inner {
                padding: 10px 0;
                gap: 8px;
                min-height: 66px;
            }

            .logo {
                gap: 10px;
            }

            .logo-mark {
                width: 46px;
                height: 46px;
            }

            .logo-divider {
                display: none;
            }

            .logo-text b {
                font-size: 16px;
                max-width: 160px;
            }

            .logo-text .school-name {
                font-size: 10px;
                max-width: 160px;
            }

            .nav-toggle {
                width: 42px;
                height: 42px;
                font-size: 17px;
            }

            .page-label {
                display: none;
            }

            .nav-dropdown {
                width: 250px;
            }

            table.data th,
            table.data td {
                padding: 10px 12px;
                font-size: 13px;
            }

            .status-form {
                flex-direction: column;
                align-items: stretch;
                gap: 6px;
            }

            .status-select {
                min-width: 100%;
            }
        }

        @media (max-width: 400px) {
            .logo-mark {
                width: 40px;
                height: 40px;
            }

            .logo-text b {
                font-size: 14px;
                max-width: 130px;
            }

            .logo-text .school-name {
                font-size: 9px;
                max-width: 130px;
            }
        }
    </style>
</head>

<body>
    <header class="nav" id="nav">
        <div class="container nav-inner">
            <a href="home.php" class="logo">
                <div class="logo-mark">
                    <img src="https://campuslink.villaruz-print-shop-and-general-merchandise.shop/Images/logo.png"
                        alt="Eguia National High School logo" onerror="this.style.display='none'">
                </div>
                <div class="logo-divider" aria-hidden="true"></div>
                <div class="logo-text">
                    <b>CampusLink</b>
                    <span class="school-name"><?php echo htmlspecialchars($teacherSchoolName); ?></span>
                </div>
            </a>

            <div class="nav-right">
                <span class="page-label">Class Attendance</span>

                <div class="nav-actions">
                    <button class="nav-toggle" id="navToggle" aria-label="Buksan ang menu" aria-expanded="false">
                        <i class="fas fa-bars"></i>
                    </button>

                    <div class="nav-dropdown" id="navDropdown" role="menu">
                        <div class="nav-dropdown-head">MENU</div>
                        <a href="advisory-class.php" role="menuitem">
                            <i class="fas fa-chalkboard-user"></i> Class Advisory
                        </a>

                        <a href="update-grades.php" role="menuitem">
                            <i class="fas fa-chart-line"></i> Grading
                        </a>
                        <div class="nav-dropdown-head"
                            style="margin-top: 6px; border-top: 1px solid var(--line); padding-top: 10px;">
                            <?php echo htmlspecialchars($displayName ?: ($user['account_number'] ?? '')); ?>
                        </div>
                        <a href="../index.php" role="menuitem">
                            <i class="fas fa-right-from-bracket"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container">
        <!-- PAGE HEADER -->
        <div class="page-header">
            <div class="page-header-left">
                <div class="page-header-icon">
                    <i class="fas fa-clipboard-user"></i>
                </div>
                <div class="page-header-text">
                    <h1>Class Attendance</h1>
                    <div class="meta">
                        <span class="pill"><i class="fas fa-layer-group"></i> Grade
                            <?php echo htmlspecialchars($gradeLevel); ?></span>
                        <span class="pill"><i class="fas fa-users"></i> <?php echo htmlspecialchars($section); ?></span>
                        <?php if ($classSubject !== ''): ?>
                            <span class="pill"><i class="fas fa-book"></i>
                                <?php echo htmlspecialchars($classSubject); ?></span>
                        <?php endif; ?>
                        <?php if ($classTimeDisplay !== ''): ?>
                            <span class="pill"><i class="fas fa-clock"></i>
                                <?php echo htmlspecialchars($classTimeDisplay); ?></span>
                        <?php endif; ?>
                        <span class="pill"><i class="fas fa-calendar-days"></i> S.Y.
                            <?php echo htmlspecialchars($currentYear); ?></span>
                        <span class="pill"><i class="fas fa-user-graduate"></i> <?php echo (int) $totalStudents; ?>
                            student<?php echo $totalStudents === 1 ? '' : 's'; ?></span>
                        <span class="pill" style="background:#fffbeb;border-color:#fde68a;color:#b45309;">
                            <i class="fas fa-user-clock"></i> <?php echo (int) $pendingCount; ?> pending
                        </span>
                    </div>
                </div>
            </div>

            <a href="advisory-class.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to Advisory
            </a>
        </div>

        <?php if ($flashError !== null): ?>
            <div class="flash is-error">
                <i class="fas fa-triangle-exclamation"></i>
                <?php echo htmlspecialchars($flashError); ?>
            </div>
        <?php endif; ?>

        <!-- STUDENTS -->
        <section class="section">
            <div class="section-head">
                <div>
                    <h2>Enrolled Students</h2>
                    <p>Pending students appear first. Already marked sink to the bottom.</p>
                </div>
            </div>

            <?php if ($fetchError !== null): ?>
                <div class="empty-state">
                    <i class="fas fa-triangle-exclamation" style="color:#f59e0b;"></i>
                    <p>Could not load students. Please try again later.</p>
                    <div class="debug">Debug: <?php echo htmlspecialchars($fetchError); ?></div>
                </div>
            <?php elseif (empty($students)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No students are enrolled in this grade and section for S.Y.
                        <?php echo htmlspecialchars($currentYear); ?>.
                    </p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Account #</th>
                                <th>Full Name</th>
                                <th>Contact</th>
                                <th>Guardian</th>
                                <th>Subject</th>
                                <th>Attendance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rowIndex = 0;
                            $lastWasPending = null;
                            foreach ($students as $s):
                                $fullName = trim(
                                    ($s['first_name'] ?? '') . ' ' .
                                    ($s['middle_name'] ?? '') . ' ' .
                                    ($s['surname'] ?? '')
                                );
                                if ($fullName === '') {
                                    $fullName = '(Unknown Name)';
                                }
                                $contact = trim((string) ($s['contact_number'] ?? ''));
                                $guardian = trim((string) ($s['guardian'] ?? ''));
                                $guardianContact = trim((string) ($s['guardian_contact_number'] ?? ''));
                                $accountNumber = trim((string) ($s['account_number'] ?? ''));
                                $hasStudentRecord = !empty($s['__has_student_record']);

                                $marked = ($accountNumber !== '' && isset($markedToday[$accountNumber]))
                                    ? $markedToday[$accountNumber]
                                    : null;
                                $isMarked = $marked !== null;
                                $isPending = !$isMarked;
                                $isMissing = !$hasStudentRecord;

                                $rowMarkedStatus = $isMarked ? (string) ($marked['status'] ?? '') : '';
                                $rowMarkedKey    = $isMarked ? statusKeyFor($rowMarkedStatus) : 'default';
                                $rowMarkedLabel  = $isMarked ? statusLabelFor($rowMarkedStatus) : '—';

                                $rowPresentTime = '';
                                if ($isMarked) {
                                    $rowPresentTime = formatTimeRange12h(
                                        (string) ($marked['start'] ?? ''),
                                        (string) ($marked['end'] ?? '')
                                    );
                                }

                                $rowIndex++;
                                ?>

                                <?php if ($lastWasPending === true && $isPending === false): ?>
                                    <tr class="group-divider">
                                        <td colspan="10">
                                            <i class="fas fa-check" style="color:#22c55e;margin-right:6px;"></i>
                                            Already Marked (<?php echo (int) $markedCount; ?>)
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <tr class="<?php echo $isMissing ? 'is-missing' : ($isMarked ? 'is-present' : 'is-pending'); ?>">
                                    <td><?php echo $rowIndex; ?></td>
                                    <td><?php echo htmlspecialchars($accountNumber !== '' ? $accountNumber : '—'); ?></td>
                                    <td>
                                        <div class="name-block">
                                            <span class="main"><?php echo htmlspecialchars($fullName); ?></span>
                                            <?php if ($contact !== ''): ?>
                                                <span class="sub"><?php echo htmlspecialchars($contact); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($contact !== '' ? $contact : '—'); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($guardian !== '' ? $guardian : '—'); ?>
                                        <?php if ($guardianContact !== ''): ?>
                                            <br><span
                                                style="color:#64748b; font-size:12px;"><?php echo htmlspecialchars($guardianContact); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="cell-subject <?php echo $classSubject === '' ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($classSubject !== '' ? $classSubject : '—'); ?>
                                        <?php if ($classTimeDisplay !== ''): ?>
                                            <span class="sub-time"><?php echo htmlspecialchars($classTimeDisplay); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="status-cell">
                                        <?php if (!$hasStudentRecord): ?>
                                            <span class="badge-missing" title="No matching record in students table">
                                                <i class="fas fa-user-slash"></i> Not in DB
                                            </span>
                                        <?php elseif ($isMarked): ?>
                                            <span class="status-badge s-<?php echo htmlspecialchars($rowMarkedKey); ?>">
                                                <i class="fas <?php echo htmlspecialchars(statusIconFor($rowMarkedStatus)); ?>"></i>
                                                <?php echo htmlspecialchars($rowMarkedLabel); ?>
                                               
                                            </span>
                                        <?php elseif ($accountNumber !== ''): ?>
                                            <form method="post" class="status-form" data-status-form>
                                                <input type="hidden" name="csrf_token"
                                                    value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="account_number"
                                                    value="<?php echo htmlspecialchars($accountNumber); ?>">
                                                <input type="hidden" name="mark_attendance" value="1">
                                                <select name="status" class="status-select" required data-status-select>
                                                    <option value="" disabled selected>— Select —</option>
                                                    <option value="On Time">On Time</option>
                                                    <option value="Late">Late</option>
                                                    <option value="Absent">Absent</option>
                                                    <option value="Excused">Excused</option>
                                                </select>
                                                <button type="submit" class="btn-mark" disabled data-status-submit>
                                                    <i class="fas fa-check"></i> Save
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color:#94a3b8;font-size:12px;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <?php
                                $lastWasPending = $isPending;
                            endforeach;
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
        const navToggle = document.getElementById('navToggle');
        const navDropdown = document.getElementById('navDropdown');

        navToggle?.addEventListener('click', (e) => {
            e.stopPropagation();
            const open = navDropdown.classList.toggle('open');
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        document.addEventListener('click', (e) => {
            if (navDropdown && !navDropdown.contains(e.target) && e.target !== navToggle) {
                navDropdown.classList.remove('open');
                navToggle.setAttribute('aria-expanded', 'false');
            }
        });

        /* ── Status form: enable Save once a status is picked + tint select ── */
        document.querySelectorAll('[data-status-form]').forEach((form) => {
            const select = form.querySelector('[data-status-select]');
            const submit = form.querySelector('[data-status-submit]');
            if (!select || !submit) return;

            function sync() {
                const v = select.value;
                submit.disabled = (v === '');

                select.classList.remove('status-pick-on-time', 'status-pick-late', 'status-pick-absent', 'status-pick-excused');
                if (v === 'On Time') select.classList.add('status-pick-on-time');
                else if (v === 'Late') select.classList.add('status-pick-late');
                else if (v === 'Absent') select.classList.add('status-pick-absent');
                else if (v === 'Excused') select.classList.add('status-pick-excused');
            }

            select.addEventListener('change', sync);
            sync();
        });
    </script>
</body>

</html>