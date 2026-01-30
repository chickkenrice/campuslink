<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$db = get_db_connection();
$studentID = $_SESSION['user_id'];
date_default_timezone_set('Asia/Kuala_Lumpur');
$currentDay = date('l'); $currentTime = date('H:i:s'); $currentDate = date('Y-m-d');

// 1. Get Student Details (Program, Year, Semester)
$stmtS = $db->prepare("SELECT programID, tutGroup, currentYear, currentSemester FROM student WHERE studentID = ?");
$stmtS->bind_param("s", $studentID);
$stmtS->execute();
$sData = $stmtS->get_result()->fetch_assoc();

// 2. Fetch TODAY'S Classes (Strict Filter by Year/Sem)
// Fix: We join program_course to ensure the class matches the student's current Year/Sem
$sql = "SELECT s.*, c.courseName, c.courseID, st.staffName, f.facilityName,
       (SELECT COUNT(*) FROM attendance a WHERE a.scheduleID = s.scheduleID AND a.studentID = ? AND a.attendanceDate = ?) as isAttended
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
       ORDER BY s.startTime ASC";

$stmt = $db->prepare($sql);
$stmt->bind_param("sssssii", $studentID, $currentDate, $sData['programID'], $sData['tutGroup'], $currentDay, $sData['currentYear'], $sData['currentSemester']);
$stmt->execute();
$todaysClasses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Handle Attendance Submission
$msg = ""; $msgType = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scheduleID'])) {
    $schedID = $_POST['scheduleID'];
    $code = implode('', $_POST['digit']); 
    
    $check = $db->query("SELECT * FROM attendance WHERE studentID='$studentID' AND scheduleID='$schedID' AND attendanceDate='$currentDate'");
    
    if ($check->num_rows > 0) { $msg = "Already attended."; $msgType = "success"; } else {
        $sess = $db->prepare("SELECT * FROM attendance_sessions WHERE scheduleID=? AND sessionDate=? AND code=? AND status='OPEN'");
        $sess->bind_param("iss", $schedID, $currentDate, $code);
        $sess->execute();
        
        if ($sess->get_result()->fetch_assoc()) {
            // LATE LOGIC
            $qTime = $db->prepare("SELECT startTime FROM class_schedule WHERE scheduleID = ?");
            $qTime->bind_param("i", $schedID);
            $qTime->execute();
            $classData = $qTime->get_result()->fetch_assoc();
            
            $status = 'Present';
            if ($classData) {
                $startTs = strtotime($currentDate . ' ' . $classData['startTime']);
                $scanTs = strtotime($currentDate . ' ' . $currentTime);
                if ($scanTs > ($startTs + (15 * 60))) { $status = 'Late'; }
            }

            $ins = $db->prepare("INSERT INTO attendance (studentID, scheduleID, attendanceDate, scanTime, status) VALUES (?, ?, ?, ?, ?)");
            $ins->bind_param("sisss", $studentID, $schedID, $currentDate, $currentTime, $status);
            $ins->execute();
            header("Refresh:0"); 
        } else { $msg = "Invalid or expired code."; $msgType = "error"; }
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
    // 1. Total Classes Conducted (Sessions that are CLOSED)
    // We check for sessions linked to schedules of this course
    $qTotal = "SELECT COUNT(*) as total 
               FROM attendance_sessions sess
               JOIN class_schedule s ON sess.scheduleID = s.scheduleID
               WHERE s.courseID = ? AND s.programID = ? 
               AND (s.tutGroup = ? OR s.tutGroup = 'Combined')
               AND sess.status = 'CLOSED'"; // Only count finished classes
    
    $stmt = $db->prepare($qTotal);
    $stmt->bind_param("sss", $courseID, $programID, $tutGroup);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];

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

    return ['total' => $total, 'attended' => $attended];
}

