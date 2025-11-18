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
            "SELECT c.id, c.content, c.createdAt, c.editedAt,  
                    c.upvoteCount, c.downvoteCount, c.parentCommentId,
                    u.id as userId, u.username
             FROM comments c
             JOIN users u ON c.userId = u.id
             WHERE c.threadId = :threadId 
             AND c.isDeleted = 0 
             AND c.parentCommentId IS NULL 
             ORDER BY c.createdAt DESC",
            [":threadId" => $threadId]
        );
        $comments = $db->getAll($stmt);

        function getReplies($parentId, $db, $depth = 1)
        {
            $stmt = $db->query(
                "SELECT c.id, c.content, c.createdAt, c.editedAt,
                        c.upvoteCount, c.downvoteCount, c.parentCommentId,
                        u.id as userId, u.username
                 FROM comments c
                 JOIN users u ON c.userId = u.id
                 WHERE c.parentCommentId = :parentCommentId 
                 AND c.isDeleted = 0 
                 ORDER BY c.createdAt DESC",
                [":parentCommentId" => $parentId]
            );
            $replies = $db->getAll($stmt);

            foreach ($replies as &$reply) {
                if ($depth < 6) {
                    $reply['replies'] = getReplies($reply['id'], $db, $depth + 1);
                } else {
                    // Check if more replies exist beyond depth limit
                    $stmtCount = $db->query(
                        "SELECT COUNT(*) as replyCount 
                         FROM comments 
                         WHERE parentCommentId = :parentCommentId 
                         AND isDeleted = 0",
                        [":parentCommentId" => $reply['id']]
                    );
                    $countResult = $db->getOne($stmtCount);
                    $reply['hasMoreReplies'] = $countResult && $countResult['replyCount'] > 0;
                }
            }

            return $replies;
        }

        foreach ($comments as &$comment) {
            $comment['replies'] = getReplies($comment['id'], $db, 1);
        }

        sendJsonResponse(true, "Comments fetched successfully.", ["comments" => $comments], 200);
    } catch (Exception $e) {
        error_log("Failed to fetch comments: " . $e->getMessage());
        sendJsonResponse(false, "Failed to fetch comments.", [], 500);
    }
} elseif ($method === 'DELETE') {
    // Handle comment deletion (soft delete)
    if (!isset($body['commentId'])) {
        sendJsonResponse(false, "Comment ID is required.", [], 400);
    }

    $userId = $_SESSION['userId'] ?? null;

    if (!$userId) {
        sendJsonResponse(false, "Authentication required.", [], 401);
    }

    $commentId = $body['commentId'];

    try {
        // Get comment details
        $stmt = $db->query(
            "SELECT c.id, c.userId, c.threadId 
             FROM comments c
             WHERE c.id = :id 
             AND c.isDeleted = 0",
            [":id" => $commentId]
        );
        $existingComment = $db->getOne($stmt);

        if (!$existingComment) {
            sendJsonResponse(false, "Comment not found or already deleted.", [], 404);
        }

        // Check if thread is locked
        $stmt = $db->query(
            "SELECT locked FROM threads WHERE id = :threadId",
            [":threadId" => $existingComment['threadId']]
        );
        $thread = $db->getOne($stmt);

        if ($thread && $thread['locked'] == 1) {
            if ($existingComment['userId'] !== $userId && !isAdmin($userId)) {
                sendJsonResponse(false, "You cannot delete comments on a locked thread.", [], 403);
            }
        }

        // Check permissions
        if ($existingComment['userId'] !== $userId && !isAdmin($userId)) {
            sendJsonResponse(false, "You do not have permission to delete this comment.", [], 403);
        }

        $db->beginTransaction();

        // Soft delete the comment
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
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Error deleting comment: " . $e->getMessage());
        sendJsonResponse(false, "An error occurred while deleting the comment.", [], 500);
    }
} else {
    sendJsonResponse(false, "Invalid HTTP method.", [], 405);
}

/**
 * Check if user is an admin/moderator
 */
function isAdmin($userId): bool
{
    try {
        $db = App::container()->resolve('Core\Database');
        $stmt = $db->query(
            "SELECT COUNT(*) as count FROM moderators WHERE userId = :userId",
            [":userId" => $userId]
        );
        $result = $db->getOne($stmt);
        return $result && $result['count'] > 0;
    } catch (Exception $e) {
        error_log("Failed to check admin status: " . $e->getMessage());
        return false;
    }
}
