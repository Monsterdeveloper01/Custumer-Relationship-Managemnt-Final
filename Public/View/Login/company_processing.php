<?php
session_start();
require '../../Model/db.php';

// Cek auth
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Parameter dari DataTables
$draw = $_POST['draw'] ?? 1;
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$searchValue = $_POST['search']['value'] ?? '';
$orderColumn = $_POST['order'][0]['column'] ?? 0;
$orderDir = $_POST['order'][0]['dir'] ?? 'asc';

// Mapping kolom
$columns = [
    0 => 'nama_perusahaan',
    1 => 'website',
    2 => 'kategori_perusahaan', 
    3 => 'tipe',
    4 => 'kota'
];

// Query dasar
$baseQuery = " FROM crm_contacts_staging WHERE 1=1";
$countQuery = "SELECT COUNT(*) " . $baseQuery;

// Search filter
if (!empty($searchValue)) {
    $searchQuery = " AND (nama_perusahaan LIKE :search OR website LIKE :search OR kategori_perusahaan LIKE :search OR tipe LIKE :search OR kota LIKE :search)";
    $baseQuery .= $searchQuery;
    $countQuery .= $searchQuery;
}

// Get total records
$stmt = $pdo->prepare($countQuery);
if (!empty($searchValue)) {
    $stmt->bindValue(':search', "%$searchValue%");
}
$stmt->execute();
$totalRecords = $stmt->fetchColumn();
$filteredRecords = $totalRecords;

// Order by
$orderBy = "";
if (isset($columns[$orderColumn])) {
    $orderBy = " ORDER BY " . $columns[$orderColumn] . " " . ($orderDir === 'asc' ? 'ASC' : 'DESC');
} else {
    $orderBy = " ORDER BY nama_perusahaan ASC";
}

// Main query dengan pagination
$dataQuery = "SELECT nama_perusahaan, website, kategori_perusahaan, tipe, kota " . $baseQuery . $orderBy . " LIMIT :start, :length";
$stmt = $pdo->prepare($dataQuery);

if (!empty($searchValue)) {
    $stmt->bindValue(':search', "%$searchValue%");
}

$stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
$stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format response
$response = [
    "draw" => intval($draw),
    "recordsTotal" => intval($totalRecords),
    "recordsFiltered" => intval($filteredRecords),
    "data" => []
];

foreach ($data as $row) {
    $response['data'][] = [
        'nama_perusahaan' => $row['nama_perusahaan'] ?? 'No Company Name',
        'website' => $row['website'] ?? null,
        'kategori_perusahaan' => $row['kategori_perusahaan'] ?? '-',
        'tipe' => $row['tipe'] ?? '-',
        'kota' => $row['kota'] ?? '-'
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>