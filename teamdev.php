<?php
session_start();
require_once 'config/database.php';
require_once 'notifications.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Our Incredible Team</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="assets/css/stylesidebar.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body class=" text-gray-800" style="background-color: #1a2b47;">
<div class="sidebar">
    <img src="assets/img/logo.png" alt="Logo" class="logo">
    <ul class="menu">
        <li class="menu-item"><a href="beranda.php"><i class="fas fa-home"></i> Beranda</a></li>
        <li class="menu-item"><a href="booking_hari.php"><i class="fas fa-calendar-check"></i> Booking Ruang</a></li>
        <li class="menu-item"><a href="my_bookings.php"><i class="fas fa-history"></i> Riwayat Booking</a></li>
        <li class="menu-item"><a href="teamdev.php"><i class="fas fa-home"></i> Team Developer</a></li>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <li class="menu-item"><a href="lapor_ruang.php"><i class="fas fa-clipboard-list"></i> Kelola Booking</a></li>
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
  <div class="container mx-auto py-12 px-4">
    <h1 class="text-4xl font-bold text-center mb-12 border-b-4 border-white-600" style="color: wheat;">OUR INCREDIBLE TEAMS</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

      <!-- Leadership -->
      <div class="bg-white rounded-xl shadow p-6 text-center">
        <h2 class="text-2xl font-semibold text-blue-700 border-b-2 border-green-400 pb-2 mb-6">Leadership</h2>
        <img src="https://via.placeholder.com/150" alt="Ezar" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
        <h3 class="text-xl font-semibold">Ezar</h3>
        <p class="text-sm text-gray-500">Team Leader</p>
        <p class="italic text-gray-600 mt-2">"Lead with purpose."</p>
        <a href="https://instagram.com/ezar" target="_blank" class="mt-3 inline-block text-pink-500 hover:text-pink-700">
          <i class="fab fa-instagram fa-lg"></i> @ezar
        </a>
      </div>

      <!-- Product Management -->
      <div class="bg-white rounded-xl shadow p-6 text-center">
        <h2 class="text-2xl font-semibold text-blue-700 border-b-2 border-green-400 pb-2 mb-6">Product Management</h2>
        <img src="https://via.placeholder.com/150" alt="Felix" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
        <h3 class="text-xl font-semibold">Felix</h3>
        <p class="text-sm text-gray-500">Product Manager</p>
        <p class="italic text-gray-600 mt-2">"Selalu belajar, selalu tumbuh."</p>
        <a href="https://instagram.com/felix_ig" target="_blank" class="mt-3 inline-block text-pink-500 hover:text-pink-700">
          <i class="fab fa-instagram fa-lg"></i> @felix_ig
        </a>
      </div>

      <!-- Software Development -->
      <div class="bg-white rounded-xl shadow p-6 text-center">
        <h2 class="text-2xl font-semibold text-blue-700 border-b-2 border-green-400 pb-2 mb-6">Software Development</h2>
        <img src="https://via.placeholder.com/150" alt="Adista" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
        <h3 class="text-xl font-semibold">Adista</h3>
        <p class="text-sm text-gray-500">Software Developer</p>
        <p class="italic text-gray-600 mt-2">"Code. Coffee. Repeat."</p>
        <a href="https://instagram.com/adista" target="_blank" class="mt-3 inline-block text-pink-500 hover:text-pink-700">
          <i class="fab fa-instagram fa-lg"></i> @adista
        </a>
      </div>

      <div class="bg-white rounded-xl shadow p-6 text-center">
        <h2 class="text-2xl font-semibold text-blue-700 border-b-2 border-green-400 pb-2 mb-6">Software Development</h2>
        <img src="https://via.placeholder.com/150" alt="Akmal" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
        <h3 class="text-xl font-semibold">Akmal</h3>
        <p class="text-sm text-gray-500">Software Developer</p>
        <p class="italic text-gray-600 mt-2">"Build. Break. Fix."</p>
        <a href="https://instagram.com/akmal" target="_blank" class="mt-3 inline-block text-pink-500 hover:text-pink-700">
          <i class="fab fa-instagram fa-lg"></i> @akmal
        </a>
      </div>

      <div class="bg-white rounded-xl shadow p-6 text-center">
        <h2 class="text-2xl font-semibold text-blue-700 border-b-2 border-green-400 pb-2 mb-6">Software Development</h2>
        <img src="https://via.placeholder.com/150" alt="Shera" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
        <h3 class="text-xl font-semibold">Shera</h3>
        <p class="text-sm text-gray-500">Software Developer</p>
        <p class="italic text-gray-600 mt-2">"Code like a girl."</p>
        <a href="https://instagram.com/shera" target="_blank" class="mt-3 inline-block text-pink-500 hover:text-pink-700">
          <i class="fab fa-instagram fa-lg"></i> @shera
        </a>
      </div>

      <!-- Design -->
      <div class="bg-white rounded-xl shadow p-6 text-center">
        <h2 class="text-2xl font-semibold text-blue-700 border-b-2 border-green-400 pb-2 mb-6">Design</h2>
        <img src="https://via.placeholder.com/150" alt="Royan" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
        <h3 class="text-xl font-semibold">Royan</h3>
        <p class="text-sm text-gray-500">UI/UX Designer</p>
        <p class="italic text-gray-600 mt-2">"Design with heart."</p>
        <a href="https://instagram.com/royan" target="_blank" class="mt-3 inline-block text-pink-500 hover:text-pink-700">
          <i class="fab fa-instagram fa-lg"></i> @royan
        </a>
      </div>

      <div class="bg-white rounded-xl shadow p-6 text-center">
        <h2 class="text-2xl font-semibold text-blue-700 border-b-2 border-green-400 pb-2 mb-6">Design</h2>
        <img src="https://via.placeholder.com/150" alt="Jelita" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
        <h3 class="text-xl font-semibold">Jelita</h3>
        <p class="text-sm text-gray-500">UI/UX Designer</p>
        <p class="italic text-gray-600 mt-2">"User first, always."</p>
        <a href="https://instagram.com/jelita" target="_blank" class="mt-3 inline-block text-pink-500 hover:text-pink-700">
          <i class="fab fa-instagram fa-lg"></i> @jelita
        </a>
      </div>

      <div class="bg-white rounded-xl shadow p-6 text-center">
        <h2 class="text-2xl font-semibold text-blue-700 border-b-2 border-green-400 pb-2 mb-6">Design</h2>
        <img src="https://via.placeholder.com/150" alt="Mufi" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
        <h3 class="text-xl font-semibold">Mufi</h3>
        <p class="text-sm text-gray-500">UI/UX Designer</p>
        <p class="italic text-gray-600 mt-2">"Simplicity is key."</p>
        <a href="https://instagram.com/mufi" target="_blank" class="mt-3 inline-block text-pink-500 hover:text-pink-700">
          <i class="fab fa-instagram fa-lg"></i> @mufi
        </a>
      </div>

      <!-- Data Analysis -->
      <div class="bg-white rounded-xl shadow p-6 text-center">
        <h2 class="text-2xl font-semibold text-blue-700 border-b-2 border-green-400 pb-2 mb-6">Data Analysis</h2>
        <img src="https://via.placeholder.com/150" alt="Maid" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
        <h3 class="text-xl font-semibold">Maid</h3>
        <p class="text-sm text-gray-500">Data Analyst</p>
        <p class="italic text-gray-600 mt-2">"Data tells the truth."</p>
        <a href="https://instagram.com/maid" target="_blank" class="mt-3 inline-block text-pink-500 hover:text-pink-700">
          <i class="fab fa-instagram fa-lg"></i> @maid
        </a>
      </div>

      <div class="bg-white rounded-xl shadow p-6 text-center">
        <h2 class="text-2xl font-semibold text-blue-700 border-b-2 border-green-400 pb-2 mb-6">Data Analysis</h2>
        <img src="https://via.placeholder.com/150" alt="Nisfu" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
        <h3 class="text-xl font-semibold">Nisfu</h3>
        <p class="text-sm text-gray-500">Data Analyst</p>
        <p class="italic text-gray-600 mt-2">"Numbers don't lie."</p>
        <a href="https://instagram.com/nisfu" target="_blank" class="mt-3 inline-block text-pink-500 hover:text-pink-700">
          <i class="fab fa-instagram fa-lg"></i> @nisfu
        </a>
      </div>

      <!-- Marketing -->
      <div class="bg-white rounded-xl shadow p-6 text-center">
        <h2 class="text-2xl font-semibold text-blue-700 border-b-2 border-green-400 pb-2 mb-6">Marketing</h2>
        <img src="https://via.placeholder.com/150" alt="Yuki" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
        <h3 class="text-xl font-semibold">Yuki</h3>
        <p class="text-sm text-gray-500">Marketing</p>
        <p class="italic text-gray-600 mt-2">"Connect with the crowd."</p>
        <a href="https://instagram.com/yuki" target="_blank" class="mt-3 inline-block text-pink-500 hover:text-pink-700">
          <i class="fab fa-instagram fa-lg"></i> @yuki
        </a>
      </div>

      <div class="bg-white rounded-xl shadow p-6 text-center">
        <h2 class="text-2xl font-semibold text-blue-700 border-b-2 border-green-400 pb-2 mb-6">Marketing</h2>
        <img src="https://via.placeholder.com/150" alt="Revaldo" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
        <h3 class="text-xl font-semibold">Revaldo</h3>
        <p class="text-sm text-gray-500">Marketing</p>
        <p class="italic text-gray-600 mt-2">"Sell with soul."</p>
        <a href="https://instagram.com/revaldo" target="_blank" class="mt-3 inline-block text-pink-500 hover:text-pink-700">
          <i class="fab fa-instagram fa-lg"></i> @revaldo
        </a>
      </div>

    </div>
  </div>
</body>
</html>
