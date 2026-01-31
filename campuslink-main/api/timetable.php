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
