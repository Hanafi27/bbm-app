<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

$id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM transaksi WHERE id = ?");
    $stmt->execute([$id]);
    $arsip = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($arsip) {
        echo json_encode(['success' => true, 'arsip' => $arsip]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Arsip tidak ditemukan']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
?> 