<?php
// student/class-activities.php
// Combined page: Class Updates (downloadable) + My Submissions (upload/list/delete via JS)
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
   CURRENT ACADEMIC YEAR
   ------------------------------------------------------------ */
function currentAcademicYear(): string
{
    $y = (int) date('Y');
    return ((int) date('n') < 6) ? (($y - 1) . '-' . $y) : ($y . '-' . ($y + 1));
}
$currentYear = currentAcademicYear();

/* ------------------------------------------------------------
   RESOLVE STUDENT'S GRADE LEVEL + SECTION
   ------------------------------------------------------------ */
$effectiveGrade = '';
$effectiveSection = '';

if ($isLoggedIn && $user) {
    try {
        $stmt = $pdo->prepare("
            SELECT grade_level, section
            FROM enrolled_students
            WHERE account_number = ?
              AND academic_year = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$user['account_number'], $currentYear]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$row) {
            $stmt = $pdo->prepare("
                SELECT grade_level, section
                FROM enrolled_students
                WHERE account_number = ?
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmt->execute([$user['account_number']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        if ($row) {
            $effectiveGrade = trim((string) ($row['grade_level'] ?? ''));
            $effectiveSection = trim((string) ($row['section'] ?? ''));
        }
    } catch (PDOException $e) {
        error_log("enrollment lookup failed: " . $e->getMessage());
    }

    if ($effectiveGrade === '' && !empty($user['grade_level'])) {
        $effectiveGrade = trim((string) $user['grade_level']);
    }
}

/* ------------------------------------------------------------
   FETCH CLASS UPDATES (teacher files — downloadable)
   ------------------------------------------------------------ */
$classUpdates = [];
$updatesFetchErr = null;

if ($isLoggedIn && $user) {
    try {
        if ($effectiveGrade !== '' && $effectiveSection !== '') {
            $stmt = $pdo->prepare("
                SELECT id, grade_level, section, subject, file_name, type, modified_at
                FROM class_updates
                WHERE CAST(grade_level AS CHAR) = ?
                  AND section = ?
                ORDER BY modified_at DESC, id DESC
            ");
            $stmt->execute([$effectiveGrade, $effectiveSection]);
        } else {
            $stmt = null;
        }

        if ($stmt !== null) {
            $classUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    } catch (PDOException $e) {
        $updatesFetchErr = $e->getMessage();
        error_log("class updates fetch failed: " . $updatesFetchErr);
        $classUpdates = [];
    }
}

/* ------------------------------------------------------------
   SUBJECT LIST for the upload dropdown
   ------------------------------------------------------------ */
$subjectChoices = [];
try {
    if ($effectiveGrade !== '' && $effectiveSection !== '') {
        $stmt = $pdo->prepare("
            SELECT DISTINCT subject
            FROM class_updates
            WHERE CAST(grade_level AS CHAR) = ?
              AND section = ?
            ORDER BY subject ASC
        ");
        $stmt->execute([$effectiveGrade, $effectiveSection]);
        $subjectChoices = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
} catch (PDOException $e) {
    error_log("subject list fetch failed: " . $e->getMessage());
}

if (empty($subjectChoices)) {
    $subjectChoices = [
        'Filipino',
        'English',
        'Mathematics',
        'Science',
        'Araling Panlipunan',
        'TLE',
        'MAPEH',
        'ESP',
    ];
}

/* ------------------------------------------------------------
   HELPERS
   ------------------------------------------------------------ */
function fileIconClass(string $type): string
{
    $t = strtolower(trim($type));
    return match ($t) {
        'pdf' => 'fa-file-pdf',
        'doc', 'docx', 'docs' => 'fa-file-word',
        'xls', 'xlsx', 'excel' => 'fa-file-excel',
        'ppt', 'pptx' => 'fa-file-powerpoint',
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'image' => 'fa-file-image',
        'zip', 'rar', '7z' => 'fa-file-zipper',
        'mp4', 'mov', 'avi', 'video' => 'fa-file-video',
        'mp3', 'wav', 'audio' => 'fa-file-audio',
        'txt' => 'fa-file-lines',
        'link', 'url' => 'fa-link',
        default => 'fa-file',
    };
}

function fileTypeColor(string $type): string
{
    $t = strtolower(trim($type));
    return match ($t) {
        'pdf' => '#dc2626',
        'doc', 'docx', 'docs' => '#2563eb',
        'xls', 'xlsx', 'excel' => '#16a34a',
        'ppt', 'pptx' => '#ea580c',
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'image' => '#7c3aed',
        'zip', 'rar', '7z' => '#a16207',
        'mp4', 'mov', 'avi', 'video' => '#db2777',
        'mp3', 'wav', 'audio' => '#0891b2',
        'txt' => '#475569',
        'link', 'url' => '#0ea5e9',
        default => '#64748b',
    };
}

function buildDownloadUrl(array $f): string
{
    $name = trim((string) ($f['file_name'] ?? ''));
    $type = trim((string) ($f['type'] ?? ''));

    if ($name === '') {
        return '#';
    }

    $hasExt = pathinfo($name, PATHINFO_EXTENSION) !== '';
    $filename = $hasExt ? $name : ($name . ($type !== '' ? '.' . $type : ''));

    return '../uploads/class_updates/' . rawurlencode($filename);
}
?>
<!DOCTYPE html>
<html lang="fil">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>" />
    <title>Assignments &amp; Submission — Eguia National High School</title>
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

        /* PANEL */
        .panel {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
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
            border-bottom: 1px dashed var(--line);
            flex-wrap: wrap;
        }

        .page-head h2 {
            font-size: 20px;
            font-weight: 800;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-head h2 i {
            color: var(--primary);
            font-size: 18px;
        }

        .page-head .sub {
            font-size: 13px;
            color: var(--muted);
        }

        .class-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .class-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 999px;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            color: var(--primary-dark);
        }

        .class-badge i {
            font-size: 11px;
        }

        /* SECTION HEADER */
        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 28px 0 12px;
            flex-wrap: wrap;
        }

        .section-head:first-of-type {
            margin-top: 0;
        }

        .section-head h3 {
            font-size: 15px;
            font-weight: 800;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .section-head h3 i {
            color: var(--primary);
            font-size: 14px;
        }

        /* BREADCRUMB */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--muted);
            padding: 10px 14px;
            background: #f8fafc;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .breadcrumb i {
            font-size: 12px;
            color: var(--primary);
        }

        .breadcrumb .sep {
            color: #cbd5e1;
        }

        .breadcrumb .crumb-active {
            color: var(--text);
            font-weight: 600;
        }

        /* TOOLBAR */
        .toolbar {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .toolbar .count-pill {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 999px;
            background: #f1f5f9;
            border: 1px solid var(--line);
            color: var(--muted);
        }

        /* UPLOAD FORM */
        .upload-box {
            background: linear-gradient(135deg, #f8fafc, #eef2ff);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 18px;
            margin-bottom: 18px;
        }

        .upload-box .upload-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 14px;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .upload-box .upload-title i {
            color: var(--primary);
        }

        .upload-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr) auto;
            gap: 10px;
            align-items: end;
        }

        .upload-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 0;
        }

        .upload-field label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
        }

        .upload-field select,
        .upload-field input[type="file"] {
            width: 100%;
            padding: 9px 12px;
            font-size: 13.5px;
            font-family: inherit;
            color: var(--text);
            background: #fff;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }

        .upload-field select:focus,
        .upload-field input[type="file"]:focus {
            border-color: var(--primary-2);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .15);
        }

        .upload-field input[type="file"] {
            padding: 7px 10px;
            cursor: pointer;
        }

        .upload-field input[type="file"]::file-selector-button {
            font-family: inherit;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--primary-dark);
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 6px;
            padding: 5px 10px;
            margin-right: 10px;
            cursor: pointer;
            transition: background .15s;
        }

        .upload-field input[type="file"]::file-selector-button:hover {
            background: #dbe4ff;
        }

        .upload-hint {
            font-size: 11.5px;
            color: var(--muted);
            margin-top: 10px;
        }

        /* EXPLORER (flat list) */
        .explorer {
            border: 1px solid var(--line);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .explorer-head,
        .file-row {
            display: grid;
            grid-template-columns: 40px minmax(0, 2.2fr) minmax(0, 1fr) 100px 150px 130px;
            gap: 10px;
            align-items: center;
        }

        .explorer-head {
            padding: 10px 14px;
            background: #f1f5f9;
            border-bottom: 1px solid var(--line);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
        }

        .explorer-head .col-action {
            text-align: right;
        }

        .file-row {
            padding: 10px 14px;
            border-bottom: 1px solid var(--line);
            background: #fff;
            transition: background .12s;
        }

        .file-row:last-child {
            border-bottom: none;
        }

        .file-row:hover {
            background: #f8fafc;
        }

        .file-row .file-icon {
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            border-radius: 6px;
            background: #f1f5f9;
            font-size: 15px;
        }

        .file-name {
            display: flex;
            flex-direction: column;
            gap: 1px;
            min-width: 0;
        }

        .file-name .fname {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-name .fext {
            font-size: 11px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .05em;
            font-weight: 600;
        }

        .file-subject {
            font-size: 13px;
            color: var(--text);
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-type-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            padding: 3px 10px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: #f8fafc;
            color: var(--muted);
            width: fit-content;
        }

        .file-date {
            font-size: 12.5px;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* BUTTONS */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 9px 18px;
            border-radius: var(--radius);
            border: 1px solid var(--primary-dark);
            background: linear-gradient(180deg, var(--primary-2), var(--primary));
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            white-space: nowrap;
            text-decoration: none;
            transition: background .15s, transform .12s, box-shadow .15s;
            box-shadow: 0 4px 10px -4px rgba(29, 78, 216, .55);
        }

        .btn-primary:hover {
            background: linear-gradient(180deg, var(--primary), var(--primary-dark));
            box-shadow: 0 6px 14px -4px rgba(29, 78, 216, .65);
        }

        .btn-primary:active {
            transform: scale(.97);
        }

        .btn-primary i {
            font-size: 12px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12.5px;
        }

        /* DELETE BUTTON */
        .col-action {
            display: flex;
            justify-content: flex-end;
        }

        .btn-delete {
            display: inline-grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border-radius: var(--radius);
            border: 1px solid var(--red-line);
            background: var(--red-bg);
            color: var(--red);
            cursor: pointer;
            font-size: 13px;
            transition: background .15s, color .15s, transform .12s;
            font-family: inherit;
        }

        .btn-delete:hover {
            background: var(--red);
            color: #fff;
            border-color: var(--red);
        }

        .btn-delete:active {
            transform: scale(.94);
        }

        .btn-delete:disabled {
            opacity: .55;
            cursor: not-allowed;
            transform: none;
        }

        /* EMPTY */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
            font-size: 14px;
            border: 1px dashed var(--line);
            border-radius: var(--radius);
        }

        .empty-state i {
            font-size: 34px;
            color: #cbd5e1;
            margin-bottom: 10px;
            display: block;
        }

        .empty-state .debug {
            display: block;
            margin-top: 10px;
            font-size: 11.5px;
            color: #b91c1c;
            font-family: monospace;
            word-break: break-all;
        }

        /* SKELETON */
        .skeleton {
            border: 1px solid var(--line);
            border-radius: var(--radius);
            overflow: hidden;
            margin-top: 4px;
        }

        .skeleton-row {
            display: grid;
            grid-template-columns: 40px minmax(0, 2.2fr) minmax(0, 1fr) 100px 150px 130px;
            gap: 10px;
            align-items: center;
            padding: 14px;
            border-bottom: 1px solid var(--line);
            background: #fff;
        }

        .skeleton-row:last-child {
            border-bottom: none;
        }

        .sk {
            display: block;
            height: 14px;
            border-radius: 4px;
            background: linear-gradient(90deg, #eef2ff 25%, #f8fafc 50%, #eef2ff 75%);
            background-size: 200% 100%;
            animation: skShimmer 1.2s ease-in-out infinite;
        }

        .sk.icon {
            width: 30px;
            height: 30px;
            border-radius: 6px;
        }

        .sk.w-60 {
            width: 60%;
        }

        .sk.w-40 {
            width: 40%;
        }

        .sk.w-30 {
            width: 30%;
        }

        @keyframes skShimmer {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        /* MODALS */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .55);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity .25s ease, visibility .25s ease;
            padding: 20px;
        }

        .modal-overlay.open {
            opacity: 1;
            visibility: visible;
        }

        .modal-box {
            background: #fff;
            border-radius: 16px;
            padding: 36px 28px 28px;
            text-align: center;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            transform: scale(.9);
            transition: transform .3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .modal-overlay.open .modal-box {
            transform: scale(1);
        }

        .modal-icon-wrap {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            margin: 0 auto 22px;
            position: relative;
        }

        .modal-icon-wrap.success {
            background: var(--green-bg);
            border: 4px solid var(--green-line);
            color: var(--green);
        }

        .modal-icon-wrap.error {
            background: var(--red-bg);
            border: 4px solid var(--red-line);
            color: var(--red);
        }

        .modal-icon-wrap.confirm {
            background: var(--amber-bg);
            border: 4px solid var(--amber-line);
            color: var(--amber);
        }

        .modal-icon-wrap i {
            font-size: 36px;
        }

        .modal-icon-wrap.success i {
            animation: popCheck .5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        .modal-icon-wrap.success::after {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            border: 4px solid var(--green);
            animation: ringPulse 1s ease-out forwards;
            opacity: 0;
        }

        @keyframes popCheck {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            70% {
                transform: scale(1.15);
                opacity: 1;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes ringPulse {
            0% {
                transform: scale(1);
                opacity: .7;
            }

            100% {
                transform: scale(1.6);
                opacity: 0;
            }
        }

        .modal-title {
            font-family: 'Poppins', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 6px;
        }

        .modal-message {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.55;
            margin-bottom: 24px;
            word-break: break-word;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .modal-btn {
            flex: 1;
            min-width: 120px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 10px 16px;
            border-radius: var(--radius);
            font-size: 13.5px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--text);
            transition: background .15s, transform .12s;
        }

        .modal-btn:hover {
            background: #f1f5f9;
        }

        .modal-btn:active {
            transform: scale(.97);
        }

        .modal-btn.danger {
            border-color: var(--red);
            background: var(--red);
            color: #fff;
        }

        .modal-btn.danger:hover {
            background: #991b1b;
            border-color: #991b1b;
        }

        .modal-btn i {
            font-size: 12px;
        }

        .hidden {
            display: none !important;
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

            .upload-grid {
                grid-template-columns: 1fr 1.4fr;
            }

            .upload-grid .upload-submit {
                grid-column: 1 / -1;
            }

            .upload-grid .upload-submit .btn-primary {
                width: 100%;
            }

            .explorer-head,
            .file-row,
            .skeleton-row {
                grid-template-columns: 36px minmax(0, 2fr) minmax(0, 1fr) 100px 100px;
            }

            .explorer-head>.col-type,
            .file-row>.file-type-cell,
            .explorer-head>.col-date,
            .file-row>.file-date {
                display: none;
            }

            .skeleton-row> :nth-child(4),
            .skeleton-row> :nth-child(5) {
                display: none;
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

            .explorer-head,
            .file-row,
            .skeleton-row {
                grid-template-columns: 34px minmax(0, 2fr) minmax(0, 1fr) 100px;
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
                padding: 20px 16px;
                margin: 20px 0 40px;
            }

            .page-head h2 {
                font-size: 17px;
            }

            .upload-grid {
                grid-template-columns: 1fr;
            }

            .explorer-head {
                display: none;
            }

            .file-row {
                grid-template-columns: 34px minmax(0, 1fr) auto;
                grid-template-areas:
                    "icon name action"
                    "icon meta action";
                row-gap: 4px;
                column-gap: 10px;
                padding: 12px 14px;
            }

            .file-row .file-icon {
                grid-area: icon;
                align-self: start;
            }

            .file-name {
                grid-area: name;
            }

            .file-row .file-subject,
            .file-row .file-type-cell,
            .file-row .file-date {
                display: none;
            }

            .file-row .col-action {
                grid-area: action;
                align-self: center;
            }

            .file-name .fext {
                display: block;
            }

            .skeleton-row {
                grid-template-columns: 34px minmax(0, 1fr) 34px;
                padding: 12px 14px;
            }

            .skeleton-row> :nth-child(3),
            .skeleton-row> :nth-child(4),
            .skeleton-row> :nth-child(5) {
                display: none;
            }

            .modal-box {
                padding: 28px 20px 20px;
            }

            .modal-title {
                font-size: 18px;
            }

            .modal-message {
                font-size: 13px;
            }

            .btn-primary {
                padding: 6px 10px;
                font-size: 12px;
            }

            .btn-primary .btn-label {
                display: none;
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
            <a href="#" class="logo">
                <div class="logo-mark">
                    <img src="https://campuslink.villaruz-print-shop-and-general-merchandise.shop/Images/logo.png"
                        alt="Eguia National High School logo" onerror="this.style.display='none'">
                </div>
                <div class="logo-divider" aria-hidden="true"></div>
                <div class="logo-text">
                    <b>CampusLink</b>
                    <span class="school-name">Eguia National High School</span>
                </div>
            </a>

            <div class="nav-right">
                <span class="page-label">CLASS ACTIVITIES</span>

                <div class="nav-actions">
                    <button class="nav-toggle" id="navToggle" aria-label="Buksan ang menu" aria-expanded="false">
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
                        <h2><i class="fas fa-file-lines"></i> Assignments &amp; Submission</h2>
                        <span class="sub">
                            <?php echo htmlspecialchars($displayName); ?>
                            &middot; <?php echo htmlspecialchars($user['account_number'] ?? '—'); ?>
                        </span>
                    </div>

                    <div class="class-badges">
                        <?php if ($effectiveGrade !== ''): ?>
                            <span class="class-badge">
                                <i class="fas fa-graduation-cap"></i>
                                Grade <?php echo htmlspecialchars($effectiveGrade); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($effectiveSection !== ''): ?>
                            <span class="class-badge">
                                <i class="fas fa-users"></i>
                                <?php echo htmlspecialchars($effectiveSection); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ============================================================
                     SECTION 1: CLASS UPDATES (teacher files — downloadable)
                     ============================================================ -->
                <div class="section-head">
                    <h3><i class="fas fa-cloud-arrow-down"></i> Class Updates</h3>
                    <?php if (!empty($classUpdates)): ?>
                        <span class="count-pill"
                            style="font-size:12px;font-weight:600;padding:4px 12px;border-radius:999px;background:#f1f5f9;border:1px solid var(--line);color:var(--muted);">
                            <i class="fas fa-file" style="margin-right:5px;"></i>
                            <?php echo count($classUpdates); ?>
                            file<?php echo count($classUpdates) === 1 ? '' : 's'; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="breadcrumb">
                    <i class="fas fa-hard-drive"></i>
                    <span>Class Updates</span>
                    <?php if ($effectiveGrade !== ''): ?>
                        <span class="sep">/</span>
                        <span>Grade <?php echo htmlspecialchars($effectiveGrade); ?></span>
                    <?php endif; ?>
                    <?php if ($effectiveSection !== ''): ?>
                        <span class="sep">/</span>
                        <span><?php echo htmlspecialchars($effectiveSection); ?></span>
                    <?php endif; ?>
                    <span class="sep">/</span>
                    <span class="crumb-active">All Files</span>
                </div>

                <?php if ($updatesFetchErr !== null): ?>
                    <div class="empty-state">
                        <i class="fas fa-triangle-exclamation"></i>
                        <p>Unable to load class updates right now. Please try again later.</p>
                        <span class="debug"><?php echo htmlspecialchars($updatesFetchErr); ?></span>
                    </div>

                <?php elseif (empty($classUpdates)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>No class updates posted for your section yet.</p>
                        <?php if ($effectiveGrade === '' || $effectiveSection === ''): ?>
                            <span class="debug">Your grade level or section could not be resolved.</span>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <div class="explorer">
                        <div class="explorer-head">
                            <span></span>
                            <span>Name</span>
                            <span>Subject</span>
                            <span class="col-type">Type</span>
                            <span class="col-date">Modified</span>
                            <span class="col-action">Action</span>
                        </div>

                        <?php foreach ($classUpdates as $f): ?>
                            <?php
                            $type = (string) ($f['type'] ?? '');
                            $icon = fileIconClass($type);
                            $color = fileTypeColor($type);
                            $downloadUrl = buildDownloadUrl($f);
                            ?>
                            <div class="file-row" title="<?php echo htmlspecialchars($f['file_name'] ?? ''); ?>">
                                <div class="file-icon" style="color: <?php echo $color; ?>;">
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <div class="file-name">
                                    <span class="fname"><?php echo htmlspecialchars($f['file_name'] ?? 'Untitled'); ?></span>
                                    <span class="fext"><?php echo htmlspecialchars($type !== '' ? $type : 'file'); ?></span>
                                </div>
                                <div class="file-subject">
                                    <?php echo htmlspecialchars($f['subject'] ?? '—'); ?>
                                </div>
                                <div class="file-type-cell">
                                    <span class="file-type-pill"><?php echo htmlspecialchars($type !== '' ? $type : '—'); ?></span>
                                </div>
                                <div class="file-date">
                                    <?php echo htmlspecialchars($f['modified_at'] ?? '—'); ?>
                                </div>
                                <div class="col-action">
                                    <a href="<?php echo htmlspecialchars($downloadUrl); ?>" class="btn-primary btn-sm" download
                                        title="Download <?php echo htmlspecialchars($f['file_name'] ?? 'file'); ?>">
                                        <i class="fas fa-download"></i>
                                        <span class="btn-label">Download</span>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- ============================================================
                     SECTION 2: MY SUBMISSIONS (upload + list + delete via JS)
                     ============================================================ -->
                <div class="section-head">
                    <h3><i class="fas fa-cloud-arrow-up"></i> My Submissions</h3>
                </div>

                <!-- UPLOAD FORM -->
                <form class="upload-box" id="uploadForm" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <div class="upload-title">
                        <i class="fas fa-paper-plane"></i> Submit a finished activity
                    </div>

                    <div class="upload-grid">
                        <div class="upload-field">
                            <label for="subject">Subject</label>
                            <select name="subject" id="subject" required>
                                <option value="">Select subject…</option>
                                <?php foreach ($subjectChoices as $subj): ?>
                                    <option value="<?php echo htmlspecialchars($subj); ?>">
                                        <?php echo htmlspecialchars($subj); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="upload-field">
                            <label for="activity_file">File</label>
                            <input type="file" name="activity_file" id="activity_file" required
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.zip,.txt">
                        </div>

                        <div class="upload-field upload-submit">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-upload"></i>
                                <span class="btn-label">Submit</span>
                            </button>
                        </div>
                    </div>

                    <p class="upload-hint">
                        Accepted: PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX, images, ZIP, TXT. Max 10 MB.
                    </p>
                </form>

                <!-- TABLE CONTAINER (filled by JS) -->
                <div id="submissionsArea">
                    <!-- Skeleton shown by JS while fetching -->
                </div>

            </section>
        <?php endif; ?>
    </main>

    <!-- CONFIRM DELETE MODAL -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-box">
            <div class="modal-icon-wrap confirm">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div class="modal-title">Delete this submission?</div>
            <div class="modal-message">This action cannot be undone.</div>
            <div class="modal-actions">
                <button type="button" class="modal-btn" id="confirmCancel">Cancel</button>
                <button type="button" class="modal-btn danger" id="confirmOk">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>

    <!-- RESULT MODAL -->
    <div class="modal-overlay" id="resultModal">
        <div class="modal-box">
            <div class="modal-icon-wrap" id="resultIconWrap">
                <i class="fas fa-circle-check" id="resultIcon"></i>
            </div>
            <div class="modal-title" id="resultTitle">Success!</div>
            <div class="modal-message" id="resultMessage"></div>
            <div class="modal-actions">
                <button type="button" class="modal-btn" id="resultClose">Close</button>
            </div>
        </div>
    </div>

    <script>
        /* ============================================================
           CONFIG
           ============================================================ */
        const API_BASE = '../API_STUDENT';

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const CSRF_TOKEN = csrfMeta ? csrfMeta.getAttribute('content') : '';

        /* ============================================================
           ICON / COLOR HELPERS
           ============================================================ */
        function fileIconClass(type) {
            const t = String(type || '').toLowerCase().trim();
            const map = {
                pdf: 'fa-file-pdf',
                doc: 'fa-file-word', docx: 'fa-file-word', docs: 'fa-file-word',
                xls: 'fa-file-excel', xlsx: 'fa-file-excel', excel: 'fa-file-excel',
                ppt: 'fa-file-powerpoint', pptx: 'fa-file-powerpoint',
                jpg: 'fa-file-image', jpeg: 'fa-file-image', png: 'fa-file-image',
                gif: 'fa-file-image', webp: 'fa-file-image', image: 'fa-file-image',
                zip: 'fa-file-zipper', rar: 'fa-file-zipper', '7z': 'fa-file-zipper',
                mp4: 'fa-file-video', mov: 'fa-file-video', avi: 'fa-file-video', video: 'fa-file-video',
                mp3: 'fa-file-audio', wav: 'fa-file-audio', audio: 'fa-file-audio',
                txt: 'fa-file-lines',
                link: 'fa-link', url: 'fa-link'
            };
            return map[t] || 'fa-file';
        }

        function fileTypeColor(type) {
            const t = String(type || '').toLowerCase().trim();
            const map = {
                pdf: '#dc2626',
                doc: '#2563eb', docx: '#2563eb', docs: '#2563eb',
                xls: '#16a34a', xlsx: '#16a34a', excel: '#16a34a',
                ppt: '#ea580c', pptx: '#ea580c',
                jpg: '#7c3aed', jpeg: '#7c3aed', png: '#7c3aed', gif: '#7c3aed',
                webp: '#7c3aed', image: '#7c3aed',
                zip: '#a16207', rar: '#a16207', '7z': '#a16207',
                mp4: '#db2777', mov: '#db2777', avi: '#db2777', video: '#db2777',
                mp3: '#0891b2', wav: '#0891b2', audio: '#0891b2',
                txt: '#475569',
                link: '#0ea5e9', url: '#0ea5e9'
            };
            return map[t] || '#64748b';
        }

        function escapeHtml(s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        /* ============================================================
           NAV
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
           MODAL HELPERS
           ============================================================ */
        const confirmModal = document.getElementById('confirmModal');
        const resultModal = document.getElementById('resultModal');
        const confirmOk = document.getElementById('confirmOk');
        const confirmCancel = document.getElementById('confirmCancel');
        const resultIconWrap = document.getElementById('resultIconWrap');
        const resultIcon = document.getElementById('resultIcon');
        const resultTitle = document.getElementById('resultTitle');
        const resultMessage = document.getElementById('resultMessage');
        const resultClose = document.getElementById('resultClose');

        let pendingDeleteId = null;
        let pendingDeleteBtn = null;

        function openConfirm(id, btn) {
            pendingDeleteId = id;
            pendingDeleteBtn = btn;
            confirmModal.classList.add('open');
        }

        function closeConfirm() {
            confirmModal.classList.remove('open');
            pendingDeleteId = null;
            pendingDeleteBtn = null;
        }

        function showResult(type, title, message, autoCloseMs) {
            resultIconWrap.className = 'modal-icon-wrap ' + type;
            resultIcon.className = (type === 'success')
                ? 'fas fa-circle-check'
                : 'fas fa-circle-exclamation';
            resultTitle.textContent = title;
            resultMessage.textContent = message;
            resultModal.classList.add('open');

            if (typeof autoCloseMs === 'number' && autoCloseMs > 0) {
                setTimeout(closeResult, autoCloseMs);
            }
        }

        function closeResult() {
            resultModal.classList.remove('open');
        }

        resultClose?.addEventListener('click', closeResult);
        resultModal?.addEventListener('click', (e) => {
            if (e.target === resultModal) closeResult();
        });

        confirmCancel?.addEventListener('click', closeConfirm);
        confirmModal?.addEventListener('click', (e) => {
            if (e.target === confirmModal) closeConfirm();
        });

        /* ============================================================
           RENDER: skeleton + list
           ============================================================ */
        const submissionsArea = document.getElementById('submissionsArea');

        function renderSkeleton() {
            let html = '<div class="skeleton">';
            for (let i = 0; i < 3; i++) {
                html += `
                    <div class="skeleton-row">
                        <span class="sk icon"></span>
                        <span class="sk w-60"></span>
                        <span class="sk w-40"></span>
                        <span class="sk w-30"></span>
                        <span class="sk w-40"></span>
                        <span class="sk w-30"></span>
                    </div>
                `;
            }
            html += '</div>';
            submissionsArea.innerHTML = html;
        }

        function renderEmpty() {
            submissionsArea.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>You have not submitted any activities yet.</p>
                </div>
            `;
        }

        function renderError(msg) {
            submissionsArea.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-triangle-exclamation"></i>
                    <p>Unable to load your submissions right now. Please try again later.</p>
                    <span style="display:block;margin-top:10px;font-size:11.5px;color:#b91c1c;font-family:monospace;word-break:break-all;">${escapeHtml(msg || '')}</span>
                </div>
            `;
        }

        function renderTable(rows) {
            if (!rows || rows.length === 0) {
                renderEmpty();
                return;
            }

            let html = `
                <div class="explorer submissions-explorer">
                    <div class="explorer-head">
                        <span></span>
                        <span>Name</span>
                        <span>Subject</span>
                        <span class="col-type">Type</span>
                        <span class="col-date">Uploaded Time</span>
                        <span class="col-action">Action</span>
                    </div>
            `;

            rows.forEach((row) => {
                const type = String(row.type || '');
                const icon = fileIconClass(type);
                const color = fileTypeColor(type);
                const id = parseInt(row.id, 10) || 0;

                html += `
                    <div class="file-row" data-row-id="${id}">
                        <div class="file-icon" style="color:${color};">
                            <i class="fas ${icon}"></i>
                        </div>
                        <div class="file-name">
                            <span class="fname">${escapeHtml(row.file_name || 'Untitled')}</span>
                            <span class="fext">${escapeHtml(type || 'file')}</span>
                        </div>
                        <div class="file-subject">${escapeHtml(row.subject || '—')}</div>
                        <div class="file-type-cell">
                            <span class="file-type-pill">${escapeHtml(type || '—')}</span>
                        </div>
                        <div class="file-date">${escapeHtml(row.modified_at || '—')}</div>
                        <div class="col-action">
                            <button type="button"
                                    class="btn-delete js-delete"
                                    data-id="${id}"
                                    title="Delete submission">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            submissionsArea.innerHTML = html;

            submissionsArea.querySelectorAll('.js-delete').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-id');
                    if (!id) return;
                    openConfirm(id, btn);
                });
            });
        }

        /* ============================================================
           LOAD LIST (GET API_STUDENT/list-activities.php)
           ============================================================ */
        async function loadSubmissions() {
            renderSkeleton();
            try {
                const res = await fetch(API_BASE + '/list-activities.php', {
                    method: 'GET',
                    headers: { 'X-CSRF-Token': CSRF_TOKEN },
                    credentials: 'same-origin'
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Unable to load submissions.');
                }

                renderTable(Array.isArray(data.rows) ? data.rows : []);
            } catch (err) {
                renderError(err.message || 'Network error.');
            }
        }

        /* ============================================================
           SUBMIT (POST API_STUDENT/upload-activity.php)
           ============================================================ */
        const uploadForm = document.getElementById('uploadForm');

        uploadForm?.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(uploadForm);
            const submitBtn = uploadForm.querySelector('button[type="submit"]');
            const submitIcon = submitBtn ? submitBtn.querySelector('i') : null;
            const submitLabel = submitBtn ? submitBtn.querySelector('.btn-label') : null;
            const originalIconClass = submitIcon ? submitIcon.className : '';

            if (submitBtn) submitBtn.disabled = true;
            if (submitIcon) submitIcon.className = 'fas fa-spinner fa-spin';
            if (submitLabel) submitLabel.textContent = 'Submitting…';

            try {
                const res = await fetch(API_BASE + '/upload-activity.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Submission failed.');
                }

                uploadForm.reset();
                if (submitBtn) submitBtn.disabled = false;
                if (submitIcon) submitIcon.className = originalIconClass;
                if (submitLabel) submitLabel.textContent = 'Submit';

                showResult('success', 'Submitted!', data.message || 'Your activity has been submitted successfully.', 1800);

                await loadSubmissions();

            } catch (err) {
                showResult('error', 'Submission Failed', err.message || 'Something went wrong.', 0);
                if (submitBtn) submitBtn.disabled = false;
                if (submitIcon) submitIcon.className = originalIconClass;
                if (submitLabel) submitLabel.textContent = 'Submit';
            }
        });

        /* ============================================================
           DELETE (POST API_STUDENT/delete-activity.php)
           ============================================================ */
        confirmOk?.addEventListener('click', async () => {
            const id = pendingDeleteId;
            const btn = pendingDeleteBtn;

            if (!id || !btn) { closeConfirm(); return; }

            confirmOk.disabled = true;
            btn.disabled = true;
            const icon = btn.querySelector('i');
            const originalIconClass = icon ? icon.className : '';
            if (icon) icon.className = 'fas fa-spinner fa-spin';

            try {
                const res = await fetch(API_BASE + '/delete-activity.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ id: id })
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Delete failed.');
                }

                const row = btn.closest('.file-row');
                if (row) {
                    row.style.transition = 'opacity .25s, transform .25s';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(10px)';
                    setTimeout(() => row.remove(), 250);
                }

                closeConfirm();
                showResult('success', 'Deleted!', 'Your submission has been deleted successfully.', 1800);

            } catch (err) {
                if (icon) icon.className = originalIconClass;
                btn.disabled = false;
                closeConfirm();
                showResult('error', 'Delete Failed', err.message || 'Something went wrong.', 0);
            } finally {
                confirmOk.disabled = false;
            }
        });

        /* ============================================================
           INIT
           ============================================================ */
        document.addEventListener('DOMContentLoaded', loadSubmissions);
    </script>

    <?php navMarkEntry(); ?>
</body>

</html>