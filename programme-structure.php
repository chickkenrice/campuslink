<?php
session_start();
require_once 'includes/config.php';

// Security: Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = get_db_connection();
$studentID = $_SESSION['user_id'];

// 1. UPDATED QUERY: Fetch program AND student details for the header
$stmt = $db->prepare("SELECT s.programID, s.studentName, p.programName, p.faculty 
                      FROM student s 
                      JOIN program p ON s.programID = p.programID 
                      WHERE s.studentID = ?");
$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();
$progData = $result->fetch_assoc();

if (!$progData) {
    die("Programme data not found. Please contact admin.");
}

$studentName = $progData['studentName']; // For Top Bar
$progID = $progData['programID'];     
$progName = $progData['programName']; 
$faculty = $progData['faculty'];      

// 2. Fetch Structure
$sql = "SELECT pc.year, pc.semester, c.courseID, c.courseName, c.creditHours, pc.type
        FROM program_course pc
        JOIN course c ON pc.courseID = c.courseID
        WHERE pc.programID = ?
        ORDER BY pc.year ASC, pc.semester ASC, c.courseID ASC";

$stmt = $db->prepare($sql);
$stmt->bind_param("s", $progID);
$stmt->execute();
$result = $stmt->get_result();

$structure = [];
while ($row = $result->fetch_assoc()) {
    $y = $row['year'];
    $s = $row['semester'];
    $structure[$y][$s][] = $row;
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Programme Structure - CAMPUSLink</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/timetable.css">

    <style>
        .dashboard {
            height: 100vh;
            overflow-y: auto;
            padding-bottom: 80px;
        }

        .structure-container {
            display: flex;
            flex-direction: column;
            gap: 40px;
            padding-bottom: 60px;
        }
        .year-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #eee;
        }
        .year-header {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        .semesters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); 
            gap: 25px;
            align-items: stretch; 
        }
        .semester-block {
            background: #fff;
            display: flex;
            flex-direction: column;
            height: 100%;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        .sem-title {
            font-size: 13px;
            font-weight: 700;
            color: #555;
            background: #f9fafb;
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        .course-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            flex-grow: 1; 
        }
        .course-table td {
            padding: 10px 15px;
            border-bottom: 1px solid #f0f0f0;
            color: #444;
            vertical-align: middle;
        }
        .code-col { font-weight: 600; color: #666; width: 85px; }
        .name-col { font-weight: 500; }
        .credit-col { text-align: center; width: 50px; color: #888; }
        
        .elective-section {
            background: #fffbf0;
            padding: 15px;
            border-top: 1px dashed #e0e0e0;
        }
        .elective-header {
            font-size: 11px;
            font-weight: 700;
            color: #d97706;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .elective-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .elective-item {
            font-size: 12px;
            color: #555;
            margin-bottom: 4px;
            display: flex;
            gap: 8px;
        }
        .elective-code {
            font-weight: 600;
            color: #d97706;
            min-width: 70px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 15px;
            background: #fafafa;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            font-weight: 700;
            color: #333;
            margin-top: auto; 
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
                    <span class="brand-tagline">Student Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="index.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-table-columns"></i></span> Dashboard</a></li>
                    <li><a href="student-timetable.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-calendar-days"></i></span> My Timetable</a></li>
                    <li class="nav-group is-open" data-expandable>
                        <button class="nav-item nav-toggle" type="button" data-target="programme-panel" aria-expanded="true">
                            <span class="nav-icon"><i class="fa-solid fa-clipboard-list"></i></span>
                            <span class="nav-label">Programme</span>
                            <span class="nav-chevron"></span>
                        </button>
                        <div class="nav-submenu" id="programme-panel">
                            <a href="programme-structure.php" class="nav-subitem active" style="color: var(--purple-base); font-weight: 600;">Programme Structure</a>
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
                    <h1 class="welcome-title">Programme Structure</h1>
                    <p style="color: var(--text-sub); font-size: 14px; margin-top: 5px;">
                        <?php echo htmlspecialchars($progID . " - " . $progName); ?>
                        <span style="background: #e0e7ff; color: #4338ca; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 10px;">
                            <?php echo htmlspecialchars($faculty); ?>
                        </span>
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

            <div class="structure-container">
                <?php if (empty($structure)): ?>
                    <p style="text-align:center; color:#999; padding: 40px;">No course structure data available.</p>
                <?php else: ?>

                    <?php foreach ($structure as $year => $sems): ?>
                        <div class="year-section">
                            <div class="year-header">Year <?php echo $year; ?></div>
                            
                            <div class="semesters-grid">
                                <?php foreach ($sems as $sem => $courses): 
                                    // 1. Separate Core and Electives
                                    $coreCourses = [];
                                    $techElectives = []; 
                                    $mpuElectives = [];  

                                    foreach ($courses as $c) {
                                        if (stripos($c['type'], 'Elective') !== false) {
                                            if (strpos($c['courseID'], 'MPU') === 0) {
                                                $mpuElectives[] = $c;
                                            } else {
                                                $techElectives[] = $c;
                                            }
                                        } else {
                                            $coreCourses[] = $c;
                                        }
                                    }

                                    // 2. Logic to determine number of Tech Elective slots
                                    // Default is 1, but for Year 3 Sem 2, we force 2 slots
                                    $techSlots = 1;
                                    if ($year == 3 && $sem == 2) {
                                        $techSlots = 2;
                                    }

                                    // 3. Calculate Total Credits
                                    $semTotal = 0;
                                    foreach ($coreCourses as $c) $semTotal += $c['creditHours'];
                                    
                                    if (!empty($techElectives)) {
                                        $techCredit = $techElectives[0]['creditHours'];
                                        $semTotal += ($techCredit * $techSlots);
                                    }

                                    if (!empty($mpuElectives)) {
                                        $mpuCredit = $mpuElectives[0]['creditHours'];
                                        $semTotal += $mpuCredit;
                                    }
                                ?>
                                    <div class="semester-block">
                                        <div class="sem-title">
                                            Year <?php echo $year; ?> Semester <?php echo $sem; ?>
                                        </div>
                                        
                                        <table class="course-table">
                                            <tbody>
                                                <?php foreach ($coreCourses as $c): ?>
                                                <tr>
                                                    <td class="code-col"><?php echo $c['courseID']; ?></td>
                                                    <td class="name-col"><?php echo $c['courseName']; ?></td>
                                                    <td class="credit-col"><?php echo $c['creditHours']; ?></td>
                                                </tr>
                                                <?php endforeach; ?>

                                                <?php if (!empty($techElectives)): ?>
                                                    <?php for($i=1; $i<=$techSlots; $i++): ?>
                                                    <tr style="background-color: #fffbf0;">
                                                        <td class="code-col" style="color: #d97706;">—</td>
                                                        <td class="name-col" style="font-style: italic; color: #555;">
                                                            Elective <?php echo ($techSlots > 1) ? toRoman($i) : 'I'; ?>
                                                        </td>
                                                        <td class="credit-col"><?php echo $techCredit; ?></td>
                                                    </tr>
                                                    <?php endfor; ?>
                                                <?php endif; ?>

                                                <?php if (!empty($mpuElectives)): ?>
                                                <tr style="background-color: #f0fdf4;">
                                                    <td class="code-col" style="color: #16a34a;">—</td>
                                                    <td class="name-col" style="font-style: italic; color: #555;">Elective (MPU)</td>
                                                    <td class="credit-col"><?php echo $mpuCredit; ?></td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>

                                        <?php if (!empty($techElectives)): ?>
                                        <div class="elective-section">
                                            <div class="elective-header">Elective Options:</div>
                                            <ul class="elective-list">
                                                <?php foreach ($techElectives as $elec): ?>
                                                    <li class="elective-item">
                                                        <span class="elective-code"><?php echo $elec['courseID']; ?></span>
                                                        <span><?php echo $elec['courseName']; ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($mpuElectives)): ?>
                                        <div class="elective-section" style="background: #f0fdf4;">
                                            <div class="elective-header" style="color: #16a34a;">MPU Options:</div>
                                            <ul class="elective-list">
                                                <?php foreach ($mpuElectives as $elec): ?>
                                                    <li class="elective-item">
                                                        <span class="elective-code" style="color: #16a34a;"><?php echo $elec['courseID']; ?></span>
                                                        <span><?php echo $elec['courseName']; ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        <?php endif; ?>

                                        <div class="total-row">
                                            <span>Total Credit Hour(s)</span>
                                            <span><?php echo $semTotal; ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>
            </div>
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

<?php
// Helper to convert number to Roman numeral
function toRoman($num) {
    $map = [1 => 'I', 2 => 'II', 3 => 'III'];
    return $map[$num] ?? $num;
}
?>