<?php
require_once __DIR__ . '/../../backend_secure/Model/db.php';
session_start();

// Kalau belum login, redirect
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Kalau bukan POST, tendang balik
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

$company_email = $_POST['company_email'] ?? '';
$name_person   = $_POST['name_person'] ?? '';
$person_email  = $_POST['person_email'] ?? '';
$phone_number  = $_POST['phone_number'] ?? '';
$company_name  = $_POST['company_name'] ?? '';
$status        = $_POST['status'] ?? 'input';

// Daftar status valid sesuai ENUM
$valid_status = [
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
];

// Defaultkan ke 'input' kalau tidak valid
if (!in_array($status, $valid_status, true)) {
    $status = 'input';
}

try {
    $stmt = $pdo->prepare("
        UPDATE crm 
        SET 
            name_person   = :name_person,
            person_email  = :person_email,
            phone_number  = :phone_number,
            company_name  = :company_name,
            status        = :status,
            updated_at    = NOW()
        WHERE company_email = :company_email
    ");

    $stmt->execute([
        ':name_person'   => $name_person,
        ':person_email'  => $person_email,
        ':phone_number'  => $phone_number,
        ':company_name'  => $company_name,
        ':status'        => $status,
        ':company_email' => $company_email
    ]);

    // balik ke dashboard
    header("Location: dashboard.php?msg=updated");
    exit;
} catch (PDOException $e) {
    // Kalau gagal, kirim pesan error
    header("Location: dashboard.php?error=" . urlencode($e->getMessage()));
    exit;
}
