<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'GET') {
    try {
        $userId = $_SESSION['userId'] ?? null;

        if (!$userId) {
            sendJsonResponse(false, "Authentication required.", [], 401);
        }

        // Fetch private chats where the user is a participant with other user's info (only safe fields)
        $query = "SELECT pc.id, pc.user1Id, pc.user2Id, pc.startedAt,
                         CASE 
                             WHEN pc.user1Id = :userId1 THEN u2.id
                             ELSE u1.id
                         END AS otherUserId,
                         CASE 
                             WHEN pc.user1Id = :userId2 THEN u2.username
                             ELSE u1.username
                         END AS otherUsername,
                         (SELECT pcm.message 
                          FROM privateChatMessages pcm 
                          WHERE pcm.chatId = pc.id 
                          ORDER BY pcm.sentAt DESC 
                          LIMIT 1) AS lastMessage,
                         (SELECT pcm.sentAt 
                          FROM privateChatMessages pcm 
                          WHERE pcm.chatId = pc.id 
                          ORDER BY pcm.sentAt DESC 
                          LIMIT 1) AS lastMessageAt
                  FROM privateChats pc
                  JOIN users u1 ON pc.user1Id = u1.id
                  JOIN users u2 ON pc.user2Id = u2.id
                  WHERE pc.user1Id = :userId3 OR pc.user2Id = :userId4
                  ORDER BY CASE WHEN lastMessageAt IS NULL THEN 1 ELSE 0 END, lastMessageAt DESC";

        $stmt = $db->query($query, [
            ":userId1" => $userId,
            ":userId2" => $userId,
            ":userId3" => $userId,
            ":userId4" => $userId
        ]);
        $chats = $db->getAll($stmt);

        // Remove sensitive internal user IDs from response
        foreach ($chats as &$chat) {
            // Keep only the necessary fields for the view
            unset($chat['user1Id']);
            unset($chat['user2Id']);
        }

        http_response_code(200);
        view("chats/private/all.view.php", [
            "heading" => "All Private Chats",
            "chats" => $chats
        ]);
    } catch (Exception $e) {
        sendJsonResponse(false, "An error occurred while fetching chats.", ["error" => $e->getMessage()], 500);
    }
} else {
    sendJsonResponse(false, "Invalid HTTP method", [], 405);
}
