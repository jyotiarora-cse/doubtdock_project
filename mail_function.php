<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php'; 

// 1. Function mein $doubtId add kijiye taaki link ban sake
function notifyRelevantMentors($mentorEmails, $doubtTitle, $studentName, $subject, $doubtId) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'doubtdock.system@gmail.com'; 
        $mail->Password   = 'pnln jjit xtwq movz'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('doubtdock.system@gmail.com', 'DoubtDock Alert');

        foreach ($mentorEmails as $email) {
            $mail->addBCC($email);
        }

        // 2. Naya Claim URL (Naye folder name 'doubtdock_project' ke saath)
        $claim_url = "http://localhost/doubtdock_project/claim_doubt.php?id=" . $doubtId;

        $mail->isHTML(true);
        $mail->Subject = "🚀 New $subject Doubt: $doubtTitle";
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                <h2 style='color: #007bff;'>New Doubt Posted!</h2>
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Student:</strong> $studentName</p>
                <p><strong>Topic:</strong> $doubtTitle</p>
                <br>
                <p>Click the button below to claim this doubt and start helping the student immediately.</p>
                <a href='$claim_url' 
                   style='background: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>
                   Claim Doubt & Start Chat
                </a>
                <p style='font-size: 12px; color: #888; margin-top: 15px;'>Note: First mentor to click the button will get the doubt.</p>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Error debugging ke liye (Optional)
        // error_log("Mail Error: " . $mail->ErrorInfo); 
        return false;
    }
}
?>