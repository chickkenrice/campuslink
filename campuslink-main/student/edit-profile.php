<?php
session_start();

// Handle AJAX Remove Photo Request FIRST (before any other output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_photo'])) {
    require_once '../includes/config.php';
    require_once '../includes/activity-logger.php';
    
    header('Content-Type: application/json');
    
    // Validate session
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        exit;
    }
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Security validation failed.']);
        exit;
    }
    
    $db = get_db_connection();
    $id = $_SESSION['user_id'];
    
    // Fetch current image
    $stmt = $db->prepare("SELECT studentImage FROM student WHERE studentID = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $currentImage = $result['studentImage'] ?? '';
    
    // Delete file if exists
    if (!empty($currentImage) && $currentImage !== 'default_avatar.png') {
        $filePath = '../uploads/profiles/' . $currentImage;
        if (file_exists($filePath)) {
            @unlink($filePath); // @ suppresses warnings
        }
    }
    
    // Update database to remove image
    $stmt = $db->prepare("UPDATE student SET studentImage = NULL WHERE studentID = ?");
    $stmt->bind_param("s", $id);
    
    if ($stmt->execute()) {
        logActivity($db, $id, 'PROFILE_UPDATE', 'Student removed their profile photo', []);
        echo json_encode(['success' => true, 'message' => 'Photo removed successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove photo.']);
    }
    exit;
}

require_once '../includes/config.php';
require_once '../includes/activity-logger.php'; 

