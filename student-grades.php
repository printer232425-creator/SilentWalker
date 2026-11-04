<?php
// student/student-grades.php
// Shows: Grades for the logged-in student — grouped by subject
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
   DISPLAY NAME
   ------------------------------------------------------------ */
$displayName = '';
if ($user) {
    $displayName = trim(
        ($user['first_name'] ?? '') . ' ' .
        ($user['middle_name'] ?? '') . ' ' .
        ($user['surname'] ?? '')
    );
}

/* ------------------------------------------------------------
   HELPER: resolve teacher full name from account_number
   ------------------------------------------------------------ */
function resolveAdviserName(PDO $pdo, string $adviserAccount, array &$cache): string
{
    $adviserAccount = trim($adviserAccount);
    if ($adviserAccount === '') {
        return '—';
    }

    if (array_key_exists($adviserAccount, $cache)) {
        return $cache[$adviserAccount];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT first_name, middle_name, surname
            FROM teachers
            WHERE account_number = ?
            LIMIT 1
        ");
        $stmt->execute([$adviserAccount]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$row) {
            $cache[$adviserAccount] = $adviserAccount;
            return $cache[$adviserAccount];
        }

        $parts = array_filter(
            [
                trim((string) ($row['first_name'] ?? '')),
                trim((string) ($row['middle_name'] ?? '')),
                trim((string) ($row['surname'] ?? '')),
            ],
            fn($p) => $p !== ''
        );
        $name = implode(' ', $parts);
        $cache[$adviserAccount] = $name !== '' ? $name : $adviserAccount;
    } catch (PDOException $e) {
        error_log("resolveAdviserName failed: " . $e->getMessage());
        $cache[$adviserAccount] = $adviserAccount;
    }

    return $cache[$adviserAccount];
}

function gradeRemarks($avg): array
{
    if ($avg === null) {
        return ['label' => '—', 'class' => 'default'];
    }
    $n = (float) $avg;
    if ($n >= 90)
        return ['label' => 'Outstanding', 'class' => 'excellent'];
    if ($n >= 85)
        return ['label' => 'Very Satisfactory', 'class' => 'very-good'];
    if ($n >= 80)
        return ['label' => 'Satisfactory', 'class' => 'good'];
    if ($n >= 75)
        return ['label' => 'Fairly Satisfactory', 'class' => 'fair'];
    return ['label' => 'Did Not Meet Expectations', 'class' => 'poor'];
}

/* ------------------------------------------------------------
   FETCH ALL GRADES FOR THIS STUDENT
   ------------------------------------------------------------ */
$studentGrades = [];
$gradesFetchErr = null;

