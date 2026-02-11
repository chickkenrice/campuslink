<?php
session_start();
require_once(__DIR__ . '/../includes/config.php');
require_once(__DIR__ . '/../includes/activity-logger.php');

// =========================================================
// 1. SECURITY CHECK
// =========================================================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
prevent_back_button_cache();

$db = get_db_connection();
$activeTab = $_GET['tab'] ?? 'all';
$message = '';

// =========================================================
// HELPER: AUTO-GENERATE USER ID
// =========================================================
function generateUserID($db, $role) {
    if ($role === 'Student') {
        // Get the highest student ID
        $result = $db->query("SELECT studentID FROM student ORDER BY studentID DESC LIMIT 1");
        if ($result && $row = $result->fetch_assoc()) {
            // Extract numeric part and increment
            $lastID = $row['studentID'];
            if (preg_match('/S(\d+)/', $lastID, $matches)) {
                $num = intval($matches[1]) + 1;
                return 'S' . str_pad($num, 3, '0', STR_PAD_LEFT);
            }
        }
        return 'S001'; // Default first ID
    } elseif ($role === 'Staff') {
        $result = $db->query("SELECT staffID FROM staff ORDER BY staffID DESC LIMIT 1");
        if ($result && $row = $result->fetch_assoc()) {
            $lastID = $row['staffID'];
            if (preg_match('/S(\d+)/', $lastID, $matches)) {
                $num = intval($matches[1]) + 1;
                return 'S' . str_pad($num, 3, '0', STR_PAD_LEFT);
            }
        }
        return 'S001';
    } elseif ($role === 'Admin') {
        $result = $db->query("SELECT adminID FROM admin ORDER BY adminID DESC LIMIT 1");
        if ($result && $row = $result->fetch_assoc()) {
            $lastID = $row['adminID'];
            if (preg_match('/ADM(\d+)/', $lastID, $matches)) {
                $num = intval($matches[1]) + 1;
                return 'ADM' . str_pad($num, 2, '0', STR_PAD_LEFT);
            }
        }
        return 'ADM01';
    }
    return null;
}

