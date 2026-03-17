<?php
require_once "../includes/config.php";
require_once "../includes/database.php";

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password_hash FROM admins WHERE username=? LIMIT 1");
    $stmt->bind_param("s",$username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1){
        $admin = $result->fetch_assoc();
        if(password_verify($password, $admin['password_hash'])){
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
$login_success = false; // after verifying password

if(!$login_success){
    trigger_alert($conn,"Failed_Login","Username: $username, possible brute-force");
}
            header("Location: dashboard.php");
            exit;
        }
    }

    $error = "Invalid username or password";
}