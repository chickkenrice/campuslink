<?php
/**
 * Auto-Progression System
 * 
 * Automatically advances students to the next semester when the next term's
 * startDate arrives. Also auto-registers core courses and assigns an elective
 * if the student hasn't chosen one.
 * 
 * Called from validate_session() in config.php for student role only.
 */

function checkAutoProgression() {
    // Only run once per request
    static $checked = false;
    if ($checked) return;
    $checked = true;

    if (!isset($_SESSION['user_id'])) return;

    $studentID = $_SESSION['user_id'];
    $db = get_db_connection();

    // Get student's current info
    $stmt = $db->prepare("SELECT programID, currentYear, currentSemester FROM student WHERE studentID = ?");
    if (!$stmt) { $db->close(); return; }
    $stmt->bind_param("s", $studentID);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$student) { $db->close(); return; }

    $programID = $student['programID'];
    $curYear = (int)$student['currentYear'];
    $curSem = (int)$student['currentSemester'];
    $today = date('Y-m-d');

    // Loop to handle multiple missed progressions (e.g., student absent for 2 semesters)
    $maxIterations = 10;

    for ($i = 0; $i < $maxIterations; $i++) {
        // Calculate next semester: Y1S1→Y1S2, Y1S3→Y2S1, etc.
        $nextSem = $curSem + 1;
        $nextYear = $curYear;
        if ($nextSem > 3) {
            $nextSem = 1;
            $nextYear++;
        }

        // Find the next term in academic_term
        $stmt = $db->prepare("
            SELECT termID, year, semester, startDate 
            FROM academic_term 
            WHERE programID = ? AND year = ? AND semester = ?
            ORDER BY startDate ASC LIMIT 1
        ");
        if (!$stmt) break;
        $stmt->bind_param("sii", $programID, $nextYear, $nextSem);
        $stmt->execute();
        $nextTerm = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // No next term defined by admin, or next term hasn't started yet
        if (!$nextTerm || $today < $nextTerm['startDate']) break;

        // === PROMOTE the student ===
        $stmt = $db->prepare("UPDATE student SET currentYear = ?, currentSemester = ? WHERE studentID = ?");
        $stmt->bind_param("iis", $nextYear, $nextSem, $studentID);
        $stmt->execute();
        $stmt->close();

        // Ensure course offerings exist for the new term
        progressionGenerateOfferings($db, $nextTerm['termID'], $programID, $nextYear, $nextSem);

        // Auto-register all core/main courses
        progressionRegisterCore($db, $studentID, $nextTerm['termID'], $programID, $nextYear, $nextSem);

        // Auto-assign elective if student hasn't chosen one
        progressionAssignElective($db, $studentID, $nextTerm['termID'], $programID, $nextYear, $nextSem);

        // Move to next iteration in case another semester was also missed
        $curYear = $nextYear;
        $curSem = $nextSem;
    }

    $db->close();
}

/**
 * Generate course offerings for a term if none exist yet.
 */
