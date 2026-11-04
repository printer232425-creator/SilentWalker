<?php
// API/admin_actions.php — POST backend for the admin dashboard
// Handles:
//   action=toggle_enrollment
//   action=assign_subject
//   action=remove_assignment
//   action=update_enrollment_status
require_once __DIR__ . '/../DB_Conn/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ------------------------------------------------------------
   AUTH — must be admin
   ------------------------------------------------------------ */
if (empty($_SESSION['account_number'])) {
    header('Location: ../index.php');
    exit;
}

$accountNumber = trim((string) $_SESSION['account_number']);
if (!preg_match('/^26_[A-Z]{1,8}_\d{1,7}$/', $accountNumber)) {
    header('Location: ../index.php');
    exit;
}

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
} catch (PDOException $e) {
    error_log("actions: admin fetch failed: " . $e->getMessage());
    $adminUser = null;
}

if (!$adminUser) {
    header('Location: ../index.php');
    exit;
}

/* ------------------------------------------------------------
   HELPERS
   ------------------------------------------------------------ */
function currentAcademicYear(): string
{
    $y = (int) date('Y');
    return ((int) date('n') < 6) ? (($y - 1) . '-' . $y) : ($y . '-' . ($y + 1));
}

function flash(string $key, string $msg): void
{
    $_SESSION['flash'][$key] = $msg;
}

/**
 * Redirect back to the admin dashboard.
 * From API/, the dashboard lives at ../admin/home.php
 */
function back(): void
{
    header('Location: ../admin/home.php');
    exit;
}

$csrfToken   = $_SESSION['csrf_token'] ?? '';
$postAction  = $_POST['action'] ?? '';
$currentYear = currentAcademicYear();

/* ------------------------------------------------------------
   CSRF check for every action
   ------------------------------------------------------------ */
$clientToken = $_POST['csrf_token'] ?? '';
if (!is_string($clientToken) || $csrfToken === '' || !hash_equals($csrfToken, $clientToken)) {
    flash('admin_error', 'Invalid security token.');
    back();
}

/* ------------------------------------------------------------
   1) TOGGLE ENROLLMENT WINDOW
   ------------------------------------------------------------ */
if ($postAction === 'toggle_enrollment') {
    $newStatus = ((int) ($_POST['new_status'] ?? 0) === 1) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("UPDATE students SET academic_status = ?");
        $stmt->execute([$newStatus]);
        // No flash on success — dashboard will simply reload with new state.
    } catch (PDOException $e) {
        error_log("toggle_enrollment failed: " . $e->getMessage());
        flash('admin_error', 'Failed to update enrollment status.');
    }
    back();
}

/* ------------------------------------------------------------
   2) ASSIGN SUBJECT TO TEACHER
   ------------------------------------------------------------ */
