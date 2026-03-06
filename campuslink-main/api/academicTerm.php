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

// Get active term for staff — pick the longest currently-running active term
// so the week selector always covers all teaching weeks (e.g. 14-week term
// even when other active terms are only 7 weeks).
function getActiveTermForStaff($conn) {
    // Among all active terms that have already started, pick the one with
    // the furthest end date (i.e. the longest running term).
    $stmt = $conn->prepare(
        "SELECT * FROM academic_term
         WHERE status = 'Active' AND startDate <= CURDATE()
         ORDER BY endDate DESC, weeksTotal DESC
         LIMIT 1"
    );
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Fallback: any active term
        $stmt2 = $conn->prepare("SELECT * FROM academic_term WHERE status = 'Active' ORDER BY weeksTotal DESC LIMIT 1");
        $stmt2->execute();
        $result = $stmt2->get_result();
        if ($result->num_rows === 0) {
            return null;
        }
    }

    $term = $result->fetch_assoc();

    // Use the actual weeksTotal of the longest term so the week selector
    // spans the full teaching period (e.g. 14 weeks).
    $totalWeeks = (int)($term['weeksTotal'] ?? 14);

    $weeks = generateWeekRanges($term['startDate'], $term['endDate'], $totalWeeks);

    $term['totalCreditHours'] = null; // Not applicable for staff
    $term['isShortSemester'] = ($totalWeeks <= 7);
    $term['calculatedWeeks'] = $totalWeeks;
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

        case 'getRegDatesFromPrevTerm':
            // Calculate registration window for an upcoming term:
            // reg opens on Week 1 of the PREVIOUS term, closes end of Week 3 (day +20)
            $programID = $_GET['programID'] ?? '';
            $year      = (int)($_GET['year'] ?? 0);
            $semester  = (int)($_GET['semester'] ?? 0);
            if (empty($programID) || $year < 1 || $semester < 1) {
                echo json_encode(['success' => false, 'message' => 'programID, year and semester required']);
                exit;
            }
            // Year 1 Semester 1 — no registration required (students are directly enrolled)
            if ($year === 1 && $semester === 1) {
                echo json_encode([
                    'success'         => true,
                    'noRegistration'  => true,
                    'message'         => 'Year 1 Semester 1 does not require course registration. Students are directly enrolled.'
                ]);
                exit;
            }
            // Determine the previous term's year/semester
            if ($semester > 1) {
                $prevYear = $year;
                $prevSem  = $semester - 1;
            } else {
                $prevYear = $year - 1;
                $prevSem  = 3;
            }
            if ($prevYear < 1) {
                echo json_encode(['success' => false, 'message' => 'No previous term exists (this is the first semester)']);
                exit;
            }
            $stmt = $conn->prepare(
                "SELECT startDate, endDate FROM academic_term
                 WHERE programID = ? AND year = ? AND semester = ?
                 ORDER BY termID DESC LIMIT 1"
            );
            $stmt->bind_param('sii', $programID, $prevYear, $prevSem);
            $stmt->execute();
            $prevTerm = $stmt->get_result()->fetch_assoc();
            if (!$prevTerm) {
                echo json_encode(['success' => false, 'message' => "Previous term ({$programID} Y{$prevYear}S{$prevSem}) not found in database"]);
                exit;
            }
            $regStart = new DateTime($prevTerm['startDate']);
            $regEnd   = clone $regStart;
            $regEnd->modify('+20 days'); // end of Week 3
            // Min start date for the new term is the day AFTER the previous term ends
            $minStart = new DateTime($prevTerm['endDate']);
            $minStart->modify('+1 day');
            echo json_encode([
                'success'         => true,
                'regStartDate'    => $regStart->format('Y-m-d'),
                'regEndDate'      => $regEnd->format('Y-m-d'),
                'basedOn'         => "{$programID} Year {$prevYear} Semester {$prevSem}",
                'prevTermEndDate' => $prevTerm['endDate'],
                'minStartDate'    => $minStart->format('Y-m-d')
            ]);
            break;

        case 'getCreditHours':
            $programID = $_GET['programID'] ?? '';
            $year      = (int)($_GET['year'] ?? 0);
            $semester  = (int)($_GET['semester'] ?? 0);
            if (empty($programID) || $year < 1 || $semester < 1) {
                echo json_encode(['success' => false, 'message' => 'programID, year and semester required']);
                exit;
            }
            $stmt = $conn->prepare(
                "SELECT COALESCE(SUM(c.creditHours), 0) AS totalCredits
                 FROM program_course pc
                 JOIN course c ON pc.courseID = c.courseID
                 WHERE pc.programID = ? AND pc.year = ? AND pc.semester = ?"
            );
            $stmt->bind_param('sii', $programID, $year, $semester);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $credits = (int)$row['totalCredits'];
            $weeks   = ($credits <= 10) ? 7 : 14;
            $type    = ($weeks === 7) ? 'Short Semester' : 'Long Semester';
            echo json_encode(['success' => true, 'totalCredits' => $credits, 'weeksTotal' => $weeks, 'semesterType' => $type]);
            break;

        case 'getAllTerms':
            $r = $conn->query("SELECT t.*, p.programName FROM academic_term t JOIN program p ON t.programID = p.programID ORDER BY t.programID, t.year, t.semester");
            $terms = [];
            while ($row = $r->fetch_assoc()) $terms[] = $row;
            echo json_encode(['success' => true, 'data' => $terms]);
            break;

        case 'getPrograms':
            $r = $conn->query("SELECT programID, programName, duration FROM program ORDER BY programID");
            $programs = [];
            while ($row = $r->fetch_assoc()) $programs[] = $row;
            echo json_encode(['success' => true, 'data' => $programs]);
            break;

        case 'getTerm':
            $termID = $_GET['termID'] ?? '';
            if (empty($termID)) { echo json_encode(['success' => false, 'message' => 'termID required']); exit; }
            $stmt = $conn->prepare("SELECT t.*, p.programName FROM academic_term t JOIN program p ON t.programID = p.programID WHERE t.termID = ?");
            $stmt->bind_param("i", $termID);
            $stmt->execute();
            $term = $stmt->get_result()->fetch_assoc();
            if ($term) {
                echo json_encode(['success' => true, 'data' => $term]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Term not found']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'createTerm':
            $programID = $data['programID'] ?? '';
            $year = (int)($data['year'] ?? 0);
            $semester = (int)($data['semester'] ?? 0);
            $academicYear = $data['academicYear'] ?? '';
            $startDate = $data['startDate'] ?? '';
            $endDate = $data['endDate'] ?? '';
            $weeksTotal = (int)($data['weeksTotal'] ?? 14);
            $status = $data['status'] ?? 'Upcoming';
            $regStartDate = !empty($data['regStartDate']) ? $data['regStartDate'] : null;
            $regEndDate = !empty($data['regEndDate']) ? $data['regEndDate'] : null;

            if (empty($programID) || $year < 1 || $semester < 1 || empty($academicYear) || empty($startDate) || empty($endDate)) {
                echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
                exit;
            }

            // Check for duplicate
            $chk = $conn->prepare("SELECT termID FROM academic_term WHERE programID = ? AND year = ? AND semester = ? AND academicYear = ?");
            $chk->bind_param("siis", $programID, $year, $semester, $academicYear);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'A term already exists for this programme/year/semester/academic year combination']);
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO academic_term (programID, year, semester, academicYear, startDate, endDate, regStartDate, regEndDate, weeksTotal, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siisssssis", $programID, $year, $semester, $academicYear, $startDate, $endDate, $regStartDate, $regEndDate, $weeksTotal, $status);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Academic term created successfully', 'termID' => $conn->insert_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create term: ' . $conn->error]);
            }
            break;

        case 'updateTerm':
            $termID = (int)($data['termID'] ?? 0);
            $programID = $data['programID'] ?? '';
            $year = (int)($data['year'] ?? 0);
            $semester = (int)($data['semester'] ?? 0);
            $academicYear = $data['academicYear'] ?? '';
            $startDate = $data['startDate'] ?? '';
            $endDate = $data['endDate'] ?? '';
            $weeksTotal = (int)($data['weeksTotal'] ?? 14);
            $status = $data['status'] ?? 'Upcoming';
            $regStartDate = !empty($data['regStartDate']) ? $data['regStartDate'] : null;
            $regEndDate = !empty($data['regEndDate']) ? $data['regEndDate'] : null;

            if ($termID < 1 || empty($programID) || $year < 1 || $semester < 1 || empty($academicYear) || empty($startDate) || empty($endDate)) {
                echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
                exit;
            }

            // Check for duplicate (excluding current)
            $chk = $conn->prepare("SELECT termID FROM academic_term WHERE programID = ? AND year = ? AND semester = ? AND academicYear = ? AND termID != ?");
            $chk->bind_param("siisi", $programID, $year, $semester, $academicYear, $termID);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'A duplicate term already exists']);
                exit;
            }

            $stmt = $conn->prepare("UPDATE academic_term SET programID = ?, year = ?, semester = ?, academicYear = ?, startDate = ?, endDate = ?, regStartDate = ?, regEndDate = ?, weeksTotal = ?, status = ? WHERE termID = ?");
            $stmt->bind_param("siisssssisi", $programID, $year, $semester, $academicYear, $startDate, $endDate, $regStartDate, $regEndDate, $weeksTotal, $status, $termID);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Academic term updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update term: ' . $conn->error]);
            }
            break;

        case 'deleteTerm':
            $termID = (int)($data['termID'] ?? 0);
            if ($termID < 1) {
                echo json_encode(['success' => false, 'message' => 'termID required']);
                exit;
            }

            // Check if term has any registrations
            $chk = $conn->prepare("SELECT COUNT(*) as cnt FROM course_registration cr JOIN course_offering co ON cr.offeringID = co.offeringID WHERE co.termID = ?");
            $chk->bind_param("i", $termID);
            $chk->execute();
            $cnt = $chk->get_result()->fetch_assoc()['cnt'];
            if ($cnt > 0) {
                echo json_encode(['success' => false, 'message' => "Cannot delete: $cnt student registration(s) exist for this term. Change the term status instead."]);
                exit;
            }

            // Delete offerings first, then term
            $conn->prepare("DELETE FROM course_offering WHERE termID = ?")->bind_param("i", $termID);
            $conn->query("DELETE FROM course_offering WHERE termID = $termID");
            
            $stmt = $conn->prepare("DELETE FROM academic_term WHERE termID = ?");
            $stmt->bind_param("i", $termID);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Academic term deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete term: ' . $conn->error]);
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
