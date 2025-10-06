<?php
require_once __DIR__ . '/../../Model/db.php';
session_start();
header('Content-Type: application/json; charset=utf-8'); 

// Cek auth
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Kalau bukan POST, tendang balik
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$partner = $_SESSION['user'];
$marketing_id = $partner['marketing_id'];

// Ambil data dari POST
$email = $_POST['email'] ?? ''; // email lama
$new_email = trim($_POST['new_email'] ?? '');
$nama_perusahaan = trim($_POST['nama_perusahaan'] ?? '');
$kategori_perusahaan = trim($_POST['kategori_perusahaan'] ?? '');
$tipe = trim($_POST['tipe'] ?? '');
$website = trim($_POST['website'] ?? '');
$nama = trim($_POST['nama'] ?? '');
$jabatan_lengkap = trim($_POST['jabatan_lengkap'] ?? '');
$kategori_jabatan = trim($_POST['kategori_jabatan'] ?? '');
$email_lain = trim($_POST['email_lain'] ?? '');
$no_telp1 = trim($_POST['no_telp1'] ?? '');
$no_telp2 = trim($_POST['no_telp2'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$kota = trim($_POST['kota'] ?? '');

// Validasi required fields
if (empty($email) || empty($nama_perusahaan) || empty($no_telp1) || empty($tipe) || empty($new_email)) {
    echo json_encode(['success' => false, 'error' => 'Required fields are missing']);
    exit;
}

// Validasi format email baru
if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

// Cek apakah new_email sudah dipakai contact lain
$dupCheck = $pdo->prepare("SELECT email FROM crm_contacts_staging WHERE email = ? AND email != ?");
$dupCheck->execute([$new_email, $email]);
if ($dupCheck->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Email already exists for another contact']);
    exit;
}

try {
    // Cek apakah contact milik marketing ini
    $checkStmt = $pdo->prepare("SELECT ditemukan_oleh FROM crm_contacts_staging WHERE email = ?");
    $checkStmt->execute([$email]);
    $contact = $checkStmt->fetch();

    if (!$contact) {
        echo json_encode(['success' => false, 'error' => 'Contact not found']);
        exit;
    }

    // Pastikan hanya bisa edit contact milik sendiri (kecuali admin)
    if ($partner['role'] !== 'admin' && $contact['ditemukan_oleh'] !== $marketing_id) {
        echo json_encode(['success' => false, 'error' => 'You can only edit your own contacts']);
        exit;
    }

    // Update semua field yang diperbolehkan
    $stmt = $pdo->prepare("
        UPDATE crm_contacts_staging 
        SET 
            email = :new_email,
            nama_perusahaan = :nama_perusahaan,
            kategori_perusahaan = :kategori_perusahaan,
            tipe = :tipe,
            website = :website,
            nama = :nama,
            jabatan_lengkap = :jabatan_lengkap,
            kategori_jabatan = :kategori_jabatan,
            email_lain = :email_lain,
            no_telp1 = :no_telp1,
            no_telp2 = :no_telp2,
            alamat = :alamat,
            kota = :kota,
            updated_at = NOW()
        WHERE email = :email
    ");

    $stmt->execute([
        ':new_email' => $new_email,
        ':nama_perusahaan' => $nama_perusahaan,
        ':kategori_perusahaan' => $kategori_perusahaan,
        ':tipe' => $tipe,
        ':website' => $website,
        ':nama' => $nama,
        ':jabatan_lengkap' => $jabatan_lengkap,
        ':kategori_jabatan' => $kategori_jabatan,
        ':email_lain' => $email_lain,
        ':no_telp1' => $no_telp1,
        ':no_telp2' => $no_telp2,
        ':alamat' => $alamat,
        ':kota' => $kota,
        ':email' => $email
    ]);

    echo json_encode(['success' => true, 'message' => 'Contact updated successfully']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
