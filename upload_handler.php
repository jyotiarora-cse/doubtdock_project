<?php
session_start();
header('Content-Type: application/json');

// ── Auth check — must be logged in ───────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

include 'db.php';

$user_id  = intval($_SESSION['user_id']);
$doubt_id = isset($_POST['doubt_id']) ? intval($_POST['doubt_id']) : 0;

if (!$doubt_id) {
    echo json_encode(['success' => false, 'message' => 'Missing doubt ID.']);
    exit;
}

// Verify the user is a participant in this doubt
$check = $conn->prepare("SELECT student_id, mentor_id FROM doubts WHERE doubt_id = ?");
$check->bind_param("i", $doubt_id);
$check->execute();
$doubt = $check->get_result()->fetch_assoc();
$check->close();

if (!$doubt || ($doubt['student_id'] != $user_id && $doubt['mentor_id'] != $user_id)) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'No file received.']);
    exit;
}

$file      = $_FILES['file'];
$file_name = $file['name'];
$file_tmp  = $file['tmp_name'];
$file_size = $file['size'];
$file_err  = $file['error'];

if ($file_err !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload error code: ' . $file_err]);
    exit;
}

// ── File type whitelist ───────────────────────────────────────────────────────
$allowed_ext   = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx'];
$allowed_mime  = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf',
                  'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

$ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
$mime = mime_content_type($file_tmp);

if (!in_array($ext, $allowed_ext) || !in_array($mime, $allowed_mime)) {
    echo json_encode(['success' => false, 'message' => 'File type not allowed. Only JPG, PNG, GIF, PDF, DOCX.']);
    exit;
}

// ── Size limit: 10 MB ─────────────────────────────────────────────────────────
if ($file_size > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum 10 MB.']);
    exit;
}

// ── Save to secure folder ─────────────────────────────────────────────────────
$upload_dir = 'uploads/chat_files/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$safe_name   = 'chat_' . $user_id . '_' . $doubt_id . '_' . time() . '.' . $ext;
$destination = $upload_dir . $safe_name;

if (move_uploaded_file($file_tmp, $destination)) {
    echo json_encode(['success' => true, 'fileUrl' => $destination]);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not save the file. Check folder permissions.']);
}
?>