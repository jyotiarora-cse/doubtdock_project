<?php
session_start();
include 'db.php';
require_once 'config.php';

// Security: Check if mentor is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mentor') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mentor_id = $_SESSION['user_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $branch = mysqli_real_escape_string($conn, $_POST['branch']);

    // File Details
    $file_name = $_FILES['resource_file']['name'];
    $file_tmp = $_FILES['resource_file']['tmp_name'];
    $file_size = $_FILES['resource_file']['size'];
    $file_error = $_FILES['resource_file']['error'];

    // 1. File Extension Check (Sirf PDF allow karein)
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed = array('pdf');

    if (in_array($file_ext, $allowed)) {
        if ($file_error === 0) {
            // 2. File Size Check (Max 10MB)
            if ($file_size <= 10485760) {
                
                // 3. Unique Name generate karein (Overwriting se bachne ke liye)
                $file_new_name = "RES_" . time() . "_" . uniqid('', true) . "." . $file_ext;
                $file_destination = 'uploads/resources/' . $file_new_name;

                // 4. File ko folder mein move karein
                if (move_uploaded_file($file_tmp, $file_destination)) {

                    // 5. Insert into database using prepared statement
                    $stmt = $conn->prepare(
                        "INSERT INTO resources (mentor_id, title, subject, branch, file_path, file_type)
                         VALUES (?, ?, ?, ?, ?, 'pdf')"
                    );
                    $stmt->bind_param("issss", $mentor_id, $title, $subject, $branch, $file_destination);

                    if ($stmt->execute()) {
                        $stmt->close();
                        $_SESSION['resource_success'] = "Resource published successfully!";
                        header("Location: teacher_dashboard.php");
                        exit();
                    } else {
                        $stmt->close();
                        echo "<p style='color:red;font-family:sans-serif;'>Database error: " . htmlspecialchars($conn->error) . "</p>";
                    }

                } else {
                    echo "<p style='color:red;font-family:sans-serif;'>Error: Could not save file. Please check folder permissions.</p>";
                }
            } else {
                echo "<p style='color:red;font-family:sans-serif;'>Error: File is too large. Maximum allowed size is 10 MB.</p>";
            }
        } else {
            echo "<p style='color:red;font-family:sans-serif;'>Error: A technical error occurred during upload. Please try again.</p>";
        }
    } else {
        echo "<p style='color:red;font-family:sans-serif;'>Error: Only PDF files are allowed.</p>";
    }
}
?>
