<?php
require_once __DIR__ . '/../config/database.php';
requireClientLogin();

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_nama'] ?? '';
$success = '';
$error = '';

// Proses ubah password
if (isset($_POST['ubah_password'])) {
    $passwordLama = trim($_POST['password_lama'] ?? '');
    $passwordBaru = trim($_POST['password_baru'] ?? '');
    $konfirmasi = trim($_POST['konfirmasi_password'] ?? '');

    if (empty($passwordLama) || empty($passwordBaru)) {
        $error = 'Password lama dan baru wajib diisi.';
    } elseif ($passwordBaru !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } elseif (strlen($passwordBaru) < 3) {
        $error = 'Password baru minimal 3 karakter.';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM pengguna WHERE id_pengguna = ?");
        $stmt->execute([$userId]);
        $current = $stmt->fetchColumn();
        if ($current !== $passwordLama) {
            $error = 'Password lama salah.';
        } else {
            $pdo->prepare("UPDATE pengguna SET password = ? WHERE id_pengguna = ?")->execute([$passwordBaru, $userId]);
            $success = 'Password berhasil diubah.';
        }
    }
}

// Proses hapus akun
if (isset($_POST['hapus_akun'])) {
    $konfirmasiHapus = trim($_POST['konfirmasi_hapus'] ?? '');
    if ($konfirmasiHapus !== 'HAPUS') {
        $error = 'Ketik "HAPUS" untuk mengonfirmasi penghapusan akun.';
    } else {
        // Hapus layanan terkait
        $pdo->prepare("DELETE FROM layanan WHERE id_pengguna = ?")->execute([$userId]);
        // Hapus pengguna
        $pdo->prepare("DELETE FROM pengguna WHERE id_pengguna = ?")->execute([$userId]);
        session_destroy();
        header('Location: ../index.php');
        exit;
    }
}

$currentPage = 'manajemen';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Keamanan Akun | WorkLance</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/WorkLance/src/output.css">
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased min-h-screen">

  <nav class="sticky top-0 z-50 glass-effect">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-20 items-center">
        <a href="../index.php" class="flex items-center gap-2">
          <div class="w-10 h-10 bg-dark text-white rounded-xl flex items-center justify-center font-bold text-xl shadow-md">W</div>
          <span class="text-2xl font-bold text-dark tracking-tight">Work<span class="text-accent">Lance</span></span>
        </a>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-primary/20 text-primary rounded-full flex items-center justify-center font-bold"><?= getInitials($userName) ?></div>
          <span class="text-sm font-bold text-dark hidden sm:block"><?= htmlspecialchars($userName) ?></span>
        </div>
      </div>
    </div>
  </nav>

  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-3xl font-bold text-dark mb-2">Pengaturan Akun</h1>
    <p class="text-gray-500 mb-8">Kelola informasi profil dan keamanan akun Anda.</p>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
      <!-- Sidebar -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <a href="informasi-akun.php" class="flex items-center gap-3 px-5 py-4 text-sm font-bold text-gray-600 hover:bg-gray-50 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            Informasi Akun
          </a>
          <a href="kontak.php" class="flex items-center gap-3 px-5 py-4 text-sm font-bold text-gray-600 hover:bg-gray-50 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            Kontak & Alamat
          </a>
          <a href="manajemen.php" class="flex items-center gap-3 px-5 py-4 text-sm font-bold bg-primary/10 text-primary border-l-4 border-primary transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            Keamanan
          </a>
          <div class="border-t border-gray-100"></div>
          <a href="../logout.php" class="flex items-center gap-3 px-5 py-4 text-sm font-bold text-red-500 hover:bg-red-50 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Logout
          </a>
        </div>
      </div>

      <!-- Content -->
      <div class="lg:col-span-3 space-y-8">
        <?php if ($success): ?>
        <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm font-medium flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="p-4 bg-red-50 border border-red-200 text-red-600 rounded-xl text-sm font-medium flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Change Password -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
          <h2 class="text-xl font-bold text-dark mb-6">Ubah Password</h2>
          <form method="POST" action="" class="space-y-5">
            <input type="hidden" name="ubah_password" value="1">
            <div>
              <label class="text-sm font-bold text-dark block mb-2">Password Lama</label>
              <input type="password" name="password_lama" required class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark" placeholder="Masukkan password saat ini">
            </div>
            <div>
              <label class="text-sm font-bold text-dark block mb-2">Password Baru</label>
              <input type="password" name="password_baru" required class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark" placeholder="Buat password baru">
            </div>
            <div>
              <label class="text-sm font-bold text-dark block mb-2">Konfirmasi Password Baru</label>
              <input type="password" name="konfirmasi_password" required class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark" placeholder="Ulangi password baru">
            </div>
            <button type="submit" class="bg-accent hover:bg-orange-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition-all cursor-pointer">Ubah Password</button>
          </form>
        </div>

        <!-- Delete Account -->
        <div class="bg-white rounded-2xl shadow-sm border border-red-200 p-8">
          <h2 class="text-xl font-bold text-red-600 mb-2">Hapus Akun</h2>
          <p class="text-gray-500 text-sm mb-6">Tindakan ini tidak dapat dibatalkan. Semua data Anda akan dihapus secara permanen.</p>
          <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="hapus_akun" value="1">
            <div>
              <label class="text-sm font-bold text-dark block mb-2">Ketik <span class="text-red-600">HAPUS</span> untuk konfirmasi</label>
              <input type="text" name="konfirmasi_hapus" required class="w-full border border-red-200 rounded-xl px-4 py-3 bg-red-50/50 focus:outline-none focus:ring-2 focus:ring-red-400 text-sm font-medium text-dark" placeholder="HAPUS">
            </div>
            <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menghapus akun ini?')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition-all cursor-pointer">Hapus Akun Saya</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>