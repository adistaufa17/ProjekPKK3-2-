<?php
/**
 * Fungsi untuk mengirim email notifikasi
 * Karena InfinityFree memiliki keterbatasan email, kita akan menggunakan dua pendekatan:
 * 1. Di lingkungan local: simpan email ke file log
 * 2. Di lingkungan hosting: gunakan service email pihak ketiga
 */

function sendEmail($to, $subject, $message) {
    // Deteksi apakah kita berada di localhost atau server
    $isLocal = ($_SERVER['SERVER_NAME'] == 'localhost' || substr($_SERVER['SERVER_NAME'], 0, 3) == '127');
    
    if ($isLocal) {
        // Di lingkungan local, simpan ke file log
        $log = "=====================\n";
        $log .= "Date: " . date('Y-m-d H:i:s') . "\n";
        $log .= "To: $to\n";
        $log .= "Subject: $subject\n";
        $log .= "Message: $message\n";
        $log .= "=====================\n\n";
        
        // Buat direktori logs jika belum ada
        if (!file_exists('logs')) {
            mkdir('logs', 0777, true);
        }
        
        // Simpan log
        file_put_contents('logs/email.log', $log, FILE_APPEND);
        return true;
    } else {
        // Di server hosting, gunakan fungsi mail PHP
        // Untuk hosting InfinityFree yang memiliki keterbatasan email
        // Berikut ini mungkin tidak berfungsi sempurna
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Sistem Pemesanan Ruang <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
        
        // Coba kirim email
        $result = mail($to, $subject, $message, $headers);
        
        // Log hasil pengiriman untuk keperluan debugging
        $log = "=====================\n";
        $log .= "Date: " . date('Y-m-d H:i:s') . "\n";
        $log .= "To: $to\n";
        $log .= "Subject: $subject\n";
        $log .= "Result: " . ($result ? "Success" : "Failed") . "\n";
        $log .= "=====================\n\n";
        
        // Buat direktori logs jika belum ada
        if (!file_exists('logs')) {
            mkdir('logs', 0777, true);
        }
        
        // Simpan log
        file_put_contents('logs/email.log', $log, FILE_APPEND);
        
        return $result;
    }
}

/**
 * Fungsi untuk mengirim notifikasi booking baru ke admin
 */
