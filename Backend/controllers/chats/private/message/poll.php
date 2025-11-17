<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'GET') {
    $chatId = $_GET['chatId'] ?? null;
    $newestTimestamp = $_GET['newestTimestamp'] ?? null;

    if (!$chatId) {
        sendJsonResponse(false, "Chat ID is required", [], 400);
    }

    try {
        // Verify user is a participant in this private chat
        $userId = $_SESSION['userId'] ?? null;

        if (!$userId) {
            sendJsonResponse(false, "Authentication required.", [], 401);
        }

        $participantStmt = $db->query(
            "SELECT id FROM privateChats WHERE id = :chatId AND (user1Id = :userId1 OR user2Id = :userId2)",
            [":chatId" => $chatId, ":userId1" => $userId, ":userId2" => $userId]
        );

        if (!$db->getOne($participantStmt)) {
            sendJsonResponse(false, "Access denied. You are not a participant in this chat.", [], 403);
        }

        // Build query for new messages with user info (only safe fields)
        $query = "SELECT pcm.id, pcm.userId, pcm.message, pcm.isEdited, pcm.isDeleted, pcm.sentAt,
                         u.username
                  FROM privateChatMessages pcm
                  JOIN users u ON pcm.userId = u.id
                  WHERE pcm.chatId = :chatId";

        $bindings = [":chatId" => $chatId];

        // If newestTimestamp is provided, get only messages newer than that
        if ($newestTimestamp) {
            $query .= " AND pcm.sentAt > :newestTimestamp";
            $bindings[":newestTimestamp"] = $newestTimestamp;
            $query .= " ORDER BY pcm.sentAt ASC";
        } else {
            // If no timestamp provided, just get the latest message
            $query .= " ORDER BY pcm.sentAt DESC LIMIT 1";
        }

        $stmt = $db->query($query, $bindings);
        $messages = $db->getAll($stmt);

        // Mark deleted messages and remove sensitive fields
        foreach ($messages as &$message) {
            if ($message['isDeleted']) {
                $message['message'] = '[deleted]';
            }
            // Remove internal flags from response
            unset($message['isDeleted']);
        }

        sendJsonResponse(true, "Messages fetched successfully", ["messages" => $messages], 200);
    } catch (Exception $e) {
        sendJsonResponse(false, "Internal server error", ["error" => $e->getMessage()], 500);
    }
} else {
    sendJsonResponse(false, "Invalid HTTP method.", [], 405);
}