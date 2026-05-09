<?php
// mantor_dashboard.php — Legacy file with a typo in the name ("mantor" instead of "mentor").
// This file is superseded by teacher_dashboard.php which has real-time Socket.io support.
// Redirect all visitors to the correct dashboard.
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: login.php");
    exit();
}

header("Location: teacher_dashboard.php");
exit();
?>