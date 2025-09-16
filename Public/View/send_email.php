<?php
require_once __DIR__ . '/../Model/db.php';
require_once __DIR__ . '/../Controller/functions.php';
require_login();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../../vendor/autoload.php';

$mid             = current_marketing_id();
$marketing_name  = $_SESSION['user']['name'];
$marketing_email = $_SESSION['user']['email'];

$stmt = $pdo->prepare("SELECT company_email, company_name, name_person
                       FROM crm
                       WHERE marketing_id = :mid");
$stmt->execute(['mid' => $mid]);
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$info            = '';
$selectedEmail   = $_GET['company_email'] ?? ''; // pre-select by query string
$default_subject = '';
$default_body    = '';
$selectedCompany = null;

/**
 * Fungsi untuk membuat template email default
 */
function build_default_message(array $company, string $marketing_name): array {
    $subject = "Kesempatan Kerja Sama antara PT Rayterton Indonesia & {$company['company_name']}";
    $body    = "Halo " . ($company['name_person'] ?: $company['company_name']) . ",\n\n"
             . "Perkenalkan, saya {$marketing_name} dari PT Rayterton Indonesia. "
             . "Saya menghubungi Bapak/Ibu karena melihat ada potensi kerja sama yang baik "
             . "antara PT Rayterton Indonesia dan {$company['company_name']}.\n\n"
             . "Apakah Bapak/Ibu berkenan meluangkan waktu untuk diskusi singkat minggu ini?\n\n"
             . "Salam,\n{$marketing_name}";
    return [$subject, $body];
}

/* --- Jika user memilih perusahaan lewat dropdown (GET atau POST tanpa kirim email) --- */
if ($selectedEmail || (isset($_POST['company_email']) && !isset($_POST['send_email']))) {
    $email = $selectedEmail ?: $_POST['company_email'];
    $stmt  = $pdo->prepare("SELECT * FROM crm WHERE company_email = :cid AND marketing_id = :mid");
    $stmt->execute(['cid' => $email, 'mid' => $mid]);
    $selectedCompany = $stmt->fetch();
    if ($selectedCompany) {
        [$default_subject, $default_body] = build_default_message($selectedCompany, $marketing_name);
        $selectedEmail = $selectedCompany['company_email']; // pastikan konsisten
    }
}

/* --- Kirim email --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $cid     = $_POST['company_email'] ?? '';
    $subject = trim($_POST['subject'] ?? '');
    $body    = trim($_POST['body'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM crm WHERE company_email = :cid AND marketing_id = :mid");
    $stmt->execute(['cid' => $cid, 'mid' => $mid]);
    $data = $stmt->fetch();

    if (!$data) {
        $info = "❌ Data tidak ditemukan atau akses ditolak.";
    } else {
        $to   = $data['company_email'];
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'mail.rayterton.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'marketing@rayterton.com';
            $mail->Password   = 'RTNmainServer'; // ganti dengan password asli (gunakan .env)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom('marketing@rayterton.com', "CRM - {$marketing_name}");
            $mail->addAddress($to);
            $mail->addReplyTo('marketing@rayterton.com', "CRM - {$marketing_name}");

            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->send();

            $stmt = $pdo->prepare("UPDATE crm
                                   SET status = 'emailed', updated_at = NOW()
                                   WHERE company_email = :cid AND marketing_id = :mid");
            $stmt->execute(['cid' => $cid, 'mid' => $mid]);

            $info = "✅ Email berhasil dikirim ke {$to}. Status diupdate menjadi 'emailed'.";
        } catch (Exception $e) {
            $info = "❌ Gagal mengirim email. Error: {$mail->ErrorInfo}";
        }
    }
}

function h($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kirim Email - CRM</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex">

<main class="flex-1 p-6">
  <div class="max-w-3xl mx-auto bg-white rounded-xl shadow-md p-6">
    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2 mb-6">
      <i class="fa-solid fa-envelope text-blue-600"></i> Kirim Email
    </h2>

    <?php if ($info): ?>
      <div class="mb-6 px-4 py-3 rounded-lg border 
                  <?= strpos($info,'berhasil')!==false
                        ? 'bg-green-100 text-green-700 border-green-300'
                        : 'bg-red-100 text-red-700 border-red-300' ?>">
        <?= h($info) ?>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-5">
      <!-- Dropdown perusahaan -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Perusahaan</label>
        <select name="company_email" class="border rounded p-2 w-full" required>
          <option value="">-- pilih perusahaan --</option>
          <?php foreach ($companies as $c): ?>
            <option value="<?= h($c['company_email']) ?>"
              <?= ($selectedEmail === $c['company_email']) ? 'selected' : '' ?>>
              <?= h($c['company_name']) ?> (<?= h($c['company_email']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php if ($selectedEmail): ?>
        <!-- Subject -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
          <input type="text" name="subject"
            value="<?= h($default_subject) ?>"
            class="w-full p-3 rounded border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
            required>
        </div>

        <!-- Body -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Isi Pesan</label>
          <textarea name="body" rows="10"
            class="w-full p-3 rounded border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 font-mono text-sm"
            required><?= h($default_body) ?></textarea>
        </div>

        <button type="submit" name="send_email"
          class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow transition">
          <i class="fa-solid fa-paper-plane"></i> Kirim Email
        </button>
      <?php endif; ?>

      <!-- Cancel -->
      <a href="/custumer-Relationship-Managemnt-final/Public/View/Login/dashboard.php"
         class="px-6 py-2 rounded-xl bg-gray-200 text-gray-700 hover:bg-gray-300 font-semibold transition inline-block">
        Cancel
      </a>
    </form>
  </div>
</main>

</body>
</html>
