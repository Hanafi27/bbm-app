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

$mid = "0000855001598321";
$errors = [];
$success_message = '';
$import_result = null;

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    // Validasi file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Error upload file: ' . $file['error'];
    } elseif ($file['size'] > 10 * 1024 * 1024) {
        $errors[] = 'File terlalu besar (maksimal 10MB)';
    } else {
        $allowed_types = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = 'Tipe file tidak didukung. Gunakan file CSV atau Excel. Tipe file Anda: ' . $file['type'];
        } else {
            // Proses import
            $import_result = process_import_file($file, $pdo);
            if (isset($import_result['errors'])) {
                $errors = array_merge($errors, $import_result['errors']);
            } else {
                $success_message = "Berhasil import " . $import_result['saved_count'] . " transaksi dari file!";
            }
        }
    }
}

function process_import_file($file, $pdo) {
    $data = [];
    $errors = [];
    $saved_count = 0;
    
    try {
        if ($file['type'] === 'text/csv') {
            $handle = fopen($file['tmp_name'], 'r');
            if ($handle) {
                $row = 1;
                while (($row_data = fgetcsv($handle)) !== false) {
                    if ($row === 1) {
                        $row++;
                        continue; // Skip header
                    }
                    
                    $result = validate_and_process_row($row_data, $row, $pdo);
                    if (!empty($result['errors'])) {
                        $errors = array_merge($errors, $result['errors']);
                    } else {
                        $data[] = $result['data'];
                    }
                    $row++;
                }
                fclose($handle);
            } else {
                $errors[] = 'Tidak bisa membuka file CSV';
            }
        } else {
            // Excel file
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Skip header row
            array_shift($rows);
            
            foreach ($rows as $index => $row_data) {
                $row_number = $index + 2;
                
                // Skip empty rows
                if (empty(array_filter($row_data))) {
                    continue;
                }
                
                $result = validate_and_process_row($row_data, $row_number, $pdo);
                if (!empty($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                } else {
                    $data[] = $result['data'];
                }
            }
        }
        
        // Jika tidak ada error, simpan ke database
        if (empty($errors) && !empty($data)) {
            $saved_count = save_transactions_to_database($data, $pdo);
        }
        
    } catch (Exception $e) {
        $errors[] = 'Error memproses file: ' . $e->getMessage();
    }
    
    if (!empty($errors)) {
        return ['errors' => $errors];
    } else {
        return [
            'saved_count' => $saved_count,
            'data' => $data
        ];
    }
}

function validate_and_process_row($row_data, $row_number, $pdo) {
    $errors = [];
    $data = [];
    
    // Clean data
    $row_data = array_map(function($value) {
        return is_string($value) ? trim($value) : $value;
    }, $row_data);
    
    // Validasi jumlah kolom (sesuai format client)
    if (count($row_data) < 8) {
        $errors[] = "Baris $row_number: Data tidak lengkap (harus 8 kolom)";
        return ['errors' => $errors];
    }
    
    // 1. Tanggal SPBU (kolom 1)
    $tanggal_spbu = $row_data[0];
    if (empty($tanggal_spbu)) {
        $errors[] = "Baris $row_number: Tanggal SPBU tidak boleh kosong";
    } else {
        // Konversi format "Sunday, August 10, 2025" ke "2025-08-10"
        $date_obj = DateTime::createFromFormat('l, F j, Y', $tanggal_spbu);
        if ($date_obj) {
            $data['tanggal'] = $date_obj->format('Y-m-d');
        } else {
            $errors[] = "Baris $row_number: Format tanggal salah (contoh: Sunday, August 10, 2025)";
        }
    }
    
    // 2. Shift SPBU (kolom 2)
    $shift_spbu = $row_data[1];
    if (empty($shift_spbu)) {
        $errors[] = "Baris $row_number: Shift SPBU tidak boleh kosong";
    } else {
        $valid_shifts = ['1', '2', '3'];
        if (!in_array($shift_spbu, $valid_shifts)) {
            $errors[] = "Baris $row_number: Shift '$shift_spbu' tidak valid (harus 1, 2, atau 3)";
        } else {
            $shift_map = ['1' => 'Pagi', '2' => 'Siang', '3' => 'Malam'];
            $data['shift'] = $shift_map[$shift_spbu];
        }
    }
    
    // 3. Jumlah Liter (kolom 5)
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
    
    // 4. Harga per Liter (kolom 6)
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
    
    // Hitung total amount
    if (isset($data['jumlah_liter']) && isset($data['harga_per_liter'])) {
        $data['amount'] = $data['jumlah_liter'] * $data['harga_per_liter'];
    }
    
    // Set default values
    $data['tid'] = 'D2DF8372';
    $data['jenis_bbm'] = 'Pertalite';
    
    // Set tipe pembayaran default (BCA)
    $stmt = $pdo->prepare("SELECT id FROM tipe_transaksi WHERE nama = 'BCA'");
    $stmt->execute();
    $tipe_result = $stmt->fetch();
    if ($tipe_result) {
        $data['tipe_id'] = $tipe_result['id'];
        $data['tipe_pembayaran'] = 'BCA';
    } else {
        $errors[] = "Baris $row_number: Tipe pembayaran 'BCA' tidak ditemukan di database";
    }
    
    return ['data' => $data, 'errors' => $errors];
}

function save_transactions_to_database($data_array, $pdo) {
    $saved_count = 0;
    $mid = "0000855001598321";
    
    foreach ($data_array as $data) {
        try {
            $stmt = $pdo->prepare("INSERT INTO transaksi (tanggal, mid, tid, tipe_id, jenis_bbm, amount, shift, admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['tanggal'], 
                $mid, 
                $data['tid'], 
                $data['tipe_id'], 
                $data['jenis_bbm'], 
                $data['amount'], 
                $data['shift'], 
                $_SESSION['admin_id']
            ]);
            $saved_count++;
        } catch (Exception $e) {
            // Log error but continue
            error_log("Error saving transaction: " . $e->getMessage());
        }
    }
    
    // Log aktivitas
    if ($saved_count > 0) {
        $pdo->prepare("INSERT INTO aktivitas (aktivitas, admin_id) VALUES (?, ?)")
            ->execute(['Import otomatis ' . $saved_count . ' transaksi dari file', $_SESSION['admin_id']]);
    }
    
    return $saved_count;
}

