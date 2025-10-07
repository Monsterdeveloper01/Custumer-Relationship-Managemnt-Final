<?php
require '../../Model/db.php';
session_start();

if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    die(json_encode([]));
}

$partner = $_POST['partner'] ?? 'all';

if ($partner === 'all') {
    $stmt = $pdo->query("
        SELECT 
            COALESCE(NULLIF(tipe, ''), 'Unknown') AS type,
            COUNT(*) AS total
        FROM crm_contacts_staging 
        WHERE ditemukan_oleh IS NOT NULL
        GROUP BY tipe
        ORDER BY total DESC
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(NULLIF(tipe, ''), 'Unknown') AS type,
            COUNT(*) AS total
        FROM crm_contacts_staging 
        WHERE ditemukan_oleh = ?
        GROUP BY tipe
        ORDER BY total DESC
    ");
    $stmt->execute([$partner]);
}

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>