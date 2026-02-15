<?php
use PHPMailer\PHPMailer\PHPMailer;

require 'vendor/autoload.php';

function sendMail($to, $subject, $body)
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'gmail-ul-tau@gmail.com';
    $mail->Password = 'parola_app';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('gmail-ul-tau@gmail.com', 'Agentie Turism');
    $mail->addAddress($to);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $body;

    return $mail->send();
}
