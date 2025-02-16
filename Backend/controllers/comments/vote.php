<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();
$body = getRequestBody();

if ($method === 'PUT' && isset($body['action']) && $body['action'] === 'vote') {

    if (empty($body['commentId']) || empty($body['voteType']) || empty($body['userId'])) {
        // 400 Bad Request: Required parameters are missing
        sendJsonResponse(false, "Comment ID, vote type, and user ID are required.", [], 400);
    }

    $commentId = $body['commentId'];
    $voteType = $body['voteType'];
    $userId = $body['userId'];

    if (!in_array($voteType, ['upvote', 'downvote'])) {
        // 400 Bad Request: Invalid vote type
        sendJsonResponse(false, "Invalid vote type. Allowed values are 'upvote' or 'downvote'.", [], 400);
    }

    try {
        // Check if the thread is locked
        $stmt = $db->query(
            "SELECT t.locked FROM comments c
             JOIN threads t ON c.threadId = t.id
             WHERE c.id = :id AND c.isDeleted = 0",
            [":id" => $commentId]
        );
        $thread = $db->getOne($stmt);

        if ($thread && $thread['locked'] == 1) {
            // 403 Forbidden: Thread is locked
            sendJsonResponse(false, "Thread is locked. You cannot vote on comments.", [], 403);
        }

        // Begin transaction
        $db->beginTransaction();

        // Update votes using a stored procedure
        $stmt = $db->query(
            "CALL updateCommentVotesAndGetCounts(:commentId, :voteType, :userId)",
            [
                ":commentId" => $commentId,
                ":voteType" => $voteType,
                ":userId" => $userId
            ]
        );

        // Get updated vote counts
        $updatedCounts = $db->getOne($stmt);

        // Clear cache for the thread
        $stmt = $db->query("SELECT threadId FROM comments WHERE id = :id", [":id" => $commentId]);
        $comment = $db->getOne($stmt);
        if ($comment) {
            $cache->delete("thread:" . $comment['threadId']);
        }

        // Commit the transaction
        $db->commit();

        // 200 OK: Vote successful
        sendJsonResponse(true, ucfirst($voteType) . " successful.", [
            "updatedUpvotes" => $updatedCounts['upvoteCount'],
            "updatedDownvotes" => $updatedCounts['downvoteCount']
        ], 200);
    } catch (Exception $e) {
        $db->rollBack();
        sendJsonResponse(false, "An error occurred: " . $e->getMessage(), [], 500);
    }
} else {
    // 405 Method Not Allowed: Invalid HTTP method
    sendJsonResponse(false, "Invalid HTTP method.", [], 405);
}
