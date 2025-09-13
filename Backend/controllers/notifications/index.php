<?php

// Get current user
$user = authUser();
if (!$user) {
    abort(401, ['message' => 'Unauthorized']);
}

$userId = $user['id'];

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get notifications with pagination
        $offset = (int) ($_GET['offset'] ?? 0);
        $limit = min((int) ($_GET['limit'] ?? 20), 50); // Max 50 per request
        
        $notifications = getUserNotifications($userId, $offset, $limit);
        $unreadCount = getUnreadNotificationCount($userId);

        error_log(print_r($notifications, true));

        view('notifications/index.view.php', [
            'heading' => 'Notifications',
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'offset' => $offset,
            'limit' => $limit
        ]);
        break;
        
    case 'POST':
        // Handle AJAX requests for various notification actions
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'mark_read':
                $notificationIds = $_POST['notification_ids'] ?? null;
                
                if ($notificationIds && is_array($notificationIds)) {
                    $success = markNotificationsAsRead($userId, $notificationIds);
                } else {
                    // Mark all as read
                    $success = markNotificationsAsRead($userId);
                }
                
                sendJsonResponse($success, $success ? 'Notifications marked as read' : 'Failed to mark notifications as read');
                break;
                
            case 'get_unread_count':
                $count = getUnreadNotificationCount($userId);
                sendJsonResponse(true, 'Unread count retrieved', ['count' => $count]);
                break;
                
            case 'get_recent':
                $limit = min((int) ($_POST['limit'] ?? 10), 20);
                $notifications = getUnreadNotifications($userId, $limit);
                
                sendJsonResponse(true, 'Recent notifications retrieved', [
                    'notifications' => $notifications,
                    'count' => count($notifications)
                ]);
                break;
                
            default:
                sendJsonResponse(false, 'Invalid action', [], 400);
        }
        break;
        
    default:
        abort(405, ['message' => 'Method not allowed']);
}
