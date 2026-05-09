<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name         = trim($_POST['name']);
    $email        = trim($_POST['email']);
    $raw_password = $_POST['password'];
    $role         = $_POST['role'];
    $branch       = $_POST['branch'];
    $semester     = isset($_POST['semester']) ? $_POST['semester'] : NULL;

    // Handle multi-select subject chips from register form (subject_experties[] array)
    if (isset($_POST['subject_experties']) && is_array($_POST['subject_experties'])) {
        $subject_experties = implode(',', $_POST['subject_experties']); // e.g. "DBMS,Python,OS"
    } elseif (isset($_POST['subject_experties']) && !empty($_POST['subject_experties'])) {
        $subject_experties = trim($_POST['subject_experties']);
    } else {
        $subject_experties = NULL;
    }

    // Validation
    if (empty($name) || empty($email) || empty($raw_password) || empty($role) || empty($branch)) {
        echo "<h3 style='color:red;font-family:sans-serif;text-align:center;margin-top:40px;'>
                ❌ Please fill in all required fields.
              </h3>";
        exit;
    }

    if ($role === 'mentor' && empty($subject_experties)) {
        echo "<h3 style='color:red;font-family:sans-serif;text-align:center;margin-top:40px;'>
                ❌ Mentors must select at least one subject.
              </h3>";
        exit;
    }

    $password = password_hash($raw_password, PASSWORD_BCRYPT);

    // Check if email already exists
    $check_sql  = "SELECT user_id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo "<h3 style='color:red;font-family:sans-serif;text-align:center;margin-top:40px;'>
                ❌ This email is already registered. Please login instead.
              </h3>";
        exit;
    }
    $check_stmt->close();

    // Insert new user
    $sql  = "INSERT INTO users (name, email, password, role, branch, semester, subject_experties)
             VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sssssss", $name, $email, $password, $role, $branch, $semester, $subject_experties);

        if ($stmt->execute()) {
            $userId = $conn->insert_id;

            // Set common session variables
            $_SESSION['user_id']   = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['role']      = $role;
            $_SESSION['branch']    = $branch;

            if ($role === 'student') {
                $_SESSION['semester'] = $semester;
                header("Location: student_dashboard.php");

            } elseif ($role === 'mentor') {
                // These are required by teacher_dashboard.php
                $_SESSION['mentor_branch']    = $branch;
                $_SESSION['mentor_expertise'] = $subject_experties;

                header("Location: teacher_dashboard.php");
            }
            exit;

        } else {
            echo "❌ Registration failed: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "❌ Database error: " . $conn->error;
    }
}
$conn->close();
?>
