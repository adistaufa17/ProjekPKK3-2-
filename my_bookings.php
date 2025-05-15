<?php
session_start();
require_once 'config/database.php';
require_once 'notifications.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user bookings
$stmt = $db->prepare("
    SELECT b.*, r.nama_ruang, bg.nama_gedung
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN buildings bg ON r.building_id = bg.id
    WHERE b.user_id = :user_id
    ORDER BY b.status = 'pending' DESC, b.status = 'approved' DESC, b.hari, b.created_at DESC
");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$bookings = $stmt->fetchAll();

// Status text translations
$statusTexts = [
    'pending' => 'Menunggu Konfirmasi',
    'approved' => 'Disetujui',
    'rejected' => 'Ditolak'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Booking - Sistem Pemesanan Ruang</title>
    <link rel="stylesheet" href="assets/css/style_detail_laporan.css">
    <link rel="stylesheet" href="assets/css/stylesidebar.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        :root {
            --sidebar-color: #1a2b47;
            --sidebar-hover: #2e4064;
            --sidebar-active: #3a5280;
            --text-primary: #fff;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .empty-booking {
            text-align: center;
            padding: 50px 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        
        .empty-booking i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 20px;
            display: block;
        }
        
        .booking-btn {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 15px;
            transition: all 0.3s;
        }
        
        .booking-btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .card-date {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        /* Responsive styling for cards */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 60px;
                width: calc(100% - 60px);
            }
            
            .header h1 {
                font-size: 20px;
                padding: 15px 10px;
            }
            
            .content-area {
                padding: 15px 10px;
            }
            
            .alert {
                padding: 10px;
                font-size: 14px;
            }
            
            .card {
                flex-direction: column;
            }
            
            .card-image {
                width: 100%;
                height: auto;
                min-height: 60px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .card-content {
                width: 100%;
                padding: 15px;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                margin-bottom: 12px;
            }
            
            .card-title {
                margin-bottom: 5px;
            }
            
            .card-details {
                flex-direction: column;
                gap: 10px;
            }
            
            .report-btn {
                width: 100%;
                text-align: center;
                padding: 8px !important;
                font-size: 14px !important;
            }
        }
        
        /* Enhanced styling for very small screens */
        @media (max-width: 480px) {
            .content-area {
                padding: 10px 5px;
            }
            
            .card-content {
                padding: 12px 10px;
            }
            
            .card-status {
                font-size: 10px;
                padding: 2px 6px;
            }
            
            .card-meta {
                font-size: 13px;
            }
            
            .card-details {
                font-size: 12px;
            }
        }
        
        /* Hamburger menu for extreme small screens */
        @media (max-width: 360px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding-top: 50px;
            }
            
            .sidebar {
                width: 0;
                transform: translateX(-100%);
                transition: all 0.3s ease;
            }
            
            .sidebar.active {
                width: 200px;
                transform: translateX(0);
            }
            
            .menu-toggle {
                display: block !important;
                position: fixed;
                top: 10px;
                left: 10px;
                z-index: 1000;
                background-color: var(--sidebar-color);
                color: white;
                border: none;
                width: 40px;
                height: 40px;
                border-radius: 5px;
                cursor: pointer;
            }
        }
        
        /* Sorted and filtered tabs */
        .sort-tabs {
            display: flex;
            overflow-x: auto;
            background: #fff;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .sort-tab {
            padding: 8px 15px;
            border: none;
            background: #f0f0f0;
            margin-right: 5px;
            border-radius: 20px;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .sort-tab.active {
            background: #3498db;
            color: white;
        }
        
        @media (max-width: 480px) {
            .sort-tab {
                padding: 6px 12px;
                font-size: 13px;
            }
        }
        
        /* Pull to refresh animation */
        .refresh-animation {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #3498db;
            transform: translateX(-100%);
            z-index: 1000;
        }
        
        .refresh-animation.active {
            animation: refresh-progress 1s ease-out forwards;
        }
        
        @keyframes refresh-progress {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(0); }
        }
        
        /* Empty state improvements */
        .empty-booking i {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <img src="assets/img/logo.png" alt="Logo" class="logo">
        <ul class="menu">
            <li class="menu-item"><a href="beranda.php"><i class="fas fa-home"></i> <span class="menu-text">Beranda</span></a></li>
            <li class="menu-item"><a href="booking_hari.php"><i class="fas fa-calendar-check"></i> <span class="menu-text">Booking Ruang</span></a></li>
            <li class="menu-item active"><a href="my_bookings.php"><i class="fas fa-history"></i> <span class="menu-text">Riwayat Booking</span></a></li>
            <li class="menu-item"><a href="teamdev.php"><i class="fas fa-home"></i> Team Developer</a></li>

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
    
    <button class="menu-toggle" id="menuToggle" style="display:none;">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="refresh-animation" id="refreshAnimation"></div>

    <div class="main-content">
        <div class="header">
            <h1>Riwayat Booking Saya</h1>
        </div>
        <div class="content-area">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success'] ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (count($bookings) > 0): ?>
                <div class="sort-tabs">
                    <button class="sort-tab active" data-filter="all">Semua</button>
                    <button class="sort-tab" data-filter="pending">Menunggu Konfirmasi</button>
                    <button class="sort-tab" data-filter="approved">Disetujui</button>
                    <button class="sort-tab" data-filter="rejected">Ditolak</button>
                </div>
                
                <div class="cards-container">
                    <?php foreach ($bookings as $booking): ?>
                        <?php 
                        $statusClass = 'status-' . $booking['status'];
                        $statusText = $statusTexts[$booking['status']];
                        ?>
                        <div class="card" data-status="<?= $booking['status'] ?>">
                            <div class="card-image">
                                <i class="fas fa-door-open"></i>
                                <?= htmlspecialchars($booking['nama_ruang']) ?>
                            </div>
                            <div class="card-content">
                                <div class="card-header">
                                    <span class="card-title"><?= htmlspecialchars($booking['nama_gedung']) ?></span>
                                    <span class="card-status <?= $statusClass ?>"><?= $statusText ?></span>
                                </div>
                                <div class="card-meta">
                                    <span><?= htmlspecialchars($_SESSION['nama_ekstrakurikuler']) ?></span>
                                </div>
                                <div class="card-details">
                                    <div>
                                        <span><i class="fas fa-calendar"></i> <?= ucfirst($booking['hari']) ?></span>
                                        <div class="card-date"><i class="fas fa-clock"></i> <?= date('d-m-Y H:i', strtotime($booking['created_at'])) ?></div>
                                    </div>
                                    <?php if ($booking['status'] === 'approved'): ?>
                                    <?php
                                    // Periksa apakah sudah ada laporan untuk booking ini
                                    $reportStmt = $db->prepare("SELECT id FROM room_reports WHERE booking_id = :booking_id");
                                    $reportStmt->execute(['booking_id' => $booking['id']]);
                                    $hasReport = $reportStmt->fetch();
        
                                    if (!$hasReport):
                                    ?>
                                    <a href="create_report.php?booking_id=<?= $booking['id'] ?>" class="report-btn" style="background-color: #28a745; color: white; padding: 5px 10px; border-radius: 3px; text-decoration: none; display: inline-block; font-size: 14px;">
                                        <i class="fas fa-clipboard-check"></i> Lapor Kondisi Ruang
                                    </a>
                                    <?php else: ?>
                                        <span style="color: #28a745; font-size: 14px;"><i class="fas fa-check-circle"></i> Laporan Terkirim</span>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-booking">
                    <i class="fas fa-calendar-times"></i>
                    <h3>Belum Ada Booking</h3>
                    <p>Anda belum pernah melakukan booking ruang. Silakan booking ruang untuk melihat riwayat booking Anda.</p>
                    <a href="booking_hari.php" class="booking-btn">Booking Ruang Sekarang</a>
                </div>
            <?php endif; ?>
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
        
        // Responsive menu toggle for mobile
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
                    sidebar.classList.add('active');
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
                if (mediaQuery.matches && !sidebar.contains(e.target) && e.target !== menuToggle) {
                    sidebar.classList.remove('active');
                }
            });
            
            // Sort tabs functionality
            const sortTabs = document.querySelectorAll('.sort-tab');
            sortTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    sortTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filter = this.getAttribute('data-filter');
                    const cards = document.querySelectorAll('.card');
                    
                    cards.forEach(card => {
                        if (filter === 'all' || card.getAttribute('data-status') === filter) {
                            card.style.display = 'flex';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
            
            // Pull to refresh functionality for mobile
            let startY;
            let endY;
            const minDistance = 100; // Minimal jarak pull untuk refresh
            let refreshing = false;
            const refreshAnimation = document.getElementById('refreshAnimation');
            
            document.addEventListener('touchstart', function(e) {
                startY = e.touches[0].clientY;
            }, { passive: true });
            
            document.addEventListener('touchmove', function(e) {
                if (refreshing) return;
                
                // Only trigger when scrolled to top
                if (window.scrollY === 0) {
                    endY = e.touches[0].clientY;
                    const distance = endY - startY;
                    
                    if (distance > 0 && distance < 200) {
                        // Visual feedback - transform content slightly
                        document.querySelector('.main-content').style.transform = `translateY(${distance * 0.2}px)`;
                    }
                }
            }, { passive: true });
            
            document.addEventListener('touchend', function() {
                if (!startY || !endY) return;
                
                const distance = endY - startY;
                
                // Reset positions
                document.querySelector('.main-content').style.transform = '';
                
                // If pull distance is enough, refresh the page
                if (distance > minDistance && window.scrollY === 0 && !refreshing) {
                    refreshing = true;
                    refreshAnimation.classList.add('active');
                    
                    // Simulate page refresh after animation
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            }, { passive: true });
        });
    </script>
</body>
</html>
