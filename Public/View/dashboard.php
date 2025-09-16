<?php
require_once __DIR__ . '/../Model/db.php';
require_once __DIR__ . '/../Controller/functions.php';

// Pastikan user sudah login
require_login();

// Ambil ID marketing dari session
$currentUserId = current_marketing_id();
if (!$currentUserId) {
  die("User not logged in.");
}

// Query data CRM
$sql  = "SELECT * FROM crm WHERE marketing_id = :mid";
$stmt = $pdo->prepare($sql);
$stmt->execute(['mid' => $currentUserId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung jumlah status
$totalCompanies    = count($rows);
$emailCount        = count(array_filter($rows, fn($r) => normStatus($r['status']) === 'emailed'));
$contactedCount    = count(array_filter($rows, fn($r) => normStatus($r['status']) === 'contacted'));
$waCount           = count(array_filter($rows, fn($r) => normStatus($r['status']) === 'wa'));
$repliedCount      = count(array_filter($rows, fn($r) => normStatus($r['status']) === 'replied'));
$presentationCount = count(array_filter($rows, fn($r) => normStatus($r['status']) === 'presentation'));
$inputCount        = count(array_filter($rows, fn($r) => normStatus($r['status']) === 'input'));
$clientCount       = count(array_filter($rows, fn($r) => normStatus($r['status']) === 'client'));

$knownStatuses = ['emailed', 'contacted', 'wa', 'replied', 'presentation', 'input', 'client'];
$otherCount    = count(array_filter($rows, fn($r) => !in_array(normStatus($r['status']), $knownStatuses)));

// Pagination
$limit = 10;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$totalPages = ceil($totalCompanies / $limit);
$rowsPaginated = array_slice($rows, $offset, $limit);

$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT * FROM crm WHERE 1";
$params = [];

// filter status jika ada
if ($statusFilter !== '') {
  $sql .= " AND status = ?";
  $params[] = $statusFilter;
}

// Pagination
$sql .= " LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rowsPaginated = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
      margin-left: 0px;
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

<body class="bg-gray-50 flex" x-data="{ sidebarOpen: false, isCollapsed: false }">
  <!-- Header -->
  <?php include("Partials/Header.html"); ?>

  <!-- Main Content -->
  <div :class="isCollapsed ? 'collapsed' : ''" class="main-content w-full min-h-screen flex flex-col">
    <br><br><br><br>
    <!-- Header -->
    <main class="flex-1 p-4 sm:p-6 lg:p-8 bg-gray-50">
      <!-- Stats Overview -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card bg-white rounded-xl shadow-sm p-4 border-l-4 border-primary-500">
          <div class="flex items-center">
            <div class="p-3 rounded-lg bg-primary-50 text-primary-500">
              <i class="fas fa-building text-lg"></i>
            </div>
            <div class="ml-4">
              <h3 class="text-sm font-medium text-gray-500">Total Companies</h3>
              <p class="text-2xl font-bold text-gray-900"><?= $totalCompanies ?></p>
            </div>
          </div>
        </div>

        <div class="stat-card bg-white rounded-xl shadow-sm p-4 border-l-4 border-green-500">
          <div class="flex items-center">
            <div class="p-3 rounded-lg bg-green-50 text-green-500">
              <i class="fas fa-handshake text-lg"></i>
            </div>
            <div class="ml-4">
              <h3 class="text-sm font-medium text-gray-500">Clients</h3>
              <p class="text-2xl font-bold text-gray-900"><?= $clientCount ?></p>
            </div>
          </div>
        </div>

        <div class="stat-card bg-white rounded-xl shadow-sm p-4 border-l-4 border-blue-500">
          <div class="flex items-center">
            <div class="p-3 rounded-lg bg-blue-50 text-blue-500">
              <i class="fas fa-reply text-lg"></i>
            </div>
            <div class="ml-4">
              <h3 class="text-sm font-medium text-gray-500">Replied</h3>
              <p class="text-2xl font-bold text-gray-900"><?= $repliedCount ?></p>
            </div>
          </div>
        </div>

        <div class="stat-card bg-white rounded-xl shadow-sm p-4 border-l-4 border-purple-500">
          <div class="flex items-center">
            <div class="p-3 rounded-lg bg-purple-50 text-purple-500">
              <i class="fas fa-chart-line text-lg"></i>
            </div>
            <div class="ml-4">
              <h3 class="text-sm font-medium text-gray-500">Conversion Rate</h3>
              <p class="text-2xl font-bold text-gray-900"><?= $totalCompanies > 0 ? round(($clientCount / $totalCompanies) * 100, 1) : 0 ?>%</p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Performance Chart -->
        <div class="bg-white shadow-lg rounded-xl p-6 col-span-1">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Performance Overview</h3>
            <button class="text-gray-400 hover:text-gray-600">
              <i class="fas fa-ellipsis-v"></i>
            </button>
          </div>
          <canvas id="performanceChart" class="w-full h-64"></canvas>
        </div>

        <!-- Status Distribution -->
        <div class="bg-white shadow-lg rounded-xl p-6 col-span-1">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Status Distribution</h3>
            <button class="text-gray-400 hover:text-gray-600">
              <i class="fas fa-ellipsis-v"></i>
            </button>
          </div>
          <div class="space-y-4">
            <?php
            $statusData = [
              ['label' => 'Input', 'count' => $inputCount, 'color' => 'bg-purple-500'],
              ['label' => 'WA', 'count' => $waCount, 'color' => 'bg-green-500'],
              ['label' => 'Emailed', 'count' => $emailCount, 'color' => 'bg-blue-500'],
              ['label' => 'Contacted', 'count' => $contactedCount, 'color' => 'bg-yellow-500'],
              ['label' => 'Replied', 'count' => $repliedCount, 'color' => 'bg-pink-500'],
              ['label' => 'Presentation', 'count' => $presentationCount, 'color' => 'bg-orange-500'],
              ['label' => 'Client', 'count' => $clientCount, 'color' => 'bg-red-500'],
              ['label' => 'Others', 'count' => $otherCount, 'color' => 'bg-gray-500']
            ];

            foreach ($statusData as $status):
              if ($totalCompanies > 0):
                $percentage = round(($status['count'] / $totalCompanies) * 100, 1);
              else:
                $percentage = 0;
              endif;
            ?>
              <div>
                <div class="flex justify-between text-sm mb-1">
                  <span class="text-gray-600"><?= $status['label'] ?></span>
                  <span class="font-medium"><?= $status['count'] ?> (<?= $percentage ?>%)</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="progress-bar h-2 rounded-full <?= $status['color'] ?>" style="width: <?= $percentage ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white shadow-lg rounded-xl p-6 col-span-1">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Quick Actions</h3>
            <button class="text-gray-400 hover:text-gray-600">
              <i class="fas fa-ellipsis-v"></i>
            </button>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <a href="contact_list.php" class="flex flex-col items-center justify-center p-4 bg-primary-50 rounded-lg hover:bg-primary-100 transition">
              <div class="p-3 bg-primary-100 text-primary-600 rounded-full mb-2">
                <i class="fas fa-plus"></i>
              </div>
              <span class="text-sm font-medium text-gray-700">Add Company</span>
            </a>
            <a href="send_email.php" class="flex flex-col items-center justify-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
              <div class="p-3 bg-green-100 text-green-600 rounded-full mb-2">
                <i class="fas fa-envelope"></i>
              </div>
              <span class="text-sm font-medium text-gray-700">Send Email</span>
            </a>
            <a href="" class="flex flex-col items-center justify-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
              <div class="p-3 bg-blue-100 text-blue-600 rounded-full mb-2">
                <i class="fas fa-file-export"></i>
              </div>
              <span class="text-sm font-medium text-gray-700">Export Data</span>
            </a>
            <a href="fetch_replies.php" class="flex flex-col items-center justify-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
              <div class="p-3 bg-purple-100 text-purple-600 rounded-full mb-2">
                <i class="fas fa-chart-pie"></i>
              </div>
              <span class="text-sm font-medium text-gray-700">View Replies</span>
            </a>
          </div>
        </div>
      </div>

      <!-- Table Section -->
      <section class="bg-white shadow-lg rounded-xl p-6 mt-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-semibold text-gray-800">All Company Records</h3>
          <div class="flex space-x-2">
            <!-- Dropdown Filter -->
            <form method="GET" class="flex">
              <select name="status" onchange="this.form.submit()"
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 focus:ring focus:ring-primary-200">
                <option value="">All Status</option>
                <option value="emailed" <?= ($_GET['status'] ?? '') === 'emailed' ? 'selected' : '' ?>>Emailed</option>
                <option value="contacted" <?= ($_GET['status'] ?? '') === 'contacted' ? 'selected' : '' ?>>Contacted</option>
                <option value="wa" <?= ($_GET['status'] ?? '') === 'wa' ? 'selected' : '' ?>>WA</option>
                <option value="replied" <?= ($_GET['status'] ?? '') === 'replied' ? 'selected' : '' ?>>Replied</option>
                <option value="presentation" <?= ($_GET['status'] ?? '') === 'presentation' ? 'selected' : '' ?>>Presentation</option>
                <option value="client" <?= ($_GET['status'] ?? '') === 'client' ? 'selected' : '' ?>>Client</option>
                <option value="input" <?= ($_GET['status'] ?? '') === 'input' ? 'selected' : '' ?>>Input</option>
              </select>
            </form>

            <!-- Tombol Sort tetap -->
            <button class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
              <i class="fas fa-sort mr-1"></i> Sort
            </button>
          </div>

        </div>

        <div class="overflow-x-auto border border-gray-200 rounded-lg">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase font-medium">
              <tr>
                <thead class="bg-gray-50 text-gray-600 uppercase font-medium">
                  <tr>
                    <th class="px-4 py-3 text-left">Company</th>
                    <th class="px-4 py-3 text-left">Contact Person</th>
                    <th class="px-4 py-3 text-left">Email</th>
                    <th class="px-4 py-3 text-left">Phone</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Marketing ID</th> <!-- Tambahan -->
                  </tr>
                </thead>

                <!-- <th class="px-4 py-3 text-right">Actions</th> -->
              </tr>
            </thead>
            <tbody class="text-gray-700 divide-y divide-gray-200">
              <?php if (!empty($rowsPaginated)): ?>
                <?php foreach ($rowsPaginated as $i => $row): ?>
                  <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3">
                      <div class="font-medium text-gray-900"><?= htmlspecialchars($row['company_name']) ?></div>
                    </td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['name_person']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['company_email']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($row['phone_number']) ?></td>
                    <td class="px-4 py-3">
                      <?php
                      $status = normStatus($row['status']);
                      $badgeClass = match ($status) {
                        'emailed'      => 'bg-blue-100 text-blue-800',
                        'contacted'    => 'bg-green-100 text-green-800',
                        'wa'           => 'bg-teal-100 text-teal-800',
                        'replied'      => 'bg-pink-100 text-pink-800',
                        'presentation' => 'bg-orange-100 text-orange-800',
                        'client'       => 'bg-red-100 text-red-800',
                        'input'        => 'bg-purple-100 text-purple-800',
                        default        => 'bg-gray-100 text-gray-800',
                      };
                      ?>
                      <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $badgeClass ?>">
                        <?= ucfirst($status) ?>
                      </span>
                    </td>

                    <!-- Marketing ID Badge -->
                    <td class="px-4 py-3">
                      <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-700">
                        <?= htmlspecialchars($row['marketing_id']) ?>
                      </span>
                    </td>


                    <!-- <td class="px-4 py-3">
                      <div class="flex justify-end space-x-2">
                        <button class="p-1 text-gray-400 hover:text-blue-600">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="p-1 text-gray-400 hover:text-green-600">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button class="p-1 text-gray-400 hover:text-red-600">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td> -->
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="px-4 py-6 text-center text-gray-400">
                    <i class="fas fa-inbox text-3xl mb-2"></i>
                    <p>No companies found. Add your first company to get started.</p>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between mt-6">
          <p class="text-sm text-gray-600">
            Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $totalCompanies) ?> of <?= $totalCompanies ?> entries
          </p>
          <div class="flex space-x-2">
            <a href="?page=1" class="px-3 py-1.5 rounded border text-sm font-medium <?= $page == 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-100' ?>">
              <i class="fas fa-angle-double-left"></i>
            </a>
            <a href="?page=<?= $page - 1 ?>" class="px-3 py-1.5 rounded border text-sm font-medium <?= $page <= 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-100' ?>">
              <i class="fas fa-angle-left"></i>
            </a>

            <?php
            // Show page numbers with ellipsis for many pages
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $startPage + 4);

            if ($endPage - $startPage < 4) {
              $startPage = max(1, $endPage - 4);
            }

            for ($i = $startPage; $i <= $endPage; $i++):
            ?>
              <a href="?page=<?= $i ?>" class="px-3 py-1.5 rounded border text-sm font-medium <?= $i == $page ? 'bg-primary-600 text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                <?= $i ?>
              </a>
            <?php endfor; ?>

            <a href="?page=<?= $page + 1 ?>" class="px-3 py-1.5 rounded border text-sm font-medium <?= $page >= $totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-100' ?>">
              <i class="fas fa-angle-right"></i>
            </a>
            <a href="?page=<?= $totalPages ?>" class="px-3 py-1.5 rounded border text-sm font-medium <?= $page >= $totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-100' ?>">
              <i class="fas fa-angle-double-right"></i>
            </a>
          </div>
        </div>
      </section>
  </div>
  </main>
  </div>

  <script>
    const ctx = document.getElementById('performanceChart').getContext('2d');
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Input', 'WA', 'Emailed', 'Contacted', 'Replied', 'Presentation', 'Client', 'Others'],
        datasets: [{
          data: [
            <?= $inputCount ?>,
            <?= $waCount ?>,
            <?= $emailCount ?>,
            <?= $contactedCount ?>,
            <?= $repliedCount ?>,
            <?= $presentationCount ?>,
            <?= $clientCount ?>,
            <?= $otherCount ?>
          ],
          backgroundColor: [
            '#8b5cf6', // Input - purple
            '#10b981', // WA - green
            '#3b82f6', // Emailed - blue
            '#f59e0b', // Contacted - yellow
            '#ec4899', // Replied - pink
            '#f97316', // Presentation - orange
            '#ef4444', // Client - red
            '#6b7280' // Others - gray
          ],
          borderWidth: 0,
          hoverOffset: 10
        }]
      },
      options: {
        responsive: true,
        cutout: '70%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 20,
              usePointStyle: true,
              pointStyle: 'circle'
            }
          }
        }
      }
    });
  </script>
</body>

</html>