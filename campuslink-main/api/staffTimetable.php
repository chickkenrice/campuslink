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
