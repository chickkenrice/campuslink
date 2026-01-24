<?php
session_start();
// Adjust path to config based on folder structure
require_once '../includes/config.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit;
}

$db = get_db_connection();
$staffID = $_SESSION['user_id'];
$staffName = $_SESSION['user_name'] ?? 'Staff Member';

// --- DATA CALCULATION ---

// Metric 1: Total Classes
$sqlClasses = "SELECT COUNT(*) as total_classes FROM class_schedule WHERE staffID = ?";
$stmtC = $db->prepare($sqlClasses);
$stmtC->bind_param("s", $staffID);
$stmtC->execute();
$totalClasses = $stmtC->get_result()->fetch_assoc()['total_classes'];

// Metric 2: Total Students
$sqlStudents = "SELECT COUNT(DISTINCT s.studentID) as total_students
                FROM student s
                JOIN class_schedule cs ON (s.programID = cs.programID AND (s.tutGroup = cs.tutGroup OR cs.tutGroup = 'Combined'))
                WHERE cs.staffID = ?";
$stmtS = $db->prepare($sqlStudents);
$stmtS->bind_param("s", $staffID);
$stmtS->execute();
$totalStudents = $stmtS->get_result()->fetch_assoc()['total_students'];

// Dynamic Greeting
$hour = date('H');
if ($hour < 12) $greeting = "Good morning";
elseif ($hour < 18) $greeting = "Good afternoon";
else $greeting = "Good evening";
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff Dashboard — CAMPUSLink</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    
    <style>
        /* 1. Metrics Grid Layout */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }

        /* 2. The Interactive Card Design */
        .metric-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            justify-content: center;
            /* Smooth transition for all properties (color, background, shadow) */
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            cursor: default;
            position: relative;
            overflow: hidden;
        }

        /* --- HOVER STATE (THE MAGIC) --- */
        .metric-card:hover {
            /* Change to Purple Gradient */
            background: linear-gradient(135deg, #8056ff 0%, #6b45f5 100%);
            /* Lift up slightly */
            transform: translateY(-5px);
            /* Add purple glow shadow */
            box-shadow: 0 20px 40px rgba(107, 69, 245, 0.3);
            border-color: transparent;
        }

        /* 3. Typography Defaults */
        .metric-label {
            font-size: 15px;
            font-weight: 600;
            color: #8d90aa;
            margin: 0 0 12px 0;
            display: flex; align-items: center; gap: 10px;
            transition: color 0.3s;
        }
        
        /* Icon Default (Purple to stand out on white) */
        .metric-label i {
            color: #8056ff;
            font-size: 18px;
            transition: color 0.3s;
        }

        .metric-value {
            font-size: 42px;
            font-weight: 700;
            color: #181b2f;
            margin: 0 0 6px 0;
            line-height: 1;
            transition: color 0.3s;
        }

        .metric-sub {
            font-size: 13px;
            font-weight: 500;
            color: #a0a3bd;
            transition: color 0.3s;
        }

        /* --- TEXT COLORS ON HOVER (TURN WHITE) --- */
        .metric-card:hover .metric-label,
        .metric-card:hover .metric-label i,
        .metric-card:hover .metric-value,
        .metric-card:hover .metric-sub {
            color: #ffffff;
        }

        /* Sidebar Toggle Logic */
        .nav-chevron::after {
            content: ""; display: block; width: 10px; height: 10px;
            border-right: 2px solid rgba(255,255,255,0.75);
            border-bottom: 2px solid rgba(255,255,255,0.75);
            transform: rotate(-45deg); transition: transform 0.2s;
            margin-left: auto;
        }
        .nav-group[aria-expanded="true"] .nav-chevron::after,
        .nav-group.is-open .nav-chevron::after {
            transform: rotate(45deg);
        }
        .nav-toggle { text-align: left; width: 100%; }
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
                            <a href="staff-dashboard.php" class="nav-item is-active">
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
                                <a href="staff-attendance.php" class="nav-subitem">Attendance</a>
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

                <section class="welcome-card">
                    <div class="welcome-details">
                        <p class="welcome-date"><?php echo date('l, F j, Y'); ?></p>
                        <h1 class="welcome-title"><?php echo $greeting . ", " . htmlspecialchars($staffName); ?>!</h1>
                        <p class="welcome-text">Here is an overview of your classes, students, and upcoming tasks.</p>
                    </div>
                </section>

                <section class="metrics-grid">
                    <article class="metric-card">
                        <p class="metric-label"><i class="fa-solid fa-users"></i> Total Students</p>
                        <p class="metric-value"><?php echo $totalStudents; ?></p>
                        <span class="metric-sub">Across all your groups</span>
                    </article>

                    <article class="metric-card">
                        <p class="metric-label"><i class="fa-regular fa-calendar-check"></i> Total Classes</p>
                        <p class="metric-value"><?php echo $totalClasses; ?></p>
                        <span class="metric-sub">Scheduled per week</span>
                    </article>

                    <article class="metric-card">
                        <p class="metric-label"><i class="fa-regular fa-file-lines"></i> Pending Tasks</p>
                        <p class="metric-value">0</p>
                        <span class="metric-sub">No pending assignments</span>
                    </article>
                </section>

                <section class="deadlines-card">
                    <div class="announcements-header">
                        <h2>Upcoming Activities</h2>
                    </div>
                    <ul class="deadline-list">
                        <li class="deadline-item">
                            <p class="deadline-label">Mid-Sem Submission</p>
                            <span class="deadline-date">Nov 15, 2025</span>
                        </li>
                        <li class="deadline-item">
                            <p class="deadline-label">Faculty Meeting</p>
                            <span class="deadline-date">Nov 20, 2025</span>
                        </li>
                    </ul>
                </section>

            </main>
        </div>
    </div>

    <script src="../script.js"></script>
</body>
</html>