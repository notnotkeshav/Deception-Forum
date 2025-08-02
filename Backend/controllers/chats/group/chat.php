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

        // Optional: Verify if the user is a member of the group
        $userId = $_SESSION['userId'] ?? null;
        $membershipStmt = $db->query(
            "SELECT id FROM groupMembers WHERE groupId = :groupId AND userId = :userId AND status = 'active'",
            [":groupId" => $groupId, ":userId" => $userId]
        );
        if (!$db->getOne($membershipStmt)) {
            sendJsonResponse(false, "Access denied. You are not a member of this group.", [], 403);
        }

        // Fetch group messages
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

        // Fetch group info with member count
        $groupStmt = $db->query("SELECT groupName FROM chatGroups WHERE id = :id", [':id' => $groupId]);
        $groupInfo = $db->getOne($groupStmt) ?? [];

        $countStmt = $db->query(
            "SELECT COUNT(*) AS memberCount FROM groupMembers WHERE groupId = :groupId AND status = 'active'",
            [':groupId' => $groupId]
        );
        $memberCount = $db->getOne($countStmt);
        $groupInfo['memberCount'] = $memberCount['memberCount'] ?? 0;

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        sendJsonResponse(false, "An error occurred while fetching messages.", [], 500);
    }

    http_response_code(200);
    view("chats/group/chat.view.php", [
        "heading" => "Group: " . htmlspecialchars($groupInfo['groupName'] ?? "Unknown"),
        "messages" => $messages ?? [],
        "groupInfo" => $groupInfo
    ]);
} else {
    sendJsonResponse(false, "Invalid HTTP method.", [], 405);
}
