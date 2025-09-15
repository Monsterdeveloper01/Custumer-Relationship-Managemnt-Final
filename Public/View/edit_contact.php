<?php
// edit_contact.php
require_once __DIR__ . '/../../backend_secure/Model/db.php';
require_once __DIR__ . '/../../backend_secure/functions.php';
require_login();
$mid = current_marketing_id();

$email = $_GET['email'] ?? '';
if (!$email) { header('Location: dashboard.php'); exit; }

// ambil data, pastikan milik marketing ini
$stmt = $pdo->prepare("SELECT * FROM crm WHERE company_email = ? AND marketing_id = ?");
$stmt->execute([$email, $mid]);
$data = $stmt->fetch();
if (!$data) {
    die("Data tidak ditemukan atau Anda tidak berhak mengeditnya.");
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $name_person = trim($_POST['name_person'] ?? '');
    $contact_person_position_title = trim($_POST['contact_person_position_title'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $phone_number2 = trim($_POST['phone_number2'] ?? '');
    $status = $_POST['status'] ?? 'input';
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');

    if ($company_name === '') $errors[] = "Company name wajib diisi.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE crm SET
            company_name = :company_name,
            name_person = :name_person,
            contact_person_position_title = :contact_person_position_title,
            phone_number = :phone_number,
            phone_number2 = :phone_number2,
            status = :status,
            address = :address,
            city = :city,
            postcode = :postcode
            WHERE company_email = :company_email AND marketing_id = :marketing_id
        ");
        $stmt->execute([
            ':company_name'=>$company_name,
            ':name_person'=>$name_person,
            ':contact_person_position_title'=>$contact_person_position_title,
            ':phone_number'=>$phone_number,
            ':phone_number2'=>$phone_number2,
            ':status'=>$status,
            ':address'=>$address,
            ':city'=>$city,
            ':postcode'=>$postcode,
            ':company_email'=>$email,
            ':marketing_id'=>$mid
        ]);
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Edit Contact | CRM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Tailwind -->
  <link href="css/output.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">

  <div class="w-full max-w-2xl bg-white shadow-lg rounded-2xl p-8">
    <h2 class="text-2xl font-bold text-blue-700 mb-6">Edit Contact</h2>

    <?php if($errors): ?>
      <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
        <ul class="list-disc ml-5 text-sm">
          <?php foreach($errors as $e) echo "<li>".h($e)."</li>"; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-5">
      <div>
        <label class="block text-sm font-medium text-gray-700">Company Name</label>
        <input type="text" name="company_name"
          value="<?=h($_POST['company_name'] ?? $data['company_name'])?>"
          class="w-full mt-1 p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Name Person</label>
        <input type="text" name="name_person"
          value="<?=h($_POST['name_person'] ?? $data['name_person'])?>"
          class="w-full mt-1 p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Position Title</label>
        <input type="text" name="contact_person_position_title"
          value="<?=h($_POST['contact_person_position_title'] ?? $data['contact_person_position_title'])?>"
          class="w-full mt-1 p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Phone Number</label>
        <input type="text" name="phone_number"
          value="<?=h($_POST['phone_number'] ?? $data['phone_number'])?>"
          class="w-full mt-1 p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Address</label>
        <textarea name="address"
          class="w-full mt-1 p-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
        ><?=h($_POST['address'] ?? $data['address'])?></textarea>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">City</label>
          <input type="text" name="city"
            value="<?=h($_POST['city'] ?? $data['city'])?>"
            class="w-full mt-1 p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Postcode</label>
          <input type="text" name="postcode"
            value="<?=h($_POST['postcode'] ?? $data['postcode'])?>"
            class="w-full mt-1 p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Status</label>
        <select name="status" class="w-full mt-1 p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
          <?php foreach(['input','wa','emailed','contacted','replied','presentation','CLIENT'] as $s): 
            $sel = (($s === ($_POST['status'] ?? $data['status'])) ? 'selected' : '');
            echo "<option value=\"".h($s)."\" $sel>".h($s)."</option>";
          endforeach; ?>
        </select>
      </div>

      <div class="flex gap-3">
        <button type="submit"
          class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-semibold shadow">
          Update
        </button>
        <a href="dashboard.php"
          class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-5 py-2 rounded-lg font-semibold shadow">
          Back
        </a>
      </div>
    </form>
  </div>

</body>
</html>
