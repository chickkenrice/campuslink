<?php
/**
 * Activity Logger
 * Handles recording of user activities across the system
 */

/**
 * Log user activity to the database
 * 
 * @param mysqli $db Database connection
 * @param string|null $userID User ID performing the action (null for anonymous actions like failed login)
 * @param string $activityType Type of activity (e.g., 'LOGIN', 'USER_CREATE', 'LOGOUT')
 * @param string $description Human-readable description of the activity
 * @param array|null $details Additional details to store as JSON
 * @return bool Success status
 */
function logActivity($db, $userID, $activityType, $description, $details = null) {
    try {
        $userName = null;
        $userRole = null;
        
        // Get user information if userID is provided
        if ($userID) {
            $userInfo = getUserInfo($db, $userID);
            $userName = $userInfo['name'] ?? 'Unknown';
            $userRole = $userInfo['role'] ?? 'Unknown';
        }
        
        // Get client IP address
        $ipAddress = getClientIP();
        
        // Convert details array to JSON if provided
        $detailsJson = $details ? json_encode($details) : null;
        
        // Insert activity log
        $stmt = $db->prepare("INSERT INTO activity_logs (userID, userName, userRole, activity_type, activity_description, ip_address, details) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            error_log("Activity Log Error: " . $db->error);
            return false;
        }
        
        $stmt->bind_param("sssssss", $userID, $userName, $userRole, $activityType, $description, $ipAddress, $detailsJson);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Activity Log Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user information from database
 * 
 * @param mysqli $db Database connection
 * @param string $userID User ID
 * @return array User information (name and role)
 */
function getUserInfo($db, $userID) {
    // First, get role from users table
    $stmt = $db->prepare("SELECT role FROM users WHERE userID = ?");
    $stmt->bind_param("s", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $role = $row['role'];
        $name = null;
        
        // Get name from appropriate table based on role
        if ($role === 'Student') {
            $stmt2 = $db->prepare("SELECT studentName FROM student WHERE userID = ?");
            $stmt2->bind_param("s", $userID);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            if ($row2 = $result2->fetch_assoc()) {
                $name = $row2['studentName'];
            }
            $stmt2->close();
        } elseif ($role === 'Staff') {
            $stmt2 = $db->prepare("SELECT staffName FROM staff WHERE userID = ?");
            $stmt2->bind_param("s", $userID);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            if ($row2 = $result2->fetch_assoc()) {
                $name = $row2['staffName'];
            }
            $stmt2->close();
        } elseif ($role === 'Admin') {
            $stmt2 = $db->prepare("SELECT adminName FROM admin WHERE userID = ?");
            $stmt2->bind_param("s", $userID);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            if ($row2 = $result2->fetch_assoc()) {
                $name = $row2['adminName'];
            }
            $stmt2->close();
        }
        
        $stmt->close();
        return ['name' => $name, 'role' => $role];
    }
    
    $stmt->close();
    return ['name' => 'Unknown', 'role' => 'Unknown'];
}

/**
 * Get client IP address (handles proxies)
 * 
 * @return string IP address
 */
function getClientIP() {
    $ipAddress = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    return $ipAddress;
}

/**
 * Get activity logs with optional filters
 * 
 * @param mysqli $db Database connection
 * @param array $filters Optional filters (startDate, endDate, userRole, activityType, userID, search)
 * @param int $page Page number for pagination (default: 1)
 * @param int $perPage Number of records per page (default: 50)
 * @return array Array containing 'logs' and 'total' count
 */
function getActivityLogs($db, $filters = [], $page = 1, $perPage = 50) {
    $conditions = [];
    $params = [];
    $types = '';
    
    // Date range filter
    if (!empty($filters['startDate'])) {
        $conditions[] = "timestamp >= ?";
        $params[] = $filters['startDate'] . ' 00:00:00';
        $types .= 's';
    }
    
    if (!empty($filters['endDate'])) {
        $conditions[] = "timestamp <= ?";
        $params[] = $filters['endDate'] . ' 23:59:59';
        $types .= 's';
    }
    
    // User role filter
    if (!empty($filters['userRole'])) {
        $conditions[] = "userRole = ?";
        $params[] = $filters['userRole'];
        $types .= 's';
    }
    
    // Activity type filter
    if (!empty($filters['activityType'])) {
        $conditions[] = "activity_type = ?";
        $params[] = $filters['activityType'];
        $types .= 's';
    }
    
    // Specific user filter
    if (!empty($filters['userID'])) {
        $conditions[] = "userID = ?";
        $params[] = $filters['userID'];
        $types .= 's';
    }
    
    // Search filter (searches in userName and activity_description)
    if (!empty($filters['search'])) {
        $conditions[] = "(userName LIKE ? OR activity_description LIKE ?)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'ss';
    }
    
    // Build WHERE clause
    $whereClause = '';
    if (count($conditions) > 0) {
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
    }
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM activity_logs $whereClause";
    $countStmt = $db->prepare($countSql);
    
    if ($types) {
        $countStmt->bind_param($types, ...$params);
    }
    
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];
    $countStmt->close();
    
    // Calculate offset for pagination
    $offset = ($page - 1) * $perPage;
    
    // Get logs with pagination
    $sql = "SELECT * FROM activity_logs $whereClause ORDER BY timestamp DESC LIMIT ? OFFSET ?";
    $stmt = $db->prepare($sql);
    
    // Add limit and offset to params
    $params[] = $perPage;
    $params[] = $offset;
    $types .= 'ii';
    
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    $stmt->close();
    
    return [
        'logs' => $logs,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => ceil($total / $perPage)
    ];
}

/**
 * Get activity statistics
 * 
 * @param mysqli $db Database connection
 * @param string $period Period for stats ('today', 'week', 'month')
 * @return array Statistics data
 */
function getActivityStats($db, $period = 'today') {
    $dateCondition = '';
    
    switch ($period) {
        case 'today':
            $dateCondition = "DATE(timestamp) = CURDATE()";
            break;
        case 'week':
            $dateCondition = "timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $dateCondition = "timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        default:
            $dateCondition = "DATE(timestamp) = CURDATE()";
    }
    
    // Total activities
    $totalSql = "SELECT COUNT(*) as total FROM activity_logs WHERE $dateCondition";
    $totalResult = $db->query($totalSql);
    $total = $totalResult->fetch_assoc()['total'];
    
    // Activities by type
    $typeSql = "SELECT activity_type, COUNT(*) as count FROM activity_logs WHERE $dateCondition GROUP BY activity_type ORDER BY count DESC LIMIT 5";
    $typeResult = $db->query($typeSql);
    $byType = [];
    while ($row = $typeResult->fetch_assoc()) {
        $byType[] = $row;
    }
    
    // Activities by role
    $roleSql = "SELECT userRole, COUNT(*) as count FROM activity_logs WHERE $dateCondition AND userRole IS NOT NULL GROUP BY userRole";
    $roleResult = $db->query($roleSql);
    $byRole = [];
    while ($row = $roleResult->fetch_assoc()) {
        $byRole[] = $row;
    }
    
    // Most active users
    $userSql = "SELECT userName, userRole, COUNT(*) as count FROM activity_logs WHERE $dateCondition AND userName IS NOT NULL GROUP BY userName, userRole ORDER BY count DESC LIMIT 5";
    $userResult = $db->query($userSql);
    $mostActive = [];
    while ($row = $userResult->fetch_assoc()) {
        $mostActive[] = $row;
    }
    
    return [
        'total' => $total,
        'byType' => $byType,
        'byRole' => $byRole,
        'mostActive' => $mostActive
    ];
}

/**
 * Export activity logs to CSV
 * 
 * @param mysqli $db Database connection
 * @param array $filters Optional filters
 * @return string CSV content
 */
function exportActivityLogsToCSV($db, $filters = []) {
    // Get all logs with filters (no pagination)
    $logs = getActivityLogs($db, $filters, 1, 100000)['logs'];
    
    // CSV header
    $csv = "Timestamp,User ID,User Name,User Role,Activity Type,Description,Details\n";
    
    // CSV rows
    foreach ($logs as $log) {
        $csv .= sprintf(
            '"%s","%s","%s","%s","%s","%s","%s"' . "\n",
            $log['timestamp'],
            $log['userID'] ?? 'N/A',
            $log['userName'] ?? 'N/A',
            $log['userRole'] ?? 'N/A',
            $log['activity_type'],
            str_replace('"', '""', $log['activity_description']),
            str_replace('"', '""', $log['details'] ?? '')
        );
    }
    
    return $csv;
}

/**
 * Delete old activity logs
 * 
 * @param mysqli $db Database connection
 * @param int $daysToKeep Number of days to keep (delete older logs)
 * @return int Number of deleted records
 */
function deleteOldLogs($db, $daysToKeep = 90) {
    $stmt = $db->prepare("DELETE FROM activity_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->bind_param("i", $daysToKeep);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();
    
    return $deleted;
}
