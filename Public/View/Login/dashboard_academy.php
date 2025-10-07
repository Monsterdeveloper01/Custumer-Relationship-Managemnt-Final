<?php
session_start();
require '../../Model/db.php';

// Kalau belum login, tendang ke login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
// Cek apakah user role admin
$partner = $_SESSION['user'];
if (($partner['role'] ?? '') !== 'admin') {
    die("Akses ditolak: hanya admin yang bisa masuk dashboard ini.");
}

// Fungsi untuk mengambil data pembayaran
function getAllPayments()
{
    global $pdo;
    $sql = "SELECT
                p.payment_id, p.class_id, c.class_title AS nama_training, p.nama,
                p.email, p.hp, p.promo_code, p.discount_pct, p.price_training,
                p.discount, p.net_training, p.jumlah_peserta, p.total_bayar,
                p.discount_promo_code, p.net_bayar, p.payment_status,
                p.created_at, p.training_date
            FROM RTN_AC_PAYMENT p
            LEFT JOIN RTN_AC_CLASSES c ON p.class_id = c.class_id
            ORDER BY p.created_at DESC";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// Fungsi untuk mengambil data pendaftar trainer
function getAllTrainers()
{
    global $pdo;
    // Ambil semua kolom baru dan hapus kolom 'nik' yang sudah tidak ada
    $query = "SELECT 
                id, fullname, kategori, no_whatsapp, email, 
                pekerjaan, jabatan, perusahaan, 
                kelas_training, pengajaran_diminati 
              FROM RTN_AC_TRAINER_REGISTRATION 
              ORDER BY id DESC";
    $stmt = $pdo->query($query);
    return $stmt ? $stmt->fetchAll() : [];
}

// Fungsi untuk mengambil data pendaftar kelas
function getAllClassRegistrations()
{
    global $pdo;
    $stmt = $pdo->query("SELECT id, Kategori, nama_kelas, Nama_Lengkap, Email, No_Whatsapp, Jumlah_Peserta, Pesan_Tambahan, type_training FROM RTN_AC_PENDAFTARAN_KELAS ORDER BY id DESC");
    return $stmt ? $stmt->fetchAll() : [];
}

// Fungsi untuk mengambil data Promo Code Marketing Partner
// Fungsi untuk mengambil data Promo Code Marketing Partner dari tabel partner_individual
// Fungsi untuk mengambil data Promo Code Marketing Partner dari tabel partner_individual
// Fungsi untuk mengambil data Marketing Partner dari tabel users
function getPromoCodeMarketingPartner()
{
    global $pdo;
    $sql = "SELECT 
                marketing_id,
                name as nama_lengkap,
                email,
                role,
                created_at
            FROM users 
            WHERE role IN ('admin', 'user')
            ORDER BY created_at DESC";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// Fungsi untuk mengambil data Promo Code Institusi Partner
function getPromoCodeInstitusiPartner()
{
    global $pdo;
    $sql = "SELECT kode_institusi_partner, nama_institusi, nama_partner, discount_pct FROM RTN_AC_INSTITUSI_PARTNER WHERE ACTIVE_STATUS = 'y'";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// --- LOGIKA FILTER ---
$all_payments = getAllPayments();
$selected_training = $_GET['filter_training'] ?? '';
$selected_bulan = $_GET['filter_bulan'] ?? '';

$training_options_map = [];
foreach ($all_payments as $payment) {
    if (!isset($training_options_map[$payment['class_id']])) {
        $training_options_map[$payment['class_id']] = $payment['class_id'] . ' - ' . ($payment['nama_training'] ?? 'Nama Tidak Ditemukan');
    }
}
asort($training_options_map);

$bulan_options = array_unique(array_map(function ($p) {
    return !empty($p['training_date']) && $p['training_date'] !== '0000-00-00' ? date('Y-m', strtotime($p['training_date'])) : null;
}, $all_payments));
$bulan_options = array_filter($bulan_options);
rsort($bulan_options);

$filtered_payments = $all_payments;
if ($selected_training) {
    $filtered_payments = array_filter($filtered_payments, function ($p) use ($selected_training) {
        return $p['class_id'] === $selected_training;
    });
}
if ($selected_bulan) {
    $filtered_payments = array_filter($filtered_payments, function ($p) use ($selected_bulan) {
        return !empty($p['training_date']) && date('Y-m', strtotime($p['training_date'])) === $selected_bulan;
    });
}
$filtered_payments = array_values($filtered_payments);

$all_class_registrations = getAllClassRegistrations();
$selected_kategori = $_GET['filter_kategori'] ?? '';
$kategori_options = array_unique(array_column($all_class_registrations, 'Kategori'));
sort($kategori_options);

$filtered_class_registrations = $all_class_registrations;
if ($selected_kategori) {
    $filtered_class_registrations = array_filter($filtered_class_registrations, function ($reg) use ($selected_kategori) {
        return $reg['Kategori'] === $selected_kategori;
    });
}
$filtered_class_registrations = array_values($filtered_class_registrations);

$trainers = getAllTrainers();
$promo_codes_marketing_partner = getPromoCodeMarketingPartner();
$promo_codes_institusi_partner = getPromoCodeInstitusiPartner();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Rayterton Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary-color: #004080;
            --secondary-color: #007acc;
            --accent-color: #f0ad4e;
            --danger-color: #dc3545;
            --text-color: #333;
            --light-bg: #f9f9f9;
            --white-bg: #ffffff;
            --border-color: #ddd;
            --shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: var(--light-bg);
        }

        header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 12px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        header h1 {
            margin: 0;
            font-size: 1.5em;
        }

        .logout-button {
            padding: 8px 15px;
            background-color: var(--danger-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            font-size: 14px;
        }

        section {
            padding: 40px 20px;
            max-width: 1400px;
            margin: 30px auto;
            background: var(--white-bg);
            border-radius: 12px;
        }

        h2 {
            color: var(--primary-color);
            border-left: 5px solid var(--primary-color);
            padding-left: 15px;
            margin-bottom: 30px;
        }

        h3 {
            color: var(--primary-color);
            margin-top: 20px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-top: 20px;
            font-size: 14px;
            border: 1px solid var(--border-color);
        }

        table th,
        table td {
            padding: 12px 10px;
            white-space: nowrap;
            border: 1px solid var(--border-color);
        }

        th {
            background-color: var(--primary-color);
            color: white;
            text-align: left;
            /* [DIUBAH] Membuat header tabel "menempel" di atas saat di-scroll */
            position: sticky;
            top: 0;
            z-index: 10;
        }

        td {
            background: #e0f2ff;
            border-bottom: 1px solid var(--border-color);
        }

        tbody tr:nth-child(even) td {
            background-color: #f0f7ff;
        }


        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .tab-container {
            display: flex;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 30px;
        }

        .tab-button {
            background-color: transparent;
            border: none;
            cursor: pointer;
            padding: 16px 20px;
            font-size: 16px;
            color: var(--primary-color);
            font-weight: 500;
            flex-grow: 1;
            text-align: center;
            white-space: nowrap;
            /* Mencegah teks tombol patah */
        }

        .tab-button.active {
            background-color: var(--primary-color);
            color: white;
        }

        .tab-button i {
            margin-right: 8px;
        }

        .controls-header {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 250px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--primary-color);
        }

        .filter-group select {
            width: 100%;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        /* ... Sisa CSS Anda yang lain tetap sama ... */
        .silabus-btn {
            padding: 8px 15px;
            background-color: var(--secondary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .create-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            margin-bottom: 20px;
            transition: background-color 0.3s;
            cursor: pointer;
        }

        .create-btn:hover {
            background-color: var(--secondary-color);
        }

        .create-btn i {
            margin-right: 8px;
        }

        .action-btn {
            padding: 6px 12px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 13px;
            transition: opacity 0.3s;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            border: none;
            margin-right: 5px;
        }

        .action-btn:hover {
            opacity: 0.8;
        }

        .action-btn i {
            margin-right: 5px;
        }

        .edit-btn {
            background-color: var(--accent-color);
        }

        .delete-btn {
            background-color: var(--danger-color);
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .modal-content {
            background-color: var(--white-bg);
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow);
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            margin: 0;
            color: var(--primary-color);
        }

        .modal-close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--danger-color);
        }

        .modal-form-group {
            margin-bottom: 15px;
        }

        .modal-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
        }

        .modal-form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
        }

        .modal-footer {
            text-align: right;
            margin-top: 25px;
        }

        .modal-submit-btn {
            padding: 10px 25px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .modal-submit-btn:hover {
            background-color: var(--secondary-color);
        }

        .toast-container {
            z-index: 1055;
        }

        .toast {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .toast-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .toast-header .me-auto {
            font-weight: 600;
        }

        .toast.bg-success,
        .toast.bg-danger,
        .toast.bg-info,
        .toast.bg-primary {
            color: white;
        }

        .toast.bg-success .toast-header,
        .toast.bg-danger .toast-header,
        .toast.bg-info .toast-header,
        .toast.bg-primary .toast-header {
            color: white;
        }

        .toast.bg-warning {
            color: #333;
        }

        .toast.bg-warning .toast-header {
            color: #333;
        }

        .toast.bg-success .btn-close,
        .toast.bg-danger .btn-close,
        .toast.bg-info .btn-close,
        .toast.bg-primary .btn-close {
            filter: brightness(0) invert(1);
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .bg-success {
            background-color: #28a745;
            color: white;
        }

        .bg-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .bg-danger {
            background-color: #dc3545;
            color: white;
        }

        /* [BARU] CSS UNTUK TAMPILAN MOBILE (RESPONSIVE) */
        @media (max-width: 768px) {
            section {
                padding: 20px 10px;
                margin: 15px auto;
            }

            header h1 {
                font-size: 1.2em;
            }

            .tab-container {
                /* Membuat tab bisa di-scroll ke samping di mobile */
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                /* Scroll lebih mulus di iOS */
                scrollbar-width: none;
                /* Sembunyikan scrollbar untuk Firefox */
            }

            .tab-container::-webkit-scrollbar {
                display: none;
                /* Sembunyikan scrollbar untuk Chrome, Safari, Opera */
            }

            .tab-button {
                flex-grow: 0;
                /* Biarkan lebar tombol sesuai kontennya */
                flex-shrink: 0;
                padding: 12px 15px;
            }

            .tab-button i {
                margin-right: 5px;
                /* Kurangi jarak ikon */
            }

            table {
                font-size: 12px;
                /* Perkecil font tabel */
            }

            th,
            td {
                padding: 8px 6px;
                /* Perkecil padding sel */
            }

            .filter-form {
                flex-direction: column;
                /* Susun filter ke bawah */
                align-items: stretch;
            }

            .filter-group {
                min-width: 100%;
                /* Lebar penuh untuk setiap filter */
            }
        }
    </style>
</head>

<body>


    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="statusToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto" id="toastTitle"></strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastBody">
            </div>
        </div>
    </div>

    <section>
        <h2>Dashboard Overview</h2>

        <div class="tab-container">
            <button class="tab-button" onclick="openTab(event, 'Payments')"><i class="fas fa-dollar-sign"></i>
                Payment</button>
            <button class="tab-button" onclick="openTab(event, 'ClassRegistrations')"><i class="fas fa-users"></i>
                Training Registrations</button>
            <button class="tab-button" onclick="openTab(event, 'TrainerRegistrations')"><i
                    class="fas fa-chalkboard-teacher"></i> Trainer</button>
            <button class="tab-button" onclick="openTab(event, 'PromoCodeMarketingPartner')"><i class="fas fa-tags"></i>
                Marketing Accounts</button>
            <!-- <button class="tab-button" onclick="openTab(event, 'PromoCodeInstitusiPartner')"><i
                    class="fas fa-university"></i> Marketing Institusi Partner</button> -->
        </div>
        <div id="Payments" class="tab-content">
            <h3>Data Pembayaran</h3>
            <div class="controls-header">
                <form action="admin_dashboard.php" method="GET" class="filter-form">
                    <div class="filter-group">
                        <label for="filter-training">Filter Training</label>
                        <select name="filter_training" id="filter-training" onchange="this.form.submit()">
                            <option value="">Semua Training</option>
                            <?php foreach ($training_options_map as $class_id => $display_text): ?>
                                <option value="<?php echo htmlspecialchars($class_id); ?>" <?php if ($selected_training === $class_id)
                                                                                                echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($display_text); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter-bulan">Filter Bulan</label>
                        <select name="filter_bulan" id="filter-bulan" onchange="this.form.submit()">
                            <option value="">Semua Bulan</option>
                            <?php foreach ($bulan_options as $bulan): ?>
                                <option value="<?php echo htmlspecialchars($bulan); ?>" <?php if ($selected_bulan === $bulan)
                                                                                            echo 'selected'; ?>>
                                    <?php echo htmlspecialchars(date('F Y', strtotime($bulan . '-01'))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="active_tab" value="Payments">
                </form>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>ID Pembayaran</th>
                            <th>Nama Peserta</th>
                            <th>Kode Training</th>
                            <th>Nama Training</th>
                            <th>Tgl Training</th>
                            <th>Harga/Peserta</th>
                            <th>Disc</th>
                            <th>Net/Peserta</th>
                            <th>Jml Peserta</th>
                            <th>Total Bayar</th>
                            <th>Kode Promo</th>
                            <th>Disc Promo</th>
                            <th>Net Bayar</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($filtered_payments) > 0): ?>
                            <?php foreach ($filtered_payments as $index => $payment): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($payment['payment_id']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['class_id']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['nama_training'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars(date('d M Y', strtotime($payment['training_date']))); ?>
                                    </td>
                                    <td style="text-align: right;">Rp
                                        <?php echo number_format($payment['price_training'], 0, ',', '.'); ?>
                                    </td>
                                    <td style="text-align: right;">Rp
                                        <?php echo number_format($payment['discount'], 0, ',', '.'); ?>
                                    </td>
                                    <td style="text-align: right;">Rp
                                        <?php echo number_format($payment['net_training'], 0, ',', '.'); ?>
                                    </td>
                                    <td style="text-align: center;"><?php echo htmlspecialchars($payment['jumlah_peserta']); ?>
                                    </td>
                                    <td style="text-align: right;">Rp
                                        <?php echo number_format($payment['total_bayar'], 0, ',', '.'); ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php echo htmlspecialchars($payment['promo_code'] ?: '-'); ?>
                                    </td>
                                    <td style="text-align: right;">Rp
                                        <?php echo number_format($payment['discount_promo_code'], 0, ',', '.'); ?>
                                    </td>
                                    <td style="text-align: right;"><strong>Rp
                                            <?php echo number_format($payment['net_bayar'], 0, ',', '.'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($payment['payment_status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="15" style="text-align:center;">Tidak ada data yang cocok dengan filter.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="ClassRegistrations" class="tab-content">
            <h3>Data Pendaftar Kelas</h3>
            <div class="controls-header">
                <form action="admin_dashboard.php" method="GET" class="filter-form">
                    <div class="filter-group">
                        <label for="filter-kategori">Filter Kategori</label>
                        <select name="filter_kategori" id="filter-kategori" onchange="this.form.submit()">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($kategori_options as $kategori): ?>
                                <option value="<?php echo htmlspecialchars($kategori); ?>" <?php if ($selected_kategori === $kategori)
                                                                                                echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($kategori); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="active_tab" value="ClassRegistrations">
                </form>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Lengkap</th>
                            <th>Kategori</th>
                            <th>Nama Kelas</th>
                            <th>Type Training</th>
                            <th>Email</th>
                            <th>No. Whatsapp</th>
                            <th>Jumlah Peserta</th>
                            <th>Pesan Tambahan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($filtered_class_registrations) > 0): ?>
                            <?php foreach ($filtered_class_registrations as $index => $registration): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($registration['Nama_Lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars($registration['Kategori']); ?></td>
                                    <td><?php echo htmlspecialchars($registration['nama_kelas']); ?></td>
                                    <td><?php echo htmlspecialchars($registration['type_training']); ?></td>
                                    <td><?php echo htmlspecialchars($registration['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($registration['No_Whatsapp']); ?></td>
                                    <td style="text-align: center;">
                                        <?php echo htmlspecialchars($registration['Jumlah_Peserta']); ?>
                                    </td>
                                    <td><?php echo nl2br(htmlspecialchars($registration['Pesan_Tambahan'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align:center;">Tidak ada data yang cocok dengan filter.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="TrainerRegistrations" class="tab-content">
            <h3>Data Pendaftar Trainer</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>No. Whatsapp</th>
                            <th>Pekerjaan & Perusahaan</th>
                            <th>Kategori Pilihan</th>
                            <th>Kelas Pilihan</th>
                            <th>Minat Pengajaran Lain</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($trainers) > 0): ?>
                            <?php foreach ($trainers as $index => $trainer): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($trainer['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($trainer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($trainer['no_whatsapp']); ?></td>
                                    <td>
                                        <?php
                                        echo htmlspecialchars($trainer['pekerjaan'] ?: '-');
                                        if (!empty($trainer['jabatan']))
                                            echo ' (' . htmlspecialchars($trainer['jabatan']) . ')';
                                        if (!empty($trainer['perusahaan']))
                                            echo '<br><small>' . htmlspecialchars($trainer['perusahaan']) . '</small>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $kategori_list = trim($trainer['kategori']);
                                        if (!empty($kategori_list)) {
                                            // Pecah string berdasarkan ', ' menjadi array
                                            $kategori_items = explode(', ', $kategori_list);
                                            // Tampilkan sebagai daftar berpoin
                                            echo '<ul style="margin: 0; padding-left: 20px; list-style-type: square;">';
                                            foreach ($kategori_items as $item) {
                                                echo '<li>' . htmlspecialchars(trim($item)) . '</li>';
                                            }
                                            echo '</ul>';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $kelas_list = trim($trainer['kelas_training']);
                                        if (!empty($kelas_list)) {
                                            // Pecah string berdasarkan ', ' menjadi array
                                            $kelas_items = explode(', ', $kelas_list);
                                            // Tampilkan sebagai daftar berpoin
                                            echo '<ul style="margin: 0; padding-left: 20px; list-style-type: square;">';
                                            foreach ($kelas_items as $item) {
                                                echo '<li>' . htmlspecialchars(trim($item)) . '</li>';
                                            }
                                            echo '</ul>';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo nl2br(htmlspecialchars($trainer['pengajaran_diminati'] ?: '-')); ?></td>
                                    <td>
                                        <a href="download_resume.php?id=<?php echo $trainer['id']; ?>" class="silabus-btn"><i
                                                class="fas fa-download"></i> CV</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align:center;">Belum ada data pendaftar trainer.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="PromoCodeMarketingPartner" class="tab-content">
            <h3>Data Marketing Individual Partner</h3>
            <div class="controls-header" style="border-bottom: none; padding-bottom: 0;">
                <button class="create-btn" onclick="openMarketingModal('create')">
                    <i class="fas fa-plus"></i> Buat data baru
                </button>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Marketing ID</th>
                            <th style="text-align: left;">Nama Lengkap</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Tanggal Daftar</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($promo_codes_marketing_partner) > 0): ?>
                            <?php foreach ($promo_codes_marketing_partner as $index => $user): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td data-label="marketing_id"><?php echo htmlspecialchars($user['marketing_id']); ?></td>
                                    <td data-label="nama" style="text-align: left;">
                                        <?php echo htmlspecialchars($user['nama_lengkap']); ?>
                                    </td>
                                    <td data-label="email"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td data-label="role">
                                        <span class="badge <?php
                                                            echo $user['role'] == 'admin' ? 'bg-primary' : 'bg-secondary';
                                                            ?>">
                                            <?php echo htmlspecialchars(strtoupper($user['role'])); ?>
                                        </span>
                                    </td>
                                    <td data-label="created_at"><?php echo htmlspecialchars(date('d M Y', strtotime($user['created_at']))); ?></td>
                                    <td style="text-align:center;">
                                        <button class="action-btn edit-btn" onclick="openMarketingModal('edit', this)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="action-btn delete-btn"
                                            onclick="deleteMarketingUser('<?php echo htmlspecialchars($user['marketing_id']); ?>')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center;">Tidak ada data marketing partner.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="PromoCodeInstitusiPartner" class="tab-content">
            <h3>Data Marketing Institution Partner</h3>
            <div class="controls-header" style="border-bottom: none; padding-bottom: 0;">
                <button class="create-btn" onclick="openInstitusiModal('create')">
                    <i class="fas fa-plus"></i> Buat Promo Code Baru
                </button>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Kode Promo</th>
                            <th>Nama Institusi</th>
                            <th>Nama Partner</th>
                            <th>Discount (%)</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($promo_codes_institusi_partner) > 0): ?>
                            <?php foreach ($promo_codes_institusi_partner as $index => $promo_code): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td data-label="kode"><?php echo htmlspecialchars($promo_code['kode_institusi_partner']); ?>
                                    </td>
                                    <td data-label="institusi"><?php echo htmlspecialchars($promo_code['nama_institusi']); ?>
                                    </td>
                                    <td data-label="partner"><?php echo htmlspecialchars($promo_code['nama_partner']); ?></td>
                                    <td data-label="diskon"><?php echo htmlspecialchars($promo_code['discount_pct']); ?>%</td>
                                    <td style="text-align:center;">
                                        <button class="action-btn edit-btn" onclick="openInstitusiModal('edit', this)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="action-btn delete-btn"
                                            onclick="deleteInstitusiPromo('<?php echo htmlspecialchars($promo_code['kode_institusi_partner']); ?>')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center;">Tidak ada data promo code institusi partner.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </section>

    <div id="marketingModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 id="marketingModalTitle"></h3>
                <button class="modal-close-btn" onclick="closeMarketingModal()">&times;</button>
            </div>
            <form id="marketingForm" action="manage_marketing_user_proxy.php" method="POST">
                <input type="hidden" name="action" id="marketingAction">
                <input type="hidden" name="original_marketing_id" id="originalMarketingId">

                <div class="modal-form-group">
                    <label for="marketingId">Marketing ID *</label>
                    <input type="text" id="marketingId" name="marketing_id" required maxlength="3">
                    <small style="color: #666;">3 karakter unik (contoh: M01, M02, dll)</small>
                </div>

                <div class="modal-form-group">
                    <label for="namaLengkap">Nama Lengkap *</label>
                    <input type="text" id="namaLengkap" name="name" required>
                </div>

                <div class="modal-form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="modal-form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                        <small style="color: #666;">Minimal 6 karakter</small>
                    </div>
                    <div class="modal-form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 5px;">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="modal-submit-btn">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="institusiModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="institusiModalTitle"></h3>
                <button class="modal-close-btn" onclick="closeInstitusiModal()">&times;</button>
            </div>
            <form id="institusiForm" action="manage_institusi_promo_proxy.php" method="POST">
                <input type="hidden" name="action" id="institusiAction">
                <input type="hidden" name="original_kode_institusi" id="originalKodeInstitusi">

                <div class="modal-form-group">
                    <label for="kodeInstitusi">Kode Promo</label>
                    <input type="text" id="kodeInstitusi" name="kode_institusi_partner" required>
                </div>
                <div class="modal-form-group">
                    <label for="namaInstitusi">Nama Institusi</label>
                    <input type="text" id="namaInstitusi" name="nama_institusi" required>
                </div>
                <div class="modal-form-group">
                    <label for="namaPartner">Nama Partner</label>
                    <input type="text" id="namaPartner" name="nama_partner" required>
                </div>
                <div class="modal-form-group">
                    <label for="discountPctInstitusi">Discount (%)</label>
                    <input type="number" id="discountPctInstitusi" min="1" name="discount_pct" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="modal-submit-btn">Simpan</button>
                </div>
            </form>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script>
        // Skrip custom Anda
        function openTab(evt, tabName) {
            let i, tabcontent, tabbuttons;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tabbuttons = document.getElementsByClassName("tab-button");
            for (i = 0; i < tabbuttons.length; i++) {
                tabbuttons[i].className = tabbuttons[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
            const url = new URL(window.location);
            url.searchParams.set('active_tab', tabName);
            window.history.replaceState({}, '', url);
        }

        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('active_tab') || 'Payments';
            const buttonToClick = document.querySelector(`.tab-button[onclick*="'${activeTab}'"]`);
            if (buttonToClick) {
                buttonToClick.click();
            } else {
                document.querySelector(".tab-button").click();
            }
        });

        // Skrip Modal
        const marketingModal = document.getElementById('marketingModal');
        const marketingForm = document.getElementById('marketingForm');
        const marketingModalTitle = document.getElementById('marketingModalTitle');
        const marketingAction = document.getElementById('marketingAction');
        const originalPromoCodeInput = document.getElementById('originalPromoCode');
        const promoCodeInput = document.getElementById('promoCode');

        function openMarketingModal(mode, button) {
            marketingForm.reset();
            if (mode === 'create') {
                marketingModalTitle.innerText = 'Buat Marketing User Baru';
                marketingAction.value = 'create';
                document.getElementById('role').value = 'user';
            } else if (mode === 'edit') {
                marketingModalTitle.innerText = 'Edit Marketing User';
                marketingAction.value = 'edit';

                const row = button.closest('tr');
                const marketingId = row.querySelector('[data-label="marketing_id"]').innerText;
                const namaLengkap = row.querySelector('[data-label="nama"]').innerText;
                const email = row.querySelector('[data-label="email"]').innerText;
                const role = row.querySelector('[data-label="role"]').querySelector('.badge').innerText.toLowerCase();

                originalMarketingId.value = marketingId;
                document.getElementById('marketingId').value = marketingId;
                document.getElementById('namaLengkap').value = namaLengkap;
                document.getElementById('email').value = email;
                document.getElementById('role').value = role;

                // Password tidak diisi saat edit untuk keamanan
                document.getElementById('password').required = false;
                document.getElementById('password').placeholder = 'Kosongkan jika tidak ingin mengubah password';
            }
            marketingModal.style.display = 'flex';
        }

        function closeMarketingModal() {
            marketingModal.style.display = 'none';
            // Reset required attribute untuk password
            document.getElementById('password').required = true;
            document.getElementById('password').placeholder = '';
        }

        function deleteMarketingUser(marketingId) {
            if (confirm(`Apakah Anda yakin ingin menghapus user dengan Marketing ID "${marketingId}"?`)) {
                window.location.href = `manage_marketing_user_proxy.php?action=delete&marketing_id=${marketingId}`;
            }
        }

        const institusiModal = document.getElementById('institusiModal');
        const institusiForm = document.getElementById('institusiForm');
        const institusiModalTitle = document.getElementById('institusiModalTitle');
        const institusiAction = document.getElementById('institusiAction');
        const originalKodeInstitusiInput = document.getElementById('originalKodeInstitusi');
        const kodeInstitusiInput = document.getElementById('kodeInstitusi');

        function openInstitusiModal(mode, button) {
            institusiForm.reset();
            if (mode === 'create') {
                institusiModalTitle.innerText = 'Buat Promo Code Institusi Baru';
                institusiAction.value = 'create';
            } else if (mode === 'edit') {
                institusiModalTitle.innerText = 'Edit Promo Code Institusi';
                institusiAction.value = 'edit';

                const row = button.closest('tr');
                const kode = row.querySelector('[data-label="kode"]').innerText;
                const institusi = row.querySelector('[data-label="institusi"]').innerText;
                const partner = row.querySelector('[data-label="partner"]').innerText;
                const diskon = row.querySelector('[data-label="diskon"]').innerText.replace('%', '');

                originalKodeInstitusiInput.value = kode;
                kodeInstitusiInput.value = kode;
                document.getElementById('namaInstitusi').value = institusi;
                document.getElementById('namaPartner').value = partner;
                document.getElementById('discountPctInstitusi').value = diskon;
            }
            institusiModal.style.display = 'flex';
        }

        function closeInstitusiModal() {
            institusiModal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == marketingModal) {
                closeMarketingModal();
            }
            if (event.target == institusiModal) {
                closeInstitusiModal();
            }
        }

        // --- [BARU] FUNGSI JAVASCRIPT UNTUK DELETE ---
        function deleteMarketingPromo(promoCode) {
            if (confirm(`Apakah Anda yakin ingin menghapus promo code "${promoCode}"?`)) {
                // BENAR: Panggil file proxy yang aman dan bisa diakses publik
                window.location.href = `manage_marketing_promo_proxy.php?promo_code=${promoCode}`;
            }
        }

        function deleteInstitusiPromo(promoCode) {
            if (confirm(`Apakah Anda yakin ingin menghapus promo code "${promoCode}"?`)) {
                // BENAR: Panggil file proxy yang aman dan bisa diakses publik
                window.location.href = `manage_institusi_promo_proxy.php?promo_code=${promoCode}`;
            }
        }
    </script>

    <?php
    // --- [DIUBAH] MENAMBAHKAN CASE UNTUK NOTIFIKASI DELETE ---
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        $message = '';
        $title = '';
        $toastClass = '';

        switch ($status) {
            case 'create_success':
                $title = '✅ Sukses';
                $message = 'Data berhasil ditambahkan!';
                $toastClass = 'bg-success text-white';
                break;
            case 'edit_success':
                $title = '✅ Sukses';
                $message = 'Data berhasil diperbarui!';
                $toastClass = 'bg-success text-white';
                break;
            case 'delete_success': // [BARU]
                $title = '✅ Sukses';
                $message = 'Data berhasil dihapus.';
                $toastClass = 'bg-success text-white';
                break;
            case 'create_failed':
                $title = '❌ Gagal';
                $message = 'Gagal menambahkan data. Kode promo mungkin sudah ada.';
                $toastClass = 'bg-danger text-white';
                break;
            case 'edit_failed':
                $title = '❌ Gagal';
                $message = 'Gagal memperbarui data.';
                $toastClass = 'bg-danger text-white';
                break;
            case 'delete_failed': // [BARU]
                $title = '❌ Gagal';
                $message = 'Gagal menghapus data.';
                $toastClass = 'bg-danger text-white';
                break;
            case 'error_validation':
                $title = '⚠️ Peringatan';
                $message = 'Gagal. Pastikan semua field terisi dengan benar.';
                $toastClass = 'bg-warning text-dark';
                break;
            case 'error_db':
            case 'invalid_action':
                $title = '❌ Error';
                $message = 'Terjadi kesalahan sistem.';
                $toastClass = 'bg-danger text-white';
                break;
        }

        if (!empty($message)) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    const statusToastEl = document.getElementById('statusToast');
                    const toastTitleEl = document.getElementById('toastTitle');
                    const toastBodyEl = document.getElementById('toastBody');
                    const toastHeaderEl = statusToastEl.querySelector('.toast-header');

                    toastTitleEl.innerHTML = '" . addslashes($title) . "';
                    toastBodyEl.innerHTML = '" . addslashes($message) . "';
                    
                    statusToastEl.className = 'toast';
                    toastHeaderEl.className = 'toast-header';
                    statusToastEl.classList.add(...'" . $toastClass . "'.split(' '));
                    if ('" . $toastClass . "' !== 'bg-warning text-dark') {
                       toastHeaderEl.classList.add('text-white');
                       statusToastEl.querySelector('.btn-close').classList.add('btn-close-white');
                    }
                    
                    const statusToast = new bootstrap.Toast(statusToastEl);
                    statusToast.show();

                    const url = new URL(window.location);
                    url.searchParams.delete('status');
                    window.history.replaceState({}, document.title, url.toString());
                });
              </script>";
        }
    }
    ?>

</body>

</html>