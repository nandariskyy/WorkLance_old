<?php
require_once __DIR__ . '/config/database.php';

$id_input = (int)($_GET['id'] ?? (int)($_GET['user'] ?? 0));
if (!$id_input) {
    header('Location: index.php');
    exit;
}

// Cek apakah $id_input adalah id_layanan
$stmtCek = $pdo->prepare("SELECT id_pengguna FROM layanan WHERE id_layanan = ?");
$stmtCek->execute([$id_input]);
if ($row = $stmtCek->fetch()) {
    $id_pengguna = $row['id_pengguna'];
} else {
    $id_pengguna = $id_input; // asumsi itu sudah id_pengguna
}

// Fetch all layanan rows for this user to get category, baseline details, and offered jasas
$stmtData = $pdo->prepare("
    SELECT l.*, p.nama_pengguna, p.no_telp, p.alamat_lengkap, p.email,
           k.nama_kategori, k.id_kategori,
           s.nama_satuan,
           j.nama_jasa
    FROM layanan l
    JOIN pengguna p ON l.id_pengguna = p.id_pengguna
    JOIN jasa j ON l.id_jasa = j.id_jasa
    JOIN kategori k ON j.id_kategori = k.id_kategori
    LEFT JOIN satuan s ON l.id_satuan = s.id_satuan
    WHERE l.id_pengguna = ?
");
$stmtData->execute([$id_pengguna]);
$freelanceRows = $stmtData->fetchAll();

if (empty($freelanceRows)) {
    // Maybe show an error or redirect
    echo "<script>alert('Profil freelancer tidak ditemukan (belum aktif).'); window.location.href='index.php';</script>";
    exit;
}

// The base profile data (prices, desc, category are identical across all rows)
$profileData = $freelanceRows[0];
$mainKategoriId = $profileData['id_kategori'];

// Filter to ONLY jasas that match this category
$filteredLayananIds = [];
$offeredJasaList = [];
foreach ($freelanceRows as $row) {
    if ($row['id_kategori'] == $mainKategoriId) {
        $filteredLayananIds[] = $row['id_layanan'];
        // Store as array of jasa options for dropdown
        $offeredJasaList[] = [
            'id_layanan' => $row['id_layanan'],
            'nama_jasa' => $row['nama_jasa']
        ];
    }
}
$placeholders = '';
if (!empty($filteredLayananIds)) {
    $placeholders = str_repeat('?,', count($filteredLayananIds) - 1) . '?';
}

// Rating & Ulasan (Filtered to this category via booking)
$stmtUlasan = $pdo->prepare("
    SELECT COUNT(u.id_ulasan) as total, COALESCE(AVG(u.rating), 0) as ulasan_avg 
    FROM ulasan u
    JOIN booking b ON u.id_booking = b.id_booking
    WHERE b.id_layanan IN (" . (!empty($filteredLayananIds) ? $placeholders : '0') . ")
");
if (!empty($filteredLayananIds)) {
    $stmtUlasan->execute($filteredLayananIds);
} else {
    $stmtUlasan->execute();
}
$ulasanStat = $stmtUlasan->fetch();

$avg_rating = number_format($ulasanStat['ulasan_avg'] ?? 0, 1);
$total_ulasan = $ulasanStat['total'] ?? 0;

// Gather all reviews for the tab
$ulasanList = [];
if (!empty($filteredLayananIds)) {
    $stmtUlasanData = $pdo->prepare("
        SELECT u.rating, u.komentar, u.tanggal_ulasan, p.nama_pengguna 
        FROM ulasan u
        JOIN booking b ON u.id_booking = b.id_booking
        JOIN pengguna p ON u.id_pengguna = p.id_pengguna
        WHERE b.id_layanan IN ($placeholders)
        ORDER BY u.tanggal_ulasan DESC
    ");
    $stmtUlasanData->execute($filteredLayananIds);
    $ulasanList = $stmtUlasanData->fetchAll();
}

$loggedIn = isClientLoggedIn();
$userName = $_SESSION['user_nama'] ?? '';


?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($profileData['nama_pengguna']) ?> - Profil Freelancer | WorkLance</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/WorkLance/src/output.css">
</head>
<body class="bg-[#F8FAFC] text-gray-800 font-sans antialiased min-h-screen flex flex-col">

  <!-- Navbar -->
  <nav class="sticky top-0 z-50 glass-effect">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-20 items-center">
        <!-- Logo -->
        <a href="index.php" class="flex items-center gap-2 group">
          <div class="w-10 h-10 bg-dark text-white rounded-xl flex items-center justify-center font-bold text-xl group-hover:scale-105 transition-transform duration-300 shadow-md">W</div>
          <span class="text-2xl font-bold text-dark tracking-tight">Work<span class="text-accent">Lance</span></span>
        </a>

        <!-- Auth Buttons / User Menu -->
        <div class="hidden md:flex items-center gap-3">
          <?php if ($loggedIn): ?>
          <?php if ($_SESSION['user_role'] == 3): ?>
          <a href="kelola-jasa.php" class="px-5 py-2.5 text-sm font-bold text-dark hover:text-accent transition-colors border border-gray-200 hover:border-accent rounded-full bg-white/60 hover:bg-accent/5 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            Kelola Jasa
          </a>
          <?php else: ?>
          <a href="mulai-freelancer.php" class="px-5 py-2.5 text-sm font-bold text-white bg-gradient-to-r from-accent to-orange-500 rounded-full shadow-md shadow-accent/20 hover:shadow-lg hover:shadow-accent/30 transition-all transform hover:-translate-y-0.5 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
            Daftar Menjadi Freelancer
          </a>
          <?php endif; ?>
          <div class="relative" id="userMenuWrap">
            <button onclick="document.getElementById('userDropdown').classList.toggle('hidden')" class="flex items-center gap-3 cursor-pointer group">
              <div class="w-10 h-10 bg-primary/20 text-primary rounded-full flex items-center justify-center font-bold relative">
                <?= getInitials($userName) ?>
                <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
              </div>
              <span class="text-sm font-bold text-dark"><?= htmlspecialchars($userName) ?></span>
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div id="userDropdown" class="hidden absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50">
              <a href="pengaturan-akun/informasi-akun.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 font-medium">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Pengaturan Akun
              </a>
              <div class="border-t border-gray-100 my-1"></div>
              <a href="logout.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Logout
              </a>
            </div>
          </div>
          <?php else: ?>
          <a href="login.php" class="px-5 py-2.5 text-sm font-bold text-dark hover:text-accent transition-colors relative border border-transparent hover:border-gray-200 rounded-full hover:bg-white/60">Masuk</a>
          <a href="register.php" class="px-7 py-2.5 text-sm font-bold text-white bg-gradient-to-r from-dark to-[#1d2666] rounded-full shadow-lg shadow-dark/20 hover:shadow-xl hover:shadow-dark/30 transition-all transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-dark">Daftar</a>
          <?php endif; ?>
        </div>

        <!-- Mobile Menu Toggle -->
        <button class="md:hidden text-gray-600 hover:text-dark">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12 flex-grow w-full">
    
    <!-- No more server-side alerts needed here -->

    <div class="flex flex-col lg:flex-row gap-8 items-start">
      
      <!-- LEFT COLUMN -->
      <div class="w-full lg:w-2/3 space-y-8">
        
        <!-- Profile Header Box -->
        <div class="bg-gradient-to-br from-white to-gray-50 rounded-[2rem] p-8 md:p-10 shadow-sm border border-white relative overflow-hidden">
          <div class="absolute inset-0 bg-primary/5 rounded-[2rem]"></div>
          
          <div class="relative flex flex-col sm:flex-row items-center sm:items-center gap-8">
            <div class="relative flex-shrink-0">
              <img src="https://ui-avatars.com/api/?name=<?= urlencode($profileData['nama_pengguna']) ?>&background=96B3BF&color=fff&size=200" alt="Avatar" class="w-28 h-28 md:w-36 md:h-36 rounded-full border-4 border-white shadow-xl object-cover">
            </div>
            <div class="text-center sm:text-left">
              <h1 class="text-3xl md:text-4xl font-extrabold text-dark tracking-tight mb-2"><?= htmlspecialchars($profileData['nama_pengguna']) ?></h1>
              <h2 class="text-lg md:text-xl text-accent font-semibold mb-4"><?= htmlspecialchars($profileData['nama_kategori']) ?></h2>
              
              <div class="flex flex-wrap items-center justify-center sm:justify-start gap-4 text-sm font-medium">
                <div class="flex items-center gap-1.5 bg-yellow-50 text-yellow-700 px-3 py-1.5 rounded-full border border-yellow-100">
                  <svg class="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                  <span><?= $avg_rating ?> <span class="font-normal text-yellow-600">(<?= $total_ulasan ?> Ulasan)</span></span>
                </div>
                <div class="flex items-center gap-1.5 text-gray-500">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                  <?= htmlspecialchars($profileData['alamat_lengkap'] ?? 'Jakarta Selatan') ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Portfolio Gallery Box (Placeholders) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="aspect-[4/3] bg-gray-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
             <div class="w-full h-full bg-gradient-to-tr from-gray-300 to-gray-200 flex items-center justify-center text-gray-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
             </div>
          </div>
          <div class="aspect-[4/3] bg-gray-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
             <div class="w-full h-full bg-gradient-to-tr from-gray-300 to-gray-200 flex items-center justify-center text-gray-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
             </div>
          </div>
          <div class="aspect-[4/3] bg-gray-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
             <div class="w-full h-full bg-gradient-to-tr from-gray-300 to-gray-200 flex items-center justify-center text-gray-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
             </div>
          </div>
          <div class="aspect-[4/3] bg-gray-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
             <div class="w-full h-full bg-gradient-to-tr from-gray-300 to-gray-200 flex items-center justify-center text-gray-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
             </div>
          </div>
        </div>

        <!-- Details Tab Box -->
        <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden">
          <div class="flex border-b border-gray-100 px-6 pt-6">
            <button onclick="switchTab('deskripsi')" id="btnDeskripsi" class="px-6 py-3 font-bold text-accent border-b-2 border-accent transition-colors flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
              Deskripsi
            </button>
            <button onclick="switchTab('ulasan')" id="btnUlasan" class="px-6 py-3 font-semibold text-gray-400 border-b-2 border-transparent hover:text-gray-600 transition-colors flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
              Ulasan (<?= $total_ulasan ?>)
            </button>
          </div>
          <div class="p-8 min-h-[200px]">
            <div id="tabDeskripsi" class="block prose max-w-none text-gray-600 leading-relaxed text-[15px]">
              <?= nl2br(htmlspecialchars($profileData['deskripsi'] ?? 'Freelancer belum menuliskan profil.')) ?>
            </div>
            <div id="tabUlasan" class="hidden">
              <?php if (empty($ulasanList)): ?>
                <div class="text-center py-8">
                  <p class="text-gray-400 font-medium">Belum ada ulasan untuk kategori jasa ini.</p>
                </div>
              <?php else: ?>
                <div class="space-y-6">
                <?php foreach ($ulasanList as $u): ?>
                  <div class="border-b border-gray-100 pb-6 last:border-0 last:pb-0">
                    <div class="flex items-center justify-between mb-2">
                       <h4 class="font-bold text-dark"><?= htmlspecialchars($u['nama_pengguna']) ?></h4>
                       <span class="text-xs font-semibold text-gray-400 bg-gray-50 px-2 py-1 rounded-md"><?= date('d M Y', strtotime($u['tanggal_ulasan'])) ?></span>
                    </div>
                    <div class="flex items-center gap-1 mb-3 text-yellow-400">
                       <?php for ($i=1; $i<=5; $i++): ?>
                         <svg class="w-4 h-4 <?= $i <= $u['rating'] ? 'fill-current' : 'text-gray-200 fill-current' ?>" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                       <?php endfor; ?>
                    </div>
                    <p class="text-gray-600 text-sm leading-relaxed"><?= nl2br(htmlspecialchars($u['komentar'])) ?></p>
                  </div>
                <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN: FLOATING BOOKING FORM -->
      <div class="w-full lg:w-1/3">
        <div class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 p-6 md:p-8 border border-gray-100 lg:sticky lg:top-24">
          <h3 class="text-2xl font-bold text-dark mb-2">Pesan Jasa</h3>
          <p class="text-gray-500 text-sm mb-6">Hubungi freelancer untuk memulai proyekmu.</p>

          <div class="bg-blue-50/50 p-5 rounded-2xl border border-blue-100 mb-6 flex-shrink-0">
            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Harga Mulai Dari</span>
            <div class="flex items-end gap-2 text-accent">
              <span class="text-2xl font-extrabold leading-none">Rp <?= number_format($profileData['tarif'], 0, ',', '.') ?></span>
              <span class="text-sm font-medium text-gray-500 mb-0.5">/ <?= htmlspecialchars($profileData['nama_satuan'] ?? 'proyek') ?></span>
            </div>
          </div>

          <form id="bookingForm" class="space-y-5">
            <div>
              <label class="block text-sm font-bold text-dark mb-2">Jenis Jasa <span class="text-red-500">*</span></label>
              <div class="relative">
                <select id="selLayanan" required class="w-full border border-gray-200 rounded-xl pl-4 pr-10 py-3.5 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark appearance-none cursor-pointer">
                  <option value="">Pilih layanan...</option>
                  <?php foreach ($offeredJasaList as $oj): ?>
                  <option value="<?= $oj['id_layanan'] ?>" data-nama="<?= htmlspecialchars($oj['nama_jasa']) ?>"><?= htmlspecialchars($oj['nama_jasa']) ?></option>
                  <?php endforeach; ?>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-sm font-bold text-dark mb-2">Deskripsi Pekerjaan <span class="text-red-500">*</span></label>
              <textarea id="inpCatatan" rows="4" required placeholder="Ceritakan detail proyek, target pengguna, dan apa yang Anda butuhkan..." class="w-full border border-gray-200 rounded-xl px-4 py-3.5 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark resize-none"></textarea>
            </div>

            <div>
              <label class="block text-sm font-bold text-dark mb-2">Alamat Lengkap <span class="text-red-500">*</span></label>
              <textarea id="inpAlamat" rows="3" required placeholder="Masukkan alamat lengkap pengerjaan proyek / alamat anda..." class="w-full border border-gray-200 rounded-xl px-4 py-3.5 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark resize-none"></textarea>
            </div>

            <div>
              <label class="block text-sm font-bold text-dark mb-2">Kapan proyek ini harus selesai? <span class="text-red-500">*</span></label>
              <div class="relative">
                <input type="date" id="inpTanggal" required min="<?= date('Y-m-d') ?>" class="w-full border border-gray-200 rounded-xl pl-4 pr-10 py-3.5 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark">
              </div>
            </div>

            <div class="bg-green-50 p-4 rounded-xl flex items-start gap-3 border border-green-100">
              <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              <div>
                <h4 class="text-sm font-bold text-green-800">Waktu Respon Cepat</h4>
                <p class="text-xs text-green-600 mt-0.5">Freelancer ini biasanya merespon dalam 1 jam.</p>
              </div>
            </div>

            <button type="submit" class="w-full bg-accent hover:bg-orange-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg border border-transparent shadow-accent/30 transition-all transform hover:-translate-y-0.5 text-[15px] cursor-pointer">
              Lanjutkan Pemesanan
            </button>
          </form>

        </div>
      </div>
      
    </div>
  </main>

  <footer class="bg-gray-50 py-8 border-t border-gray-200 mt-12 w-full text-center text-sm text-gray-500">
    <p>&copy; 2026 WorkLance. All rights reserved.</p>
  </footer>

  <script>
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
      const wrap = document.getElementById('userMenuWrap');
      const dd = document.getElementById('userDropdown');
      if (wrap && dd && !wrap.contains(e.target)) {
        dd.classList.add('hidden');
      }
    });

    function switchTab(tabId) {
      if (tabId === 'deskripsi') {
        document.getElementById('tabDeskripsi').classList.remove('hidden');
        document.getElementById('tabDeskripsi').classList.add('block');
        document.getElementById('tabUlasan').classList.add('hidden');
        document.getElementById('tabUlasan').classList.remove('block');
        
        document.getElementById('btnDeskripsi').classList.add('text-accent', 'border-accent');
        document.getElementById('btnDeskripsi').classList.remove('text-gray-400', 'border-transparent');
        document.getElementById('btnUlasan').classList.remove('text-accent', 'border-accent');
        document.getElementById('btnUlasan').classList.add('text-gray-400', 'border-transparent');
      } else {
        document.getElementById('tabUlasan').classList.remove('hidden');
        document.getElementById('tabUlasan').classList.add('block');
        document.getElementById('tabDeskripsi').classList.add('hidden');
        document.getElementById('tabDeskripsi').classList.remove('block');
        
        document.getElementById('btnUlasan').classList.add('text-accent', 'border-accent');
        document.getElementById('btnUlasan').classList.remove('text-gray-400', 'border-transparent');
        document.getElementById('btnDeskripsi').classList.remove('text-accent', 'border-accent');
        document.getElementById('btnDeskripsi').classList.add('text-gray-400', 'border-transparent');
      }
    }

    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
      bookingForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const selLayanan = document.getElementById('selLayanan');
        const idLayanan = selLayanan.value;
        const namaJasa = selLayanan.options[selLayanan.selectedIndex].getAttribute('data-nama');
        const catatan = document.getElementById('inpCatatan').value;
        const alamat = document.getElementById('inpAlamat').value;
        const tanggal = document.getElementById('inpTanggal').value;
        
        const bookingData = {
          idLayanan: idLayanan,
          namaJasa: namaJasa,
          freelancerNama: <?= json_encode($profileData['nama_pengguna']) ?>,
          freelancerTelp: <?= json_encode($profileData['no_telp']) ?>,
          freelancerTarif: <?= json_encode($profileData['tarif']) ?>,
          freelancerSatuan: <?= json_encode($profileData['nama_satuan']) ?>,
          catatan: catatan,
          alamat: alamat,
          tanggal: tanggal
        };
        
        localStorage.setItem('worklance_booking', JSON.stringify(bookingData));
        window.location.href = 'ringkasan-pesanan.php';
      });
    }
  </script>
</body>
</html>