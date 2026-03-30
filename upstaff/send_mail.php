<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHP-Mailer-master/src/PHPMailer.php';
require 'PHP-Mailer-master/src/SMTP.php';
require 'PHP-Mailer-master/src/Exception.php';

$mail = new PHPMailer(true);

try {

    // SMTP settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'upstaff7@gmail.com'; 
    $mail->Password = 'adthzbjsnqjfhjky'; 
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Bypass SSL certificate verification (localhost/testing only)
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    // Sender
    $mail->setFrom('upstaff7@gmail.com', 'Upstaff zamboanga');

    // Receiver
$mail->addAddress('edlynkyuttt@gmail.com');

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'PHPMailer Test';
    $mail->Body = 'Hello! This email was sent from localhost using PHPMailer.';

    $mail->send();
    echo "Email sent successfully!";

} catch (Exception $e) {
    echo "Error: {$mail->ErrorInfo}";
}