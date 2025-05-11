<?php
require_once 'config/database.php';

function resetBookingData($db) {
    try {
        $db->beginTransaction();
        
        // 1. Hapus data laporan terlebih dahulu (child table)
        $db->exec("DELETE FROM room_reports");
        
        // 2. Hapus semua booking (parent table)
        $db->exec("DELETE FROM bookings");
        
        $db->commit();
        
        // Simpan status reset ke session
        $_SESSION['reset_status'] = 'success';
        $_SESSION['reset_message'] = 'Data booking telah direset otomatis untuk minggu baru.';
        
        // Log reset
        file_put_contents('reset_log.txt', date('Y-m-d H:i:s')." - Reset otomatis\n", FILE_APPEND);
        
        return true;
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['reset_status'] = 'error';
        $_SESSION['reset_message'] = 'Gagal mereset data: '.$e->getMessage();
        return false;
    }
}

// Cek kapan terakhir reset
$lastReset = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'last_reset'")->fetchColumn();
$currentWeek = date('Y-W');
$needsReset = false;

// Logika reset otomatis (Sabtu jam 00:00)
if (date('w') == 6 && date('H') == 0) {
    if (!$lastReset || date('Y-W', strtotime($lastReset)) != $currentWeek) {
        $needsReset = true;
    }
}

// Opsi manual override untuk testing
if (isset($_GET['forcereset'])) {
    $needsReset = true;
}

// Eksekusi reset jika diperlukan
if ($needsReset) {
    if (resetBookingData($db)) {
        // Update last_reset di database
        $db->exec("INSERT INTO system_settings (setting_key, setting_value) 
                 VALUES ('last_reset', NOW()) 
                 ON DUPLICATE KEY UPDATE setting_value = NOW()");
    }
}
?>