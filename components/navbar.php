<?php
/**
 * Global Navbar Component
 * Included across frontend pages.
 */

require_once __DIR__ . '/../config/database.php';

$loggedIn = isClientLoggedIn();
$userName = $_SESSION['user_nama'] ?? '';
$userRole = $_SESSION['user_role'] ?? null;
$userId = $_SESSION['user_id'] ?? null;
$basePath = '/WorkLance/';

// Determine if user is freelancer (active or not)
$isFreelancer = false;
if ($loggedIn && $userRole == 3) {
    // Optionally check if they have active services
    $stmtNav = $pdo->prepare("SELECT 1 FROM layanan WHERE id_pengguna = ? LIMIT 1");
    $stmtNav->execute([$userId]);
    $isFreelancer = (bool)$stmtNav->fetch();
}

$currentPage = $currentPage ?? ''; // Optional variable set by parent pages
?>
<nav class="sticky top-0 z-50 glass-effect">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between h-20 items-center">
      <!-- Logo -->
      <a href="<?= $basePath ?>index.php" class="flex items-center gap-2 group">
        <div class="w-10 h-10 bg-dark text-white rounded-xl flex items-center justify-center font-bold text-xl group-hover:scale-105 transition-transform duration-300 shadow-md">W</div>
        <span class="text-2xl font-bold text-dark tracking-tight">Work<span class="text-accent">Lance</span></span>
      </a>

      <!-- Auth Buttons / User Menu -->
      <div class="hidden md:flex items-center gap-3 relative">
        <?php if ($loggedIn): ?>
          
          <?php if ($userRole == 3): ?>
            <!-- Freelancer Action Buttons -->
            <a href="<?= $basePath ?>kelola-jasa.php" class="px-5 py-2.5 text-sm font-bold <?= $currentPage === 'kelola-jasa' ? 'bg-accent/10 text-accent border border-accent/20' : 'text-dark hover:text-accent border border-gray-200 hover:border-accent bg-white/60 hover:bg-accent/5' ?> transition-colors rounded-full flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
              Kelola Jasa
            </a>
            <a href="<?= $basePath ?>pesanan.php" class="px-5 py-2.5 text-sm font-bold <?= $currentPage === 'pesanan' ? 'bg-accent/10 text-accent border border-accent/20' : 'text-dark hover:text-accent border border-gray-200 hover:border-accent bg-white/60 hover:bg-accent/5' ?> transition-colors rounded-full flex items-center gap-2 mr-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
              Pesanan Masuk
            </a>
          <?php else: ?>
            <!-- Normal Client Button -->
            <a href="<?= $basePath ?>mulai-freelancer.php" class="px-5 py-2.5 text-sm font-bold text-white bg-gradient-to-r from-accent to-orange-500 rounded-full shadow-md shadow-accent/20 hover:shadow-lg hover:shadow-accent/30 transition-all transform hover:-translate-y-0.5 flex items-center gap-2 mr-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
              Daftar Menjadi Freelancer
            </a>
          <?php endif; ?>

          <!-- User Dropdown Wrap -->
          <div class="relative ml-2" id="userMenuWrap">
            <button onclick="document.getElementById('userDropdown').classList.toggle('hidden')" class="flex items-center gap-3 cursor-pointer group focus:outline-none">
              <div class="w-10 h-10 bg-primary/20 text-primary rounded-full flex items-center justify-center font-bold relative">
                <?= getInitials($userName) ?>
                <?php if ($userRole == 3): ?>
                  <div class="absolute bottom-0 right-0 w-3 h-3 <?= $isFreelancer ? 'bg-green-500' : 'bg-gray-400' ?> border-2 border-white rounded-full" title="<?= $isFreelancer ? 'Online' : 'Offline' ?>"></div>
                <?php endif; ?>
              </div>
              <span class="text-sm font-bold text-dark"><?= htmlspecialchars($userName) ?></span>
              <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 group-focus:-rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div id="userDropdown" class="hidden absolute right-0 mt-3 w-52 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50">
              <a href="<?= $basePath ?>pengaturan-akun/informasi-akun.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 font-medium">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Pengaturan Akun
              </a>
              <div class="border-t border-gray-100 my-1"></div>
              <a href="<?= $basePath ?>logout.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Logout
              </a>
            </div>
          </div>
          
        <?php else: ?>
          <!-- Guest Buttons -->
          <a href="<?= $basePath ?>login.php" class="px-5 py-2.5 text-sm font-bold text-dark hover:text-accent transition-colors relative border border-transparent hover:border-gray-200 rounded-full hover:bg-white/60">Masuk</a>
          <a href="<?= $basePath ?>register.php" class="px-7 py-2.5 text-sm font-bold text-white bg-gradient-to-r from-dark to-[#1d2666] rounded-full shadow-lg shadow-dark/20 hover:shadow-xl hover:shadow-dark/30 transition-all transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-dark">Daftar</a>
        <?php endif; ?>
      </div>
      
      <!-- Mobile Menu Button (Hamburger) -->
      <div class="md:hidden flex items-center">
        <button onclick="document.getElementById('mobileMenu').classList.toggle('hidden')" class="text-gray-500 hover:text-dark focus:outline-none">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile Menu Content -->
  <div id="mobileMenu" class="hidden md:hidden bg-white border-t border-gray-100 shadow-xl absolute w-full">
    <div class="px-4 py-6 space-y-4">
      <?php if ($loggedIn): ?>
        <div class="flex items-center gap-3 mb-6 pb-6 border-b border-gray-100">
          <div class="w-10 h-10 bg-primary/20 text-primary rounded-full flex items-center justify-center font-bold">
            <?= getInitials($userName) ?>
          </div>
          <div>
            <p class="font-bold text-dark text-sm"><?= htmlspecialchars($userName) ?></p>
            <p class="text-xs text-gray-500"><?= $userRole == 3 ? 'Freelancer' : 'Klien' ?></p>
          </div>
        </div>
        
        <?php if ($userRole == 3): ?>
          <a href="<?= $basePath ?>kelola-jasa.php" class="block text-base font-bold <?= $currentPage === 'kelola-jasa' ? 'text-accent' : 'text-gray-700' ?>">Kelola Jasa</a>
          <a href="<?= $basePath ?>pesanan.php" class="block text-base font-bold <?= $currentPage === 'pesanan' ? 'text-accent' : 'text-gray-700' ?>">Pesanan Masuk</a>
        <?php else: ?>
          <a href="<?= $basePath ?>mulai-freelancer.php" class="block text-base font-bold text-accent">Daftar Menjadi Freelancer</a>
        <?php endif; ?>
        
        <a href="<?= $basePath ?>pengaturan-akun/informasi-akun.php" class="block text-base font-bold text-gray-700">Pengaturan Akun</a>
        <a href="<?= $basePath ?>logout.php" class="block text-base font-bold text-red-500 pt-4 border-t border-gray-100">Logout</a>
      <?php else: ?>
        <a href="<?= $basePath ?>login.php" class="block text-base font-bold text-gray-700">Masuk</a>
        <a href="<?= $basePath ?>register.php" class="block text-base font-bold text-primary">Daftar</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Close Dropdown when clicking outside -->
<script>
  window.addEventListener('click', function(e) {
    if (document.getElementById('userMenuWrap')) {
      if (!document.getElementById('userMenuWrap').contains(e.target)) {
        const dropdown = document.getElementById('userDropdown');
        if (dropdown && !dropdown.classList.contains('hidden')) {
          dropdown.classList.add('hidden');
        }
      }
    }
  });
</script>
