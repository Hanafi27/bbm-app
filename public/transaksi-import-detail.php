<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/auth.php';
require_login();
require_once '../includes/db.php';
require_once '../includes/utils.php';
require_once '../includes/validation.php';
require_once '../includes/tid-list.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] ?? 'admin') === 'kepala_spbu') {
    header('Location: rekap-transaksi.php');
    exit;
}

// Debug: Log session data
error_log("=== DETAIL IMPORT PAGE ACCESSED ===");
error_log("Session data: " . print_r($_SESSION, true));
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("HTTP Referer: " . ($_SERVER['HTTP_REFERER'] ?? 'none'));
error_log("Session ID: " . session_id());
error_log("Session status: " . session_status());

// Ambil data dari session
$import_data = $_SESSION['import_detail_data'] ?? [];
$import_ids = $_SESSION['import_detail_ids'] ?? [];
$success_message = $_SESSION['import_success'] ?? '';

// Jika ada daftar ID, baca ulang dari DB agar selalu mengikuti perubahan di arsip
if (!empty($import_ids)) {
    try {
        $placeholders = implode(',', array_fill(0, count($import_ids), '?'));
        $stmt = $pdo->prepare("SELECT t.*, tt.nama AS tipe_pembayaran_nama FROM transaksi t LEFT JOIN tipe_transaksi tt ON t.tipe_id = tt.id WHERE t.admin_id = ? AND t.id IN ($placeholders) ORDER BY t.tanggal DESC, t.id DESC");
        $params = array_merge([$_SESSION['admin_id']], $import_ids);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $mapped = [];
        foreach ($rows as $r) {
            $mapped[] = [
                'tanggal_transaksi' => $r['tanggal'],
                'shift' => $r['shift'] ?? '-',
                'mid' => $r['mid'] ?? '',
                'tid' => $r['tid'] ?? '',
                'jenis_bbm' => $r['jenis_bbm'] ?? '',
                'tipe_transaksi' => $r['tipe_pembayaran_nama'] ?? '',
                'jumlah_liter' => null,
                'harga_per_liter' => null,
                'total_amount' => isset($r['amount']) ? (float)$r['amount'] : 0,
            ];
        }
        $import_data = $mapped;
    } catch (Exception $e) {
        error_log('Read by IDs error: ' . $e->getMessage());
    }
}

