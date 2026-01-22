<?php
session_start();
require_once 'includes/config.php';

// Security: Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ... inside programme-structure.php ...

$db = get_db_connection();
$studentID = $_SESSION['user_id'];

// 1. UPDATED QUERY: Fetch programID directly from the student table
// We also JOIN the program table to get the name immediately
// FIX: Changed 's.programID' to 's.programme' to match your database table
$stmt = $db->prepare("SELECT s.programme, p.programName, p.faculty 
                      FROM student s 
                      JOIN program p ON s.programme = p.programID 
                      WHERE s.studentID = ?");
$stmt->execute();
$result = $stmt->get_result();
$progData = $result->fetch_assoc();

if (!$progData) {
    die("Programme data not found. Please contact admin.");
}

// Now you have clean variables to use in your HTML
// FIX: Use 'programme' key
$progID = $progData['programme'];     // "RSD"
$progName = $progData['programName']; // "Bachelor of IT..."
$faculty = $progData['faculty'];      // "FOCS"

// ... continue with fetching structure ...
// 3. Fetch Structure (Year 1, 2, 3)
$sql = "SELECT pc.year, pc.semester, c.courseID, c.courseName, c.creditHours, pc.type
        FROM program_course pc
        JOIN course c ON pc.courseID = c.courseID
        WHERE pc.programID = ?
        ORDER BY pc.year ASC, pc.semester ASC, c.courseID ASC";

$stmt = $db->prepare($sql);
$stmt->bind_param("s", $progID);
$stmt->execute();
$structure = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Group data for easier display
$grouped = [];
foreach ($structure as $row) {
    $grouped[$row['year']][$row['semester']][] = $row;
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Programme Structure — <?php echo htmlspecialchars($progID); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .structure-container {
            display: flex;
            flex-direction: column;
            gap: 40px; /* Increased gap for better separation */
            padding-bottom: 60px;
        }
        /* White Card for each Year */
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
        /* Grid Layout for Semesters */
        .semesters-grid {
            display: grid;
            /* Creates columns that wrap automatically */
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); 
            gap: 25px;
            align-items: stretch; /* FIX 1: Forces all boxes in a row to match height */
        }
        .semester-block {
            background: #fff;
            display: flex; /* FIX 2: Makes this a flex container */
            flex-direction: column; /* Stacks children vertically */
            height: 100%; /* Takes full height provided by grid */
        }
        .sem-title {
            font-size: 13px;
            font-weight: 700;
            color: #555;
            background: #f9fafb;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-bottom: none;
            border-radius: 8px 8px 0 0; /* Rounded top corners */
        }
        /* Table Styling */
        .course-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            border: 1px solid #e0e0e0;
            flex-grow: 1; /* FIX 3: Table expands to fill empty space */
        }
        .course-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f0f0f0;
            color: #444;
            vertical-align: middle;
        }
        .code-col {
            font-weight: 600;
            color: #666;
            width: 80px;
        }
        .name-col {
            font-weight: 500;
        }
        .credit-col {
            text-align: center;
            width: 40px;
            color: #888;
        }
        .total-row {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 12px;
            background: #fafafa;
            border: 1px solid #e0e0e0;
            border-top: none;
            font-size: 12px;
            font-weight: 700;
            color: #333;
            margin-top: auto; /* FIX 4: Ensures footer stays at the bottom */
            border-radius: 0 0 8px 8px; /* Rounded bottom corners */
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
                    </div>
                </div>
                <nav class="sidebar-nav">
                    <ul>
                        <li><a href="index.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-house"></i></span> Dashboard</a></li>
                        <li><a href="#" class="nav-item is-active"><span class="nav-icon"><i class="fa-solid fa-layer-group"></i></span> Programme</a></li>
                    </ul>
                </nav>
            </aside>

            <main class="dashboard">
                <header class="dashboard-topbar">
                    <div>
                        <h1 class="welcome-title">Programme Structure</h1>
                        <p style="color: var(--text-sub); font-size: 14px; margin-top:5px;">
                            <?php echo htmlspecialchars($progName); ?>
                        </p>
                    </div>
                    <div class="topbar-right">
                        <div class="user-card">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                            <span class="user-status">Student</span>
                        </div>
                    </div>
                </header>

                <div class="structure-container">
                    <?php if (empty($grouped)): ?>
                        <div class="year-section">
                            <p style="text-align:center; color:var(--text-muted);">
                                No structure data found for <?php echo htmlspecialchars($progID); ?>.
                            </p>
                        </div>
                    <?php else: ?>
                        
                        <?php foreach ($grouped as $year => $semesters): ?>
                            <section class="year-section">
                                <div class="year-header">Year <?php echo $year; ?></div>
                                <div class="semesters-grid">
                                    
                                    <?php foreach ($semesters as $sem => $courses): 
                                        $semTotal = 0;
                                    ?>
                                        <div class="semester-block">
                                            <div class="sem-title">Year <?php echo $year; ?> Semester <?php echo $sem; ?></div>
                                            <table class="course-table">
                                                <tbody>
                                                    <?php foreach ($courses as $c): 
                                                        $semTotal += $c['creditHours'];
                                                    ?>
                                                    <tr>
                                                        <td class="code-col"><?php echo $c['courseID']; ?></td>
                                                        <td class="name-col"><?php echo $c['courseName']; ?></td>
                                                        <td class="credit-col"><?php echo $c['creditHours']; ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                            <div class="total-row">
                                                <span>Total Credit Hour(s) :</span>
                                                <span><?php echo $semTotal; ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                </div>
                            </section>
                        <?php endforeach; ?>

                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>