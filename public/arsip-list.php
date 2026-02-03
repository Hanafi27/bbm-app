<?php
require_once '../includes/db.php';
session_start();
if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] ?? 'admin') === 'kepala_spbu') {
    header('Location: rekap-transaksi.php');
    exit;
}

// Filter parameters
$search = $_GET['search'] ?? '';
$jenis_bbm_filter = $_GET['jenis_bbm'] ?? '';
$tipe_transaksi_filter = $_GET['tipe_transaksi'] ?? '';
$shift_filter = $_GET['shift'] ?? '';
$tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';

// Pagination settings
$items_per_page = 20; // Jumlah item per halaman
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Build WHERE clause for filters
$where_conditions = ["t.admin_id = ?"];
$params = [$_SESSION['admin_id']];

if (!empty($search)) {
    $where_conditions[] = "(t.mid LIKE ? OR t.tid LIKE ? OR t.jenis_bbm LIKE ? OR tt.nama LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if (!empty($jenis_bbm_filter)) {
    $where_conditions[] = "t.jenis_bbm = ?";
    $params[] = $jenis_bbm_filter;
}

if (!empty($tipe_transaksi_filter)) {
    $where_conditions[] = "tt.nama = ?";
    $params[] = $tipe_transaksi_filter;
}

if (!empty($shift_filter)) {
    $where_conditions[] = "t.shift = ?";
    $params[] = $shift_filter;
}

if (!empty($tanggal_mulai)) {
    $where_conditions[] = "t.tanggal >= ?";
    $params[] = $tanggal_mulai;
}

if (!empty($tanggal_akhir)) {
    $where_conditions[] = "t.tanggal <= ?";
    $params[] = $tanggal_akhir;
}

$where_clause = implode(' AND ', $where_conditions);

