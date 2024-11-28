<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();
$body = getRequestBody();

if ($method === 'POST') {
   if (!isset($_POST['threadId'], $_POST['content'])) {
      http_response_code(400);
      echo json_encode(["success" => false, "error" => "Thread ID and content are required.", 'body' => $_POST]);
      exit();
   }

   $userId = $_SESSION['userId'];
   $threadId = $_POST['threadId'];
   $content = $_POST['content'];
   $parentCommentId = isset($_POST['parentCommentId']) && $_POST['parentCommentId'] !== '' 
      ? $_POST['parentCommentId'] 
      : null;

   try {
      $stmt = $db->query(
         "INSERT INTO comments (userId, threadId, content, parentCommentId, createdAt) 
          VALUES (:userId, :threadId, :content, :parentCommentId, NOW())",
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
         http_response_code(200);
         echo json_encode(["success" => true, 'msg' => 'Comment created']);
         exit();
      } else {
         throw new Exception("Failed to add comment.");
      }
   } catch (Exception $e) {
      http_response_code(500);
      echo json_encode(["success" => false, "error" => $e->getMessage()]);
      exit();
   }
}
