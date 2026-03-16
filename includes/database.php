<?php

require_once "config.php";

$host = "localhost";
$user = "buildsmart_user";
$pass = "StrongPassword123!";
$db   = "buildsmart_db";

$conn = new mysqli($host,$user,$pass,$db);

if($conn->connect_error){
error_log("Database connection failed");
die("System error");
}

$conn->set_charset("utf8mb4");

?>