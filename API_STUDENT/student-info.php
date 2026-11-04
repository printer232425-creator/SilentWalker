<?php
// API/edit-info.php
// Inline-edit backend for student profile fields.
// POST-only. CSRF required. Student session required.
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

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

require_once __DIR__ . '/../DB_Conn/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

/* CSRF */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$clientToken = $_POST['csrf_token'] ?? '';
if (!is_string($clientToken) || !hash_equals($_SESSION['csrf_token'], $clientToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Refresh and try again.']);
    exit;
}

/* Auth — student */
$accountNumber = $_SESSION['account_number'] ?? null;
if (!is_string($accountNumber) || $accountNumber === '') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

/* Whitelisted editable fields — must match the data-field="" attributes in the page */
$allowed = [
    'first_name',
    'middle_name',
    'surname',
    'age',
    'sex',
    'birth_date',
    'birth_place',
    'contact_number',
    'address',
    'guardian',
    'guardian_contact_number',
];

$field = trim((string) ($_POST['field'] ?? ''));
$value = trim((string) ($_POST['value'] ?? ''));

if (!in_array($field, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Field not editable.']);
    exit;
}

/* Per-field validation */
if ($field === 'age') {
    if ($value !== '' && (!ctype_digit($value) || (int) $value < 5 || (int) $value > 100)) {
        echo json_encode(['success' => false, 'message' => 'Age must be a number between 5 and 100.']);
        exit;
    }
}

if ($field === 'sex' && $value !== '') {
    $norm = strtolower($value);
    if (!in_array($norm, ['male', 'female', 'm', 'f'], true)) {
        echo json_encode(['success' => false, 'message' => 'Sex must be Male or Female.']);
        exit;
    }
    $value = ($norm[0] === 'm') ? 'Male' : 'Female';
}

if (($field === 'contact_number' || $field === 'guardian_contact_number') && $value !== '') {
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $value)) {
        echo json_encode(['success' => false, 'message' => 'Invalid contact number format.']);
        exit;
    }
}

/* Save */
try {
    $stmt = $pdo->prepare("
        UPDATE students
        SET `{$field}` = ?
        WHERE account_number = ?
          AND user_type = 'student'
        LIMIT 1
    ");
    $stmt->execute([$value, $accountNumber]);

    if ($stmt->rowCount() === 0) {
        /* Value was identical or student row missing */
        try {
            $check = $pdo->prepare("SELECT 1 FROM students WHERE account_number = ? AND user_type = 'student' LIMIT 1");
            $check->execute([$accountNumber]);
            if (!$check->fetchColumn()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Student not found.']);
                exit;
            }
        } catch (PDOException $e) {
            error_log("edit-info row check failed: " . $e->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Saved.',
        'field'   => $field,
        'value'   => $value,
    ]);
} catch (PDOException $e) {
    error_log("edit-info update failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Try again later.']);
}