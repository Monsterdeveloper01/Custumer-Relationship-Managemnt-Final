<?php
require '../backend/dbconnection.php';

// Ambil filter dari URL
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Gabungkan hasil
$partners = [];

// Query institusi
if ($filter === 'institution' || $filter === 'all') {
    $result = $conn->query("SELECT kode_institusi_partner AS kode, nama_institusi AS nama, whatsapp, email, 'Institution' AS jenis 
                            FROM institusi_partner");
    while ($row = $result->fetch_assoc()) {
        $partners[] = $row;
    }
}

// Query individual
if ($filter === 'individual' || $filter === 'all') {
    $result = $conn->query("SELECT promo_code AS kode, nama_lengkap AS nama, whatsapp, email, 'Individual' AS jenis 
                            FROM individual_promocodes");
    while ($row = $result->fetch_assoc()) {
        $partners[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Partner List</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #fff;
        }
        .badge.individual { background: #059bb6; }
        .badge.institution { background: #4f46e5; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="title">Registered Partner List</h1>
        <p class="subtitle">Below is the list of partners who have registered in the system.</p>

        <!-- Button to input.php -->
        <div class="button-group" style="margin-bottom:20px;">
            <a href="input.php" class="btn primary">+ Add Partner</a>
        </div>

        <!-- Filter Dropdown -->
        <form method="get" style="margin-bottom:20px;">
            <label for="filter"><b>Filter by Partner Type:</b></label>
            <select name="filter" id="filter" onchange="this.form.submit()">
                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All</option>
                <option value="individual" <?= $filter === 'individual' ? 'selected' : '' ?>>Individual</option>
                <option value="institution" <?= $filter === 'institution' ? 'selected' : '' ?>>Institution</option>
            </select>
        </form>

        <!-- Partner List Table -->
        <div class="card info-card partner-list">
            <table class="partner-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>WhatsApp</th>
                        <th>Email</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($partners) > 0): ?>
                        <?php foreach ($partners as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['kode']) ?></td>
                                <td><?= htmlspecialchars($p['nama']) ?></td>
                                <td><?= htmlspecialchars($p['whatsapp']) ?></td>
                                <td><?= htmlspecialchars($p['email']) ?></td>
                                <td>
                                    <span class="badge <?= strtolower($p['jenis']) ?>">
                                        <?= htmlspecialchars($p['jenis']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">No partner data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>