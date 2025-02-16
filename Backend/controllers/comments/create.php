<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();
$body = getRequestBody();

if ($method === 'POST') {
    if (!isset($_POST['threadId'], $_POST['content'])) {
        sendJsonResponse(false, "Thread ID and content are required", [], 400);
    }

    $userId = $_SESSION['userId'];
    $threadId = $_POST['threadId'];
    $content = $_POST['content'];
    $parentCommentId = isset($_POST['parentCommentId']) && $_POST['parentCommentId'] !== ''
        ? $_POST['parentCommentId']
        : null;

    // Check if the thread is locked
    $stmt = $db->query("SELECT locked FROM threads WHERE id = :id", [":id" => $threadId]);
    $thread = $db->getOne($stmt);

    if ($thread && $thread['locked'] == 1) {
        sendJsonResponse(false, "Thread is locked. You cannot post comments", [], 403);
    }

    try {
        $stmt = $db->query(
            "INSERT INTO comments (userId, threadId, content, parentCommentId) 
             VALUES (:userId, :threadId, :content, :parentCommentId)",
            [
                ":userId" => $userId,
                ":threadId" => $threadId,
                ":content" => $content,
                ":parentCommentId" => $parentCommentId
            ]
        );

        if ($stmt) {
            $commentId = $db->lastInsertId();
            $cache->delete("thread:" . $threadId);
            sendJsonResponse(true, "Comment created");
        } else {
            throw new Exception("Failed to add comment.");
        }
    } catch (Exception $e) {
        sendJsonResponse(false, $e->getMessage(), [], 500);
    }
}
