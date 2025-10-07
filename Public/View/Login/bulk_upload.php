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
$failed_invalid = 0;   // data tidak valid (email salah, nama kosong, dll)
$failed_duplicate = 0; // email sudah ada

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
        $_SESSION['bulk_upload_result'] = ['error' => 'Error upload file.'];
        header("Location: dashboard.php");
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        $_SESSION['bulk_upload_result'] = ['error' => 'Hanya file .csv yang diizinkan.'];
        header("Location: dashboard.php");
        exit;
    }

    if (($handle = fopen($file['tmp_name'], 'r')) !== false) {
        $header = fgetcsv($handle); // skip header

        while (($row = fgetcsv($handle)) !== false) {
            // Ambil data
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

            // Skip baris instruksi/contoh
            if (strpos($row[0], 'HAPUS') !== false || strpos($row[1], 'contoh') !== false) {
                continue;
            }

            // Skip baris kosong
            if (empty($email) && empty($nama_perusahaan)) {
                continue;
            }

            // ✅ Validasi: email wajib & valid
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $failed_invalid++;
                continue;
            }

            // ✅ Validasi: nama_perusahaan wajib
            if (empty($nama_perusahaan)) {
                $failed_invalid++;
                continue;
            }

            // ✅ Validasi: tipe perusahaan
            if (empty($tipe) || !in_array($tipe, ['Swasta', 'Bumn', 'Bumd'])) {
                $failed_invalid++;
                continue;
            }

            // ✅ Validasi: kategori perusahaan
            if ($kategori_perusahaan !== '' && !in_array($kategori_perusahaan, $allowedCategories)) {
                $failed_invalid++;
                continue;
            }

            // ✅ Cek duplikat berdasarkan email (PRIMARY KEY)
            $stmt = $pdo->prepare("SELECT 1 FROM crm_contacts_staging WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $failed_duplicate++;
                continue;
            }

            // ✅ Insert
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

    // Hitung total baris yang dibaca (termasuk header)
    $totalRows = 0;
    $processedRows = 0;

    if (($handle = fopen($file['tmp_name'], 'r')) !== false) {
        $header = fgetcsv($handle); // skip header
        $totalRows = 1; // header dihitung

        while (($row = fgetcsv($handle)) !== false) {
            $totalRows++;
            $processedRows++; // coba proses

            // Skip jika baris terlalu pendek
            if (count($row) < 13) {
                $failed_invalid++;
                continue;
            }

            // Ambil data
            $nama_perusahaan = trim($row[0] ?? '');
            $email = trim($row[1] ?? '');
            // ... (ambil semua kolom seperti sebelumnya)

            // Skip baris instruksi HANYA jika mengandung teks khusus DI KOLOM PERTAMA
            if (strpos($row[0], 'HAPUS') !== false || strpos($row[1], 'contoh@') !== false) {
                $processedRows--; // batalkan hitungan
                continue;
            }

            // Skip baris benar-benar kosong
            if (empty($email) && empty($nama_perusahaan)) {
                $processedRows--;
                continue;
            }

            // ... (validasi & insert seperti sebelumnya)
        }
        fclose($handle);
    }

    // 🔥 Tentukan pesan berdasarkan kondisi
    if ($totalRows <= 1) {
        // Hanya header, tidak ada data
        $_SESSION['bulk_upload_result'] = [
            'error' => 'File CSV kosong. Tidak ada data untuk diupload.',
            'detail' => 'Pastikan file berisi minimal 1 baris data di bawah header.'
        ];
    } elseif ($processedRows === 0) {
        // Ada baris, tapi semua di-skip
        $_SESSION['bulk_upload_result'] = [
            'error' => 'Tidak ada data valid yang diproses.',
            'detail' => 'Pastikan Anda menghapus baris contoh/instruksi sebelum upload.'
        ];
    } else {
        // Ada data diproses → tampilkan hasil
        $_SESSION['bulk_upload_result'] = [
            'success' => $success,
            'failed_invalid' => $failed_invalid,
            'failed_duplicate' => $failed_duplicate
        ];
    }

    header("Location: dashboard.php");
    exit;
}
