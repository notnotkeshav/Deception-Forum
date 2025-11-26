<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'POST') {
    $chatId = $_POST['chatId'] ?? null;
    $message = $_POST['message'] ?? null;

    if (!$chatId || !$message) {
        sendJsonResponse(false, "Chat ID and message are required", [], 400);
    }

    $userId = $_SESSION['userId'] ?? null;
    if (!$userId) {
        sendJsonResponse(false, "Unauthorized", [], 401);
    }

    // Fetch the other participant's ID from the chat
    $chatQuery = "SELECT user1Id, user2Id FROM privateChats WHERE id = :chatId";
    $chatResult = $db->query($chatQuery, [':chatId' => $chatId])->fetch();

    if (!$chatResult) {
        sendJsonResponse(false, "Chat not found", [], 404);
    }

    // Determine which user is the recipient
    $otherUserId = ($chatResult['user1Id'] == $userId)
        ? $chatResult['user2Id']
        : $chatResult['user1Id'];

    // Fetch current user's username if not in session
    if (!isset($_SESSION['username'])) {
        $userQuery = "SELECT username FROM users WHERE id = :userId";
        $userResult = $db->query($userQuery, [':userId' => $userId])->fetch();
        $_SESSION['username'] = $userResult['username'] ?? 'Unknown User';
    }

    // Insert the message
    $query = "INSERT INTO privateChatMessages (chatId, userId, message) 
              VALUES (:chatId, :userId, :message)";
    $db->query($query, [':chatId' => $chatId, ':userId' => $userId, ':message' => $message]);

    $messageId = $db->lastInsertId();

    // Send notification
    $notificationManager = new \Backend\Utils\NotificationManager();
    $notificationManager->notifySystem(
        $otherUserId,
        "New Private Message",
        "{$_SESSION['username']} sent you a message",
        ['chat_id' => $chatId, 'message_id' => $messageId]
    );

    sendJsonResponse(true, "Message sent successfully", ['messageId' => $messageId], 201);
} else {
    sendJsonResponse(false, "Invalid HTTP method", [], 405);
}
