<?php
session_start();
require_once(__DIR__ . '/includes/config.php');

// 1. SECURITY: Only Admin allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$db = get_db_connection();
$message = '';
$msgType = '';

// =========================================================
// DATABASE WRITE OPERATIONS (This section updates the DB)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    // --- DELETE ACTION ---
    if ($action === 'delete') {
        $id = $_POST['id'];
        // This SQL command REMOVES the row from your database immediately
        $stmt = $db->prepare("DELETE FROM class_schedule WHERE scheduleID = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "Class entry deleted successfully.";
            $msgType = 'success';
        } else {
            $message = "Error deleting class.";
            $msgType = 'error';
        }
    }

    // --- CREATE ACTION ---
    elseif ($action === 'create') {
        // Collect form data
        $courseID = $_POST['courseID'];
        $staffID = $_POST['staffID'];
        $facilityID = $_POST['facilityID'];
        $day = $_POST['day'];
        $startTime = $_POST['startTime'];
        $endTime = $_POST['endTime'];
        $classType = $_POST['classType'];

        // 1. CONFLICT CHECK: Room Occupied?
        $checkSql = "SELECT * FROM class_schedule WHERE facilityID = ? AND day = ? AND (startTime < ? AND endTime > ?)";
        $stmt = $db->prepare($checkSql);
        $stmt->bind_param("ssss", $facilityID, $day, $endTime, $startTime);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $message = "Conflict: That ROOM is already booked!";
            $msgType = 'error';
        } else {
            // 2. CONFLICT CHECK: Lecturer Busy?
            $stmt = $db->prepare("SELECT * FROM class_schedule WHERE staffID = ? AND day = ? AND (startTime < ? AND endTime > ?)");
            $stmt->bind_param("ssss", $staffID, $day, $endTime, $startTime);
            $stmt->execute();

            if ($stmt->get_result()->num_rows > 0) {
                $message = "Conflict: That LECTURER is already teaching!";
                $msgType = 'error';
            } else {
                // 3. NO CONFLICT -> SAVE TO DATABASE
                // This SQL command ADDS the new row to your database immediately
                $insertSql = "INSERT INTO class_schedule (courseID, staffID, facilityID, day, startTime, endTime, classType) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($insertSql);
                $stmt->bind_param("sssssss", $courseID, $staffID, $facilityID, $day, $startTime, $endTime, $classType);
                
                if ($stmt->execute()) {
                    $message = "Class scheduled successfully!";
                    $msgType = 'success';
                } else {
                    $message = "Database Error: " . $db->error;
                    $msgType = 'error';
                }
            }
        }
    }
}

// =========================================================
// FETCH DATA FOR DISPLAY
// =========================================================
$courses = $db->query("SELECT * FROM course ORDER BY courseName");
$facilities = $db->query("SELECT * FROM facility WHERE status = 'Active' ORDER BY facilityName");
$lecturers = $db->query("SELECT * FROM staff ORDER BY staffName");

$scheduleSql = "SELECT s.scheduleID, s.day, s.startTime, s.endTime, s.classType,
                c.courseName, c.courseID, 
                f.facilityName, 
                st.staffName 
                FROM class_schedule s
                JOIN course c ON s.courseID = c.courseID
                JOIN facility f ON s.facilityID = f.facilityID
                JOIN staff st ON s.staffID = st.staffID
                ORDER BY FIELD(s.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), s.startTime";
$scheduleList = $db->query($scheduleSql);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin - Timetable</title>
    <link rel="stylesheet" href="manage-students.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Extra styles for the form */
        .form-card { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .form-group label { display: block; font-weight: 600; font-size: 13px; color: #555; margin-bottom: 5px; }
        .form-group select, .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .btn-add { grid-column: span 2; padding: 12px; background: var(--purple-base); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; margin-top: 10px; }
        .btn-add:hover { background: var(--purple-deep); }
        .msg-box { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; display:flex; gap:10px; align-items:center; }
        .msg-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .msg-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
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
                        <span class="brand-tagline">Admin Console</span>
                    </div>
                </div>
                <nav class="sidebar-nav">
                    <ul>
                        <li><a href="manage-students.php" class="nav-item"><i class="fa-solid fa-users-gear"></i> User Management</a></li>
                        <li><a href="#" class="nav-item is-active"><i class="fa-solid fa-calendar-days"></i> Timetable</a></li>
                        <li><a href="login.php" class="nav-item"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
                    </ul>
                </nav>
            </aside>

            <main class="dashboard">
                <header class="dashboard-topbar">
                    <h1 class="welcome-title">Master Timetable</h1>
                </header>

                <?php if($message): ?>
                    <div class="msg-box <?php echo $msgType == 'success' ? 'msg-success' : 'msg-error'; ?>">
                        <i class="fa-solid <?php echo $msgType == 'success' ? 'fa-check-circle' : 'fa-triangle-exclamation'; ?>"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <section class="form-card">
                    <h3 style="margin-top:0; color:var(--purple-base);"><i class="fa-solid fa-plus-circle"></i> Add Class Entry</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Course</label>
                                <select name="courseID" required>
                                    <option value="" disabled selected>Select Course...</option>
                                    <?php while($c = $courses->fetch_assoc()): ?>
                                        <option value="<?php echo $c['courseID']; ?>"><?php echo $c['courseID'] . " - " . $c['courseName']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Lecturer</label>
                                <select name="staffID" required>
                                    <option value="" disabled selected>Select Lecturer...</option>
                                    <?php while($s = $lecturers->fetch_assoc()): ?>
                                        <option value="<?php echo $s['staffID']; ?>"><?php echo $s['staffName']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Venue</label>
                                <select name="facilityID" required>
                                    <option value="" disabled selected>Select Room...</option>
                                    <?php while($f = $facilities->fetch_assoc()): ?>
                                        <option value="<?php echo $f['facilityID']; ?>"><?php echo $f['facilityName']; ?> (<?php echo $f['type']; ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Type</label>
                                <select name="classType" required>
                                    <option>Lecture</option><option>Tutorial</option><option>Practical</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Day</label>
                                <select name="day" required>
                                    <option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option>
                                </select>
                            </div>
                            <div class="form-group" style="display:flex; gap:10px;">
                                <div style="flex:1;">
                                    <label>Start Time</label>
                                    <input type="time" name="startTime" required>
                                </div>
                                <div style="flex:1;">
                                    <label>End Time</label>
                                    <input type="time" name="endTime" required>
                                </div>
                            </div>
                            <button type="submit" class="btn-add">Add to Schedule</button>
                        </div>
                    </form>
                </section>

                <section class="announcements-card">
                    <div class="table-container">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Course</th>
                                    <th>Type</th>
                                    <th>Venue</th>
                                    <th>Lecturer</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($scheduleList->num_rows > 0): ?>
                                    <?php while($row = $scheduleList->fetch_assoc()): ?>
                                    <tr>
                                        <td><span class="badge-prog"><?php echo $row['day']; ?></span></td>
                                        <td style="font-weight:600;"><?php echo substr($row['startTime'], 0, 5) . ' - ' . substr($row['endTime'], 0, 5); ?></td>
                                        <td><strong><?php echo $row['courseID']; ?></strong><br><small><?php echo $row['courseName']; ?></small></td>
                                        <td><?php echo $row['classType']; ?></td>
                                        <td><?php echo $row['facilityName']; ?></td>
                                        <td><?php echo $row['staffName']; ?></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Delete this class?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $row['scheduleID']; ?>">
                                                <button type="submit" class="action-btn btn-delete"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" style="text-align:center; padding:30px;">No classes scheduled.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>