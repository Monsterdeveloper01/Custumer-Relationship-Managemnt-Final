<?php
require_once __DIR__ . '/../../Model/db.php';
require_once __DIR__ . '/../../Controller/functions.php';
require_login();
$mid = current_marketing_id();



$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_email = trim($_POST['company_email'] ?? '');
    $company_name  = trim($_POST['company_name'] ?? '');
    $name_person   = trim($_POST['name_person'] ?? '');
    $position      = trim($_POST['contact_person_position_title'] ?? '');
    $phone_number  = trim($_POST['phone_number'] ?? '');
    $phone_number2 = trim($_POST['phone_number2'] ?? '');
    $status        = $_POST['status'] ?? 'input';
    $address       = trim($_POST['address'] ?? '');
    $city          = trim($_POST['city'] ?? '');
    $postcode      = trim($_POST['postcode'] ?? '');

    if ($company_email === '') {
        $errors[] = "Company email wajib diisi.";
    } elseif (!filter_var($company_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }
    if ($company_name === '') $errors[] = "Company name wajib diisi.";

    if (empty($errors)) {
        $cek = $pdo->prepare("SELECT COUNT(*) FROM crm WHERE company_email = ? AND marketing_id = ?");
        $cek->execute([$company_email, $mid]);
        if ($cek->fetchColumn() > 0) {
            $errors[] = "Email perusahaan sudah terdaftar di akun Anda.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO crm
                (marketing_id, company_email, company_name, name_person,
                 contact_person_position_title, phone_number, phone_number2,
                 status, address, city, postcode)
                VALUES
                (:marketing_id, :company_email, :company_name, :name_person,
                 :contact_person_position_title, :phone_number, :phone_number2,
                 :status, :address, :city, :postcode)");
            $stmt->execute([
                ':marketing_id'=>$mid,
                ':company_email'=>$company_email,
                ':company_name'=>$company_name,
                ':name_person'=>$name_person,
                ':contact_person_position_title'=>$position,
                ':phone_number'=>$phone_number,
                ':phone_number2'=>$phone_number2,
                ':status'=>$status,
                ':address'=>$address,
                ':city'=>$city,
                ':postcode'=>$postcode
            ]);
            header("Location: dashboard.php");
            exit;
        }
    }
}
if (!function_exists('h')) {
    function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Add Contact | CRM</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-100 min-h-screen flex items-center justify-center p-6">

<div class="w-full max-w-3xl bg-white rounded-3xl shadow-2xl overflow-hidden transform transition-all hover:shadow-blue-300 hover:scale-[1.01]">
  <!-- Header -->
  <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-7">
      <h1 class="text-3xl font-bold text-white flex items-center gap-2">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Add New Contact
      </h1>
      <p class="text-blue-100 text-sm mt-1">Masukkan detail perusahaan dan kontak yang ingin disimpan.</p>
  </div>

  <div class="p-8 space-y-6">
    <?php if ($errors): ?>
      <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl shadow-sm">
        <ul class="list-disc pl-5 text-sm space-y-1">
          <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Company Email -->
      <div class="col-span-2">
        <label class="block text-sm font-semibold text-gray-700">Company Email<span class="text-red-500">*</span></label>
        <input type="email" name="company_email" value="<?=h($_POST['company_email'] ?? '')?>"
          class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
      </div>

      <!-- Company Name -->
      <div>
        <label class="block text-sm font-semibold text-gray-700">Company Name<span class="text-red-500">*</span></label>
        <input type="text" name="company_name" value="<?=h($_POST['company_name'] ?? '')?>"
          class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
      </div>

      <!-- Contact Person -->
      <div>
        <label class="block text-sm font-semibold text-gray-700">Contact Person</label>
        <input type="text" name="name_person" value="<?=h($_POST['name_person'] ?? '')?>"
          class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
      </div>

      <!-- Position -->
      <div>
        <label class="block text-sm font-semibold text-gray-700">Position Title</label>
        <input type="text" name="contact_person_position_title" value="<?=h($_POST['contact_person_position_title'] ?? '')?>"
          class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
      </div>

      <!-- Phone 1 -->
      <div>
        <label class="block text-sm font-semibold text-gray-700">Phone Number</label>
        <input type="text" name="phone_number" value="<?=h($_POST['phone_number'] ?? '')?>"
          class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
      </div>

      <!-- Phone 2 -->
      <div>
        <label class="block text-sm font-semibold text-gray-700">Phone Number 2</label>
        <input type="text" name="phone_number2" value="<?=h($_POST['phone_number2'] ?? '')?>"
          class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
      </div>

      <!-- Address -->
      <div class="col-span-2">
        <label class="block text-sm font-semibold text-gray-700">Address</label>
        <textarea name="address" rows="3"
          class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition"><?=h($_POST['address'] ?? '')?></textarea>
      </div>

      <!-- City & Postcode -->
      <div>
        <label class="block text-sm font-semibold text-gray-700">City</label>
        <input type="text" name="city" value="<?=h($_POST['city'] ?? '')?>"
          class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700">Postcode</label>
        <input type="text" name="postcode" value="<?=h($_POST['postcode'] ?? '')?>"
          class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
      </div>

      <!-- Status -->
      <div class="col-span-2">
        <label class="block text-sm font-semibold text-gray-700">Status</label>
        <select name="status"
          class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-400 p-3 transition">
          <?php
          foreach (['input','emailed','presentation','NDA process','Gap analysis / requirement analysis','SIT (System Integration Testing)', 'UAT (User Acceptance Testing)', 'Proposal', 'Negotiation', 'Deal / Closed', 'Failed / Tidak Lanjut', 'Postpone'] as $s) {
            $sel = ($s === ($_POST['status'] ?? 'input')) ? 'selected' : '';
            echo "<option value='".h($s)."' $sel>".ucfirst($s)."</option>";
          }
          ?>
        </select>
      </div>

      <!-- Buttons -->
      <div class="col-span-2 flex justify-end gap-4 pt-4">
        <a href="dashboard.php"
           class="px-6 py-2 rounded-xl bg-gray-200 text-gray-700 hover:bg-gray-300 font-semibold transition">
          Cancel
        </a>
        <button type="submit"
          class="px-6 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 font-semibold shadow-md transition transform hover:scale-105">
          Save Contact
        </button>
      </div>
    </form>
  </div>
</div>
</body>
</html>
