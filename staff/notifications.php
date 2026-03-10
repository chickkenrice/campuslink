<?php
session_start();
require_once(__DIR__ . '/../includes/config.php');

if (!validate_session() || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit;
}

$userID = $_SESSION['user_id'];
$db = get_db_connection();

// Fetch staff details
$staffName = $_SESSION['user_name'] ?? 'Staff Member';

// Pagination
$perPage = 15;
$pageNum = max(1, intval($_GET['page'] ?? 1));
$offset  = ($pageNum - 1) * $perPage;

// Total count
$stmtTotal = $db->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE userID = ?");
$stmtTotal->bind_param("s", $userID);
$stmtTotal->execute();
$total      = (int)$stmtTotal->get_result()->fetch_assoc()['cnt'];
$totalPages = max(1, (int)ceil($total / $perPage));

// Fetch notifications for this page
$stmt = $db->prepare("SELECT id, type, title, message, link, isRead, createdAt
                      FROM notifications
                      WHERE userID = ?
                      ORDER BY isRead ASC, createdAt DESC
                      LIMIT ? OFFSET ?");
$stmt->bind_param("sii", $userID, $perPage, $offset);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Mark all as read when the page is opened
$markStmt = $db->prepare("UPDATE notifications SET isRead = 1 WHERE userID = ?");
$markStmt->bind_param("s", $userID);
$markStmt->execute();

// Helper
function timeAgoLocal($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('d-m-Y', strtotime($datetime));
}

function getIconClass($type) {
    $map = [
        'class_soon'     => 'class-soon',
        'new_assignment' => 'new-assignment',
        'booking_update' => 'booking-update',
        'announcement'   => 'announcement',
    ];
    return $map[$type] ?? 'announcement';
}

function getIconSymbol($type) {
    $map = [
        'class_soon'     => 'fa-solid fa-clock',
        'new_assignment' => 'fa-solid fa-file-pen',
        'booking_update' => 'fa-solid fa-building',
        'announcement'   => 'fa-solid fa-bullhorn',
    ];
    return $map[$type] ?? 'fa-solid fa-bell';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notifications - CAMPUSLink</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .notif-page-header {
            margin-bottom: 24px;
        }

        .notif-page-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(128, 86, 255, 0.08);
        }

        .notif-page-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 16px 24px;
            border-bottom: 1px solid #f3f4f6;
            text-decoration: none;
            color: inherit;
            transition: background 0.15s;
            position: relative;
        }

        .notif-page-item:last-child {
            border-bottom: none;
        }

        .notif-page-item:hover {
            background: #f8f5ff;
        }

        .notif-page-item.unread {
            background: #faf8ff;
        }

        .notif-page-item.unread::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #8056ff;
            border-radius: 0 4px 4px 0;
        }

        .notif-page-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            flex-shrink: 0;
        }

        .notif-page-icon.class-soon     { background: #fef3c7; color: #d97706; }
        .notif-page-icon.new-assignment { background: #dbeafe; color: #2563eb; }
        .notif-page-icon.booking-update { background: #d1fae5; color: #059669; }
        .notif-page-icon.announcement   { background: #f3e8ff; color: #7c3aed; }

        .notif-page-body {
            flex: 1;
            min-width: 0;
        }

        .notif-page-title {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 3px;
        }

        .notif-page-message {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.5;
        }

        .notif-page-time {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 5px;
        }

        .notif-page-empty {
            padding: 72px 20px;
            text-align: center;
            color: #9ca3af;
        }

        .notif-page-empty i {
            font-size: 48px;
            color: #e5e7eb;
            margin-bottom: 16px;
        }

        .notif-page-empty p {
            margin: 4px 0;
            font-size: 14px;
        }

        .notif-page-empty p:first-of-type {
            font-weight: 600;
            color: #6b7280;
            font-size: 15px;
        }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 28px;
        }

        .pagination a,
        .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }

        .pagination a {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #e5e7eb;
        }

        .pagination a:hover {
            background: #f3efff;
            color: #8056ff;
            border-color: #d0b3ff;
        }

        .pagination span.current {
            background: #8056ff;
            color: #fff;
            border: 1px solid #8056ff;
        }

        .pagination span.disabled {
            background: #f9fafb;
            color: #d1d5db;
            border: 1px solid #f3f4f6;
            cursor: not-allowed;
        }

        .notif-count-info {
            font-size: 13px;
            color: #9ca3af;
            text-align: center;
            margin-top: 12px;
        }
    </style>
</head>
<body>

<div class="app-bg">
    <div class="main-card">

        <?php $currentPage = 'notifications'; include __DIR__ . '/../includes/staffMaster.php'; ?>

        <main class="dashboard">
            <header class="dashboard-topbar">
                <div class="topbar-right">
                    <?php include __DIR__ . '/../includes/notificationBell.php'; ?>
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

            <!-- Page heading banner -->
            <section class="welcome-card notif-page-header">
                <div class="welcome-details">
                    <h1 class="welcome-title">Notifications</h1>
                    <div class="welcome-meta">
                        <span class="welcome-meta-sub">
                            <i class="fa-solid fa-bell"></i>
                            <?php echo $total; ?> notification<?php echo $total !== 1 ? 's' : ''; ?> total
                        </span>
                    </div>
                </div>
            </section>

            <!-- Notifications card -->
            <div class="notif-page-card">
                <?php if (empty($notifications)): ?>
                    <div class="notif-page-empty">
                        <i class="fa-regular fa-bell-slash"></i>
                        <p>You're all caught up</p>
                        <p>No notifications yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notif):
                        $iconCls = getIconClass($notif['type']);
                        $iconSym = getIconSymbol($notif['type']);
                        $unreadCls = $notif['isRead'] ? '' : 'unread';
                        $href = htmlspecialchars($notif['link'] ?? '#');
                        if ($href === '#' || $href === '') $href = '#';
                    ?>
                    <a href="<?php echo $href; ?>" class="notif-page-item <?php echo $unreadCls; ?>">
                        <div class="notif-page-icon <?php echo $iconCls; ?>">
                            <i class="<?php echo $iconSym; ?>"></i>
                        </div>
                        <div class="notif-page-body">
                            <div class="notif-page-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                            <div class="notif-page-message"><?php echo htmlspecialchars($notif['message'] ?? ''); ?></div>
                            <div class="notif-page-time"><?php echo timeAgoLocal($notif['createdAt']); ?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="pagination">
                <?php if ($pageNum > 1): ?>
                    <a href="?page=<?php echo $pageNum - 1; ?>"><i class="fa-solid fa-chevron-left"></i></a>
                <?php else: ?>
                    <span class="disabled"><i class="fa-solid fa-chevron-left"></i></span>
                <?php endif; ?>

                <?php
                $range = 2;
                $start = max(1, $pageNum - $range);
                $end   = min($totalPages, $pageNum + $range);
                if ($start > 1): ?>
                    <a href="?page=1">1</a>
                    <?php if ($start > 2): ?><span class="disabled">...</span><?php endif; ?>
                <?php endif;

                for ($p = $start; $p <= $end; $p++):
                    if ($p === $pageNum): ?>
                        <span class="current"><?php echo $p; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $p; ?>"><?php echo $p; ?></a>
                    <?php endif;
                endfor;

                if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?><span class="disabled">...</span><?php endif; ?>
                    <a href="?page=<?php echo $totalPages; ?>"><?php echo $totalPages; ?></a>
                <?php endif; ?>

                <?php if ($pageNum < $totalPages): ?>
                    <a href="?page=<?php echo $pageNum + 1; ?>"><i class="fa-solid fa-chevron-right"></i></a>
                <?php else: ?>
                    <span class="disabled"><i class="fa-solid fa-chevron-right"></i></span>
                <?php endif; ?>
            </nav>
            <p class="notif-count-info">
                Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $perPage, $total); ?> of <?php echo $total; ?> notifications
            </p>
            <?php endif; ?>

            <?php include __DIR__ . '/../includes/footer.php'; ?>

        </main>
    </div>
</div>

</body>
</html>
