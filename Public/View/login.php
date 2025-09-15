<?php
// login.php
session_start();

require_once __DIR__ . '/../backend_secure/Model/db.php';
require_once __DIR__ . '/../backend_secure/functions.php';

$err = '';
$marketing_id_post = $_POST['marketing_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $marketing_id = strtoupper(trim($marketing_id_post));
  $password = $_POST['password'] ?? '';

  $stmt = $pdo->prepare("SELECT marketing_id, name, email, password, 'user' AS source 
                       FROM users WHERE marketing_id = ?");
  $stmt->execute([$marketing_id]);
  $user = $stmt->fetch();

  // kalau belum ketemu, cek di individual_promocodes
  if (!$user) {
    $stmt = $pdo->prepare("SELECT marketing_id, nama_lengkap AS name, email, password, 'individual' AS source 
                           FROM individual_promocodes WHERE marketing_id = ?");
    $stmt->execute([$marketing_id]);
    $user = $stmt->fetch();
  }

  // kalau masih belum ketemu, cek di institusi_partner
  if (!$user) {
    $stmt = $pdo->prepare("SELECT marketing_id, nama_partner AS name, email, password, 'institution' AS source 
                           FROM institusi_partner WHERE marketing_id = ?");
    $stmt->execute([$marketing_id]);
    $user = $stmt->fetch();
  }

  if ($user && password_verify($password, $user['password'])) {
    // sukses login
    $_SESSION['user'] = [
      'marketing_id' => $user['marketing_id'],
      'name' => $user['name'],
      'email' => $user['email']
    ];
    header('Location: dashboard.php');
    exit;
  } else {
    $err = "Marketing ID atau password salah.";
  }
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Login CRM | IT Consultant</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="../../Public/assets/css/output.css" rel="stylesheet">
</head>

<body class="bg-gradient-to-r from-blue-50 to-blue-100 min-h-screen flex items-center justify-center">

  <div class="w-full max-w-md bg-white shadow-lg rounded-2xl p-8">
    <div class="text-center mb-6">
      <h1 class="text-2xl font-bold text-blue-700">CRM Login</h1>
      <p class="text-gray-500 text-sm">Silakan login untuk mengakses dashboard Marketing</p>
    </div>

    <?php if ($err): ?>
      <div class="mb-4 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg p-3">
        <?= h($err) ?>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-5">
      <div>
        <label for="marketing_id" class="block text-sm font-medium text-gray-700">Marketing ID</label>
        <input id="marketing_id" name="marketing_id"
          value="<?= h($marketing_id_post) ?>"
          required
          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-800" />
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
        <input type="password" id="password" name="password" required
          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-800" />
      </div>

      <!-- tombol login -->
      <button type="submit"
        class="w-full py-2 px-4 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
        Login
      </button>

      <!-- tombol ke dashboard lain -->
      <a href="../View/Login/login.php"
        class="w-full block text-center py-2 px-4 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition">
        Login ke List CRM Dashboard
      </a>
    </form>


    <!-- Link reset password -->
    <div class="mt-4 text-center">
      <a href="forgot_password.php" class="text-sm text-blue-600 hover:text-blue-800">
        Lupa password?
      </a>
    </div>

    <p class="mt-6 text-xs text-gray-400 text-center">© <?= date('Y') ?> IT Consultant CRM</p>
  </div>

</body>

</html>