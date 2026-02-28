<?php
session_start();
require_once(__DIR__ . '/../includes/config.php');
require_once(__DIR__ . '/../includes/activity-logger.php');

// =========================================================
// 1. SECURITY CHECK
// =========================================================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
prevent_back_button_cache();

$db = get_db_connection();

// =========================================================
// 2. HANDLE EXPORT REQUEST
// =========================================================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filters = [
        'startDate' => $_GET['startDate'] ?? '',
        'endDate' => $_GET['endDate'] ?? '',
        'userRole' => $_GET['filterRole'] ?? '',
        'activityType' => $_GET['filterType'] ?? '',
        'search' => $_GET['search'] ?? ''
    ];
    
    $csv = exportActivityLogsToCSV($db, $filters);
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="activity_logs_' . date('Y-m-d_His') . '.csv"');
    echo $csv;
    exit;
}

// =========================================================
// 3. GET FILTER PARAMETERS
// =========================================================
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 50;
$activeTab = $_GET['tab'] ?? 'all';

// Build filters based on active tab and user input
$filters = [];

// Date filters
$filters['startDate'] = $_GET['startDate'] ?? '';
$filters['endDate'] = $_GET['endDate'] ?? '';

// Role filter based on active tab (like manage-students.php)
if ($activeTab === 'student') {
    $filters['userRole'] = 'Student';
} elseif ($activeTab === 'staff') {
    $filters['userRole'] = 'Staff';
} elseif ($activeTab === 'admin') {
    $filters['userRole'] = 'Admin';
} else {
    $filters['userRole'] = $_GET['filterRole'] ?? '';
}

// Other filters
$filters['activityType'] = $_GET['filterType'] ?? '';
$filters['search'] = $_GET['search'] ?? '';

// =========================================================
// 4. FETCH DATA
// =========================================================
$logsData = getActivityLogs($db, $filters, $currentPage, $perPage);
$stats = getActivityStats($db, 'month');

// Get role-specific counts for tab badges
$studentCount = $db->query("SELECT COUNT(*) as c FROM activity_logs WHERE userRole = 'Student'")->fetch_assoc()['c'];
$staffCount = $db->query("SELECT COUNT(*) as c FROM activity_logs WHERE userRole = 'Staff'")->fetch_assoc()['c'];
$adminCount = $db->query("SELECT COUNT(*) as c FROM activity_logs WHERE userRole = 'Admin'")->fetch_assoc()['c'];
$allCount = $db->query("SELECT COUNT(*) as c FROM activity_logs")->fetch_assoc()['c'];

