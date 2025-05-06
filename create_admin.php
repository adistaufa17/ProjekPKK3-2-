<?php
require_once 'config/database.php';

// Cek apakah user admin sudah ada
$stmt = $db->prepare("SELECT * FROM users WHERE username = 'admin'");
$stmt->execute();
$admin = $stmt->fetch();

if ($admin) {
    // Update password admin yang sudah ada
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $updateStmt = $db->prepare("UPDATE users SET password = :password WHERE username = 'admin'");
    $updateStmt->execute(['password' => $hashedPassword]);
    echo "Admin password updated successfully!";
} else {
    // Buat user admin baru
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $insertStmt = $db->prepare("
        INSERT INTO users (username, password, nama_ekstrakurikuler, role) 
        VALUES (:username, :password, :nama, :role)
    ");
    
    $insertStmt->execute([
        'username' => 'admin',
        'password' => $hashedPassword,
        'nama' => 'Administrator',
        'role' => 'admin'
    ]);
    
    echo "Admin user created successfully!";
}
?>
