<?php
session_start();
require_once(__DIR__ . '/includes/config.php'); 

// 1. SECURITY CHECK
if (!validate_session() || strcasecmp($_SESSION['role'], 'student') !== 0) {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['user_id'];
$db = get_db_connection();

// 2. FETCH STUDENT DETAILS
$stmt = $db->prepare("SELECT studentName, tutGroup, programID, studentImage FROM student WHERE studentID = ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $fullName = $row['studentName'];
    $tutGroup = $row['tutGroup'];
    $programme = $row['programID'];
    $studentImage = $row['studentImage'] ?? null;
} else {
    $fullName = $_SESSION['user_name'] ?? 'Student';
    $tutGroup = 'N/A';
    $programme = 'N/A';
    $studentImage = null;
}

$firstName = explode(' ', trim($fullName))[0];

// 3. FETCH ANNOUNCEMENTS (for students: show 'all' and 'student' targeted)
$ann_sql = "SELECT id, title, body, announcement_type, is_pinned, created_at FROM announcements 
            WHERE target_audience IN ('all', 'student') 
            ORDER BY is_pinned DESC, created_at DESC LIMIT 10";
$announcements = $db->query($ann_sql);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CAMPUSLink — Student Dashboard</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/styles.css">
    
    <style>
        .announcement-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 8px;
            background: #fff;
            border: 1px solid #eee;
            text-decoration: none;
            transition: all 0.2s;
        }
        .announcement-item:hover {
            background: #f8f5ff;
            border-color: #c4b5fd;
            transform: translateX(4px);
        }
        .ann-title {
            font-weight: 600;
            color: #333;
            font-size: 14px;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ann-date {
            font-size: 12px;
            color: #94a3b8;
            margin-left: 12px;
            white-space: nowrap;
        }
        .ann-arrow {
            color: #c4b5fd;
            margin-left: 10px;
            font-size: 12px;
        }
        .ann-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-right: 10px;
            flex-shrink: 0;
        }
        .type-system {
            background: #f3e8ff;
            color: #7c3aed;
        }
        .type-academic {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .type-campus {
            background: #dcfce7;
            color: #15803d;
        }
        .ann-pin-icon {
            color: #f59e0b;
            margin-right: 6px;
        }
    </style>
</head>
<body>
    <div class="app-bg">
        <div class="main-card">
            
            <?php $currentPage = 'dashboard'; include __DIR__ . '/includes/studentMaster.php'; ?>

            <main class="dashboard" aria-label="Dashboard">
                <header class="dashboard-topbar">
                    <div class="topbar-right">
                        <?php $notifApiPath = 'api/notifications.php'; include __DIR__ . '/includes/notificationBell.php'; ?>
                        <div class="user-card">
                            <div class="user-info">
                                <span class="user-name"><?php echo htmlspecialchars($fullName); ?></span>
                                <span class="user-role">Student</span>
                            </div>
                            <a href="student/student-profile.php" class="profile-pic" title="View Profile">
                                <?php if (!empty($studentImage) && $studentImage !== 'default_avatar.png'): ?>
                                    <img src="uploads/profiles/<?php echo htmlspecialchars($studentImage); ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover;">
                                <?php else: ?>
                                    <i class="fa-solid fa-user"></i>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </header>

                <section class="welcome-card">
                    <div class="welcome-details">
                        <p class="welcome-date"><?php echo date("j F Y"); ?></p>
                        <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($firstName); ?>!</h1>
                        <p class="welcome-text">Always stay updated in your student portal.</p>
                    </div>
                </section>

                <section class="announcements-card">
                    <header class="announcements-header">
                        <h2>Announcements</h2>
                        <div class="actions">
                            <button id="refreshBtn" class="btn" type="button" onclick="location.reload();">Refresh</button>
                            <a class="see-all" href="student/announcements.php">See all</a>
                        </div>
                    </header>
                    
                    <div id="announcements" class="announcements">
                        <?php if ($announcements && $announcements->num_rows > 0): ?>
                            <?php while($row = $announcements->fetch_assoc()): 
                                $annType = $row['announcement_type'] ?? 'system';
                                $typeConfig = [
                                    'system' => ['icon' => 'fa-gear', 'label' => 'System'],
                                    'academic' => ['icon' => 'fa-graduation-cap', 'label' => 'Academic'],
                                    'campus' => ['icon' => 'fa-building-columns', 'label' => 'Campus']
                                ];
                                $config = $typeConfig[$annType] ?? $typeConfig['system'];
                                $isPinned = ($row['is_pinned'] ?? 0) == 1;
                            ?>
                                <a href="student/announcements.php?id=<?php echo $row['id']; ?>" class="announcement-item">
                                    <span class="ann-type-badge type-<?php echo $annType; ?>">
                                        <i class="fa-solid <?php echo $config['icon']; ?>"></i><?php echo $config['label']; ?>
                                    </span>
                                    <span class="ann-title">
                                        <?php if ($isPinned): ?><i class="fa-solid fa-thumbtack ann-pin-icon"></i><?php endif; ?>
                                        <?php echo htmlspecialchars($row['title']); ?>
                                    </span>
                                    <span class="ann-date"><?php echo date("d-m-Y", strtotime($row['created_at'])); ?></span>
                                    <i class="fa-solid fa-chevron-right ann-arrow"></i>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="hint">No announcements available at the moment.</p>
                        <?php endif; ?>
                    </div>
                </section>
                
                <?php include __DIR__ . '/includes/footer.php'; ?>
            </main>
        </div>
    </div>

    <script>
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