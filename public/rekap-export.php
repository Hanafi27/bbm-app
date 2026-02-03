<?php
session_start();
if (ob_get_level()) ob_end_clean();
require_once '../includes/db.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!isset($_SESSION['admin_id'])) {
    die('Unauthorized');
}

$mode = $_GET['mode'] ?? 'harian';
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');
$year = $_GET['year'] ?? date('Y');
$bulan = $_GET['bulan'] ?? '';
$type = $_GET['type'] ?? 'excel';

// Helper: nama bulan Indonesia dan formatter tanggal ke d-Bulan-YYYY
$__bulan_indo = [
	1 => 'Januari',
	2 => 'Februari',
	3 => 'Maret',
	4 => 'April',
	5 => 'Mei',
	6 => 'Juni',
	7 => 'Juli',
	8 => 'Agustus',
	9 => 'September',
	10 => 'Oktober',
	11 => 'November',
	12 => 'Desember'
];

function format_tanggal_indo($tanggalYmd) {
	global $__bulan_indo;
	$ts = strtotime($tanggalYmd);
	if ($ts === false) return htmlspecialchars($tanggalYmd);
	$hari = date('d', $ts);
	$bulan = (int)date('n', $ts);
	$tahun = date('Y', $ts);
	return $hari . '-' . ($__bulan_indo[$bulan] ?? date('m', $ts)) . '-' . $tahun;
}

// Query untuk total keseluruhan
if ($mode === 'bulanan') {
    if ($bulan && $bulan >= 1 && $bulan <= 12) {
        $stmt = $pdo->prepare("SELECT SUM(amount) as total_keseluruhan, COUNT(*) as total_transaksi FROM transaksi WHERE YEAR(tanggal)=? AND MONTH(tanggal)=?");
        $stmt->execute([$year, $bulan]);
    } else {
        $stmt = $pdo->prepare("SELECT SUM(amount) as total_keseluruhan, COUNT(*) as total_transaksi FROM transaksi WHERE YEAR(tanggal)=?");
        $stmt->execute([$year]);
    }
    $total_keseluruhan = $stmt->fetch();
    
    // Query untuk breakdown per jenis BBM
    if ($bulan && $bulan >= 1 && $bulan <= 12) {
        $stmt = $pdo->prepare("SELECT jenis_bbm, SUM(amount) as total, COUNT(*) as jumlah FROM transaksi WHERE YEAR(tanggal)=? AND MONTH(tanggal)=? GROUP BY jenis_bbm ORDER BY total DESC");
        $stmt->execute([$year, $bulan]);
    } else {
        $stmt = $pdo->prepare("SELECT jenis_bbm, SUM(amount) as total, COUNT(*) as jumlah FROM transaksi WHERE YEAR(tanggal)=? GROUP BY jenis_bbm ORDER BY total DESC");
        $stmt->execute([$year]);
    }
    $breakdown_bbm = $stmt->fetchAll();
    
    // Query untuk detail per bulan dan jenis BBM
    if ($bulan && $bulan >= 1 && $bulan <= 12) {
        $stmt = $pdo->prepare("SELECT YEAR(tanggal) as tahun, MONTH(tanggal) as bulan, jenis_bbm, SUM(amount) as total, COUNT(*) as jumlah FROM transaksi WHERE YEAR(tanggal)=? AND MONTH(tanggal)=? GROUP BY tahun, bulan, jenis_bbm ORDER BY tahun DESC, bulan DESC, jenis_bbm");
        $stmt->execute([$year, $bulan]);
    } else {
        $stmt = $pdo->prepare("SELECT YEAR(tanggal) as tahun, MONTH(tanggal) as bulan, jenis_bbm, SUM(amount) as total, COUNT(*) as jumlah FROM transaksi WHERE YEAR(tanggal)=? GROUP BY tahun, bulan, jenis_bbm ORDER BY tahun DESC, bulan DESC, jenis_bbm");
        $stmt->execute([$year]);
    }
    $rekap = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT SUM(amount) as total_keseluruhan, COUNT(*) as total_transaksi FROM transaksi WHERE tanggal BETWEEN ? AND ?");
    $stmt->execute([$start, $end]);
    $total_keseluruhan = $stmt->fetch();
    
    // Query untuk breakdown per jenis BBM
    $stmt = $pdo->prepare("SELECT jenis_bbm, SUM(amount) as total, COUNT(*) as jumlah FROM transaksi WHERE tanggal BETWEEN ? AND ? GROUP BY jenis_bbm ORDER BY total DESC");
    $stmt->execute([$start, $end]);
    $breakdown_bbm = $stmt->fetchAll();
    
    // Query untuk detail per tanggal dan jenis BBM
    $stmt = $pdo->prepare("SELECT tanggal, jenis_bbm, SUM(amount) as total, COUNT(*) as jumlah FROM transaksi WHERE tanggal BETWEEN ? AND ? GROUP BY tanggal, jenis_bbm ORDER BY tanggal DESC, jenis_bbm");
    $stmt->execute([$start, $end]);
    $rekap = $stmt->fetchAll();
}

