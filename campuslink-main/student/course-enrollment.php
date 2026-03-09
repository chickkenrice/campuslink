<?php
session_start();
require_once '../includes/config.php';

if (!validate_session() || strcasecmp($_SESSION['role'], 'student') !== 0) {
    header("Location: ../login.php");
    exit;
}

$db = get_db_connection();
$studentID = $_SESSION['user_id'];

// Get student info
$stmt = $db->prepare("SELECT s.studentName, s.tutGroup, s.programID, s.currentYear, s.currentSemester, s.studentImage, p.programName 
                       FROM student s 
                       JOIN program p ON s.programID = p.programID 
                       WHERE s.studentID = ?");
$stmt->bind_param("s", $studentID);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$studentName = $student['studentName'] ?? 'Student';
$studentImage = $student['studentImage'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Course Enrollment - CAMPUSLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/course-enrollment.css">
</head>
<body>

<div class="app-bg">
    <div class="main-card">
        
        <?php $currentPage = 'course-enrollment'; include __DIR__ . '/../includes/studentMaster.php'; ?>

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

            <!-- Page Header -->
            <section class="welcome-card" style="margin-bottom: 28px;">
                <div class="welcome-details">
                    <h1 class="welcome-title">Course Enrollment</h1>
                    <div class="welcome-meta">
                        <span class="welcome-badge"><?php echo htmlspecialchars($student['programID']); ?></span>
                        <span class="welcome-meta-text"><?php echo htmlspecialchars($student['programName']); ?></span>
                        <span class="welcome-meta-sep">·</span>
                        <span class="welcome-meta-sub"><i class="fa-solid fa-layer-group"></i> Year <?php echo $student['currentYear']; ?> Semester <?php echo $student['currentSemester']; ?></span>
                    </div>
                </div>
            </section>

            <!-- Registration Status Banner -->
            <div id="regStatusBanner" class="ce-status-banner ce-status-loading">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <span>Checking registration period...</span>
            </div>

            <!-- Tab Navigation -->
            <div class="ce-tabs">
                <button class="ce-tab active" data-tab="available">
                    <i class="fa-solid fa-book-open"></i> Available Courses
                </button>
                <button class="ce-tab" data-tab="registered">
                    <i class="fa-solid fa-check-circle"></i> My Registered Courses
                    <span id="registeredCount" class="ce-tab-badge">0</span>
                </button>
            </div>

            <!-- Available Courses Tab -->
            <div id="tab-available" class="ce-tab-content active">
                <!-- Summary Cards -->
                <div class="ce-summary-row">
                    <div class="ce-summary-card">
                        <div class="ce-summary-icon" style="background: #ede9fe;">
                            <i class="fa-solid fa-book" style="color: #7c3aed;"></i>
                        </div>
                        <div class="ce-summary-info">
                            <span class="ce-summary-value" id="totalCourses">0</span>
                            <span class="ce-summary-label">Total Courses</span>
                        </div>
                    </div>
                    <div class="ce-summary-card">
                        <div class="ce-summary-icon" style="background: #dbeafe;">
                            <i class="fa-solid fa-layer-group" style="color: #2563eb;"></i>
                        </div>
                        <div class="ce-summary-info">
                            <span class="ce-summary-value" id="coreCourses">0</span>
                            <span class="ce-summary-label">Core Courses</span>
                        </div>
                    </div>
                    <div class="ce-summary-card">
                        <div class="ce-summary-icon" style="background: #fef3c7;">
                            <i class="fa-solid fa-shuffle" style="color: #d97706;"></i>
                        </div>
                        <div class="ce-summary-info">
                            <span class="ce-summary-value" id="electiveCourses">0</span>
                            <span class="ce-summary-label">Elective Courses</span>
                        </div>
                    </div>
                    <div class="ce-summary-card">
                        <div class="ce-summary-icon" style="background: #d1fae5;">
                            <i class="fa-solid fa-graduation-cap" style="color: #059669;"></i>
                        </div>
                        <div class="ce-summary-info">
                            <span class="ce-summary-value" id="totalCredits">0</span>
                            <span class="ce-summary-label">Total Credits</span>
                        </div>
                    </div>
                </div>

                <!-- Course List -->
                <div id="courseList" class="ce-course-list">
                    <div class="ce-loading">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        <span>Loading courses...</span>
                    </div>
                </div>
            </div>

            <!-- Registered Courses Tab -->
            <div id="tab-registered" class="ce-tab-content">
                <div id="registeredList" class="ce-course-list">
                    <div class="ce-loading">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        <span>Loading registered courses...</span>
                    </div>
                </div>
                <div id="registeredSummary" class="ce-registered-summary" style="display:none;">
                    <div class="ce-summary-footer">
                        <span>Total Registered Credits:</span>
                        <strong id="regTotalCredits">0</strong>
                    </div>
                </div>
            </div>
            
            <?php include __DIR__ . '/../includes/footer.php'; ?>
        </main>
    </div>
</div>

<!-- Confirm Modal -->
<div id="confirmModal" class="ce-modal-overlay" style="display: none;">
    <div class="ce-modal">
        <div class="ce-modal-header">
            <h3><i class="fa-solid fa-circle-question"></i> <span id="confirmTitle">Confirm Action</span></h3>
        </div>
        <div class="ce-modal-body">
            <p id="confirmMessage" style="color: #555; line-height: 1.6;"></p>
        </div>
        <div class="ce-modal-footer">
            <button id="confirmCancel" class="ce-btn ce-btn-secondary">Cancel</button>
            <button id="confirmOk" class="ce-btn ce-btn-primary">OK</button>
        </div>
    </div>
</div>

<script>
const studentID = '<?php echo $studentID; ?>';
let currentTermID = null;
let registrationOpen = false;
let availableCourses = [];
let hasElectiveRegistered = false; // track if student already picked an elective

// =============================================
// INITIALIZATION
// =============================================

document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    checkRegistrationPeriod();
});

