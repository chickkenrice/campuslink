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
    <link rel="icon" type="image/png" href="../favicon2.png">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Course Offerings - CAMPUSLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/course-enrollment.css">
</head>
<body>

<div class="app-bg">
    <div class="main-card">
        
        <?php $currentPage = 'offerings'; include __DIR__ . '/../includes/adminMaster.php'; ?>

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

            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h1 class="welcome-title">Course Offerings Management</h1>
                    <p style="color: var(--text-sub); font-size: 14px; margin-top: 5px;">
                        Create course offerings for upcoming terms and manage registration periods
                    </p>
                </div>
                <a href="course-registration-report.php" class="btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 8px; font-weight: 600; text-decoration: none; background: var(--purple-base); color: white; font-size: 13px;">
                    <i class="fa-solid fa-file-lines"></i> Generate Report
                </a>
            </div>

            <!-- Registration Period Section -->
            <div class="ce-admin-section">
                <div class="ce-section-title">
                    <i class="fa-solid fa-calendar-alt"></i> Registration Period
                </div>
                <div class="ce-reg-period-form">
                    <div class="ce-filter-group">
                        <label>Select Term</label>
                        <select id="regTermSelect" onchange="loadRegPeriod()">
                            <option value="">— Select a term —</option>
                        </select>
                    </div>
                    <div class="ce-filter-group">
                        <label>Registration Start</label>
                        <input type="date" id="regStart" class="ce-input">
                    </div>
                    <div class="ce-filter-group">
                        <label>Registration End</label>
                        <input type="date" id="regEnd" class="ce-input">
                    </div>
                    <button class="ce-btn ce-btn-primary" onclick="saveRegPeriod()">
                        <i class="fa-solid fa-save"></i> Save Period
                    </button>
                </div>
                <div id="regPeriodStatus" class="ce-reg-period-status"></div>
            </div>

            <!-- Offerings List Section -->
            <div class="ce-admin-section">
                <div class="ce-section-title">
                    <i class="fa-solid fa-list"></i> Current Offerings
                </div>
                <div class="ce-filter-bar" style="margin-bottom: 15px;">
                    <div class="ce-filter-group">
                        <label>Programme</label>
                        <select id="filterProgram" onchange="loadOfferings()">
                            <option value="">All Programmes</option>
                        </select>
                    </div>
                    <div class="ce-filter-group">
                        <label>Term</label>
                        <select id="filterTerm" onchange="loadOfferings()">
                            <option value="">All Terms</option>
                        </select>
                    </div>
                </div>

                <div id="offeringsTable" class="ce-loading">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <span>Loading offerings...</span>
                </div>
            </div>
            
            <?php include __DIR__ . '/../includes/footer.php'; ?>
        </main>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="ce-modal-overlay" style="display:none;">
    <div class="ce-modal">
        <div class="ce-modal-header">
            <h3><i class="fa-solid fa-edit"></i> Edit Offering</h3>
            <button class="ce-modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="ce-modal-body">
            <input type="hidden" id="editOfferingID">
            <div class="ce-form-group">
                <label>Course</label>
                <input type="text" id="editCourseLabel" class="ce-input" disabled>
            </div>
            <div class="ce-form-group">
                <label>Capacity</label>
                <input type="number" id="editCapacity" class="ce-input" min="1" max="500">
            </div>
            <div class="ce-form-group">
                <label>Status</label>
                <select id="editStatus" class="ce-input">
                    <option value="Open">Open</option>
                    <option value="Closed">Closed</option>
                    <option value="Full">Full</option>
                </select>
            </div>
        </div>
        <div class="ce-modal-footer">
            <button class="ce-btn ce-btn-secondary" onclick="closeModal()">Cancel</button>
            <button class="ce-btn ce-btn-primary" onclick="saveOffering()">Save Changes</button>
        </div>
    </div>
</div>

<script>
let allTerms = [];

document.addEventListener('DOMContentLoaded', () => {
    loadAllDropdowns();
});

// =============================================
// LOAD DROPDOWNS
// =============================================

