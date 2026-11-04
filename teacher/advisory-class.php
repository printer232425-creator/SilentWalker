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

/* ============================================================
   STEP 1 — ADVISORY ROWS ONLY
   ============================================================ */
$advisoryRows = [];

if ($isLoggedIn && $user) {
    try {
        $stmt = $pdo->prepare("
            SELECT grade_level, section, subject
            FROM assigned_teachers
            WHERE adviser = ?
              AND advisory = 1
            ORDER BY grade_level ASC, section ASC, subject ASC
        ");
        $stmt->execute([$user['account_number']]);
        $advisoryRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("advisory fetch failed: " . $e->getMessage());
        $advisoryRows = [];
    }
}

$totalAdvisoryRows = count($advisoryRows);
$hasAdvisory = $totalAdvisoryRows > 0;

/* First advisory row's grade/section/subject for the welcome card */
$wcGrade = '';
$wcSection = '';
$wcSubject = '';
if ($hasAdvisory) {
    $wcGrade = trim((string) ($advisoryRows[0]['grade_level'] ?? ''));
    $wcSection = trim((string) ($advisoryRows[0]['section'] ?? ''));
    $wcSubject = trim((string) ($advisoryRows[0]['subject'] ?? ''));
}

/* ============================================================
   STEP 2 — COUNT ENROLLED STUDENTS IN THE ADVISORY CLASS
   ============================================================ */
$enrolledStudentCount = 0;
$enrolledFetchErr = null;

if ($hasAdvisory && $wcGrade !== '' && $wcSection !== '') {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM enrolled_students
            WHERE grade_level = ?
              AND section = ?
        ");
        $stmt->execute([$wcGrade, $wcSection]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0];
        $enrolledStudentCount = (int) ($row['total'] ?? 0);
    } catch (PDOException $e) {
        $enrolledFetchErr = $e->getMessage();
        error_log("enrolled_student count failed: " . $enrolledFetchErr);
        $enrolledStudentCount = 0;
    }
}

/* ============================================================
   STEP 3 — FETCH NON-ADVISORY ROWS FROM assigned_teachers
   Only rows where advisory != 1 (subject-only assignments).
   Includes days, start_time, end_time.
   ============================================================ */
$assignedSubjects = [];
$assignedFetchErr = null;

