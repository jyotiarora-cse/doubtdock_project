<?php
session_start();
include 'db.php';
$doubt_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$doubt_id) { header("Location: student_dashboard.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finding Expert... | DoubtDock</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #4361ee, #7209b7);
            min-height: 100vh;
            display: flex; justify-content: center; align-items: center;
        }
        .card {
            background: white; border-radius: 24px; padding: 50px 40px;
            text-align: center; box-shadow: 0 25px 60px rgba(0,0,0,0.25);
            max-width: 420px; width: 90%;
        }
        .spinner {
            font-size: 3.5rem; color: #4361ee; margin-bottom: 20px;
            animation: spin 1.2s linear infinite; display: inline-block;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        h2 { color: #1a1a2e; font-size: 1.4rem; margin-bottom: 8px; }
        p  { color: #64748b; font-size: 0.9rem; line-height: 1.7; margin-top: 12px; }
        .dots span {
            display: inline-block; animation: bounce 1.4s infinite;
            font-size: 1.6rem; color: #4361ee; line-height: 1;
        }
        .dots span:nth-child(2) { animation-delay: 0.2s; }
        .dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce {
            0%,80%,100% { transform: translateY(0); }
            40%          { transform: translateY(-10px); }
        }
        .timer { margin-top: 18px; font-size: 0.8rem; color: #94a3b8; }
        .cancel-btn {
            margin-top: 28px; display: inline-block; color: #ef4444;
            font-size: 0.85rem; text-decoration: none;
            border: 1px solid #ef4444; padding: 9px 22px;
            border-radius: 10px; transition: 0.2s;
        }
        .cancel-btn:hover { background: #ef4444; color: white; }
    </style>
</head>
<body>
<div class="card">
    <div class="spinner"><i class="fas fa-circle-notch"></i></div>
    <h2>Finding the best expert for you...</h2>
    <div class="dots"><span>.</span><span>.</span><span>.</span></div>
    <p>Mentors for your subject have been notified.<br>An expert will join shortly.</p>
    <div class="timer">⏱ Waiting: <span id="elapsed">0</span>s</div>
    <a href="student_dashboard.php" class="cancel-btn">
        <i class="fas fa-times"></i> Cancel
    </a>
</div>

<script src="http://localhost:3000/socket.io/socket.io.js"></script>
<script>
    const doubtId = "<?php echo $doubt_id; ?>";
    let elapsed   = 0;

    // Timer counter
    setInterval(() => {
        document.getElementById('elapsed').textContent = ++elapsed;
    }, 1000);

    // Method 1: Socket.io — instant redirect when mentor claims
    const socket = io('http://localhost:3000');
    socket.emit('join_chat', doubtId);

    socket.on('mentor_found', () => {
        window.location.href = 'chat_ui.php?id=' + doubtId;
    });

    // Method 2: Polling fallback — in case socket event is missed
    // check_status.php returns JSON: { "status": "ready" } or { "status": "waiting" }
    const poll = setInterval(() => {
        fetch('check_status.php?id=' + doubtId)
            .then(r => r.json())
            .then(data => {
                if (data.status === 'ready') {
                    clearInterval(poll);
                    window.location.href = 'chat_ui.php?id=' + doubtId;
                }
            })
            .catch(() => {}); // fail silently
    }, 3000);
</script>
</body>
</html>
