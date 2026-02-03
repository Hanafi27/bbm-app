<?php
require_once '../includes/db.php';
session_start();
if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] ?? 'admin') === 'kepala_spbu') {
    header('Location: rekap-transaksi.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(trim($_POST['nama']))) {
    $stmt = $pdo->prepare("INSERT INTO arsip (nama) VALUES (?)");
    $stmt->execute([trim($_POST['nama'])]);
    header('Location: arship-list.php?success=1');
    exit;
}
header('Location: arsip-list.php'); 