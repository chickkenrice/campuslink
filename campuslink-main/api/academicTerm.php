<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

$conn = get_db_connection();

// Check if input is studentID or userID and return studentID
function resolveStudentID($conn, $input) {
    // If it starts with '23WP' or similar pattern, it's already a studentID
    if (preg_match('/^\d{2}WP\d+$/', $input)) {
        return $input;
    }
    
    // Otherwise, treat it as userID and look up studentID
    $stmt = $conn->prepare("SELECT studentID FROM student WHERE userID = ?");
    $stmt->bind_param("s", $input);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['studentID'] : null;
}

// Get student's total credit hours for current term based on programme structure
function getStudentCreditHours($conn, $studentID, $termID) {
    // Get student's program, current year, and semester
    $stmt = $conn->prepare("SELECT programID, currentYear, currentSemester FROM student WHERE studentID = ?");
    $stmt->bind_param("s", $studentID);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    
    if (!$student) {
        return 0;
    }
    
    // Use student's current year and semester
    $currentYear = $student['currentYear'] ?? 1;
    $currentSemester = $student['currentSemester'] ?? 1;
    
    // Get credit hours from program_course based on program, year, and semester
    $query = "SELECT SUM(c.creditHours) as totalCredits
              FROM program_course pc
              INNER JOIN course c ON pc.courseID = c.courseID
              WHERE pc.programID = ? 
              AND pc.year = ?
              AND pc.semester = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $student['programID'], $currentYear, $currentSemester);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['totalCredits'] ?? 0;
}

// Generate week ranges based on term dates and total weeks
function generateWeekRanges($startDate, $endDate, $totalWeeks) {
    $weeks = [];
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    
    for ($i = 1; $i <= $totalWeeks; $i++) {
        $weekStart = clone $start;
        $weekStart->modify('+' . ($i - 1) . ' weeks');
        
        $weekEnd = clone $weekStart;
        $weekEnd->modify('+6 days');
        
        // Don't exceed term end date
        if ($weekEnd > $end) {
            $weekEnd = $end;
        }
        
        $weeks[] = [
            'weekNumber' => $i,
            'startDate' => $weekStart->format('Y-m-d'),
            'endDate' => $weekEnd->format('Y-m-d'),
            'label' => "Week $i : " . $weekStart->format('Y-m-d') . " – " . $weekEnd->format('Y-m-d')
        ];
        
        // Stop if we've reached the end date
        if ($weekEnd >= $end) {
            break;
        }
    }
    
    return $weeks;
}

// Get current active term with weeks for a specific student
function getActiveTermWithWeeks($conn, $studentID) {
    // Get student's program, current year, and semester
    $stmt = $conn->prepare("SELECT programID, currentYear, currentSemester FROM student WHERE studentID = ?");
    $stmt->bind_param("s", $studentID);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    
    if (!$student) {
        return null;
    }
    
    // Get the active term for this specific program/year/semester
    $query = "SELECT * FROM academic_term 
              WHERE status = 'Active' 
              AND programID = ? 
              AND year = ? 
              AND semester = ? 
              LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $student['programID'], $student['currentYear'], $student['currentSemester']);
    $stmt->execute();
    $termResult = $stmt->get_result();
    
    if ($termResult->num_rows === 0) {
        return null;
    }
    
    $term = $termResult->fetch_assoc();
    
    // Calculate student's credit hours for this term
    $creditHours = getStudentCreditHours($conn, $studentID, $term['termID']);
    
    // Use the weeksTotal from the term (already defined per program/year/semester)
    $weeksToGenerate = $term['weeksTotal'];
    $isShortSem = $weeksToGenerate == 7;
    
    // Generate weeks
    $weeks = generateWeekRanges($term['startDate'], $term['endDate'], $weeksToGenerate);
    
    $term['totalCreditHours'] = $creditHours;
    $term['isShortSemester'] = $isShortSem;
    $term['calculatedWeeks'] = $weeksToGenerate;
    $term['weeks'] = $weeks;
    
    return $term;
}

// Get active term for staff (always show 14 weeks)
function getActiveTermForStaff($conn) {
    $stmt = $conn->prepare("SELECT * FROM academic_term WHERE status = 'Active' LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    $term = $result->fetch_assoc();
    
    // Staff always see 14 weeks (full term view)
    $weeks = generateWeekRanges($term['startDate'], $term['endDate'], 14);
    
    $term['totalCreditHours'] = null; // Not applicable for staff
    $term['isShortSemester'] = false;
    $term['calculatedWeeks'] = 14;
    $term['weeks'] = $weeks;
    
    return $term;
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'getActiveTermWithWeeks':
            $input = $_GET['studentID'] ?? ''; // Could be studentID or userID
            if (empty($input)) {
                echo json_encode(['success' => false, 'message' => 'ID required']);
                exit;
            }
            
            // Resolve to actual studentID (handles both studentID and userID)
            $studentID = resolveStudentID($conn, $input);
            if (!$studentID) {
                echo json_encode(['success' => false, 'message' => 'Student not found for ID: ' . $input]);
                exit;
            }
            
            $termData = getActiveTermWithWeeks($conn, $studentID);
            
            if ($termData) {
                echo json_encode(['success' => true, 'data' => $termData]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No active term found']);
            }
            break;
            
        case 'getActiveTermForStaff':
            // For staff - no student ID needed, just return active term with all weeks
            $termData = getActiveTermForStaff($conn);
            
            if ($termData) {
                echo json_encode(['success' => true, 'data' => $termData]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No active term found']);
            }
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
