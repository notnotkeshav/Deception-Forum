<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();
$body = getRequestBody();

if ($method === 'PUT' && isset($body['action']) && $body['action'] === 'vote') {
    if (!isset($body['threadId'], $body['voteType'], $body['userId'])) {
        sendJsonResponse(false, "Thread ID, vote type, and user ID are required.", [], 400);
    }

    $threadId = $body['threadId'];
    $voteType = $body['voteType'];
    $userId = $body['userId'];

    if (!in_array($voteType, ['upvote', 'downvote'])) {
        sendJsonResponse(false, "Invalid vote type.", [], 400);
    }

    $stmt = $db->query(
        "SELECT locked FROM threads WHERE id = :id AND isDeleted = 0",
        [":id" => $threadId]
    );
    $thread = $db->getOne($stmt);

    if (!$thread) {
        sendJsonResponse(false, "Thread not found or deleted.", [], 404);
    }

    if ($thread['locked'] == 1) {
        sendJsonResponse(false, "Thread is locked. You cannot vote on it.", [], 403);
    }

    try {
        $db->beginTransaction();

        $stmt = $db->query(
            "CALL UpdateThreadVotesAndGetCounts(:threadId, :voteType, :userId)",
            [
                ":threadId" => $threadId,
                ":voteType" => $voteType,
                ":userId" => $userId
            ]
        );

        $updatedCounts = $db->getOne($stmt);

        $cache->delete("thread:" . $threadId);
        $db->commit();

        sendJsonResponse(
            true,
            ucfirst($voteType) . " successful.",
            [
                "updatedUpvotes" => $updatedCounts['upvoteCount'],
                "updatedDownvotes" => $updatedCounts['downvoteCount']
            ],
            200
        );
    } catch (Exception $e) {
        $db->rollBack();
        sendJsonResponse(false, $e->getMessage(), [], 500);
    }
} else {
    sendJsonResponse(false, "Invalid HTTP method.", [], 405);
}