if ($isLoggedIn && $user) {
    try {
        $stmt = $pdo->prepare("
            SELECT
                id,
                account_number,
                grade_level,
                section,
                subject,
                quarter,
                grade,
                adviser,
                academic_year
            FROM student_grades
            WHERE account_number = ?
            ORDER BY academic_year DESC, subject ASC, quarter ASC
        ");
        $stmt->execute([$user['account_number']]);
        $studentGrades = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        $gradesFetchErr = $e->getMessage();
        error_log("grades fetch failed: " . $gradesFetchErr);
        $studentGrades = [];
    }
}

/* ------------------------------------------------------------
   GROUP BY academic_year → subject (grade_level|section|subject)
   Within each subject: quarters map [1..4] => grade
   ------------------------------------------------------------ */
$groupedByYear = [];   // [ay] => [subjectKey] => data
$adviserNameCache = [];

foreach ($studentGrades as $row) {
    $ay = trim((string) ($row['academic_year'] ?? '')) ?: '—';
    $gradeLv = trim((string) ($row['grade_level'] ?? ''));
    $sect = trim((string) ($row['section'] ?? ''));
    $subj = trim((string) ($row['subject'] ?? '')) ?: 'Unassigned Subject';
    $q = (int) ($row['quarter'] ?? 0);
    $gVal = $row['grade'];
    $advAcc = trim((string) ($row['adviser'] ?? ''));
    $advName = resolveAdviserName($pdo, $advAcc, $adviserNameCache);

    $subjectKey = $gradeLv . '|' . $sect . '|' . $subj;

    if (!isset($groupedByYear[$ay])) {
        $groupedByYear[$ay] = [];
    }
    if (!isset($groupedByYear[$ay][$subjectKey])) {
        $groupedByYear[$ay][$subjectKey] = [
            'subject' => $subj,
            'grade_level' => $gradeLv,
            'section' => $sect,
            'adviser' => $advName,
            'quarters' => [1 => null, 2 => null, 3 => null, 4 => null],
        ];
    }

    if ($q >= 1 && $q <= 4 && $gVal !== null && $gVal !== '') {
        $groupedByYear[$ay][$subjectKey]['quarters'][$q] = (float) $gVal;
    }
}

/* Compute average per subject */
foreach ($groupedByYear as $ay => &$subjects) {
    foreach ($subjects as &$data) {
        $filled = array_filter($data['quarters'], fn($v) => $v !== null);
        $data['average'] = count($filled) > 0 ? array_sum($filled) / count($filled) : null;
        $data['filled_count'] = count($filled);
    }
    unset($data);
}
unset($subjects);

/* Sort subjects alphabetically within each year */
foreach ($groupedByYear as $ay => &$subjects) {
    uasort($subjects, fn($a, $b) => strcasecmp($a['subject'], $b['subject']));
}
unset($subjects);

$totalSubjects = 0;
foreach ($groupedByYear as $subjects) {
    $totalSubjects += count($subjects);
}
?>
<!DOCTYPE html>
<html lang="fil">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>" />
    <title>Grades &amp; Transcripts — Eguia National High School</title>
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
            --green: #047857;
            --green-bg: #ecfdf5;
            --green-line: #a7f3d0;
            --red: #b91c1c;
            --red-bg: #fef2f2;
            --red-line: #fecaca;
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

        /* ── PAGE BACKGROUND ── */
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
        
         /* =========================================================
           FIXED BACKGROUND IMAGE (Does not scroll)
           ========================================================= */
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
            
            /* Desktop Background */
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

        /* ── NAV ── */
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

        /* ── PANEL ── */
        .panel {
            background: rgba(8, 40, 95, .72);
            border: 1px solid rgba(255, 255, 255, .3);
            border-radius: var(--radius);
            box-shadow: 0 14px 34px -14px rgba(0, 0, 0, .35);
            backdrop-filter: blur(0px);
            -webkit-backdrop-filter: blur(0px);
            padding: 28px;
            margin: 30px 0 60px;
        }

        /* PAGE HEAD */
        .page-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px dashed rgba(255, 255, 255, .3);
            flex-wrap: wrap;
        }

        .page-head h2 {
            font-size: 20px;
            font-weight: 800;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-head h2 i {
            color: #fbbf24;
            font-size: 18px;
        }

        .page-head .sub {
            font-size: 13px;
            color: rgba(255, 255, 255, .75);
            display: block;
            margin-top: 2px;
        }

        .count-pill {
            font-size: 12px;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 999px;
            background: rgba(8, 40, 95, .78);
            border: 1px solid rgba(255, 255, 255, .3);
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .count-pill i {
            font-size: 11px;
        }

        /* ── ACADEMIC YEAR SECTION ── */
        .ay-section {
            margin-bottom: 28px;
        }

        .ay-section:last-child {
            margin-bottom: 0;
        }

        .ay-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
            flex-wrap: wrap;
            padding: 12px 16px;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 8px;
        }

        .ay-header h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 800;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 9px;
            letter-spacing: .02em;
        }

        .ay-header h3 i {
            color: #fbbf24;
            font-size: 13px;
        }

        .ay-header .ay-subject-count {
            font-size: 11.5px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 999px;
            background: rgba(8, 40, 95, .78);
            border: 1px solid rgba(255, 255, 255, .3);
            color: #fff;
        }

        /* ── SUBJECT CARD GRID ── */
        .subject-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 16px;
        }

        .subject-card {
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 10px;
            padding: 16px;
            transition: border-color .15s, background .15s;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .subject-card:hover {
            border-color: rgba(255, 255, 255, .4);
            background: rgba(255, 255, 255, .09);
        }

        .subject-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 12px;
            border-bottom: 1px dashed rgba(255, 255, 255, .22);
        }

        .subject-title {
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            font-weight: 800;
            color: #fff;
            line-height: 1.3;
            word-break: break-word;
        }

        .subject-meta {
            font-size: 11.5px;
            color: rgba(255, 255, 255, .7);
            margin-top: 4px;
            display: flex;
            flex-wrap: wrap;
            gap: 3px 12px;
        }

        .subject-meta span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .subject-meta i {
            font-size: 10px;
            color: rgba(255, 255, 255, .55);
        }

        /* Average badge */
        .avg-badge {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-weight: 800;
            line-height: 1;
            flex-shrink: 0;
            min-width: 76px;
            border: 1.5px solid rgba(255, 255, 255, .3);
            background: rgba(255, 255, 255, .1);
        }

        .avg-badge .avg-num {
            font-size: 20px;
            color: #fff;
        }

        .avg-badge .avg-label {
            font-size: 9.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(255, 255, 255, .75);
            margin-top: 3px;
        }

        .avg-badge.excellent {
            background: rgba(52, 211, 153, .22);
            border-color: rgba(52, 211, 153, .7);
        }

        .avg-badge.very-good {
            background: rgba(96, 165, 250, .22);
            border-color: rgba(96, 165, 250, .7);
        }

        .avg-badge.good {
            background: rgba(147, 197, 253, .22);
            border-color: rgba(147, 197, 253, .7);
        }

        .avg-badge.fair {
            background: rgba(251, 191, 36, .22);
            border-color: rgba(251, 191, 36, .7);
        }

        .avg-badge.poor {
            background: rgba(248, 113, 113, .22);
            border-color: rgba(248, 113, 113, .7);
        }

        .avg-badge.default {
            background: rgba(255, 255, 255, .1);
            border-color: rgba(255, 255, 255, .3);
        }

        /* Quarter rows */
        .quarters-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .quarter-row {
            display: grid;
            grid-template-columns: 44px 1fr auto;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 7px;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .1);
        }

        .quarter-label {
            font-family: 'Poppins', sans-serif;
            font-size: 11.5px;
            font-weight: 800;
            color: #fbbf24;
            text-align: center;
            letter-spacing: .04em;
        }

        .quarter-bar-wrap {
            height: 8px;
            background: rgba(255, 255, 255, .12);
            border-radius: 4px;
            overflow: hidden;
            position: relative;
            min-width: 50px;
        }

        .quarter-bar {
            height: 100%;
            border-radius: 4px;
            transition: width .35s ease;
        }

        .quarter-bar.excellent {
            background: linear-gradient(90deg, #34d399, #10b981);
        }

        .quarter-bar.very-good {
            background: linear-gradient(90deg, #60a5fa, #3b82f6);
        }

        .quarter-bar.good {
            background: linear-gradient(90deg, #93c5fd, #60a5fa);
        }

        .quarter-bar.fair {
            background: linear-gradient(90deg, #fcd34d, #f59e0b);
        }

        .quarter-bar.poor {
            background: linear-gradient(90deg, #fca5a5, #ef4444);
        }

        .quarter-bar.default {
            background: rgba(255, 255, 255, .25);
        }

        .quarter-value {
            font-family: 'Poppins', sans-serif;
            font-size: 13.5px;
            font-weight: 800;
            color: #fff;
            min-width: 48px;
            text-align: right;
        }

        .quarter-value.empty {
            color: rgba(255, 255, 255, .35);
            font-weight: 500;
            font-style: italic;
        }

        /* Subject footer: teacher */
        .subject-footer {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-top: 10px;
            border-top: 1px dashed rgba(255, 255, 255, .22);
            font-size: 11.5px;
            color: rgba(255, 255, 255, .75);
        }

        .subject-footer i {
            color: rgba(255, 255, 255, .55);
            font-size: 11px;
        }

        .subject-footer b {
            color: #fff;
            font-weight: 700;
        }

        /* EMPTY */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, .85);
            font-size: 14px;
            border: 1px dashed rgba(255, 255, 255, .3);
            border-radius: var(--radius);
        }

        .empty-state i {
            font-size: 40px;
            color: rgba(255, 255, 255, .4);
            margin-bottom: 12px;
            display: block;
        }

        .empty-state .debug {
            display: block;
            margin-top: 10px;
            font-size: 11.5px;
            color: #fca5a5;
            font-family: monospace;
            word-break: break-all;
        }

        /* GLOBAL WHITE TEXT */
        body,
        body :where(h1, h2, h3, h4, h5, h6, p, span, div, a, button, label, strong, b, small, th, td, li, i, em, code) {
            color: #ffffff !important;
        }

        /* Nav dropdown readable */
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
                padding: 20px 16px;
                margin: 20px 0 40px;
            }

            .page-head h2 {
                font-size: 17px;
            }

            .subject-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 400px) {

            .nav::before,
            .nav::after {
                width: 150px;
            }

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
        }

        /* ── DEVTOOLS ACTIVE ── */
        body.devtools-active {
            overflow: hidden !important;
            background: #000 !important;
        }

        body.devtools-active>* {
            visibility: hidden !important;
        }

        body.devtools-active::before {
            content: "" !important;
            position: fixed !important;
            inset: 0 !important;
            top: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            left: 0 !important;
            z-index: 2147483647 !important;
            background-color: #000 !important;
            background-image: url('https://campuslink.villaruz-print-shop-and-general-merchandise.shop/Images/inspect.png') !important;
            background-repeat: no-repeat !important;
            background-position: 50% 50% !important;
            background-size: contain !important;
            pointer-events: none !important;
            visibility: visible !important;
        }
    </style>
</head>

<body>

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
                <span class="page-label">MY GRADES</span>

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
            <section class="panel">

                <!-- PAGE HEAD -->
                <div class="page-head">
                    <div>
                        <h2><i class="fas fa-chart-line"></i> My Grades</h2>
                        <span class="sub">
                            <?php echo htmlspecialchars($displayName !== '' ? $displayName : 'Student'); ?>
                            &middot; <?php echo htmlspecialchars($user['account_number'] ?? '—'); ?>
                        </span>
                    </div>

                    <?php if ($totalSubjects > 0): ?>
                        <span class="count-pill">
                            <i class="fas fa-book-open"></i>
                            <?php echo $totalSubjects; ?>
                            subject<?php echo $totalSubjects === 1 ? '' : 's'; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($gradesFetchErr !== null): ?>
                    <div class="empty-state">
                        <i class="fas fa-triangle-exclamation"></i>
                        <p>Unable to load your grades right now. Please try again later.</p>
                        <span class="debug"><?php echo htmlspecialchars($gradesFetchErr); ?></span>
                    </div>

                <?php elseif (empty($groupedByYear)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No grades have been posted yet. Please check back later.</p>
                    </div>

                <?php else: ?>

                    <!-- ACADEMIC YEARS → SUBJECTS -->
                    <?php foreach ($groupedByYear as $ay => $subjects): ?>
                        <div class="ay-section">
                            <div class="ay-header">
                                <h3>
                                    <i class="fas fa-calendar-days"></i>
                                    S.Y. <?php echo htmlspecialchars($ay); ?>
                                </h3>
                                <span class="ay-subject-count">
                                    <?php echo count($subjects); ?>
                                    subject<?php echo count($subjects) === 1 ? '' : 's'; ?>
                                </span>
                            </div>

                            <div class="subject-grid">
                                <?php foreach ($subjects as $data): ?>
                                    <?php
                                    $avg = $data['average'];
                                    $remarks = gradeRemarks($avg);
                                    $avgDisplay = $avg !== null ? number_format($avg, 2) : '—';
                                    ?>
                                    <div class="subject-card">
                                        <div class="subject-top">
                                            <div style="min-width:0;">
                                                <div class="subject-title"><?php echo htmlspecialchars($data['subject']); ?></div>
                                                <div class="subject-meta">
                                                    <?php if ($data['grade_level'] !== ''): ?>
                                                        <span><i class="fas fa-graduation-cap"></i> Grade
                                                            <?php echo htmlspecialchars($data['grade_level']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($data['section'] !== ''): ?>
                                                        <span><i class="fas fa-users"></i>
                                                            <?php echo htmlspecialchars($data['section']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="avg-badge <?php echo $remarks['class']; ?>"
                                                title="<?php echo htmlspecialchars($remarks['label']); ?>">
                                                <span class="avg-num"><?php echo htmlspecialchars($avgDisplay); ?></span>
                                                <span class="avg-label">Avg</span>
                                            </div>
                                        </div>

                                        <div class="quarters-list">
                                            <?php for ($q = 1; $q <= 4; $q++): ?>
                                                <?php
                                                $qv = $data['quarters'][$q];
                                                $isFilled = $qv !== null;
                                                $qRem = $isFilled ? gradeRemarks($qv) : ['class' => 'default', 'label' => ''];
                                                $qPct = $isFilled ? max(0, min(100, (float) $qv)) : 0;
                                                ?>
                                                <div class="quarter-row">
                                                    <div class="quarter-label">Q<?php echo $q; ?></div>
                                                    <div class="quarter-bar-wrap">
                                                        <div class="quarter-bar <?php echo $qRem['class']; ?>"
                                                            style="width: <?php echo $isFilled ? $qPct : 0; ?>%;"></div>
                                                    </div>
                                                    <div class="quarter-value <?php echo $isFilled ? '' : 'empty'; ?>">
                                                        <?php echo $isFilled ? htmlspecialchars(number_format($qv, 2)) : '—'; ?>
                                                    </div>
                                                </div>
                                            <?php endfor; ?>
                                        </div>

                                        <div class="subject-footer">
                                            <i class="fas fa-user-tie"></i>
                                            <span>Teacher:</span>
                                            <b><?php echo htmlspecialchars($data['adviser']); ?></b>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>

            </section>
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
            if (navDropdown && !navDropdown.contains(e.target) && navToggle && e.target !== navToggle) {
                navDropdown.classList.remove('open');
                navToggle.setAttribute('aria-expanded', 'false');
            }
        });

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