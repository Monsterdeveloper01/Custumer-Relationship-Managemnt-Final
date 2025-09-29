<?php
session_start();
require '../../Model/db.php';

// Cek auth dan role admin
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$ref_id = $_POST['ref_id'] ?? '';
$status = $_POST['status'] ?? '';

if (empty($ref_id) || !in_array($status, ['PENDING', 'ACTIVE', 'REJECTED'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $sql = "UPDATE partner_institution SET active_status = ? WHERE ref_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$status, $ref_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Institution not found']);
    }
} catch (PDOException $e) {
    error_log("Error updating institution status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>