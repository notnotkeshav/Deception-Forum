<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

$params = getQueryParams();
if (!isset($params['id'])) {
    sendJsonResponse(false, 'Group ID not provided', [], 404);
}

$groupId = $params['id'];

if ($method === 'GET') {
    try {
        $db->beginTransaction();

        // Verify if the user is a member of the group
        $userId = $_SESSION['userId'] ?? null;

        if (!$userId) {
            $db->rollBack();
            sendJsonResponse(false, "Authentication required.", [], 401);
        }

        $membershipStmt = $db->query(
            "SELECT id FROM groupMembers WHERE groupId = :groupId AND userId = :userId AND status = 'active'",
            [":groupId" => $groupId, ":userId" => $userId]
        );

        if (!$db->getOne($membershipStmt)) {
            $db->rollBack();
            sendJsonResponse(false, "Access denied. You are not a member of this group.", [], 403);
        }

        // Fetch group messages with user info (only safe fields)
        $stmt = $db->query(
            "SELECT gm.id, gm.userId, gm.message, gm.isEdited, gm.isDeleted, gm.sentAt,
                    u.username
             FROM groupMessages gm
             JOIN users u ON gm.userId = u.id
             WHERE gm.groupId = :groupId
             ORDER BY gm.sentAt DESC
             LIMIT 50",
            [":groupId" => $groupId]
        );
        $messages = array_reverse($db->getAll($stmt));

        // Fetch group info
        $groupStmt = $db->query(
            "SELECT id, groupName, createdAt, createdBy 
             FROM chatGroups 
             WHERE id = :id",
            [':id' => $groupId]
        );
        $groupInfo = $db->getOne($groupStmt);

        if (!$groupInfo) {
            $db->rollBack();
            sendJsonResponse(false, "Group not found.", [], 404);
        }

        // Fetch member count
        $countStmt = $db->query(
            "SELECT COUNT(*) AS memberCount 
             FROM groupMembers 
             WHERE groupId = :groupId AND status = 'active'",
            [':groupId' => $groupId]
        );
        $memberCountResult = $db->getOne($countStmt);
        $groupInfo['memberCount'] = $memberCountResult['memberCount'] ?? 0;

        // Fetch active members (only safe fields)
        $membersStmt = $db->query(
            "SELECT u.id, u.username, gm.joinedAt, gm.role
             FROM groupMembers gm
             JOIN users u ON gm.userId = u.id
             WHERE gm.groupId = :groupId AND gm.status = 'active'
             ORDER BY gm.joinedAt ASC",
            [':groupId' => $groupId]
        );
        $members = $db->getAll($membersStmt);

        $db->commit();

        http_response_code(200);
        view("chats/group/chat.view.php", [
            "heading" => "Group: " . htmlspecialchars($groupInfo['groupName'] ?? "Unknown"),
            "messages" => $messages,
            "groupInfo" => $groupInfo,
            "members" => $members
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        sendJsonResponse(false, "An error occurred while fetching messages.", ["error" => $e->getMessage()], 500);
    }
} else {
    sendJsonResponse(false, "Invalid HTTP method.", [], 405);
}
