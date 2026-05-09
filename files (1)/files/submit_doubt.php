<?php
session_start();
include 'db.php';
require_once 'mail_function.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id   = $_SESSION['user_id'];
    $student_name = $_SESSION['user_name'];
    $subject      = mysqli_real_escape_string($conn, trim($_POST['subject']));
    $topic        = mysqli_real_escape_string($conn, trim($_POST['topic']));
    $description  = mysqli_real_escape_string($conn, trim($_POST['description']));

    // 1. Doubt DB mein save karo
    $sql = "INSERT INTO doubts (student_id, subject, topic, description, status)
            VALUES ('$student_id', '$subject', '$topic', '$description', 'Pending')";

    if (mysqli_query($conn, $sql)) {
        $last_id = mysqli_insert_id($conn);

        // 2. ✅ FIX: FIND_IN_SET use karo — mentor ke comma-separated subjects mein match karo
        // subject_experties = "DBMS,Python,OS" — FIND_IN_SET('DBMS', subject_experties) = 1
        $mentorSql = "SELECT email FROM users 
                      WHERE role = 'mentor' 
                      AND FIND_IN_SET('$subject', subject_experties) > 0";
        $res = mysqli_query($conn, $mentorSql);

        $mentorEmails = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $mentorEmails[] = $row['email'];
        }

        // 3. Email bhejo
        if (!empty($mentorEmails)) {
            notifyRelevantMentors($mentorEmails, $topic, $student_name, $subject, $last_id);
        }

        // 4. Waiting room par redirect
        header("Location: waiting_room.php?id=" . $last_id);
        exit();

    } else {
        die("Database Error: " . mysqli_error($conn));
    }
}
?>
