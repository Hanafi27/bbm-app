<?php
require_once '../includes/auth.php';
require_login();
require_once '../includes/db.php';
require_once '../includes/utils.php';
require_once '../includes/validation.php';
require_once '../includes/tid-list.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] ?? 'admin') === 'kepala_spbu') {
    header('Location: rekap-transaksi.php');
    exit;
}

$mid = "0000855001598321";
$errors = [];
$success_message = '';
$analysis_data = [];
$show_analysis = false;

// Handle file upload untuk analisis otomatis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    // Debug: Log untuk troubleshooting
    error_log("Import Otomatis - File upload detected: " . $file['name']);
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Error upload file: ' . $file['error'];
        error_log("Import Otomatis - Upload error: " . $file['error']);
    } elseif ($file['size'] > 10 * 1024 * 1024) {
        $errors[] = 'File terlalu besar (maksimal 10MB)';
    } else {
        $allowed_types = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = 'Tipe file tidak didukung. Gunakan file CSV atau Excel. Tipe file Anda: ' . $file['type'];
            error_log("Import Otomatis - Invalid file type: " . $file['type']);
        } else {
            error_log("Import Otomatis - Starting analysis...");
            $analysis_result = analyze_sales_file($file, $pdo);
            
            if (isset($analysis_result['errors'])) {
                $errors = array_merge($errors, $analysis_result['errors']);
                error_log("Import Otomatis - Analysis errors: " . print_r($analysis_result['errors'], true));
            } else {
                $analysis_data = $analysis_result;
                $show_analysis = true;
                error_log("Import Otomatis - Analysis successful. Data count: " . count($analysis_data['data']) . ", Shifts: " . count($analysis_data['rekap_per_shift']));
            }
        }
    }
}

// Handle import berdasarkan analisis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_analysis'])) {
    $analysis_json = $_POST['analysis_data'] ?? '';
    if (!empty($analysis_json)) {
        $analysis_array = json_decode($analysis_json, true);
        if ($analysis_array) {
            $saved_count = import_from_analysis($analysis_array, $pdo);
            if ($saved_count > 0) {
                $pdo->prepare("INSERT INTO aktivitas (aktivitas, admin_id) VALUES (?, ?)")
                    ->execute(['Import otomatis ' . $saved_count . ' transaksi dari analisis file', $_SESSION['admin_id']]);
                $success_message = "Berhasil import " . $saved_count . " transaksi dari analisis file";
                $show_analysis = false;
                $analysis_data = [];
            }
        }
    }
}

