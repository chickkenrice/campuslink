<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../includes/config.php';
session_start();

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

$studentID = $_SESSION['user_id'];
$db = get_db_connection();

// 2. Fetch Student Details
$sql = "SELECT studentName, email, programme, tutGroup FROM student WHERE studentID = ?";
$stmt = $db->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'query_error']);
    exit;
}

$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if ($data) {
    // 3. Return Data as JSON
    echo json_encode([
        'status' => 'success',
        'data' => [
            'name' => $data['studentName'],
            'email' => $data['email'],
            'programme' => explode(':', $data['programme'])[0], // Simplify 'RSD: Software...' to 'RSD'
            'group' => $data['tutGroup']
        ]
    ]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'student_not_found']);
}
?>