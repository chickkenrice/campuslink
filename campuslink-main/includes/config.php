<?php
// Session Configuration - Prevent random logouts
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 7200); // 2 hours
    ini_set('session.cookie_lifetime', 7200); // 2 hours
}

// SET TIMEZONE TO MALAYSIA
date_default_timezone_set('Asia/Kuala_Lumpur');

// Update these values to match your local MySQL credentials and database name.
// Default XAMPP MySQL user is 'root' with empty password on Windows.
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'campuslink');
define('DB_USER', 'root');
define('DB_PASS', '');

function get_db_connection(): mysqli {
	$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if ($mysqli->connect_errno) {
		http_response_code(500);
		header('Content-Type: application/json');
		echo json_encode([
			'error' => 'database_connection_failed',
			'message' => $mysqli->connect_error
		]);
		exit;
	}
	$mysqli->set_charset('utf8mb4');
	return $mysqli;
}

/**
 * Validate and refresh session
 * Prevents random logouts by ensuring session is active and valid
 */
function validate_session() {
    // Check if session has required data
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        return false;
    }
    
    // Check if session has expired (optional activity timeout)
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        if ($inactive_time > 7200) { // 2 hours
            return false;
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    return true;
}


