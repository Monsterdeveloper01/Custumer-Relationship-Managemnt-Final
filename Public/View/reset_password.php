<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Pastikan user sudah login
require_login();
$mid = current_marketing_id();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) {
        $message = "Password baru dan konfirmasi tidak sama.";
    } else {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE marketing_id = ?");
        $stmt->execute([$mid]);
        $row = $stmt->fetch();

        if ($row && password_verify($old, $row['password'])) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET password = ? WHERE marketing_id = ?");
            $upd->execute([$hash, $mid]);
            $message = "Password berhasil diubah.";
        } else {
            $message = "Password lama salah.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Reset Password | IT Consultant CRM</title>
  <link href="css/output.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-r from-blue-50 to-blue-100 min-h-screen flex items-center justify-center">

  <div class="w-full max-w-md bg-white shadow-lg rounded-2xl p-8">

    <!-- Tombol Back -->
    <div class="mb-4">
      <a href="settings.php"
         class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
        ← Kembali ke Settings
      </a>
    </div>

    <div class="text-center mb-6">
      <h1 class="text-2xl font-bold text-blue-700">Pengaturan Akun</h1>
      <p class="text-gray-500 text-sm">Ubah password akun CRM Marketing Anda</p>
    </div>

    <?php if ($message): ?>
      <div class="mb-4 text-sm text-blue-700 bg-blue-50 border border-blue-200 rounded-lg p-3">
        <?= h($message) ?>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-5">
      <input type="hidden" name="reset_password" value="1">

      <div>
        <label class="block text-sm font-medium text-gray-700">Password Lama</label>
        <input type="password" name="old_password" required
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
        Simpan Password Baru
      </button>
    </form>

    <p class="mt-6 text-xs text-gray-400 text-center">© <?= date('Y') ?> IT Consultant CRM</p>
  </div>

</body>
</html>
