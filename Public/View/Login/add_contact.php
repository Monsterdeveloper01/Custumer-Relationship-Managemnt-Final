<?php
require_once __DIR__ . '/../../Model/db.php';
require_once __DIR__ . '/../../Controller/functions.php';
require_login();

$mid = current_marketing_id();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flag = trim($_POST['flag'] ?? '');
    $partner_type = trim($_POST['partner_type'] ?? '');

    // Validasi flag
    if (!in_array($flag, ['CLT', 'MKT'])) {
        $errors[] = "Jenis kontak (flag) wajib dipilih.";
    }

    if ($flag === 'MKT' && !in_array($partner_type, ['individual', 'institution'])) {
        $errors[] = "Jenis partner (individual/institution) wajib dipilih.";
    }

    if (empty($errors)) {
        if ($flag === 'CLT') {
            // === Mode CLT: Calon Client ===
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
                         kota, alamat, ditemukan_oleh, status, flag)
                        VALUES
                        (:nama_perusahaan, :email, :email_lain, :nama, :no_telp1, :no_telp2, :website,
                         :kategori_perusahaan, :kategori_jabatan, :jabatan_lengkap, :tipe,
                         :kota, :alamat, :ditemukan_oleh, :status, :flag)");
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
                        ':ditemukan_oleh' => $mid,
                        ':status' => 'input',
                        ':flag' => 'CLT',
                    ]);
                    header("Location: dashboard.php");
                    exit;
                }
            }
        } else {
            // === Mode MKT: Calon Partner ===
            $email = trim($_POST['email'] ?? '');
            $whatsapp = trim($_POST['whatsapp'] ?? '');
            $nama_bank = trim($_POST['nama_bank'] ?? '');
            $no_rekening = trim($_POST['no_rekening'] ?? '');
            $profil_jaringan = trim($_POST['profil_jaringan'] ?? '');
            $segment_industri = trim($_POST['segment_industri'] ?? '');

            if ($email === '') {
                $errors[] = "Email wajib diisi.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Format email tidak valid.";
            }
            if ($whatsapp === '') {
                $errors[] = "Nomor WhatsApp wajib diisi.";
            }

            if (empty($errors)) {
                $cek = $pdo->prepare("SELECT COUNT(*) FROM crm_contacts_staging WHERE email = ?");
                $cek->execute([$email]);
                if ($cek->fetchColumn() > 0) {
                    $errors[] = "Email sudah terdaftar.";
                } else {
                    if ($partner_type === 'individual') {
                        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
                        if ($nama_lengkap === '') {
                            $errors[] = "Nama lengkap wajib diisi.";
                        }
                        if (empty($errors)) {
                            // Mapping ke kolom crm_contacts_staging
                            $stmt = $pdo->prepare("INSERT INTO crm_contacts_staging
                                (nama_perusahaan, email, no_telp1, alamat, kategori_perusahaan, tipe,
                                 segment_industri_fokus, ditemukan_oleh, status, flag)
                                VALUES
                                (:nama_perusahaan, :email, :no_telp1, :alamat, :kategori_perusahaan, :tipe,
                                 :segment_industri_fokus, :ditemukan_oleh, :status, :flag)");
                            $stmt->execute([
                                ':nama_perusahaan' => $nama_lengkap,
                                ':email' => $email,
                                ':no_telp1' => $whatsapp,
                                ':alamat' => "Bank: {$nama_bank}, Rek: {$no_rekening}\nProfil: {$profil_jaringan}",
                                ':kategori_perusahaan' => 'Individual Partner',
                                ':tipe' => 'INDIVIDU',
                                ':segment_industri_fokus' => $segment_industri,
                                ':ditemukan_oleh' => $mid,
                                ':status' => 'input',
                                ':flag' => 'MKT',
                            ]);
                        }
                    } else {
                        $nama_institusi = trim($_POST['nama_institusi'] ?? '');
                        if ($nama_institusi === '') {
                            $errors[] = "Nama institusi wajib diisi.";
                        }
                        if (empty($errors)) {
                            $stmt = $pdo->prepare("INSERT INTO crm_contacts_staging
                                (nama_perusahaan, email, no_telp1, alamat, kategori_perusahaan, tipe,
                                 segment_industri_fokus, ditemukan_oleh, status, flag)
                                VALUES
                                (:nama_perusahaan, :email, :no_telp1, :alamat, :kategori_perusahaan, :tipe,
                                 :segment_industri_fokus, :ditemukan_oleh, :status, :flag)");
                            $stmt->execute([
                                ':nama_perusahaan' => $nama_institusi,
                                ':email' => $email,
                                ':no_telp1' => $whatsapp,
                                ':alamat' => "Bank: {$nama_bank}, Rek: {$no_rekening}\nProfil: {$profil_jaringan}",
                                ':kategori_perusahaan' => 'Institution Partner',
                                ':tipe' => 'INSTITUSI',
                                ':segment_industri_fokus' => $segment_industri,
                                ':ditemukan_oleh' => $mid,
                                ':status' => 'input',
                                ':flag' => 'MKT',
                            ]);
                        }
                    }

                    if (empty($errors)) {
                        header("Location: dashboard.php");
                        exit;
                    }
                }
            }
        }
    }
}
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Add Contact | CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media (max-width: 639px) {
            .mobile-padding {
                padding: 1rem !important;
            }

            .mobile-text {
                font-size: 0.9rem;
            }

            .mobile-grid {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }

            .mobile-full {
                grid-column: 1 / -1 !important;
            }

            .mobile-stack {
                flex-direction: column;
                gap: 0.5rem;
            }

            .mobile-btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (min-width: 576px) {
            .custom-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        input,
        select,
        textarea {
            min-height: 44px;
        }

        .transition-all {
            transition: all 0.3s ease;
        }

        .hidden {
            display: none;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-100 min-h-screen flex items-center justify-center p-4 sm:p-6">
    <div class="w-full max-w-6xl bg-white rounded-2xl lg:rounded-3xl shadow-lg lg:shadow-2xl overflow-hidden transition-all">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-4 lg:p-7">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 lg:w-8 lg:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <h1 class="text-xl lg:text-3xl font-bold text-white">Add New Contact</h1>
            </div>
            <p class="text-blue-100 text-sm mt-1">Pilih jenis kontak, lalu isi form sesuai kebutuhan.</p>
        </div>

        <div class="p-4 lg:p-8 space-y-4 lg:space-y-6">
            <?php if ($errors): ?>
                <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl shadow-sm">
                    <ul class="list-disc pl-5 text-sm space-y-1">
                        <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="grid grid-cols-1 custom-grid gap-4 lg:gap-6" id="mainForm">
                <!-- Flag Type -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700">
                        Jenis Kontak <span class="text-red-500">*</span>
                    </label>
                    <select name="flag" id="flagSelect"
                        class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition"
                        required>
                        <option value="">-- Pilih --</option>
                        <option value="CLT" <?= (($_POST['flag'] ?? '') === 'CLT') ? 'selected' : '' ?>>Calon Client (CLT)</option>
                        <option value="MKT" <?= (($_POST['flag'] ?? '') === 'MKT') ? 'selected' : '' ?>>Calon Partner (MKT)</option>
                    </select>
                </div>

                <!-- Partner Type (hanya muncul jika MKT) -->
                <div id="partnerTypeSection" class="hidden">
                    <label class="block text-sm font-semibold text-gray-700">
                        Jenis Partner <span class="text-red-500">*</span>
                    </label>
                    <select name="partner_type" id="partnerTypeSelect"
                        class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
                        <option value="">-- Pilih --</option>
                        <option value="individual" <?= (($_POST['partner_type'] ?? '') === 'individual') ? 'selected' : '' ?>>Individual</option>
                        <option value="institution" <?= (($_POST['partner_type'] ?? '') === 'institution') ? 'selected' : '' ?>>Institution</option>
                    </select>
                </div>

                <!-- === CLT FORM === -->
                <div id="cltForm" class="<?= (($_POST['flag'] ?? '') === 'CLT') ? '' : 'hidden' ?>">
                    <!-- Company Email -->
                    <div class="col-span-1 sm:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700">Company Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" placeholder="contoh: info@perusahaan.com"
                            value="<?= h($_POST['email'] ?? '') ?>"
                            class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition"
                            required>
                    </div>

                    <div class="col-span-1 sm:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700">Secondary Email</label>
                        <input type="email" name="email_lain" placeholder="Email alternatif (opsional)"
                            value="<?= h($_POST['email_lain'] ?? '') ?>"
                            class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Company Name <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_perusahaan" placeholder="Nama lengkap perusahaan"
                            value="<?= h($_POST['nama_perusahaan'] ?? '') ?>"
                            class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Contact Person</label>
                        <input type="text" name="nama" placeholder="Nama orang yang bisa dihubungi"
                            value="<?= h($_POST['nama'] ?? '') ?>"
                            class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Company Category</label>
                        <select name="kategori_perusahaan"
                            class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition" required>
                            <option value="">-- Select Category --</option>
                            <option value="Banking">Banking</option>
                            <option value="Multifinance">Multifinance</option>
                            <option value="Insurance">Insurance</option>
                            <option value="Manufacturing">Manufacturing</option>
                            <option value="Retail">Retail</option>
                            <option value="Distribution">Distribution</option>
                            <option value="Oil & Gas / Energy">Oil & Gas / Energy</option>
                            <option value="Government and Ministry">Government and Ministry</option>
                            <option value="Koperasi & UMKM">Koperasi & UMKM</option>
                            <option value="Logistics and Transportation">Logistics and Transportation</option>
                            <option value="Hospital and Clinics">Hospital and Clinics</option>
                            <option value="Education and Training">Education and Training</option>
                            <option value="Hotels, Restaurant, and Hospitality">Hotels, Restaurant, and Hospitality</option>
                            <option value="Tour and Travel">Tour and Travel</option>
                            <option value="NGO, LSM, and International Organizations">NGO, LSM, and International Organizations</option>
                            <option value="Property and Real Estate">Property and Real Estate</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Job Category</label>
                        <input type="text" name="kategori_jabatan" placeholder="Kategori jabatan"
                            value="<?= h($_POST['kategori_jabatan'] ?? '') ?>"
                            class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Position Title</label>
                        <input type="text" name="jabatan_lengkap" placeholder="Jabatan lengkap"
                            value="<?= h($_POST['jabatan_lengkap'] ?? '') ?>"
                            class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Type <span class="text-red-500">*</span></label>
                        <select name="tipe"
                            class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition"
                            required>
                            <option value="" disabled>-- Pilih tipe perusahaan --</option>
                            <option value="SWASTA" <?= (($_POST['tipe'] ?? '') === 'SWASTA') ? 'selected' : '' ?>>SWASTA</option>
                            <option value="BUMN" <?= (($_POST['tipe'] ?? '') === 'BUMN') ? 'selected' : '' ?>>BUMN</option>
                            <option value="BUMD" <?= (($_POST['tipe'] ?? '') === 'BUMD') ? 'selected' : '' ?>>BUMD</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Phone Number 1</label>
                        <input type="text" name="no_telp1" placeholder="Nomor telepon utama"
                            value="<?= h($_POST['no_telp1'] ?? '') ?>"
                            class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Phone Number 2</label>
                        <input type="text" name="no_telp2" placeholder="Nomor telepon alternatif"
                            value="<?= h($_POST['no_telp2'] ?? '') ?>"
                            class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
                    </div>

                    <div class="col-span-1 sm:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700">Website</label>
                        <input type="url" name="website" placeholder="https://www.perusahaan.com"
                            value="<?= h($_POST['website'] ?? '') ?>"
                            class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
                    </div>

                    <div class="col-span-1 sm:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700">Address</label>
                        <textarea name="alamat" placeholder="Alamat lengkap perusahaan" rows="3"
                            class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition"><?= h($_POST['alamat'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">City</label>
                        <input type="text" name="kota" placeholder="Masukkan kota perusahaan"
                            value="<?= h($_POST['kota'] ?? '') ?>"
                            class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
                    </div>
                </div>

                <!-- === MKT FORM === -->
                <div id="mktForm" class="<?= (($_POST['flag'] ?? '') === 'MKT') ? '' : 'hidden' ?>">
                    <div id="mktIndividualForm" class="<?= (($_POST['partner_type'] ?? '') === 'individual') ? '' : 'hidden' ?>">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_lengkap" placeholder="Nama lengkap calon partner"
                                value="<?= h($_POST['nama_lengkap'] ?? '') ?>"
                                class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
                        </div>
                    </div>

                    <div id="mktInstitutionForm" class="<?= (($_POST['partner_type'] ?? '') === 'institution') ? '' : 'hidden' ?>">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700">Nama Institusi <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_institusi" placeholder="Nama institusi calon partner"
                                value="<?= h($_POST['nama_institusi'] ?? '') ?>"
                                class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" placeholder="Email partner"
                            value="<?= h($_POST['email'] ?? '') ?>"
                            class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">WhatsApp <span class="text-red-500">*</span></label>
                        <input type="text" name="whatsapp" placeholder="Nomor WhatsApp aktif"
                            value="<?= h($_POST['whatsapp'] ?? '') ?>"
                            class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Nama Bank <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_bank" placeholder="Nama bank untuk komisi"
                            value="<?= h($_POST['nama_bank'] ?? '') ?>"
                            class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Nomor Rekening <span class="text-red-500">*</span></label>
                        <input type="text" name="no_rekening" placeholder="Nomor rekening"
                            value="<?= h($_POST['no_rekening'] ?? '') ?>"
                            class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Profil Jaringan</label>
                        <textarea name="profil_jaringan" placeholder="Deskripsi jaringan/profil (opsional)" rows="2"
                            class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition"><?= h($_POST['profil_jaringan'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">Segment Industri Fokus</label>
                        <input type="text" name="segment_industri" placeholder="Industri yang sering dijangkau"
                            value="<?= h($_POST['segment_industri'] ?? '') ?>"
                            class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
                    </div>
                </div>

                <!-- Status (tetap hidden) -->
                <input type="hidden" name="status" value="input">

                <!-- Buttons -->
                <div class="col-span-1 sm:col-span-2 justify-end flex flex-col sm:flex-row gap-3 lg:gap-4 pt-4 lg:pt-6">
                    <a href="dashboard.php"
                        class="px-6 py-3 rounded-xl bg-gray-200 text-gray-700 hover:bg-gray-300 font-semibold transition text-center sm:text-left">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-6 py-3 rounded-xl bg-blue-600 text-white hover:bg-blue-700 font-semibold shadow-md transition transform hover:scale-105 text-center">
                        Save Contact
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- <script>
        document.getElementById('flagSelect').addEventListener('change', function() {
            const flag = this.value;
            const cltForm = document.getElementById('cltForm');
            const mktForm = document.getElementById('mktForm');
            const partnerTypeSection = document.getElementById('partnerTypeSection');

            // Nonaktifkan semua required di CLT & MKT dulu
            document.querySelectorAll('#cltForm [required]').forEach(el => el.removeAttribute('required'));
            document.querySelectorAll('#mktForm [required]').forEach(el => el.removeAttribute('required'));

            if (flag === 'CLT') {
                cltForm.classList.remove('hidden');
                // Aktifkan required hanya untuk CLT
                document.querySelectorAll('#cltForm input, #cltForm select').forEach(el => {
                    if (['email', 'nama_perusahaan', 'tipe', 'kategori_perusahaan'].includes(el.name)) {
                        el.setAttribute('required', 'required');
                    }
                });
                mktForm.classList.add('hidden');
                partnerTypeSection.classList.add('hidden');
            } else if (flag === 'MKT') {
                cltForm.classList.add('hidden');
                partnerTypeSection.classList.remove('hidden');
                // Trigger partner type change
                document.getElementById('partnerTypeSelect').dispatchEvent(new Event('change'));
            }
        });

        document.getElementById('partnerTypeSelect').addEventListener('change', function() {
            const type = this.value;
            const individual = document.getElementById('mktIndividualForm');
            const institution = document.getElementById('mktInstitutionForm');
            const mktForm = document.getElementById('mktForm');

            // Reset semua required di MKT
            document.querySelectorAll('#mktForm [required]').forEach(el => el.removeAttribute('required'));

            if (type === 'individual') {
                individual.classList.remove('hidden');
                institution.classList.add('hidden');
                mktForm.classList.remove('hidden');
                // Aktifkan required hanya untuk individual
                ['email', 'whatsapp', 'nama_bank', 'no_rekening', 'nama_lengkap'].forEach(name => {
                    const el = document.querySelector(`#mktForm [name="${name}"]`);
                    if (el) el.setAttribute('required', 'required');
                });
            } else if (type === 'institution') {
                individual.classList.add('hidden');
                institution.classList.remove('hidden');
                mktForm.classList.remove('hidden');
                // Aktifkan required hanya untuk institution
                ['email', 'whatsapp', 'nama_bank', 'no_rekening', 'nama_institusi'].forEach(name => {
                    const el = document.querySelector(`#mktForm [name="${name}"]`);
                    if (el) el.setAttribute('required', 'required');
                });
            } else {
                // Tidak pilih apa-apa → sembunyikan semua
                individual.classList.add('hidden');
                institution.classList.add('hidden');
                mktForm.classList.add('hidden');
            }
        });
    </script> -->

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const flagSelect = document.getElementById("flagSelect");
            const partnerTypeSelect = document.getElementById("partnerTypeSelect");

            function toggleForms() {
                const flag = flagSelect.value;
                const partnerType = partnerTypeSelect.value;

                // Reset semua required dulu
                document.querySelectorAll("#cltForm [required], #mktForm [required]").forEach(el => {
                    el.removeAttribute("required");
                });

                // Tampilkan/hidden form
                document.getElementById("cltForm").classList.toggle("hidden", flag !== "CLT");
                document.getElementById("mktForm").classList.toggle("hidden", flag !== "MKT");

                document.getElementById("partnerTypeSection").classList.toggle("hidden", flag !== "MKT");

                document.getElementById("mktIndividualForm").classList.toggle("hidden", !(flag === "MKT" && partnerType === "individual"));
                document.getElementById("mktInstitutionForm").classList.toggle("hidden", !(flag === "MKT" && partnerType === "institution"));

                // Tambahkan kembali required sesuai kondisi
                if (flag === "CLT") {
                    document.querySelectorAll("#cltForm [name='email'], #cltForm [name='nama_perusahaan'], #cltForm [name='tipe']").forEach(el => {
                        el.setAttribute("required", "required");
                    });
                } else if (flag === "MKT") {
                    document.querySelectorAll("#mktForm [name='email'], #mktForm [name='whatsapp'], #mktForm [name='nama_bank'], #mktForm [name='no_rekening']").forEach(el => {
                        el.setAttribute("required", "required");
                    });

                    if (partnerType === "individual") {
                        document.querySelector("#mktIndividualForm [name='nama_lengkap']").setAttribute("required", "required");
                    } else if (partnerType === "institution") {
                        document.querySelector("#mktInstitutionForm [name='nama_institusi']").setAttribute("required", "required");
                    }
                }
            }

            flagSelect.addEventListener("change", toggleForms);
            partnerTypeSelect.addEventListener("change", toggleForms);

            // Panggil sekali saat load (biar sesuai POST lama juga)
            toggleForms();
        });
    </script>


    <!-- <script>
document.addEventListener("DOMContentLoaded", function () {
    const flagSelect = document.getElementById("flagSelect");
    const partnerTypeSection = document.getElementById("partnerTypeSection");
    const cltForm = document.getElementById("cltForm");
    const mktForm = document.getElementById("mktForm");

    const partnerTypeSelect = document.getElementById("partnerTypeSelect");
    const mktIndividualForm = document.getElementById("mktIndividualForm");
    const mktInstitutionForm = document.getElementById("mktInstitutionForm");

    function updateFormVisibility() {
        const flag = flagSelect.value;
        if (flag === "CLT") {
            cltForm.classList.remove("hidden");
            mktForm.classList.add("hidden");
            partnerTypeSection.classList.add("hidden");
        } else if (flag === "MKT") {
            cltForm.classList.add("hidden");
            mktForm.classList.remove("hidden");
            partnerTypeSection.classList.remove("hidden");
        } else {
            cltForm.classList.add("hidden");
            mktForm.classList.add("hidden");
            partnerTypeSection.classList.add("hidden");
        }
    }

    function updatePartnerTypeVisibility() {
        const type = partnerTypeSelect.value;
        if (type === "individual") {
            mktIndividualForm.classList.remove("hidden");
            mktInstitutionForm.classList.add("hidden");
        } else if (type === "institution") {
            mktIndividualForm.classList.add("hidden");
            mktInstitutionForm.classList.remove("hidden");
        } else {
            mktIndividualForm.classList.add("hidden");
            mktInstitutionForm.classList.add("hidden");
        }
    }

    flagSelect.addEventListener("change", updateFormVisibility);
    partnerTypeSelect.addEventListener("change", updatePartnerTypeVisibility);

    // run once on load
    updateFormVisibility();
    updatePartnerTypeVisibility();
});
</script> -->

</body>

</html>