// Fetch announcements from PHP endpoint (XAMPP) and render them.

(function init() {
	setupTopbarDate();
	setupNavToggles();

	const refreshBtn = document.getElementById("refreshBtn");
	if (refreshBtn) {
		refreshBtn.addEventListener("click", () => refreshAnnouncements(refreshBtn));
	}

	// Initial load
	refreshAnnouncements();
})();

function setupTopbarDate() {
	const el = document.getElementById("currentDate");
	const welcomeDate = document.getElementById("welcomeDate");
	if (!el) return;
	const now = new Date();
	el.textContent = now.toLocaleDateString(undefined, {
		weekday: "long",
		year: "numeric",
		month: "long",
		day: "numeric"
	});
	if (welcomeDate) {
		welcomeDate.textContent = now.toLocaleDateString(undefined, {
			year: "numeric",
			month: "long",
			day: "numeric"
		});
	}
}

function setupNavToggles() {
	const groups = document.querySelectorAll(".nav-group[data-expandable]");
	groups.forEach(group => {
		const toggle = group.querySelector(".nav-toggle");
		if (!toggle) return;

		toggle.addEventListener("click", () => {
			const isOpen = group.classList.toggle("is-open");
			toggle.setAttribute("aria-expanded", String(isOpen));
		});

		// Initialize aria-expanded state
		const open = group.classList.contains("is-open");
		toggle.setAttribute("aria-expanded", String(open));
	});
}

async function refreshAnnouncements(button) {
	const container = document.getElementById("announcements");
	const hintEl = document.querySelector(".panel .hint");
	if (!container) return;

	if (hintEl) {
		hintEl.hidden = true;
	}

	if (button) {
		button.disabled = true;
		const original = button.textContent;
		button.textContent = "Refreshing…";
		try {
			await loadAnnouncements(container, hintEl);
		} finally {
			button.textContent = original;
			button.disabled = false;
		}
	} else {
		await loadAnnouncements(container, hintEl);
	}
}

async function loadAnnouncements(container, hintEl) {
	container.innerHTML = "";

	try {
		const resp = await fetch("./api/announcements.php?audience=student", { cache: "no-store" });
		if (!resp.ok) throw new Error("Network response was not ok");
		const json = await resp.json();
		const items = Array.isArray(json?.data) ? json.data : [];

		if (items.length === 0) {
			if (hintEl) {
				hintEl.textContent = "No announcements yet.";
				hintEl.hidden = false;
			}
			return;
		}

		for (const it of items) {
			container.appendChild(renderAnnouncement(it));
		}
	} catch (err) {
		if (hintEl) {
			hintEl.textContent = "Failed to load announcements. Please try again.";
			hintEl.hidden = false;
		}
	}
}

function renderAnnouncement(item) {
	const wrap = document.createElement("a");
	wrap.className = "announcement-item";
	wrap.href = `./student/announcements.php?id=${item?.id}`;

	// Type badge with icon
	const typeBadge = document.createElement("span");
	const annType = item?.announcementType || 'system';
	typeBadge.className = `ann-type-badge type-${annType}`;
	const typeConfig = {
		system: { icon: 'fa-gear', label: 'System' },
		academic: { icon: 'fa-graduation-cap', label: 'Academic' },
		campus: { icon: 'fa-building-columns', label: 'Campus' }
	};
	const config = typeConfig[annType] || typeConfig.system;
	typeBadge.innerHTML = `<i class="fa-solid ${config.icon}"></i>${config.label}`;

	// Pin indicator
	const isPinned = item?.isPinned === 1 || item?.isPinned === '1';
	
	const title = document.createElement("span");
	title.className = "ann-title";
	title.textContent = item?.title ?? "Untitled";
	if (isPinned) {
		title.innerHTML = `<i class="fa-solid fa-thumbtack" style="color:#f59e0b;margin-right:6px;"></i>${item?.title ?? "Untitled"}`;
	}

	const dateSpan = document.createElement("span");
	dateSpan.className = "ann-date";
	dateSpan.textContent = formatDate(item?.createdAt);

	const arrow = document.createElement("i");
	arrow.className = "fa-solid fa-chevron-right ann-arrow";

	wrap.appendChild(typeBadge);
	wrap.appendChild(title);
	wrap.appendChild(dateSpan);
	wrap.appendChild(arrow);
	return wrap;
}

function formatDate(iso) {
	if (!iso) return "";
	try {
		const d = new Date(iso);
		return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
	} catch {
		return String(iso);
	}
}

