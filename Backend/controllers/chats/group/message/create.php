<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'POST') {
    $groupId = $_POST['groupId'] ?? null;
    $message = $_POST['message'] ?? null;

    if (!$groupId || !$message) {
        sendJsonResponse(false, "Group ID and message are required", [], 400);
    }

    $userId = $_SESSION['userId'] ?? null;
    if (!$userId) {
        sendJsonResponse(false, "Unauthorized", [], 401);
    }

    // Optional: Validate group membership before allowing message creation
    $checkMembership = $db->query("SELECT 1 FROM groupMembers WHERE groupId = :groupId AND userId = :userId", [
        ':groupId' => $groupId,
        ':userId' => $userId
    ])->fetch();

    if (!$checkMembership) {
        sendJsonResponse(false, "User not a member of this group", [], 403);
    }

    $query = "INSERT INTO groupMessages (groupId, userId, message) 
              VALUES (:groupId, :userId, :message)";
    $db->query($query, [
        ':groupId' => $groupId,
        ':userId' => $userId,
        ':message' => $message
    ]);

    $messageId = $db->lastInsertId();
    $members = getGroupMembers($groupId); // You need to implement this
    $notificationManager = new \Backend\Utils\NotificationManager();

    foreach ($members as $member) {
        if ($member['userId'] !== $userId) { // Don't notify sender
            $notificationManager->notifySystem(
                $member['userId'],
                "New Group Message",
                "{$_SESSION['username']} sent a message in {$groupName}",
                ['group_id' => $groupId, 'message_id' => $messageId]
            );
        }
    }
    sendJsonResponse(true, "Message sent successfully", ['messageId' => $messageId], 201);
} else {
    sendJsonResponse(false, "Invalid HTTP method", [], 405);
}
