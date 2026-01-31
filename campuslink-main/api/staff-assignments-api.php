<?php
// File: api/staff-assignments-api.php
require_once '../includes/config.php';
$db = get_db_connection();

if (isset($_GET['assignmentID'])) {
    $aID = $_GET['assignmentID'];
    
    // Fetch Submissions
    $sql = "SELECT s.*, st.studentName 
            FROM submissions s 
            JOIN student st ON s.studentID = st.studentID 
            WHERE s.assignmentID = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $aID);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows == 0) {
        echo '<p style="text-align:center; color:#888; padding:20px;">No students have submitted yet.</p>';
    } else {
        echo '<table class="sub-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Date</th>
                        <th>File</th>
                        <th>Grade</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>';
        
        while ($row = $res->fetch_assoc()) {
            $statusClass = 'st-submitted';
            if ($row['status'] == 'Graded') $statusClass = 'st-graded';
            
            // Note: This link works because it is rendered inside staff/staff-assignments.php
            echo '<tr>
                    <td>
                        <div style="font-weight:600;">'.htmlspecialchars($row['studentName']).'</div>
                        <div style="font-size:11px; color:#888;">'.$row['studentID'].'</div>
                    </td>
                    <td>'.date('M d, H:i', strtotime($row['submittedAt'])).'</td>
                    <td>
                        <a href="../uploads/submissions/'.htmlspecialchars($row['filePath']).'" download style="color:#8056ff; font-weight:600; text-decoration:none;">
                            <i class="fa-solid fa-paperclip"></i> View
                        </a>
                    </td>
                    <td>
                        <span class="status-badge '.$statusClass.'">'.$row['status'].'</span>
                        '.($row['grade'] ? '<span style="font-weight:bold; margin-left:5px;">('.$row['grade'].'/100)</span>' : '').'
                    </td>
                    <td>
                        <form method="POST" style="display:flex; gap:5px;">
                            <input type="hidden" name="submissionID" value="'.$row['submissionID'].'">
                            <input type="number" name="grade" placeholder="0-100" style="width:60px; padding:5px; border:1px solid #ddd; border-radius:6px;" required>
                            <button type="submit" name="submit_grade" style="background:#1f2937; color:white; border:none; padding:5px 10px; border-radius:6px; cursor:pointer;">Save</button>
                        </form>
                    </td>
                  </tr>';
        }
        echo '</tbody></table>';
    }
}
?>