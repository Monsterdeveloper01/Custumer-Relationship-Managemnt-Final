<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require '../../Model/db.php';

// if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'partner') {
//     die('Akses ditolak.');
// }

$marketing_id = $_SESSION['user']['marketing_id'];
$success = 0;
$failed = 0;

// Daftar kategori yang diizinkan (harus sama dengan form & template)
$allowedCategories = [
    'Banking',
    'Multifinance',
    'Insurance',
    'Manufacturing',
    'Retail',
    'Distribution',
    'Oil & Gas / Energy',
    'Government and Ministry',
    'Koperasi & UMKM',
    'Logistics and Transportation',
    'Hospital and Clinics',
    'Education and Training',
    'Hotels, Restaurant, and Hospitality',
    'Tour and Travel',
    'NGO, LSM, and International Organizations',
    'Property and Real Estate'
];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['bulk_msg'] = "Error upload file.";
        header("Location: dashboard.php");
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        $_SESSION['bulk_msg'] = "Hanya file .csv yang diizinkan.";
        header("Location: dashboard.php");
        exit;
    }

    if (($handle = fopen($file['tmp_name'], 'r')) !== false) {
        $header = fgetcsv($handle); // skip header

        while (($row = fgetcsv($handle)) !== false) {
            // Ambil data dari CSV (sesuai urutan kolom template)
            $nama_perusahaan = trim($row[0] ?? '');
            $email = trim($row[1] ?? '');
            $email_lain = trim($row[2] ?? '');
            $nama = trim($row[3] ?? '');
            $no_telp1 = trim($row[4] ?? '');
            $no_telp2 = trim($row[5] ?? '');
            $website = trim($row[6] ?? '');
            $kategori_perusahaan = trim($row[7] ?? '');
            $kategori_jabatan = trim($row[8] ?? '');
            $jabatan_lengkap = trim($row[9] ?? '');
            $tipe = trim($row[10] ?? '');
            $kota = trim($row[11] ?? '');
            $alamat = trim($row[12] ?? '');

            // Skip baris kosong
            if (empty($email) && empty($nama_perusahaan)) {
                continue;
            }

            // ✅ Validasi 1: Email wajib & valid
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $failed++;
                continue;
            }

            // ✅ Validasi 2: Nama perusahaan wajib
            if (empty($nama_perusahaan)) {
                $failed++;
                continue;
            }

            // ✅ Validasi 3: Tipe perusahaan harus Swasta/Bumn/Bumd
            if (empty($tipe) || !in_array($tipe, ['Swasta', 'Bumn', 'Bumd', 'SWASTA', 'BUMN', 'BUMD'])) {
                $failed++;
                continue;
            }

            // ✅ Validasi 4: Kategori perusahaan (jika diisi, harus sesuai daftar)
            if ($kategori_perusahaan !== '' && !in_array($kategori_perusahaan, $allowedCategories)) {
                $failed++;
                continue;
            }

            // ✅ Cek duplikat berdasarkan email
            $stmt = $pdo->prepare("SELECT email FROM crm_contacts_staging WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $failed++;
                continue;
            }

            // Di dalam loop while di bulk_upload.php
            if (strpos($row[0], 'HAPUS') !== false || strpos($row[1], 'contoh') !== false) {
                continue; // skip baris instruksi/contoh
            }

            // ✅ Insert ke database
            $stmt = $pdo->prepare("
                INSERT INTO crm_contacts_staging 
                (nama_perusahaan, email, email_lain, nama, no_telp1, no_telp2, website,
                 kategori_perusahaan, kategori_jabatan, jabatan_lengkap, tipe,
                 kota, alamat, ditemukan_oleh, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'input')
            ");
            $stmt->execute([
                $nama_perusahaan,
                $email,
                $email_lain,
                $nama,
                $no_telp1,
                $no_telp2,
                $website,
                $kategori_perusahaan,
                $kategori_jabatan,
                $jabatan_lengkap,
                $tipe,
                $kota,
                $alamat,
                $marketing_id
            ]);

            $success++;
        }
        fclose($handle);
    }

    if ($success > 0 || $failed > 0) {
        $_SESSION['bulk_upload_result'] = [
            'success' => $success,
            'failed' => $failed
        ];
    } else {
        $_SESSION['bulk_upload_result'] = ['error' => 'Tidak ada data yang diproses.'];
    }

    header("Location: dashboard.php");
    exit;
}
