<?php
session_start();
require_once '../includes/config.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit;
}

$db = get_db_connection();
$staffID = $_SESSION['user_id'];
$staffName = $_SESSION['user_name'];
$msg = ""; $msgType = "";

// 2. Handle ID Mapping (User ID -> Staff ID)
if (strpos($staffID, 'U') === 0) {
    $stmtUser = $db->prepare("SELECT staffID FROM staff WHERE userID = ?");
    $stmtUser->bind_param("s", $staffID);
    $stmtUser->execute();
    $resUser = $stmtUser->get_result()->fetch_assoc();
    $staffID = $resUser['staffID'];
}

// 3. Handle Create Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment'])) {
    $courseID = $_POST['courseID'];
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $deadline = $_POST['deadline'];
    
    // File Upload (Optional Attachment)
    $filePath = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $uploadDir = '../uploads/assignments/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileName = time() . '_' . basename($_FILES['attachment']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
            $filePath = $fileName;
        }
    }

    $stmt = $db->prepare("INSERT INTO assignments (courseID, staffID, title, description, attachmentPath, deadline) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $courseID, $staffID, $title, $desc, $filePath, $deadline);
    
    if ($stmt->execute()) {
        $msg = "Assignment created successfully!"; $msgType = "success";
    } else {
        $msg = "Error creating assignment."; $msgType = "error";
    }
}

// 4. Handle Grading
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_grade'])) {
    $subID = $_POST['submissionID'];
    $grade = $_POST['grade'];
    $feedback = $_POST['feedback'];
    
    $stmt = $db->prepare("UPDATE submissions SET grade = ?, feedback = ?, status = 'Graded' WHERE submissionID = ?");
    $stmt->bind_param("isi", $grade, $feedback, $subID);
    $stmt->execute();
    $msg = "Grade saved!"; $msgType = "success";
}

// 5. Fetch Data
$courses = $db->query("SELECT DISTINCT c.courseID, c.courseName FROM class_schedule s JOIN course c ON s.courseID = c.courseID WHERE s.staffID = '$staffID'");

