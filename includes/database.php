<?php

$host="localhost";
$user="buildsmart_user";
$pass="StrongPassword";
$db="buildsmart_db";

$conn = new mysqli($host,$user,$pass,$db);

if($conn->connect_error){
   die("Database connection failed");
}

?>