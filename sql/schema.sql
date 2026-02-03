-- Tabel users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','kepala_spbu') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel aktivitas (dengan admin_id untuk memisahkan aktivitas per user)
CREATE TABLE aktivitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aktivitas VARCHAR(255) NOT NULL,
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    admin_id INT NOT NULL,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

-- Tabel tipe_transaksi
CREATE TABLE tipe_transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(50) NOT NULL UNIQUE
);

-- Tabel transaksi
CREATE TABLE transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    mid VARCHAR(20) NOT NULL,
    tid VARCHAR(20) NOT NULL,
    tipe_id INT NOT NULL,
    jenis_bbm ENUM('Pertalite','Pertamax','Dexlite','Solar') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    net_amount DECIMAL(15,2) NOT NULL,
    mdr DECIMAL(15,2) NOT NULL,
    selisih DECIMAL(15,2) NOT NULL,
    admin_id INT NOT NULL,
    FOREIGN KEY (tipe_id) REFERENCES tipe_transaksi(id),
    FOREIGN KEY (admin_id) REFERENCES users(id)
); 