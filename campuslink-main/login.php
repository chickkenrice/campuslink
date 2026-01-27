<?php
session_start();
require_once(__DIR__ . '/includes/config.php');
$error = '';


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
        $stmt = $db->prepare("SELECT studentID, studentName FROM student WHERE studentID = ?");
        $stmt->bind_param("s", $userID);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && $password === 'abc') {
            $_SESSION['user_id'] = $user['studentID'];
            $_SESSION['user_name'] = $user['studentName'];
            $_SESSION['role'] = 'student';
            $_SESSION['last_activity'] = time(); // Track session activity
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid Student ID or Password.";
        }
    } 
    // --- 2. STAFF LOGIN (Enabled) ---
    elseif ($role === 'staff') {
        $stmt = $db->prepare("SELECT staffID, staffName FROM staff WHERE staffID = ?");
        $stmt->bind_param("s", $userID);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && $password === 'abc') {
            $_SESSION['user_id'] = $user['staffID'];
            $_SESSION['user_name'] = $user['staffName'];
            $_SESSION['role'] = 'staff';
            $_SESSION['last_activity'] = time(); // Track session activity
            header("Location: staff/staff-dashboard.php");
            exit;
        } else {
            $error = "Invalid Staff ID or Password.";
        }
    }
    // --- 3. ADMIN LOGIN ---
    elseif ($role === 'admin') {
        $stmt = $db->prepare("SELECT adminID, adminName FROM admin WHERE adminID = ?");
        $stmt->bind_param("s", $userID);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && $password === 'abc') {
            $_SESSION['user_id'] = $user['adminID'];
            $_SESSION['user_name'] = $user['adminName'];
            $_SESSION['role'] = 'admin';
            $_SESSION['last_activity'] = time(); // Track session activity
            header("Location: admin/manage-students.php");
            exit;
        } else {
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
    <title>CAMPUSLink — Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/authentication.css">
</head>
<body>
    <div class="login-card">
        <div class="brand-icon">CL</div>
        <h1>Welcome Back</h1>
        <p>Please log in to your portal</p>
        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
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
                <input type="text" id="studentID" name="studentID" placeholder="ID (e.g. 23WP..., STAFF01, ADMIN01)" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••" required>
            </div>

            <button type="submit" class="btn-login">Log In</button>
        </form>
        <div class="hint">(Default password is "abc")</div>
    </div>
</body>
</html>