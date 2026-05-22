<?php
$url = "http://localhost/doubtdock_project/heartbeat.php?id=73";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=" . session_id()); // We need to simulate student session?
$response = curl_exec($ch);
echo "Response: " . $response;
?>
