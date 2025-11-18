<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'GET') {
    try {
        $userId = $_SESSION['userId'] ?? null;

        if (!$userId) {
            sendJsonResponse(false, "Authentication required.", [], 401);
        }

        // Get pagination params
        $limit = min((int)($_GET['limit'] ?? 50), 100); // Max 100
        $offset = (int)($_GET['offset'] ?? 0);

        // Fetch notifications for user
        $stmt = $db->query(
            "SELECT id, type, title, message, data, read_at, created_at 
             FROM notifications 
             WHERE userId = :userId 
             ORDER BY created_at DESC 
             LIMIT :limit OFFSET :offset",
            [
                ':userId' => $userId,
                ':limit' => $limit,
                ':offset' => $offset
            ]
        );
        $notifications = $db->getAll($stmt);

        // Get unread count
        $unreadCount = getUnreadNotificationCount($userId);

        http_response_code(200);
        view("notifications/index.view.php", [
            "heading" => "Notifications",
            "notifications" => $notifications,
            "unreadCount" => $unreadCount,
            "limit" => $limit,
            "offset" => $offset
        ]);
    } catch (Exception $e) {
        error_log("Error fetching notifications: " . $e->getMessage());
        sendJsonResponse(false, "An error occurred while fetching notifications.", ["error" => $e->getMessage()], 500);
    }
} elseif ($method === 'POST') {
    // Handle AJAX actions
    $action = $_POST['action'] ?? '';
    $userId = $_SESSION['userId'] ?? null;

    if (!$userId) {
        sendJsonResponse(false, "Authentication required.", [], 401);
    }

    try {
        if ($action === 'mark_read') {
            $notificationId = $_POST['notification_id'] ?? null;

            if ($notificationId) {
                // Mark single notification as read
                $db->query(
                    "UPDATE notifications 
                     SET read_at = NOW() 
                     WHERE id = :id AND userId = :userId",
                    [':id' => $notificationId, ':userId' => $userId]
                );

                // Get updated unread count
                $unreadCount = getUnreadNotificationCount($userId);

                sendJsonResponse(true, "Notification marked as read", ['unread_count' => $unreadCount], 200);
            } else {
                // Mark all as read
                $db->query(
                    "UPDATE notifications 
                     SET read_at = NOW() 
                     WHERE userId = :userId AND read_at IS NULL",
                    [':userId' => $userId]
                );

                sendJsonResponse(true, "All notifications marked as read", ['unread_count' => 0], 200);
            }
        } elseif ($action === 'delete') {
            $notificationId = $_POST['notification_id'] ?? null;

            if (!$notificationId) {
                sendJsonResponse(false, "Notification ID required", [], 400);
            }

            $db->query(
                "DELETE FROM notifications WHERE id = :id AND userId = :userId",
                [':id' => $notificationId, ':userId' => $userId]
            );

            // Get updated unread count
            $unreadCount = getUnreadNotificationCount($userId);

            sendJsonResponse(true, "Notification deleted", ['unread_count' => $unreadCount], 200);
        } else {
            sendJsonResponse(false, "Invalid action", [], 400);
        }
    } catch (Exception $e) {
        error_log("Error processing notification action: " . $e->getMessage());
        sendJsonResponse(false, "An error occurred.", ["error" => $e->getMessage()], 500);
    }
} else {
    sendJsonResponse(false, "Invalid HTTP method", [], 405);
}
