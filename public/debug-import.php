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
$preview_data = [];
$show_preview = false;

// Debug: Tampilkan semua informasi
echo "<h1>🔍 Debug Import Transaksi</h1>";
echo "<p><strong>Session:</strong> " . print_r($_SESSION, true) . "</p>";
echo "<p><strong>Request Method:</strong> " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p><strong>POST Data:</strong> " . print_r($_POST, true) . "</p>";
echo "<p><strong>FILES Data:</strong> " . print_r($_FILES, true) . "</p>";

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    echo "<h2>📁 File Upload Detected</h2>";
    
    $file = $_FILES['file'];
    
    // Debug: Tampilkan informasi file
    echo "<h3>File Info:</h3>";
    echo "<ul>";
    echo "<li>Name: " . $file['name'] . "</li>";
    echo "<li>Type: " . $file['type'] . "</li>";
    echo "<li>Size: " . $file['size'] . " bytes</li>";
    echo "<li>Error: " . $file['error'] . "</li>";
    echo "<li>Tmp Name: " . $file['tmp_name'] . "</li>";
    echo "</ul>";
    
    // Validasi file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Error upload file: ' . $file['error'];
        echo "<p style='color: red;'>❌ Upload Error: " . $file['error'] . "</p>";
    } elseif ($file['size'] > 10 * 1024 * 1024) {
        $errors[] = 'File terlalu besar (maksimal 10MB)';
        echo "<p style='color: red;'>❌ File terlalu besar</p>";
    } else {
        $allowed_types = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = 'Tipe file tidak didukung. Gunakan file CSV atau Excel. Tipe file Anda: ' . $file['type'];
            echo "<p style='color: red;'>❌ Tipe file tidak didukung: " . $file['type'] . "</p>";
        } else {
            echo "<p style='color: green;'>✅ File valid, mulai proses...</p>";
            
            // Proses file untuk preview
            $uploaded_data = process_import_file($file, $pdo, true); // true untuk preview mode
            echo "<h3>Process Result:</h3>";
            echo "<pre>" . print_r($uploaded_data, true) . "</pre>";
            
            if (isset($uploaded_data['errors'])) {
                $errors = array_merge($errors, $uploaded_data['errors']);
                echo "<p style='color: red;'>❌ Validation errors found</p>";
            } else {
                $preview_data = $uploaded_data['data'];
                $show_preview = true;
                echo "<p style='color: green;'>✅ Preview data loaded: " . count($preview_data) . " rows</p>";
            }
        }
    }
}

