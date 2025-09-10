<?php
require_once __DIR__ . '/../Model/db.php';
require_once __DIR__ . '/../Controller/functions.php';
require_login();

$user = $_SESSION['user']; // dari login
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Settings | CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>

<body class="bg-gray-50 min-h-screen font-sans" x-data="{ sidebarOpen: false }">

    <!-- Header -->
    <?php include("partials/header.html"); ?>

    <!-- Sidebar -->
    <?php include("partials/sidebar.html"); ?>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 py-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-8 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 11c0-1.104.896-2 2-2s2 .896 2 2v0a2 2 0 11-4 0zM12 11V8m0 3v7" />
            </svg>
            Pengaturan Akun
        </h2>

        <!-- Profil Akun -->
        <div
            class="bg-white shadow-md hover:shadow-lg transition rounded-xl p-6 mb-8 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Profil Akun</h3>
            <dl class="divide-y divide-gray-200 text-sm">
                <div class="py-3 flex justify-between items-center">
                    <dt class="text-gray-600">Marketing ID</dt>
                    <dd class="font-medium text-gray-900"><?= h($user['marketing_id']) ?></dd>
                </div>
                <div class="py-3 flex justify-between items-center">
                    <dt class="text-gray-600">Nama</dt>
                    <dd class="font-medium text-gray-900"><?= h($user['name']) ?></dd>
                </div>
                <div class="py-3 flex justify-between items-center">
                    <dt class="text-gray-600">Email</dt>
                    <dd class="font-medium text-gray-900"><?= h($user['email']) ?></dd>
                </div>
            </dl>
        </div>

        <!-- Keamanan -->
        <div
            class="bg-white shadow-md hover:shadow-lg transition rounded-xl p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 11c0-1.104.896-2 2-2s2 .896 2 2v0a2 2 0 11-4 0zM12 11V8m0 3v7" />
                </svg>
                Keamanan
            </h3>
            <p class="text-gray-600 text-sm mb-6 leading-relaxed">
                Untuk menjaga keamanan akun, sebaiknya ubah password Anda secara berkala. 
                Pastikan password mengandung kombinasi huruf, angka, dan simbol agar lebih aman.
            </p>
            <a href="reset_password.php"
                class="inline-flex items-center px-5 py-2.5 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 active:scale-95 transform transition gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 11c0-1.104.896-2 2-2s2 .896 2 2v0a2 2 0 11-4 0zM12 11V8m0 3v7" />
                </svg>
                Reset Password
            </a>
        </div>
    </main>
</body>

</html>
