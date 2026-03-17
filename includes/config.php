<?php

/* ==============================
   1. SECURE SESSION SETTINGS
============================== */

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '', // set your domain in production
        'secure' => isset($_SERVER['HTTPS']), // true if HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    session_start();
}

/* Regenerate session ID (prevent hijacking) */
if (!isset($_SESSION['created'])) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

/* Session timeout (30 minutes) */
if (isset($_SESSION['created']) && (time() - $_SESSION['created'] > 1800)) {
    session_unset();
    session_destroy();
    session_start();
}

/* ==============================
   2. SECURITY HEADERS
============================== */

header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header("Referrer-Policy: no-referrer-when-downgrade");

/* Content Security Policy (basic safe version) */
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net;");

/* ==============================
   3. ERROR HANDLING (PRODUCTION SAFE)
============================== */

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

/* ==============================
   4. TIMEZONE
============================== */

date_default_timezone_set("Africa/Nairobi");

/* ==============================
   5. LOAD ENV VARIABLES (.env)
============================== */

function load_env($path) {

    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {

        if (strpos(trim($line), '#') === 0) continue;

        list($key, $value) = explode('=', $line, 2);

        $key = trim($key);
        $value = trim($value);

        if (!defined($key)) {
            define($key, $value);
        }
    }
}

/* Load .env file */
load_env(__DIR__ . "/../.env");

/* ==============================
   6. SYSTEM CONSTANTS
============================== */

define("APP_NAME", "BuildSmart AI");
define("APP_URL", "http://localhost/buildsmart"); // change in production

/* ==============================
   7. BASIC SECURITY UTILITIES
============================== */

/* Generate CSRF Token */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/* Verify CSRF Token */
function verify_csrf($token) {
    return isset($_SESSION['csrf_token']) &&
           hash_equals($_SESSION['csrf_token'], $token);
}

/* ==============================
   8. HELPER FUNCTIONS
============================== */

function json_response($data) {
    header("Content-Type: application/json");
    echo json_encode($data);
    exit;
}

function redirect($url) {
    header("Location: " . $url);
    exit;
}