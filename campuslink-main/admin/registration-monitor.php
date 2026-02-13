<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

prevent_back_button_cache();
$db = get_db_connection();
$adminName = $_SESSION['user_name'] ?? 'Admin';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration Monitor - CAMPUSLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/course-enrollment.css">
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
                        <a href="manage-timetable.php" class="nav-item">
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
                        <a href="registration-monitor.php" class="nav-item is-active">
                            <span class="nav-icon"><i class="fa-solid fa-chart-bar"></i></span>
                            <span class="nav-label">Registration Monitor</span>
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
                            <span class="user-name"><?php echo htmlspecialchars($adminName); ?></span>
                            <span class="user-role">Admin</span>
                        </div>
                        <div class="profile-pic">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Header -->
            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h1 class="welcome-title">Registration Monitor</h1>
                    <p style="color: var(--text-sub); font-size: 14px; margin-top: 5px;">
                        Monitor student enrollment statistics across course offerings
                    </p>
                </div>
                <a href="course-registration-report.php" class="btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 8px; font-weight: 600; text-decoration: none; background: var(--purple-base); color: white; font-size: 13px;">
                    <i class="fa-solid fa-file-lines"></i> Generate Report
                </a>
            </div>

            <!-- Filters -->
            <div class="ce-filter-bar">
                <div class="ce-filter-group">
                    <label><i class="fa-solid fa-graduation-cap"></i> Programme</label>
                    <select id="filterProgram" onchange="loadStats()">
                        <option value="">All Programmes</option>
                    </select>
                </div>
                <div class="ce-filter-group">
                    <label><i class="fa-solid fa-calendar"></i> Academic Term</label>
                    <select id="filterTerm" onchange="loadStats()">
                        <option value="">All Terms</option>
                    </select>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="ce-summary-row">
                <div class="ce-summary-card">
                    <div class="ce-summary-icon" style="background: #ede9fe;">
                        <i class="fa-solid fa-book" style="color: #7c3aed;"></i>
                    </div>
                    <div class="ce-summary-info">
                        <span class="ce-summary-value" id="statOfferings">0</span>
                        <span class="ce-summary-label">Course Offerings</span>
                    </div>
                </div>
                <div class="ce-summary-card">
                    <div class="ce-summary-icon" style="background: #dbeafe;">
                        <i class="fa-solid fa-user-check" style="color: #2563eb;"></i>
                    </div>
                    <div class="ce-summary-info">
                        <span class="ce-summary-value" id="statEnrolled">0</span>
                        <span class="ce-summary-label">Total Enrolled</span>
                    </div>
                </div>
                <div class="ce-summary-card">
                    <div class="ce-summary-icon" style="background: #fef3c7;">
                        <i class="fa-solid fa-chart-pie" style="color: #d97706;"></i>
                    </div>
                    <div class="ce-summary-info">
                        <span class="ce-summary-value" id="statAvgFill">0%</span>
                        <span class="ce-summary-label">Avg Fill Rate</span>
                    </div>
                </div>
                <div class="ce-summary-card">
                    <div class="ce-summary-icon" style="background: #fee2e2;">
                        <i class="fa-solid fa-circle-exclamation" style="color: #dc2626;"></i>
                    </div>
                    <div class="ce-summary-info">
                        <span class="ce-summary-value" id="statFull">0</span>
                        <span class="ce-summary-label">Full Courses</span>
                    </div>
                </div>
            </div>

            <!-- Enrollment Table -->
            <div class="ce-table-container">
                <div id="statsTable" class="ce-loading">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <span>Loading enrollment data...</span>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadFilters();
});

