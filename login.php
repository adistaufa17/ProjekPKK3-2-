<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: beranda.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi!";
    } else {
        // Debug: Cek apakah user ada di database
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $db->prepare($sql);
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        // Debug: Cek hasil query
        if ($user) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['nama_ekstrakurikuler'] = $user['nama_ekstrakurikuler'];
                
                header("Location: beranda.php");
                exit();
            } else {
                // Coba dengan password plain text untuk admin123 (hanya untuk debugging)
                if ($username === 'admin' && $password === 'admin123') {
                    // Update password di database dengan hash yang baru
                    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
                    $updateStmt = $db->prepare("UPDATE users SET password = :password WHERE username = 'admin'");
                    $updateStmt->execute(['password' => $hashedPassword]);
                    
                    // Set session dan redirect
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['nama_ekstrakurikuler'] = $user['nama_ekstrakurikuler'];
                    
                    header("Location: beranda.php");
                    exit();
                }
                $error = "Username atau password salah!";
            }
        } else {
            $error = "Username atau password salah!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Pemesanan Ruang</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Login Sistem Pemesanan Ruang</h2>
            
            <?php if (isset($_SESSION['logout_success'])): ?>
                <div style="color: green; margin-bottom: 15px; text-align: center;">
                    Anda telah berhasil logout. Silakan login kembali untuk melanjutkan.
                </div>
                <?php unset($_SESSION['logout_success']); ?>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div style="color: red; margin-bottom: 15px; text-align: center;"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="post" action="">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
        </div>
    </div>
</body>
</html>