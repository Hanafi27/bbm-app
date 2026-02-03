<?php
// Halaman test untuk debugging upload file
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Debug Upload File</h2>";
    echo "<pre>";
    echo "POST data:\n";
    print_r($_POST);
    echo "\n\nFILES data:\n";
    print_r($_FILES);
    echo "</pre>";
    
    if (isset($_FILES['file'])) {
        $file = $_FILES['file'];
        echo "<h3>File Info:</h3>";
        echo "<ul>";
        echo "<li>Name: " . $file['name'] . "</li>";
        echo "<li>Type: " . $file['type'] . "</li>";
        echo "<li>Size: " . $file['size'] . " bytes</li>";
        echo "<li>Error: " . $file['error'] . "</li>";
        echo "<li>Tmp Name: " . $file['tmp_name'] . "</li>";
        echo "</ul>";
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            echo "<p style='color: green;'>✅ File berhasil diupload!</p>";
            
            // Coba baca file CSV
            if ($file['type'] === 'text/csv') {
                echo "<h3>Isi File CSV:</h3>";
                $handle = fopen($file['tmp_name'], 'r');
                if ($handle) {
                    $row = 1;
                    while (($data = fgetcsv($handle)) !== false) {
                        echo "Baris $row: " . implode(', ', $data) . "<br>";
                        $row++;
                        if ($row > 5) break; // Tampilkan 5 baris pertama saja
                    }
                    fclose($handle);
                }
            }
        } else {
            echo "<p style='color: red;'>❌ Error upload: " . $file['error'] . "</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Upload File</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-container { max-width: 500px; padding: 20px; border: 1px solid #ccc; }
        .file-info { margin-top: 10px; padding: 10px; background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Test Upload File</h1>
    
    <div class="form-container">
        <form method="post" enctype="multipart/form-data">
            <h3>Upload File Test</h3>
            <input type="file" name="file" accept=".csv,.xlsx,.xls" required>
            <br><br>
            <button type="submit">Upload & Test</button>
        </form>
        
        <div class="file-info" id="fileInfo"></div>
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
