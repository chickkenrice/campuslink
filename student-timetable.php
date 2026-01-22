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
// Added 'programme' to the SELECT list
$stmt = $db->prepare("SELECT studentName, tutGroup, programme FROM student WHERE studentID = ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

$studentName = $student['studentName'] ?? 'Student';
$myGroup = $student['tutGroup'] ?? 'Unassigned';
// Get the program ID (e.g. 'RSD', 'RSW')
$myProgram = $student['programme'] ?? '';

// 3. FETCH SCHEDULE DATA
// Added 'AND cs.programID = ?' to the WHERE clause
$sql = "SELECT * FROM class_schedule cs
        JOIN course c ON cs.courseID = c.courseID
        JOIN facility f ON cs.facilityID = f.facilityID
        JOIN staff s ON cs.staffID = s.staffID
        WHERE cs.tutGroup = ? 
        AND cs.programID = ?"; 

$stmt = $db->prepare($sql);
// Bind both the Group (string) and Program (string)
$stmt->bind_param("ss", $myGroup, $myProgram);

// 4. BUILD SCHEDULE MATRIX
$scheduleMatrix = [];
while ($row = $result->fetch_assoc()) {
    $day = $row['day'];
    $startHour = (int)date('G', strtotime($row['startTime']));
    $endHour   = (int)date('G', strtotime($row['endTime']));
    $duration  = $endHour - $startHour;

    $scheduleMatrix[$day][$startHour] = [
        'info' => $row,
        'duration' => $duration
    ];
    
    // Mark covered hours
    for ($i = 1; $i < $duration; $i++) {
        $scheduleMatrix[$day][$startHour + $i] = 'occupied';
    }
}

$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$startOfDay = 8; // 8 AM
$endOfDay   = 18; // 6 PM
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
    
    <style>
        /* CORE VARIABLES */
        :root {
            --purple-base: #8056ff;
            --sidebar-gradient: linear-gradient(200deg, #8f5dff 0%, #6c44f6 100%);
            --body-bg: #f5f7fa;
            --text-main: #181b2f;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--body-bg);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* LAYOUT STRUCTURE */
        .app-layout {
            display: flex;
            width: 100%;
            padding: 20px;
            gap: 20px;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            background: var(--sidebar-gradient);
            border-radius: 24px;
            padding: 30px;
            color: white;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            height: calc(100vh - 40px);
            position: sticky;
            top: 20px;
        }

        .brand-block {
            display: flex; align-items: center; gap: 12px; margin-bottom: 40px;
        }
        .brand-logo {
            width: 48px; height: 48px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: grid; place-items: center; font-weight: 700;
        }
        .nav-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; }
        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px;
            text-decoration: none;
            color: rgba(255,255,255,0.7);
            font-weight: 600;
            border-radius: 14px;
            transition: 0.2s;
        }
        .nav-item:hover, .nav-item.is-active {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        /* MAIN DASHBOARD AREA */
        .main-content {
            flex: 1;
            background: white;
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
            overflow: hidden; /* Prevent body scroll if table scrolls */
        }

        .header-row {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;
        }

        /* TIMETABLE GRID SYSTEM */
        .timetable-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #eee;
        }

        table.timetable {
            width: 100%;
            border-collapse: collapse; /* Removes double borders */
            min-width: 1200px; /* Ensures width on small screens */
            table-layout: fixed; /* STRICT alignment */
        }

        /* HEADERS */
        .timetable thead th {
            background: #f9fafb;
            color: #666;
            font-size: 13px;
            font-weight: 700;
            padding: 12px;
            border-bottom: 2px solid #eee;
            border-right: 1px solid #f0f0f0;
            text-transform: uppercase;
        }

        /* DAY COLUMN */
        .day-column {
            width: 100px;
            background: #fff;
            color: var(--purple-base);
            font-weight: 700;
            text-align: center;
            border-right: 2px solid #eee;
            border-bottom: 1px solid #f0f0f0;
        }

        /* SLOTS */
        .time-slot {
            border-bottom: 1px solid #f0f0f0;
            border-right: 1px solid #f0f0f0;
            height: 100px;
            padding: 0; /* REMOVED PADDING to fix alignment */
            vertical-align: top;
            position: relative;
        }
        
        .time-slot.empty:hover {
            background-color: #fafafa;
        }

        /* CLASS BLOCK */
        .class-container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            padding: 5px;
            /* Small margin to simulate grid gap without breaking alignment */
            border: 1px solid white; 
            box-sizing: border-box;
            border-radius: 6px;
        }

        .is-Lecture { background-color: #4CAF50; }
        .is-Practical { background-color: #6c5ce7; }

        .subject-code { font-weight: 800; font-size: 13px; margin-bottom: 4px; display: block; }
        .subject-loc { font-size: 11px; font-weight: 600; display: block; opacity: 0.9; }
        .subject-prof { font-size: 10px; font-style: italic; opacity: 0.8; margin-top: 2px; display: block; }

    </style>
</head>
<body>

<div class="app-layout">
    
    <aside class="sidebar">
        <div class="brand-block">
            <div class="brand-logo">CL</div>
            <div style="line-height:1.2">
                <div style="font-weight:700; font-size:18px;">CAMPUSLink</div>
                <div style="font-size:12px; opacity:0.7;">Student Portal</div>
            </div>
        </div>
        <ul class="nav-list">
            <li><a href="index.php" class="nav-item"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="#" class="nav-item is-active"><i class="fa-solid fa-calendar-days"></i> My Timetable</a></li>
            <li><a href="logout.php" class="nav-item" style="color: #ffcccc;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="header-row">
            <div>
                <h1 style="margin:0; font-size:24px;">Weekly Schedule</h1>
                <p style="margin:5px 0 0; color:#888; font-size:14px;">
                    Group: <strong style="color:var(--purple-base);"><?php echo htmlspecialchars($myGroup); ?></strong>
                </p>
            </div>
            <div style="text-align:right;">
                <div style="font-weight:700;"><?php echo htmlspecialchars($studentName); ?></div>
                <div style="font-size:12px; color:#999;">Student ID: <?php echo htmlspecialchars($userID); ?></div>
            </div>
        </div>

        <div class="timetable-wrapper">
            <table class="timetable">
                <thead>
                    <tr>
                        <th style="width: 80px;">Day</th>
                        <?php for ($h = $startOfDay; $h < $endOfDay; $h++): ?>
                            <th>
                                <?php echo sprintf("%02d:00", $h); ?>
                            </th>
                        <?php endfor; ?>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($daysOfWeek as $day): ?>
                        <tr>
                            <td class="day-column">
                                <?php echo substr($day, 0, 3); ?>
                            </td>

                            <?php 
                            for ($h = $startOfDay; $h < $endOfDay; $h++) {
                                
                                if (isset($scheduleMatrix[$day][$h])) {
                                    $slot = $scheduleMatrix[$day][$h];

                                    // If part of a previous span, skip rendering
                                    if ($slot === 'occupied') continue;

                                    $dur = $slot['duration'];
                                    $info = $slot['info'];
                                    $typeClass = 'is-' . ($info['classType'] ?? 'Lecture');

                                    // Render Merged Cell
                                    echo "<td colspan='$dur' class='time-slot'>";
                                        echo "<div class='class-container $typeClass'>";
                                            $suffix = ($info['classType'] == 'Lecture') ? '(L)' : '(P)';
                                            echo "<span class='subject-code'>{$info['courseID']} $suffix</span>";
                                            echo "<span class='subject-loc'>{$info['facilityID']}</span>";
                                            echo "<span class='subject-prof'>{$info['staffName']}</span>";
                                        echo "</div>";
                                    echo "</td>";

                                } else {
                                    // Empty Cell
                                    echo "<td class='time-slot empty'></td>";
                                }
                            }
                            ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

</div>

</body>
</html>