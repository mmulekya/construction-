<?php

require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/security.php";

header("Content-Type: application/json");

session_start();

$action = $_GET['action'] ?? '';

switch($action){

    case 'login':
        require "login.php";
        break;

    case 'register':
        require "register.php";
        break;

    case 'ask_ai':
        require "ask_ai.php";
        break;

    case 'history':
        require "history.php";
        break;

    default:
        echo json_encode(["error"=>"Invalid API action"]);
}