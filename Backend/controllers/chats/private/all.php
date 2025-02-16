<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'GET') {
    $userId = $_SESSION['userId'];

    $query = "SELECT * from privateChatMessages";
    $stmt = $db->query($query, []);

    $chats = $db->getAll($stmt);
    http_response_code(200);
    view("chats/private/all.view.php", [
        "heading" => "All Private Chats",
        "chats" => $chats
    ]);
} else {
    sendJsonResponse(false, "Invalid HTTP method", [], 405);
}
