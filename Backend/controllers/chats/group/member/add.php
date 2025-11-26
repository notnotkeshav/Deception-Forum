<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if (!isset($_SESSION['userId'])) {
    sendJsonResponse(false, "Unauthorized", [], 401);
}

$userId = $_SESSION['userId'];
$params = getQueryParams();
$groupId = $params['groupId'] ?? ($_POST['groupId'] ?? null);

if (!$groupId) {
    sendJsonResponse(false, "Group ID is required", [], 400);
}

if ($method === 'GET') {
    try {
        // Verify current user is part of the group
        $stmt = $db->query(
            "SELECT role FROM groupMembers WHERE groupId = :groupId AND userId = :userId AND status = 'active'",
            [':groupId' => $groupId, ':userId' => $userId]
        );
        $membership = $db->getOne($stmt);

        if (!$membership || !in_array($membership['role'], ['owner', 'admin'])) {
            sendJsonResponse(false, "Access denied", [], 403);
        }

        // Get all users NOT already in the group
        $stmt = $db->query(
            "SELECT u.id, u.username 
             FROM users u 
             WHERE u.id NOT IN (
                SELECT userId FROM groupMembers WHERE groupId = :groupId
             ) AND u.id != :currentUserId",
            [':groupId' => $groupId, ':currentUserId' => $userId]
        );
        $users = $db->getAll($stmt);

        // Get group name
        $stmt = $db->query("SELECT groupName FROM chatGroups WHERE id = :id", [':id' => $groupId]);
        $group = $db->getOne($stmt);

        view("chats/group/member/add.view.php", [
            "heading" => htmlspecialchars($group['groupName'] ?? ''),
            "groupId" => $groupId,
            "users" => $users
        ]);
    } catch (Exception $e) {
        sendJsonResponse(false, "Error loading members view", ["error" => $e->getMessage()], 500);
    }
} elseif ($method === 'POST') {
    $newMemberId = $_POST['memberId'] ?? null;

    if (!$newMemberId) {
        sendJsonResponse(false, "Member ID is required", [], 400);
    }

    try {
        $db->beginTransaction();

        // Validate permissions
        $stmt = $db->query(
            "SELECT role FROM groupMembers WHERE groupId = :groupId AND userId = :userId AND status = 'active'",
            [':groupId' => $groupId, ':userId' => $userId]
        );
        $membership = $db->getOne($stmt);

        if (!$membership || !in_array($membership['role'], ['owner', 'admin'])) {
            $db->rollBack();
            sendJsonResponse(false, "You do not have permission to add members", [], 403);
        }

        // Fetch group information for notification
        $stmt = $db->query(
            "SELECT groupName FROM chatGroups WHERE id = :id",
            [':id' => $groupId]
        );
        $group = $db->getOne($stmt);

        if (!$group) {
            $db->rollBack();
            sendJsonResponse(false, "Group not found", [], 404);
        }

        // Check if user exists
        $stmt = $db->query(
            "SELECT id, username FROM users WHERE id = :userId",
            [':userId' => $newMemberId]
        );
        $newUser = $db->getOne($stmt);

        if (!$newUser) {
            $db->rollBack();
            sendJsonResponse(false, "User not found", [], 404);
        }

        // Check for duplicate membership
        $stmt = $db->query(
            "SELECT id FROM groupMembers WHERE groupId = :groupId AND userId = :memberId",
            [':groupId' => $groupId, ':memberId' => $newMemberId]
        );
        if ($db->getOne($stmt)) {
            $db->rollBack();
            sendJsonResponse(false, "User already in group", [], 409);
        }

        // Add member to group
        $db->query(
            "INSERT INTO groupMembers (id, groupId, userId, role, status) 
             VALUES (UUID(), :groupId, :userId, 'member', 'active')",
            [':groupId' => $groupId, ':userId' => $newMemberId]
        );

        $db->commit();

        // Send notification after successful commit
        try {
            $notificationManager = new \Backend\Utils\NotificationManager();
            $notificationManager->notifySystem(
                $newMemberId,
                "Added to Group Chat",
                "You were added to the group \"{$group['groupName']}\"",
                ['group_id' => $groupId]
            );
        } catch (Exception $notifException) {
            // Log notification failure but don't fail the request
            error_log("Failed to send notification: " . $notifException->getMessage());
        }

        sendJsonResponse(true, "Member added successfully", [
            'memberId' => $newMemberId,
            'username' => $newUser['username'],
            'groupName' => $group['groupName']
        ], 201);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Error adding group member: " . $e->getMessage());
        sendJsonResponse(false, "Failed to add member", ["error" => $e->getMessage()], 500);
    }
} else {
    sendJsonResponse(false, "Invalid HTTP method", [], 405);
}
