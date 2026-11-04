<?php
// admin/teachers.php — Teachers list & Assignment
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
   FETCH TEACHERS
   ------------------------------------------------------------ */
$allTeachers = [];
try {
    $allTeachers = $pdo->query("
        SELECT
            id, account_number, first_name, middle_name, surname,
            age, sex, birth_date, birth_place, address,
            contact_number, position, department, school_name,
            registered_date
        FROM teachers
        ORDER BY surname, first_name
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("teachers fetch failed: " . $e->getMessage());
}

$totalTeacherCount = count($allTeachers);

$adminDisplayName = trim(
    ($adminUser['first_name'] ?? '') . ' ' .
    ($adminUser['middle_name'] ?? '') . ' ' .
    ($adminUser['surname'] ?? '')
);
?>
<!DOCTYPE html>
<html lang="fil">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>" />
    <title>Teachers — Eguia National High School</title>

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

        .name-block {
            display: flex;
            flex-direction: column;
            line-height: 1.25;
        }

        .name-block .contact-sub {
            font-size: 11.5px;
            color: var(--muted);
            font-weight: 500;
            margin-top: 2px;
        }

        /* ── ASSIGN BUTTON ── */
        .btn-assign {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--primary-2), var(--primary-dark));
            color: #fff;
            font-size: 12.5px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 6px 14px -8px rgba(37, 99, 235, .8);
            transition: transform .15s, box-shadow .15s;
            white-space: nowrap;
            font-family: inherit;
            border: none;
            cursor: pointer;
        }

        .btn-assign:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px -8px rgba(37, 99, 235, .9);
        }

        .btn-assign:active {
            transform: scale(.97);
        }

        .btn-assign i {
            font-size: 12px;
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
                <span class="page-label">All Teachers</span>

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
        <!-- ALL TEACHERS LIST -->
        <section class="section">
            <div class="section-head">
                <div>
                    <h2>All Teachers</h2>
                    <p><?php echo $totalTeacherCount; ?> teacher<?php echo $totalTeacherCount === 1 ? '' : 's'; ?>
                        registered in the system.</p>
                </div>
            </div>

            <?php if (empty($allTeachers)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-tie"></i>
                    <p>No teachers registered yet.</p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Account #</th>
                                <th>Full Name</th>
                                <th>Sex</th>
                                <th>Age</th>
                                <th>Position</th>
                                <th>Department</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allTeachers as $t): ?>
                                <?php
                                $tFullName = trim(
                                    ($t['first_name'] ?? '') . ' ' .
                                    ($t['middle_name'] ?? '') . ' ' .
                                    ($t['surname'] ?? '')
                                );
                                $tContact = trim((string) ($t['contact_number'] ?? ''));
                                $tAccount = (string) ($t['account_number'] ?? '');
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($tAccount !== '' ? $tAccount : '—'); ?></td>
                                    <td>
                                        <div class="name-block">
                                            <span><?php echo htmlspecialchars($tFullName); ?></span>
                                            <?php if ($tContact !== ''): ?>
                                                <span class="contact-sub"><?php echo htmlspecialchars($tContact); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($t['sex'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($t['age'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($t['position'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($t['department'] ?? '—'); ?></td>
                                    <td style="text-align:right;">
                                        <?php if ($tAccount !== ''): ?>
                                            <a href="assign-teachers.php?teacher=<?php echo urlencode($tAccount); ?>"
                                                class="btn-assign"
                                                title="Assign subjects for <?php echo htmlspecialchars($tFullName); ?>">
                                                <i class="fas fa-book"></i> Assign Subjects
                                            </a>
                                        <?php else: ?>
                                            <span style="color:#94a3b8; font-size:12px;">No account #</span>
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
           STATUS MODAL (flash messages)
           ============================================================ */
        const statusModal = document.getElementById('statusModal');

        if (statusModal && statusModal.classList.contains('show')) {
            setTimeout(() => statusModal.classList.remove('show'), 2000);
        }
    </script>
</body>

</html>