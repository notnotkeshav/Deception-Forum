<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⛧ Notifications ⛧</title>
<link rel="shortcut icon" href="/public/images/favicon.ico" type="image/x-icon">
    <style>
        @font-face {
            font-family: 'vamp';
            src: url('/public/fonts/ScaryVampire.ttf') format('truetype');
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #000;
            color: #f8f8f8;
            font-family: 'Courier New', monospace;
            min-height: 100vh;
            padding-top: 80px;
        }

        .notifications-wrapper {
            display: block;
            max-width: 900px;
            margin: 3rem auto;
            padding: 0 1rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .page-title {
            font-family: 'vamp', sans-serif;
            color: #f03;
            font-size: 2.2rem;
            letter-spacing: 3px;
            text-shadow: 0 0 15px rgba(255, 0, 51, 0.6);
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .unread-badge {
            background: #f03;
            color: #fff;
            font-size: 1rem;
            font-weight: bold;
            padding: 0.5rem 1.2rem;
            border-radius: 20px;
            box-shadow: 0 0 15px rgba(255, 0, 51, 0.6);
        }

        .mark-all-read {
            background: #1a0000;
            border: 2px solid #960d0d;
            color: #f03;
            font-family: 'courier new', monospace;
            font-size: 0.9rem;
            font-weight: bold;
            padding: 0.6rem 1.5rem;
            cursor: pointer;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }

        .mark-all-read:hover {
            background: #960d0d;
            color: #fff;
        }

        .mark-all-read:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .settings-btn {
            background: transparent;
            border: 2px solid #555;
            color: #999;
            font-family: 'courier new', monospace;
            font-size: 0.9rem;
            font-weight: bold;
            padding: 0.6rem 1.5rem;
            cursor: pointer;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .settings-btn:hover {
            border-color: #f03;
            color: #f03;
            box-shadow: 0 0 10px rgba(255, 0, 51, 0.3);
        }

        .settings-btn::before {
            content: "⚙ ";
        }

        .notifications-list {
            list-style: none;
            padding: 0;
        }

        .notification-item {
            background: #0a0a0a;
            border-left: 3px solid #333;
            padding: 1.2rem 1.8rem;
            margin-bottom: 0.8rem;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .notification-item.unread {
            border-left-color: #f03;
            background: #110000;
        }

        .notification-item:hover {
            background: #111;
            transform: translateX(8px);
        }

        .notification-content {
            flex: 1;
        }

        .notification-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }

        .notification-type {
            color: #ff6600;
            font-size: 0.85rem;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .notification-time {
            color: #555;
            font-size: 0.75rem;
        }

        .notification-title {
            color: #f03;
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 0.4rem;
        }

        .notification-message {
            color: #999;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .notification-link {
            color: #0af;
            text-decoration: none;
            font-size: 0.85rem;
            margin-top: 0.5rem;
            display: inline-block;
        }

        .notification-link:hover {
            text-decoration: underline;
        }

        .notification-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
            margin-left: 1rem;
        }

        .action-btn {
            background: transparent;
            border: 1px solid #555;
            color: #999;
            padding: 0.4rem 0.8rem;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            border-color: #f03;
            color: #f03;
        }

        .action-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .empty-state {
            text-align: center;
            color: #666;
            font-size: 1.1rem;
            font-style: italic;
            padding: 3rem 0;
            background: #0a0a0a;
            border: 1px solid #333;
        }

        .empty-state::before {
            content: "✓";
            display: block;
            font-size: 3rem;
            color: #0f0;
            margin-bottom: 1rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .page-btn {
            background: #1a0000;
            border: 2px solid #960d0d;
            color: #f03;
            padding: 0.6rem 1.5rem;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .page-btn:hover {
            background: #960d0d;
            color: #fff;
        }

        .page-btn.disabled {
            opacity: 0.3;
            pointer-events: none;
        }
    </style>
</head>

<body>
    <?php require(base_path("/frontend/views/partials/navbar.php")); ?>

    <div class="notifications-wrapper">
        <div class="page-header">
            <div class="page-title-section">
                <h2 class="page-title">⛧ Notifications ⛧</h2>
            </div>
            <div class="header-actions">
                <?php if ($unreadCount > 0): ?>
                    <button class="mark-all-read" id="mark-all-btn" onclick="markAllAsRead()">
                        (<span id="unread-count"><?= $unreadCount ?></span>) Mark All Read
                    </button>
                <?php endif; ?>
                <a href="/notifications/settings" class="settings-btn">Settings</a>
            </div>
        </div>

        <?php if (!empty($notifications)): ?>
            <ul class="notifications-list">
                <?php foreach ($notifications as $notif): ?>
                    <li class="notification-item <?= is_null($notif['read_at']) ? 'unread' : '' ?>"
                        id="notif-<?= htmlspecialchars($notif['id']) ?>">
                        <div class="notification-content">
                            <div class="notification-header">
                                <span class="notification-type"><?= htmlspecialchars(str_replace('_', ' ', $notif['type'])) ?></span>
                                <span class="notification-time"><?= htmlspecialchars($notif['created_at']) ?></span>
                            </div>
                            <div class="notification-title"><?= htmlspecialchars($notif['title']) ?></div>
                            <div class="notification-message"><?= htmlspecialchars($notif['message']) ?></div>
                            <?php
                            $data = $notif['data'] ? json_decode($notif['data'], true) : null;
                            if ($data && isset($data['thread_id'])):
                            ?>
                                <a href="/thread?id=<?= htmlspecialchars($data['thread_id']) ?>" class="notification-link">View Thread →</a>
                            <?php endif; ?>
                        </div>
                        <div class="notification-actions">
                            <?php if (is_null($notif['read_at'])): ?>
                                <button class="action-btn mark-read-btn"
                                    data-id="<?= htmlspecialchars($notif['id']) ?>"
                                    onclick="markAsRead(this, '<?= htmlspecialchars($notif['id']) ?>')">✓</button>
                            <?php endif; ?>
                            <button class="action-btn delete-btn"
                                data-id="<?= htmlspecialchars($notif['id']) ?>"
                                onclick="deleteNotification(this, '<?= htmlspecialchars($notif['id']) ?>')">✕</button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="pagination">
                <?php if ($offset > 0): ?>
                    <a href="?offset=<?= max(0, $offset - $limit) ?>&limit=<?= $limit ?>" class="page-btn">← Previous</a>
                <?php endif; ?>
                <?php if (count($notifications) >= $limit): ?>
                    <a href="?offset=<?= $offset + $limit ?>&limit=<?= $limit ?>" class="page-btn">Next →</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                No notifications yet
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateUnreadBadge(count) {
            const countSpan = document.getElementById('unread-count');
            const markAllBtn = document.getElementById('mark-all-btn');

            if (count > 0) {
                if (countSpan) {
                    countSpan.textContent = count;
                }
            } else {
                // Hide the button when count reaches 0
                if (markAllBtn) {
                    markAllBtn.style.display = 'none';
                }
            }
        }

        function markAsRead(button, notificationId) {
            button.disabled = true;

            fetch('/notifications', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=mark_read&notification_id=${notificationId}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const item = document.getElementById(`notif-${notificationId}`);
                        item.classList.remove('unread');
                        button.remove();
                        updateUnreadBadge(data.details.unread_count);

                        // Update navbar badge too
                        const navBadge = document.getElementById('notif-badge');
                        if (navBadge) {
                            if (data.details.unread_count > 0) {
                                navBadge.textContent = data.details.unread_count > 99 ? '99+' : data.details.unread_count;
                                navBadge.classList.add('visible');
                            } else {
                                navBadge.classList.remove('visible');
                            }
                        }
                    } else {
                        alert('Failed to mark as read: ' + data.message);
                        button.disabled = false;
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('An error occurred');
                    button.disabled = false;
                });
        }

        function markAllAsRead() {
            const btn = document.getElementById('mark-all-btn');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Marking...';

            fetch('/notifications', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=mark_read'
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to mark all as read: ' + data.message);
                        btn.disabled = false;
                        btn.textContent = originalText;
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('An error occurred');
                    btn.disabled = false;
                    btn.textContent = originalText;
                });
        }

        function deleteNotification(button, notificationId) {
            if (!confirm('Delete this notification?')) return;

            button.disabled = true;

            fetch('/notifications', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=delete&notification_id=${notificationId}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const item = document.getElementById(`notif-${notificationId}`);
                        item.style.opacity = '0';
                        setTimeout(() => item.remove(), 300);
                        updateUnreadBadge(data.details.unread_count);

                        // Update navbar badge too
                        const navBadge = document.getElementById('notif-badge');
                        if (navBadge) {
                            if (data.details.unread_count > 0) {
                                navBadge.textContent = data.details.unread_count > 99 ? '99+' : data.details.unread_count;
                                navBadge.classList.add('visible');
                            } else {
                                navBadge.classList.remove('visible');
                            }
                        }
                    } else {
                        alert('Failed to delete: ' + data.message);
                        button.disabled = false;
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('An error occurred');
                    button.disabled = false;
                });
        }
    </script>
</body>

</html>