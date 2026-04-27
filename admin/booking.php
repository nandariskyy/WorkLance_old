<?php
require_once __DIR__ . '/../config/database.php';
requireAdminLogin();

$currentPage = 'booking';
$adminNama = $_SESSION['admin_nama'] ?? 'Admin';
$success = '';
$error = '';

// Status helper
function getStatusBadge($status) {
    switch ($status) {
        case 'MENUNGGU': return 'bg-gray-100 text-gray-600 border-gray-200';
        case 'DIPROSES': return 'bg-yellow-50 text-yellow-600 border-yellow-200';
        case 'SELESAI': return 'bg-green-50 text-green-600 border-green-200';
        case 'DIBATALKAN': return 'bg-red-50 text-red-600 border-red-200';
        default: return 'bg-gray-100 text-gray-600 border-gray-200';
    }
}
function getStatusLabel($status) {
    switch ($status) {
        case 'MENUNGGU': return 'Menunggu';
        case 'DIPROSES': return 'Diproses';
        case 'SELESAI': return 'Selesai';
        case 'DIBATALKAN': return 'Dibatalkan';
        default: return $status;
    }
}

// ============ HANDLE ACTIONS ============

// UPDATE STATUS
if (isset($_POST['update_status'])) {
    $id = (int)$_POST['id_booking'];
    $status = $_POST['status_booking'];
    $validStatuses = ['MENUNGGU', 'DIPROSES', 'SELESAI', 'DIBATALKAN'];
    if (in_array($status, $validStatuses)) {
        $stmt = $pdo->prepare("UPDATE booking SET status_booking = ? WHERE id_booking = ?");
        $stmt->execute([$status, $id]);
        $success = 'Status booking berhasil diperbarui.';
    }
}

// HAPUS BOOKING
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM booking WHERE id_booking = ?");
    $stmt->execute([$id]);
    $success = 'Booking berhasil dihapus.';
}

// FILTER
$filterStatus = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$where = "1=1";
$params = [];

