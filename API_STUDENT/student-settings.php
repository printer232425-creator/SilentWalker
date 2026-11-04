<?php
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

header('Content-Type: application/json');

// 1. Check if logged in
if (empty($_SESSION['account_number'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// 2. Check CSRF Token
$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$accountNumber = $_POST['account_number'] ?? '';

if ($accountNumber !== $_SESSION['account_number']) {
    echo json_encode(['success' => false, 'error' => 'Account mismatch']);
    exit;
}

// 3. Update the database
try {
    $stmt = $pdo->prepare("
        UPDATE students 
        SET student_status = 'T-pending' 
        WHERE account_number = ?
    ");
    $stmt->execute([$accountNumber]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Update status failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}