<?php
session_start();
require_once(__DIR__ . '/includes/config.php');
require_once(__DIR__ . '/includes/activity-logger.php');

// Log logout activity BEFORE destroying session
if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {
    $db = get_db_connection();
    // Use actual_user_id if available, otherwise look it up based on role
    $userIDForLog = $_SESSION['actual_user_id'] ?? null;
    if (!$userIDForLog && isset($_SESSION['role'])) {
        $roleID = $_SESSION['user_id'];
        $role = $_SESSION['role'];
        if ($role === 'student') {
            $stmt = $db->prepare("SELECT userID FROM student WHERE studentID = ?");
        } elseif ($role === 'staff') {
            $stmt = $db->prepare("SELECT userID FROM staff WHERE staffID = ?");
        } elseif ($role === 'admin') {
            $stmt = $db->prepare("SELECT userID FROM admin WHERE adminID = ?");
        }
        if (isset($stmt)) {
            $stmt->bind_param("s", $roleID);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $userIDForLog = $result['userID'] ?? null;
            $stmt->close();
        }
    }
    logActivity($db, $userIDForLog, 'LOGOUT', 'User logged out', [
        'userName' => $_SESSION['user_name'],
        'role' => $_SESSION['role']
    ]);
    $db->close();
}

// 1. Clear all session variables
$_SESSION = array();

// 2. Destroy the session cookie (crucial for complete logout)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy session
session_destroy();

// 4. Prevent caching so browser can't go Back to authenticated pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 5. Redirect to login
header("Location: login.php");
exit;
?>