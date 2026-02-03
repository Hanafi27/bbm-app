<?php
require_once '../includes/auth.php';
require_login();
require_once '../includes/db.php';
require_once '../includes/utils.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$mid = "0000855001598321";
$transaksi = [];
$total_amount = 0;
$total_liter = 0;
$total_transaksi = 0;

// Ambil data untuk filter
$jenis_bbm_list = $pdo->query("SELECT DISTINCT jenis_bbm FROM transaksi WHERE jenis_bbm IS NOT NULL ORDER BY jenis_bbm")->fetchAll(PDO::FETCH_COLUMN);
$tipe_pembayaran_list = $pdo->query("SELECT DISTINCT tt.nama FROM tipe_transaksi tt INNER JOIN transaksi t ON tt.id = t.tipe_id ORDER BY tt.nama")->fetchAll(PDO::FETCH_COLUMN);
$shift_list = $pdo->query("SELECT DISTINCT shift FROM transaksi WHERE shift IS NOT NULL ORDER BY shift")->fetchAll(PDO::FETCH_COLUMN);

// Handle filter
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['filter'])) {
    $where_conditions = [];
    $params = [];
    
    // Filter tanggal
    if (!empty($_POST['tanggal_mulai'] ?? $_GET['tanggal_mulai'] ?? '')) {
        $tanggal_mulai = $_POST['tanggal_mulai'] ?? $_GET['tanggal_mulai'];
        $where_conditions[] = "t.tanggal >= ?";
        $params[] = $tanggal_mulai;
    }
    
    if (!empty($_POST['tanggal_akhir'] ?? $_GET['tanggal_akhir'] ?? '')) {
        $tanggal_akhir = $_POST['tanggal_akhir'] ?? $_GET['tanggal_akhir'];
        $where_conditions[] = "t.tanggal <= ?";
        $params[] = $tanggal_akhir;
    }
    
    // Filter jenis BBM
    if (!empty($_POST['jenis_bbm'] ?? $_GET['jenis_bbm'] ?? '')) {
        $jenis_bbm = $_POST['jenis_bbm'] ?? $_GET['jenis_bbm'];
        $where_conditions[] = "t.jenis_bbm = ?";
        $params[] = $jenis_bbm;
    }
    
    // Filter tipe pembayaran
    if (!empty($_POST['tipe_pembayaran'] ?? $_GET['tipe_pembayaran'] ?? '')) {
        $tipe_pembayaran = $_POST['tipe_pembayaran'] ?? $_GET['tipe_pembayaran'];
        $where_conditions[] = "tt.nama = ?";
        $params[] = $tipe_pembayaran;
    }
    
    // Filter shift
    if (!empty($_POST['shift'] ?? $_GET['shift'] ?? '')) {
        $shift = $_POST['shift'] ?? $_GET['shift'];
        $where_conditions[] = "t.shift = ?";
        $params[] = $shift;
    }
    
    // Filter TID
    if (!empty($_POST['tid'] ?? $_GET['tid'] ?? '')) {
        $tid = $_POST['tid'] ?? $_GET['tid'];
        $where_conditions[] = "t.tid = ?";
        $params[] = $tid;
    }
    
    // Build query
    $sql = "SELECT t.*, tt.nama as tipe_pembayaran, u.nama as admin_nama 
            FROM transaksi t 
            LEFT JOIN tipe_transaksi tt ON t.tipe_id = tt.id 
            LEFT JOIN users u ON t.admin_id = u.id 
            WHERE 1=1";
    
    if (!empty($where_conditions)) {
        $sql .= " AND " . implode(" AND ", $where_conditions);
    }
    
    $sql .= " ORDER BY t.tanggal DESC, t.id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transaksi = $stmt->fetchAll();
    
    // Hitung total
    $total_transaksi = count($transaksi);
    $total_amount = array_sum(array_column($transaksi, 'amount'));
    
    // Hitung total liter (jika ada field jumlah_liter)
    $total_liter = 0;
    foreach ($transaksi as $t) {
        if (isset($t['jumlah_liter'])) {
            $total_liter += $t['jumlah_liter'];
        }
    }
}

