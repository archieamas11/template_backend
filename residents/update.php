<?php
require_once __DIR__ . '/../config.php';

$payload = auth_payload_or_401();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) json(['message' => 'Invalid id'], 400);

$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
  $pdo = db();
  // Build dynamic update
  $fields = [];
  $params = [':id' => $id];
  $allowed = ['first_name','last_name','middle_name','age','gender','address','barangay','contact_number','occupation','civil_status'];
  foreach ($allowed as $k) {
    if (array_key_exists($k, $input)) {
      $fields[] = "$k = :$k";
      $params[":$k"] = $input[$k];
    }
  }
  if (empty($fields)) {
    json(['message' => 'No changes'], 400);
  }
  $sql = 'UPDATE residents SET ' . implode(', ', $fields) . ' WHERE id = :id';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  $row = $pdo->prepare('SELECT * FROM residents WHERE id = :id');
  $row->execute([':id' => $id]);
  $resident = $row->fetch();
  json(['message' => 'Updated', 'resident' => $resident]);
} catch (Throwable $e) {
  json(['message' => 'Server error'], 500);
}
