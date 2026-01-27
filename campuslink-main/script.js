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
			const targetId = toggle.getAttribute("data-target");
			toggle.setAttribute("aria-expanded", String(isOpen));
			if (targetId) {
				const panel = document.getElementById(targetId);
				if (panel) {
					panel.hidden = !isOpen;
				}
			}
		});

		// Initialize hidden state
		const targetId = toggle.getAttribute("data-target");
		if (targetId) {
			const panel = document.getElementById(targetId);
			if (panel) {
				const open = group.classList.contains("is-open");
				panel.hidden = !open;
				toggle.setAttribute("aria-expanded", String(open));
			}
		}
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
		const resp = await fetch("./api/announcements.php", { cache: "no-store" });
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
	const wrap = document.createElement("article");
	wrap.className = "announcement";

	const title = document.createElement("h3");
	title.className = "announcement-title";
	title.textContent = item?.title ?? "Untitled";

	const meta = document.createElement("p");
	meta.className = "announcement-meta";
	meta.textContent = formatDate(item?.createdAt);

	const body = document.createElement("p");
	body.className = "announcement-body";
	body.textContent = item?.body ?? "";

	wrap.appendChild(title);
	wrap.appendChild(meta);
	wrap.appendChild(body);
	return wrap;
}

function formatDate(iso) {
	if (!iso) return "";
	try {
		const d = new Date(iso);
		return d.toLocaleString();
	} catch {
		return String(iso);
	}
}

