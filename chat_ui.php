<?php
session_start();
include 'db.php';
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id   = $_SESSION['user_id'];
$current_user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$myRole            = isset($_SESSION['role'])      ? $_SESSION['role']      : 'student';
$doubt_id          = isset($_GET['id'])            ? intval($_GET['id'])    : 0;

// Chat messages fetch
$sql = "SELECT m.*, u.name AS sender_name 
        FROM chat_messages m 
        JOIN users u ON m.sender_id = u.user_id 
        WHERE m.doubt_id = '$doubt_id' 
        ORDER BY m.created_at ASC";
$result = mysqli_query($conn, $sql);

// Doubt info fetch
$doubt_sql    = "SELECT image_path, topic, subject, mentor_id, student_id, status FROM doubts WHERE doubt_id = '$doubt_id'";
$doubt_result = mysqli_query($conn, $doubt_sql);
$doubt_row    = mysqli_fetch_assoc($doubt_result);

if (!$doubt_row) {
    die("Error: Doubt not found.");
}

$doubt_image      = !empty($doubt_row['image_path']) ? $doubt_row['image_path'] : null;
$doubt_topic      = !empty($doubt_row['topic'])      ? $doubt_row['topic']      : null;
$doubt_subject    = !empty($doubt_row['subject'])    ? $doubt_row['subject']    : null;
$doubt_mentor_id  = !empty($doubt_row['mentor_id'])  ? intval($doubt_row['mentor_id'])  : 0;
$doubt_student_id = !empty($doubt_row['student_id']) ? intval($doubt_row['student_id']) : 0;
$doubt_status     = $doubt_row['status'];

