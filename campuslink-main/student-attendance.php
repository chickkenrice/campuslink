<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$db = get_db_connection();
$studentID = $_SESSION['user_id'];
date_default_timezone_set('Asia/Kuala_Lumpur');
$currentDay = date('l'); $currentTime = date('H:i:s'); $currentDate = date('Y-m-d');

// 1. Get Student Details
$stmtS = $db->prepare("SELECT programID, tutGroup FROM student WHERE studentID = ?");
$stmtS->bind_param("s", $studentID);
$stmtS->execute();
$sData = $stmtS->get_result()->fetch_assoc();

// 2. Fetch Classes
$sql = "SELECT s.*, c.courseName, c.courseID, st.staffName, f.facilityName,
       (SELECT COUNT(*) FROM attendance a WHERE a.scheduleID = s.scheduleID AND a.studentID = ? AND a.attendanceDate = ?) as isAttended
       FROM class_schedule s
       JOIN course c ON s.courseID = c.courseID
       JOIN staff st ON s.staffID = st.staffID
       JOIN facility f ON s.facilityID = f.facilityID
       WHERE s.programID = ? AND (s.tutGroup = ? OR s.tutGroup = 'Combined') AND s.day = ?
       ORDER BY s.startTime ASC";

$stmt = $db->prepare($sql);
$stmt->bind_param("sssss", $studentID, $currentDate, $sData['programID'], $sData['tutGroup'], $currentDay);
$stmt->execute();
$todaysClasses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Handle Submit
$msg = ""; $msgType = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedID = $_POST['scheduleID'];
    $code = implode('', $_POST['digit']); 
    $check = $db->query("SELECT * FROM attendance WHERE studentID='$studentID' AND scheduleID='$schedID' AND attendanceDate='$currentDate'");
    
    if ($check->num_rows > 0) { $msg = "Already attended."; $msgType = "success"; } else {
        $sess = $db->prepare("SELECT * FROM attendance_sessions WHERE scheduleID=? AND sessionDate=? AND code=? AND status='OPEN'");
        $sess->bind_param("iss", $schedID, $currentDate, $code);
        $sess->execute();
        if ($sess->get_result()->fetch_assoc()) {
            $ins = $db->prepare("INSERT INTO attendance (studentID, scheduleID, attendanceDate, scanTime, status) VALUES (?, ?, ?, ?, 'Present')");
            $ins->bind_param("siss", $studentID, $schedID, $currentDate, $currentTime);
            $ins->execute();
            header("Refresh:0"); 
        } else { $msg = "Invalid or expired code."; $msgType = "error"; }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Classes — CAMPUSLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo time(); ?>">
    <style>
        /* Modern Grid Layout */
        .class-list { max-width: 100%; display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 25px; margin-bottom: 40px; }
        
        /* Light Purple Card */
        .class-card {
            background: #fcfaff; /* Very light purple tint */
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 10px 40px rgba(200, 200, 230, 0.15);
            border: 1px solid #ebe5ff; /* Subtle purple border */
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative; overflow: hidden;
            display: flex; flex-direction: column; gap: 15px;
        }
        .class-card:hover { transform: translateY(-5px); box-shadow: 0 15px 50px rgba(128, 86, 255, 0.15); border-color: #dcd0ff; }

        /* Status Colors with Purple Accents */
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
        .btn-submit:hover { background: #6b45f5; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(128,86,255,0.3); }
        
        .alert { padding: 12px; border-radius: 10px; text-align: center; font-weight: 600; margin-bottom: 20px; }
        .alert.error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .alert.success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }

        /* VIBRANT SUMMARY SECTION */
        .summary-section { margin-top: 50px; display: flex; flex-direction: column; gap: 15px; }
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
        .sum-meta { font-size: 13px; color: #6b7280; }
        
        /* Colorful Progress Circle */
        .progress-circle {
            position: relative; width: 50px; height: 50px; border-radius: 50%;
            background: conic-gradient(var(--c) calc(var(--p)*1%), #f3f4f6 0);
            display: flex; align-items: center; justify-content: center;
        }
        .progress-circle::before { content: ""; position: absolute; inset: 6px; background: white; border-radius: 50%; }
        .progress-icon { position: relative; font-size: 16px; }
    </style>
</head>
<body>
    <div class="app-bg">
        <div class="main-card">
            <aside class="sidebar" aria-label="Primary navigation">
                <div class="sidebar-head">
                    <div class="brand-icon">CL</div>
                    <div class="brand-text">
                        <span class="brand-name">CAMPUSLink</span>
                        <span class="brand-tagline">Student Portal</span>
                    </div>
                </div>
                <nav class="sidebar-nav">
                    <ul>
                        <li>
                            <a href="index.php" class="nav-item" style="text-decoration: none;">
                                <span class="nav-icon"><i class="fa-solid fa-table-columns"></i></span>
                                <span class="nav-label">Dashboard</span>
                            </a>
                        </li>

                        <li>
                            <a href="student-attendance.php" class="nav-item is-active" style="text-decoration: none;">
                                <span class="nav-icon"><i class="fa-solid fa-clock"></i></span>
                                <span class="nav-label">Attendance</span>
                            </a>
                        </li>

                        <li>
                            <a href="student-timetable.php" class="nav-item" style="text-decoration: none;">
                                <span class="nav-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                <span class="nav-label">My Timetable</span>
                            </a>
                        </li>

                        <li class="nav-group" data-expandable>
                            <button class="nav-item nav-toggle" type="button" data-target="programme-panel">
                                <span class="nav-icon"><i class="fa-solid fa-graduation-cap"></i></span>
                                <span class="nav-label">Programme</span>
                                <span class="nav-chevron"></span>
                            </button>
                            <div class="nav-submenu" id="programme-panel" hidden>
                                <a href="programme-structure.php" class="nav-subitem">Programme Structure</a>
                                <a href="#" class="nav-subitem">Course Enrollment</a>
                                <a href="#" class="nav-subitem">Results</a>
                            </div>
                        </li>

                        <li class="nav-group" data-expandable>
                            <button class="nav-item nav-toggle" type="button" data-target="examination-panel">
                                <span class="nav-icon"><i class="fa-solid fa-book"></i></span>
                                <span class="nav-label">Examination</span>
                                <span class="nav-chevron"></span>
                            </button>
                            <div class="nav-submenu" id="examination-panel" hidden>
                                <a href="#" class="nav-subitem">Exam Slip</a>
                                <a href="#" class="nav-subitem">Exam Schedule</a>
                            </div>
                        </li>

                        <li>
                            <a href="logout.php" class="nav-item">
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
                        <label class="sr-only" for="dashboard-search">Search</label>
                        <input id="dashboard-search" type="search" placeholder="Search" autocomplete="off">
                    </div>
                    
                    <div class="topbar-right">
                        <div class="user-card" style="display: flex; align-items: center; gap: 12px; flex-direction: row-reverse;">
                            <a href="student-profile.php" class="profile-link" title="View Profile" style="text-decoration: none; cursor: pointer;">
                                <div class="profile-pic" style="width: 42px; height: 42px; border-radius: 50%; background: var(--purple-tint); display: grid; place-items: center; border: 2px solid var(--purple-soft); overflow: hidden;">
                                    <i class="fa-solid fa-user" style="color: var(--purple-base); font-size: 18px;"></i>
                                </div>
                            </a>

                            <div class="user-meta" style="text-align: right;">
                                <span class="user-name" style="display: block; font-weight: 600; color: var(--text-main);">
                                    <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                                </span>
                                <span class="user-status" style="font-size: 12px; color: var(--text-sub);">Student</span>
                            </div>
                        </div>
                    </div>
                </header>

                <?php if($msg): ?><div class="alert <?php echo $msgType; ?>"><?php echo $msg; ?></div><?php endif; ?>

                <div class="class-list">
                    <?php 
                    $hasClasses = false;
                    if (empty($todaysClasses)): ?>
                        <div style="text-align: center; padding: 50px; color: #888;">
                            <i class="fa-solid fa-mug-hot" style="font-size: 40px; margin-bottom: 15px;"></i>
                            <p>No classes scheduled for today.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($todaysClasses as $cls): 
                            $hasClasses = true;
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
                                            <?php for($i=0; $i<6; $i++): ?>
                                                <input type="text" name="digit[]" class="digit-box" maxlength="1" required autocomplete="off">
                                            <?php endfor; ?>
                                        </div>
                                        <button class="btn-submit">Submit Attendance</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if($hasClasses): ?>
                <div class="summary-section">
                    <h2 class="summary-title">My Daily Summary</h2>
                    <?php foreach ($todaysClasses as $cls):
                        if ($currentTime > $cls['endTime']): // Only show finished classes
                            $attended = $cls['isAttended'] > 0;
                            $color = $attended ? '#10b981' : '#ef4444';
                            $percent = $attended ? 100 : 0;
                            $icon = $attended ? '<i class="fa-solid fa-check" style="color:#10b981"></i>' : '<i class="fa-solid fa-xmark" style="color:#ef4444"></i>';
                    ?>
                    <div class="summary-row">
                        <div class="sum-info">
                            <h4><?php echo $cls['courseName']; ?></h4>
                            <div class="sum-meta"><?php echo substr($cls['startTime'], 0, 5); ?> • <?php echo $cls['staffName']; ?></div>
                        </div>
                        <div class="progress-circle" style="--p:<?php echo $percent; ?>; --c:<?php echo $color; ?>;">
                            <div class="progress-icon"><?php echo $icon; ?></div>
                        </div>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
                <?php endif; ?>

            </main>
        </div>
    </div>
    <script>
        const inputs = document.querySelectorAll('.digit-box');
        inputs.forEach((inp, i) => {
            inp.addEventListener('input', e => { if(e.target.value) inputs[i+1]?.focus(); });
            inp.addEventListener('keydown', e => { if(e.key==='Backspace' && !e.target.value) inputs[i-1]?.focus(); });
        });
    </script>
</body>
</html>