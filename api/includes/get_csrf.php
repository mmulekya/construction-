<?php

require_once "config.php";
require_once "security.php";

session_start();

header("Content-Type: application/json");

echo json_encode([
    "token" => generate_csrf_token()
]);