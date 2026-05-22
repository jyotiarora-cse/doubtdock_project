<?php
include 'db.php';
$php_time = date('Y-m-d H:i:s');
$res = $conn->query("SELECT NOW() as mysql_time");
$row = $res->fetch_assoc();
echo "PHP Time: $php_time\n";
echo "MySQL Time: " . $row['mysql_time'] . "\n";
?>
