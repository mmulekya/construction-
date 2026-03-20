<?php
// 🔒 Disable error display (production)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 🔐 Secure session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

// 🔑 Load environment variables
$env_path = __DIR__ . '/../.env';

if(file_exists($env_path)){
    $env = parse_ini_file($env_path);
} else {
    die("Config error: .env file missing");
}

// 🔐 Define constants safely
define("DB_HOST", $env['DB_HOST'] ?? 'localhost');
define("DB_USER", $env['DB_USER'] ?? '');
define("DB_PASS", $env['DB_PASS'] ?? '');
define("DB_NAME", $env['DB_NAME'] ?? '');
define("OPENAI_API_KEY", $env['OPENAI_API_KEY'] ?? '');

// 🔒 Validate config
if(empty(DB_USER) || empty(DB_NAME)){
    die("Database configuration error");
}

// 🛡️ Global security headers
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// 🔐 CSRF Token generator
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

if(empty($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 🧼 Sanitization function
function sanitize($data){
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}