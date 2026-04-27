<?php
require_once __DIR__ . '/../config/database.php';
requireAdminLogin();

$currentPage = 'kelola';
$adminNama = $_SESSION['admin_nama'] ?? 'Admin';
$success = '';
$error = '';

// ============ HANDLE KATEGORI ACTIONS ============

// Hapus Kategori
if (isset($_GET['hapus_kategori'])) {
    $id = (int)$_GET['hapus_kategori'];
    // Cek apakah ada jasa atau freelancer yang masih pakai kategori ini
    $count = $pdo->prepare("SELECT COUNT(*) FROM jasa WHERE id_kategori = ?");
    $count->execute([$id]);
    if ($count->fetchColumn() > 0) {
        $error = 'Kategori tidak bisa dihapus karena masih memiliki data jasa. Hapus jasa terlebih dahulu.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM kategori WHERE id_kategori = ?");
        $stmt->execute([$id]);
        $success = 'Kategori berhasil dihapus.';
    }
}

// Tambah/Edit Kategori
if (isset($_POST['simpan_kategori'])) {
    $id_kategori = $_POST['id_kategori'] ?? '';
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');

    if (empty($nama_kategori)) {
        $error = 'Nama kategori wajib diisi.';
    } else {
        if ($id_kategori) {
            $stmt = $pdo->prepare("UPDATE kategori SET nama_kategori = ? WHERE id_kategori = ?");
            $stmt->execute([$nama_kategori, $id_kategori]);
            $success = 'Kategori berhasil diperbarui.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
            $stmt->execute([$nama_kategori]);
            $success = 'Kategori berhasil ditambahkan.';
        }
    }
}

// ============ HANDLE JASA ACTIONS ============

// Hapus Jasa
if (isset($_GET['hapus_jasa'])) {
    $id = (int)$_GET['hapus_jasa'];
    $stmt = $pdo->prepare("DELETE FROM jasa WHERE id_jasa = ?");
    $stmt->execute([$id]);
    $success = 'Jasa berhasil dihapus.';
}

// Tambah/Edit Jasa
if (isset($_POST['simpan_jasa'])) {
    $id_jasa = $_POST['id_jasa'] ?? '';
    $id_kategori = (int)($_POST['id_kategori_jasa'] ?? 0);
    $nama_jasa = trim($_POST['nama_jasa'] ?? '');

    if (empty($nama_jasa) || !$id_kategori) {
        $error = 'Nama jasa dan kategori wajib diisi.';
    } else {
        if ($id_jasa) {
            $stmt = $pdo->prepare("UPDATE jasa SET id_kategori = ?, nama_jasa = ? WHERE id_jasa = ?");
            $stmt->execute([$id_kategori, $nama_jasa, $id_jasa]);
            $success = 'Jasa berhasil diperbarui.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO jasa (id_kategori, nama_jasa) VALUES (?, ?)");
            $stmt->execute([$id_kategori, $nama_jasa]);
            $success = 'Jasa berhasil ditambahkan.';
        }
    }
}

// ============ HANDLE SATUAN ACTIONS ============

// Hapus Satuan
if (isset($_GET['hapus_satuan'])) {
    $id = (int)$_GET['hapus_satuan'];
    $stmt = $pdo->prepare("DELETE FROM satuan WHERE id_satuan = ?");
    $stmt->execute([$id]);
    $success = 'Satuan berhasil dihapus.';
}

// Tambah/Edit Satuan
if (isset($_POST['simpan_satuan'])) {
    $id_satuan = $_POST['id_satuan'] ?? '';
    $nama_satuan = trim($_POST['nama_satuan'] ?? '');

    if (empty($nama_satuan)) {
        $error = 'Nama satuan wajib diisi.';
    } else {
        if ($id_satuan) {
            $stmt = $pdo->prepare("UPDATE satuan SET nama_satuan = ? WHERE id_satuan = ?");
            $stmt->execute([$nama_satuan, $id_satuan]);
            $success = 'Satuan berhasil diperbarui.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO satuan (nama_satuan) VALUES (?)");
            $stmt->execute([$nama_satuan]);
            $success = 'Satuan berhasil ditambahkan.';
        }
    }
}

