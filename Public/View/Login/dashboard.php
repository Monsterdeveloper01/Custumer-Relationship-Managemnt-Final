<?php
session_start();
require '../../Model/db.php';

// Kalau belum login, tendang ke login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION['user']['role'] ?? '') === 'admin') {
    header("Location: dashboard_admin.php");
    exit;
}


$partner = $_SESSION['user'];
$marketing_id = $partner['marketing_id']; // contoh: PTR
$isPartner = ($partner['role'] === 'partner');

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
    </style>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>

<body>
    <?php include("../Partials/Header.html"); ?>

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
                        <?php if (count($contacts) > 0): ?>
                            <?php foreach ($contacts as $i => $c): ?>
                                <?php $emailClean = normalize_email($c['email'] ?? ''); ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($c['nama_perusahaan'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($emailClean ?: '-') ?></td>
                                    <td><?= htmlspecialchars($c['no_telp1'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($c['kategori_perusahaan'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($c['status'] ?? '-') ?></td>
                                    <td class="table-actions">
                                        <?php if (($c['status'] ?? '') === 'input' && $emailClean && empty($isPartner)): ?>
                                            <button type="button" class="btn email" onclick="previewEmail('<?= $emailClean ?>')">Send Email</button>
                                        <?php else: ?>
                                            <button class="btn email" style="opacity:0.5; cursor:not-allowed;" disabled>Send Email</button>
                                        <?php endif; ?>
                                        <a href="javascript:void(0);" class="btn details" onclick='toggleDetails(this, <?= json_encode($c) ?>)'>Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center;">No contacts available</td>
                            </tr>
                        <?php endif; ?>
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
        $(document).ready(function() {
            var table = $('#contactsTable').DataTable({
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
                ordering: false, // ⛔️ disable sorting di semua kolom
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

        function openEditModal(contact) {
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
    </script>
</body>

</html>