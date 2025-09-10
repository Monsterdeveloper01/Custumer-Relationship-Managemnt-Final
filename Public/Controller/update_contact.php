<?php
require_once '/../Model/db.php';
require_once '/../Controller/functions.php';
// Kalau session sudah aktif jangan dipanggil lagi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$marketingId = $_SESSION['user']['marketing_id'] ?? null;
if (!$marketingId) {
    die("Unauthorized: Anda harus login sebagai marketing.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "UPDATE crm 
        SET company_name = :company_name,
            company_email = :company_email,
            name_person = :name_person,
            person_email = :person_email,
            phone_number = :phone_number,
            phone_number2 = :phone_number2,
            contact_person_position_title = :contact_person_position_title,
            company_website = :company_website,
            company_category = :company_category,
            contact_person_position_category = :contact_person_position_category,
            company_type = :company_type,
            address = :address,
            city = :city,
            postcode = :postcode,
            status = :status
        WHERE company_email = :old_company_email
          AND marketing_id = :marketing_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'company_name' => $_POST['company_name'],
        'company_email' => $_POST['company_email'], // email baru
        'name_person' => $_POST['name_person'],
        'person_email' => $_POST['person_email'],
        'phone_number' => $_POST['phone_number'],
        'phone_number2' => $_POST['phone_number2'],
        'contact_person_position_title' => $_POST['contact_person_position_title'],
        'company_website' => $_POST['company_website'],
        'company_category' => $_POST['company_category'],
        'contact_person_position_category' => $_POST['contact_person_position_category'],
        'company_type' => $_POST['company_type'],
        'address' => $_POST['address'],
        'city' => $_POST['city'],
        'postcode' => $_POST['postcode'],
        'status' => $_POST['status'],
        'old_company_email' => $_POST['old_company_email'], // email lama
        'marketing_id' => $marketingId
    ]);


    header("Location: contact_list.php");
    exit;
}
