<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

$params = getQueryParams();
parse_str(file_get_contents("php://input"), $body);

if ($method === 'PUT' && isset($body['action']) && $body['action'] === 'vote') {
  if (!isset($body['messageId'], $body['voteType'])) {
    sendJsonResponse(false, "Message ID and vote type are required.", [], 400);
  }

  $messageId = $body['messageId'];
  $voteType = $body['voteType'];
  $userId = $_SESSION['userId'] ?? null;

  if (!$userId) {
    sendJsonResponse(false, "Authentication required.", [], 401);
  }

  if (!in_array($voteType, ['upvote', 'downvote'])) {
    sendJsonResponse(false, "Invalid vote type.", [], 400);
  }

  try {
    // Verify user is a participant in the chat
    $chatCheckStmt = $db->query(
      "SELECT pc.id 
             FROM privateChatMessages pcm
             JOIN privateChats pc ON pcm.chatId = pc.id
             WHERE pcm.id = :messageId 
             AND (pc.user1Id = :userId1 OR pc.user2Id = :userId2)",
      [
        ":messageId" => $messageId,
        ":userId1" => $userId,
        ":userId2" => $userId
      ]
    );

    if (!$db->getOne($chatCheckStmt)) {
      sendJsonResponse(false, "Access denied. You are not a participant in this chat.", [], 403);
    }

    // Call stored procedure to update votes
    $stmt = $db->query(
      "CALL updatePrivateMessageVotesAndGetCounts(:messageId, :voteType, :userId)",
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
    error_log("Error while voting private message: " . $e->getMessage());
    sendJsonResponse(false, "An error occurred while processing your vote.", ["error" => $e->getMessage()], 500);
  }
} else {
  sendJsonResponse(false, "Invalid HTTP method or action.", [], 405);
}