async function loadAllDropdowns() {
    try {
        const [progRes, termRes] = await Promise.all([
            fetch('../api/courseRegistration.php?action=getPrograms'),
            fetch('../api/courseRegistration.php?action=getTerms')
        ]);
        const programs = await progRes.json();
        const terms = await termRes.json();

        if (programs.success) {
            const progOptions = programs.data.map(p => 
                `<option value="${p.programID}">${p.programID} - ${p.programName}</option>`
            ).join('');

            document.getElementById('filterProgram').innerHTML += progOptions;
        }

        if (terms.success) {
            allTerms = terms.data;
            const termOptions = terms.data.map(t => {
                const label = `${t.programID} Y${t.year}S${t.semester} (${t.academicYear}) — ${t.status}`;
                return `<option value="${t.termID}">${label}</option>`;
            }).join('');

            document.getElementById('regTermSelect').innerHTML += termOptions;
            document.getElementById('filterTerm').innerHTML += termOptions;
        }

        loadOfferings();
    } catch (err) {
        console.error('Error loading dropdowns:', err);
    }
}

// =============================================
// REGISTRATION PERIOD
// =============================================

function loadRegPeriod() {
    const termID = document.getElementById('regTermSelect').value;
    const statusDiv = document.getElementById('regPeriodStatus');

    if (!termID) {
        document.getElementById('regStart').value = '';
        document.getElementById('regEnd').value = '';
        statusDiv.innerHTML = '';
        return;
    }

    const term = allTerms.find(t => t.termID == termID);
    if (term) {
        document.getElementById('regStart').value = term.regStartDate || '';
        document.getElementById('regEnd').value = term.regEndDate || '';

        if (term.regStartDate && term.regEndDate) {
            const today = new Date().toISOString().split('T')[0];
            const isActive = today >= term.regStartDate && today <= term.regEndDate;
            statusDiv.innerHTML = `
                <span class="ce-badge ${isActive ? 'ce-status-registered' : 'ce-status-closed-badge'}">
                    ${isActive ? 'Registration Currently Active' : 'Registration Not Active'}
                </span>
                <span style="font-size: 12px; color: #666; margin-left: 10px;">
                    ${formatDate(term.regStartDate)} — ${formatDate(term.regEndDate)}
                </span>
            `;
        } else {
            statusDiv.innerHTML = `<span class="ce-badge ce-status-prereq">No registration dates set</span>`;
        }
    }
}

async function saveRegPeriod() {
    const termID = document.getElementById('regTermSelect').value;
    const regStartDate = document.getElementById('regStart').value;
    const regEndDate = document.getElementById('regEnd').value;

    if (!termID || !regStartDate || !regEndDate) {
        showToast('Please select a term and set both dates', 'error');
        return;
    }

    try {
        const res = await fetch('../api/courseRegistration.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'updateRegPeriod', termID, regStartDate, regEndDate })
        });
        const result = await res.json();

        if (result.success) {
            showToast(result.message, 'success');
            // Refresh term data
            const termRes = await fetch('../api/courseRegistration.php?action=getTerms');
            const terms = await termRes.json();
            if (terms.success) allTerms = terms.data;
            loadRegPeriod();
        } else {
            showToast(result.message, 'error');
        }
    } catch (err) {
        console.error('Error saving reg period:', err);
        showToast('Failed to save registration period', 'error');
    }
}

// =============================================
// LOAD OFFERINGS
// =============================================

async function loadOfferings() {
    const programID = document.getElementById('filterProgram').value;
    const termID = document.getElementById('filterTerm').value;

    if (!programID && !termID) {
        document.getElementById('offeringsTable').innerHTML = `
            <div class="ce-empty-state">
                <i class="fa-solid fa-filter"></i>
                <p>Select a programme or term to view offerings</p>
            </div>
        `;
        return;
    }

    let url = `../api/courseRegistration.php?action=getOfferings`;
    if (programID) url += `&programID=${programID}`;
    if (termID) url += `&termID=${termID}`;

    try {
        const res = await fetch(url);
        const result = await res.json();

        if (result.success) {
            renderOfferings(result.data);
        }
    } catch (err) {
        console.error('Error loading offerings:', err);
    }
}

