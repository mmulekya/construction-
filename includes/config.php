<?php

// Start secure session
session_start();

// Security headers
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

// Regenerate session ID
if(!isset($_SESSION['initiated'])){
session_regenerate_id();
$_SESSION['initiated'] = true;
}

// Timezone
date_default_timezone_set("UTC");

?>