<?php
require_once 'config.php';

// Check if an admin already exists to prevent duplication
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
if ($stmt->fetchColumn() == 0) {
    $username = 'admin';
    $email = 'admin@example.com';
    $password = password_hash('admin123', PASSWORD_BCRYPT); // Secure hashing
    $role = 'admin';

    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $email, $password, $role]);
    echo "Admin user created successfully! Username: admin | Password: admin123";
} else {
    echo "Users already exist. Delete this file for security.";
}
?>