<?php
require_once '/../Model/db.php';
require_once '/../Controller/functions.php';
session_start(); // pastikan session aktif

// Pastikan user login
if (!isset($_SESSION['user']['marketing_id'])) {
    die("Unauthorized: Anda harus login sebagai marketing.");
}

$marketingId = $_SESSION['user']['marketing_id'];

// --- Pagination setup ---
$limit = 5;
$page  = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Hitung total data khusus marketing ini
$stmtTotal = $pdo->prepare("SELECT COUNT(*) AS total FROM crm WHERE marketing_id = :marketing_id");
$stmtTotal->execute(['marketing_id' => $marketingId]);
$total = (int)$stmtTotal->fetchColumn();
$pages = $total > 0 ? ceil($total / $limit) : 1;

// Ambil data sesuai marketing_id + pagination
$sql = "SELECT * FROM crm 
        WHERE marketing_id = :marketing_id 
        ORDER BY company_name ASC
        LIMIT :start, :limit";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':marketing_id', $marketingId, PDO::PARAM_STR);
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Mode edit ---
$editData = null;
if (isset($_GET['email'])) {
    $stmtEdit = $pdo->prepare("SELECT * FROM crm 
                               WHERE company_email = :email 
                               AND marketing_id = :marketing_id");
    $stmtEdit->execute([
        'email'        => $_GET['email'],
        'marketing_id' => $marketingId
    ]);
    $editData = $stmtEdit->fetch(PDO::FETCH_ASSOC);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Contact List</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<script>
    function confirmDelete(email) {
        Swal.fire({
            title: 'Yakin hapus kontak ini?',
            text: "Data akan terhapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "delete_contact.php?email=" + encodeURIComponent(email);
            }
        });
    }
</script>

<body class="bg-gray-50 min-h-screen" x-data="{ sidebarOpen: false }">
    <!-- Header -->
    <?php include("partials/Header.html"); ?>

    <!-- Sidebar -->
    <?php include("partials/sidebar.html"); ?>

    <!-- Konten utama -->
    <div class="pt-32 container mx-auto p-6">
        <!-- form & table kamu tetap di sini -->

        <!-- Input / Edit Form -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-xl font-bold mb-4">
                <?= $editData ? "Edit Contact" : "Add New Contact" ?>
            </h2>

            <form action="<?= $editData ? 'update_contact.php' : 'save_contact.php' ?>" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php if ($editData): ?>
                    <input type="hidden" name="old_company_email" value="<?= h($editData['company_email']); ?>">
                <?php endif; ?>

                <!-- Kolom Kiri -->
                <div class="space-y-4">
                    <div>
                        <label class="block mb-1 font-semibold">Company Name</label>
                        <input type="text" name="company_name"
                            value="<?= $editData ? h($editData['company_name']) : '' ?>"
                            placeholder="Pt. Company Name"
                            class="border rounded-lg p-2 w-full" required>
                    </div>

                    <div>
                        <label class="block mb-1 font-semibold">Company Email</label>
                        <input type="email" name="company_email"
                            value="<?= $editData ? h($editData['company_email']) : '' ?>"
                            placeholder="company@gmail.com"
                            class="border rounded-lg p-2 w-full" required>
                    </div>

                    <div>
                        <label class="block mb-1 font-semibold">NamePerson</label>
                        <input type="text" name="name_person"
                            value="<?= $editData ? h($editData['name_person']) : '' ?>"
                            placeholder="Budi Santoso"
                            class="border rounded-lg p-2 w-full" required>
                    </div>

                    <div>
                        <label class="block mb-1 font-semibold">Person Email</label>
                        <input type="email" name="person_email"
                            value="<?= $editData ? h($editData['person_email']) : '' ?>"
                            placeholder="Person@gmail.com"
                            class="border rounded-lg p-2 w-full">
                    </div>

                    <div>
                        <label class="block mb-1 font-semibold">Phone Number</label>
                        <input type="text" name="phone_number"
                            value="<?= $editData ? h($editData['phone_number']) : '' ?>"
                            placeholder="081xxxxxxxx / +6281xxxxxxxx / 021xxxxxxx"
                            class="border rounded-lg p-2 w-full">
                    </div>

                    <div>
                        <label class="block mb-1 font-semibold">Phone Number 2 (Opsional)</label>
                        <input type="text" name="phone_number2"
                            value="<?= $editData ? h($editData['phone_number2']) : '' ?>"
                            placeholder="081xxxxxxxx / +6281xxxxxxxx / 021xxxxxxx"
                            class="border rounded-lg p-2 w-full">
                    </div>

                    <div>
                        <label class="block mb-1 font-semibold">Position Title</label>
                        <input type="text" name="contact_person_position_title"
                            value="<?= $editData ? h($editData['contact_person_position_title']) : '' ?>"
                            placeholder="Director / Manager / Staff"
                            class="border rounded-lg p-2 w-full">
                    </div>

                    <div>
                        <label class="block mb-1 font-semibold">Position Category</label>
                        <input type="text" name="contact_person_position_category"
                            value="<?= $editData ? h($editData['contact_person_position_category']) : '' ?>"
                            placeholder="IT / Finance / Marketing"
                            class="border rounded-lg p-2 w-full">
                    </div>
                </div>

                <!-- Kolom Kanan -->
                <div class="space-y-4">
                    <div>
                        <label class="block mb-1 font-semibold">Company Website</label>
                        <input type="text" name="company_website"
                            value="<?= $editData ? h($editData['company_website']) : '' ?>"
                            placeholder="www.company.com"
                            class="border rounded-lg p-2 w-full">
                    </div>

                    <div>
                        <label class="block mb-1 font-semibold">Company Category</label>
                        <input type="text" name="company_category"
                            value="<?= $editData ? h($editData['company_category']) : '' ?>"
                            placeholder="finance / tech / health"
                            class="border rounded-lg p-2 w-full">
                    </div>

                    <div>
                        <label class="block mb-1 font-semibold">Company Type</label>
                        <input type="text" name="company_type"
                            value="<?= $editData ? h($editData['company_type']) : '' ?>"
                            placeholder="Swasta / BUMN / BUMD"
                            class="border rounded-lg p-2 w-full">
                    </div>

                    <div>
                        <label class="block mb-1 font-semibold">Address</label>
                        <textarea name="address" placeholder="Jl. Example No.123, Jakarta"
                            class="border rounded-lg p-2 w-full h-24"><?= $editData ? h($editData['address']) : '' ?></textarea>
                    </div>

                    <div>
                        <label class="block mb-1 font-semibold">City</label>
                        <input type="text" name="city"
                            value="<?= $editData ? h($editData['city']) : '' ?>"
                            placeholder="Jakarta Selatan"
                            class="border rounded-lg p-2 w-full">
                    </div>

                    <div>
                        <label class="block mb-1 font-semibold">Postcode</label>
                        <input type="text" name="postcode"
                            value="<?= $editData ? h($editData['postcode']) : '' ?>"
                            placeholder="10430"
                            class="border rounded-lg p-2 w-full">
                    </div>

                    <div>
                        <label class="block mb-1 font-semibold">Status</label>
                        <select name="status" class="border rounded-lg p-2 w-full" required>
                            <?php
                            $statuses = ["-- Pilih Status --", "input", "wa", "emailed", "contacted", "replied", "presentation", "client"];
                            foreach ($statuses as $s):
                                $selected = ($editData && $editData['status'] == $s) ? "selected" : "";
                                echo "<option value='$s' $selected>" . ucfirst($s) . "</option>";
                            endforeach;
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Tombol -->
                <div class="col-span-1 md:col-span-2 flex gap-3 mt-4">
                    <button type="submit"
                        class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700">
                        <?= $editData ? "Update Contact" : "Add Contact" ?>
                    </button>

                    <?php if ($editData): ?>
                        <a href="contact_list.php"
                            class="bg-gray-400 text-white py-2 px-4 rounded-lg hover:bg-gray-500">
                            Cancel
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>


        <!-- Table -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-lg font-semibold mb-4">All Contacts</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-200 text-sm">
                    <thead>
                        <tr class="bg-gradient-to-r from-blue-500 to-blue-600 text-white text-xs uppercase">
                            <th class="py-3 px-4 border w-10">No</th>
                            <th class="px-4 py-2 min-w-[250px]">Company</th>
                            <th class="px-4 py-2 min-w-[250px]">Company Email</th>
                            <th class="px-4 py-2 min-w-[150px]">Name Person</th>
                            <th class="px-4 py-2 min-w-[250px]">Person Email</th>
                            <th class="py-3 px-4 min-w-[150px]">Number Phone</th>
                            <th class="py-3 px-4 min-w-[150px]">Number Phone 2</th>
                            <th class="py-3 px-4 border w-40">Position Title</th>
                            <th class="py-3 px-4 border w-40">Position Category</th>
                            <th class="px-4 py-2 min-w-[200px]">Company Website</th>
                            <th class="py-3 px-4 border w-40">Company Category</th>
                            <th class="py-3 px-4 border w-32">Company Type</th>
                            <th class="px-4 py-2 min-w-[300px]">Address</th>
                            <th class="py-3 px-4 min-w-[150px]">City</th>
                            <th class="py-3 px-4 border w-24">Postcode</th>
                            <th class="py-3 px-4 border w-28">Status</th>
                            <th class="py-3 px-4 min-w-[220px] text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php $no = $start + 1;
                        foreach ($data as $row): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4 border text-center"><?= $no++; ?></td>
                                <td class="py-3 px-4 border font-medium text-gray-800 text-left"><?= h($row['company_name']); ?></td>
                                <td class="py-3 px-4 border text-left break-words"><?= h($row['company_email']); ?></td>
                                <td class="py-3 px-4 border text-left"><?= h($row['name_person']); ?></td>
                                <td class="py-3 px-4 border text-left break-words"><?= h($row['person_email']); ?></td>
                                <td class="py-3 px-4 border text-center"><?= h($row['phone_number']); ?></td>
                                <td class="py-3 px-4 border text-center"><?= h($row['phone_number2']); ?></td>
                                <td class="py-3 px-4 border text-left"><?= h($row['contact_person_position_title']); ?></td>
                                <td class="py-3 px-4 border text-left"><?= h($row['contact_person_position_category']); ?></td>
                                <td class="py-3 px-4 border text-blue-600 underline text-left break-words"><?= h($row['company_website']); ?></td>
                                <td class="py-3 px-4 border text-left"><?= h($row['company_category']); ?></td>
                                <td class="py-3 px-4 border text-left"><?= h($row['company_type']); ?></td>
                                <td class="py-3 px-4 border text-left break-words"><?= h($row['address']); ?></td>
                                <td class="py-3 px-4 border text-center"><?= h($row['city']); ?></td>
                                <td class="py-3 px-4 border text-center"><?= h($row['postcode']); ?></td>
                                <td class="py-3 px-4 border text-center">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                                <?= $row['status'] === 'client' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                        <?= ucfirst(h($row['status'])); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 border text-center space-x-2">
                                    <a href="contact_list.php?email=<?= urlencode($row['company_email']); ?>"
                                        class="inline-block bg-blue-500 text-white px-3 py-1 rounded-lg text-xs hover:bg-blue-600">
                                        Edit
                                    </a>
                                    <button onclick="confirmDelete('<?= $row['company_email']; ?>')"
                                        class="inline-block bg-red-500 text-white px-3 py-1 rounded-lg text-xs hover:bg-red-600">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-between items-center mt-4">
                <p class="text-sm text-gray-600">
                    Showing <?= $start + 1; ?> to <?= min($start + $limit, $total); ?> of <?= $total; ?> contacts
                </p>
                <div class="flex space-x-2">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <a href="?page=<?= $i; ?>"
                            class="px-3 py-1 border rounded-lg text-sm 
                   <?= ($i == $page) ? 'bg-blue-600 text-white border-blue-600' : 'hover:bg-gray-100 text-gray-700 border-gray-300' ?>">
                            <?= $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

    </div>
    <?php if (isset($_SESSION['toast'])): ?>
        <script>
            Swal.fire({
                icon: '<?= $_SESSION['toast']['type'] ?>',
                title: '<?= $_SESSION['toast']['type'] === "success" ? "Berhasil" : "Gagal" ?>',
                html: '<?= $_SESSION['toast']['message'] ?>',
                timer: 3000,
                showConfirmButton: false
            });
        </script>
    <?php unset($_SESSION['toast']);
    endif; ?>

</body>

</html>