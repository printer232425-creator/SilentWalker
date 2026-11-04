<?php
// /student-enrollment.php — Eguia National High School Pages

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
   READ FLASH FIRST — then clear
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
    if ((int) date('n') < 6) {
        return ($y - 1) . '-' . $y;
    }
    return $y . '-' . ($y + 1);
}
$currentYear = currentAcademicYear();

/* ------------------------------------------------------------
   SECTION OPTIONS
   ------------------------------------------------------------ */
$sectionOptions = [
    'Ilang-Ilang',
    'Dahlia',
    'Rosal',
    'Sampaguita',
    'Acacia',
    'Yakal',
    'Molave',
    'Mahogany',
    'Mabini',
    'Rizal',
    'Jacinto',
    'Bonifacio',
    'Emerald',
    'Diamond',
    'Jade',
    'Home Economics - Pasteur',
    'Einstein',
    'Socrates',
    'Franklin',
    'Automotive Servicing',
    'TVL - HE',
    'STEM',
    'Humanities & Social Science'
];

$sectionOptionsByGrade = [
    '7' => ['Ilang-Ilang', 'Dahlia', 'Rosal', 'Sampaguita'],
    '8' => ['Acacia', 'Yakal', 'Molave', 'Mahogany'],
    '9' => ['Mabini', 'Rizal', 'Jacinto', 'Bonifacio'],
    '10' => ['Emerald', 'Diamond', 'Jade'],
    '11' => ['Home Economics - Pasteur', 'Einstein', 'Socrates', 'Franklin'],
    '12' => ['Automotive Servicing', 'TVL - HE', 'STEM', 'Humanities & Social Science'],
];

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
    }

    $isLoggedIn = true;
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
$displayName = trim(
    ($user['first_name'] ?? '') . ' ' .
    ($user['middle_name'] ?? '') . ' ' .
    ($user['surname'] ?? '')
);

/* ------------------------------------------------------------
   ENROLLMENT STATUS & ACADEMIC STATUS CHECK
   ------------------------------------------------------------ */
$enrollmentStatus = strtolower(trim((string) ($user['enrollment_status'] ?? 'not enrolled')));
$studentType = strtoupper(trim((string) ($user['student_type'] ?? '')));
$enrollmentAccess = (int) ($user['enrollment_access'] ?? 0);

$isPending = ($enrollmentStatus === 'pending');
$isEnrolled = ($enrollmentStatus === 'enrolled');
$canEnroll = (!$isPending && !$isEnrolled && $enrollmentAccess === 1);
$isClosed = (!$isPending && !$isEnrolled && $enrollmentAccess === 0);

/* ------------------------------------------------------------
   If the student is already fully enrolled, redirect away.
   ------------------------------------------------------------ */
if ($isEnrolled) {
    header('Location: academic-information.php');
    exit;
}

$isTransferee = ($studentType === 'T');
$isNewStudent = ($studentType === 'N');
$isOldStudent = ($studentType === 'O');

/* ------------------------------------------------------------
   RESOLVE THE STUDENT'S GRADE FOR THE SECTION PICKER
   ------------------------------------------------------------ */
$userGrade = trim((string) ($user['grade_level'] ?? ''));
$sectionsForGrade = $sectionOptionsByGrade[$userGrade] ?? $sectionOptions;

