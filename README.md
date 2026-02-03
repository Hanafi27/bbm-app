# BBM App

Aplikasi manajemen arsip dan transaksi penjualan BBM (Bahan Bakar Minyak) berbasis web, dikembangkan dengan PHP dan MySQL.

## Fitur Utama
- **Manajemen Arsip:** Tambah, edit, hapus, dan ekspor arsip data.
- **Transaksi Penjualan:** Input, filter, dan impor data transaksi penjualan BBM.
- **Dashboard:** Statistik dan rekap data penjualan.
- **Autentikasi:** Login, register, dan logout pengguna.
- **Impor/Ekspor:** Dukungan impor data dari CSV/PDF dan ekspor rekap transaksi.
- **Validasi & Utilitas:** Validasi data dan utilitas tambahan untuk pengelolaan data.

## Struktur Folder
- `public/` : File utama aplikasi (dashboard, login, transaksi, arsip, dll)
- `includes/` : File PHP untuk koneksi database, autentikasi, sidebar, utilitas, dan validasi
- `assets/` : File JavaScript dan aset pendukung
- `sql/` : Skrip SQL untuk pembuatan dan update database
- `vendor/` : Dependensi Composer (otomatis, jangan edit manual)

## Instalasi
1. **Clone repository** ke direktori web server Anda (misal: `htdocs` untuk XAMPP).
2. **Buat database** di MySQL, lalu impor file `bbm_db.sql` atau gunakan skrip pada folder `sql/`.
3. **Konfigurasi koneksi database** di `includes/db.php` sesuai pengaturan MySQL Anda.
4. **Install dependensi** PHP dengan Composer (jika diperlukan):
   ```bash
   composer install
   ```
5. **Akses aplikasi** melalui browser ke alamat sesuai folder (`http://localhost/bbm-app/public/`).

## Dependensi
- PHP >= 7.4
- MySQL
- [mpdf/mpdf](https://github.com/mpdf/mpdf) (untuk PDF)
- [phpoffice/phpspreadsheet](https://github.com/PHPOffice/PhpSpreadsheet) (untuk Excel/CSV)

## Catatan
- Jangan edit folder `vendor/` secara manual.
- Pastikan file permission pada folder upload/data sesuai kebutuhan.
- Untuk update struktur database, gunakan skrip pada folder `sql/`.

## Lisensi
Proyek ini menggunakan lisensi open source sesuai file LICENSE pada masing-masing dependensi.
