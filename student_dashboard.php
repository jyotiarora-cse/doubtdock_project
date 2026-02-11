<?php
session_start();
include('db.php');
include('mail_function.php');

$student_id = $_SESSION['user_id'] ?? 101; 
$student_name = $_SESSION['user_name'] ?? "Rahul";

if(isset($_POST['ask_btn'])) {
    $description = mysqli_real_escape_string($conn, $_POST['question']);
    $insert = "INSERT INTO doubts (student_id, description, status) VALUES ('$student_id', '$description', 'Pending')";
    
    if(mysqli_query($conn, $insert)) {
        echo "<script>alert('Doubt submitted!'); window.location.href='student_dashboard.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard | DoubtDock</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --pending: #f39c12;
            --solved: #27ae60;
            --claimed: #3498db;
        }

        body { font-family: 'Segoe UI', sans-serif; background: #f4f7fe; margin: 0; }
        .main-wrapper { max-width: 800px; margin: 40px auto; padding: 0 20px; }

        /* Top Action Bar */
        .action-bar { display: flex; gap: 10px; margin-bottom: 30px; }
        
        .btn-action { 
            padding: 12px 20px; border-radius: 10px; text-decoration: none; 
            font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px;
            transition: 0.3s; cursor: pointer; border: none;
        }
        .btn-ask { background: var(--primary); color: white; }
        .btn-ask:hover { background: #3451d1; transform: translateY(-2px); }
        
        .btn-resources { background: white; color: var(--primary); border: 2px solid var(--primary); }
        .btn-resources:hover { background: #f0f2ff; }

        /* Hidden Ask Form */
        #askFormContainer { 
            display: none; background: white; padding: 25px; border-radius: 15px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); margin-bottom: 30px; 
            animation: fadeInDown 0.4s ease;
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .input-area { 
            width: 100%; border: 1px solid #e0e0e0; border-radius: 10px; 
            padding: 15px; font-size: 15px; outline: none; box-sizing: border-box;
            min-height: 120px; margin-bottom: 15px;
        }

        /* Doubt Cards */
        .doubt-card { 
            background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.03); border-left: 6px solid #ddd;
        }
        .status-pill { font-size: 11px; font-weight: 800; padding: 5px 10px; border-radius: 5px; display: inline-block; margin-bottom: 10px; }
        .status-pending { background: #fff4e5; color: var(--pending); }
        
        .question-text { display: block; font-size: 16px; font-weight: 500; color: #2d3436; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="main-wrapper">
    <div style="margin-bottom: 25px;">
        <h2 style="margin: 0;">Hello, <?php echo $student_name; ?>! 👋</h2>
        <p style="color: #666;">Manage your doubts or ask a new one below.</p>
    </div>

    <div class="action-bar">
    <a href="ask_doubt.php" class="btn-action btn-ask" style="text-decoration: none;">
        <i class="fas fa-plus-circle"></i> Ask New Doubt
    </a>
        <a href="resources.php" class="btn-action btn-resources">
            <i class="fas fa-book"></i> View Resources
        </a>
    </div>

    <div id="askFormContainer">
        <h3 style="margin-top: 0;">Post Your Doubt</h3>
        <form method="POST">
            <textarea name="question" class="input-area" placeholder="Describe your doubt in detail..." required></textarea>
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="ask_btn" class="btn-action btn-ask" style="width: 100%; justify-content: center;">
                    Submit Doubt
                </button>
                <button type="button" onclick="toggleAskForm()" class="btn-action" style="background: #eee; color: #333;">
                    Cancel
                </button>
            </div>
        </form>
    </div>

    <h3 style="color: #444; border-bottom: 2px solid #eee; padding-bottom: 10px;">Your History</h3>

    <?php
    $fetch = mysqli_query($conn, "SELECT * FROM doubts WHERE student_id = '$student_id' ORDER BY doubt_id DESC");
    while($row = mysqli_fetch_assoc($fetch)) {
        $status = strtoupper($row['status'] ?? 'PENDING');
    ?>
    <div class="doubt-card" style="border-left-color: <?php echo ($status == 'PENDING' ? 'var(--pending)' : 'var(--primary)'); ?>">
        <span class="status-pill status-pending">
            <i class="fas fa-clock"></i> <?php echo $status; ?>
        </span>
        <span class="question-text"><?php echo htmlspecialchars($row['description']); ?></span>
        
        <?php if($status !== 'PENDING') { ?>
            <a href="chat_ui.php?id=<?php echo $row['doubt_id']; ?>" style="color: var(--primary); font-weight: 600; text-decoration: none;">
                <i class="fas fa-comments"></i> Open Chat
            </a>
        <?php } ?>
    </div>
    <?php } ?>
</div>

<script>
function toggleAskForm() {
    const form = document.getElementById('askFormContainer');
    if (form.style.display === 'block') {
        form.style.display = 'none';
    } else {
        form.style.display = 'block';
        window.scrollTo({ top: 100, behavior: 'smooth' });
    }
}
</script>

</body>
</html>