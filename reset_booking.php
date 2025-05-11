<?php
require_once 'config/database.php';

// Fungsi untuk mereset data booking
function resetBookingData($db) {
    try {
        // Hanya reset tabel yang terkait booking
        $db->exec("DELETE FROM bookings");
        $db->exec("DELETE FROM room_reports");
        
        // Tambahkan log reset
        file_put_contents('reset_log.txt', "Data booking direset pada: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        
        return true;
    } catch (PDOException $e) {
        file_put_contents('reset_log.txt', "Gagal reset: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}

// Cek apakah perlu direset
$lastResetDate = @file_get_contents('last_reset_date.txt');
$currentWeek = date('Y-W');
$shouldReset = false;

// Reset jika:
// - Belum pernah direset ATAU
// - Sudah Sabtu (hari ke-6) dan minggu berbeda dari terakhir reset
if (!$lastResetDate || 
    (date('w') == 6 && date('Y-W', strtotime($lastResetDate)) != $currentWeek)) {
    
    if (resetBookingData($db)) {
        file_put_contents('last_reset_date.txt', date('Y-m-d H:i:s'));
    }
}
?>