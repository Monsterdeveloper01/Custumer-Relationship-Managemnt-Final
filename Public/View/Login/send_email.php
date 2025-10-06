<?php
// Debug minimal saat pengembangan (cabut di produksi)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../Model/db.php';
require_once __DIR__ . '/../../Controller/functions.php';
require_login();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../../vendor/autoload.php';

$mid             = current_marketing_id();
$is_admin = ($_SESSION['user']['role'] ?? '') === 'admin';
$marketing_name  = $_SESSION['user']['name'];
$marketing_email = $_SESSION['user']['email'];

// Ambil daftar perusahaan milik marketing ini
if ($is_admin) {
  // Admin: tampilkan SEMUA kontak
  $stmt = $pdo->query("
        SELECT email, nama_perusahaan, nama, jabatan_lengkap, ditemukan_oleh
        FROM crm_contacts_staging
    ");
} else {
  // Partner: hanya miliknya sendiri
  $stmt = $pdo->prepare("
        SELECT email, nama_perusahaan, nama, jabatan_lengkap, ditemukan_oleh
        FROM crm_contacts_staging
        WHERE ditemukan_oleh = :mid
    ");
  $stmt->execute(['mid' => $mid]);
}
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$info               = '';
$selectedEmail      = $_GET['email'] ?? '';
$default_subject    = '';
$default_body       = '';
$selectedCompany    = null;
$language_selected  = 'id'; // default
$debugLog           = '';

// Util
// function h($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

function normalize_email_str(?string $val): string
{
  $val = trim((string)$val);
  if (stripos($val, 'mailto:') === 0) {
    $val = substr($val, 7);
  }
  if (($q = strpos($val, '?')) !== false) {
    $val = substr($val, 0, $q);
  }
  // sanitasi lalu validasi
  $san = filter_var($val, FILTER_SANITIZE_EMAIL);
  return filter_var($san, FILTER_VALIDATE_EMAIL) ? $san : '';
}



// Normalisasi email GET jika ada
if ($selectedEmail) {
  $selectedEmail = normalize_email_str($selectedEmail); // buang 'mailto:' dan query tail, validasi
}

// Pilih perusahaan (GET email atau POST pilih)
if ($selectedEmail || (isset($_POST['email']) && !isset($_POST['send_email']))) {
  $email = $selectedEmail ?: normalize_email_str($_POST['email'] ?? '');
  if ($email) {
    if ($is_admin) {
      // Admin: boleh akses semua kontak
      $stmt = $pdo->prepare("SELECT * FROM crm_contacts_staging WHERE email = :cid");
      $stmt->execute(['cid' => $email]);
    } else {
      // Partner: hanya boleh akses kontak miliknya
      $stmt = $pdo->prepare("SELECT * FROM crm_contacts_staging WHERE email = :cid AND ditemukan_oleh = :mid");
      $stmt->execute(['cid' => $email, 'mid' => $mid]);
    }
    $selectedCompany = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($selectedCompany) {
      $promoCode = $selectedCompany['ditemukan_oleh'];

      // 🔹 Cari nama marketing asli
      $ownerName = $marketing_name; // default: user saat ini
      if ($promoCode) {
        // Cari di users, partner_individual, atau partner_institution
        $ownerStmt = $pdo->prepare("
            SELECT COALESCE(u.name, pi.nama_lengkap, pin.nama_institusi, :default) AS owner_name
            FROM (SELECT 1) dummy
            LEFT JOIN users u ON u.marketing_id = :promoCode COLLATE utf8mb4_general_ci
            LEFT JOIN partner_individual pi ON pi.promo_code = :promoCode COLLATE utf8mb4_general_ci
            LEFT JOIN partner_institution pin ON pin.kode_institusi = :promoCode COLLATE utf8mb4_general_ci
        ");
        $ownerStmt->execute(['promoCode' => $promoCode, 'default' => $promoCode]);
        $ownerRow = $ownerStmt->fetch();
        $ownerName = $ownerRow ? $ownerRow['owner_name'] : $promoCode;
      }

      $language_selected = $_POST['language'] ?? 'id';
      [$default_subject, $default_body] =
        $language_selected === 'en'
        ? build_message_en($selectedCompany, $ownerName, $promoCode)
        : build_message_id($selectedCompany, $ownerName, $promoCode);
      $selectedEmail = $selectedCompany['email'];
    }
  }
}

// Kirim Email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
  $cid     = normalize_email_str($_POST['company_email'] ?? '');
  $subject = trim($_POST['subject'] ?? '');
  $body    = trim($_POST['body'] ?? '');

  if (!$cid) {
    $info = "❌ Email perusahaan tidak valid.";
  } elseif ($subject === '' || $body === '') {
    $info = "❌ Subject atau body kosong.";
  } else {
    $stmt = $pdo->prepare("
            SELECT * FROM crm_contacts_staging
            WHERE email = :cid AND ditemukan_oleh = :mid
        ");
    $stmt->execute(['cid' => $cid, 'mid' => $mid]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
      $info = "❌ Data tidak ditemukan atau akses ditolak.";
    } else {
      $to   = $data['email'];
      $mail = new PHPMailer(true);
      try {
        // Konfigurasi SMTP
        $mail->isSMTP();
        $mail->Host       = 'mail.rayterton.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'marketing@rayterton.com';
        $mail->Password   = 'RTNmainServer'; // Pindahkan ke ENV/konfigurasi aman
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Debug PHPMailer (sementara; cabut di produksi)
        $mail->SMTPDebug  = 2; // client+server
        $mail->Debugoutput = function ($str, $level) use (&$debugLog) {
          $debugLog .= "[$level] $str\n";
        }; // kumpulkan, jangan tampilkan langsung ke user

        // Opsi TLS longgar sementara (diagnosis sertifikat)
        $mail->SMTPOptions = [
          'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
          ],
        ];

        // Header & konten
        $mail->setFrom('marketing@rayterton.com', "{$marketing_name} - PT Rayterton Indonesia");
        $mail->addAddress($to);
        $mail->addReplyTo('marketing@rayterton.com', "{$marketing_name} - PT Rayterton Indonesia");
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        // Kirim
        $mail->send();

        // Update status
        $owner_mid = $data['ditemukan_oleh'];

        $pdo->prepare("
    UPDATE crm_contacts_staging
    SET status = 'emailed', updated_at = NOW()
    WHERE email = :cid AND ditemukan_oleh = :owner_mid
")->execute(['cid' => $cid, 'owner_mid' => $owner_mid]);

        $info = "✅ Email berhasil dikirim ke {$to}. Status diupdate menjadi 'emailed'.";
        if ($debugLog) {
          error_log("[PHPMailer][SUCCESS][$to]\n" . $debugLog);
        }
      } catch (Exception $e) {
        $msg = "❌ Gagal mengirim email. Error: {$mail->ErrorInfo}";
        if ($debugLog) {
          $msg .= " | Debug: " . substr($debugLog, 0, 4000);
          error_log("[PHPMailer][FAIL][$to]\n" . $debugLog);
        }
        $info = $msg;
      }
    }
  }
}





// $cid = normalize_email_str($_POST['company_email'] ?? '');
// if (!$cid) {
//     $info = "❌ Email perusahaan tidak valid atau belum terdaftar.";
// }
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kirim Email - CRM</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>

<body class="bg-gray-50 min-h-screen flex">
  <main class="flex-1 p-6">
    <div class="max-w-3xl mx-auto bg-white rounded-xl shadow-md p-6">
      <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2 mb-6">
        <i class="fa-solid fa-envelope text-blue-600"></i> Kirim Email
      </h2>

      <?php if ($info): ?>
        <div class="mb-6 px-4 py-3 rounded-lg border
        <?= strpos($info, 'berhasil') !== false
          ? 'bg-green-100 text-green-700 border-green-300'
          : 'bg-red-100 text-red-700 border-red-300' ?>">
          <?= h($info) ?>
        </div>
      <?php endif; ?>

      <form method="post" class="space-y-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Perusahaan</label>
          <select name="company_email" class="border rounded p-2 w-full" required onchange="this.form.submit()">
            <option value="">-- pilih perusahaan --</option>
            <?php foreach ($companies as $c): ?>
              <?php $optEmail = normalize_email_str($c['email'] ?? ''); ?>
              <?php if ($optEmail): // hanya render kalau email valid 
              ?>
                <option value="<?= h($optEmail) ?>" <?= ($selectedEmail === $optEmail) ? 'selected' : '' ?>>
                  <?= h($c['nama_perusahaan']) ?> (<?= h($optEmail) ?>)
                </option>
              <?php endif; ?>
            <?php endforeach; ?>

          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Bahasa</label>
          <select name="language" class="border rounded p-2 w-full" onchange="this.form.submit()">
            <option value="id" <?= ($language_selected === 'id') ? 'selected' : '' ?>>Bahasa Indonesia</option>
            <option value="en" <?= ($language_selected === 'en') ? 'selected' : '' ?>>English</option>
          </select>
        </div>

        <?php if ($selectedEmail): ?>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
            <input type="text" name="subject" value="<?= h($default_subject) ?>"
              class="w-full p-3 rounded border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500" required />
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Isi Pesan</label>
              <div class="w-full p-3 rounded border bg-gray-50 text-sm prose max-w-none 
      cursor-not-allowed select-text overflow-y-auto"
                style="pointer-events: auto; max-height: 300px;">
                <?= $default_body ?>
              </div>
              <input type="hidden" name="body" value="<?= h($default_body) ?>">
            </div>

            <style>
              /* Matikan interaksi kecuali link */
              div[readonly] {
                pointer-events: none;
              }

              div[readonly] a {
                pointer-events: auto;
                color: #2563eb;
                /* biru */
                text-decoration: underline;
              }
            </style>
          </div>
          <button type="submit" name="send_email"
            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow transition">
            <i class="fa-solid fa-paper-plane"></i> Kirim Email
          </button>
          <a href="dashboard.php"
            class="px-6 py-2 rounded-xl bg-gray-200 text-gray-700 hover:bg-gray-300 font-semibold transition inline-block">
            Cancel
          </a>
    </div>
  <?php endif; ?>


  </form>
  </div>
  </main>
</body>

</html>