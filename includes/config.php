<?php
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


