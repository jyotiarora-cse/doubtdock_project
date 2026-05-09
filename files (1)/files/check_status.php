<?php
session_start();
include 'db.php';

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error']);
    exit;
}

$id  = (int)$_GET['id'];
$sql = "SELECT status, mentor_id FROM doubts WHERE doubt_id = $id";
$result = mysqli_query($conn, $sql);
$row    = mysqli_fetch_assoc($result);

if (!$row) {
    echo json_encode(['status' => 'error']);
    exit;
}

// ✅ FIX: claim_doubt.php status 'Accepted' set karta hai — 'Claimed' nahi
// waiting_room.php ko 'ready' milega jab mentor ne claim kiya
if ($row['status'] === 'Accepted') {
    echo json_encode(['status' => 'ready']);
} else {
    echo json_encode(['status' => 'waiting']);
}
?>
