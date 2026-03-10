<?php
/**
 * Notifications API
 * Handles fetching notifications and marking as read for students and staff
 */
session_start();
header('Content-Type: application/json');
require_once '../includes/config.php';

// Security check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['student', 'staff'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$db = get_db_connection();
$userID = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// GET: Fetch notifications + upcoming classes
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'list') {
        $notifications = [];
        
        // 1. Fetch stored notifications (assignments, bookings, etc.) - preview only
        $stmt = $db->prepare("SELECT id, type, title, message, link, isRead, createdAt 
                              FROM notifications 
                              WHERE userID = ? 
                              ORDER BY isRead ASC, createdAt DESC 
                              LIMIT 10");
        $stmt->bind_param("s", $userID);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'id' => $row['id'],
                'type' => $row['type'],
                'title' => $row['title'],
                'message' => $row['message'],
                'link' => $row['link'],
                'isRead' => (bool)$row['isRead'],
                'time' => timeAgo($row['createdAt']),
                'stored' => true
            ];
        }
        
        // 2. Check for upcoming classes (real-time, within 30 minutes)
        if ($userRole === 'student') {
            $upcomingClasses = getUpcomingClasses($db, $userID);
        } else {
            $upcomingClasses = getUpcomingClassesStaff($db, $userID);
        }
        foreach ($upcomingClasses as $class) {
            array_unshift($notifications, [
                'id' => 'class_' . $class['scheduleID'],
                'type' => 'class_soon',
                'title' => 'Class Starting Soon',
                'message' => $class['courseName'] . ' in ' . $class['minutesUntil'] . ' min • ' . $class['venue'],
                'link' => $userRole === 'staff' ? 'staff-timetable.php' : 'student-timetable.php',
                'isRead' => false,
                'time' => 'Now',
                'stored' => false
            ]);
        }
        
        // Count unread
        $unreadCount = 0;
        foreach ($notifications as $n) {
            if (!$n['isRead']) $unreadCount++;
        }
        
        echo json_encode([
            'status' => 'success',
            'unreadCount' => $unreadCount,
            'notifications' => $notifications
        ]);
        exit;
    }
    
    if ($action === 'list_all') {
        // Full paginated list for the notifications page (no real-time classes mixed in)
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        // Total count
        $stmtTotal = $db->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE userID = ?");
        $stmtTotal->bind_param("s", $userID);
        $stmtTotal->execute();
        $total = $stmtTotal->get_result()->fetch_assoc()['cnt'];

        $stmt = $db->prepare("SELECT id, type, title, message, link, isRead, createdAt 
                              FROM notifications 
                              WHERE userID = ? 
                              ORDER BY isRead ASC, createdAt DESC 
                              LIMIT ? OFFSET ?");
        $stmt->bind_param("sii", $userID, $perPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'id'      => $row['id'],
                'type'    => $row['type'],
                'title'   => $row['title'],
                'message' => $row['message'],
                'link'    => $row['link'],
                'isRead'  => (bool)$row['isRead'],
                'time'    => timeAgo($row['createdAt']),
                'stored'  => true
            ];
        }

        echo json_encode([
            'status'        => 'success',
            'notifications' => $notifications,
            'total'         => (int)$total,
            'page'          => $page,
            'perPage'       => $perPage,
            'totalPages'    => max(1, (int)ceil($total / $perPage))
        ]);
        exit;
    }

    if ($action === 'count') {
        // Quick count for badge updates
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE userID = ? AND isRead = 0");
        $stmt->bind_param("s", $userID);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['cnt'];
        
        // Add upcoming classes count
        if ($userRole === 'student') {
            $upcomingClasses = getUpcomingClasses($db, $userID);
        } else {
            $upcomingClasses = getUpcomingClassesStaff($db, $userID);
        }
        $count += count($upcomingClasses);

        // Return class schedule IDs so JS can track which ones have been seen
        $classIds = array_map(function($class) {
            return 'class_' . $class['scheduleID'];
        }, $upcomingClasses);
        
        echo json_encode(['status' => 'success', 'count' => $count, 'classIds' => array_values($classIds)]);
        exit;
    }
}

