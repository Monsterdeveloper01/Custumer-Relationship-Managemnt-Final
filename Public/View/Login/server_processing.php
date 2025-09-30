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
$length = $_POST['length'] ?? 20;
$searchValue = $_POST['search']['value'] ?? '';
$orderColumn = $_POST['order'][0]['column'] ?? 0;
$orderDir = $_POST['order'][0]['dir'] ?? 'asc';

// Mapping kolom
$columns = [
    0 => 'nama_perusahaan',
    1 => 'email', 
    2 => 'no_telp1',
    3 => 'kategori_perusahaan',
    4 => 'ditemukan_oleh',
    5 => 'status'
];

// Query dasar
$baseQuery = " FROM crm_contacts_staging WHERE 1=1";
$countQuery = "SELECT COUNT(*) " . $baseQuery;

// Search filter
if (!empty($searchValue)) {
    $searchQuery = " AND (nama_perusahaan LIKE :search OR email LIKE :search OR no_telp1 LIKE :search OR kategori_perusahaan LIKE :search OR ditemukan_oleh LIKE :search OR status LIKE :search)";
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

// Get filtered records count
$filteredRecords = $totalRecords;

// Order by - Default ordering by status
$orderBy = " ORDER BY FIELD(status,
    'input',
    'emailed', 
    'contacted',
    'presentation',
    'NDA process',
    'Gap analysis / requirement analysis',
    'SIT (System Integration Testing)',
    'UAT (User Acceptance Testing)',
    'Proposal', 
    'Negotiation',
    'Deal / Closed',
    'Failed / Tidak Lanjut',
    'Postpone'
) ASC";

// Jika ada sorting dari DataTables
if (isset($columns[$orderColumn])) {
    $orderBy = " ORDER BY " . $columns[$orderColumn] . " " . ($orderDir === 'asc' ? 'ASC' : 'DESC');
}

// Main query dengan pagination
$dataQuery = "SELECT * " . $baseQuery . $orderBy . " LIMIT :start, :length";
$stmt = $pdo->prepare($dataQuery);

if (!empty($searchValue)) {
    $stmt->bindValue(':search', "%$searchValue%");
}

$stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
$stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

function normalize_email($val) {
    $val = trim($val ?? '');
    if (stripos($val, 'mailto:') === 0) {
        $val = substr($val, 7);
    }
    if (($qpos = strpos($val, '?')) !== false) {
        $val = substr($val, 0, $qpos);
    }
    return $val;
}

// Format response untuk DataTables
$response = [
    "draw" => intval($draw),
    "recordsTotal" => intval($totalRecords),
    "recordsFiltered" => intval($filteredRecords),
    "data" => []
];

foreach ($data as $row) {
    $emailClean = normalize_email($row['email'] ?? '');
    
    $response['data'][] = [
        'DT_RowIndex' => '',
        'nama_perusahaan' => $row['nama_perusahaan'] ?? null,
        'email' => $emailClean ?: null,
        'no_telp1' => $row['no_telp1'] ?? null,
        'kategori_perusahaan' => $row['kategori_perusahaan'] ?? null,
        'ditemukan_oleh' => $row['ditemukan_oleh'] ?? null,
        'status' => $row['status'] ?? null,
        'actions' => [
            'email' => $emailClean,
            'status' => $row['status'] ?? '',
            'raw_data' => $row
        ]
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>