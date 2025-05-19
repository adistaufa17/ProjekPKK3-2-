<?php
session_start();
require_once 'config/database.php';
require_once 'notifications.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] === 'admin') {
    $pendingCountSidebar = getPendingBookingCount($db);
}

// Process status update
if (isset($_POST['action']) && isset($_POST['booking_id'])) {
    $action = $_POST['action'];
    $booking_id = $_POST['booking_id'];
    
    if ($action === 'approve') {
        $stmt = $db->prepare("UPDATE bookings SET status = 'approved' WHERE id = :id");
        $stmt->execute(['id' => $booking_id]);
        
        // Kirim email notifikasi ke user
        sendBookingStatusNotification($db, $booking_id, 'approved');
        
        $_SESSION['success'] = "Booking berhasil disetujui dan notifikasi telah dikirim ke user.";
    } else if ($action === 'reject') {
        $stmt = $db->prepare("UPDATE bookings SET status = 'rejected' WHERE id = :id");
        $stmt->execute(['id' => $booking_id]);
        
        // Kirim email notifikasi ke user
        sendBookingStatusNotification($db, $booking_id, 'rejected');
        
        $_SESSION['success'] = "Booking berhasil ditolak dan notifikasi telah dikirim ke user.";
    }
    
    // Redirect to refresh
    header("Location: admin_bookings.php");
    exit();
}

// Get all bookings
$stmt = $db->query("
    SELECT b.*, r.nama_ruang, bg.nama_gedung, u.nama_ekstrakurikuler
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN buildings bg ON r.building_id = bg.id
    JOIN users u ON b.user_id = u.id
    ORDER BY b.status, b.hari, bg.id, r.nama_ruang
");
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Booking - Sistem Pemesanan Ruang</title>
    <link rel="stylesheet" href="assets/css/style_detail_laporan.css">
    <link rel="stylesheet" href="assets/css/stylesidebar.css">
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
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .action-buttons button {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .approve-btn {
            background-color: #28a745;
            color: white;
        }
        .reject-btn {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
<div class="sidebar">
    <img src="assets/img/logo.png" alt="Logo" class="logo">
    <ul class="menu">
        <li class="menu-item"><a href="beranda.php"><i class="fas fa-home"></i> Beranda</a></li>
        <li class="menu-item"><a href="booking_hari.php"><i class="fas fa-calendar-check"></i> Booking Ruang</a></li>
        <li class="menu-item"><a href="my_bookings.php"><i class="fas fa-history"></i> Riwayat Booking</a></li>
        <li class="menu-item"><a href="teamdev.php"><i class="fas fa-users"></i> Team Developer</a></li>
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
            <li class="menu-item"><a href="view_reports.php"><i class="fas fa-clipboard-check"></i> Laporan Ruang</a></li>
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

    <div class="main-content">
        <div class="header">
            <h1>Kelola Pemesanan Ruang</h1>
        </div>
        <div class="content-area">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                    <?= $_SESSION['success'] ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-bar" id="searchBar" placeholder="Cari ruangan atau ekstrakurikuler...">
            </div>
            
            <div class="cards-container" id="cardsContainer">
                <?php if (count($bookings) > 0): ?>
                    <?php foreach ($bookings as $booking): ?>
                        <?php 
                        $statusClass = '';
                        switch ($booking['status']) {
                            case 'pending':
                                $statusClass = 'status-pending';
                                break;
                            case 'approved':
                                $statusClass = 'status-approved';
                                break;
                            case 'rejected':
                                $statusClass = 'status-rejected';
                                break;
                        }
                        ?>
                        <div class="card" data-room="<?= htmlspecialchars($booking['nama_ruang']) ?>" data-eskul="<?= htmlspecialchars($booking['nama_ekstrakurikuler']) ?>">
                            <div class="card-image">
                                <i class="fas fa-door-open"></i>
                                <?= htmlspecialchars($booking['nama_ruang']) ?>
                            </div>
                            <div class="card-content">
                                <div class="card-header">
                                    <span class="card-title"><?= htmlspecialchars($booking['nama_gedung']) ?></span>
                                    <span class="card-status <?= $statusClass ?>"><?= ucfirst($booking['status']) ?></span>
                                </div>
                                <div class="card-meta">
                                    <span><?= htmlspecialchars($booking['nama_ekstrakurikuler']) ?></span>
                                </div>
                                <div class="card-details">
                                    <span><i class="fas fa-calendar"></i> <?= ucfirst($booking['hari']) ?></span>
                                    <span><i class="fas fa-clock"></i> <?= date('d M Y H:i', strtotime($booking['created_at'])) ?></span>
                                </div>
                                <div class="card-actions" style="margin-top: 10px; display: flex; justify-content: flex-end;">
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <div class="action-buttons">
                                            <form method="post" action="">
                                                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="approve-btn">Setujui</button>
                                            </form>
                                            <form method="post" action="">
                                                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="reject-btn">Tolak</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state" id="emptyState" style="display: block;">
                        <i class="fas fa-calendar-times"></i>
                        <h3>Belum Ada Pemesanan</h3>
                        <p>Belum ada pemesanan ruang yang tercatat dalam sistem.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
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
</script>


    <script>
        // Search functionality
        document.getElementById('searchBar').addEventListener('keyup', function() {
            let searchQuery = this.value.toLowerCase();
            let cards = document.querySelectorAll('.card');
            let emptyState = document.getElementById('emptyState');
            let visibleCards = 0;
            
            cards.forEach(function(card) {
                let roomName = card.dataset.room.toLowerCase();
                let eskulName = card.dataset.eskul.toLowerCase();
                
                if (roomName.includes(searchQuery) || eskulName.includes(searchQuery)) {
                    card.style.display = 'flex';
                    visibleCards++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            if (visibleCards === 0 && cards.length > 0) {
                emptyState.style.display = 'block';
                emptyState.querySelector('h3').textContent = 'Tidak Ada Hasil';
                emptyState.querySelector('p').textContent = 'Tidak ada data yang sesuai dengan pencarian Anda.';
            } else {
                emptyState.style.display = 'none';
            }
        });
    </script>
</body>
</html>
