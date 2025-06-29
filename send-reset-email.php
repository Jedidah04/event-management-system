<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Composer autoload

function sendPasswordResetEmail($toEmail, $resetLink) {
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';           // Use your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; // Your SMTP username
        $mail->Password = 'your-email-password';  // Your SMTP password or app password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        //Recipients
        $mail->setFrom('your-email@gmail.com', 'Event Management');
        $mail->addAddress($toEmail);

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body = "Hi,<br><br>Click the link below to reset your password:<br><a href=\"$resetLink\">$resetLink</a><br><br>If you did not request this, ignore this email.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
