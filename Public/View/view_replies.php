<?php
require_once __DIR__ . '/../Model/db.php';
require_once __DIR__ . '/../Controller/functions.php';

require_login();
$mid = current_marketing_id();

// Ambil data balasan dari database
$stmt = $pdo->prepare("SELECT * FROM email_replies WHERE marketing_id = :mid ORDER BY id DESC");
$stmt->execute(['mid' => $mid]);
$replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
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
<body class="bg-gray-100 min-h-screen">

      <!-- Header -->
  <?php include("Partials/Header.html"); ?>

  <div class="max-w-6xl mx-auto py-10 px-4">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">📩 Daftar Balasan Email</h1>

    <?php if (empty($replies)): ?>
      <div class="bg-yellow-100 border border-yellow-300 text-yellow-700 p-4 rounded-lg">
        Belum ada balasan email yang masuk.
      </div>
    <?php else: ?>
      <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full table-auto border-collapse">
          <thead class="bg-gray-200 text-gray-700">
            <tr>
              <th class="px-4 py-2 text-left">Dari</th>
              <th class="px-4 py-2 text-left">Subjek</th>
              <th class="px-4 py-2 text-left">Isi</th>
              <th class="px-4 py-2 text-left">Waktu</th>
              <th class="px-4 py-2 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($replies as $reply): ?>
              <tr class="border-t hover:bg-gray-50">
                <td class="px-4 py-2 text-sm text-gray-800"><?= htmlspecialchars($reply['from_email']) ?></td>
                <td class="px-4 py-2 text-sm text-gray-800"><?= htmlspecialchars($reply['subject']) ?></td>
                <td class="px-4 py-2 text-sm text-gray-600 truncate max-w-xs">
                  <?= nl2br(htmlspecialchars(substr(strip_tags($reply['body']), 0, 120))) ?>...
                </td>
                <td class="px-4 py-2 text-sm text-gray-500"><?= $reply['created_at'] ?? '-' ?></td>
                <td class="px-4 py-2 text-center">
                  <button 
                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm"
                    onclick="openModal(
                      '<?= htmlspecialchars(addslashes($reply['from_email'])) ?>',
                      '<?= htmlspecialchars(addslashes($reply['subject'])) ?>',
                      `<?= htmlspecialchars(addslashes($reply['body'])) ?>`,
                      '<?= $reply['created_at'] ?? '-' ?>'
                    )">
                    Detail
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Modal -->
  <div id="emailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-6 relative">
      <button onclick="closeModal()" class="absolute top-2 right-2 text-gray-500 hover:text-black">✖</button>
      <h2 class="text-xl font-bold mb-4">📧 Detail Balasan Email</h2>
      <p><span class="font-semibold">Dari:</span> <span id="modalFrom"></span></p>
      <p><span class="font-semibold">Subjek:</span> <span id="modalSubject"></span></p>
      <p><span class="font-semibold">Waktu:</span> <span id="modalDate"></span></p>
      <div class="mt-4 p-3 border rounded bg-gray-50 max-h-96 overflow-y-auto">
        <div id="modalBody" class="text-gray-700 whitespace-pre-wrap"></div>
      </div>
    </div>
  </div>

  <script>
    function openModal(from, subject, body, date) {
      document.getElementById('modalFrom').innerText = from;
      document.getElementById('modalSubject').innerText = subject;
      document.getElementById('modalDate').innerText = date;
      document.getElementById('modalBody').innerHTML = body.replace(/\n/g, "<br>");
      document.getElementById('emailModal').classList.remove('hidden');
      document.getElementById('emailModal').classList.add('flex');
    }

    function closeModal() {
      document.getElementById('emailModal').classList.remove('flex');
      document.getElementById('emailModal').classList.add('hidden');
    }
  </script>
</body>
</html>
