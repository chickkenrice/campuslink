<?php
session_start();
require_once '../includes/config.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit;
}
prevent_back_button_cache();

$db = get_db_connection();
$staffID = $_SESSION['user_id'];
$staffName = $_SESSION['user_name'];
$msg = ""; $msgType = "";

// Pick up flash message from redirect (PRG pattern)
if (isset($_SESSION['assign_msg'])) {
    $msg = $_SESSION['assign_msg'];
    $msgType = $_SESSION['assign_msg_type'] ?? 'success';
    unset($_SESSION['assign_msg'], $_SESSION['assign_msg_type']);
}

// 2. Handle ID Mapping
if (strpos($staffID, 'U') === 0) {
    $stmtUser = $db->prepare("SELECT staffID FROM staff WHERE userID = ?");
    $stmtUser->bind_param("s", $staffID);
    $stmtUser->execute();
    $resUser = $stmtUser->get_result()->fetch_assoc();
    $staffID = $resUser['staffID'];
}

// 3. Handle Create Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment'])) {
    $errors = [];
    
    // Validate course_group
    if (empty($_POST['course_group'])) {
        $errors[] = "Please select a class group.";
    } else {
        $parts = explode('|', $_POST['course_group']);
        $courseID = $parts[0] ?? '';
        $targetGroup = $parts[1] ?? '';
    }
    
    // Validate title (3-100 chars)
    $title = trim($_POST['title'] ?? '');
    if (strlen($title) < 3) {
        $errors[] = "Title must be at least 3 characters.";
    } elseif (strlen($title) > 100) {
        $errors[] = "Title cannot exceed 100 characters.";
    }
    
    // Validate description (10+ chars)
    $desc = trim($_POST['description'] ?? '');
    if (strlen($desc) < 10) {
        $errors[] = "Description must be at least 10 characters.";
    }
    
    // Validate deadline (must be in future)
    $deadlineDate = $_POST['deadline_date'] ?? '';
    $deadlineTime = $_POST['deadline_time'] ?? '23:59';
    $deadline = $deadlineDate . ' ' . $deadlineTime;
    if (empty($deadlineDate)) {
        $errors[] = "Please set a deadline date.";
    } elseif (strtotime($deadline) <= time()) {
        $errors[] = "Deadline must be in the future.";
    }
    
    // Validate file upload (if provided)
    $filePath = null;
    $allowedTypes = ['pdf', 'doc', 'docx', 'txt', 'zip', 'rar', 'png', 'jpg', 'jpeg'];
    $maxFileSize = 10 * 1024 * 1024; // 10MB
    
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $fileExt = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExt, $allowedTypes)) {
            $errors[] = "Invalid file type. Allowed: PDF, DOC, DOCX, TXT, ZIP, RAR, PNG, JPG.";
        } elseif ($_FILES['attachment']['size'] > $maxFileSize) {
            $errors[] = "File size exceeds 10MB limit.";
        } else {
            $uploadDir = '../uploads/assignments/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $fileName = time() . '_' . basename($_FILES['attachment']['name']);
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $fileName)) {
                $filePath = $fileName;
            } else {
                $errors[] = "Failed to upload file.";
            }
        }
    } elseif (isset($_FILES['attachment']) && $_FILES['attachment']['error'] != 4) {
        // Error 4 = no file uploaded (which is OK since it's optional)
        $errors[] = "File upload error. Please try again.";
    }
    
    // If no errors, insert
    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO assignments (courseID, staffID, tutGroup, title, description, attachmentPath, deadline) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $courseID, $staffID, $targetGroup, $title, $desc, $filePath, $deadline);
        
        if ($stmt->execute()) {
            $_SESSION['assign_msg'] = "Assignment created successfully for $targetGroup!";
            $_SESSION['assign_msg_type'] = "success";
            header("Location: staff-assignments.php");
            exit;
        } else {
            $msg = "Database error. Please try again."; 
            $msgType = "error";
        }
    } else {
        $msg = implode("<br>", $errors);
        $msgType = "error";
    }
}

// 4. Fetch Teaching Groups
$groupSql = "SELECT DISTINCT s.courseID, c.courseName, s.tutGroup 
             FROM class_schedule s 
             JOIN course c ON s.courseID = c.courseID 
             WHERE s.staffID = ?
             ORDER BY s.courseID, s.tutGroup";
