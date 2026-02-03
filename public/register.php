<?php
require_once '../includes/db.php';
require_once '../includes/validation.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = validate_register($_POST);
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$_POST['email']]);
        if ($stmt->fetch()) {
            $errors[] = "Email sudah terdaftar";
        } else {
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role = isset($_POST['role']) && $_POST['role'] === 'kepala_spbu' ? 'kepala_spbu' : 'admin';
            $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['nama'], $_POST['email'], $hash, $role]);
            header('Location: login.php?register=success');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register Admin</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="bg-gradient-to-br from-blue-100 to-blue-300 flex items-center justify-center min-h-screen">
    <form class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md card-3d transition-transform duration-300" method="post">
        <div class="flex flex-col items-center mb-6">
            <div class="bg-blue-100 rounded-full p-3 mb-2 shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            </div>
            <h2 class="text-3xl font-extrabold text-blue-700 tracking-tight">Register Admin</h2>
            <p class="text-gray-500 text-sm mt-1">Buat akun admin baru untuk aplikasi BBM</p>
        </div>
        <?php if ($errors): ?>
            <div class="mb-4 text-red-600 bg-red-50 rounded p-2 text-sm">
                <ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
            </div>
        <?php endif; ?>
        <input name="nama" type="text" placeholder="Nama" class="input input-bordered w-full mb-3 px-4 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-400 focus:outline-none" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
        <input name="email" type="email" placeholder="Email" class="input input-bordered w-full mb-3 px-4 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-400 focus:outline-none" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <input name="password" type="password" placeholder="Password" class="input input-bordered w-full mb-3 px-4 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-400 focus:outline-none">
        <div class="mb-3">
    <label class="block mb-1 font-semibold">Role</label>
    <select name="role" class="w-full px-4 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-400">
        <option value="admin" <?= (($_POST['role'] ?? '') === 'admin' ? 'selected' : '') ?>>Admin</option>
        <option value="kepala_spbu" <?= (($_POST['role'] ?? '') === 'kepala_spbu' ? 'selected' : '') ?>>Kepala SPBU</option>
    </select>
</div>
        <input name="confirm_password" type="password" placeholder="Konfirmasi Password" class="input input-bordered w-full mb-3 px-4 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-400 focus:outline-none">
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded shadow transition-all duration-200 mt-2">Register</button>
        <p class="mt-4 text-center text-sm">Sudah punya akun? <a href="login.php" class="text-blue-600 hover:underline">Login</a></p>
    </form>
</body>
</html> 