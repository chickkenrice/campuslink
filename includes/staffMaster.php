<?php
/**
 * Staff Console — Sidebar Navigation Master
 * 
 * Usage: Set $currentPage before including this file.
 *   $currentPage = 'dashboard'; // or 'tutorial-management', 'attendance', 
 *                                //    'assignments', 'timetable', 'resource-booking'
 *   include __DIR__ . '/../includes/staffMaster.php';
 */

// Determine active states
$studentPages = ['tutorial-management', 'attendance', 'assignments'];
$studentOpen = in_array($currentPage ?? '', $studentPages);
?>
<aside class="sidebar">
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
                <a href="staff-dashboard.php" class="nav-item<?php echo ($currentPage ?? '') === 'dashboard' ? ' is-active' : ''; ?>">
                    <span class="nav-icon"><i class="fa-solid fa-table-columns"></i></span>
                    <span class="nav-label">Dashboard</span>
                </a>
            </li>

            <li class="nav-group<?php echo $studentOpen ? ' is-open' : ''; ?>" data-expandable>
                <button class="nav-item nav-toggle" type="button" aria-expanded="<?php echo $studentOpen ? 'true' : 'false'; ?>" data-target="student-menu">
                    <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
                    <span class="nav-label">Student</span>
                    <span class="nav-chevron"></span>
                </button>
                <div id="student-menu" class="nav-submenu">
                    <a href="tutorial-management.php" class="nav-subitem"<?php echo ($currentPage ?? '') === 'tutorial-management' ? ' style="background: rgba(255,255,255,0.1);"' : ''; ?>>Tutorial Management</a>
                    <a href="staff-attendance.php" class="nav-subitem"<?php echo ($currentPage ?? '') === 'attendance' ? ' style="background: rgba(255,255,255,0.1);"' : ''; ?>>Attendance</a>
                    <a href="staff-assignments.php" class="nav-subitem"<?php echo ($currentPage ?? '') === 'assignments' ? ' style="background: rgba(255,255,255,0.1);"' : ''; ?>>Assignment</a>
                </div>
            </li>

            <li>
                <a href="staff-timetable.php" class="nav-item<?php echo ($currentPage ?? '') === 'timetable' ? ' is-active' : ''; ?>">
                    <span class="nav-icon"><i class="fa-solid fa-calendar-week"></i></span>
                    <span class="nav-label">My Timetable</span>
                </a>
            </li>

            <li>
                <a href="resource-booking.php" class="nav-item<?php echo ($currentPage ?? '') === 'resource-booking' ? ' is-active' : ''; ?>">
                    <span class="nav-icon"><i class="fa-solid fa-building"></i></span>
                    <span class="nav-label">Resource Booking</span>
                </a>
            </li>

            <li>
                <a href="../logout.php" class="nav-item nav-item-logout" style="margin-top: 20px;">
                    <span class="nav-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                    <span class="nav-label">Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
