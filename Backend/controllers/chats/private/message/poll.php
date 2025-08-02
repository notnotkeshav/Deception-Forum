<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'GET') {
    $chatId = $_GET['chatId'] ?? null;
    $newestTimestamp = $_GET['newestTimestamp'] ?? null;
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

        sendJsonResponse(true, "Messages fechted successfully", ["messages" => $messages, $newestTimestamp], 200);
        exit;
    } catch (Exception $e) {
        sendJsonResponse(false, 'Internal server error', ["message" => "An error occurred while fetching messages: " . $e->getMessage()], 500,);
        exit;
    }
} else {
    // Return an error if the HTTP method is not GET
    sendJsonResponse(false, "Invalid HTTP method.", [], 405);
}