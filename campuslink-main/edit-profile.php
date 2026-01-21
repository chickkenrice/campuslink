<?php
session_start();
require_once 'includes/config.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$db = get_db_connection();
$id = $_SESSION['user_id'];
$message = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPhone = $_POST['contactNo'];
    $newHome = $_POST['homeAddress'];
    $newCorr = $_POST['corrAddress'];
    $newParentPhone = $_POST['parentContact'];

    $db->begin_transaction();
    try {
        // Update main student table
        $stmt1 = $db->prepare("UPDATE student SET contactNo = ? WHERE studentID = ?");
        $stmt1->bind_param("ss", $newPhone, $id);
        $stmt1->execute();

        // Update student_details table
        $stmt2 = $db->prepare("UPDATE student_details SET homeAddress = ?, corrAddress = ?, parentContact = ? WHERE studentID = ?");
        $stmt2->bind_param("ssss", $newHome, $newCorr, $newParentPhone, $id);
        $stmt2->execute();

        $db->commit();
        $message = "Profile updated successfully!";
    } catch (Exception $e) {
        $db->rollback();
        $message = "Error updating profile: " . $e->getMessage();
    }
}

// Fetch current data
$query = "SELECT s.*, d.dob, d.gender, d.icNo, d.homeAddress, d.corrAddress, d.parentName, d.parentContact 
          FROM student s 
          LEFT JOIN student_details d ON s.studentID = d.studentID 
          WHERE s.studentID = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("s", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Profile — CAMPUSLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
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
            <aside class="sidebar">
                <div class="sidebar-head">
                    <div class="brand-icon">CL</div>
                    <div class="brand-text"><span class="brand-name">CAMPUSLink</span></div>
                </div>
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
                                <input type="text" value="<?php echo $student['studentID']; ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Programme</label>
                                <input type="text" value="<?php echo $student['programme']; ?>" readonly>
                            </div>
                        </div>

                        <h3 style="margin-top: 30px;">Editable Contact Details</h3>
                        <div class="edit-grid">
                            <div class="form-group">
                                <label>Your Contact Number</label>
                                <input type="text" name="contactNo" value="<?php echo $student['contactNo']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Parent Contact</label>
                                <input type="text" name="parentContact" value="<?php echo $student['parentContact']; ?>" required>
                            </div>
                            <div class="form-group full-width">
                                <label>Home Address</label>
                                <textarea name="homeAddress" rows="3"><?php echo $student['homeAddress']; ?></textarea>
                            </div>
                            <div class="form-group full-width">
                                <label>Correspondence Address</label>
                                <textarea name="corrAddress" rows="3"><?php echo $student['corrAddress']; ?></textarea>
                            </div>
                        </div>

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