$stmtG = $db->prepare($groupSql);
$stmtG->bind_param("s", $staffID);
$stmtG->execute();
$groups = $stmtG->get_result();

// 5. Fetch Active Assignments
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
        /* --- ORIGINAL DASHBOARD STYLES --- */
        .assign-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; margin-top: 20px; }
        .assign-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #f0f0f0; transition: all 0.3s ease; position: relative; overflow: hidden; display: flex; flex-direction: column; height: 100%; }
        .assign-card:hover { transform: translateY(-5px); box-shadow: 0 10px 40px rgba(128, 86, 255, 0.15); border-color: #dcd0ff; }
        .assign-card::before { content: ""; position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: #8056ff; }
        .ac-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .ac-course { font-size: 11px; font-weight: 700; color: #8056ff; background: #f3efff; padding: 4px 10px; border-radius: 8px; text-transform: uppercase; }
        .ac-group-badge { font-size: 10px; font-weight: 700; color: #4b5563; background: #f3f4f6; padding: 3px 8px; border-radius: 6px; margin-left: 5px; }
        .ac-title { font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 8px; line-height: 1.3; }
        .ac-desc { font-size: 13px; color: #6b7280; margin-bottom: 20px; line-height: 1.5; flex-grow: 1; }
        .ac-meta { display: flex; gap: 15px; font-size: 12px; color: #6b7280; font-weight: 600; margin-bottom: 20px; border-top: 1px solid #f3f4f6; padding-top: 15px; }
        .btn-view { flex: 1; background: #1f2937; color: white; border: none; padding: 12px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-view:hover { background: #8056ff; }
        .ac-actions { display: flex; gap: 10px; align-items: stretch; }
        .btn-report { display: grid; place-items: center; width: 44px; background: #fee2e2; color: #dc2626; border: none; border-radius: 12px; font-size: 16px; cursor: pointer; transition: 0.2s; text-decoration: none; }
        .btn-report:hover { background: #dc2626; color: white; }
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; margin-top: 20px; }
        .section-title { font-size: 18px; font-weight: 700; color: #1f2937; margin: 0; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #374151; }
        .form-input { width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; outline: none; transition: 0.2s; box-sizing: border-box; }
        .form-input:focus { border-color: #8056ff; box-shadow: 0 0 0 3px rgba(128, 86, 255, 0.1); }
        .form-input.error { border-color: #ef4444; background: #fef2f2; }
        .field-hint { font-size: 11px; color: #9ca3af; margin-top: 5px; }
        .field-hint.error { color: #ef4444; }
        .field-hint.success { color: #10b981; }
        .form-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px; border-radius: 10px; margin-bottom: 15px; font-size: 13px; font-weight: 500; }
        a.nav-item, a.nav-item:visited, a.nav-subitem { text-decoration: none !important; }

        /* --- UPDATED: FULL SCREEN GRADING INTERFACE --- */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        
        .modal-content.grading-mode { 
            background: white; 
            width: 95vw; 
            height: 90vh; 
            border-radius: 16px; 
            overflow: hidden; 
            display: flex; 
            flex-direction: column; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .modal-header-bar { padding: 15px 30px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background: #fff; height: 70px; }
        .modal-header-title { font-size: 18px; font-weight: 700; color: #1f2937; display: flex; align-items: center; gap: 12px; }
        
        /* CLEANER CLOSE BUTTON */
        .close-btn { 
            font-size: 20px; 
            cursor: pointer; 
            color: #9ca3af; 
            transition: 0.2s; 
            background: transparent; 
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: grid; 
            place-items: center;
        }
        .close-btn:hover { color: #1f2937; background: #f3f4f6; }

        #gradingContent { flex-grow: 1; overflow: hidden; display: flex; background: #ffffff; }
        .loading-state { display: flex; align-items: center; justify-content: center; height: 100%; width: 100%; color: #6b7280; font-weight: 600; gap: 10px; flex-direction: column; }
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
                                <a href="tutorial-management.php" class="nav-subitem">Tutorial Management</a>
                                <a href="staff-attendance.php" class="nav-subitem">Attendance</a>
                                <a href="staff-assignments.php" class="nav-subitem" style="background: rgba(255,255,255,0.1);">Assignment</a>
                            </div>
                        </li>
                        <li><a href="staff-timetable.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-calendar-week"></i></span> My Timetable</a></li>
                        <li><a href="resource-booking.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-building"></i></span> Resource Booking</a></li>
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

                <!-- Enhanced Page Hero Header -->
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
                        <p style="margin: 0; font-size: 14px; color: rgba(255,255,255,0.85);">Create tasks and grade student submissions</p>
                    </div>
                    
                    <!-- Right Decorative Illustration - Grading Theme -->
                    <div style="position: relative; z-index: 1; display: flex; align-items: flex-end; gap: 10px;">
                        <!-- Graded Paper Stack -->
                        <div style="position: relative;">
                            <!-- Back paper -->
                            <div style="width: 70px; height: 90px; background: #f3f4f6; border-radius: 8px; position: absolute; top: 5px; left: 5px; transform: rotate(5deg);"></div>
                            <!-- Front paper with grade -->
                            <div style="
                                width: 70px; height: 90px;
                                background: white;
                                border-radius: 8px;
                                box-shadow: 0 8px 25px rgba(0,0,0,0.12);
                                position: relative;
                                display: flex;
                                flex-direction: column;
                                padding: 10px;
                            ">
                                <div style="height: 6px; background: #e5e7eb; border-radius: 3px; margin-bottom: 6px;"></div>
                                <div style="height: 6px; background: #e5e7eb; border-radius: 3px; width: 80%; margin-bottom: 6px;"></div>
                                <div style="height: 6px; background: #e5e7eb; border-radius: 3px; width: 60%; margin-bottom: auto;"></div>
                                <div style="font-size: 20px; font-weight: 800; color: #10b981; text-align: center;">A+</div>
                            </div>
                        </div>
                        <!-- Pencil -->
                        <div style="
                            width: 14px; height: 85px;
                            background: linear-gradient(to bottom, #fcd34d 0%, #fcd34d 82%, #fde68a 82%, #fde68a 90%, #1f2937 90%);
                            border-radius: 2px 2px 6px 6px;
                            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
                            transform: rotate(-8deg);
                            position: relative;
                        ">
                            <div style="position: absolute; top: 0; left: 0; right: 0; height: 10px; background: #ef4444; border-radius: 2px 2px 0 0;"></div>
                        </div>
                        <!-- Checkmark Circle -->
                        <div style="
                            width: 65px; height: 65px;
                            background: white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
                            margin-bottom: 10px;
                        ">
                            <div style="width: 45px; height: 45px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-check" style="color: white; font-size: 20px;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if($msg): ?>
                    <div class="alert" style="background: <?php echo $msgType=='success'?'#d1fae5':'#fee2e2'; ?>; color: <?php echo $msgType=='success'?'#065f46':'#991b1b'; ?>; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight:600;"><?php echo $msg; ?></div>
                <?php endif; ?>

                <div class="action-bar">
                    <h2 class="section-title">Active Tasks</h2>
                    <button onclick="openCreateModal()" class="btn" style="display:flex; align-items:center; gap:8px;"><i class="fa-solid fa-plus"></i> New Assignment</button>
                </div>

                <div class="assign-grid">
                    <?php if($assignments->num_rows == 0): ?>
                        <div style="grid-column:1/-1; text-align:center; padding:50px; color:#9ca3af;"><i class="fa-solid fa-folder-open" style="font-size:40px; margin-bottom:10px;"></i><p>No assignments created yet.</p></div>
                    <?php else: ?>
                        <?php while($row = $assignments->fetch_assoc()): 
                            $date = date('d M Y', strtotime($row['deadline']));
                            $isExpired = strtotime($row['deadline']) < time();
                        ?>
                        <article class="assign-card">
                            <div class="ac-header">
                                <div><span class="ac-course"><?php echo $row['courseID']; ?></span><span class="ac-group-badge"><?php echo $row['tutGroup']; ?></span></div>
                                <?php if($isExpired): ?><span style="font-size:11px; font-weight:700; color:#dc2626; background:#fee2e2; padding:4px 8px; border-radius:6px;">CLOSED</span><?php else: ?><span style="font-size:11px; font-weight:700; color:#059669; background:#d1fae5; padding:4px 8px; border-radius:6px;">ACTIVE</span><?php endif; ?>
                            </div>
                            <h3 class="ac-title"><?php echo $row['title']; ?></h3>
                            <p class="ac-desc"><?php echo substr($row['description'], 0, 80) . '...'; ?></p>
                            <div class="ac-meta"><span><i class="fa-regular fa-clock"></i> Due: <?php echo $date; ?></span><span><i class="fa-solid fa-user-check"></i> <?php echo $row['submitCount']; ?> Submissions</span></div>
                            <div class="ac-actions">
                                <button class="btn-view" onclick="openGradingModal(<?php echo $row['assignmentID']; ?>, '<?php echo addslashes($row['title']); ?>')">View Submissions</button>
                                <a href="assignment-report.php?assignmentID=<?php echo $row['assignmentID']; ?>" target="_blank" class="btn-report" title="Generate PDF Report"><i class="fa-solid fa-file-pdf"></i></a>
                            </div>
                        </article>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <div id="createModal" class="modal" style="z-index: 2005;">
        <div class="modal-content" id="createModalContent" style="background:white; width: 600px; padding: 30px; border-radius: 20px; height: auto; max-height: 90vh; overflow-y: auto; overflow-x: visible; position: relative;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="margin:0;">Create Assignment</h2>
                <button type="button" class="close-btn" onclick="closeCreateModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="createAssignmentForm" onsubmit="return validateAssignmentForm()">
                <input type="hidden" name="create_assignment" value="1">
                <div class="form-group">
                    <label class="form-label">Select Class Group <span style="color:#ef4444;">*</span></label>
                    <select name="course_group" id="course_group" class="form-input" required>
                        <option value="" disabled selected>Select a class...</option>
                        <?php while($g = $groups->fetch_assoc()): ?>
                            <option value="<?php echo $g['courseID'] . '|' . $g['tutGroup']; ?>"><?php echo $g['courseID'] . " - " . $g['courseName'] . " (" . $g['tutGroup'] . ")"; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Assignment Title <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="title" id="assignTitle" class="form-input" placeholder="e.g., Lab Report 1" required minlength="3" maxlength="100">
                    <div class="field-hint"><span id="titleCounter">0</span>/100 characters (min 3)</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Instructions / Description <span style="color:#ef4444;">*</span></label>
                    <textarea name="description" id="assignDesc" class="form-input" rows="4" required minlength="10" placeholder="Provide clear instructions for students..."></textarea>
                    <div class="field-hint"><span id="descCounter">0</span> characters (min 10)</div>
                </div>
                
                <div class="form-group" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div>
                        <label class="form-label">Deadline Date <span style="color:#ef4444;">*</span></label>
                        <input type="date" name="deadline_date" id="assignDeadlineDate" class="form-input" required>
                        <div class="field-hint" id="deadlineDateHint">Select deadline date</div>
                    </div>
                    <div>
                        <label class="form-label">Deadline Time <span style="color:#ef4444;">*</span></label>
                        <input type="time" name="deadline_time" id="assignDeadlineTime" class="form-input" required value="23:59">
                        <div class="field-hint">Select deadline time</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Attachment (Optional)</label>
                    <input type="file" name="attachment" id="assignFile" class="form-input" accept=".pdf,.doc,.docx,.txt,.zip,.rar,.png,.jpg,.jpeg">
                    <div class="field-hint">Max 10MB • PDF, DOC, TXT, ZIP, Images</div>
                </div>
                
                <div id="formError" class="form-error" style="display:none;"></div>
                
                <button type="submit" id="submitBtn" class="btn" style="width:100%;">
                    <span id="submitText">Publish Assignment</span>
                    <span id="submitLoader" style="display:none;"><i class="fa-solid fa-circle-notch fa-spin"></i> Publishing...</span>
                </button>
            </form>
        </div>
    </div>

    <div id="gradingModal" class="modal">
        <div class="modal-content grading-mode">
            <div class="modal-header-bar">
                <div class="modal-header-title">
                    <div style="width:36px; height:36px; background:#f3efff; border-radius:8px; display:grid; place-items:center; color:#8056ff;"><i class="fa-solid fa-graduation-cap"></i></div>
                    <span id="gradingTitle">Assignment Title</span>
                </div>
                <div style="display:flex; align-items:center; gap:15px;">
                    <button class="close-btn" onclick="closeModal('gradingModal')" title="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
            
            <div id="gradingContent"></div>
        </div>
    </div>

    <script>
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
        
        function openGradingModal(assignID, title) {
            document.getElementById('gradingModal').style.display = 'flex';
            document.getElementById('gradingTitle').innerText = title;
            document.getElementById('gradingContent').innerHTML = '<div class="loading-state"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; color:#8056ff;"></i><span>Loading students...</span></div>';
            
            fetch('../api/staff-assignments-api.php?assignmentID=' + assignID)
                .then(r => r.text())
                .then(html => {
                    document.getElementById('gradingContent').innerHTML = html;
                    // Auto-select first student
                    const first = document.querySelector('.gc-student-item');
                    if(first) first.click();
                });
        }

        // Logic for Split View (Sidebar Click)
        function loadStudentDetails(element, subID) {
            // Remove active class from all
            document.querySelectorAll('.gc-student-item').forEach(el => el.classList.remove('active'));
            // Add to clicked
            element.classList.add('active');
            // Hide all detail panels and missing info panel
            document.querySelectorAll('.gc-detail-panel').forEach(el => el.style.display = 'none');
            document.getElementById('empty-state').style.display = 'none';
            var missingPanel = document.getElementById('missing-info-panel');
            if(missingPanel) missingPanel.style.display = 'none';
            // Show target panel
            const panel = document.getElementById('detail-' + subID);
            if(panel) panel.style.display = 'flex';
        }

        // AJAX Form Submit
        document.addEventListener('submit', function(e) {
            if (e.target && e.target.classList.contains('gc-grading-form')) {
                e.preventDefault();
                const btn = e.target.querySelector('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = 'Saving...'; btn.disabled = true;

                const formData = new FormData(e.target);
                formData.append('submit_grade_ajax', true);

                fetch('../api/staff-assignments-api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                     btn.innerHTML = originalText; btn.disabled = false;
                     if(data.status === 'success') {
                         btn.style.background = '#10b981'; btn.innerText = 'Saved!';
                         setTimeout(() => { btn.style.background = ''; btn.innerText = originalText; }, 2000);
                         
                         // Update sidebar grade text
                         const subID = formData.get('submissionID');
                         const sbGrade = document.querySelector(`.gc-student-item[onclick*="${subID}"] .gc-grade-pill`);
                         if(sbGrade) {
                             sbGrade.innerText = formData.get('grade');
                             sbGrade.style.background = '#d1fae5'; sbGrade.style.color = '#065f46';
                         }
                         // Update status badge
                         const sbStatus = document.querySelector(`.gc-student-item[onclick*="${subID}"] .gc-status-text`);
                         if(sbStatus) { sbStatus.innerText = 'Graded'; sbStatus.style.color = '#10b981'; }
                     } else { alert('Error saving grade.'); }
                });
            }
        });
        
        // ========== CREATE ASSIGNMENT FORM VALIDATION ==========
        let formChanged = false;
        
        // Set minimum date to today
        function initDeadlineInputs() {
            const today = new Date();
            const minDate = today.toISOString().split('T')[0];
            document.getElementById('assignDeadlineDate').setAttribute('min', minDate);
            
            // Listen for changes
            document.getElementById('assignDeadlineDate').addEventListener('change', function() {
                formChanged = true;
                validateDeadline();
            });
            document.getElementById('assignDeadlineTime').addEventListener('change', function() {
                formChanged = true;
                validateDeadline();
            });
        }
        initDeadlineInputs();
        
        function validateDeadline() {
            const dateVal = document.getElementById('assignDeadlineDate').value;
            const timeVal = document.getElementById('assignDeadlineTime').value || '23:59';
            const hintEl = document.getElementById('deadlineDateHint');
            
            if (!dateVal) {
                hintEl.textContent = 'Select deadline date';
                hintEl.className = 'field-hint';
                return false;
            }
            
            const deadline = new Date(dateVal + 'T' + timeVal);
            const now = new Date();
            
            if (deadline <= now) {
                hintEl.textContent = 'Must be a future date/time';
                hintEl.className = 'field-hint error';
                return false;
            }
            
            hintEl.textContent = '✓ Deadline set';
            hintEl.className = 'field-hint success';
            return true;
        }
        
        // Character counters
        document.getElementById('assignTitle').addEventListener('input', function() {
            document.getElementById('titleCounter').textContent = this.value.length;
            formChanged = true;
            validateField(this, this.value.length >= 3 && this.value.length <= 100);
        });
        
        document.getElementById('assignDesc').addEventListener('input', function() {
            document.getElementById('descCounter').textContent = this.value.length;
            formChanged = true;
            validateField(this, this.value.length >= 10);
        });

        
        // File validation
        document.getElementById('assignFile').addEventListener('change', function() {
            formChanged = true;
            if (this.files.length > 0) {
                const file = this.files[0];
                const maxSize = 10 * 1024 * 1024; // 10MB
                const allowedExts = ['pdf', 'doc', 'docx', 'txt', 'zip', 'rar', 'png', 'jpg', 'jpeg'];
                const ext = file.name.split('.').pop().toLowerCase();
                
                if (!allowedExts.includes(ext)) {
                    alert('Invalid file type. Allowed: PDF, DOC, DOCX, TXT, ZIP, RAR, PNG, JPG, JPEG');
                    this.value = '';
                } else if (file.size > maxSize) {
                    alert('File size exceeds 10MB limit.');
                    this.value = '';
                }
            }
        });
        
        // Track form changes
        document.getElementById('course_group').addEventListener('change', () => formChanged = true);
        
        function validateField(input, isValid) {
            if (isValid) {
                input.classList.remove('error');
            } else {
                input.classList.add('error');
            }
        }
        
        function validateAssignmentForm() {
            const title = document.getElementById('assignTitle').value.trim();
            const desc = document.getElementById('assignDesc').value.trim();
            const deadlineDate = document.getElementById('assignDeadlineDate').value;
            const deadlineTime = document.getElementById('assignDeadlineTime').value || '23:59';
            const courseGroup = document.getElementById('course_group').value;
            const errorDiv = document.getElementById('formError');
            let errors = [];
            
            if (!courseGroup) errors.push('Please select a class group.');
            if (title.length < 3) errors.push('Title must be at least 3 characters.');
            if (title.length > 100) errors.push('Title cannot exceed 100 characters.');
            if (desc.length < 10) errors.push('Description must be at least 10 characters.');
            if (!deadlineDate) errors.push('Please set a deadline date.');
            else {
                const deadline = new Date(deadlineDate + 'T' + deadlineTime);
                if (deadline <= new Date()) errors.push('Deadline must be in the future.');
            }
            
            if (errors.length > 0) {
                errorDiv.innerHTML = errors.join('<br>');
                errorDiv.style.display = 'block';
                return false;
            }
            
            // Show loading state
            document.getElementById('submitText').style.display = 'none';
            document.getElementById('submitLoader').style.display = 'inline';
            document.getElementById('submitBtn').disabled = true;
            formChanged = false;
            return true;
        }
        
        function openCreateModal() { 
            document.getElementById('createModal').style.display = 'flex';
            // Refresh min date
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('assignDeadlineDate').setAttribute('min', today);
            formChanged = false;
        }
        
        function closeCreateModal() {
            if (formChanged) {
                if (confirm('You have unsaved changes. Are you sure you want to close?')) {
                    document.getElementById('createModal').style.display = 'none';
                    resetCreateForm();
                }
            } else {
                document.getElementById('createModal').style.display = 'none';
                resetCreateForm();
            }
        }
        
        function resetCreateForm() {
            document.getElementById('createAssignmentForm').reset();
            document.getElementById('titleCounter').textContent = '0';
            document.getElementById('descCounter').textContent = '0';
            document.getElementById('formError').style.display = 'none';
            document.querySelectorAll('.form-input.error').forEach(el => el.classList.remove('error'));
            document.getElementById('submitText').style.display = 'inline';
            document.getElementById('submitLoader').style.display = 'none';
            document.getElementById('submitBtn').disabled = false;
            document.getElementById('deadlineDateHint').textContent = 'Select deadline date';
            document.getElementById('deadlineDateHint').className = 'field-hint';
            // Reset time to default 23:59
            document.getElementById('assignDeadlineTime').value = '23:59';
            formChanged = false;
        }
        
        window.onclick = function(e) { 
            if(e.target.classList.contains('modal')) {
                if (e.target.id === 'createModal' && formChanged) {
                    if (confirm('You have unsaved changes. Are you sure you want to close?')) {
                        e.target.style.display = 'none';
                        resetCreateForm();
                    }
                } else {
                    e.target.style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>