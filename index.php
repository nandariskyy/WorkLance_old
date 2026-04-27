<?php
require_once __DIR__ . '/config/database.php';

// ============ QUERY DATA ============

// Kategori + Jasa
$kategoriList = $pdo->query("SELECT * FROM kategori ORDER BY id_kategori")->fetchAll();
$jasaList = $pdo->query("SELECT * FROM jasa ORDER BY id_kategori, id_jasa")->fetchAll();
$jasaPerKategori = [];
foreach ($jasaList as $j) {
    $jasaPerKategori[$j['id_kategori']][] = $j;
}

// Freelancer Unggulan (LIMIT 4)
$freelancerUnggulan = $pdo->query("
    SELECT l.id_layanan, l.tarif, l.deskripsi,
           p.id_pengguna, p.nama_pengguna, p.alamat_lengkap,
           k.nama_kategori,
           j.nama_jasa,
           s.nama_satuan,
           COALESCE(AVG(u.rating), 0) AS avg_rating,
           COUNT(u.id_ulasan) AS total_ulasan
    FROM layanan l
    JOIN pengguna p ON l.id_pengguna = p.id_pengguna
    JOIN jasa j ON l.id_jasa = j.id_jasa
    JOIN kategori k ON j.id_kategori = k.id_kategori
    LEFT JOIN satuan s ON l.id_satuan = s.id_satuan
    LEFT JOIN booking b ON b.id_layanan = l.id_layanan
    LEFT JOIN ulasan u ON u.id_booking = b.id_booking
    GROUP BY l.id_layanan
    ORDER BY avg_rating DESC, l.id_layanan ASC
    LIMIT 4
")->fetchAll();

// Pencarian
$search = trim($_GET['search'] ?? '');
$hasilCari = [];
if ($search !== '') {
    $stmt = $pdo->prepare("
        SELECT l.id_layanan, l.tarif,
               p.id_pengguna, p.nama_pengguna, p.alamat_lengkap,
               k.nama_kategori, j.nama_jasa,
               COALESCE(AVG(u.rating), 0) AS avg_rating
        FROM layanan l
        JOIN pengguna p ON l.id_pengguna = p.id_pengguna
        JOIN jasa j ON l.id_jasa = j.id_jasa
        JOIN kategori k ON j.id_kategori = k.id_kategori
        LEFT JOIN booking b ON b.id_layanan = l.id_layanan
        LEFT JOIN ulasan u ON u.id_booking = b.id_booking
        WHERE p.nama_pengguna LIKE ? OR j.nama_jasa LIKE ? OR k.nama_kategori LIKE ?
        GROUP BY l.id_layanan
        ORDER BY avg_rating DESC
    ");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    $hasilCari = $stmt->fetchAll();
}

// SVG icons per kategori
$kategoriIcons = [
    1 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>',
    2 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>',
    3 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>',
    4 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>',
    5 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>',
    6 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>',
    7 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>',
    8 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
];


$loggedIn = isClientLoggedIn();
$userName = $_SESSION['user_nama'] ?? '';
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>WorkLance - Temukan Freelancer Lokal Terbaik</title>
  <meta name="description" content="WorkLance adalah platform marketplace freelancer lokal. Temukan tenaga profesional terbaik di sekitarmu dengan mudah, cepat, dan transparan." />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/WorkLance/src/output.css">
</head>

<body class="bg-gray-50 text-gray-800 font-sans antialiased overflow-x-hidden">

  <!-- Navbar -->
  <?php $currentPage = 'index'; require_once __DIR__ . '/components/navbar.php'; ?>

  <?php if ($search !== ''): ?>
  <!-- Search Results -->
  <section class="py-16 bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mb-8">
        <a href="index.php" class="inline-flex items-center text-gray-500 hover:text-accent font-medium mb-4 transition-colors">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
          Kembali ke Beranda
        </a>
        <h2 class="text-3xl font-bold text-dark mb-2">Hasil Pencarian: "<?= htmlspecialchars($search) ?>"</h2>
        <p class="text-gray-500"><?= count($hasilCari) ?> freelancer ditemukan</p>
      </div>
      <?php if (empty($hasilCari)): ?>
      <div class="text-center py-16">
        <p class="text-gray-400 text-lg">Tidak ada freelancer yang cocok dengan pencarian Anda.</p>
      </div>
      <?php else: ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        <?php foreach ($hasilCari as $idx => $fl): ?>
        <div class="bg-white rounded-3xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden group hover:-translate-y-1">
          <div class="p-8 relative flex flex-col h-full">
            <div class="absolute top-5 right-5 bg-yellow-50 text-yellow-700 text-sm font-bold px-3 py-1 rounded-full flex items-center border border-yellow-200">
              <svg class="w-4 h-4 mr-1 pb-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
              <?= number_format($fl['avg_rating'], 1) ?>
            </div>
            <div class="flex justify-center mb-6 mt-4">
              <img src="<?= $freelancerPhotos[$idx % 4] ?>" alt="<?= htmlspecialchars($fl['nama_pengguna']) ?>" class="w-28 h-28 rounded-full object-cover ring-4 ring-primary/20 group-hover:ring-primary/50 transition-all p-1 bg-white">
            </div>
            <div class="text-center flex-grow">
              <h3 class="text-xl font-bold text-dark mb-1"><?= htmlspecialchars($fl['nama_pengguna']) ?></h3>
              <p class="text-accent font-medium text-sm mb-4"><?= htmlspecialchars($fl['nama_jasa']) ?></p>
              <div class="flex items-center justify-center text-gray-500 text-sm mb-6 bg-gray-50 py-1.5 px-3 rounded-full w-max mx-auto">
                <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                <?= htmlspecialchars($fl['alamat_lengkap'] ?? '-') ?>
              </div>
            </div>
            <a href="layanan.php?id=<?= $fl['id_layanan'] ?>" class="block text-center w-full bg-gray-50 border border-gray-200 text-dark group-hover:bg-dark group-hover:text-white group-hover:border-dark py-3 rounded-xl transition-all duration-300 font-semibold mt-auto">Lihat Profil</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>
  <?php else: ?>

  <!-- Hero Section -->
  <header class="relative pt-24 pb-32 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-primary/10 to-gray-50 -z-10"></div>
    <div class="absolute top-0 right-0 -translate-y-12 translate-x-1/3 w-[800px] h-[800px] bg-primary/20 rounded-full blur-3xl -z-10 opacity-60"></div>
    <div class="absolute bottom-0 left-0 translate-y-1/3 -translate-x-1/3 w-[600px] h-[600px] bg-accent/10 rounded-full blur-3xl -z-10 opacity-60"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
      <div class="grid lg:grid-cols-2 gap-16 items-center">
        <!-- Hero Text -->
        <div class="max-w-2xl">
          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white shadow-sm border border-primary/20 text-sm font-medium text-dark mb-6">
            <span class="flex h-2 w-2 rounded-full bg-accent"></span>
            Platform Freelancer Lokal #1
          </div>
          <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold text-dark leading-[1.1] mb-6 tracking-tight">
            Temukan Freelancer <br />
            <span class="text-accent relative inline-block">
              Lokal Terbaik
              <svg class="absolute w-full h-3 -bottom-1 left-0 text-secondary/30" viewBox="0 0 100 10" preserveAspectRatio="none"><path d="M0 5 Q 50 10 100 5" stroke="currentColor" stroke-width="8" fill="none" stroke-linecap="round" /></svg>
            </span>
          </h1>
          <p class="text-xl text-gray-600 mb-10 leading-relaxed">
            Hubungkan kebutuhanmu dengan tenaga profesional tepercaya di sekitarmu. Dari desainer, teknisi, hingga tukang bangunan.
          </p>

          <!-- Search Component -->
          <form action="index.php" method="GET" class="bg-white p-3 rounded-2xl shadow-xl shadow-primary/10 border border-gray-100 flex flex-col sm:flex-row gap-3">
            <div class="flex-1 flex items-center px-4 bg-gray-50 rounded-xl">
              <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
              <input type="text" name="search" placeholder="Cari jasa atau keahlian..." class="w-full bg-transparent border-none py-3 px-3 focus:outline-none text-gray-700 placeholder-gray-400">
            </div>
            <button type="submit" class="bg-dark hover:bg-blue-900 text-white px-8 py-3 rounded-xl font-medium transition-colors shadow-md sm:w-auto w-full cursor-pointer">Cari Sekarang</button>
          </form>

          <!-- Popular Tags -->
          <div class="mt-8 flex flex-wrap items-center gap-3 text-sm">
            <span class="text-gray-500 font-medium">Pencarian Populer:</span>
            <a href="index.php?search=Foto" class="px-4 py-1.5 rounded-full bg-white border border-gray-200 text-gray-600 hover:border-accent hover:text-accent transition-colors shadow-sm">Fotografi</a>
            <a href="index.php?search=Service AC" class="px-4 py-1.5 rounded-full bg-white border border-gray-200 text-gray-600 hover:border-accent hover:text-accent transition-colors shadow-sm">Service AC</a>
            <a href="index.php?search=Tukang" class="px-4 py-1.5 rounded-full bg-white border border-gray-200 text-gray-600 hover:border-accent hover:text-accent transition-colors shadow-sm">Tukang Bangunan</a>
          </div>
        </div>

        <!-- Hero Visual -->
        <div class="relative hidden lg:block">
          <div class="relative w-[500px] h-[580px] mx-auto">
            <div class="absolute inset-0 bg-primary rounded-[3rem] rotate-3 opacity-20"></div>
            <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=800" alt="Freelancer tersenyum" class="absolute inset-0 w-full h-full object-cover rounded-[3rem] shadow-2xl border-8 border-white">
            <div class="absolute -left-12 top-20 bg-white p-4 rounded-2xl shadow-xl flex items-center gap-4 border border-gray-100 animate-[bounce_4s_infinite]">
              <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=100" alt="David" class="w-12 h-12 rounded-full object-cover ring-2 ring-primary">
              <div>
                <p class="font-bold text-dark text-sm">David W.</p>
                <div class="flex text-yellow-400 text-xs mt-0.5">★★★★★ <span class="text-gray-500 ml-1">(42)</span></div>
              </div>
            </div>
            <div class="absolute -right-8 bottom-32 bg-white p-4 rounded-2xl shadow-xl flex items-center gap-4 border border-gray-100 animate-[bounce_5s_infinite_1s]">
              <div class="bg-green-100 p-2.5 rounded-full text-green-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              </div>
              <div>
                <p class="font-bold text-dark text-sm">Terverifikasi</p>
                <p class="text-gray-500 text-xs mt-0.5">Identitas & Skill</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Categories Section -->
  <section id="categories" class="py-24 bg-gray-50 border-t border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center max-w-2xl mx-auto mb-16">
        <h2 class="text-4xl font-bold text-dark mb-4">Kategori Keahlian</h2>
        <p class="text-lg text-gray-600">Jelajahi berbagai keahlian dari freelancer lokal, dari desain kreatif hingga<br>perbaikan rumah.</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($kategoriList as $kat): ?>
        <a href="index.php?search=<?= urlencode($kat['nama_kategori']) ?>" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center group hover:shadow-xl hover:-translate-y-1 hover:border-primary/20 transition-all duration-300 flex flex-col relative overflow-hidden">
          <div class="relative z-10 transition-transform duration-500 group-hover:-translate-y-2">
            <div class="w-14 h-14 mx-auto bg-primary/10 text-primary rounded-xl flex items-center justify-center mb-4 group-hover:bg-primary group-hover:text-white transition-colors duration-300">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $kategoriIcons[$kat['id_kategori']] ?? '' ?></svg>
            </div>
            <h3 class="font-bold text-dark text-sm group-hover:text-accent transition-colors"><?= htmlspecialchars($kat['nama_kategori']) ?></h3>
          </div>
          <?php if (!empty($jasaPerKategori[$kat['id_kategori']])): ?>
          <div class="max-h-0 opacity-0 group-hover:max-h-96 group-hover:opacity-100 transition-all duration-500 ease-in-out text-left border-t border-transparent group-hover:border-gray-100 group-hover:mt-4 group-hover:pt-4">
            <ul class="space-y-2.5">
              <?php foreach ($jasaPerKategori[$kat['id_kategori']] as $j): ?>
              <li class="flex items-start gap-2 text-gray-500 text-xs font-medium">
                <svg class="w-3.5 h-3.5 text-accent mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><?= htmlspecialchars($j['nama_jasa']) ?>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Featured Freelancers -->
  <section id="freelancers" class="py-24 bg-white relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-6">
        <div class="max-w-2xl">
          <h2 class="text-4xl font-bold text-dark mb-4">Freelancer Unggulan</h2>
          <p class="text-lg text-gray-600">Profil profesional dengan rating tertinggi yang siap membantu proyek Anda hari ini.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        <?php foreach ($freelancerUnggulan as $idx => $fl): ?>
        <div class="bg-white rounded-3xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden group hover:-translate-y-1">
          <div class="p-8 relative flex flex-col h-full">
            <div class="absolute top-5 right-5 bg-yellow-50 text-yellow-700 text-sm font-bold px-3 py-1 rounded-full flex items-center border border-yellow-200">
              <svg class="w-4 h-4 mr-1 pb-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
              <?= number_format($fl['avg_rating'], 1) ?>
            </div>
            <div class="flex justify-center mb-6 mt-4">
              <div class="w-28 h-28 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-4xl ring-4 ring-primary/20 group-hover:ring-primary/50 transition-all p-1">
                <?= getInitials($fl['nama_pengguna']) ?>
              </div>
            </div>
            <div class="text-center flex-grow">
              <h3 class="text-xl font-bold text-dark mb-1"><?= htmlspecialchars($fl['nama_pengguna']) ?></h3>
              <p class="text-accent font-medium text-sm mb-4"><?= htmlspecialchars($fl['nama_kategori']) ?></p>
              <div class="flex items-center justify-center text-gray-500 text-sm mb-6 bg-gray-50 py-1.5 px-3 rounded-full w-max mx-auto">
                <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <?= htmlspecialchars($fl['alamat_lengkap'] ?: '-') ?>
              </div>
            </div>
            <a href="layanan.php?id=<?= $fl['id_layanan'] ?>" class="block text-center w-full bg-gray-50 border border-gray-200 text-dark group-hover:bg-dark group-hover:text-white group-hover:border-dark py-3 rounded-xl transition-all duration-300 font-semibold mt-auto">Lihat Profil</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Why WorkLance -->
  <section id="why-worklance" class="py-24 bg-dark text-white relative overflow-hidden">
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-primary/20 rounded-full blur-[100px] -z-0"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
      <div class="grid lg:grid-cols-2 gap-16 items-center">
        <div>
          <h2 class="text-4xl font-bold mb-6">Kenapa Memilih <span class="text-accent">WorkLance?</span></h2>
          <p class="text-gray-300 text-lg mb-10 leading-relaxed">Kami bukan sekedar marketplace jasa. Kami percaya bahwa kualitas pekerjaan terbaik lahir dari orang-orang terbaik.</p>
          <div class="space-y-8">
            <div class="flex gap-4">
              <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center flex-shrink-0 text-primary"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg></div>
              <div><h4 class="text-xl font-bold mb-2">Fokus Tenaga Lokal</h4><p class="text-gray-400">Temukan profesional terbaik yang berada tak jauh darimu untuk kolaborasi langsung.</p></div>
            </div>
            <div class="flex gap-4">
              <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center flex-shrink-0 text-primary"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg></div>
              <div><h4 class="text-xl font-bold mb-2">Transparan & Terpercaya</h4><p class="text-gray-400">Review nyata dari pelanggan sebelumnya menjamin kualitas dan integritas.</p></div>
            </div>
            <div class="flex gap-4">
              <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center flex-shrink-0 text-primary"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg></div>
              <div><h4 class="text-xl font-bold mb-2">Mendukung Kerja Layak</h4><p class="text-gray-400">Berkontribusi pada SDGs 8 dengan memberdayakan komunitas pekerja independen lokal secara adil.</p></div>
            </div>
          </div>
        </div>
        <div class="relative">
          <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&q=80&w=800" alt="Tim bekerja" class="rounded-3xl shadow-2xl border-4 border-white/10">
          <div class="absolute -bottom-6 -right-6 bg-accent text-white p-6 rounded-2xl shadow-xl w-48 text-center animate-bounce duration-1000">
            <h3 class="text-4xl font-bold mb-1">10k+</h3>
            <p class="text-sm font-medium">Pengguna Bahagia</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="py-24 bg-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-gradient-to-r from-primary to-blue-500 rounded-3xl p-10 md:p-16 text-center shadow-2xl relative overflow-hidden text-white">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-2xl -translate-y-1/2 translate-x-1/3"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-accent/20 rounded-full blur-2xl translate-y-1/3 -translate-x-1/3"></div>
        <div class="relative z-10">
          <h2 class="text-4xl md:text-5xl font-bold mb-6">Punya keahlian yang bisa dijual?</h2>
          <p class="text-xl text-blue-50 mb-10 max-w-2xl mx-auto">Bergabung dengan ribuan freelancer sukses lainnya di sekitarmu. Mulai tawarkan jasamu tanpa biaya langganan bulanan.</p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="<?= $loggedIn ? 'daftar-freelancer.php' : 'register.php' ?>" class="bg-accent hover:bg-orange-600 text-white px-8 py-4 rounded-xl font-bold text-lg transition-all shadow-lg hover:shadow-xl hover:-translate-y-1">Mulai Jadi Freelancer</a>
            <a href="mulai-freelancer.php" class="bg-white/20 hover:bg-white text-white hover:text-dark px-8 py-4 rounded-xl font-bold text-lg transition-all backdrop-blur-sm border border-white/30">Pelajari Lebih Lanjut</a>
          </div>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Footer -->
  <footer class="bg-gray-50 pt-16 pb-8 border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
        <div class="col-span-1 md:col-span-1">
          <a href="index.php" class="flex items-center gap-2 mb-4">
            <div class="w-8 h-8 bg-dark text-white rounded-lg flex items-center justify-center font-bold text-lg">W</div>
            <span class="text-xl font-bold text-dark tracking-tight">Work<span class="text-accent">Lance</span></span>
          </a>
          <p class="text-gray-500 mb-6 font-medium">Menghubungkan orang dengan orang sekitarmu, bukan hanya jasa.</p>
          <div class="flex space-x-4">
            <a href="#" class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 hover:bg-primary hover:text-white transition-colors"><span class="sr-only">Facebook</span><svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" /></svg></a>
            <a href="#" class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 hover:bg-primary hover:text-white transition-colors"><span class="sr-only">Instagram</span><svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd" /></svg></a>
          </div>
        </div>
        <div>
          <h4 class="font-bold text-dark text-lg mb-6">Tentang Kami</h4>
          <ul class="space-y-4">
            <li><a href="#" class="text-gray-500 hover:text-accent transition-colors font-medium">Bantuan & FAQ</a></li>
            <li><a href="#" class="text-gray-500 hover:text-accent transition-colors font-medium">Syarat & Ketentuan</a></li>
            <li><a href="#" class="text-gray-500 hover:text-accent transition-colors font-medium">Kebijakan Privasi</a></li>
          </ul>
        </div>
        <div>
          <h4 class="font-bold text-dark text-lg mb-6">Kategori Populer</h4>
          <ul class="space-y-4">
            <?php foreach (array_slice($kategoriList, 0, 4) as $kat): ?>
            <li><a href="index.php?search=<?= urlencode($kat['nama_kategori']) ?>" class="text-gray-500 hover:text-accent transition-colors font-medium"><?= htmlspecialchars($kat['nama_kategori']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div>
          <h4 class="font-bold text-dark text-lg mb-6">Informasi</h4>
          <ul class="space-y-4">
            <li><a href="#" class="text-gray-500 hover:text-accent transition-colors font-medium">Cara Memesan</a></li>
            <li><a href="mulai-freelancer.php" class="text-gray-500 hover:text-accent transition-colors font-medium">Cara Menjadi Freelancer</a></li>
            <li><a href="#" class="text-gray-500 hover:text-accent transition-colors font-medium">Kontak Kami</a></li>
          </ul>
        </div>
      </div>
      <div class="border-t border-gray-200 pt-8 flex flex-col md:flex-row justify-between items-center text-gray-500 text-sm font-medium">
        <p>&copy; 2026 WorkLance Indonesia. Seluruh hak cipta dilindungi.</p>
        <p class="mt-4 md:mt-0">Dibuat dengan ❤️ untuk pekerja lokal.</p>
      </div>
    </div>
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
  </script>

</body>
</html>