<?php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentID = trim($_POST['studentID']);
    $password = $_POST['password'];
    $role = $_POST['role']; // Capture the selected role

    $db = get_db_connection();
    
    // LOGIC: You can now use $role to decide which table to check
    // For now, we keep the existing Student logic as default
    
    if ($role === 'student') {
        $stmt = $db->prepare("SELECT studentID, studentName FROM student WHERE studentID = ?");
        $stmt->bind_param("s", $studentID);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && $password === 'abc') {
            $_SESSION['user_id'] = $user['studentID'];
            $_SESSION['user_name'] = $user['studentName'];
            $_SESSION['role'] = 'student';
            header("Location: index.html");
            exit;
        } else {
            $error = "Invalid Student ID or Password.";
        }
    } 
    // Placeholder for Staff/Admin logic
    else {
        $error = "Login for $role is not implemented yet.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CAMPUSLink — Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="authentication.css">
</head>
<body>

    <div class="login-card">
        <div class="brand-icon">CL</div>
        <h1>Welcome Back</h1>
        <p>Please log in to your portal</p>

        <?php if ($error): ?>
            <div class="error-msg">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
            </div>
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
                <input type="text" id="studentID" name="studentID" placeholder="e.g. 23WP12345" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••" required>
            </div>

            <button type="submit" class="btn-login">Log In</button>
        </form>

        <div class="hint">
            (Default password is "abc")
        </div>
    </div>

</body>
</html>