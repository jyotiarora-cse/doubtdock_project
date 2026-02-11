<?php
include('db.php');
include('mail_function.php');

$msg = ""; $type = "";

if (isset($_POST['forgot_submit'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $res = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");

    if (mysqli_num_rows($res) > 0) {
        // 1. Token aur Expiry banana
        $token = bin2hex(random_bytes(16));
        $expiry = date("Y-m-d H:i:s", strtotime("+24 hours")); // Testing ke liye long rakha hai

        // 2. Database update karna
        mysqli_query($conn, "UPDATE users SET reset_token='$token', token_expiry='$expiry' WHERE email='$email'");

        // 3. Dynamic Link banana (Space handle karne ke liye)
        $dir = dirname($_SERVER['PHP_SELF']);
        $link = "http://" . $_SERVER['HTTP_HOST'] . $dir . "/update_password.php?token=$token&email=$email";

        // 4. Email bhejna
        $subject = "Password Reset - DoubtDock";
        $body = "<h3>Update Password</h3>
                 <p>Niche diye link par click karke naya password banayein:</p>
                 <a href='$link' style='padding:10px 20px; background:#0084ff; color:#fff; text-decoration:none; border-radius:5px;'>Reset Password</a>";

        if (notifyRelevantMentors($email, $subject, $body)) {
            $msg = "Reset link sent! Please check your email.";
            $type = "success";
        }
    } else {
        $msg = "Email not found!";
        $type = "error";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <style>
        body { font-family: sans-serif; background: #f0f6ff; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .box { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); width: 350px; text-align: center; }
        input { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; box-sizing: border-box; }
        .btn { background: #0084ff; color: white; border: none; padding: 10px; width: 100%; border-radius: 5px; cursor: pointer; }
        .success { color: green; } .error { color: red; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Forgot Password</h2>
        <p style="font-size: 14px; color: #666;">Enter your registered email</p>
        <?php if($msg) echo "<p class='$type'>$msg</p>"; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email Address" required>
            <button type="submit" name="forgot_submit" class="btn">Send Reset Link</button>
        </form>
    </div>
</body>
</html>