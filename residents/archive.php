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

  // Soft-archive using isArchive flag. Add column if missing.
  try {
    $stmt = $pdo->prepare('UPDATE residents SET isArchive = 1 WHERE id = :id');
    $stmt->execute([':id' => $id]);
  } catch (Throwable $inner) {
    // If column doesn't exist yet, create it then retry once.
    // Note: In production, use migrations. This is a dev-friendly safeguard.
    $pdo->exec("ALTER TABLE residents ADD COLUMN isArchive TINYINT(1) NOT NULL DEFAULT 0");
    $stmt = $pdo->prepare('UPDATE residents SET isArchive = 1 WHERE id = :id');
    $stmt->execute([':id' => $id]);
  }

  json(['message' => 'Resident archived successfully']);
} catch (Throwable $e) {
  json(['message' => 'Server error'], 500);
}