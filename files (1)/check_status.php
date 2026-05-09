<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing doubt ID']);
    exit;
}

$id     = (int)$_GET['id'];
$sql    = "SELECT status, mentor_id FROM doubts WHERE doubt_id = $id";
$result = mysqli_query($conn, $sql);
$row    = mysqli_fetch_assoc($result);

if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Doubt not found']);
    exit;
}

// FIX: claim_doubt.php sets status = 'Accepted' — NOT 'Claimed'
// Waiting room polls this endpoint and redirects when status is 'Accepted'
if ($row['status'] === 'Accepted') {
    echo json_encode(['status' => 'ready']);
} else {
    echo json_encode(['status' => 'waiting']);
}
?>