function renderOfferings(offerings) {
    const container = document.getElementById('offeringsTable');

    if (offerings.length === 0) {
        container.innerHTML = `
            <div class="ce-empty-state">
                <i class="fa-solid fa-folder-open"></i>
                <p>No offerings found. Use "Generate Course Offerings" to create them.</p>
            </div>
        `;
        return;
    }

    let html = `
        <table class="ce-reg-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Course</th>
                    <th>Programme</th>
                    <th>Term</th>
                    <th>Type</th>
                    <th>Capacity</th>
                    <th>Enrolled</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;

    offerings.forEach(o => {
        const statusClass = o.status === 'Full' ? 'ce-status-full' : o.status === 'Closed' ? 'ce-status-closed-badge' : 'ce-status-available';
        const isElective = o.courseType && o.courseType !== 'Core' && o.courseType !== 'Main';

        html += `
            <tr>
                <td class="ce-center">${o.offeringID}</td>
                <td>
                    <strong>${o.courseID}</strong>
                    <div style="font-size: 11px; color: #888;">${o.courseName}</div>
                </td>
                <td><span class="ce-badge-info">${o.programID}</span></td>
                <td>Y${o.year}S${o.semester}<br><small style="color:#888;">${o.academicYear}</small></td>
                <td><span class="ce-course-type ${isElective ? 'ce-type-elective' : 'ce-type-core'}">${o.courseType || 'Core'}</span></td>
                <td class="ce-center">${o.capacity}</td>
                <td class="ce-center"><strong>${o.enrolledCount}</strong></td>
                <td><span class="ce-badge ${statusClass}">${o.status}</span></td>
                <td>
                    <button class="ce-btn ce-btn-sm ce-btn-edit" onclick="editOffering(${o.offeringID}, '${o.courseID} - ${o.courseName.replace(/'/g, "\\'")}', ${o.capacity}, '${o.status}')">
                        <i class="fa-solid fa-edit"></i>
                    </button>
                    <button class="ce-btn ce-btn-sm ce-btn-drop" onclick="deleteOffering(${o.offeringID}, '${o.courseID}')" ${o.enrolledCount > 0 ? 'disabled title="Has enrolled students"' : ''}>
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

// =============================================
// EDIT / DELETE OFFERINGS
// =============================================

function editOffering(offeringID, courseLabel, capacity, status) {
    document.getElementById('editOfferingID').value = offeringID;
    document.getElementById('editCourseLabel').value = courseLabel;
    document.getElementById('editCapacity').value = capacity;
    document.getElementById('editStatus').value = status;
    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

async function saveOffering() {
    const offeringID = document.getElementById('editOfferingID').value;
    const capacity = parseInt(document.getElementById('editCapacity').value);
    const status = document.getElementById('editStatus').value;

    try {
        const res = await fetch('../api/courseRegistration.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'updateOffering', offeringID, capacity, status })
        });
        const result = await res.json();

        if (result.success) {
            showToast(result.message, 'success');
            closeModal();
            loadOfferings();
        } else {
            showToast(result.message, 'error');
        }
    } catch (err) {
        console.error('Error saving offering:', err);
        showToast('Failed to save changes', 'error');
    }
}

async function deleteOffering(offeringID, courseID) {
    if (!confirm(`Delete offering for ${courseID}? This cannot be undone.`)) return;

    try {
        const res = await fetch('../api/courseRegistration.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'deleteOffering', offeringID })
        });
        const result = await res.json();

        if (result.success) {
            showToast(result.message, 'success');
            loadOfferings();
        } else {
            showToast(result.message, 'error');
        }
    } catch (err) {
        console.error('Error deleting offering:', err);
        showToast('Failed to delete offering', 'error');
    }
}

// =============================================
// UTILITIES
// =============================================

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const [y,mo,da] = dateStr.split('-');
    return `${da}-${mo}-${y}`;
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
</script>

</body>
</html>
