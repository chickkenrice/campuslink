<?php
session_start();

// 1. Security check: If not logged in, send them back to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Get the name from the session (saved during login)
$fullName = $_SESSION['user_name'] ?? 'Student';

// 3. Get just the first name for the welcome message
$firstName = explode(' ', trim($fullName))[0];
?>

<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>CAMPUSLink — Dashboard</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/styles.css">
	
	
</head>
<body>
	<div class="app-bg">
		<div class="main-card">
			<aside class="sidebar" aria-label="Primary navigation">
				<div class="sidebar-head">
					<div class="brand-icon">CL</div>
					<div class="brand-text">
						<span class="brand-name">CAMPUSLink</span>
						<span class="brand-tagline">Student Portal</span>
					</div>
				</div>
				<nav class="sidebar-nav">
					<ul>
						<li>
							<button class="nav-item is-active" type="button">
								<span class="nav-icon icon-dashboard" aria-hidden="true"><i class="fa-solid fa-table-columns"></i></span>
								<span class="nav-label">Dashboard</span>
							</button>
						</li>
						<li class="nav-group is-open" data-expandable>
							<button class="nav-item nav-toggle" type="button" data-target="programme-panel" aria-expanded="true">
								<span class="nav-icon icon-programme" aria-hidden="true"><i class="fa-solid fa-clipboard-list"></i></span>
								<span class="nav-label">Programme</span>
								<span class="nav-chevron" aria-hidden="true"></span>
							</button>
							<div class="nav-submenu" id="programme-panel">
								<button class="nav-subitem" type="button">Bachelor of IT</button>
								<button class="nav-subitem" type="button">Diploma in Multimedia</button>
								<button class="nav-subitem" type="button">Foundation Studies</button>
							</div>
						</li>
						<li class="nav-group" data-expandable>
							<button class="nav-item nav-toggle" type="button" data-target="examination-panel" aria-expanded="false">
								<span class="nav-icon icon-exam" aria-hidden="true"><i class="fa-solid fa-book"></i></span>
								<span class="nav-label">Examination</span>
								<span class="nav-chevron" aria-hidden="true"></span>
							</button>
							<div class="nav-submenu" id="examination-panel" hidden>
								<button class="nav-subitem" type="button">Mid-term Schedule</button>
								<button class="nav-subitem" type="button">Final Exam Slots</button>
								<button class="nav-subitem" type="button">Results Archive</button>
							</div>
						</li>
					</ul>
				</nav>
			</aside>

			<main class="dashboard" aria-label="Dashboard">
				<header class="dashboard-topbar">
					<div class="search-box">
						<label class="sr-only" for="dashboard-search">Search</label>
						<input id="dashboard-search" type="search" placeholder="Search" autocomplete="off">
						<span id="currentDate" class="sr-only"></span>
					</div>
					<div class="topbar-right">
						<button class="icon-btn" type="button" aria-label="Notifications">
							<span class="icon-bell" aria-hidden="true"></span>
							<span class="badge"></span>
						</button>
						<a class="switch-link" href="./staff-dashboard.php">Staff Portal</a>
						<div class="user-card" style="display: flex; align-items: center; gap: 12px; flex-direction: row-reverse;">
    <a href="student-profile.php" class="profile-link" title="View Profile" style="text-decoration: none; cursor: pointer;">
        <div class="profile-pic" style="width: 42px; height: 42px; border-radius: 50%; background: var(--purple-tint); display: grid; place-items: center; border: 2px solid var(--purple-soft); overflow: hidden; transition: transform 0.2s ease;">
            <i class="fa-solid fa-user" style="color: var(--purple-base); font-size: 18px;"></i>
        </div>
    </a>

    <div class="user-meta" style="text-align: right;">
        <span class="user-name" style="display: block; font-weight: 600; color: var(--text-main);">
            <?php echo htmlspecialchars($fullName); ?>
        </span>
        <span class="user-status" style="font-size: 12px; color: var(--text-sub);">Student</span>
    </div>
</div>
					</div>
				</header>

				<section class="welcome-card">
    <div class="welcome-details">
        <p class="welcome-date" id="welcomeDate"></p>
        <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($firstName); ?>!</h1>
        <p class="welcome-text">Always stay updated in your student portal.</p>
    </div>
</section>

				<section class="announcements-card">
					<header class="announcements-header">
						<h2>Announcements</h2>
						<div class="actions">
							<button id="refreshBtn" class="btn" type="button" aria-label="Refresh announcements">Refresh</button>
							<a class="see-all" href="#">See all</a>
						</div>
					</header>
					<div id="announcements" class="announcements" aria-live="polite"></div>
					<p class="hint">No announcements yet. Content will appear here once connected to the database.</p>
				</section>
			</main>
		</div>
	</div>

	<script src="./script.js"></script>
</body>
</html>


