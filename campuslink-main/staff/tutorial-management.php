<?php
session_start();
require_once '../includes/config.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit;
}
prevent_back_button_cache();

$db = get_db_connection();
$staffUserID = $_SESSION['user_id'];
$staffName = $_SESSION['user_name'];

// 2. Handle ID Mapping (U001 -> S001)
$staffID = $staffUserID;
if (strpos($staffUserID, 'U') === 0) {
    $stmtUser = $db->prepare("SELECT staffID, staffName FROM staff WHERE userID = ?");
    $stmtUser->bind_param("s", $staffUserID);
    $stmtUser->execute();
    $resUser = $stmtUser->get_result()->fetch_assoc();
    if ($resUser) {
        $staffID = $resUser['staffID'];
        $staffName = $resUser['staffName'];
    }
}

// 3. Fetch Tutorial/Practical classes taught by this staff (NOT Lecture)
// Each class is shown separately (not grouped by tutGroup)
$groupsSql = "SELECT DISTINCT cs.scheduleID, cs.courseID, cs.tutGroup, cs.programID, cs.classType, 
              c.courseName, p.programName
              FROM class_schedule cs
              JOIN course c ON cs.courseID = c.courseID
              JOIN program p ON cs.programID = p.programID
              WHERE cs.staffID = ? 
              AND cs.classType IN ('Tutorial', 'Practical', 'Lab')
              ORDER BY c.courseName, cs.classType, p.programName, cs.tutGroup";
$stmtGroups = $db->prepare($groupsSql);
$stmtGroups->bind_param("s", $staffID);
$stmtGroups->execute();
$tutorialClasses = $stmtGroups->get_result()->fetch_all(MYSQLI_ASSOC);

