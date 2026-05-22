<?php
session_start();
include 'db.php';
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    $_SESSION['redirect_to'] = "claim_doubt.php?id=" . intval($_GET['id'] ?? 0);
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Error: Doubt ID missing.");
}

$doubt_id  = intval($_GET['id']);
$mentor_id = intval($_SESSION['user_id']);

// Atomic check+update — only update if still Pending (race condition safe)
$update = $conn->prepare(
    "UPDATE doubts SET status = 'Accepted', mentor_id = ?
     WHERE doubt_id = ? AND status = 'Pending'"
);
$update->bind_param("ii", $mentor_id, $doubt_id);
$update->execute();
$affected = $conn->affected_rows;
$update->close();

if ($affected > 0) {
    // Notify via cURL (with timeout + error logging — replaces silent @file_get_contents)
    $ch = curl_init(NODE_URL . "/notify-claim/" . $doubt_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    $res = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log("Socket notify failed (claim_doubt #$doubt_id): " . curl_error($ch));
    }
    curl_close($ch);

    header("Location: chat_ui.php?id=" . $doubt_id);
    exit();

} else {
    // Check WHY it failed
    $check = $conn->prepare("SELECT status, last_heartbeat FROM doubts WHERE doubt_id = ?");
    $check->bind_param("i", $doubt_id);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();
    $check->close();

    $errorTitle = "Too Late!";
    $errorMsg   = "Another mentor has already claimed this doubt.";
    $icon       = "fa-clock";

    // If still pending but heartbeat is old, student left
    if ($res && $res['status'] === 'Pending') {
        $errorTitle = "Student Left";
        $errorMsg   = "The student has already left the waiting room. They might have cancelled the request or lost connection.";
        $icon       = "fa-user-slash";
    }

    echo '<!DOCTYPE html><html lang="en"><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Unavailable | DoubtDock</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:"Segoe UI",sans-serif;background:#f0f2f5;display:flex;justify-content:center;align-items:center;height:100vh;}
        .box{background:white;padding:40px;border-radius:20px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.1);max-width:420px;width:90%;}
        .icon{font-size:3.5rem;margin-bottom:16px;color:#f59e0b;}
        h2{color:#333;margin-bottom:10px;font-size:1.5rem;}
        p{color:#64748b;line-height:1.6;margin-bottom:24px;font-size:0.95rem;}
        a{display:inline-block;background:#4361ee;color:white;padding:12px 30px;border-radius:12px;text-decoration:none;font-weight:600;transition:0.2s;box-shadow: 0 4px 14px rgba(67, 97, 238, 0.3);}
        a:hover{background:#3451d1;transform:translateY(-2px);}
    </style>
    </head><body>
    <div class="box">
      <div class="icon"><i class="fa-solid ' . $icon . '"></i></div>
      <h2>' . $errorTitle . '</h2>
      <p>' . $errorMsg . '</p>
      <a href="teacher_dashboard.php"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    </div>
    </body></html>';
    exit();
}
?>

