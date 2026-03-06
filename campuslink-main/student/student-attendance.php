<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/activity-logger.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}
prevent_back_button_cache();

$db = get_db_connection();
$studentID = $_SESSION['user_id'];
date_default_timezone_set('Asia/Kuala_Lumpur');
$currentDay = date('l'); $currentTime = date('H:i:s'); $currentDate = date('Y-m-d');

// 1. Get Student Details (Program, Year, Semester)
$stmtS = $db->prepare("SELECT programID, tutGroup, currentYear, currentSemester, studentImage FROM student WHERE studentID = ?");
$stmtS->bind_param("s", $studentID);
$stmtS->execute();
$sData = $stmtS->get_result()->fetch_assoc();
$studentImage = $sData['studentImage'] ?? null;

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
    
    // 1b. Auto-CREATE sessions for approved REPLACEMENT classes today
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
    
    // 2b. Auto-CLOSE sessions for replacement classes
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

// 2. Fetch TODAY'S Classes (Regular + Replacement) - Strict Filter by Year/Sem
$sql = "(SELECT s.*, c.courseName, c.courseID, st.staffName, f.facilityName,
       (SELECT COUNT(*) FROM attendance a WHERE a.scheduleID = s.scheduleID AND a.studentID = ? AND a.attendanceDate = ?) as isAttended,
       COALESCE((SELECT sess.status FROM attendance_sessions sess WHERE sess.scheduleID = s.scheduleID AND sess.sessionDate = ? LIMIT 1), 'NONE') as sessionStatus,
       'regular' as classSource
       FROM class_schedule s
       JOIN course c ON s.courseID = c.courseID
       JOIN staff st ON s.staffID = st.staffID
       JOIN facility f ON s.facilityID = f.facilityID
       JOIN program_course pc ON s.courseID = pc.courseID AND s.programID = pc.programID
       WHERE s.programID = ? 
       AND (s.tutGroup = ? OR s.tutGroup = 'Combined')
       AND s.day = ?
       AND pc.year = ? 
       AND pc.semester = ?
       AND NOT EXISTS (
           SELECT 1 FROM replacement_request rr 
           WHERE rr.scheduleID = s.scheduleID 
           AND rr.status = 'Approved' 
           AND rr.originalDate = ?
       ))
       UNION ALL
       (SELECT s.*, c.courseName, c.courseID, st.staffName, f.facilityName,
       (SELECT COUNT(*) FROM attendance a WHERE a.scheduleID = s.scheduleID AND a.studentID = ? AND a.attendanceDate = ?) as isAttended,
       COALESCE((SELECT sess.status FROM attendance_sessions sess WHERE sess.scheduleID = s.scheduleID AND sess.sessionDate = ? LIMIT 1), 'NONE') as sessionStatus,
       'replacement' as classSource
       FROM replacement_request rr
       JOIN class_schedule s ON rr.scheduleID = s.scheduleID
       JOIN course c ON s.courseID = c.courseID
       JOIN staff st ON s.staffID = st.staffID
       JOIN facility f ON rr.facilityID = f.facilityID
       JOIN program_course pc ON s.courseID = pc.courseID AND s.programID = pc.programID
       WHERE s.programID = ? 
       AND (s.tutGroup = ? OR s.tutGroup = 'Combined')
       AND rr.newDate = ?
       AND rr.status = 'Approved'
       AND pc.year = ? 
       AND pc.semester = ?)
       ORDER BY startTime ASC";

$stmt = $db->prepare($sql);
$stmt->bind_param("ssssssiisssssssii", $studentID, $currentDate, $currentDate, $sData['programID'], $sData['tutGroup'], $currentDay, $sData['currentYear'], $sData['currentSemester'], $currentDate, $studentID, $currentDate, $currentDate, $sData['programID'], $sData['tutGroup'], $currentDate, $sData['currentYear'], $sData['currentSemester']);
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

// 3. Handle Attendance Submission
// Generate CSRF token for the attendance form
if (!isset($_SESSION['attend_csrf'])) {
    $_SESSION['attend_csrf'] = bin2hex(random_bytes(32));
}

