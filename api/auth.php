<?php
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

if($_SERVER['REQUEST_METHOD']=='POST'){
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id,password_hash,role FROM users WHERE username=?");
    $stmt->bind_param("s",$username);
    $stmt->execute();
    $stmt->store_result();
    if($stmt->num_rows==1){
        $stmt->bind_result($id,$hash,$role);
        $stmt->fetch();
        if(password_verify($password,$hash)){
            $_SESSION['user_id']=$id;
            $_SESSION['role']=$role;
            json_response(["success"=>true,"role"=>$role]);
        } else { json_response(["error"=>"Invalid credentials"]); }
    } else { json_response(["error"=>"User not found"]); }
}