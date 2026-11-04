<?php
// teachers/advisory-class.php — Eguia National High School Pages
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
   HELPERS
   ------------------------------------------------------------ */
function formatTime12h(string $time24): string
{
    $time24 = trim($time24);
    if ($time24 === '')
        return '';
    if (!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $time24, $m))
        return $time24;
    $hours = (int) $m[1];
    $minutes = (int) $m[2];
    if ($hours > 23 || $minutes > 59)
        return $time24;
    $suffix = ($hours >= 12) ? 'PM' : 'AM';
    $hours12 = $hours % 12;
    if ($hours12 === 0)
        $hours12 = 12;
    return $hours12 . ':' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) . ' ' . $suffix;
}

function formatDayShort(string $day): string
{
    $d = strtolower(trim($day));
    return match ($d) {
        'monday' => 'M', 'tuesday' => 'T', 'wednesday' => 'W',
        'thursday' => 'Th', 'friday' => 'F', 'saturday' => 'S', 'sunday' => 'Su',
        default => $day,
    };
}

function currentAcademicYear(): string
{
    $y = (int) date('Y');
    return ((int) date('n') < 6) ? (($y - 1) . '-' . $y) : ($y . '-' . ($y + 1));
}

/* ------------------------------------------------------------
   FLASH
   ------------------------------------------------------------ */
$flashError = $_SESSION['flash']['grading_error'] ?? null;
$flashSuccess = $_SESSION['flash']['grading_success'] ?? null;
unset($_SESSION['flash']['grading_error'], $_SESSION['flash']['grading_success']);

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
            $stmt = $pdo->prepare("SELECT * FROM teachers WHERE account_number = ? LIMIT 1");
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
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        header('Location: ../index.php');
        exit;
    }
}

$displayName = '';
if ($user) {
    $displayName = trim(
        ($user['first_name'] ?? '') . ' ' .
        ($user['middle_name'] ?? '') . ' ' .
        ($user['surname'] ?? '')
    );
}

$teacherSchoolName = trim((string) ($user['school_name'] ?? ''));
if ($teacherSchoolName === '') {
    $teacherSchoolName = 'Eguia National High School';
}

$currentYear = currentAcademicYear();

/* ============================================================
   STEP 1 — ALL SUBJECTS THE TEACHER TEACHES
   ============================================================ */
