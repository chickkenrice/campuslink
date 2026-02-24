<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}
prevent_back_button_cache();

$db = get_db_connection();
$studentID = $_SESSION['user_id'];
$studentName = $_SESSION['user_name'];

// 1. Get Student Program & Group Info
$stmtS = $db->prepare("SELECT programID, tutGroup, currentYear, currentSemester, studentImage FROM student WHERE studentID = ?");
$stmtS->bind_param("s", $studentID);
$stmtS->execute();
$sData = $stmtS->get_result()->fetch_assoc();
$myGroup = $sData['tutGroup'];
$studentImage = $sData['studentImage'] ?? null;

// 2. Fetch ALL Assignments (Filtered by Group)
$sql = "SELECT a.*, c.courseName, s.staffName,
        sub.status as subStatus, sub.grade, sub.submittedAt
        FROM assignments a
        JOIN course c ON a.courseID = c.courseID
        JOIN staff s ON a.staffID = s.staffID
        JOIN program_course pc ON a.courseID = pc.courseID
        LEFT JOIN submissions sub ON a.assignmentID = sub.assignmentID AND sub.studentID = ?
        WHERE pc.programID = ? 
        AND pc.year = ? 
        AND pc.semester = ?
        AND (a.tutGroup = ? OR a.tutGroup = 'All' OR a.tutGroup = 'Combined')
        ORDER BY a.deadline ASC";

$stmt = $db->prepare($sql);
$stmt->bind_param("ssiis", $studentID, $sData['programID'], $sData['currentYear'], $sData['currentSemester'], $myGroup);
$stmt->execute();
$allAssignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Categorize Assignments into Buckets
$listAll = $allAssignments; 
$listDueSoon = [];
$listSubmitted = [];
$listGraded = [];

$now = time();
$threeDays = 3 * 24 * 60 * 60; // 3 days in seconds

foreach($allAssignments as $row) {
    $deadlineTs = strtotime($row['deadline']);
    $isSubmitted = !empty($row['subStatus']);
    
    // Bucket: Graded
    if ($row['subStatus'] == 'Graded') {
        $listGraded[] = $row;
    }
    
    // Bucket: Submitted (includes Late & Graded)
    if ($isSubmitted) {
        $listSubmitted[] = $row;
    }
    
    // Bucket: Due Soon (Not submitted AND deadline is within 3 days from now AND not overdue)
    // Note: If it's overdue, it stays in "All" but won't appear in "Due Soon" to avoid clutter
    if (!$isSubmitted && ($deadlineTs > $now) && ($deadlineTs - $now <= $threeDays)) {
        $listDueSoon[] = $row;
    }
}

