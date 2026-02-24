<?php
session_start();
require_once(__DIR__ . '/../includes/config.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
prevent_back_button_cache();

$db = get_db_connection();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resource Monitor - CAMPUSLink</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/resource-booking.css">
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
                        <a href="manage-resources.php" class="nav-item is-active">
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
                        <a href="registration-monitor.php" class="nav-item">
                            <span class="nav-icon"><i class="fa-solid fa-chart-bar"></i></span>
                            <span class="nav-label">Registration Monitor</span>
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
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                            <span class="user-role">Admin</span>
                        </div>
                        <div class="profile-pic">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Header -->
            <section class="welcome-card" style="display: flex; justify-content: space-between; align-items: center;">
                <div class="welcome-details">
                    <p class="welcome-date"><?php echo date('l, j F Y'); ?></p>
                    <h1 class="welcome-title">Resource Monitor</h1>
                    <p class="welcome-text">Monitor facility bookings by students and staff</p>
                </div>
                <a href="facility-inventory-report.php" class="btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 8px; font-weight: 600; text-decoration: none; background: white; color: var(--purple-base); font-size: 13px; position: relative; z-index: 2; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                    <i class="fa-solid fa-clipboard-list"></i> Facility Inventory Report
                </a>
            </section>

            <!-- Stats Cards -->
            <div class="rb-stats-row" id="stats-row">
                <div class="rb-stat-card">
                    <div class="rb-stat-icon" style="background: #ede9fe;"><i class="fa-solid fa-calendar-check" style="color:#7c3aed;"></i></div>
                    <div class="rb-stat-info">
                        <span class="rb-stat-value" id="stat-total">-</span>
                        <span class="rb-stat-label">Total Bookings</span>
                    </div>
                </div>
                <div class="rb-stat-card">
                    <div class="rb-stat-icon" style="background: #dcfce7;"><i class="fa-solid fa-circle-check" style="color:#16a34a;"></i></div>
                    <div class="rb-stat-info">
                        <span class="rb-stat-value" id="stat-active">-</span>
                        <span class="rb-stat-label">Active</span>
                    </div>
                </div>
                <div class="rb-stat-card">
                    <div class="rb-stat-icon" style="background: #dbeafe;"><i class="fa-solid fa-user-graduate" style="color:#2563eb;"></i></div>
                    <div class="rb-stat-info">
                        <span class="rb-stat-value" id="stat-students">-</span>
                        <span class="rb-stat-label">By Students</span>
                    </div>
                </div>
                <div class="rb-stat-card">
                    <div class="rb-stat-icon" style="background: #fef3c7;"><i class="fa-solid fa-chalkboard-user" style="color:#d97706;"></i></div>
                    <div class="rb-stat-info">
                        <span class="rb-stat-value" id="stat-staff">-</span>
                        <span class="rb-stat-label">By Staff</span>
                    </div>
                </div>
            </div>

            <!-- Filters & Table -->
            <section class="form-card" style="margin-top: 20px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                    <h3 style="margin:0; color:var(--purple-base); display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-list"></i> All Bookings
                    </h3>
                    <span id="bookings-count" style="background: #f3f4f6; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; color: #6b7280;">
                        0 results
                    </span>
                </div>

                <div class="rb-admin-filters" style="background: #f9fafb; padding: 18px; border-radius: 12px; border: 1px solid #e5e7eb;">
                    <div class="rb-form-group">
                        <label for="admin-date-from"><i class="fa-solid fa-calendar-day" style="margin-right: 5px;"></i>From</label>
                        <input type="date" id="admin-date-from" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="rb-form-group">
                        <label for="admin-date-to"><i class="fa-solid fa-calendar-day" style="margin-right: 5px;"></i>To</label>
                        <input type="date" id="admin-date-to" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                    </div>
                    <div class="rb-form-group">
                        <label for="admin-type-filter"><i class="fa-solid fa-building" style="margin-right: 5px;"></i>Type</label>
                        <select id="admin-type-filter">
                            <option value="">All Types</option>
                        </select>
                    </div>
                    <div class="rb-form-group">
                        <label for="admin-status-filter"><i class="fa-solid fa-filter" style="margin-right: 5px;"></i>Status</label>
                        <select id="admin-status-filter">
                            <option value="">All</option>
                            <option value="Active">Active</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="rb-form-group" style="align-self: flex-end;">
                        <button class="rb-btn-submit" onclick="loadAllBookings()" style="margin:0; padding: 10px 24px; box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);">
                            <i class="fa-solid fa-search"></i> Search
                        </button>
                    </div>
                </div>

                <div class="table-container" style="margin-top: 20px; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb;">
                    <table class="student-table rb-bookings-table" id="admin-bookings-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: linear-gradient(135deg, #7c3aed 0%, #6366f1 100%);">
                                <th style="width: 60px; padding: 14px 12px; color: white; font-weight: 600; font-size: 12px; text-transform: uppercase;">#</th>
                                <th style="min-width: 140px; padding: 14px 12px; color: white; font-weight: 600; font-size: 12px; text-transform: uppercase;">User</th>
                                <th style="width: 90px; padding: 14px 12px; color: white; font-weight: 600; font-size: 12px; text-transform: uppercase; text-align: center;">Role</th>
                                <th style="min-width: 180px; padding: 14px 12px; color: white; font-weight: 600; font-size: 12px; text-transform: uppercase;">Facility</th>
                                <th style="width: 110px; padding: 14px 12px; color: white; font-weight: 600; font-size: 12px; text-transform: uppercase;">Date</th>
                                <th style="width: 110px; padding: 14px 12px; color: white; font-weight: 600; font-size: 12px; text-transform: uppercase;">Time</th>
                                <th style="min-width: 140px; padding: 14px 12px; color: white; font-weight: 600; font-size: 12px; text-transform: uppercase;">Purpose</th>
                                <th style="width: 90px; padding: 14px 12px; color: white; font-weight: 600; font-size: 12px; text-transform: uppercase; text-align: center;">Status</th>
                                <th style="width: 130px; padding: 14px 12px; color: white; font-weight: 600; font-size: 12px; text-transform: uppercase;">Booked At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="9" style="text-align:center; padding:40px; color:var(--text-sub); background: #f9fafb;">
                                <i class="fa-solid fa-search" style="font-size: 32px; color: #d1d5db; margin-bottom: 10px;"></i>
                                <p style="margin: 0; font-size: 14px;">Click Search to load bookings</p>
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Master Facility Schedule View -->
            <section class="form-card" style="margin-top: 20px;">
                <h3 style="margin:0 0 15px 0; color:var(--purple-base);">
                    <i class="fa-solid fa-calendar-week"></i> Master Facility Schedule View
                </h3>

                <div class="rb-admin-filters">
                    <div class="rb-form-group">
                        <label for="schedule-date">Date</label>
                        <input type="date" id="schedule-date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="rb-form-group">
                        <label for="schedule-type">Facility Type</label>
                        <select id="schedule-type">
                            <option value="">All Types</option>
                        </select>
                    </div>
                    <div class="rb-form-group" style="align-self: flex-end;">
                        <button class="rb-btn-submit" onclick="loadSchedule()" style="margin:0; padding: 8px 20px;">
                            <i class="fa-solid fa-search"></i> View
                        </button>
                    </div>
                </div>

                <!-- Legend -->
                <div class="schedule-legend">
                    <span class="schedule-legend-item"><span class="legend-swatch legend-class"></span> Class</span>
                    <span class="schedule-legend-item"><span class="legend-swatch legend-replacement"></span> Replacement</span>
                    <span class="schedule-legend-item"><span class="legend-swatch legend-booking"></span> Booking</span>
                    <span class="schedule-legend-item"><span class="legend-swatch legend-available"></span> Available</span>
                </div>

                <div id="schedule-container" class="schedule-container">
                    <div style="text-align:center; padding:40px; color:var(--text-sub);">
                        <i class="fa-solid fa-calendar-week" style="font-size:2rem; margin-bottom:10px; display:block; opacity:0.3;"></i>
                        Click <strong>View</strong> to load the facility schedule
                    </div>
                </div>
            </section>

            <!-- Facility Status Management -->
            <section class="card" style="margin-top: 24px;">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <h2><i class="fa-solid fa-toggle-on"></i> Facility Status Management</h2>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <select id="status-type-filter" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--gray-200);">
                            <option value="">All Types</option>
                        </select>
                        <select id="status-status-filter" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--gray-200);">
                            <option value="">All Statuses</option>
                            <option value="Active">Active</option>
                            <option value="Closed">Closed</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>

                <div class="table-container" style="overflow-x: auto;">
                    <table id="facility-status-table" class="data-table" style="width: 100%; border-collapse: separate; border-spacing: 0;">
                        <thead>
                            <tr style="background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%); color: white;">
                                <th style="padding: 16px 16px; text-align: left; font-weight: 600; border-bottom: 2px solid #6d28d9; min-width: 100px;">Facility ID</th>
                                <th style="padding: 16px 16px; text-align: left; font-weight: 600; border-bottom: 2px solid #6d28d9; min-width: 150px;">Name</th>
                                <th style="padding: 16px 16px; text-align: left; font-weight: 600; border-bottom: 2px solid #6d28d9; min-width: 100px;">Type</th>
                                <th style="padding: 16px 16px; text-align: left; font-weight: 600; border-bottom: 2px solid #6d28d9; min-width: 120px;">Location</th>
                                <th style="padding: 16px 16px; text-align: center; font-weight: 600; border-bottom: 2px solid #6d28d9; min-width: 80px;">Capacity</th>
                                <th style="padding: 16px 16px; text-align: center; font-weight: 600; border-bottom: 2px solid #6d28d9; min-width: 140px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:40px;">
                                    <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; color: var(--purple-base);"></i>
                                    <p style="margin: 10px 0 0; color: #6b7280;">Loading facilities...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

        </main>
    </div>
