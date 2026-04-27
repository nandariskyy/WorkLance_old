<?php
require_once __DIR__ . '/../config/database.php';
requireAdminLogin();

$adminNama = $_SESSION['admin_nama'] ?? 'Admin';
$success = '';
$error = '';

// Proses form admin (Terima / Tolak)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id_pengajuan = (int)$_POST['id_pengajuan'];
    $pengguna_id = (int)$_POST['id_pengguna'];
    $action = $_POST['action'];
    $catatan = trim($_POST['catatan_admin'] ?? '');

    try {
        $pdo->beginTransaction();

        if ($action === 'terima') {
            // Update status pengajuan
            $stmt = $pdo->prepare("UPDATE pengajuan_freelancer SET status = 'DITERIMA', catatan_admin = ? WHERE id_pengajuan = ?");
            $stmt->execute([$catatan, $id_pengajuan]);

            // Update role pengguna menjadi Freelancer (3)
            $stmtRole = $pdo->prepare("UPDATE pengguna SET id_role = 3 WHERE id_pengguna = ? AND id_role = 2");
            $stmtRole->execute([$pengguna_id]);

            $success = "Pengajuan berhasil diterima. Pengguna kini berstatus Freelancer.";
        } elseif ($action === 'tolak') {
            // Update status pengajuan
            $stmt = $pdo->prepare("UPDATE pengajuan_freelancer SET status = 'DITOLAK', catatan_admin = ? WHERE id_pengajuan = ?");
            $stmt->execute([$catatan, $id_pengajuan]);

            $success = "Pengajuan telah ditolak.";
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Terjadi kesalahan saat memproses data.";
    }
}

