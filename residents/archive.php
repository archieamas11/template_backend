<?php
require_once __DIR__ . '/../config.php';

$payload = auth_payload_or_401();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
  json(['message' => 'Invalid id'], 400);
}

try {
  $pdo = db();

  // Check existence first (optional but yields clearer 404)
  $check = $pdo->prepare('SELECT id FROM residents WHERE id = :id');
  $check->execute([':id' => $id]);
  $exists = $check->fetchColumn();
  if (!$exists) {
    json(['message' => 'Not found'], 404);
  }

  $stmt = $pdo->prepare('UPDATE residents SET isArchived = 1 WHERE id = :id');
  $stmt->execute([':id' => $id]);

  json(['message' => 'Resident archived successfully']);
} catch (Throwable $e) {
  json(['message' => 'Server error'], 500);
}