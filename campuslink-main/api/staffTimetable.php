<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

$conn = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'getSchedule':
            $staffID = $_GET['staffID'] ?? '';
            $weekNumber = isset($_GET['weekNumber']) ? intval($_GET['weekNumber']) : null;
            $weekStartDate = $_GET['weekStartDate'] ?? '';
            $weekEndDate = $_GET['weekEndDate'] ?? '';
            
            if (empty($staffID)) {
                echo json_encode(['success' => false, 'message' => 'Staff ID required']);
                exit;
            }
            
            // Get staff's teaching schedule
            // Filter by semesterWeeks - only show classes that are scheduled for this week
            $query = "SELECT cs.*, c.courseName, c.creditHours, f.facilityName
                      FROM class_schedule cs
                      JOIN course c ON cs.courseID = c.courseID
                      JOIN facility f ON cs.facilityID = f.facilityID
                      WHERE cs.staffID = ?";
            
            // If weekNumber is provided, filter by semesterWeeks
            // E.g., Week 8 should not show classes with semesterWeeks = 7
            if ($weekNumber !== null) {
                $query .= " AND (cs.semesterWeeks IS NULL OR cs.semesterWeeks >= ?)";
            }
            
            $query .= " ORDER BY FIELD(cs.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), cs.startTime";
            
            $stmt = $conn->prepare($query);
            
            if ($weekNumber !== null) {
                $stmt->bind_param("si", $staffID, $weekNumber);
            } else {
                $stmt->bind_param("s", $staffID);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $schedule = [];
            while ($row = $result->fetch_assoc()) {
                $schedule[] = $row;
            }
            
            // Apply approved replacements if week dates are provided
            if (!empty($weekStartDate) && !empty($weekEndDate)) {
                // 1. Hide classes that have approved replacements with originalDate in this week
                $hideStmt = $conn->prepare(
                    "SELECT scheduleID FROM replacement_request 
                     WHERE staffID = ? AND status = 'Approved' 
                     AND originalDate BETWEEN ? AND ?"
                );
                $hideStmt->bind_param("sss", $staffID, $weekStartDate, $weekEndDate);
                $hideStmt->execute();
                $hideResult = $hideStmt->get_result();
                $hideIDs = [];
                while ($row = $hideResult->fetch_assoc()) {
                    $hideIDs[] = $row['scheduleID'];
                }
                
                if (!empty($hideIDs)) {
                    $schedule = array_values(array_filter($schedule, function($s) use ($hideIDs) {
                        return !in_array($s['scheduleID'], $hideIDs);
                    }));
                }
                
                // 2. Add replacement classes where newDate falls in this week
                $addStmt = $conn->prepare(
                    "SELECT rr.newDate, rr.newTime, rr.facilityID as rrFacilityID,
                            cs.scheduleID, cs.courseID, cs.classType, cs.tutGroup, cs.programID, cs.staffID,
                            c.courseName, c.creditHours,
                            COALESCE(f_new.facilityName, f_old.facilityName) as facilityName,
                            COALESCE(rr.facilityID, cs.facilityID) as facilityID
                     FROM replacement_request rr
                     JOIN class_schedule cs ON rr.scheduleID = cs.scheduleID
                     JOIN course c ON cs.courseID = c.courseID
                     LEFT JOIN facility f_new ON rr.facilityID = f_new.facilityID
                     LEFT JOIN facility f_old ON cs.facilityID = f_old.facilityID
                     WHERE rr.staffID = ? AND rr.status = 'Approved'
                     AND rr.newDate BETWEEN ? AND ?"
                );
                $addStmt->bind_param("sss", $staffID, $weekStartDate, $weekEndDate);
                $addStmt->execute();
                $addResult = $addStmt->get_result();
                
                while ($row = $addResult->fetch_assoc()) {
                    $newDay = date('l', strtotime($row['newDate']));
                    $timeParts = explode(' - ', $row['newTime']);
                    $startTime = trim($timeParts[0]);
                    $endTime = isset($timeParts[1]) ? trim($timeParts[1]) : $startTime;
                    // Ensure HH:MM:SS format
                    if (strlen($startTime) === 5) $startTime .= ':00';
                    if (strlen($endTime) === 5) $endTime .= ':00';
                    
                    $schedule[] = [
                        'scheduleID' => $row['scheduleID'],
                        'courseID' => $row['courseID'],
                        'staffID' => $row['staffID'],
                        'facilityID' => $row['facilityID'],
                        'day' => $newDay,
                        'startTime' => $startTime,
                        'endTime' => $endTime,
                        'classType' => $row['classType'],
                        'tutGroup' => $row['tutGroup'],
                        'programID' => $row['programID'],
                        'courseName' => $row['courseName'],
                        'creditHours' => $row['creditHours'],
                        'facilityName' => $row['facilityName'],
                        'isReplacement' => true
                    ];
                }
            }
            
            echo json_encode([
                'success' => true, 
                'data' => $schedule,
                'weekNumber' => $weekNumber,
                'note' => $weekNumber ? "Showing schedule for Week $weekNumber" : "Showing all scheduled classes"
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
