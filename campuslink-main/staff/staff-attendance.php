<?php
session_start();
require_once '../includes/config.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit;
}
prevent_back_button_cache();

$db = get_db_connection();
$staffID = $_SESSION['user_id'];
// Handle ID mapping if needed (U001 -> S001)
if (strpos($staffID, 'U') === 0) {
    $stmtUser = $db->prepare("SELECT staffID, staffName FROM staff WHERE userID = ?");
    $stmtUser->bind_param("s", $staffID);
    $stmtUser->execute();
    $resUser = $stmtUser->get_result()->fetch_assoc();
    if ($resUser) {
        $staffID = $resUser['staffID'];
        $_SESSION['staff_real_id'] = $staffID;
        $staffName = $resUser['staffName'];
    } else { $staffName = "Staff"; }
} else { $staffName = $_SESSION['user_name']; }

// --- CONFIG ---
date_default_timezone_set('Asia/Kuala_Lumpur');
$currentDay = date('l');        
$currentTime = date('H:i:s');   
$currentDate = date('Y-m-d');   

// --- AUTO-SYNC ATTENDANCE SESSIONS (GLOBAL) ---
// This runs on page load to keep attendance_sessions table up-to-date for ALL classes
function syncAllAttendanceSessions($db, $currentDay, $currentDate) {
    // 1. Auto-CREATE sessions for ALL regular classes that have started today (no session exists yet)
    $createSql = "INSERT INTO attendance_sessions (scheduleID, sessionDate, code, status)
                  SELECT s.scheduleID, CURDATE(), LPAD(FLOOR(RAND() * 1000000), 6, '0'), 'OPEN'
                  FROM class_schedule s
                  WHERE s.day = ?
                  AND CURTIME() >= s.startTime
                  AND NOT EXISTS (
                      SELECT 1 FROM attendance_sessions sess 
                      WHERE sess.scheduleID = s.scheduleID 
                      AND sess.sessionDate = CURDATE()
                  )
                  AND NOT EXISTS (
                      SELECT 1 FROM replacement_request rr
                      WHERE rr.scheduleID = s.scheduleID
                      AND rr.status = 'Approved'
                      AND rr.originalDate = CURDATE()
                  )";
    $stmtCreate = $db->prepare($createSql);
    $stmtCreate->bind_param("s", $currentDay);
    $stmtCreate->execute();
    
    // 1b. Auto-CREATE sessions for approved REPLACEMENT classes today (parse newTime for start time)
    $createReplSql = "INSERT INTO attendance_sessions (scheduleID, sessionDate, code, status)
                      SELECT rr.scheduleID, CURDATE(), LPAD(FLOOR(RAND() * 1000000), 6, '0'), 'OPEN'
                      FROM replacement_request rr
                      WHERE rr.newDate = ?
                      AND rr.status = 'Approved'
                      AND SUBSTRING_INDEX(rr.newTime, ' - ', 1) <= CURTIME()
                      AND NOT EXISTS (
                          SELECT 1 FROM attendance_sessions sess
                          WHERE sess.scheduleID = rr.scheduleID
                          AND sess.sessionDate = CURDATE()
                      )";
    $stmtCreateRepl = $db->prepare($createReplSql);
    $stmtCreateRepl->bind_param("s", $currentDate);
    $stmtCreateRepl->execute();
    
    // 2. Auto-CLOSE ALL sessions for regular classes that ended 15+ minutes ago
    $closeSql = "UPDATE attendance_sessions sess
                 JOIN class_schedule s ON sess.scheduleID = s.scheduleID
                 SET sess.status = 'CLOSED'
                 WHERE sess.sessionDate = CURDATE()
                 AND sess.status = 'OPEN'
                 AND CURTIME() > ADDTIME(s.endTime, '00:15:00')
                 AND NOT EXISTS (
                     SELECT 1 FROM replacement_request rr
                     WHERE rr.scheduleID = s.scheduleID
                     AND rr.newDate = CURDATE()
                     AND rr.status = 'Approved'
                 )";
    $stmtClose = $db->prepare($closeSql);
    $stmtClose->execute();
    
    // 2b. Auto-CLOSE sessions for replacement classes (parse endTime from newTime)
    $closeReplSql = "UPDATE attendance_sessions sess
                     JOIN replacement_request rr ON sess.scheduleID = rr.scheduleID
                     SET sess.status = 'CLOSED'
                     WHERE sess.sessionDate = CURDATE()
                     AND sess.status = 'OPEN'
                     AND rr.newDate = CURDATE()
                     AND rr.status = 'Approved'
                     AND CURTIME() > ADDTIME(SUBSTRING_INDEX(rr.newTime, ' - ', -1), '00:15:00')";
    $stmtCloseRepl = $db->prepare($closeReplSql);
    $stmtCloseRepl->execute();
}

