<?php
// root/academic-information.php
// Shows: Enrollment Details (with adviser) + My Subjects
// If the student has no enrollment record, an inline "Not Enrolled" panel
// is shown with a link to student-enrollment.php (NO redirect — prevents loop).
require_once __DIR__ . '/DB_Conn/config.php';

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
   FLASH
   ------------------------------------------------------------ */
$flashError = $_SESSION['flash']['enroll_error'] ?? null;
$flashSuccess = $_SESSION['flash']['enroll_success'] ?? null;

unset(
    $_SESSION['flash']['enroll_error'],
    $_SESSION['flash']['enroll_success'],
    $_SESSION['flash']['enrolled_subjects']
);

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
   HELPERS
   ------------------------------------------------------------ */
function buildFullName(string $first, string $middle, string $last): string
{
    $parts = array_filter(
        [trim($first), trim($middle), trim($last)],
        fn($p) => $p !== ''
    );
    return implode(' ', $parts);
}

function resolveTeacherNameFromJoin(array $row): string
{
    $name = buildFullName(
        (string) ($row['teacher_first'] ?? ''),
        (string) ($row['teacher_middle'] ?? ''),
        (string) ($row['teacher_last'] ?? '')
    );

    if ($name !== '') {
        return $name;
    }

    $surname = trim((string) ($row['surname'] ?? ''));
    if ($surname !== '') {
        return $surname;
    }

    return '—';
}

function fetchTeacherNameByAccount(PDO $pdo, string $account): string
{
    $account = trim($account);
    if ($account === '') {
        return '';
    }

    try {
        $stmt = $pdo->prepare("
            SELECT first_name, middle_name, surname
            FROM teachers
            WHERE account_number = ?
            LIMIT 1
        ");
        $stmt->execute([$account]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$row) {
            return '';
        }

        return buildFullName(
            (string) ($row['first_name'] ?? ''),
            (string) ($row['middle_name'] ?? ''),
            (string) ($row['surname'] ?? '')
        );
    } catch (PDOException $e) {
        error_log("fetchTeacherNameByAccount failed: " . $e->getMessage());
        return '';
    }
}

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

function formatDayShort(string $day): string
{
    $d = strtolower(trim($day));

    return match ($d) {
        'monday' => 'M',
        'tuesday' => 'T',
        'wednesday' => 'W',
        'thursday' => 'Th',
        'friday' => 'F',
        'saturday' => 'S',
        'sunday' => 'Su',
        default => $day,
    };
}

/* ------------------------------------------------------------
   FETCH LOGGED-IN STUDENT
   ------------------------------------------------------------ */
$isLoggedIn = false;
$user = null;

if (!empty($_SESSION['account_number']) && is_string($_SESSION['account_number'])) {
    $accountNumber = trim($_SESSION['account_number']);

    if (strlen($accountNumber) > 5) {
        try {
            $stmt = $pdo->prepare("
                SELECT *
                FROM students
                WHERE account_number = ?
                  AND user_type = 'student'
                LIMIT 1
            ");
            $stmt->execute([$accountNumber]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("student fetch failed: " . $e->getMessage());
            $user = null;
        }
    }

    if (!$user) {
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
        header('Location: index.php');
        exit;
    } else {
        $isLoggedIn = true;
    }
}

/* ------------------------------------------------------------
   HARD GUARD — no session = no access
   ------------------------------------------------------------ */
if (!$isLoggedIn || !$user) {
    header('Location: index.php');
    exit;
}

/* ------------------------------------------------------------
   CHECK ENROLLMENT STATUS (no redirect — use flag instead)
   ------------------------------------------------------------ */
$rawEnrollmentStatus = strtolower(trim((string) ($user['enrollment_status'] ?? '')));
$isNotEnrolledStatus = ($rawEnrollmentStatus === 'not enrolled');

/* ------------------------------------------------------------
   DISPLAY NAME
   ------------------------------------------------------------ */
$displayName = '';
if ($user) {
    $displayName = buildFullName(
        (string) ($user['first_name'] ?? ''),
        (string) ($user['middle_name'] ?? ''),
        (string) ($user['surname'] ?? '')
    );
}

/* ------------------------------------------------------------
   ENROLLMENT STATUS (student level)
   ------------------------------------------------------------ */
$enrollmentStatus = $user ? strtolower(trim((string) ($user['enrollment_status'] ?? 'pending'))) : 'pending';
$enrolledThisYear = in_array($enrollmentStatus, ['enrolled', 'active'], true);
$isPendingStudent = ($enrollmentStatus === 'pending');

/* ------------------------------------------------------------
   FETCH ENROLLMENT RECORD
   ------------------------------------------------------------ */
$enrollmentRecord = null;
$displayStatus = null;

if ($isLoggedIn && $user) {
    try {
        $stmt = $pdo->prepare("
            SELECT *
            FROM enrolled_students
            WHERE account_number = ?
              AND academic_year = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$user['account_number'], $currentYear]);
        $enrollmentRecord = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$enrollmentRecord) {
            $stmt = $pdo->prepare("
                SELECT *
                FROM enrolled_students
                WHERE account_number = ?
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmt->execute([$user['account_number']]);
            $enrollmentRecord = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        if ($enrollmentRecord) {
            $rawStatus = trim((string) ($enrollmentRecord['status'] ?? ''));
            if (strcasecmp($rawStatus, 'Active') === 0 || strcasecmp($rawStatus, 'Enrolled') === 0) {
                $displayStatus = 'Enrolled';
            } else {
                $displayStatus = $rawStatus ?: 'Enrolled';
            }
        }
    } catch (PDOException $e) {
        error_log("enrollment record fetch failed: " . $e->getMessage());
    }
}

/* ------------------------------------------------------------
   NO RECORD → set flag (NO redirect — prevents loop with
   student-enrollment.php which redirects back here)
   ------------------------------------------------------------ */
$needsEnrollment = $isNotEnrolledStatus || !$enrollmentRecord;

/* Effective grade + section */
$effectiveGrade = '';
$effectiveSection = '';

if ($enrollmentRecord) {
    $effectiveGrade = trim((string) ($enrollmentRecord['grade_level'] ?? ''));
    $effectiveSection = trim((string) ($enrollmentRecord['section'] ?? ''));
}

if ($effectiveGrade === '' && !empty($user['grade_level'])) {
    $effectiveGrade = trim((string) $user['grade_level']);
}

/* ------------------------------------------------------------
   PENDING STATE
   ------------------------------------------------------------ */
$recordStatusRaw = $enrollmentRecord
    ? strtolower(trim((string) ($enrollmentRecord['status'] ?? '')))
    : '';
$isPendingRecord = ($recordStatusRaw === 'pending');
$isPending = ($isPendingRecord || $isPendingStudent);

/* ------------------------------------------------------------
   DETECT ASSIGNMENT TABLE
   ------------------------------------------------------------ */
$assignmentTable = null;
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
            $assignmentTable = $candidate;
            break;
        }
    }
} catch (PDOException $e) {
    error_log("table detection failed: " . $e->getMessage());
}

