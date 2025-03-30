<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'PUT') {
    parse_str(file_get_contents("php://input"), $_PUT);
    $messageId = $_PUT['messageId'] ?? null;
    $newMessage = $_PUT['message'] ?? null;

    if (!$messageId || !$newMessage) {
        sendJsonResponse(false, "Message ID and new message are required", [], 400);
    }

    $userId = $_SESSION['userId'] ?? null;
    if (!$userId) {
        sendJsonResponse(false, "Unauthorized", [], 401);
    }

    $query = "UPDATE privateChatMessages SET message = :message WHERE id = :messageId AND userId = :userId";
    $updated = $db->query($query, [':message' => $newMessage, ':messageId' => $messageId, ':userId' => $userId]);

    if ($updated) {
        sendJsonResponse(true, "Message updated successfully", [], 200);
    } else {
        sendJsonResponse(false, "Failed to update message", [], 500);
    }
} else {
    sendJsonResponse(false, "Invalid HTTP method", [], 405);
}
