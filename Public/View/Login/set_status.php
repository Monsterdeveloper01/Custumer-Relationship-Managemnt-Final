<?php
require_once __DIR__ . '/../../Model/db.php';
session_start();

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$partner = $_SESSION['user'];
$marketing_id = $partner['marketing_id'];

$email = $_POST['email'] ?? '';
$status = trim($_POST['status'] ?? '');

$allowedStatuses = [
    'input',
    'emailed',
    'contacted',
    'presentation',
    'NDA process',
    'Gap analysis / requirement analysis',
    'Customization',
    'SIT (System Integration Testing)',
    'UAT (User Acceptance Testing)',
    'Proposal',
    'Negotiation',
    'Deal / Closed',
    'Failed / Tidak Lanjut',
    'Postpone'
];

if (!in_array($status, $allowedStatuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status value']);
    exit;
}

// Validasi input
if (empty($email) || empty($status)) {
    echo json_encode(['success' => false, 'error' => 'Email and status are required']);
    exit;
}

try {
    // Cek contact
    $checkStmt = $pdo->prepare("SELECT ditemukan_oleh FROM crm_contacts_staging WHERE email = ?");
    $checkStmt->execute([$email]);
    $contact = $checkStmt->fetch();

    if (!$contact) {
        echo json_encode(['success' => false, 'error' => 'Contact not found']);
        exit;
    }

    // Role check
    if ($partner['role'] !== 'admin' && $contact['ditemukan_oleh'] !== $marketing_id) {
        echo json_encode(['success' => false, 'error' => 'You can only update your own contacts']);
        exit;
    }

    // Update hanya status
    $stmt = $pdo->prepare("
        UPDATE crm_contacts_staging
        SET status = :status, updated_at = NOW()
        WHERE email = :email
    ");
    $stmt->execute([
        ':status' => $status,
        ':email' => $email
    ]);

    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} catch (PDOException $e) {
    // Jangan expose pesan asli ke user
    error_log("DB Error (set_status.php): " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
