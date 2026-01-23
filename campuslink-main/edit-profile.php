<?php
session_start();
require_once 'includes/config.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$db = get_db_connection();
$id = $_SESSION['user_id'];
$message = '';

// Validation functions
function validatePhoneNumber($phone) {
    $phone = trim($phone);
    // Malaysian phone format: 010-12345678, +60101234567, 01012345678, etc.
    if (empty($phone)) {
        return ['valid' => false, 'error' => 'Phone number cannot be empty'];
    }
    if (strlen($phone) > 20) {
        return ['valid' => false, 'error' => 'Phone number is too long'];
    }
    if (!preg_match('/^[0-9\s\-\+()]{8,20}$/', $phone)) {
        return ['valid' => false, 'error' => 'Phone number contains invalid characters'];
    }
    return ['valid' => true, 'value' => $phone];
}

function validateAddress($address) {
    $address = trim($address);
    if (strlen($address) > 500) {
        return ['valid' => false, 'error' => 'Address is too long (max 500 characters)'];
    }
    // Remove potential XSS characters but allow normal address text
    if (preg_match('/<script|<iframe|javascript:|onerror=/i', $address)) {
        return ['valid' => false, 'error' => 'Address contains invalid content'];
    }
    return ['valid' => true, 'value' => $address];
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Security validation failed. Please try again.';
    } else {
        $newPhone = $_POST['contactNo'] ?? '';
        $newHome = $_POST['homeAddress'] ?? '';
        $newCorr = $_POST['corrAddress'] ?? '';
        $newParentPhone = $_POST['parentContact'] ?? '';
        
        // Validate all inputs
        $phoneValidation = validatePhoneNumber($newPhone);
        $homeValidation = validateAddress($newHome);
        $corrValidation = validateAddress($newCorr);
        $parentPhoneValidation = validatePhoneNumber($newParentPhone);
        
        if (!$phoneValidation['valid']) {
            $message = 'Your contact number: ' . htmlspecialchars($phoneValidation['error'], ENT_QUOTES, 'UTF-8');
        } elseif (!$homeValidation['valid']) {
            $message = 'Home address: ' . htmlspecialchars($homeValidation['error'], ENT_QUOTES, 'UTF-8');
        } elseif (!$corrValidation['valid']) {
            $message = 'Correspondence address: ' . htmlspecialchars($corrValidation['error'], ENT_QUOTES, 'UTF-8');
        } elseif (!$parentPhoneValidation['valid']) {
            $message = 'Parent contact number: ' . htmlspecialchars($parentPhoneValidation['error'], ENT_QUOTES, 'UTF-8');
        } else {
            // All validations passed, proceed with update
            $newPhone = $phoneValidation['value'];
            $newHome = $homeValidation['value'];
            $newCorr = $corrValidation['value'];
            $newParentPhone = $parentPhoneValidation['value'];

            $db->begin_transaction();
            try {
                // Update main student table
                $stmt1 = $db->prepare("UPDATE student SET contactNo = ? WHERE studentID = ?");
                if (!$stmt1) {
                    throw new Exception('Database error: ' . $db->error);
                }
                $stmt1->bind_param("ss", $newPhone, $id);
                if (!$stmt1->execute()) {
                    throw new Exception('Failed to update contact number: ' . $stmt1->error);
                }

                // Update student_details table
                $stmt2 = $db->prepare("UPDATE student_details SET homeAddress = ?, corrAddress = ?, parentContact = ? WHERE studentID = ?");
                if (!$stmt2) {
                    throw new Exception('Database error: ' . $db->error);
                }
                $stmt2->bind_param("ssss", $newHome, $newCorr, $newParentPhone, $id);
                if (!$stmt2->execute()) {
                    throw new Exception('Failed to update address details: ' . $stmt2->error);
                }

                $db->commit();
                $message = "Profile updated successfully!";
            } catch (Exception $e) {
                $db->rollback();
                $message = "Error updating profile. Please try again later.";
                // Log error for debugging (not shown to user)
                error_log('Edit profile error for user ' . $id . ': ' . $e->getMessage());
            }
        }
    }
}

// Fetch current data
$query = "SELECT s.*, d.dob, d.gender, d.icNo, d.homeAddress, d.corrAddress, d.parentName, d.parentContact 
          FROM student s 
          LEFT JOIN student_details d ON s.studentID = d.studentID 
          WHERE s.studentID = ?";
$stmt = $db->prepare($query);
if (!$stmt) {
    die('Database error: ' . $db->error);
}
$stmt->bind_param("s", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Verify student exists
if (!$student) {
    header("Location: student-profile.php");
    exit;
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Profile — CAMPUSLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .edit-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px; }
        .form-group input, .form-group textarea { 
            width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(202, 200, 240, 0.8); 
            background: rgba(249, 247, 255, 0.95); font-family: inherit; font-size: 14px;
        }
        .form-group input[readonly] { background: #f0f0f0; cursor: not-allowed; color: #888; }
        .full-width { grid-column: span 2; }
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
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
                            <a href="attendance.php" class="nav-item" style="text-decoration: none;">
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
                    <h1 class="welcome-title" style="color: var(--text-main);">Edit My Profile</h1>
                    <a href="student-profile.php" class="btn" style="background: var(--text-muted);">Cancel</a>
                </header>

                <div class="announcements-card" style="max-width: 800px; margin: 0 auto;">
                    <?php if($message): ?> <div class="alert"><?php echo $message; ?></div> <?php endif; ?>
                    
                    <form method="POST">
                        <h3>Locked Academic Information</h3>
                        <div class="edit-grid">
                            <div class="form-group">
                                <label>Student ID</label>
                                <input type="text" value="<?php echo htmlspecialchars($student['studentID'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Programme</label>
                                <input type="text" value="<?php echo htmlspecialchars($student['programID'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" readonly>
                            </div>
                        </div>

                        <h3 style="margin-top: 30px;">Editable Contact Details</h3>
                        <div class="edit-grid">
                            <div class="form-group">
                                <label>Your Contact Number</label>
                                <input type="text" name="contactNo" value="<?php echo htmlspecialchars($student['contactNo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Parent Contact</label>
                                <input type="text" name="parentContact" value="<?php echo htmlspecialchars($student['parentContact'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="form-group full-width">
                                <label>Home Address</label>
                                <textarea name="homeAddress" rows="3"><?php echo htmlspecialchars($student['homeAddress'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            <div class="form-group full-width">
                                <label>Correspondence Address</label>
                                <textarea name="corrAddress" rows="3"><?php echo htmlspecialchars($student['corrAddress'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>
                        
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                        <div style="margin-top: 30px; text-align: right;">
                            <button type="submit" class="btn">Update Profile</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>
</html>