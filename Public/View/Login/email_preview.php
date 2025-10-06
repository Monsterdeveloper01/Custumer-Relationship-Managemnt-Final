<?php
require_once __DIR__ . '/../../Model/db.php';
require_once __DIR__ . '/../../Controller/functions.php';
require_login();

$mid            = current_marketing_id();
$marketing_name = $_SESSION['user']['name'];
$promo          = strtoupper(substr(md5($mid . time()), 0, 8));

header("Content-Type: application/json");

$email = $_GET['email'] ?? '';
if (!$email) {
    echo json_encode(['error' => 'Email tidak ditemukan']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM crm_contacts_staging WHERE email = ? AND ditemukan_oleh = ?");
$stmt->execute([$email, $mid]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) {
    echo json_encode(['error' => 'Kontak tidak ditemukan']);
    exit;
}

// Gunakan builder ID (bisa diganti ke EN sesuai pilihan)
list($subject, $body) = build_message_id($c, $marketing_name, $promo);

// Escape teks tapi izinkan <br> dan <a>
$body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');

// Balikin <br> jadi tag beneran
$body = str_replace('&lt;br&gt;', '<br>', $body);

// Deteksi link lagi biar clickable
$body = preg_replace(
    '/(https?:\/\/[^\s]+)/i',
    '<a href="$1" target="_blank" style="color:#2563eb;text-decoration:underline;">$1</a>',
    $body
);

// Jaga newline
$body = nl2br($body);

echo json_encode([
    'subject' => $subject,
    'body'    => $body,
    'email'   => $email
]);
