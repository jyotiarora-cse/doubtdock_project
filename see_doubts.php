<?php
// see_doubts.php — Legacy file superseded by teacher_dashboard.php + claim_doubt.php.
// It references sendMyMail() which no longer exists, uses raw SQL injection vectors,
// and operates on a 'doubt_text' column that was renamed to 'description'.
// Redirect all traffic to the current teacher dashboard.
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: login.php");
    exit();
}

header("Location: teacher_dashboard.php");
exit();
?>