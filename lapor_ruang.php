<?php
session_start();
require_once 'config/database.php';
require_once 'notifications.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
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

    // Redirect untuk refresh
    header("Location: lapor_ruang.php");
    exit();
}

// Get all bookings
$sql = "
    SELECT b.*, r.nama_ruang, bg.nama_gedung, u.nama_ekstrakurikuler, u.email as user_email
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN buildings bg ON r.building_id = bg.id
    JOIN users u ON b.user_id = u.id
    ORDER BY (b.status = 'pending') DESC, b.hari, r.nama_ruang
";

$stmt = $db->query($sql); // <- INI WAJIB
$bookings = $stmt->fetchAll();

// Map status ke teks bahasa Indonesia
$statusMap = [
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
    <title>Kelola Booking - Sistem Pemesanan Ruang</title>
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
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .action-buttons button {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .approve-btn {
            background-color: #28a745;
            color: white;
        }
        .approve-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }
        .reject-btn {
            background-color: #dc3545;
            color: white;
        }
        .reject-btn:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }
        .alert {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 500;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .filters {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            background-color: white;
            padding: 12px 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filter-label {
            margin-right: 10px;
            font-weight: bold;
            color: #333;
        }
        .filter-options {
            display: flex;
            gap: 10px;
        }
        .filter-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            background-color: #f0f0f0;
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-btn:hover, .filter-btn.active {
            background-color: #3498db;
            color: white;
        }
        .badge {
            display: inline-block;
            min-width: 20px;
            padding: 3px 7px;
            font-size: 12px;
            font-weight: bold;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            background-color: #777;
            border-radius: 10px;
            margin-left: 5px;
        }
        .badge-pending {
            background-color: #856404;
        }
        .card-date {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-options {
                margin-top: 10px;
                width: 100%;
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 10px;
            }
            
            .card-content {
                padding: 10px;
            }
            
            .action-buttons {
                margin-top: 10px;
                flex-direction: column;
                width: 100%;
            }
            
            .action-buttons button {
                width: 100%;
                margin-bottom: 5px;
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
            <li class="menu-item "><a href="my_bookings.php"><i class="fas fa-history"></i> <span class="menu-text">Riwayat Booking</span></a></li>
            <li class="menu-item"><a href="teamdev.php"><i class="fas fa-users"></i><span class="menu-text">Team Developer</span></a></li>     
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <li class="menu-item  active"><a href="lapor_ruang.php"><i class="fas fa-clipboard-list"></i> <span class="menu-text">Kelola Booking</span></a></li>
            <li class="menu-item"><a href="view_reports.php"><i class="fas fa-clipboard-check"></i> <span class="menu-text">Laporan Ruang</span></a></li>
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


    <div class="main-content">
        <div class="header">
            <h1>Kelola Pemesanan Ruang</h1>
        </div>
        <div class="content-area">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success'] ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-bar" id="searchBar" placeholder="Cari ruangan atau ekstrakurikuler...">
            </div>

            <div class="filters">
                <span class="filter-label">Filter:</span>
                <div class="filter-options">
                    <button class="filter-btn active" data-filter="all">Semua</button>
                    <button class="filter-btn" data-filter="pending">Menunggu 
                        <?php 
                        $pendingCount = 0;
                        foreach ($bookings as $booking) {
                            if ($booking['status'] === 'pending') $pendingCount++;
                        }
                        if ($pendingCount > 0):
                        ?>
                        <span class="badge badge-pending"><?= $pendingCount ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="filter-btn" data-filter="approved">Disetujui</button>
                    <button class="filter-btn" data-filter="rejected">Ditolak</button>
                </div>
            </div>
            
            <div class="cards-container" id="cardsContainer">
                <?php if (count($bookings) > 0): ?>
                    <?php foreach ($bookings as $booking): ?>
                        <?php 
                        $status = $booking['status'] ?? 'unknown';
                        $statusClass = 'status-' . $status;
                        $statusText = $statusMap[$status] ?? ucfirst($status);
                        ?>

                        <div class="card" data-room="<?= htmlspecialchars($booking['nama_ruang']) ?>" 
                        data-eskul="<?= htmlspecialchars($booking['nama_ekstrakurikuler']) ?>"
                        data-status="<?= $status ?>">
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
                                    <span><?= htmlspecialchars($booking['nama_ekstrakurikuler']) ?></span>
                                    <?php if ($booking['user_email']): ?>
                                        <div style="font-size: 12px; color: #666;">
                                            <i class="fas fa-envelope"></i> <?= htmlspecialchars($booking['user_email']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-details">
<div>
        <span><i class="fas fa-calendar"></i> <?= ucfirst($booking['hari']) ?></span>
        <div class="card-date"><i class="fas fa-clock"></i> <?= date('d-m-Y H:i', strtotime($booking['created_at'])) ?></div>
    </div>

    <?php if ($booking['status'] === 'pending'): ?>
        <!-- Tombol untuk admin menyetujui atau menolak booking -->
        <div class="action-buttons">
            <form method="post" action="process_booking.php">
                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="approve-btn" onclick="return confirm('Yakin menyetujui booking ini?')">
                    <i class="fas fa-check"></i> Setujui
                </button>
            </form>
            <form method="post" action="process_booking.php">
                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                <input type="hidden" name="action" value="reject">
                <button type="submit" class="reject-btn" onclick="return confirm('Yakin menolak booking ini?')">
                    <i class="fas fa-times"></i> Tolak
                </button>
            </form>
        </div>
    <?php elseif ($booking['status'] === 'approved'): ?>
        <!-- Tombol untuk admin membatalkan booking -->
        <div class="action-buttons">
            <form method="post" action="process_cancel.php" onsubmit="return confirm('Yakin ingin membatalkan booking ini?');">
                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                <button type="submit" name="action" value="approve_cancel" class="approve-btn">
                    <i class="fas fa-check"></i> Batalkan Booking
                </button>
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

                <div class="empty-state" id="noSearchResults" style="display: none;">
                    <i class="fas fa-search"></i>
                    <h3>Tidak Ada Hasil</h3>
                    <p>Tidak ada data yang sesuai dengan kriteria pencarian.</p>
                </div>
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
</script>


    <script>
        // Search functionality
        document.getElementById('searchBar').addEventListener('keyup', function() {
            filterCards();
        });

        // Filter buttons
        const filterButtons = document.querySelectorAll('.filter-btn');
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                filterCards();
            });
        });

        function filterCards() {
            const searchQuery = document.getElementById('searchBar').value.toLowerCase();
            const activeFilter = document.querySelector('.filter-btn.active').dataset.filter;
            
            const cards = document.querySelectorAll('.card');
            let visibleCards = 0;
            
            cards.forEach(function(card) {
                const roomName = card.dataset.room.toLowerCase();
                const eskulName = card.dataset.eskul.toLowerCase();
                const status = card.dataset.status;
                
                const matchesSearch = roomName.includes(searchQuery) || eskulName.includes(searchQuery);
                const matchesFilter = activeFilter === 'all' || status === activeFilter;
                
                if (matchesSearch && matchesFilter) {
                    card.style.display = 'flex';
                    visibleCards++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show/hide empty state
            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('noSearchResults').style.display = (visibleCards === 0 && cards.length > 0) ? 'block' : 'none';
        }


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
</body>
</html>
