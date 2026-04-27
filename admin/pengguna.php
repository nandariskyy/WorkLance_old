<?php
require_once __DIR__ . '/../config/database.php';
requireAdminLogin();

$currentPage = 'pengguna';
$adminNama = $_SESSION['admin_nama'] ?? 'Admin';
$success = '';
$error = '';

// ============ HANDLE ACTIONS ============

// HAPUS PENGGUNA
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    // Jangan hapus diri sendiri
    if ($id == $_SESSION['admin_id']) {
        $error = 'Anda tidak bisa menghapus akun sendiri.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM pengguna WHERE id_pengguna = ?");
        $stmt->execute([$id]);
        $success = 'Pengguna berhasil dihapus.';
    }
}

// TAMBAH / EDIT PENGGUNA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pengguna = $_POST['id_pengguna'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $nama_pengguna = trim($_POST['nama_pengguna'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_telp = trim($_POST['no_telp'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? null;
    $password = trim($_POST['password'] ?? '');
    $id_role = (int)($_POST['id_role'] ?? 2);

    if (empty($username) || empty($nama_pengguna) || empty($email)) {
        $error = 'Username, nama, dan email wajib diisi.';
    } else {
        if ($id_pengguna) {
            // UPDATE
            if (!empty($password)) {
                $stmt = $pdo->prepare("UPDATE pengguna SET username=?, nama_pengguna=?, email=?, no_telp=?, tanggal_lahir=?, password=?, id_role=? WHERE id_pengguna=?");
                $stmt->execute([$username, $nama_pengguna, $email, $no_telp, $tanggal_lahir ?: null, $password, $id_role, $id_pengguna]);
            } else {
                $stmt = $pdo->prepare("UPDATE pengguna SET username=?, nama_pengguna=?, email=?, no_telp=?, tanggal_lahir=?, id_role=? WHERE id_pengguna=?");
                $stmt->execute([$username, $nama_pengguna, $email, $no_telp, $tanggal_lahir ?: null, $id_role, $id_pengguna]);
            }
            $success = 'Pengguna berhasil diperbarui.';
        } else {
            // INSERT
            if (empty($password)) {
                $error = 'Password wajib diisi untuk pengguna baru.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO pengguna (username, nama_pengguna, email, no_telp, tanggal_lahir, password, id_role) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $nama_pengguna, $email, $no_telp, $tanggal_lahir ?: null, $password, $id_role]);
                $success = 'Pengguna berhasil ditambahkan.';
            }
        }
    }
}

// AMBIL DATA UNTUK EDIT
$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id_pengguna = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editData = $stmt->fetch();
}

