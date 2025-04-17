<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if (!isset($_SESSION['userId'])) {
    sendJsonResponse(false, "Unauthorized access", [], 401);
}

$userId = $_SESSION['userId'];

if ($method === 'GET') {
    // Render the group chat creation view
    view("chats/group/create.view.php", [
        "heading" => "Create a New Group Chat"
    ]);
} elseif ($method === 'POST') {
    $groupName = $_POST['groupName'] ?? null;

    if (!$groupName) {
        sendJsonResponse(false, "Group name is required.", [], 400);
    }

    try {
        $db->beginTransaction();

        $query = "INSERT INTO chatGroups (id, groupName, createdBy) VALUES (UUID(), :groupName, :createdBy)";
        $db->query($query, [
            ':groupName' => $groupName,
            ':createdBy' => $userId
        ]);

        $groupId = $db->lastInsertId();

        if (!$groupId) {
            $groupIdStmt = $db->query("SELECT id FROM chatGroups WHERE createdBy = :uid ORDER BY createdAt DESC LIMIT 1", [':uid' => $userId]);
            $groupRow = $db->getOne($groupIdStmt);
            $groupId = $groupRow['id'] ?? null;
        }

        $db->query("INSERT INTO groupMembers (id, groupId, userId, role) VALUES (UUID(), :groupId, :userId, 'owner')", [
            ':groupId' => $groupId,
            ':userId' => $userId
        ]);

        $db->commit();

        sendJsonResponse(true, "Group chat created successfully.", ['groupId' => $groupId], 201);
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Group chat creation error: " . $e->getMessage());
        sendJsonResponse(false, "Failed to create group chat.", ["error" => $e->getMessage()], 500);
    }
} else {
    sendJsonResponse(false, "Invalid HTTP method.", [], 405);
}