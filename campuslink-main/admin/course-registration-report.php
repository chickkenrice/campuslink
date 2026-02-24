<?php
// File: admin/course-registration-report.php
// Student Course Registration List Report
require_once '../includes/config.php';
session_start();

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied");
}
prevent_back_button_cache();

$db = get_db_connection();

// Get filter parameters
$termID = isset($_GET['termID']) ? intval($_GET['termID']) : null;
$courseID = isset($_GET['courseID']) ? $_GET['courseID'] : null;

// Get available terms for dropdown (if viewing in browser)
$termsResult = $db->query("
    SELECT t.termID, t.academicYear, t.semester, t.year, p.programID, p.programName 
    FROM academic_term t
    JOIN program p ON t.programID = p.programID
    ORDER BY t.academicYear DESC, t.year, t.semester
");
$terms = $termsResult->fetch_all(MYSQLI_ASSOC);

// Auto-select first term if none specified
if (!$termID && !empty($terms)) {
    $termID = $terms[0]['termID'];
}

// Get term details
$termInfo = null;
if ($termID) {
    $stmt = $db->prepare("
        SELECT t.*, p.programName 
        FROM academic_term t 
        JOIN program p ON t.programID = p.programID
        WHERE t.termID = ?
    ");
    $stmt->bind_param("i", $termID);
    $stmt->execute();
    $termInfo = $stmt->get_result()->fetch_assoc();
}

// Build query to get course registrations
$registrations = [];
if ($termID) {
    $sql = "
        SELECT 
            co.courseID,
            c.courseName,
            c.creditHours,
            pc.type as courseType,
            s.studentID,
            s.studentName,
            cr.registeredAt,
            cr.status,
            cr.droppedAt
        FROM course_registration cr
        JOIN course_offering co ON cr.offeringID = co.offeringID
        JOIN course c ON co.courseID = c.courseID
        JOIN student s ON cr.studentID = s.studentID
        LEFT JOIN program_course pc ON pc.courseID = co.courseID AND pc.programID = co.programID
        WHERE co.termID = ? AND cr.status = 'Registered'
    ";
    
    if ($courseID) {
        $sql .= " AND co.courseID = ?";
        $stmt = $db->prepare($sql . " ORDER BY co.courseID, s.studentName");
        $stmt->bind_param("is", $termID, $courseID);
    } else {
        $stmt = $db->prepare($sql . " ORDER BY co.courseID, s.studentName");
        $stmt->bind_param("i", $termID);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Group by course
    while ($row = $result->fetch_assoc()) {
        $cID = $row['courseID'];
        if (!isset($registrations[$cID])) {
            $registrations[$cID] = [
                'courseName' => $row['courseName'],
                'creditHours' => $row['creditHours'],
                'courseType' => $row['courseType'],
                'students' => []
            ];
        }
        $registrations[$cID]['students'][] = $row;
    }
}

// Calculate totals
$totalCourses = count($registrations);
$totalEnrollments = 0;
foreach ($registrations as $courseData) {
    $totalEnrollments += count($courseData['students']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Registration Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo time(); ?>">
    <style>
        @media screen {
            .filter-bar {
                background: white;
                padding: 20px;
                margin-bottom: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                display: flex;
                gap: 15px;
                align-items: end;
            }
            .filter-group {
                flex: 1;
            }
            .filter-group label {
                display: block;
                font-size: 12px;
                font-weight: 600;
                color: #555;
                margin-bottom: 5px;
            }
            .filter-group select {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 6px;
                font-size: 13px;
            }
            .filter-group button {
                padding: 8px 20px;
                background: var(--purple-base, #7c3aed);
                color: white;
                border: none;
                border-radius: 6px;
                font-weight: 600;
                cursor: pointer;
            }
        }
        @media print {
            .filter-bar, .fab-container { display: none !important; }
        }
        .course-section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }
        .course-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 8px 8px 0 0;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .course-meta {
            font-size: 11px;
            font-weight: 400;
            opacity: 0.9;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-confirmed { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body class="users-report-page">

    <div class="fab-container">
        <button onclick="window.print()" class="fab">
            <i class="fa-solid fa-print"></i> Print / Save PDF
        </button>
        <button onclick="window.location.href='manage-offerings.php'" class="fab fab-secondary">
            <i class="fa-solid fa-arrow-left"></i> Back
        </button>
    </div>

    <div class="filter-bar">
        <div class="filter-group">
            <label>Academic Term</label>
            <select id="termSelect" onchange="filterReport()">
                <option value="">Select Term...</option>
                <?php foreach ($terms as $t): ?>
                    <option value="<?php echo $t['termID']; ?>" <?php echo ($termID == $t['termID']) ? 'selected' : ''; ?>>
                        <?php echo "{$t['programID']} - Y{$t['year']}S{$t['semester']} ({$t['academicYear']})"; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label>Course (Optional)</label>
            <input type="text" id="courseInput" placeholder="e.g., BMIT3084" value="<?php echo htmlspecialchars($courseID ?? ''); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
        </div>
        <div class="filter-group" style="flex: 0;">
            <button onclick="filterReport()">Apply</button>
        </div>
    </div>

    <div class="page">
        <div class="header">
            <div class="logo-circle">CL</div>
            <div class="uni-name">CampusLink University</div>
            <div class="report-title">MASTER COURSE REGISTRATION LIST</div>
            <?php if ($termInfo): ?>
                <div style="font-size: 12px; color: #555; margin-top: 5px;">
                    <strong><?php echo $termInfo['programName']; ?></strong> — 
                    Year <?php echo $termInfo['year']; ?> Semester <?php echo $termInfo['semester']; ?> 
                    (<?php echo $termInfo['academicYear']; ?>)
                </div>
            <?php endif; ?>
        </div>

        <div class="meta-box">
            <div class="meta-col">
                <span class="label">Total Courses</span>
                <span class="value"><?php echo $totalCourses; ?></span>
            </div>
            <div class="meta-col">
                <span class="label">Total Enrollments</span>
                <span class="value"><?php echo $totalEnrollments; ?></span>
            </div>
            <div class="meta-col">
                <span class="label">Generated On</span>
                <span class="value" style="font-size: 13px;"><?php echo date('d M Y, H:i'); ?></span>
            </div>
        </div>

        <?php if (!empty($registrations)): ?>
            <?php foreach ($registrations as $cID => $courseData): ?>
                <div class="course-section">
                    <div class="course-header">
                        <div>
                            <strong><?php echo htmlspecialchars($cID); ?></strong> — 
                            <?php echo htmlspecialchars($courseData['courseName']); ?>
                        </div>
                        <div class="course-meta">
                            <?php echo $courseData['creditHours']; ?> Credits • 
                            <?php echo $courseData['courseType'] ?? 'Main'; ?> • 
                            <?php echo count($courseData['students']); ?> Student(s)
                        </div>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 15%; text-align: center;">No.</th>
                                <th style="width: 20%;">Student ID</th>
                                <th style="width: 35%;">Name</th>
                                <th style="width: 15%;">Date Registered</th>
                                <th style="width: 15%; text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($courseData['students'] as $student): ?>
                                <tr>
                                    <td style="text-align: center;"><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($student['studentID']); ?></td>
                                    <td><?php echo htmlspecialchars($student['studentName']); ?></td>
                                    <td><?php echo date('d-M-Y', strtotime($student['registeredAt'])); ?></td>
                                    <td style="text-align: center;">
                                        <span class="status-badge status-confirmed">Confirmed</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 60px 20px; color: #6b7280;">
                <i class="fa-solid fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                <p style="font-size: 15px; font-weight: 600;">No Registration Records Found</p>
                <p style="font-size: 13px; margin-top: 5px;">Select a term to view course registrations.</p>
            </div>
        <?php endif; ?>

        <div class="footer">
            <div></div>
            <div class="signature">
                <div class="sig-line">
                    Administrator<br>
                    <span style="font-weight: 400; font-size: 11px; color: #555;">Report Generated By</span>
                </div>
            </div>
        </div>

        <div class="page-footer">
            <span>*** END OF REPORT ***</span>
            <span>Page 1 of 1</span>
        </div>
    </div>

    <script>
        function filterReport() {
            const termID = document.getElementById('termSelect').value;
            const courseID = document.getElementById('courseInput').value.trim();
            let url = 'course-registration-report.php';
            const params = new URLSearchParams();
            if (termID) params.append('termID', termID);
            if (courseID) params.append('courseID', courseID);
            if (params.toString()) url += '?' + params.toString();
            window.location.href = url;
        }
    </script>

</body>
</html>
