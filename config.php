<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
// 🌐 CORS: Allow origins from env (CORS_ALLOWED_ORIGINS, comma-separated)
$allowed_origins_env = $_ENV['CORS_ALLOWED_ORIGINS'] ?? '';
$allowed_origins = array_map('trim', explode(',', $allowed_origins_env));
if ($allowed_origins_env && in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
} else {
    // For development: allow localhost/127.0.0.1 with any port or without origin
    if (preg_match('/^http:\/\/(localhost|127\.0\.0\.1)(:\\d+)?$/', $origin)) {
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    } elseif (empty($origin)) {
        header("Access-Control-Allow-Origin: *");
        // Credentials cannot be used with wildcard
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['ok' => true]);
    exit();
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('Dotenv\\Dotenv')) {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->safeLoad();
    }
}

$DB_HOST = $_ENV['DB_HOST'] ?? 'localhost';
$DB_NAME = $_ENV['DB_NAME'] ?? 'default';
$DB_USER = $_ENV['DB_USER'] ?? 'mysql';
$DB_PASS = $_ENV['DB_PASS'] ?? '';
$JWT_SECRET = $_ENV['JWT_SECRET'] ?? '';
$JWT_EXPIRES = intval($_ENV['JWT_EXPIRES'] ?? '3600');

// 🛡️ Validate required envs for production
if (php_sapi_name() !== 'cli') {
    $missing = [];
    if ($DB_HOST === 'localhost') $missing[] = 'DB_HOST';
    if ($DB_NAME === 'default') $missing[] = 'DB_NAME';
    if ($DB_USER === 'mysql' && empty($DB_PASS)) $missing[] = 'DB_PASS';
    if (empty($JWT_SECRET)) $missing[] = 'JWT_SECRET';
    if (!empty($missing)) {
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => 'Missing or insecure environment variables', 'missing' => $missing], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function db(): PDO {
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;
    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

function json($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// 🔐 Robust Authorization header helpers (works across Apache/FastCGI/IIS)
function get_auth_header(): string {
    // Common server variables
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        return trim($_SERVER['HTTP_AUTHORIZATION']);
    }
    if (!empty($_SERVER['Authorization'])) { // Some servers
        return trim($_SERVER['Authorization']);
    }
    // Fallback to getallheaders()
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach ($headers as $name => $value) {
                if (strcasecmp($name, 'Authorization') === 0) {
                    return trim((string)$value);
                }
            }
        }
    }
    return '';
}

function get_bearer_token(): string {
    $auth = get_auth_header();
    if ($auth !== '' && strncasecmp($auth, 'Bearer ', 7) === 0) {
        return substr($auth, 7);
    }
    // Optional: allow token from cookie named "token"
    if (!empty($_COOKIE['token'])) {
        return (string)$_COOKIE['token'];
    }
    return '';
}

function auth_payload_or_401(): array {
    global $JWT_SECRET;
    $token = get_bearer_token();
    if ($token === '') {
        json(['message' => 'Unauthorized'], 401);
    }
    $payload = verify_jwt($token, $JWT_SECRET);
    if ($payload === false) {
        json(['message' => 'Invalid token'], 401);
    }
    return $payload;
}

// Dev helper: ensure schema & seed default user if empty
function ensure_schema(PDO $pdo): void {
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  isAdmin TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
    try {
        $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count === 0) {
            // password: admin123
            $hash = password_hash('admin123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, isAdmin) VALUES (:u, :p, :a)');
            $stmt->execute([':u' => 'admin', ':p' => $hash, ':a' => 1]);
        }
    } catch (Throwable $e) {
        // ignore
    }
}

// JWT helpers using RFC 7515 base64url encoding
function b64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64url_decode(string $data): string|false {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function make_jwt(array $payload, string $secret, int $expiresIn): string {
    $header = b64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $iat = time();
    $payload = array_merge($payload, ['iat' => $iat, 'exp' => $iat + $expiresIn]);
    $payloadEnc = b64url_encode(json_encode($payload));
    $signature = b64url_encode(hash_hmac('sha256', "$header.$payloadEnc", $secret, true));
    return "$header.$payloadEnc.$signature";
}

function verify_jwt(string $jwt, string $secret) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return false;
    [$h, $p, $s] = $parts;
    $validSig = b64url_encode(hash_hmac('sha256', "$h.$p", $secret, true));
    if (!hash_equals($validSig, $s)) return false;
    $payloadJson = b64url_decode($p);
    if ($payloadJson === false) return false;
    $payload = json_decode($payloadJson, true);
    if (!is_array($payload)) return false;
    if (($payload['exp'] ?? 0) < time()) return false;
    return $payload;
}

// Run ensure_schema in dev-like environments
try {
    $pdo = db();
    ensure_schema($pdo);
} catch (Throwable $e) {
    // ignore
}