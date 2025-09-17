<?php
session_start();
require '../../Model/db.php';

// Kalau belum login, tendang ke login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$partner = $_SESSION['user'];
$marketing_id = $partner['marketing_id']; // contoh: PTR

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

// Statistik status
$statsStmt = $pdo->prepare("
    SELECT status, COUNT(*) as total 
    FROM crm_contacts_staging 
    WHERE ditemukan_oleh = ? 
    GROUP BY status
");
$statsStmt->execute([$marketing_id]);
$stats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Partner Dashboard</title>
    <link rel="stylesheet" href="../Login/style.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
    </style>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>

<body>
    <div class="topbar">
        <div class="topbar-left">
            <h1 class="topbar-title">Rayterton CRM Partner</h1>
        </div>
        <div class="topbar-right">
            <form id="logoutForm" action="logout.php" method="post">
                <button type="button" class="btn logout" onclick="confirmLogout()">Logout</button>
            </form>

            <script>
                function confirmLogout() {
                    Swal.fire({
                        title: 'Yakin mau logout?',
                        text: "Kamu akan keluar dari dashboard",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, logout!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById("logoutForm").submit();
                        }
                    });
                }
            </script>
        </div>
    </div>

    <div class="container">
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
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Your List</h2>
                <!-- Panduan -->
                <p class="card-hint">
                    Daftar semua kontak yang sudah kamu input.
                    Kamu bisa <b>tambah</b>, <b>edit</b>, <b>lihat detail</b>, atau <b>kirim email</b> langsung.
                </p>
                <button type="button" onclick="window.location.href='add_contact.php'" class="btn-add-contact">
                    <span class="icon">＋</span>
                    Add Contact
                </button>
            </div>

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
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($c['nama_perusahaan'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($c['email'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($c['no_telp1'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($c['kategori_perusahaan'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($c['status'] ?? '-') ?></td>
                                <td class="table-actions">
                                    <a href="../send_email.php?email=<?= urlencode($c['email'] ?? '') ?>"
                                        class="btn email">Send Email</a>
                                    <a href="javascript:void(0);" class="btn edit"
                                        onclick='openEditModal(<?= json_encode($c) ?>)'>Edit</a>
                                    <a href="javascript:void(0);" class="btn details"
                                        onclick='toggleDetails(this, <?= json_encode($c) ?>)'>Details</a>
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


        <!-- CRM Statistics -->
        <div class="card stats-card">
            <h2>CRM Status Overview</h2>
            <!-- Panduan -->
            <p class="card-hint">
                Statistik perkembangan kontak berdasarkan status di pipeline CRM.
                Bantu kamu memantau sejauh mana proses berjalan.
            </p>
            <ul>
                <?php foreach ($stats as $s): ?>
                    <li><?= htmlspecialchars($s['status']) ?>: <b><?= $s['total'] ?></b></li>
                <?php endforeach; ?>
            </ul>
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
            $('#contactsTable').DataTable({
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
                order: [
                    [0, "asc"]
                ],
                columnDefs: [{
                    orderable: false,
                    targets: -1
                }]
            });
            $('#companyTable').DataTable({
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
                order: [
                    [0, "asc"]
                ]
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
    </script>
</body>

</html>