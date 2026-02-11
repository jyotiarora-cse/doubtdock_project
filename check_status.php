<?php
include 'db.php';
$id = $_GET['id'];
$sql = "SELECT status FROM doubts WHERE doubt_id = '$id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

// DB mein status 'claimed' ho chuka hoga mentor ke click karne par
if ($row && $row['status'] == 'Claimed') {
    echo "ready";
} else {
    echo "waiting";
}
?>