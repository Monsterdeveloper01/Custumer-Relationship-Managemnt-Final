<?php
session_start();

require '../../backend_secure/crm/db.php';
require_once __DIR__ . '/../../backend_secure/crm/functions.php';

// Pastikan hanya admin yang boleh akses
if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$errors = [];
$success = '';

// Handle perubahan role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marketing_id']) && isset($_POST['new_role'])) {
    $marketing_id = trim($_POST['marketing_id']);
    $new_role = trim($_POST['new_role']);

    if (!in_array($new_role, ['admin', 'user'])) {
        $errors[] = "Role hanya boleh 'admin' atau 'user'.";
    } else {
        // Pastikan tidak mengubah diri sendiri jadi non-admin
        if ($marketing_id == $_SESSION['user']['marketing_id'] && $new_role !== 'admin') {
            $errors[] = "Anda tidak bisa mengubah role diri sendiri menjadi non-admin.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE marketing_id = ?");
                $updated = $stmt->execute([$new_role, $marketing_id]);

                if ($updated && $stmt->rowCount() > 0) {
                    $success = "Role berhasil diperbarui!";
                    // Refresh untuk memperbarui tampilan
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    $errors[] = "Gagal memperbarui role. User tidak ditemukan.";
                }
            } catch (Exception $e) {
                $errors[] = "Terjadi kesalahan sistem: " . $e->getMessage();
            }
        }
    }
}

// Ambil semua user (kecuali guest, jika ada)
try {
    $stmt = $pdo->query("SELECT marketing_id, name, email, role, created_at FROM users ORDER BY name ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errors[] = "Gagal mengambil data user: " . $e->getMessage();
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kelola Pengguna - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>

<body class="bg-gray-50 min-h-screen">
    <?php include("./Partials/Header.html"); ?>

    <div class="container mx-auto px-4 py-20">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Kelola Pengguna</h1>
            <p class="text-gray-600">Kelola role dan akses untuk semua anak magang</p>
        </div>

        <!-- Notifications -->
        <?php if ($errors): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                    <div>
                        <h3 class="font-medium text-red-800">Terjadi Kesalahan</h3>
                        <?php foreach ($errors as $e): ?>
                            <p class="text-red-700 text-sm mt-1"><?= htmlspecialchars($e) ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                    <div>
                        <h3 class="font-medium text-green-800">Berhasil!</h3>
                        <p class="text-green-700 text-sm mt-1"><?= htmlspecialchars($success) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Users Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Role</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bergabung</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                            <?php
                            $is_current_user = ($user['marketing_id'] == $_SESSION['user']['marketing_id']);
                            $is_admin = ($user['role'] === 'admin');
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-blue-500 rounded-full flex items-center justify-center">
                                            <span class="text-white font-medium text-sm">
                                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                            </span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($user['name']) ?>
                                                <?php if ($is_current_user): ?>
                                                    <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Anda</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($user['email']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?= $is_admin ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800' ?>">
                                            <i class="fas <?= $is_admin ? 'fa-crown' : 'fa-user' ?> mr-1"></i>
                                            <?= $is_admin ? 'Admin' : 'User' ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('d M Y', strtotime($user['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if (!$is_current_user): ?>
                                        <div class="flex space-x-2">
                                            <!-- Make as Admin Button -->
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="marketing_id" value="<?= $user['marketing_id'] ?>">
                                                <input type="hidden" name="new_role" value="admin">
                                                <button type="submit" 
                                                    class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded 
                                                    <?= $is_admin ? 
                                                        'bg-gray-100 text-gray-400 cursor-not-allowed' : 
                                                        'bg-purple-600 hover:bg-purple-700 text-white shadow-sm' 
                                                    ?>"
                                                    <?= $is_admin ? 'disabled' : '' ?>
                                                    onclick="return confirm('Jadikan <?= htmlspecialchars($user['name']) ?> sebagai Admin?')">
                                                    <i class="fas fa-crown mr-1"></i>
                                                    Make as Admin
                                                </button>
                                            </form>

                                            <!-- Make as User Button -->
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="marketing_id" value="<?= $user['marketing_id'] ?>">
                                                <input type="hidden" name="new_role" value="user">
                                                <button type="submit" 
                                                    class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded 
                                                    <?= !$is_admin ? 
                                                        'bg-gray-100 text-gray-400 cursor-not-allowed' : 
                                                        'bg-green-600 hover:bg-green-700 text-white shadow-sm' 
                                                    ?>"
                                                    <?= !$is_admin ? 'disabled' : '' ?>
                                                    onclick="return confirm('Jadikan <?= htmlspecialchars($user['name']) ?> sebagai User?')">
                                                    <i class="fas fa-user mr-1"></i>
                                                    Make as User
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm">Akun saat ini</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Summary Card -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-users text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Pengguna</p>
                        <p class="text-2xl font-bold text-gray-900"><?= count($users) ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="fas fa-user text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total User</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?= count(array_filter($users, fn($u) => $u['role'] === 'user')) ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <i class="fas fa-crown text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Admin</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-8">
            <a href="./dashboard.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali ke Dashboard
            </a>
        </div>
    </div>

    <style>
        .btn:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }
        .btn:disabled:hover {
            transform: none;
        }
    </style>
</body>
</html>