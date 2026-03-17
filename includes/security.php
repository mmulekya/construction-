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