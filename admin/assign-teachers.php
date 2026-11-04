<?php
// admin/assign-teachers.php — Assign subjects for a specific teacher (teacher comes from URL)
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
   AUTH — must be admin
   ------------------------------------------------------------ */
$isAdmin = false;
$adminUser = null;

if (!empty($_SESSION['account_number']) && is_string($_SESSION['account_number'])) {
    $accountNumber = trim($_SESSION['account_number']);
    $formatOk = (bool) preg_match('/^26[A-Z]{1,8}_A\d{1,7}$/', $accountNumber);

    if ($formatOk) {
        try {
            $stmt = $pdo->prepare("
                SELECT *
                FROM admins
                WHERE account_number = ?
                  AND user_type = 'admin'
                LIMIT 1
            ");
            $stmt->execute([$accountNumber]);
            $adminUser = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if (!$adminUser) {
                header('Location: ../index.php');
                exit;
            }
            $isAdmin = true;
        } catch (PDOException $e) {
            error_log("admin fetch failed: " . $e->getMessage());
            http_response_code(500);
            echo "Database error. Please try again later.";
            exit;
        }
    } else {
        header('Location: ../index.php');
        exit;
    }
} else {
    header('Location: ../index.php');
    exit;
}

/* ------------------------------------------------------------
   ADMIN SCHOOL
   ------------------------------------------------------------ */
$adminSchoolName = trim((string) ($adminUser['school_name'] ?? ''));
if ($adminSchoolName === '') {
    $adminSchoolName = 'Eguia National High School';
}

/* ------------------------------------------------------------
   FLASH
   ------------------------------------------------------------ */
$flashError = $_SESSION['flash']['admin_error'] ?? null;
$flashSuccess = $_SESSION['flash']['admin_success'] ?? null;
unset($_SESSION['flash']['admin_error'], $_SESSION['flash']['admin_success']);

/* ------------------------------------------------------------
   READ TEACHER FROM URL (?teacher=ACCOUNT_NUMBER)
   ------------------------------------------------------------ */
$teacherAccount = trim((string) ($_GET['teacher'] ?? ''));
$teacher = null;
$teacherError = '';

if ($teacherAccount === '') {
    $teacherError = 'No teacher specified. Please pick a teacher from the teachers list.';
} else {
    try {
        $stmt = $pdo->prepare("
            SELECT
                id, account_number, first_name, middle_name, surname,
                age, sex, birth_date, birth_place, address,
                contact_number, position, department, school_name,
                registered_date
            FROM teachers
            WHERE account_number = ?
            LIMIT 1
        ");
        $stmt->execute([$teacherAccount]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$teacher) {
            $teacherError = 'Teacher not found. The account number in the URL is invalid.';
        }
    } catch (PDOException $e) {
        error_log("teacher lookup failed: " . $e->getMessage());
        $teacherError = 'Database error while loading teacher.';
    }
}

$teacherDisplayName = '';
if ($teacher) {
    $teacherDisplayName = trim(
        ($teacher['first_name'] ?? '') . ' ' .
        ($teacher['middle_name'] ?? '') . ' ' .
        ($teacher['surname'] ?? '')
    );
}

$adminDisplayName = trim(
    ($adminUser['first_name'] ?? '') . ' ' .
    ($adminUser['middle_name'] ?? '') . ' ' .
    ($adminUser['surname'] ?? '')
);

/* ------------------------------------------------------------
   GRADE + SECTION MAP + SUBJECTS + DAYS
   ------------------------------------------------------------ */
$gradeOptions = ['7', '8', '9', '10', '11', '12'];

$sectionOptionsByGrade = [
    '7' => ['Ilang-Ilang', 'Dahlia', 'Rosal', 'Sampaguita'],
    '8' => ['Acacia', 'Yakal', 'Molave', 'Mahogany'],
    '9' => ['Mabini', 'Rizal', 'Jacinto', 'Bonifacio'],
    '10' => ['Emerald', 'Diamond', 'Jade'],
    '11' => ['Home Economics - Pasteur', 'Einstein', 'Socrates', 'Franklin'],
    '12' => ['Automotive Servicing', 'TVL - HE', 'STEM', 'Humanities & Social Science'],
];

$subjectOptions = [
    'English',
    'Filipino',
    'Mathematics',
    'Science',
    'Araling Panlipunan',
    'Edukasyon sa Pagpapakatao (ESP)',
    'MAPEH',
    'TLE',
    'Computer Science',
    'Physical Education',
    'Music',
    'Arts',
    'Health'
];

$dayOptions = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
?>
<!DOCTYPE html>
<html lang="fil">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>" />
    <title>Assign Subjects — Eguia National High School</title>

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

        /* ── TEACHER INFO CARD ── */
        .teacher-card {
            display: flex;
            align-items: center;
            gap: 16px;
            background: linear-gradient(135deg, #f8fafc, #eef2ff);
            border: 1px solid #c7d2fe;
            border-radius: var(--radius);
            padding: 18px 20px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .teacher-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-2), var(--primary-dark));
            color: #fff;
            display: grid;
            place-items: center;
            font-size: 22px;
            font-weight: 800;
            font-family: 'Poppins', sans-serif;
            flex-shrink: 0;
            box-shadow: 0 8px 18px -8px rgba(30, 64, 175, .6);
        }

        .teacher-meta {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .teacher-meta .t-name {
            font-family: 'Poppins', sans-serif;
            font-size: 17px;
            font-weight: 700;
            color: var(--text);
            word-break: break-word;
        }

        .teacher-meta .t-sub {
            font-size: 12.5px;
            color: var(--muted);
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .teacher-meta .t-sub span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .teacher-meta .t-sub i {
            color: var(--primary);
            font-size: 11px;
        }

        .teacher-back {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 600;
            color: var(--primary-dark);
            padding: 8px 14px;
            border-radius: 8px;
            background: #fff;
            border: 1px solid #c7d2fe;
            transition: background .15s;
        }

        .teacher-back:hover {
            background: #eef2ff;
        }

        /* ── ASSIGNMENT ROWS ── */
        .assign-row {
            border: 1.5px solid var(--line);
            border-radius: var(--radius);
            padding: 16px;
            margin-bottom: 14px;
            background: #fbfdff;
            transition: border-color .15s, box-shadow .15s;
        }

        .assign-row:hover {
            border-color: #c7d2fe;
            box-shadow: 0 6px 18px -12px rgba(30, 64, 175, .35);
        }

        .assign-row-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
        }

        .assign-row-head .row-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--primary-dark);
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .assign-row-head .row-label i {
            color: var(--primary);
        }

        .btn-remove {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid var(--red-line);
            background: var(--red-bg);
            color: var(--red);
            font-size: 12px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: background .15s, color .15s;
        }

        .btn-remove:hover {
            background: var(--red);
            color: #fff;
            border-color: var(--red);
        }

        .btn-remove:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        /* Grid inside a row */
        .row-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr);
            gap: 12px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }

        .form-group label {
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .form-group select,
        .form-group input[type="time"] {
            padding: 10px 12px;
            border: 1.5px solid var(--line);
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            background: #fff;
            outline: none;
            transition: border-color .18s, box-shadow .18s;
        }

        .form-group select:focus,
        .form-group input[type="time"]:focus {
            border-color: var(--primary-2);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .15);
        }

        .form-group select:disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }

        /* ── SCHEDULE BLOCK ── */
        .schedule-block {
            margin-top: 12px;
            border: 1.5px solid var(--line);
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }

        .schedule-head {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            background: linear-gradient(135deg, #f8fafc, #eef2ff);
            border-bottom: 1px solid var(--line);
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .schedule-head i {
            color: var(--primary);
        }

        .schedule-days {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 6px;
            padding: 10px 12px;
            border-bottom: 1px dashed var(--line);
        }

        .day-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 8px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 500;
            background: #f8fafc;
            border: 1px solid transparent;
            cursor: pointer;
            user-select: none;
            transition: background .12s, border-color .12s;
        }

        .day-item:hover {
            background: #eef2ff;
            border-color: #c7d2fe;
        }

        .day-item input[type="checkbox"] {
            width: 14px;
            height: 14px;
            accent-color: var(--primary-2);
            cursor: pointer;
            flex-shrink: 0;
        }

        .day-item.checked {
            background: #eef2ff;
            border-color: #c7d2fe;
            color: var(--primary-dark);
            font-weight: 600;
        }

        .schedule-times {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 12px;
        }

        .schedule-times .form-group label {
            font-size: 10.5px;
        }

        /* Subjects + advisory block */
        .row-secondary {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: start;
            margin-top: 12px;
        }

        .subjects-box {
            border: 1.5px solid var(--line);
            border-radius: 8px;
            padding: 10px 12px;
            background: #fff;
        }

        .subjects-box .subjects-label {
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .subjects-box .subjects-label i {
            color: var(--primary);
        }

        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 6px;
        }

        .subj-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 8px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 500;
            background: #f8fafc;
            border: 1px solid transparent;
            cursor: pointer;
            user-select: none;
            transition: background .12s, border-color .12s;
        }

        .subj-item:hover {
            background: #eef2ff;
            border-color: #c7d2fe;
        }

        .subj-item input[type="checkbox"] {
            width: 14px;
            height: 14px;
            accent-color: var(--primary-2);
            cursor: pointer;
            flex-shrink: 0;
        }

        .subj-item.checked {
            background: #eef2ff;
            border-color: #c7d2fe;
            color: var(--primary-dark);
            font-weight: 600;
        }

        /* Advisory box */
        .advisory-box {
            border: 1.5px solid var(--line);
            border-radius: 8px;
            padding: 14px 16px;
            background: #fff;
            display: flex;
            flex-direction: column;
            gap: 4px;
            cursor: pointer;
            user-select: none;
            min-width: 200px;
            transition: border-color .15s, background .15s;
        }

        .advisory-box:hover {
            border-color: var(--primary-2);
            background: #f8fafc;
        }

        .advisory-box .adv-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
        }

        .advisory-box .adv-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary-2);
            cursor: pointer;
        }

        .advisory-box .adv-hint {
            font-size: 11.5px;
            color: var(--muted);
            margin-left: 24px;
            line-height: 1.4;
        }

        .advisory-box.checked {
            background: #eef2ff;
            border-color: var(--primary-2);
        }

        /* Add row + Save actions */
        .form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px dashed var(--line);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 20px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: transform .15s, box-shadow .15s, background .15s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-2), var(--primary-dark));
            color: #fff;
            box-shadow: 0 10px 22px -10px rgba(37, 99, 235, .8);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: #fff;
            color: var(--primary-dark);
            border: 1.5px solid #c7d2fe;
        }

        .btn-secondary:hover {
            background: #eef2ff;
        }

        /* Error state */
        .error-panel {
            background: var(--red-bg);
            border: 1px solid var(--red-line);
            color: var(--red);
            border-radius: var(--radius);
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
        }

        .error-panel i {
            font-size: 20px;
        }

        /* ── STATUS MODAL ── */
        .status-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity .3s ease, visibility .3s ease;
        }

        .status-modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .status-modal-box {
            background: #fff;
            border-radius: 16px;
            padding: 40px 32px;
            text-align: center;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transform: scale(.9);
            transition: transform .3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .status-modal-overlay.show .status-modal-box {
            transform: scale(1);
        }

        .status-icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            margin: 0 auto 24px;
            position: relative;
        }

        .status-icon-wrapper.success {
            background: var(--green-bg);
            color: #10b981;
            border: 4px solid var(--green-line);
            animation: pulse-green 1s ease-out forwards;
        }

        .status-icon-wrapper.error {
            background: var(--red-bg);
            color: #ef4444;
            border: 4px solid var(--red-line);
            animation: pulse-red 1s ease-out forwards;
        }

        .status-icon-wrapper i {
            font-size: 40px;
            animation: draw-check .5s ease-out forwards;
        }

        @keyframes pulse-green {
            0% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, .6);
            }

            100% {
                box-shadow: 0 0 0 20px rgba(16, 185, 129, 0);
            }
        }

        @keyframes pulse-red {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, .6);
            }

            100% {
                box-shadow: 0 0 0 20px rgba(239, 68, 68, 0);
            }
        }

        @keyframes draw-check {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            70% {
                transform: scale(1.1);
                opacity: 1;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .status-title {
            font-family: 'Poppins', sans-serif;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .status-message {
            font-size: 15px;
            color: var(--muted);
            line-height: 1.5;
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

            .row-secondary {
                grid-template-columns: 1fr;
            }

            .advisory-box {
                min-width: 0;
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

            .row-grid {
                grid-template-columns: 1fr;
            }

            .subjects-grid {
                grid-template-columns: 1fr 1fr;
            }

            .schedule-days {
                grid-template-columns: 1fr 1fr;
            }

            .schedule-times {
                grid-template-columns: 1fr;
            }

            .teacher-card {
                padding: 14px;
                gap: 12px;
            }

            .teacher-avatar {
                width: 46px;
                height: 46px;
                font-size: 18px;
            }

            .teacher-meta .t-name {
                font-size: 15px;
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

            .subjects-grid {
                grid-template-columns: 1fr;
            }

            .schedule-days {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <!-- Center Success/Error Modal -->
    <div class="status-modal-overlay <?php echo ($flashSuccess || $flashError) ? 'show' : ''; ?>" id="statusModal">
        <div class="status-modal-box">
            <div class="status-icon-wrapper <?php echo $flashSuccess ? 'success' : 'error'; ?>" id="statusIconWrapper">
                <i class="fas <?php echo $flashSuccess ? 'fa-check' : 'fa-exclamation'; ?>" id="statusIcon"></i>
            </div>
            <div class="status-title" id="statusTitle"><?php echo $flashSuccess ? 'Success!' : 'Error!'; ?></div>
            <div class="status-message" id="statusMessage">
                <?php echo htmlspecialchars($flashSuccess ?: ($flashError ?: '')); ?>
            </div>
        </div>
    </div>

    <header class="nav" id="nav">
        <div class="container nav-inner">
            <a href="dashboard.php" class="logo">
                <div class="logo-mark">
                    <img src="https://campuslink.villaruz-print-shop-and-general-merchandise.shop/Images/logo.png"
                        alt="Eguia National High School logo" onerror="this.style.display='none'">
                </div>
                <div class="logo-divider" aria-hidden="true"></div>
                <div class="logo-text">
                    <b>CampusLink</b>
                    <span class="school-name"><?php echo htmlspecialchars($adminSchoolName); ?></span>
                </div>
            </a>

            <div class="nav-right">
                <span class="page-label">Assign Subjects</span>

                <div class="nav-actions">
                    <button class="nav-toggle" id="navToggle" aria-label="Buksan ang menu" aria-expanded="false">
                        <i class="fas fa-bars"></i>
                    </button>

                    <div class="nav-dropdown" id="navDropdown" role="menu">
                        <div class="nav-dropdown-head">MENU</div>

                        <a href="dashboard.php" role="menuitem">
                            <i class="fas fa-house"></i> Dashboard
                        </a>
                        <a href="teachers.php" role="menuitem">
                            <i class="fas fa-chalkboard-user"></i> Teachers
                        </a>
                        <div class="nav-dropdown-head"
                            style="margin-top: 6px; border-top: 1px solid var(--line); padding-top: 10px;">
                            <?php echo htmlspecialchars($adminDisplayName ?: $adminUser['account_number']); ?>
                        </div>
                        <a href="logout.php" role="menuitem">
                            <i class="fas fa-right-from-bracket"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container">
        <section class="section">
            <div class="section-head">
                <div>
                    <h2>Assign Subjects to Teacher</h2>
                    <p>Add one or more assignment rows for the selected teacher, then click Save.</p>
                </div>
            </div>

            <?php if ($teacherError !== '' || !$teacher): ?>
                <div class="error-panel">
                    <i class="fas fa-triangle-exclamation"></i>
                    <div><?php echo htmlspecialchars($teacherError ?: 'Teacher not found.'); ?></div>
                </div>

                <div style="margin-top:18px;">
                    <a href="teachers.php" class="teacher-back">
                        <i class="fas fa-arrow-left"></i> Back to Teachers
                    </a>
                </div>

            <?php else: ?>
                <div class="teacher-card">
                    <div class="teacher-avatar">
                        <?php
                        $initial = strtoupper(substr(trim((string) ($teacher['first_name'] ?? '?')), 0, 1));
                        echo htmlspecialchars($initial !== '' ? $initial : '?');
                        ?>
                    </div>
                    <div class="teacher-meta">
                        <span class="t-name"><?php echo htmlspecialchars($teacherDisplayName); ?></span>
                        <span class="t-sub">
                            <span><i class="fas fa-id-card"></i>
                                <?php echo htmlspecialchars($teacher['account_number'] ?? '—'); ?></span>
                            <?php if (!empty($teacher['position'])): ?>
                                <span><i class="fas fa-briefcase"></i>
                                    <?php echo htmlspecialchars($teacher['position']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($teacher['department'])): ?>
                                <span><i class="fas fa-building"></i>
                                    <?php echo htmlspecialchars($teacher['department']); ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <a href="teachers.php" class="teacher-back">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>

                <form id="assignTeacherForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="teacher_account"
                        value="<?php echo htmlspecialchars($teacher['account_number']); ?>">

                    <div id="assignRows"></div>

                    <div class="form-actions">
                        <button type="button" id="addRowBtn" class="btn btn-secondary">
                            <i class="fas fa-plus"></i> Add Row
                        </button>
                        <button type="submit" id="saveAssignmentBtn" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Assignment
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </main>

    <script>
        /* ============================================================
           CONFIG — teacher comes from URL (via PHP)
           ============================================================ */
        const TEACHER_ACCOUNT = <?php echo json_encode($teacher['account_number'] ?? null); ?>;

        const GRADE_OPTIONS = <?php echo json_encode($gradeOptions); ?>;
        const SECTIONS_BY_GRADE = <?php echo json_encode($sectionOptionsByGrade, JSON_UNESCAPED_UNICODE); ?>;
        const SUBJECT_OPTIONS = <?php echo json_encode($subjectOptions, JSON_UNESCAPED_UNICODE); ?>;
        const DAY_OPTIONS = <?php echo json_encode($dayOptions); ?>;

        /* ============================================================
           NAVBAR
           ============================================================ */
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

        /* ============================================================
           STATUS MODAL
           ============================================================ */
        const statusModal = document.getElementById('statusModal');
        const statusWrapper = document.getElementById('statusIconWrapper');
        const statusIcon = document.getElementById('statusIcon');
        const statusTitle = document.getElementById('statusTitle');
        const statusMessage = document.getElementById('statusMessage');

        function showStatusModal(type, message) {
            statusWrapper.className = 'status-icon-wrapper ' + type;
            statusIcon.className = 'fas ' + (type === 'success' ? 'fa-check' : 'fa-exclamation');
            statusTitle.textContent = (type === 'success' ? 'Success!' : 'Error!');
            statusMessage.textContent = message;

            statusModal.classList.add('show');
            setTimeout(() => statusModal.classList.remove('show'), 2200);
        }

        if (statusModal.classList.contains('show')) {
            setTimeout(() => statusModal.classList.remove('show'), 2000);
        }

        /* ============================================================
           TIME HELPERS
           ============================================================ */
        function formatTime12(hhmm) {
            if (!hhmm) return '';
            const parts = String(hhmm).split(':');
            if (parts.length < 2) return hhmm;
            const h = parseInt(parts[0], 10);
            const m = parseInt(parts[1], 10);
            if (isNaN(h) || isNaN(m)) return hhmm;
            const period = h >= 12 ? 'PM' : 'AM';
            const h12 = h % 12 || 12;
            return `${h12}:${String(m).padStart(2, '0')}${period}`;
        }

        /* Build 30-min interval options from 6:00 AM to 7:00 PM */
        function buildTimeOptions(selected) {
            const times = [];
            for (let h = 6; h <= 19; h++) {
                for (let m = 0; m < 60; m += 30) {
                    if (h === 19 && m > 0) break;
                    const hh = String(h).padStart(2, '0');
                    const mm = String(m).padStart(2, '0');
                    const val = `${hh}:${mm}`;
                    times.push({ val, display: formatTime12(val) });
                }
            }

            let html = '<option value="">— Select Time —</option>';
            times.forEach(t => {
                const sel = (selected && selected === t.val) ? ' selected' : '';
                html += `<option value="${t.val}"${sel}>${t.display}</option>`;
            });
            const isCustom = selected && !times.some(t => t.val === selected);
            html += `<option value="__manual__"${isCustom ? ' selected' : ''}>Manual…</option>`;
            return html;
        }

        /* ============================================================
           ASSIGNMENT ROWS
           ============================================================ */
        const assignRowsEl = document.getElementById('assignRows');
        const addRowBtn = document.getElementById('addRowBtn');
        const assignForm = document.getElementById('assignTeacherForm');
        const saveBtn = document.getElementById('saveAssignmentBtn');

        let rowCounter = 0;

        function escapeHtml(s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function buildGradeOptions(selected) {
            let html = '<option value="">— Select Grade —</option>';
            GRADE_OPTIONS.forEach(g => {
                const sel = (selected && String(selected) === String(g)) ? ' selected' : '';
                html += `<option value="${escapeHtml(g)}"${sel}>Grade ${escapeHtml(g)}</option>`;
            });
            return html;
        }

        function buildSectionOptions(grade, selected) {
            const list = SECTIONS_BY_GRADE[grade] || [];
            if (!grade) return '<option value="">— Select Grade first —</option>';
            if (list.length === 0) return '<option value="">— No sections —</option>';
            let html = '<option value="">— Select Section —</option>';
            list.forEach(s => {
                const sel = (selected && selected === s) ? ' selected' : '';
                html += `<option value="${escapeHtml(s)}"${sel}>${escapeHtml(s)}</option>`;
            });
            return html;
        }

        function buildSubjectsGrid(rowId) {
            let html = '';
            SUBJECT_OPTIONS.forEach((subj, i) => {
                const id = `subj_${rowId}_${i}`;
                html += `
                    <label class="subj-item" for="${id}">
                        <input type="checkbox" id="${id}" name="subjects_${rowId}[]" value="${escapeHtml(subj)}">
                        <span>${escapeHtml(subj)}</span>
                    </label>
                `;
            });
            return html;
        }

        function buildDaysGrid(rowId) {
            let html = '';
            DAY_OPTIONS.forEach((day, i) => {
                const id = `day_${rowId}_${i}`;
                html += `
                    <label class="day-item" for="${id}">
                        <input type="checkbox" id="${id}" name="days_${rowId}[]" value="${escapeHtml(day)}">
                        <span>${escapeHtml(day)}</span>
                    </label>
                `;
            });
            return html;
        }

        function addRow(prefill) {
            prefill = prefill || {};
            rowCounter++;
            const rowId = rowCounter;

            const wrapper = document.createElement('div');
            wrapper.className = 'assign-row';
            wrapper.dataset.rowId = rowId;

            wrapper.innerHTML = `
                <div class="assign-row-head">
                    <span class="row-label"><i class="fas fa-layer-group"></i> Assignment #${rowId}</span>
                    <button type="button" class="btn-remove js-remove" title="Remove this row">
                        <i class="fas fa-xmark"></i> Remove
                    </button>
                </div>

                <div class="row-grid">
                    <div class="form-group">
                        <label>Grade Level</label>
                        <select name="grade_level_${rowId}" class="js-grade" required>
                            ${buildGradeOptions(prefill.grade)}
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Section</label>
                        <select name="section_${rowId}" class="js-section" required>
                            ${buildSectionOptions(prefill.grade, prefill.section)}
                        </select>
                    </div>
                </div>

                <!-- SCHEDULE -->
                <div class="schedule-block">
                    <div class="schedule-head">
                        <i class="fas fa-calendar-days"></i> Schedule
                    </div>

                    <div class="schedule-days">
                        ${buildDaysGrid(rowId)}
                    </div>

                    <div class="schedule-times">
                        <div class="form-group">
                            <label>Start Time</label>
                            <select class="js-start-time-select">
                                ${buildTimeOptions(prefill.start_time || '')}
                            </select>
                            <input type="time" class="js-start-time js-manual-start"
                                name="start_time_${rowId}"
                                value="${escapeHtml(prefill.start_time || '')}"
                                style="display:none; margin-top:6px;">
                        </div>
                        <div class="form-group">
                            <label>End Time</label>
                            <select class="js-end-time-select">
                                ${buildTimeOptions(prefill.end_time || '')}
                            </select>
                            <input type="time" class="js-end-time js-manual-end"
                                name="end_time_${rowId}"
                                value="${escapeHtml(prefill.end_time || '')}"
                                style="display:none; margin-top:6px;">
                        </div>
                    </div>
                </div>

                <div class="row-secondary">
                    <div class="subjects-box">
                        <div class="subjects-label"><i class="fas fa-book"></i> Subjects (check all that apply)</div>
                        <div class="subjects-grid">
                            ${buildSubjectsGrid(rowId)}
                        </div>
                    </div>

                    <label class="advisory-box js-advisory-wrap">
                        <span class="adv-label">
                            <input type="checkbox" class="js-advisory" name="advisory_${rowId}" value="1">
                            Class Advisory
                        </span>
                        <span class="adv-hint">Check if this teacher is the adviser of this grade/section.</span>
                    </label>
                </div>
            `;

            assignRowsEl.appendChild(wrapper);

            const gradeSel = wrapper.querySelector('.js-grade');
            const sectionSel = wrapper.querySelector('.js-section');
            const advisory = wrapper.querySelector('.js-advisory');
            const advWrap = wrapper.querySelector('.js-advisory-wrap');

            const startTimeSelect = wrapper.querySelector('.js-start-time-select');
            const endTimeSelect = wrapper.querySelector('.js-end-time-select');
            const startTime = wrapper.querySelector('.js-start-time');
            const endTime = wrapper.querySelector('.js-end-time');

            gradeSel.addEventListener('change', () => {
                sectionSel.innerHTML = buildSectionOptions(gradeSel.value, '');
            });

            /* Subject checkbox visual state */
            wrapper.querySelectorAll('.subj-item input[type="checkbox"]').forEach(cb => {
                cb.addEventListener('change', () => {
                    cb.closest('.subj-item').classList.toggle('checked', cb.checked);
                });
            });

            /* Day checkbox visual state */
            wrapper.querySelectorAll('.day-item input[type="checkbox"]').forEach(cb => {
                cb.addEventListener('change', () => {
                    cb.closest('.day-item').classList.toggle('checked', cb.checked);
                });
            });

            /* Time select ↔ manual input sync */
            function syncTime(selectEl, manualEl) {
                if (selectEl.value === '__manual__') {
                    manualEl.style.display = 'block';
                    manualEl.required = true;
                } else {
                    manualEl.style.display = 'none';
                    manualEl.required = false;
                    manualEl.value = selectEl.value;
                }
            }

            function validateTimes() {
                const s = startTime.value;
                const e = endTime.value;
                if (s && e && e <= s) {
                    endTime.setCustomValidity('End time must be after start time.');
                } else {
                    endTime.setCustomValidity('');
                }
            }

            startTimeSelect.addEventListener('change', () => {
                syncTime(startTimeSelect, startTime);
                validateTimes();
            });

            endTimeSelect.addEventListener('change', () => {
                syncTime(endTimeSelect, endTime);
                validateTimes();
            });

            startTime.addEventListener('change', validateTimes);
            endTime.addEventListener('change', validateTimes);

            /* Initial sync (handles prefill/custom values) */
            if (startTimeSelect.value === '__manual__') {
                startTime.style.display = 'block';
                startTime.required = true;
            } else {
                startTime.value = startTimeSelect.value;
            }

            if (endTimeSelect.value === '__manual__') {
                endTime.style.display = 'block';
                endTime.required = true;
            } else {
                endTime.value = endTimeSelect.value;
            }

            validateTimes();

            advisory.addEventListener('change', () => {
                advWrap.classList.toggle('checked', advisory.checked);
            });

            wrapper.querySelector('.js-remove').addEventListener('click', () => {
                if (assignRowsEl.children.length <= 1) {
                    showStatusModal('error', 'At least one assignment row is required.');
                    return;
                }
                wrapper.remove();
                renumberRows();
            });

            return wrapper;
        }

        function renumberRows() {
            Array.from(assignRowsEl.children).forEach((row, idx) => {
                const label = row.querySelector('.row-label');
                if (label) {
                    label.innerHTML = `<i class="fas fa-layer-group"></i> Assignment #${idx + 1}`;
                }
            });
        }

        if (addRowBtn && assignRowsEl) {
            addRowBtn.addEventListener('click', () => addRow());
            addRow(); /* start with one row */
        }

        /* ============================================================
           SUBMIT
           ============================================================ */
        if (assignForm && saveBtn) {
            assignForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                if (!TEACHER_ACCOUNT) {
                    showStatusModal('error', 'Teacher account missing. Please go back and try again.');
                    return;
                }

                const rows = [];
                let hasError = false;

                Array.from(assignRowsEl.children).forEach((rowEl) => {
                    if (hasError) return;

                    const grade = rowEl.querySelector('.js-grade')?.value || '';
                    const section = rowEl.querySelector('.js-section')?.value || '';
                    const advisory = rowEl.querySelector('.js-advisory')?.checked ? 1 : 0;
                    const startTime = rowEl.querySelector('.js-start-time')?.value || '';
                    const endTime = rowEl.querySelector('.js-end-time')?.value || '';

                    const subjects = [];
                    rowEl.querySelectorAll('.subj-item input[type="checkbox"]:checked').forEach(cb => {
                        subjects.push(cb.value);
                    });

                    const days = [];
                    rowEl.querySelectorAll('.day-item input[type="checkbox"]:checked').forEach(cb => {
                        days.push(cb.value);
                    });

                    if (!grade) { showStatusModal('error', 'Please select a grade in every row.'); hasError = true; return; }
                    if (!section) { showStatusModal('error', 'Please select a section in every row.'); hasError = true; return; }
                    if (subjects.length === 0) { showStatusModal('error', 'Please check at least one subject per row.'); hasError = true; return; }
                    if (days.length === 0) { showStatusModal('error', 'Please check at least one day in the schedule.'); hasError = true; return; }
                    if (!startTime) { showStatusModal('error', 'Please set the start time for every row.'); hasError = true; return; }
                    if (!endTime) { showStatusModal('error', 'Please set the end time for every row.'); hasError = true; return; }
                    if (endTime <= startTime) { showStatusModal('error', 'End time must be after start time.'); hasError = true; return; }

                    rows.push({
                        teacher_account: TEACHER_ACCOUNT,
                        grade_level: grade,
                        section: section,
                        subjects: subjects,
                        days: days,
                        start_time: startTime,
                        end_time: endTime,
                        advisory: advisory
                    });
                });

                if (hasError) return;

                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

                try {
                    const res = await fetch('../API_ADMIN/assign-teachers.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': <?php echo json_encode($csrfToken); ?>
                        },
                        body: JSON.stringify({
                            teacher_account: TEACHER_ACCOUNT,
                            assignments: rows
                        })
                    });

                    const data = await res.json().catch(() => null);
                    if (!data) throw new Error('Server returned an invalid response (HTTP ' + res.status + ').');
                    if (!data.success) throw new Error(data.message || 'Failed to save assignment.');

                    assignRowsEl.innerHTML = '';
                    rowCounter = 0;
                    addRow();

                    showStatusModal('success', data.message || 'Assignments saved successfully.');

                    setTimeout(() => window.location.reload(), 2200);

                } catch (err) {
                    console.error(err);
                    showStatusModal('error', err.message || 'An error occurred. Please try again.');
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Assignment';
                }
            });
        }
    </script>
</body>

</html>