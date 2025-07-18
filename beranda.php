<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - Sistem Pemesanan Ruang</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/stylesidebar.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .main-container {
            margin-left: 280px;
            width: calc(100% - 280px);
            overflow-y: auto;
            height: 100vh;
        }
        
        /* Hero section */
        .hero {
            background-image: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('assets/img/fotompk.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            min-height: 400px;
            display: flex;
            align-items: center;
            padding: 60px 20px;
        }
        
        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .hero-text {
            max-width: 600px;
            margin-bottom: 20px;
        }
        
        .hero-text h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: #3498db;
        }
        
        .hero-text p {
            font-size: 1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btnBookingRuang {
            display: inline-block;
            background-color: #3498db;
            color: #fff;
            padding: 10px 30px;
            border-radius: 30px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .btnBookingRuang:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .hero-logo img {
            width: 150px;
            height: 150px;
        }
        
        /* Latar Belakang */
        .latar-belakang {
            padding: 60px 20px;
            background-color: #f9f9f9;
        }
        
        .section-header {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .section-header h2 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 10px;
        }
        
        .line {
            height: 3px;
            width: 100px;
            background-color: #3498db;
            margin: 0 auto;
        }
        
        .latar-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .latar-content p {
            font-size: 1rem;
            line-height: 1.8;
            color: #666;
        }
        
        /* Footer */
        footer {
            background-color: #333;
            color: white;
            padding: 50px 20px;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        .footer-column {
            flex: 1;
            min-width: 250px;
            margin-bottom: 20px;
            padding: 0 15px;
        }
        
        .footer-column h3 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .footer-column p {
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .contact-info {
            list-style: none;
            padding: 0;
        }
        
        .contact-info li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .contact-info li i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .main-container {
                margin-left: 60px;
                width: calc(100% - 60px);
            }
            
            .hero-text h2 {
                font-size: 2rem;
            }
            
            .hero-content {
                flex-direction: column;
                text-align: center;
            }
            
            .hero-logo {
                margin-top: 20px;
            }
            
            .hero-logo img {
                width: 120px;
                height: 120px;
            }
            
            .btnBookingRuang {
                margin-bottom: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .hero-text h2 {
                font-size: 1.8rem;
            }
            
            .section-header h2 {
                font-size: 1.6rem;
            }
            
            .footer-column {
                flex: 100%;
                margin-right: 0;
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
            <li class="menu-item active"><a href="beranda.php"><i class="fas fa-home"></i> <span class="menu-text">Beranda</span></a></li>
            <li class="menu-item"><a href="booking_hari.php"><i class="fas fa-calendar-check"></i> <span class="menu-text">Booking Ruang</span></a></li>
            <li class="menu-item"><a href="my_bookings.php"><i class="fas fa-history"></i> <span class="menu-text">Riwayat Booking</span></a></li>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <li class="menu-item"><a href="lapor_ruang.php"><i class="fas fa-clipboard-list"></i> <span class="menu-text">Kelola Booking</span></a></li>
            <li class="menu-item"><a href="view_reports.php"><i class="fas fa-clipboard-check"></i> <span class="menu-text">Laporan Ruang</span></a></li>
            <?php endif; ?>
            <li class="menu-item"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span class="menu-text">Logout</span></a></li>
        </ul>
    </div>

    <div class="main-container">
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <div class="hero-text">
                    <h2>Sistem Pemesanan<br>Ruang Ekstrakurikuler</h2>
                    <p>Selamat datang, <?= $_SESSION['nama_ekstrakurikuler'] ?>! Platform ini akan membantu pengelolaan dalam pemesanan ruang kelas yang digunakan untuk keperluan ekstrakulikuler sekolah.</p>
                    <div>
                        <a href="booking_hari.php" class="btnBookingRuang">Booking Ruang</a>
                        <a href="my_bookings.php" class="btnBookingRuang" style="background-color: #2c3e50;">Riwayat Booking</a>
                    </div>
                </div>

                <div class="hero-logo">
                    <img src="assets/img/logo.png" alt="Logo Sekolah">
                </div>
            </div>
        </section>

        <!-- Latar Belakang Section -->
        <section class="latar-belakang">
            <div class="section-header">
                <h2>Tentang Sistem Ini</h2>
                <div class="line"></div>
            </div>
            <div class="latar-content">
                <p>
                    Sistem Pemesanan Ruang ini dibuat untuk memudahkan manajemen penggunaan ruangan oleh 27 ekstrakurikuler yang ada di sekolah kita. Dengan sistem ini, diharapkan tidak akan ada lagi konflik dalam penggunaan ruangan untuk kegiatan ekstrakurikuler.
                </p>
            </div>
        </section>

        <!-- Footer Section -->
        <footer>
            <div class="footer-content">
                <div class="footer-column">
                    <h3>Sistem Pemesanan Ruang</h3>
                    <p>Dikembangkan untuk memudahkan pengelolaan ruangan yang digunakan oleh ekstrakurikuler di sekolah kita. Dengan sistem ini, konflik penggunaan ruangan dapat diminimalisir.</p>
                </div>
                <div class="footer-column">
                    <h3>Hubungi Admin</h3>
                    <ul class="contact-info">
                        <li><i class="fa fa-envelope"></i> admin@sekolah.sch.id</li>
                        <li><i class="fa fa-phone"></i> +62812345678</li>
                    </ul>
                </div>
            </div>
        </footer>
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
</body>
</html>
