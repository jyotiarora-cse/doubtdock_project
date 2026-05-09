<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name         = trim($_POST['name']     ?? '');
    $email        = trim($_POST['email']    ?? '');
    $raw_password = $_POST['password']      ?? '';
    $role         = $_POST['role']          ?? '';
    $branch       = $_POST['branch']        ?? '';

    $semester = ($role === 'student' && isset($_POST['semester'])) ? $_POST['semester'] : NULL;

    if ($role === 'mentor') {
        $subject_experties = trim($_POST['subject_experties_hidden'] ?? '');
        if (empty($subject_experties)) {
            $subject_experties = NULL;
        }
    } else {
        $subject_experties = NULL;
    }

    if (empty($name) || empty($email) || empty($raw_password) || empty($role) || empty($branch)) {
        $_SESSION['register_error'] = "Please fill all required fields.";
        header("Location: register.php");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = "Invalid email address.";
        header("Location: register.php");
        exit;
    }

    if (strlen($raw_password) < 6) {
        $_SESSION['register_error'] = "Password must be at least 6 characters.";
        header("Location: register.php");
        exit;
    }

    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $check_stmt->close();
        $_SESSION['register_error'] = "This email is already registered.";
        header("Location: register.php");
        exit;
    }
    $check_stmt->close();

    $password = password_hash($raw_password, PASSWORD_BCRYPT);
    $sql  = "INSERT INTO users (name, email, password, role, branch, semester, subject_experties) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sssssss", $name, $email, $password, $role, $branch, $semester, $subject_experties);
        if ($stmt->execute()) {
            $userId = $conn->insert_id;
            $_SESSION['user_id']   = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['role']      = $role;
            if ($role === 'student') {
                $_SESSION['semester'] = $semester;
                header("Location: student_dashboard.php");
            } else {
                $_SESSION['mentor_branch']    = $branch;
                $_SESSION['mentor_expertise'] = $subject_experties;
                header("Location: teacher_dashboard.php");
            }
            exit;
        } else {
            $_SESSION['register_error'] = "Registration failed.";
            header("Location: register.php");
            exit;
        }
    }
}

