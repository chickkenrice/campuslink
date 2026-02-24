<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/config.php';

if (!validate_session()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = get_db_connection();
$userID = $_SESSION['user_id'];
$userRole = $_SESSION['role']; // 'student' or 'staff'

// ============================================================
// GET REQUESTS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {

        // ----------------------------------------------------------
        // 1. Get bookable facilities (exclude Halls - used for classes only)
        // ----------------------------------------------------------
        case 'getBookableFacilities':
            $typeFilter = $_GET['type'] ?? '';

            $query = "SELECT f.facilityID, f.facilityName, f.type, f.location, f.capacity, 
                             br.maxDurationMinutes, br.maxAdvanceDays, br.maxActiveBookings, 
                             br.allowedRoles, br.operatingStart, br.operatingEnd
                      FROM facility f
                      LEFT JOIN booking_rules br ON f.type = br.facilityType
                      WHERE f.status = 'Active' AND f.type != 'Hall'";

            $params = [];
            $types = '';

            if (!empty($typeFilter)) {
                $query .= " AND f.type = ?";
                $params[] = $typeFilter;
                $types .= 's';
            }

            // Filter by role access
            $roleLabel = ucfirst($userRole); // 'Student' or 'Staff'
            $query .= " AND (br.allowedRoles IS NULL OR br.allowedRoles LIKE CONCAT('%', ?, '%'))";
            $params[] = $roleLabel;
            $types .= 's';

            $query .= " ORDER BY f.type, f.facilityName";

            $stmt = $db->prepare($query);
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            $facilities = [];
            while ($row = $result->fetch_assoc()) {
                $facilities[] = $row;
            }

            // Get distinct types for filter
            $typeStmt = $db->prepare(
                "SELECT DISTINCT f.type FROM facility f 
                 LEFT JOIN booking_rules br ON f.type = br.facilityType
                 WHERE f.status = 'Active' AND f.type != 'Hall'
                 AND (br.allowedRoles IS NULL OR br.allowedRoles LIKE CONCAT('%', ?, '%'))
                 ORDER BY f.type"
            );
            $typeStmt->bind_param("s", $roleLabel);
            $typeStmt->execute();
            $typeResult = $typeStmt->get_result();
            $availableTypes = [];
            while ($t = $typeResult->fetch_assoc()) {
                $availableTypes[] = $t['type'];
            }

            echo json_encode(['success' => true, 'data' => $facilities, 'types' => $availableTypes]);
            break;

        // ----------------------------------------------------------
        // 2. Get available time slots for a facility on a date
        // ----------------------------------------------------------
        case 'getAvailableSlots':
            $facilityID = $_GET['facilityID'] ?? '';
            $date = $_GET['date'] ?? '';

            if (empty($facilityID) || empty($date)) {
                echo json_encode(['success' => false, 'message' => 'Facility ID and date required']);
                exit;
            }

            // Validate date is not in the past
            if ($date < date('Y-m-d')) {
                echo json_encode(['success' => false, 'message' => 'Cannot book for past dates']);
                exit;
            }

            // Get facility info & rules
            $facStmt = $db->prepare(
                "SELECT f.*, br.maxDurationMinutes, br.operatingStart, br.operatingEnd, br.maxAdvanceDays
                 FROM facility f 
                 LEFT JOIN booking_rules br ON f.type = br.facilityType
                 WHERE f.facilityID = ?"
            );
            $facStmt->bind_param("s", $facilityID);
            $facStmt->execute();
            $facility = $facStmt->get_result()->fetch_assoc();

            if (!$facility) {
                echo json_encode(['success' => false, 'message' => 'Facility not found']);
                exit;
            }

            // Validate advance booking limit
            $maxAdvance = $facility['maxAdvanceDays'] ?? 7;
            $maxDate = date('Y-m-d', strtotime("+{$maxAdvance} days"));
            if ($date > $maxDate) {
                echo json_encode(['success' => false, 'message' => "Cannot book more than {$maxAdvance} days in advance"]);
                exit;
            }

            $opStart = $facility['operatingStart'] ?? '08:00:00';
            $opEnd = $facility['operatingEnd'] ?? '22:00:00';
            $maxDuration = $facility['maxDurationMinutes'] ?? 120;

            // Collect all blocked time ranges for this facility on this date
            $blocked = [];
            $dayOfWeek = date('l', strtotime($date));

            // Source 1: Regular class schedule (recurring weekly)
            $classStmt = $db->prepare(
                "SELECT startTime, endTime, courseID FROM class_schedule 
                 WHERE facilityID = ? AND day = ?"
            );
            $classStmt->bind_param("ss", $facilityID, $dayOfWeek);
            $classStmt->execute();
            $classResult = $classStmt->get_result();
            while ($row = $classResult->fetch_assoc()) {
                $blocked[] = [
                    'start' => substr($row['startTime'], 0, 5),
                    'end' => substr($row['endTime'], 0, 5),
                    'reason' => 'Class: ' . $row['courseID']
                ];
            }

            // Source 2: Approved replacement classes on this specific date
            $replStmt = $db->prepare(
                "SELECT rr.newTime, cs.courseID FROM replacement_request rr
                 JOIN class_schedule cs ON rr.scheduleID = cs.scheduleID
                 WHERE rr.facilityID = ? AND rr.newDate = ? AND rr.status = 'Approved'"
            );
            $replStmt->bind_param("ss", $facilityID, $date);
            $replStmt->execute();
            $replResult = $replStmt->get_result();
            while ($row = $replResult->fetch_assoc()) {
                $timeParts = explode(' - ', $row['newTime']);
                if (count($timeParts) === 2) {
                    $blocked[] = [
                        'start' => trim($timeParts[0]),
                        'end' => trim($timeParts[1]),
                        'reason' => 'Replacement: ' . $row['courseID']
                    ];
                }
            }

            // But also: if a class has an approved replacement AWAY from this facility on this date,
            // then that original slot is freed. We handle this by checking if original classes are replaced.
            // Remove blocked entries from class_schedule if they have approved replacements on this date
            $freedStmt = $db->prepare(
                "SELECT cs.startTime, cs.endTime FROM replacement_request rr
                 JOIN class_schedule cs ON rr.scheduleID = cs.scheduleID
                 WHERE cs.facilityID = ? AND rr.originalDate = ? AND rr.status = 'Approved'"
            );
            $freedStmt->bind_param("ss", $facilityID, $date);
            $freedStmt->execute();
            $freedResult = $freedStmt->get_result();
            $freed = [];
            while ($row = $freedResult->fetch_assoc()) {
                $freed[] = [
                    'start' => substr($row['startTime'], 0, 5),
                    'end' => substr($row['endTime'], 0, 5)
                ];
            }

            // Remove freed slots from blocked
            if (!empty($freed)) {
                $blocked = array_values(array_filter($blocked, function($b) use ($freed) {
                    foreach ($freed as $f) {
                        if ($b['start'] === $f['start'] && $b['end'] === $f['end'] && strpos($b['reason'], 'Class:') === 0) {
                            return false; // This class slot is freed by replacement
                        }
                    }
                    return true;
                }));
            }

            // Source 3: Existing active bookings
            $bookStmt = $db->prepare(
                "SELECT startTime, endTime, userID FROM facility_booking 
                 WHERE facilityID = ? AND bookingDate = ? AND status = 'Active'"
            );
            $bookStmt->bind_param("ss", $facilityID, $date);
            $bookStmt->execute();
            $bookResult = $bookStmt->get_result();
            while ($row = $bookResult->fetch_assoc()) {
                $blocked[] = [
                    'start' => substr($row['startTime'], 0, 5),
                    'end' => substr($row['endTime'], 0, 5),
                    'reason' => 'Booked'
                ];
            }

            // Generate 30-minute time slots within operating hours
            $slots = [];
            $current = strtotime($opStart);
            $end = strtotime($opEnd);

            while ($current < $end) {
                $slotStart = date('H:i', $current);
                $slotEnd = date('H:i', $current + 30 * 60);
                
                // Check if this slot overlaps with any blocked range
                $isBlocked = false;
                $blockReason = '';
                foreach ($blocked as $b) {
                    // Overlap: slotStart < blockedEnd AND slotEnd > blockedStart
                    if ($slotStart < $b['end'] && $slotEnd > $b['start']) {
                        $isBlocked = true;
                        $blockReason = $b['reason'];
                        break;
                    }
                }

                // If booking is for today, block past time slots
                if ($date === date('Y-m-d') && $slotStart < date('H:i')) {
                    $isBlocked = true;
                    $blockReason = 'Past';
                }

                $slots[] = [
                    'start' => $slotStart,
                    'end' => $slotEnd,
                    'available' => !$isBlocked,
                    'reason' => $isBlocked ? $blockReason : ''
                ];

                $current += 30 * 60;
            }

            echo json_encode([
                'success' => true,
                'facility' => [
                    'facilityID' => $facility['facilityID'],
                    'facilityName' => $facility['facilityName'],
                    'type' => $facility['type'],
                    'location' => $facility['location'],
                    'capacity' => $facility['capacity']
                ],
                'slots' => $slots,
                'maxDuration' => $maxDuration,
                'operatingHours' => substr($opStart, 0, 5) . ' - ' . substr($opEnd, 0, 5)
            ]);
            break;

        // ----------------------------------------------------------
        // 3. Get my bookings (student or staff)
        // ----------------------------------------------------------
        case 'getMyBookings':
            $statusFilter = $_GET['status'] ?? '';

            $query = "SELECT fb.*, f.facilityName, f.type, f.location
                      FROM facility_booking fb
                      JOIN facility f ON fb.facilityID = f.facilityID
                      WHERE fb.userID = ?";
            $params = [$userID];
            $types = 's';

            if (!empty($statusFilter)) {
                $query .= " AND fb.status = ?";
                $params[] = $statusFilter;
                $types .= 's';
            }

            $query .= " ORDER BY fb.bookingDate DESC, fb.startTime DESC";

            $stmt = $db->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            $bookings = [];
            while ($row = $result->fetch_assoc()) {
                $bookings[] = $row;
            }

            echo json_encode(['success' => true, 'data' => $bookings]);
            break;

        // ----------------------------------------------------------
        // 4. Get all bookings (admin only)
        // ----------------------------------------------------------
        case 'getAllBookings':
            if ($userRole !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                exit;
            }

            $dateFrom = $_GET['dateFrom'] ?? date('Y-m-d');
            $dateTo = $_GET['dateTo'] ?? date('Y-m-d', strtotime('+7 days'));
            $typeFilter = $_GET['type'] ?? '';
            $statusFilter = $_GET['status'] ?? '';

            $query = "SELECT fb.*, f.facilityName, f.type, f.location,
                             CASE fb.userRole 
                                 WHEN 'Student' THEN (SELECT studentName FROM student WHERE studentID = fb.userID)
                                 WHEN 'Staff' THEN (SELECT staffName FROM staff WHERE staffID = fb.userID)
                             END as userName
                      FROM facility_booking fb
                      JOIN facility f ON fb.facilityID = f.facilityID
                      WHERE fb.bookingDate BETWEEN ? AND ?";
            $params = [$dateFrom, $dateTo];
            $types = 'ss';

            if (!empty($typeFilter)) {
                $query .= " AND f.type = ?";
                $params[] = $typeFilter;
                $types .= 's';
            }

            if (!empty($statusFilter)) {
                $query .= " AND fb.status = ?";
                $params[] = $statusFilter;
                $types .= 's';
            }

            $query .= " ORDER BY fb.bookingDate, fb.startTime";

            $stmt = $db->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            $bookings = [];
            while ($row = $result->fetch_assoc()) {
                $bookings[] = $row;
            }

            // Get summary stats
            $statsStmt = $db->prepare(
                "SELECT COUNT(*) as total,
                        SUM(CASE WHEN fb.status = 'Active' THEN 1 ELSE 0 END) as active,
                        SUM(CASE WHEN fb.status = 'Completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN fb.status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
                        SUM(CASE WHEN fb.userRole = 'Student' THEN 1 ELSE 0 END) as byStudents,
                        SUM(CASE WHEN fb.userRole = 'Staff' THEN 1 ELSE 0 END) as byStaff
                 FROM facility_booking fb
                 WHERE fb.bookingDate BETWEEN ? AND ?"
            );
            $statsStmt->bind_param("ss", $dateFrom, $dateTo);
            $statsStmt->execute();
            $stats = $statsStmt->get_result()->fetch_assoc();

            echo json_encode(['success' => true, 'data' => $bookings, 'stats' => $stats]);
            break;

        // ----------------------------------------------------------
        // 5. Get booking rules
        // ----------------------------------------------------------
        case 'getRules':
            $result = $db->query("SELECT * FROM booking_rules ORDER BY facilityType");
            $rules = [];
            while ($row = $result->fetch_assoc()) {
                $rules[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $rules]);
            break;

        // ----------------------------------------------------------
        // 6. Get facility schedule for a date (admin - timeline view)
        // ----------------------------------------------------------
        case 'getFacilitySchedule':
            $schedDate = $_GET['date'] ?? date('Y-m-d');
            $schedType = $_GET['type'] ?? '';
            $dayOfWeek = date('l', strtotime($schedDate));

            // Get all bookable facilities (exclude Hall)
            $facQuery = "SELECT f.facilityID, f.facilityName, f.type, f.location, f.capacity,
                                br.operatingStart, br.operatingEnd
                         FROM facility f
                         LEFT JOIN booking_rules br ON f.type = br.facilityType
                         WHERE f.status = 'Active' AND f.type != 'Hall'";
            $facParams = [];
            $facTypes = '';
            if (!empty($schedType)) {
                $facQuery .= " AND f.type = ?";
                $facParams[] = $schedType;
                $facTypes .= 's';
            }
            $facQuery .= " ORDER BY f.type, f.facilityName";
            $facStmt = $db->prepare($facQuery);
            if (!empty($facTypes)) {
                $facStmt->bind_param($facTypes, ...$facParams);
            }
            $facStmt->execute();
            $facResult = $facStmt->get_result();
            $facilities = [];
            while ($row = $facResult->fetch_assoc()) {
                $facilities[$row['facilityID']] = $row;
                $facilities[$row['facilityID']]['events'] = [];
            }

            if (empty($facilities)) {
                echo json_encode(['success' => true, 'facilities' => [], 'date' => $schedDate]);
                break;
            }

            $facilityIDs = array_keys($facilities);
            $placeholders = implode(',', array_fill(0, count($facilityIDs), '?'));
            $bindTypes = str_repeat('s', count($facilityIDs));

            // Source 1: Regular class_schedule for this day of week
            $classQuery = "SELECT cs.facilityID, cs.startTime, cs.endTime, cs.courseID, cs.day,
                                  cs.staffID, cs.tutGroup,
                                  COALESCE(st.staffName, cs.staffID) as staffName
                           FROM class_schedule cs
                           LEFT JOIN staff st ON cs.staffID = st.staffID
                           WHERE cs.facilityID IN ($placeholders) AND cs.day = ?";
            $classStmt = $db->prepare($classQuery);
            $classParams = array_merge($facilityIDs, [$dayOfWeek]);
            $classBindTypes = $bindTypes . 's';
            $classStmt->bind_param($classBindTypes, ...$classParams);
            $classStmt->execute();
            $classResult = $classStmt->get_result();
            while ($row = $classResult->fetch_assoc()) {
                $fid = $row['facilityID'];
                if (isset($facilities[$fid])) {
                    $facilities[$fid]['events'][] = [
                        'type' => 'class',
                        'start' => substr($row['startTime'], 0, 5),
                        'end' => substr($row['endTime'], 0, 5),
                        'label' => $row['courseID'],
                        'detail' => $row['staffName'] . ' • ' . $row['tutGroup']
                    ];
                }
            }

            // Source 2: Check if any regular classes are replaced away on this date (freed slots)
            $freedQuery = "SELECT cs.facilityID, cs.startTime, cs.endTime
                           FROM replacement_request rr
                           JOIN class_schedule cs ON rr.scheduleID = cs.scheduleID
                           WHERE cs.facilityID IN ($placeholders)
                             AND rr.originalDate = ? AND rr.status = 'Approved'";
            $freedStmt = $db->prepare($freedQuery);
            $freedParams = array_merge($facilityIDs, [$schedDate]);
            $freedBindTypes = $bindTypes . 's';
            $freedStmt->bind_param($freedBindTypes, ...$freedParams);
            $freedStmt->execute();
            $freedResult = $freedStmt->get_result();
            $freedSlots = [];
            while ($row = $freedResult->fetch_assoc()) {
                $freedSlots[] = [
                    'facilityID' => $row['facilityID'],
                    'start' => substr($row['startTime'], 0, 5),
                    'end' => substr($row['endTime'], 0, 5)
                ];
            }

            // Remove freed class events
            foreach ($freedSlots as $fs) {
                $fid = $fs['facilityID'];
                if (isset($facilities[$fid])) {
                    $facilities[$fid]['events'] = array_values(array_filter(
                        $facilities[$fid]['events'],
                        function($ev) use ($fs) {
                            return !($ev['type'] === 'class' && $ev['start'] === $fs['start'] && $ev['end'] === $fs['end']);
                        }
                    ));
                }
            }

            // Source 3: Approved replacement classes on this date
            $replQuery = "SELECT rr.facilityID, rr.newTime, cs.courseID,
                                 COALESCE(st.staffName, cs.staffID) as staffName, cs.tutGroup
                          FROM replacement_request rr
                          JOIN class_schedule cs ON rr.scheduleID = cs.scheduleID
                          LEFT JOIN staff st ON cs.staffID = st.staffID
                          WHERE rr.facilityID IN ($placeholders)
                            AND rr.newDate = ? AND rr.status = 'Approved'";
            $replStmt = $db->prepare($replQuery);
            $replParams = array_merge($facilityIDs, [$schedDate]);
            $replBindTypes = $bindTypes . 's';
            $replStmt->bind_param($replBindTypes, ...$replParams);
            $replStmt->execute();
            $replResult = $replStmt->get_result();
            while ($row = $replResult->fetch_assoc()) {
                $fid = $row['facilityID'];
                $timeParts = explode(' - ', $row['newTime']);
                if (count($timeParts) === 2 && isset($facilities[$fid])) {
                    $facilities[$fid]['events'][] = [
                        'type' => 'replacement',
                        'start' => trim($timeParts[0]),
                        'end' => trim($timeParts[1]),
                        'label' => $row['courseID'] . ' (R)',
                        'detail' => $row['staffName'] . ' • ' . $row['tutGroup']
                    ];
                }
            }

            // Source 4: Active facility bookings
            $bookQuery = "SELECT fb.facilityID, fb.startTime, fb.endTime, fb.purpose,
                                 fb.userID, fb.userRole,
                                 CASE fb.userRole
                                     WHEN 'Student' THEN (SELECT studentName FROM student WHERE studentID = fb.userID)
                                     WHEN 'Staff' THEN (SELECT staffName FROM staff WHERE staffID = fb.userID)
                                 END as userName
                          FROM facility_booking fb
                          WHERE fb.facilityID IN ($placeholders)
                            AND fb.bookingDate = ? AND fb.status = 'Active'";
            $bookStmt = $db->prepare($bookQuery);
            $bookParams = array_merge($facilityIDs, [$schedDate]);
            $bookBindTypes = $bindTypes . 's';
            $bookStmt->bind_param($bookBindTypes, ...$bookParams);
            $bookStmt->execute();
            $bookResult = $bookStmt->get_result();
            while ($row = $bookResult->fetch_assoc()) {
                $fid = $row['facilityID'];
                if (isset($facilities[$fid])) {
                    $facilities[$fid]['events'][] = [
                        'type' => 'booking',
                        'start' => substr($row['startTime'], 0, 5),
                        'end' => substr($row['endTime'], 0, 5),
                        'label' => $row['purpose'],
                        'detail' => ($row['userName'] ?? $row['userID']) . ' (' . $row['userRole'] . ')'
                    ];
                }
            }

            // Convert to indexed array
            $output = [];
            foreach ($facilities as $fid => $fac) {
                $output[] = $fac;
            }

            echo json_encode(['success' => true, 'facilities' => $output, 'date' => $schedDate]);
            break;

        // ----------------------------------------------------------
        // Admin: Get all facilities with status
        // ----------------------------------------------------------
        case 'getAllFacilities':
            if ($userRole !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                exit;
            }

            $typeFilter = $_GET['type'] ?? '';
            $statusFilter = $_GET['status'] ?? '';

            $query = "SELECT facilityID, facilityName, type, location, capacity, status FROM facility WHERE 1=1";
            $params = [];
            $types = '';

            if (!empty($typeFilter)) {
                $query .= " AND type = ?";
                $params[] = $typeFilter;
                $types .= 's';
            }
            if (!empty($statusFilter)) {
                $query .= " AND status = ?";
                $params[] = $statusFilter;
                $types .= 's';
            }

            $query .= " ORDER BY type, facilityName";

            $stmt = $db->prepare($query);
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            $facilities = [];
            while ($row = $result->fetch_assoc()) {
                $facilities[] = $row;
            }

            // Get type list
            $typeResult = $db->query("SELECT DISTINCT type FROM facility ORDER BY type");
            $facilityTypes = [];
            while ($t = $typeResult->fetch_assoc()) {
                $facilityTypes[] = $t['type'];
            }

            echo json_encode(['success' => true, 'data' => $facilities, 'types' => $facilityTypes]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}

// ============================================================
// POST REQUESTS
// ============================================================
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {

        // ----------------------------------------------------------
        // 1. Create a booking
        // ----------------------------------------------------------
        case 'create':
            $facilityID = $input['facilityID'] ?? '';
            $bookingDate = $input['bookingDate'] ?? '';
            $startTime = $input['startTime'] ?? '';
            $endTime = $input['endTime'] ?? '';
            $purpose = trim($input['purpose'] ?? '');

            // Basic validation
            if (empty($facilityID) || empty($bookingDate) || empty($startTime) || empty($endTime) || empty($purpose)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }

            if ($endTime <= $startTime) {
                echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
                exit;
            }

            if ($bookingDate < date('Y-m-d')) {
                echo json_encode(['success' => false, 'message' => 'Cannot book for past dates']);
                exit;
            }

            // If today, check time not in past
            if ($bookingDate === date('Y-m-d') && $startTime < date('H:i')) {
                echo json_encode(['success' => false, 'message' => 'Cannot book for past time slots']);
                exit;
            }

            // Get facility & rules
            $facStmt = $db->prepare(
                "SELECT f.type, br.maxDurationMinutes, br.maxAdvanceDays, br.maxActiveBookings, br.allowedRoles, br.operatingStart, br.operatingEnd
                 FROM facility f
                 LEFT JOIN booking_rules br ON f.type = br.facilityType
                 WHERE f.facilityID = ? AND f.status = 'Active'"
            );
            $facStmt->bind_param("s", $facilityID);
            $facStmt->execute();
            $facInfo = $facStmt->get_result()->fetch_assoc();

            if (!$facInfo) {
                echo json_encode(['success' => false, 'message' => 'Facility not found or inactive']);
                exit;
            }

            // Check role permission
            $roleLabel = ucfirst($userRole);
            if (!empty($facInfo['allowedRoles']) && strpos($facInfo['allowedRoles'], $roleLabel) === false) {
                echo json_encode(['success' => false, 'message' => 'Your role does not have permission to book this facility type']);
                exit;
            }

            // Check advance booking limit
            $maxAdvance = $facInfo['maxAdvanceDays'] ?? 7;
            $maxDate = date('Y-m-d', strtotime("+{$maxAdvance} days"));
            if ($bookingDate > $maxDate) {
                echo json_encode(['success' => false, 'message' => "Cannot book more than {$maxAdvance} days in advance"]);
                exit;
            }

            // Check duration limit (minimum 1 hour, maximum 2 hours)
            $maxDuration = 120; // Fixed 2 hours maximum
            $minDuration = 60;  // Fixed 1 hour minimum
            $durationMinutes = (strtotime($endTime) - strtotime($startTime)) / 60;
            if ($durationMinutes < $minDuration) {
                echo json_encode(['success' => false, 'message' => "Minimum booking duration is {$minDuration} minutes (1 hour)"]);
                exit;
            }
            if ($durationMinutes > $maxDuration) {
                echo json_encode(['success' => false, 'message' => "Maximum booking duration is {$maxDuration} minutes (2 hours)"]);
                exit;
            }

            // Check operating hours
            $opStart = substr($facInfo['operatingStart'] ?? '08:00:00', 0, 5);
            $opEnd = substr($facInfo['operatingEnd'] ?? '22:00:00', 0, 5);
            if ($startTime < $opStart || $endTime > $opEnd) {
                echo json_encode(['success' => false, 'message' => "Booking must be within operating hours ($opStart - $opEnd)"]);
                exit;
            }

            // Check max active bookings per user
            $maxActive = $facInfo['maxActiveBookings'] ?? 3;
            $activeStmt = $db->prepare(
                "SELECT COUNT(*) as cnt FROM facility_booking 
                 WHERE userID = ? AND status = 'Active' AND bookingDate >= CURDATE()"
            );
            $activeStmt->bind_param("s", $userID);
            $activeStmt->execute();
            $activeCount = $activeStmt->get_result()->fetch_assoc()['cnt'];
            if ($activeCount >= $maxActive) {
                echo json_encode(['success' => false, 'message' => "You already have {$activeCount} active bookings (maximum {$maxActive} allowed)"]);
                exit;
            }

            // CONFLICT CHECK: Time overlap with existing bookings
            $conflictStmt = $db->prepare(
                "SELECT bookingID, startTime, endTime, userID FROM facility_booking 
                 WHERE facilityID = ? AND bookingDate = ? AND status = 'Active'
                 AND startTime < ? AND endTime > ?"
            );
            $conflictStmt->bind_param("ssss", $facilityID, $bookingDate, $endTime, $startTime);
            $conflictStmt->execute();
            if ($conflictStmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Time slot conflict: This facility is already booked during the selected time']);
                exit;
            }

            // CONFLICT CHECK: Regular class schedule
            $dayOfWeek = date('l', strtotime($bookingDate));
            $classConflict = $db->prepare(
                "SELECT courseID, startTime, endTime FROM class_schedule 
                 WHERE facilityID = ? AND day = ? AND startTime < ? AND endTime > ?"
            );
            $classConflict->bind_param("ssss", $facilityID, $dayOfWeek, $endTime, $startTime);
            $classConflict->execute();
            $classConflictRow = $classConflict->get_result()->fetch_assoc();

            // Check if the conflicting class has been replaced away on this date
            if ($classConflictRow) {
                $freedCheck = $db->prepare(
                    "SELECT replacementID FROM replacement_request rr
                     JOIN class_schedule cs ON rr.scheduleID = cs.scheduleID
                     WHERE cs.facilityID = ? AND rr.originalDate = ? AND rr.status = 'Approved'
                     AND cs.startTime = ? AND cs.endTime = ?"
                );
                $freedCheck->bind_param("ssss", $facilityID, $bookingDate, $classConflictRow['startTime'], $classConflictRow['endTime']);
                $freedCheck->execute();
                if ($freedCheck->get_result()->num_rows === 0) {
                    // Class is NOT replaced, so there is a real conflict
                    echo json_encode(['success' => false, 'message' => "Time slot conflict: Class {$classConflictRow['courseID']} is scheduled at this time"]);
                    exit;
                }
            }

            // CONFLICT CHECK: Approved replacement classes
            $replConflict = $db->prepare(
                "SELECT cs.courseID, rr.newTime FROM replacement_request rr
                 JOIN class_schedule cs ON rr.scheduleID = cs.scheduleID
                 WHERE rr.facilityID = ? AND rr.newDate = ? AND rr.status = 'Approved'
                 AND SUBSTRING_INDEX(rr.newTime, ' - ', 1) < ? 
                 AND SUBSTRING_INDEX(rr.newTime, ' - ', -1) > ?"
            );
            $replConflict->bind_param("ssss", $facilityID, $bookingDate, $endTime, $startTime);
            $replConflict->execute();
            $replRow = $replConflict->get_result()->fetch_assoc();
            if ($replRow) {
                echo json_encode(['success' => false, 'message' => "Time slot conflict: Replacement class {$replRow['courseID']} is scheduled at this time"]);
                exit;
            }

            // All checks passed — insert booking
            $roleLabel = ucfirst($userRole);
            $insertStmt = $db->prepare(
                "INSERT INTO facility_booking (facilityID, userID, userRole, bookingDate, startTime, endTime, purpose, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')"
            );
            $insertStmt->bind_param("sssssss", $facilityID, $userID, $roleLabel, $bookingDate, $startTime, $endTime, $purpose);

            if ($insertStmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Booking confirmed!', 'bookingID' => $insertStmt->insert_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create booking. Please try again.']);
            }
            break;

        // ----------------------------------------------------------
        // 2. Cancel a booking
        // ----------------------------------------------------------
        case 'cancel':
            $bookingID = $input['bookingID'] ?? 0;

            if (empty($bookingID)) {
                echo json_encode(['success' => false, 'message' => 'Booking ID required']);
                exit;
            }

            // Verify ownership (unless admin)
            if ($userRole !== 'admin') {
                $ownerCheck = $db->prepare("SELECT userID, bookingDate, startTime FROM facility_booking WHERE bookingID = ? AND status = 'Active'");
                $ownerCheck->bind_param("i", $bookingID);
                $ownerCheck->execute();
                $booking = $ownerCheck->get_result()->fetch_assoc();

                if (!$booking) {
                    echo json_encode(['success' => false, 'message' => 'Booking not found or already cancelled']);
                    exit;
                }

                if ($booking['userID'] !== $userID) {
                    echo json_encode(['success' => false, 'message' => 'You can only cancel your own bookings']);
                    exit;
                }
            }

            $cancelStmt = $db->prepare("UPDATE facility_booking SET status = 'Cancelled' WHERE bookingID = ? AND status = 'Active'");
            $cancelStmt->bind_param("i", $bookingID);

            if ($cancelStmt->execute() && $cancelStmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to cancel booking']);
            }
            break;

        // ----------------------------------------------------------
        // 3. Update facility status (Admin only)
        // ----------------------------------------------------------
        case 'updateFacilityStatus':
            if ($userRole !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                exit;
            }

            $facilityID = $input['facilityID'] ?? '';
            $newStatus = $input['status'] ?? '';

            if (empty($facilityID) || empty($newStatus)) {
                echo json_encode(['success' => false, 'message' => 'Facility ID and status are required']);
                exit;
            }

            $validStatuses = ['Active', 'Closed', 'Maintenance'];
            if (!in_array($newStatus, $validStatuses)) {
                echo json_encode(['success' => false, 'message' => 'Invalid status value']);
                exit;
            }

            $updateStmt = $db->prepare("UPDATE facility SET status = ? WHERE facilityID = ?");
            $updateStmt->bind_param("ss", $newStatus, $facilityID);

            if ($updateStmt->execute() && $updateStmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Facility status updated successfully']);
            } else if ($updateStmt->affected_rows === 0) {
                // Check if facility exists
                $check = $db->prepare("SELECT status FROM facility WHERE facilityID = ?");
                $check->bind_param("s", $facilityID);
                $check->execute();
                $existing = $check->get_result()->fetch_assoc();
                if ($existing && $existing['status'] === $newStatus) {
                    echo json_encode(['success' => true, 'message' => 'Status unchanged']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Facility not found']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update facility status']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$db->close();
?>
