<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();
if (!isset($params['id'])) {
   sendJsonResponse(false, "Thread ID not found", [], 404);
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
                 LEFT JOIN threadCategoryLink tcl ON t.id = tcl.threadId
                 LEFT JOIN categories cat ON tcl.categoryId = cat.id
                 LEFT JOIN threadImages img ON t.id = img.threadId
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
            $db->commit();
         } else {
            $db->rollBack();
            sendJsonResponse(false, "No thread found with the provided ID.", [], 404);
         }
      } catch (Exception $e) {
         $db->rollBack();
         sendJsonResponse(false, "An error occurred while fetching the thread.", ["error" => $e->getMessage()], 500);
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
         sendJsonResponse(false, "Thread not found or already deleted.", [], 404);
      }

      if ($existingThread['locked']) {
         $db->rollBack();
         sendJsonResponse(false, "This thread is locked and cannot be deleted.", [], 403);
      }

      if ($existingThread['userId'] !== $_SESSION['userId']) {
         $db->rollBack();
         sendJsonResponse(false, "Access Denied", [], 403);
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
         sendJsonResponse(true, "Thread deleted successfully.", [], 200);
      } else {
         $db->rollBack();
         sendJsonResponse(false, "Access Denied", [], 403);
      }
   } catch (Exception $e) {
      $db->rollBack();
      sendJsonResponse(false, "An error occurred while deleting the thread.", ["error" => $e->getMessage()], 500);
   }
} else {
   sendJsonResponse(false, "Invalid HTTP method.", [], 405);
}
