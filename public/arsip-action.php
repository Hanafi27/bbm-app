<?php
require_once '../includes/db.php';
session_start();
if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] ?? 'admin') === 'kepala_spbu') {
    header('Location: rekap-transaksi.php');
    exit;
}
header('Content-Type: application/json');
$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? 0;
if ($action === 'delete' && $id) {
    $stmt = $pdo->prepare('DELETE FROM transaksi WHERE id = ?');
    $stmt->execute([$id]);
    // Catat aktivitas
    $pdo->prepare("INSERT INTO aktivitas (aktivitas, admin_id) VALUES (?, ?)")
        ->execute(['Menghapus arsip transaksi ID: ' . $id, $_SESSION['admin_id']]);
    echo json_encode(['success' => true, 'message' => 'Arsip berhasil dihapus.']);
    exit;
}
if ($action === 'update' && $id) {
    $tanggal = $_POST['tanggal'] ?? '';
    $mid = $_POST['mid'] ?? '';
    $tid = $_POST['tid'] ?? '';
    $tipe_id = $_POST['tipe_id'] ?? '';
    $jenis_bbm = $_POST['jenis_bbm'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $stmt = $pdo->prepare('UPDATE transaksi SET tanggal=?, mid=?, tid=?, tipe_id=?, jenis_bbm=?, amount=? WHERE id=?');
    $stmt->execute([$tanggal, $mid, $tid, $tipe_id, $jenis_bbm, $amount, $id]);
    // Catat aktivitas
    $pdo->prepare("INSERT INTO aktivitas (aktivitas, admin_id) VALUES (?, ?)")
        ->execute(['Mengupdate arsip transaksi ID: ' . $id, $_SESSION['admin_id']]);
    echo json_encode(['success' => true, 'message' => 'Arsip berhasil diupdate.']);
    exit;
}
if ($action === 'tambah') {
    $tanggal = $_POST['tanggal'] ?? '';
    $mid = $_POST['mid'] ?? '';
    $tid = $_POST['tid'] ?? '';
    $tipe_id = $_POST['tipe_id'] ?? '';
    $jenis_bbm = $_POST['jenis_bbm'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $admin_id = $_SESSION['admin_id'];
    if ($tanggal && $mid && $tid && $tipe_id && $jenis_bbm && $amount) {
        $stmt = $pdo->prepare('INSERT INTO transaksi (tanggal, mid, tid, tipe_id, jenis_bbm, amount, admin_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$tanggal, $mid, $tid, $tipe_id, $jenis_bbm, $amount, $admin_id]);
        // Catat aktivitas
        $pdo->prepare("INSERT INTO aktivitas (aktivitas, admin_id) VALUES (?, ?)")
            ->execute(['Menambah arsip transaksi baru (TID: ' . $tid . ')', $_SESSION['admin_id']]);
        echo json_encode(['success' => true, 'message' => 'Arsip berhasil ditambahkan.']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
        exit;
    }
}
echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']); 