function initTabs() {
    document.querySelectorAll('.ce-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.ce-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.ce-tab-content').forEach(c => c.classList.remove('active'));
            tab.classList.add('active');
            const target = tab.getAttribute('data-tab');
            document.getElementById('tab-' + target).classList.add('active');
        });
    });
}

// =============================================
// CHECK REGISTRATION PERIOD
// =============================================

async function checkRegistrationPeriod() {
    try {
        const res = await fetch(`../api/courseRegistration.php?action=getRegistrationTerm&studentID=${studentID}`);
        const result = await res.json();
        const banner = document.getElementById('regStatusBanner');

        if (result.success) {
            currentTermID = result.data.termID;
            registrationOpen = true;
            const term = result.data;
            banner.className = 'ce-status-banner ce-status-open';
            banner.innerHTML = `
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <strong>Registration Open</strong> — 
                    Year ${term.year} Semester ${term.semester} (${term.academicYear})
                    <span class="ce-reg-period">
                        <i class="fa-regular fa-calendar"></i> 
                        ${formatDate(term.regStartDate)} — ${formatDate(term.regEndDate)}
                    </span>
                </div>
            `;
            // Auto-register core courses first, then load data
            await autoRegisterCoreCourses();
            loadAvailableCourses();
            loadRegisteredCourses();
        } else {
            registrationOpen = false;
            banner.className = 'ce-status-banner ce-status-closed';

            if (result.upcomingTerm) {
                const ut = result.upcomingTerm;
                currentTermID = ut.termID;
                let msg = `<strong>Registration Not Yet Open</strong> — Showing available courses for Year ${ut.year} Semester ${ut.semester} (${ut.academicYear}).`;
                if (ut.regStartDate) {
                    msg += `<br><small><i class="fa-regular fa-calendar"></i> Registration opens: ${formatDate(ut.regStartDate)} — ${formatDate(ut.regEndDate)}</small>`;
                }
                banner.innerHTML = `<i class="fa-solid fa-circle-info"></i><div>${msg}</div>`;
                // Load courses for preview (registration buttons will be disabled)
                loadAvailableCourses();
                loadRegisteredCourses();
            } else if (result.isFinalSemester) {
                banner.className = 'ce-status-banner ce-status-open';
                banner.innerHTML = `<i class="fa-solid fa-graduation-cap"></i><div><strong>Final Semester Completed</strong> — You have completed all semesters of your programme. No further course registration is required.</div>`;
                document.getElementById('courseList').innerHTML = `
                    <div class="ce-empty-state">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <p>Congratulations! You have completed your programme.</p>
                    </div>
                `;
            } else {
                banner.innerHTML = `<i class="fa-solid fa-circle-xmark"></i><div><strong>No Upcoming Term</strong> — ${result.message}</div>`;
                document.getElementById('courseList').innerHTML = `
                    <div class="ce-empty-state">
                        <i class="fa-solid fa-calendar-xmark"></i>
                        <p>No courses available for registration at this time.</p>
                    </div>
                `;
            }
        }
    } catch (err) {
        console.error('Error checking registration:', err);
        const banner = document.getElementById('regStatusBanner');
        banner.className = 'ce-status-banner ce-status-closed';
        banner.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i><span>Error checking registration period</span>`;
    }
}

// =============================================
// AUTO-REGISTER CORE COURSES
// =============================================

async function autoRegisterCoreCourses() {
    if (!currentTermID) return;
    try {
        const res = await fetch(`../api/courseRegistration.php?action=autoRegisterCore&studentID=${studentID}&termID=${currentTermID}`);
        const result = await res.json();
        if (result.success && result.registered > 0) {
            showToast(`Auto-registered for ${result.registered} core course(s)`, 'success');
        }
    } catch (err) {
        console.error('Error auto-registering core courses:', err);
    }
}

// =============================================
// LOAD AVAILABLE COURSES
// =============================================

async function loadAvailableCourses() {
    if (!currentTermID) return;

    try {
        const res = await fetch(`../api/courseRegistration.php?action=getAvailableCourses&termID=${currentTermID}&studentID=${studentID}`);
        const result = await res.json();

        if (result.success) {
            availableCourses = result.data;
            renderAvailableCourses(result.data);
            updateSummaryCards(result.data);
        } else {
            document.getElementById('courseList').innerHTML = `
                <div class="ce-empty-state">
                    <i class="fa-solid fa-info-circle"></i>
                    <p>${result.message}</p>
                </div>
            `;
        }
    } catch (err) {
        console.error('Error loading courses:', err);
    }
}

function renderAvailableCourses(courses) {
    const container = document.getElementById('courseList');

    if (courses.length === 0) {
        container.innerHTML = `
            <div class="ce-empty-state">
                <i class="fa-solid fa-folder-open"></i>
                <p>No course offerings available for this term yet.</p>
            </div>
        `;
        return;
    }

    // Group by type
    const core = courses.filter(c => !c.courseType || c.courseType === 'Core' || c.courseType === 'Main');
    const electives = courses.filter(c => c.courseType && c.courseType !== 'Core' && c.courseType !== 'Main');

    // Check if student already has an elective registered
    hasElectiveRegistered = electives.some(c => c.isRegistered == 1 && c.registrationStatus === 'Registered');

    let html = '';

    if (core.length > 0) {
        html += `<div class="ce-section-header"><i class="fa-solid fa-layer-group"></i> Core Courses</div>`;
        html += core.map(c => renderCourseCard(c)).join('');
    }

    if (electives.length > 0) {
        html += `<div class="ce-section-header ce-section-elective"><i class="fa-solid fa-shuffle"></i> Elective Courses <span style="font-size:13px; font-weight:400; color:#6b7280; margin-left:8px;">(Choose 1 of ${electives.length})</span></div>`;
        html += electives.map(c => renderCourseCard(c, true)).join('');
    }

    container.innerHTML = html;
}

function renderCourseCard(course, isElective = false) {
    const isRegistered = course.isRegistered == 1 && course.registrationStatus === 'Registered';
    const isFull = course.slotsRemaining <= 0 && !isRegistered;
    const fillPercent = course.capacity > 0 ? Math.round((course.enrolledCount / course.capacity) * 100) : 0;

    let statusClass = 'ce-status-available';
    let statusText = 'Available';
    let actionBtn = '';

    if (isRegistered) {
        statusClass = 'ce-status-registered';
        statusText = 'Registered';
        actionBtn = `<button class="ce-btn ce-btn-drop" onclick="dropCourse(${course.offeringID}, '${course.courseID}')" ${!registrationOpen ? 'disabled' : ''}>
            <i class="fa-solid fa-xmark"></i> Drop
        </button>`;
    } else if (isFull) {
        statusClass = 'ce-status-full';
        statusText = 'Full';
    } else if (isElective && hasElectiveRegistered) {
        // Another elective already selected — disable this one
        actionBtn = `<button class="ce-btn ce-btn-register" disabled title="You already selected an elective. Drop it first to choose this one.">
            <i class="fa-solid fa-lock"></i> Locked
        </button>`;
    } else {
        actionBtn = `<button class="ce-btn ce-btn-register" onclick="registerCourse(${course.offeringID}, '${course.courseID}')" ${!registrationOpen ? 'disabled' : ''}>
            <i class="fa-solid fa-plus"></i> Register
        </button>`;
    }

    const fillBarColor = fillPercent >= 90 ? '#ef4444' : fillPercent >= 70 ? '#f59e0b' : '#22c55e';

    return `
        <div class="ce-course-card ${isRegistered ? 'ce-card-registered' : ''} ${isFull ? 'ce-card-full' : ''} ${isElective ? 'ce-card-elective' : ''}">
            <div class="ce-card-header">
                <div class="ce-card-title">
                    <span class="ce-course-id">${course.courseID}</span>
                    <span class="ce-course-name">${course.courseName}</span>
                </div>
                <div class="ce-card-badges">
                    <span class="ce-badge-credit">${course.creditHours} Cr</span>
                    <span class="ce-badge ${statusClass}">${statusText}</span>
                </div>
            </div>
            <div class="ce-card-body">
                <div class="ce-capacity-info">
                    <div class="ce-capacity-bar">
                        <div class="ce-capacity-fill" style="width: ${fillPercent}%; background: ${fillBarColor};"></div>
                    </div>
                    <span class="ce-capacity-text">${course.enrolledCount} / ${course.capacity} enrolled</span>
                </div>
            </div>
            <div class="ce-card-footer">
                <span class="ce-course-type ${isElective ? 'ce-type-elective' : 'ce-type-core'}">
                    ${course.courseType || 'Core'}
                </span>
                ${actionBtn}
            </div>
        </div>
    `;
}

function updateSummaryCards(courses) {
    const core = courses.filter(c => !c.courseType || c.courseType === 'Core' || c.courseType === 'Main');
    const electives = courses.filter(c => c.courseType && c.courseType !== 'Core' && c.courseType !== 'Main');

    document.getElementById('totalCourses').textContent = courses.length;
    document.getElementById('coreCourses').textContent = core.length;
    document.getElementById('electiveCourses').textContent = electives.length;

    // Total credits = all core credits + only 1 elective's credits (student picks 1)
    const coreCr = core.reduce((sum, c) => sum + parseInt(c.creditHours), 0);
    let electiveCr = 0;
    if (electives.length > 0) {
        // Use the registered elective's credits if one is registered, otherwise use the first elective
        const registeredElective = electives.find(c => c.isRegistered == 1 && c.registrationStatus === 'Registered');
        electiveCr = parseInt((registeredElective || electives[0]).creditHours);
    }
    document.getElementById('totalCredits').textContent = coreCr + electiveCr;
}

// =============================================
// LOAD REGISTERED COURSES
// =============================================

async function loadRegisteredCourses() {
    try {
        const url = currentTermID 
            ? `../api/courseRegistration.php?action=getRegisteredCourses&studentID=${studentID}&termID=${currentTermID}`
            : `../api/courseRegistration.php?action=getRegisteredCourses&studentID=${studentID}`;
        const res = await fetch(url);
        const result = await res.json();

        if (result.success) {
            renderRegisteredCourses(result.data, result.totalCredits);
            document.getElementById('registeredCount').textContent = result.data.length;
        }
    } catch (err) {
        console.error('Error loading registered courses:', err);
    }
}

function renderRegisteredCourses(courses, totalCredits) {
    const container = document.getElementById('registeredList');
    const summary = document.getElementById('registeredSummary');

    if (courses.length === 0) {
        container.innerHTML = `
            <div class="ce-empty-state">
                <i class="fa-solid fa-clipboard"></i>
                <p>You haven't registered for any courses yet.</p>
                <small>Switch to the "Available Courses" tab to start registering.</small>
            </div>
        `;
        summary.style.display = 'none';
        return;
    }

    let html = `
        <table class="ce-reg-table">
            <thead>
                <tr>
                    <th>Course ID</th>
                    <th>Course Name</th>
                    <th>Type</th>
                    <th>Credits</th>
                    <th>Enrolled</th>
                    <th>Registered On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
    `;

    courses.forEach(c => {
        const isElective = c.courseType && c.courseType !== 'Core' && c.courseType !== 'Main';
        html += `
            <tr>
                <td><strong>${c.courseID}</strong></td>
                <td>${c.courseName}</td>
                <td><span class="ce-course-type ${isElective ? 'ce-type-elective' : 'ce-type-core'}">${c.courseType || 'Core'}</span></td>
                <td class="ce-center">${c.creditHours}</td>
                <td class="ce-center">${c.enrolledCount} / ${c.capacity}</td>
                <td>${formatDateTime(c.registeredAt)}</td>
                <td>
                    <button class="ce-btn ce-btn-drop ce-btn-sm" onclick="dropCourse(${c.offeringID}, '${c.courseID}')" ${!registrationOpen ? 'disabled' : ''}>
                        <i class="fa-solid fa-xmark"></i> Drop
                    </button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table>';
    container.innerHTML = html;

    document.getElementById('regTotalCredits').textContent = totalCredits + ' Credit Hours';
    summary.style.display = 'block';
}

// =============================================
// REGISTER / DROP ACTIONS
// =============================================

async function registerCourse(offeringID, courseID) {
    if (!registrationOpen) {
        showToast('Registration period is not open', 'error');
        return;
    }

    const confirmed = await showConfirm(
        `Are you sure you want to register for ${courseID}?`,
        'Register Course'
    );
    if (!confirmed) return;

    try {
        const res = await fetch('../api/courseRegistration.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'register', studentID, offeringID })
        });
        const result = await res.json();

        if (result.success) {
            showToast(result.message, 'success');
            loadAvailableCourses();
            loadRegisteredCourses();
        } else {
            showToast(result.message, 'error');
        }
    } catch (err) {
        console.error('Error registering:', err);
        showToast('Failed to register. Please try again.', 'error');
    }
}

