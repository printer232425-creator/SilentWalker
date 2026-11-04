<?php
// index.php — Eguia National High School Portal
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

/* ============================================================
   FETCH ALL SCHOOLS FROM `schools` TABLE
   ============================================================ */
$schoolsData = [];
try {
    $stmt = $pdo->query("
        SELECT id, school_name, school_address, school_type
        FROM schools
        WHERE school_name IS NOT NULL
          AND school_name <> ''
          AND school_address IS NOT NULL
          AND school_address <> ''
        ORDER BY school_name ASC
    ");
    $schoolsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("schools fetch failed: " . $e->getMessage());
    $schoolsData = [];
}

/* Unique school names — used for register dropdowns */
$schoolAddresses = array_values(array_unique(array_column($schoolsData, 'school_name')));

/* Total students — still from student_records */
$totalStudents = 0;
try {
    $totalStudents = (int) $pdo->query("SELECT COUNT(*) FROM student_records")->fetchColumn();
} catch (PDOException $e) {
    error_log("student count failed: " . $e->getMessage());
    $totalStudents = 0;
}
?>
<!DOCTYPE html>
<html lang="fil">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>" />
    <title>Eguia National High School — Dasol, Pangasinan</title>
    <meta name="description"
        content="Opisyal na portal ng Eguia National High School sa Dasol, Pangasinan. Enrollment, grades, schedule, at iba pang serbisyo." />

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
            --navy: #0a1a33;
            --primary: #1e40af;
            --primary-2: #3b82f6;
            --primary-dark: #1d4ed8;
            --gold: #f59e0b;
            --bg: #f8fafc;
            --text: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;

            --radius: 18px;
            --shadow-md: 0 14px 34px -14px rgba(15, 23, 42, .22);
            --shadow-lg: 0 34px 70px -28px rgba(15, 23, 42, .45);
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

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 600;
            padding: 14px 26px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease;
            white-space: nowrap;
        }

        .btn i {
            font-size: 16px;
            flex-shrink: 0;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-2), var(--primary-dark));
            color: #fff;
            box-shadow: 0 12px 26px -10px rgba(37, 99, 235, .85);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 34px -12px rgba(37, 99, 235, .95);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-ghost {
            background: rgba(255, 255, 255, .08);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, .28);
            backdrop-filter: blur(8px);
        }

        .btn-ghost:hover {
            background: rgba(255, 255, 255, .18);
            transform: translateY(-2px);
        }

        .btn-light {
            background: #fff;
            color: var(--primary-dark);
            box-shadow: 0 12px 26px -12px rgba(0, 0, 0, .4);
        }

        .btn-light:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 36px -14px rgba(0, 0, 0, .5);
        }

        .btn-sm {
            padding: 10px 18px;
            font-size: 14px;
            border-radius: 10px;
        }

        .btn-login {
            background: #fff;
            color: var(--primary-dark);
            border: 1.5px solid var(--primary-2);
        }

        .btn-login:hover {
            background: #eff6ff;
            transform: translateY(-1px);
        }

        /* NAV */
        .nav {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, .96);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--line);
            transition: box-shadow .3s ease;
        }

        .nav.scrolled {
            box-shadow: 0 8px 24px -18px rgba(15, 23, 42, .4);
        }

        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 14px 0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .logo-mark {
            width: 46px;
            height: 46px;
            border-radius: 606px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            box-shadow: 0 8px 20px -10px rgba(15, 23, 42, .35);
            position: relative;
            padding: 5px;
            overflow: visible;
            background: transparent;
        }

        .logo-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            border-radius: 10px;
            position: relative;
            z-index: 1;
        }

        .logo-text {
            min-width: 0;
        }

        .logo-text b {
            display: block;
            font-family: 'Poppins';
            font-size: 16px;
            font-weight: 700;
            line-height: 1.2;
            color: var(--text);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 210px;
        }

        .logo-text span {
            display: block;
            font-size: 11px;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 210px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2px;
            list-style: none;
        }

        .nav-links a {
            color: var(--muted);
            font-size: 14.5px;
            font-weight: 500;
            padding: 9px 15px;
            border-radius: 10px;
            transition: .2s;
        }

        .nav-links a:hover {
            background: #eef2ff;
            color: var(--primary-dark);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-toggle {
            display: none;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #f1f5f9;
            border: 1px solid var(--line);
            color: var(--text);
            cursor: pointer;
            place-items: center;
            font-size: 18px;
        }

        /* MOBILE MENU */
        .mobile-menu {
            display: none;
            position: fixed;
            top: 70px;
            left: 16px;
            right: 16px;
            z-index: 99;
            background: #fff;
            border-radius: 16px;
            padding: 10px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--line);
            max-height: calc(100vh - 90px);
            overflow-y: auto;
        }

        .mobile-menu.open {
            display: block;
            animation: dropIn .22s ease;
        }

        .mobile-menu a {
            display: block;
            padding: 13px 16px;
            border-radius: 11px;
            color: var(--text);
            font-weight: 500;
            font-size: 15px;
        }

        .mobile-menu a:hover {
            background: #f1f5f9;
            color: var(--primary-dark);
        }

        .mm-auth {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 10px 6px 6px;
            margin-top: 8px;
            border-top: 1px solid var(--line);
        }

        .mm-auth .btn {
            width: 100%;
            padding: 13px 16px;
            font-size: 15px;
            border-radius: 11px;
        }

        .mm-auth .mm-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #94a3b8;
            font-weight: 700;
            padding: 8px 10px 4px;
        }

        .mm-auth .mm-divider::before,
        .mm-auth .mm-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--line);
        }

        .mm-school-list {
            display: flex;
            flex-direction: column;
            gap: 4px;
            max-height: 240px;
            overflow-y: auto;
            padding: 4px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid var(--line);
        }

        .mm-school-list button {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 12px;
            border-radius: 8px;
            color: var(--text);
            font-size: 13.5px;
            font-weight: 500;
            transition: background .15s, color .15s;
            cursor: pointer;
            border: none;
            background: #fff;
            width: 100%;
            text-align: left;
            font-family: inherit;
        }

        .mm-school-list button:hover {
            background: #eef2ff;
            color: var(--primary-dark);
        }

        .mm-school-list button i {
            font-size: 13px;
            width: 14px;
            text-align: center;
            flex-shrink: 0;
            color: var(--primary);
        }

        .mm-empty {
            padding: 14px;
            text-align: center;
            color: var(--muted);
            font-size: 12.5px;
        }

        @keyframes dropIn {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        /* DESKTOP LOGIN DROPDOWN */
        .login-dropdown {
            position: relative;
            display: inline-block;
        }

        .login-dropdown .btn i.chevron {
            font-size: 11px;
            transition: transform .2s ease;
        }

        .login-dropdown.open .btn i.chevron {
            transform: rotate(180deg);
        }

        .login-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            left: auto;
            width: 320px;
            max-height: 420px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: var(--shadow-lg);
            padding: 8px;
            z-index: 200;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px);
            transition: opacity .18s ease, transform .18s ease, visibility .18s ease;
        }

        .login-dropdown.open .login-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .login-menu-head {
            padding: 10px 12px 8px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #94a3b8;
            font-weight: 700;
            border-bottom: 1px solid var(--line);
            margin-bottom: 6px;
        }

        .login-menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 9px;
            color: var(--text);
            font-size: 13.5px;
            font-weight: 500;
            transition: background .15s, color .15s;
            cursor: pointer;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
            font-family: inherit;
        }

        .login-menu-item:hover {
            background: #eef2ff;
            color: var(--primary-dark);
        }

        .login-menu-item i {
            font-size: 14px;
            width: 16px;
            text-align: center;
            flex-shrink: 0;
            color: var(--primary);
        }

        .login-menu-empty {
            padding: 18px 14px;
            text-align: center;
            color: var(--muted);
            font-size: 13px;
        }

        /* MODAL */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(10, 26, 51, 0.65);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            z-index: 5000;
            display: none;
            align-items: flex-start;
            justify-content: center;
            padding: 20px;
            overflow-y: auto;
            opacity: 0;
            visibility: hidden;
            transition: opacity .2s ease, visibility .2s ease;
        }

        .modal-overlay.show {
            display: flex;
            opacity: 1;
            visibility: visible;
        }

        .modal-card {
            background: #fff;
            border-radius: 22px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 34px 70px -20px rgba(0, 0, 0, 0.5);
            transform: translateY(12px) scale(0.97);
            transition: transform .22s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            margin: auto 0;
        }

        .modal-overlay.show .modal-card {
            transform: translateY(0) scale(1);
        }

        #registerModal .modal-card {
            max-height: none;
            overflow-y: visible;
            margin: 20px 0;
        }

        .modal-head {
            background: linear-gradient(135deg, var(--primary-2), var(--primary-dark));
            padding: 26px 26px 22px;
            color: #fff;
            position: relative;
            border-radius: 22px 22px 0 0;
        }

        .modal-head.login-head {
            background: linear-gradient(135deg, #0e7490, #06b6d4);
        }

        .modal-head h3 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-right: 40px;
        }

        .modal-head h3 i {
            font-size: 20px;
            flex-shrink: 0;
        }

        .modal-head p {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
            line-height: 1.5;
        }

        .modal-head .school-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12.5px;
            font-weight: 600;
            margin-top: 12px;
            max-width: 100%;
        }

        .modal-head .school-chip span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .modal-head .school-chip i {
            font-size: 12px;
            flex-shrink: 0;
        }

        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.18);
            border: none;
            color: #fff;
            cursor: pointer;
            display: grid;
            place-items: center;
            font-size: 16px;
            transition: background .2s;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .modal-body {
            padding: 24px 26px 26px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }

        .form-group label .req {
            color: #ef4444;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--line);
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            color: var(--text);
            background: #f8fafc;
            transition: border-color .18s, box-shadow .18s, background .18s;
            outline: none;
        }

        .form-group input[readonly] {
            background: #f1f5f9;
            color: #64748b;
            cursor: not-allowed;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }

        .form-group select {
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
            padding-right: 40px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary-2);
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .form-group input.error,
        .form-group select.error,
        .form-group textarea.error {
            border-color: #ef4444;
            background-color: #fef2f2;
        }

        .form-group input.error:focus,
        .form-group select.error:focus,
        .form-group textarea.error:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
        }

        .form-group .err-msg {
            display: none;
            font-size: 11.5px;
            color: #ef4444;
            margin-top: 4px;
            font-weight: 500;
        }

        .form-group .err-msg.show {
            display: block;
        }

        .pin-input {
            letter-spacing: 0.6em;
            font-weight: 700;
            text-align: center;
            font-size: 16px !important;
            padding-left: 20px !important;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 8px;
        }

        .form-actions .btn {
            flex: 1;
            padding: 13px 18px;
            font-size: 14.5px;
        }

        .btn-cancel {
            background: #f1f5f9;
            color: #475569;
            border: none;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-2), var(--primary-dark));
            color: #fff;
            box-shadow: 0 12px 26px -10px rgba(37, 99, 235, .85);
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 16px 30px -12px rgba(37, 99, 235, .95);
        }

        .btn-submit:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .btn-login-submit {
            background: linear-gradient(135deg, #06b6d4, #0e7490);
            color: #fff;
            box-shadow: 0 12px 26px -10px rgba(6, 182, 212, .85);
        }

        .btn-login-submit:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 16px 30px -12px rgba(6, 182, 212, .95);
        }

        .modal-alert {
            display: none;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .modal-alert.show {
            display: block;
        }

        .modal-alert.success {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .modal-alert.error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .section-title-group {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #94a3b8;
            margin: 18px 0 10px;
            padding-top: 12px;
            border-top: 1px dashed var(--line);
        }

        .section-title-group:first-of-type {
            margin-top: 0;
            padding-top: 0;
            border-top: none;
        }

        /* SUCCESS POPUP */
        .success-card {
            max-width: 480px;
        }

        .success-head {
            background: linear-gradient(135deg, #059669, #10b981);
            padding: 28px 26px 24px;
            color: #fff;
            text-align: center;
            border-radius: 22px 22px 0 0;
        }

        .success-head h3 {
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .success-head h3 i {
            font-size: 24px;
        }

        .success-head p {
            font-size: 13.5px;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.5;
        }

        .success-body {
            padding: 28px 26px 26px;
            text-align: center;
        }

        .account-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 16px;
            margin-bottom: 18px;
        }

        .account-label i {
            color: var(--primary);
        }

        .account-display {
            font-family: 'Poppins', monospace;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: .06em;
            color: var(--primary-dark);
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border: 2px solid #bfdbfe;
            border-radius: 14px;
            padding: 20px 16px;
            margin-bottom: 16px;
            word-break: break-all;
            user-select: all;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .account-display:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px -10px rgba(37, 99, 235, .5);
        }

        .account-display.copied {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border-color: #a7f3d0;
            color: #059669;
        }

        .success-actions {
            display: flex;
            gap: 10px;
        }

        .success-actions .btn {
            flex: 1;
            padding: 14px 20px;
            font-size: 15px;
        }

        /* HERO */
        .hero {
            position: relative;
            overflow: hidden;
            color: #fff;
            padding: 100px 0 120px;
            background-image:
                linear-gradient(135deg, rgba(10, 26, 51, 0.88) 0%, rgba(10, 26, 51, 0.65) 50%, rgba(10, 26, 51, 0.88) 100%),
                url('deped-bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: radial-gradient(circle at 50% 0%, rgba(59, 130, 246, 0.3) 0%, transparent 60%);
        }

        .hero-inner {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 820px;
            margin: 0 auto;
        }

        .hero-brand-row {
            display: flex;
            justify-content: center;
            width: 100%;
            margin-bottom: 28px;
        }

        .hero-brand {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            padding: 50px 50px 80px 50px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            max-width: 100%;
        }

        .hero-brand-logo {
            width: 155px;
            height: 155px;
            border-radius: 14px;
            flex-shrink: 0;
            padding: 6px;
            position: relative;
            display: grid;
            place-items: center;
            overflow: visible;
            background: transparent;
        }

        .hero-brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            border-radius: 5px;
        }

        .hero-brand-text {
            display: flex;
            flex-direction: column;
            text-align: left;
            line-height: 1.1;
            min-width: 0;
        }

        .hero-brand-text .brand-name {
            font-family: 'Poppins', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.02em;
            line-height: 1.15;
        }

        .hero-brand-text .brand-tag {
            font-size: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .14em;
            color: rgba(219, 234, 254, 0.85);
            margin-top: 4px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 7px 16px 7px 9px;
            font-size: 13px;
            font-weight: 500;
            backdrop-filter: blur(8px);
            margin-bottom: 26px;
            max-width: 100%;
            text-align: left;
            line-height: 1.4;
            white-space: normal;
        }

        .hero-badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #34d399;
            box-shadow: 0 0 0 4px rgba(52, 211, 153, .22);
            animation: pulse 2.2s ease-in-out infinite;
            flex-shrink: 0;
        }

        @keyframes pulse {

            0%,
            100% {
                box-shadow: 0 0 0 4px rgba(52, 211, 153, .22);
            }

            50% {
                box-shadow: 0 0 0 8px rgba(52, 211, 153, .06);
            }
        }

        .hero h1 {
            font-size: clamp(2rem, 4.4vw, 3.4rem);
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        }

        .hero h1 .grad {
            background: linear-gradient(100deg, #60a5fa, #22d3ee 55%, #fbbf24);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .hero p.lead {
            color: rgba(255, 255, 255, .85);
            font-size: 16.5px;
            max-width: 620px;
            margin: 0 auto;
            line-height: 1.75;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
        }

        /* SECTIONS */
        .section {
            padding: 92px 0;
        }

        .section-head {
            max-width: 680px;
            margin-bottom: 48px;
        }

        .section-head.center {
            margin-left: auto;
            margin-right: auto;
            text-align: center;
        }

        .eyebrow {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--primary-dark);
            background: #e8efff;
            padding: 6px 13px;
            border-radius: 999px;
            margin-bottom: 16px;
        }

        .section-title {
            font-size: clamp(1.7rem, 3vw, 2.4rem);
            font-weight: 700;
            margin-bottom: 14px;
        }

        .section-sub {
            color: var(--muted);
            font-size: 16px;
            line-height: 1.75;
        }

        /* DIRECTORY */
        .directory {
            background: #fff;
            border-bottom: 1px solid var(--line);
        }

        .dir-toolbar {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 32px;
        }

        .filter-tabs {
            display: flex;
            flex-wrap: nowrap;
            gap: 8px;
            overflow-x: auto;
            -ms-overflow-style: none;
            scrollbar-width: none;
            padding-bottom: 2px;
            max-width: 100%;
        }

        .filter-tabs::-webkit-scrollbar {
            display: none;
        }

        .filter-tabs button {
            font-family: 'Inter';
            font-size: 13.5px;
            font-weight: 600;
            padding: 9px 18px;
            border-radius: 10px;
            cursor: pointer;
            background: #f1f5f9;
            color: var(--muted);
            border: 1px solid transparent;
            transition: .2s;
            flex-shrink: 0;
        }

        .filter-tabs button:hover {
            background: #e2e8f0;
            color: var(--text);
        }

        .filter-tabs button.active {
            background: linear-gradient(135deg, var(--primary-2), var(--primary-dark));
            color: #fff;
            box-shadow: 0 8px 18px -8px rgba(37, 99, 235, .8);
        }

        .dir-count {
            font-size: 13.5px;
            color: var(--muted);
            margin-bottom: 20px;
        }

        .dir-count b {
            color: var(--primary-dark);
            font-weight: 700;
        }

        .school-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 18px;
        }

        .school-card {
            display: flex;
            align-items: center;
            gap: 14px;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 16px 18px;
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
            position: relative;
            cursor: pointer;
            min-width: 0;
        }

        .school-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: #c7d7fe;
        }

        .school-logo {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            font-family: 'Poppins';
            font-weight: 800;
            font-size: 13px;
            color: #fff;
            letter-spacing: .02em;
        }

        .school-logo.elementary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }

        .school-logo.junior {
            background: linear-gradient(135deg, #06b6d4, #0e7490);
        }

        .school-logo.senior {
            background: linear-gradient(135deg, #f59e0b, #b45309);
        }

        .school-body {
            flex: 1;
            min-width: 0;
        }

        .school-type {
            display: inline-block;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: 3px 9px;
            margin-bottom: 6px;
        }

        .school-type.elementary {
            color: #1d4ed8;
        }

        .school-type.junior {
            color: #0e7490;
        }

        .school-type.senior {
            color: #b45309;
        }

        .school-body h3 {
            font-size: 14.5px;
            font-weight: 700;
            line-height: 1.35;
            margin-bottom: 3px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            text-overflow: ellipsis;
        }

        .school-body p {
            font-size: 12.5px;
            color: var(--muted);
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            text-overflow: ellipsis;
        }

        .school-link {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            background: #f1f5f9;
            color: var(--muted);
            transition: .2s;
            font-size: 14px;
        }

        .school-card:hover .school-link {
            background: var(--primary);
            color: #fff;
            transform: translateX(2px);
        }

        .empty-state {
            grid-column: 1/-1;
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state .empty-icon {
            font-size: 40px;
            margin-bottom: 14px;
            color: #cbd5e1;
        }

        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 6px;
        }

        .empty-state p {
            font-size: 14px;
            color: var(--muted);
        }

        /* SERVICES */
        .services {
            background: var(--bg);
        }

        .svc-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .svc-card {
            position: relative;
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 28px;
            transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
            cursor: pointer;
        }

        .svc-card::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(135deg, rgba(37, 99, 235, .06), transparent 62%);
            opacity: 0;
            transition: opacity .25s ease;
        }

        .svc-card:hover {
            transform: translateY(-7px);
            box-shadow: var(--shadow-md);
            border-color: #c7d7fe;
        }

        .svc-card:hover::after {
            opacity: 1;
        }

        .svc-icon {
            width: 54px;
            height: 54px;
            border-radius: 15px;
            display: grid;
            place-items: center;
            margin-bottom: 20px;
            transition: transform .25s ease;
            font-size: 22px;
        }

        .svc-card:hover .svc-icon {
            transform: scale(1.07) rotate(-4deg);
        }

        .svc-card h3 {
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .svc-card p {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.65;
            margin-bottom: 18px;
        }

        .svc-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13.5px;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .svc-link i {
            font-size: 12px;
            transition: transform .25s ease;
        }

        .svc-card:hover .svc-link i {
            transform: translateX(4px);
        }

        .ic-blue {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1d4ed8;
        }

        .ic-teal {
            background: linear-gradient(135deg, #cffafe, #a5f3fc);
            color: #0e7490;
        }

        .ic-amber {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #b45309;
        }

        .ic-violet {
            background: linear-gradient(135deg, #ede9fe, #ddd6fe);
            color: #6d28d9;
        }

        .ic-rose {
            background: linear-gradient(135deg, #ffe4e6, #fecdd3);
            color: #be123c;
        }

        .ic-green {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            color: #15803d;
        }

        /* STATS */
        .stats {
            background: linear-gradient(135deg, #0a1a33, #122a52 55%, #0b2447);
            color: #fff;
            padding: 74px 0;
            position: relative;
            overflow: hidden;
        }

        .stats::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(700px 300px at 50% 0%, rgba(59, 130, 246, .28), transparent 70%);
        }

        .stats-grid {
            position: relative;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            text-align: center;
        }

        .stat {
            padding: 6px 10px;
        }

        .stat+.stat {
            border-left: 1px solid rgba(255, 255, 255, .13);
        }

        .stat .num {
            font-family: 'Poppins';
            font-size: clamp(1.9rem, 3.4vw, 2.7rem);
            font-weight: 800;
            background: linear-gradient(100deg, #93c5fd, #22d3ee);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            word-break: break-word;
            line-height: 1.15;
        }

        .stat .lbl {
            font-size: 13.5px;
            color: rgba(255, 255, 255, .62);
            margin-top: 6px;
            font-weight: 500;
        }

        /* NEWS */
        .news {
            background: #fff;
        }

        .news-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .news-card {
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            transition: transform .25s ease, box-shadow .25s ease;
        }

        .news-card:hover {
            transform: translateY(-7px);
            box-shadow: var(--shadow-md);
        }

        .news-thumb {
            height: 158px;
            position: relative;
            display: grid;
            place-items: center;
            color: rgba(255, 255, 255, .9);
            font-size: 42px;
        }

        .news-thumb .tag {
            position: absolute;
            top: 14px;
            left: 14px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            background: rgba(255, 255, 255, .94);
            color: var(--primary-dark);
            padding: 5px 11px;
            border-radius: 999px;
        }

        .t-blue {
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
        }

        .t-teal {
            background: linear-gradient(135deg, #0e7490, #06b6d4);
        }

        .t-amber {
            background: linear-gradient(135deg, #b45309, #f59e0b);
        }

        .news-body {
            padding: 22px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .news-date {
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: .03em;
            margin-bottom: 9px;
        }

        .news-card h3 {
            font-size: 16.5px;
            font-weight: 700;
            line-height: 1.4;
            margin-bottom: 10px;
        }

        .news-card p {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.65;
            margin-bottom: 18px;
        }

        .news-card .svc-link {
            margin-top: auto;
        }

        /* CTA */
        .cta-wrap {
            padding: 0 0 96px;
            background: #fff;
        }

        .cta {
            position: relative;
            overflow: hidden;
            text-align: center;
            background: linear-gradient(135deg, #1d4ed8, #0e7490);
            border-radius: 28px;
            padding: 64px 32px;
            color: #fff;
            box-shadow: 0 34px 70px -30px rgba(29, 78, 216, .7);
        }

        .cta::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(520px 260px at 18% 0%, rgba(255, 255, 255, .22), transparent 65%),
                radial-gradient(520px 260px at 88% 100%, rgba(255, 255, 255, .16), transparent 65%);
        }

        .cta>* {
            position: relative;
        }

        .cta h2 {
            font-size: clamp(1.7rem, 3.2vw, 2.4rem);
            font-weight: 800;
            margin-bottom: 14px;
        }

        .cta p {
            color: rgba(255, 255, 255, .82);
            max-width: 560px;
            margin: 0 auto 32px;
            font-size: 16px;
            line-height: 1.7;
        }

        .cta-actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 14px;
        }

        /* FOOTER */
        .footer {
            background: #08152b;
            color: rgba(255, 255, 255, .66);
            padding: 66px 0 0;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr 1fr 1.3fr;
            gap: 44px;
            padding-bottom: 52px;
        }

        .footer .logo {
            margin-bottom: 16px;
        }

        .footer .logo-mark {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, .15);
            box-shadow: 0 6px 16px -10px rgba(0, 0, 0, .6);
        }

        .footer .logo-text b {
            color: #fff;
            max-width: none;
        }

        .footer .logo-text span {
            color: rgba(255, 255, 255, .5);
            max-width: none;
        }

        .footer-about {
            font-size: 14px;
            line-height: 1.75;
            max-width: 300px;
        }

        .footer h4 {
            color: #fff;
            font-family: 'Poppins';
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 18px;
        }

        .footer ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 11px;
        }

        .footer ul a {
            color: rgba(255, 255, 255, .62);
            font-size: 14px;
            transition: color .2s;
        }

        .footer ul a:hover {
            color: #fff;
        }

        .footer-contact li {
            display: flex;
            gap: 10px;
            font-size: 14px;
            line-height: 1.6;
        }

        .footer-contact i {
            font-size: 14px;
            flex-shrink: 0;
            margin-top: 4px;
            color: #60a5fa;
            width: 16px;
            text-align: center;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, .1);
            padding: 24px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            font-size: 13px;
            color: rgba(255, 255, 255, .45);
        }

        .footer-socials {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .footer-socials a {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, .07);
            color: rgba(255, 255, 255, .7);
            transition: .2s;
            font-size: 15px;
        }

        .footer-socials a:hover {
            background: var(--primary);
            color: #fff;
            transform: translateY(-2px);
        }

        /* REVEAL */
        .reveal {
            opacity: 0;
            transform: translateY(26px);
            transition: opacity .65s ease, transform .65s ease;
        }

        .reveal.in {
            opacity: 1;
            transform: none;
        }

        /* TABLET */
        @media (max-width:1024px) {

            .svc-grid,
            .news-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
                gap: 36px;
            }

            .hero {
                background-attachment: scroll;
            }

            .nav-inner {
                gap: 12px;
            }

            .logo-text b {
                font-size: 15px;
                max-width: 170px;
            }

            .logo-text span {
                font-size: 10px;
                max-width: 170px;
            }

            .nav-links a {
                padding: 9px 10px;
                font-size: 14px;
            }

            .nav-actions {
                gap: 8px;
            }
        }

        /* MOBILE */
        @media (max-width: 820px) {
            .nav-links {
                display: none;
            }

            .nav-toggle {
                display: grid;
                width: 42px;
                height: 42px;
                font-size: 16px;
            }

            .nav-inner {
                padding: 12px 0;
                gap: 10px;
            }

            .logo {
                gap: 10px;
                min-width: 0;
                flex: 1;
            }

            .logo-mark {
                width: 42px;
                height: 42px;
                border-radius: 5px;
                padding: 4px;
            }

            .logo-text b {
                font-size: 14.5px;
                max-width: 150px;
            }

            .logo-text span {
                font-size: 9.5px;
                max-width: 150px;
                letter-spacing: .08em;
            }

            .nav-actions .btn-login,
            .nav-actions .login-dropdown {
                display: none;
            }

            .mobile-menu {
                top: 68px;
                left: 12px;
                right: 12px;
                max-height: calc(100vh - 80px);
            }

            .hero {
                padding: 70px 0 90px;
                background-attachment: scroll;
            }

            .hero-brand-row {
                width: 100%;
                padding: 0 8px;
                margin-bottom: 24px;
            }

            .hero-brand {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                width: 100%;
                max-width: 100%;
                padding: 22px 20px;
                gap: 16px;
                text-align: center;
            }

            .hero-brand-logo {
                width: 120px;
                height: 120px;
                padding: 8px;
                border-radius: 20px;
            }

            .hero-brand-text {
                align-items: center;
                text-align: center;
            }

            .hero-brand-text .brand-name {
                font-size: 32px;
                letter-spacing: -.02em;
            }

            .hero-brand-text .brand-tag {
                font-size: 12px;
                letter-spacing: .16em;
                margin-top: 6px;
            }

            .hero-badge {
                font-size: 12px;
                padding: 7px 14px 7px 10px;
                line-height: 1.4;
            }

            .hero h1 {
                font-size: clamp(1.6rem, 6.5vw, 2.2rem);
            }

            .hero p.lead {
                font-size: 15px;
                padding: 0 4px;
            }

            .dir-toolbar {
                gap: 12px;
                flex-direction: column;
                align-items: stretch;
            }

            .filter-tabs {
                padding-bottom: 4px;
            }

            .stats {
                padding: 56px 0;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 34px 20px;
            }

            .stat+.stat {
                border-left: none;
            }

            .stat:nth-child(even) {
                border-left: 1px solid rgba(255, 255, 255, .13);
            }

            .section-head {
                margin-bottom: 32px;
            }

            .section {
                padding: 60px 0;
            }

            .cta {
                padding: 44px 20px;
                border-radius: 20px;
            }

            .modal-overlay {
                padding: 12px;
            }

            .modal-card {
                border-radius: 18px;
                max-width: 100%;
            }

            .modal-head {
                padding: 22px 20px 18px;
                border-radius: 18px 18px 0 0;
            }

            .modal-head h3 {
                font-size: 18px;
                padding-right: 36px;
            }

            .modal-body {
                padding: 20px;
            }

            .hero-badge {
                width: 100%;
                max-width: 100%;
                justify-content: flex-start;
                border-radius: 14px;
                font-size: 12.5px;
                padding: 10px 14px;
                gap: 10px;
                background: rgba(255, 255, 255, 0.08);
                border: 1px solid rgba(255, 255, 255, 0.18);
            }
        }

        /* SMALL */
        @media (max-width: 640px) {
            .container {
                padding: 0 16px;
            }

            .section {
                padding: 56px 0;
            }

            .hero {
                padding: 60px 0 80px;
            }

            .hero-brand-row {
                width: 100%;
                padding: 0;
                margin-bottom: 20px;
            }

            .hero-brand {
                padding: 20px 16px;
                gap: 14px;
                border-radius: 18px;
            }

            .hero-brand-logo {
                width: 100px;
                height: 100px;
                padding: 7px;
                border-radius: 18px;
            }

            .hero-brand-text .brand-name {
                font-size: 28px;
            }

            .hero-brand-text .brand-tag {
                font-size: 11px;
                letter-spacing: .14em;
                margin-top: 5px;
            }

            .hero h1 {
                font-size: clamp(1.5rem, 7vw, 1.9rem);
            }

            .hero p.lead {
                font-size: 14.5px;
                line-height: 1.7;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .form-group {
                margin-bottom: 12px;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .form-actions .btn {
                width: 100%;
            }

            .modal-head h3 {
                font-size: 17px;
            }

            .modal-body {
                padding: 18px 16px;
            }

            .school-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .school-card {
                padding: 14px;
                gap: 12px;
            }

            .school-logo {
                width: 46px;
                height: 46px;
                font-size: 12px;
                border-radius: 12px;
            }

            .school-body h3 {
                font-size: 14px;
            }

            .school-body p {
                font-size: 12px;
            }

            .school-link {
                width: 32px;
                height: 32px;
                font-size: 13px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 28px 14px;
            }

            .stat .num {
                font-size: clamp(1.6rem, 7vw, 2.1rem);
            }

            .stat .lbl {
                font-size: 12px;
            }

            .svc-grid,
            .news-grid {
                grid-template-columns: 1fr;
            }

            .footer {
                padding-top: 48px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 28px;
                padding-bottom: 36px;
            }

            .footer-bottom {
                font-size: 12px;
                padding: 20px 0;
                justify-content: center;
                text-align: center;
            }

            .nav-inner {
                padding: 10px 0;
            }

            .logo-mark {
                width: 38px;
                height: 38px;
                padding: 4px;
            }

            .logo-mark img {
                border-radius: 2px;
            }

            .logo-text b {
                font-size: 13.5px;
                max-width: 130px;
            }

            .logo-text span {
                font-size: 9px;
                max-width: 130px;
            }

            .nav-actions {
                gap: 6px;
            }

            .nav-toggle {
                width: 38px;
                height: 38px;
            }

            .mobile-menu {
                top: 62px;
                left: 10px;
                right: 10px;
            }

            .cta-actions {
                flex-direction: column;
            }

            .cta-actions .btn {
                width: 100%;
            }

            .account-display {
                font-size: 24px;
                padding: 16px 12px;
            }

            .success-head h3 {
                font-size: 17px;
            }

            .success-body {
                padding: 22px 18px 20px;
            }

            .hero-brand-row {
                width: 100%;
                padding: 0;
                margin-bottom: 16px;
            }

            .hero-brand {
                padding: 12px 14px;
                gap: 12px;
                border-radius: 16px;
            }

            .hero-brand-logo {
                width: 92px;
                height: 92px;
                padding: 5px;
                border-radius: 13px;
            }

            .hero-brand-text .brand-name {
                font-size: 18px;
            }

            .hero-brand-text .brand-tag {
                font-size: 9.5px;
                letter-spacing: .1em;
                margin-top: 2px;
            }

            .hero-badge {
                border-radius: 12px;
                padding: 9px 12px;
                font-size: 12px;
                gap: 8px;
            }

            .hero-badge .dot {
                width: 7px;
                height: 7px;
            }
        }

        /* TINY */
        @media (max-width: 380px) {
            .logo-mark {
                width: 34px;
                height: 34px;
                padding: 3px;
                border-radius: 10px;
            }

            .logo-text b {
                font-size: 12.5px;
                max-width: 110px;
            }

            .logo-text span {
                font-size: 8.5px;
                max-width: 110px;
            }

            .hero-brand-row {
                margin-bottom: 16px;
            }

            .hero-brand {
                padding: 6px 14px 6px 6px;
                gap: 9px;
            }

            .hero-brand-logo {
                width: 36px;
                height: 36px;
                padding: 3px;
                border-radius: 10px;
            }

            .hero-brand-text .brand-name {
                font-size: 15px;
            }

            .hero-brand-text .brand-tag {
                font-size: 8.5px;
            }

            .hero h1 {
                font-size: 1.35rem;
            }

            .hero p.lead {
                font-size: 13.5px;
            }

            .school-type {
                font-size: 10px;
            }

            .account-display {
                font-size: 20px;
                padding: 14px 10px;
            }

            .hero-brand-row {
                padding: 0;
                margin-bottom: 14px;
            }

            .hero-brand {
                padding: 10px 12px;
                gap: 10px;
                border-radius: 14px;
            }

            .hero-brand-logo {
                width: 46px;
                height: 46px;
                padding: 4px;
                border-radius: 11px;
            }

            .hero-brand-text .brand-name {
                font-size: 16px;
            }

            .hero-brand-text .brand-tag {
                font-size: 8.5px;
                letter-spacing: .08em;
            }

            .hero-badge {
                padding: 8px 10px;
                font-size: 11.5px;
                gap: 7px;
                border-radius: 10px;
            }
        }
    </style>
</head>

<body>
    <header class="nav" id="nav">
        <div class="container nav-inner">
            <a href="#" class="logo">
                <div class="logo-mark">
                    <img src="Images/logo.png" alt="Eguia National High School logo"
                        onerror="this.style.display='none'">
                </div>
                <div class="logo-text">
                    <b>CampusLink</b>
                </div>
            </a>

            <nav>
                <ul class="nav-links">
                    <li><a href="#schools">Mga Paaralan</a></li>
                    <li><a href="#services">Serbisyo</a></li>
                    <li><a href="#stats">Bakit Kami</a></li>
                    <li><a href="#news">Balita</a></li>
                    <li><a href="#contact">Kontak</a></li>
                </ul>
            </nav>

            <div class="nav-actions">
                <button class="btn btn-login btn-sm" id="loginOpenBtn" type="button">
                    <i class="fas fa-right-to-bracket"></i>
                    Mag-login
                </button>

                <div class="login-dropdown" id="loginDropdown">
                    <button class="btn btn-primary btn-sm" id="loginDropdownBtn" type="button">
                        Mag-rehistro
                        <i class="fas fa-chevron-down chevron"></i>
                    </button>
                    <div class="login-menu" id="loginMenu" role="menu">
                        <div class="login-menu-head">Pumili ng Paaralan</div>
                        <?php if (!empty($schoolAddresses)): ?>
                            <?php foreach ($schoolAddresses as $addr): ?>
                                <button type="button" class="login-menu-item" role="menuitem"
                                    data-school="<?php echo htmlspecialchars($addr); ?>">
                                    <i class="fas fa-school"></i>
                                    <?php echo htmlspecialchars($addr); ?>
                                </button>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="login-menu-empty">Wala pang paaralan sa database.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <button class="nav-toggle" id="navToggle" aria-label="Buksan ang menu" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <div class="mobile-menu" id="mobileMenu">
        <a href="#schools">Mga Paaralan</a>
        <a href="#services">Serbisyo</a>
        <a href="#stats">Bakit Kami</a>
        <a href="#news">Balita</a>
        <a href="#contact">Kontak</a>

        <div class="mm-auth">
            <div class="mm-divider">Account</div>

            <button type="button" class="btn btn-login" id="mobileLoginBtn">
                <i class="fas fa-right-to-bracket"></i>
                Mag-login
            </button>

            <div class="mm-divider">Mag-rehistro sa Paaralan</div>

            <div class="mm-school-list" id="mobileSchoolList">
                <?php if (!empty($schoolAddresses)): ?>
                    <?php foreach ($schoolAddresses as $addr): ?>
                        <button type="button" data-school="<?php echo htmlspecialchars($addr); ?>">
                            <i class="fas fa-school"></i>
                            <?php echo htmlspecialchars($addr); ?>
                        </button>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="mm-empty">Wala pang paaralan sa database.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="loginModal" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle">
        <div class="modal-card">
            <div class="modal-head login-head">
                <button type="button" class="modal-close" id="loginModalClose" aria-label="Close">
                    <i class="fas fa-xmark"></i>
                </button>
                <h3 id="loginModalTitle">
                    <i class="fas fa-right-to-bracket"></i>
                    Mag-login
                </h3>
                <p>Ilagay ang iyong account number at PIN para makapasok.</p>
            </div>

            <div class="modal-body">
                <div class="modal-alert" id="loginAlert"></div>

                <form id="loginForm" novalidate>
                    <div class="form-group">
                        <label for="loginAccount">Account Number <span class="req">*</span></label>
                        <input type="text" id="loginAccount" name="account_number" placeholder="ACC-1001"
                            autocomplete="username" required>
                        <div class="err-msg" id="loginAccountErr">Ilagay ang account number.</div>
                    </div>

                    <div class="form-group">
                        <label for="loginPin">6-Digit PIN <span class="req">*</span></label>
                        <input type="text" id="loginPin" name="pin" class="pin-input" placeholder="••••••"
                            inputmode="numeric" maxlength="6" pattern="\d{6}" autocomplete="current-password" required>
                        <div class="err-msg" id="loginPinErr">Dapat eksaktong 6 digit ang PIN.</div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-cancel" id="loginCancelBtn">
                            <i class="fas fa-xmark"></i> Kanselahin
                        </button>
                        <button type="submit" class="btn btn-login-submit" id="loginSubmitBtn">
                            <i class="fas fa-right-to-bracket"></i> Mag-login
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="registerModal" role="dialog" aria-modal="true" aria-labelledby="registerModalTitle">
        <div class="modal-card">
            <div class="modal-head">
                <button type="button" class="modal-close" id="registerModalClose" aria-label="Close">
                    <i class="fas fa-xmark"></i>
                </button>
                <h3 id="registerModalTitle">
                    <i class="fas fa-user-plus"></i>
                    Mag-rehistro
                </h3>
                <p>Punan ang form para makapag-enroll sa napiling paaralan.</p>
                <div class="school-chip" id="selectedSchoolChip">
                    <i class="fas fa-school"></i>
                    <span id="selectedSchoolName">—</span>
                </div>
            </div>

            <div class="modal-body">
                <div class="modal-alert" id="registerAlert"></div>

                <form id="registerForm" novalidate>
                    <input type="hidden" id="selectedSchool" name="school_address" value="">

                    <!-- URI NG GUMAGAMIT (top) -->
                    <div class="section-title-group">Uri ng Gumagamit</div>

                    <div class="form-group">
                        <label for="userType">Uri ng Gumagamit <span class="req">*</span></label>
                        <select id="userType" name="user_type" required>
                            <option value="student" selected>Estudyante</option>
                            <option value="teacher">Guro</option>
                        </select>
                        <div class="err-msg" id="userTypeErr">Pumili ng uri ng gumagamit.</div>
                    </div>

                    <!-- SHARED -->
                    <div class="section-title-group">Pangunahing Impormasyon</div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">Pangalan <span class="req">*</span></label>
                            <input type="text" id="firstName" name="first_name" placeholder="Juan"
                                autocomplete="given-name" required>
                            <div class="err-msg" id="firstNameErr">Ilagay ang pangalan.</div>
                        </div>
                        <div class="form-group">
                            <label for="middleName">Gitnang Pangalan <span class="req">*</span></label>
                            <input type="text" id="middleName" name="middle_name" placeholder="Dela"
                                autocomplete="additional-name" required>
                            <div class="err-msg" id="middleNameErr">Ilagay ang gitnang pangalan.</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="surname">Apelyido <span class="req">*</span></label>
                        <input type="text" id="surname" name="surname" placeholder="Cruz" autocomplete="family-name"
                            required>
                        <div class="err-msg" id="surnameErr">Ilagay ang apelyido.</div>
                    </div>

                    <div class="section-title-group">Personal na Detalye</div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="sex">Kasarian <span class="req">*</span></label>
                            <select id="sex" name="sex" required>
                                <option value="">Pumili…</option>
                                <option value="Male">Lalaki</option>
                                <option value="Female">Babae</option>
                            </select>
                            <div class="err-msg" id="sexErr">Pumili ng kasarian.</div>
                        </div>

                        <div class="form-group">
                            <label for="age">Edad <span class="req">*</span></label>
                            <input type="number" id="age" name="age" placeholder="—" min="1" max="120" readonly
                                title="Awtomatikong kinakalkula mula sa petsa ng kapanganakan">
                            <div class="err-msg" id="ageErr">Ilagay muna ang petsa ng kapanganakan.</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="birthDate">Petsa ng Kapanganakan <span class="req">*</span></label>
                            <input type="date" id="birthDate" name="birth_date" required>
                            <div class="err-msg" id="birthDateErr">Ilagay ang petsa ng kapanganakan.</div>
                        </div>

                        <div class="form-group">
                            <label for="birthPlace">Lugar ng Kapanganakan <span class="req">*</span></label>
                            <input type="text" id="birthPlace" name="birth_place" placeholder="Dasol, Pangasinan"
                                required>
                            <div class="err-msg" id="birthPlaceErr">Ilagay ang lugar ng kapanganakan.</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Tirahan <span class="req">*</span></label>
                        <textarea id="address" name="address" placeholder="House No., Street, Barangay, Municipality"
                            required></textarea>
                        <div class="err-msg" id="addressErr">Ilagay ang tirahan.</div>
                    </div>

                    <div class="form-group">
                        <label for="contactNumber">Numero ng Telepono <span class="req">*</span></label>
                        <input type="tel" id="contactNumber" name="contact_number" placeholder="09171234567"
                            pattern="[0-9+\-\s]{7,15}" required>
                        <div class="err-msg" id="contactNumberErr">Ilagay ang numero ng telepono.</div>
                    </div>

                    <!-- STUDENT-ONLY -->
                    <div id="studentFields">
                        <div class="section-title-group">Impormasyon ng Estudyante</div>

                        <div class="form-group">
                            <label for="gradeLevel">Grade Level <span class="req">*</span></label>
                            <select id="gradeLevel" name="grade_level" required>
                                <option value="">Pumili…</option>
                                <option value="1">Grade 1</option>
                                <option value="2">Grade 2</option>
                                <option value="3">Grade 3</option>
                                <option value="4">Grade 4</option>
                                <option value="5">Grade 5</option>
                                <option value="6">Grade 6</option>
                                <option value="7">Grade 7</option>
                                <option value="8">Grade 8</option>
                                <option value="9">Grade 9</option>
                                <option value="10">Grade 10</option>
                                <option value="11">Grade 11</option>
                                <option value="12">Grade 12</option>
                            </select>
                            <div class="err-msg" id="gradeLevelErr">Pumili ng grade level.</div>
                        </div>

                        <div class="form-group">
                            <label for="studentStatus">Student Status <span class="req">*</span></label>
                            <select id="studentStatus" name="student_status" required>
                                <option value="">Pumili…</option>
                                <option value="O">Old Student</option>
                                <option value="N">New Student</option>
                                <option value="T">Transferee</option>
                            </select>
                            <div class="err-msg" id="studentStatusErr">Pumili ng student status.</div>
                        </div>

                        <div class="section-title-group">Tagapag-alaga</div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="guardian">Pangalan ng Tagapag-alaga <span class="req">*</span></label>
                                <input type="text" id="guardian" name="guardian" placeholder="Maria Cruz">
                                <div class="err-msg" id="guardianErr">Ilagay ang pangalan ng tagapag-alaga.</div>
                            </div>

                            <div class="form-group">
                                <label for="guardianContactNumber">Numero ng Tagapag-alaga <span
                                        class="req">*</span></label>
                                <input type="tel" id="guardianContactNumber" name="guardian_contact_number"
                                    placeholder="09181234567" pattern="[0-9+\-\s]{7,15}">
                                <div class="err-msg" id="guardianContactNumberErr">Ilagay ang numero ng
                                    tagapag-alaga.</div>
                            </div>
                        </div>
                    </div>

                    <!-- TEACHER-ONLY -->
                    <div id="teacherFields" style="display: none;">
                        <div class="section-title-group">Impormasyon ng Guro</div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="position">Posisyon <span class="req">*</span></label>
                                <select id="position" name="position">
                                    <option value="">Pumili…</option>
                                    <option value="Teacher I">Teacher I</option>
                                    <option value="Teacher II">Teacher II</option>
                                    <option value="Teacher III">Teacher III</option>
                                    <option value="Master Teacher I">Master Teacher I</option>
                                    <option value="Master Teacher II">Master Teacher II</option>
                                    <option value="Master Teacher III">Master Teacher III</option>
                                    <option value="Head Teacher I">Head Teacher I</option>
                                    <option value="Head Teacher II">Head Teacher II</option>
                                    <option value="Principal I">Principal I</option>
                                    <option value="Principal II">Principal II</option>
                                    <option value="Principal III">Principal III</option>
                                    <option value="Principal IV">Principal IV</option>
                                </select>
                                <div class="err-msg" id="positionErr">Pumili ng posisyon.</div>
                            </div>

                            <div class="form-group">
                                <label for="department">Departamento <span class="req">*</span></label>
                                <select id="department" name="department">
                                    <option value="">Pumili…</option>
                                    <option value="English">English</option>
                                    <option value="Filipino">Filipino</option>
                                    <option value="Mathematics">Mathematics</option>
                                    <option value="Science">Science</option>
                                    <option value="Araling Panlipunan">Araling Panlipunan</option>
                                    <option value="MAPEH">MAPEH</option>
                                    <option value="TLE">TLE</option>
                                    <option value="ESP">ESP</option>
                                </select>
                                <div class="err-msg" id="departmentErr">Pumili ng departamento.</div>
                            </div>
                        </div>
                    </div>

                    <!-- SECURITY -->
                    <div class="section-title-group">Seguridad</div>

                    <div class="form-group">
                        <label for="pin">Magsumite ng anim na numero <span class="req">*</span></label>
                        <input type="text" id="pin" name="pin" class="pin-input" placeholder="••••••"
                            inputmode="numeric" maxlength="6" pattern="\d{6}" autocomplete="off" required>
                        <div class="err-msg" id="pinErr">Dapat eksaktong 6 digit ang PIN.</div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-cancel" id="registerCancelBtn">
                            <i class="fas fa-xmark"></i> Kanselahin
                        </button>
                        <button type="submit" class="btn btn-submit" id="registerSubmitBtn">
                            <i class="fas fa-check"></i> I-submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="successModal" role="dialog" aria-modal="true" aria-labelledby="successModalTitle">
        <div class="modal-card success-card">
            <div class="modal-head success-head">
                <h3 id="successModalTitle">
                    <i class="fas fa-circle-check"></i>
                    Matagumpay ang Pag-rehistro!
                </h3>
                <p>Narito ang iyong account number para sa pag-login.</p>
            </div>

            <div class="modal-body success-body">
                <div class="account-label">
                    <i class="fas fa-id-card"></i>
                    Ito ang iyong Account Number para sa pag login. Itago ang account number na ito. Kakailanganin mo
                    ito para makapag-login.
                </div>

                <div class="account-display" id="successAccountNumber">ACC-00000</div>

                <div class="success-actions">
                    <button type="button" class="btn btn-submit" id="saveAccountBtn">
                        <i class="fas fa-copy"></i> Itabi
                    </button>
                </div>
            </div>
        </div>
    </div>

    <section class="hero">
        <div class="container">
            <div class="hero-inner">
                <div class="hero-brand-row">
                    <div class="hero-brand">
                        <div class="hero-brand-logo">
                            <img src="Images/logo.png" alt="CampusLink" onerror="this.style.display='none'">
                        </div>
                        <div class="hero-brand-text">
                            <span class="brand-name">CampusLink</span>
                            <span class="brand-tag">Pangasinan · District I &amp; II</span>
                        </div>
                    </div>
                </div>

                <div class="hero-badge">
                    <span class="dot"></span>
                    SY 2025–2026 · Bukas na ang Enrollment sa CampusLink
                </div>

                <h1>Isang portal para sa <span class="grad">lahat ng paaralan sa Dasol.</span></h1>

                <p class="lead">
                    Mula elementary hanggang senior high — hanapin ang paaralan mo,
                    mag-enroll online, tingnan ang grades at schedule, at bayaran ang fees.
                    Isang login. Lahat ng kailangan mo.
                </p>
            </div>
        </div>
    </section>

    <section class="section directory" id="schools">
        <div class="container">
            <div class="section-head reveal">
                <span class="eyebrow">Direktoryo ng Paaralan</span>
                <h2 class="section-title">Lahat ng paaralan sa Dasol, isang lugar</h2>
                <p class="section-sub">
                    Piliin ang paaralan para makapasok sa kanilang portal. May filter at search
                    para mabilis mong mahanap ang kailangan mo.
                </p>
            </div>

            <div class="dir-toolbar reveal">
                <div class="filter-tabs" id="filterTabs">
                    <button class="active" data-filter="all">Lahat</button>
                    <button data-filter="elementary">Elementarya</button>
                    <button data-filter="junior">Junior High</button>
                    <button data-filter="senior">Senior High</button>
                </div>
            </div>

            <div class="dir-count reveal">
                <b id="schoolCount">0</b> paaralan ang nakita
            </div>

            <div class="school-grid" id="schoolGrid"></div>
        </div>
    </section>

    <section class="section services" id="services">
        <div class="container">
            <div class="section-head center reveal">
                <span class="eyebrow">Serbisyo</span>
                <h2 class="section-title">Lahat ng serbisyo, isang click lang</h2>
                <p class="section-sub">
                    Mula sa enrollment hanggang sa pagtingin ng grades at pagbabayad ng fees —
                    nakaayos lahat para sa mga estudyante, magulang, at guro.
                </p>
            </div>

            <div class="svc-grid">
                <article class="svc-card reveal">
                    <div class="svc-icon ic-blue"><i class="fas fa-user-graduate"></i></div>
                    <h3>Student and Teacher Portal</h3>
                    <p>Personal na dashboard para sa iyong profile, attendance, at mga aktibidad sa school.</p>
                    <a href="#" class="svc-link">Pumasok <i class="fas fa-arrow-right"></i></a>
                </article>

                <article class="svc-card reveal">
                    <div class="svc-icon ic-teal"><i class="fas fa-chart-line"></i></div>
                    <h3>Grades & Records</h3>
                    <p>Tingnan ang iyong mga marka kada quarter at i-download ang official record ng grades.</p>
                    <a href="#" class="svc-link">Tingnan <i class="fas fa-arrow-right"></i></a>
                </article>

                <article class="svc-card reveal">
                    <div class="svc-icon ic-amber"><i class="fas fa-clipboard-list"></i></div>
                    <h3>Online Enrollment</h3>
                    <p>Mag-enroll o mag-rehistro nang hindi na kailangang pumila sa school.</p>
                    <a href="#" class="svc-link">Mag-enroll <i class="fas fa-arrow-right"></i></a>
                </article>

                <article class="svc-card reveal">
                    <div class="svc-icon ic-violet"><i class="fas fa-calendar-days"></i></div>
                    <h3>Class Schedule</h3>
                    <p>Buong timetable mo para sa buong semester, may reminders para sa mga exam.</p>
                    <a href="#" class="svc-link">Tingnan <i class="fas fa-arrow-right"></i></a>
                </article>

                <article class="svc-card reveal">
                    <div class="svc-icon ic-green"><i class="fas fa-book-open"></i></div>
                    <h3>Digital Library</h3>
                    <p>Libu-libong e-books, modules, at learning materials na pwedeng basahin online.</p>
                    <a href="#" class="svc-link">Magbasa <i class="fas fa-arrow-right"></i></a>
                </article>

                <article class="svc-card reveal">
                    <div class="svc-icon ic-rose"><i class="fas fa-credit-card"></i></div>
                    <h3>Payments & Fees</h3>
                    <p>Bayaran ang tuition at iba pang fees online — may resibo agad pagkatapos.</p>
                    <a href="#" class="svc-link">Magbayad <i class="fas fa-arrow-right"></i></a>
                </article>
            </div>
        </div>
    </section>

    <section class="stats" id="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat reveal">
                    <div class="num" data-count="<?php echo count($schoolsData); ?>" data-suffix="">0</div>
                    <div class="lbl">All Schools</div>
                </div>
                <div class="stat reveal">
                    <div class="num" data-count="<?php echo $totalStudents; ?>" data-suffix="+">0</div>
                    <div class="lbl">All Students</div>
                </div>
                <div class="stat reveal">
                    <div class="num">0</div>
                    <div class="lbl">Dedicated Teachers</div>
                </div>
                <div class="stat reveal">
                    <div class="num">0</div>
                    <div class="lbl">Graduation Rate</div>
                </div>
            </div>
        </div>
    </section>

    <section class="section news" id="news">
        <div class="container">
            <div class="section-head reveal">
                <span class="eyebrow">Balita at Anunsyo</span>
                <h2 class="section-title">Mga pinakabagong balita sa Dasol</h2>
                <p class="section-sub">Manatiling updated sa mga anunsyo mula sa mga paaralan at sa buong
                    distrito.</p>
            </div>

            <div class="news-grid">
                <article class="news-card reveal">
                    <div class="news-thumb t-blue">
                        <i class="fas fa-paper-plane"></i>
                        <span class="tag">Enrollment</span>
                    </div>
                    <div class="news-body">
                        <div class="news-date">Hunyo 3, 2025</div>
                        <h3>Bukas na ang Online Enrollment para sa SY 2025–2026</h3>
                        <p>Simula Hunyo 3, tatanggap na ng enrollment ang lahat ng pampublikong paaralan sa Dasol sa
                            pamamagitan ng portal.</p>
                        <a href="#" class="svc-link">Magbasa pa <i class="fas fa-arrow-right"></i></a>
                    </div>
                </article>

                <article class="news-card reveal">
                    <div class="news-thumb t-teal">
                        <i class="fas fa-star"></i>
                        <span class="tag">Achievement</span>
                    </div>
                    <div class="news-body">
                        <div class="news-date">Mayo 28, 2025</div>
                        <h3>Dasol NHS, nagwagi sa Regional Science Fair 2025</h3>
                        <p>Nag-uwi ng gintong medalya ang mga estudyante ng Dasol National High School sa ginanap na
                            Regional Science Fair.</p>
                        <a href="#" class="svc-link">Magbasa pa <i class="fas fa-arrow-right"></i></a>
                    </div>
                </article>

                <article class="news-card reveal">
                    <div class="news-thumb t-amber">
                        <i class="fas fa-calendar-days"></i>
                        <span class="tag">Schedule</span>
                    </div>
                    <div class="news-body">
                        <div class="news-date">Mayo 20, 2025</div>
                        <h3>Schedule ng Brigada Eskwela 2025, inilabas na</h3>
                        <p>Narito ang kumpletong schedule ng Brigada Eskwela para sa lahat ng paaralan sa Dasol.
                            Sumali at makiisa!</p>
                        <a href="#" class="svc-link">Magbasa pa <i class="fas fa-arrow-right"></i></a>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="cta-wrap" id="contact">
        <div class="container">
            <div class="cta reveal">
                <h2>Handa ka na bang mag-enroll?</h2>
                <p>Mag-login gamit ang iyong school account o mag-rehistro para sa bagong school year. Mabilis at
                    madali lang!</p>
                <div class="cta-actions">
                    <a href="#" class="btn btn-light">Mag-login Ngayon <i class="fas fa-arrow-right"></i></a>
                    <a href="#" class="btn btn-ghost">Mag-rehistro</a>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <a href="#" class="logo">
                        <div class="logo-mark">
                            <img src="Images/logo.png" alt="Eguia National High School logo"
                                onerror="this.style.display='none'">
                        </div>
                        <div class="logo-text">
                            <b>Eguia National High School</b>
                            <span>Pangasinan · District I & II</span>
                        </div>
                    </a>
                    <p class="footer-about">
                        Ang opisyal na portal ng Eguia National High School sa Dasol, Pangasinan.
                        Nagbibigay ng mabilis at secure na access sa mga serbisyo ng paaralan.
                    </p>
                </div>

                <div>
                    <h4>Mga Link</h4>
                    <ul>
                        <li><a href="#schools">Mga Paaralan</a></li>
                        <li><a href="#services">Serbisyo</a></li>
                        <li><a href="#news">Balita</a></li>
                        <li><a href="#">Transparency Seal</a></li>
                    </ul>
                </div>

                <div>
                    <h4>Suporta</h4>
                    <ul>
                        <li><a href="#">Helpdesk</a></li>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Use</a></li>
                    </ul>
                </div>

                <div>
                    <h4>Kontak</h4>
                    <ul class="footer-contact">
                        <li><i class="fas fa-location-dot"></i> Dasol, Pangasinan, Philippines</li>
                        <li><i class="fas fa-phone"></i> (075) 123-4567</li>
                        <li><i class="fas fa-envelope"></i> info@eguianhs.edu.ph</li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2025 Eguia National High School. All rights reserved.</p>
                <div class="footer-socials">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        const schoolsData = <?php echo json_encode($schoolsData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

        function getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        }

        function normalizeType(t) {
            const v = (t || '').toString().trim().toLowerCase();
            if (v === 'senior' || v.includes('senior')) return { type: 'senior', typeLabel: 'Senior High' };
            if (v === 'junior' || v.includes('junior')) return { type: 'junior', typeLabel: 'Junior High' };
            if (v === 'elementary' || v.includes('elem')) return { type: 'elementary', typeLabel: 'Elementarya' };
            if (v.includes('integrat')) return { type: 'junior', typeLabel: 'Junior High' };
            return { type: 'elementary', typeLabel: 'Paaralan' };
        }

        function deriveAbbr(name) {
            const stop = ['SCHOOL', 'ELEMENTARY', 'INTEGRATED', 'NATIONAL', 'HIGH', 'SENIOR', 'MEMORIAL', 'SR.', 'SR'];
            const words = (name || '').split(/\s+/).filter(w => w && !stop.includes(w.toUpperCase()));
            const abbr = words.map(w => w[0]).join('').toUpperCase();
            return (abbr || (name || '?').substring(0, 3)).slice(0, 5);
        }

        const schools = (schoolsData || []).map(row => {
            const name = row.school_name || '';
            const address = row.school_address || '';
            const { type, typeLabel } = normalizeType(row.school_type);
            return { name, address, abbr: deriveAbbr(name), type, typeLabel };
        });

        /* NAV */
        const nav = document.getElementById('nav');
        window.addEventListener('scroll', () => {
            nav.classList.toggle('scrolled', window.scrollY > 10);
        }, { passive: true });

        const navToggle = document.getElementById('navToggle');
        const mobileMenu = document.getElementById('mobileMenu');

        navToggle.addEventListener('click', () => {
            const open = mobileMenu.classList.toggle('open');
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => mobileMenu.classList.remove('open'));
        });

        document.addEventListener('click', (e) => {
            if (!mobileMenu.contains(e.target) && !navToggle.contains(e.target)) {
                mobileMenu.classList.remove('open');
                navToggle.setAttribute('aria-expanded', 'false');
            }
        });

        /* DESKTOP REGISTER DROPDOWN */
        const loginDropdown = document.getElementById('loginDropdown');
        const loginDropdownBtn = document.getElementById('loginDropdownBtn');

        loginDropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            loginDropdown.classList.toggle('open');
        });

        document.addEventListener('click', (e) => {
            if (!loginDropdown.contains(e.target)) loginDropdown.classList.remove('open');
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') loginDropdown.classList.remove('open');
        });

        /* LOGIN MODAL */
        const loginModal = document.getElementById('loginModal');
        const loginOpenBtn = document.getElementById('loginOpenBtn');
        const mobileLoginBtn = document.getElementById('mobileLoginBtn');
        const loginModalClose = document.getElementById('loginModalClose');
        const loginCancelBtn = document.getElementById('loginCancelBtn');
        const loginForm = document.getElementById('loginForm');
        const loginSubmitBtn = document.getElementById('loginSubmitBtn');
        const loginAlert = document.getElementById('loginAlert');
        const loginAccountInput = document.getElementById('loginAccount');
        const loginPinInput = document.getElementById('loginPin');
        const loginAccountErr = document.getElementById('loginAccountErr');
        const loginPinErr = document.getElementById('loginPinErr');

        function openLoginModal() {
            loginForm.reset();
            loginClearErrors();
            loginAlert.className = 'modal-alert';
            loginAlert.textContent = '';
            loginSubmitBtn.disabled = false;
            loginSubmitBtn.innerHTML = '<i class="fas fa-right-to-bracket"></i> Mag-login';

            loginModal.classList.add('show');
            document.body.style.overflow = 'hidden';
            setTimeout(() => loginAccountInput.focus(), 250);
        }

        function closeLoginModal() {
            loginModal.classList.remove('show');
            document.body.style.overflow = '';
        }

        function loginClearErrors() {
            [loginAccountInput, loginPinInput].forEach(i => i.classList.remove('error'));
            [loginAccountErr, loginPinErr].forEach(e => e.classList.remove('show'));
        }

        loginOpenBtn.addEventListener('click', openLoginModal);
        if (mobileLoginBtn) {
            mobileLoginBtn.addEventListener('click', () => {
                mobileMenu.classList.remove('open');
                navToggle.setAttribute('aria-expanded', 'false');
                openLoginModal();
            });
        }

        document.querySelectorAll('.svc-card').forEach(card => {
            card.addEventListener('click', (e) => {
                e.preventDefault();
                openLoginModal();
            });
        });

        loginModalClose.addEventListener('click', closeLoginModal);
        loginCancelBtn.addEventListener('click', closeLoginModal);
        loginModal.addEventListener('click', (e) => {
            if (e.target === loginModal) closeLoginModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && loginModal.classList.contains('show')) closeLoginModal();
        });

        loginPinInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 6);
        });

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            loginClearErrors();
            loginAlert.className = 'modal-alert';
            loginAlert.textContent = '';

            let valid = true;
            if (!loginAccountInput.value.trim()) {
                loginAccountInput.classList.add('error');
                loginAccountErr.classList.add('show');
                valid = false;
            }
            if (!/^\d{6}$/.test(loginPinInput.value.trim())) {
                loginPinInput.classList.add('error');
                loginPinErr.classList.add('show');
                valid = false;
            }
            if (!valid) return;

            loginSubmitBtn.disabled = true;
            loginSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Nagla-login...';

            try {
                const fd = new FormData();
                fd.append('account_number', loginAccountInput.value.trim());
                fd.append('pin', loginPinInput.value.trim());
                fd.append('csrf_token', getCsrfToken());

                const res = await fetch('API_SYSTEM/login.php', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                });
                const data = await res.json();

                if (data.success) {
                    loginAlert.className = 'modal-alert success show';
                    loginAlert.textContent = 'Matagumpay ang pag-login! Nire-redirect ka...';
                    setTimeout(() => {
                        window.location.href = data.redirect || 'dashboard.php';
                    }, 1000);
                } else {
                    loginAlert.className = 'modal-alert error show';
                    loginAlert.textContent = data.message || 'Mali ang account number o PIN.';
                    loginSubmitBtn.disabled = false;
                    loginSubmitBtn.innerHTML = '<i class="fas fa-right-to-bracket"></i> Mag-login';
                }
            } catch (err) {
                console.error(err);
                loginAlert.className = 'modal-alert error show';
                loginAlert.textContent = 'Network error. Subukan muli.';
                loginSubmitBtn.disabled = false;
                loginSubmitBtn.innerHTML = '<i class="fas fa-right-to-bracket"></i> Mag-login';
            }
        });

        /* REGISTER MODAL */
        const registerModal = document.getElementById('registerModal');
        const registerModalClose = document.getElementById('registerModalClose');
        const registerCancelBtn = document.getElementById('registerCancelBtn');
        const registerForm = document.getElementById('registerForm');
        const registerSubmitBtn = document.getElementById('registerSubmitBtn');
        const registerAlert = document.getElementById('registerAlert');
        const selectedSchoolInput = document.getElementById('selectedSchool');
        const selectedSchoolName = document.getElementById('selectedSchoolName');

        // Shared inputs
        const firstNameInput = document.getElementById('firstName');
        const middleNameInput = document.getElementById('middleName');
        const surnameInput = document.getElementById('surname');
        const userTypeSelect = document.getElementById('userType');
        const sexSelect = document.getElementById('sex');
        const ageInput = document.getElementById('age');
        const birthDateInput = document.getElementById('birthDate');
        const birthPlaceInput = document.getElementById('birthPlace');
        const addressInput = document.getElementById('address');
        const contactNumberInput = document.getElementById('contactNumber');
        const pinInput = document.getElementById('pin');

        // Student-only
        const studentFields = document.getElementById('studentFields');
        const guardianInput = document.getElementById('guardian');
        const guardianContactNumberInput = document.getElementById('guardianContactNumber');
        const gradeLevelSelect = document.getElementById('gradeLevel');
        const studentStatusSelect = document.getElementById('studentStatus');

        // Teacher-only
        const teacherFields = document.getElementById('teacherFields');
        const positionSelect = document.getElementById('position');
        const departmentSelect = document.getElementById('department');

        // Error elements
        const firstNameErr = document.getElementById('firstNameErr');
        const middleNameErr = document.getElementById('middleNameErr');
        const surnameErr = document.getElementById('surnameErr');
        const userTypeErr = document.getElementById('userTypeErr');
        const sexErr = document.getElementById('sexErr');
        const ageErr = document.getElementById('ageErr');
        const birthDateErr = document.getElementById('birthDateErr');
        const birthPlaceErr = document.getElementById('birthPlaceErr');
        const addressErr = document.getElementById('addressErr');
        const contactNumberErr = document.getElementById('contactNumberErr');
        const guardianErr = document.getElementById('guardianErr');
        const guardianContactNumberErr = document.getElementById('guardianContactNumberErr');
        const gradeLevelErr = document.getElementById('gradeLevelErr');
        const studentStatusErr = document.getElementById('studentStatusErr');
        const positionErr = document.getElementById('positionErr');
        const departmentErr = document.getElementById('departmentErr');
        const pinErr = document.getElementById('pinErr');

        /* Toggle fields by user type */
        function applyUserTypeVisibility() {
            const isTeacher = userTypeSelect.value === 'teacher';

            if (isTeacher) {
                studentFields.style.display = 'none';
                teacherFields.style.display = '';
                guardianInput.value = '';
                guardianContactNumberInput.value = '';
                gradeLevelSelect.value = '';
                studentStatusSelect.value = '';
                guardianInput.classList.remove('error');
                guardianContactNumberInput.classList.remove('error');
                gradeLevelSelect.classList.remove('error');
                studentStatusSelect.classList.remove('error');
                guardianErr.classList.remove('show');
                guardianContactNumberErr.classList.remove('show');
                gradeLevelErr.classList.remove('show');
                studentStatusErr.classList.remove('show');
            } else {
                studentFields.style.display = '';
                teacherFields.style.display = 'none';
                positionSelect.value = '';
                departmentSelect.value = '';
                positionSelect.classList.remove('error');
                departmentSelect.classList.remove('error');
                positionErr.classList.remove('show');
                departmentErr.classList.remove('show');
            }
        }

        userTypeSelect.addEventListener('change', applyUserTypeVisibility);

        /* Auto-age */
        function calculateAge(dateStr) {
            if (!dateStr) return '';
            const today = new Date();
            const birth = new Date(dateStr + 'T00:00:00');
            if (isNaN(birth.getTime())) return '';
            let age = today.getFullYear() - birth.getFullYear();
            const m = today.getMonth() - birth.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
            if (age < 0) return '';
            if (age > 120) return 120;
            return age;
        }

        function updateAgeFromBirthDate() {
            const age = calculateAge(birthDateInput.value);
            ageInput.value = age === '' ? '' : age;
            if (age !== '' && age > 0) {
                birthDateInput.classList.remove('error');
                birthDateErr.classList.remove('show');
                ageInput.classList.remove('error');
                ageErr.classList.remove('show');
            }
        }

        birthDateInput.addEventListener('change', updateAgeFromBirthDate);
        birthDateInput.addEventListener('input', updateAgeFromBirthDate);

        function openRegisterModal(schoolAddress) {
            registerForm.reset();
            clearErrors();
            registerAlert.className = 'modal-alert';
            registerAlert.textContent = '';
            registerSubmitBtn.disabled = false;
            registerSubmitBtn.innerHTML = '<i class="fas fa-check"></i> I-submit';

            selectedSchoolInput.value = schoolAddress;
            selectedSchoolName.textContent = schoolAddress;

            userTypeSelect.value = 'student';
            applyUserTypeVisibility();
            ageInput.value = '';
            gradeLevelSelect.value = '';
            studentStatusSelect.value = '';

            registerModal.classList.add('show');
            document.body.style.overflow = 'hidden';
            setTimeout(() => firstNameInput.focus(), 250);
        }

        function closeRegisterModal() {
            registerModal.classList.remove('show');
            document.body.style.overflow = '';
        }

        function clearErrors() {
            const inputs = [
                firstNameInput, middleNameInput, surnameInput, userTypeSelect, sexSelect,
                ageInput, birthDateInput, birthPlaceInput, addressInput,
                contactNumberInput, guardianInput, guardianContactNumberInput,
                gradeLevelSelect, studentStatusSelect, positionSelect, departmentSelect, pinInput
            ];
            const errs = [
                firstNameErr, middleNameErr, surnameErr, userTypeErr, sexErr, ageErr,
                birthDateErr, birthPlaceErr, addressErr, contactNumberErr,
                guardianErr, guardianContactNumberErr, gradeLevelErr, studentStatusErr,
                positionErr, departmentErr, pinErr
            ];
            inputs.forEach(i => { if (i) i.classList.remove('error'); });
            errs.forEach(e => { if (e) e.classList.remove('show'); });
        }

        function setError(input, errEl, message) {
            if (input) input.classList.add('error');
            if (message && errEl) errEl.textContent = message;
            if (errEl) errEl.classList.add('show');
        }

        document.querySelectorAll('[data-school]').forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                const school = el.getAttribute('data-school');
                if (!school) return;
                loginDropdown.classList.remove('open');
                mobileMenu.classList.remove('open');
                navToggle.setAttribute('aria-expanded', 'false');
                openRegisterModal(school);
            });
        });

        document.addEventListener('click', (e) => {
            const card = e.target.closest('.school-card');
            if (card) {
                e.preventDefault();
                const schoolName = card.getAttribute('data-school-name');
                if (schoolName) openRegisterModal(schoolName);
            }
        });

        registerModalClose.addEventListener('click', closeRegisterModal);
        registerCancelBtn.addEventListener('click', closeRegisterModal);
        registerModal.addEventListener('click', (e) => {
            if (e.target === registerModal) closeRegisterModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && registerModal.classList.contains('show')) closeRegisterModal();
        });

        pinInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 6);
        });

        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();
            registerAlert.className = 'modal-alert';
            registerAlert.textContent = '';

            updateAgeFromBirthDate();

            const isTeacher = userTypeSelect.value === 'teacher';

            let valid = true;
            if (!firstNameInput.value.trim()) { setError(firstNameInput, firstNameErr, 'Ilagay ang pangalan.'); valid = false; }
            if (!middleNameInput.value.trim()) { setError(middleNameInput, middleNameErr, 'Ilagay ang gitnang pangalan.'); valid = false; }
            if (!surnameInput.value.trim()) { setError(surnameInput, surnameErr, 'Ilagay ang apelyido.'); valid = false; }
            if (!['student', 'teacher'].includes(userTypeSelect.value)) { setError(userTypeSelect, userTypeErr, 'Pumili ng uri ng gumagamit.'); valid = false; }
            if (!['Male', 'Female'].includes(sexSelect.value)) { setError(sexSelect, sexErr, 'Pumili ng kasarian.'); valid = false; }
            if (!birthDateInput.value) { setError(birthDateInput, birthDateErr, 'Ilagay ang petsa ng kapanganakan.'); valid = false; }
            if (!ageInput.value || isNaN(parseInt(ageInput.value, 10)) || parseInt(ageInput.value, 10) < 1) { setError(ageInput, ageErr, 'Awtomatikong makakalkula ang edad.'); valid = false; }
            if (!birthPlaceInput.value.trim()) { setError(birthPlaceInput, birthPlaceErr, 'Ilagay ang lugar ng kapanganakan.'); valid = false; }
            if (!addressInput.value.trim()) { setError(addressInput, addressErr, 'Ilagay ang tirahan.'); valid = false; }
            if (!contactNumberInput.value.trim()) { setError(contactNumberInput, contactNumberErr, 'Ilagay ang numero ng telepono.'); valid = false; }

            if (!isTeacher) {
                if (!guardianInput.value.trim()) { setError(guardianInput, guardianErr, 'Ilagay ang pangalan ng tagapag-alaga.'); valid = false; }
                if (!guardianContactNumberInput.value.trim()) { setError(guardianContactNumberInput, guardianContactNumberErr, 'Ilagay ang numero ng tagapag-alaga.'); valid = false; }
                if (!gradeLevelSelect.value) { setError(gradeLevelSelect, gradeLevelErr, 'Pumili ng grade level.'); valid = false; }
                if (!['O', 'T'].includes(studentStatusSelect.value)) { setError(studentStatusSelect, studentStatusErr, 'Pumili ng student status.'); valid = false; }
            } else {
                if (!positionSelect.value) { setError(positionSelect, positionErr, 'Pumili ng posisyon.'); valid = false; }
                if (!departmentSelect.value) { setError(departmentSelect, departmentErr, 'Pumili ng departamento.'); valid = false; }
            }

            if (!/^\d{6}$/.test(pinInput.value.trim())) { setError(pinInput, pinErr, 'Dapat eksaktong 6 digit ang PIN.'); valid = false; }
            if (!selectedSchoolInput.value) { registerAlert.className = 'modal-alert error show'; registerAlert.textContent = 'Pumili muna ng paaralan.'; valid = false; }

            if (!valid) return;

            registerSubmitBtn.disabled = true;
            registerSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Isinusumite...';

            try {
                const fd = new FormData();
                fd.append('first_name', firstNameInput.value.trim());
                fd.append('middle_name', middleNameInput.value.trim());
                fd.append('surname', surnameInput.value.trim());
                fd.append('user_type', userTypeSelect.value);
                fd.append('sex', sexSelect.value);
                fd.append('age', ageInput.value.trim());
                fd.append('birth_date', birthDateInput.value);
                fd.append('birth_place', birthPlaceInput.value.trim());
                fd.append('address', addressInput.value.trim());
                fd.append('contact_number', contactNumberInput.value.trim());
                fd.append('pin', pinInput.value.trim());
                fd.append('school_address', selectedSchoolInput.value);
                fd.append('csrf_token', getCsrfToken());

                if (!isTeacher) {
                    fd.append('guardian', guardianInput.value.trim());
                    fd.append('guardian_contact_number', guardianContactNumberInput.value.trim());
                    fd.append('grade_level', gradeLevelSelect.value);
                    fd.append('student_status', studentStatusSelect.value);
                } else {
                    fd.append('position', positionSelect.value);
                    fd.append('department', departmentSelect.value);
                }

                const endpoint = isTeacher
                    ? 'API_SYSTEM/register_teacher.php'
                    : 'API_SYSTEM/register_student.php';

                const res = await fetch(endpoint, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                });
                const data = await res.json();

                if (data.success) {
                    if (data.account_number) {
                        loginAccountInput.value = data.account_number;
                    }

                    const keepSchool = selectedSchoolInput.value;
                    closeRegisterModal();
                    registerForm.reset();
                    selectedSchoolInput.value = keepSchool;
                    selectedSchoolName.textContent = keepSchool || '—';

                    if (data.account_number) {
                        openSuccessModal(data.account_number);
                    } else {
                        openLoginModal();
                    }
                } else {
                    registerAlert.className = 'modal-alert error show';
                    registerAlert.textContent = data.message || 'May error sa pagrehistro. Subukan muli.';
                    registerSubmitBtn.disabled = false;
                    registerSubmitBtn.innerHTML = '<i class="fas fa-check"></i> I-submit';
                }
            } catch (err) {
                console.error(err);
                registerAlert.className = 'modal-alert error show';
                registerAlert.textContent = 'Network error. Subukan muli.';
                registerSubmitBtn.disabled = false;
                registerSubmitBtn.innerHTML = '<i class="fas fa-check"></i> I-submit';
            }
        });

        /* SUCCESS POPUP */
        const successModal = document.getElementById('successModal');
        const successAccountNumber = document.getElementById('successAccountNumber');
        const saveAccountBtn = document.getElementById('saveAccountBtn');

        function openSuccessModal(accountNumber) {
            successAccountNumber.textContent = accountNumber;
            successAccountNumber.classList.remove('copied');
            saveAccountBtn.innerHTML = '<i class="fas fa-copy"></i> Itabi';
            successModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeSuccessModal() {
            successModal.classList.remove('show');
            document.body.style.overflow = '';
        }

        async function copyAccountNumber() {
            const value = successAccountNumber.textContent.trim();
            try {
                await navigator.clipboard.writeText(value);
            } catch (err) {
                const tmp = document.createElement('textarea');
                tmp.value = value;
                tmp.style.position = 'fixed';
                tmp.style.opacity = '0';
                document.body.appendChild(tmp);
                tmp.select();
                try { document.execCommand('copy'); } catch (_) { }
                document.body.removeChild(tmp);
            }
            saveAccountBtn.innerHTML = '<i class="fas fa-check"></i> Naitabi!';
            successAccountNumber.classList.add('copied');
            setTimeout(() => {
                saveAccountBtn.innerHTML = '<i class="fas fa-copy"></i> Itabi';
                successAccountNumber.classList.remove('copied');
            }, 2200);
        }

        saveAccountBtn.addEventListener('click', copyAccountNumber);
        successAccountNumber.addEventListener('click', copyAccountNumber);

        successModal.addEventListener('click', (e) => {
            if (e.target === successModal) closeSuccessModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && successModal.classList.contains('show')) closeSuccessModal();
        });

        /* DIRECTORY */
        const schoolGrid = document.getElementById('schoolGrid');
        const schoolCount = document.getElementById('schoolCount');
        let currentFilter = 'all';

        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function renderSchools() {
            const filtered = schools.filter(s => {
                return currentFilter === 'all' || s.type === currentFilter;
            });

            schoolCount.textContent = filtered.length;

            if (filtered.length === 0) {
                schoolGrid.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-magnifying-glass"></i></div>
                        <h3>Walang nahanap na paaralan</h3>
                        <p>Subukan ang ibang filter.</p>
                    </div>`;
                return;
            }

            schoolGrid.innerHTML = filtered.map(s => `
                    <a href="#" class="school-card" aria-label="${escapeHtml(s.name)}" data-school-name="${escapeHtml(s.name)}">
                    <div class="school-logo ${escapeHtml(s.type)}">${escapeHtml(s.abbr)}</div>
                    <div class="school-body">
                        <span class="school-type ${escapeHtml(s.type)}">${escapeHtml(s.typeLabel)}</span>
                        <h3 title="${escapeHtml(s.name)}">${escapeHtml(s.name)}</h3>
                        <p title="${escapeHtml(s.address)}">${escapeHtml(s.address)}</p>
                    </div>
                    <div class="school-link"><i class="fas fa-chevron-right"></i></div>
                    </a>
                `).join('');
        }

        document.querySelectorAll('#filterTabs button').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('#filterTabs button').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                 
                 
                 
                currentFilter = btn.dataset.filter;
                renderSchools();
            });
        });

        /* REVEAL / COUNTERS */
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                const target = parseInt(el.dataset.count, 10);
                const suffix = el.dataset.suffix || '';
                const duration = 1400;
                const startTime = performance.now();

                function tick(now) {
                    const progress = Math.min((now - startTime) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const value = Math.floor(eased * target);
                    el.textContent = value.toLocaleString() + suffix;
                    if (progress < 1) requestAnimationFrame(tick);
                    else el.textContent = target.toLocaleString() + suffix;
                }
                requestAnimationFrame(tick);
                counterObserver.unobserve(el);
            });
        }, { threshold: 0.4 });

        document.querySelectorAll('[data-count]').forEach(el => counterObserver.observe(el));

        renderSchools();
    </script>
</body>

</html>