<?php
// view_contact.php
require_once __DIR__ . '/../../backend_secure/Model/db.php';
require_once __DIR__ . '/../../backend_secure/functions.php';
require_login();
$mid = current_marketing_id();
$email = $_GET['email'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM crm WHERE company_email = ? AND marketing_id = ?");
$stmt->execute([$email, $mid]);
$data = $stmt->fetch();
if (!$data) { die("Data tidak ditemukan atau bukan milik Anda."); }
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Detail Contact | CRM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Tailwind CDN -->
  <link href="css/output.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">

  <div class="w-full max-w-3xl bg-white rounded-2xl shadow-lg p-8">
    <h2 class="text-2xl font-bold text-blue-700 mb-6">
      <?=h($data['company_name'])?> 
      <span class="block text-sm text-gray-500"><?=h($data['company_email'])?></span>
    </h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
      <div>
        <p class="text-gray-600 font-medium">Name Person</p>
        <p class="mt-1"><?=h($data['name_person'])?> 
          <span class="text-gray-500">(<?=h($data['contact_person_position_title'])?>)</span>
        </p>
      </div>

      <div>
        <p class="text-gray-600 font-medium">Phone / WA</p>
        <p class="mt-1"><?=h($data['phone_number'])?></p>
      </div>

      <div>
        <p class="text-gray-600 font-medium">Phone / WA</p>
        <p class="mt-1"><?=h($data['phone_number2'])?></p>
      </div>

      <div>
        <p class="text-gray-600 font-medium">Website</p>
        <p class="mt-1">
          <a href="<?=h($data['company_website'])?>" target="_blank" class="text-blue-600 hover:underline">
            <?=h($data['company_website'])?>
          </a>
        </p>
      </div>

      <div>
        <p class="text-gray-600 font-medium">Category & Industry</p>
        <p class="mt-1"><?=h($data['company_category'])?> — <?=h($data['company_type'])?></p>
      </div>

      <div class="sm:col-span-2">
        <p class="text-gray-600 font-medium">Address</p>
        <p class="mt-1"><?=nl2br(h($data['address']))?>, <?=h($data['city'])?> <?=h($data['postcode'])?></p>
      </div>

      <div>
        <p class="text-gray-600 font-medium">Status</p>
        <p class="mt-1">
          <span class="px-3 py-1 text-xs font-semibold rounded-full
            <?= $data['status']=='CLIENT' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' ?>">
            <?=h($data['status'])?>
          </span>
        </p>
      </div>
    </div>

    <div class="mt-8 flex flex-wrap gap-3">
      <a href="edit_contact.php?email=<?=urlencode($data['company_email'])?>"
         class="bg-yellow-500 hover:bg-yellow-600 text-white px-5 py-2 rounded-lg text-sm font-semibold shadow">
        Edit
      </a>
      <a href="send_email.php?email=<?=urlencode($data['company_email'])?>"
         class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-semibold shadow">
        Send Email
      </a>
      <a href="dashboard.php"
         class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-5 py-2 rounded-lg text-sm font-semibold shadow">
        Back
      </a>
    </div>
  </div>

</body>
</html>
