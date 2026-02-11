<?php
session_start();
include('db.php');
$my_id = $_SESSION['user_id'] ?? 201;

// AUTO-RELEASE: 20 min rule (Fix: claimed_at column exists in your screenshot)
mysqli_query($conn, "UPDATE doubts SET status='Pending', mentor_id=NULL, claimed_at=NULL 
                     WHERE status='Claimed' AND claimed_at < NOW() - INTERVAL 20 MINUTE");

// Fetch Doubts (Fix: Table columns mapping)
$query = "SELECT * FROM doubts WHERE status='Pending' OR (status='Claimed' AND mentor_id='$my_id') ORDER BY status DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mentor Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .card { background: white; padding: 15px; margin-bottom: 10px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn-claim { background: #7c3aed; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; display: inline-block; font-size: 14px; }
        .btn-send { background: #22c55e; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; }
        textarea { width: 100%; margin-top: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        .status-tag { color: #f59e0b; font-weight: bold; font-size: 0.9em; margin-bottom: 5px; }
        .info { font-size: 0.85em; color: #666; }
    </style>
</head>
<body>

    <h2>Doubt Pool</h2>

    <?php while($row = mysqli_fetch_assoc($result)) { ?>
        <div class="card">
            <p><strong>Student ID:</strong> #<?php echo $row['student_id']; ?></p>
            
            <p><strong>Question:</strong> <?php echo $row['doubt_text']; ?></p>
            
            <p class="info">Subject: <?php echo $row['subject']; ?> | Topic: <?php echo $row['topic']; ?></p>

            <?php if($row['status'] == 'Pending') { ?>
                <a href="process_doubt.php?action=claim&id=<?php echo $row['doubt_id']; ?>" class="btn-claim">Claim Doubt</a>
            
            <?php } else { ?>
                <div class="solve-area">
                    <p class="status-tag">✓ YOU HAVE CLAIMED THIS</p>
                    <form action="process_doubt.php" method="POST">
                        <input type="hidden" name="doubt_id" value="<?php echo $row['doubt_id']; ?>">
                        <textarea name="answer" rows="3" placeholder="Write your expert answer here..." required></textarea><br><br>
                        <button type="submit" name="solve_btn" class="btn-send">Submit Solution</button>
                    </form>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

</body>
</html>