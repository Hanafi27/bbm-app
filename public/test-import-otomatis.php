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
$analysis_data = [];
$show_analysis = false;

// Handle file upload untuk analisis otomatis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    echo "<h2>📁 File Upload Detected</h2>";
    echo "<p>File: " . $file['name'] . "</p>";
    echo "<p>Type: " . $file['type'] . "</p>";
    echo "<p>Size: " . $file['size'] . " bytes</p>";
    echo "<p>Error: " . $file['error'] . "</p>";
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Error upload file: ' . $file['error'];
    } elseif ($file['size'] > 10 * 1024 * 1024) {
        $errors[] = 'File terlalu besar (maksimal 10MB)';
    } else {
        $allowed_types = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = 'Tipe file tidak didukung. Gunakan file CSV atau Excel. Tipe file Anda: ' . $file['type'];
        } else {
            echo "<p>✅ File valid, mulai analisis...</p>";
            
            $analysis_result = analyze_sales_file($file, $pdo);
            echo "<h3>Analysis Result:</h3>";
            echo "<pre>" . print_r($analysis_result, true) . "</pre>";
            
            if (isset($analysis_result['errors'])) {
                $errors = array_merge($errors, $analysis_result['errors']);
            } else {
                $analysis_data = $analysis_result;
                $show_analysis = true;
                echo "<p>✅ Analysis completed successfully!</p>";
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
                    
                    // Count jenis BBM
                    $jenis_bbm = $result['data']['jenis_bbm'];
                    if (!isset($rekap_per_shift[$key]['jenis_bbm_count'][$jenis_bbm])) {
                        $rekap_per_shift[$key]['jenis_bbm_count'][$jenis_bbm] = 0;
                    }
                    $rekap_per_shift[$key]['jenis_bbm_count'][$jenis_bbm]++;
                    
                    // Count tipe pembayaran
                    $tipe_pembayaran = $result['data']['tipe_pembayaran'];
                    if (!isset($rekap_per_shift[$key]['tipe_pembayaran_count'][$tipe_pembayaran])) {
                        $rekap_per_shift[$key]['tipe_pembayaran_count'][$tipe_pembayaran] = 0;
                    }
                    $rekap_per_shift[$key]['tipe_pembayaran_count'][$tipe_pembayaran]++;
                }
                $row++;
            }
            fclose($handle);
        } else {
            $errors[] = 'Tidak bisa membuka file CSV';
        }
    } else {
        try {
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Skip header row
            array_shift($rows);
            
            foreach ($rows as $index => $row_data) {
                $row_number = $index + 2;
                
                // Filter baris kosong
                if (empty(array_filter($row_data))) {
                    continue;
                }
                
                $result = validate_import_row($row_data, $row_number, $pdo);
                if (!empty($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                } else {
                    $data[] = $result['data'];
                    
                    // Buat rekap per shift (sama seperti CSV)
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
                    
                    // Count jenis BBM
                    $jenis_bbm = $result['data']['jenis_bbm'];
                    if (!isset($rekap_per_shift[$key]['jenis_bbm_count'][$jenis_bbm])) {
                        $rekap_per_shift[$key]['jenis_bbm_count'][$jenis_bbm] = 0;
                    }
                    $rekap_per_shift[$key]['jenis_bbm_count'][$jenis_bbm]++;
                    
                    // Count tipe pembayaran
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
    
    if (empty($errors)) {
        return [
            'data' => $data,
            'rekap_per_shift' => array_values($rekap_per_shift)
        ];
    } else {
        return ['errors' => $errors];
    }
}

function validate_import_row($row_data, $row_number, $pdo) {
    $errors = [];
    $data = [];
    
    // Filter data kosong
    $row_data = array_map(function($value) {
        return is_string($value) ? trim($value) : $value;
    }, $row_data);
    
    if (count($row_data) < 8) {
        $errors[] = "Baris $row_number: Data tidak lengkap (harus 8 kolom)";
        return ['errors' => $errors];
    }
    
    // Validasi tanggal SPBU
    $tanggal_spbu = $row_data[0];
    if (empty($tanggal_spbu)) {
        $errors[] = "Baris $row_number: Tanggal SPBU tidak boleh kosong";
    } else {
        // Konversi format tanggal dari "Sunday, August 10, 2025" ke "2025-08-10"
        $date_obj = DateTime::createFromFormat('l, F j, Y', $tanggal_spbu);
        if ($date_obj) {
            $data['tanggal'] = $date_obj->format('Y-m-d');
        } else {
            $errors[] = "Baris $row_number: Format tanggal SPBU salah (contoh: Sunday, August 10, 2025)";
        }
    }
    
    // Validasi Shift SPBU
    $shift_spbu = $row_data[1];
    if (empty($shift_spbu)) {
        $errors[] = "Baris $row_number: Shift SPBU tidak boleh kosong";
    } else {
        $valid_shifts = ['1', '2', '3'];
        if (!in_array($shift_spbu, $valid_shifts)) {
            $errors[] = "Baris $row_number: Shift SPBU '$shift_spbu' tidak valid (harus 1, 2, atau 3)";
        } else {
            // Konversi shift ke format yang sesuai
            $shift_map = ['1' => 'Pagi', '2' => 'Siang', '3' => 'Malam'];
            $data['shift'] = $shift_map[$shift_spbu];
        }
    }
    
    // Skip Tanggal Settle (kolom 3) - tidak digunakan untuk import
    $tanggal_settle = $row_data[2];
    
    // Skip Waktu (kolom 4) - tidak digunakan untuk import
    $waktu = $row_data[3];
    
    // Validasi Jumlah Liter
    $jumlah_liter = $row_data[4];
    if (empty($jumlah_liter)) {
        $errors[] = "Baris $row_number: Jumlah Liter tidak boleh kosong";
    } else {
        // Hapus suffix "L" dan konversi ke angka
        $liter_clean = str_replace(' L', '', $jumlah_liter);
        if (!is_numeric($liter_clean) || $liter_clean <= 0) {
            $errors[] = "Baris $row_number: Jumlah Liter '$jumlah_liter' tidak valid";
        } else {
            $data['jumlah_liter'] = $liter_clean;
        }
    }
    
    // Validasi Harga per Liter
    $harga = $row_data[5];
    if (empty($harga)) {
        $errors[] = "Baris $row_number: Harga tidak boleh kosong";
    } else {
        // Hapus separator dan konversi ke angka
        $harga_clean = str_replace(['.', ','], '', $harga);
        if (!is_numeric($harga_clean) || $harga_clean <= 0) {
            $errors[] = "Baris $row_number: Harga '$harga' tidak valid";
        } else {
            $data['harga_per_liter'] = $harga_clean;
        }
    }
    
    // Hitung total amount berdasarkan jumlah liter dan harga
    if (isset($data['jumlah_liter']) && isset($data['harga_per_liter'])) {
        $data['amount'] = $data['jumlah_liter'] * $data['harga_per_liter'];
    }
    
    // Set default values untuk kolom yang tidak ada di format client
    $data['tid'] = 'D2DF8372'; // Default TID
    $data['jenis_bbm'] = 'Pertalite'; // Default jenis BBM
    
    // Set tipe pembayaran default
    $stmt = $pdo->prepare("SELECT id FROM tipe_transaksi WHERE nama = 'BCA'");
    $stmt->execute();
    $tipe_result = $stmt->fetch();
    if ($tipe_result) {
        $data['tipe_id'] = $tipe_result['id'];
        $data['tipe_pembayaran'] = 'BCA';
    } else {
        $errors[] = "Baris $row_number: Tipe pembayaran default 'BCA' tidak ditemukan di database";
    }
    
    return ['data' => $data, 'errors' => $errors];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Import Otomatis</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-container { border: 2px dashed #ccc; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Import Otomatis</h1>
        
        <div class="form-container">
            <form method="post" enctype="multipart/form-data">
                <h3>Upload File untuk Test</h3>
                <input type="file" name="file" accept=".csv,.xlsx,.xls" required>
                <br><br>
                <button type="submit">Upload & Test</button>
            </form>
        </div>
        
        <?php if ($show_analysis && !empty($analysis_data)): ?>
        <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h3>✅ Analysis Berhasil!</h3>
            <p>Total data: <?= count($analysis_data['data']) ?></p>
            <p>Total shifts: <?= count($analysis_data['rekap_per_shift']) ?></p>
            <h4>Rekap per Shift:</h4>
            <pre><?= print_r($analysis_data['rekap_per_shift'], true) ?></pre>
        </div>
        <?php endif; ?>
        
        <?php if ($errors): ?>
        <div style="background: #ffe8e8; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h3>❌ Errors Found:</h3>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