// Ambil data untuk template
$tipe_transaksi = $pdo->query("SELECT * FROM tipe_transaksi")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Import Data Penjualan Otomatis</title>
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
                    <span class="font-semibold text-pertamina-blue">Import Data Penjualan Otomatis</span>
                </div>
            </nav>
            
            <main class="flex-1 p-4 md:p-8 overflow-auto">
                <div class="w-full max-w-7xl mx-auto">
                    
                    <!-- Success Message -->
                    <?php if ($success_message): ?>
                    <div id="notif-toast" class="fixed top-6 left-1/2 -translate-x-1/2 z-50 px-6 py-3 rounded-xl shadow-lg text-white text-center text-base font-semibold animate-fade-in"
                         style="background: linear-gradient(90deg,#43b02a,#0099da);min-width:220px;">
                        <?= $success_message ?>
                    </div>
                    <style>@keyframes fade-in{from{opacity:0;transform:translateY(-20px);}to{opacity:1;transform:translateY(0);}}</style>
                    <script>setTimeout(()=>{const n=document.getElementById('notif-toast');if(n)n.style.display='none';},3000);</script>
                    <?php endif; ?>
                    
                    <!-- Error Messages -->
                    <?php if ($errors): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 sm:p-6">
                        <div class="flex items-center gap-2 sm:gap-3 mb-4">
                            <div class="p-2 bg-red-500 rounded-lg">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-base sm:text-lg font-semibold text-red-800">Error Import File:</h3>
                        </div>
                        <div class="bg-white rounded-lg p-4 max-h-60 overflow-y-auto">
                            <ul class="list-disc list-inside space-y-1 sm:space-y-2 text-red-700">
                                <?php foreach ($errors as $e) echo "<li class='text-xs sm:text-sm'>$e</li>"; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Import Result -->
                    <?php if ($import_result && isset($import_result['saved_count'])): ?>
                    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 sm:p-6">
                        <div class="flex items-center gap-2 sm:gap-3 mb-4">
                            <div class="p-2 bg-green-500 rounded-lg">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h3 class="text-base sm:text-lg font-semibold text-green-800">Import Berhasil!</h3>
                        </div>
                        <div class="bg-white rounded-lg p-4">
                            <p class="text-green-700 font-semibold">✅ Berhasil import <?= $import_result['saved_count'] ?> transaksi ke database</p>
                            <p class="text-sm text-gray-600 mt-2">Data penjualan sudah tersimpan dan dapat dilihat di dashboard</p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Main Content -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <!-- Upload Section -->
                        <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                            <div class="flex items-center gap-2 sm:gap-3 mb-6">
                                <div class="p-2 bg-pertamina-green rounded-lg">
                                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                </div>
                                <h2 class="text-lg sm:text-xl font-semibold text-pertamina-blue">Upload File Penjualan</h2>
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
                                
                                <button type="submit" class="w-full bg-pertamina-blue hover:bg-pertamina-green text-white font-semibold py-3 sm:py-4 rounded-lg shadow-lg transition-all duration-200 text-base sm:text-lg hover-lift">
                                    🚀 Import Data Penjualan
                                </button>
                            </form>
                        </div>
                        
                        <!-- Format & Info Section -->
                        <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                            <div class="flex items-center gap-2 sm:gap-3 mb-6">
                                <div class="p-2 bg-blue-500 rounded-lg">
                                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h2 class="text-lg sm:text-xl font-semibold text-pertamina-blue">Format File & Panduan</h2>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <h3 class="font-semibold text-blue-800 mb-2">📋 Format File yang Diperlukan:</h3>
                                    <div class="text-sm text-blue-700 space-y-1">
                                        <p><strong>Kolom 1:</strong> Tanggal SPBU (format: Sunday, August 10, 2025)</p>
                                        <p><strong>Kolom 2:</strong> Shift SPBU (1=Pagi, 2=Siang, 3=Malam)</p>
                                        <p><strong>Kolom 3:</strong> Tanggal Settle (tidak digunakan)</p>
                                        <p><strong>Kolom 4:</strong> Waktu (tidak digunakan)</p>
                                        <p><strong>Kolom 5:</strong> Jumlah Liter (contoh: 1.5 L)</p>
                                        <p><strong>Kolom 6:</strong> Harga per Liter (contoh: 13,000)</p>
                                        <p><strong>Kolom 7:</strong> BCA (tidak digunakan)</p>
                                        <p><strong>Kolom 8:</strong> PERTALITE (tidak digunakan)</p>
                                    </div>
                                </div>
                                
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <h3 class="font-semibold text-green-800 mb-2">✅ Keuntungan Sistem Ini:</h3>
                                    <div class="text-sm text-green-700 space-y-1">
                                        <p>• <strong>Hemat Waktu:</strong> Tidak perlu input manual satu per satu</p>
                                        <p>• <strong>Minimal Error:</strong> Data langsung dari file, tidak ada typo</p>
                                        <p>• <strong>Real-time:</strong> Data langsung tersimpan dan dapat dilihat</p>
                                        <p>• <strong>Batch Import:</strong> Bisa import ratusan transaksi sekaligus</p>
                                    </div>
                                </div>
                                
                                <div class="bg-yellow-50 p-4 rounded-lg">
                                    <h3 class="font-semibold text-yellow-800 mb-2">⚠️ Catatan Penting:</h3>
                                    <div class="text-sm text-yellow-700 space-y-1">
                                        <p>• File harus memiliki header di baris pertama</p>
                                        <p>• Format tanggal harus sesuai (Sunday, August 10, 2025)</p>
                                        <p>• Shift harus berupa angka 1, 2, atau 3</p>
                                        <p>• Jumlah liter dan harga harus berupa angka</p>
                                        <p>• Sistem akan otomatis set TID, Jenis BBM, dan Tipe Pembayaran</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="mt-6 bg-white rounded-lg shadow-md p-4 sm:p-6">
                        <h3 class="text-lg font-semibold text-pertamina-blue mb-4">🚀 Quick Actions</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <a href="rekap-transaksi.php" class="p-4 bg-blue-50 rounded-lg border border-blue-200 hover:bg-blue-100 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-blue-500 rounded-lg">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-blue-800">Lihat Rekap</h4>
                                        <p class="text-sm text-blue-600">Dashboard data penjualan</p>
                                    </div>
                                </div>
                            </a>
                            
                            <a href="transaksi-filter.php" class="p-4 bg-green-50 rounded-lg border border-green-200 hover:bg-green-100 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-green-500 rounded-lg">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-green-800">Filter Data</h4>
                                        <p class="text-sm text-green-600">Cari berdasarkan kriteria</p>
                                    </div>
                                </div>
                            </a>
                            
                            <a href="panduan-import.php" class="p-4 bg-purple-50 rounded-lg border border-purple-200 hover:bg-purple-100 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-purple-500 rounded-lg">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-purple-800">Panduan Lengkap</h4>
                                        <p class="text-sm text-purple-600">Cara penggunaan sistem</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
