<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'GET') {
    $groupId = $_GET['id'];
    $newestTimestamp = $_GET['newestTimestamp'];
    try {
        // Build query for new group chat messages
        $query = "SELECT gcm.id, gcm.userId, gcm.message, gcm.isEdited, gcm.isDeleted, gcm.upvoteCount, gcm.downvoteCount, gcm.sentAt
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

        sendJsonResponse(true, "messages fecthed succesfully", ["messages" => $messages], 200);
        exit;
    } catch (Exception $e) {
        sendJsonResponse(false, "Error fetching new messages", ["details" => "Error fetching new messages: " . $e->getMessage()], 500);
        exit;
    }
} else {
    sendJsonResponse(false, "", ["Invalid HTTP method."], 405);
}
