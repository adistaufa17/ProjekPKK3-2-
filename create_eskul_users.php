<?php
require_once 'config/database.php';

// Daftar ekstrakurikuler yang akan ditambahkan
$ekstrakurikuler = [
    'pramuka' => ['nama' => 'Pramuka', 'email' => 'pramuka@sekolah.sch.id'],
    'pmr' => ['nama' => 'Palang Merah Remaja', 'email' => 'pmr@sekolah.sch.id'],
    'basket' => ['nama' => 'Basket', 'email' => 'basket@sekolah.sch.id'],
    'voli' => ['nama' => 'Voli', 'email' => 'voli@sekolah.sch.id'],
    'futsal' => ['nama' => 'Futsal', 'email' => 'futsal@sekolah.sch.id'],
    'kir' => ['nama' => 'Karya Ilmiah Remaja', 'email' => 'kir@sekolah.sch.id'],
    'musik' => ['nama' => 'Musik', 'email' => 'musik@sekolah.sch.id'],
    'dance' => ['nama' => 'Dance', 'email' => 'dance@sekolah.sch.id'],
    'theater' => ['nama' => 'Theater', 'email' => 'theater@sekolah.sch.id'],
    'english_club' => ['nama' => 'English Club', 'email' => 'english@sekolah.sch.id'],
    'robotik' => ['nama' => 'Robotik', 'email' => 'robotik@sekolah.sch.id'],
    'jurnalistik' => ['nama' => 'Jurnalistik', 'email' => 'jurnalistik@sekolah.sch.id'],
    'fotografi' => ['nama' => 'Fotografi', 'email' => 'foto@sekolah.sch.id'],
    'karate' => ['nama' => 'Karate', 'email' => 'karate@sekolah.sch.id'],
    'pencaksilat' => ['nama' => 'Pencak Silat', 'email' => 'silat@sekolah.sch.id'],
    'paskibra' => ['nama' => 'Paskibra', 'email' => 'paskibra@sekolah.sch.id'],
    'seni_rupa' => ['nama' => 'Seni Rupa', 'email' => 'senirupa@sekolah.sch.id'],
    'paduan_suara' => ['nama' => 'Paduan Suara', 'email' => 'choir@sekolah.sch.id'],
    'debat' => ['nama' => 'Debat', 'email' => 'debat@sekolah.sch.id'],
    'tari' => ['nama' => 'Tari Tradisional', 'email' => 'tari@sekolah.sch.id']
];

// Password default untuk semua user
$defaultPassword = 'ekskul123';
$hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

// Counter untuk tracking berhasil dibuat
$created = 0;
$errors = [];

// Tambahkan user ekskul ke database
foreach ($ekstrakurikuler as $username => $data) {
    try {
        // Cek apakah user sudah ada
        $checkStmt = $db->prepare("SELECT id FROM users WHERE username = :username");
        $checkStmt->execute(['username' => $username]);
        $existingUser = $checkStmt->fetch();
        
        if ($existingUser) {
            // Update email jika user sudah ada
            $updateStmt = $db->prepare("UPDATE users SET email = :email WHERE username = :username");
            $updateStmt->execute([
                'email' => $data['email'],
                'username' => $username
            ]);
            $errors[] = "User '$username' sudah ada, email diupdate";
            continue;
        }
        
        // Tambahkan user baru
        $stmt = $db->prepare("
            INSERT INTO users (username, password, nama_ekstrakurikuler, role, email) 
            VALUES (:username, :password, :nama, 'user', :email)
        ");
        
        $result = $stmt->execute([
            'username' => $username,
            'password' => $hashedPassword,
            'nama' => $data['nama'],
            'email' => $data['email']
        ]);
        
        if ($result) {
            $created++;
        }
    } catch (PDOException $e) {
        $errors[] = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat User Ekstrakurikuler</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Pembuatan User Ekstrakurikuler</h1>
    
    <?php if ($created > 0): ?>
        <div class="success">Berhasil membuat <?= $created ?> user ekstrakurikuler baru</div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="error">
            <p>Beberapa info:</p>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <p>User ekstrakurikuler telah dibuat dengan password default: <strong><?= htmlspecialchars($defaultPassword) ?></strong></p>
    
    <h2>List User Ekstrakurikuler:</h2>
    <table>
        <tr>
            <th>Username</th>
            <th>Nama Ekstrakurikuler</th>
            <th>Email</th>
        </tr>
        <?php foreach ($ekstrakurikuler as $username => $data): ?>
        <tr>
            <td><?= htmlspecialchars($username) ?></td>
            <td><?= htmlspecialchars($data['nama']) ?></td>
            <td><?= htmlspecialchars($data['email']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <p style="margin-top: 20px;"><a href="index.php">Kembali ke Halaman Utama</a></p>
</body>
</html>
