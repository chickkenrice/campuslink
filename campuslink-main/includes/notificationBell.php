<?php
/**
 * Notification Bell Component for Student Pages
 * Include this in the topbar, before the user-card div
 * 
 * Usage: <?php include __DIR__ . '/../includes/notificationBell.php'; ?>
 * 
 * Set $notifApiPath before including if not in student/ folder
 */
if (!isset($notifApiPath)) {
    $notifApiPath = '../api/notifications.php';
}
?>
<div class="notif-bell-wrapper">
    <button class="notif-bell-btn" id="notifBellBtn" onclick="toggleNotifDropdown()" title="Notifications">
        <i class="fa-regular fa-bell"></i>
        <span class="notif-badge" id="notifBadge"></span>
    </button>
    <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-header">
            <h3>Notifications</h3>
            <button class="notif-mark-read" onclick="markAllNotifRead()">Mark all read</button>
        </div>
        <div class="notif-list" id="notifList">
            <div class="notif-empty">
                <i class="fa-regular fa-bell-slash"></i>
                <p>No notifications</p>
            </div>
        </div>
        <div class="notif-footer">
            <a href="<?php echo (strpos($notifApiPath, '../') === 0) ? '' : 'student/'; ?>notifications.php" class="notif-view-all">
                View all notifications <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<script>