function process_import_file($file, $pdo, $preview_mode = false) {
    echo "<h3>🔄 Processing File...</h3>";
    $data = [];
    $errors = [];
    
    if ($file['type'] === 'text/csv') {
        echo "<p>📄 Processing CSV file...</p>";
        // Proses CSV
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle) {
            $row = 1;
            while (($row_data = fgetcsv($handle)) !== false) {
                echo "<p>Row $row: " . implode(', ', $row_data) . "</p>";
                
                if ($row === 1) {
                    // Skip header
                    echo "<p>⏭️ Skipping header row</p>";
                    $row++;
                    continue;
                }
                
                $result = validate_import_row($row_data, $row, $pdo);
                if (!empty($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                    echo "<p style='color: red;'>❌ Row $row errors: " . implode(', ', $result['errors']) . "</p>";
                } else {
                    $data[] = $result['data'];
                    echo "<p style='color: green;'>✅ Row $row valid: " . print_r($result['data'], true) . "</p>";
                }
                $row++;
            }
            fclose($handle);
        } else {
            $errors[] = 'Tidak bisa membuka file CSV';
            echo "<p style='color: red;'>❌ Cannot open CSV file</p>";
        }
    } else {
        echo "<p>📊 Processing Excel file...</p>";
        // Proses Excel menggunakan PhpSpreadsheet
        try {
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            echo "<p>Total rows in Excel: " . count($rows) . "</p>";
            
            // Skip header row
            array_shift($rows);
            
            foreach ($rows as $index => $row_data) {
                $row_number = $index + 2; // +2 karena index dimulai dari 0 dan header di skip
                
                echo "<p>Row $row_number: " . implode(', ', $row_data) . "</p>";
                
                // Filter baris kosong
                if (empty(array_filter($row_data))) {
                    echo "<p>⏭️ Skipping empty row $row_number</p>";
                    continue;
                }
                
                $result = validate_import_row($row_data, $row_number, $pdo);
                if (!empty($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                    echo "<p style='color: red;'>❌ Row $row_number errors: " . implode(', ', $result['errors']) . "</p>";
                } else {
                    $data[] = $result['data'];
                    echo "<p style='color: green;'>✅ Row $row_number valid: " . print_r($result['data'], true) . "</p>";
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Error membaca file Excel: ' . $e->getMessage();
            echo "<p style='color: red;'>❌ Excel error: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>📊 Final Result:</h3>";
    echo "<p>Data count: " . count($data) . "</p>";
    echo "<p>Errors count: " . count($errors) . "</p>";
    
    return ['data' => $data, 'errors' => $errors];
}

function validate_import_row($row_data, $row_number, $pdo) {
    echo "<h4>🔍 Validating Row $row_number</h4>";
    $errors = [];
    $data = [];
    
    // Filter data kosong
    $row_data = array_map(function($value) {
        return is_string($value) ? trim($value) : $value;
    }, $row_data);
    
    echo "<p>Row data: " . print_r($row_data, true) . "</p>";
    
    if (count($row_data) < 8) {
        $errors[] = "Baris $row_number: Data tidak lengkap (harus 8 kolom)";
        echo "<p style='color: red;'>❌ Insufficient columns: " . count($row_data) . "</p>";
        return ['errors' => $errors];
    }
    
    // Validasi tanggal SPBU
    $tanggal_spbu = $row_data[0];
    if (empty($tanggal_spbu)) {
        $errors[] = "Baris $row_number: Tanggal SPBU tidak boleh kosong";
        echo "<p style='color: red;'>❌ Empty tanggal SPBU</p>";
    } else {
        // Konversi format tanggal dari "Sunday, August 10, 2025" ke "2025-08-10"
        $date_obj = DateTime::createFromFormat('l, F j, Y', $tanggal_spbu);
        if ($date_obj) {
            $data['tanggal'] = $date_obj->format('Y-m-d');
            echo "<p style='color: green;'>✅ Tanggal converted: " . $data['tanggal'] . "</p>";
        } else {
            $errors[] = "Baris $row_number: Format tanggal SPBU salah (contoh: Sunday, August 10, 2025)";
            echo "<p style='color: red;'>❌ Invalid date format: $tanggal_spbu</p>";
        }
    }
    
    // Validasi Shift SPBU
    $shift_spbu = $row_data[1];
    if (empty($shift_spbu)) {
        $errors[] = "Baris $row_number: Shift SPBU tidak boleh kosong";
        echo "<p style='color: red;'>❌ Empty shift SPBU</p>";
    } else {
        $valid_shifts = ['1', '2', '3'];
        if (!in_array($shift_spbu, $valid_shifts)) {
            $errors[] = "Baris $row_number: Shift SPBU '$shift_spbu' tidak valid (harus 1, 2, atau 3)";
            echo "<p style='color: red;'>❌ Invalid shift: $shift_spbu</p>";
        } else {
            // Konversi shift ke format yang sesuai
            $shift_map = ['1' => 'Pagi', '2' => 'Siang', '3' => 'Malam'];
            $data['shift'] = $shift_map[$shift_spbu];
            echo "<p style='color: green;'>✅ Shift converted: " . $data['shift'] . "</p>";
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
        echo "<p style='color: red;'>❌ Empty jumlah liter</p>";
    } else {
        // Hapus suffix "L" dan konversi ke angka
        $liter_clean = str_replace(' L', '', $jumlah_liter);
        if (!is_numeric($liter_clean) || $liter_clean <= 0) {
            $errors[] = "Baris $row_number: Jumlah Liter '$jumlah_liter' tidak valid";
            echo "<p style='color: red;'>❌ Invalid liter: $jumlah_liter</p>";
        } else {
            $data['jumlah_liter'] = $liter_clean;
            echo "<p style='color: green;'>✅ Liter converted: " . $data['jumlah_liter'] . "</p>";
        }
    }
    
    // Validasi Harga per Liter
    $harga = $row_data[5];
    if (empty($harga)) {
        $errors[] = "Baris $row_number: Harga tidak boleh kosong";
        echo "<p style='color: red;'>❌ Empty harga</p>";
    } else {
        // Hapus separator dan konversi ke angka
        $harga_clean = str_replace(['.', ','], '', $harga);
        if (!is_numeric($harga_clean) || $harga_clean <= 0) {
            $errors[] = "Baris $row_number: Harga '$harga' tidak valid";
            echo "<p style='color: red;'>❌ Invalid harga: $harga</p>";
        } else {
            $data['harga_per_liter'] = $harga_clean;
            echo "<p style='color: green;'>✅ Harga converted: " . $data['harga_per_liter'] . "</p>";
        }
    }
    
    // Hitung total amount berdasarkan jumlah liter dan harga
    if (isset($data['jumlah_liter']) && isset($data['harga_per_liter'])) {
        $data['amount'] = $data['jumlah_liter'] * $data['harga_per_liter'];
        echo "<p style='color: green;'>✅ Amount calculated: " . $data['amount'] . "</p>";
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
        echo "<p style='color: green;'>✅ Tipe pembayaran set: BCA</p>";
    } else {
        $errors[] = "Baris $row_number: Tipe pembayaran default 'BCA' tidak ditemukan di database";
        echo "<p style='color: red;'>❌ BCA payment type not found in database</p>";
    }
    
    echo "<h4>📋 Final Data for Row $row_number:</h4>";
    echo "<pre>" . print_r($data, true) . "</pre>";
    
    return ['data' => $data, 'errors' => $errors];
}

// Ambil tipe transaksi untuk validasi
$tipe = $pdo->query("SELECT * FROM tipe_transaksi")->fetchAll();
echo "<h3>💳 Payment Types in Database:</h3>";
echo "<pre>" . print_r($tipe, true) . "</pre>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Import Transaksi</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-container { border: 2px dashed #ccc; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
        .file-info { margin-top: 10px; padding: 10px; background: #f0f0f0; border-radius: 4px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug Import Transaksi</h1>
        
        <div class="form-container">
            <form method="post" enctype="multipart/form-data">
                <h3>Upload File untuk Debug</h3>
                <input type="file" name="file" accept=".csv,.xlsx,.xls" required>
                <br><br>
                <button type="submit">Upload & Debug</button>
            </form>
            
            <div class="file-info" id="fileInfo"></div>
        </div>
        
        <?php if ($show_preview && !empty($preview_data)): ?>
        <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h3>✅ Preview Data Berhasil!</h3>
            <p>Total data: <?= count($preview_data) ?></p>
            <pre><?= print_r($preview_data, true) ?></pre>
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
    
    <script>
        document.querySelector('input[type="file"]').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const info = document.getElementById('fileInfo');
            
            if (file) {
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                info.innerHTML = `
                    <strong>File Info:</strong><br>
                    Nama: ${file.name}<br>
                    Ukuran: ${sizeMB} MB<br>
                    Tipe: ${file.type}
                `;
            } else {
                info.innerHTML = '';
            }
        });
    </script>
</body>
</html>
