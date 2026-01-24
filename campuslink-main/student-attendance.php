<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$db = get_db_connection();
$studentID = $_SESSION['user_id'];
$currentDay = date('D'); $currentTime = date('H:i:s'); $currentDate = date('Y-m-d');

// 1. Get Student Info
$stmtS = $db->prepare("SELECT programID, tutGroup FROM student WHERE studentID = ?");
$stmtS->bind_param("s", $studentID);
$stmtS->execute();
$sData = $stmtS->get_result()->fetch_assoc();

// 2. Find Active Class
$sqlClass = "SELECT s.*, c.courseName 
             FROM class_schedule s 
             JOIN course c ON s.courseID = c.courseID
             WHERE s.day = ? AND ? BETWEEN s.startTime AND s.endTime
             AND s.programID = ? AND (s.tutGroup = ? OR s.tutGroup = 'Combined') LIMIT 1";
$stmt = $db->prepare($sqlClass);
$stmt->bind_param("ssss", $currentDay, $currentTime, $sData['programID'], $sData['tutGroup']);
$stmt->execute();
$activeClass = $stmt->get_result()->fetch_assoc();

$msg = ""; $msgType = "";

// 3. Handle Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $activeClass) {
    $code = implode('', $_POST['digit']);
    
    // Check duplication
    $chk = $db->prepare("SELECT * FROM attendance WHERE studentID=? AND scheduleID=? AND attendanceDate=?");
    $chk->bind_param("sis", $studentID, $activeClass['scheduleID'], $currentDate);
    $chk->execute();
    
    if($chk->get_result()->num_rows > 0) {
        $msg = "You already attended!"; $msgType = "error";
    } else {
        // Validate Code
        $sess = $db->prepare("SELECT * FROM attendance_sessions WHERE scheduleID=? AND sessionDate=? AND code=? AND status='OPEN'");
        $sess->bind_param("iss", $activeClass['scheduleID'], $currentDate, $code);
        $sess->execute();
        
        if($sess->get_result()->fetch_assoc()) {
            $ins = $db->prepare("INSERT INTO attendance (studentID, scheduleID, attendanceDate, scanTime) VALUES (?, ?, ?, ?)");
            $ins->bind_param("siss", $studentID, $activeClass['scheduleID'], $currentDate, $currentTime);
            $ins->execute();
            $msg = "Success! Attendance Marked."; $msgType = "success";
        } else {
            $msg = "Invalid Code or Session Closed."; $msgType = "error";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Attendance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo time(); ?>">
    <style>
        .input-group { display: flex; justify-content: center; gap: 10px; margin: 30px 0; }
        .digit-box { width: 50px; height: 60px; font-size: 24px; text-align: center; border: 2px solid #ddd; border-radius: 12px; font-weight: 700; }
        .digit-box:focus { border-color: #8056ff; outline: none; }
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; text-align: center; }
        .alert.success { background: #d1fae5; color: #065f46; }
        .alert.error { background: #fee2e2; color: #991b1b; }
        .btn-submit { background: #8056ff; color: white; width: 100%; padding: 15px; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; }
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
                                <span class="nav-icon"><i class="fa-solid fa-clipboard-list"></i></span>
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
                            <a href="logout.php" class="nav-item" style="text-decoration: none; margin-top: 20px; color: #e74c3c;">
                                <span class="nav-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                                <span class="nav-label">Logout</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </aside>

            <main class="dashboard" aria-label="Dashboard">
                <header class="dashboard-topbar">
                    <div class="search-box">
                        <label class="sr-only" for="attendance-search">Search</label>
                        <input id="attendance-search" type="search" placeholder="Search" autocomplete="off">
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

                <h1 style="margin-bottom: 20px;">Submit Attendance</h1>

                <?php if($msg): ?><div class="alert <?php echo $msgType; ?>"><?php echo $msg; ?></div><?php endif; ?>

                <?php if($activeClass): ?>
                    <div style="background:white; padding:40px; border-radius:26px; text-align:center; box-shadow:0 10px 30px rgba(0,0,0,0.05);">
                        <span style="background:#eefcf6; color:#0d9488; padding:5px 12px; border-radius:20px; font-weight:700;">LIVE CLASS</span>
                        <h2 style="color:#6b45f5; margin:15px 0 5px;"><?php echo $activeClass['courseID']; ?></h2>
                        <p style="color:#888;"><?php echo $activeClass['courseName']; ?></p>
                        
                        <form method="POST">
                            <div class="input-group">
                                <?php for($i=0; $i<6; $i++): ?>
                                    <input type="text" name="digit[]" class="digit-box" maxlength="1" required>
                                <?php endfor; ?>
                            </div>
                            <button class="btn-submit">Submit Code</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding:50px; background:white; border-radius:20px;">
                        <h3>No Active Class</h3>
                        <p style="color:#888;">Relax! You have no classes right now.</p>
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