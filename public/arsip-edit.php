<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] ?? 'admin') === 'kepala_spbu') {
    header('Location: rekap-transaksi.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

// Validasi input
$required_fields = ['id', 'tanggal', 'mid', 'tid', 'jenis_bbm', 'amount'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "Field $field harus diisi"]);
        exit;
    }
}

$id = (int)$_POST['id'];
$tanggal = $_POST['tanggal'];
$mid = trim($_POST['mid']);
$tid = trim($_POST['tid']);
$jenis_bbm = $_POST['jenis_bbm'];
$amount = (float)$_POST['amount'];

// Validasi jenis BBM
$allowed_bbm = ['Pertalite', 'Pertamax', 'Dexlite', 'Solar'];
if (!in_array($jenis_bbm, $allowed_bbm)) {
    echo json_encode(['success' => false, 'message' => 'Jenis BBM tidak valid']);
    exit;
}

// Validasi amount
if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Amount harus lebih dari 0']);
    exit;
}

try {
    // Cek apakah arsip ada
    $stmt = $pdo->prepare("SELECT id FROM transaksi WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Arsip tidak ditemukan']);
        exit;
    }
    
    // Update arsip
    $stmt = $pdo->prepare("UPDATE transaksi SET tanggal = ?, mid = ?, tid = ?, jenis_bbm = ?, amount = ? WHERE id = ?");
    $result = $stmt->execute([$tanggal, $mid, $tid, $jenis_bbm, $amount, $id]);
    
    if ($result) {
        // Catat aktivitas
        $pdo->prepare("INSERT INTO aktivitas (aktivitas, admin_id) VALUES (?, ?)")
            ->execute(['Mengedit arsip transaksi ID: ' . $id, $_SESSION['admin_id']]);
        echo json_encode(['success' => true, 'message' => 'Arsip berhasil diperbarui']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui arsip']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?> 