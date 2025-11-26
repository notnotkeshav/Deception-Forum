<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if (!isset($_SESSION['userId'])) {
    sendJsonResponse(false, "Unauthorized access", [], 401);
}

$userId = $_SESSION['userId'];

if ($method === 'GET') {
    // Fetch all users except the current user and those who already have a private chat
    $query = "SELECT u.id, u.username 
              FROM users u
              WHERE u.id != :userId1
              AND u.id NOT IN (
                  SELECT CASE 
                      WHEN pc.user1Id = :userId2 THEN pc.user2Id
                      ELSE pc.user1Id
                  END
                  FROM privateChats pc
                  WHERE pc.user1Id = :userId3 OR pc.user2Id = :userId4
              )";

    $stmt = $db->query($query, [
        ':userId1' => $userId,
        ':userId2' => $userId,
        ':userId3' => $userId,
        ':userId4' => $userId
    ]);
    $users = $db->getAll($stmt);

    // Render the view
    view("chats/private/create.view.php", [
        "heading" => "Start a New Private Chat",
        "users" => $users
    ]);
} elseif ($method === 'POST') {
    // Handle chat creation
    $recipientId = $_POST['recipientId'] ?? null;

    if (!$recipientId) {
        sendJsonResponse(false, "Recipient ID is required.", [], 400);
    }

    // Validate recipient exists and is not the current user
    if ($recipientId === $userId) {
        sendJsonResponse(false, "Cannot create a chat with yourself.", [], 400);
    }

    try {
        $db->beginTransaction();

        // Check if recipient exists
        $userCheckStmt = $db->query(
            "SELECT id FROM users WHERE id = :recipientId",
            [':recipientId' => $recipientId]
        );

        if (!$db->getOne($userCheckStmt)) {
            $db->rollBack();
            sendJsonResponse(false, "Recipient user does not exist.", [], 404);
        }

        // Check if chat already exists
        $query = "
            SELECT id 
            FROM privateChats 
            WHERE (user1Id = :user1 AND user2Id = :user2) 
               OR (user1Id = :user3 AND user2Id = :user4)
        ";
        $stmt = $db->query($query, [
            ':user1' => $userId,
            ':user2' => $recipientId,
            ':user3' => $recipientId,
            ':user4' => $userId
        ]);

        $existingChat = $db->getOne($stmt);

        if ($existingChat) {
            $db->rollBack();
            sendJsonResponse(false, "Chat already exists.", ["chatId" => $existingChat['id']], 409);
        }

        // Create a new chat
        $insertQuery = "INSERT INTO privateChats (id, user1Id, user2Id) VALUES (UUID(), :user1, :user2)";
        $db->query($insertQuery, [
            ':user1' => $userId,
            ':user2' => $recipientId
        ]);

        // Get the newly created chat ID
        $newChatStmt = $db->query(
            "SELECT id FROM privateChats WHERE user1Id = :user1 AND user2Id = :user2",
            [':user1' => $userId, ':user2' => $recipientId]
        );
        $newChat = $db->getOne($newChatStmt);

        $db->commit();
        // sendJsonResponse(true, "Chat created successfully.", ["chatId" => $newChat['id']], 201);
        redirect("/chats/private/{$newChat['id']}");
    } catch (Exception $e) {
        $db->rollBack();
        error_log("An error occurred: " . $e->getMessage());
        sendJsonResponse(false, "Server error occurred.", ["error" => $e->getMessage()], 500);
    }
} else {
    sendJsonResponse(false, "Invalid HTTP method.", [], 405);
}
