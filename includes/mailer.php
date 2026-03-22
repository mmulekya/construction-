<?php

function send_email($to, $subject, $message){

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@yourdomain.com";

    return mail($to, $subject, $message, $headers);
}