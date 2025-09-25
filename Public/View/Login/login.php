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

        // 2. Kalau belum ketemu → cek di individual_promocodes (role default user)
        if (!$account) {
            $stmt = $pdo->prepare("
                SELECT marketing_id, nama_lengkap AS name, email, password, 'user' AS role, 'individual' AS source
                FROM individual_promocodes
                WHERE email = :email
                LIMIT 1
            ");
            $stmt->execute([':email' => $email]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // 3. Kalau masih belum ketemu → cek di institusi_partner (role default user)
        if (!$account) {
            $stmt = $pdo->prepare("
                SELECT marketing_id, nama_partner AS name, email, password, 'user' AS role, 'institution' AS source
                FROM institusi_partner
                WHERE email = :email
                LIMIT 1
            ");
            $stmt->execute([':email' => $email]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($account) {
            // cek password
            if (password_verify($password, $account['password'])) {
                $_SESSION['user'] = [
                    'marketing_id' => $account['marketing_id'],
                    'name'         => $account['name'],
                    'email'        => $account['email'],
                    'role'         => $account['role'],   // <<=== simpan role
                    'source'       => $account['source']
                ];

                // redirect berdasarkan role (opsional)
                if ($account['role'] === 'admin') {
                    header("Location: dashboard_admin.php");
                } else {
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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- Header -->
    <header class="bg-white shadow-md border-b border-gray-200 fixed top-0 w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">

                <!-- Logo + Label -->
                <div class="flex items-center">
                    <div class="flex items-center">
                        <img src="../../assets/img/rayterton-apps-software-logo.png"
                            alt="Logo"
                            class="h-10 w-auto"
                            style="width: 175px; height: 50px;">
                        <span class="ml-3 text-lg font-semibold text-gray-900 hidden md:block">
                            Customer Relationship Management
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </header>


    <div class="container">
        <h1 class="title">Login</h1>
        <p class="subtitle">Login with your registered account to access your CRM data.</p>

        <div class="card">
            <?php if ($error): ?>
                <p style="color:#B91C1C; font-size:14px; margin-bottom:16px;">
                    <?= htmlspecialchars($error) ?>
                </p>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="your@email.com" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="********" required>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn primary">Login</button>
                </div>
                <div class="button-group" style="margin-top:10px;">
                    <a href="../login.php" class="btn secondary">Login ke CRM App</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>