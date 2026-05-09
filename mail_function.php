<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';
require_once __DIR__ . '/config.php';

// ── Helper: Build configured PHPMailer instance ───────────────────────────────
function _buildMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USER;
    $mail->Password   = MAIL_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = MAIL_PORT;
    $mail->setFrom(MAIL_FROM, MAIL_NAME);
    return $mail;
}

// ── FUNCTION 1: Notify mentors about a new doubt ──────────────────────────────
function notifyRelevantMentors($mentorEmails, $doubtTitle, $studentName, $subject, $doubtId) {
    $mail = _buildMailer();
    try {
        foreach ($mentorEmails as $email) {
            $mail->addBCC($email);
        }

        $claim_url = APP_URL . "/claim_doubt.php?id=" . intval($doubtId);

        $mail->isHTML(true);
        $mail->Subject = "🚀 New $subject Doubt: $doubtTitle";
        $mail->Body    = "
            <div style='font-family:Arial,sans-serif;padding:20px;border:1px solid #eee;border-radius:10px;max-width:520px;'>
                <h2 style='color:#3b5bdb;'>New Doubt Posted!</h2>
                <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
                <p><strong>Student:</strong> " . htmlspecialchars($studentName) . "</p>
                <p><strong>Topic:</strong> " . htmlspecialchars($doubtTitle) . "</p>
                <br>
                <p>Click below to claim this doubt and start helping the student immediately.</p>
                <a href='" . htmlspecialchars($claim_url) . "'
                   style='background:#2f9e44;color:white;padding:12px 25px;text-decoration:none;border-radius:8px;font-weight:bold;display:inline-block;'>
                   Claim Doubt &amp; Start Chat
                </a>
                <p style='font-size:12px;color:#888;margin-top:15px;'>
                    Note: The first mentor to click the button will get this doubt.
                </p>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error (notifyRelevantMentors): " . $mail->ErrorInfo);
        return false;
    }
}

// ── FUNCTION 2: Send password reset email ─────────────────────────────────────
function sendPasswordResetEmail($toEmail, $resetLink) {
    $mail = _buildMailer();
    try {
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = "Password Reset — DoubtDock";
        $mail->Body    = "
            <div style='font-family:Arial,sans-serif;padding:20px;border:1px solid #eee;border-radius:10px;max-width:500px;'>
                <h2 style='color:#3b5bdb;'>Reset Your Password</h2>
                <p>We received a request to reset your DoubtDock password.</p>
                <p>Click the button below to set a new password.
                   This link is valid for <strong>15 minutes</strong>.</p>
                <br>
                <a href='" . htmlspecialchars($resetLink) . "'
                   style='background:#3b5bdb;color:white;padding:12px 25px;text-decoration:none;border-radius:8px;font-weight:bold;display:inline-block;'>
                   Reset Password
                </a>
                <br><br>
                <p style='font-size:12px;color:#888;'>
                    If you did not request a password reset, please ignore this email.
                    Your password will remain unchanged.
                </p>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Password Reset Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>