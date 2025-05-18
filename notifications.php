<?php
/**
 * Mengirim notifikasi ke pengguna
 */
function sendNotification($db, $user_id, $message) {
    $stmt = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)");
    return $stmt->execute(['user_id' => $user_id, 'message' => $message]);
}

/**
 * Mengirim notifikasi booking baru ke admin
 */
function sendBookingNotification($db, $bookingId) {
    // Dapatkan detail booking
    $stmt = $db->prepare("
        SELECT b.*, r.nama_ruang, bg.nama_gedung, u.nama_ekstrakurikuler
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN buildings bg ON r.building_id = bg.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = :booking_id
    ");
    $stmt->execute(['booking_id' => $bookingId]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        return false;
    }
    
    // Dapatkan semua admin
    $stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admins = $stmt->fetchAll();
    
    // Buat pesan notifikasi
    $message = "Permintaan booking ruang baru dari {$booking['nama_ekstrakurikuler']} untuk ruang {$booking['nama_ruang']} ({$booking['nama_gedung']}) pada hari " . ucfirst($booking['hari']) . "\nAlasan: $cancel_reason";
    
    $notif = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)");
    $notif->execute([
    'user_id' => $admin['id'],
    'message' => $message
    ]);
    // Kirim notifikasi ke semua admin
    foreach ($admins as $admin) {
        sendNotification($db, $admin['id'], $message);
    }
    
    return true;
}

/**
 * Mengirim notifikasi status booking ke user
 */
function sendBookingStatusNotification($db, $bookingId, $status) {
    // Dapatkan detail booking
    $stmt = $db->prepare("
        SELECT b.*, r.nama_ruang, bg.nama_gedung, u.id as user_id
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN buildings bg ON r.building_id = bg.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = :booking_id
    ");
    $stmt->execute(['booking_id' => $bookingId]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        return false;
    }
    
    // Status dalam bahasa Indonesia
    $statusText = ($status == 'approved') ? 'disetujui' : 'ditolak';
    
    // Buat pesan notifikasi
    $message = "Permintaan booking ruang {$booking['nama_ruang']} ({$booking['nama_gedung']}) pada hari " . ucfirst($booking['hari']) . " telah $statusText oleh admin.";
    
    // Kirim notifikasi ke user
    return sendNotification($db, $booking['user_id'], $message);
}

/**
 * Mengirim notifikasi laporan ruang ke admin
 */
function sendRoomReportNotification($db, $reportId) {
    // Dapatkan detail laporan
    $stmt = $db->prepare("
        SELECT rr.*, b.hari, r.nama_ruang, bg.nama_gedung, u.nama_ekstrakurikuler
        FROM room_reports rr
        JOIN bookings b ON rr.booking_id = b.id
        JOIN rooms r ON b.room_id = r.id
        JOIN buildings bg ON r.building_id = bg.id
        JOIN users u ON rr.user_id = u.id
        WHERE rr.id = :report_id
    ");
    $stmt->execute(['report_id' => $reportId]);
    $report = $stmt->fetch();
    
    if (!$report) {
        return false;
    }
    
    // Dapatkan semua admin
    $stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admins = $stmt->fetchAll();
    
    // Buat pesan notifikasi
    $message = "Laporan kondisi ruang baru dari {$report['nama_ekstrakurikuler']} untuk ruang {$report['nama_ruang']} ({$report['nama_gedung']}) pada hari " . ucfirst($report['hari']) . ".";
    
    // Kirim notifikasi ke semua admin
    foreach ($admins as $admin) {
        sendNotification($db, $admin['id'], $message);
    }
    
    return true;
}

/**
 * Mendapatkan jumlah notifikasi yang belum dibaca
 */
function getUnreadNotificationCount($db, $user_id) {
    $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0";

    // Tambahkan filter khusus jika user adalah admin
    if ($_SESSION['role'] === 'admin') {
        $sql .= " AND (is_handled = 0 OR is_handled IS NULL)";
    }

    $stmt = $db->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchColumn();
}


/**message
 * Mendapatkan semua notifikasi user
 */
function getUserNotifications($db, $user_id, $limit = 50) {
    $sql = "SELECT * FROM notifications 
            WHERE user_id = :user_id";

    // Jika admin, filter hanya yang belum diproses
    if ($_SESSION['role'] === 'admin') {
        $sql .= " AND (is_handled = 0 OR is_handled IS NULL)";
    }

    $sql .= " ORDER BY created_at DESC LIMIT :limit";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Menandai notifikasi sebagai sudah dibaca
 */
function markNotificationAsRead($db, $notification_id) {
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id");
    return $stmt->execute(['id' => $notification_id]);
}

/**
 * Menandai semua notifikasi user sebagai sudah dibaca
 */
function markAllNotificationsAsRead($db, $user_id) {
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :user_id");
    return $stmt->execute(['user_id' => $user_id]);
}

function sendCancellationNotification($db, $user_id, $booking_id, $cancelled_by) {
       $message = ($cancelled_by === 'admin') 
           ? "Booking Anda telah dibatalkan oleh admin." 
           : "Permintaan pembatalan Anda telah disetujui oleh admin.";
       sendNotification($db, $user_id, $message);
   }
?>


