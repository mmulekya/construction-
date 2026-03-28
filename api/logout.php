<?php
require_once "../includes/config.php";
header("Content-Type: application/json");
session_start();

// Destroy session safely
$_SESSION = [];
session_destroy();

echo json_encode(["success"=>true,"message"=>"Logged out"]);