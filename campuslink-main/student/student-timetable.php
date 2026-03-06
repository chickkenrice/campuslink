<?php
session_start();

// 1. CONFIG & SECURITY
require_once(__DIR__ . '/../includes/config.php'); 

if (!validate_session() || strcasecmp($_SESSION['role'], 'student') !== 0) {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['user_id'];
$db = get_db_connection();

// 2. GET STUDENT INFO
$stmt = $db->prepare("SELECT studentName, tutGroup, programID, studentImage FROM student WHERE studentID = ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

$studentName = $student['studentName'] ?? 'Student';
$myGroup = $student['tutGroup'] ?? 'Unassigned';
$myProgram = $student['programID'] ?? '';
$studentImage = $student['studentImage'] ?? null;

// 3. FETCH SCHEDULE DATA
$sql = "SELECT * FROM class_schedule cs
        JOIN course c ON cs.courseID = c.courseID
        JOIN facility f ON cs.facilityID = f.facilityID
        JOIN staff s ON cs.staffID = s.staffID
        WHERE cs.tutGroup = ? 
        AND cs.programID = ?"; 

$stmt = $db->prepare($sql);
$stmt->bind_param("ss", $myGroup, $myProgram);
$stmt->execute();
$result = $stmt->get_result();

// =============================================================
// 4. BUILD 30-MINUTE SCHEDULE MATRIX
// =============================================================
$startHour = 8;
$endHour = 22;
$totalSlots = ($endHour - $startHour) * 2;
$scheduleMatrix = [];

while ($row = $result->fetch_assoc()) {
    $day = $row['day'];
    
    // Parse Start Time
    list($sH, $sM) = explode(':', $row['startTime']);
    $startIndex = (($sH - $startHour) * 2) + ($sM == '30' ? 1 : 0);

    // Parse End Time
    list($eH, $eM) = explode(':', $row['endTime']);
    $endIndex = (($eH - $startHour) * 2) + ($eM == '30' ? 1 : 0);

    $durationSlots = $endIndex - $startIndex;

    if ($durationSlots <= 0 || $startIndex < 0 || $endIndex > $totalSlots) continue;

    $isBlocked = false;
    for ($i = 0; $i < $durationSlots; $i++) {
        if (isset($scheduleMatrix[$day][$startIndex + $i])) {
            $isBlocked = true;
            break;
        }
    }

    if (!$isBlocked) {
        $scheduleMatrix[$day][$startIndex] = [
            'info' => $row,
            'colspan' => $durationSlots
        ];
        
        for ($i = 1; $i < $durationSlots; $i++) {
            $scheduleMatrix[$day][$startIndex + $i] = 'occupied';
        }
    }
}

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
        /* Extra styles for Course Name inside block */
        .subject-name {
            font-size: 9px;
            font-weight: 500;
            display: block;
            margin-bottom: 3px;
            line-height: 1.1;
            opacity: 0.95;
            white-space: nowrap; 
            overflow: hidden;
            text-overflow: ellipsis; /* Adds ... if text is too long */
            max-width: 100%;
        }
    </style>
</head>
<body>

<div class="app-bg">
    <div class="main-card">
        
        <?php $currentPage = 'timetable'; include __DIR__ . '/../includes/studentMaster.php'; ?>

        <main class="dashboard">
            <header class="dashboard-topbar">
                <div class="topbar-right">
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

            <section class="welcome-card" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 28px;">
                <div class="welcome-details">
                    <h1 class="welcome-title">My Timetable</h1>
                    <div class="welcome-meta">
                        <span class="welcome-badge"><?php echo htmlspecialchars($myProgram); ?></span>
                        <span class="welcome-meta-sub"><i class="fa-solid fa-users"></i> <?php echo htmlspecialchars($myGroup); ?></span>
                    </div>
                </div>
                <a href="student-timetable-report.php" target="_blank" style="
                    display: inline-flex; align-items: center; gap: 8px;
                    padding: 12px 22px;
                    background: rgba(255,255,255,0.2);
                    color: white;
                    border-radius: 14px;
                    text-decoration: none;
                    font-weight: 600;
                    font-size: 14px;
                    border: 1px solid rgba(255,255,255,0.3);
                    position: relative; z-index: 1;
                    transition: all 0.2s ease;
                " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    <i class="fa-solid fa-file-pdf"></i> Generate Report
                </a>
            </section>

            <section class="timetable-card">
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
                    
                    <!-- Credit Hours Info -->
                    <div id="credit-info" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e0e0e0;">
                        <span style="font-size: 13px; color: #666;">
                            <i class="fa-solid fa-book"></i> Total Credit Hours: <strong id="total-credits">-</strong> | 
                            Semester Type: <strong id="sem-type">-</strong>
                        </span>
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
                            <tr>
                                <td colspan="21" style="text-align: center; padding: 40px;">
                                    <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; color: #666;"></i>
                                    <p style="margin-top: 10px; color: #666;">Loading timetable...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>

<script>
    // Navigation toggle functionality
    document.querySelectorAll('.nav-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const targetPanel = document.getElementById(targetId);
            const isExpanded = btn.getAttribute('aria-expanded') === 'true';
            
            if (targetPanel) {
                targetPanel.hidden = isExpanded;
                btn.setAttribute('aria-expanded', !isExpanded);
                btn.parentElement.classList.toggle('is-open', !isExpanded);
            }
        });
    });

    // Term and Week Management
    const studentID = '<?php echo $userID; ?>';
    let termData = null;
    let currentWeek = 1;

    // Load term and weeks on page load
    async function loadTermAndWeeks() {
        try {
            const response = await fetch(`../api/academicTerm.php?action=getActiveTermWithWeeks&studentID=${studentID}`);
            const result = await response.json();
            
            if (result.success && result.data) {
                termData = result.data;
                displayTermInfo();
                populateWeekSelector();
                
                // Auto-select current week based on today's date
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
        
        // Use actual semester dates based on weeks generated
        // For short semester (7 weeks), use Week 7's end date instead of full term end date
        const actualStartDate = termData.weeks[0].startDate;
        const actualEndDate = termData.weeks[termData.weeks.length - 1].endDate;
        
        const startDate = new Date(actualStartDate).toLocaleDateString('en-MY', { 
            weekday: 'long', year: 'numeric', month: 'short', day: '2-digit' 
        });
        const endDate = new Date(actualEndDate).toLocaleDateString('en-MY', { 
            weekday: 'long', year: 'numeric', month: 'short', day: '2-digit' 
        });
        
        document.getElementById('term-title').innerHTML = `<i class="fa-solid fa-calendar-alt"></i> ${termTitle}`;
        document.getElementById('term-dates').textContent = `( ${startDate} – ${endDate} )`;
        
        // Display credit hours and semester type
        document.getElementById('total-credits').textContent = termData.totalCreditHours;
        const semType = termData.isShortSemester ? 'Short Semester (7 weeks)' : 'Long Semester (14 weeks)';
        document.getElementById('sem-type').textContent = semType;
        document.getElementById('sem-type').style.color = termData.isShortSemester ? '#e67e22' : '#27ae60';
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
            console.log('Selected week:', currentWeek);
            loadTimetable(currentWeek); // Reload timetable for selected week
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

            let url = `../api/timetable.php?action=getSchedule&studentID=${studentID}`;
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
                    const tooltipText = `${slot.info.courseID} ${suffix}\n${slot.info.courseName}\n${timeStr}\nVenue: ${slot.info.facilityName || slot.info.facilityID}\nLecturer: ${slot.info.staffName || ''}${slot.info.isReplacement ? '\n(Replacement Class)' : ''}`;

                    td.innerHTML = `<div class="class-container ${typeClass}" title="${tooltipText.replace(/"/g, '&quot;')}" ${slot.info.isReplacement ? 'style="border:2px dashed #f59e0b;"' : ''}>
                        <span class="subject-code">${slot.info.courseID} ${suffix}${replBadge}</span>
                        <span class="subject-name">${slot.info.courseName}</span>
                        <span class="subject-loc"><i class="fa-solid fa-location-dot"></i> ${slot.info.facilityID}</span>
                        <span class="subject-prof">${slot.info.staffName}</span>
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

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadTermAndWeeks();
    });
</script>

</body>
</html>