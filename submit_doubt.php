<?php
session_start();
include 'db.php';
require_once 'mail_function.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_SESSION['user_id'];
    $student_name = $_SESSION['user_name']; 
    
    // Yahan hum subject ko trim aur normalize kar rahe hain taaki extra space ya caps ki galti na ho
    $subject = mysqli_real_escape_string($conn, trim($_POST['subject']));
    $topic = mysqli_real_escape_string($conn, $_POST['topic']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    // 1. Doubt Database mein save karein
    $sql = "INSERT INTO doubts (student_id, subject, topic, description, status) 
            VALUES ('$student_id', '$subject', '$topic', '$description', 'Pending')";

    if (mysqli_query($conn, $sql)) {
        $last_id = mysqli_insert_id($conn); 

        // 2. Mentors find karein (Yahan column name 'subject_experties' hi rakha hai)
        $mentorSql = "SELECT email FROM users WHERE role = 'mentor' AND subject_experties = '$subject'";
        $res = mysqli_query($conn, $mentorSql);
        
        $mentorEmails = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $mentorEmails[] = $row['email'];
        }

        // 3. Email trigger
        if (!empty($mentorEmails)) {
            notifyRelevantMentors($mentorEmails, $topic, $student_name, $subject, $last_id);
        }

        // 4. Redirect to waiting room
        header("Location: waiting_room.php?id=" . $last_id);
        exit(); 

    } else {
        die("Database Error: " . mysqli_error($conn));
    }
}
?>