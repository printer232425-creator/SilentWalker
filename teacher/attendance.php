<?php
// teachers/class-attendance.php — Eguia National High School Pages
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

if (!$isLoggedIn || !$user) {
    header('Location: ../index.php');
    exit;
}

$displayName = trim(
    ($user['first_name'] ?? '') . ' ' .
    ($user['middle_name'] ?? '') . ' ' .
    ($user['surname'] ?? '')
);

$teacherSchoolName = trim((string) ($user['school_name'] ?? ''));
if ($teacherSchoolName === '') {
    $teacherSchoolName = 'Eguia National High School';
}

/* ------------------------------------------------------------
   READ + VALIDATE grade_level / section FROM QUERY STRING
   ------------------------------------------------------------ */
$allowedGrades = ['7', '8', '9', '10', '11', '12'];

$allowedSections = [
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

$gradeLevel = trim((string) ($_GET['grade_level'] ?? ''));
$section = trim((string) ($_GET['section'] ?? ''));

$invalidParams =
    !in_array($gradeLevel, $allowedGrades, true)
    || !in_array($section, $allowedSections, true);

if ($invalidParams) {
    header('Location: advisory-class.php');
    exit;
}

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
   FETCH ENROLLED STUDENTS for this grade + section
   ------------------------------------------------------------ */
$students = [];
$fetchError = null;

try {
    $stmt = $pdo->prepare("
        SELECT
            es.id               AS enrollment_id,
            es.account_number,
            es.grade_level,
            es.section,
            es.academic_year,
            es.school_name      AS school_name,
            es.status           AS enrollment_status,
            s.first_name,
            s.middle_name,
            s.surname,
            s.sex,
            s.age,
            s.birth_date,
            s.contact_number,
            s.guardian,
            s.guardian_contact_number
        FROM enrolled_students es
        LEFT JOIN students s ON s.account_number = es.account_number
        WHERE CAST(es.grade_level AS CHAR) = ?
          AND es.section = ?
          AND es.academic_year = ?
        ORDER BY s.surname ASC, s.first_name ASC
    ");
    $stmt->execute([$gradeLevel, $section, $currentYear]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    $fetchError = $e->getMessage();
    error_log("class-attendance: enrolled_students fetch failed: " . $fetchError);
}

$totalStudents = count($students);

/* ------------------------------------------------------------
   CHECK WHO IS ALREADY MARKED PRESENT TODAY
   ------------------------------------------------------------ */
$presentToday = [];
if (!empty($students)) {
    try {
        $accounts = array_values(array_filter(array_map(
            fn($s) => trim((string) ($s['account_number'] ?? '')),
            $students
        )));
        if (!empty($accounts)) {
            $placeholders = implode(',', array_fill(0, count($accounts), '?'));
            $sql = "
                SELECT DISTINCT account_number
                FROM attendance_list
                WHERE date_today = ?
                  AND CAST(grade_level AS CHAR) = ?
                  AND section = ?
                  AND account_number IN ($placeholders)
            ";
            $params = array_merge([date('Y-m-d'), $gradeLevel, $section], $accounts);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $presentToday[trim((string) $row['account_number'])] = true;
            }
        }
    } catch (PDOException $e) {
        error_log("attendance present check failed: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fil">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>" />
    <title>Class Attendance — Eguia National High School</title>

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

        /* ── PAGE HEADER ── */
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

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: transform .15s, box-shadow .15s, background .15s;
            white-space: nowrap;
        }

        .btn-back {
            background: #fff;
            color: var(--primary-dark);
            border: 1px solid #bfdbfe;
        }

        .btn-back:hover {
            background: #eff6ff;
        }

        /* ── TOAST ── */
        .toast-wrap {
            position: fixed;
            top: 90px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 8px;
            pointer-events: none;
        }

        .toast {
            padding: 12px 16px;
            border-radius: var(--radius);
            font-size: 13.5px;
            font-weight: 500;
            box-shadow: 0 10px 24px -12px rgba(15, 23, 42, .4);
            display: flex;
            align-items: center;
            gap: 10px;
            pointer-events: auto;
            animation: toastIn .25s ease;
            max-width: 360px;
        }

        .toast.is-success {
            background: var(--green-bg);
            border: 1px solid var(--green-line);
            color: var(--green);
        }

        .toast.is-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        .toast.is-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: var(--primary-dark);
        }

        .toast i {
            font-size: 14px;
        }

        @keyframes toastIn {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── MODAL ── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 9998;
            background: rgba(15, 23, 42, .55);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            visibility: hidden;
            transition: opacity .2s ease, visibility .2s ease;
        }

        .modal-overlay.open {
            opacity: 1;
            visibility: visible;
        }

        .modal-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid var(--line);
            box-shadow: 0 24px 60px -20px rgba(15, 23, 42, .55);
            width: 100%;
            max-width: 420px;
            padding: 28px 24px 22px;
            text-align: center;
            transform: translateY(8px) scale(.98);
            transition: transform .2s ease;
        }

        .modal-overlay.open .modal-card {
            transform: translateY(0) scale(1);
        }

        .modal-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            margin: 0 auto 14px;
            display: grid;
            place-items: center;
            background: var(--green-bg);
            color: var(--green);
            font-size: 24px;
            border: 1px solid var(--green-line);
        }

        .modal-title {
            font-family: 'Poppins', sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 6px;
        }

        .modal-text {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.6;
            margin-bottom: 22px;
        }

        .modal-text b {
            color: var(--text);
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .modal-btn {
            flex: 1;
            padding: 10px 16px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: transform .15s, box-shadow .15s, background .15s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .modal-btn-cancel {
            background: #f1f5f9;
            color: var(--text);
            border: 1px solid var(--line);
        }

        .modal-btn-cancel:hover {
            background: #e2e8f0;
        }

        .modal-btn-confirm {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
            box-shadow: 0 8px 18px -10px rgba(34, 197, 94, .9);
        }

        .modal-btn-confirm:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 12px 22px -12px rgba(34, 197, 94, 1);
        }

        .modal-btn-confirm:disabled {
            opacity: .65;
            cursor: not-allowed;
        }

        .modal-btn i {
            font-size: 12px;
        }

        /* ── TABLE ── */
        .table-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--line);
            border-radius: var(--radius);
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }

        table.data thead {
            background: #f1f5f9;
        }

        table.data th,
        table.data td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
        }

        table.data th {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
        }

        table.data tbody tr:last-child td {
            border-bottom: none;
        }

        table.data tbody tr:hover {
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

        /* ── PRESENT BUTTON ── */
        .btn-present {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 8px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
            font-size: 12.5px;
            font-weight: 700;
            font-family: inherit;
            border: none;
            cursor: pointer;
            box-shadow: 0 6px 14px -8px rgba(34, 197, 94, .85);
            transition: transform .15s, box-shadow .15s, opacity .15s;
            white-space: nowrap;
        }

        .btn-present:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 10px 18px -10px rgba(34, 197, 94, .95);
        }

        .btn-present:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .btn-present i {
            font-size: 11.5px;
        }

        .present-mark {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 8px;
            background: var(--green-bg);
            color: var(--green);
            border: 1px solid var(--green-line);
            font-size: 12.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .present-mark i {
            font-size: 11.5px;
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

            .page-header {
                padding: 20px;
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

            table.data th,
            table.data td {
                padding: 10px 12px;
                font-size: 13px;
            }

            .toast-wrap {
                right: 12px;
                left: 12px;
            }

            .toast {
                max-width: 100%;
            }

            .modal-card {
                padding: 24px 18px 18px;
            }

            .modal-title {
                font-size: 16px;
            }

            .modal-text {
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
                <span class="page-label">Class Attendance</span>

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
    </header>

    <!-- TOAST CONTAINER -->
    <div class="toast-wrap" id="toastWrap"></div>

    <!-- PRESENT CONFIRM MODAL -->
    <div class="modal-overlay" id="presentModal" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="presentModalTitle">
            <div class="modal-icon">
                <i class="fas fa-circle-check"></i>
            </div>
            <h3 id="presentModalTitle" class="modal-title">Mark as Present?</h3>
            <p class="modal-text" id="presentModalText">
                Mark <b>Student</b> as Present today?
            </p>
            <div class="modal-actions">
                <button type="button" class="modal-btn modal-btn-cancel" id="presentModalCancel">Cancel</button>
                <button type="button" class="modal-btn modal-btn-confirm" id="presentModalConfirm">
                    <i class="fas fa-check"></i> Yes, Mark Present
                </button>
            </div>
        </div>
    </div>

    <main class="container">
        <!-- PAGE HEADER -->
        <div class="page-header">
            <div class="page-header-left">
                <div class="page-header-icon">
                    <i class="fas fa-clipboard-user"></i>
                </div>
                <div class="page-header-text">
                    <h1>Class Attendance</h1>
                    <div class="meta">
                        <span class="pill"><i class="fas fa-layer-group"></i> Grade
                            <?php echo htmlspecialchars($gradeLevel); ?></span>
                        <span class="pill"><i class="fas fa-users"></i> <?php echo htmlspecialchars($section); ?></span>
                        <span class="pill"><i class="fas fa-calendar-days"></i> S.Y.
                            <?php echo htmlspecialchars($currentYear); ?></span>
                        <span class="pill"><i class="fas fa-user-graduate"></i> <?php echo (int) $totalStudents; ?>
                            student<?php echo $totalStudents === 1 ? '' : 's'; ?></span>
                    </div>
                </div>
            </div>

            <a href="advisory-class.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to Advisory
            </a>
        </div>

        <!-- STUDENTS -->
        <section class="section">
            <div class="section-head">
                <div>
                    <h2>Enrolled Students</h2>
                    <p>All students currently enrolled in Grade <?php echo htmlspecialchars($gradeLevel); ?> -
                        <?php echo htmlspecialchars($section); ?>.
                    </p>
                </div>
            </div>

            <?php if ($fetchError !== null): ?>
                <div class="empty-state">
                    <i class="fas fa-triangle-exclamation" style="color:#f59e0b;"></i>
                    <p>Could not load students. Please try again later.</p>
                    <div class="debug">Debug: <?php echo htmlspecialchars($fetchError); ?></div>
                </div>
            <?php elseif (empty($students)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No students are enrolled in this grade and section for S.Y.
                        <?php echo htmlspecialchars($currentYear); ?>.
                    </p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Account #</th>
                                <th>Full Name</th>
                                <th>Sex</th>
                                <th>Age</th>
                                <th>Contact</th>
                                <th>Guardian</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $i => $s): ?>
                                <?php
                                $fullName = trim(
                                    ($s['first_name'] ?? '') . ' ' .
                                    ($s['middle_name'] ?? '') . ' ' .
                                    ($s['surname'] ?? '')
                                );
                                if ($fullName === '') {
                                    $fullName = '(Unknown Name)';
                                }
                                $contact = trim((string) ($s['contact_number'] ?? ''));
                                $guardian = trim((string) ($s['guardian'] ?? ''));
                                $guardianContact = trim((string) ($s['guardian_contact_number'] ?? ''));
                                $accountNumber = trim((string) ($s['account_number'] ?? ''));

                                $isPresent = $accountNumber !== '' && isset($presentToday[$accountNumber]);
                                ?>
                                <tr data-account="<?php echo htmlspecialchars($accountNumber); ?>">
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo htmlspecialchars($accountNumber !== '' ? $accountNumber : '—'); ?></td>
                                    <td>
                                        <div class="name-block">
                                            <span class="main"><?php echo htmlspecialchars($fullName); ?></span>
                                            <?php if ($contact !== ''): ?>
                                                <span class="sub"><?php echo htmlspecialchars($contact); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($s['sex'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($s['age'] ?? '—')); ?></td>
                                    <td><?php echo htmlspecialchars($contact !== '' ? $contact : '—'); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($guardian !== '' ? $guardian : '—'); ?>
                                        <?php if ($guardianContact !== ''): ?>
                                            <br><span
                                                style="color:#64748b; font-size:12px;"><?php echo htmlspecialchars($guardianContact); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="status-cell">
                                        <?php if ($isPresent): ?>
                                            <span class="present-mark">
                                                <i class="fas fa-check"></i> Present
                                            </span>
                                        <?php elseif ($accountNumber !== ''): ?>
                                            <button type="button" class="btn-present"
                                                data-account="<?php echo htmlspecialchars($accountNumber); ?>"
                                                data-name="<?php echo htmlspecialchars($fullName); ?>">
                                                <i class="fas fa-check"></i> Present
                                            </button>
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
        </section>
    </main>

    <script>
        /* ── NAV TOGGLE ── */
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

        /* ── TOAST HELPER ── */
        function showToast(message, type = 'success') {
            const wrap = document.getElementById('toastWrap');
            if (!wrap) return;
            const el = document.createElement('div');
            el.className = 'toast is-' + type;

            let icon = 'fa-circle-check';
            if (type === 'error') icon = 'fa-triangle-exclamation';
            if (type === 'info') icon = 'fa-circle-info';

            el.innerHTML = '<i class="fas ' + icon + '"></i><span>' + message + '</span>';
            wrap.appendChild(el);

            setTimeout(() => {
                el.style.transition = 'opacity .3s, transform .3s';
                el.style.opacity = '0';
                el.style.transform = 'translateY(-8px)';
                setTimeout(() => el.remove(), 300);
            }, 3500);
        }

        /* ── CONFIG ── */
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const GRADE_LEVEL = <?php echo json_encode($gradeLevel); ?>;
        const SECTION = <?php echo json_encode($section); ?>;

        /* ── MODAL ELEMENTS ── */
        const modal = document.getElementById('presentModal');
        const modalText = document.getElementById('presentModalText');
        const modalConfirm = document.getElementById('presentModalConfirm');
        const modalCancel = document.getElementById('presentModalCancel');

        let pendingBtn = null;
        let pendingAccount = '';
        let pendingName = '';

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, (c) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;',
                '"': '&quot;', "'": '&#39;'
            }[c]));
        }

        function openModal(btn, account, name) {
            pendingBtn = btn;
            pendingAccount = account;
            pendingName = name;

            modalText.innerHTML = 'Mark <b>' + escapeHtml(name) + '</b> as Present today?';
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            modalConfirm.disabled = false;
            modalConfirm.innerHTML = '<i class="fas fa-check"></i> Yes, Mark Present';

            setTimeout(() => modalConfirm.focus(), 50);
        }

        function closeModal() {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            pendingBtn = null;
            pendingAccount = '';
            pendingName = '';
        }

        /* close on cancel / overlay / Escape */
        modalCancel?.addEventListener('click', closeModal);

        modal?.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal?.classList.contains('open')) {
                closeModal();
            }
        });

        /* ── PRESENT BUTTON — opens modal ── */
        document.querySelectorAll('.btn-present[data-account]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const account = btn.getAttribute('data-account');
                const name = btn.getAttribute('data-name') || account;
                if (!account) return;
                openModal(btn, account, name);
            });
        });

        /* ── MODAL CONFIRM — fires API ── */
        modalConfirm?.addEventListener('click', async () => {
            if (!pendingAccount || !pendingBtn) return;

            const btn = pendingBtn;
            const account = pendingAccount;
            const name = pendingName;

            modalConfirm.disabled = true;
            modalConfirm.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

            try {
                const res = await fetch('API_TEACHER/checking-attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        csrf_token: CSRF_TOKEN,
                        account_number: account,
                        grade_level: GRADE_LEVEL,
                        section: SECTION,
                    }),
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok || data.status !== 'success') {
                    throw new Error(data.message || 'Failed to save attendance.');
                }

                const row = btn.closest('tr');
                const cell = row?.querySelector('.status-cell');
                if (cell) {
                    cell.innerHTML =
                        '<span class="present-mark"><i class="fas fa-check"></i> Present</span>';
                }

                closeModal();
                showToast(data.message || 'Marked present: ' + name, 'success');

            } catch (err) {
                modalConfirm.disabled = false;
                modalConfirm.innerHTML = '<i class="fas fa-check"></i> Yes, Mark Present';
                showToast(err.message || 'Something went wrong.', 'error');
            }
        });
    </script>
</body>

</html>