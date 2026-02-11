<?php
include 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if variables exist to avoid PHP Undefined Index notices
    $doubt_id = isset($_POST['doubt_id']) ? mysqli_real_escape_string($conn, $_POST['doubt_id']) : null;
    $rating = isset($_POST['rating']) ? mysqli_real_escape_string($conn, $_POST['rating']) : null;
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if (!$doubt_id) {
        echo json_encode(['success' => false, 'message' => 'Missing Doubt ID']);
        exit;
    }

    if ($action === 'rate_and_solve') {
        // Jab STUDENT rating submit kare
        $sql = "UPDATE doubts SET status = 'Solved', rating = '$rating' WHERE doubt_id = '$doubt_id'";
    } else {
        // Jab MENTOR session end kare (Initial close)
        $sql = "UPDATE doubts SET status = 'Closed' WHERE doubt_id = '$doubt_id'";
    }

    if(mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
}
?>