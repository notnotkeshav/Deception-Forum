<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();
if (!isset($params['id'])) {
   sendJsonResponse(404, ["success" => false, "message" => "Thread ID not found"]);
}

$threadId = $params['id'];
$cache->clearExpired();

if ($method === 'GET') {
   $cachedThread = $cache->get("thread:" . $threadId);
   if ($cachedThread) {
      $thread = $cachedThread['value'];
   } else {
      try {
         $db->beginTransaction();

         $stmt = $db->query(
            "SELECT t.*, 
                        cat.id AS category_id, cat.name AS category_name,
                        img.id AS image_id, img.imageUrl AS image_url
                 FROM threads t
                 LEFT JOIN threadcategorylink tcl ON t.id = tcl.threadId
                 LEFT JOIN categories cat ON tcl.categoryId = cat.id
                 LEFT JOIN threadimages img ON t.id = img.threadId
                 WHERE t.id = :id AND t.isDeleted = 0",
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
            $db->rollBack();
            sendJsonResponse(404, ["success" => false, "message" => "No thread found with the provided ID."]);
         }

         $db->commit();
      } catch (Exception $e) {
         $db->rollBack();
         sendJsonResponse(500, ["success" => false, "message" => "An error occurred while fetching the thread."]);
      }
   }

   http_response_code(200);
   view("threads/one.view.php", [
      "heading" => "Single Thread",
      "thread" => $thread
   ]);
} elseif ($method === 'DELETE') {
   try {
      $db->beginTransaction();

      $stmt = $db->query(
         "SELECT locked, userId FROM threads WHERE id = :id AND isDeleted = 0",
         [":id" => $threadId]
      );
      $existingThread = $db->getOne($stmt);

      if (!$existingThread) {
         $db->rollBack();
         sendJsonResponse(404, ["success" => false, "message" => "Thread not found or already deleted."]);
      }

      if ($existingThread['locked']) {
         $db->rollBack();
         sendJsonResponse(403, ["success" => false, "message" => "This thread is locked and cannot be deleted."]);
      }

      if ($existingThread['userId'] !== $_SESSION['userId']) {
         $db->rollBack();
         sendJsonResponse(403, ["success" => false, "message" => "Access Denied"]);
      }

      $stmt = $db->query(
         "UPDATE threads SET isDeleted = 1 WHERE id = :id AND userId = :userId",
         [
            ":id" => $threadId,
            ":userId" => $_SESSION['userId']
         ]
      );

      $cacheKey = "thread:" . $threadId;
      $cache->delete($cacheKey);

      if ($db->rowCount($stmt) !== 0) {
         $db->commit();
         sendJsonResponse(200, ["success" => true, "message" => "Thread deleted successfully."]);
      } else {
         $db->rollBack();
         sendJsonResponse(403, ["success" => false, "message" => "Access Denied"]);
      }
   } catch (Exception $e) {
      $db->rollBack();
      sendJsonResponse(500, ["success" => false, "message" => "An error occurred while deleting the thread."]);
   }
} else {
   sendJsonResponse(405, ["success" => false, "message" => "Invalid HTTP method."]);
}
