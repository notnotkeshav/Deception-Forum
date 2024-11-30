<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();
$body = getRequestBody();

if ($method === 'GET') {
    // Fetch comments for a specific thread
    if (!isset($params['threadId'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Thread ID is required."]);
        exit();
    }

    $threadId = $params['threadId'];

    // Fetch comments from database
    $stmt = $db->query("SELECT * FROM comments WHERE threadId = :threadId AND deleted = 0 AND parentCommentId IS NULL ORDER BY createdAt DESC", [":threadId" => $threadId]);
    $comments = $db->getAll($stmt);

    function getReplies($parentId, $db)
    {
        $stmt = $db->query("SELECT * FROM comments WHERE parentCommentId = :parentCommentId AND deleted = 0 ORDER BY createdAt DESC", [":parentCommentId" => $parentId]);
        $replies = $db->getAll($stmt);
        foreach ($replies as &$reply) {
            $reply['replies'] = getReplies($reply['id'], $db);
        }
        return $replies;
    }

    foreach ($comments as &$comment) {
        $comment['replies'] = getReplies($comment['id'], $db);
    }

    echo json_encode(["success" => true, "comments" => $comments]);
    exit();
} elseif ($method === 'DELETE') {
    // Handle comment deletion (soft delete)
    if (!isset($body['commentId'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Comment ID is required.", 'body' => $body]);
        exit();
    }

    $userId = $_SESSION['userId'];
    $commentId = $body['commentId'];

    $stmt = $db->query("SELECT userId, threadId FROM comments WHERE id = :id AND deleted = 0", [":id" => $commentId]);
    $existingComment = $db->getOne($stmt);

    if (!$existingComment) {
        http_response_code(404);
        echo json_encode(["success" => false, "error" => "Comment not found or already deleted."]);
        exit();
    }

    $stmt = $db->query("SELECT locked FROM threads WHERE id = :threadId", [":threadId" => $existingComment['threadId']]);
    $thread = $db->getOne($stmt);

    if ($thread && $thread['locked'] == 1) {
        if ($_SESSION['userId'] !== $existingComment['userId'] && !isAdmin($_SESSION['userId'])) {
            http_response_code(403);
            echo json_encode(["success" => false, "error" => "You cannot delete comments on a locked thread."]);
            exit();
        }
    }

    if ($existingComment['userId'] !== $userId && !isAdmin($userId)) {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "You do not have permission to delete this comment."]);
        exit();
    }

    $stmt = $db->query(
        "UPDATE comments SET deleted = 1 WHERE id = :id",
        [":id" => $commentId]
    );

    if ($stmt) {
        $cache->delete("thread:" . $existingComment['threadId']);
        echo json_encode(["success" => true]);
        exit();
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Failed to delete comment."]);
    }
}

function isAdmin($userId)
{
    global $db;
    $stmt = $db->query("SELECT accessLevel FROM users WHERE id = :userId", [":userId" => $userId]);
    $user = $db->getOne($stmt);
    return $user && $user['accesslevel'] > 10;
}
