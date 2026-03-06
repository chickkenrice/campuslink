<?php
// File: api/staff-assignments-api.php
header('Content-Type: application/json');
require_once '../includes/config.php';
$db = get_db_connection();

// --- HANDLE AJAX GRADE SAVE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_grade_ajax'])) {
    $subID = $_POST['submissionID'];
    $grade = $_POST['grade'];
    $feedback = $_POST['feedback'] ?? ''; 
    $stmt = $db->prepare("UPDATE submissions SET grade = ?, feedback = ?, status = 'Graded' WHERE submissionID = ?");
    $stmt->bind_param("isi", $grade, $feedback, $subID);
    if($stmt->execute()) echo json_encode(['status' => 'success']);
    else echo json_encode(['status' => 'error']);
    exit;
}

// --- FETCH CONTENT ---
if (isset($_GET['assignmentID'])) {
    $aID = $_GET['assignmentID'];
    
    // First, get assignment details to know courseID and tutGroup
    $assignStmt = $db->prepare("SELECT a.*, c.courseName FROM assignments a JOIN course c ON a.courseID = c.courseID WHERE a.assignmentID = ?");
    $assignStmt->bind_param("i", $aID);
    $assignStmt->execute();
    $assignmentData = $assignStmt->get_result()->fetch_assoc();
    
    if (!$assignmentData) {
        echo '<div class="loading-state" style="flex-direction:column; color:#9ca3af;">
                <i class="fa-solid fa-exclamation-triangle" style="font-size:40px; margin-bottom:15px; color:#e5e7eb;"></i>
                <p>Assignment not found.</p>
              </div>';
        exit;
    }
    
    $courseID = $assignmentData['courseID'];
    $tutGroup = $assignmentData['tutGroup'];
    
    // Get all students who should submit this assignment
    // Based on program_course enrollment matching the assignment's course and tutGroup
    $allStudentsSql = "SELECT DISTINCT st.studentID, st.studentName, st.studentImage
                       FROM student st
                       JOIN program_course pc ON st.programID = pc.programID 
                           AND st.currentYear = pc.year 
                           AND st.currentSemester = pc.semester
                       WHERE pc.courseID = ?
                       AND (? = 'All' OR ? = 'Combined' OR st.tutGroup = ?)
                       ORDER BY st.studentName ASC";
    $allStmt = $db->prepare($allStudentsSql);
    $allStmt->bind_param("ssss", $courseID, $tutGroup, $tutGroup, $tutGroup);
    $allStmt->execute();
    $allStudents = $allStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get submissions for this assignment
    $sql = "SELECT s.*, st.studentName, st.studentID as realStudentID, st.studentImage 
            FROM submissions s 
            JOIN student st ON s.studentID = st.studentID 
            WHERE s.assignmentID = ?
            ORDER BY s.submittedAt ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $aID);
    $stmt->execute();
    $submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Fetch all files for all submissions (multi-file support)
    $submissionFilesMap = [];
    if (!empty($submissions)) {
        $subIDs = array_column($submissions, 'submissionID');
        $placeholders = implode(',', array_fill(0, count($subIDs), '?'));
        $types = str_repeat('i', count($subIDs));
        
        $filesStmt = $db->prepare("SELECT submissionID, fileID, filePath, originalName, uploadedAt FROM submission_files WHERE submissionID IN ($placeholders) ORDER BY uploadedAt ASC");
        $filesStmt->bind_param($types, ...$subIDs);
        $filesStmt->execute();
        $allFiles = $filesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($allFiles as $f) {
            $submissionFilesMap[$f['submissionID']][] = $f;
        }
    }
    
    // Create a map of submitted student IDs
    $submittedStudentIDs = [];
    foreach ($submissions as $sub) {
        $submittedStudentIDs[$sub['realStudentID']] = $sub;
    }
    
    // Find students who haven't submitted
    $missingStudents = [];
    foreach ($allStudents as $student) {
        if (!isset($submittedStudentIDs[$student['studentID']])) {
            $missingStudents[] = $student;
        }
    }
    
    $submittedCount = count($submissions);
    $missingCount = count($missingStudents);
    $totalCount = count($allStudents);

    $sidebar = '';
    $panels = '';
    
    // === SUBMITTED STUDENTS SECTION ===
    if (!empty($submissions)) {
        $sidebar .= '<div style="padding:12px 20px; font-size:11px; font-weight:700; color:#059669; text-transform:uppercase; background:#ecfdf5; border-bottom:1px solid #d1fae5;">
            <i class="fa-solid fa-check-circle" style="margin-right:6px;"></i>Submitted ('.$submittedCount.')
        </div>';
        
        foreach ($submissions as $row) {
            $subID = $row['submissionID'];
            $name = htmlspecialchars($row['studentName']);
            $id = htmlspecialchars($row['realStudentID']);
            $date = date('d M, g:i A', strtotime($row['submittedAt']));
            $legacyFile = htmlspecialchars($row['filePath']);
            $grade = $row['grade'];
            $feedback = htmlspecialchars($row['feedback'] ?? '');
            $status = $row['status'];
            $studentImage = $row['studentImage'] ?? '';
            
            // Get files for this submission (from new multi-file table or legacy)
            $submissionFiles = isset($submissionFilesMap[$subID]) ? $submissionFilesMap[$subID] : [];
            // Backward compatibility: if no files in submission_files but filePath exists
            if (empty($submissionFiles) && !empty($row['filePath'])) {
                $submissionFiles[] = [
                    'filePath' => $row['filePath'],
                    'originalName' => $row['filePath'],
                    'uploadedAt' => $row['submittedAt']
                ];
            }
            $fileCount = count($submissionFiles);
            
            // Colors
            $stColor = ($status == 'Late') ? '#ef4444' : (($status == 'Graded') ? '#10b981' : '#6b7280');
            $gradePill = $grade ? $grade : '-';
            $gradeBg = $grade ? '#d1fae5' : '#f3f4f6';
            $gradeTxt = $grade ? '#065f46' : '#9ca3af';
            
            // Avatar: photo or fallback to user icon
            $hasPhoto = !empty($studentImage) && trim($studentImage) !== '';
            $avatarContent = $hasPhoto 
                ? '<img src="../uploads/profiles/'.htmlspecialchars($studentImage).'" style="width:38px; height:38px; border-radius:50%; object-fit:cover;" alt="'.substr($name,0,1).'" onerror="this.outerHTML=\'<div class=gc-avatar-icon><i class=fa-solid fa-user></i></div>\'">' 
                : '<div class="gc-avatar-icon"><i class="fa-solid fa-user"></i></div>';

            // SIDEBAR ITEM
            $sidebar .= '
            <div class="gc-student-item" onclick="loadStudentDetails(this, '.$subID.')">
                '.$avatarContent.'
                <div style="flex-grow:1;">
                    <div class="gc-name">'.$name.'</div>
                    <div class="gc-status-text" style="color:'.$stColor.'; font-size:11px;">'.$status.'</div>
                </div>
                <div class="gc-grade-pill" style="background:'.$gradeBg.'; color:'.$gradeTxt.';">'.$gradePill.'</div>
            </div>';

            // DETAIL PANEL
            // Build files list HTML
            $filesHtml = '';
            foreach ($submissionFiles as $sf) {
                $sfPath = htmlspecialchars($sf['filePath']);
                $sfName = htmlspecialchars($sf['originalName']);
                $sfDate = date('d M, g:i A', strtotime($sf['uploadedAt']));
                $filesHtml .= '
                    <a href="../uploads/submissions/'.$sfPath.'" download class="gc-file-download" style="margin-bottom:8px;">
                        <div class="gc-file-icon"><i class="fa-solid fa-file-arrow-down"></i></div>
                        <div style="flex:1; overflow:hidden;">
                            <div style="font-weight:600; color:#1f2937; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">'.$sfName.'</div>
                            <div style="font-size:11px; color:#9ca3af;">'.$sfDate.'</div>
                        </div>
                    </a>';
            }
            
            $panels .= '
            <div id="detail-'.$subID.'" class="gc-detail-panel" style="display:none;">
                <div class="gc-panel-top">
                    <div style="font-size:20px; font-weight:700; color:#1f2937;">'.$name.'</div>
                    <div style="color:#6b7280; font-size:13px;">ID: '.$id.' &bull; Submitted: '.$date.'</div>
                </div>
                
                <div class="gc-work-area">
                    <div class="gc-section-label">Submitted Work <span style="font-weight:400; color:#9ca3af;">('.$fileCount.' file'.($fileCount > 1 ? 's' : '').')</span></div>
                    <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:20px;">
                        '.$filesHtml.'
                    </div>

                    <div style="border-top:1px solid #e5e7eb; margin:25px 0;"></div>

                    <div class="gc-section-label">Grading & Feedback</div>
                    <form class="gc-grading-form">
                        <input type="hidden" name="submissionID" value="'.$subID.'">
                        <div style="display:flex; gap:20px; margin-bottom:15px;">
                            <div style="flex-grow:0;">
                                <label style="display:block; font-size:12px; font-weight:600; margin-bottom:5px; color:#374151;">Grade / 100</label>
                                <input type="number" name="grade" value="'.$grade.'" min="0" max="100" required
                                    style="font-size:24px; font-weight:700; color:#8056ff; width:100px; padding:10px; border:1px solid #e5e7eb; border-radius:10px; text-align:center;">
                            </div>
                        </div>
                        <div style="margin-bottom:15px;">
                            <label style="display:block; font-size:12px; font-weight:600; margin-bottom:5px; color:#374151;">Comments</label>
                            <textarea name="feedback" rows="5" placeholder="Add private feedback..."
                                style="width:100%; padding:12px; border:1px solid #e5e7eb; border-radius:10px; resize:none; font-family:inherit; font-size:13px;">'.$feedback.'</textarea>
                        </div>
                        <div style="text-align:right;">
                            <button type="submit" class="btn" style="background:#1f2937; color:white; padding:10px 20px; border-radius:8px; border:none; font-weight:600; cursor:pointer;">Save Grade</button>
                        </div>
                    </form>
                </div>
            </div>';
        }
    }
    
    // === MISSING STUDENTS SECTION ===
    if (!empty($missingStudents)) {
        $sidebar .= '<div style="padding:12px 20px; font-size:11px; font-weight:700; color:#dc2626; text-transform:uppercase; background:#fef2f2; border-bottom:1px solid #fecaca; border-top:1px solid #e5e7eb;">
            <i class="fa-solid fa-exclamation-circle" style="margin-right:6px;"></i>Missing ('.$missingCount.')
        </div>';
        
        foreach ($missingStudents as $student) {
            $name = htmlspecialchars($student['studentName']);
            $id = htmlspecialchars($student['studentID']);
            $studentImage = $student['studentImage'] ?? '';
            
            // Avatar: photo or fallback to user icon
            $hasPhoto = !empty($studentImage) && trim($studentImage) !== '';
            $avatarContent = $hasPhoto 
                ? '<img src="../uploads/profiles/'.htmlspecialchars($studentImage).'" style="width:38px; height:38px; border-radius:50%; object-fit:cover; opacity:0.7;" alt="'.substr($name,0,1).'" onerror="this.outerHTML=\'<div class=gc-avatar-icon style=opacity:0.7><i class=fa-solid fa-user></i></div>\'">' 
                : '<div class="gc-avatar-icon" style="opacity:0.7;"><i class="fa-solid fa-user"></i></div>';

            // SIDEBAR ITEM (non-clickable, just display)
            $sidebar .= '
            <div class="gc-student-item gc-missing-item" onclick="showMissingInfo(\''.$name.'\', \''.$id.'\')">
                '.$avatarContent.'
                <div style="flex-grow:1;">
                    <div class="gc-name" style="color:#6b7280;">'.$name.'</div>
                    <div style="font-size:11px; color:#dc2626;"><i class="fa-solid fa-clock" style="margin-right:4px;"></i>Not submitted</div>
                </div>
                <div class="gc-grade-pill" style="background:#fee2e2; color:#dc2626;">-</div>
            </div>';
        }
    }
    
    // Handle empty state when no students at all
    if (empty($allStudents)) {
        echo '<div class="loading-state" style="flex-direction:column; color:#9ca3af;">
                <i class="fa-solid fa-users-slash" style="font-size:40px; margin-bottom:15px; color:#e5e7eb;"></i>
                <p>No students enrolled for this assignment.</p>
              </div>';
        exit;
    }

    echo '
    <style>
        .gc-container { display:flex; width:100%; height:100%; }
        
        /* Sidebar Styles */
        .gc-sidebar { width:320px; background:white; border-right:1px solid #e5e7eb; overflow-y:auto; display:flex; flex-direction:column; }
        .gc-student-item { display:flex; align-items:center; gap:12px; padding:15px 20px; border-bottom:1px solid #f9fafb; cursor:pointer; transition:0.2s; }
        .gc-student-item:hover { background:#f9fafb; }
        .gc-student-item.active { background:#f3efff; border-left:4px solid #8056ff; }
        .gc-student-item.gc-missing-item:hover { background:#fef2f2; }
        .gc-avatar { width:38px; height:38px; background:#e0d4fc; color:#8056ff; border-radius:50%; display:grid; place-items:center; font-weight:700; font-size:16px; }
        .gc-avatar-icon { width:38px; height:38px; background:#f3f4f6; color:#9ca3af; border-radius:50%; display:grid; place-items:center; font-size:14px; flex-shrink:0; }
        .gc-name { font-weight:600; color:#1f2937; font-size:14px; }
        .gc-grade-pill { font-size:12px; font-weight:700; padding:2px 8px; border-radius:12px; background:#f3f4f6; color:#9ca3af; }

        /* Detail Panel Styles */
        .gc-main { flex-grow:1; background:#f9fafb; overflow-y:auto; padding:40px; display:flex; justify-content:center; }
        .gc-detail-panel { width:100%; max-width:800px; display:flex; flex-direction:column; gap:25px; }
        .gc-panel-top { background:white; padding:20px; border-radius:16px; border:1px solid #e5e7eb; box-shadow:0 2px 5px rgba(0,0,0,0.02); }
        .gc-work-area { background:white; padding:30px; border-radius:16px; border:1px solid #e5e7eb; box-shadow:0 2px 5px rgba(0,0,0,0.02); }
        
        .gc-section-label { font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:700; color:#9ca3af; margin-bottom:10px; }
        .gc-file-download { display:flex; align-items:center; gap:15px; padding:15px; border:1px solid #e5e7eb; border-radius:12px; text-decoration:none; transition:0.2s; }
        .gc-file-download:hover { border-color:#8056ff; background:#fbfaff; }
        .gc-file-icon { font-size:24px; color:#8056ff; }

        /* REFINED EMPTY STATE */
        #empty-state {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px dashed #e5e7eb;
            border-radius: 16px;
            color: #9ca3af;
            background: #fdfdff;
        }
        
        /* Missing student info panel */
        .gc-missing-panel {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 16px;
            border: 1px solid #fecaca;
            padding: 30px;
            text-align: center;
        }
    </style>

    <div class="gc-container">
        <div class="gc-sidebar">
            <div style="padding:15px 20px; font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase; border-bottom:1px solid #e5e7eb; background:#fafafa;">
                <i class="fa-solid fa-users" style="margin-right:6px;"></i>All Students ('.$totalCount.')
            </div>
            '.$sidebar.'
        </div>
        <div class="gc-main">
            '.$panels.'
            <div id="empty-state">
                <i class="fa-solid fa-arrow-left" style="font-size:24px; margin-bottom:15px; color:#d1d5db;"></i>
                <p style="font-weight:600; margin:0;">Select a student from the list</p>
                <p style="font-size:13px; margin:5px 0 0;">View submission and grade work</p>
            </div>
            <div id="missing-info-panel" class="gc-missing-panel" style="display:none;">
                <div style="width:60px; height:60px; background:#fee2e2; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
                    <i class="fa-solid fa-file-circle-xmark" style="font-size:24px; color:#dc2626;"></i>
                </div>
                <h3 id="missing-name" style="margin:0 0 5px; font-size:18px; color:#1f2937;"></h3>
                <p id="missing-id" style="margin:0 0 15px; font-size:13px; color:#6b7280;"></p>
                <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:10px; padding:15px; color:#991b1b; font-size:13px;">
                    <i class="fa-solid fa-exclamation-triangle" style="margin-right:6px;"></i>
                    This student has not submitted their assignment yet.
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function showMissingInfo(name, id) {
        // Hide all detail panels and empty state
        document.querySelectorAll(".gc-detail-panel").forEach(p => p.style.display = "none");
        document.getElementById("empty-state").style.display = "none";
        
        // Show missing info panel
        document.getElementById("missing-info-panel").style.display = "block";
        document.getElementById("missing-name").textContent = name;
        document.getElementById("missing-id").textContent = "ID: " + id;
        
        // Update active state
        document.querySelectorAll(".gc-student-item").forEach(i => i.classList.remove("active"));
        event.currentTarget.classList.add("active");
    }
    </script>';
}
?>