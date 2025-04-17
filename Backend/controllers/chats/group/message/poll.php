<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'GET') {
    try {
        // Build query for new group chat messages
        $query = "SELECT gcm.id, gcm.userId, gcm.message, gcm.isEdited, gcm.isDeleted, gcm.sentAt
                  FROM groupMessages gcm
                  WHERE gcm.groupId = :groupId";

        $bindings = [":groupId" => $groupId];

        // If newestTimestamp is provided, get only messages newer than that
        if ($newestTimestamp) {
            $query .= " AND gcm.sentAt > :newestTimestamp";
            $bindings[":newestTimestamp"] = $newestTimestamp;
        } else {
            // If no timestamp provided, just get the latest message
            $query .= " ORDER BY gcm.sentAt DESC LIMIT 1";
        }

        $stmt = $db->query($query, $bindings);
        $messages = $db->getAll($stmt);

        // Mark messages as '[deleted]' if they are flagged as deleted
        foreach ($messages as &$message) {
            if ($message['isDeleted']) {
                $message['message'] = '[deleted]';
            }
        }

        sendJsonResponse(200, ["success" => true, "messages" => $messages]);
        exit;
    } catch (Exception $e) {
        sendJsonResponse(500, ["success" => false, "message" => "Error fetching new messages: " . $e->getMessage()]);
        exit;
    }
} else {
    sendJsonResponse(405, ["success" => false, "message" => "Invalid HTTP method."]);
}