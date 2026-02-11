<?php
session_start();
include 'db.php';

// Redirect if not a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.html");
    exit();
}

// FETCH DYNAMIC SUBJECTS - Yahan 'subjects' ko 'subject_experties' se replace kiya gaya hai
$subject_query = "SELECT DISTINCT subject_experties FROM users WHERE role = 'mentor' AND subject_experties IS NOT NULL";
$subject_result = mysqli_query($conn, $subject_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ask a Doubt | DoubtDock</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4A90E2; --secondary: #F5A623; }
        body { font-family: 'Poppins', sans-serif; background: #f0f4f8; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .form-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        .form-card h2 { color: #333; text-align: center; margin-bottom: 30px; }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #666; }
        .input-group select, .input-group input, .input-group textarea {
            width: 100%; padding: 12px; border: 2px solid #e1e8ed; border-radius: 10px; font-size: 15px; transition: 0.3s; box-sizing: border-box;
        }
        .input-group select:focus, .input-group input:focus, .input-group textarea:focus { border-color: var(--primary); outline: none; }
        .submit-btn { 
            width: 100%; background: var(--primary); color: white; border: none; padding: 15px; 
            border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: 0.3s;
        }
        .submit-btn:hover { background: #357ABD; transform: translateY(-2px); }
    </style>
</head>
<body>

<div class="form-card">
    <h2>🚀 Post a Doubt</h2>
    <form action="submit_doubt.php" method="POST">
        <div class="input-group">
            <label>Choose Subject</label>
            <select name="subject" required>
                <option value="" disabled selected>Which subject is it?</option>
                <?php 
                // Loop ke andar bhi column name update kar diya gaya hai
                while($row = mysqli_fetch_assoc($subject_result)): 
                ?>
                    <option value="<?= htmlspecialchars($row['subject_experties']) ?>">
                        <?= htmlspecialchars($row['subject_experties']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="input-group">
            <label>Topic</label>
            <input type="text" name="topic" placeholder="e.g. Quantum Physics or Algebra" required>
        </div>

        <div class="input-group">
            <label>Explain your Doubt</label>
            <textarea name="description" rows="4" placeholder="Be descriptive so mentors can help better..." required></textarea>
        </div>

        <button type="submit" class="submit-btn">Find an Expert</button>
    </form>
</div>

</body>
</html>