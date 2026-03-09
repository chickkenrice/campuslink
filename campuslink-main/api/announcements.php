<?php
declare(strict_types=1);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../includes/config.php';

$db = get_db_connection();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Helper: Check if user is admin
function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Helper: Send JSON response
function sendResponse(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// ─────────────────────────────────────────────────────────────
// GET: Fetch announcements (optionally filter by audience)
// ─────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $audience = $_GET['audience'] ?? null; // 'student', 'staff', or null (all for admin)
    $adminView = isset($_GET['admin']) && isAdmin();
    
    if ($adminView) {
        // Admin gets all announcements
        $sql = "SELECT id, title, body, target_audience, announcement_type, is_pinned, created_by, created_at 
                FROM announcements ORDER BY is_pinned DESC, created_at DESC LIMIT 100";
        $stmt = $db->prepare($sql);
    } else {
        // Filter by audience: show 'all' + specific audience, pinned first
        $sql = "SELECT id, title, body, target_audience, announcement_type, is_pinned, created_at 
                FROM announcements 
                WHERE target_audience = 'all' OR target_audience = ?
                ORDER BY is_pinned DESC, created_at DESC LIMIT 50";
        $stmt = $db->prepare($sql);
        $audience = $audience ?? 'all';
        $stmt->bind_param("s", $audience);
    }
    
    if (!$stmt || !$stmt->execute()) {
        sendResponse(['success' => false, 'error' => 'query_failed'], 500);
    }
    
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $item = [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'body' => $row['body'],
            'targetAudience' => $row['target_audience'],
            'announcementType' => $row['announcement_type'],
            'isPinned' => (bool)$row['is_pinned'],
            'createdAt' => $row['created_at']
        ];
        if ($adminView && isset($row['created_by'])) {
            $item['createdBy'] = $row['created_by'];
        }
        $rows[] = $item;
    }
    
    sendResponse(['success' => true, 'data' => $rows]);
}

// ─────────────────────────────────────────────────────────────
// POST: Create or Update announcement (Admin only)
// ─────────────────────────────────────────────────────────────
if ($method === 'POST') {
    if (!isAdmin()) {
        sendResponse(['success' => false, 'error' => 'unauthorized'], 403);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'create';
    
    if ($action === 'create') {
        $title = trim($input['title'] ?? '');
        $body = trim($input['body'] ?? '');
        $audience = $input['targetAudience'] ?? 'all';
        $annType = $input['announcementType'] ?? 'system';
        $isPinned = !empty($input['isPinned']) ? 1 : 0;
        $createdBy = $_SESSION['user_name'] ?? 'Admin';
        
        if (empty($title) || empty($body)) {
            sendResponse(['success' => false, 'message' => 'Title and body are required'], 400);
        }
        
        if (!in_array($audience, ['student', 'staff', 'all'])) {
            $audience = 'all';
        }
        if (!in_array($annType, ['system', 'academic', 'campus'])) {
            $annType = 'system';
        }
        
        $stmt = $db->prepare("INSERT INTO announcements (title, body, target_audience, announcement_type, is_pinned, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssis", $title, $body, $audience, $annType, $isPinned, $createdBy);
        
        if ($stmt->execute()) {
            sendResponse(['success' => true, 'message' => 'Announcement created successfully', 'id' => $db->insert_id]);
        } else {
            sendResponse(['success' => false, 'message' => 'Failed to create announcement'], 500);
        }
    }
    
    if ($action === 'update') {
        $id = (int)($input['id'] ?? 0);
        $title = trim($input['title'] ?? '');
        $body = trim($input['body'] ?? '');
        $audience = $input['targetAudience'] ?? 'all';
        $annType = $input['announcementType'] ?? 'system';
        $isPinned = !empty($input['isPinned']) ? 1 : 0;
        
        if ($id <= 0 || empty($title) || empty($body)) {
            sendResponse(['success' => false, 'message' => 'ID, title, and body are required'], 400);
        }
        
        if (!in_array($audience, ['student', 'staff', 'all'])) {
            $audience = 'all';
        }
        if (!in_array($annType, ['system', 'academic', 'campus'])) {
            $annType = 'system';
        }
        
        $stmt = $db->prepare("UPDATE announcements SET title = ?, body = ?, target_audience = ?, announcement_type = ?, is_pinned = ? WHERE id = ?");
        $stmt->bind_param("ssssii", $title, $body, $audience, $annType, $isPinned, $id);
        
        if ($stmt->execute() && $stmt->affected_rows >= 0) {
            sendResponse(['success' => true, 'message' => 'Announcement updated successfully']);
        } else {
            sendResponse(['success' => false, 'message' => 'Failed to update announcement'], 500);
        }
    }
    
    if ($action === 'togglePin') {
        $id = (int)($input['id'] ?? 0);
        
        if ($id <= 0) {
            sendResponse(['success' => false, 'message' => 'Invalid announcement ID'], 400);
        }
        
        $stmt = $db->prepare("UPDATE announcements SET is_pinned = NOT is_pinned WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute() && $stmt->affected_rows >= 0) {
            sendResponse(['success' => true, 'message' => 'Pin status toggled']);
        } else {
            sendResponse(['success' => false, 'message' => 'Failed to toggle pin'], 500);
        }
    }
    
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        
        if ($id <= 0) {
            sendResponse(['success' => false, 'message' => 'Invalid announcement ID'], 400);
        }
        
        $stmt = $db->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            sendResponse(['success' => true, 'message' => 'Announcement deleted successfully']);
        } else {
            sendResponse(['success' => false, 'message' => 'Announcement not found or already deleted'], 404);
        }
    }
    
    sendResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

// Method not allowed
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'method_not_allowed']);


