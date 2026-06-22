<script src="/public/javascripts/auth.js" defer></script>
<script src="/public/javascripts/thread.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php if (isAuthenticated() && !empty($_SESSION['user']['accessLevel']) && $_SESSION['user']['accessLevel'] >= 5): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize notification client for authenticated users
    const notificationClient = new NotificationClient({
        onNewNotification: function(notification) {
            // You can customize this to show toast notifications or update UI
            console.log('New notification:', notification);
        },
        onUnreadCountUpdate: function(count) {
            // Update the notification badge
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            }
        },
        onError: function(error, retryCount) {
            if (retryCount === 1) {
                console.warn('Notification polling connection lost. Retrying...');
            }
        }
    });
    
    // Request notification permission
    NotificationClient.requestPermission().then(permission => {
        if (permission === 'granted') {
            console.log('Browser notifications enabled');
        } else if (permission === 'denied') {
            console.log('Browser notifications denied');
        }
    });
    
    // Load initial unread count
    NotificationClient.getUnreadCount().then(count => {
        const badge = document.querySelector('.notification-badge');
        if (badge && count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'inline-block';
        }
    });
    
    // Start polling
    notificationClient.start();
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        notificationClient.stop();
    });
});
</script>
<?php endif; ?>

</body>
</html>