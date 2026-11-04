<?php
// student/my-attendance.php
// Shows: Attendance calendar per subject for the logged-in student
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
   FETCH ALL ATTENDANCE RECORDS FOR THIS STUDENT
   ------------------------------------------------------------ */
$attendanceRows = [];
$attendanceErr = null;
$totalRecords = 0;

if ($isLoggedIn && $user) {
    try {
        try {
            $stmt = $pdo->prepare("
                SELECT id, account_number, date_today, time_start, time_end,
                       grade_level, section, subject, status, adviser
                FROM attendance_list
                WHERE account_number = ?
                ORDER BY id DESC
            ");
            $stmt->execute([$user['account_number']]);
            $attendanceRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $inner) {
            $stmt = $pdo->prepare("
                SELECT id, account_number, date_today, grade_level, section, subject, status, adviser
                FROM attendance_list
                WHERE account_number = ?
                ORDER BY id DESC
            ");
            $stmt->execute([$user['account_number']]);
            $attendanceRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        $totalRecords = count($attendanceRows);
    } catch (PDOException $e) {
        $attendanceErr = $e->getMessage();
        error_log("attendance fetch failed: " . $attendanceErr);
    }
}

/* ------------------------------------------------------------
   HELPERS
   ------------------------------------------------------------ */
function resolveAdviserName(PDO $pdo, string $adviserAccount, string $fallback = ''): string
{
    $adviserAccount = trim($adviserAccount);
    if ($adviserAccount === '') {
        return $fallback;
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
            return $fallback !== '' ? $fallback : $adviserAccount;
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
        return $name !== '' ? $name : ($fallback !== '' ? $fallback : $adviserAccount);
    } catch (PDOException $e) {
        error_log("resolveAdviserName failed: " . $e->getMessage());
        return $fallback !== '' ? $fallback : $adviserAccount;
    }
}

$adviserNameCache = [];
function adviserNameCached(PDO $pdo, array &$cache, string $adviserAccount, string $fallback = ''): string
{
    $key = trim($adviserAccount) . '|' . $fallback;
    if (!isset($cache[$key])) {
        $cache[$key] = resolveAdviserName($pdo, $adviserAccount, $fallback);
    }
    return $cache[$key];
}

/**
 * Returns one of: 'present', 'late', 'absent', 'excused'.
 * Unknown/empty statuses return '' (no color/class).
 */
function statusKey(string $status): string
{
    $s = strtolower(trim($status));
    return match ($s) {
        'present', 'on time', 'ontime' => 'present',
        'absent' => 'absent',
        'late', 'tardy' => 'late',
        'excused', 'excuse' => 'excused',
        default => '',
    };
}

function statusLabel(string $status): string
{
    $s = trim($status);
    if ($s === '')
        return '';
    $lower = strtolower($s);
    $map = [
        'present' => 'Present',
        'on time' => 'On Time',
        'ontime' => 'On Time',
        'absent' => 'Absent',
        'late' => 'Late',
        'tardy' => 'Late',
        'excused' => 'Excused',
        'excuse' => 'Excused',
    ];
    return $map[$lower] ?? ucfirst($lower);
}

function extractDatePart(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '')
        return '';

    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $raw, $m)) {
        return $m[1];
    }

    $ts = strtotime($raw);
    return $ts !== false ? date('Y-m-d', $ts) : '';
}

function formatTimePart(string $timeRaw): string
{
    $timeRaw = trim($timeRaw);
    if ($timeRaw === '')
        return '';

    if (preg_match('/^\d{1,2}:\d{2}\s*(AM|PM|am|pm)$/i', $timeRaw)) {
        $ts = strtotime($timeRaw);
        return $ts !== false ? date('g:iA', $ts) : $timeRaw;
    }
    if (preg_match('/^\d{1,2}:\d{2}(?::\d{2})?$/', $timeRaw)) {
        $ts = strtotime('1970-01-01 ' . $timeRaw);
        return $ts !== false ? date('g:iA', $ts) : $timeRaw;
    }
    if (preg_match('/^\d{1,2}$/', $timeRaw)) {
        $ts = strtotime('1970-01-01 ' . $timeRaw . ':00');
        return $ts !== false ? date('g:iA', $ts) : $timeRaw;
    }
    return $timeRaw;
}

