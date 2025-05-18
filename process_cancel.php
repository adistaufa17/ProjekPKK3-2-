<?php
session_start();
require 'config/database.php'; // ganti dengan file koneksi database kamu

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'];
    $cancel_reason = trim($_POST['cancel_reason']);
    $user_id = $_SESSION['user_id']; // pastikan session user login sudah tersedia

    if (!$cancel_reason) {
        $_SESSION['error'] = 'Alasan pembatalan harus diisi.';
        header("Location: cancel_booking.php?booking_id=" . $booking_id);
        exit;
    }

    // Update data booking
    $stmt = $db->prepare("UPDATE bookings 
                          SET cancel_reason = :reason, cancel_status = 'pending', cancelled_by = 'user' 
                          WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        'reason' => $cancel_reason,
        'id' => $booking_id,
        'user_id' => $user_id
    ]);

    // Tambahkan notifikasi untuk admin
    $adminStmt = $db->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $adminStmt->execute();
    $admin = $adminStmt->fetch();

    if ($admin) {
        $message = "Permintaan pembatalan booking ID $booking_id telah diajukan oleh user.\nAlasan: $cancel_reason";
        $notif = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)");
        $notif->execute([
            'user_id' => $admin['id'],
            'message' => $message
        ]);
    }

    $_SESSION['success'] = 'Permintaan pembatalan berhasil dikirim dan menunggu persetujuan admin.';
    header("Location: my_bookings.php");
    exit;
} else {
    $_SESSION['error'] = 'Permintaan tidak valid.';
    header("Location: my_bookings.php");
    exit;
}