if ($postAction === 'assign_subject') {
    $teacherAcc = trim((string) ($_POST['teacher_account_number'] ?? ''));

    // Read the three checkbox arrays
    $gradesRaw   = $_POST['grade_levels'] ?? [];
    $sectionsRaw = $_POST['sections']     ?? [];
    $subjectsRaw = $_POST['subject_ids']  ?? [];

    if (!is_array($gradesRaw))   { $gradesRaw = []; }
    if (!is_array($sectionsRaw)) { $sectionsRaw = []; }
    if (!is_array($subjectsRaw)) { $subjectsRaw = []; }

    // Normalise
    $gradeLevels = array_values(array_unique(array_filter(array_map('trim', $gradesRaw), 'strlen')));
    $sections    = array_values(array_unique(array_filter(array_map('trim', $sectionsRaw), 'strlen')));
    $subjectIds  = array_values(array_unique(array_filter(array_map('intval', $subjectsRaw), fn($id) => $id > 0)));

    $errors = [];

    if ($teacherAcc === '') {
        $errors[] = 'Please choose a teacher.';
    } elseif (!preg_match('/^26_[A-Z]{1,8}_T\d{4}$/', $teacherAcc)) {
        $errors[] = 'Invalid teacher account number format.';
    }

    if (empty($gradeLevels)) {
        $errors[] = 'Tick at least one grade level.';
    }

    if (empty($sections)) {
        $errors[] = 'Tick at least one section.';
    }

    if (empty($subjectIds)) {
        $errors[] = 'Tick at least one subject.';
    }

    foreach ($gradeLevels as $g) {
        if (mb_strlen($g) > 20) {
            $errors[] = "Grade level '{$g}' is too long.";
            break;
        }
    }
    foreach ($sections as $sec) {
        if (mb_strlen($sec) > 50) {
            $errors[] = "Section '{$sec}' is too long.";
            break;
        }
    }

    if ($errors) {
        flash('admin_error', implode(' ', $errors));
        back();
    }

    // Fetch the teacher row (we need surname)
    $teacher = null;
    try {
        $stmt = $pdo->prepare("
            SELECT account_number, first_name, middle_name, surname
            FROM teachers
            WHERE account_number = ?
            LIMIT 1
        ");
        $stmt->execute([$teacherAcc]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log("assign_subject teacher fetch failed: " . $e->getMessage());
    }

    if (!$teacher) {
        flash('admin_error', 'That teacher account does not exist.');
        back();
    }

    $teacherSurname = (string) ($teacher['surname'] ?? '');

    // Fetch subject names for all selected IDs
    $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
    $subjectMap = [];
    try {
        $stmt = $pdo->prepare("SELECT id, subject_name FROM subjects WHERE id IN ($placeholders)");
        $stmt->execute($subjectIds);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $subjectMap[(int) $row['id']] = (string) $row['subject_name'];
        }
    } catch (PDOException $e) {
        error_log("assign_subject subject fetch failed: " . $e->getMessage());
    }

    $missing = array_diff($subjectIds, array_keys($subjectMap));
    if (!empty($missing)) {
        flash('admin_error', 'One or more selected subjects no longer exist.');
        back();
    }

    // Insert one row per (grade × section × subject) — account_number stays NULL
    try {
        $pdo->beginTransaction();

        $insert = $pdo->prepare("
            INSERT INTO student_subjects
                (grade_level, section, adviser, surname, subject)
            VALUES (?, ?, ?, ?, ?)
        ");

        $rowCount = 0;

        foreach ($gradeLevels as $grade) {
            foreach ($sections as $sec) {
                $sectionLabel = 'Section ' . $sec;

                foreach ($subjectIds as $sid) {
                    $insert->execute([
                        $grade,
                        $sectionLabel,
                        $teacherAcc,
                        $teacherSurname,
                        $subjectMap[$sid],
                    ]);
                    $rowCount++;
                }
            }
        }

        $pdo->commit();

        flash('admin_success', $rowCount . ' assignment row(s) inserted successfully.');
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($e->getCode() === '23000') {
            flash('admin_error', 'One or more assignments already exist. No rows were inserted.');
        } else {
            error_log("assign_subject insert failed: " . $e->getMessage());
            flash('admin_error', 'Database error: ' . $e->getMessage());
        }
    }
    back();
}

/* ------------------------------------------------------------
   3) REMOVE ASSIGNMENT  (delete from student_subjects)  ← DELETE BACKEND
   ------------------------------------------------------------ */
if ($postAction === 'remove_assignment') {
    $assignmentId = (int) ($_POST['assignment_id'] ?? 0);

    if ($assignmentId < 1) {
        flash('admin_error', 'Invalid assignment.');
        back();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM student_subjects WHERE id = ?");
        $stmt->execute([$assignmentId]);

        if ($stmt->rowCount() > 0) {
            flash('admin_success', 'Assignment #' . $assignmentId . ' removed.');
        } else {
            flash('admin_error', 'Assignment not found (already removed?).');
        }
    } catch (PDOException $e) {
        error_log("remove_assignment failed: " . $e->getMessage());
        flash('admin_error', 'Failed to remove assignment.');
    }
    back();
}

/* ------------------------------------------------------------
   4) UPDATE ENROLLMENT STATUS
   ------------------------------------------------------------ */
if ($postAction === 'update_enrollment_status') {
    $recordId  = (int) ($_POST['record_id'] ?? 0);
    $newStatus = trim((string) ($_POST['status'] ?? ''));

    if ($recordId < 1 || !in_array($newStatus, ['Enrolled', 'Declined', 'Pending'], true)) {
        flash('admin_error', 'Invalid record or status.');
        back();
    }

    try {
        $stmt = $pdo->prepare("UPDATE student_records SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $recordId]);
        flash('admin_success', 'Enrollment status updated to ' . $newStatus . '.');
    } catch (PDOException $e) {
        error_log("update_enrollment_status failed: " . $e->getMessage());
        flash('admin_error', 'Failed to update enrollment status.');
    }
    back();
}

/* ------------------------------------------------------------
   Unknown action
   ------------------------------------------------------------ */
flash('admin_error', 'Unknown action.');
back();