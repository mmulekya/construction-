<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime'=>0,
        'path'=>'/',
        'secure'=>isset($_SERVER['HTTPS']),
        'httponly'=>true,
        'samesite'=>'Strict'
    ]);
    session_start();
    if(!isset($_SESSION['created'])) { session_regenerate_id(true); $_SESSION['created'] = time();}
}

// Security headers
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header("Referrer-Policy: no-referrer-when-downgrade");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self';");

// Error handling
ini_set('display_errors',0);
ini_set('log_errors',1);
ini_set('error_log',__DIR__.'/../logs/php_errors.log');

// Timezone
date_default_timezone_set("Africa/Nairobi");

// Load .env
function load_env($path){
    if(!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach($lines as $line){
        if(strpos(trim($line),'#')===0) continue;
        if(!str_contains($line,'=')) continue;
        list($key,$value)=explode('=',$line,2);
        $key=trim($key); $value=trim($value);
        if(!defined($key)) define($key,$value);
    }
}
load_env(__DIR__.'/../.env');

// Constants
define("APP_NAME","BuildSmart AI");
define("APP_URL","http://localhost/buildsmart");

// CSRF
function csrf_token(){ if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }
function verify_csrf($token){ return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'],$token); }

// Helpers
function json_response($data){ header("Content-Type: application/json"); echo json_encode($data); exit; }
function redirect($url){ header("Location:".$url); exit; }