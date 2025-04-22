<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();
parse_str(file_get_contents("php://input"), $body);
if ($method === 'PUT' && isset($body['action']) && $body['action'] === 'vote') {
    if (!isset($body['messageId'], $body['voteType'], $body['userId'])) {
        sendJsonResponse(false, "Message ID, vote type, and user ID are required.", [], 400);
    }

    $messageId = $body['messageId'];
    $voteType = $body['voteType'];
    $userId = $body['userId'];

    if (!in_array($voteType, ['upvote', 'downvote'])) {
        sendJsonResponse(false, "Invalid vote type.", [], 400);
    }

    try {
        $stmt = $db->query(
            "CALL updateMessageVotesAndGetCounts(:messageId, :voteType, :userId)",
            [
                ":messageId" => $messageId,
                ":voteType" => $voteType,
                ":userId" => $userId
            ]
        );

        $updatedCounts = $db->getOne($stmt);

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
        error_log("Error while voting group message " . $e->getMessage());
        sendJsonResponse(false, $e->getMessage(), [], 500);
    }
} else {
    sendJsonResponse(false, "Invalid HTTP method.", [], 405);
}