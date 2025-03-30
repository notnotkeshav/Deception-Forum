<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

$params = getQueryParams();
if (!isset($params['id'])) {
    sendJsonResponse(404, ["success" => false, "message" => "Chat ID not found"]);
}

$chatId = $params['id'];

if ($method === 'GET') {
    try {
        $db->beginTransaction();

        $stmt = $db->query(
            "SELECT pcm.id, pcm.userId, pcm.message, pcm.isEdited, pcm.isDeleted, pcm.sentAt 
             FROM privateChatMessages pcm
             WHERE pcm.chatId = :chatId
             ORDER BY pcm.sentAt DESC
             LIMIT 20",
            [":chatId" => $chatId]
        );
        $messages = array_reverse($db->getAll($stmt));

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        sendJsonResponse(500, ["success" => false, "message" => "An error occurred while fetching messages."]);
    }

    http_response_code(200);
    view("chats/private/chat.view.php", [
        "heading" => "Chat Messages",
        "messages" => $messages ?? []
    ]);
} else {
    sendJsonResponse(405, ["success" => false, "message" => "Invalid HTTP method."]);
}