// Hitung total records untuk pagination
$total_query = $pdo->prepare("SELECT COUNT(*) as total FROM transaksi t 
                             LEFT JOIN tipe_transaksi tt ON t.tipe_id=tt.id 
                             WHERE $where_clause");
$total_query->execute($params);
$total_records = $total_query->fetch()['total'];
$total_pages = ceil($total_records / $items_per_page);

// Pastikan current_page tidak melebihi total_pages
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

// Ambil data transaksi dengan pagination dan filter
$arsip = $pdo->prepare("SELECT t.*, tt.nama AS tipe_nama, u.nama AS admin_nama 
                       FROM transaksi t 
                       LEFT JOIN tipe_transaksi tt ON t.tipe_id=tt.id 
                       LEFT JOIN users u ON t.admin_id=u.id 
                       WHERE $where_clause
                       ORDER BY t.tanggal DESC, t.id DESC 
                       LIMIT ? OFFSET ?");
$arsip->execute(array_merge($params, [$items_per_page, $offset]));
$arsip = $arsip->fetchAll();

// Get filter options
$jenis_bbm_options = $pdo->query("SELECT DISTINCT jenis_bbm FROM transaksi WHERE admin_id = " . $_SESSION['admin_id'] . " ORDER BY jenis_bbm")->fetchAll(PDO::FETCH_COLUMN);
$tipe_transaksi_options = $pdo->query("SELECT DISTINCT tt.nama FROM tipe_transaksi tt 
                                      INNER JOIN transaksi t ON t.tipe_id = tt.id 
                                      WHERE t.admin_id = " . $_SESSION['admin_id'] . " ORDER BY tt.nama")->fetchAll(PDO::FETCH_COLUMN);
$shift_options = $pdo->query("SELECT DISTINCT shift FROM transaksi WHERE admin_id = " . $_SESSION['admin_id'] . " AND shift IS NOT NULL ORDER BY shift")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Arsip Transaksi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
      .pertamina-blue { color: #0099da; }
      .pertamina-green { color: #43b02a; }
      .pertamina-red { color: #ed1c24; }
      .bg-pertamina-blue { background-color: #0099da; }
      .bg-pertamina-green { background-color: #43b02a; }
      .bg-pertamina-light { background-color: #f6fbfd; }
      .border-pertamina-blue { border-color: #0099da; }
      .border-pertamina-light { border-color: #e3f1fa; }
    </style>
</head>
<body class="bg-pertamina-light min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <?php include '../includes/sidebar.php'; ?>
        <div class="flex-1 flex flex-col h-screen overflow-hidden bg-pertamina-light">
            <!-- Navbar -->
            <nav class="bg-white shadow-md py-3 px-4 md:px-8 flex items-center justify-between border-b-2 border-pertamina-blue flex-shrink-0 z-10">
                <button class="md:hidden text-pertamina-blue focus:outline-none" onclick="toggleSidebar()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <div class="flex items-center gap-3 ml-auto">
                    <span class="font-semibold text-pertamina-blue">Arsip Transaksi</span>
                </div>
            </nav>
            <main class="flex-1 flex flex-col p-4 md:p-8 overflow-hidden">
                <div class="w-full space-y-4 px-2 md:px-8 overflow-y-auto">
                    <?php if (isset($_SESSION['import_success'])): ?>
                    <div id="notif-toast" class="fixed top-4 left-1/2 -translate-x-1/2 z-50 px-4 py-2 rounded-lg shadow-lg text-white text-center text-sm font-semibold animate-fade-in"
                        style="background: linear-gradient(90deg,#43b02a,#0099da);min-width:200px;">
                        <?= $_SESSION['import_success'] ?>
                    </div>
                    <style>@keyframes fade-in{from{opacity:0;transform:translateY(-20px);}to{opacity:1;transform:translateY(0);}}</style>
                    <script>setTimeout(()=>{const n=document.getElementById('notif-toast');if(n)n.style.display='none';},5000);</script>
                    <?php 
                        unset($_SESSION['import_success']); 
                    endif; 
                    ?>
                    
                    <!-- Search and Filter Section -->
                    <div class="bg-white rounded-lg p-4 shadow-sm border border-pertamina-light">
                        <form method="GET" class="space-y-4">
                            <!-- Search Bar -->
                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-semibold mb-2 text-gray-700">Pencarian</label>
                                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                                           placeholder="Cari MID, TID, Jenis BBM, atau Tipe Transaksi..." 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pertamina-blue focus:border-transparent">
                                </div>
                                <div class="flex items-end">
                                    <button type="submit" class="bg-pertamina-blue hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg transition">
                                        🔍 Cari
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Filter Options -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                                <!-- Jenis BBM Filter -->
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-gray-700">Jenis BBM</label>
                                    <select name="jenis_bbm" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pertamina-blue">
                                        <option value="">Semua Jenis BBM</option>
                                        <?php foreach ($jenis_bbm_options as $option): ?>
                                            <option value="<?= htmlspecialchars($option) ?>" <?= $jenis_bbm_filter === $option ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($option) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Tipe Transaksi Filter -->
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-gray-700">Tipe Transaksi</label>
                                    <select name="tipe_transaksi" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pertamina-blue">
                                        <option value="">Semua Tipe</option>
                                        <?php foreach ($tipe_transaksi_options as $option): ?>
                                            <option value="<?= htmlspecialchars($option) ?>" <?= $tipe_transaksi_filter === $option ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($option) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Shift Filter -->
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-gray-700">Shift</label>
                                    <select name="shift" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pertamina-blue">
                                        <option value="">Semua Shift</option>
                                        <?php foreach ($shift_options as $option): ?>
                                            <option value="<?= htmlspecialchars($option) ?>" <?= $shift_filter === $option ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($option) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Tanggal Mulai -->
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-gray-700">Tanggal Mulai</label>
                                    <input type="date" name="tanggal_mulai" value="<?= htmlspecialchars($tanggal_mulai) ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pertamina-blue">
                                </div>
                                
                                <!-- Tanggal Akhir -->
                                <div>
                                    <label class="block text-sm font-semibold mb-2 text-gray-700">Tanggal Akhir</label>
                                    <input type="date" name="tanggal_akhir" value="<?= htmlspecialchars($tanggal_akhir) ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pertamina-blue">
                                </div>
                            </div>
                            
                            <!-- Filter Actions -->
                            <div class="flex justify-between items-center">
                                <div class="flex gap-2">
                                    <button type="submit" class="bg-pertamina-blue hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg transition">
                                        🔍 Terapkan Filter
                                    </button>
                                    <a href="arsip-list.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-4 py-2 rounded-lg transition">
                                        🔄 Reset
                                    </a>
                                </div>
                                <?php if (!empty($search) || !empty($jenis_bbm_filter) || !empty($tipe_transaksi_filter) || !empty($shift_filter) || !empty($tanggal_mulai) || !empty($tanggal_akhir)): ?>
                                <div class="text-sm text-gray-600">
                                    Filter aktif: <?= $total_records ?> hasil ditemukan
                                </div>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Info Pagination -->
                    <div class="flex justify-between items-center bg-white rounded-lg p-4 shadow-sm border border-pertamina-light">
                        <div class="text-sm text-gray-600">
                            Menampilkan <?= $offset + 1 ?> - <?= min($offset + count($arsip), $total_records) ?> dari <?= $total_records ?> transaksi
                        </div>
                        <?php if ($total_pages > 1): ?>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600">Halaman:</span>
                            <select id="pageSelect" class="px-3 py-1 border border-gray-300 rounded text-sm" onchange="changePage(this.value)">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i == $current_page ? 'selected' : '' ?>>
                                        <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <span class="text-sm text-gray-600">dari <?= $total_pages ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-lg border border-pertamina-light flex flex-col">
                        <div class="flex justify-between items-center p-4 border-b border-pertamina-light flex-shrink-0">
                            <h3 class="text-lg font-semibold text-pertamina-blue">Daftar Transaksi</h3>
                            <button id="hapusSemuaBtn" class="bg-red-600 hover:bg-red-700 text-white font-semibold rounded px-4 py-2 shadow transition" onclick="hapusSemuaArsip()">Hapus Semua</button>
                        </div>
                        
                        <div class="overflow-x-auto flex-1">
                            <table class="w-full text-sm text-left">
                                <thead class="sticky top-0 z-10 bg-pertamina-blue/10">
                                    <tr class="pertamina-blue border-b font-semibold text-base">
                                        <th class="py-3 px-4">#</th>
                                        <th class="py-3 px-4">Tanggal</th>
                                        <th class="py-3 px-4">MID</th>
                                        <th class="py-3 px-4">TID</th>
                                        <th class="py-3 px-4">Jenis BBM</th>
                                        <th class="py-3 px-4">Tipe Transaksi</th>
                                        <th class="py-3 px-4">Shift</th>
                                        <th class="py-3 px-4">Amount</th>
                                        <th class="py-3 px-4">Admin</th>
                                        <th class="py-3 px-4 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($arsip) === 0): ?>
                                        <tr><td colspan="10" class="py-6 text-center text-gray-400">Tidak ada transaksi yang ditemukan</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($arsip as $i => $a): ?>
                                            <tr class="border-b last:border-0 even:bg-pertamina-light/60 hover:bg-pertamina-blue/5 transition">
                                                <td class="py-3 px-4 font-semibold text-gray-700"><?= $offset + $i + 1 ?></td>
                                                <td class="py-3 px-4 text-gray-700"><?= htmlspecialchars($a['tanggal']) ?></td>
                                                <td class="py-3 px-4 text-gray-700"><?= htmlspecialchars($a['mid']) ?></td>
                                                <td class="py-3 px-4 text-gray-700"><?= htmlspecialchars($a['tid']) ?></td>
                                                <td class="py-3 px-4 text-gray-700"><?= htmlspecialchars($a['jenis_bbm']) ?></td>
                                                <td class="py-3 px-4 text-gray-700"><?= htmlspecialchars($a['tipe_nama']) ?></td>
                                                <td class="py-3 px-4 text-gray-700"><?= htmlspecialchars($a['shift'] ?? '-') ?></td>
                                                <td class="py-3 px-4 text-gray-700">Rp <?= number_format($a['amount'],0,',','.') ?></td>
                                                <td class="py-3 px-4 text-gray-700"><?= htmlspecialchars($a['admin_nama']) ?></td>
                                                <td class="py-3 px-4 text-center">
                                                    <button type="button" class="editArsipBtn bg-pertamina-green hover:bg-pertamina-blue text-white font-semibold rounded px-4 py-1 mr-1 transition" data-id="<?= $a['id'] ?>">Edit</button>
                                                    <button type="button" class="hapusArsipBtn bg-red-600 hover:bg-red-700 text-white font-semibold rounded px-4 py-1 shadow transition" data-id="<?= $a['id'] ?>">Hapus</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination Controls -->
                        <?php if ($total_pages > 1): ?>
                        <div class="flex justify-center items-center gap-2 p-4 border-t border-pertamina-light bg-gray-50 flex-shrink-0">
                            <!-- Previous Page -->
                            <?php if ($current_page > 1): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page - 1])) ?>" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded hover:bg-gray-50 transition">
                                    ← Sebelumnya
                                </a>
                            <?php else: ?>
                                <span class="px-3 py-2 text-sm bg-gray-100 border border-gray-300 rounded text-gray-400 cursor-not-allowed">
                                    ← Sebelumnya
                                </span>
                            <?php endif; ?>
                            
                            <!-- Page Numbers -->
                            <div class="flex items-center gap-1">
                                <?php
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);
                                
                                if ($start_page > 1): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded hover:bg-gray-50 transition">1</a>
                                    <?php if ($start_page > 2): ?>
                                        <span class="px-2 text-gray-400">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <?php if ($i == $current_page): ?>
                                        <span class="px-3 py-2 text-sm bg-pertamina-blue text-white border border-pertamina-blue rounded"><?= $i ?></span>
                                    <?php else: ?>
                                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded hover:bg-gray-50 transition"><?= $i ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <span class="px-2 text-gray-400">...</span>
                                    <?php endif; ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded hover:bg-gray-50 transition"><?= $total_pages ?></a>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Next Page -->
                            <?php if ($current_page < $total_pages): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page + 1])) ?>" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded hover:bg-gray-50 transition">
                                    Selanjutnya →
                                </a>
                            <?php else: ?>
                                <span class="px-3 py-2 text-sm bg-gray-100 border border-gray-300 rounded text-gray-400 cursor-not-allowed">
                                    Selanjutnya →
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
            <!-- Modal Edit Arsip (struktur, belum aktif) -->
            <div id="modalEditArsip" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30 hidden">
                <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
                    <h2 class="text-lg font-bold mb-4 pertamina-blue">Edit Arsip</h2>
                    <!-- Form edit akan diisi dinamis JS -->
                    <form id="formEditArsip">
                        <!-- Isi form dinamis -->
                    </form>
                    <div class="flex justify-end mt-4">
                        <button type="button" id="closeEditArsip" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-700 mr-2">Batal</button>
                        <button type="submit" form="formEditArsip" class="px-4 py-2 rounded bg-pertamina-green hover:bg-green-600 text-white font-semibold">Simpan</button>
                    </div>
                </div>
            </div>
            <!-- Modal Konfirmasi Hapus Arsip -->
            <div id="modalHapusArsip" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30 hidden">
                <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-sm">
                    <h2 class="text-lg font-bold mb-4 pertamina-red">Hapus Arsip</h2>
                    <p class="mb-4 text-gray-700">Yakin ingin menghapus arsip ini?</p>
                    <div class="flex justify-center gap-3">
                        <button type="button" id="closeHapusArsip" class="px-4 py-2 rounded bg-red-600 text-white font-semibold">Batal</button>
                        <button type="button" id="confirmHapusArsip" class="px-4 py-2 rounded bg-red-600 text-white font-semibold">Hapus</button>
                    </div>
                </div>
            </div>
            <!-- Modal Tambah Arsip -->
            <!-- (Dihapus seluruh modal tambah arsip) -->
            <!-- Popup Sukses -->
            <div id="popupSukses" class="fixed top-6 left-1/2 transform -translate-x-1/2 z-50 bg-pertamina-green text-white font-semibold px-6 py-3 rounded-xl shadow-lg text-center text-base opacity-0 pointer-events-none transition-all duration-500"></div>
        </div>
    </div>

    <script>
        // Fungsi untuk mengubah halaman dengan mempertahankan filter
        function changePage(page) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('page', page);
            window.location.href = '?' + urlParams.toString();
        }
        
        // Fungsi untuk menampilkan popup sukses
        function showPopup(message) {
            const popup = document.getElementById('popupSukses');
            popup.textContent = message;
            popup.classList.remove('opacity-0', 'pointer-events-none');
            popup.classList.add('opacity-100');
            
            setTimeout(() => {
                popup.classList.remove('opacity-100');
                popup.classList.add('opacity-0', 'pointer-events-none');
            }, 3000);
        }

        function hapusSemuaArsip() {
            if (!confirm('Yakin ingin menghapus SEMUA arsip/transaksi? Tindakan ini tidak bisa dibatalkan!')) return;
            fetch('arsip-hapus-semua.php', { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Semua arsip berhasil dihapus!');
                        location.reload();
                    } else {
                        alert('Gagal menghapus semua arsip: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(() => alert('Terjadi kesalahan saat menghapus semua arsip.'));
        }

        // Event listener untuk button edit
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, setting up event listeners...');
            
            // Debug: cek apakah button hapus ada
            const hapusButtons = document.querySelectorAll('.hapusArsipBtn');
            console.log('Jumlah button hapus ditemukan:', hapusButtons.length);
            
            // Debug: cek apakah modal dan button modal ada
            const modalHapus = document.getElementById('modalHapusArsip');
            const confirmBtn = document.getElementById('confirmHapusArsip');
            const closeBtn = document.getElementById('closeHapusArsip');
            
            console.log('Modal hapus:', modalHapus ? 'ditemukan' : 'tidak ditemukan');
            console.log('Button confirm:', confirmBtn ? 'ditemukan' : 'tidak ditemukan');
            console.log('Button close:', closeBtn ? 'ditemukan' : 'tidak ditemukan');
            // Edit Arsip
            document.querySelectorAll('.editArsipBtn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    // Ambil data arsip untuk edit
                    fetch(`arsip-get.php?id=${id}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Isi form edit dengan data yang ada
                                const form = document.getElementById('formEditArsip');
                                form.innerHTML = `
                                    <input type="hidden" name="id" value="${data.arsip.id}">
                                    <div class="mb-4">
                                        <label class="block text-sm font-semibold mb-1">Tanggal</label>
                                        <input type="date" name="tanggal" value="${data.arsip.tanggal}" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-pertamina-blue" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-sm font-semibold mb-1">MID</label>
                                        <input type="text" name="mid" value="${data.arsip.mid}" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-pertamina-blue" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-sm font-semibold mb-1">TID</label>
                                        <input type="text" name="tid" value="${data.arsip.tid}" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-pertamina-blue" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-sm font-semibold mb-1">Jenis BBM</label>
                                        <select name="jenis_bbm" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-pertamina-blue" required>
                                            <option value="Pertalite" ${data.arsip.jenis_bbm === 'Pertalite' ? 'selected' : ''}>Pertalite</option>
                                            <option value="Pertamax" ${data.arsip.jenis_bbm === 'Pertamax' ? 'selected' : ''}>Pertamax</option>
                                            <option value="Dexlite" ${data.arsip.jenis_bbm === 'Dexlite' ? 'selected' : ''}>Dexlite</option>
                                            <option value="Solar" ${data.arsip.jenis_bbm === 'Solar' ? 'selected' : ''}>Solar</option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-sm font-semibold mb-1">Amount</label>
                                        <input type="number" name="amount" value="${data.arsip.amount}" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-pertamina-blue" required>
                                    </div>
                                `;
                                
                                // Tampilkan modal
                                document.getElementById('modalEditArsip').classList.remove('hidden');
                            } else {
                                alert('Gagal mengambil data arsip');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Terjadi kesalahan saat mengambil data');
                        });
                });
            });

            // Hapus Arsip
            document.querySelectorAll('.hapusArsipBtn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    console.log('Button hapus diklik, ID:', id);
                    
                    // Tampilkan modal konfirmasi
                    const modalHapus = document.getElementById('modalHapusArsip');
                    modalHapus.classList.remove('hidden');
                    
                    // Set ID untuk konfirmasi hapus
                    const confirmButton = document.getElementById('confirmHapusArsip');
                    confirmButton.setAttribute('data-id', id);
                    
                    console.log('Modal hapus ditampilkan, confirm button ID:', confirmButton.getAttribute('data-id'));
                });
            });

            // Close modal edit
            document.getElementById('closeEditArsip').addEventListener('click', function() {
                document.getElementById('modalEditArsip').classList.add('hidden');
            });

            // Close modal hapus
            const closeHapusBtn = document.getElementById('closeHapusArsip');
            if (closeHapusBtn) {
                closeHapusBtn.addEventListener('click', function() {
                    console.log('Button close modal hapus diklik');
                    document.getElementById('modalHapusArsip').classList.add('hidden');
                });
            } else {
                console.error('Button closeHapusArsip tidak ditemukan');
            }

            // Submit form edit
            document.getElementById('formEditArsip').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch('arsip-edit.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showPopup('Arsip berhasil diperbarui!');
                        document.getElementById('modalEditArsip').classList.add('hidden');
                        // Reload halaman setelah 1 detik
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        alert('Gagal memperbarui arsip: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memperbarui arsip');
                });
            });

            // Konfirmasi hapus
            const confirmHapusBtn = document.getElementById('confirmHapusArsip');
            if (confirmHapusBtn) {
                confirmHapusBtn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    console.log('Button konfirmasi hapus diklik, ID:', id);
                    
                    if (!id) {
                        alert('ID tidak ditemukan');
                        return;
                    }
                    
                    fetch('arsip-hapus.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({id: id})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showPopup('Arsip berhasil dihapus!');
                            document.getElementById('modalHapusArsip').classList.add('hidden');
                            // Reload halaman setelah 1 detik
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            alert('Gagal menghapus arsip: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat menghapus arsip');
                    });
                });
            } else {
                console.error('Button confirmHapusArsip tidak ditemukan');
            }

            // Close modal ketika klik di luar modal
            document.getElementById('modalEditArsip').addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                }
            });

            document.getElementById('modalHapusArsip').addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html> 