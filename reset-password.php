<?php
session_start();
require_once(__DIR__ . '/includes/config.php');

$message = '';
$messageType = '';
$validToken = false;
$token = $_GET['token'] ?? $_POST['token'] ?? '';

$db = get_db_connection();

// Validate token
if (!empty($token)) {
    $stmt = $db->prepare("SELECT pr.userID, pr.expires_at FROM password_resets pr WHERE pr.token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $resetData = $stmt->get_result()->fetch_assoc();
    
    if ($resetData) {
        if (strtotime($resetData['expires_at']) > time()) {
            $validToken = true;
        } else {
            $message = 'This reset link has expired. Please request a new one.';
            $messageType = 'error';
            // Clean up expired token
            $stmt = $db->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
        }
    } else {
        $message = 'Invalid or already used reset link. Please request a new one.';
        $messageType = 'error';
    }
} else {
    $message = 'No reset token provided. Please use the link from your email.';
    $messageType = 'error';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $message = 'Please fill in both password fields.';
        $messageType = 'error';
    } elseif (strlen($newPassword) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'Passwords do not match. Please try again.';
        $messageType = 'error';
    } else {
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update the password in users table
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE userID = ?");
        $stmt->bind_param("ss", $hashedPassword, $resetData['userID']);
        
        if ($stmt->execute()) {
            // Delete the used token
            $stmt = $db->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            
            $message = 'Your password has been reset successfully! You can now log in with your new password.';
            $messageType = 'success';
            $validToken = false; // Hide the form after successful reset
        } else {
            $message = 'Failed to update password. Please try again.';
            $messageType = 'error';
        }
    }
}

$db->close();
?>
<!doctype html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="favicon2.png">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CAMPUSLink — Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/authentication.css">
</head>
<body>
    <canvas id="bg-canvas"></canvas>
    <div class="login-card">
        <div class="brand-icon">CL</div>
        <h1>Reset Password</h1>
        <p>Enter your new password below.</p>
        
        <?php if ($message): ?>
            <div class="<?php echo $messageType === 'success' ? 'success-msg' : 'error-msg'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($validToken): ?>
        <form method="POST" id="resetForm" novalidate>
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <div class="pw-wrapper">
                    <input type="password" id="new_password" name="new_password" placeholder="Min. 6 characters" autofocus>
                    <button type="button" class="pw-toggle" onclick="togglePassword('new_password', this)" tabindex="-1">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
                <div class="field-error" id="newPwError" style="display: none; color: #dc2626; font-size: 12px; margin-top: 5px;">Password must be at least 6 characters.</div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="pw-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password">
                    <button type="button" class="pw-toggle" onclick="togglePassword('confirm_password', this)" tabindex="-1">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
                <div class="field-error" id="confirmPwError" style="display: none; color: #dc2626; font-size: 12px; margin-top: 5px;">Passwords do not match.</div>
            </div>

            <button type="submit" class="btn-login">Reset Password</button>
        </form>
        <?php endif; ?>
        
        <div class="back-to-login">
            <a href="login.php">&larr; Back to Login</a>
        </div>
    </div>

    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.className = isHidden ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
        }

        <?php if ($validToken): ?>
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const newPw = document.getElementById('new_password');
            const confirmPw = document.getElementById('confirm_password');
            const newPwError = document.getElementById('newPwError');
            const confirmPwError = document.getElementById('confirmPwError');
            
            let isValid = true;
            
            newPwError.style.display = 'none';
            confirmPwError.style.display = 'none';
            newPw.style.borderColor = '';
            confirmPw.style.borderColor = '';
            
            if (newPw.value.length < 6) {
                newPwError.style.display = 'block';
                newPw.style.borderColor = '#dc2626';
                isValid = false;
            }
            
            if (newPw.value !== confirmPw.value) {
                confirmPwError.style.display = 'block';
                confirmPw.style.borderColor = '#dc2626';
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        document.getElementById('new_password').addEventListener('input', function() {
            document.getElementById('newPwError').style.display = 'none';
            this.style.borderColor = '';
        });
        document.getElementById('confirm_password').addEventListener('input', function() {
            document.getElementById('confirmPwError').style.display = 'none';
            this.style.borderColor = '';
        });
        <?php endif; ?>
    </script>
    <script src="login-bg.js"></script>
    <!-- Footer -->
    <footer class="cl-footer">
        <div class="cl-footer-line"></div>
        <div class="cl-footer-content">
            <span class="cl-footer-link">DISCLAIMER</span>
            <span class="cl-footer-divider">|</span>
            <span class="cl-footer-link">PRIVACY POLICY</span>
            <span class="cl-footer-divider">|</span>
            <span class="cl-footer-link">ABAC Policy</span>
            <span class="cl-footer-divider">|</span>
            <span class="cl-footer-copyright">COPYRIGHT &copy; <?php echo date('Y'); ?> <strong>CAMPUSLink. ALL RIGHTS RESERVED</strong></span>
            <div class="cl-footer-social">
                <i class="fa-brands fa-tiktok"></i>
                <i class="fa-brands fa-facebook"></i>
                <i class="fa-brands fa-instagram"></i>
                <i class="fa-brands fa-linkedin"></i>
            </div>
        </div>
    </footer>
</body>
</html>