// Run global sync on page load
syncAllAttendanceSessions($db, $currentDay, $currentDate);

// Fetch courses taught by this staff (for monthly report dropdown)
$coursesSql = "SELECT DISTINCT c.courseID, c.courseName 
               FROM class_schedule s 
               JOIN course c ON s.courseID = c.courseID 
               WHERE s.staffID = ? 
               ORDER BY c.courseName ASC";
$stmtCourses = $db->prepare($coursesSql);
$stmtCourses->bind_param("s", $staffID);
$stmtCourses->execute();
$staffCourses = $stmtCourses->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch course-group combinations for this staff (for group dropdown)
$courseGroupsSql = "SELECT DISTINCT s.courseID, s.tutGroup, s.programID, p.programName
                    FROM class_schedule s 
                    JOIN program p ON s.programID = p.programID
                    WHERE s.staffID = ? 
                    ORDER BY s.courseID, s.tutGroup ASC";
$stmtCG = $db->prepare($courseGroupsSql);
$stmtCG->bind_param("s", $staffID);
$stmtCG->execute();
$courseGroups = $stmtCG->get_result()->fetch_all(MYSQLI_ASSOC);

// 2. Fetch Today's Classes (Regular classes + Approved Replacement classes for today)
// Exclude regular classes that have been moved to another date
$sql = "(SELECT s.*, c.courseName, c.courseID, f.facilityName,
               sess.sessionID, sess.code, sess.status as sessionStatus,
               (SELECT COUNT(*) FROM attendance a WHERE a.scheduleID = s.scheduleID AND a.attendanceDate = ?) as joinedCount,
               (SELECT COUNT(*) FROM student st WHERE st.programID = s.programID AND (st.tutGroup = s.tutGroup OR s.tutGroup = 'Combined')) as totalStudents,
               'regular' as classSource
        FROM class_schedule s 
        JOIN course c ON s.courseID = c.courseID
        JOIN facility f ON s.facilityID = f.facilityID
        LEFT JOIN attendance_sessions sess ON s.scheduleID = sess.scheduleID AND sess.sessionDate = ?
        WHERE s.staffID = ? AND s.day = ?
        AND NOT EXISTS (
            SELECT 1 FROM replacement_request rr 
            WHERE rr.scheduleID = s.scheduleID 
            AND rr.status = 'Approved' 
            AND rr.originalDate = ?
        ))
        UNION ALL
        (SELECT s.*, c.courseName, c.courseID, f.facilityName,
               sess.sessionID, sess.code, sess.status as sessionStatus,
               (SELECT COUNT(*) FROM attendance a WHERE a.scheduleID = s.scheduleID AND a.attendanceDate = ?) as joinedCount,
               (SELECT COUNT(*) FROM student st WHERE st.programID = s.programID AND (st.tutGroup = s.tutGroup OR s.tutGroup = 'Combined')) as totalStudents,
               'replacement' as classSource
        FROM replacement_request rr
        JOIN class_schedule s ON rr.scheduleID = s.scheduleID
        JOIN course c ON s.courseID = c.courseID
        JOIN facility f ON rr.facilityID = f.facilityID
        LEFT JOIN attendance_sessions sess ON s.scheduleID = sess.scheduleID AND sess.sessionDate = ?
        WHERE s.staffID = ? AND rr.newDate = ? AND rr.status = 'Approved')
        ORDER BY startTime ASC";

$stmt = $db->prepare($sql);
$stmt->bind_param("sssssssss", $currentDate, $currentDate, $staffID, $currentDay, $currentDate, $currentDate, $currentDate, $staffID, $currentDate);
$stmt->execute();
$todaysClasses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Update replacement class times from newTime field
foreach ($todaysClasses as &$class) {
    if ($class['classSource'] === 'replacement') {
        // Find the replacement request to get newTime
        $rrStmt = $db->prepare("SELECT newTime FROM replacement_request WHERE scheduleID = ? AND newDate = ? AND status = 'Approved' LIMIT 1");
        $rrStmt->bind_param("is", $class['scheduleID'], $currentDate);
        $rrStmt->execute();
        $rrResult = $rrStmt->get_result()->fetch_assoc();
        if ($rrResult && $rrResult['newTime']) {
            // Parse newTime (format: "08:00 - 10:00")
            $timeParts = explode(' - ', $rrResult['newTime']);
            if (count($timeParts) === 2) {
                $class['startTime'] = $timeParts[0] . ':00';
                $class['endTime'] = $timeParts[1] . ':00';
            }
        }
    }
}
unset($class); // Break reference

// 3. Fetch History (Based on Filter)
// Two sources: attendance_sessions + today's classes with no session (if time passed)
$monthFilter = isset($_GET['month']) ? $_GET['month'] : '';

