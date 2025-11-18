<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$userId = $_SESSION['userId'] ?? null;

if (!$userId) {
    sendJsonResponse(false, "Unauthorized", [], 401);
}

try {
    // Get the timestamp of last check from client
    $lastCheck = $_GET['last_check'] ?? null;

    // Get unread count
    $unreadCount = getUnreadNotificationCount($userId);

    // If lastCheck provided, get new notifications since then
    $newNotifications = [];
    if ($lastCheck) {
        $stmt = $db->query(
            "SELECT id, type, title, message, data, created_at 
             FROM notifications 
             WHERE userId = :userId 
             AND UNIX_TIMESTAMP(created_at) > :lastCheck 
             ORDER BY created_at DESC 
             LIMIT 10",
            [
                ':userId' => $userId,
                ':lastCheck' => (int)$lastCheck
            ]
        );
        $newNotifications = $db->getAll($stmt);
    }

    sendJsonResponse(true, "Poll successful", [
        'unread_count' => $unreadCount,
        'new_notifications' => $newNotifications,
        'timestamp' => time()
    ], 200);
} catch (Exception $e) {
    error_log("Notification poll error: " . $e->getMessage());
    sendJsonResponse(false, "Error", ["error" => $e->getMessage()], 500);
}
