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

   $rows = $db->getAll($stmt);

   if (empty($rows)) {
      // 404 Not Found: Thread not found or deleted
      http_response_code(404);
      echo json_encode(["success" => false, "error" => "Thread not found or deleted."]);
      exit();
   }
   $thread = [
      "id" => $rows[0]['id'],
      "title" => $rows[0]['title'],
      "content" => $rows[0]['content'],
      "userId" => $rows[0]['userId'],
      "category_id" => $rows[0]['category_id'],
      "category_name" => $rows[0]['category_name'],
      "images" => array_column($rows, 'image_url')
   ];

   if ($_SESSION['userId'] !== $thread['userId']) {
      // 403 Forbidden: User does not have permission to edit this thread
      http_response_code(403);
      echo json_encode(["success" => false, "error" => "Forbidden. You do not have permission to edit this thread."]);
      exit();
   }

   // 200 OK: Successfully fetched the thread data
   http_response_code(200);
   view("threads/edit.view.php", [
      "heading" => "Edit Thread",
      "thread" => $thread,
   ]);
} elseif ($method === 'PUT') {
   $data = json_decode(file_get_contents('php://input'), true);

   if (!$data['title'] || !$data['content']) {
      // 400 Bad Request: Title and content are required
      http_response_code(400);
      echo json_encode(["success" => false, "error" => "Title and content are required."]);
      exit();
   }

   $stmt = $db->query(
      "SELECT userId FROM threads WHERE id = :id AND deleted = 0",
      [":id" => $threadId]
   );
   $existingThread = $db->getOne($stmt);

   if (!$existingThread) {
      // 404 Not Found: Thread not found or deleted
      http_response_code(404);
      echo json_encode(["success" => false, "error" => "Thread not found or deleted."]);
      exit();
   }

   if ($_SESSION['userId'] !== $existingThread['userId']) {
      // 403 Forbidden: User does not have permission to edit this thread
      http_response_code(403);
      echo json_encode(["success" => false, "error" => "Forbidden. You do not have permission to edit this thread."]);
      exit();
   }

   $db->query(
      "UPDATE threads SET title = :title, content = :content, editedAt = NOW() WHERE id = :id",
      [
         ":title" => $data['title'],
         ":content" => $data['content'],
         ":id" => $threadId
      ]
   );

   if (isset($data['category'])) {
      $categoryName = $data['category'];
      $stmt = $db->query("SELECT id FROM categories WHERE name = :name LIMIT 1", [":name" => $categoryName]);
      $existingCategory = $db->getOne($stmt);

      if ($existingCategory) {
          $categoryId = $existingCategory['id'];
      } else {
          $query = "INSERT INTO categories (name) VALUES (:name)";
          $db->query($query, [':name' => $categoryName]);
          $categoryId = $db->lastInsertId();
      }

      $db->query("DELETE FROM thread_category_link WHERE threadId = :threadId", [":threadId" => $threadId]);

      $db->query(
          "INSERT INTO thread_category_link (threadId, categoryId) VALUES (:threadId, :categoryId)",
          [":threadId" => $threadId, ":categoryId" => $categoryId]
      );
   }

   if (isset($data['images'])) {
      $db->query("DELETE FROM thread_images WHERE threadId = :threadId", [":threadId" => $threadId]);

      foreach ($data['images'] as $imageUrl) {
         $db->query(
            "INSERT INTO thread_images (threadId, imageUrl) VALUES (:threadId, :imageUrl)",
            [":threadId" => $threadId, ":imageUrl" => $imageUrl]
         );
      }
   }

   $cache->delete("thread:$threadId");

   // 200 OK: Successfully updated the thread
   http_response_code(200);
   echo json_encode(["success" => true, "message" => "Thread updated successfully."]);
}