/* ------------------------------------------------------------
   FLASH FLAGS
   ------------------------------------------------------------ */
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
            height: 100%;
            background-color: #0a1a33;
        }

        html,
        body {
            width: 100%;
            min-height: 100vh;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #0a1a33;
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            isolation: isolate;
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

        /* Mobile Background */
        @media (max-width: 768px) {
            body::before {
                background-image: url("https://campuslink.villaruz-print-shop-and-general-merchandise.shop/Images/desktop.jpg");
            }
        }

        /* Dark Overlay Gradient (Also fixed, sits above the image) */
        body::after {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -1;
            background: linear-gradient(180deg,
                    rgba(4, 18, 42, .42) 0%,
                    rgba(4, 18, 42, .24) 45%,
                    rgba(4, 18, 42, .50) 100%);
            pointer-events: none;
        }

        main {
            flex: 1 0 auto;
            width: 100%;
            /* Push content down because header is now fixed */
            padding-top: 100px;
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
            /* Changed from sticky to fixed */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 100;
            background: rgba(255, 255, 255, .82);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(255, 255, 255, .45);
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

        .nav-empty-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            margin: 4px 0;
            border-radius: var(--radius);
            background: #f8fafc;
            border: 1px dashed var(--line);
            color: var(--muted);
            font-size: 12.5px;
            line-height: 1.5;
        }

        .nav-empty-note i {
            color: #94a3b8;
            font-size: 14px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .nav-empty-note span {
            word-break: break-word;
        }

        /* FLASH BANNERS */
        .flash-banner {
            max-width: 720px;
            margin: 22px auto 0;
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.55;
            box-shadow: 0 8px 22px rgba(0, 0, 0, .18);
        }

        .flash-banner i {
            font-size: 16px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .flash-banner.is-error {
            background: rgba(254, 242, 242, .96);
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        .flash-banner.is-success {
            background: rgba(236, 253, 245, .96);
            border: 1px solid #a7f3d0;
            color: #047857;
        }

        /* PANEL */
        .panel {
            background: rgba(255, 255, 255, .88);
            border: 1px solid rgba(255, 255, 255, .78);
            border-radius: 18px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, .20);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            padding: 34px;
            margin: 34px auto 60px;
            width: 100%;
            max-width: 1440px;
            min-height: calc(100vh - 260px);
        }

        /* WELCOME CARD */
        .welcome-card {
            text-align: center;
            padding: 56px 28px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(239, 246, 255, .92), rgba(219, 234, 254, .88));
            border: 1px solid rgba(147, 197, 253, .78);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .55);
        }

        .welcome-card.is-closed {
            background: linear-gradient(135deg, #f8fafc, #eef2f7);
            border-color: var(--line);
        }

        .welcome-card.is-transferee {
            background: linear-gradient(135deg, rgba(254, 243, 199, .92), rgba(253, 230, 138, .78));
            border-color: rgba(245, 158, 11, .55);
        }

        .welcome-card.is-new {
            background: linear-gradient(135deg, rgba(220, 252, 231, .92), rgba(187, 247, 208, .88));
            border-color: rgba(16, 185, 129, .55);
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

        .welcome-card.is-closed .welcome-icon {
            color: #64748b;
            box-shadow: 0 12px 26px -14px rgba(100, 116, 139, .45);
        }

        .welcome-card.is-transferee .welcome-icon {
            color: #b45309;
            box-shadow: 0 12px 26px -14px rgba(245, 158, 11, .55);
        }

        .welcome-card.is-new .welcome-icon {
            color: #047857;
            box-shadow: 0 12px 26px -14px rgba(16, 185, 129, .55);
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
            max-width: 620px;
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
            margin-bottom: 26px;
        }

        .welcome-card .account-box b {
            font-family: 'Poppins';
            font-weight: 800;
            letter-spacing: .04em;
        }

        .welcome-card>.account-box+.account-box {
            margin-left: 8px;
        }

        @media (min-width: 641px) {
            .welcome-card .account-box {
                margin-bottom: 26px;
            }
        }

        .section-picker {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            margin: 6px auto 24px;
            max-width: 420px;
        }

        .section-picker label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #475569;
        }

        .section-picker select {
            width: 100%;
            padding: 12px 40px 12px 16px;
            border: 1.5px solid #bfdbfe;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14.5px;
            color: var(--text);
            background: #fff;
            cursor: pointer;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 16px;
            transition: border-color .18s, box-shadow .18s;
        }

        .section-picker select:focus {
            border-color: var(--primary-2);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .15);
        }

        .welcome-actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 13px 24px;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14.5px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: transform .15s, box-shadow .15s, background .15s, opacity .15s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-2), var(--primary-dark));
            color: #fff;
            box-shadow: 0 10px 22px -10px rgba(37, 99, 235, .8);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 14px 26px -12px rgba(37, 99, 235, .9);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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

            /* Adjust main padding for smaller header */
            main {
                padding-top: 76px;
            }
        }

        @media (max-width: 640px) {
            .container {
                padding: 0 16px;
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

            .panel {
                padding: 14px;
                margin: 16px 0 32px;
                min-height: calc(100vh - 170px);
                border-radius: 14px;
            }

            .welcome-card {
                padding: 34px 16px;
                border-radius: 10px;
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

            .welcome-actions .btn {
                width: 100%;
            }

            .section-picker {
                max-width: 100%;
            }

            .welcome-card .account-box {
                display: flex;
                width: 100%;
                justify-content: center;
                margin: 0 0 10px !important;
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

            .flash-banner {
                margin: 16px auto 0;
                font-size: 13px;
                padding: 12px 14px;
            }

            /* Adjust main padding for smaller header */
            main {
                padding-top: 66px;
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

        /* MODERN RESPONSIVE BUTTONS */
        .btn {
            position: relative;
            min-height: 50px;
            padding: 14px 28px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, .28);
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: .01em;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            overflow: hidden;
            transition:
                transform .2s ease,
                box-shadow .2s ease,
                filter .2s ease,
                background .2s ease;
            -webkit-tap-highlight-color: transparent;
        }

        .btn::before {
            content: "";
            position: absolute;
            top: 0;
            left: -110%;
            width: 65%;
            height: 100%;
            background: linear-gradient(100deg, transparent, rgba(255, 255, 255, .35), transparent);
            transform: skewX(-18deg);
            transition: left .55s ease;
        }

        .btn:hover::before {
            left: 140%;
        }

        .btn i {
            font-size: 16px;
            transition: transform .2s ease;
        }

        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 55%, #1e40af 100%);
            border-color: rgba(147, 197, 253, .45);
            box-shadow:
                0 12px 24px rgba(29, 78, 216, .35),
                inset 0 1px 0 rgba(255, 255, 255, .20);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-3px);
            filter: brightness(1.08);
            box-shadow:
                0 17px 30px rgba(29, 78, 216, .45),
                0 0 0 4px rgba(59, 130, 246, .14);
        }

        .btn-primary:hover:not(:disabled) i {
            transform: translateX(2px) scale(1.06);
        }

        .btn-primary:active:not(:disabled) {
            transform: translateY(0) scale(.98);
            box-shadow: 0 8px 16px rgba(29, 78, 216, .32);
        }

        .btn-primary:disabled {
            opacity: .6;
            cursor: not-allowed;
            transform: none;
        }

        @media (max-width: 640px) {
            .welcome-actions {
                width: 100%;
                padding: 0 2px;
            }

            .welcome-actions .btn {
                width: 100%;
                min-height: 54px;
                padding: 15px 20px;
                border-radius: 15px;
                font-size: 15px;
            }

            .welcome-actions .btn i {
                font-size: 17px;
            }
        }

        @media (max-width: 380px) {
            .welcome-actions .btn {
                min-height: 52px;
                font-size: 14px;
            }
        }

        /* CAMPUSLINK DARK HEADER */
        .nav {
            /* Changed from sticky to fixed */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 100;
            background: linear-gradient(180deg, #06275f 0%, #031a43 100%);
            border-bottom: 1px solid rgba(255, 255, 255, .14);
            box-shadow: 0 8px 24px rgba(0, 15, 45, .28);
            overflow: visible;
        }


        .nav-inner {
            position: relative;
            z-index: 2;
            min-height: 100px;
            padding: 10px 0;
        }

        .logo {
            gap: 14px;
            align-items: center;
        }

        .logo-mark {
            width: 108px;
            height: 76px;
            flex-shrink: 0;
        }

        .logo-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .logo-divider {
            display: block;
            width: 1px;
            height: 48px;
            background: rgba(255, 255, 255, .42);
            flex-shrink: 0;
        }

        .campus-link-title {
            display: block;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 24px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: .02em;
            white-space: nowrap;
            text-transform: uppercase;
            text-shadow: 0 2px 8px rgba(0, 0, 0, .2);
        }

        .logo-text {
            display: none !important;
        }

        .page-label {
            color: #dce7ff;
            border: 0;
            background: transparent;
            font-size: 13px;
            letter-spacing: .12em;
            padding: 8px 14px;
        }

        .nav-toggle {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(255, 255, 255, .96);
            border: 1px solid rgba(255, 255, 255, .7);
            color: #0f172a;
            box-shadow: 0 8px 18px rgba(0, 0, 0, .15);
        }

        .nav-toggle:hover {
            background: #fff;
            color: #174fc1;
        }

        .nav {
            overflow: visible !important;
        }

        .nav-dropdown {
            z-index: 1000 !important;
        }

        @media (max-width: 640px) {
            .nav-inner {
                min-height: 68px;
                padding: 8px 0;
            }

            .logo {
                gap: 9px;
            }

            .logo-mark {
                width: 78px;
                height: 54px;
            }

            .logo-divider {
                height: 34px;
            }

            .campus-link-title {
                font-size: 16px;
                letter-spacing: .01em;
            }

            .page-label {
                display: none;
            }

            .nav-toggle {
                width: 44px;
                height: 44px;
            }

            .nav::before,
            .nav::after {
                width: 100px;
            }
        }

        @media (max-width: 480px) {
            .campus-link-title {
                font-size: 14px;
            }

            .logo-mark {
                width: 70px;
                height: 48px;
            }
        }

        @media (max-width: 380px) {
            .logo {
                gap: 7px;
            }

            .logo-mark {
                width: 62px;
                height: 44px;
            }

            .logo-divider {
                height: 30px;
            }

            .campus-link-title {
                font-size: 12px;
            }
        }

        /* FINAL REQUEST: remove the outer/content cards */
        main.container {
            width: 100%;
            max-width: none;
            padding: 0 24px;
            padding-top: 100px;
            /* Push content down for fixed header */
        }

        .panel,
        .welcome-card,
        .welcome-card.is-closed,
        .welcome-card.is-transferee,
        .welcome-card.is-new,
        .welcome-card.is-dark-blue-card {
            width: 100%;
            max-width: none;
            min-height: 0;
            margin: 0;
            padding: 42px 20px;
            background: transparent !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            -webkit-backdrop-filter: none !important;
            backdrop-filter: none !important;
        }

        .welcome-card h2,
        .welcome-card p,
        .welcome-card strong,
        .welcome-card,
        .welcome-card .account-box,
        .section-picker label {
            color: #fff !important;
        }

        .welcome-card p {
            max-width: 680px;
        }

        .welcome-card .account-box {
            display: inline-flex;
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
            padding: 10px 12px;
        }

        .section-picker select {
            color: #fff !important;
            background-color: rgba(3, 26, 67, .78) !important;
            border-color: rgba(255, 255, 255, .55);
        }

        .section-picker select option {
            color: #fff;
            background: #06275f;
        }

        .welcome-card .welcome-icon {
            background: rgba(255, 255, 255, .96);
        }

        .welcome-card .welcome-icon i {
            color: #1d4ed8 !important;
        }

        /* Registrar notice block */
        .registrar-notice {
            max-width: 720px;
            margin: 22px auto 0;
            text-align: left;
            background: rgba(255, 255, 255, 0.10);
            border: 1px solid rgba(255, 255, 255, 0.30);
            border-radius: 12px;
            padding: 20px 22px;
            color: #fff;
            font-size: 14.5px;
            line-height: 1.75;
        }

        .registrar-notice h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #fff;
        }

        .registrar-notice h3 i {
            color: #fbbf24;
            font-size: 18px;
        }

        .registrar-notice ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .registrar-notice li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 6px 0;
            color: #fff;
        }

        .registrar-notice li i {
            color: #34d399;
            font-size: 14px;
            margin-top: 4px;
            flex-shrink: 0;
        }

        @media (max-width: 640px) {
            .registrar-notice {
                padding: 16px 16px;
                font-size: 13.5px;
                line-height: 1.7;
            }

            .registrar-notice h3 {
                font-size: 15px;
            }
        }

        /* White typography across the page */
        body,
        main,
        main h1,
        main h2,
        main h3,
        main p,
        main span,
        main label,
        main strong,
        main b,
        main i {
            color: #fff;
        }

        .nav-dropdown,
        .nav-dropdown-head,
        .nav-dropdown a,
        .nav-dropdown button,
        .nav-dropdown .nav-empty-note,
        .nav-dropdown .nav-empty-note span {
            color: #fff;
        }

        .nav-dropdown {
            background: #06275f;
            border-color: rgba(255, 255, 255, .18);
        }

        .nav-dropdown-head {
            border-bottom-color: rgba(255, 255, 255, .18);
        }

        .nav-dropdown a:hover,
        .nav-dropdown button:hover {
            background: rgba(255, 255, 255, .12);
            color: #fff;
        }

        .nav-dropdown a i,
        .nav-dropdown button i,
        .nav-dropdown .nav-empty-note i {
            color: #fff;
        }

        .nav-empty-note {
            background: rgba(255, 255, 255, .08);
            border-color: rgba(255, 255, 255, .3);
        }

        @media (max-width: 640px) {
            main.container {
                padding: 0 16px;
                padding-top: 66px;
            }

            .panel,
            .welcome-card,
            .welcome-card.is-closed,
            .welcome-card.is-transferee,
            .welcome-card.is-new,
            .welcome-card.is-dark-blue-card {
                padding: 32px 8px;
                margin: 0;
            }
        }

        /* ============================================================
           NEW: DARK BLUE GLASS CARD OVERRIDE
           (Placed at the very bottom to override "FINAL REQUEST")
           ============================================================ */
        .welcome-card.is-dark-blue-card {
            background: rgba(15, 30, 60, 0.65) !important;
            /* Dark navy blue */
            backdrop-filter: blur(0px) !important;
            /* Heavy glass blur */
            -webkit-backdrop-filter: blur(16px) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            /* Subtle white border */
            border-radius: 16px !important;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4) !important;
            /* Deep shadow */
            padding: 48px 32px !important;
            /* Restore padding */
            max-width: 800px !important;
            margin: 30px auto !important;
        }

        @media (max-width: 640px) {
            .welcome-card.is-dark-blue-card {
                padding: 34px 16px !important;
                border-radius: 12px !important;
            }
        }
    </style>
