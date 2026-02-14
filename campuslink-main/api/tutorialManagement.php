<?php
/**
 * Tutorial Management API
 * Handles student management for tutorial/practical classes
 */

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json; charset=utf-8');

session_start();
require_once '../includes/config.php';
require_once '../includes/activity-logger.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = get_db_connection();
$staffUserID = $_SESSION['user_id'];

// Get staff's actual staffID
$staffID = $staffUserID;
if (strpos($staffUserID, 'U') === 0) {
    $stmtUser = $db->prepare("SELECT staffID FROM staff WHERE userID = ?");
    $stmtUser->bind_param("s", $staffUserID);
    $stmtUser->execute();
    $resUser = $stmtUser->get_result()->fetch_assoc();
    if ($resUser) {
        $staffID = $resUser['staffID'];
    }
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'getStudents':
            getStudents($db, $staffID);
            break;
            
        case 'getStudentProfile':
            getStudentProfile($db);
            break;
            
        case 'generateReport':
            generateReport($db, $staffID);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'removeStudent':
            removeStudent($db, $staffID, $input);
            break;
            
        case 'reinstateStudent':
            reinstateStudent($db, $staffID, $input);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

/**
 * Get students for a specific tutorial/practical group
 */
function getStudents($db, $staffID) {
    $scheduleID = $_GET['scheduleID'] ?? '';
    $programID = $_GET['programID'] ?? '';
    $tutGroup = $_GET['tutGroup'] ?? '';
    $status = $_GET['status'] ?? 'active';
    
    if (empty($programID) || empty($tutGroup)) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        return;
    }
    
    // Verify this staff teaches this group (tutorial/practical only) and get courseID
    // Use scheduleID if provided for more specific filtering
    if (!empty($scheduleID) && $scheduleID !== 'undefined') {
        $verifyStmt = $db->prepare("SELECT scheduleID, courseID FROM class_schedule 
                                    WHERE staffID = ? AND scheduleID = ?
                                    AND classType IN ('Tutorial', 'Practical', 'Lab')
                                    LIMIT 1");
        $verifyStmt->bind_param("si", $staffID, $scheduleID);
    } else {
        $verifyStmt = $db->prepare("SELECT scheduleID, courseID FROM class_schedule 
                                    WHERE staffID = ? AND programID = ? AND tutGroup = ? 
                                    AND classType IN ('Tutorial', 'Practical', 'Lab')
                                    LIMIT 1");
        $verifyStmt->bind_param("sss", $staffID, $programID, $tutGroup);
    }
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result()->fetch_assoc();
    if (!$verifyResult) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access to this group']);
        return;
    }
    $courseID = $verifyResult['courseID'];
    
    // Get the year and semester for this course from programme structure
    $courseYearSemStmt = $db->prepare("SELECT year, semester FROM program_course WHERE programID = ? AND courseID = ? LIMIT 1");
    $courseYearSemStmt->bind_param("ss", $programID, $courseID);
    $courseYearSemStmt->execute();
    $courseYearSem = $courseYearSemStmt->get_result()->fetch_assoc();
    
    // Debug logging (remove after testing)
    error_log("TutorialAPI Debug - CourseID: $courseID, Program: $programID, Year/Sem: " . json_encode($courseYearSem));
    
    // Get active student count (filtered by year/semester)
    $activeCountSql = "SELECT COUNT(*) as cnt FROM student s
                       WHERE s.programID = ? AND s.tutGroup = ?";
    if ($courseYearSem) {
        $activeCountSql .= " AND s.currentYear = ? AND s.currentSemester = ?";
    }
    $activeCountSql .= " AND NOT EXISTS (
                           SELECT 1 FROM class_group_removals r 
                           WHERE r.studentID = s.studentID 
                           AND r.programID = s.programID 
                           AND r.tutGroup = s.tutGroup 
                           AND r.status = 'Removed'
                       )";
    $stmtActiveCount = $db->prepare($activeCountSql);
    if ($courseYearSem) {
        $stmtActiveCount->bind_param("ssii", $programID, $tutGroup, $courseYearSem['year'], $courseYearSem['semester']);
    } else {
        $stmtActiveCount->bind_param("ss", $programID, $tutGroup);
    }
    $stmtActiveCount->execute();
    $activeCount = $stmtActiveCount->get_result()->fetch_assoc()['cnt'];
    
    // Get removed student count
    $removedCountSql = "SELECT COUNT(*) as cnt FROM class_group_removals 
                        WHERE programID = ? AND tutGroup = ? AND status = 'Removed'";
    $stmtRemovedCount = $db->prepare($removedCountSql);
    $stmtRemovedCount->bind_param("ss", $programID, $tutGroup);
    $stmtRemovedCount->execute();
    $removedCount = $stmtRemovedCount->get_result()->fetch_assoc()['cnt'];
    
    if ($status === 'active') {
        // Get active students (not removed) - filtered by year/semester
        $sql = "SELECT s.studentID, s.studentName, s.email, s.contactNo, s.programID, s.tutGroup, 'Active' as status
                FROM student s
                WHERE s.programID = ? AND s.tutGroup = ?";
        if ($courseYearSem) {
            $sql .= " AND s.currentYear = ? AND s.currentSemester = ?";
        }
        $sql .= " AND NOT EXISTS (
                    SELECT 1 FROM class_group_removals r 
                    WHERE r.studentID = s.studentID 
                    AND r.programID = s.programID 
                    AND r.tutGroup = s.tutGroup 
                    AND r.status = 'Removed'
                )
                ORDER BY s.studentName ASC";
        $stmt = $db->prepare($sql);
        if ($courseYearSem) {
            $stmt->bind_param("ssii", $programID, $tutGroup, $courseYearSem['year'], $courseYearSem['semester']);
        } else {
            $stmt->bind_param("ss", $programID, $tutGroup);
        }
    } else {
        // Get removed students
        $sql = "SELECT s.studentID, s.studentName, s.email, s.contactNo, s.programID, s.tutGroup, 
                       'Removed' as status, r.removalID, r.reason, r.removedAt
                FROM class_group_removals r
                JOIN student s ON r.studentID = s.studentID
                WHERE r.programID = ? AND r.tutGroup = ? AND r.status = 'Removed'
                ORDER BY r.removedAt DESC";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ss", $programID, $tutGroup);
    }
    
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'students' => $students,
        'activeCount' => $activeCount,
        'removedCount' => $removedCount
    ]);
}

