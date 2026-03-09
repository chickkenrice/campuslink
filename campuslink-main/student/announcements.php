<?php
session_start();
require_once(__DIR__ . '/../includes/config.php');

if (!validate_session() || strcasecmp($_SESSION['role'], 'student') !== 0) {
    header("Location: ../login.php");
    exit;
}

$userID = $_SESSION['user_id'];
$db = get_db_connection();

// Fetch student details
$stmt = $db->prepare("SELECT studentName, programID, studentImage FROM student WHERE studentID = ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$studentName = $student['studentName'] ?? $_SESSION['user_name'] ?? 'Student';
$studentImage = $student['studentImage'] ?? null;

// Check if viewing specific announcement
$viewingId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$singleAnnouncement = null;

if ($viewingId) {
    $stmt = $db->prepare("SELECT id, title, body, announcement_type, is_pinned, created_at FROM announcements WHERE id = ? AND target_audience IN ('all', 'student')");
    $stmt->bind_param("i", $viewingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $singleAnnouncement = $result->fetch_assoc();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $singleAnnouncement ? htmlspecialchars($singleAnnouncement['title']) : 'Announcements'; ?> - CAMPUSLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .ann-detail-card {
            background: #ffffff !important;
            border-radius: 16px;
            padding: 32px;
            margin: 20px;
            box-shadow: 0 8px 30px rgba(124, 58, 237, 0.12), 0 4px 12px rgba(0,0,0,0.08) !important;
            border: 1px solid #e2e8f0;
            text-align: left !important;
        }
        .ann-detail-header {
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 20px;
            margin-bottom: 24px;
            text-align: left !important;
        }
        .ann-detail-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 12px 0;
            line-height: 1.4;
            text-align: left !important;
        }
        .ann-detail-meta {
            font-size: 14px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: flex-start;
        }
        .ann-detail-body {
            font-size: 16px;
            color: #475569;
            line-height: 1.8;
            white-space: normal;
            text-align: left;
            word-break: break-word;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            color: #7c3aed;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
            margin: 20px;
            box-shadow: 0 2px 8px rgba(124, 58, 237, 0.06);
        }
        .back-btn:hover {
            background: #ede9fe;
            border-color: #c4b5fd;
            color: #7c3aed;
        }
        .ann-list-item {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            margin-bottom: 8px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            transition: all 0.2s;
        }
        .ann-list-item:hover {
            background: #f8f5ff;
            border-color: #c4b5fd;
            transform: translateX(4px);
        }
        .ann-list-title {
            font-weight: 600;
            color: #1e293b;
            font-size: 15px;
            flex: 1;
        }
        .ann-list-date {
            font-size: 13px;
            color: #94a3b8;
            margin-left: 16px;
        }
        .ann-list-arrow {
            color: #c4b5fd;
            margin-left: 12px;
        }
        .ann-list-container {
            padding: 20px;
        }
        /* Type badge styles */
        .ann-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .type-system { background: #f3e8ff; color: #7c3aed; }
        .type-academic { background: #dbeafe; color: #1d4ed8; }
        .type-campus { background: #dcfce7; color: #15803d; }
        .ann-pin-badge { color: #f59e0b; margin-left: 8px; }
    </style>
</head>
<body>

<div class="app-bg">
    <div class="main-card">
        
        <?php $currentPage = 'dashboard'; include __DIR__ . '/../includes/studentMaster.php'; ?>

        <main class="dashboard">
            <header class="dashboard-topbar">
                <div class="topbar-right">
                    <?php include __DIR__ . '/../includes/notificationBell.php'; ?>
                    <div class="user-card">
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($studentName); ?></span>
                            <span class="user-role">Student</span>
                        </div>
                        <a href="student-profile.php" class="profile-pic" title="View Profile">
                            <?php if (!empty($studentImage) && $studentImage !== 'default_avatar.png'): ?>
                                <img src="../uploads/profiles/<?php echo htmlspecialchars($studentImage); ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                            <?php else: ?>
                                <i class="fa-solid fa-user"></i>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </header>

            <?php if ($singleAnnouncement): 
                $annType = $singleAnnouncement['announcement_type'] ?? 'system';
                $typeConfig = [
                    'system' => ['icon' => 'fa-gear', 'label' => 'System'],
                    'academic' => ['icon' => 'fa-graduation-cap', 'label' => 'Academic'],
                    'campus' => ['icon' => 'fa-building-columns', 'label' => 'Campus']
                ];
                $config = $typeConfig[$annType] ?? $typeConfig['system'];
                $isPinned = ($singleAnnouncement['is_pinned'] ?? 0) == 1;
            ?>
                <!-- Single Announcement View -->
                <a href="../index.php" class="back-btn">
                    <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
                </a>
                <div class="ann-detail-card">
                    <div class="ann-detail-header">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                            <span class="ann-type-badge type-<?php echo $annType; ?>">
                                <i class="fa-solid <?php echo $config['icon']; ?>"></i><?php echo $config['label']; ?>
                            </span>
                            <?php if ($isPinned): ?>
                                <span class="ann-pin-badge"><i class="fa-solid fa-thumbtack"></i> Pinned</span>
                            <?php endif; ?>
                        </div>
                        <h1 class="ann-detail-title"><?php echo htmlspecialchars($singleAnnouncement['title']); ?></h1>
                        <div class="ann-detail-meta">
                            <i class="fa-regular fa-clock"></i>
                            <?php echo date("l, j F Y \a\\t g:i A", strtotime($singleAnnouncement['created_at'])); ?>
                        </div>
                    </div>
                    <div class="ann-detail-body">
                        <?php echo nl2br(htmlspecialchars(trim($singleAnnouncement['body']))); ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- All Announcements List -->
                <section class="welcome-card" style="margin-bottom: 24px;">
                    <div class="welcome-details">
                        <h1 class="welcome-title">All Announcements</h1>
                        <div class="welcome-meta">
                            <span class="welcome-meta-sub"><i class="fa-solid fa-bullhorn"></i> View all announcements</span>
                        </div>
                    </div>
                </section>

                <div class="ann-list-container" id="announcementsList">
                    <div style="text-align: center; padding: 40px; color: #94a3b8;">
                        <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px;"></i>
                        <p style="margin-top: 12px;">Loading announcements...</p>
                    </div>
                </div>
            <?php endif; ?>
            
                <?php include __DIR__ . '/../includes/footer.php'; ?>
        </main>
    </div>
</div>

<?php if (!$singleAnnouncement): ?>
<script>
document.addEventListener('DOMContentLoaded', loadAnnouncements);

async function loadAnnouncements() {
    const container = document.getElementById('announcementsList');
    
    try {
        const res = await fetch('../api/announcements.php?audience=student');
        const data = await res.json();
        
        if (data.success && data.data.length > 0) {
            container.innerHTML = data.data.map(a => {
                const annType = a.announcementType || 'system';
                const typeConfig = {
                    system: { icon: 'fa-gear', label: 'System', bg: '#f3e8ff', color: '#7c3aed' },
                    academic: { icon: 'fa-graduation-cap', label: 'Academic', bg: '#dbeafe', color: '#1d4ed8' },
                    campus: { icon: 'fa-building-columns', label: 'Campus', bg: '#dcfce7', color: '#15803d' }
                };
                const config = typeConfig[annType] || typeConfig.system;
                const isPinned = a.isPinned === 1 || a.isPinned === '1';
                const pinIcon = isPinned ? '<i class="fa-solid fa-thumbtack" style="color:#f59e0b;margin-right:8px;"></i>' : '';
                
                return `
                <a href="announcements.php?id=${a.id}" class="ann-list-item">
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:5px;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.3px;margin-right:12px;flex-shrink:0;background:${config.bg};color:${config.color};">
                        <i class="fa-solid ${config.icon}"></i>${config.label}
                    </span>
                    <span class="ann-list-title">${pinIcon}${escapeHtml(a.title)}</span>
                    <span class="ann-list-date">${formatDate(a.createdAt)}</span>
                    <i class="fa-solid fa-chevron-right ann-list-arrow"></i>
                </a>
            `}).join('');
        } else {
            container.innerHTML = `
                <div style="text-align: center; padding: 60px 20px; color: #94a3b8;">
                    <i class="fa-solid fa-bullhorn" style="font-size: 48px; margin-bottom: 16px; color: #c4b5fd;"></i>
                    <p style="font-size: 16px; margin: 0;">No announcements available at the moment.</p>
                </div>
            `;
        }
    } catch (err) {
        container.innerHTML = `
            <div style="text-align: center; padding: 60px 20px; color: #94a3b8;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 48px; margin-bottom: 16px; color: #dc2626;"></i>
                <p style="font-size: 16px; margin: 0;">Failed to load announcements. Please try again later.</p>
            </div>
        `;
    }
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
<?php endif; ?>

</body>
</html>
