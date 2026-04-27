<?php
require_once __DIR__ . '/../config/database.php';
requireAdminLogin();

// ============ QUERY STATISTIK DASHBOARD ============

// Total User (semua pengguna)
$totalUser = $pdo->query("SELECT COUNT(*) FROM pengguna")->fetchColumn();

// Total Freelancer
$totalFreelancer = $pdo->query("SELECT COUNT(*) FROM pengguna WHERE id_role = 3")->fetchColumn();

// Total Booking
$totalBooking = $pdo->query("SELECT COUNT(*) FROM booking")->fetchColumn();

// Booking Selesai
$totalSelesai = $pdo->query("SELECT COUNT(*) FROM booking WHERE status_booking = 'SELESAI'")->fetchColumn();

// 5 Booking Terbaru
$stmtBooking = $pdo->query("
    SELECT b.*, p.nama_pengguna AS nama_client, j.nama_jasa
    FROM booking b
    LEFT JOIN pengguna p ON b.id_pengguna = p.id_pengguna
    LEFT JOIN layanan l ON b.id_layanan = l.id_layanan
    LEFT JOIN jasa j ON l.id_jasa = j.id_jasa
    ORDER BY b.tanggal_booking DESC
    LIMIT 5
");
$bookingTerbaru = $stmtBooking->fetchAll();

// Kategori Terpopuler (berdasarkan jumlah freelancer per kategori)
$stmtKategori = $pdo->query("
    SELECT k.nama_kategori, COUNT(DISTINCT l.id_pengguna) AS total
    FROM kategori k
    LEFT JOIN jasa j ON k.id_kategori = j.id_kategori
    LEFT JOIN layanan l ON j.id_jasa = l.id_jasa
    GROUP BY k.id_kategori, k.nama_kategori
    ORDER BY total DESC
    LIMIT 3
");
$kategoriPopuler = $stmtKategori->fetchAll();
$maxKategori = !empty($kategoriPopuler) ? max(array_column($kategoriPopuler, 'total')) : 1;

// Pengajuan/Verifikasi Terbaru
$stmtPengajuan = $pdo->query("
    SELECT pf.*, p.nama_pengguna, p.no_telp
    FROM pengajuan_freelancer pf
    JOIN pengguna p ON pf.id_pengguna = p.id_pengguna
    WHERE pf.status = 'MENUNGGU'
    ORDER BY pf.tanggal_pengajuan DESC
    LIMIT 5
");
$newPengajuan = $stmtPengajuan->fetchAll();

// Status badge helper
function getStatusBadge($status) {
    switch ($status) {
        case 'MENUNGGU':
            return 'bg-gray-100 text-gray-600 border-gray-200';
        case 'DIPROSES':
            return 'bg-yellow-50 text-yellow-600 border-yellow-200';
        case 'SELESAI':
            return 'bg-green-50 text-green-600 border-green-200';
        case 'DIBATALKAN':
            return 'bg-red-50 text-red-600 border-red-200';
        default:
            return 'bg-gray-100 text-gray-600 border-gray-200';
    }
}

function getStatusLabel($status) {
    switch ($status) {
        case 'MENUNGGU': return 'Menunggu';
        case 'DIPROSES': return 'Diproses';
        case 'SELESAI': return 'Selesai';
        case 'DIBATALKAN': return 'Dibatalkan';
        default: return $status;
    }
}

$adminNama = $_SESSION['admin_nama'] ?? 'Admin';
$currentPage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | WorkLance</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/WorkLance/src/output.css">
</head>
<body class="bg-gray-50 font-sans text-gray-800 h-screen flex overflow-hidden">

  <!-- Sidebar -->
  <aside class="w-72 bg-dark text-white flex flex-col hidden md:flex flex-shrink-0 z-20">
    <!-- Logo -->
    <div class="h-20 flex items-center px-8 border-b border-white/10 shrink-0">
      <a href="../index.html" class="flex items-center gap-2 group">
        <div class="w-10 h-10 bg-white text-dark rounded-xl flex items-center justify-center font-bold text-xl group-hover:scale-105 transition-transform duration-300 shadow-md">W</div>
        <span class="text-2xl font-bold tracking-tight">Work<span class="text-accent">Lance</span></span>
      </a>
      <span class="ml-2 px-2 py-0.5 text-[10px] font-bold bg-accent rounded-md uppercase tracking-wide">Admin</span>
    </div>

    <!-- Nav Links -->
    <div class="flex-1 overflow-y-auto py-6 px-4 space-y-1.5 scrollbar-hide">
      <div class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-4">Utama</div>
      <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 <?= $currentPage === 'dashboard' ? 'bg-white/10 text-white border border-white/5' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?> rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5 <?= $currentPage === 'dashboard' ? 'text-primary' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
        </svg>
        Dashboard
      </a>
      <a href="pengguna.php" class="flex items-center gap-3 px-4 py-3 <?= $currentPage === 'pengguna' ? 'bg-white/10 text-white border border-white/5' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?> rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
        </svg>
        Pengguna
      </a>
      <a href="freelancer.php" class="flex items-center gap-3 px-4 py-3 <?= $currentPage === 'freelancer' ? 'bg-white/10 text-white border border-white/5' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?> rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
        </svg>
        Freelancer
      </a>
      <a href="booking.php" class="flex items-center gap-3 px-4 py-3 <?= $currentPage === 'booking' ? 'bg-white/10 text-white border border-white/5' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?> rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
        </svg>
        Booking
      </a>
      <a href="pengajuan.php" class="flex items-center gap-3 px-4 py-3 <?= $currentPage === 'verifikasi' ? 'bg-white/10 text-white border border-white/5' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?> rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
        Pengajuan
      </a>

      <div class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-8">Sistem</div>
      <a href="kelola.php" class="flex items-center gap-3 px-4 py-3 <?= $currentPage === 'kelola' ? 'bg-white/10 text-white border border-white/5' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?> rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
        </svg>
        Kelola
      </a>
    </div>

    <!-- Bottom Action -->
    <div class="p-4 border-t border-white/10 shrink-0">
      <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-400 hover:bg-red-500/10 hover:text-red-300 rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
        </svg>
        Logout
      </a>
    </div>
  </aside>

  <!-- Main View -->
  <main class="flex-1 flex flex-col min-w-0 bg-gray-50/50 relative overflow-x-hidden">
    <!-- Abstract Background Decor -->
    <div class="absolute top-0 right-0 w-96 h-96 bg-primary/10 rounded-full blur-3xl -z-10 -translate-y-1/2 translate-x-1/2"></div>

    <!-- Top Header -->
    <header class="h-20 bg-white/80 backdrop-blur-md border-b border-gray-100 flex items-center justify-between px-6 lg:px-10 sticky top-0 z-30 shrink-0">
      <!-- Mobile menu button -->
      <button class="md:hidden p-2 text-gray-500 hover:text-dark hover:bg-gray-100 rounded-xl">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      </button>

      <!-- Global Search -->
      <div class="hidden sm:flex flex-1 max-w-lg items-center relative">
        <svg class="w-5 h-5 text-gray-400 absolute left-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
        <input type="text" placeholder="Cari booking, freelancer, atau pengguna..."
          class="w-full bg-gray-100/50 border border-transparent focus:border-gray-200 hover:bg-gray-100 rounded-full pl-12 pr-4 py-2.5 text-sm outline-none transition-all placeholder-gray-400 text-dark">
      </div>

      <!-- Right Nav -->
      <div class="flex items-center gap-3 md:gap-5 ml-auto">
        <!-- Notification -->
        <button class="relative p-2.5 text-gray-500 hover:text-dark hover:bg-gray-100 rounded-full transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
          </svg>
        </button>

        <div class="h-6 w-px bg-gray-200 hidden sm:block"></div>

        <!-- Profile Dropdown -->
        <div class="flex items-center gap-3 cursor-pointer group">
          <div class="w-10 h-10 rounded-full bg-accent text-white flex items-center justify-center font-bold text-sm ring-2 ring-gray-100 group-hover:ring-primary transition-all">
            <?= getInitials($adminNama) ?>
          </div>
          <div class="hidden md:block text-sm">
            <p class="font-bold text-dark leading-tight"><?= htmlspecialchars($adminNama) ?></p>
            <p class="text-gray-500 text-xs font-medium">Super Admin</p>
          </div>
          <svg class="w-4 h-4 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </div>
      </div>
    </header>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto p-6 lg:p-10 pb-20">
      <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-dark mb-1">Dashboard</h1>
          <p class="text-gray-500">Selamat datang kembali, <?= htmlspecialchars($adminNama) ?>. Pantau aktivitas terbaru hari ini.</p>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <!-- Stat 1: Total User -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col">
          <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
              </svg>
            </div>
          </div>
          <h3 class="text-gray-500 text-sm font-medium mb-1">Total User</h3>
          <p class="text-3xl font-bold text-dark"><?= number_format($totalUser, 0, ',', '.') ?></p>
        </div>

        <!-- Stat 2: Total Freelancer -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col">
          <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-orange-50 text-accent rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
            </div>
          </div>
          <h3 class="text-gray-500 text-sm font-medium mb-1">Total Freelancer</h3>
          <p class="text-3xl font-bold text-dark"><?= number_format($totalFreelancer, 0, ',', '.') ?></p>
        </div>

        <!-- Stat 3: Total Booking -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col">
          <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
              </svg>
            </div>
          </div>
          <h3 class="text-gray-500 text-sm font-medium mb-1">Total Booking</h3>
          <p class="text-3xl font-bold text-dark"><?= number_format($totalBooking, 0, ',', '.') ?></p>
        </div>

        <!-- Stat 4: Booking Selesai -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col">
          <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
          <h3 class="text-gray-500 text-sm font-medium mb-1">Booking Selesai</h3>
          <p class="text-3xl font-bold text-dark"><?= number_format($totalSelesai, 0, ',', '.') ?></p>
        </div>
      </div>

      <!-- Main Columns -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Activity Table -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-dark text-lg">Booking Terbaru</h3>
            <a href="booking.php" class="text-accent text-sm font-bold hover:underline">Lihat Semua</a>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
              <thead>
                <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider">
                  <th class="p-4 pl-6 font-semibold">Nama Client</th>
                  <th class="p-4 font-semibold">Jasa</th>
                  <th class="p-4 font-semibold">Tanggal</th>
                  <th class="p-4 font-semibold pr-6 text-right">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <?php if (empty($bookingTerbaru)): ?>
                <tr>
                  <td colspan="4" class="p-8 text-center text-gray-400">Belum ada data booking.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($bookingTerbaru as $bk): ?>
                <tr class="hover:bg-gray-50/50 transition-colors">
                  <td class="p-4 pl-6">
                    <p class="font-bold text-dark text-sm whitespace-nowrap"><?= htmlspecialchars($bk['nama_client'] ?? '-') ?></p>
                  </td>
                  <td class="p-4">
                    <p class="text-sm text-gray-600 whitespace-nowrap"><?= htmlspecialchars($bk['nama_jasa'] ?? '-') ?></p>
                  </td>
                  <td class="p-4 text-sm text-gray-500 whitespace-nowrap">
                    <?= $bk['tanggal_booking'] ? formatTanggal($bk['tanggal_booking']) : '-' ?>
                  </td>
                  <td class="p-4 pr-6 text-right">
                    <span class="px-3 py-1 <?= getStatusBadge($bk['status_booking']) ?> rounded-full text-[11px] font-bold border inline-block whitespace-nowrap">
                      <?= getStatusLabel($bk['status_booking']) ?>
                    </span>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Sidebar Widgets -->
        <div class="space-y-6">
          <!-- New Pengajuan -->
          <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-dark text-lg mb-4">Pengajuan Terbaru</h3>
            <div class="space-y-4">
              <?php if (empty($newPengajuan)): ?>
              <p class="text-gray-400 text-sm text-center py-4">Belum ada pengajuan.</p>
              <?php else: ?>
              <?php foreach ($newPengajuan as $pj): ?>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-orange-50 text-accent flex items-center justify-center font-bold">
                    <?= getInitials($pj['nama_pengguna'] ?? 'N/A') ?>
                  </div>
                  <div>
                    <p class="text-sm font-bold text-dark mb-0.5"><?= htmlspecialchars($pj['nama_pengguna'] ?? '-') ?></p>
                    <?php if ($pj['status'] === 'MENUNGGU'): ?>
                      <span class="px-2 py-0.5 bg-yellow-50 text-yellow-600 border border-yellow-200 rounded text-[10px] font-bold tracking-wide inline-block">MENUNGGU</span>
                    <?php elseif ($pj['status'] === 'DITERIMA'): ?>
                      <span class="px-2 py-0.5 bg-green-50 text-green-600 border border-green-200 rounded text-[10px] font-bold tracking-wide inline-block">DITERIMA</span>
                    <?php else: ?>
                      <span class="px-2 py-0.5 bg-red-50 text-red-600 border border-red-200 rounded text-[10px] font-bold tracking-wide inline-block">DITOLAK</span>
                    <?php endif; ?>
                  </div>
                </div>
                <a href="pengajuan.php" class="text-sm px-3 py-1.5 bg-primary/10 text-primary hover:bg-primary hover:text-white rounded-lg font-bold transition-colors">Detail</a>
              </div>
              <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <a href="pengajuan.php" class="block w-full mt-5 py-2 text-sm font-bold text-gray-500 hover:bg-gray-50 hover:text-dark rounded-lg transition-colors border border-gray-200 text-center">
              Lihat Semua
            </a>
          </div>

          <!-- Top Categories -->
          <div class="bg-gradient-to-br from-dark to-[#1d2666] rounded-2xl shadow-lg p-6 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-primary/20 rounded-full blur-2xl -translate-y-1/2 translate-x-1/3"></div>
            <h3 class="font-bold text-lg mb-6 relative z-10">Kategori Terpopuler</h3>
            <div class="space-y-5 relative z-10">
              <?php if (empty($kategoriPopuler)): ?>
              <p class="text-gray-400 text-sm text-center">Belum ada data kategori.</p>
              <?php else: ?>
              <?php 
              $colorSet = ['bg-accent', 'bg-primary', 'bg-secondary'];
              foreach ($kategoriPopuler as $idx => $kat): 
                $persen = $maxKategori > 0 ? round(($kat['total'] / $maxKategori) * 100) : 0;
                $barColor = $colorSet[$idx % count($colorSet)];
              ?>
              <div>
                <div class="flex justify-between text-sm mb-2">
                  <span class="font-medium text-white"><?= htmlspecialchars($kat['nama_kategori']) ?></span>
                  <span class="text-gray-300 font-bold"><?= $kat['total'] ?> freelancer</span>
                </div>
                <div class="w-full bg-white/20 rounded-full h-2">
                  <div class="<?= $barColor ?> h-2 rounded-full" style="width: <?= $persen ?>%"></div>
                </div>
              </div>
              <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>

</body>
</html>