/**
 * Get detailed student profile
 */
function getStudentProfile($db) {
    $studentID = $_GET['studentID'] ?? '';
    
    if (empty($studentID)) {
        echo json_encode(['success' => false, 'message' => 'Missing student ID']);
        return;
    }
    
    $sql = "SELECT s.studentID, s.studentName, s.email, s.contactNo, s.programID, s.tutGroup, s.studentImage,
                   sd.dob, sd.gender, sd.homeAddress, sd.corrAddress, sd.parentName, sd.parentContact
            FROM student s
            LEFT JOIN student_details sd ON s.studentID = sd.studentID
            WHERE s.studentID = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $studentID);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    
    if ($student) {
        echo json_encode(['success' => true, 'student' => $student]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
    }
}

/**
 * Remove student from a tutorial/practical group
 */
function removeStudent($db, $staffID, $input) {
    $studentID = $input['studentID'] ?? '';
    $programID = $input['programID'] ?? '';
    $tutGroup = $input['tutGroup'] ?? '';
    $reason = $input['reason'] ?? '';
    
    if (empty($studentID) || empty($programID) || empty($tutGroup) || empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    // Verify this staff teaches this group
    $verifyStmt = $db->prepare("SELECT scheduleID FROM class_schedule 
                                WHERE staffID = ? AND programID = ? AND tutGroup = ? 
                                AND classType IN ('Tutorial', 'Practical', 'Lab')
                                LIMIT 1");
    $verifyStmt->bind_param("sss", $staffID, $programID, $tutGroup);
    $verifyStmt->execute();
    if ($verifyStmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        return;
    }
    
    // Check if student is already removed
    $checkStmt = $db->prepare("SELECT removalID FROM class_group_removals 
                               WHERE studentID = ? AND programID = ? AND tutGroup = ? AND status = 'Removed'");
    $checkStmt->bind_param("sss", $studentID, $programID, $tutGroup);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Student is already removed']);
        return;
    }
    
    // Insert removal record
    $insertStmt = $db->prepare("INSERT INTO class_group_removals (studentID, programID, tutGroup, removedBy, reason) VALUES (?, ?, ?, ?, ?)");
    $insertStmt->bind_param("sssss", $studentID, $programID, $tutGroup, $staffID, $reason);
    
    if ($insertStmt->execute()) {
        // Log the activity
        $studentStmt = $db->prepare("SELECT studentName FROM student WHERE studentID = ?");
        $studentStmt->bind_param("s", $studentID);
        $studentStmt->execute();
        $studentResult = $studentStmt->get_result()->fetch_assoc();
        $studentName = $studentResult['studentName'] ?? $studentID;
        
        logActivity(
            $db,
            $_SESSION['user_id'],
            'STUDENT_REMOVAL',
            "Removed student {$studentName} ($studentID) from {$programID} {$tutGroup}. Reason: {$reason}"
        );
        
        echo json_encode(['success' => true, 'message' => 'Student removed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove student']);
    }
}

/**
 * Reinstate a removed student back to the group
 */
function reinstateStudent($db, $staffID, $input) {
    $removalID = $input['removalID'] ?? '';
    $studentID = $input['studentID'] ?? '';
    $programID = $input['programID'] ?? '';
    $tutGroup = $input['tutGroup'] ?? '';
    
    if (empty($removalID) || empty($studentID)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    // Verify this staff teaches this group
    $verifyStmt = $db->prepare("SELECT scheduleID FROM class_schedule 
                                WHERE staffID = ? AND programID = ? AND tutGroup = ? 
                                AND classType IN ('Tutorial', 'Practical', 'Lab')
                                LIMIT 1");
    $verifyStmt->bind_param("sss", $staffID, $programID, $tutGroup);
    $verifyStmt->execute();
    if ($verifyStmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        return;
    }
    
    // Update removal record
    $updateStmt = $db->prepare("UPDATE class_group_removals 
                                SET status = 'Reinstated', reinstatedAt = NOW(), reinstatedBy = ? 
                                WHERE removalID = ? AND status = 'Removed'");
    $updateStmt->bind_param("si", $staffID, $removalID);
    
    if ($updateStmt->execute() && $updateStmt->affected_rows > 0) {
        // Log the activity
        $studentStmt = $db->prepare("SELECT studentName FROM student WHERE studentID = ?");
        $studentStmt->bind_param("s", $studentID);
        $studentStmt->execute();
        $studentResult = $studentStmt->get_result()->fetch_assoc();
        $studentName = $studentResult['studentName'] ?? $studentID;
        
        logActivity(
            $db,
            $_SESSION['user_id'],
            'STUDENT_REINSTATEMENT',
            "Reinstated student {$studentName} ($studentID) back to {$programID} {$tutGroup}"
        );
        
        echo json_encode(['success' => true, 'message' => 'Student reinstated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reinstate student']);
    }
}

/**
 * Generate a PDF/printable report of students in the group
 */
function generateReport($db, $staffID) {
    $scheduleID = $_GET['scheduleID'] ?? '';
    $programID = $_GET['programID'] ?? '';
    $tutGroup = $_GET['tutGroup'] ?? '';
    
    if (empty($programID) || empty($tutGroup)) {
        die('Missing parameters');
    }
    
    // Verify this staff teaches this group and get courseID
    // Use scheduleID if provided for more specific filtering
    if (!empty($scheduleID) && $scheduleID !== 'undefined') {
        $verifyStmt = $db->prepare("SELECT scheduleID, courseID FROM class_schedule 
                                    WHERE staffID = ? AND scheduleID = ?
                                    AND classType IN ('Tutorial', 'Practical', 'Lab')
                                    LIMIT 1");
        $verifyStmt->bind_param("si", $staffID, $scheduleID);
    } else {
        $verifyStmt = $db->prepare("SELECT scheduleID, courseID FROM class_schedule 
                                    WHERE staffID = ? AND programID = ? AND tutGroup = ? 
                                    AND classType IN ('Tutorial', 'Practical', 'Lab')
                                    LIMIT 1");
        $verifyStmt->bind_param("sss", $staffID, $programID, $tutGroup);
    }
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result()->fetch_assoc();
    if (!$verifyResult) {
        die('Unauthorized');
    }
    $courseID = $verifyResult['courseID'];
    
    // Get the year and semester for this course
    $courseYearSemStmt = $db->prepare("SELECT year, semester FROM program_course WHERE programID = ? AND courseID = ? LIMIT 1");
    $courseYearSemStmt->bind_param("ss", $programID, $courseID);
    $courseYearSemStmt->execute();
    $courseYearSem = $courseYearSemStmt->get_result()->fetch_assoc();
    
    // Get program name
    $progStmt = $db->prepare("SELECT programName FROM program WHERE programID = ?");
    $progStmt->bind_param("s", $programID);
    $progStmt->execute();
    $programName = $progStmt->get_result()->fetch_assoc()['programName'] ?? $programID;
    
    // Get staff name
    $staffStmt = $db->prepare("SELECT staffName FROM staff WHERE staffID = ?");
    $staffStmt->bind_param("s", $staffID);
    $staffStmt->execute();
    $staffName = $staffStmt->get_result()->fetch_assoc()['staffName'] ?? 'Staff';
    
    // Get active students (filtered by year/semester)
    $sql = "SELECT s.studentID, s.studentName, s.email, s.contactNo, s.programID
            FROM student s
            WHERE s.programID = ? AND s.tutGroup = ?";
    if ($courseYearSem) {
        $sql .= " AND s.currentYear = ? AND s.currentSemester = ?";
    }
    $sql .= " AND NOT EXISTS (
                SELECT 1 FROM class_group_removals r 
                WHERE r.studentID = s.studentID 
                AND r.programID = s.programID 
                AND r.tutGroup = s.tutGroup 
                AND r.status = 'Removed'
            )
            ORDER BY s.studentName ASC";
    $stmt = $db->prepare($sql);
    if ($courseYearSem) {
        $stmt->bind_param("ssii", $programID, $tutGroup, $courseYearSem['year'], $courseYearSem['semester']);
    } else {
        $stmt->bind_param("ss", $programID, $tutGroup);
    }
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Generate HTML report
    $date = date('d M Y');
    $totalStudents = count($students);
    
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Tutorial Group Enrollment List - <?php echo htmlspecialchars($programID . ' ' . $tutGroup); ?></title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Times+New+Roman:ital,wght@0,400;0,700;1,400&family=Inter:wght@400;600&display=swap');

            :root { --primary: #000; --border: #000; }
            
            body { font-family: 'Inter', sans-serif; background: #525659; margin: 0; padding: 40px; display: flex; justify-content: center; }
            
            /* THE A4 PAGE */
            .page {
                background: white;
                width: 210mm;
                min-height: 297mm;
                padding: 20mm;
                box-shadow: 0 10px 30px rgba(0,0,0,0.5);
                position: relative;
                box-sizing: border-box;
            }

            /* HEADER */
            .header { text-align: center; border-bottom: 3px solid #000; padding-bottom: 20px; margin-bottom: 25px; }
            .logo-circle {
                width: 70px; height: 70px; background: #000; color: #fff; border-radius: 50%;
                font-family: 'Times New Roman', serif; font-weight: 700; font-size: 28px;
                display: grid; place-items: center; margin: 0 auto 15px auto;
            }
            .uni-name { font-family: 'Times New Roman', serif; text-transform: uppercase; font-size: 20px; letter-spacing: 1px; font-weight: 700; margin-bottom: 5px; color: #000; }
            .report-title { font-size: 16px; text-transform: uppercase; color: #333; font-weight: 600; }

            /* METADATA BOX */
            .meta-box { border: 1px solid #000; display: flex; margin-bottom: 25px; }
            .meta-col { flex: 1; padding: 10px; border-right: 1px solid #000; }
            .meta-col:last-child { border-right: none; }
            .label { display: block; font-size: 10px; text-transform: uppercase; color: #555; font-weight: 700; margin-bottom: 4px; }
            .value { font-size: 13px; font-weight: 600; color: #000; }

            /* TABLE */
            table { width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 30px; }
            th { background: #e5e5e5; text-align: left; padding: 10px 8px; border: 1px solid #000; text-transform: uppercase; font-size: 11px; }
            td { padding: 8px; border: 1px solid #000; color: #000; }

            /* FOOTER */
            .footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 40px; page-break-inside: avoid; }
            .summary { border: 1px solid #000; padding: 15px; width: 180px; font-size: 12px; line-height: 1.6; }
            .signature { text-align: center; width: 220px; }
            .sig-line { border-top: 1px solid #000; margin-top: 40px; padding-top: 8px; font-weight: 700; font-size: 13px; }

            /* FLOATING PRINT BUTTON */
            .fab-print {
                position: fixed; bottom: 30px; right: 30px;
                background: #8056ff; color: white;
                padding: 15px 25px; border-radius: 50px;
                font-weight: 600; cursor: pointer; border: none;
                box-shadow: 0 4px 15px rgba(0,0,0,0.3);
                display: flex; align-items: center; gap: 10px;
                font-family: 'Inter', sans-serif; transition: 0.2s;
            }
            .fab-print:hover { transform: translateY(-3px); background: #6b46c1; }

            @media print {
                body { background: white; padding: 0; }
                .page { box-shadow: none; margin: 0; width: 100%; height: auto; }
                .fab-print { display: none; }
                @page { margin: 10mm; }
            }
        </style>
    </head>
    <body>

        <button onclick="window.print()" class="fab-print">
            <i class="fa-solid fa-print"></i> Print / Save PDF
        </button>

        <div class="page">
            <div class="header">
                <div class="logo-circle">CL</div>
                <div class="uni-name">CampusLink System</div>
                <div class="report-title">Tutorial Group Enrollment List</div>
            </div>

            <div class="meta-box">
                <div class="meta-col" style="flex: 2;">
                    <span class="label">Programme</span>
                    <span class="value"><?php echo htmlspecialchars($programName); ?></span>
                </div>
                <div class="meta-col">
                    <span class="label">Tutorial Group</span>
                    <span class="value"><?php echo htmlspecialchars($programID . ' ' . $tutGroup); ?></span>
                </div>
            </div>
            <div class="meta-box" style="margin-top: -1px;">
                <div class="meta-col">
                    <span class="label">Generated On</span>
                    <span class="value"><?php echo $date; ?></span>
                </div>
                <div class="meta-col">
                    <span class="label">Instructor</span>
                    <span class="value"><?php echo htmlspecialchars($staffName); ?></span>
                </div>
                <div class="meta-col">
                    <span class="label">Total Students</span>
                    <span class="value"><?php echo $totalStudents; ?></span>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 5%; text-align: center;">No.</th>
                        <th style="width: 15%;">Student ID</th>
                        <th style="width: 30%;">Name</th>
                        <th style="width: 30%;">Email</th>
                        <th style="width: 15%;">Contact No.</th>
                        <th style="width: 10%;">Program</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $count = 1; foreach($students as $s): ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $count++; ?></td>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($s['studentID']); ?></td>
                        <td><?php echo htmlspecialchars($s['studentName']); ?></td>
                        <td style="font-size: 11px;"><?php echo htmlspecialchars($s['email']); ?></td>
                        <td><?php echo htmlspecialchars($s['contactNo'] ?? '-'); ?></td>
                        <td style="text-align: center;"><?php echo htmlspecialchars($s['programID']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="footer">
                <div class="summary">
                    <strong>Summary</strong><br>
                    Total Enrolled: <?php echo $totalStudents; ?><br>
                    Group: <?php echo htmlspecialchars($tutGroup); ?><br>
                </div>
                <div class="signature">
                    <div class="sig-line"><?php echo htmlspecialchars($staffName); ?></div>
                    <span style="font-size: 11px; color: #555;">Instructor Signature</span>
                </div>
            </div>
        </div>

    </body>
    </html>
    <?php
}
?>
