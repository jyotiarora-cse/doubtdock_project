<?php
session_start();
include 'db.php';
require_once 'config.php';

// ── Auth check ────────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$doubt_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$doubt_id) {
    header("Location: student_dashboard.php");
    exit;
}

// ── Verify this doubt belongs to this student ─────────────────────────────────
$student_id = intval($_SESSION['user_id']);
$verify = $conn->prepare("SELECT doubt_id FROM doubts WHERE doubt_id = ? AND student_id = ?");
$verify->bind_param("ii", $doubt_id, $student_id);
$verify->execute();
$verify->store_result();

if ($verify->num_rows === 0) {
    // Doubt doesn't belong to this student
    header("Location: student_dashboard.php");
    exit;
}
$verify->close();
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
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh; display: flex;
            justify-content: center; align-items: center;
        }
        .card {
            background: white; border-radius: 24px; padding: 50px 40px;
            text-align: center; box-shadow: 0 25px 60px rgba(0,0,0,0.2);
            max-width: 420px; width: 90%; transition: opacity 0.4s;
        }
        .spinner {
            font-size: 3.5rem; color: #4361ee; margin-bottom: 20px;
            animation: spin 1.2s linear infinite; display: inline-block;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        h2 { color: #1a1a2e; font-size: 1.4rem; margin-bottom: 10px; }
        p  { color: #64748b; font-size: 0.9rem; line-height: 1.6; }
        .dots span {
            display: inline-block; animation: bounce 1.4s infinite;
            font-size: 1.5rem; color: #4361ee;
        }
        .dots span:nth-child(2) { animation-delay: 0.2s; }
        .dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce {
            0%,80%,100% { transform: translateY(0); }
            40%          { transform: translateY(-10px); }
        }
        .ring-wrap {
            position: relative; width: 90px; height: 90px;
            margin: 20px auto 10px;
        }
        .ring-wrap svg { transform: rotate(-90deg); }
        .ring-bg   { fill: none; stroke: #e8ecf0; stroke-width: 6; }
        .ring-fill {
            fill: none; stroke: #4361ee; stroke-width: 6;
            stroke-linecap: round;
            stroke-dasharray: 245;
            stroke-dashoffset: 0;
            transition: stroke-dashoffset 1s linear, stroke 0.5s;
        }
        .status-text { font-size: 0.78rem; color: #94a3b8; margin-top: 6px; }
        .cancel-btn {
            margin-top: 25px; display: inline-block; color: #ef4444;
            font-size: 0.85rem; text-decoration: none;
            border: 1px solid #ef4444; padding: 8px 20px;
            border-radius: 10px; transition: 0.2s;
        }
        .cancel-btn:hover { background: #ef4444; color: white; }

        #no-mentor-card {
            display: none;
            background: white; border-radius: 24px; padding: 45px 35px;
            text-align: center; box-shadow: 0 25px 60px rgba(0,0,0,0.2);
            max-width: 440px; width: 90%;
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .no-mentor-icon { font-size: 3rem; margin-bottom: 16px; }
        #no-mentor-card h2 { color: #1a1a2e; margin-bottom: 10px; font-size: 1.4rem; }
        #no-mentor-card p  { color: #64748b; font-size: 0.9rem; line-height: 1.7; margin-bottom: 24px; }
        .btn-group { display: flex; flex-direction: column; gap: 10px; }
        .btn-retry {
            background: #4361ee; color: white; border: none;
            padding: 13px; border-radius: 12px; font-size: 14px;
            font-weight: 600; cursor: pointer; transition: 0.2s;
            text-decoration: none; display: block;
        }
        .btn-retry:hover { background: #3451d1; transform: translateY(-2px); }
        .btn-dashboard {
            background: white; color: #64748b;
            border: 2px solid #e2e8f0; padding: 11px; border-radius: 12px;
            font-size: 14px; font-weight: 600; cursor: pointer;
            text-decoration: none; display: block; transition: 0.2s;
        }
        .btn-dashboard:hover { border-color: #94a3b8; }
        .info-box {
            background: #f8fafc; border-radius: 12px;
            padding: 14px 16px; margin-bottom: 20px;
            font-size: 12px; color: #94a3b8; line-height: 1.6; text-align: left;
        }
        .info-box i { color: #4361ee; margin-right: 5px; }
    </style>
</head>
<body>

<!-- Waiting State -->
<div class="card" id="waiting-card">
    <div class="spinner"><i class="fas fa-circle-notch"></i></div>
    <h2>Finding the best expert for you...</h2>
    <div class="dots"><span>.</span><span>.</span><span>.</span></div>
    <p style="margin-top:12px;">
        Mentors for your subject have been notified.<br>
        An expert will join shortly.
    </p>
    <div class="ring-wrap">
        <svg width="90" height="90" viewBox="0 0 90 90">
            <circle class="ring-bg"   cx="45" cy="45" r="39"/>
            <circle class="ring-fill" cx="45" cy="45" r="39" id="ring"/>
        </svg>
    </div>
    <div class="status-text" id="status-text">Waiting for a mentor to accept...</div>
    <a href="student_dashboard.php" class="cancel-btn">
        <i class="fas fa-times"></i> Cancel
    </a>
</div>

<!-- No Mentor Found State -->
<div id="no-mentor-card">
    <div class="no-mentor-icon">😔</div>
    <h2>No Mentor Available Right Now</h2>
    <p>
        All mentors for your subject are currently offline or busy.<br>
        Your doubt has been saved — you'll receive an <strong>email notification</strong>
        as soon as a mentor becomes available.
    </p>
    <div class="info-box">
        <div><i class="fas fa-clock"></i> Average response time: <strong>within 30 minutes</strong></div>
        <div><i class="fas fa-envelope"></i> Email notification will be sent when a mentor is available</div>
        <div><i class="fas fa-bookmark"></i> Your doubt is saved in your dashboard</div>
    </div>
    <div class="btn-group">
        <a href="waiting_room.php?id=<?= $doubt_id ?>" class="btn-retry">
            <i class="fas fa-redo"></i> Try Again
        </a>
        <a href="student_dashboard.php" class="btn-dashboard">
            <i class="fas fa-home"></i> Go to Dashboard
        </a>
    </div>
    <p id="auto-redirect-text" style="margin-top:16px; font-size:12px; color:#94a3b8;">
        Redirecting to dashboard in 5s...
    </p>
</div>

<script>
    const doubtId   = <?= intval($doubt_id) ?>;
    const TIMEOUT   = 180;
    const CIRCUMF   = 245;

    let timeLeft    = TIMEOUT;
    let mentorFound = false;

    const ring       = document.getElementById('ring');
    const statusText = document.getElementById('status-text');

    // ── Try to connect to Socket.io (graceful fallback if Node.js is down) ────
    let socket = null;
    try {
        socket = io('<?= NODE_URL ?>', { timeout: 5000, reconnectionAttempts: 3 });

        // Fixed: send an object matching what the server expects
        socket.on('connect', () => {
            socket.emit('join_chat', { doubtId: doubtId });
        });

        socket.on('mentor_found', () => {
            mentorFound = true;
            statusText.textContent = '✅ Mentor found! Redirecting...';
            statusText.style.color = '#28a745';
            setTimeout(() => { window.location.href = 'chat_ui.php?id=' + doubtId; }, 800);
        });

        socket.on('connect_error', () => {
            console.warn('Socket.io unavailable — using polling fallback only.');
        });
    } catch(e) {
        console.warn('Socket.io not loaded — polling only.');
    }

    // ── Polling fallback every 3s ─────────────────────────────────────────────
    const poll = setInterval(() => {
        if (mentorFound) { clearInterval(poll); return; }
        fetch('check_status.php?id=' + doubtId)
            .then(r => r.json())
            .then(data => {
                if (data.status === 'ready') {
                    mentorFound = true;
                    clearInterval(poll);
                    window.location.href = 'chat_ui.php?id=' + doubtId;
                }
            })
            .catch(() => {});
    }, 3000);

    // ── Countdown ticker ──────────────────────────────────────────────────────
    const ticker = setInterval(() => {
        if (mentorFound) { clearInterval(ticker); return; }

        timeLeft--;
        const progress = timeLeft / TIMEOUT;
        ring.style.strokeDashoffset = CIRCUMF * (1 - progress);

        if      (timeLeft <= 30) ring.style.stroke = '#ef4444';
        else if (timeLeft <= 60) ring.style.stroke = '#f59e0b';
        else                     ring.style.stroke = '#4361ee';

        if (timeLeft <= 60 && timeLeft > 30) {
            statusText.textContent = 'Still searching... almost there';
            statusText.style.color = '#f59e0b';
        } else if (timeLeft <= 30) {
            statusText.textContent = 'Last chance — checking for mentors...';
            statusText.style.color = '#ef4444';
        }

        if (timeLeft <= 0) {
            clearInterval(ticker);
            clearInterval(poll);

            document.getElementById('waiting-card').style.display  = 'none';
            document.getElementById('no-mentor-card').style.display = 'block';

            let autoLeft = 5;
            const autoEl = document.getElementById('auto-redirect-text');
            const autoTick = setInterval(() => {
                autoLeft--;
                autoEl.textContent = 'Redirecting to dashboard in ' + autoLeft + 's...';
                if (autoLeft <= 0) {
                    clearInterval(autoTick);
                    window.location.href = 'student_dashboard.php';
                }
            }, 1000);
        }
    }, 1000);
</script>

<!-- Load Socket.io after inline script defines the callbacks -->
<script>
// Dynamically load socket.io so page doesn't break if Node.js is offline
(function() {
    var s = document.createElement('script');
    s.src = '<?= NODE_URL ?>/socket.io/socket.io.js';
    s.onerror = function() { console.warn('Socket.io script failed to load.'); };
    document.head.appendChild(s);
})();
</script>
</body>
</html>
