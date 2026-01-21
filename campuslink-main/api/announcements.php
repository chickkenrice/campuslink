<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET') {
	http_response_code(405);
	echo json_encode(['error' => 'method_not_allowed']);
	exit;
}

$db = get_db_connection();

$sql = "SELECT id, title, body, created_at FROM announcements ORDER BY created_at DESC LIMIT 50";
$stmt = $db->prepare($sql);
if (!$stmt) {
	http_response_code(500);
	echo json_encode(['error' => 'query_prepare_failed']);
	exit;
}

$ok = $stmt->execute();
if (!$ok) {
	http_response_code(500);
	echo json_encode(['error' => 'query_execute_failed']);
	exit;
}

$result = $stmt->get_result();
$rows = [];
while ($row = $result->fetch_assoc()) {
	$rows[] = [
		'id' => (int)$row['id'],
		'title' => $row['title'],
		'body' => $row['body'],
		'createdAt' => $row['created_at']
	];
}

echo json_encode(['data' => $rows]);


