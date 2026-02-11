<?php
// Session shuru karna zaroori hai taaki use khatam kiya ja sake
session_start();

// Saare session variables ko khali (unset) kar dena
$_SESSION = array();

// Agar session cookie use ho rahi hai, toh use bhi expire kar dena (Extra security)
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Poore session ko server se delete (destroy) karna
session_destroy();

// Logout ke baad user ko login page par bhej dena
header("Location: login.php");
exit;
?>