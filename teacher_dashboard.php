<?php
session_start();
include 'db.php';
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mentor') {
    header("Location: login.php");
    exit();
}

$mentor_subject = $_SESSION['mentor_expertise'] ?? '';
$branch         = $_SESSION['mentor_branch']     ?? '';
$mentor_id      = $_SESSION['user_id']           ?? 0;
$mentor_name    = $_SESSION['user_name']         ?? 'Mentor';

if (empty($mentor_subject) || empty($branch)) {
    $uid = (int)$mentor_id;
    $q   = mysqli_query($conn, "SELECT branch, subject_experties FROM users WHERE user_id = $uid LIMIT 1");
    if ($q && $row_u = mysqli_fetch_assoc($q)) {
        $branch         = $row_u['branch']            ?? '';
        $mentor_subject = $row_u['subject_experties'] ?? '';
        $_SESSION['mentor_branch']    = $branch;
        $_SESSION['mentor_expertise'] = $mentor_subject;
    }
}

$subjects_arr = [];
if (!empty($mentor_subject)) {
    $subjects_arr = array_map('trim', explode(',', $mentor_subject));
    $conditions   = [];
    foreach ($subjects_arr as $subj) {
        if (!empty($subj)) {
            $safe = mysqli_real_escape_string($conn, $subj);
            $conditions[] = "subject = '$safe'";
        }
    }
    $doubts_condition = !empty($conditions) ? implode(' OR ', $conditions) : '1=0';
} else {
    $doubts_condition = '1=0';
}

$sql    = "SELECT * FROM doubts 
           WHERE ($doubts_condition) 
           AND status = 'Pending' 
           AND last_heartbeat > NOW() - INTERVAL 20 SECOND
           ORDER BY doubt_id DESC";
$result = mysqli_query($conn, $sql);
if (!$result) die("Query Failed: " . mysqli_error($conn));

$subjects_json = json_encode(array_values(array_filter($subjects_arr)));
$pending_count = mysqli_num_rows($result);

// Fetch recently solved/active doubts for this mentor
$history_sql = "SELECT d.*, u.name as student_name 
                FROM doubts d 
                JOIN users u ON d.student_id = u.user_id 
                WHERE d.mentor_id = $mentor_id AND d.status NOT IN ('Solved', 'Closed')
                ORDER BY d.created_at DESC LIMIT 5";
$history_result = mysqli_query($conn, $history_sql);

