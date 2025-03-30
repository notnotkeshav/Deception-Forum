<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'GET') {
    try {
        // Build query for new messages
        $query = "SELECT pcm.id, pcm.userId, pcm.message, pcm.isEdited, pcm.isDeleted, pcm.sentAt
    FROM privateChatMessages pcm
    WHERE pcm.chatId = :chatId";

        $bindings = [":chatId" => $chatId];

        // If newestTimestamp is provided, get only messages newer than that
        if ($newestTimestamp) {
            $query .= " AND pcm.sentAt > :newestTimestamp";
            $bindings[":newestTimestamp"] = $newestTimestamp;
        } else {
            // If no timestamp provided, just get the latest message
            $query .= " ORDER BY pcm.sentAt DESC LIMIT 1";
        }

        if ($newestTimestamp) {
            $query .= " ORDER BY pcm.sentAt ASC";
        }

        $stmt = $db->query($query, $bindings);
        $messages = $db->getAll($stmt);

        sendJsonResponse(200, ["success" => true, "messages" => $messages]);
        exit;
    } catch (Exception $e) {
        sendJsonResponse(500, ["success" => false, "message" => "Error fetching new messages: " . $e->getMessage()]);
        exit;
    }
} else {
    // Return an error if the HTTP method is not GET
    sendJsonResponse(405, ["success" => false, "message" => "Invalid HTTP method."]);
}