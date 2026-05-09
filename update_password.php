<?php
include('db.php');

$msg = ""; $type = ""; $show_form = false;
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// ── STEP 1: Validate token ───────────────────────────────────────────────────
if (!empty($token)) {
    // Token sirf URL mein hai — email nahi (secure)
    // Prepared statement se token + expiry check karo
    $stmt = $conn->prepare(
        "SELECT user_id, email FROM users 
         WHERE reset_token = ? AND token_expiry > NOW()"
    );
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        $show_form = true;

        // ── STEP 2: Password update ──────────────────────────────────────────
        if (isset($_POST['update_btn'])) {
            $p1 = $_POST['pass']  ?? '';
            $p2 = $_POST['cpass'] ?? '';

            // Validation
            if (strlen($p1) < 8) {
                $msg  = "Password must be at least 8 characters.";
                $type = "error";

            } elseif (!preg_match('/[A-Z]/', $p1)) {
                $msg  = "Password must contain at least one uppercase letter.";
                $type = "error";

            } elseif (!preg_match('/[0-9]/', $p1)) {
                $msg  = "Password must contain at least one number.";
                $type = "error";

            } elseif ($p1 !== $p2) {
                $msg  = "Passwords do not match.";
                $type = "error";

            } else {
                // Hash and update password
                $hashed = password_hash($p1, PASSWORD_BCRYPT);

                // One-time use: delete token immediately after use
                $upd = $conn->prepare(
                    "UPDATE users 
                     SET password = ?, reset_token = NULL, token_expiry = NULL 
                     WHERE user_id = ?"
                );
                $upd->bind_param("si", $hashed, $user['user_id']);

                if ($upd->execute()) {
                    $msg       = "Password updated successfully!";
                    $type      = "success";
                    $show_form = false; // Hide form after success
                } else {
                    $msg  = "Database error. Please try again.";
                    $type = "error";
                }
                $upd->close();
            }
        }

    } else {
        // Token invalid ya expired
        $msg  = "expired";
        $type = "error";
    }

} else {
    // No token in URL
    $msg  = "invalid";
    $type = "error";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password | DoubtDock</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea22, #764ba222), #f0f4f8;
            display: flex; justify-content: center; align-items: center; min-height: 100vh;
        }
        .box {
            background: white; padding: 40px 35px; border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.12); width: 400px; text-align: center;
        }
        .icon { font-size: 2.5rem; margin-bottom: 12px; }
        h2 { color: #1a1a2e; margin-bottom: 6px; font-size: 1.5rem; }
        .sub { font-size: 13px; color: #888; margin-bottom: 24px; }

        .input-wrap { position: relative; margin: 10px 0; }
        .input-wrap input {
            width: 100%; padding: 12px 42px 12px 15px;
            border-radius: 10px; border: 2px solid #e8ecf0;
            font-size: 14px; outline: none; transition: 0.2s;
        }
        .input-wrap input:focus { border-color: #0084ff; box-shadow: 0 0 0 3px rgba(0,132,255,0.1); }
        .toggle-eye {
            position: absolute; right: 13px; top: 50%; transform: translateY(-50%);
            cursor: pointer; color: #aaa; font-size: 15px; user-select: none;
        }

        /* Password strength bar */
        #strength-bar-wrap { text-align: left; margin: 4px 0 8px; }
        #strength-bar {
            height: 4px; border-radius: 4px; background: #e8ecf0;
            transition: 0.3s; width: 0%;
        }
        #strength-text { font-size: 11px; color: #aaa; margin-top: 3px; }

        /* Requirements checklist */
        .requirements { text-align: left; margin: 8px 0 16px; }
        .req { font-size: 12px; color: #aaa; margin: 3px 0; display: flex; align-items: center; gap: 6px; }
        .req.met { color: #28a745; }
        .req i { width: 14px; }

        .btn {
            background: #0084ff; color: white; border: none;
            padding: 13px; width: 100%; border-radius: 10px;
            cursor: pointer; font-size: 15px; font-weight: 600; transition: 0.2s;
        }
        .btn:hover { background: #006ecc; transform: translateY(-1px); }
        .btn:disabled { background: #aaa; cursor: not-allowed; transform: none; }

        .success {
            background: #f0fff4; border: 1px solid #c6f6d5; color: #276749;
            padding: 14px; border-radius: 10px; font-size: 14px; margin-bottom: 16px;
        }
        .error-box {
            background: #fff5f5; border: 1px solid #fed7d7; color: #c53030;
            padding: 14px; border-radius: 10px; font-size: 14px; margin-bottom: 16px;
        }
        .inline-error { color: #c53030; font-size: 12px; margin: 4px 0 8px; text-align: left; }

        .expired-state { padding: 10px 0; }
        .expired-state .big-icon { font-size: 3rem; margin-bottom: 15px; }

        .back-link { margin-top: 20px; font-size: 13px; }
        .back-link a { color: #0084ff; text-decoration: none; font-weight: 600; }
        .back-link a:hover { text-decoration: underline; }

        .login-btn {
            display: inline-block; margin-top: 16px;
            background: #0084ff; color: white; padding: 11px 28px;
            border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 14px;
            transition: 0.2s;
        }
        .login-btn:hover { background: #006ecc; }
    </style>
</head>
<body>
<div class="box">

    <?php if ($type === 'success'): ?>
        <!-- ── SUCCESS STATE ── -->
        <div class="icon">✅</div>
        <h2>Password Updated!</h2>
        <p class="sub">Your password has been changed successfully.</p>
        <a href="login.php" class="login-btn">Login Now</a>

    <?php elseif ($msg === 'expired' || $msg === 'invalid'): ?>
        <!-- ── EXPIRED / INVALID TOKEN STATE ── -->
        <div class="expired-state">
            <div class="big-icon">⏰</div>
            <h2>Link Expired</h2>
            <p class="sub" style="margin-bottom:20px;">
                This password reset link has expired or is invalid.<br>
                Reset links are only valid for <strong>15 minutes</strong>.
            </p>
            <a href="forgot_password.php" class="login-btn" style="background:#e53e3e;">
                Request New Link
            </a>
        </div>

    <?php else: ?>
        <!-- ── PASSWORD FORM ── -->
        <div class="icon">🔑</div>
        <h2>Set New Password</h2>
        <p class="sub">Choose a strong password for your account.</p>

        <?php if ($type === 'error' && !empty($msg)): ?>
            <div class="inline-error">⚠ <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <form method="POST" onsubmit="return validateForm()">

            <div class="input-wrap">
                <input type="password" id="pass" name="pass"
                       placeholder="New Password" required oninput="checkStrength()">
                <span class="toggle-eye" onclick="toggleVis('pass', this)">👁</span>
            </div>

            <!-- Strength bar -->
            <div id="strength-bar-wrap">
                <div id="strength-bar"></div>
                <div id="strength-text"></div>
            </div>

            <!-- Requirements checklist -->
            <div class="requirements">
                <div class="req" id="req-len"><i>○</i> At least 8 characters</div>
                <div class="req" id="req-upper"><i>○</i> One uppercase letter</div>
                <div class="req" id="req-num"><i>○</i> One number</div>
            </div>

            <div class="input-wrap">
                <input type="password" id="cpass" name="cpass"
                       placeholder="Confirm Password" required>
                <span class="toggle-eye" onclick="toggleVis('cpass', this)">👁</span>
            </div>

            <button type="submit" name="update_btn" class="btn" id="submit-btn">
                Update Password
            </button>
        </form>

        <div class="back-link">
            <a href="forgot_password.php">← Request new link</a>
        </div>
    <?php endif; ?>

</div>

<script>
function toggleVis(id, el) {
    const inp = document.getElementById(id);
    inp.type  = inp.type === 'password' ? 'text' : 'password';
    el.textContent = inp.type === 'password' ? '👁' : '🙈';
}

function checkStrength() {
    const val   = document.getElementById('pass').value;
    const bar   = document.getElementById('strength-bar');
    const text  = document.getElementById('strength-text');

    const hasLen   = val.length >= 8;
    const hasUpper = /[A-Z]/.test(val);
    const hasNum   = /[0-9]/.test(val);
    const hasSpec  = /[^A-Za-z0-9]/.test(val);

    // Update checklist
    setReq('req-len',   hasLen);
    setReq('req-upper', hasUpper);
    setReq('req-num',   hasNum);

    const score = [hasLen, hasUpper, hasNum, hasSpec].filter(Boolean).length;
    const colors = ['#e53e3e', '#f6ad55', '#ecc94b', '#48bb78'];
    const labels = ['Weak', 'Fair', 'Good', 'Strong'];

    bar.style.width      = (score * 25) + '%';
    bar.style.background = colors[score - 1] || '#e8ecf0';
    text.textContent     = score > 0 ? labels[score - 1] : '';
    text.style.color     = colors[score - 1] || '#aaa';
}

function setReq(id, met) {
    const el = document.getElementById(id);
    el.classList.toggle('met', met);
    el.querySelector('i').textContent = met ? '✓' : '○';
}

function validateForm() {
    const p1 = document.getElementById('pass').value;
    const p2 = document.getElementById('cpass').value;
    if (p1 !== p2) {
        alert("Passwords do not match!");
        return false;
    }
    if (p1.length < 8 || !/[A-Z]/.test(p1) || !/[0-9]/.test(p1)) {
        alert("Password does not meet the requirements.");
        return false;
    }
    return true;
}
</script>
</body>
</html>
