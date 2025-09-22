<?php
require_once __DIR__ . '/../../Model/db.php';
require_once __DIR__ . '/../../Controller/functions.php';
require_login();

$mid = current_marketing_id();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $email_lain = trim($_POST['email_lain'] ?? '');
    $nama_perusahaan = trim($_POST['nama_perusahaan'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $kategori_perusahaan = trim($_POST['kategori_perusahaan'] ?? '');
    $kategori_jabatan = trim($_POST['kategori_jabatan'] ?? '');
    $jabatan_lengkap = trim($_POST['jabatan_lengkap'] ?? '');
    $tipe = trim($_POST['tipe'] ?? '');
    $no_telp1 = trim($_POST['no_telp1'] ?? '');
    $no_telp2 = trim($_POST['no_telp2'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $kota = trim($_POST['kota'] ?? '');
    $ditemukan_oleh = trim($_POST['ditemukan_oleh'] ?? $mid);
    $status = $_POST['status'] ?? 'input';

    // Validasi
    if ($email === '') {
        $errors[] = "Email perusahaan wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }
    if ($nama_perusahaan === '') {
        $errors[] = "Nama perusahaan wajib diisi.";
    }

    if (empty($errors)) {
        $cek = $pdo->prepare("SELECT COUNT(*) FROM crm_contacts_staging WHERE email = ?");
        $cek->execute([$email]);
        if ($cek->fetchColumn() > 0) {
            $errors[] = "Email perusahaan sudah terdaftar.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO crm_contacts_staging
                (nama_perusahaan, email, email_lain, nama, no_telp1, no_telp2, website,
                 kategori_perusahaan, kategori_jabatan, jabatan_lengkap, tipe,
                 kota, alamat, ditemukan_oleh, status)
                VALUES
                (:nama_perusahaan, :email, :email_lain, :nama, :no_telp1, :no_telp2, :website,
                 :kategori_perusahaan, :kategori_jabatan, :jabatan_lengkap, :tipe,
                 :kota, :alamat, :ditemukan_oleh, :status)");
            $stmt->execute([
                ':nama_perusahaan' => $nama_perusahaan,
                ':email' => $email,
                ':email_lain' => $email_lain,
                ':nama' => $nama,
                ':no_telp1' => $no_telp1,
                ':no_telp2' => $no_telp2,
                ':website' => $website,
                ':kategori_perusahaan' => $kategori_perusahaan,
                ':kategori_jabatan' => $kategori_jabatan,
                ':jabatan_lengkap' => $jabatan_lengkap,
                ':tipe' => $tipe,
                ':kota' => $kota,
                ':alamat' => $alamat,
                ':ditemukan_oleh' => $ditemukan_oleh,
                ':status' => $status
            ]);
            header("Location: dashboard.php");
            exit;
        }
    }
}

function h($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Add Contact | CRM</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-100 min-h-screen flex items-center justify-center p-6">

<div class="w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-7">
        <h1 class="text-3xl font-bold text-white flex items-center gap-3">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add New Contact
        </h1>
        <p class="text-blue-100 text-sm mt-1">Masukkan detail perusahaan dan kontak yang ingin disimpan.</p>
    </div>

    <div class="p-8 space-y-6">
        <?php if ($errors): ?>
        <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl shadow-sm">
            <ul class="list-disc pl-5 text-sm space-y-1">
                <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Company Email -->
            <div class="col-span-2">
                <label class="block text-sm font-semibold text-gray-700">Company Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" placeholder="contoh: info@perusahaan.com" value="<?= h($_POST['email'] ?? '') ?>" class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition" required>
            </div>

            <!-- Secondary Email -->
            <div class="col-span-2">
                <label class="block text-sm font-semibold text-gray-700">Secondary Email</label>
                <input type="email" name="email_lain" placeholder="Email alternatif (opsional)" value="<?= h($_POST['email_lain'] ?? '') ?>" class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
            </div>

            <!-- Company Name -->
            <div>
                <label class="block text-sm font-semibold text-gray-700">Company Name <span class="text-red-500">*</span></label>
                <input type="text" name="nama_perusahaan" placeholder="Nama lengkap perusahaan" value="<?= h($_POST['nama_perusahaan'] ?? '') ?>" class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition" required>
            </div>

            <!-- Contact Person -->
            <div>
                <label class="block text-sm font-semibold text-gray-700">Contact Person</label>
                <input type="text" name="nama" placeholder="Nama orang yang bisa dihubungi" value="<?= h($_POST['nama'] ?? '') ?>" class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
            </div>

            <!-- Company Category -->
            <div>
                <label class="block text-sm font-semibold text-gray-700">Company Category</label>
                <input type="text" name="kategori_perusahaan" placeholder="Kategori perusahaan" value="<?= h($_POST['kategori_perusahaan'] ?? '') ?>" class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
            </div>

            <!-- Job Category -->
            <div>
                <label class="block text-sm font-semibold text-gray-700">Job Category</label>
                <input type="text" name="kategori_jabatan" placeholder="Kategori jabatan" value="<?= h($_POST['kategori_jabatan'] ?? '') ?>" class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
            </div>

            <!-- Position Title -->
            <div>
                <label class="block text-sm font-semibold text-gray-700">Position Title</label>
                <input type="text" name="jabatan_lengkap" placeholder="Jabatan lengkap" value="<?= h($_POST['jabatan_lengkap'] ?? '') ?>" class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
            </div>

            <!-- Type -->
            <div>
                <label class="block text-sm font-semibold text-gray-700">Type <span class="text-red-500">*</span></label>
                <select name="tipe" class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition" required>
                    <option value="" disabled selected>-- Pilih tipe perusahaan --</option>
                    <option value="SWASTA" <?= (($_POST['tipe'] ?? '') === 'SWASTA') ? 'selected' : '' ?>>SWASTA</option>
                    <option value="BUMN" <?= (($_POST['tipe'] ?? '') === 'BUMN') ? 'selected' : '' ?>>BUMN</option>
                    <option value="BUMD" <?= (($_POST['tipe'] ?? '') === 'BUMD') ? 'selected' : '' ?>>BUMD</option>
                </select>
            </div>

            <!-- Phone 1 -->
            <div>
                <label class="block text-sm font-semibold text-gray-700">Phone Number 1</label>
                <input type="text" name="no_telp1" placeholder="Nomor telepon utama" value="<?= h($_POST['no_telp1'] ?? '') ?>" class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
            </div>

            <!-- Phone 2 -->
            <div>
                <label class="block text-sm font-semibold text-gray-700">Phone Number 2</label>
                <input type="text" name="no_telp2" placeholder="Nomor telepon alternatif" value="<?= h($_POST['no_telp2'] ?? '') ?>" class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
            </div>

            <!-- Website -->
            <div class="col-span-2">
                <label class="block text-sm font-semibold text-gray-700">Website</label>
                <input type="url" name="website" placeholder="https://www.perusahaan.com" value="<?= h($_POST['website'] ?? '') ?>" class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
            </div>

            <!-- Address -->
            <div class="col-span-2">
                <label class="block text-sm font-semibold text-gray-700">Address</label>
                <textarea name="alamat" placeholder="Alamat lengkap perusahaan" rows="3" class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition"><?= h($_POST['alamat'] ?? '') ?></textarea>
            </div>

            <!-- City -->
            <div>
                <label class="block text-sm font-semibold text-gray-700">City</label>
                <input type="text" name="kota" placeholder="Masukkan kota perusahaan" value="<?= h($_POST['kota'] ?? '') ?>" class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-semibold text-gray-700">Status</label>
                <select name="status" class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
                    <?php
                    $statuses = [
                        'input','emailed','presentation','NDA process','Gap analysis / requirement analysis',
                        'SIT (System Integration Testing)','UAT (User Acceptance Testing)','Proposal','Negotiation',
                        'Deal / Closed','Failed / Tidak Lanjut','Postpone'
                    ];
                    foreach ($statuses as $s) {
                        $sel = ($s === ($_POST['status'] ?? 'input')) ? 'selected' : '';
                        echo "<option value='".h($s)."' $sel>".ucfirst($s)."</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Buttons -->
            <div class="col-span-2 flex justify-end gap-4 pt-4">
                <a href="dashboard.php" class="px-6 py-2 rounded-xl bg-gray-200 text-gray-700 hover:bg-gray-300 font-semibold transition">Cancel</a>
                <button type="submit" class="px-6 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 font-semibold shadow-md transition transform hover:scale-105">Save Contact</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
