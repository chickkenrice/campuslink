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
    
    $isSubmitted = !empty($data['submissionID']);
    $deadlinePassed = strtotime($data['deadline']) < time();
    $dueDate = date('M d, Y - h:i A', strtotime($data['deadline']));
    
    // START SPLIT LAYOUT
    ?>
    <div class="gc-layout">
        
        <div class="gc-left">
            <div style="margin-bottom: 20px;">
                <h1 style="font-size: 28px; color: #1f2937; margin: 0 0 8px 0;"><?php echo $data['title']; ?></h1>
                <div style="color: #6b7280; font-size: 14px;">
                    <span style="font-weight: 600; color: #8056ff;"><?php echo $data['staffName']; ?></span>
                    &bull; <?php echo $data['courseID']; ?>
                    &bull; <span style="color: #ef4444;">Due <?php echo $dueDate; ?></span>
                </div>
            </div>
            
            <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 20px 0;">
            
            <div style="font-size: 15px; line-height: 1.6; color: #374151; white-space: pre-wrap;"><?php echo $data['description']; ?></div>
            
            <?php if($data['attachmentPath']): ?>
                <div style="margin-top: 30px;">
                    <h4 style="margin-bottom: 10px; color: #374151;">Reference Materials</h4>
                    <a href="../uploads/assignments/<?php echo $data['attachmentPath']; ?>" download style="display: flex; align-items: center; gap: 15px; padding: 15px; border: 1px solid #e5e7eb; border-radius: 12px; text-decoration: none; width: fit-content; transition: 0.2s;" onmouseover="this.style.borderColor='#8056ff'" onmouseout="this.style.borderColor='#e5e7eb'">
                        <div style="font-size: 24px; color: #ef4444;"><i class="fa-solid fa-file-pdf"></i></div>
                        <div>
                            <div style="font-weight: 600; color: #1f2937; font-size: 14px;"><?php echo basename($data['attachmentPath']); ?></div>
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
                    <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                        <i class="fa-solid fa-file-lines" style="color: #8056ff; font-size: 20px;"></i>
                        <div style="overflow: hidden;">
                            <a href="../uploads/submissions/<?php echo $data['subFile']; ?>" download style="display: block; font-weight: 600; font-size: 13px; color: #1f2937; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo $data['subFile']; ?></a>
                            <span style="font-size: 11px; color: #6b7280;">Submitted</span>
                        </div>
                    </div>

                    <?php if($data['grade']): ?>
                        <div style="text-align: center; margin-bottom: 20px;">
                            <div style="font-size: 42px; font-weight: 700; color: #1f2937;"><?php echo $data['grade']; ?><span style="font-size: 16px; color: #9ca3af; font-weight: 500;">/100</span></div>
                            <div style="font-size: 12px; color: #6b7280;">Overall Grade</div>
                        </div>
                    <?php endif; ?>

                    <?php if($data['feedback']): ?>
                        <div style="background: #f9fafb; padding: 15px; border-radius: 8px; font-size: 13px; color: #4b5563;">
                            <strong style="display: block; margin-bottom: 5px; color: #1f2937;"><i class="fa-regular fa-comment"></i> Private Comment:</strong>
                            "<?php echo htmlspecialchars($data['feedback']); ?>"
                        </div>
                    <?php else: ?>
                        <?php if($data['status'] != 'Graded'): ?>
                            <button disabled style="width: 100%; background: #e5e7eb; color: #9ca3af; border: none; padding: 10px; border-radius: 8px; cursor: not-allowed; font-weight: 600;">Unsubmit</button>
                            <p style="text-align: center; font-size: 11px; color: #9ca3af; margin-top: 10px;">To make changes, contact your lecturer.</p>
                        <?php endif; ?>
                    <?php endif; ?>

                <?php else: ?>
                    <?php if($deadlinePassed): ?>
                        <div style="background: #fef2f2; color: #b91c1c; padding: 10px; border-radius: 8px; font-size: 12px; margin-bottom: 15px; font-weight: 600;">
                            <i class="fa-solid fa-triangle-exclamation"></i> Late Submission
                        </div>
                    <?php endif; ?>

                    <form id="submissionForm" onsubmit="submitAssignment(event)">
                        <input type="hidden" name="assignmentID" value="<?php echo $aID; ?>">
                        
                        <div style="border: 2px dashed #d1d5db; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 15px; background: #fdfdff; cursor: pointer;" onclick="document.getElementById('fileInput').click()">
                            <i class="fa-solid fa-cloud-arrow-up" style="font-size: 24px; color: #8056ff; margin-bottom: 8px;"></i>
                            <div style="font-size: 13px; font-weight: 600; color: #4b5563;">Click to add file</div>
                            <input type="file" id="fileInput" name="subFile" style="display: none;" required onchange="document.getElementById('fileNameDisplay').innerText = this.files[0].name">
                            <div id="fileNameDisplay" style="font-size: 11px; color: #6b7280; margin-top: 5px;"></div>
                        </div>

                        <button type="submit" class="btn-submit-work" style="width: 100%; background: #1f2937; color: white; padding: 12px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;">
                            <?php echo $deadlinePassed ? 'Mark as Done (Late)' : 'Turn In'; ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

// 2. HANDLE FILE UPLOAD (Unchanged Logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['subFile'])) {
    $aID = $_POST['assignmentID'];
    $uploadDir = '../uploads/submissions/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $fileName = time() . '_' . $studentID . '_' . basename($_FILES['subFile']['name']);
    $targetPath = $uploadDir . $fileName;
    
    $qCheck = $db->query("SELECT deadline FROM assignments WHERE assignmentID = '$aID'");
    $deadline = $qCheck->fetch_assoc()['deadline'];
    $status = (time() > strtotime($deadline)) ? 'Late' : 'Submitted';
    
    if (move_uploaded_file($_FILES['subFile']['tmp_name'], $targetPath)) {
        $stmt = $db->prepare("INSERT INTO submissions (assignmentID, studentID, filePath, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $aID, $studentID, $fileName, $status);
        if ($stmt->execute()) {
            // Log assignment submission
            $assignStmt = $db->prepare("SELECT a.title, a.courseID, c.courseName FROM assignments a JOIN course c ON a.courseID = c.courseID WHERE a.assignmentID = ?");
            $assignStmt->bind_param("i", $aID);
            $assignStmt->execute();
            $assignInfo = $assignStmt->get_result()->fetch_assoc();
            $assignStmt->close();
            $assignTitle = $assignInfo ? $assignInfo['title'] : 'Unknown';
            $courseLabel = $assignInfo ? $assignInfo['courseName'] . ' (' . $assignInfo['courseID'] . ')' : '';
            $logUserID = $_SESSION['actual_user_id'] ?? $studentID;
            logActivity($db, $logUserID, 'ASSIGNMENT_SUBMIT', "Student submitted assignment: $assignTitle for $courseLabel", [
                'assignmentID' => $aID,
                'title' => $assignTitle,
                'courseID' => $assignInfo['courseID'] ?? '',
                'fileName' => $fileName,
                'submissionStatus' => $status
            ]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'File upload failed']);
    }
}
?>