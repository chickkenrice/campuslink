<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$db = get_db_connection();
$studentID = $_SESSION['user_id'];
$studentName = $_SESSION['user_name'];

// 1. Get Student Program & Group Info
$stmtS = $db->prepare("SELECT programID, tutGroup, currentYear, currentSemester FROM student WHERE studentID = ?");
$stmtS->bind_param("s", $studentID);
$stmtS->execute();
$sData = $stmtS->get_result()->fetch_assoc();
$myGroup = $sData['tutGroup'];

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
            $timeLeft = ceil((strtotime($row['deadline']) - time()) / 86400);
            
            // Determine Visual Style based on Status
            $barClass = 'st-pending'; 
            $statusBadge = ''; 
            $titleStyle = '';
            
            if(!$row['subStatus']) {
                 // Not Submitted
                 $statusBadge = '<span style="font-size:11px; font-weight:700; color:#d97706; background:#fffbeb; padding:2px 8px; border-radius:6px;">PENDING</span>';
                 if($timeLeft < 0) {
                     $barClass = 'st-late'; // Overdue
                     $statusBadge = '<span style="font-size:11px; font-weight:700; color:#dc2626; background:#fef2f2; padding:2px 8px; border-radius:6px;">OVERDUE</span>';
                 }
            } else {
                 // Submitted / Graded / Late
                 $titleStyle = 'color:#6b7280;'; // Gray out title slightly
                 if($row['subStatus'] == 'Graded') {
                     $barClass = 'st-done';
                     $statusBadge = '<span style="font-size:11px; font-weight:700; color:#059669; background:#ecfdf5; padding:2px 8px; border-radius:6px;">GRADED: '.$row['grade'].'%</span>';
                 } elseif($row['subStatus'] == 'Late') {
                     $barClass = 'st-late';
                     $statusBadge = '<span style="font-size:11px; font-weight:700; color:#dc2626; background:#fef2f2; padding:2px 8px; border-radius:6px;">LATE</span>';
                 } else {
                     $barClass = 'st-done';
                     $statusBadge = '<span style="font-size:11px; font-weight:700; color:#059669; background:#ecfdf5; padding:2px 8px; border-radius:6px;">SUBMITTED</span>';
                 }
            }
            
            $timeColor = ($timeLeft < 3 && !$row['subStatus'] && $timeLeft >= 0) ? '#ef4444' : '#6b7280';
        ?>
        <article class="assign-card" onclick="openModal(<?php echo $row['assignmentID']; ?>)">
            <div class="ac-status-bar <?php echo $barClass; ?>"></div>
            <div class="ac-header">
                <span class="ac-course"><?php echo $row['courseID']; ?></span>
                <?php echo $statusBadge; ?>
            </div>
            <h3 class="ac-title" style="<?php echo $titleStyle; ?>"><?php echo $row['title']; ?></h3>
            <div class="ac-deadline">
                <i class="fa-regular fa-clock" style="color:<?php echo $timeColor; ?>"></i> 
                <span style="color:<?php echo $timeColor; ?>; font-weight:600;">
                    <?php echo $timeLeft < 0 ? 'Overdue' : $timeLeft . ' days left'; ?>
                </span>
            </div>
            <div class="ac-footer">
                <span style="font-size:12px; color:#9ca3af;">Posted by <?php echo $row['staffName']; ?></span>
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
        .ac-deadline { font-size: 13px; color: #6b7280; display: flex; align-items: center; gap: 6px; margin-bottom: 15px; }
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
                        <li class="nav-group"><button class="nav-item nav-toggle"><span class="nav-icon"><i class="fa-solid fa-clipboard-list"></i></span> Programme <span class="nav-chevron"></span></button></li>
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
                                <i class="fa-solid fa-user"></i>
                            </a>
                        </div>
                    </div>
                </header>

                <div class="page-header" style="margin-bottom: 10px;">
                    <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main); margin: 0;">Assignments</h1>
                    <p style="color: var(--text-light); margin: 4px 0 0 0;">Track deadlines and submit your work</p>
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