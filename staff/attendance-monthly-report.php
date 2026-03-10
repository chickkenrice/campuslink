<?php
// File: staff/attendance-monthly-report.php
// Monthly Attendance Summary Report (Per Course)
require_once '../includes/config.php';
session_start();

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    die("Access Denied");
}
prevent_back_button_cache();

if (!isset($_GET['month']) || !isset($_GET['courseID']) || !isset($_GET['programID']) || !isset($_GET['group'])) {
    die("Missing parameters. Required: month (YYYY-MM), courseID, programID, and group");
}

$db = get_db_connection();
$month = $_GET['month'];
$courseID = $_GET['courseID'];
$programID = $_GET['programID'];
$tutGroup = $_GET['group'];
$staffID = $_SESSION['user_id'];

// Validate month format
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    die("Invalid month format. Use YYYY-MM");
}

// 1. Fetch Course & Staff Info (filtered by programID and group)
$metaSql = "SELECT c.courseID, c.courseName, st.staffName, p.programName, s.tutGroup
            FROM class_schedule s
            JOIN course c ON s.courseID = c.courseID
            JOIN staff st ON s.staffID = st.staffID
            JOIN program p ON s.programID = p.programID
            WHERE s.staffID = ? AND s.courseID = ? AND s.programID = ? AND s.tutGroup = ?
            LIMIT 1";
$stmt = $db->prepare($metaSql);
$stmt->bind_param("ssss", $staffID, $courseID, $programID, $tutGroup);
$stmt->execute();
$meta = $stmt->get_result()->fetch_assoc();

if (!$meta) die("Course not found or you don't have access.");

// 2. Get Total Classes Conducted in this Month for this Course + Group
$totalClassesSql = "SELECT COUNT(DISTINCT sess.sessionID) as totalClasses
                    FROM attendance_sessions sess
                    JOIN class_schedule s ON sess.scheduleID = s.scheduleID
                    WHERE s.staffID = ? 
                    AND s.courseID = ?
                    AND s.programID = ?
                    AND s.tutGroup = ?
                    AND sess.sessionDate LIKE ?
                    AND (sess.status = 'CLOSED' OR sess.sessionDate < CURDATE())";
$param = $month . '%';
$stmt2 = $db->prepare($totalClassesSql);
$stmt2->bind_param("sssss", $staffID, $courseID, $programID, $tutGroup, $param);
$stmt2->execute();
$totalClasses = $stmt2->get_result()->fetch_assoc()['totalClasses'];

if ($totalClasses == 0) {
    die("No classes conducted for this course in " . date('F Y', strtotime($month . '-01')));
}

// 3. Get Student Attendance Summary (filtered by programme and group)
$summarySql = "SELECT 
                st.studentID,
                st.studentName,
                COUNT(DISTINCT sess.sessionID) as totalSessions,
                SUM(CASE WHEN a.status IN ('Present', 'Late') THEN 1 ELSE 0 END) as presentCount,
                SUM(CASE WHEN a.status IS NULL OR a.status = 'Absent' THEN 1 ELSE 0 END) as absentCount
               FROM student st
               JOIN class_schedule s ON st.programID = s.programID 
                    AND (st.tutGroup = s.tutGroup OR s.tutGroup = 'Combined')
               JOIN attendance_sessions sess ON sess.scheduleID = s.scheduleID
                    AND sess.sessionDate LIKE ?
                    AND (sess.status = 'CLOSED' OR sess.sessionDate < CURDATE())
               LEFT JOIN attendance a ON a.studentID = st.studentID 
                    AND a.scheduleID = sess.scheduleID 
                    AND a.attendanceDate = sess.sessionDate
               WHERE s.staffID = ? AND s.courseID = ? AND s.programID = ? AND s.tutGroup = ?
               GROUP BY st.studentID, st.studentName
               ORDER BY st.studentName ASC";

$stmt3 = $db->prepare($summarySql);
$stmt3->bind_param("sssss", $param, $staffID, $courseID, $programID, $tutGroup);
$stmt3->execute();
$students = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);

// Get month name
$monthName = date('F', strtotime($month . '-01'));
$year = date('Y', strtotime($month . '-01'));

