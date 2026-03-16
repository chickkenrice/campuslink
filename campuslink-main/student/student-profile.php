<?php
session_start();
// Check that the path to your config file is correct based on your folder structure
require_once '../includes/config.php'; 

if (!validate_session() || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$db = get_db_connection();
$id = $_SESSION['user_id'];

// Query pulls s.studentImage along with everything else
$query = "SELECT s.*, 
                 d.dob, d.gender, d.icNo, d.homeAddress, d.corrAddress, d.parentName, d.parentContact,
                 p.programName, p.faculty,
                 s.currentYear, s.currentSemester
          FROM student s 
          LEFT JOIN student_details d ON s.studentID = d.studentID 
          LEFT JOIN program p ON s.programID = p.programID 
          WHERE s.studentID = ?";

$stmt = $db->prepare($query);
$stmt->bind_param("s", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Profile not found.");
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Profile — CAMPUSLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../assets/css/styles.css"> 
    <style>
        .profile-container { max-width: 1000px; margin: 0 auto; display: grid; grid-template-columns: 300px 1fr; gap: 30px; }
        .profile-side-card { background: white; padding: 40px 20px; border-radius: 26px; text-align: center; box-shadow: var(--panel-shadow); height: fit-content; }
        /* Added overflow:hidden to keep uploaded image circular */
        .large-avatar { width: 120px; height: 120px; border-radius: 50%; background: var(--purple-tint); margin: 0 auto 20px; display: grid; place-items: center; border: 4px solid var(--purple-soft); overflow: hidden; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-group { background: rgba(249, 247, 255, 0.95); padding: 16px 20px; border-radius: 18px; border: 1px solid rgba(202, 200, 240, 0.8); }
        .info-label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 5px; }
        .info-value { font-weight: 600; color: var(--text-main); word-break: break-word; }
        .full-width { grid-column: span 2; }
        h3 { margin-top: 0; margin-bottom: 20px; color: var(--purple-base); font-size: 18px; }
    </style>
</head>
<body>
    <div class="app-bg">
        <div class="main-card">
            <?php $currentPage = 'profile'; include __DIR__ . '/../includes/studentMaster.php'; ?>

            <main class="dashboard">
                <header class="dashboard-topbar">
                    <div class="topbar-right">
                        <?php include __DIR__ . '/../includes/notificationBell.php'; ?>
                        <div class="user-card">
                            <div class="user-info">
                                <span class="user-name"><?php echo htmlspecialchars($student['studentName']); ?></span>
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
                        <h1 style="font-size: 30px; font-weight: 700; margin: 0 0 8px 0; color: white;">My Profile</h1>
                        <p style="margin: 0; font-size: 14px; color: rgba(255,255,255,0.85);">View and manage your account information</p>
                    </div>
                    
                    <!-- Right: Action Buttons -->
                    <div style="position: relative; z-index: 1; display: flex; gap: 12px;">
                        <a href="edit-profile.php" style="
                            display: inline-flex;
                            align-items: center;
                            gap: 8px;
                            padding: 12px 22px;
                            background: white;
                            color: #8056ff;
                            border-radius: 14px;
                            text-decoration: none;
                            font-weight: 600;
                            font-size: 14px;
                            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                            transition: all 0.2s ease;
                        " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.1)';">
                            <i class="fa-solid fa-pen-to-square"></i> Edit Profile
                        </a>
                        <a href="../index.php" style="
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
                            <i class="fa-solid fa-arrow-left"></i> Dashboard
                        </a>
                    </div>
                </div>

                <div class="profile-container">
                    <div class="profile-side-card">
                        <div class="large-avatar">
                            <?php if (!empty($student['studentImage']) && $student['studentImage'] !== 'default_avatar.png'): ?>
                                <img src="../uploads/profiles/<?php echo htmlspecialchars($student['studentImage']); ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <i class="fa-solid fa-user" style="font-size: 50px; color: var(--purple-base);"></i>
                            <?php endif; ?>
                        </div>
                        <h2 style="margin: 0; font-size: 20px;"><?php echo htmlspecialchars($student['studentName']); ?></h2>
                        <p style="color: var(--text-sub); margin-bottom: 5px;"><?php echo htmlspecialchars($student['studentID']); ?></p>
                        <p style="color: var(--purple-base); font-weight: 600; font-size: 13px; margin-bottom: 15px;">
                            <i class="fa-solid fa-graduation-cap"></i> Year <?php echo $student['currentYear'] ?? 1; ?> Semester <?php echo $student['currentSemester'] ?? 1; ?>
                        </p>
                        <span class="badge-prog" style="background: var(--purple-tint); color: var(--purple-base); padding: 8px 15px; border-radius: 12px; font-weight: 700; font-size: 12px;">
                            <?php echo htmlspecialchars($student['programID']); ?>
                        </span>
                    </div>

                    <div class="announcements-card">
                        <h3>Academic Information</h3>
                        <div class="info-grid">
                            <div class="info-group">
                                <div class="info-label">Programme</div>
                                <div class="info-value">
                                    <?php echo htmlspecialchars($student['programName'] ?? 'Not Assigned'); ?>
                                    <br>
                                    <small style="color:var(--text-muted);">
                                        (<?php echo htmlspecialchars($student['faculty'] ?? 'FOCS'); ?>)
                                    </small>
                                </div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Tutorial Group</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['tutGroup']); ?></div>
                            </div>
                        </div>

                        <h3>Personal Details</h3>
                        <div class="info-grid">
                            <div class="info-group">
                                <div class="info-label">IC / Passport No</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['icNo'] ?? 'Not Set'); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Gender</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['gender'] ?? 'Not Set'); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Date of Birth</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['dob'] ?? 'Not Set'); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Contact Number</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['contactNo']); ?></div>
                            </div>
                            <div class="info-group full-width">
                                <div class="info-label">Home Address</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($student['homeAddress'] ?? 'Not Set')); ?></div>
                            </div>
                            <div class="info-group full-width">
                                <div class="info-label">Correspondence Address</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($student['corrAddress'] ?? 'Not Set')); ?></div>
                            </div>
                        </div>

                        <h3>Parent / Guardian Information</h3>
                        <div class="info-grid">
                            <div class="info-group">
                                <div class="info-label">Guardian Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['parentName'] ?? 'Not Set'); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Guardian Contact</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['parentContact'] ?? 'Not Set'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php include __DIR__ . '/../includes/footer.php'; ?>
            </main>
        </div>
    </div>
    
    <script>
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
    </script>
</body>
</html>