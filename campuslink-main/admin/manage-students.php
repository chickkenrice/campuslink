<?php
session_start();
require_once '../includes/config.php';

// 1. SECURITY CHECK
// Only allow 'admin' role to access this page.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$db = get_db_connection();
$activeTab = $_GET['tab'] ?? 'all';
$message = '';

// =========================================================
// DATABASE OPERATIONS (This part updates your DB)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    // --- DELETE USER ---
    if ($action === 'delete') {
        $id = $_POST['id'];
        // Because we set "ON DELETE CASCADE" in SQL, deleting the User 
        // automatically deletes their profile in Student/Staff/Admin table.
        $stmt = $db->prepare("DELETE FROM users WHERE userID = ?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) {
            $message = "User $id has been deleted.";
        } else {
            $message = "Error deleting user: " . $db->error;
        }
    }

    // --- CREATE USER ---
    elseif ($action === 'create') {
        $id = $_POST['id'];
        $role = $_POST['role']; // Values: 'Student', 'Staff', 'Admin'
        $name = $_POST['name'];
        $email = $_POST['email'];
        $extra = $_POST['extra'] ?? ''; // Holds Programme, Dept, or Phone
        $group = $_POST['group'] ?? ''; // Only for Students

        // 1. Insert into Parent Table (USERS)
        // We use INSERT IGNORE so if ID exists, we don't crash (but profile insert might fail)
        $db->query("INSERT IGNORE INTO users (userID, role) VALUES ('$id', '$role')");

        // 2. Insert into Child Table (PROFILE)
        if ($role === 'Student') {
            $stmt = $db->prepare("INSERT INTO student (studentID, userID, studentName, email, programme, tutGroup) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $id, $id, $name, $email, $extra, $group);
        } elseif ($role === 'Staff') {
            $stmt = $db->prepare("INSERT INTO staff (staffID, userID, staffName, email, department) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $id, $id, $name, $email, $extra);
        } elseif ($role === 'Admin') {
            $stmt = $db->prepare("INSERT INTO admin (adminID, userID, adminName, email, contactNo) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $id, $id, $name, $email, $extra);
        }

        if (isset($stmt) && $stmt->execute()) {
            $message = "New $role added successfully.";
        } else {
            $message = "Error adding user (ID might already exist).";
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

        // Update the specific profile table based on role
        if ($role === 'Student') {
            $stmt = $db->prepare("UPDATE student SET studentName=?, email=?, programme=?, tutGroup=? WHERE studentID=?");
            $stmt->bind_param("sssss", $name, $email, $extra, $group, $id);
        } elseif ($role === 'Staff') {
            $stmt = $db->prepare("UPDATE staff SET staffName=?, email=?, department=? WHERE staffID=?");
            $stmt->bind_param("ssss", $name, $email, $extra, $id);
        } elseif ($role === 'Admin') {
            $stmt = $db->prepare("UPDATE admin SET adminName=?, email=?, contactNo=? WHERE adminID=?");
            $stmt->bind_param("ssss", $name, $email, $extra, $id);
        }

        if (isset($stmt) && $stmt->execute()) {
            $message = "User details updated successfully.";
        } else {
            $message = "Error updating user.";
        }
    }
}

// =========================================================
// FETCH DATA FOR DISPLAY
// =========================================================
if ($activeTab === 'student') {
    $sql = "SELECT studentID as id, studentName as name, email, 'Student' as role, programme as extra, tutGroup as grp FROM student ORDER BY name";
} elseif ($activeTab === 'staff') {
    $sql = "SELECT staffID as id, staffName as name, email, 'Staff' as role, department as extra, '' as grp FROM staff ORDER BY name";
} elseif ($activeTab === 'admin') {
    $sql = "SELECT adminID as id, adminName as name, email, 'Admin' as role, contactNo as extra, '' as grp FROM admin ORDER BY name";
} else {
    // 'ALL' TAB: Master Query combining all tables
    $sql = "SELECT u.userID as id, u.role,
            COALESCE(s.studentName, st.staffName, a.adminName) as name,
            COALESCE(s.email, st.email, a.email) as email,
            COALESCE(s.programme, st.department, a.contactNo) as extra,
            COALESCE(s.tutGroup, '') as grp
            FROM users u
            LEFT JOIN student s ON u.userID = s.userID
            LEFT JOIN staff st ON u.userID = st.userID
            LEFT JOIN admin a ON u.userID = a.userID
            ORDER BY u.role, name";
}
$result = $db->query($sql);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Console — Manage Users</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/manage-students.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <style>
        /* Small internal overrides if needed */
        .tab-nav { display: flex; gap: 8px; margin-bottom: 25px; border-bottom: 2px solid #f0f0f0; padding-bottom: 12px; }
        .tab-btn { 
            padding: 8px 20px; border-radius: 20px; border: none; font-weight: 600; font-size: 14px;
            cursor: pointer; text-decoration: none; color: var(--text-sub); background: transparent; 
            transition: all 0.2s;
        }
        .tab-btn:hover { background: #f5f5ff; color: var(--purple-base); }
        .tab-btn.active { background: var(--purple-base); color: white; box-shadow: 0 4px 12px rgba(128,86,255,0.25); }

        .action-btn { width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; color: white; display: inline-flex; align-items: center; justify-content: center; margin-right: 4px; transition: transform 0.1s; }
        .action-btn:hover { transform: translateY(-2px); }
        .btn-edit { background: #4a90e2; }
        .btn-delete { background: #ff5f73; }

        .modal { display: none; position: fixed; inset: 0; background: rgba(24,27,47,0.5); backdrop-filter: blur(2px); align-items: center; justify-content: center; z-index: 999; }
        .modal.open { display: flex; animation: fadeIn 0.2s; }
        .modal-content { background: white; padding: 32px; border-radius: 24px; width: 100%; max-width: 450px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); position: relative; }
        .close-modal { position: absolute; top: 20px; right: 24px; font-size: 24px; cursor: pointer; color: #aaa; }
        
        .form-row { margin-bottom: 16px; }
        .form-row label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 700; color: var(--text-main); }
        .form-row input, .form-row select { width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 10px; font-size: 14px; background: #fdfdff; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
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
                        <li><a href="#" class="nav-item is-active"><span class="nav-icon"><i class="fa-solid fa-users-gear"></i></span> User Management</a></li>
                        <li><a href="login.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span> Logout</a></li>
                    </ul>
                </nav>
            </aside>

            <main class="dashboard">
                <header class="dashboard-topbar">
                    <div>
                        <h1 class="welcome-title" style="font-size: 26px;">User Management</h1>
                        <p style="color: var(--text-sub); font-size: 14px; margin-top: 4px;">Manage accounts for Students, Staff, and Admins</p>
                    </div>
                    <div class="topbar-right">
                        <button onclick="openModal('create')" class="btn"><i class="fa-solid fa-plus"></i> Add User</button>
                    </div>
                </header>

                <?php if($message): ?>
                    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size:14px; border: 1px solid #c3e6cb;">
                        <i class="fa-solid fa-check-circle"></i> <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <nav class="tab-nav">
                    <a href="?tab=all" class="tab-btn <?php echo $activeTab == 'all' ? 'active' : ''; ?>">All Users</a>
                    <a href="?tab=student" class="tab-btn <?php echo $activeTab == 'student' ? 'active' : ''; ?>">Students</a>
                    <a href="?tab=staff" class="tab-btn <?php echo $activeTab == 'staff' ? 'active' : ''; ?>">Staff</a>
                    <a href="?tab=admin" class="tab-btn <?php echo $activeTab == 'admin' ? 'active' : ''; ?>">Admins</a>
                </nav>

                <section class="announcements-card">
                    <div class="table-container">
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Role</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>
                                        <?php 
                                        if ($activeTab == 'student') echo 'Programme';
                                        elseif ($activeTab == 'staff') echo 'Department';
                                        elseif ($activeTab == 'admin') echo 'Contact No';
                                        else echo 'Details';
                                        ?>
                                    </th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($result && $result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="id-cell"><?php echo $row['id']; ?></td>
                                        <td>
                                            <?php 
                                            $badgeColor = '#f3e5ff'; // Purple (Student)
                                            $textColor = '#8056ff';
                                            if($row['role'] == 'Admin') { $badgeColor = '#ffe5e5'; $textColor = '#ff5f73'; } // Red
                                            if($row['role'] == 'Staff') { $badgeColor = '#e5f6ff'; $textColor = '#00a8ff'; } // Blue
                                            ?>
                                            <span class="badge-prog" style="background: <?php echo $badgeColor; ?>; color: <?php echo $textColor; ?>;">
                                                <?php echo $row['role']; ?>
                                            </span>
                                        </td>
                                        <td class="name-cell"><?php echo $row['name']; ?></td>
                                        <td><?php echo $row['email']; ?></td>
                                        <td>
                                            <?php 
                                                echo $row['extra']; 
                                                if($row['grp']) echo " <span style='color:#aaa; font-size:12px;'>(" . $row['grp'] . ")</span>";
                                            ?>
                                        </td>
                                        <td>
                                            <?php if($row['id'] !== $_SESSION['user_id']): ?>
                                                <button class="action-btn btn-edit" onclick='openEdit(<?php echo json_encode($row); ?>)'>
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="action-btn btn-delete"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            <?php else: ?>
                                                <span style="font-size:12px; color:#aaa;">(Current)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" style="text-align:center; padding: 30px; color:#999;">No records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <div id="userModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle" style="margin-top:0;">Add User</h2>
            
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="create">
                
                <div class="form-row">
                    <label>Role</label>
                    <select name="role" id="inpRole" onchange="toggleFields()" required>
                        <option value="Student">Student</option>
                        <option value="Staff">Staff</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>

                <div class="form-row">
                    <label>User ID</label>
                    <input type="text" name="id" id="inpId" placeholder="e.g. 23WP... or ADMIN01" required>
                </div>

                <div class="form-row">
                    <label>Full Name</label>
                    <input type="text" name="name" id="inpName" required>
                </div>

                <div class="form-row">
                    <label>Email</label>
                    <input type="email" name="email" id="inpEmail" required>
                </div>

                <div class="form-row">
                    <label id="lblExtra">Programme</label>
                    <input type="text" name="extra" id="inpExtra" placeholder="e.g. RSD">
                </div>

                <div class="form-row" id="groupFieldRow">
                    <label>Tutorial Group</label>
                    <input type="text" name="group" id="inpGroup" placeholder="e.g. Group A">
                </div>

                <div style="margin-top:24px;">
                    <button type="submit" class="btn" style="width:100%;">Save User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('userModal');

        function toggleFields() {
            const role = document.getElementById('inpRole').value;
            const lblExtra = document.getElementById('lblExtra');
            const inpExtra = document.getElementById('inpExtra');
            const groupRow = document.getElementById('groupFieldRow');

            if (role === 'Student') {
                lblExtra.innerText = 'Programme';
                inpExtra.placeholder = 'e.g. RSD';
                groupRow.style.display = 'block';
            } else if (role === 'Staff') {
                lblExtra.innerText = 'Department';
                inpExtra.placeholder = 'e.g. IT Support';
                groupRow.style.display = 'none';
            } else { // Admin
                lblExtra.innerText = 'Contact No';
                inpExtra.placeholder = 'e.g. 012-3456789';
                groupRow.style.display = 'none';
            }
        }

        function openModal(mode) {
            modal.classList.add('open');
            document.getElementById('formAction').value = mode;
            document.getElementById('modalTitle').innerText = mode === 'create' ? 'Add New User' : 'Edit User';
            
            if(mode === 'create') {
                document.getElementById('inpId').value = '';
                document.getElementById('inpId').readOnly = false;
                document.getElementById('inpRole').disabled = false;
                document.getElementById('inpName').value = '';
                document.getElementById('inpEmail').value = '';
                document.getElementById('inpExtra').value = '';
                document.getElementById('inpGroup').value = '';
                toggleFields();
            }
        }

        function openEdit(data) {
            openModal('update');
            document.getElementById('inpId').value = data.id;
            document.getElementById('inpId').readOnly = true; 
            document.getElementById('inpRole').value = data.role;
            // document.getElementById('inpRole').disabled = true; // Optional: Lock role
            
            document.getElementById('inpName').value = data.name;
            document.getElementById('inpEmail').value = data.email;
            document.getElementById('inpExtra').value = data.extra;
            document.getElementById('inpGroup').value = data.grp;
            toggleFields();
        }

        function closeModal() {
            modal.classList.remove('open');
        }

        toggleFields(); 
    </script>
</body>
</html>