<?php

function sanitize_input($data){

$data = trim($data);
$data = stripslashes($data);
$data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

return $data;

}

function generate_token(){

if(empty($_SESSION['token'])){
$_SESSION['token'] = bin2hex(random_bytes(32));
}

return $_SESSION['token'];

}

function verify_token($token){

if(isset($_SESSION['token']) && hash_equals($_SESSION['token'],$token)){
return true;
}

return false;

}

?>