async function dropCourse(offeringID, courseID) {
    if (!registrationOpen) {
        showToast('Registration period is not open', 'error');
        return;
    }

    // Check if dropping a core course would bring core credits below 6
    const courseBeingDropped = availableCourses.find(c => c.offeringID == offeringID || c.courseID === courseID);
    if (courseBeingDropped) {
        const isCoreDropped = courseBeingDropped.courseType === 'Core' || courseBeingDropped.courseType === 'Main';
        if (isCoreDropped) {
            // Calculate remaining core credits after dropping this course
            const registeredCore = availableCourses.filter(c => 
                (c.isRegistered == 1 && c.registrationStatus === 'Registered') &&
                (c.courseType === 'Core' || c.courseType === 'Main') &&
                c.courseID !== courseID
            );
            const remainingCoreCredits = registeredCore.reduce((sum, c) => sum + parseInt(c.creditHours || 0), 0);
            if (remainingCoreCredits < 6) {
                showToast(`Cannot drop ${courseID}: You must keep at least 6 credit hours of core courses. Remaining would be ${remainingCoreCredits} credit hours.`, 'error');
                return;
            }
        }
    }

    const confirmed = await showConfirm(
        `Drop ${courseID}? You can re-register while the registration period is open.`,
        'Drop Course'
    );
    if (!confirmed) return;

    try {
        const res = await fetch('../api/courseRegistration.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'drop', studentID, offeringID })
        });
        const result = await res.json();

        if (result.success) {
            showToast(result.message, 'success');
            loadAvailableCourses();
            loadRegisteredCourses();
        } else {
            showToast(result.message, 'error');
        }
    } catch (err) {
        console.error('Error dropping:', err);
        showToast('Failed to drop course. Please try again.', 'error');
    }
}

