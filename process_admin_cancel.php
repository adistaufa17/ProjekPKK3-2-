<?php
session_start();
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'];
    $action = $_POST['action'];

    // Ambil user_id dari booking
    $stmt = $db->prepare("SELECT user_id FROM bookings WHERE id = :id");
    $stmt->execute(['id' => $booking_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        $_SESSION['error'] = 'Data booking tidak ditemukan.';
        header('Location: notifications_page.php');
        exit;
    }

    $user_id = $booking['user_id'];

    if ($action === 'approve') {
        // Setujui pembatalan
        $db->prepare("UPDATE bookings SET cancel_status = 'approved', status = 'cancelled' WHERE id = :id")
           ->execute(['id' => $booking_id]);

        $message = "Permintaan pembatalan booking ID $booking_id telah disetujui oleh admin.";

    } elseif ($action === 'reject') {
        // Tolak pembatalan
        $db->prepare("UPDATE bookings SET cancel_status = 'rejected' WHERE id = :id")
           ->execute(['id' => $booking_id]);

        $message = "Permintaan pembatalan booking ID $booking_id telah ditolak oleh admin.";
    }

    // Kirim notifikasi ke user
    $notif = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)");
    $notif->execute([
        'user_id' => $user_id,
        'message' => $message
    ]);

    // Hapus notifikasi permintaan pembatalan (hanya untuk admin)
    $db->prepare("UPDATE notifications 
              SET is_handled = 1 
              WHERE user_id = :admin_id 
              AND message LIKE :message")
   ->execute([
       'admin_id' => $_SESSION['user_id'],
       'message' => "%booking ID $booking_id%"
   ]);


    $_SESSION['success'] = 'Permintaan pembatalan berhasil diproses.';
    header('Location: notifications_page.php');
    exit;
}
