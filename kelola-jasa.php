<?php
require_once __DIR__ . '/config/database.php';
requireClientLogin();

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_nama'] ?? '';
$userRole = $_SESSION['user_role'] ?? 2;
$success = '';
$error = '';

// ============ PROSES FORM ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $jasas = $_POST['jasa'] ?? []; // Array of id_jasa
    $id_satuan = (int)($_POST['id_satuan'] ?? 0);
    $tarif = trim($_POST['tarif'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if (!$id_kategori) {
        $error = 'Kategori wajib dipilih.';
    } elseif (empty($tarif)) {
        $error = 'Tarif wajib diisi.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Hapus data lama
            $pdo->prepare("DELETE FROM layanan WHERE id_pengguna = ?")->execute([$userId]);
            
            if (empty($jasas)) {
                // Downgrade role jika tidak ada jasa yang dipilih
                $pdo->prepare("UPDATE pengguna SET id_role = 2 WHERE id_pengguna = ?")->execute([$userId]);
                $_SESSION['user_role'] = 2;
                $success = 'Semua jasa telah dihapus. Profil Freelancer Anda dinonaktifkan.';
            } else {
                // Insert data baru (satu baris per jasa yang di checked)
                $stmt = $pdo->prepare("INSERT INTO layanan (id_pengguna, id_jasa, id_satuan, tarif, deskripsi) VALUES (?, ?, ?, ?, ?)");
                foreach ($jasas as $jasa_id) {
                    $stmt->execute([$userId, (int)$jasa_id, $id_satuan ?: null, $tarif, $deskripsi]);
                }
                
                // Upgrade role
                if ($userRole == 2) {
                    $pdo->prepare("UPDATE pengguna SET id_role = 3 WHERE id_pengguna = ?")->execute([$userId]);
                    $_SESSION['user_role'] = 3;
                }
                $success = 'Profil Jasa Freelancer berhasil diperbarui!';
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Terjadi kesalahan sistem saat menyimpan data.';
        }
    }
}

// Data dropdown/checkbox
$kategoriList = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();
$jasaList = $pdo->query("SELECT * FROM jasa ORDER BY nama_jasa")->fetchAll();
$satuanList = $pdo->query("SELECT * FROM satuan ORDER BY nama_satuan")->fetchAll();

// Get Current Data
$stmtMy = $pdo->prepare("SELECT l.*, j.id_kategori FROM layanan l JOIN jasa j ON l.id_jasa = j.id_jasa WHERE l.id_pengguna = ?");
$stmtMy->execute([$userId]);
$myFreelanceData = $stmtMy->fetchAll();

$savedKategori = 0;
$savedTarif = '';
$savedSatuan = 0;
$savedDeskripsi = '';
$checkedJasa = [];

if (!empty($myFreelanceData)) {
    $first = $myFreelanceData[0];
    $savedKategori = $first['id_kategori'];
    $savedTarif = $first['tarif'];
    $savedSatuan = $first['id_satuan'];
    $savedDeskripsi = $first['deskripsi'];
    foreach ($myFreelanceData as $row) {
        $checkedJasa[] = $row['id_jasa'];
    }
}

$isFreelancer = !empty($checkedJasa);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Jasa | WorkLance</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/WorkLance/src/output.css">
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased min-h-screen flex flex-col">

  <!-- Navbar -->
  <nav class="sticky top-0 z-50 glass-effect">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-20 items-center">
        <a href="index.php" class="flex items-center gap-2">
          <div class="w-10 h-10 bg-dark text-white rounded-xl flex items-center justify-center font-bold text-xl shadow-md">W</div>
          <span class="text-2xl font-bold text-dark tracking-tight">Work<span class="text-accent">Lance</span></span>
        </a>
        <div class="flex items-center gap-3">
          <a href="kelola-jasa.php" class="px-5 py-2.5 bg-accent/10 border border-accent/20 text-accent rounded-full text-sm font-bold transition-colors hidden sm:block">Kelola Jasa</a>
          <a href="pesanan.php" class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-dark transition-colors mr-2 hidden sm:block">Pesanan Masuk</a>

          <div class="w-10 h-10 bg-primary/20 text-primary rounded-full flex items-center justify-center font-bold relative ml-4">
            <?= getInitials($userName) ?>
            <div class="absolute bottom-0 right-0 w-3 h-3 <?= $isFreelancer ? 'bg-green-500' : 'bg-gray-400' ?> border-2 border-white rounded-full" title="<?= $isFreelancer ? 'Online' : 'Offline' ?>"></div>
          </div>
          <span class="text-sm font-bold text-dark hidden sm:block"><?= htmlspecialchars($userName) ?></span>
          <a href="logout.php" class="px-4 py-2 text-sm font-bold text-red-500 hover:bg-red-50 rounded-full transition-colors ml-4 border border-red-100">Logout</a>
        </div>
      </div>
    </div>
  </nav>

  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex-grow">
    <!-- Breadcrumb -->
    <nav class="flex items-center text-sm gap-2 font-medium mb-8">
      <a href="index.php" class="text-gray-500 hover:text-accent transition-colors">Beranda</a>
      <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
      <span class="text-dark font-bold">Kelola Jasa</span>
    </nav>

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
      <div>
        <h1 class="text-3xl font-bold text-dark flex items-center gap-3">
          Kelola Profil Freelancer
          <?php if ($isFreelancer): ?>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 text-green-700 rounded-lg text-xs font-bold border border-green-200 uppercase tracking-widest">
            <span class="w-2 h-2 rounded-full bg-green-500"></span> Aktif
          </span>
          <?php endif; ?>
        </h1>
        <p class="text-gray-500 mt-2">Atur kategori, centang jasa yang tersedia, tetapkan harga, dan deskripsikan keahlian Anda agar dilihat oleh klien.</p>
      </div>
      <?php if ($isFreelancer): ?>
      <a href="profil.php?id=<?= $myFreelanceData[0]['id_layanan'] ?? 0 ?>" target="_blank" class="px-5 py-2.5 bg-dark hover:bg-gray-800 text-white rounded-xl text-sm font-bold shadow-md transition-all flex items-center gap-2 flex-shrink-0">
        Lihat Profil Publik
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
      </a>
      <?php endif; ?>
    </div>

    <?php if ($success): ?>
    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm font-medium flex items-center gap-2 shadow-sm">
      <svg class="w-5 h-5 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-xl text-sm font-medium flex items-center gap-2 shadow-sm">
      <svg class="w-5 h-5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- ============ MAIN PROFILE FORM ============ -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
      
      <div class="bg-primary/5 p-6 border-b border-primary/10 flex items-center gap-4">
        <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center text-primary border border-gray-100">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
        </div>
        <div>
          <h2 class="text-xl font-bold text-dark"><?= htmlspecialchars($userName) ?></h2>
          <p class="text-sm text-gray-500">Konfigurasi Jasa yang Anda Tawarkan</p>
        </div>
      </div>

      <form method="POST" action="kelola-jasa.php" class="p-8 space-y-8">
        
        <!-- Kategori & Harga -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div>
            <label class="flex items-center gap-2 text-sm font-bold text-dark mb-3">
              <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
              Kategori Keahlian Utama <span class="text-red-500">*</span>
            </label>
            <select name="id_kategori" id="selectKategori" required class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark transition-colors cursor-pointer">
              <option value="">-- Pilih Kategori --</option>
              <?php foreach ($kategoriList as $kat): ?>
              <option value="<?= $kat['id_kategori'] ?>" <?= ($savedKategori == $kat['id_kategori']) ? 'selected' : '' ?>><?= htmlspecialchars($kat['nama_kategori']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="flex items-center gap-2 text-sm font-bold text-dark mb-3">
              <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              Harga Dasar / Minimum Order <span class="text-red-500">*</span>
            </label>
            <div class="flex shadow-sm rounded-xl overflow-hidden">
              <span class="inline-flex items-center px-4 border border-r-0 border-gray-200 bg-gray-100 text-gray-600 font-bold text-sm">Rp</span>
              <input type="number" name="tarif" value="<?= htmlspecialchars($savedTarif) ?>" placeholder="Contoh: 150000" required class="w-full border border-gray-200 px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark transition-colors z-10">
              <span class="inline-flex items-center px-3 border border-l-0 border-r-0 border-gray-200 bg-gray-100 text-gray-400 text-sm">/</span>
              <select name="id_satuan" class="border border-gray-200 px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark transition-colors z-10 cursor-pointer" style="min-width: 120px;">
                <option value="">Satuan</option>
                <?php foreach ($satuanList as $st): ?>
                <option value="<?= $st['id_satuan'] ?>" <?= ($savedSatuan == $st['id_satuan']) ? 'selected' : '' ?>><?= htmlspecialchars($st['nama_satuan']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <p class="text-xs text-gray-400 mt-2">Harga ini akan muncul sebagai 'Harga Mulai Dari' di profil Anda.</p>
          </div>
        </div>

        <!-- Checklist Jasa -->
        <div id="jasaContainerWrapper" class="pt-2 <?= empty($savedKategori) ? 'hidden' : '' ?>">
          <label class="flex items-center gap-2 text-sm font-bold text-dark mb-3">
            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            Tandai Jasa yang Anda Sediakan
          </label>
          <div class="bg-gray-50 p-6 rounded-2xl border border-gray-200">
            <div id="jasaCheckboxList" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
              <?php foreach ($jasaList as $js): ?>
              <?php $isChecked = in_array($js['id_jasa'], $checkedJasa); ?>
              <label class="jasa-item flex items-center gap-3 p-3 bg-white rounded-xl border <?= $isChecked ? 'border-accent shadow-sm ring-1 ring-accent/30' : 'border-gray-200' ?> cursor-pointer hover:border-gray-300 transition-all" data-kategori="<?= $js['id_kategori'] ?>">
                <input type="checkbox" name="jasa[]" value="<?= $js['id_jasa'] ?>" <?= $isChecked ? 'checked' : '' ?> class="w-5 h-5 text-accent rounded border-gray-300 focus:ring-accent transition-colors accent-accent">
                <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($js['nama_jasa']) ?></span>
              </label>
              <?php endforeach; ?>
            </div>
            <p id="noJasaMsg" class="text-sm text-gray-500 hidden py-4 text-center">Tidak ada jasa di kategori ini, atau silakan pilih kategori terlebih dahulu.</p>
          </div>
        </div>

        <!-- Deskripsi -->
        <div>
          <label class="flex items-center gap-2 text-sm font-bold text-dark mb-3">
            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            Deskripsi Profil
          </label>
          <textarea name="deskripsi" rows="6" placeholder="Ceritakan pengalaman, gaya kerja, dan apa yang membuat jasa Anda spesial..." required class="w-full border border-gray-200 rounded-2xl px-5 py-4 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark resize-none transition-colors"><?= htmlspecialchars($savedDeskripsi) ?></textarea>
        </div>

        <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-gray-100">
          <p class="text-sm text-gray-500 w-full sm:w-auto">
            <?= $isFreelancer ? 'Kosongkan semua tanda centang untuk mematikan profil.' : 'Tekan simpan untuk mengaktifkan profil.' ?>
          </p>
          <button type="submit" class="w-full sm:w-auto px-10 bg-accent hover:bg-orange-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-accent/30 transition-all transform hover:-translate-y-0.5 text-lg cursor-pointer flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
            Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-gray-50 pt-12 pb-8 mt-auto border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 text-center text-sm text-gray-500">
      <p>&copy; 2026 WorkLance Indonesia.</p>
    </div>
  </footer>

  <script>
    // JS Logic for dynamically hiding/showing checkboxes based on Category
    const selKategori = document.getElementById('selectKategori');
    const containerWrapper = document.getElementById('jasaContainerWrapper');
    const items = document.querySelectorAll('.jasa-item');
    const noJasaMsg = document.getElementById('noJasaMsg');

    function updateCheckboxes() {
      const katId = selKategori.value;
      let count = 0;
      
      if (!katId) {
        containerWrapper.classList.add('hidden');
        items.forEach(el => {
          el.querySelector('input').checked = false; // uncheck all if no cat
          el.classList.remove('border-accent', 'shadow-sm', 'ring-1', 'ring-accent/30');
          el.classList.add('border-gray-200');
        });
        return;
      }
      
      containerWrapper.classList.remove('hidden');

      items.forEach(el => {
        if (el.dataset.kategori === katId) {
          el.style.display = 'flex';
          count++;
        } else {
          el.style.display = 'none';
          el.querySelector('input').checked = false; // Uncheck hidden ones
          el.classList.remove('border-accent', 'shadow-sm', 'ring-1', 'ring-accent/30');
          el.classList.add('border-gray-200');
        }
      });

      noJasaMsg.style.display = (count === 0) ? 'block' : 'none';
    }

    selKategori.addEventListener('change', updateCheckboxes);

    // Initial Filter on load
    updateCheckboxes();

    // Visual styling for selected checkboxes
    items.forEach(label => {
      const input = label.querySelector('input');
      input.addEventListener('change', () => {
        if (input.checked) {
          label.classList.add('border-accent', 'shadow-sm', 'ring-1', 'ring-accent/30');
          label.classList.remove('border-gray-200');
        } else {
          label.classList.remove('border-accent', 'shadow-sm', 'ring-1', 'ring-accent/30');
          label.classList.add('border-gray-200');
        }
      });
    });
  </script>
</body>
</html>