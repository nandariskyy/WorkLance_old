<?php
require_once __DIR__ . '/config/database.php';
requireClientLogin();

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_nama'] ?? '';
$userRole = $_SESSION['user_role'] ?? 2;
$success = '';
$error = '';

if ($userRole != 3) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id_kategori = (int)($_POST['id_kategori'] ?? 0);
        $jasas = $_POST['jasa'] ?? []; // Array of id_jasa
        $id_satuan = (int)($_POST['id_satuan'] ?? 0);
        $tarif = trim($_POST['tarif'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        if (!$id_kategori) {
            $error = 'Kategori wajib dipilih.';
        } elseif (empty($tarif)) {
            $error = 'Tarif wajib diisi.';
        } elseif (empty($jasas)) {
            $error = 'Minimal satu jasa harus ditandai.';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Get existing jasa ids for this user & category
                $stmtExist = $pdo->prepare("SELECT l.id_jasa FROM layanan l JOIN jasa j ON l.id_jasa = j.id_jasa WHERE l.id_pengguna = ? AND j.id_kategori = ?");
                $stmtExist->execute([$userId, $id_kategori]);
                $existingJasaIds = $stmtExist->fetchAll(PDO::FETCH_COLUMN);
                
                // Jasas that should be added
                $toAdd = array_diff($jasas, $existingJasaIds);
                // Jasas that should be removed
                $toRemove = array_diff($existingJasaIds, $jasas);
                
                // 1. Update EVERY existing row in this category with new tarif, deskripsi, id_satuan
                if (!empty($existingJasaIds)) {
                    $stmtUpd = $pdo->prepare("UPDATE layanan l JOIN jasa j ON l.id_jasa = j.id_jasa SET l.tarif = ?, l.deskripsi = ?, l.id_satuan = ? WHERE l.id_pengguna = ? AND j.id_kategori = ?");
                    $stmtUpd->execute([$tarif, $deskripsi, $id_satuan ?: null, $userId, $id_kategori]);
                }
                
                // 2. Add new jasas
                if (!empty($toAdd)) {
                    $stmtIn = $pdo->prepare("INSERT INTO layanan (id_pengguna, id_jasa, id_satuan, tarif, deskripsi) VALUES (?, ?, ?, ?, ?)");
                    foreach ($toAdd as $jasa_id) {
                        $stmtIn->execute([$userId, (int)$jasa_id, $id_satuan ?: null, $tarif, $deskripsi]);
                    }
                }
                
                // 3. Remove unchecked jasas
                if (!empty($toRemove)) {
                    $stmtDel = $pdo->prepare("DELETE FROM layanan WHERE id_pengguna = ? AND id_jasa = ?");
                    foreach($toRemove as $jasa_id) {
                        // Check if it has booking first
                        $stmtChk = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE id_layanan IN (SELECT id_layanan FROM layanan WHERE id_pengguna = ? AND id_jasa = ?)");
                        $stmtChk->execute([$userId, $jasa_id]);
                        if ($stmtChk->fetchColumn() > 0) {
                            throw new Exception("Tidak dapat menghapus pilihan sub-jasa yang sudah pernah dipesan. (Centang kembali atau tambahkan yang lain)");
                        }
                        $stmtDel->execute([$userId, $jasa_id]);
                    }
                }
                
                $pdo->commit();
                $success = 'Layanan berhasil disimpan!';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = (strpos($e->getMessage(), 'Deskripsi') === false && strpos($e->getMessage(), 'Tidak dapat') !== false) 
                            ? $e->getMessage() 
                            : 'Terjadi kesalahan sistem saat menyimpan data layanan.';
            }
        }
    } elseif ($action === 'delete') {
        $id_kategori = (int)($_POST['id_kategori'] ?? 0);
        try {
            $pdo->beginTransaction();
            
            // Cek booking dulu untuk mencegah Foreign Key Violation
            $stmtChk2 = $pdo->prepare("SELECT COUNT(*) FROM booking b JOIN layanan l ON b.id_layanan = l.id_layanan JOIN jasa j ON l.id_jasa = j.id_jasa WHERE l.id_pengguna = ? AND j.id_kategori = ?");
            $stmtChk2->execute([$userId, $id_kategori]);
            if ($stmtChk2->fetchColumn() > 0) {
                 throw new Exception("Tidak dapat menghapus kategori yang memiliki riwayat pesanan (Cukup edit harganya saja).");
            }
            
            $stmtDel2 = $pdo->prepare("DELETE l FROM layanan l JOIN jasa j ON l.id_jasa = j.id_jasa WHERE l.id_pengguna = ? AND j.id_kategori = ?");
            $stmtDel2->execute([$userId, $id_kategori]);
            
            // Cek apakah user masih memiliki setidaknya 1 layanan, jika tidak matikan/downgrade profil freelancer-nya
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM layanan WHERE id_pengguna = ?");
            $stmtCheck->execute([$userId]);
            if ($stmtCheck->fetchColumn() == 0) {
                $pdo->prepare("UPDATE pengguna SET id_role = 2 WHERE id_pengguna = ?")->execute([$userId]);
                $_SESSION['user_role'] = 2; // Downgrade di session
            }
            $pdo->commit();

            if ($_SESSION['user_role'] == 2) {
                // Return to user dashboard if no longer a freelancer
                header('Location: index.php');
                exit;
            } else {
                $success = 'Kategori layanan berhasil dihapus.';
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = (strpos($e->getMessage(), 'Tidak dapat') !== false) ? $e->getMessage() : 'Gagal menghapus layanan.';
        }
    }
}

