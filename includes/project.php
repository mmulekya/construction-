<?php

function create_project($conn, $user_id, $name, $description){
    $stmt = $conn->prepare("INSERT INTO projects (user_id, name, description) VALUES (?,?,?)");
    $stmt->bind_param("iss", $user_id, $name, $description);
    $stmt->execute();
    return $conn->insert_id;
}

function get_user_projects($conn, $user_id){
    $stmt = $conn->prepare("SELECT * FROM projects WHERE user_id=? ORDER BY id DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

function add_project_data($conn, $project_id, $type, $content){
    $stmt = $conn->prepare("INSERT INTO project_data (project_id, type, content) VALUES (?,?,?)");
    $stmt->bind_param("iss", $project_id, $type, $content);
    $stmt->execute();
}

function get_project_data($conn, $project_id){
    $stmt = $conn->prepare("SELECT * FROM project_data WHERE project_id=? ORDER BY id DESC");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    return $stmt->get_result();
}