<?php

function check_rate_limit($conn, $endpoint, $limit = 20, $seconds = 60){

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $stmt = $conn->prepare("SELECT id, requests, last_request FROM rate_limits WHERE ip_address=? AND endpoint=?");
    $stmt->bind_param("ss", $ip, $endpoint);
    $stmt->execute();
    $res = $stmt->get_result();

    if($row = $res->fetch_assoc()){

        $time_diff = time() - strtotime($row['last_request']);

        if($time_diff < $seconds){

            if($row['requests'] >= $limit){

                // 🔐 LOG ATTACK
                error_log("Rate limit exceeded: $ip on $endpoint");

                http_response_code(429);
                echo json_encode(["error"=>"Too many requests. Try again later."]);
                exit;
            }

            $stmt = $conn->prepare("UPDATE rate_limits SET requests=requests+1 WHERE id=?");
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();

        } else {

            $stmt = $conn->prepare("UPDATE rate_limits SET requests=1, last_request=NOW() WHERE id=?");
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();
        }

    } else {

        $stmt = $conn->prepare("INSERT INTO rate_limits (ip_address, endpoint) VALUES (?,?)");
        $stmt->bind_param("ss", $ip, $endpoint);
        $stmt->execute();
    }
}