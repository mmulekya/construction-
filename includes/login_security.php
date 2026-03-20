<?php

function check_login_lock($conn, $email){

    $ip = $_SERVER['REMOTE_ADDR'];

    $stmt = $conn->prepare("SELECT attempts, locked_until FROM login_attempts WHERE email=? AND ip_address=?");
    $stmt->bind_param("ss", $email, $ip);
    $stmt->execute();
    $res = $stmt->get_result();

    if($row = $res->fetch_assoc()){
        if($row['locked_until'] && strtotime($row['locked_until']) > time()){
            http_response_code(403);
            echo json_encode(["error"=>"Account temporarily locked. Try later."]);
            exit;
        }
    }
}

function record_failed_login($conn, $email){

    $ip = $_SERVER['REMOTE_ADDR'];

    $stmt = $conn->prepare("SELECT id, attempts FROM login_attempts WHERE email=? AND ip_address=?");
    $stmt->bind_param("ss", $email, $ip);
    $stmt->execute();
    $res = $stmt->get_result();

    if($row = $res->fetch_assoc()){

        $attempts = $row['attempts'] + 1;

        if($attempts >= 5){
            $lock_time = date("Y-m-d H:i:s", strtotime("+10 minutes"));

            $stmt = $conn->prepare("UPDATE login_attempts SET attempts=?, locked_until=? WHERE id=?");
            $stmt->bind_param("isi", $attempts, $lock_time, $row['id']);
        } else {
            $stmt = $conn->prepare("UPDATE login_attempts SET attempts=? WHERE id=?");
            $stmt->bind_param("ii", $attempts, $row['id']);
        }

        $stmt->execute();

    } else {
        $stmt = $conn->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?,?)");
        $stmt->bind_param("ss", $email, $ip);
        $stmt->execute();
    }
}

function reset_login_attempts($conn, $email){

    $ip = $_SERVER['REMOTE_ADDR'];

    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE email=? AND ip_address=?");
    $stmt->bind_param("ss", $email, $ip);
    $stmt->execute();
}