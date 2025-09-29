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
    header("Location: dashboard.php");
    exit;
}

// Ambil data partner individual
$sql_individual = "SELECT * FROM partner_individual ORDER BY created_at DESC";
$stmt_individual = $pdo->query($sql_individual);
$partners_individual = $stmt_individual->fetchAll(PDO::FETCH_ASSOC);

// Ambil data partner institution
$sql_institution = "SELECT * FROM partner_institution ORDER BY created_at DESC";
$stmt_institution = $pdo->query($sql_institution);
$partners_institution = $stmt_institution->fetchAll(PDO::FETCH_ASSOC);

// Hitung statistik
$total_individual = count($partners_individual);
$total_institution = count($partners_institution);
$total_partners = $total_individual + $total_institution;

// Hitung status institution
$status_counts = [
    'PENDING' => 0,
    'ACTIVE' => 0,
    'REJECTED' => 0
];
foreach ($partners_institution as $partner) {
    $status_counts[$partner['active_status']]++;
}

// Hitung status individual
$status_counts_individual = [
    'PENDING' => 0,
    'ACTIVE' => 0,
    'REJECTED' => 0
];
foreach ($partners_individual as $partner) {
    $status_counts_individual[$partner['active_status']]++;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Partner List - Admin Dashboard</title>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                        }
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <style>
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-rejected {
            background: #fee2e2;
            color: #dc2626;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .tab-button {
            padding: 10px 20px;
            border: none;
            background: #f3f4f6;
            cursor: pointer;
            border-radius: 6px 6px 0 0;
            margin-right: 5px;
        }

        .tab-button.active {
            background: #3b82f6;
            color: white;
        }
    </style>
</head>

