<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'GET') {
    $groupId = $_GET['id'] ?? null;
    $newestTimestamp = $_GET['newestTimestamp'] ?? null;

    if (!$groupId) {
        sendJsonResponse(false, "Group ID is required", [], 400);
    }

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

        // Build query for new group chat messages with user info (only safe fields)
        $query = "SELECT gm.id, gm.userId, gm.message, gm.isEdited, gm.isDeleted, 
                         gm.upvoteCount, gm.downvoteCount, gm.sentAt,
                         u.username
                  FROM groupMessages gm
                  JOIN users u ON gm.userId = u.id
                  WHERE gm.groupId = :groupId";

        $bindings = [":groupId" => $groupId];

        // If newestTimestamp is provided, get only messages newer than that
        if ($newestTimestamp) {
            $query .= " AND gm.sentAt > :newestTimestamp";
            $bindings[":newestTimestamp"] = $newestTimestamp;
        } else {
            // If no timestamp provided, just get the latest message
            $query .= " ORDER BY gm.sentAt DESC LIMIT 1";
        }

        $stmt = $db->query($query, $bindings);
        $messages = $db->getAll($stmt);

        // Mark messages as '[deleted]' if they are flagged as deleted
        foreach ($messages as &$message) {
            if ($message['isDeleted']) {
                $message['message'] = '[deleted]';
            }
            // Remove sensitive fields from response
            unset($message['isDeleted']);
        }

        sendJsonResponse(true, "Messages fetched successfully", ["messages" => $messages], 200);
    } catch (Exception $e) {
        sendJsonResponse(false, "Error fetching new messages", ["error" => $e->getMessage()], 500);
    }
} else {
    sendJsonResponse(false, "Invalid HTTP method.", [], 405);
}
