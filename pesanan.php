<?php
require_once __DIR__ . '/config/database.php';
requireClientLogin();

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_nama'] ?? '';
$userRole = $_SESSION['user_role'] ?? 2;

// Akses spesifik role Freelancer
if ($userRole != 3) {
    echo "<script>alert('Akses Ditolak: Fitur ini khusus Freelancer.'); window.location.href='index.php';</script>";
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id_booking = (int)$_POST['id_booking'];
    $status_baru = $_POST['status_baru'];
    
    // Verifikasi kepemilikan
    $stmtCek = $pdo->prepare("
        SELECT b.id_booking FROM booking b 
        JOIN layanan l ON b.id_layanan = l.id_layanan 
        WHERE b.id_booking = ? AND l.id_pengguna = ?
    ");
    $stmtCek->execute([$id_booking, $userId]);
    $valid = $stmtCek->fetch();

    if ($valid) {
        $allowedStatuses = ['MENUNGGU', 'DIPROSES', 'SELESAI', 'DIBATALKAN'];
        if (in_array($status_baru, $allowedStatuses)) {
            try {
                $stmtUp = $pdo->prepare("UPDATE booking SET status_booking = ? WHERE id_booking = ?");
                $stmtUp->execute([$status_baru, $id_booking]);
                $success = 'Status pesanan berhasil diperbarui!';
            } catch (Exception $e) {
                $error = 'Gagal memperbarui status pesanan.';
            }
        }
    } else {
        $error = 'Data pesanan tidak ditemukan atau akses ditolak.';
    }
}

// Fetch Histories
$stmtPesan = $pdo->prepare("
    SELECT b.*, p.nama_pengguna as nama_klien, p.no_telp as telp_klien, p.email as email_klien,
           j.nama_jasa, s.nama_satuan, l.tarif
    FROM booking b
    JOIN layanan l ON b.id_layanan = l.id_layanan
    JOIN jasa j ON l.id_jasa = j.id_jasa
    LEFT JOIN satuan s ON l.id_satuan = s.id_satuan
    JOIN pengguna p ON b.id_pengguna = p.id_pengguna
    WHERE l.id_pengguna = ?
    ORDER BY b.tanggal_booking DESC, b.id_booking DESC
");
$stmtPesan->execute([$userId]);
$pesananList = $stmtPesan->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pesanan Masuk | WorkLance</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/WorkLance/src/output.css">
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased min-h-screen flex flex-col">

  <!-- Navbar -->
  <?php $currentPage = 'pesanan'; require_once __DIR__ . '/components/navbar.php'; ?>

  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex-grow w-full">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4 mb-8">
      <div>
        <h1 class="text-3xl font-bold text-dark flex items-center gap-3 mb-2">Manajemen Pesanan</h1>
        <p class="text-gray-500">Kelola dan update status pengerjaan proyek dari setiap klien Anda.</p>
      </div>
      <div class="bg-primary/10 text-primary px-4 py-2 rounded-xl text-sm font-bold border border-primary/20">
        <?= count($pesananList) ?> Total Transaksi
      </div>
    </div>

    <!-- Alert Success/Error -->
    <?php if ($success): ?>
    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm font-medium flex items-center gap-2 shadow-sm">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-xl text-sm font-medium flex items-center gap-2 shadow-sm">
      <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Data List -->
    <div class="space-y-6">
      <?php if (empty($pesananList)): ?>
        <div class="bg-white rounded-3xl p-10 text-center shadow-sm border border-gray-100">
           <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
           <h3 class="text-xl font-bold text-dark">Belum Ada Pesanan Masuk</h3>
           <p class="text-gray-500 mt-2">Daftar pengguna yang memesan jasa dari profil Anda akan muncul di bagian ini.</p>
        </div>
      <?php else: ?>
        <?php foreach ($pesananList as $ps): ?>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col md:flex-row md:items-center gap-6 hover:shadow-md transition-shadow">
          <!-- Col 1: Jasa & Info Pelanggan -->
          <div class="flex-1">
             <div class="flex items-center gap-3 mb-2">
               <span class="px-2.5 py-1 text-xs font-bold rounded-lg border 
                 <?= $ps['status_booking'] === 'MENUNGGU' ? 'bg-yellow-50 text-yellow-600 border-yellow-200' : '' ?>
                 <?= $ps['status_booking'] === 'DIPROSES' ? 'bg-blue-50 text-blue-600 border-blue-200' : '' ?>
                 <?= $ps['status_booking'] === 'SELESAI' ? 'bg-green-50 text-green-600 border-green-200' : '' ?>
                 <?= $ps['status_booking'] === 'DIBATALKAN' ? 'bg-red-50 text-red-600 border-red-200' : '' ?>
               ">
                 <?= htmlspecialchars($ps['status_booking']) ?>
               </span>
               <span class="text-sm font-semibold text-gray-400"><?= date('d M Y', strtotime($ps['tanggal_booking'])) ?></span>
             </div>
             
             <h3 class="text-xl font-extrabold text-dark mb-1"><?= htmlspecialchars($ps['nama_jasa']) ?></h3>
             <p class="text-accent font-bold mb-4">Rp <?= number_format($ps['tarif'], 0, ',', '.') ?> <span class="text-sm text-gray-400 font-medium font-normal">/ <?= htmlspecialchars($ps['nama_satuan']) ?></span></p>

             <div class="flex items-start gap-4 p-4 rounded-xl bg-gray-50 border border-gray-100 mb-0 lg:mb-4">
                <div class="w-10 h-10 rounded-full bg-primary text-white font-bold flex items-center justify-center shrink-0">
                   <?= getInitials($ps['nama_klien']) ?>
                </div>
                <div>
                   <p class="font-bold text-dark text-sm leading-none flex items-center gap-2 mb-1">
                     <?= htmlspecialchars($ps['nama_klien']) ?>
                   </p>
                   <div class="flex items-center flex-wrap gap-2 text-xs text-gray-500 font-medium">
                     <span class="flex items-center gap-1">
                       <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/><path d="M17 11h-4V7c0-.55-.45-1-1-1s-1 .45-1 1v5c0 .55.45 1 1 1h5c.55 0 1-.45 1-1s-.45-1-1-1z"/></svg>
                       <?= htmlspecialchars($ps['telp_klien']) ?>
                     </span>
                     <span>&bull;</span>
                     <span><?= htmlspecialchars($ps['email_klien']) ?></span>
                     <span>&bull;</span>
                     <span class="text-gray-600 truncate max-w-[200px]" title="<?= htmlspecialchars($ps['alamat_booking']) ?>"><?= htmlspecialchars($ps['alamat_booking']) ?></span>
                   </div>
                </div>
             </div>
          </div>

          <!-- Col 2: Action & Notes -->
          <div class="md:w-[35%] w-full flex flex-col justify-between self-stretch border-t mt-4 pt-4 md:border-t-0 md:border-l md:mt-0 md:pt-0 md:pl-6 border-gray-100">
             <div class="mb-4 text-sm text-gray-600 w-full break-all pb-4">
                 <p class="font-bold text-dark mb-1 text-xs uppercase tracking-wider">Catatan Klien</p>
                 <p class="italic">"<?= nl2br(htmlspecialchars($ps['catatan'] ?: 'Tidak ada catatan.')) ?>"</p>
             </div>
             <div>
                <form method="POST" action="pesanan.php" class="w-full flex items-center gap-2">
                   <input type="hidden" name="action" value="update_status">
                   <input type="hidden" name="id_booking" value="<?= $ps['id_booking'] ?>">
                   <select name="status_baru" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2.5 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent font-bold text-dark">
                      <option value="MENUNGGU" <?= $ps['status_booking'] === 'MENUNGGU' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                      <option value="DIPROSES" <?= $ps['status_booking'] === 'DIPROSES' ? 'selected' : '' ?>>Proses Pekerjaan</option>
                      <option value="SELESAI" <?= $ps['status_booking'] === 'SELESAI' ? 'selected' : '' ?>>Selesai</option>
                      <option value="DIBATALKAN" <?= $ps['status_booking'] === 'DIBATALKAN' ? 'selected' : '' ?>>Batalkan</option>
                   </select>
                   <button type="submit" class="shrink-0 bg-dark hover:bg-black text-white p-2.5 rounded-lg shadow transition-colors cursor-pointer" title="Simpan Status">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                   </button>
                </form>
             </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <footer class="bg-gray-50 pt-12 pb-8 mt-auto border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 text-center text-sm text-gray-500">
      <p>&copy; 2026 WorkLance Indonesia.</p>
    </div>
  </footer>

</body>
</html>
