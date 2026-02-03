<?php
require_once '../includes/auth.php';
require_login();
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="detail_transaksi_import.csv"');

$output = fopen('php://output', 'w');

// Header kolom
fputcsv($output, [
    'Tanggal', 'Hari', 'Shift', 'MID', 'TID', 'Jenis BBM', 'Tipe Transaksi', 'Jumlah Liter', 'Harga Per Liter', 'Total Amount'
]);

// Ambil data dari session terlebih dahulu
$import_data = $_SESSION['import_detail_data'] ?? [];

// Fallback: ambil dari DB berdasarkan aktivitas import terakhir
if (empty($import_data)) {
    try {
        $stmt = $pdo->prepare("SELECT aktivitas FROM aktivitas WHERE admin_id = ? AND aktivitas LIKE 'Import % transaksi%' ORDER BY waktu DESC LIMIT 1");
        $stmt->execute([$_SESSION['admin_id']]);
        $lastActivity = $stmt->fetchColumn();
        if ($lastActivity && preg_match('/Import\s+(\d+)\s+transaksi/i', $lastActivity, $m)) {
            $lastCount = (int)$m[1];
            if ($lastCount > 0) {
                $stmt2 = $pdo->prepare("SELECT t.*, tt.nama AS tipe_pembayaran_nama FROM transaksi t LEFT JOIN tipe_transaksi tt ON t.tipe_id = tt.id WHERE t.admin_id = ? ORDER BY t.id DESC LIMIT {$lastCount}");
                $stmt2->execute([$_SESSION['admin_id']]);
                $rows = $stmt2->fetchAll();
                foreach ($rows as $r) {
                    $import_data[] = [
                        'tanggal_transaksi' => $r['tanggal'],
                        'shift' => $r['shift'] ?? '-',
                        'mid' => $r['mid'] ?? '',
                        'tid' => $r['tid'] ?? '',
                        'jenis_bbm' => $r['jenis_bbm'] ?? '',
                        'tipe_transaksi' => $r['tipe_pembayaran_nama'] ?? '',
                        'jumlah_liter' => null,
                        'harga_per_liter' => null,
                        'total_amount' => isset($r['amount']) ? (float)$r['amount'] : 0,
                    ];
                }
            }
        }
    } catch (Exception $e) {
        // Abaikan error dan hasilkan CSV kosong
    }
}

// Tulis baris data
foreach ($import_data as $row) {
    $tanggal = isset($row['tanggal_transaksi']) ? $row['tanggal_transaksi'] : '';
    $hari = $tanggal ? date('l', strtotime($tanggal)) : '';
    $shift = $row['shift'] ?? '';
    $mid = $row['mid'] ?? '';
    $tid = $row['tid'] ?? '';
    $jenis_bbm = $row['jenis_bbm'] ?? '';
    $tipe_transaksi = $row['tipe_transaksi'] ?? '';
    $jumlah_liter = (isset($row['jumlah_liter']) && $row['jumlah_liter'] !== null && $row['jumlah_liter'] !== '') ? $row['jumlah_liter'] : '';
    $harga_per_liter = (isset($row['harga_per_liter']) && $row['harga_per_liter'] !== null && $row['harga_per_liter'] !== '') ? $row['harga_per_liter'] : '';
    $total_amount = isset($row['total_amount']) ? $row['total_amount'] : '';

    fputcsv($output, [
        $tanggal,
        $hari,
        $shift,
        $mid,
        $tid,
        $jenis_bbm,
        $tipe_transaksi,
        $jumlah_liter,
        $harga_per_liter,
        $total_amount
    ]);
}

fclose($output);
exit;
?>