if ($type === 'pdf') {
    require_once '../vendor/autoload.php';
    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
    $logoPath = realpath(__DIR__ . '/../assets/Pertamina_Logo.svg');
    $kop = '<div style="display: flex; align-items: center; margin-bottom: 16px;">
        <img src="' . $logoPath . '" width="80" style="margin-right: 20px;" />
        <div style="font-size: 18px; font-weight: bold; color: #082313;">
            SPBU Pertamina 34.40123<br/>
            <span style="font-size: 13px; font-weight: normal; color: #333;">Jl. Dr. Djundjunan No.118, Sukagalih, Kec. Sukajadi, Kota Bandung, Jawa Barat 40162</span>
        </div>
    </div>';
    $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #888; padding: 8px; text-align: left; }
        th { background: #e6f0fa; color: #082313; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 8px; color: #006cb8; }
        .summary { background: #f8f9fa; padding: 12px; margin: 16px 0; border-radius: 4px; }
        .summary-title { font-weight: bold; margin-bottom: 8px; color: #006cb8; }
        .summary-grid { display: table; width: 100%; }
        .summary-item { display: table-cell; width: 50%; padding: 8px; }
        .bbm-grid { display: table; width: 100%; margin-top: 8px; }
        .bbm-item { display: table-cell; width: 25%; padding: 4px; }
    </style></head><body>';
    $html .= $kop;
    $html .= '<div class="title">Rekapitulasi Transaksi ' . ($mode === 'harian' ? 'Harian' : 'Bulanan') . '</div>';
    
    // Periode
    if ($mode === 'harian') {
        $html .= '<div style="margin-bottom:8px;">Periode: ' . format_tanggal_indo($start) . ' s/d ' . format_tanggal_indo($end) . '</div>';
    } else {
        $html .= '<div style="margin-bottom:8px;">Tahun: ' . htmlspecialchars($year);
        if ($bulan && $bulan >= 1 && $bulan <= 12) {
            $html .= ', Bulan: ' . ($__bulan_indo[(int)$bulan] ?? $bulan);
        }
        $html .= '</div>';
    }
    
    // Total Keseluruhan
    $html .= '<div class="summary">
        <div class="summary-title">Total Penjualan Keseluruhan</div>
        <div class="summary-grid">
            <div class="summary-item">
                <strong>Total Penjualan:</strong><br/>
                Rp ' . number_format($total_keseluruhan['total_keseluruhan'] ?? 0, 0, ',', '.') . '
            </div>
            <div class="summary-item">
                <strong>Total Transaksi:</strong><br/>
                ' . number_format($total_keseluruhan['total_transaksi'] ?? 0, 0, ',', '.') . '
            </div>
        </div>
    </div>';
    
    // Breakdown per Jenis BBM
    $html .= '<div class="summary">
        <div class="summary-title">Penjualan per Jenis BBM</div>
        <div class="bbm-grid">';
    foreach ($breakdown_bbm as $bbm) {
        $html .= '<div class="bbm-item">
            <strong>' . htmlspecialchars($bbm['jenis_bbm']) . '</strong><br/>
            Rp ' . number_format($bbm['total'], 0, ',', '.') . '<br/>
            <small>' . number_format($bbm['jumlah'], 0, ',', '.') . ' transaksi</small>
        </div>';
    }
    $html .= '</div></div>';
    
    // Tabel Detail
    $html .= '<div style="margin-top: 16px;">
        <div style="font-weight: bold; margin-bottom: 8px; color: #006cb8;">Detail Transaksi per ' . ($mode === 'harian' ? 'Tanggal' : 'Bulan') . '</div>';
    if ($mode === 'harian') {
        $html .= '<table><thead><tr><th>Tanggal</th><th>Jenis BBM</th><th>Total Penjualan</th><th>Jumlah Transaksi</th></tr></thead><tbody>';
        foreach ($rekap as $r) {
            $html .= '<tr><td>' . format_tanggal_indo($r['tanggal']) . '</td><td>' . htmlspecialchars($r['jenis_bbm']) . '</td><td>' . number_format($r['total'],0,',','.') . '</td><td>' . $r['jumlah'] . '</td></tr>';
        }
        $html .= '</tbody></table>';
    } else {
        $html .= '<table><thead><tr><th>Bulan</th><th>Tahun</th><th>Jenis BBM</th><th>Total Penjualan</th><th>Jumlah Transaksi</th></tr></thead><tbody>';
        foreach ($rekap as $r) {
            $bulanNama = $__bulan_indo[(int)$r['bulan']] ?? $r['bulan'];
            $html .= '<tr><td>' . $bulanNama . '</td><td>' . $r['tahun'] . '</td><td>' . htmlspecialchars($r['jenis_bbm']) . '</td><td>' . number_format($r['total'],0,',','.') . '</td><td>' . $r['jumlah'] . '</td></tr>';
        }
        $html .= '</tbody></table>';
    }
    $html .= '</div></body></html>';
    
    $filename = 'rekap-transaksi';
    if ($mode === 'harian') {
        $filename .= '-harian-' . $start . '_sd_' . $end;
    } else {
        if ($bulan && $bulan >= 1 && $bulan <= 12) {
            $filename .= '-bulanan-' . $year . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);
        } else {
            $filename .= '-bulanan-' . $year;
        }
    }
    $filename .= '.pdf';
    $mpdf->WriteHTML($html);
    $mpdf->Output($filename, 'D');
    exit;
} else {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Judul
    $sheet->setCellValue('A1', 'REKAPITULASI TRANSAKSI ' . strtoupper($mode === 'harian' ? 'HARIAN' : 'BULANAN'));
    $sheet->mergeCells('A1:E1');
    
    // Periode
    $row = 3;
    if ($mode === 'harian') {
        $sheet->setCellValue('A' . $row, 'Periode: ' . format_tanggal_indo($start) . ' s/d ' . format_tanggal_indo($end));
    } else {
        $sheet->setCellValue('A' . $row, 'Tahun: ' . $year);
        if ($bulan && $bulan >= 1 && $bulan <= 12) {
            $sheet->setCellValue('A' . $row, 'Tahun: ' . $year . ', Bulan: ' . ($__bulan_indo[(int)$bulan] ?? $bulan));
        }
    }
    
    // Total Keseluruhan
    $row += 2;
    $sheet->setCellValue('A' . $row, 'TOTAL PENJUALAN KESELURUHAN');
    $sheet->mergeCells('A' . $row . ':E' . $row);
    $row++;
    $sheet->setCellValue('A' . $row, 'Total Penjualan');
    $sheet->setCellValue('B' . $row, 'Total Transaksi');
    $row++;
    $sheet->setCellValue('A' . $row, $total_keseluruhan['total_keseluruhan'] ?? 0);
    $sheet->setCellValue('B' . $row, $total_keseluruhan['total_transaksi'] ?? 0);
    
    // Breakdown per Jenis BBM
    $row += 2;
    $sheet->setCellValue('A' . $row, 'PENJUALAN PER JENIS BBM');
    $sheet->mergeCells('A' . $row . ':E' . $row);
    $row++;
    $sheet->setCellValue('A' . $row, 'Jenis BBM');
    $sheet->setCellValue('B' . $row, 'Total Penjualan');
    $sheet->setCellValue('C' . $row, 'Jumlah Transaksi');
    $row++;
    foreach ($breakdown_bbm as $bbm) {
        $sheet->setCellValue('A' . $row, $bbm['jenis_bbm']);
        $sheet->setCellValue('B' . $row, $bbm['total']);
        $sheet->setCellValue('C' . $row, $bbm['jumlah']);
        $row++;
    }
    
    // Tabel Detail
    $row += 2;
    $sheet->setCellValue('A' . $row, 'DETAIL TRANSAKSI PER ' . strtoupper($mode === 'harian' ? 'TANGGAL' : 'BULAN'));
    $sheet->mergeCells('A' . $row . ':E' . $row);
    $row++;
    
    // Header
    if ($mode === 'harian') {
        $sheet->setCellValue('A' . $row, 'Tanggal');
        $sheet->setCellValue('B' . $row, 'Jenis BBM');
        $sheet->setCellValue('C' . $row, 'Total Penjualan');
        $sheet->setCellValue('D' . $row, 'Jumlah Transaksi');
        $row++;
        foreach ($rekap as $r) {
            $sheet->setCellValue('A' . $row, format_tanggal_indo($r['tanggal']));
            $sheet->setCellValue('B' . $row, $r['jenis_bbm']);
            $sheet->setCellValue('C' . $row, $r['total']);
            $sheet->setCellValue('D' . $row, $r['jumlah']);
            $row++;
        }
    } else {
        $sheet->setCellValue('A' . $row, 'Bulan');
        $sheet->setCellValue('B' . $row, 'Tahun');
        $sheet->setCellValue('C' . $row, 'Jenis BBM');
        $sheet->setCellValue('D' . $row, 'Total Penjualan');
        $sheet->setCellValue('E' . $row, 'Jumlah Transaksi');
        $row++;
        foreach ($rekap as $r) {
            $sheet->setCellValue('A' . $row, ($__bulan_indo[(int)$r['bulan']] ?? $r['bulan']));
            $sheet->setCellValue('B' . $row, $r['tahun']);
            $sheet->setCellValue('C' . $row, $r['jenis_bbm']);
            $sheet->setCellValue('D' . $row, $r['total']);
            $sheet->setCellValue('E' . $row, $r['jumlah']);
            $row++;
        }
    }
    
    $filename = 'rekap-transaksi';
    if ($mode === 'harian') {
        $filename .= '-harian-' . $start . '_sd_' . $end;
    } else {
        if ($bulan && $bulan >= 1 && $bulan <= 12) {
            $filename .= '-bulanan-' . $year . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);
        } else {
            $filename .= '-bulanan-' . $year;
        }
    }
    $filename .= '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} 