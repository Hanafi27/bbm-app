<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
$admin_nama = $_SESSION['admin_nama'] ?? 'Admin';
$role = $_SESSION['role'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              pertamina: {
                blue: '#0099da',
                red: '#ed1c24',
                green: '#43b02a',
                light: '#f4f8fb',
                dark: '#222',
              }
            }
          }
        }
      }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
      .sidebar-enter {
        transform: translateX(-100%);
      }
      .sidebar-enter-active {
        transform: translateX(0);
        transition: transform 0.3s;
      }
      .sidebar-leave {
        transform: translateX(0);
      }
      .sidebar-leave-active {
        transform: translateX(-100%);
        transition: transform 0.3s;
      }
      .table-striped tbody tr:nth-child(odd) { background-color: #f4f8fb; }
    </style>
</head>
<body class="bg-pertamina-light min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed md:static z-30 top-0 left-0 h-screen w-64 bg-white border-r border-gray-200 flex flex-col h-full py-8 px-6 space-y-6 shadow-2xl transition-transform duration-300 -translate-x-full md:translate-x-0">
            <div class="flex items-center gap-2 mb-2 justify-center shrink-0">
                <img src="../assets/Pertamina_Logo.svg" alt="Logo Pertamina" class="w-32 h-32 object-contain mb-2" />
            </div>
            <nav class="flex-1 flex flex-col gap-2 overflow-y-auto">
                <?php if ($role === 'kepala_spbu'): ?>
                    <a href="rekap-transaksi.php" class="flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-black bg-white shadow hover:bg-pertamina-light transition border border-pertamina-blue"><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 17a4 4 0 004 4h10a4 4 0 004-4V7a4 4 0 00-4-4H7a4 4 0 00-4 4v10z' /></svg>Rekap Transaksi</a>
                <?php else: ?>
                    <a href="dashboard.php" class="flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-black bg-white shadow hover:bg-pertamina-light transition border border-pertamina-blue"><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 12l2-2m0 0l7-7 7 7M13 5v6h6m-6 0v6m0 0H7m6 0h6' /></svg>Dashboard</a>
                    <a href="transaksi-import.php" class="flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-black hover:bg-pertamina-blue/10 transition"><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 4v16m8-8H4' /></svg>Input Transaksi</a>
                    <a href="arsip-list.php" class="flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-black hover:bg-pertamina-blue/10 transition"><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 17v-2a4 4 0 014-4h2a4 4 0 014 4v2' /></svg>Daftar Arsip</a>
                    <a href="rekap-transaksi.php" class="flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-black hover:bg-pertamina-blue/10 transition"><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 17a4 4 0 004 4h10a4 4 0 004-4V7a4 4 0 00-4-4H7a4 4 0 00-4 4v10z' /></svg>Rekap Transaksi</a>
                    <a href="tipe-transaksi.php" class="flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-black hover:bg-pertamina-blue/10 transition"><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' /></svg>Tipe Transaksi</a>
                <?php endif; ?>
                <a href="logout.php" class="flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-pertamina-red hover:bg-white hover:text-pertamina-red transition"><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-9V4m0 0H5a2 2 0 00-2 2v12a2 2 0 002 2h6' /></svg>Logout</a>
            </nav>
            <div class="mt-auto pt-8 flex flex-col items-center sticky bottom-0 bg-white pb-2">
                <span class="text-gray-500 text-xs mb-1">Waktu Sekarang</span>
                <span id="sidebar-clock" class="text-xl font-mono text-black"></span>
            </div>
        </aside>
        <!-- Overlay for mobile -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-30 z-20 hidden md:hidden" onclick="toggleSidebar()"></div>
        <!-- Main Content -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden bg-pertamina-light">
            <!-- Navbar -->
            <nav class="bg-white shadow-md py-3 px-4 md:px-8 flex items-center justify-between border-b-2 border-pertamina-blue flex-shrink-0 z-10">
                <button class="md:hidden text-pertamina-blue focus:outline-none" onclick="toggleSidebar()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <div class="flex items-center gap-3 ml-auto">
                    <span class="font-semibold text-pertamina-blue">👤 <?= htmlspecialchars($admin_nama) ?></span>
                </div>
            </nav>
            <main class="flex-1 flex flex-col items-center justify-center p-4 md:p-8" style="margin-left: 0;">
                <div class="w-full max-w-none space-y-8 px-2 md:px-8">
                    <?php if ($role === 'kepala_spbu'): ?>
                        <h1 class="text-3xl font-extrabold text-pertamina-blue mb-2">Rekap Transaksi</h1>
                        <p class="text-gray-600 mb-4">Berikut adalah informasi rekap transaksi dan penjualan BBM di SPBU Anda.</p>
                    <?php else: ?>
                        <h1 class="text-3xl font-extrabold text-pertamina-blue mb-2">Selamat Datang, <?= htmlspecialchars($admin_nama) ?>!</h1>
                        <p class="text-gray-600 mb-4">Ini adalah dashboard admin aplikasi BBM. Berikut ringkasan data dan aktivitas terbaru.</p>
                    <?php endif; ?>
                    <!-- Statistik Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4 flex-shrink-0">
                        <div class="bg-pertamina-blue/10 rounded-xl p-4 flex flex-col items-center shadow">
                            <div id="stat-transaksi" class="text-2xl font-bold text-pertamina-blue mb-1">0</div>
                            <div class="text-sm text-gray-700">Transaksi Bulan Ini</div>
                        </div>
                        <div class="bg-pertamina-red/10 rounded-xl p-4 flex flex-col items-center shadow">
                            <div id="stat-arsip" class="text-2xl font-bold text-pertamina-red mb-1">0</div>
                            <div class="text-sm text-gray-700">Arsip</div>
                        </div>
                        <div class="bg-pertamina-green/10 rounded-xl p-4 flex flex-col items-center shadow">
                            <div id="stat-tipe" class="text-2xl font-bold text-pertamina-green mb-1">0</div>
                            <div class="text-sm text-gray-700">Tipe Transaksi</div>
                        </div>
                        <div class="bg-gray-100 rounded-xl p-4 flex flex-col items-center shadow">
                            <div id="stat-user" class="text-2xl font-bold text-gray-700 mb-1">0</div>
                            <div class="text-sm text-gray-700">Admin/User</div>
                        </div>
                    </div>
                    <!-- Quick Actions -->
                    <?php if ($role !== 'kepala_spbu'): ?>
                    <div class="flex flex-wrap gap-4 justify-center mb-4 flex-shrink-0">
                        <a href="transaksi-import.php" class="bg-pertamina-blue hover:bg-pertamina-red text-white font-semibold px-6 py-2 rounded shadow transition-all duration-200 flex items-center gap-2"><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 4v16m8-8H4' /></svg>Input Transaksi</a>
                        <a href="arsip-list.php" class="bg-pertamina-red hover:bg-pertamina-blue text-white font-semibold px-6 py-2 rounded shadow transition-all duration-200 flex items-center gap-2"><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 17v-2a4 4 0 014-4h2a4 4 0 014 4v2' /></svg>Daftar Arsip</a>
                        <a href="rekap-transaksi.php" class="bg-pertamina-green hover:bg-pertamina-blue text-white font-semibold px-6 py-2 rounded shadow transition-all duration-200 flex items-center gap-2"><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 17a4 4 0 004 4h10a4 4 0 004-4V7a4 4 0 00-4-4H7a4 4 0 00-4 4v10z' /></svg>Rekap Transaksi</a>
                        <a href="tipe-transaksi.php" class="bg-gray-400 hover:bg-pertamina-blue text-white font-semibold px-6 py-2 rounded shadow transition-all duration-200 flex items-center gap-2"><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' /></svg>Tipe Transaksi</a>
                    </div>
                    <?php endif; ?>
                    <!-- Grafik & Aktivitas dalam satu baris -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white rounded-lg shadow p-4 border border-gray-100 flex flex-col justify-between h-full min-h-[240px]">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-block w-2 h-2 rounded-full bg-pertamina-blue"></span>
                                <span class="text-base font-semibold text-gray-700">Harian (7 Hari)</span>
                            </div>
                            <div class="flex-1 flex items-center justify-center">
                                <canvas id="chartHarian" height="120"></canvas>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4 border border-gray-100 flex flex-col justify-between h-full min-h-[240px]">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-block w-2 h-2 rounded-full bg-pertamina-green"></span>
                                <span class="text-base font-semibold text-gray-700">Bulanan (12 Bulan)</span>
                            </div>
                            <div class="flex-1 flex items-center justify-center">
                                <canvas id="chartBulanan" height="120"></canvas>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg border border-gray-100 p-4 overflow-x-auto flex flex-col h-full min-h-[240px]">
                            <h3 class="text-base font-bold text-pertamina-blue mb-1 text-left">Aktivitas Terbaru</h3>
                            <div class="flex-1 overflow-y-auto">
                                <table class="w-full text-xs text-left table-striped" id="activity-table">
                                    <thead>
                                        <tr class="text-pertamina-blue border-b font-semibold">
                                            <th class="py-1 pr-2">Aktivitas</th>
                                            <th class="py-1">Waktu</th>
                                        </tr>
                                    </thead>
                                    <tbody id="activity-tbody">
                                        <!-- Diisi dinamis oleh JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
      function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        if (sidebar.classList.contains('-translate-x-full')) {
          sidebar.classList.remove('-translate-x-full');
          overlay.classList.remove('hidden');
        } else {
          sidebar.classList.add('-translate-x-full');
          overlay.classList.add('hidden');
        }
      }
      // Warna Pertamina
      const warnaPertamina = [
        '#0099da', // biru
        '#43b02a', // hijau
        '#ed1c24', // merah
        '#f6fbfd', // abu/krem muda
      ];

      let chartHarian = null;
      let chartBulanan = null;

      async function fetchDashboardData() {
        try {
          const res = await fetch('dashboard-data.php');
          const data = await res.json();
          // Statistik
          document.getElementById('stat-transaksi').textContent = data.statistik.transaksi || 0;
          document.getElementById('stat-arsip').textContent = data.statistik.arsip || 0;
          document.getElementById('stat-tipe').textContent = data.statistik.tipe || 0;
          document.getElementById('stat-user').textContent = data.statistik.user || 0;
          // Aktivitas
          const tbody = document.getElementById('activity-tbody');
          tbody.innerHTML = '';
          if (data.aktivitas && data.aktivitas.length > 0) {
            data.aktivitas.forEach(item => {
              const tr = document.createElement('tr');
              tr.innerHTML = `<td class='py-1 pr-2'>${item.text}</td><td class='py-1 text-xs text-gray-500'>${item.time}</td>`;
              tbody.appendChild(tr);
            });
          } else {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td colspan='2' class='py-2 text-center text-gray-400'>Belum ada aktivitas</td>`;
            tbody.appendChild(tr);
          }
          // Grafik Harian
          if (chartHarian) chartHarian.destroy();
          const elHarian = document.getElementById('chartHarian');
          if (elHarian) {
            chartHarian = new Chart(elHarian.getContext('2d'), {
              type: 'line',
              data: {
                labels: data.grafik_harian.labels,
                datasets: [{
                  label: 'Penjualan',
                  data: data.grafik_harian.data,
                  backgroundColor: warnaPertamina[0] + '22',
                  borderColor: warnaPertamina[0],
                  borderWidth: 2,
                  pointBackgroundColor: warnaPertamina[2],
                  tension: 0.3,
                  fill: true,
                }]
              },
              options: {
                responsive: true,
                plugins: {
                  legend: { display: false },
                  tooltip: { enabled: true, callbacks: { label: ctx => 'Rp ' + ctx.parsed.y.toLocaleString('id-ID') } }
                },
                scales: {
                  x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 }, color: '#888' }
                  },
                  y: {
                    grid: { color: '#f4f8fb' },
                    ticks: {
                      font: { size: 10 },
                      color: '#888',
                      callback: value => 'Rp ' + value.toLocaleString('id-ID')
                    }
                  }
                }
              }
            });
          }
          // Grafik Bulanan
          if (chartBulanan) chartBulanan.destroy();
          const elBulanan = document.getElementById('chartBulanan');
          if (elBulanan) {
            chartBulanan = new Chart(elBulanan.getContext('2d'), {
              type: 'line',
              data: {
                labels: data.grafik_bulanan.labels,
                datasets: [{
                  label: 'Penjualan',
                  data: data.grafik_bulanan.data,
                  backgroundColor: warnaPertamina[1] + '22',
                  borderColor: warnaPertamina[1],
                  borderWidth: 2,
                  pointBackgroundColor: warnaPertamina[2],
                  tension: 0.3,
                  fill: true,
                }]
              },
              options: {
                responsive: true,
                plugins: {
                  legend: { display: false },
                  tooltip: { enabled: true, callbacks: { label: ctx => 'Rp ' + ctx.parsed.y.toLocaleString('id-ID') } }
                },
                scales: {
                  x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 }, color: '#888' }
                  },
                  y: {
                    grid: { color: '#f4f8fb' },
                    ticks: {
                      font: { size: 10 },
                      color: '#888',
                      callback: value => 'Rp ' + value.toLocaleString('id-ID')
                    }
                  }
                }
              }
            });
          }
        } catch (e) {
          // Error handling (optional)
        }
      }
      fetchDashboardData();
      setInterval(fetchDashboardData, 5000);

      // Jam real-time sidebar
      function updateSidebarClock() {
        const el = document.getElementById('sidebar-clock');
        if (!el) return;
        const now = new Date();
        el.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
      }
      setInterval(updateSidebarClock, 1000);
      updateSidebarClock();
    </script>
</body>
</html> 