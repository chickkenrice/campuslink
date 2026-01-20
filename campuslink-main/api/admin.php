<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../includes/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

$userID = $_SESSION['user_id'];
$db = get_db_connection();

// 2. Fetch Admin Details
$sql = "SELECT adminName, email, contactNo FROM admin WHERE adminID = ?";
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
    echo json_encode([
        'status' => 'success',
        'data' => [
            'name' => $data['adminName'],
            'email' => $data['email'],
            'phone' => $data['contactNo']
        ]
    ]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'admin_profile_not_found']);
}
?>