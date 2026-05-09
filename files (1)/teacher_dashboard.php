<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mentor') {
    header("Location: login.php");
    exit();
}

$mentor_subject = $_SESSION['mentor_expertise'] ?? '';
$branch         = $_SESSION['mentor_branch']     ?? '';
$mentor_id      = $_SESSION['user_id']           ?? 0;

// If session is empty, fetch from DB
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

// Build OR conditions for each subject in comma-separated list
// e.g. "DBMS,Python,OS" → WHERE (subject='DBMS' OR subject='Python' OR subject='OS')
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

$sql    = "SELECT * FROM doubts WHERE ($doubts_condition) AND status = 'Pending' ORDER BY doubt_id DESC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Dashboard | DoubtDock</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #007bff; --success: #28a745; --warning: #ffc107; --danger: #dc3545; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1000px; margin: auto; }

        .header {
            display: flex; justify-content: space-between; align-items: center;
            background: white; padding: 20px 30px; border-radius: 20px; margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .action-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px; margin-bottom: 30px;
        }
        .action-card {
            background: white; padding: 20px; border-radius: 18px;
            text-align: left; transition: 0.3s; text-decoration: none; color: inherit;
            display: flex; align-items: center; gap: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid #eee;
        }
        .action-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); }
        .icon-box {
            width: 50px; height: 50px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 20px;
        }
        .card {
            background: white; padding: 25px; margin-bottom: 20px; border-radius: 18px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); display: flex;
            justify-content: space-between; align-items: center;
            border-left: 6px solid var(--primary); transition: 0.3s;
        }
        .subject-tag {
            background: #e7f1ff; color: var(--primary);
            padding: 6px 15px; border-radius: 50px; font-size: 12px; font-weight: 700;
        }
        .btn-claim {
            background: var(--success); color: white; padding: 12px 25px;
            text-decoration: none; border-radius: 10px; font-weight: bold;
            transition: 0.3s; white-space: nowrap;
        }
        .btn-claim:hover { background: #218838; }
        .badge-live {
            display: inline-block; width: 10px; height: 10px;
            background: var(--success); border-radius: 50%; margin-right: 5px;
            animation: blink 1.5s infinite;
        }
        @keyframes blink { 0%,100% { opacity: 1; } 50% { opacity: 0.3; } }
        .logout-btn {
            color: var(--danger); font-weight: 600; text-decoration: none;
            border: 1px solid var(--danger); padding: 8px 18px; border-radius: 10px;
        }
        .session-warn {
            background: #fff3cd; border: 1px solid #ffc107; color: #856404;
            padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;
        }
        .subject-chips { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 6px; }
        .subject-chip {
            background: #e7f1ff; color: var(--primary);
            font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 50px;
        }
    </style>
</head>
<body>
<div class="container">

    <?php if (empty($mentor_subject)): ?>
    <div class="session-warn">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <strong>Session incomplete:</strong> Your subject expertise was not found.
        Please <a href="logout.php">logout</a> and log in again.
    </div>
    <?php endif; ?>

    <div class="header">
        <div>
            <h2 style="margin:0;">Hello, <span style="color:var(--primary);"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Mentor'); ?></span> 👋</h2>
            <small style="color:#666;">
                <span class="badge-live"></span>
                Mentor Dashboard &bull; <b><?php echo htmlspecialchars($branch); ?></b>
            </small>
            <?php if (!empty($mentor_subject)): ?>
            <div class="subject-chips">
                <?php foreach (array_map('trim', explode(',', $mentor_subject)) as $s): ?>
                    <?php if (!empty($s)): ?>
                    <span class="subject-chip"><?php echo htmlspecialchars($s); ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <a href="logout.php" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>

    <div class="action-grid">
        <a href="teacher_dashboard.php" class="action-card" style="border-bottom:4px solid var(--primary);">
            <div class="icon-box" style="background:#e7f1ff;color:var(--primary);"><i class="fa-solid fa-comments"></i></div>
            <div><h4 style="margin:0;">Pending Doubts</h4><small>Solve Student Queries</small></div>
        </a>
        <a href="upload_resources.php" class="action-card">
            <div class="icon-box" style="background:#e6fcf5;color:var(--success);"><i class="fa-solid fa-cloud-arrow-up"></i></div>
            <div><h4 style="margin:0;">Upload Resources</h4><small>Share Notes &amp; PDFs</small></div>
        </a>
        <a href="manage_resources.php" class="action-card">
            <div class="icon-box" style="background:#fff9db;color:var(--warning);"><i class="fa-solid fa-folder-open"></i></div>
            <div><h4 style="margin:0;">My Library</h4><small>Manage Documents</small></div>
        </a>
        <a href="view_resources.php" class="action-card">
            <div class="icon-box" style="background:#fff9db;color:var(--warning);"><i class="fa-solid fa-eye"></i></div>
            <div><h4 style="margin:0;">View Resources</h4><small>Browse All Resources</small></div>
        </a>
    </div>

    <h3 style="color:#444;margin-bottom:20px;">
        <i class="fa-solid fa-bolt" style="color:var(--warning);"></i> Live Doubt Feed
    </h3>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <div class="card">
            <div style="flex:1;padding-right:20px;">
                <span class="subject-tag"><?php echo htmlspecialchars($row['subject']); ?></span>
                <h3 style="margin:15px 0 10px;color:#333;"><?php echo htmlspecialchars($row['topic']); ?></h3>
                <p style="color:#555;line-height:1.6;font-size:14px;"><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                <small style="color:#999;">Doubt ID: #<?php echo $row['doubt_id']; ?></small>
            </div>
            <div>
                <a href="claim_doubt.php?id=<?php echo $row['doubt_id']; ?>" class="btn-claim">
                    Accept &amp; Chat <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align:center;padding:60px 20px;background:white;border-radius:20px;box-shadow:0 5px 15px rgba(0,0,0,0.05);">
            <img src="https://cdn-icons-png.flaticon.com/512/2618/2618245.png" width="80" style="opacity:0.2;margin-bottom:20px;">
            <h3 style="color:#bbb;">No Pending Doubts Right Now</h3>
            <p style="color:#999;">
                <?php if (!empty($mentor_subject)): ?>
                    Subjects: <b><?php echo htmlspecialchars($mentor_subject); ?></b>
                    — new doubts will appear here automatically.
                <?php else: ?>
                    No subject is set for your account. Please logout and log in again.
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>

</div>
</body>
</html>

