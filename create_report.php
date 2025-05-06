<?php
session_start();
require_once 'config/database.php';
require_once 'send_email.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['booking_id'])) {
    header("Location: my_bookings.php");
    exit();
}

$booking_id = $_GET['booking_id'];

// Check if booking exists and belongs to user
$stmt = $db->prepare("
    SELECT b.*, r.nama_ruang, bg.nama_gedung
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN buildings bg ON r.building_id = bg.id
    WHERE b.id = :booking_id AND b.user_id = :user_id AND b.status = 'approved'
");
$stmt->execute([
    'booking_id' => $booking_id,
    'user_id' => $_SESSION['user_id']
]);
$booking = $stmt->fetch();

if (!$booking) {
    $_SESSION['error'] = "Booking tidak ditemukan atau Anda tidak memiliki akses.";
    header("Location: my_bookings.php");
    exit();
}

// Check if report already exists
$stmt = $db->prepare("SELECT id FROM room_reports WHERE booking_id = :booking_id");
$stmt->execute(['booking_id' => $booking_id]);
if ($stmt->fetch()) {
    $_SESSION['error'] = "Laporan untuk booking ini sudah ada.";
    header("Location: my_bookings.php");
    exit();
}

// Process form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $condition_report = trim($_POST['condition_report']);
    
    if (empty($condition_report)) {
        $error = "Laporan kondisi ruang harus diisi.";
    } else {
        $photo_path = null;
        
        // Handle photo upload
        if (isset($_FILES['room_photo']) && $_FILES['room_photo']['error'] === UPLOAD_ERR_OK) {
            $uploads_dir = 'uploads/room_photos';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploads_dir)) {
                mkdir($uploads_dir, 0777, true);
            }
            
            // Get file extension and generate unique filename
            $tmp_name = $_FILES['room_photo']['tmp_name'];
            $name = basename($_FILES['room_photo']['name']);
            $file_ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $allowed_exts = array('jpg', 'jpeg', 'png');
            
            if (in_array($file_ext, $allowed_exts)) {
                $new_filename = 'room_' . $booking_id . '_' . date('YmdHis') . '.' . $file_ext;
                $photo_path = $uploads_dir . '/' . $new_filename;
                
                if (move_uploaded_file($tmp_name, $photo_path)) {
                    // File uploaded successfully
                } else {
                    $error = "Gagal mengupload foto. Silakan coba lagi.";
                }
            } else {
                $error = "Format file tidak didukung. Harap upload file JPG, JPEG, atau PNG.";
            }
        }
        
        if (empty($error)) {
            // Insert report
            $stmt = $db->prepare("
                INSERT INTO room_reports (booking_id, user_id, condition_report, photo_path)
                VALUES (:booking_id, :user_id, :condition_report, :photo_path)
            ");
            
            $result = $stmt->execute([
                'booking_id' => $booking_id,
                'user_id' => $_SESSION['user_id'],
                'condition_report' => $condition_report,
                'photo_path' => $photo_path
            ]);
            
            if ($result) {
                // Send email notification to admin
                sendRoomReportNotification($db, $db->lastInsertId());
                
                $_SESSION['success'] = "Laporan kondisi ruang berhasil dikirim. Terima kasih!";
                header("Location: my_bookings.php");
                exit();
            } else {
                $error = "Gagal mengirim laporan. Silakan coba lagi.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kondisi Ruang - Sistem Pemesanan Ruang</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a365d, #2c5282);
        }
        
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
        
        textarea {
            width: 100%;
            min-height: 150px;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: Arial, sans-serif;
            font-size: 16px;
        }
        
        .file-input {
            margin-bottom: 20px;
        }
        
        .file-input label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .button-container {
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Laporan Kondisi Ruang</h2>
            
            <?php if (!empty($error)): ?>
                <div style="color: red; margin-bottom: 15px; text-align: center;"><?= $error ?></div>
            <?php endif; ?>
            
            <div class="booking-info">
                <div class="info-item">
                    <span class="info-label">Ekstrakurikuler:</span>
                    <span><?= htmlspecialchars($_SESSION['nama_ekstrakurikuler']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Gedung:</span>
                    <span><?= htmlspecialchars($booking['nama_gedung']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Ruang:</span>
                    <span><?= htmlspecialchars($booking['nama_ruang']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Hari:</span>
                    <span><?= ucfirst($booking['hari']) ?></span>
                </div>
            </div>
            
            <form method="post" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="condition_report">Laporan Kondisi Ruang:</label>
                    <textarea id="condition_report" name="condition_report" required placeholder="Deskripsikan kondisi ruangan setelah digunakan, termasuk jika ada kerusakan atau masalah yang ditemui..."><?= isset($_POST['condition_report']) ? htmlspecialchars($_POST['condition_report']) : '' ?></textarea>
                </div>
                
                <div class="file-input">
                    <label for="room_photo">Foto Ruangan (Opsional):</label>
                    <input type="file" id="room_photo" name="room_photo" accept="image/jpeg,image/png">
                    <small style="display: block; margin-top: 5px; color: #666;">Upload foto kondisi ruangan (format JPG, JPEG, atau PNG).</small>
                </div>
                
                <div class="button-container">
                    <button type="button" class="back-btn" onclick="window.location.href='my_bookings.php'">Kembali</button>
                    <button type="submit">Kirim Laporan</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