function progressionGenerateOfferings($db, $termID, $programID, $year, $semester) {
    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM course_offering WHERE termID = ? AND programID = ?");
    $stmt->bind_param("is", $termID, $programID);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    if ($count > 0) return; // Offerings already exist

    $stmt = $db->prepare("
        SELECT pc.courseID FROM program_course pc
        JOIN course c ON pc.courseID = c.courseID
        WHERE pc.programID = ? AND pc.year = ? AND pc.semester = ?
    ");
    $stmt->bind_param("sii", $programID, $year, $semester);
    $stmt->execute();
    $courses = $stmt->get_result();

    $defaultCapacity = 40;
    while ($course = $courses->fetch_assoc()) {
        $ins = $db->prepare("
            INSERT INTO course_offering (courseID, programID, termID, sectionNo, capacity, status) 
            VALUES (?, ?, ?, 1, ?, 'Open')
        ");
        $ins->bind_param("ssii", $course['courseID'], $programID, $termID, $defaultCapacity);
        $ins->execute();
        $ins->close();
    }
    $stmt->close();
}

/**
 * Auto-register a student for all core/main courses of the new term.
 */
function progressionRegisterCore($db, $studentID, $termID, $programID, $year, $semester) {
    $stmt = $db->prepare("
        SELECT co.offeringID, co.capacity
        FROM course_offering co
        JOIN program_course pc ON co.courseID = pc.courseID AND co.programID = pc.programID
        WHERE co.termID = ? AND co.programID = ?
          AND pc.year = ? AND pc.semester = ?
          AND pc.type = 'Main'
          AND co.status != 'Closed'
    ");
    $stmt->bind_param("isii", $termID, $programID, $year, $semester);
    $stmt->execute();
    $offerings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($offerings as $offering) {
        // Skip if student already has any record for this offering
        $chk = $db->prepare("SELECT registrationID FROM course_registration WHERE studentID = ? AND offeringID = ?");
        $chk->bind_param("si", $studentID, $offering['offeringID']);
        $chk->execute();
        $exists = $chk->get_result()->num_rows > 0;
        $chk->close();
        if ($exists) continue;

        // Check capacity
        $capChk = $db->prepare("SELECT COUNT(*) AS cnt FROM course_registration WHERE offeringID = ? AND status = 'Registered'");
        $capChk->bind_param("i", $offering['offeringID']);
        $capChk->execute();
        $enrolled = $capChk->get_result()->fetch_assoc()['cnt'];
        $capChk->close();
        if ($enrolled >= $offering['capacity']) continue;

        // Register
        $ins = $db->prepare("INSERT INTO course_registration (studentID, offeringID, status) VALUES (?, ?, 'Registered')");
        $ins->bind_param("si", $studentID, $offering['offeringID']);
        $ins->execute();
        $ins->close();

        // Mark offering as Full if at capacity
        if (($enrolled + 1) >= $offering['capacity']) {
            $upd = $db->prepare("UPDATE course_offering SET status = 'Full' WHERE offeringID = ?");
            $upd->bind_param("i", $offering['offeringID']);
            $upd->execute();
            $upd->close();
        }
    }
}

/**
 * Auto-assign 1 elective course if the student hasn't registered for any
 * elective in the new semester. Picks the first available with capacity.
 */
function progressionAssignElective($db, $studentID, $termID, $programID, $year, $semester) {
    // Get all elective offerings for this term
    $stmt = $db->prepare("
        SELECT co.offeringID, co.courseID, co.capacity
        FROM course_offering co
        JOIN program_course pc ON co.courseID = pc.courseID AND co.programID = pc.programID
        WHERE co.termID = ? AND co.programID = ?
          AND pc.year = ? AND pc.semester = ?
          AND pc.type = 'Elective'
          AND co.status != 'Closed'
    ");
    $stmt->bind_param("isii", $termID, $programID, $year, $semester);
    $stmt->execute();
    $electives = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($electives)) return; // No electives in this semester

    // Check if student is already registered for any elective
    foreach ($electives as $elec) {
        $chk = $db->prepare("SELECT registrationID FROM course_registration WHERE studentID = ? AND offeringID = ? AND status = 'Registered'");
        $chk->bind_param("si", $studentID, $elec['offeringID']);
        $chk->execute();
        $hasElective = $chk->get_result()->num_rows > 0;
        $chk->close();
        if ($hasElective) return; // Student already chose an elective
    }

    // Pick the first elective with available capacity
    foreach ($electives as $elec) {
        $capChk = $db->prepare("SELECT COUNT(*) AS cnt FROM course_registration WHERE offeringID = ? AND status = 'Registered'");
        $capChk->bind_param("i", $elec['offeringID']);
        $capChk->execute();
        $enrolled = $capChk->get_result()->fetch_assoc()['cnt'];
        $capChk->close();

        if ($enrolled < $elec['capacity']) {
            $ins = $db->prepare("INSERT INTO course_registration (studentID, offeringID, status) VALUES (?, ?, 'Registered')");
            $ins->bind_param("si", $studentID, $elec['offeringID']);
            $ins->execute();
            $ins->close();

            if (($enrolled + 1) >= $elec['capacity']) {
                $upd = $db->prepare("UPDATE course_offering SET status = 'Full' WHERE offeringID = ?");
                $upd->bind_param("i", $elec['offeringID']);
                $upd->execute();
                $upd->close();
            }
            return; // Only assign 1 elective
        }
    }
}
