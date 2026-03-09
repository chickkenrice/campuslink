<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

prevent_back_button_cache();
$adminName = $_SESSION['user_name'] ?? 'Admin';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Announcements - CAMPUSLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/course-enrollment.css">
    <style>
        .ann-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 12px;
            background: #fff;
            transition: box-shadow 0.2s, border-color 0.2s;
        }
        .ann-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border-color: #c4b5fd;
        }
        .ann-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .ann-card-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .ann-card-meta {
            font-size: 12px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .ann-card-body {
            font-size: 14px;
            color: #475569;
            line-height: 1.6;
            background: #f8fafc;
            padding: 12px 16px;
            border-radius: 10px;
            border-left: 3px solid #c4b5fd;
        }
        .ann-actions {
            display: flex;
            gap: 6px;
        }
        .ann-actions button {
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
        .ann-actions button:hover {
            background: #ede9fe;
            border-color: #c4b5fd;
            color: #7c3aed;
        }
        .ann-actions button.btn-delete:hover {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #dc2626;
        }
        .audience-badge {
            display: inline-flex;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .audience-student {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .audience-staff {
            background: #fef3c7;
            color: #d97706;
        }
        .audience-all {
            background: #dcfce7;
            color: #15803d;
        }
        .ann-modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.4);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .ann-modal-overlay.show {
            display: flex;
        }
        .ann-modal {
            background: #fff;
            border-radius: 18px;
            padding: 28px;
            width: 600px;
            max-width: 95vw;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .ann-modal h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 16px;
        }
        .form-group label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
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
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
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
            margin-bottom: 0;
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
        .char-count {
            font-size: 11px;
            color: #94a3b8;
            text-align: right;
            margin-top: 4px;
        }
        /* Announcement Type Badges */
        .type-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .type-system {
            background: #f3e8ff;
            color: #7c3aed;
        }
        .type-academic {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .type-campus {
            background: #dcfce7;
            color: #15803d;
        }
        /* Pin Button */
        .pin-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: #94a3b8;
            transition: all 0.2s;
            padding: 4px;
        }
        .pin-btn:hover { color: #f59e0b; }
        .pin-btn.pinned { color: #f59e0b; }
        .pinned-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: #f59e0b;
            font-size: 11px;
            font-weight: 600;
        }
        /* Form toggle */
        .toggle-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
        }
        .toggle-switch {
            position: relative;
            width: 44px;
            height: 24px;
        }
        .toggle-switch input {
            display: none;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background: #e2e8f0;
            border-radius: 24px;
            transition: 0.3s;
        }
        .toggle-slider:before {
            content: '';
            position: absolute;
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: 0.3s;
        }
        .toggle-switch input:checked + .toggle-slider {
            background: #f59e0b;
        }
        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(20px);
        }
        .toggle-label {
            font-size: 13px;
            color: #334155;
        }
    </style>
</head>
<body>

<div class="app-bg">
    <div class="main-card">
        
        <?php $currentPage = 'announcements'; include __DIR__ . '/../includes/adminMaster.php'; ?>

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
                    <h1 class="welcome-title">Announcements</h1>
                    <div class="welcome-meta">
                        <span class="welcome-meta-sub"><i class="fa-solid fa-bullhorn"></i> Create and manage announcements for students and staff</span>
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
                    <i class="fa-solid fa-plus"></i> New Announcement
                </button>
            </section>

            <!-- Summary Cards -->
            <div class="ce-summary-row">
                <div class="ce-summary-card">
                    <div class="ce-summary-icon" style="background: #ede9fe;">
                        <i class="fa-solid fa-bullhorn" style="color: #7c3aed;"></i>
                    </div>
                    <div class="ce-summary-info">
                        <span class="ce-summary-value" id="statTotal">0</span>
                        <span class="ce-summary-label">Total</span>
                    </div>
                </div>
                <div class="ce-summary-card">
                    <div class="ce-summary-icon" style="background: #dbeafe;">
                        <i class="fa-solid fa-user-graduate" style="color: #1d4ed8;"></i>
                    </div>
                    <div class="ce-summary-info">
                        <span class="ce-summary-value" id="statStudent">0</span>
                        <span class="ce-summary-label">For Students</span>
                    </div>
                </div>
                <div class="ce-summary-card">
                    <div class="ce-summary-icon" style="background: #fef3c7;">
                        <i class="fa-solid fa-chalkboard-user" style="color: #d97706;"></i>
                    </div>
                    <div class="ce-summary-info">
                        <span class="ce-summary-value" id="statStaff">0</span>
                        <span class="ce-summary-label">For Staff</span>
                    </div>
                </div>
                <div class="ce-summary-card">
                    <div class="ce-summary-icon" style="background: #dcfce7;">
                        <i class="fa-solid fa-users" style="color: #15803d;"></i>
                    </div>
                    <div class="ce-summary-info">
                        <span class="ce-summary-value" id="statAll">0</span>
                        <span class="ce-summary-label">For Everyone</span>
                    </div>
                </div>
            </div>

            <!-- Announcements List -->
            <div class="ce-table-container">
                <div class="filter-bar">
                    <div class="form-group">
                        <label><i class="fa-solid fa-users" style="margin-right: 4px;"></i>Audience</label>
                        <select id="filterAudience" onchange="renderAnnouncements()" style="
                            padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 10px;
                            font-size: 13px; font-family: 'Inter', sans-serif; background: #f8fafc;
                            cursor: pointer; outline: none; color: #334155; width: 100%;
                        ">
                            <option value="">All Audiences</option>
                            <option value="student">Students Only</option>
                            <option value="staff">Staff Only</option>
                            <option value="all">Everyone</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-tag" style="margin-right: 4px;"></i>Type</label>
                        <select id="filterType" onchange="renderAnnouncements()" style="
                            padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 10px;
                            font-size: 13px; font-family: 'Inter', sans-serif; background: #f8fafc;
                            cursor: pointer; outline: none; color: #334155; width: 100%;
                        ">
                            <option value="">All Types</option>
                            <option value="system">System</option>
                            <option value="academic">Academic</option>
                            <option value="campus">Campus</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-search" style="margin-right: 4px;"></i>Search</label>
                        <input type="text" id="filterSearch" placeholder="Search announcements..." oninput="renderAnnouncements()" style="
                            padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 10px;
                            font-size: 13px; font-family: 'Inter', sans-serif; background: #f8fafc;
                            outline: none; color: #334155; width: 100%;
                        ">
                    </div>
                </div>
                <div id="announcementsList" style="padding: 0 15px 15px;">
                    <div class="ce-loading">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        <span>Loading announcements...</span>
                    </div>
                </div>
            </div>
            
            <?php include __DIR__ . '/../includes/footer.php'; ?>
        </main>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="annModal" class="ann-modal-overlay">
    <div class="ann-modal">
        <h2><i class="fa-solid fa-bullhorn" style="color: #7c3aed;"></i> <span id="modalTitle">New Announcement</span></h2>
        <input type="hidden" id="editAnnID" value="">
        
        <div class="form-group">
            <label>Title *</label>
            <input type="text" id="formTitle" placeholder="Enter announcement title" maxlength="200">
            <div class="char-count"><span id="titleCount">0</span>/200</div>
        </div>
        
        <div class="form-group">
            <label>Target Audience *</label>
            <select id="formAudience">
                <option value="all">Everyone (Students & Staff)</option>
                <option value="student">Students Only</option>
                <option value="staff">Staff Only</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Announcement Type *</label>
            <select id="formType">
                <option value="system">System</option>
                <option value="academic">Academic</option>
                <option value="campus">Campus</option>
            </select>
        </div>
        
        <div class="toggle-group">
            <label class="toggle-switch">
                <input type="checkbox" id="formPinned">
                <span class="toggle-slider"></span>
            </label>
            <span class="toggle-label"><i class="fa-solid fa-thumbtack" style="color: #f59e0b; margin-right: 4px;"></i>Pin this announcement</span>
        </div>
        
        <div class="form-group">
            <label>Message *</label>
            <textarea id="formBody" placeholder="Enter announcement message..." maxlength="2000"></textarea>
            <div class="char-count"><span id="bodyCount">0</span>/2000</div>
        </div>
        
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            <button class="btn-save" onclick="saveAnnouncement()"><i class="fa-solid fa-check"></i> Publish</button>
        </div>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div id="deleteModal" class="ann-modal-overlay">
    <div class="ann-modal" style="width: 400px;">
        <h2><i class="fa-solid fa-triangle-exclamation" style="color: #dc2626;"></i> Delete Announcement</h2>
        <p style="color: #555; line-height: 1.6; font-size: 14px;" id="deleteMessage">Are you sure you want to delete this announcement?</p>
        <input type="hidden" id="deleteAnnID" value="">
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn-save" style="background: #dc2626;" onclick="confirmDelete()"><i class="fa-solid fa-trash"></i> Delete</button>
        </div>
    </div>
</div>

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<script>
let allAnnouncements = [];

document.addEventListener('DOMContentLoaded', () => {
    loadAnnouncements();
    setupCharCounters();
});

function setupCharCounters() {
    const titleInput = document.getElementById('formTitle');
    const bodyInput = document.getElementById('formBody');
    
    titleInput.addEventListener('input', () => {
        document.getElementById('titleCount').textContent = titleInput.value.length;
    });
    
    bodyInput.addEventListener('input', () => {
        document.getElementById('bodyCount').textContent = bodyInput.value.length;
    });
}

async function loadAnnouncements() {
    try {
        const res = await fetch('../api/announcements.php?admin=1');
        const data = await res.json();
        
        if (data.success) {
            allAnnouncements = data.data;
            renderAnnouncements();
            updateSummary();
        } else {
            showToast('Failed to load announcements', 'error');
        }
    } catch (err) {
        console.error('Error loading announcements:', err);
        showToast('Network error. Please try again.', 'error');
    }
}

function renderAnnouncements() {
    const container = document.getElementById('announcementsList');
    const filterAudience = document.getElementById('filterAudience').value;
    const filterType = document.getElementById('filterType').value;
    const filterSearch = document.getElementById('filterSearch').value.toLowerCase();
    
    let filtered = allAnnouncements;
    
    if (filterAudience) {
        filtered = filtered.filter(a => a.targetAudience === filterAudience);
    }
    
    if (filterType) {
        filtered = filtered.filter(a => a.announcementType === filterType);
    }
    
    if (filterSearch) {
        filtered = filtered.filter(a => 
            a.title.toLowerCase().includes(filterSearch) || 
            a.body.toLowerCase().includes(filterSearch)
        );
    }
    
    if (filtered.length === 0) {
        container.innerHTML = `
            <div class="ce-empty-state">
                <i class="fa-solid fa-bullhorn"></i>
                <p>No announcements found.</p>
                <small>Click "New Announcement" to create one.</small>
            </div>
        `;
        return;
    }
    
    container.innerHTML = filtered.map(a => {
        const audienceClass = `audience-${a.targetAudience}`;
        const audienceLabel = a.targetAudience === 'student' ? 'Students' : 
                             a.targetAudience === 'staff' ? 'Staff' : 'Everyone';
        const audienceIcon = a.targetAudience === 'student' ? 'fa-user-graduate' : 
                            a.targetAudience === 'staff' ? 'fa-chalkboard-user' : 'fa-users';
        
        // Type badge
        const typeClass = `type-${a.announcementType || 'system'}`;
        const typeLabel = a.announcementType === 'academic' ? 'Academic' : 
                         a.announcementType === 'campus' ? 'Campus' : 'System';
        const typeIcon = a.announcementType === 'academic' ? 'fa-graduation-cap' : 
                        a.announcementType === 'campus' ? 'fa-building-columns' : 'fa-gear';
        
        const isPinned = a.isPinned === 1 || a.isPinned === '1';
        const pinClass = isPinned ? 'pinned' : '';
        const pinIcon = isPinned ? 'fa-solid fa-thumbtack' : 'fa-regular fa-thumbtack';
        
        return `
            <div class="ann-card" style="${isPinned ? 'border-left: 3px solid #f59e0b;' : ''}">
                <div class="ann-card-header">
                    <div>
                        <div class="ann-card-title">
                            ${isPinned ? '<span class="pinned-badge"><i class="fa-solid fa-thumbtack"></i></span> ' : ''}
                            ${escapeHtml(a.title)}
                        </div>
                        <div class="ann-card-meta">
                            <span><i class="fa-regular fa-clock"></i> ${formatDate(a.createdAt)}</span>
                            ${a.createdBy ? `<span><i class="fa-regular fa-user"></i> ${escapeHtml(a.createdBy)}</span>` : ''}
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="type-badge ${typeClass}">
                            <i class="fa-solid ${typeIcon}"></i>${typeLabel}
                        </span>
                        <span class="audience-badge ${audienceClass}">
                            <i class="fa-solid ${audienceIcon}" style="margin-right: 4px;"></i>${audienceLabel}
                        </span>
                        <div class="ann-actions">
                            <button class="pin-btn ${pinClass}" onclick="togglePin(${a.id})" title="${isPinned ? 'Unpin' : 'Pin'}"><i class="${pinIcon}"></i></button>
                            <button onclick="openEditModal(${a.id})" title="Edit"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn-delete" onclick="openDeleteModal(${a.id}, '${escapeHtml(a.title)}')" title="Delete"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                </div>
                <div class="ann-card-body">
                    ${escapeHtml(a.body).replace(/\n/g, '<br>')}
                </div>
            </div>
        `;
    }).join('');
}

function updateSummary() {
    document.getElementById('statTotal').textContent = allAnnouncements.length;
    document.getElementById('statStudent').textContent = allAnnouncements.filter(a => a.targetAudience === 'student').length;
    document.getElementById('statStaff').textContent = allAnnouncements.filter(a => a.targetAudience === 'staff').length;
    document.getElementById('statAll').textContent = allAnnouncements.filter(a => a.targetAudience === 'all').length;
}

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'New Announcement';
    document.getElementById('editAnnID').value = '';
    document.getElementById('formTitle').value = '';
    document.getElementById('formAudience').value = 'all';
    document.getElementById('formType').value = 'system';
    document.getElementById('formPinned').checked = false;
    document.getElementById('formBody').value = '';
    document.getElementById('titleCount').textContent = '0';
    document.getElementById('bodyCount').textContent = '0';
    document.getElementById('annModal').classList.add('show');
}

function openEditModal(id) {
    const ann = allAnnouncements.find(a => a.id === id);
    if (!ann) return;
    
    document.getElementById('modalTitle').textContent = 'Edit Announcement';
    document.getElementById('editAnnID').value = id;
    document.getElementById('formTitle').value = ann.title;
    document.getElementById('formAudience').value = ann.targetAudience;
    document.getElementById('formType').value = ann.announcementType || 'system';
    document.getElementById('formPinned').checked = ann.isPinned === 1 || ann.isPinned === '1';
    document.getElementById('formBody').value = ann.body;
    document.getElementById('titleCount').textContent = ann.title.length;
    document.getElementById('bodyCount').textContent = ann.body.length;
    document.getElementById('annModal').classList.add('show');
}

function closeModal() {
    document.getElementById('annModal').classList.remove('show');
}

async function saveAnnouncement() {
    const id = document.getElementById('editAnnID').value;
    const title = document.getElementById('formTitle').value.trim();
    const audience = document.getElementById('formAudience').value;
    const announcementType = document.getElementById('formType').value;
    const isPinned = document.getElementById('formPinned').checked ? 1 : 0;
    const body = document.getElementById('formBody').value.trim();
    
    if (!title || !body) {
        showToast('Please fill in title and message', 'error');
        return;
    }
    
    const data = {
        action: id ? 'update' : 'create',
        title,
        targetAudience: audience,
        announcementType,
        isPinned,
        body
    };
    
    if (id) data.id = parseInt(id);
    
    try {
        const res = await fetch('../api/announcements.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            closeModal();
            loadAnnouncements();
        } else {
            showToast(result.message || 'Failed to save', 'error');
        }
    } catch (err) {
        showToast('Network error. Please try again.', 'error');
    }
}

async function togglePin(id) {
    try {
        const res = await fetch('../api/announcements.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'togglePin', id })
        });
        const result = await res.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            loadAnnouncements();
        } else {
            showToast(result.message || 'Failed to toggle pin', 'error');
        }
    } catch (err) {
        showToast('Network error. Please try again.', 'error');
    }
}

function openDeleteModal(id, title) {
    document.getElementById('deleteAnnID').value = id;
    document.getElementById('deleteMessage').textContent = `Are you sure you want to delete "${title}"? This action cannot be undone.`;
    document.getElementById('deleteModal').classList.add('show');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
}

async function confirmDelete() {
    const id = parseInt(document.getElementById('deleteAnnID').value);
    
    try {
        const res = await fetch('../api/announcements.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        const result = await res.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            closeDeleteModal();
            loadAnnouncements();
        } else {
            showToast(result.message || 'Failed to delete', 'error');
        }
    } catch (err) {
        showToast('Network error. Please try again.', 'error');
    }
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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
