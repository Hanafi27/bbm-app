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
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] ?? 'admin') === 'kepala_spbu') {
    header('Location: rekap-transaksi.php');
    exit;
}

$mid = "0000855001598321";
$errors = [];
$success_message = '';
$preview_data = [];
$show_preview = false;

// Definisi field yang diperlukan untuk import
$required_fields = [
    'mid' => 'MID',
    'tid' => 'TID',
    'jenis_bbm' => 'Jenis BBM',
    'tipe_transaksi' => 'Tipe Transaksi',
    'jumlah_liter' => 'Jumlah Liter',
    'harga_per_liter' => 'Harga Per Liter',
    'total_amount' => 'Total Amount',
    'tanggal_transaksi' => 'Tanggal Transaksi',
    'shift' => 'Shift'
];

// Handle file download template
if (isset($_GET['download_template'])) {
    try {
        // Debug log
        error_log("Starting template generation...");
        
        // Clear any existing output
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Check if PhpSpreadsheet is available
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            throw new Exception('PhpSpreadsheet library not found. Please check if it is properly installed.');
        }
        
        // Buat spreadsheet baru
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set judul template
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT TRANSAKSI BBM');
        $sheet->mergeCells('A1:I1');
        
        // Set header kolom
        $headers = array_values($required_fields);
        foreach ($headers as $index => $header) {
            $col = chr(65 + $index); // A, B, C, dst
            $sheet->setCellValue($col . '3', $header);
        }
        
        // Tambahkan contoh data
        $exampleData = [
            ['855001598321', 'D2DF8372', 'Pertalite', 'BCA', '15.5', '13000', '201500', '15/01/25', 'Pagi'],
            ['855001598321', 'C2DC9354', 'Pertamax', 'BNI', '20.0', '14000', '280000', '15/01/25', 'Siang'],
            ['855001598321', 'A2DC9354', 'Solar', 'BRI', '25.0', '12000', '300000', '15/01/25', 'Malam']
        ];
        
        foreach ($exampleData as $rowIndex => $data) {
            $row = $rowIndex + 4;
            foreach ($data as $colIndex => $value) {
                $col = chr(65 + $colIndex);
                $sheet->setCellValue($col . $row, $value);
            }
        }
        
                 // Auto size columns
        foreach (range('A', 'I') as $col) {
             $sheet->getColumnDimension($col)->setAutoSize(true);
         }
        
        // Set active sheet kembali ke sheet pertama
        $spreadsheet->setActiveSheetIndex(0);
        
        error_log("Template content created successfully");
        
        // Check format
        $format = $_GET['format'] ?? 'xlsx';
        
        if ($format === 'csv') {
            // Set headers untuk CSV
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="template_import_transaksi.csv"');
            header('Cache-Control: max-age=0');
            
            // Create CSV content
            $output = fopen('php://output', 'w');
            
            // Write headers
            $headers = array_values($required_fields);
            fputcsv($output, $headers);
            
            // Write example data
            $exampleData = [
                ['855001598321', 'D2DF8372', 'Pertalite', 'BCA', '15.5', '13000', '201500', '15/01/25', 'Pagi'],
                ['855001598321', 'C2DC9354', 'Pertamax', 'BNI', '20.0', '14000', '280000', '15/01/25', 'Siang'],
                ['855001598321', 'A2DC9354', 'Solar', 'BRI', '25.0', '12000', '300000', '15/01/25', 'Malam']
            ];
            
            foreach ($exampleData as $data) {
                fputcsv($output, $data);
            }
            
            fclose($output);
            error_log("CSV template generated successfully");
            exit;
        } else {
            // Set headers untuk Excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="template_import_transaksi.xlsx"');
        header('Cache-Control: max-age=0');
            header('Cache-Control: max-age=1');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: cache, must-revalidate');
            header('Pragma: public');
        
            error_log("Headers set, creating writer...");
            
            // Create writer dan save
            $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
            
            error_log("Excel template saved successfully");
        exit;
        }
        
    } catch (Exception $e) {
        error_log("Error generating Excel template: " . $e->getMessage());
        
        // Fallback: Generate CSV template
        try {
            error_log("Trying CSV fallback...");
            
            // Clear any existing output
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Set headers untuk CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="template_import_transaksi.csv"');
            header('Cache-Control: max-age=0');
        
            // Create CSV content
        $output = fopen('php://output', 'w');
            
            // Write headers
            $headers = array_values($required_fields);
            fputcsv($output, $headers);
            
            // Write example data
            $exampleData = [
                ['855001598321', 'D2DF8372', 'Pertalite', 'BCA', '15.5', '13000', '201500', '15/01/25', 'Pagi'],
                ['855001598321', 'C2DC9354', 'Pertamax', 'BNI', '20.0', '14000', '280000', '15/01/25', 'Siang'],
                ['855001598321', 'A2DC9354', 'Solar', 'BRI', '25.0', '12000', '300000', '15/01/25', 'Malam']
            ];
            
            foreach ($exampleData as $data) {
                fputcsv($output, $data);
            }
            
        fclose($output);
            error_log("CSV template generated successfully");
            exit;
            
        } catch (Exception $csvError) {
            error_log("Error generating CSV template: " . $csvError->getMessage());
            header('HTTP/1.1 500 Internal Server Error');
            echo "Error generating template. Please check server logs for details.";
        exit;
        }
    }
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    

    
    // Validasi file
    if ($file['error'] !== UPLOAD_ERR_OK) {
         $errors[] = '❌ Error upload file: ' . $file['error'] . '. Silakan coba lagi atau hubungi administrator.';
     } elseif ($file['size'] === 0) {
         $errors[] = '❌ File kosong. Pilih file yang berisi data.';
    } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
         $errors[] = '❌ File terlalu besar (maksimal 10MB). Silakan kompres file atau gunakan file yang lebih kecil.';
    } else {
        // Dukung semua jenis file Excel
        $allowed_types = [
            'text/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/octet-stream', // Untuk file .xls yang tidak terdeteksi dengan benar
            'application/vnd.ms-office' // Untuk file Excel lama
        ];
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $excel_extensions = ['csv'];
        
        if (!in_array($file_extension, $excel_extensions)) {
            $errors[] = '❌ Format file tidak didukung! Gunakan file CSV. Format file Anda: ' . $file_extension . '. Silakan download template yang benar.';
        } else {
            // Proses file untuk preview
            $uploaded_data = process_import_file($file, $pdo, true); // hanya CSV
            
            if (isset($uploaded_data['errors']) && !empty($uploaded_data['errors'])) {
                $errors = array_merge($errors, $uploaded_data['errors']);
            } else {
                $preview_data = $uploaded_data['data'] ?? [];
                $show_preview = true;
            }
        }
    }
}

