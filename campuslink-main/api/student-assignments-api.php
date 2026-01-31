<?php
// File: api/student-assignments-api.php
require_once '../includes/config.php';
session_start();
$db = get_db_connection();

if (!isset($_SESSION['user_id'])) exit;
$studentID = $_SESSION['user_id'];

// 1. FETCH ASSIGNMENT DETAILS (for Modal)
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
    $isGraded = ($data['status'] === 'Graded');
    $deadlinePassed = strtotime($data['deadline']) < time();
    
    // Render HTML for Modal
    ?>
    <div class="modal-body-content">
        <div class="m-header">
            <span class="m-course"><?php echo $data['courseID']; ?> - <?php echo $data['courseName']; ?></span>
            <span class="m-date">Due: <?php echo date('M d, h:i A', strtotime($data['deadline'])); ?></span>
        </div>
        <h2 class="m-title"><?php echo $data['title']; ?></h2>
        <div class="m-desc">
            <?php echo nl2br(htmlspecialchars($data['description'])); ?>
        </div>
        
        <?php if($data['attachmentPath']): ?>
        <div class="m-attach">
            <i class="fa-solid fa-paperclip"></i>
            <a href="../uploads/assignments/<?php echo $data['attachmentPath']; ?>" download>Download Attachment (<?php echo basename($data['attachmentPath']); ?>)</a>
        </div>
        <?php endif; ?>
        
        <hr style="margin: 20px 0; border:0; border-top:1px solid #eee;">
        
        <?php if($isSubmitted): ?>
            <div class="submission-status-card <?php echo $data['status'] == 'Graded' ? 'ss-graded' : 'ss-submitted'; ?>">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <strong style="display:block; margin-bottom:4px;">Status: <?php echo $data['status']; ?></strong>
                        <span style="font-size:12px;">Submitted on <?php echo date('M d, h:i A', strtotime($data['submittedAt'])); ?></span>
                    </div>
                    <?php if($data['grade']): ?>
                        <div class="grade-circle"><?php echo $data['grade']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top:10px; font-size:13px;">
                    <i class="fa-solid fa-file-arrow-up"></i> 
                    <a href="../uploads/submissions/<?php echo $data['subFile']; ?>" download>My Submission</a>
                </div>

                <?php if($data['feedback']): ?>
                    <div class="feedback-box">
                        <strong><i class="fa-regular fa-comment-dots"></i> Lecturer Feedback:</strong>
                        <p><?php echo htmlspecialchars($data['feedback']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="upload-area">
                <?php if($deadlinePassed): ?>
                    <div class="alert-late"><i class="fa-solid fa-triangle-exclamation"></i> The deadline has passed. You are submitting late.</div>
                <?php endif; ?>
                
                <form id="submissionForm" onsubmit="submitAssignment(event)">
                    <input type="hidden" name="assignmentID" value="<?php echo $aID; ?>">
                    <label style="display:block; font-weight:600; margin-bottom:8px;">Upload Your Work</label>
                    <input type="file" name="subFile" class="form-input" required>
                    <button type="submit" class="btn-submit-work">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Submit Assignment
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// 2. HANDLE FILE UPLOAD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['subFile'])) {
    $aID = $_POST['assignmentID'];
    $uploadDir = '../uploads/submissions/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $fileName = time() . '_' . $studentID . '_' . basename($_FILES['subFile']['name']);
    $targetPath = $uploadDir . $fileName;
    
    // Check Deadline for "Late" status
    $qCheck = $db->query("SELECT deadline FROM assignments WHERE assignmentID = '$aID'");
    $deadline = $qCheck->fetch_assoc()['deadline'];
    $status = (time() > strtotime($deadline)) ? 'Late' : 'Submitted';
    
    if (move_uploaded_file($_FILES['subFile']['tmp_name'], $targetPath)) {
        $stmt = $db->prepare("INSERT INTO submissions (assignmentID, studentID, filePath, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $aID, $studentID, $fileName, $status);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'File upload failed']);
    }
}
?>