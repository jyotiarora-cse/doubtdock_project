<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$doubt_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$student_id = intval($_SESSION['user_id']);

if ($doubt_id > 0) {
    // Update last_heartbeat for this doubt
    $stmt = $conn->prepare("UPDATE doubts SET last_heartbeat = NOW() WHERE doubt_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $doubt_id, $student_id);
    $stmt->execute();
    echo json_encode(['success' => true]);
}
?>
