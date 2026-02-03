<?php
require_once '../includes/db.php';
session_start();
if (!isset($_SESSION['admin_id']) || ($_SESSION['role'] ?? 'admin') === 'kepala_spbu') {
    header('Location: rekap-transaksi.php');
    exit;
}

// Tambah tipe
if (isset($_POST['tambah']) && !empty(trim($_POST['nama']))) {
    $stmt = $pdo->prepare("INSERT INTO tipe_transaksi (nama) VALUES (?)");
    $stmt->execute([trim($_POST['nama'])]);
    // Catat aktivitas
    $pdo->prepare("INSERT INTO aktivitas (aktivitas, admin_id) VALUES (?, ?)")
        ->execute(['Menambah tipe transaksi: ' . trim($_POST['nama']), $_SESSION['admin_id']]);
    header('Location: tipe-transaksi.php?success=tambah');
    exit;
}

// Edit tipe
if (isset($_POST['edit']) && isset($_POST['id']) && !empty(trim($_POST['nama']))) {
    $stmt = $pdo->prepare("UPDATE tipe_transaksi SET nama = ? WHERE id = ?");
    $stmt->execute([trim($_POST['nama']), $_POST['id']]);
    // Catat aktivitas
    $pdo->prepare("INSERT INTO aktivitas (aktivitas, admin_id) VALUES (?, ?)")
        ->execute(['Mengedit tipe transaksi: ' . trim($_POST['nama']), $_SESSION['admin_id']]);
    header('Location: tipe-transaksi.php?success=edit');
    exit;
}