/* ------------------------------------------------------------
   FETCH ADVISER
   ------------------------------------------------------------ */
$adviserName = '';
$adviserAccount = '';
$adviserFetchErr = null;

if (
    $isLoggedIn
    && $user
    && !$needsEnrollment
    && $assignmentTable !== null
    && $effectiveGrade !== ''
    && $effectiveSection !== ''
) {
    try {
        $sql = "
            SELECT
                at.adviser,
                at.surname,
                t.first_name  AS teacher_first,
                t.middle_name AS teacher_middle,
                t.surname     AS teacher_last
            FROM `{$assignmentTable}` at
            LEFT JOIN teachers t
                ON t.account_number = at.adviser
            WHERE CAST(at.grade_level AS CHAR) = ?
              AND at.section = ?
              AND at.advisory = 1
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$effectiveGrade, $effectiveSection]);
        $adviserRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($adviserRow) {
            $adviserAccount = trim((string) ($adviserRow['adviser'] ?? ''));
            $adviserName = resolveTeacherNameFromJoin($adviserRow);
        }
    } catch (PDOException $e) {
        $adviserFetchErr = $e->getMessage();
        error_log("adviser fetch failed: " . $adviserFetchErr);
    }
}

/* ------------------------------------------------------------
   FETCH ALL SUBJECTS + THEIR TEACHERS
   ------------------------------------------------------------ */
$studentSubjects = [];
$subjectsFetchErr = null;

