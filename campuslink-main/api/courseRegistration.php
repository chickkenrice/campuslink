<?php
/**
 * Course Registration API
 * 
 * REQUIRED: Run this SQL first if not done:
 * ALTER TABLE academic_term ADD COLUMN regStartDate DATE NULL AFTER endDate, ADD COLUMN regEndDate DATE NULL AFTER regStartDate;
 * 
 * GET actions:
 *   getRegistrationTerm   - Find the upcoming term with open registration window
 *   getAvailableCourses   - List courses available for registration (with capacity info)
 *   getRegisteredCourses  - Get student's registered courses for a term
 *   getEnrollmentStats    - Staff: enrollment statistics per course
 *   getOfferings          - Admin: list all offerings for a term
 * 
 * POST actions:
 *   register         - Register a student for a course offering
 *   drop             - Drop a student's registration
 *   createOfferings  - Admin: bulk-create offerings from programme structure
 *   updateOffering   - Admin: update offering capacity/status
 *   deleteOffering   - Admin: delete an offering
 *   updateRegPeriod  - Admin: set registration dates on a term
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

session_start();

$db = get_db_connection();

// Route by method
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'getRegistrationTerm':
            getRegistrationTerm($db);
            break;
        case 'getAvailableCourses':
            getAvailableCourses($db);
            break;
        case 'getRegisteredCourses':
            getRegisteredCourses($db);
            break;
        case 'getEnrollmentStats':
            getEnrollmentStats($db);
            break;
        case 'getOfferings':
            getOfferings($db);
            break;
        case 'getTerms':
            getTerms($db);
            break;
        case 'getPrograms':
            getPrograms($db);
            break;
        case 'autoRegisterCore':
            autoRegisterCoreCourses($db);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'register':
            registerCourse($db, $data);
            break;
        case 'drop':
            dropCourse($db, $data);
            break;
        case 'createOfferings':
            createOfferings($db, $data);
            break;
        case 'updateOffering':
            updateOffering($db, $data);
            break;
        case 'deleteOffering':
            deleteOffering($db, $data);
            break;
        case 'updateRegPeriod':
            updateRegPeriod($db, $data);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

// =============================================
// GET FUNCTIONS
// =============================================

/**
 * Auto-generate course offerings for a term/program if none exist yet.
 * Reads from programme structure and creates offerings with default capacity of 40.
 */
