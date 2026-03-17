<?php
require_once "database.php";

function notify_admin($conn, $notification_type, $notification_details){
    // Get all admins
    $stmt = $conn->prepare("
        SELECT u.id AS admin_id 
        FROM users u 
        JOIN user_roles ur ON u.id = ur.user_id
        JOIN roles r ON ur.role_id = r.id
        WHERE r.role_name='admin'
    ");
    $stmt->execute();
    $res = $stmt->get_result();

    while($admin = $res->fetch_assoc()){
        $stmt2 = $conn->prepare("
            INSERT INTO notifications (admin_id, notification_type, notification_details) 
            VALUES (?,?,?)
        ");
        $stmt2->bind_param("sss",$admin['admin_id'],$notification_type,$notification_details);
        $stmt2->execute();

        // Optional: send email
        // mail($admin_email, "BuildSmart Notification: $notification_type", $notification_details);
    }
}