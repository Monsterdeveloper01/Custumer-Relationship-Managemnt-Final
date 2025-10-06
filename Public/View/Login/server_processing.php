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
$draw        = $_POST['draw'] ?? 1;
$start       = $_POST['start'] ?? 0;
$length      = $_POST['length'] ?? 20;
$searchValue = $_POST['search']['value'] ?? '';
$orderColumn = $_POST['order'][0]['column'] ?? 0;
$orderDir    = $_POST['order'][0]['dir'] ?? 'asc';
// Ambil flag dari request
$flag = $_POST['flag'] ?? null;

// Validasi flag
if (!in_array($flag, ['CLT', 'MKT'])) {
    // Jika tidak valid, default ke 'calon client' atau kirim error
    $flag = 'CLT';
}

// Mapping kolom sesuai urutan tabel (index 1–6)
$columns = [
    1 => 'nama_perusahaan',
    2 => 'email',
    3 => 'no_telp1',
    4 => 'kategori_perusahaan',
    5 => 'ditemukan_oleh',
    6 => 'status'
];

// ---- Hitung total data SESUAI FLAG (tanpa search/column filter) ----
$totalQuery = "SELECT COUNT(*) FROM crm_contacts_staging WHERE flag = :flag";
$stmtTotal = $pdo->prepare($totalQuery);
$stmtTotal->bindValue(':flag', $flag);
$stmtTotal->execute();
$totalRecords = $stmtTotal->fetchColumn();

// Query dasar dengan filter flag
$baseQuery = " FROM crm_contacts_staging WHERE flag = :flag";
$countQuery = "SELECT COUNT(*) " . $baseQuery;

// Bind flag
$bindings = [':flag' => $flag];


// Global search
if (!empty($searchValue)) {
    $searchQuery = " AND (nama_perusahaan LIKE :search 
        OR email LIKE :search 
        OR no_telp1 LIKE :search 
        OR kategori_perusahaan LIKE :search 
        OR ditemukan_oleh LIKE :search 
        OR status LIKE :search)";
    $baseQuery  .= $searchQuery;
    $countQuery .= $searchQuery;
    $bindings[':search'] = "%$searchValue%";
}

// Column-specific search
$columnSearches = [];
foreach ($_POST['columns'] as $i => $col) {
    if (!isset($columns[$i])) continue; // skip kolom # (0) dan Actions (7)

    $colSearch = $col['search']['value'] ?? '';
    if (!empty($colSearch)) {
        $colName = $columns[$i];
        $paramName = "colsearch$i";
        $columnSearches[] = "$colName LIKE :$paramName";
        $bindings[":$paramName"] = "%$colSearch%";
    }
}

if (!empty($columnSearches)) {
    $whereClause = " AND " . implode(" AND ", $columnSearches);
    $baseQuery .= $whereClause;
    $countQuery .= $whereClause;
}

// ---- Hitung total setelah filter ----
$stmt = $pdo->prepare($countQuery);
foreach ($bindings as $param => $value) {
    $stmt->bindValue($param, $value);
}
$stmt->execute();
$filteredRecords = $stmt->fetchColumn();

// ---- Ordering ----
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

if (isset($columns[$orderColumn])) {
    $orderBy = " ORDER BY " . $columns[$orderColumn] . " " . ($orderDir === 'asc' ? 'ASC' : 'DESC');
}

// ---- Query data utama ----
$dataQuery = "SELECT * " . $baseQuery . $orderBy . " LIMIT :start, :length";
$stmt = $pdo->prepare($dataQuery);

// Bind semua parameter pencarian
foreach ($bindings as $param => $value) {
    $stmt->bindValue($param, $value);
}

// Bind pagination
$stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
$stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- Helper ----
function normalize_email($val)
{
    $val = trim($val ?? '');
    if (stripos($val, 'mailto:') === 0) {
        $val = substr($val, 7);
    }
    if (($qpos = strpos($val, '?')) !== false) {
        $val = substr($val, 0, $qpos);
    }
    return $val;
}

// ---- Format response ----
$response = [
    "draw"            => intval($draw),
    "recordsTotal"    => intval($totalRecords),
    "recordsFiltered" => intval($filteredRecords),
    "data"            => []
];

foreach ($data as $row) {
    $emailClean = normalize_email($row['email'] ?? '');
    $response['data'][] = [
        'DT_RowIndex'       => '',
        'nama_perusahaan'   => $row['nama_perusahaan'] ?? null,
        'email'             => $emailClean ?: null,
        'no_telp1'          => $row['no_telp1'] ?? null,
        'kategori_perusahaan' => $row['kategori_perusahaan'] ?? null,
        'ditemukan_oleh'    => $row['ditemukan_oleh'] ?? null,
        'status'            => $row['status'] ?? null,
        'actions'           => [
            'email'    => $emailClean,
            'status'   => $row['status'] ?? '',
            'raw_data' => $row
        ]
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
