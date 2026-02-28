<?php
// File: admin/users-report.php
// All Users Report (Students, Staff, Admins)
require_once '../includes/config.php';
session_start();

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied");
}
prevent_back_button_cache();

$db = get_db_connection();

// Get filter parameter (all, student, staff, admin)
$filter = isset($_GET['type']) ? $_GET['type'] : 'all';

// Build query based on filter with additional columns for students and staff
$sql = "SELECT 
            u.userID,
            u.role,
            CASE 
                WHEN u.role = 'student' THEN s.studentID
                WHEN u.role = 'staff' THEN st.staffID
                WHEN u.role = 'admin' THEN a.adminID
            END as roleID,
            CASE 
                WHEN u.role = 'student' THEN s.studentName
                WHEN u.role = 'staff' THEN st.staffName
                WHEN u.role = 'admin' THEN a.adminName
            END as userName,
            CASE 
                WHEN u.role = 'student' THEN s.email
                WHEN u.role = 'staff' THEN st.email
                WHEN u.role = 'admin' THEN a.email
            END as email,
            s.programID as programID,
            st.staffType as staffType,
            (SELECT GROUP_CONCAT(DISTINCT cs.courseID ORDER BY cs.courseID SEPARATOR ', ')
             FROM class_schedule cs
             WHERE cs.staffID = st.staffID) as courseIDs
        FROM users u
        LEFT JOIN student s ON u.userID = s.userID AND u.role = 'student'
        LEFT JOIN staff st ON u.userID = st.userID AND u.role = 'staff'
        LEFT JOIN admin a ON u.userID = a.userID AND u.role = 'admin'
        WHERE 1=1";

// Add filter condition
if ($filter !== 'all') {
    $sql .= " AND u.role = '" . $db->real_escape_string($filter) . "'";
}

if ($filter === 'all') {
    $sql .= " ORDER BY u.userID ASC";
} else {
    $sql .= " ORDER BY userName ASC";
}

$result = $db->query($sql);
$users = $result->fetch_all(MYSQLI_ASSOC);

// Get counts for summary using direct DB queries for accuracy
$countResult = $db->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN LOWER(role) = 'student' THEN 1 ELSE 0 END) as students,
    SUM(CASE WHEN LOWER(role) = 'staff' THEN 1 ELSE 0 END) as staff,
    SUM(CASE WHEN LOWER(role) = 'admin' THEN 1 ELSE 0 END) as admins
    FROM users");
$counts = $countResult->fetch_assoc();

if ($filter === 'all') {
    $totalUsers = $counts['total'];
} else {
    $totalUsers = count($users);
}
$studentCount = $counts['students'];
$staffCount = $counts['staff'];
$adminCount = $counts['admins'];

// Determine report title based on filter
$reportTitle = "All Users Report";
if ($filter === 'student') $reportTitle = "Students Report";
elseif ($filter === 'staff') $reportTitle = "Staff Report";
elseif ($filter === 'admin') $reportTitle = "Administrators Report";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $reportTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body class="users-report-page">

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
            <div class="report-title"><?php echo $reportTitle; ?></div>
        </div>

        <div class="meta-box">
            <?php if ($filter === 'all'): ?>
                <div class="meta-col">
                    <span class="label">Total Users</span>
                    <span class="value"><?php echo $totalUsers; ?></span>
                </div>
                <div class="meta-col">
                    <span class="label">Students</span>
                    <span class="value"><?php echo $studentCount; ?></span>
                </div>
                <div class="meta-col">
                    <span class="label">Staff</span>
                    <span class="value"><?php echo $staffCount; ?></span>
                </div>
                <div class="meta-col">
                    <span class="label">Admins</span>
                    <span class="value"><?php echo $adminCount; ?></span>
                </div>
            <?php elseif ($filter === 'student'): ?>
                <div class="meta-col">
                    <span class="label">Total Students</span>
                    <span class="value"><?php echo $totalUsers; ?></span>
                </div>
            <?php elseif ($filter === 'staff'): ?>
                <div class="meta-col">
                    <span class="label">Total Staff</span>
                    <span class="value"><?php echo $totalUsers; ?></span>
                </div>
            <?php elseif ($filter === 'admin'): ?>
                <div class="meta-col">
                    <span class="label">Total Admins</span>
                    <span class="value"><?php echo $totalUsers; ?></span>
                </div>
            <?php endif; ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="text-align: center;">User ID</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <?php if ($filter === 'all'): ?>
                        <th style="text-align: center;">Role / Type</th>
                    <?php elseif ($filter === 'student'): ?>
                        <th style="text-align: center;">Programme</th>
                    <?php elseif ($filter === 'staff'): ?>
                        <th style="text-align: center;">Type</th>
                        <th style="text-align: center;">Courses</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach($users as $user): 
                        $roleClass = 'role-' . $user['role'];
                        $roleDisplay = ucfirst($user['role']);
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($filter === 'all' ? $user['userID'] : ($user['roleID'] ?? $user['userID'])); ?></td>
                        <td><?php echo htmlspecialchars($user['userName']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <?php if ($filter === 'all'): ?>
                            <td><span class="role-badge <?php echo $roleClass; ?>"><?php echo $roleDisplay; ?></span></td>
                        <?php elseif ($filter === 'student'): ?>
                            <td style="text-align: center; font-weight: 600;"><?php echo htmlspecialchars($user['programID'] ?? '-'); ?></td>
                        <?php elseif ($filter === 'staff'): ?>
                            <td style="text-align: center;"><?php echo htmlspecialchars($user['staffType'] ?? '-'); ?></td>
                            <td style="text-align: center; font-weight: 600;"><?php echo htmlspecialchars($user['courseIDs'] ?? '-'); ?></td>
                        <?php elseif ($filter === 'admin'): ?>
                            <!-- No additional column for admin -->
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="<?php echo ($filter === 'staff') ? '5' : (($filter === 'admin') ? '3' : '4'); ?>" style="text-align:center; padding: 30px; color: #6b7280;">No user records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer">
            <div></div>
            <div class="signature">
                <div class="sig-line">
                    Administrator<br>
                    <span style="font-weight: 400; font-size: 11px; color: #555;">Report Generated By</span>
                </div>
                <div style="font-size: 10px; color: #666; margin-top: 5px;">Generated: <?php echo date('d-m-Y H:i'); ?></div>
            </div>
        </div>

        <div class="page-footer">
            <span>Page 1 of 1</span>
            <span>CampusLink University - Users Report</span>
        </div>
    </div>

</body>
</html>