$assignments = $db->query("SELECT a.*, c.courseName, 
    (SELECT COUNT(*) FROM submissions s WHERE s.assignmentID = a.assignmentID) as submitCount 
    FROM assignments a 
    JOIN course c ON a.courseID = c.courseID 
    WHERE a.staffID = '$staffID' 
    ORDER BY a.createdAt DESC");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Assignments - CAMPUSLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        /* UI Tweaks for Assignments */
        .assign-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; margin-top: 20px; }
        
        .assign-card {
            background: white; border-radius: 20px; padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #f0f0f0;
            transition: all 0.3s ease; position: relative; overflow: hidden;
            display: flex; flex-direction: column; height: 100%;
        }
        .assign-card:hover { transform: translateY(-5px); box-shadow: 0 10px 40px rgba(128, 86, 255, 0.15); border-color: #dcd0ff; }
        .assign-card::before { content: ""; position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: #8056ff; }
        
        .ac-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .ac-course { font-size: 11px; font-weight: 700; color: #8056ff; background: #f3efff; padding: 4px 10px; border-radius: 8px; text-transform: uppercase; }
        .ac-title { font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 8px; line-height: 1.3; }
        .ac-desc { font-size: 13px; color: #6b7280; margin-bottom: 20px; line-height: 1.5; flex-grow: 1; }
        
        .ac-meta { display: flex; gap: 15px; font-size: 12px; color: #6b7280; font-weight: 600; margin-bottom: 20px; border-top: 1px solid #f3f4f6; padding-top: 15px; }
        .ac-meta i { color: #9ca3af; margin-right: 5px; }

        .btn-view { width: 100%; background: #1f2937; color: white; border: none; padding: 12px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-view:hover { background: #8056ff; }

        /* ACTION BAR (NEW) */
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; margin-top: 20px; }
        .section-title { font-size: 18px; font-weight: 700; color: #1f2937; margin: 0; }

        /* Modal Styles */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal-content { background: white; width: 600px; max-width: 95%; border-radius: 24px; padding: 30px; position: relative; max-height: 90vh; overflow-y: auto; }
        .close-btn { position: absolute; top: 20px; right: 20px; font-size: 24px; cursor: pointer; color: #9ca3af; }
        
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #374151; }
        .form-input { width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; outline: none; transition: 0.2s; }
        .form-input:focus { border-color: #8056ff; box-shadow: 0 0 0 3px rgba(128,86,255,0.1); }
        
        /* Submission Table */
        .sub-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .sub-table th { text-align: left; font-size: 12px; color: #6b7280; padding: 12px; border-bottom: 1px solid #eee; }
        .sub-table td { padding: 12px; border-bottom: 1px solid #f9fafb; font-size: 13px; color: #374151; }
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; }
        .st-submitted { background: #eff6ff; color: #2563eb; }
        .st-graded { background: #ecfdf5; color: #059669; }
        .st-late { background: #fef2f2; color: #dc2626; }
        
        /* Force remove underlines from sidebar just in case */
        a.nav-item, a.nav-item:visited, a.nav-subitem { text-decoration: none !important; }
    </style>
</head>
<body>
    <div class="app-bg">
        <div class="main-card">
            <aside class="sidebar">
                <div class="sidebar-head">
                    <div class="brand-icon">CL</div>
                    <div class="brand-text"><span class="brand-name">CAMPUSLink</span><span class="brand-tagline">Staff Console</span></div>
                </div>
                <nav class="sidebar-nav">
                    <ul>
                        <li><a href="staff-dashboard.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-table-columns"></i></span> Dashboard</a></li>
                        <li class="nav-group is-open">
                            <button class="nav-item nav-toggle"><span class="nav-icon"><i class="fa-solid fa-users"></i></span> Student <span class="nav-chevron"></span></button>
                            <div class="nav-submenu">
                                <a href="manage-students.php" class="nav-subitem">Manage Student</a>
                                <a href="staff-attendance.php" class="nav-subitem">Attendance</a>
                                <a href="staff-assignments.php" class="nav-subitem" style="background: rgba(255,255,255,0.1);">Assignment</a>
                            </div>
                        </li>
                        <li><a href="staff-timetable.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-calendar-week"></i></span> My Timetable</a></li>
                        <li><a href="../logout.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span> Logout</a></li>
                    </ul>
                </nav>
            </aside>

            <main class="dashboard">
                <header class="dashboard-topbar">
                    <div class="topbar-right">
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

                <div class="page-header" style="margin-bottom: 24px;">
                    <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main); margin: 0;">Assignments</h1>
                    <p style="color: var(--text-light); margin: 4px 0 0 0;">Create tasks and grade student submissions</p>
                </div>

                <?php if($msg): ?>
                    <div class="alert" style="background: <?php echo $msgType=='success'?'#d1fae5':'#fee2e2'; ?>; color: <?php echo $msgType=='success'?'#065f46':'#991b1b'; ?>; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight:600;">
                        <?php echo $msg; ?>
                    </div>
                <?php endif; ?>

                <div class="action-bar">
                    <h2 class="section-title">Active Tasks</h2>
                    <button onclick="openCreateModal()" class="btn" style="display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-plus"></i> New Assignment
                    </button>
                </div>

                <div class="assign-grid">
                    <?php if($assignments->num_rows == 0): ?>
                        <div style="grid-column:1/-1; text-align:center; padding:50px; color:#9ca3af;">
                            <i class="fa-solid fa-folder-open" style="font-size:40px; margin-bottom:10px;"></i>
                            <p>No assignments created yet.</p>
                        </div>
                    <?php else: ?>
                        <?php while($row = $assignments->fetch_assoc()): 
                            $date = date('M d, Y', strtotime($row['deadline']));
                            $isExpired = strtotime($row['deadline']) < time();
                        ?>
                        <article class="assign-card">
                            <div class="ac-header">
                                <span class="ac-course"><?php echo $row['courseID']; ?></span>
                                <?php if($isExpired): ?>
                                    <span style="font-size:11px; font-weight:700; color:#dc2626; background:#fee2e2; padding:4px 8px; border-radius:6px;">CLOSED</span>
                                <?php else: ?>
                                    <span style="font-size:11px; font-weight:700; color:#059669; background:#d1fae5; padding:4px 8px; border-radius:6px;">ACTIVE</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="ac-title"><?php echo $row['title']; ?></h3>
                            <p class="ac-desc"><?php echo substr($row['description'], 0, 80) . '...'; ?></p>
                            
                            <div class="ac-meta">
                                <span><i class="fa-regular fa-clock"></i> Due: <?php echo $date; ?></span>
                                <span><i class="fa-solid fa-user-check"></i> <?php echo $row['submitCount']; ?> Submissions</span>
                            </div>
                            
                            <button class="btn-view" onclick="openGradingModal(<?php echo $row['assignmentID']; ?>, '<?php echo addslashes($row['title']); ?>')">
                                View Submissions
                            </button>
                        </article>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <div id="createModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('createModal')">&times;</span>
            <h2 style="margin-top:0;">Create Assignment</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Select Course</label>
                    <select name="courseID" class="form-input" required>
                        <?php while($c = $courses->fetch_assoc()): ?>
                            <option value="<?php echo $c['courseID']; ?>"><?php echo $c['courseID'] . " - " . $c['courseName']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Assignment Title</label>
                    <input type="text" name="title" class="form-input" placeholder="e.g., Lab Report 1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Instructions / Description</label>
                    <textarea name="description" class="form-input" rows="4" required></textarea>
                </div>
                <div class="form-group" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div>
                        <label class="form-label">Deadline</label>
                        <input type="datetime-local" name="deadline" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Attachment (Optional)</label>
                        <input type="file" name="attachment" class="form-input">
                    </div>
                </div>
                <button type="submit" name="create_assignment" class="btn" style="width:100%;">Publish Assignment</button>
            </form>
        </div>
    </div>

    <div id="gradingModal" class="modal">
        <div class="modal-content" style="width: 800px;">
            <span class="close-btn" onclick="closeModal('gradingModal')">&times;</span>
            <h2 style="margin-top:0;" id="gradingTitle">Submissions</h2>
            <div id="gradingContent">Loading...</div>
        </div>
    </div>

    <script>
        function openCreateModal() { document.getElementById('createModal').style.display = 'flex'; }
        
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
        
        function openGradingModal(assignID, title) {
            document.getElementById('gradingModal').style.display = 'flex';
            document.getElementById('gradingTitle').innerText = title;
            document.getElementById('gradingContent').innerHTML = '<p style="text-align:center;">Loading submissions...</p>';
            
            // UPDATED LINK: Points to the api folder
            fetch('../api/staff-assignments-api.php?assignmentID=' + assignID)
                .then(r => r.text())
                .then(html => {
                    document.getElementById('gradingContent').innerHTML = html;
                });
        }
        
        // Close modal on click outside
        window.onclick = function(e) {
            if(e.target.classList.contains('modal')) e.target.style.display = 'none';
        }
    </script>
    <script src="../script.js"></script>
</body>
</html>