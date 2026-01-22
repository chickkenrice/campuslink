<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
session_start();

// Security: Only logged-in users
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = get_db_connection();
$method = $_SERVER['REQUEST_METHOD'];

// GET: Fetch requests (Staff sees their own, Admin sees all)
if ($method === 'GET') {
    $role = $_SESSION['role'];
    $userID = $_SESSION['user_id'];

    if ($role === 'admin') {
        $sql = "SELECT r.*, s.staffName, c.courseName 
                FROM replacement_request r
                JOIN class_schedule cs ON r.scheduleID = cs.scheduleID
                JOIN staff s ON cs.staffID = s.staffID
                JOIN course c ON cs.courseID = c.courseID
                ORDER BY r.created_at DESC";
        $stmt = $db->prepare($sql);
    } else {
        // Staff only sees their own requests
        $sql = "SELECT r.*, c.courseName 
                FROM replacement_request r
                JOIN class_schedule cs ON r.scheduleID = cs.scheduleID
                JOIN course c ON cs.courseID = c.courseID
                WHERE cs.staffID = ?
                ORDER BY r.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("s", $userID);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode(['data' => $result->fetch_all(MYSQLI_ASSOC)]);
}

// POST: Create a new request
elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    // Add validation & insert logic here
    // INSERT INTO replacement_request ...
}
?>