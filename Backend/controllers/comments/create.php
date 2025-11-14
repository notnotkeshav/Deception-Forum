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

    $userId = $_SESSION['userId'];
    $threadId = $_POST['threadId'];
    $content = $_POST['content'];
    $parentCommentId = isset($_POST['parentCommentId']) && $_POST['parentCommentId'] !== ''
        ? $_POST['parentCommentId']
        : null;

    // Check if the thread is locked
    $stmt = $db->query("SELECT locked, userId AS authorId FROM threads WHERE id = :id", [":id" => $threadId]);
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

            // Send notifications
            $notificationManager = new NotificationManager();

            if ($parentCommentId) {
                // This is a reply to a comment
                $parentStmt = $db->query("SELECT userId FROM comments WHERE id = :id", [":id" => $parentCommentId]);
                $parentComment = $db->getOne($parentStmt);

                if ($parentComment) {
                    $notificationManager->notifyCommentReply($parentCommentId, $userId, $parentComment['userId']);
                }
            } else {
                // This is a comment on the thread
                if ($thread) {
                    $notificationManager->notifyThreadComment($threadId, $userId, $thread['authorId']);
                }
            }

            // Check for mentions in the comment content
            if (preg_match_all('/@(\w+)/', $content, $matches)) {
                $mentionedUsernames = array_unique($matches[1]);

                foreach ($mentionedUsernames as $username) {
                    // Get user ID by username
                    $userStmt = $db->query("SELECT id FROM users WHERE username = :username", [":username" => $username]);
                    $mentionedUser = $db->getOne($userStmt);

                    if ($mentionedUser && $mentionedUser['id'] !== $userId) {
                        $notificationManager->notifyMention($mentionedUser['id'], $userId, $threadId, $commentId);
                    }
                }
            }

            sendJsonResponse(true, "Comment created");
        } else {
            throw new Exception("Failed to add comment.");
        }
    } catch (Exception $e) {
        sendJsonResponse(false, $e->getMessage(), [], 500);
    }
}
