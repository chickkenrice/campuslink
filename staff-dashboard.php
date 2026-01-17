<?php
// Future: restrict access after login. For now this page is standalone.
?><!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>CAMPUSLink — Staff Dashboard</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
	<style>
		:root {
			--purple-base: #8056ff;
			--purple-deep: #6a46f6;
			--purple-soft: #a886ff;
			--sidebar-gradient: linear-gradient(200deg, #905dff 0%, #6b42f6 100%);
			--body-gradient: linear-gradient(150deg, #f7f4ff 0%, #ece7ff 45%, #e2dcff 100%);
			--text-main: #181b2f;
			--text-sub: rgba(24, 27, 47, 0.58);
			--text-muted: #8e91aa;
			--white: #ffffff;
			--card-shadow: 0 26px 60px rgba(123, 99, 255, 0.22);
			--panel-shadow: 0 18px 48px rgba(62, 70, 135, 0.18);
			--sidebar-width: 260px;
		}

		*,
		*::before,
		*::after {
			box-sizing: border-box;
		}

		html,
		body {
			height: 100%;
		}

		body {
			margin: 0;
			font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, "Noto Sans", sans-serif;
			background: var(--body-gradient);
			color: var(--text-main);
		}

		.app-bg {
			width: 100%;
			max-width: 100%;
		}

		.main-card {
			display: grid;
			grid-template-columns: var(--sidebar-width) 1fr;
			background: var(--white);
			box-shadow: var(--card-shadow);
			overflow: hidden;
			min-height: 100vh;
			width: 100%;
		}

		.sidebar {
			background: var(--sidebar-gradient);
			color: rgba(255, 255, 255, 0.95);
			padding: 38px 28px;
			display: flex;
			flex-direction: column;
			gap: 36px;
			min-height: 100vh;
		}

		.sidebar-head {
			display: flex;
			align-items: center;
			gap: 12px;
		}

		.brand-icon {
			width: 62px;
			height: 62px;
			border-radius: 22px;
			background: linear-gradient(135deg, rgba(255, 255, 255, 0.35), rgba(255, 255, 255, 0.5));
			color: #26134f;
			font-weight: 700;
			display: grid;
			place-items: center;
			font-size: 22px;
			box-shadow: 0 16px 38px rgba(66, 24, 150, 0.35);
		}

		.brand-text {
			display: flex;
			flex-direction: column;
		}

		.brand-name {
			font-size: 18px;
			font-weight: 700;
		}

		.brand-tagline {
			font-size: 12px;
			color: rgba(255, 255, 255, 0.7);
		}

		.sidebar-nav ul {
			list-style: none;
			margin: 0;
			padding: 0;
			display: flex;
			flex-direction: column;
			gap: 12px;
		}

		.nav-item {
			width: 100%;
			display: grid;
			grid-template-columns: auto 1fr auto;
			align-items: center;
			padding: 13px 16px;
			border-radius: 18px;
			border: none;
			background: transparent;
			color: inherit;
			font-size: 15px;
			font-weight: 600;
			cursor: pointer;
			transition: background-color 0.2s ease, transform 0.12s ease;
		}

		.nav-item:hover,
		.nav-item:focus-visible {
			background: rgba(255, 255, 255, 0.18);
			outline: none;
			transform: translateX(5px);
		}

		.nav-item.is-active {
			background: rgba(255, 255, 255, 0.24);
			box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.15);
		}

		.nav-icon {
			width: 32px;
			height: 32px;
			border-radius: 10px;
			background: rgba(255, 255, 255, 0.2);
			display: grid;
			place-items: center;
			color: rgba(255, 255, 255, 0.95);
			font-size: 16px;
		}

		.nav-icon i {
			line-height: 1;
		}

		.nav-chevron::after {
			content: "";
			width: 14px;
			height: 14px;
			mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='white' d='m8.12 9.29 3.88 3.88 3.88-3.88 1.42 1.42L12 16l-5.3-5.29 1.42-1.42Z'/%3E%3C/svg%3E") center/contain no-repeat;
			background: rgba(255, 255, 255, 0.75);
			display: block;
		}

		.dashboard {
			background: var(--white);
			padding: 42px 48px 50px;
			display: flex;
			flex-direction: column;
			gap: 28px;
		}

		.dashboard-topbar {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 24px;
		}

		.search-box {
			flex: 1;
			max-width: 360px;
			position: relative;
		}

		.search-box input {
			width: 100%;
			padding: 13px 20px;
			border-radius: 24px;
			border: none;
			background: rgba(246, 244, 255, 0.95);
			box-shadow: inset 0 0 0 1px rgba(183, 178, 227, 0.45);
			color: var(--text-main);
			font-size: 14px;
		}

		.search-box input::placeholder {
			color: rgba(24, 27, 47, 0.35);
		}

		.search-box input:focus {
			outline: none;
			box-shadow: 0 0 0 3px rgba(128, 86, 255, 0.25);
		}

		.topbar-right {
			display: flex;
			align-items: center;
			gap: 16px;
		}

		.switch-link {
			font-weight: 600;
			color: var(--purple-base);
			text-decoration: none;
			padding: 10px 16px;
			border-radius: 16px;
			background: rgba(128, 86, 255, 0.12);
			box-shadow: 0 12px 20px rgba(128, 86, 255, 0.18);
			transition: background-color 0.15s ease, color 0.15s ease;
		}

		.switch-link:hover {
			color: #fff;
			background: var(--purple-base);
		}

		.icon-btn {
			position: relative;
			width: 46px;
			height: 46px;
			border-radius: 18px;
			border: none;
			background: rgba(246, 244, 255, 0.95);
			display: grid;
			place-items: center;
			cursor: pointer;
			transition: background-color 0.15s ease;
		}

		.icon-btn:hover {
			background: rgba(232, 228, 255, 0.95);
		}

		.icon-bell::before {
			content: "";
			width: 18px;
			height: 18px;
			mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='white' d='M12 2a6 6 0 0 0-6 6v2.586l-.707.707A1 1 0 0 0 6 13h12a1 1 0 0 0 .707-1.707L18 10.586V8a6 6 0 0 0-6-6Zm0 20a3 3 0 0 0 2.995-2.824L15 19h-6a3 3 0 0 0 2.824 2.995L12 22Z'/%3E%3C/svg%3E") center/contain no-repeat;
			background: var(--purple-deep);
			display: block;
		}

		.badge {
			position: absolute;
			top: 10px;
			right: 11px;
			width: 10px;
			height: 10px;
			border-radius: 50%;
			background: #ff5f73;
		}

		.user-card {
			display: flex;
			flex-direction: column;
			align-items: flex-end;
			gap: 4px;
			padding: 0;
			background: transparent;
			box-shadow: none;
			border: none;
		}

		.user-name {
			font-weight: 600;
			color: var(--text-main);
		}

		.user-status {
			font-size: 12px;
			color: var(--text-sub);
		}

		.welcome-card {
			border-radius: 26px;
			background: linear-gradient(140deg, var(--purple-base), var(--purple-deep));
			padding: 32px 36px;
			color: var(--white);
			box-shadow: 0 28px 60px rgba(116, 88, 255, 0.35);
			overflow: hidden;
			position: relative;
		}

		.welcome-card::before,
		.welcome-card::after {
			content: "";
			position: absolute;
			border-radius: 22px;
			background: rgba(255, 255, 255, 0.18);
			transform: rotate(12deg);
		}

		.welcome-card::before {
			width: 160px;
			height: 160px;
			top: 22px;
			right: 44px;
		}

		.welcome-card::after {
			width: 110px;
			height: 110px;
			top: 110px;
			right: 152px;
		}

		.welcome-details {
			position: relative;
			z-index: 1;
			display: flex;
			flex-direction: column;
			gap: 8px;
		}

		.welcome-date {
			margin: 0;
			font-size: 14px;
			color: rgba(255, 255, 255, 0.78);
		}

		.welcome-title {
			margin: 0;
			font-size: 30px;
			font-weight: 700;
		}

		.welcome-text {
			margin: 0;
			max-width: 420px;
			color: rgba(255, 255, 255, 0.85);
		}

		.metrics-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 18px;
		}

		.metric-card {
			border-radius: 20px;
			padding: 18px 20px;
			background: rgba(248, 246, 255, 0.95);
			border: 1px solid rgba(202, 200, 240, 0.9);
			box-shadow: 0 14px 32px rgba(76, 62, 156, 0.12);
			display: flex;
			flex-direction: column;
			gap: 6px;
		}

		.metric-card.primary {
			background: linear-gradient(135deg, var(--purple-base), var(--purple-soft));
			color: #fff;
			border: none;
		}

		.metric-label {
			margin: 0;
			font-size: 14px;
			color: inherit;
		}

		.metric-value {
			margin: 0;
			font-size: 30px;
			font-weight: 700;
			color: inherit;
		}

		.metric-sub {
			font-size: 13px;
			color: inherit;
		}

		.deadlines-card {
			background: var(--white);
			border-radius: 26px;
			padding: 30px 32px;
			box-shadow: var(--panel-shadow);
			display: flex;
			flex-direction: column;
			gap: 16px;
		}

		.deadline-list {
			list-style: none;
			margin: 0;
			padding: 0;
			display: flex;
			flex-direction: column;
			gap: 12px;
		}

		.deadline-item {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 14px 18px;
			border-radius: 16px;
			background: rgba(249, 247, 255, 0.95);
			border: 1px solid rgba(206, 204, 244, 0.8);
		}

		.deadline-label {
			margin: 0;
			font-weight: 600;
			color: var(--text-main);
		}

		.deadline-date {
			font-size: 13px;
			color: var(--text-sub);
		}

		@media (max-width: 900px) {
			.main-card {
				grid-template-columns: 1fr;
			}

			.sidebar {
				flex-direction: row;
				align-items: center;
				gap: 18px;
				padding: 24px;
			}

			.sidebar-nav ul {
				flex-direction: row;
				gap: 16px;
			}

			.metrics-grid {
				grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
			}
		}

		@media (max-width: 640px) {
			.sidebar {
				flex-direction: column;
				align-items: stretch;
			}

			.sidebar-nav ul {
				flex-direction: column;
			}

			.dashboard {
				padding: 30px 22px;
			}

			.dashboard-topbar {
				flex-direction: column;
				align-items: stretch;
			}

			.deadline-item {
				flex-direction: column;
				align-items: flex-start;
				gap: 6px;
			}
		}
	</style>
