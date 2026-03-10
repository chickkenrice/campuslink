<?php
// File: staff/assignment-report.php
require_once '../includes/config.php';
session_start();

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    die("Access Denied");
}
prevent_back_button_cache();

if (!isset($_GET['assignmentID'])) {
    die("Missing assignment ID.");
}

$db = get_db_connection();
$assignmentID = intval($_GET['assignmentID']);
$staffID = $_SESSION['user_id'];

// 1. Fetch Assignment Metadata
$metaSql = "SELECT a.assignmentID, a.courseID, a.tutGroup, a.title, a.description, a.deadline, 
                   c.courseName, st.staffName
            FROM assignments a
            JOIN course c ON a.courseID = c.courseID
            JOIN staff st ON a.staffID = st.staffID
            WHERE a.assignmentID = ? AND a.staffID = ?";
$stmt = $db->prepare($metaSql);
$stmt->bind_param("is", $assignmentID, $staffID);
$stmt->execute();
$meta = $stmt->get_result()->fetch_assoc();

if (!$meta) die("Assignment not found or access denied.");

// 2. Get program name from students enrolled in this course
$progNameSql = "SELECT DISTINCT p.programID, p.programName 
                FROM student st
                JOIN program p ON st.programID = p.programID
                JOIN program_course pc ON st.programID = pc.programID 
                    AND st.currentYear = pc.year 
                    AND st.currentSemester = pc.semester
                WHERE pc.courseID = ?
                AND (? = 'All' OR ? = 'Combined' OR st.tutGroup = ?)
                LIMIT 1";
$stmtPN = $db->prepare($progNameSql);
$stmtPN->bind_param("ssss", $meta['courseID'], $meta['tutGroup'], $meta['tutGroup'], $meta['tutGroup']);
$stmtPN->execute();
$progNameResult = $stmtPN->get_result()->fetch_assoc();
$programName = $progNameResult ? $progNameResult['programName'] : 'Unknown Program';
$programID = $progNameResult ? $progNameResult['programID'] : '';

// 3. Fetch All Students enrolled in this course with LEFT JOIN to Submissions
// Uses program_course to properly identify students taking this course
$sql = "SELECT DISTINCT st.studentID, st.studentName, 
               sub.submittedAt, sub.grade, sub.status as subStatus, sub.feedback
        FROM student st
        JOIN program_course pc ON st.programID = pc.programID 
            AND st.currentYear = pc.year 
            AND st.currentSemester = pc.semester
        LEFT JOIN submissions sub ON st.studentID = sub.studentID AND sub.assignmentID = ?
        WHERE pc.courseID = ?
        AND (? = 'All' OR ? = 'Combined' OR st.tutGroup = ?)
        ORDER BY st.studentName ASC";
$stmt2 = $db->prepare($sql);
$stmt2->bind_param("issss", $assignmentID, $meta['courseID'], $meta['tutGroup'], $meta['tutGroup'], $meta['tutGroup']);
$stmt2->execute();
$students = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// 5. Calculate Statistics
$total = count($students);
$submitted = 0;
$onTime = 0;
$late = 0;
$missing = 0;
$graded = 0;

$deadline = strtotime($meta['deadline']);

foreach($students as $s) {
    if ($s['submittedAt']) {
        $submitted++;
        $submitTime = strtotime($s['submittedAt']);
        if ($submitTime <= $deadline) {
            $onTime++;
        } else {
            $late++;
        }
        if ($s['subStatus'] == 'Graded') {
            $graded++;
        }
    } else {
        $missing++;
    }
}

