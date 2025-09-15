<?php
// delete_contact.php
require_once __DIR__ . '/../Model/db.php';
session_start();

$marketingId = $_SESSION['user']['marketing_id'] ?? null;
if (!$marketingId) {
    die("Unauthorized");
}

if (!isset($_GET['email']) || empty($_GET['email'])) {
    $_SESSION['toast'] = ['icon' => 'error', 'msg' => 'Email tidak valid!'];
    header("Location: ../View/contact_list.php");
    exit;
}

$companyEmail = $_GET['email'];

$sql = "DELETE FROM crm WHERE company_email = :email AND marketing_id = :marketing_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':email' => $companyEmail,
    ':marketing_id' => $marketingId
]);

if ($stmt->rowCount() > 0) {
    $_SESSION['toast'] = ['icon' => 'success', 'msg' => 'Kontak berhasil dihapus!'];
} else {
    $_SESSION['toast'] = ['icon' => 'error', 'msg' => 'Gagal menghapus: kontak tidak ditemukan atau bukan milikmu!'];
}

header("Location: ../View/contact_list.php");
exit;

