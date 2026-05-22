<?php
include 'db.php';
$result = $conn->query("SELECT doubt_id, subject, status, last_heartbeat, NOW() as current_time_db FROM doubts ORDER BY doubt_id DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
?>
