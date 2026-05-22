<?php
include 'db.php';
$result = $conn->query("SELECT user_id, name, role, subject_experties FROM users WHERE role='mentor'");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
?>
