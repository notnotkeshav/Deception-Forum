<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();
$body = getRequestBody();

if ($method === 'PUT') {
    if (empty($body['commentId']) || empty($body['comment'])) {
        // 400 Bad Request: commentId and comment are required
        sendJsonResponse(false, "commentId and comment are required.", [], 400);
    }

    try {
        // Check if the thread is locked
        $stmt = $db->query(
            "SELECT t.locked FROM comments c
             JOIN threads t ON c.threadId = t.id
             WHERE c.id = :id AND c.isDeleted = 0", 
            [":id" => $body['commentId']]
        );
        $thread = $db->getOne($stmt);

        if ($thread && $thread['locked'] == 1) {
            // 403 Forbidden: Thread is locked
            sendJsonResponse(false, "Thread is locked. You cannot edit comments.", [], 403);
        }

        // Check if the comment exists
        $stmt = $db->query(
            "SELECT userId FROM comments WHERE id = :id AND isDeleted = 0",
            [":id" => $body['commentId']]
        );
        $existingComment = $db->getOne($stmt);

        if (!$existingComment) {
            // 404 Not Found: Comment not found
            sendJsonResponse(false, "Comment not found or is deleted.", [], 404);
        }

        // Check if the user has permission to edit the comment
        if ($_SESSION['userId'] !== $existingComment['userId']) {
            // 403 Forbidden: User does not have permission
            sendJsonResponse(false, "Forbidden. You do not have permission to edit this comment.", [], 403);
        }

        // Update the comment
        $stmt = $db->query(
            "UPDATE comments SET content = :content, editedAt = NOW() WHERE id = :commentId",
            [
                ":content" => $body['comment'],
                ":commentId" => $body['commentId']
            ]
        );

        if ($stmt) {
            // 200 OK: Comment updated successfully
            sendJsonResponse(true, "Comment updated successfully.", [], 200);
        } else {
            // 500 Internal Server Error: Update failed
            sendJsonResponse(false, "Failed to update the comment. Please try again later.", [], 500);
        }
    } catch (Exception $e) {
        // 500 Internal Server Error: Exception occurred
        sendJsonResponse(false, "An error occurred: " . $e->getMessage(), [], 500);
    }
}