function autoGenerateOfferingsIfNeeded($db, $termID, $programID) {
    // Check if offerings already exist for this term+program
    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM course_offering WHERE termID = ? AND programID = ?");
    $stmt->bind_param("is", $termID, $programID);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['cnt'];

    if ($count > 0) {
        return; // Offerings already exist
    }

    // Get term details (year, semester)
    $stmt = $db->prepare("SELECT year, semester FROM academic_term WHERE termID = ?");
    $stmt->bind_param("i", $termID);
    $stmt->execute();
    $term = $stmt->get_result()->fetch_assoc();

    if (!$term) return;

    // Get courses from programme structure for this year/semester
    $stmt = $db->prepare("
        SELECT pc.courseID 
        FROM program_course pc
        JOIN course c ON pc.courseID = c.courseID
        WHERE pc.programID = ? AND pc.year = ? AND pc.semester = ?
    ");
    $stmt->bind_param("sii", $programID, $term['year'], $term['semester']);
    $stmt->execute();
    $courses = $stmt->get_result();

    $defaultCapacity = 40;
    while ($course = $courses->fetch_assoc()) {
        $insertStmt = $db->prepare("
            INSERT INTO course_offering (courseID, programID, termID, sectionNo, capacity, status) 
            VALUES (?, ?, ?, 1, ?, 'Open')
        ");
        $insertStmt->bind_param("ssii", $course['courseID'], $programID, $termID, $defaultCapacity);
        $insertStmt->execute();
    }
}

/**
 * Auto-set registration dates on an 'Upcoming' term if not already set.
 * Registration opens on Week 1 of the PREVIOUS term and closes at end of Week 3
 * (previous term startDate + 20 days), so students register during the first
 * 3 weeks of their current semester for the next semester.
 */
function autoSetRegDatesIfNeeded($db, $termID, $startDate) {
    // Check if dates are already set
    $stmt = $db->prepare("SELECT regStartDate, regEndDate, programID, year, semester FROM academic_term WHERE termID = ?");
    $stmt->bind_param("i", $termID);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!empty($row['regStartDate']) && !empty($row['regEndDate'])) {
        return; // Already set
    }

    // Year 1 Semester 1 does not require a registration window
    if ((int)$row['year'] === 1 && (int)$row['semester'] === 1) {
        return;
    }

    // Determine previous term (year/semester)
    $programID = $row['programID'];
    $year      = (int)$row['year'];
    $semester  = (int)$row['semester'];

    if ($semester > 1) {
        $prevYear = $year;
        $prevSem  = $semester - 1;
    } else {
        $prevYear = $year - 1;
        $prevSem  = 3;
    }

    $regStartStr = null;
    $regEndStr   = null;

    if ($prevYear >= 1) {
        $prevStmt = $db->prepare("SELECT startDate FROM academic_term WHERE programID = ? AND year = ? AND semester = ? ORDER BY termID DESC LIMIT 1");
        $prevStmt->bind_param("sii", $programID, $prevYear, $prevSem);
        $prevStmt->execute();
        $prevTerm = $prevStmt->get_result()->fetch_assoc();

        if ($prevTerm) {
            $regStart = new DateTime($prevTerm['startDate']);
            $regEnd   = clone $regStart;
            $regEnd->modify('+20 days'); // end of Week 3
            $regStartStr = $regStart->format('Y-m-d');
            $regEndStr   = $regEnd->format('Y-m-d');
        }
    }

    // Fallback: if no previous term found, open 4 weeks before the upcoming term starts
    if (!$regStartStr) {
        $start    = new DateTime($startDate);
        $regEnd   = clone $start;
        $regEnd->modify('-1 day');
        $regStart = clone $start;
        $regStart->modify('-28 days');
        $regStartStr = $regStart->format('Y-m-d');
        $regEndStr   = $regEnd->format('Y-m-d');
    }

    $stmt = $db->prepare("UPDATE academic_term SET regStartDate = ?, regEndDate = ? WHERE termID = ?");
    $stmt->bind_param("ssi", $regStartStr, $regEndStr, $termID);
    $stmt->execute();
}

/**
 * Find the term that's currently open for registration
 * Returns the upcoming term where CURDATE() is between regStartDate and regEndDate
 * AND the term is exactly 1 semester ahead of student's current year/semester
 * 
 * Auto-generates registration dates and course offerings if not already set.
 */
function getRegistrationTerm($db) {
    $studentID = $_GET['studentID'] ?? '';

    if (empty($studentID)) {
        echo json_encode(['success' => false, 'message' => 'Student ID required']);
        return;
    }

    // Get student's program info
    $stmt = $db->prepare("SELECT programID, currentYear, currentSemester FROM student WHERE studentID = ?");
    $stmt->bind_param("s", $studentID);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        return;
    }

    // Calculate the student's next semester (1 semester ahead)
    // Y1S1 -> Y1S2, Y1S2 -> Y1S3, Y1S3 -> Y2S1, etc.
    $nextYear = (int)$student['currentYear'];
    $nextSem = (int)$student['currentSemester'] + 1;
    if ($nextSem > 3) {
        $nextSem = 1;
        $nextYear++;
    }

    // First, find any term matching the student's next year/semester (any status)
    $stmt = $db->prepare("
        SELECT t.*, p.programName 
        FROM academic_term t
        JOIN program p ON t.programID = p.programID
        WHERE t.programID = ? 
          AND t.year = ?
          AND t.semester = ?
        ORDER BY t.startDate ASC
        LIMIT 1
    ");
    $stmt->bind_param("sii", $student['programID'], $nextYear, $nextSem);
    $stmt->execute();
    $upcomingTerm = $stmt->get_result()->fetch_assoc();

    if (!$upcomingTerm) {
        // Check if student has completed the final semester of their programme (max Year 3 Semester 3)
        $maxYearStmt = $db->prepare("SELECT MAX(year) AS maxYear FROM academic_term WHERE programID = ?");
        $maxYearStmt->bind_param("s", $student['programID']);
        $maxYearStmt->execute();
        $maxYearRow = $maxYearStmt->get_result()->fetch_assoc();
        $maxYear = (int)($maxYearRow['maxYear'] ?? 3);

        if ($nextYear > $maxYear) {
            echo json_encode([
                'success' => false,
                'isFinalSemester' => true,
                'message' => 'You have completed all semesters of your programme. Congratulations!',
                'student' => $student
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'isFinalSemester' => false,
                'message' => "No term found for Year $nextYear Semester $nextSem. Contact administration.",
                'upcomingTerm' => null,
                'student' => $student,
                'nextYear' => $nextYear,
                'nextSemester' => $nextSem
            ]);
        }
        return;
    }

    // Auto-set registration dates if not already set (4 weeks before term start)
    autoSetRegDatesIfNeeded($db, $upcomingTerm['termID'], $upcomingTerm['startDate']);

    // Auto-generate course offerings if none exist for this term+program
    autoGenerateOfferingsIfNeeded($db, $upcomingTerm['termID'], $upcomingTerm['programID']);

    // Re-fetch the term to get updated regStartDate/regEndDate
    $stmt = $db->prepare("
        SELECT t.*, p.programName 
        FROM academic_term t
        JOIN program p ON t.programID = p.programID
        WHERE t.termID = ?
    ");
    $stmt->bind_param("i", $upcomingTerm['termID']);
    $stmt->execute();
    $term = $stmt->get_result()->fetch_assoc();

    // Check if current date is within the registration window
    $today = date('Y-m-d');
    if ($today >= $term['regStartDate'] && $today <= $term['regEndDate']) {
        echo json_encode([
            'success' => true,
            'data' => $term,
            'student' => $student,
            'nextYear' => $nextYear,
            'nextSemester' => $nextSem
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No registration period is currently open.',
            'upcomingTerm' => $term,
            'student' => $student,
            'nextYear' => $nextYear,
            'nextSemester' => $nextSem
        ]);
    }
}

/**
 * Get available courses for a specific term
 * Includes capacity, enrolled count, and prerequisite info
 */
function getAvailableCourses($db) {
    $termID = $_GET['termID'] ?? '';
    $studentID = $_GET['studentID'] ?? '';

    if (empty($termID) || empty($studentID)) {
        echo json_encode(['success' => false, 'message' => 'termID and studentID required']);
        return;
    }

    // Get the term details (year, semester, programID)
    $stmt = $db->prepare("SELECT * FROM academic_term WHERE termID = ?");
    $stmt->bind_param("i", $termID);
    $stmt->execute();
    $term = $stmt->get_result()->fetch_assoc();

    if (!$term) {
        echo json_encode(['success' => false, 'message' => 'Term not found']);
        return;
    }

    // Auto-generate offerings if none exist (safety net)
    autoGenerateOfferingsIfNeeded($db, $termID, $term['programID']);

    // Get course offerings for this term with enrollment counts
    $stmt = $db->prepare("
        SELECT 
            co.offeringID,
            co.courseID,
            c.courseName,
            c.creditHours,
            co.sectionNo,
            co.capacity,
            co.status,
            pc.type AS courseType,
            COALESCE(reg.enrolledCount, 0) AS enrolledCount,
            CASE WHEN cr.registrationID IS NOT NULL THEN 1 ELSE 0 END AS isRegistered,
            cr.status AS registrationStatus
        FROM course_offering co
        JOIN course c ON co.courseID = c.courseID
        LEFT JOIN program_course pc ON pc.courseID = co.courseID AND pc.programID = co.programID
        LEFT JOIN (
            SELECT offeringID, COUNT(*) AS enrolledCount 
            FROM course_registration 
            WHERE status = 'Registered' 
            GROUP BY offeringID
        ) reg ON reg.offeringID = co.offeringID
        LEFT JOIN course_registration cr ON cr.offeringID = co.offeringID AND cr.studentID = ?
        WHERE co.termID = ? AND co.programID = ?
        ORDER BY pc.type ASC, co.courseID ASC
    ");
    $stmt->bind_param("sis", $studentID, $termID, $term['programID']);
    $stmt->execute();
    $result = $stmt->get_result();

    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $row['slotsRemaining'] = max(0, $row['capacity'] - $row['enrolledCount']);
        $courses[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $courses, 'term' => $term]);
}

/**
 * Get a student's registered courses for a specific term
 */
function getRegisteredCourses($db) {
    $studentID = $_GET['studentID'] ?? '';
    $termID = $_GET['termID'] ?? '';

    if (empty($studentID)) {
        echo json_encode(['success' => false, 'message' => 'studentID required']);
        return;
    }

    $query = "
        SELECT 
            cr.registrationID,
            cr.offeringID,
            cr.status,
            cr.registeredAt,
            co.courseID,
            c.courseName,
            c.creditHours,
            co.sectionNo,
            co.capacity,
            pc.type AS courseType,
            COALESCE(reg.enrolledCount, 0) AS enrolledCount
        FROM course_registration cr
        JOIN course_offering co ON cr.offeringID = co.offeringID
        JOIN course c ON co.courseID = c.courseID
        LEFT JOIN program_course pc ON pc.courseID = co.courseID AND pc.programID = co.programID
        LEFT JOIN (
            SELECT offeringID, COUNT(*) AS enrolledCount 
            FROM course_registration 
            WHERE status = 'Registered' 
            GROUP BY offeringID
        ) reg ON reg.offeringID = co.offeringID
        WHERE cr.studentID = ? AND cr.status = 'Registered'
    ";

    if (!empty($termID)) {
        $query .= " AND co.termID = ?";
        $stmt = $db->prepare($query . " ORDER BY co.courseID ASC");
        $stmt->bind_param("si", $studentID, $termID);
    } else {
        $stmt = $db->prepare($query . " ORDER BY co.courseID ASC");
        $stmt->bind_param("s", $studentID);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $courses = [];
    $totalCredits = 0;
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
        $totalCredits += $row['creditHours'];
    }

    echo json_encode([
        'success' => true,
        'data' => $courses,
        'totalCredits' => $totalCredits
    ]);
}

/**
 * Staff: Get enrollment statistics per course
 */
function getEnrollmentStats($db) {
    $termID = $_GET['termID'] ?? '';
    $programID = $_GET['programID'] ?? '';

    $query = "
        SELECT 
            co.offeringID,
            co.courseID,
            c.courseName,
            c.creditHours,
            co.sectionNo,
            co.capacity,
            co.status,
            co.programID,
            p.programName,
            pc.type AS courseType,
            at.year,
            at.semester,
            at.academicYear,
            COALESCE(reg.enrolledCount, 0) AS enrolledCount,
            COALESCE(dropped.droppedCount, 0) AS droppedCount
        FROM course_offering co
        JOIN course c ON co.courseID = c.courseID
        JOIN program p ON co.programID = p.programID
        JOIN academic_term at ON co.termID = at.termID
        LEFT JOIN program_course pc ON pc.courseID = co.courseID AND pc.programID = co.programID
        LEFT JOIN (
            SELECT offeringID, COUNT(*) AS enrolledCount 
            FROM course_registration 
            WHERE status = 'Registered' 
            GROUP BY offeringID
        ) reg ON reg.offeringID = co.offeringID
        LEFT JOIN (
            SELECT offeringID, COUNT(*) AS droppedCount 
            FROM course_registration 
            WHERE status = 'Dropped' 
            GROUP BY offeringID
        ) dropped ON dropped.offeringID = co.offeringID
        WHERE 1=1
    ";

    $params = [];
    $types = '';

    if (!empty($termID)) {
        $query .= " AND co.termID = ?";
        $params[] = $termID;
        $types .= 'i';
    }
    if (!empty($programID)) {
        $query .= " AND co.programID = ?";
        $params[] = $programID;
        $types .= 's';
    }

    $query .= " ORDER BY co.programID, co.courseID ASC";

    $stmt = $db->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $stats = [];
    while ($row = $result->fetch_assoc()) {
        $row['fillRate'] = $row['capacity'] > 0 
            ? round(($row['enrolledCount'] / $row['capacity']) * 100, 1) 
            : 0;
        $stats[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $stats]);
}

/**
 * Admin: Get all offerings for a specific term
 */
function getOfferings($db) {
    $termID = $_GET['termID'] ?? '';
    $programID = $_GET['programID'] ?? '';

    if (empty($termID) && empty($programID)) {
        echo json_encode(['success' => false, 'message' => 'termID or programID required']);
        return;
    }

    $query = "
        SELECT 
            co.*, 
            c.courseName, c.creditHours,
            pc.type AS courseType,
            at.year, at.semester, at.academicYear, at.status AS termStatus,
            at.regStartDate, at.regEndDate,
            p.programName,
            COALESCE(reg.enrolledCount, 0) AS enrolledCount
        FROM course_offering co
        JOIN course c ON co.courseID = c.courseID
        JOIN academic_term at ON co.termID = at.termID
        JOIN program p ON co.programID = p.programID
        LEFT JOIN program_course pc ON pc.courseID = co.courseID AND pc.programID = co.programID
        LEFT JOIN (
            SELECT offeringID, COUNT(*) AS enrolledCount 
            FROM course_registration 
            WHERE status = 'Registered' 
            GROUP BY offeringID
        ) reg ON reg.offeringID = co.offeringID
        WHERE 1=1
    ";

    $params = [];
    $types = '';

    if (!empty($termID)) {
        $query .= " AND co.termID = ?";
        $params[] = $termID;
        $types .= 'i';
    }
    if (!empty($programID)) {
        $query .= " AND co.programID = ?";
        $params[] = $programID;
        $types .= 's';
    }

    $query .= " ORDER BY co.courseID ASC";

    $stmt = $db->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $offerings = [];
    while ($row = $result->fetch_assoc()) {
        $offerings[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $offerings]);
}

/**
 * Get all terms (for dropdowns)
 */
function getTerms($db) {
    $result = $db->query("
        SELECT t.*, p.programName 
        FROM academic_term t 
        JOIN program p ON t.programID = p.programID
        ORDER BY t.academicYear DESC, t.programID, t.year, t.semester
    ");

    $terms = [];
    while ($row = $result->fetch_assoc()) {
        $terms[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $terms]);
}

/**
 * Get all programs (for dropdowns)
 */
function getPrograms($db) {
    $result = $db->query("SELECT * FROM program ORDER BY programName");
    $programs = [];
    while ($row = $result->fetch_assoc()) {
        $programs[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $programs]);
}

// =============================================
// POST FUNCTIONS
// =============================================

/**
 * Register a student for a course offering
 */
function registerCourse($db, $data) {
    $studentID = $data['studentID'] ?? '';
    $offeringID = $data['offeringID'] ?? '';

    if (empty($studentID) || empty($offeringID)) {
        echo json_encode(['success' => false, 'message' => 'studentID and offeringID required']);
        return;
    }

    // 1. Check offering exists and is open
    $stmt = $db->prepare("
        SELECT co.*, at.regStartDate, at.regEndDate, at.status AS termStatus
        FROM course_offering co
        JOIN academic_term at ON co.termID = at.termID
        WHERE co.offeringID = ?
    ");
    $stmt->bind_param("i", $offeringID);
    $stmt->execute();
    $offering = $stmt->get_result()->fetch_assoc();

    if (!$offering) {
        echo json_encode(['success' => false, 'message' => 'Course offering not found']);
        return;
    }

    if ($offering['status'] !== 'Open') {
        echo json_encode(['success' => false, 'message' => 'This course offering is currently ' . $offering['status']]);
        return;
    }

    // 2. Check registration window is open
    $today = date('Y-m-d');
    if (!empty($offering['regStartDate']) && !empty($offering['regEndDate'])) {
        if ($today < $offering['regStartDate'] || $today > $offering['regEndDate']) {
            echo json_encode(['success' => false, 'message' => 'Registration period is not open. Period: ' . $offering['regStartDate'] . ' to ' . $offering['regEndDate']]);
            return;
        }
    }

    // 3. Check not already registered
    $stmt = $db->prepare("
        SELECT registrationID, status FROM course_registration 
        WHERE studentID = ? AND offeringID = ?
    ");
    $stmt->bind_param("si", $studentID, $offeringID);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($existing && $existing['status'] === 'Registered') {
        echo json_encode(['success' => false, 'message' => 'You are already registered for this course']);
        return;
    }

    // 4. Check capacity
    $stmt = $db->prepare("
        SELECT COUNT(*) AS cnt FROM course_registration 
        WHERE offeringID = ? AND status = 'Registered'
    ");
    $stmt->bind_param("i", $offeringID);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['cnt'];

    if ($count >= $offering['capacity']) {
        echo json_encode(['success' => false, 'message' => 'This course section is full (' . $count . '/' . $offering['capacity'] . ')']);
        return;
    }

    // 5. Check elective limit (1 elective per year/semester group)
    $termStmt = $db->prepare("SELECT year, semester FROM academic_term WHERE termID = ?");
    $termStmt->bind_param("i", $offering['termID']);
    $termStmt->execute();
    $termInfo = $termStmt->get_result()->fetch_assoc();

    // Check if this course is an elective
    $typeStmt = $db->prepare("SELECT type FROM program_course WHERE programID = ? AND courseID = ?");
    $typeStmt->bind_param("ss", $offering['programID'], $offering['courseID']);
    $typeStmt->execute();
    $courseTypeRow = $typeStmt->get_result()->fetch_assoc();
    $isElective = ($courseTypeRow && $courseTypeRow['type'] === 'Elective');

    if ($isElective && $termInfo) {
        // Get all elective courseIDs for this program/year/semester
        $electStmt = $db->prepare("
            SELECT pc.courseID FROM program_course pc
            WHERE pc.programID = ? AND pc.year = ? AND pc.semester = ? AND pc.type = 'Elective'
        ");
        $electStmt->bind_param("sii", $offering['programID'], $termInfo['year'], $termInfo['semester']);
        $electStmt->execute();
        $electResult = $electStmt->get_result();
        $electiveCourseIDs = [];
        while ($eRow = $electResult->fetch_assoc()) {
            $electiveCourseIDs[] = $eRow['courseID'];
        }

        // Check if student already has a different elective registered from this group
        if (count($electiveCourseIDs) > 0) {
            $placeholders = implode(',', array_fill(0, count($electiveCourseIDs), '?'));
            $checkSql = "
                SELECT co.courseID FROM course_registration cr
                JOIN course_offering co ON cr.offeringID = co.offeringID
                WHERE cr.studentID = ? AND co.termID = ? AND cr.status = 'Registered'
                AND co.courseID IN ($placeholders)
                AND co.courseID != ?
            ";
            $checkStmt = $db->prepare($checkSql);
            $bindTypes = 'si' . str_repeat('s', count($electiveCourseIDs)) . 's';
            $bindParams = array_merge([$studentID, $offering['termID']], $electiveCourseIDs, [$offering['courseID']]);
            $checkStmt->bind_param($bindTypes, ...$bindParams);
            $checkStmt->execute();
            $existingElective = $checkStmt->get_result()->fetch_assoc();

            if ($existingElective) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'You can only choose 1 elective course. Drop your current elective (' . $existingElective['courseID'] . ') first to select a different one.'
                ]);
                return;
            }
        }
    }

    // 6. Check duplicate course registration (same courseID, different section)
    $stmt = $db->prepare("
        SELECT cr.registrationID 
        FROM course_registration cr
        JOIN course_offering co ON cr.offeringID = co.offeringID
        WHERE cr.studentID = ? AND co.courseID = ? AND co.termID = ? AND cr.status = 'Registered'
    ");
    $stmt->bind_param("ssi", $studentID, $offering['courseID'], $offering['termID']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You are already registered for another section of this course']);
        return;
    }

    // 7. Insert registration
    if ($existing && $existing['status'] === 'Dropped') {
        // Re-register: update existing record
        $stmt = $db->prepare("
            UPDATE course_registration 
            SET status = 'Registered', registeredAt = CURRENT_TIMESTAMP, droppedAt = NULL 
            WHERE registrationID = ?
        ");
        $stmt->bind_param("i", $existing['registrationID']);
    } else {
        $stmt = $db->prepare("
            INSERT INTO course_registration (studentID, offeringID, status) 
            VALUES (?, ?, 'Registered')
        ");
        $stmt->bind_param("si", $studentID, $offeringID);
    }
    $stmt->execute();

    // 8. Update offering status if full
    $newCount = $count + 1;
    if ($newCount >= $offering['capacity']) {
        $updateStmt = $db->prepare("UPDATE course_offering SET status = 'Full' WHERE offeringID = ?");
        $updateStmt->bind_param("i", $offeringID);
        $updateStmt->execute();
    }

    $response = [
        'success' => true,
        'message' => 'Successfully registered for the course',
        'enrolled' => $newCount,
        'capacity' => $offering['capacity']
    ];

    echo json_encode($response);
}

/**
 * Drop a student's course registration
 */
function dropCourse($db, $data) {
    $studentID = $data['studentID'] ?? '';
    $offeringID = $data['offeringID'] ?? '';

    if (empty($studentID) || empty($offeringID)) {
        echo json_encode(['success' => false, 'message' => 'studentID and offeringID required']);
        return;
    }

    // Check registration exists
    $stmt = $db->prepare("
        SELECT cr.registrationID, co.termID
        FROM course_registration cr
        JOIN course_offering co ON cr.offeringID = co.offeringID
        WHERE cr.studentID = ? AND cr.offeringID = ? AND cr.status = 'Registered'
    ");
    $stmt->bind_param("si", $studentID, $offeringID);
    $stmt->execute();
    $reg = $stmt->get_result()->fetch_assoc();

    if (!$reg) {
        echo json_encode(['success' => false, 'message' => 'Registration not found']);
        return;
    }

    // Check registration window is still open
    $stmt = $db->prepare("
        SELECT regStartDate, regEndDate FROM academic_term WHERE termID = ?
    ");
    $stmt->bind_param("i", $reg['termID']);
    $stmt->execute();
    $term = $stmt->get_result()->fetch_assoc();

    $today = date('Y-m-d');
    if ($term && !empty($term['regStartDate']) && !empty($term['regEndDate'])) {
        if ($today < $term['regStartDate'] || $today > $term['regEndDate']) {
            echo json_encode(['success' => false, 'message' => 'Registration period has ended. Cannot drop courses.']);
            return;
        }
    }

    // Check core course minimum credit hours restriction
    // Get the course type of the course being dropped
    $courseStmt = $db->prepare("
        SELECT c.courseID, c.creditHours, pc.type AS courseType 
        FROM course_offering co 
        JOIN course c ON co.courseID = c.courseID
        LEFT JOIN program_course pc ON c.courseID = pc.courseID 
            AND pc.programID = (SELECT programID FROM student WHERE studentID = ?)
        WHERE co.offeringID = ?
    ");
    $courseStmt->bind_param("si", $studentID, $offeringID);
    $courseStmt->execute();
    $courseInfo = $courseStmt->get_result()->fetch_assoc();

    if ($courseInfo) {
        $courseType = $courseInfo['courseType'] ?? null;
        // Only block drop if course is explicitly a core/main course
        $isCore = ($courseType === 'Core' || $courseType === 'Main');
        
        if ($isCore) {
            // Calculate remaining core credits after dropping this course
            $coreCheckStmt = $db->prepare("
                SELECT SUM(c.creditHours) as totalCoreCredits
                FROM course_registration cr
                JOIN course_offering co ON cr.offeringID = co.offeringID
                JOIN course c ON co.courseID = c.courseID
                LEFT JOIN program_course pc ON c.courseID = pc.courseID 
                    AND pc.programID = (SELECT programID FROM student WHERE studentID = ?)
                WHERE cr.studentID = ? 
                AND cr.status = 'Registered' 
                AND co.termID = ?
                AND cr.offeringID != ?
                AND (pc.type = 'Core' OR pc.type = 'Main')
            ");
            $coreCheckStmt->bind_param("ssii", $studentID, $studentID, $reg['termID'], $offeringID);
            $coreCheckStmt->execute();
            $coreResult = $coreCheckStmt->get_result()->fetch_assoc();
            $remainingCoreCredits = intval($coreResult['totalCoreCredits'] ?? 0);
            
            if ($remainingCoreCredits < 6) {
                echo json_encode([
                    'success' => false, 
                    'message' => "Cannot drop this course. You must keep at least 6 credit hours of core courses. Remaining would be {$remainingCoreCredits} credit hours."
                ]);
                return;
            }
        }
    }

    // Update to Dropped
    $stmt = $db->prepare("
        UPDATE course_registration 
        SET status = 'Dropped', droppedAt = CURRENT_TIMESTAMP 
        WHERE registrationID = ?
    ");
    $stmt->bind_param("i", $reg['registrationID']);
    $stmt->execute();

    // Update offering status back to Open if it was Full
    $stmt = $db->prepare("UPDATE course_offering SET status = 'Open' WHERE offeringID = ? AND status = 'Full'");
    $stmt->bind_param("i", $offeringID);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Course dropped successfully']);
}

/**
 * Admin: Bulk-create offerings from programme structure
 */
function createOfferings($db, $data) {
    $termID = $data['termID'] ?? '';
    $programID = $data['programID'] ?? '';
    $capacity = intval($data['capacity'] ?? 40);

    if (empty($termID) || empty($programID)) {
        echo json_encode(['success' => false, 'message' => 'termID and programID required']);
        return;
    }

    // Get term details
    $stmt = $db->prepare("SELECT year, semester FROM academic_term WHERE termID = ?");
    $stmt->bind_param("i", $termID);
    $stmt->execute();
    $term = $stmt->get_result()->fetch_assoc();

    if (!$term) {
        echo json_encode(['success' => false, 'message' => 'Term not found']);
        return;
    }

    // Get courses from programme structure for this year/semester
    $stmt = $db->prepare("
        SELECT pc.courseID, c.courseName 
        FROM program_course pc
        JOIN course c ON pc.courseID = c.courseID
        WHERE pc.programID = ? AND pc.year = ? AND pc.semester = ?
    ");
    $stmt->bind_param("sii", $programID, $term['year'], $term['semester']);
    $stmt->execute();
    $courses = $stmt->get_result();

    $created = 0;
    $skipped = 0;

    while ($course = $courses->fetch_assoc()) {
        // Check if offering already exists
        $checkStmt = $db->prepare("
            SELECT offeringID FROM course_offering 
            WHERE courseID = ? AND programID = ? AND termID = ?
        ");
        $checkStmt->bind_param("ssi", $course['courseID'], $programID, $termID);
        $checkStmt->execute();

        if ($checkStmt->get_result()->num_rows > 0) {
            $skipped++;
            continue;
        }

        // Create offering
        $insertStmt = $db->prepare("
            INSERT INTO course_offering (courseID, programID, termID, sectionNo, capacity, status) 
            VALUES (?, ?, ?, 1, ?, 'Open')
        ");
        $insertStmt->bind_param("ssii", $course['courseID'], $programID, $termID, $capacity);
        $insertStmt->execute();
        $created++;
    }

    echo json_encode([
        'success' => true,
        'message' => "Created $created offering(s). Skipped $skipped (already exist).",
        'created' => $created,
        'skipped' => $skipped
    ]);
}

/**
 * Admin: Update an offering's capacity or status
 */
function updateOffering($db, $data) {
    $offeringID = $data['offeringID'] ?? '';
    $capacity = isset($data['capacity']) ? intval($data['capacity']) : null;
    $status = $data['status'] ?? null;

    if (empty($offeringID)) {
        echo json_encode(['success' => false, 'message' => 'offeringID required']);
        return;
    }

    $updates = [];
    $params = [];
    $types = '';

    if ($capacity !== null) {
        $updates[] = "capacity = ?";
        $params[] = $capacity;
        $types .= 'i';
    }
    if ($status !== null) {
        $updates[] = "status = ?";
        $params[] = $status;
        $types .= 's';
    }

    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'Nothing to update']);
        return;
    }

    $params[] = $offeringID;
    $types .= 'i';

    $sql = "UPDATE course_offering SET " . implode(', ', $updates) . " WHERE offeringID = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Offering updated successfully']);
}

/**
 * Admin: Delete an offering
 */
function deleteOffering($db, $data) {
    $offeringID = $data['offeringID'] ?? '';

    if (empty($offeringID)) {
        echo json_encode(['success' => false, 'message' => 'offeringID required']);
        return;
    }

    // Check if any students are registered
    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM course_registration WHERE offeringID = ? AND status = 'Registered'");
    $stmt->bind_param("i", $offeringID);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['cnt'];

    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => "Cannot delete: $count student(s) are registered"]);
        return;
    }

    $stmt = $db->prepare("DELETE FROM course_offering WHERE offeringID = ?");
    $stmt->bind_param("i", $offeringID);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Offering deleted successfully']);
}

/**
 * Admin: Set registration dates on a term
 */
function updateRegPeriod($db, $data) {
    $termID = $data['termID'] ?? '';
    $regStartDate = $data['regStartDate'] ?? '';
    $regEndDate = $data['regEndDate'] ?? '';

    if (empty($termID) || empty($regStartDate) || empty($regEndDate)) {
        echo json_encode(['success' => false, 'message' => 'termID, regStartDate, and regEndDate required']);
        return;
    }

    if ($regStartDate > $regEndDate) {
        echo json_encode(['success' => false, 'message' => 'Start date cannot be after end date']);
        return;
    }

    $stmt = $db->prepare("UPDATE academic_term SET regStartDate = ?, regEndDate = ? WHERE termID = ?");
    $stmt->bind_param("ssi", $regStartDate, $regEndDate, $termID);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Registration period updated successfully']);
}

/**
 * Auto-register core courses for a student
 * Called when student first opens enrollment — registers all Main/Core offerings
 */
function autoRegisterCoreCourses($db) {
    $studentID = $_GET['studentID'] ?? '';
    $termID = $_GET['termID'] ?? '';

    if (empty($studentID) || empty($termID)) {
        echo json_encode(['success' => false, 'message' => 'studentID and termID required']);
        return;
    }

    // Get term details
    $stmt = $db->prepare("SELECT * FROM academic_term WHERE termID = ?");
    $stmt->bind_param("i", $termID);
    $stmt->execute();
    $term = $stmt->get_result()->fetch_assoc();

    if (!$term) {
        echo json_encode(['success' => false, 'message' => 'Term not found']);
        return;
    }

    // Get all core/main course offerings for this term
    $stmt = $db->prepare("
        SELECT co.offeringID, co.courseID, co.capacity, co.status
        FROM course_offering co
        JOIN program_course pc ON co.courseID = pc.courseID AND co.programID = pc.programID
        WHERE co.termID = ? AND co.programID = ? 
          AND pc.year = ? AND pc.semester = ?
          AND pc.type = 'Main'
          AND co.status != 'Closed'
    ");
    $stmt->bind_param("isii", $termID, $term['programID'], $term['year'], $term['semester']);
    $stmt->execute();
    $offerings = $stmt->get_result();

    $registered = 0;
    $skipped = 0;

    while ($offering = $offerings->fetch_assoc()) {
        // Check if student already has ANY record for this offering (Registered or Dropped)
        $checkStmt = $db->prepare("
            SELECT registrationID, status FROM course_registration 
            WHERE studentID = ? AND offeringID = ?
        ");
        $checkStmt->bind_param("si", $studentID, $offering['offeringID']);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();

        if ($existing) {
            // Already has a record (Registered or Dropped) — skip
            $skipped++;
            continue;
        }

        // Check capacity
        $capStmt = $db->prepare("SELECT COUNT(*) AS cnt FROM course_registration WHERE offeringID = ? AND status = 'Registered'");
        $capStmt->bind_param("i", $offering['offeringID']);
        $capStmt->execute();
        $count = $capStmt->get_result()->fetch_assoc()['cnt'];

        if ($count >= $offering['capacity']) {
            $skipped++;
            continue;
        }

        // Auto-register
        $insertStmt = $db->prepare("INSERT INTO course_registration (studentID, offeringID, status) VALUES (?, ?, 'Registered')");
        $insertStmt->bind_param("si", $studentID, $offering['offeringID']);
        $insertStmt->execute();
        $registered++;

        // Update offering status if now full
        if (($count + 1) >= $offering['capacity']) {
            $fullStmt = $db->prepare("UPDATE course_offering SET status = 'Full' WHERE offeringID = ?");
            $fullStmt->bind_param("i", $offering['offeringID']);
            $fullStmt->execute();
        }
    }

    echo json_encode([
        'success' => true,
        'message' => $registered > 0 ? "Auto-registered for $registered core course(s)." : 'All core courses already processed.',
        'registered' => $registered,
        'skipped' => $skipped
    ]);
}

// =============================================
// HELPER FUNCTIONS
// =============================================


