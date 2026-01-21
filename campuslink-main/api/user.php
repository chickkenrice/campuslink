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

// Smart Query: Joins all tables to find the name, no matter the role
$sql = "SELECT u.userID, u.role,
        COALESCE(s.studentName, st.staffName, a.adminName, 'User') as name,
        COALESCE(s.email, st.email, a.email, 'N/A') as email
        FROM users u
        LEFT JOIN student s ON u.userID = s.userID
        LEFT JOIN staff st ON u.userID = st.userID
        LEFT JOIN admin a ON u.userID = a.userID
        WHERE u.userID = ?";

$stmt = $db->prepare($sql);
$stmt->bind_param("s", $userID);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if ($data) {
    echo json_encode([
        'status' => 'success',
        'data' => [
            'id' => $data['userID'],
            'role' => $data['role'],
            'name' => $data['name'],
            'email' => $data['email']
        ]
    ]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'user_not_found']);
}
?>