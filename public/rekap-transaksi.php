<?php
require_once '../includes/db.php';
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
// Filter
$mode = $_GET['mode'] ?? 'harian';
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');
$year = $_GET['year'] ?? date('Y');
$bulan = $_GET['bulan'] ?? '';
$role = $_SESSION['role'] ?? 'admin';

// Query untuk total keseluruhan (hanya untuk user yang sedang login)
if ($mode === 'bulanan') {
    if ($bulan && $bulan >= 1 && $bulan <= 12) {
        if ($role === 'kepala_spbu') {
            $stmt = $pdo->prepare("SELECT SUM(amount) as total_keseluruhan, COUNT(*) as total_transaksi FROM transaksi WHERE YEAR(tanggal)=? AND MONTH(tanggal)=?");
            $stmt->execute([$year, $bulan]);
        } else {
            $stmt = $pdo->prepare("SELECT SUM(amount) as total_keseluruhan, COUNT(*) as total_transaksi FROM transaksi WHERE YEAR(tanggal)=? AND MONTH(tanggal)=? AND admin_id=?");
            $stmt->execute([$year, $bulan, $_SESSION['admin_id']]);
        }
    } else {
        if ($role === 'kepala_spbu') {
            $stmt = $pdo->prepare("SELECT SUM(amount) as total_keseluruhan, COUNT(*) as total_transaksi FROM transaksi WHERE YEAR(tanggal)=?");
            $stmt->execute([$year]);
        } else {
            $stmt = $pdo->prepare("SELECT SUM(amount) as total_keseluruhan, COUNT(*) as total_transaksi FROM transaksi WHERE YEAR(tanggal)=? AND admin_id=?");
            $stmt->execute([$year, $_SESSION['admin_id']]);
        }
    }
    $total_keseluruhan = $stmt->fetch();
    
    // Query untuk breakdown per jenis BBM
    if ($bulan && $bulan >= 1 && $bulan <= 12) {
        if ($role === 'kepala_spbu') {
            $stmt = $pdo->prepare("SELECT jenis_bbm, SUM(amount) as total, COUNT(*) as jumlah FROM transaksi WHERE YEAR(tanggal)=? AND MONTH(tanggal)=? GROUP BY jenis_bbm ORDER BY total DESC");
            $stmt->execute([$year, $bulan]);
        } else {
            $stmt = $pdo->prepare("SELECT jenis_bbm, SUM(amount) as total, COUNT(*) as jumlah FROM transaksi WHERE YEAR(tanggal)=? AND MONTH(tanggal)=? AND admin_id=? GROUP BY jenis_bbm ORDER BY total DESC");
            $stmt->execute([$year, $bulan, $_SESSION['admin_id']]);
        }
    } else {
        if ($role === 'kepala_spbu') {
            $stmt = $pdo->prepare("SELECT jenis_bbm, SUM(amount) as total, COUNT(*) as jumlah FROM transaksi WHERE YEAR(tanggal)=? GROUP BY jenis_bbm ORDER BY total DESC");
            $stmt->execute([$year]);
        } else {
            $stmt = $pdo->prepare("SELECT jenis_bbm, SUM(amount) as total, COUNT(*) as jumlah FROM transaksi WHERE YEAR(tanggal)=? AND admin_id=? GROUP BY jenis_bbm ORDER BY total DESC");
            $stmt->execute([$year, $_SESSION['admin_id']]);
        }
    }
    $breakdown_bbm = $stmt->fetchAll();
    
    // Query untuk detail per bulan dan jenis BBM
    if ($bulan && $bulan >= 1 && $bulan <= 12) {
        if ($role === 'kepala_spbu') {
            $stmt = $pdo->prepare("SELECT YEAR(tanggal) as tahun, MONTH(tanggal) as bulan, jenis_bbm, SUM(amount) as total, COUNT(*) as jumlah FROM transaksi WHERE YEAR(tanggal)=? AND MONTH(tanggal)=? GROUP BY tahun, bulan, jenis_bbm ORDER BY tahun DESC, bulan DESC, jenis_bbm");
            $stmt->execute([$year, $bulan]);
        } else {
            $stmt = $pdo->prepare("SELECT YEAR(tanggal) as tahun, MONTH(tanggal) as bulan, jenis_bbm, SUM(amount) as total, COUNT(*) as jumlah FROM transaksi WHERE YEAR(tanggal)=? AND MONTH(tanggal)=? AND admin_id=? GROUP BY tahun, bulan, jenis_bbm ORDER BY tahun DESC, bulan DESC, jenis_bbm");
            $stmt->execute([$year, $bulan, $_SESSION['admin_id']]);
        }
    } else {
        if ($role === 'kepala_spbu') {
            $stmt = $pdo->prepare("SELECT YEAR(tanggal) as tahun, MONTH(tanggal) as bulan, jenis_bbm, SUM(amount) as total, COUNT(*) as jumlah FROM transaksi WHERE YEAR(tanggal)=? GROUP BY tahun, bulan, jenis_bbm ORDER BY tahun DESC, bulan DESC, jenis_bbm");
            $stmt->execute([$year]);
        } else {
            $stmt = $pdo->prepare("SELECT YEAR(tanggal) as tahun, MONTH(tanggal) as bulan, jenis_bbm, SUM(amount) as total, COUNT(*) as jumlah FROM transaksi WHERE YEAR(tanggal)=? AND admin_id=? GROUP BY tahun, bulan, jenis_bbm ORDER BY tahun DESC, bulan DESC, jenis_bbm");
            $stmt->execute([$year, $_SESSION['admin_id']]);
        }
    }
    $rekap = $stmt->fetchAll();
} else {
    if ($role === 'kepala_spbu') {
        $stmt = $pdo->prepare("SELECT SUM(amount) as total_keseluruhan, COUNT(*) as total_transaksi FROM transaksi WHERE tanggal BETWEEN ? AND ?");
        $stmt->execute([$start, $end]);
    } else {
        $stmt = $pdo->prepare("SELECT SUM(amount) as total_keseluruhan, COUNT(*) as total_transaksi FROM transaksi WHERE tanggal BETWEEN ? AND ? AND admin_id=?");
        $stmt->execute([$start, $end, $_SESSION['admin_id']]);
    }
    $total_keseluruhan = $stmt->fetch();
    
    // Query untuk breakdown per jenis BBM
    if ($role === 'kepala_spbu') {
        $stmt = $pdo->prepare("SELECT jenis_bbm, SUM(amount) as total, COUNT(*) as jumlah FROM transaksi WHERE tanggal BETWEEN ? AND ? GROUP BY jenis_bbm ORDER BY total DESC");
        $stmt->execute([$start, $end]);
    } else {
        $stmt = $pdo->prepare("SELECT jenis_bbm, SUM(amount) as total, COUNT(*) as jumlah FROM transaksi WHERE tanggal BETWEEN ? AND ? AND admin_id=? GROUP BY jenis_bbm ORDER BY total DESC");
        $stmt->execute([$start, $end, $_SESSION['admin_id']]);
    }
    $breakdown_bbm = $stmt->fetchAll();
    
    // Query untuk detail per tanggal dan jenis BBM
    if ($role === 'kepala_spbu') {
        $stmt = $pdo->prepare("SELECT tanggal, jenis_bbm, SUM(amount) as total, COUNT(*) as jumlah FROM transaksi WHERE tanggal BETWEEN ? AND ? GROUP BY tanggal, jenis_bbm ORDER BY tanggal DESC, jenis_bbm");
        $stmt->execute([$start, $end]);
    } else {
        $stmt = $pdo->prepare("SELECT tanggal, jenis_bbm, SUM(amount) as total, COUNT(*) as jumlah FROM transaksi WHERE tanggal BETWEEN ? AND ? AND admin_id=? GROUP BY tanggal, jenis_bbm ORDER BY tanggal DESC, jenis_bbm");
        $stmt->execute([$start, $end, $_SESSION['admin_id']]);
    }
    $rekap = $stmt->fetchAll();
}
$success = isset($_GET['success']) ? $_GET['success'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Transaksi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
      .pertamina-blue { color: #0099da; }
      .pertamina-green { color: #43b02a; }
      .pertamina-red { color: #ed1c24; }
      .bg-pertamina-blue { background-color: #0099da; }
      .bg-pertamina-green { background-color: #43b02a; }
      .bg-pertamina-light { background-color: #f6fbfd; }
      .border-pertamina-blue { border-color: #0099da; }
      .border-pertamina-light { border-color: #e3f1fa; }
    </style>
</head>
<body class="bg-pertamina-light min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <?php include '../includes/sidebar.php'; ?>
        <div class="flex-1 flex flex-col h-screen overflow-hidden bg-pertamina-light">
            <!-- Navbar -->
            <nav class="bg-white shadow-md py-3 px-4 md:px-8 flex items-center justify-between border-b-2 border-pertamina-blue flex-shrink-0 z-10">
                <button class="md:hidden text-pertamina-blue focus:outline-none" onclick="toggleSidebar()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <div class="flex items-center gap-3 ml-auto">
                    <span class="font-semibold text-pertamina-blue">Rekap Transaksi</span>
                </div>
            </nav>
            <main class="flex-1 flex flex-col p-4 md:p-8 overflow-auto">
                <div class="w-full space-y-6">
                    <!-- Filter Section -->
                    <div class="bg-white rounded-xl shadow-lg p-4 border border-pertamina-light">
                        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-4">
                            <div class="flex gap-2">
                                <a href="?mode=harian" class="px-4 py-2 rounded font-semibold border transition <?= $mode==='harian' ? 'bg-pertamina-blue text-white border-pertamina-blue' : 'bg-white text-pertamina-blue border-pertamina-blue hover:bg-pertamina-blue/10' ?>">Harian</a>
                                <a href="?mode=bulanan" class="px-4 py-2 rounded font-semibold border transition <?= $mode==='bulanan' ? 'bg-pertamina-blue text-white border-pertamina-blue' : 'bg-white text-pertamina-blue border-pertamina-blue hover:bg-pertamina-blue/10' ?>">Bulanan</a>
                            </div>
                        </div>
                        <form method="get" class="flex flex-wrap gap-3 items-end">
                            <input type="hidden" name="mode" value="<?= htmlspecialchars($mode) ?>">
                            <?php if ($mode==='harian'): ?>
                                <div>
                                    <label class="block text-xs font-semibold mb-1">Dari Tanggal</label>
                                    <input type="date" name="start" value="<?= htmlspecialchars($start) ?>" class="px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-pertamina-blue">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold mb-1">Sampai Tanggal</label>
                                    <input type="date" name="end" value="<?= htmlspecialchars($end) ?>" class="px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-pertamina-blue">
                                </div>
                            <?php else: ?>
                                <div>
                                    <label class="block text-xs font-semibold mb-1">Tahun</label>
                                    <select name="year" class="px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-pertamina-blue">
                                        <?php for($y=date('Y');$y>=2020;$y--): ?>
                                            <option value="<?= $y ?>" <?= $year==$y?'selected':'' ?>><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold mb-1">Bulan</label>
                                    <select name="bulan" class="px-3 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-pertamina-blue">
                                        <option value="">Semua Bulan</option>
                                        <?php for($b=1;$b<=12;$b++): ?>
                                            <option value="<?= $b ?>" <?= $bulan==$b?'selected':'' ?>><?= date('F', mktime(0,0,0,$b,1)) ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <button type="submit" class="bg-pertamina-blue hover:bg-pertamina-green text-white font-semibold px-4 py-2 rounded shadow transition">Tampilkan</button>
                            <a href="rekap-export.php?mode=<?= $mode ?>&start=<?= $start ?>&end=<?= $end ?>&year=<?= $year ?>&type=excel" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded shadow transition">Download Excel</a>
                            <a href="rekap-export.php?mode=<?= $mode ?>&start=<?= $start ?>&end=<?= $end ?>&year=<?= $year ?>&bulan=<?= $bulan ?>&type=pdf" class="bg-pertamina-blue hover:bg-pertamina-red text-white font-semibold px-4 py-2 rounded shadow transition flex items-center gap-2">
                                <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 4v16m8-8H4' /></svg>
                                Download PDF
                            </a>
                        </form>
                    </div>

                    <!-- Total Keseluruhan -->
                    <div class="bg-white rounded-xl shadow-lg p-6 border border-pertamina-light">
                        <h2 class="text-xl font-bold pertamina-blue mb-4">Total Penjualan Keseluruhan</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-pertamina-blue/10 p-4 rounded-lg border border-pertamina-blue/20">
                                <div class="text-sm text-gray-600 mb-1">Total Penjualan</div>
                                <div class="text-2xl font-bold pertamina-blue">Rp <?= number_format($total_keseluruhan['total_keseluruhan'] ?? 0, 0, ',', '.') ?></div>
                            </div>
                            <div class="bg-pertamina-green/10 p-4 rounded-lg border border-pertamina-green/20">
                                <div class="text-sm text-gray-600 mb-1">Total Transaksi</div>
                                <div class="text-2xl font-bold pertamina-green"><?= number_format($total_keseluruhan['total_transaksi'] ?? 0, 0, ',', '.') ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Breakdown per Jenis BBM -->
                    <div class="bg-white rounded-xl shadow-lg p-6 border border-pertamina-light">
                        <h2 class="text-xl font-bold pertamina-blue mb-4">Penjualan per Jenis BBM</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <?php foreach ($breakdown_bbm as $bbm): ?>
                                <div class="bg-pertamina-light p-4 rounded-lg border border-pertamina-light">
                                    <div class="text-sm text-gray-600 mb-1"><?= htmlspecialchars($bbm['jenis_bbm']) ?></div>
                                    <div class="text-lg font-bold pertamina-blue mb-1">Rp <?= number_format($bbm['total'], 0, ',', '.') ?></div>
                                    <div class="text-sm text-gray-500"><?= number_format($bbm['jumlah'], 0, ',', '.') ?> transaksi</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Tabel Detail -->
                    <div class="bg-white rounded-xl shadow-lg p-0 border border-pertamina-light overflow-x-auto">
                        <div class="p-4 border-b border-pertamina-light">
                            <h2 class="text-xl font-bold pertamina-blue">Detail Transaksi per <?= $mode==='harian' ? 'Tanggal' : 'Bulan' ?></h2>
                        </div>
                        <table class="w-full text-sm text-left">
                            <thead class="sticky top-0 z-10 bg-pertamina-blue/10">
                                <tr class="pertamina-blue border-b font-semibold text-base">
                                    <?php if ($mode==='harian'): ?>
                                        <th class="py-3 px-4">Tanggal</th>
                                    <?php else: ?>
                                        <th class="py-3 px-4">Bulan</th>
                                        <th class="py-3 px-4">Tahun</th>
                                    <?php endif; ?>
                                    <th class="py-3 px-4">Jenis BBM</th>
                                    <th class="py-3 px-4">Total Penjualan</th>
                                    <th class="py-3 px-4">Jumlah Transaksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($rekap) === 0): ?>
                                    <tr><td colspan="<?= $mode==='harian'?4:5 ?>" class="py-6 text-center text-gray-400">Tidak ada data</td></tr>
                                <?php else: ?>
                                    <?php foreach ($rekap as $r): ?>
                                        <tr class="border-b last:border-0 even:bg-pertamina-light/60 hover:bg-pertamina-blue/5 transition">
                                            <?php if ($mode==='harian'): ?>
                                                <td class="py-3 px-4 font-semibold text-gray-700"><?= htmlspecialchars($r['tanggal']) ?></td>
                                            <?php else: ?>
                                                <td class="py-3 px-4 font-semibold text-gray-700"><?= date('F', mktime(0,0,0,$r['bulan'],1)) ?></td>
                                                <td class="py-3 px-4 font-semibold text-gray-700"><?= $r['tahun'] ?></td>
                                            <?php endif; ?>
                                            <td class="py-3 px-4 text-gray-700"><?= htmlspecialchars($r['jenis_bbm']) ?></td>
                                            <td class="py-3 px-4 text-gray-700">Rp <?= number_format($r['total'],0,',','.') ?></td>
                                            <td class="py-3 px-4 text-gray-700"><?= $r['jumlah'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html> 