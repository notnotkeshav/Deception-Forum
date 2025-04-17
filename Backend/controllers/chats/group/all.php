<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'GET') {
    $userId = $_SESSION['userId'];

    $query = "SELECT * from chatGroups";
    $stmt = $db->query($query, []);

    $groups = $db->getAll($stmt);
    http_response_code(200);
    view("chats/group/all.view.php", [
        "heading" => "All Group Chats",
        "groups" => $groups
    ]);
} else {
    sendJsonResponse(false, "Invalid HTTP method", [], 405);
}