$teacherSubjects = [];
if ($isLoggedIn && $user) {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                CAST(grade_level AS CHAR) AS grade_level,
                section,
                subject,
                CASE WHEN advisory = 1 THEN 1 ELSE 0 END AS is_advisory
            FROM assigned_teachers
            WHERE adviser = ?
              AND subject IS NOT NULL AND subject <> ''
              AND section IS NOT NULL AND section <> ''
              AND grade_level IS NOT NULL
            ORDER BY
                CAST(grade_level AS UNSIGNED) ASC,
                section ASC,
                is_advisory DESC,
                subject ASC
        ");
        $stmt->execute([$user['account_number']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $seen = [];
        foreach ($rows as $r) {
            $key = $r['grade_level'] . '|' . $r['section'] . '|' . $r['subject'];
            if (!isset($seen[$key]) || (int) $r['is_advisory'] === 1) {
                $seen[$key] = $r;
            }
        }
        $teacherSubjects = array_values($seen);
    } catch (PDOException $e) {
        error_log("teacher subjects fetch failed: " . $e->getMessage());
    }
}

/* ============================================================
   STEP 2 — WHICH SUBJECT IS SELECTED?
   ============================================================ */
$selected = null;

$lookupKey = '';
if (!empty($_POST['subject_key'])) {
    $lookupKey = trim((string) $_POST['subject_key']);
} elseif (!empty($_GET['subject_key'])) {
    $lookupKey = trim((string) $_GET['subject_key']);
}

if ($lookupKey !== '') {
    foreach ($teacherSubjects as $ts) {
        $k = $ts['grade_level'] . '|' . $ts['section'] . '|' . $ts['subject'];
        if ($k === $lookupKey) {
            $selected = $ts;
            break;
        }
    }
}

if ($selected === null && !empty($teacherSubjects)) {
    $selected = $teacherSubjects[0];
}

$wcGrade = $selected ? $selected['grade_level'] : '';
$wcSection = $selected ? $selected['section'] : '';
$wcSubject = $selected ? $selected['subject'] : '';
$wcAdvisory = $selected ? (int) $selected['is_advisory'] : 0;
$subjectKey = $selected ? ($wcGrade . '|' . $wcSection . '|' . $wcSubject) : '';

/* ============================================================
   STEP 3 — SAVE GRADES (POST handler) — per quarter
   Reloads the SAME page (same subject) after saving.
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grades'])) {
    /* Build a same-page reload URL that keeps the current subject selected. */
    $saveKey = trim((string) ($_POST['subject_key'] ?? ''));
    $saveParts = explode('|', $saveKey);
    $keyIsValid = (count($saveParts) === 3 && $saveParts[0] !== '' && $saveParts[1] !== '' && $saveParts[2] !== '');

    $reloadUrl = 'update-grades.php';
    if ($keyIsValid) {
        $reloadUrl .= '?subject_key=' . urlencode($saveKey);
    }

    /* CSRF */
    $postedCsrf = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $postedCsrf)) {
        $_SESSION['flash']['grading_error'] = 'Invalid security token. Please try again.';
        header('Location: ' . $reloadUrl);
        exit;
    }

    if (!$keyIsValid) {
        $_SESSION['flash']['grading_error'] = 'Missing or invalid class info (grade, section, subject).';
        header('Location: ' . $reloadUrl);
        exit;
    }

    [$gGrade, $gSection, $gSubject] = $saveParts;

    $allowed = false;
    foreach ($teacherSubjects as $ts) {
        if (
            $ts['grade_level'] === $gGrade
            && $ts['section'] === $gSection
            && $ts['subject'] === $gSubject
        ) {
            $allowed = true;
            break;
        }
    }
    if (!$allowed) {
        $_SESSION['flash']['grading_error'] = 'You are not assigned to this class.';
        header('Location: ' . $reloadUrl);
        exit;
    }

    $grades = $_POST['grades'] ?? [];
    if (!is_array($grades) || empty($grades)) {
        $_SESSION['flash']['grading_error'] = 'No grades submitted.';
        header('Location: ' . $reloadUrl);
        exit;
    }

    $errors = [];

    try {
        $pdo->beginTransaction();

        $upStmt = $pdo->prepare("
            INSERT INTO student_grades
                (account_number, grade_level, section, subject, quarter, grade, adviser, academic_year)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                grade = VALUES(grade),
                adviser = VALUES(adviser),
                updated_at = CURRENT_TIMESTAMP
        ");

        foreach ($grades as $acc => $quarters) {
            $acc = trim((string) $acc);
            if ($acc === '' || !is_array($quarters)) {
                continue;
            }

            for ($q = 1; $q <= 4; $q++) {
                $rawVal = $quarters[$q] ?? '';
                $val = trim((string) $rawVal);

                if ($val === '')
                    continue;

                $num = (float) $val;
                if ($num < 0 || $num > 100) {
                    $errors[] = $acc . ' Q' . $q . ' (must be 0-100)';
                    continue;
                }

                try {
                    $upStmt->execute([
                        $acc,
                        $gGrade,
                        $gSection,
                        $gSubject,
                        $q,
                        $num,
                        $user['account_number'],
                        $currentYear,
                    ]);
                } catch (PDOException $inner) {
                    $errors[] = $acc . ' Q' . $q . ' (' . $inner->getMessage() . ')';
                }
            }
        }

        $pdo->commit();

        if (!empty($errors)) {
            $_SESSION['flash']['grading_error'] =
                'Some grades could not be saved: ' . implode('; ', array_slice($errors, 0, 3));
        } else {
            $_SESSION['flash']['grading_success'] = 'Grades saved successfully.';
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        error_log("grading save failed: " . $e->getMessage());
        $_SESSION['flash']['grading_error'] = 'Could not save grades: ' . $e->getMessage();
    }

    /* Reload the SAME page with the SAME subject selected. */
    header('Location: ' . $reloadUrl);
    exit;
}

/* ============================================================
   STEP 4 — LOAD STUDENTS + EXISTING GRADES (Q1-Q4)
   ============================================================ */
$gradingStudents = [];
$gradingFetchErr = null;
$existingGrades = [];

if ($selected && $wcGrade !== '' && $wcSection !== '') {
    try {
        $stmt = $pdo->prepare("
            SELECT
                es.account_number,
                es.grade_level,
                es.section,
                es.academic_year,
                s.first_name,
                s.middle_name,
                s.surname
            FROM enrolled_students es
            LEFT JOIN students s ON s.account_number = es.account_number
            WHERE CAST(es.grade_level AS CHAR) = ?
              AND es.section = ?
            ORDER BY s.surname ASC, s.first_name ASC
        ");
        $stmt->execute([$wcGrade, $wcSection]);
        $gradingStudents = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (!empty($gradingStudents) && $wcSubject !== '') {
            $stmt = $pdo->prepare("
                SELECT account_number, quarter, grade
                FROM student_grades
                WHERE CAST(grade_level AS CHAR) = ?
                  AND section = ?
                  AND subject = ?
                  AND academic_year = ?
            ");
            $stmt->execute([$wcGrade, $wcSection, $wcSubject, $currentYear]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $key = trim((string) $r['account_number']) . '|' . (int) $r['quarter'];
                $existingGrades[$key] = $r['grade'];
            }
        }
    } catch (PDOException $e) {
        $gradingFetchErr = $e->getMessage();
        error_log("grading students fetch failed: " . $e->getMessage());
    }
}

$totalGradingStudents = count($gradingStudents);
?>
<!DOCTYPE html>
<html lang="fil">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>" />
    <title>Teacher Dashboard — Eguia National High School</title>

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
            margin-top: 30px;
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

        /* FLASH */
        .flash {
            padding: 12px 16px;
            border-radius: var(--radius);
            font-size: 13.5px;
            font-weight: 500;
            margin-top: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .flash.is-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        .flash.is-success {
            background: var(--green-bg);
            border: 1px solid var(--green-line);
            color: var(--green);
        }

        .flash i {
            font-size: 15px;
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

        /* SUBJECT SELECTOR */
        .subject-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            padding: 16px 18px;
            background: linear-gradient(135deg, #f8fafc, #eef2ff);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            margin-bottom: 20px;
        }

        .subject-bar label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }

        .subject-select {
            flex: 1;
            min-width: 200px;
            max-width: 520px;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid var(--line);
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            background: #fff;
            cursor: pointer;
            transition: border-color .15s, box-shadow .15s;
        }

        .subject-select:focus {
            outline: none;
            border-color: var(--primary-2);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .18);
        }

        .subject-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .subject-badge.is-advisory {
            background: #eef2ff;
            color: var(--primary-dark);
            border: 1px solid #c7d2fe;
        }

        .subject-badge.is-subject {
            background: #f1f5f9;
            color: var(--muted);
            border: 1px solid var(--line);
        }

        /* GRADING TABLE */
        .grading-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--line);
            border-radius: var(--radius);
        }

        table.grading {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }

        table.grading thead {
            background: #f1f5f9;
        }

        table.grading th,
        table.grading td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
        }

        table.grading th {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
        }

        table.grading tbody tr:last-child td {
            border-bottom: none;
        }

        table.grading tbody tr:hover {
            background: #f8fafc;
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

        .grade-input {
            width: 72px;
            padding: 7px 6px;
            border-radius: 8px;
            border: 1px solid var(--line);
            font-family: inherit;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text);
            background: #fff;
            text-align: center;
            transition: border-color .15s, box-shadow .15s;
        }

        .grade-input:focus {
            outline: none;
            border-color: var(--primary-2);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .18);
        }

        .btn-save {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #fff;
            font-size: 13.5px;
            font-weight: 700;
            font-family: inherit;
            border: none;
            cursor: pointer;
            box-shadow: 0 8px 18px -10px rgba(37, 99, 235, .85);
            transition: transform .15s, box-shadow .15s;
        }

        .btn-save:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 22px -12px rgba(37, 99, 235, .95);
        }

        .btn-save i {
            font-size: 12.5px;
        }

        .grading-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 16px;
            flex-wrap: wrap;
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

            .section-head {
                margin-bottom: 14px;
            }

            .section-head h2 {
                font-size: 16px;
            }

            table.grading th,
            table.grading td {
                padding: 10px 12px;
                font-size: 13px;
            }

            .grade-input {
                width: 60px;
                font-size: 12.5px;
                padding: 6px 4px;
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
                <span class="page-label">Teacher Portal</span>

                <div class="nav-actions">
                    <button class="nav-toggle" id="navToggle" aria-label="Buksan ang menu" aria-expanded="false">
                        <i class="fas fa-bars"></i>
                    </button>

                    <div class="nav-dropdown" id="navDropdown" role="menu">
                        <div class="nav-dropdown-head">MENU</div>
                        <a href="advisory-class.php" role="menuitem">
                            <i class="fas fa-chalkboard-user"></i> Class Advisory
                        </a>
                        <a href="#" role="menuitem">
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

        <?php if ($flashError !== null): ?>
            <div class="flash is-error" style="margin-top:24px;">
                <i class="fas fa-triangle-exclamation"></i>
                <?php echo htmlspecialchars($flashError); ?>
            </div>
        <?php elseif ($flashSuccess !== null): ?>
            <div class="flash is-success" style="margin-top:24px;">
                <i class="fas fa-circle-check"></i>
                <?php echo htmlspecialchars($flashSuccess); ?>
            </div>
        <?php endif; ?>

        <!-- PAGE HEADER -->
        <div class="page-header">
            <div class="page-header-left">
                <div class="page-header-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="page-header-text">
                    <h1>Class Grading</h1>
                    <div class="meta">
                        <?php if ($selected): ?>
                            <span class="pill"><i class="fas fa-layer-group"></i> Grade
                                <?php echo htmlspecialchars($wcGrade); ?></span>
                            <span class="pill"><i class="fas fa-users"></i>
                                <?php echo htmlspecialchars($wcSection); ?></span>
                            <span class="pill"><i class="fas fa-book"></i>
                                <?php echo htmlspecialchars($wcSubject); ?></span>
                            <span class="pill"><i class="fas fa-calendar-days"></i> S.Y.
                                <?php echo htmlspecialchars($currentYear); ?></span>
                            <span class="pill"><i class="fas fa-user-graduate"></i>
                                <?php echo (int) $totalGradingStudents; ?>
                                student<?php echo $totalGradingStudents === 1 ? '' : 's'; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- GRADING SECTION -->
        <section class="section">

            <?php if (empty($teacherSubjects)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>You have no subjects assigned in <b>assigned_teachers</b>. Grades cannot be entered yet.</p>
                </div>
            <?php else: ?>

                <div class="section-head">
                    <div>
                        <h2>Student Grades</h2>
                        <p>
                            Choose a subject, then enter grades per quarter. The <b>Final</b> column averages whatever
                            quarters are filled.
                        </p>
                    </div>
                    <span class="subjects-count"
                        style="font-size:12px;color:var(--muted);background:#f1f5f9;border:1px solid var(--line);padding:3px 10px;border-radius:999px;font-weight:600;">
                        <?php echo (int) $totalGradingStudents; ?>
                        student<?php echo $totalGradingStudents === 1 ? '' : 's'; ?>
                    </span>
                </div>

                <!-- SUBJECT SELECTOR -->
                <form method="get" class="subject-bar" id="subjectForm">
                    <label for="subject_key">Subject</label>
                    <select name="subject_key" id="subject_key" class="subject-select"
                        onchange="document.getElementById('subjectForm').submit();">
                        <?php foreach ($teacherSubjects as $ts): ?>
                            <?php
                            $k = $ts['grade_level'] . '|' . $ts['section'] . '|' . $ts['subject'];
                            $advisoryTag = ((int) $ts['is_advisory'] === 1) ? '  (Advisory)' : '';
                            $label = sprintf(
                                'Grade %s - %s - %s%s',
                                $ts['grade_level'],
                                $ts['section'],
                                $ts['subject'],
                                $advisoryTag
                            );
                            $isSelected = ($k === $subjectKey);
                            ?>
                            <option value="<?php echo htmlspecialchars($k); ?>" <?php echo $isSelected ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if ($selected): ?>
                        <span class="subject-badge <?php echo $wcAdvisory === 1 ? 'is-advisory' : 'is-subject'; ?>">
                            <?php if ($wcAdvisory === 1): ?>
                                <i class="fas fa-star"></i> Advisory
                            <?php else: ?>
                                <i class="fas fa-book"></i> Subject
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </form>

                <?php if ($gradingFetchErr !== null): ?>
                    <div class="empty-state">
                        <i class="fas fa-triangle-exclamation" style="color:#f59e0b;"></i>
                        <p>Could not load students for grading.</p>
                        <div class="debug">Debug: <?php echo htmlspecialchars($gradingFetchErr); ?></div>
                    </div>
                <?php elseif (empty($gradingStudents)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <p>No students are enrolled in Grade <?php echo htmlspecialchars($wcGrade); ?> -
                            <?php echo htmlspecialchars($wcSection); ?> yet.</p>
                    </div>
                <?php else: ?>
                    <form method="post" id="gradingForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="save_grades" value="1">
                        <input type="hidden" name="subject_key" value="<?php echo htmlspecialchars($subjectKey); ?>">

                        <div class="grading-wrap">
                            <table class="grading">
                                <thead>
                                    <tr>
                                        <th style="width:50px;">#</th>
                                        <th>Account #</th>
                                        <th>Full Name</th>
                                        <th style="width:90px;text-align:center;">Q1</th>
                                        <th style="width:90px;text-align:center;">Q2</th>
                                        <th style="width:90px;text-align:center;">Q3</th>
                                        <th style="width:90px;text-align:center;">Q4</th>
                                        <th style="width:100px;text-align:center;">Final</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($gradingStudents as $i => $st): ?>
                                        <?php
                                        $acc = trim((string) ($st['account_number'] ?? ''));
                                        $fn = trim(
                                            ($st['first_name'] ?? '') . ' ' .
                                            ($st['middle_name'] ?? '') . ' ' .
                                            ($st['surname'] ?? '')
                                        );
                                        if ($fn === '')
                                            $fn = '(Unknown Name)';

                                        $qvals = [];
                                        $sum = 0;
                                        $cnt = 0;
                                        for ($q = 1; $q <= 4; $q++) {
                                            $k = $acc . '|' . $q;
                                            $qvals[$q] = isset($existingGrades[$k]) ? (string) $existingGrades[$k] : '';
                                            if ($qvals[$q] !== '' && is_numeric($qvals[$q])) {
                                                $sum += (float) $qvals[$q];
                                                $cnt++;
                                            }
                                        }
                                        $finalGrade = $cnt > 0 ? number_format($sum / $cnt, 2) : '-';
                                        ?>
                                        <tr data-account="<?php echo htmlspecialchars($acc); ?>">
                                            <td><?php echo $i + 1; ?></td>
                                            <td style="font-family:'Poppins';font-weight:700;color:var(--primary-dark);">
                                                <?php echo htmlspecialchars($acc !== '' ? $acc : '-'); ?>
                                            </td>
                                            <td>
                                                <div class="name-block">
                                                    <span class="main"><?php echo htmlspecialchars($fn); ?></span>
                                                </div>
                                            </td>
                                            <?php for ($q = 1; $q <= 4; $q++): ?>
                                                <td style="text-align:center;">
                                                    <input type="text" inputmode="decimal" autocomplete="off" maxlength="6"
                                                        class="grade-input"
                                                        name="grades[<?php echo htmlspecialchars($acc); ?>][<?php echo $q; ?>]"
                                                        value="<?php echo htmlspecialchars($qvals[$q]); ?>" placeholder="-" <?php echo $acc === '' ? 'disabled' : ''; ?>>
                                                </td>
                                            <?php endfor; ?>
                                            <td class="final-cell" data-final="<?php echo htmlspecialchars($finalGrade); ?>"
                                                style="text-align:center;font-family:'Poppins';font-weight:800;color:var(--primary-dark);">
                                                <?php echo htmlspecialchars($finalGrade); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="grading-actions">
                            <button type="submit" class="btn-save">
                                <i class="fas fa-floppy-disk"></i> Save Grades
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

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

        /* Grade input sanitizer */
        document.querySelectorAll('.grade-input').forEach((input) => {
            input.addEventListener('input', () => {
                let v = input.value;

                v = v.replace(/[^\d.]/g, '');

                const firstDot = v.indexOf('.');
                if (firstDot !== -1) {
                    v = v.slice(0, firstDot + 1) + v.slice(firstDot + 1).replace(/\./g, '');
                }

                const parts = v.split('.');
                let intPart = parts[0] || '';
                let decPart = parts[1] || '';

                if (intPart.length > 3) intPart = intPart.slice(0, 3);
                if (decPart.length > 2) decPart = decPart.slice(0, 2);

                if (intPart !== '' && parseInt(intPart, 10) > 100) intPart = '100';

                if (intPart.length > 1 && intPart[0] === '0') intPart = intPart.replace(/^0+/, '');

                v = intPart;
                if (firstDot !== -1) v += '.' + decPart;

                if (input.value !== v) input.value = v;
            });

            input.addEventListener('paste', () => {
                setTimeout(() => input.dispatchEvent(new Event('input')), 0);
            });
        });

        /* ── Live Final grade recalculation ── */
        function recalcFinal(row) {
            const inputs = row.querySelectorAll('.grade-input');
            let sum = 0, cnt = 0;

            inputs.forEach((inp) => {
                const v = inp.value.trim();
                if (v !== '' && !isNaN(parseFloat(v))) {
                    sum += parseFloat(v);
                    cnt++;
                }
            });

            const cell = row.querySelector('.final-cell');
            if (!cell) return;

            cell.textContent = cnt > 0 ? (sum / cnt).toFixed(2) : '-';
        }

        document.querySelectorAll('tr[data-account]').forEach((row) => {
            if (!row.querySelector('.grade-input')) return;
            row.querySelectorAll('.grade-input').forEach((inp) => {
                inp.addEventListener('input', () => recalcFinal(row));
            });
            recalcFinal(row);
        });

        /* ── Preserve scroll position across POST→reload ── */
        (function () {
            const SCROLL_KEY = 'advisory_class_scroll_y';

            // Restore scroll (if any) on load
            try {
                const y = sessionStorage.getItem(SCROLL_KEY);
                if (y !== null && !isNaN(parseInt(y, 10))) {
                    window.scrollTo(0, parseInt(y, 10));
                    sessionStorage.removeItem(SCROLL_KEY);
                }
            } catch (e) { /* ignore */ }

            // Save scroll right before the grading form submits
            const gradingForm = document.getElementById('gradingForm');
            if (gradingForm) {
                gradingForm.addEventListener('submit', () => {
                    try {
                        sessionStorage.setItem(SCROLL_KEY, String(window.scrollY || window.pageYOffset || 0));
                    } catch (e) { /* ignore */ }
                });
            }
        })();
    </script>
</body>

</html>