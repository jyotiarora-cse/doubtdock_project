<?php
session_start();
include('db.php'); 
include('mail_function.php'); 

// Error reporting on karein taaki hidden galtiyan dikhen
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    die("Access Denied. Please login as a mentor.");
}

$current_mentor_id = $_SESSION['user_id']; 
$mentor_expertise = $_SESSION['mentor_expertise'];

// --- LOGIC: Doubt Claim Karna ---
if (isset($_GET['claim_id'])) {
    $claim_id = mysqli_real_escape_string($conn, $_GET['claim_id']);
    
    // Yahan check karein ki status update ho raha hai ya nahi
    try {
        $claim_sql = "UPDATE doubts SET status='In-Progress', mentor_id='$current_mentor_id' 
                      WHERE doubt_id='$claim_id' AND status='Pending'";
        mysqli_query($conn, $claim_sql);
        header("Location: see_doubts.php");
        exit();
    } catch (Exception $e) {
        die("Claim Failed: " . $e->getMessage());
    }
}

// --- LOGIC: Solution Submit Karna ---
if (isset($_POST['solve_submit'])) {
    $doubt_id = mysqli_real_escape_string($conn, $_POST['doubt_id']);
    $answer = mysqli_real_escape_string($conn, $_POST['answer']);
    $student_email = $_POST['student_email'];
    $student_name = $_POST['student_name'];

    try {
        $update_sql = "UPDATE doubts SET answer='$answer', status='Solved' WHERE doubt_id='$doubt_id'";
        if (mysqli_query($conn, $update_sql)) {
            // Mail function ko try-catch mein rakhein
            $subject = "DoubtDock | Your Doubt has been Solved!";
            $body = "Hi $student_name, <br><br> Expert ne aapka doubt solve kar diya hai. <br><br><b>Solution:</b> $answer";
            
            $mail_sent = sendMyMail($student_email, $subject, $body);
            
            if($mail_sent) {
                echo "<script>alert('Solved and Email Sent!'); window.location='see_doubts.php';</script>";
            } else {
                echo "<script>alert('Solved but Email Failed! Check mail_function.php settings.'); window.location='see_doubts.php';</script>";
            }
            exit();
        }
    } catch (Exception $e) {
        die("Solve Failed: " . $e->getMessage());
    }
}

// --- QUERY: Isko dhyan se dekhein ---
// Humne status 'In-Progress' ko bhi query mein rakha hai
$query = "SELECT d.*, u.name as student_name, u.email as student_email 
          FROM doubts d 
          JOIN users u ON d.student_id = u.user_id 
          WHERE d.subject = '$mentor_expertise' 
          AND (d.status = 'Pending' OR (d.status = 'In-Progress' AND d.mentor_id = '$current_mentor_id'))
          ORDER BY d.created_at DESC";

$result = mysqli_query($conn, $query);
$pending_count = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DoubtDock | Mentor Workspace</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --fresh-blue: #0084ff; --soft-blue: #eef7ff; --deep-blue: #003d80; --border-color: #dbeafe; --white: #ffffff; --text-dark: #1e293b; --text-muted: #64748b; --progress-orange: #f59e0b; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f0f6ff; margin: 0; padding: 30px; color: var(--text-dark); }
        .container { max-width: 850px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .mentor-card { background: var(--white); border: 1px solid var(--border-color); border-radius: 20px; padding: 24px; margin-bottom: 24px; box-shadow: 0 10px 30px rgba(0, 132, 255, 0.05); }
        .status-badge { font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-progress { background: #dcfce7; color: #166534; border: 1px solid #22c55e; }
        .student-profile { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 40px; height: 40px; background: var(--soft-blue); color: var(--fresh-blue); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .doubt-question { font-size: 1.1rem; font-weight: 600; color: var(--deep-blue); margin: 20px 0; }
        .solve-container { background: #f8fafc; border: 2px dashed var(--border-color); border-radius: 12px; padding: 15px; }
        textarea { width: 100%; border: none; background: transparent; font-size: 1rem; min-height: 100px; outline: none; resize: vertical; }
        .btn-submit { background: var(--fresh-blue); color: white; border: none; padding: 12px 25px; border-radius: 10px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-claim { background: var(--progress-orange); }
        .empty-state { text-align: center; padding: 60px; background: white; border-radius: 20px; color: var(--text-muted); }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h2 style="color: var(--deep-blue); margin:0;">Expert Workspace</h2>
            <p style="color: var(--text-muted); margin:5px 0;">Topic: <b><?php echo htmlspecialchars($mentor_expertise); ?></b></p>
        </div>
        <div><span style="font-weight: 700; color: var(--fresh-blue);"><?php echo $pending_count; ?> Doubts found</span></div>
    </div>

    <?php if ($pending_count > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <div class="mentor-card">
                <div style="display: flex; justify-content: space-between;">
                    <div class="student-profile">
                        <div class="avatar"><?php echo strtoupper(substr($row['student_name'], 0, 1)); ?></div>
                        <div>
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($row['student_name']); ?></div>
                            <div style="font-size: 0.8rem; color: #94a3b8;"><?php echo date('h:i A', strtotime($row['created_at'])); ?></div>
                        </div>
                    </div>
                    <span class="status-badge <?php echo ($row['status'] == 'Pending') ? 'status-pending' : 'status-progress'; ?>">
                        <?php echo ($row['status'] == 'Pending') ? 'New' : 'Claimed'; ?>
                    </span>
                </div>

                <div class="doubt-question">"<?php echo htmlspecialchars($row['doubt_text'] ?? $row['doubt_text']); ?>"</div>

                <?php if ($row['status'] == 'Pending'): ?>
                    <div style="text-align: right;">
                        <a href="see_doubts.php?claim_id=<?php echo $row['doubt_id']; ?>" class="btn-submit btn-claim">Claim & Solve</a>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="solve-container">
                            <input type="hidden" name="doubt_id" value="<?php echo $row['doubt_id']; ?>">
                            <input type="hidden" name="student_email" value="<?php echo $row['student_email']; ?>">
                            <input type="hidden" name="student_name" value="<?php echo $row['student_name']; ?>">
                            <textarea name="answer" placeholder="Explain the solution..." required></textarea>
                            <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
                                <button type="submit" name="solve_submit" class="btn-submit">Send Solution</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state"><h3>All caught up! ☕</h3><p>No new doubts in your subject area.</p></div>
    <?php endif; ?>
</div>

</body>
</html>