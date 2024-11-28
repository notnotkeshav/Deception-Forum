<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();
$body = getRequestBody();

if ($method === 'PUT') {
   if (!$body['commentId'] || !$body['comment']) {
      // 400 Bad Request: commentId and Comment are required
      http_response_code(400);
      echo json_encode(["success" => false, "error" => "commentId and comment are required."]);
      exit();
   }

   $stmt = $db->query(
      "SELECT userId FROM comments where id = :id AND deleted = 0",
      [":id" => $body['commentId']]
   );
   $existingComment =  $db->getOne($stmt);

   if (!$existingComment) {
      http_response_code(404);
      echo json_encode(["success" => false, "error" => "Comment not found or deleted."]);
      exit();
   }

   if ($_SESSION['userId'] !== $existingComment['userId']) {
      http_response_code(403);
      echo json_encode(["success" => false, "error" => "Forbidden. You do not have permission to edit this comment.", $_SESSION['userId'], $existingComment['userId']]);
      exit();
   }

   $db->query(
      "UPDATE comments SET content = :content, editedAt = NOW() where id = :commentId",
      [
         ":content" => $body['comment'],
         ":commentId" => $body['commentId']
      ]
   );

   http_response_code(200);
   echo json_encode(["success" => true, "message" => "Comment updated successfully."]);
}
