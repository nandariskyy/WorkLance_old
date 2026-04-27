<?php
require_once __DIR__ . '/../config/database.php';
requireClientLogin();

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Load user data
$stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id_pengguna = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_pengguna'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? null;

    if (empty($nama) || empty($username)) {
        $error = 'Nama dan username wajib diisi.';
    } else {
        // Cek username unik (exclud diri sendiri)
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM pengguna WHERE username = ? AND id_pengguna != ?");
        $stmtCheck->execute([$username, $userId]);
        if ($stmtCheck->fetchColumn() > 0) {
            $error = 'Username sudah digunakan.';
        } else {
            $stmt = $pdo->prepare("UPDATE pengguna SET nama_pengguna = ?, username = ?, tanggal_lahir = ? WHERE id_pengguna = ?");
            $stmt->execute([$nama, $username, $tanggal_lahir ?: null, $userId]);
            $_SESSION['user_nama'] = $nama;
            $_SESSION['user_username'] = $username;
            $success = 'Informasi akun berhasil diperbarui.';
            // Reload
            $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id_pengguna = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        }
    }
}

$userName = $_SESSION['user_nama'] ?? '';
$currentPage = 'informasi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Informasi Akun | WorkLance</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/WorkLance/src/output.css">
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased min-h-screen">

  <!-- Navbar -->
  <?php require_once __DIR__ . '/../components/navbar.php'; ?>

  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-3xl font-bold text-dark mb-2">Pengaturan Akun</h1>
    <p class="text-gray-500 mb-8">Kelola informasi profil dan keamanan akun Anda.</p>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
      <!-- Sidebar -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <a href="informasi-akun.php" class="flex items-center gap-3 px-5 py-4 text-sm font-bold <?= $currentPage === 'informasi' ? 'bg-primary/10 text-primary border-l-4 border-primary' : 'text-gray-600 hover:bg-gray-50' ?> transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            Informasi Akun
          </a>
          <a href="kontak.php" class="flex items-center gap-3 px-5 py-4 text-sm font-bold text-gray-600 hover:bg-gray-50 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            Kontak & Alamat
          </a>
          <a href="manajemen.php" class="flex items-center gap-3 px-5 py-4 text-sm font-bold text-gray-600 hover:bg-gray-50 transition-colors">
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
      <div class="lg:col-span-3">
        <?php if ($success): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm font-medium flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-xl text-sm font-medium flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
          <h2 class="text-xl font-bold text-dark mb-6">Informasi Akun</h2>
          <form method="POST" action="" class="space-y-5">
            <div>
              <label class="text-sm font-bold text-dark block mb-2">Nama Lengkap</label>
              <input type="text" name="nama_pengguna" value="<?= htmlspecialchars($user['nama_pengguna'] ?? '') ?>" required class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark">
            </div>
            <div>
              <label class="text-sm font-bold text-dark block mb-2">Username</label>
              <input type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark">
            </div>
            <div>
              <label class="text-sm font-bold text-dark block mb-2">Tanggal Lahir</label>
              <input type="date" name="tanggal_lahir" value="<?= $user['tanggal_lahir'] ?? '' ?>" class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark">
            </div>
            <div class="pt-2">
              <button type="submit" class="bg-accent hover:bg-orange-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition-all cursor-pointer">Simpan Perubahan</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>