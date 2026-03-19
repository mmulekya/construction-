<?php

function save_memory($conn, $user_id, $type, $content){
    $stmt = $conn->prepare("INSERT INTO user_memory (user_id, type, content) VALUES (?,?,?)");
    $stmt->bind_param("iss", $user_id, $type, $content);
    $stmt->execute();
}

function get_memory($conn, $user_id, $limit = 10){
    $stmt = $conn->prepare("SELECT type, content FROM user_memory WHERE user_id=? ORDER BY id DESC LIMIT ?");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();

    $result = $stmt->get_result();
    $memory = [];

    while($row = $result->fetch_assoc()){
        $memory[] = $row;
    }

    return $memory;
}

function summarize_memory($memory){
    $summary = "";

    foreach($memory as $m){
        $summary .= "[".$m['type']."] ".$m['content']."\n";
    }

    return $summary;
}