// Data dropdown/checkbox
$kategoriList = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();
$jasaList = $pdo->query("SELECT * FROM jasa ORDER BY nama_jasa")->fetchAll();
$satuanList = $pdo->query("SELECT * FROM satuan ORDER BY nama_satuan")->fetchAll();

// Get Current Data Grouped By Kategori
$stmtMy = $pdo->prepare("
  SELECT j.id_kategori, k.nama_kategori, MIN(l.tarif) as tarif, MAX(l.deskripsi) as deskripsi, 
         MIN(l.id_satuan) as id_satuan, MAX(s.nama_satuan) as nama_satuan, MAX(l.id_layanan) as first_layanan_id,
         GROUP_CONCAT(j.nama_jasa SEPARATOR ', ') as jasa_names,
         GROUP_CONCAT(j.id_jasa SEPARATOR ',') as jasa_ids
  FROM layanan l
  JOIN jasa j ON l.id_jasa = j.id_jasa
  JOIN kategori k ON j.id_kategori = k.id_kategori
  LEFT JOIN satuan s ON l.id_satuan = s.id_satuan
  WHERE l.id_pengguna = ?
  GROUP BY j.id_kategori
");
$stmtMy->execute([$userId]);
$myCategories = $stmtMy->fetchAll();

$isFreelancer = !empty($myCategories);

// Transform jasa_ids into array string for JS
foreach($myCategories as &$mc) {
    if (!empty($mc['jasa_ids'])) {
        $mc['jasa_ids_json'] = json_encode(explode(',', $mc['jasa_ids']));
    } else {
        $mc['jasa_ids_json'] = '[]';
    }
}
unset($mc);
$currentPage = 'kelola-jasa';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Jasa | WorkLance</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/WorkLance/src/output.css?v=<?= time() ?>">
  <style>
    /* Prevent body scroll when modal is open */
    .modal-open { overflow: hidden; }
  </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased min-h-screen flex flex-col relative">

  <!-- Navbar -->
  <?php require_once __DIR__ . '/components/navbar.php'; ?>

  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex-grow w-full relative z-0">
    <!-- Breadcrumb -->
    <nav class="flex items-center text-sm gap-2 font-medium mb-8">
      <a href="index.php" class="text-gray-500 hover:text-accent transition-colors">Beranda</a>
      <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
      <span class="text-dark font-bold">Kelola Layanan</span>
    </nav>

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
      <div>
        <h1 class="text-3xl font-bold text-dark flex items-center gap-3">
          Layanan Freelancer Anda
          <?php if ($isFreelancer): ?>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 text-green-700 rounded-lg text-xs font-bold border border-green-200 uppercase tracking-widest">
            <span class="w-2 h-2 rounded-full bg-green-500"></span> Aktif
          </span>
          <?php endif; ?>
        </h1>
        <p class="text-gray-500 mt-2">Buat berbagai paket keahlian berdasarkan kategori agar mudah dijangkau oleh klien.</p>
      </div>
      
      <div class="flex items-center gap-3 w-full sm:w-auto">
        <?php if ($isFreelancer): ?>
        <a href="layanan.php?user=<?= $userId ?>" target="_blank" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-dark rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2 flex-1 sm:flex-none whitespace-nowrap hidden md:flex">
          Lihat Profil
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
        </a>
        <?php endif; ?>
        <button onclick="openModal()" class="px-5 py-2.5 bg-accent hover:bg-orange-600 text-white rounded-xl text-sm font-bold shadow-md shadow-accent/30 transition-all flex items-center justify-center gap-2 flex-1 sm:flex-none whitespace-nowrap">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
          Tambah Layanan Baru
        </button>
      </div>
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

    <!-- Layanan Cards List -->
    <div class="space-y-4">
        <?php if (!$isFreelancer): ?>
        <div class="text-center py-20 bg-white border border-gray-100 border-dashed rounded-3xl">
          <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13V6a2 2 0 00-2-2h-3m-6 0H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
          <p class="text-lg font-bold text-gray-400">Belum ada layanan yang ditambahkan.</p>
          <p class="text-gray-500 text-sm mt-1">Gunakan tombol "Tambah Layanan Baru" untuk mulai menawarkan jasa.</p>
        </div>
        <?php else: ?>
            <?php foreach($myCategories as $cat): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col md:flex-row gap-6 items-start md:items-center hover:shadow-md transition-all">
               <div class="w-16 h-16 shrink-0 bg-primary/10 rounded-xl flex items-center justify-center text-primary/70">
                   <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
               </div>
               <div class="flex-grow">
                   <div class="flex items-center gap-3 mb-2">
                     <span class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider bg-gray-100 text-gray-500 rounded-md">Kategori Keahlian</span>
                   </div>
                   <h2 class="text-2xl font-bold text-dark mb-1"><?= htmlspecialchars($cat['nama_kategori']) ?></h2>
                   <p class="text-sm font-medium text-accent mb-4"><?= htmlspecialchars($cat['jasa_names']) ?></p>
                   
                   <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 leading-relaxed text-sm text-gray-600 line-clamp-2">
                       <?= htmlspecialchars($cat['deskripsi'] ?: 'Tidak ada deskripsi.') ?>
                   </div>
               </div>
               
               <div class="md:w-56 shrink-0 w-full flex flex-row md:flex-col justify-between md:justify-center items-center md:items-end border-t md:border-t-0 md:border-l border-gray-100 pt-5 md:pt-0 pl-0 md:pl-6 gap-3">
                   <div class="text-left md:text-right">
                       <p class="text-xs font-bold text-gray-400 mb-0.5 uppercase tracking-wide">Harga Dasar</p>
                       <p class="text-xl font-black text-dark">Rp <?= number_format($cat['tarif'], 0, ',', '.') ?></p>
                       <p class="text-xs text-gray-400 mt-0.5">/ <?= htmlspecialchars($cat['nama_satuan'] ?: 'Pesanan') ?></p>
                   </div>
                   
                   <div class="flex gap-2">
                       <button onclick="openModalEdit(<?= htmlspecialchars(json_encode([
                           'id_kategori' => $cat['id_kategori'],
                           'tarif' => $cat['tarif'],
                           'id_satuan' => $cat['id_satuan'],
                           'deskripsi' => $cat['deskripsi'],
                           'arr_jasa' => json_decode($cat['jasa_ids_json'], true)
                       ])) ?>)" class="p-2.5 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors border border-blue-200/50" title="Edit Layanan">
                           <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                       </button>
                       <form method="POST" action="kelola-jasa.php" onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh layanan dalam kategori ini?');">
                           <input type="hidden" name="action" value="delete">
                           <input type="hidden" name="id_kategori" value="<?= $cat['id_kategori'] ?>">
                           <button type="submit" class="p-2.5 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors border border-red-200/50" title="Hapus Layanan">
                               <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                           </button>
                       </form>
                   </div>
               </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-gray-50 pt-12 pb-8 mt-auto border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 text-center text-sm text-gray-500">
      <p>&copy; 2026 WorkLance Indonesia.</p>
    </div>
  </footer>

  <!-- MODAL: ADD/EDIT FORM (Retaining Original UI Design) -->
  <div id="serviceModal" class="fixed inset-0 z-[100] hidden">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-dark/60 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
      
      <!-- Modal Content Wrapper (Scrollable) -->
      <div class="absolute inset-0 overflow-y-auto px-4 py-10 flex items-start justify-center">
          <div class="bg-white rounded-3xl shadow-[0_20px_50px_-12px_rgba(0,0,0,0.5)] w-full max-w-4xl border border-gray-100 overflow-hidden transform transition-all flex flex-col my-auto relative">
              
              <!-- Modal Header -->
              <div class="bg-primary/5 p-6 md:px-10 border-b border-primary/10 flex items-center justify-between sticky top-0 z-10 backdrop-blur-xl">
                  <div class="flex items-center gap-4">
                      <div class="w-14 h-14 bg-white rounded-2xl shadow-sm flex items-center justify-center text-primary border border-gray-100">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                      </div>
                      <div>
                        <h2 class="text-xl font-bold text-dark" id="modalTitle">Tambah Layanan Baru</h2>
                        <p class="text-sm text-gray-500">Formulir konfigurasi jasa Anda</p>
                      </div>
                  </div>
                  <button onclick="closeModal()" class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-red-100 hover:text-red-500 transition-colors tooltip" aria-label="Tutup">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                  </button>
              </div>

              <!-- Form Body (Origin Design Preserved) -->
              <form method="POST" action="kelola-jasa.php" class="p-6 md:p-10 space-y-8 bg-white relative">
                  <input type="hidden" name="action" value="save">
                  
                  <!-- Kategori & Harga -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                      <label class="flex items-center gap-2 text-sm font-bold text-dark mb-3">
                        <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        Kategori Keahlian <span class="text-red-500">*</span>
                      </label>
                      <select name="id_kategori" id="selectKategori" required class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark transition-colors cursor-pointer">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($kategoriList as $kat): ?>
                        <option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <p class="text-xs text-gray-400 mt-2">Peringatan: Mengedit kategori yang sama akan menimpa pilihan jasa yang lama.</p>
                    </div>

                    <div>
                      <label class="flex items-center gap-2 text-sm font-bold text-dark mb-3">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Harga Dasar / Min Order <span class="text-red-500">*</span>
                      </label>
                      <div class="flex shadow-sm rounded-xl overflow-hidden">
                        <span class="inline-flex items-center px-4 border border-r-0 border-gray-200 bg-gray-100 text-gray-600 font-bold text-sm">Rp</span>
                        <input type="number" name="tarif" id="inputTarif" placeholder="Contoh: 150000" required class="w-full border border-gray-200 px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark transition-colors z-10 appearance-none">
                        <span class="inline-flex items-center px-3 border border-l-0 border-r-0 border-gray-200 bg-gray-100 text-gray-400 text-sm">/</span>
                        <select name="id_satuan" id="selectSatuan" class="border border-gray-200 px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark transition-colors z-10 cursor-pointer" style="min-width: 120px;">
                          <option value="">Satuan</option>
                          <?php foreach ($satuanList as $st): ?>
                          <option value="<?= $st['id_satuan'] ?>"><?= htmlspecialchars($st['nama_satuan']) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                  </div>

                  <!-- Checklist Jasa -->
                  <div id="jasaContainerWrapper" class="pt-2 hidden">
                    <label class="flex items-center gap-2 text-sm font-bold text-dark mb-3">
                      <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                      Tandai Jasa yang Anda Sediakan <span class="text-red-500">*</span>
                    </label>
                    <div class="bg-gray-50 p-6 rounded-2xl border border-gray-200">
                      <div id="jasaCheckboxList" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <?php foreach ($jasaList as $js): ?>
                        <label class="jasa-item flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-200 cursor-pointer hover:border-accent/40 transition-all" data-kategori="<?= $js['id_kategori'] ?>">
                          <input type="checkbox" name="jasa[]" value="<?= $js['id_jasa'] ?>" class="jasa-checkbox w-5 h-5 text-accent rounded border-gray-300 focus:ring-accent transition-colors accent-accent">
                          <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($js['nama_jasa']) ?></span>
                        </label>
                        <?php endforeach; ?>
                      </div>
                      <p id="noJasaMsg" class="text-sm text-gray-500 hidden py-4 text-center">Tidak ada sub-jasa di kategori ini.</p>
                    </div>
                  </div>

                  <!-- Deskripsi -->
                  <div>
                    <label class="flex items-center gap-2 text-sm font-bold text-dark mb-3">
                      <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                      Deskripsi Profil
                    </label>
                    <textarea name="deskripsi" id="inputDeskripsi" rows="6" placeholder="Beri gambaran detail apa saja yang Anda kerjakan di kategori ini, prosedur kerja, dll..." required class="w-full border border-gray-200 rounded-2xl px-5 py-4 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white text-sm font-medium text-dark resize-none transition-colors"></textarea>
                  </div>

                  <div class="pt-6 flex flex-col sm:flex-row items-center justify-end gap-3 border-t border-gray-100">
                    <button type="button" onclick="closeModal()" class="w-full sm:w-auto px-8 py-3.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-colors cursor-pointer text-center">Batal</button>
                    <button type="submit" class="w-full sm:w-auto px-10 bg-accent hover:bg-orange-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-accent/30 transition-all transform hover:-translate-y-0.5 text-lg cursor-pointer flex items-center justify-center gap-2">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                      Simpan Layanan
                    </button>
                  </div>
              </form>
              
          </div>
      </div>
  </div>

  <script>
    // Elements
    const modal = document.getElementById('serviceModal');
    const modalTitle = document.getElementById('modalTitle');
    const selKategori = document.getElementById('selectKategori');
    const inputTarif = document.getElementById('inputTarif');
    const selectSatuan = document.getElementById('selectSatuan');
    const inputDeskripsi = document.getElementById('inputDeskripsi');
    const containerWrapper = document.getElementById('jasaContainerWrapper');
    const items = document.querySelectorAll('.jasa-item');
    const checkboxes = document.querySelectorAll('.jasa-checkbox');
    const noJasaMsg = document.getElementById('noJasaMsg');

    function resetForm() {
        modalTitle.innerText = "Tambah Layanan Baru";
        selKategori.value = '';
        inputTarif.value = '';
        selectSatuan.value = '';
        inputDeskripsi.value = '';
        selKategori.disabled = false; // allows changing category if it's a new entry
        updateCheckboxesVis();
        checkboxes.forEach(chk => { chk.checked = false; triggerCheckboxStyling(chk); });
    }

    function triggerCheckboxStyling(input) {
        const label = input.closest('.jasa-item');
        if (input.checked) {
          label.classList.add('border-accent', 'shadow-sm', 'ring-1', 'ring-accent/30');
          label.classList.remove('border-gray-200');
        } else {
          label.classList.remove('border-accent', 'shadow-sm', 'ring-1', 'ring-accent/30');
          label.classList.add('border-gray-200');
        }
    }

    function updateCheckboxesVis() {
      const katId = selKategori.value;
      let count = 0;
      
      if (!katId) {
        containerWrapper.classList.add('hidden');
        items.forEach(el => {
          el.querySelector('input').checked = false; // uncheck all if no cat
          triggerCheckboxStyling(el.querySelector('input'));
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
          triggerCheckboxStyling(el.querySelector('input'));
        }
      });

      noJasaMsg.style.display = (count === 0) ? 'block' : 'none';
    }

    selKategori.addEventListener('change', updateCheckboxesVis);

    checkboxes.forEach(input => {
      input.addEventListener('change', () => { triggerCheckboxStyling(input); });
    });

    function openModal() {
        resetForm();
        modal.classList.remove('hidden');
        document.body.classList.add('modal-open');
    }

    function openModalEdit(data) {
        resetForm();
        modalTitle.innerText = "Edit Kategori Layanan";
        selKategori.value = data.id_kategori;
        
        // Lock other options so they can't change category on Edit mode
        Array.from(selKategori.options).forEach(opt => { 
            if (opt.value && opt.value != data.id_kategori) opt.disabled = true; 
        });

        inputTarif.value = data.tarif;
        selectSatuan.value = data.id_satuan;
        inputDeskripsi.value = data.deskripsi;
        
        updateCheckboxesVis();
        
        // Tick the correct ones
        if(data.arr_jasa && Array.isArray(data.arr_jasa)) {
            checkboxes.forEach(chk => {
                if (data.arr_jasa.includes(parseInt(chk.value) || chk.value.toString())) {
                    chk.checked = true;
                    triggerCheckboxStyling(chk);
                }
            });
        }
        
        modal.classList.remove('hidden');
        document.body.classList.add('modal-open');
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.classList.remove('modal-open');
        Array.from(selKategori.options).forEach(opt => { opt.disabled = false; });
    }
  </script>
</body>
</html>