<?php
session_start();
include 'db.php';

// Check if mentor is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mentor') {
    header("Location: login.html");
    exit();
}

$mentor_subject = $_SESSION['mentor_expertise']; 
$mentor_id = $_SESSION['user_id'];

// SQL Query to fetch pending doubts for this specific expertise
$sql = "SELECT * FROM doubts WHERE subject = '$mentor_subject' AND status = 'Pending' ORDER BY doubt_id DESC";
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
    <meta http-equiv="refresh" content="30"> 
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: auto; }
        
        .header { 
            display: flex; justify-content: space-between; align-items: center; 
            background: white; padding: 15px 25px; border-radius: 15px; margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .card { 
            background: white; padding: 25px; margin-bottom: 20px; border-radius: 15px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); display: flex; 
            justify-content: space-between; align-items: center; 
            border-left: 6px solid #007bff; transition: 0.3s;
        }
        .card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }

        .btn-claim { 
            background: #28a745; color: white; padding: 14px 28px; 
            text-decoration: none; border-radius: 10px; font-weight: bold; 
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
        }
        .btn-claim:hover { background: #218838; }

        .subject-tag { 
            background: #e7f1ff; color: #007bff; padding: 6px 15px; 
            border-radius: 50px; font-size: 12px; font-weight: 700; 
        }
        
        .logout-btn { color: #dc3545; font-weight: 600; text-decoration: none; border: 1px solid #dc3545; padding: 8px 15px; border-radius: 8px; transition: 0.3s; }
        .logout-btn:hover { background: #dc3545; color: white; }

        .badge-live {
            display: inline-block; width: 10px; height: 10px; background: #28a745;
            border-radius: 50%; margin-right: 5px; animation: blink 1.5s infinite;
        }
        @keyframes blink { 0% { opacity: 1; } 50% { opacity: 0.3; } 100% { opacity: 1; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h2 style="margin:0;">Welcome, <span style="color: #007bff;"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span></h2>
                <small style="color: #666;"><span class="badge-live"></span> Active in <b><?php echo htmlspecialchars($mentor_subject); ?></b></small>
            </div>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>

        <h3 style="color: #444; margin-bottom: 20px;">Pending Doubts (Real-time Feed)</h3>

        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
                <div class="card">
                    <div style="flex: 1; padding-right: 20px;">
                        <span class="subject-tag"><?php echo htmlspecialchars($row['subject']); ?></span>
                        <h3 style="margin: 15px 0 10px 0; color: #333;"><?php echo htmlspecialchars($row['topic']); ?></h3>
                        <p style="color: #555; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                        <small style="color: #999;">Doubt ID: #<?php echo $row['doubt_id']; ?></small>
                    </div>

                    <div>
                        <a href="claim_doubt.php?id=<?php echo $row['doubt_id']; ?>" class="btn-claim">Accept & Chat</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 80px 20px; background: white; border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <img src="https://cdn-icons-png.flaticon.com/512/2618/2618245.png" width="80" style="opacity: 0.2; margin-bottom: 20px;">
                <h3 style="color: #bbb;">No Pending Doubts Right Now</h3>
                <p style="color: #999;">New doubts in <b><?php echo htmlspecialchars($mentor_subject); ?></b> will appear here automatically.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>