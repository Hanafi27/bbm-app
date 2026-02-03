<?php
/**
 * Script untuk mengupdate tabel aktivitas agar terpisah per user
 * Jalankan script ini sekali saja untuk mengupdate database yang sudah ada
 */

require_once 'includes/db.php';

echo "<h2>Update Database Aktivitas</h2>";
echo "<p>Memulai proses update...</p>";

try {
    // 1. Cek apakah kolom admin_id sudah ada
    $stmt = $pdo->query("SHOW COLUMNS FROM aktivitas LIKE 'admin_id'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: orange;'>⚠️ Kolom admin_id sudah ada di tabel aktivitas.</p>";
    } else {
        // 2. Tambah kolom admin_id
        echo "<p>📝 Menambahkan kolom admin_id...</p>";
        $pdo->exec("ALTER TABLE aktivitas ADD COLUMN admin_id INT NOT NULL DEFAULT 1 AFTER waktu");
        echo "<p style='color: green;'>✅ Kolom admin_id berhasil ditambahkan.</p>";
        
        // 3. Tambah foreign key constraint
        echo "<p>🔗 Menambahkan foreign key constraint...</p>";
        $pdo->exec("ALTER TABLE aktivitas ADD CONSTRAINT aktivitas_ibfk_1 FOREIGN KEY (admin_id) REFERENCES users(id)");
        echo "<p style='color: green;'>✅ Foreign key constraint berhasil ditambahkan.</p>";
        
        // 4. Update data existing
        echo "<p>🔄 Mengupdate data yang sudah ada...</p>";
        $pdo->exec("UPDATE aktivitas SET admin_id = 1 WHERE admin_id = 0 OR admin_id IS NULL");
        echo "<p style='color: green;'>✅ Data existing berhasil diupdate.</p>";
        
        // 5. Hapus default value
        echo "<p>🧹 Menghapus default value...</p>";
        $pdo->exec("ALTER TABLE aktivitas ALTER COLUMN admin_id DROP DEFAULT");
        echo "<p style='color: green;'>✅ Default value berhasil dihapus.</p>";
    }
    
    // 6. Verifikasi struktur tabel
    echo "<p>🔍 Memverifikasi struktur tabel...</p>";
    $stmt = $pdo->query("DESCRIBE aktivitas");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Struktur Tabel Aktivitas:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 7. Cek jumlah aktivitas per user
    echo "<h3>Jumlah Aktivitas per User:</h3>";
    $stmt = $pdo->query("SELECT u.nama, COUNT(a.id) as jumlah_aktivitas FROM users u LEFT JOIN aktivitas a ON u.id = a.admin_id GROUP BY u.id, u.nama");
    $userActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>User</th><th>Jumlah Aktivitas</th></tr>";
    foreach ($userActivities as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['nama']) . "</td>";
        echo "<td>" . htmlspecialchars($user['jumlah_aktivitas']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color: green; font-weight: bold;'>🎉 Update database berhasil selesai!</p>";
    echo "<p>Silakan test aplikasi dengan login menggunakan user yang berbeda untuk memastikan aktivitas terpisah dengan benar.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Silakan periksa koneksi database dan pastikan tabel aktivitas dan users sudah ada.</p>";
}

echo "<hr>";
echo "<p><a href='public/login.php'>Kembali ke Login</a></p>";
?> 