if (
    $isLoggedIn
    && $user
    && !$needsEnrollment
    && $assignmentTable !== null
    && $effectiveGrade !== ''
    && $effectiveSection !== ''
) {
    try {
        $sql = "
            SELECT
                at.id,
                at.subject,
                at.adviser,
                at.surname,
                at.grade_level,
                at.section,
                at.days,
                at.start_time,
                at.end_time,
                t.first_name  AS teacher_first,
                t.middle_name AS teacher_middle,
                t.surname     AS teacher_last
            FROM `{$assignmentTable}` at
            LEFT JOIN teachers t
                ON t.account_number = at.adviser
            WHERE CAST(at.grade_level AS CHAR) = ?
              AND at.section = ?
            ORDER BY at.subject ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$effectiveGrade, $effectiveSection]);
        $studentSubjects = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        $subjectsFetchErr = $e->getMessage();
        error_log("teacher-assignment fetch failed: " . $subjectsFetchErr);
        $studentSubjects = [];
    }
}

$showSuccessFlash = (bool) $flashSuccess;
$showErrorFlash = (bool) $flashError;

$justEnrolled = !empty($_SESSION['flash']['just_enrolled']);
unset($_SESSION['flash']['just_enrolled']);
?>
<!DOCTYPE html>
<html lang="fil">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>" />
    <title>Eguia National High School — Dasol, Pangasinan</title>
    <meta name="description" content="Opisyal na portal ng Eguia National High School sa Dasol, Pangasinan." />

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
            --radius: 8px;
            --shadow-md: 0 14px 34px -14px rgba(15, 23, 42, .22);
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
            background-color: #1f2327;
            background-image:
                linear-gradient(rgba(15, 23, 42, .18), rgba(15, 23, 42, .18)),
                url('https://campuslink.villaruz-print-shop-and-general-merchandise.shop/Images/Colorful_Back_to_School_Stationery_Border.png');
            background-size: cover;
            background-position: center top;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            min-height: 100vh;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -2;
            background-color: #0a1a33;
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            background-image: url("https://campuslink.villaruz-print-shop-and-general-merchandise.shop/Images/desktop.jpg");
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
            background: #08285f;
            border-bottom: 0;
            overflow: visible;
            isolation: isolate;
        }

        .nav-inner {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 10px 0;
            min-height: 120px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 22px;
            min-width: 0;
            text-decoration: none;
            flex-shrink: 1;
        }

        .logo-mark {
            width: 191px;
            height: 107px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            overflow: hidden;
            background: #08285f;
            border-radius: 2px;
        }

        .logo-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .logo-divider {
            display: none;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
            line-height: 1.1;
            min-width: 0;
            gap: 0;
        }

        .logo-text b {
            font-family: 'Poppins', sans-serif;
            font-size: 30px;
            font-weight: 800;
            color: #fff;
            letter-spacing: .01em;
            line-height: 1.1;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .logo-text .school-name {
            display: none;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 18px;
            flex-shrink: 0;
        }

        .page-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #fff;
            padding: 8px 10px;
            white-space: nowrap;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            position: relative;
            flex-shrink: 0;
            z-index: 201;
        }

        .nav-toggle {
            display: grid;
            width: 60px;
            height: 60px;
            border-radius: 15px;
            background: #f8fafc;
            border: 0;
            color: #0f172a;
            cursor: pointer;
            place-items: center;
            font-size: 24px;
            transition: background .18s, color .18s, transform .18s;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .12);
        }

        .nav-toggle:hover {
            background: #fff;
            color: #08285f;
        }

        .nav-toggle:active {
            transform: scale(.96);
        }

        .nav-dropdown {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            left: auto;
            width: 280px;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
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
            border-radius: 8px;
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

        /* PANEL */
        .panel {
            background: transparent;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            padding: 0;
            margin: 30px 0 60px;
        }

        /* Welcome card */
        .welcome-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 14px;
            padding: 40px 24px;
            border-radius: 0;
            border: 0;
            background: transparent;
            box-shadow: none;
        }

        .welcome-card .welcome-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 30px;
            margin-bottom: 4px;
        }

        .welcome-card h2 {
            font-size: 22px;
            font-weight: 800;
            color: var(--text);
        }

        .welcome-card p {
            font-size: 15px;
            color: var(--muted);
            max-width: 540px;
            line-height: 1.65;
        }

        .welcome-card p strong {
            color: var(--text);
        }

        .welcome-card .account-box {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 8px;
            padding: 10px 20px;
            border-radius: var(--radius);
            background: #fff;
            border: 1px solid var(--line);
            font-size: 14px;
            color: var(--muted);
            font-weight: 500;
        }

        .welcome-card .account-box i {
            color: var(--primary);
            font-size: 16px;
        }

        .welcome-card .account-box b {
            color: var(--primary-dark);
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            letter-spacing: .02em;
        }

        /* ENROLLMENT DETAILS */
        .enrolled-summary {
            padding: 24px 20px;
            border-radius: var(--radius);
            border: 1px solid var(--line);
            background: rgba(8, 40, 95, .72);
            backdrop-filter: blur(0px);
            -webkit-backdrop-filter: blur(0px);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .enrolled-summary h3 {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 14px;
        }

        .enrolled-summary .summary-row {
            display: grid;
            grid-template-columns: 200px 20px minmax(0, auto);
            align-items: baseline;
            column-gap: 8px;
            font-size: 15px;
            line-height: 1.9;
            width: 100%;
            max-width: 520px;
        }

        .enrolled-summary .summary-row .label {
            text-align: right;
            color: var(--muted);
            font-weight: 500;
            white-space: nowrap;
        }

        .enrolled-summary .summary-row .dash {
            text-align: center;
            color: var(--muted);
            font-weight: 400;
        }

        .enrolled-summary .summary-row .value {
            text-align: left;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            color: var(--primary-dark);
            word-break: break-word;
        }

        .enrolled-summary .summary-row .value.muted-value {
            color: #0781fa;
            font-weight: 500;
            font-style: italic;
        }

        /* Status pill */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 12px;
            border-radius: 999px;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .02em;
            line-height: 1.6;
        }

        .status-pill.pending {
            background: #fef3c7;
            color: #92400e;
        }

        /* SUBJECTS */
        .subjects-section {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px dashed var(--line);
        }

        .subjects-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            flex-wrap: wrap;
            gap: 8px;
        }

        .subjects-head h3 {
            font-size: 15px;
            font-weight: 700;
            color: black;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .subjects-head h3 i {
            color: var(--primary);
            font-size: 15px;
        }

        .subjects-count {
            font-size: 12px;
            color: var(--muted);
            padding: 3px 10px;
            border-radius: 5px;
            font-weight: 600;
        }

        .subjects-table-container {
            width: 100%;
            padding: 24px 20px;
            border-radius: var(--radius);
            border: 1px solid rgba(255, 255, 255, .3);
            background: rgba(8, 40, 95, .72);
            backdrop-filter: blur(0px);
            -webkit-backdrop-filter: blur(0px);
            box-shadow: 0 14px 34px -14px rgba(0, 0, 0, .35);
        }

        .subjects-table-container .subjects-wrap {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: transparent;
        }

        table.subjects {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            background: transparent;
        }

        table.subjects thead {
            background: rgba(255, 255, 255, 0.1);
        }

        table.subjects th,
        table.subjects td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #ffffff !important;
        }

        table.subjects th {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(255, 255, 255, 0.7) !important;
        }

        table.subjects tbody tr:last-child td {
            border-bottom: none;
        }

        .subjects-table-container table.subjects,
        .subjects-table-container table.subjects :where(th, td, span, a, b, strong, i, em, code, p, div) {
            color: #ffffff !important;
        }

        .subjects-table-container table.subjects th {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        .empty-subjects {
            text-align: center;
            padding: 30px 16px;
            color: var(--muted);
            font-size: 14px;
        }

        .empty-subjects i {
            font-size: 28px;
            color: #cbd5e1;
            margin-bottom: 8px;
            display: block;
        }

        .empty-subjects .debug {
            display: block;
            margin-top: 10px;
            font-size: 11.5px;
            color: #b91c1c;
            font-family: monospace;
            word-break: break-all;
        }

        /* NOT-ENROLLED CTA */
        .not-enrolled-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 16px;
            padding: 48px 24px;
            border-radius: var(--radius);
            border: 1px solid rgba(255, 255, 255, .25);
            background: rgba(8, 40, 95, .72);
            margin-top: 20px;
        }

        .not-enrolled-card .icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 30px;
            background: rgba(254, 243, 199, .15);
            color: #fbbf24;
        }

        .not-enrolled-card h3 {
            font-size: 20px;
            font-weight: 800;
            color: #fff;
        }

        .not-enrolled-card p {
            font-size: 15px;
            color: rgba(255, 255, 255, .8);
            max-width: 460px;
            line-height: 1.65;
        }

        .not-enrolled-card .btn-enroll {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 26px;
            background: #3b82f6;
            color: #fff !important;
            border-radius: 8px;
            font-weight: 700;
            font-size: 15px;
            transition: background .18s, transform .18s;
            text-decoration: none;
        }

        .not-enrolled-card .btn-enroll:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        /* SPINNER */
        .spinner-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 24px;
            background: rgba(10, 26, 51, 0.82);
            backdrop-filter: blur(0px);
            -webkit-backdrop-filter: blur(0px);
            color: #fff;
            opacity: 1;
            transition: opacity .4s ease;
        }

        .spinner-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .spinner {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            border: 5px solid rgba(255, 255, 255, .18);
            border-top-color: #22d3ee;
            border-right-color: #3b82f6;
            animation: spin 0.9s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .spinner-overlay .spinner-title {
            font-family: 'Poppins', sans-serif;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: .01em;
        }

        .spinner-overlay .spinner-sub {
            font-size: 13.5px;
            color: rgba(255, 255, 255, .75);
            max-width: 420px;
            text-align: center;
            padding: 0 20px;
        }

        /* GLOBAL WHITE TEXT */
        body,
        body :where(h1, h2, h3, h4, h5, h6, p, span, div, a, button, label, strong, b, small, th, td, li, i, em, code) {
            color: #ffffff !important;
        }

        .nav-dropdown {
            background: #08285f;
            border-color: rgba(255, 255, 255, .22);
        }

        .nav-dropdown-head {
            color: #ffffff !important;
            border-bottom-color: rgba(255, 255, 255, .22);
        }

        .nav-dropdown a:hover,
        .nav-dropdown button:hover {
            background: rgba(255, 255, 255, .14);
            color: #ffffff !important;
        }

        .nav-dropdown a i,
        .nav-dropdown button i {
            color: #ffffff !important;
        }

        .nav-toggle {
            color: #ffffff !important;
            background: #123b78;
        }

        .nav-toggle:hover {
            background: #1d4e89;
            color: #ffffff !important;
        }

        .subjects-count,
        .welcome-card .account-box {
            background: rgba(8, 40, 95, .78);
            border-color: rgba(255, 255, 255, .3);
        }

        .status-pill.pending,
        .status-pill.enrolled {
            color: greenyellow !important;
        }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .nav-inner {
                padding: 10px 0;
                gap: 14px;
                min-height: 100px;
            }

            .logo {
                gap: 16px;
            }

            .logo-mark {
                width: 150px;
                height: 84px;
            }

            .logo-text b {
                font-size: 25px;
            }

            .nav-toggle {
                width: 54px;
                height: 54px;
                font-size: 22px;
            }
        }

        @media (max-width: 820px) {
            .nav-inner {
                padding: 8px 0;
                gap: 10px;
                min-height: 82px;
            }

            .logo {
                gap: 12px;
                flex: 1;
                min-width: 0;
            }

            .logo-mark {
                width: 125px;
                height: 70px;
            }

            .logo-text b {
                font-size: 21px;
            }

            .nav-toggle {
                width: 48px;
                height: 48px;
                font-size: 20px;
                border-radius: 12px;
            }

            .page-label {
                font-size: 11px;
                padding: 6px 8px;
            }
        }

        @media (max-width: 640px) {
            .container {
                padding: 0 16px;
            }

            .nav-inner {
                padding: 7px 0;
                gap: 8px;
                min-height: 68px;
            }

            .logo {
                gap: 8px;
            }

            .logo-mark {
                width: 100px;
                height: 56px;
            }

            .logo-text b {
                font-size: 16px;
                max-width: 145px;
            }

            .nav-toggle {
                width: 42px;
                height: 42px;
                font-size: 17px;
                border-radius: 10px;
            }

            .page-label {
                display: none;
            }

            .nav-dropdown {
                width: 250px;
            }

            .panel {
                padding: 0;
                margin: 20px 0 40px;
            }

            table.subjects th,
            table.subjects td {
                padding: 10px 12px;
                font-size: 13px;
            }

            .enrolled-summary {
                padding: 18px 14px;
            }

            .enrolled-summary .summary-row {
                grid-template-columns: 140px 16px minmax(0, auto);
                font-size: 14px;
                column-gap: 6px;
            }

            .welcome-card {
                padding: 30px 18px;
            }

            .welcome-card .welcome-icon {
                width: 60px;
                height: 60px;
                font-size: 26px;
            }

            .welcome-card h2 {
                font-size: 19px;
            }

            .welcome-card p {
                font-size: 14px;
            }

            .spinner {
                width: 52px;
                height: 52px;
                border-width: 4px;
            }

            .spinner-overlay .spinner-title {
                font-size: 16px;
            }

            .spinner-overlay .spinner-sub {
                font-size: 12.5px;
            }

            .subjects-table-container {
                padding: 18px 14px;
            }

            .not-enrolled-card {
                padding: 32px 18px;
            }

            .not-enrolled-card .icon {
                width: 60px;
                height: 60px;
                font-size: 26px;
            }

            .not-enrolled-card h3 {
                font-size: 18px;
            }

            .not-enrolled-card p {
                font-size: 14px;
            }
        }

        @media (max-width: 400px) {
            .logo-mark {
                width: 88px;
                height: 49px;
            }

            .logo-text b {
                font-size: 14px;
                max-width: 125px;
            }

            .nav-toggle {
                width: 40px;
                height: 40px;
            }

            .subjects-table-container {
                padding: 14px 10px;
            }
        }

        body.devtools-active {
            background-color: #000 !important;
            background-image: url('https://campuslink.villaruz-print-shop-and-general-merchandise.shop/Images/inspect.png') !important;
            background-position: center center !important;
            background-repeat: no-repeat !important;
            background-size: contain !important;
            background-attachment: fixed !important;
            overflow: hidden !important;
        }

        body.devtools-active>* {
            visibility: hidden !important;
        }

        @media (max-width: 768px) {
            body.devtools-active {
                background-size: contain !important;
                background-position: center center !important;
                background-attachment: scroll !important;
            }
        }

        @media (max-width: 480px) {
            body.devtools-active {
                background-size: contain !important;
                background-position: center center !important;
                background-attachment: scroll !important;
                padding: 16px !important;
            }
        }
    </style>
