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

// Helper: Parse attachment column (JSON array or legacy single filename)
function parseAttachments(?string $val): array {
    if (empty($val)) return [];
    $decoded = json_decode($val, true);
    return is_array($decoded) ? $decoded : [$val];
}

// ─────────────────────────────────────────────────────────────
// GET: Fetch announcements (optionally filter by audience)
// ─────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $audience = $_GET['audience'] ?? null; // 'student', 'staff', or null (all for admin)
    $adminView = isset($_GET['admin']) && isAdmin();
    
    if ($adminView) {
        // Admin gets all announcements
        $sql = "SELECT id, title, body, attachment, target_audience, announcement_type, is_pinned, created_by, created_at 
                FROM announcements ORDER BY is_pinned DESC, created_at DESC LIMIT 100";
        $stmt = $db->prepare($sql);
    } else {
        // Filter by audience: show 'all' + specific audience, pinned first
        $sql = "SELECT id, title, body, attachment, target_audience, announcement_type, is_pinned, created_at 
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
            'attachments' => parseAttachments($row['attachment'] ?? null),
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
    
    // Support both JSON and FormData (for file uploads)
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'multipart/form-data') !== false) {
        $input = $_POST;
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
    }
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
        
        // Handle multiple file uploads
        $attachments = [];
        if (!empty($_FILES['attachments']['name'][0])) {
            $allowed = ['pdf','doc','docx','xls','xlsx','ppt','pptx','jpg','jpeg','png','gif','zip','txt'];
            $uploadDir = __DIR__ . '/../uploads/announcements/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $files = $_FILES['attachments'];
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                $origName = basename($files['name'][$i]);
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) {
                    sendResponse(['success' => false, 'message' => 'File type not allowed: ' . $origName], 400);
                }
                if ($files['size'][$i] > 10 * 1024 * 1024) {
                    sendResponse(['success' => false, 'message' => 'File too large (max 10MB): ' . $origName], 400);
                }
                $safeName = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
                if (!move_uploaded_file($files['tmp_name'][$i], $uploadDir . $safeName)) {
                    sendResponse(['success' => false, 'message' => 'Failed to upload: ' . $origName], 500);
                }
                $attachments[] = $safeName;
            }
        }
        $attachmentJson = !empty($attachments) ? json_encode($attachments) : null;
        
        $stmt = $db->prepare("INSERT INTO announcements (title, body, attachment, target_audience, announcement_type, is_pinned, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssis", $title, $body, $attachmentJson, $audience, $annType, $isPinned, $createdBy);
        
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
        
        // Handle multiple file attachments with keep-list
        $keepList = [];
        if (!empty($input['keepAttachments'])) {
            $decoded = json_decode($input['keepAttachments'], true);
            if (is_array($decoded)) {
                $keepList = array_values(array_filter($decoded, fn($f) => is_string($f) && strpos($f, '/') === false && strpos($f, '\\') === false));
            }
        }
        $uploadDir = __DIR__ . '/../uploads/announcements/';

        // Delete existing files that are not being kept
        $oldStmt = $db->prepare("SELECT attachment FROM announcements WHERE id = ?");
        $oldStmt->bind_param("i", $id);
        $oldStmt->execute();
        $oldRow = $oldStmt->get_result()->fetch_assoc();
        foreach (parseAttachments($oldRow['attachment'] ?? null) as $f) {
            if (!in_array($f, $keepList, true)) {
                $path = $uploadDir . $f;
                if (file_exists($path)) unlink($path);
            }
        }

        // Upload new files
        $newFiles = [];
        if (!empty($_FILES['attachments']['name'][0])) {
            $allowed = ['pdf','doc','docx','xls','xlsx','ppt','pptx','jpg','jpeg','png','gif','zip','txt'];
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $files = $_FILES['attachments'];
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                $origName = basename($files['name'][$i]);
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) {
                    sendResponse(['success' => false, 'message' => 'File type not allowed: ' . $origName], 400);
                }
                if ($files['size'][$i] > 10 * 1024 * 1024) {
                    sendResponse(['success' => false, 'message' => 'File too large (max 10MB): ' . $origName], 400);
                }
                $safeName = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
                if (!move_uploaded_file($files['tmp_name'][$i], $uploadDir . $safeName)) {
                    sendResponse(['success' => false, 'message' => 'Failed to upload: ' . $origName], 500);
                }
                $newFiles[] = $safeName;
            }
        }

        $allAttachments = array_merge($keepList, $newFiles);
        $attachmentJson = !empty($allAttachments) ? json_encode(array_values($allAttachments)) : null;

        $stmt = $db->prepare("UPDATE announcements SET title = ?, body = ?, attachment = ?, target_audience = ?, announcement_type = ?, is_pinned = ? WHERE id = ?");
        $stmt->bind_param("sssssii", $title, $body, $attachmentJson, $audience, $annType, $isPinned, $id);

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
        
        // Delete all attachment files
        $oldStmt = $db->prepare("SELECT attachment FROM announcements WHERE id = ?");
        $oldStmt->bind_param("i", $id);
        $oldStmt->execute();
        $oldRow = $oldStmt->get_result()->fetch_assoc();
        foreach (parseAttachments($oldRow['attachment'] ?? null) as $f) {
            $oldPath = __DIR__ . '/../uploads/announcements/' . $f;
            if (file_exists($oldPath)) unlink($oldPath);
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


