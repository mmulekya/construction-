<?php

require_once "../includes/config.php";

header("Content-Type: application/json");

session_start();

// Destroy session completely
$_SESSION = [];
session_destroy();

echo json_encode(["success"=>true]);