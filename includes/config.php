<?php
// ==============================
// 🔒 PRODUCTION ERROR SETTINGS
// ==============================
ini_set('display_errors', 0);
error_reporting(E_ALL);

// ==============================
// 🔐 SECURE SESSION SETTINGS
// ==============================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Only enable secure cookie if HTTPS
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', 1);
}

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==============================
// 🔑 LOAD ENV FILE
// ==============================
$env_path = __DIR__ . '/../.env';

if (!file_exists($env_path)) {
    http_response_code(500);
    die("Configuration error");
}

$env = parse_ini_file($env_path);

// ==============================
// 🔐 DEFINE CONSTANTS
// ==============================
define("DB_HOST", $env['DB_HOST'] ?? 'localhost');
define("DB_USER", $env['DB_USER'] ?? '');
define("DB_PASS", $env['DB_PASS'] ?? '');
define("DB_NAME", $env['DB_NAME'] ?? '');
define("OPENAI_API_KEY", $env['OPENAI_API_KEY'] ?? '');

// ==============================
// 🔒 VALIDATE DATABASE CONFIG
// ==============================
if (empty(DB_USER) || empty(DB_NAME)) {
    http_response_code(500);
    die("Database configuration error");
}

// ==============================
// 🛡️ SECURITY HEADERS
// ==============================
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Optional (safe to include)
header("Referrer-Policy: no-referrer-when-downgrade");

// ==============================
// 🔐 CSRF TOKEN INIT
// ==============================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==============================
// 🧼 SANITIZATION FUNCTION
// ==============================
function sanitize($data){
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// ==============================
// 🚫 BASIC BOT BLOCKING (SAFE)
// ==============================
if (isset($_SERVER['HTTP_USER_AGENT'])) {

    $bad_agents = ['curl', 'wget', 'python-requests'];
    $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);

    foreach ($bad_agents as $bad) {
        if (strpos($user_agent, $bad) !== false) {
            http_response_code(403);
            exit("Access denied");
        }
    }
}

// ==============================
// 🔐 LIMIT REQUEST SIZE (SAFE)
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $max_size = 10000; // 10KB

    $input = file_get_contents("php://input");

    if ($input && strlen($input) > $max_size) {
        http_response_code(413);
        exit("Request too large");
    }
}