// Authorization Check
if ($current_user_id != $doubt_student_id && $current_user_id != $doubt_mentor_id) {
    // If a mentor is trying to view a doubt that isn't theirs yet, redirect them to claim it
    if ($myRole === 'mentor' && $doubt_status === 'Pending') {
        header("Location: claim_doubt.php?id=" . $doubt_id);
    } else {
        die("Error: You are not authorized to view this chat.");
    }
    exit();
}
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
        }

        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Roboto, sans-serif; background: var(--bg); margin: 0; display: flex; justify-content: center; align-items: center; height: 100vh; overflow: hidden; }
        #chat-container { width: 450px; height: 85vh; background: var(--white); display: flex; flex-direction: column; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); overflow: hidden; }

        /* Header */
        .chat-header { background: var(--primary); color: white; padding: 15px 20px; display: flex; align-items: center; gap: 15px; flex-shrink: 0; }
        .logo-circle { width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 20px; }

        /* Doubt Image Banner */
        .doubt-image-banner { flex-shrink: 0; background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 16px; }
        .doubt-image-label { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; display: flex; align-items: center; gap: 5px; }
        .doubt-image-wrap { position: relative; display: inline-block; width: 100%; }
        .doubt-image-wrap img { width: 100%; max-height: 160px; object-fit: cover; border-radius: 10px; cursor: pointer; display: block; transition: opacity 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .doubt-image-wrap img:hover { opacity: 0.92; }
        .view-full-btn { position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.55); color: white; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; pointer-events: none; }

        /* Doubt info bar */
        .doubt-info-bar { flex-shrink: 0; background: #fff; border-bottom: 1px solid #e2e8f0; padding: 8px 16px; display: flex; align-items: center; gap: 8px; font-size: 12px; color: #64748b; }
        .doubt-info-bar .subject-tag { background: #ede9fe; color: #6d28d9; font-weight: 600; padding: 2px 10px; border-radius: 20px; font-size: 11px; }
        .doubt-info-bar .topic-text { font-weight: 500; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 260px; }

        /* Messages */
        #messages { flex: 1; padding: 20px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 12px; }
        .msg { max-width: 80%; padding: 12px 16px; border-radius: 15px; font-size: 14px; line-height: 1.6; animation: slideIn 0.2s ease-out; }
        @keyframes slideIn { from { opacity:0; transform:translateY(5px); } to { opacity:1; transform:translateY(0); } }
        .msg-me    { background: var(--me-msg); align-self: flex-end; color: white; border-bottom-right-radius: 4px; }
        .msg-other { background: var(--white); align-self: flex-start; color: #334155; border-bottom-left-radius: 4px; border: 1px solid #e2e8f0; }
        .msg-wrong           { background: #fff1f1; border: 1.5px solid #f87171; color: #7f1d1d; align-self: flex-end; border-bottom-right-radius: 4px; }
        .msg-complex         { background: #fffbeb; border: 1.5px solid #fbbf24; color: #78350f; align-self: flex-end; border-bottom-right-radius: 4px; }
        .msg-corrected-mentor{ background: #f0fdf4; border: 1.5px solid #4ade80; color: #14532d; align-self: flex-end; border-bottom-right-radius: 4px; }
        .sender-name { font-size: 10px; font-weight: 700; margin-bottom: 4px; display: block; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.65; }
        .ai-badge       { font-size: 10px; padding: 2px 8px; border-radius: 20px; margin-left: 6px; font-weight: 600; display: inline-block; vertical-align: middle; }
        .badge-wrong    { background: #fee2e2; color: #b91c1c; }
        .badge-complex  { background: #fef3c7; color: #b45309; }
        .badge-corrected{ background: #dcfce7; color: #15803d; }

        /* Input */
        .input-area { padding: 15px; background: white; display: flex; align-items: center; gap: 10px; border-top: 1px solid #f1f5f9; flex-shrink: 0; }
        #message-input { flex: 1; padding: 12px 18px; border: 1px solid #e2e8f0; border-radius: 25px; outline: none; background: #f8fafc; font-family: inherit; font-size: 14px; }
        #message-input:focus { border-color: var(--primary); }
        #plus-btn, #send-btn { border: none; cursor: pointer; transition: 0.2s; display: flex; justify-content: center; align-items: center; flex-shrink: 0; }
        #plus-btn { background: #f1f5f9; color: var(--primary); width: 40px; height: 40px; border-radius: 50%; }
        #send-btn { background: var(--primary); color: white; width: 40px; height: 40px; border-radius: 50%; }
        #send-btn:hover { opacity: 0.9; transform: scale(1.05); }
        .chat-img { max-width: 200px; border-radius: 10px; margin-top: 5px; cursor: pointer; }

        /* Rating Modal */
        #rating-modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.75); z-index:1000; justify-content:center; align-items:center; backdrop-filter:blur(4px); }
        .modal-box { background:white; padding:32px; border-radius:20px; text-align:center; width:340px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .modal-box h3 { margin: 0 0 8px; font-size: 20px; color: #1e293b; }
        .modal-box p  { color: #64748b; font-size: 14px; margin-bottom: 20px; }

        /* Lightbox */
        #lightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 9998; justify-content: center; align-items: center; cursor: zoom-out; }
        #lightbox.active { display: flex; }
        #lightbox img { max-width: 90vw; max-height: 90vh; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
        #lightbox-close { position: absolute; top: 20px; right: 24px; color: white; font-size: 28px; cursor: pointer; opacity: 0.7; transition: 0.2s; }
        #lightbox-close:hover { opacity: 1; }

        /* AI Toast */
        .ai-toast { position: fixed; bottom: 28px; right: 28px; background: #0f172a; color: #f8fafc; padding: 20px 22px; border-radius: 16px; font-size: 13px; max-width: 360px; z-index: 9999; line-height: 1.65; box-shadow: 0 12px 40px rgba(0,0,0,0.45); animation: toastSlide 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
        @keyframes toastSlide { from { opacity:0; transform: translateY(20px) scale(0.95); } to { opacity:1; transform: translateY(0) scale(1); } }
        .toast-header   { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .toast-title    { font-weight: 700; font-size: 14px; }
        .toast-close    { cursor: pointer; opacity: 0.45; font-size: 18px; line-height: 1; transition: opacity 0.2s; }
        .toast-close:hover { opacity: 1; }
        .toast-question { font-size: 11.5px; color: #94a3b8; margin-bottom: 10px; padding: 6px 10px; background: rgba(255,255,255,0.06); border-radius: 8px; font-style: italic; }
        .toast-body     { font-size: 13px; color: #cbd5e1; line-height: 1.65; }
        .toast-mistake  { margin-top: 12px; font-size: 12px; color: #fca5a5; background: rgba(239,68,68,0.12); padding: 8px 12px; border-radius: 8px; border-left: 3px solid #f87171; }
        .toast-scores   { display: flex; gap: 8px; margin-top: 14px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.08); }
        .toast-score-item { flex: 1; background: rgba(255,255,255,0.07); padding: 8px 10px; border-radius: 10px; text-align: center; }
        .toast-score-label{ font-size: 10px; color: #94a3b8; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em; }
        .toast-score-value{ font-size: 16px; font-weight: 700; }

        /* AI Thinking */
        .ai-thinking { align-self: flex-start; background: white; border: 1px solid #e2e8f0; border-radius: 15px; border-bottom-left-radius: 4px; padding: 12px 16px; display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748b; }
        .thinking-dots span { display: inline-block; width: 6px; height: 6px; background: #94a3b8; border-radius: 50%; animation: blink 1.4s infinite; }
        .thinking-dots span:nth-child(2) { animation-delay: 0.2s; }
        .thinking-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes blink { 0%,80%,100% { opacity:0.2; transform:scale(0.8); } 40% { opacity:1; transform:scale(1); } }
        
        /* Typing Indicator Style - Modern & Inline */
        .typing-msg { align-self: flex-start; background: transparent; padding: 4px 16px; display: none; align-items: center; gap: 8px; font-size: 13px; color: #64748b; animation: fadeIn 0.3s ease; }
        .typing-dots { display: flex; gap: 3px; background: #f1f5f9; padding: 8px 12px; border-radius: 15px; border-bottom-left-radius: 4px; }
        .typing-dots span { width: 4px; height: 4px; background: #94a3b8; border-radius: 50%; display: inline-block; animation: typingBlink 1.4s infinite; }
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typingBlink { 0%,80%,100% { opacity:0.3; transform: scale(0.8); } 40% { opacity:1; transform: scale(1.1); } }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pulse { 0% { opacity: 0.5; } 50% { opacity: 1; } 100% { opacity: 0.5; } }
    </style>
</head>
<body>

<audio id="notif-sound" src="https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3" preload="auto"></audio>

<!-- Lightbox -->
<div id="lightbox" onclick="closeLightbox()">
    <span id="lightbox-close" onclick="closeLightbox()">✕</span>
    <img id="lightbox-img" src="" alt="Full Image">
</div>

<div id="chat-container">

    <div class="chat-header">
        <div class="logo-circle">D</div>
        <div style="flex:1;">
            <h3 style="margin:0; font-size:16px; font-weight:600;">DoubtDock</h3>
            <div style="font-size:11px; opacity:0.75; margin-top:2px;">
                <span id="header-typing" style="color:#ffffff; font-weight:700; display:none; animation: pulse 1.5s infinite;">Typing...</span>
                <span id="session-label">Live Support Session</span>
                <span id="socket-status" style="margin-left:8px; font-weight:700; color:#4ade80;">● Online</span>
            </div>
        </div>
        <?php if ($myRole === 'mentor'): ?>
            <button onclick="mentorEndSession()" style="background:#ef4444; color:white; border:none; padding:8px 16px; border-radius:8px; cursor:pointer; font-size:12px; font-weight:600;">
                End Session
            </button>
        <?php endif; ?>
    </div>

    <?php if ($doubt_subject || $doubt_topic): ?>
    <div class="doubt-info-bar">
        <?php if ($doubt_subject): ?>
            <span class="subject-tag"><?php echo htmlspecialchars($doubt_subject); ?></span>
        <?php endif; ?>
        <?php if ($doubt_topic): ?>
            <span class="topic-text">📌 <?php echo htmlspecialchars($doubt_topic); ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($doubt_image): ?>
    <div class="doubt-image-banner">
        <div class="doubt-image-label"><i class="fas fa-image"></i> Attached by Student</div>
        <div class="doubt-image-wrap">
            <img src="<?php echo htmlspecialchars($doubt_image); ?>"
                 alt="Doubt Image"
                 onclick="openLightbox('<?php echo htmlspecialchars($doubt_image); ?>')"
                 title="Click to view full image">
            <span class="view-full-btn">🔍 View Full</span>
        </div>
    </div>
    <?php endif; ?>

    <div id="messages">
        <?php while($row = mysqli_fetch_assoc($result)):
            $sideClass   = ($row['sender_id'] == $current_user_id) ? 'msg-me' : 'msg-other';
            $displayName = ($row['sender_id'] == $current_user_id) ? 'You' : htmlspecialchars($row['sender_name']);
            $rawMessage  = $row['message'];
            // Check if message is a stored file path (from upload_handler)
            $isFilePath  = (bool) preg_match('#^uploads/[\w/\-\.]+$#', $rawMessage);
            if ($isFilePath) {
                $ext        = strtolower(pathinfo($rawMessage, PATHINFO_EXTENSION));
                $safePath   = htmlspecialchars($rawMessage, ENT_QUOTES, 'UTF-8');
                $msgContent = in_array($ext, ['jpg','jpeg','png','gif'])
                    ? "<img src='{$safePath}' class='chat-img' onclick=\"openLightbox('{$safePath}')\">" 
                    : "<a href='{$safePath}' target='_blank' rel='noopener' style='color:inherit;font-weight:600;'><i class='fas fa-file'></i> View File</a>";
            } else {
                $msgContent = htmlspecialchars($rawMessage, ENT_QUOTES, 'UTF-8');
            }
        ?>
            <div class="msg <?php echo $sideClass; ?>">
                <span class="sender-name"><?php echo $displayName; ?></span>
                <?php echo $msgContent; ?>
            </div>
        <?php endwhile; ?>
        
        <!-- Typing Indicator Moved Inside Messages -->
        <div id="typing-indicator" class="typing-msg">
            <div class="typing-dots"><span></span><span></span><span></span></div>
            <span id="typing-text" style="font-size: 12px; font-weight: 700; color: #4361ee;">Someone is typing</span>
        </div>
    </div>

    <div class="input-area">
        <input type="file" id="file-input" style="display:none;" accept="image/*, .pdf, .docx">
        <button type="button" id="plus-btn" title="Attach file"><i class="fas fa-plus"></i></button>
        <input type="text" id="message-input" placeholder="Type your message...">
        <button id="send-btn" title="Send"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<!-- Rating Modal -->
<div id="rating-modal">
    <div class="modal-box">
        <h3>Rate Your Session</h3>
        <p>How would you rate the support you received from your mentor?</p>
        <select id="rating-stars" style="width:100%; padding:11px 14px; border-radius:10px; margin-bottom:20px; border:1px solid #e2e8f0; font-size:14px; outline:none;">
            <option value="5">⭐⭐⭐⭐⭐ — Excellent</option>
            <option value="4">⭐⭐⭐⭐ — Good</option>
            <option value="3">⭐⭐⭐ — Average</option>
            <option value="2">⭐⭐ — Poor</option>
            <option value="1">⭐ — Very Poor</option>
        </select>
        <button onclick="studentSubmitRating()" style="width:100%; background:var(--primary); color:white; border:none; padding:13px; border-radius:10px; cursor:pointer; font-weight:600; font-size:15px;">
            Submit Rating
        </button>
    </div>
</div>

<script>
    // PHP variables used in JS
    const doubtId   = <?php echo intval($doubt_id); ?>;
    const myId      = <?php echo intval($current_user_id); ?>;
    const mentorId  = <?php echo intval($doubt_mentor_id); ?>;
    const studentId = <?php echo intval($doubt_student_id); ?>;
    const myName    = "<?php echo addslashes($current_user_name); ?>";
    const myRole    = "<?php echo $myRole; ?>";

    // Load Socket.io gracefully — page won't break if Node.js is offline
    let socket = null;
    (function() {
        var s = document.createElement('script');
        s.src = '<?= NODE_URL ?>/socket.io/socket.io.js';
        s.onload = function() {
            socket = io('<?= NODE_URL ?>', { timeout: 5000, reconnectionAttempts: 3 });
            
            socket.on('connect', () => {
                document.getElementById('socket-status').innerHTML = "● Online";
                document.getElementById('socket-status').style.color = "#4ade80";
                console.log("Socket connected!");
                initSocketEvents();
            });

            socket.on('disconnect', () => {
                document.getElementById('socket-status').innerHTML = "○ Offline";
                document.getElementById('socket-status').style.color = "#ef4444";
            });

            socket.on('connect_error', () => {
                document.getElementById('socket-status').innerHTML = "○ Offline";
                document.getElementById('socket-status').style.color = "#ef4444";
            });
        };
        s.onerror = function() { console.warn('Socket.io unavailable — chat in polling mode.'); };
        document.head.appendChild(s);
    })();
</script>
<script>
    function openLightbox(src) {
        document.getElementById('lightbox-img').src = src;
        document.getElementById('lightbox').classList.add('active');
    }
    function closeLightbox() {
        document.getElementById('lightbox').classList.remove('active');
        document.getElementById('lightbox-img').src = '';
    }
    document.addEventListener('keydown', (e) => { if(e.key === 'Escape') closeLightbox(); });

    const scrollToBottom = () => { 
        const messagesDiv = document.getElementById('messages');
        if (messagesDiv) messagesDiv.scrollTop = messagesDiv.scrollHeight; 
    };
    window.onload = scrollToBottom;

    function initSocketEvents() {
        const messagesDiv = document.getElementById('messages');
        const input       = document.getElementById('message-input');
        const fileInput   = document.getElementById('file-input');
        const notifSound  = document.getElementById('notif-sound');

        document.getElementById('plus-btn').onclick = () => fileInput.click();

    // FIX: join_chat mein mentorId aur studentId bhi bhejo
    // Server roomMentors[room] set karta hai isi se
    // Toh rating save hote waqt mentor ka ID milta hai
        socket.emit('join_chat', {
            doubtId:   doubtId,
            mentorId:  mentorId,
            studentId: studentId
        });

    // File Upload
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
                    doubtId:    doubtId,
                    senderId:   myId,
                    senderName: myName,
                    senderType: myRole,
                    message:    data.fileUrl
                });
            }
        });
    };

    // Send Message
    const sendMessage = () => {
        const msg = input.value.trim();
        if (!msg) return;

        // FIX: sab values numbers hain (string nahi)
        // Server ka saveToDb() aur saveRatingToDoubt() dono correctly chalenge
        socket.emit('send_message', {
            doubtId:    doubtId,    // integer
            senderId:   myId,       // integer
            senderName: myName,
            senderType: myRole,     // "mentor" ya "student"
            message:    msg
        });
        input.value = "";
    };

        document.getElementById('send-btn').onclick = sendMessage;
        
        // --- Typing Indicator Logic ---
        let isTyping = false; // Flag taaki baar-baar emit na ho
        let typingTimeout = null;
        
        input.oninput = () => {
            console.log("Input detected!"); // Debug log
            if (!isTyping && socket) {
                isTyping = true;
                socket.emit('typing', { doubtId, senderName: myName });
            }
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                isTyping = false;
                if (socket) socket.emit('stop_typing', { doubtId });
            }, 2000);
        };

        input.onkeypress = (e) => { 
            if (e.key === 'Enter') {
                sendMessage();
                isTyping = false;
                if (socket) socket.emit('stop_typing', { doubtId });
            }
        };

        socket.on('display_typing', (data) => {
            // Agar room alag hai ya humne khud type kiya hai, toh ignore karo
            if (String(data.doubtId) !== String(doubtId) || data.senderName === myName) return;
            
            console.log("Typing notification received for:", data.senderName);
            const indicator = document.getElementById('typing-indicator');
            const text      = document.getElementById('typing-text');
            
            text.innerText = `${data.senderName} is typing`;
            indicator.style.display = 'flex';
            
            // Header Typing Update
            document.getElementById('header-typing').innerText = `${data.senderName} is typing...`;
            document.getElementById('header-typing').style.display = 'inline';
            document.getElementById('session-label').style.display = 'none';
            
            setTimeout(scrollToBottom, 50); 
        });

        socket.on('hide_typing', (data) => {
            if (String(data.doubtId) !== String(doubtId) || data.senderName === myName) return;
            document.getElementById('typing-indicator').style.display = 'none';
            
            // Header Typing Reset
            document.getElementById('header-typing').style.display = 'none';
            document.getElementById('session-label').style.display = 'inline';
        });
        // --- End Typing Logic ---

    // Receive Message
    socket.on('receive_message', (data) => {
        if (String(data.doubtId) !== String(doubtId)) return;

        const thinking = document.getElementById('ai-thinking');
        if (thinking) thinking.remove();

        const isMe   = (String(data.senderId) === String(myId));
        const status = data.messageStatus || 'normal';

        if (!isMe) notifSound.play().catch(() => {});

        let msgClass    = isMe ? 'msg-me' : 'msg-other';
        let badge       = '';
        let senderLabel = isMe ? 'You' : (data.senderName || (myRole === 'student' ? 'Mentor' : 'Student'));

        if (status === 'wrong') {
            msgClass = 'msg-wrong';
            badge    = '<span class="ai-badge badge-wrong">Incorrect Answer</span>';
        } else if (status === 'complex') {
            msgClass = 'msg-complex';
            badge    = '<span class="ai-badge badge-complex">Overly Complex</span>';
        } else if (status === 'corrected_mentor') {
            msgClass    = 'msg-corrected-mentor';
            senderLabel = 'AI — Suggested Answer';
            badge       = '<span class="ai-badge badge-corrected">Verified Correct</span>';
        } else {
            msgClass = isMe ? 'msg-me' : 'msg-other';
        }

        let content = data.message || '';
        if (content.includes('uploads/')) {
            content = content.match(/\.(jpeg|jpg|gif|png)$/)
                ? `<img src="${content}" class="chat-img" onclick="openLightbox('${content}')">`
                : `<a href="${content}" target="_blank" style="color:inherit;font-weight:600;"><i class="fas fa-file"></i> View Attachment</a>`;
        }

        const div = document.createElement('div');
        div.classList.add('msg', msgClass);
        div.innerHTML = `<span class="sender-name">${senderLabel}${badge}</span>${content}`;
        messagesDiv.appendChild(div);
        scrollToBottom();

        if (isMe && myRole === 'mentor') {
            // Mentor will wait for AI results (which are emitted as 'receive_message' with status 'correct/wrong/complex')
            // No need to show thinking here anymore as it's handled by 'ai_thinking' event
        }
    });

    // New AI Thinking Event Listener
    socket.on('ai_thinking', (data) => {
        if (String(data.doubtId) === String(doubtId) && myRole === 'mentor') {
            showThinking();
        }
    });

    function showThinking() {
        const div = document.createElement('div');
        div.className = 'ai-thinking';
        div.id = 'ai-thinking';
        div.innerHTML = `
            <span style="font-size:15px;">🤖</span>
            <span>AI is reviewing your answer</span>
            <div class="thinking-dots"><span></span><span></span><span></span></div>`;
        messagesDiv.appendChild(div);
        scrollToBottom();
        setTimeout(() => { if (div.parentElement) div.remove(); }, 15000);
    }

    // AI Feedback Toast
    socket.on('ai_feedback', (result) => {
        if (myRole !== 'mentor') return;

        const thinking = document.getElementById('ai-thinking');
        if (thinking) thinking.remove();

        const isWrong   = !result.isCorrect;
        const isComplex = result.isCorrect && result.isTooComplex;

        const color = isWrong ? '#f87171' : isComplex ? '#fbbf24' : '#4ade80';
        const icon  = isWrong ? '❌'      : isComplex ? '⚠️'      : '✅';
        const title = isWrong ? 'Incorrect Answer Provided'
                    : isComplex ? 'Explanation Too Complex'
                    : 'Answer Verified Correct';

        const scoreColor = (n) => n >= 8 ? '#4ade80' : n >= 5 ? '#fbbf24' : '#f87171';

        const toast = document.createElement('div');
        toast.className = 'ai-toast';
        toast.style.borderLeft = `4px solid ${color}`;
        toast.innerHTML = `
            <div class="toast-header">
                <div class="toast-title" style="color:${color}">${icon} ${title}</div>
                <span class="toast-close" onclick="this.closest('.ai-toast').remove()">✕</span>
            </div>
            ${result.studentQuestion ? `<div class="toast-question">📌 Student's question: "${result.studentQuestion}"</div>` : ''}
            <div class="toast-body">${result.mentorFeedback || ''}</div>
            ${result.mistake ? `<div class="toast-mistake"><strong>Error:</strong> ${result.mistake}</div>` : ''}
            <div class="toast-scores">
                <div class="toast-score-item">
                    <div class="toast-score-label">Accuracy</div>
                    <div class="toast-score-value" style="color:${scoreColor(result.accuracyScore)}">${result.accuracyScore}<span style="font-size:11px;opacity:0.5">/10</span></div>
                </div>
                <div class="toast-score-item">
                    <div class="toast-score-label">Simplicity</div>
                    <div class="toast-score-value" style="color:${scoreColor(result.simplicityScore)}">${result.simplicityScore}<span style="font-size:11px;opacity:0.5">/10</span></div>
                </div>
            </div>`;
        document.body.appendChild(toast);
        setTimeout(() => { if (toast.parentElement) toast.remove(); }, 12000);
    });

        socket.on('chat_closed', (data) => {
            if (String(data.doubtId) !== String(doubtId)) return;
            if (myRole === 'student') {
                document.getElementById('rating-modal').style.display = 'flex';
            } else {
                window.location.href = "teacher_dashboard.php";
            }
        });
    } // end initSocketEvents

    // Session Management
    function mentorEndSession() {
        if (!confirm("Are you sure you want to end this session?")) return;
        const formData = new FormData();
        formData.append('doubt_id', doubtId);
        formData.append('action', 'close_only');
        fetch('close_session_handler.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (socket) {
                    socket.emit('close_chat', { doubtId });
                }
                setTimeout(() => { window.location.href = "teacher_dashboard.php"; }, 500);
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => {
            alert("Network error. Please check your connection.");
            console.error(err);
        });
    }

    function studentSubmitRating() {
        const formData = new FormData();
        formData.append('doubt_id', doubtId);
        formData.append('rating', document.getElementById('rating-stars').value);
        formData.append('action', 'rate_and_solve');
        fetch('close_session_handler.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert("Thank you for your feedback!");
                window.location.href = "student_dashboard.php";
            } else {
                alert("Something went wrong. Please try again.");
            }
        });
    }

    // Show rating modal on page load if doubt is Closed & student hasn't rated
    // (handles the case where student refreshed/missed the socket event)
    <?php
    if ($myRole === 'student') {
        $statusCheck = $conn->prepare(
            "SELECT status, rating FROM doubts WHERE doubt_id = ? AND student_id = ?"
        );
        $statusCheck->bind_param("ii", $doubt_id, $current_user_id);
        $statusCheck->execute();
        $statusRow = $statusCheck->get_result()->fetch_assoc();
        $statusCheck->close();
        if ($statusRow && $statusRow['status'] === 'Closed' && $statusRow['rating'] === null) {
            echo "document.getElementById('rating-modal').style.display = 'flex';";
        }
    }
    ?>
</script>
</body>
</html>