$msg = ""; $msgType = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scheduleID'])) {

    // CSRF validation
    if (!isset($_POST['attend_csrf']) || $_POST['attend_csrf'] !== $_SESSION['attend_csrf']) {
        $msg = "Security validation failed. Please reload the page and try again.";
        $msgType = "error";
    } else {
        $schedID = $_POST['scheduleID'];
        $code = implode('', $_POST['digit']);

        // Brute-force guard: max 5 wrong attempts per class, 5-minute lockout
        $failKey = 'attend_fail_' . $schedID;
        $failData = $_SESSION[$failKey] ?? ['count' => 0, 'until' => 0];
        if (time() < $failData['until']) {
            $waitMins = (int)ceil(($failData['until'] - time()) / 60);
            $msg = "Too many failed attempts. Please wait {$waitMins} minute(s) before trying again.";
            $msgType = "error";
        } else {
            // Reset stale lockout automatically when timer has expired
            if ($failData['until'] > 0) { $failData = ['count' => 0, 'until' => 0]; }

            $check = $db->prepare("SELECT recordID FROM attendance WHERE studentID = ? AND scheduleID = ? AND attendanceDate = ?");
            $check->bind_param("sis", $studentID, $schedID, $currentDate);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) { $msg = "Already attended."; $msgType = "success"; } else {
                $sess = $db->prepare("SELECT * FROM attendance_sessions WHERE scheduleID=? AND sessionDate=? AND code=? AND status='OPEN'");
                $sess->bind_param("iss", $schedID, $currentDate, $code);
                $sess->execute();

                if ($sess->get_result()->fetch_assoc()) {
                    // Success — reset fail counter
                    unset($_SESSION[$failKey]);

                    // LATE LOGIC - Check if replacement class or regular class
                    $rrCheck = $db->prepare("SELECT newTime FROM replacement_request WHERE scheduleID = ? AND newDate = ? AND status = 'Approved' LIMIT 1");
                    $rrCheck->bind_param("is", $schedID, $currentDate);
                    $rrCheck->execute();
                    $replData = $rrCheck->get_result()->fetch_assoc();

                    $startTime = null;
                    if ($replData && $replData['newTime']) {
                        // Replacement class - parse newTime
                        $timeParts = explode(' - ', $replData['newTime']);
                        if (count($timeParts) === 2) {
                            $startTime = $timeParts[0] . ':00';
                        }
                    } else {
                        // Regular class
                        $qTime = $db->prepare("SELECT startTime FROM class_schedule WHERE scheduleID = ?");
                        $qTime->bind_param("i", $schedID);
                        $qTime->execute();
                        $classData = $qTime->get_result()->fetch_assoc();
                        if ($classData) {
                            $startTime = $classData['startTime'];
                        }
                    }

                    $status = 'Present';
                    if ($startTime) {
                        $startTs = strtotime($currentDate . ' ' . $startTime);
                        $scanTs = strtotime($currentDate . ' ' . $currentTime);
                        if ($scanTs > ($startTs + (15 * 60))) { $status = 'Late'; }
                    }

                    $ins = $db->prepare("INSERT INTO attendance (studentID, scheduleID, attendanceDate, scanTime, status) VALUES (?, ?, ?, ?, ?)");
                    $ins->bind_param("sisss", $studentID, $schedID, $currentDate, $currentTime, $status);
                    $ins->execute();

                    // Log attendance activity
                    $courseStmt = $db->prepare("SELECT c.courseID, c.courseName FROM class_schedule cs JOIN course c ON cs.courseID = c.courseID WHERE cs.scheduleID = ?");
                    $courseStmt->bind_param("i", $schedID);
                    $courseStmt->execute();
                    $courseInfo = $courseStmt->get_result()->fetch_assoc();
                    $courseStmt->close();
                    $courseName = $courseInfo ? $courseInfo['courseName'] . ' (' . $courseInfo['courseID'] . ')' : 'Unknown Course';
                    $logUserID = $_SESSION['actual_user_id'] ?? $studentID;
                    logActivity($db, $logUserID, 'ATTENDANCE', "Student took attendance for $courseName - Status: $status", [
                        'scheduleID' => $schedID,
                        'courseID' => $courseInfo['courseID'] ?? '',
                        'courseName' => $courseInfo['courseName'] ?? '',
                        'date' => $currentDate,
                        'scanTime' => $currentTime,
                        'status' => $status
                    ]);

                    header("Refresh:0");
                } else {
                    // Increment fail counter on wrong code
                    $failData['count']++;
                    if ($failData['count'] >= 5) {
                        $failData['until'] = time() + (5 * 60);
                        $failData['count'] = 0;
                        $_SESSION[$failKey] = $failData;
                        $msg = "Too many failed attempts. Please wait 5 minutes before trying again.";
                    } else {
                        $_SESSION[$failKey] = $failData;
                        $remaining = 5 - $failData['count'];
                        $msg = "Invalid or expired code. {$remaining} attempt(s) remaining.";
                    }
                    $msgType = "error";
                }
            }
        }
    }
}

// 4. Fetch COURSE SUMMARY (The "My Subjects" Section)
// This query gets every subject the student is enrolled in and calculates stats.
$courseSql = "SELECT pc.courseID, c.courseName, c.creditHours
              FROM program_course pc
              JOIN course c ON pc.courseID = c.courseID
              WHERE pc.programID = ? AND pc.year = ? AND pc.semester = ?";
$stmtC = $db->prepare($courseSql);
$stmtC->bind_param("sii", $sData['programID'], $sData['currentYear'], $sData['currentSemester']);
$stmtC->execute();
$myCourses = $stmtC->get_result()->fetch_all(MYSQLI_ASSOC);

