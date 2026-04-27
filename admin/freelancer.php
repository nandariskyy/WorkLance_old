<?php
require_once __DIR__ . '/../config/database.php';
requireAdminLogin();

$currentPage = 'freelancer';
$adminNama = $_SESSION['admin_nama'] ?? 'Admin';
$success = '';
$error = '';

// ============ HANDLE ACTIONS ============

// HAPUS FREELANCER
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM layanan WHERE id_layanan = ?");
    $stmt->execute([$id]);
    $success = 'Data freelancer berhasil dihapus.';
}

// TAMBAH / EDIT FREELANCER
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_layanan = $_POST['id_layanan'] ?? '';
    $id_pengguna = (int)($_POST['id_pengguna'] ?? 0);
    $id_jasa = (int)($_POST['id_jasa'] ?? 0);
    $id_satuan = (int)($_POST['id_satuan'] ?? 0);
    $tarif = trim($_POST['tarif'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if (!$id_pengguna || !$id_jasa) {
        $error = 'Pengguna dan jasa wajib dipilih.';
    } else {
        if ($id_layanan) {
            $stmt = $pdo->prepare("UPDATE layanan SET id_pengguna=?, id_jasa=?, id_satuan=?, tarif=?, deskripsi=? WHERE id_layanan=?");
            $stmt->execute([$id_pengguna, $id_jasa, $id_satuan ?: null, $tarif, $deskripsi, $id_layanan]);
            $success = 'Data freelancer berhasil diperbarui.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO layanan (id_pengguna, id_jasa, id_satuan, tarif, deskripsi) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_pengguna, $id_jasa, $id_satuan ?: null, $tarif, $deskripsi]);
            $success = 'Data freelancer berhasil ditambahkan.';
        }
    }
}

// AMBIL DATA EDIT
$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("
        SELECT l.*, j.id_kategori 
        FROM layanan l 
        JOIN jasa j ON l.id_jasa = j.id_jasa 
        WHERE l.id_layanan = ?
    ");
    $stmt->execute([(int)$_GET['edit']]);
    $editData = $stmt->fetch();
}

// AMBIL SEMUA FREELANCER
$search = $_GET['search'] ?? '';
$filterKategori = $_GET['kategori'] ?? '';

$where = "1=1";
$params = [];
if ($search !== '') {
    $where .= " AND (p.nama_pengguna LIKE ? OR j.nama_jasa LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filterKategori !== '') {
    $where .= " AND j.id_kategori = ?";
    $params[] = (int)$filterKategori;
}

$stmt = $pdo->prepare("
    SELECT l.*, p.nama_pengguna, p.email, p.no_telp, k.nama_kategori, j.nama_jasa, s.nama_satuan
    FROM layanan l
    LEFT JOIN pengguna p ON l.id_pengguna = p.id_pengguna
    LEFT JOIN jasa j ON l.id_jasa = j.id_jasa
    LEFT JOIN kategori k ON j.id_kategori = k.id_kategori
    LEFT JOIN satuan s ON l.id_satuan = s.id_satuan
    WHERE $where
    ORDER BY l.id_layanan DESC
");
$stmt->execute($params);
$freelancerList = $stmt->fetchAll();

// Data dropdown
$penggunaFreelancer = $pdo->query("SELECT id_pengguna, nama_pengguna FROM pengguna WHERE id_role = 3 ORDER BY nama_pengguna")->fetchAll();
$kategoriList = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();
$jasaList = $pdo->query("SELECT * FROM jasa ORDER BY nama_jasa")->fetchAll();
$satuanList = $pdo->query("SELECT * FROM satuan ORDER BY nama_satuan")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Freelancer | Admin WorkLance</title>
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
      <a href="freelancer.php" class="flex items-center gap-3 px-4 py-3 bg-white/10 text-white border border-white/5 rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
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
      <a href="kelola.php" class="flex items-center gap-3 px-4 py-3 text-gray-400 hover:bg-white/5 hover:text-white rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
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
      <div class="hidden sm:flex flex-1 max-w-lg items-center relative">
        <svg class="w-5 h-5 text-gray-400 absolute left-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        <form method="GET" class="w-full">
          <?php if ($filterKategori): ?><input type="hidden" name="kategori" value="<?= $filterKategori ?>"><?php endif; ?>
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari freelancer..."
            class="w-full bg-gray-100/50 border border-transparent focus:border-gray-200 hover:bg-gray-100 rounded-full pl-12 pr-4 py-2.5 text-sm outline-none transition-all placeholder-gray-400 text-dark">
        </form>
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

      <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-dark mb-1">Kelola Freelancer</h1>
          <p class="text-gray-500">Total <?= count($freelancerList) ?> freelancer terdaftar.</p>
        </div>
        <button onclick="document.getElementById('modalForm').classList.remove('hidden')" class="px-5 py-2.5 bg-accent text-white rounded-xl text-sm font-bold shadow-md hover:bg-orange-700 transition-colors flex items-center gap-2 cursor-pointer">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
          Tambah Freelancer
        </button>
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

      <!-- Filter Tabs -->
      <div class="flex gap-2 mb-6 flex-wrap">
        <a href="freelancer.php" class="px-4 py-2 rounded-lg text-sm font-bold transition-colors <?= $filterKategori === '' ? 'bg-dark text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">Semua</a>
        <?php foreach ($kategoriList as $kat): ?>
        <a href="freelancer.php?kategori=<?= $kat['id_kategori'] ?>" class="px-4 py-2 rounded-lg text-sm font-bold transition-colors <?= $filterKategori == $kat['id_kategori'] ? 'bg-dark text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">
          <?= htmlspecialchars($kat['nama_kategori']) ?>
        </a>
        <?php endforeach; ?>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse min-w-[900px]">
            <thead>
              <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider">
                <th class="p-4 pl-6 font-semibold">ID</th>
                <th class="p-4 font-semibold">Nama</th>
                <th class="p-4 font-semibold">Kategori</th>
                <th class="p-4 font-semibold">Jasa</th>
                <th class="p-4 font-semibold">Tarif</th>
                <th class="p-4 font-semibold">Satuan</th>
                <th class="p-4 font-semibold pr-6 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php if (empty($freelancerList)): ?>
              <tr><td colspan="7" class="p-8 text-center text-gray-400">Tidak ada data freelancer.</td></tr>
              <?php else: ?>
              <?php foreach ($freelancerList as $fl): ?>
              <tr class="hover:bg-gray-50/50 transition-colors">
                <td class="p-4 pl-6 text-sm text-gray-500"><?= $fl['id_layanan'] ?></td>
                <td class="p-4">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-accent/10 text-accent flex items-center justify-center font-bold text-xs"><?= getInitials($fl['nama_pengguna'] ?? 'N/A') ?></div>
                    <div>
                      <p class="font-bold text-dark text-sm whitespace-nowrap"><?= htmlspecialchars($fl['nama_pengguna'] ?? '-') ?></p>
                      <p class="text-xs text-gray-400"><?= htmlspecialchars($fl['email'] ?? '') ?></p>
                    </div>
                  </div>
                </td>
                <td class="p-4">
                  <span class="px-3 py-1 bg-blue-50 text-blue-600 border border-blue-200 rounded-full text-[11px] font-bold inline-block"><?= htmlspecialchars($fl['nama_kategori'] ?? '-') ?></span>
                </td>
                <td class="p-4 text-sm text-gray-600"><?= htmlspecialchars($fl['nama_jasa'] ?? '-') ?></td>
                <td class="p-4 text-sm font-bold text-dark"><?= $fl['tarif'] ? 'Rp ' . number_format((int)$fl['tarif'], 0, ',', '.') : '-' ?></td>
                <td class="p-4 text-sm text-gray-500"><?= htmlspecialchars($fl['nama_satuan'] ?? '-') ?></td>
                <td class="p-4 pr-6 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <a href="freelancer.php?edit=<?= $fl['id_layanan'] ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </a>
                    <a href="freelancer.php?hapus=<?= $fl['id_layanan'] ?>" onclick="return confirm('Yakin ingin menghapus layanan ini?')" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
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
  </main>

  <!-- Modal Form -->
  <div id="modalForm" class="<?= $editData ? '' : 'hidden' ?> fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
      <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-bold text-dark text-lg"><?= $editData ? 'Edit Freelancer' : 'Tambah Freelancer Baru' ?></h3>
        <a href="freelancer.php" class="p-2 text-gray-400 hover:text-dark hover:bg-gray-100 rounded-lg transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </a>
      </div>
      <form method="POST" action="freelancer.php" class="p-6 space-y-4">
        <input type="hidden" name="id_layanan" value="<?= $editData['id_layanan'] ?? '' ?>">

        <div>
          <label class="block text-sm font-bold text-dark mb-1.5">Pengguna (Role Freelancer) <span class="text-red-500">*</span></label>
          <select name="id_pengguna" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white text-sm text-dark font-medium">
            <option value="">-- Pilih Pengguna --</option>
            <?php foreach ($penggunaFreelancer as $pf): ?>
            <option value="<?= $pf['id_pengguna'] ?>" <?= ($editData['id_pengguna'] ?? '') == $pf['id_pengguna'] ? 'selected' : '' ?>><?= htmlspecialchars($pf['nama_pengguna']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-bold text-dark mb-1.5">Kategori <span class="text-red-500">*</span></label>
            <select name="id_kategori" id="selectKategori" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white text-sm text-dark font-medium">
              <option value="">-- Pilih --</option>
              <?php foreach ($kategoriList as $kat): ?>
              <option value="<?= $kat['id_kategori'] ?>" <?= ($editData['id_kategori'] ?? '') == $kat['id_kategori'] ? 'selected' : '' ?>><?= htmlspecialchars($kat['nama_kategori']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-bold text-dark mb-1.5">Jasa <span class="text-red-500">*</span></label>
            <select name="id_jasa" id="selectJasa" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white text-sm text-dark font-medium">
              <option value="">-- Pilih --</option>
              <?php foreach ($jasaList as $js): ?>
              <option value="<?= $js['id_jasa'] ?>" data-kategori="<?= $js['id_kategori'] ?>" <?= ($editData['id_jasa'] ?? '') == $js['id_jasa'] ? 'selected' : '' ?>><?= htmlspecialchars($js['nama_jasa']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-bold text-dark mb-1.5">Tarif</label>
            <input type="text" name="tarif" value="<?= htmlspecialchars($editData['tarif'] ?? '') ?>" placeholder="cth: 150000" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white text-sm text-dark font-medium">
          </div>
          <div>
            <label class="block text-sm font-bold text-dark mb-1.5">Satuan</label>
            <select name="id_satuan" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white text-sm text-dark font-medium">
              <option value="">-- Pilih --</option>
              <?php foreach ($satuanList as $st): ?>
              <option value="<?= $st['id_satuan'] ?>" <?= ($editData['id_satuan'] ?? '') == $st['id_satuan'] ? 'selected' : '' ?>><?= htmlspecialchars($st['nama_satuan']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-bold text-dark mb-1.5">Deskripsi</label>
          <textarea name="deskripsi" rows="3" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white text-sm text-dark font-medium resize-none"><?= htmlspecialchars($editData['deskripsi'] ?? '') ?></textarea>
        </div>

        <div class="flex gap-3 pt-4">
          <a href="freelancer.php" class="flex-1 py-2.5 text-center text-sm font-bold text-gray-500 hover:bg-gray-50 rounded-xl border border-gray-200 transition-colors">Batal</a>
          <button type="submit" class="flex-1 py-2.5 bg-accent text-white text-sm font-bold rounded-xl shadow-md hover:bg-orange-700 transition-colors cursor-pointer"><?= $editData ? 'Perbarui' : 'Simpan' ?></button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Filter jasa berdasarkan kategori yang dipilih
    document.getElementById('selectKategori').addEventListener('change', function() {
      const kategoriId = this.value;
      const jasaSelect = document.getElementById('selectJasa');
      const options = jasaSelect.querySelectorAll('option[data-kategori]');
      
      jasaSelect.value = '';
      options.forEach(opt => {
        if (!kategoriId || opt.dataset.kategori === kategoriId) {
          opt.style.display = '';
        } else {
          opt.style.display = 'none';
        }
      });
    });
  </script>

</body>
</html>
