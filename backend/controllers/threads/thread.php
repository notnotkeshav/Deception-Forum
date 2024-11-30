<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();
if (!isset($params['id'])) {
   // 404 Not Found: Thread Id not found
   http_response_code(404);
   view("errors/404.php", [
      "msg" => "Thread Id Not found"
   ]);
   exit();
}

$threadId = $params['id'];
$cache->clearExpired();

if ($method === 'GET') {
   $cachedThread = $cache->get("thread:" . $threadId);
   if ($cachedThread) {
      $thread = $cachedThread['value'];
   } else {
      $stmt = $db->query(
         "SELECT t.*, 
                 cat.id AS category_id, cat.name AS category_name,
                 img.id AS image_id, img.imageUrl AS image_url
          FROM threads t
          LEFT JOIN thread_category_link tcl ON t.id = tcl.threadId
          LEFT JOIN categories cat ON tcl.categoryId = cat.id
          LEFT JOIN thread_images img ON t.id = img.threadId
          WHERE t.id = :id AND t.deleted = 0",
         [":id" => $threadId]
      );
      $threadData = $db->getAll($stmt);
      if ($threadData) {
         $thread = [
            'id' => $threadData[0]['id'],
            'title' => $threadData[0]['title'],
            'content' => $threadData[0]['content'],
            'userId' => $threadData[0]['userId'],
            'locked' => $threadData[0]['locked'],
            'createdAt' => $threadData[0]['createdAt'],
            'editedAt' => $threadData[0]['editedAt'],
            'viewsCount' => $threadData[0]['viewsCount'],
            'upvoteCount' => $threadData[0]['upvoteCount'],
            'downvoteCount' => $threadData[0]['downvoteCount'],
            'comments' => [],
            'category' => null,
            'images' => []
         ];

         $imageIds = [];

         foreach ($threadData as $row) {
            if ($row['category_id'] && !$thread['category']) {
               $thread['category'] = [
                  'id' => $row['category_id'],
                  'name' => $row['category_name']
               ];
            }

            if ($row['image_id'] && !in_array($row['image_id'], $imageIds)) {
               $thread['images'][] = [
                  'id' => $row['image_id'],
                  'url' => $row['image_url']
               ];
               $imageIds[] = $row['image_id'];
            }
         }

         $cache->set("thread:" . $threadId, $thread);
      } else {
         // 404 Not Found: No thread found with the provided ID
         http_response_code(404);
         echo json_encode(["success" => false, "error" => "No thread found with the provided ID."]);
         exit();
      }
   }

   // 200 OK: Successfully retrieved the thread
   http_response_code(200);
   view("threads/one.view.php", [
      "heading" => "Single Thread",
      "thread" => $thread
   ]);
} elseif ($method === 'DELETE') {
   $stmt = $db->query(
      "SELECT locked, userId FROM threads WHERE id = :id AND deleted = 0",
      [":id" => $threadId]
   );
   $existingThread = $db->getOne($stmt);

   if (!$existingThread) {
      // 404 Not Found: Thread not found or already deleted
      http_response_code(404);
      echo json_encode(["success" => false, "message" => "Thread not found or already deleted."]);
      exit();
   }

   if ($existingThread['locked']) {
      // 403 Forbidden: Thread is locked
      http_response_code(403);
      echo json_encode(["success" => false, "message" => "This thread is locked and cannot be deleted."]);
      exit();
   }

   if ($existingThread['userId'] !== $_SESSION['userId']) {
      // 403 Forbidden: Access Denied
      http_response_code(403);
      echo json_encode(["success" => false, "message" => "Access Denied"]);
      exit();
   }

   $stmt = $db->query(
      "UPDATE threads SET deleted = 1 WHERE id = :id AND userId = :userId",
      [
         ":id" => $threadId,
         ":userId" => $_SESSION['userId']
      ]
   );

   $cacheKey = "thread:" . $threadId;
   $cache->delete($cacheKey);

   if ($db->rowCount($stmt) !== 0) {
      // 200 OK: Successfully deleted the thread
      http_response_code(200);
      echo json_encode(["success" => true, "message" => "Thread deleted successfully."]);
   } else {
      // 403 Forbidden: Access Denied
      http_response_code(403);
      echo json_encode(["success" => false, "message" => "Access Denied"]);
   }
} else {
   // 405 Method Not Allowed: Invalid HTTP method
   http_response_code(405);
   echo json_encode(["success" => false, "error" => "Invalid HTTP method."]);
}
