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
    <title>Manage Academic Terms - CAMPUSLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/course-enrollment.css">
    <style>
        .term-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .term-form-grid .full-width {
            grid-column: 1 / -1;
        }
        .term-form-grid .form-group {
            min-width: 0;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .form-group label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-group input,
        .form-group select {
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            outline: none;
            background: #f8fafc;
            color: #334155;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
            width: 100%;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }
        .term-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 12px;
            background: #fff;
            transition: box-shadow 0.2s, border-color 0.2s;
        }
        .term-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border-color: #c4b5fd;
        }
        .term-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .term-card-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
        }
        .term-card-subtitle {
            font-size: 13px;
            color: #64748b;
            margin-top: 2px;
        }
        .term-card-body {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 10px;
        }
        .term-detail {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .term-detail-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
        }
        .term-detail-value {
            font-size: 13px;
            color: #334155;
            font-weight: 500;
        }
        .term-actions {
            display: flex;
            gap: 6px;
        }
        .term-actions button {
            padding: 6px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            color: #64748b;
            cursor: pointer;
            font-size: 12px;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
        }
        .term-actions button:hover {
            background: #ede9fe;
            border-color: #c4b5fd;
            color: #7c3aed;
        }
        .term-actions button.btn-delete:hover {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #dc2626;
        }
        .status-badge {
            display: inline-flex;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .status-active {
            background: #dcfce7;
            color: #15803d;
        }
        .status-upcoming {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .status-completed {
            background: #f1f5f9;
            color: #64748b;
        }
        .term-modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.4);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .term-modal-overlay.show {
            display: flex;
        }
        .term-modal {
            background: #fff;
            border-radius: 18px;
            padding: 28px;
            width: 640px;
            max-width: 95vw;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .term-modal h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #f1f5f9;
        }
        .btn-cancel {
            padding: 10px 20px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
            color: #64748b;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-cancel:hover {
            background: #f8fafc;
        }
        .btn-save {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            background: #7c3aed;
            color: #fff;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-save:hover {
            background: #6d28d9;
        }
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
            margin-bottom: 16px;
            padding-left: 15px;
        }
        .filter-bar .form-group {
            width: 200px;
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2000;
        }
        .toast {
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            margin-bottom: 8px;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .toast-success { background: #dcfce7; color: #15803d; }
        .toast-error { background: #fee2e2; color: #dc2626; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
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
                        <a href="registration-monitor.php" class="nav-item">
                            <span class="nav-icon"><i class="fa-solid fa-chart-bar"></i></span>
                            <span class="nav-label">Registration Monitor</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage-terms.php" class="nav-item is-active">
                            <span class="nav-icon"><i class="fa-solid fa-calendar-plus"></i></span>
                            <span class="nav-label">Academic Terms</span>
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
                    <h1 class="welcome-title">Academic Terms</h1>
                    <div class="welcome-meta">
                        <span class="welcome-meta-sub"><i class="fa-solid fa-calendar-plus"></i> Create and manage academic terms for course registration</span>
                    </div>
                </div>
                <button onclick="openCreateModal()" style="
                    display: inline-flex; align-items: center; gap: 8px;
                    padding: 12px 22px;
                    background: rgba(255,255,255,0.2);
                    color: white;
                    border-radius: 14px;
                    text-decoration: none;
                    font-weight: 600;
                    font-size: 14px;
                    font-family: 'Inter', sans-serif;
                    border: 1px solid rgba(255,255,255,0.3);
                    position: relative; z-index: 1;
                    cursor: pointer;
                    transition: all 0.2s ease;
                " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    <i class="fa-solid fa-plus"></i> New Term
                </button>
            </section>

            <!-- Summary Cards -->
            <div class="ce-summary-row">
                <div class="ce-summary-card">
                    <div class="ce-summary-icon" style="background: #ede9fe;">
                        <i class="fa-solid fa-calendar" style="color: #7c3aed;"></i>
                    </div>
                    <div class="ce-summary-info">
                        <span class="ce-summary-value" id="statTotal">0</span>
                        <span class="ce-summary-label">Total Terms</span>
                    </div>
                </div>
                <div class="ce-summary-card">
                    <div class="ce-summary-icon" style="background: #dcfce7;">
                        <i class="fa-solid fa-circle-check" style="color: #15803d;"></i>
                    </div>
                    <div class="ce-summary-info">
                        <span class="ce-summary-value" id="statActive">0</span>
                        <span class="ce-summary-label">Active</span>
                    </div>
                </div>
                <div class="ce-summary-card">
                    <div class="ce-summary-icon" style="background: #dbeafe;">
                        <i class="fa-solid fa-clock" style="color: #1d4ed8;"></i>
                    </div>
                    <div class="ce-summary-info">
                        <span class="ce-summary-value" id="statUpcoming">0</span>
                        <span class="ce-summary-label">Upcoming</span>
                    </div>
                </div>
                <div class="ce-summary-card">
                    <div class="ce-summary-icon" style="background: #f1f5f9;">
                        <i class="fa-solid fa-flag-checkered" style="color: #64748b;"></i>
                    </div>
                    <div class="ce-summary-info">
                        <span class="ce-summary-value" id="statCompleted">0</span>
                        <span class="ce-summary-label">Completed</span>
                    </div>
                </div>
            </div>

            <!-- Terms List -->
            <div class="ce-table-container">
                <div class="filter-bar">
                    <div class="form-group">
                        <label><i class="fa-solid fa-graduation-cap" style="margin-right: 4px;"></i>Programme</label>
                        <select id="filterProgram" onchange="renderTerms()" style="
                            padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 10px;
                            font-size: 13px; font-family: 'Inter', sans-serif; background: #f8fafc;
                            cursor: pointer; outline: none; color: #334155; width: 100%;
                        ">
                            <option value="">All Programmes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-filter" style="margin-right: 4px;"></i>Status</label>
                        <select id="filterStatus" onchange="renderTerms()" style="
                            padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 10px;
                            font-size: 13px; font-family: 'Inter', sans-serif; background: #f8fafc;
                            cursor: pointer; outline: none; color: #334155; width: 100%;
                        ">
                            <option value="">All Statuses</option>
                            <option value="Active">Active</option>
                            <option value="Upcoming">Upcoming</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div id="termsList" style="padding: 0 15px 15px;">
                    <div class="ce-loading">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        <span>Loading academic terms...</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="termModal" class="term-modal-overlay">
    <div class="term-modal">
        <h2><i class="fa-solid fa-calendar-plus" style="color: #7c3aed;"></i> <span id="modalTitle">New Academic Term</span></h2>
        <input type="hidden" id="editTermID" value="">
        <div class="term-form-grid">
            <div class="form-group">
                <label>Programme *</label>
                <select id="formProgram" onchange="onTermSelectionChange()">
                    <option value="">Select Programme</option>
                </select>
            </div>
            <div class="form-group">
                <label>Academic Year *</label>
                <input type="text" id="formAcademicYear" placeholder="e.g. 2025/2026">
            </div>
            <div class="form-group">
                <label>Study Year *</label>
                <select id="formYear" onchange="onTermSelectionChange()">
                    <option value="">Select Year</option>
                    <option value="1">Year 1</option>
                    <option value="2">Year 2</option>
                    <option value="3">Year 3</option>
                    <option value="4">Year 4</option>
                </select>
            </div>
            <div class="form-group">
                <label>Semester *</label>
                <select id="formSemester" onchange="onTermSelectionChange()">
                    <option value="">Select Semester</option>
                    <option value="1">Semester 1</option>
                    <option value="2">Semester 2</option>
                    <option value="3">Semester 3</option>
                </select>
            </div>
            <div class="form-group">
                <label>Start Date *</label>
                <input type="date" id="formStartDate" oninput="autoCalculate()">
            </div>
            <div class="form-group">
                <label>End Date <span style="color:#94a3b8;font-weight:400;font-size:11px;">(auto-calculated)</span></label>
                <input type="date" id="formEndDate" readonly style="background:#f1f5f9;color:#64748b;cursor:not-allowed;">
            </div>
            <div class="form-group">
                <label>Registration Start Date</label>
                <input type="date" id="formRegStart">
            </div>
            <div class="form-group">
                <label>Registration End Date</label>
                <input type="date" id="formRegEnd">
            </div>
            <div class="form-group">
                <label>Total Weeks <span style="color:#94a3b8;font-weight:400;font-size:11px;">(auto-calculated)</span></label>
                <div style="display:flex;align-items:center;gap:10px;">
                    <input type="number" id="formWeeks" readonly style="background:#f1f5f9;color:#64748b;cursor:not-allowed;width:100%;">
                    <span id="semesterTypeBadge" style="display:none;white-space:nowrap;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;"></span>
                </div>
            </div>
            <div class="form-group">
                <label>Status *</label>
                <select id="formStatus">
                    <option value="Upcoming">Upcoming</option>
                    <option value="Active">Active</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>
            <div class="full-width" id="semesterInfoBox" style="display:none;padding:10px 14px;border-radius:10px;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;font-size:13px;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            <button class="btn-save" onclick="saveTerm()"><i class="fa-solid fa-check"></i> Save</button>
        </div>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div id="deleteModal" class="term-modal-overlay">
    <div class="term-modal" style="width: 400px;">
        <h2><i class="fa-solid fa-triangle-exclamation" style="color: #dc2626;"></i> Delete Term</h2>
        <p style="color: #555; line-height: 1.6; font-size: 14px;" id="deleteMessage">Are you sure you want to delete this academic term?</p>
        <input type="hidden" id="deleteTermID" value="">
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn-save" style="background: #dc2626;" onclick="confirmDelete()"><i class="fa-solid fa-trash"></i> Delete</button>
        </div>
    </div>
</div>

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<script>
let allTerms = [];
let programs = [];

document.addEventListener('DOMContentLoaded', () => {
    loadData();
});

async function loadData() {
    try {
        const [termsRes, progsRes] = await Promise.all([
            fetch('../api/academicTerm.php?action=getAllTerms'),
            fetch('../api/academicTerm.php?action=getPrograms')
        ]);
        const termsData = await termsRes.json();
        const progsData = await progsRes.json();

        if (progsData.success) {
            programs = progsData.data;
            const filterProg = document.getElementById('filterProgram');
            const formProg = document.getElementById('formProgram');
            // Clear existing options (keep first)
            filterProg.innerHTML = '<option value="">All Programmes</option>';
            formProg.innerHTML = '<option value="">Select Programme</option>';
            programs.forEach(p => {
                filterProg.innerHTML += `<option value="${p.programID}">${p.programID} — ${p.programName}</option>`;
                formProg.innerHTML += `<option value="${p.programID}">${p.programID} — ${p.programName}</option>`;
            });
        }

        if (termsData.success) {
            allTerms = termsData.data;
            renderTerms();
            updateSummary();
        }
    } catch (err) {
        console.error('Error loading data:', err);
    }
}

function renderTerms() {
    const container = document.getElementById('termsList');
    const filterProg = document.getElementById('filterProgram').value;
    const filterStatus = document.getElementById('filterStatus').value;

    let filtered = allTerms;
    if (filterProg) filtered = filtered.filter(t => t.programID === filterProg);
    if (filterStatus) filtered = filtered.filter(t => t.status === filterStatus);

    if (filtered.length === 0) {
        container.innerHTML = `
            <div class="ce-empty-state">
                <i class="fa-solid fa-calendar-xmark"></i>
                <p>No academic terms found.</p>
                <small>Click "New Term" to create one.</small>
            </div>
        `;
        return;
    }

    container.innerHTML = filtered.map(t => {
        const statusClass = t.status === 'Active' ? 'status-active' : t.status === 'Upcoming' ? 'status-upcoming' : 'status-completed';
        const regInfo = t.regStartDate && t.regEndDate
            ? `${formatDate(t.regStartDate)} — ${formatDate(t.regEndDate)}`
            : '<span style="color: #cbd5e1;">Auto-set on first access</span>';

        return `
            <div class="term-card">
                <div class="term-card-header">
                    <div>
                        <div class="term-card-title">${t.programID} — Year ${t.year} Semester ${t.semester}</div>
                        <div class="term-card-subtitle">${t.programName} · ${t.academicYear}</div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="status-badge ${statusClass}">${t.status}</span>
                        <div class="term-actions">
                            <button onclick="openEditModal(${t.termID})" title="Edit"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn-delete" onclick="openDeleteModal(${t.termID}, '${t.programID} Y${t.year}S${t.semester}')" title="Delete"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                </div>
                <div class="term-card-body">
                    <div class="term-detail">
                        <span class="term-detail-label">Term Dates</span>
                        <span class="term-detail-value">${formatDate(t.startDate)} — ${formatDate(t.endDate)}</span>
                    </div>
                    <div class="term-detail">
                        <span class="term-detail-label">Registration Window</span>
                        <span class="term-detail-value">${regInfo}</span>
                    </div>
                    <div class="term-detail">
                        <span class="term-detail-label">Duration</span>
                        <span class="term-detail-value">${t.weeksTotal} weeks</span>
                    </div>
                    <div class="term-detail">
                        <span class="term-detail-label">Term ID</span>
                        <span class="term-detail-value">#${t.termID}</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function updateSummary() {
    document.getElementById('statTotal').textContent = allTerms.length;
    document.getElementById('statActive').textContent = allTerms.filter(t => t.status === 'Active').length;
    document.getElementById('statUpcoming').textContent = allTerms.filter(t => t.status === 'Upcoming').length;
    document.getElementById('statCompleted').textContent = allTerms.filter(t => t.status === 'Completed').length;
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

// ===== MODAL FUNCTIONS =====

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'New Academic Term';
    document.getElementById('editTermID').value = '';
    document.getElementById('formProgram').value = '';
    document.getElementById('formAcademicYear').value = '';
    document.getElementById('formYear').value = '';
    document.getElementById('formSemester').value = '';
    document.getElementById('formStartDate').value = '';
    document.getElementById('formEndDate').value = '';
    document.getElementById('formRegStart').value = '';
    document.getElementById('formRegEnd').value = '';
    document.getElementById('formWeeks').value = '';
    document.getElementById('formStatus').value = 'Upcoming';
    hideSemesterBadge();
    clearRegDateHint();
    minStartDate = null;
    document.getElementById('formRegStart').disabled = false;
    document.getElementById('formRegEnd').disabled   = false;
    const errEl = document.getElementById('startDateError');
    if (errEl) errEl.style.display = 'none';
    document.getElementById('termModal').classList.add('show');
}

function openEditModal(termID) {
    const term = allTerms.find(t => t.termID == termID);
    if (!term) return;

    document.getElementById('modalTitle').textContent = 'Edit Academic Term';
    document.getElementById('editTermID').value = term.termID;
    document.getElementById('formProgram').value = term.programID;
    document.getElementById('formAcademicYear').value = term.academicYear;
    document.getElementById('formYear').value = term.year;
    document.getElementById('formSemester').value = term.semester;
    document.getElementById('formStartDate').value = term.startDate;
    document.getElementById('formEndDate').value = term.endDate;
    document.getElementById('formRegStart').value = term.regStartDate || '';
    document.getElementById('formRegEnd').value = term.regEndDate || '';
    document.getElementById('formWeeks').value = term.weeksTotal;
    document.getElementById('formStatus').value = term.status;
    // Show badge from existing data
    showSemesterBadge(parseInt(term.weeksTotal));
    document.getElementById('termModal').classList.add('show');
    // Re-fetch credit hours for badge accuracy
    fetchCreditHoursAndCalculate(false);
}

function closeModal() {
    document.getElementById('termModal').classList.remove('show');
}

// ===== AUTO-CALCULATE WEEKS & END DATE =====

let autoCalcTimeout = null;
let minStartDate = null; // earliest allowed start date (day after previous term ends)

// Called when Programme / Year / Semester dropdowns change
function onTermSelectionChange() {
    clearTimeout(autoCalcTimeout);
    autoCalcTimeout = setTimeout(() => fetchCreditHoursAndCalculate(true), 300);
}

async function fetchCreditHoursAndCalculate(recalcEndDate) {
    const programID = document.getElementById('formProgram').value;
    const year      = document.getElementById('formYear').value;
    const semester  = document.getElementById('formSemester').value;

    if (!programID || !year || !semester) return;

    try {
        // Fetch credit hours (determines short/long semester weeks)
        const [credRes, regRes] = await Promise.all([
            fetch(`../api/academicTerm.php?action=getCreditHours&programID=${encodeURIComponent(programID)}&year=${year}&semester=${semester}`),
            fetch(`../api/academicTerm.php?action=getRegDatesFromPrevTerm&programID=${encodeURIComponent(programID)}&year=${year}&semester=${semester}`)
        ]);
        const credData = await credRes.json();
        const regData  = await regRes.json();

        if (credData.success) {
            document.getElementById('formWeeks').value = credData.weeksTotal;
            showSemesterBadge(credData.weeksTotal, credData.totalCredits);
            if (recalcEndDate) autoCalculate();
        }

        // Only auto-fill reg dates when creating a new term (editTermID is empty)
        const isNew = !document.getElementById('editTermID').value;
        if (isNew && regData.success) {
            if (regData.noRegistration) {
                // Y1S1 — clear reg dates and show info
                document.getElementById('formRegStart').value = '';
                document.getElementById('formRegEnd').value   = '';
                document.getElementById('formRegStart').disabled = true;
                document.getElementById('formRegEnd').disabled   = true;
                showRegDateHint('none', null, null, regData.message);
                minStartDate = null;
                applyStartDateConstraint();
            } else {
                document.getElementById('formRegStart').disabled = false;
                document.getElementById('formRegEnd').disabled   = false;
                document.getElementById('formRegStart').value = regData.regStartDate;
                document.getElementById('formRegEnd').value   = regData.regEndDate;
                showRegDateHint(regData.basedOn, regData.regStartDate, regData.regEndDate);
                minStartDate = regData.minStartDate;
                applyStartDateConstraint();
            }
        } else if (isNew && !regData.success) {
            document.getElementById('formRegStart').disabled = false;
            document.getElementById('formRegEnd').disabled   = false;
            document.getElementById('formRegStart').value = '';
            document.getElementById('formRegEnd').value   = '';
            clearRegDateHint();
            minStartDate = null;
            applyStartDateConstraint();
        }
    } catch (err) {
        console.error('Error fetching term data:', err);
    }
}

function showRegDateHint(basedOn, regStart, regEnd, overrideMsg) {
    let hint = document.getElementById('regDateHint');
    if (!hint) {
        hint = document.createElement('div');
        hint.id = 'regDateHint';
        hint.style.cssText = 'grid-column:1/-1;padding:10px 14px;border-radius:10px;font-size:13px;';
        const regEndGroup = document.getElementById('formRegEnd').closest('.form-group');
        regEndGroup.after(hint);
    }
    hint.style.display = 'block';
    if (overrideMsg) {
        hint.style.background   = '#eff6ff';
        hint.style.border       = '1px solid #bfdbfe';
        hint.style.color        = '#1d4ed8';
        hint.innerHTML = `<i class="fa-solid fa-circle-info" style="margin-right:6px;"></i>${overrideMsg}`;
    } else {
        hint.style.background   = '#eff6ff';
        hint.style.border       = '1px solid #bfdbfe';
        hint.style.color        = '#1d4ed8';
        hint.innerHTML = `<i class="fa-solid fa-circle-info" style="margin-right:6px;"></i>Registration window auto-set from <strong>${basedOn}</strong> — Weeks 1–3 (${formatDate(regStart)} to ${formatDate(regEnd)}). You can adjust if needed.`;
    }
}

function clearRegDateHint() {
    const hint = document.getElementById('regDateHint');
    if (hint) hint.style.display = 'none';
}

function autoCalculate() {
    const startDate = document.getElementById('formStartDate').value;
    const weeks     = parseInt(document.getElementById('formWeeks').value);
    // Validate start date against previous term end
    validateStartDate(startDate);
    if (!startDate || !weeks) return;

    const start = new Date(startDate + 'T00:00:00');
    const end   = new Date(start);
    end.setDate(end.getDate() + (weeks * 7) - 1);
    document.getElementById('formEndDate').value = end.toISOString().split('T')[0];
}

function applyStartDateConstraint() {
    const input = document.getElementById('formStartDate');
    if (minStartDate) {
        input.min = minStartDate;
    } else {
        input.removeAttribute('min');
    }
    // Re-validate if a date is already entered
    if (input.value) validateStartDate(input.value);
}

function validateStartDate(dateValue) {
    let errEl = document.getElementById('startDateError');
    if (!errEl) {
        errEl = document.createElement('small');
        errEl.id = 'startDateError';
        errEl.style.cssText = 'color:#dc2626;font-size:12px;margin-top:4px;display:block;';
        document.getElementById('formStartDate').insertAdjacentElement('afterend', errEl);
    }
    if (minStartDate && dateValue && dateValue < minStartDate) {
        errEl.textContent = `⚠ Start date must be after the previous term's end date (${formatDate(minStartDate)}).`;
        errEl.style.display = 'block';
    } else {
        errEl.style.display = 'none';
    }
}

function showSemesterBadge(weeks, credits) {
    const badge    = document.getElementById('semesterTypeBadge');
    const infoBox  = document.getElementById('semesterInfoBox');
    const isShort  = weeks === 7;
    badge.textContent = isShort ? 'Short Semester (7 weeks)' : 'Long Semester (14 weeks)';
    badge.style.background = isShort ? '#dbeafe' : '#dcfce7';
    badge.style.color      = isShort ? '#1d4ed8' : '#15803d';
    badge.style.display    = 'inline-block';
    if (credits !== undefined) {
        infoBox.innerHTML = `<i class="fa-solid fa-circle-info" style="margin-right:6px;"></i>Total credit hours for this semester: <strong>${credits} credits</strong> — auto-assigned as <strong>${isShort ? 'Short (7 weeks)' : 'Long (14 weeks)'}</strong> semester.`;
        infoBox.style.display = 'block';
        infoBox.style.background = isShort ? '#eff6ff' : '#f0fdf4';
        infoBox.style.borderColor = isShort ? '#bfdbfe' : '#bbf7d0';
        infoBox.style.color = isShort ? '#1d4ed8' : '#15803d';
    }
}

function hideSemesterBadge() {
    document.getElementById('semesterTypeBadge').style.display = 'none';
    document.getElementById('semesterInfoBox').style.display = 'none';
}

async function saveTerm() {
    const termID = document.getElementById('editTermID').value;
    const data = {
        action: termID ? 'updateTerm' : 'createTerm',
        programID: document.getElementById('formProgram').value,
        academicYear: document.getElementById('formAcademicYear').value,
        year: document.getElementById('formYear').value,
        semester: document.getElementById('formSemester').value,
        startDate: document.getElementById('formStartDate').value,
        endDate: document.getElementById('formEndDate').value,
        regStartDate: document.getElementById('formRegStart').value,
        regEndDate: document.getElementById('formRegEnd').value,
        weeksTotal: document.getElementById('formWeeks').value,
        status: document.getElementById('formStatus').value
    };
    if (termID) data.termID = termID;

    // Basic validation
    if (!data.programID || !data.academicYear || !data.year || !data.semester || !data.startDate) {
        showToast('Please fill in all required fields', 'error');
        return;
    }
    // Block save if start date is invalid
    if (minStartDate && data.startDate < minStartDate) {
        showToast(`Start date must be after the previous term's end date (${formatDate(minStartDate)}).`, 'error');
        document.getElementById('formStartDate').focus();
        return;
    }
    if (!data.endDate) {
        showToast('End date could not be calculated. Please select Programme, Year, Semester and Start Date.', 'error');
        return;
    }

    try {
        const res = await fetch('../api/academicTerm.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();

        if (result.success) {
            showToast(result.message, 'success');
            closeModal();
            loadData();
        } else {
            showToast(result.message, 'error');
        }
    } catch (err) {
        showToast('Network error. Please try again.', 'error');
    }
}

function openDeleteModal(termID, label) {
    document.getElementById('deleteTermID').value = termID;
    document.getElementById('deleteMessage').textContent = `Are you sure you want to delete the academic term "${label}"? This action cannot be undone.`;
    document.getElementById('deleteModal').classList.add('show');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
}

async function confirmDelete() {
    const termID = document.getElementById('deleteTermID').value;

    try {
        const res = await fetch('../api/academicTerm.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'deleteTerm', termID })
        });
        const result = await res.json();

        if (result.success) {
            showToast(result.message, 'success');
            closeDeleteModal();
            loadData();
        } else {
            showToast(result.message, 'error');
        }
    } catch (err) {
        showToast('Network error. Please try again.', 'error');
    }
}

function showToast(msg, type) {
    const container = document.getElementById('toastContainer');
    const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<i class="fa-solid ${icon}"></i> ${msg}`;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
}
</script>

</body>
</html>
