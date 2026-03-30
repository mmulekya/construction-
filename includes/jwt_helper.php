<?php

function generate_jwt($user_id){

    $secret = getenv("CSRF_SECRET");

    $header = base64_encode(json_encode(['typ'=>'JWT','alg'=>'HS256']));
    $payload = base64_encode(json_encode([
        'user_id'=>$user_id,
        'exp'=>time()+3600
    ]));

    $signature = hash_hmac(
        'sha256',
        "$header.$payload",
        $secret,
        true
    );

    $signature = base64_encode($signature);

    return "$header.$payload.$signature";
}

function verify_jwt($token){

    if(!$token) return false;

    $secret = getenv("CSRF_SECRET");

    $parts = explode(".", $token);

    if(count($parts) !== 3) return false;

    list($header,$payload,$signature) = $parts;

    $valid_sig = base64_encode(
        hash_hmac('sha256', "$header.$payload", $secret, true)
    );

    if(!hash_equals($valid_sig, $signature)) return false;

    $data = json_decode(base64_decode($payload), true);

    if(!$data || $data['exp'] < time()) return false;

    return $data['user_id'];
}