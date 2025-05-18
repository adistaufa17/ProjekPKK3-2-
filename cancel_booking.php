<?php
session_start();
require 'config/database.php'; // atau koneksi database kamu

// Pastikan booking_id dikirim dari my_bookings
if (!isset($_GET['booking_id'])) {
    $_SESSION['error'] = 'Booking tidak valid.';
    header('Location: my_bookings.php');
    exit;
}

$booking_id = $_GET['booking_id'];

// Ambil data booking jika diperlukan
$stmt = $db->prepare("SELECT b.*, r.nama_ruang, g.nama_gedung 
                      FROM bookings b
                      JOIN rooms r ON b.room_id = r.id
                      JOIN buildings g ON r.building_id = g.id
                      WHERE b.id = :id");
$stmt->execute(['id' => $booking_id]);
$booking = $stmt->fetch();

if (!$booking) {
    $_SESSION['error'] = 'Booking tidak ditemukan.';
    header('Location: my_bookings.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ajukan Pembatalan</title>
    <link rel="stylesheet" href="your-styles.css"> <!-- opsional -->
</head>
<body>
    <div class="container">
        <h2>Ajukan Pembatalan Booking</h2>
        <p><strong>Ruang:</strong> <?= htmlspecialchars($booking['nama_ruang']) ?> (<?= htmlspecialchars($booking['nama_gedung']) ?>)</p>
        <p><strong>Hari:</strong> <?= ucfirst($booking['hari']) ?></p>

        <form action="process_cancel.php" method="POST">
            <input type="hidden" name="booking_id" value="<?= $booking_id ?>">

            <label for="cancel_reason">Alasan Pembatalan:</label><br>
            <textarea name="cancel_reason" id="cancel_reason" rows="5" required style="width: 100%;"></textarea><br><br>

            <button type="submit" style="background-color: #dc3545; color: white; padding: 8px 15px; border: none; border-radius: 3px;">
                Kirim Permintaan Pembatalan
            </button>
        </form>

        <br>
        <a href="my_bookings.php">Kembali</a>
    </div>
</body>
</html>
