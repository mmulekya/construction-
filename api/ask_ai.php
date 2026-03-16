<?php

session_start();

include("../includes/database.php");

$question = htmlspecialchars(trim($_POST['question']));

$user_id = $_SESSION['user_id'];

$ip = $_SERVER['REMOTE_ADDR'];


// Send to AI model
$ai_reply = "Example AI answer about construction.";


// Save conversation
$stmt = $conn->prepare(
"INSERT INTO messages (user_id,question,answer,ip_address)
VALUES (?,?,?,?)"
);

$stmt->bind_param("isss",$user_id,$question,$ai_reply,$ip);
$stmt->execute();


echo json_encode([
"reply"=>$ai_reply
]);

?>