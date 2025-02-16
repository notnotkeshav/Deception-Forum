<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();
$body = getRequestBody();

if ($method === 'GET') {
    // Fetch comments for a specific thread
    if (!isset($params['threadId'])) {
        sendJsonResponse(false, "Thread ID is required.", [], 400);
    }

    $threadId = $params['threadId'];

    try {
        $stmt = $db->query(
            "SELECT * FROM comments WHERE threadId = :threadId AND isDeleted = 0 AND parentCommentId IS NULL ORDER BY createdAt DESC",
            [":threadId" => $threadId]
        );
        $comments = $db->getAll($stmt);

        function getReplies($parentId, $db)
        {
            $stmt = $db->query(
                "SELECT * FROM comments WHERE parentCommentId = :parentCommentId AND isDeleted = 0 ORDER BY createdAt DESC",
                [":parentCommentId" => $parentId]
            );
            $replies = $db->getAll($stmt);
            foreach ($replies as &$reply) {
                $reply['replies'] = getReplies($reply['id'], $db);
            }
            return $replies;
        }

        foreach ($comments as &$comment) {
            $comment['replies'] = getReplies($comment['id'], $db);
        }

        sendJsonResponse(true, "Comments fetched successfully.", ["comments" => $comments], 200);
    } catch (Exception $e) {
        sendJsonResponse(false, "Failed to fetch comments: " . $e->getMessage(), [], 500);
    }
} elseif ($method === 'DELETE') {
    // Handle comment deletion (soft delete)
    if (!isset($body['commentId'])) {
        sendJsonResponse(false, "Comment ID is required.", [], 400);
    }

    $userId = $_SESSION['userId'];
    $commentId = $body['commentId'];

    try {
        $stmt = $db->query(
            "SELECT userId, threadId FROM comments WHERE id = :id AND isDeleted = 0",
            [":id" => $commentId]
        );
        $existingComment = $db->getOne($stmt);

        if (!$existingComment) {
            sendJsonResponse(false, "Comment not found or already deleted.", [], 404);
        }

        $stmt = $db->query(
            "SELECT locked FROM threads WHERE id = :threadId",
            [":threadId" => $existingComment['threadId']]
        );
        $thread = $db->getOne($stmt);

        if ($thread && $thread['locked'] == 1) {
            if ($_SESSION['userId'] !== $existingComment['userId'] && !isAdmin($_SESSION['userId'])) {
                sendJsonResponse(false, "You cannot delete comments on a locked thread.", [], 403);
            }
        }

        if ($existingComment['userId'] !== $userId && !isAdmin($userId)) {
            sendJsonResponse(false, "You do not have permission to delete this comment.", [], 403);
        }

        $db->beginTransaction();

        $stmt = $db->query(
            "UPDATE comments SET isDeleted = 1 WHERE id = :id",
            [":id" => $commentId]
        );

        if ($stmt) {
            $cache->delete("thread:" . $existingComment['threadId']);
            $db->commit();
            sendJsonResponse(true, "Comment deleted successfully.", [], 200);
        } else {
            $db->rollBack();
            sendJsonResponse(false, "Failed to delete comment.", [], 500);
        }
    } catch (Exception $e) {
        $db->rollBack();
        sendJsonResponse(false, "An error occurred: " . $e->getMessage(), [], 500);
    }
}

function isAdmin($userId)
{
    global $db;
    $stmt = $db->query("SELECT count(*) FROM moderators WHERE userId = :userId", [":userId" => $userId]);
    $user = $db->getOne($stmt);
    return isset($user);
}
