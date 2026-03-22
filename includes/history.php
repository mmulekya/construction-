<?php

/* =========================
   SAVE CHAT HISTORY (LIMITED)
========================= */
function save_chat_history($conn, $user_id, $message, $response){

    // 🔐 LIMIT: keep only last 50 messages per user
    $stmt = $conn->prepare("
        DELETE FROM chat_history 
        WHERE user_id=? 
        AND id NOT IN (
            SELECT id FROM (
                SELECT id FROM chat_history 
                WHERE user_id=? 
                ORDER BY created_at DESC 
                LIMIT 50
            ) AS temp
        )
    ");
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();

    // 🔐 INSERT NEW MESSAGE
    $stmt = $conn->prepare("
        INSERT INTO chat_history (user_id, message, response, created_at)
        VALUES (?, ?, ?, NOW())
    ");

    $stmt->bind_param("iss", $user_id, $message, $response);
    $stmt->execute();
}


/* =========================
   GET CHAT HISTORY (LIMITED)
========================= */
function get_chat_history($conn, $user_id){

    $stmt = $conn->prepare("
        SELECT message, response, created_at 
        FROM chat_history 
        WHERE user_id=? 
        ORDER BY created_at DESC 
        LIMIT 50
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    return $stmt->get_result();
}