<?php
require '../../Model/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
        $account = null;

        // 1. Cari di users (ambil role juga)
        $stmt = $pdo->prepare("
            SELECT marketing_id, name, email, password, role, 'user' AS source
            FROM users
            WHERE email = :email
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Kalau belum ketemu → cek di partner_individual (role dari kolom role, default 'user' jika null)
        if (!$account) {
            $stmt = $pdo->prepare("
                SELECT promo_code AS marketing_id, nama_lengkap AS name, email, password, 
                       COALESCE(role, 'user') AS role, 'individual' AS source
                FROM partner_individual
                WHERE email = :email
                LIMIT 1
            ");
            $stmt->execute([':email' => $email]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // 3. Kalau masih belum ketemu → cek di partner_institution (role dari kolom role, default 'user' jika null)
        if (!$account) {
            $stmt = $pdo->prepare("
                SELECT kode_institusi AS marketing_id, nama_institusi AS name, email, password, 
                       COALESCE(role, 'user') AS role, 'institution' AS source
                FROM partner_institution
                WHERE email = :email
                LIMIT 1
            ");
            $stmt->execute([':email' => $email]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($account) {
            if (password_verify($password, $account['password'])) {
                $_SESSION['user'] = [
                    'marketing_id' => $account['marketing_id'],
                    'name'         => $account['name'],
                    'email'        => $account['email'],
                    'role'         => $account['role'],
                    'source'       => $account['source']
                ];

                // Redirect berdasarkan role
                if ($account['role'] === 'admin') {
                    header("Location: dashboard_admin.php");
                } else {
                    // Untuk role 'user' atau 'partner', arahkan ke dashboard biasa
                    header("Location: dashboard.php");
                }
                exit;
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "Email not registered!";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- penting untuk responsive -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css  " rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

  <!-- Background Video -->
  <div class="fixed inset-0 -z-10 overflow-hidden">
    <video
      class="min-w-full min-h-full w-auto h-auto object-cover filter blur-[8px] brightness-75"
      src="../../assets/vidio/background.mp4"
      autoplay
      loop
      muted
      playsinline>
    </video>
    <!-- Overlay hitam tipis supaya teks makin jelas -->
    <div class="absolute inset-0 bg-black/40"></div>
  </div>

  <!-- Header: putih solid -->
  <header class="bg-white shadow-md border-b border-gray-200 fixed top-0 w-full z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16 items-center">
        <div class="flex items-center">
          <img src="../../assets//img/rayterton-apps-software-logo.png"
               alt="Logo"
               class="h-10 w-auto sm:h-12"
               style="width: 175px; height: 50px;">
          <span class="ml-3 text-base sm:text-lg font-semibold text-gray-900 hidden md:block">
            Customer Relationship Management
          </span>
        </div>
      </div>
    </div>
  </header>

  <!-- Content -->
  <main class="relative z-40 flex-grow flex items-center justify-center px-4 sm:px-6 lg:px-8 pt-24">
    <div class="max-w-md w-full space-y-6">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-white text-center">Login</h1>
        <p class="mt-2 text-center text-sm text-gray-200">
          Login with your registered account to access your CRM data.
        </p>
      </div>

      <!-- Form login: putih solid -->
      <div class="bg-white shadow-md rounded-lg p-6">
        <?php if ($error): ?>
          <p class="mb-4 text-sm text-red-600">
            <?= htmlspecialchars($error) ?>
          </p>
        <?php endif; ?>

        <form method="post" class="space-y-4">
          <div>
            <label for="email" class="block text-sm font-medium text-gray-800">Email Address</label>
            <input type="email"
                   id="email"
                   name="email"
                   placeholder="your@email.com"
                   required
                   class="mt-2 block w-full rounded-lg bg-gray-100 px-4 py-3 text-base focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-gray-800">Password</label>
            <input type="password"
                   id="password"
                   name="password"
                   placeholder="********"
                   required
                   class="mt-2 block w-full rounded-lg bg-gray-100 px-4 py-3 text-base focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
          </div>

          <div>
            <button type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
              Login
            </button>
          </div>
        </form>
      </div>
    </div>
  </main>
</body>



</html>