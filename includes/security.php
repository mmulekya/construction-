<?php
function generate_csrf(){
    if(!isset($_SESSION['csrf_token'])){
        $_SESSION['csrf_token']=bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function sanitize($data){
    return htmlspecialchars(trim($data),ENT_QUOTES,'UTF-8');
}

function verify_csrf($token){
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'],$token);
}

function enforce_https(){
    if(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']!=='on'){
        header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
        exit;
    }
}