// Build the query with UNION to include classes without sessions
$historySql = "(SELECT sess.sessionID, sess.scheduleID, sess.sessionDate as attendanceDate, 
               s.startTime, s.endTime, s.tutGroup, c.courseName, c.courseID, s.programID,
               (SELECT COUNT(*) FROM attendance a2 WHERE a2.scheduleID = sess.scheduleID AND a2.attendanceDate = sess.sessionDate) as joinedCount,
               (SELECT COUNT(*) FROM student st WHERE st.programID = s.programID AND (st.tutGroup = s.tutGroup OR s.tutGroup = 'Combined')) as totalStudents
               FROM attendance_sessions sess
               JOIN class_schedule s ON sess.scheduleID = s.scheduleID
               JOIN course c ON s.courseID = c.courseID
               WHERE s.staffID = ?
               AND (
                   sess.status = 'CLOSED' 
                   OR (sess.sessionDate < CURDATE())
                   OR (sess.sessionDate = CURDATE() AND CURTIME() > ADDTIME(s.endTime, '00:15:00'))
               )";

if ($monthFilter) {
    $historySql .= " AND sess.sessionDate LIKE '" . $db->real_escape_string($monthFilter) . "%'";
}

$historySql .= ")
               UNION ALL
               (SELECT NULL as sessionID, s.scheduleID, CURDATE() as attendanceDate,
               s.startTime, s.endTime, s.tutGroup, c.courseName, c.courseID, s.programID,
               0 as joinedCount,
               (SELECT COUNT(*) FROM student st WHERE st.programID = s.programID AND (st.tutGroup = s.tutGroup OR s.tutGroup = 'Combined')) as totalStudents
               FROM class_schedule s
               JOIN course c ON s.courseID = c.courseID
               WHERE s.staffID = ?
               AND s.day = DAYNAME(CURDATE())
               AND CURTIME() > ADDTIME(s.endTime, '00:15:00')
               AND NOT EXISTS (
                   SELECT 1 FROM attendance_sessions sess 
                   WHERE sess.scheduleID = s.scheduleID 
                   AND sess.sessionDate = CURDATE()
               )";

if ($monthFilter) {
    // Only include today's no-session classes if filter matches current month
    $currentMonth = date('Y-m');
    if (strpos($currentMonth, $monthFilter) === 0) {
        $historySql .= ")";
    } else {
        $historySql .= " AND 1=0)"; // Exclude if filtering different month
    }
    $historySql .= " ORDER BY attendanceDate DESC, startTime DESC";
} else {
    $historySql .= ") ORDER BY attendanceDate DESC, startTime DESC LIMIT 5";
}

$stmtHist = $db->prepare($historySql);
$stmtHist->bind_param("ss", $staffID, $staffID);
$stmtHist->execute();
$historyList = $stmtHist->get_result()->fetch_all(MYSQLI_ASSOC);

// 4. Handle Actions (Start/Stop/Manual)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['export_csv'])) {
    if (isset($_POST['create_session'])) {
        $schedID = $_POST['scheduleID'];
        $code = rand(100000, 999999);
        $check = $db->query("SELECT * FROM attendance_sessions WHERE scheduleID='$schedID' AND sessionDate='$currentDate'");
        if ($check->num_rows == 0) {
            $ins = $db->prepare("INSERT INTO attendance_sessions (scheduleID, sessionDate, code) VALUES (?, ?, ?)");
            $ins->bind_param("iss", $schedID, $currentDate, $code);
            $ins->execute();
        }
        header("Refresh:0");
    }
    if (isset($_POST['stop_session'])) {
        $sessID = $_POST['sessionID'];
        $db->query("UPDATE attendance_sessions SET status='CLOSED' WHERE sessionID='$sessID'");
        header("Refresh:0");
    }
    if (isset($_POST['manual_attendance'])) {
        $studentID = $_POST['studentID']; $schedID = $_POST['scheduleID']; $status = $_POST['status']; 
        // Note: For history modal, we need to handle specific dates, not just currentDate. 
        // Passing date via POST is safest.
        $targetDate = $_POST['targetDate'] ?? $currentDate; 

        if ($status === 'Present') {
            $chk = $db->prepare("SELECT recordID FROM attendance WHERE studentID=? AND scheduleID=? AND attendanceDate=?");
            $chk->bind_param("sis", $studentID, $schedID, $targetDate);
            $chk->execute();
            if ($chk->get_result()->num_rows == 0) {
                // Staff manual marking = always 'Present' (Late logic only applies to student self-scanning)
                $ins = $db->prepare("INSERT INTO attendance (studentID, scheduleID, attendanceDate, scanTime, status) VALUES (?, ?, ?, ?, 'Present')");
                $ins->bind_param("siss", $studentID, $schedID, $targetDate, $currentTime);
                $ins->execute();
            }
        } elseif ($status === 'Absent') {
            $del = $db->prepare("DELETE FROM attendance WHERE studentID=? AND scheduleID=? AND attendanceDate=?");
            $del->bind_param("sis", $studentID, $schedID, $targetDate);
            $del->execute();
        }
        echo json_encode(['status' => 'success']); exit; 
    }
}

