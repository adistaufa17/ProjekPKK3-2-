<?php
session_start();
require_once 'config/database.php';
require_once 'notifications.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get hari parameter
$hari = isset($_GET['hari']) ? $_GET['hari'] : 'senin';
$hari = strtolower($hari);

// Validate hari parameter
$valid_days = ['senin', 'selasa', 'rabu', 'kamis', 'jumat'];
if (!in_array($hari, $valid_days)) {
    $hari = 'senin';
}

// Get buildings with rooms
$stmt = $db->query("SELECT * FROM buildings ORDER BY id ASC");
$buildings = $stmt->fetchAll();

// Function to check if room is booked
function isRoomBooked($db, $room_id, $hari, $current_date) {
    $stmt = $db->prepare("
        SELECT * FROM bookings 
        WHERE room_id = :room_id 
        AND hari = :hari 
        AND booking_date = :booking_date
        AND status IN ('pending', 'approved')
    ");
    $stmt->execute([
        'room_id' => $room_id, 
        'hari' => $hari,
        'booking_date' => $current_date
    ]);
    return $stmt->fetch();
}

// Dapatkan tanggal hari ini
$current_date = date('Y-m-d');

// Di bagian tampilan ruangan, tambahkan pengecekan waktu
foreach ($rooms as $room):
    $booking = isRoomBooked($db, $room['id'], $hari, $current_date);
    $isBooked = $booking ? true : false;

    // Untuk Workshop, tampilkan waktu booking jika ada
    if ($room['building_id'] == 5) { // ID 5 adalah Workshop
        if ($isBooked) {
            $time_info = " (".date('H:i', strtotime($booking['start_time']))." - ".date('H:i', strtotime($booking['end_time'])).")";
        } else {
            $time_info = " (Tersedia)";
        }
    } else {
        $time_info = "";
    }
    
    $roomClass = $isBooked ? "room booked" : "room available";
    
    if ($isBooked): ?>
        <div class="<?= $roomClass ?>" onclick="showRoomDetails(<?= $room['id'] ?>, '<?= htmlspecialchars($room['nama_ruang']) ?>', '<?= htmlspecialchars(getEkskulName($db, $booking)) ?>', '<?= $booking['status'] ?>', '<?= $booking['start_time'] ?>', '<?= $booking['end_time'] ?>')" title="<?= htmlspecialchars($room['nama_ruang']) ?> - Sudah dipesan oleh <?= htmlspecialchars(getEkskulName($db, $booking)) ?>">
            <?= htmlspecialchars($room['nama_ruang']) ?><?= $time_info ?>
        </div>
    <?php else: ?>
        <div class="<?= $roomClass ?>" onclick="<?= ($room['building_id'] == 5) ? "showTimeSelection(".$room['id'].",'".htmlspecialchars($room['nama_ruang'])."')" : "bookRoom(".$room['id'].",'".htmlspecialchars($room['nama_ruang'])."')" ?>" title="<?= htmlspecialchars($room['nama_ruang']) ?> - Tersedia">
            <?= htmlspecialchars($room['nama_ruang']) ?><?= $time_info ?>
        </div>
    <?php endif;
endforeach;

// Function to get ekstrakurikuler name
function getEkskulName($db, $booking) {
    $stmt = $db->prepare("SELECT nama_ekstrakurikuler FROM users WHERE id = :user_id");
    $stmt->execute(['user_id' => $booking['user_id']]);
    $user = $stmt->fetch();
    return $user ? $user['nama_ekstrakurikuler'] : 'Unknown';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Ruang <?= ucfirst($hari) ?> - Sistem Pemesanan Ruang</title>
    <link rel="stylesheet" href="assets/css/style_booking_ruang.css">
    <link rel="stylesheet" href="assets/css/stylesidebar.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">

    <style>
        :root {
            --primary-color: #1a365d;
            --secondary-color: #2c5282;
            --accent-color: #3182ce;
            --light-bg: #f0f4f8;
            --available-color: #48bb78;
            --booked-color: #e53e3e;
            --warning-color: #ecc94b;
            --text-light: #ffffff;
            --text-dark: #2d3748;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            
            /* Sizing variables for consistent room grids */
            --room-height: 55px;
            --room-gap: 12px;
            --grid-padding: 25px;
        }

        /* Sidebar variables */
        :root {
            --sidebar-color: #1a2b47;
            --sidebar-hover: #2e4064;
            --sidebar-active: #3a5280;
            --text-primary: #fff;
        }
        
        /* Status styling */
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
        }

        /* Mobile responsiveness for room grid */
        .room-row {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: var(--room-gap);
        }

        /* Responsive grid adjustments */
        @media (max-width: 1200px) {
            .room-row {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        @media (max-width: 992px) {
            .room-row {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 768px) {
            .container {
                margin-left: 60px;
                width: calc(100% - 60px);
            }

            .main-content {
                padding: 15px;
            }

            .room-row {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }

            .building-header {
                font-size: 18px;
                padding: 10px;
            }

            .header h1 {
                font-size: 20px;
            }

            .room {
                height: 45px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .room-row {
                grid-template-columns: repeat(2, 1fr);
                gap: 6px;
            }

            .room {
                height: 40px;
                font-size: 12px;
            }

            .building-header {
                font-size: 16px;
                padding: 8px;
            }

            /* Popup improvements */
            .room-details {
                width: 85%;
                padding: 15px;
            }

            .booking-message {
                font-size: 14px;
            }

            .buttons {
                flex-direction: column;
                gap: 10px;
            }

            .button {
                width: 100%;
            }
        }

        /* Fix for extreme small screens */
        @media (max-width: 360px) {
            .container {
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

            .menu-toggle {
                display: block !important;
                position: fixed;
                z-index: 1000;
            }

            .room-row {
                grid-template-columns: repeat(2, 1fr);
                gap: 5px;
            }

            .room {
                height: 38px;
                font-size: 11px;
                padding: 0 5px;
            }

            .header h1 {
                font-size: 18px;
            }
        }

        /* Fix popup display in mobile */
        .room-popup {
            z-index: 2000;
        }

        /* Day navigation for mobile */
        .day-navigation {
            display: none;
            margin-bottom: 15px;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .day-navigation select {
            flex-grow: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .day-navigation button {
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            margin-left: 10px;
            cursor: pointer;
        }

        .time-selection {
        margin: 20px 0;
        }

        .time-input {
        margin-bottom: 15px;
        }

        .time-input label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        }

        .time-input input {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        }

        @media (max-width: 768px) {
            .day-navigation {
                display: flex;
            }
        }
    </style>
</head>
<body>
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
            <li class="menu-item">
                <a href="notifications_page.php">
                    <i class="fas fa-bell"></i> 
                    <span class="menu-text">
                        Notifikasi
                        <?php if (getUnreadNotificationCount($db, $_SESSION['user_id']) > 0): ?>
                            <span class="notification-badge"><?= getUnreadNotificationCount($db, $_SESSION['user_id']) ?></span>
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

    <div class="container">
        <div class="main-content">
            <div class="header">
                <h1>Booking Ruang untuk Hari <?= ucfirst($hari) ?></h1>
            </div>
            
            <!-- Mobile Day Navigation -->
            <div class="day-navigation">
                <select id="daySelect">
                    <option value="senin" <?= $hari == 'senin' ? 'selected' : '' ?>>Senin</option>
                    <option value="selasa" <?= $hari == 'selasa' ? 'selected' : '' ?>>Selasa</option>
                    <option value="rabu" <?= $hari == 'rabu' ? 'selected' : '' ?>>Rabu</option>
                    <option value="kamis" <?= $hari == 'kamis' ? 'selected' : '' ?>>Kamis</option>
                    <option value="jumat" <?= $hari == 'jumat' ? 'selected' : '' ?>>Jumat</option>
                </select>
                <button onclick="changeDayMobile()"><i class="fas fa-check"></i> Pilih</button>
            </div>
            
            <?php foreach ($buildings as $building): ?>
                <!-- Get rooms for this building -->
                <?php 
                $stmt = $db->prepare("SELECT * FROM rooms WHERE building_id = :building_id ORDER BY nama_ruang ASC");
                $stmt->execute(['building_id' => $building['id']]);
                $rooms = $stmt->fetchAll();
                
                // Skip if no rooms
                if (count($rooms) == 0) continue;
                ?>
                
                <div class="building-header"><?= htmlspecialchars($building['nama_gedung']) ?></div>
                <div class="room-grid">
                    <div class="room-row">
                        <?php foreach ($rooms as $room): ?>
                            <?php 
                            $booking = isRoomBooked($db, $room['id'], $hari, $current_date);
                            $isBooked = $booking ? true : false;
                            $roomClass = $isBooked ? "room booked" : "room available";
                            ?>
                            
                            <?php if ($isBooked): ?>
                                <div class="<?= $roomClass ?>" onclick="showRoomDetails(<?= $room['id'] ?>, '<?= htmlspecialchars($room['nama_ruang']) ?>', '<?= htmlspecialchars(getEkskulName($db, $booking)) ?>', '<?= $booking['status'] ?>')" title="<?= htmlspecialchars($room['nama_ruang']) ?> - Sudah dipesan oleh <?= htmlspecialchars(getEkskulName($db, $booking)) ?>">
                                    <?= htmlspecialchars($room['nama_ruang']) ?>
                                </div>
                            <?php else: ?>
                                <div class="<?= $roomClass ?>" onclick="bookRoom(<?= $room['id'] ?>, '<?= htmlspecialchars($room['nama_ruang']) ?>')" title="<?= htmlspecialchars($room['nama_ruang']) ?> - Tersedia">
                                    <?= htmlspecialchars($room['nama_ruang']) ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Room Booking Popup -->
    <div class="room-popup" id="roomPopup">
        <div class="room-details">
            <p class="booking-message">Anda akan memesan <span id="roomName"></span> untuk hari <span><?= ucfirst($hari) ?></span>.</p>
            <div class="buttons">
                <button class="button button-cancel" onclick="cancelBooking()">Batal</button>
                <button class="button button-next" onclick="confirmBooking()">Konfirmasi Booking</button>
            </div>
        </div>
    </div>

    <!-- Room Details Popup -->
    <div class="room-popup" id="roomDetailsPopup" style="display: none;">
        <div class="room-details">
            <h3>Detail Ruangan</h3>
            <div class="room-info" style="margin: 20px 0; line-height: 1.6;">
                <p><strong>Nama Ruang:</strong> <span id="detailRoomName"></span></p>
                <p><strong>Dipesan oleh:</strong> <span id="detailEkskul"></span></p>
                <p><strong>Status:</strong> <span id="detailStatus"></span></p>
            </div>
            <div class="buttons">
                <button class="button button-cancel" onclick="closeRoomDetails()">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Time Selection Popup for Workshop -->
<div class="room-popup" id="timeSelectionPopup" style="display: none;">
    <div class="room-details">
        <h3>Pilih Waktu Booking</h3>
        <div class="time-selection">
            <div class="time-input">
                <label for="startTime">Mulai:</label>
                <input type="time" id="startTime" min="07:00" max="22:00" required>
            </div>
            <div class="time-input">
                <label for="endTime">Selesai:</label>
                <input type="time" id="endTime" min="07:00" max="22:00" required>
            </div>
        </div>
        <div class="buttons">
            <button class="button button-cancel" onclick="cancelTimeSelection()">Batal</button>
            <button class="button button-next" onclick="confirmTimeSelection()">Lanjutkan</button>
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
        // Day navigation for mobile
        function changeDayMobile() {
            const day = document.getElementById('daySelect').value;
            window.location.href = `booking_ruang.php?hari=${day}`;
        }
    
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
                    sidebar.classList.add('active'); // Keep sidebar visible on larger screens
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

            // Touch-friendly improvements for mobile
            const rooms = document.querySelectorAll('.room');
            rooms.forEach(room => {
                room.addEventListener('touchstart', function() {
                    this.classList.add('touch-active');
                });
                room.addEventListener('touchend', function() {
                    this.classList.remove('touch-active');
                });
            });
        });
    
        // Room booking functions
        let selectedRoomId = null;
        
        function bookRoom(roomId, roomName) {
            selectedRoomId = roomId;
            document.getElementById('roomName').textContent = roomName;
            document.getElementById('roomPopup').style.display = 'flex';
        }
        
        function cancelBooking() {
            document.getElementById('roomPopup').style.display = 'none';
        }
        
        function confirmBooking() {
            if (selectedRoomId) {
                window.location.href = `form_booking.php?room_id=${selectedRoomId}&hari=<?= $hari ?>`;
            }
        }
        
        function showRoomDetails(roomId, roomName, ekskul, status) {
            document.getElementById('detailRoomName').textContent = roomName;
            document.getElementById('detailEkskul').textContent = ekskul;
            
            let statusText = '';
            switch(status) {
                case 'pending':
                    statusText = 'Menunggu Konfirmasi';
                    document.getElementById('detailStatus').className = 'status-pending';
                    break;
                case 'approved':
                    statusText = 'Disetujui';
                    document.getElementById('detailStatus').className = 'status-approved';
                    break;
                case 'rejected':
                    statusText = 'Ditolak';
                    document.getElementById('detailStatus').className = 'status-rejected';
                    break;
            }
            
            document.getElementById('detailStatus').textContent = statusText;
            document.getElementById('roomDetailsPopup').style.display = 'flex';
        }
        
        function closeRoomDetails() {
            document.getElementById('roomDetailsPopup').style.display = 'none';
        }


        // Untuk Workshop - Tampilkan form pemilihan waktu
        function showTimeSelection(roomId, roomName) {
        selectedRoomId = roomId;
        selectedRoomName = roomName;
        document.getElementById('timeSelectionPopup').style.display = 'flex';
        }

        function cancelTimeSelection() {
        document.getElementById('timeSelectionPopup').style.display = 'none';
        }   

        function confirmTimeSelection() {
        const startTime = document.getElementById('startTime').value;
        const endTime = document.getElementById('endTime').value;
    
        if (!startTime || !endTime) {
        alert('Harap pilih waktu mulai dan selesai');
        return;
        }
    
        if (startTime >= endTime) {
        alert('Waktu selesai harus setelah waktu mulai');
        return;
        }
    
        // Lanjutkan ke halaman konfirmasi dengan parameter waktu
        window.location.href = `form_booking.php?room_id=${selectedRoomId}&hari=<?= $hari ?>&start_time=${startTime}&end_time=${endTime}`;
        }

    </script>
</body>
</html>
