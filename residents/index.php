<?php
require_once __DIR__ . '/../config.php';

$payload = auth_payload_or_401();

// Query params
$q = trim($_GET['q'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = max(1, min(100, intval($_GET['pageSize'] ?? 10)));
$sortBy = $_GET['sortBy'] ?? 'id';
$sortDir = strtolower($_GET['sortDir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
$gender = $_GET['gender'] ?? '';
$barangay = $_GET['barangay'] ?? '';

$allowedSort = ['id','first_name','last_name','age','gender','barangay','created_at','updated_at'];
if (!in_array($sortBy, $allowedSort, true)) {
  $sortBy = 'id';
}

try {
  $pdo = db();

  $where = [];
  $params = [];

  // Add archive filter only if column exists
  $hasArchiveCol = false;
  try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM residents LIKE 'isArchive'");
    $hasArchiveCol = (bool)$colCheck->fetch();
  } catch (Throwable $e) {
    $hasArchiveCol = false;
  }
  if ($hasArchiveCol) {
    $where[] = 'isArchive = 0';
  }

  if ($q !== '') {
    $where[] = "(id = :idExact OR first_name LIKE :q OR last_name LIKE :q OR middle_name LIKE :q OR address LIKE :q OR occupation LIKE :q)";
    $params[':idExact'] = ctype_digit($q) ? (int)$q : -1;
    $params[':q'] = "%$q%";
  }
  if ($gender === 'Male' || $gender === 'Female') {
    $where[] = 'gender = :gender';
    $params[':gender'] = $gender;
  }
  if ($barangay !== '') {
    $where[] = 'barangay = :barangay';
    $params[':barangay'] = $barangay;
  }

  $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

  // Compute total rows matching filters
  if (count($where)) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM residents $whereSql");
    foreach ($params as $k => $v) {
      $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    $total = (int)$stmt->fetchColumn();
  } else {
    $total = (int)$pdo->query('SELECT COUNT(*) FROM residents')->fetchColumn();
  }

  $offset = ($page - 1) * $pageSize;
  $sql = "SELECT * FROM residents $whereSql ORDER BY $sortBy $sortDir LIMIT :limit OFFSET :offset";
  $stmt = $pdo->prepare($sql);
  foreach ($params as $k => $v) $stmt->bindValue($k, $v);
  $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll();

  json([
    'data' => $rows,
    'page' => $page,
    'pageSize' => $pageSize,
    'total' => $total,
  ]);
} catch (Throwable $e) {
  json(['message' => 'Server error'], 500);
}