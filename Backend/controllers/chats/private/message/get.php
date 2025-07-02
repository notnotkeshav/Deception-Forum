<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$params = getQueryParams();

if (!isset($params['id'])) {
    sendJsonResponse(400, ["success" => false, "message" => "Chat ID is required."]);
}

$chatId = $params['id'];
$oldestTimestamp = $params['oldestTimestamp'] ?? null;
$lastMessageId = $params['lastMessageId'] ?? null;
$limit = 20; // Define the chunk size for pagination

// Check if the method is GET
if ($method === 'GET') {
    try {
        // Build the query for fetching messages
        $query = "SELECT pcm.id, pcm.userId, pcm.message, pcm.isEdited, pcm.isDeleted, pcm.sentAt 
                  FROM privateChatMessages pcm
                  WHERE pcm.chatId = :chatId";

        $bindings = [":chatId" => $chatId, ":limit" => $limit];
        
        // If oldestTimestamp is provided, fetch messages older than that timestamp
        if ($oldestTimestamp) {
            // For pagination/infinite scrolling - get messages OLDER than the oldestTimestamp
            $query .= " AND pcm.sentAt < :oldestTimestamp";
            $bindings[":oldestTimestamp"] = $oldestTimestamp;
            $query .= " ORDER BY pcm.sentAt DESC LIMIT :limit";
        } else {
            // For initial load - get the most recent messages
            $query .= " ORDER BY pcm.sentAt DESC LIMIT :limit";
        }

        // Execute the query
        $stmt = $db->query($query, $bindings);
        $messages = $db->getAll($stmt);

        // Modify messages to replace deleted ones with [deleted]
        foreach ($messages as &$message) {
            if ($message['isDeleted']) {
                $message['message'] = '[deleted]'; // Replace the message with '[deleted]'
            }
        }

        // For the initial load or when fetching older messages
        // We need to reverse the order so that oldest are first (top) and newest are last (bottom)
        if (!$lastMessageId) {
            $messages = array_reverse($messages);
        }

        // Send the response with the messages
        sendJsonResponse(200, ["success" => true, "messages" => $messages]);
    } catch (Exception $e) {
        // Handle any errors that occur during the fetch process
        sendJsonResponse(500, ["success" => false, "message" => "An error occurred while fetching messages: " . $e->getMessage()]);
    }
} else {
    // Return an error if the HTTP method is not GET
    sendJsonResponse(405, ["success" => false, "message" => "Invalid HTTP method."]);
}