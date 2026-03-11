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

// 2. Get enrolled courses for filter dropdown
$courseStmt = $db->prepare("SELECT DISTINCT c.courseID, c.courseName 
    FROM course c 
    JOIN program_course pc ON c.courseID = pc.courseID 
    WHERE pc.programID = ? AND pc.year = ? AND pc.semester = ?
    ORDER BY c.courseName");
$courseStmt->bind_param("sii", $sData['programID'], $sData['currentYear'], $sData['currentSemester']);
$courseStmt->execute();
$enrolledCourses = $courseStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Get filter parameters
$selectedCourse = $_GET['course'] ?? 'all';
$showArchived = isset($_GET['archived']) && $_GET['archived'] === '1';
$defaultLimit = 12;

// 4. Fetch ALL Assignments (Filtered by Group)
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
        ORDER BY a.deadline DESC";

$stmt = $db->prepare($sql);
$stmt->bind_param("ssiis", $studentID, $sData['programID'], $sData['currentYear'], $sData['currentSemester'], $myGroup);
$stmt->execute();
$rawAssignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 5. Process assignments: separate active vs archived
$now = time();
$thirtyDays = 30 * 24 * 60 * 60;
$allAssignments = [];
$archivedAssignments = [];

foreach ($rawAssignments as $row) {
    // Apply course filter
    if ($selectedCourse !== 'all' && $row['courseID'] !== $selectedCourse) {
        continue;
    }
    
    // Archive logic: Graded submissions older than 30 days are archived
    $isAutoArchived = false;
    if ($row['subStatus'] === 'Graded' && !empty($row['submittedAt'])) {
        $submittedTs = strtotime($row['submittedAt']);
        if (($now - $submittedTs) > $thirtyDays) {
            $isAutoArchived = true;
        }
    }
    
    if ($isAutoArchived) {
        $archivedAssignments[] = $row;
    } else {
        $allAssignments[] = $row;
    }
}

// 6. Categorize Active Assignments into Buckets
$listAll = $allAssignments; 
$listDueSoon = [];
$listSubmitted = [];
$listGraded = [];
$threeDays = 3 * 24 * 60 * 60;

foreach($allAssignments as $row) {
    $deadlineTs = strtotime($row['deadline']);
    $isSubmitted = !empty($row['subStatus']);
    
    if ($row['subStatus'] == 'Graded') {
        $listGraded[] = $row;
    }
    
    if ($isSubmitted) {
        $listSubmitted[] = $row;
    }
    
    if (!$isSubmitted && ($deadlineTs > $now) && ($deadlineTs - $now <= $threeDays)) {
        $listDueSoon[] = $row;
    }
}

// Count totals for display
$totalActive = count($listAll);
$totalArchived = count($archivedAssignments);

// Helper Function to Render Cards with optional limit
function renderGrid($list, $limit = null, $gridId = '', $archivedIds = []) {
    $archivedSet = array_flip($archivedIds);
    $total = count($list);
    $showAll = $limit === null;
    $hasMore = !$showAll && $total > $limit;
    
    if(empty($list)): ?>
        <div style="grid-column:1/-1; text-align:center; padding:50px; color:#9ca3af;">
            <i class="fa-solid fa-folder-open" style="font-size:40px; margin-bottom:10px; color:#d1d5db;"></i>
            <p>No assignments found.</p>
        </div>
    <?php else: 
        $cardIndex = 0;
        foreach($list as $row): 
            $cardIndex++;
            $isHidden = !$showAll && $limit && $cardIndex > $limit;
            
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
        <article class="assign-card<?php echo isset($archivedSet[$row['assignmentID']]) ? ' archived' : ''; ?>" onclick="openModal(<?php echo (int)$row['assignmentID']; ?>)" <?php echo $isHidden ? 'style="display:none;"' : ''; ?>>
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
        
        // Show "Load More" button if there are more items
        if ($hasMore && $gridId): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 20px;">
                <button class="load-more-btn" onclick="loadMore('<?php echo $gridId; ?>', <?php echo $total; ?>)">
                    <i class="fa-solid fa-chevron-down"></i> 
                    Show All (<?php echo $total - $limit; ?> more)
                </button>
            </div>
        <?php endif;
    endif;
}
?>
<!doctype html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../favicon2.png">
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
        .assign-card.archived { opacity: 0.65; background: #f9fafb; border-color: #e5e7eb; }
        .assign-card.archived::before { background: #9ca3af !important; }
        .assign-card.archived:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.06); border-color: #d1d5db; }
        
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
        .filter-bar { display: flex; gap: 10px; margin-top: 25px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 5px; align-items: center; flex-wrap: wrap; }
        .filter-btn { 
            background: white; border: 1px solid #e5e7eb; padding: 8px 16px; border-radius: 20px; 
            font-size: 13px; font-weight: 600; color: #4b5563; cursor: pointer; transition: all 0.2s; white-space: nowrap;
        }
        .filter-btn:hover { background: #f9fafb; border-color: #d1d5db; }
        .filter-btn.active { background: #f3efff; color: #8056ff; border-color: #8056ff; }
        .filter-bar::-webkit-scrollbar { display: none; }
        
        /* Course Filter Dropdown */
        .filter-select {
            padding: 8px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 13px; font-weight: 600;
            color: #4b5563; background: white; cursor: pointer; outline: none; min-width: 160px;
        }
        .filter-select:focus { border-color: #8056ff; box-shadow: 0 0 0 3px rgba(128, 86, 255, 0.1); }
        
        /* Archive Toggle */
        .archive-toggle { display: flex; align-items: center; gap: 8px; margin-left: auto; }
        .toggle-switch { position: relative; width: 40px; height: 22px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; inset: 0; background: #e5e7eb; border-radius: 22px; transition: 0.3s; }
        .toggle-slider::before { content: ""; position: absolute; height: 16px; width: 16px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s; }
        .toggle-switch input:checked + .toggle-slider { background: #8056ff; }
        .toggle-switch input:checked + .toggle-slider::before { transform: translateX(18px); }
        .archive-label { font-size: 12px; color: #6b7280; font-weight: 600; }
        .archive-count { font-size: 11px; color: #9ca3af; background: #f3f4f6; padding: 2px 8px; border-radius: 10px; }
        
        /* Load More Button */
        .load-more-btn {
            background: white; border: 1px solid #e5e7eb; padding: 12px 24px; border-radius: 12px;
            font-size: 13px; font-weight: 600; color: #6b7280; cursor: pointer; transition: all 0.2s;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .load-more-btn:hover { background: #f3efff; border-color: #8056ff; color: #8056ff; }

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
            <?php $currentPage = 'assignments'; include __DIR__ . '/../includes/studentMaster.php'; ?>

            <main class="dashboard">
                <header class="dashboard-topbar">
                    <div class="topbar-right">
                        <?php include __DIR__ . '/../includes/notificationBell.php'; ?>
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
                    <!-- Course Filter -->
                    <select class="filter-select" id="courseFilter" onchange="applyCourseFilter(this.value)">
                        <option value="all" <?php echo $selectedCourse === 'all' ? 'selected' : ''; ?>>All Courses</option>
                        <?php foreach ($enrolledCourses as $course): ?>
                            <option value="<?php echo htmlspecialchars($course['courseID']); ?>" <?php echo $selectedCourse === $course['courseID'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['courseName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button class="filter-btn active" onclick="filterGrid(event, 'all')">All (<?php echo count($listAll); ?>)</button>
                    <button class="filter-btn" onclick="filterGrid(event, 'duesoon')">Due Soon (<?php echo count($listDueSoon); ?>)</button>
                    <button class="filter-btn" onclick="filterGrid(event, 'submitted')">Submitted (<?php echo count($listSubmitted); ?>)</button>
                    <button class="filter-btn" onclick="filterGrid(event, 'graded')">Graded (<?php echo count($listGraded); ?>)</button>
                    
                    <!-- Archive Toggle -->
                    <?php if ($totalArchived > 0): ?>
                    <div class="archive-toggle">
                        <label class="toggle-switch">
                            <input type="checkbox" id="archiveToggle" onchange="toggleArchive(this.checked)" <?php echo $showArchived ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="archive-label">Show Archived</span>
                        <span class="archive-count"><?php echo $totalArchived; ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div id="grid-all" class="assign-grid">
                    <?php
                    $archivedIds = array_column($archivedAssignments, 'assignmentID');
                    $mergedList  = $showArchived ? array_merge($listAll, $archivedAssignments) : $listAll;
                    renderGrid($mergedList, $defaultLimit, 'all', $showArchived ? $archivedIds : []);
                    ?>
                </div>

                <div id="grid-duesoon" class="assign-grid" style="display:none;">
                    <?php renderGrid($listDueSoon, $defaultLimit, 'duesoon'); ?>
                </div>

                <div id="grid-submitted" class="assign-grid" style="display:none;">
                    <?php renderGrid($listSubmitted, $defaultLimit, 'submitted'); ?>
                </div>

                <div id="grid-graded" class="assign-grid" style="display:none;">
                    <?php renderGrid($listGraded, $defaultLimit, 'graded'); ?>
                </div>

                <?php include __DIR__ . '/../includes/footer.php'; ?>
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
        
        // Course filter - reload page with course parameter
        function applyCourseFilter(courseId) {
            const url = new URL(window.location.href);
            if (courseId === 'all') {
                url.searchParams.delete('course');
            } else {
                url.searchParams.set('course', courseId);
            }
            window.location.href = url.toString();
        }
        
        // Archive toggle - reload page with archived parameter
        function toggleArchive(show) {
            const url = new URL(window.location.href);
            if (show) {
                url.searchParams.set('archived', '1');
            } else {
                url.searchParams.delete('archived');
            }
            window.location.href = url.toString();
        }
        
        // Load more - show all cards in a grid
        function loadMore(gridId, total) {
            const grid = document.getElementById('grid-' + gridId);
            if (!grid) return;
            
            // Show all hidden cards
            grid.querySelectorAll('.assign-card').forEach(card => {
                card.style.display = '';
            });
            
            // Hide the load more button
            const btn = grid.querySelector('.load-more-btn');
            if (btn) btn.style.display = 'none';
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
            
            // Use staged files instead of form input
            if (stagedFiles.length === 0) {
                alert('Please add at least one file before turning in.');
                return;
            }
            
            const btn = document.getElementById('turnInBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';
            btn.disabled = true;
            
            const formData = new FormData(e.target);
            // Clear any existing subFiles and add staged files
            formData.delete('subFiles[]');
            stagedFiles.forEach(file => {
                formData.append('subFiles[]', file);
            });
            
            fetch('../api/student-assignments-api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if(data.status === 'success') {
                    alert(data.message || 'Assignment submitted successfully!');
                    location.reload(); 
                } else {
                    alert('Error: ' + data.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(err => {
                alert('Upload failed.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
        
        function unsubmitAssignment(submissionID, assignmentID) {
            if (!confirm('Unsubmit this assignment? Your files will be removed and you can submit again.')) return;
            
            fetch('../api/student-assignments-api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=unsubmit&submissionID=${submissionID}&assignmentID=${assignmentID}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message || 'Submission removed.');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Could not unsubmit'));
                }
            })
            .catch(err => {
                alert('Error. Please try again.');
            });
        }
        
        // Staged files array for Google Classroom-style flow
        let stagedFiles = [];
        
        function addFilesToStaged(input) {
            // Add new files to the staged array
            Array.from(input.files).forEach(file => {
                // Avoid duplicates by name+size
                const exists = stagedFiles.some(f => f.name === file.name && f.size === file.size);
                if (!exists) {
                    stagedFiles.push(file);
                }
            });
            // Clear input so same file can be re-selected if removed
            input.value = '';
            renderStagedFiles();
        }
        
        function removeStagedFile(index) {
            stagedFiles.splice(index, 1);
            renderStagedFiles();
        }
        
        function renderStagedFiles() {
            const preview = document.getElementById('stagedFilesPreview');
            const btn = document.getElementById('turnInBtn');
            if (!preview || !btn) return;
            
            if (stagedFiles.length === 0) {
                preview.innerHTML = '';
                btn.style.background = '#9ca3af';
                btn.style.cursor = 'not-allowed';
                btn.disabled = true;
            } else {
                let html = '';
                stagedFiles.forEach((file, idx) => {
                    html += `
                        <div style="display: flex; align-items: center; gap: 8px; padding: 8px 10px; background: #f3f4f6; border-radius: 6px; margin-bottom: 6px; font-size: 12px;">
                            <i class="fa-solid fa-file" style="color: #8056ff;"></i>
                            <span style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${file.name}</span>
                            <span style="color: #9ca3af; font-size: 10px;">${(file.size / 1024).toFixed(1)} KB</span>
                            <button type="button" onclick="removeStagedFile(${idx})" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 2px 5px; font-size: 14px;" title="Remove">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    `;
                });
                preview.innerHTML = html;
                btn.style.background = '#1f2937';
                btn.style.cursor = 'pointer';
                btn.disabled = false;
            }
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