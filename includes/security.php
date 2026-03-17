<?php
function log_action($conn,$action_type,$action_details="",$user_id=null,$admin_id=null){
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $stmt = $conn->prepare(
        "INSERT INTO logs (user_id, admin_id, action_type, action_details, ip_address, user_agent) 
         VALUES (?,?,?,?,?,?)"
    );
    $stmt->bind_param("iissss",$user_id,$admin_id,$action_type,$action_details,$ip,$ua);
    $stmt->execute();
}