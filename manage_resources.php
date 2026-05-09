<?php
session_start();
include 'db.php';
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mentor') {
    header("Location: login.php");
    exit();
}

$mentor_id = $_SESSION['user_id'];

// Mentor ki apni uploads fetch karna — using prepared statement
$stmt = $conn->prepare("SELECT * FROM resources WHERE mentor_id = ? ORDER BY uploaded_at DESC");
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Library | DoubtDock</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 30px; }
        .library-container { max-width: 1000px; margin: auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8fafc; color: #64748b; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
        .btn-delete { color: #ef4444; background: #fee2e2; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; transition: 0.3s; }
        .btn-delete:hover { background: #fecaca; transform: scale(1.1); }
        .status-badge { background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>

<div class="library-container">
    <div class="header">
        <h2><i class="fa-solid fa-folder-open" style="color: #6366f1;"></i> My Uploaded Resources</h2>
        <a href="teacher_dashboard.php" style="text-decoration:none; color: #6366f1; font-weight:bold;"><i class="fa fa-arrow-left"></i> Back</a>
    </div>

    <?php 
    if (isset($_SESSION['resource_success'])) {
        echo '<div style="background:#dcfce7; color:#15803d; padding:12px; border-radius:10px; margin-bottom:20px; font-weight:600; font-size:14px;">
                <i class="fa fa-circle-check"></i> ' . htmlspecialchars($_SESSION['resource_success']) . '
              </div>';
        unset($_SESSION['resource_success']);
    }
    ?>

    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Subject</th>
                <th>Branch</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                    <td><span class="status-badge"><?php echo htmlspecialchars($row['branch']); ?></span></td>
                    <td style="font-size: 13px; color: #888;"><?php echo date('d M, Y', strtotime($row['uploaded_at'])); ?></td>
                    <td>
                        <a href="delete_resources.php?id=<?php echo $row['resource_id']; ?>" 
                           class="btn-delete" 
                           onclick="return confirm('Bhai, kya aap sach mein ye file delete karna chahte hain?')">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center; padding: 50px; color: #999;">Aapne abhi tak koi resource upload nahi kiya hai.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
