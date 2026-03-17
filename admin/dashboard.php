<?php
require_once "../includes/config.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}

// Dashboard functions:
// 1. Add / Edit Knowledge
// 2. Add / Edit Calculators
// 3. View Logs