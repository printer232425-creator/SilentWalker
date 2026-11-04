<?php
// student/my-profile.php
// Shows: Student Info (personal, contact, guardian)
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

        /* STUDENT INFO */
        .student-card {
            display: flex;
            align-items: center;
            gap: 24px;
            padding: 24px 20px;
            border-radius: var(--radius);
            border: 1px solid var(--line);
            background: rgba(8, 40, 95, .72);
            backdrop-filter: blur(0px);
            -webkit-backdrop-filter: blur(0px);
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        /* ID-style photo — portrait aspect, scaled down */
        .student-avatar {
            width: 90px;
            height: 115px;
            border-radius: 8px;
            overflow: hidden;
            border: 3px solid #fff;
            box-shadow: 0 8px 20px -8px rgba(15, 23, 42, .35);
            flex-shrink: 0;
            background: #e2e8f0;
            display: grid;
            place-items: center;
        }

        .student-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
            display: block;
        }

        .student-avatar .avatar-fallback {
            font-size: 34px;
            color: #94a3b8;
        }

        .student-meta {
            min-width: 0;
            flex: 1;
        }

        .student-meta h2 {
            font-size: 22px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 4px;
            word-break: break-word;
        }

        .student-meta .account {
            font-size: 13.5px;
            color: var(--muted);
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .student-meta .account i {
            color: var(--primary);
            font-size: 12px;
        }

        /* INFO GRID */
        .info-section {
            margin-bottom: 24px;
        }

        .info-section:last-child {
            margin-bottom: 0;
        }

        .info-section h3 {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-section h3 i {
            color: var(--primary);
            font-size: 13px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1px;
            background: var(--line);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .info-item {
            background: #fff;
            padding: 12px 16px;
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
            position: relative;
        }

        .info-item.full {
            grid-column: 1 / -1;
        }

        .info-item .info-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #94a3b8;
        }

        .info-item .info-value {
            font-size: 14.5px;
            font-weight: 600;
            color: var(--text);
            word-break: break-word;
            line-height: 1.4;
            cursor: pointer;
            border-radius: 4px;
            transition: background .15s ease, box-shadow .15s ease;
            padding: 2px 4px;
            margin: -2px -4px;
            min-height: 1.4em;
        }

        .info-item .info-value:hover {
            background: #eef2ff;
        }

        .info-item .info-value.empty {
            color: #cbd5e1;
            font-weight: 400;
            font-style: italic;
        }

        /* Pencil hint */
        .info-item .edit-hint {
            position: absolute;
            top: 8px;
            right: 10px;
            font-size: 10.5px;
            color: #94a3b8;
            opacity: 0;
            pointer-events: none;
            transition: opacity .15s ease;
        }

        .info-item:hover .edit-hint {
            opacity: 1;
        }

        /* Inline input */
        .info-item .inline-input {
            width: 100%;
            font: inherit;
            font-size: 14.5px;
            font-weight: 600;
            color: var(--text);
            padding: 2px 6px;
            margin: -2px -6px;
            border: 1px solid var(--primary-2);
            border-radius: 4px;
            background: #fff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .info-item.saving .info-value {
            opacity: .55;
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 10002;
            background: #0f172a;
            color: #fff;
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 500;
            box-shadow: 0 15px 30px -10px rgba(0, 0, 0, .4);
            opacity: 0;
            transform: translateY(10px);
            transition: opacity .2s ease, transform .2s ease;
            max-width: 320px;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast.error {
            background: #b91c1c;
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

        /* Fix for the toast text */
        .toast,
        .toast * {
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

        /* INFO BOXES BACKGROUND & BORDERS */
        .student-card {
            background: rgba(8, 40, 95, .72);
            border-color: rgba(255, 255, 255, .3);
        }

        .info-grid {
            background: rgba(255, 255, 255, .15);
            border-color: rgba(255, 255, 255, .2);
        }

        .info-item {
            background: rgba(8, 40, 95, .72);
        }

        .info-item .info-label {
            color: rgba(255, 255, 255, 0.6) !important;
        }

        .info-item .info-value {
            color: #ffffff !important;
        }

        .info-item .info-value.empty {
            color: rgba(255, 255, 255, 0.4) !important;
        }

        .info-item .edit-hint {
            color: rgba(255, 255, 255, 0.6) !important;
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

            .student-card {
                gap: 16px;
                padding: 18px 14px;
            }

            .student-avatar {
                width: 74px;
                height: 94px;
                border-radius: 6px;
                border-width: 2px;
            }

            .student-avatar .avatar-fallback {
                font-size: 28px;
            }

            .student-meta h2 {
                font-size: 18px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .info-item {
                padding: 10px 14px;
            }

            .info-item .info-value {
                font-size: 14px;
            }

            .toast {
                left: 16px;
                right: 16px;
                bottom: 16px;
                max-width: none;
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
                    <img src="https://campuslink.villaruz-print-shop-and-general-merchandise.shop/campuslink-logo-navy.png"
                        alt="Eguia National High School logo" onerror="this.style.display='none'">
                </div>
                <div class="logo-divider" aria-hidden="true"></div>
                <div class="logo-text">
                    <b>CampusLink</b>
                    <span class="school-name">Eguia National High School</span>
                </div>
            </a>

            <div class="nav-right">
                <span class="page-label">MASTER FILE (INFORMATION)</span>

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

                <!-- ================= STUDENT CARD ================= -->
                <div class="student-card">
                    <div class="student-avatar">
                        <?php
                        $profileFile = trim((string) ($user['profile_account'] ?? ''));

                        $hasProfile = $profileFile !== ''
                            && strcasecmp($profileFile, 'profile-image.jpg') !== 0;

                        $absProfile = __DIR__ . '/../Students_Profile/' . basename($profileFile);
                        $fileExists = $hasProfile && is_file($absProfile);

                        $profileUrl = $fileExists
                            ? '../Students_Profile/' . rawurlencode($profileFile)
                            : '';
                        ?>
                        <?php if ($profileUrl !== ''): ?>
                            <img src="<?php echo htmlspecialchars($profileUrl); ?>"
                                alt="<?php echo htmlspecialchars($displayName); ?>"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                            <div class="avatar-fallback" style="display:none;"><i class="fas fa-user"></i></div>
                        <?php else: ?>
                            <div class="avatar-fallback"><i class="fas fa-user"></i></div>
                        <?php endif; ?>
                    </div>

                    <div class="student-meta">
                        <h2><?php echo htmlspecialchars($displayName !== '' ? $displayName : 'Student'); ?></h2>
                        <span class="account">
                            <i class="fas fa-id-card"></i>
                            <?php echo htmlspecialchars($user['account_number'] ?? '—'); ?>
                        </span>
                        <span class="account">
                            <i class="fas fa-key"></i>
                            <?php echo htmlspecialchars($user['pin_text'] ?? '—'); ?>
                        </span>
                    </div>
                </div>

                <!-- PERSONAL INFORMATION -->
                <div class="info-section">
                    <h3><i class="fas fa-user"></i> Personal Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">First Name</span>
                            <i class="fas fa-pencil edit-hint"></i>
                            <span class="info-value"
                                data-field="first_name"><?php echo htmlspecialchars($user['first_name'] ?? '—'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Middle Name</span>
                            <i class="fas fa-pencil edit-hint"></i>
                            <span class="info-value"
                                data-field="middle_name"><?php echo htmlspecialchars($user['middle_name'] ?? '—'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Surname</span>
                            <i class="fas fa-pencil edit-hint"></i>
                            <span class="info-value"
                                data-field="surname"><?php echo htmlspecialchars($user['surname'] ?? '—'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Age</span>
                            <i class="fas fa-pencil edit-hint"></i>
                            <span class="info-value <?php echo empty($user['age']) ? 'empty' : ''; ?>" data-field="age">
                                <?php echo !empty($user['age']) ? htmlspecialchars((string) $user['age']) : 'Not provided'; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Sex</span>
                            <i class="fas fa-pencil edit-hint"></i>
                            <span class="info-value <?php echo empty($user['sex']) ? 'empty' : ''; ?>" data-field="sex">
                                <?php echo !empty($user['sex']) ? htmlspecialchars($user['sex']) : 'Not provided'; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Birth Date</span>
                            <i class="fas fa-pencil edit-hint"></i>
                            <span class="info-value <?php echo empty($user['birth_date']) ? 'empty' : ''; ?>"
                                data-field="birth_date">
                                <?php echo !empty($user['birth_date']) ? htmlspecialchars($user['birth_date']) : 'Not provided'; ?>
                            </span>
                        </div>

                        <div class="info-item">
                            <span class="info-label">Contact Number</span>
                            <i class="fas fa-pencil edit-hint"></i>
                            <span class="info-value <?php echo empty($user['contact_number']) ? 'empty' : ''; ?>"
                                data-field="contact_number">
                                <?php echo !empty($user['contact_number']) ? htmlspecialchars($user['contact_number']) : 'Not provided'; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Birth Place</span>
                            <i class="fas fa-pencil edit-hint"></i>
                            <span class="info-value <?php echo empty($user['birth_place']) ? 'empty' : ''; ?>"
                                data-field="birth_place">
                                <?php echo !empty($user['birth_place']) ? htmlspecialchars($user['birth_place']) : 'Not provided'; ?>
                            </span>
                        </div>

                        <div class="info-item full">
                            <span class="info-label">Address</span>
                            <i class="fas fa-pencil edit-hint"></i>
                            <span class="info-value <?php echo empty($user['address']) ? 'empty' : ''; ?>"
                                data-field="address">
                                <?php echo !empty($user['address']) ? htmlspecialchars($user['address']) : 'Not provided'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- CONTACT & GUARDIAN -->
                <div class="info-section">
                    <h3><i class="fas fa-address-book"></i> Contact &amp; Guardian</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Guardian</span>
                            <i class="fas fa-pencil edit-hint"></i>
                            <span class="info-value <?php echo empty($user['guardian']) ? 'empty' : ''; ?>"
                                data-field="guardian">
                                <?php echo !empty($user['guardian']) ? htmlspecialchars($user['guardian']) : 'Not provided'; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Guardian Contact Number</span>
                            <i class="fas fa-pencil edit-hint"></i>
                            <span class="info-value <?php echo empty($user['guardian_contact_number']) ? 'empty' : ''; ?>"
                                data-field="guardian_contact_number">
                                <?php echo !empty($user['guardian_contact_number']) ? htmlspecialchars($user['guardian_contact_number']) : 'Not provided'; ?>
                            </span>
                        </div>
                    </div>
                </div>

            </section>
        <?php endif; ?>
    </main>

    <div class="toast" id="editToast" role="status" aria-live="polite"></div>

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

        /* ── INLINE EDIT ── */
        const editToast = document.getElementById('editToast');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let toastTimer = null;
        function showToast(message, isError = false) {
            if (!editToast) return;
            editToast.textContent = message;
            editToast.className = 'toast show' + (isError ? ' error' : '');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => {
                editToast.classList.remove('show');
            }, 2600);
        }

        /* Keep track of which field is currently being edited so we can
           auto-commit it when the user taps/clicks on another field */
        let activeEditor = null; // { valueEl, item, input, current, finish }

        function startEdit(valueEl) {
            if (!valueEl || valueEl.dataset.editing === '1') return;

            /* If another field is currently open, commit it first */
            if (activeEditor) {
                activeEditor.finish(true);
            }

            const field = valueEl.dataset.field;
            if (!field) return;

            const item = valueEl.closest('.info-item');
            const current = valueEl.classList.contains('empty') ? '' : valueEl.textContent.trim();

            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'inline-input';
            input.value = current;
            input.setAttribute('data-field', field);

            valueEl.dataset.editing = '1';
            valueEl.style.display = 'none';
            item.appendChild(input);
            input.focus();
            input.select();

            let committed = false;

            const finish = (save) => {
                if (committed) return;
                committed = true;

                const newValue = input.value.trim();

                /* Detach from global tracker */
                if (activeEditor && activeEditor.valueEl === valueEl) {
                    activeEditor = null;
                }

                if (!save || newValue === current) {
                    input.remove();
                    valueEl.style.display = '';
                    delete valueEl.dataset.editing;
                    return;
                }

                item.classList.add('saving');
                valueEl.textContent = newValue || 'Not provided';
                valueEl.classList.toggle('empty', newValue === '');
                valueEl.style.display = '';
                input.remove();

                fetch('API_STUDENT/student-info.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                    body: new URLSearchParams({
                        csrf_token: csrfToken,
                        field: field,
                        value: newValue,
                    }),
                })
                    .then(r => r.json())
                    .then(data => {
                        item.classList.remove('saving');
                        if (data.success) {
                            if (typeof data.value === 'string') {
                                valueEl.textContent = data.value || 'Not provided';
                                valueEl.classList.toggle('empty', data.value === '');
                            }
                            showToast('Saved.');
                        } else {
                            valueEl.textContent = current || 'Not provided';
                            valueEl.classList.toggle('empty', current === '');
                            showToast(data.message || 'Could not save.', true);
                        }
                    })
                    .catch(() => {
                        item.classList.remove('saving');
                        valueEl.textContent = current || 'Not provided';
                        valueEl.classList.toggle('empty', current === '');
                        showToast('Network error. Try again.', true);
                    })
                    .finally(() => {
                        delete valueEl.dataset.editing;
                    });
            };

            activeEditor = { valueEl, item, input, current, finish };

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    finish(true);
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    finish(false);
                }
            });

            input.addEventListener('blur', () => {
                finish(true);
            });
        }

        /* Wire up dblclick (desktop) + double-tap (mobile) */
        document.querySelectorAll('.info-value[data-field]').forEach((el) => {
            el.addEventListener('dblclick', (e) => {
                e.preventDefault();
                startEdit(el);
            });

            let lastTap = 0;
            el.addEventListener('touchend', (e) => {
                const now = Date.now();
                if (now - lastTap < 320) {
                    e.preventDefault();
                    startEdit(el);
                    lastTap = 0;
                } else {
                    lastTap = now;
                }
            });
        });

        /* Global tap/click outside the input → commit (acts as "tap the other data") */
        document.addEventListener('pointerdown', (e) => {
            if (!activeEditor) return;
            if (e.target === activeEditor.input) return;
            activeEditor.finish(true);
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