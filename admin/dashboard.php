<?php
// admin/dashboard.php — Administrator dashboard
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
   HELPERS
   ------------------------------------------------------------ */
function currentAcademicYear(): string
{
    $y = (int) date('Y');
    return ((int) date('n') < 6) ? (($y - 1) . '-' . $y) : ($y . '-' . ($y + 1));
}

$currentYear = currentAcademicYear();

/* ------------------------------------------------------------
   GRADE + SECTION MAP
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

$sectionMapJson = json_encode($sectionOptionsByGrade);

/* ------------------------------------------------------------
   FLASH
   ------------------------------------------------------------ */
$flashError = $_SESSION['flash']['admin_error'] ?? null;
$flashSuccess = $_SESSION['flash']['admin_success'] ?? null;
unset($_SESSION['flash']['admin_error'], $_SESSION['flash']['admin_success']);

/* ------------------------------------------------------------
   FETCH DATA
   ------------------------------------------------------------ */
$totalStudents = 0;
$totalTeachers = 0;
$totalAssignments = 0;
$pendingEnrollments = 0;
$enrollmentOpen = false;

try {
    $totalStudents = (int) $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $totalTeachers = (int) $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
    $totalAssignments = (int) $pdo->query("SELECT COUNT(*) FROM student_subjects")->fetchColumn();
} catch (PDOException $e) {
    error_log("admin stats failed: " . $e->getMessage());
}

try {
    $pendingEnrollments = (int) $pdo->query(
        "SELECT COUNT(*) FROM enrolled_students WHERE status = 'Pending'"
    )->fetchColumn();
} catch (PDOException $e) {
    error_log("pending enrollments fetch failed: " . $e->getMessage());
    $pendingEnrollments = 0;
}

