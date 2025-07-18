<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get room reports
$stmt = $db->query("
    SELECT rr.*, b.hari, r.nama_ruang, bg.nama_gedung, u.nama_ekstrakurikuler
    FROM room_reports rr
    JOIN bookings b ON rr.booking_id = b.id
    JOIN rooms r ON b.room_id = r.id
    JOIN buildings bg ON r.building_id = bg.id
    JOIN users u ON rr.user_id = u.id
    ORDER BY rr.report_time DESC
");
$reports = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kondisi Ruang - Sistem Pemesanan Ruang</title>
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

        .report-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .report-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .report-header {
            display: flex;
            justify-content: space-between;
            background-color: #1a365d;
            color: white;
            padding: 15px;
        }
        
        .report-room {
            font-weight: bold;
            font-size: 18px;
        }
        
        .report-date {
            font-size: 14px;
            text-align: right;
        }
        
        .report-content {
            padding: 15px;
        }
        
        .report-text {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .report-image {
            margin-top: 15px;
        }
        
        .report-image img {
            max-width: 100%;
            max-height: 400px;
            border-radius: 5px;
            border: 1px solid #ddd;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .report-image img:hover {
            opacity: 0.9;
        }
        
        .report-meta {
            color: #666;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            padding: 15px;
            border-top: 1px solid #eee;
        }
        
        .no-reports {
            text-align: center;
            padding: 50px 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .no-reports i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 20px;
            display: block;
        }
        
        /* Sort tabs */
        .sort-tabs {
            display: flex;
            overflow-x: auto;
            background: #fff;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            -webkit-overflow-scrolling: touch; /* For smooth scrolling on iOS */
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
        
        /* Image modal */
        .image-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.85);
        }
        
        .modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90%;
        }
        
        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            color: #fff;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1001;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 60px;
                width: calc(100% - 60px);
            }
            
            .header h1 {
                font-size: 20px;
            }
            
            .content-area {
                padding: 15px 10px;
            }
            
            .report-header {
                flex-direction: column;
            }
            
            .report-date {
                text-align: left;
                margin-top: 5px;
                font-size: 12px;
            }
            
            .report-room {
                font-size: 16px;
            }
            
            .report-content {
                padding: 12px;
            }
            
            .report-meta {
                padding: 12px;
            }
            
            .sort-tab {
                padding: 6px 12px;
                font-size: 13px;
            }
        }
        
        @media (max-width: 480px) {
            .report-header {
                padding: 10px;
            }
            
            .report-content {
                padding: 10px;
            }
            
            .report-text {
                padding: 10px;
                font-size: 14px;
            }
            
            .report-content h3 {
                font-size: 16px;
                margin-bottom: 8px;
            }
        }
        
        /* Extreme small screens */
        @media (max-width: 360px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding-top: 50px;
            }
            
            .sidebar {
                width: 0;
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                width: 200px;
                transform: translateX(0);
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
    </style>
</head>
<body>
    <div class="sidebar">
        <img src="assets/img/logo.png" alt="Logo" class="logo">
        <ul class="menu">
            <li class="menu-item"><a href="beranda.php"><i class="fas fa-home"></i> <span class="menu-text">Beranda</span></a></li>
            <li class="menu-item"><a href="booking_hari.php"><i class="fas fa-calendar-check"></i> <span class="menu-text">Booking Ruang</span></a></li>
            <li class="menu-item"><a href="my_bookings.php"><i class="fas fa-history"></i> <span class="menu-text">Riwayat Booking</span></a></li>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <li class="menu-item"><a href="lapor_ruang.php"><i class="fas fa-clipboard-list"></i> <span class="menu-text">Kelola Booking</span></a></li>
            <li class="menu-item active"><a href="view_reports.php"><i class="fas fa-clipboard-check"></i> <span class="menu-text">Laporan Ruang</span></a></li>
            <?php endif; ?>
            <li class="menu-item"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span class="menu-text">Logout</span></a></li>
        </ul>
    </div>

    <button class="menu-toggle" id="menuToggle" style="display:none;">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="refresh-animation" id="refreshAnimation"></div>
    
    <!-- Image modal -->
    <div id="imageModal" class="image-modal">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Laporan Kondisi Ruang</h1>
        </div>
        <div class="content-area">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-bar" id="searchBar" placeholder="Cari ruangan atau ekstrakurikuler...">
            </div>
            
            <?php if (count($reports) > 0): ?>
                <!-- Sort tabs for mobile -->
                <div class="sort-tabs">
                    <button class="sort-tab active" data-gedung="all">Semua</button>
                    <?php 
                    $buildings = [];
                    foreach ($reports as $report) {
                        if (!in_array($report['nama_gedung'], $buildings)) {
                            $buildings[] = $report['nama_gedung'];
                        }
                    }
                    sort($buildings);
                    foreach ($buildings as $building): 
                    ?>
                    <button class="sort-tab" data-gedung="<?= htmlspecialchars($building) ?>"><?= htmlspecialchars($building) ?></button>
                    <?php endforeach; ?>
                </div>
                
                <div id="reports-container">
                    <?php foreach ($reports as $report): ?>
                        <div class="report-card" data-room="<?= htmlspecialchars($report['nama_ruang']) ?>" data-eskul="<?= htmlspecialchars($report['nama_ekstrakurikuler']) ?>" data-gedung="<?= htmlspecialchars($report['nama_gedung']) ?>">
                            <div class="report-header">
                                <div class="report-room">
                                    <?= htmlspecialchars($report['nama_gedung']) ?> - <?= htmlspecialchars($report['nama_ruang']) ?>
                                </div>
                                <div class="report-date">
                                    <div><i class="fas fa-calendar"></i> <?= ucfirst($report['hari']) ?></div>
                                    <div><i class="fas fa-clock"></i> <?= date('d M Y H:i', strtotime($report['report_time'])) ?></div>
                                </div>
                            </div>
                            <div class="report-content">
                                <h3>Laporan Kondisi Ruang:</h3>
                                <div class="report-text">
                                    <?= nl2br(htmlspecialchars($report['condition_report'])) ?>
                                </div>
                                
                                <?php if (!empty($report['photo_path'])): ?>
                                    <div class="report-image">
                                        <h3>Foto Kondisi Ruang:</h3>
                                        <img src="<?= htmlspecialchars($report['photo_path']) ?>" alt="Foto Kondisi Ruang" onclick="showModal(this.src)">
                                        <div style="text-align: center; font-size: 12px; color: #666; margin-top: 5px;">
                                            <i class="fas fa-search-plus"></i> Klik gambar untuk memperbesar
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="report-meta">
                                <div>
                                    <strong>Dilaporkan oleh:</strong> <?= htmlspecialchars($report['nama_ekstrakurikuler']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-reports" id="noReports">
                    <i class="fas fa-clipboard"></i>
                    <h3>Belum Ada Laporan</h3>
                    <p>Belum ada laporan kondisi ruang yang dikirimkan oleh pengguna.</p>
                </div>
            <?php endif; ?>
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
                    
                    const gedungFilter = this.getAttribute('data-gedung');
                    const cards = document.querySelectorAll('.report-card');
                    
                    cards.forEach(card => {
                        if (gedungFilter === 'all' || card.getAttribute('data-gedung') === gedungFilter) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                    
                    // Check if no cards are visible after filtering
                    let visibleCards = document.querySelectorAll('.report-card[style="display: block;"]');
                    if (visibleCards.length === 0) {
                        document.getElementById('noReports').style.display = 'block';
                        document.getElementById('noReports').querySelector('h3').textContent = 'Tidak Ada Hasil';
                        document.getElementById('noReports').querySelector('p').textContent = 'Tidak ada laporan yang sesuai dengan filter yang dipilih.';
                    } else {
                        document.getElementById('noReports').style.display = 'none';
                    }
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
        
        // Search functionality
        document.getElementById('searchBar').addEventListener('keyup', function() {
            let searchQuery = this.value.toLowerCase();
            let reportCards = document.querySelectorAll('.report-card');
            let noReports = document.getElementById('noReports');
            let visibleCards = 0;
            
            reportCards.forEach(function(card) {
                let roomName = card.dataset.room.toLowerCase();
                let eskulName = card.dataset.eskul.toLowerCase();
                let gedungName = card.dataset.gedung.toLowerCase();
                
                if (roomName.includes(searchQuery) || eskulName.includes(searchQuery) || gedungName.includes(searchQuery)) {
                    card.style.display = 'block';
                    visibleCards++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            if (visibleCards === 0 && reportCards.length > 0) {
                noReports.style.display = 'block';
                noReports.querySelector('h3').textContent = 'Tidak Ada Hasil';
                noReports.querySelector('p').textContent = 'Tidak ada laporan yang sesuai dengan pencarian Anda.';
            } else if (reportCards.length > 0) {
                noReports.style.display = 'none';
            }
        });
        
        // Image modal functionality
        function showModal(src) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            modalImg.src = src;
            
            // Prevent scrolling when modal is open
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Close modal when clicking outside the image
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
