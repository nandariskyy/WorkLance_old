<?php
require_once __DIR__ . '/config/database.php';

// Jika sudah login, redirect
if (isClientLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE email = ? AND id_role IN (2, 3)");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $user['password'] === $password) {
            $_SESSION['user_id'] = $user['id_pengguna'];
            $_SESSION['user_nama'] = $user['nama_pengguna'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['id_role'];
            $_SESSION['user_username'] = $user['username'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masuk | WorkLance</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/WorkLance/src/output.css">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen font-sans">
  <div class="w-full max-w-md p-8 bg-white rounded-3xl shadow-xl shadow-primary/10 border border-gray-100 m-4">
    <div class="text-center mb-8">
      <a href="index.php" class="inline-flex items-center gap-2 group mb-6">
        <div class="w-10 h-10 bg-dark text-white rounded-xl flex items-center justify-center font-bold text-xl shadow-md">W</div>
        <span class="text-2xl font-bold text-dark tracking-tight">Work<span class="text-accent">Lance</span></span>
      </a>
      <h2 class="text-2xl font-bold text-dark">Selamat Datang Kembali</h2>
      <p class="text-gray-500 mt-2">Masuk untuk mengelola pekerjaan atau penawaran Anda.</p>
    </div>

    <?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-xl text-sm font-medium flex items-center gap-2">
      <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-5">
      <div>
        <label class="block text-sm font-bold text-dark mb-2">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white transition-colors" placeholder="nama@email.com" required>
      </div>
      <div>
        <div class="flex justify-between items-center mb-2">
           <label class="block text-sm font-bold text-dark">Password</label>
           <a href="#" class="text-sm font-medium text-accent hover:underline">Lupa Password?</a>
        </div>
        <input type="password" name="password" class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white transition-colors" placeholder="••••••••" required>
      </div>
      <button type="submit" class="w-full bg-dark hover:bg-blue-900 text-white font-bold py-3.5 rounded-xl shadow-lg transition-all transform hover:-translate-y-0.5 mt-2 text-lg cursor-pointer">
        Masuk
      </button>
    </form>
    <p class="text-center text-gray-600 mt-8">
      Belum punya akun? <a href="register.php" class="text-accent font-bold hover:underline">Daftar sekarang</a>
    </p>
  </div>
</body>
</html>