<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../includes/config.php';
session_start();

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

$userID = $_SESSION['user_id'];
$db = get_db_connection();

// 2. Fetch Staff Details
$sql = "SELECT staffName, email, department FROM staff WHERE staffID = ?";
$stmt = $db->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'query_error']);
    exit;
}

$stmt->bind_param("s", $userID);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if ($data) {
    // 3. Return Data
    echo json_encode([
        'status' => 'success',
        'data' => [
            'name' => $data['staffName'],
            'email' => $data['email'],
            'department' => $data['department']
        ]
    ]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'staff_profile_not_found']);
}
?>