// Helper function to get stats for a course
function getCourseStats($db, $studentID, $courseID, $programID, $tutGroup) {
    // 1. Total Classes Conducted - Two sources:
    //    A) Sessions in attendance_sessions (CLOSED or past end time)
    //    B) Today's scheduled classes that ended 15+ min ago (even if no session created)
    
    // Count A: From attendance_sessions
    $qSessionTotal = "SELECT COUNT(*) as total 
               FROM attendance_sessions sess
               JOIN class_schedule s ON sess.scheduleID = s.scheduleID
               WHERE s.courseID = ? AND s.programID = ? 
               AND (s.tutGroup = ? OR s.tutGroup = 'Combined')
               AND (
                   sess.status = 'CLOSED' 
                   OR (sess.sessionDate < CURDATE())
                   OR (sess.sessionDate = CURDATE() AND CURTIME() > ADDTIME(s.endTime, '00:15:00'))
               )";
    
    $stmt = $db->prepare($qSessionTotal);
    $stmt->bind_param("sss", $courseID, $programID, $tutGroup);
    $stmt->execute();
    $sessionTotal = $stmt->get_result()->fetch_assoc()['total'];
    
    // Count B: Today's classes with NO session record that ended 15+ min ago
    $qTodayNoSession = "SELECT COUNT(*) as total
                        FROM class_schedule s
                        WHERE s.courseID = ? AND s.programID = ?
                        AND (s.tutGroup = ? OR s.tutGroup = 'Combined')
                        AND s.day = DAYNAME(CURDATE())
                        AND CURTIME() > ADDTIME(s.endTime, '00:15:00')
                        AND NOT EXISTS (
                            SELECT 1 FROM attendance_sessions sess 
                            WHERE sess.scheduleID = s.scheduleID 
                            AND sess.sessionDate = CURDATE()
                        )";
    
    $stmt = $db->prepare($qTodayNoSession);
    $stmt->bind_param("sss", $courseID, $programID, $tutGroup);
    $stmt->execute();
    $todayNoSession = $stmt->get_result()->fetch_assoc()['total'];
    
    $total = $sessionTotal + $todayNoSession;

    // 2. Total Attended (Present or Late)
    $qAttended = "SELECT COUNT(*) as attended
                  FROM attendance a
                  JOIN class_schedule s ON a.scheduleID = s.scheduleID
                  WHERE a.studentID = ? AND s.courseID = ?
                  AND (a.status = 'Present' OR a.status = 'Late')";
    
    $stmt = $db->prepare($qAttended);
    $stmt->bind_param("ss", $studentID, $courseID);
    $stmt->execute();
    $attended = $stmt->get_result()->fetch_assoc()['attended'];

    // 3. Calculate missed classes
    $missed = $total - $attended;

    return ['total' => $total, 'attended' => $attended, 'missed' => $missed];
}