// Helper Function to Render Cards (To avoid code duplication)
function renderGrid($list) {
    if(empty($list)): ?>
        <div style="grid-column:1/-1; text-align:center; padding:50px; color:#9ca3af;">
            <i class="fa-solid fa-folder-open" style="font-size:40px; margin-bottom:10px; color:#d1d5db;"></i>
            <p>No assignments found.</p>
        </div>
    <?php else: 
        foreach($list as $row): 
            $deadlineTs = strtotime($row['deadline']);
            $now = time();
            $diffSecs = $deadlineTs - $now;
            $daysOverdue = abs(floor($diffSecs / 86400));
            
            // Compare calendar dates for accurate "today/tomorrow" detection
            $deadlineDate = date('Y-m-d', $deadlineTs);
            $todayDate = date('Y-m-d');
            $tomorrowDate = date('Y-m-d', strtotime('+1 day'));
            $daysLeft = (int)ceil($diffSecs / 86400);
            if ($deadlineDate === $todayDate) { $daysLeft = 0; }
            elseif ($deadlineDate === $tomorrowDate) { $daysLeft = 1; }
            
            // Format deadline date nicely
            $deadlineFormatted = date('j M Y \a\t g:i A', $deadlineTs);
            
            // Determine Visual Style based on Status
            $barClass = 'st-pending'; 
            $statusBadge = ''; 
            $titleStyle = '';
            $deadlineIcon = 'fa-regular fa-clock';
            $deadlineText = '';
            $deadlineColor = '#6b7280';
            
            if(!$row['subStatus']) {
                // Not Submitted
                if($diffSecs < 0) {
                    // OVERDUE
                    $barClass = 'st-late';
                    $statusBadge = '<span style="font-size:11px; font-weight:700; color:#dc2626; background:#fef2f2; padding:2px 8px; border-radius:6px;">MISSING</span>';
                    $deadlineIcon = 'fa-solid fa-triangle-exclamation';
                    $deadlineText = $daysOverdue == 0 ? 'Due was today' : 'Overdue by ' . $daysOverdue . ' day' . ($daysOverdue > 1 ? 's' : '');
                    $deadlineColor = '#dc2626';
                } elseif($daysLeft == 0) {
                    // Due today
                    $barClass = 'st-late';
                    $statusBadge = '<span style="font-size:11px; font-weight:700; color:#dc2626; background:#fef2f2; padding:2px 8px; border-radius:6px;">DUE TODAY</span>';
                    $deadlineIcon = 'fa-solid fa-fire';
                    $deadlineText = 'Due at ' . date('g:i A', $deadlineTs);
                    $deadlineColor = '#dc2626';
                } elseif($daysLeft == 1) {
                    // Due tomorrow
                    $statusBadge = '<span style="font-size:11px; font-weight:700; color:#d97706; background:#fffbeb; padding:2px 8px; border-radius:6px;">DUE TOMORROW</span>';
                    $deadlineIcon = 'fa-solid fa-hourglass-half';
                    $deadlineText = 'Due tomorrow at ' . date('g:i A', $deadlineTs);
                    $deadlineColor = '#d97706';
                } elseif($daysLeft <= 3) {
                    // Due soon (within 3 days)
                    $statusBadge = '<span style="font-size:11px; font-weight:700; color:#d97706; background:#fffbeb; padding:2px 8px; border-radius:6px;">DUE SOON</span>';
                    $deadlineText = 'Due in ' . $daysLeft . ' days';
                    $deadlineColor = '#d97706';
                } else {
                    // Not urgent
                    $statusBadge = '<span style="font-size:11px; font-weight:700; color:#6b7280; background:#f3f4f6; padding:2px 8px; border-radius:6px;">PENDING</span>';
                    $deadlineText = 'Due ' . date('j M', $deadlineTs);
                }
            } else {
                // Submitted / Graded / Late
                $titleStyle = 'color:#6b7280;'; // Gray out title slightly
                $deadlineIcon = 'fa-solid fa-check-circle';
                
                if($row['subStatus'] == 'Graded') {
                    $barClass = 'st-done';
                    $grade = $row['grade'];
                    // Color grade based on score
                    $gradeColor = $grade >= 80 ? '#059669' : ($grade >= 50 ? '#d97706' : '#dc2626');
                    $statusBadge = '<span style="font-size:11px; font-weight:700; color:'.$gradeColor.'; background:'.($grade >= 80 ? '#ecfdf5' : ($grade >= 50 ? '#fffbeb' : '#fef2f2')).'; padding:2px 8px; border-radius:6px;">'.$grade.'%</span>';
                    $deadlineText = 'Graded on ' . date('j M', strtotime($row['submittedAt']));
                    $deadlineColor = '#059669';
                } elseif($row['subStatus'] == 'Late') {
                    $barClass = 'st-late';
                    $statusBadge = '<span style="font-size:11px; font-weight:700; color:#dc2626; background:#fef2f2; padding:2px 8px; border-radius:6px;">SUBMITTED LATE</span>';
                    $deadlineText = 'Turned in late';
                    $deadlineColor = '#d97706';
                } else {
                    $barClass = 'st-done';
                    $statusBadge = '<span style="font-size:11px; font-weight:700; color:#059669; background:#ecfdf5; padding:2px 8px; border-radius:6px;">TURNED IN</span>';
                    $deadlineText = 'Submitted ' . date('j M', strtotime($row['submittedAt']));
                    $deadlineColor = '#059669';
                }
            }
        ?>
        <article class="assign-card" onclick="openModal(<?php echo (int)$row['assignmentID']; ?>)">
            <div class="ac-status-bar <?php echo $barClass; ?>"></div>
            <div class="ac-header">
                <span class="ac-course"><?php echo htmlspecialchars($row['courseID'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php echo $statusBadge; ?>
            </div>
            <h3 class="ac-title" style="<?php echo $titleStyle; ?>"><?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <div class="ac-deadline">
                <i class="<?php echo $deadlineIcon; ?>" style="color:<?php echo $deadlineColor; ?>"></i> 
                <span style="color:<?php echo $deadlineColor; ?>; font-weight:600;">
                    <?php echo $deadlineText; ?>
                </span>
            </div>
            <div class="ac-footer">
                <span style="font-size:12px; color:#9ca3af;">Posted by <?php echo htmlspecialchars($row['staffName'], ENT_QUOTES, 'UTF-8'); ?></span>
                <i class="fa-solid fa-chevron-right" style="font-size:12px; color:#d1d5db;"></i>
            </div>
        </article>
        <?php endforeach; 
    endif;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Assignments - CAMPUSLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        /* --- KEEPING YOUR ORIGINAL STYLES --- */
        .assign-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 25px; margin-top: 25px; }
        .assign-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #f0f0f0; transition: all 0.3s ease; position: relative; overflow: hidden; display: flex; flex-direction: column; cursor: pointer; }
        .assign-card:hover { transform: translateY(-5px); box-shadow: 0 10px 40px rgba(128, 86, 255, 0.15); border-color: #dcd0ff; }
        
        .ac-status-bar { position: absolute; top: 0; left: 0; width: 6px; height: 100%; }
        .st-pending { background: #f59e0b; } /* Orange */
        .st-done { background: #10b981; }    /* Green */
        .st-late { background: #ef4444; }    /* Red */
        
        .ac-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .ac-course { font-size: 11px; font-weight: 700; color: #6b7280; background: #f3f4f6; padding: 4px 10px; border-radius: 6px; }
        .ac-title { font-size: 17px; font-weight: 700; color: #1f2937; margin-bottom: 6px; line-height: 1.3; }
        .ac-deadline { font-size: 13px; color: #6b7280; display: flex; align-items: center; gap: 6px; margin-bottom: 15px; white-space: nowrap; }
        .ac-footer { margin-top: auto; padding-top: 15px; border-top: 1px solid #f9fafb; display: flex; justify-content: space-between; align-items: center; }

        /* --- NEW FILTER BAR STYLES --- */
        .filter-bar { display: flex; gap: 10px; margin-top: 25px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 5px; }
        .filter-btn { 
            background: white; border: 1px solid #e5e7eb; padding: 8px 16px; border-radius: 20px; 
            font-size: 13px; font-weight: 600; color: #4b5563; cursor: pointer; transition: all 0.2s; white-space: nowrap;
        }
        .filter-btn:hover { background: #f9fafb; border-color: #d1d5db; }
        .filter-btn.active { background: #f3efff; color: #8056ff; border-color: #8056ff; }
        .filter-bar::-webkit-scrollbar { display: none; }

        /* --- MODAL STYLES (Split View) --- */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal-content { background: white; width: 1000px; max-width: 95vw; height: 85vh; border-radius: 24px; overflow: hidden; display: flex; flex-direction: column; position: relative; }
        .modal-header-bar { padding: 20px 30px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background: white; }
        .close-btn { font-size: 28px; cursor: pointer; color: #9ca3af; transition: 0.2s; }
        .close-btn:hover { color: #1f2937; }
        #modalBody { flex-grow: 1; overflow-y: auto; background: #fff; display: flex; }
        .loading-state { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-weight: 600; }
        
        /* AJAX Content Styles */
        .gc-layout { display: grid; grid-template-columns: 1fr 350px; width: 100%; height: 100%; }
        .gc-left { padding: 40px; overflow-y: auto; border-right: 1px solid #f3f4f6; }
        .gc-right { padding: 30px; background: #f9fafb; border-left: 1px solid #e5e7eb; overflow-y: auto; }
        .gc-work-card { background: white; border-radius: 16px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; }
        .gc-card-title { font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        @media (max-width: 900px) {
            .gc-layout { grid-template-columns: 1fr; overflow-y: auto; }
            .gc-right { border-left: none; border-top: 1px solid #e5e7eb; }
        }
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
                        <li><a href="../index.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-table-columns"></i></span> Dashboard</a></li>
                        <li><a href="student-attendance.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-clock"></i></span> Attendance</a></li>
                        <li><a href="student-assignments.php" class="nav-item is-active"><span class="nav-icon"><i class="fa-solid fa-file-pen"></i></span> Assignments</a></li>
                        <li><a href="student-timetable.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-calendar-days"></i></span> My Timetable</a></li>
                        <li><a href="resource-booking.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-building"></i></span> Resource Booking</a></li>
                        <li class="nav-group" data-expandable><button class="nav-item nav-toggle" type="button" data-target="programme-panel"><span class="nav-icon"><i class="fa-solid fa-clipboard-list"></i></span> Programme <span class="nav-chevron"></span></button><div class="nav-submenu" id="programme-panel" hidden><a href="programme-structure.php" class="nav-subitem">Programme Structure</a><a href="course-enrollment.php" class="nav-subitem">Course Enrollment</a><a href="#" class="nav-subitem">Results</a></div></li>
                        <li><a href="../logout.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span> Logout</a></li>
                    </ul>
                </nav>
            </aside>

            <main class="dashboard">
                <header class="dashboard-topbar">
                    <div class="topbar-right">
                        <div class="user-card">
                            <div class="user-info">
                                <span class="user-name"><?php echo htmlspecialchars($studentName); ?></span>
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
                        <h1 style="font-size: 30px; font-weight: 700; margin: 0 0 8px 0; color: white;">Assignments</h1>
                        <p style="margin: 0; font-size: 14px; color: rgba(255,255,255,0.85);">Track deadlines and submit your work</p>
                    </div>
                    
                    <!-- Right Decorative Illustration - Books Stack -->
                    <div style="position: relative; z-index: 1; display: flex; align-items: flex-end; gap: 8px;">
                        <!-- Book 1 (Green) -->
                        <div style="
                            width: 22px; height: 70px;
                            background: linear-gradient(135deg, #10b981, #059669);
                            border-radius: 4px 8px 8px 4px;
                            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
                            position: relative;
                        ">
                            <div style="position: absolute; left: 2px; top: 8px; bottom: 8px; width: 2px; background: rgba(255,255,255,0.3); border-radius: 2px;"></div>
                        </div>
                        <!-- Book 2 (Red/Orange) -->
                        <div style="
                            width: 28px; height: 85px;
                            background: linear-gradient(135deg, #ef4444, #dc2626);
                            border-radius: 4px 8px 8px 4px;
                            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
                            position: relative;
                        ">
                            <div style="position: absolute; left: 3px; top: 10px; bottom: 10px; width: 2px; background: rgba(255,255,255,0.3); border-radius: 2px;"></div>
                            <div style="position: absolute; top: 15px; right: 5px; width: 12px; height: 3px; background: rgba(255,255,255,0.5); border-radius: 2px;"></div>
                            <div style="position: absolute; top: 22px; right: 5px; width: 8px; height: 3px; background: rgba(255,255,255,0.3); border-radius: 2px;"></div>
                        </div>
                        <!-- Notebook (Main) -->
                        <div style="
                            width: 80px; height: 100px;
                            background: white;
                            border-radius: 8px;
                            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
                            position: relative;
                            display: flex;
                            flex-direction: column;
                            padding: 12px 10px;
                        ">
                            <!-- Spiral binding -->
                            <div style="position: absolute; left: -4px; top: 15px; display: flex; flex-direction: column; gap: 8px;">
                                <div style="width: 8px; height: 8px; background: #d1d5db; border-radius: 50%;"></div>
                                <div style="width: 8px; height: 8px; background: #d1d5db; border-radius: 50%;"></div>
                                <div style="width: 8px; height: 8px; background: #d1d5db; border-radius: 50%;"></div>
                                <div style="width: 8px; height: 8px; background: #d1d5db; border-radius: 50%;"></div>
                                <div style="width: 8px; height: 8px; background: #d1d5db; border-radius: 50%;"></div>
                            </div>
                            <!-- Lines -->
                            <div style="flex: 1; display: flex; flex-direction: column; gap: 6px; padding-left: 8px;">
                                <div style="height: 2px; background: #e5e7eb; border-radius: 2px;"></div>
                                <div style="height: 2px; background: #e5e7eb; border-radius: 2px; width: 80%;"></div>
                                <div style="height: 2px; background: #e5e7eb; border-radius: 2px; width: 90%;"></div>
                                <div style="height: 2px; background: #e5e7eb; border-radius: 2px; width: 60%;"></div>
                                <div style="height: 2px; background: #e5e7eb; border-radius: 2px; width: 75%;"></div>
                            </div>
                            <!-- Checkmark badge -->
                            <div style="position: absolute; bottom: -8px; right: -8px; width: 30px; height: 30px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);">
                                <i class="fa-solid fa-check" style="color: white; font-size: 12px;"></i>
                            </div>
                        </div>
                        <!-- Book 3 (Yellow) -->
                        <div style="
                            width: 24px; height: 75px;
                            background: linear-gradient(135deg, #f59e0b, #d97706);
                            border-radius: 4px 8px 8px 4px;
                            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
                            position: relative;
                        ">
                            <div style="position: absolute; left: 3px; top: 10px; bottom: 10px; width: 2px; background: rgba(255,255,255,0.3); border-radius: 2px;"></div>
                        </div>
                        <!-- Pencil -->
                        <div style="
                            width: 12px; height: 90px;
                            background: linear-gradient(to bottom, #fcd34d 0%, #fcd34d 85%, #fde68a 85%, #fde68a 92%, #1f2937 92%);
                            border-radius: 2px 2px 6px 6px;
                            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
                            transform: rotate(8deg);
                            margin-left: 5px;
                        ">
                            <div style="position: absolute; top: 0; left: 0; right: 0; height: 8px; background: #ef4444; border-radius: 2px 2px 0 0;"></div>
                        </div>
                    </div>
                </div>

                <div class="filter-bar">
                    <button class="filter-btn active" onclick="filterGrid(event, 'all')">All (<?php echo count($listAll); ?>)</button>
                    <button class="filter-btn" onclick="filterGrid(event, 'duesoon')">Due Soon (<?php echo count($listDueSoon); ?>)</button>
                    <button class="filter-btn" onclick="filterGrid(event, 'submitted')">Submitted (<?php echo count($listSubmitted); ?>)</button>
                    <button class="filter-btn" onclick="filterGrid(event, 'graded')">Graded (<?php echo count($listGraded); ?>)</button>
                </div>

                <div id="grid-all" class="assign-grid">
                    <?php renderGrid($listAll); ?>
                </div>

                <div id="grid-duesoon" class="assign-grid" style="display:none;">
                    <?php renderGrid($listDueSoon); ?>
                </div>

                <div id="grid-submitted" class="assign-grid" style="display:none;">
                    <?php renderGrid($listSubmitted); ?>
                </div>

                <div id="grid-graded" class="assign-grid" style="display:none;">
                    <?php renderGrid($listGraded); ?>
                </div>

            </main>
        </div>
    </div>

    <div id="assignModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('assignModal').style.display='none'" style="position: absolute; top: 15px; right: 20px; z-index: 10;">&times;</span>
            <div id="modalBody"></div>
        </div>
    </div>

    <script>
        // NEW FILTER LOGIC
        function filterGrid(event, filterName) {
            // 1. Update active button state
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // 2. Hide all grids
            document.querySelectorAll('.assign-grid').forEach(grid => grid.style.display = 'none');
            
            // 3. Show selected grid
            document.getElementById('grid-' + filterName).style.display = 'grid';
        }

        function openModal(id) {
            document.getElementById('assignModal').style.display = 'flex';
            document.getElementById('modalBody').innerHTML = '<div class="loading-state"><i class="fa-solid fa-spinner fa-spin"></i> Loading details...</div>';
            
            // NOTE: Ensure this points to your API
            fetch(`../api/student-assignments-api.php?assignmentID=${id}`)
                .then(r => r.text())
                .then(html => {
                    document.getElementById('modalBody').innerHTML = html;
                });
        }
        
        function submitAssignment(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Uploading...'; btn.disabled = true;
            
            const formData = new FormData(e.target);
            fetch('../api/student-assignments-api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if(data.status === 'success') {
                    alert('Assignment submitted successfully!');
                    location.reload(); 
                } else {
                    alert('Error: ' + data.message);
                    btn.innerHTML = originalText; btn.disabled = false;
                }
            })
            .catch(err => {
                alert('Upload failed.');
                btn.innerHTML = originalText; btn.disabled = false;
            });
        }
        
        window.onclick = function(e) {
            if(e.target == document.getElementById('assignModal')) {
                document.getElementById('assignModal').style.display = 'none';
            }
        }
    </script>
    <script src="../script.js"></script>
</body>
</html>