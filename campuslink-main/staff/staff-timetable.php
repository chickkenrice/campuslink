<?php
session_start();
require_once('./includes/config.php');

// 1. SECURITY HEADERS
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 2. AUTH CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit;
}

$staffID = $_SESSION['user_id'];
$db = get_db_connection();
$message = '';
$msgType = '';

// =========================================================
// HANDLE REPLACEMENT REQUEST (POST)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_replacement') {
    $scheduleID = $_POST['scheduleID'];
    $newDate = $_POST['newDate'];
    $newTime = $_POST['newTime']; // e.g. "10:00 - 12:00"
    $reason = trim($_POST['reason']);

    // Insert using your specific fields
    $stmt = $db->prepare("INSERT INTO replacement_request (scheduleID, newDate, newTime, reason) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $scheduleID, $newDate, $newTime, $reason);
    
    if ($stmt->execute()) {
        $message = "Request submitted successfully.";
        $msgType = "success";
    } else {
        $message = "Error submitting request: " . $db->error;
        $msgType = "error";
    }
}

// =========================================================
// FETCH DATA
// =========================================================

// 1. My Timetable
$sqlSchedule = "SELECT s.scheduleID, s.day, s.startTime, s.endTime, s.classType, 
                c.courseName, c.courseID, f.facilityName 
                FROM class_schedule s
                JOIN course c ON s.courseID = c.courseID
                JOIN facility f ON s.facilityID = f.facilityID
                WHERE s.staffID = ?
                ORDER BY FIELD(s.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), s.startTime";
$stmt = $db->prepare($sqlSchedule);
$stmt->bind_param("s", $staffID);
$stmt->execute();
$mySchedule = $stmt->get_result();

// 2. My Request History
// We join class_schedule to ensure we only show requests for this staff member
$sqlRequests = "SELECT r.replacementID, r.newDate, r.newTime, r.reason, r.status, 
                c.courseName, c.courseID 
                FROM replacement_request r
                JOIN class_schedule s ON r.scheduleID = s.scheduleID
                JOIN course c ON s.courseID = c.courseID
                WHERE s.staffID = ?
                ORDER BY r.created_at DESC";
