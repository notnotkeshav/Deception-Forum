<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$body = getRequestBody();

if ($method === 'PUT') {
    if (!isset($body['threadId'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Thread ID is required."]);
        exit();
    }

    $stmt = $db->query(
        "SELECT locked FROM threads WHERE id = :id", 
        [":id" => $body['threadId']]
    );
    $thread = $db->getOne($stmt);

    if (!$thread) {
        http_response_code(404);
        echo json_encode(["success" => false, "error" => "Thread not found."]);
        exit();
    }

    if ($_SESSION['user']['accessLevel'] < 10) {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "You do not have permission to lock/unlock this thread.", $_SESSION['user']['accessLevel']]);
        exit();
    }

    $newLockStatus = $thread['locked'] ? 0 : 1;
    $lockedBy = $newLockStatus ? $_SESSION['userId'] : null;

    $stmt = $db->query(
        "UPDATE threads SET locked = :locked, lockedBy = :lockedBy WHERE id = :id", 
        [":locked" => $newLockStatus,":lockedBy"=>$lockedBy, ":id" => $body['threadId']]
    );

    if ($stmt) {
        $cache->delete("thread:" . $body['threadId']);
        http_response_code(200);
        echo json_encode(["success" => true, "locked" => $newLockStatus]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Failed to update thread lock status."]);
    }
}
