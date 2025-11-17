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
        // Verify user is a member of the group
        $userId = $_SESSION['userId'] ?? null;

        if (!$userId) {
            sendJsonResponse(false, "Authentication required.", [], 401);
        }

        $membershipStmt = $db->query(
            "SELECT id FROM groupMembers WHERE groupId = :groupId AND userId = :userId AND status = 'active'",
            [":groupId" => $groupId, ":userId" => $userId]
        );

        if (!$db->getOne($membershipStmt)) {
            sendJsonResponse(false, "Access denied. You are not a member of this group.", [], 403);
        }

        // Fetch messages with user info (only safe fields)
        $query = "SELECT gm.id, gm.userId, gm.message, gm.isEdited, gm.isDeleted, 
                         gm.upvoteCount, gm.downvoteCount, gm.sentAt,
                         u.username
                  FROM groupMessages gm
                  JOIN users u ON gm.userId = u.id
                  WHERE gm.groupId = :groupId";

        $bindings = [":groupId" => $groupId];

        if ($oldestTimestamp) {
            $query .= " AND gm.sentAt < :oldestTimestamp";
            $bindings[":oldestTimestamp"] = $oldestTimestamp;
        }

        $query .= " ORDER BY gm.sentAt DESC LIMIT :limit";
        $bindings[":limit"] = $limit;

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

        // For initial load or paginated fetch, reverse to show oldest at top
        if (!$oldestTimestamp) {
            $messages = array_reverse($messages);
        }

        sendJsonResponse(true, "Messages fetched successfully", ["messages" => $messages], 200);
    } catch (Exception $e) {
        sendJsonResponse(false, "Error fetching messages", ["error" => $e->getMessage()], 500);
    }
} else {
    sendJsonResponse(false, "Invalid HTTP method.", [], 405);
}
