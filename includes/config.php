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

// Auto-progression: advance students to next semester when term starts
require_once __DIR__ . '/auto-progression.php';

/**
 * Auto-sync academic_term status based on today's date.
 * Active = today is within startDate..endDate
 * Upcoming = startDate is in the future
 * Completed = endDate is in the past
 * Runs once per PHP process (static guard prevents repeated DB calls).
 */
function autoUpdateTermStatuses() {
    static $synced = false;
    if ($synced) return;
    $synced = true;

    $db = get_db_connection();
    $db->query("
        UPDATE academic_term SET status = CASE
            WHEN endDate < CURDATE() THEN 'Completed'
            WHEN startDate <= CURDATE() AND endDate >= CURDATE() THEN 'Active'
            ELSE 'Upcoming'
        END
    ");
    $db->close();
}

/**
 * Send no-cache headers AND inject JavaScript to defeat browser bfcache.
 * HTTP headers alone don't prevent bfcache (back-forward cache) in modern browsers.
 * The pageshow JS event detects bfcache restore and forces a real server reload.
 */
function prevent_back_button_cache() {
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Inject bfcache-busting JS into the page output
    ob_start(function($html) {
        $script = '<script>window.addEventListener("pageshow",function(e){if(e.persisted){window.location.reload();}});</script>';
        // Insert before </head> if possible, otherwise before </body>
        if (stripos($html, '</head>') !== false) {
            return str_ireplace('</head>', $script . '</head>', $html);
        } elseif (stripos($html, '</body>') !== false) {
            return str_ireplace('</body>', $script . '</body>', $html);
        }
        return $html . $script;
    });
}

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
	// Sync MySQL session timezone with PHP (Asia/Kuala_Lumpur = UTC+8)
	// This ensures CURDATE()/CURTIME() return Malaysia time on any server
	$mysqli->query("SET time_zone = '+08:00'");
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

    // Prevent browser from caching authenticated pages (skip for JSON API responses)
    $isApiRequest = false;
    foreach (headers_list() as $header) {
        if (stripos($header, 'Content-Type: application/json') !== false) {
            $isApiRequest = true;
            break;
        }
    }
    if (!$isApiRequest) {
        prevent_back_button_cache();
    }

    // Auto-sync term statuses based on dates (runs once per request, all roles)
    if (!$isApiRequest) {
        autoUpdateTermStatuses();
    }

    // Auto-advance student to next semester if the next term has started
    if ($_SESSION['role'] === 'student') {
        checkAutoProgression();
    }
    
    return true;
}


