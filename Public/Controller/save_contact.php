<?php
require_once __DIR__ . '/../Model/db.php';
require_once __DIR__ . '/../Controller/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$marketingId = $_SESSION['user']['marketing_id'] ?? null;
if (!$marketingId) {
    die("Unauthorized: Anda harus login sebagai marketing.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyEmail = trim($_POST['company_email']);

    // Cek apakah email perusahaan sudah ada
    $check = $pdo->prepare("SELECT company_email FROM crm WHERE company_email = :email");
    $check->execute([':email' => $companyEmail]);

    if ($check->fetch()) {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => "Email perusahaan <b>$companyEmail</b> sudah terdaftar!"
        ];
        header("Location: ../View/contact_list.php");
        exit;
    }

    $sql = "INSERT INTO crm 
        (company_name, name_person, company_email, person_email, phone_number,phone_number2, 
         contact_person_position_title, company_website, company_category, 
         contact_person_position_category, company_type, address, city, postcode, status, marketing_id)
        VALUES 
        (:company_name, :name_person, :company_email, :person_email, :phone_number, :phone_number2,
         :contact_person_position_title, :company_website, :company_category, 
         :contact_person_position_category, :company_type, :address, :city, :postcode, :status, :marketing_id)";

    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([
            'company_name' => trim($_POST['company_name']),
            'name_person' => trim($_POST['name_person']),
            'company_email' => $companyEmail,
            'person_email' => trim($_POST['person_email']),
            'phone_number' => trim($_POST['phone_number']),
            'phone_number2' => trim($_POST['phone_number2']),
            'contact_person_position_title' => trim($_POST['contact_person_position_title']),
            'company_website' => trim($_POST['company_website']),
            'company_category' => trim($_POST['company_category']),
            'contact_person_position_category' => trim($_POST['contact_person_position_category']),
            'company_type' => trim($_POST['company_type']),
            'address' => trim($_POST['address']),
            'city' => trim($_POST['city']),
            'postcode' => trim($_POST['postcode']),
            'status' => trim($_POST['status']),
            'marketing_id' => $marketingId
        ]);

        $_SESSION['toast'] = [
            'type' => 'success',
            'message' => "Kontak berhasil ditambahkan!"
        ];
    } catch (PDOException $e) {
        $_SESSION['toast'] = [
            'type' => 'error',
            'message' => "Error DB: " . $e->getMessage()
        ];
    }

    header("Location: ../View/contact_list.php");
    exit;
}
