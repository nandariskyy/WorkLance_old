<?php
require_once __DIR__ . '/config/database.php';
requireClientLogin();

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_nama'] ?? '';
$userRole = $_SESSION['user_role'] ?? 2;

// Cek sudah freelancer
if ($userRole == 3) {
    header('Location: kelola-jasa.php');
    exit;
}

// Cek apakah sudah pernah mengajukan dan masih menunggu
$stmtCek = $pdo->prepare("SELECT status, catatan_admin FROM pengajuan_freelancer WHERE id_pengguna = ? ORDER BY tanggal_pengajuan DESC LIMIT 1");
$stmtCek->execute([$userId]);
$cekPengajuan = $stmtCek->fetch();

$infoMsg = null;
if ($cekPengajuan && $cekPengajuan['status'] == 'MENUNGGU') {
    $infoMsg = "Anda sudah memiliki pengajuan yang sedang diproses. Harap tunggu konfirmasi admin.";
} elseif ($cekPengajuan && $cekPengajuan['status'] == 'DITERIMA') {
    $_SESSION['user_role'] = 3;
    header('Location: kelola-jasa.php');
    exit;
}

$errorMsg = '';
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$infoMsg) {
    $nik = trim($_POST['nik'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    
    $id_provinsi = (int)($_POST['id_provinsi'] ?? 0) ?: null;
    $id_kabupaten = (int)($_POST['id_kabupaten'] ?? 0) ?: null;
    $id_kecamatan = (int)($_POST['id_kecamatan'] ?? 0) ?: null;
    $id_desa = (int)($_POST['id_desa'] ?? 0) ?: null;
    $alamat_lengkap = trim($_POST['alamat_lengkap'] ?? '');
    
    if (empty($nik) || empty($deskripsi) || empty($alamat_lengkap) || !$id_provinsi || !$id_kabupaten || !$id_kecamatan || !$id_desa) {
        $errorMsg = "Pendaftaran gagal! Kolom alamat, NIK, dan Deskripsi wajib diisi.";
    } elseif (!isset($_POST['agree'])) {
        $errorMsg = "Anda harus menyetujui syarat & ketentuan.";
    } else {
        try {
            $pdo->beginTransaction();
            // Update alamat di tabel pengguna
            $stmtUpdate = $pdo->prepare("UPDATE pengguna SET id_provinsi=?, id_kabupaten=?, id_kecamatan=?, id_desa=?, alamat_lengkap=? WHERE id_pengguna=?");
            $stmtUpdate->execute([$id_provinsi, $id_kabupaten, $id_kecamatan, $id_desa, $alamat_lengkap, $userId]);
            
            // Simpan pengajuan
            $stmt = $pdo->prepare("INSERT INTO pengajuan_freelancer (id_pengguna, nik, deskripsi, status) VALUES (?, ?, ?, 'MENUNGGU')");
            $stmt->execute([$userId, $nik, $deskripsi]);
            $pdo->commit();
            
            $successMsg = "Pengajuan dan pembaruan profil berhasil disimpan! Silakan tunggu admin memverifikasi pengajuan Anda.";
            $infoMsg = "Anda sudah memiliki pengajuan yang sedang diproses. Harap tunggu konfirmasi admin."; // Stop further submitting
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errorMsg = "Terjadi kesalahan saat memproses data. Silakan coba lagi.";
        }
    }
}

// Load user data for pre-fill
$stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id_pengguna = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Load lokasi
$provinsiList = $pdo->query("SELECT * FROM provinsi ORDER BY nama_provinsi")->fetchAll();
$kabupatenList = $pdo->query("SELECT * FROM kabupaten ORDER BY nama_kabupaten")->fetchAll();
$kecamatanList = $pdo->query("SELECT * FROM kecamatan ORDER BY nama_kecamatan")->fetchAll();
$desaList = $pdo->query("SELECT * FROM desa ORDER BY nama_desa")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pendaftaran Freelancer | WorkLance</title>
  <meta name="description" content="Daftar sebagai freelancer di WorkLance. Lengkapi data dirimu dan ajukan pendaftaran." />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/WorkLance/src/output.css">
</head>
<body class="bg-gray-50 min-h-screen font-sans flex flex-col antialiased">

  <!-- Navbar -->
  <nav class="sticky top-0 z-50 bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-20 items-center">
        <a href="index.php" class="flex items-center gap-2 group">
          <div class="w-10 h-10 bg-dark text-white rounded-xl flex items-center justify-center font-bold text-xl">W</div>
          <span class="text-2xl font-bold text-dark tracking-tight">Work<span class="text-accent">Lance</span></span>
        </a>
        <div class="flex items-center gap-3">
          <span class="text-sm font-medium text-gray-500 hidden sm:block">Pendaftaran Freelancer</span>
          <div class="w-10 h-10 bg-primary/20 text-primary rounded-full flex items-center justify-center font-bold"><?= getInitials($userName) ?></div>
        </div>
      </div>
    </div>
  </nav>

  <!-- Form -->
  <main class="flex-grow py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
      
      <?php if ($successMsg): ?>
      <div class="mb-6 bg-green-50 text-green-700 p-4 rounded-xl border border-green-200 flex items-start gap-3">
        <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <p class="font-medium whitespace-pre-line"><?= htmlspecialchars($successMsg) ?></p>
      </div>
      <?php endif; ?>

      <?php if ($errorMsg): ?>
      <div class="mb-6 bg-red-50 text-red-700 p-4 rounded-xl border border-red-200 flex items-start gap-3">
        <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <p class="font-medium"><?= htmlspecialchars($errorMsg) ?></p>
      </div>
      <?php endif; ?>

      <?php if ($cekPengajuan && $cekPengajuan['status'] == 'DITOLAK' && !$successMsg): ?>
      <div class="mb-6 bg-red-50 text-red-700 p-4 rounded-xl border border-red-200 flex items-start gap-3">
        <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <div>
          <p class="font-bold">Pengajuan Sebelumnya Ditolak</p>
          <p class="text-sm mt-1">Alasan: <?= htmlspecialchars($cekPengajuan['catatan_admin'] ?: 'Tidak ada alasan spesifik.') ?></p>
          <p class="text-sm mt-2">Anda dapat memperbaiki data dan mengajukan ulang formulir di bawah ini.</p>
        </div>
      </div>
      <?php endif; ?>

      <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Header -->
        <div class="bg-dark p-8 md:p-10 text-white relative overflow-hidden">
          <div class="absolute top-0 right-0 w-64 h-64 bg-primary/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
          <div class="absolute bottom-0 left-0 w-48 h-48 bg-accent/15 rounded-full blur-3xl translate-y-1/2 -translate-x-1/3"></div>
          <div class="relative z-10">
            <h1 class="text-3xl font-bold mb-2">Form Pengajuan Freelancer</h1>
            <p class="text-gray-300">Bergabunglah dan mulai tawarkan keahlian Anda kepada klien di sekitar Anda.</p>
          </div>
        </div>

        <?php if ($infoMsg && !$successMsg): ?>
        <div class="p-10 text-center">
            <div class="w-20 h-20 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h2 class="text-2xl font-bold text-dark mb-2">Pengajuan Sedang Diproses</h2>
            <p class="text-gray-500 mb-6"><?= htmlspecialchars($infoMsg) ?></p>
            <a href="index.php" class="inline-block bg-gray-100 hover:bg-gray-200 text-dark font-bold py-3 px-6 rounded-xl transition-colors">Kembali ke Beranda</a>
        </div>
        <?php else: ?>
        <form method="POST" action="" class="p-8 md:p-10 space-y-8">

          <!-- Section 1: Identitas Diri -->
          <div>
            <h2 class="text-xl font-bold text-dark mb-1 flex items-center gap-2">
              <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
              Identitas Diri
            </h2>
            <p class="text-sm text-gray-500 mb-5">Informasi dasar akun Anda. (Dapat diubah di Pengaturan Akun)</p>
            <div class="grid md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-bold text-dark mb-2">Nama Lengkap</label>
                <input type="text" value="<?= htmlspecialchars($user['nama_pengguna'] ?? '') ?>" class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-100 focus:outline-none text-sm font-medium text-gray-500 cursor-not-allowed" readonly>
              </div>
              <div>
                <label class="block text-sm font-bold text-dark mb-2">Email</label>
                <input type="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-100 focus:outline-none text-sm font-medium text-gray-500 cursor-not-allowed" readonly>
              </div>
              <div>
                <label class="block text-sm font-bold text-dark mb-2">Provinsi <span class="text-red-500">*</span></label>
                <select name="id_provinsi" id="selProvinsi" required class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark cursor-pointer">
                  <option value="">-- Pilih --</option>
                  <?php foreach ($provinsiList as $p): ?>
                  <option value="<?= $p['id_provinsi'] ?>" <?= $user['id_provinsi'] == $p['id_provinsi'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nama_provinsi']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label class="block text-sm font-bold text-dark mb-2">Kabupaten/Kota <span class="text-red-500">*</span></label>
                <select name="id_kabupaten" id="selKabupaten" required class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark cursor-pointer">
                  <option value="">-- Pilih --</option>
                  <?php foreach ($kabupatenList as $k): ?>
                  <option value="<?= $k['id_kabupaten'] ?>" data-prov="<?= $k['id_provinsi'] ?>" <?= $user['id_kabupaten'] == $k['id_kabupaten'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kabupaten']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label class="block text-sm font-bold text-dark mb-2">Kecamatan <span class="text-red-500">*</span></label>
                <select name="id_kecamatan" id="selKecamatan" required class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark cursor-pointer">
                  <option value="">-- Pilih --</option>
                  <?php foreach ($kecamatanList as $kc): ?>
                  <option value="<?= $kc['id_kecamatan'] ?>" data-kab="<?= $kc['id_kabupaten'] ?>" <?= $user['id_kecamatan'] == $kc['id_kecamatan'] ? 'selected' : '' ?>><?= htmlspecialchars($kc['nama_kecamatan']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label class="block text-sm font-bold text-dark mb-2">Desa/Kelurahan <span class="text-red-500">*</span></label>
                <select name="id_desa" id="selDesa" required class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark cursor-pointer">
                  <option value="">-- Pilih --</option>
                  <?php foreach ($desaList as $d): ?>
                  <option value="<?= $d['id_desa'] ?>" data-kec="<?= $d['id_kecamatan'] ?>" <?= $user['id_desa'] == $d['id_desa'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nama_desa']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-bold text-dark mb-2">Alamat Lengkap <span class="text-red-500">*</span></label>
                <textarea name="alamat_lengkap" rows="2" required class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark resize-none cursor-text"><?= htmlspecialchars($user['alamat_lengkap'] ?? '') ?></textarea>
              </div>
            </div>
          </div>

          <hr class="border-gray-100">

          <!-- Section 2: Data Pengajuan -->
          <div>
            <h2 class="text-xl font-bold text-dark mb-1 flex items-center gap-2">
              <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
              Data Pengajuan
            </h2>
            <p class="text-sm text-gray-500 mb-5">Lengkapi data tambahan untuk verifikasi keamanan dan keahlian Anda.</p>
            <div class="space-y-6">
              <div>
                <label class="block text-sm font-bold text-dark mb-2">Nomor Induk Kependudukan (NIK) <span class="text-red-500">*</span></label>
                <input type="text" name="nik" placeholder="Ketik 16 digit NIK Anda..." required maxlength="16" class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark">
                <p class="text-xs text-gray-400 mt-1">Hanya digunakan untuk verifikasi identitas internal.</p>
              </div>
              
              <div>
                <label class="block text-sm font-bold text-dark mb-2">Deskripsi Diri & Keahlian <span class="text-red-500">*</span></label>
                <textarea name="deskripsi" rows="5" placeholder="Ceritakan riwayat pendidikan, pengalaman kerja, alur kerja, hingga pencapaian relevan yang membuat Anda layak menjadi freelancer di platform ini..." required class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark resize-none"></textarea>
              </div>
            </div>
          </div>

          <!-- Agreement -->
          <div class="flex items-start gap-3 p-4 bg-gray-50 rounded-xl border border-gray-100">
            <input type="checkbox" id="agree" name="agree" required class="mt-1 w-4 h-4 accent-accent cursor-pointer">
            <label for="agree" class="text-sm text-gray-600 cursor-pointer">
              Dengan mengajukan permohonan ini, saya menyatakan bahwa data yang saya berikan adalah benar. Saya menyetujui <a href="#" class="text-accent font-bold hover:underline">Syarat & Ketentuan</a> untuk menjadi freelancer dan siap menjaga nama baik platform.
            </label>
          </div>

          <!-- Action Buttons -->
          <div class="pt-4 flex flex-col sm:flex-row items-center gap-4">
            <a href="mulai-freelancer.php" class="px-8 py-3.5 text-gray-600 font-bold hover:text-dark transition-colors text-center w-full sm:w-auto">Kembali</a>
            <button type="submit" class="flex-1 w-full sm:w-auto bg-accent hover:bg-orange-600 text-white font-bold py-3.5 rounded-xl shadow-[0_8px_20px_-6px_rgba(193,87,42,0.5)] hover:shadow-xl transition-all transform hover:-translate-y-0.5 text-lg cursor-pointer">
              Kirim Pengajuan
            </button>
          </div>
        </form>
        <?php endif; ?>
      </div>

    </div>
  </main>

  <script>
    // JS Filtering Cascade
    function cascadeFilter(parentSel, childSel, dataAttr) {
      const parentEl = document.getElementById(parentSel);
      if (!parentEl) return;
      parentEl.addEventListener('change', function() {
        const val = this.value;
        const child = document.getElementById(childSel);
        if (!child) return;
        child.value = '';
        child.querySelectorAll('option[' + dataAttr + ']').forEach(opt => {
          opt.style.display = (!val || opt.getAttribute(dataAttr) === val) ? '' : 'none';
        });
        child.dispatchEvent(new Event('change'));
      });
    }

    // Initialize display states correctly without overwriting values
    function initCascadeFilters() {
      const sels = [
        { c: 'selKabupaten', pId: document.getElementById('selProvinsi')?.value, attr: 'data-prov' },
        { c: 'selKecamatan', pId: document.getElementById('selKabupaten')?.value, attr: 'data-kab' },
        { c: 'selDesa', pId: document.getElementById('selKecamatan')?.value, attr: 'data-kec' }
      ];
      sels.forEach(combo => {
        const child = document.getElementById(combo.c);
        if(!child) return;
        child.querySelectorAll('option[' + combo.attr + ']').forEach(opt => {
           opt.style.display = (!combo.pId || opt.getAttribute(combo.attr) === combo.pId) ? '' : 'none';
        });
      });
    }

    cascadeFilter('selProvinsi', 'selKabupaten', 'data-prov');
    cascadeFilter('selKabupaten', 'selKecamatan', 'data-kab');
    cascadeFilter('selKecamatan', 'selDesa', 'data-kec');
    initCascadeFilters();
  </script>
</body>
</html>