</div>

<script src="../script.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        loadFacilityTypes();
        loadAllBookings();
        loadScheduleTypes();
        loadSchedule();
        loadFacilityStatusTable();
    });

    // Attach filter change events
    ['admin-date-from', 'admin-date-to', 'admin-type-filter', 'admin-status-filter'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', loadAllBookings);
    });

    async function loadFacilityTypes() {
        try {
            const res = await fetch('../api/resource_booking.php?action=getBookableFacilities');
            const result = await res.json();
            if (result.success) {
                const select = document.getElementById('admin-type-filter');
                result.types.forEach(t => {
                    select.innerHTML += `<option value="${t}">${t}</option>`;
                });
            }
        } catch (e) { console.error(e); }
    }

    async function loadAllBookings() {
        const dateFrom = document.getElementById('admin-date-from').value;
        const dateTo = document.getElementById('admin-date-to').value;
        const typeFilter = document.getElementById('admin-type-filter').value;
        const statusFilter = document.getElementById('admin-status-filter').value;

        const tbody = document.querySelector('#admin-bookings-table tbody');
        const countBadge = document.getElementById('bookings-count');
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:40px; background: #f9fafb;"><i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; color: var(--purple-base);"></i><p style="margin: 10px 0 0; color: #6b7280;">Loading bookings...</p></td></tr>';
        countBadge.textContent = 'Loading...';

        try {
            let url = `../api/resource_booking.php?action=getAllBookings&dateFrom=${dateFrom}&dateTo=${dateTo}`;
            if (typeFilter) url += `&type=${typeFilter}`;
            if (statusFilter) url += `&status=${statusFilter}`;

            const res = await fetch(url);
            const result = await res.json();

            if (result.success) {
                renderAdminBookings(result.data);
                updateStats(result.stats);
            } else {
                tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:40px; background: #fef2f2;"><i class="fa-solid fa-exclamation-circle" style="font-size: 32px; color: #dc2626; margin-bottom: 10px;"></i><p style="margin: 0; color: #dc2626;">${result.message}</p></td></tr>`;
                countBadge.textContent = '0 results';
            }
        } catch (e) {
            console.error(e);
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:40px; background: #fef2f2;"><i class="fa-solid fa-exclamation-triangle" style="font-size: 32px; color: #dc2626; margin-bottom: 10px;"></i><p style="margin: 0; color: #dc2626;">Error loading bookings</p></td></tr>';
            countBadge.textContent = '0 results';
        }
    }

    function renderAdminBookings(bookings) {
        const tbody = document.querySelector('#admin-bookings-table tbody');
        const countBadge = document.getElementById('bookings-count');

        if (bookings.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:40px; color:var(--text-sub); background: #f9fafb;"><i class="fa-solid fa-inbox" style="font-size: 32px; color: #d1d5db; margin-bottom: 10px;"></i><p style="margin: 0;">No bookings found for the selected period</p></td></tr>';
            countBadge.textContent = '0 results';
            return;
        }

        countBadge.textContent = `${bookings.length} result${bookings.length !== 1 ? 's' : ''}`;

        tbody.innerHTML = bookings.map((b, idx) => {
            const statusClass = b.status === 'Active' ? 'status-active' : b.status === 'Completed' ? 'status-completed' : 'status-cancelled';
            const roleClass = b.userRole === 'Student' ? 'rb-role-student' : 'rb-role-staff';
            const createdAt = b.created_at ? new Date(b.created_at).toLocaleString('en-GB', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' }) : '-';
            const bookingDate = new Date(b.bookingDate).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
            const rowBg = idx % 2 === 0 ? '#ffffff' : '#f9fafb';
            return `<tr style="background: ${rowBg}; border-bottom: 1px solid #e5e7eb; transition: background 0.15s;">
                <td style="padding: 16px 12px; font-weight: 600; color: var(--purple-base);">#${b.bookingID}</td>
                <td style="padding: 16px 12px;">
                    <div style="display: flex; flex-direction: column; gap: 3px;">
                        <strong style="font-size: 13px; color: #111827;">${b.userName || b.userID}</strong>
                        <small style="color:#9ca3af; font-size: 11px;">${b.userID}</small>
                    </div>
                </td>
                <td style="padding: 16px 12px; text-align: center;"><span class="${roleClass}">${b.userRole}</span></td>
                <td style="padding: 16px 12px;">
                    <div style="display: flex; flex-direction: column; gap: 3px;">
                        <strong style="font-size: 13px; color: #111827;">${b.facilityName}</strong>
                        <small style="color:#9ca3af; font-size: 11px;"><i class="fa-solid fa-location-dot" style="margin-right: 3px; color: #a78bfa;"></i>${b.location}</small>
                    </div>
                </td>
                <td style="padding: 16px 12px; white-space: nowrap; font-size: 13px; color: #374151;">${bookingDate}</td>
                <td style="padding: 16px 12px; white-space: nowrap; font-weight: 600; font-size: 13px; color: #374151;">${b.startTime.substring(0,5)} - ${b.endTime.substring(0,5)}</td>
                <td style="padding: 16px 12px; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #6b7280;" title="${b.purpose}">${b.purpose}</td>
                <td style="padding: 16px 12px; text-align: center;"><span class="rb-status ${statusClass}">${b.status}</span></td>
                <td style="padding: 16px 12px;"><small style="color: #6b7280; font-size: 11px;">${createdAt}</small></td>
            </tr>`;
        }).join('');
    }

    function updateStats(stats) {
        if (!stats) return;
        document.getElementById('stat-total').textContent = stats.total || 0;
        document.getElementById('stat-active').textContent = stats.active || 0;
        document.getElementById('stat-students').textContent = stats.byStudents || 0;
        document.getElementById('stat-staff').textContent = stats.byStaff || 0;
    }

    // ============================================================
    // MASTER FACILITY SCHEDULE VIEW
    // ============================================================
    async function loadScheduleTypes() {
        try {
            const res = await fetch('../api/resource_booking.php?action=getBookableFacilities');
            const result = await res.json();
            if (result.success) {
                const select = document.getElementById('schedule-type');
                result.types.forEach(t => {
                    select.innerHTML += `<option value="${t}">${t}</option>`;
                });
            }
        } catch (e) { console.error(e); }
    }

    async function loadSchedule() {
        const date = document.getElementById('schedule-date').value;
        const type = document.getElementById('schedule-type').value;
        const container = document.getElementById('schedule-container');

        container.innerHTML = '<div style="text-align:center; padding:40px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:1.5rem; color:var(--purple-base);"></i></div>';

        try {
            let url = `../api/resource_booking.php?action=getFacilitySchedule&date=${date}`;
            if (type) url += `&type=${type}`;

            const res = await fetch(url);
            const result = await res.json();

            if (result.success) {
                renderScheduleTimeline(result.facilities, result.date);
            } else {
                container.innerHTML = `<div style="text-align:center; padding:40px; color:#dc2626;">${result.message}</div>`;
            }
        } catch (e) {
            console.error(e);
            container.innerHTML = '<div style="text-align:center; padding:40px; color:#dc2626;">Error loading schedule</div>';
        }
    }

    function renderScheduleTimeline(facilities, date) {
        const container = document.getElementById('schedule-container');

        if (!facilities || facilities.length === 0) {
            container.innerHTML = '<div style="text-align:center; padding:40px; color:var(--text-sub);">No facilities found</div>';
            return;
        }

        // Determine the global operating range (earliest start to latest end)
        let globalStart = 24, globalEnd = 0;
        facilities.forEach(f => {
            const opS = parseInt((f.operatingStart || '08:00:00').substring(0, 2));
            const opE = parseInt((f.operatingEnd || '22:00:00').substring(0, 2));
            if (opS < globalStart) globalStart = opS;
            if (opE > globalEnd) globalEnd = opE;
        });

        const totalHours = globalEnd - globalStart;
        const dayLabel = new Date(date + 'T00:00:00').toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });

        // Build header hours
        let headerHTML = '<div class="sched-header-cell sched-label-cell">Resource / Time</div>';
        for (let h = globalStart; h < globalEnd; h++) {
            const startTime = String(h).padStart(2,'0') + ':00';
            const endTime = String(h + 1).padStart(2,'0') + ':00';
            headerHTML += `<div class="sched-header-cell">${startTime} - ${endTime}</div>`;
        }

        // Build rows
        let rowsHTML = '';
        facilities.forEach(f => {
            const opS = parseInt((f.operatingStart || '08:00:00').substring(0, 2));
            const opE = parseInt((f.operatingEnd || '22:00:00').substring(0, 2));

            // Build event blocks
            let blocksHTML = '';
            (f.events || []).forEach(ev => {
                const evStartParts = ev.start.split(':');
                const evEndParts = ev.end.split(':');
                const evStartMin = parseInt(evStartParts[0]) * 60 + parseInt(evStartParts[1]);
                const evEndMin = parseInt(evEndParts[0]) * 60 + parseInt(evEndParts[1]);

                const totalMin = totalHours * 60;
                const globalStartMin = globalStart * 60;
                const left = ((evStartMin - globalStartMin) / totalMin) * 100;
                const width = ((evEndMin - evStartMin) / totalMin) * 100;

                const typeClass = ev.type === 'class' ? 'sched-class' :
                                  ev.type === 'replacement' ? 'sched-replacement' : 'sched-booking';

                blocksHTML += `<div class="sched-block ${typeClass}" 
                    style="left:${left}%; width:${width}%;"
                    title="${ev.start} - ${ev.end}\n${ev.label}\n${ev.detail}">
                    <span class="sched-block-label">${ev.start} - ${ev.end}</span>
                </div>`;
            });

            // Non-operating shading (before and after operating hours)
            let shadingHTML = '';
            if (opS > globalStart) {
                const w = ((opS - globalStart) / totalHours) * 100;
                shadingHTML += `<div class="sched-closed" style="left:0; width:${w}%;" title="Closed"></div>`;
            }
            if (opE < globalEnd) {
                const l = ((opE - globalStart) / totalHours) * 100;
                const w = ((globalEnd - opE) / totalHours) * 100;
                shadingHTML += `<div class="sched-closed" style="left:${l}%; width:${w}%;" title="Closed"></div>`;
            }

            rowsHTML += `
                <div class="sched-row">
                    <div class="sched-label-cell">
                        <strong>${f.facilityName}</strong>
                        <small>${f.type}</small>
                    </div>
                    <div class="sched-track">
                        ${shadingHTML}
                        ${blocksHTML}
                    </div>
                </div>`;
        });

        container.innerHTML = `
            <div class="sched-date-label"><i class="fa-solid fa-calendar-day"></i> ${dayLabel}</div>
            <div class="sched-grid" style="--sched-cols: ${totalHours};">
                <div class="sched-header">${headerHTML}</div>
                <div class="sched-body">${rowsHTML}</div>
            </div>`;
    }

    // ================================================================
    // Facility Status Management
    // ================================================================

    // Attach filter change events for status management
    ['status-type-filter', 'status-status-filter'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', loadFacilityStatusTable);
    });

    async function loadFacilityStatusTable() {
        const typeFilter = document.getElementById('status-type-filter').value;
        const statusFilter = document.getElementById('status-status-filter').value;
        const tbody = document.querySelector('#facility-status-table tbody');

        tbody.innerHTML = `<tr>
            <td colspan="6" style="text-align:center; padding:40px;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; color: var(--purple-base);"></i>
                <p style="margin: 10px 0 0; color: #6b7280;">Loading facilities...</p>
            </td>
        </tr>`;

        try {
            let url = `../api/resource_booking.php?action=getAllFacilities`;
            if (typeFilter) url += `&type=${encodeURIComponent(typeFilter)}`;
            if (statusFilter) url += `&status=${encodeURIComponent(statusFilter)}`;

            const res = await fetch(url);
            const result = await res.json();

            if (result.success) {
                renderFacilityStatusTable(result.data);
                populateStatusTypeFilter(result.types);
            } else {
                tbody.innerHTML = `<tr>
                    <td colspan="6" style="text-align:center; padding:40px; background: #fef2f2;">
                        <i class="fa-solid fa-exclamation-circle" style="font-size: 32px; color: #dc2626; margin-bottom: 10px;"></i>
                        <p style="margin: 0; color: #dc2626;">${result.message}</p>
                    </td>
                </tr>`;
            }
        } catch (e) {
            console.error(e);
            tbody.innerHTML = `<tr>
                <td colspan="6" style="text-align:center; padding:40px; background: #fef2f2;">
                    <i class="fa-solid fa-exclamation-triangle" style="font-size: 32px; color: #dc2626; margin-bottom: 10px;"></i>
                    <p style="margin: 0; color: #dc2626;">Error loading facilities</p>
                </td>
            </tr>`;
        }
    }

    function populateStatusTypeFilter(types) {
        const select = document.getElementById('status-type-filter');
        const currentVal = select.value;
        // Clear existing options except first
        select.innerHTML = '<option value="">All Types</option>';
        types.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t;
            opt.textContent = t;
            if (t === currentVal) opt.selected = true;
            select.appendChild(opt);
        });
    }

    function renderFacilityStatusTable(facilities) {
        const tbody = document.querySelector('#facility-status-table tbody');

        if (facilities.length === 0) {
            tbody.innerHTML = `<tr>
                <td colspan="6" style="text-align:center; padding:40px; background: #f9fafb;">
                    <i class="fa-solid fa-building" style="font-size: 32px; color: #9ca3af; margin-bottom: 10px;"></i>
                    <p style="margin: 0; color: #6b7280;">No facilities found</p>
                </td>
            </tr>`;
            return;
        }

        let html = '';
        facilities.forEach((f, i) => {
            const bgColor = i % 2 === 0 ? '#ffffff' : '#f9fafb';
            const statusColors = {
                'Active': { bg: '#d1fae5', color: '#065f46' },
                'Closed': { bg: '#fee2e2', color: '#991b1b' },
                'Maintenance': { bg: '#fef3c7', color: '#92400e' }
            };
            const statusStyle = statusColors[f.status] || { bg: '#e5e7eb', color: '#374151' };

            html += `<tr style="background: ${bgColor};">
                <td style="padding: 16px; border-bottom: 1px solid #e5e7eb; font-family: monospace;">${f.facilityID}</td>
                <td style="padding: 16px; border-bottom: 1px solid #e5e7eb; font-weight: 500;">${f.facilityName}</td>
                <td style="padding: 16px; border-bottom: 1px solid #e5e7eb;">${f.type}</td>
                <td style="padding: 16px; border-bottom: 1px solid #e5e7eb;">${f.location}</td>
                <td style="padding: 16px; border-bottom: 1px solid #e5e7eb; text-align: center;">${f.capacity}</td>
                <td style="padding: 16px; border-bottom: 1px solid #e5e7eb; text-align: center;">
                    <select onchange="updateFacilityStatus('${f.facilityID}', this.value, this)" 
                            style="padding: 8px 16px; border-radius: 20px; border: none; font-weight: 600; cursor: pointer;
                                   background: ${statusStyle.bg}; color: ${statusStyle.color};">
                        <option value="Active" ${f.status === 'Active' ? 'selected' : ''} style="background: #d1fae5; color: #065f46;">Active</option>
                        <option value="Closed" ${f.status === 'Closed' ? 'selected' : ''} style="background: #fee2e2; color: #991b1b;">Closed</option>
                        <option value="Maintenance" ${f.status === 'Maintenance' ? 'selected' : ''} style="background: #fef3c7; color: #92400e;">Maintenance</option>
                    </select>
                </td>
            </tr>`;
        });

        tbody.innerHTML = html;
    }

    async function updateFacilityStatus(facilityID, newStatus, selectElement) {
        const statusColors = {
            'Active': { bg: '#d1fae5', color: '#065f46' },
            'Closed': { bg: '#fee2e2', color: '#991b1b' },
            'Maintenance': { bg: '#fef3c7', color: '#92400e' }
        };

        try {
            const res = await fetch('../api/resource_booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'updateFacilityStatus',
                    facilityID: facilityID,
                    status: newStatus
                })
            });

            const result = await res.json();

            if (result.success) {
                // Update the select styling
                const style = statusColors[newStatus] || { bg: '#e5e7eb', color: '#374151' };
                selectElement.style.background = style.bg;
                selectElement.style.color = style.color;

                // Show success toast
                showStatusToast('Status updated successfully', 'success');
            } else {
                showStatusToast(result.message || 'Failed to update status', 'error');
                // Reload to reset
                loadFacilityStatusTable();
            }
        } catch (e) {
            console.error(e);
            showStatusToast('Error updating status', 'error');
            loadFacilityStatusTable();
        }
    }

    function showStatusToast(message, type) {
        // Remove existing toast
        const existing = document.querySelector('.status-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = 'status-toast';
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 8px;
            font-weight: 500;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            ${type === 'success' 
                ? 'background: #10b981; color: white;' 
                : 'background: #ef4444; color: white;'}
        `;
        toast.innerHTML = `<i class="fa-solid ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${message}`;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
</script>

</body>
</html>
