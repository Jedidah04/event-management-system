<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 3;
    $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'jedidahpam04@gmail.com';      // Your Gmail address
    $mail->Password = 'ludmeyodajrznhbh';         // Your 16-char app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('your-email@gmail.com', 'Test Mail');
    $mail->addAddress('your-email@gmail.com');

    $mail->isHTML(true);
    $mail->Subject = 'Test SMTP Auth';
    $mail->Body    = 'If you get this email, SMTP auth works!';

    $mail->send();
    echo 'Message sent!';
} catch (Exception $e) {
    echo "Mailer Error: " . $mail->ErrorInfo;
}