<body>

    <?php include("../Partials/Header.html"); ?>

    <div class="max-w-7xl mx-auto p-6 space-y-6 py-20">
        <h1 class="text-3xl font-bold text-gray-900">Partner Management</h1>
        <p class="text-gray-600">Manage all registered partners and institutions</p>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
            <div class="stat-card">
                <div class="text-3xl font-bold"><?= $total_partners ?></div>
                <div class="text-sm opacity-90">Total Partners</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="text-3xl font-bold"><?= $total_individual ?></div>
                <div class="text-sm opacity-90">Individual Partners</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="text-3xl font-bold"><?= $total_institution ?></div>
                <div class="text-sm opacity-90">Institutions</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="text-3xl font-bold"><?= $status_counts['ACTIVE'] ?></div>
                <div class="text-sm opacity-90">Active Institutions</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%);">
                <div class="text-3xl font-bold"><?= $status_counts_individual['ACTIVE'] ?></div>
                <div class="text-sm opacity-90">Active Individuals</div>
            </div>
        </div>

        <!-- Institution Status Breakdown -->
        <div class="card">
            <h3 class="text-lg font-semibold mb-4">Institution Status</h3>
            <div class="flex gap-4">
                <div class="flex items-center gap-2">
                    <span class="status-badge status-pending">Pending</span>
                    <span class="font-semibold"><?= $status_counts['PENDING'] ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="status-badge status-active">Active</span>
                    <span class="font-semibold"><?= $status_counts['ACTIVE'] ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="status-badge status-rejected">Rejected</span>
                    <span class="font-semibold"><?= $status_counts['REJECTED'] ?></span>
                </div>
            </div>
        </div>

        <!-- Individual Status Breakdown -->
        <div class="card">
            <h3 class="text-lg font-semibold mb-4">Individual Status</h3>
            <div class="flex gap-4">
                <div class="flex items-center gap-2">
                    <span class="status-badge status-pending">Pending</span>
                    <span class="font-semibold"><?= $status_counts_individual['PENDING'] ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="status-badge status-active">Active</span>
                    <span class="font-semibold"><?= $status_counts_individual['ACTIVE'] ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="status-badge status-rejected">Rejected</span>
                    <span class="font-semibold"><?= $status_counts_individual['REJECTED'] ?></span>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="card">
            <div class="flex border-b mb-4">
                <button class="tab-button active" onclick="openTab('individual')">Individual Partners (<?= $total_individual ?>)</button>
                <button class="tab-button" onclick="openTab('institution')">Institutions (<?= $total_institution ?>)</button>
            </div>

            <!-- Individual Partners Tab -->
            <div id="individual" class="tab-content active">
                <div class="overflow-x-auto">
                    <table id="individualTable" class="display w-full text-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Ref ID</th>
                                <th>Promo Code</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>WhatsApp</th>
                                <th>Bank Account</th>
                                <th>Profil Jaringan</th>
                                <th>Industry Focus</th>
                                <th>Referral Awal</th>
                                <th>Status</th>
                                <th>Registration Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($partners_individual as $i => $partner): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><code><?= htmlspecialchars($partner['ref_id']) ?></code></td>
                                    <td><code><?= htmlspecialchars($partner['promo_code']) ?></code></td>
                                    <td><?= htmlspecialchars($partner['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($partner['email']) ?></td>
                                    <td><?= htmlspecialchars($partner['whatsapp']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($partner['nama_bank']) ?><br>
                                        <small><?= htmlspecialchars($partner['no_rekening']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($partner['profil_jaringan'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($partner['segment_industri_fokus'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($partner['referral_awal'] ?? '-') ?></td>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($partner['active_status']) ?>">
                                            <?= $partner['active_status'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('d M Y H:i', strtotime($partner['created_at'])) ?></td>
                                    <td>
                                        <div class="flex gap-2">
                                            <?php if ($partner['active_status'] === 'PENDING'): ?>
                                                <button onclick="updateIndividualStatus('<?= $partner['ref_id'] ?>', 'ACTIVE')"
                                                    class="px-3 py-1 bg-green-500 text-white rounded text-sm">
                                                    Approve
                                                </button>
                                                <button onclick="updateIndividualStatus('<?= $partner['ref_id'] ?>', 'REJECTED')"
                                                    class="px-3 py-1 bg-red-500 text-white rounded text-sm">
                                                    Reject
                                                </button>
                                            <?php elseif ($partner['active_status'] === 'ACTIVE'): ?>
                                                <button onclick="updateIndividualStatus('<?= $partner['ref_id'] ?>', 'REJECTED')"
                                                    class="px-3 py-1 bg-red-500 text-white rounded text-sm">
                                                    Deactivate
                                                </button>
                                            <?php else: ?>
                                                <button onclick="updateIndividualStatus('<?= $partner['ref_id'] ?>', 'ACTIVE')"
                                                    class="px-3 py-1 bg-green-500 text-white rounded text-sm">
                                                    Reactivate
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Institutions Tab -->
            <div id="institution" class="tab-content">
                <div class="overflow-x-auto">
                    <table id="institutionTable" class="display w-full text-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Ref ID</th>
                                <th>Promo Code</th>
                                <th>Institution Name</th>
                                <th>Email</th>
                                <th>WhatsApp</th>
                                <th>Bank Account</th>
                                <th>Profil Jaringan</th>
                                <th>Industry Focus</th>
                                <th>Referral Awal</th>
                                <th>Status</th>
                                <th>Registration Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($partners_institution as $i => $partner): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><code><?= htmlspecialchars($partner['ref_id']) ?></code></td>
                                    <td><code><?= htmlspecialchars($partner['kode_institusi']) ?></code></td>
                                    <td><?= htmlspecialchars($partner['nama_institusi']) ?></td>
                                    <td><?= htmlspecialchars($partner['email']) ?></td>
                                    <td><?= htmlspecialchars($partner['whatsapp']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($partner['nama_bank']) ?><br>
                                        <small><?= htmlspecialchars($partner['no_rekening']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($partner['profil_jaringan'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($partner['segment_industri_fokus'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($partner['referral_awal'] ?? '-') ?></td>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($partner['active_status']) ?>">
                                            <?= $partner['active_status'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('d M Y H:i', strtotime($partner['created_at'])) ?></td>
                                    <td>
                                        <div class="flex gap-2">
                                            <?php if ($partner['active_status'] === 'PENDING'): ?>
                                                <button onclick="updateStatus('<?= $partner['ref_id'] ?>', 'ACTIVE')"
                                                    class="px-3 py-1 bg-green-500 text-white rounded text-sm">
                                                    Approve
                                                </button>
                                                <button onclick="updateStatus('<?= $partner['ref_id'] ?>', 'REJECTED')"
                                                    class="px-3 py-1 bg-red-500 text-white rounded text-sm">
                                                    Reject
                                                </button>
                                            <?php elseif ($partner['active_status'] === 'ACTIVE'): ?>
                                                <button onclick="updateStatus('<?= $partner['ref_id'] ?>', 'REJECTED')"
                                                    class="px-3 py-1 bg-red-500 text-white rounded text-sm">
                                                    Deactivate
                                                </button>
                                            <?php else: ?>
                                                <button onclick="updateStatus('<?= $partner['ref_id'] ?>', 'ACTIVE')"
                                                    class="px-3 py-1 bg-green-500 text-white rounded text-sm">
                                                    Reactivate
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <script>
                $(document).ready(function() {
                    $('#individualTable').DataTable({
                        pageLength: 10,
                        ordering: true,
                        order: [
                            [8, 'desc']
                        ] // Sort by registration date
                    });

                    $('#institutionTable').DataTable({
                        pageLength: 10,
                        ordering: true,
                        order: [
                            [9, 'desc']
                        ] // Sort by registration date
                    });
                });

                function openTab(tabName) {
                    // Hide all tab content
                    document.querySelectorAll('.tab-content').forEach(tab => {
                        tab.classList.remove('active');
                    });

                    // Remove active class from all buttons
                    document.querySelectorAll('.tab-button').forEach(button => {
                        button.classList.remove('active');
                    });

                    // Show the specific tab content
                    document.getElementById(tabName).classList.add('active');

                    // Add active class to the clicked button
                    event.currentTarget.classList.add('active');
                }

                function updateStatus(refId, status) {
                    const statusText = status === 'ACTIVE' ? 'approve' :
                        status === 'REJECTED' ? 'reject' : 'update';

                    Swal.fire({
                        title: 'Are you sure?',
                        text: `Do you want to ${statusText} this institution?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: `Yes, ${statusText} it!`
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Send AJAX request to update status
                            fetch('update_institution_status.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                    },
                                    body: `ref_id=${refId}&status=${status}`
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire(
                                            'Updated!',
                                            `Institution has been ${statusText}ed.`,
                                            'success'
                                        ).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire(
                                            'Error!',
                                            data.message || 'Failed to update status.',
                                            'error'
                                        );
                                    }
                                })
                                .catch(error => {
                                    Swal.fire(
                                        'Error!',
                                        'An error occurred while updating status.',
                                        'error'
                                    );
                                });
                        }
                    });
                }

                function updateIndividualStatus(refId, status) {
                    const statusText = status === 'ACTIVE' ? 'approve' :
                        status === 'REJECTED' ? 'reject' : 'update';

                    Swal.fire({
                        title: 'Are you sure?',
                        text: `Do you want to ${statusText} this individual partner?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: `Yes, ${statusText} it!`
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch('update_individual_status.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                    },
                                    body: `ref_id=${refId}&status=${status}`
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire(
                                            'Updated!',
                                            `Individual partner has been ${statusText}ed.`,
                                            'success'
                                        ).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire(
                                            'Error!',
                                            data.message || 'Failed to update status.',
                                            'error'
                                        );
                                    }
                                })
                                .catch(error => {
                                    Swal.fire(
                                        'Error!',
                                        'An error occurred while updating status.',
                                        'error'
                                    );
                                });
                        }
                    });
                }
            </script>
</body>

</html>