<?php
session_start();
if (!empty($_SESSION['bulk_msg'])): ?>
    <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6">
        <?= htmlspecialchars($_SESSION['bulk_msg']) ?>
    </div>
    <?php unset($_SESSION['bulk_msg']); ?>
<?php endif;
require '../../Model/db.php';

// Kalau belum login, tendang ke login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

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

// Cek apakah user role admin
$partner = $_SESSION['user'];
if (($partner['role'] ?? '') !== 'admin') {
    die("Akses ditolak: hanya admin yang bisa masuk dashboard ini.");
}

// 🔹 AMBIL DATA UNTUK COMPANY DIRECTORY (limited untuk performance)
// // 🔹 AMBIL SEMUA DATA TANPA FILTER
// $companyStmt = $pdo->query("
//     SELECT 
//         COALESCE(NULLIF(nama_perusahaan, ''), 'No Company Name') as nama_perusahaan, 
//         COALESCE(NULLIF(website, ''), '-') as website,
//         COALESCE(NULLIF(kategori_perusahaan, ''), '-') as kategori_perusahaan, 
//         COALESCE(NULLIF(tipe, ''), '-') as tipe, 
//         COALESCE(NULLIF(kota, ''), '-') as kota
//     FROM crm_contacts_staging 
//     ORDER BY nama_perusahaan
// ");
// $companyContacts = $companyStmt->fetchAll(PDO::FETCH_ASSOC);

// // Pagination setup
// $limit = 20;
// $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
// if ($page < 1) $page = 1;
// $offset = ($page - 1) * $limit;

// // Ambil contact dengan pagination
// $sql = "
//     SELECT *
//     FROM crm_contacts_staging
//     ORDER BY FIELD(
//         status,
//         'input',
//         'emailed',
//         'contacted',
//         'presentation',
//         'NDA process',
//         'Gap analysis / requirement analysis',
//         'SIT (System Integration Testing)',
//         'UAT (User Acceptance Testing)',
//         'Proposal',
//         'Negotiation',
//         'Deal / Closed',
//         'Failed / Tidak Lanjut',
//         'Postpone'
//     ) ASC
//     LIMIT :limit OFFSET :offset
// ";
// $stmt = $pdo->prepare($sql);
// $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
// $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
// $stmt->execute();
// $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// // Hitung total data (untuk pagination link)
// $totalStmt = $pdo->query("SELECT COUNT(*) FROM crm_contacts_staging");
// $totalContacts = $totalStmt->fetchColumn();
// $totalPages = ceil($totalContacts / $limit);