if ($isLoggedIn && $user) {
    try {
        $stmt = $pdo->prepare("
            SELECT grade_level, section, subject, days, start_time, end_time
            FROM assigned_teachers
            WHERE adviser = ?
              AND (advisory IS NULL OR advisory <> 1)
            ORDER BY grade_level ASC, section ASC, subject ASC
        ");
        $stmt->execute([$user['account_number']]);
        $assignedSubjects = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        $assignedFetchErr = $e->getMessage();
        error_log("assigned_teachers (non-advisory) fetch failed: " . $assignedFetchErr);
        $assignedSubjects = [];
    }
}

$totalAssigned = count($assignedSubjects);
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

        /* ── NAV ── */
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

        /* ── MAIN ── */
        main {
            padding: 0 0 64px;
        }

        .section {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            padding: 24px;
            margin-top: 40px;
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

        /* ── WELCOME CARD ── */
        .welcome-card {
            text-align: center;
            padding: 44px 28px;
            border-radius: var(--radius);
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border: 1px solid #bfdbfe;
        }

        .welcome-card .welcome-icon {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            margin: 0 auto 22px;
            display: grid;
            place-items: center;
            background: #fff;
            color: var(--primary-dark);
            font-size: 32px;
            box-shadow: 0 12px 26px -14px rgba(37, 99, 235, .55);
        }

        .welcome-card h2 {
            font-size: 24px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 10px;
            line-height: 1.25;
        }

        .welcome-card p {
            font-size: 15px;
            color: #334155;
            max-width: 560px;
            margin: 0 auto 20px;
            line-height: 1.7;
        }

        .welcome-card .account-box {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            background: #fff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            font-size: 14px;
            color: #1e3a8a;
        }

        .welcome-card .account-box b {
            font-family: 'Poppins';
            font-weight: 800;
            letter-spacing: .04em;
        }

        /* ── WELCOME CARD — advisory info (NO border, centered) ── */
        .welcome-advisory {
            margin: 16px auto 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 0;
            background: transparent;
            border: none;
            border-radius: 0;
            font-size: 15px;
            color: #1e3a8a;
            text-align: center;
            width: fit-content;
        }

        .welcome-advisory-row {
            display: grid;
            grid-template-columns: 70px 20px 140px;
            align-items: center;
            column-gap: 0;
            font-size: 15px;
            line-height: 1.7;
            white-space: nowrap;
        }

        .welcome-advisory-row .wa-label {
            font-weight: 600;
            color: #3b82f6;
            text-align: right;
            white-space: nowrap;
            padding-right: 8px;
        }

        .welcome-advisory-row .wa-dash {
            color: #94a3b8;
            font-weight: 600;
            text-align: center;
            justify-self: center;
            width: 100%;
        }

        .welcome-advisory-row .wa-value {
            font-weight: 700;
            color: #1e3a8a;
            font-family: 'Poppins', sans-serif;
            text-align: left;
            white-space: nowrap;
            padding-left: 8px;
        }

        /* Advisory attendance button inside welcome card */
        .welcome-attendance {
            margin-top: 14px;
            padding: 9px 18px;
            font-size: 13.5px;
        }

        /* ── ASSIGNED SUBJECTS TABLE ── */
        .subjects-section {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px dashed var(--line);
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

        /* ── ATTENDANCE BUTTON ── */
        .btn-attendance {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #fff;
            font-size: 12.5px;
            font-weight: 600;
            font-family: inherit;
            box-shadow: 0 6px 14px -8px rgba(37, 99, 235, .85);
            transition: transform .15s, box-shadow .15s;
            white-space: nowrap;
        }

        .btn-attendance:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 18px -10px rgba(37, 99, 235, .95);
        }

        .btn-attendance i {
            font-size: 11.5px;
        }

        .empty-subjects {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
            border: 1px dashed var(--line);
            border-radius: var(--radius);
        }

        .empty-subjects i {
            font-size: 40px;
            color: #cbd5e1;
            margin-bottom: 12px;
            display: block;
        }

        /* ── RESPONSIVE ── */
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
                margin-top: 32px;
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
        }

        @media (max-width: 640px) {
            .container {
                padding: 0 16px;
            }

            .section {
                margin-top: 24px;
                padding: 18px 14px;
                margin-bottom: 18px;
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

            .welcome-card {
                padding: 30px 18px;
            }

            .welcome-card h2 {
                font-size: 20px;
            }

            .welcome-card p {
                font-size: 14px;
            }

            .welcome-card .welcome-icon {
                width: 62px;
                height: 62px;
                font-size: 26px;
                margin-bottom: 18px;
            }

            .welcome-advisory {
                font-size: 14px;
                margin-top: 14px;
            }

            .welcome-advisory-row {
                grid-template-columns: 62px 16px 120px;
                font-size: 14px;
            }

            .welcome-attendance {
                padding: 8px 16px;
                font-size: 13px;
            }

            table.subjects th,
            table.subjects td {
                padding: 10px 12px;
                font-size: 13px;
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

            .welcome-advisory-row {
                grid-template-columns: 56px 14px 110px;
                font-size: 13px;
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
        <?php if ($isLoggedIn && $user): ?>
            <section class="section">

                <!-- WELCOME CARD -->
                <div class="welcome-card">
                    <div class="welcome-icon">
                        <i class="fas fa-chalkboard-user"></i>
                    </div>

                    <h2>Welcome, <?php echo htmlspecialchars($displayName ?: $user['account_number']); ?>!</h2>

                    <p>
                        This dashboard shows the subjects assigned to you. Each row corresponds to a
                        grade level, section, and subject. Reach out to the admin if any assignment
                        looks incorrect.
                    </p>

                    <div class="account-box">
                        <i class="fas fa-id-card"></i>
                        Account No. <b><?php echo htmlspecialchars($user['account_number']); ?></b>
                    </div>

                    <?php if ($hasAdvisory): ?>
                        <div class="welcome-advisory">
                            <div class="welcome-advisory-row">
                                <span class="wa-label">Grade</span>
                                <span class="wa-dash">-</span>
                                <span class="wa-value"><?php echo htmlspecialchars($wcGrade !== '' ? $wcGrade : '—'); ?></span>
                            </div>
                            <div class="welcome-advisory-row">
                                <span class="wa-label">Section</span>
                                <span class="wa-dash">-</span>
                                <span
                                    class="wa-value"><?php echo htmlspecialchars($wcSection !== '' ? $wcSection : '—'); ?></span>
                            </div>
                            <div class="welcome-advisory-row">
                                <span class="wa-label">Subject</span>
                                <span class="wa-dash">-</span>
                                <span
                                    class="wa-value"><?php echo htmlspecialchars($wcSubject !== '' ? $wcSubject : '—'); ?></span>
                            </div>
                            <div class="welcome-advisory-row">
                                <span class="wa-label">Student</span>
                                <span class="wa-dash">-</span>
                                <span class="wa-value"><?php echo $enrolledStudentCount; ?></span>
                            </div>

                            <?php if ($wcGrade !== '' && $wcSection !== ''): ?>
                                <a class="btn-attendance welcome-attendance"
                                   href="class-attendance.php?grade_level=<?php echo urlencode($wcGrade); ?>&section=<?php echo urlencode($wcSection); ?>">
                                    <i class="fas fa-clipboard-check"></i> Attendance
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ============================================================
                     ASSIGNED SUBJECTS
                     Only rows where advisory != 1 (subject-only assignments).
                     Each row has an Attendance button linking to
                     class-attendance.php?grade_level=...&section=...
                     ============================================================ -->
                <div class="subjects-section">
                    <div class="section-head" style="margin-bottom: 14px;">
                        <div>
                            <h2>My Assigned Subjects</h2>
                            <p>Subjects you teach outside your advisory class.</p>
                        </div>
                        <span class="subjects-count">
                            <?php echo $totalAssigned; ?>
                            row<?php echo $totalAssigned === 1 ? '' : 's'; ?>
                        </span>
                    </div>

                    <?php if (empty($assignedSubjects)): ?>
                        <div class="empty-subjects">
                            <i class="fas fa-inbox"></i>
                            <p>No non-advisory subjects assigned. You only have an advisory class.</p>
                        </div>
                    <?php else: ?>
                        <div class="subjects-wrap">
                            <table class="subjects">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Grade Level</th>
                                        <th>Section</th>
                                        <th>Subject</th>
                                        <th>Days</th>
                                        <th>Time</th>
                                        <th>Attendance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignedSubjects as $i => $s): ?>
                                        <?php
                                        $rowGrade = trim((string) ($s['grade_level'] ?? ''));
                                        $rowSection = trim((string) ($s['section'] ?? ''));
                                        $rowSubject = trim((string) ($s['subject'] ?? ''));

                                        // Days: "Monday,Wednesday,Friday" → "M, W, F"
                                        $daysRaw = trim((string) ($s['days'] ?? ''));
                                        $daysList = $daysRaw !== '' ? array_map('trim', explode(',', $daysRaw)) : [];
                                        $daysShort = [];
                                        foreach ($daysList as $d) {
                                            if ($d !== '') {
                                                $daysShort[] = formatDayShort($d);
                                            }
                                        }
                                        $daysDisplay = implode(', ', $daysShort);

                                        // Time: "08:00" -> "8:00 AM"
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
                                            <td><?php echo $i + 1; ?></td>
                                            <td>Grade <?php echo htmlspecialchars($rowGrade !== '' ? $rowGrade : '—'); ?></td>
                                            <td><?php echo htmlspecialchars($rowSection !== '' ? $rowSection : '—'); ?></td>
                                            <td><?php echo htmlspecialchars($rowSubject !== '' ? $rowSubject : '—'); ?></td>
                                            <td title="<?php echo htmlspecialchars($daysRaw !== '' ? $daysRaw : '—'); ?>">
                                                <?php echo htmlspecialchars($daysDisplay !== '' ? $daysDisplay : '—'); ?>
                                            </td>
                                            <td title="<?php echo htmlspecialchars($timeText); ?>">
                                                <?php echo htmlspecialchars($timeText); ?>
                                            </td>
                                            <td>
                                                <?php if ($rowGrade !== '' && $rowSection !== ''): ?>
                                                    <a class="btn-attendance"
                                                        href="class-attendance.php?grade_level=<?php echo urlencode($rowGrade); ?>&section=<?php echo urlencode($rowSection); ?>">
                                                        <i class="fas fa-clipboard-check"></i> Attendance
                                                    </a>
                                                <?php else: ?>
                                                    <span style="color:#94a3b8;font-size:12px;">—</span>
                                                <?php endif; ?>
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
    </script>
</body>

</html>