async function loadFilters() {
    try {
        const [progRes, termRes] = await Promise.all([
            fetch('../api/courseRegistration.php?action=getPrograms'),
            fetch('../api/courseRegistration.php?action=getTerms')
        ]);
        const programs = await progRes.json();
        const terms = await termRes.json();

        const progSelect = document.getElementById('filterProgram');
        if (programs.success) {
            programs.data.forEach(p => {
                progSelect.innerHTML += `<option value="${p.programID}">${p.programID} - ${p.programName}</option>`;
            });
        }

        const termSelect = document.getElementById('filterTerm');
        if (terms.success) {
            terms.data.forEach(t => {
                const label = `${t.programID} Y${t.year}S${t.semester} (${t.academicYear}) — ${t.status}`;
                termSelect.innerHTML += `<option value="${t.termID}">${label}</option>`;
            });
        }

        loadStats();
    } catch (err) {
        console.error('Error loading filters:', err);
    }
}

async function loadStats() {
    const programID = document.getElementById('filterProgram').value;
    const termID = document.getElementById('filterTerm').value;

    let url = `../api/courseRegistration.php?action=getEnrollmentStats`;
    if (programID) url += `&programID=${programID}`;
    if (termID) url += `&termID=${termID}`;

    try {
        const res = await fetch(url);
        const result = await res.json();

        if (result.success) {
            renderStatsTable(result.data);
            updateStatsSummary(result.data);
        }
    } catch (err) {
        console.error('Error loading stats:', err);
    }
}

function renderStatsTable(stats) {
    const container = document.getElementById('statsTable');

    if (stats.length === 0) {
        container.innerHTML = `
            <div class="ce-empty-state">
                <i class="fa-solid fa-chart-bar"></i>
                <p>No enrollment data found for the selected filters.</p>
            </div>
        `;
        return;
    }

    let html = `
        <table class="ce-reg-table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Programme</th>
                    <th>Term</th>
                    <th>Type</th>
                    <th>Credits</th>
                    <th>Enrolled</th>
                    <th>Capacity</th>
                    <th>Fill Rate</th>
                    <th>Dropped</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
    `;

    stats.forEach(s => {
        const fillColor = s.fillRate >= 90 ? '#ef4444' : s.fillRate >= 70 ? '#f59e0b' : '#22c55e';
        const statusClass = s.status === 'Full' ? 'ce-status-full' : s.status === 'Closed' ? 'ce-status-closed-badge' : 'ce-status-available';
        const isElective = s.courseType && s.courseType !== 'Core' && s.courseType !== 'Main';

        html += `
            <tr>
                <td>
                    <strong>${s.courseID}</strong>
                    <div style="font-size: 11px; color: #888;">${s.courseName}</div>
                </td>
                <td><span class="ce-badge-info">${s.programID}</span></td>
                <td>Y${s.year}S${s.semester}<br><small style="color:#888;">${s.academicYear}</small></td>
                <td><span class="ce-course-type ${isElective ? 'ce-type-elective' : 'ce-type-core'}">${s.courseType || 'Core'}</span></td>
                <td class="ce-center">${s.creditHours}</td>
                <td class="ce-center"><strong>${s.enrolledCount}</strong></td>
                <td class="ce-center">${s.capacity}</td>
                <td>
                    <div class="ce-capacity-bar" style="width: 80px; display: inline-flex;">
                        <div class="ce-capacity-fill" style="width: ${s.fillRate}%; background: ${fillColor};"></div>
                    </div>
                    <span style="font-size: 11px; margin-left: 5px; font-weight: 600; color: ${fillColor};">${s.fillRate}%</span>
                </td>
                <td class="ce-center">${s.droppedCount}</td>
                <td><span class="ce-badge ${statusClass}">${s.status}</span></td>
            </tr>
        `;
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

function updateStatsSummary(stats) {
    document.getElementById('statOfferings').textContent = stats.length;
    
    const totalEnrolled = stats.reduce((sum, s) => sum + parseInt(s.enrolledCount), 0);
    document.getElementById('statEnrolled').textContent = totalEnrolled;

    const avgFill = stats.length > 0 
        ? Math.round(stats.reduce((sum, s) => sum + s.fillRate, 0) / stats.length) 
        : 0;
    document.getElementById('statAvgFill').textContent = avgFill + '%';

    const fullCount = stats.filter(s => s.status === 'Full').length;
    document.getElementById('statFull').textContent = fullCount;
}
</script>

</body>
</html>
