<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

$params = getQueryParams();
if (!isset($params['id'])) {
    sendJsonResponse(false, "Chat ID not found", [], 404);
}

$chatId = $params['id'];

if ($method === 'GET') {
    try {
        $db->beginTransaction();

        // Verify user is a participant in this private chat
        $userId = $_SESSION['userId'] ?? null;

        if (!$userId) {
            $db->rollBack();
            sendJsonResponse(false, "Authentication required.", [], 401);
        }

        $participantStmt = $db->query(
            "SELECT id FROM privateChats WHERE id = :chatId AND (user1Id = :userId1 OR user2Id = :userId2)",
            [":chatId" => $chatId, ":userId1" => $userId, ":userId2" => $userId]
        );

        if (!$db->getOne($participantStmt)) {
            $db->rollBack();
            sendJsonResponse(false, "Access denied. You are not a participant in this chat.", [], 403);
        }

        // Fetch messages with user info (only safe fields)
        $stmt = $db->query(
            "SELECT pcm.id, pcm.userId, pcm.message, pcm.isEdited, pcm.isDeleted, pcm.sentAt,
                    u.username
             FROM privateChatMessages pcm
             JOIN users u ON pcm.userId = u.id
             WHERE pcm.chatId = :chatId
             ORDER BY pcm.sentAt DESC
             LIMIT 20",
            [":chatId" => $chatId]
        );
        $messages = array_reverse($db->getAll($stmt));

        // Mark deleted messages and remove sensitive fields
        foreach ($messages as &$message) {
            if ($message['isDeleted']) {
                $message['message'] = '[deleted]';
            }
            // Remove internal flags from response
            unset($message['isDeleted']);
        }

        // Fetch chat participant info (only safe fields)
        $chatInfoStmt = $db->query(
            "SELECT pc.id, pc.user1Id, pc.user2Id, pc.startedAt,
                    u1.username AS user1Username,
                    u2.username AS user2Username
             FROM privateChats pc
             JOIN users u1 ON pc.user1Id = u1.id
             JOIN users u2 ON pc.user2Id = u2.id
             WHERE pc.id = :chatId",
            [":chatId" => $chatId]
        );
        $chatInfo = $db->getOne($chatInfoStmt);

        // Determine the other participant
        $otherParticipant = null;
        if ($chatInfo) {
            if ($chatInfo['user1Id'] == $userId) {
                $otherParticipant = [
                    'id' => $chatInfo['user2Id'],
                    'username' => $chatInfo['user2Username']
                ];
            } else {
                $otherParticipant = [
                    'id' => $chatInfo['user1Id'],
                    'username' => $chatInfo['user1Username']
                ];
            }
        }

        $db->commit();

        http_response_code(200);
        view("chats/private/chat.view.php", [
            "heading" => $otherParticipant ? "Chat with " . htmlspecialchars($otherParticipant['username']) : "Chat Messages",
            "messages" => $messages,
            "chatInfo" => $chatInfo,
            "otherParticipant" => $otherParticipant
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        sendJsonResponse(false, "An error occurred while fetching messages.", ["error" => $e->getMessage()], 500);
    }
} else {
    sendJsonResponse(false, "Invalid HTTP method.", [], 405);
}
