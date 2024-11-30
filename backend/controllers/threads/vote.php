<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();
$body = getRequestBody();

if ($method === 'PUT' && isset($body['action']) && $body['action'] === 'vote') {

    if (!isset($body['threadId'], $body['voteType'], $body['userId'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Thread ID, vote type, and user ID are required."]);
        exit();
    }

    $threadId = (int)$body['threadId'];
    $voteType = $body['voteType'];
    $userId = (int)$body['userId'];

    if (!in_array($voteType, ['upvote', 'downvote'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Invalid vote type."]);
        exit();
    }

    $stmt = $db->query(
        "SELECT locked FROM threads WHERE id = :id AND deleted = 0",
        [":id" => $threadId]
    );
    $thread = $db->getOne($stmt);

    if (!$thread) {
        http_response_code(404);
        echo json_encode(["success" => false, "error" => "Thread not found or deleted."]);
        exit();
    }

    if ($thread['locked'] == 1) {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "Thread is locked. You cannot vote on it."]);
        exit();
    }

    try {
        $db->beginTransaction();

        $db->query(
            "CALL UpdateThreadVotesAndGetCounts(:threadId, :voteType, :userId)",
            [
                ":threadId" => $threadId, 
                ":voteType" => $voteType, 
                ":userId" => $userId
            ]
        );

        $updatedCounts = $db->getOne(
            $db->query("SELECT upvoteCount, downvoteCount FROM threads WHERE id = :id", [":id" => $threadId])
        );

        $cache->delete("thread:" . $threadId);
        $db->commit();

        echo json_encode([
            "success" => true,
            "message" => ucfirst($voteType) . " successful.",
            "updatedUpvotes" => $updatedCounts['upvoteCount'],
            "updatedDownvotes" => $updatedCounts['downvoteCount']
        ]);
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "An error occurred: " . $e->getMessage()]);
        exit();
    }
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Invalid HTTP method."]);
    exit();
}
