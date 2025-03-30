<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'DELETE') {
    parse_str(file_get_contents("php://input"), $_DELETE);
    $messageId = $_DELETE['messageId'] ?? null;

    if (!$messageId) {
        sendJsonResponse(false, "Message ID is required", [], 400);
    }

    $userId = $_SESSION['userId'] ?? null;
    if (!$userId) {
        sendJsonResponse(false, "Unauthorized", [], 401);
    }

    $query = "UPDATE privateChatMessages SET message = '[Deleted]', isDeleted = 1 WHERE id = :messageId AND userId = :userId";
    $updated = $db->query($query, [':messageId' => $messageId, ':userId' => $userId]);

    if ($updated) {
        sendJsonResponse(true, "Message deleted successfully", [], 200);
    } else {
        sendJsonResponse(false, "Failed to delete message", [], 500);
    }
} else {
    sendJsonResponse(false, "Invalid HTTP method", [], 405);
}
