<?php

require_once "config.php";

/* =========================
   FORCE HTTPS
========================= */
function enforce_https(){
    if(
        (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') &&
        $_SERVER['HTTP_HOST'] !== 'localhost'
    ){
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit;
    }
}

/* =========================
   SECURE SESSION SETTINGS
========================= */
function secure_session(){

    if (session_status() === PHP_SESSION_NONE) {

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        session_start();
    }

    // Session timeout (30 min)
    if(isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)){
        session_unset();
        session_destroy();
    }

    $_SESSION['LAST_ACTIVITY'] = time();
}

secure_session();

/* =========================
   SANITIZE INPUT
========================= */
function sanitize($data){
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/* =========================
   CSRF PROTECTION
========================= */
function generate_csrf_token(){
    if(empty($_SESSION['csrf_token'])){
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token){
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/* =========================
   AUTH CHECKS
========================= */
function is_logged_in(){
    return isset($_SESSION['user_id']);
}

function is_admin(){
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_login(){
    if(!is_logged_in()){
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        exit;
    }
}

function require_admin(){
    if(!is_logged_in() || !is_admin()){
        http_response_code(403);
        echo json_encode(["error" => "Forbidden"]);
        exit;
    }
}

/* =========================
   RATE LIMIT (EMAIL + IP)
========================= */
function is_rate_limited($conn, $identifier){

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $stmt = $conn->prepare("
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE (identifier=? OR ip=?)
        AND success=0 
        AND created_at > (NOW() - INTERVAL 15 MINUTE)
    ");

    $stmt->bind_param("ss", $identifier, $ip);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    return ($result['attempts'] >= 5);
}

/* =========================
   LOG LOGIN ATTEMPTS
========================= */
function log_login_attempt($conn, $identifier, $success){

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $stmt = $conn->prepare("
        INSERT INTO login_attempts (identifier, ip, success, created_at)
        VALUES (?, ?, ?, NOW())
    ");

    $stmt->bind_param("ssi", $identifier, $ip, $success);
    $stmt->execute();
}

/* =========================
   JWT AUTH
========================= */
function generate_jwt($user_id){

    $secret = getenv('CSRF_SECRET') ?: "fallback_secret";

    $payload = [
        "user_id" => $user_id,
        "exp" => time() + 3600
    ];

    $base = base64_encode(json_encode($payload));

    return $base . "." . hash_hmac('sha256', $base, $secret);
}

function verify_jwt($token){

    $parts = explode(".", $token);

    if(count($parts) !== 2){
        return false;
    }

    list($base, $signature) = $parts;

    $secret = getenv('CSRF_SECRET') ?: "fallback_secret";

    $valid_sig = hash_hmac('sha256', $base, $secret);

    if(!hash_equals($valid_sig, $signature)){
        return false;
    }

    $payload = json_decode(base64_decode($base), true);

    if(!$payload || $payload['exp'] < time()){
        return false;
    }

    return $payload['user_id'];
}