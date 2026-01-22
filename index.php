<?php
session_start();
require_once(__DIR__ . '/includes/config.php'); // Ensure correct path to config

// 1. SECURITY CHECK
// Ensure user is logged in AND is a Student (case-insensitive check)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strcasecmp($_SESSION['role'], 'student') !== 0) {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['user_id'];
$db = get_db_connection();

// 2. FETCH STUDENT DETAILS
// We use the studentID (which is the userID) to get the specific details
$stmt = $db->prepare("SELECT studentName, tutGroup, programme FROM student WHERE studentID = ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $fullName = $row['studentName'];
    $tutGroup = $row['tutGroup'];
    $programme = $row['programme'];
} else {
    // Fallback if not found in student table
    $fullName = $_SESSION['user_name'] ?? 'Student';
    $tutGroup = 'N/A';
    $programme = 'N/A';
}

// Prepare first name for welcome message
$firstName = explode(' ', trim($fullName))[0];

// 3. FETCH ANNOUNCEMENTS
$ann_sql = "SELECT title, body, created_at FROM announcements ORDER BY created_at DESC LIMIT 3";
$announcements = $db->query($ann_sql);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CAMPUSLink — Student Dashboard</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/styles.css">
    
    <style>
        /* Extra styles for announcements */
        .announcement-item {
            background: #fff;
            border: 1px solid #eee;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            border-left: 4px solid #6c5ce7;
        }
        .ann-date { font-size: 0.8rem; color: #888; margin-bottom: 4px; display:block; }
        .ann-title { font-weight: 700; color: #333; margin-bottom: 6px; display:block; font-size: 1rem; }
        .ann-body { color: #555; font-size: 0.9rem; line-height: 1.4; }
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
                            <a href="index.php" class="nav-item is-active" style="text-decoration: none;">
                                <span class="nav-icon"><i class="fa-solid fa-table-columns"></i></span>
                                <span class="nav-label">Dashboard</span>
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
                        <label class="sr-only" for="dashboard-search">Search</label>
                        <input id="dashboard-search" type="search" placeholder="Search" autocomplete="off">
                    </div>
                    
                    <div class="topbar-right">
                        <div class="user-card" style="display: flex; align-items: center; gap: 12px; flex-direction: row-reverse;">
                            <a href="student-profile.php" class="profile-link" title="View Profile">
                                <div class="profile-pic" style="width: 42px; height: 42px; border-radius: 50%; background: #e0e7ff; display: grid; place-items: center; border: 2px solid #c7d2fe; overflow: hidden;">
                                    <i class="fa-solid fa-user" style="color: #4f46e5; font-size: 18px;"></i>
                                </div>
                            </a>

                            <div class="user-meta" style="text-align: right;">
                                <span class="user-name" style="display: block; font-weight: 600; color: #333;">
                                    <?php echo htmlspecialchars($fullName); ?>
                                </span>
                                <span class="user-status" style="font-size: 12px; color: #666;">
                                    <?php echo htmlspecialchars($programme . " | " . $tutGroup); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </header>

                <section class="welcome-card">
                    <div class="welcome-details">
                        <p class="welcome-date"><?php echo date("F j, Y"); ?></p>
                        <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($firstName); ?>!</h1>
                        <p class="welcome-text">Always stay updated in your student portal.</p>
                    </div>
                </section>

                <section class="announcements-card">
                    <header class="announcements-header">
                        <h2>Announcements</h2>
                        <div class="actions">
                            <button id="refreshBtn" class="btn" type="button" onclick="location.reload();">Refresh</button>
                            <a class="see-all" href="#">See all</a>
                        </div>
                    </header>
                    
                    <div id="announcements" class="announcements">
                        <?php if ($announcements && $announcements->num_rows > 0): ?>
                            <?php while($row = $announcements->fetch_assoc()): ?>
                                <div class="announcement-item">
                                    <span class="ann-date">
                                        <i class="fa-regular fa-clock"></i> 
                                        <?php echo date("M j, Y • g:i A", strtotime($row['created_at'])); ?>
                                    </span>
                                    <span class="ann-title"><?php echo htmlspecialchars($row['title']); ?></span>
                                    <div class="ann-body">
                                        <?php echo nl2br(htmlspecialchars($row['body'])); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="hint">No announcements available at the moment.</p>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script>
        document.querySelectorAll('.nav-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                const targetPanel = document.getElementById(targetId);
                const isExpanded = btn.getAttribute('aria-expanded') === 'true';
                
                // Toggle visibility
                targetPanel.hidden = isExpanded;
                btn.setAttribute('aria-expanded', !isExpanded);
                
                // Toggle arrow rotation if you have CSS for .is-open
                btn.parentElement.classList.toggle('is-open', !isExpanded);
            });
        });
        
        // Simple Date Script
        // document.getElementById('welcomeDate').textContent = new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    </script>
</body>
</html>