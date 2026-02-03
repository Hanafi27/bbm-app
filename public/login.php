<?php
require_once '../includes/db.php';
require_once '../includes/validation.php';
session_start();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = validate_login($_POST);
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$_POST['email']]);
        $user = $stmt->fetch();
        if ($user && password_verify($_POST['password'], $user['password'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = "Email atau password salah";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Admin</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="bg-gradient-to-br from-blue-100 to-blue-300 flex items-center justify-center min-h-screen">
    <form class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md card-3d transition-transform duration-300" method="post">
        <div class="flex flex-col items-center mb-6">
            <div class="bg-blue-100 rounded-full p-3 mb-2 shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-1.657-1.343-3-3-3s-3 1.343-3 3 1.343 3 3 3 3-1.343 3-3zm0 0c0 1.657 1.343 3 3 3s3-1.343 3-3-1.343-3-3-3-3 1.343-3 3zm0 0v2m0 4h.01" /></svg>
            </div>
            <h2 class="text-3xl font-extrabold text-blue-700 tracking-tight">Login Admin</h2>
            <p class="text-gray-500 text-sm mt-1">Silakan masuk untuk mengelola aplikasi BBM</p>
        </div>
        <?php if ($errors): ?>
            <div class="mb-4 text-red-600 bg-red-50 rounded p-2 text-sm">
                <ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
            </div>
        <?php endif; ?>
        <input name="email" type="email" placeholder="Email" class="input input-bordered w-full mb-3 px-4 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-400 focus:outline-none" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <input name="password" type="password" placeholder="Password" class="input input-bordered w-full mb-3 px-4 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-400 focus:outline-none">
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded shadow transition-all duration-200 mt-2">Login</button>
        <p class="mt-4 text-center text-sm">Belum punya akun? <a href="register.php" class="text-blue-600 hover:underline">Register</a></p>
    </form>
</body>
</html> 