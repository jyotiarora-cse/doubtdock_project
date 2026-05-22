<?php
session_start();
include('db.php');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_id   = intval($_SESSION['user_id']);
$student_name = $_SESSION['user_name'] ?? 'Student';

$doubt_stmt = $conn->prepare(
    "SELECT d.doubt_id, d.description, d.topic, d.subject, d.status, d.created_at, u.name as mentor_name 
     FROM doubts d
     LEFT JOIN users u ON d.mentor_id = u.user_id
     WHERE d.student_id = ? 
     ORDER BY d.created_at DESC"
);
$doubt_stmt->bind_param("i", $student_id);
$doubt_stmt->execute();
$doubt_result = $doubt_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | DoubtDock</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-layout {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .welcome-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
        }
        .welcome-section h1 {
            font-size: 2.5rem;
        }
        .welcome-section h1 em {
            font-style: normal;
            color: var(--primary);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        .stat-card {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: var(--primary-glow);
            color: var(--primary);
        }
        .doubt-history-grid {
            display: grid;
            gap: 1rem;
        }
        .doubt-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-pending { background: #fef3c7; color: #b45309; }
        .status-solved { background: #dcfce7; color: #15803d; }
        .status-active { background: #e0e7ff; color: #4338ca; }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="bg-mesh-container"></div>
    
    <nav class="nav-modern">
        <div class="nav-logo">
            <i class="fa-solid fa-graduation-cap"></i> DoubtDock
        </div>
        <div class="flex items-center gap-4">
            <span class="text-muted mr-4">Logged in as <strong><?= htmlspecialchars($student_name) ?></strong></span>
            <a href="logout.php" class="btn-modern btn-secondary-modern" style="padding: 0.5rem 1rem;">Logout</a>
        </div>
    </nav>

    <div class="dashboard-layout animate-up">
        <div class="welcome-section">
            <div>
                <h1>Welcome Back, <em><?= htmlspecialchars($student_name) ?></em>! 👋</h1>
                <p class="text-muted">What would you like to learn today?</p>
            </div>
            <div class="action-buttons">
                <a href="ask_doubt.php" class="btn-modern btn-primary-modern">
                    <i class="fa-solid fa-plus"></i> Ask a Doubt
                </a>
                <a href="view_resources.php" class="btn-modern btn-secondary-modern">
                    <i class="fa-solid fa-book"></i> Resources
                </a>
            </div>
        </div>

        <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 2rem;">
            <!-- Doubts History -->
            <div class="section">
                <h2 class="mb-4">Recent Doubts</h2>
                <div class="doubt-history-grid">
                    <?php if ($doubt_result->num_rows > 0): ?>
                        <?php while ($row = $doubt_result->fetch_assoc()): 
                            $status = strtolower($row['status']);
                            $badgeClass = "status-" . $status;
                            $date = date('d M, Y', strtotime($row['created_at']));
                        ?>
                            <div class="card-premium doubt-item" style="flex-direction: column; align-items: flex-start; gap: 0.5rem;">
                                <div class="flex items-center justify-between" style="width: 100%;">
                                    <div class="flex items-center gap-2">
                                        <span class="status-badge <?= $badgeClass ?>"><?= $row['status'] ?></span>
                                        <span class="subject-tag"><?= htmlspecialchars($row['subject']) ?></span>
                                    </div>
                                    <span class="text-muted" style="font-size: 0.75rem;"><i class="fa-solid fa-calendar"></i> <?= $date ?></span>
                                </div>
                                
                                <h3 style="font-size: 1.2rem; margin: 0.25rem 0;"><?= htmlspecialchars($row['topic']) ?></h3>
                                <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 0.5rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= htmlspecialchars($row['description']) ?>
                                </p>

                                <div class="flex items-center justify-between" style="width: 100%; border-top: 1px solid var(--border-color); padding-top: 0.75rem; margin-top: 0.25rem;">
                                    <div class="flex items-center gap-2">
                                        <div class="stat-icon" style="width: 24px; height: 24px; font-size: 0.7rem;">
                                            <i class="fa-solid fa-user-tie"></i>
                                        </div>
                                        <span style="font-size: 0.85rem; font-weight: 600;">
                                            <?= $row['mentor_name'] ? 'Mentor: ' . htmlspecialchars($row['mentor_name']) : 'Awaiting Mentor...' ?>
                                        </span>
                                    </div>
                                    <?php if ($status !== 'pending'): ?>
                                        <a href="chat_ui.php?id=<?= $row['doubt_id'] ?>" class="btn-modern btn-primary-modern" style="padding: 0.4rem 1rem; font-size: 0.8rem;">
                                            Open Conversation <i class="fa-solid fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="card-premium text-center" style="padding: 3rem;">
                            <i class="fa-solid fa-folder-open mb-4" style="font-size: 3rem; color: var(--border-color);"></i>
                            <p class="text-muted">No doubts posted yet. Start by asking your first question!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Online Mentors -->
            <div class="section">
                <h2 class="mb-4">Online Mentors</h2>
                <div id="mentors-list" class="grid" style="gap: 1rem;">
                    <!-- Loading via JS -->
                    <div class="card-premium" style="text-align: center; padding: 1rem;">
                        <i class="fa-solid fa-spinner fa-spin"></i> Loading...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= NODE_URL ?>/socket.io/socket.io.js"></script>
    <script>
        const socket = io('<?= NODE_URL ?>');

        function loadOnlineMentors() {
            fetch('<?= NODE_URL ?>/online-mentors')
                .then(r => r.json())
                .then(mentors => {
                    const list = document.getElementById('mentors-list');
                    if (!mentors || mentors.length === 0) {
                        list.innerHTML = `<div class="card-premium text-muted" style="font-size: 0.875rem; text-align: center; padding: 20px;">
                            <i class="fa-solid fa-user-slash mb-2" style="display:block; font-size:1.5rem; opacity:0.5;"></i>
                            No mentors online right now
                        </div>`;
                        return;
                    }
                    list.innerHTML = mentors.map(m => `
                        <div class="card-premium" style="padding: 1rem; display: flex; align-items: center; gap: 1rem; border-left: 3px solid var(--success);">
                            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 1rem; flex-shrink: 0;">
                                ${m.name.charAt(0).toUpperCase()}
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 700; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${m.name}</div>
                                <div class="text-muted" style="font-size: 0.75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    ${m.subject || 'General Expertise'}
                                </div>
                                ${m.rating ? `
                                    <div style="font-size: 11px; color: #f59e0b; margin-top: 2px;">
                                        <i class="fa-solid fa-star"></i> ${m.rating} <span style="color:#94a3b8">(${m.review_count} reviews)</span>
                                    </div>
                                ` : ''}
                            </div>
                            <a href="request_mentor.php?mentor_id=${m.id}" class="btn-modern btn-primary-modern" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; flex-shrink: 0;">Connect</a>
                        </div>
                    `).join('');
                })
                .catch((err) => {
                    console.error("Mentor fetch error:", err);
                    document.getElementById('mentors-list').innerHTML = `<div class="card-premium text-muted" style="color:var(--danger);">
                        <i class="fa-solid fa-triangle-exclamation"></i> Chat Server Offline
                    </div>`;
                });
        }
        
        socket.on('connect', () => {
            console.log("Connected to Socket Server");
            loadOnlineMentors();
        });

        socket.on('mentors_updated', () => {
            console.log("Mentors list changed, refreshing...");
            loadOnlineMentors();
        });

        // Fallback for disconnects
        socket.on('disconnect', () => {
            console.warn("Disconnected from Socket Server");
        });
    </script>
</body>
</html>
