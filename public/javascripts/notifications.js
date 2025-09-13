/**
 * Notification Client for Long Polling
 * Handles real-time notifications using long polling technique
 */
class NotificationClient {
    constructor(options = {}) {
        this.options = {
            pollUrl: '/notifications/poll',
            timeout: 30,
            retryDelay: 5000,
            maxRetries: 3,
            onNewNotification: null,
            onUnreadCountUpdate: null,
            onError: null,
            ...options
        };
        
        this.isPolling = false;
        this.retryCount = 0;
        this.lastCheckTime = Math.floor(Date.now() / 1000);
        this.abortController = null;
    }
    
    /**
     * Start long polling for notifications
     */
    start() {
        if (this.isPolling) {
            return;
        }
        
        this.isPolling = true;
        this.retryCount = 0;
        this.poll();
    }
    
    /**
     * Stop long polling
     */
    stop() {
        this.isPolling = false;
        if (this.abortController) {
            this.abortController.abort();
            this.abortController = null;
        }
    }
    
    /**
     * Perform the long polling request
     */
    async poll() {
        if (!this.isPolling) {
            return;
        }
        
        try {
            this.abortController = new AbortController();
            
            const url = new URL(this.options.pollUrl, window.location.origin);
            url.searchParams.append('last_check', this.lastCheckTime);
            url.searchParams.append('timeout', this.options.timeout);
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Cache-Control': 'no-cache',
                },
                signal: this.abortController.signal
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Reset retry count on successful response
                this.retryCount = 0;
                
                // Update last check time
                this.lastCheckTime = data.timestamp || Math.floor(Date.now() / 1000);
                
                // Handle new notifications
                if (data.notifications && data.notifications.length > 0) {
                    this.handleNewNotifications(data.notifications);
                }
                
                // Update unread count
                if (typeof data.unread_count !== 'undefined') {
                    this.updateUnreadCount(data.unread_count);
                }
            }
            
        } catch (error) {
            if (error.name === 'AbortError') {
                // Request was aborted, don't retry
                return;
            }
            
            this.handleError(error);
        } finally {
            this.abortController = null;
            
            // Schedule next poll if still active
            if (this.isPolling) {
                const delay = this.retryCount > 0 ? this.options.retryDelay : 1000;
                setTimeout(() => this.poll(), delay);
            }
        }
    }
    
    /**
     * Handle new notifications
     */
    handleNewNotifications(notifications) {
        notifications.forEach(notification => {
            // Show browser notification if permission granted
            this.showBrowserNotification(notification);
            
            // Call custom callback
            if (this.options.onNewNotification) {
                this.options.onNewNotification(notification);
            }
        });
    }
    
    /**
     * Update unread count
     */
    updateUnreadCount(count) {
        if (this.options.onUnreadCountUpdate) {
            this.options.onUnreadCountUpdate(count);
        }
        
        // Update badge in navbar if it exists
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    /**
     * Handle errors
     */
    handleError(error) {
        this.retryCount++;
        
        console.error('Notification polling error:', error);
        
        if (this.options.onError) {
            this.options.onError(error, this.retryCount);
        }
        
        // Stop polling if max retries reached
        if (this.retryCount >= this.options.maxRetries) {
            console.warn('Max retry attempts reached. Stopping notification polling.');
            this.stop();
        }
    }
    
    /**
     * Show browser notification
     */
    showBrowserNotification(notification) {
        if (!('Notification' in window) || Notification.permission !== 'granted') {
            return;
        }
        
        const options = {
            body: notification.message,
            icon: '/public/images/favicon.ico',
            tag: notification.id,
            renotify: false,
            silent: false
        };
        
        try {
            const browserNotification = new Notification(notification.title, options);
            
            // Auto close after 5 seconds
            setTimeout(() => {
                browserNotification.close();
            }, 5000);
            
            // Handle click
            browserNotification.onclick = (event) => {
                event.preventDefault();
                window.focus();
                
                // Navigate to related content if available
                if (notification.data) {
                    const data = typeof notification.data === 'string' 
                        ? JSON.parse(notification.data) 
                        : notification.data;
                    
                    if (data.thread_id) {
                        window.location.href = `/thread?id=${data.thread_id}`;
                    }
                }
                
                browserNotification.close();
            };
        } catch (error) {
            console.error('Failed to show browser notification:', error);
        }
    }
    
    /**
     * Request notification permission
     */
    static async requestPermission() {
        if (!('Notification' in window)) {
            return 'unsupported';
        }
        
        if (Notification.permission === 'default') {
            const permission = await Notification.requestPermission();
            return permission;
        }
        
        return Notification.permission;
    }
    
    /**
     * Mark notifications as read
     */
    static async markAsRead(notificationIds = null) {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_read');
            
            if (notificationIds && Array.isArray(notificationIds)) {
                notificationIds.forEach(id => {
                    formData.append('notification_ids[]', id);
                });
            }
            
            const response = await fetch('/notifications', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            return data.success;
        } catch (error) {
            console.error('Failed to mark notifications as read:', error);
            return false;
        }
    }
    
    /**
     * Get recent notifications
     */
    static async getRecent(limit = 10) {
        try {
            const formData = new FormData();
            formData.append('action', 'get_recent');
            formData.append('limit', limit);
            
            const response = await fetch('/notifications', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            return data.success ? data.details.notifications : [];
        } catch (error) {
            console.error('Failed to get recent notifications:', error);
            return [];
        }
    }
    
    /**
     * Get unread count
     */
    static async getUnreadCount() {
        try {
            const formData = new FormData();
            formData.append('action', 'get_unread_count');
            
            const response = await fetch('/notifications', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            return data.success ? data.details.count : 0;
        } catch (error) {
            console.error('Failed to get unread count:', error);
            return 0;
        }
    }
}

// Export for use in other scripts
window.NotificationClient = NotificationClient;