// API Endpoint for fetching history (AJAX) - Now shows ALL classes including missed
if (isset($_GET['get_history']) && isset($_GET['courseID'])) {
    // Clear any output buffer from prevent_back_button_cache() so JSON isn't corrupted
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
    $cID = $_GET['courseID'];
    
    // Get conducted sessions from attendance_sessions (CLOSED or past end time)
    // UNION with today's classes that have no session created
    $histSql = "SELECT 
                    sess.sessionDate as attendanceDate,
                    s.classType,
                    s.startTime,
                    s.endTime,
                    s.scheduleID,
                    COALESCE(a.status, 'Absent') as status,
                    a.scanTime
                FROM attendance_sessions sess
                JOIN class_schedule s ON sess.scheduleID = s.scheduleID
                LEFT JOIN attendance a ON sess.scheduleID = a.scheduleID 
                    AND sess.sessionDate = a.attendanceDate 
                    AND a.studentID = ?
                WHERE s.courseID = ? 
                AND s.programID = ?
                AND (s.tutGroup = ? OR s.tutGroup = 'Combined')
                AND (
                    sess.status = 'CLOSED' 
                    OR (sess.sessionDate < CURDATE())
                    OR (sess.sessionDate = CURDATE() AND CURTIME() > ADDTIME(s.endTime, '00:15:00'))
                )
                
                UNION ALL
                
                SELECT 
                    CURDATE() as attendanceDate,
                    s.classType,
                    s.startTime,
                    s.endTime,
                    s.scheduleID,
                    'Absent' as status,
                    NULL as scanTime
                FROM class_schedule s
                WHERE s.courseID = ?
                AND s.programID = ?
                AND (s.tutGroup = ? OR s.tutGroup = 'Combined')
                AND s.day = DAYNAME(CURDATE())
                AND CURTIME() > ADDTIME(s.endTime, '00:15:00')
                AND NOT EXISTS (
                    SELECT 1 FROM attendance_sessions sess 
                    WHERE sess.scheduleID = s.scheduleID 
                    AND sess.sessionDate = CURDATE()
                )
                
                ORDER BY attendanceDate DESC, startTime DESC";
    
    $stmtH = $db->prepare($histSql);
    $stmtH->bind_param("sssssss", $studentID, $cID, $sData['programID'], $sData['tutGroup'], $cID, $sData['programID'], $sData['tutGroup']);
    $stmtH->execute();
    echo json_encode($stmtH->get_result()->fetch_all(MYSQLI_ASSOC));
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Classes — CAMPUSLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <style>
        /* Modern Grid Layout */
        .class-list { max-width: 100%; display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .class-card {
            background: #fcfaff; border-radius: 24px; padding: 28px; box-shadow: 0 10px 40px rgba(200, 200, 230, 0.15); border: 1px solid #ebe5ff;
            transition: transform 0.2s, box-shadow 0.2s; position: relative; overflow: hidden; display: flex; flex-direction: column; gap: 15px;
        }
        .class-card:hover { transform: translateY(-5px); box-shadow: 0 15px 50px rgba(128, 86, 255, 0.15); border-color: #dcd0ff; }
        .class-card.attended { border-left: 6px solid #10b981; opacity: 0.95; }
        .class-card.live { border-left: 6px solid #8056ff; background: white; box-shadow: 0 10px 30px rgba(107, 69, 245, 0.2); }
        .class-card.upcoming { border-left: 6px solid #9ca3af; background: #fafafa; }
        .class-card.missed { border-left: 6px solid #ef4444; opacity: 0.85; }
        .class-card.stopped { border-left: 6px solid #9ca3af; background: #f9fafb; opacity: 0.95; }
        
        .card-top { display: flex; justify-content: space-between; align-items: center; }
        .time-badge { background: #f3f4f6; padding: 8px 14px; border-radius: 12px; font-weight: 700; font-size: 13px; color: #555; }
        .status-badge { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
        .st-attended { background: #d1fae5; color: #065f46; }
        .st-live { background: #f3efff; color: #7c3aed; }
        .st-upcoming { background: #f3f4f6; color: #6b7280; }
        .st-missed { background: #fee2e2; color: #991b1b; }
        .st-stopped { background: #f3f4f6; color: #6b7280; }

        .code-input-area { background: #faf9ff; padding: 24px 20px; border-radius: 16px; text-align: center; border: 2px dashed #e0d6ff; margin-top: 14px; }
        .input-group { display: flex; justify-content: center; gap: 10px; margin-bottom: 18px; }
        .digit-box { width: 42px; height: 50px; font-size: 22px; text-align: center; border: 2px solid #e5e7eb; border-radius: 12px; font-weight: 700; outline: none; background: #fff; transition: all 0.2s ease; }
        .digit-box:focus { border-color: #8056ff; box-shadow: 0 0 0 3px rgba(128,86,255,0.15); background: #faf8ff; }
        .digit-box:hover { border-color: #c4b5fd; }
        .btn-submit { background: #8056ff; color: white; border: none; padding: 12px 28px; border-radius: 12px; font-weight: 700; cursor: pointer; width: 100%; max-width: 220px; transition: all 0.2s; }
        .btn-submit:hover { background: #6b45f5; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(128,86,255,0.3); }
        
        /* Toast Notification Styles */
        .toast-container { position: fixed; bottom: 30px; right: 30px; z-index: 9999; }
        .toast { display: flex; align-items: center; gap: 12px; padding: 16px 24px; border-radius: 14px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); font-weight: 600; font-size: 14px; animation: slideInUp 0.4s ease, fadeOut 0.4s ease 4s forwards; max-width: 380px; }
        .toast.error { background: linear-gradient(135deg, #fff5f5 0%, #fee2e2 100%); color: #991b1b; border: 1px solid #fecaca; }
        .toast.error .toast-icon { background: #fef2f2; color: #ef4444; }
        .toast.success { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); color: #166534; border: 1px solid #bbf7d0; }
        .toast.success .toast-icon { background: #ecfdf5; color: #10b981; }
        .toast-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .toast-icon i { font-size: 16px; }
        .toast-content { flex: 1; }
        .toast-title { font-weight: 700; margin-bottom: 2px; }
        .toast-message { font-weight: 500; opacity: 0.85; font-size: 13px; }
        .toast-close { background: none; border: none; cursor: pointer; padding: 4px; opacity: 0.5; transition: opacity 0.2s; }
        .toast-close:hover { opacity: 1; }
        @keyframes slideInUp { from { transform: translateY(100px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; visibility: hidden; } }

        .empty-state { grid-column: 1 / -1; background: #ffffff; border-radius: 24px; padding: 60px 40px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 2px dashed #f0f0f0; margin: 20px 0; }
        .empty-icon-bg { width: 90px; height: 90px; background: #eff6ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 25px; animation: float 6s ease-in-out infinite; }
        .empty-icon-bg i { font-size: 36px; color: #2563eb; }
        .empty-state h3 { font-size: 20px; font-weight: 700; color: #1f2937; margin: 0 0 8px 0; }
        .empty-state p { font-size: 14px; color: #6b7280; line-height: 1.5; max-width: 300px; margin: 0; }
        @keyframes float { 0% {transform: translateY(0px);} 50% {transform: translateY(-10px);} 100% {transform: translateY(0px);} }

        /* --- NEW: COURSE SUMMARY SECTION --- */
        .summary-section { margin-top: 50px; }
        .summary-title { font-size: 22px; font-weight: 800; color: #1f2937; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        
        .subject-card {
            background: white; border-radius: 20px; padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f3f4f6;
            transition: all 0.3s ease; cursor: pointer;
            display: flex; justify-content: space-between; align-items: center;
        }
        .subject-card:hover { transform: translateY(-5px); border-color: #8056ff; box-shadow: 0 10px 30px rgba(128, 86, 255, 0.1); }
        
        .subj-info h4 { margin: 0 0 5px; font-size: 16px; font-weight: 700; color: #1f2937; }
        .subj-code { font-size: 12px; color: #6b7280; font-weight: 600; background: #f3f4f6; padding: 4px 10px; border-radius: 8px; display: inline-block; }
        .subj-stats { margin-top: 10px; font-size: 13px; color: #4b5563; }

        /* PERCENTAGE WHEEL */
        .percent-wheel {
            position: relative; width: 60px; height: 60px; border-radius: 50%;
            background: conic-gradient(var(--c) calc(var(--p)*1%), #f3f4f6 0);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .percent-wheel::before { content: ""; position: absolute; inset: 6px; background: white; border-radius: 50%; }
        .percent-text { position: relative; font-size: 12px; font-weight: 800; color: #1f2937; }

        /* MODAL STYLES */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal-content { background: white; padding: 30px; border-radius: 24px; width: 500px; max-width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.2); }
        .hist-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #f3f4f6; }
        .hist-row:last-child { border-bottom: none; }
        .hist-date { font-weight: 600; font-size: 14px; color: #374151; display: block; }
        .hist-meta { font-size: 12px; color: #9ca3af; margin-top: 2px; }
        .hist-badge { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 12px; text-transform: uppercase; }
        .hb-present { background: #d1fae5; color: #065f46; }
        .hb-late { background: #fffbeb; color: #d97706; }
        .hb-absent { background: #fee2e2; color: #991b1b; }
        
        /* Low Attendance Warning Banner */
        .attendance-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fef9c3 100%);
            border: 1px solid #fcd34d;
            border-left: 4px solid #f59e0b;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }
        .warning-icon {
            width: 36px;
            height: 36px;
            background: #f59e0b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .warning-icon i { color: white; font-size: 16px; }
        .warning-content strong { color: #92400e; font-size: 14px; display: block; margin-bottom: 4px; }
        .warning-content p { color: #a16207; font-size: 13px; margin: 0; line-height: 1.5; }
        
        /* Low attendance card highlight */
        .subject-card.low-attendance {
            border-color: #fcd34d;
            background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%);
        }
        .subject-card.low-attendance:hover {
            border-color: #f59e0b;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.15);
        }
    </style>
</head>
<body>
    <div class="app-bg">
        <div class="main-card">
            <?php $currentPage = 'attendance'; include __DIR__ . '/../includes/studentMaster.php'; ?>

            <main class="dashboard">
                <header class="dashboard-topbar">
                    <div class="topbar-right">
                        <div class="user-card">
                            <div class="user-info">
                                <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                                <span class="user-role">Student</span>
                            </div>
                            <a href="student-profile.php" class="profile-pic" title="View Profile">
                                <?php if (!empty($studentImage) && $studentImage !== 'default_avatar.png'): ?>
                                    <img src="../uploads/profiles/<?php echo htmlspecialchars($studentImage); ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover;">
                                <?php else: ?>
                                    <i class="fa-solid fa-user"></i>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </header>

                <!-- Enhanced Page Hero Header -->
                <div class="page-hero-card" style="
                    border-radius: 26px;
                    background: linear-gradient(140deg, #8056ff, #6c5ce7);
                    padding: 32px 36px;
                    color: white;
                    box-shadow: 0 28px 60px rgba(116, 88, 255, 0.35);
                    overflow: hidden;
                    position: relative;
                    margin-bottom: 28px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                ">
                    <!-- Decorative Background Elements -->
                    <div style="position: absolute; width: 160px; height: 160px; top: -40px; right: 180px; background: rgba(255,255,255,0.12); border-radius: 22px; transform: rotate(12deg);"></div>
                    <div style="position: absolute; width: 110px; height: 110px; top: 60px; right: 320px; background: rgba(255,255,255,0.08); border-radius: 22px; transform: rotate(12deg);"></div>
                    
                    <!-- Left Content -->
                    <div style="position: relative; z-index: 1;">
                        <h1 style="font-size: 30px; font-weight: 700; margin: 0 0 8px 0; color: white;">Attendance Overview</h1>
                        <p style="margin: 0; font-size: 14px; color: rgba(255,255,255,0.85);">Track your class attendance and history</p>
                    </div>
                    
                    <!-- Right Decorative Illustration -->
                    <div style="position: relative; z-index: 1; display: flex; align-items: center; gap: 15px;">
                        <!-- Calendar Illustration -->
                        <div style="
                            width: 100px; height: 100px;
                            background: white;
                            border-radius: 18px;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
                            position: relative;
                        ">
                            <div style="position: absolute; top: -8px; left: 20px; width: 8px; height: 16px; background: #6c5ce7; border-radius: 4px;"></div>
                            <div style="position: absolute; top: -8px; right: 20px; width: 8px; height: 16px; background: #6c5ce7; border-radius: 4px;"></div>
                            <div style="font-size: 12px; color: #8056ff; font-weight: 700; margin-bottom: 2px;"><?php echo strtoupper(date('M')); ?></div>
                            <div style="font-size: 32px; font-weight: 800; color: #1f2937;"><?php echo date('d'); ?></div>
                            <div style="display: flex; gap: 3px; margin-top: 4px;">
                                <span style="width: 6px; height: 6px; background: #10b981; border-radius: 50%;"></span>
                                <span style="width: 6px; height: 6px; background: #8056ff; border-radius: 50%;"></span>
                                <span style="width: 6px; height: 6px; background: #f59e0b; border-radius: 50%;"></span>
                            </div>
                        </div>
                        <!-- Clock Illustration -->
                        <div style="
                            width: 80px; height: 80px;
                            background: white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
                            position: relative;
                        ">
                            <div style="width: 60px; height: 60px; border: 3px solid #e5e7eb; border-radius: 50%; position: relative;">
                                <!-- Clock hands -->
                                <div style="position: absolute; top: 50%; left: 50%; width: 2px; height: 18px; background: #1f2937; transform-origin: bottom; transform: translate(-50%, -100%) rotate(0deg); border-radius: 2px;"></div>
                                <div style="position: absolute; top: 50%; left: 50%; width: 2px; height: 12px; background: #8056ff; transform-origin: bottom; transform: translate(-50%, -100%) rotate(90deg); border-radius: 2px;"></div>
                                <div style="position: absolute; top: 50%; left: 50%; width: 6px; height: 6px; background: #8056ff; border-radius: 50%; transform: translate(-50%, -50%);"></div>
                            </div>
                            <!-- Checkmark badge -->
                            <div style="position: absolute; bottom: -5px; right: -5px; width: 28px; height: 28px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white;">
                                <i class="fa-solid fa-check" style="color: white; font-size: 12px;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Classes Section -->
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 4px; height: 24px; background: #8056ff; border-radius: 4px;"></div>
                        <h2 style="font-size: 1.1rem; font-weight: 600; color: #1f2937; margin: 0;">
                            <i class="fa-solid fa-calendar-day" style="color: #8056ff; margin-right: 8px;"></i>Today's Classes
                        </h2>
                    </div>
                    <span style="font-size: 14px; font-weight: 500; color: #f8f8f8; background: #8056ff; padding: 6px 14px; border-radius: 8px;">
                        <?php echo date('l, j F Y'); ?>
                    </span>
                </div>

                <div class="class-list">
                    <?php if (empty($todaysClasses)): ?>
                        <div class="empty-state">
                            <div class="empty-icon-bg"><i class="fa-solid fa-mug-hot"></i></div>
                            <h3>All Caught Up!</h3>
                            <p>Enjoy your free time. No classes scheduled for today.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($todaysClasses as $cls): 
                            $start = $cls['startTime']; $end = $cls['endTime'];
                            $cardStyle = 'upcoming'; $label = 'Upcoming'; $status = 'upcoming';
                            if ($cls['isAttended'] > 0) { $status='attended'; $cardStyle='attended'; $label='<i class="fa-solid fa-circle-check"></i> Attended'; }
                            elseif ($currentTime >= $start && $currentTime <= $end && isset($cls['sessionStatus']) && $cls['sessionStatus'] === 'CLOSED') { $status='stopped'; $cardStyle='stopped'; $label='<i class="fa-solid fa-circle-stop"></i> Session Ended'; }
                            elseif ($currentTime >= $start && $currentTime <= $end) { $status='live'; $cardStyle='live'; $label='<i class="fa-solid fa-tower-broadcast"></i> Live Now'; }
                            elseif ($currentTime > $end) { $status='missed'; $cardStyle='missed'; $label='Absent'; }
                        ?>
                        <div class="class-card <?php echo $cardStyle; ?>">
                            <div class="card-top">
                                <span class="time-badge"><?php echo substr($start, 0, 5) . ' - ' . substr($end, 0, 5); ?></span>
                                <span class="status-badge st-<?php echo $status; ?>"><?php echo $label; ?></span>
                            </div>
                            <div>
                                <h3 style="margin: 0; color: #1f2937;"><?php echo $cls['courseName']; ?></h3>
                                <p style="margin: 5px 0 0; color: #6b7280; font-size: 14px;"><?php echo $cls['courseID']; ?> • <?php echo $cls['facilityName']; ?></p>
                                <p style="margin: 5px 0 0; color: #6b7280; font-size: 13px;"><i class="fa-solid fa-chalkboard-user"></i> <?php echo $cls['staffName']; ?></p>
                            </div>
                            <?php if ($status === 'live'): ?>
                                <div class="code-input-area">
                                    <form method="POST">
                                        <input type="hidden" name="scheduleID" value="<?php echo $cls['scheduleID']; ?>">
                                        <input type="hidden" name="attend_csrf" value="<?php echo htmlspecialchars($_SESSION['attend_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <p style="margin: 0 0 14px; font-size: 13px; color: #6b7280; font-weight: 500;">
                                            <i class="fa-solid fa-keyboard" style="margin-right: 6px; color: #8056ff;"></i>Enter 6-digit code:
                                        </p>
                                        <div class="input-group">
                                            <?php for($i=0; $i<6; $i++): ?><input type="text" name="digit[]" class="digit-box" maxlength="1" required autocomplete="off" inputmode="numeric" pattern="[0-9]"><?php endfor; ?>
                                        </div>
                                        <button class="btn-submit"><i class="fa-solid fa-paper-plane" style="margin-right: 8px;"></i>Submit</button>
                                    </form>
                                </div>
                            <?php elseif ($status === 'stopped'): ?>
                                <div class="code-input-area" style="border-color: #e5e7eb; background: #f9fafb;">
                                    <div style="text-align: center; padding: 10px 0;">
                                        <i class="fa-solid fa-circle-stop" style="font-size: 28px; color: #9ca3af; margin-bottom: 10px; display: block;"></i>
                                        <p style="margin: 0; font-weight: 600; color: #4b5563; font-size: 14px;">Attendance Session Ended</p>
                                        <p style="margin: 8px 0 0; font-size: 12px; color: #9ca3af;">The lecturer has closed the attendance for this class.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="summary-section">
                    <h2 class="summary-title"><i class="fa-solid fa-chart-pie" style="color:#8056ff; font-size:20px;"></i> My Course Attendance</h2>
                    
                    <?php 
                    // Check if any course has low attendance
                    $lowAttendanceCourses = [];
                    foreach($myCourses as $crs) {
                        $stats = getCourseStats($db, $studentID, $crs['courseID'], $sData['programID'], $sData['tutGroup']);
                        if ($stats['total'] > 0) {
                            $percent = round(($stats['attended'] / $stats['total']) * 100);
                            if ($percent < 80) {
                                $lowAttendanceCourses[] = ['name' => $crs['courseName'], 'percent' => $percent];
                            }
                        }
                    }
                    if (!empty($lowAttendanceCourses)): 
                    ?>
                    <div class="attendance-warning">
                        <div class="warning-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div class="warning-content">
                            <strong>Low Attendance Warning</strong>
                            <p>Your attendance is below 80% in <?php echo count($lowAttendanceCourses); ?> course(s). Maintain at least 80% attendance to avoid academic penalties.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="summary-grid">
                        <?php foreach($myCourses as $crs): 
                            $stats = getCourseStats($db, $studentID, $crs['courseID'], $sData['programID'], $sData['tutGroup']);
                            
                            // Handle case when no classes conducted yet
                            if ($stats['total'] == 0) {
                                $percent = 100; // Show 100% if no classes yet
                                $displayText = 'No classes yet';
                            } else {
                                $percent = round(($stats['attended'] / $stats['total']) * 100);
                                $displayText = $stats['attended'] . ' / ' . $stats['total'] . ' Classes';
                            }
                            
                            // Color Logic
                            $wheelColor = '#10b981'; // Green
                            if ($stats['total'] > 0 && $percent < 80) $wheelColor = '#f59e0b'; // Orange
                            if ($stats['total'] > 0 && $percent < 50) $wheelColor = '#ef4444'; // Red
                            
                            // Show missed count if any
                            $missedText = '';
                            if ($stats['missed'] > 0) {
                                $missedText = '<span style="color:#ef4444; margin-left:8px;"><i class="fa-solid fa-xmark"></i> ' . $stats['missed'] . ' missed</span>';
                            }
                        ?>
                        <div class="subject-card<?php echo ($stats['total'] > 0 && $percent < 80) ? ' low-attendance' : ''; ?>" onclick="openHistory('<?php echo $crs['courseID']; ?>', '<?php echo $crs['courseName']; ?>')">
                            <div class="subj-info">
                                <h4><?php echo $crs['courseName']; ?></h4>
                                <span class="subj-code"><?php echo $crs['courseID']; ?></span>
                                <div class="subj-stats">
                                    <i class="fa-solid fa-check-double"></i> <?php echo $displayText; ?><?php echo $missedText; ?>
                                </div>
                            </div>
                            <div class="percent-wheel" style="--p:<?php echo $percent; ?>; --c:<?php echo $wheelColor; ?>;">
                                <div class="percent-text"><?php echo $percent; ?>%</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <div id="historyModal" class="modal">
        <div class="modal-content">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px; align-items:center;">
                <h3 id="histTitle" style="margin:0; font-size:18px;">History</h3>
                <span onclick="closeModal()" style="cursor:pointer; font-size:24px; color:#9ca3af;">&times;</span>
            </div>
            <div id="histContainer">Loading...</div>
        </div>
    </div>

    <script>
        const inputs = document.querySelectorAll('.digit-box');
        inputs.forEach((inp, i) => {
            inp.addEventListener('input', e => { if(e.target.value) inputs[i+1]?.focus(); });
            inp.addEventListener('keydown', e => { if(e.key==='Backspace' && !e.target.value) inputs[i-1]?.focus(); });
        });

        function openHistory(courseID, courseName) {
            document.getElementById('historyModal').style.display = 'flex';
            document.getElementById('histTitle').innerText = courseName;
            document.getElementById('histContainer').innerHTML = '<p style="text-align:center; color:#888;">Fetching data...</p>';

            fetch(`student-attendance.php?get_history=1&courseID=${courseID}`)
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('histContainer');
                    if(data.length === 0) {
                        container.innerHTML = `
                            <div style="text-align:center; padding: 30px 0;">
                                <i class="fa-solid fa-calendar-xmark" style="font-size: 40px; color: #d1d5db; margin-bottom: 15px;"></i>
                                <p style="color:#6b7280; font-weight: 600; margin: 0;">No Classes Conducted Yet</p>
                                <p style="color:#9ca3af; font-size: 13px; margin-top: 5px;">Attendance history will appear here once classes begin.</p>
                            </div>`;
                        return;
                    }
                    
                    let html = '';
                    data.forEach(row => {
                        let badge = '';
                        if(row.status === 'Present') badge = '<span class="hist-badge hb-present">Present</span>';
                        else if(row.status === 'Late') badge = '<span class="hist-badge hb-late">Late</span>';
                        else badge = '<span class="hist-badge hb-absent">Absent</span>';
                        
                        // Format Time
                        let timeStr = row.startTime.substring(0,5);
                        let scanStr = row.scanTime ? row.scanTime.substring(0,5) : (row.status === 'Absent' ? 'Not Scanned' : '--:--');
                        
                        html += `
                        <div class="hist-row">
                            <div>
                                <span class="hist-date">${row.attendanceDate}</span>
                                <div class="hist-meta">${row.classType} • Starts ${timeStr}</div>
                                <div class="hist-meta" style="font-size:11px;">${row.status === 'Absent' ? '<span style="color:#991b1b;">Not Attended</span>' : 'Scanned: ' + scanStr}</div>
                            </div>
                            ${badge}
                        </div>`;
                    });
                    container.innerHTML = html;
                });
        }

        function closeModal() {
            document.getElementById('historyModal').style.display = 'none';
        }
        
        window.onclick = function(e) {
            if (e.target == document.getElementById('historyModal')) closeModal();
        }

        // Nav toggle functionality
        document.querySelectorAll('.nav-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                const targetPanel = document.getElementById(targetId);
                const isExpanded = btn.getAttribute('aria-expanded') === 'true';
                if (targetPanel) {
                    targetPanel.hidden = isExpanded;
                    btn.setAttribute('aria-expanded', !isExpanded);
                    btn.parentElement.classList.toggle('is-open', !isExpanded);
                }
            });
        });
    </script>

    <!-- Toast Notification Container -->
    <?php if($msg): ?>
    <div class="toast-container">
        <div class="toast <?php echo $msgType; ?>">
            <div class="toast-icon">
                <?php if($msgType === 'error'): ?>
                    <i class="fa-solid fa-circle-exclamation"></i>
                <?php else: ?>
                    <i class="fa-solid fa-circle-check"></i>
                <?php endif; ?>
            </div>
            <div class="toast-content">
                <div class="toast-title"><?php echo $msgType === 'error' ? 'Attendance Failed' : 'Attendance Recorded'; ?></div>
                <div class="toast-message"><?php echo $msg; ?></div>
            </div>
            <button class="toast-close" onclick="this.parentElement.parentElement.remove();">
                <i class="fa-solid fa-xmark" style="color: #6b7280;"></i>
            </button>
        </div>
    </div>
    <script>
        // Auto-remove toast after 5 seconds
        setTimeout(() => {
            const toast = document.querySelector('.toast-container');
            if(toast) toast.remove();
        }, 5000);
    </script>
    <?php endif; ?>
</body>
</html>