// Get unique activity types for filter dropdown
$activityTypesResult = $db->query("SELECT DISTINCT activity_type FROM activity_logs ORDER BY activity_type");
$activityTypes = [];
while ($row = $activityTypesResult->fetch_assoc()) {
    $activityTypes[] = $row['activity_type'];
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Console — Activity Logs</title>
    
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>

    <div class="app-bg">
        <div class="main-card">
            
            <aside class="sidebar">
                <div class="sidebar-head">
                    <div class="brand-icon">CL</div>
                    <div class="brand-text">
                        <span class="brand-name">CAMPUSLink</span>
                        <span class="brand-tagline">Admin Console</span>
                    </div>
                </div>
                <nav class="sidebar-nav">
                    <ul>
                        <li>
                            <a href="manage-students.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-users-gear"></i></span>
                                <span class="nav-label">User Management</span>
                            </a>
                        </li>
                        <li>
                            <a href="activity-logs.php" class="nav-item is-active">
                                <span class="nav-icon"><i class="fa-solid fa-chart-line"></i></span>
                                <span class="nav-label">Activity Logs</span>
                            </a>
                        </li>
                        <li>
                            <a href="manage-timetable.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                <span class="nav-label">Timetable</span>
                            </a>
                        </li>
                        <li>
                            <a href="manage-resources.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-building"></i></span>
                                <span class="nav-label">Resources</span>
                            </a>
                        </li>
                        <li>
                            <a href="registration-monitor.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-chart-bar"></i></span>
                                <span class="nav-label">Registration Monitor</span>
                            </a>
                        </li>
                        <li>
                            <a href="manage-terms.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-calendar-plus"></i></span>
                                <span class="nav-label">Academic Terms</span>
                            </a>
                        </li>
                        <li>
                            <a href="../logout.php" class="nav-item">
                                <span class="nav-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                                <span class="nav-label">Logout</span>
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
                                <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                                <span class="user-role">Admin</span>
                            </div>
                            <div class="profile-pic">
                                <i class="fa-solid fa-user"></i>
                            </div>
                        </div>
                    </div>
                </header>
                
                <section class="welcome-card">
                    <div class="welcome-details">
                        <p class="welcome-date"><?php echo date('l, j F Y'); ?></p>
                        <h1 class="welcome-title">Activity Logs</h1>
                        <p class="welcome-text">Monitor and audit all system activities</p>
                    </div>
                </section>

                <section class="announcements-card" style="margin-top: 20px;">
                    
                    <!-- Statistics Cards -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div class="stat-icon" style="background: #f3e5ff; color: #8056ff;">
                                    <i class="fa-solid fa-chart-line"></i>
                                </div>
                                <div>
                                    <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                                    <div class="stat-label">Total Activities</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div class="stat-icon" style="background: #e5f6ff; color: #00a8ff;">
                                    <i class="fa-solid fa-users"></i>
                                </div>
                                <div>
                                    <div class="stat-value"><?php echo count($stats['mostActive']); ?></div>
                                    <div class="stat-label">Active Users</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div class="stat-icon" style="background: #e7f5e9; color: #2d9f44;">
                                    <i class="fa-solid fa-list-check"></i>
                                </div>
                                <div>
                                    <div class="stat-value"><?php echo count($stats['byType']); ?></div>
                                    <div class="stat-label">Activity Types</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div class="stat-icon" style="background: #fff3e0; color: #ff9800;">
                                    <i class="fa-solid fa-clock"></i>
                                </div>
                                <div>
                                    <div class="stat-value"><?php echo ucfirst($activeTab); ?></div>
                                    <div class="stat-label">Current View</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Navigation -->
                    <nav class="tab-nav">
                        <a href="?tab=all" class="tab-btn <?php echo $activeTab == 'all' ? 'active' : ''; ?>">All Users</a>
                        <a href="?tab=student" class="tab-btn <?php echo $activeTab == 'student' ? 'active' : ''; ?>">Students</a>
                        <a href="?tab=staff" class="tab-btn <?php echo $activeTab == 'staff' ? 'active' : ''; ?>">Staff</a>
                        <a href="?tab=admin" class="tab-btn <?php echo $activeTab == 'admin' ? 'active' : ''; ?>">Admins</a>
                    </nav>
                    
                    <!-- Filters Section -->
                    <div class="filters-section">
                        <form method="GET" action="">
                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($activeTab); ?>">
                            
                            <div class="filters-grid">
                                <div class="filter-field">
                                    <label for="startDate">
                                        <i class="fa-solid fa-calendar-day"></i> Start Date
                                    </label>
                                    <input type="date" id="startDate" name="startDate" value="<?php echo htmlspecialchars($filters['startDate']); ?>">
                                </div>
                                
                                <div class="filter-field">
                                    <label for="endDate">
                                        <i class="fa-solid fa-calendar-day"></i> End Date
                                    </label>
                                    <input type="date" id="endDate" name="endDate" value="<?php echo htmlspecialchars($filters['endDate']); ?>">
                                </div>
                                
                                <?php if ($activeTab === 'all'): ?>
                                <div class="filter-field">
                                    <label for="filterRole">
                                        <i class="fa-solid fa-user-tag"></i> User Role
                                    </label>
                                    <select id="filterRole" name="filterRole">
                                        <option value="">All Roles</option>
                                        <option value="Student" <?php echo $filters['userRole'] === 'Student' ? 'selected' : ''; ?>>Student</option>
                                        <option value="Staff" <?php echo $filters['userRole'] === 'Staff' ? 'selected' : ''; ?>>Staff</option>
                                        <option value="Admin" <?php echo $filters['userRole'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                                <?php endif; ?>
                                
                                <div class="filter-field">
                                    <label for="filterType">
                                        <i class="fa-solid fa-filter"></i> Activity Type
                                    </label>
                                    <select id="filterType" name="filterType">
                                        <option value="">All Types</option>
                                        <?php foreach ($activityTypes as $type): ?>
                                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filters['activityType'] === $type ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($type); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="filter-field" style="grid-column: span 2;">
                                    <label for="search">
                                        <i class="fa-solid fa-search"></i> Search (User Name or Description)
                                    </label>
                                    <input type="text" id="search" name="search" placeholder="Search activities..." value="<?php echo htmlspecialchars($filters['search']); ?>">
                                </div>
                            </div>
                            
                            <div class="filter-actions">
                                <button type="submit" class="btn-filter">
                                    <i class="fa-solid fa-filter"></i> Apply Filters
                                </button>
                                <button type="button" class="btn-clear" onclick="window.location.href='?tab=<?php echo $activeTab; ?>'">
                                    <i class="fa-solid fa-times"></i> Clear Filters
                                </button>
                                <button type="button" class="btn-export" onclick="exportLogs()">
                                    <i class="fa-solid fa-download"></i> Export to CSV
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Logs Table -->
                    <div class="table-container" style="max-height: calc(100vh - 550px);">
                        <table class="logs-table">
                            <thead>
                                <tr>
                                    <th style="width: 160px;">Timestamp</th>
                                    <?php if ($activeTab === 'all'): ?>
                                        <th style="width: 100px;">User ID</th>
                                    <?php else: ?>
                                        <th style="width: 100px;"><?php echo ucfirst($activeTab); ?> ID</th>
                                    <?php endif; ?>
                                    <th>User Name</th>
                                    <?php if ($activeTab === 'all'): ?>
                                    <th style="width: 80px;">Role</th>
                                    <?php endif; ?>
                                    <th style="width: 120px;">Activity Type</th>
                                    <th>Description</th>
                                    <th style="width: 80px;">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($logsData['logs'])): ?>
                                    <?php foreach ($logsData['logs'] as $log): ?>
                                        <tr>
                                            <td class="timestamp">
                                                <i class="fa-solid fa-clock"></i>
                                                <?php echo date('d M Y H:i:s', strtotime($log['timestamp'])); ?>
                                            </td>
                                            <td style="font-weight: 600; font-size: 13px;">
                                                <?php echo htmlspecialchars($log['userID'] ?? 'N/A'); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['userName'] ?? 'N/A'); ?></td>
                                            <?php if ($activeTab === 'all'): ?>
                                            <td>
                                                <?php if ($log['userRole']): ?>
                                                    <?php 
                                                    $badgeClass = 'badge-student';
                                                    if ($log['userRole'] === 'Admin') $badgeClass = 'badge-admin';
                                                    if ($log['userRole'] === 'Staff') $badgeClass = 'badge-staff';
                                                    ?>
                                                    <span class="role-badge <?php echo $badgeClass; ?>">
                                                        <?php echo htmlspecialchars($log['userRole']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #999;">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php endif; ?>
                                            <td>
                                                <?php 
                                                $activityClass = 'activity-default';
                                                $type = strtolower($log['activity_type']);
                                                if (strpos($type, 'login') !== false) $activityClass = 'activity-login';
                                                elseif (strpos($type, 'logout') !== false) $activityClass = 'activity-logout';
                                                elseif (strpos($type, 'create') !== false) $activityClass = 'activity-create';
                                                elseif (strpos($type, 'update') !== false || strpos($type, 'profile') !== false) $activityClass = 'activity-update';
                                                elseif (strpos($type, 'delete') !== false) $activityClass = 'activity-delete';
                                                elseif (strpos($type, 'attendance') !== false) $activityClass = 'activity-attendance';
                                                elseif (strpos($type, 'submit') !== false || strpos($type, 'assignment') !== false) $activityClass = 'activity-submit';
                                                elseif (strpos($type, 'profile') !== false) $activityClass = 'activity-profile';
                                                ?>
                                                <span class="activity-badge <?php echo $activityClass; ?>">
                                                    <?php echo htmlspecialchars($log['activity_type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['activity_description']); ?></td>
                                            <td>
                                                <?php if ($log['details']): ?>
                                                    <button class="details-btn" onclick='showDetails(<?php echo json_encode($log['details']); ?>)'>
                                                        <i class="fa-solid fa-eye"></i> View
                                                    </button>
                                                <?php else: ?>
                                                    <span style="color: #ccc; font-size: 12px;">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?php echo $activeTab === 'all' ? '7' : '6'; ?>">
                                            <div class="no-data">
                                                <div><i class="fa-solid fa-inbox"></i></div>
                                                <div style="font-weight: 600; font-size: 16px; margin-bottom: 5px;">No Activity Logs Found</div>
                                                <div style="font-size: 14px;">Try adjusting your filters or date range</div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($logsData['totalPages'] > 1): ?>
                        <div class="pagination">
                            <?php
                            $queryParams = $_GET;
                            unset($queryParams['page']);
                            $baseQuery = http_build_query($queryParams);
                            ?>
                            
                            <?php if ($currentPage > 1): ?>
                                <a href="?<?php echo $baseQuery; ?>&page=<?php echo $currentPage - 1; ?>">
                                    <i class="fa-solid fa-chevron-left"></i> Previous
                                </a>
                            <?php else: ?>
                                <span class="disabled">
                                    <i class="fa-solid fa-chevron-left"></i> Previous
                                </span>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($logsData['totalPages'], $currentPage + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <?php if ($i == $currentPage): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?<?php echo $baseQuery; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($currentPage < $logsData['totalPages']): ?>
                                <a href="?<?php echo $baseQuery; ?>&page=<?php echo $currentPage + 1; ?>">
                                    Next <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            <?php else: ?>
                                <span class="disabled">
                                    Next <i class="fa-solid fa-chevron-right"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="text-align: center; color: #999; font-size: 13px; margin-top: 10px;">
                        Showing <?php echo count($logsData['logs']); ?> of <?php echo number_format($logsData['total']); ?> total activities
                    </div>
                    
                </section>
            </main>
        </div>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeDetailsModal()">&times;</span>
            <h2 style="margin-top: 0; color: #8056ff;">
                <i class="fa-solid fa-info-circle"></i> Activity Details
            </h2>
            <pre id="detailsContent" style="background: #f9f9f9; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 13px; line-height: 1.6;"></pre>
        </div>
    </div>

    <script>
        const detailsModal = document.getElementById('detailsModal');
        
        function showDetails(details) {
            const content = document.getElementById('detailsContent');
            try {
                const parsed = JSON.parse(details);
                content.textContent = JSON.stringify(parsed, null, 2);
            } catch (e) {
                content.textContent = details;
            }
            detailsModal.classList.add('open');
        }
        
        function closeDetailsModal() {
            detailsModal.classList.remove('open');
        }
        
        function exportLogs() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.location.href = '?' + params.toString();
        }
        
        // Close modal when clicking outside
        detailsModal.addEventListener('click', function(e) {
            if (e.target === detailsModal) {
                closeDetailsModal();
            }
        });
    </script>
</body>
</html>
