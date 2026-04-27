<?php
require_once __DIR__ . '/../config/database.php';
requireClientLogin();

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_nama'] ?? '';
$success = '';
$error = '';

// Load user
$stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id_pengguna = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $no_telp = trim($_POST['no_telp'] ?? '');
    $id_provinsi = (int)($_POST['id_provinsi'] ?? 0) ?: null;
    $id_kabupaten = (int)($_POST['id_kabupaten'] ?? 0) ?: null;
    $id_kecamatan = (int)($_POST['id_kecamatan'] ?? 0) ?: null;
    $id_desa = (int)($_POST['id_desa'] ?? 0) ?: null;
    $alamat_lengkap = trim($_POST['alamat_lengkap'] ?? '');

    if (empty($email)) {
        $error = 'Email wajib diisi.';
    } else {
        // Cek email unik
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM pengguna WHERE email = ? AND id_pengguna != ?");
        $stmtCheck->execute([$email, $userId]);
        if ($stmtCheck->fetchColumn() > 0) {
            $error = 'Email sudah digunakan.';
        } else {
            $stmt = $pdo->prepare("UPDATE pengguna SET email=?, no_telp=?, id_provinsi=?, id_kabupaten=?, id_kecamatan=?, id_desa=?, alamat_lengkap=? WHERE id_pengguna=?");
            $stmt->execute([$email, $no_telp, $id_provinsi, $id_kabupaten, $id_kecamatan, $id_desa, $alamat_lengkap, $userId]);
            $_SESSION['user_email'] = $email;
            $success = 'Kontak & alamat berhasil diperbarui.';
            $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id_pengguna = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        }
    }
}

// Load lokasi
$provinsiList = $pdo->query("SELECT * FROM provinsi ORDER BY nama_provinsi")->fetchAll();
$kabupatenList = $pdo->query("SELECT * FROM kabupaten ORDER BY nama_kabupaten")->fetchAll();
$kecamatanList = $pdo->query("SELECT * FROM kecamatan ORDER BY nama_kecamatan")->fetchAll();
$desaList = $pdo->query("SELECT * FROM desa ORDER BY nama_desa")->fetchAll();

$currentPage = 'kontak';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kontak & Alamat | WorkLance</title>
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
          <a href="kontak.php" class="flex items-center gap-3 px-5 py-4 text-sm font-bold bg-primary/10 text-primary border-l-4 border-primary transition-colors">
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
          <h2 class="text-xl font-bold text-dark mb-6">Kontak & Alamat</h2>
          <form method="POST" action="" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div>
                <label class="text-sm font-bold text-dark block mb-2">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark">
              </div>
              <div>
                <label class="text-sm font-bold text-dark block mb-2">No. Telepon</label>
                <input type="tel" name="no_telp" value="<?= htmlspecialchars($user['no_telp'] ?? '') ?>" class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark">
              </div>
            </div>

            <hr class="border-gray-100">
            <h3 class="text-lg font-bold text-dark">Alamat</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div>
                <label class="text-sm font-bold text-dark block mb-2">Provinsi</label>
                <select name="id_provinsi" id="selProvinsi" class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark">
                  <option value="">-- Pilih --</option>
                  <?php foreach ($provinsiList as $p): ?>
                  <option value="<?= $p['id_provinsi'] ?>" <?= $user['id_provinsi'] == $p['id_provinsi'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nama_provinsi']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label class="text-sm font-bold text-dark block mb-2">Kabupaten/Kota</label>
                <select name="id_kabupaten" id="selKabupaten" class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark">
                  <option value="">-- Pilih --</option>
                  <?php foreach ($kabupatenList as $k): ?>
                  <option value="<?= $k['id_kabupaten'] ?>" data-prov="<?= $k['id_provinsi'] ?>" <?= $user['id_kabupaten'] == $k['id_kabupaten'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kabupaten']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div>
                <label class="text-sm font-bold text-dark block mb-2">Kecamatan</label>
                <select name="id_kecamatan" id="selKecamatan" class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark">
                  <option value="">-- Pilih --</option>
                  <?php foreach ($kecamatanList as $kc): ?>
                  <option value="<?= $kc['id_kecamatan'] ?>" data-kab="<?= $kc['id_kabupaten'] ?>" <?= $user['id_kecamatan'] == $kc['id_kecamatan'] ? 'selected' : '' ?>><?= htmlspecialchars($kc['nama_kecamatan']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label class="text-sm font-bold text-dark block mb-2">Desa/Kelurahan</label>
                <select name="id_desa" id="selDesa" class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark">
                  <option value="">-- Pilih --</option>
                  <?php foreach ($desaList as $d): ?>
                  <option value="<?= $d['id_desa'] ?>" data-kec="<?= $d['id_kecamatan'] ?>" <?= $user['id_desa'] == $d['id_desa'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nama_desa']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div>
              <label class="text-sm font-bold text-dark block mb-2">Alamat Lengkap</label>
              <textarea name="alamat_lengkap" rows="3" class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark resize-none"><?= htmlspecialchars($user['alamat_lengkap'] ?? '') ?></textarea>
            </div>

            <div class="pt-2">
              <button type="submit" class="bg-accent hover:bg-orange-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition-all cursor-pointer">Simpan Perubahan</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    function cascadeFilter(parentSel, childSel, dataAttr) {
      document.getElementById(parentSel).addEventListener('change', function() {
        const val = this.value;
        const child = document.getElementById(childSel);
        child.value = '';
        child.querySelectorAll('option[' + dataAttr + ']').forEach(opt => {
          opt.style.display = (!val || opt.getAttribute(dataAttr) === val) ? '' : 'none';
        });
        child.dispatchEvent(new Event('change'));
      });
    }
    cascadeFilter('selProvinsi', 'selKabupaten', 'data-prov');
    cascadeFilter('selKabupaten', 'selKecamatan', 'data-kab');
    cascadeFilter('selKecamatan', 'selDesa', 'data-kec');
  </script>
</body>
</html>