if (!validate_session() || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$db = get_db_connection();
$id = $_SESSION['user_id'];
$message = '';
$messageType = ''; // 'success' or 'error'

// Fetch current data FIRST so we have the existing image name
$query = "SELECT s.*, d.dob, d.gender, d.icNo, d.homeAddress, d.corrAddress, d.parentName, d.parentContact 
          FROM student s 
          LEFT JOIN student_details d ON s.studentID = d.studentID 
          WHERE s.studentID = ?";
$stmt = $db->prepare($query);
if (!$stmt) { die('Database error: ' . $db->error); }
$stmt->bind_param("s", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    header("Location: student-profile.php");
    exit;
}

// Validation functions
function validatePhoneNumber($phone) {
    $phone = trim($phone);
    if (empty($phone)) return ['valid' => false, 'error' => 'Phone number cannot be empty'];
    $digits = preg_replace('/[^0-9]/', '', $phone);
    if (strpos($phone, '+60') === 0) $digits = substr($digits, 2);
    elseif (strpos($digits, '60') === 0 && strlen($digits) >= 11) $digits = substr($digits, 2);
    if (strlen($digits) < 9 || strlen($digits) > 11) return ['valid' => false, 'error' => 'Malaysian phone number should be 9-11 digits'];
    if ($digits[0] !== '0') return ['valid' => false, 'error' => 'Phone number must start with 0 or +60'];
    $validPrefixes = ['010', '011', '012', '013', '014', '015', '016', '017', '018', '019', '03', '04', '05', '06', '07', '08', '09'];
    $isValidPrefix = false;
    foreach ($validPrefixes as $prefix) {
        if (strpos($digits, $prefix) === 0) { $isValidPrefix = true; break; }
    }
    if (!$isValidPrefix) return ['valid' => false, 'error' => 'Invalid Malaysian phone prefix'];
    return ['valid' => true, 'value' => $phone];
}

function validateAddress($address) {
    $address = trim($address);
    if (strlen($address) > 500) return ['valid' => false, 'error' => 'Address is too long (max 500 characters)'];
    if (preg_match('/<script|<iframe|javascript:|onerror=/i', $address)) return ['valid' => false, 'error' => 'Address contains invalid content'];
    return ['valid' => true, 'value' => $address];
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Security validation failed. Please try again.';
        $messageType = 'error';
    } else {
        $newPhone = $_POST['contactNo'] ?? '';
        $newHome = $_POST['homeAddress'] ?? '';
        $newCorr = $_POST['corrAddress'] ?? '';
        $newParentPhone = $_POST['parentContact'] ?? '';
        
        $phoneValidation = validatePhoneNumber($newPhone);
        $homeValidation = validateAddress($newHome);
        $corrValidation = validateAddress($newCorr);
        $parentPhoneValidation = validatePhoneNumber($newParentPhone);
        
        if (!$phoneValidation['valid']) { $message = 'Your contact number: ' . htmlspecialchars($phoneValidation['error'], ENT_QUOTES, 'UTF-8'); $messageType = 'error'; } 
        elseif (!$homeValidation['valid']) { $message = 'Home address: ' . htmlspecialchars($homeValidation['error'], ENT_QUOTES, 'UTF-8'); $messageType = 'error'; } 
        elseif (!$corrValidation['valid']) { $message = 'Correspondence address: ' . htmlspecialchars($corrValidation['error'], ENT_QUOTES, 'UTF-8'); $messageType = 'error'; } 
        elseif (!$parentPhoneValidation['valid']) { $message = 'Parent contact number: ' . htmlspecialchars($parentPhoneValidation['error'], ENT_QUOTES, 'UTF-8'); $messageType = 'error'; } 
        else {
            $newPhone = $phoneValidation['value'];
            $newHome = $homeValidation['value'];
            $newCorr = $corrValidation['value'];
            $newParentPhone = $parentPhoneValidation['value'];
            
            // Handle Profile Picture Upload
            $newImage = $student['studentImage'] ?? 'default_avatar.png'; // Keep existing by default
            if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/profiles/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $ext = strtolower(pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION));
                $detectedMime = mime_content_type($_FILES['profileImage']['tmp_name']);
                $imageInfo    = @getimagesize($_FILES['profileImage']['tmp_name']);
                $allowedImgMimes = ['image/jpeg', 'image/png'];
                if (in_array($ext, ['jpg', 'jpeg', 'png'])
                    && $_FILES['profileImage']['size'] <= 2097152
                    && in_array($detectedMime, $allowedImgMimes)
                    && $imageInfo !== false) { // 2MB Limit + MIME + real image check
                    $newFileName = 'profile_' . $id . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $uploadDir . $newFileName)) {
                        // Delete old profile photo to prevent disk accumulation
                        $oldImage = $student['studentImage'] ?? '';
                        if (!empty($oldImage) && $oldImage !== 'default_avatar.png' && $oldImage !== $newFileName) {
                            $oldPath = $uploadDir . $oldImage;
                            if (file_exists($oldPath)) { @unlink($oldPath); }
                        }
                        $newImage = $newFileName;
                    }
                } else {
                    $message = "Invalid image format or size (Max 2MB, JPG/PNG only)."; 
                    $messageType = 'error';
                }
            }

            if(empty($messageType) || $messageType !== 'error') {
                $db->begin_transaction();
                try {
                    // Updated to include studentImage
                    $stmt1 = $db->prepare("UPDATE student SET contactNo = ?, studentImage = ? WHERE studentID = ?");
                    $stmt1->bind_param("sss", $newPhone, $newImage, $id);
                    $stmt1->execute();

                    $stmt2 = $db->prepare("UPDATE student_details SET homeAddress = ?, corrAddress = ?, parentContact = ? WHERE studentID = ?");
                    $stmt2->bind_param("ssss", $newHome, $newCorr, $newParentPhone, $id);
                    $stmt2->execute();

                    $db->commit();

                    $logUserID = $_SESSION['actual_user_id'] ?? $id;
                    logActivity($db, $logUserID, 'PROFILE_UPDATE', 'Student updated their profile', ['contactNo' => $newPhone]);

                    $message = "Profile updated successfully!";
                    $messageType = 'success';
                    
                    // Refresh data for display
                    $student['contactNo'] = $newPhone; $student['homeAddress'] = $newHome; 
                    $student['corrAddress'] = $newCorr; $student['parentContact'] = $newParentPhone;
                    $student['studentImage'] = $newImage;
                } catch (Exception $e) {
                    $db->rollback();
                    $message = "Error updating profile. Please try again later."; $messageType = 'error';
                }
            }
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Profile — CAMPUSLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        /* Existing Styles Kept Intact */
        .edit-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; position: relative; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px; }
        .form-group label .required { color: #ef4444; margin-left: 2px; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(202, 200, 240, 0.8); background: rgba(249, 247, 255, 0.95); font-family: inherit; font-size: 14px; transition: border-color 0.2s, box-shadow 0.2s; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: var(--purple-base); box-shadow: 0 0 0 3px rgba(128, 86, 255, 0.1); }
        .form-group input.is-invalid, .form-group textarea.is-invalid { border-color: #ef4444; background: #fef2f2; }
        .form-group input.is-valid, .form-group textarea.is-valid { border-color: #10b981; background: #f0fdf4; }
        .form-group input[readonly] { background: #f0f0f0; cursor: not-allowed; color: #888; }
        .form-group .field-error { font-size: 12px; color: #ef4444; margin-top: 5px; display: none; }
        .form-group .field-error.show { display: block; }
        .form-group .char-counter { font-size: 11px; color: #9ca3af; text-align: right; margin-top: 4px; }
        .form-group .char-counter.warning { color: #f59e0b; }
        .form-group .char-counter.danger { color: #ef4444; }
        .full-width { grid-column: span 2; }
        .alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; font-weight: 500; }
        .alert i { font-size: 18px; }
        .alert.alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert.alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .btn-submit { position: relative; min-width: 140px; }
        .btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }
        .btn-submit .btn-text { transition: opacity 0.2s; }
        .btn-submit .btn-loader { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; }
        .btn-submit.loading .btn-text { opacity: 0; }
        .btn-submit.loading .btn-loader { opacity: 1; }
        .spinner { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="app-bg">
        <div class="main-card">
            <aside class="sidebar" aria-label="Primary navigation">
                <div class="sidebar-head"><div class="brand-icon">CL</div><div class="brand-text"><span class="brand-name">CAMPUSLink</span><span class="brand-tagline">Student Portal</span></div></div>
                <nav class="sidebar-nav">
                    <ul>
                        <li><a href="../index.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-table-columns"></i></span><span class="nav-label">Dashboard</span></a></li>
                        <li><a href="student-attendance.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-clock"></i></span><span class="nav-label">Attendance</span></a></li>
                        <li><a href="student-assignments.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-file-pen"></i></span><span class="nav-label">Assignments</span></a></li>
                        <li><a href="student-timetable.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-calendar-days"></i></span><span class="nav-label">My Timetable</span></a></li>
                        <li><a href="resource-booking.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-building"></i></span><span class="nav-label">Resource Booking</span></a></li>
                        <li class="nav-group" data-expandable><button class="nav-item nav-toggle" type="button" data-target="programme-panel"><span class="nav-icon"><i class="fa-solid fa-clipboard-list"></i></span><span class="nav-label">Programme</span><span class="nav-chevron"></span></button><div class="nav-submenu" id="programme-panel" hidden><a href="programme-structure.php" class="nav-subitem">Programme Structure</a><a href="course-enrollment.php" class="nav-subitem">Course Enrollment</a><a href="#" class="nav-subitem">Results</a></div></li>
                        <li class="nav-group" data-expandable><button class="nav-item nav-toggle" type="button" data-target="examination-panel"><span class="nav-icon"><i class="fa-solid fa-book"></i></span><span class="nav-label">Examination</span><span class="nav-chevron"></span></button><div class="nav-submenu" id="examination-panel" hidden><a href="#" class="nav-subitem">Exam Slip</a><a href="#" class="nav-subitem">Exam Schedule</a></div></li>
                        <li><a href="../logout.php" class="nav-item" style="margin-top: 20px;"><span class="nav-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span><span class="nav-label" style="color: white;">Logout</span></a></li>
                    </ul>
                </nav>
            </aside>

            <main class="dashboard">
                <header class="dashboard-topbar">
                    <div class="topbar-right">
                        <div class="user-card">
                            <div class="user-info">
                                <span class="user-name"><?php echo htmlspecialchars($student['studentName'] ?? $_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="user-role">Student</span>
                            </div>
                            <a href="student-profile.php" class="profile-pic" title="View Profile">
                                <?php if (!empty($student['studentImage']) && $student['studentImage'] !== 'default_avatar.png'): ?>
                                    <img src="../uploads/profiles/<?php echo htmlspecialchars($student['studentImage']); ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover;">
                                <?php else: ?>
                                    <i class="fa-solid fa-user"></i>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </header>

                <div class="page-hero-card" style="
                    border-radius: 26px;
                    background: linear-gradient(140deg, #8056ff, #6c5ce7);
                    padding: 32px 36px;
                    color: white;
                    box-shadow: 0 28px 60px rgba(116, 88, 255, 0.35);
                    overflow: hidden;
                    position: relative;
                    margin-bottom: 28px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                ">
                    <!-- Decorative Background Elements -->
                    <div style="position: absolute; width: 160px; height: 160px; top: -40px; right: 180px; background: rgba(255,255,255,0.12); border-radius: 22px; transform: rotate(12deg);"></div>
                    <div style="position: absolute; width: 110px; height: 110px; top: 60px; right: 320px; background: rgba(255,255,255,0.08); border-radius: 22px; transform: rotate(12deg);"></div>
                    
                    <!-- Left Content -->
                    <div style="position: relative; z-index: 1;">
                        <h1 style="font-size: 30px; font-weight: 700; margin: 0 0 8px 0; color: white;">Edit My Profile</h1>
                        <p style="margin: 0; font-size: 14px; color: rgba(255,255,255,0.85);">Update your contact details and profile photo</p>
                    </div>
                    
                    <!-- Right: Action Button -->
                    <div style="position: relative; z-index: 1;">
                        <a href="student-profile.php" style="
                            display: inline-flex;
                            align-items: center;
                            gap: 8px;
                            padding: 12px 22px;
                            background: rgba(255,255,255,0.2);
                            color: white;
                            border-radius: 14px;
                            text-decoration: none;
                            font-weight: 600;
                            font-size: 14px;
                            border: 1px solid rgba(255,255,255,0.3);
                            transition: all 0.2s ease;
                        " onmouseover="this.style.background='rgba(255,255,255,0.3)';" onmouseout="this.style.background='rgba(255,255,255,0.2)';">
                            <i class="fa-solid fa-arrow-left"></i> Cancel
                        </a>
                    </div>
                </div>

                <div class="announcements-card" style="max-width: 800px; margin: 0 auto;">
                    <?php if($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?>">
                            <i class="fa-solid <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                            <span><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="editProfileForm" enctype="multipart/form-data" novalidate>
                        
                        <h3 style="margin-top: 0; color: var(--text-main); font-size: 16px;">Profile Picture</h3>
                        <div class="edit-grid" style="margin-bottom: 30px;">
                            <div class="form-group full-width" style="display: flex; align-items: center; gap: 20px; background: #fff; padding: 20px; border-radius: 16px; border: 1px dashed #cbd5e1;">
                                <div class="avatar-preview" id="avatarPreviewBox" style="width: 80px; height: 80px; border-radius: 50%; background: #e0e7ff; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 3px solid var(--purple-base); flex-shrink: 0;">
                                    <?php if (!empty($student['studentImage']) && $student['studentImage'] !== 'default_avatar.png'): ?>
                                        <img id="imgPreview" src="../uploads/profiles/<?php echo htmlspecialchars($student['studentImage']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <i id="defaultUserIcon" class="fa-solid fa-user" style="font-size: 32px; color: var(--purple-base);"></i>
                                        <img id="imgPreview" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                    <?php endif; ?>
                                </div>
                                <div style="flex-grow: 1;">
                                    <label style="color: var(--text-main);">Upload New Photo (JPG/PNG, Max 2MB)</label>
                                    <input type="file" name="profileImage" id="profileImage" accept="image/jpeg, image/png" class="form-input" style="background: white; cursor: pointer;">
                                    <div class="field-error" id="profileImage-error" style="font-size: 13px; font-weight: 600; margin-top: 6px;"></div>
                                    <small style="color: #8b5cf6; display: block; margin-top: 5px; font-weight: 600;"><i class="fa-solid fa-user-check"></i> Must contain exactly ONE human face.</small>
                                    <?php if (!empty($student['studentImage']) && $student['studentImage'] !== 'default_avatar.png'): ?>
                                    <button type="button" onclick="removeProfilePhoto()" class="btn-remove-photo" style="margin-top: 10px; background: #fee2e2; color: #dc2626; border: none; padding: 8px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s;">
                                        <i class="fa-solid fa-trash-can"></i> Remove Photo
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <h3 style="margin-top: 30px; color: var(--text-main); font-size: 16px;">Locked Academic Information</h3>
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

                        <h3 style="margin-top: 30px; color: var(--text-main); font-size: 16px;">Editable Contact Details</h3>
                        <div class="edit-grid">
                            <div class="form-group">
                                <label>Your Contact Number <span class="required">*</span></label>
                                <input type="tel" name="contactNo" id="contactNo" placeholder="e.g. 012-3456789" value="<?php echo htmlspecialchars($student['contactNo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                <div class="field-error" id="contactNo-error"></div>
                            </div>
                            <div class="form-group">
                                <label>Parent Contact <span class="required">*</span></label>
                                <input type="tel" name="parentContact" id="parentContact" placeholder="e.g. 012-3456789" value="<?php echo htmlspecialchars($student['parentContact'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                <div class="field-error" id="parentContact-error"></div>
                            </div>
                            <div class="form-group full-width">
                                <label>Home Address</label>
                                <textarea name="homeAddress" id="homeAddress" rows="3" maxlength="500" placeholder="Enter your home address"><?php echo htmlspecialchars($student['homeAddress'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <div class="char-counter"><span id="homeAddress-count">0</span>/500</div>
                                <div class="field-error" id="homeAddress-error"></div>
                            </div>
                            <div class="form-group full-width">
                                <label>Correspondence Address</label>
                                <textarea name="corrAddress" id="corrAddress" rows="3" maxlength="500" placeholder="Enter correspondence address (if different)"><?php echo htmlspecialchars($student['corrAddress'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <div class="char-counter"><span id="corrAddress-count">0</span>/500</div>
                                <div class="field-error" id="corrAddress-error"></div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                        <div style="margin-top: 30px; text-align: right;">
                            <button type="submit" class="btn btn-submit" id="submitBtn">
                                <span class="btn-text">Update Profile</span>
                                <span class="btn-loader"><span class="spinner"></span></span>
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar toggle functionality
        document.querySelectorAll('.nav-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                const targetPanel = document.getElementById(targetId);
                const isExpanded = btn.getAttribute('aria-expanded') === 'true';
                
                if (targetPanel) {
                    targetPanel.hidden = isExpanded;
                    btn.setAttribute('aria-expanded', !isExpanded);
                    btn.parentElement.classList.toggle('is-open', !isExpanded);
                }
            });
        });
        
        const form = document.getElementById('editProfileForm');
        const submitBtn = document.getElementById('submitBtn');
        let formChanged = false;

        // Track form changes for unsaved warning
        const inputs = form.querySelectorAll('input:not([readonly]):not([type="hidden"]), textarea');
        inputs.forEach(input => {
            input.addEventListener('input', () => { formChanged = true; });
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // --- NEW: FACE API LOGIC ---
        const imgInput = document.getElementById('profileImage');
        const imgPreview = document.getElementById('imgPreview');
        const defaultIcon = document.getElementById('defaultUserIcon');
        const imgError = document.getElementById('profileImage-error');
        let faceModelsLoaded = false;
        let isFaceValid = true; // True if no new image is selected

        // Load AI Models from public CDN (Required for Face-API)
        Promise.all([
            faceapi.nets.ssdMobilenetv1.loadFromUri('https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights')
        ]).then(() => {
            faceModelsLoaded = true;
            console.log("AI Face Models Loaded Ready");
        });

        imgInput.addEventListener('change', async function(e) {
            formChanged = true;
            
            if(!faceModelsLoaded) {
                imgInput.classList.add('is-invalid');
                imgError.textContent = "AI is still loading. Please wait 2 seconds and select again.";
                imgError.classList.add('show');
                isFaceValid = false;
                return;
            }
            
            const file = e.target.files[0];
            if(!file) {
                isFaceValid = true; 
                imgInput.classList.remove('is-invalid');
                imgError.classList.remove('show');
                return;
            }

            // Show Preview Immediately
            const tempUrl = URL.createObjectURL(file);
            if(defaultIcon) defaultIcon.style.display = 'none';
            imgPreview.style.display = 'block';
            imgPreview.src = tempUrl;

            // Start AI Scan
            imgInput.classList.remove('is-valid', 'is-invalid');
            imgError.textContent = "Scanning image for a human face...";
            imgError.style.color = "#f59e0b"; // Orange Warning
            imgError.classList.add('show');
            submitBtn.disabled = true;

            try {
                const image = await faceapi.bufferToImage(file);
                const detections = await faceapi.detectAllFaces(image);

                if(detections.length === 0) {
                    imgInput.classList.add('is-invalid');
                    imgError.textContent = "❌ Rejected: No human face detected. Please upload a clear picture of your face.";
                    imgError.style.color = "#ef4444";
                    isFaceValid = false;
                } else if (detections.length > 1) {
                    imgInput.classList.add('is-invalid');
                    imgError.textContent = "❌ Rejected: Multiple faces detected. Please upload a solo picture.";
                    imgError.style.color = "#ef4444";
                    isFaceValid = false;
                } else {
                    imgInput.classList.add('is-valid');
                    imgError.innerHTML = "✅ Approved: Perfect! One face detected.";
                    imgError.style.color = "#10b981";
                    isFaceValid = true;
                    submitBtn.disabled = false;
                }
            } catch (err) {
                console.error(err);
                imgError.textContent = "Error scanning image. Try a different format.";
                imgError.style.color = "#ef4444";
                isFaceValid = false;
            }
        });
        // --- END FACE API LOGIC ---

        // Existing Validations (Unchanged)
        const validMobilePrefixes = ['010', '011', '012', '013', '014', '015', '016', '017', '018', '019'];
        const validLandlinePrefixes = ['03', '04', '05', '06', '07', '08', '09'];
        const allPrefixes = [...validMobilePrefixes, ...validLandlinePrefixes];

        function validatePhone(input) {
            const value = input.value.trim();
            const errorEl = document.getElementById(input.id + '-error');
            if (!value) { setInvalid(input, errorEl, 'Phone number is required'); return false; }
            let digits = value.replace(/[^0-9]/g, '');
            if (value.startsWith('+60')) digits = digits.substring(2);
            else if (digits.startsWith('60') && digits.length >= 11) digits = digits.substring(2);
            if (digits.length < 9 || digits.length > 11) { setInvalid(input, errorEl, 'Malaysian phone: 9-11 digits (e.g. 012-3456789)'); return false; }
            if (digits[0] !== '0') { setInvalid(input, errorEl, 'Must start with 0 or +60'); return false; }
            let hasValidPrefix = allPrefixes.some(prefix => digits.startsWith(prefix));
            if (!hasValidPrefix) { setInvalid(input, errorEl, 'Invalid Malaysian phone prefix'); return false; }
            setValid(input, errorEl);
            return true;
        }

        function validateAddress(input) {
            const value = input.value.trim();
            const errorEl = document.getElementById(input.id + '-error');
            if (value.length > 500) { setInvalid(input, errorEl, 'Address is too long (max 500 characters)'); return false; }
            if (/<script|<iframe|javascript:|onerror=/i.test(value)) { setInvalid(input, errorEl, 'Address contains invalid content'); return false; }
            setValid(input, errorEl);
            return true;
        }

        function setInvalid(input, errorEl, message) {
            input.classList.remove('is-valid'); input.classList.add('is-invalid');
            errorEl.textContent = message; errorEl.classList.add('show');
        }

        function setValid(input, errorEl) {
            input.classList.remove('is-invalid'); input.classList.add('is-valid');
            errorEl.textContent = ''; errorEl.classList.remove('show');
        }

        function updateCharCounter(textarea) {
            const count = textarea.value.length;
            const countEl = document.getElementById(textarea.id + '-count');
            const counterEl = countEl.parentElement;
            countEl.textContent = count;
            counterEl.classList.remove('warning', 'danger');
            if (count >= 450) counterEl.classList.add('danger');
            else if (count >= 400) counterEl.classList.add('warning');
        }

        ['contactNo', 'parentContact'].forEach(id => {
            const input = document.getElementById(id);
            input.addEventListener('blur', () => validatePhone(input));
            input.addEventListener('input', () => { if (input.classList.contains('is-invalid')) validatePhone(input); });
        });

        ['homeAddress', 'corrAddress'].forEach(id => {
            const textarea = document.getElementById(id);
            updateCharCounter(textarea);
            textarea.addEventListener('input', () => { updateCharCounter(textarea); if (textarea.classList.contains('is-invalid')) validateAddress(textarea); });
            textarea.addEventListener('blur', () => validateAddress(textarea));
        });

        // Updated Form submission to check Face validity
        form.addEventListener('submit', function(e) {
            let isValid = true;
            isValid = validatePhone(document.getElementById('contactNo')) && isValid;
            isValid = validatePhone(document.getElementById('parentContact')) && isValid;
            isValid = validateAddress(document.getElementById('homeAddress')) && isValid;
            isValid = validateAddress(document.getElementById('corrAddress')) && isValid;

            if (!isValid || !isFaceValid) {
                e.preventDefault();
                const firstError = form.querySelector('.is-invalid');
                if (firstError) firstError.focus();
                
                // Show shake animation on submit button if face is invalid
                if(!isFaceValid) {
                    submitBtn.style.animation = 'shake 0.5s';
                    setTimeout(() => submitBtn.style.animation = '', 500);
                }
                return;
            }

            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            formChanged = false; 
        });
    });
    
    // Remove Profile Photo Function
    function removeProfilePhoto() {
        if (!confirm('Are you sure you want to remove your profile photo?')) return;
        
        const formData = new FormData();
        formData.append('remove_photo', '1');
        formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
        
        fetch('edit-profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload(); // Reload to update all photos
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
    </script>
    <style>
        @keyframes shake { 0%, 100% {transform: translateX(0);} 25% {transform: translateX(-5px);} 75% {transform: translateX(5px);} }
        .btn-remove-photo:hover { background: #dc2626 !important; color: white !important; }
    </style>
</body>
</html>