$error = $_SESSION['register_error'] ?? '';
unset($_SESSION['register_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DoubtDock | Join Us</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { padding: 4rem 2rem; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .reg-container { width: 100%; max-width: 600px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 600px) { .grid-2 { grid-template-columns: 1fr; } }
        .role-selector {
            display: flex; gap: 1rem; margin-bottom: 1.5rem;
        }
        .role-option {
            flex: 1;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
            font-weight: 700;
        }
        .role-option i { display: block; font-size: 1.5rem; margin-bottom: 0.5rem; }
        input[type="radio"] { display: none; }
        input[type="radio"]:checked + .role-option {
            border-color: var(--primary);
            background: var(--primary-glow);
            color: var(--primary);
        }
        .expertise-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.5rem;
        }
        .expertise-item {
            display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem;
            padding: 0.5rem; border: 1px solid var(--border-color); border-radius: var(--radius-sm);
            cursor: pointer; transition: all 0.2s;
        }
        .expertise-item:has(input:checked) {
            border-color: var(--primary);
            background: var(--primary-glow);
            color: var(--primary);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="bg-mesh-container"></div>
    <div class="reg-container animate-up">
        <div style="text-align:center; margin-bottom: 2.5rem;">
            <div class="nav-logo mb-2" style="justify-content: center; font-size: 2.5rem;">
                <i class="fa-solid fa-graduation-cap"></i> DoubtDock
            </div>
            <h1>Create Account</h1>
            <p class="text-muted">Join the community of learners and mentors</p>
        </div>

        <?php if ($error): ?>
            <div style="background:#fee2e2; color:#dc2626; padding:1rem; border-radius:var(--radius-md); margin-bottom:1.5rem; border:1px solid #fecaca; font-size:0.875rem;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="card-premium">
            <form action="register.php" method="POST">
                <div class="role-selector">
                    <label style="flex: 1;">
                        <input type="radio" name="role" value="student" checked onchange="toggleFields()">
                        <div class="role-option">
                            <i class="fa-solid fa-user-graduate"></i> Student
                        </div>
                    </label>
                    <label style="flex: 1;">
                        <input type="radio" name="role" value="mentor" onchange="toggleFields()">
                        <div class="role-option">
                            <i class="fa-solid fa-chalkboard-user"></i> Mentor
                        </div>
                    </label>
                </div>

                <div class="grid-2">
                    <div class="mb-4">
                        <label class="text-muted mb-1" style="display:block; font-size:0.875rem;">Full Name</label>
                        <input type="text" name="name" class="input-modern" placeholder="John Doe" required>
                    </div>
                    <div class="mb-4">
                        <label class="text-muted mb-1" style="display:block; font-size:0.875rem;">Email Address</label>
                        <input type="email" name="email" class="input-modern" placeholder="john@example.com" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="mb-4">
                        <label class="text-muted mb-1" style="display:block; font-size:0.875rem;">Branch</label>
                        <select name="branch" class="input-modern" required>
                            <option value="">Select Branch</option>
                            <option value="CSE">Computer Science</option>
                            <option value="ECE">Electronics</option>
                            <option value="ME">Mechanical</option>
                            <option value="CE">Civil</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="text-muted mb-1" style="display:block; font-size:0.875rem;">Password</label>
                        <input type="password" name="password" class="input-modern" placeholder="••••••••" required>
                    </div>
                </div>

                <div id="student-fields" class="mb-4">
                    <label class="text-muted mb-1" style="display:block; font-size:0.875rem;">Semester</label>
                    <select name="semester" class="input-modern">
                        <?php for($i=1;$i<=8;$i++) echo "<option value='Sem $i'>Semester $i</option>"; ?>
                    </select>
                </div>

                <div id="mentor-fields" class="mb-4" style="display:none;">
                    <label class="text-muted mb-1" style="display:block; font-size:0.875rem;">Subject Expertise (Search and select multiple)</label>
                    
                    <div class="search-container mb-2">
                        <input type="text" id="subject-search" class="input-modern" placeholder="Type to search (e.g. Data, Java, Math)..." autocomplete="off">
                        <div id="suggestions" class="card-premium" style="display:none; position:absolute; width:100%; z-index:100; max-height:200px; overflow-y:auto; margin-top:5px; padding:0.5rem;"></div>
                    </div>
                    
                    <div id="selected-tags" class="flex" style="flex-wrap:wrap; gap:0.5rem; margin-top:0.5rem;">
                        <!-- Tags will appear here -->
                    </div>
                    
                    <!-- Hidden input to store comma-separated subjects -->
                    <input type="hidden" name="subject_experties_hidden" id="subject_experties_hidden">
                </div>

                <button type="submit" class="btn-modern btn-primary-modern" style="width: 100%; margin-top: 1rem;">
                    Create Account <i class="fa-solid fa-user-plus"></i>
                </button>
            </form>
        </div>

        <div style="text-align:center; margin-top: 2rem; color:var(--text-muted); font-size:0.875rem;">
            Already have an account? <a href="login.php" style="font-weight:700;">Sign In</a>
        </div>
    </div>

    <script>
        const subjectsByBranch = {
            'CSE': ['Data Structures', 'Algorithms', 'Database (SQL)', 'Operating Systems', 'Computer Networks', 'Machine Learning', 'Web Development', 'Java', 'Python', 'C/C++', 'Cloud Computing', 'Cyber Security'],
            'ECE': ['Digital Electronics', 'Microprocessors', 'Signal Processing', 'Communication Systems', 'VLSI Design', 'Embedded Systems', 'Control Systems', 'Circuit Theory'],
            'ME': ['Thermodynamics', 'Fluid Mechanics', 'Machine Design', 'Manufacturing Process', 'Heat Transfer', 'Automobile Engineering', 'Robotics'],
            'CE': ['Structural Analysis', 'Surveying', 'Geotechnical Engineering', 'Transportation Engineering', 'Environmental Engineering', 'Hydraulics', 'Construction Management']
        };

        let selectedSubjects = [];

        function toggleFields() {
            const role = document.querySelector('input[name="role"]:checked').value;
            document.getElementById('student-fields').style.display = role === 'student' ? 'block' : 'none';
            document.getElementById('mentor-fields').style.display = role === 'mentor' ? 'block' : 'none';
        }

        const searchInput = document.getElementById('subject-search');
        const suggestionsBox = document.getElementById('suggestions');
        const tagsContainer = document.getElementById('selected-tags');
        const hiddenInput = document.getElementById('subject_experties_hidden');
        const branchSelect = document.querySelector('select[name="branch"]');

        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase();
            const branch = branchSelect.value;
            
            if (!branch) {
                suggestionsBox.innerHTML = '<div class="text-muted p-2" style="font-size:0.8rem;">Please select a branch first</div>';
                suggestionsBox.style.display = 'block';
                return;
            }

            if (query.length === 0) {
                suggestionsBox.style.display = 'none';
                return;
            }

            const subjects = subjectsByBranch[branch] || [];
            const filtered = subjects.filter(s => s.toLowerCase().includes(query) && !selectedSubjects.includes(s));

            if (filtered.length > 0) {
                suggestionsBox.innerHTML = filtered.map(s => `
                    <div class="suggestion-item p-2" style="cursor:pointer; border-radius:var(--radius-sm); transition:all 0.2s;" onclick="addSubject('${s}')">
                        ${s}
                    </div>
                `).join('');
                suggestionsBox.style.display = 'block';
            } else {
                suggestionsBox.style.display = 'none';
            }
        });

        function addSubject(s) {
            if (!selectedSubjects.includes(s)) {
                selectedSubjects.push(s);
                renderTags();
                searchInput.value = '';
                suggestionsBox.style.display = 'none';
                updateHiddenInput();
            }
        }

        function removeSubject(s) {
            selectedSubjects = selectedSubjects.filter(sub => sub !== s);
            renderTags();
            updateHiddenInput();
        }

        function renderTags() {
            tagsContainer.innerHTML = selectedSubjects.map(s => `
                <div class="card-premium" style="padding:0.4rem 0.8rem; border-radius:50px; font-size:0.8rem; background:var(--primary-glow); color:var(--primary); display:flex; align-items:center; gap:0.5rem; border:1px solid var(--primary);">
                    ${s}
                    <i class="fa-solid fa-xmark" style="cursor:pointer;" onclick="removeSubject('${s}')"></i>
                </div>
            `).join('');
        }

        function updateHiddenInput() {
            hiddenInput.value = selectedSubjects.join(',');
        }

        // Close suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                suggestionsBox.style.display = 'none';
            }
        });

        // Form submission check for mentor
        document.querySelector('form').addEventListener('submit', (e) => {
            const role = document.querySelector('input[name="role"]:checked').value;
            if (role === 'mentor' && selectedSubjects.length === 0) {
                e.preventDefault();
                alert('Please select at least one subject expertise.');
            }
        });
    </script>
    <style>
        .suggestion-item:hover {
            background: var(--primary-glow);
            color: var(--primary);
        }
    </style>
</body>
</html>
<?php $conn->close(); ?>
