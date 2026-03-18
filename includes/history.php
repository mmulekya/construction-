<?php

function save_chat($conn, $user_id, $question, $answer){
    $stmt = $conn->prepare("
        INSERT INTO chat_history (user_id, question, answer)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iss", $user_id, $question, $answer);
    $stmt->execute();
}

function get_user_history($conn, $user_id){
    $stmt = $conn->prepare("
        SELECT question, answer, created_at 
        FROM chat_history 
        WHERE user_id=? 
        ORDER BY id DESC 
        LIMIT 20
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    return $stmt->get_result();
}