// Handle confirm import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_import'])) {
    $preview_data_json = $_POST['preview_data'] ?? '';
    if (!empty($preview_data_json)) {
        $preview_data_array = json_decode($preview_data_json, true);
        if ($preview_data_array) {
            $resultImport = save_import_data($preview_data_array, $pdo);
            $saved_count = is_array($resultImport) ? ($resultImport['saved_count'] ?? 0) : (int)$resultImport;
            $inserted_ids = is_array($resultImport) ? ($resultImport['inserted_ids'] ?? []) : [];
            if ($saved_count > 0) {
                // Catat aktivitas
                $pdo->prepare("INSERT INTO aktivitas (aktivitas, admin_id) VALUES (?, ?)")
                    ->execute(['Import ' . $saved_count . ' transaksi dari file Excel ke arsip dengan format lengkap (MID, TID, Jenis BBM, Tipe Transaksi, Jumlah Liter, Harga Per Liter, Total Amount, Tanggal Transaksi dd/mm/yy, Shift)', $_SESSION['admin_id']]);
                
                // Debug: Log session data sebelum redirect
                error_log("=== IMPORT SUCCESS ===");
                error_log("Saved count: " . $saved_count);
                error_log("Preview data count: " . count($preview_data_array));
                error_log("Session admin_id: " . ($_SESSION['admin_id'] ?? 'not set'));
                
                // Simpan data untuk ditampilkan di halaman detail
                $_SESSION['import_detail_data'] = $preview_data_array;
                $_SESSION['import_detail_ids'] = $inserted_ids;
                $_SESSION['import_success'] = "✅ Berhasil import " . $saved_count . " transaksi ke arsip dari file Excel";
                
                // Debug: Log session data setelah set
                error_log("Session import_detail_data count: " . count($_SESSION['import_detail_data']));
                error_log("Session import_success: " . $_SESSION['import_success']);
                error_log("Session data structure: " . print_r($_SESSION, true));
                
                // Pastikan session tersimpan sebelum redirect
                session_write_close();
                
                // Redirect ke halaman detail transaksi import
                header('Location: transaksi-import-detail.php');
                exit;
            }
        }
    }
}

