<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$params = getQueryParams();

if (!isset($params['id'])) {
    sendJsonResponse(false, "Group Chat ID is required", [], 400);
}

$groupId = $params['id'];
$oldestTimestamp = $params['oldestTimestamp'] ?? null;
$limit = 20;

if ($method === 'GET') {
    try {
        $query = "SELECT gcm.id, gcm.userId, gcm.message, gcm.isEdited, gcm.isDeleted, gcm.upvoteCount, gcm.downvoteCount, gcm.sentAt
                  FROM groupMessages gcm
                  WHERE gcm.groupId = :groupId";

        $bindings = [":groupId" => $groupId, ":limit" => $limit];

        if ($oldestTimestamp) {
            $query .= " AND gcm.sentAt < :oldestTimestamp";
            $bindings[":oldestTimestamp"] = $oldestTimestamp;
        }

        $query .= " ORDER BY gcm.sentAt DESC LIMIT :limit";

        $stmt = $db->query($query, $bindings);
        $messages = $db->getAll($stmt);

        foreach ($messages as &$message) {
            if ($message['isDeleted']) {
                $message['message'] = '[deleted]';
            }
        }

        // For initial load or paginated fetch, reverse to show oldest at top
        if (!$oldestTimestamp) {
            $messages = array_reverse($messages);
        }

        sendJsonResponse(true, "Messages Fechted", ["messages" => $messages], 200);
    } catch (Exception $e) {
        sendJsonResponse(false, "Error fetching messages", ["message" =>   $e->getMessage(), 500]);
    }
} else {
    sendJsonResponse(false, "Invalid HTTP method.", [], 405);
}
