<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if (!isset($_SESSION['userId'])) {
    sendJsonResponse(false, "Unauthorized access", [], 401);
}

$userId = $_SESSION['userId'];

if ($method === 'GET') {
    // Fetch all users except the current user
    $query = "SELECT id, username FROM users WHERE id != :userId";
    $stmt = $db->query($query, [':userId' => $userId]);
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

    try {

        $db->beginTransaction();
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
            sendJsonResponse(false, "Chat already exists.", ["chatId" => $existingChat['id']], 200);
        }

        // Create a new chat
        $query = "INSERT INTO privateChats (id, user1Id, user2Id) VALUES (UUID(), :user1, :user2)";
        $db->query($query, [
            ':user1' => $userId,
            ':user2' => $recipientId
        ]);

        $db->commit();
        sendJsonResponse(true, "Chat created successfully.", [], 201);
    } catch (Exception $e) {
        $db->rollBack();
        error_log("An error occurred: " . $e->getMessage());
        sendJsonResponse(false, "Server error occurred.", ["error" => $e->getMessage()], 500);
    }
} else {
    sendJsonResponse(false, "Invalid HTTP method.", [], 405);
}
