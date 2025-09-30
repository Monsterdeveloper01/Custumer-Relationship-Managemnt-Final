<?php
session_start();
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
    "SIT (System Integration Testing)" => "#e11d48",
    "UAT (User Acceptance Testing)" => "#7c3aed",
    "Proposal" => "#06b6d4",
    "Negotiation" => "#f97316",
    "Deal / Closed" => "#16a34a",
    "Failed / Tidak Lanjut" => "#dc2626",
    "Postpone" => "#6b7280",
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Partner Dashboard</title>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- chart -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
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
    to { transform: rotate(360deg); }
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

    <div class="max-w-6xl mx-auto p-6 space-y-6">
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
                    <h2>
                        Berikut contoh form isi email:
                    </h2>
                </div>
                <p class="card-hint">
                    Gunakan format ini saat mengirim email ke calon client.
                </p>
            </div>
            <textarea class="form-control w-full" rows="7" readonly style="width:100%; resize:none;">
Kepada Yth Bapak/Ibu/Sdr [Nama Calon Client] 
[Jabatan Calon Client] 
[Nama PT Calon Client] 

Hormat kami,  
[Nama Anda sebagai Marketing]  
Marketing Partner PT Rayterton Indonesia
    </textarea>
        </div>

        <!-- Contact List -->

        <body>

            <!-- Contact List -->
            <div class="card info-card partner-list">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">All Contacts</h2>
                    <!-- Panduan -->
                    <p class="card-hint">
                        Daftar semua kontak yang sudah diinput oleh semua partner.
                        Kamu bisa <b>edit</b>, <b>lihat detail</b>, atau <b>kirim email</b> langsung.
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
                                <!-- 🔹 Tambahin kolom Marketing ID -->
                                <th>Marketing ID</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

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

            <!-- Company Directory -->
            <div class="card info-card">
                <h2>Company Directory</h2>
                <!-- Panduan -->
                <p class="card-hint">
                    Direktori semua perusahaan dari kontak kamu.
                    Klik link website untuk langsung menuju halaman perusahaan.
                </p>
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
            <div id="editModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <h2>Edit Contact</h2>
                    <!-- Panduan -->
                    <p class="card-hint">
                        Gunakan form ini untuk memperbarui data kontak. Pastikan email benar karena jadi acuan utama.
                    </p>
                    <form id="editForm" method="post" action="edit_contact.php">
                        <input type="hidden" name="company_email" id="edit_company_email">
                        <label for="edit_name_person">Company Name:</label>
                        <input type="text" name="company_name" id="edit_company_name" required>
                        <label for="edit_person_email">Email:</label>
                        <input type="email" name="person_email" id="edit_person_email" required>
                        <label for="edit_phone_number">Phone:</label>
                        <input type="text" name="phone_number" id="edit_phone_number" required>
                        <label for="edit_status">Status:</label>
                        <select name="status" id="edit_status" required>
                            <option value="input">Input</option>
                            <option value="emailed">Emailed</option>
                            <option value="contacted">Contacted</option>
                            <option value="presentation">Presentation</option>
                            <option value="NDA process">NDA process</option>
                            <option value="Gap analysis / requirement analysis">Gap analysis / requirement analysis</option>
                            <option value="SIT (System Integration Testing)">SIT (System Integration Testing)</option>
                            <option value="UAT (User Acceptance Testing)">UAT (User Acceptance Testing)</option>
                            <option value="Proposal">Proposal</option>
                            <option value="Negotiation">Negotiation</option>
                            <option value="Deal / Closed">Deal / Closed</option>
                            <option value="Failed / Tidak Lanjut">Failed / Tidak Lanjut</option>
                            <option value="Postpone">Postpone</option>
                        </select>
                        <button type="submit" class="btn edit">Save Changes</button>
                    </form>
                </div>
            </div>
    </div>

    <script>
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
                                // Reload halaman agar data terbaru muncul
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
    // 1. All Contacts Table - Server-side
    var contactsTable = $('#contactsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'server_processing.php',
            type: 'POST'
        },
        columns: [
            { 
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
                render: function(data, type, row) {
                    let emailBtn = '';
                    if (data.status === 'input' && data.email) {
                        emailBtn = `<a href="send_email.php?email=${encodeURIComponent(data.email)}" class="btn email">Send Email</a>`;
                    } else {
                        emailBtn = `<button class="btn email" style="opacity:0.5; cursor:not-allowed;" disabled>Send Email</button>`;
                    }
                    
                    return `
                        ${emailBtn}
                        <a href="javascript:void(0);" class="btn edit" onclick='openEditModal(${JSON.stringify(data.raw_data)})'>Edit</a>
                        <a href="javascript:void(0);" class="btn details" onclick='toggleDetails(this, ${JSON.stringify(data.raw_data)})'>Details</a>
                        <button class="btn delete" onclick='confirmDelete("${data.email.replace(/"/g, '\\"')}")'>Delete</button>
                    `;
                }
            }
        ],
        pageLength: 20,
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
        }
    });

    // 2. Company Directory Table - Server-side
    var companyTable = $('#companyTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'company_processing.php',
            type: 'POST'
        },
        columns: [
            { 
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
                        return `<a href="${data}" target="_blank" style="color: #2563eb; text-decoration: underline;">${data}</a>`;
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
        lengthMenu: [5, 10, 25, 50],
        language: {
            processing: "Loading...",
            search: "Search companies:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ companies",
            infoEmpty: "Showing 0 to 0 of 0 companies",
            infoFiltered: "(filtered from _MAX_ total companies)",
            zeroRecords: "No matching companies found"
        }
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

        function openEditModal(contact) {
            document.getElementById("edit_company_email").value = contact.email;
            document.getElementById("edit_person_email").value = contact.email;
            document.getElementById("edit_company_name").value = contact.nama_perusahaan;
            document.getElementById("edit_phone_number").value = contact.no_telp1;
            document.getElementById("edit_status").value = contact.status;
            document.getElementById("editModal").style.display = "block";
        }


        function closeModal() {
            document.getElementById("editModal").style.display = "none";
        }

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
    </script>
</body>

</html>