// Ambil tipe transaksi untuk validasi
$tipe = $pdo->query("SELECT * FROM tipe_transaksi")->fetchAll();

function process_import_file($file, $pdo, $preview_mode = false) {
    $data = [];
    $errors = [];
    
    try {
        // Deteksi tipe file berdasarkan ekstensi
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($file_extension === 'csv') {
        // Proses CSV
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle) {
            $row = 1;
                $headers = null;
                
            while (($row_data = fgetcsv($handle)) !== false) {
                if ($row === 1) {
                        // Validasi header
                        $headers = array_map('trim', $row_data);
                        $header_validation = validate_headers($headers);
                        if (!empty($header_validation['errors'])) {
                            $errors = array_merge($errors, $header_validation['errors']);
                            break;
                        }
                    $row++;
                    continue;
                }
                
                    $result = validate_import_row_new_format($row_data, $row, $pdo, $headers);
                if (!empty($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                } else {
                    $data[] = $result['data'];
                }
                $row++;
            }
            fclose($handle);
            }
        }
        
        } catch (Exception $e) {
        $errors[] = '❌ Error membaca file: ' . $e->getMessage() . '. Pastikan file tidak rusak dan format sesuai.';
        error_log("File processing error: " . $e->getMessage());
    }
    
    return ['data' => $data, 'errors' => $errors];
}

function validate_headers($headers) {
    global $required_fields;
    $errors = [];
    
    // Cek apakah semua field yang diperlukan ada
    foreach ($required_fields as $field => $label) {
        if (!in_array($label, $headers)) {
            $errors[] = "❌ Header '$label' tidak ditemukan dalam file. Header yang ada: " . implode(', ', $headers) . ". Silakan download template yang benar.";
        }
    }
    
    return ['errors' => $errors];
}

