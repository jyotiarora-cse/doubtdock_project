<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    $_SESSION['redirect_to'] = "claim_doubt.php?id=" . ($_GET['id'] ?? '');
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Error: Doubt ID missing.");
}

$doubt_id  = (int)$_GET['id'];
$mentor_id = (int)$_SESSION['user_id'];

// ATOMIC race-condition-safe update:
// Only one mentor can claim — WHERE status='Pending' ensures only first mentor wins
$update = "UPDATE doubts
           SET status = 'Accepted', mentor_id = $mentor_id
           WHERE doubt_id = $doubt_id AND status = 'Pending'";

mysqli_query($conn, $update);

if (mysqli_affected_rows($conn) > 0) {
    // This mentor claimed it first — notify Node.js server
    $notify_url = "http://localhost:3000/notify-claim/" . $doubt_id;
    @file_get_contents($notify_url);

    header("Location: chat_ui.php?id=" . $doubt_id);
    exit();
} else {
    // Another mentor already claimed this doubt
    echo '<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:"Segoe UI",sans-serif; background:#f0f2f5; display:flex; justify-content:center; align-items:center; height:100vh; }
        .box { background:white; padding:45px 40px; border-radius:20px; text-align:center; box-shadow:0 10px 30px rgba(0,0,0,0.1); max-width:420px; }
        .icon { font-size:3rem; margin-bottom:15px; color:#ffc107; }
        h2 { color:#333; margin-bottom:10px; }
        p  { color:#666; margin-bottom:25px; line-height:1.6; }
        a  { display:inline-block; color:#007bff; font-weight:600; text-decoration:none; border:2px solid #007bff; padding:10px 22px; border-radius:10px; transition:0.2s; }
        a:hover { background:#007bff; color:white; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <h2>Too Late!</h2>
        <p>This doubt has already been claimed by another mentor. Check the dashboard for new doubts!</p>
        <a href="teacher_dashboard.php"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>';
    exit();
}
?>

