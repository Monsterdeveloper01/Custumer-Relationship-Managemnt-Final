<?php
require_once __DIR__ . '/../../backend_secure/Model/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
        $account = null;

        // 1. Cari di users
        $stmt = $pdo->prepare("
            SELECT marketing_id, name, email, password, 'user' AS source
            FROM users
            WHERE email = :email
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Kalau belum ketemu → cek di individual_promocodes
        if (!$account) {
            $stmt = $pdo->prepare("
                SELECT marketing_id, nama_lengkap AS name, email, password, 'individual' AS source
                FROM individual_promocodes
                WHERE email = :email
                LIMIT 1
            ");
            $stmt->execute([':email' => $email]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // 3. Kalau masih belum ketemu → cek di institusi_partner
        if (!$account) {
            $stmt = $pdo->prepare("
                SELECT marketing_id, nama_partner AS name, email, password, 'institution' AS source
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
                    'source'       => $account['source']
                ];

                // redirect ke dashboard
                header("Location: dashboard.php");
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
    <link rel="stylesheet" href="../../assets/css/output.css">
</head>

<body>
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