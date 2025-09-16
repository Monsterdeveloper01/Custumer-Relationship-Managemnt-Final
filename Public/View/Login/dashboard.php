<?php
session_start();
require '../../Model/db.php';

// Kalau belum login, tendang ke login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$partner = $_SESSION['user'];
$marketing_id = $partner['marketing_id'];

// Ambil contact lengkap dari CRM berdasarkan marketing_id
$sql = "
    SELECT *
    FROM crm
    WHERE marketing_id = ?
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
    ) ASC, updated_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$marketing_id]);
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistik status
$statsStmt = $pdo->prepare("SELECT status, COUNT(*) as total 
                             FROM crm WHERE marketing_id = ? 
                             GROUP BY status");
$statsStmt->execute([$marketing_id]);
$stats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Partner Dashboard</title>
    <link rel="stylesheet" href="style.css">
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
<!-- Contact List -->
<div class="card info-card partner-list">

    <!-- Tombol Add Contact -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold">Your List</h2>
        <a href="add_contact.php"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white shadow-md transition ease-in-out duration-150">
            + Add Contact
        </a>
    </div>

    <table id="contactsTable" class="partner-table w-full border-collapse">
        <thead>
            <tr>
                <th>#</th>
                <th>Contact Name</th>
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
                        <td><?= htmlspecialchars($c['name_person']) ?></td>
                        <td><?= htmlspecialchars($c['company_email']) ?></td>
                        <td><?= htmlspecialchars($c['phone_number']) ?></td>
                        <td><?= htmlspecialchars($c['company_name']) ?></td>
                        <td><?= htmlspecialchars($c['status']) ?></td>
                        <td class="table-actions">
                            <a href="send_email.php?company_email=<?= urlencode($c['company_email']) ?>"
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
            <ul>
                <?php foreach ($stats as $s): ?>
                    <li><?= htmlspecialchars($s['status']) ?>: <b><?= $s['total'] ?></b></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Company Directory -->
        <div class="card info-card">
            <h2>Company Directory</h2>
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
                            <td><?= htmlspecialchars($c['company_name']) ?></td>
                            <td><a href="<?= htmlspecialchars($c['company_website']) ?>" target="_blank"><?= htmlspecialchars($c['company_website']) ?></a></td>
                            <td><?= htmlspecialchars($c['company_category']) ?></td>
                            <td><?= htmlspecialchars($c['company_type']) ?></td>
                            <td><?= htmlspecialchars($c['city']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Details Section -->
        <div id="detailsSection" class="card info-card hidden">
            <h2>Contact & Company Details</h2>
            <p id="detailsHint"><i>Klik salah satu tombol "Details" untuk melihat data.</i></p>

            <div id="detailsContent" style="display:none;">
                <div class="card info-card">
                    <h3>Contact Person Details</h3>
                    <p><b>Contact Name:</b> <span id="d_name_person"></span></p>
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
            </div>
        </div>

        <!-- Edit Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>Edit Contact</h2>
                <form id="editForm" method="post" action="edit_contact.php">
                    <input type="hidden" name="company_email" id="edit_company_email">
                    <label for="edit_name_person">Contact Name:</label>
                    <input type="text" name="name_person" id="edit_name_person" required>
                    <label for="edit_person_email">Email:</label>
                    <input type="email" name="person_email" id="edit_person_email" required>
                    <label for="edit_phone_number">Phone:</label>
                    <input type="text" name="phone_number" id="edit_phone_number" required>
                    <label for="edit_company_name">Company:</label>
                    <input type="text" name="company_name" id="edit_company_name" required>
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

            document.getElementById("d_name_person").textContent = contact.name_person || "-";
            document.getElementById("d_person_email").textContent = contact.person_email || "-";
            document.getElementById("d_phone_number").textContent = contact.phone_number || "-";
            document.getElementById("d_position_title").textContent = contact.contact_person_position_title || "-";
            document.getElementById("d_position_category").textContent = contact.contact_person_position_category || "-";
            document.getElementById("d_phone2").textContent = contact.phone_number2 || "-";

            document.getElementById("d_company_name").textContent = contact.company_name || "-";
            document.getElementById("d_company_website").textContent = contact.company_website || "-";
            document.getElementById("d_company_website").href = contact.company_website || "#";
            document.getElementById("d_company_category").textContent = contact.company_category || "-";
            document.getElementById("d_company_type").textContent = contact.company_type || "-";
            document.getElementById("d_address").textContent = contact.address || "-";
            document.getElementById("d_city").textContent = contact.city || "-";
            document.getElementById("d_postcode").textContent = contact.postcode || "-";

            section.classList.remove("hidden");
            hint.style.display = "none";
            content.style.display = "block";

            btn.textContent = "Hide Details";
            activeButton = btn;
        }

        function openEditModal(contact) {
            document.getElementById("edit_company_email").value = contact.company_email;
            document.getElementById("edit_name_person").value = contact.name_person;
            document.getElementById("edit_person_email").value = contact.person_email;
            document.getElementById("edit_phone_number").value = contact.phone_number;
            document.getElementById("edit_company_name").value = contact.company_name;
            document.getElementById("edit_status").value = contact.status;
            document.getElementById("editModal").style.display = "block";
        }

        function closeModal() {
            document.getElementById("editModal").style.display = "none";
        }
    </script>
</body>

</html>