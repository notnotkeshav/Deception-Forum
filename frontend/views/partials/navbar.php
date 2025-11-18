<style type="text/css">
    @font-face {
        font-family: 'vamp';
        src: url('/public/fonts/ScaryVampire.ttf') format('truetype');
    }

    /* Navigation Styles */
    body {
        margin: 0;
        padding: 0;
        font-family: 'Courier New', monospace;
        background: #000;
    }

    .navbar {
        background-color: #000;
        border-bottom: 1px solid #960d0d;
        padding: 10px 0;
        width: 100%;
        box-shadow: 0 0 10px rgba(255, 0, 0, 0.3);
    }

    .container {
        width: 90%;
        max-width: 1390px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .navbar-brand {
        color: #f03;
        font-weight: bold;
        font-size: 1.5em;
        text-decoration: none;
        font-family: 'vamp', sans-serif;
        letter-spacing: 1px;
        text-shadow: 0 0 5px rgba(255, 0, 0, 0.5);
    }

    .navbar-nav {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .nav-item {
        margin-left: 25px;
        position: relative;
    }

    .nav-link {
        color: #fff;
        text-decoration: none;
        font-size: 0.9em;
        padding: 5px 0;
        transition: all 0.3s;
        border-bottom: 1px solid transparent;
        text-transform: uppercase;
        letter-spacing: 1px;
        position: relative;
        display: inline-block;
    }

    .nav-link:hover {
        color: #f03;
        border-bottom: 1px solid #f03;
        text-shadow: 0 0 5px rgba(255, 0, 0, 0.5);
    }

    .btn {
        padding: 5px 15px;
        border-radius: 3px;
        font-size: 0.9em;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .btn-danger {
        background: #960d0d;
        color: #fff;
        border: 1px solid #960d0d;
        font-weight: bold;
    }

    .btn-danger:hover {
        background: #c00;
        border-color: #c00;
        box-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
    }

    .notification-badge {
        display: none;
        background: #f03;
        color: #000;
        border-radius: 25px;
        padding: 2px 6px;
        font-size: 0.7em;
        font-weight: bold;
        position: absolute;
        top: -8px;
        right: -15px;
        box-shadow: 0 0 10px rgba(255, 0, 51, 0.8);
        min-width: 18px;
        text-align: center;
    }

    .notification-badge.visible {
        display: inline-block;
    }

    /* Hide badge when on notifications page */
    body[data-page="notifications"] .notification-badge {
        display: none !important;
    }

    /* Active link indicator */
    .nav-link.active {
        color: #f03;
        border-bottom: 1px solid #f03;
    }

    /* Pulse animation for notification badge */
    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
            opacity: 1;
        }

        50% {
            transform: scale(1.15);
            opacity: 0.85;
        }
    }

    .notification-badge.visible {
        animation: pulse 2s infinite;
    }

    /* Blinking cursor effect */
    @keyframes blink {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0;
        }
    }

    .navbar-brand::after {
        content: "_";
        animation: blink 1s step-end infinite;
        color: #f03;
    }
</style>

<nav class="navbar">
    <div class="container">
        <a class="navbar-brand" href="/">RED_SKULL</a>

        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" href="/threads">Threads</a>
            </li>
            <?php if ($_SESSION['user']['accessLevel'] >= 5) : ?>
                <li class="nav-item">
                    <a class="nav-link" href="/generate_invite_code">Invites</a>
                </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link" href="/notifications">
                    Notifications
                    <span id="notif-badge" class="notification-badge">0</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/private-chats">Private</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/group-chats">Group</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/user">Profile</a>
            </li>
            <li class="nav-item">
                <a id="logout" class="btn btn-danger" href="#">Logout</a>
            </li>
        </ul>
    </div>
</nav>

<script type="text/javascript">
    // Set page identifier on body for CSS targeting
    (function() {
        const path = window.location.pathname;
        if (path === '/notifications' || path.startsWith('/notifications/')) {
            document.body.setAttribute('data-page', 'notifications');
        }
    })();

    // Logout functionality
    document.getElementById('logout').addEventListener('click', function(e) {
        e.preventDefault();

        if (confirm('Terminate session?')) {
            fetch('/signout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    redirect: 'follow'
                })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                        return;
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.success) {
                        console.log(data.message);
                        sessionStorage.clear();
                        localStorage.clear();
                        window.location.href = '/signin';
                    } else if (data && data.error) {
                        alert('Logout failed: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error during logout:', error);
                    alert('An error occurred during logout. Please try again.');
                });
        }
    });

    // Simulate active link based on current page
    const currentPage = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });

    // ========================================
    // NOTIFICATION POLLING SYSTEM
    // ========================================
    (function() {
        let lastCheckTime = Math.floor(Date.now() / 1000);
        let isPolling = false;
        const isOnNotificationsPage = window.location.pathname === '/notifications' ||
            window.location.pathname.startsWith('/notifications/');

        function updateNotificationBadge(count) {
            const badge = document.getElementById('notif-badge');
            if (!badge) return;

            // Don't show badge on notifications page
            if (isOnNotificationsPage) {
                badge.classList.remove('visible');
                return;
            }

            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.add('visible');
            } else {
                badge.classList.remove('visible');
            }
        }

        function showDesktopNotification(notification) {
            if ('Notification' in window && Notification.permission === 'granted') {
                const notif = new Notification(notification.title, {
                    body: notification.message,
                    icon: '/public/images/logo.png',
                    badge: '/public/images/badge.png',
                    tag: notification.id,
                    requireInteraction: false
                });

                notif.onclick = function() {
                    window.focus();
                    const data = notification.data ? JSON.parse(notification.data) : null;
                    if (data && data.thread_id) {
                        window.location.href = `/thread?id=${data.thread_id}`;
                    } else {
                        window.location.href = '/notifications';
                    }
                    notif.close();
                };

                setTimeout(() => notif.close(), 5000);
            }
        }

        function pollNotifications() {
            if (isPolling) return;
            isPolling = true;

            fetch(`/notifications/poll?last_check=${lastCheckTime}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        updateNotificationBadge(data.details.unread_count);

                        if (data.details.new_notifications && data.details.new_notifications.length > 0) {
                            data.details.new_notifications.forEach(notif => {
                                showDesktopNotification(notif);
                            });
                        }

                        lastCheckTime = data.details.timestamp;
                    }
                })
                .catch(err => {
                    console.error('Notification poll error:', err);
                })
                .finally(() => {
                    isPolling = false;
                });
        }

        // Request notification permission on first interaction
        document.addEventListener('click', function requestPermission() {
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }
            // Remove listener after first click
            document.removeEventListener('click', requestPermission);
        }, {
            once: true
        });

        // Initial poll
        pollNotifications();

        // Poll every 30 seconds
        setInterval(pollNotifications, 30000);

        // Poll when page becomes visible
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                pollNotifications();
            }
        });

        // Poll when window gains focus
        window.addEventListener('focus', function() {
            pollNotifications();
        });
    })();
</script>