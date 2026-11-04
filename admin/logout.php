<?php
// admin/logout.php — Session-only logout
// Destroys the server session. Does NOT touch any cookies.

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

/* 1) Clear all session variables */
$_SESSION = [];

/* 2) Destroy the session on the server */
session_destroy();

/* 3) Redirect to the login page (cookie stays in the browser — just orphaned) */
header('Location: ../index.php');
exit;