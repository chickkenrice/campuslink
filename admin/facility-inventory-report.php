<?php
// File: admin/facility-inventory-report.php
// Campus Facility Inventory & Status Report
require_once '../includes/config.php';
session_start();

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied");
}
prevent_back_button_cache();

$db = get_db_connection();

// Get filter parameter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$typeFilter = isset($_GET['type']) ? $_GET['type'] : 'all';

// Build query based on filters
$sql = "SELECT * FROM facility WHERE 1=1";

if ($statusFilter !== 'all') {
    $sql .= " AND status = '" . $db->real_escape_string($statusFilter) . "'";
}

if ($typeFilter !== 'all') {
    $sql .= " AND type = '" . $db->real_escape_string($typeFilter) . "'";
}

$sql .= " ORDER BY type ASC, facilityName ASC";

$result = $db->query($sql);
$facilities = $result->fetch_all(MYSQLI_ASSOC);

// Get summary counts
$countResult = $db->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed,
    SUM(CASE WHEN status = 'Maintenance' THEN 1 ELSE 0 END) as maintenance,
    SUM(capacity) as totalCapacity
    FROM facility");
$counts = $countResult->fetch_assoc();

// Get type counts for summary
$typeCounts = [];
$typeResult = $db->query("SELECT type, COUNT(*) as count FROM facility GROUP BY type ORDER BY type");
while ($row = $typeResult->fetch_assoc()) {
    $typeCounts[$row['type']] = $row['count'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Campus Facility Inventory Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo time(); ?>">
    <style>
        @media screen {
            .filter-bar {
                background: white;
                padding: 20px;
                margin-bottom: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                display: flex;
                gap: 15px;
                align-items: end;
            }
            .filter-group {
                flex: 1;
            }
            .filter-group label {
                display: block;
                font-size: 12px;
                font-weight: 600;
                color: #555;
                margin-bottom: 5px;
            }
            .filter-group select {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 6px;
                font-size: 13px;
            }
            .filter-group button {
                padding: 8px 20px;
                background: var(--purple-base, #7c3aed);
                color: white;
                border: none;
                border-radius: 6px;
                font-weight: 600;
                cursor: pointer;
            }
        }
        @media print {
            .filter-bar, .fab-container { display: none !important; }
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-closed { background: #fee2e2; color: #991b1b; }
        .status-maintenance { background: #fef3c7; color: #92400e; }
        .type-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
            background: #e0e7ff;
            color: #3730a3;
        }
    </style>
</head>
<body class="users-report-page">

    <div class="fab-container">
        <button onclick="window.print()" class="fab">
            <i class="fa-solid fa-print"></i> Print / Save PDF
        </button>
        <button onclick="window.location.href='manage-resources.php'" class="fab fab-secondary">
            <i class="fa-solid fa-arrow-left"></i> Back
        </button>
    </div>

    <div class="filter-bar">
        <div class="filter-group">
            <label>Status</label>
            <select id="statusSelect" onchange="filterReport()">
                <option value="all" <?php echo ($statusFilter === 'all') ? 'selected' : ''; ?>>All Status</option>
                <option value="Active" <?php echo ($statusFilter === 'Active') ? 'selected' : ''; ?>>Active</option>
                <option value="Closed" <?php echo ($statusFilter === 'Closed') ? 'selected' : ''; ?>>Closed</option>
                <option value="Maintenance" <?php echo ($statusFilter === 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Type</label>
            <select id="typeSelect" onchange="filterReport()">
                <option value="all" <?php echo ($typeFilter === 'all') ? 'selected' : ''; ?>>All Types</option>
                <option value="Lab" <?php echo ($typeFilter === 'Lab') ? 'selected' : ''; ?>>Lab</option>
                <option value="Hall" <?php echo ($typeFilter === 'Hall') ? 'selected' : ''; ?>>Hall</option>
                <option value="Tutorial" <?php echo ($typeFilter === 'Tutorial') ? 'selected' : ''; ?>>Tutorial</option>
                <option value="Meeting" <?php echo ($typeFilter === 'Meeting') ? 'selected' : ''; ?>>Meeting</option>
                <option value="Sport" <?php echo ($typeFilter === 'Sport') ? 'selected' : ''; ?>>Sport</option>
                <option value="Study" <?php echo ($typeFilter === 'Study') ? 'selected' : ''; ?>>Study</option>
            </select>
        </div>
        <div class="filter-group" style="flex: 0;">
            <button onclick="filterReport()">Apply</button>
        </div>
    </div>

    <div class="page">
        <div class="header">
            <div class="logo-circle">CL</div>
            <div class="uni-name">CampusLink University</div>
            <div class="report-title">CAMPUS FACILITY INVENTORY & STATUS</div>
        </div>

        <div class="meta-box">
            <div class="meta-col">
                <span class="label">Total Facilities</span>
                <span class="value"><?php echo $counts['total']; ?></span>
            </div>
            <div class="meta-col">
                <span class="label">Active</span>
                <span class="value" style="color: #059669;"><?php echo $counts['active']; ?></span>
            </div>
            <div class="meta-col">
                <span class="label">Closed</span>
                <span class="value" style="color: #dc2626;"><?php echo $counts['closed']; ?></span>
            </div>
            <div class="meta-col">
                <span class="label">Maintenance</span>
                <span class="value" style="color: #d97706;"><?php echo $counts['maintenance']; ?></span>
            </div>
            <div class="meta-col">
                <span class="label">Total Capacity</span>
                <span class="value"><?php echo $counts['totalCapacity']; ?></span>
            </div>
        </div>

        <!-- Type Summary -->
        <?php if (!empty($typeCounts)): ?>
        <div style="background: #f9fafb; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px;">
            <div style="font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 10px;">
                <i class="fa-solid fa-chart-pie"></i> FACILITY DISTRIBUTION
            </div>
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <?php foreach ($typeCounts as $type => $count): ?>
                    <div style="font-size: 12px;">
                        <strong><?php echo htmlspecialchars($type); ?>:</strong> 
                        <span style="color: #6b7280;"><?php echo $count; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th style="width: 12%; text-align: center;">Facility ID</th>
                    <th style="width: 28%;">Facility Name</th>
                    <th style="width: 20%;">Location / Block</th>
                    <th style="width: 12%; text-align: center;">Type</th>
                    <th style="width: 10%; text-align: center;">Capacity</th>
                    <th style="width: 18%; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($facilities)): ?>
                    <?php foreach($facilities as $facility): 
                        $statusClass = 'status-' . strtolower($facility['status']);
                    ?>
                    <tr>
                        <td style="text-align: center; font-weight: 700; color: #6366f1;">
                            <?php echo htmlspecialchars($facility['facilityID']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($facility['facilityName']); ?></td>
                        <td style="color: #555;">
                            <?php echo htmlspecialchars($facility['location'] ?? '-'); ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="type-badge"><?php echo htmlspecialchars($facility['type']); ?></span>
                        </td>
                        <td style="text-align: center; font-weight: 600;">
                            <?php echo $facility['capacity'] ? $facility['capacity'] : 'N/A'; ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($facility['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding: 40px; color: #6b7280;">
                            <i class="fa-solid fa-inbox" style="font-size: 36px; margin-bottom: 10px; opacity: 0.3; display: block;"></i>
                            No facility records found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer" style="margin-top: 30px;">
            <div style="font-size: 11px; color: #6b7280;">
                <strong>Status Summary:</strong><br>
                Active: <?php echo $counts['active']; ?> facilities available for use<br>
                Closed: <?php echo $counts['closed']; ?> facilities temporarily unavailable<br>
                Maintenance: <?php echo $counts['maintenance']; ?> facilities under maintenance
            </div>
            <div class="signature">
                <div class="sig-line">
                    Administrator<br>
                    <span style="font-weight: 400; font-size: 11px; color: #555;">Report Generated By</span>
                </div>
                <div style="font-size: 10px; color: #666; margin-top: 5px;">
                    Generated: <?php echo date('d-m-Y H:i'); ?>
                </div>
            </div>
        </div>

        <div class="page-footer">
            <span>*** END OF REPORT ***</span>
            <span>Page 1 of 1</span>
        </div>
    </div>

    <script>
        function filterReport() {
            const status = document.getElementById('statusSelect').value;
            const type = document.getElementById('typeSelect').value;
            let url = 'facility-inventory-report.php?';
            const params = [];
            if (status !== 'all') params.push('status=' + status);
            if (type !== 'all') params.push('type=' + type);
            window.location.href = url + params.join('&');
        }
    </script>

</body>
</html>
