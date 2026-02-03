<?php
require_once '../includes/auth.php';
require_login();

// Halaman ini sudah digantikan oleh transaksi-import
header('Location: transaksi-import.php');
        exit;
?>