(function() {
    const API_URL = '<?php echo $notifApiPath; ?>';
    let notifOpen = false;
    let lastFetchTime = 0;

    // ── sessionStorage helpers for seen class notifications ──────────────
    // Keyed by scheduleID string (e.g. "class_5").
    // sessionStorage clears when the tab closes, which is fine — by then
    // the 30-minute window will long have passed.
    const SEEN_KEY = 'cl_seen_class_notifs';

    function getSeenClassIds() {
        try { return new Set(JSON.parse(sessionStorage.getItem(SEEN_KEY) || '[]')); }
        catch(e) { return new Set(); }
    }

    function saveSeenClassIds(set) {
        try { sessionStorage.setItem(SEEN_KEY, JSON.stringify([...set])); }
        catch(e) {}
    }

    function markClassIdsSeen(ids) {
        const seen = getSeenClassIds();
        ids.forEach(id => seen.add(id));
        saveSeenClassIds(seen);
    }

    // Remove IDs that are no longer active (30-min window passed)
    function pruneSeenClassIds(currentActiveIds) {
        const activeSet = new Set(currentActiveIds);
        const seen = getSeenClassIds();
        const pruned = new Set([...seen].filter(id => activeSet.has(id)));
        saveSeenClassIds(pruned);
        return pruned;
    }
    // ─────────────────────────────────────────────────────────────────────
    
    console.log('[NotifBell] Initialized with API:', API_URL);
    
    // Toggle dropdown
    window.toggleNotifDropdown = function() {
        const dropdown = document.getElementById('notifDropdown');
        notifOpen = !notifOpen;
        dropdown.classList.toggle('open', notifOpen);
        
        if (notifOpen) {
            fetchNotifications();
        }
    };
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const wrapper = document.querySelector('.notif-bell-wrapper');
        if (wrapper && !wrapper.contains(e.target) && notifOpen) {
            notifOpen = false;
            document.getElementById('notifDropdown').classList.remove('open');
        }
    });
    
    // Fetch notifications from API
    function fetchNotifications() {
        fetch(API_URL + '?action=list')
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    // Mark all class notifications currently visible as seen
                    const classIds = data.notifications
                        .filter(n => n.type === 'class_soon')
                        .map(n => n.id);
                    if (classIds.length > 0) markClassIdsSeen(classIds);

                    // Prune stale IDs then subtract seen class notifs from count
                    pruneSeenClassIds(classIds);
                    const adjustedUnread = Math.max(0, data.unreadCount - classIds.length);

                    renderNotifications(data.notifications);
                    updateBadge(adjustedUnread);
                }
            })
            .catch(err => console.error('Notification fetch error:', err));
    }
    
    // Update badge count
    function updateBadge(count) {
        const badge = document.getElementById('notifBadge');
        const btn = document.getElementById('notifBellBtn');
        
        if (count > 0) {
            badge.textContent = count > 9 ? '9+' : count;
            badge.classList.remove('hidden');
            btn.classList.add('has-unread');
        } else {
            badge.textContent = '';
            badge.classList.add('hidden');
            btn.classList.remove('has-unread');
        }
    }
    
    // Resolve notification link relative to current page
    function resolveLink(link) {
        if (!link || link === '#') return '#';
        // If we're on the root index.php, prefix with student/
        const isRoot = window.location.pathname.replace(/\/+$/, '').endsWith('campuslink-main') || 
                       window.location.pathname.match(/\/index\.php$/);
        if (isRoot && !link.startsWith('student/') && !link.startsWith('http') && !link.startsWith('/')) {
            return 'student/' + link;
        }
        return link;
    }

    // Render notification list
    function renderNotifications(notifications) {
        const list = document.getElementById('notifList');
        
        if (!notifications || notifications.length === 0) {
            list.innerHTML = `
                <div class="notif-empty">
                    <i class="fa-regular fa-bell-slash"></i>
                    <p>No notifications</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        notifications.forEach(notif => {
            const iconClass = getIconClass(notif.type);
            const iconSymbol = getIcon(notif.type);
            const unreadClass = notif.isRead ? '' : 'unread';
            const href = resolveLink(notif.link);
            
            html += `
                <a href="${href}" class="notif-item ${unreadClass}" 
                   onclick="handleNotifClick(event, ${notif.stored ? notif.id : 0})">
                    <div class="notif-icon ${iconClass}">
                        <i class="${iconSymbol}"></i>
                    </div>
                    <div class="notif-content">
                        <div class="notif-title">${escapeHtml(notif.title)}</div>
                        <div class="notif-message">${escapeHtml(notif.message || '')}</div>
                        <div class="notif-time">${notif.time}</div>
                    </div>
                </a>
            `;
        });
        
        list.innerHTML = html;
    }
    
    // Handle notification click
    window.handleNotifClick = function(e, id) {
        if (id > 0) {
            // Mark as read
            fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=markRead&id=${id}`
            });
        }
        // Don't prevent default - let the link navigate
    };
    
    // Mark all as read
    window.markAllNotifRead = function() {
        fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=markAllRead'
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                fetchNotifications();
            }
        });
    };
    
    // Helper: Get icon class by type
    function getIconClass(type) {
        const classes = {
            'class_soon': 'class-soon',
            'new_assignment': 'new-assignment',
            'booking_update': 'booking-update',
            'announcement': 'announcement'
        };
        return classes[type] || 'announcement';
    }
    
    // Helper: Get icon by type
    function getIcon(type) {
        const icons = {
            'class_soon': 'fa-solid fa-clock',
            'new_assignment': 'fa-solid fa-file-pen',
            'booking_update': 'fa-solid fa-building',
            'announcement': 'fa-solid fa-bullhorn'
        };
        return icons[type] || 'fa-solid fa-bell';
    }
    
    // Helper: Escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Initial badge count fetch
    function fetchBadgeCount() {
        fetch(API_URL + '?action=count')
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(data => {
                console.log('[NotifBell] Badge count:', data);
                if (data.status === 'success') {
                    // Prune stale seen IDs, then subtract seen active class ones
                    const activeClassIds = data.classIds || [];
                    const seen = pruneSeenClassIds(activeClassIds);
                    const seenActiveCount = activeClassIds.filter(id => seen.has(id)).length;
                    const adjustedCount = Math.max(0, data.count - seenActiveCount);
                    updateBadge(adjustedCount);
                } else {
                    console.warn('[NotifBell] API error:', data.message);
                }
            })
            .catch(err => {
                console.error('[NotifBell] Fetch error:', err);
            });
    }
    
    // Fetch badge count on load and periodically
    fetchBadgeCount();
    setInterval(fetchBadgeCount, 60000); // Every 60 seconds
})();
</script>
