<?php
session_start();
// Adjust path to config based on folder structure
require_once '../includes/config.php';

// 1. Security Check
if (!validate_session() || $_SESSION['role'] !== 'staff') {
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

// Metric 3: Active Assignments (deadline not yet passed)
$sqlAssign = "SELECT COUNT(*) as active_assignments FROM assignments WHERE staffID = ? AND deadline >= NOW()";
$stmtA = $db->prepare($sqlAssign);
$stmtA->bind_param("s", $staffID);
$stmtA->execute();
$activeAssignments = $stmtA->get_result()->fetch_assoc()['active_assignments'];

// Dynamic Greeting
$hour = date('H');
if ($hour < 12) $greeting = "Good morning";
elseif ($hour < 18) $greeting = "Good afternoon";
else $greeting = "Good evening";
?>
<!doctype html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../favicon2.png">
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

        .nav-toggle { text-align: left; width: 100%; }
    </style>
</head>

<body>
    <div class="app-bg">
        <div class="main-card">
            
            <?php $currentPage = 'dashboard'; include __DIR__ . '/../includes/staffMaster.php'; ?>

            <main class="dashboard">
                
                <header class="dashboard-topbar">
                    <div class="topbar-right">
                        <?php include __DIR__ . '/../includes/notificationBell.php'; ?>
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

                <section class="welcome-card">
                    <div class="welcome-details">
                        <p class="welcome-date"><?php echo date('l, j F Y'); ?></p>
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
                        <p class="metric-label"><i class="fa-regular fa-file-lines"></i> Active Assignments</p>
                        <p class="metric-value"><?php echo $activeAssignments; ?></p>
                        <span class="metric-sub"><?php echo $activeAssignments === 0 ? 'No active assignments' : ($activeAssignments === 1 ? '1 assignment open' : $activeAssignments . ' assignments open'); ?></span>
                    </article>
                </section>

                <section class="deadlines-card">
                    <div class="announcements-header">
                        <h2>Announcements</h2>
                        <div class="actions">
                            <button id="refreshAnnBtn" class="btn" type="button" onclick="loadStaffAnnouncements()">Refresh</button>
                            <a class="see-all" href="announcements.php">See all</a>
                        </div>
                    </div>
                    <div id="staff-announcements" class="announcements" style="padding: 15px 0;">
                        <p class="hint" style="color: #94a3b8; text-align: center;">Loading announcements...</p>
                    </div>
                </section>

                <?php include __DIR__ . '/../includes/footer.php'; ?>
            </main>
        </div>
    </div>

    <script src="../script.js"></script>
    <script>
        // Load staff announcements
        async function loadStaffAnnouncements() {
            const container = document.getElementById('staff-announcements');
            container.innerHTML = '<p class="hint" style="color: #94a3b8; text-align: center;">Loading...</p>';
            
            try {
                const res = await fetch('../api/announcements.php?audience=staff');
                const data = await res.json();
                
                if (data.success && data.data.length > 0) {
                    container.innerHTML = data.data.slice(0, 10).map(a => {
                        const annType = a.announcementType || 'system';
                        const typeConfig = {
                            system: { icon: 'fa-gear', label: 'System', bg: '#f3e8ff', color: '#7c3aed' },
                            academic: { icon: 'fa-graduation-cap', label: 'Academic', bg: '#dbeafe', color: '#1d4ed8' },
                            campus: { icon: 'fa-building-columns', label: 'Campus', bg: '#dcfce7', color: '#15803d' }
                        };
                        const config = typeConfig[annType] || typeConfig.system;
                        const isPinned = a.isPinned === 1 || a.isPinned === '1';
                        const pinIcon = isPinned ? '<i class="fa-solid fa-thumbtack" style="color:#f59e0b;margin-right:6px;"></i>' : '';
                        
                        return `
                        <a href="announcements.php?id=${a.id}" class="announcement-item" style="display: flex; align-items: center; padding: 12px 15px; margin-bottom: 8px; border-radius: 8px; background: #fff; border: 1px solid #eee; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='#f8f5ff'; this.style.borderColor='#c4b5fd'; this.style.transform='translateX(4px)';" onmouseout="this.style.background='#fff'; this.style.borderColor='#eee'; this.style.transform='none';">
                            <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:5px;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.3px;margin-right:10px;flex-shrink:0;background:${config.bg};color:${config.color};">
                                <i class="fa-solid ${config.icon}"></i>${config.label}
                            </span>
                            <span style="font-weight: 600; color: #333; font-size: 14px; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${pinIcon}${escapeHtml(a.title)}</span>
                            <span style="font-size: 12px; color: #94a3b8; margin-left: 12px; white-space: nowrap;">${formatAnnDate(a.createdAt)}</span>
                            <i class="fa-solid fa-chevron-right" style="color: #c4b5fd; margin-left: 10px; font-size: 12px;"></i>
                        </a>
                    `}).join('');
                } else {
                    container.innerHTML = '<p class="hint" style="color: #94a3b8; text-align: center;">No announcements available.</p>';
                }
            } catch (err) {
                container.innerHTML = '<p class="hint" style="color: #dc2626; text-align: center;">Failed to load announcements.</p>';
            }
        }
        
        function formatAnnDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Load on page ready
        document.addEventListener('DOMContentLoaded', loadStaffAnnouncements);
    </script>
</body>
</html>