<?php
session_start();
require_once(__DIR__ . '/../includes/config.php');
require_once(__DIR__ . '/../includes/activity-logger.php');

// 1. SECURITY: Only Admin allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
prevent_back_button_cache();

$db = get_db_connection();
$message = '';
$msgType = '';

// =========================================================
// DATABASE WRITE OPERATIONS (This section updates the DB)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    // --- DELETE ACTION ---
    if ($action === 'delete') {
        $id = $_POST['id'];
        
        // Get schedule info before deleting for logging
        $getScheduleStmt = $db->prepare("SELECT c.courseName, s.staffName, f.facilityName, cs.day, cs.startTime, cs.endTime FROM class_schedule cs JOIN course c ON cs.courseID = c.courseID JOIN staff s ON cs.staffID = s.staffID JOIN facility f ON cs.facilityID = f.facilityID WHERE cs.scheduleID = ?");
        $getScheduleStmt->bind_param("i", $id);
        $getScheduleStmt->execute();
        $scheduleInfo = $getScheduleStmt->get_result()->fetch_assoc();
        $getScheduleStmt->close();
        
        // This SQL command REMOVES the row from your database immediately
        $stmt = $db->prepare("DELETE FROM class_schedule WHERE scheduleID = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // Log schedule deletion
            if ($scheduleInfo) {
                logActivity($db, $_SESSION['user_id'], 'SCHEDULE_DELETE', 'Deleted class schedule: ' . $scheduleInfo['courseName'], [
                    'scheduleID' => $id,
                    'course' => $scheduleInfo['courseName'],
                    'staff' => $scheduleInfo['staffName'],
                    'facility' => $scheduleInfo['facilityName'],
                    'day' => $scheduleInfo['day'],
                    'time' => $scheduleInfo['startTime'] . ' - ' . $scheduleInfo['endTime']
                ]);
            }
            
            $message = "Class entry deleted successfully.";
            $msgType = 'success';
        } else {
            $message = "Error deleting class.";
            $msgType = 'error';
        }
    }

    // --- CREATE ACTION ---
    elseif ($action === 'create') {
        // Collect form data
        $courseID = $_POST['courseID'];
        $staffID = $_POST['staffID'];
        $facilityID = $_POST['facilityID'];
        $day = $_POST['day'];
        $startTime = $_POST['startTime'];
        $endTime = $_POST['endTime'];
        $classType = $_POST['classType'];

        // 1. CONFLICT CHECK: Room Occupied?
        $checkSql = "SELECT * FROM class_schedule WHERE facilityID = ? AND day = ? AND (startTime < ? AND endTime > ?)";
        $stmt = $db->prepare($checkSql);
        $stmt->bind_param("ssss", $facilityID, $day, $endTime, $startTime);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $message = "Conflict: That ROOM is already booked!";
            $msgType = 'error';
        } else {
            // 2. CONFLICT CHECK: Lecturer Busy?
            $stmt = $db->prepare("SELECT * FROM class_schedule WHERE staffID = ? AND day = ? AND (startTime < ? AND endTime > ?)");
            $stmt->bind_param("ssss", $staffID, $day, $endTime, $startTime);
            $stmt->execute();

            if ($stmt->get_result()->num_rows > 0) {
                $message = "Conflict: That LECTURER is already teaching!";
                $msgType = 'error';
            } else {
                // 3. NO CONFLICT -> SAVE TO DATABASE
                // This SQL command ADDS the new row to your database immediately
                $insertSql = "INSERT INTO class_schedule (courseID, staffID, facilityID, day, startTime, endTime, classType) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($insertSql);
                $stmt->bind_param("sssssss", $courseID, $staffID, $facilityID, $day, $startTime, $endTime, $classType);
                
                if ($stmt->execute()) {
                    // Get course, staff, and facility names for logging
                    $getCourseStmt = $db->prepare("SELECT courseName FROM course WHERE courseID = ?");
                    $getCourseStmt->bind_param("s", $courseID);
                    $getCourseStmt->execute();
                    $courseName = $getCourseStmt->get_result()->fetch_assoc()['courseName'];
                    $getCourseStmt->close();
                    
                    $getStaffStmt = $db->prepare("SELECT staffName FROM staff WHERE staffID = ?");
                    $getStaffStmt->bind_param("s", $staffID);
                    $getStaffStmt->execute();
                    $staffName = $getStaffStmt->get_result()->fetch_assoc()['staffName'];
                    $getStaffStmt->close();
                    
                    $getFacilityStmt = $db->prepare("SELECT facilityName FROM facility WHERE facilityID = ?");
                    $getFacilityStmt->bind_param("s", $facilityID);
                    $getFacilityStmt->execute();
                    $facilityName = $getFacilityStmt->get_result()->fetch_assoc()['facilityName'];
                    $getFacilityStmt->close();
                    
                    // Log schedule creation
                    logActivity($db, $_SESSION['user_id'], 'SCHEDULE_CREATE', "Created class schedule: $courseName", [
                        'course' => $courseName,
                        'staff' => $staffName,
                        'facility' => $facilityName,
                        'day' => $day,
                        'time' => $startTime . ' - ' . $endTime,
                        'classType' => $classType
                    ]);
                    
                    $message = "Class scheduled successfully!";
                    $msgType = 'success';
                } else {
                    $message = "Database Error: " . $db->error;
                    $msgType = 'error';
                }
            }
        }
    }
}

