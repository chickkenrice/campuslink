<?php
// File: staff/staff-timetable-report.php
// Official Teaching Schedule Report for Staff
require_once '../includes/config.php';
session_start();

// Security Check
if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['role'], 'staff') !== 0) {
    die("Access Denied");
}
prevent_back_button_cache();

$userID = $_SESSION['user_id'];
$db = get_db_connection();

// Get staff info
$stmt = $db->prepare("SELECT staffID, staffName, staffType, email FROM staff WHERE staffID = ? OR userID = ?");
$stmt->bind_param("ss", $userID, $userID);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();

if (!$staff) {
    die("Staff not found");
}

$staffName = $staff['staffName'];
$staffID   = $staff['staffID'];
$staffType = $staff['staffType'];
$staffEmail = $staff['email'];

// Get any active academic term
$termResult = $db->query("SELECT * FROM academic_term WHERE status = 'Active' LIMIT 1");
$term = $termResult->fetch_assoc();

$academicYear = $term['academicYear'] ?? 'N/A';
$semester     = $term['semester'] ?? 'N/A';
$termStart    = $term['startDate'] ?? '';
$termEnd      = $term['endDate'] ?? '';

// Get schedule data for this staff
$sql = "SELECT cs.courseID, cs.day, cs.startTime, cs.endTime, cs.classType, cs.facilityID,
               cs.tutGroup, cs.programID,
               c.courseName, c.creditHours, f.facilityName
        FROM class_schedule cs
        JOIN course c ON cs.courseID = c.courseID
        JOIN facility f ON cs.facilityID = f.facilityID
        WHERE cs.staffID = ?
        ORDER BY FIELD(cs.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), cs.startTime";

$stmt = $db->prepare($sql);
$stmt->bind_param("s", $staffID);
$stmt->execute();
$result = $stmt->get_result();

$scheduleData = [];
$courseSummary = [];

while ($row = $result->fetch_assoc()) {
    $scheduleData[] = $row;
    // Build unique course list for summary (group by courseID + tutGroup to show all groups)
    $key = $row['courseID'];
    if (!isset($courseSummary[$key])) {
        $courseSummary[$key] = [
            'courseID'     => $row['courseID'],
            'courseName'  => $row['courseName'],
            'creditHours' => $row['creditHours'],
            'groups'      => [],
            'classTypes'  => []
        ];
    }
    $groupLabel = $row['tutGroup'] . '-' . $row['programID'];
    if (!in_array($groupLabel, $courseSummary[$key]['groups'])) {
        $courseSummary[$key]['groups'][] = $groupLabel;
    }
    if (!in_array($row['classType'], $courseSummary[$key]['classTypes'])) {
        $courseSummary[$key]['classTypes'][] = $row['classType'];
    }
}

// Build schedule matrix for timetable grid
$startHour = 8;
$endHour   = 18;
$totalSlots = ($endHour - $startHour) * 2;
$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$scheduleMatrix = [];

foreach ($scheduleData as $row) {
    $day = $row['day'];
    list($sH, $sM) = explode(':', $row['startTime']);
    $startIndex = (($sH - $startHour) * 2) + ($sM == '30' ? 1 : 0);
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
            'info'    => $row,
            'colspan' => $durationSlots
        ];
        for ($i = 1; $i < $durationSlots; $i++) {
            $scheduleMatrix[$day][$startIndex + $i] = 'occupied';
        }
    }
}

