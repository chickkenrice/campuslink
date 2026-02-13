<?php
session_start();

// 1. CONFIG & SECURITY
require_once(__DIR__ . '/../includes/config.php'); 

if (!validate_session() || strcasecmp($_SESSION['role'], 'staff') !== 0) {
    header("Location: ../login.php");
    exit;
}

$userID = $_SESSION['user_id'];
$db = get_db_connection();

// 2. GET STAFF INFO
$stmt = $db->prepare("SELECT staffName, staffType FROM staff WHERE staffID = ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();

$staffName = $staff['staffName'] ?? 'Staff';
$staffType = $staff['staffType'] ?? 'Lecturer';

// 3. Constants for timetable
$startHour = 8;
$endHour = 22;
$totalSlots = ($endHour - $startHour) * 2;
$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Timetable - CAMPUSLink</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/timetable.css">
    
    <style>
        .subject-name {
            font-size: 9px;
            font-weight: 500;
            display: block;
            margin-bottom: 3px;
            line-height: 1.1;
            opacity: 0.95;
            white-space: nowrap; 
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }
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
                    <span class="brand-tagline">Staff Console</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="staff-dashboard.php" class="nav-item">
                            <span class="nav-icon"><i class="fa-solid fa-table-columns"></i></span>
                            <span class="nav-label">Dashboard</span>
                        </a>
                    </li>

                    <li class="nav-group" data-expandable>
                        <button class="nav-item nav-toggle" type="button" aria-expanded="false" data-target="student-menu">
                            <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
                            <span class="nav-label">Student</span>
                            <span class="nav-chevron"></span>
                        </button>
                        <div id="student-menu" class="nav-submenu" hidden>
                            <a href="tutorial-management.php" class="nav-subitem">Tutorial Management</a>
                            <a href="staff-attendance.php" class="nav-subitem">Attendance</a>
                            <a href="staff-assignments.php" class="nav-subitem">Assignment</a>
                        </div>
                    </li>

                    <li>
                        <a href="staff-timetable.php" class="nav-item is-active">
                            <span class="nav-icon"><i class="fa-solid fa-calendar-week"></i></span>
                            <span class="nav-label">My Timetable</span>
                        </a>
                    </li>

                    <li>
                        <a href="resource-booking.php" class="nav-item">
                            <span class="nav-icon"><i class="fa-solid fa-building"></i></span>
                            <span class="nav-label">Resource Booking</span>
                        </a>
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
                            <span class="user-name"><?php echo htmlspecialchars($staffName); ?></span>
                            <span class="user-role">Staff</span>
                        </div>
                        <div class="profile-pic">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    </div>
                </div>
            </header>

            <section class="timetable-card">
                <div style="margin-bottom: 20px;">
                    <h1 class="welcome-title">My Teaching Schedule</h1>
                    <p style="color: var(--text-sub); font-size: 14px; margin-top: 5px;">
                        Personal timetable for: <strong style="color: var(--purple-base);"><?php echo htmlspecialchars($userID); ?></strong>
                    </p>
                </div>

                <!-- Generate Report Button -->
                <div style="margin-bottom: 15px; display: flex; justify-content: flex-end;">
                    <a href="staff-timetable-report.php" target="_blank" 
                       style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: #8056ff; color: white; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: 0.2s; box-shadow: 0 2px 8px rgba(128,86,255,0.3);" 
                       onmouseover="this.style.background='#6b46c1'" onmouseout="this.style.background='#8056ff'">
                        <i class="fa-solid fa-file-pdf"></i> Generate Report
                    </a>
                </div>

                <!-- ====================================================
                     REQUEST CLASS REPLACEMENT SECTION
                     ==================================================== -->
                <div class="replacement-section">
                    <div class="replacement-header" onclick="toggleReplacementForm()">
                        <h3><i class="fa-solid fa-arrow-right-arrow-left"></i> Request Class Replacement</h3>
                        <i class="fa-solid fa-chevron-down" id="replacement-chevron"></i>
                    </div>
                    <div id="replacement-form-container" style="display: none;">
                        <p style="color: #666; font-size: 13px; margin-bottom: 15px;">Select the date of the class you'll miss, then choose when to hold the replacement.</p>
                        
                        <div id="replacement-msg" style="display:none; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px; font-size: 13px;"></div>
                        
                        <div class="replacement-form-grid">
                            <div class="rform-group">
                                <label>Original Class Date</label>
                                <input type="date" id="rr-originalDate">
                                <small id="rr-day-label" style="color:#666; margin-top:2px;"></small>
                            </div>
                            <div class="rform-group">
                                <label>Course & Slot <span id="rr-class-status" style="font-size:11px; color:#666;"></span></label>
                                <select id="rr-scheduleID" disabled>
                                    <option value="">Pick original date first...</option>
                                </select>
                            </div>
                            <div class="rform-group">
                                <label>Reason for Replacement</label>
                                <select id="rr-reason">
                                    <option value="">Select reason...</option>
                                    <option value="Medical Leave">Medical Leave</option>
                                    <option value="Conference">Conference</option>
                                    <option value="Emergency">Emergency</option>
                                    <option value="Official Duty">Official Duty</option>
                                    <option value="Personal Leave">Personal Leave</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="rform-group">
                                <label>Replacement Date</label>
                                <input type="date" id="rr-newDate">
                            </div>
                            <div class="rform-group">
                                <label>Replacement Time</label>
                                <div style="display: flex; gap: 8px;">
                                    <input type="time" id="rr-startTime" style="flex:1;">
                                    <span style="align-self: center; color: #666;">to</span>
                                    <input type="time" id="rr-endTime" style="flex:1;">
                                </div>
                            </div>
                            <div class="rform-group" style="grid-column: 1 / -1;">
                                <label>Available Venue (Resource) <span id="venue-status" style="font-size: 11px; color: #666;"></span></label>
                                <select id="rr-facilityID">
                                    <option value="">Select replacement date & time first...</option>
                                </select>
                            </div>
                        </div>
                        <div id="rr-conflict-warning" style="display:none; background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; padding:12px 15px; border-radius:8px; margin-top:12px; font-size:13px; line-height:1.6;"></div>
                        <button id="btn-submit-rr" class="btn-submit-replacement" onclick="submitReplacementRequest()">
                            <i class="fa-solid fa-paper-plane"></i> Submit Request
                        </button>
                    </div>
                </div>

                <!-- My Replacement Requests History -->
                <div class="replacement-section" style="margin-top: 15px;">
                    <div class="replacement-header" onclick="toggleRequestHistory()">
                        <h3><i class="fa-solid fa-clock-rotate-left"></i> My Replacement Requests</h3>
                        <span id="pending-badge" class="pending-count-badge" style="display:none;">0</span>
                        <i class="fa-solid fa-chevron-down" id="history-chevron"></i>
                    </div>
                    <div id="request-history-container" style="display: none;">
                        <div id="request-history-list">
                            <p style="text-align:center; color:#666; padding: 20px;">Loading requests...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Term and Week Selector -->
                <div id="term-info-section" style="background: #f8f9fa; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e0e0e0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                        <div>
                            <h3 id="term-title" style="margin: 0 0 5px 0; font-size: 16px; color: #333;">
                                <i class="fa-solid fa-calendar-alt"></i> Loading term info...
                            </h3>
                            <p id="term-dates" style="margin: 0; font-size: 13px; color: #666;">
                                Please wait...
                            </p>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <label for="week-selector" style="font-size: 14px; font-weight: 500; color: #555;">Week:</label>
                            <select id="week-selector" style="padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; min-width: 250px; cursor: pointer;">
                                <option value="">Loading weeks...</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="timetable-wrapper">
                    <table class="timetable" id="dynamic-timetable">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Day</th>
                                <?php for ($h = $startHour; $h < $endHour; $h++): ?>
                                    <th colspan="2">
                                        <?php echo sprintf("%02d:00 - %02d:00", $h, $h+1); ?>
                                    </th>
                                <?php endfor; ?>
                            </tr>
                        </thead>

                        <tbody id="timetable-body">
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>

<script>
    // Term and Week Management
    const staffID = '<?php echo $userID; ?>';
    let termData = null;
    let currentWeek = 1;

    // Load term and weeks on page load
    async function loadTermAndWeeks() {
        try {
            const response = await fetch(`../api/academicTerm.php?action=getActiveTermForStaff`);
            const result = await response.json();
            
            if (result.success && result.data) {
                termData = result.data;
                displayTermInfo();
                populateWeekSelector();
                selectCurrentWeek();
            } else {
                document.getElementById('term-title').textContent = 'No Active Term';
                document.getElementById('term-dates').textContent = result.message || 'No active semester found';
            }
        } catch (error) {
            console.error('Error loading term data:', error);
            document.getElementById('term-title').textContent = 'Error Loading Term';
            document.getElementById('term-dates').textContent = 'Please refresh the page';
        }
    }

    // Display term information
    function displayTermInfo() {
        const termTitle = `${termData.academicYear} Semester ${termData.semester}`;
        const startDate = new Date(termData.startDate).toLocaleDateString('en-MY', { 
            weekday: 'long', year: 'numeric', month: 'short', day: '2-digit' 
        });
        const endDate = new Date(termData.endDate).toLocaleDateString('en-MY', { 
            weekday: 'long', year: 'numeric', month: 'short', day: '2-digit' 
        });
        
        document.getElementById('term-title').innerHTML = `<i class="fa-solid fa-calendar-alt"></i> ${termTitle}`;
        document.getElementById('term-dates').textContent = `( ${startDate} – ${endDate} )`;
    }

    // Populate week selector dropdown
    function populateWeekSelector() {
        const selector = document.getElementById('week-selector');
        selector.innerHTML = '';
        
        termData.weeks.forEach(week => {
            const option = document.createElement('option');
            option.value = week.weekNumber;
            option.textContent = week.label;
            option.dataset.startDate = week.startDate;
            option.dataset.endDate = week.endDate;
            selector.appendChild(option);
        });
        
        // Add event listener for week changes
        selector.addEventListener('change', function() {
            currentWeek = parseInt(this.value);
            loadTimetable(currentWeek);
        });
    }

    // Load and render timetable dynamically
    async function loadTimetable(weekNumber = null) {
        const tbody = document.getElementById('timetable-body');
        tbody.innerHTML = '<tr><td colspan="21" style="text-align: center; padding: 40px;"><i class="fa-solid fa-spinner fa-spin" style="font-size: 24px;"></i><p style="margin-top: 10px;">Loading schedule...</p></td></tr>';
        
        try {
            // Get week dates from the selector for replacement logic
            const selector = document.getElementById('week-selector');
            const selectedOpt = selector.options[selector.selectedIndex];
            const weekStartDate = selectedOpt?.dataset?.startDate || '';
            const weekEndDate = selectedOpt?.dataset?.endDate || '';
            
            let url = `../api/staffTimetable.php?action=getSchedule&staffID=${staffID}`;
            if (weekNumber) url += `&weekNumber=${weekNumber}`;
            if (weekStartDate) url += `&weekStartDate=${weekStartDate}`;
            if (weekEndDate) url += `&weekEndDate=${weekEndDate}`;
            
            const response = await fetch(url);
            const result = await response.json();
            
            if (result.success && result.data) {
                renderTimetable(result.data, weekStartDate);
            } else {
                tbody.innerHTML = '<tr><td colspan="21" style="text-align: center; padding: 40px; color: #e74c3c;">' + (result.message || 'No schedule found') + '</td></tr>';
            }
        } catch (error) {
            console.error('Error loading timetable:', error);
            tbody.innerHTML = '<tr><td colspan="21" style="text-align: center; padding: 40px; color: #e74c3c;">Error loading timetable. Please refresh.</td></tr>';
        }
    }

    // Render timetable from schedule data
    function renderTimetable(scheduleData, weekStartDate) {
        const startHour = 8, endHour = 22, totalSlots = (endHour - startHour) * 2;
        const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        // Compute date for each day of the week
        const dayDates = {};
        if (weekStartDate) {
            const base = new Date(weekStartDate + 'T00:00:00');
            daysOfWeek.forEach((day, idx) => {
                const d = new Date(base);
                d.setDate(base.getDate() + idx);
                dayDates[day] = d.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit' });
            });
        }
        const scheduleMatrix = {};
        
        scheduleData.forEach(row => {
            const day = row.day;
            if (!scheduleMatrix[day]) scheduleMatrix[day] = {};
            
            const [sH, sM] = row.startTime.split(':');
            const startIndex = ((parseInt(sH) - startHour) * 2) + (sM === '30' ? 1 : 0);
            const [eH, eM] = row.endTime.split(':');
            const endIndex = ((parseInt(eH) - startHour) * 2) + (eM === '30' ? 1 : 0);
            const durationSlots = endIndex - startIndex;
            
            if (durationSlots <= 0 || startIndex < 0 || endIndex > totalSlots) return;
            
            let isBlocked = false;
            for (let i = 0; i < durationSlots; i++) {
                if (scheduleMatrix[day][startIndex + i]) { isBlocked = true; break; }
            }
            
            if (!isBlocked) {
                scheduleMatrix[day][startIndex] = { info: row, colspan: durationSlots };
                for (let i = 1; i < durationSlots; i++) scheduleMatrix[day][startIndex + i] = 'occupied';
            }
        });
        
        const tbody = document.getElementById('timetable-body');
        tbody.innerHTML = '';
        
        daysOfWeek.forEach(day => {
            const tr = document.createElement('tr');
            const dayTd = document.createElement('td');
            dayTd.className = 'day-column';
            if (dayDates[day]) {
                dayTd.innerHTML = `${day.substring(0, 3)}<br><span style="font-size:10px; font-weight:400; opacity:0.7;">${dayDates[day]}</span>`;
            } else {
                dayTd.textContent = day.substring(0, 3);
            }
            tr.appendChild(dayTd);
            
            for (let i = 0; i < totalSlots; i++) {
                if (scheduleMatrix[day] && scheduleMatrix[day][i]) {
                    const slot = scheduleMatrix[day][i];
                    if (slot === 'occupied') continue;
                    
                    const td = document.createElement('td');
                    td.className = 'time-slot';
                    td.colSpan = slot.colspan;
                    const typeClass = 'is-' + (slot.info.classType || 'Lecture');
                    const suffix = (slot.info.classType === 'Lecture') ? '(L)' : (slot.info.classType === 'Practical' || slot.info.classType === 'Lab' ? '(P)' : '(T)');
                    const replBadge = slot.info.isReplacement ? '<span style="background:#f59e0b;color:#fff;font-size:7px;padding:1px 4px;border-radius:3px;margin-left:3px;">REPLACEMENT</span>' : '';
                    
                    const timeStr = slot.info.startTime.substring(0,5) + ' - ' + slot.info.endTime.substring(0,5);
                    const tooltipText = `${slot.info.courseID} ${suffix}\n${slot.info.courseName}\n${timeStr}\nVenue: ${slot.info.facilityName || slot.info.facilityID}\nGroup: ${slot.info.tutGroup} - ${slot.info.programID}${slot.info.isReplacement ? '\n(Replacement Class)' : ''}`;
                    
                    td.innerHTML = `<div class="class-container ${typeClass}" title="${tooltipText.replace(/"/g, '&quot;')}" ${slot.info.isReplacement ? 'style="border:2px dashed #f59e0b;"' : ''}>
                        <span class="subject-code">${slot.info.courseID} ${suffix}${replBadge}</span>
                        <span class="subject-name">${slot.info.courseName}</span>
                        <span class="subject-loc"><i class="fa-solid fa-location-dot"></i> ${slot.info.facilityID}</span>
                        <span class="subject-prof">${slot.info.tutGroup} - ${slot.info.programID}</span>
                    </div>`;
                    tr.appendChild(td);
                } else {
                    const td = document.createElement('td');
                    td.className = 'time-slot empty';
                    tr.appendChild(td);
                }
            }
            tbody.appendChild(tr);
        });
    }

    // Auto-select current week based on today's date
    function selectCurrentWeek() {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        for (let i = 0; i < termData.weeks.length; i++) {
            const week = termData.weeks[i];
            const weekStart = new Date(week.startDate);
            const weekEnd = new Date(week.endDate);
            weekStart.setHours(0, 0, 0, 0);
            weekEnd.setHours(23, 59, 59, 999);
            
            if (today >= weekStart && today <= weekEnd) {
                document.getElementById('week-selector').value = week.weekNumber;
                currentWeek = week.weekNumber;
                break;
            }
        }
        
        // Load timetable for current week
        loadTimetable(currentWeek);
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadTermAndWeeks();
        loadRequestHistory();
    });

    // ============================================================
    // REPLACEMENT REQUEST FUNCTIONS
    // ============================================================

    function toggleReplacementForm() {
        const container = document.getElementById('replacement-form-container');
        const chevron = document.getElementById('replacement-chevron');
        const isOpen = container.style.display !== 'none';
        container.style.display = isOpen ? 'none' : 'block';
        chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
    }

    function toggleRequestHistory() {
        const container = document.getElementById('request-history-container');
        const chevron = document.getElementById('history-chevron');
        const isOpen = container.style.display !== 'none';
        container.style.display = isOpen ? 'none' : 'block';
        chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
    }

    // Step 1: When staff picks the original date, load classes for that day
    document.getElementById('rr-originalDate')?.addEventListener('change', async function() {
        const dateVal = this.value;
        const select = document.getElementById('rr-scheduleID');
        const dayLabel = document.getElementById('rr-day-label');
        const classStatus = document.getElementById('rr-class-status');
        
        if (!dateVal) {
            select.innerHTML = '<option value="">Pick original date first...</option>';
            select.disabled = true;
            dayLabel.textContent = '';
            return;
        }

        const dayOfWeek = new Date(dateVal + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'long' });
        dayLabel.textContent = dayOfWeek;
        classStatus.textContent = '(loading...)';
        select.innerHTML = '<option value="">Loading...</option>';
        select.disabled = true;

        try {
            const res = await fetch(`../api/replacement_request.php?action=getStaffClasses&staffID=${staffID}&day=${dayOfWeek}`);
            const result = await res.json();
            select.innerHTML = '<option value="">Select a class...</option>';
            
            if (result.success && result.data.length > 0) {
                result.data.forEach(cls => {
                    const suffix = cls.classType === 'Lecture' ? '(L)' : (cls.classType === 'Practical' || cls.classType === 'Lab' ? '(P)' : '(T)');
                    const label = `${cls.courseID} ${suffix} - ${cls.startTime.substring(0,5)} to ${cls.endTime.substring(0,5)} @ ${cls.facilityID}`;
                    const opt = document.createElement('option');
                    opt.value = cls.scheduleID;
                    opt.textContent = label;
                    opt.dataset.startTime = cls.startTime;
                    opt.dataset.endTime = cls.endTime;
                    select.appendChild(opt);
                });
                select.disabled = false;
                classStatus.textContent = `(${result.data.length} class${result.data.length > 1 ? 'es' : ''} on ${dayOfWeek})`;
                classStatus.style.color = '#16a34a';
            } else {
                select.innerHTML = '<option value="">No classes on ' + dayOfWeek + '</option>';
                classStatus.textContent = '(no classes found)';
                classStatus.style.color = '#dc2626';
            }
        } catch (e) {
            console.error('Error loading classes:', e);
            select.innerHTML = '<option value="">Error loading classes</option>';
            classStatus.textContent = '';
        }
    });

    // Auto-fill time from selected class
    document.getElementById('rr-scheduleID')?.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (selected && selected.dataset.startTime) {
            document.getElementById('rr-startTime').value = selected.dataset.startTime.substring(0,5);
            document.getElementById('rr-endTime').value = selected.dataset.endTime.substring(0,5);
            onReplacementSlotChange();
        }
    });

    // When replacement date or time changes, check facility availability AND staff schedule conflict
    document.getElementById('rr-newDate')?.addEventListener('change', onReplacementSlotChange);
    document.getElementById('rr-startTime')?.addEventListener('change', onReplacementSlotChange);
    document.getElementById('rr-endTime')?.addEventListener('change', onReplacementSlotChange);

    // Combined check: facilities + staff own schedule conflict
    async function onReplacementSlotChange() {
        await Promise.all([checkFacilities(), checkStaffConflict()]);
    }

    // Check if staff has a schedule conflict on the replacement date/time
    let hasScheduleConflict = false;

    async function checkStaffConflict() {
        const date = document.getElementById('rr-newDate').value;
        const startTime = document.getElementById('rr-startTime').value;
        const endTime = document.getElementById('rr-endTime').value;
        const scheduleID = document.getElementById('rr-scheduleID').value;
        const conflictDiv = document.getElementById('rr-conflict-warning');
        const submitBtn = document.getElementById('btn-submit-rr');

        if (!date || !startTime || !endTime) {
            conflictDiv.style.display = 'none';
            hasScheduleConflict = false;
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            return;
        }

        try {
            let url = `../api/replacement_request.php?action=checkStaffConflict&staffID=${staffID}&date=${date}&startTime=${startTime}&endTime=${endTime}`;
            
            const res = await fetch(url);
            const result = await res.json();

            if (result.success && result.hasConflict) {
                hasScheduleConflict = true;
                let msg = '<i class="fa-solid fa-triangle-exclamation"></i> <strong>Schedule Conflict Detected!</strong><br>';
                result.conflicts.forEach(c => {
                    const suffix = c.classType === 'Lecture' ? '(L)' : (c.classType === 'Practical' || c.classType === 'Lab' ? '(P)' : '(T)');
                    if (c.type === 'regular') {
                        msg += `You have <strong>${c.courseID} ${suffix}</strong> — ${c.courseName} at ${c.startTime.substring(0,5)}-${c.endTime.substring(0,5)} on ${c.day}<br>`;
                    } else {
                        msg += `You have an approved replacement: <strong>${c.courseID} ${suffix}</strong> — ${c.courseName} at ${c.newTime} on ${c.newDate}<br>`;
                    }
                });
                msg += '<small>Please choose a different date or time to avoid clashing.</small>';
                conflictDiv.innerHTML = msg;
                conflictDiv.style.display = 'block';
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
            } else {
                hasScheduleConflict = false;
                conflictDiv.style.display = 'none';
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            }
        } catch (e) {
            console.error('Error checking staff conflict:', e);
            hasScheduleConflict = false;
            conflictDiv.style.display = 'none';
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
        }
    }

    async function checkFacilities() {
        const date = document.getElementById('rr-newDate').value;
        const startTime = document.getElementById('rr-startTime').value;
        const endTime = document.getElementById('rr-endTime').value;
        const select = document.getElementById('rr-facilityID');
        const status = document.getElementById('venue-status');

        if (!date || !startTime || !endTime) {
            select.innerHTML = '<option value="">Select replacement date & time first...</option>';
            status.textContent = '';
            return;
        }

        status.textContent = '(checking availability...)';
        try {
            const res = await fetch(`../api/replacement_request.php?action=getAvailableFacilities&date=${date}&startTime=${startTime}&endTime=${endTime}`);
            const result = await res.json();
            select.innerHTML = '<option value="">Select venue...</option>';
            if (result.success) {
                let availCount = 0;
                result.data.forEach(f => {
                    const opt = document.createElement('option');
                    opt.value = f.facilityID;
                    if (f.available) {
                        opt.textContent = `${f.facilityID} - ${f.facilityName} (${f.type}) ✓ Available`;
                        availCount++;
                    } else {
                        opt.textContent = `${f.facilityID} - ${f.facilityName} (${f.type}) ✗ Occupied`;
                        opt.disabled = true;
                        opt.style.color = '#999';
                    }
                    select.appendChild(opt);
                });
                status.textContent = `(${availCount} available)`;
                status.style.color = availCount > 0 ? '#16a34a' : '#dc2626';
            }
        } catch (e) {
            console.error('Error checking facilities:', e);
            status.textContent = '(error checking)';
        }
    }

    // Submit replacement request
    async function submitReplacementRequest() {
        if (hasScheduleConflict) {
            showReplacementMsg('Cannot submit: You have a schedule conflict at the selected replacement time. Please choose a different date or time.', 'error');
            return;
        }

        const originalDate = document.getElementById('rr-originalDate').value;
        const scheduleID = document.getElementById('rr-scheduleID').value;
        const reason = document.getElementById('rr-reason').value;
        const newDate = document.getElementById('rr-newDate').value;
        const startTime = document.getElementById('rr-startTime').value;
        const endTime = document.getElementById('rr-endTime').value;
        const facilityID = document.getElementById('rr-facilityID').value;

        if (!originalDate || !scheduleID || !reason || !newDate || !startTime || !endTime) {
            showReplacementMsg('Please fill in all required fields.', 'error');
            return;
        }

        if (originalDate === newDate) {
            showReplacementMsg('Replacement date must be different from the original date.', 'error');
            return;
        }

        if (endTime <= startTime) {
            showReplacementMsg('End time must be after start time. If you mean PM, please make sure to select PM (e.g. 13:00 for 1:00 PM).', 'error');
            return;
        }

        const newTime = `${startTime} - ${endTime}`;

        try {
            const res = await fetch('../api/replacement_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'create',
                    scheduleID: parseInt(scheduleID),
                    originalDate: originalDate,
                    newDate: newDate,
                    newTime: newTime,
                    facilityID: facilityID || null,
                    reason: reason
                })
            });
            const result = await res.json();
            if (result.success) {
                showReplacementMsg(result.message, 'success');
                // Reset form
                document.getElementById('rr-originalDate').value = '';
                document.getElementById('rr-day-label').textContent = '';
                document.getElementById('rr-scheduleID').innerHTML = '<option value="">Pick original date first...</option>';
                document.getElementById('rr-scheduleID').disabled = true;
                document.getElementById('rr-class-status').textContent = '';
                document.getElementById('rr-reason').value = '';
                document.getElementById('rr-newDate').value = '';
                document.getElementById('rr-startTime').value = '';
                document.getElementById('rr-endTime').value = '';
                document.getElementById('rr-facilityID').innerHTML = '<option value="">Select replacement date & time first...</option>';
                document.getElementById('venue-status').textContent = '';
                document.getElementById('rr-conflict-warning').style.display = 'none';
                hasScheduleConflict = false;
                document.getElementById('btn-submit-rr').disabled = false;
                document.getElementById('btn-submit-rr').style.opacity = '1';
                loadRequestHistory();
            } else {
                showReplacementMsg(result.message, 'error');
            }
        } catch (e) {
            showReplacementMsg('Failed to submit request. Please try again.', 'error');
        }
    }

    function showReplacementMsg(msg, type) {
        const div = document.getElementById('replacement-msg');
        div.style.display = 'block';
        div.style.background = type === 'success' ? '#dcfce7' : '#fee2e2';
        div.style.color = type === 'success' ? '#166534' : '#991b1b';
        div.style.border = `1px solid ${type === 'success' ? '#86efac' : '#fca5a5'}`;
        div.textContent = msg;
        setTimeout(() => { div.style.display = 'none'; }, 5000);
    }

    // Load request history
    async function loadRequestHistory() {
        try {
            const res = await fetch('../api/replacement_request.php?action=list');
            const result = await res.json();
            const container = document.getElementById('request-history-list');
            
            if (result.success && result.data.length > 0) {
                const pendingCount = result.data.filter(r => r.status === 'Pending').length;
                const badge = document.getElementById('pending-badge');
                if (pendingCount > 0) {
                    badge.textContent = pendingCount;
                    badge.style.display = 'inline-flex';
                } else {
                    badge.style.display = 'none';
                }

                let html = '<table class="request-history-table"><thead><tr><th>Course</th><th>Original Date</th><th>Replacement Date/Time</th><th>Reason</th><th>Status</th></tr></thead><tbody>';
                result.data.forEach(r => {
                    const statusClass = r.status === 'Approved' ? 'status-approved' : (r.status === 'Rejected' ? 'status-rejected' : 'status-pending');
                    html += `<tr>
                        <td><strong>${r.courseID}</strong><br><small>${r.courseName}</small></td>
                        <td>${r.originalDate || '-'}<br><small>${r.originalDay} ${r.originalStart?.substring(0,5)}-${r.originalEnd?.substring(0,5)}</small></td>
                        <td>${r.newDate}<br><small>${r.newTime}</small>${r.newVenue ? '<br><small>' + r.newVenue + '</small>' : ''}</td>
                        <td>${r.reason}</td>
                        <td><span class="rr-status ${statusClass}">${r.status}</span>${r.adminNotes ? '<br><small style="color:#666;">' + r.adminNotes + '</small>' : ''}</td>
                    </tr>`;
                });
                html += '</tbody></table>';
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p style="text-align:center; color:#666; padding: 20px;">No replacement requests yet.</p>';
            }
        } catch (e) {
            console.error('Error loading request history:', e);
        }
    }
</script>

<script src="../script.js"></script>

</body>
</html>