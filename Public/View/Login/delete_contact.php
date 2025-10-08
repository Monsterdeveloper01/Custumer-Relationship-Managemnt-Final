<?php
session_start();
require '../../Model/db.php';

// Cek login & role admin
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metode tidak diizinkan']);
    exit;
}

$email = $_POST['email'] ?? null;
if (!$email) {
    echo json_encode(['error' => 'Email tidak diberikan']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM crm_contacts_staging WHERE email = ?");
    $result = $stmt->execute([$email]);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Gagal menghapus data']);
    }
} catch (Exception $e) {
    error_log("Delete error: " . $e->getMessage());
    echo json_encode(['error' => 'Terjadi kesalahan server']);
}
?>