// AMBIL DATA UNTUK DETAIL
$detailData = null;
if (isset($_GET['detail'])) {
    $stmt = $pdo->prepare("
        SELECT p.*, r.nama_role, 
               pr.nama_provinsi, kb.nama_kabupaten, 
               kc.nama_kecamatan, ds.nama_desa
        FROM pengguna p
        LEFT JOIN role r ON p.id_role = r.id_role
        LEFT JOIN provinsi pr ON p.id_provinsi = pr.id_provinsi
        LEFT JOIN kabupaten kb ON p.id_kabupaten = kb.id_kabupaten
        LEFT JOIN kecamatan kc ON p.id_kecamatan = kc.id_kecamatan
        LEFT JOIN desa ds ON p.id_desa = ds.id_desa
        WHERE p.id_pengguna = ?
    ");
    $stmt->execute([(int)$_GET['detail']]);
    $detailData = $stmt->fetch();
}

// AMBIL SEMUA PENGGUNA
$filterRole = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';

$where = "1=1";
$params = [];

if ($filterRole !== '') {
    $where .= " AND p.id_role = ?";
    $params[] = (int)$filterRole;
}
if ($search !== '') {
    $where .= " AND (p.nama_pengguna LIKE ? OR p.email LIKE ? OR p.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("
    SELECT p.*, r.nama_role
    FROM pengguna p
    LEFT JOIN role r ON p.id_role = r.id_role
    WHERE $where
    ORDER BY p.id_pengguna DESC
");
$stmt->execute($params);
$penggunaList = $stmt->fetchAll();

// Ambil daftar role untuk dropdown
$roles = $pdo->query("SELECT * FROM role ORDER BY id_role")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Pengguna | Admin WorkLance</title>
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
      <a href="pengguna.php" class="flex items-center gap-3 px-4 py-3 bg-white/10 text-white border border-white/5 rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
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

    <!-- Top Header -->
    <header class="h-20 bg-white/80 backdrop-blur-md border-b border-gray-100 flex items-center justify-between px-6 lg:px-10 sticky top-0 z-30 shrink-0">
      <div class="hidden sm:flex flex-1 max-w-lg items-center relative">
        <svg class="w-5 h-5 text-gray-400 absolute left-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        <form method="GET" class="w-full">
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari pengguna..."
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

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto p-6 lg:p-10 pb-20">

      <!-- Page Title & Action -->
      <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-dark mb-1">Kelola Pengguna</h1>
          <p class="text-gray-500">Total <?= count($penggunaList) ?> pengguna terdaftar.</p>
        </div>
        <button onclick="document.getElementById('modalForm').classList.remove('hidden')" class="px-5 py-2.5 bg-accent text-white rounded-xl text-sm font-bold shadow-md hover:bg-orange-700 transition-colors flex items-center gap-2 cursor-pointer">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
          Tambah Pengguna
        </button>
      </div>

      <!-- Alerts -->
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
        <a href="pengguna.php" class="px-4 py-2 rounded-lg text-sm font-bold transition-colors <?= $filterRole === '' ? 'bg-dark text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">Semua</a>
        <?php foreach ($roles as $r): ?>
        <a href="pengguna.php?role=<?= $r['id_role'] ?>" class="px-4 py-2 rounded-lg text-sm font-bold transition-colors <?= $filterRole == $r['id_role'] ? 'bg-dark text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">
          <?= htmlspecialchars($r['nama_role']) ?>
        </a>
        <?php endforeach; ?>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse min-w-[800px]">
            <thead>
              <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider">
                <th class="p-4 pl-6 font-semibold">ID</th>
                <th class="p-4 font-semibold">Nama</th>
                <th class="p-4 font-semibold">Username</th>
                <th class="p-4 font-semibold">Email</th>
                <th class="p-4 font-semibold">No. Telp</th>
                <th class="p-4 font-semibold">Role</th>
                <th class="p-4 font-semibold pr-6 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php if (empty($penggunaList)): ?>
              <tr><td colspan="7" class="p-8 text-center text-gray-400">Tidak ada data pengguna.</td></tr>
              <?php else: ?>
              <?php foreach ($penggunaList as $pg): ?>
              <tr class="hover:bg-gray-50/50 transition-colors">
                <td class="p-4 pl-6 text-sm text-gray-500"><?= $pg['id_pengguna'] ?></td>
                <td class="p-4">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs"><?= getInitials($pg['nama_pengguna']) ?></div>
                    <p class="font-bold text-dark text-sm whitespace-nowrap"><?= htmlspecialchars($pg['nama_pengguna']) ?></p>
                  </div>
                </td>
                <td class="p-4 text-sm text-gray-600"><?= htmlspecialchars($pg['username']) ?></td>
                <td class="p-4 text-sm text-gray-600"><?= htmlspecialchars($pg['email']) ?></td>
                <td class="p-4 text-sm text-gray-500"><?= htmlspecialchars($pg['no_telp'] ?? '-') ?></td>
                <td class="p-4">
                  <?php
                    $roleBadge = 'bg-gray-100 text-gray-600 border-gray-200';
                    if ($pg['id_role'] == 1) $roleBadge = 'bg-red-50 text-red-600 border-red-200';
                    if ($pg['id_role'] == 3) $roleBadge = 'bg-blue-50 text-blue-600 border-blue-200';
                  ?>
                  <span class="px-3 py-1 <?= $roleBadge ?> rounded-full text-[11px] font-bold border inline-block"><?= htmlspecialchars($pg['nama_role'] ?? '-') ?></span>
                </td>
                <td class="p-4 pr-6 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <a href="pengguna.php?detail=<?= $pg['id_pengguna'] ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Detail">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </a>
                    <a href="pengguna.php?edit=<?= $pg['id_pengguna'] ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </a>
                    <?php if ($pg['id_pengguna'] != $_SESSION['admin_id']): ?>
                    <a href="pengguna.php?hapus=<?= $pg['id_pengguna'] ?>" onclick="return confirm('Yakin ingin menghapus pengguna ini?')" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </a>
                    <?php endif; ?>
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

  <!-- Modal Form Tambah/Edit -->
  <div id="modalForm" class="<?= $editData ? '' : 'hidden' ?> fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
      <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-bold text-dark text-lg"><?= $editData ? 'Edit Pengguna' : 'Tambah Pengguna Baru' ?></h3>
        <a href="pengguna.php" class="p-2 text-gray-400 hover:text-dark hover:bg-gray-100 rounded-lg transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </a>
      </div>
      <form method="POST" action="pengguna.php" class="p-6 space-y-4">
        <input type="hidden" name="id_pengguna" value="<?= $editData['id_pengguna'] ?? '' ?>">
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-bold text-dark mb-1.5">Username <span class="text-red-500">*</span></label>
            <input type="text" name="username" value="<?= htmlspecialchars($editData['username'] ?? '') ?>" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white text-sm text-dark font-medium">
          </div>
          <div>
            <label class="block text-sm font-bold text-dark mb-1.5">Role <span class="text-red-500">*</span></label>
            <select name="id_role" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white text-sm text-dark font-medium">
              <?php foreach ($roles as $r): ?>
              <option value="<?= $r['id_role'] ?>" <?= ($editData['id_role'] ?? 2) == $r['id_role'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nama_role']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-bold text-dark mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
          <input type="text" name="nama_pengguna" value="<?= htmlspecialchars($editData['nama_pengguna'] ?? '') ?>" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white text-sm text-dark font-medium">
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-bold text-dark mb-1.5">Email <span class="text-red-500">*</span></label>
            <input type="email" name="email" value="<?= htmlspecialchars($editData['email'] ?? '') ?>" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white text-sm text-dark font-medium">
          </div>
          <div>
            <label class="block text-sm font-bold text-dark mb-1.5">No. Telepon</label>
            <input type="text" name="no_telp" value="<?= htmlspecialchars($editData['no_telp'] ?? '') ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white text-sm text-dark font-medium">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-bold text-dark mb-1.5">Tanggal Lahir</label>
            <input type="date" name="tanggal_lahir" value="<?= $editData['tanggal_lahir'] ?? '' ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white text-sm text-dark font-medium">
          </div>
          <div>
            <label class="block text-sm font-bold text-dark mb-1.5">Password <?= $editData ? '(kosongkan jika tidak diubah)' : '<span class="text-red-500">*</span>' ?></label>
            <input type="password" name="password" <?= $editData ? '' : 'required' ?> class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white text-sm text-dark font-medium">
          </div>
        </div>

        <div class="flex gap-3 pt-4">
          <a href="pengguna.php" class="flex-1 py-2.5 text-center text-sm font-bold text-gray-500 hover:bg-gray-50 rounded-xl border border-gray-200 transition-colors">Batal</a>
          <button type="submit" class="flex-1 py-2.5 bg-accent text-white text-sm font-bold rounded-xl shadow-md hover:bg-orange-700 transition-colors cursor-pointer"><?= $editData ? 'Perbarui' : 'Simpan' ?></button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Detail -->
  <?php if ($detailData): ?>
  <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
      <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-bold text-dark text-lg">Detail Pengguna #<?= $detailData['id_pengguna'] ?></h3>
        <a href="pengguna.php<?= $filterRole ? '?role='.$filterRole : '' ?>" class="p-2 text-gray-400 hover:text-dark hover:bg-gray-100 rounded-lg transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </a>
      </div>
      <div class="p-6 space-y-5">
        <div class="flex items-center gap-4 mb-4">
          <div class="w-16 h-16 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-2xl">
            <?= getInitials($detailData['nama_pengguna']) ?>
          </div>
          <div>
            <h4 class="font-bold text-dark text-lg"><?= htmlspecialchars($detailData['nama_pengguna']) ?></h4>
            <p class="text-sm text-gray-500">@<?= htmlspecialchars($detailData['username']) ?></p>
            <span class="mt-1 px-2.5 py-0.5 bg-gray-100 text-gray-600 border border-gray-200 rounded text-[10px] font-bold tracking-wide inline-block uppercase"><?= htmlspecialchars($detailData['nama_role']) ?></span>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Email</p>
            <p class="text-sm font-medium text-dark"><?= htmlspecialchars($detailData['email'] ?? '-') ?></p>
          </div>
          <div>
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">No. Telp</p>
            <p class="text-sm font-medium text-dark"><?= htmlspecialchars($detailData['no_telp'] ?? '-') ?></p>
          </div>
          <div>
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Tanggal Lahir</p>
            <p class="text-sm font-medium text-dark"><?= $detailData['tanggal_lahir'] ? date('d M Y', strtotime($detailData['tanggal_lahir'])) : '-' ?></p>
          </div>
        </div>

        <hr class="border-gray-100">

        <!-- Info Alamat -->
        <div>
          <h5 class="text-sm font-bold text-primary mb-3">Informasi Alamat</h5>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Provinsi</p>
              <p class="text-sm font-medium text-dark"><?= htmlspecialchars($detailData['nama_provinsi'] ?? '-') ?></p>
            </div>
            <div>
              <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Kabupaten/Kota</p>
              <p class="text-sm font-medium text-dark"><?= htmlspecialchars($detailData['nama_kabupaten'] ?? '-') ?></p>
            </div>
            <div>
              <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Kecamatan</p>
              <p class="text-sm font-medium text-dark"><?= htmlspecialchars($detailData['nama_kecamatan'] ?? '-') ?></p>
            </div>
            <div>
              <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Desa/Kelurahan</p>
              <p class="text-sm font-medium text-dark"><?= htmlspecialchars($detailData['nama_desa'] ?? '-') ?></p>
            </div>
          </div>
          <div class="mt-4 bg-gray-50 p-4 rounded-xl border border-gray-100">
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Alamat Lengkap</p>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($detailData['alamat_lengkap'] ?? '(Belum ada data alamat)') ?></p>
          </div>
        </div>

      </div>
    </div>
  </div>
  <?php endif; ?>

</body>
</html>