</head>
<body>
	<div class="app-bg">
		<div class="main-card">
			<aside class="sidebar" aria-label="Staff navigation">
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
							<button class="nav-item is-active" type="button">
								<span class="nav-icon" aria-hidden="true"><i class="fa-solid fa-table-columns"></i></span>
								<span class="nav-label">Dashboard</span>
							</button>
						</li>
						<li>
							<button class="nav-item" type="button">
								<span class="nav-icon" aria-hidden="true"><i class="fa-solid fa-id-badge"></i></span>
								<span class="nav-label">Student Profile</span>
								<span class="nav-chevron" aria-hidden="true"></span>
							</button>
						</li>
						<li>
							<button class="nav-item" type="button">
								<span class="nav-icon" aria-hidden="true"><i class="fa-solid fa-calendar-check"></i></span>
								<span class="nav-label">Attendance</span>
								<span class="nav-chevron" aria-hidden="true"></span>
							</button>
						</li>
						<li>
							<button class="nav-item" type="button">
								<span class="nav-icon" aria-hidden="true"><i class="fa-solid fa-book-open"></i></span>
								<span class="nav-label">Assignments</span>
								<span class="nav-chevron" aria-hidden="true"></span>
							</button>
						</li>
					</ul>
				</nav>
			</aside>

			<main class="dashboard" aria-label="Staff dashboard">
				<header class="dashboard-topbar">
					<div class="search-box">
						<label class="sr-only" for="staff-search">Search</label>
						<input id="staff-search" type="search" placeholder="Search" autocomplete="off">
					</div>
					<div class="topbar-right">
						<button class="icon-btn" type="button" aria-label="Notifications">
							<span class="icon-bell" aria-hidden="true"></span>
							<span class="badge"></span>
						</button>
						<a class="switch-link" href="./index.html">Student Portal</a>
						<div class="user-card">
							<span class="user-name">Dr. Aini Musa</span>
							<span class="user-status">Academic Affairs</span>
						</div>
					</div>
				</header>

				<section class="welcome-card">
					<div class="welcome-details">
						<p class="welcome-date">November 13, 2025</p>
						<h1 class="welcome-title">Good afternoon, Dr. Aini!</h1>
						<p class="welcome-text">Here is an overview of your classes, students, and pending deadlines.</p>
					</div>
				</section>

				<section class="metrics-grid">
					<article class="metric-card primary">
						<p class="metric-label">Total Students</p>
						<p class="metric-value">1,240</p>
						<span class="metric-sub">+12 vs last week</span>
					</article>
					<article class="metric-card">
						<p class="metric-label">Total Classes</p>
						<p class="metric-value">42</p>
						<span class="metric-sub">6 running today</span>
					</article>
					<article class="metric-card">
						<p class="metric-label">Upcoming Deadlines</p>
						<p class="metric-value">5</p>
						<span class="metric-sub">3 due this week</span>
					</article>
				</section>

				<section class="deadlines-card">
					<header class="announcements-header">
						<h2>Upcoming Deadlines</h2>
						<div class="actions">
							<a class="see-all" href="#">View calendar</a>
						</div>
					</header>
					<ul class="deadline-list">
						<li class="deadline-item">
							<p class="deadline-label">Submit mid-term grades</p>
							<span class="deadline-date">Nov 15, 2025</span>
						</li>
						<li class="deadline-item">
							<p class="deadline-label">CS101 assignment review</p>
							<span class="deadline-date">Nov 17, 2025</span>
						</li>
						<li class="deadline-item">
							<p class="deadline-label">Faculty meeting agenda</p>
							<span class="deadline-date">Nov 20, 2025</span>
						</li>
					</ul>
					<p class="hint">These deadlines are placeholders. Connect to your staff database to populate live data.</p>
				</section>
			</main>
		</div>
	</div>

	<script src="./script.js"></script>
</body>
</html>

