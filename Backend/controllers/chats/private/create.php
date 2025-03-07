<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

// Ensure the user is logged in
if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo "Unauthorized access.";
    exit;
}

$userId = $_SESSION['userId'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch all users except the current user
    $query = "SELECT id, username FROM users WHERE id != :userId";
    $stmt = $db->query($query, [':userId' => $userId]);
    $users = $db->getAll($stmt);

    // Render the view
    http_response_code(200);
    view("chats/private/create.view.php", [
        "heading" => "Start a New Private Chat",
        "users" => $users
    ]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle chat creation
    $recipientId = $_POST['recipientId'] ?? null;

    if (!$recipientId) {
        http_response_code(400);
        echo "Recipient ID is required.";
        exit;
    }

    try {
        // Check if chat already exists
        $query = "
            SELECT id 
            FROM privateChats 
            WHERE (user1Id = :user1 AND user2Id = :user2) 
               OR (user1Id = :user2 AND user2Id = :user1)
        ";
        $stmt = $db->query($query, [
            ':user1' => $userId,
            ':user2' => $recipientId,
            ':user2' => $recipientId,
            ':user1' => $userId,
        ]);
        $existingChat = $db->getFirst($stmt);

        if ($existingChat) {
            echo "Chat already exists with ID: " . htmlspecialchars($existingChat['id']);
            exit;
        }

        // Create a new chat
        $query = "INSERT INTO privateChats (id, user1Id, user2Id) VALUES (UUID(), :user1, :user2)";
        $db->query($query, [
            ':user1' => $userId,
            ':user2' => $recipientId
        ]);

        echo "Chat created successfully.";
    } catch (Exception $e) {
        http_response_code(500);
        error_log("An error occurred: " . $e->getMessage());
        echo $e->getMessage();
    }
} else {
    http_response_code(405);
    echo "Invalid HTTP method.";
    exit;
}
