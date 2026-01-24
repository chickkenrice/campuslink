<?php
session_start();
require_once '../includes/config.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit;
}

$db = get_db_connection();
$staffID = $_SESSION['user_id'];
$staffName = $_SESSION['user_name'];

// --- TIME SETTINGS ---
$currentDay = date('D');       
$currentTime = date('H:i:s');  
$currentDate = date('Y-m-d');

// 1. Find Active Class for Staff
$sqlClass = "SELECT s.*, c.courseName, c.courseID 
             FROM class_schedule s 
             JOIN course c ON s.courseID = c.courseID
             WHERE s.staffID = ? AND s.day = ? AND ? BETWEEN s.startTime AND s.endTime
             LIMIT 1";
$stmt = $db->prepare($sqlClass);
$stmt->bind_param("sss", $staffID, $currentDay, $currentTime);
$stmt->execute();
$activeClass = $stmt->get_result()->fetch_assoc();

$sessionData = null;
$studentCount = 0;

if ($activeClass) {
    // 2. Check for existing session
    $sqlSession = "SELECT * FROM attendance_sessions WHERE scheduleID = ? AND sessionDate = ?";
    $stmtS = $db->prepare($sqlSession);
    $stmtS->bind_param("is", $activeClass['scheduleID'], $currentDate);
    $stmtS->execute();
    $sessionData = $stmtS->get_result()->fetch_assoc();

    // 3. Create Session if missing
    if (!$sessionData) {
        $code = rand(100000, 999999);
        $ins = "INSERT INTO attendance_sessions (scheduleID, sessionDate, code) VALUES (?, ?, ?)";
        $stmtI = $db->prepare($ins);
        $stmtI->bind_param("iss", $activeClass['scheduleID'], $currentDate, $code);
        if($stmtI->execute()) header("Refresh:0");
    }

    // 4. Get Live Count
    $sqlCount = "SELECT COUNT(*) as c FROM attendance WHERE scheduleID = ? AND attendanceDate = ?";
    $stmtC = $db->prepare($sqlCount);
    $stmtC->bind_param("is", $activeClass['scheduleID'], $currentDate);
    $stmtC->execute();
    $studentCount = $stmtC->get_result()->fetch_assoc()['c'];
}

