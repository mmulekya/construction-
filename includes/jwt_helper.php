<?php

// =========================
// 🔐 BASE64 URL ENCODE
// =========================
function base64url_encode($data){
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data){
    return base64_decode(strtr($data, '-_', '+/'));
}

// =========================
// 🔐 GENERATE JWT
// =========================
function generate_jwt($user_id){

    global $conn;

    // Get user role
    $stmt = $conn->prepare("SELECT role FROM users WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $role = $user['role'] ?? 'user';

    $secret = getenv("CSRF_SECRET");

    $header = base64url_encode(json_encode([
        'typ'=>'JWT',
        'alg'=>'HS256'
    ]));

    $payload = base64url_encode(json_encode([
        'user_id'=>$user_id,
        'role'=>$role,
        'iat'=>time(),
        'exp'=>time()+3600
    ]));

    $signature = base64url_encode(
        hash_hmac('sha256', "$header.$payload", $secret, true)
    );

    return "$header.$payload.$signature";
}

// =========================
// 🔐 VERIFY JWT
// =========================
function verify_jwt($token){

    if(!$token) return false;

    $secret = getenv("CSRF_SECRET");

    $parts = explode(".", $token);
    if(count($parts) !== 3) return false;

    list($header, $payload, $signature) = $parts;

    $valid_signature = base64url_encode(
        hash_hmac('sha256', "$header.$payload", $secret, true)
    );

    // Signature check
    if(!hash_equals($valid_signature, $signature)){
        return false;
    }

    // Decode payload
    $data = json_decode(base64url_decode($payload), true);

    if(!$data){
        return false;
    }

    // Expiration check
    if($data['exp'] < time()){
        return false;
    }

    return $data; // 🔥 return FULL payload
}