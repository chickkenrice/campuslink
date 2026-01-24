<?php
session_start();
// Check that the path to your config file is correct based on your folder structure
require_once 'includes/config.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$db = get_db_connection();
$id = $_SESSION['user_id'];

/** * CHANGE 1: Use a LEFT JOIN to pull data from both 'student' 
 * and your new 'student_details' table simultaneously.
 */
// UPDATED QUERY: Joins 'student', 'student_details', AND 'program'
$query = "SELECT s.*, 
                 d.dob, d.gender, d.icNo, d.homeAddress, d.corrAddress, d.parentName, d.parentContact,
                 p.programName, p.faculty
          FROM student s 
          LEFT JOIN student_details d ON s.studentID = d.studentID 
          LEFT JOIN program p ON s.programID = p.programID 
          WHERE s.studentID = ?";

$stmt = $db->prepare($query);
$stmt->bind_param("s", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Profile not found.");
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Profile — CAMPUSLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/styles.css"> <style>
        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }
        .profile-side-card {
            background: white;
            padding: 40px 20px;
            border-radius: 26px;
            text-align: center;
            box-shadow: var(--panel-shadow);
            height: fit-content;
        }
        .large-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--purple-tint);
            margin: 0 auto 20px;
            display: grid;
            place-items: center;
            border: 4px solid var(--purple-soft);
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-group {
            background: rgba(249, 247, 255, 0.95);
            padding: 16px 20px;
            border-radius: 18px;
            border: 1px solid rgba(202, 200, 240, 0.8);
        }
        .info-label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .info-value {
            font-weight: 600;
            color: var(--text-main);
            word-break: break-word;
        }
        .full-width {
            grid-column: span 2;
        }
        h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: var(--purple-base);
            font-size: 18px;
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
                        <li>
                            <a href="index.php" class="nav-item" style="text-decoration: none;">
                                <span class="nav-icon"><i class="fa-solid fa-table-columns"></i></span>
                                <span class="nav-label">Dashboard</span>
                            </a>
                        </li>

                        <li>
                            <a href="student-attendance.php" class="nav-item" style="text-decoration: none;">
                                <span class="nav-icon"><i class="fa-solid fa-clock"></i></span>
                                <span class="nav-label">Attendance</span>
                            </a>
                        </li>

                        <li>
                            <a href="student-timetable.php" class="nav-item" style="text-decoration: none;">
                                <span class="nav-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                <span class="nav-label">My Timetable</span>
                            </a>
                        </li>

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
                        
                        <li>
                            <a href="logout.php" class="nav-item" style="text-decoration: none; margin-top: 20px; color: #e74c3c;">
                                <span class="nav-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                                <span class="nav-label">Logout</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </aside>

            <main class="dashboard">
                <header class="dashboard-topbar">
<h1 class="welcome-title" style="color: var(--text-main);">My Profile</h1>
<div class="topbar-right" style="display: flex; gap: 10px;">
                          <a href="edit-profile.php" class="btn">
                              <i class="fa-solid fa-pen-to-square"></i> Edit Profile
                          </a>
                          <a href="index.php" class="btn" style="background: var(--text-muted);">Back to Dashboard</a>
                      </div>
</header>

                <div class="profile-container">
                    <div class="profile-side-card">
                        <div class="large-avatar">
                            <i class="fa-solid fa-user" style="font-size: 50px; color: var(--purple-base);"></i>
                        </div>
                        <h2 style="margin: 0; font-size: 20px;"><?php echo htmlspecialchars($student['studentName']); ?></h2>
                        <p style="color: var(--text-sub); margin-bottom: 15px;"><?php echo htmlspecialchars($student['studentID']); ?></p>
                        <span class="badge-prog" style="background: var(--purple-tint); color: var(--purple-base); padding: 8px 15px; border-radius: 12px; font-weight: 700; font-size: 12px;">
                            <?php echo htmlspecialchars($student['programID']); ?>
                        </span>
                    </div>

                    <div class="announcements-card">
                        <h3>Academic Information</h3>
                        <div class="info-grid">
                            <div class="info-group">
    <div class="info-label">Programme</div>
    <div class="info-value">
        <?php echo htmlspecialchars($student['programName'] ?? 'Not Assigned'); ?>
        <br>
        <small style="color:var(--text-muted);">
            (<?php echo htmlspecialchars($student['faculty'] ?? 'FOCS'); ?>)
        </small>
    </div>
</div>
                            <div class="info-group">
                                <div class="info-label">Tutorial Group</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['tutGroup']); ?></div>
                            </div>
                        </div>

                        <h3>Personal Details</h3>
                        <div class="info-grid">
                            <div class="info-group">
                                <div class="info-label">IC / Passport No</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['icNo'] ?? 'Not Set'); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Gender</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['gender'] ?? 'Not Set'); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Date of Birth</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['dob'] ?? 'Not Set'); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Contact Number</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['contactNo']); ?></div>
                            </div>
                            <div class="info-group full-width">
                                <div class="info-label">Home Address</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($student['homeAddress'] ?? 'Not Set')); ?></div>
                            </div>
                            <div class="info-group full-width">
                                <div class="info-label">Correspondence Address</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($student['corrAddress'] ?? 'Not Set')); ?></div>
                            </div>
                        </div>

                        <h3>Parent / Guardian Information</h3>
                        <div class="info-grid">
                            <div class="info-group">
                                <div class="info-label">Guardian Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['parentName'] ?? 'Not Set'); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Guardian Contact</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['parentContact'] ?? 'Not Set'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>