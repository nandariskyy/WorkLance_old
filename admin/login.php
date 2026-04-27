<?php
require_once __DIR__ . '/../config/database.php';

// Jika sudah login, redirect ke dashboard
if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE email = ? AND id_role = 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && $admin['password'] === $password) {
            $_SESSION['admin_id'] = $admin['id_pengguna'];
            $_SESSION['admin_nama'] = $admin['nama_pengguna'];
            $_SESSION['admin_email'] = $admin['email'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Email atau password salah, atau Anda bukan admin.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | WorkLance</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/WorkLance/src/output.css">
</head>
<body class="bg-dark flex justify-center min-h-screen font-sans">
  <div class="flex flex-col md:flex-row w-full flex-1">
    
    <!-- Left Screen - Branding -->
    <div class="hidden md:flex md:w-1/2 bg-gradient-to-br from-dark to-[#0d1336] p-12 flex-col justify-center relative overflow-hidden text-white">
      <div class="absolute top-0 right-0 w-96 h-96 bg-primary/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
      <div class="absolute bottom-0 left-0 w-96 h-96 bg-accent/20 rounded-full blur-3xl translate-y-1/3 -translate-x-1/3"></div>
      
      <div class="relative z-10 max-w-lg mx-auto">
        <div class="flex items-center gap-3 mb-10">
          <div class="w-12 h-12 bg-white text-dark rounded-xl flex items-center justify-center font-bold text-2xl shadow-xl">W</div>
          <span class="text-4xl font-bold tracking-tight">Work<span class="text-accent">Lance</span></span>
        </div>
        <h1 class="text-4xl font-bold leading-tight mb-6">Portal<br/><span class="text-primary">Administrator</span></h1>
        <p class="text-gray-400 text-lg leading-relaxed">
          Kelola platform, pantau aktivitas pengguna, dan optimalkan layanan WorkLance melalui satu dashboard terpusat.
        </p>
      </div>
    </div>

    <!-- Right Screen - Login Form -->
    <div class="w-full md:w-1/2 bg-gray-50 flex items-center justify-center p-8 relative">
      <div class="w-full max-w-md bg-white rounded-3xl p-8 sm:p-10 shadow-2xl border border-gray-100 relative z-10 border-t-4 border-t-accent">
        
        <!-- Mobile Logo -->
        <div class="md:hidden flex items-center gap-2 justify-center mb-8">
          <div class="w-10 h-10 bg-dark text-white rounded-xl flex items-center justify-center font-bold text-xl shadow-md">W</div>
          <span class="text-2xl font-bold text-dark tracking-tight">Work<span class="text-accent">Lance</span></span>
        </div>

        <div class="mb-8">
          <h2 class="text-3xl font-bold text-dark mb-2">Admin Login</h2>
          <p class="text-gray-500 font-medium">Masukkan kredensial administrator Anda.</p>
        </div>

        <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-xl text-sm font-medium flex items-center gap-2">
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
          <div>
            <label class="block text-sm font-bold text-dark mb-2">Email Admin</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                </svg>
              </div>
              <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="w-full pl-11 pr-4 py-3.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white transition-colors text-dark font-medium" placeholder="admin@worklance.id" required>
            </div>
          </div>
          
          <div>
            <div class="flex justify-between items-center mb-2">
               <label class="block text-sm font-bold text-dark">Password</label>
            </div>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
              <input type="password" name="password" class="w-full pl-11 pr-4 py-3.5 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-accent focus:bg-white transition-colors text-dark font-medium" placeholder="••••••••" required>
            </div>
          </div>

          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded">
              <label for="remember-me" class="ml-2 block text-sm text-gray-600 font-medium">Ingat saya</label>
            </div>
          </div>
          
          <button type="submit" class="w-full bg-accent hover:bg-orange-700 text-white font-bold py-4 rounded-xl shadow-[0_8px_20px_-6px_rgba(193,87,42,0.5)] transition-all transform hover:-translate-y-1 text-lg cursor-pointer">
            Masuk ke Dashboard
          </button>
        </form>
        
        <div class="mt-8 pt-6 border-t border-gray-100 flex items-center justify-center text-sm text-gray-400">
           <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
           </svg>
           Koneksi aman & terenkripsi
        </div>
      </div>
    </div>
  </div>
</body>
</html>
