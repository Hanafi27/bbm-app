<?php
// Sidebar menu untuk semua halaman
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? 'admin';
?>
<aside id="sidebar" class="fixed md:static z-30 top-0 left-0 h-screen w-64 bg-white border-r border-gray-200 flex flex-col h-full py-8 px-6 space-y-6 shadow-2xl transition-transform duration-300 -translate-x-full md:translate-x-0">
    <div class="flex items-center gap-2 mb-2 justify-center shrink-0">
        <img src="../assets/Pertamina_Logo.svg" alt="Logo Pertamina" class="w-32 h-32 object-contain mb-2" />
    </div>
    <nav class="flex-1 flex flex-col gap-2 overflow-y-auto">
        <a href="dashboard.php" class="flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-black bg-white shadow hover:bg-pertamina-light transition border border-pertamina-blue"><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 12l2-2m0 0l7-7 7 7M13 5v6h6m-6 0v6m0 0H7m6 0h6' /></svg>Dashboard</a>
        <?php if ($role !== 'kepala_spbu'): ?>
        <a href="transaksi-import.php" class="flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-black hover:bg-pertamina-blue/10 transition"><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12' /></svg>Input Transaksi</a>
        <a href="arsip-list.php" class="flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-black hover:bg-pertamina-blue/10 transition"><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 17v-2a4 4 0 014-4h2a4 4 0 014 4v2' /></svg>Daftar Arsip</a>
        <a href="tipe-transaksi.php" class="flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-black hover:bg-pertamina-blue/10 transition"><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' /></svg>Tipe Transaksi</a>
        <?php endif; ?>
        <a href="rekap-transaksi.php" class="flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-black hover:bg-pertamina-blue/10 transition"><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 17a4 4 0 004 4h10a4 4 0 004-4V7a4 4 0 00-4-4H7a4 4 0 00-4 4v10z' /></svg>Rekap Transaksi</a>
        <a href="logout.php" class="flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-pertamina-red hover:bg-white hover:text-pertamina-red transition"><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-9V4m0 0H5a2 2 0 00-2 2v12a2 2 0 002 2h6' /></svg>Logout</a>
    </nav>
    <div class="mt-auto pt-8 flex flex-col items-center sticky bottom-0 bg-white pb-2">
        <span class="text-gray-500 text-xs mb-1">Waktu Sekarang</span>
        <span id="sidebar-clock" class="text-xl font-mono text-black"></span>
    </div>
</aside>
<script>
  function updateSidebarClock() {
    const el = document.getElementById('sidebar-clock');
    if (!el) return;
    const now = new Date();
    el.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  }
  setInterval(updateSidebarClock, 1000);
  updateSidebarClock();
</script> 