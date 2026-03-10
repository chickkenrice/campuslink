<?php
// File: api/student-assignments-api.php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/activity-logger.php';
session_start();
$db = get_db_connection();

if (!isset($_SESSION['user_id'])) exit;
$studentID = $_SESSION['user_id'];

// 1. FETCH ASSIGNMENT DETAILS & RENDER SPLIT VIEW
if (isset($_GET['assignmentID'])) {
    $aID = $_GET['assignmentID'];
    
    // Get Assignment Info + Student's Submission (if any)
    $sql = "SELECT a.*, c.courseName, s.staffName,
            sub.submissionID, sub.filePath as subFile, sub.status, sub.grade, sub.feedback, sub.submittedAt
            FROM assignments a
            JOIN course c ON a.courseID = c.courseID
            JOIN staff s ON a.staffID = s.staffID
            LEFT JOIN submissions sub ON a.assignmentID = sub.assignmentID AND sub.studentID = ?
            WHERE a.assignmentID = ?";
            
    $stmt = $db->prepare($sql);
    $stmt->bind_param("si", $studentID, $aID);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    
    if (!$data) { echo "Assignment not found."; exit; }
    
    // Authorization: verify the student belongs to this assignment's program/year/semester/group
    $authStmt = $db->prepare(
        "SELECT 1 FROM assignments a
         JOIN program_course pc ON a.courseID = pc.courseID
         JOIN student s ON pc.programID = s.programID
                       AND pc.year = s.currentYear
                       AND pc.semester = s.currentSemester
         WHERE a.assignmentID = ?
           AND s.studentID = ?
           AND (a.tutGroup = s.tutGroup OR a.tutGroup = 'All' OR a.tutGroup = 'Combined')
         LIMIT 1"
    );
    $authStmt->bind_param("is", $aID, $studentID);
    $authStmt->execute();
    $authStmt->store_result();
    if ($authStmt->num_rows === 0) {
        echo '<div style="padding:40px;color:#dc2626;"><i class="fa-solid fa-lock"></i> You do not have access to this assignment.</div>';
        exit;
    }
    
    $isSubmitted = !empty($data['submissionID']);
    $deadlinePassed = strtotime($data['deadline']) < time();
    $dueDate = date('d-m-Y h:i A', strtotime($data['deadline']));
    
    // Fetch all submitted files (multi-file support)
    $submissionFiles = [];
    if ($isSubmitted) {
        // First check submission_files table (new multi-file)
        $filesStmt = $db->prepare("SELECT fileID, filePath, originalName, uploadedAt FROM submission_files WHERE submissionID = ? ORDER BY uploadedAt ASC");
        $filesStmt->bind_param("i", $data['submissionID']);
        $filesStmt->execute();
        $submissionFiles = $filesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Backward compatibility: if no files in submission_files but filePath exists in submissions
        if (empty($submissionFiles) && !empty($data['subFile'])) {
            $submissionFiles[] = [
                'fileID' => 0, // Legacy file, no fileID
                'filePath' => $data['subFile'],
                'originalName' => $data['subFile'],
                'uploadedAt' => $data['submittedAt'],
                'isLegacy' => true
            ];
        }
    }
    $fileCount = count($submissionFiles);
    
    // START SPLIT LAYOUT
    ?>
    <div class="gc-layout">
        
        <div class="gc-left">
            <div style="margin-bottom: 20px;">
                <h1 style="font-size: 28px; color: #1f2937; margin: 0 0 8px 0;"><?php echo htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <div style="color: #6b7280; font-size: 14px;">
                    <span style="font-weight: 600; color: #8056ff;"><?php echo htmlspecialchars($data['staffName'], ENT_QUOTES, 'UTF-8'); ?></span>
                    &bull; <?php echo htmlspecialchars($data['courseID'], ENT_QUOTES, 'UTF-8'); ?>
                    &bull; <span style="color: #ef4444;">Due <?php echo htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
            
            <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 20px 0;">
            
            <div style="font-size: 15px; line-height: 1.6; color: #374151; white-space: pre-wrap;"><?php echo htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8'); ?></div>
            
            <?php if($data['attachmentPath']): ?>
                <div style="margin-top: 30px;">
                    <h4 style="margin-bottom: 10px; color: #374151;">Reference Materials</h4>
                    <a href="../uploads/assignments/<?php echo htmlspecialchars($data['attachmentPath'], ENT_QUOTES, 'UTF-8'); ?>" download style="display: flex; align-items: center; gap: 15px; padding: 15px; border: 1px solid #e5e7eb; border-radius: 12px; text-decoration: none; width: fit-content; transition: 0.2s;" onmouseover="this.style.borderColor='#8056ff'" onmouseout="this.style.borderColor='#e5e7eb'">
                        <div style="font-size: 24px; color: #ef4444;"><i class="fa-solid fa-file-pdf"></i></div>
                        <div>
                            <div style="font-weight: 600; color: #1f2937; font-size: 14px;"><?php echo htmlspecialchars(basename($data['attachmentPath']), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div style="font-size: 12px; color: #6b7280;">Click to download</div>
                        </div>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="gc-right">
            <div class="gc-work-card">
                <div class="gc-card-title">
                    Your Work
                    <?php if($isSubmitted): ?>
                        <?php if($data['status'] == 'Graded'): ?>
                            <span style="font-size: 12px; background: #d1fae5; color: #065f46; padding: 4px 10px; border-radius: 20px;">Graded</span>
                        <?php else: ?>
                            <span style="font-size: 12px; background: #e5e7eb; color: #374151; padding: 4px 10px; border-radius: 20px;">Turned in</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="font-size: 12px; background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 20px;">Missing</span>
                    <?php endif; ?>
                </div>

                <?php if($isSubmitted): ?>
                    <!-- Submitted Files List (Locked) -->
                    <div id="submittedFilesList" style="margin-bottom: 20px;">
                        <?php foreach($submissionFiles as $file): ?>
                        <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; display: flex; align-items: center; gap: 10px; margin-bottom: 8px; background: #fafafa;">
                            <i class="fa-solid fa-file-lines" style="color: #8056ff; font-size: 18px;"></i>
                            <div style="overflow: hidden; flex:1;">
                                <a href="../uploads/submissions/<?php echo htmlspecialchars($file['filePath'], ENT_QUOTES, 'UTF-8'); ?>" download style="display: block; font-weight: 600; font-size: 13px; color: #1f2937; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($file['originalName'], ENT_QUOTES, 'UTF-8'); ?></a>
                                <span style="font-size: 10px; color: #9ca3af;"><?php echo date('d-m-Y g:i A', strtotime($file['uploadedAt'])); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="font-size: 11px; color: #6b7280; margin-bottom: 15px; text-align: center;">
                        <i class="fa-solid fa-check-circle" style="color: #10b981;"></i> <?php echo $fileCount; ?> file(s) turned in
                    </div>

                    <?php if($data['grade']): ?>
                        <div style="text-align: center; margin-bottom: 20px;">
                            <div style="font-size: 42px; font-weight: 700; color: #1f2937;"><?php echo $data['grade']; ?><span style="font-size: 16px; color: #9ca3af; font-weight: 500;">/100</span></div>
                            <div style="font-size: 12px; color: #6b7280;">Overall Grade</div>
                        </div>
                    <?php endif; ?>

                    <?php if($data['feedback']): ?>
                        <div style="background: #f9fafb; padding: 15px; border-radius: 8px; font-size: 13px; color: #4b5563; margin-bottom: 20px;">
                            <strong style="display: block; margin-bottom: 5px; color: #1f2937;"><i class="fa-regular fa-comment"></i> Feedback:</strong>
                            "<?php echo htmlspecialchars($data['feedback']); ?>"
                        </div>
                    <?php endif; ?>

                    <?php 
                    // Check if can unsubmit: not graded AND deadline not passed
                    $canUnsubmit = ($data['status'] != 'Graded' && !$deadlinePassed);
                    ?>
                    
                    <?php if($canUnsubmit): ?>
                        <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; margin-top: 10px;">
                            <div style="background: #f0fdf4; color: #166534; padding: 10px; border-radius: 8px; font-size: 12px; margin-bottom: 15px;">
                                <i class="fa-solid fa-check-circle"></i> Your work has been turned in. Unsubmit to make changes.
                            </div>
                            <button type="button" onclick="unsubmitAssignment(<?php echo $data['submissionID']; ?>, <?php echo $aID; ?>)" style="width: 100%; background: white; border: 2px solid #e5e7eb; color: #374151; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;" onmouseover="this.style.borderColor='#8056ff'" onmouseout="this.style.borderColor='#e5e7eb'">
                                <i class="fa-solid fa-rotate-left"></i> Unsubmit
                            </button>
                        </div>
                    <?php elseif($data['status'] == 'Graded'): ?>
                        <p style="text-align: center; font-size: 12px; color: #6b7280; margin-top: 15px;"><i class="fa-solid fa-lock"></i> This submission has been graded and cannot be changed.</p>
                    <?php elseif($deadlinePassed): ?>
                        <p style="text-align: center; font-size: 12px; color: #6b7280; margin-top: 15px;"><i class="fa-solid fa-clock"></i> Deadline has passed. Contact your lecturer for any changes.</p>
                    <?php endif; ?>

                <?php else: ?>
                    <?php if($deadlinePassed): ?>
                        <div style="background: #fef2f2; color: #b91c1c; padding: 10px; border-radius: 8px; font-size: 12px; margin-bottom: 15px; font-weight: 600;">
                            <i class="fa-solid fa-triangle-exclamation"></i> Late Submission
                        </div>
                    <?php endif; ?>

                    <form id="submissionForm" onsubmit="submitAssignment(event)">
                        <input type="hidden" name="assignmentID" value="<?php echo $aID; ?>">
                        
                        <div style="background: #eff6ff; color: #1e40af; padding: 10px; border-radius: 8px; font-size: 12px; margin-bottom: 15px;">
                            <i class="fa-solid fa-info-circle"></i> Add all your files, then click Turn In.
                        </div>
                        
                        <div id="newFilesDropzone" style="border: 2px dashed #d1d5db; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 15px; background: #fdfdff; cursor: pointer;" onclick="document.getElementById('fileInput').click()">
                            <i class="fa-solid fa-cloud-arrow-up" style="font-size: 24px; color: #8056ff; margin-bottom: 8px;"></i>
                            <div style="font-size: 13px; font-weight: 600; color: #4b5563;">Click to add files</div>
                            <div style="font-size: 11px; color: #9ca3af; margin-top: 4px;">You can select multiple files</div>
                            <input type="file" id="fileInput" name="subFiles[]" style="display: none;" multiple onchange="addFilesToStaged(this)">
                        </div>
                        <div id="stagedFilesPreview" style="margin-bottom: 15px;"></div>

                        <button type="submit" id="turnInBtn" class="btn-submit-work" style="width: 100%; background: #9ca3af; color: white; padding: 12px; border: none; border-radius: 8px; font-weight: 600; cursor: not-allowed; transition: 0.2s;" disabled>
                            <?php echo $deadlinePassed ? 'Mark as Done (Late)' : 'Turn In'; ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

// 2. HANDLE FILE OPERATIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $uploadDir = '../uploads/submissions/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    // File validation settings
    $allowedExts  = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'txt', 'jpg', 'jpeg', 'png'];
    $allowedMimes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip', 'application/x-zip-compressed',
        'text/plain',
        'image/jpeg', 'image/png'
    ];
    $maxFileSize = 10 * 1024 * 1024; // 10 MB per file
    
    // === UNSUBMIT ACTION (Google Classroom style) ===
    if (isset($_POST['action']) && $_POST['action'] == 'unsubmit') {
        $submissionID = intval($_POST['submissionID']);
        $aID = intval($_POST['assignmentID']);
        
        // Verify ownership and status
        $verifyStmt = $db->prepare("SELECT s.submissionID, s.status, a.title, a.courseID, c.courseName 
            FROM submissions s 
            JOIN assignments a ON s.assignmentID = a.assignmentID
            JOIN course c ON a.courseID = c.courseID
            WHERE s.submissionID = ? AND s.studentID = ? AND s.assignmentID = ?");
        $verifyStmt->bind_param("isi", $submissionID, $studentID, $aID);
        $verifyStmt->execute();
        $subData = $verifyStmt->get_result()->fetch_assoc();
        
        if (!$subData) {
            echo json_encode(['status' => 'error', 'message' => 'Submission not found or access denied.']);
            exit;
        }
        
        if ($subData['status'] == 'Graded') {
            echo json_encode(['status' => 'error', 'message' => 'Cannot unsubmit a graded submission.']);
            exit;
        }
        
        // Get all files to delete from filesystem
        $filesStmt = $db->prepare("SELECT filePath FROM submission_files WHERE submissionID = ?");
        $filesStmt->bind_param("i", $submissionID);
        $filesStmt->execute();
        $files = $filesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Also get the main filePath from submissions table (backward compat)
        $mainFileStmt = $db->prepare("SELECT filePath FROM submissions WHERE submissionID = ?");
        $mainFileStmt->bind_param("i", $submissionID);
        $mainFileStmt->execute();
        $mainFile = $mainFileStmt->get_result()->fetch_assoc();
        
        // Delete submission files from submission_files table (CASCADE will handle this, but explicit is safer)
        $db->query("DELETE FROM submission_files WHERE submissionID = $submissionID");
        
        // Delete submission record
        $delStmt = $db->prepare("DELETE FROM submissions WHERE submissionID = ? AND studentID = ?");
        $delStmt->bind_param("is", $submissionID, $studentID);
        
        if ($delStmt->execute()) {
            // Delete files from filesystem
            foreach ($files as $file) {
                $filePath = $uploadDir . $file['filePath'];
                if (file_exists($filePath)) @unlink($filePath);
            }
            // Also delete main file if exists
            if ($mainFile && $mainFile['filePath']) {
                $mainFilePath = $uploadDir . $mainFile['filePath'];
                if (file_exists($mainFilePath)) @unlink($mainFilePath);
            }
            
            // Log activity
            $logUserID = $_SESSION['actual_user_id'] ?? $studentID;
            $assignTitle = $subData['title'];
            $courseLabel = $subData['courseName'] . ' (' . $subData['courseID'] . ')';
            logActivity($db, $logUserID, 'ASSIGNMENT_UNSUBMIT', "Student unsubmitted assignment: $assignTitle for $courseLabel", [
                'assignmentID' => $aID,
                'title' => $assignTitle,
                'courseID' => $subData['courseID']
            ]);
            
            echo json_encode(['status' => 'success', 'message' => 'Submission removed. You can now resubmit.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
        exit;
    }
    
    // === NEW SUBMISSION (Multiple files) ===
    if (isset($_FILES['subFiles'])) {
        $aID = intval($_POST['assignmentID']);
        
        // Check deadline for late status
        $qCheck = $db->prepare("SELECT deadline FROM assignments WHERE assignmentID = ?");
        $qCheck->bind_param("i", $aID);
        $qCheck->execute();
        $deadline = $qCheck->get_result()->fetch_assoc()['deadline'];
        $status = (time() > strtotime($deadline)) ? 'Late' : 'Submitted';
        
        // Create submission record first
        $firstFileName = null;
        $uploadedFiles = [];
        $errors = [];
        
        foreach ($_FILES['subFiles']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['subFiles']['error'][$key] !== UPLOAD_ERR_OK) continue;
            
            $originalName = $_FILES['subFiles']['name'][$key];
            $fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $fileMime = mime_content_type($tmpName);
            $fileSize = $_FILES['subFiles']['size'][$key];
            
            if (!in_array($fileExt, $allowedExts) || !in_array($fileMime, $allowedMimes)) {
                $errors[] = "Invalid type: $originalName";
                continue;
            }
            if ($fileSize > $maxFileSize) {
                $errors[] = "Too large: $originalName";
                continue;
            }
            
            $fileName = time() . '_' . $studentID . '_' . basename($originalName);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($tmpName, $targetPath)) {
                $uploadedFiles[] = ['fileName' => $fileName, 'originalName' => $originalName];
                if (!$firstFileName) $firstFileName = $fileName;
            }
        }
        
        if (empty($uploadedFiles)) {
            echo json_encode(['status' => 'error', 'message' => 'No valid files uploaded. ' . implode(', ', $errors)]);
            exit;
        }
        
        // Insert submission record (with first file for backward compatibility)
        $stmt = $db->prepare("INSERT INTO submissions (assignmentID, studentID, filePath, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $aID, $studentID, $firstFileName, $status);
        
        if ($stmt->execute()) {
            $submissionID = $db->insert_id;
            
            // Insert all files into submission_files
            foreach ($uploadedFiles as $file) {
                $insStmt = $db->prepare("INSERT INTO submission_files (submissionID, filePath, originalName) VALUES (?, ?, ?)");
                $insStmt->bind_param("iss", $submissionID, $file['fileName'], $file['originalName']);
                $insStmt->execute();
            }
            
            // Log activity
            $assignStmt = $db->prepare("SELECT a.title, a.courseID, c.courseName FROM assignments a JOIN course c ON a.courseID = c.courseID WHERE a.assignmentID = ?");
            $assignStmt->bind_param("i", $aID);
            $assignStmt->execute();
            $assignInfo = $assignStmt->get_result()->fetch_assoc();
            $assignStmt->close();
            $assignTitle = $assignInfo ? $assignInfo['title'] : 'Unknown';
            $courseLabel = $assignInfo ? $assignInfo['courseName'] . ' (' . $assignInfo['courseID'] . ')' : '';
            $logUserID = $_SESSION['actual_user_id'] ?? $studentID;
            logActivity($db, $logUserID, 'ASSIGNMENT_SUBMIT', "Student submitted assignment: $assignTitle for $courseLabel (" . count($uploadedFiles) . " files)", [
                'assignmentID' => $aID,
                'title' => $assignTitle,
                'courseID' => $assignInfo['courseID'] ?? '',
                'fileCount' => count($uploadedFiles),
                'submissionStatus' => $status
            ]);
            
            $msg = count($uploadedFiles) . ' file(s) submitted successfully!';
            if (!empty($errors)) $msg .= ' ' . count($errors) . ' file(s) failed.';
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } else {
            // Clean up uploaded files on failure
            foreach ($uploadedFiles as $file) {
                @unlink($uploadDir . $file['fileName']);
            }
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
        exit;
    }
}
?>