<?php
require_once '../includes/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] ?? 'admin') === 'kepala_spbu') {
    header('Location: rekap-transaksi.php');
    exit;
}

try {
    $pdo->exec('DELETE FROM transaksi');
    // Catat aktivitas
    $pdo->prepare("INSERT INTO aktivitas (aktivitas, admin_id) VALUES (?, ?)")
        ->execute(['Menghapus semua arsip/transaksi', $_SESSION['admin_id']]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 