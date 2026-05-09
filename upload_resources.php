<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mentor') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Resources | DoubtDock</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { padding: 4rem 2rem; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .form-container { width: 100%; max-width: 500px; }
        .upload-area {
            border: 2px dashed var(--border-color);
            padding: 2.5rem;
            border-radius: var(--radius-md);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 1.5rem;
            background: var(--bg-surface);
        }
        .upload-area:hover {
            border-color: var(--primary);
            background: var(--primary-glow);
        }
    </style>
</head>
<body>
    <div class="bg-mesh-container"></div>
    
    <div class="form-container animate-up">
        <div style="text-align:center; margin-bottom: 2.5rem;">
            <div class="nav-logo mb-2" style="justify-content: center; font-size: 2.5rem;">
                <i class="fa-solid fa-cloud-arrow-up"></i> DoubtDock
            </div>
            <h1>Upload Resource</h1>
            <p class="text-muted">Share your knowledge with the community</p>
        </div>

        <div class="card-premium">
            <form action="process_resources.php" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="text-muted mb-1" style="display:block; font-size:0.875rem;">Resource Title</label>
                    <input type="text" name="title" class="input-modern" placeholder="e.g. Advanced Java Notes" required>
                </div>

                <div class="mb-4">
                    <label class="text-muted mb-1" style="display:block; font-size:0.875rem;">Subject</label>
                    <select name="subject" class="input-modern" required>
                        <option value="" disabled selected>Select Subject</option>
                        <?php
                            $expertise = $_SESSION['mentor_expertise'] ?? '';
                            $subjects  = array_filter(array_map('trim', explode(',', $expertise)));
                            foreach ($subjects as $subject):
                        ?>
                            <option value="<?= htmlspecialchars($subject) ?>"><?= htmlspecialchars($subject) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="text-muted mb-1" style="display:block; font-size:0.875rem;">Target Branch</label>
                    <select name="branch" class="input-modern" required>
                        <option value="All">All Branches</option>
                        <option value="CSE">Computer Science</option>
                        <option value="ECE">Electronics</option>
                        <option value="ME">Mechanical</option>
                        <option value="CE">Civil</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="text-muted mb-1" style="display:block; font-size:0.875rem;">Document (PDF only)</label>
                    <div class="upload-area" onclick="document.getElementById('file-input').click()">
                        <i class="fa-solid fa-file-pdf mb-2" style="font-size: 2.5rem; color: var(--primary);"></i>
                        <p id="file-name" style="font-weight: 700;">Click to select PDF</p>
                        <p class="text-muted" style="font-size: 0.75rem;">PDF format only</p>
                        <input type="file" name="resource_file" id="file-input" style="display:none" accept=".pdf" required onchange="handleFile(this)">
                    </div>
                </div>

                <div class="flex gap-4">
                    <a href="teacher_dashboard.php" class="btn-modern btn-secondary-modern" style="flex:1">Cancel</a>
                    <button type="submit" class="btn-modern btn-primary-modern" style="flex:2">
                        Publish <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function handleFile(input) {
            const fileName = document.getElementById('file-name');
            if (input.files && input.files[0]) {
                fileName.innerText = "✅ " + input.files[0].name;
                fileName.style.color = "var(--primary)";
            }
        }
    </script>
</body>
</html>
