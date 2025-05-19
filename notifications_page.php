<?php
session_start();
require_once 'config/database.php';
require_once 'notifications.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] === 'admin') {
    $pendingCountSidebar = getPendingBookingCount($db);
}
// Mark notification as read if requested
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    markNotificationAsRead($db, $_GET['mark_read']);
    header("Location: notifications_page.php");
    exit();
}

// Mark all notifications as read if requested
if (isset($_GET['mark_all_read'])) {
    markAllNotificationsAsRead($db, $_SESSION['user_id']);
    
    // Tambahkan script untuk menghapus badge setelah menandai semua dibaca
    echo '<script>
        // Hapus semua badge notifikasi
        document.addEventListener("DOMContentLoaded", function() {
            const badges = document.querySelectorAll(".notification-badge");
            badges.forEach(badge => badge.remove());
        });
    </script>';
    
    header("Location: notifications_page.php");
    exit();
}

// Get user notifications
$notifications = getUserNotifications($db, $_SESSION['user_id'], 50);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - Sistem Pemesanan Ruang</title>
    <link rel="stylesheet" href="assets/css/style_detail_laporan.css">
    <link rel="stylesheet" href="assets/css/stylesidebar.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        .notification-list {
            max-width: 800px;
            margin: 0 auto;
        }

        .notification-item {
            background-color: #fff;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }

        .notification-item.unread {
            border-left: 4px solid #3498db;
            background-color: #f0f7ff;
        }

        .notification-icon {
            background-color: #e1f0ff;
            color: #3498db;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .notification-content {
            flex-grow: 1;
        }

        .notification-message {
            margin-bottom: 5px;
            color: #333;
        }

        .notification-time {
            font-size: 12px;
            color: #777;
        }

        .notification-actions {
            display: flex;
            align-items: center;
        }

        .mark-read-btn {
            background: none;
            border: none;
            color: #3498db;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 4px;
            transition: all 0.3s;
        }

        .mark-read-btn:hover {
            background-color: #e1f0ff;
        }

        .mark-read-btn i {
            margin-right: 5px;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .mark-all-read-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }

        .mark-all-read-btn:hover {
            background-color: #2980b9;
        }

        .mark-all-read-btn i {
            margin-right: 8px;
        }

        .empty-notifications {
            text-align: center;
            padding: 50px 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .empty-notifications i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 20px;
            display: block;
        }

        @media (max-width: 768px) {
            .notification-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .mark-all-read-btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Badge Styling */
        .notification-badge {
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            min-width: 18px;
            text-align: center;
        }
        
        .menu-item a {
            position: relative;
        }
        
        @media (max-width: 768px) {
            .notification-badge {
                right: 5px;
            }
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
            <li class="menu-item"><a href="booking_hari.php"><i class="fas fa-calendar-check"></i> <span class="menu-text">Booking Ruang</span></a></li>
            <li class="menu-item"><a href="my_bookings.php"><i class="fas fa-history"></i> <span class="menu-text">Riwayat Booking</span></a></li>
            <li class="menu-item"><a href="teamdev.php"><i class="fas fa-users"></i><span class="menu-text">Team Developer</span></a></li>     
            <?php if ($_SESSION['role'] === 'admin'): ?>
             <li class="menu-item">
                <a href="lapor_ruang.php">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="menu-text">
                        Kelola Booking
                        <?php if ($pendingCountSidebar > 0): ?>
                            <span class="notification-badge"><?= $pendingCountSidebar ?></span>
                        <?php endif; ?>
                    </span>
                </a>
            </li>
            <li class="menu-item"><a href="view_reports.php"><i class="fas fa-clipboard-check"></i> <span class="menu-text">Laporan Ruang</span></a></li>
            <?php endif; ?>
            <li class="menu-item active">
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

    <div class="main-content">
        <div class="header">
            <h1>Notifikasi</h1>
        </div>
        <div class="content-area">
            <div class="notification-list">
                <div class="notification-header">
                    <h2>Pemberitahuan Terbaru</h2>
                    <?php if (count($notifications) > 0): ?>
                        <a href="?mark_all_read=1" class="mark-all-read-btn" id="markAllReadBtn">
                            <i class="fas fa-check-double"></i> Tandai Semua Dibaca
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if (count($notifications) > 0): ?>
                    <?php foreach ($notifications as $notification): ?>
    <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>">
        <div class="notification-icon">
            <i class="fas fa-bell"></i>
        </div>
        <div class="notification-content">
            <div class="notification-message">
                <?= htmlspecialchars($notification['message']) ?>
            </div>
            <div class="notification-time">
                <i class="fas fa-clock"></i> <?= date('d M Y H:i', strtotime($notification['created_at'])) ?>
            </div>

            <?php
            // Cek apakah notifikasi ini adalah permintaan pembatalan booking
            if ($_SESSION['role'] === 'admin' && preg_match('/Permintaan pembatalan booking ID (\d+)/', $notification['message'], $matches)):
    $booking_id = $matches[1];
    // Tampilkan tombol setuju/tolak hanya untuk admin
?>
            
            <form action="process_admin_cancel.php" method="POST" style="margin-top: 10px;">
                <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
                <button type="submit" name="action" value="approve" style="color: white; background-color: green; border: none; padding: 5px 10px; margin-right: 5px;">
                    <i class="fas fa-check"></i> Setujui
                </button>
                <button type="submit" name="action" value="reject" style="color: white; background-color: red; border: none; padding: 5px 10px;">
                    <i class="fas fa-times"></i> Tolak
                </button>
            </form>
            <?php endif; ?>
        </div>

        <?php if (!$notification['is_read']): ?>
            <div class="notification-actions">
                <a href="?mark_read=<?= $notification['id'] ?>" class="mark-read-btn">
                    <i class="fas fa-check"></i> Tandai Dibaca
                </a>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

                <?php else: ?>
                    <div class="empty-notifications">
                        <i class="fas fa-bell-slash"></i>
                        <h3>Tidak Ada Notifikasi</h3>
                        <p>Anda tidak memiliki notifikasi saat ini. Notifikasi akan muncul ketika ada aktivitas baru.</p>
                    </div>
                <?php endif; ?>
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
            })
            .catch(error => console.error('Error fetching notifications:', error));
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
            
            // Mark all read button functionality
            const markAllReadBtn = document.getElementById('markAllReadBtn');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', function() {
                    // Hapus semua badge notifikasi dari tampilan sebelum page reload
                    const badges = document.querySelectorAll('.notification-badge');
                    badges.forEach(badge => badge.remove());
                });
            }
        });        
    </script>
</body>
</html>