// Helper function for letter grade
function getLetterGrade($marks) {
    if ($marks === null || $marks === '') return '-';
    $marks = intval($marks);
    if ($marks >= 80) return 'A';
    if ($marks >= 70) return 'B';
    if ($marks >= 60) return 'C';
    if ($marks >= 50) return 'D';
    if ($marks >= 40) return 'E';
    return 'F';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assignment Report - <?php echo htmlspecialchars($meta['title']); ?></title>
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
        table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 30px; }
        th { background: #e5e5e5; text-align: left; padding: 10px 6px; border: 1px solid #000; text-transform: uppercase; font-size: 10px; }
        td { padding: 7px 6px; border: 1px solid #000; color: #000; }
        
        /* STATUS STYLES */
        .st-ontime { color: #065f46; font-weight: 700; }
        .st-late { color: #b45309; font-weight: 700; }
        .st-missing { color: #991b1b; font-weight: 700; }
        
        /* GRADE STYLES */
        .grade-a { color: #065f46; font-weight: 700; }
        .grade-b { color: #0369a1; font-weight: 700; }
        .grade-c { color: #6b7280; font-weight: 700; }
        .grade-d { color: #b45309; font-weight: 700; }
        .grade-e { color: #dc2626; font-weight: 700; }
        .grade-f { color: #991b1b; font-weight: 700; }

        /* FOOTER */
        .footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 40px; page-break-inside: avoid; }
        .summary { border: 1px solid #000; padding: 15px; width: 200px; font-size: 12px; line-height: 1.6; }
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
            <div class="report-title">Assignment Submission Report</div>
        </div>

        <div class="meta-box">
            <div class="meta-col" style="flex: 2;">
                <span class="label">Course</span>
                <span class="value"><?php echo htmlspecialchars($meta['courseID']); ?> - <?php echo htmlspecialchars($meta['courseName']); ?></span>
            </div>
            <div class="meta-col" style="flex: 2;">
                <span class="label">Programme</span>
                <span class="value"><?php echo htmlspecialchars($programName); ?></span>
            </div>
        </div>
        <div class="meta-box" style="margin-top: -1px;">
            <div class="meta-col" style="flex: 2;">
                <span class="label">Assignment</span>
                <span class="value"><?php echo htmlspecialchars($meta['title']); ?></span>
            </div>
            <div class="meta-col">
                <span class="label">Deadline</span>
                <span class="value"><?php echo date('d M Y, h:i A', strtotime($meta['deadline'])); ?></span>
            </div>
            <div class="meta-col">
                <span class="label">Group</span>
                <span class="value"><?php echo htmlspecialchars($meta['tutGroup']); ?></span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">No.</th>
                    <th style="width: 14%;">Student ID</th>
                    <th style="width: 28%;">Student Name</th>
                    <th style="width: 18%; text-align: center;">Submitted At</th>
                    <th style="width: 12%; text-align: center;">Status</th>
                    <th style="width: 10%; text-align: center;">Marks</th>
                    <th style="width: 8%; text-align: center;">Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php $count = 1; foreach($students as $s): 
                    // Determine status
                    if (!$s['submittedAt']) {
                        $status = 'MISSING';
                        $statusClass = 'st-missing';
                        $submitDisplay = '-';
                    } else {
                        $submitTime = strtotime($s['submittedAt']);
                        if ($submitTime <= $deadline) {
                            $status = 'ON TIME';
                            $statusClass = 'st-ontime';
                        } else {
                            $status = 'LATE';
                            $statusClass = 'st-late';
                        }
                        $submitDisplay = date('d M Y, h:i A', $submitTime);
                    }
                    
                    // Determine grade
                    $marks = $s['grade'];
                    $letterGrade = getLetterGrade($marks);
                    $gradeClass = 'grade-' . strtolower($letterGrade);
                    $marksDisplay = ($marks !== null && $marks !== '') ? $marks : '-';
                ?>
                <tr>
                    <td style="text-align: center;"><?php echo $count++; ?></td>
                    <td><?php echo htmlspecialchars($s['studentID']); ?></td>
                    <td><?php echo htmlspecialchars($s['studentName']); ?></td>
                    <td style="text-align: center; font-size: 10px;"><?php echo $submitDisplay; ?></td>
                    <td style="text-align: center;" class="<?php echo $statusClass; ?>"><?php echo $status; ?></td>
                    <td style="text-align: center;"><?php echo $marksDisplay; ?></td>
                    <td style="text-align: center;" class="<?php echo $gradeClass; ?>"><?php echo $letterGrade; ?></td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($students)): ?>
                <tr><td colspan="7" style="text-align:center; padding: 20px;">No students found for this assignment group.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer">
            <div class="summary">
                <strong>SUBMISSION SUMMARY</strong><br>
                Total Students: <?php echo $total; ?><br>
                Submitted: <?php echo $submitted; ?><br>
                &nbsp;&nbsp;• On Time: <?php echo $onTime; ?><br>
                &nbsp;&nbsp;• Late: <?php echo $late; ?><br>
                Missing: <?php echo $missing; ?><br>
                Graded: <?php echo $graded; ?>
            </div>
            
            <div class="signature">
                <div style="height: 40px;"></div>
                <div class="sig-line">
                    <?php echo htmlspecialchars($meta['staffName']); ?><br>
                    <span style="font-weight: 400; font-size: 11px;">Lecturer Signature</span>
                </div>
                <div style="font-size: 10px; color: #666; margin-top: 5px;">Generated: <?php echo date('d-m-Y H:i'); ?></div>
            </div>
        </div>
    </div>

</body>
</html>
