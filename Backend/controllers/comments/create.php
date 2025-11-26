<?php

use Backend\Core\App;
use Backend\Utils\NotificationManager;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();
$body = getRequestBody();

if ($method === 'POST') {
    if (!isset($_POST['threadId'], $_POST['content'])) {
        sendJsonResponse(false, "Thread ID and content are required", [], 400);
    }

    $userId = $_SESSION['userId'] ?? null;

    if (!$userId) {
        sendJsonResponse(false, "Authentication required", [], 401);
    }

    $threadId = $_POST['threadId'];
    $content = $_POST['content'];
    $parentCommentId = isset($_POST['parentCommentId']) && $_POST['parentCommentId'] !== ''
        ? $_POST['parentCommentId']
        : null;

    // Check if the thread exists and is not locked
    $stmt = $db->query(
        "SELECT id, locked, userId FROM threads WHERE id = :id",
        [":id" => $threadId]
    );
    $thread = $db->getOne($stmt);

    if (!$thread) {
        sendJsonResponse(false, "Thread not found", [], 404);
    }

    if ($thread['locked'] == 1) {
        sendJsonResponse(false, "Thread is locked. You cannot post comments", [], 403);
    }

    try {
        $db->beginTransaction();

        // Insert the comment
        $commentId = $db->query("SELECT UUID() as id", [])->fetch()['id'];

        $stmt = $db->query(
            "INSERT INTO comments (id, userId, threadId, content, parentCommentId) 
             VALUES (:id, :userId, :threadId, :content, :parentCommentId)",
            [
                ":id" => $commentId,
                ":userId" => $userId,
                ":threadId" => $threadId,
                ":content" => $content,
                ":parentCommentId" => $parentCommentId
            ]
        );

        // Clear cache
        $cache->delete("thread:" . $threadId);

        // Send notifications
        $notificationManager = new NotificationManager();

        if ($parentCommentId) {
            // This is a reply to a comment
            $parentStmt = $db->query(
                "SELECT userId FROM comments WHERE id = :id",
                [":id" => $parentCommentId]
            );
            $parentComment = $db->getOne($parentStmt);

            if ($parentComment && $parentComment['userId'] !== $userId) {
                $notificationManager->notifyCommentReply(
                    $parentCommentId,
                    $userId,
                    $parentComment['userId']
                );
            }
        } else {
            // This is a comment on the thread
            if ($thread['userId'] !== $userId) {
                $notificationManager->notifyThreadComment(
                    $threadId,
                    $userId,
                    $thread['userId']
                );
            }
        }

        // Check for mentions in the comment content
        if (preg_match_all('/@(\w+)/', $content, $matches)) {
            $mentionedUsernames = array_unique($matches[1]);

            foreach ($mentionedUsernames as $username) {
                // Get user ID by username
                $userStmt = $db->query(
                    "SELECT id FROM users WHERE username = :username",
                    [":username" => $username]
                );
                $mentionedUser = $db->getOne($userStmt);

                if ($mentionedUser && $mentionedUser['id'] !== $userId) {
                    $notificationManager->notifyMention(
                        $mentionedUser['id'],
                        $userId,
                        $threadId,
                        $commentId
                    );
                }
            }
        }

        $db->commit();

        // Get the newly created comment with user info for response
        $newCommentStmt = $db->query(
            "SELECT c.id, c.content, c.createdAt, c.parentCommentId,
                    u.username, u.id as userId
             FROM comments c
             JOIN users u ON c.userId = u.id
             WHERE c.id = :commentId",
            [':commentId' => $commentId]
        );
        $newComment = $db->getOne($newCommentStmt);

        sendJsonResponse(true, "Comment created successfully", [
            'comment' => $newComment
        ], 201);
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error creating comment: " . $e->getMessage());
        sendJsonResponse(false, "Failed to create comment", ["error" => $e->getMessage()], 500);
    }
} else {
    sendJsonResponse(false, "Invalid HTTP method", [], 405);
}
