<?php
// student/academic-information.php
// Shows: Enrollment Details (with adviser) + My Subjects
// Redirects to student-enrollment.php if the student has no enrollment record.
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

    return trim((string) ($row['adviser'] ?? ''));
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
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    } else {
        $isLoggedIn = true;
    }
}

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
   NO RECORD AT ALL → redirect to the enrollment form
   ------------------------------------------------------------ */
if ($isLoggedIn && $user && !$enrollmentRecord) {
    header('Location: student-enrollment.php');
    exit;
}

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
   - enrolled_students.status == 'Pending'
   - OR students.enrollment_status == 'Pending'
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
   FETCH ADVISER (advisory = 1)
   ------------------------------------------------------------ */
$adviserName = '';
$adviserAccount = '';
$adviserFetchErr = null;

if (
    $isLoggedIn
    && $user
    && $assignmentTable !== null
    && $effectiveGrade !== ''
    && $effectiveSection !== ''
) {
    try {
        $sql = "
            SELECT
                at.adviser,
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
                url('Colorful_Back_to_School_Stationery_Border.png');
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

        /* NAV - CAMPUS LINK STYLE */
        .nav {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #08285f;
            border-bottom: 0;
            /* Allow the dropdown to extend below the navbar without being clipped. */
            overflow: visible;
            isolation: isolate;
        }

        .nav::before,
        .nav::after {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            width: 250px;
            z-index: -1;
            pointer-events: none;
        }

        /* Left diagonal ribbon */
        .nav::before {
            left: -90px;
            background:
                linear-gradient(55deg,
                    transparent 0 24%,
                    #9b1536 24% 34%,
                    #f8c324 34% 42%,
                    #2058c8 42% 56%,
                    transparent 56% 100%);
        }

        /* Right diagonal ribbon */
        .nav::after {
            right: -90px;
            transform: scaleX(-1);
            background:
                linear-gradient(55deg,
                    transparent 0 24%,
                    #9b1536 24% 34%,
                    #f8c324 34% 42%,
                    #2058c8 42% 56%,
                    transparent 56% 100%);
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
            width: 112px;
            height: 82px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
        }

        .logo-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            /* Gives the existing blue logo the gold look of the reference header. */
            filter: brightness(0) saturate(100%)
                    invert(77%) sepia(90%) saturate(1070%)
                    hue-rotate(355deg) brightness(103%) contrast(102%);
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
            /* Remove the large middle card/container while keeping its contents. */
            background: transparent;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            padding: 0;
            margin: 30px 0 60px;
        }

        /* CHANGED: Welcome card (Pending / New Student / Transferee) */
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
            background: linear-gradient(135deg, #f8fafc, #eef2ff);
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
            color: #f5f6f7;
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
            font-size: 12.5px;
            font-weight: 700;
            letter-spacing: .02em;
            line-height: 1.6;
        }

        .status-pill.pending {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .status-pill.enrolled {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
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
            color: var(--text);
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
            background: #f1f5f9;
            border: 1px solid var(--line);
            padding: 3px 10px;
            border-radius: 999px;
            font-weight: 600;
        }

        .subjects-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            -webkit-overflow-scrolling: touch;
        }

        table.subjects {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        table.subjects thead {
            background: #f1f5f9;
        }

        table.subjects th,
        table.subjects td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        table.subjects th {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
        }

        table.subjects tbody tr:last-child td {
            border-bottom: none;
        }

        table.subjects tbody tr:hover {
            background: #f8fafc;
        }

        .empty-subjects {
            text-align: center;
            padding: 30px 16px;
            color: var(--muted);
            font-size: 14px;
        }

        .empty-subjects i {
            font-size: 28px;
            color: #f8f9fa;
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
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
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

        /* MAKE ALL PAGE TEXT WHITE */
        body,
        body :where(h1, h2, h3, h4, h5, h6, p, span, div, a, button, label, strong, b, small, th, td, li, i, em, code) {
            color: #ffffff !important;
        }

        /* Keep the dropdown readable while using white text. */
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

        .enrolled-summary {
            background: rgba(8, 40, 95, .72);
            border-color: rgba(255, 255, 255, .3);
        }

        .subjects-wrap {
            border-color: rgba(255, 255, 255, .3);
        }

        table.subjects thead,
        table.subjects tbody tr:hover {
            background: rgba(8, 40, 95, .72);
        }

        table.subjects th,
        table.subjects td {
            border-bottom-color: rgba(255, 255, 255, .25);
        }

        .subjects-count,
        .welcome-card .account-box {
            background: rgba(8, 40, 95, .78);
            border-color: rgba(255, 255, 255, .3);
        }

        .status-pill.pending,
        .status-pill.enrolled {
            color: #ffffff !important;
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
                width: 92px;
                height: 70px;
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
                width: 70px;
                height: 58px;
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
                width: 52px;
                height: 46px;
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
        }

        @media (max-width: 400px) {
            .nav::before,
            .nav::after {
                width: 150px;
            }

            .logo-mark {
                width: 44px;
                height: 40px;
            }

            .logo-text b {
                font-size: 14px;
                max-width: 125px;
            }

            .nav-toggle {
                width: 40px;
                height: 40px;
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
                    <img src="campuslink-logo.png"
                        alt="Eguia National High School logo" onerror="this.style.display='none'">
                </div>
                <div class="logo-divider" aria-hidden="true"></div>
                <div class="logo-text">
                    <b>Campus Link</b>
                    <span class="school-name">Eguia National High School</span>
                </div>
            </a>

            <div class="nav-right">
                <span class="page-label">Student Portal</span>

                <div class="nav-actions">
                    <button class="nav-toggle" id="navToggle" aria-label="Buksan ang menu" aria-expanded="false">
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
                <?php /* CHANGED: Pending state → show welcome card only, hide enrollment details + subjects */ ?>
                <section class="panel">
                    <div class="welcome-card is-closed">
                        <div class="welcome-icon"
                            style="color: #b45309; background: #fef3c7; box-shadow: 0 12px 26px -14px rgba(245, 158, 11, 0.45);">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <h2>Enrollment Pending — Visit the Registrar's Office</h2>
                        <p>
                            Your enrollment request has been submitted and is currently
                            <strong>pending approval</strong>. To complete your enrollment,
                            please proceed to the <strong>Registrar's Office</strong> and bring
                            your required documents for verification and approval.
                        </p>
                        <div class="account-box">
                            <i class="fas fa-id-card"></i>
                            Account No. <b><?php echo htmlspecialchars($user['account_number']); ?></b>
                        </div>
                    </div>
                </section>
            <?php else: ?>
                <section class="panel">

                    <!-- ENROLLMENT DETAILS -->
                    <div class="enrolled-summary">
                        <h3>Enrollment Details</h3>

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

                    <!-- MY SUBJECTS -->
                    <div class="subjects-section">
                        <div class="subjects-head">
                            <h3>
                                <i class="fas fa-book-open"></i>
                                My Subjects
                            </h3>
                            <span class="subjects-count">
                                <?php echo count($studentSubjects); ?>
                                subject<?php echo count($studentSubjects) === 1 ? '' : 's'; ?>
                            </span>
                        </div>

                        <?php if (empty($studentSubjects)): ?>
                            <div class="empty-subjects">
                                <i class="fas fa-inbox"></i>
                                <p>No subjects have been assigned yet. Please check back later.</p>
                                <?php if ($subjectsFetchErr !== null): ?>
                                    <span class="debug">Query error: <?php echo htmlspecialchars($subjectsFetchErr); ?></span>
                                <?php elseif ($assignmentTable === null): ?>
                                    <span class="debug">No assignment table found (`assigned_teachers` / `assinged_teachers`).</span>
                                <?php elseif ($effectiveGrade === '' || $effectiveSection === ''): ?>
                                    <span class="debug">Missing grade_level or section for this student.</span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
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
                        <?php endif; ?>
                    </div>

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
    </script>
</body>

</html>