/* ------------------------------------------------------------
   GROUP ATTENDANCE BY SUBJECT
   Skips any row without a real subject, date, or status.
   ------------------------------------------------------------ */
$groupedBySubject = [];

foreach ($attendanceRows as $row) {
    $subject = trim((string) ($row['subject'] ?? ''));
    $status = trim((string) ($row['status'] ?? ''));
    $dateKey = extractDatePart((string) ($row['date_today'] ?? ''));

    // Skip rows missing essential data (no fake defaults)
    if ($subject === '' || $dateKey === '' || $status === '') {
        continue;
    }

    $grade = trim((string) ($row['grade_level'] ?? ''));
    $section = trim((string) ($row['section'] ?? ''));

    $rawAdviser = trim((string) ($row['adviser'] ?? ''));
    $adviserName = $rawAdviser !== ''
        ? adviserNameCached($pdo, $adviserNameCache, $rawAdviser, '')
        : '';

    $startRaw = trim((string) ($row['time_start'] ?? ''));
    $endRaw = trim((string) ($row['time_end'] ?? ''));
    $startFmt = formatTimePart($startRaw);
    $endFmt = formatTimePart($endRaw);
    $timeDisplay = '';
    if ($startFmt !== '' || $endFmt !== '') {
        $timeDisplay = $startFmt . ($startFmt !== '' && $endFmt !== '' ? ' – ' : '') . $endFmt;
    }

    $key = strtolower($subject . '|' . $grade . '|' . $section);
    if (!isset($groupedBySubject[$key])) {
        $groupedBySubject[$key] = [
            'subject' => $subject,
            'grade_level' => $grade,
            'section' => $section,
            'adviser' => $adviserName,
            'time_display' => $timeDisplay,
            'records' => [],
            'earliest' => null,
            'latest' => null,
        ];
    }

    $groupedBySubject[$key]['records'][] = [
        'id' => (int) ($row['id'] ?? 0),
        'date' => $dateKey,
        'status' => $status,
        'status_key' => statusKey($status),
        'status_label' => statusLabel($status),
        'time_display' => $timeDisplay,
    ];

    $ts = strtotime($dateKey);
    if ($ts !== false) {
        if ($groupedBySubject[$key]['earliest'] === null || $ts < $groupedBySubject[$key]['earliest']) {
            $groupedBySubject[$key]['earliest'] = $ts;
        }
        if ($groupedBySubject[$key]['latest'] === null || $ts > $groupedBySubject[$key]['latest']) {
            $groupedBySubject[$key]['latest'] = $ts;
        }
    }
}

/* Only keep subjects that actually have at least one valid record */
$groupedBySubject = array_filter($groupedBySubject, fn($g) => !empty($g['records']));

/* Sort subjects alphabetically */
uasort($groupedBySubject, fn($a, $b) => strcasecmp($a['subject'], $b['subject']));

