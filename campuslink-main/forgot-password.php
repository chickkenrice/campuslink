<?php
session_start();
require_once(__DIR__ . '/includes/config.php');
require_once(__DIR__ . '/includes/mail-config.php');

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $message = 'Please enter your email address.';
        $messageType = 'error';
    } else {
        $db = get_db_connection();
        
        // Search for the email across student, staff, and admin tables
        $userID = null;
        $userName = null;
        $role = null;
        
        // Check student table
        $stmt = $db->prepare("SELECT s.userID, s.studentName FROM student s WHERE s.email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result) {
            $userID = $result['userID'];
            $userName = $result['studentName'];
            $role = 'Student';
        }
        
        // Check staff table
        if (!$userID) {
            $stmt = $db->prepare("SELECT st.userID, st.staffName FROM staff st WHERE st.email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            if ($result) {
                $userID = $result['userID'];
                $userName = $result['staffName'];
                $role = 'Staff';
            }
        }
        
        // Check admin table
        if (!$userID) {
            $stmt = $db->prepare("SELECT a.userID, a.adminName FROM admin a WHERE a.email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            if ($result) {
                $userID = $result['userID'];
                $userName = $result['adminName'];
                $role = 'Admin';
            }
        }
        
        if ($userID) {
            // Generate a secure token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Delete any existing tokens for this user
            $stmt = $db->prepare("DELETE FROM password_resets WHERE userID = ?");
            $stmt->bind_param("s", $userID);
            $stmt->execute();
            
            // Insert new token
            $stmt = $db->prepare("INSERT INTO password_resets (userID, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $userID, $token, $expires);
            $stmt->execute();
            
            // Build reset link
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $basePath = dirname($_SERVER['SCRIPT_NAME']);
            $resetLink = "$protocol://$host$basePath/reset-password.php?token=$token";
            
            // Send email
            $subject = "CampusLink - Password Reset Request";
            $emailBody = "
            <html>
            <head>
                <style>
                    body { font-family: 'Inter', Arial, sans-serif; background: #f7f4ff; padding: 20px; }
                    .container { max-width: 500px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 40px; box-shadow: 0 4px 20px rgba(128,86,255,0.1); }
                    .brand { text-align: center; margin-bottom: 24px; }
                    .brand-icon { display: inline-block; width: 50px; height: 50px; background: linear-gradient(135deg, #8056ff, #6a46f6); color: white; font-size: 20px; font-weight: 700; border-radius: 14px; line-height: 50px; text-align: center; }
                    h2 { color: #181b2f; text-align: center; margin: 0 0 10px; }
                    p { color: #555; font-size: 14px; line-height: 1.6; }
                    .btn { display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #8056ff, #6a46f6); color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: 600; font-size: 15px; margin: 20px 0; }
                    .footer { font-size: 12px; color: #999; text-align: center; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
                    .link-text { word-break: break-all; font-size: 12px; color: #8056ff; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='brand'><div class='brand-icon'>CL</div></div>
                    <h2>Password Reset</h2>
                    <p>Hi <strong>$userName</strong>,</p>
                    <p>We received a request to reset the password for your CampusLink account. Click the button below to set a new password:</p>
                    <p style='text-align:center;'><a href='$resetLink' class='btn'>Reset My Password</a></p>
                    <p>If the button doesn't work, copy and paste this link into your browser:</p>
                    <p class='link-text'>$resetLink</p>
                    <p>This link will expire in <strong>1 hour</strong>. If you didn't request a password reset, please ignore this email.</p>
                    <div class='footer'>
                        &copy; " . date('Y') . " CampusLink. All rights reserved.
                    </div>
                </div>
            </body>
            </html>";
            
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: CampusLink <noreply@campuslink.edu.my>\r\n";
            
            $mailResult = sendMail($email, $userName, $subject, $emailBody);
            
            if ($mailResult['success']) {
                $message = 'A password reset link has been sent to your email address. Please check your inbox.';
                $messageType = 'success';
            } else {
                $message = 'Failed to send email: ' . $mailResult['error'];
                $messageType = 'error';
            }
        } else {
            // Don't reveal whether email exists for security - show same success message
            $message = 'If an account with that email exists, a password reset link has been sent. Please check your inbox.';
            $messageType = 'success';
        }
        
        $db->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CAMPUSLink — Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/authentication.css">
</head>
<body>
    <canvas id="bg-canvas"></canvas>
    <div class="login-card">
        <div class="brand-icon">CL</div>
        <h1>Forgot Password</h1>
        <p>Enter your registered email address and we'll send you a link to reset your password.</p>
        
        <?php if ($message): ?>
            <div class="<?php echo $messageType === 'success' ? 'success-msg' : 'error-msg'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="forgotForm" novalidate>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="e.g. example@student.tarc.edu.my" autofocus>
                <div class="field-error" id="emailError" style="display: none; color: #dc2626; font-size: 12px; margin-top: 5px;">Please enter a valid email address.</div>
            </div>

            <button type="submit" class="btn-login">Send Reset Link</button>
            <div class="back-to-login">
                <a href="login.php">&larr; Back to Login</a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('forgotForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email');
            const emailError = document.getElementById('emailError');
            
            emailError.style.display = 'none';
            email.style.borderColor = '';
            
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email.value.trim() || !emailPattern.test(email.value.trim())) {
                emailError.style.display = 'block';
                email.style.borderColor = '#dc2626';
                e.preventDefault();
            }
        });
        
        document.getElementById('email').addEventListener('input', function() {
            document.getElementById('emailError').style.display = 'none';
            this.style.borderColor = '';
        });
    </script>
    <script src="login-bg.js"></script>
</body>
</html>
