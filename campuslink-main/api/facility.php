<?php
header('Content-Type: application/json');
require_once '../config.php';
$db = get_db_connection();

$sql = "SELECT facilityID, facilityName, type, location, capacity FROM facility WHERE status = 'Active' ORDER BY facilityName";
$result = $db->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(['status' => 'success', 'data' => $data]);
?>