$totalSubjects = count($groupedBySubject);
?>
<!DOCTYPE html>
<html lang="fil">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>" />
    <title>My Attendance — Eguia National High School</title>
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
            --red: #b91c1c;
            --amber: #b45309;
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

        /* ── LEGEND ── */
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 18px;
            padding: 12px 16px;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 12.5px;
        }

        .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #fff;
            font-weight: 500;
        }

        .legend-dot {
            width: 14px;
            height: 14px;
            border-radius: 4px;
            display: inline-block;
            flex-shrink: 0;
        }

        .legend-dot.present {
            background: #34d399;
        }

        .legend-dot.absent {
            background: #f87171;
        }

        .legend-dot.late {
            background: #fbbf24;
        }

        .legend-dot.excused {
            background: #93c5fd;
        }

        /* ── SUBJECT CALENDAR CARDS ── */
        .subject-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 20px;
        }

        .subject-card {
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 10px;
            padding: 16px;
            transition: border-color .15s;
        }

        .subject-card:hover {
            border-color: rgba(255, 255, 255, .4);
        }

        .subject-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 1px dashed rgba(255, 255, 255, .25);
        }

        .subject-title {
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            font-weight: 800;
            color: #fff;
            line-height: 1.25;
            word-break: break-word;
        }

        .subject-meta {
            font-size: 12px;
            color: rgba(255, 255, 255, .75);
            margin-top: 3px;
            display: flex;
            flex-wrap: wrap;
            gap: 4px 12px;
        }

        .subject-meta span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .subject-meta i {
            font-size: 10.5px;
            color: rgba(255, 255, 255, .6);
        }

        .subject-count {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .25);
            color: #fff;
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* ── MONTH NAV (prev/next buttons) ── */
        .month-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
        }

        .month-nav-btn {
            display: grid;
            place-items: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, .3);
            background: rgba(255, 255, 255, .1);
            color: #fff;
            font-size: 13px;
            cursor: pointer;
            transition: background .15s, border-color .15s, opacity .15s;
            flex-shrink: 0;
        }

        .month-nav-btn:hover:not(:disabled) {
            background: rgba(255, 255, 255, .22);
            border-color: rgba(255, 255, 255, .5);
        }

        .month-nav-btn:disabled {
            opacity: .3;
            cursor: not-allowed;
        }

        .month-nav-title {
            text-align: center;
            font-family: 'Poppins', sans-serif;
            font-size: 12.5px;
            font-weight: 700;
            color: #fff;
            letter-spacing: .04em;
            text-transform: uppercase;
            flex: 1;
        }

        /* ── CALENDAR ── */
        .calendar-holder {
            display: flex;
            justify-content: center;
        }

        .calendar {
            width: 100%;
            max-width: 320px;
        }

        .cal-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 3px;
            margin-bottom: 4px;
        }

        .cal-weekday {
            text-align: center;
            font-size: 10px;
            font-weight: 700;
            color: rgba(255, 255, 255, .55);
            padding: 3px 0;
            text-transform: uppercase;
        }

        .cal-week {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 3px;
            margin-bottom: 3px;
        }

        .cal-day {
            aspect-ratio: 1 / 1;
            display: grid;
            place-items: center;
            border-radius: 5px;
            font-size: 11.5px;
            font-weight: 600;
            color: rgba(255, 255, 255, .35);
            background: rgba(255, 255, 255, .03);
            border: 1px solid transparent;
            position: relative;
            cursor: default;
            transition: transform .12s;
        }

        .cal-day.blank {
            visibility: hidden;
        }

        .cal-day.has-record {
            cursor: pointer;
            color: #fff;
            border-color: rgba(255, 255, 255, .25);
        }

        .cal-day.has-record:hover {
            transform: scale(1.08);
            z-index: 2;
        }

        .cal-day.present {
            background: rgba(52, 211, 153, .35);
            border-color: rgba(52, 211, 153, .7);
        }

        .cal-day.absent {
            background: rgba(248, 113, 113, .35);
            border-color: rgba(248, 113, 113, .7);
        }

        .cal-day.late {
            background: rgba(251, 191, 36, .35);
            border-color: rgba(251, 191, 36, .7);
        }

        .cal-day.excused {
            background: rgba(147, 197, 253, .35);
            border-color: rgba(147, 197, 253, .7);
        }

        .cal-day .tooltip {
            position: absolute;
            bottom: calc(100% + 6px);
            left: 50%;
            transform: translateX(-50%);
            background: #0b1a3a;
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            padding: 6px 10px;
            border-radius: 6px;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            visibility: hidden;
            transition: opacity .15s, visibility .15s;
            z-index: 10;
            border: 1px solid rgba(255, 255, 255, .2);
            box-shadow: 0 8px 20px -8px rgba(0, 0, 0, .6);
        }

        .cal-day .tooltip::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #0b1a3a;
        }

        .cal-day:hover .tooltip {
            opacity: 1;
            visibility: visible;
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
            background-image: url('https://campuslink.villaruz-print-shop-and-general-merchandise.shop/inspect.png') !important;
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
                <span class="page-label">MY ATTENDANCE</span>

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
                        <h2><i class="fas fa-clipboard-user"></i> My Attendance</h2>
                        <span class="sub">
                            <?php echo htmlspecialchars($displayName !== '' ? $displayName : 'Student'); ?>
                            &middot; <?php echo htmlspecialchars($user['account_number'] ?? '—'); ?>
                        </span>
                    </div>

                    <?php if ($totalRecords > 0): ?>
                        <span class="count-pill">
                            <i class="fas fa-list-check"></i>
                            <?php echo $totalRecords; ?>
                            record<?php echo $totalRecords === 1 ? '' : 's'; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($attendanceErr !== null): ?>
                    <div class="empty-state">
                        <i class="fas fa-triangle-exclamation"></i>
                        <p>Unable to load your attendance records right now. Please try again later.</p>
                        <span class="debug"><?php echo htmlspecialchars($attendanceErr); ?></span>
                    </div>

                <?php elseif (empty($groupedBySubject)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No attendance records found yet.</p>
                    </div>

                <?php else: ?>
                    <!-- LEGEND -->
                    <div class="legend">
                        <span class="legend-item"><span class="legend-dot present"></span> Present / On Time</span>
                        <span class="legend-item"><span class="legend-dot absent"></span> Absent</span>
                        <span class="legend-item"><span class="legend-dot late"></span> Late</span>
                        <span class="legend-item"><span class="legend-dot excused"></span> Excused</span>
                    </div>

                    <!-- CALENDARS PER SUBJECT -->
                    <div class="subject-grid">
                        <?php foreach ($groupedBySubject as $key => $group): ?>
                            <?php
                            $subject = $group['subject'];
                            $gradeLvl = $group['grade_level'];
                            $sect = $group['section'];
                            $advName = $group['adviser'];
                            $timeDisp = $group['time_display'];

                            $recordsByDate = [];
                            foreach ($group['records'] as $rec) {
                                $recordsByDate[$rec['date']] = $rec;
                            }

                            $recordsForJs = [];
                            foreach ($recordsByDate as $d => $r) {
                                $recordsForJs[$d] = [
                                    'status_key' => $r['status_key'],
                                    'status_label' => $r['status_label'],
                                    'time_display' => $r['time_display'],
                                ];
                            }
                            $recordsJson = json_encode($recordsForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                            $earliest = $group['earliest'];
                            $latest = $group['latest'];

                            // No default fallback: initial month is always derived from a real record
                            $initTs = $latest !== null ? $latest : $earliest;
                            $initYear = $initTs !== null ? (int) date('Y', $initTs) : 0;
                            $initMonth = $initTs !== null ? (int) date('n', $initTs) : 0;

                            $recordCount = count($group['records']);
                            ?>
                            <div class="subject-card" data-subject-card
                                data-earliest="<?php echo $earliest !== null ? $earliest : ''; ?>"
                                data-latest="<?php echo $latest !== null ? $latest : ''; ?>"
                                data-init-year="<?php echo $initYear; ?>" data-init-month="<?php echo $initMonth; ?>"
                                data-records="<?php echo htmlspecialchars($recordsJson, ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="subject-head">
                                    <div>
                                        <div class="subject-title"><?php echo htmlspecialchars($subject); ?></div>
                                        <div class="subject-meta">
                                            <?php if ($gradeLvl !== ''): ?>
                                                <span><i class="fas fa-graduation-cap"></i> Grade
                                                    <?php echo htmlspecialchars($gradeLvl); ?></span>
                                            <?php endif; ?>
                                            <?php if ($sect !== ''): ?>
                                                <span><i class="fas fa-users"></i> <?php echo htmlspecialchars($sect); ?></span>
                                            <?php endif; ?>
                                            <?php if ($timeDisp !== ''): ?>
                                                <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($timeDisp); ?></span>
                                            <?php endif; ?>
                                            <?php if ($advName !== ''): ?>
                                                <span><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($advName); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="subject-count"><?php echo $recordCount; ?></span>
                                </div>

                                <div class="month-nav">
                                    <button type="button" class="month-nav-btn" data-nav="prev" aria-label="Previous month">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <div class="month-nav-title" data-month-label>&nbsp;</div>
                                    <button type="button" class="month-nav-btn" data-nav="next" aria-label="Next month">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>

                                <div class="calendar-holder">
                                    <div class="calendar" data-calendar></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
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

        /* ============================================================
           MONTH-NAV CALENDARS (one month per subject, prev/next buttons)
           ============================================================ */
        (function () {
            'use strict';

            const MONTH_NAMES = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            const WEEKDAYS = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
            const VALID_STATUS_KEYS = new Set(['present', 'late', 'absent', 'excused']);

            function pad2(n) { return String(n).padStart(2, '0'); }

            function daysInMonth(year, month) {
                return new Date(year, month, 0).getDate();
            }

            function firstWeekdayOfMonth(year, month) {
                return new Date(year, month - 1, 1).getDay();
            }

            function monthIndex(year, month) {
                return year * 12 + (month - 1);
            }

            function monthFromIndex(idx) {
                const year = Math.floor(idx / 12);
                const month = (idx % 12) + 1;
                return { year, month };
            }

            function escapeHtml(s) {
                return String(s == null ? '' : s)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function renderMonth(calEl, year, month, records) {
                const totalDays = daysInMonth(year, month);
                const startWeekday = firstWeekdayOfMonth(year, month);

                let html = '<div class="cal-weekdays">';
                WEEKDAYS.forEach(d => {
                    html += '<div class="cal-weekday">' + d + '</div>';
                });
                html += '</div>';

                const cells = [];
                for (let i = 0; i < startWeekday; i++) cells.push(null);
                for (let d = 1; d <= totalDays; d++) cells.push(d);
                while (cells.length % 7 !== 0) cells.push(null);

                for (let i = 0; i < cells.length; i += 7) {
                    html += '<div class="cal-week">';
                    for (let j = 0; j < 7; j++) {
                        const dayNum = cells[i + j];
                        if (dayNum === null) {
                            html += '<div class="cal-day blank"></div>';
                            continue;
                        }

                        const dateStr = year + '-' + pad2(month) + '-' + pad2(dayNum);
                        const rec = records[dateStr];
                        let cls = 'cal-day';
                        let tip = '';
                        if (rec && VALID_STATUS_KEYS.has(rec.status_key)) {
                            cls += ' has-record ' + rec.status_key;
                            tip = rec.status_label || '';
                            if (rec.time_display) {
                                tip += (tip ? ' · ' : '') + rec.time_display;
                            }
                        }

                        html += '<div class="' + cls + '">' + dayNum;
                        if (rec && VALID_STATUS_KEYS.has(rec.status_key)) {
                            html += '<span class="tooltip">' + escapeHtml(tip) + '</span>';
                        }
                        html += '</div>';
                    }
                    html += '</div>';
                }

                calEl.innerHTML = html;
            }

            function initCard(card) {
                const calEl = card.querySelector('[data-calendar]');
                const labelEl = card.querySelector('[data-month-label]');
                const prevBtn = card.querySelector('[data-nav="prev"]');
                const nextBtn = card.querySelector('[data-nav="next"]');

                if (!calEl || !labelEl) return;

                let records = {};
                try {
                    records = JSON.parse(card.getAttribute('data-records') || '{}');
                } catch (e) {
                    records = {};
                }

                // Determine min/max month index from real records only
                let minIdx = null;
                let maxIdx = null;
                Object.keys(records).forEach(d => {
                    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(d);
                    if (!m) return;
                    const idx = monthIndex(parseInt(m[1], 10), parseInt(m[2], 10));
                    if (minIdx === null || idx < minIdx) minIdx = idx;
                    if (maxIdx === null || idx > maxIdx) maxIdx = idx;
                });

                // No default: require a real month from data
                if (minIdx === null || maxIdx === null) {
                    calEl.innerHTML = '';
                    labelEl.textContent = '';
                    if (prevBtn) prevBtn.disabled = true;
                    if (nextBtn) nextBtn.disabled = true;
                    return;
                }

                const initYear = parseInt(card.getAttribute('data-init-year') || '0', 10);
                const initMonth = parseInt(card.getAttribute('data-init-month') || '0', 10);
                let curIdx = (initYear && initMonth) ? monthIndex(initYear, initMonth) : maxIdx;

                if (curIdx < minIdx) curIdx = minIdx;
                if (curIdx > maxIdx) curIdx = maxIdx;

                function update() {
                    const { year, month } = monthFromIndex(curIdx);
                    labelEl.textContent = MONTH_NAMES[month - 1] + ' ' + year;
                    renderMonth(calEl, year, month, records);

                    if (prevBtn) prevBtn.disabled = (curIdx <= minIdx);
                    if (nextBtn) nextBtn.disabled = (curIdx >= maxIdx);
                }

                prevBtn?.addEventListener('click', () => {
                    if (curIdx <= minIdx) return;
                    curIdx--;
                    update();
                });

                nextBtn?.addEventListener('click', () => {
                    if (curIdx >= maxIdx) return;
                    curIdx++;
                    update();
                });

                update();
            }

            document.querySelectorAll('[data-subject-card]').forEach(initCard);
        })();

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