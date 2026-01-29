<?php
session_start();
require_once '../includes/config.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit;
}

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

// 2. Fetch Classes + Counts (Joined vs Total)
$sql = "SELECT s.*, c.courseName, c.courseID, f.facilityName,
               sess.sessionID, sess.code, sess.status as sessionStatus,
               (SELECT COUNT(*) FROM attendance a WHERE a.scheduleID = s.scheduleID AND a.attendanceDate = ?) as joinedCount,
               (SELECT COUNT(*) FROM student st WHERE st.programID = s.programID AND (st.tutGroup = s.tutGroup OR s.tutGroup = 'Combined')) as totalStudents
        FROM class_schedule s 
        JOIN course c ON s.courseID = c.courseID
        JOIN facility f ON s.facilityID = f.facilityID
        LEFT JOIN attendance_sessions sess ON s.scheduleID = sess.scheduleID AND sess.sessionDate = ?
        WHERE s.staffID = ? AND s.day = ?
        ORDER BY s.startTime ASC";

$stmt = $db->prepare($sql);
$stmt->bind_param("ssss", $currentDate, $currentDate, $staffID, $currentDay);
$stmt->execute();
$result = $stmt->get_result();
$todaysClasses = $result->fetch_all(MYSQLI_ASSOC);

// 3. Handle Actions (Start/Stop/Manual)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        if ($status === 'Present') {
            $chk = $db->prepare("SELECT recordID FROM attendance WHERE studentID=? AND scheduleID=? AND attendanceDate=?");
            $chk->bind_param("sis", $studentID, $schedID, $currentDate);
            $chk->execute();
            if ($chk->get_result()->num_rows == 0) {
                $ins = $db->prepare("INSERT INTO attendance (studentID, scheduleID, attendanceDate, scanTime, status) VALUES (?, ?, ?, ?, 'Present')");
                $ins->bind_param("siss", $studentID, $schedID, $currentDate, $currentTime);
                $ins->execute();
            }
        } elseif ($status === 'Absent') {
            $del = $db->prepare("DELETE FROM attendance WHERE studentID=? AND scheduleID=? AND attendanceDate=?");
            $del->bind_param("sis", $studentID, $schedID, $currentDate);
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

    $sql = "SELECT s.studentName, s.studentID, a.scanTime, COALESCE(a.status, 'Absent') as status
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
        /* Modern Grid */
        .class-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 25px; margin-bottom: 50px; }
        
        /* NEW: Light Purple Card Style */
        .class-card {
            background: #fcfaff; /* Very light purple tint */
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 10px 40px rgba(200, 200, 230, 0.15); 
            border: 1px solid #ebe5ff; /* Subtle purple border */
            transition: transform 0.2s, box-shadow 0.2s; 
            position: relative; overflow: hidden;
            display: flex; flex-direction: column;
        }
        .class-card:hover { transform: translateY(-5px); box-shadow: 0 15px 50px rgba(128, 86, 255, 0.15); border-color: #dcd0ff; }

        /* Status Accents */
        .class-card.active { border-left: 6px solid #8056ff; background: #fff; } /* Purple Strip */
        .class-card.completed { border-left: 6px solid #10b981; opacity: 0.9; } /* Green Strip */
        .class-card.upcoming { border-left: 6px solid #9ca3af; background: #fafafa; } /* Gray Strip */

        /* Card Elements */
        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .time-badge { padding: 6px 14px; border-radius: 12px; font-size: 13px; font-weight: 700; color: #555; background: #f3f4f6; }
        .session-code { font-size: 36px; font-weight: 800; letter-spacing: 6px; margin: 20px 0; text-align: center; background: white; color: #6b45f5; padding: 15px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 2px solid #f3efff; }
        .card-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }

        /* --- VIBRANT SUMMARY SECTION (Stretched) --- */
        .summary-section { display: flex; flex-direction: column; gap: 15px; margin-top: 40px; }
        .summary-title { 
            font-size: 24px; 
            font-weight: 800; 
            margin-bottom: 20px; 
            background: linear-gradient(135deg, #8056ff 0%, #6b45f5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .summary-row {
            background: linear-gradient(135deg, #fcfaff 0%, #ffffff 100%);
            border-radius: 18px;
            padding: 20px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(128, 86, 255, 0.08);
            border: 1px solid #ebe5ff;
            transition: all 0.3s;
        }
        .summary-row:hover { transform: translateX(8px); border-left: 5px solid #8056ff; box-shadow: 0 8px 30px rgba(128, 86, 255, 0.15); }
        
        .sum-info h4 { margin: 0 0 5px; font-size: 16px; font-weight: 700; color: #1f2937; }
        .sum-meta { font-size: 13px; color: #6b7280; display: flex; gap: 15px; }
        
        /* CSS Percentage Circle */
        .progress-circle {
            position: relative; width: 50px; height: 50px; border-radius: 50%;
            background: conic-gradient(#8056ff calc(var(--p)*1%), #f3f4f6 0);
            display: flex; align-items: center; justify-content: center;
        }
        .progress-circle::before { content: ""; position: absolute; inset: 6px; background: white; border-radius: 50%; }
        .progress-text { position: relative; font-size: 11px; font-weight: 700; color: #1f2937; }

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
                    <div class="brand-text">
                        <span class="brand-name">CAMPUSLink</span>
                        <span class="brand-tagline">Staff Console</span>
                    </div>
                </div>
                
                <nav class="sidebar-nav">
                    <ul>
                        <li>
                            <a href="staff-dashboard.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-table-columns"></i></span>
                                <span class="nav-label">Dashboard</span>
                            </a>
                        </li>

                        <li class="nav-group" data-expandable>
                            <button class="nav-item nav-toggle" type="button" aria-expanded="false" data-target="student-menu">
                                <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
                                <span class="nav-label">Student</span>
                                <span class="nav-chevron"></span>
                            </button>
                            <div id="student-menu" class="nav-submenu" hidden>
                                <a href="manage-students.php" class="nav-subitem">Manage Student</a>
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
                            <a href="../logout.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                                <span class="nav-label">Logout</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </aside>

            <main class="dashboard">
                <header class="dashboard-topbar">
                    <div class="search-box">
                        <input type="search" placeholder="Search..." autocomplete="off">
                    </div>
                    <div class="topbar-right">
                        <div class="user-card">
                            <span class="user-name"><?php echo htmlspecialchars($staffName); ?></span>
                            <span class="user-status">Staff ID: <?php echo htmlspecialchars($staffID); ?></span>
                        </div>
                    </div>
                </header>

                <div class="class-grid">
                    <?php 
                    $hasClasses = false;
                    foreach ($todaysClasses as $cls): 
                        $hasClasses = true;
                        $startTime = substr($cls['startTime'], 0, 5);
                        $endTime = substr($cls['endTime'], 0, 5);
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
                    <?php endforeach; 
                    if (!$hasClasses) echo '<p style="color:#888;">No classes today.</p>';
                    ?>
                </div>

                <h2 class="summary-title" style="margin-top: 50px;">Summary for Today</h2>
                <div class="summary-section">
                    <?php 
                    $hasSummary = false;
                    foreach ($todaysClasses as $cls):
                        // Only show completed classes in summary
                        $isPast = ($currentTime > $cls['endTime']);
                        if ($cls['sessionStatus'] == 'CLOSED' || $isPast):
                            $hasSummary = true;
                            $joined = $cls['joinedCount'];
                            $total = $cls['totalStudents'] > 0 ? $cls['totalStudents'] : 1; // Avoid div by zero
                            $percent = round(($joined / $total) * 100);
                    ?>
                    <div class="summary-row">
                        <div class="sum-info">
                            <h4><?php echo $cls['courseName']; ?></h4>
                            <div class="sum-meta">
                                <span><i class="fa-regular fa-clock"></i> <?php echo substr($cls['startTime'], 0, 5); ?> - <?php echo substr($cls['endTime'], 0, 5); ?></span>
                                <span><i class="fa-solid fa-users"></i> <?php echo $joined . ' / ' . $cls['totalStudents']; ?> Students</span>
                            </div>
                        </div>
                        <div class="sum-chart">
                            <div class="progress-circle" style="--p:<?php echo $percent; ?>;">
                                <div class="progress-text"><?php echo $percent; ?>%</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; endforeach; 
                    if (!$hasSummary) echo '<p style="color:#aaa; font-style:italic;">No completed classes yet.</p>';
                    ?>
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
        function openModal(students, courseName, scheduleID) {
            currentScheduleID = scheduleID;
            const container = document.getElementById('studentListContainer');
            const title = document.getElementById('modalTitle');
            const total = students.length;
            const present = students.filter(s => s.status === 'Present').length;
            const absent = total - present;

            title.innerHTML = `<div style="display:flex; flex-direction:column; gap:4px;"><span>${courseName}</span><span style="font-size:12px; color:#6b7280; font-weight:500;">Total: ${total} • <span style="color:#059669;">Present: ${present}</span> • <span style="color:#dc2626;">Absent: ${absent}</span></span></div>`;
            container.innerHTML = '';

            if (students.length === 0) container.innerHTML = '<p style="text-align:center;">No students.</p>';
            else students.forEach(s => {
                let actionHTML = '', statusBadge = '';
                if (s.status === 'Absent') {
                    statusBadge = `<span style="background:#fee2e2; color:#991b1b; padding:4px 10px; border-radius:12px; font-size:11px; font-weight:700;">ABSENT</span>`;
                    actionHTML = `<button onclick="toggleAttendance('${s.studentID}', 'Present', this)" style="background:#8056ff; color:white; border:none; padding:5px 10px; border-radius:6px; font-size:11px; cursor:pointer; margin-left:10px;">Mark Present</button>`;
                } else {
                    statusBadge = `<span style="background:#d1fae5; color:#065f46; padding:4px 10px; border-radius:12px; font-size:11px; font-weight:700;">PRESENT</span>`;
                    actionHTML = `<button onclick="toggleAttendance('${s.studentID}', 'Absent', this)" style="background:transparent; color:#991b1b; border:1px solid #fee2e2; padding:4px 8px; border-radius:6px; font-size:10px; cursor:pointer; margin-left:10px;">Undo</button>`;
                }
                const row = `<div class="student-row" style="display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-bottom:1px solid #f3f4f6;"><div style="display:flex; align-items:center; gap:12px;"><div style="width:36px; height:36px; background:#f3f4f6; border-radius:50%; display:grid; place-items:center; color:#6b7280; font-size:14px;"><i class="fa-solid fa-user"></i></div><div><div style="font-weight:600; color:#1f2937; font-size:14px;">${s.studentName}</div><div style="font-size:12px; color:#9ca3af;">${s.studentID}</div></div></div><div style="text-align:right; display:flex; align-items:center; gap:10px;"><div style="text-align:right;">${statusBadge}</div>${actionHTML}</div></div>`;
                container.innerHTML += row;
            });
            document.getElementById('listModal').style.display = 'flex';
        }

        function toggleAttendance(studentID, status, btnElement) {
            btnElement.textContent = "..."; btnElement.disabled = true;
            const formData = new FormData();
            formData.append('manual_attendance', true); formData.append('studentID', studentID);
            formData.append('scheduleID', currentScheduleID); formData.append('status', status);
            fetch('staff-attendance.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => { if(d.status==='success') location.reload(); });
        }
        window.onclick = function(e) { if (e.target == document.getElementById('listModal')) document.getElementById('listModal').style.display = 'none'; }
    </script>
</body>
</html>