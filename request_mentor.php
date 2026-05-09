<?php
// ── request_mentor.php ────────────────────────────────────────────────────────
// When a student clicks "Connect" on an online mentor card, they land here.
// We pre-fill the Ask a Doubt form with that mentor's subject, or redirect
// directly to ask_doubt.php if no specific mentor logic is needed.
session_start();
include 'db.php';
require_once 'config.php';

// Must be a logged-in student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$mentor_id = isset($_GET['mentor_id']) ? intval($_GET['mentor_id']) : 0;

if (!$mentor_id) {
    header("Location: ask_doubt.php");
    exit();
}

// Fetch the mentor's name and subject
$stmt = $conn->prepare(
    "SELECT name, subject_experties FROM users WHERE user_id = ? AND role = 'mentor'"
);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$mentor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$mentor) {
    header("Location: ask_doubt.php");
    exit();
}

$mentor_name     = htmlspecialchars($mentor['name']);
$mentor_subjects = array_filter(array_map('trim', explode(',', $mentor['subject_experties'] ?? '')));
$student_name    = $_SESSION['user_name'] ?? 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connect with Mentor | DoubtDock</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b5bdb;
            --primary-light: #eef2ff;
            --success: #2f9e44;
            --bg: #f8f9fd;
            --surface: #ffffff;
            --border: #e8ecf4;
            --text-1: #1a1f36;
            --text-2: #4a5278;
            --text-3: #8892b0;
            --radius-md: 16px;
            --radius-lg: 22px;
            --shadow-lg: 0 20px 60px rgba(59,91,219,0.15);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 24px 16px;
        }
        body::before {
            content: '';
            position: fixed; top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, var(--primary), #7048e8, #f76707);
        }
        .card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            width: 100%; max-width: 500px;
            overflow: hidden;
            animation: fadeUp 0.45s ease both;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .card-banner {
            background: linear-gradient(135deg, var(--primary), #4c6ef5);
            padding: 30px 32px 26px;
            color: white;
        }
        .mentor-avatar {
            width: 54px; height: 54px;
            background: rgba(255,255,255,0.2);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; font-weight: 800;
            margin-bottom: 14px;
            backdrop-filter: blur(8px);
        }
        .banner-title { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .banner-sub   { font-size: 13px; opacity: 0.8; }
        .card-body    { padding: 28px 32px 32px; }
        .info-row {
            display: flex; align-items: flex-start; gap: 12px;
            background: var(--primary-light);
            border: 1.5px solid #c5d0fa;
            border-radius: var(--radius-md);
            padding: 16px 18px;
            margin-bottom: 24px;
        }
        .info-row i { color: var(--primary); font-size: 18px; margin-top: 2px; flex-shrink: 0; }
        .info-text strong { font-size: 14px; color: var(--text-1); display: block; margin-bottom: 6px; }
        .subject-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
        .chip {
            background: white; border: 1.5px solid #c5d0fa;
            color: var(--primary); font-size: 12px; font-weight: 700;
            padding: 4px 12px; border-radius: 50px;
        }
        .label {
            font-size: 13px; font-weight: 700; color: var(--text-2);
            margin-bottom: 8px; display: block;
        }
        select {
            width: 100%; padding: 12px 14px;
            border: 2px solid var(--border); border-radius: 10px;
            font-size: 14px; font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 500; color: var(--text-1);
            background: #f3f5fb; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            margin-bottom: 20px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238892b0' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 36px;
        }
        select:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(59,91,219,0.1); background-color: white; }
        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), #4c6ef5);
            color: white; border: none; padding: 14px;
            border-radius: 10px; font-size: 15px; font-weight: 800;
            cursor: pointer; font-family: 'Plus Jakarta Sans', sans-serif;
            box-shadow: 0 5px 20px rgba(59,91,219,0.35);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(59,91,219,0.45); }
        .back-link {
            text-align: center; margin-top: 16px;
            font-size: 13px; color: var(--text-3);
        }
        .back-link a { color: var(--primary); font-weight: 700; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="card">
    <div class="card-banner">
        <div class="mentor-avatar"><?= strtoupper(substr($mentor_name, 0, 1)) ?></div>
        <div class="banner-title">Connect with <?= $mentor_name ?></div>
        <div class="banner-sub">This mentor is online and ready to help you!</div>
    </div>

    <div class="card-body">

        <div class="info-row">
            <i class="fas fa-book-open"></i>
            <div class="info-text">
                <strong>Subjects this mentor can help with:</strong>
                <div class="subject-chips">
                    <?php foreach ($mentor_subjects as $s): ?>
                        <span class="chip"><?= htmlspecialchars($s) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <form action="ask_doubt.php" method="GET">
            <?php if (!empty($mentor_subjects)): ?>
                <label class="label" for="preselect-subject">
                    Select the subject your doubt is about:
                </label>
                <select name="preselect_subject" id="preselect-subject" required>
                    <option value="" disabled selected>Choose a subject</option>
                    <?php foreach ($mentor_subjects as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <input type="hidden" name="mentor_id" value="<?= $mentor_id ?>">

            <button type="submit" class="btn-primary">
                <i class="fas fa-paper-plane"></i>
                Post a Doubt to This Mentor
            </button>
        </form>

        <div class="back-link">
            <a href="student_dashboard.php">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

</body>
</html>