try {
    $accessValue = $pdo->query("
        SELECT enrollment_access
        FROM students
        WHERE student_type = 'O'
        LIMIT 1
    ")->fetchColumn();

    $enrollmentOpen = ((int) $accessValue === 1);
} catch (PDOException $e) {
    error_log("enrollment state fetch failed: " . $e->getMessage());
    $enrollmentOpen = false;
}

/* ------------------------------------------------------------
   FINAL ENROLLMENT — only Pending enrolled_students for the current
   academic year. Active rows are excluded so they only show in
   "All Students" below.
   ------------------------------------------------------------ */
$studentsForEnrollment = [];
try {
    $stmt = $pdo->prepare("
        SELECT
            e.id            AS enrollment_id,
            e.account_number,
            e.grade_level   AS en_grade_level,
            e.section       AS en_section,
            e.academic_year AS en_academic_year,
            e.status        AS en_status,
            s.first_name,
            s.middle_name,
            s.surname,
            s.age,
            s.sex,
            s.student_type,
            s.student_lrn,
            s.profile_account
        FROM enrolled_students e
        LEFT JOIN students s
               ON s.account_number = e.account_number
        WHERE e.academic_year = ?
          AND LOWER(TRIM(e.status)) = 'pending'
        ORDER BY s.surname, s.first_name, e.id DESC
    ");
    $stmt->execute([$currentYear]);
    $studentsForEnrollment = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    /* Extra PHP guard — never show Active rows here even if query changes */
    $studentsForEnrollment = array_values(array_filter(
        $studentsForEnrollment,
        fn($r) => strtolower(trim((string) ($r['en_status'] ?? ''))) === 'pending'
    ));
} catch (PDOException $e) {
    error_log("final enrollment fetch failed: " . $e->getMessage());
}

/* ------------------------------------------------------------
   ALL STUDENTS — every Active enrolled_students row (all years),
   joined to students for name / LRN / profile picture.
   Only the latest Active row per student so no duplicates.
   ------------------------------------------------------------ */
$enrolledStudents = [];
try {
    $stmt = $pdo->query("
        SELECT
            e.id            AS enrollment_id,
            e.account_number,
            e.grade_level,
            e.section,
            e.academic_year,
            e.status        AS enrollment_record_status,
            s.first_name,
            s.middle_name,
            s.surname,
            s.student_lrn,
            s.profile_account,
            s.enrollment_status
        FROM enrolled_students e
        INNER JOIN students s
                ON s.account_number = e.account_number
        WHERE LOWER(TRIM(e.status)) = 'active'
          AND e.id = (
                SELECT MAX(e2.id)
                FROM enrolled_students e2
                WHERE e2.account_number = e.account_number
                  AND LOWER(TRIM(e2.status)) = 'active'
          )
        ORDER BY s.surname, s.first_name
    ");
    $enrolledStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("all students fetch failed: " . $e->getMessage());
}

$gradeFilter = isset($_GET['grade']) ? trim((string) $_GET['grade']) : 'all';
$validGrades = ['7', '8', '9', '10', '11', '12'];
$isAllGrades = ($gradeFilter === 'all' || $gradeFilter === '');

if (!$isAllGrades && !in_array($gradeFilter, $validGrades, true)) {
    $gradeFilter = 'all';
    $isAllGrades = true;
}

$filteredEnrolledStudents = [];
if ($isAllGrades) {
    $filteredEnrolledStudents = $enrolledStudents;
} else {
    foreach ($enrolledStudents as $s) {
        if ((string) ($s['grade_level'] ?? '') === $gradeFilter) {
            $filteredEnrolledStudents[] = $s;
        }
    }
}

$adminDisplayName = trim(
    ($adminUser['first_name'] ?? '') . ' ' .
    ($adminUser['middle_name'] ?? '') . ' ' .
    ($adminUser['surname'] ?? '')
);

/* Helper: student type label */
function studentTypeLabel(string $t): string
{
    switch (strtoupper(trim($t))) {
        case 'O':
            return 'Old Student';
        case 'N':
            return 'New Student';
        case 'T':
            return 'Transferee';
        default:
            return $t !== '' ? $t : '—';
    }
}

function studentTypeBadgeClass(string $t): string
{
    switch (strtoupper(trim($t))) {
        case 'O':
            return 'type-o';
        case 'N':
            return 'type-n';
        case 'T':
            return 'type-t';
        default:
            return 'type-default';
    }
}
?>
<!DOCTYPE html>
<html lang="fil">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>" />
    <title>Admin Dashboard — Eguia National High School</title>

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

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; overflow-y: scroll; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        img, svg { display: block; }
        a { text-decoration: none; color: inherit; }

        h1, h2, h3, h4 {
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

        .logo-mark img { width: 100%; height: 100%; object-fit: contain; display: block; }

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

        .nav-right { display: flex; align-items: center; gap: 14px; flex-shrink: 0; }

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

        .nav-toggle:hover { background: #eef2ff; color: var(--primary-dark); }
        .nav-toggle:active { transform: scale(.96); }

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

        .nav-dropdown.open { opacity: 1; visibility: visible; transform: translateY(0); }

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
        .nav-dropdown button:hover { background: #eef2ff; color: var(--primary-dark); }

        .nav-dropdown a i,
        .nav-dropdown button i {
            font-size: 16px;
            width: 18px;
            text-align: center;
            flex-shrink: 0;
            color: var(--primary);
        }

        /* MAIN */
        main { padding: 40px 0 64px; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 40px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-md);
        }

        .stat-card .label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            font-weight: 700;
            margin-bottom: 6px;
        }

        .stat-card .value {
            font-family: 'Poppins';
            font-size: 30px;
            font-weight: 800;
            color: var(--primary-dark);
            line-height: 1.1;
        }

        .section {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            padding: 24px;
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

        .section-head h2 { font-size: 18px; font-weight: 700; }
        .section-head p  { font-size: 13px; color: var(--muted); margin-top: 2px; }

        .toggle-panel {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
            border-radius: var(--radius);
            background: #f8fafc;
            border: 1px solid var(--line);
        }

        .toggle-status {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 52px;
            height: 28px;
            flex-shrink: 0;
        }

        .switch input { opacity: 0; width: 0; height: 0; }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: .3s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        input:checked+.slider { background-color: #10b981; }
        input:focus+.slider { box-shadow: 0 0 1px #10b981; }
        input:checked+.slider:before { transform: translateX(24px); }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--line);
            border-radius: var(--radius);
        }

        table.data { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        table.data thead { background: #f1f5f9; }
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
        table.data tbody tr:last-child td { border-bottom: none; }
        table.data tbody tr:hover { background: #f8fafc; }

        .name-block { display: flex; flex-direction: column; line-height: 1.25; }
        .name-block .contact-sub {
            font-size: 11.5px;
            color: var(--muted);
            font-weight: 500;
            margin-top: 2px;
        }

        /* Student type badges — no background, colored text only */
        .type-badge {
            display: inline-block;
            font-size: 11.5px;
            font-weight: 700;
            padding: 3px 0;
            border-radius: 0;
            letter-spacing: .02em;
            background: transparent;
            border: none;
        }

        .type-o { color: #1e40af; }
        .type-n { color: #166534; }
        .type-t { color: #92400e; }
        .type-default { color: #64748b; }

        /* Pending pill — no background, colored text only */
        .pending-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11.5px;
            font-weight: 700;
            padding: 3px 0;
            border-radius: 0;
            background: transparent;
            border: none;
            color: #92400e;
        }

        /* ID picture thumbnail + fallback */
        .id-photo-link {
            display: inline-block;
            line-height: 0;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid var(--line);
            transition: transform .15s, box-shadow .15s, border-color .15s;
            cursor: zoom-in;
        }

        .id-photo-link:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px -8px rgba(15, 23, 42, .35);
            border-color: var(--primary-2);
        }

        .id-photo-thumb {
            width: 44px;
            height: 44px;
            object-fit: cover;
            display: block;
        }

        .id-photo-missing {
            display: inline-block;
            font-size: 11.5px;
            color: var(--muted);
            font-style: italic;
        }

        /* ── Photo Lightbox ── */
        .lightbox-overlay {
            position: fixed;
            inset: 0;
            z-index: 10001;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.22s ease, visibility 0.22s ease;
        }

        .lightbox-overlay.open {
            opacity: 1;
            visibility: visible;
        }

        .lightbox-img {
            max-width: 92vw;
            max-height: 88vh;
            object-fit: contain;
            border-radius: 10px;
            background: #fff;
            padding: 6px;
            box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.65);
            transform: scale(0.94);
            transition: transform 0.22s ease;
            cursor: default;
        }

        .lightbox-overlay.open .lightbox-img {
            transform: scale(1);
        }

        .lightbox-close {
            position: absolute;
            top: 18px;
            right: 20px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: none;
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            font-size: 18px;
            cursor: pointer;
            display: grid;
            place-items: center;
            transition: background .15s, transform .15s;
        }

        .lightbox-close:hover {
            background: rgba(255, 255, 255, 0.28);
        }

        .lightbox-close:active {
            transform: scale(.94);
        }

        /* Final Enrollment search bar */
        .final-search-input {
            width: 320px;
            min-width: 220px;
            padding: 9px 12px;
            font-size: 13.5px;
            font-weight: 500;
            border-radius: 8px;
            text-transform: none;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: transform .15s, box-shadow .15s;
        }

        .btn-danger { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .btn-danger:hover { background: #fee2e2; }

        .btn-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .btn-success:hover { background: #d1fae5; }

        .btn-primary { background: #eef2ff; color: #1e40af; border: 1px solid #c7d2fe; }
        .btn-primary:hover { background: #e0e7ff; }

        .btn-cancel {
            background: #fff;
            color: var(--text);
            border: 1px solid var(--line);
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-cancel:hover { background: #f1f5f9; }

        .btn-mini { padding: 6px 12px; font-size: 12.5px; border-radius: 6px; gap: 5px; }
        .btn:disabled { opacity: .55; cursor: not-allowed; }

        .filter-select {
            font-family: inherit;
            font-size: 13.5px;
            font-weight: 600;
            padding: 9px 34px 9px 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            color: var(--text);
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 14px;
            min-width: 180px;
            transition: border-color .15s, box-shadow .15s;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary-2);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .filter-hint {
            font-size: 13px;
            color: var(--muted);
            text-align: center;
            padding: 20px 12px;
        }

        /* MODAL */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease, visibility 0.2s ease;
        }

        .modal-overlay.open { opacity: 1; visibility: visible; }

        .modal-box {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
            margin: 20px;
            padding: 24px;
            transform: translateY(10px);
            transition: transform 0.2s ease;
        }

        .modal-overlay.open .modal-box { transform: translateY(0); }

        .modal-box h3 { font-size: 18px; font-weight: 700; margin-bottom: 12px; color: var(--text); }
        .modal-box p { font-size: 14.5px; color: var(--muted); margin-bottom: 24px; line-height: 1.5; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 12px; }

        /* Camera enrollment modal */
        .camera-modal-box {
            max-width: 520px;
            max-height: 92vh;
            overflow-y: auto;
        }

        .camera-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .camera-modal-head h3 {
            margin: 0;
            font-size: 18px;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .camera-close {
            background: transparent;
            border: none;
            font-size: 18px;
            color: var(--muted);
            cursor: pointer;
            padding: 6px 8px;
            border-radius: 6px;
            transition: background .15s, color .15s;
        }

        .camera-close:hover { background: #f1f5f9; color: var(--text); }

        .camera-student-info {
            background: #f8fafc;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 14px;
            font-size: 13.5px;
        }

        .camera-student-info strong { color: var(--text); }

        .camera-box {
            position: relative;
            width: 100%;
            aspect-ratio: 4 / 3;
            background: #0f172a;
            border-radius: 10px;
            overflow: hidden;
            display: grid;
            place-items: center;
        }

        .camera-box video,
        .camera-box canvas,
        .camera-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* Mirror the live preview so the camera is "flipped" */
        .camera-box video {
            transform: scaleX(-1);
        }

        .camera-box canvas,
        .camera-box img.captured-preview { display: none; }

        .camera-box.captured video { display: none; }
        .camera-box.captured canvas,
        .camera-box.captured img.captured-preview { display: block; }

        .camera-placeholder {
            position: absolute;
            inset: 0;
            display: grid;
            place-items: center;
            color: rgba(255, 255, 255, .55);
            font-size: 13.5px;
            text-align: center;
            padding: 20px;
        }

        .camera-placeholder i {
            display: block;
            font-size: 30px;
            margin-bottom: 8px;
            opacity: .7;
        }

        .camera-box.captured .camera-placeholder,
        .camera-box.streaming .camera-placeholder { display: none; }

        .camera-actions {
            display: flex;
            gap: 10px;
            margin-top: 14px;
        }

        .camera-actions .btn { flex: 1; }

        .field-group { margin-top: 16px; }

        .field-label {
            display: block;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .field-input {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid var(--line);
            border-radius: 8px;
            font-family: inherit;
            font-size: 14.5px;
            color: var(--text);
            background: #fff;
            transition: border-color .15s, box-shadow .15s;
        }

        .field-input:focus {
            outline: none;
            border-color: var(--primary-2);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .field-hint {
            font-size: 11.5px;
            color: var(--muted);
            margin-top: 4px;
        }

        .camera-approve {
            margin-top: 20px;
            width: 100%;
            padding: 13px;
            font-size: 15px;
        }

        .camera-status {
            font-size: 12.5px;
            color: var(--muted);
            margin-top: 8px;
            text-align: center;
            min-height: 18px;
        }

        .camera-status.error { color: #b91c1c; font-weight: 600; }
        .camera-status.success { color: #065f46; font-weight: 600; }

        /* STATUS MODAL */
        .status-modal-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(2px);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease, visibility 0.2s ease;
        }

        .status-modal-overlay.show { opacity: 1; visibility: visible; }

        .status-modal-box {
            background: #fff;
            border-radius: 16px;
            padding: 40px 32px;
            text-align: center;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .status-modal-overlay.show .status-modal-box { transform: scale(1); }

        .status-icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            margin: 0 auto 24px auto;
            position: relative;
        }

        .status-icon-wrapper.success {
            background: #ecfdf5;
            color: #10b981;
            border: 4px solid #a7f3d0;
            animation: pulse-green 1s ease-out forwards;
        }

        .status-icon-wrapper.error {
            background: #fef2f2;
            color: #ef4444;
            border: 4px solid #fecaca;
            animation: pulse-red 1s ease-out forwards;
        }

        .status-icon-wrapper i { font-size: 40px; animation: draw-check 0.5s ease-out forwards; }

        @keyframes pulse-green {
            0%   { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6); }
            100% { box-shadow: 0 0 0 20px rgba(16, 185, 129, 0); }
        }

        @keyframes pulse-red {
            0%   { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6); }
            100% { box-shadow: 0 0 0 20px rgba(239, 68, 68, 0); }
        }

        @keyframes draw-check {
            0%   { transform: scale(0);   opacity: 0; }
            70%  { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(1);   opacity: 1; }
        }

        .status-title {
            font-family: 'Poppins', sans-serif;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text);
        }

        .status-message {
            font-size: 15px;
            color: var(--muted);
            line-height: 1.5;
        }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .nav-inner { padding: 12px 0; gap: 14px; min-height: 92px; }
            .nav-toggle { width: 46px; height: 46px; font-size: 19px; }
            .logo { gap: 16px; }
            .logo-mark { width: 68px; height: 68px; }
            .logo-divider { height: 46px; }
            .logo-text b { font-size: 23px; max-width: 380px; }
            .logo-text .school-name { font-size: 12.5px; max-width: 380px; }
            .page-label { font-size: 11.5px; padding: 7px 14px; }
        }

        @media (max-width: 820px) {
            .nav-inner { padding: 10px 0; gap: 10px; min-height: 76px; }
            .logo { gap: 12px; flex: 1; min-width: 0; }
            .logo-mark { width: 56px; height: 56px; }
            .logo-divider { height: 40px; }
            .logo-text b { font-size: 19px; max-width: 240px; }
            .logo-text .school-name { font-size: 11px; max-width: 240px; }
            .nav-toggle { width: 44px; height: 44px; font-size: 18px; }
            .page-label { font-size: 10.5px; padding: 6px 12px; gap: 6px; }
            .stats-grid { grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 22px; }
            .stat-card { padding: 16px; }
            .stat-card .value { font-size: 26px; }
            .section { padding: 20px; margin-bottom: 20px; }
        }

        @media (max-width: 640px) {
            .container { padding: 0 16px; }
            .section { padding: 18px 14px; margin-bottom: 18px; }
            .toggle-panel { flex-direction: column; align-items: stretch; }
            .nav-inner { padding: 10px 0; gap: 8px; min-height: 66px; }
            .logo { gap: 10px; }
            .logo-mark { width: 46px; height: 46px; }
            .logo-divider { display: none; }
            .logo-text b { font-size: 16px; max-width: 160px; }
            .logo-text .school-name { font-size: 10px; max-width: 160px; }
            .nav-toggle { width: 42px; height: 42px; font-size: 17px; }
            .page-label { display: none; }
            .nav-dropdown { width: 250px; }
            .stats-grid { grid-template-columns: 1fr; gap: 12px; margin-bottom: 20px; }
            .stat-card { padding: 16px; }
            .section-head { margin-bottom: 14px; }
            .section-head h2 { font-size: 16px; }
            .filter-select { width: 100%; }
            .camera-actions { flex-direction: column; }
            .lightbox-close {
                top: 12px;
                right: 14px;
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            .lightbox-img {
                padding: 4px;
            }
            .final-search-input {
                width: 100%;
                min-width: 0;
            }
        }

        @media (max-width: 400px) {
            .logo-mark { width: 40px; height: 40px; }
            .logo-text b { font-size: 14px; max-width: 130px; }
            .logo-text .school-name { font-size: 9px; max-width: 130px; }
        }
    </style>
</head>

<body>
    <?php if ($flashSuccess || $flashError): ?>
        <div class="status-modal-overlay show" id="statusModal">
            <div class="status-modal-box">
                <div class="status-icon-wrapper <?php echo $flashSuccess ? 'success' : 'error'; ?>">
                    <i class="fas <?php echo $flashSuccess ? 'fa-check' : 'fa-exclamation'; ?>"></i>
                </div>
                <div class="status-title"><?php echo $flashSuccess ? 'Success!' : 'Error!'; ?></div>
                <div class="status-message"><?php echo htmlspecialchars($flashSuccess ?: $flashError); ?></div>
            </div>
        </div>
    <?php endif; ?>

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
                    <span class="school-name"><?php echo htmlspecialchars($adminSchoolName); ?></span>
                </div>
            </a>

            <div class="nav-right">
                <span class="page-label">Admin Dashboard</span>

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
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Students</div>
                <div class="value"><?php echo number_format($totalStudents); ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Teachers</div>
                <div class="value"><?php echo number_format($totalTeachers); ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Subject Assignments</div>
                <div class="value"><?php echo number_format($totalAssignments); ?></div>
            </div>
        </div>

        <section class="section">
            <div class="section-head">
                <div>
                    <h2>Enrollment Window</h2>
                    <p>Toggle whether Old Students can enroll for S.Y. <?php echo htmlspecialchars($currentYear); ?>.</p>
                </div>
            </div>
            <div class="toggle-panel">
                <div class="toggle-status">
                    <label class="switch">
                        <input type="checkbox" id="enrollmentSwitch" <?php echo $enrollmentOpen ? 'checked' : ''; ?>
                            onchange="handleToggleChange(this)">
                        <span class="slider"></span>
                    </label>
                    <span id="toggleLabelText"><?php echo $enrollmentOpen ? 'Enrollment is OPEN' : 'Enrollment is CLOSED'; ?></span>
                </div>
            </div>
        </section>

        <?php /* FINAL ENROLLMENT — Pending enrolled_students for the current academic year */ ?>
        <?php if (!empty($studentsForEnrollment)): ?>
            <section class="section">
                <div class="section-head">
                    <div>
                        <h2>Final Enrollment</h2>
                        <p>Pending enrollments for S.Y. <?php echo htmlspecialchars($currentYear); ?> — approve by capturing a photo and updating the LRN.</p>
                    </div>
                    <div style="display:flex; gap:8px; align-items:center;">
                        <input type="text" class="field-input final-search-input" id="finalSearchInput"
                               placeholder="Search by name…"
                               autocomplete="off" oninput="filterFinalTable(this.value)">
                        <button type="button" class="btn btn-cancel btn-mini" id="finalSearchClearBtn"
                                onclick="clearFinalSearch()" style="display:none;">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="data" id="finalEnrollmentTable">
                        <thead>
                            <tr>
                                <th>Account #</th>
                                <th>Full Name</th>
                                <th>Age</th>
                                <th>Sex</th>
                                <th>Grade Level</th>
                                <th>Section</th>
                                <th>Type</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($studentsForEnrollment as $s): ?>
                                <?php
                                $sFullName = trim(
                                    ($s['first_name'] ?? '') . ' ' .
                                    ($s['middle_name'] ?? '') . ' ' .
                                    ($s['surname'] ?? '')
                                );
                                $sAccount = (string) ($s['account_number'] ?? '');
                                $sType    = (string) ($s['student_type'] ?? '');

                                $sGradeLevel = $s['en_grade_level'] ?? '';
                                $sSection    = $s['en_section'] ?? '';
                                $sEnStatus   = strtolower(trim((string) ($s['en_status'] ?? '')));

                                $sLrn = trim((string) ($s['student_lrn'] ?? ''));
                                ?>
                                <tr data-name="<?php echo htmlspecialchars(strtolower($sFullName), ENT_QUOTES); ?>">
                                    <td><?php echo htmlspecialchars($sAccount ?: '—'); ?></td>
                                    <td>
                                        <div class="name-block">
                                            <span><?php echo htmlspecialchars($sFullName ?: '—'); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) ($s['age'] ?? '—')); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($s['sex'] ?? '—')); ?></td>
                                    <td>
                                        <?php echo $sGradeLevel !== '' && $sGradeLevel !== null
                                            ? ('Grade ' . htmlspecialchars((string) $sGradeLevel))
                                            : '—'; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($sSection !== '' && $sSection !== null ? (string) $sSection : '—'); ?></td>
                                    <td>
                                        <span class="type-badge <?php echo htmlspecialchars(studentTypeBadgeClass($sType)); ?>">
                                            <?php echo htmlspecialchars(studentTypeLabel($sType)); ?>
                                        </span>
                                    </td>
                               
                                    <td>
                                        <button type="button" class="btn btn-primary btn-mini"
                                            onclick="openCameraModal(
                                                '<?php echo htmlspecialchars($sAccount, ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars(addslashes($sFullName), ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars(addslashes((string) ($sGradeLevel ?? '')), ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars(addslashes((string) ($sSection ?? '')), ENT_QUOTES); ?>'
                                            )">
                                            <i class="fas fa-camera"></i> Take Camera
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="finalNoMatchesRow" style="display:none;">
                                <td colspan="9" class="filter-hint">
                                    <i class="fas fa-search" style="margin-right:6px;"></i>
                                    No students match your search.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <?php /* ALL STUDENTS — every Active enrolled_students row with student data */ ?>
        <?php if (!empty($enrolledStudents)): ?>
            <section class="section">
                <div class="section-head">
                    <div>
                        <h2>All Students</h2>
                        <p>All students with an Active enrollment record.</p>
                    </div>
                    <div>
                        <form method="GET" action="dashboard.php" id="gradeFilterForm">
                            <select class="filter-select" name="grade" id="gradeFilter"
                                onchange="document.getElementById('gradeFilterForm').submit();">
                                <option value="all" <?php echo $isAllGrades ? 'selected' : ''; ?>>All Levels</option>
                                <?php foreach ($gradeOptions as $g): ?>
                                    <option value="<?php echo $g; ?>" <?php echo ($gradeFilter === (string) $g) ? 'selected' : ''; ?>>
                                        Grade <?php echo $g; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>

                <?php if (empty($filteredEnrolledStudents)): ?>
                    <div class="filter-hint">
                        <i class="fas fa-user-slash" style="margin-right: 6px;"></i>
                        No students found<?php echo $isAllGrades ? '' : ' for Grade ' . htmlspecialchars($gradeFilter); ?>.
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="data">
                            <thead>
                                <tr>
                                    <th>Account #</th>
                                    <th>Full Name</th>
                                    <th>LRN</th>
                                    <th>Grade Level</th>
                                    <th>Section</th>
                                    <th>Academic Year</th>
                                    <th>ID Picture</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filteredEnrolledStudents as $s): ?>
                                    <?php
                                    $sFullName = trim(
                                        ($s['first_name'] ?? '') . ' ' .
                                        ($s['middle_name'] ?? '') . ' ' .
                                        ($s['surname'] ?? '')
                                    );
                                    $sAccount = (string) ($s['account_number'] ?? '');

                                    $sLrn = trim((string) ($s['student_lrn'] ?? ''));

                                    $sPhotoFile = trim((string) ($s['profile_account'] ?? ''));
                                    $sPhotoUrl  = $sPhotoFile !== ''
                                        ? '../Students_Profile/' . $sPhotoFile
                                        : '';

                                    $sStatus = trim((string) ($s['enrollment_record_status'] ?? 'Active'));
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sAccount ?: '—'); ?></td>
                                        <td>
                                            <div class="name-block">
                                                <span><?php echo htmlspecialchars($sFullName ?: '—'); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($sLrn !== '' ? $sLrn : '—'); ?></td>
                                        <td>Grade <?php echo htmlspecialchars((string) ($s['grade_level'] ?? '—')); ?></td>
                                        <td><?php echo htmlspecialchars((string) ($s['section'] ?? '—')); ?></td>
                                        <td><?php echo htmlspecialchars((string) ($s['academic_year'] ?? '—')); ?></td>
                                        <td>
                                            <?php if ($sPhotoUrl !== ''): ?>
                                                <a href="javascript:void(0);" class="id-photo-link" title="View ID picture"
                                                   onclick="openLightbox('<?php echo htmlspecialchars($sPhotoUrl, ENT_QUOTES); ?>', '<?php echo htmlspecialchars(addslashes($sFullName ?: $sAccount), ENT_QUOTES); ?>')">
                                                    <img src="<?php echo htmlspecialchars($sPhotoUrl); ?>"
                                                         alt="ID picture of <?php echo htmlspecialchars($sFullName ?: $sAccount); ?>"
                                                         class="id-photo-thumb"
                                                         onerror="this.parentElement.innerHTML='<span class=&quot;id-photo-missing&quot;>No photo</span>';">
                                                </a>
                                            <?php else: ?>
                                                <span class="id-photo-missing">No photo</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <div class="modal-overlay" id="enrollmentModal">
        <div class="modal-box">
            <h3 id="modalTitle" style="color: #b45309;">WARNING!</h3>
            <p>You are about to <strong><span id="modalActionText">close</span></strong> the enrollment.<br><br>Please
                note that once changed, you <strong>cannot open or close</strong> the enrollment again for the next 24
                hours (1 day).</p>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEnrollmentModal()">Cancel</button>
                <button type="button" class="btn btn-danger" id="modalConfirmBtn"
                    onclick="confirmEnrollmentToggle()">Close</button>
            </div>
        </div>
    </div>

    <?php /* Camera + LRN + Approve modal */ ?>
    <div class="modal-overlay" id="cameraModal">
        <div class="modal-box camera-modal-box">
            <div class="camera-modal-head">
                <h3><i class="fas fa-camera"></i> Approve Student</h3>
                <button type="button" class="camera-close" onclick="closeCameraModal()" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="camera-student-info" id="cameraStudentInfo">
                Loading student…
            </div>

            <div class="camera-box" id="cameraBox">
                <video id="cameraVideo" autoplay playsinline muted></video>
                <canvas id="cameraCanvas"></canvas>
                <img id="capturedPreview" class="captured-preview" alt="Captured photo" />
                <div class="camera-placeholder" id="cameraPlaceholder">
                    <div>
                        <i class="fas fa-video"></i>
                        Camera preview will appear here
                    </div>
                </div>
            </div>

            <div class="camera-actions">
                <button type="button" class="btn btn-primary" id="captureBtn" onclick="capturePhoto()">
                    <i class="fas fa-camera"></i> Capture
                </button>
                <button type="button" class="btn btn-cancel" id="retakeBtn" onclick="retakePhoto()" style="display:none;">
                    <i class="fas fa-rotate-left"></i> Retake
                </button>
            </div>

            <div class="field-group">
                <label class="field-label" for="cameraLrnInput">LRN (12 digits)</label>
                <input type="text" class="field-input" id="cameraLrnInput"
                    placeholder="Enter 12-digit LRN" autocomplete="off" inputmode="numeric"
                    pattern="[0-9]{12}" maxlength="12" />
                <div class="field-hint">Must be exactly 12 digits (numbers only).</div>
            </div>

            <button type="button" class="btn btn-success camera-approve" id="approveBtn" onclick="approveStudent()">
                <i class="fas fa-check"></i> Approve Student
            </button>

            <div class="camera-status" id="cameraStatus"></div>
        </div>
    </div>

    <!-- Lightbox for viewing ID pictures -->
    <div class="lightbox-overlay" id="photoLightbox" onclick="closeLightbox(event)">
        <button type="button" class="lightbox-close" onclick="closeLightbox(event)" aria-label="Close">
            <i class="fas fa-times"></i>
        </button>
        <img class="lightbox-img" id="lightboxImg" src="" alt="ID picture preview" />
    </div>

    <script>
        /* ── NAVBAR ── */
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

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        /* ── FINAL ENROLLMENT SEARCH (name only) ── */
        function filterFinalTable(term) {
            const table    = document.getElementById('finalEnrollmentTable');
            const clearBtn = document.getElementById('finalSearchClearBtn');
            const noMatch  = document.getElementById('finalNoMatchesRow');
            if (!table) return;

            const q = (term || '').trim().toLowerCase();

            if (clearBtn) clearBtn.style.display = q !== '' ? '' : 'none';

            const rows = table.querySelectorAll('tbody tr:not(#finalNoMatchesRow)');
            let visibleCount = 0;

            rows.forEach((row) => {
                const name = row.dataset.name || '';

                const nameStartsWith = name.startsWith(q);
                const wordMatches    = name.split(/\s+/).some(w => w.startsWith(q));

                if (q === '' || nameStartsWith || wordMatches) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (noMatch) {
                noMatch.style.display = (visibleCount === 0 && q !== '') ? '' : 'none';
            }
        }

        function clearFinalSearch() {
            const input = document.getElementById('finalSearchInput');
            if (!input) return;
            input.value = '';
            filterFinalTable('');
            input.focus();
        }

        document.addEventListener('keydown', (e) => {
            const input = document.getElementById('finalSearchInput');
            if (e.key === 'Escape' && input && document.activeElement === input) {
                clearFinalSearch();
            }
        });

        /* ── PHOTO LIGHTBOX ── */
        const photoLightbox = document.getElementById('photoLightbox');
        const lightboxImg   = document.getElementById('lightboxImg');

        function openLightbox(src, alt) {
            if (!src) return;
            lightboxImg.src = src;
            lightboxImg.alt = alt ? ('ID picture of ' + alt) : 'ID picture preview';
            photoLightbox.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox(e) {
            if (e && e.target && e.target === lightboxImg) return;
            photoLightbox.classList.remove('open');
            lightboxImg.removeAttribute('src');
            document.body.style.overflow = '';
        }

        lightboxImg.addEventListener('click', (e) => e.stopPropagation());

        photoLightbox.addEventListener('click', (e) => {
            if (e.target === photoLightbox) closeLightbox(e);
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && photoLightbox.classList.contains('open')) {
                closeLightbox();
                return;
            }
        });

        /* ── ENROLLMENT TOGGLE MODAL ── */
        let currentToggleAction = '';
        let isOriginalStateOpen = <?php echo $enrollmentOpen ? 'true' : 'false'; ?>;

        const switchEl = document.getElementById('enrollmentSwitch');
        const labelText = document.getElementById('toggleLabelText');

        function handleToggleChange(checkbox) {
            currentToggleAction = checkbox.checked ? 'open' : 'close';

            const modalActionText = document.getElementById('modalActionText');
            const modalConfirmBtn = document.getElementById('modalConfirmBtn');

            modalActionText.textContent = currentToggleAction;

            if (currentToggleAction === 'open') {
                modalConfirmBtn.textContent = 'Open';
                modalConfirmBtn.className = 'btn btn-success';
            } else {
                modalConfirmBtn.textContent = 'Close';
                modalConfirmBtn.className = 'btn btn-danger';
            }

            document.getElementById('enrollmentModal').classList.add('open');
        }

        function closeEnrollmentModal() {
            switchEl.checked = isOriginalStateOpen;
            document.getElementById('enrollmentModal').classList.remove('open');
        }

        function confirmEnrollmentToggle() {
            document.getElementById('enrollmentModal').classList.remove('open');

            if (currentToggleAction === 'open') {
                labelText.textContent = 'Enrollment is OPEN';
                isOriginalStateOpen = true;
            } else {
                labelText.textContent = 'Enrollment is CLOSED';
                isOriginalStateOpen = false;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../API_ADMIN/enrollment-opener.php';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'toggle_enrollment';
            form.appendChild(actionInput);

            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'new_status';
            statusInput.value = (currentToggleAction === 'open') ? '1' : '0';
            form.appendChild(statusInput);

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);

            document.body.appendChild(form);
            form.submit();
        }

        document.getElementById('enrollmentModal').addEventListener('click', function (e) {
            if (e.target === this) closeEnrollmentModal();
        });

        /* ── AUTO REFRESH AFTER STATUS MODAL ── */
        const statusModal = document.getElementById('statusModal');
        if (statusModal) {
            setTimeout(() => window.location.reload(), 1000);
        }

        /* ── CAMERA MODAL ── */
        let cameraStream = null;
        let cameraAccount = '';
        let cameraFullName = '';
        let cameraGrade = '';
        let cameraSection = '';
        let capturedDataUrl = '';

        const cameraModal = document.getElementById('cameraModal');
        const cameraBox = document.getElementById('cameraBox');
        const cameraVideo = document.getElementById('cameraVideo');
        const cameraCanvas = document.getElementById('cameraCanvas');
        const capturedPreview = document.getElementById('capturedPreview');
        const cameraPlaceholder = document.getElementById('cameraPlaceholder');
        const captureBtn = document.getElementById('captureBtn');
        const retakeBtn = document.getElementById('retakeBtn');
        const approveBtn = document.getElementById('approveBtn');
        const cameraLrnInput = document.getElementById('cameraLrnInput');
        const cameraStatus = document.getElementById('cameraStatus');
        const cameraStudentInfo = document.getElementById('cameraStudentInfo');

        cameraLrnInput.addEventListener('input', () => {
            cameraLrnInput.value = cameraLrnInput.value.replace(/\D+/g, '').slice(0, 12);
        });

        function setCameraStatus(msg, type) {
            cameraStatus.textContent = msg || '';
            cameraStatus.className = 'camera-status' + (type ? ' ' + type : '');
        }

        async function startCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                setCameraStatus('Camera is not supported in this browser.', 'error');
                cameraPlaceholder.style.display = 'grid';
                cameraPlaceholder.innerHTML = '<div><i class="fas fa-triangle-exclamation"></i>Camera not supported</div>';
                return;
            }

            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 960 } },
                    audio: false,
                });
                cameraVideo.srcObject = cameraStream;
                cameraBox.classList.add('streaming');
                setCameraStatus('');
            } catch (err) {
                console.error('Camera error:', err);
                let msg = 'Unable to access camera.';
                if (err && err.name === 'NotAllowedError') msg = 'Camera permission was denied. Please allow access and try again.';
                else if (err && err.name === 'NotFoundError') msg = 'No camera device was found.';
                setCameraStatus(msg, 'error');
                cameraPlaceholder.innerHTML = '<div><i class="fas fa-triangle-exclamation"></i>' + msg + '</div>';
            }
        }

        function stopCamera() {
            if (cameraStream) {
                cameraStream.getTracks().forEach(t => t.stop());
                cameraStream = null;
            }
            cameraVideo.srcObject = null;
            cameraBox.classList.remove('streaming');
        }

        function openCameraModal(account, fullName, grade, section) {
            cameraAccount = account || '';
            cameraFullName = fullName || '';
            cameraGrade = grade || '';
            cameraSection = section || '';

            const gradeTxt = cameraGrade ? ('Grade ' + cameraGrade) : 'Grade —';
            const sectionTxt = cameraSection ? (' - ' + cameraSection) : '';

            cameraStudentInfo.innerHTML =
                '<strong>' + escapeHtml(cameraFullName) + '</strong>' +
                ' &middot; Account #: ' + escapeHtml(cameraAccount) +
                '<br><span style="color:#64748b">' + escapeHtml(gradeTxt + sectionTxt) + '</span>';

            cameraLrnInput.value = '';
            capturedDataUrl = '';

            cameraBox.classList.remove('captured');
            capturedPreview.removeAttribute('src');
            captureBtn.style.display = '';
            retakeBtn.style.display = 'none';
            approveBtn.disabled = false;
            setCameraStatus('Starting camera…');

            cameraModal.classList.add('open');
            startCamera();
        }

        function closeCameraModal() {
            stopCamera();
            cameraModal.classList.remove('open');
            cameraBox.classList.remove('captured');
            capturedPreview.removeAttribute('src');
            capturedDataUrl = '';
            cameraLrnInput.value = '';
            setCameraStatus('');
        }

        function capturePhoto() {
            if (!cameraStream) {
                setCameraStatus('Camera is not active.', 'error');
                return;
            }

            const w = cameraVideo.videoWidth || 640;
            const h = cameraVideo.videoHeight || 480;
            cameraCanvas.width = w;
            cameraCanvas.height = h;

            const ctx = cameraCanvas.getContext('2d');
            ctx.translate(w, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(cameraVideo, 0, 0, w, h);
            ctx.setTransform(1, 0, 0, 1, 0, 0);

            capturedDataUrl = cameraCanvas.toDataURL('image/jpeg', 0.85);

            capturedPreview.src = capturedDataUrl;
            cameraBox.classList.add('captured');

            captureBtn.style.display = 'none';
            retakeBtn.style.display = '';
            setCameraStatus('Photo captured. Fill in LRN, then approve.', 'success');
        }

        function retakePhoto() {
            capturedDataUrl = '';
            capturedPreview.removeAttribute('src');
            cameraBox.classList.remove('captured');
            captureBtn.style.display = '';
            retakeBtn.style.display = 'none';
            setCameraStatus('');
        }

        function approveStudent() {
            if (!cameraAccount) {
                setCameraStatus('Missing student account.', 'error');
                return;
            }
            if (!capturedDataUrl) {
                setCameraStatus('Please capture a photo first.', 'error');
                return;
            }

            const lrn = (cameraLrnInput.value || '').trim();
            if (!/^\d{12}$/.test(lrn)) {
                setCameraStatus('LRN must be exactly 12 digits (numbers only).', 'error');
                cameraLrnInput.focus();
                return;
            }

            approveBtn.disabled = true;
            setCameraStatus('Submitting approval…');

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../API_ADMIN/enroll-students.php';

            const fields = {
                action: 'approve_with_capture',
                account_number: cameraAccount,
                lrn: lrn,
                grade_level: cameraGrade,
                section: cameraSection,
                photo_data: capturedDataUrl,
                csrf_token: csrfToken,
            };

            for (const [name, value] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
        }

        function escapeHtml(str) {
            return String(str)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        cameraModal.addEventListener('click', function (e) {
            if (e.target === this) closeCameraModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (cameraModal.classList.contains('open')) closeCameraModal();
                if (document.getElementById('enrollmentModal').classList.contains('open')) closeEnrollmentModal();
            }
        });
    </script>
</body>

</html>