function analyze_sales_file($file, $pdo) {
    $data = [];
    $errors = [];
    $rekap_per_shift = [];
    
    if ($file['type'] === 'text/csv') {
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle) {
            $row = 1;
            while (($row_data = fgetcsv($handle)) !== false) {
                if ($row === 1) {
                    $row++;
                    continue;
                }
                
                $result = validate_import_row($row_data, $row, $pdo);
                if (!empty($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                } else {
                    $data[] = $result['data'];
                    
                    // Buat rekap per shift
                    $tanggal = $result['data']['tanggal'];
                    $shift = $result['data']['shift'];
                    $key = $tanggal . '_' . $shift;
                    
                    if (!isset($rekap_per_shift[$key])) {
                        $rekap_per_shift[$key] = [
                            'tanggal' => $tanggal,
                            'shift' => $shift,
                            'total_transaksi' => 0,
                            'total_liter' => 0,
                            'total_amount' => 0,
                            'jenis_bbm_count' => [],
                            'tipe_pembayaran_count' => []
                        ];
                    }
                    
                    $rekap_per_shift[$key]['total_transaksi']++;
                    $rekap_per_shift[$key]['total_liter'] += $result['data']['jumlah_liter'];
                    $rekap_per_shift[$key]['total_amount'] += $result['data']['amount'];
                    
                    $jenis_bbm = $result['data']['jenis_bbm'];
                    if (!isset($rekap_per_shift[$key]['jenis_bbm_count'][$jenis_bbm])) {
                        $rekap_per_shift[$key]['jenis_bbm_count'][$jenis_bbm] = 0;
                    }
                    $rekap_per_shift[$key]['jenis_bbm_count'][$jenis_bbm]++;
                    
                    $tipe_pembayaran = $result['data']['tipe_pembayaran'];
                    if (!isset($rekap_per_shift[$key]['tipe_pembayaran_count'][$tipe_pembayaran])) {
                        $rekap_per_shift[$key]['tipe_pembayaran_count'][$tipe_pembayaran] = 0;
                    }
                    $rekap_per_shift[$key]['tipe_pembayaran_count'][$tipe_pembayaran]++;
                }
                $row++;
            }
            fclose($handle);
        }
    } else {
        try {
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            array_shift($rows);
            
            foreach ($rows as $index => $row_data) {
                $row_number = $index + 2;
                
                if (empty(array_filter($row_data))) {
                    continue;
                }
                
                $result = validate_import_row($row_data, $row_number, $pdo);
                if (!empty($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                } else {
                    $data[] = $result['data'];
                    
                    $tanggal = $result['data']['tanggal'];
                    $shift = $result['data']['shift'];
                    $key = $tanggal . '_' . $shift;
                    
                    if (!isset($rekap_per_shift[$key])) {
                        $rekap_per_shift[$key] = [
                            'tanggal' => $tanggal,
                            'shift' => $shift,
                            'total_transaksi' => 0,
                            'total_liter' => 0,
                            'total_amount' => 0,
                            'jenis_bbm_count' => [],
                            'tipe_pembayaran_count' => []
                        ];
                    }
                    
                    $rekap_per_shift[$key]['total_transaksi']++;
                    $rekap_per_shift[$key]['total_liter'] += $result['data']['jumlah_liter'];
                    $rekap_per_shift[$key]['total_amount'] += $result['data']['amount'];
                    
                    $jenis_bbm = $result['data']['jenis_bbm'];
                    if (!isset($rekap_per_shift[$key]['jenis_bbm_count'][$jenis_bbm])) {
                        $rekap_per_shift[$key]['jenis_bbm_count'][$jenis_bbm] = 0;
                    }
                    $rekap_per_shift[$key]['jenis_bbm_count'][$jenis_bbm]++;
                    
                    $tipe_pembayaran = $result['data']['tipe_pembayaran'];
                    if (!isset($rekap_per_shift[$key]['tipe_pembayaran_count'][$tipe_pembayaran])) {
                        $rekap_per_shift[$key]['tipe_pembayaran_count'][$tipe_pembayaran] = 0;
                    }
                    $rekap_per_shift[$key]['tipe_pembayaran_count'][$tipe_pembayaran]++;
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Error membaca file Excel: ' . $e->getMessage();
        }
    }
    
    return [
        'data' => $data, 
        'rekap_per_shift' => array_values($rekap_per_shift),
        'errors' => $errors
    ];
}

function validate_import_row($row_data, $row_number, $pdo) {
    $errors = [];
    $data = [];
    
    $row_data = array_map(function($value) {
        return is_string($value) ? trim($value) : $value;
    }, $row_data);
    
    if (count($row_data) < 8) {
        $errors[] = "Baris $row_number: Data tidak lengkap (harus 8 kolom)";
        return ['errors' => $errors];
    }
    
    $tanggal_spbu = $row_data[0];
    if (empty($tanggal_spbu)) {
        $errors[] = "Baris $row_number: Tanggal SPBU tidak boleh kosong";
    } else {
        $date_obj = DateTime::createFromFormat('l, F j, Y', $tanggal_spbu);
        if ($date_obj) {
            $data['tanggal'] = $date_obj->format('Y-m-d');
        } else {
            $errors[] = "Baris $row_number: Format tanggal SPBU salah";
        }
    }
    
    $shift_spbu = $row_data[1];
    if (empty($shift_spbu)) {
        $errors[] = "Baris $row_number: Shift SPBU tidak boleh kosong";
    } else {
        $valid_shifts = ['1', '2', '3'];
        if (!in_array($shift_spbu, $valid_shifts)) {
            $errors[] = "Baris $row_number: Shift SPBU '$shift_spbu' tidak valid";
        } else {
            $shift_map = ['1' => 'Pagi', '2' => 'Siang', '3' => 'Malam'];
            $data['shift'] = $shift_map[$shift_spbu];
        }
    }
    
    $jumlah_liter = $row_data[4];
    if (empty($jumlah_liter)) {
        $errors[] = "Baris $row_number: Jumlah Liter tidak boleh kosong";
    } else {
        $liter_clean = str_replace(' L', '', $jumlah_liter);
        if (!is_numeric($liter_clean) || $liter_clean <= 0) {
            $errors[] = "Baris $row_number: Jumlah Liter '$jumlah_liter' tidak valid";
        } else {
            $data['jumlah_liter'] = $liter_clean;
        }
    }
    
    $harga = $row_data[5];
    if (empty($harga)) {
        $errors[] = "Baris $row_number: Harga tidak boleh kosong";
    } else {
        $harga_clean = str_replace(['.', ','], '', $harga);
        if (!is_numeric($harga_clean) || $harga_clean <= 0) {
            $errors[] = "Baris $row_number: Harga '$harga' tidak valid";
        } else {
            $data['harga_per_liter'] = $harga_clean;
        }
    }
    
    if (isset($data['jumlah_liter']) && isset($data['harga_per_liter'])) {
        $data['amount'] = $data['jumlah_liter'] * $data['harga_per_liter'];
    }
    
    $data['tid'] = 'D2DF8372';
    $data['jenis_bbm'] = 'Pertalite';
    
    $stmt = $pdo->prepare("SELECT id FROM tipe_transaksi WHERE nama = 'BCA'");
    $stmt->execute();
    $tipe_result = $stmt->fetch();
    if ($tipe_result) {
        $data['tipe_id'] = $tipe_result['id'];
        $data['tipe_pembayaran'] = 'BCA';
    } else {
        $errors[] = "Baris $row_number: Tipe pembayaran default 'BCA' tidak ditemukan";
    }
    
    return ['data' => $data, 'errors' => $errors];
}

function import_from_analysis($analysis_array, $pdo) {
    $saved_count = 0;
    $mid = "0000855001598321";
    
    foreach ($analysis_array as $data) {
        try {
            $stmt = $pdo->prepare("INSERT INTO transaksi (tanggal, mid, tid, tipe_id, jenis_bbm, amount, shift, admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['tanggal'], $mid, $data['tid'], $data['tipe_id'], 
                $data['jenis_bbm'], $data['amount'], $data['shift'], $_SESSION['admin_id']
            ]);
            $saved_count++;
        } catch (Exception $e) {
            // Log error
        }
    }
    
    return $saved_count;
}

$tipe = $pdo->query("SELECT * FROM tipe_transaksi")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Import Otomatis File Penjualan</title>
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
                    <span class="font-semibold text-pertamina-blue">Import Otomatis File Penjualan</span>
                </div>
            </nav>
            
            <main class="flex-1 p-4 md:p-8 overflow-auto">
                <div class="w-full max-w-7xl mx-auto">
                    
                    <?php if ($success_message): ?>
                    <div id="notif-toast" class="fixed top-6 left-1/2 -translate-x-1/2 z-50 px-6 py-3 rounded-xl shadow-lg text-white text-center text-base font-semibold animate-fade-in"
                        style="background: linear-gradient(90deg,#43b02a,#0099da);min-width:220px;">
                        <?= $success_message ?>
                    </div>
                    <style>@keyframes fade-in{from{opacity:0;transform:translateY(-20px);}to{opacity:1;transform:translateY(0);}}</style>
                    <script>setTimeout(()=>{const n=document.getElementById('notif-toast');if(n)n.style.display='none';},3000);</script>
                    <?php endif; ?>
                    
                    <?php if ($errors): ?>
                        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 sm:p-6">
                            <div class="flex items-center gap-2 sm:gap-3 mb-4">
                                <div class="p-2 bg-red-500 rounded-lg">
                                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-base sm:text-lg font-semibold text-red-800">Error Analisis File:</h3>
                            </div>
                            <div class="bg-white rounded-lg p-4 max-h-60 overflow-y-auto">
                                <ul class="list-disc list-inside space-y-1 sm:space-y-2 text-red-700">
                                    <?php foreach ($errors as $e) echo "<li class='text-xs sm:text-sm'>$e</li>"; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Debug: Tampilkan status variabel
                    if (isset($_GET['debug'])) {
                        echo "<div style='background: yellow; padding: 10px; margin: 10px;'>";
                        echo "<strong>Debug Info:</strong><br>";
                        echo "show_analysis: " . ($show_analysis ? 'true' : 'false') . "<br>";
                        echo "analysis_data empty: " . (empty($analysis_data) ? 'true' : 'false') . "<br>";
                        echo "analysis_data keys: " . (isset($analysis_data) ? implode(', ', array_keys($analysis_data)) : 'not set') . "<br>";
                        echo "rekap_per_shift exists: " . (isset($analysis_data['rekap_per_shift']) ? 'true' : 'false') . "<br>";
                        echo "</div>";
                    }
                    ?>
                    
                    <?php if ($show_analysis && !empty($analysis_data) && isset($analysis_data['rekap_per_shift'])): ?>
                    <!-- Analisis Data -->
                    <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-6">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-2 sm:gap-3">
                            <h2 class="text-lg sm:text-xl font-semibold text-pertamina-blue">
                                📊 Analisis File Penjualan
                            </h2>
                            <div class="text-base sm:text-lg font-bold text-pertamina-green">
                                Total: <?= count($analysis_data['data']) ?> transaksi
                            </div>
                        </div>
                        
                        <!-- Rekap per Shift -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">📈 Rekap per Shift</h3>
                            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                                <?php foreach ($analysis_data['rekap_per_shift'] as $rekap): ?>
                                <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg border border-blue-200">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h4 class="font-semibold text-blue-800"><?= date('d/m/Y', strtotime($rekap['tanggal'])) ?></h4>
                                            <p class="text-sm text-blue-600">Shift <?= $rekap['shift'] ?></p>
                                        </div>
                                        <span class="bg-blue-500 text-white px-2 py-1 rounded text-xs font-semibold">
                                            <?= $rekap['total_transaksi'] ?> transaksi
                                        </span>
                                    </div>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Total Liter:</span>
                                            <span class="font-semibold"><?= number_format($rekap['total_liter'], 2) ?> L</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Total Amount:</span>
                                            <span class="font-semibold text-green-600">Rp <?= number_format($rekap['total_amount']) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Total Keseluruhan -->
                        <?php 
                        $total_transaksi = array_sum(array_column($analysis_data['rekap_per_shift'], 'total_transaksi'));
                        $total_liter = array_sum(array_column($analysis_data['rekap_per_shift'], 'total_liter'));
                        $total_amount = array_sum(array_column($analysis_data['rekap_per_shift'], 'total_amount'));
                        ?>
                        <div class="bg-gradient-to-r from-green-50 to-blue-50 p-4 rounded-lg border border-green-200">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">💰 Total Keseluruhan</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-blue-600"><?= $total_transaksi ?></div>
                                    <div class="text-sm text-gray-600">Total Transaksi</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600"><?= number_format($total_liter, 2) ?> L</div>
                                    <div class="text-sm text-gray-600">Total Liter</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-purple-600">Rp <?= number_format($total_amount) ?></div>
                                    <div class="text-sm text-gray-600">Total Amount</div>
                                </div>
                            </div>
                        </div>
                        
                        <form method="post" class="mt-6">
                            <input type="hidden" name="analysis_data" value="<?= htmlspecialchars(json_encode($analysis_data['data'])) ?>">
                            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 justify-center">
                                <button type="submit" name="import_analysis" class="px-6 sm:px-8 py-2 sm:py-3 bg-pertamina-green text-white rounded-lg hover:bg-green-600 transition-all duration-200 font-semibold hover-lift shadow-md text-sm sm:text-base">
                                    ✅ Import <?= count($analysis_data['data']) ?> Transaksi
                                </button>
                                <a href="transaksi-import-otomatis.php" class="px-6 sm:px-8 py-2 sm:py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-all duration-200 font-semibold hover-lift shadow-md text-center text-sm sm:text-base">
                                    ❌ Batal
                                </a>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Upload Form -->
                    <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                        <div class="flex items-center gap-2 sm:gap-3 mb-6">
                            <div class="p-2 bg-pertamina-green rounded-lg">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h2 class="text-lg sm:text-xl font-semibold text-pertamina-blue">Upload File Penjualan untuk Analisis Otomatis</h2>
                        </div>
                        
                        <form method="post" enctype="multipart/form-data" class="space-y-6">
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 sm:p-8 text-center hover:border-pertamina-blue transition-colors">
                                <div class="mb-4">
                                    <svg class="mx-auto h-8 w-8 sm:h-12 sm:w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-base sm:text-lg font-semibold text-gray-700 mb-2">Pilih File Penjualan</label>
                                    <input type="file" name="file" accept=".csv,.xlsx,.xls" 
                                           class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pertamina-blue focus:border-transparent text-sm sm:text-base" required>
                                </div>
                                <p class="text-sm text-gray-500">Format: CSV, Excel (.xlsx, .xls) - maksimal 10MB</p>
                            </div>
                            
                            <div class="bg-gradient-to-r from-blue-50 to-pertamina-light p-4 sm:p-6 rounded-lg border border-blue-200">
                                <h3 class="font-semibold text-blue-800 mb-4 text-base sm:text-lg">🔍 Fitur Analisis Otomatis:</h3>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-sm text-blue-700">
                                    <div class="space-y-2">
                                        <p><strong>📊 Rekap per Shift:</strong> Analisis total transaksi, liter, dan amount per shift</p>
                                        <p><strong>⛽ Jenis BBM:</strong> Menghitung distribusi jenis BBM yang terjual</p>
                                        <p><strong>💳 Tipe Pembayaran:</strong> Analisis metode pembayaran yang digunakan</p>
                                    </div>
                                    <div class="space-y-2">
                                        <p><strong>📈 Total Keseluruhan:</strong> Ringkasan total penjualan dari file</p>
                                        <p><strong>✅ Validasi Otomatis:</strong> Pengecekan format dan konsistensi data</p>
                                        <p><strong>🚀 Import Massal:</strong> Import semua data sekaligus ke database</p>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="w-full bg-pertamina-blue hover:bg-pertamina-green text-white font-semibold py-3 sm:py-4 rounded-lg shadow-lg transition-all duration-200 text-base sm:text-lg hover-lift">
                                🔍 Analisis File Penjualan
                            </button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