function validate_import_row_new_format($row_data, $row_number, $pdo, $headers) {
    global $required_fields, $tid_list;
    $errors = [];
    $data = [];
    
    // Filter data kosong
    $row_data = array_map(function($value) {
        return is_string($value) ? trim($value) : $value;
    }, $row_data);
    
    // Map data berdasarkan header
    $field_mapping = [];
    foreach ($required_fields as $field => $label) {
        $index = array_search($label, $headers);
        if ($index !== false && isset($row_data[$index])) {
            $field_mapping[$field] = $row_data[$index];
        } else {
            $field_mapping[$field] = '';
        }
    }
    
    // Validasi MID
    $mid = $field_mapping['mid'];
    if (empty($mid)) {
        $errors[] = "❌ Baris $row_number: MID tidak boleh kosong";
     } else {
        // Handle scientific notation dari Excel (contoh: 8.55002E+15)
        if (is_numeric($mid) && strpos($mid, 'E') !== false) {
            $mid = number_format($mid, 0, '', ''); // Konversi ke format normal
        }
        
        // Hapus semua karakter non-digit
        $mid_clean = preg_replace('/[^0-9]/', '', $mid);
        
        // Validasi format MID (12 digit)
        if (strlen($mid_clean) === 12) {
            $data['mid'] = $mid_clean;
         } else {
            $errors[] = "❌ Baris $row_number: MID '$mid' tidak valid (harus 12 digit angka). Setelah pembersihan: '$mid_clean' (panjang: " . strlen($mid_clean) . ")";
        }
    }
    
    // Validasi TID
    $tid = $field_mapping['tid'];
    if (empty($tid)) {
        $errors[] = "❌ Baris $row_number: TID tidak boleh kosong";
     } else {
        if (in_array($tid, $tid_list)) {
            $data['tid'] = $tid;
         } else {
            $errors[] = "❌ Baris $row_number: TID '$tid' tidak valid. TID yang tersedia: " . implode(', ', $tid_list);
        }
    }
    
    // Validasi Jenis BBM
    $jenis_bbm = $field_mapping['jenis_bbm'];
    if (empty($jenis_bbm)) {
        $errors[] = "❌ Baris $row_number: Jenis BBM tidak boleh kosong";
    } else {
        $valid_bbm = ['Pertalite', 'Pertamax', 'Solar', 'Dexlite'];
        if (in_array($jenis_bbm, $valid_bbm)) {
            $data['jenis_bbm'] = $jenis_bbm;
        } else {
            $errors[] = "❌ Baris $row_number: Jenis BBM '$jenis_bbm' tidak valid. Pilihan: " . implode(', ', $valid_bbm);
        }
    }
    
    // Validasi Tipe Transaksi
    $tipe_transaksi = $field_mapping['tipe_transaksi'];
    if (empty($tipe_transaksi)) {
        $errors[] = "❌ Baris $row_number: Tipe Transaksi tidak boleh kosong";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM tipe_transaksi WHERE nama = ?");
        $stmt->execute([$tipe_transaksi]);
        $tipe_result = $stmt->fetch();
        if ($tipe_result) {
            $data['tipe_id'] = $tipe_result['id'];
            $data['tipe_transaksi'] = $tipe_transaksi;
        } else {
            $errors[] = "❌ Baris $row_number: Tipe transaksi '$tipe_transaksi' tidak ditemukan di database. Tipe yang tersedia: " . implode(', ', array_column($GLOBALS['tipe'], 'nama'));
        }
    }
     
     // Validasi Jumlah Liter
    $jumlah_liter = $field_mapping['jumlah_liter'];
     if (empty($jumlah_liter)) {
        $errors[] = "❌ Baris $row_number: Jumlah Liter tidak boleh kosong";
     } else {
        if (is_numeric($jumlah_liter) && $jumlah_liter > 0) {
            $data['jumlah_liter'] = $jumlah_liter;
         } else {
            $errors[] = "❌ Baris $row_number: Jumlah Liter '$jumlah_liter' tidak valid (harus angka positif)";
        }
    }
    
    // Validasi Harga Per Liter
    $harga_per_liter = $field_mapping['harga_per_liter'];
    if (empty($harga_per_liter)) {
        $errors[] = "❌ Baris $row_number: Harga Per Liter tidak boleh kosong";
     } else {
         // Hapus separator dan konversi ke angka
        $harga_clean = str_replace(['.', ','], '', $harga_per_liter);
        if (is_numeric($harga_clean) && $harga_clean > 0) {
             $data['harga_per_liter'] = $harga_clean;
        } else {
            $errors[] = "❌ Baris $row_number: Harga Per Liter '$harga_per_liter' tidak valid (harus angka positif)";
        }
    }
    
    // Validasi Total Amount
    $total_amount = $field_mapping['total_amount'];
    if (empty($total_amount)) {
        $errors[] = "❌ Baris $row_number: Total Amount tidak boleh kosong";
    } else {
        // Hapus separator dan konversi ke angka
        $total_clean = str_replace(['.', ','], '', $total_amount);
        if (is_numeric($total_clean) && $total_clean > 0) {
            $data['total_amount'] = $total_clean;
        } else {
            $errors[] = "❌ Baris $row_number: Total Amount '$total_amount' tidak valid (harus angka positif)";
        }
    }
    
    // Validasi Tanggal Transaksi
    $tanggal_transaksi = $field_mapping['tanggal_transaksi'];
    if (empty($tanggal_transaksi)) {
        $errors[] = "❌ Baris $row_number: Tanggal Transaksi tidak boleh kosong";
    } else {
        // Coba format dd/mm/yy terlebih dahulu
        $date_obj = DateTime::createFromFormat('d/m/y', $tanggal_transaksi);
        if ($date_obj && $date_obj->format('d/m/y') === $tanggal_transaksi) {
            // Konversi ke format database Y-m-d untuk penyimpanan
            $data['tanggal_transaksi'] = $date_obj->format('Y-m-d');
        } else {
            // Coba format dd/mm/yyyy sebagai fallback
            $date_obj = DateTime::createFromFormat('d/m/Y', $tanggal_transaksi);
            if ($date_obj && $date_obj->format('d/m/Y') === $tanggal_transaksi) {
                // Konversi ke format database Y-m-d untuk penyimpanan
                $data['tanggal_transaksi'] = $date_obj->format('Y-m-d');
            } else {
                $errors[] = "❌ Baris $row_number: Format tanggal transaksi salah (harus dd/mm/yy atau dd/mm/yyyy, contoh: 15/01/25 atau 15/01/2025). Nilai: '$tanggal_transaksi'";
            }
        }
    }
    
    // Validasi Shift
    $shift = $field_mapping['shift'];
    if (empty($shift)) {
        $errors[] = "❌ Baris $row_number: Shift tidak boleh kosong";
     } else {
        $valid_shifts = ['Pagi', 'Siang', 'Malam'];
        if (in_array($shift, $valid_shifts)) {
            $data['shift'] = $shift;
        } else {
            $errors[] = "❌ Baris $row_number: Shift '$shift' tidak valid. Pilihan: " . implode(', ', $valid_shifts);
        }
    }
    
    // Validasi konsistensi Total Amount dengan perhitungan
    if (isset($data['jumlah_liter']) && isset($data['harga_per_liter']) && isset($data['total_amount'])) {
        $calculated_amount = $data['jumlah_liter'] * $data['harga_per_liter'];
        if ($calculated_amount != $data['total_amount']) {
            $errors[] = "❌ Baris $row_number: Total Amount tidak konsisten. Dihitung: $calculated_amount, Input: " . $data['total_amount'];
        }
    }
    
    return ['data' => $data, 'errors' => $errors];
}

