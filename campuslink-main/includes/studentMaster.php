<?php
/**
 * Student Portal — Sidebar Navigation Master
 * 
 * Usage: Set $currentPage before including this file.
 *   $currentPage = 'dashboard'; // or 'attendance', 'assignments', 'timetable', 
 *                                //    'resource-booking', 'programme-structure', 
 *                                //    'course-enrollment', 'results', 'exam-slip', 'exam-schedule'
 *   include __DIR__ . '/../includes/studentMaster.php';  // from student/ folder
 *   include __DIR__ . '/includes/studentMaster.php';     // from root folder
 */

// Determine the root path based on the including file's location
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$isRoot = (basename($scriptDir) !== 'student');
$studentRoot = $isRoot ? '' : '../';
$studentFolder = $isRoot ? 'student/' : '';

// Determine active states
$programmePages = ['programme-structure', 'course-enrollment'];
$programmeOpen = in_array($currentPage ?? '', $programmePages);
?>
<aside class="sidebar" aria-label="Primary navigation">
    <div class="sidebar-head">
        <div class="brand-icon">CL</div>
        <div class="brand-text">
            <span class="brand-name">CAMPUSLink</span>
            <span class="brand-tagline">Student Portal</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="<?php echo $studentRoot; ?>index.php" class="nav-item<?php echo ($currentPage ?? '') === 'dashboard' ? ' is-active' : ''; ?>">
                    <span class="nav-icon"><i class="fa-solid fa-table-columns"></i></span>
                    <span class="nav-label">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $studentFolder; ?>student-attendance.php" class="nav-item<?php echo ($currentPage ?? '') === 'attendance' ? ' is-active' : ''; ?>">
                    <span class="nav-icon"><i class="fa-solid fa-clock"></i></span>
                    <span class="nav-label">Attendance</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $studentFolder; ?>student-assignments.php" class="nav-item<?php echo ($currentPage ?? '') === 'assignments' ? ' is-active' : ''; ?>">
                    <span class="nav-icon"><i class="fa-solid fa-file-pen"></i></span>
                    <span class="nav-label">Assignments</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $studentFolder; ?>student-timetable.php" class="nav-item<?php echo ($currentPage ?? '') === 'timetable' ? ' is-active' : ''; ?>">
                    <span class="nav-icon"><i class="fa-solid fa-calendar-days"></i></span>
                    <span class="nav-label">My Timetable</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $studentFolder; ?>resource-booking.php" class="nav-item<?php echo ($currentPage ?? '') === 'resource-booking' ? ' is-active' : ''; ?>">
                    <span class="nav-icon"><i class="fa-solid fa-building"></i></span>
                    <span class="nav-label">Resource Booking</span>
                </a>
            </li>
            <li class="nav-group<?php echo $programmeOpen ? ' is-open' : ''; ?>" data-expandable>
                <button class="nav-item nav-toggle" type="button" data-target="programme-panel"<?php echo $programmeOpen ? ' aria-expanded="true"' : ''; ?>>
                    <span class="nav-icon"><i class="fa-solid fa-clipboard-list"></i></span>
                    <span class="nav-label">Programme</span>
                    <span class="nav-chevron"></span>
                </button>
                <div class="nav-submenu" id="programme-panel">
                    <a href="<?php echo $studentFolder; ?>programme-structure.php" class="nav-subitem<?php echo ($currentPage ?? '') === 'programme-structure' ? ' active' : ''; ?>"<?php echo ($currentPage ?? '') === 'programme-structure' ? ' style="color: var(--purple-base); font-weight: 600;"' : ''; ?>>Programme Structure</a>
                    <a href="<?php echo $studentFolder; ?>course-enrollment.php" class="nav-subitem<?php echo ($currentPage ?? '') === 'course-enrollment' ? ' active' : ''; ?>"<?php echo ($currentPage ?? '') === 'course-enrollment' ? ' style="color: var(--purple-base); font-weight: 600;"' : ''; ?>>Course Enrollment</a>
                </div>
            </li>
            <li>
                <a href="<?php echo $studentRoot; ?>logout.php" class="nav-item" style="margin-top: 20px;">
                    <span class="nav-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                    <span class="nav-label" style="color: white;">Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
