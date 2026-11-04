<?php
// config.php – Place this file OUTSIDE the public web root.

$host = 'localhost'; // This is always the hostname for Hostinger shared hosting
$dbname = 'u408983097_db_Bv9RVNRg';
$username = 'u408983097_usr_Bv9RVNRg';
$password = 'Nj_Verzosa@24';
 

// $host = 'localhost'; // This is always the hostname for Hostinger shared hosting
// $dbname = 'school_system';
// $username = 'root';
// $password = ''; // Enter the password you set for this database use


try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