// Hapus tipe
if (isset($_POST['hapus']) && isset($_POST['id'])) {
    $stmt = $pdo->prepare("DELETE FROM tipe_transaksi WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    // Catat aktivitas
    $pdo->prepare("INSERT INTO aktivitas (aktivitas, admin_id) VALUES (?, ?)")
        ->execute(['Menghapus tipe transaksi ID: ' . $_POST['id'], $_SESSION['admin_id']]);
    header('Location: tipe-transaksi.php?success=hapus');
    exit;
}

// Ambil data tipe transaksi
$tipe = $pdo->query("SELECT * FROM tipe_transaksi ORDER BY id DESC")->fetchAll();
?>
<?php
$success = isset($_GET['success']) ? $_GET['success'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tipe Transaksi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
      .modal-bg { background: rgba(0,0,0,0.3); }
      .modal-fade { transition: opacity 0.2s; }
      .pertamina-blue { color: #0099da; }
      .pertamina-green { color: #43b02a; }
      .pertamina-red { color: #ed1c24; }
      .pertamina-yellow { color: #ffd100; }
      .bg-pertamina-blue { background-color: #0099da; }
      .bg-pertamina-green { background-color: #43b02a; }
      .bg-pertamina-red { background-color: #ed1c24; }
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
                    <span class="font-semibold text-pertamina-blue">Tipe Transaksi</span>
                </div>
            </nav>
            <main class="flex-1 flex flex-col items-center justify-center p-4 md:p-8 overflow-auto">
                <div class="w-full space-y-8 px-2 md:px-8">
                    <div class="flex justify-between items-center">
                        <!-- Judul sudah di navbar -->
                        <a href="dashboard.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded shadow font-semibold transition-all border border-gray-200">&larr; Kembali</a>
                    </div>
                    <div class="relative bg-white rounded-xl shadow-lg p-6 border border-pertamina-light">
                        <!-- Tombol tambah tipe di dalam card, atas kiri -->
                        <div class="flex justify-start mb-2">
                            <button type="button" onclick="showTambahModal()" class="flex items-center gap-2 bg-pertamina-blue hover:bg-pertamina-green text-white px-4 py-2 rounded-lg shadow font-semibold transition border border-pertamina-blue focus:ring-2 focus:ring-pertamina-blue">
                                <svg xmlns='http://www.w3.org/2000/svg' class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Tambah Tipe
                            </button>
                        </div>
                        <table class="w-full text-sm text-left mt-2">
                            <thead>
                                <tr class="pertamina-blue border-b font-semibold">
                                    <th class="py-2 pr-2">#</th>
                                    <th class="py-2 pr-2">Nama Tipe</th>
                                    <th class="py-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($tipe) === 0): ?>
                                    <tr><td colspan="3" class="py-4 text-center text-gray-400">Belum ada tipe transaksi</td></tr>
                                <?php else: ?>
                                    <?php foreach ($tipe as $i => $t): ?>
                                        <tr class="border-b last:border-0 hover:bg-pertamina-light/60">
                                            <td class="py-2 pr-2"><?= $i+1 ?></td>
                                            <td class="py-2 pr-2" id="tipe-nama-<?= $t['id'] ?>"><?= htmlspecialchars($t['nama']) ?></td>
                                            <td class="py-2 flex gap-2">
                                                <button type="button" onclick="showEditModal(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['nama'])) ?>')" class="px-3 py-1 rounded font-semibold border border-pertamina-blue text-pertamina-blue bg-blue-50 hover:bg-pertamina-blue hover:text-white transition">Edit</button>
                                                <button type="button" onclick="showDeleteModal(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['nama'])) ?>')" class="px-3 py-1 rounded font-semibold border border-pertamina-red text-pertamina-red bg-white hover:bg-pertamina-red hover:text-white transition">Hapus</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <!-- Modal Tambah -->
            <div id="modal-tambah" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center">
                <div class="bg-white rounded-xl shadow-xl p-8 w-full max-w-xs modal-fade flex flex-col items-center border border-pertamina-light">
                    <h2 class="text-lg font-bold mb-4 pertamina-blue">Tambah Tipe Transaksi</h2>
                    <form method="post" class="w-full space-y-4">
                        <input type="text" name="nama" placeholder="Nama tipe transaksi (misal: BRI, Mandiri, BCA)" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-pertamina-blue" required>
                        <button type="submit" name="tambah" class="w-full bg-pertamina-blue hover:bg-pertamina-green text-white px-4 py-2 rounded font-semibold transition">Tambah</button>
                        <button type="button" onclick="closeTambahModal()" class="w-full mt-1 px-4 py-2 rounded border border-gray-300 text-gray-600 hover:bg-gray-100">Batal</button>
                    </form>
                </div>
            </div>
            <!-- Modal Edit -->
            <div id="modal-edit" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center">
                <div class="bg-white rounded-xl shadow-xl p-8 w-full max-w-xs modal-fade flex flex-col items-center border border-pertamina-light">
                    <h2 class="text-lg font-bold mb-4 pertamina-blue">Edit Tipe Transaksi</h2>
                    <form method="post" class="w-full space-y-4">
                        <input type="hidden" name="id" id="edit-id">
                        <input type="text" name="nama" id="edit-nama" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-pertamina-blue" required>
                        <div class="flex gap-2 w-full">
                            <button type="submit" name="edit" value="1" class="flex-1 bg-pertamina-blue hover:bg-pertamina-green text-white px-4 py-2 rounded font-semibold border border-pertamina-blue transition">Simpan</button>
                            <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 rounded border border-gray-300 text-gray-600 hover:bg-gray-100">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Modal Hapus -->
            <div id="modal-hapus" class="fixed inset-0 z-50 hidden modal-bg flex items-center justify-center">
                <div class="bg-white rounded-xl shadow-xl p-8 w-full max-w-xs modal-fade flex flex-col items-center border border-pertamina-light">
                    <h2 class="text-lg font-bold mb-4 pertamina-red">Hapus Tipe Transaksi</h2>
                    <form method="post" class="w-full space-y-4">
                        <input type="hidden" name="id" id="hapus-id">
                        <p class="mb-2">Yakin ingin menghapus tipe <span id="hapus-nama" class="font-semibold"></span>?</p>
                        <div class="flex gap-2 w-full">
                            <button type="submit" name="hapus" value="1" class="flex-1 bg-pertamina-red hover:bg-pertamina-blue text-white px-4 py-2 rounded font-semibold border border-pertamina-red transition">Hapus</button>
                            <button type="button" onclick="closeDeleteModal()" class="flex-1 px-4 py-2 rounded border border-gray-300 text-gray-600 hover:bg-gray-100">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php if ($success): ?>
    <div id="notif-toast" class="fixed top-6 left-1/2 -translate-x-1/2 z-50 px-6 py-3 rounded-xl shadow-lg text-white text-center text-base font-semibold animate-fade-in"
        style="background: <?=
            $success==='edit' ? 'linear-gradient(90deg,#0099da,#43b02a)' :
            ($success==='hapus' ? 'linear-gradient(90deg,#ed1c24,#0099da)' :
            'linear-gradient(90deg,#43b02a,#0099da)')
        ?>;min-width:220px;">
        <?= $success==='edit' ? 'Tipe transaksi berhasil diubah!' : ($success==='hapus' ? 'Tipe transaksi berhasil dihapus!' : 'Tipe transaksi berhasil ditambahkan!') ?>
    </div>
    <style>@keyframes fade-in{from{opacity:0;transform:translateY(-20px);}to{opacity:1;transform:translateY(0);}}</style>
    <script>setTimeout(()=>{const n=document.getElementById('notif-toast');if(n)n.style.display='none';},2500);</script>
    <?php endif; ?>
    <script>
      // Modal Tambah
      function showTambahModal() {
        document.getElementById('modal-tambah').classList.remove('hidden');
        setTimeout(() => document.getElementById('modal-tambah').classList.add('opacity-100'), 10);
      }
      function closeTambahModal() {
        document.getElementById('modal-tambah').classList.add('hidden');
        document.getElementById('modal-tambah').classList.remove('opacity-100');
      }
      // Modal Edit
      function showEditModal(id, nama) {
        document.getElementById('edit-id').value = id;
        document.getElementById('edit-nama').value = nama;
        document.getElementById('modal-edit').classList.remove('hidden');
        setTimeout(() => document.getElementById('modal-edit').classList.add('opacity-100'), 10);
      }
      function closeEditModal() {
        document.getElementById('modal-edit').classList.add('hidden');
        document.getElementById('modal-edit').classList.remove('opacity-100');
      }
      // Modal Hapus
      function showDeleteModal(id, nama) {
        document.getElementById('hapus-id').value = id;
        document.getElementById('hapus-nama').textContent = nama;
        document.getElementById('modal-hapus').classList.remove('hidden');
        setTimeout(() => document.getElementById('modal-hapus').classList.add('opacity-100'), 10);
      }
      function closeDeleteModal() {
        document.getElementById('modal-hapus').classList.add('hidden');
        document.getElementById('modal-hapus').classList.remove('opacity-100');
      }
      // Close modal on bg click
      document.getElementById('modal-tambah').addEventListener('click', function(e) { if (e.target === this) closeTambahModal(); });
      document.getElementById('modal-edit').addEventListener('click', function(e) { if (e.target === this) closeEditModal(); });
      document.getElementById('modal-hapus').addEventListener('click', function(e) { if (e.target === this) closeDeleteModal(); });
    </script>
</body>
</html> 