// =========================================================
// 2. DATABASE ACTIONS (POST)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    // --- DELETE USER ---
    if ($action === 'delete') {
        $uid = $_POST['uid'];
        
        // Get user info before deleting for logging
        $getUserStmt = $db->prepare("SELECT role FROM users WHERE userID = ?");
        $getUserStmt->bind_param("s", $uid);
        $getUserStmt->execute();
        $userInfo = $getUserStmt->get_result()->fetch_assoc();
        $getUserStmt->close();
        
        $stmt = $db->prepare("DELETE FROM users WHERE userID = ?");
        $stmt->bind_param("s", $uid);
        
        if ($stmt->execute()) {
            // Log user deletion
            $logUserID = $_SESSION['actual_user_id'] ?? $_SESSION['user_id'];
            logActivity($db, $logUserID, 'USER_DELETE', 'Deleted user account: ' . $uid, [
                'deletedUserID' => $uid,
                'deletedUserRole' => $userInfo['role'] ?? 'Unknown'
            ]);
            
            $message = "User has been deleted.";
        } else {
            $message = "Error deleting user: " . $db->error;
        }
    }

    // --- CREATE USER ---
    elseif ($action === 'create') {
        $role = $_POST['role'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'] ?? '';
        $programme = $_POST['programme'] ?? '';
        $staffType = $_POST['staffType'] ?? '';
        $course = $_POST['course'] ?? '';
        
        // Auto-generate ID based on role
        $id = generateUserID($db, $role);
        
        if (!$id) {
            $message = "Error generating user ID.";
        } else {
            // 1. Insert into Parent Table (USERS)
            // Note: Password handling is done separately (default is 'abc')
            $stmt = $db->prepare("INSERT INTO users (userID, role) VALUES (?, ?)");
            $stmt->bind_param("ss", $id, $role);
            
            if ($stmt->execute()) {
                // 2. Insert into Child Table
                if ($role === 'Student') {
                    $stmt = $db->prepare("INSERT INTO student (studentID, userID, studentName, email, programID) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $id, $id, $name, $email, $programme);
                } elseif ($role === 'Staff') {
                    $stmt = $db->prepare("INSERT INTO staff (staffID, userID, staffName, email, staffType) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $id, $id, $name, $email, $staffType);
                } elseif ($role === 'Admin') {
                    $stmt = $db->prepare("INSERT INTO admin (adminID, userID, adminName, email, contactNo) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $id, $id, $name, $email, $phone);
                }

                if (isset($stmt)) {
                    try {
                        if ($stmt->execute()) {
                            // Log user creation
                            $logUserID = $_SESSION['actual_user_id'] ?? $_SESSION['user_id'];
                            logActivity($db, $logUserID, 'USER_CREATE', "Created new $role: $name (ID: $id)", [
                                'newUserID' => $id,
                                'newUserRole' => $role,
                                'newUserName' => $name,
                                'email' => $email
                            ]);
                            
                            $message = "New $role added successfully with ID: $id (Default password: abc)";
                        } else {
                            $message = "Error adding details: " . $stmt->error;
                        }
                    } catch (Exception $e) {
                        $message = "Error: " . $e->getMessage();
                    }
                }
            } else {
                $message = "Error creating user: " . $db->error;
            }
        }
    }

    // --- UPDATE USER ---
    elseif ($action === 'update') {
        $id = $_POST['id']; 
        $role = $_POST['role'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $extra = $_POST['extra'] ?? '';
        $group = $_POST['group'] ?? '';

        if ($role === 'Student') {
            // FIX: Changed 'programme' to 'programID'
            $stmt = $db->prepare("UPDATE student SET studentName=?, email=?, programID=?, tutGroup=? WHERE studentID=?");
            $stmt->bind_param("sssss", $name, $email, $extra, $group, $id);
        } elseif ($role === 'Staff') {
            // FIX: Changed 'department' to 'staffType'
            $stmt = $db->prepare("UPDATE staff SET staffName=?, email=?, staffType=? WHERE staffID=?");
            $stmt->bind_param("ssss", $name, $email, $extra, $id);
        } elseif ($role === 'Admin') {
            $stmt = $db->prepare("UPDATE admin SET adminName=?, email=?, contactNo=? WHERE adminID=?");
            $stmt->bind_param("ssss", $name, $email, $extra, $id);
        }

        if (isset($stmt) && $stmt->execute()) {
            // Log user update
            $logUserID = $_SESSION['actual_user_id'] ?? $_SESSION['user_id'];
            logActivity($db, $logUserID, 'USER_UPDATE', "Updated $role: $name (ID: $id)", [
                'updatedUserID' => $id,
                'updatedUserRole' => $role,
                'updatedUserName' => $name,
                'email' => $email
            ]);
            
            $message = "User details updated successfully.";
        } else {
            $message = "Error updating user.";
        }
    }
}

// =========================================================
// 3. FETCH DATA (GET) with Sorting
// =========================================================
$sortBy = $_GET['sort'] ?? 'name_asc';

// Build ORDER BY clause based on sort option
$orderByClause = "name ASC"; // default
switch($sortBy) {
    case 'id_asc': $orderByClause = "childID ASC"; break;
    case 'id_desc': $orderByClause = "childID DESC"; break;
    case 'name_asc': $orderByClause = "name ASC"; break;
    case 'name_desc': $orderByClause = "name DESC"; break;
    case 'email_asc': $orderByClause = "email ASC"; break;
    case 'email_desc': $orderByClause = "email DESC"; break;
    case 'extra_asc': $orderByClause = "extra ASC, name ASC"; break;
    case 'extra_desc': $orderByClause = "extra DESC, name ASC"; break;
    case 'role_asc': $orderByClause = "role ASC, name ASC"; break;
    case 'role_desc': $orderByClause = "role DESC, name ASC"; break;
}

if ($activeTab === 'student') {
    // FIX: 'programme' -> 'programID'
    $sql = "SELECT studentID as childID, userID, studentName as name, email, 'Student' as role, programID as extra, tutGroup as grp FROM student ORDER BY " . $orderByClause;
} elseif ($activeTab === 'staff') {
    // FIX: 'department' -> 'staffType'
    $sql = "SELECT staffID as childID, userID, staffName as name, email, 'Staff' as role, staffType as extra, '' as grp,
            (SELECT GROUP_CONCAT(DISTINCT cs.courseID ORDER BY cs.courseID SEPARATOR ', ')
             FROM class_schedule cs
             WHERE cs.staffID = staff.staffID) as courses
            FROM staff ORDER BY " . $orderByClause;
} elseif ($activeTab === 'admin') {
    $sql = "SELECT adminID as childID, userID, adminName as name, email, 'Admin' as role, contactNo as extra, '' as grp FROM admin ORDER BY " . $orderByClause;
} else {
    // 'ALL' TAB
    // FIX: 'programme' -> 'programID' AND 'department' -> 'staffType'
    $sql = "SELECT u.userID, u.role,
            COALESCE(s.studentName, st.staffName, a.adminName) as name,
            COALESCE(s.email, st.email, a.email) as email,
            COALESCE(s.programID, st.staffType, a.contactNo) as extra,
            COALESCE(s.tutGroup, '') as grp,
            COALESCE(s.studentID, st.staffID, a.adminID) as childID,
            (SELECT GROUP_CONCAT(DISTINCT cs.courseID ORDER BY cs.courseID SEPARATOR ', ')
             FROM class_schedule cs
             WHERE cs.staffID = st.staffID) as courses
            FROM users u
            LEFT JOIN student s ON u.userID = s.userID
            LEFT JOIN staff st ON u.userID = st.userID
            LEFT JOIN admin a ON u.userID = a.userID
            ORDER BY " . $orderByClause;
}
$result = $db->query($sql);

// Fetch dropdown options for form
$programmes = $db->query("SELECT DISTINCT programID FROM student WHERE programID IS NOT NULL AND programID != '' ORDER BY programID");
$courses = $db->query("SELECT courseID, courseName FROM course ORDER BY courseName");

// Fetch distinct values for Edit modal dropdowns
$staffTypes = $db->query("SELECT DISTINCT staffType FROM staff WHERE staffType IS NOT NULL AND staffType != '' ORDER BY staffType");
$editProgrammes = $db->query("SELECT DISTINCT programID FROM student WHERE programID IS NOT NULL AND programID != '' ORDER BY programID");
$tutGroups = $db->query("SELECT DISTINCT tutGroup FROM student WHERE tutGroup IS NOT NULL AND tutGroup != '' ORDER BY tutGroup");
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Console — Manage Users</title>
    
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo time(); ?>">
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
                        <li>
                            <a href="manage-students.php" class="nav-item is-active">
                                <span class="nav-icon"><i class="fa-solid fa-users-gear"></i></span>
                                <span class="nav-label">User Management</span>
                            </a>
                        </li>
                        <li>
                            <a href="activity-logs.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-chart-line"></i></span>
                                <span class="nav-label">Activity Logs</span>
                            </a>
                        </li>
                        <li>
                            <a href="manage-timetable.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                <span class="nav-label">Timetable</span>
                            </a>
                        </li>
                        <li>
                            <a href="manage-resources.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-building"></i></span>
                                <span class="nav-label">Resources</span>
                            </a>
                        </li>
                        <li>
                            <a href="manage-offerings.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-book-open"></i></span>
                                <span class="nav-label">Course Offerings</span>
                            </a>
                        </li>
                        <li>
                            <a href="registration-monitor.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-chart-bar"></i></span>
                                <span class="nav-label">Registration Monitor</span>
                            </a>
                        </li>
                        <li>
                            <a href="../logout.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                                <span class="nav-label">Logout</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </aside>

            <main class="dashboard">
                <header class="dashboard-topbar">
                    <div class="topbar-right">
                        <div class="user-card">
                            <div class="user-info">
                                <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                                <span class="user-role">Admin</span>
                            </div>
                            <div class="profile-pic">
                                <i class="fa-solid fa-user"></i>
                            </div>
                        </div>
                    </div>
                </header>
                
                <section class="welcome-card">
                    <div class="welcome-details">
                        <p class="welcome-date"><?php echo date('l, F j, Y'); ?></p>
                        <h1 class="welcome-title">User Management</h1>
                        <p class="welcome-text">Manage accounts for Students, Staff, and Admins</p>
                    </div>
                </section>

                <section class="announcements-card" style="margin-top: 20px;">
                    <?php if($message): ?>
                        <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size:14px; border: 1px solid #c3e6cb;">
                            <i class="fa-solid fa-check-circle"></i> <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Add New User Section -->
                    <div class="add-user-section">
                        <button type="button" class="toggle-form-btn" onclick="toggleAddForm()">
                            <i class="fa-solid fa-user-plus"></i> Add New User
                        </button>
                        
                        <div id="addUserFormContainer" class="add-user-form-container">
                            <h3 style="margin-top: 0; color: #8056ff; margin-bottom: 20px;">
                                <i class="fa-solid fa-user-plus"></i> Create New User Account
                            </h3>
                            <form method="POST" onsubmit="return validateForm()" novalidate>
                                <input type="hidden" name="action" value="create">
                                
                                <div class="form-grid-2">
                                    <div class="form-field">
                                        <label for="role">Role <span style="color: red;">*</span></label>
                                        <select id="role" name="role" required onchange="toggleRoleFields()" onfocus="clearFieldError(this)">
                                            <option value="">Select Role...</option>
                                            <option value="Student">Student</option>
                                            <option value="Staff">Staff</option>
                                            <option value="Admin">Admin</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-field">
                                        <label for="name">Full Name <span style="color: red;">*</span></label>
                                        <input type="text" id="name" name="name" placeholder="Enter full name" required disabled oninput="clearFieldError(this)">
                                    </div>
                                    
                                    <div class="form-field">
                                        <label for="email">Email Address <span style="color: red;">*</span></label>
                                        <input type="email" id="email" name="email" placeholder="user@tarc.edu.my" required disabled oninput="clearFieldError(this)">
                                    </div>
                                    
                                    <div class="form-field">
                                        <label for="phone">Phone Number</label>
                                        <input type="tel" id="phone" name="phone" placeholder="012-3456789" disabled oninput="clearFieldError(this)">
                                    </div>
                                    
                                    <!-- Programme field for Students -->
                                    <div class="form-field" id="programmeField" style="display: none;">
                                        <label for="programme">Programme <span style="color: red;">*</span></label>
                                        <select id="programme" name="programme" onchange="clearFieldError(this)">
                                            <option value="">Select Programme...</option>
                                            <?php 
                                            if ($programmes && $programmes->num_rows > 0) {
                                                while($prog = $programmes->fetch_assoc()) {
                                                    echo '<option value="' . htmlspecialchars($prog['programID']) . '">' . htmlspecialchars($prog['programID']) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Staff Type field for Staff -->
                                    <div class="form-field" id="staffTypeField" style="display: none;">
                                        <label for="staffType">Staff Type <span style="color: red;">*</span></label>
                                        <select id="staffType" name="staffType" onchange="clearFieldError(this)">
                                            <option value="">Select Type...</option>
                                            <option value="Lecturer">Lecturer</option>
                                            <option value="Tutor">Tutor</option>
                                            <option value="Both">Both</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Course field for Staff -->
                                    <div class="form-field" id="courseField" style="display: none;">
                                        <label for="course">Assigned Course <span style="color: red;">*</span></label>
                                        <select id="course" name="course" onchange="clearFieldError(this)">
                                            <option value="">Select Course...</option>
                                            <?php 
                                            if ($courses && $courses->num_rows > 0) {
                                                while($crs = $courses->fetch_assoc()) {
                                                    echo '<option value="' . htmlspecialchars($crs['courseID']) . '">' . htmlspecialchars($crs['courseID']) . ' - ' . htmlspecialchars($crs['courseName']) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div style="background: #f0f7ff; padding: 12px; border-radius: 8px; margin-top: 16px; font-size: 13px; color: #0366d6;">
                                    <i class="fa-solid fa-info-circle"></i> 
                                    <strong>Note:</strong> User ID will be auto-generated. Default password is <strong>"abc"</strong> - users should change it after first login.
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn-submit">
                                        <i class="fa-solid fa-check"></i> Create User
                                    </button>
                                    <button type="button" class="btn-cancel" onclick="toggleAddForm()">
                                        <i class="fa-solid fa-times"></i> Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="tab-header-container">
                        <nav class="tab-nav">
                            <a href="?tab=all" class="tab-btn <?php echo $activeTab == 'all' ? 'active' : ''; ?>">All Users</a>
                            <a href="?tab=student" class="tab-btn <?php echo $activeTab == 'student' ? 'active' : ''; ?>">Students</a>
                            <a href="?tab=staff" class="tab-btn <?php echo $activeTab == 'staff' ? 'active' : ''; ?>">Staff</a>
                            <a href="?tab=admin" class="tab-btn <?php echo $activeTab == 'admin' ? 'active' : ''; ?>">Admins</a>
                        </nav>
                        <a href="users-report.php?type=<?php echo $activeTab; ?>" target="_blank" class="report-btn">
                            <i class="fa-solid fa-file-pdf"></i> Generate Report
                        </a>
                    </div>
                    
                    <!-- Sort Filter Section -->
                    <div class="sort-filter-container" style="margin-bottom: 15px; display: flex; align-items: center; gap: 12px;">
                        <label for="sortSelect" style="font-size: 14px; font-weight: 600; color: #555;">
                            <i class="fa-solid fa-sort"></i> Sort by:
                        </label>
                        <select id="sortSelect" class="sort-dropdown" onchange="handleSortChange()" style="padding: 8px 12px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px; background: white; cursor: pointer; min-width: 180px;">
                            <?php 
                            $sortOptions = [];
                            if ($activeTab === 'all') {
                                $sortOptions = [
                                    'name_asc' => 'Name (A-Z)',
                                    'name_desc' => 'Name (Z-A)',
                                    'id_asc' => 'ID (Ascending)',
                                    'id_desc' => 'ID (Descending)',
                                    'email_asc' => 'Email (A-Z)',
                                    'email_desc' => 'Email (Z-A)',
                                    'role_asc' => 'Role (A-Z)',
                                    'role_desc' => 'Role (Z-A)'
                                ];
                            } elseif ($activeTab === 'student') {
                                $sortOptions = [
                                    'name_asc' => 'Name (A-Z)',
                                    'name_desc' => 'Name (Z-A)',
                                    'id_asc' => 'ID (Ascending)',
                                    'id_desc' => 'ID (Descending)',
                                    'email_asc' => 'Email (A-Z)',
                                    'email_desc' => 'Email (Z-A)',
                                    'extra_asc' => 'Programme (A-Z)',
                                    'extra_desc' => 'Programme (Z-A)'
                                ];
                            } elseif ($activeTab === 'staff') {
                                $sortOptions = [
                                    'name_asc' => 'Name (A-Z)',
                                    'name_desc' => 'Name (Z-A)',
                                    'id_asc' => 'ID (Ascending)',
                                    'id_desc' => 'ID (Descending)',
                                    'email_asc' => 'Email (A-Z)',
                                    'email_desc' => 'Email (Z-A)',
                                    'extra_asc' => 'Type (A-Z)',
                                    'extra_desc' => 'Type (Z-A)'
                                ];
                            } elseif ($activeTab === 'admin') {
                                $sortOptions = [
                                    'name_asc' => 'Name (A-Z)',
                                    'name_desc' => 'Name (Z-A)',
                                    'id_asc' => 'ID (Ascending)',
                                    'id_desc' => 'ID (Descending)',
                                    'email_asc' => 'Email (A-Z)',
                                    'email_desc' => 'Email (Z-A)',
                                    'extra_asc' => 'Contact No (Ascending)',
                                    'extra_desc' => 'Contact No (Descending)'
                                ];
                            }
                            
                            foreach ($sortOptions as $value => $label) {
                                $selected = ($sortBy === $value) ? 'selected' : '';
                                echo '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="table-container" style="max-height: calc(100vh - 380px);">
                        <table class="student-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background:#f9fafb; border-bottom: 2px solid #eee; text-align: left;">
                                    <th style="padding:12px;">ID</th>
                                    <th style="padding:12px;">Role</th>
                                    <th style="padding:12px;">Name</th>
                                    <th style="padding:12px;">Email</th>
                                    <?php if ($activeTab !== 'all'): ?>
                                    <th style="padding:12px;">
                                        <?php 
                                        if ($activeTab == 'student') echo 'Programme';
                                        elseif ($activeTab == 'staff') echo 'Type';
                                        elseif ($activeTab == 'admin') echo 'Contact No';
                                        ?>
                                    </th>
                                    <?php endif; ?>
                                    <?php if ($activeTab == 'staff'): ?>
                                    <th style="padding:12px;">Courses</th>
                                    <?php endif; ?>
                                    <th style="padding:12px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($result && $result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr style="border-bottom: 1px solid #f0f0f0;">
                                        <td class="id-cell" style="padding:12px; font-weight:600;">
                                            <?php echo ($activeTab === 'all') ? $row['userID'] : $row['childID']; ?>
                                        </td>
                                        <td style="padding:12px;">
                                            <?php 
                                            $badgeColor = '#f3e5ff'; // Purple (Student)
                                            $textColor = '#8056ff';
                                            if($row['role'] == 'Admin') { $badgeColor = '#ffe5e5'; $textColor = '#ff5f73'; } // Red
                                            if($row['role'] == 'Staff') { $badgeColor = '#e5f6ff'; $textColor = '#00a8ff'; } // Blue
                                            ?>
                                            <span style="background: <?php echo $badgeColor; ?>; color: <?php echo $textColor; ?>; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                                                <?php echo $row['role']; ?>
                                            </span>
                                        </td>
                                        <td class="name-cell" style="padding:12px;"><?php echo $row['name']; ?></td>
                                        <td style="padding:12px;"><?php echo $row['email']; ?></td>
                                        <?php if ($activeTab !== 'all'): ?>
                                        <td style="padding:12px;">
                                            <?php 
                                                echo $row['extra']; 
                                                if($row['grp']) echo " <span style='color:#aaa; font-size:12px;'>(" . $row['grp'] . ")</span>";
                                            ?>
                                        </td>
                                        <?php endif; ?>
                                        <?php if ($activeTab == 'staff'): ?>
                                        <td style="padding:12px; font-weight: 600;">
                                            <?php echo !empty($row['courses']) ? $row['courses'] : '-'; ?>
                                        </td>
                                        <?php endif; ?>
                                        <?php if ($activeTab == 'staff'): ?>
                                        <td style="padding:12px; font-weight: 600;">
                                            <?php echo !empty($row['courses']) ? $row['courses'] : '-'; ?>
                                        </td>
                                        <?php endif; ?>
                                        <td style="padding:12px;">
                                            <?php if($row['userID'] !== $_SESSION['user_id']): ?>
                                                <button class="action-btn btn-edit" onclick='openEdit(<?php echo json_encode($row); ?>)'>
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="uid" value="<?php echo $row['userID']; ?>">
                                                    <button type="submit" class="action-btn btn-delete"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            <?php else: ?>
                                                <span style="font-size:12px; color:#aaa;">(Current)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="<?php echo $activeTab === 'all' ? '5' : ($activeTab === 'staff' ? '7' : '6'); ?>" style="text-align:center; padding: 30px; color:#999;">No records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <!-- Edit User Modal (kept for editing existing users) -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle" style="margin-top:0;">Edit User</h2>
            
            <form method="POST" onsubmit="return validateEditForm()" novalidate>
                <input type="hidden" name="action" id="formAction" value="update">
                
                <div class="form-row">
                    <label>Role</label>
                    <select name="role" id="inpRole" disabled style="background: #eee;">
                        <option value="Student">Student</option>
                        <option value="Staff">Staff</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>

                <div class="form-row">
                    <label>User ID</label>
                    <input type="text" name="id" id="inpId" readonly style="background: #eee;">
                </div>

                <div class="form-row">
                    <label>Full Name</label>
                    <input type="text" name="name" id="inpName" required oninput="clearFieldError(this)">
                </div>

                <div class="form-row">
                    <label>Email</label>
                    <input type="email" name="email" id="inpEmail" required oninput="clearFieldError(this)">
                </div>

                <div class="form-row" id="extraFieldRow">
                    <label id="lblExtra">Additional Info</label>
                    <!-- Staff Type dropdown (shown for Staff) -->
                    <select name="extra" id="inpExtraStaffType" onchange="clearFieldError(this)" style="display:none;">
                        <option value="">Select Staff Type...</option>
                        <?php if ($staffTypes && $staffTypes->num_rows > 0): ?>
                            <?php while($st = $staffTypes->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($st['staffType']); ?>"><?php echo htmlspecialchars($st['staffType']); ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                    <!-- Programme dropdown (shown for Student) -->
                    <select name="extra" id="inpExtraProgramme" onchange="clearFieldError(this)" style="display:none;">
                        <option value="">Select Programme...</option>
                        <?php if ($editProgrammes && $editProgrammes->num_rows > 0): ?>
                            <?php while($ep = $editProgrammes->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($ep['programID']); ?>"><?php echo htmlspecialchars($ep['programID']); ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                    <!-- Contact No text input (shown for Admin) -->
                    <input type="text" name="extra" id="inpExtraAdmin" oninput="clearFieldError(this)" style="display:none;" placeholder="e.g. 012-3456789">
                </div>

                <div class="form-row" id="groupFieldRow">
                    <label>Tutorial Group</label>
                    <select name="group" id="inpGroup" onchange="clearFieldError(this)">
                        <option value="">Select Tutorial Group...</option>
                        <?php if ($tutGroups && $tutGroups->num_rows > 0): ?>
                            <?php while($tg = $tutGroups->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($tg['tutGroup']); ?>"><?php echo htmlspecialchars($tg['tutGroup']); ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div style="margin-top:24px;">
                    <button type="submit" class="btn" style="width:100%; background: #8056ff; color: white; padding: 12px; border:none; border-radius: 8px; cursor:pointer;">Update User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Handle sort change
        function handleSortChange() {
            const sortValue = document.getElementById('sortSelect').value;
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('sort', sortValue);
            window.location.href = currentUrl.toString();
        }
        
        // Toggle Add User Form
        function toggleAddForm() {
            const container = document.getElementById('addUserFormContainer');
            container.classList.toggle('active');
        }
        
        // Toggle role-specific fields in add form
        function toggleRoleFields() {
            // Clear errors when changing role
            clearErrors();
            
            const role = document.getElementById('role').value;
            
            // Get common fields
            const nameInput = document.getElementById('name');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');
            
            // Get role-specific fields
            const programmeField = document.getElementById('programmeField');
            const staffTypeField = document.getElementById('staffTypeField');
            const courseField = document.getElementById('courseField');
            const programmeSelect = document.getElementById('programme');
            const staffTypeSelect = document.getElementById('staffType');
            const courseSelect = document.getElementById('course');
            
            // If no role selected, disable all fields
            if (!role) {
                nameInput.disabled = true;
                emailInput.disabled = true;
                phoneInput.disabled = true;
                programmeField.style.display = 'none';
                staffTypeField.style.display = 'none';
                courseField.style.display = 'none';
                programmeSelect.removeAttribute('required');
                staffTypeSelect.removeAttribute('required');
                courseSelect.removeAttribute('required');
                return;
            }
            
            // Enable common fields when role is selected
            nameInput.disabled = false;
            emailInput.disabled = false;
            phoneInput.disabled = false;
            
            // Hide all role-specific fields first
            programmeField.style.display = 'none';
            staffTypeField.style.display = 'none';
            courseField.style.display = 'none';
            programmeSelect.removeAttribute('required');
            staffTypeSelect.removeAttribute('required');
            courseSelect.removeAttribute('required');
            
            // Show relevant fields based on role
            if (role === 'Student') {
                programmeField.style.display = 'block';
                programmeSelect.setAttribute('required', 'required');
            } else if (role === 'Staff') {
                staffTypeField.style.display = 'block';
                courseField.style.display = 'block';
                staffTypeSelect.setAttribute('required', 'required');
                courseSelect.setAttribute('required', 'required');
            }
        }
        
        // Form validation
        function validateForm() {
            // Clear any previous error highlights
            clearErrors();
            
            const role = document.getElementById('role').value.trim();
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            
            // Role validation
            if (!role) {
                showError('role', 'Role is required');
                return false;
            }
            
            // Full Name validation
            if (!name) {
                showError('name', 'Full name is required');
                return false;
            }
            
            // Check if name contains only alphabets and spaces
            const namePattern = /^[A-Za-z\s]+$/;
            if (!namePattern.test(name)) {
                showError('name', 'Only alphabets are allowed in name');
                return false;
            }
            
            // Check minimum name length
            if (name.length < 3) {
                showError('name', 'Name must be at least 3 characters long');
                return false;
            }
            
            // Email validation
            if (!email) {
                showError('email', 'Email address is required');
                return false;
            }
            
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                showError('email', 'Please enter a valid email address');
                return false;
            }
            
            // Phone validation (only if filled)
            if (phone) {
                // Remove dashes and spaces for validation
                const cleanPhone = phone.replace(/[\s-]/g, '');
                const phonePattern = /^[0-9]{10,12}$/;
                
                if (!phonePattern.test(cleanPhone)) {
                    showError('phone', 'Phone number must be 10-12 digits (e.g., 012-3456789)');
                    return false;
                }
            }
            
            // Role-specific validation
            if (role === 'Student') {
                const programme = document.getElementById('programme').value.trim();
                
                if (!programme) {
                    showError('programme', 'Programme is required for students');
                    return false;
                }
            } else if (role === 'Staff') {
                const staffType = document.getElementById('staffType').value.trim();
                const course = document.getElementById('course').value.trim();
                
                if (!staffType) {
                    showError('staffType', 'Staff type is required');
                    return false;
                }
                
                if (!course) {
                    showError('course', 'Assigned course is required for staff');
                    return false;
                }
            } else if (role === 'Admin') {
                const adminPhone = document.getElementById('phone').value.trim();
                
                if (!adminPhone) {
                    showError('phone', 'Phone number is required for admins');
                    return false;
                }
            }
            
            return true;
        }
        
        // Function to display error message
        function showError(fieldId, message) {
            const field = document.getElementById(fieldId);
            const fieldContainer = field.closest('.form-field');
            
            // Highlight the field
            field.style.borderColor = '#ff5f73';
            field.style.background = '#fff5f5';
            
            // Create and insert error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.style.color = '#ff5f73';
            errorDiv.style.fontSize = '12px';
            errorDiv.style.marginTop = '4px';
            errorDiv.style.fontWeight = '600';
            errorDiv.innerHTML = '<i class="fa-solid fa-exclamation-circle"></i> ' + message;
            
            fieldContainer.appendChild(errorDiv);
            
            // Scroll to the field
            field.scrollIntoView({ behavior: 'smooth', block: 'center' });
            field.focus();
        }
        
        // Function to clear all error messages
        function clearErrors() {
            // Remove all error messages
            const errorMessages = document.querySelectorAll('.error-message');
            errorMessages.forEach(msg => msg.remove());
            
            // Reset field styles
            const fields = document.querySelectorAll('.form-field input, .form-field select, .form-row input, .form-row select');
            fields.forEach(field => {
                field.style.borderColor = '#e0e0e0';
                field.style.background = field.disabled ? '#f5f5f5' : '#fdfdff';
            });
        }

        // Function to clear error for a specific field
        function clearFieldError(field) {
            const fieldContainer = field.closest('.form-field') || field.closest('.form-row');
            if (!fieldContainer) return;
            
            // Remove error message
            const errorMsg = fieldContainer.querySelector('.error-message');
            if (errorMsg) {
                errorMsg.remove();
            }
            
            // Reset field style
            field.style.borderColor = '#e0e0e0';
            field.style.background = field.disabled ? '#f5f5f5' : '#fdfdff';
        }

        // Edit User Modal Functions
        const modal = document.getElementById('userModal');

        function toggleFields() {
            const role = document.getElementById('inpRole').value;
            const lblExtra = document.getElementById('lblExtra');
            const groupRow = document.getElementById('groupFieldRow');
            
            // Get all three extra inputs
            const staffTypeSelect = document.getElementById('inpExtraStaffType');
            const programmeSelect = document.getElementById('inpExtraProgramme');
            const adminInput = document.getElementById('inpExtraAdmin');
            
            // Hide all extra inputs and disable their name attribute
            staffTypeSelect.style.display = 'none';
            staffTypeSelect.removeAttribute('name');
            programmeSelect.style.display = 'none';
            programmeSelect.removeAttribute('name');
            adminInput.style.display = 'none';
            adminInput.removeAttribute('name');

            if (role === 'Student') {
                lblExtra.innerText = 'Programme';
                programmeSelect.style.display = 'block';
                programmeSelect.setAttribute('name', 'extra');
                groupRow.style.display = 'block';
            } else if (role === 'Staff') {
                lblExtra.innerText = 'Staff Type';
                staffTypeSelect.style.display = 'block';
                staffTypeSelect.setAttribute('name', 'extra');
                groupRow.style.display = 'none';
            } else { // Admin
                lblExtra.innerText = 'Contact No';
                adminInput.style.display = 'block';
                adminInput.setAttribute('name', 'extra');
                groupRow.style.display = 'none';
            }
        }

        function openEdit(data) {
            // Clear any previous errors when opening the modal
            clearErrors();
            
            modal.classList.add('open');
            document.getElementById('inpId').value = data.childID || data.userID; 
            
            const roleSelect = document.getElementById('inpRole');
            roleSelect.value = data.role;
            
            document.getElementById('inpName').value = data.name;
            document.getElementById('inpEmail').value = data.email;
            
            // Set the correct extra field based on role
            if (data.role === 'Staff') {
                document.getElementById('inpExtraStaffType').value = data.extra || '';
            } else if (data.role === 'Student') {
                document.getElementById('inpExtraProgramme').value = data.extra || '';
            } else {
                document.getElementById('inpExtraAdmin').value = data.extra || '';
            }
            
            document.getElementById('inpGroup').value = data.grp || '';
            toggleFields();
        }

        function closeModal() {
            clearErrors();
            modal.classList.remove('open');
        }
        
        // Validation for edit form
        function validateEditForm() {
            // Clear any previous errors
            clearErrors();
            
            const role = document.getElementById('inpRole').value.trim();
            const name = document.getElementById('inpName').value.trim();
            const email = document.getElementById('inpEmail').value.trim();
            // Get the active extra field value based on role
            let extra = '';
            if (role === 'Staff') {
                extra = document.getElementById('inpExtraStaffType').value.trim();
            } else if (role === 'Student') {
                extra = document.getElementById('inpExtraProgramme').value.trim();
            } else {
                extra = document.getElementById('inpExtraAdmin').value.trim();
            }
            
            // Full Name validation
            if (!name) {
                showErrorModal('inpName', 'Full name is required');
                return false;
            }
            
            // Check if name contains only alphabets and spaces
            const namePattern = /^[A-Za-z\s]+$/;
            if (!namePattern.test(name)) {
                showErrorModal('inpName', 'Only alphabets are allowed in name');
                return false;
            }
            
            // Check minimum name length
            if (name.length < 3) {
                showErrorModal('inpName', 'Name must be at least 3 characters long');
                return false;
            }
            
            // Email validation
            if (!email) {
                showErrorModal('inpEmail', 'Email address is required');
                return false;
            }
            
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                showErrorModal('inpEmail', 'Please enter a valid email address');
                return false;
            }
            
            // Role-specific validation
            if (role === 'Student') {
                if (!extra) {
                    showErrorModal('inpExtraProgramme', 'Programme is required for students');
                    return false;
                }
            } else if (role === 'Staff') {
                if (!extra) {
                    showErrorModal('inpExtraStaffType', 'Staff type is required');
                    return false;
                }
            } else if (role === 'Admin') {
                if (!extra) {
                    showErrorModal('inpExtraAdmin', 'Phone number is required for admins');
                    return false;
                }
                
                // Validate phone format
                const cleanPhone = extra.replace(/[\s-]/g, '');
                const phonePattern = /^[0-9]{10,12}$/;
                
                if (!phonePattern.test(cleanPhone)) {
                    showErrorModal('inpExtraAdmin', 'Phone number must be 10-12 digits (e.g., 012-3456789)');
                    return false;
                }
            }
            
            return true;
        }
        
        // Function to display error message in modal
        function showErrorModal(fieldId, message) {
            const field = document.getElementById(fieldId);
            const fieldContainer = field.closest('.form-row');
            
            // Highlight the field
            field.style.borderColor = '#ff5f73';
            field.style.background = '#fff5f5';
            
            // Create and insert error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.style.color = '#ff5f73';
            errorDiv.style.fontSize = '12px';
            errorDiv.style.marginTop = '4px';
            errorDiv.style.fontWeight = '600';
            errorDiv.innerHTML = '<i class="fa-solid fa-exclamation-circle"></i> ' + message;
            
            fieldContainer.appendChild(errorDiv);
            
            // Focus on the field
            field.focus();
        }
    </script>
</body>
</html>