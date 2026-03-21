<?php

function save_memory($conn, $user_id, $key, $value){
    $stmt = $conn->prepare("
        INSERT INTO memory (user_id, memory_key, memory_value)
        VALUES (?, ?, ?)
    ");

    $stmt->bind_param("iss", $user_id, $key, $value);
    $stmt->execute();
}

function get_memory($conn, $user_id, $key){
    $stmt = $conn->prepare("
        SELECT memory_value FROM memory 
        WHERE user_id=? AND memory_key=?
    ");

    $stmt->bind_param("is", $user_id, $key);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}