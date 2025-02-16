<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();
if (!isset($params['id'])) {
   // Thread ID not provided
   sendJsonResponse(false, "Thread ID not found.", null, 404);
   exit();
}

$threadId = $params['id'];
$cache->clearExpired();


if ($method === 'GET') {
   // Start transaction
   $db->beginTransaction();

   // Fetch thread details
   $stmt = $db->query(
      "SELECT t.*, 
                    cat.id AS category_id, cat.name AS category_name,
                    img.imageUrl AS image_url
             FROM threads t
             LEFT JOIN threadcategorylink tcl ON t.id = tcl.threadId
             LEFT JOIN categories cat ON tcl.categoryId = cat.id
             LEFT JOIN threadimages img ON t.id = img.threadId
             WHERE t.id = :id AND t.isDeleted = 0",
      [":id" => $threadId]
   );

   $rows = $db->getAll($stmt);

   if (empty($rows)) {
      $db->rollBack();
      sendJsonResponse(false, "Thread not found or deleted.", [], 404);
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

   $db->commit();

   if ($_SESSION['userId'] !== $thread['userId']) {
      sendJsonResponse(false, "You do not have permission to edit this thread.", null, 403);
      exit();
   }

   // Successfully fetched the thread data
   http_response_code(200);
   view("threads/edit.view.php", [
      "heading" => "Edit Thread",
      "thread" => $thread,
   ]);
} elseif ($method === 'PUT') {
   $data = json_decode(file_get_contents('php://input'), true);

   if (!$data['title'] || !$data['content']) {
      sendJsonResponse(false, "Title and content are required.", null, 400);
      exit();
   }
   try {

      // Start transaction
      $db->beginTransaction();

      $stmt = $db->query(
         "SELECT userId, locked FROM threads WHERE id = :id AND isDeleted = 0",
         [":id" => $threadId]
      );
      $existingThread = $db->getOne($stmt);

      if (!$existingThread) {
         $db->rollBack();
         sendJsonResponse(false, "Thread not found or deleted.", null, 404);
         exit();
      }

      if ($existingThread['locked']) {
         $db->rollBack();
         sendJsonResponse(false, "This thread is locked and cannot be modified.", null, 403);
         exit();
      }

      if ($_SESSION['userId'] !== $existingThread['userId']) {
         $db->rollBack();
         sendJsonResponse(false, "You do not have permission to edit this thread.", null, 403);
         exit();
      }

      // Update thread
      $db->query(
         "UPDATE threads SET title = :title, content = :content, editedAt = NOW() WHERE id = :id",
         [
            ":title" => $data['title'],
            ":content" => $data['content'],
            ":id" => $threadId
         ]
      );

      // Update or insert category
      if (isset($data['category'])) {
         $categoryName = $data['category'];
         $stmt = $db->query("SELECT id FROM categories WHERE name = :name LIMIT 1", [":name" => $categoryName]);
         $existingCategory = $db->getOne($stmt);

         if ($existingCategory) {
            $categoryId = $existingCategory['id'];
         } else {
            $query = "INSERT INTO categories (name) VALUES (:name)";
            $db->query($query, [':name' => $categoryName]);

            $stmt = $db->query("SELECT id FROM categories WHERE name = :name LIMIT 1", [':name' => $categoryName]);
            $categoryId = $db->getOne($stmt)['id'];
         }

         // Update thread-category link
         $db->query("DELETE FROM threadcategorylink WHERE threadId = :threadId", [":threadId" => $threadId]);

         $db->query(
            "INSERT INTO threadcategorylink (threadId, categoryId) VALUES (:threadId, :categoryId)",
            [":threadId" => $threadId, ":categoryId" => $categoryId]
         );
      }

      // Update images
      if (isset($data['images'])) {
         $db->query("DELETE FROM threadimages WHERE threadId = :threadId", [":threadId" => $threadId]);

         foreach ($data['images'] as $imageUrl) {
            $db->query(
               "INSERT INTO threadimages (threadId, imageUrl) VALUES (:threadId, :imageUrl)",
               [":threadId" => $threadId, ":imageUrl" => $imageUrl]
            );
         }
      }

      $db->commit();
      $cache->delete("thread:$threadId");

      //  Successfully updated the thread
      sendJsonResponse(true, "Thread updated successfully.");
   } catch (Exception $e) {
      $db->rollBack();
      error_log($e->getMessage());
      sendJsonResponse(false, "An error occurred: " . $e->getMessage(), [], 500);
   }
}
