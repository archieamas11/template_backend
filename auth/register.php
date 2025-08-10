<?php
require_once __DIR__ . '/../config.php';

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$isAdmin = intval($input['isAdmin'] ?? 0);

if ($username === '' || $password === '') {
    json(['message' => 'Username and password are required'], 400);
}

try {
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO users (username, password, isAdmin) VALUES (:u, :p, :a)');
    $stmt->execute([
        ':u' => $username,
        ':p' => password_hash($password, PASSWORD_BCRYPT),
        ':a' => $isAdmin > 0 ? 1 : 0,
    ]);
    json(['message' => 'Registered']);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        json(['message' => 'Username already exists'], 409);
    }
    json(['message' => 'Server error'], 500);
}