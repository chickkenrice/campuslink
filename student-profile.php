<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$db = get_db_connection();
$id = $_SESSION['user_id'];

// Fetch all relevant student details
$stmt = $db->prepare("SELECT * FROM student WHERE studentID = ?");
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .profile-container {
            max-width: 900px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }
        .profile-side-card {
            background: white;
            padding: 40px 20px;
            border-radius: 26px;
            text-align: center;
            box-shadow: var(--panel-shadow);
        }
        .large-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--purple-tint);
            margin: 0 auto 20px;
            display: grid;
            place-items: center;
            border: 4px solid var(--purple-soft);
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .info-group {
            background: rgba(249, 247, 255, 0.95);
            padding: 16px 20px;
            border-radius: 18px;
            border: 1px solid rgba(202, 200, 240, 0.8);
        }
        .info-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .info-value {
            font-weight: 600;
            color: var(--text-main);
        }
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
                    </div>
                </div>
                <nav class="sidebar-nav">
                    <ul>
                        <li><a href="index.php" class="nav-item"><span class="nav-icon"><i class="fa-solid fa-house"></i></span> Dashboard</a></li>
                        <li><a href="#" class="nav-item is-active"><span class="nav-icon"><i class="fa-solid fa-user"></i></span> Profile</a></li>
                    </ul>
                </nav>
            </aside>

            <main class="dashboard">
                <header class="dashboard-topbar">
                    <h1 class="welcome-title" style="color: var(--text-main);">My Profile</h1>
                    <a href="index.php" class="btn">Back to Dashboard</a>
                </header>

                <div class="profile-container">
                    <div class="profile-side-card">
                        <div class="large-avatar">
                            <i class="fa-solid fa-user" style="font-size: 50px; color: var(--purple-base);"></i>
                        </div>
                        <h2 style="margin: 0;"><?php echo htmlspecialchars($student['studentName']); ?></h2>
                        <p style="color: var(--text-sub);"><?php echo htmlspecialchars($student['studentID']); ?></p>
                        <span class="badge-prog" style="background: var(--purple-tint); color: var(--purple-base); padding: 5px 15px; border-radius: 12px; font-weight: 700;">
                            <?php echo htmlspecialchars($student['programme']); ?>
                        </span>
                    </div>

                    <div class="announcements-card">
                        <h3>Academic & Personal Information</h3>
                        <div class="info-grid">
                            <div class="info-group">
                                <div class="info-label">Full Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['studentName']); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Student ID</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['studentID']); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Programme</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['programme']); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Tutorial Group</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['tutGroup']); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Email Address</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['email']); ?></div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Contact Number</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['contactNo']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>