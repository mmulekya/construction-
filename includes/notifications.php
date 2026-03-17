<?php
function send_notification($conn,$user_id,$message){
    $stmt = $conn->prepare("INSERT INTO notifications (user_id,message) VALUES (?,?)");
    $stmt->bind_param("is",$user_id,$message);
    $stmt->execute();
}

function get_notifications($conn,$user_id){
    $stmt = $conn->prepare("SELECT id,message,created_at,is_read FROM notifications WHERE user_id=? ORDER BY created_at DESC");
    $stmt->bind_param("i",$user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_all(MYSQLI_ASSOC);
}