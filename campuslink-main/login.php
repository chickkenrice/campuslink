<?php
session_start();
require_once(__DIR__ . '/includes/config.php');
require_once(__DIR__ . '/includes/activity-logger.php');
$error = '';

// Prevent browser from caching the login page
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// If a logged-in user reaches the login page (e.g. via Back button),
// destroy their session so they must re-login.
// Forward button will then force a server re-request (no-cache) which
// finds no session and redirects back here.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['user_id'])) {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    session_start(); // Start fresh session for login form
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userID = '';
    if (isset($_POST['studentID'])) {
        $userID = trim($_POST['studentID']);
    } elseif (isset($_POST['staffID'])) {
        $userID = trim($_POST['staffID']);
    } elseif (isset($_POST['adminID'])) {
        $userID = trim($_POST['adminID']);
    }
    
    $password = $_POST['password'];
    $role = $_POST['role']; 

    $db = get_db_connection();
        
    // --- 1. STUDENT LOGIN ---
    if ($role === 'student') {
        $stmt = $db->prepare("SELECT s.studentID, s.studentName, s.userID, u.password AS hashed_pw FROM student s JOIN users u ON s.userID = u.userID WHERE s.studentID = ?");
        $stmt->bind_param("s", $userID);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['hashed_pw'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['studentID'];
            $_SESSION['actual_user_id'] = $user['userID']; // Store actual userID for logging
            $_SESSION['user_name'] = $user['studentName'];
            $_SESSION['role'] = 'student';
            $_SESSION['last_activity'] = time(); // Track session activity
            
            // Log successful login using userID from users table
            logActivity($db, $user['userID'], 'LOGIN', 'Student logged in successfully', ['userName' => $user['studentName']]);
            
            header("Location: index.php");
            exit;
        } else {
            // Log failed login attempt
            logActivity($db, null, 'LOGIN_FAILED', 'Failed login attempt for Student ID: ' . $userID, ['attemptedID' => $userID, 'role' => 'student']);
            $error = "Invalid Student ID or Password.";
        }
    } 
    // --- 2. STAFF LOGIN (Enabled) ---
    elseif ($role === 'staff') {
        $stmt = $db->prepare("SELECT st.staffID, st.staffName, st.userID, u.password AS hashed_pw FROM staff st JOIN users u ON st.userID = u.userID WHERE st.staffID = ?");
        $stmt->bind_param("s", $userID);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['hashed_pw'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['staffID'];
            $_SESSION['actual_user_id'] = $user['userID']; // Store actual userID for logging
            $_SESSION['user_name'] = $user['staffName'];
            $_SESSION['role'] = 'staff';
            $_SESSION['last_activity'] = time(); // Track session activity
            
            // Log successful login using userID from users table
            logActivity($db, $user['userID'], 'LOGIN', 'Staff logged in successfully', ['userName' => $user['staffName']]);
            
            header("Location: staff/staff-dashboard.php");
            exit;
        } else {
            // Log failed login attempt
            logActivity($db, null, 'LOGIN_FAILED', 'Failed login attempt for Staff ID: ' . $userID, ['attemptedID' => $userID, 'role' => 'staff']);
            $error = "Invalid Staff ID or Password.";
        }
    }
    // --- 3. ADMIN LOGIN ---
    elseif ($role === 'admin') {
        $stmt = $db->prepare("SELECT a.adminID, a.adminName, a.userID, u.password AS hashed_pw FROM admin a JOIN users u ON a.userID = u.userID WHERE a.adminID = ?");
        $stmt->bind_param("s", $userID);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['hashed_pw'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['adminID'];
            $_SESSION['actual_user_id'] = $user['userID']; // Store actual userID for logging
            $_SESSION['user_name'] = $user['adminName'];
            $_SESSION['role'] = 'admin';
            $_SESSION['last_activity'] = time(); // Track session activity
            
            // Log successful login using userID from users table
            logActivity($db, $user['userID'], 'LOGIN', 'Admin logged in successfully', ['userName' => $user['adminName']]);
            
            header("Location: admin/manage-students.php");
            exit;
        } else {
            // Log failed login attempt
            logActivity($db, null, 'LOGIN_FAILED', 'Failed login attempt for Admin ID: ' . $userID, ['attemptedID' => $userID, 'role' => 'admin']);
            $error = "Invalid Admin ID or Password.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>window.addEventListener("pageshow",function(e){if(e.persisted){window.location.reload();}});</script>
    <title>CAMPUSLink — Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/authentication.css">
</head>
<body>
    <canvas id="bg-canvas"></canvas>
    <div class="login-card">
        <div class="brand-icon">CL</div>
        <h1>Welcome Back</h1>
        <p>Please log in to your portal</p>
        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" id="loginForm" novalidate>
            <div class="role-selector">
                <input type="radio" id="role-student" name="role" value="student" checked>
                <label for="role-student">Student</label>
                
                <input type="radio" id="role-staff" name="role" value="staff">
                <label for="role-staff">Staff</label>
                
                <input type="radio" id="role-admin" name="role" value="admin">
                <label for="role-admin">Admin</label>
            </div>

            <div class="form-group">
                <label for="studentID">User ID</label>
                <input type="text" id="studentID" name="studentID" autofocus>
                <div class="field-error" id="userIdError" style="display: none; color: #dc2626; font-size: 12px; margin-top: 5px;">User ID cannot be empty.</div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="pw-wrapper">
                    <input type="password" id="password" name="password" >
                    <button type="button" class="pw-toggle" onclick="togglePassword('password', this)" tabindex="-1">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
                <div class="field-error" id="passwordError" style="display: none; color: #dc2626; font-size: 12px; margin-top: 5px;">Password cannot be empty.</div>
            </div>

            <button type="submit" class="btn-login">Log In</button>
            <div class="forgot-password-link">
                <a href="forgot-password.php">Forgot Password?</a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.className = isHidden ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
        }

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const userId = document.getElementById('studentID');
            const password = document.getElementById('password');
            const userIdError = document.getElementById('userIdError');
            const passwordError = document.getElementById('passwordError');
            
            let isValid = true;
            
            // Reset errors
            userIdError.style.display = 'none';
            passwordError.style.display = 'none';
            userId.style.borderColor = '';
            password.style.borderColor = '';
            
            // Validate User ID
            if (!userId.value.trim()) {
                userIdError.style.display = 'block';
                userId.style.borderColor = '#dc2626';
                isValid = false;
            }
            
            // Validate Password
            if (!password.value) {
                passwordError.style.display = 'block';
                password.style.borderColor = '#dc2626';
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        // Clear errors on input
        document.getElementById('studentID').addEventListener('input', function() {
            document.getElementById('userIdError').style.display = 'none';
            this.style.borderColor = '';
        });
        document.getElementById('password').addEventListener('input', function() {
            document.getElementById('passwordError').style.display = 'none';
            this.style.borderColor = '';
        });
    </script>
    <script src="login-bg.js"></script>
</body>
</html>