// Statistik status (tanpa filter marketing_id)
$statsStmt = $pdo->query("
    SELECT 
        CASE 
            WHEN status IS NULL OR status = '' THEN 'Belum di beri status' 
            ELSE status 
        END as status,
        COUNT(*) as total 
    FROM crm_contacts_staging 
    GROUP BY 
        CASE 
            WHEN status IS NULL OR status = '' THEN 'Belum di beri status' 
            ELSE status 
        END
");
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

// 🔹 AMBIL DATA MARKETING & PARTNER PERFORMANCE (jumlah CRM per penemu)
$marketingStmt = $pdo->query("
    SELECT 
        COALESCE(
            u.name,
            pi.nama_lengkap,
            pin.nama_institusi
        ) AS display_name,
        c.ditemukan_oleh AS partner_id,
        COUNT(c.email) AS total_crm
    FROM crm_contacts_staging c
    LEFT JOIN users u 
        ON u.marketing_id = c.ditemukan_oleh COLLATE utf8mb4_general_ci
    LEFT JOIN partner_individual pi 
        ON pi.promo_code = c.ditemukan_oleh COLLATE utf8mb4_general_ci
    LEFT JOIN partner_institution pin 
        ON pin.kode_institusi = c.ditemukan_oleh COLLATE utf8mb4_general_ci
    WHERE c.ditemukan_oleh IS NOT NULL
    GROUP BY c.ditemukan_oleh
    ORDER BY total_crm DESC
");
$marketingData = $marketingStmt->fetchAll(PDO::FETCH_ASSOC);

// 🔹 AMBIL DATA KATEGORI PERUSAHAAN PER PARTNER
$categoryStmt = $pdo->query("
    SELECT 
        COALESCE(u.name, pi.nama_lengkap, pin.nama_institusi) AS display_name,
        cat.ditemukan_oleh AS partner_id,
        SUM(cat.total_per_category) AS total_category,
        GROUP_CONCAT(CONCAT(cat.kategori_perusahaan, ' (', cat.total_per_category, ')') SEPARATOR ', ') AS category_details
    FROM (
        SELECT 
            c.ditemukan_oleh,
            c.kategori_perusahaan,
            COUNT(c.email) AS total_per_category
        FROM crm_contacts_staging c
        WHERE c.ditemukan_oleh IS NOT NULL
        GROUP BY c.ditemukan_oleh, c.kategori_perusahaan
    ) cat
    LEFT JOIN users u 
        ON u.marketing_id = cat.ditemukan_oleh COLLATE utf8mb4_general_ci
    LEFT JOIN partner_individual pi 
        ON pi.promo_code = cat.ditemukan_oleh COLLATE utf8mb4_general_ci
    LEFT JOIN partner_institution pin 
        ON pin.kode_institusi = cat.ditemukan_oleh COLLATE utf8mb4_general_ci
    GROUP BY cat.ditemukan_oleh
    ORDER BY total_category DESC
");
$categoryData = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// 🔹 AMBIL DATA TIPE PERUSAHAAN PER PARTNER
$typeStmt = $pdo->query("
    SELECT 
        COALESCE(u.name, pi.nama_lengkap, pin.nama_institusi) AS display_name,
        t.ditemukan_oleh AS partner_id,
        SUM(t.total_per_type) AS total_type,
        GROUP_CONCAT(CONCAT(t.tipe, ' (', t.total_per_type, ')') SEPARATOR ', ') AS type_details
    FROM (
        SELECT 
            c.ditemukan_oleh,
            c.tipe,
            COUNT(c.email) AS total_per_type
        FROM crm_contacts_staging c
        WHERE c.ditemukan_oleh IS NOT NULL
        GROUP BY c.ditemukan_oleh, c.tipe
    ) t
    LEFT JOIN users u 
        ON u.marketing_id = t.ditemukan_oleh COLLATE utf8mb4_general_ci
    LEFT JOIN partner_individual pi 
        ON pi.promo_code = t.ditemukan_oleh COLLATE utf8mb4_general_ci
    LEFT JOIN partner_institution pin 
        ON pin.kode_institusi = t.ditemukan_oleh COLLATE utf8mb4_general_ci
    GROUP BY t.ditemukan_oleh
    ORDER BY total_type DESC
");
$typeData = $typeStmt->fetchAll(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Partner Dashboard</title>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- chart -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
    <style>
        .welcome {
            margin-bottom: 24px;
            font-size: 16px;
            color: #374151;
        }

        .table-actions {
            display: flex;
            flex-direction: row;
            /* sejajarkan horizontal */
            gap: 6px;
            /* jarak antar tombol */
            flex-wrap: nowrap;
            /* jangan dibungkus ke bawah */
            justify-content: center;
        }

        .table-actions .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 70px;
            /* biar seragam */
            text-align: center;
            padding: 4px 8px;
            /* lebih kecil dari sebelumnya */
            font-size: 12px;
            font-weight: 500;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            line-height: 1.2;
        }

        .btn.email {
            background: #E0F2FE;
            color: #0369A1;
            border: 1px solid #BAE6FD;
        }

        .btn.status {
            background: #FEF9C3;
            color: #92400E;
            border: 1px solid #FDE68A;
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
            /* dari 8px → lebih rapat */
            padding: 10px 16px;
            /* dari 12px 20px → lebih kecil */
            font-size: 14px;
            /* dari 15px → lebih kecil */
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: none;
            border-radius: 10px;
            /* dari 12px → lebih ramping */
            cursor: pointer;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
            transition: all 0.25s ease-in-out;
        }

        .btn-add-contact .icon {
            font-size: 16px;
            /* dari 18px → lebih kecil */
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
            /* biru tua biar kontras */
            background: #eef2ff;
            /* biru muda lembut sebagai highlight */
            border-left: 3px solid #3b82f6;
            /* garis tegas di kiri */
            padding: 6px 10px;
            /* kasih ruang biar nyaman */
            margin-top: 6px;
            margin-bottom: 12px;
            border-radius: 6px;
            /* sudut agak membulat */
            font-style: italic;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-top: 16px;
        }

        .btn.delete {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
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

        /* Loading spinner */
        .spinner-border {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 0.2em solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner-border .75s linear infinite;
        }

        @keyframes spinner-border {
            to {
                transform: rotate(360deg);
            }
        }

        .btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .partner-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            text-align: left;
        }

        .partner-table th,
        .partner-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            /* disamakan */
            font-size: 13px;
            /* lebih kecil biar muat */
            text-align: center;
            /* biar tombol & isi sel rata */
            vertical-align: middle;
            white-space: nowrap;
        }

        .partner-table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }

        .partner-table tbody tr:nth-child(even) {
            background: #f3f4f6;
            /* zebra strip */
        }
    </style>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>

<body>

    <?php include("../Partials/Header.html"); ?>

    <div class="max-w-6xl mx-auto p-6 space-y-6 mt-16">
        <h1 class="title">Admin Dashboard</h1>
        <p class="subtitle">Manage all contacts from CRM across all partners.</p>

        <div class="welcome-card">
            <div class="welcome-icon">👑</div>
            <div class="welcome-text">
                <h2>Welcome back,
                    <span><?= htmlspecialchars($partner['name'] ?? 'Admin') ?></span>!
                </h2>
                <p>You are logged in as <b>Administrator</b>.</p>
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

        <div class="card info-card partner-list">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 class="text-xl font-semibold">CRM Contacts</h2>
                    <p class="card-hint">
                        Kelola kontak berdasarkan jenis: calon client atau calon partner.
                        Kamu bisa <b>edit</b>, <b>lihat detail</b>, atau <b>kirim email</b> langsung.
                    </p>
                </div>
                <button type="button" onclick="window.location.href='add_contact.php'" class="btn-add-contact">
                    <span class="icon">＋</span>
                    Add Contact
                </button>
            </div>

            <!-- 🔹 Tabs: Tambahkan di sini, sebelum tabel -->
            <ul class="nav nav-tabs mb-3" id="contactTypeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="client-tab" data-bs-toggle="tab" data-bs-target="#calon-client" type="button" role="tab" aria-selected="true">
                        Calon Client
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="partner-tab" data-bs-toggle="tab" data-bs-target="#calon-partner" type="button" role="tab" aria-selected="false">
                        Calon Partner
                    </button>
                </li>
            </ul>

            <!-- 🔹 Tabel tetap satu, di luar tab content (lebih aman) -->
            <div class="overflow-x-auto">
                <table id="contactsTable" class="partner-table w-full border-collapse">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Company Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Category</th>
                            <th>Found By</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
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

        <!-- Marketing Performance -->
        <div class="card stats-card" id="marketingCard">
            <h2>CRM Prospect Overview</h2>
            <p class="card-hint">
                Jumlah kontak CRM yang ditemukan oleh marketing dan partner (individual/institusi).
                Diurutkan dari yang paling banyak.
            </p>
            <div class="overflow-x-auto">
                <div class="chart-container" style="width: max-content; min-width: 100%; height: 250px; padding: 10px;">
                    <canvas id="marketingChart"></canvas>
                </div>
            </div>
            <?php if (count($marketingData) > 5): ?>
                <div class="text-center mt-4">
                    <button id="toggleMarketingChart" class="btn-add-contact" style="padding:8px 16px; font-size:13px;">
                        <span class="icon">🔽</span>
                        <span id="toggleText">Show More</span>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Company Category Performance -->
        <div class="card stats-card" id="categoryCard">
            <h2>Company Category Overview</h2>
            <p class="card-hint">
                Jumlah CRM berdasarkan kategori perusahaan, ditampilkan per marketing/partner.
            </p>
            <div class="overflow-x-auto">
                <div class="chart-container" style="width: max-content; min-width: 100%; height: 250px; padding: 10px;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
            <?php if (count($categoryData) > 5): ?>
                <div class="text-center mt-4">
                    <button id="toggleCategoryChart" class="btn-add-contact" style="padding:8px 16px; font-size:13px;">
                        <span class="icon">🔽</span>
                        <span id="toggleCategoryText">Show More</span>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Company Type Performance -->
        <div class="card stats-card" id="typeCard">
            <h2>Company Type Overview</h2>
            <p class="card-hint">
                Jumlah CRM berdasarkan tipe perusahaan, ditampilkan per marketing/partner.
            </p>
            <div class="overflow-x-auto">
                <div class="chart-container" style="width: max-content; min-width: 100%; height: 250px; padding: 10px;">
                    <canvas id="typeChart"></canvas>
                </div>
            </div>
            <?php if (count($typeData) > 5): ?>
                <div class="text-center mt-4">
                    <button id="toggleTypeChart" class="btn-add-contact" style="padding:8px 16px; font-size:13px;">
                        <span class="icon">🔽</span>
                        <span id="toggleTypeText">Show More</span>
                    </button>
                </div>
            <?php endif; ?>
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

        <!-- Status Modal -->
        <div id="statusModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeStatusModal()">&times;</span>
                <h2>Set Client Prospect Status</h2>
                <form id="statusForm" method="post" action="set_status.php">
                    <input type="hidden" name="email" id="statusEmail">
                    <label for="status">Status:</label>
                    <select name="status" id="statusSelect" required>
                        <option value="">-- Pilih Status --</option>
                        <option value="input">Input</option>
                        <option value="emailed">Emailed</option>
                        <option value="contacted">Contacted</option>
                        <option value="presentation">Presentation</option>
                        <option value="NDA process">NDA Process</option>
                        <option value="Gap analysis / requirement analysis">Gap Analysis / Requirement Analysis</option>
                        <option value="Customization">Customization</option>
                        <option value="SIT (System Integration Testing)">SIT (System Integration Testing)</option>
                        <option value="UAT (User Acceptance Testing)">UAT (User Acceptance Testing)</option>
                        <option value="Proposal">Proposal</option>
                        <option value="Negotiation">Negotiation</option>
                        <option value="Deal / Closed">Deal / Closed</option>
                        <option value="Failed / Tidak Lanjut">Failed / Tidak Lanjut</option>
                        <option value="Postpone">Postpone</option>
                    </select>
                    <button type="submit" class="btn status">Update Status</button>
                </form>
            </div>
        </div>


        <script>
            function linkify(text) {
                const urlRegex = /(https?:\/\/[^\s<>"{}|\\^`\[\]]+)/gi;
                return text.replace(urlRegex, url => `<a href="${url}" target="_blank" style="color:#2563eb; text-decoration:underline;">${url}</a>`);
            }

            function confirmDelete(email) {
                if (!email || email === '-') {
                    Swal.fire('Error', 'Email tidak valid untuk dihapus.', 'error');
                    return;
                }
                Swal.fire({
                    title: 'Yakin hapus kontak ini?',
                    text: "Data ini akan dihapus permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post('delete_contact.php', {
                            email: email
                        }, function(response) {
                            let res = JSON.parse(response);
                            if (res.success) {
                                Swal.fire('Terhapus!', 'Kontak berhasil dihapus.', 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Gagal!', res.error || 'Terjadi kesalahan.', 'error');
                            }
                        }).fail(function() {
                            Swal.fire('Error', 'Gagal menghubungi server.', 'error');
                        });
                    }
                });
            }
            $(document).ready(function() {
                let currentPage = 0;
                let isSyncing = false;
                let contactsTable = null;
                let companyTable = null;
                let currentFlag = 'CLT'; // 🔁 default ke CLT

                function syncPagination(sourceTable, targetTable, newPage) {
                    if (isSyncing) return;
                    isSyncing = true;
                    currentPage = newPage;
                    if (targetTable && targetTable.page() !== newPage) {
                        targetTable.page(newPage).draw('page');
                    }
                    setTimeout(() => {
                        isSyncing = false;
                    }, 100);
                }

                function loadContactsTable(flag) {
                    currentFlag = flag;
                    if (contactsTable) {
                        contactsTable.destroy();
                        $('#contactsTable tbody').empty();
                    }
                    contactsTable = $('#contactsTable').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: 'server_processing.php',
                            type: 'POST',
                            data: function(d) {
                                d.flag = flag; // 🔑 kirim CLT atau MKT ke backend
                            }
                        },
                        ordering: false,
                        columns: [{
                                data: null,
                                name: 'DT_RowIndex',
                                orderable: false,
                                searchable: false,
                                render: function(data, type, row, meta) {
                                    return meta.row + meta.settings._iDisplayStart + 1;
                                }
                            },
                            {
                                data: 'nama_perusahaan',
                                name: 'nama_perusahaan',
                                render: function(data) {
                                    return data || '-';
                                }
                            },
                            {
                                data: 'email',
                                name: 'email',
                                render: function(data) {
                                    return data || '-';
                                }
                            },
                            {
                                data: 'no_telp1',
                                name: 'no_telp1',
                                render: function(data) {
                                    return data || '-';
                                }
                            },
                            {
                                data: 'kategori_perusahaan',
                                name: 'kategori_perusahaan',
                                render: function(data) {
                                    return data || '-';
                                }
                            },
                            {
                                data: 'ditemukan_oleh',
                                name: 'ditemukan_oleh',
                                render: function(data) {
                                    return data || '-';
                                }
                            },
                            {
                                data: 'status',
                                name: 'status',
                                render: function(data) {
                                    return data || '-';
                                }
                            },
                            {
                                data: 'actions',
                                name: 'actions',
                                orderable: false,
                                searchable: false,
                                width: '220px',
                                render: function(data, type, row) {
                                    let emailBtn = '';
                                    if (row.status === 'input' && row.email) {
                                        emailBtn = `<a href="send_email.php?email=${encodeURIComponent(row.email)}" class="btn email">Send Email</a>`;
                                    } else {
                                        emailBtn = `<button class="btn email disabled" disabled>Send Email</button>`;
                                    }
                                    const emailForModal = row.email ? row.email.replace(/'/g, "\\'") : '';
                                    const emailForDelete = row.email ? row.email.replace(/"/g, '\\"') : '';
                                    return `
                            <div class="table-actions">
                                ${emailBtn}
                                <button class="btn status" onclick="openStatusModal('${emailForModal}')">
                                    Set Client Prospect Status
                                </button>
                                <a href="javascript:void(0);" class="btn details" onclick='toggleDetails(this, ${JSON.stringify(row)})'>Details</a>
                                <button class="btn delete" onclick='confirmDelete("${emailForDelete}")'>Delete</button>
                            </div>`;
                                }
                            }
                        ],
                        pageLength: 10,
                        lengthMenu: [10, 20, 50, 100],
                        language: {
                            processing: "<div class='spinner-border' role='status'><span class='visually-hidden'>Loading...</span></div>",
                            search: "Search all:",
                            lengthMenu: "Show _MENU_ entries",
                            info: "Showing _START_ to _END_ of _TOTAL_ entries",
                            infoEmpty: "Showing 0 to 0 of 0 entries",
                            infoFiltered: "(filtered from _MAX_ total entries)",
                            zeroRecords: "No matching records found",
                            paginate: {
                                first: "First",
                                last: "Last",
                                next: "Next",
                                previous: "Previous"
                            }
                        },
                        drawCallback: function(settings) {
                            if (!isSyncing) {
                                const newPage = this.api().page();
                                syncPagination(contactsTable, companyTable, newPage);
                            }
                        }
                    });
                }

                // 🔁 Inisialisasi pertama kali dengan 'CLT'
                loadContactsTable('CLT');

                // 🔁 Saat tab berubah → kirim CLT / MKT
                $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                    const target = e.target.getAttribute('data-bs-target');
                    if (target === '#calon-client') {
                        loadContactsTable('CLT');
                    } else if (target === '#calon-partner') {
                        loadContactsTable('MKT');
                    }
                });

                // Company Directory Table (tidak berubah)
                companyTable = $('#companyTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: 'company_processing.php',
                        type: 'POST'
                    },
                    columns: [{
                            data: 'nama_perusahaan',
                            name: 'nama_perusahaan',
                            render: function(data) {
                                return data || 'No Company Name';
                            }
                        },
                        {
                            data: 'website',
                            name: 'website',
                            render: function(data) {
                                if (data && data !== '-' && data !== '#') {
                                    return `<a href="${data}" target="_blank" style="color:#2563eb; text-decoration:underline;">${data}</a>`;
                                }
                                return '-';
                            }
                        },
                        {
                            data: 'kategori_perusahaan',
                            name: 'kategori_perusahaan',
                            render: function(data) {
                                return data || '-';
                            }
                        },
                        {
                            data: 'tipe',
                            name: 'tipe',
                            render: function(data) {
                                return data || '-';
                            }
                        },
                        {
                            data: 'kota',
                            name: 'kota',
                            render: function(data) {
                                return data || '-';
                            }
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [10, 20, 50, 100],
                    language: {
                        processing: "Loading...",
                        search: "Search companies:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ companies",
                        infoEmpty: "Showing 0 to 0 of 0 companies",
                        infoFiltered: "(filtered from _MAX_ total companies)",
                        zeroRecords: "No matching companies found"
                    },
                    drawCallback: function(settings) {
                        if (!isSyncing) {
                            const newPage = this.api().page();
                            syncPagination(companyTable, contactsTable, newPage);
                        }
                    }
                });

                // Header search (tidak berubah)
                $('#contactsTable thead th').each(function(index) {
                    var th = $(this);
                    if (th.text() === "#" || th.text() === "Actions") return;
                    th.css("cursor", "pointer");
                    th.on("click", function() {
                        if (th.find(".header-search").length > 0) return;
                        var currentText = th.text();
                        th.html('<div style="position:relative; display:flex; flex-direction:column; align-items:center;">' +
                            '<span class="header-title">' + currentText + '</span>' +
                            '<input type="text" class="header-search" placeholder="Search..." ' +
                            'style="margin-top:4px; padding:3px 6px; font-size:12px; width:90%; border:1px solid #ccc; border-radius:4px;" />' +
                            '</div>');
                        var input = th.find(".header-search");
                        input.focus();
                        input.on("keyup change clear", function() {
                            contactsTable.column(index).search(this.value).draw();
                        });
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

            function openStatusModal(email) {
                document.getElementById('statusEmail').value = email;
                document.getElementById('statusModal').style.display = 'block';
            }

            function closeStatusModal() {
                document.getElementById('statusModal').style.display = 'none';
            }

            document.getElementById('statusForm').addEventListener('submit', function(e) {
                e.preventDefault();

                fetch('set_status.php', {
                        method: 'POST',
                        body: new FormData(this)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert("Status updated!");
                            location.reload();
                        } else {
                            alert("Error: " + data.error);
                        }
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

            // 🔹 MARKETING PERFORMANCE CHART
            const marketingData = <?= json_encode($marketingData) ?>;
            let isExpanded = false;

            function renderMarketingChart(showAll = false) {
                const displayData = showAll ? marketingData : marketingData.slice(0, 5);
                const ctx2 = document.getElementById('marketingChart').getContext('2d');

                // Hapus chart lama jika ada
                if (window.marketingChartInstance) {
                    window.marketingChartInstance.destroy();
                }

                // Fallback label: jika display_name kosong/null, pakai partner_id
                // 🔹 TAMBAHKAN PROMO CODE CETAK TEBAL
                const labels = displayData.map(d => {
                    const name = d.display_name?.trim();
                    const displayName = name && name !== 'null' ? name : d.partner_id || 'Unknown';
                    const promoCode = d.partner_id || '-';

                    // Format dengan promo code cetak tebal di baris baru
                    return `${displayName} - ${promoCode}`;
                });

                window.marketingChartInstance = new Chart(ctx2, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Jumlah CRM',
                            data: displayData.map(d => d.total_crm),
                            backgroundColor: '#3b82f6',
                            borderColor: '#2563eb',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        indexAxis: 'y', // horizontal bar
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            title: {
                                display: false
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'right',
                                color: '#111',
                                font: {
                                    weight: 'bold'
                                },
                                formatter: value => value
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                },
                                title: {
                                    display: true,
                                    text: 'Jumlah CRM',
                                    font: {
                                        weight: 'bold'
                                    }
                                }
                            },
                            y: {
                                ticks: {
                                    font: {
                                        size: 12
                                    }
                                }
                            }
                        }
                    },
                    plugins: [ChartDataLabels]
                });

                // Sesuaikan tinggi container berdasarkan jumlah data
                const chartContainer = document.querySelector('#marketingCard .chart-container');
                const newHeight = Math.max(250, displayData.length * 40);
                chartContainer.style.height = newHeight + 'px';
            }

            // Render awal (5 data)
            renderMarketingChart(false);

            // Toggle Show More / Show Less
            document.getElementById('toggleMarketingChart')?.addEventListener('click', function() {
                isExpanded = !isExpanded;
                renderMarketingChart(isExpanded);
                document.getElementById('toggleText').textContent = isExpanded ? 'Show Less' : 'Show More';
                this.querySelector('.icon').textContent = isExpanded ? '🔼' : '🔽';

                // 🔹 Scroll ke awal card jika "Show Less" diklik
                if (!isExpanded) {
                    const card = document.getElementById('marketingCard');
                    if (card) {
                        card.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            });

            // Data dari PHP
            const categoryData = <?= json_encode($categoryData) ?>;
            const typeData = <?= json_encode($typeData) ?>;

            // ================== CATEGORY CHART ==================
            let isCategoryExpanded = false;

            function renderCategoryChart(showAll = false) {
                const displayData = showAll ? categoryData : categoryData.slice(0, 5);
                const ctx = document.getElementById('categoryChart').getContext('2d');

                if (window.categoryChartInstance) window.categoryChartInstance.destroy();

                const labels = displayData.map(d => {
                    const name = d.display_name?.trim();
                    const displayName = name && name !== 'null' ? name : d.partner_id || 'Unknown';
                    const promoCode = d.partner_id || '-';
                    return `${displayName} - ${promoCode}`;
                });

                window.categoryChartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Company Category',
                            data: displayData.map(d => d.total_category),
                            backgroundColor: '#10b981',
                            borderColor: '#059669',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const detail = displayData[context.dataIndex].category_details;
                                        return detail ? detail.split(', ').map(d => `• ${d}`) : 'No detail';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true
                            }
                        }
                    },
                    plugins: [ChartDataLabels]
                });

                const chartContainer = document.querySelector('#categoryCard .chart-container');
                chartContainer.style.height = Math.max(250, displayData.length * 40) + 'px';
            }

            renderCategoryChart(false);

            document.getElementById('toggleCategoryChart')?.addEventListener('click', function() {
                isCategoryExpanded = !isCategoryExpanded;
                renderCategoryChart(isCategoryExpanded);
                document.getElementById('toggleCategoryText').textContent = isCategoryExpanded ? 'Show Less' : 'Show More';
                this.querySelector('.icon').textContent = isCategoryExpanded ? '🔼' : '🔽';

                if (!isCategoryExpanded) {
                    document.getElementById('categoryCard')?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });

            // ================== TYPE CHART ==================
            let isTypeExpanded = false;

            function renderTypeChart(showAll = false) {
                const displayData = showAll ? typeData : typeData.slice(0, 5);
                const ctx = document.getElementById('typeChart').getContext('2d');

                if (window.typeChartInstance) window.typeChartInstance.destroy();

                const labels = displayData.map(d => {
                    const name = d.display_name?.trim();
                    const displayName = name && name !== 'null' ? name : d.partner_id || 'Unknown';
                    const promoCode = d.partner_id || '-';
                    return `${displayName} - ${promoCode}`;
                });

                window.typeChartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Company Type',
                            data: displayData.map(d => d.total_type),
                            backgroundColor: '#f59e0b',
                            borderColor: '#d97706',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const detail = displayData[context.dataIndex].type_details;
                                        return detail ? detail.split(', ').map(d => `• ${d}`) : 'No detail';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true
                            }
                        }
                    },
                    plugins: [ChartDataLabels]
                });

                const chartContainer = document.querySelector('#typeCard .chart-container');
                chartContainer.style.height = Math.max(250, displayData.length * 40) + 'px';
            }

            renderTypeChart(false);

            document.getElementById('toggleTypeChart')?.addEventListener('click', function() {
                isTypeExpanded = !isTypeExpanded;
                renderTypeChart(isTypeExpanded);
                document.getElementById('toggleTypeText').textContent = isTypeExpanded ? 'Show Less' : 'Show More';
                this.querySelector('.icon').textContent = isTypeExpanded ? '🔼' : '🔽';

                if (!isTypeExpanded) {
                    document.getElementById('typeCard')?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        </script>
            <?php if (!empty($_SESSION['bulk_upload_result'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const result = <?= json_encode($_SESSION['bulk_upload_result']) ?>;
                deleteSessionBulkResult(); // hapus session setelah tampil

                if (result.error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Upload Gagal',
                        text: result.error,
                        confirmButtonText: 'OK'
                    });
                } else {
                    const success = result.success || 0;
                    const failed = result.failed || 0;
                    let title = 'Upload Selesai!';
                    let html = `<div style="text-align:left;">
            <p><strong>Berhasil:</strong> ${success} kontak</p>
            <p><strong>Gagal:</strong> ${failed} kontak</p>
        </div>`;
                    let icon = 'success';

                    if (success === 0 && failed > 0) {
                        icon = 'error';
                        title = 'Upload Gagal!';
                    } else if (success > 0 && failed > 0) {
                        icon = 'warning';
                        title = 'Sebagian Berhasil';
                    }

                    Swal.fire({
                        icon: icon,
                        title: title,
                        html: html,
                        confirmButtonText: 'OK'
                    });
                }
            });

            // Fungsi untuk hapus session via AJAX (opsional) atau cukup unset di PHP
            function deleteSessionBulkResult() {
                fetch('clear_bulk_session.php', {
                    method: 'POST'
                });
            }
        </script>
        <?php unset($_SESSION['bulk_upload_result']); ?>
    <?php endif; ?>
</body>

</html>