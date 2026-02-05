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
$endHour = 18;
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
                            <a href="manage-students.php" class="nav-subitem">Manage Student</a>
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
            const url = `../api/staffTimetable.php?action=getSchedule&staffID=${staffID}${weekNumber ? '&weekNumber=' + weekNumber : ''}`;
            const response = await fetch(url);
            const result = await response.json();
            
            if (result.success && result.data) {
                renderTimetable(result.data);
            } else {
                tbody.innerHTML = '<tr><td colspan="21" style="text-align: center; padding: 40px; color: #e74c3c;">' + (result.message || 'No schedule found') + '</td></tr>';
            }
        } catch (error) {
            console.error('Error loading timetable:', error);
            tbody.innerHTML = '<tr><td colspan="21" style="text-align: center; padding: 40px; color: #e74c3c;">Error loading timetable. Please refresh.</td></tr>';
        }
    }

    // Render timetable from schedule data
    function renderTimetable(scheduleData) {
        const startHour = 8, endHour = 18, totalSlots = (endHour - startHour) * 2;
        const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
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
            dayTd.textContent = day.substring(0, 3);
            tr.appendChild(dayTd);
            
            for (let i = 0; i < totalSlots; i++) {
                if (scheduleMatrix[day] && scheduleMatrix[day][i]) {
                    const slot = scheduleMatrix[day][i];
                    if (slot === 'occupied') continue;
                    
                    const td = document.createElement('td');
                    td.className = 'time-slot';
                    td.colSpan = slot.colspan;
                    const typeClass = 'is-' + (slot.info.classType || 'Lecture');
                    const suffix = (slot.info.classType === 'Lecture') ? '(L)' : (slot.info.classType === 'Practical' ? '(P)' : '(T)');
                    
                    td.innerHTML = `<div class="class-container ${typeClass}">
                        <span class="subject-code">${slot.info.courseID} ${suffix}</span>
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
    });
</script>

<script src="../script.js"></script>

</body>
</html>