<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

$conn = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'getSchedule':
            $studentID = $_GET['studentID'] ?? '';
            $weekNumber = isset($_GET['weekNumber']) ? intval($_GET['weekNumber']) : null;
            $weekStartDate = $_GET['weekStartDate'] ?? '';
            $weekEndDate = $_GET['weekEndDate'] ?? '';
            
            if (empty($studentID)) {
                echo json_encode(['success' => false, 'message' => 'Student ID required']);
                exit;
            }
            
            // Get student info including current year and semester
            $stmt = $conn->prepare("SELECT tutGroup, programID, currentYear, currentSemester FROM student WHERE studentID = ? OR userID = ?");
            $stmt->bind_param("ss", $studentID, $studentID);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();
            
            if (!$student) {
                echo json_encode(['success' => false, 'message' => 'Student not found']);
                exit;
            }
            
            $currentYear = $student['currentYear'] ?? 1;
            $currentSemester = $student['currentSemester'] ?? 1;
            
            // Build query - fetch only classes for student's current year and semester
            $query = "SELECT cs.*, c.courseName, c.creditHours, f.facilityName, s.staffName
                      FROM class_schedule cs
                      JOIN course c ON cs.courseID = c.courseID
                      JOIN facility f ON cs.facilityID = f.facilityID
                      JOIN staff s ON cs.staffID = s.staffID
                      JOIN program_course pc ON cs.courseID = pc.courseID 
                          AND cs.programID = pc.programID
                      WHERE cs.tutGroup = ? 
                      AND cs.programID = ?
                      AND pc.year = ?
                      AND pc.semester = ?
                      ORDER BY FIELD(cs.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), cs.startTime";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssii", $student['tutGroup'], $student['programID'], $currentYear, $currentSemester);
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
                    "SELECT rr.scheduleID FROM replacement_request rr
                     JOIN class_schedule cs ON rr.scheduleID = cs.scheduleID
                     WHERE rr.status = 'Approved' 
                     AND rr.originalDate BETWEEN ? AND ?
                     AND cs.tutGroup = ? AND cs.programID = ?"
                );
                $hideStmt->bind_param("ssss", $weekStartDate, $weekEndDate, $student['tutGroup'], $student['programID']);
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

                // 2. Add replacement classes where newDate falls in this week (for student's group/program)
                $addStmt = $conn->prepare(
                    "SELECT rr.newDate, rr.newTime, rr.facilityID as rrFacilityID,
                            cs.scheduleID, cs.courseID, cs.classType, cs.tutGroup, cs.programID, cs.staffID,
                            c.courseName, c.creditHours,
                            s.staffName,
                            COALESCE(f_new.facilityName, f_old.facilityName) as facilityName,
                            COALESCE(rr.facilityID, cs.facilityID) as facilityID
                     FROM replacement_request rr
                     JOIN class_schedule cs ON rr.scheduleID = cs.scheduleID
                     JOIN course c ON cs.courseID = c.courseID
                     JOIN staff s ON cs.staffID = s.staffID
                     LEFT JOIN facility f_new ON rr.facilityID = f_new.facilityID
                     LEFT JOIN facility f_old ON cs.facilityID = f_old.facilityID
                     WHERE rr.status = 'Approved'
                     AND rr.newDate BETWEEN ? AND ?
                     AND cs.tutGroup = ? AND cs.programID = ?"
                );
                $addStmt->bind_param("ssss", $weekStartDate, $weekEndDate, $student['tutGroup'], $student['programID']);
                $addStmt->execute();
                $addResult = $addStmt->get_result();

                while ($row = $addResult->fetch_assoc()) {
                    $newDay = date('l', strtotime($row['newDate']));
                    $timeParts = explode(' - ', $row['newTime']);
                    $startTime = trim($timeParts[0]);
                    $endTime = isset($timeParts[1]) ? trim($timeParts[1]) : $startTime;
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
                        'staffName' => $row['staffName'],
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
