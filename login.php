<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Session Variables Set Karna
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['mentor_expertise'] = $user['mentor_experties']; // Expertise yahan set ho rahi hai

        // Redirect based on role
        if ($user['role'] === 'student') {
            header("Location: ask_doubt.php");
            exit;
        } elseif ($user['role'] === 'mentor') {
            header("Location: teacher_dashboard.php");
            exit;
        } else {
            echo "<p style='color:red;'>Unknown role. Contact admin.</p>";
        }
    } else {
        echo "<p style='color:red;'>Invalid email or password.</p>";
    }
    $stmt->close();
}
$conn->close();
?>