// AMBIL DATA
$kategoriList = $pdo->query("SELECT * FROM kategori ORDER BY id_kategori ASC")->fetchAll();
$jasaList = $pdo->query("
    SELECT j.*, k.nama_kategori 
    FROM jasa j 
    LEFT JOIN kategori k ON j.id_kategori = k.id_kategori 
    ORDER BY j.id_jasa ASC
")->fetchAll();
$satuanList = $pdo->query("SELECT * FROM satuan ORDER BY id_satuan ASC")->fetchAll();

// Count jasa per kategori
$jasaCountPerKategori = [];
$stmtCount = $pdo->query("SELECT id_kategori, COUNT(*) as total FROM jasa GROUP BY id_kategori");
foreach ($stmtCount->fetchAll() as $row) {
    $jasaCountPerKategori[$row['id_kategori']] = $row['total'];
}

// Edit data
$editKategori = null;
if (isset($_GET['edit_kategori'])) {
    $stmt = $pdo->prepare("SELECT * FROM kategori WHERE id_kategori = ?");
    $stmt->execute([(int)$_GET['edit_kategori']]);
    $editKategori = $stmt->fetch();
}

$editJasa = null;
if (isset($_GET['edit_jasa'])) {
    $stmt = $pdo->prepare("SELECT * FROM jasa WHERE id_jasa = ?");
    $stmt->execute([(int)$_GET['edit_jasa']]);
    $editJasa = $stmt->fetch();
}

$editSatuan = null;
if (isset($_GET['edit_satuan'])) {
    $stmt = $pdo->prepare("SELECT * FROM satuan WHERE id_satuan = ?");
    $stmt->execute([(int)$_GET['edit_satuan']]);
    $editSatuan = $stmt->fetch();
}

// Active tab
$activeTab = $_GET['tab'] ?? 'kategori';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Kategori | Admin WorkLance</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/WorkLance/src/output.css">
</head>
<body class="bg-gray-50 font-sans text-gray-800 h-screen flex overflow-hidden">

  <!-- Sidebar -->
  <aside class="w-72 bg-dark text-white flex flex-col hidden md:flex flex-shrink-0 z-20">
    <div class="h-20 flex items-center px-8 border-b border-white/10 shrink-0">
      <a href="../index.html" class="flex items-center gap-2 group">
        <div class="w-10 h-10 bg-white text-dark rounded-xl flex items-center justify-center font-bold text-xl group-hover:scale-105 transition-transform duration-300 shadow-md">W</div>
        <span class="text-2xl font-bold tracking-tight">Work<span class="text-accent">Lance</span></span>
      </a>
      <span class="ml-2 px-2 py-0.5 text-[10px] font-bold bg-accent rounded-md uppercase tracking-wide">Admin</span>
    </div>
    <div class="flex-1 overflow-y-auto py-6 px-4 space-y-1.5 scrollbar-hide">
      <div class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-4">Utama</div>
      <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-400 hover:bg-white/5 hover:text-white rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
        Dashboard
      </a>
      <a href="pengguna.php" class="flex items-center gap-3 px-4 py-3 text-gray-400 hover:bg-white/5 hover:text-white rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
        Pengguna
      </a>
      <a href="freelancer.php" class="flex items-center gap-3 px-4 py-3 text-gray-400 hover:bg-white/5 hover:text-white rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
        Freelancer
      </a>
      <a href="booking.php" class="flex items-center gap-3 px-4 py-3 text-gray-400 hover:bg-white/5 hover:text-white rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
        Booking
      </a>
      <a href="pengajuan.php" class="flex items-center gap-3 px-4 py-3 text-gray-400 hover:bg-white/5 hover:text-white rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
        Pengajuan
      </a>
      <div class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-8">Sistem</div>
      <a href="kelola.php" class="flex items-center gap-3 px-4 py-3 bg-white/10 text-white border border-white/5 rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
        Kelola
      </a>
    </div>
    <div class="p-4 border-t border-white/10 shrink-0">
      <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-400 hover:bg-red-500/10 hover:text-red-300 rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
        Logout
      </a>
    </div>
  </aside>

  <!-- Main View -->
  <main class="flex-1 flex flex-col min-w-0 bg-gray-50/50 relative overflow-x-hidden">
    <div class="absolute top-0 right-0 w-96 h-96 bg-primary/10 rounded-full blur-3xl -z-10 -translate-y-1/2 translate-x-1/2"></div>

    <header class="h-20 bg-white/80 backdrop-blur-md border-b border-gray-100 flex items-center justify-between px-6 lg:px-10 sticky top-0 z-30 shrink-0">
      <div class="flex-1">
        <h2 class="text-lg font-bold text-dark">Kategori, Jasa & Satuan</h2>
      </div>
      <div class="flex items-center gap-3 md:gap-5 ml-auto">
        <div class="flex items-center gap-3 cursor-pointer group">
          <div class="w-10 h-10 rounded-full bg-accent text-white flex items-center justify-center font-bold text-sm ring-2 ring-gray-100"><?= getInitials($adminNama) ?></div>
          <div class="hidden md:block text-sm">
            <p class="font-bold text-dark leading-tight"><?= htmlspecialchars($adminNama) ?></p>
            <p class="text-gray-500 text-xs font-medium">Super Admin</p>
          </div>
        </div>
      </div>
    </header>

    <div class="flex-1 overflow-y-auto p-6 lg:p-10 pb-20">

      <div class="mb-8">
        <h1 class="text-3xl font-bold text-dark mb-1">Kelola Kategori & Jasa</h1>
        <p class="text-gray-500">Atur kategori layanan, jasa, dan satuan tarif.</p>
      </div>

      <?php if ($success): ?>
      <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm font-medium flex items-center gap-2">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <?= htmlspecialchars($success) ?>
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-xl text-sm font-medium flex items-center gap-2">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <!-- Tabs -->
      <div class="flex gap-1 mb-8 bg-gray-100 rounded-xl p-1 w-fit">
        <a href="kelola.php?tab=kategori" class="px-5 py-2.5 rounded-lg text-sm font-bold transition-colors <?= $activeTab === 'kategori' ? 'bg-white text-dark shadow-sm' : 'text-gray-500 hover:text-dark' ?>">Kategori</a>
        <a href="kelola.php?tab=jasa" class="px-5 py-2.5 rounded-lg text-sm font-bold transition-colors <?= $activeTab === 'jasa' ? 'bg-white text-dark shadow-sm' : 'text-gray-500 hover:text-dark' ?>">Jasa</a>
        <a href="kelola.php?tab=satuan" class="px-5 py-2.5 rounded-lg text-sm font-bold transition-colors <?= $activeTab === 'satuan' ? 'bg-white text-dark shadow-sm' : 'text-gray-500 hover:text-dark' ?>">Satuan</a>
      </div>

      <?php if ($activeTab === 'kategori'): ?>
      <!-- ============ KATEGORI TAB ============ -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <h3 class="font-bold text-dark text-lg mb-4"><?= $editKategori ? 'Edit Kategori' : 'Tambah Kategori' ?></h3>
          <form method="POST" action="kelola.php?tab=kategori" class="space-y-4">
            <input type="hidden" name="simpan_kategori" value="1">
            <input type="hidden" name="id_kategori" value="<?= $editKategori['id_kategori'] ?? '' ?>">
            <div>
              <label class="block text-sm font-bold text-dark mb-1.5">Nama Kategori <span class="text-red-500">*</span></label>
              <input type="text" name="nama_kategori" value="<?= htmlspecialchars($editKategori['nama_kategori'] ?? '') ?>" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white text-sm text-dark font-medium" placeholder="cth: Desain & Kreatif">
            </div>
            <div class="flex gap-3">
              <?php if ($editKategori): ?>
              <a href="kelola.php?tab=kategori" class="flex-1 py-2.5 text-center text-sm font-bold text-gray-500 hover:bg-gray-50 rounded-xl border border-gray-200 transition-colors">Batal</a>
              <?php endif; ?>
              <button type="submit" class="flex-1 py-2.5 bg-accent text-white text-sm font-bold rounded-xl shadow-md hover:bg-orange-700 transition-colors cursor-pointer"><?= $editKategori ? 'Perbarui' : 'Simpan' ?></button>
            </div>
          </form>
        </div>

        <!-- List -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="font-bold text-dark text-lg">Daftar Kategori (<?= count($kategoriList) ?>)</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider">
                  <th class="p-4 pl-6 font-semibold">ID</th>
                  <th class="p-4 font-semibold">Nama Kategori</th>
                  <th class="p-4 font-semibold">Jumlah Jasa</th>
                  <th class="p-4 font-semibold pr-6 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <?php if (empty($kategoriList)): ?>
                <tr><td colspan="4" class="p-8 text-center text-gray-400">Belum ada data kategori.</td></tr>
                <?php else: ?>
                <?php foreach ($kategoriList as $kat): ?>
                <tr class="hover:bg-gray-50/50 transition-colors">
                  <td class="p-4 pl-6 text-sm text-gray-500"><?= $kat['id_kategori'] ?></td>
                  <td class="p-4">
                    <div class="flex items-center gap-3">
                      <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                      </div>
                      <p class="font-bold text-dark text-sm"><?= htmlspecialchars($kat['nama_kategori']) ?></p>
                    </div>
                  </td>
                  <td class="p-4 text-sm text-gray-500"><?= $jasaCountPerKategori[$kat['id_kategori']] ?? 0 ?> jasa</td>
                  <td class="p-4 pr-6 text-right">
                    <div class="flex items-center justify-end gap-2">
                      <a href="kelola.php?tab=kategori&edit_kategori=<?= $kat['id_kategori'] ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                      </a>
                      <a href="kelola.php?tab=kategori&hapus_kategori=<?= $kat['id_kategori'] ?>" onclick="return confirm('Yakin ingin menghapus kategori ini?')" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                      </a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <?php elseif ($activeTab === 'jasa'): ?>
      <!-- ============ JASA TAB ============ -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <h3 class="font-bold text-dark text-lg mb-4"><?= $editJasa ? 'Edit Jasa' : 'Tambah Jasa' ?></h3>
          <form method="POST" action="kelola.php?tab=jasa" class="space-y-4">
            <input type="hidden" name="simpan_jasa" value="1">
            <input type="hidden" name="id_jasa" value="<?= $editJasa['id_jasa'] ?? '' ?>">
            <div>
              <label class="block text-sm font-bold text-dark mb-1.5">Kategori <span class="text-red-500">*</span></label>
              <select name="id_kategori_jasa" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white text-sm text-dark font-medium">
                <option value="">-- Pilih Kategori --</option>
                <?php foreach ($kategoriList as $kat): ?>
                <option value="<?= $kat['id_kategori'] ?>" <?= ($editJasa['id_kategori'] ?? '') == $kat['id_kategori'] ? 'selected' : '' ?>><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="block text-sm font-bold text-dark mb-1.5">Nama Jasa <span class="text-red-500">*</span></label>
              <input type="text" name="nama_jasa" value="<?= htmlspecialchars($editJasa['nama_jasa'] ?? '') ?>" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white text-sm text-dark font-medium" placeholder="cth: Desain Logo">
            </div>
            <div class="flex gap-3">
              <?php if ($editJasa): ?>
              <a href="kelola.php?tab=jasa" class="flex-1 py-2.5 text-center text-sm font-bold text-gray-500 hover:bg-gray-50 rounded-xl border border-gray-200 transition-colors">Batal</a>
              <?php endif; ?>
              <button type="submit" class="flex-1 py-2.5 bg-accent text-white text-sm font-bold rounded-xl shadow-md hover:bg-orange-700 transition-colors cursor-pointer"><?= $editJasa ? 'Perbarui' : 'Simpan' ?></button>
            </div>
          </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="font-bold text-dark text-lg">Daftar Jasa (<?= count($jasaList) ?>)</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider">
                  <th class="p-4 pl-6 font-semibold">ID</th>
                  <th class="p-4 font-semibold">Nama Jasa</th>
                  <th class="p-4 font-semibold">Kategori</th>
                  <th class="p-4 font-semibold pr-6 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <?php if (empty($jasaList)): ?>
                <tr><td colspan="4" class="p-8 text-center text-gray-400">Belum ada data jasa.</td></tr>
                <?php else: ?>
                <?php foreach ($jasaList as $js): ?>
                <tr class="hover:bg-gray-50/50 transition-colors">
                  <td class="p-4 pl-6 text-sm text-gray-500"><?= $js['id_jasa'] ?></td>
                  <td class="p-4 font-bold text-dark text-sm"><?= htmlspecialchars($js['nama_jasa']) ?></td>
                  <td class="p-4">
                    <span class="px-3 py-1 bg-blue-50 text-blue-600 border border-blue-200 rounded-full text-[11px] font-bold inline-block"><?= htmlspecialchars($js['nama_kategori'] ?? '-') ?></span>
                  </td>
                  <td class="p-4 pr-6 text-right">
                    <div class="flex items-center justify-end gap-2">
                      <a href="kelola.php?tab=jasa&edit_jasa=<?= $js['id_jasa'] ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                      </a>
                      <a href="kelola.php?tab=jasa&hapus_jasa=<?= $js['id_jasa'] ?>" onclick="return confirm('Yakin ingin menghapus jasa ini?')" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                      </a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <?php elseif ($activeTab === 'satuan'): ?>
      <!-- ============ SATUAN TAB ============ -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <h3 class="font-bold text-dark text-lg mb-4"><?= $editSatuan ? 'Edit Satuan' : 'Tambah Satuan' ?></h3>
          <form method="POST" action="kelola.php?tab=satuan" class="space-y-4">
            <input type="hidden" name="simpan_satuan" value="1">
            <input type="hidden" name="id_satuan" value="<?= $editSatuan['id_satuan'] ?? '' ?>">
            <div>
              <label class="block text-sm font-bold text-dark mb-1.5">Nama Satuan <span class="text-red-500">*</span></label>
              <input type="text" name="nama_satuan" value="<?= htmlspecialchars($editSatuan['nama_satuan'] ?? '') ?>" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white text-sm text-dark font-medium" placeholder="cth: Per Jam">
            </div>
            <div class="flex gap-3">
              <?php if ($editSatuan): ?>
              <a href="kelola.php?tab=satuan" class="flex-1 py-2.5 text-center text-sm font-bold text-gray-500 hover:bg-gray-50 rounded-xl border border-gray-200 transition-colors">Batal</a>
              <?php endif; ?>
              <button type="submit" class="flex-1 py-2.5 bg-accent text-white text-sm font-bold rounded-xl shadow-md hover:bg-orange-700 transition-colors cursor-pointer"><?= $editSatuan ? 'Perbarui' : 'Simpan' ?></button>
            </div>
          </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="font-bold text-dark text-lg">Daftar Satuan (<?= count($satuanList) ?>)</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider">
                  <th class="p-4 pl-6 font-semibold">ID</th>
                  <th class="p-4 font-semibold">Nama Satuan</th>
                  <th class="p-4 font-semibold pr-6 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <?php if (empty($satuanList)): ?>
                <tr><td colspan="3" class="p-8 text-center text-gray-400">Belum ada data satuan.</td></tr>
                <?php else: ?>
                <?php foreach ($satuanList as $st): ?>
                <tr class="hover:bg-gray-50/50 transition-colors">
                  <td class="p-4 pl-6 text-sm text-gray-500"><?= $st['id_satuan'] ?></td>
                  <td class="p-4 font-bold text-dark text-sm"><?= htmlspecialchars($st['nama_satuan']) ?></td>
                  <td class="p-4 pr-6 text-right">
                    <div class="flex items-center justify-end gap-2">
                      <a href="kelola.php?tab=satuan&edit_satuan=<?= $st['id_satuan'] ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                      </a>
                      <a href="kelola.php?tab=satuan&hapus_satuan=<?= $st['id_satuan'] ?>" onclick="return confirm('Yakin ingin menghapus satuan ini?')" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                      </a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </main>

</body>
</html>
