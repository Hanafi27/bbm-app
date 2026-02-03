<?php
require_once '../includes/db.php';
session_start();
header('Content-Type: application/json');

$errors = [];
$role = $_SESSION['role'] ?? 'admin';
try {
    // Statistik
    if ($role === 'kepala_spbu') {
        $stmt = $pdo->query("SELECT COUNT(*) FROM transaksi");
        $totalTransaksi = $stmt->fetchColumn();
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transaksi WHERE admin_id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $totalTransaksi = $stmt->fetchColumn();
    }
} catch (Exception $e) {
    $totalTransaksi = 0;
    $errors[] = 'transaksi: ' . $e->getMessage();
}
try {
    if ($role === 'kepala_spbu') {
        $stmt = $pdo->query("SELECT COUNT(*) FROM transaksi");
        $totalArsip = $stmt->fetchColumn();
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transaksi WHERE admin_id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $totalArsip = $stmt->fetchColumn();
    }
} catch (Exception $e) {
    $totalArsip = 0;
    $errors[] = 'arsip: ' . $e->getMessage();
}
try {
    $totalTipe = $pdo->query("SELECT COUNT(*) FROM tipe_transaksi")->fetchColumn();
} catch (Exception $e) {
    $totalTipe = 0;
    $errors[] = 'tipe_transaksi: ' . $e->getMessage();
}
try {
    $totalUser = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
} catch (Exception $e) {
    $totalUser = 0;
    $errors[] = 'users: ' . $e->getMessage();
}

// Grafik penjualan bulanan (12 bulan terakhir)
$bulanLabels = [];
$bulanData = [];
try {
    for ($i = 11; $i >= 0; $i--) {
        $ts = strtotime(date('Y-m-01') . " -$i months");
        $bulanLabels[] = date('M Y', $ts);
        $bulanData[] = 0;
    }
    $bulanMap = array_combine($bulanLabels, array_keys($bulanLabels));
    if ($role === 'kepala_spbu') {
        $stmt = $pdo->prepare("SELECT DATE_FORMAT(tanggal, '%b %Y') as bulan, SUM(amount) as total FROM transaksi WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY bulan ORDER BY MIN(tanggal)");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT DATE_FORMAT(tanggal, '%b %Y') as bulan, SUM(amount) as total FROM transaksi WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND admin_id = ? GROUP BY bulan ORDER BY MIN(tanggal)");
        $stmt->execute([$_SESSION['admin_id']]);
    }
    foreach ($stmt as $row) {
        if (isset($bulanMap[$row['bulan']])) {
            $bulanData[$bulanMap[$row['bulan']]] = (float)$row['total'];
        }
    }
} catch (Exception $e) {
    $errors[] = 'grafik_bulanan: ' . $e->getMessage();
}

// Grafik penjualan harian (7 hari terakhir)
$harianLabels = [];
$harianData = [];
try {
    for ($i = 6; $i >= 0; $i--) {
        $ts = strtotime(date('Y-m-d') . " -$i days");
        $harianLabels[] = date('D, d M', $ts);
        $harianData[] = 0;
    }
    $harianMap = array_combine($harianLabels, array_keys($harianLabels));
    if ($role === 'kepala_spbu') {
        $stmt = $pdo->prepare("SELECT DATE_FORMAT(tanggal, '%a, %d %b') as hari, SUM(amount) as total FROM transaksi WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY hari ORDER BY MIN(tanggal)");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT DATE_FORMAT(tanggal, '%a, %d %b') as hari, SUM(amount) as total FROM transaksi WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND admin_id = ? GROUP BY hari ORDER BY MIN(tanggal)");
        $stmt->execute([$_SESSION['admin_id']]);
    }
    foreach ($stmt as $row) {
        if (isset($harianMap[$row['hari']])) {
            $harianData[$harianMap[$row['hari']]] = (float)$row['total'];
        }
    }
} catch (Exception $e) {
    $errors[] = 'grafik_harian: ' . $e->getMessage();
}

// Aktivitas terbaru (hanya untuk user yang sedang login)
$aktivitas = [];
try {
    if ($pdo->query("SHOW TABLES LIKE 'aktivitas'")->fetch()) {
        if ($role === 'kepala_spbu') {
            $stmt = $pdo->prepare("SELECT aktivitas, waktu FROM aktivitas ORDER BY waktu DESC LIMIT 5");
            $stmt->execute();
        } else {
            $stmt = $pdo->prepare("SELECT aktivitas, waktu FROM aktivitas WHERE admin_id = ? ORDER BY waktu DESC LIMIT 5");
            $stmt->execute([$_SESSION['admin_id']]);
        }
        foreach ($stmt as $row) {
            $aktivitas[] = [
                'text' => $row['aktivitas'],
                'time' => date('d-m-Y H:i', strtotime($row['waktu']))
            ];
        }
    }
} catch (Exception $e) {
    $errors[] = 'aktivitas: ' . $e->getMessage();
}

// Output JSON

echo json_encode([
    'statistik' => [
        'transaksi' => $totalTransaksi,
        'arsip' => $totalArsip,
        'tipe' => $totalTipe,
        'user' => $totalUser
    ],
    'grafik_bulanan' => [
        'labels' => $bulanLabels,
        'data' => $bulanData
    ],
    'grafik_harian' => [
        'labels' => $harianLabels,
        'data' => $harianData
    ],
    'aktivitas' => $aktivitas,
    'errors' => $errors
]); 