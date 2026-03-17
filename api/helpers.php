<?php
function json_response($data){ header('Content-Type: application/json'); echo json_encode($data); exit; }

function log_action($conn,$action,$details,$user_id){
    $stmt = $conn->prepare("INSERT INTO logs (action,details,user_id) VALUES (?,?,?)");
    $stmt->bind_param("ssi",$action,$details,$user_id);
    $stmt->execute();
}

function verify_csrf_or_exit($token){
    if(!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']){
        json_response(["error"=>"Invalid CSRF token"]);
    }
}

function rate_limit_check($user_id, $limit){
    if(!isset($_SESSION['ai_requests'])) $_SESSION['ai_requests'] = [];
    $_SESSION['ai_requests'][] = time();
    $_SESSION['ai_requests'] = array_filter($_SESSION['ai_requests'], fn($t)=>$t>time()-60);
    if(count($_SESSION['ai_requests']) > $limit) json_response(["error"=>"Rate limit exceeded"]);
}

function has_permission($conn,$user_id,$perm){
    $stmt = $conn->prepare("SELECT 1 FROM user_permissions WHERE user_id=? AND permission=?");
    $stmt->bind_param("is",$user_id,$perm);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows>0;
}