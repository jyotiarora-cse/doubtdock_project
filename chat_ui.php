<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html"); 
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$myRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'student';
$doubt_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0; 

// Query with JOIN to get names
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
            --primary: #4361ee;
            --bg: #f1f5f9;
            --white: #ffffff;
            --me-msg: #4361ee;
            --other-msg: #f8fafc;
        }

        body { font-family: 'Segoe UI', Roboto, sans-serif; background: var(--bg); margin: 0; display: flex; justify-content: center; align-items: center; height: 100vh; overflow: hidden; }
        #chat-container { width: 450px; height: 85vh; background: var(--white); display: flex; flex-direction: column; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); overflow: hidden; }

        .chat-header { background: var(--primary); color: white; padding: 15px 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .logo-circle { width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 20px; }

        #messages { flex: 1; padding: 20px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 12px; }
        .msg { max-width: 80%; padding: 12px 16px; border-radius: 15px; font-size: 14.5px; line-height: 1.4; position: relative; animation: slideIn 0.2s ease-out; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        .me { background: var(--me-msg); align-self: flex-end; color: white; border-bottom-right-radius: 2px; }
        .other { background: var(--white); align-self: flex-start; color: #334155; border-bottom-left-radius: 2px; border: 1px solid #e2e8f0; }
        .sender-name { font-size: 10px; font-weight: bold; margin-bottom: 4px; display: block; text-transform: uppercase; opacity: 0.8; }

        .input-area { padding: 15px; background: white; display: flex; align-items: center; gap: 10px; border-top: 1px solid #f1f5f9; }
        #message-input { flex: 1; padding: 12px 18px; border: 1px solid #e2e8f0; border-radius: 25px; outline: none; background: #f8fafc; }
        #plus-btn, #send-btn { border: none; cursor: pointer; transition: 0.2s; display: flex; justify-content: center; align-items: center; }
        #plus-btn { background: #f1f5f9; color: var(--primary); width: 40px; height: 40px; border-radius: 50%; }
        #send-btn { background: var(--primary); color: white; width: 40px; height: 40px; border-radius: 50%; }
        .chat-img { max-width: 200px; border-radius: 10px; margin-top: 5px; cursor: pointer; }
        
        #rating-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
        .modal-box { background: white; padding: 30px; border-radius: 20px; text-align: center; width: 320px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
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
            <button id="end-session-btn" onclick="mentorEndSession()" style="background:#ef4444; color:white; border:none; padding:8px 15px; border-radius:8px; cursor:pointer; font-size:12px; font-weight:600;">
                End Session
            </button>
        <?php endif; ?>
    </div>

    <div id="messages">
        <?php while($row = mysqli_fetch_assoc($result)): 
            $sideClass = ($row['sender_id'] == $current_user_id) ? 'me' : 'other';
            $displayName = ($row['sender_id'] == $current_user_id) ? 'You' : htmlspecialchars($row['sender_name']);
            $msgContent = htmlspecialchars($row['message']);
            
            if(strpos($msgContent, 'uploads/') !== false) {
                $ext = pathinfo($msgContent, PATHINFO_EXTENSION);
                if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $msgContent = "<img src='$msgContent' class='chat-img' onclick='window.open(this.src)'>";
                } else {
                    $msgContent = "<a href='$msgContent' target='_blank' style='color:inherit; font-weight:bold;'><i class='fas fa-file'></i> View File</a>";
                }
            }
        ?>
            <div class="msg <?php echo $sideClass; ?>">
                <span class="sender-name"><?php echo $displayName; ?></span>
                <?php echo $msgContent; ?>
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

<div id="rating-modal">
    <div class="modal-box">
        <h3 style="margin-top:0;">Rate Your Mentor</h3>
        <p style="color:#64748b; font-size:14px;">The session has ended. Please rate your experience.</p>
        <select id="rating-stars" style="width:100%; padding:10px; border-radius:10px; margin-bottom:20px; border:1px solid #e2e8f0;">
            <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
            <option value="4">⭐⭐⭐⭐ Good</option>
            <option value="3">⭐⭐⭐ Average</option>
            <option value="2">⭐⭐ Poor</option>
            <option value="1">⭐ Bad</option>
        </select>
        <button onclick="studentSubmitRating()" style="width:100%; background:var(--primary); color:white; border:none; padding:12px; border-radius:10px; cursor:pointer; font-weight:bold;">Submit & Finish</button>
    </div>
</div>

<script src="http://localhost:3000/socket.io/socket.io.js"></script>
<script>
    const socket = io("http://localhost:3000");

    // CORRECTION 1: Force String Conversion (Ensures Room ID consistency)
    const doubtId = String("<?php echo $doubt_id; ?>"); 
    const myId = String("<?php echo $current_user_id; ?>"); 
    const myName = "<?php echo $current_user_name; ?>";
    const myRole = "<?php echo $myRole; ?>";

    const messagesDiv = document.getElementById('messages');
    const input = document.getElementById('message-input');
    const fileInput = document.getElementById('file-input');
    const notifSound = document.getElementById('notif-sound');

    // CRITICAL FIX: Always join room immediately after connection
    socket.emit('join_chat', doubtId);

    const scrollToBottom = () => { messagesDiv.scrollTop = messagesDiv.scrollHeight; };
    window.onload = scrollToBottom;

    document.getElementById('plus-btn').onclick = () => fileInput.click();

    // File Upload Handler
    fileInput.onchange = () => {
        const file = fileInput.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('file', file);
        formData.append('doubt_id', doubtId);

        fetch('upload_handler.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                socket.emit('send_message', { 
                    doubtId: doubtId, // Correctly sending as string
                    senderId: myId, 
                    senderName: myName, 
                    senderType: myRole, 
                    message: data.fileUrl 
                });
            }
        });
    };

    // Send Message
    const sendMessage = () => {
        if (input.value.trim() !== "") {
            socket.emit('send_message', { 
                doubtId: doubtId, 
                senderId: myId, 
                senderName: myName, 
                senderType: myRole, 
                message: input.value 
            });
            input.value = "";
        }
    };

    document.getElementById('send-btn').onclick = sendMessage;
    input.onkeypress = (e) => { if(e.key === 'Enter') sendMessage(); };

    // Received Message Handler
    socket.on('receive_message', (data) => {
    console.log("Incoming message for room:", data.doubtId);

    if (String(data.doubtId) === doubtId) {
        // FIX: Added the missing closing bracket )
        const isMe = (String(data.senderId) === String(myId)); 
        
        if(!isMe) { 
            notifSound.play().catch(e => console.log("Sound muted")); 
        }

        const div = document.createElement('div');
        div.classList.add('msg', isMe ? 'me' : 'other');
        
        let content = data.message;
        // File/Image check
        if(content.includes('uploads/')) {
            content = content.match(/\.(jpeg|jpg|gif|png)$/) 
                ? `<img src="${content}" class="chat-img" onclick="window.open(this.src)">` 
                : `<a href="${content}" target="_blank" style="color:inherit; font-weight:bold;"><i class="fas fa-file"></i> View File</a>`;
        }

        const senderLabel = isMe ? "You" : (data.senderName || (myRole === 'student' ? "Mentor" : "Student"));
        div.innerHTML = `<span class="sender-name">${senderLabel}</span>${content}`;
        
        messagesDiv.appendChild(div);
        scrollToBottom();
    }
});

    // --- SESSION MANAGEMENT ---

    function mentorEndSession() {
    if(confirm("Are you sure you want to end this session?")) {
        const formData = new FormData();
        formData.append('doubt_id', doubtId);
        formData.append('action', 'close_only');

        fetch('close_session_handler.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // Pehle signal bhejo
                socket.emit('close_chat', { doubtId: doubtId });
                console.log("Closing signal sent to server...");

                // Turant bhagne ke bajaye half second ruko taaki socket msg nikal jaye
                setTimeout(() => {
                    window.location.href = "teacher_dashboard.php";
                }, 500); 
            }
        }).catch(err => console.error("Error closing session:", err));
    }
}

    socket.on('chat_closed', (data) => {
        if(String(data.doubtId) === doubtId) {
            if(myRole === 'student') {
                document.getElementById('rating-modal').style.display = 'flex';
            } else {
                window.location.href = "teacher_dashboard.php";
            }
        }
    });

    function studentSubmitRating() {
    // 1. Value sahi se pakdein
    const ratingElement = document.getElementById('rating-stars');
    const ratingValue = ratingElement.value; 

    // Debugging ke liye alert (Check karo number aa raha hai ya nahi)
    // alert("Sending Rating: " + ratingValue); 

    const formData = new FormData();
    formData.append('doubt_id', doubtId); // doubt_id as Primary Key
    formData.append('rating', ratingValue);
    formData.append('action', 'rate_and_solve');

    fetch('close_session_handler.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert("Rating saved successfully!");
            window.location.href = "student_dashboard.php";
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(err => console.error("Fetch error:", err));
}
</script>
</body>
</html>