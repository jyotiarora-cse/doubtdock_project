<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

// ── Auth check — must be logged in ───────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$doubt_id = isset($_POST['doubt_id']) ? intval($_POST['doubt_id']) : 0;
$action   = $_POST['action'] ?? '';
$user_id  = intval($_SESSION['user_id']);
$role     = $_SESSION['role'] ?? '';

if (!$doubt_id) {
    echo json_encode(['success' => false, 'message' => 'Missing Doubt ID.']);
    exit;
}

// ── Verify that this user is authorized for this doubt ────────────────────────
$check = $conn->prepare("SELECT student_id, mentor_id, status FROM doubts WHERE doubt_id = ?");
$check->bind_param("i", $doubt_id);
$check->execute();
$doubt = $check->get_result()->fetch_assoc();
$check->close();

if (!$doubt) {
    echo json_encode(['success' => false, 'message' => 'Doubt not found.']);
    exit;
}

// Only the assigned mentor or the owning student can interact with this doubt
$isMentor  = ($role === 'mentor'  && intval($doubt['mentor_id'])  === $user_id);
$isStudent = ($role === 'student' && intval($doubt['student_id']) === $user_id);

if (!$isMentor && !$isStudent) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

// ── Handle actions ────────────────────────────────────────────────────────────
if ($action === 'rate_and_solve' && $isStudent) {
    // Student submits rating → mark as Solved
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 5;
    $rating = max(1, min(5, $rating)); // clamp 1–5

    $stmt = $conn->prepare("UPDATE doubts SET status = 'Solved', rating = ? WHERE doubt_id = ? AND student_id = ?");
    $stmt->bind_param("iii", $rating, $doubt_id, $user_id);

} elseif ($action === 'close_only' && $isMentor) {
    // Mentor ends the session → mark as Closed
    $stmt = $conn->prepare("UPDATE doubts SET status = 'Closed' WHERE doubt_id = ? AND mentor_id = ?");
    $stmt->bind_param("ii", $doubt_id, $user_id);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action or insufficient permissions.']);
    exit;
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
$stmt->close();
?>