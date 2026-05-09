<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// FIX: subject_experties is comma-separated — split in PHP to get individual unique subjects
// e.g. "DBMS,Python,OS" → individual dropdown options [DBMS] [Python] [OS]
$subject_query  = "SELECT subject_experties FROM users
                   WHERE role = 'mentor'
                   AND subject_experties IS NOT NULL
                   AND subject_experties != ''";
$subject_result = mysqli_query($conn, $subject_query);

$all_subjects = [];
while ($row = mysqli_fetch_assoc($subject_result)) {
    $parts = array_map('trim', explode(',', $row['subject_experties']));
    foreach ($parts as $s) {
        if (!empty($s)) $all_subjects[] = $s;
    }
}
$all_subjects = array_unique($all_subjects);
sort($all_subjects);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ask a Doubt | DoubtDock</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4A90E2; --success: #28a745; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea22 0%, #764ba222 100%), #f0f4f8;
            min-height: 100vh; display: flex; justify-content: center;
            align-items: center; padding: 20px;
        }
        .form-card {
            background: white; padding: 40px; border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
            width: 100%; max-width: 520px;
            animation: slideUp 0.5s ease both;
        }
        @keyframes slideUp {
            from { opacity:0; transform:translateY(20px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .card-header { text-align: center; margin-bottom: 32px; }
        .card-header .icon { font-size: 2.5rem; margin-bottom: 10px; }
        .card-header h2 { color: #1a1a2e; font-size: 1.6rem; font-weight: 700; margin-bottom: 6px; }
        .card-header p  { color: #888; font-size: 0.85rem; }

        .input-group { margin-bottom: 20px; }
        .input-group label {
            display: block; margin-bottom: 7px;
            font-weight: 600; color: #444; font-size: 0.85rem;
        }
        .input-group label span { color: #e74c3c; }
        .input-group select,
        .input-group input,
        .input-group textarea {
            width: 100%; padding: 13px 16px;
            border: 2px solid #e8ecf0; border-radius: 12px;
            font-size: 0.92rem; font-family: 'Poppins', sans-serif;
            color: #333; background: #fafbfc;
            transition: all 0.25s; outline: none;
            appearance: none; -webkit-appearance: none;
        }
        .input-group select {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 14px center; padding-right: 40px;
        }
        .input-group select:focus,
        .input-group input:focus,
        .input-group textarea:focus {
            border-color: var(--primary); background: white;
            box-shadow: 0 0 0 4px rgba(74,144,226,0.1);
        }
        .input-group textarea { resize: vertical; min-height: 110px; line-height: 1.6; }
        .input-group input::placeholder,
        .input-group textarea::placeholder { color: #bbb; }

        .subject-hint { font-size: 0.73rem; color: #888; margin-top: 5px; }
        .subject-hint b { color: var(--primary); }
        .no-subjects-warn {
            background: #fff3cd; border: 1px solid #ffc107; color: #856404;
            padding: 10px 14px; border-radius: 10px; font-size: 0.8rem; margin-top: 8px;
        }
        .submit-btn {
            width: 100%; background: var(--primary); color: white;
            border: none; padding: 15px; border-radius: 12px;
            font-size: 1rem; font-weight: 700; cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s; margin-top: 8px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 6px 20px rgba(74,144,226,0.35);
        }
        .submit-btn:hover {
            background: #357ABD; transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(74,144,226,0.45);
        }
        .back-link { text-align: center; margin-top: 20px; font-size: 0.82rem; color: #888; }
        .back-link a { color: var(--primary); font-weight: 600; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="form-card">
    <div class="card-header">
        <div class="icon">🚀</div>
        <h2>Post a Doubt</h2>
        <p>A mentor will solve your doubt right away</p>
    </div>

    <form action="submit_doubt.php" method="POST">

        <div class="input-group">
            <label>Subject <span>*</span></label>
            <select name="subject" required>
                <option value="" disabled selected>Select a subject</option>
                <?php if (!empty($all_subjects)): ?>
                    <?php foreach ($all_subjects as $subj): ?>
                        <option value="<?= htmlspecialchars($subj) ?>">
                            <?= htmlspecialchars($subj) ?>
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="" disabled>No mentors available right now</option>
                <?php endif; ?>
            </select>
            <?php if (!empty($all_subjects)): ?>
                <div class="subject-hint"><b><?= count($all_subjects) ?></b> subject(s) available</div>
            <?php else: ?>
                <div class="no-subjects-warn">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    No mentors are registered yet. Please try again later.
                </div>
            <?php endif; ?>
        </div>

        <div class="input-group">
            <label>Topic <span>*</span></label>
            <input type="text" name="topic"
                   placeholder="e.g. Binary Trees, Pointers, Thermodynamics..."
                   required maxlength="150">
        </div>

        <div class="input-group">
            <label>Describe your doubt <span>*</span></label>
            <textarea name="description" rows="4"
                      placeholder="Explain in detail — the more context you give, the better your mentor can help..."
                      required></textarea>
        </div>

        <button type="submit" class="submit-btn">
            <i class="fa-solid fa-paper-plane"></i> Find an Expert
        </button>
    </form>

    <div class="back-link">
        <a href="student_dashboard.php">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

</body>
</html>

