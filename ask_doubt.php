<?php
session_start();
include 'db.php';
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$doubt_error = '';
if (!empty($_SESSION['doubt_error'])) {
    $doubt_error = $_SESSION['doubt_error'];
    unset($_SESSION['doubt_error']);
}

$preselect_subject = trim($_GET['preselect_subject'] ?? '');

$subject_query  = "SELECT subject_experties FROM users WHERE role = 'mentor' AND subject_experties IS NOT NULL AND subject_experties != ''";
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
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { padding: 4rem 2rem; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .form-container { width: 100%; max-width: 700px; }
        .upload-area {
            border: 2px dashed var(--border-color);
            padding: 2rem;
            border-radius: var(--radius-md);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 1.5rem;
        }
        .upload-area:hover {
            border-color: var(--primary);
            background: var(--primary-glow);
        }
        #image-preview-container {
            display: none;
            margin-top: 1rem;
            position: relative;
        }
        #image-preview {
            max-width: 100%;
            border-radius: var(--radius-md);
            max-height: 300px;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <div class="bg-mesh-container"></div>
    
    <div class="form-container animate-up">
        <div style="text-align:center; margin-bottom: 2.5rem;">
            <div class="nav-logo mb-2" style="justify-content: center; font-size: 2.5rem;">
                <i class="fa-solid fa-circle-question"></i> DoubtDock
            </div>
            <h1>Ask Your Doubt</h1>
            <p class="text-muted">Describe your problem and get help from expert mentors</p>
        </div>

        <?php if ($doubt_error): ?>
            <div style="background:#fee2e2; color:#dc2626; padding:1rem; border-radius:var(--radius-md); margin-bottom:1.5rem; border:1px solid #fecaca;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($doubt_error) ?>
            </div>
        <?php endif; ?>

        <div class="card-premium">
            <form action="submit_doubt.php" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="text-muted mb-1" style="display:block; font-size:0.875rem;">Subject</label>
                    <select name="subject" class="input-modern" required>
                        <option value="" disabled <?= empty($preselect_subject) ? 'selected' : '' ?>>Choose a subject</option>
                        <?php foreach($all_subjects as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>" <?= ($preselect_subject === $s) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="text-muted mb-1" style="display:block; font-size:0.875rem;">Topic / Title</label>
                    <input type="text" name="topic" class="input-modern" placeholder="e.g. Binary Tree Insertion" required>
                </div>

                <div class="mb-4">
                    <label class="text-muted mb-1" style="display:block; font-size:0.875rem;">Description</label>
                    <textarea name="description" class="input-modern" style="min-height: 150px;" placeholder="Explain your doubt in detail..." required></textarea>
                </div>

                <div class="mb-4">
                    <label class="text-muted mb-1" style="display:block; font-size:0.875rem;">Attachment (Optional)</label>
                    <div class="upload-area" onclick="document.getElementById('image-input').click()">
                        <i class="fa-solid fa-cloud-arrow-up mb-2" style="font-size: 2rem; color: var(--primary);"></i>
                        <p style="font-weight: 700;">Click to upload image</p>
                        <p class="text-muted" style="font-size: 0.75rem;">PNG, JPG, JPEG (Max 5MB)</p>
                        <input type="file" name="doubt_image" id="image-input" style="display:none" accept="image/*" onchange="handlePreview(this)">
                    </div>
                    <div id="image-preview-container">
                        <img id="image-preview" src="" alt="Preview">
                        <button type="button" onclick="removeImage()" style="position:absolute; top:10px; right:10px; background:var(--danger); color:white; border:none; border-radius:50%; width:30px; height:30px; cursor:pointer;">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <div class="flex gap-4">
                    <a href="student_dashboard.php" class="btn-modern btn-secondary-modern" style="flex:1">Cancel</a>
                    <button type="submit" class="btn-modern btn-primary-modern" style="flex:2">
                        Post Doubt <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function handlePreview(input) {
            const container = document.getElementById('image-preview-container');
            const preview = document.getElementById('image-preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    container.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        function removeImage() {
            document.getElementById('image-input').value = '';
            document.getElementById('image-preview-container').style.display = 'none';
        }
    </script>
</body>
</html>
