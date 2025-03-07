<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $chatId = $_GET['id'] ?? null;

    if (!$chatId) {
        sendJsonResponse(false, "Chat ID is required", [], 400);
    }

    $query = "SELECT pcm.id, pcm.userId, pcm.message, pcm.isEdited, pcm.isDeleted, pcm.sentAt 
              FROM privateChatMessages pcm
              WHERE pcm.chatId = :chatId
              ORDER BY pcm.sentAt ASC";
    $stmt = $db->query($query, [':chatId' => $chatId]);

    $messages = $db->getAll($stmt);
    sendJsonResponse(true, "Messages fetched successfully", $messages, 200);
} else {
    sendJsonResponse(false, "Invalid HTTP method", [], 405);
}
