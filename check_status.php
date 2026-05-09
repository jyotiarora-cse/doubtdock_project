<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error']);
    exit;
}

$id  = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT status, mentor_id FROM doubts WHERE doubt_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row    = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['status' => 'error']);
    exit;
}

if ($row['status'] === 'Accepted' || $row['status'] === 'Claimed') {
    echo json_encode(['status' => 'ready']);
} else {
    echo json_encode(['status' => 'waiting']);
}
?>
