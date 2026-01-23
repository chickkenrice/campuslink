<?php
session_start();

// 1. CONFIG & SECURITY
require_once(__DIR__ . '/includes/config.php'); 

if (!isset($_SESSION['role']) || strcasecmp($_SESSION['role'], 'student') !== 0) {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['user_id'];
$db = get_db_connection();

// 2. GET STUDENT INFO
$stmt = $db->prepare("SELECT studentName, tutGroup, programID FROM student WHERE studentID = ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

$studentName = $student['studentName'] ?? 'Student';
$myGroup = $student['tutGroup'] ?? 'Unassigned';
$myProgram = $student['programID'] ?? '';

// 3. FETCH SCHEDULE DATA
$sql = "SELECT * FROM class_schedule cs
        JOIN course c ON cs.courseID = c.courseID
        JOIN facility f ON cs.facilityID = f.facilityID
        JOIN staff s ON cs.staffID = s.staffID
        WHERE cs.tutGroup = ? 
        AND cs.programID = ?"; 

$stmt = $db->prepare($sql);
$stmt->bind_param("ss", $myGroup, $myProgram);
$stmt->execute();
$result = $stmt->get_result();

// =============================================================
// 4. BUILD 30-MINUTE SCHEDULE MATRIX
// =============================================================
$startHour = 8;
$endHour = 18;
$totalSlots = ($endHour - $startHour) * 2;
$scheduleMatrix = [];

while ($row = $result->fetch_assoc()) {
    $day = $row['day'];
    
    // Parse Start Time
    list($sH, $sM) = explode(':', $row['startTime']);
    $startIndex = (($sH - $startHour) * 2) + ($sM == '30' ? 1 : 0);

    // Parse End Time
    list($eH, $eM) = explode(':', $row['endTime']);
    $endIndex = (($eH - $startHour) * 2) + ($eM == '30' ? 1 : 0);

    $durationSlots = $endIndex - $startIndex;

    if ($durationSlots <= 0 || $startIndex < 0 || $endIndex > $totalSlots) continue;

    $isBlocked = false;
    for ($i = 0; $i < $durationSlots; $i++) {
        if (isset($scheduleMatrix[$day][$startIndex + $i])) {
            $isBlocked = true;
            break;
        }
    }

    if (!$isBlocked) {
        $scheduleMatrix[$day][$startIndex] = [
            'info' => $row,
            'colspan' => $durationSlots
        ];
        
        for ($i = 1; $i < $durationSlots; $i++) {
            $scheduleMatrix[$day][$startIndex + $i] = 'occupied';
        }
    }
}

$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Timetable - CAMPUSLink</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/timetable.css">
    
    <style>
        /* Extra styles for Course Name inside block */
        .subject-name {
            font-size: 9px;
            font-weight: 500;
            display: block;
            margin-bottom: 3px;
            line-height: 1.1;
            opacity: 0.95;
            white-space: nowrap; 
            overflow: hidden;
            text-overflow: ellipsis; /* Adds ... if text is too long */
            max-width: 100%;
        }
    </style>
</head>
<body>

<div class="app-bg">
    <div class="main-card">
        
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
                    <li><a href="index.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-table-columns"></i></span> Dashboard</a></li>
                    <li><a href="attendance.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-clock"></i></span> Attendance</a></li>
                    <li><a href="student-timetable.php" class="nav-item is-active"><span class="nav-icon"><i class="fa-solid fa-calendar-days"></i></span> My Timetable</a></li>
                    <li class="nav-group" data-expandable>
                        <button class="nav-item nav-toggle" type="button" data-target="programme-panel">
                            <span class="nav-icon"><i class="fa-solid fa-clipboard-list"></i></span>
                            <span class="nav-label">Programme</span>
                            <span class="nav-chevron"></span>
                        </button>
                        <div class="nav-submenu" id="programme-panel" hidden>
                            <a href="programme-structure.php" class="nav-subitem">Programme Structure</a>
                            <a href="#" class="nav-subitem">Course Enrollment</a>
                            <a href="#" class="nav-subitem">Results</a>
                        </div>
                    </li>
                    <li class="nav-group" data-expandable>
                        <button class="nav-item nav-toggle" type="button" data-target="examination-panel">
                            <span class="nav-icon"><i class="fa-solid fa-book"></i></span>
                            <span class="nav-label">Examination</span>
                            <span class="nav-chevron"></span>
                        </button>
                        <div class="nav-submenu" id="examination-panel" hidden>
                            <a href="#" class="nav-subitem">Exam Slip</a>
                            <a href="#" class="nav-subitem">Exam Schedule</a>
                        </div>
                    </li>
                    <li><a href="logout.php" class="nav-item" style="color: #e74c3c; margin-top: 20px;"><span class="nav-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="dashboard">
            <header class="dashboard-topbar">
                <div>
                    <h1 class="welcome-title">My Timetable</h1>
                    <p style="color: var(--text-sub); font-size: 14px; margin-top: 5px;">
                        Viewing schedule for: <strong style="color: var(--purple-base);"><?php echo htmlspecialchars($myProgram . " - " . $myGroup); ?></strong>
                    </p>
                </div>
                
                <div class="topbar-right">
                    <div class="user-card" style="display: flex; align-items: center; gap: 12px; flex-direction: row-reverse;">
                        <a href="student-profile.php" class="profile-link" title="View Profile">
                            <div class="profile-pic" style="width: 42px; height: 42px; border-radius: 50%; background: #e0e7ff; display: grid; place-items: center; border: 2px solid #c7d2fe; overflow: hidden;">
                                <i class="fa-solid fa-user" style="color: #4f46e5; font-size: 18px;"></i>
                            </div>
                        </a>
                        <div class="user-meta" style="text-align: right;">
                            <span class="user-name" style="display: block; font-weight: 600; color: #333;"><?php echo htmlspecialchars($studentName); ?></span>
                            <span class="user-status" style="font-size: 12px; color: #666;">Student</span>
                        </div>
                    </div>
                </div>
            </header>

            <section class="timetable-card">
                <div class="timetable-wrapper">
                    <table class="timetable">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Day</th>
                                <?php for ($h = $startHour; $h < $endHour; $h++): ?>
                                    <th colspan="2">
                                        <?php echo sprintf("%02d:00 - %02d:00", $h, $h+1); ?>
                                    </th>
                                <?php endfor; ?>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($daysOfWeek as $day): ?>
                                <tr>
                                    <td class="day-column"><?php echo substr($day, 0, 3); ?></td>

                                    <?php 
                                    for ($i = 0; $i < $totalSlots; $i++) {
                                        if (isset($scheduleMatrix[$day][$i])) {
                                            $slot = $scheduleMatrix[$day][$i];
                                            if ($slot === 'occupied') continue;

                                            $colspan = $slot['colspan'];
                                            $info = $slot['info'];
                                            $typeClass = 'is-' . ($info['classType'] ?? 'Lecture');

                                            echo "<td colspan='$colspan' class='time-slot'>";
                                                echo "<div class='class-container $typeClass'>";
                                                    // FIX: Display Course Name below Code
                                                    $suffix = ($info['classType'] == 'Lecture') ? '(L)' : '(P)';
                                                    echo "<span class='subject-code'>{$info['courseID']} $suffix</span>";
                                                    echo "<span class='subject-name'>{$info['courseName']}</span>";
                                                    echo "<span class='subject-loc'><i class='fa-solid fa-location-dot'></i> {$info['facilityID']}</span>";
                                                    echo "<span class='subject-prof'>{$info['staffName']}</span>";
                                                echo "</div>";
                                            echo "</td>";
                                        } else {
                                            echo "<td class='time-slot empty'></td>";
                                        }
                                    }
                                    ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>

<script>
    document.querySelectorAll('.nav-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const targetPanel = document.getElementById(targetId);
            const isExpanded = btn.getAttribute('aria-expanded') === 'true';
            
            if (targetPanel) {
                targetPanel.hidden = isExpanded;
                btn.setAttribute('aria-expanded', !isExpanded);
                btn.parentElement.classList.toggle('is-open', !isExpanded);
            }
        });
    });
</script>

</body>
</html>