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

$doubt_id  = (int)$_GET['id']; // ✅ Integer cast — injection safe
$mentor_id = (int)$_SESSION['user_id'];

// Race condition safe — atomic check+update
// Sirf tab update karo jab status Pending ho, aur usi ek mentor ko mile
$update = "UPDATE doubts 
           SET status = 'Accepted', mentor_id = $mentor_id 
           WHERE doubt_id = $doubt_id AND status = 'Pending'";

mysqli_query($conn, $update);

if (mysqli_affected_rows($conn) > 0) {
    // ✅ Ye mentor pehle claim kar paya — Node.js ko signal bhejo
    $notify_url = "http://localhost:3000/notify-claim/" . $doubt_id;
    @file_get_contents($notify_url);

    header("Location: chat_ui.php?id=" . $doubt_id);
    exit();
} else {
    // Koi aur mentor pehle le gaya
    echo "<!DOCTYPE html><html><head>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    <style>body{font-family:'Segoe UI',sans-serif;background:#f0f2f5;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}
    .box{background:white;padding:40px;border-radius:20px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.1);max-width:400px;}
    .icon{font-size:3rem;margin-bottom:15px;color:#ffc107;}
    h2{color:#333;margin-bottom:10px;} p{color:#666;} a{color:#007bff;font-weight:600;text-decoration:none;}</style>
    </head><body>
    <div class='box'>
      <div class='icon'><i class='fa-solid fa-clock'></i></div>
      <h2>Too Late!</h2>
      <p>Yeh doubt kisi aur mentor ne pehle claim kar liya. Dashboard pe naye doubts dekhte hain!</p>
      <br><a href='teacher_dashboard.php'><i class='fa-solid fa-arrow-left'></i> Dashboard par wapas jao</a>
    </div>
    </body></html>";
    exit();
}
?>

