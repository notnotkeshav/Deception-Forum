<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();
if (!isset($params['id'])) {
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
      $stmt = $db->query("SELECT t.*, 
                                 c.id AS comment_id, c.content AS comment_content, c.userId AS comment_userId, c.editedAt AS comment_edited_at, c.createdAt AS comment_created_at, c.deleted AS comment_deleted,
                                 cat.id AS category_id, cat.name AS category_name,
                                 img.id AS image_id, img.imageUrl AS image_url
                            FROM threads t
                            LEFT JOIN comments c ON t.id = c.threadId AND c.deleted = 0
                            LEFT JOIN thread_category_link tcl ON t.id = tcl.threadId
                            LEFT JOIN categories cat ON tcl.categoryId = cat.id
                            LEFT JOIN thread_images img ON t.id = img.threadId
                            WHERE t.id = :id AND t.deleted = 0
                        ", [":id" => $threadId]);
      $threadData = $db->getAll($stmt);
      if ($threadData) {
         $thread = [
            'id' => $threadData[0]['id'],
            'title' => $threadData[0]['title'],
            'content' => $threadData[0]['content'],
            'userId' => $threadData[0]['userId'],
            'createdAt' => $threadData[0]['createdAt'],
            'editedAt' => $threadData[0]['editedAt'],
            'comments' => [],
            'category' => null,
            'images' => []
         ];

         $commentIds = [];
         $imageIds = [];

         foreach ($threadData as $row) {
            if ($row['comment_id'] && !in_array($row['comment_id'], $commentIds)) {
               $thread['comments'][] = [
                  'id' => $row['comment_id'],
                  'content' => $row['comment_content'],
                  'userId' => $row['comment_userId'],
                  'createdAt' => $row['comment_created_at'],
                  'editedAt' => $row['comment_edited_at'],
                  'deleted' => $row['comment_deleted'],
               ];
               $commentIds[] = $row['comment_id'];
            }

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
         echo json_encode(["success" => false, "error" => "No thread found with the provided ID."]);
         exit();
      }
   }

   view("threads/one.view.php", [
      "heading" => "Single Thread",
      "thread" => $thread
   ]);
} elseif ($method === 'DELETE') {
   $stmt = $db->query("UPDATE threads SET deleted = 1 WHERE id = :id and userId = :userId", [
      ":id" => $threadId,
      ":userId" => $_SESSION['userId']
   ]);

   $cacheKey = "thread:" . $threadId;
   $cache->delete($cacheKey);

   if ($db->rowCount($stmt) !== 0) {
      echo json_encode(["success" => true, "message" => "Thread deleted successfully."]);
   } else {
      echo json_encode(["success" => false, "message" => "Access Denied"]);
   }
} else {
   echo json_encode(["success" => false, "error" => "Invalid HTTP method."]);
}