// Ambil data untuk export
if (isset($_GET['export']) && !empty($transaksi)) {
    $filename = 'transaksi_filter_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Header CSV
    fputcsv($output, ['Tanggal', 'TID', 'Jenis BBM', 'Tipe Pembayaran', 'Amount', 'Shift', 'Admin']);
    
    // Data
    foreach ($transaksi as $t) {
        fputcsv($output, [
            $t['tanggal'],
            $t['tid'],
            $t['jenis_bbm'],
            $t['tipe_pembayaran'],
            $t['amount'],
            $t['shift'],
            $t['admin_nama']
        ]);
    }
    
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Filter & Panggil Data Transaksi</title>
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
      
      .hover-lift {
        transition: transform 0.2s ease-in-out;
      }
      .hover-lift:hover {
        transform: translateY(-2px);
      }
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
                    <span class="font-semibold text-pertamina-blue">Filter & Panggil Data Transaksi</span>
                </div>
            </nav>
            
            <main class="flex-1 p-4 md:p-8 overflow-auto">
                <div class="w-full max-w-7xl mx-auto">
                    
                    <!-- Filter Form -->
                    <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-6">
                        <div class="flex items-center gap-2 sm:gap-3 mb-6">
                            <div class="p-2 bg-pertamina-blue rounded-lg">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                                </svg>
                            </div>
                            <h2 class="text-lg sm:text-xl font-semibold text-pertamina-blue">🔍 Filter Data Transaksi</h2>
                        </div>
                        
                        <form method="post" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <!-- Tanggal Mulai -->
                                <div>
                                    <label class="block mb-1 font-semibold text-gray-700">Tanggal Mulai</label>
                                    <input type="date" name="tanggal_mulai" value="<?= $_POST['tanggal_mulai'] ?? $_GET['tanggal_mulai'] ?? '' ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pertamina-blue focus:border-transparent">
                                </div>
                                
                                <!-- Tanggal Akhir -->
                                <div>
                                    <label class="block mb-1 font-semibold text-gray-700">Tanggal Akhir</label>
                                    <input type="date" name="tanggal_akhir" value="<?= $_POST['tanggal_akhir'] ?? $_GET['tanggal_akhir'] ?? '' ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pertamina-blue focus:border-transparent">
                                </div>
                                
                                <!-- Jenis BBM -->
                                <div>
                                    <label class="block mb-1 font-semibold text-gray-700">Jenis BBM</label>
                                    <select name="jenis_bbm" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pertamina-blue focus:border-transparent">
                                        <option value="">Semua Jenis BBM</option>
                                        <?php foreach ($jenis_bbm_list as $jenis): ?>
                                            <option value="<?= $jenis ?>" <?= ($_POST['jenis_bbm'] ?? $_GET['jenis_bbm'] ?? '') === $jenis ? 'selected' : '' ?>>
                                                <?= $jenis ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Tipe Pembayaran -->
                                <div>
                                    <label class="block mb-1 font-semibold text-gray-700">Tipe Pembayaran</label>
                                    <select name="tipe_pembayaran" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pertamina-blue focus:border-transparent">
                                        <option value="">Semua Tipe Pembayaran</option>
                                        <?php foreach ($tipe_pembayaran_list as $tipe): ?>
                                            <option value="<?= $tipe ?>" <?= ($_POST['tipe_pembayaran'] ?? $_GET['tipe_pembayaran'] ?? '') === $tipe ? 'selected' : '' ?>>
                                                <?= $tipe ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Shift -->
                                <div>
                                    <label class="block mb-1 font-semibold text-gray-700">Shift</label>
                                    <select name="shift" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pertamina-blue focus:border-transparent">
                                        <option value="">Semua Shift</option>
                                        <?php foreach ($shift_list as $shift): ?>
                                            <option value="<?= $shift ?>" <?= ($_POST['shift'] ?? $_GET['shift'] ?? '') === $shift ? 'selected' : '' ?>>
                                                <?= $shift ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- TID -->
                                <div>
                                    <label class="block mb-1 font-semibold text-gray-700">TID</label>
                                    <input type="text" name="tid" value="<?= $_POST['tid'] ?? $_GET['tid'] ?? '' ?>" placeholder="Masukkan TID"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pertamina-blue focus:border-transparent">
                                </div>
                            </div>
                            
                            <div class="flex flex-col sm:flex-row gap-3 pt-4">
                                <button type="submit" class="flex-1 bg-pertamina-blue hover:bg-pertamina-green text-white font-semibold py-3 rounded-lg shadow-lg transition-all duration-200 hover-lift">
                                    🔍 Cari Data
                                </button>
                                <a href="transaksi-filter.php" class="px-6 bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 rounded-lg shadow-lg transition-all duration-200 hover-lift text-center">
                                    🔄 Reset Filter
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <?php if (!empty($transaksi)): ?>
                    <!-- Hasil Filter -->
                    <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-6">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                            <h3 class="text-lg sm:text-xl font-semibold text-pertamina-blue">
                                📊 Hasil Filter (<?= $total_transaksi ?> transaksi)
                            </h3>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <a href="?<?= http_build_query(array_merge($_GET, ['export' => '1'])) ?>" 
                                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition-colors hover-lift">
                                    📥 Export CSV
                                </a>
                            </div>
                        </div>
                        
                        <!-- Summary Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <div class="text-2xl font-bold text-blue-600"><?= $total_transaksi ?></div>
                                <div class="text-sm text-gray-600">Total Transaksi</div>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                <div class="text-2xl font-bold text-green-600">Rp <?= number_format($total_amount) ?></div>
                                <div class="text-sm text-gray-600">Total Amount</div>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                                <div class="text-2xl font-bold text-purple-600"><?= number_format($total_liter, 2) ?> L</div>
                                <div class="text-sm text-gray-600">Total Liter</div>
                            </div>
                        </div>
                        
                        <!-- Data Table -->
                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full bg-white">
                                <thead>
                                    <tr class="bg-pertamina-blue text-white">
                                        <th class="px-4 py-3 text-left font-semibold">No</th>
                                        <th class="px-4 py-3 text-left font-semibold">Tanggal</th>
                                        <th class="px-4 py-3 text-left font-semibold">TID</th>
                                        <th class="px-4 py-3 text-left font-semibold">Jenis BBM</th>
                                        <th class="px-4 py-3 text-left font-semibold">Tipe Pembayaran</th>
                                        <th class="px-4 py-3 text-left font-semibold">Amount</th>
                                        <th class="px-4 py-3 text-left font-semibold">Shift</th>
                                        <th class="px-4 py-3 text-left font-semibold">Admin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transaksi as $index => $t): ?>
                                    <tr class="hover:bg-gray-50 border-b border-gray-100">
                                        <td class="px-4 py-3"><?= $index + 1 ?></td>
                                        <td class="px-4 py-3"><?= date('d/m/Y', strtotime($t['tanggal'])) ?></td>
                                        <td class="px-4 py-3 font-mono"><?= htmlspecialchars($t['tid']) ?></td>
                                        <td class="px-4 py-3"><?= htmlspecialchars($t['jenis_bbm']) ?></td>
                                        <td class="px-4 py-3"><?= htmlspecialchars($t['tipe_pembayaran']) ?></td>
                                        <td class="px-4 py-3 font-mono">Rp <?= number_format($t['amount']) ?></td>
                                        <td class="px-4 py-3"><?= htmlspecialchars($t['shift']) ?></td>
                                        <td class="px-4 py-3"><?= htmlspecialchars($t['admin_nama']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php elseif (isset($_POST['tanggal_mulai']) || isset($_GET['filter'])): ?>
                    <!-- No Results -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                        <div class="text-yellow-600 mb-2">
                            <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-yellow-800 mb-2">Tidak Ada Data</h3>
                        <p class="text-yellow-700">Tidak ditemukan transaksi yang sesuai dengan kriteria filter yang Anda pilih.</p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Quick Filter Buttons -->
                    <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                        <h3 class="text-lg font-semibold text-pertamina-blue mb-4">⚡ Filter Cepat</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                            <a href="?tanggal_mulai=<?= date('Y-m-d') ?>&tanggal_akhir=<?= date('Y-m-d') ?>" 
                               class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-4 py-3 rounded-lg font-semibold transition-colors hover-lift text-center">
                                📅 Hari Ini
                            </a>
                            <a href="?tanggal_mulai=<?= date('Y-m-d', strtotime('-7 days')) ?>&tanggal_akhir=<?= date('Y-m-d') ?>" 
                               class="bg-green-100 hover:bg-green-200 text-green-800 px-4 py-3 rounded-lg font-semibold transition-colors hover-lift text-center">
                                📊 7 Hari Terakhir
                            </a>
                            <a href="?tanggal_mulai=<?= date('Y-m-01') ?>&tanggal_akhir=<?= date('Y-m-t') ?>" 
                               class="bg-purple-100 hover:bg-purple-200 text-purple-800 px-4 py-3 rounded-lg font-semibold transition-colors hover-lift text-center">
                                📈 Bulan Ini
                            </a>
                            <a href="?jenis_bbm=Pertalite" 
                               class="bg-orange-100 hover:bg-orange-200 text-orange-800 px-4 py-3 rounded-lg font-semibold transition-colors hover-lift text-center">
                                ⛽ Pertalite
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
