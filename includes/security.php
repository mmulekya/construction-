<?php
// ==========================================
// 🔒 FINAL SECURITY LAYER (PRODUCTION READY)
// ==========================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

// ============================
// 🌐 GET REAL CLIENT IP
// ============================
function get_client_ip() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// ============================
// 🚫 BLOCK BANNED IP
// ============================
function check_banned_ip($conn) {
    $ip = get_client_ip();

    $stmt = $conn->prepare("SELECT id FROM banned_ips WHERE ip=? LIMIT 1");
    $stmt->bind_param("s", $ip);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(403);
        exit(json_encode(["error"=>"🚫 Your IP has been blocked"]));
    }

    $stmt->close();
}

// Run immediately
check_banned_ip($conn);

// ============================
// 🔐 CSRF
// ============================
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ============================
// ⚡ RATE LIMIT (SAFE + ATOMIC)
// ============================
function check_rate_limit($conn, $key, $limit = 10, $seconds = 60) {

    $ip = get_client_ip();
    $identifier = $key . "_" . $ip;

    $stmt = $conn->prepare("
        INSERT INTO rate_limits (`key`, `requests`, `last_request`)
        VALUES (?, 1, NOW())
        ON DUPLICATE KEY UPDATE
            requests = IF(TIMESTAMPDIFF(SECOND, last_request, NOW()) > ?, 1, requests + 1),
            last_request = NOW()
    ");
    $stmt->bind_param("si", $identifier, $seconds);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("SELECT requests FROM rate_limits WHERE `key`=?");
    $stmt->bind_param("s", $identifier);
    $stmt->execute();

    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res && $res['requests'] > $limit) {
        log_attack($conn, "rate_limit");
        http_response_code(429);
        exit(json_encode(["error"=>"Too many requests"]));
    }
}

function get_authenticated_user(){

    // 1. Session
    if(isset($_SESSION['user_id'])){
        return $_SESSION['user_id'];
    }

    // 2. JWT
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';

    if($token){
        return verify_jwt($token);
    }

    return false;
}

// ============================
// 📝 LOGIN ATTEMPTS + AUTO BAN
// ============================
function log_login_attempt($conn, $email, $success = 0) {

    $ip = get_client_ip();

    $stmt = $conn->prepare("
        INSERT INTO login_attempts (email, ip, success, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param("ssi", $email, $ip, $success);
    $stmt->execute();
    $stmt->close();

    // Trigger auto-ban
    if (!$success) {
        auto_ban_ip($conn, $ip);
    }
}

// ============================
// 🔥 AUTO BAN SYSTEM
// ============================
function auto_ban_ip($conn, $ip) {

    $stmt = $conn->prepare("
        SELECT COUNT(*) as failed
        FROM login_attempts
        WHERE ip=? AND success=0
        AND created_at > NOW() - INTERVAL 15 MINUTE
    ");
    $stmt->bind_param("s", $ip);
    $stmt->execute();

    $failed = $stmt->get_result()->fetch_assoc()['failed'];
    $stmt->close();

    if ($failed >= 10) {

        $stmt = $conn->prepare("
            INSERT IGNORE INTO banned_ips (ip, reason, banned_at)
            VALUES (?, 'Brute-force attack', NOW())
        ");
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $stmt->close();

        log_attack($conn, "ip_banned");
    }
}

// ============================
// 🚨 ATTACK LOGGING
// ============================
function log_attack($conn, $type){

    $ip = get_client_ip();

    $stmt = $conn->prepare("
        INSERT INTO logs (action, ip_address, created_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->bind_param("ss", $type, $ip);
    $stmt->execute();
    $stmt->close();
}

// ============================
// 🧹 CLEANUP (AUTO)
// ============================
function clean_security_logs($conn){

    $conn->query("DELETE FROM login_attempts WHERE created_at < NOW() - INTERVAL 30 DAY");
    $conn->query("DELETE FROM rate_limits WHERE last_request < NOW() - INTERVAL 1 HOUR");
}

// Optional auto cleanup
clean_security_logs($conn);