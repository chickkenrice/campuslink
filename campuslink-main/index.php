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

// 3. FETCH ANNOUNCEMENTS
$ann_sql = "SELECT title, body, created_at FROM announcements ORDER BY created_at DESC LIMIT 3";
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
            background: #fff; border: 1px solid #eee; padding: 15px;
            margin-bottom: 10px; border-radius: 8px; border-left: 4px solid #6c5ce7;
        }
        .ann-date { font-size: 0.8rem; color: #888; margin-bottom: 4px; display:block; }
        .ann-title { font-weight: 700; color: #333; margin-bottom: 6px; display:block; font-size: 1rem; }
        .ann-body { color: #555; font-size: 0.9rem; line-height: 1.4; }
    </style>
</head>
<body>
    <div class="app-bg">
        <div class="main-card">
            
            <?php $currentPage = 'dashboard'; include __DIR__ . '/includes/studentMaster.php'; ?>

            <main class="dashboard" aria-label="Dashboard">
                <header class="dashboard-topbar">
                    <div class="topbar-right">
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
                            <a class="see-all" href="#">See all</a>
                        </div>
                    </header>
                    
                    <div id="announcements" class="announcements">
                        <?php if ($announcements && $announcements->num_rows > 0): ?>
                            <?php while($row = $announcements->fetch_assoc()): ?>
                                <div class="announcement-item">
                                    <span class="ann-date">
                                        <i class="fa-regular fa-clock"></i> 
                                        <?php echo date("j M Y • g:i A", strtotime($row['created_at'])); ?>
                                    </span>
                                    <span class="ann-title"><?php echo htmlspecialchars($row['title']); ?></span>
                                    <div class="ann-body">
                                        <?php echo nl2br(htmlspecialchars($row['body'])); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="hint">No announcements available at the moment.</p>
                        <?php endif; ?>
                    </div>
                </section>
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