// =============================================
// UTILITIES
// =============================================

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-MY', { day: 'numeric', month: 'short', year: 'numeric' });
}

function formatDateTime(dtStr) {
    if (!dtStr) return '—';
    const d = new Date(dtStr);
    return d.toLocaleDateString('en-MY', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function showToast(message, type = 'info') {
    const existing = document.querySelector('.ce-toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `ce-toast ce-toast-${type}`;
    
    const icons = { success: 'fa-check-circle', error: 'fa-times-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
    toast.innerHTML = `<i class="fa-solid ${icons[type] || icons.info}"></i> <span>${message}</span>`;
    document.body.appendChild(toast);
    
    requestAnimationFrame(() => toast.classList.add('show'));
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function showConfirm(message, title = 'Confirm Action') {
    return new Promise((resolve) => {
        const modal = document.getElementById('confirmModal');
        const titleEl = document.getElementById('confirmTitle');
        const messageEl = document.getElementById('confirmMessage');
        const okBtn = document.getElementById('confirmOk');
        const cancelBtn = document.getElementById('confirmCancel');
        
        titleEl.textContent = title;
        messageEl.textContent = message;
        modal.style.display = 'flex';
        
        const handleOk = () => {
            modal.style.display = 'none';
            okBtn.removeEventListener('click', handleOk);
            cancelBtn.removeEventListener('click', handleCancel);
            resolve(true);
        };
        
        const handleCancel = () => {
            modal.style.display = 'none';
            okBtn.removeEventListener('click', handleOk);
            cancelBtn.removeEventListener('click', handleCancel);
            resolve(false);
        };
        
        okBtn.addEventListener('click', handleOk);
        cancelBtn.addEventListener('click', handleCancel);
        
        // Close on overlay click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) handleCancel();
        });
    });
}

// Nav toggle
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
</script>

</body>
</html>
