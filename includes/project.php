<?php

function create_project($conn, $user_id, $name, $description){
    $stmt = $conn->prepare("
        INSERT INTO projects (user_id, name, description, created_at)
        VALUES (?, ?, ?, NOW())
    ");

    $stmt->bind_param("iss", $user_id, $name, $description);
    $stmt->execute();
}

function get_projects($conn, $user_id){
    $stmt = $conn->prepare("
        SELECT * FROM projects WHERE user_id=?
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    return $stmt->get_result();
}