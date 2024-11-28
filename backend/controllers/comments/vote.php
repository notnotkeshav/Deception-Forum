<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();
$body = getRequestBody();

if ($method === 'POST' && isset($_POST['action']) && $_POST['action'] === 'vote') {
   if (!isset($_POST['commentId'], $_POST['voteType'])) {
       http_response_code(400);
       echo json_encode(["success" => false, "error" => "Comment ID and vote type (upvote/downvote) are required."]);
       exit();
   }

   $commentId = $_POST['commentId'];
   $voteType = $_POST['voteType'];

   if (!in_array($voteType, ['upvote', 'downvote'])) {
       http_response_code(400);
       echo json_encode(["success" => false, "error" => "Invalid vote type."]);
       exit();
   }

   $stmt = $db->query("SELECT upvoteCount, downvoteCount FROM comments WHERE id = :id", [":id" => $commentId]);
   $comment = $db->getOne($stmt);

   if (!$comment) {
       http_response_code(404);
       echo json_encode(["success" => false, "error" => "Comment not found."]);
       exit();
   }

   if ($voteType === 'upvote') {
       $stmt = $db->query(
           "UPDATE comments SET upvoteCount = upvoteCount + 1 WHERE id = :id",
           [":id" => $commentId]
       );
   } else {
       $stmt = $db->query(
           "UPDATE comments SET downvoteCount = downvoteCount + 1 WHERE id = :id",
           [":id" => $commentId]
       );
   }

   if ($stmt) {
       $cache->delete("thread:" . $comment['threadId']);
       echo json_encode(["success" => true, "message" => ucfirst($voteType) . " successful."]);
       exit();
   } else {
       http_response_code(500);
       echo json_encode(["success" => false, "error" => "Failed to update vote."]);
       exit();
   }
} else {
   http_response_code(405);
   echo json_encode(["success" => false, "error" => "Invalid HTTP method."]);
   exit();
}