function getStudentList($db, $scheduleID, $date) {
    $stmtClass = $db->prepare("SELECT programID, tutGroup FROM class_schedule WHERE scheduleID = ?");
    $stmtClass->bind_param("i", $scheduleID);
    $stmtClass->execute();
    $classInfo = $stmtClass->get_result()->fetch_assoc();
    if (!$classInfo) return [];

    $sql = "SELECT s.studentName, s.studentID, s.studentImage, a.scanTime, COALESCE(a.status, 'Absent') as status
            FROM student s
            LEFT JOIN attendance a ON s.studentID = a.studentID AND a.scheduleID = ? AND a.attendanceDate = ?
            WHERE s.programID = ? AND (s.tutGroup = ? OR ? = 'Combined')
            ORDER BY status DESC, s.studentName ASC"; 
    $stmt = $db->prepare($sql);
    $stmt->bind_param("issss", $scheduleID, $date, $classInfo['programID'], $classInfo['tutGroup'], $classInfo['tutGroup']);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Attendance Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <style>
        /* Grid & Cards (Same as before) */
        .class-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 25px; margin-bottom: 50px; }
        .class-card { background: #fcfaff; border-radius: 24px; padding: 28px; box-shadow: 0 10px 40px rgba(200, 200, 230, 0.15); border: 1px solid #ebe5ff; transition: transform 0.2s, box-shadow 0.2s; position: relative; overflow: hidden; display: flex; flex-direction: column; }
        .class-card:hover { transform: translateY(-5px); box-shadow: 0 15px 50px rgba(128, 86, 255, 0.15); border-color: #dcd0ff; }
        .class-card.active { border-left: 6px solid #8056ff; background: #fff; }
        .class-card.completed { border-left: 6px solid #10b981; opacity: 0.9; }
        .class-card.upcoming { border-left: 6px solid #9ca3af; background: #fafafa; }
        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .time-badge { padding: 6px 14px; border-radius: 12px; font-size: 13px; font-weight: 700; color: #555; background: #f3f4f6; }
        .session-code { font-size: 36px; font-weight: 800; letter-spacing: 6px; margin: 20px 0; text-align: center; background: white; color: #6b45f5; padding: 15px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 2px solid #f3efff; }
        .card-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }

        /* --- HISTORY SECTION --- */
        .history-section { margin-top: 40px; background: white; border-radius: 24px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02); }
        .history-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .history-title { font-size: 20px; font-weight: 800; color: #1f2937; margin: 0; }
        
        .filter-actions { display: flex; align-items: center; gap: 12px; }
        .filter-form { display: flex; gap: 10px; align-items: center; }
        .month-select { padding: 8px 12px; border-radius: 10px; border: 1px solid #e5e7eb; font-size: 13px; outline: none; background: #f9fafb; }
        .btn-filter { background: #1f2937; color: white; border: none; padding: 8px 15px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .btn-export { background: #10b981; color: white; border: none; padding: 8px 15px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: opacity 0.2s; }

        .history-table { width: 100%; border-collapse: collapse; }
        .history-table th { text-align: left; font-size: 12px; color: #6b7280; font-weight: 600; padding: 15px; border-bottom: 1px solid #f3f4f6; }
        .history-table td { padding: 15px; border-bottom: 1px solid #f3f4f6; font-size: 14px; color: #374151; vertical-align: middle; }
        .history-table tr:last-child td { border-bottom: none; }
        
        .date-badge { background: #f3f4f6; padding: 4px 10px; border-radius: 8px; font-weight: 700; font-size: 12px; color: #4b5563; white-space: nowrap; }
        .attendance-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .pill-high { background: #ecfdf5; color: #059669; }
        .pill-med { background: #fffbeb; color: #d97706; }
        .pill-low { background: #fef2f2; color: #dc2626; }

        .empty-state { grid-column: 1 / -1; background: #ffffff; border-radius: 24px; padding: 60px 40px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 2px dashed #f0f0f0; margin: 20px 0; }
        .empty-icon-bg { width: 90px; height: 90px; background: #fdf2f8; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 25px; animation: float 6s ease-in-out infinite; }
        .empty-icon-bg i { font-size: 36px; color: #db2777; }
        .empty-state h3 { font-size: 20px; font-weight: 700; color: #1f2937; margin: 0 0 8px 0; }
        .empty-state p { font-size: 14px; color: #6b7280; line-height: 1.5; max-width: 300px; margin: 0; }
        @keyframes float { 0% {transform: translateY(0px);} 50% {transform: translateY(-10px);} 100% {transform: translateY(0px);} }

        /* Modal */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 30px; border-radius: 24px; width: 500px; max-width: 90%; max-height: 80vh; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="app-bg">
        <div class="main-card">
            <aside class="sidebar">
                <div class="sidebar-head">
                    <div class="brand-icon">CL</div>
                    <div class="brand-text"><span class="brand-name">CAMPUSLink</span><span class="brand-tagline">Staff Console</span></div>
                </div>
                <nav class="sidebar-nav">
                    <ul>
                        <li>
                            <a href="staff-dashboard.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-table-columns"></i></span>
                                <span class="nav-label">Dashboard</span>
                            </a>
                        </li>

                        <li class="nav-group is-open" data-expandable>
                            <button class="nav-item nav-toggle" type="button" data-target="student-menu" aria-expanded="true">
                                <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
                                <span class="nav-label">Student</span>
                                <span class="nav-chevron"></span>
                            </button>
                            <div id="student-menu" class="nav-submenu">
                                <a href="tutorial-management.php" class="nav-subitem">Tutorial Management</a>
                                <a href="staff-attendance.php" class="nav-subitem" style="background: rgba(255,255,255,0.1);">Attendance</a>
                                <a href="staff-assignments.php" class="nav-subitem">Assignment</a>
                            </div>
                        </li>

                        <li>
                            <a href="staff-timetable.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-calendar-week"></i></span>
                                <span class="nav-label">My Timetable</span>
                            </a>
                        </li>

                        <li>
                            <a href="resource-booking.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-building"></i></span>
                                <span class="nav-label">Resource Booking</span>
                            </a>
                        </li>

                        <li>
                            <a href="../logout.php" class="nav-item" style="margin-top: 20px;">
                                <span class="nav-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                                <span class="nav-label" style="color: #e74c3c;">Logout</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </aside>

            <main class="dashboard">
                <header class="dashboard-topbar">
                    <div class="topbar-right">
                        <div class="user-card">
                            <div class="user-info">
                                <span class="user-name"><?php echo htmlspecialchars($staffName); ?></span>
                                <span class="user-role">Staff</span>
                            </div>
                            <div class="profile-pic">
                                <i class="fa-solid fa-user"></i>
                            </div>
                        </div>
                    </div>
                </header>

                <div class="page-header" style="margin-bottom: 24px;">
                    <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main); margin: 0;">Attendance Manager</h1>
                    <p style="color: var(--text-light); margin: 4px 0 0 0;"><?php echo date('l, d F Y'); ?></p>
                </div>

                <div class="class-grid">
                    <?php if (empty($todaysClasses)): ?>
                        <div class="empty-state">
                            <div class="empty-icon-bg"><i class="fa-solid fa-calendar-check"></i></div>
                            <h3>No Classes Today</h3>
                            <p>You have a free day! No classes are scheduled for <?php echo $currentDay; ?>.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($todaysClasses as $cls): 
                            $startTime = substr($cls['startTime'], 0, 5); $endTime = substr($cls['endTime'], 0, 5);
                            $isActive = ($currentTime >= $cls['startTime'] && $currentTime <= $cls['endTime']);
                            $isPast = ($currentTime > $cls['endTime']);
                            if ($cls['sessionStatus'] == 'CLOSED') { $isActive = false; $cardClass = 'completed'; }
                            else { $cardClass = $isActive ? 'active' : ($isPast ? 'completed' : 'upcoming'); }
                        ?>
                        <article class="class-card <?php echo $cardClass; ?>">
                            <div class="card-header">
                                <span class="time-badge"><i class="fa-regular fa-clock"></i> <?php echo $startTime . ' - ' . $endTime; ?></span>
                                <?php if ($isActive): ?> <span style="color: #8056ff; font-weight: 700; font-size: 12px; background: #f3efff; padding: 4px 10px; border-radius: 10px;">LIVE NOW</span>
                                <?php elseif ($cardClass == 'completed'): ?> <span style="color: #10b981; font-weight: 700; font-size: 12px;"><i class="fa-solid fa-check"></i> Done</span>
                                <?php endif; ?>
                            </div>
                            <h3 style="margin: 0 0 5px; font-size: 18px; color: #1f2937;"><?php echo $cls['courseName']; ?></h3>
                            <p class="text-muted" style="margin: 0; font-size: 13px; color: #6b7280;"><?php echo $cls['courseID']; ?> • <?php echo $cls['tutGroup']; ?></p>
                            <?php if ($isActive && $cls['code']): ?>
                                <div class="session-code"><?php echo $cls['code']; ?></div>
                                <div class="card-footer">
                                    <span><i class="fa-solid fa-users"></i> <strong><?php echo $cls['joinedCount']; ?></strong> Joined</span>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="sessionID" value="<?php echo $cls['sessionID']; ?>">
                                        <button type="submit" name="stop_session" class="btn" style="background: #fee2e2; color:#991b1b; padding: 8px 15px;">Stop</button>
                                    </form>
                                </div>
                            <?php elseif ($isActive && !$cls['code']): ?>
                                <div class="card-footer" style="justify-content: center;">
                                    <form method="POST">
                                        <input type="hidden" name="scheduleID" value="<?php echo $cls['scheduleID']; ?>">
                                        <button type="submit" name="create_session" class="btn" style="width: 100%;">Start Attendance</button>
                                    </form>
                                </div>
                            <?php elseif ($cardClass == 'completed'): ?>
                                <div class="card-footer">
                                    <span style="font-size: 13px; color: #6b7280;">Total Present: <strong><?php echo $cls['joinedCount']; ?></strong></span>
                                    <button class="btn" style="background: #f3f4f6; color: #374151; padding: 8px 15px; font-size: 12px;" 
                                            onclick='openModal(<?php echo json_encode(getStudentList($db, $cls["scheduleID"], $currentDate)); ?>, "<?php echo $cls["courseName"]; ?>", <?php echo $cls["scheduleID"]; ?>)'>
                                        View List
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="card-footer" style="justify-content: center; opacity: 0.6;">
                                    <span style="font-size: 13px; font-weight: 600;">Starts at <?php echo $startTime; ?></span>
                                </div>
                            <?php endif; ?>
                        </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="history-section">
                    <div class="history-header">
                        <h2 class="history-title">Class History</h2>
                        <div class="filter-actions">
                            <form method="GET" class="filter-form">
                                <select name="month" class="month-select" onchange="this.form.submit()">
                                    <option value="">Recent (Last 5)</option>
                                    <option value="2026-01" <?php if($monthFilter == '2026-01') echo 'selected'; ?>>January 2026</option>
                                    <option value="2026-02" <?php if($monthFilter == '2026-02') echo 'selected'; ?>>February 2026</option>
                                    <option value="2026-03" <?php if($monthFilter == '2026-03') echo 'selected'; ?>>March 2026</option>
                                </select>
                            </form>
                            <div class="report-controls" style="display: flex; gap: 8px; align-items: center;">
                                <select id="reportCourse" class="month-select" style="min-width: 140px;" <?php if(!$monthFilter) echo 'disabled'; ?> onchange="updateGroupDropdown(); filterHistoryTable();">
                                    <option value="">All Courses</option>
                                    <?php foreach($staffCourses as $course): ?>
                                    <option value="<?php echo $course['courseID']; ?>"><?php echo $course['courseID']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select id="reportGroup" class="month-select" style="min-width: 120px;" <?php if(!$monthFilter) echo 'disabled'; ?> onchange="filterHistoryTable()">
                                    <option value="">All Groups</option>
                                </select>
                                <button type="button" onclick="generateMonthlyReport()" class="btn-export" <?php if(!$monthFilter) echo 'disabled style="opacity:0.5; cursor:not-allowed;"'; ?>>
                                    <i class="fa-solid fa-file-pdf"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($historyList)): ?>
                        <p style="color:#6b7280; font-style:italic;">No records found.</p>
                    <?php else: ?>
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Course</th>
                                    <th>Group</th>
                                    <th>Attendance</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historyList as $hist): 
                                    $joined = $hist['joinedCount'];
                                    $total = $hist['totalStudents'] > 0 ? $hist['totalStudents'] : 1;
                                    $percent = round(($joined / $total) * 100);
                                    
                                    $pillClass = 'pill-low';
                                    if($percent >= 80) $pillClass = 'pill-high';
                                    elseif($percent >= 50) $pillClass = 'pill-med';
                                ?>
                                <tr data-course="<?php echo $hist['courseID']; ?>" data-group="<?php echo $hist['tutGroup']; ?>" data-program="<?php echo $hist['programID']; ?>">
                                    <td><span class="date-badge"><?php echo date('d M Y', strtotime($hist['attendanceDate'])); ?></span></td>
                                    <td>
                                        <div style="font-weight:700;"><?php echo $hist['courseName']; ?></div>
                                        <div style="font-size:12px; color:#9ca3af;"><?php echo date('H:i', strtotime($hist['startTime'])); ?> - <?php echo date('H:i', strtotime($hist['endTime'])); ?></div>
                                    </td>
                                    <td><?php echo $hist['tutGroup']; ?></td>
                                    <td>
                                        <span class="attendance-pill <?php echo $pillClass; ?>">
                                            <?php echo $percent; ?>% (<?php echo $joined; ?>/<?php echo $hist['totalStudents']; ?>)
                                        </span>
                                    </td>
                                    <td style="display: flex; gap: 8px; align-items: center;">
                                        <button style="border:none; background:transparent; color:#6366f1; font-weight:600; cursor:pointer; font-size:13px;"
                                                onclick='openModal(<?php echo json_encode(getStudentList($db, $hist["scheduleID"], $hist["attendanceDate"])); ?>, "<?php echo $hist["courseName"]; ?>", <?php echo $hist["scheduleID"]; ?>, "<?php echo $hist["attendanceDate"]; ?>")'>
                                            View List
                                        </button>
                                        <a href="attendance-report.php?scheduleID=<?php echo $hist['scheduleID']; ?>&date=<?php echo $hist['attendanceDate']; ?>" 
                                           target="_blank" 
                                           style="color: #dc2626; font-size: 16px;" 
                                           title="View PDF Report">
                                            <i class="fa-solid fa-file-pdf"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p id="noFilteredRecords" style="color:#6b7280; font-style:italic; display:none; text-align:center; padding: 20px;">No records match the selected filter.</p>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <div id="listModal" class="modal">
        <div class="modal-content">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <h3 id="modalTitle" style="margin:0;">Attendance List</h3>
                <span onclick="document.getElementById('listModal').style.display='none'" style="cursor:pointer; font-size:20px;">&times;</span>
            </div>
            <div id="studentListContainer"></div>
        </div>
    </div>

    <script>
        let currentScheduleID = null;
        let currentTargetDate = null;

        function openModal(students, courseName, scheduleID, dateStr = '<?php echo $currentDate; ?>') {
            currentScheduleID = scheduleID;
            currentTargetDate = dateStr;
            
            const container = document.getElementById('studentListContainer');
            const title = document.getElementById('modalTitle');
            const total = students.length;

            const presentCount = students.filter(s => s.status === 'Present').length;
            const lateCount = students.filter(s => s.status === 'Late').length;
            const attended = presentCount + lateCount; 
            const absent = total - attended;

            // Header Stats
            let statsHTML = `<span style="color:#059669;">Present: ${presentCount}</span>`;
            if (lateCount > 0) statsHTML += ` • <span style="color:#d97706;">Late: ${lateCount}</span>`;
            statsHTML += ` • <span style="color:#dc2626;">Absent: ${absent}</span>`;

            title.innerHTML = `
                <div style="display:flex; flex-direction:column; gap:4px;">
                    <span>${courseName}</span>
                    <span style="font-size:12px; color:#6b7280; font-weight:500;">
                        Total: ${total} • ${statsHTML}
                    </span>
                </div>`;
            
            container.innerHTML = '';

            if (students.length === 0) container.innerHTML = '<p style="text-align:center;">No students.</p>';
            else students.forEach(s => {
                let actionHTML = '', statusBadge = '', timeDisplay = '';
                
                // 1. FORMAT TIME (e.g., "14:05:00" -> "2:05 PM")
                if (s.scanTime) {
                    // Create a dummy date to parse the time string
                    const [hours, minutes] = s.scanTime.split(':');
                    const h = parseInt(hours, 10);
                    const ampm = h >= 12 ? 'PM' : 'AM';
                    const h12 = h % 12 || 12;
                    timeDisplay = `<div style="font-size:11px; color:#6b7280; margin-top:3px; font-weight:500;"><i class="fa-regular fa-clock" style="font-size:10px; margin-right:4px;"></i>${h12}:${minutes} ${ampm}</div>`;
                } else {
                    timeDisplay = `<div style="font-size:11px; color:#d1d5db; margin-top:3px;">-- : --</div>`;
                }

                // 2. STATUS LOGIC
                if (s.status === 'Absent') {
                    statusBadge = `<span style="background:#fee2e2; color:#991b1b; padding:4px 10px; border-radius:12px; font-size:11px; font-weight:700;">ABSENT</span>`;
                    actionHTML = `<button onclick="toggleAttendance('${s.studentID}', 'Present', this)" style="background:#8056ff; color:white; border:none; padding:5px 10px; border-radius:6px; font-size:11px; cursor:pointer; margin-left:10px;">Mark Present</button>`;
                } else if (s.status === 'Late') {
                    statusBadge = `<span style="background:#fffbeb; color:#d97706; padding:4px 10px; border-radius:12px; font-size:11px; font-weight:700;">LATE</span>`;
                    actionHTML = `<button onclick="toggleAttendance('${s.studentID}', 'Absent', this)" style="background:transparent; color:#991b1b; border:1px solid #fee2e2; padding:4px 8px; border-radius:6px; font-size:10px; cursor:pointer; margin-left:10px;">Undo</button>`;
                } else {
                    statusBadge = `<span style="background:#d1fae5; color:#065f46; padding:4px 10px; border-radius:12px; font-size:11px; font-weight:700;">PRESENT</span>`;
                    actionHTML = `<button onclick="toggleAttendance('${s.studentID}', 'Absent', this)" style="background:transparent; color:#991b1b; border:1px solid #fee2e2; padding:4px 8px; border-radius:6px; font-size:10px; cursor:pointer; margin-left:10px;">Undo</button>`;
                }

                // Student avatar (photo or fallback to user icon)
                const hasPhoto = s.studentImage && s.studentImage.trim() !== '' && s.studentImage !== 'null';
                const avatarContent = hasPhoto 
                    ? `<img src="../uploads/profiles/${s.studentImage}" style="width:36px; height:36px; border-radius:50%; object-fit:cover;" alt="${s.studentName}" onerror="this.outerHTML='<div style=\\'width:36px; height:36px; background:#f3f4f6; border-radius:50%; display:grid; place-items:center; color:#9ca3af; font-size:14px;\\'><i class=\\'fa-solid fa-user\\'></i></div>'">` 
                    : `<div style="width:36px; height:36px; background:#f3f4f6; border-radius:50%; display:grid; place-items:center; color:#9ca3af; font-size:14px;"><i class="fa-solid fa-user"></i></div>`;

                const row = `
                    <div class="student-row" style="display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-bottom:1px solid #f3f4f6;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            ${avatarContent}
                            <div>
                                <div style="font-weight:600; color:#1f2937; font-size:14px;">${s.studentName}</div>
                                <div style="font-size:12px; color:#9ca3af;">${s.studentID}</div>
                            </div>
                        </div>
                        <div style="text-align:right; display:flex; align-items:center; gap:10px;">
                            <div style="text-align:right; display:flex; flex-direction:column; align-items:flex-end;">
                                ${statusBadge}
                                ${timeDisplay}
                            </div>
                            ${actionHTML}
                        </div>
                    </div>`;
                container.innerHTML += row;
            });
            document.getElementById('listModal').style.display = 'flex';
        }

        function toggleAttendance(studentID, status, btnElement) {
            btnElement.textContent = "..."; btnElement.disabled = true;
            const formData = new FormData();
            formData.append('manual_attendance', true); 
            formData.append('studentID', studentID);
            formData.append('scheduleID', currentScheduleID); 
            formData.append('status', status);
            formData.append('targetDate', currentTargetDate);

            fetch('staff-attendance.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => { if(d.status==='success') location.reload(); });
        }
        
        function generateMonthlyReport() {
            const month = document.querySelector('.month-select[name="month"]')?.value || 
                          new URLSearchParams(window.location.search).get('month');
            const courseID = document.getElementById('reportCourse').value;
            const group = document.getElementById('reportGroup').value;
            
            if (!month) {
                alert('Please select a month first.');
                return;
            }
            if (!courseID) {
                alert('Please select a course to generate the report.');
                return;
            }
            if (!group) {
                alert('Please select a group to generate the report.');
                return;
            }
            
            // Parse group value (format: "programID|tutGroup")
            const [programID, tutGroup] = group.split('|');
            window.open(`attendance-monthly-report.php?month=${month}&courseID=${courseID}&programID=${encodeURIComponent(programID)}&group=${encodeURIComponent(tutGroup)}`, '_blank');
        }
        
        // Course-Group data for dynamic dropdown
        const courseGroupData = <?php echo json_encode($courseGroups); ?>;
        
        function updateGroupDropdown() {
            const courseID = document.getElementById('reportCourse').value;
            const groupSelect = document.getElementById('reportGroup');
            
            // Clear existing options
            groupSelect.innerHTML = '<option value="">All Groups</option>';
            
            if (!courseID) return;
            
            // Filter groups for selected course
            const groups = courseGroupData.filter(cg => cg.courseID === courseID);
            
            groups.forEach(g => {
                const option = document.createElement('option');
                option.value = `${g.programID}|${g.tutGroup}`;
                option.textContent = `${g.tutGroup} (${g.programID})`;
                groupSelect.appendChild(option);
            });
        }
        
        function filterHistoryTable() {
            const courseID = document.getElementById('reportCourse').value;
            const groupValue = document.getElementById('reportGroup').value;
            
            let programID = '';
            let tutGroup = '';
            if (groupValue) {
                [programID, tutGroup] = groupValue.split('|');
            }
            
            const rows = document.querySelectorAll('.history-table tbody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const rowCourse = row.dataset.course;
                const rowGroup = row.dataset.group;
                const rowProgram = row.dataset.program;
                
                let show = true;
                
                // Filter by course
                if (courseID && rowCourse !== courseID) {
                    show = false;
                }
                
                // Filter by group (must match both program and group)
                if (groupValue && (rowProgram !== programID || rowGroup !== tutGroup)) {
                    show = false;
                }
                
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });
            
            // Show/hide "no records" message
            const noRecordsMsg = document.getElementById('noFilteredRecords');
            if (noRecordsMsg) {
                noRecordsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        }
        
        window.onclick = function(e) { if (e.target == document.getElementById('listModal')) document.getElementById('listModal').style.display = 'none'; }
    </script>
    <script src="../script.js"></script>
</body>
</html>