<?php
/**
 * Admin Console — Sidebar Navigation Master
 * 
 * Usage: Set $currentPage before including this file.
 *   $currentPage = 'users';    // or 'activity-logs', 'timetable', 'resources',
 *                               //    'offerings', 'registration', 'terms'
 *   include __DIR__ . '/../includes/adminMaster.php';
 */
?>
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
                <a href="manage-students.php" class="nav-item<?php echo ($currentPage ?? '') === 'users' ? ' is-active' : ''; ?>">
                    <span class="nav-icon"><i class="fa-solid fa-users-gear"></i></span>
                    <span class="nav-label">User Management</span>
                </a>
            </li>
            <li>
                <a href="activity-logs.php" class="nav-item<?php echo ($currentPage ?? '') === 'activity-logs' ? ' is-active' : ''; ?>">
                    <span class="nav-icon"><i class="fa-solid fa-chart-line"></i></span>
                    <span class="nav-label">Activity Logs</span>
                </a>
            </li>
            <li>
                <a href="manage-announcements.php" class="nav-item<?php echo ($currentPage ?? '') === 'announcements' ? ' is-active' : ''; ?>">
                    <span class="nav-icon"><i class="fa-solid fa-bullhorn"></i></span>
                    <span class="nav-label">Announcements</span>
                </a>
            </li>
            <li>
                <a href="manage-timetable.php" class="nav-item<?php echo ($currentPage ?? '') === 'timetable' ? ' is-active' : ''; ?>">
                    <span class="nav-icon"><i class="fa-solid fa-calendar-days"></i></span>
                    <span class="nav-label">Timetable</span>
                </a>
            </li>
            <li>
                <a href="manage-resources.php" class="nav-item<?php echo ($currentPage ?? '') === 'resources' ? ' is-active' : ''; ?>">
                    <span class="nav-icon"><i class="fa-solid fa-building"></i></span>
                    <span class="nav-label">Resources</span>
                </a>
            </li>
            <li>
                <a href="manage-offerings.php" class="nav-item<?php echo ($currentPage ?? '') === 'offerings' ? ' is-active' : ''; ?>">
                    <span class="nav-icon"><i class="fa-solid fa-book-open"></i></span>
                    <span class="nav-label">Course Offerings</span>
                </a>
            </li>
            <li>
                <a href="registration-monitor.php" class="nav-item<?php echo ($currentPage ?? '') === 'registration' ? ' is-active' : ''; ?>">
                    <span class="nav-icon"><i class="fa-solid fa-chart-bar"></i></span>
                    <span class="nav-label">Registration Monitor</span>
                </a>
            </li>
            <li>
                <a href="manage-terms.php" class="nav-item<?php echo ($currentPage ?? '') === 'terms' ? ' is-active' : ''; ?>">
                    <span class="nav-icon"><i class="fa-solid fa-calendar-plus"></i></span>
                    <span class="nav-label">Academic Terms</span>
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