// Count total teaching hours per week
$totalTeachingHours = 0;
foreach ($scheduleData as $row) {
    list($sH, $sM) = explode(':', $row['startTime']);
    list($eH, $eM) = explode(':', $row['endTime']);
    $hours = ($eH * 60 + $eM - $sH * 60 - $sM) / 60;
    $totalTeachingHours += $hours;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../favicon2.png">
    <meta charset="UTF-8">
    <title>Teaching Schedule - <?php echo htmlspecialchars($staffName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo time(); ?>">
    <style>
        /* Timetable Report Specific Styles */
        body.timetable-report-page {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #525659;
            margin: 0;
            padding: 40px;
            display: flex;
            justify-content: center;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .timetable-report-page .header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .timetable-report-page .label {
            display: block;
            font-size: 10px;
            text-transform: uppercase;
            color: #555;
            font-weight: 700;
            margin-bottom: 4px;
        }

        /* Staff Info Meta Box - 2 rows */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            border: 1px solid #000;
            margin-bottom: 20px;
        }
        .info-grid .info-cell {
            padding: 8px 12px;
            border-right: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .info-grid .info-cell:nth-child(3n) { border-right: none; }
        .info-grid .info-cell:nth-child(n+4) { border-bottom: none; }
        .info-cell .label { display: block; font-size: 9px; text-transform: uppercase; color: #555; font-weight: 700; margin-bottom: 3px; }
        .info-cell .value { font-size: 12px; font-weight: 600; color: #000; }

        /* Timetable Grid */
        .timetable-grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 20px;
            table-layout: fixed;
        }
        .timetable-grid th {
            background: #e5e5e5;
            padding: 6px 2px;
            border: 1px solid #000 !important;
            font-size: 8px;
            font-weight: 700;
            text-align: center;
            color: #000;
        }
        .timetable-grid th.day-col { width: 45px; }
        .timetable-grid td {
            border: 1px solid #000 !important;
            padding: 0;
            height: 50px;
            vertical-align: top;
            position: relative;
        }
        .timetable-grid td.day-cell {
            background: #f5f5f5;
            font-weight: 700;
            font-size: 10px;
            text-align: center;
            vertical-align: middle;
            color: #000;
        }
        .timetable-grid td.empty-cell {
            background: #fafafa;
        }

        /* Class Block */
        .class-block {
            padding: 3px 4px;
            height: 100%;
            box-sizing: border-box;
            font-size: 8px;
            line-height: 1.3;
            overflow: hidden;
            border-radius: 2px;
        }
        .class-block.is-Lecture { background: #dbeafe !important; border-left: 3px solid #2563eb !important; }
        .class-block.is-Practical { background: #dcfce7 !important; border-left: 3px solid #16a34a !important; }
        .class-block.is-Tutorial { background: #fef3c7 !important; border-left: 3px solid #d97706 !important; }
        .class-block .cb-code { font-weight: 700; font-size: 9px; color: #111; display: block; }
        .class-block .cb-venue { color: #444; display: block; margin-top: 2px; }
        .class-block .cb-group { color: #666; display: block; font-style: italic; margin-top: 1px; }

        /* Course Summary Table */
        .course-summary { margin-top: 10px; }
        .course-summary h3 {
            font-size: 13px;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 8px;
            color: #000;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 15px;
        }
        .summary-table th {
            background: #e5e5e5;
            padding: 8px 10px;
            border: 1px solid #000;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            text-align: left;
            color: #000;
        }
        .summary-table td {
            padding: 7px 10px;
            border: 1px solid #000;
            color: #000;
        }
        .summary-table tr:nth-child(even) { background: #fafafa; }
        .summary-table .total-row {
            font-weight: 700;
            background: #e5e5e5;
        }

        /* Note */
        .report-note {
            font-size: 10px;
            color: #555;
            font-style: italic;
            margin-top: 10px;
            padding: 8px 10px;
            background: #f9f9f9;
            border-left: 3px solid #8056ff;
        }

        /* Legend */
        .legend {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            font-size: 10px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .legend-color {
            width: 14px;
            height: 14px;
            border-radius: 2px;
            border: 1px solid #ccc;
        }
        .legend-color.lec { background: #dbeafe; border-left: 3px solid #2563eb; }
        .legend-color.prac { background: #dcfce7; border-left: 3px solid #16a34a; }
        .legend-color.tut { background: #fef3c7; border-left: 3px solid #d97706; }

        /* Print Styles */
        @media print {
            body.timetable-report-page { background: white; padding: 0; }
            .page { box-shadow: none; margin: 0; width: 100%; height: auto; padding: 10mm; }
            .fab-container { display: none; }
            @page { 
                margin: 5mm; 
                size: A4 landscape;
            }
            .timetable-grid td { height: 45px; }
            .info-grid, .info-cell { border-color: #000 !important; }

            /* Fix: border-collapse:collapse drops borders in Chrome PDF output.
               Use separate + spacing:0 and draw borders on edges instead. */
            .timetable-grid {
                border-collapse: separate !important;
                border-spacing: 0 !important;
                border-top: 1px solid #000 !important;
                border-left: 1px solid #000 !important;
            }
            .timetable-grid th,
            .timetable-grid td {
                border-right: 1px solid #000 !important;
                border-bottom: 1px solid #000 !important;
                border-top: none !important;
                border-left: none !important;
            }
            .summary-table {
                border-collapse: separate !important;
                border-spacing: 0 !important;
                border-top: 1px solid #000 !important;
                border-left: 1px solid #000 !important;
            }
            .summary-table th,
            .summary-table td {
                border-right: 1px solid #000 !important;
                border-bottom: 1px solid #000 !important;
                border-top: none !important;
                border-left: none !important;
            }

            /* Force backgrounds to print */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
        }
    </style>
</head>
<body class="timetable-report-page">

    <div class="fab-container">
        <button onclick="window.print()" class="fab">
            <i class="fa-solid fa-print"></i> Print / Save PDF
        </button>
        <button onclick="window.close()" class="fab fab-secondary">
            <i class="fa-solid fa-xmark"></i> Close
        </button>
    </div>

    <div class="page" style="width: 297mm; min-height: 210mm;">
        <!-- Header -->
        <div class="header">
            <div class="logo-circle">CL</div>
            <div class="uni-name">CampusLink University</div>
            <div class="report-title">Official Teaching Schedule</div>
        </div>

        <!-- Staff Info Grid -->
        <div class="info-grid">
            <div class="info-cell">
                <span class="label">Staff Name</span>
                <span class="value"><?php echo htmlspecialchars($staffName); ?></span>
            </div>
            <div class="info-cell">
                <span class="label">Staff ID</span>
                <span class="value"><?php echo htmlspecialchars($staffID); ?></span>
            </div>
            <div class="info-cell">
                <span class="label">Staff Type</span>
                <span class="value"><?php echo htmlspecialchars($staffType); ?></span>
            </div>
            <div class="info-cell">
                <span class="label">Teaching Hours / Week</span>
                <span class="value"><?php echo $totalTeachingHours; ?> hours</span>
            </div>
            <div class="info-cell">
                <span class="label">Academic Year / Semester</span>
                <span class="value"><?php echo htmlspecialchars($academicYear . ' / Semester ' . $semester); ?></span>
            </div>
            <div class="info-cell">
                <span class="label">Generated Date</span>
                <span class="value"><?php echo date('d-m-Y H:i'); ?></span>
            </div>
        </div>

        <!-- Legend -->
        <div class="legend">
            <div class="legend-item"><span class="legend-color lec"></span> Lecture (L)</div>
            <div class="legend-item"><span class="legend-color prac"></span> Practical (P)</div>
            <div class="legend-item"><span class="legend-color tut"></span> Tutorial (T)</div>
        </div>

        <!-- Timetable Grid -->
        <table class="timetable-grid">
            <thead>
                <tr>
                    <th class="day-col">Day</th>
                    <?php for ($h = $startHour; $h < $endHour; $h++): ?>
                        <th colspan="2"><?php echo sprintf("%02d:00 - %02d:00", $h, $h + 1); ?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daysOfWeek as $day): ?>
                <tr>
                    <td class="day-cell"><?php echo strtoupper(substr($day, 0, 3)); ?></td>
                    <?php for ($i = 0; $i < $totalSlots; $i++): ?>
                        <?php if (isset($scheduleMatrix[$day][$i])): ?>
                            <?php if ($scheduleMatrix[$day][$i] === 'occupied') continue; ?>
                            <?php 
                                $slot = $scheduleMatrix[$day][$i];
                                $info = $slot['info'];
                                $classType = ($info['classType'] === 'Lab') ? 'Practical' : ($info['classType'] ?? 'Lecture');
                                $typeClass = 'is-' . $classType;
                                $suffix = ($classType === 'Lecture') ? '(L)' : ($classType === 'Practical' ? '(P)' : '(T)');
                            ?>
                            <td colspan="<?php echo $slot['colspan']; ?>">
                                <div class="class-block <?php echo $typeClass; ?>">
                                    <span class="cb-code"><?php echo htmlspecialchars($info['courseID'] . ' ' . $suffix); ?></span>
                                    <span class="cb-venue"><?php echo htmlspecialchars($info['facilityID']); ?></span>
                                    <span class="cb-group"><?php echo htmlspecialchars($info['tutGroup'] . '-' . $info['programID']); ?></span>
                                </div>
                            </td>
                        <?php else: ?>
                            <td class="empty-cell"></td>
                        <?php endif; ?>
                    <?php endfor; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Course Summary -->
        <div class="course-summary">
            <h3>Teaching Summary</h3>
            <table class="summary-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">No.</th>
                        <th style="width: 15%;">Course Code</th>
                        <th style="width: 35%;">Course Name</th>
                        <th style="width: 12%; text-align: center;">Credit Hours</th>
                        <th style="width: 15%;">Class Type</th>
                        <th style="width: 15%;">Groups</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = 1; foreach ($courseSummary as $course): ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $counter++; ?></td>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($course['courseID']); ?></td>
                        <td><?php echo htmlspecialchars($course['courseName']); ?></td>
                        <td style="text-align: center;"><?php echo $course['creditHours']; ?></td>
                        <td><?php echo htmlspecialchars(implode(', ', $course['classTypes'])); ?></td>
                        <td><?php echo htmlspecialchars(implode(', ', $course['groups'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="3" style="text-align: right; padding-right: 15px;">Total Teaching Hours / Week</td>
                        <td style="text-align: center;"><?php echo $totalTeachingHours; ?>h</td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Note -->
        <div class="report-note">
            <strong>Note:</strong> This teaching schedule is subject to change. Please check the system regularly for any updates or replacement classes.
        </div>

        <!-- Footer -->
        <div class="footer">
            <div></div>
            <div class="signature">
                <div class="sig-line">
                    <?php echo htmlspecialchars($staffName); ?><br>
                    <span style="font-weight: 400; font-size: 11px; color: #555;"><?php echo htmlspecialchars($staffType); ?></span>
                </div>
                <div style="font-size: 10px; color: #666; margin-top: 5px;">Generated: <?php echo date('d-m-Y H:i'); ?></div>
            </div>
        </div>

        <div class="page-footer">
            <span>Page 1 of 1</span>
            <span>CampusLink University - Official Teaching Schedule</span>
        </div>
    </div>

</body>
</html>
