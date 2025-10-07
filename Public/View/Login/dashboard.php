<?php
session_start();
if (!empty($_SESSION['bulk_msg'])): ?>
    <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6">
        <?= htmlspecialchars($_SESSION['bulk_msg']) ?>
    </div>
    <?php unset($_SESSION['bulk_msg']); ?>
<?php endif;
// require '../../backend_secure/crm/db.php';
require '../../Model/db.php';
require_once __DIR__ . '/../../Controller/functions.php';

// Kalau belum login, tendang ke login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION['user']['role'] ?? '') === 'admin') {
    header("Location: dashboard_admin.php");
    exit;
}
// function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }


$partner = $_SESSION['user'];
$marketing_id = $partner['marketing_id']; // contoh: PTR
$isPartner = ($partner['role'] === 'partner');

// Cek jumlah data CRM untuk marketing ini
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM crm_contacts_staging WHERE ditemukan_oleh = ?");
$stmtCount->execute([$marketing_id]);
$totalByFinder = (int)$stmtCount->fetchColumn();

// Tentukan apakah harus tampilkan modal
$showWelcomeModal = ($totalByFinder === 0);

// Ambil contact lengkap dari CRM berdasarkan ditemukan_oleh
$sql = "
    SELECT *
    FROM crm_contacts_staging
    WHERE ditemukan_oleh = ?
    ORDER BY FIELD(
        status,
        'input',
        'emailed',
        'contacted',
        'presentation',
        'NDA process',
        'Gap analysis / requirement analysis',
        'Customization',
        'SIT (System Integration Testing)',
        'UAT (User Acceptance Testing)',
        'Proposal',
        'Negotiation',
        'Deal / Closed',
        'Failed / Tidak Lanjut',
        'Postpone'
    ) ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$marketing_id]);
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

function normalize_email($val)
{
    $val = trim($val ?? '');
    // buang prefix mailto:
    if (stripos($val, 'mailto:') === 0) {
        $val = substr($val, 7);
    }
    // jika format mailto pernah membawa query (?subject=...), ambil sebelum '?'
    if (($qpos = strpos($val, '?')) !== false) {
        $val = substr($val, 0, $qpos);
    }
    return $val;
}

// Statistik status
$statsStmt = $pdo->prepare("
    SELECT 
        CASE 
            WHEN status IS NULL OR status = '' THEN 'Belum di beri status' 
            ELSE status 
        END as status,
        COUNT(*) as total 
    FROM crm_contacts_staging 
    WHERE ditemukan_oleh = ? 
    GROUP BY 
        CASE 
            WHEN status IS NULL OR status = '' THEN 'Belum di beri status' 
            ELSE status 
        END
");
$statsStmt->execute([$marketing_id]);
$stats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);

$statusList = [
    "input" => "#3b82f6",
    "emailed" => "#22c55e",
    "contacted" => "#f59e0b",
    "presentation" => "#8b5cf6",
    "NDA process" => "#0ea5e9",
    "Gap analysis / requirement analysis" => "#14b8a6",
    "Customization" => "#a855f7",
    "SIT (System Integration Testing)" => "#e11d48",
    "UAT (User Acceptance Testing)" => "#7c3aed",
    "Proposal" => "#06b6d4",
    "Negotiation" => "#f97316",
    "Deal / Closed" => "#16a34a",
    "Failed / Tidak Lanjut" => "#dc2626",
    "Postpone" => "#6b7280",
];

$limit = 0; // 0 artinya: jika sudah punya >=1 contact, form tidak muncul
$canShowForm = ($totalByFinder <= $limit); // true bila belum pernah input

