<?php
require '../../Model/db.php';
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

$email      = $_POST['person_email'] ?? '';
$nama_perusahaan = $_POST['company_name'] ?? '';
$no_telp1   = $_POST['phone_number'] ?? '';
$status     = $_POST['status'] ?? 'input';

// Daftar status valid sesuai ENUM dashboard
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
    UPDATE crm_contacts_staging 
    SET 
        nama_perusahaan = :nama_perusahaan,
        no_telp1        = :no_telp1,
        status          = :status,
        updated_at      = NOW()
    WHERE email = :email
");

    $stmt->execute([
        ':nama_perusahaan'  => $nama_perusahaan,
        ':email'      => $email,
        ':no_telp1'   => $no_telp1,
        ':status'     => $status
    ]);


    // balik ke dashboard
    header("Location: dashboard.php?msg=updated");
    exit;
} catch (PDOException $e) {
    // Kalau gagal, kirim pesan error
    header("Location: dashboard.php?error=" . urlencode($e->getMessage()));
    exit;
}