// Create removed_students table if not exists
$createTableSql = "CREATE TABLE IF NOT EXISTS `class_group_removals` (
    `removalID` int(11) NOT NULL AUTO_INCREMENT,
    `studentID` varchar(12) NOT NULL,
    `programID` varchar(10) NOT NULL,
    `tutGroup` varchar(20) NOT NULL,
    `removedBy` varchar(12) NOT NULL,
    `reason` text NOT NULL,
    `removedAt` timestamp NOT NULL DEFAULT current_timestamp(),
    `reinstatedAt` timestamp NULL DEFAULT NULL,
    `reinstatedBy` varchar(12) DEFAULT NULL,
    `status` enum('Removed','Reinstated') DEFAULT 'Removed',
    PRIMARY KEY (`removalID`),
    KEY `studentID` (`studentID`),
    KEY `programID` (`programID`),
    KEY `tutGroup` (`tutGroup`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
$db->query($createTableSql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutorial Management — CAMPUSLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <!-- Force cache reload: v2.1 -->
    <style>
        /* Tutorial Management Specific Styles */
        :root {
            --purple-base: #8056ff;
            --purple-deep: #6b45f5;
            --purple-soft: #a786ff;
            --purple-tint: rgba(129, 98, 255, 0.12);
        }
        
        /* Page Header - Consistent with Attendance/Assignment */
        .page-header {
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .page-header-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--purple-base), var(--purple-deep));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 22px;
            box-shadow: 0 4px 12px rgba(128, 86, 255, 0.3);
        }
        
        .page-header-text h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
        }
        
        .page-header-text p {
            color: var(--text-light, #6b7280);
            margin: 4px 0 0 0;
            font-size: 14px;
        }
        
        .tutorial-container {
            display: flex;
            gap: 24px;
            height: calc(100vh - 220px);
        }
        
        /* Left Panel - Groups List */
        .groups-panel {
            width: 320px;
            flex-shrink: 0;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .groups-header {
            padding: 20px;
            border-bottom: 1px solid #f0f0f5;
        }
        
        .groups-header h3 {
            margin: 0 0 4px;
            font-size: 16px;
            color: #181b2f;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .groups-header h3 i {
            color: var(--purple-base);
        }
        
        .groups-header-sub {
            font-size: 12px;
            color: #8d90aa;
        }
        
        .groups-search {
            margin-top: 12px;
            position: relative;
        }
        
        .groups-search input {
            width: 100%;
            padding: 10px 12px 10px 36px;
            border: 1px solid #e5e5ea;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        
        .groups-search input:focus {
            border-color: var(--purple-base);
        }
        
        .groups-search i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: 14px;
        }
        
        .groups-list {
            flex: 1;
            overflow-y: auto;
            padding: 12px;
        }
        
        .group-item {
            padding: 16px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 10px;
            border: 1px solid #f0f0f5;
            background: #fafafa;
        }
        
        .group-item:hover {
            background: #f9f8ff;
            border-color: #e5e0ff;
        }
        
        .group-item.active {
            background: var(--purple-tint);
            border-color: var(--purple-soft);
            box-shadow: 0 2px 8px rgba(128, 86, 255, 0.15);
        }
        
        .group-item-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 4px;
        }
        
        .group-item .group-name {
            font-weight: 700;
            font-size: 13px;
            color: #181b2f;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .group-item .group-program {
            font-size: 11px;
            color: #8d90aa;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .group-item .group-program i {
            color: #a786ff;
            font-size: 10px;
        }
        
        .group-item .group-courses {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #e5e5ea;
        }
        
        .group-course-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            margin: 3px 3px 3px 0;
            background: #f3f0ff;
            color: var(--purple-deep);
        }
        
        .group-course-tag i {
            font-size: 10px;
        }
        
        .group-course-name {
            font-weight: 600;
            font-size: 13px;
            color: #181b2f;
            margin: 6px 0 8px;
            line-height: 1.3;
        }
        
        .class-type-badge {
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .class-type-badge.tutorial { background: #e0f2fe; color: #0369a1; }
        .class-type-badge.practical { background: #fef3c7; color: #b45309; }
        .class-type-badge.lab { background: #dcfce7; color: #16a34a; }
        
        .group-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            background: var(--purple-base);
            color: #fff;
        }
        
        /* Right Panel - Students Table */
        .students-panel {
            flex: 1;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .students-header {
            padding: 20px 28px;
            border-bottom: 1px solid #f0f0f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .students-header-left h2 {
            margin: 0;
            font-size: 20px;
            color: #181b2f;
            font-weight: 700;
        }
        
        .students-header-left .header-subtitle {
            font-size: 13px;
            color: #8d90aa;
            margin-top: 4px;
        }
        
        .header-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--purple-base), var(--purple-deep));
            color: #fff;
            box-shadow: 0 4px 12px rgba(128, 86, 255, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(128, 86, 255, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #e5e5ea;
            color: #666;
        }
        
        .btn-outline:hover {
            background: #f5f5f5;
        }
        
        .students-table-container {
            flex: 1;
            overflow-y: auto;
            padding: 0;
        }
        
        .students-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .students-table th {
            position: sticky;
            top: 0;
            background: linear-gradient(180deg, #faf9ff 0%, #f5f3ff 100%);
            padding: 16px 24px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 2px solid #eae6ff;
        }
        
        .students-table td {
            padding: 18px 24px;
            border-bottom: 1px solid #f5f5f5;
            font-size: 14px;
            vertical-align: middle;
        }
        
        .students-table tbody tr {
            transition: all 0.15s;
        }
        
        .students-table tbody tr:hover {
            background: linear-gradient(90deg, #fdfcff 0%, #f9f8ff 100%);
        }
        
        .student-id {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-weight: 700;
            color: var(--purple-base);
            font-size: 13px;
            background: #f8f6ff;
            padding: 6px 10px;
            border-radius: 6px;
            display: inline-block;
        }
        
        .student-name {
            font-weight: 600;
            color: #181b2f;
            font-size: 14px;
        }
        
        .student-email {
            color: #6b7280;
            font-size: 13px;
        }
        
        .student-contact {
            color: #6b7280;
            font-size: 13px;
            font-family: 'JetBrains Mono', monospace;
        }
        
        .action-btns {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-view {
            background: #eef6ff;
            color: #0066cc;
            border: 1px solid #d0e8ff;
        }
        
        .btn-view:hover {
            background: #d0e8ff;
            transform: translateY(-1px);
        }
        
        .btn-remove {
            background: #fff5f5;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .btn-remove:hover {
            background: #fee2e2;
            transform: translateY(-1px);
        }
        
        .btn-reinstate {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        
        .btn-reinstate:hover {
            background: #dcfce7;
            transform: translateY(-1px);
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #e8fff0;
            color: #28a745;
        }
        
        .status-removed {
            background: #ffe8e8;
            color: #cc3333;
        }
        
        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 60px 20px;
            color: #8d90aa;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.4;
        }
        
        .empty-state h3 {
            font-size: 18px;
            margin: 0 0 8px;
            color: #555;
        }
        
        .empty-state p {
            font-size: 14px;
            margin: 0;
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal {
            background: #fff;
            border-radius: 16px;
            width: 100%;
            max-width: 480px;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(20px);
            transition: transform 0.3s;
        }
        
        .modal-overlay.active .modal {
            transform: translateY(0);
        }
        
        .modal-header {
            padding: 24px;
            border-bottom: 1px solid #f0f0f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            color: #181b2f;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            color: #999;
            cursor: pointer;
            padding: 4px;
        }
        
        .modal-close:hover {
            color: #333;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #f0f0f5;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        /* Profile Modal */
        .profile-modal {
            max-width: 420px;
        }
        
        .profile-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f5;
            margin-bottom: 20px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--purple-base), var(--purple-deep));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            overflow: hidden;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-avatar i {
            font-size: 48px;
            color: #fff;
        }
        
        .profile-name {
            font-size: 22px;
            font-weight: 700;
            color: #181b2f;
            margin: 0 0 8px;
        }
        
        .profile-id {
            font-size: 14px;
            color: var(--purple-base);
            font-weight: 600;
        }
        
        .profile-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .info-item {
            padding: 12px;
            background: #f9f8ff;
            border-radius: 8px;
        }
        
        .info-label {
            font-size: 11px;
            color: #8d90aa;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .info-value {
            font-size: 14px;
            color: #181b2f;
            font-weight: 500;
            word-break: break-word;
        }
        
        /* Removal Modal */
        .removal-modal {
            max-width: 450px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #444;
            margin-bottom: 8px;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e5e5ea;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: var(--purple-base);
        }
        
        .warning-text {
            background: #fff8e6;
            border: 1px solid #ffeeba;
            border-radius: 8px;
            padding: 12px;
            font-size: 13px;
            color: #856404;
            margin-bottom: 20px;
        }
        
        .warning-text i {
            margin-right: 8px;
        }
        
        /* Tab navigation for removed students */
        .tab-nav {
            display: flex;
            border-bottom: 1px solid #f0f0f5;
            padding: 0 20px;
        }
        
        .tab-btn {
            padding: 14px 20px;
            background: none;
            border: none;
            font-size: 14px;
            font-weight: 600;
            color: #8d90aa;
            cursor: pointer;
            position: relative;
            transition: color 0.2s;
        }
        
        .tab-btn:hover {
            color: var(--purple-base);
        }
        
        .tab-btn.active {
            color: var(--purple-base);
        }
        
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--purple-base);
        }
        
        .tab-count {
            display: inline-block;
            background: #f0f0f5;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            margin-left: 6px;
        }
        
        .tab-btn.active .tab-count {
            background: var(--purple-tint);
            color: var(--purple-base);
        }
        
        /* Removed reason tooltip */
        .removal-reason {
            font-size: 12px;
            color: #999;
            font-style: italic;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="app-bg">
        <div class="main-card">
            <?php $currentPage = 'tutorial-management'; include __DIR__ . '/../includes/staffMaster.php'; ?>
            
            <!-- Main Content -->
            <main class="dashboard">
                <header class="dashboard-topbar">
                    <div class="topbar-right">
                        <div class="user-card">
                            <div class="user-info">
                                <span class="user-name"><?php echo htmlspecialchars($staffName); ?></span>
                                <span class="user-role">Staff</span>
                            </div>
                            <div class="profile-pic">
                                <i class="fa-solid fa-user"></i>
                            </div>
                        </div>
                    </div>
                </header>
                
                <!-- Enhanced Page Hero Header -->
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
                        <h1 style="font-size: 30px; font-weight: 700; margin: 0 0 8px 0; color: white;">Tutorial Management</h1>
                        <p style="margin: 0; font-size: 14px; color: rgba(255,255,255,0.85);">Manage students in your tutorial and practical classes</p>
                    </div>
                    
                    <!-- Right Decorative Illustration - Classroom Theme -->
                    <div style="position: relative; z-index: 1; display: flex; align-items: flex-end; gap: 12px;">
                        <!-- Chalkboard -->
                        <div style="
                            width: 100px; height: 75px;
                            background: #1f2937;
                            border-radius: 8px;
                            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                            position: relative;
                            padding: 10px;
                            border: 4px solid #92400e;
                        ">
                            <!-- Chalk writing simulation -->
                            <div style="height: 4px; background: rgba(255,255,255,0.7); border-radius: 2px; width: 60%; margin-bottom: 8px;"></div>
                            <div style="height: 4px; background: rgba(255,255,255,0.5); border-radius: 2px; width: 80%; margin-bottom: 8px;"></div>
                            <div style="height: 4px; background: rgba(255,255,255,0.6); border-radius: 2px; width: 45%;"></div>
                            <!-- Chalk piece -->
                            <div style="position: absolute; bottom: -12px; right: 10px; width: 25px; height: 8px; background: white; border-radius: 2px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);"></div>
                        </div>
                        <!-- Student Group -->
                        <div style="display: flex; align-items: flex-end; margin-left: -20px;">
                            <!-- Student 1 -->
                            <div style="
                                width: 45px; height: 55px;
                                background: white;
                                border-radius: 12px 12px 0 0;
                                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                                display: flex;
                                flex-direction: column;
                                align-items: center;
                                padding-top: 8px;
                                margin-right: -10px;
                                z-index: 1;
                            ">
                                <div style="width: 24px; height: 24px; background: #fcd34d; border-radius: 50%; margin-bottom: 4px;"></div>
                                <div style="width: 30px; height: 20px; background: #3b82f6; border-radius: 6px 6px 0 0;"></div>
                            </div>
                            <!-- Student 2 -->
                            <div style="
                                width: 50px; height: 65px;
                                background: white;
                                border-radius: 12px 12px 0 0;
                                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                                display: flex;
                                flex-direction: column;
                                align-items: center;
                                padding-top: 10px;
                                z-index: 2;
                            ">
                                <div style="width: 28px; height: 28px; background: #f97316; border-radius: 50%; margin-bottom: 4px;"></div>
                                <div style="width: 34px; height: 24px; background: #8b5cf6; border-radius: 6px 6px 0 0;"></div>
                            </div>
                            <!-- Student 3 -->
                            <div style="
                                width: 45px; height: 55px;
                                background: white;
                                border-radius: 12px 12px 0 0;
                                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                                display: flex;
                                flex-direction: column;
                                align-items: center;
                                padding-top: 8px;
                                margin-left: -10px;
                                z-index: 1;
                            ">
                                <div style="width: 24px; height: 24px; background: #ec4899; border-radius: 50%; margin-bottom: 4px;"></div>
                                <div style="width: 30px; height: 20px; background: #10b981; border-radius: 6px 6px 0 0;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="tutorial-container">
                    <!-- Left Panel - Groups -->
                    <div class="groups-panel">
                        <div class="groups-header">
                            <h3><i class="fa-solid fa-layer-group"></i> My Classes</h3>
                            <span class="groups-header-sub">Tutorial & Practical Groups</span>
                            <div class="groups-search">
                                <i class="fa-solid fa-search"></i>
                                <input type="text" id="groupSearch" placeholder="Search groups...">
                            </div>
                        </div>
                        <div class="groups-list" id="groupsList">
                            <?php if (empty($tutorialClasses)): ?>
                                <div class="empty-state" style="padding: 30px 10px;">
                                    <i class="fa-solid fa-folder-open" style="font-size: 32px;"></i>
                                    <p style="font-size: 13px; text-align: center;">No tutorial/practical classes assigned</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($tutorialClasses as $class): 
                                    $typeClass = strtolower($class['classType']);
                                ?>
                                    <div class="group-item" 
                                         data-program="<?php echo htmlspecialchars($class['programID']); ?>" 
                                         data-tutgroup="<?php echo htmlspecialchars($class['tutGroup']); ?>"
                                         data-courseid="<?php echo htmlspecialchars($class['courseID']); ?>"
                                         data-classtype="<?php echo htmlspecialchars($class['classType']); ?>"
                                         data-scheduleid="<?php echo htmlspecialchars($class['scheduleID']); ?>">
                                        <div class="group-item-header">
                                            <div class="group-name">
                                                <span class="group-badge"><?php echo htmlspecialchars($class['courseID']); ?></span>
                                                <span class="class-type-badge <?php echo $typeClass; ?>"><?php echo $class['classType']; ?></span>
                                            </div>
                                        </div>
                                        <div class="group-course-name"><?php echo htmlspecialchars($class['courseName']); ?></div>
                                        <div class="group-program">
                                            <i class="fa-solid fa-users"></i>
                                            <?php echo htmlspecialchars($class['programID'] . ' ' . $class['tutGroup']); ?> 
                                            <span style="color: #aaa;">•</span> 
                                            <?php echo htmlspecialchars($class['programName']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Right Panel - Students -->
                    <div class="students-panel">
                        <div class="students-header">
                            <div class="students-header-left">
                                <h2 id="panelTitle">Select a Class</h2>
                                <div class="header-subtitle" id="panelSubtitle">Choose a tutorial group from the left panel</div>
                            </div>
                            <div class="header-actions">
                                <button class="btn btn-primary" id="btnGenerateReport" style="display: none;">
                                    <i class="fa-solid fa-file-pdf"></i> Generate Report
                                </button>
                            </div>
                        </div>
                        
                        <!-- Tab Navigation -->
                        <div class="tab-nav" id="tabNav" style="display: none;">
                            <button class="tab-btn active" data-tab="active">
                                Active Students <span class="tab-count" id="activeCount">0</span>
                            </button>
                            <button class="tab-btn" data-tab="removed">
                                Removed <span class="tab-count" id="removedCount">0</span>
                            </button>
                        </div>
                        
                        <div class="students-table-container" id="studentsContainer">
                            <!-- Empty State (default) -->
                            <div class="empty-state" id="emptyState">
                                <i class="fa-solid fa-users-rectangle"></i>
                                <h3>Select a Group</h3>
                                <p>Choose a tutorial/practical group from the left panel to view students</p>
                            </div>
                            
                            <!-- Students Table (hidden initially) -->
                            <table class="students-table" id="studentsTable" style="display: none;">
                                <thead>
                                    <tr>
                                        <th style="width: 140px;">Student ID</th>
                                        <th style="width: 200px;">Name</th>
                                        <th style="width: 100px;">Program</th>
                                        <th>Email</th>
                                        <th style="width: 130px;">Contact</th>
                                        <th style="width: 180px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="studentsBody">
                                    <!-- Populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Profile Modal -->
    <div class="modal-overlay" id="profileModal">
        <div class="modal profile-modal">
            <div class="modal-header">
                <h3>Student Profile</h3>
                <button class="modal-close" onclick="closeModal('profileModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="profile-header">
                    <div class="profile-avatar" id="profileAvatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <h2 class="profile-name" id="profileName">Student Name</h2>
                    <div id="profileIdSection">
                        <span class="info-label">ID</span>
                        <span class="profile-id" id="profileId">23WP00000</span>
                    </div>
                    <div id="profileProgramSection" style="margin-top: 8px;">
                        <span class="info-label">Program</span>
                        <div class="info-value" id="profileProgram">RSD</div>
                    </div>
                </div>
                <div class="profile-info">
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value" id="profileEmail">email@student.tarc.edu.my</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Contact</div>
                        <div class="info-value" id="profileContact">+1 452)217-6590</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-remove" id="btnRemoveFromProfile" style="width: 100%;">
                    <i class="fa-solid fa-user-minus"></i> Remove from Group
                </button>
            </div>
        </div>
    </div>
    
    <!-- Remove Student Modal -->
    <div class="modal-overlay" id="removeModal">
        <div class="modal removal-modal">
            <div class="modal-header">
                <h3>Remove Student</h3>
                <button class="modal-close" onclick="closeModal('removeModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="warning-text">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    You are about to remove <strong id="removeStudentName">Student Name</strong> from this group. 
                    Their historical data (attendance, submissions) will be preserved.
                </div>
                <div class="form-group">
                    <label for="removalReason">Reason for Removal *</label>
                    <textarea id="removalReason" placeholder="e.g., Course changed, Transferred to another group, etc."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('removeModal')">Cancel</button>
                <button class="btn btn-remove" id="confirmRemoveBtn">
                    <i class="fa-solid fa-user-minus"></i> Remove Student
                </button>
            </div>
        </div>
    </div>
    
    <!-- Reinstate Student Modal -->
    <div class="modal-overlay" id="reinstateModal">
        <div class="modal removal-modal">
            <div class="modal-header">
                <h3>Reinstate Student</h3>
                <button class="modal-close" onclick="closeModal('reinstateModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="warning-text" style="background: #e8fff0; border-color: #c3e6cb; color: #155724;">
                    <i class="fa-solid fa-user-plus"></i>
                    You are about to reinstate <strong id="reinstateStudentName">Student Name</strong> back to this group.
                </div>
                <p id="removalInfo" style="font-size: 13px; color: #666; margin-bottom: 0;">
                    Previously removed on: <span id="removedDate">-</span><br>
                    Reason: <span id="removedReason">-</span>
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('reinstateModal')">Cancel</button>
                <button class="btn btn-reinstate" id="confirmReinstateBtn">
                    <i class="fa-solid fa-user-plus"></i> Reinstate Student
                </button>
            </div>
        </div>
    </div>
    
    <script src="../script.js"></script>
    <script>
        // State
        let currentProgram = '';
        let currentTutGroup = '';
        let currentScheduleID = '';  // Add this to track specific class
        let currentCourseID = '';
        let currentClassType = '';
        let currentCourseName = '';
        let currentTab = 'active';
        let currentStudentId = '';
        let currentRemovalId = null;
        
        // DOM Elements
        const groupItems = document.querySelectorAll('.group-item');
        const groupSearch = document.getElementById('groupSearch');
        const tabNav = document.getElementById('tabNav');
        const tabBtns = document.querySelectorAll('.tab-btn');
        const emptyState = document.getElementById('emptyState');
        const studentsTable = document.getElementById('studentsTable');
        const studentsBody = document.getElementById('studentsBody');
        const btnGenerateReport = document.getElementById('btnGenerateReport');
        const panelTitle = document.getElementById('panelTitle');
        const panelSubtitle = document.getElementById('panelSubtitle');
        
        // Group Selection
        groupItems.forEach(item => {
            item.addEventListener('click', () => {
                // Update active state
                groupItems.forEach(g => g.classList.remove('active'));
                item.classList.add('active');
                
                // Get selected class data
                currentProgram = item.dataset.program;
                currentTutGroup = item.dataset.tutgroup;
                currentScheduleID = item.dataset.scheduleid;  // Get scheduleID
                currentCourseID = item.dataset.courseid;
                currentClassType = item.dataset.classtype;
                currentCourseName = item.querySelector('.group-course-name')?.textContent || currentCourseID;
                const programInfo = item.querySelector('.group-program')?.textContent || '';
                
                btnGenerateReport.style.display = 'inline-flex';
                tabNav.style.display = 'flex';
                
                // Update title and subtitle
                panelTitle.textContent = `${currentCourseID} - ${currentClassType}`;
                panelSubtitle.textContent = `${currentCourseName} • ${currentProgram} ${currentTutGroup}`;
                
                // Load students
                loadStudents();
            });
        });
        
        // Search groups
        groupSearch.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            groupItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(query) ? 'block' : 'none';
            });
        });
        
        // Tab switching
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                tabBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentTab = btn.dataset.tab;
                loadStudents();
            });
        });
        
        // Load Students
        async function loadStudents() {
            if (!currentProgram || !currentTutGroup) return;
            
            // Debug: Log what we're requesting
            console.log('🔍 Loading students for:', {
                scheduleID: currentScheduleID,
                program: currentProgram,
                tutGroup: currentTutGroup,
                courseID: currentCourseID,
                status: currentTab
            });
            
            try {
                // Add timestamp to prevent caching
                const timestamp = new Date().getTime();
                const url = `../api/tutorialManagement.php?action=getStudents&scheduleID=${encodeURIComponent(currentScheduleID)}&programID=${encodeURIComponent(currentProgram)}&tutGroup=${encodeURIComponent(currentTutGroup)}&status=${currentTab}&_t=${timestamp}`;
                
                console.log('📡 API Request URL:', url);
                
                const response = await fetch(url, {
                    cache: 'no-cache',
                    headers: {
                        'Cache-Control': 'no-cache',
                        'Pragma': 'no-cache'
                    }
                });
                const data = await response.json();
                
                console.log('📥 API Response:', data);
                
                if (data.success) {
                    // Update counts
                    document.getElementById('activeCount').textContent = data.activeCount || 0;
                    document.getElementById('removedCount').textContent = data.removedCount || 0;
                    
                    // Show/hide table
                    if (data.students.length === 0) {
                        emptyState.style.display = 'flex';
                        emptyState.innerHTML = `
                            <i class="fa-solid fa-user-slash"></i>
                            <h3>No ${currentTab === 'active' ? 'Active' : 'Removed'} Students</h3>
                            <p>${currentTab === 'active' ? 'All students have been removed from this group' : 'No students have been removed from this group'}</p>
                        `;
                        studentsTable.style.display = 'none';
                    } else {
                        emptyState.style.display = 'none';
                        studentsTable.style.display = 'table';
                        renderStudents(data.students);
                    }
                } else {
                    console.error('Failed to load students:', data.message);
                }
            } catch (error) {
                console.error('Error loading students:', error);
            }
        }
        
        // Render Students
        function renderStudents(students) {
            studentsBody.innerHTML = students.map(student => {
                const isRemoved = student.status === 'Removed';
                return `
                    <tr>
                        <td><span class="student-id">${escapeHtml(student.studentID)}</span></td>
                        <td>
                            <div class="student-name">${escapeHtml(student.studentName)}</div>
                            ${isRemoved && student.reason ? `<div class="removal-reason"><i class="fa-solid fa-info-circle"></i> ${escapeHtml(student.reason)}</div>` : ''}
                        </td>
                        <td><span style="background: #f3f0ff; color: #6b45f5; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">${escapeHtml(student.programID)}</span></td>
                        <td><span class="student-email">${escapeHtml(student.email)}</span></td>
                        <td><span class="student-contact">${escapeHtml(student.contactNo || '-')}</span></td>
                        <td>
                            <div class="action-btns">
                                <button class="action-btn btn-view" onclick="viewStudent('${escapeHtml(student.studentID)}')">
                                    <i class="fa-solid fa-eye"></i> View
                                </button>
                                ${isRemoved ? `
                                    <button class="action-btn btn-reinstate" onclick="showReinstateModal('${escapeHtml(student.studentID)}', '${escapeHtml(student.studentName)}', '${escapeHtml(student.removedAt || '')}', '${escapeHtml(student.reason || '')}', ${student.removalID})">
                                        <i class="fa-solid fa-user-plus"></i> Reinstate
                                    </button>
                                ` : `
                                    <button class="action-btn btn-remove" onclick="showRemoveModal('${escapeHtml(student.studentID)}', '${escapeHtml(student.studentName)}')">
                                        <i class="fa-solid fa-user-minus"></i> Remove
                                    </button>
                                `}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }
        
        // View Student Profile
        async function viewStudent(studentId) {
            currentStudentId = studentId;
            
            try {
                const timestamp = new Date().getTime();
                const response = await fetch(`../api/tutorialManagement.php?action=getStudentProfile&studentID=${encodeURIComponent(studentId)}&_t=${timestamp}`, {
                    cache: 'no-cache'
                });
                const data = await response.json();
                
                if (data.success) {
                    const student = data.student;
                    document.getElementById('profileName').textContent = student.studentName;
                    document.getElementById('profileId').textContent = student.studentID;
                    document.getElementById('profileProgram').textContent = student.programID;
                    document.getElementById('profileEmail').textContent = student.email;
                    document.getElementById('profileContact').textContent = student.contactNo || '-';
                    
                    // Update profile avatar with image or default icon
                    const avatarEl = document.getElementById('profileAvatar');
                    if (student.studentImage) {
                        avatarEl.innerHTML = `<img src="../uploads/profiles/${escapeHtml(student.studentImage)}" alt="${escapeHtml(student.studentName)}" style="width:100%; height:100%; object-fit:cover; border-radius:50%;" onerror="this.outerHTML='<i class=\\'fa-solid fa-user\\'></i>'">`;
                    } else {
                        avatarEl.innerHTML = '<i class="fa-solid fa-user"></i>';
                    }
                    
                    // Check if student is removed
                    if (currentTab === 'removed') {
                        document.getElementById('btnRemoveFromProfile').style.display = 'none';
                    } else {
                        document.getElementById('btnRemoveFromProfile').style.display = 'block';
                    }
                    
                    openModal('profileModal');
                }
            } catch (error) {
                console.error('Error loading student profile:', error);
            }
        }
        
        // Remove from profile modal
        document.getElementById('btnRemoveFromProfile').addEventListener('click', () => {
            closeModal('profileModal');
            const studentName = document.getElementById('profileName').textContent;
            showRemoveModal(currentStudentId, studentName);
        });
        
        // Show Remove Modal
        function showRemoveModal(studentId, studentName) {
            currentStudentId = studentId;
            document.getElementById('removeStudentName').textContent = studentName;
            document.getElementById('removalReason').value = '';
            openModal('removeModal');
        }
        
        // Show Reinstate Modal
        function showReinstateModal(studentId, studentName, removedAt, reason, removalId) {
            currentStudentId = studentId;
            currentRemovalId = removalId;
            document.getElementById('reinstateStudentName').textContent = studentName;
            document.getElementById('removedDate').textContent = removedAt || 'Unknown';
            document.getElementById('removedReason').textContent = reason || 'No reason provided';
            openModal('reinstateModal');
        }
        
        // Confirm Remove
        document.getElementById('confirmRemoveBtn').addEventListener('click', async () => {
            const reason = document.getElementById('removalReason').value.trim();
            
            if (!reason) {
                alert('Please provide a reason for removal.');
                return;
            }
            
            try {
                const response = await fetch('../api/tutorialManagement.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'removeStudent',
                        studentID: currentStudentId,
                        programID: currentProgram,
                        tutGroup: currentTutGroup,
                        reason: reason
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    closeModal('removeModal');
                    loadStudents();
                } else {
                    alert('Failed to remove student: ' + data.message);
                }
            } catch (error) {
                console.error('Error removing student:', error);
                alert('An error occurred while removing the student.');
            }
        });
        
        // Confirm Reinstate
        document.getElementById('confirmReinstateBtn').addEventListener('click', async () => {
            try {
                const response = await fetch('../api/tutorialManagement.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'reinstateStudent',
                        removalID: currentRemovalId,
                        studentID: currentStudentId,
                        programID: currentProgram,
                        tutGroup: currentTutGroup
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    closeModal('reinstateModal');
                    loadStudents();
                } else {
                    alert('Failed to reinstate student: ' + data.message);
                }
            } catch (error) {
                console.error('Error reinstating student:', error);
                alert('An error occurred while reinstating the student.');
            }
        });
        
        // Generate Report
        btnGenerateReport.addEventListener('click', () => {
            if (!currentProgram || !currentTutGroup) return;
            window.open(`../api/tutorialManagement.php?action=generateReport&scheduleID=${encodeURIComponent(currentScheduleID)}&programID=${encodeURIComponent(currentProgram)}&tutGroup=${encodeURIComponent(currentTutGroup)}`, '_blank');
        });
        
        // Modal helpers
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        // Close modal on overlay click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                }
            });
        });
        
        // Escape HTML helper
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
