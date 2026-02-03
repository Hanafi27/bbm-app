<?php
require_once '../includes/auth.php';
require_login();
require_once '../includes/db.php';
require_once '../includes/utils.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Filter parameters
$tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';
$jenis_bbm = $_GET['jenis_bbm'] ?? '';
$shift = $_GET['shift'] ?? '';
$tipe_pembayaran = $_GET['tipe_pembayaran'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($tanggal_mulai) {
    $where_conditions[] = "t.tanggal >= ?";
    $params[] = $tanggal_mulai;
}

if ($tanggal_akhir) {
    $where_conditions[] = "t.tanggal <= ?";
    $params[] = $tanggal_akhir;
}

if ($jenis_bbm) {
    $where_conditions[] = "t.jenis_bbm = ?";
    $params[] = $jenis_bbm;
}

if ($shift) {
    $where_conditions[] = "t.shift = ?";
    $params[] = $shift;
}

if ($tipe_pembayaran) {
    $where_conditions[] = "tt.nama = ?";
    $params[] = $tipe_pembayaran;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get transactions
$query = "SELECT t.*, tt.nama as tipe_pembayaran_nama 
          FROM transaksi t 
          LEFT JOIN tipe_transaksi tt ON t.tipe_id = tt.id 
          $where_clause 
          ORDER BY t.tanggal DESC, t.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Get summary statistics
$summary_query = "SELECT 
                    COUNT(*) as total_transaksi,
                    SUM(t.amount) as total_amount,
                    SUM(CASE WHEN t.jenis_bbm = 'Pertalite' THEN 1 ELSE 0 END) as pertalite_count,
                    SUM(CASE WHEN t.jenis_bbm = 'Pertamax' THEN 1 ELSE 0 END) as pertamax_count,
                    SUM(CASE WHEN t.jenis_bbm = 'Solar' THEN 1 ELSE 0 END) as solar_count
                  FROM transaksi t 
                  LEFT JOIN tipe_transaksi tt ON t.tipe_id = tt.id 
                  $where_clause";

$summary_stmt = $pdo->prepare($summary_query);
$summary_stmt->execute($params);
$summary = $summary_stmt->fetch();

// Get unique values for filters
$jenis_bbm_list = $pdo->query("SELECT DISTINCT jenis_bbm FROM transaksi WHERE jenis_bbm IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
$shift_list = $pdo->query("SELECT DISTINCT shift FROM transaksi WHERE shift IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
$tipe_pembayaran_list = $pdo->query("SELECT DISTINCT nama FROM tipe_transaksi")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Penjualan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .pertamina-blue { color: #0099da; }
        .pertamina-green { color: #43b02a; }
        .bg-pertamina-blue { background-color: #0099da; }
        .bg-pertamina-green { background-color: #43b02a; }
        .bg-pertamina-light { background-color: #f6fbfd; }
        .border-pertamina-blue { border-color: #0099da; }
        
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
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="flex items-center gap-3 ml-auto">
                    <span class="font-semibold text-pertamina-blue">Dashboard Penjualan</span>
                </div>
            </nav>
            
            <main class="flex-1 p-4 md:p-8 overflow-auto">
                <div class="w-full max-w-7xl mx-auto">
                    
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white rounded-lg shadow-md p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Total Transaksi</p>
                                    <p class="text-2xl font-bold text-blue-600"><?= number_format($summary['total_transaksi']) ?></p>
                                </div>
                                <div class="p-3 bg-blue-100 rounded-lg">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-md p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Total Pendapatan</p>
                                    <p class="text-2xl font-bold text-green-600">Rp <?= number_format($summary['total_amount']) ?></p>
                                </div>
                                <div class="p-3 bg-green-100 rounded-lg">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-md p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Pertalite</p>
                                    <p class="text-2xl font-bold text-yellow-600"><?= number_format($summary['pertalite_count']) ?></p>
                                </div>
                                <div class="p-3 bg-yellow-100 rounded-lg">
                                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-md p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Pertamax</p>
                                    <p class="text-2xl font-bold text-red-600"><?= number_format($summary['pertamax_count']) ?></p>
                                </div>
                                <div class="p-3 bg-red-100 rounded-lg">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filter Section -->
                    <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-6">
                        <h3 class="text-lg font-semibold text-pertamina-blue mb-4">🔍 Filter Data</h3>
                        <form method="get" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                                <input type="date" name="tanggal_mulai" value="<?= htmlspecialchars($tanggal_mulai) ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pertamina-blue focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                                <input type="date" name="tanggal_akhir" value="<?= htmlspecialchars($tanggal_akhir) ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pertamina-blue focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis BBM</label>
                                <select name="jenis_bbm" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pertamina-blue focus:border-transparent">
                                    <option value="">Semua</option>
                                    <?php foreach ($jenis_bbm_list as $bbm): ?>
                                        <option value="<?= htmlspecialchars($bbm) ?>" <?= $jenis_bbm === $bbm ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($bbm) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Shift</label>
                                <select name="shift" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pertamina-blue focus:border-transparent">
                                    <option value="">Semua</option>
                                    <?php foreach ($shift_list as $s): ?>
                                        <option value="<?= htmlspecialchars($s) ?>" <?= $shift === $s ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="flex items-end">
                                <button type="submit" class="w-full bg-pertamina-blue hover:bg-pertamina-green text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                                    🔍 Filter
                                </button>
                            </div>
                        </form>
                        
                        <!-- Quick Filter Buttons -->
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="?tanggal_mulai=<?= date('Y-m-d') ?>&tanggal_akhir=<?= date('Y-m-d') ?>" 
                               class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm hover:bg-blue-200 transition-colors">
                                Hari Ini
                            </a>
                            <a href="?tanggal_mulai=<?= date('Y-m-d', strtotime('-7 days')) ?>&tanggal_akhir=<?= date('Y-m-d') ?>" 
                               class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm hover:bg-green-200 transition-colors">
                                7 Hari Terakhir
                            </a>
                            <a href="?tanggal_mulai=<?= date('Y-m-01') ?>&tanggal_akhir=<?= date('Y-m-t') ?>" 
                               class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm hover:bg-purple-200 transition-colors">
                                Bulan Ini
                            </a>
                            <a href="?jenis_bbm=Pertalite" 
                               class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm hover:bg-yellow-200 transition-colors">
                                Pertalite
                            </a>
                            <a href="dashboard-penjualan.php" 
                               class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm hover:bg-gray-200 transition-colors">
                                Reset Filter
                            </a>
                        </div>
                    </div>
                    
                    <!-- Data Table -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                <h3 class="text-lg font-semibold text-pertamina-blue">
                                    📊 Data Penjualan (<?= count($transactions) ?> transaksi)
                                </h3>
                                <div class="flex gap-2">
                                    <a href="import-penjualan.php" class="px-4 py-2 bg-pertamina-green text-white rounded-lg hover:bg-green-600 transition-colors text-sm">
                                        ➕ Import Baru
                                    </a>
                                    <a href="transaksi-import.php" class="px-4 py-2 bg-pertamina-blue text-white rounded-lg hover:bg-blue-600 transition-colors text-sm">
                                        ➕ Input Manual
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shift</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis BBM</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe Pembayaran</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">TID</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            Tidak ada data penjualan yang ditemukan
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($transactions as $transaction): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= date('d/m/Y', strtotime($transaction['tanggal'])) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                                    <?= $transaction['shift'] === 'Pagi' ? 'bg-yellow-100 text-yellow-800' : 
                                                        ($transaction['shift'] === 'Siang' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800') ?>">
                                                    <?= htmlspecialchars($transaction['shift']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($transaction['jenis_bbm']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($transaction['tipe_pembayaran_nama']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                                                Rp <?= number_format($transaction['amount']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($transaction['tid']) ?>
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
        </div>
    </div>
</body>
</html>
