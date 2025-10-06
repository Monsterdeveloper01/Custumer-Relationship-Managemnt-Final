<?php
session_start();
// Opsional: aktifkan pengecekan role jika sudah siap production
// if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'partner') {
//     die('Akses ditolak.');
// }

// Format tanggal: YYYY-MM-DD
$tanggal = date('Y-m-d');
$filename = "template_upload_kontak_$tanggal.csv";

header('Content-Type: text/csv');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$fp = fopen('php://output', 'w');

// Header kolom
$headers = [
    'nama_perusahaan',
    'email',
    'email_lain',
    'nama',
    'no_telp1',
    'no_telp2',
    'website',
    'kategori_perusahaan',
    'kategori_jabatan',
    'jabatan_lengkap',
    'tipe',
    'kota',
    'alamat'
];

fputcsv($fp, $headers);

// Daftar kategori yang diizinkan
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

// Baris contoh — dengan keterangan eksplisit
$exampleRow = [
    'PT Contoh Perusahaan',
    'contact@contoh.com',
    'alt@contoh.com',
    'Budi Santoso',
    '08123456789',
    '021-12345678',
    'https://contoh.com',
    implode(' | ', $allowedCategories),
    'Management',
    'Direktur Marketing',
    'Swasta',
    'Jakarta',
    'Jl. Contoh No. 123'
];

fputcsv($fp, $exampleRow);

// 🔴 BARIS PENTING: INSTRUKSI UNTUK USER
$instructionRow = [
    '=== HAPUS BARIS INI SEBELUM UPLOAD ===',
    'Email ini hanya contoh. JANGAN DIUPLOAD.',
    '',
    '',
    '',
    '',
    '',
    'Pilih SATU kategori dari daftar di atas (jangan salin semua)',
    '',
    '',
    'Tipe harus: Swasta, Bumn, atau Bumd',
    '',
    ''
];

fputcsv($fp, $instructionRow);

fclose($fp);
exit;