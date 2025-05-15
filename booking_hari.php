<?php
session_start();
require_once 'config/database.php';
require_once 'notifications.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get current day index (0 = Sunday, 1 = Monday, ..., 6 = Saturday)
$currentDayIndex = date('w');
// Convert to our system (1 = Senin, 2 = Selasa, ..., 5 = Jumat)
// Sunday (0) -> no booking possible
// Monday (1) -> index 1
// Tuesday (2) -> index 2
// etc.
$currentDayIndexSystem = $currentDayIndex == 0 ? 0 : $currentDayIndex;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Hari - Sistem Pemesanan Ruang</title>
    <link rel="stylesheet" href="assets/css/style_booking_hari.css">
    <link rel="stylesheet" href="assets/css/stylesidebar.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">

    <style>
        :root {
            --sidebar-color: #1a2b47;
            --sidebar-hover: #2e4064;
            --sidebar-active: #3a5280;
            --header-color: #3498db;
            --card-color: #fff;
            --card-hover: #f5f5f5;
            --text-primary: #fff;
            --text-dark: #333;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
        }
        
        /* Style for disabled cards */
        .card.disabled {
            background-color: #e0e0e0;
            color: #999;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        /* Info message style */
        .info-message {
            background-color: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #333;
        }
    </style>
</head>
<body>
<button class="menu-toggle" id="menuToggle" style="display:none;">
        <i class="fas fa-bars"></i>
</button>
<div class="sidebar">
        <img src="assets/img/logo.png" alt="Logo" class="logo">
        <ul class="menu">
            <li class="menu-item"><a href="beranda.php"><i class="fas fa-home"></i> <span class="menu-text">Beranda</span></a></li>
            <li class="menu-item active"><a href="booking_hari.php"><i class="fas fa-calendar-check"></i> <span class="menu-text">Booking Ruang</span></a></li>
            <li class="menu-item"><a href="my_bookings.php"><i class="fas fa-history"></i> <span class="menu-text">Riwayat Booking</span></a></li>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <li class="menu-item"><a href="lapor_ruang.php"><i class="fas fa-clipboard-list"></i> <span class="menu-text">Kelola Booking</span></a></li>
            <li class="menu-item"><a href="view_reports.php"><i class="fas fa-clipboard-check"></i> <span class="menu-text">Laporan Ruang</span></a></li>
            <li class="menu-item"><a href="teamdev.php"><i class="fas fa-home"></i> Team Developer</a></li>
            <?php endif; ?>
            <li class="menu-item <?= basename($_SERVER['PHP_SELF']) == 'notifications_page.php' ? 'active' : '' ?>">
                <a href="notifications_page.php">
                    <i class="fas fa-bell"></i> 
                        <span class="menu-text">
                        Notifikasi
                        <?php 
                    // Dapatkan jumlah notifikasi yang belum dibaca
                    $unreadCount = getUnreadNotificationCount($db, $_SESSION['user_id']);
                     // Tampilkan badge jika ada notifikasi yang belum dibaca
                    if ($unreadCount > 0): 
                 ?>
                <span class="notification-badge"><?= $unreadCount ?></span>
            <?php endif; ?>
        </span>
    </a>
</li>

            <li class="menu-item"><a href="logout_confirmation.php"><i class="fas fa-sign-out-alt"></i> <span class="menu-text">Logout</span></a></li>
        </ul>
    </div>



    <div class="container">
        <div class="main-content">
            <div class="header">
                <h1>PILIH HARI UNTUK BOOKING RUANG</h1>
            </div>
            
            <?php if ($currentDayIndex > 0 && $currentDayIndex < 6): // Only show this message on weekdays ?>
            <div class="info-message">
                <i class="fas fa-info-circle"></i> Anda hanya dapat booking ruangan untuk hari ini (<?= getDayName($currentDayIndex) ?>) dan hari-hari berikutnya dalam minggu ini.
            </div>
            <?php endif; ?>
            
            <div class="grid">
                <?php
                // Array of weekdays
                $days = ['senin', 'selasa', 'rabu', 'kamis', 'jumat'];
                $dayIndex = 1; // Monday = 1
                
                foreach ($days as $day):
                    $isDisabled = $dayIndex < $currentDayIndexSystem;
                    $cardClass = $isDisabled ? 'card disabled' : 'card';
                    $clickHandler = $isDisabled ? '' : 'onclick="location.href=\'booking_ruang.php?hari=' . $day . '\'"';
                ?>
                    <div class="<?= $cardClass ?>" <?= $clickHandler ?>>
                        <?= ucfirst($day) ?>
                        <?php if ($isDisabled): ?>
                            <div style="font-size: 12px; margin-top: 5px;">(Tidak tersedia)</div>
                        <?php endif; ?>
                    </div>
                <?php
                    $dayIndex++;
                endforeach;
                ?>
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
        
   document.addEventListener('DOMContentLoaded', function() {
            const mediaQuery = window.matchMedia('(max-width: 360px)');
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.querySelector('.sidebar');
            
            function handleScreenChange(e) {
                if (e.matches) {
                    menuToggle.style.display = 'block';
                    sidebar.classList.remove('active');
                } else {
                    menuToggle.style.display = 'none';
                    sidebar.classList.remove('active');
                }
            }
            
            // Initial check
            handleScreenChange(mediaQuery);
            
            // Add event listener for changes
            mediaQuery.addEventListener('change', handleScreenChange);
            
            // Toggle menu on click
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (!sidebar.contains(e.target) && e.target !== menuToggle) {
                    sidebar.classList.remove('active');
                }
            });
        });
</script>

</body>
</html>

<?php
// Helper function to get day name in Indonesian
function getDayName($dayIndex) {
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    return $days[$dayIndex];
}
?>