$stmt = $db->prepare($sqlRequests);
$stmt->bind_param("s", $staffID);
$stmt->execute();
$myRequests = $stmt->get_result();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff - My Timetable</title>
    <link rel="stylesheet" href="assets\css\manage-students.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Status Badges */
        .status-Pending { background: #fff8e1; color: #f57c00; border: 1px solid #ffe0b2; }
        .status-Approved { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .status-Rejected { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        .badge-status { padding: 5px 10px; font-weight: 700; font-size: 11px; text-transform: uppercase; display:inline-block; }
        
        .msg-box { padding: 15px; margin-bottom: 20px; font-weight: 600; display:flex; gap:10px; align-items:center; }
        .msg-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .msg-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .section-title { font-size: 16px; font-weight: 700; color: var(--text-main); margin: 0 0 15px 0; display: flex; align-items: center; gap: 8px; }
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
                        <span class="brand-tagline">Staff Portal</span>
                    </div>
                </div>
                <nav class="sidebar-nav">
                    <ul>
                        <li><a href="staff-dashboard.php" class="nav-item"><i class="fa-solid fa-home"></i> Dashboard</a></li>
                        <li><a href="#" class="nav-item is-active"><i class="fa-solid fa-calendar-week"></i> My Timetable</a></li>
                        <li><a href="logout.php" class="nav-item"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
                    </ul>
                </nav>
            </aside>

            <main class="dashboard">
                <header class="dashboard-topbar">
                    <div>
                        <h1 class="welcome-title">Timetable Management</h1>
                        <p style="color: var(--text-sub); font-size: 14px;">View your schedule and request replacements</p>
                    </div>
                </header>

                <?php if($message): ?>
                    <div class="msg-box <?php echo $msgType == 'success' ? 'msg-success' : 'msg-error'; ?>">
                        <i class="fa-solid <?php echo $msgType == 'success' ? 'fa-check-circle' : 'fa-triangle-exclamation'; ?>"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <section class="announcements-card" style="margin-bottom: 30px; padding: 25px;">
                    <h3 class="section-title"><i class="fa-solid fa-list"></i> Weekly Schedule</h3>
                    <div class="table-container" style="height: auto; max-height: 400px;">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Course</th>
                                    <th>Type</th>
                                    <th>Venue</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($mySchedule->num_rows > 0): ?>
                                    <?php while($row = $mySchedule->fetch_assoc()): ?>
                                    <tr>
                                        <td><span class="badge-prog" style="background:#f3e5ff; color:#8056ff;"><?php echo $row['day']; ?></span></td>
                                        <td style="font-weight:600;">
                                            <?php echo substr($row['startTime'], 0, 5) . ' - ' . substr($row['endTime'], 0, 5); ?>
                                        </td>
                                        <td><strong><?php echo $row['courseID']; ?></strong><br><small><?php echo $row['courseName']; ?></small></td>
                                        <td><?php echo $row['classType']; ?></td>
                                        <td><?php echo $row['facilityName']; ?></td>
                                        <td>
                                            <button class="btn" style="padding:8px 16px; font-size:12px; background:#4a90e2;" 
                                                onclick='openRequestModal(<?php echo json_encode($row); ?>)'>
                                                Request Change
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" style="text-align:center; padding:30px;">No classes found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="announcements-card" style="padding: 25px;">
                    <h3 class="section-title"><i class="fa-solid fa-clock-rotate-left"></i> Replacement Request History</h3>
                    <div class="table-container" style="height: auto; max-height: 400px;">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Course</th>
                                    <th>Requested Slot</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($myRequests->num_rows > 0): ?>
                                    <?php while($req = $myRequests->fetch_assoc()): ?>
                                    <tr>
                                        <td class="id-cell">#<?php echo $req['replacementID']; ?></td>
                                        <td><strong><?php echo $req['courseID']; ?></strong><br><small><?php echo $req['courseName']; ?></small></td>
                                        <td>
                                            <div style="font-weight:600; color:var(--purple-base);">
                                                <?php echo $req['newDate']; ?>
                                            </div>
                                            <small><?php echo $req['newTime']; ?></small>
                                        </td>
                                        <td style="max-width:250px; font-size:13px;"><?php echo $req['reason']; ?></td>
                                        <td>
                                            <span class="badge-status status-<?php echo $req['status']; ?>">
                                                <?php echo $req['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" style="text-align:center; padding:30px;">No requests history.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <div id="reqModal" class="modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:1000;">
        <div class="modal-content" style="background:white; padding:30px; width:450px; max-width:90%; position:relative;">
            <span style="position:absolute; top:20px; right:20px; cursor:pointer; font-size:24px;" onclick="closeModal()">&times;</span>
            <h2 style="margin-top:0; color:var(--purple-base);">Request Replacement</h2>
            <p id="modalSubInfo" style="font-size:13px; color:#666; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:10px;"></p>
            
            <form method="POST">
                <input type="hidden" name="action" value="request_replacement">
                <input type="hidden" name="scheduleID" id="inpScheduleID">

                <div style="margin-bottom:15px;">
                    <label style="display:block; font-weight:700; font-size:12px; margin-bottom:5px;">New Date</label>
                    <input type="date" name="newDate" required style="width:100%; padding:10px; border:1px solid #ccc;">
                </div>

                <div style="margin-bottom:15px;">
                    <label style="display:block; font-weight:700; font-size:12px; margin-bottom:5px;">New Time Slot</label>
                    <input type="text" name="newTime" placeholder="e.g. 10:00 - 12:00" required style="width:100%; padding:10px; border:1px solid #ccc;">
                </div>

                <div style="margin-bottom:20px;">
                    <label style="display:block; font-weight:700; font-size:12px; margin-bottom:5px;">Reason</label>
                    <textarea name="reason" rows="3" required style="width:100%; padding:10px; border:1px solid #ccc;"></textarea>
                </div>

                <button type="submit" class="btn" style="width:100%;">Submit Request</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('reqModal');
        
        function openRequestModal(data) {
            modal.style.display = 'flex';
            document.getElementById('inpScheduleID').value = data.scheduleID;
            
            let timeStr = data.startTime.substring(0,5) + ' - ' + data.endTime.substring(0,5);
            document.getElementById('modalSubInfo').innerText = 
                `Class: ${data.courseID} (${data.classType})\nCurrent: ${data.day}, ${timeStr}`;
        }

        function closeModal() {
            modal.style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == modal) closeModal();
        }
    </script>
</body>
</html>