// Fallback: jika tidak ada data di session, coba ambil dari database berdasarkan impor terakhir
$from_db = false;
if (empty($import_data)) {
    try {
        // Cari aktivitas impor terakhir milik admin saat ini
        $stmt = $pdo->prepare("SELECT aktivitas FROM aktivitas WHERE admin_id = ? AND aktivitas LIKE 'Import % transaksi%' ORDER BY waktu DESC LIMIT 1");
        $stmt->execute([$_SESSION['admin_id']]);
        $lastActivity = $stmt->fetchColumn();

        if ($lastActivity && preg_match('/Import\s+(\d+)\s+transaksi/i', $lastActivity, $m)) {
            $lastCount = (int)$m[1];
            if ($lastCount > 0) {
                // Ambil N transaksi terakhir milik admin (diasumsikan batch terakhir)
                $stmt2 = $pdo->prepare("SELECT t.*, tt.nama AS tipe_pembayaran_nama FROM transaksi t LEFT JOIN tipe_transaksi tt ON t.tipe_id = tt.id WHERE t.admin_id = ? ORDER BY t.id DESC LIMIT {$lastCount}");
                $stmt2->execute([$_SESSION['admin_id']]);
                $rows = $stmt2->fetchAll();

                if ($rows && count($rows) > 0) {
                    // Map ke struktur yang digunakan tampilan detail
                    $mapped = [];
                    foreach ($rows as $r) {
                        $mapped[] = [
                            'tanggal_transaksi' => $r['tanggal'],
                            'shift' => $r['shift'] ?? '-',
                            'mid' => $r['mid'] ?? '',
                            'tid' => $r['tid'] ?? '',
                            'jenis_bbm' => $r['jenis_bbm'] ?? '',
                            'tipe_transaksi' => $r['tipe_pembayaran_nama'] ?? '',
                            'jumlah_liter' => null, // tidak tersimpan di DB
                            'harga_per_liter' => null, // tidak tersimpan di DB
                            'total_amount' => isset($r['amount']) ? (float)$r['amount'] : 0,
                        ];
                    }
                    $import_data = $mapped;
                    $from_db = true;
                    if (empty($success_message)) {
                        $success_message = 'Menampilkan detail import terakhir dari database.';
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log('Fallback DB error: ' . $e->getMessage());
    }
}

// Debug: Log import data
error_log("Import data count: " . count($import_data));
error_log("Success message: " . $success_message);
error_log("Import data structure: " . print_r($import_data, true));

// Jangan hapus data session agar halaman bisa dibuka ulang setelah import

// Jika tidak ada data, tampilkan halaman kosong dengan pesan
$has_data = !empty($import_data);
error_log("Has data: " . ($has_data ? 'true' : 'false'));

// Hitung statistik
$total_amount = array_sum(array_map(function($r){ return isset($r['total_amount']) ? (float)$r['total_amount'] : 0; }, $import_data));
$total_liter = array_sum(array_map(function($r){ return isset($r['jumlah_liter']) && is_numeric($r['jumlah_liter']) ? (float)$r['jumlah_liter'] : 0; }, $import_data));

// Deteksi ketersediaan field liter/harga
$has_liter_fields = false;
foreach ($import_data as $row) {
    if (isset($row['jumlah_liter']) && isset($row['harga_per_liter']) && $row['jumlah_liter'] !== null && $row['harga_per_liter'] !== null) {
        $has_liter_fields = true;
        break;
    }
}

// Group by date
$grouped_by_date = [];
foreach ($import_data as $row) {
    $date = $row['tanggal_transaksi'];
    if (!isset($grouped_by_date[$date])) {
        $grouped_by_date[$date] = [];
    }
    $grouped_by_date[$date][] = $row;
}

// Group by shift
$grouped_by_shift = [];
foreach ($import_data as $row) {
    $shift = $row['shift'];
    if (!isset($grouped_by_shift[$shift])) {
        $grouped_by_shift[$shift] = [];
    }
    $grouped_by_shift[$shift][] = $row;
}

// Group by BBM type
$grouped_by_bbm = [];
foreach ($import_data as $row) {
    $bbm = $row['jenis_bbm'];
    if (!isset($grouped_by_bbm[$bbm])) {
        $grouped_by_bbm[$bbm] = [];
    }
    $grouped_by_bbm[$bbm][] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Transaksi Import - BBM App</title>
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
        
        /* Custom scrollbar */
        .overflow-y-auto::-webkit-scrollbar {
            width: 6px;
        }
        .overflow-y-auto::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        .overflow-y-auto::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Animasi untuk hover effects */
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
                    <span class="font-semibold text-pertamina-blue">Detail Transaksi Import</span>
                </div>
            </nav>
            
            <main class="flex-1 p-4 md:p-8 overflow-auto">
                <div class="w-full max-w-7xl mx-auto">
                    
                    <?php if (!$has_data): ?>
                    <!-- Tampilan ketika tidak ada data -->
                    <div class="text-center py-12">
                        <div class="bg-white rounded-lg shadow-lg p-8 max-w-md mx-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Tidak Ada Data Import</h3>
                            <p class="text-gray-600 mb-6">Belum ada data transaksi yang diimport. Silakan import file Excel terlebih dahulu.</p>
                            <a href="transaksi-import.php" class="inline-flex items-center gap-2 px-4 py-2 bg-pertamina-blue text-white rounded-lg hover:bg-blue-700 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                Import Transaksi
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    
                    <?php if ($success_message): ?>
                    <div id="notif-toast" class="fixed top-4 left-1/2 -translate-x-1/2 z-50 px-4 py-2 rounded-lg shadow-lg text-white text-center text-sm font-semibold animate-fade-in"
                        style="background: linear-gradient(90deg,#43b02a,#0099da);min-width:200px;">
                        <?= $success_message ?>
                    </div>
                    <style>@keyframes fade-in{from{opacity:0;transform:translateY(-20px);}to{opacity:1;transform:translateY(0);}}</style>
                    <script>setTimeout(()=>{const n=document.getElementById('notif-toast');if(n)n.style.display='none';},5000);</script>
                    <?php endif; ?>
                    
                    <!-- Header Summary -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                            <div>
                                <h1 class="text-2xl font-bold text-pertamina-blue mb-2">
                                    📊 Detail Transaksi Import Berhasil
                                </h1>
                                <p class="text-sm text-gray-600">
                                    Data transaksi telah berhasil disimpan ke arsip
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-4">
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-pertamina-green"><?= count($import_data) ?></div>
                                    <div class="text-xs text-gray-600">Total Transaksi</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-pertamina-blue"><?= number_format($total_liter, 1) ?></div>
                                    <div class="text-xs text-gray-600">Total Liter</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-pertamina-red">Rp <?= number_format($total_amount) ?></div>
                                    <div class="text-xs text-gray-600">Total Amount</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        
                        <!-- Additional Statistics -->
                        <div class="md:col-span-3 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg p-4 border border-purple-200 mb-4">
                            <h3 class="font-semibold text-purple-800 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                Statistik Detail
                            </h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="text-center">
                                    <div class="text-lg font-bold text-purple-600"><?= count(array_unique(array_column($import_data, 'tid'))) ?></div>
                                    <div class="text-xs text-gray-600">TID Unik</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-indigo-600"><?= count(array_unique(array_column($import_data, 'tipe_transaksi'))) ?></div>
                                    <div class="text-xs text-gray-600">Tipe Transaksi</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-blue-600"><?= count(array_unique(array_column($import_data, 'tanggal_transaksi'))) ?></div>
                                    <div class="text-xs text-gray-600">Hari Transaksi</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-green-600"><?= number_format($total_amount / count($import_data), 0) ?></div>
                                    <div class="text-xs text-gray-600">Rata-rata/Transaksi</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Summary by Date -->
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                            <h3 class="font-semibold text-blue-800 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Ringkasan per Tanggal
                            </h3>
                            <div class="space-y-3">
                                <?php foreach ($grouped_by_date as $date => $transactions): 
                                    $date_total_liter = array_sum(array_column($transactions, 'jumlah_liter'));
                                    $date_total_amount = array_sum(array_column($transactions, 'total_amount'));
                                ?>
                                <div class="bg-white rounded-lg p-2 border border-blue-200">
                                    <div class="flex justify-between items-center text-sm mb-1">
                                        <span class="font-medium"><?= date('d/m/Y', strtotime($date)) ?></span>
                                        <span class="text-blue-600 font-semibold"><?= count($transactions) ?> transaksi</span>
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        <div class="flex justify-between">
                                            <span>Total Liter:</span>
                                            <span class="font-medium"><?= number_format($date_total_liter, 1) ?> L</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Total Amount:</span>
                                            <span class="font-medium">Rp <?= number_format($date_total_amount) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Summary by Shift -->
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                            <h3 class="font-semibold text-green-800 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Ringkasan per Shift
                            </h3>
                            <div class="space-y-3">
                                <?php foreach ($grouped_by_shift as $shift => $transactions): 
                                    $shift_total_liter = array_sum(array_column($transactions, 'jumlah_liter'));
                                    $shift_total_amount = array_sum(array_column($transactions, 'total_amount'));
                                ?>
                                <div class="bg-white rounded-lg p-2 border border-green-200">
                                    <div class="flex justify-between items-center text-sm mb-1">
                                        <span class="font-medium"><?= $shift ?></span>
                                        <span class="text-green-600 font-semibold"><?= count($transactions) ?> transaksi</span>
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        <div class="flex justify-between">
                                            <span>Total Liter:</span>
                                            <span class="font-medium"><?= number_format($shift_total_liter, 1) ?> L</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Total Amount:</span>
                                            <span class="font-medium">Rp <?= number_format($shift_total_amount) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Summary by BBM -->
                        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4 border border-red-200">
                            <h3 class="font-semibold text-red-800 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Ringkasan per Jenis BBM
                            </h3>
                            <div class="space-y-3">
                                <?php foreach ($grouped_by_bbm as $bbm => $transactions): 
                                    $bbm_total_liter = array_sum(array_column($transactions, 'jumlah_liter'));
                                    $bbm_total_amount = array_sum(array_column($transactions, 'total_amount'));
                                ?>
                                <div class="bg-white rounded-lg p-2 border border-red-200">
                                    <div class="flex justify-between items-center text-sm mb-1">
                                        <span class="font-medium"><?= $bbm ?></span>
                                        <span class="text-red-600 font-semibold"><?= count($transactions) ?> transaksi</span>
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        <div class="flex justify-between">
                                            <span>Total Liter:</span>
                                            <span class="font-medium"><?= number_format($bbm_total_liter, 1) ?> L</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Total Amount:</span>
                                            <span class="font-medium">Rp <?= number_format($bbm_total_amount) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detailed Transaction Table -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-4">
                            <h2 class="text-xl font-bold text-pertamina-blue flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Detail Transaksi Lengkap
                            </h2>
                            <div class="flex gap-2">
                                <a href="transaksi-import-detail-csv.php" class="px-4 py-2 bg-pertamina-green text-white rounded-lg hover:bg-green-600 transition-all duration-200 text-sm flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Export CSV
                                </a>
                                <a href="transaksi-import-detail-pdf.php" target="_blank" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-200 text-sm flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Export PDF
                                </a>
                                <button onclick="printTable()" class="px-4 py-2 bg-pertamina-blue text-white rounded-lg hover:bg-blue-600 transition-all duration-200 text-sm flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                    </svg>
                                    Print
                                </button>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full bg-white" id="transactionTable">
                                <thead>
                                    <tr class="bg-gradient-to-r from-pertamina-blue to-blue-600 text-white">
                                        <th class="px-3 py-3 text-left font-semibold text-sm">No</th>
                                        <th class="px-3 py-3 text-left font-semibold text-sm">Tanggal</th>
                                        <th class="px-3 py-3 text-left font-semibold text-sm">Shift</th>
                                        <th class="px-3 py-3 text-left font-semibold text-sm">MID</th>
                                        <th class="px-3 py-3 text-left font-semibold text-sm">TID</th>
                                        <th class="px-3 py-3 text-left font-semibold text-sm">Jenis BBM</th>
                                        <th class="px-3 py-3 text-left font-semibold text-sm">Tipe Transaksi</th>
                                        <th class="px-3 py-3 text-left font-semibold text-sm">Jumlah Liter</th>
                                        <th class="px-3 py-3 text-left font-semibold text-sm">Harga/Liter</th>
                                        <th class="px-3 py-3 text-left font-semibold text-sm">Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($import_data as $index => $row): ?>
                                    <tr class="hover:bg-gray-50 border-b border-gray-100 transition-colors">
                                        <td class="px-3 py-3 text-sm font-semibold text-gray-700"><?= $index + 1 ?></td>
                                        <td class="px-3 py-3 text-sm text-gray-700">
                                            <div class="font-medium"><?= date('d/m/Y', strtotime($row['tanggal_transaksi'])) ?></div>
                                            <div class="text-xs text-gray-500"><?= date('l', strtotime($row['tanggal_transaksi'])) ?></div>
                                        </td>
                                        <td class="px-3 py-3 text-sm">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                                <?= $row['shift'] === 'Pagi' ? 'bg-yellow-100 text-yellow-800' : 
                                                   ($row['shift'] === 'Siang' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800') ?>">
                                                <?= $row['shift'] ?>
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-sm font-mono text-gray-700"><?= htmlspecialchars($row['mid']) ?></td>
                                        <td class="px-3 py-3 text-sm font-mono text-gray-700"><?= htmlspecialchars($row['tid']) ?></td>
                                        <td class="px-3 py-3 text-sm">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                                <?= $row['jenis_bbm'] === 'Pertalite' ? 'bg-green-100 text-green-800' : 
                                                   ($row['jenis_bbm'] === 'Pertamax' ? 'bg-blue-100 text-blue-800' : 
                                                   ($row['jenis_bbm'] === 'Solar' ? 'bg-gray-100 text-gray-800' : 'bg-purple-100 text-purple-800')) ?>">
                                                <?= htmlspecialchars($row['jenis_bbm']) ?>
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-sm text-gray-700"><?= htmlspecialchars($row['tipe_transaksi']) ?></td>
                                        <td class="px-3 py-3 text-sm font-mono text-gray-700"><?= ($row['jumlah_liter'] !== null && $row['jumlah_liter'] !== '') ? number_format((float)$row['jumlah_liter'], 1) . ' L' : '-' ?></td>
                                        <td class="px-3 py-3 text-sm font-mono text-gray-700"><?= ($row['harga_per_liter'] !== null && $row['harga_per_liter'] !== '') ? ('Rp ' . number_format((float)$row['harga_per_liter'])) : '-' ?></td>
                                        <td class="px-3 py-3 text-sm font-mono font-semibold text-green-600">Rp <?= number_format($row['total_amount']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 justify-center mb-6">
                        <a href="transaksi-import.php" class="px-6 py-3 bg-pertamina-blue hover:bg-blue-600 text-white rounded-lg transition-all duration-200 font-semibold hover-lift shadow-lg text-center text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Import Transaksi Baru
                        </a>
                        <a href="arsip-list.php" class="px-6 py-3 bg-pertamina-green hover:bg-green-600 text-white rounded-lg transition-all duration-200 font-semibold hover-lift shadow-lg text-center text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Lihat Arsip Transaksi
                        </a>
                        <a href="index.php" class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 font-semibold hover-lift shadow-lg text-center text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            Kembali ke Dashboard
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        // Fungsi untuk print tabel
        function printTable() {
            const printContent = document.getElementById('transactionTable').outerHTML;
            const originalContent = document.body.innerHTML;
            
            document.body.innerHTML = `
                <div style="padding: 20px;">
                    <h2 style="text-align: center; margin-bottom: 20px;">Detail Transaksi Import</h2>
                    ${printContent}
                </div>
            `;
            
            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        }
        
        // Auto hide notification after 5 seconds
        setTimeout(() => {
            const notif = document.getElementById('notif-toast');
            if (notif) {
                notif.style.display = 'none';
            }
        }, 5000);
    </script>
</body>
</html>
