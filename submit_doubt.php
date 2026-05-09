<?php
session_start();
include 'db.php';
require_once 'config.php';
require_once 'mail_function.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: student_dashboard.php");
    exit();
}

$student_id   = intval($_SESSION['user_id']);
$student_name = $_SESSION['user_name'] ?? 'Student';
$subject      = trim($_POST['subject']     ?? '');
$topic        = trim($_POST['topic']       ?? '');
$description  = trim($_POST['description'] ?? '');

// Basic server-side validation
if (empty($subject) || empty($topic) || empty($description)) {
    $_SESSION['doubt_error'] = "All fields are required.";
    header("Location: ask_doubt.php");
    exit();
}

// ── Image Upload ──────────────────────────────────────────────────────────────
$image_path = NULL;

if (isset($_FILES['doubt_image']) && $_FILES['doubt_image']['error'] === UPLOAD_ERR_OK) {
    $file      = $_FILES['doubt_image'];
    $file_tmp  = $file['tmp_name'];
    $file_size = $file['size'];
    $file_ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $file_mime = mime_content_type($file_tmp);

    $allowed_ext  = ['jpg', 'jpeg', 'png'];
    $allowed_mime = ['image/jpeg', 'image/png'];

    if (!in_array($file_ext, $allowed_ext) || !in_array($file_mime, $allowed_mime)) {
        $_SESSION['doubt_error'] = "Invalid file type. Only JPG, JPEG, PNG allowed.";
        header("Location: ask_doubt.php");
        exit();
    }

    if ($file_size > 5 * 1024 * 1024) {
        $_SESSION['doubt_error'] = "Image size must be less than 5 MB.";
        header("Location: ask_doubt.php");
        exit();
    }

    $upload_dir    = 'uploads/doubt_images/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $new_file_name = 'doubt_' . $student_id . '_' . time() . '.' . $file_ext;
    $destination   = $upload_dir . $new_file_name;

    if (!move_uploaded_file($file_tmp, $destination)) {
        $_SESSION['doubt_error'] = "Failed to upload image. Please try again.";
        header("Location: ask_doubt.php");
        exit();
    }
    $image_path = $destination;
}

// ── Insert doubt using prepared statement ─────────────────────────────────────
$stmt = $conn->prepare(
    "INSERT INTO doubts (student_id, subject, topic, description, image_path, status)
     VALUES (?, ?, ?, ?, ?, 'Pending')"
);
$stmt->bind_param("issss", $student_id, $subject, $topic, $description, $image_path);

if (!$stmt->execute()) {
    $stmt->close();
    $_SESSION['doubt_error'] = "Database error. Please try again.";
    header("Location: ask_doubt.php");
    exit();
}

$last_id = $conn->insert_id;
$stmt->close();

// ── Notify ONLINE mentors via Socket.io (cURL with timeout) ──────────────────
$ch = curl_init(NODE_URL . "/notify-new-doubt/" . $last_id);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 3);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
$result = curl_exec($ch);
if (curl_errno($ch)) {
    error_log("Socket notify failed (submit_doubt): " . curl_error($ch));
}
curl_close($ch);

// ── Email OFFLINE mentors ─────────────────────────────────────────────────────
$mStmt = $conn->prepare(
    "SELECT email FROM users
     WHERE role = 'mentor' AND FIND_IN_SET(?, subject_experties) > 0"
);
$mStmt->bind_param("s", $subject);
$mStmt->execute();
$mResult = $mStmt->get_result();

$mentorEmails = [];
while ($row = $mResult->fetch_assoc()) {
    $mentorEmails[] = $row['email'];
}
$mStmt->close();

if (!empty($mentorEmails)) {
    notifyRelevantMentors($mentorEmails, $topic, $student_name, $subject, $last_id);
}

// ── Redirect student to waiting room ──────────────────────────────────────────
header("Location: waiting_room.php?id=" . $last_id);
exit();
?>