if ($filterStatus !== '') {
    $where .= " AND b.status_booking = ?";
    $params[] = $filterStatus;
}
if ($search !== '') {
    $where .= " AND (p.nama_pengguna LIKE ? OR pf.nama_pengguna LIKE ? OR j.nama_jasa LIKE ? OR b.alamat_booking LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare("
    SELECT b.*, p.nama_pengguna AS nama_client, j.nama_jasa, pf.nama_pengguna AS nama_freelancer
    FROM booking b
    LEFT JOIN pengguna p ON b.id_pengguna = p.id_pengguna
    LEFT JOIN layanan l ON b.id_layanan = l.id_layanan
    LEFT JOIN jasa j ON l.id_jasa = j.id_jasa
    LEFT JOIN pengguna pf ON l.id_pengguna = pf.id_pengguna
    WHERE $where
    ORDER BY b.id_booking DESC
");
$stmt->execute($params);
$bookingList = $stmt->fetchAll();

// Count per status
$countAll = $pdo->query("SELECT COUNT(*) FROM booking")->fetchColumn();
$countMenunggu = $pdo->query("SELECT COUNT(*) FROM booking WHERE status_booking='MENUNGGU'")->fetchColumn();
$countDiproses = $pdo->query("SELECT COUNT(*) FROM booking WHERE status_booking='DIPROSES'")->fetchColumn();
$countSelesai = $pdo->query("SELECT COUNT(*) FROM booking WHERE status_booking='SELESAI'")->fetchColumn();
$countBatal = $pdo->query("SELECT COUNT(*) FROM booking WHERE status_booking='DIBATALKAN'")->fetchColumn();

// Detail booking
$detailData = null;
if (isset($_GET['detail'])) {
    $stmt = $pdo->prepare("
        SELECT b.*, p.nama_pengguna AS nama_client, p.email, p.no_telp, j.nama_jasa, k.nama_kategori, pf.nama_pengguna AS nama_freelancer
        FROM booking b
        LEFT JOIN pengguna p ON b.id_pengguna = p.id_pengguna
        LEFT JOIN layanan l ON b.id_layanan = l.id_layanan
        LEFT JOIN jasa j ON l.id_jasa = j.id_jasa
        LEFT JOIN kategori k ON j.id_kategori = k.id_kategori
        LEFT JOIN pengguna pf ON l.id_pengguna = pf.id_pengguna
        WHERE b.id_booking = ?
    ");
    $stmt->execute([(int)$_GET['detail']]);
    $detailData = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Booking | Admin WorkLance</title>
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
      <a href="booking.php" class="flex items-center gap-3 px-4 py-3 bg-white/10 text-white border border-white/5 rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
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
          <?php if ($filterStatus): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari booking..."
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

      <div class="mb-8">
        <h1 class="text-3xl font-bold text-dark mb-1">Kelola Booking</h1>
        <p class="text-gray-500">Pantau dan kelola semua booking yang masuk.</p>
      </div>

      <?php if ($success): ?>
      <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm font-medium flex items-center gap-2">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <?= htmlspecialchars($success) ?>
      </div>
      <?php endif; ?>

      <!-- Status Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <a href="booking.php?status=MENUNGGU" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow text-center <?= $filterStatus === 'MENUNGGU' ? 'ring-2 ring-accent' : '' ?>">
          <p class="text-2xl font-bold text-dark"><?= $countMenunggu ?></p>
          <p class="text-xs text-gray-500 font-medium mt-1">Menunggu</p>
        </a>
        <a href="booking.php?status=DIPROSES" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow text-center <?= $filterStatus === 'DIPROSES' ? 'ring-2 ring-accent' : '' ?>">
          <p class="text-2xl font-bold text-yellow-600"><?= $countDiproses ?></p>
          <p class="text-xs text-gray-500 font-medium mt-1">Diproses</p>
        </a>
        <a href="booking.php?status=SELESAI" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow text-center <?= $filterStatus === 'SELESAI' ? 'ring-2 ring-accent' : '' ?>">
          <p class="text-2xl font-bold text-green-600"><?= $countSelesai ?></p>
          <p class="text-xs text-gray-500 font-medium mt-1">Selesai</p>
        </a>
        <a href="booking.php?status=DIBATALKAN" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow text-center <?= $filterStatus === 'DIBATALKAN' ? 'ring-2 ring-accent' : '' ?>">
          <p class="text-2xl font-bold text-red-600"><?= $countBatal ?></p>
          <p class="text-xs text-gray-500 font-medium mt-1">Dibatalkan</p>
        </a>
      </div>

      <!-- Filter -->
      <div class="flex gap-2 mb-6 flex-wrap">
        <a href="booking.php" class="px-4 py-2 rounded-lg text-sm font-bold transition-colors <?= $filterStatus === '' ? 'bg-dark text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">Semua (<?= $countAll ?>)</a>
        <a href="booking.php?status=MENUNGGU" class="px-4 py-2 rounded-lg text-sm font-bold transition-colors <?= $filterStatus === 'MENUNGGU' ? 'bg-dark text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">Menunggu</a>
        <a href="booking.php?status=DIPROSES" class="px-4 py-2 rounded-lg text-sm font-bold transition-colors <?= $filterStatus === 'DIPROSES' ? 'bg-dark text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">Diproses</a>
        <a href="booking.php?status=SELESAI" class="px-4 py-2 rounded-lg text-sm font-bold transition-colors <?= $filterStatus === 'SELESAI' ? 'bg-dark text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">Selesai</a>
        <a href="booking.php?status=DIBATALKAN" class="px-4 py-2 rounded-lg text-sm font-bold transition-colors <?= $filterStatus === 'DIBATALKAN' ? 'bg-dark text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">Dibatalkan</a>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse min-w-[800px]">
            <thead>
              <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider">
                <th class="p-4 pl-6 font-semibold">ID</th>
                <th class="p-4 font-semibold">Client</th>
                <th class="p-4 font-semibold">Freelancer</th>
                <th class="p-4 font-semibold">Jasa</th>
                <th class="p-4 font-semibold">Tanggal</th>
                <th class="p-4 font-semibold">Status</th>
                <th class="p-4 font-semibold pr-6 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php if (empty($bookingList)): ?>
              <tr><td colspan="6" class="p-8 text-center text-gray-400">Tidak ada data booking.</td></tr>
              <?php else: ?>
              <?php foreach ($bookingList as $bk): ?>
              <tr class="hover:bg-gray-50/50 transition-colors">
                <td class="p-4 pl-6 text-sm text-gray-500">#<?= $bk['id_booking'] ?></td>
                <td class="p-4">
                  <p class="font-bold text-dark text-sm whitespace-nowrap"><?= htmlspecialchars($bk['nama_client'] ?? '-') ?></p>
                </td>
                <td class="p-4 text-sm font-semibold text-accent whitespace-nowrap"><?= htmlspecialchars($bk['nama_freelancer'] ?? '-') ?></td>
                <td class="p-4 text-sm text-gray-600"><?= htmlspecialchars($bk['nama_jasa'] ?? '-') ?></td>
                <td class="p-4 text-sm text-gray-500 whitespace-nowrap"><?= $bk['tanggal_booking'] ? formatTanggal($bk['tanggal_booking']) : '-' ?></td>
                <td class="p-4">
                  <span class="px-3 py-1 <?= getStatusBadge($bk['status_booking']) ?> rounded-full text-[11px] font-bold border inline-block whitespace-nowrap"><?= getStatusLabel($bk['status_booking']) ?></span>
                </td>
                <td class="p-4 pr-6 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <a href="booking.php?detail=<?= $bk['id_booking'] ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Detail">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </a>
                    <a href="booking.php?hapus=<?= $bk['id_booking'] ?>" onclick="return confirm('Yakin ingin menghapus booking ini?')" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
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

  <!-- Detail Modal -->
  <?php if ($detailData): ?>
  <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
      <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-bold text-dark text-lg">Detail Booking #<?= $detailData['id_booking'] ?></h3>
        <a href="booking.php<?= $filterStatus ? '?status='.$filterStatus : '' ?>" class="p-2 text-gray-400 hover:text-dark hover:bg-gray-100 rounded-lg transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </a>
      </div>
      <div class="p-6 space-y-5">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Client</p>
            <p class="text-sm font-bold text-dark"><?= htmlspecialchars($detailData['nama_client'] ?? '-') ?></p>
          </div>
          <div>
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Email</p>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($detailData['email'] ?? '-') ?></p>
          </div>
          <div>
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">No. Telepon</p>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($detailData['no_telp'] ?? '-') ?></p>
          </div>
          <div>
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Tanggal Booking</p>
            <p class="text-sm text-gray-600"><?= $detailData['tanggal_booking'] ? formatTanggal($detailData['tanggal_booking']) : '-' ?></p>
          </div>
          <div>
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Kategori</p>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($detailData['nama_kategori'] ?? '-') ?></p>
          </div>
          <div>
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Freelancer</p>
            <p class="text-sm font-bold text-accent"><?= htmlspecialchars($detailData['nama_freelancer'] ?? '-') ?></p>
          </div>
          <div>
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Jasa</p>
            <p class="text-sm font-bold text-dark"><?= htmlspecialchars($detailData['nama_jasa'] ?? '-') ?></p>
          </div>
        </div>

        <div>
          <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Alamat Booking</p>
          <p class="text-sm text-gray-600"><?= htmlspecialchars($detailData['alamat_booking'] ?? '-') ?></p>
        </div>

        <div>
          <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Catatan</p>
          <p class="text-sm text-gray-600"><?= htmlspecialchars($detailData['catatan'] ?? 'Tidak ada catatan') ?></p>
        </div>

        <div>
          <p class="text-xs text-gray-400 font-semibold uppercase mb-2">Status</p>
          <span class="px-3 py-1 <?= getStatusBadge($detailData['status_booking']) ?> rounded-full text-xs font-bold border inline-block"><?= getStatusLabel($detailData['status_booking']) ?></span>
        </div>

        <!-- Update Status Form -->
        <form method="POST" action="booking.php" class="pt-4 border-t border-gray-100">
          <input type="hidden" name="update_status" value="1">
          <input type="hidden" name="id_booking" value="<?= $detailData['id_booking'] ?>">
          <label class="block text-sm font-bold text-dark mb-2">Ubah Status</label>
          <div class="flex gap-3">
            <select name="status_booking" class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white text-sm text-dark font-medium">
              <option value="MENUNGGU" <?= $detailData['status_booking'] === 'MENUNGGU' ? 'selected' : '' ?>>Menunggu</option>
              <option value="DIPROSES" <?= $detailData['status_booking'] === 'DIPROSES' ? 'selected' : '' ?>>Diproses</option>
              <option value="SELESAI" <?= $detailData['status_booking'] === 'SELESAI' ? 'selected' : '' ?>>Selesai</option>
              <option value="DIBATALKAN" <?= $detailData['status_booking'] === 'DIBATALKAN' ? 'selected' : '' ?>>Dibatalkan</option>
            </select>
            <button type="submit" class="px-5 py-2.5 bg-accent text-white text-sm font-bold rounded-xl shadow-md hover:bg-orange-700 transition-colors cursor-pointer">Update</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>

</body>
</html>
