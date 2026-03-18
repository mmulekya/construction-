<?php
$DB_HOST = "sqlXXX.infinityfree.com";
$DB_USER = "your_db_user";
$DB_PASS = "your_db_password";
$DB_NAME = "your_db_name";

$conn = new mysqli($DB_HOST,$DB_USER,$DB_PASS,$DB_NAME);
if($conn->connect_error) die("DB Error");

session_start();