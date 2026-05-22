<?php
include 'db.php';
$result = $conn->query("DESCRIBE doubts");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Default'] . "\n";
}
?>
