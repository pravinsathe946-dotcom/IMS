<?php
// config.php

define('DB_HOST', 'sql306.infinityfree.com');
define('DB_USER', 'if0_42355711'); // Change to your DB username
define('DB_PASS', 'Propravin123');     // Change to your DB password
define('DB_NAME', 'if0_42355711_inventory');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    // Set error mode to exceptions for better debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Fetch associative arrays by default
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>