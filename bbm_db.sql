-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 18 Agu 2025 pada 14.33
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bbm_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `aktivitas`
--

CREATE TABLE `aktivitas` (
  `id` int(11) NOT NULL,
  `aktivitas` varchar(255) NOT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `aktivitas`
--

INSERT INTO `aktivitas` (`id`, `aktivitas`, `waktu`, `admin_id`) VALUES
(1, 'Menambah transaksi baru (TID: A2DC9354)', '2025-07-11 13:54:30', 1),
(2, 'Menambah tipe transaksi: BNI', '2025-07-11 13:54:48', 1),
(3, 'Menambah tipe transaksi: Mandiri', '2025-07-11 13:54:53', 1),
(4, 'Menambah tipe transaksi: Cimbniaga', '2025-07-11 13:55:00', 1),
(5, 'Mengedit tipe transaksi: Seabank', '2025-07-11 13:55:11', 1),
(6, 'Menghapus tipe transaksi ID: 9', '2025-07-12 02:09:14', 1),
(7, 'Menambah transaksi baru (TID: C2DC9354)', '2025-07-12 02:10:51', 1),
(8, 'Menambah tipe transaksi: dana', '2025-07-12 02:13:14', 1),
(9, 'Menghapus tipe transaksi ID: 10', '2025-07-12 02:13:17', 1),
(10, 'Menambah transaksi baru (TID: C2DC9354)', '2025-07-13 08:40:35', 1),
(11, 'Menambah transaksi baru (TID: A2DC9354)', '2025-07-13 10:09:37', 2),
(12, 'Menambah transaksi baru (TID: A2DC9354)', '2025-07-14 09:06:11', 1),
(13, 'Menghapus semua arsip/transaksi', '2025-07-14 09:08:30', 1),
(14, 'Menambah transaksi baru (TID: D2DF8372)', '2025-07-14 09:08:54', 1),
(15, 'Menambah transaksi baru (TID: C2DC9354)', '2025-07-14 09:09:16', 1),
(16, 'Menambah transaksi baru (TID: D2DF8372)', '2025-08-14 11:38:56', 1),
(17, 'Import otomatis 3 transaksi dari file', '2025-08-17 10:55:56', 1),
(18, 'Import otomatis 3 transaksi dari file', '2025-08-17 10:56:35', 1),
(19, 'Menghapus semua arsip/transaksi', '2025-08-17 10:56:55', 1),
(20, 'Import otomatis 3 transaksi dari file', '2025-08-17 10:57:04', 1),
(21, 'Menghapus semua arsip/transaksi', '2025-08-17 10:57:14', 1),
(22, 'Import otomatis 3 transaksi dari file', '2025-08-17 10:57:50', 1),
(23, 'Menambah transaksi baru (TID: D2DF8372)', '2025-08-17 10:58:36', 1),
(24, 'Menghapus semua arsip/transaksi', '2025-08-17 23:49:36', 1),
(25, 'Import 3 transaksi dari file Excel ke arsip dengan format lengkap (MID, TID, Jenis BBM, Tipe Transaksi, Jumlah Liter, Harga Per Liter, Total Amount, Tanggal Transaksi dd/mm/yy, Shift)', '2025-08-18 00:26:11', 1),
(26, 'Import 3 transaksi dari file Excel ke arsip dengan format lengkap (MID, TID, Jenis BBM, Tipe Transaksi, Jumlah Liter, Harga Per Liter, Total Amount, Tanggal Transaksi dd/mm/yy, Shift)', '2025-08-18 00:47:33', 1),
(27, 'Menghapus semua arsip/transaksi', '2025-08-18 00:52:22', 1),
(28, 'Import 3 transaksi dari file Excel ke arsip dengan format lengkap (MID, TID, Jenis BBM, Tipe Transaksi, Jumlah Liter, Harga Per Liter, Total Amount, Tanggal Transaksi dd/mm/yy, Shift)', '2025-08-18 00:53:56', 1),
(29, 'Menghapus semua arsip/transaksi', '2025-08-18 12:19:03', 1),
(30, 'Import 3 transaksi dari file Excel ke arsip dengan format lengkap (MID, TID, Jenis BBM, Tipe Transaksi, Jumlah Liter, Harga Per Liter, Total Amount, Tanggal Transaksi dd/mm/yy, Shift)', '2025-08-18 12:19:55', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `arsip`
--

CREATE TABLE `arsip` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tipe_transaksi`
--

CREATE TABLE `tipe_transaksi` (
  `id` int(11) NOT NULL,
  `nama` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tipe_transaksi`
--

INSERT INTO `tipe_transaksi` (`id`, `nama`) VALUES
(6, 'BCA'),
(7, 'BNI'),
(5, 'BRI'),
(8, 'Mandiri');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `mid` varchar(20) NOT NULL,
  `tid` varchar(20) NOT NULL,
  `tipe_id` int(11) NOT NULL,
  `jenis_bbm` enum('Pertalite','Pertamax','Dexlite','Solar') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `shift` enum('Pagi','Siang','Malam') NOT NULL,
  `net_amount` decimal(15,2) NOT NULL,
  `mdr` decimal(15,2) NOT NULL,
  `selisih` decimal(15,2) NOT NULL,
  `admin_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi`
--

INSERT INTO `transaksi` (`id`, `tanggal`, `mid`, `tid`, `tipe_id`, `jenis_bbm`, `amount`, `shift`, `net_amount`, `mdr`, `selisih`, `admin_id`) VALUES
(34, '2025-01-15', '0000855001598321', 'D2DF8372', 6, 'Pertalite', 201500.00, 'Pagi', 0.00, 0.00, 0.00, 1),
(35, '2025-01-15', '0000855001598321', 'C2DC9354', 7, 'Pertamax', 280000.00, 'Siang', 0.00, 0.00, 0.00, 1),
(36, '2025-01-15', '0000855001598321', 'A2DC9354', 5, 'Solar', 300000.00, 'Malam', 0.00, 0.00, 0.00, 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','kepala_spbu') NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'hans', 'hans@gmail.com', '$2y$10$nrlTI6wDmAeLg8v35VKTtu4FkMqPQYwGbF2VxMSHbJ/xsWUBzgTkW', 'admin', '2025-07-11 10:24:05'),
(2, 'ahmad', 'mad@gmail.com', '$2y$10$yBqOyi8eTrlxH1DY3GPAfuRIy4eD8cgxx7iKzZNEqbOA7UN9kuInu', 'admin', '2025-07-13 10:00:18'),
(4, 'ilham', 'ilham@gmail.com', '$2y$10$29sv1Y4W1Sr4pnbyIfgZnuXyjokivGtoqN/zEw5Dm3A2XCKLPc/J6', 'admin', '2025-07-27 10:13:59'),
(5, 'ganjar', 'ganjar@gmail.com', '$2y$10$Zje9C1uL74JP5h6rfUgEKePRmQAZ2U0pYo3A1QAPmSuBbTvmRo0Bm', 'kepala_spbu', '2025-07-28 09:33:56');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `aktivitas`
--
ALTER TABLE `aktivitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aktivitas_ibfk_1` (`admin_id`);

--
-- Indeks untuk tabel `arsip`
--
ALTER TABLE `arsip`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `tipe_transaksi`
--
ALTER TABLE `tipe_transaksi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama` (`nama`);

--
-- Indeks untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tipe_id` (`tipe_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `aktivitas`
--
ALTER TABLE `aktivitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT untuk tabel `arsip`
--
ALTER TABLE `arsip`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tipe_transaksi`
--
ALTER TABLE `tipe_transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `aktivitas`
--
ALTER TABLE `aktivitas`
  ADD CONSTRAINT `aktivitas_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`tipe_id`) REFERENCES `tipe_transaksi` (`id`),
  ADD CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
