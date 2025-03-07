<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'POST') {
    $chatId = $_POST['chatId'] ?? null;
    $message = $_POST['message'] ?? null;

    if (!$chatId || !$message) {
        sendJsonResponse(false, "Chat ID and message are required", [], 400);
    }

    $userId = $_SESSION['userId'];

    $query = "INSERT INTO privateChatMessages (chatId, userId, message) 
              VALUES (:chatId, :userId, :message)";
    $db->query($query, [':chatId' => $chatId, ':userId' => $userId, ':message' => $message]);

    $messageId = $db->lastInsertId();
    sendJsonResponse(true, "Message sent successfully", ['messageId' => $messageId], 201);
} else {
    sendJsonResponse(false, "Invalid HTTP method", [], 405);
}
