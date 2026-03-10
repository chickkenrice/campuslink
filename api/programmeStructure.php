<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

if (!isset($_GET['programID'])) {
    echo json_encode(['error' => 'Missing Program ID']);
    exit;
}

$db = get_db_connection();
$progID = $_GET['programID'];

$sql = "SELECT pc.year, pc.semester, c.courseID, c.courseName, c.creditHours, pc.type
        FROM program_course pc
        JOIN course c ON pc.courseID = c.courseID
        WHERE pc.programID = ?
        ORDER BY pc.year, pc.semester, c.courseID";

$stmt = $db->prepare($sql);
$stmt->bind_param("s", $progID);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode(['data' => $result->fetch_all(MYSQLI_ASSOC)]);
?>