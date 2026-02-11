<?php
session_start();
include 'db.php';

// claim_doubt.php ki shuruat mein
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    // Current URL ko session mein save karlo taaki login ke baad wapas yahan aa sakein
    $_SESSION['redirect_to'] = "claim_doubt.php?id=" . $_GET['id'];
    header("Location: login.html");
    exit();
}
// URL se ID lena
if (!isset($_GET['id'])) {
    die("Error: Doubt ID missing.");
}

$doubt_id = mysqli_real_escape_string($conn, $_GET['id']);
$mentor_id = $_SESSION['user_id'];

// Check status (Column name doubt_id use kiya hai)
$check = mysqli_query($conn, "SELECT status FROM doubts WHERE doubt_id = '$doubt_id'");
$row = mysqli_fetch_assoc($check);

if ($row && $row['status'] == 'Pending') {
    // Update Database
    $update = "UPDATE doubts SET status = 'Accepted', mentor_id = '$mentor_id' WHERE doubt_id = '$doubt_id'";
    
    if (mysqli_query($conn, $update)) {
        // Node.js ko signal bhejna
        $notify_node = "http://localhost:3000/notify-claim/" . $doubt_id;
        @file_get_contents($notify_node); 
        
        header("Location: chat_ui.php?id=" . $doubt_id);
        exit();
    }
} else {
    // claim_doubt.php mein
if ($row && $row['status'] == 'Pending') {
    // ... (aapka update logic) ...
} else {
    echo "<div style='text-align:center; margin-top:50px;'>
            <h2>Oops! Too Late.</h2>
            <p>This doubt has already been claimed by another expert or is no longer available.</p>
            <a href='teacher_dashboard.php'>Go back to Dashboard</a>
          </div>";
    exit();
}
}
?>