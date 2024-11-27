<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

if ($method === "GET") {
   view("threads/create.view.php", [
      "heading" => "Create Page"
   ]);
} else {

   $title = $_POST['title'] ?? null;
   $content = $_POST['content'] ?? null;
   $category = strtolower($_POST['category']) ?? null;
   $imageUrls = $_POST['imageUrl'] ?? [];

   $cache->clearExpired();
   $cachedThread = $cache->get('thread:' . $title);
   if ($cachedThread) {
      http_response_code(409); // Conflict
      echo json_encode(['success' => false, 'error' => "Someone just created a thread with the same name."]);
      exit();
   }

   if (empty($title) || empty($content) || empty($category)) {
      http_response_code(400); // Bad Request
      echo json_encode(
         ['success' => false, $title, $content, $category, 'error' => "All fields are required."]
      );
      exit();
   }

   try {
      $db->beginTransaction();

      $query = "INSERT INTO threads (title, content, userId) VALUES (:title, :content, :userId)";
      $db->query($query, [
         ':title' => $title,
         ':content' => $content,
         ':userId' => $_SESSION['userId']
      ]);
      $threadId = $db->lastInsertId();

      $categoryId = null;
      $cachedCategory = $cache->get('category:' . $category);

      if (is_array($cachedCategory) && isset($cachedCategory['value'])) {
         $categoryId = (int) $cachedCategory['value'];
      } else {
         $query = "SELECT id FROM categories WHERE name = :name LIMIT 1";
         $stmt = $db->query($query, [':name' => $category]);
         $result = $db->getOne($stmt);
         if ($result) {
            $categoryId = (int) $result['id'];
         } else {
            $query = "INSERT INTO categories (name) VALUES (:name)";
            $db->query($query, [':name' => $category]);
            $categoryId = (int) $db->lastInsertId();
         }
         $cache->set('category:' . $category, $categoryId);
      }

      $query = "INSERT INTO thread_category_link (threadId, categoryId) VALUES (:threadId, :categoryId)";
      $db->query($query, [
         ':threadId' => $threadId,
         ':categoryId' => $categoryId
      ]);

      if (!empty($imageUrls)) {
         foreach ($imageUrls as $imageUrl) {
            if (!empty($imageUrl)) {
               $query = "INSERT INTO thread_images (threadId, imageUrl) VALUES (:threadId, :imageUrl)";
               $db->query($query, [
                  ':threadId' => $threadId,
                  ':imageUrl' => $imageUrl
               ]);
            }
         }
      }

      $db->commit();
      $cache->set('thread:' . $title, $title);

      http_response_code(201); // Created
      echo json_encode(['success' => true, 'message' => "Thread created successfully.", "categoryId" => $categoryId, "cachedCategory" => $cachedCategory]);
   } catch (Exception $e) {
      $db->rollBack();
      http_response_code(500); // Internal Server Error
      echo json_encode(['success' => false, 'error' => "$e", "categoryId" => $categoryId, "cachedCategory" => $cachedCategory]);
   }
}
