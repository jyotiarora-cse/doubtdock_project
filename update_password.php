<?php
include('db.php');
$msg = ""; $type = ""; $show_form = false;

if (isset($_GET['token']) && isset($_GET['email'])) {
    $token = mysqli_real_escape_string($conn, $_GET['token']);
    $email = mysqli_real_escape_string($conn, $_GET['email']);

    // Database mein token verify karna
    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' AND reset_token='$token' AND token_expiry > NOW()");
    
    if (mysqli_num_rows($check) > 0) {
        $show_form = true;

        if (isset($_POST['update_btn'])) {
            $p1 = $_POST['pass'];
            $p2 = $_POST['cpass'];

            if ($p1 === $p2) {
                // 1. Password ko sahi se HASH karein ($p1 ko use karke)
                $hashed_password = password_hash($p1, PASSWORD_DEFAULT);
                
                // 2. Query mein $hashed_password use karein ($p1 ki jagah)
                $sql = "UPDATE users SET password='$hashed_password', reset_token=NULL, token_expiry=NULL WHERE email='$email'";
                
                if (mysqli_query($conn, $sql)) {
                    $msg = "Password updated and hashed! <a href='login.html'>Login Now</a>";
                    $type = "success";
                    $show_form = false;
                } else {
                    $msg = "Database Error: " . mysqli_error($conn);
                    $type = "error";
                }
            } else {
                $msg = "Passwords do not match!";
                $type = "error";
            }
        }
    } else {
        $msg = "Invalid or Expired Link!";
        $type = "error";
    }
}
?>

?>
<!DOCTYPE html>
<html>
<head>
    <title>Set New Password</title>
    <style>
        /* Same CSS as above */
        body { font-family: sans-serif; background: #f0f6ff; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .box { background: white; padding: 30px; border-radius: 15px; width: 350px; text-align: center; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; box-sizing: border-box; }
        .btn { background: #28a745; color: white; border: none; padding: 10px; width: 100%; border-radius: 5px; cursor: pointer; }
        .success { color: green; } .error { color: red; }
    </style>
</head>
<body>
    <div class="box">
        <h2>New Password</h2>
        <?php if($msg) echo "<p class='$type'>$msg</p>"; ?>
        
        <?php if($show_form): ?>
        <form method="POST">
            <input type="password" name="pass" placeholder="New Password" required minlength="5">
            <input type="password" name="cpass" placeholder="Confirm Password" required>
            <button type="submit" name="update_btn" class="btn">Update Password</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>