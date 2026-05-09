<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Please fill in all fields.";
        header("Location: login.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['user_name'] = $user['name'];

        if ($user['role'] === 'mentor') {
            $_SESSION['mentor_expertise'] = $user['subject_experties'];
            $_SESSION['mentor_branch']    = $user['branch'];
            if (!empty($_SESSION['redirect_to'])) {
                $redirect = $_SESSION['redirect_to'];
                unset($_SESSION['redirect_to']);
                header("Location: " . $redirect);
            } else {
                header("Location: teacher_dashboard.php");
            }
            exit;
        } elseif ($user['role'] === 'student') {
            $_SESSION['user_branch'] = $user['branch'];
            header("Location: student_dashboard.php");
            exit;
        } else {
            $_SESSION['login_error'] = "Unknown role. Please contact admin.";
            header("Location: login.php");
            exit;
        }
    } else {
        $_SESSION['login_error'] = "Invalid email or password. Please try again.";
        header("Location: login.php");
        exit;
    }
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DoubtDock | Sign In</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .login-container {
            width: 100%;
            max-width: 440px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .error-toast {
            background: #fee2e2;
            color: #dc2626;
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            border: 1px solid #fecaca;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .input-group-modern {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .input-group-modern i {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            transition: color 0.3s;
        }
        .input-group-modern input:focus + i {
            color: var(--primary);
        }
        .input-group-modern input {
            padding-left: 3.5rem;
        }
        .footer-links {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        .footer-links a {
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="bg-mesh-container"></div>
    
    <div class="login-container animate-up">
        <div class="login-header">
            <div class="nav-logo mb-2" style="justify-content: center; font-size: 2.5rem;">
                <i class="fa-solid fa-graduation-cap"></i> DoubtDock
            </div>
            <h1>Welcome Back</h1>
            <p class="text-muted">Enter your credentials to access your account</p>
        </div>

        <?php if ($error): ?>
            <div class="error-toast">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="card-premium">
            <form action="login.php" method="POST">
                <div class="input-group-modern">
                    <input type="email" name="email" class="input-modern" placeholder="Email Address" required>
                    <i class="fa-solid fa-envelope"></i>
                </div>

                <div class="input-group-modern">
                    <input type="password" name="password" class="input-modern" placeholder="Password" required>
                    <i class="fa-solid fa-lock"></i>
                </div>

                <button type="submit" class="btn-modern btn-primary-modern" style="width: 100%;">
                    Sign In <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
        </div>

        <div class="footer-links">
            <p>New to DoubtDock? <a href="register.php">Create an account</a></p>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
