<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_id   = $_SESSION['user_id'];
$student_name = $_SESSION['user_name'] ?? "Student";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | DoubtDock</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --pending:  #f39c12;
            --solved:   #27ae60;
            --accepted: #3498db;
            --closed:   #95a5a6;
            --bg: #f4f7fe;
        }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; scroll-behavior: smooth; }
        .main-wrapper { max-width: 800px; margin: 40px auto; padding: 0 20px; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .action-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .btn-action {
            padding: 12px 20px; border-radius: 12px; text-decoration: none;
            font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px;
            transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; border: none;
        }
        .btn-ask       { background: var(--primary); color: white; box-shadow: 0 4px 15px rgba(67,97,238,0.3); }
        .btn-ask:hover { background: #3451d1; transform: translateY(-3px); }
        .btn-resources       { background: white; color: var(--primary); border: 2px solid var(--primary); }
        .btn-resources:hover { background: #f0f2ff; transform: translateY(-3px); }
        .btn-logout       { background: white; color: #e53e3e; border: 2px solid #e53e3e; margin-left: auto; }
        .btn-logout:hover { background: #fff5f5; transform: translateY(-3px); }

        .search-container { position: relative; margin-bottom: 25px; animation: fadeInUp 0.5s ease backwards; }
        .search-container i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #a0aec0; }
        .search-input {
            width: 100%; padding: 12px 15px 12px 45px; border-radius: 12px;
            border: 2px solid transparent; background: white; font-size: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02); outline: none; transition: 0.3s;
            box-sizing: border-box;
        }
        .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(67,97,238,0.1); }

        .doubt-card {
            background: white; padding: 20px; border-radius: 15px; margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03); border-left: 6px solid #ddd;
            animation: fadeInUp 0.6s ease backwards; transition: 0.3s;
        }
        .doubt-card:hover { transform: scale(1.01); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }

        .subject-tag {
            display: inline-block; background: #e7f1ff; color: var(--primary);
            font-size: 11px; font-weight: 700; padding: 3px 10px;
            border-radius: 50px; margin-bottom: 8px;
        }
        .status-pill {
            font-size: 11px; font-weight: 800; padding: 5px 12px;
            border-radius: 50px; display: inline-block; margin-bottom: 8px; margin-left: 6px;
        }
        .status-PENDING  { background: #fff4e5; color: var(--pending); }
        .status-ACCEPTED { background: #e0e7ff; color: var(--accepted); }
        .status-SOLVED   { background: #e6fffa; color: var(--solved); }
        .status-CLOSED   { background: #f0f0f0; color: var(--closed); }

        .topic-text {
            display: block; font-size: 16px; font-weight: 600;
            color: #2d3748; margin-bottom: 6px;
        }
        .desc-text { font-size: 13px; color: #718096; margin-bottom: 12px; line-height: 1.5; }

        .chat-link {
            color: var(--primary); font-weight: 700; text-decoration: none;
            font-size: 14px; display: inline-flex; align-items: center; gap: 5px;
        }
        .chat-link:hover { text-decoration: underline; }

        .no-results { text-align: center; padding: 40px; display: none; color: #a0aec0; }
        .empty-state { text-align: center; padding: 50px 20px; color: #a0aec0; }
        .empty-state i { font-size: 3rem; margin-bottom: 15px; display: block; }
    </style>
</head>
<body>

<div class="main-wrapper">
    <div style="margin-bottom: 25px; animation: fadeInUp 0.4s ease; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <div>
            <h2 style="margin: 0; color: #1a202c;">Hello, <?php echo htmlspecialchars($student_name); ?>! 👋</h2>
            <p style="color: #718096; margin:4px 0 0;">Track your doubts or browse study resources.</p>
        </div>
        <a href="logout.php" class="btn-action btn-logout" style="text-decoration:none;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <div class="action-bar" style="animation: fadeInUp 0.5s ease backwards;">
        <a href="ask_doubt.php" class="btn-action btn-ask">
            <i class="fas fa-plus-circle"></i> Ask New Doubt
        </a>
        <a href="view_resources.php" class="btn-action btn-resources">
            <i class="fas fa-book"></i> View Resources
        </a>
    </div>

    <div class="search-container">
        <input type="text" id="historySearch" class="search-input"
               placeholder="Search your previous doubts..."
               onkeyup="filterHistory()">
        <i class="fas fa-search"></i>
    </div>

    <h3 style="color: #4a5568; border-bottom: 2px solid #edf2f7; padding-bottom: 10px; margin-bottom: 20px;">
        Doubt History
    </h3>

    <div id="historyList">
        <?php
        $fetch = mysqli_query($conn,
            "SELECT * FROM doubts WHERE student_id = '$student_id' ORDER BY doubt_id DESC"
        );

        $count = mysqli_num_rows($fetch);
        if ($count === 0):
        ?>
        <div class="empty-state">
            <i class="fas fa-question-circle"></i>
            <h3>No doubts yet!</h3>
            <p>Click "Ask New Doubt" to get help from a mentor.</p>
        </div>
        <?php else:
        $delay = 0;
        while ($row = mysqli_fetch_assoc($fetch)):
            $status    = strtoupper($row['status'] ?? 'PENDING');
            $topic     = $row['topic'] ?? '';
            $subject   = $row['subject'] ?? '';
            $desc      = $row['description'] ?? '';
            $delay += 0.1;

            // Border color per status
            $border_colors = [
                'PENDING'  => 'var(--pending)',
                'ACCEPTED' => 'var(--accepted)',
                'SOLVED'   => 'var(--solved)',
                'CLOSED'   => 'var(--closed)',
            ];
            $border = $border_colors[$status] ?? '#ddd';

            // Status icon
            $icons = [
                'PENDING'  => 'fa-clock',
                'ACCEPTED' => 'fa-user-check',
                'SOLVED'   => 'fa-check-circle',
                'CLOSED'   => 'fa-times-circle',
            ];
            $icon = $icons[$status] ?? 'fa-circle';
        ?>
        <div class="doubt-card"
             style="border-left-color: <?= $border ?>; animation-delay: <?= $delay ?>s">
            <div>
                <?php if (!empty($subject)): ?>
                    <span class="subject-tag"><?= htmlspecialchars($subject) ?></span>
                <?php endif; ?>
                <span class="status-pill status-<?= $status ?>">
                    <i class="fas <?= $icon ?>"></i> <?= $status ?>
                </span>
            </div>
            <?php if (!empty($topic)): ?>
                <span class="topic-text"><?= htmlspecialchars($topic) ?></span>
            <?php endif; ?>
            <span class="desc-text"><?= htmlspecialchars($desc) ?></span>

            <?php if ($status === 'ACCEPTED'): ?>
                <a href="chat_ui.php?id=<?= $row['doubt_id'] ?>" class="chat-link">
                    <i class="fas fa-comments"></i> Open Chat Room
                    <i class="fas fa-arrow-right" style="font-size:10px;"></i>
                </a>
            <?php elseif ($status === 'PENDING'): ?>
                <span style="color:#a0aec0;font-size:13px;">
                    <i class="fas fa-hourglass-half"></i> Waiting for a mentor...
                </span>
            <?php elseif ($status === 'SOLVED' || $status === 'CLOSED'): ?>
                <span style="color:#27ae60;font-size:13px;">
                    <i class="fas fa-check"></i> Session completed
                </span>
            <?php endif; ?>
        </div>
        <?php endwhile; endif; ?>
    </div>

    <div id="noResults" class="no-results">
        <i class="fas fa-search fa-3x" style="margin-bottom: 15px;"></i>
        <p>No doubts found matching your search.</p>
    </div>
</div>

<script>
function filterHistory() {
    const input   = document.getElementById('historySearch').value.toLowerCase();
    const cards   = document.getElementsByClassName('doubt-card');
    const noRes   = document.getElementById('noResults');
    let   found   = false;

    for (let i = 0; i < cards.length; i++) {
        const text = cards[i].innerText.toLowerCase();
        if (text.includes(input)) {
            cards[i].style.display = "";
            found = true;
        } else {
            cards[i].style.display = "none";
        }
    }
    noRes.style.display = found ? "none" : "block";
}
</script>

</body>
</html>

