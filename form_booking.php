<?php
session_start();
require_once 'config/database.php';
require_once 'notifications.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if parameters exist
if (!isset($_GET['room_id']) || !isset($_GET['hari'])) {
    header("Location: booking_hari.php");
    exit();
}

$room_id = $_GET['room_id'];
$hari = $_GET['hari'];

// Validate hari parameter
$valid_days = ['senin', 'selasa', 'rabu', 'kamis', 'jumat'];
if (!in_array($hari, $valid_days)) {
    header("Location: booking_hari.php");
    exit();
}

// Get room details
$stmt = $db->prepare("SELECT r.*, b.nama_gedung FROM rooms r JOIN buildings b ON r.building_id = b.id WHERE r.id = :room_id");
$stmt->execute(['room_id' => $room_id]);
$room = $stmt->fetch();

if (!$room) {
    header("Location: booking_hari.php");
    exit();
}

// Check if room is already booked
$stmt = $db->prepare("
    SELECT * FROM bookings 
    WHERE room_id = :room_id AND hari = :hari AND status IN ('pending', 'approved')
");
$stmt->execute(['room_id' => $room_id, 'hari' => $hari]);
$booking = $stmt->fetch();

if ($booking) {
    $_SESSION['error'] = "Ruangan ini sudah dipesan untuk hari " . ucfirst($hari) . ".";
    header("Location: booking_ruang.php?hari=" . $hari);
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_time = null;
    $end_time = null;

    // Validasi waktu hanya jika ruang adalah workshop
    if (strtolower($room['nama_ruang']) === 'workshop') {
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        // Validasi jam: jam selesai harus setelah jam mulai
        if (strtotime($end_time) <= strtotime($start_time)) {
            $error = "Jam selesai harus lebih besar dari jam mulai.";
        }
    }

    if (!isset($error)) {
        $stmt = $db->prepare("
            INSERT INTO bookings (user_id, room_id, hari, status, start_time, end_time)
            VALUES (:user_id, :room_id, :hari, 'pending', :start_time, :end_time)
        ");

        $result = $stmt->execute([
            'user_id' => $_SESSION['user_id'],
            'room_id' => $room_id,
            'hari' => $hari,
            'start_time' => $start_time,
            'end_time' => $end_time
        ]);

        if ($result) {
            $_SESSION['success'] = "Booking berhasil! Menunggu konfirmasi admin.";
            header("Location: beranda.php");
            exit();
        } else {
            $error = "Terjadi kesalahan saat menyimpan data. Silakan coba lagi.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Booking - Sistem Pemesanan Ruang</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .form-container {
            max-width: 600px;
        }
        
        .booking-info {
            background-color: #f0f8ff;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: bold;
            width: 150px;
        }
        
        .back-btn {
            background-color: #ccc;
            color: #333;
            margin-right: 10px;
        }

        @media (max-width: 768px) {
            .form-container {
                width: 90%;
                max-width: 100%;
                padding: 15px;
            }
            
            .info-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .info-label {
                width: 100%;
                margin-bottom: 5px;
            }
            
            button {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .button-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Konfirmasi Booking Ruang</h2>

    <?php if (isset($error)): ?>
        <div style="color: red; margin-bottom: 15px; text-align: center;"><?= $error ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="booking-info">
            <div class="info-item">
                <span class="info-label">Ekstrakurikuler:</span>
                <span><?= htmlspecialchars($_SESSION['nama_ekstrakurikuler']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Gedung:</span>
                <span><?= htmlspecialchars($room['nama_gedung']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Ruang:</span>
                <span><?= htmlspecialchars($room['nama_ruang']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Hari:</span>
                <span><?= ucfirst($hari) ?></span>
            </div>
        </div>

        <?php if (strtolower(trim($room['nama_ruang'])) === 'workshop'): ?>
            <div class="info-item">
                <label class="info-label" for="start_time">Jam Mulai:</label>
                <input type="time" name="start_time" id="start_time" required>
            </div>
            <div class="info-item">
                <label class="info-label" for="end_time">Jam Selesai:</label>
                <input type="time" name="end_time" id="end_time" required>
            </div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between;">
            <button type="button" class="back-btn" onclick="history.back()">Kembali</button>
            <button type="submit">Konfirmasi Booking</button>
        </div>
    </form>
</div>


        </div>
    </div>

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