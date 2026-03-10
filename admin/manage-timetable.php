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
    }


// =========================================================
// FETCH DATA FOR DISPLAY
// =========================================================
// FETCH STAFF WITH CLASS COUNTS
// =========================================================
$staffSql = "SELECT st.staffID, st.staffName, st.staffType,
                    COUNT(cs.scheduleID) AS classCount,
                    GROUP_CONCAT(DISTINCT c.courseID ORDER BY c.courseID SEPARATOR ', ') AS courses
             FROM staff st
             JOIN class_schedule cs ON st.staffID = cs.staffID
             JOIN course c ON cs.courseID = c.courseID
             GROUP BY st.staffID, st.staffName, st.staffType
             ORDER BY st.staffName";
$staffList = $db->query($staffSql);
$staffArr = [];
while ($r = $staffList->fetch_assoc()) { $staffArr[] = $r; }
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
    <link rel="stylesheet" href="../assets/css/timetable.css?v=<?php echo time(); ?>">
    <style>
        .staff-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
        .staff-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:18px 20px; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; gap:14px; }
        .staff-card:hover { border-color:var(--purple-base); box-shadow:0 4px 16px rgba(128,86,255,0.12); transform:translateY(-2px); }
        .staff-card.active { border-color:var(--purple-base); background:linear-gradient(135deg,#f5f0ff,#ede5ff); box-shadow:0 4px 16px rgba(128,86,255,0.18); }
        .staff-avatar { width:44px; height:44px; border-radius:50%; background:linear-gradient(135deg,#8056ff,#a78bfa); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:16px; flex-shrink:0; }
        .staff-card-info h4 { margin:0 0 3px 0; font-size:14px; color:#1f2937; }
        .staff-card-info p { margin:0; font-size:12px; color:#6b7280; }
        .staff-card-badge { margin-left:auto; background:#f3f4f6; color:#6b7280; font-size:11px; font-weight:600; padding:4px 10px; border-radius:20px; flex-shrink:0; }
        .staff-card.active .staff-card-badge { background:var(--purple-base); color:#fff; }
        #staff-timetable-panel { display:none; margin-top:20px; }
        .admin-tt-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px; }
        .admin-tt-title { font-size:17px; font-weight:700; color:#1f2937; display:flex; align-items:center; gap:8px; }
        .admin-tt-title .badge-prog { font-size:12px; }
        .admin-tt-close { background:none; border:1px solid #d1d5db; border-radius:8px; padding:6px 14px; cursor:pointer; font-size:13px; color:#374151; transition:all 0.15s; }
        .admin-tt-close:hover { border-color:#ef4444; color:#ef4444; }
        .staff-search-box { position:relative; max-width:350px; margin-bottom:16px; }
        .staff-search-box input { width:100%; padding:9px 12px 9px 36px; border:1px solid #e0e0e0; border-radius:8px; font-size:14px; box-sizing:border-box; outline:none; }
        .staff-search-box input:focus { border-color:#8056ff; }
        .staff-search-box i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#9ca3af; font-size:14px; }
        .subject-name { font-size:9px; font-weight:500; display:block; margin-bottom:3px; line-height:1.1; opacity:0.95; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%; }
    </style>
</head>
<body>
    <div class="app-bg">
        <div class="main-card">
            <?php $currentPage = 'timetable'; include __DIR__ . '/../includes/adminMaster.php'; ?>

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

                <?php if($message): ?>
                    <div class="msg-box <?php echo $msgType == 'success' ? 'msg-success' : 'msg-error'; ?>" style="margin: 20px 0;">
                        <i class="fa-solid <?php echo $msgType == 'success' ? 'fa-check-circle' : 'fa-triangle-exclamation'; ?>"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <section class="announcements-card" style="margin-top: 20px;">
                    <h3 style="margin:0 0 12px 0; color:#1f2937; font-size:16px;">
                        <i class="fa-solid fa-chalkboard-user" style="color:var(--purple-base);"></i> Lecturers
                        <span style="font-weight:400; font-size:13px; color:#6b7280; margin-left:6px;">(<?php echo count($staffArr); ?> staff with classes)</span>
                    </h3>
                    <div class="staff-search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="staffSearch" placeholder="Search lecturer..." oninput="filterStaffCards()">
                    </div>
                    <div class="staff-grid" id="staff-grid">
                        <?php foreach ($staffArr as $s): ?>
                        <div class="staff-card" data-id="<?php echo htmlspecialchars($s['staffID']); ?>" data-name="<?php echo htmlspecialchars($s['staffName']); ?>" onclick="selectStaff('<?php echo htmlspecialchars($s['staffID']); ?>', '<?php echo htmlspecialchars(addslashes($s['staffName'])); ?>')">
                            <div class="staff-avatar"><?php echo strtoupper(substr($s['staffName'],0,1)); ?></div>
                            <div class="staff-card-info">
                                <h4><?php echo htmlspecialchars($s['staffName']); ?></h4>
                                <p><?php echo htmlspecialchars($s['courses']); ?></p>
                            </div>
                            <div class="staff-card-badge"><?php echo $s['classCount']; ?> classes</div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Timetable Panel (shown when staff is selected) -->
                    <div id="staff-timetable-panel">
                        <div class="admin-tt-header">
                            <div class="admin-tt-title">
                                <i class="fa-solid fa-calendar-week" style="color:var(--purple-base);"></i>
                                <span id="tt-panel-name">—</span>
                                <span class="badge-prog" id="tt-panel-id"></span>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <label for="admin-week-selector" style="font-size:13px; font-weight:500; color:#555;">Week:</label>
                                <select id="admin-week-selector" onchange="loadSelectedWeek()" style="padding:7px 12px; border:1px solid #ccc; border-radius:6px; font-size:13px; min-width:230px; cursor:pointer;">
                                    <option value="">Loading…</option>
                                </select>
                                <button class="admin-tt-close" onclick="closeStaffTimetable()"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                        <div class="timetable-wrapper">
                            <table class="timetable" id="admin-timetable">
                                <thead>
                                    <tr>
                                        <th style="width:80px;">Day</th>
                                        <?php for ($h = 8; $h < 22; $h++): ?>
                                        <th colspan="2"><?php echo sprintf("%02d:00 - %02d:00", $h, $h+1); ?></th>
                                        <?php endfor; ?>
                                    </tr>
                                </thead>
                                <tbody id="admin-timetable-body">
                                    <tr><td colspan="29" style="text-align:center; padding:40px; color:#9ca3af;">Select a lecturer above to view their timetable</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
                
                <?php include __DIR__ . '/../includes/footer.php'; ?>
            </main>
        </div>
    </div>
    
    <script src="../script.js"></script>
    <script>
    // ============================================================
    // STAFF TIMETABLE VIEWER
    // ============================================================
    let selectedStaffID = null;
    let adminTermData = null;
    let adminCurrentWeek = 1;

    function filterStaffCards() {
        const q = document.getElementById('staffSearch').value.toLowerCase().trim();
        document.querySelectorAll('.staff-card').forEach(card => {
            const name = card.dataset.name.toLowerCase();
            const id = card.dataset.id.toLowerCase();
            card.style.display = (!q || name.includes(q) || id.includes(q)) ? '' : 'none';
        });
    }

    async function selectStaff(staffID, staffName) {
        selectedStaffID = staffID;

        // Highlight card
        document.querySelectorAll('.staff-card').forEach(c => c.classList.remove('active'));
        document.querySelector(`.staff-card[data-id="${staffID}"]`)?.classList.add('active');

        // Show panel
        const panel = document.getElementById('staff-timetable-panel');
        panel.style.display = 'block';
        document.getElementById('tt-panel-name').textContent = staffName;
        document.getElementById('tt-panel-id').textContent = staffID;
        document.getElementById('admin-timetable-body').innerHTML = '<tr><td colspan="29" style="text-align:center; padding:40px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:20px;"></i></td></tr>';

        // Load term data (once)
        if (!adminTermData) {
            try {
                const res = await fetch('../api/academicTerm.php?action=getActiveTermForStaff');
                const result = await res.json();
                if (result.success && result.data) {
                    adminTermData = result.data;
                    populateAdminWeekSelector();
                    autoSelectCurrentWeek();
                } else {
                    loadStaffSchedule(staffID, null, '', '');
                    return;
                }
            } catch (e) {
                console.error(e);
                loadStaffSchedule(staffID, null, '', '');
                return;
            }
        }
        loadSelectedWeek();
        panel.scrollIntoView({ behavior:'smooth', block:'start' });
    }

    function populateAdminWeekSelector() {
        const sel = document.getElementById('admin-week-selector');
        sel.innerHTML = '';
        adminTermData.weeks.forEach(w => {
            const opt = document.createElement('option');
            opt.value = w.weekNumber;
            opt.textContent = w.label;
            opt.dataset.startDate = w.startDate;
            opt.dataset.endDate = w.endDate;
            sel.appendChild(opt);
        });
    }

    function autoSelectCurrentWeek() {
        const today = new Date(); today.setHours(0,0,0,0);
        for (const w of adminTermData.weeks) {
            const ws = new Date(w.startDate); ws.setHours(0,0,0,0);
            const we = new Date(w.endDate); we.setHours(23,59,59,999);
            if (today >= ws && today <= we) {
                document.getElementById('admin-week-selector').value = w.weekNumber;
                adminCurrentWeek = w.weekNumber;
                return;
            }
        }
    }

    function loadSelectedWeek() {
        const sel = document.getElementById('admin-week-selector');
        const opt = sel.options[sel.selectedIndex];
        const wk = parseInt(sel.value) || null;
        const ws = opt?.dataset?.startDate || '';
        const we = opt?.dataset?.endDate || '';
        adminCurrentWeek = wk;
        loadStaffSchedule(selectedStaffID, wk, ws, we);
    }

    async function loadStaffSchedule(staffID, weekNumber, weekStartDate, weekEndDate) {
        const tbody = document.getElementById('admin-timetable-body');
        tbody.innerHTML = '<tr><td colspan="29" style="text-align:center; padding:40px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:20px;"></i><p style="margin-top:8px; color:#6b7280;">Loading schedule…</p></td></tr>';

        try {
            let url = `../api/staffTimetable.php?action=getSchedule&staffID=${staffID}`;
            if (weekNumber) url += `&weekNumber=${weekNumber}`;
            if (weekStartDate) url += `&weekStartDate=${weekStartDate}&weekEndDate=${weekEndDate}`;

            const res = await fetch(url);
            const result = await res.json();

            if (result.success && result.data) {
                renderAdminTimetable(result.data, weekStartDate);
            } else {
                tbody.innerHTML = '<tr><td colspan="29" style="text-align:center; padding:40px; color:#ef4444;">' + (result.message || 'No schedule found') + '</td></tr>';
            }
        } catch (e) {
            console.error(e);
            tbody.innerHTML = '<tr><td colspan="29" style="text-align:center; padding:40px; color:#ef4444;">Error loading timetable</td></tr>';
        }
    }

    function renderAdminTimetable(scheduleData, weekStartDate) {
        const startHour = 8, endHour = 22, totalSlots = (endHour - startHour) * 2;
        const daysOfWeek = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
        const colors = ['#8056ff','#e74c3c','#2ecc71','#3498db','#e67e22','#9b59b6','#1abc9c','#f39c12','#e84393','#00cec9'];

        // Day dates
        const dayDates = {};
        if (weekStartDate) {
            const base = new Date(weekStartDate + 'T00:00:00');
            daysOfWeek.forEach((day, idx) => {
                const d = new Date(base); d.setDate(base.getDate() + idx);
                dayDates[day] = String(d.getDate()).padStart(2,'0') + '-' + String(d.getMonth()+1).padStart(2,'0');
            });
        }

        // Build matrix
        const matrix = {};
        const courseColorMap = {};
        let colorIdx = 0;
        scheduleData.forEach(row => {
            const day = row.day;
            if (!matrix[day]) { matrix[day] = new Array(totalSlots).fill(null); }
            if (!courseColorMap[row.courseID]) { courseColorMap[row.courseID] = colors[colorIdx++ % colors.length]; }

            const [sh, sm] = row.startTime.split(':').map(Number);
            const [eh, em] = row.endTime.split(':').map(Number);
            const startSlot = (sh - startHour) * 2 + (sm >= 30 ? 1 : 0);
            const endSlot = (eh - startHour) * 2 + (em >= 30 ? 1 : 0);
            const span = endSlot - startSlot;
            if (startSlot >= 0 && startSlot < totalSlots) {
                matrix[day][startSlot] = { ...row, span, color: courseColorMap[row.courseID] };
                for (let i = startSlot + 1; i < startSlot + span && i < totalSlots; i++) {
                    matrix[day][i] = 'spanned';
                }
            }
        });

        const tbody = document.getElementById('admin-timetable-body');
        tbody.innerHTML = '';
        daysOfWeek.forEach(day => {
            const tr = document.createElement('tr');
            const dayTd = document.createElement('td');
            dayTd.className = 'day-label';
            dayTd.innerHTML = `<strong>${day.substring(0,3)}</strong>${dayDates[day] ? '<br><small style="font-size:10px; opacity:0.7;">' + dayDates[day] + '</small>' : ''}`;
            tr.appendChild(dayTd);

            const slots = matrix[day] || new Array(totalSlots).fill(null);
            for (let i = 0; i < totalSlots; i++) {
                if (slots[i] === 'spanned') continue;
                const td = document.createElement('td');
                if (slots[i] && typeof slots[i] === 'object') {
                    const s = slots[i];
                    td.colSpan = s.span;
                    td.className = 'has-class';
                    td.style.cssText = `background:${s.color}; color:#fff; border-radius:6px; padding:4px 6px; cursor:pointer; position:relative;`;
                    td.innerHTML = `<span class="subject-name">${s.courseID}</span><small style="font-size:8px; opacity:0.85;">${s.classType}<br>${s.facilityName || ''}</small>`;
                    td.title = `${s.courseID} — ${s.courseName}\n${s.classType} | ${s.facilityName}\n${s.startTime.substring(0,5)} - ${s.endTime.substring(0,5)}${s.tutGroup ? '\nGroup: ' + s.tutGroup : ''}${s.isReplacement ? '\n⟳ Replacement class' : ''}`;
                    if (s.isReplacement) {
                        td.style.outline = '2px dashed #facc15';
                        td.style.outlineOffset = '-2px';
                    }
                } else {
                    td.innerHTML = '&nbsp;';
                }
                tr.appendChild(td);
            }
            tbody.appendChild(tr);
        });
    }

    function closeStaffTimetable() {
        document.getElementById('staff-timetable-panel').style.display = 'none';
        document.querySelectorAll('.staff-card').forEach(c => c.classList.remove('active'));
        selectedStaffID = null;
    }

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
                    const reviewed = r.reviewed_at ? (() => { const _d = new Date(r.reviewed_at); return `${String(_d.getDate()).padStart(2,'0')}-${String(_d.getMonth()+1).padStart(2,'0')}-${_d.getFullYear()}`; })() : '-';
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