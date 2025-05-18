<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$hari = isset($_GET['hari']) ? $_GET['hari'] : 'senin';
$hari = strtolower($hari);

// Validasi hari
$valid_days = ['senin', 'selasa', 'rabu', 'kamis', 'jumat'];
if (!in_array($hari, $valid_days)) {
    $hari = 'senin';
}

// Ambil info ruangan
$stmt = $db->prepare("SELECT * FROM rooms WHERE id = :id");
$stmt->execute(['id' => $room_id]);
$room = $stmt->fetch();

if (!$room) {
    die("Ruangan tidak ditemukan.");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $start_time = isset($_POST['start_time']) ? $_POST['start_time'] : null;
    $end_time = isset($_POST['end_time']) ? $_POST['end_time'] : null;

    // Validasi form
    if (strtolower($room['nama_ruang']) == 'workshop') {
        if (!$start_time || !$end_time) {
            $error = "Jam mulai dan jam selesai wajib diisi untuk Workshop.";
        } elseif ($start_time >= $end_time) {
            $error = "Jam selesai harus lebih besar dari jam mulai.";
        }
    }

    if (!$error) {
        // Cek bentrok booking di database
        if (strtolower($room['nama_ruang']) == 'workshop') {
            // Cek bentrok berdasarkan jam mulai dan selesai
            $stmtCheck = $db->prepare("SELECT * FROM bookings WHERE room_id = :room_id AND hari = :hari AND status IN ('pending', 'approved') 
                                       AND ((start_time < :end_time AND end_time > :start_time))");
            $stmtCheck->execute([
                'room_id' => $room_id,
                'hari' => $hari,
                'start_time' => $start_time,
                'end_time' => $end_time
            ]);
            $conflict = $stmtCheck->fetch();
        } else {
            // Cek booking untuk ruangan selain Workshop tanpa jam
            $stmtCheck = $db->prepare("SELECT * FROM bookings WHERE room_id = :room_id AND hari = :hari AND status IN ('pending', 'approved')");
            $stmtCheck->execute(['room_id' => $room_id, 'hari' => $hari]);
            $conflict = $stmtCheck->fetch();
        }

        if ($conflict) {
            $error = "Ruangan sudah dipesan pada waktu yang dipilih.";
        } else {
            // Simpan booking ke database dengan status pending
            $stmtInsert = $db->prepare("INSERT INTO bookings (room_id, user_id, hari, status, start_time, end_time) VALUES 
                                      (:room_id, :user_id, :hari, 'pending', :start_time, :end_time)");

            $stmtInsert->execute([
                'room_id' => $room_id,
                'user_id' => $_SESSION['user_id'],
                'hari' => $hari,
                'start_time' => $start_time ?? null,
                'end_time' => $end_time ?? null,
            ]);

            $success = "Booking berhasil dibuat. Tunggu konfirmasi admin.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Booking Ruang - <?= htmlspecialchars($room['nama_ruang']) ?></title>
    <link rel="stylesheet" href="assets/css/style_booking_ruang.css">
</head>
<body>
    <h2>Booking Ruang: <?= htmlspecialchars($room['nama_ruang']) ?></h2>
    <p>Hari: <?= ucfirst($hari) ?></p>

    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color: green;"><?= htmlspecialchars($success) ?></p>
        <p><a href="booking_ruang.php?hari=<?= $hari ?>">Kembali ke daftar ruang</a></p>
        <?php exit; ?>
    <?php endif; ?>

    <form method="POST" action="">
        <?php if (strtolower($room['nama_ruang']) == 'workshop'): ?>
            <label for="start_time">Jam Mulai:</label><br>
            <input type="time" id="start_time" name="start_time" required><br><br>

            <label for="end_time">Jam Selesai:</label><br>
            <input type="time" id="end_time" name="end_time" required><br><br>
        <?php else: ?>
            <p>Tidak perlu mengisi jam untuk ruangan ini.</p>
        <?php endif; ?>

        <button type="submit">Pesan Ruang</button>
    </form>

    <p><a href="booking_ruang.php?hari=<?= $hari ?>">Batal dan kembali</a></p>

    <script>
        // Auto-update notification badge every 30 seconds
        setInterval(function() {
            fetch('get_notification_count.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.notification-badge');
                if (data.count > 0) {
                    if (badge) {
                        badge.textContent = data.count;
                    } else {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'notification-badge';
                        newBadge.textContent = data.count;
                        document.querySelector('.menu-item a[href="notifications_page.php"] .menu-text').appendChild(newBadge);
                    }
                } else if (badge) {
                    badge.remove();
                }
            });
        }, 30000);
    </script>
</body>
</html>