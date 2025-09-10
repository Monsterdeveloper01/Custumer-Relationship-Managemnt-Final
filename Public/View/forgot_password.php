<?php
// forgot_password.php
require_once __DIR__ . '/../Model/db.php';
require_once __DIR__ . '/../Controller/functions.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marketing_id = strtoupper(trim($_POST['marketing_id'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $message = "Password baru dan konfirmasi tidak sama.";
    } else {
        // Cek user berdasarkan marketing_id + email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE marketing_id = ? AND email = ?");
        $stmt->execute([$marketing_id, $email]);
        $user = $stmt->fetch();

        if ($user) {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET password = ? WHERE marketing_id = ?");
            $upd->execute([$hash, $marketing_id]);
            $message = "Password berhasil direset. Silakan login kembali.";
        } else {
            $message = "Data tidak cocok. Periksa Marketing ID dan Email Anda.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Lupa Password | IT Consultant CRM</title>
  <link href="css/output.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-r from-blue-50 to-blue-100 min-h-screen flex items-center justify-center">

  <div class="w-full max-w-md bg-white shadow-lg rounded-2xl p-8">

    <!-- Tombol Back -->
    <div class="mb-4">
      <a href="login.php"
         class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
        ← Kembali ke Login
      </a>
    </div>

    <div class="text-center mb-6">
      <h1 class="text-2xl font-bold text-blue-700">Lupa Password</h1>
      <p class="text-gray-500 text-sm">Masukkan Marketing ID dan Email untuk reset password</p>
    </div>

    <?php if ($message): ?>
      <div class="mb-4 text-sm text-blue-700 bg-blue-50 border border-blue-200 rounded-lg p-3">
        <?= h($message) ?>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-5">
      <div>
        <label class="block text-sm font-medium text-gray-700">Marketing ID</label>
        <input type="text" name="marketing_id" required
          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-800" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" required
          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-800" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Password Baru</label>
        <input type="password" name="new_password" required
          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-800" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
        <input type="password" name="confirm_password" required
          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-800" />
      </div>

      <button type="submit"
        class="w-full py-2 px-4 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
        Reset Password
      </button>
    </form>

    <p class="mt-6 text-xs text-gray-400 text-center">© <?= date('Y') ?> IT Consultant CRM</p>
  </div>

</body>
</html>
