<?php require_once view_path('partials/header.php'); ?>
<?php require_once view_path('partials/navbar.php'); ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><?= htmlspecialchars($heading) ?></h2>
                <div>
                    <span class="badge badge-primary" id="unread-count"><?= $unread_count ?> unread</span>
                    <a href="/notifications/settings" class="btn btn-sm btn-outline-secondary ml-2">Settings</a>
                </div>
            </div>

            <div class="d-flex mb-3">
                <button type="button" class="btn btn-sm btn-primary" id="mark-all-read">Mark All as Read</button>
                <button type="button" class="btn btn-sm btn-outline-primary ml-2" id="refresh-notifications">Refresh</button>
            </div>

            <div id="notifications-container">
                <?php if (empty($notifications)): ?>
                    <div class="alert alert-info" id="no-notifications">
                        <i class="fas fa-bell"></i> No notifications yet.
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="card mb-3 notification-item <?= is_null($notification['read_at']) ? 'unread' : 'read' ?>"
                            data-id="<?= htmlspecialchars($notification['id']) ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div class="flex-grow-1">
                                        <h6 class="card-title">
                                            <?= htmlspecialchars($notification['title']) ?>
                                            <?php if (is_null($notification['read_at'])): ?>
                                                <span class="badge badge-primary badge-sm">New</span>
                                            <?php endif; ?>
                                        </h6>
                                        <p class="card-text"><?= htmlspecialchars($notification['message']) ?></p>
                                        <small class="text-muted">
                                            <?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?>
                                        </small>
                                    </div>
                                    <div class="ml-3">
                                        <?php if (is_null($notification['read_at'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary mark-read"
                                                data-id="<?= htmlspecialchars($notification['id']) ?>">
                                                Mark as Read
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($notification['data']): ?>
                                            <?php $data = json_decode($notification['data'], true); ?>
                                            <?php if (isset($data['thread_id'])): ?>
                                                <a href="/thread?id=<?= htmlspecialchars($data['thread_id']) ?>"
                                                    class="btn btn-sm btn-link">View</a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (count($notifications) >= $limit): ?>
                        <div class="text-center mt-4">
                            <button type="button" class="btn btn-outline-primary" id="load-more"
                                data-offset="<?= $offset + $limit ?>">
                                Load More
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let lastCheckTime = Math.floor(Date.now() / 1000);
        let isPolling = false;

        // Start long polling for new notifications
        function startLongPolling() {
            if (isPolling) return;

            isPolling = true;
            fetch(`/notifications/poll?last_check=${lastCheckTime}&timeout=30`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.notifications.length > 0) {
                        // Add new notifications to the top
                        prependNotifications(data.notifications);
                        updateUnreadCount(data.unread_count);
                    }
                    lastCheckTime = data.timestamp || Math.floor(Date.now() / 1000);
                })
                .catch(error => {
                    console.error('Long polling error:', error);
                })
                .finally(() => {
                    isPolling = false;
                    // Restart polling after a short delay
                    setTimeout(startLongPolling, 1000);
                });
        }

        // Function to prepend new notifications
        function prependNotifications(notifications) {
            const container = document.getElementById('notifications-container');
            const noNotifications = document.getElementById('no-notifications');

            if (noNotifications) {
                noNotifications.remove();
            }

            notifications.forEach(notification => {
                const notificationElement = createNotificationElement(notification);
                container.insertBefore(notificationElement, container.firstChild);
            });
        }

        // Function to create notification element
        function createNotificationElement(notification) {
            const div = document.createElement('div');
            div.className = 'card mb-3 notification-item unread';
            div.setAttribute('data-id', notification.id);

            const data = notification.data ? JSON.parse(notification.data) : {};
            const viewButton = data.thread_id ?
                `<a href="/thread?id=${data.thread_id}" class="btn btn-sm btn-link">View</a>` : '';

            div.innerHTML = `
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="flex-grow-1">
                        <h6 class="card-title">
                            ${escapeHtml(notification.title)}
                            <span class="badge badge-primary badge-sm">New</span>
                        </h6>
                        <p class="card-text">${escapeHtml(notification.message)}</p>
                        <small class="text-muted">${formatDate(notification.created_at)}</small>
                    </div>
                    <div class="ml-3">
                        <button type="button" class="btn btn-sm btn-outline-primary mark-read" 
                                data-id="${notification.id}">Mark as Read</button>
                        ${viewButton}
                    </div>
                </div>
            </div>
        `;

            return div;
        }

        // Function to update unread count
        function updateUnreadCount(count) {
            const unreadCountElement = document.getElementById('unread-count');
            if (unreadCountElement) {
                unreadCountElement.textContent = `${count} unread`;
            }
        }

        // Mark single notification as read
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('mark-read')) {
                const notificationId = e.target.getAttribute('data-id');
                markAsRead([notificationId], e.target.closest('.notification-item'));
            }
        });

        // Mark all notifications as read
        document.getElementById('mark-all-read').addEventListener('click', function() {
            const unreadNotifications = document.querySelectorAll('.notification-item.unread');
            if (unreadNotifications.length === 0) {
                alert('No unread notifications to mark as read.');
                return;
            }

            markAsRead();
        });

        // Refresh notifications
        document.getElementById('refresh-notifications').addEventListener('click', function() {
            location.reload();
        });

        // Function to mark notifications as read
        function markAsRead(notificationIds = null, element = null) {
            const formData = new FormData();
            formData.append('action', 'mark_read');

            if (notificationIds) {
                notificationIds.forEach(id => {
                    formData.append('notification_ids[]', id);
                });
            }

            fetch('/notifications', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (element) {
                            // Mark single notification as read
                            element.classList.remove('unread');
                            element.classList.add('read');
                            const badge = element.querySelector('.badge');
                            const button = element.querySelector('.mark-read');
                            if (badge) badge.remove();
                            if (button) button.remove();
                        } else {
                            // Mark all notifications as read
                            const unreadNotifications = document.querySelectorAll('.notification-item.unread');
                            unreadNotifications.forEach(item => {
                                item.classList.remove('unread');
                                item.classList.add('read');
                                const badge = item.querySelector('.badge');
                                const button = item.querySelector('.mark-read');
                                if (badge) badge.remove();
                                if (button) button.remove();
                            });
                        }
                        updateUnreadCount(0);
                    } else {
                        alert('Failed to mark notifications as read');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while marking notifications as read');
                });
        }

        // Utility functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            });
        }

        // Start long polling
        startLongPolling();
    });
</script>

<style>
    .notification-item.unread {
        border-left: 4px solid #007bff;
        background-color: #f8f9fa;
    }

    .notification-item.read {
        border-left: 4px solid #6c757d;
    }

    .badge-sm {
        font-size: 0.75em;
    }
</style>


<script src="/public/javascripts/notifications.js"></script>
<?php require_once view_path('partials/footer.php'); ?>