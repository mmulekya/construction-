<?php
// =========================================
// 🔒 PRODUCTION CONFIG & SECURITY SETTINGS
// =========================================

header("Content-Security-Policy: 
default-src 'self'; 
script-src 'self'; 
style-src 'self' 'unsafe-inline'; 
img-src 'self' data:; 
connect-src 'self'; 
font-src 'self'; 
frame-ancestors 'none';
");

// -------------------------------
// 🚫 ERROR HANDLING
// -------------------------------
ini_set('display_errors', 0); // Never show errors publicly
error_reporting(E_ALL);
set_error_handler(function($errno, $errstr, $errfile, $errline){
    error_log("Error [$errno]: $errstr in $errfile at line $errline");
    return true; // prevent default output
});
set_exception_handler(function($e){
    error_log("Uncaught Exception: ".$e->getMessage());
});

// -------------------------------
// 🔐 SECURE SESSION SETTINGS
// -------------------------------
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

// Secure cookie if HTTPS
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', 1);
}

// Session configuration
session_name("BuildSmartSession");
session_start();

// Regenerate session periodically (prevents fixation)
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = time();
}

// -------------------------------
// 🔑 LOAD ENV FILE
// -------------------------------
$env_path = __DIR__ . '/../.env';
if (!file_exists($env_path)) {
    http_response_code(500);
    die("Configuration file missing");
}

$env = parse_ini_file($env_path, false, INI_SCANNER_TYPED);
define("DB_HOST", $env['DB_HOST'] ?? 'localhost');
define("DB_USER", $env['DB_USER'] ?? '');
define("DB_PASS", $env['DB_PASS'] ?? '');
define("DB_NAME", $env['DB_NAME'] ?? '');
define("OPENAI_API_KEY", $env['OPENAI_API_KEY'] ?? '');

// Validate critical configs
if (empty(DB_USER) || empty(DB_NAME)) {
    http_response_code(500);
    die("Invalid database configuration");
}

// -------------------------------
// 🛡️ HTTP SECURITY HEADERS
// -------------------------------
header("X-Frame-Options: SAMEORIGIN");      // Prevent clickjacking
header("X-Content-Type-Options: nosniff");  // Prevent MIME type sniffing
header("X-XSS-Protection: 1; mode=block");  // Basic XSS protection
header("Referrer-Policy: no-referrer-when-downgrade");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");

// -------------------------------
// 🔐 CSRF TOKEN GENERATION
// -------------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to get current CSRF token
function get_csrf_token(): string {
    return $_SESSION['csrf_token'];
}

// -------------------------------
// 🧼 SANITIZATION FUNCTIONS
// -------------------------------
function sanitize_input(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Safe integer casting
function safe_int($value, $default = 0): int {
    return filter_var($value, FILTER_VALIDATE_INT) ?? $default;
}

// -------------------------------
// 🚫 BOT / SCRIPT BLOCKER
// -------------------------------
$bad_agents = ['curl', 'wget', 'python-requests', 'libwww-perl'];
$ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
foreach ($bad_agents as $agent) {
    if (strpos($ua, $agent) !== false) {
        http_response_code(403);
        exit("Access denied");
    }
}

// -------------------------------
// 🔐 LIMIT REQUEST SIZE
// -------------------------------
$max_post_size = 10240; // 10 KB
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents("php://input");
    if ($input && strlen($input) > $max_post_size) {
        http_response_code(413);
        exit("Request too large");
    }
}

// -------------------------------
// 🔒 STRONG PASSWORD SALT CONFIG
// -------------------------------
define("PASSWORD_COST", 12); // bcrypt cost factor

function hash_password(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ["cost"=>PASSWORD_COST]);
}

// -------------------------------
// 🔐 DATABASE CONNECTION (mysqli with error handling)
// -------------------------------
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    die("Database connection failed");
}
$conn->set_charset("utf8mb4"); // prevent charset issues & XSS via DB

// -------------------------------
// 🔐 OPTIONAL DEBUGGING (OFF IN PROD)
// -------------------------------
// $debug = $env['DEBUG'] ?? false;
// if ($debug) {
//     ini_set('display_errors', 1);
//     error_reporting(E_ALL);
// }

// -------------------------------
// ✅ CONFIGURATION COMPLETE
// -------------------------------