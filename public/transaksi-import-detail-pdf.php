<?php
require_once '../includes/auth.php';
require_login();
require_once '../includes/db.php';
require_once '../vendor/autoload.php';

use Mpdf\Mpdf;

if (session_status() === PHP_SESSION_NONE) session_start();

// Ambil data dari session terlebih dahulu
$import_data = $_SESSION['import_detail_data'] ?? [];

// Fallback ke DB: gunakan aktivitas import terakhir
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
        // Abaikan; akan menghasilkan PDF kosong
    }
}

// Hitung ringkasan
$total_amount = 0; $total_liter = 0;
foreach ($import_data as $row) {
    $total_amount += isset($row['total_amount']) ? (float)$row['total_amount'] : 0;
    $total_liter += (isset($row['jumlah_liter']) && is_numeric($row['jumlah_liter'])) ? (float)$row['jumlah_liter'] : 0;
}

// HTML template
$dateNow = date('d/m/Y H:i');
$totalCount = count($import_data);

$html = '<html><head><style>
body { font-family: sans-serif; font-size: 10pt; }
.title { color: #0099da; font-weight: bold; font-size: 14pt; margin-bottom: 4px; }
.subtitle { color: #555; font-size: 9pt; margin-bottom: 10px; }
.summary { margin: 10px 0 14px; }
.summary .card { display: inline-block; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px 10px; margin-right: 8px; background: #f8fafc; }
.summary .label { font-size: 8pt; color: #666; }
.summary .value { font-weight: bold; font-size: 11pt; color: #111827; }
table { width: 100%; border-collapse: collapse; }
th { background: #0099da; color: #fff; padding: 6px; font-weight: bold; font-size: 9pt; }
td { border-bottom: 1px solid #e5e7eb; padding: 6px; font-size: 9pt; }
.right { text-align: right; }
.mono { font-family: monospace; }
.muted { color: #6b7280; font-size: 8pt; }
</style></head><body>';

$html .= '<div class="title">Detail Transaksi Import</div>';
$html .= '<div class="subtitle">Dicetak: ' . htmlspecialchars($dateNow) . '</div>';

$html .= '<div class="summary">';
$html .= '<div class="card"><div class="label">Total Transaksi</div><div class="value">' . number_format($totalCount) . '</div></div>';
$html .= '<div class="card"><div class="label">Total Liter</div><div class="value">' . number_format($total_liter, 1) . ' L</div></div>';
$html .= '<div class="card"><div class="label">Total Amount</div><div class="value">Rp ' . number_format($total_amount, 0, ',', '.') . '</div></div>';
$html .= '</div>';

$html .= '<table autosize="1"><thead><tr>' .
         '<th>No</th><th>Tanggal</th><th>Shift</th><th>MID</th><th>TID</th>' .
         '<th>Jenis BBM</th><th>Tipe</th><th class="right">Liter</th><th class="right">Harga/L</th><th class="right">Total</th>' .
         '</tr></thead><tbody>';

foreach ($import_data as $i => $row) {
    $tgl = isset($row['tanggal_transaksi']) ? date('d/m/Y', strtotime($row['tanggal_transaksi'])) : '';
    $shift = $row['shift'] ?? '';
    $mid = $row['mid'] ?? '';
    $tid = $row['tid'] ?? '';
    $jenis = $row['jenis_bbm'] ?? '';
    $tipe = $row['tipe_transaksi'] ?? '';
    $liter = (isset($row['jumlah_liter']) && $row['jumlah_liter'] !== null && $row['jumlah_liter'] !== '') ? number_format((float)$row['jumlah_liter'], 1, ',', '.') : '-';
    $harga = (isset($row['harga_per_liter']) && $row['harga_per_liter'] !== null && $row['harga_per_liter'] !== '') ? 'Rp ' . number_format((float)$row['harga_per_liter'], 0, ',', '.') : '-';
    $total = 'Rp ' . number_format(isset($row['total_amount']) ? (float)$row['total_amount'] : 0, 0, ',', '.');

    $html .= '<tr>' .
             '<td class="mono">' . ($i + 1) . '</td>' .
             '<td>' . htmlspecialchars($tgl) . '</td>' .
             '<td>' . htmlspecialchars($shift) . '</td>' .
             '<td class="mono">' . htmlspecialchars($mid) . '</td>' .
             '<td class="mono">' . htmlspecialchars($tid) . '</td>' .
             '<td>' . htmlspecialchars($jenis) . '</td>' .
             '<td>' . htmlspecialchars($tipe) . '</td>' .
             '<td class="right">' . $liter . '</td>' .
             '<td class="right">' . $harga . '</td>' .
             '<td class="right">' . $total . '</td>' .
             '</tr>';
}

$html .= '</tbody></table>';
$html .= '<div class="muted">Catatan: Beberapa kolom (Liter/Harga) mungkin tidak tersedia jika data bersumber dari database.</div>';
$html .= '</body></html>';

$mpdf = new Mpdf(['format' => 'A4-L']);
$mpdf->WriteHTML($html);
$mpdf->Output('detail_transaksi_import.pdf', 'I');
exit;
?>


