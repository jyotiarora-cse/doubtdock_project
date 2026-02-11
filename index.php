<?php
// 1. Errors dekhne ke liye settings
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. PHPMailer files ko include karna
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // 3. Gmail SMTP Configuration
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'doubtdock.system@gmail.com'; // Aapka Gmail
    $mail->Password = 'pnln jjit xtwq movz';       // Yahan apna 16-digit APP PASSWORD likhein (Bina spaces ke bhi likh sakte hain)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
    $mail->Port       = 587;
   


    // 4. Sender aur Receiver details
    $mail->setFrom('doubtdock.system@gmail.com', 'Doubtdock Admin');
    $mail->addAddress('arorajyoti090@gmail.com'); // Yahan apna koi bhi doosra email test ke liye daalein

    // 5. Email Content
    $mail->SMTPDebug = 2;
    $mail->isHTML(true);
    $mail->Subject = 'Doubtdock Connection Successful!';
    $mail->Body    = '<h1>Mubarak Ho!</h1><p>Aapka PHPMailer setup kaam kar raha hai. Ab aap Doubtdock par doubts ke notifications bhej sakte hain.</p>';

    // 6. Mail Bhejna
    $mail->send();
    echo "<h2>Success: Email chala gaya! Apna Inbox check karein.</h2>";

} catch (Exception $e) {
    echo "<h2>Error: Mail nahi gaya.</h2>";
    echo "Mailer Error: {$mail->ErrorInfo}";
}
?>