// =========================================================
// FETCH DATA FOR DISPLAY
// =========================================================
$courses = $db->query("SELECT * FROM course ORDER BY courseName");
$facilities = $db->query("SELECT * FROM facility WHERE status = 'Active' ORDER BY facilityName");
$lecturers = $db->query("SELECT * FROM staff ORDER BY staffName");

$scheduleSql = "SELECT s.scheduleID, s.day, s.startTime, s.endTime, s.classType,
                c.courseName, c.courseID, 
                f.facilityName, 
                st.staffName 
                FROM class_schedule s
                JOIN course c ON s.courseID = c.courseID
                JOIN facility f ON s.facilityID = f.facilityID
                JOIN staff st ON s.staffID = st.staffID
                ORDER BY FIELD(s.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), s.startTime";
$scheduleList = $db->query($scheduleSql);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Console — Timetable</title>
    
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="app-bg">
        <div class="main-card">
            <aside class="sidebar">
                <div class="sidebar-head">
                    <div class="brand-icon">CL</div>
                    <div class="brand-text">
                        <span class="brand-name">CAMPUSLink</span>
                        <span class="brand-tagline">Admin Console</span>
                    </div>
                </div>
                <nav class="sidebar-nav">
                    <ul>
                        <li>
                            <a href="manage-students.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-users-gear"></i></span>
                                <span class="nav-label">User Management</span>
                            </a>
                        </li>
                        <li>
                            <a href="activity-logs.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-chart-line"></i></span>
                                <span class="nav-label">Activity Logs</span>
                            </a>
                        </li>
                        <li>
                            <a href="manage-timetable.php" class="nav-item is-active">
                                <span class="nav-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                <span class="nav-label">Timetable</span>
                            </a>
                        </li>
                        <li>
                            <a href="manage-resources.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-building"></i></span>
                                <span class="nav-label">Resources</span>
                            </a>
                        </li>
                        <li>
                            <a href="manage-offerings.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-book-open"></i></span>
                                <span class="nav-label">Course Offerings</span>
                            </a>
                        </li>
                        <li>
                            <a href="registration-monitor.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-chart-bar"></i></span>
                                <span class="nav-label">Registration Monitor</span>
                            </a>
                        </li>
                        <li>
                            <a href="../logout.php" class="nav-item" style="margin-top: 20px;">
                                <span class="nav-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                                <span class="nav-label" style="color: white;">Logout</span>
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
                                <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                                <span class="user-role">Admin</span>
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
                        <h1 class="welcome-title">Master Timetable</h1>
                        <p class="welcome-text">Manage and schedule classes across all programmes</p>
                    </div>
                </section>
                
                <!-- ============================================ -->
                <!-- REPLACEMENT REQUESTS SECTION               -->
                <!-- ============================================ -->
                <section class="form-card" style="margin-top: 20px;">
                    <div class="replacement-header" onclick="toggleReplacementRequests()" style="cursor:pointer; display:flex; align-items:center; justify-content:space-between;">
                        <h3 style="margin:0; color:var(--purple-base);">
                            <i class="fa-solid fa-rotate"></i> Pending Tutor Replacement Requests
                            <span id="admin-pending-badge" style="display:none; background:#ef4444; color:#fff; font-size:0.7rem; font-weight:700; min-width:20px; height:20px; border-radius:10px; align-items:center; justify-content:center; padding:0 6px; margin-left:6px;">0</span>
                        </h3>
                        <i id="rr-admin-chevron" class="fa-solid fa-chevron-down" style="transition: transform 0.3s; color:var(--purple-base);"></i>
                    </div>
                    <div id="rr-admin-container" style="display:none; margin-top:15px;">
                        <div id="rr-admin-msg" style="display:none; padding:10px; border-radius:8px; margin-bottom:10px;"></div>
                        <div class="table-container" style="max-height:400px;">
                            <table class="student-table" id="rr-admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tutor</th>
                                        <th>Subject</th>
                                        <th>Original Date</th>
                                        <th>Replacement</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="rr-admin-body">
                                    <tr><td colspan="8" style="text-align:center; padding:20px;">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top:10px; text-align:right;">
                            <button class="btn-add" onclick="toggleRequestHistoryAdmin()" style="background:#6b7280; font-size:0.85rem; padding:6px 14px;">
                                <i class="fa-solid fa-clock-rotate-left"></i> View History
                            </button>
                        </div>
                        <div id="rr-history-container" style="display:none; margin-top:15px;">
                            <h4 style="color:#6b7280; margin-bottom:10px;"><i class="fa-solid fa-history"></i> Request History</h4>
                            <div class="table-container" style="max-height:300px;">
                                <table class="student-table">
                                    <thead>
                                        <tr><th>ID</th><th>Tutor</th><th>Subject</th><th>Reason</th><th>Status</th><th>Reviewed</th></tr>
                                    </thead>
                                    <tbody id="rr-history-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Approve Modal -->
                <div id="approve-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                    <div style="background:#fff; border-radius:16px; padding:30px; max-width:500px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                        <h3 style="margin-top:0; color:var(--purple-base);"><i class="fa-solid fa-check-circle"></i> Approve Replacement Request</h3>
                        <input type="hidden" id="approve-rr-id">
                        <div id="approve-request-info" style="background:#f8f9fa; padding:12px; border-radius:8px; margin-bottom:15px; font-size:0.9rem;"></div>
                        <div class="form-group" style="margin-bottom:15px;">
                            <label style="font-weight:600; display:block; margin-bottom:5px;">Assign Venue</label>
                            <select id="approve-facilityID" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px;">
                                <option value="">Loading facilities...</option>
                            </select>
                            <small id="approve-venue-status" style="color:#666;"></small>
                        </div>
                        <div class="form-group" style="margin-bottom:15px;">
                            <label style="font-weight:600; display:block; margin-bottom:5px;">Admin Notes (optional)</label>
                            <textarea id="approve-notes" rows="2" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; resize:vertical;" placeholder="Any notes for the tutor..."></textarea>
                        </div>
                        <div style="display:flex; gap:10px; justify-content:flex-end;">
                            <button onclick="closeApproveModal()" style="padding:8px 20px; border:1px solid #d1d5db; background:#fff; border-radius:8px; cursor:pointer;">Cancel</button>
                            <button onclick="confirmApprove()" style="padding:8px 20px; background:var(--purple-base); color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600;">Approve</button>
                        </div>
                    </div>
                </div>

                <!-- Reject Modal -->
                <div id="reject-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                    <div style="background:#fff; border-radius:16px; padding:30px; max-width:450px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                        <h3 style="margin-top:0; color:#dc2626;"><i class="fa-solid fa-times-circle"></i> Reject Replacement Request</h3>
                        <input type="hidden" id="reject-rr-id">
                        <div id="reject-request-info" style="background:#fef2f2; padding:12px; border-radius:8px; margin-bottom:15px; font-size:0.9rem;"></div>
                        <div class="form-group" style="margin-bottom:15px;">
                            <label style="font-weight:600; display:block; margin-bottom:5px;">Reason for Rejection</label>
                            <textarea id="reject-notes" rows="3" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; resize:vertical;" placeholder="Explain why this request is being rejected..."></textarea>
                        </div>
                        <div style="display:flex; gap:10px; justify-content:flex-end;">
                            <button onclick="closeRejectModal()" style="padding:8px 20px; border:1px solid #d1d5db; background:#fff; border-radius:8px; cursor:pointer;">Cancel</button>
                            <button onclick="confirmReject()" style="padding:8px 20px; background:#dc2626; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:600;">Reject</button>
                        </div>
                    </div>
                </div>

                <section class="form-card" style="margin-top: 20px;">
                    <?php if($message): ?>
                        <div class="msg-box <?php echo $msgType == 'success' ? 'msg-success' : 'msg-error'; ?>">
                            <i class="fa-solid <?php echo $msgType == 'success' ? 'fa-check-circle' : 'fa-triangle-exclamation'; ?>"></i>
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <h3 style="margin-top:0; color:var(--purple-base);"><i class="fa-solid fa-plus-circle"></i> Add Class Entry</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Course</label>
                                <select name="courseID" required>
                                    <option value="" disabled selected>Select Course...</option>
                                    <?php while($c = $courses->fetch_assoc()): ?>
                                        <option value="<?php echo $c['courseID']; ?>"><?php echo $c['courseID'] . " - " . $c['courseName']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Lecturer</label>
                                <select name="staffID" required>
                                    <option value="" disabled selected>Select Lecturer...</option>
                                    <?php while($s = $lecturers->fetch_assoc()): ?>
                                        <option value="<?php echo $s['staffID']; ?>"><?php echo $s['staffName']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Venue</label>
                                <select name="facilityID" required>
                                    <option value="" disabled selected>Select Room...</option>
                                    <?php while($f = $facilities->fetch_assoc()): ?>
                                        <option value="<?php echo $f['facilityID']; ?>"><?php echo $f['facilityName']; ?> (<?php echo $f['type']; ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Type</label>
                                <select name="classType" required>
                                    <option>Lecture</option><option>Tutorial</option><option>Practical</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Day</label>
                                <select name="day" required>
                                    <option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option>
                                </select>
                            </div>
                            <div class="form-group" style="display:flex; gap:10px;">
                                <div style="flex:1;">
                                    <label>Start Time</label>
                                    <input type="time" name="startTime" required>
                                </div>
                                <div style="flex:1;">
                                    <label>End Time</label>
                                    <input type="time" name="endTime" required>
                                </div>
                            </div>
                            <button type="submit" class="btn-add">Add to Schedule</button>
                        </div>
                    </form>
                </section>

                <section class="announcements-card">
                    <div class="table-container" style="max-height: calc(100vh - 520px);">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Course</th>
                                    <th>Type</th>
                                    <th>Venue</th>
                                    <th>Lecturer</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($scheduleList->num_rows > 0): ?>
                                    <?php while($row = $scheduleList->fetch_assoc()): ?>
                                    <tr>
                                        <td><span class="badge-prog"><?php echo $row['day']; ?></span></td>
                                        <td style="font-weight:600;"><?php echo substr($row['startTime'], 0, 5) . ' - ' . substr($row['endTime'], 0, 5); ?></td>
                                        <td><strong><?php echo $row['courseID']; ?></strong><br><small><?php echo $row['courseName']; ?></small></td>
                                        <td><?php echo $row['classType']; ?></td>
                                        <td><?php echo $row['facilityName']; ?></td>
                                        <td><?php echo $row['staffName']; ?></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Delete this class?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $row['scheduleID']; ?>">
                                                <button type="submit" class="action-btn btn-delete"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" style="text-align:center; padding:30px;">No classes scheduled.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>
    
    <script src="../script.js"></script>
    <script>
    // ============================================================
    // REPLACEMENT REQUEST MANAGEMENT
    // ============================================================
    let allRequests = [];

    document.addEventListener('DOMContentLoaded', function() {
        loadReplacementRequests();
    });

    function toggleReplacementRequests() {
        const container = document.getElementById('rr-admin-container');
        const chevron = document.getElementById('rr-admin-chevron');
        const isOpen = container.style.display !== 'none';
        container.style.display = isOpen ? 'none' : 'block';
        chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
    }

    function toggleRequestHistoryAdmin() {
        const container = document.getElementById('rr-history-container');
        container.style.display = container.style.display === 'none' ? 'block' : 'none';
    }

    async function loadReplacementRequests() {
        try {
            const res = await fetch('../api/replacement_request.php?action=list');
            const result = await res.json();
            if (!result.success) return;
            
            allRequests = result.data;
            const pending = allRequests.filter(r => r.status === 'Pending');
            const history = allRequests.filter(r => r.status !== 'Pending');

            // Update badge
            const badge = document.getElementById('admin-pending-badge');
            if (pending.length > 0) {
                badge.textContent = pending.length;
                badge.style.display = 'inline-flex';
            } else {
                badge.style.display = 'none';
            }

            // Render pending table
            const tbody = document.getElementById('rr-admin-body');
            if (pending.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:20px; color:#666;">No pending requests</td></tr>';
            } else {
                tbody.innerHTML = pending.map(r => `
                    <tr>
                        <td><strong>#${r.replacementID}</strong></td>
                        <td>${r.staffName}</td>
                        <td><strong>${r.courseID}</strong><br><small>${r.courseName}</small></td>
                        <td><strong>${r.originalDate || '-'}</strong><br><small>${r.originalDay} ${r.originalStart?.substring(0,5)}-${r.originalEnd?.substring(0,5)}</small><br><small>${r.originalVenue || ''}</small></td>
                        <td>${r.newDate}<br><small>${r.newTime}</small>${r.newVenue ? '<br><small>' + r.newVenue + '</small>' : ''}</td>
                        <td>${r.reason}</td>
                        <td><span class="badge-prog" style="background:#fef3c7; color:#92400e;">Pending</span></td>
                        <td style="white-space:nowrap;">
                            <button class="action-btn" onclick="openApproveModal(${r.replacementID})" style="color:#16a34a; border:1px solid #16a34a; padding:4px 8px; border-radius:6px; cursor:pointer; background:transparent; margin-right:4px;" title="Approve">
                                <i class="fa-solid fa-check"></i>
                            </button>
                            <button class="action-btn" onclick="openRejectModal(${r.replacementID})" style="color:#dc2626; border:1px solid #dc2626; padding:4px 8px; border-radius:6px; cursor:pointer; background:transparent;" title="Reject">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');
            }

            // Render history table
            const historyBody = document.getElementById('rr-history-body');
            if (history.length === 0) {
                historyBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:15px; color:#666;">No history yet</td></tr>';
            } else {
                historyBody.innerHTML = history.map(r => {
                    const statusColor = r.status === 'Approved' ? 'background:#dcfce7; color:#166534;' : 'background:#fee2e2; color:#991b1b;';
                    const reviewed = r.reviewed_at ? new Date(r.reviewed_at).toLocaleDateString() : '-';
                    return `<tr>
                        <td>#${r.replacementID}</td>
                        <td>${r.staffName}</td>
                        <td><strong>${r.courseID}</strong><br><small>${r.courseName}</small></td>
                        <td>${r.reason}</td>
                        <td><span class="badge-prog" style="${statusColor}">${r.status}</span>${r.adminNotes ? '<br><small style="color:#666;">' + r.adminNotes + '</small>' : ''}</td>
                        <td><small>${reviewed}</small></td>
                    </tr>`;
                }).join('');
            }
        } catch (e) {
            console.error('Error loading requests:', e);
        }
    }

    // --- Approve Modal ---
    function openApproveModal(id) {
        const req = allRequests.find(r => r.replacementID == id);
        if (!req) return;

        document.getElementById('approve-rr-id').value = id;
        document.getElementById('approve-request-info').innerHTML = `
            <strong>${req.staffName}</strong> requests to replace<br>
            <strong>${req.courseID}</strong> on <strong>${req.originalDate || req.originalDay}</strong> (${req.originalDay} ${req.originalStart?.substring(0,5)}-${req.originalEnd?.substring(0,5)})<br>
            → Move to <strong>${req.newDate}</strong> at <strong>${req.newTime}</strong><br>
            Reason: ${req.reason}
        `;
        document.getElementById('approve-notes').value = '';
        document.getElementById('approve-modal').style.display = 'flex';

        // Load available facilities for the requested slot
        loadFacilitiesForApproval(req.newDate, req.newTime);
    }

    async function loadFacilitiesForApproval(date, timeRange) {
        const select = document.getElementById('approve-facilityID');
        const status = document.getElementById('approve-venue-status');
        select.innerHTML = '<option value="">Loading...</option>';
        status.textContent = '';

        const parts = timeRange.split(' - ');
        if (parts.length < 2) return;

        try {
            const res = await fetch(`../api/replacement_request.php?action=getAvailableFacilities&date=${date}&startTime=${parts[0]}&endTime=${parts[1]}`);
            const result = await res.json();
            select.innerHTML = '<option value="">-- Select Venue --</option>';
            if (result.success) {
                let availCount = 0;
                result.data.forEach(f => {
                    const opt = document.createElement('option');
                    opt.value = f.facilityID;
                    if (f.available) {
                        opt.textContent = `${f.facilityID} - ${f.facilityName} (${f.type}) ✓`;
                        availCount++;
                    } else {
                        opt.textContent = `${f.facilityID} - ${f.facilityName} (${f.type}) ✗ Occupied`;
                        opt.disabled = true;
                        opt.style.color = '#999';
                    }
                    select.appendChild(opt);
                });
                status.textContent = `${availCount} venue(s) available`;
                status.style.color = availCount > 0 ? '#16a34a' : '#dc2626';
            }
        } catch (e) {
            select.innerHTML = '<option value="">Error loading facilities</option>';
        }
    }

    function closeApproveModal() {
        document.getElementById('approve-modal').style.display = 'none';
    }

    async function confirmApprove() {
        const id = document.getElementById('approve-rr-id').value;
        const facilityID = document.getElementById('approve-facilityID').value;
        const adminNotes = document.getElementById('approve-notes').value;

        try {
            const res = await fetch('../api/replacement_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'approve',
                    replacementID: parseInt(id),
                    facilityID: facilityID || null,
                    adminNotes: adminNotes
                })
            });
            const result = await res.json();
            closeApproveModal();
            if (result.success) {
                showAdminMsg(result.message, 'success');
                loadReplacementRequests();
            } else {
                showAdminMsg(result.message, 'error');
            }
        } catch (e) {
            showAdminMsg('Failed to approve request', 'error');
        }
    }

    // --- Reject Modal ---
    function openRejectModal(id) {
        const req = allRequests.find(r => r.replacementID == id);
        if (!req) return;

        document.getElementById('reject-rr-id').value = id;
        document.getElementById('reject-request-info').innerHTML = `
            <strong>${req.staffName}</strong> — ${req.courseID}<br>
            Original: <strong>${req.originalDate || req.originalDay}</strong> (${req.originalDay} ${req.originalStart?.substring(0,5)}-${req.originalEnd?.substring(0,5)})<br>
            → Replacement: ${req.newDate} ${req.newTime}<br>
            Reason: ${req.reason}
        `;
        document.getElementById('reject-notes').value = '';
        document.getElementById('reject-modal').style.display = 'flex';
    }

    function closeRejectModal() {
        document.getElementById('reject-modal').style.display = 'none';
    }

    async function confirmReject() {
        const id = document.getElementById('reject-rr-id').value;
        const adminNotes = document.getElementById('reject-notes').value;

        try {
            const res = await fetch('../api/replacement_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'reject',
                    replacementID: parseInt(id),
                    adminNotes: adminNotes
                })
            });
            const result = await res.json();
            closeRejectModal();
            if (result.success) {
                showAdminMsg(result.message, 'success');
                loadReplacementRequests();
            } else {
                showAdminMsg(result.message, 'error');
            }
        } catch (e) {
            showAdminMsg('Failed to reject request', 'error');
        }
    }

    function showAdminMsg(msg, type) {
        const div = document.getElementById('rr-admin-msg');
        div.style.display = 'block';
        div.style.background = type === 'success' ? '#dcfce7' : '#fee2e2';
        div.style.color = type === 'success' ? '#166534' : '#991b1b';
        div.style.border = `1px solid ${type === 'success' ? '#86efac' : '#fca5a5'}`;
        div.textContent = msg;
        setTimeout(() => { div.style.display = 'none'; }, 5000);
    }
    </script>
</body>
</html>