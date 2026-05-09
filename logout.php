<?php
session_start();

// Clear all session data
$_SESSION = [];

// Expire the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Destroy session on server
session_destroy();

// Redirect to login
header("Location: login.php");
exit;
?>