<?php
session_start();
require_once '../includes/config.php';

// Security: Check if logged in
if (!validate_session()) {
    header("Location: login.php");
    exit;
}

$db = get_db_connection();
$studentID = $_SESSION['user_id'];

// 1. UPDATED QUERY: Fetch program AND student details for the header
$stmt = $db->prepare("SELECT s.programID, s.studentName, s.studentImage, p.programName, p.faculty 
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

$studentName = $progData['studentName']; 
$progID = $progData['programID'];     
$progName = $progData['programName']; 
$faculty = $progData['faculty'];
$studentImage = $progData['studentImage'] ?? null;      

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
    <link rel="icon" type="image/png" href="../favicon2.png">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Programme Structure - CAMPUSLink</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/programme.css">
</head>
<body>

<div class="app-bg">
    <div class="main-card">
        
        <?php $currentPage = 'programme-structure'; include __DIR__ . '/../includes/studentMaster.php'; ?>

        <main class="dashboard">
            <header class="dashboard-topbar">
                <div class="topbar-right">
                    <?php include __DIR__ . '/../includes/notificationBell.php'; ?>
                    <div class="user-card">
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($studentName); ?></span>
                            <span class="user-role">Student</span>
                        </div>
                        <a href="student-profile.php" class="profile-pic" title="View Profile">
                            <?php if (!empty($studentImage) && $studentImage !== 'default_avatar.png'): ?>
                                <img src="../uploads/profiles/<?php echo htmlspecialchars($studentImage); ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <i class="fa-solid fa-user"></i>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </header>

            <section class="welcome-card" style="margin-bottom: 28px;">
                <div class="welcome-details">
                    <h1 class="welcome-title">Programme Structure</h1>
                    <div class="welcome-meta">
                        <span class="welcome-badge"><?php echo htmlspecialchars($progID); ?></span>
                        <span class="welcome-meta-text"><?php echo htmlspecialchars($progName); ?></span>
                        <span class="welcome-meta-sep">·</span>
                        <span class="welcome-meta-sub"><i class="fa-solid fa-building-columns"></i> <?php echo htmlspecialchars($faculty); ?></span>
                    </div>
                </div>
            </section>

            <div class="structure-container">
                <?php if (empty($structure)): ?>
                    <p style="text-align:center; color:#999; padding: 40px;">No course structure data available.</p>
                <?php else: ?>

                    <?php foreach ($structure as $year => $sems): ?>
                        <div class="year-section">
                            <div class="year-header">Year <?php echo $year; ?></div>
                            
                            <div class="semesters-grid">
                                <?php foreach ($sems as $sem => $courses): 
                                    // Logic for credits and electives
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

                                    $techSlots = 1;
                                    if ($year == 3 && $sem == 2) {
                                        $techSlots = 2;
                                    }

                                    $semTotal = 0;
                                    foreach ($coreCourses as $c) $semTotal += $c['creditHours'];
                                    
                                    $techCredit = 0;
                                    if (!empty($techElectives)) {
                                        $techCredit = $techElectives[0]['creditHours'];
                                        $semTotal += ($techCredit * $techSlots);
                                    }

                                    $mpuCredit = 0;
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
            
            <?php include __DIR__ . '/../includes/footer.php'; ?>
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
function toRoman($num) {
    $map = [1 => 'I', 2 => 'II', 3 => 'III'];
    return $map[$num] ?? $num;
}
?>