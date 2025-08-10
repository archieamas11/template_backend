<?php
require_once __DIR__ . '/../config.php';

$payload = auth_payload_or_401();

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$required = ['first_name','last_name','age','gender','address','barangay','created_by'];
foreach ($required as $r) {
  if (!isset($input[$r]) || $input[$r] === '') {
    json(['message' => "Missing field: $r"], 400);
  }
}

try {
  $pdo = db();
  $stmt = $pdo->prepare('INSERT INTO residents (first_name,last_name,middle_name,age,gender,address,barangay,contact_number,occupation,civil_status,created_by) VALUES (:first_name,:last_name,:middle_name,:age,:gender,:address,:barangay,:contact_number,:occupation,:civil_status,:created_by)');
  $stmt->execute([
    ':first_name' => $input['first_name'],
    ':last_name' => $input['last_name'],
    ':middle_name' => $input['middle_name'] ?? null,
    ':age' => (int)$input['age'],
    ':gender' => $input['gender'],
    ':address' => $input['address'],
    ':barangay' => $input['barangay'],
    ':contact_number' => $input['contact_number'] ?? null,
    ':occupation' => $input['occupation'] ?? null,
    ':civil_status' => $input['civil_status'] ?? 'Single',
    ':created_by' => (int)$input['created_by'],
  ]);
  $id = (int)$pdo->lastInsertId();
  $row = $pdo->prepare('SELECT * FROM residents WHERE id = :id');
  $row->execute([':id' => $id]);
  $resident = $row->fetch();
  json(['message' => 'Created', 'resident' => $resident], 201);
} catch (Throwable $e) {
  json(['message' => 'Server error'], 500);
}
