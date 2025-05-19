<?php
session_start();
require_once 'config/database.php';
require_once 'notifications.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];
    $action = $_POST['action'];

    // Validasi booking
    $stmt = $db->prepare("SELECT * FROM bookings WHERE id = :id");
    $stmt->execute(['id' => $booking_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        $_SESSION['error'] = 'Data booking tidak ditemukan.';
        header("Location: lapor_ruang.php");
        exit();
    }

    if ($booking['status'] !== 'pending') {
        $_SESSION['error'] = 'Booking sudah diproses sebelumnya.';
        header("Location: lapor_ruang.php");
        exit();
    }

    if ($action === 'approve') {
        $stmt = $db->prepare("UPDATE bookings SET status = 'approved' WHERE id = :id");
        $stmt->execute(['id' => $booking_id]);
        sendBookingStatusNotification($db, $booking_id, 'approved');
        $_SESSION['success'] = "Booking berhasil disetujui.";
    } elseif ($action === 'reject') {
        $stmt = $db->prepare("UPDATE bookings SET status = 'rejected' WHERE id = :id");
        $stmt->execute(['id' => $booking_id]);
        sendBookingStatusNotification($db, $booking_id, 'rejected');
        $_SESSION['success'] = "Booking berhasil ditolak.";
    } else {
        $_SESSION['error'] = 'Aksi tidak valid.';
    }

    header("Location: lapor_ruang.php");
    exit();
}
// Setelah booking berhasil disimpan
$admin_id = 3; // ID admin, pastikan ini sesuai dengan data di tabel users
$message = "Permintaan booking ruang baru dari user ID $user_id menunggu persetujuan.";

$stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)");
$stmt->execute([$admin_id, $message]);