// Handle Stop
if(isset($_POST['stop_session']) && $sessionData) {
    $upd = "UPDATE attendance_sessions SET status = 'CLOSED' WHERE sessionID = ?";
    $stmtU = $db->prepare($upd);
    $stmtU->bind_param("i", $sessionData['sessionID']);
    $stmtU->execute();
    header("Refresh:0");
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Attendance — CAMPUSLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    
    <style>
        /* Specific Styles for Attendance Widgets */
        .glass-card {
            background: linear-gradient(135deg, #6c44f6 0%, #a786ff 100%);
            border-radius: 26px; padding: 40px; color: white;
            text-align: center; box-shadow: 0 20px 50px rgba(108, 68, 246, 0.4);
            position: relative; overflow: hidden; margin-bottom: 30px;
        }
        .glass-card::before {
            content:""; position:absolute; top:-50%; left:-50%; width:200%; height:200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            pointer-events: none;
        }
        .big-code {
            background: rgba(255,255,255,0.95); color: #6c44f6;
            font-size: 64px; font-weight: 800; font-family: monospace;
            padding: 20px 40px; border-radius: 20px; display: inline-block;
            letter-spacing: 15px; margin: 20px 0;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        .live-badge {
            background: #eefcf6; color: #0d9488; padding: 8px 16px; 
            border-radius: 20px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;
        }
        .live-dot { width: 8px; height: 8px; background: #0d9488; border-radius: 50%; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }
        
        /* Stats Row */
        .stats-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .stat-widget { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .btn-stop { background: #fee2e2; color: #991b1b; border: none; padding: 15px; width: 100%; border-radius: 14px; font-weight: 700; margin-top: 20px; cursor: pointer; }
        
        /* Sidebar Fix */
        .nav-chevron::after {
            content: ""; display: block; width: 10px; height: 10px;
            border-right: 2px solid rgba(255,255,255,0.75);
            border-bottom: 2px solid rgba(255,255,255,0.75);
            transform: rotate(-45deg); transition: transform 0.2s; margin-left: auto;
        }
        .nav-group[aria-expanded="true"] .nav-chevron::after { transform: rotate(45deg); }
        .nav-toggle { text-align: left; width: 100%; }
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
                        <span class="brand-tagline">Staff Console</span>
                    </div>
                </div>
                <nav class="sidebar-nav">
                    <ul>
                        <li>
                            <a href="staff-dashboard.php" class="nav-item" style="text-decoration: none;">
                                <span class="nav-icon"><i class="fa-solid fa-table-columns"></i></span>
                                <span class="nav-label">Dashboard</span>
                            </a>
                        </li>

                        <li class="nav-group" data-expandable>
                            <button class="nav-item nav-toggle" type="button" data-target="student-menu">
                                <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
                                <span class="nav-label">Student</span>
                                <span class="nav-chevron"></span>
                            </button>
                            <div class="nav-submenu" id="student-menu" hidden>
                                <a href="manage-students.php" class="nav-subitem">Manage Student</a>
                                <a href="staff-attendance.php" class="nav-subitem" style="background: rgba(255,255,255,0.2);">Attendance</a>
                                <a href="staff-assignments.php" class="nav-subitem">Assignment</a>
                            </div>
                        </li>

                        <li>
                            <a href="staff-timetable.php" class="nav-item" style="text-decoration: none;">
                                <span class="nav-icon"><i class="fa-solid fa-calendar-week"></i></span>
                                <span class="nav-label">My Timetable</span>
                            </a>
                        </li>

                        <li>
                            <a href="../logout.php" class="nav-item" style="text-decoration: none; margin-top: 20px; color: #e74c3c;">
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
                            <a href="staff-dashboard.php" class="profile-link" title="Back to Dashboard" style="text-decoration: none; cursor: pointer;">
                                <div class="profile-pic" style="width: 42px; height: 42px; border-radius: 50%; background: var(--purple-tint); display: grid; place-items: center; border: 2px solid var(--purple-soft); overflow: hidden;">
                                    <i class="fa-solid fa-user" style="color: var(--purple-base); font-size: 18px;"></i>
                                </div>
                            </a>

                            <div class="user-meta" style="text-align: right;">
                                <span class="user-name" style="display: block; font-weight: 600; color: var(--text-main);">
                                    <?php echo htmlspecialchars($staffName); ?>
                                </span>
                                <span class="user-status" style="font-size: 12px; color: var(--text-sub);">Staff</span>
                            </div>
                        </div>
                    </div>
                </header>

                <h1 style="margin: 0 0 20px;">Live Attendance</h1>

                <?php if ($activeClass && $sessionData): ?>
                    <div class="glass-card">
                        <div style="opacity:0.9; text-transform:uppercase; font-weight:600; margin-bottom:10px;">
                            <?php echo $activeClass['courseID']; ?> — <?php echo $activeClass['tutGroup']; ?>
                        </div>
                        <h2 style="font-size:32px; margin:0 0 20px;"><?php echo $activeClass['courseName']; ?></h2>
                        
                        <?php if ($sessionData['status'] === 'OPEN'): ?>
                            <div class="big-code"><?php echo $sessionData['code']; ?></div>
                            <div><span class="live-badge"><div class="live-dot"></div> Session Active</span></div>
                        <?php else: ?>
                            <div class="big-code" style="color:#dc2626; letter-spacing: 5px;">CLOSED</div>
                        <?php endif; ?>
                    </div>

                    <div class="stats-row">
                        <div class="stat-widget">
                            <div>
                                <h3 style="margin:0; font-size:32px;"><?php echo $studentCount; ?></h3>
                                <span style="color:#888;">Students Joined</span>
                            </div>
                            <i class="fa-solid fa-users" style="font-size:30px; color:#6c44f6;"></i>
                        </div>
                        <div class="stat-widget">
                            <div>
                                <h3 style="margin:0; font-size:32px;">-- %</h3>
                                <span style="color:#888;">Attendance Rate</span>
                            </div>
                            <i class="fa-solid fa-chart-pie" style="font-size:30px; color:#0d9488;"></i>
                        </div>
                    </div>

                    <?php if ($sessionData['status'] === 'OPEN'): ?>
                        <form method="POST"><button name="stop_session" class="btn-stop">Stop Attendance Session</button></form>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="stat-widget" style="display:block; text-align:center; padding:50px;">
                        <i class="fa-solid fa-mug-hot" style="font-size:40px; color:#ddd; margin-bottom:20px;"></i>
                        <h3>No Active Class</h3>
                        <p style="color:#888;">You don't have a class scheduled right now.</p>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    <script src="../script.js"></script>
</body>
</html>