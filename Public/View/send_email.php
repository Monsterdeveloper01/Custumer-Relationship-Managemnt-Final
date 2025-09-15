<?php
require_once __DIR__ . '/../backend_secure/Model/db.php';
require_once __DIR__ . '/../backend_secure/functions.php'; // fungsi2 (require_login, current_marketing_id, h)

require_login(); // wajib login

// PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';

$mid             = current_marketing_id();
$marketing_name  = $_SESSION['user']['name'];
$marketing_email = $_SESSION['user']['email'];

// ==============================
// Ambil semua perusahaan milik marketing ini
// ==============================
$stmt = $pdo->prepare("SELECT company_email, company_name, name_person, phone_number, city, status 
                       FROM crm 
                       WHERE marketing_id = :mid");
$stmt->execute(['mid' => $mid]);
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Default
$selectedCompany = null;
$default_subject = '';
$default_body    = '';
$info = '';

// ==============================
// Jika user memilih perusahaan
// ==============================
if (isset($_POST['company_email']) && !isset($_POST['send_email'])) {
  $cid = $_POST['company_email'];
  $stmt = $pdo->prepare("SELECT * FROM crm WHERE company_email = :cid AND marketing_id = :mid");
  $stmt->execute(['cid' => $cid, 'mid' => $mid]);
  $selectedCompany = $stmt->fetch();

  if ($selectedCompany) {
    $default_subject = "Kesempatan Kerja Sama antara PT Rayterton Indonesia & {$selectedCompany['company_name']}";
    $default_body    = "Halo " . ($selectedCompany['name_person'] ?: $selectedCompany['company_name']) . ",\n\n"
      . "Perkenalkan, saya {$marketing_name} dari PT Rayterton Indonesia. "
      . "Saya menghubungi Bapak/Ibu karena melihat ada potensi kerja sama yang baik "
      . "antara PT Rayterton Indonesia dan {$selectedCompany['company_name']}.\n\n"
      . "Kami yakin, dengan pengalaman serta layanan yang kami miliki, "
      . "kolaborasi ini bisa membawa manfaat bagi kedua belah pihak.\n\n"
      . "Apakah Bapak/Ibu berkenan meluangkan waktu untuk diskusi singkat minggu ini? "
      . "Saya bisa menyesuaikan jadwal sesuai waktu yang nyaman bagi Bapak/Ibu.\n\n"
      . "Terima kasih atas perhatian Bapak/Ibu.\n"
      . "Saya berharap kita bisa segera berdiskusi lebih lanjut.\n\n"
      . "Salam hangat,\n"
      . "{$marketing_name}\n"
      . "[Posisi]\n"
      . "[Telepon/WA]\n";
  }
}

// ==============================
// Proses Kirim Email
// ==============================
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
    $to = $data['company_email'];

    $mail = new PHPMailer(true);
    try {
      $mail->isSMTP();
      $mail->Host       = 'mail.rayterton.com';
      $mail->SMTPAuth   = true;
      $mail->Username   = 'marketing@rayterton.com';
      $mail->Password   = 'RTNmainServer'; // ganti password asli
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
      $mail->Port       = 465;

      $mail->setFrom('marketing@rayterton.com', "CRM - {$marketing_name}");
      $mail->addAddress($to);

      // pastikan balasan masuk ke email marketing
      $mail->addReplyTo('marketing@rayterton.com', "CRM - {$marketing_name}");

      $mail->isHTML(false);
      $mail->Subject = $subject;
      $mail->Body    = $body;

      $mail->send();

      // update status
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CRM Dashboard | TechSolutions Inc.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: {
              50: '#eff6ff',
              100: '#dbeafe',
              200: '#bfdbfe',
              300: '#93c5fd',
              400: '#60a5fa',
              500: '#3b82f6',
              600: '#2563eb',
              700: '#1d4ed8',
              800: '#1e40af',
              900: '#1e3a8a',
            },
            dark: {
              50: '#f8fafc',
              100: '#f1f5f9',
              200: '#e2e8f0',
              300: '#cbd5e1',
              400: '#94a3b8',
              500: '#64748b',
              600: '#475569',
              700: '#334155',
              800: '#1e293b',
              900: '#0f172a',
            }
          }
        }
      }
    }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    body {
      font-family: 'Inter', sans-serif;
    }

    .sidebar {
      width: 250px;
      transition: all 0.3s ease;
    }

    .main-content {
      margin-left: 250px;
      transition: all 0.3s ease;
    }

    .collapsed .sidebar {
      width: 70px;
    }

    .collapsed .main-content {
      margin-left: 70px;
    }

    .sidebar-item {
      transition: all 0.2s ease;
    }

    .sidebar-item:hover {
      background-color: rgba(59, 130, 246, 0.1);
    }

    .stat-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.1);
    }

    .progress-bar {
      transition: width 1s ease-in-out;
    }
  </style>
</head>

<body>
  <!-- Header -->
  <?php include("Partials/Header.html"); ?>

  <!-- Bagian Send Email -->
  <section name="sendmail" class="bg-white shadow-lg rounded-xl p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-6">Send Email</h2>

    <?php if ($info): ?>
      <div class="mb-4 p-4 rounded-lg <?= strpos($info, 'berhasil') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
        <?= h($info) ?>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-5">
      <div>
        <label class="block text-sm font-medium text-gray-700">Pilih Perusahaan</label>
        <select name="company_email" onchange="this.form.submit()"
          class="w-full mt-1 p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 bg-white">
          <option value="">-- pilih perusahaan --</option>
          <?php foreach ($companies as $c): ?>
            <option value="<?= $c['company_email'] ?>" <?= ($selectedCompany && $selectedCompany['company_email'] == $c['company_email']) ? 'selected' : '' ?>>
              <?= $c['company_name'] ?> (<?= $c['company_email'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php if ($selectedCompany): ?>
        <div>
          <label class="block text-sm font-medium text-gray-700">Subject</label>
          <input type="text" name="subject" value="<?= h($default_subject) ?>"
            class="w-full mt-1 p-2 border rounded-lg focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Body</label>
          <textarea name="body" rows="10"
            class="w-full mt-1 p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm"><?= h($default_body) ?></textarea>
        </div>

        <button type="submit" name="send_email"
          class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-semibold shadow">
          Send Email
        </button>
      <?php endif; ?>
    </form>
  </section>

</body>

</html>