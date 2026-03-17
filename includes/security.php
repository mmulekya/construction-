function has_permission($conn, $user_id, $permission_name){
    $stmt = $conn->prepare("
        SELECT 1 
        FROM user_roles ur
        JOIN role_permissions rp ON ur.role_id = rp.role_id
        JOIN permissions p ON rp.permission_id = p.id
        WHERE ur.user_id = ? AND p.permission_name = ? LIMIT 1
    ");
    $stmt->bind_param("is", $user_id, $permission_name);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->num_rows > 0;
}
function has_permission($conn, $user_id, $permission_name){
    $stmt = $conn->prepare("
        SELECT 1 
        FROM user_roles ur
        JOIN role_permissions rp ON ur.role_id = rp.role_id
        JOIN permissions p ON rp.permission_id = p.id
        WHERE ur.user_id = ? AND p.permission_name = ? LIMIT 1
    ");
    $stmt->bind_param("is", $user_id, $permission_name);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->num_rows > 0;
}
function trigger_alert($conn, $alert_type, $alert_details="", $user_id=null, $admin_id=null){
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $stmt = $conn->prepare("
        INSERT INTO alerts (user_id, admin_id, alert_type, alert_details, ip_address, user_agent)
        VALUES (?,?,?,?,?,?)
    ");
    $stmt->bind_param("iissss", $user_id, $admin_id, $alert_type, $alert_details, $ip, $ua);
    $stmt->execute();

    // Optional: send email or push notification
    // mail("admin@buildsmart.com", "Security Alert: $alert_type", $alert_details);
}