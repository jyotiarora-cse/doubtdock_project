<?php
session_start();
$doubt_id = isset($_GET['id']) ? $_GET['id'] : 0; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Searching for Expert... | DoubtDock</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; text-align: center; padding-top: 100px; background: #f9f9f9; }
        .loader { color: #007bff; font-size: 50px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="loader"><i class="fas fa-circle-notch fa-spin"></i></div>
    <h2>Finding the best Expert for you...</h2>
    <p>Please wait, mentors are being notified via email.</p>

    <script src="http://localhost:3000/socket.io/socket.io.js"></script>
<script>
    const socket = io('http://localhost:3000');
    // PHP se ID le rahe hain (Make sure URL mein ?id=... aa raha hai)
    const doubtId = "<?php echo $_GET['id']; ?>"; 

    if (doubtId) {
        socket.emit('join_chat', doubtId);
        console.log("Waiting for Mentor in room: " + doubtId);

        socket.on('mentor_found', (data) => {
            alert("Expert Found! Redirecting...");
            window.location.href = 'chat_ui.php?id=' + doubtId;
        });
    }
</script>
</body>
</html>