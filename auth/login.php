<?php
require_once __DIR__ . '/../config.php';
global $JWT_SECRET, $JWT_EXPIRES;

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if ($username === '' || $password === '') {
    json(['message' => 'Username and password are required'], 400);
}

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, username, password, isAdmin FROM users WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password'])) {
        json(['message' => 'Invalid credentials'], 401);
    }

    $token = make_jwt([
        'sub' => $user['id'],
        'username' => $user['username'],
        'isAdmin' => intval($user['isAdmin'])
    ], $JWT_SECRET, $JWT_EXPIRES);

    json(['token' => $token, 'user' => [
        'id' => intval($user['id']),
        'username' => $user['username'],
        'isAdmin' => intval($user['isAdmin'])
    ]]);
} catch (Throwable $e) {
    json(['message' => 'Server error'], 500);
}