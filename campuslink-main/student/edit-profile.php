<?php
session_start();
require_once '../includes/config.php'; 

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

// Validation functions
function validatePhoneNumber($phone) {
    $phone = trim($phone);
    
    if (empty($phone)) {
        return ['valid' => false, 'error' => 'Phone number cannot be empty'];
    }
    
    // Remove all formatting characters for validation
    $digits = preg_replace('/[^0-9]/', '', $phone);
    
    // Malaysian phone validation:
    // Mobile: 01X-XXXXXXX or 01X-XXXXXXXX (10-11 digits)
    // Landline: 0X-XXXXXXXX (9-10 digits)
    // International: +60XXXXXXXXX (11-12 digits after +60)
    
    // Check if starts with +60 (international format)
    if (strpos($phone, '+60') === 0) {
        $digits = substr($digits, 2); // Remove 60, keep rest
    } elseif (strpos($digits, '60') === 0 && strlen($digits) >= 11) {
        $digits = substr($digits, 2); // Remove 60 prefix
    }
    
    // Now digits should start with 0 and be 9-11 digits
    if (strlen($digits) < 9 || strlen($digits) > 11) {
        return ['valid' => false, 'error' => 'Malaysian phone number should be 9-11 digits'];
    }
    
    // Must start with 0
    if ($digits[0] !== '0') {
        return ['valid' => false, 'error' => 'Phone number must start with 0 or +60'];
    }
    
    // Valid Malaysian mobile prefixes: 010, 011, 012, 013, 014, 015, 016, 017, 018, 019
    // Valid landline prefixes: 03 (KL), 04 (Penang), 05 (Perak), 06 (Melaka), 07 (Johor), 08 (Sabah), 09 (Kelantan)
    $validPrefixes = ['010', '011', '012', '013', '014', '015', '016', '017', '018', '019', '03', '04', '05', '06', '07', '08', '09'];
    $isValidPrefix = false;
    foreach ($validPrefixes as $prefix) {
        if (strpos($digits, $prefix) === 0) {
            $isValidPrefix = true;
            break;
        }
    }
    
    if (!$isValidPrefix) {
        return ['valid' => false, 'error' => 'Invalid Malaysian phone prefix'];
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
        $messageType = 'error';
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
            $messageType = 'error';
        } elseif (!$homeValidation['valid']) {
            $message = 'Home address: ' . htmlspecialchars($homeValidation['error'], ENT_QUOTES, 'UTF-8');
            $messageType = 'error';
        } elseif (!$corrValidation['valid']) {
            $message = 'Correspondence address: ' . htmlspecialchars($corrValidation['error'], ENT_QUOTES, 'UTF-8');
            $messageType = 'error';
        } elseif (!$parentPhoneValidation['valid']) {
            $message = 'Parent contact number: ' . htmlspecialchars($parentPhoneValidation['error'], ENT_QUOTES, 'UTF-8');
            $messageType = 'error';
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
                $messageType = 'success';
            } catch (Exception $e) {
                $db->rollback();
                $message = "Error updating profile. Please try again later.";
                $messageType = 'error';
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
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .edit-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; position: relative; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px; }
        .form-group label .required { color: #ef4444; margin-left: 2px; }
        .form-group input, .form-group textarea { 
            width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(202, 200, 240, 0.8); 
            background: rgba(249, 247, 255, 0.95); font-family: inherit; font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none; border-color: var(--purple-base); box-shadow: 0 0 0 3px rgba(128, 86, 255, 0.1);
        }
        .form-group input.is-invalid, .form-group textarea.is-invalid {
            border-color: #ef4444; background: #fef2f2;
        }
        .form-group input.is-valid, .form-group textarea.is-valid {
            border-color: #10b981; background: #f0fdf4;
        }
        .form-group input[readonly] { background: #f0f0f0; cursor: not-allowed; color: #888; }
        .form-group .field-error { font-size: 12px; color: #ef4444; margin-top: 5px; display: none; }
        .form-group .field-error.show { display: block; }
        .form-group .char-counter { font-size: 11px; color: #9ca3af; text-align: right; margin-top: 4px; }
        .form-group .char-counter.warning { color: #f59e0b; }
        .form-group .char-counter.danger { color: #ef4444; }
        .full-width { grid-column: span 2; }
        
        /* Alert styles */
        .alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; font-weight: 500; }
        .alert i { font-size: 18px; }
        .alert.alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert.alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        
        /* Submit button states */
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
                            <a href="../index.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-table-columns"></i></span>
                                <span class="nav-label">Dashboard</span>
                            </a>
                        </li>

                        <li>
                            <a href="student-attendance.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-clock"></i></span>
                                <span class="nav-label">Attendance</span>
                            </a>
                        </li>

                        <li>
                            <a href="student-assignments.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-file-pen"></i></span>
                                <span class="nav-label">Assignments</span>
                            </a>
                        </li>

                        <li>
                            <a href="student-timetable.php" class="nav-item">
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
                            <a href="../logout.php" class="nav-item" style="margin-top: 20px;">
                                <span class="nav-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                                <span class="nav-label" style="color: #e74c3c;">Logout</span>
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
                                <span class="user-name"><?php echo htmlspecialchars($student['studentName'] ?? $_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="user-role">Student</span>
                            </div>
                            <a href="student-profile.php" class="profile-pic" title="View Profile">
                                <i class="fa-solid fa-user"></i>
                            </a>
                        </div>
                    </div>
                </header>

                <div style="margin-bottom: 20px;">
                    <h1 class="welcome-title" style="color: var(--text-main); margin: 0;">Edit My Profile</h1>
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <a href="student-profile.php" class="btn" style="background: var(--text-muted);">
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
                    
                    <form method="POST" id="editProfileForm" novalidate>
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
                        
                        <!-- CSRF Token -->
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('editProfileForm');
        const submitBtn = document.getElementById('submitBtn');
        let formChanged = false;

        // Track form changes for unsaved warning
        const inputs = form.querySelectorAll('input:not([readonly]):not([type="hidden"]), textarea');
        inputs.forEach(input => {
            input.addEventListener('input', () => { formChanged = true; });
        });

        // Warn before leaving with unsaved changes
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Malaysian phone validation
        const validMobilePrefixes = ['010', '011', '012', '013', '014', '015', '016', '017', '018', '019'];
        const validLandlinePrefixes = ['03', '04', '05', '06', '07', '08', '09'];
        const allPrefixes = [...validMobilePrefixes, ...validLandlinePrefixes];

        // Validate phone field - Malaysian format
        function validatePhone(input) {
            const value = input.value.trim();
            const errorEl = document.getElementById(input.id + '-error');
            
            if (!value) {
                setInvalid(input, errorEl, 'Phone number is required');
                return false;
            }

            // Remove all non-digit characters for validation
            let digits = value.replace(/[^0-9]/g, '');
            
            // Handle international format +60 or 60
            if (value.startsWith('+60')) {
                digits = digits.substring(2); // Remove '60'
            } else if (digits.startsWith('60') && digits.length >= 11) {
                digits = digits.substring(2); // Remove '60'
            }
            
            // Check length (9-11 digits for Malaysian numbers)
            if (digits.length < 9 || digits.length > 11) {
                setInvalid(input, errorEl, 'Malaysian phone: 9-11 digits (e.g. 012-3456789)');
                return false;
            }
            
            // Must start with 0
            if (digits[0] !== '0') {
                setInvalid(input, errorEl, 'Must start with 0 or +60');
                return false;
            }
            
            // Check valid prefix
            let hasValidPrefix = allPrefixes.some(prefix => digits.startsWith(prefix));
            if (!hasValidPrefix) {
                setInvalid(input, errorEl, 'Invalid Malaysian phone prefix');
                return false;
            }
            
            setValid(input, errorEl);
            return true;
        }

        // Validate address field
        function validateAddress(input) {
            const value = input.value.trim();
            const errorEl = document.getElementById(input.id + '-error');
            
            if (value.length > 500) {
                setInvalid(input, errorEl, 'Address is too long (max 500 characters)');
                return false;
            }
            if (/<script|<iframe|javascript:|onerror=/i.test(value)) {
                setInvalid(input, errorEl, 'Address contains invalid content');
                return false;
            }
            setValid(input, errorEl);
            return true;
        }

        // Set field as invalid
        function setInvalid(input, errorEl, message) {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            errorEl.textContent = message;
            errorEl.classList.add('show');
        }

        // Set field as valid
        function setValid(input, errorEl) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            errorEl.textContent = '';
            errorEl.classList.remove('show');
        }

        // Character counter for textareas
        function updateCharCounter(textarea) {
            const count = textarea.value.length;
            const countEl = document.getElementById(textarea.id + '-count');
            const counterEl = countEl.parentElement;
            countEl.textContent = count;
            
            counterEl.classList.remove('warning', 'danger');
            if (count >= 450) counterEl.classList.add('danger');
            else if (count >= 400) counterEl.classList.add('warning');
        }

        // Attach validation to phone fields
        ['contactNo', 'parentContact'].forEach(id => {
            const input = document.getElementById(id);
            input.addEventListener('blur', () => validatePhone(input));
            input.addEventListener('input', () => {
                if (input.classList.contains('is-invalid')) validatePhone(input);
            });
        });

        // Attach validation to address fields
        ['homeAddress', 'corrAddress'].forEach(id => {
            const textarea = document.getElementById(id);
            updateCharCounter(textarea); // Initial count
            textarea.addEventListener('input', () => {
                updateCharCounter(textarea);
                if (textarea.classList.contains('is-invalid')) validateAddress(textarea);
            });
            textarea.addEventListener('blur', () => validateAddress(textarea));
        });

        // Form submission
        form.addEventListener('submit', function(e) {
            let isValid = true;

            // Validate all fields
            isValid = validatePhone(document.getElementById('contactNo')) && isValid;
            isValid = validatePhone(document.getElementById('parentContact')) && isValid;
            isValid = validateAddress(document.getElementById('homeAddress')) && isValid;
            isValid = validateAddress(document.getElementById('corrAddress')) && isValid;

            if (!isValid) {
                e.preventDefault();
                // Scroll to first error
                const firstError = form.querySelector('.is-invalid');
                if (firstError) firstError.focus();
                return;
            }

            // Show loading state
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            formChanged = false; // Disable unsaved warning
        });
    });
    </script>
</body>
</html>