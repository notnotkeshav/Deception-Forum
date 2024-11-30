<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();
$body = getRequestBody();

if ($method === 'PUT' && isset($body['action']) && $body['action'] === 'vote') {

    if (!isset($body['commentId'], $body['voteType'], $body['userId'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Comment ID, vote type, and user ID are required."]);
        exit();
    }

    $commentId = (int)$body['commentId'];
    $voteType = $body['voteType'];
    $userId = (int)$body['userId'];

    // Check if the thread is locked
    $stmt = $db->query(
        "SELECT t.locked FROM comments c
         JOIN threads t ON c.threadId = t.id
         WHERE c.id = :id AND c.deleted = 0",
        [":id" => $commentId]
    );
    $thread = $db->getOne($stmt);

    if ($thread && $thread['locked'] == 1) {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "Thread is locked. You cannot vote on comments."]);
        exit();
    }

    if (!in_array($voteType, ['upvote', 'downvote'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Invalid vote type."]);
        exit();
    }

    try {
        $db->beginTransaction();
        $db->query(
            "CALL UpdateVoteAndGetCounts(:commentId, :voteType, :userId)",
            [
                ":commentId" => $commentId, 
                ":voteType" => $voteType, 
                ":userId" => $userId
            ]
        );

        // Get the updated vote counts
        $updatedCounts = $db->getOne(
            $db->query("SELECT upvoteCount, downvoteCount FROM comments WHERE id = :id", [":id" => $commentId])
        );

        // Clear cache for the thread
        $stmt = $db->query("SELECT threadId FROM comments WHERE id = :id", [":id" => $commentId]);
        $comment = $db->getOne($stmt);
        if ($comment) {
            $cache->delete("thread:" . $comment['threadId']);
        }

        // Commit the transaction
        $db->commit();

        // Return updated counts
        echo json_encode([
            "success" => true,
            "message" => ucfirst($voteType) . " successful.",
            "updatedUpvotes" => $updatedCounts['upvoteCount'],
            "updatedDownvotes" => $updatedCounts['downvoteCount']
        ]);
        exit();
    } catch (Exception $e) {
        // Rollback on error
        $db->rollBack();
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "An error occurred: " . $e->getMessage(), $userId]);
        exit();
    }
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Invalid HTTP method."]);
    exit();
}
