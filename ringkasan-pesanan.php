<?php
require_once __DIR__ . '/config/database.php';

$loggedIn = isClientLoggedIn();
$userName = $_SESSION['user_nama'] ?? '';
$bookingSaved = false;
$error = '';

// Proses simpan booking jika POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $loggedIn) {
    // Expected id_jasa to be updated to id_layanan to match new table structure
    $id_layanan = (int)($_POST['id_layanan'] ?? 0);
    $tanggal = $_POST['tanggal_booking'] ?? '';
    // Expected alamat to be submitted from localStorage's data.alamat
    $alamat = trim($_POST['alamat_booking'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');

    if ($id_layanan && $tanggal && $alamat) {
        $stmt = $pdo->prepare("INSERT INTO booking (id_pengguna, id_layanan, tanggal_booking, alamat_booking, catatan, status_booking) VALUES (?, ?, ?, ?, ?, 'MENUNGGU')");
        $stmt->execute([$_SESSION['user_id'], $id_layanan, $tanggal, $alamat, $catatan]);
        $bookingSaved = true;
    } else {
        $error = 'Data booking tidak lengkap.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ringkasan Pesanan | WorkLance</title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/WorkLance/src/output.css">
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased min-h-screen">

  <!-- Navbar -->
  <?php $currentPage = 'ringkasan-pesanan'; require_once __DIR__ . '/components/navbar.php'; ?>

  <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumb -->
    <nav class="flex items-center text-sm gap-2 font-medium mb-8">
      <a href="index.php" class="text-gray-500 hover:text-accent transition-colors">Beranda</a>
      <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
      <span class="text-dark font-bold">Ringkasan Pesanan</span>
    </nav>

    <?php if ($bookingSaved): ?>
    <!-- Booking Success -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-10 text-center">
      <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      </div>
      <h1 class="text-3xl font-bold text-dark mb-3">Booking Berhasil!</h1>
      <p class="text-gray-500 text-lg mb-8">Pesanan Anda telah disimpan. Silakan hubungi freelancer via WhatsApp untuk konfirmasi.</p>
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <button id="btnWhatsApp" class="bg-green-500 hover:bg-green-600 text-white font-bold py-4 px-8 rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.527 3.66 1.438 5.17L2 22l4.93-1.417A9.954 9.954 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
          Hubungi via WhatsApp
        </button>
        <a href="index.php" class="bg-gray-100 hover:bg-gray-200 text-dark font-bold py-4 px-8 rounded-xl transition-all text-center">Kembali ke Beranda</a>
      </div>
    </div>
    <?php else: ?>

    <?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-xl text-sm font-medium flex items-center gap-2">
      <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <h1 class="text-3xl font-bold text-dark mb-8">Ringkasan Pesanan</h1>

    <!-- Order Summary (loaded from localStorage) -->
    <div id="orderSummary" class="space-y-6">
      <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-lg font-bold text-dark mb-6 flex items-center gap-2">
          <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
          Detail Freelancer
        </h2>
        <div class="grid grid-cols-2 gap-y-4 gap-x-6 text-sm">
          <div>
            <p class="text-gray-400 font-semibold uppercase text-xs mb-1">Nama Freelancer</p>
            <p class="font-bold text-dark" id="sumNama">-</p>
          </div>
          <div>
            <p class="text-gray-400 font-semibold uppercase text-xs mb-1">Layanan</p>
            <p class="font-bold text-dark" id="sumJasa">-</p>
          </div>
          <div>
            <p class="text-gray-400 font-semibold uppercase text-xs mb-1">Tarif</p>
            <p class="font-bold text-accent" id="sumTarif">-</p>
          </div>
          <div>
            <p class="text-gray-400 font-semibold uppercase text-xs mb-1">Satuan</p>
            <p class="font-bold text-dark" id="sumSatuan">-</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-lg font-bold text-dark mb-6 flex items-center gap-2">
          <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
          Detail Pesanan
        </h2>
        <div class="grid grid-cols-2 gap-y-4 gap-x-6 text-sm">
          <div>
            <p class="text-gray-400 font-semibold uppercase text-xs mb-1">Tanggal</p>
            <p class="font-bold text-dark" id="sumTanggal">-</p>
          </div>
          <div>
            <p class="text-gray-400 font-semibold uppercase text-xs mb-1">Alamat</p>
            <p class="font-bold text-dark" id="sumAlamat">-</p>
          </div>
          <div class="col-span-2">
            <p class="text-gray-400 font-semibold uppercase text-xs mb-1">Catatan</p>
            <p class="text-dark" id="sumCatatan">-</p>
          </div>
        </div>
      </div>

      <?php if (!$loggedIn): ?>
      <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-6 flex items-start gap-4">
        <svg class="w-6 h-6 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>
        <div>
          <p class="font-bold text-yellow-800 mb-1">Login diperlukan</p>
          <p class="text-yellow-700 text-sm">Anda perlu <a href="login.php" class="underline font-bold">masuk terlebih dahulu</a> agar pesanan tersimpan di database kami.</p>
        </div>
      </div>
      <?php endif; ?>

      <div class="flex flex-col sm:flex-row gap-4">
        <?php if ($loggedIn): ?>
        <form method="POST" action="" id="confirmForm" class="flex-1">
          <input type="hidden" name="id_layanan" id="formIdLayanan">
          <input type="hidden" name="tanggal_booking" id="formTanggal">
          <input type="hidden" name="alamat_booking" id="formAlamat">
          <input type="hidden" name="catatan" id="formCatatan">
          <button type="submit" class="w-full bg-accent hover:bg-orange-700 text-white font-bold py-4 rounded-xl shadow-[0_8px_20px_-6px_rgba(193,87,42,0.5)] transition-all transform hover:-translate-y-1 text-lg cursor-pointer">
            Konfirmasi & Hubungi via WhatsApp
          </button>
        </form>
        <?php else: ?>
        <button id="btnWaDirect" class="flex-1 bg-green-500 hover:bg-green-600 text-white font-bold py-4 rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.527 3.66 1.438 5.17L2 22l4.93-1.417A9.954 9.954 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
          Hubungi via WhatsApp
        </button>
        <?php endif; ?>
        <a href="javascript:history.back()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-dark font-bold py-4 rounded-xl transition-all text-center">Kembali</a>
      </div>
    </div>

    <div id="noData" class="hidden text-center py-20">
      <p class="text-gray-400 text-lg mb-4">Tidak ada data pesanan.</p>
      <a href="index.php" class="text-accent font-bold hover:underline">Kembali ke Beranda</a>
    </div>
    <?php endif; ?>
  </div>

  <script>
    const data = JSON.parse(localStorage.getItem('worklance_booking') || 'null');

    function buildWaUrl(d) {
      const telp = (d.freelancerTelp || '').replace(/^0/, '62');
      const msg = `Halo ${d.freelancerNama}, saya ingin memesan layanan *${d.namaJasa || d.freelancerJasa}*.\n\n📅 Tanggal: ${d.tanggal}\n📍 Alamat: ${d.alamat}\n📝 Catatan: ${d.catatan || '-'}\n\nTerima kasih!`;
      return `https://wa.me/${telp}?text=${encodeURIComponent(msg)}`;
    }

    <?php if (!$bookingSaved): ?>
    if (data) {
      document.getElementById('sumNama').textContent = data.freelancerNama;
      document.getElementById('sumJasa').textContent = data.namaJasa || data.freelancerJasa;
      document.getElementById('sumTarif').textContent = 'Rp ' + parseInt(data.freelancerTarif).toLocaleString('id-ID');
      document.getElementById('sumSatuan').textContent = data.freelancerSatuan;
      document.getElementById('sumTanggal').textContent = data.tanggal;
      document.getElementById('sumAlamat').textContent = data.alamat;
      document.getElementById('sumCatatan').textContent = data.catatan || '-';

      // Fill hidden form
      const fIdLayanan = document.getElementById('formIdLayanan');
      if (fIdLayanan) fIdLayanan.value = data.idLayanan;
      const fTanggal = document.getElementById('formTanggal');
      if (fTanggal) fTanggal.value = data.tanggal;
      const fAlamat = document.getElementById('formAlamat');
      if (fAlamat) fAlamat.value = data.alamat;
      const fCatatan = document.getElementById('formCatatan');
      if (fCatatan) fCatatan.value = data.catatan;

      // Direct WA button (not logged in)
      const btnDirect = document.getElementById('btnWaDirect');
      if (btnDirect) btnDirect.addEventListener('click', () => window.open(buildWaUrl(data), '_blank'));
    } else {
      document.getElementById('orderSummary').classList.add('hidden');
      document.getElementById('noData').classList.remove('hidden');
    }
    <?php else: ?>
    // After booking saved, WhatsApp button
    const btnWa = document.getElementById('btnWhatsApp');
    if (btnWa && data) {
      btnWa.addEventListener('click', () => {
        window.open(buildWaUrl(data), '_blank');
        localStorage.removeItem('worklance_booking');
      });
    }
    <?php endif; ?>
  </script>

</body>
</html>