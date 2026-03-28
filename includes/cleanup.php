<?php

function run_cleanup($conn){

    // Run only once per hour
    if(isset($_SESSION['last_cleanup'])){
        if(time() - $_SESSION['last_cleanup'] < 3600){
            return;
        }
    }

    $_SESSION['last_cleanup'] = time();

    // 🔥 Delete old login attempts (3 days)
    $conn->query("
        DELETE FROM login_attempts 
        WHERE created_at < NOW() - INTERVAL 3 DAY
    ");

    // 🔥 Delete old AI cache (7 days)
    $conn->query("
        DELETE FROM query_cache 
        WHERE created_at < NOW() - INTERVAL 7 DAY
    ");

    // 🔥 Limit chat history (keep last 50 per user)
    $conn->query("
        DELETE FROM chat_messages 
        WHERE id NOT IN (
            SELECT id FROM (
                SELECT id FROM chat_messages 
                ORDER BY id DESC LIMIT 500
            ) temp
        )
    ");

    // 🔥 Limit PDF chunks (keep recent 5000)
    $conn->query("
        DELETE FROM pdf_chunks 
        WHERE id NOT IN (
            SELECT id FROM (
                SELECT id FROM pdf_chunks 
                ORDER BY id DESC LIMIT 5000
            ) temp
        )
    );

    // 🔥 Clean AI usage (30 days)
    $conn->query("
        DELETE FROM ai_usage 
        WHERE last_request < CURDATE() - INTERVAL 30 DAY
    );
}