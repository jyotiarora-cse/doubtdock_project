<?php
include('db.php');
include('mail_function.php');

$msg = ""; $type = "";

if (isset($_POST['forgot_submit'])) {
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // 1. Secure token generate karo
        $token = bin2hex(random_bytes(32));

        // 2. Token DB mein save karo — MySQL NOW() use karo (timezone safe)
        $upd = $conn->prepare("UPDATE users SET reset_token = ?, token_expiry = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE email = ?");
        $upd->bind_param("ss", $token, $email);
        $upd->execute();
        $upd->close();

        // 3. Reset link — sirf token, email nahi
        $link = APP_URL . "/update_password.php?token=" . urlencode($token);

        // 4. Email bhejo
        sendPasswordResetEmail($email, $link);
    }

    // Always same message — security best practice
    $msg  = "If this email is registered, a reset link has been sent. Please check your inbox. Link expires in 15 minutes.";
    $type = "success";

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | DoubtDock</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea22, #764ba222), #f0f4f8;
            display: flex; justify-content: center; align-items: center; height: 100vh;
        }
        .box {
            background: white; padding: 40px 35px; border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.12); width: 380px; text-align: center;
        }
        .icon { font-size: 2.5rem; margin-bottom: 12px; }
        h2 { color: #1a1a2e; margin-bottom: 6px; font-size: 1.5rem; }
        .sub { font-size: 13px; color: #888; margin-bottom: 24px; line-height: 1.5; }
        input {
            width: 100%; padding: 12px 15px; margin: 8px 0;
            border-radius: 10px; border: 2px solid #e8ecf0;
            font-size: 14px; outline: none; transition: 0.2s;
        }
        input:focus { border-color: #0084ff; box-shadow: 0 0 0 3px rgba(0,132,255,0.1); }
        .btn {
            background: #0084ff; color: white; border: none;
            padding: 13px; width: 100%; border-radius: 10px;
            cursor: pointer; font-size: 15px; font-weight: 600;
            margin-top: 8px; transition: 0.2s;
        }
        .btn:hover { background: #006ecc; transform: translateY(-1px); }
        .success {
            background: #f0fff4; border: 1px solid #c6f6d5;
            color: #276749; padding: 12px 14px; border-radius: 10px;
            font-size: 13px; margin-bottom: 16px; line-height: 1.6; text-align: left;
        }
        .error {
            background: #fff5f5; border: 1px solid #fed7d7;
            color: #c53030; padding: 12px 14px; border-radius: 10px;
            font-size: 13px; margin-bottom: 16px;
        }
        .expiry-note { font-size: 11px; color: #aaa; margin-top: 14px; }
        .expiry-note span { color: #e53e3e; font-weight: 600; }
        .back-link { margin-top: 20px; font-size: 13px; }
        .back-link a { color: #0084ff; text-decoration: none; font-weight: 600; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">🔐</div>
        <h2>Forgot Password?</h2>
        <p class="sub">Enter your registered email and we'll send you a secure reset link.</p>

        <?php if ($msg): ?>
            <div class="<?= $type ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if ($type !== 'success'): ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email Address" required autofocus>
            <button type="submit" name="forgot_submit" class="btn">Send Reset Link</button>
        </form>
        <p class="expiry-note">Reset link expires in <span>15 minutes</span></p>
        <?php endif; ?>

        <div class="back-link">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>