// Get intake (using September as default academic start)
$intakeMonth = date('n', strtotime($month . '-01'));
$intakeYear = $year;
if ($intakeMonth >= 9) {
    $intake = "Sep $intakeYear";
} else {
    $intake = "Sep " . ($intakeYear - 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Attendance Summary - <?php echo $meta['courseName']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Times+New+Roman:ital,wght@0,400;0,700;1,400&family=Inter:wght@400;600;700&display=swap');

        :root { --primary: #000; --border: #000; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: #525659; 
            margin: 0; 
            padding: 40px; 
            display: flex; 
            justify-content: center;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        
        /* FLOATING ACTION BUTTONS */
        .fab-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .fab {
            background: #8056ff;
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Inter', sans-serif;
            transition: 0.2s;
            font-size: 14px;
        }
        .fab:hover { transform: translateY(-3px); background: #6b46c1; }
        .fab-secondary { background: #1f2937; }
        .fab-secondary:hover { background: #374151; }

        /* THE A4 PAGE */
        .page {
            background: white;
            width: 210mm;
            min-height: 297mm;
            padding: 15mm 20mm;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            position: relative;
            box-sizing: border-box;
        }

        /* HEADER */
        .header { text-align: center; border-bottom: 3px solid #000; padding-bottom: 20px; margin-bottom: 25px; }
        .logo-circle {
            width: 70px; height: 70px; background: #000; color: #fff; border-radius: 50%;
            font-family: 'Times New Roman', serif; font-weight: 700; font-size: 28px;
            display: grid; place-items: center; margin: 0 auto 15px auto;
        }
        .uni-name { font-family: 'Times New Roman', serif; text-transform: uppercase; font-size: 20px; letter-spacing: 1px; font-weight: 700; margin-bottom: 5px; color: #000; }
        .report-title { font-size: 16px; text-transform: uppercase; color: #333; font-weight: 600; }

        /* METADATA BOX */
        .meta-box { border: 1px solid #000; display: flex; margin-bottom: 25px; }
        .meta-col { flex: 1; padding: 10px; border-right: 1px solid #000; }
        .meta-col:last-child { border-right: none; }
        .label { display: block; font-size: 10px; text-transform: uppercase; color: #555; font-weight: 700; margin-bottom: 4px; }
        .value { font-size: 13px; font-weight: 600; color: #000; }

        /* TABLE */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 12px; 
            margin-bottom: 30px; 
        }
        th { 
            background: #e5e5e5; 
            text-align: left; 
            padding: 12px 10px; 
            border: 1px solid #000; 
            font-size: 11px; 
            font-weight: 700;
            color: #000;
            text-transform: uppercase;
        }
        th:first-child { text-align: center; width: 8%; }
        td { 
            padding: 10px; 
            border: 1px solid #000; 
            color: #000; 
        }
        td:first-child { text-align: center; font-weight: 600; }
        tr:nth-child(even) { background: #fafafa; }
        tr:hover { background: #f3f4f6; }
        
        /* CENTER ALIGN NUMERIC COLUMNS */
        td:nth-child(3), td:nth-child(4), td:nth-child(5), td:nth-child(6) { 
            text-align: center; 
            font-weight: 600;
        }
        th:nth-child(3), th:nth-child(4), th:nth-child(5), th:nth-child(6), th:nth-child(7) { 
            text-align: center; 
        }

        /* STATUS STYLES */
        .status-good { 
            color: #065f46; 
            font-weight: 700; 
            background: #d1fae5;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            display: inline-block;
        }
        .status-barred { 
            color: #991b1b; 
            font-weight: 700; 
            background: #fee2e2;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            display: inline-block;
        }

        /* FOOTER */
        .footer { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-end; 
            margin-top: 40px; 
            page-break-inside: avoid; 
        }
        .signature { 
            text-align: center; 
            width: 220px; 
        }
        .sig-line { 
            border-top: 1px solid #000; 
            margin-top: 50px; 
            padding-top: 10px; 
            font-weight: 700; 
            font-size: 13px; 
        }
        .page-footer {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #555;
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid #000;
        }

        @media print {
            body { background: white; padding: 0; }
            .page { box-shadow: none; margin: 0; width: 100%; height: auto; padding: 10mm; }
            .fab-container { display: none; }
            @page { margin: 10mm; }
        }
    </style>
</head>
<body>

    <div class="fab-container">
        <button onclick="window.print()" class="fab">
            <i class="fa-solid fa-print"></i> Print / Save PDF
        </button>
        <button onclick="window.close()" class="fab fab-secondary">
            <i class="fa-solid fa-xmark"></i> Close
        </button>
    </div>

    <div class="page">
        <div class="header">
            <div class="logo-circle">CL</div>
            <div class="uni-name">CampusLink University</div>
            <div class="report-title">Monthly Attendance Summary</div>
        </div>

        <div class="meta-box">
            <div class="meta-col" style="flex: 2;">
                <span class="label">Course</span>
                <span class="value"><?php echo $meta['courseID']; ?> - <?php echo $meta['courseName']; ?></span>
            </div>
            <div class="meta-col" style="flex: 2;">
                <span class="label">Programme</span>
                <span class="value"><?php echo $meta['programName']; ?></span>
            </div>
        </div>
        <div class="meta-box" style="margin-top: -1px;">
            <div class="meta-col">
                <span class="label">Month</span>
                <span class="value"><?php echo $monthName . ' ' . $year; ?></span>
            </div>
            <div class="meta-col">
                <span class="label">Total Classes</span>
                <span class="value"><?php echo $totalClasses; ?> Sessions</span>
            </div>
            <div class="meta-col">
                <span class="label">Group</span>
                <span class="value"><?php echo $meta['tutGroup']; ?></span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Total Classes</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Attendance %</th>
                    <th style="text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($students as $s): 
                    $present = $s['presentCount'];
                    $absent = $s['absentCount'];
                    $total = $s['totalSessions'];
                    $percentage = $total > 0 ? round(($present / $total) * 100) : 0;
                    $status = $percentage >= 80 ? 'Good' : 'Barred';
                    $statusClass = $percentage >= 80 ? 'status-good' : 'status-barred';
                ?>
                <tr>
                    <td><?php echo $s['studentID']; ?></td>
                    <td><?php echo $s['studentName']; ?></td>
                    <td><?php echo $total; ?></td>
                    <td><?php echo $present; ?></td>
                    <td><?php echo $absent; ?></td>
                    <td><?php echo $percentage; ?>%</td>
                    <td style="text-align: center;"><span class="<?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($students)): ?>
                <tr><td colspan="7" style="text-align:center; padding: 30px; color: #6b7280;">No student records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer">
            <div></div>
            <div class="signature">
                <div class="sig-line">
                    <?php echo $meta['staffName']; ?><br>
                    <span style="font-weight: 400; font-size: 11px; color: #555;">Lecturer Signature</span>
                </div>
                <div style="font-size: 10px; color: #666; margin-top: 5px;">Generated: <?php echo date('d-m-Y H:i'); ?></div>
            </div>
        </div>

        <div class="page-footer">
            <span>Page 1 of 1</span>
            <span></span>
        </div>
    </div>

</body>
</html>