function save_import_data($data_array, $pdo) {
    $saved_count = 0;
    $inserted_ids = [];
    $errors = [];
    
    foreach ($data_array as $index => $data) {
        try {
            if (empty($data['mid']) || empty($data['tid']) || empty($data['tipe_id']) || 
                empty($data['jenis_bbm']) || empty($data['total_amount']) || empty($data['tanggal_transaksi']) || empty($data['shift'])) {
                $errors[] = "Data baris " . ($index + 1) . " tidak lengkap";
                continue;
            }
            
            $stmt = $pdo->prepare("INSERT INTO transaksi (tanggal, mid, tid, tipe_id, jenis_bbm, amount, shift, admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['tanggal_transaksi'], $data['mid'], $data['tid'], $data['tipe_id'], 
                $data['jenis_bbm'], $data['total_amount'], $data['shift'], $_SESSION['admin_id']
            ]);
            $saved_count++;
            $inserted_ids[] = (int)$pdo->lastInsertId();
        } catch (Exception $e) {
            $errors[] = "Error menyimpan baris " . ($index + 1) . ": " . $e->getMessage();
            error_log("Import error at row " . ($index + 1) . ": " . $e->getMessage());
        }
    }
    
    if (!empty($errors)) {
        error_log("Import errors: " . implode(", ", $errors));
    }
    
    return ['saved_count' => $saved_count, 'inserted_ids' => $inserted_ids];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Import Transaksi BBM</title>
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
      
      /* Custom scrollbar untuk error list */
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
                    <span class="font-semibold text-pertamina-blue">Import Transaksi</span>
                </div>
            </nav>
            
            <main class="flex-1 p-4 md:p-8 overflow-auto">
                <div class="w-full max-w-7xl mx-auto">
                    
                    <?php if ($success_message): ?>
                    <div id="notif-toast" class="fixed top-4 left-1/2 -translate-x-1/2 z-50 px-4 py-2 rounded-lg shadow-lg text-white text-center text-sm font-semibold animate-fade-in"
                        style="background: linear-gradient(90deg,#43b02a,#0099da);min-width:200px;">
                        <?= $success_message ?>
                    </div>
                    <style>@keyframes fade-in{from{opacity:0;transform:translateY(-20px);}to{opacity:1;transform:translateY(0);}}</style>
                    <script>setTimeout(()=>{const n=document.getElementById('notif-toast');if(n)n.style.display='none';},3000);</script>
                    <?php endif; ?>
                    
                    <?php if ($errors): ?>
                         <div class="mb-3 bg-red-50 border border-red-200 rounded-lg p-3">
                             <div class="flex items-center gap-2 mb-3">
                                <div class="p-2 bg-red-500 rounded-lg">
                                     <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                 <h3 class="text-base font-semibold text-red-800">❌ Error Import File CSV:</h3>
                            </div>
                             <div class="bg-white rounded-lg p-3 max-h-48 overflow-y-auto">
                                 <div class="mb-2 p-2 bg-yellow-50 border border-yellow-200 rounded-lg">
                                     <p class="text-yellow-800 text-xs"><strong>💡 Solusi:</strong> Download template yang benar dan pastikan format data sesuai.</p>
                                 </div>
                                 <ul class="list-disc list-inside space-y-1 text-red-700">
                                     <?php foreach ($errors as $e) echo "<li class='text-xs'>$e</li>"; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>

                    
                    <?php if ($show_preview && !empty($preview_data)): ?>
                     <!-- Detail Transaksi Comprehensive -->
                     <div class="bg-white rounded-lg shadow-md p-4 mb-4">
                        <?php 
                         $total_amount = array_sum(array_column($preview_data, 'total_amount'));
                         $total_liter = array_sum(array_column($preview_data, 'jumlah_liter'));
                         
                         // Group by date
                         $grouped_by_date = [];
                         foreach ($preview_data as $row) {
                             $date = $row['tanggal_transaksi'];
                             if (!isset($grouped_by_date[$date])) {
                                 $grouped_by_date[$date] = [];
                             }
                             $grouped_by_date[$date][] = $row;
                         }
                         
                         // Group by shift
                         $grouped_by_shift = [];
                         foreach ($preview_data as $row) {
                             $shift = $row['shift'];
                             if (!isset($grouped_by_shift[$shift])) {
                                 $grouped_by_shift[$shift] = [];
                             }
                             $grouped_by_shift[$shift][] = $row;
                         }
                         
                         // Group by BBM type
                         $grouped_by_bbm = [];
                         foreach ($preview_data as $row) {
                             $bbm = $row['jenis_bbm'];
                             if (!isset($grouped_by_bbm[$bbm])) {
                                 $grouped_by_bbm[$bbm] = [];
                             }
                             $grouped_by_bbm[$bbm][] = $row;
                         }
                         ?>
                         
                         <!-- Header Summary -->
                         <div class="border-b border-gray-200 pb-4 mb-4">
                             <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                                 <div>
                                     <h2 class="text-xl font-bold text-pertamina-blue mb-2">
                                         📊 Detail Transaksi Import
                            </h2>
                                     <p class="text-sm text-gray-600">
                                         File: <span class="font-mono"><?= htmlspecialchars($_FILES['file']['name'] ?? 'Unknown') ?></span>
                                     </p>
                            </div>
                                 <div class="flex flex-wrap gap-4">
                                     <div class="text-center">
                                         <div class="text-2xl font-bold text-pertamina-green"><?= count($preview_data) ?></div>
                                         <div class="text-xs text-gray-600">Total Transaksi</div>
                        </div>
                                     <div class="text-center">
                                         <div class="text-2xl font-bold text-pertamina-blue"><?= number_format($total_liter, 1) ?></div>
                                         <div class="text-xs text-gray-600">Total Liter</div>
                                     </div>
                                     <div class="text-center">
                                         <div class="text-2xl font-bold text-pertamina-red">Rp <?= number_format($total_amount) ?></div>
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
                                          <div class="text-lg font-bold text-purple-600"><?= count(array_unique(array_column($preview_data, 'tid'))) ?></div>
                                          <div class="text-xs text-gray-600">TID Unik</div>
                                      </div>
                                      <div class="text-center">
                                          <div class="text-lg font-bold text-indigo-600"><?= count(array_unique(array_column($preview_data, 'tipe_transaksi'))) ?></div>
                                          <div class="text-xs text-gray-600">Tipe Transaksi</div>
                                      </div>
                                      <div class="text-center">
                                          <div class="text-lg font-bold text-blue-600"><?= count(array_unique(array_column($preview_data, 'tanggal_transaksi'))) ?></div>
                                          <div class="text-xs text-gray-600">Hari Transaksi</div>
                                      </div>
                                      <div class="text-center">
                                          <div class="text-lg font-bold text-green-600"><?= number_format($total_amount / count($preview_data), 0) ?></div>
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
                         <div class="mb-4">
                             <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
                                 <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                 </svg>
                                 Detail Transaksi Lengkap
                             </h3>
                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full bg-white">
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
                                    <?php foreach ($preview_data as $index => $row): ?>
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
                                             <td class="px-3 py-3 text-sm font-mono text-gray-700"><?= number_format($row['jumlah_liter'], 1) ?> L</td>
                                             <td class="px-3 py-3 text-sm font-mono text-gray-700">Rp <?= number_format($row['harga_per_liter']) ?></td>
                                             <td class="px-3 py-3 text-sm font-mono font-semibold text-green-600">Rp <?= number_format($row['total_amount']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                         </div>
                         
                         <!-- Action Buttons -->
                         <form method="post" action="transaksi-import.php" class="mt-4">
                            <input type="hidden" name="preview_data" value="<?= htmlspecialchars(json_encode($preview_data)) ?>">
                             <div class="flex flex-col sm:flex-row gap-3 justify-center">
                                 <button type="submit" name="confirm_import" class="px-6 py-3 bg-gradient-to-r from-pertamina-green to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-200 font-semibold hover-lift shadow-lg text-sm flex items-center gap-2">
                                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                     </svg>
                                     ✅ Submit ke Arsip (<?= count($preview_data) ?> transaksi)
                                </button>
                                 <a href="transaksi-import.php" class="px-6 py-3 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-lg hover:from-gray-600 hover:to-gray-700 transition-all duration-200 font-semibold hover-lift shadow-lg text-center text-sm flex items-center gap-2">
                                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                     </svg>
                                     ❌ Batal Import
                                </a>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!$show_preview): ?>
                     <!-- Grid Layout untuk Download Template dan Upload File -->
                     <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mt-3">
                    <!-- Download Template -->
                         <div class="bg-white rounded-lg shadow-md p-3">
                             <div class="flex items-center gap-2 mb-3">
                            <div class="p-2 bg-pertamina-blue rounded-lg">
                                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                                 <h2 class="text-base font-semibold text-pertamina-blue">Download Template CSV</h2>
                        </div>
                                                           <p class="text-gray-600 mb-3 text-sm">Template dengan format: MID, TID, Jenis BBM, Tipe Transaksi, Jumlah Liter, Harga Per Liter, Total Amount, Tanggal Transaksi (dd/mm/yy atau dd/mm/yyyy), dan Shift.</p>
                             
                             <div class="flex flex-col gap-2">
                                <a href="?download_template=1&format=csv" class="flex items-center justify-center gap-2 px-4 py-2 bg-pertamina-blue text-white rounded-lg hover:bg-blue-600 transition-all duration-200 font-semibold hover-lift shadow-md text-sm">
                                     <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                     Download Template CSV
                            </a>
                        </div>
                    </div>
                    
                    <?php if (!$show_preview): ?>
                    <!-- Upload Form -->
                         <div class="bg-white rounded-lg shadow-md p-3">
                             <div class="flex items-center gap-2 mb-3">
                            <div class="p-2 bg-pertamina-green rounded-lg">
                                     <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                            </div>
                                 <h2 class="text-base font-semibold text-pertamina-blue">Upload File CSV</h2>
                        </div>
                        
                             <form method="post" action="transaksi-import.php" enctype="multipart/form-data" class="space-y-3" onsubmit="return validateForm()">
                                 <div class="border-2 border-dashed border-gray-300 rounded-lg p-3 text-center hover:border-pertamina-blue transition-colors">
                                     <div class="mb-3">
                                         <svg class="mx-auto h-8 w-8 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                     <div class="mb-3">
                                         <label class="block text-sm font-semibold text-gray-700 mb-2">Pilih File</label>
                                         <input type="file" name="file" id="fileInput" accept=".csv" 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pertamina-blue focus:border-transparent text-sm" required>
                                </div>
                                                                           <p class="text-sm text-gray-500">Format: CSV - maksimal 10MB</p>
                                      <p class="text-xs text-blue-600 mt-1">💡 Gunakan template yang disediakan di sebelah kiri</p>
                                     <div id="fileInfo" class="mt-2 text-sm text-gray-500"></div>
                            </div>
                            
                                                                   <button type="submit" class="w-full bg-pertamina-green hover:bg-green-600 text-white font-semibold py-2 rounded-lg shadow-lg transition-all duration-200 text-sm hover-lift">
                                      🔍 Preview & Submit Data CSV
                                  </button>
                             </form>
                                     </div>
                         <?php else: ?>
                         <!-- Placeholder untuk preview mode -->
                         <div class="bg-gray-50 rounded-lg border-2 border-dashed border-gray-300 p-6 flex items-center justify-center">
                             <div class="text-center text-gray-500">
                                 <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                 </svg>
                                 <p class="text-sm">Preview mode aktif</p>
                                     </div>
                                 </div>
                         <?php endif; ?>
                            </div>
                     <?php endif; ?>
                     
                     <!-- Info Button -->
                     <div class="mt-3 text-center">
                         <button onclick="openInfoModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-all duration-200 text-sm">
                             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                             </svg>
                             Lihat Informasi Format
                            </button>
                    </div>
                     
                                           <!-- Tips -->
                      <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mt-3">
                          <p class="text-yellow-800 text-sm"><strong>💡 Tips:</strong> Gunakan template yang disediakan untuk format data yang benar.</p>
                          <p class="text-yellow-700 text-xs mt-1"><strong>⚠️ Troubleshooting:</strong> Jika terdapat masalah pada file, pastikan simpan sebagai CSV UTF-8.</p>
                      </div>
                      
                      <!-- Lihat Arsip Button -->
                      <div class="mt-3 text-center">
                          <a href="arsip-list.php" class="inline-flex items-center gap-2 px-4 py-2 bg-pertamina-blue hover:bg-blue-600 text-white rounded-lg transition-all duration-200 text-sm">
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                              </svg>
                              Lihat Arsip Transaksi
                          </a>
                      </div>
                      
                      <!-- Detail Import Button -->
                      <div class="mt-3 text-center">
                          <a href="transaksi-import-detail.php" class="inline-flex items-center gap-2 px-4 py-2 bg-pertamina-green hover:bg-green-600 text-white rounded-lg transition-all duration-200 text-sm">
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 01-2 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                              </svg>
                              Detail Import Terakhir
                          </a>
                      </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Info Modal -->
    <div id="infoModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-lg font-semibold text-pertamina-blue">Informasi Format Import</h3>
                                         <div class="text-sm text-gray-600">
                         <p>Template tersedia dalam format CSV</p>
                         <p class="mt-1 text-xs text-green-600">Data akan disimpan ke tabel Arsip Transaksi</p>
                     </div>
                    <button onclick="closeInfoModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                 <!-- rest of info modal unchanged -->
                 
                 <!-- existing scripts below remain unchanged except validation -->
    <script>
         function openInfoModal() {
             const modal = document.getElementById('infoModal');
             if (!modal) return;
             modal.classList.remove('hidden');
             document.body.style.overflow = 'hidden';
         }

         function closeInfoModal() {
             const modal = document.getElementById('infoModal');
             if (!modal) return;
             modal.classList.add('hidden');
             document.body.style.overflow = 'auto';
         }

         document.addEventListener('DOMContentLoaded', function() {
             const modal = document.getElementById('infoModal');
             if (modal) {
                 modal.addEventListener('click', function(e) {
                     if (e.target === modal) {
                         closeInfoModal();
                     }
                 });
             }
             document.addEventListener('keydown', function(e) {
                 if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                     closeInfoModal();
                 }
             });
         });

         function validateForm() {
             const fileInput = document.getElementById('fileInput');
             const file = fileInput.files[0];
             if (!file) { showFormatError('Silakan pilih file terlebih dahulu!'); return false; }
             if (file.size === 0) { showFormatError('File kosong! Pilih file yang berisi data.'); return false; }
             if (file.size > 10 * 1024 * 1024) { showFormatError('File terlalu besar! Maksimal 10MB.'); return false; }
             const file_extension = file.name.toLowerCase().split('.').pop();
             if (file_extension !== 'csv') { showFormatError('Tipe file tidak didukung! Gunakan file CSV.'); return false; }
            const submitBtn = document.querySelector('button[type="submit"]');
             submitBtn.innerHTML = '⏳ Memproses File CSV...';
            submitBtn.disabled = true;
             showSuccessMessage('Memproses file CSV...');
            return true;
        }
        
         document.getElementById('fileInput')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const fileInfo = document.getElementById('fileInfo');
            if (file) {
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                 const file_extension = file.name.toLowerCase().split('.').pop();
                 fileInfo.innerHTML = `File: ${file.name} (${sizeMB} MB) - Format: ${file_extension.toUpperCase()}`;
                 if (file.size === 0) { fileInfo.innerHTML += ' <span class="text-red-500">⚠️ File kosong</span>'; showWarningMessage('File kosong. Pilih file yang berisi data.'); return; }
                 if (file.size > 10 * 1024 * 1024) { fileInfo.innerHTML += ' <span class="text-red-500">⚠️ File terlalu besar (max 10MB)</span>'; showWarningMessage('File terlalu besar. Maksimal 10MB.'); return; }
                 if (file_extension !== 'csv') { fileInfo.innerHTML += ' <span class="text-red-500">⚠️ Format file tidak didukung</span>'; showWarningMessage('Format file tidak didukung. Gunakan file CSV.'); return; }
                 fileInfo.innerHTML += ' <span class="text-green-500">✅ File valid</span>';
                 showSuccessMessage('File berhasil dipilih. Klik "Preview & Submit Data CSV" untuk melanjutkan.');
            } else {
                fileInfo.innerHTML = '';
            }
        });
    </script>

</body>
</html>
