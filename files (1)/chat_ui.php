<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id   = $_SESSION['user_id'];
$current_user_name = $_SESSION['user_name'] ?? 'User';
$myRole            = $_SESSION['role']      ?? 'student';
$doubt_id          = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load existing chat messages
$sql = "SELECT m.*, u.name AS sender_name
        FROM chat_messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE m.doubt_id = '$doubt_id'
        ORDER BY m.created_at ASC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DoubtDock | Live Support</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary:   #4361ee;
            --bg:        #f1f5f9;
            --white:     #ffffff;
            --me-msg:    #4361ee;
        }
        body {
            font-family: 'Segoe UI', Roboto, sans-serif;
            background: var(--bg); margin: 0;
            display: flex; justify-content: center; align-items: center;
            height: 100vh; overflow: hidden;
        }
        #chat-container {
            width: 450px; height: 85vh; background: var(--white);
            display: flex; flex-direction: column;
            border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); overflow: hidden;
        }
        .chat-header {
            background: var(--primary); color: white;
            padding: 15px 20px; display: flex; align-items: center;
            gap: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .logo-circle {
            width: 40px; height: 40px; background: rgba(255,255,255,0.2);
            border-radius: 12px; display: flex; justify-content: center;
            align-items: center; font-weight: bold; font-size: 20px;
        }
        #messages {
            flex: 1; padding: 20px; overflow-y: auto;
            background: #f8fafc; display: flex; flex-direction: column; gap: 12px;
        }
        .msg {
            max-width: 80%; padding: 12px 16px; border-radius: 15px;
            font-size: 14.5px; line-height: 1.4;
            animation: slideIn 0.2s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(5px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .me    { background: var(--me-msg); align-self: flex-end; color: white; border-bottom-right-radius: 2px; }
        .other { background: var(--white); align-self: flex-start; color: #334155; border-bottom-left-radius: 2px; border: 1px solid #e2e8f0; }
        .sender-name { font-size: 10px; font-weight: bold; margin-bottom: 4px; display: block; text-transform: uppercase; opacity: 0.8; }

        .input-area {
            padding: 15px; background: white; display: flex;
            align-items: center; gap: 10px; border-top: 1px solid #f1f5f9;
        }
        #message-input {
            flex: 1; padding: 12px 18px; border: 1px solid #e2e8f0;
            border-radius: 25px; outline: none; background: #f8fafc; font-size: 14px;
        }
        #plus-btn, #send-btn {
            border: none; cursor: pointer; transition: 0.2s;
            display: flex; justify-content: center; align-items: center;
        }
        #plus-btn { background: #f1f5f9; color: var(--primary); width: 40px; height: 40px; border-radius: 50%; }
        #send-btn { background: var(--primary); color: white; width: 40px; height: 40px; border-radius: 50%; }
        .chat-img { max-width: 200px; border-radius: 10px; margin-top: 5px; cursor: pointer; }

        #rating-modal {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15,23,42,0.7); z-index: 1000;
            justify-content: center; align-items: center; backdrop-filter: blur(4px);
        }
        .modal-box {
            background: white; padding: 35px 30px; border-radius: 20px;
            text-align: center; width: 320px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .modal-box h3 { margin-top: 0; color: #1a202c; }
        .modal-box p  { color: #64748b; font-size: 14px; margin-bottom: 20px; }
        .star-select {
            width: 100%; padding: 10px; border-radius: 10px;
            margin-bottom: 20px; border: 1px solid #e2e8f0;
            font-size: 14px; outline: none;
        }
        .submit-rating-btn {
            width: 100%; background: var(--primary); color: white;
            border: none; padding: 13px; border-radius: 10px;
            cursor: pointer; font-weight: bold; font-size: 15px;
            transition: 0.2s;
        }
        .submit-rating-btn:hover { background: #3451d1; }
    </style>
</head>
<body>

<audio id="notif-sound" src="https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3" preload="auto"></audio>

<div id="chat-container">
    <div class="chat-header">
        <div class="logo-circle">D</div>
        <div style="flex:1;">
            <h3 style="margin:0; font-size:16px;">DoubtDock</h3>
            <div style="font-size:11px; opacity:0.8;">Live Support Session</div>
        </div>
        <?php if ($myRole === 'mentor'): ?>
            <button onclick="mentorEndSession()"
                    style="background:#ef4444; color:white; border:none; padding:8px 15px;
                           border-radius:8px; cursor:pointer; font-size:12px; font-weight:600;">
                End Session
            </button>
        <?php endif; ?>
    </div>

    <div id="messages">
        <?php while ($row = mysqli_fetch_assoc($result)):
            $sideClass   = ($row['sender_id'] == $current_user_id) ? 'me' : 'other';
            $displayName = ($row['sender_id'] == $current_user_id) ? 'You' : htmlspecialchars($row['sender_name']);
            $msgContent  = htmlspecialchars($row['message']);

            if (strpos($msgContent, 'uploads/') !== false) {
                $ext = strtolower(pathinfo($msgContent, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif'])) {
                    $msgContent = "<img src='$msgContent' class='chat-img' onclick='window.open(this.src)'>";
                } else {
                    $msgContent = "<a href='$msgContent' target='_blank' style='color:inherit;font-weight:bold;'><i class='fas fa-file'></i> View File</a>";
                }
            }
        ?>
            <div class="msg <?= $sideClass ?>">
                <span class="sender-name"><?= $displayName ?></span>
                <?= $msgContent ?>
            </div>
        <?php endwhile; ?>
    </div>

    <div class="input-area">
        <input type="file" id="file-input" style="display:none;" accept="image/*, .pdf, .docx">
        <button type="button" id="plus-btn" title="Upload File"><i class="fas fa-plus"></i></button>
        <input type="text" id="message-input" placeholder="Type a message...">
        <button id="send-btn"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<!-- Rating Modal (shown to student when mentor ends session) -->
<div id="rating-modal">
    <div class="modal-box">
        <h3>Rate Your Mentor</h3>
        <p>The session has ended. Please rate your experience.</p>
        <select id="rating-stars" class="star-select">
            <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
            <option value="4">⭐⭐⭐⭐ Good</option>
            <option value="3">⭐⭐⭐ Average</option>
            <option value="2">⭐⭐ Poor</option>
            <option value="1">⭐ Bad</option>
        </select>
        <button class="submit-rating-btn" onclick="studentSubmitRating()">
            Submit &amp; Finish
        </button>
    </div>
</div>

<script src="http://localhost:3000/socket.io/socket.io.js"></script>
<script>
    const socket = io("http://localhost:3000");

    const doubtId = String("<?php echo $doubt_id; ?>");
    const myId    = String("<?php echo $current_user_id; ?>");
    const myName  = "<?php echo addslashes($current_user_name); ?>";
    const myRole  = "<?php echo $myRole; ?>";

    const messagesDiv  = document.getElementById('messages');
    const input        = document.getElementById('message-input');
    const fileInput    = document.getElementById('file-input');
    const notifSound   = document.getElementById('notif-sound');

    // Join socket room for this doubt
    socket.emit('join_chat', doubtId);

    const scrollToBottom = () => { messagesDiv.scrollTop = messagesDiv.scrollHeight; };
    window.onload = scrollToBottom;

    document.getElementById('plus-btn').onclick = () => fileInput.click();

    // File upload
    fileInput.onchange = () => {
        const file = fileInput.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('file', file);
        formData.append('doubt_id', doubtId);

        fetch('upload_handler.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    socket.emit('send_message', {
                        doubtId, senderId: myId, senderName: myName,
                        senderType: myRole, message: data.fileUrl
                    });
                }
            });
    };

    // Send message
    const sendMessage = () => {
        const text = input.value.trim();
        if (text !== "") {
            socket.emit('send_message', {
                doubtId, senderId: myId, senderName: myName,
                senderType: myRole, message: text
            });
            input.value = "";
        }
    };

    document.getElementById('send-btn').onclick = sendMessage;
    input.onkeypress = (e) => { if (e.key === 'Enter') sendMessage(); };

    // Receive message
    socket.on('receive_message', (data) => {
        if (String(data.doubtId) !== doubtId) return;

        const isMe = (String(data.senderId) === String(myId));
        if (!isMe) notifSound.play().catch(() => {});

        const div = document.createElement('div');
        div.classList.add('msg', isMe ? 'me' : 'other');

        let content = data.message;
        if (content.includes('uploads/')) {
            content = content.match(/\.(jpeg|jpg|gif|png)$/)
                ? `<img src="${content}" class="chat-img" onclick="window.open(this.src)">`
                : `<a href="${content}" target="_blank" style="color:inherit;font-weight:bold;"><i class="fas fa-file"></i> View File</a>`;
        }

        const label = isMe ? "You" : (data.senderName || (myRole === 'student' ? "Mentor" : "Student"));
        div.innerHTML = `<span class="sender-name">${label}</span>${content}`;
        messagesDiv.appendChild(div);
        scrollToBottom();
    });

    // Mentor ends session
    function mentorEndSession() {
        if (!confirm("Are you sure you want to end this session?")) return;

        const formData = new FormData();
        formData.append('doubt_id', doubtId);
        formData.append('action', 'close_only');

        fetch('close_session_handler.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    socket.emit('close_chat', { doubtId });
                    setTimeout(() => {
                        window.location.href = "teacher_dashboard.php";
                    }, 500);
                } else {
                    alert("Failed to end session. Please try again.");
                }
            })
            .catch(() => alert("Network error. Please try again."));
    }

    // Chat closed event — show rating modal to student
    socket.on('chat_closed', (data) => {
        if (String(data.doubtId) !== doubtId) return;
        if (myRole === 'student') {
            document.getElementById('rating-modal').style.display = 'flex';
        } else {
            window.location.href = "teacher_dashboard.php";
        }
    });

    // Student submits rating
    function studentSubmitRating() {
        const ratingValue = document.getElementById('rating-stars').value;

        const formData = new FormData();
        formData.append('doubt_id', doubtId);
        formData.append('rating', ratingValue);
        formData.append('action', 'rate_and_solve');

        fetch('close_session_handler.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert("Thank you for your feedback!");
                    window.location.href = "student_dashboard.php";
                } else {
                    alert("Error saving rating: " + data.message);
                }
            })
            .catch(() => alert("Network error. Please try again."));
    }
</script>
</body>
</html>

