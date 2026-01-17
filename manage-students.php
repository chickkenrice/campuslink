<?php
require_once 'config.php';
$db = get_db_connection();

// Logic to Generate 40 Students (20 RSD, 20 RSW)
if (isset($_POST['generate_data'])) {
    $programmes = ['RSD: Software Development', 'RSW: Software Engineering'];
    foreach ($programmes as $prog) {
        for ($i = 1; $i <= 20; $i++) {
            $year = "23";
            $uniqueID = str_pad((string)rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $sid = $year . "WP" . $uniqueID;
            $name = "Student " . ($prog == $programmes[0] ? "RSD" : "RSW") . " " . $i;
            $email = strtolower(str_replace(' ', '', $name)) . "@student.tarc.edu.my";
            
            // Insert User (Foreign Key Requirement)
            $db->query("INSERT IGNORE INTO users (userID, role) VALUES ('$sid', 'Student')");
            // Insert Student using your specific attributes
            $db->query("INSERT IGNORE INTO student (studentID, userID, studentName, email, contactNo, tutGroup, programme) 
                        VALUES ('$sid', '$sid', '$name', '$email', '012-3456789', 'Group A', '$prog')");
        }
    }
    header("Location: manage-students.php?success=1");
    exit;
}

$result = $db->query("SELECT * FROM student ORDER BY programme, studentName");
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CAMPUSLink — Manage Students</title>
    <link rel="stylesheet" href="manage-students.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body>
	<div class="app-bg">
		<div class="main-card">
			<aside class="sidebar">
				<div class="sidebar-head">
					<div class="brand-icon">CL</div>
					<div class="brand-text">
						<span class="brand-name">CAMPUSLink</span>
						<span class="brand-tagline">Staff Console</span>
					</div>
				</div>
				<nav class="sidebar-nav">
					<ul>
						<li>
							<a href="staff-dashboard.php" class="nav-item">
								<span class="nav-icon"><i class="fa-solid fa-table-columns"></i></span>
								<span class="nav-label">Dashboard</span>
							</a>
						</li>
						<li>
							<button class="nav-item is-active" type="button">
								<span class="nav-icon"><i class="fa-solid fa-id-badge"></i></span>
								<span class="nav-label">Student Management</span>
							</button>
						</li>
					</ul>
				</nav>
			</aside>

			<main class="dashboard">
				<header class="dashboard-topbar">
					<h1 class="welcome-title" style="color: var(--text-main); font-size: 26px; margin: 0;">Student Management</h1>
					<div class="topbar-right">
						<form method="POST">
							<button type="submit" name="generate_data" class="btn">Generate 40 Students</button>
						</form>
					</div>
				</header>

				<section class="announcements-card">
					<header class="announcements-header">
						<h2>Student Records (<?php echo $result->num_rows; ?>)</h2>
					</header>
					
					<div class="table-container">
						<table class="student-table">
							<thead>
								<tr>
									<th>Student ID</th>
									<th>Name</th>
									<th>Programme</th>
									<th>Email</th>
									<th>Group</th>
								</tr>
							</thead>
							<tbody>
								<?php while($row = $result->fetch_assoc()): ?>
								<tr>
									<td class="id-cell"><?php echo $row['studentID']; ?></td>
									<td class="name-cell"><?php echo $row['studentName']; ?></td>
									<td><span class="badge-prog"><?php echo explode(':', $row['programme'])[0]; ?></span></td>
									<td class="email-cell"><?php echo $row['email']; ?></td>
									<td class="group-cell"><?php echo $row['tutGroup']; ?></td>
								</tr>
								<?php endwhile; ?>
							</tbody>
						</table>
					</div>
				</section>
			</main>
		</div>
	</div>
</body>
</html>