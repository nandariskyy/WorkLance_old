<?php
require_once __DIR__ . '/config/database.php';
$loggedIn = isClientLoggedIn();
$userName = $_SESSION['user_nama'] ?? '';
$userRole = $_SESSION['user_role'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Jadi Freelancer | WorkLance</title>
  <meta name="description" content="Mulai karir freelancer Anda di WorkLance. Ubah keahlian menjadi penghasilan, tanpa biaya langganan." />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/WorkLance/src/output.css">
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased overflow-x-hidden">

  <!-- Navbar -->
  <?php $currentPage = 'mulai-freelancer'; require_once __DIR__ . '/components/navbar.php'; ?>

  <!-- Hero Section -->
  <section class="relative pt-20 pb-24 overflow-hidden bg-dark text-white">
    <div class="absolute inset-0 bg-primary/20 bg-cover bg-center mix-blend-overlay" style="background-image: url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&q=80&w=1200');"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
      <h1 class="text-5xl md:text-6xl font-bold mb-6">Ubah Keahlianmu Menjadi Penghasilan!</h1>
      <p class="text-xl text-gray-300 max-w-3xl mx-auto mb-10 leading-relaxed">
        Ribuan pelanggan lokal sedang mencari seseorang dengan kemampuan persis sepertimu. Daftar gratis, tanpa ikatan waktu, dan raih kebebasan finansialmu bersama WorkLance.
      </p>
      <a href="<?= $loggedIn ? 'daftar-freelancer.php' : 'register.php' ?>" class="inline-block bg-accent hover:bg-orange-600 text-white px-10 py-5 rounded-xl font-bold text-xl transition-all shadow-xl hover:-translate-y-1">
        Daftar Sebagai Freelancer Sekarang
      </a>
    </div>
  </section>

  <!-- Keuntungan (Benefits) -->
  <section class="py-24 bg-white relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center max-w-2xl mx-auto mb-16">
        <h2 class="text-4xl font-bold text-dark mb-4">Mengapa Menggunakan WorkLance?</h2>
        <p class="text-lg text-gray-600">Maksimalkan potensimu dengan fitur-fitur yang dirancang khusus untuk memajukan karir lokalmu.</p>
      </div>

      <div class="grid md:grid-cols-3 gap-8">
        <div class="bg-gray-50 rounded-3xl p-8 border border-gray-100 hover:shadow-lg transition-all hover:-translate-y-2">
          <div class="w-14 h-14 bg-blue-100 text-primary rounded-xl flex items-center justify-center mb-6">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          </div>
          <h3 class="text-2xl font-bold text-dark mb-3">Atur Waktu Sendiri</h3>
          <p class="text-gray-600 leading-relaxed">Kerjakan proyek kapan saja dan dari mana saja. Anda memegang kendali penuh atas jadwal kerja Anda.</p>
        </div>
        <div class="bg-gray-50 rounded-3xl p-8 border border-gray-100 hover:shadow-lg transition-all hover:-translate-y-2">
          <div class="w-14 h-14 bg-green-100 text-green-600 rounded-xl flex items-center justify-center mb-6">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          </div>
          <h3 class="text-2xl font-bold text-dark mb-3">Penghasilan Maksimal</h3>
          <p class="text-gray-600 leading-relaxed">Tanpa biaya pendaftaran yang menyulitkan. Pembayaran dijamin aman melalui sistem rekening bersama kami.</p>
        </div>
        <div class="bg-gray-50 rounded-3xl p-8 border border-gray-100 hover:shadow-lg transition-all hover:-translate-y-2">
          <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center mb-6">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
          </div>
          <h3 class="text-2xl font-bold text-dark mb-3">Koneksi Lokal</h3>
          <p class="text-gray-600 leading-relaxed">Bangun reputasi dan portofolio di kotamu sendiri, jangkau lebih banyak klien potensial di sekitarmu.</p>
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-8 mt-8">
        <div class="bg-gray-50 rounded-3xl p-8 border border-gray-100 hover:shadow-lg transition-all hover:-translate-y-2">
          <div class="w-14 h-14 bg-orange-100 text-accent rounded-xl flex items-center justify-center mb-6">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
          </div>
          <h3 class="text-2xl font-bold text-dark mb-3">Jangan Khawatir Penipuan</h3>
          <p class="text-gray-600 leading-relaxed">Buyer harus melakukan booking melalui platform kami sebelum Anda bekerja. Sistem kami menjaga keamanan transaksi.</p>
        </div>
        <div class="bg-gray-50 rounded-3xl p-8 border border-gray-100 hover:shadow-lg transition-all hover:-translate-y-2">
          <div class="w-14 h-14 bg-cyan-100 text-cyan-600 rounded-xl flex items-center justify-center mb-6">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
          </div>
          <h3 class="text-2xl font-bold text-dark mb-3">Tim Kami Siap Melayani</h3>
          <p class="text-gray-600 leading-relaxed">Tim support kami siap membantu Anda kapan pun. Kami terus meningkatkan sistem untuk pengalaman terbaik.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Langkah-langkah (Steps) -->
  <section class="py-24 bg-gray-50 border-t border-gray-100 relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center max-w-2xl mx-auto mb-16">
        <h2 class="text-4xl font-bold text-dark mb-4">Cara Memulai di WorkLance</h2>
        <p class="text-lg text-gray-600">4 Langkah mudah dari mendaftar hingga mendapat uang.</p>
      </div>
      
      <div class="max-w-4xl mx-auto">
        <div class="relative flex flex-col items-center space-y-12">
          <!-- Line connector -->
          <div class="absolute left-8 top-8 bottom-8 w-0.5 bg-gray-200 hidden md:block"></div>

          <div class="flex items-start md:items-center gap-6 w-full">
            <div class="flex-shrink-0 w-16 h-16 bg-white border-4 border-accent text-accent font-bold text-2xl rounded-full flex items-center justify-center shadow-lg relative z-10">1</div>
            <div>
              <h3 class="text-2xl font-bold text-dark mb-2">Punya Akun WorkLance & Lengkapi Data</h3>
              <p class="text-gray-600">Pastikan Anda sudah memiliki akun WorkLance. Klik daftar dan buat akun Anda, lalu isi form pendaftaran freelancer dengan data diri dan tipe layanan Anda.</p>
            </div>
          </div>
          
          <div class="flex items-start md:items-center gap-6 w-full">
            <div class="flex-shrink-0 w-16 h-16 bg-white border-4 border-accent text-accent font-bold text-2xl rounded-full flex items-center justify-center shadow-lg relative z-10">2</div>
            <div>
              <h3 class="text-2xl font-bold text-dark mb-2">Lengkapi Profil & Portofolio</h3>
              <p class="text-gray-600">Unggah foto profil yang profesional, cantumkan rate harga, keahlian, dan pasang foto karya terbaikmu.</p>
            </div>
          </div>

          <div class="flex items-start md:items-center gap-6 w-full">
            <div class="flex-shrink-0 w-16 h-16 bg-white border-4 border-accent text-accent font-bold text-2xl rounded-full flex items-center justify-center shadow-lg relative z-10">3</div>
            <div>
              <h3 class="text-2xl font-bold text-dark mb-2">Terima Tawaran</h3>
              <p class="text-gray-600">Klien akan menghubungimu. Diskusikan detail pesanan melalui chat WhatsApp dan sepakati harga.</p>
            </div>
          </div>

          <div class="flex items-start md:items-center gap-6 w-full">
            <div class="flex-shrink-0 w-16 h-16 bg-accent text-white font-bold text-2xl rounded-full flex items-center justify-center shadow-lg relative z-10">4</div>
            <div>
              <h3 class="text-2xl font-bold text-dark mb-2">Selesaikan & Dapatkan Penghasilan</h3>
              <p class="text-gray-600">Kerjakan proyek secara profesional, dapatkan rating positif, dan jadilah freelancer top pilihan lokal!</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Bottom -->
  <section class="py-20 bg-primary/10">
    <div class="max-w-4xl mx-auto px-4 text-center">
      <h2 class="text-3xl md:text-4xl font-bold text-dark mb-6">Tunggu Apa Lagi?</h2>
      <p class="text-xl text-gray-600 mb-8">Peluang karir masa depan ada di depan matamu. Ambil langkah pertamamu menjadi freelancer sukses hari ini.</p>
      <a href="<?= $loggedIn ? 'daftar-freelancer.php' : 'register.php' ?>" class="inline-block bg-accent hover:bg-orange-600 text-white px-10 py-5 rounded-xl font-bold text-xl transition-all shadow-xl hover:-translate-y-1">
        Daftar Sebagai Freelancer
      </a>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-gray-50 pt-16 pb-8 border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-500 text-sm font-medium">
      <p>&copy; 2026 WorkLance Indonesia. Dibuat dengan ❤️ untuk pekerja lokal.</p>
    </div>
  </footer>

  <script>
    document.addEventListener('click', function(e) {
      const wrap = document.getElementById('userMenuWrap');
      const dd = document.getElementById('userDropdown');
      if (wrap && dd && !wrap.contains(e.target)) dd.classList.add('hidden');
    });
  </script>
</body>
</html>