// Fitur Pencarian
$search = trim($_GET['search'] ?? '');
$searchQuery = "";
$params = [];
if ($search !== '') {
    $searchQuery = "WHERE (p.nama_pengguna LIKE ? OR pf.nik LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Mengambil Data Pengajuan
$stmtData = $pdo->prepare("
    SELECT pf.*, p.nama_pengguna, p.email, p.no_telp
    FROM pengajuan_freelancer pf
    JOIN pengguna p ON pf.id_pengguna = p.id_pengguna
    $searchQuery
    ORDER BY CASE WHEN pf.status = 'MENUNGGU' THEN 1 ELSE 2 END, pf.tanggal_pengajuan DESC
");
$stmtData->execute($params);
$pengajuanList = $stmtData->fetchAll();

// Detail Data Modal
$detailData = null;
if (isset($_GET['detail'])) {
    $stmt = $pdo->prepare("
        SELECT pf.*, p.nama_pengguna, p.email, p.no_telp
        FROM pengajuan_freelancer pf
        JOIN pengguna p ON pf.id_pengguna = p.id_pengguna
        WHERE pf.id_pengajuan = ?
    ");
    $stmt->execute([(int)$_GET['detail']]);
    $detailData = $stmt->fetch();
}

$currentPage = 'pengajuan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verifikasi Pengajuan | WorkLance Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/WorkLance/src/output.css">
</head>
<body class="bg-gray-50 font-sans text-gray-800 h-screen flex overflow-hidden">

  <!-- Sidebar -->
  <aside class="w-72 bg-dark text-white flex flex-col hidden md:flex flex-shrink-0 z-20">
    <div class="h-20 flex items-center px-8 border-b border-white/10 shrink-0">
      <a href="../index.php" class="flex items-center gap-2 group">
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
      <a href="pengajuan.php" class="flex items-center gap-3 px-4 py-3 bg-white/10 text-white border border-white/5 rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
        Pengajuan
      </a>

      <div class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-8">Sistem</div>
      <a href="kelola.php" class="flex items-center gap-3 px-4 py-3 text-gray-400 hover:bg-white/5 hover:text-white rounded-xl font-medium transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
        Kelola
      </a>
    </div>

    <!-- Bottom Action -->
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
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari NIK atau Nama..."
            class="w-full bg-gray-100/50 border border-transparent focus:border-gray-200 hover:bg-gray-100 rounded-full pl-12 pr-4 py-2.5 text-sm outline-none transition-all placeholder-gray-400 text-dark">
        </form>
      </div>
      <div class="flex items-center gap-3 md:gap-5 ml-auto">
        <div class="flex items-center gap-3 cursor-pointer group">
          <div class="w-10 h-10 rounded-full bg-accent text-white flex items-center justify-center font-bold text-sm ring-2 ring-gray-100">AD</div>
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
          <h1 class="text-3xl font-bold text-dark mb-1">Verifikasi Pendaftaran Freelancer</h1>
          <p class="text-gray-500">Total <?= count($pengajuanList) ?> pengajuan telah diinput masuk.</p>
        </div>
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

      <!-- Table -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse min-w-[900px]">
            <thead>
              <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider">
                <th class="p-4 pl-6 font-semibold">Tanggal</th>
                <th class="p-4 font-semibold">Nama Pengguna</th>
                <th class="p-4 font-semibold">Kontak</th>
                <th class="p-4 font-semibold">NIK</th>
                <th class="p-4 font-semibold">Status</th>
                <th class="p-4 font-semibold pr-6 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php if (empty($pengajuanList)): ?>
              <tr><td colspan="6" class="p-8 text-center text-gray-400">Belum ada data pengajuan freelancer.</td></tr>
              <?php else: ?>
              <?php foreach ($pengajuanList as $pj): ?>
              <tr class="hover:bg-gray-50/50 transition-colors">
                <td class="p-4 pl-6 text-sm text-gray-500 whitespace-nowrap">
                  <?= date('d M Y', strtotime($pj['tanggal_pengajuan'])) ?>
                </td>
                <td class="p-4">
                  <div class="font-bold text-dark text-sm"><?= htmlspecialchars($pj['nama_pengguna']) ?></div>
                </td>
                <td class="p-4 text-sm text-gray-600 whitespace-nowrap">
                  <div class="font-medium text-dark"><?= htmlspecialchars($pj['email']) ?></div>
                  <div class="text-xs text-gray-500"><?= htmlspecialchars($pj['no_telp']) ?></div>
                </td>
                <td class="p-4 text-sm font-medium text-dark whitespace-nowrap">
                  <?= htmlspecialchars($pj['nik']) ?>
                </td>
                <td class="p-4">
                  <?php if ($pj['status'] === 'MENUNGGU'): ?>
                    <span class="px-2.5 py-1 bg-yellow-50 text-yellow-600 border border-yellow-200 rounded-md text-[11px] font-bold tracking-wide">MENUNGGU</span>
                  <?php elseif ($pj['status'] === 'DITERIMA'): ?>
                    <span class="px-2.5 py-1 bg-green-50 text-green-600 border border-green-200 rounded-md text-[11px] font-bold tracking-wide">DITERIMA</span>
                  <?php else: ?>
                    <span class="px-2.5 py-1 bg-red-50 text-red-600 border border-red-200 rounded-md text-[11px] font-bold tracking-wide" title="<?= htmlspecialchars($pj['catatan_admin'] ?? '') ?>">DITOLAK</span>
                  <?php endif; ?>
                </td>
                <td class="p-4 pr-6 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <?php if ($pj['status'] === 'MENUNGGU'): ?>
                      <button type="button" onclick="openActionModal(<?= $pj['id_pengajuan'] ?>, <?= $pj['id_pengguna'] ?>, '<?= htmlspecialchars($pj['nama_pengguna'], ENT_QUOTES) ?>', 'terima')" class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors cursor-pointer" title="Terima">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                      </button>
                      <button type="button" onclick="openActionModal(<?= $pj['id_pengajuan'] ?>, <?= $pj['id_pengguna'] ?>, '<?= htmlspecialchars($pj['nama_pengguna'], ENT_QUOTES) ?>', 'tolak')" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors cursor-pointer" title="Tolak">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                      </button>
                    <?php endif; ?>
                    <a href="pengajuan.php?detail=<?= $pj['id_pengajuan'] ?><?= $search ? '&search='.urlencode($search) : '' ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Detail">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
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
        <h3 class="font-bold text-dark text-lg flex items-center gap-2">Detail Pengajuan Verifikasi</h3>
        <a href="pengajuan.php<?= $search ? '?search='.urlencode($search) : '' ?>" class="p-2 text-gray-400 hover:text-dark hover:bg-gray-100 rounded-lg transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </a>
      </div>
      <div class="p-6 space-y-5">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Nama Pengguna</p>
            <p class="text-sm font-bold text-dark"><?= htmlspecialchars($detailData['nama_pengguna']) ?></p>
          </div>
          <div>
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Status Pendaftaran</p>
            <?php if ($detailData['status'] === 'MENUNGGU'): ?>
              <span class="px-2 py-0.5 bg-yellow-50 text-yellow-600 border border-yellow-200 rounded text-xs font-bold uppercase tracking-wide inline-block">Menunggu</span>
            <?php elseif ($detailData['status'] === 'DITERIMA'): ?>
              <span class="px-2 py-0.5 bg-green-50 text-green-600 border border-green-200 rounded text-xs font-bold uppercase tracking-wide inline-block">Diterima</span>
            <?php else: ?>
              <span class="px-2 py-0.5 bg-red-50 text-red-600 border border-red-200 rounded text-xs font-bold uppercase tracking-wide inline-block">Ditolak</span>
            <?php endif; ?>
          </div>
          <div>
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">NIK</p>
            <p class="text-sm font-medium text-dark"><?= htmlspecialchars($detailData['nik']) ?></p>
          </div>
          <div>
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Email</p>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($detailData['email']) ?></p>
          </div>
          <div>
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">No. Telp</p>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($detailData['no_telp']) ?></p>
          </div>
          <div>
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Waktu</p>
            <p class="text-sm text-gray-600"><?= date('d M Y, H:i', strtotime($detailData['tanggal_pengajuan'])) ?></p>
          </div>
        </div>

        <div>
          <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Deskripsi Keahlian</p>
          <div class="text-sm text-gray-600 bg-gray-50 rounded-xl p-4 border border-gray-100">
             <?= nl2br(htmlspecialchars($detailData['deskripsi'])) ?>
          </div>
        </div>

        <?php if ($detailData['status'] === 'DITOLAK' || $detailData['status'] === 'DITERIMA'): ?>
          <?php if (!empty($detailData['catatan_admin'])): ?>
          <div>
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Catatan Admin</p>
            <p class="text-sm text-dark font-medium italic">"<?= htmlspecialchars($detailData['catatan_admin']) ?>"</p>
          </div>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ($detailData['status'] === 'MENUNGGU'): ?>
        <hr class="border-gray-100 my-4">
        <form method="POST" action="pengajuan.php<?= $search ? '?search='.urlencode($search) : '' ?>" class="space-y-4">
          <input type="hidden" name="id_pengajuan" value="<?= $detailData['id_pengajuan'] ?>">
          <input type="hidden" name="id_pengguna" value="<?= $detailData['id_pengguna'] ?>">
          
          <div>
            <label class="block text-sm font-bold text-dark mb-2">Catatan Verifikasi (Opsional)</label>
            <textarea name="catatan_admin" rows="2" class="w-full border border-gray-200 rounded-xl px-4 py-2 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary text-sm font-medium text-dark" placeholder="Masukkan alasan ditolak atau catatan tambahan jika diterima..."></textarea>
          </div>

          <div class="flex gap-3">
             <button type="submit" name="action" value="tolak" class="flex-1 px-5 py-3 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 rounded-xl text-sm font-bold transition-colors cursor-pointer text-center" onclick="return confirm('Tolak pengajuan ini?')">Tolak</button>
             <button type="submit" name="action" value="terima" class="flex-1 px-5 py-3 bg-green-500 hover:bg-green-600 text-white rounded-xl text-sm font-bold shadow-md transition-colors cursor-pointer text-center" onclick="return confirm('Terima pengajuan ini?')">Terima (Jadikan Freelancer)</button>
          </div>
        </form>
        <?php endif; ?>

      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Modal Aksi Terima/Tolak -->
  <div id="modalAction" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center opacity-0">
    <div class="bg-white max-w-md w-full rounded-2xl shadow-xl overflow-hidden transform scale-95" id="modalActionContent">
      <div class="p-6 border-b border-gray-100 flex items-center justify-between shadow-sm">
        <h3 class="text-xl font-bold text-dark flex items-center gap-2" id="actionTitle">
          Tindak Lanjut
        </h3>
        <button type="button" onclick="closeActionModal()" class="text-gray-400 hover:text-dark hover:bg-gray-100 p-1.5 rounded-lg transition-colors cursor-pointer">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>
      <form method="POST" action="pengajuan.php<?= $search ? '?search='.urlencode($search) : '' ?>">
        <div class="p-6">
          <p class="text-gray-600 text-sm mb-5 leading-relaxed" id="actionDesc">Anda akan memproses pengajuan ini. Silakan isi catatan verifikasi jika ada (opsional).</p>
          <input type="hidden" name="id_pengajuan" id="actionPengajuanId">
          <input type="hidden" name="id_pengguna" id="actionPenggunaId">
          <input type="hidden" name="action" id="actionType">
          
          <div>
            <label class="text-sm font-bold text-dark block mb-2">Catatan Admin <span class="text-gray-400 font-medium text-xs ml-1">(Opsional)</span></label>
            <textarea name="catatan_admin" rows="3" class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50/50 hover:bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary text-sm font-medium text-dark transition-all" placeholder="Masukkan alasan ditolak atau pesan instruksi jika diterima..."></textarea>
          </div>
        </div>
        <div class="p-6 border-t border-gray-100 bg-gray-50/50 flex justify-end gap-3">
          <button type="button" onclick="closeActionModal()" class="px-5 py-2.5 text-gray-500 font-bold hover:text-dark hover:bg-gray-200 rounded-xl transition-colors cursor-pointer">Batal</button>
          <button type="submit" id="actionSubmitBtn" class="px-6 py-2.5 bg-primary hover:bg-primary/90 text-white rounded-xl font-bold shadow-md hover:shadow-lg transition-all cursor-pointer">Konfirmasi</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openActionModal(id_pengajuan, id_pengguna, nama, type) {
      document.getElementById('actionPengajuanId').value = id_pengajuan;
      document.getElementById('actionPenggunaId').value = id_pengguna;
      document.getElementById('actionType').value = type;
      
      const titleEl = document.getElementById('actionTitle');
      const descEl = document.getElementById('actionDesc');
      const btnEl = document.getElementById('actionSubmitBtn');

      if (type === 'terima') {
        titleEl.innerHTML = `<svg class="w-6 h-6 text-green-500 bg-green-50 rounded-lg p-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Terima Pendaftaran`;
        descEl.innerHTML = `Anda akan <strong>MENERIMA</strong> pengajuan dari <strong class="text-dark bg-yellow-50 px-1 py-0.5 rounded text-[13px] border border-yellow-100">${nama}</strong>.<br><span class="block mt-2 text-xs text-gray-500">Pengguna ini akan segera diangkat menjadi <strong class="text-primary">Freelancer</strong>.</span>`;
        btnEl.className = 'px-6 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-xl font-bold shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all cursor-pointer';
        btnEl.textContent = 'Terima Pengajuan';
      } else {
        titleEl.innerHTML = `<svg class="w-6 h-6 text-red-500 bg-red-50 rounded-lg p-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Tolak Pendaftaran`;
        descEl.innerHTML = `Anda akan <strong>MENOLAK</strong> pengajuan dari <strong class="text-dark bg-yellow-50 px-1 py-0.5 rounded text-[13px] border border-yellow-100">${nama}</strong>.`;
        btnEl.className = 'px-6 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-xl font-bold shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all cursor-pointer';
        btnEl.textContent = 'Tolak Pengajuan';
      }
      
      const modal = document.getElementById('modalAction');
      const modalContent = document.getElementById('modalActionContent');
      
      modal.classList.remove('hidden');
      setTimeout(() => {
        modal.classList.remove('opacity-0');
        modalContent.classList.remove('scale-95');
      }, 10);
    }

    function closeActionModal() {
      const modal = document.getElementById('modalAction');
      const modalContent = document.getElementById('modalActionContent');
      
      modal.classList.add('opacity-0');
      modalContent.classList.add('scale-95');
      
      setTimeout(() => {
        modal.classList.add('hidden');
      }, 300);
    }
  </script>

</body>
</html>
