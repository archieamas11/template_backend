<?php
require_once __DIR__ . '/../config.php';

$payload = auth_payload_or_401();

json(['user' => [
    'id' => intval($payload['sub'] ?? 0),
    'username' => (string)($payload['username'] ?? ''),
    'isAdmin' => intval($payload['isAdmin'] ?? 0),
]]);