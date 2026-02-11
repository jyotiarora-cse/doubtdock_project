<?php
session_start();
include 'db.php'; // Maan lete hain db connection yahan se aa raha hai

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $raw_password = $_POST['password'];
    $role = $_POST['role'];
    $branch = $_POST['branch'];
    $semester = isset($_POST['semester']) ? $_POST['semester'] : NULL;
    
    // Yahan Correction: 'subjects' ki jagah 'subject_experties' variable use karein
    $subject_experties = isset($_POST['subject_experties']) ? $_POST['subject_experties'] : NULL;

    // Validation
    if (empty($name) || empty($email) || empty($raw_password) || empty($role) || empty($branch)) {
        echo "<h3 style='color:red;'>❌ Please fill all required fields.</h3>";
        exit;
    }

    $password = password_hash($raw_password, PASSWORD_BCRYPT);

    // CHECK IF EMAIL EXISTS
    $check_sql = "SELECT user_id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo "<h3 style='color:red;'>❌ Email already registered.</h3>";
        exit;
    }
    $check_stmt->close();

    // -------------------------
    // PREPARE & INSERT DATA (Update column name here)
    // -------------------------
    // 'subjects' ko 'subject_experties' se badla gaya hai
    $sql = "INSERT INTO users (name, email, password, role, branch, semester, subject_experties)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Param bind karte waqt naya variable use karein
        $stmt->bind_param("sssssss", $name, $email, $password, $role, $branch, $semester, $subject_experties);

        if ($stmt->execute()) {
            $userId = $conn->insert_id;

            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['role'] = $role;

            if ($role == "student") {
                header("Location: student_dashboard.php");
            } elseif ($role == "mentor") {
                header("Location: teacher_dashboard.php");
            }
            exit;
        } else {
            echo "❌ Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>