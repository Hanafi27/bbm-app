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

// Ambil data JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !is_numeric($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

$id = (int)$input['id'];

try {
    // Cek apakah arsip ada
    $stmt = $pdo->prepare("SELECT id FROM transaksi WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Arsip tidak ditemukan']);
        exit;
    }
    
    // Hapus arsip
    $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Arsip berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus arsip']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?> 