</head>

<body>

    <header class="nav" id="nav">
        <div class="container nav-inner">
            <a href="#" class="logo">
                <div class="logo-mark">
                    <img src="https://campuslink.villaruz-print-shop-and-general-merchandise.shop/Images/campuslink-logo.png"
                        alt="CampusLink Logo" onerror="this.style.display='none'">
                </div>
                <span class="logo-divider" aria-hidden="true"></span>
                <span class="campus-link-title">CampusLink</span>
            </a>

            <div class="nav-right">
                <span class="page-label">STUDENT ENROLLMENT</span>

                <div class="nav-actions">
                    <button class="nav-toggle" id="navToggle" aria-label="Buksan ang menu" aria-expanded="false"
                        style="margin-right: 10px;">
                        <i class="fas fa-bars"></i>
                    </button>

                    <div class="nav-dropdown" id="navDropdown" role="menu">
                        <div class="nav-dropdown-head">MENU</div>
                        <a href="logout.php" role="menuitem" style="color: #b91c1c;">
                            <i class="fas fa-right-from-bracket" style="color: #b91c1c;"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container">

        <?php if ($isLoggedIn && $user): ?>
            <section class="panel">

                <?php if ($isPending): ?>
                    <div class="welcome-card is-dark-blue-card">
                        <div class="welcome-icon">
                            <i class="fas fa-hourglass-half"></i>
                        </div>

                        <h2>Enrollment Pending, <?php echo htmlspecialchars($displayName ?: $user['account_number']); ?>!</h2>

                        <p>
                            Your enrollment for
                            <strong>S.Y. <?php echo htmlspecialchars($currentYear); ?></strong>
                            has been submitted and is now waiting for final processing.
                        </p>

                        <div class="account-box">
                            <i class="fas fa-id-card"></i>
                            Account No. <b><?php echo htmlspecialchars($user['account_number']); ?></b>
                        </div>

                        <div class="account-box" style="margin-left: 8px;">
                            <i class="fas fa-graduation-cap"></i>
                            Grade Level: <b><?php echo htmlspecialchars($userGrade !== '' ? $userGrade : '—'); ?></b>
                        </div>

                        <div class="registrar-notice">
                            <h3>
                                <i class="fas fa-triangle-exclamation"></i>
                                Your Next Step: Visit the Registrar's Office
                            </h3>
                            <p style="margin-bottom: 12px;">
                                Students who have completed online enrollment must still finalize their records
                                in person. Please proceed to the <strong>Registrar's Office</strong> to complete
                                the enrollment process.
                            </p>
                            <ul>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <span>Bring your <strong>required documents</strong> for verification.</span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <span>Dress appropriately in a <strong>plain white T-shirt</strong> — this is for
                                        your official ID picture.</span>
                                </li>
                                <li>
                                    <i class="fas fa-clock"></i>
                                    <span>The Registrar's Office is open from <strong>8:00 AM to 4:00 PM</strong>,
                                        Monday to Friday.</span>
                                </li>
                                <li>
                                    <i class="fas fa-clock"></i>
                                    <span>Your enrollment request will expire in 24 hours.</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                <?php elseif ($canEnroll): ?>
                    <div class="welcome-card is-dark-blue-card">
                        <div class="welcome-icon">
                            <?php if ($isTransferee): ?>
                                <i class="fas fa-people-arrows"></i>
                            <?php elseif ($isNewStudent): ?>
                                <i class="fas fa-user-plus"></i>
                            <?php else: ?>
                                <i class="fas fa-hand-sparkles"></i>
                            <?php endif; ?>
                        </div>

                        <h2>Welcome, <?php echo htmlspecialchars($displayName ?: $user['account_number']); ?>!</h2>

                        <p>
                            <?php if ($isTransferee): ?>
                                As a <strong>transferee</strong>, you are enrolling for
                                <strong>S.Y. <?php echo htmlspecialchars($currentYear); ?></strong>.
                                Select your section below and click <strong>Enroll Now</strong>.
                                After enrolling online, please proceed to the
                                <strong>Registrar's Office</strong> to submit your transfer credentials.
                            <?php elseif ($isNewStudent): ?>
                                Welcome to Eguia National High School! You are enrolling as a
                                <strong>new student</strong> for
                                <strong>S.Y. <?php echo htmlspecialchars($currentYear); ?></strong>.
                                Select your section below and click <strong>Enroll Now</strong>.
                                After enrolling online, please proceed to the
                                <strong>Registrar's Office</strong> to submit your requirements.
                            <?php else: ?>
                                Enrollment for
                                <strong>S.Y. <?php echo htmlspecialchars($currentYear); ?></strong>
                                is now open. Select your section below, then click
                                <strong>Enroll Now</strong> to proceed.
                            <?php endif; ?>
                        </p>

                        <div class="account-box">
                            <i class="fas fa-id-card"></i>
                            Account No. <b><?php echo htmlspecialchars($user['account_number']); ?></b>
                        </div>

                        <div class="account-box" style="margin-left: 8px;">
                            <i class="fas fa-graduation-cap"></i>
                            Grade Level: <b><?php echo htmlspecialchars($userGrade !== '' ? $userGrade : '—'); ?></b>
                        </div>

                    </div>

                <?php elseif ($isClosed): ?>
                    <div class="welcome-card is-dark-blue-card">
                        <div class="welcome-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h2>Enrollment is Currently Closed</h2>
                        <p>
                            Online enrollment is not available at the moment. Please wait for the school's
                            official announcement and schedule before trying again. You may also visit the
                            <strong>Registrar's Office</strong> for assistance during office hours.
                        </p>
                        <div class="account-box">
                            <i class="fas fa-id-card"></i>
                            Account No. <b><?php echo htmlspecialchars($user['account_number']); ?></b>
                        </div>
                    </div>

                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <script>
        /* ---------- NAV ---------- */
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

        /* ---------- ENROLL NOW ---------- */
        const enrollNowBtn = document.getElementById('enrollNowBtn');
        const sectionSelect = document.getElementById('sectionSelect');

        if (enrollNowBtn) {
            enrollNowBtn.addEventListener('click', function () {
                if (enrollNowBtn.disabled) return;

                if (!sectionSelect || !sectionSelect.value) {
                    alert('Please select a section first.');
                    if (sectionSelect) sectionSelect.focus();
                    return;
                }

                const chosenSection = sectionSelect.value;

                enrollNowBtn.disabled = true;
                enrollNowBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…';
                if (sectionSelect) sectionSelect.disabled = true;

                const form = document.createElement('form');
                form.method = 'POST';
                // FIXED: relative path — resolves correctly in root AND subfolder installs
                form.action = 'API_STUDENT/student-enrollment.php';

                const fields = {
                    csrf_token: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    section: chosenSection
                };

                for (const [name, value] of Object.entries(fields)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    form.appendChild(input);
                }

                document.body.appendChild(form);
                setTimeout(() => form.submit(), 800);
            });
        }

        /* ---------- SPINNER OVERLAY ---------- */
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