// Status list (seragam dgn halaman lain)
$statuses = [
    'input',
    'emailed',
    'presentation',
    'NDA process',
    'Gap analysis / requirement analysis',
    'Customization',
    'SIT (System Integration Testing)',
    'UAT (User Acceptance Testing)',
    'Proposal',
    'Negotiation',
    'Deal / Closed',
    'Failed / Tidak Lanjut',
    'Postpone'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Jika form disubmit sementara canShowForm false, tolak
    if (!$canShowForm) {
        $errors[] = "Tambah contact dinonaktifkan karena akun marketing ini sudah memiliki contact sebelumnya.";
    } else {
        // Ambil input + sanitasi secukupnya
        $email_input = trim($_POST['email'] ?? '');
        $email = filter_var($email_input, FILTER_SANITIZE_EMAIL);
        $email_lain_input = trim($_POST['email_lain'] ?? '');
        $email_lain = $email_lain_input !== '' ? filter_var($email_lain_input, FILTER_SANITIZE_EMAIL) : '';

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
        $status = $_POST['status'] ?? 'input';

        // Simpan nilai untuk redisplay
        $old = compact(
            'email',
            'email_lain',
            'nama_perusahaan',
            'nama',
            'kategori_perusahaan',
            'kategori_jabatan',
            'jabatan_lengkap',
            'tipe',
            'no_telp1',
            'no_telp2',
            'website',
            'alamat',
            'kota',
            'status'
        );

        // Validasi minimum
        if ($email === '') {
            $errors['email'] = "Email perusahaan wajib diisi.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Format email tidak valid.";
        }
        if ($nama_perusahaan === '') {
            $errors['nama_perusahaan'] = "Nama perusahaan wajib diisi.";
        }
        if ($tipe === '') {
            $errors['tipe'] = "Tipe perusahaan wajib dipilih.";
        }
        if ($email_lain !== '' && !filter_var($email_lain, FILTER_VALIDATE_EMAIL)) {
            $errors['email_lain'] = "Format secondary email tidak valid.";
        }
        if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
            $errors['website'] = "Format URL website tidak valid.";
        }

        // Cek unik email
        if (empty($errors)) {
            $cek = $pdo->prepare("SELECT COUNT(*) FROM crm_contacts_staging WHERE email = ?");
            $cek->execute([$email]);
            if ((int)$cek->fetchColumn() > 0) {
                $errors['email'] = "Email perusahaan sudah terdaftar.";
            }
        }

        // Insert
        if (empty($errors)) {
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
                ':ditemukan_oleh' => $marketing_id,
                ':status' => in_array($status, $statuses, true) ? $status : 'input'
            ]);

            header("Location: dashboard.php");
            exit;
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Partner Dashboard</title>
    <style>
        .input-base {
            @apply w-full rounded-xl border p-3 shadow-sm transition;
        }

        .input-focus {
            @apply focus:border-blue-500 focus:ring-2 focus:ring-blue-400;
        }

        .input-error {
            @apply border-red-400 focus:ring-red-300;
        }

        .label-base {
            @apply block text-sm font-semibold text-gray-700;
        }

        .help-text {
            @apply text-xs text-gray-500 mt-1;
        }

        .error-text {
            @apply text-xs text-red-600 mt-1;
        }
    </style>
    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a'
                        },
                        dark: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a'
                        }
                    }
                }
            }
        }
    </script>

    <!-- AlpineJS -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="style.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- chart -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

    <style>
        .welcome {
            margin-bottom: 24px;
            font-size: 16px;
            color: #374151;
        }

        .table-actions .btn {
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn.email {
            background: #E0F2FE;
            color: #0369A1;
            border: 1px solid #BAE6FD;
        }

        .btn.edit {
            background: #DCFCE7;
            color: #166534;
            border: 1px solid #A7F3D0;
        }

        .btn.details {
            background: #F3E8FF;
            color: #6B21A8;
            border: 1px solid #E9D5FF;
        }

        .card {
            margin-top: 20px;
            padding: 16px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
        }

        .stats-card ul {
            list-style: none;
            padding: 0;
        }

        .stats-card li {
            padding: 4px 0;
        }

        .hidden {
            display: none;
        }

        .btn-add-contact {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
            transition: all 0.25s ease-in-out;
        }

        .btn-add-contact .icon {
            font-size: 16px;
            font-weight: bold;
        }

        .btn-add-contact:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.2);
            transform: translateY(-2px);
        }

        .btn-add-contact:active {
            background: linear-gradient(135deg, #1e3a8a, #1e40af);
            transform: translateY(0);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
        }

        .card-hint {
            font-size: 13px;
            color: #1e3a8a;
            background: #eef2ff;
            border-left: 3px solid #3b82f6;
            padding: 6px 10px;
            margin-top: 6px;
            margin-bottom: 12px;
            border-radius: 6px;
            font-style: italic;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-top: 16px;
        }

        .btn.replies {
            background: #E0E7FF;
            color: #1E40AF;
            border: 1px solid #C7D2FE;
        }

        .btn.replies:hover {
            background: #C7D2FE;
            color: #1E3A8A;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 16px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        .modal-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .modal-title {
            font-size: 24px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 12px;
        }

        .modal-text {
            font-size: 16px;
            color: #4b5563;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        /* Supaya semua elemen ikut lebar layar */
        body,
        html {
            max-width: 100%;
            overflow-x: hidden;
        }

        /* Supaya card shrink di layar kecil */
        .svc-card,
        .welcome-card {
            width: 100%;
            box-sizing: border-box;
        }

        /* Grid fleksibel */
        .svc-panel {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        /* Responsive text */
        h2,
        h3,
        h4,
        p,
        span {
            word-wrap: break-word;
        }

        /* Button full width di HP */
        @media (max-width: 640px) {

            .btn,
            button {
                width: 100%;
                margin-top: 0.5rem;
            }

            .welcome-card,
            .svc-card {
                padding: 1rem;
                font-size: 0.9rem;
            }
        }

        /* Ganti atau tambahkan ini di bagian CSS */
        .hidden {
            display: none !important;
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            max-height: 90vh;
            overflow-y: auto;
        }
    </style>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>

<body>
    <?php include("../Partials/Header.html"); ?>

    <!-- Welcome Modal untuk Marketing Baru -->
    <?php if ($showWelcomeModal): ?>
        <div id="welcomeModal" class="modal-overlay">
            <div class="modal-content">
                <div class="modal-icon">🎉</div>
                <h2 class="modal-title">Selamat Datang di CRM Dashboard!</h2>
                <p class="modal-text">
                    Halo <strong><?= htmlspecialchars($partner['name'] ?? 'Partner') ?></strong>!<br>
                    Kami melihat ini adalah pertama kalinya Anda mengakses dashboard CRM.<br>
                    Mari mulai dengan menambahkan calon client pertama Anda.
                </p>
                <div class="modal-buttons">
                    <button class="btn-secondary" onclick="closeWelcomeModal()">Nanti Saja</button>
                    <button class="btn-primary" onclick="scrollToContactForm()">Tambah Client Pertama</button>
                </div>
            </div>
        </div>
    <?php endif; ?>


    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-20 space-y-8">
        <h1 class="title">Partner Dashboard</h1>
        <p class="subtitle">Manage your contacts and send email directly from your dashboard.</p>

        <div class="welcome-card">
            <div class="welcome-icon">👋</div>
            <div class="welcome-text">
                <h2>Welcome back,
                    <span><?= htmlspecialchars($partner['name'] ?? 'Partner') ?></span>!
                </h2>
                <p>Your Marketing ID: <b><?= htmlspecialchars($partner['marketing_id']) ?></b></p>
            </div>
        </div>

        <div class="card email-format">
            <div class="flex justify-between items-center mb-4 flex-col sm:flex-row">
                <div class="welcome-text">
                    <h2>Berikut contoh form isi email:</h2>
                </div>
                <div class="flex items-center gap-2">
                    <label for="langSelect" class="text-sm">Pilih Bahasa:</label>
                    <select id="langSelect" class="border rounded p-1">
                        <option value="id">Indonesia</option>
                        <option value="en">English</option>
                    </select>
                </div>
            </div>
            <p class="card-hint mb-2">
                Email format ini yang akan digunakan saat klik send email ke calon client
            </p>

            <!-- <textarea id="emailPreview"
                class="form-control w-full"
                rows="18"
                readonly
                style="width:100%; resize:none;">
  </textarea> -->
            <div id="emailPreview"
                class="form-control w-full over"
                style="width:100%; 
            max-height: 300px;        /* Batas tinggi maksimal */
            min-height: 200px;        /* Tinggi minimal (opsional) */
            padding: 10px; 
            border: 1px solid #ced4da; 
            border-radius: 0.375rem; 
            background-color: #f8f9fa; 
            white-space: pre-wrap; 
            font-family: monospace; 
            overflow: auto;           /* Aktifkan scroll jika konten melebihi */
            resize: vertical;">
            </div>
        </div>

        <!-- Bulk Upload CSV -->
        <div class="card mt-6">
            <h3 class="text-lg font-semibold mb-3">Upload Kontak Massal (CSV)</h3>
            <div class="card mt-2">
                <h3 class="text-lg font-semibold mb-3">📋 Panduan Upload CSV</h3>
                <ul class="list-disc pl-5 text-sm text-gray-700 space-y-1">
                    <li><strong>Kolom wajib:</strong> <code>nama_perusahaan</code>, <code>email</code>, <code>no_telp1</code>, <code>tipe</code></li>
                    <li><strong>Tipe perusahaan:</strong> hanya boleh <code>Swasta</code>, <code>Bumn</code>, atau <code>Bumd</code></li>
                    <li><strong>Kategori perusahaan:</strong> pilih dari daftar yang tersedia di template</li>
                    <li><strong>Hapus baris contoh</strong> sebelum upload</li>
                    <li>Simpan file sebagai <strong>CSV UTF-8</strong></li>
                </ul>
            </div>
            <form method="POST" enctype="multipart/form-data" action="bulk_upload.php">
                <input type="file" name="csv_file" accept=".csv" required
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                <button type="submit" class="mt-3 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    Upload CSV
                </button>
            </form>
            <div class="mt-2 text-sm text-gray-600">
                <a href="download_template.php" class="text-blue-600 hover:underline font-medium">
                    📥 Download Template (CSV)
                </a>
            </div>
        </div>

        <!-- Contact List -->
        <div class="card info-card partner-list">
            <!-- Tombol Add Contact -->
            <div class="flex justify-between items-center mb-4 flex-col sm:flex-row">
                <h2 class="text-xl font-semibold">Your List</h2>
                <?php if ($isPartner): ?>
                    <p class="card-hint" style="background:#fef3c7; border-color:#f59e0b; color:#92400e;">
                        <i class="fas fa-info-circle"></i>
                        Anda login sebagai <b>Partner</b>. Fitur "Send Email" dinonaktifkan hingga diverifikasi oleh admin.
                    </p>
                <?php endif; ?>
                <p class="card-hint">
                    Daftar semua kontak yang sudah kamu input.
                    Kamu bisa <b>tambah</b>, <b>edit</b>, <b>lihat detail</b>, atau <b>kirim email</b> langsung.
                </p>
                <button type="button" onclick="window.location.href='add_contact.php'" class="btn-add-contact">
                    <span class="icon">＋</span>
                    Add Contact
                </button>
            </div>

            <div class="overflow-x-auto">
                <table id="contactsTable" class="partner-table w-full border-collapse">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Company Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Company</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contacts as $i => $c): ?>
                            <?php $emailClean = normalize_email($c['email'] ?? ''); ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($c['nama_perusahaan'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($emailClean ?: '-') ?></td>
                                <td><?= htmlspecialchars($c['no_telp1'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($c['kategori_perusahaan'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($c['status'] ?? '-') ?></td>
                                <!-- Di bagian render actions di DataTables -->
                                <td class="table-actions">
                                    <?php if (($c['status'] ?? '') === 'input' && $emailClean && empty($isPartner)): ?>
                                        <button type="button" class="btn email" onclick="previewEmail('<?= $emailClean ?>')">Send Email</button>
                                    <?php else: ?>
                                        <button class="btn email" style="opacity:0.5; cursor:not-allowed;" disabled>Send Email</button>
                                    <?php endif; ?>
                                    <!-- TAMBAH BUTTON EDIT -->
                                    <a href="javascript:void(0);" class="btn edit" onclick='openEditModal(<?= json_encode($c) ?>)'>Edit</a>
                                    <a href="javascript:void(0);" class="btn details" onclick='toggleDetails(this, <?= json_encode($c) ?>)'>Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>


        <!-- Email Modal -->
        <div id="emailPreviewModal"
            class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
            <div class="bg-white rounded-lg shadow-lg p-6 max-w-lg w-full">
                <h2 class="text-xl font-semibold mb-4">Email Preview</h2>
                <p><b>To:</b> <span id="previewEmailTo"></span></p>
                <p><b>Subject:</b> <span id="previewSubject"></span></p>
                <p class="mt-2"><b>Message:</b></p>

                <!-- Hanya bagian pesan yang discroll -->
                <div id="previewMessage"
                    class="border p-3 rounded mt-1 bg-gray-50 whitespace-pre-line 
                max-h-64 overflow-y-auto">
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button onclick="closeEmailPreview()"
                        class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                    <button id="confirmSendBtn"
                        class="px-4 py-2 bg-blue-600 text-white rounded">Send Now</button>
                </div>
            </div>
        </div>




        <!-- CRM Statistics -->
        <div class="card stats-card">
            <h2>CRM Status Overview</h2>
            <!-- Panduan -->
            <p class="card-hint">
                Statistik perkembangan kontak berdasarkan status di pipeline CRM.
                Bantu kamu memantau sejauh mana proses berjalan.
            </p>
            <div class="card-hint" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
                <?php foreach ($statusList as $status => $color): ?>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="width:14px;height:14px;background:<?= $color ?>;display:inline-block;border-radius:3px;"></span>
                        <span style="font-size:13px;"><?= htmlspecialchars($status) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="chart-container" style="width:100%; max-width:500px; margin:auto;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <!-- Company Directory -->
        <div class="card info-card">
            <h2>Company Directory</h2>
            <!-- Panduan -->
            <p class="card-hint">
                Direktori semua perusahaan dari kontak kamu.
                Klik link website untuk langsung menuju halaman perusahaan.
            </p>
            <div class="overflow-x-auto">
                <table id="companyTable" class="partner-table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Website</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>City</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contacts as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['nama_perusahaan'] ?? '-') ?></td>
                                <td><a href="<?= htmlspecialchars($c['website'] ?? '#') ?>" target="_blank">
                                        <?= htmlspecialchars($c['website'] ?? '-') ?></a></td>
                                <td><?= htmlspecialchars($c['kategori_perusahaan'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($c['tipe'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($c['kota'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>

        <!-- Details Section -->
        <div id="detailsSection" class="card info-card hidden">
            <h2>Contact & Company Details</h2>
            <p class="text-sm text-gray-500 mt-1">
                Detail lengkap kontak dan perusahaan. Klik tombol <b>Details</b> pada daftar kontak untuk melihat info di sini.
            </p>
            <p id="detailsHint"><i>Klik salah satu tombol "Details" untuk melihat data.</i></p>

            <div id="detailsContent" style="display:none;">
                <div class="card info-card">
                    <h3>Contact Person Details</h3>
                    <p><b>Company Name:</b> <span id="d_name_person"></span></p>
                    <p><b>Email:</b> <span id="d_person_email"></span></p>
                    <p><b>Phone:</b> <span id="d_phone_number"></span></p>
                    <p><b>Position Title:</b> <span id="d_position_title"></span></p>
                    <p><b>Position Category:</b> <span id="d_position_category"></span></p>
                    <p><b>Alternate Phone:</b> <span id="d_phone2"></span></p>
                </div>

                <div class="card info-card">
                    <h3>Company Info</h3>
                    <p><b>Company:</b> <span id="d_company_name"></span></p>
                    <p><b>Website:</b> <a id="d_company_website" href="#" target="_blank">-</a></p>
                    <p><b>Category:</b> <span id="d_company_category"></span></p>
                    <p><b>Type:</b> <span id="d_company_type"></span></p>
                    <p><b>Address:</b> <span id="d_address"></span>, <span id="d_city"></span> (<span id="d_postcode"></span>)</p>
                </div>
                <!-- 🔹 Back to Top Button -->
                <button id="backToTopBtn" class="btn" style="
            margin-top:16px;
            background:#2563eb;
            color:white;
            border:none;
            padding:10px 16px;
            border-radius:8px;
            cursor:pointer;
        " onclick="backToTop()">⬆ Back to Top</button>
            </div>
        </div>

        <!-- Edit Modal -->
        <div id="editModal" class="modal fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
            <div class="modal-content bg-white rounded-lg shadow-xl max-w-2xl mx-auto">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Edit Contact</h2>
                    <span class="close text-2xl cursor-pointer" onclick="closeEditModal()">&times;</span>
                </div>

                <div class="mb-4 p-4 bg-yellow-50 rounded-lg">
                    <p class="text-sm text-yellow-800">
                        <strong>Note:</strong> Status kontak tidak dapat diubah manual.
                        Status akan berubah otomatis berdasarkan progress deal dengan client.
                    </p>
                    <p class="text-sm text-yellow-800 mt-1">
                        <strong>Current Status:</strong>
                        <span id="currentStatusDisplay" class="font-semibold"></span>
                    </p>
                </div>

                <form id="editForm" method="post" action="edit_contact.php" class="space-y-4">
                    <input type="hidden" name="email" id="edit_email">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Company Information -->
                        <div class="md:col-span-2">
                            <h3 class="text-lg font-medium mb-3 text-gray-700">Company Information</h3>
                        </div>

                        <div>
                            <label for="edit_nama_perusahaan" class="block mb-2 font-medium">Company Name *</label>
                            <input type="text" name="nama_perusahaan" id="edit_nama_perusahaan"
                                class="w-full border rounded p-3" required>
                        </div>

                        <div>
                            <label for="edit_kategori_perusahaan" class="block mb-2 font-medium">Company Category</label>
                            <select name="kategori_perusahaan" id="edit_kategori_perusahaan"
                                class="w-full border rounded p-3">
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
                            <label for="edit_tipe" class="block mb-2 font-medium">Company Type *</label>
                            <select name="tipe" id="edit_tipe" class="w-full border rounded p-3" required>
                                <option value="">-- Select Type --</option>
                                <option value="Swasta">Swasta</option>
                                <option value="Bumn">Bumn</option>
                                <option value="Bumd">Bumd</option>
                            </select>
                        </div>

                        <div>
                            <label for="edit_website" class="block mb-2 font-medium">Website</label>
                            <input type="url" name="website" id="edit_website"
                                class="w-full border rounded p-3" placeholder="https://...">
                        </div>

                        <!-- Contact Person Information -->
                        <div class="md:col-span-2 mt-4">
                            <h3 class="text-lg font-medium mb-3 text-gray-700">Contact Person Information</h3>
                        </div>

                        <div>
                            <label for="edit_nama" class="block mb-2 font-medium">Contact Name</label>
                            <input type="text" name="nama" id="edit_nama"
                                class="w-full border rounded p-3">
                        </div>

                        <div>
                            <label for="edit_jabatan_lengkap" class="block mb-2 font-medium">Position Title</label>
                            <input type="text" name="jabatan_lengkap" id="edit_jabatan_lengkap"
                                class="w-full border rounded p-3">
                        </div>

                        <div>
                            <label for="edit_kategori_jabatan" class="block mb-2 font-medium">Position Category</label>
                            <input type="text" name="kategori_jabatan" id="edit_kategori_jabatan"
                                class="w-full border rounded p-3">
                        </div>

                        <!-- 🔹 New Email Field -->
                        <div>
                            <label for="edit_new_email" class="block mb-2 font-medium">Email *</label>
                            <input type="email" name="new_email" id="edit_new_email"
                                class="w-full border rounded p-3" required>
                        </div>

                        <div>
                            <label for="edit_email_lain" class="block mb-2 font-medium">Secondary Email</label>
                            <input type="email" name="email_lain" id="edit_email_lain"
                                class="w-full border rounded p-3">
                        </div>

                        <!-- Contact Details -->
                        <div class="md:col-span-2 mt-4">
                            <h3 class="text-lg font-medium mb-3 text-gray-700">Contact Details</h3>
                        </div>

                        <div>
                            <label for="edit_no_telp1" class="block mb-2 font-medium">Primary Phone *</label>
                            <input type="text" name="no_telp1" id="edit_no_telp1"
                                class="w-full border rounded p-3" required>
                        </div>

                        <div>
                            <label for="edit_no_telp2" class="block mb-2 font-medium">Alternate Phone</label>
                            <input type="text" name="no_telp2" id="edit_no_telp2"
                                class="w-full border rounded p-3">
                        </div>

                        <!-- Address Information -->
                        <div class="md:col-span-2">
                            <label for="edit_alamat" class="block mb-2 font-medium">Address</label>
                            <textarea name="alamat" id="edit_alamat"
                                class="w-full border rounded p-3" rows="3"></textarea>
                        </div>

                        <div>
                            <label for="edit_kota" class="block mb-2 font-medium">City</label>
                            <input type="text" name="kota" id="edit_kota"
                                class="w-full border rounded p-3">
                        </div>
                    </div>

                    <div class="flex gap-3 pt-4 border-t">
                        <button type="button" onclick="closeEditModal()"
                            class="flex-1 bg-gray-300 text-gray-700 py-3 px-4 rounded hover:bg-gray-400 transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="flex-1 bg-green-600 text-white py-3 px-4 rounded hover:bg-green-700 transition">
                            Save Changes
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        function linkify(text) {
            // Regex untuk mencocokkan URL (sederhana tapi efektif)
            const urlRegex = /(https?:\/\/[^\s<>"{}|\\^`\[\]]+)/gi;
            return text.replace(urlRegex, url => `<a href="${url}" target="_blank" style="color:#2563eb; text-decoration:underline;">${url}</a>`);
        }

        // Fungsi untuk modal welcome
        function closeWelcomeModal() {
            const modal = document.getElementById('welcomeModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        function scrollToContactForm() {
            window.location.href = 'add_contact.php';
        }

        // Tutup modal jika klik di luar konten modal
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('welcomeModal');
            if (modal && event.target === modal) {
                closeWelcomeModal();
            }
        });


        $(document).ready(function() {
            var table = $('#contactsTable').DataTable({
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
                ordering: false, // ⛔ disable sorting di semua kolom
                columnDefs: [{
                    orderable: false,
                    targets: "_all" // pastikan semua kolom tidak bisa di-sort
                }]
            });

            var companyTable = $('#companyTable').DataTable({
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
                order: false
            });

            // 🔹 Sinkronisasi pagination
            table.on('page.dt', function() {
                var pageInfo = table.page.info();
                companyTable.page(pageInfo.page).draw(false);
            });

            companyTable.on('page.dt', function() {
                var pageInfo = companyTable.page.info();
                table.page(pageInfo.page).draw(false);
            });

            // 🔹 Search by header click (khusus contactsTable)
            $('#contactsTable thead th').each(function(index) {
                var th = $(this);
                if (th.text() === "#" || th.text() === "Actions") return;

                th.css("cursor", "pointer");

                th.on("click", function() {
                    // Kalau input sudah ada, jangan bikin lagi
                    if (th.find(".header-search").length > 0) return;

                    // Simpan teks asli header
                    var currentText = th.text();

                    // Bungkus teks header + input container
                    th.html('<div style="position:relative; display:flex; flex-direction:column; align-items:center;">' +
                        '<span class="header-title">' + currentText + '</span>' +
                        '<input type="text" class="header-search" placeholder="Search..." ' +
                        'style="margin-top:4px; padding:3px 6px; font-size:12px; width:90%; border:1px solid #ccc; border-radius:4px;" />' +
                        '</div>');

                    var input = th.find(".header-search");
                    input.focus();

                    input.on("keyup change clear", function() {
                        table.column(index).search(this.value).draw();
                    });

                    // Kalau blur dan kosong → hapus input, balikin teks header
                    input.on("blur", function() {
                        if (this.value === "") {
                            th.html(currentText);
                        }
                    });
                });
            });
        });

        let activeButton = null;

        function toggleDetails(btn, contact) {
            const section = document.getElementById("detailsSection");
            const hint = document.getElementById("detailsHint");
            const content = document.getElementById("detailsContent");

            if (activeButton === btn) {
                section.classList.add("hidden");
                btn.textContent = "Details";
                activeButton = null;
                return;
            }
            if (activeButton) activeButton.textContent = "Details";

            document.getElementById("d_name_person").textContent = contact.nama || "-";
            document.getElementById("d_person_email").textContent = contact.email || "-";
            document.getElementById("d_phone_number").textContent = contact.no_telp1 || "-";
            document.getElementById("d_position_title").textContent = contact.jabatan_lengkap || "-";
            document.getElementById("d_position_category").textContent = contact.kategori_jabatan || "-";
            document.getElementById("d_phone2").textContent = contact.no_telp2 || "-";

            document.getElementById("d_company_name").textContent = contact.nama_perusahaan || "-";
            document.getElementById("d_company_website").textContent = contact.website || "-";
            document.getElementById("d_company_website").href = contact.website || "#";
            document.getElementById("d_company_category").textContent = contact.kategori_perusahaan || "-";
            document.getElementById("d_company_type").textContent = contact.tipe || "-";
            document.getElementById("d_address").textContent = contact.alamat || "-";
            document.getElementById("d_city").textContent = contact.kota || "-";



            section.classList.remove("hidden");
            hint.style.display = "none";
            content.style.display = "block";

            btn.textContent = "Hide Details";
            activeButton = btn;
            section.scrollIntoView({
                behavior: "smooth"
            });

        }

        // Function untuk buka modal edit
        function openEditModal(contact) {
            document.getElementById('edit_email').value = contact.email; // hidden input
            document.getElementById('edit_nama_perusahaan').value = contact.nama_perusahaan;
            document.getElementById('edit_kategori_perusahaan').value = contact.kategori_perusahaan;
            document.getElementById('edit_tipe').value = contact.tipe;
            document.getElementById('edit_website').value = contact.website;
            document.getElementById('edit_nama').value = contact.nama;
            document.getElementById('edit_jabatan_lengkap').value = contact.jabatan_lengkap;
            document.getElementById('edit_kategori_jabatan').value = contact.kategori_jabatan;
            document.getElementById('edit_email_lain').value = contact.email_lain;
            document.getElementById('edit_no_telp1').value = contact.no_telp1;
            document.getElementById('edit_no_telp2').value = contact.no_telp2;
            document.getElementById('edit_alamat').value = contact.alamat;
            document.getElementById('edit_kota').value = contact.kota;

            // 👇 Tambahin baris ini supaya input new_email auto isi current email
            document.getElementById('edit_new_email').value = contact.email;

            // buka modal
            document.getElementById('editModal').classList.remove('hidden');
        }


        // Function untuk tutup modal edit
        function closeEditModal() {
            document.getElementById("editModal").classList.add("hidden");
        }

        // Handle form submission untuk edit
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            // Validasi client-side
            const companyName = document.getElementById('edit_nama_perusahaan').value.trim();
            const phone = document.getElementById('edit_no_telp1').value.trim();
            const companyType = document.getElementById('edit_tipe').value;

            if (!companyName) {
                Swal.fire('Error!', 'Company Name is required.', 'error');
                return;
            }

            if (!phone) {
                Swal.fire('Error!', 'Primary Phone is required.', 'error');
                return;
            }

            if (!companyType) {
                Swal.fire('Error!', 'Company Type is required.', 'error');
                return;
            }

            // Tampilkan loading
            Swal.fire({
                title: 'Updating...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Kirim data ke server
            fetch('edit_contact.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Contact updated successfully.',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            closeEditModal();
                            // Refresh halaman untuk melihat perubahan
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.error || 'Failed to update contact.',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'System error occurred: ' + error.message,
                        confirmButtonText: 'OK'
                    });
                });
        });

        function backToTop() {
            // Scroll ke atas dengan smooth
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });

            // Hide details section
            const section = document.getElementById("detailsSection");
            section.classList.add("hidden");

            // Reset tombol Details yang aktif
            if (activeButton) {
                activeButton.textContent = "Details";
                activeButton = null;
            }
        }


        const statusList = <?= json_encode($statusList) ?>;
        const statusData = <?= json_encode($stats) ?>;

        // Map hasil query ke object {status: total}
        const dataMap = {};
        statusData.forEach(s => dataMap[s.status] = s.total);

        // Susun labels dan data sesuai urutan statusList
        const labels = Object.keys(statusList);
        const data = labels.map(s => dataMap[s] || 0);
        const colors = Object.values(statusList);

        const ctx = document.getElementById('statusChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Kontak',
                    data: data,
                    backgroundColor: colors,
                    borderRadius: 6,
                    maxBarThickness: 40
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Statistik Status CRM',
                        font: {
                            size: 18,
                            weight: 'bold'
                        },
                        padding: {
                            top: 10,
                            bottom: 20
                        }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        color: '#111',
                        font: {
                            weight: 'bold'
                        },
                        formatter: value => value
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        },
                        title: {
                            display: true,
                            text: 'Jumlah Kontak',
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });


        function previewEmail(email) {
            $.getJSON("email_preview.php", {
                email: email
            }, function(res) {
                if (res.error) {
                    Swal.fire("Error", res.error, "error");
                    return;
                }

                document.getElementById('previewEmailTo').textContent = res.email;
                document.getElementById('previewSubject').textContent = res.subject;
                document.getElementById('previewMessage').innerHTML = res.body;

                document.getElementById('confirmSendBtn').onclick = function() {
                    window.location.href = 'send_email.php?email=' + encodeURIComponent(email);
                };

                document.getElementById('emailPreviewModal').classList.remove('hidden');
            });
        }

        function closeEmailPreview() {
            const modal = document.getElementById("emailPreviewModal"); // pastikan ID modal sesuai
            if (modal) {
                modal.classList.add("hidden"); // sembunyikan modal
            }
        }

        const emailTemplates = {
            id: `Kepada Yth. Bapak/Ibu [Nama Calon Client]
[Jabatan Calon Client]
[Nama PT Calon Client]

Perkenalkan, saya [Nama Anda sebagai Marketing] dari PT Rayterton Indonesia.

Kami menawarkan solusi software IT dan bisnis dengan prinsip 100% Tanpa Risiko. 
Software kami bersifat bestfit yang sepenuhnya bisa disesuaikan dengan proses dan kebutuhan bisnis Anda. 
Tim kami dapat melakukan kustomisasi tanpa batas hingga benar-benar sesuai, semua tanpa biaya atau perjanjian di muka. 
Proposal baru akan dikirim setelah software kami lolos uji (User Acceptance Testing / UAT) dan siap untuk go live di perusahaan Anda. 100% Risk Free. 
Tersedia untuk berbagai industri seperti Banking, Multifinance, Insurance, Manufacturing, Retail, Distribution, Government/Ministry/BUMN/BUMD, 
Oil and Gas, Transportation & Logistics, Hotels and Hospitality, Travel, Property, dan lainnya.

https://www.rayterton.com

Kami juga menyediakan jasa IT Consulting dan Management Consulting 100% Risk Free. 
Dokumen konsultasi di awal (sebelum eksekusi) dapat Anda peroleh tanpa biaya di muka dan tanpa perjanjian terlebih dahulu.

https://www.rayterton.com/it-consulting.php
https://www.rayterton.com/management-consulting.php

Selain itu, kami menyediakan Rayterton Academy, program training pelatihan praktis IT, Business and Finance, Entrepreneurship, Leadership, serta Management dan Career, 
yang membantu meningkatkan kompetensi tim Anda dalam lingkungan bisnis digital yang terus berkembang. 
Program ini juga membantu mempercepat perkembangan karir Anda dan rekan-rekan Anda.

https://www.raytertonacademy.com

Sebagai apresiasi, kami berikan kode promo berikut:
Kode Promo: [Kode Promo]
Gunakan kode ini untuk mendapatkan penawaran khusus dari kami.

Hormat kami,
[Nama Anda sebagai Marketing]
Marketing Consultant/Partner
PT Rayterton Indonesia`,

            en: `Dear Mr./Ms. [Client Name]
[Client Position]
[Client Company Name]

My name is [Your Name as Marketing] from PT Rayterton Indonesia.

We provide software solutions, IT consulting, and management consulting under a 100% Risk-Free principle. 
Our best-fit software can be fully customized to your business processes and requirements. 
Unlimited customization until it perfectly fits—no upfront cost or agreement. 
A proposal will only be sent after our software passes User Acceptance Testing (UAT) and is ready to go live. 
This service is available for various industries such as Banking, Multifinance, Insurance, Manufacturing, Retail, Distribution, Government/Ministry/State-Owned Enterprises, 
Oil and Gas, Transportation & Logistics, Hotels and Hospitality, Travel, Property, and more.

https://www.rayterton.com

We also provide IT Consulting and Management Consulting 100% Risk Free. 
Initial consulting documents (before execution) are available without any upfront cost or agreement.

https://www.rayterton.com/it-consulting.php
https://www.rayterton.com/management-consulting.php

Additionally, Rayterton Academy offers practical training in IT, Business and Finance, Entrepreneurship, Leadership, as well as Management and Career development, 
helping to enhance your team's competence in today's evolving digital business environment. 
This program also accelerates career growth for you and your colleagues.

https://www.raytertonacademy.com

Promo Code: [Promo Code]
Use this code to access our special offer.

Best regards,
[Your Name as Marketing]
Marketing Consultant/Partner
PT Rayterton Indonesia`
        };

        const langSelect = document.getElementById("langSelect");
        const emailPreviewDiv = document.getElementById("emailPreview");

        function updateEmailPreview(lang) {
            const template = emailTemplates[lang] || emailTemplates['id'];
            emailPreviewDiv.innerHTML = linkify(template);
        }

        // Set awal ke bahasa Indonesia
        updateEmailPreview('id');

        // Saat ganti bahasa
        langSelect.addEventListener("change", (e) => {
            updateEmailPreview(e.target.value);
        });
    </script>

    <?php if (!empty($_SESSION['bulk_upload_result'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const res = <?= json_encode($_SESSION['bulk_upload_result']) ?>;
                let title, html, icon;

                if (res.error) {
                    // Kasus error khusus (file kosong, tidak ada data, dll)
                    title = 'Upload Gagal';
                    html = `<p><strong>${res.error}</strong></p>`;
                    if (res.detail) {
                        html += `<p class="text-sm text-gray-600 mt-2">${res.detail}</p>`;
                    }
                    icon = 'error';
                } else {
                    // Kasus berhasil/gagal sebagian
                    const success = res.success || 0;
                    const invalid = res.failed_invalid || 0;
                    const duplicate = res.failed_duplicate || 0;
                    const totalFailed = invalid + duplicate;

                    if (success === 0 && totalFailed > 0) {
                        // ❌ Tidak ada yang berhasil → beri pesan "Gagal"
                        title = 'Upload Gagal';
                        html = `<div style="text-align:left; line-height:1.6;">
                <p><strong>Tidak ada kontak yang berhasil diupload.</strong></p>`;
                        if (duplicate > 0) {
                            html += `<p><strong>Gagal (duplikat):</strong> ${duplicate} email sudah terdaftar</p>`;
                        }
                        if (invalid > 0) {
                            html += `<p><strong>Gagal (data tidak valid):</strong> ${invalid} baris</p>`;
                        }
                        html += `</div>`;
                        icon = 'error';
                    } else if (success > 0 && totalFailed === 0) {
                        // ✅ Semua berhasil
                        title = 'Upload Berhasil!';
                        html = `<p>Semua data berhasil diupload.</p><p><strong>Berhasil:</strong> ${success} kontak</p>`;
                        icon = 'success';
                    } else if (success > 0 && totalFailed > 0) {
                        // ⚠️ Sebagian berhasil
                        title = 'Upload Sebagian Berhasil';
                        html = `<div style="text-align:left; line-height:1.6;">
                <p><strong>Berhasil:</strong> ${success} kontak</p>`;
                        if (duplicate > 0) {
                            html += `<p><strong>Gagal (duplikat):</strong> ${duplicate} email sudah terdaftar</p>`;
                        }
                        if (invalid > 0) {
                            html += `<p><strong>Gagal (data tidak valid):</strong> ${invalid} baris</p>`;
                        }
                        html += `</div>`;
                        icon = 'warning';
                    } else {
                        // 🤷‍♂️ Tidak ada data diproses (misal: file hanya header)
                        title = 'Tidak Ada Data Diproses';
                        html = 'File tidak mengandung data valid.';
                        icon = 'info';
                    }
                }

                Swal.fire({
                    icon: icon,
                    title: title,
                    html: html,
                    confirmButtonText: 'OK'
                });
            });
        </script>
        <?php unset($_SESSION['bulk_upload_result']); ?>
    <?php endif; ?>
</body>

</html>