<?php

function save_chat_history($conn, $user_id, $message, $response){
    $stmt = $conn->prepare("
        INSERT INTO chat_history (user_id, message, response, created_at)
        VALUES (?, ?, ?, NOW())
    ");

    $stmt->bind_param("iss", $user_id, $message, $response);
    $stmt->execute();
}

function get_chat_history($conn, $user_id){
    $stmt = $conn->prepare("
        SELECT * FROM chat_history WHERE user_id=? ORDER BY created_at DESC
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    return $stmt->get_result();
}