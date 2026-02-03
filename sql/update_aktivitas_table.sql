-- Script untuk mengupdate tabel aktivitas yang sudah ada
-- Menambahkan kolom admin_id untuk memisahkan aktivitas per user

-- Tambah kolom admin_id ke tabel aktivitas
ALTER TABLE aktivitas ADD COLUMN admin_id INT NOT NULL DEFAULT 1 AFTER waktu;

-- Tambah foreign key constraint
ALTER TABLE aktivitas ADD CONSTRAINT aktivitas_ibfk_1 FOREIGN KEY (admin_id) REFERENCES users(id);

-- Update semua aktivitas yang sudah ada untuk menggunakan admin_id = 1 (user pertama)
-- Ini mengasumsikan bahwa semua aktivitas yang sudah ada adalah milik user pertama
UPDATE aktivitas SET admin_id = 1 WHERE admin_id = 0 OR admin_id IS NULL;

-- Hapus default value setelah data diupdate
ALTER TABLE aktivitas ALTER COLUMN admin_id DROP DEFAULT;

-- Tambah kolom role pada tabel users jika belum ada
ALTER TABLE users ADD COLUMN role ENUM('admin','kepala_spbu') NOT NULL DEFAULT 'admin' AFTER password; 