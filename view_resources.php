<?php
session_start();
include 'db.php';
require_once 'config.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'mentor')) {
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['role'];
$student_branch = mysqli_real_escape_string($conn, $_SESSION['user_branch'] ?? 'All');

if ($user_role === 'mentor') {
    $sql = "SELECT r.*, u.name as mentor_name 
            FROM resources r 
            JOIN users u ON r.mentor_id = u.user_id 
            ORDER BY r.uploaded_at DESC";
} else {
    $sql = "SELECT r.*, u.name as mentor_name 
            FROM resources r 
            JOIN users u ON r.mentor_id = u.user_id 
            WHERE r.branch = '$student_branch' OR r.branch = 'All'
            ORDER BY r.uploaded_at DESC";
}

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources Library | DoubtDock</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container { max-width: 900px; margin: 0 auto; padding: 2rem; }
        .resource-grid { display: grid; gap: 1rem; }
        .resource-card { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; }
        .search-container { position: relative; margin-bottom: 2rem; }
        .search-container i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        .search-container input { padding-left: 3rem; }
        .subject-tag { background: var(--primary-glow); color: var(--primary); padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.75rem; font-weight: 700; }
    </style>
</head>
<body>
    <div class="bg-mesh-container"></div>
    <nav class="nav-modern">
        <div class="nav-logo"><i class="fa-solid fa-graduation-cap"></i> DoubtDock</div>
        <a href="<?= $user_role === 'student' ? 'student_dashboard.php' : 'teacher_dashboard.php' ?>" class="btn-modern btn-secondary-modern" style="padding: 0.5rem 1rem;">Back to Dashboard</a>
    </nav>

    <div class="container animate-up">
        <div style="text-align: center; margin-bottom: 3rem;">
            <h1>Knowledge Repository</h1>
            <p class="text-muted">Curated study materials for your branch</p>
        </div>

        <div class="search-container">
            <i class="fa-solid fa-search"></i>
            <input type="text" id="resSearch" class="input-modern" placeholder="Search resources..." onkeyup="searchResources()">
        </div>

        <div id="resourceList" class="resource-grid">
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <div class="card-premium resource-card">
                        <div>
                            <span class="subject-tag mb-2"><?= htmlspecialchars($row['subject']) ?></span>
                            <h3 class="res-title"><?= htmlspecialchars($row['title']) ?></h3>
                            <p class="text-muted" style="font-size: 0.875rem;">Uploaded by <?= htmlspecialchars($row['mentor_name']) ?></p>
                        </div>
                        <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="btn-modern btn-primary-modern">
                            <i class="fa-solid fa-file-pdf"></i> View PDF
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="card-premium text-center" style="padding: 4rem;">
                    <i class="fa-solid fa-folder-open mb-4" style="font-size: 3rem; color: var(--border-color);"></i>
                    <p class="text-muted">No resources available for this branch yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <div id="noMatch" class="card-premium text-center" style="display: none; padding: 4rem;">
            <i class="fa-solid fa-magnifying-glass mb-4" style="font-size: 3rem; color: var(--border-color);"></i>
            <p class="text-muted">No matching resources found.</p>
        </div>
    </div>

    <script>
        function searchResources() {
            const input = document.getElementById('resSearch').value.toLowerCase();
            const cards = document.getElementsByClassName('resource-card');
            const noMatch = document.getElementById('noMatch');
            let found = false;
            for (let i = 0; i < cards.length; i++) {
                const title = cards[i].querySelector('.res-title').innerText.toLowerCase();
                if (title.includes(input)) {
                    cards[i].style.display = "flex";
                    found = true;
                } else {
                    cards[i].style.display = "none";
                }
            }
            noMatch.style.display = found ? "none" : "block";
        }
    </script>
</body>
</html>
