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
        
        <?php $currentPage = 'registration'; include __DIR__ . '/../includes/adminMaster.php'; ?>

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
            <section class="welcome-card" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 28px;">
                <div class="welcome-details">
                    <h1 class="welcome-title">Registration Monitor</h1>
                    <div class="welcome-meta">
                        <span class="welcome-meta-sub"><i class="fa-solid fa-chart-bar"></i> Monitor student enrollment statistics across course offerings</span>
                    </div>
                </div>
                <a href="course-registration-report.php" target="_blank" style="
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
                    <i class="fa-solid fa-file-lines"></i> Generate Report
                </a>
            </section>

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
                <!-- Filters + Search + Sort — all inside the container -->
                <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; margin-bottom: 16px; justify-content: flex-start; padding-left: 15px;">
                    <div style="display: flex; flex-direction: column; gap: 4px; width: 200px;">
                        <label style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fa-solid fa-graduation-cap" style="margin-right: 4px;"></i>Programme
                        </label>
                        <select id="filterProgram" onchange="loadStats()" style="
                            padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 10px;
                            font-size: 13px; font-family: 'Inter', sans-serif; background: #f8fafc;
                            cursor: pointer; outline: none; color: #334155; width: 100%;
                        ">
                            <option value="">All Programmes</option>
                        </select>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 4px; width: 200px;">
                        <label style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fa-solid fa-calendar" style="margin-right: 4px;"></i>Academic Term
                        </label>
                        <select id="filterTerm" onchange="loadStats()" style="
                            padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 10px;
                            font-size: 13px; font-family: 'Inter', sans-serif; background: #f8fafc;
                            cursor: pointer; outline: none; color: #334155; width: 100%;
                        ">
                            <option value="">All Terms</option>
                        </select>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 4px; width: 200px;">
                        <label style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fa-solid fa-search" style="margin-right: 4px;"></i>Search
                        </label>
                        <div style="position: relative;">
                            <i class="fa-solid fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 12px;"></i>
                            <input type="text" id="searchInput" placeholder="Course ID or name..." oninput="applySearchAndSort()" style="
                                width: 100%; padding: 9px 12px 9px 34px; border: 1px solid #e2e8f0; border-radius: 10px;
                                font-size: 13px; font-family: 'Inter', sans-serif; outline: none; background: #f8fafc;
                                transition: border-color 0.2s, box-shadow 0.2s; box-sizing: border-box;
                            " onfocus="this.style.borderColor='#7c3aed'; this.style.boxShadow='0 0 0 3px rgba(124,58,237,0.1)'"
                               onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 4px;">
                        <label style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">
                            Sort by
                        </label>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <select id="sortField" onchange="applySearchAndSort()" style="
                                padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 10px;
                                font-size: 13px; font-family: 'Inter', sans-serif; background: #f8fafc;
                                cursor: pointer; outline: none; color: #334155;
                            ">
                                <option value="courseID">Course ID</option>
                                <option value="courseName">Course Name</option>
                                <option value="term">Term</option>
                                <option value="type">Type</option>
                                <option value="credits">Credits</option>
                                <option value="enrolled">Enrolled</option>
                                <option value="capacity">Capacity</option>
                                <option value="fillRate">Fill Rate</option>
                                <option value="dropped">Dropped</option>
                                <option value="status">Status</option>
                            </select>
                            <button id="sortDirBtn" onclick="toggleSortDir()" title="Toggle sort direction" style="
                                padding: 9px 10px; border: 1px solid #e2e8f0; border-radius: 10px;
                                background: #f8fafc; cursor: pointer; font-size: 14px; color: #64748b;
                                display: flex; align-items: center; transition: background 0.2s;
                            " onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#f8fafc'">
                                <i class="fa-solid fa-arrow-up-short-wide"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div id="statsTable" class="ce-loading">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <span>Loading enrollment data...</span>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
let allStats = [];
let sortField = 'courseID';
let sortAsc = true;

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
                const label = `${t.programID} Y${t.year}S${t.semester} (${t.academicYear})`;
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
            allStats = result.data;
            applySearchAndSort();
            updateStatsSummary(result.data);
        }
    } catch (err) {
        console.error('Error loading stats:', err);
    }
}

function applySearchAndSort() {
    const query = document.getElementById('searchInput').value.toLowerCase().trim();
    sortField = document.getElementById('sortField').value;

    let filtered = allStats;
    if (query) {
        filtered = allStats.filter(s =>
            s.courseID.toLowerCase().includes(query) ||
            s.courseName.toLowerCase().includes(query)
        );
    }

    filtered.sort((a, b) => {
        let valA, valB;
        switch (sortField) {
            case 'courseID':    valA = a.courseID; valB = b.courseID; break;
            case 'courseName': valA = a.courseName.toLowerCase(); valB = b.courseName.toLowerCase(); break;
            case 'term':       valA = `${a.year}${a.semester}`; valB = `${b.year}${b.semester}`; break;
            case 'type':       valA = a.courseType || 'Core'; valB = b.courseType || 'Core'; break;
            case 'credits':    valA = parseInt(a.creditHours); valB = parseInt(b.creditHours); break;
            case 'enrolled':   valA = parseInt(a.enrolledCount); valB = parseInt(b.enrolledCount); break;
            case 'capacity':   valA = parseInt(a.capacity); valB = parseInt(b.capacity); break;
            case 'fillRate':   valA = a.fillRate; valB = b.fillRate; break;
            case 'dropped':    valA = parseInt(a.droppedCount); valB = parseInt(b.droppedCount); break;
            case 'status':     valA = a.status; valB = b.status; break;
            default:           valA = a.courseID; valB = b.courseID;
        }
        if (typeof valA === 'string') {
            return sortAsc ? valA.localeCompare(valB) : valB.localeCompare(valA);
        }
        return sortAsc ? valA - valB : valB - valA;
    });

    renderStatsTable(filtered);
    updateStatsSummary(filtered);
}

function toggleSortDir() {
    sortAsc = !sortAsc;
    const icon = document.querySelector('#sortDirBtn i');
    icon.className = sortAsc ? 'fa-solid fa-arrow-up-short-wide' : 'fa-solid fa-arrow-down-wide-short';
    applySearchAndSort();
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
