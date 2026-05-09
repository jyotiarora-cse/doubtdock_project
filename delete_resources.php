<?php
session_start();
include 'db.php';
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mentor') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $resource_id = (int)$_GET['id'];
    $mentor_id = $_SESSION['user_id'];

    // 1. Fetch file path and verify ownership
    $stmt = $conn->prepare("SELECT file_path FROM resources WHERE resource_id = ? AND mentor_id = ?");
    $stmt->bind_param("ii", $resource_id, $mentor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    if ($data) {
        $file_to_delete = $data['file_path'];

        // 2. Delete file from storage
        if (file_exists($file_to_delete)) {
            unlink($file_to_delete);
        }

        // 3. Delete from database
        $del = $conn->prepare("DELETE FROM resources WHERE resource_id = ? AND mentor_id = ?");
        $del->bind_param("ii", $resource_id, $mentor_id);
        if ($del->execute()) {
            $_SESSION['resource_success'] = "Resource deleted successfully.";
        }
        $del->close();
    }
}

header("Location: manage_resources.php");
exit();
?>