<?php
// File: staff/attendance-report.php
require_once '../includes/config.php';
session_start();

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    die("Access Denied");
}

if (!isset($_GET['scheduleID']) || !isset($_GET['date'])) {
    die("Missing parameters.");
}

$db = get_db_connection();
$scheduleID = $_GET['scheduleID'];
$date = $_GET['date'];
$staffID = $_SESSION['user_id'];

// 1. Fetch Class Metadata
$metaSql = "SELECT s.courseID, c.courseName, s.tutGroup, s.startTime, s.endTime, f.facilityName, st.staffName, s.programID, p.programName
            FROM class_schedule s
            JOIN course c ON s.courseID = c.courseID
            JOIN facility f ON s.facilityID = f.facilityID
            JOIN staff st ON s.staffID = st.staffID
            JOIN program p ON s.programID = p.programID
            WHERE s.scheduleID = ?";
$stmt = $db->prepare($metaSql);
$stmt->bind_param("i", $scheduleID);
$stmt->execute();
$meta = $stmt->get_result()->fetch_assoc();

if (!$meta) die("Class not found.");

// 2. Fetch Student List & Attendance Status
// Logic: Get all students enrolled in this Program + Group, then JOIN with attendance table to check status
$sql = "SELECT st.studentName, st.studentID, 
        COALESCE(a.status, 'Absent') as status, 
        a.scanTime
        FROM student st
        LEFT JOIN attendance a ON st.studentID = a.studentID 
            AND a.scheduleID = ? 
            AND a.attendanceDate = ?
        WHERE st.programID = ? 
        AND (st.tutGroup = ? OR 'Combined' = ? OR ? = 'Combined')
        ORDER BY st.studentName ASC";

$stmt2 = $db->prepare($sql);
$stmt2->bind_param("isssss", $scheduleID, $date, $meta['programID'], $meta['tutGroup'], $meta['tutGroup'], $meta['tutGroup']);
$stmt2->execute();
$students = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate Stats
$total = count($students);
$present = 0;
$absent = 0;
$late = 0;

foreach($students as $s) {
    if ($s['status'] == 'Present') $present++;
    elseif ($s['status'] == 'Late') $late++;
    else $absent++; // Absent or null
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Report - <?php echo $date; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Times+New+Roman:ital,wght@0,400;0,700;1,400&family=Inter:wght@400;600&display=swap');

        :root { --primary: #000; --border: #000; }
        
        body { font-family: 'Inter', sans-serif; background: #525659; margin: 0; padding: 40px; display: flex; justify-content: center; }
        
        /* THE A4 PAGE */
        .page {
            background: white;
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
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
        table { width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 30px; }
        th { background: #e5e5e5; text-align: left; padding: 10px 8px; border: 1px solid #000; text-transform: uppercase; font-size: 11px; }
        td { padding: 8px; border: 1px solid #000; color: #000; }
        
        /* STATUS STYLES */
        .st-present { color: #065f46; font-weight: 700; }
        .st-absent { color: #991b1b; font-weight: 700; }
        .st-late { color: #b45309; font-weight: 700; }

        /* FOOTER */
        .footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 40px; page-break-inside: avoid; }
        .summary { border: 1px solid #000; padding: 15px; width: 180px; font-size: 12px; line-height: 1.6; }
        .signature { text-align: center; width: 220px; }
        .sig-line { border-top: 1px solid #000; margin-top: 40px; padding-top: 8px; font-weight: 700; font-size: 13px; }

        /* FLOATING PRINT BUTTON */
        .fab-print {
            position: fixed; bottom: 30px; right: 30px;
            background: #8056ff; color: white;
            padding: 15px 25px; border-radius: 50px;
            font-weight: 600; cursor: pointer; border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            display: flex; align-items: center; gap: 10px;
            font-family: 'Inter', sans-serif; transition: 0.2s;
        }
        .fab-print:hover { transform: translateY(-3px); background: #6b46c1; }

        @media print {
            body { background: white; padding: 0; }
            .page { box-shadow: none; margin: 0; width: 100%; height: auto; }
            .fab-print { display: none; }
            @page { margin: 10mm; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="fab-print">
        <i class="fa-solid fa-print"></i> Print / Save PDF
    </button>

    <div class="page">
        <div class="header">
            <div class="logo-circle">CL</div>
            <div class="uni-name">CampusLink University</div>
            <div class="report-title">Class Attendance Report</div>
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
                <span class="label">Date</span>
                <span class="value"><?php echo date('d M Y', strtotime($date)); ?></span>
            </div>
            <div class="meta-col">
                <span class="label">Time</span>
                <span class="value"><?php echo substr($meta['startTime'],0,5) . ' - ' . substr($meta['endTime'],0,5); ?></span>
            </div>
            <div class="meta-col">
                <span class="label">Group</span>
                <span class="value"><?php echo $meta['tutGroup']; ?></span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">No.</th>
                    <th style="width: 15%;">Student ID</th>
                    <th style="width: 40%;">Student Name</th>
                    <th style="width: 20%; text-align: center;">Time In</th>
                    <th style="width: 20%; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php $count = 1; foreach($students as $s): 
                    $time = $s['scanTime'] ? date('h:i A', strtotime($s['scanTime'])) : '-';
                    $statusClass = 'st-present';
                    if($s['status'] == 'Absent') $statusClass = 'st-absent';
                    if($s['status'] == 'Late') $statusClass = 'st-late';
                ?>
                <tr>
                    <td style="text-align: center;"><?php echo $count++; ?></td>
                    <td><?php echo $s['studentID']; ?></td>
                    <td><?php echo $s['studentName']; ?></td>
                    <td style="text-align: center;"><?php echo $time; ?></td>
                    <td style="text-align: center;" class="<?php echo $statusClass; ?>"><?php echo strtoupper($s['status']); ?></td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($students)): ?>
                <tr><td colspan="5" style="text-align:center; padding: 20px;">No students found for this class.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer">
            <div class="summary">
                <strong>ATTENDANCE SUMMARY</strong><br>
                Total Students: <?php echo $total; ?><br>
                Present: <?php echo $present; ?><br>
                Late: <?php echo $late; ?><br>
                Absent: <?php echo $absent; ?>
            </div>
            
            <div class="signature">
                <div style="height: 40px;"></div> <div class="sig-line">
                    <?php echo $meta['staffName']; ?><br>
                    <span style="font-weight: 400; font-size: 11px;">Lecturer Signature</span>
                </div>
                <div style="font-size: 10px; color: #666; margin-top: 5px;">Generated: <?php echo date('d-m-Y H:i'); ?></div>
            </div>
        </div>
    </div>

</body>
</html>