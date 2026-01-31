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

// 1. Get Student Program Info
$stmtS = $db->prepare("SELECT programID, currentYear, currentSemester FROM student WHERE studentID = ?");
$stmtS->bind_param("s", $studentID);
$stmtS->execute();
$sData = $stmtS->get_result()->fetch_assoc();

// 2. Fetch Assignments
// Logic: Get assignments for courses linked to student's Program + Year + Semester
$sql = "SELECT a.*, c.courseName, s.staffName,
        sub.status as subStatus, sub.grade
        FROM assignments a
        JOIN course c ON a.courseID = c.courseID
        JOIN staff s ON a.staffID = s.staffID
        JOIN program_course pc ON a.courseID = pc.courseID
        LEFT JOIN submissions sub ON a.assignmentID = sub.assignmentID AND sub.studentID = ?
        WHERE pc.programID = ? AND pc.year = ? AND pc.semester = ?
        ORDER BY a.deadline ASC";

$stmt = $db->prepare($sql);
$stmt->bind_param("ssii", $studentID, $sData['programID'], $sData['currentYear'], $sData['currentSemester']);
$stmt->execute();
$allAssignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$active = [];
$completed = [];

foreach($allAssignments as $row) {
    if ($row['subStatus'] == 'Submitted' || $row['subStatus'] == 'Graded' || $row['subStatus'] == 'Late') {
        $completed[] = $row;
    } else {
        $active[] = $row;
    }
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
        /* Card Grid */
        .assign-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 25px; margin-top: 25px; }
        
        .assign-card {
            background: white; border-radius: 20px; padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #f0f0f0;
            transition: all 0.3s ease; position: relative; overflow: hidden;
            display: flex; flex-direction: column; cursor: pointer;
        }
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
        
        /* Tabs */
        .tabs { display: flex; gap: 20px; border-bottom: 2px solid #f0f0f0; margin-top: 20px; }
        .tab-btn { padding: 10px 20px; font-weight: 600; color: #6b7280; border-bottom: 3px solid transparent; cursor: pointer; transition: 0.2s; }
        .tab-btn.active { color: #8056ff; border-color: #8056ff; }
        
        /* Modal Content Styling */
        .m-course { font-size: 12px; font-weight: 700; color: #8056ff; background: #f3efff; padding: 4px 10px; border-radius: 6px; }
        .m-date { float: right; font-size: 13px; font-weight: 600; color: #ef4444; }
        .m-title { margin: 15px 0 10px; font-size: 22px; color: #1f2937; }
        .m-desc { font-size: 14px; color: #4b5563; line-height: 1.6; background: #f9fafb; padding: 15px; border-radius: 12px; border: 1px solid #eee; }
        .m-attach { margin-top: 15px; font-size: 13px; }
        .m-attach a { color: #8056ff; font-weight: 600; text-decoration: none; }
        
        /* Submission Box in Modal */
        .upload-area { border: 2px dashed #d1d5db; border-radius: 12px; padding: 25px; text-align: center; background: #fdfdff; }
        .btn-submit-work { background: #1f2937; color: white; padding: 10px 20px; border-radius: 10px; border: none; font-weight: 600; cursor: pointer; margin-top: 10px; width: 100%; transition: 0.2s; }
        .btn-submit-work:hover { background: #8056ff; }
        
        .submission-status-card { background: #ecfdf5; border: 1px solid #d1fae5; border-radius: 12px; padding: 20px; color: #065f46; }
        .ss-graded { background: #f0fdf4; border-color: #bbf7d0; }
        .grade-circle { width: 40px; height: 40px; border-radius: 50%; background: #15803d; color: white; display: grid; place-items: center; font-weight: 800; font-size: 16px; }
        
        .feedback-box { margin-top: 15px; background: white; padding: 15px; border-radius: 8px; border: 1px solid #bbf7d0; font-size: 13px; color: #374151; }
        
        .alert-late { color: #b91c1c; background: #fef2f2; padding: 10px; border-radius: 8px; font-size: 13px; margin-bottom: 10px; font-weight: 600; }
        
        /* Modal Skeleton */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal-content { background: white; width: 600px; max-width: 95%; border-radius: 24px; padding: 30px; position: relative; max-height: 90vh; overflow-y: auto; }
        .close-btn { position: absolute; top: 20px; right: 20px; font-size: 24px; cursor: pointer; color: #9ca3af; }
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
                        <li>
                            <a href="../index.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-table-columns"></i></span>
                                <span class="nav-label">Dashboard</span>
                            </a>
                        </li>

                        <li>
                            <a href="student-attendance.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-clock"></i></span>
                                <span class="nav-label">Attendance</span>
                            </a>
                        </li>

                        <li>
                            <a href="student-assignments.php" class="nav-item is-active">
                                <span class="nav-icon"><i class="fa-solid fa-file-pen"></i></span>
                                <span class="nav-label">Assignments</span>
                            </a>
                        </li>

                        <li>
                            <a href="student-timetable.php" class="nav-item">
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
                            <a href="../logout.php" class="nav-item" style="margin-top: 20px;">
                                <span class="nav-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                                <span class="nav-label" style="color: #e74c3c;">Logout</span>
                            </a>
                        </li>
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

                <div class="tabs">
                    <div class="tab-btn active" onclick="switchTab('active')">Active Tasks (<?php echo count($active); ?>)</div>
                    <div class="tab-btn" onclick="switchTab('completed')">History (<?php echo count($completed); ?>)</div>
                </div>

                <div id="tab-active" class="assign-grid">
                    <?php if(empty($active)): ?>
                        <div style="grid-column:1/-1; text-align:center; padding:50px; color:#9ca3af;">
                            <i class="fa-solid fa-check-circle" style="font-size:40px; margin-bottom:10px; color:#d1d5db;"></i>
                            <p>You have no pending assignments!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($active as $a): 
                            $timeLeft = ceil((strtotime($a['deadline']) - time()) / 86400);
                            $timeColor = $timeLeft < 3 ? '#ef4444' : '#6b7280';
                        ?>
                        <article class="assign-card" onclick="openModal(<?php echo $a['assignmentID']; ?>)">
                            <div class="ac-status-bar st-pending"></div>
                            <div class="ac-header">
                                <span class="ac-course"><?php echo $a['courseID']; ?></span>
                                <span style="font-size:11px; font-weight:700; color:#d97706; background:#fffbeb; padding:2px 8px; border-radius:6px;">PENDING</span>
                            </div>
                            <h3 class="ac-title"><?php echo $a['title']; ?></h3>
                            <div class="ac-deadline">
                                <i class="fa-regular fa-clock" style="color:<?php echo $timeColor; ?>"></i> 
                                <span style="color:<?php echo $timeColor; ?>; font-weight:600;">
                                    <?php echo $timeLeft < 0 ? 'Overdue' : $timeLeft . ' days left'; ?>
                                </span>
                            </div>
                            <div class="ac-footer">
                                <span style="font-size:12px; color:#9ca3af;">Posted by <?php echo $a['staffName']; ?></span>
                                <i class="fa-solid fa-chevron-right" style="font-size:12px; color:#d1d5db;"></i>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="tab-completed" class="assign-grid" style="display:none;">
                    <?php foreach($completed as $c): 
                         $bgClass = 'st-done';
                         $label = 'SUBMITTED';
                         if($c['subStatus'] == 'Graded') { $bgClass = 'st-done'; $label = 'GRADED: '.$c['grade'].'%'; }
                         if($c['subStatus'] == 'Late') { $bgClass = 'st-late'; $label = 'LATE SUBMISSION'; }
                    ?>
                    <article class="assign-card" onclick="openModal(<?php echo $c['assignmentID']; ?>)">
                        <div class="ac-status-bar <?php echo $bgClass; ?>"></div>
                        <div class="ac-header">
                            <span class="ac-course"><?php echo $c['courseID']; ?></span>
                            <span style="font-size:11px; font-weight:700; color:#059669; background:#ecfdf5; padding:2px 8px; border-radius:6px;"><?php echo $label; ?></span>
                        </div>
                        <h3 class="ac-title" style="color:#6b7280; text-decoration: line-through;"><?php echo $c['title']; ?></h3>
                        <div class="ac-footer">
                            <span style="font-size:12px; color:#9ca3af;">View Feedback</span>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>

            </main>
        </div>
    </div>

    <div id="assignModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('assignModal').style.display='none'">&times;</span>
            <div id="modalBody">Loading...</div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.assign-grid').forEach(g => g.style.display = 'none');
            
            // Activate selected
            document.querySelector(`.tab-btn[onclick="switchTab('${tabName}')"]`).classList.add('active');
            document.getElementById('tab-' + tabName).style.display = 'grid';
        }

        function openModal(id) {
            document.getElementById('assignModal').style.display = 'flex';
            document.getElementById('modalBody').innerHTML = '<p style="text-align:center; padding:20px;">Loading details...</p>';
            
            fetch(`../api/student-assignments-api.php?assignmentID=${id}`)
                .then(r => r.text())
                .then(html => {
                    document.getElementById('modalBody').innerHTML = html;
                });
        }
        
        // Handle Submission without Page Refresh
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
                    location.reload(); // Refresh to update status
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