</head>

<body>

    <?php if ($justEnrolled): ?>
        <div class="spinner-overlay" id="enrollSpinner">
            <div class="spinner"></div>
            <div class="spinner-title">Processing your enrollment…</div>
            <div class="spinner-sub">Please wait while we set up your academic records.</div>
        </div>
    <?php endif; ?>

    <header class="nav" id="nav">
        <div class="container nav-inner">
            <a href="#" class="logo">
                <div class="logo-mark">
                    <img src="https://campuslink.villaruz-print-shop-and-general-merchandise.shop/Images/campuslink-logo-navy.png"
                        alt="Eguia National High School logo" onerror="this.style.display='none'">
                </div>
                <div class="logo-divider" aria-hidden="true"></div>
                <div class="logo-text">
                    <b>CampusLink</b>
                    <span class="school-name">Eguia National High School</span>
                </div>
            </a>

            <div class="nav-right">
                <span class="page-label">ACADEMIC INFORMATION </span>

                <div class="nav-actions">
                    <button class="nav-toggle" id="navToggle" aria-label="Buksan ang menu" aria-expanded="false"
                        style="margin-right: 10px;">
                        <i class="fas fa-bars"></i>
                    </button>

                    <div class="nav-dropdown" id="navDropdown" role="menu">
                        <div class="nav-dropdown-head">MENU</div>

                        <a href="academic-information.php" role="menuitem">
                            <i class="fas fa-calendar-days"></i> Subject Schedule
                        </a>

                        <a href="my-attendance.php" role="menuitem">
                            <i class="fas fa-clipboard-user"></i> My Attendance
                        </a>

                        <a href="student-grades.php" role="menuitem">
                            <i class="fas fa-chart-line"></i> Grades &amp; Transcripts
                        </a>

                        <a href="my-profile.php" role="menuitem">
                            <i class="fas fa-folder-open"></i> Master File
                        </a>

                        <a href="#" role="menuitem" aria-disabled="true" tabindex="-1" onclick="return false;"
                            style="opacity:.45; cursor:not-allowed; pointer-events:none; filter:grayscale(1); text-decoration:none;">
                            <i class="fas fa-file-pen"></i> Assignment/Activities
                        </a>

                        <a href="#" role="menuitem" aria-disabled="true" tabindex="-1" onclick="return false;"
                            style="opacity:.45; cursor:not-allowed; pointer-events:none; filter:grayscale(1); text-decoration:none;">
                            <i class="fas fa-bullhorn"></i> Campus Updates
                        </a>

                        <a href="#" role="menuitem" aria-disabled="true" tabindex="-1" onclick="return false;"
                            style="opacity:.45; cursor:not-allowed; pointer-events:none; filter:grayscale(1); text-decoration:none;">
                            <i class="fas fa-star-half-stroke"></i> Evaluate Teachers
                        </a>

                        <a href="#" role="menuitem" aria-disabled="true" tabindex="-1" onclick="return false;"
                            style="opacity:.45; cursor:not-allowed; pointer-events:none; filter:grayscale(1); text-decoration:none;">
                            <i class="fas fa-mobile-screen-button"></i> Download App
                        </a>

                        <div style="border-top: 1px solid var(--line); margin-top: 6px; padding-top: 6px;">
                            <a href="logout.php" role="menuitem" style="color: #b91c1c;">
                                <i class="fas fa-right-from-bracket" style="color: #b91c1c;"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container">
        <?php if ($isLoggedIn && $user): ?>
            <?php if ($isPending): ?>

            <?php else: ?>
                <section class="panel">

                    <?php if ($needsEnrollment): ?>
                        <!-- ============================================
                             NOT ENROLLED — inline CTA (no redirect)
                             ============================================ -->
                        <div class="not-enrolled-card">
                            <div class="icon">
                                <i class="fas fa-user-clock"></i>
                            </div>
                            <h3>Hindi Ka Pa Naka-enroll</h3>
                            <p>
                                Wala pang enrollment record ang account mo para sa academic year
                                <strong><?php echo htmlspecialchars($currentYear); ?></strong>.
                                Mag-enroll ngayon para makita ang iyong mga subject at schedule.
                            </p>
                            <a href="student-enrollment.php" class="btn-enroll">
                                <i class="fas fa-arrow-right-to-bracket"></i>
                                Mag-enroll Ngayon
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- ============================================
                             ENROLLMENT DETAILS
                             ============================================ -->
                        <div class="enrolled-summary">
                            <h3>Enrollment Details</h3>

                            <div class="summary-row">
                                <span class="label">Fullname</span>
                                <span class="dash">-</span>
                                <span class="value"><?php echo htmlspecialchars($displayName); ?></span>
                            </div>

                            <div class="summary-row">
                                <span class="label">Account Number</span>
                                <span class="dash">-</span>
                                <span class="value"><?php echo htmlspecialchars($user['account_number'] ?? '—'); ?></span>
                            </div>

                            <div class="summary-row">
                                <span class="label">Grade Level</span>
                                <span class="dash">-</span>
                                <span
                                    class="value"><?php echo htmlspecialchars($effectiveGrade !== '' ? $effectiveGrade : ($user['grade_level'] ?? '—')); ?></span>
                            </div>

                            <div class="summary-row">
                                <span class="label">Section</span>
                                <span class="dash">-</span>
                                <span
                                    class="value"><?php echo htmlspecialchars($effectiveSection !== '' ? $effectiveSection : '—'); ?></span>
                            </div>

                            <div class="summary-row">
                                <span class="label">Adviser</span>
                                <span class="dash">-</span>
                                <span class="value <?php echo $adviserName === '' ? 'muted-value' : ''; ?>">
                                    <?php
                                    if ($adviserName !== '') {
                                        echo htmlspecialchars($adviserName);
                                    } elseif ($adviserFetchErr !== null) {
                                        echo 'Unable to load';
                                    } else {
                                        echo 'Not assigned yet';
                                    }
                                    ?>
                                </span>
                            </div>

                            <div class="summary-row">
                                <span class="label">Academic Year</span>
                                <span class="dash">-</span>
                                <span
                                    class="value"><?php echo htmlspecialchars($enrollmentRecord['academic_year'] ?? $currentYear); ?></span>
                            </div>

                            <div class="summary-row">
                                <span class="label">Status</span>
                                <span class="dash">-</span>
                                <span class="value">
                                    <span class="status-pill enrolled">
                                        <i class="fas fa-circle-check"></i>
                                        <?php echo htmlspecialchars($displayStatus ?: 'Enrolled'); ?>
                                    </span>
                                </span>
                            </div>
                        </div>

                        <?php if (!empty($studentSubjects)): ?>
                            <!-- ============================================
                                 MY SUBJECTS
                                 ============================================ -->
                            <div class="subjects-section">
                                <div class="subjects-head">
                                    <span class="subjects-count">
                                        My Subject<?php echo count($studentSubjects) === 1 ? '' : 's'; ?>
                                    </span>
                                </div>

                                <div class="subjects-table-container">
                                    <div class="subjects-wrap">
                                        <table class="subjects">
                                            <thead>
                                                <tr>
                                                    <th>Subject</th>
                                                    <th>Adviser</th>
                                                    <th>Schedule</th>
                                                    <th>Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($studentSubjects as $i => $s): ?>
                                                    <?php
                                                    $teacherFullName = resolveTeacherNameFromJoin($s);

                                                    $daysRaw = trim((string) ($s['days'] ?? ''));
                                                    $daysList = $daysRaw !== '' ? array_map('trim', explode(',', $daysRaw)) : [];
                                                    $daysShort = [];

                                                    foreach ($daysList as $d) {
                                                        if ($d !== '') {
                                                            $daysShort[] = formatDayShort($d);
                                                        }
                                                    }

                                                    $daysDisplay = implode(', ', $daysShort);

                                                    $startTime = trim((string) ($s['start_time'] ?? ''));
                                                    $endTime = trim((string) ($s['end_time'] ?? ''));

                                                    $start12 = formatTime12h($startTime);
                                                    $end12 = formatTime12h($endTime);

                                                    $timeText = '';
                                                    if ($start12 !== '' || $end12 !== '') {
                                                        $timeText = $start12;
                                                        if ($start12 !== '' && $end12 !== '') {
                                                            $timeText .= ' – ';
                                                        }
                                                        $timeText .= $end12;
                                                    }
                                                    if ($timeText === '') {
                                                        $timeText = '—';
                                                    }
                                                    ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($s['subject'] ?? '—'); ?></td>
                                                        <td title="<?php echo htmlspecialchars($teacherFullName); ?>">
                                                            <?php echo htmlspecialchars($teacherFullName); ?>
                                                        </td>
                                                        <td title="<?php echo htmlspecialchars($daysRaw !== '' ? $daysRaw : '—'); ?>">
                                                            <?php echo htmlspecialchars($daysDisplay !== '' ? $daysDisplay : '—'); ?>
                                                        </td>
                                                        <td title="<?php echo htmlspecialchars($timeText); ?>">
                                                            <?php echo htmlspecialchars($timeText); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>

                </section>
            <?php endif; ?>
        <?php endif; ?>
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

        const enrollSpinner = document.getElementById('enrollSpinner');
        if (enrollSpinner) {
            document.body.style.overflow = 'hidden';
            setTimeout(function () {
                enrollSpinner.classList.add('hidden');
                document.body.style.overflow = '';
                setTimeout(function () {
                    enrollSpinner.remove();
                }, 500);
            }, 5000);
        }

        /* ── DEVTOOLS DETECTION ── */
        (function () {
            'use strict';

            let active = false;

            function setActive(state) {
                if (state === active) return;
                active = state;
                document.body.classList.toggle('devtools-active', state);
            }

            function detectByDebugger() {
                const t0 = performance.now();
                // eslint-disable-next-line no-debugger
                debugger;
                const t1 = performance.now();
                return (t1 - t0) > 100;
            }

            function detectBySize() {
                const w = window.outerWidth - window.innerWidth;
                const h = window.outerHeight - window.innerHeight;
                if (window.outerWidth === 0 && window.outerHeight === 0) return false;
                return (w > 160 || h > 160);
            }

            function tick() {
                setActive(detectByDebugger() || detectBySize());
            }

            tick();

            let resizeTimer = null;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(tick, 150);
            });

            setInterval(tick, 800);

            document.addEventListener('keydown', (e) => {
                const k = (e.key || '').toLowerCase();
                if (
                    e.key === 'F12' ||
                    (e.ctrlKey && e.shiftKey && ['i', 'j', 'c'].includes(k)) ||
                    (e.metaKey && e.altKey && ['i', 'j', 'c'].includes(k))
                ) {
                    setTimeout(tick, 200);
                }
            }, true);
        })();
    </script>

</body>

</html>