function sendBookingNotification($db, $bookingId) {
    // Ambil data booking
    $stmt = $db->prepare("
        SELECT b.*, r.nama_ruang, bg.nama_gedung, u.nama_ekstrakurikuler, u.email as user_email
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN buildings bg ON r.building_id = bg.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = :id
    ");
    $stmt->execute(['id' => $bookingId]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        return false;
    }
    
    // Ambil email admin
    $stmt = $db->prepare("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    $adminEmail = $admin['email'] ?? 'admin@sekolah.sch.id';
    
    // Template email untuk admin
    $subject = "Permintaan Booking Ruang Baru";
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; }
            .header { background: #3498db; color: white; padding: 15px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .details { margin: 15px 0; padding: 15px; background: #fff; border-left: 5px solid #3498db; }
            .footer { padding: 10px; text-align: center; font-size: 12px; color: #777; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Permintaan Booking Ruang Baru</h2>
            </div>
            <div class='content'>
                <p>Kepada Admin,</p>
                <p>Ada permintaan booking ruang baru yang memerlukan persetujuan Anda:</p>
                <div class='details'>
                    <p><strong>Ekstrakurikuler:</strong> {$booking['nama_ekstrakurikuler']}</p>
                    <p><strong>Ruang:</strong> {$booking['nama_ruang']}</p>
                    <p><strong>Gedung:</strong> {$booking['nama_gedung']}</p>
                    <p><strong>Hari:</strong> " . ucfirst($booking['hari']) . "</p>
                    <p><strong>Waktu Pengajuan:</strong> " . date('d-m-Y H:i', strtotime($booking['created_at'])) . "</p>
                </div>
                <p>Silakan login ke sistem untuk menyetujui atau menolak permintaan ini.</p>
            </div>
            <div class='footer'>
                <p>Email ini dikirim secara otomatis oleh Sistem Pemesanan Ruang. Mohon tidak membalas email ini.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Kirim email
    return sendEmail($adminEmail, $subject, $message);
}

/**
 * Fungsi untuk mengirim notifikasi status booking ke user
 */
function sendBookingStatusNotification($db, $bookingId, $status) {
    // Ambil data booking
    $stmt = $db->prepare("
        SELECT b.*, r.nama_ruang, bg.nama_gedung, u.nama_ekstrakurikuler, u.email as user_email
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN buildings bg ON r.building_id = bg.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = :id
    ");
    $stmt->execute(['id' => $bookingId]);
    $booking = $stmt->fetch();
    
    if (!$booking || empty($booking['user_email'])) {
        return false;
    }
    
    // Tentukan status dalam bahasa Indonesia
    $statusText = ($status == 'approved') ? 'Disetujui' : 'Ditolak';
    $statusColor = ($status == 'approved') ? '#28a745' : '#dc3545';
    
    // Template email untuk user
    $subject = "Pembaruan Status Booking Ruang - $statusText";
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; }
            .header { background: #3498db; color: white; padding: 15px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .details { margin: 15px 0; padding: 15px; background: #fff; border-left: 5px solid $statusColor; }
            .status { display: inline-block; padding: 5px 10px; background-color: $statusColor; color: white; border-radius: 3px; }
            .footer { padding: 10px; text-align: center; font-size: 12px; color: #777; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Pembaruan Status Booking Ruang</h2>
            </div>
            <div class='content'>
                <p>Kepada {$booking['nama_ekstrakurikuler']},</p>
                <p>Status pemesanan ruang Anda telah diperbarui menjadi: <span class='status'>$statusText</span></p>
                <div class='details'>
                    <p><strong>Ruang:</strong> {$booking['nama_ruang']}</p>
                    <p><strong>Gedung:</strong> {$booking['nama_gedung']}</p>
                    <p><strong>Hari:</strong> " . ucfirst($booking['hari']) . "</p>
                </div>
                <p>Silakan cek riwayat booking Anda di sistem untuk informasi lebih lanjut.</p>
            </div>
            <div class='footer'>
                <p>Email ini dikirim secara otomatis oleh Sistem Pemesanan Ruang. Mohon tidak membalas email ini.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Kirim email
    return sendEmail($booking['user_email'], $subject, $message);
}

/**
 * Fungsi untuk mengirim notifikasi laporan ruang ke admin
 */
function sendRoomReportNotification($db, $reportId) {
    // Ambil data laporan
    $stmt = $db->prepare("
        SELECT rr.*, b.hari, r.nama_ruang, bg.nama_gedung, u.nama_ekstrakurikuler, u.email as user_email
        FROM room_reports rr
        JOIN bookings b ON rr.booking_id = b.id
        JOIN rooms r ON b.room_id = r.id
        JOIN buildings bg ON r.building_id = bg.id
        JOIN users u ON rr.user_id = u.id
        WHERE rr.id = :id
    ");
    $stmt->execute(['id' => $reportId]);
    $report = $stmt->fetch();
    
    if (!$report) {
        return false;
    }
    
    // Ambil email admin
    $stmt = $db->prepare("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    $adminEmail = $admin['email'] ?? 'admin@sekolah.sch.id';
    
    // Template email untuk admin
    $subject = "Laporan Kondisi Ruang Baru";
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; }
            .header { background: #3498db; color: white; padding: 15px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .details { margin: 15px 0; padding: 15px; background: #fff; border-left: 5px solid #3498db; }
            .footer { padding: 10px; text-align: center; font-size: 12px; color: #777; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Laporan Kondisi Ruang Baru</h2>
            </div>
            <div class='content'>
                <p>Kepada Admin,</p>
                <p>Ada laporan kondisi ruang baru yang telah dikirimkan:</p>
                <div class='details'>
                    <p><strong>Ekstrakurikuler:</strong> {$report['nama_ekstrakurikuler']}</p>
                    <p><strong>Ruang:</strong> {$report['nama_ruang']}</p>
                    <p><strong>Gedung:</strong> {$report['nama_gedung']}</p>
                    <p><strong>Hari:</strong> " . ucfirst($report['hari']) . "</p>
                    <p><strong>Waktu Laporan:</strong> " . date('d-m-Y H:i', strtotime($report['report_time'])) . "</p>
                </div>
                <p>Silakan login ke sistem untuk melihat detail laporan ini.</p>
            </div>
            <div class='footer'>
                <p>Email ini dikirim secara otomatis oleh Sistem Pemesanan Ruang. Mohon tidak membalas email ini.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Kirim email
    return sendEmail($adminEmail, $subject, $message);
}

?>
