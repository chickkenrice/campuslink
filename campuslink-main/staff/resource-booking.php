<?php
session_start();
require_once(__DIR__ . '/../includes/config.php');

if (!validate_session() || strcasecmp($_SESSION['role'], 'staff') !== 0) {
    header("Location: ../login.php");
    exit;
}

$userID = $_SESSION['user_id'];
$db = get_db_connection();

$stmt = $db->prepare("SELECT staffName, staffType FROM staff WHERE staffID = ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();

$staffName = $staff['staffName'] ?? 'Staff';
prevent_back_button_cache();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resource Booking - CAMPUSLink</title>
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
                        <a href="staff-timetable.php" class="nav-item">
                            <span class="nav-icon"><i class="fa-solid fa-calendar-week"></i></span>
                            <span class="nav-label">My Timetable</span>
                        </a>
                    </li>
                    <li>
                        <a href="resource-booking.php" class="nav-item is-active">
                            <span class="nav-icon"><i class="fa-solid fa-building"></i></span>
                            <span class="nav-label">Resource Booking</span>
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
                            <span class="user-name"><?php echo htmlspecialchars($staffName); ?></span>
                            <span class="user-role">Staff</span>
                        </div>
                        <div class="profile-pic">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Header -->
            <section class="welcome-card">
                <div class="welcome-details">
                    <p class="welcome-date"><?php echo date('l, j F Y'); ?></p>
                    <h1 class="welcome-title">Resource Booking</h1>
                    <p class="welcome-text">Book facilities for meetings, labs, sports, and more</p>
                </div>
            </section>

            <!-- Booking Section -->
            <section class="form-card" style="margin-top: 20px;">
                <h3 style="margin:0 0 20px 0; color:var(--purple-base);">
                    <i class="fa-solid fa-calendar-plus"></i> Book a Facility
                </h3>

                <div class="rb-steps">
                    <div class="rb-step-row">
                        <div class="rb-step-label">1. Select Type</div>
                        <div class="rb-type-filters" id="type-filters">
                            <button class="rb-type-btn active" data-type="">All</button>
                        </div>
                    </div>

                    <div class="rb-facility-grid" id="facility-grid">
                        <div class="rb-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading facilities...</div>
                    </div>

                    <div id="booking-panel" style="display:none;">
                        <div class="rb-selected-facility" id="selected-facility-info"></div>

                        <div class="rb-booking-form">
                            <div class="rb-form-row">
                                <div class="rb-form-group">
                                    <label for="rb-date">2. Select Date</label>
                                    <input type="date" id="rb-date" min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="rb-form-group">
                                    <label>Operating Hours</label>
                                    <span id="rb-operating-hours" class="rb-info-text">-</span>
                                </div>
                                <div class="rb-form-group">
                                    <label>Max Duration</label>
                                    <span id="rb-max-duration" class="rb-info-text">-</span>
                                </div>
                            </div>

                            <div id="slot-section" style="display:none;">
                                <label class="rb-slot-label">3. Select Time Slot</label>
                                <p style="font-size: 12px; color: var(--text-sub); margin: 0 0 10px 0;">
                                    Click the start time, then click the time you want to end at.
                                </p>
                                <div class="rb-slot-grid" id="slot-grid"></div>
                                <div class="rb-selected-time" id="selected-time-info" style="display:none;"></div>
                            </div>

                            <div id="purpose-section" style="display:none;">
                                <button id="btn-book" class="rb-btn-submit" onclick="submitBooking()" style="margin-top: 15px;">
                                    <i class="fa-solid fa-check"></i> Confirm Booking
                                </button>
                                <div id="booking-msg" style="display:none; margin-top:10px; padding:10px; border-radius:8px; font-size:13px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Slot Availability Button -->
            <section class="form-card" style="margin-top: 20px;">
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <h3 style="margin:0; color:var(--purple-base);">
                        <i class="fa-solid fa-calendar-week"></i> Facility Schedule
                    </h3>
                    <button class="rb-btn-submit" onclick="openScheduleModal()" style="margin:0; padding: 8px 20px;">
                        <i class="fa-solid fa-table-cells-large"></i> Slot Availability
                    </button>
                </div>
                <p style="margin:8px 0 0; color:var(--text-sub); font-size:13px;">View the availability of all facilities for any date</p>
            </section>

            <!-- My Bookings Section -->
            <section class="form-card" style="margin-top: 20px;">
                <div style="display:flex; align-items:center; justify-content:space-between; cursor:pointer;" onclick="toggleMyBookings()">
                    <h3 style="margin:0; color:var(--purple-base);">
                        <i class="fa-solid fa-clock-rotate-left"></i> My Bookings
                        <span id="active-booking-badge" class="rb-badge" style="display:none;">0</span>
                    </h3>
                    <i id="my-bookings-chevron" class="fa-solid fa-chevron-down" style="transition: transform 0.3s; color:var(--purple-base);"></i>
                </div>
                <div id="my-bookings-container" style="display:none; margin-top:15px;">
                    <div class="rb-filter-row">
                        <select id="booking-status-filter" onchange="loadMyBookings()">
                            <option value="">All Bookings</option>
                            <option value="Active">Active</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="table-container">
                        <table class="student-table" id="my-bookings-table" style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr style="background:#f9fafb; border-bottom:2px solid #eee;">
                                    <th style="padding:12px; text-align:left;">Facility</th>
                                    <th style="padding:12px; text-align:center;">Date</th>
                                    <th style="padding:12px; text-align:center;">Time</th>
                                    <th style="padding:12px; text-align:center;">Status</th>
                                    <th style="padding:12px; text-align:center;">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Schedule Modal -->
            <div id="schedule-modal" class="rb-modal-overlay" style="display:none;" onclick="if(event.target===this)closeScheduleModal()">
                <div class="rb-modal">
                    <div class="rb-modal-header">
                        <h3><i class="fa-solid fa-calendar-week"></i> Facility Slot Availability</h3>
                        <button class="rb-modal-close" onclick="closeScheduleModal()"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="rb-modal-body">
                        <div class="rb-admin-filters" style="margin-bottom:15px;">
                            <div class="rb-form-group">
                                <label for="modal-sched-date">Date</label>
                                <input type="date" id="modal-sched-date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="rb-form-group">
                                <label for="modal-sched-type">Facility Type</label>
                                <select id="modal-sched-type">
                                    <option value="">All Types</option>
                                </select>
                            </div>
                            <div class="rb-form-group" style="align-self: flex-end;">
                                <button class="rb-btn-submit" onclick="loadModalSchedule()" style="margin:0; padding: 8px 20px;">
                                    <i class="fa-solid fa-search"></i> View
                                </button>
                            </div>
                        </div>
                        <div class="schedule-legend">
                            <span class="schedule-legend-item"><span class="legend-swatch legend-class"></span> Class</span>
                            <span class="schedule-legend-item"><span class="legend-swatch legend-replacement"></span> Replacement</span>
                            <span class="schedule-legend-item"><span class="legend-swatch legend-booking"></span> Booking</span>
                            <span class="schedule-legend-item"><span class="legend-swatch legend-available"></span> Available</span>
                        </div>
                        <div id="modal-schedule-container" class="schedule-container">
                            <div style="text-align:center; padding:40px; color:var(--text-sub);">
                                <i class="fa-solid fa-calendar-week" style="font-size:2rem; margin-bottom:10px; display:block; opacity:0.3;"></i>
                                Click <strong>View</strong> to load the facility schedule
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="../script.js"></script>
<script>
    const userID = '<?php echo $userID; ?>';
    let allFacilities = [];
    let selectedFacility = null;
    let selectedSlots = { start: null, end: null, endClicked: null };
    let slotData = [];
    let maxDurationSlots = 4;

    document.addEventListener('DOMContentLoaded', function() {
        loadFacilities();
        loadMyBookings();
    });

    async function loadFacilities(typeFilter = '') {
        const grid = document.getElementById('facility-grid');
        grid.innerHTML = '<div class="rb-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading facilities...</div>';
        try {
            let url = `../api/resource_booking.php?action=getBookableFacilities`;
            if (typeFilter) url += `&type=${typeFilter}`;
            const res = await fetch(url);
            const result = await res.json();
            if (result.success) {
                allFacilities = result.data;
                renderFacilityGrid(result.data);
                renderTypeFilters(result.types);
            } else {
                grid.innerHTML = '<div class="rb-empty">Failed to load facilities</div>';
            }
        } catch (e) {
            console.error(e);
            grid.innerHTML = '<div class="rb-empty">Error loading facilities</div>';
        }
    }

    function renderTypeFilters(types) {
        const container = document.getElementById('type-filters');
        const currentActive = container.querySelector('.rb-type-btn.active')?.dataset.type || '';
        container.innerHTML = '<button class="rb-type-btn ' + (currentActive === '' ? 'active' : '') + '" data-type="" onclick="filterByType(\'\')">All</button>';
        const icons = { 'Sport': 'fa-volleyball', 'Lab': 'fa-flask', 'Meeting': 'fa-handshake', 'Study': 'fa-book-open', 'Tutorial': 'fa-chalkboard' };
        types.forEach(t => {
            const icon = icons[t] || 'fa-building';
            container.innerHTML += `<button class="rb-type-btn ${currentActive === t ? 'active' : ''}" data-type="${t}" onclick="filterByType('${t}')"><i class="fa-solid ${icon}"></i> ${t}</button>`;
        });
    }

    function filterByType(type) {
        document.querySelectorAll('.rb-type-btn').forEach(b => b.classList.remove('active'));
        document.querySelector(`.rb-type-btn[data-type="${type}"]`).classList.add('active');
        loadFacilities(type);
        document.getElementById('booking-panel').style.display = 'none';
        selectedFacility = null;
    }

    function renderFacilityGrid(facilities) {
        const grid = document.getElementById('facility-grid');
        if (facilities.length === 0) {
            grid.innerHTML = '<div class="rb-empty">No facilities available for your role</div>';
            return;
        }
        const icons = { 'Sport': 'fa-volleyball', 'Lab': 'fa-flask', 'Meeting': 'fa-handshake', 'Study': 'fa-book-open', 'Tutorial': 'fa-chalkboard' };
        grid.innerHTML = facilities.map(f => {
            const icon = icons[f.type] || 'fa-building';
            const isSelected = selectedFacility && selectedFacility.facilityID === f.facilityID;
            return `<div class="rb-facility-card ${isSelected ? 'is-selected' : ''}" onclick="selectFacility('${f.facilityID}')">
                <div class="rb-facility-icon"><i class="fa-solid ${icon}"></i></div>
                <div class="rb-facility-info">
                    <div class="rb-facility-name">${f.facilityName}</div>
                    <div class="rb-facility-meta">
                        <span><i class="fa-solid fa-location-dot"></i> ${f.location}</span>
                        <span><i class="fa-solid fa-users"></i> ${f.capacity}</span>
                    </div>
                    <span class="rb-facility-type">${f.type}</span>
                </div>
            </div>`;
        }).join('');
    }

    function selectFacility(facilityID) {
        selectedFacility = allFacilities.find(f => f.facilityID === facilityID);
        if (!selectedFacility) return;
        document.querySelectorAll('.rb-facility-card').forEach(c => c.classList.remove('is-selected'));
        document.querySelector(`.rb-facility-card[onclick="selectFacility('${facilityID}')"]`)?.classList.add('is-selected');
        const panel = document.getElementById('booking-panel');
        panel.style.display = 'block';
        document.getElementById('selected-facility-info').innerHTML = `
            <strong>${selectedFacility.facilityName}</strong> &mdash; ${selectedFacility.location}
            <span style="margin-left:10px; opacity:0.7;"><i class="fa-solid fa-users"></i> Capacity: ${selectedFacility.capacity}</span>`;
        const opStart = (selectedFacility.operatingStart || '08:00:00').substring(0, 5);
        const opEnd = (selectedFacility.operatingEnd || '22:00:00').substring(0, 5);
        document.getElementById('rb-operating-hours').textContent = `${opStart} - ${opEnd}`;
        const maxMin = selectedFacility.maxDurationMinutes || 120;
        maxDurationSlots = maxMin / 30;
        document.getElementById('rb-max-duration').textContent = `${maxMin} min (${maxMin / 60}h)`;
        const maxDays = selectedFacility.maxAdvanceDays || 7;
        const maxDate = new Date();
        maxDate.setDate(maxDate.getDate() + maxDays);
        document.getElementById('rb-date').max = maxDate.toISOString().split('T')[0];
        document.getElementById('rb-date').value = '';
        document.getElementById('slot-section').style.display = 'none';
        document.getElementById('purpose-section').style.display = 'none';
        selectedSlots = { start: null, end: null, endClicked: null };
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    document.getElementById('rb-date')?.addEventListener('change', async function() {
        const date = this.value;
        if (!date || !selectedFacility) return;
        const slotGrid = document.getElementById('slot-grid');
        const slotSection = document.getElementById('slot-section');
        slotSection.style.display = 'block';
        slotGrid.innerHTML = '<div class="rb-loading"><i class="fa-solid fa-spinner fa-spin"></i> Checking availability...</div>';
        document.getElementById('purpose-section').style.display = 'none';
        selectedSlots = { start: null, end: null, endClicked: null };
        try {
            const res = await fetch(`../api/resource_booking.php?action=getAvailableSlots&facilityID=${selectedFacility.facilityID}&date=${date}`);
            const result = await res.json();
            if (result.success) {
                slotData = result.slots;
                renderSlotGrid(result.slots);
            } else {
                slotGrid.innerHTML = `<div class="rb-empty">${result.message}</div>`;
            }
        } catch (e) {
            console.error(e);
            slotGrid.innerHTML = '<div class="rb-empty">Error loading slots</div>';
        }
    });

    function renderSlotGrid(slots) {
        const grid = document.getElementById('slot-grid');
        grid.innerHTML = '';
        slots.forEach((slot, idx) => {
            const btn = document.createElement('button');
            btn.className = `rb-slot ${slot.available ? 'available' : 'unavailable'}`;
            btn.dataset.index = idx;
            btn.textContent = `${slot.start} - ${slot.end}`;
            if (!slot.available) {
                btn.title = slot.reason || 'Unavailable';
                btn.disabled = true;
            } else {
                btn.onclick = () => toggleSlot(idx);
            }
            grid.appendChild(btn);
        });
        const legend = document.createElement('div');
        legend.className = 'rb-slot-legend';
        legend.innerHTML = `
            <span><span class="rb-legend-box available"></span> Available</span>
            <span><span class="rb-legend-box unavailable"></span> Unavailable</span>
            <span><span class="rb-legend-box selected"></span> Selected</span>`;
        grid.appendChild(legend);
    }

    function toggleSlot(idx) {
        if (selectedSlots.start === null) {
            // First click: set start (minimum booking is 1 hour = 2 slots)
            selectedSlots.start = idx;
            selectedSlots.end = idx;
            selectedSlots.endClicked = idx;
            highlightSlots();
            // Don't show time info yet, user must select end time for at least 1 hour
            document.getElementById('selected-time-info').innerHTML = 
                '<i class="fa-solid fa-info-circle"></i> Click end time (minimum 1 hour required)';
            document.getElementById('selected-time-info').style.display = 'block';
        } else if (selectedSlots.start === idx && selectedSlots.end === idx) {
            selectedSlots = { start: null, end: null, endClicked: null };
            highlightSlots();
            document.getElementById('selected-time-info').style.display = 'none';
            document.getElementById('purpose-section').style.display = 'none';
        } else {
            if (idx <= selectedSlots.start) {
                // Clicked on or before start: reset to new single slot
                selectedSlots.start = idx;
                selectedSlots.end = idx;
                selectedSlots.endClicked = idx;
                document.getElementById('selected-time-info').innerHTML = 
                    '<i class="fa-solid fa-info-circle"></i> Click end time (minimum 1 hour required)';
                document.getElementById('selected-time-info').style.display = 'block';
                document.getElementById('purpose-section').style.display = 'none';
            } else {
                // Second click = end slot (inclusive)
                const lastSlot = idx;
                const slotCount = lastSlot - selectedSlots.start + 1;
                
                // Minimum 1 hour (2 slots), Maximum based on facility rules
                if (slotCount < 2) {
                    showBookingMsg('Minimum booking duration is 1 hour. Please select at least 1 hour.', 'error');
                    return;
                }
                if (slotCount > maxDurationSlots) {
                    showBookingMsg(`Maximum booking duration is ${maxDurationSlots * 30} minutes. Please select fewer slots.`, 'error');
                    return;
                }
                
                for (let i = selectedSlots.start; i <= lastSlot; i++) {
                    if (!slotData[i].available) {
                        showBookingMsg('Cannot select across unavailable time slots.', 'error');
                        return;
                    }
                }
                selectedSlots.end = lastSlot;
                selectedSlots.endClicked = idx;
            }
            highlightSlots();
            updateTimeInfo();
        }
    }

    function highlightSlots() {
        document.querySelectorAll('.rb-slot').forEach(btn => btn.classList.remove('selected', 'in-range'));
        if (selectedSlots.start !== null) {
            for (let i = selectedSlots.start; i <= selectedSlots.end; i++) {
                const btn = document.querySelector(`.rb-slot[data-index="${i}"]`);
                if (btn) btn.classList.add(i === selectedSlots.start || i === selectedSlots.end ? 'selected' : 'in-range');
            }
        }
    }

    function updateTimeInfo() {
        if (selectedSlots.start === null) return;
        const startTime = slotData[selectedSlots.start].start;
        const endTime = slotData[selectedSlots.end].end;
        const duration = (selectedSlots.end - selectedSlots.start + 1) * 30;
        const info = document.getElementById('selected-time-info');
        info.style.display = 'block';
        info.innerHTML = `<i class="fa-solid fa-clock"></i> Selected: <strong>${startTime} - ${endTime}</strong> (${duration} min)`;
        document.getElementById('purpose-section').style.display = 'block';
    }

    async function submitBooking() {
        const date = document.getElementById('rb-date').value;
        if (!selectedFacility || selectedSlots.start === null || !date) { showBookingMsg('Please complete all steps.', 'error'); return; }
        const startTime = slotData[selectedSlots.start].start;
        const endTime = slotData[selectedSlots.end].end;
        const btn = document.getElementById('btn-book');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Booking...';
        try {
            const res = await fetch('../api/resource_booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'create', facilityID: selectedFacility.facilityID, bookingDate: date, startTime, endTime, purpose: '' })
            });
            const result = await res.json();
            if (result.success) {
                showBookingMsg(result.message, 'success');
                setTimeout(() => {
                    const dateInput = document.getElementById('rb-date');
                    if (dateInput.value) {
                        dateInput.dispatchEvent(new Event('change'));
                    }
                    loadMyBookings();
                }, 300);
                selectedSlots = { start: null, end: null, endClicked: null };
            } else {
                showBookingMsg(result.message, 'error');
            }
        } catch (e) {
            console.error(e);
            showBookingMsg('Error submitting booking. Please try again.', 'error');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Confirm Booking';
    }

    function toggleMyBookings() {
        const container = document.getElementById('my-bookings-container');
        const chevron = document.getElementById('my-bookings-chevron');
        if (container.style.display === 'none') { container.style.display = 'block'; chevron.style.transform = 'rotate(180deg)'; }
        else { container.style.display = 'none'; chevron.style.transform = 'rotate(0deg)'; }
    }

    async function loadMyBookings() {
        const statusFilter = document.getElementById('booking-status-filter')?.value || '';
        try {
            let url = `../api/resource_booking.php?action=getMyBookings`;
            if (statusFilter) url += `&status=${statusFilter}`;
            const res = await fetch(url);
            const result = await res.json();
            if (result.success) renderMyBookings(result.data);
        } catch (e) { console.error(e); }
    }

    function renderMyBookings(bookings) {
        const tbody = document.querySelector('#my-bookings-table tbody');
        const activeCount = bookings.filter(b => b.status === 'Active').length;
        const badge = document.getElementById('active-booking-badge');
        if (activeCount > 0) { badge.textContent = activeCount; badge.style.display = 'inline-flex'; }
        else { badge.style.display = 'none'; }
        if (bookings.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:30px; color:var(--text-sub);">No bookings found</td></tr>';
            return;
        }
        tbody.innerHTML = bookings.map(b => {
            const statusClass = b.status === 'Active' ? 'status-active' : b.status === 'Completed' ? 'status-completed' : 'status-cancelled';
            const canCancel = b.status === 'Active' && b.bookingDate >= '<?php echo date("Y-m-d"); ?>';
            const formattedDate = new Date(b.bookingDate).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            return `<tr style="border-bottom:1px solid #f0f0f0;">
                <td style="padding:12px;"><strong>${b.facilityName}</strong><br><small style="color:var(--text-sub);">${b.type} &bull; ${b.location}</small></td>
                <td style="padding:12px; text-align:center;">${formattedDate}</td>
                <td style="padding:12px; text-align:center; font-weight:600;">${b.startTime.substring(0,5)} - ${b.endTime.substring(0,5)}</td>
                <td style="padding:12px; text-align:center;"><span class="rb-status ${statusClass}">${b.status}</span></td>
                <td style="padding:12px; text-align:center;">${canCancel ? `<button class="rb-btn-cancel" onclick="cancelBooking(${b.bookingID})"><i class="fa-solid fa-xmark"></i> Cancel</button>` : '-'}</td>
            </tr>`;
        }).join('');
    }

    async function cancelBooking(bookingID) {
        if (!confirm('Are you sure you want to cancel this booking?')) return;
        try {
            const res = await fetch('../api/resource_booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'cancel', bookingID })
            });
            const result = await res.json();
            if (result.success) {
                loadMyBookings();
                const dateInput = document.getElementById('rb-date');
                if (dateInput.value) dateInput.dispatchEvent(new Event('change'));
            } else { alert(result.message); }
        } catch (e) { console.error(e); alert('Error cancelling booking'); }
    }

    function showBookingMsg(msg, type) {
        const el = document.getElementById('booking-msg');
        el.style.display = 'block';
        el.style.background = type === 'success' ? '#f0fdf4' : '#fef2f2';
        el.style.color = type === 'success' ? '#16a34a' : '#dc2626';
        el.style.border = `1px solid ${type === 'success' ? '#bbf7d0' : '#fecaca'}`;
        el.textContent = msg;
        if (type === 'success') setTimeout(() => { el.style.display = 'none'; }, 5000);
    }

    // ============================================================
    // SCHEDULE MODAL
    // ============================================================
    function openScheduleModal() {
        document.getElementById('schedule-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        loadModalScheduleTypes();
        loadModalSchedule();
    }

    function closeScheduleModal() {
        document.getElementById('schedule-modal').style.display = 'none';
        document.body.style.overflow = '';
    }

    async function loadModalScheduleTypes() {
        try {
            const res = await fetch('../api/resource_booking.php?action=getBookableFacilities');
            const result = await res.json();
            if (result.success) {
                const select = document.getElementById('modal-sched-type');
                select.innerHTML = '<option value="">All Types</option>';
                result.types.forEach(t => {
                    select.innerHTML += `<option value="${t}">${t}</option>`;
                });
            }
        } catch (e) { console.error(e); }
    }

    async function loadModalSchedule() {
        const date = document.getElementById('modal-sched-date').value;
        const type = document.getElementById('modal-sched-type').value;
        const container = document.getElementById('modal-schedule-container');

        container.innerHTML = '<div style="text-align:center; padding:40px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:1.5rem; color:var(--purple-base);"></i></div>';

        try {
            let url = `../api/resource_booking.php?action=getFacilitySchedule&date=${date}`;
            if (type) url += `&type=${type}`;

            const res = await fetch(url);
            const result = await res.json();

            if (result.success) {
                renderScheduleTimeline(result.facilities, result.date, container);
            } else {
                container.innerHTML = `<div style="text-align:center; padding:40px; color:#dc2626;">${result.message}</div>`;
            }
        } catch (e) {
            console.error(e);
            container.innerHTML = '<div style="text-align:center; padding:40px; color:#dc2626;">Error loading schedule</div>';
        }
    }

    function renderScheduleTimeline(facilities, date, container) {
        if (!facilities || facilities.length === 0) {
            container.innerHTML = '<div style="text-align:center; padding:40px; color:var(--text-sub);">No facilities found</div>';
            return;
        }

        let globalStart = 24, globalEnd = 0;
        facilities.forEach(f => {
            const opS = parseInt((f.operatingStart || '08:00:00').substring(0, 2));
            const opE = parseInt((f.operatingEnd || '22:00:00').substring(0, 2));
            if (opS < globalStart) globalStart = opS;
            if (opE > globalEnd) globalEnd = opE;
        });

        const totalHours = globalEnd - globalStart;
        const dayLabel = new Date(date + 'T00:00:00').toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });

        let headerHTML = '<div class="sched-header-cell sched-label-cell">Facility</div>';
        for (let h = globalStart; h < globalEnd; h++) {
            const startTime = String(h).padStart(2,'0') + ':00';
            const endTime = String(h + 1).padStart(2,'0') + ':00';
            headerHTML += `<div class="sched-header-cell">${startTime} - ${endTime}</div>`;
        }

        let rowsHTML = '';
        facilities.forEach(f => {
            const opS = parseInt((f.operatingStart || '08:00:00').substring(0, 2));
            const opE = parseInt((f.operatingEnd || '22:00:00').substring(0, 2));

            let blocksHTML = '';
            (f.events || []).forEach(ev => {
                const evStartMin = parseInt(ev.start.split(':')[0]) * 60 + parseInt(ev.start.split(':')[1]);
                const evEndMin = parseInt(ev.end.split(':')[0]) * 60 + parseInt(ev.end.split(':')[1]);
                const totalMin = totalHours * 60;
                const globalStartMin = globalStart * 60;
                const left = ((evStartMin - globalStartMin) / totalMin) * 100;
                const width = ((evEndMin - evStartMin) / totalMin) * 100;
                const typeClass = ev.type === 'class' ? 'sched-class' : ev.type === 'replacement' ? 'sched-replacement' : 'sched-booking';

                blocksHTML += `<div class="sched-block ${typeClass}" 
                    style="left:${left}%; width:${width}%;"
                    title="${ev.start} - ${ev.end}\n${ev.label}\n${ev.detail || ''}">
                    <span class="sched-block-label">${ev.start} - ${ev.end}</span>
                </div>`;
            });

            let shadingHTML = '';
            if (opS > globalStart) {
                shadingHTML += `<div class="sched-closed" style="left:0; width:${((opS - globalStart) / totalHours) * 100}%;" title="Closed"></div>`;
            }
            if (opE < globalEnd) {
                shadingHTML += `<div class="sched-closed" style="left:${((opE - globalStart) / totalHours) * 100}%; width:${((globalEnd - opE) / totalHours) * 100}%;" title="Closed"></div>`;
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
</script>

</body>
</html>