$resource_success = $_SESSION['resource_success'] ?? '';
unset($_SESSION['resource_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Dashboard | DoubtDock</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-layout { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 3rem; }
        .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 3rem; }
        .action-card { padding: 1.5rem; display: flex; align-items: center; gap: 1rem; cursor: pointer; transition: all 0.3s; }
        .action-card i { font-size: 2rem; color: var(--primary); }
        .doubt-feed { display: grid; gap: 1.5rem; }
        .doubt-card { border-left: 4px solid var(--primary); padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; }
        .subject-badge { background: var(--primary-glow); color: var(--primary); padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.75rem; font-weight: 700; }
        .online-status { display: flex; align-items: center; gap: 0.5rem; color: var(--success); font-size: 0.875rem; font-weight: 600; }
        .dot-blink { width: 8px; height: 8px; background: var(--success); border-radius: 50%; animation: pulse 2s infinite; }
    </style>
</head>
<body>
    <div class="bg-mesh-container"></div>
    <nav class="nav-modern">
        <div class="nav-logo"><i class="fa-solid fa-graduation-cap"></i> DoubtDock</div>
        <div class="flex items-center">
            <div class="online-status mr-6">
                <div class="dot-blink"></div> Online & Visible
            </div>
            <a href="logout.php" class="btn-modern btn-secondary-modern" style="padding: 0.5rem 1rem;">Logout</a>
        </div>
    </nav>

    <div class="dashboard-layout animate-up">
        <?php if ($resource_success): ?>
            <div class="card-premium mb-4" style="background: var(--primary-glow); border-color: var(--primary); color: var(--primary); padding: 1rem;">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($resource_success) ?>
            </div>
        <?php endif; ?>

        <div class="header-section">
            <div>
                <h1>Hello, <em><?= htmlspecialchars($mentor_name) ?></em>! 👋</h1>
                <p class="text-muted">You are assigned to <strong><?= htmlspecialchars($branch) ?></strong> branch.</p>
                <div class="flex gap-2 mt-2">
                    <?php foreach ($subjects_arr as $s): ?>
                        <span class="subject-badge"><?= htmlspecialchars($s) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="action-grid">
            <a href="upload_resources.php" class="card-premium action-card">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                <div>
                    <h3 style="font-size: 1.1rem;">Upload Resources</h3>
                    <p class="text-muted" style="font-size: 0.875rem;">Share notes and PDFs</p>
                </div>
            </a>
            <a href="manage_resources.php" class="card-premium action-card">
                <i class="fa-solid fa-folder-open"></i>
                <div>
                    <h3 style="font-size: 1.1rem;">Manage Library</h3>
                    <p class="text-muted" style="font-size: 0.875rem;">Your uploaded materials</p>
                </div>
            </a>
            <a href="view_resources.php" class="card-premium action-card">
                <i class="fa-solid fa-eye"></i>
                <div>
                    <h3 style="font-size: 1.1rem;">Browse All</h3>
                    <p class="text-muted" style="font-size: 0.875rem;">View all student resources</p>
                </div>
            </a>
        </div>

        <h2 class="mb-4"><i class="fa-solid fa-bolt" style="color: var(--accent);"></i> Pending Doubts (<span id="pending-count"><?= $pending_count ?></span>)</h2>
        
        <div id="doubt-feed" class="doubt-feed mb-4">
            <?php if ($pending_count > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="card-premium doubt-card" id="doubt-<?= $row['doubt_id'] ?>">
                        <div style="flex: 1;">
                            <span class="subject-badge mb-2"><?= htmlspecialchars($row['subject']) ?></span>
                            <h3 class="mb-2"><?= htmlspecialchars($row['topic']) ?></h3>
                            <p class="text-muted" style="font-size: 0.9rem;"><?= nl2br(htmlspecialchars($row['description'])) ?></p>
                        </div>
                        <a href="claim_doubt.php?id=<?= $row['doubt_id'] ?>" class="btn-modern btn-primary-modern">
                            Accept & Chat <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="card-premium text-center" style="padding: 4rem;">
                    <i class="fa-solid fa-mug-hot mb-4" style="font-size: 3rem; color: var(--border-color);"></i>
                    <p class="text-muted">No pending doubts right now. Grab a coffee!</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if (mysqli_num_rows($history_result) > 0): ?>
            <h2 class="mb-4 mt-4"><i class="fa-solid fa-history" style="color: var(--primary);"></i> My Recent Chats</h2>
            <div class="doubt-feed">
                <?php while ($h = mysqli_fetch_assoc($history_result)): ?>
                    <div class="card-premium doubt-card" style="border-left-color: var(--success);">
                        <div style="flex: 1;">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="status-badge" style="background: #dcfce7; color: #15803d;"><?= $h['status'] ?></span>
                                <span class="subject-tag"><?= htmlspecialchars($h['subject']) ?></span>
                            </div>
                            <h3 class="mb-1"><?= htmlspecialchars($h['topic']) ?></h3>
                            <p class="text-muted" style="font-size: 0.85rem;">Student: <?= htmlspecialchars($h['student_name']) ?></p>
                        </div>
                        <a href="chat_ui.php?id=<?= $h['doubt_id'] ?>" class="btn-modern btn-secondary-modern">
                            View Conversation <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="<?= NODE_URL ?>/socket.io/socket.io.js"></script>
    <script>
        const socket = io('<?= NODE_URL ?>');
        const mySubjects = <?= $subjects_json ?>.map(s => s.trim().toLowerCase());
        
        socket.on('connect', () => {
            socket.emit('mentor_online', {
                id: "<?= $mentor_id ?>",
                name: "<?= addslashes($mentor_name) ?>",
                subject: "<?= addslashes($mentor_subject) ?>"
            });
        });

        socket.on('new_doubt', (data) => {
            if (!mySubjects.includes(data.subject.trim().toLowerCase())) return;
            location.reload(); // Refresh to show new doubt with full details
        });

        socket.on('doubt_claimed', (data) => {
            const el = document.getElementById('doubt-' + data.doubt_id);
            if (el) el.remove();
        });
    </script>
</body>
</html>