// POST: Mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'markRead') {
        $notifId = intval($_POST['id'] ?? 0);
        if ($notifId > 0) {
            $stmt = $db->prepare("UPDATE notifications SET isRead = 1 WHERE id = ? AND userID = ?");
            $stmt->bind_param("is", $notifId, $userID);
            $stmt->execute();
        }
        echo json_encode(['status' => 'success']);
        exit;
    }
    
    if ($action === 'markAllRead') {
        $stmt = $db->prepare("UPDATE notifications SET isRead = 1 WHERE userID = ?");
        $stmt->bind_param("s", $userID);
        $stmt->execute();
        echo json_encode(['status' => 'success']);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);

// Helper function: Get upcoming classes within 30 minutes
function getUpcomingClasses($db, $studentID) {
    $classes = [];
    $today = date('l'); // Day name
    $now = date('H:i:s');
    $soon = date('H:i:s', strtotime('+30 minutes'));
    
    // Get student's program and group
    $stmt = $db->prepare("SELECT programID, tutGroup, currentYear, currentSemester FROM student WHERE studentID = ?");
    $stmt->bind_param("s", $studentID);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    
    if (!$student) return $classes;
    
    // Get enrolled courses
    $coursesSql = "SELECT courseID FROM program_course 
                   WHERE programID = ? AND year = ? AND semester = ?";
    $stmtC = $db->prepare($coursesSql);
    $stmtC->bind_param("sii", $student['programID'], $student['currentYear'], $student['currentSemester']);
    $stmtC->execute();
    $coursesResult = $stmtC->get_result();
    $courseIDs = [];
    while ($c = $coursesResult->fetch_assoc()) {
        $courseIDs[] = "'" . $db->real_escape_string($c['courseID']) . "'";
    }
    
    if (empty($courseIDs)) return $classes;
    
    $courseList = implode(',', $courseIDs);
    $tutGroup = $db->real_escape_string($student['tutGroup']);
    
    // Find classes starting soon
    $sql = "SELECT cs.scheduleID, cs.courseID, c.courseName, cs.startTime, f.facilityName AS venue
            FROM class_schedule cs
            JOIN course c ON cs.courseID = c.courseID
            LEFT JOIN facility f ON cs.facilityID = f.facilityID
            WHERE cs.day = ?
            AND cs.courseID IN ($courseList)
            AND (cs.tutGroup = ? OR cs.tutGroup = 'All' OR cs.tutGroup = 'Combined')
            AND cs.startTime > ?
            AND cs.startTime <= ?
            ORDER BY cs.startTime ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ssss", $today, $tutGroup, $now, $soon);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $startTime = strtotime($row['startTime']);
        $nowTime = time();
        $minutesUntil = round(($startTime - $nowTime) / 60);
        
        if ($minutesUntil > 0 && $minutesUntil <= 30) {
            $row['minutesUntil'] = $minutesUntil;
            $classes[] = $row;
        }
    }
    
    return $classes;
}

// Helper function: Get upcoming classes for staff within 30 minutes
function getUpcomingClassesStaff($db, $staffID) {
    $classes = [];
    $today = date('l');
    $now = date('H:i:s');
    $soon = date('H:i:s', strtotime('+30 minutes'));
    
    $sql = "SELECT cs.scheduleID, cs.courseID, c.courseName, cs.startTime, f.facilityName AS venue
            FROM class_schedule cs
            JOIN course c ON cs.courseID = c.courseID
            LEFT JOIN facility f ON cs.facilityID = f.facilityID
            WHERE cs.staffID = ?
            AND cs.day = ?
            AND cs.startTime > ?
            AND cs.startTime <= ?
            ORDER BY cs.startTime ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ssss", $staffID, $today, $now, $soon);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $startTime = strtotime($row['startTime']);
        $nowTime = time();
        $minutesUntil = round(($startTime - $nowTime) / 60);
        
        if ($minutesUntil > 0 && $minutesUntil <= 30) {
            $row['minutesUntil'] = $minutesUntil;
            $classes[] = $row;
        }
    }
    
    return $classes;
}

// Helper function: Format time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('j M', $time);
}
?>