// API Endpoint for fetching history (AJAX)
if (isset($_GET['get_history']) && isset($_GET['courseID'])) {
    $cID = $_GET['courseID'];
    $histSql = "SELECT a.attendanceDate, a.status, a.scanTime, s.classType, s.startTime, s.endTime
                FROM attendance a
                JOIN class_schedule s ON a.scheduleID = s.scheduleID
                WHERE a.studentID = ? AND s.courseID = ?
                ORDER BY a.attendanceDate DESC";
    $stmtH = $db->prepare($histSql);
    $stmtH->bind_param("ss", $studentID, $cID);
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
        
        .card-top { display: flex; justify-content: space-between; align-items: center; }
        .time-badge { background: #f3f4f6; padding: 8px 14px; border-radius: 12px; font-weight: 700; font-size: 13px; color: #555; }
        .status-badge { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
        .st-attended { background: #d1fae5; color: #065f46; }
        .st-live { background: #f3efff; color: #7c3aed; }
        .st-upcoming { background: #f3f4f6; color: #6b7280; }
        .st-missed { background: #fee2e2; color: #991b1b; }

        .code-input-area { background: #fdfdff; padding: 20px; border-radius: 16px; text-align: center; border: 2px dashed #dcd0ff; margin-top: 10px; }
        .input-group { display: flex; justify-content: center; gap: 8px; margin-bottom: 15px; }
        .digit-box { width: 45px; height: 55px; font-size: 24px; text-align: center; border: 2px solid #e0e0e0; border-radius: 10px; font-weight: 700; outline: none; }
        .digit-box:focus { border-color: #8056ff; box-shadow: 0 0 0 3px rgba(128,86,255,0.15); }
        .btn-submit { background: #8056ff; color: white; border: none; padding: 12px 25px; border-radius: 12px; font-weight: 700; cursor: pointer; width: 100%; max-width: 200px; transition: all 0.2s; }
        
        .alert { padding: 12px; border-radius: 10px; text-align: center; font-weight: 600; margin-bottom: 20px; }
        .alert.error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .alert.success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }

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
    </style>
</head>
<body>
    <div class="app-bg">
        <div class="main-card">
            <aside class="sidebar">
                <div class="sidebar-head">
                    <div class="brand-icon">CL</div>
                    <div class="brand-text"><span class="brand-name">CAMPUSLink</span><span class="brand-tagline">Student Portal</span></div>
                </div>
                <nav class="sidebar-nav">
                    <ul>
                        <li><a href="../index.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-table-columns"></i></span> <span class="nav-label">Dashboard</span></a></li>
                        <li><a href="student-attendance.php" class="nav-item is-active"><span class="nav-icon"><i class="fa-solid fa-clock"></i></span> <span class="nav-label">Attendance</span></a></li>
                        <li><a href="student-timetable.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-calendar-days"></i></span> <span class="nav-label">My Timetable</span></a></li>
                        <li class="nav-group" data-expandable><button class="nav-item nav-toggle" type="button" data-target="programme-panel"><span class="nav-icon"><i class="fa-solid fa-clipboard-list"></i></span> <span class="nav-label">Programme</span> <span class="nav-chevron"></span></button></li>
                        <li><a href="../logout.php" class="nav-item" style="margin-top: 20px;"><span class="nav-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span> <span class="nav-label" style="color: #e74c3c;">Logout</span></a></li>
                    </ul>
                </nav>
            </aside>

            <main class="dashboard">
                <header class="dashboard-topbar">
                    <div class="search-box"><input id="dashboard-search" type="search" placeholder="Search" autocomplete="off"></div>
                    <div class="topbar-right">
                        <div class="user-card" style="display: flex; align-items: center; gap: 12px; flex-direction: row-reverse;">
                            <a href="student-profile.php" class="profile-link">
                                <div class="profile-pic" style="width: 42px; height: 42px; border-radius: 50%; background: #f3efff; display: grid; place-items: center; border: 2px solid #e0d4fc; overflow: hidden;">
                                    <i class="fa-solid fa-user" style="color: #8056ff; font-size: 18px;"></i>
                                </div>
                            </a>
                            <div class="user-meta" style="text-align: right;">
                                <span class="user-name" style="display: block; font-weight: 600; color: #1f2937;"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                                <span class="user-status" style="font-size: 12px; color: #6b7280;">Student</span>
                            </div>
                        </div>
                    </div>
                </header>

                <?php if($msg): ?><div class="alert <?php echo $msgType; ?>"><?php echo $msg; ?></div><?php endif; ?>

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
                                        <p style="margin: 0 0 10px; font-size: 13px; color: #666;">Enter 6-digit code:</p>
                                        <div class="input-group">
                                            <?php for($i=0; $i<6; $i++): ?><input type="text" name="digit[]" class="digit-box" maxlength="1" required autocomplete="off"><?php endfor; ?>
                                        </div>
                                        <button class="btn-submit">Submit Attendance</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="summary-section">
                    <h2 class="summary-title"><i class="fa-solid fa-chart-pie" style="color:#8056ff; font-size:20px;"></i> My Course Attendance</h2>
                    <div class="summary-grid">
                        <?php foreach($myCourses as $crs): 
                            $stats = getCourseStats($db, $studentID, $crs['courseID'], $sData['programID'], $sData['tutGroup']);
                            $total = $stats['total'] > 0 ? $stats['total'] : 1; 
                            $percent = round(($stats['attended'] / $total) * 100);
                            
                            // Color Logic
                            $wheelColor = '#10b981'; // Green
                            if ($percent < 80) $wheelColor = '#f59e0b'; // Orange
                            if ($percent < 50) $wheelColor = '#ef4444'; // Red
                        ?>
                        <div class="subject-card" onclick="openHistory('<?php echo $crs['courseID']; ?>', '<?php echo $crs['courseName']; ?>')">
                            <div class="subj-info">
                                <h4><?php echo $crs['courseName']; ?></h4>
                                <span class="subj-code"><?php echo $crs['courseID']; ?></span>
                                <div class="subj-stats">
                                    <i class="fa-solid fa-check-double"></i> <?php echo $stats['attended']; ?> / <?php echo $stats['total']; ?> Classes
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
                        container.innerHTML = '<p style="text-align:center; color:#888;">No attendance history found.</p>';
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
                        let scanStr = row.scanTime ? row.scanTime.substring(0,5) : '--:--';
                        
                        html += `
                        <div class="hist-row">
                            <div>
                                <span class="hist-date">${row.attendanceDate}</span>
                                <div class="hist-meta">${row.classType} • Starts ${timeStr}</div>
                                <div class="hist-meta" style="font-size:11px;">Scanned: ${scanStr}</div>
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
    </script>
</body>
</html>