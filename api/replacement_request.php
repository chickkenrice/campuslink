<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
session_start();

// Security: Only logged-in users
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = get_db_connection();
$method = $_SERVER['REQUEST_METHOD'];

// ============================================================
// GET: Fetch requests
// ============================================================
if ($method === 'GET') {
    $role = $_SESSION['role'];
    $userID = $_SESSION['user_id'];
    $action = $_GET['action'] ?? 'list';

    // --- List requests ---
    if ($action === 'list') {
        if ($role === 'admin') {
            // Admin sees all requests with staff and course info
            $sql = "SELECT r.*, s.staffName, c.courseName, c.courseID, cs.day AS originalDay, 
                           cs.startTime AS originalStart, cs.endTime AS originalEnd,
                           cs.classType, cs.tutGroup, cs.programID,
                           f_old.facilityName AS originalVenue,
                           f_new.facilityName AS newVenue
                    FROM replacement_request r
                    JOIN class_schedule cs ON r.scheduleID = cs.scheduleID
                    JOIN staff s ON r.staffID = s.staffID
                    JOIN course c ON cs.courseID = c.courseID
                    LEFT JOIN facility f_old ON cs.facilityID = f_old.facilityID
                    LEFT JOIN facility f_new ON r.facilityID = f_new.facilityID
                    ORDER BY FIELD(r.status, 'Pending', 'Approved', 'Rejected'), r.created_at DESC";
            $stmt = $db->prepare($sql);
        } else {
            // Staff sees only their own requests
            $sql = "SELECT r.*, c.courseName, c.courseID, cs.day AS originalDay,
                           cs.startTime AS originalStart, cs.endTime AS originalEnd,
                           cs.classType, cs.tutGroup, cs.programID,
                           f_old.facilityName AS originalVenue,
                           f_new.facilityName AS newVenue
                    FROM replacement_request r
                    JOIN class_schedule cs ON r.scheduleID = cs.scheduleID
                    JOIN course c ON cs.courseID = c.courseID
                    LEFT JOIN facility f_old ON cs.facilityID = f_old.facilityID
                    LEFT JOIN facility f_new ON r.facilityID = f_new.facilityID
                    WHERE r.staffID = ?
                    ORDER BY r.created_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("s", $userID);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
    }

    // --- Get staff's class schedule (for the dropdown) ---
    elseif ($action === 'getStaffClasses') {
        $staffID = $_GET['staffID'] ?? $userID;
        $day = $_GET['day'] ?? '';
        $sql = "SELECT cs.scheduleID, cs.courseID, cs.day, cs.startTime, cs.endTime, 
                       cs.classType, cs.facilityID, cs.tutGroup, cs.programID,
                       c.courseName, f.facilityName
                FROM class_schedule cs
                JOIN course c ON cs.courseID = c.courseID
                JOIN facility f ON cs.facilityID = f.facilityID
                WHERE cs.staffID = ?";
        if (!empty($day)) {
            $sql .= " AND cs.day = ?";
        }
        $sql .= " ORDER BY FIELD(cs.day, 'Monday','Tuesday','Wednesday','Thursday','Friday'), cs.startTime";
        $stmt = $db->prepare($sql);
        if (!empty($day)) {
            $stmt->bind_param("ss", $staffID, $day);
        } else {
            $stmt->bind_param("s", $staffID);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode(['success' => true, 'data' => $result->fetch_all(MYSQLI_ASSOC)]);
    }

    // --- Get available facilities for a specific date and time ---
    elseif ($action === 'getAvailableFacilities') {
        $date = $_GET['date'] ?? '';
        $startTime = $_GET['startTime'] ?? '';
        $endTime = $_GET['endTime'] ?? '';

        if (empty($date) || empty($startTime) || empty($endTime)) {
            echo json_encode(['success' => false, 'message' => 'Date, start time and end time required']);
            exit;
        }

        // Convert date to day of week
        $dayOfWeek = date('l', strtotime($date));

        // Get all active facilities
        $allFacilities = $db->query("SELECT * FROM facility WHERE status = 'Active' ORDER BY type, facilityName");
        $facilities = $allFacilities->fetch_all(MYSQLI_ASSOC);

        // Get occupied facilities at that day/time (from regular schedule)
        $occupiedStmt = $db->prepare(
            "SELECT facilityID FROM class_schedule 
             WHERE day = ? AND startTime < ? AND endTime > ?"
        );
        $occupiedStmt->bind_param("sss", $dayOfWeek, $endTime, $startTime);
        $occupiedStmt->execute();
        $occupiedResult = $occupiedStmt->get_result();
        $occupied = [];
        while ($row = $occupiedResult->fetch_assoc()) {
            $occupied[] = $row['facilityID'];
        }

        // Also check approved replacements on that specific date
        $approvedStmt = $db->prepare(
            "SELECT facilityID FROM replacement_request 
             WHERE newDate = ? AND status = 'Approved' AND facilityID IS NOT NULL
             AND SUBSTRING_INDEX(newTime, ' - ', 1) < ? 
             AND SUBSTRING_INDEX(newTime, ' - ', -1) > ?"
        );
        $approvedStmt->bind_param("sss", $date, $endTime, $startTime);
        $approvedStmt->execute();
        $approvedResult = $approvedStmt->get_result();
        while ($row = $approvedResult->fetch_assoc()) {
            if (!in_array($row['facilityID'], $occupied)) {
                $occupied[] = $row['facilityID'];
            }
        }

        // Mark availability
        foreach ($facilities as &$f) {
            $f['available'] = !in_array($f['facilityID'], $occupied);
        }

        echo json_encode(['success' => true, 'data' => $facilities, 'occupied' => $occupied]);
    }

    // --- Check staff schedule conflict for a specific date and time ---
    elseif ($action === 'checkStaffConflict') {
        $staffID = $_GET['staffID'] ?? $userID;
        $date = $_GET['date'] ?? '';
        $startTime = $_GET['startTime'] ?? '';
        $endTime = $_GET['endTime'] ?? '';

        if (empty($date) || empty($startTime) || empty($endTime)) {
            echo json_encode(['success' => false, 'message' => 'Date, start time and end time required']);
            exit;
        }

        $dayOfWeek = date('l', strtotime($date));
        $conflicts = [];

        // 1. Check regular weekly schedule conflicts (no exclusions - recurring classes exist every week)
        $stmt = $db->prepare(
            "SELECT cs.scheduleID, cs.courseID, cs.day, cs.startTime, cs.endTime, cs.classType,
                    c.courseName, f.facilityName
             FROM class_schedule cs
             JOIN course c ON cs.courseID = c.courseID
             JOIN facility f ON cs.facilityID = f.facilityID
             WHERE cs.staffID = ? AND cs.day = ? AND cs.startTime < ? AND cs.endTime > ?"
        );
        $stmt->bind_param("ssss", $staffID, $dayOfWeek, $endTime, $startTime);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $conflicts[] = [
                'type' => 'regular',
                'courseID' => $row['courseID'],
                'courseName' => $row['courseName'],
                'day' => $row['day'],
                'startTime' => $row['startTime'],
                'endTime' => $row['endTime'],
                'classType' => $row['classType'],
                'facilityName' => $row['facilityName']
            ];
        }

        // 2. Check approved replacement classes on that specific date
        $rrStmt = $db->prepare(
            "SELECT rr.newDate, rr.newTime, rr.replacementID, cs.courseID, c.courseName, cs.classType
             FROM replacement_request rr
             JOIN class_schedule cs ON rr.scheduleID = cs.scheduleID
             JOIN course c ON cs.courseID = c.courseID
             WHERE rr.staffID = ? AND rr.status = 'Approved' AND rr.newDate = ?
             AND SUBSTRING_INDEX(rr.newTime, ' - ', 1) < ? 
             AND SUBSTRING_INDEX(rr.newTime, ' - ', -1) > ?"
        );
        $rrStmt->bind_param("ssss", $staffID, $date, $endTime, $startTime);
        $rrStmt->execute();
        $rrResult = $rrStmt->get_result();
        while ($row = $rrResult->fetch_assoc()) {
            $conflicts[] = [
                'type' => 'replacement',
                'courseID' => $row['courseID'],
                'courseName' => $row['courseName'],
                'classType' => $row['classType'],
                'newDate' => $row['newDate'],
                'newTime' => $row['newTime']
            ];
        }

        echo json_encode([
            'success' => true,
            'hasConflict' => count($conflicts) > 0,
            'conflicts' => $conflicts
        ]);
    }
}

// ============================================================
// POST: Create or update a request
// ============================================================
elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    // --- Staff submits a new replacement request ---
    if ($action === 'create') {
        $role = $_SESSION['role'];
        if ($role !== 'staff') {
            echo json_encode(['success' => false, 'message' => 'Only staff can submit requests']);
            exit;
        }

        $scheduleID = $input['scheduleID'] ?? '';
        $originalDate = $input['originalDate'] ?? '';
        $newDate = $input['newDate'] ?? '';
        $newTime = $input['newTime'] ?? '';
        $facilityID = $input['facilityID'] ?? null;
        $reason = $input['reason'] ?? '';
        $staffID = $_SESSION['user_id'];

        if (empty($scheduleID) || empty($originalDate) || empty($newDate) || empty($newTime) || empty($reason)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }

        // Validate that originalDate falls on the correct day of week for this schedule
        $dayCheck = $db->prepare("SELECT day FROM class_schedule WHERE scheduleID = ?");
        $dayCheck->bind_param("i", $scheduleID);
        $dayCheck->execute();
        $schedDay = $dayCheck->get_result()->fetch_assoc();
        if ($schedDay && date('l', strtotime($originalDate)) !== $schedDay['day']) {
            echo json_encode(['success' => false, 'message' => 'Original date does not match the class day (' . $schedDay['day'] . ')']);
            exit;
        }

        // Check if staff owns this schedule
        $checkStmt = $db->prepare("SELECT staffID FROM class_schedule WHERE scheduleID = ?");
        $checkStmt->bind_param("i", $scheduleID);
        $checkStmt->execute();
        $scheduleOwner = $checkStmt->get_result()->fetch_assoc();
        
        if (!$scheduleOwner || $scheduleOwner['staffID'] !== $staffID) {
            echo json_encode(['success' => false, 'message' => 'You can only request replacement for your own classes']);
            exit;
        }

        // Check for duplicate pending request for same class on same original date
        $dupStmt = $db->prepare("SELECT replacementID FROM replacement_request WHERE scheduleID = ? AND staffID = ? AND originalDate = ? AND status = 'Pending'");
        $dupStmt->bind_param("iss", $scheduleID, $staffID, $originalDate);
        $dupStmt->execute();
        if ($dupStmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'A pending request already exists for this class on that date']);
            exit;
        }

        // Validate time format: end time must be after start time
        $timeParts = explode(' - ', $newTime);
        $newStartTime = trim($timeParts[0]);
        $newEndTime = isset($timeParts[1]) ? trim($timeParts[1]) : $newStartTime;
        if ($newEndTime <= $newStartTime) {
            echo json_encode(['success' => false, 'message' => 'End time must be after start time. If you mean PM, use 24-hour format (e.g. 13:00 for 1:00 PM).']);
            exit;
        }

        // CONFLICT CHECK: Staff's own schedule on the replacement date/time
        $newDayOfWeek = date('l', strtotime($newDate));

        // Check regular weekly schedule (no exclusion - recurring classes exist every week)
        $conflictStmt = $db->prepare(
            "SELECT cs.courseID, c.courseName, cs.startTime, cs.endTime 
             FROM class_schedule cs JOIN course c ON cs.courseID = c.courseID
             WHERE cs.staffID = ? AND cs.day = ? AND cs.startTime < ? AND cs.endTime > ?"
        );
        $conflictStmt->bind_param("ssss", $staffID, $newDayOfWeek, $newEndTime, $newStartTime);
        $conflictStmt->execute();
        $conflictResult = $conflictStmt->get_result()->fetch_assoc();
        if ($conflictResult) {
            echo json_encode(['success' => false, 'message' => "Schedule conflict: You already have {$conflictResult['courseName']} ({$conflictResult['courseID']}) from {$conflictResult['startTime']} to {$conflictResult['endTime']} on {$newDayOfWeek}"]);
            exit;
        }

        // Check approved replacements on the exact replacement date
        $rrConflictStmt = $db->prepare(
            "SELECT cs.courseID, c.courseName, rr.newTime
             FROM replacement_request rr
             JOIN class_schedule cs ON rr.scheduleID = cs.scheduleID
             JOIN course c ON cs.courseID = c.courseID
             WHERE rr.staffID = ? AND rr.status = 'Approved' AND rr.newDate = ?
             AND SUBSTRING_INDEX(rr.newTime, ' - ', 1) < ? AND SUBSTRING_INDEX(rr.newTime, ' - ', -1) > ?"
        );
        $rrConflictStmt->bind_param("ssss", $staffID, $newDate, $newEndTime, $newStartTime);
        $rrConflictStmt->execute();
        $rrConflict = $rrConflictStmt->get_result()->fetch_assoc();
        if ($rrConflict) {
            echo json_encode(['success' => false, 'message' => "Schedule conflict: You already have an approved replacement for {$rrConflict['courseName']} ({$rrConflict['courseID']}) at {$rrConflict['newTime']} on {$newDate}"]);
            exit;
        }

        $stmt = $db->prepare("INSERT INTO replacement_request (scheduleID, originalDate, staffID, newDate, newTime, facilityID, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("issssss", $scheduleID, $originalDate, $staffID, $newDate, $newTime, $facilityID, $reason);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Replacement request submitted successfully', 'id' => $db->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit request: ' . $db->error]);
        }
    }

    // --- Admin approves a request ---
    elseif ($action === 'approve') {
        if ($_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            exit;
        }

        $replacementID = $input['replacementID'] ?? '';
        $facilityID = $input['facilityID'] ?? null;
        $adminNotes = $input['adminNotes'] ?? '';

        if (empty($replacementID)) {
            echo json_encode(['success' => false, 'message' => 'Replacement ID required']);
            exit;
        }

        // Get request details for conflict check
        $reqStmt = $db->prepare("SELECT r.*, cs.staffID as schedStaffID FROM replacement_request r JOIN class_schedule cs ON r.scheduleID = cs.scheduleID WHERE r.replacementID = ?");
        $reqStmt->bind_param("i", $replacementID);
        $reqStmt->execute();
        $request = $reqStmt->get_result()->fetch_assoc();

        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Request not found']);
            exit;
        }

        // Update the facility if admin provides one
        $updateFacility = $facilityID ?? $request['facilityID'];

        // Parse replacement time
        $newDate = $request['newDate'];
        $dayOfWeek = date('l', strtotime($newDate));
        $timeParts = explode(' - ', $request['newTime']);
        $startTime = trim($timeParts[0] ?? '');
        $endTime = trim($timeParts[1] ?? '');
        $reqStaffID = $request['staffID'] ?? $request['schedStaffID'];
        $reqScheduleID = $request['scheduleID'];

        // CONFLICT CHECK 1: Staff's own schedule on replacement date/time
        $staffConflictStmt = $db->prepare(
            "SELECT cs.courseID, c.courseName, cs.startTime, cs.endTime FROM class_schedule cs 
             JOIN course c ON cs.courseID = c.courseID
             WHERE cs.staffID = ? AND cs.day = ? AND cs.startTime < ? AND cs.endTime > ?"
        );
        $staffConflictStmt->bind_param("ssss", $reqStaffID, $dayOfWeek, $endTime, $startTime);
        $staffConflictStmt->execute();
        $staffConflict = $staffConflictStmt->get_result()->fetch_assoc();
        if ($staffConflict) {
            echo json_encode(['success' => false, 'message' => "Staff schedule conflict: {$reqStaffID} already has {$staffConflict['courseName']} ({$staffConflict['courseID']}) from {$staffConflict['startTime']} to {$staffConflict['endTime']} on {$dayOfWeek}"]);
            exit;
        }

        // CONFLICT CHECK 1b: Staff's approved replacements on that exact date
        $staffRRStmt = $db->prepare(
            "SELECT cs.courseID, c.courseName, rr.newTime FROM replacement_request rr
             JOIN class_schedule cs ON rr.scheduleID = cs.scheduleID
             JOIN course c ON cs.courseID = c.courseID
             WHERE rr.staffID = ? AND rr.status = 'Approved' AND rr.newDate = ? AND rr.replacementID != ?
             AND SUBSTRING_INDEX(rr.newTime, ' - ', 1) < ? AND SUBSTRING_INDEX(rr.newTime, ' - ', -1) > ?"
        );
        $staffRRStmt->bind_param("ssiss", $reqStaffID, $newDate, $replacementID, $endTime, $startTime);
        $staffRRStmt->execute();
        $staffRRConflict = $staffRRStmt->get_result()->fetch_assoc();
        if ($staffRRConflict) {
            echo json_encode(['success' => false, 'message' => "Staff schedule conflict: {$reqStaffID} already has an approved replacement for {$staffRRConflict['courseName']} at {$staffRRConflict['newTime']} on {$newDate}"]);
            exit;
        }

        // CONFLICT CHECK 2: Facility conflict if a facility is assigned
        if ($updateFacility) {
            // Check regular schedule conflicts
            $conflictStmt = $db->prepare(
                "SELECT cs.scheduleID, c.courseName FROM class_schedule cs 
                 JOIN course c ON cs.courseID = c.courseID
                 WHERE cs.facilityID = ? AND cs.day = ? AND cs.startTime < ? AND cs.endTime > ?"
            );
            $conflictStmt->bind_param("ssss", $updateFacility, $dayOfWeek, $endTime, $startTime);
            $conflictStmt->execute();
            $conflict = $conflictStmt->get_result()->fetch_assoc();
            
            if ($conflict) {
                echo json_encode(['success' => false, 'message' => "Facility conflict: $updateFacility is occupied by {$conflict['courseName']} at that time"]);
                exit;
            }
        }

        $stmt = $db->prepare("UPDATE replacement_request SET status = 'Approved', facilityID = ?, adminNotes = ?, reviewed_at = NOW() WHERE replacementID = ? AND status = 'Pending'");
        $stmt->bind_param("ssi", $updateFacility, $adminNotes, $replacementID);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Request approved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to approve request (may already be processed)']);
        }
    }

    // --- Admin rejects a request ---
    elseif ($action === 'reject') {
        if ($_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            exit;
        }

        $replacementID = $input['replacementID'] ?? '';
        $adminNotes = $input['adminNotes'] ?? '';

        if (empty($replacementID)) {
            echo json_encode(['success' => false, 'message' => 'Replacement ID required']);
            exit;
        }

        $stmt = $db->prepare("UPDATE replacement_request SET status = 'Rejected', adminNotes = ?, reviewed_at = NOW() WHERE replacementID = ? AND status = 'Pending'");
        $stmt->bind_param("si", $adminNotes, $replacementID);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Request rejected']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reject request (may already be processed)']);
        }
    }

    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$db->close();
?>